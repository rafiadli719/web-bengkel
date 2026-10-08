# Analisis HPP FIFO — Kondisi Saat Ini dan Rencana Implementasi

**Tanggal:** 2026-10-05
**Penyusun:** Claude Code (investigate-first, tanpa mengubah kode/data)
**Cakupan:** seluruh jejak HPP/FIFO di codebase `app/`, migrasi `db/`, dokumen `docs/`, dan DB lokal Laragon (`fitmotor_dbbengkel`).
**Status dokumen:** analisis + usulan desain. **Belum ada keputusan Owner**, jadi bagian "Usulan Desain" belum boleh dikerjakan.

---

## 1. Ringkasan Eksekutif

1. **FIFO belum ada sama sekali di web base.** Tidak ada tabel lot/layer, tidak ada kolom sisa lot, tidak ada kode yang menghitung HPP per batch. `git grep -i fifo -- app/` kosong. DB lokal tidak punya tabel `%fifo%`.
2. **Klaim "web base sudah pakai FIFO real-time" di `docs/fsd/FSD_SERVIS.md` §7 salah.** Klaim itu tidak punya dasar di kode. Dokumen itu perlu dikoreksi.
3. **HPP yang berjalan sekarang adalah "harga pokok master barang" (`tblitem.hargapokok`).** Nilainya hanya berubah lewat edit manual master barang atau sinkronisasi dari Access. **Pembelian baru tidak memperbarui nilainya.**
4. **Transaksi penjualan menyimpan `harga_pokok = 0`.** Semua jalur insert `tblpenjualan_detail` yang saya baca menulis literal `'0'` untuk kolom itu. Servis (`tblservis_barang`) tidak punya kolom HPP sama sekali. Akibatnya laporan laba yang membaca kolom itu salah (lihat §5).
5. **Metode costing resmi belum diputuskan Owner.** Ada empat kandidat yang bertabrakan: harga terakhir, rata-rata, FIFO, dan acuan 4-pembelian-termahal. Pertanyaan A1 di `docs/PERTANYAAN_OWNER_2026-08-30.md` masih kosong.
6. **Bukti dari Access tidak konsisten soal FIFO.** Dua analisis berbeda menyimpulkan hal berlawanan (§3). Tabel FIFO ada di Access, tetapi laporan laba-rugi Access memakai "acuan HPP" (bukan FIFO).

**Rekomendasi:** jangan bangun FIFO sebelum A1 dijawab. Yang aman dikerjakan sekarang: perbaiki pencatatan HPP saat transaksi (snapshot), karena itu dibutuhkan metode apa pun yang dipilih (§7, Fase 0).

---

## 2. Kondisi Aktual di Web Base (terverifikasi di kode dan DB)

### 2.1 Skema yang relevan

| Objek | Fakta | Bukti |
|---|---|---|
| `tbstok` | Ledger gerakan: `id, kd_cabang, keluar, keterangan, masuk, no_item, no_transaksi, tanggal, tipe`. **Tidak ada kolom harga/biaya.** | `information_schema` DB lokal |
| `tblitem.hargapokok` | Satu angka per item. 5.919 item: 5.734 > 0, 184 = 0. | query DB lokal |
| `tblpembelian_detail.harga_pokok` | Harga beli per baris faktur, ada. Kolom `id_inv varchar(50)` ada tapi diisi `''`. | skema + insert di `pembelian_add*.php` |
| `tblpenjualan_detail.harga_pokok` | Kolom ada, **diisi `'0'` saat insert**. | lihat §2.3 |
| `tblservis_barang` | **Tidak ada kolom `harga_pokok`/`hpp`.** | `information_schema` (tidak ada kolom `%pokok%`/`%hpp%`) |
| Tabel/view FIFO | **Tidak ada** (`SHOW TABLES LIKE '%fifo%'` kosong). | DB lokal |
| `gabung_hpp_acuan` | 27.517 baris, hasil mirror dari Access (`LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP`), kolom `tahun, siklus, no_item, frek, qty, beli, acuan_hpp, sts`. Tidak ada kode yang membacanya selain sync. | DB lokal + `app/access-sync.php:59` |
| `tbbeli_peritem_history` | **0 baris** di DB lokal, tapi `cari_item_pembelian_rst.php:450` memakainya untuk kolom "HPP Terakhir". | DB lokal |
| `alarm_harga_beli` | 0 baris. Trigger `trg_alarm_harga_beli` ada di `tblpembelian_detail` (AFTER INSERT). | DB lokal |

### 2.2 Tipe gerakan `tbstok` (dari data lokal)

| `tipe` | Arti (dari kode) | Jumlah baris lokal |
|---|---|---|
| `2` | Pembelian (masuk) | 84 |
| `3` | Penjualan (keluar) | 3 |
| `4` | Penjualan servis (keluar) | 320.130 |
| `7` | Retur pembelian (dihapus saat batal retur, `retur_pembelian_batal.php:41`) | tidak muncul di data lokal |
| `1`, `5`, `6` | **Belum saya verifikasi artinya** (kemungkinan saldo awal / stok masuk manual / stok keluar manual) | 24 / 77 / 3 |

### 2.3 Jalur HPP per jenis transaksi

| Transaksi | Apa yang terjadi pada HPP | File |
|---|---|---|
| **Pembelian** | Simpan harga beli di `tblpembelian_detail.harga_pokok`, tambah stok di `tbstok` (tipe 2). **Tidak mengubah `tblitem.hargapokok`.** Trigger hanya mencatat alarm bila harga berubah melewati threshold. | `pembelian_add.php:300,546`, `pembelian_add_rst.php:216,350` |
| **Penjualan** | Insert `tblpenjualan_detail` dengan `harga_pokok='0'`, kurangi stok di `tbstok` (tipe 3). HPP tidak pernah diisi belakangan. | `penjualan_add.php:104`, `penjualan_add_rst.php:136,225,471`, `proses-add-detail/penjualan.php:91`, `penjualan_cab_add_proses.php:163` |
| **Servis (barang)** | Insert `tblservis_barang` tanpa HPP. Komisi dan laba dihitung belakangan dengan JOIN ke `tblitem.hargapokok` **saat laporan/pembayaran dijalankan**. | `proses-add-detail/penjualan-brg-servis.php:87`, `_include_komisi_snapshot.php:33-41`, `issue_add.php:206` |
| **Antar cabang** | HPP transfer diambil dari `tblitem.hargapokok` saat kirim. | `pengadaan_antarcab_add.php:44`, `pengadaan_antarcab_proses.php:246`, `pengadaan_antarcab_push.php:63` |
| **Retur pembelian** | Mengurangi `qty_retur` dan stok. Tidak ada koreksi biaya. | `retur_pembelian_batal.php` |
| **Edit master barang** | `UPDATE tblitem SET hargapokok=...` manual. Inilah satu-satunya jalur web yang mengubah HPP master. | `barang_edit_proses.php:75`, `barang_edit_improved.php:125`, `jasa-input.php:112` |
| **Sync Access** | `UPSERT tblitem.hargapokok` dari Access. | `_include_access_sync.php:1022,1073` |

### 2.4 Siapa yang membaca HPP

| Pembaca | Sumber HPP yang dipakai | Konsekuensi |
|---|---|---|
| `lap_profit_penjualan.php:54` | `SUM(d.quantity * d.harga_pokok)` dari `tblpenjualan_detail` | Karena kolom berisi 0 untuk transaksi web, **HPP tampil 0 dan margin tampil mendekati 100%.** |
| `lap_profit_insentif.php:68` | `SUM(tothpp)` dari tabel hasil mirror Access (`tothpp`) | Benar hanya untuk data historis Access. Transaksi web baru tidak punya `tothpp`. |
| `_include_komisi_snapshot.php:33`, `issue_add.php:206`, `lap_komisi_mekanik.php:128` | `tblitem.hargapokok` **saat ini** | Laba lama berubah bila master diedit. Tidak ada snapshot saat transaksi. |
| `pesanan_pembelian_cetak.php:87` | fallback `tblitem.hargapokok` bila harga PO 0 | Sah, bukan HPP penjualan. |

> **Batasan verifikasi:** DB lokal hanya berisi 2 baris `tblpenjualan_detail`, jadi seberapa luas masalah "HPP = 0" di **produksi** belum bisa diukur dari sini. Pola kodenya konsisten di semua jalur insert, jadi risikonya nyata. Angkanya perlu dicek langsung di produksi (query di §8).

---

## 3. Bukti dari Access: Dokumen Saling Bertentangan

| Dokumen | Kesimpulan soal FIFO |
|---|---|
| `docs/audit/ANALISIS_FITMOTOR_APP_PROSES_BISNIS.md` §1 (2026-07-03, dari `FITMOTOR APP.MDB`, 285 query) | **Memakai FIFO.** `HPP_final = MAX( MAX(HPP_FIFO, HPP_TBLITEM), ACUAN_PKK )`. Dari query `HRGPOKOK_PENJUALAN`/`HRGPOKOK_SERVICE`. |
| `docs/audit/REVERSE_ENGINEERING_ACCESS_FITMOTOR_CUSTOMER_VEHICLE.md` §11.7 (dari `FITMOTOR GABUNG.MDB`) | **Bukan FIFO, tapi "acuan MAX".** Laporan laba-rugi (`LABARUGI_HPP_*`) hanya memakai `ACUAN_HPP × QTY`. |
| `docs/fsd/FSD_SERVIS.md` §7 | Mengutip rumus 3-lapis, lalu menulis "web base sudah pakai FIFO real-time". **Pernyataan kedua salah.** |
| `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md` | Access punya 5 tabel FIFO, web nol. Jangan bangun sebelum C1 dijawab. |
| `docs/GAP_ANALYSIS_JAWABAN_TEMUAN.md` C1 | Kode menampilkan harga beli terakhir. |
| `docs/PLANNING_KEPUTUSAN_BISNIS_2026-07-05.md` | Memuat kebutuhan "acuan HPP = termahal dari 4 pembelian terakhir" (disebut dua kali di dokumen requirement). |

**Cara membaca pertentangan ini.** Dua kesimpulan itu bisa sama-sama benar kalau dua mesin hitung berbeda hidup berdampingan di Access:

- **Mesin operasional** (`HRGPOKOK_*`, `TBL*DtFifo`): HPP per transaksi, FIFO ditambah pengaman MAX. Dipakai untuk komisi dan laba per transaksi.
- **Mesin akuntansi** (`LABARUGI_*`): HPP periodik dari acuan siklus. Definisi view di migrasi: `LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX` mengambil baris dengan `MAX(siklus)` per item. Jadi "MAX" di nama view adalah **siklus terbaru**, **bukan harga tertinggi**. Sumber `LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP` sendiri (kolom `beli`, `qty`, `acuan_hpp`) belum terbaca logikanya, dan contoh data `acuan_hpp = beli / qty` mengarah ke harga satuan per siklus.

Ini hipotesis saya, belum terbukti. Pembuktian butuh ekstraksi VBA/query pembangun tabel temp itu dari Access.

### 3.1 Struktur FIFO di Access (dari dokumen, bukan dari DB)

Menurut `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md:114`:

- `TBLItemFifo`: `IDInv`, `RefIDInv`, `TipeTrs`, `HargaPokok`, `Sisa` (satu baris per lot masuk, `Sisa` = sisa qty lot).
- `TBLPenjualanDtFifo`, `TBLServiceItemDtFifo`, `TBLReturBeliDtFifo`, `TBLItemKDtFifo`: tabel alokasi (baris transaksi keluar ke lot sumbernya).
- `TBLSetting.SistemHargaPokok`: sakelar metode costing.
- `TBLItem.Inv_JmlAwal / Inv_HrgAwal / Inv_TglAwal / Inv_IdAwal`: saldo awal item.
- Tiap tabel ada versi per cabang (`_PUSAT`, `_PESALAKAN`, `_PACUL`, `_CIKDITIRO`, `_TRAYEMAN`), digabung lewat view `GABUNG_*` dengan kolom `STS`.

Di MySQL, tabel FIFO itu **tidak ikut termigrasi** (audit `FITMOTOR_CRYSTAL_ACCESS_MYSQL_AUDIT.md`: status `EMPTY_ACCESS_TABLE`, "skema detail belum di-dump"). Hanya view `GABUNG_TBLPenjualanDtFifo` dan `GABUNG_TBLSERVICEITEMDTFIFO` yang didefinisikan di migrasi, dan view itu bergantung pada tabel yang tidak ada. Rencana migrasi data (`RENCANA_MIGRASI_DATA_ACCESS.md` A2.3) merekomendasikan **tidak mengimpor tabel FIFO mentah**, melainkan snapshot saldo awal lalu rekonstruksi dari transaksi valid.

---

## 4. Empat Kandidat Metode Costing

| Metode | Cara hitung | Kelebihan | Kekurangan untuk bengkel ini |
|---|---|---|---|
| **A. Harga terakhir** (kondisi de facto) | HPP = harga beli faktur terakhir | Sederhana, murah | Laba historis bergeser tiap kali harga beli berubah. Tidak ada snapshot. |
| **B. Rata-rata bergerak** | HPP = (nilai stok + nilai masuk) / (qty stok + qty masuk) | Stabil, 1 angka per item per cabang, mudah dibangun | Butuh nilai persediaan; retur dan koreksi stok harus menyesuaikan nilai. |
| **C. FIFO** | Keluar mengambil lot tertua dulu | Paling akurat untuk barang yang harganya naik-turun | Kompleks: lot per cabang, alokasi per baris, pembatalan/retur harus membalik alokasi, race condition. |
| **D. Acuan N pembelian termahal** | HPP = harga tertinggi dari 4 pembelian terakhir | Konservatif (laba tidak terlalu optimis) | Bukan costing akuntansi baku; hasil beda dari FIFO/rata-rata. |

Pertimbangan untuk bengkel multi-cabang (5 cabang, ±5,9 rb item, 320 rb+ baris servis historis di `tbstok`):

- Barang bengkel (oli, filter, kampas) punya perputaran cepat dan harga relatif stabil, jadi selisih FIFO vs rata-rata biasanya kecil.
- Kebutuhan bisnis yang tercatat (komisi mekanik, insentif advisor) bergantung pada **laba per baris transaksi**. Itu membutuhkan **snapshot HPP saat transaksi**, apa pun metodenya.
- FIFO memberi laba paling "adil" saat harga beli naik tajam, tetapi memaksa rekonsiliasi lot yang berat. Pertanyaannya apakah Owner butuh presisi itu.

---

## 5. Temuan dan Risiko (urut keparahan)

| # | Temuan | Dampak | Bukti |
|---|---|---|---|
| T1 | `tblpenjualan_detail.harga_pokok` selalu `0` pada transaksi web | Laporan Profit Penjualan menampilkan HPP 0 dan margin ~100%. Keputusan harga/margin bisa salah. | §2.3 |
| T2 | Servis tidak punya snapshot HPP; laba dihitung dari master saat ini | Laba/komisi servis lama berubah jika harga pokok master diedit. Audit mundur tidak mungkin. | `_include_komisi_snapshot.php:33`, `tblservis_barang` tanpa kolom HPP |
| T3 | Pembelian tidak memperbarui `tblitem.hargapokok` | Master barang menyimpan HPP basi. Semua modul yang bergantung padanya (antar cabang, komisi, PO fallback) memakai angka lama. | §2.3 |
| T4 | Dokumen menyatakan FIFO sudah jalan | Tim bisa mengira HPP sudah benar dan menunda perbaikan. | `FSD_SERVIS.md` §7 |
| T5 | `tbstok` tidak punya biaya | FIFO/rata-rata tidak bisa dihitung dari ledger yang ada. Butuh tabel baru. | skema `tbstok` |
| T6 | `tbbeli_peritem_history` kosong di lokal, tapi dipakai kolom "HPP Terakhir" | Kolom itu kosong/tanpa data di lingkungan tanpa sinkron Access. | `cari_item_pembelian_rst.php:450` |
| T7 | `gabung_hpp_acuan` (27 rb baris) tidak punya konsumer | Data acuan ada tetapi tidak dipakai. Selama diandalkan, web bergantung pada Access. | `access-sync.php:59` |
| T8 | `pesanan_penjualan` dan `penjualan_cab_*` juga menulis `harga_pokok=0` | Sama dengan T1, lebih luas. | `penjualan_cab_add_proses.php:170` |

---

## 6. Keputusan yang Dibutuhkan dari Owner

Diambil dari `docs/PERTANYAAN_OWNER_2026-08-30.md` (bagian A) dan temuan di atas. Tanpa jawaban A1, modul ini terblokir.

1. **A1 — Metode HPP resmi?** Harga terakhir / rata-rata / FIFO / acuan 4-termahal. *(rekomendasi teknis di bawah)*
2. **A2 — Cakupan:** HPP per cabang atau gabungan semua cabang? Stok di web sudah per cabang (`tbstok.kd_cabang`), jadi FIFO/rata-rata per cabang paling konsisten.
3. **Pengaman MAX:** pertahankan `MAX(HPP_metode, HPP_master, acuan)` seperti di Access, atau hapus? (Access memakainya agar HPP tidak anjlok ke 0 karena data rusak.)
4. **Barang yang HPP-nya 0 / tanpa histori beli** (184 item di lokal): pakai apa sebagai HPP awal?
5. **Saldo awal:** boleh memakai snapshot `tblitem.quantity × hargapokok` per cabang sebagai lot awal (sesuai rekomendasi `RENCANA_MIGRASI_DATA_ACCESS.md`)?
6. **Transaksi historis:** HPP transaksi lama (2025–sekarang) diisi ulang (backfill) atau dibiarkan 0 dan hanya berlaku mulai tanggal go-live?
7. **Antar cabang:** nilai transfer memakai HPP metode baru, atau tetap `tblitem.hargapokok`?

### Rekomendasi teknis

**Mulai dari snapshot HPP (Fase 0), lalu rata-rata bergerak (Fase 1), FIFO hanya kalau Owner butuh.** Alasannya: snapshot menutup T1/T2/T8 dan dibutuhkan semua metode. Rata-rata bergerak menutup T3 dengan kompleksitas jauh lebih rendah daripada FIFO. FIFO baru layak jika Owner menegaskan perlu akurasi per lot, karena biayanya paling besar dan paling rawan salah (retur, batal, koreksi, race condition).

Ini rekomendasi, bukan keputusan. Kalau Owner tetap memilih FIFO, §7.3 menjelaskan desainnya.

---

## 7. Usulan Desain Bertahap

### Fase 0 — Snapshot HPP saat transaksi (netral terhadap metode, aman dikerjakan sekarang)

Tujuan: transaksi menyimpan HPP yang berlaku **saat transaksi terjadi**.

1. Isi `tblpenjualan_detail.harga_pokok` saat insert/finalisasi dari sumber HPP yang berlaku (sementara `tblitem.hargapokok` per cabang). Titik ubah: semua jalur insert di §2.3 (kemungkinan 6–8 file). Gunakan satu helper (mis. `fmHppSaatIni($koneksi, $no_item, $kd_cabang)`) supaya perubahan metode kelak cukup di satu tempat.
2. Tambah kolom `harga_pokok` di `tblservis_barang` (migrasi reversible + default), isi saat item ditambahkan/dibayar.
3. Ubah `_include_komisi_snapshot.php`, `issue_add.php`, dan `lap_komisi_mekanik.php` supaya membaca snapshot, bukan `tblitem.hargapokok`.
4. Backfill transaksi lama: hanya jika Owner setuju (keputusan 6).
5. Uji: transaksi baru menghasilkan `harga_pokok > 0`; mengubah master tidak mengubah laba transaksi lama.

### Fase 1 — Rata-rata bergerak (opsional, bila Owner memilih B)

- Tabel `tbitem_hpp_saldo(kd_cabang, no_item, qty, nilai, hpp_rata2, updated_at)`.
- Pembelian: `nilai += qty × (harga_net)`, `qty += qty`, `hpp_rata2 = nilai / qty`. Update dalam transaksi DB yang sama dengan insert `tbstok`.
- Penjualan/servis: HPP baris = `hpp_rata2` saat itu (disnapshot Fase 0); kurangi `nilai` sebesar `qty × hpp_rata2`.
- Retur pembelian dan koreksi stok: sesuaikan `nilai` dan `qty` memakai HPP yang sama.

### 7.3 Fase 2 — FIFO (hanya bila Owner memilih C)

**Tabel baru (usulan):**

```text
tbitem_lot            -- 1 baris per lot masuk
  id, kd_cabang, no_item,
  tipe_sumber ('beli','saldo_awal','stok_masuk','antarcab_terima','retur_jual'),
  no_transaksi, tanggal_masuk,
  qty_awal, qty_sisa, harga_pokok,         -- harga satuan lot
  INDEX (kd_cabang, no_item, tanggal_masuk, id)

tbitem_lot_alokasi    -- 1 baris per pengambilan dari lot
  id, lot_id, tipe_keluar ('jual','servis','antarcab_kirim','retur_beli','stok_keluar'),
  no_transaksi, no_baris, qty, harga_pokok, tanggal
  INDEX (no_transaksi, no_baris)
```

**Algoritma keluar:** dalam satu transaksi DB, `SELECT ... FROM tbitem_lot WHERE kd_cabang=? AND no_item=? AND qty_sisa>0 ORDER BY tanggal_masuk, id FOR UPDATE`; ambil dari lot tertua sampai qty terpenuhi; tiap pengambilan menambah baris alokasi dan mengurangi `qty_sisa`; HPP baris = Σ(qty × harga lot) / qty total. Jika lot tidak cukup (stok negatif/legacy), pakai HPP fallback (master) untuk sisa qty dan tandai alokasi sebagai `fallback`.

**Titik integrasi:** pembelian (buat lot), penjualan, servis, antar cabang (kirim mengambil lot, terima membuat lot baru di cabang tujuan), retur pembelian (kembalikan dari lot asal), retur penjualan/servis (buat lot kembali di HPP asal), penyesuaian/opname, **dan pembatalan** (`servis cancel`, batal penjualan, batal retur harus membalik alokasi).

**Migrasi saldo awal:** satu lot per item per cabang: `qty = SUM(masuk-keluar) dari tbstok`, `harga = tblitem.hargapokok`, `tanggal_masuk = tanggal cut-off`, `tipe_sumber='saldo_awal'`. Jangan impor `TBL*DtFifo` Access mentah (duplikasi ledger, lihat `RENCANA_MIGRASI_DATA_ACCESS.md`).

**Risiko khusus FIFO:**

- Lot harus selalu rekonsiliasi dengan `tbstok`: `SUM(qty_sisa) = SUM(masuk-keluar)` per item per cabang. Perlu skrip audit rutin.
- Banyak jalur insert yang saat ini gagal diam-diam bila kolom wajib hilang (pola bug yang sering ditemukan di codebase ini). Setiap jalur baru harus mengecek hasil query dan dibungkus transaksi.
- Konkurensi: dua kasir menjual item sama bersamaan memerlukan `FOR UPDATE` pada lot.
- Dua jenis servis historis (320 rb baris `tbstok` tipe 4) tidak punya lot sumber, jadi hanya berlaku maju dari tanggal cut-off.

### Fase 3 — Acuan dan pengaman (opsional)

- Hitung acuan sendiri dari `tblpembelian_detail` (menggantikan mirror `gabung_hpp_acuan`), supaya web tidak bergantung pada Access.
- Pengaman `MAX(...)` jika Owner memutuskan dipertahankan.
- Audit trail perubahan HPP (disebut di `PLANNING_KEPUTUSAN_BISNIS` #16): tabel log perubahan.

---

## 8. Query Verifikasi untuk Produksi (read-only)

Jalankan di phpMyAdmin produksi untuk mengukur skala T1/T2. Tidak mengubah data.

```sql
-- Seberapa banyak penjualan web yang HPP-nya 0?
SELECT COUNT(*) total,
       SUM(harga_pokok = 0) hpp_nol,
       SUM(harga_pokok > 0) hpp_terisi
FROM tblpenjualan_detail
WHERE no_transaksi <> '';

-- Item master tanpa harga pokok
SELECT COUNT(*) total, SUM(hargapokok = 0) hp_nol FROM tblitem;

-- Apakah master pernah mengikuti pembelian? Bandingkan harga beli terakhir dengan master
SELECT i.noitem, i.hargapokok AS master,
       (SELECT d.harga_pokok FROM tblpembelian_detail d
         WHERE d.no_item = i.noitem ORDER BY d.id DESC LIMIT 1) AS beli_terakhir
FROM tblitem i
LIMIT 50;

-- Apakah ada tabel FIFO di produksi?
SHOW TABLES LIKE '%fifo%';
```

---

## 9. Hal yang Belum Saya Verifikasi

- Arti `tbstok.tipe` 1, 5, 6 dan kode jalur penyesuaian/opname/stok masuk-keluar manual (tidak dibaca di analisis ini).
- Isi DB **produksi** (hanya DB lokal yang diperiksa; lokal hanya punya 2 baris penjualan).
- Logika pembangun `LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP` di Access (butuh ekstraksi VBA/query dari file `.mdb`).
- Isi sebenarnya `TBLItemFifo` di Access (struktur kolom hanya dari dokumen audit, bukan dari DB).
- Apakah modul Keuangan Kasir / jurnal membaca HPP.

---

## 10. Daftar Dokumen dan Kode Sumber

**Kode:** `app/penjualan_add.php`, `app/penjualan_add_rst.php`, `app/proses-add-detail/penjualan.php`, `app/penjualan_cab_add_proses.php`, `app/pembelian_add.php`, `app/pembelian_add_rst.php`, `app/proses-add-detail/penjualan-brg-servis.php`, `app/_include_komisi_snapshot.php`, `app/issue_add.php`, `app/lap_profit_penjualan.php`, `app/lap_profit_insentif.php`, `app/lap_komisi_mekanik.php`, `app/pengadaan_antarcab_*.php`, `app/retur_pembelian_batal.php`, `app/barang_edit_proses.php`, `app/cari_item_pembelian_rst.php`, `app/access-sync.php`, `app/_include_access_sync.php`, `db/migrations/2026-07-17_run_alarm_harga_beli.php`, `db/migrations/2026-06-24_fitmotor_access_views.sql`.

**Dokumen:** `docs/audit/ANALISIS_FITMOTOR_APP_PROSES_BISNIS.md`, `docs/audit/REVERSE_ENGINEERING_ACCESS_FITMOTOR_CUSTOMER_VEHICLE.md` §11.7, `docs/fsd/FSD_SERVIS.md` §7, `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md`, `docs/GAP_ANALYSIS_JAWABAN_TEMUAN.md`, `docs/PERTANYAAN_OWNER_2026-08-30.md`, `docs/PLANNING_KEPUTUSAN_BISNIS_2026-07-05.md`, `docs/migration/RENCANA_MIGRASI_DATA_ACCESS.md`, `docs/audit/FITMOTOR_CRYSTAL_ACCESS_MYSQL_AUDIT.md`.
