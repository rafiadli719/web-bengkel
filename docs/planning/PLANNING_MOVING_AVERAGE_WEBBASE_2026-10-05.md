# Planning dan Task — Implementasi Moving Average Costing di Web Base Fit Motor

**Tanggal:** 2026-10-05
**Sumber requirement:** `docs/PRD_Moving_Average_Fit_Motor_Webbase_v1.md` (v1.0, Business Analyst)
**Penyusun:** Claude Code (investigate-first; **tidak ada kode, skema, atau data yang diubah**)
**Status:** PLANNING SAJA. Belum ada task yang dieksekusi. Menunggu review dan keputusan Rafi/Owner (§9).
**Dokumen terkait:** `docs/planning/ANALISIS_HPP_FIFO_2026-10-05.md` (kondisi HPP saat ini; sebagian isinya sekarang tersupersede oleh PRD ini, lihat §1.3).

---

## 1. Ringkasan Eksekutif

### 1.1 Kesimpulan analisis

1. **PRD ini menutup pertanyaan A1** (metode HPP). Metode resmi = **Moving Average per lokasi** (per item per cabang, Gudang Pusat dihitung sebagai satu lokasi). FIFO tidak jadi dibangun.
2. **Web base belum punya fondasi untuk menjalankan PRD ini.** PRD mengasumsikan ada saldo per lokasi yang bersih, nilai persediaan, jurnal, periode, dan satu pintu posting. Yang ada sekarang: ledger kuantitas `tbstok` tanpa nilai, ditulis langsung oleh ±30 file PHP, tanpa transaksi DB.
3. **Ada lima masalah data/arsitektur yang harus dibereskan sebelum satu baris pun logika average ditulis** (§3, P1–P5). Yang paling berat: kunci lokasi tidak konsisten (`CDT/PCL/PSL/TRM` vs `CIKDITIRO/PACUL/PESALAKAN/TRAYEMAN`) dan 99% pasangan item–cabang di DB lokal bersaldo negatif.
4. **Aturan "stok minus dilarang total" (BR-05) tidak bisa langsung diaktifkan.** Aturan itu hanya valid setelah saldo awal (opening stock) per lokasi ditetapkan lewat stok fisik. Tanpa itu, semua penjualan akan ditolak.
5. **Sebagian PRD tidak punya padanan sama sekali di web base**: periode persediaan (open/closed/locked), jurnal akuntansi, Piutang Klaim Supplier, Barang dalam Perjalanan bernilai, Selisih Harga Pembelian, revaluasi, bonus proporsional, konversi satuan, pemakaian internal. Itu modul baru, bukan perubahan kecil.
6. **Rekomendasi:** bangun bertahap dalam 6 fase (§6), dengan satu service pusat `InventoryService`, mode bayangan (shadow) sebelum cutover, dan cutover per cabang. Mulai dari Fase 0 (fondasi data), yang bisa dikerjakan tanpa menunggu semua keputusan Owner.

### 1.2 Estimasi kasar

| Fase | Isi | Estimasi (hari kerja, 1 developer) |
|---|---|---|
| 0 | Fondasi data: kunci lokasi, saldo awal, audit | 6–9 |
| 1 | Inti: `InventoryService`, average, HPP snapshot, shadow mode | 12–16 |
| 2 | Titik transaksi: pembelian, penjualan, servis, retur, transfer | 14–20 |
| 3 | Koreksi maju, maker-checker, Selisih Harga Pembelian, periode | 12–16 |
| 4 | Fitur lanjutan: bonus, klaim supplier, revaluasi, scrap/internal, opname | 10–14 |
| 5 | Laporan, jurnal akuntansi (butuh keputusan COA), cutover | 10–15 |
| **Total** | | **± 64–90 hari kerja** (±3–4,5 bulan) |

Estimasi ini kasar, dibuat tanpa mengukur produksi. Dua hal yang paling bisa menggeser angka: kondisi data produksi (§8) dan keputusan jurnal akuntansi (D-07).

### 1.3 Hubungan dengan dokumen FIFO

`ANALISIS_HPP_FIFO_2026-10-05.md` §7 mengusulkan "Fase 0 snapshot HPP → Fase 1 rata-rata bergerak → Fase 2 FIFO". PRD ini memilih rata-rata bergerak, jadi:

- Fase 2 FIFO dan Fase 3 acuan di dokumen itu **batal**.
- Temuan T1–T8 (HPP 0 di penjualan, servis tanpa HPP, master basi) **tetap berlaku** dan ditutup oleh Fase 1–2 di sini.
- Catatan: aturan "HPP FIFO" yang tercatat sebagai Rule project harus diganti jadi "HPP Moving Average per lokasi" (task F5-09).

---

## 2. Cakupan PRD dan Pembagian Rilis

PRD sangat luas. Saya sarankan memecahnya menjadi tiga rilis supaya nilai bisnis keluar lebih dulu.

| Rilis | Isi | Nilai bisnis |
|---|---|---|
| **R1 — Inti average** (Fase 0–2) | Saldo per lokasi bernilai, average, HPP tersimpan di tiap transaksi, penolakan stok minus, transfer dengan transit, retur | Laba per transaksi benar; komisi dan laporan profit akurat |
| **R2 — Kontrol dan koreksi** (Fase 3–4) | Koreksi maju, maker-checker, Selisih Harga Pembelian, periode, bonus, klaim, revaluasi, scrap, opname | Audit dan pengendalian; menggantikan edit/hapus langsung |
| **R3 — Akuntansi dan laporan** (Fase 5) | Jurnal real-time, neraca persediaan, laporan baru, cutover seluruh cabang | Siap audit/pajak |

**Di luar cakupan** (sesuai PRD §1 dan BR-20): modul Produksi/Assembly, backdate, recalculation berantai, konsinyasi, pricing engine, bundling/unbundling.

---

## 3. Temuan Analisis: Kesiapan Web Base terhadap PRD

### 3.1 Prasyarat yang harus dibereskan dulu (P1–P5)

| # | Temuan | Bukti (kode/DB lokal) | Dampak ke PRD |
|---|---|---|---|
| **P1** | **Kunci lokasi tidak konsisten.** `tbstok.kd_cabang` berisi `CDT`, `PCL`, `PSL`, `PESALAKAN`, `PST`, `001`, dan string kosong. Master `tbcabang.kode_cabang` memakai `CIKDITIRO/PACUL/PESALAKAN/PST/TRAYEMAN`. `tblservice.kd_cabang` juga campur (`TRM 1.933`, `CDT 14.566`, `PCL 38.727`, `PSL 48.312` baris vs `TRAYEMAN 2`, `PESALAKAN 9`). Pemetaan singkatan ada di `_include_access_sync.php:2817`. | BR-10 (average per lokasi) membutuhkan satu kunci lokasi yang pasti. Saat ini saldo satu cabang terpecah di beberapa kode, dan transaksi web memakai kode baru, jadi tidak melihat riwayat lama. |
| **P2** | **Saldo ledger tidak bisa dipercaya.** 6.255 pasangan item–cabang di `tbstok`: 62 positif, 14 nol, **6.179 negatif** (minimum −15.198). Sebabnya: 320 rb baris servis historis (tipe 4, keluar) hasil migrasi Access masuk ke ledger **tanpa** saldo awal/penerimaan yang menyeimbangkan. | BR-05 (tanpa stok minus) akan menolak hampir semua transaksi keluar kalau diaktifkan di atas data ini. Butuh saldo awal baru dari stok fisik. |
| **P3** | **Dua sumber stok yang saling bertentangan.** `tblitem.quantity` (snapshot dari Access) berbeda dari `SUM(masuk-keluar)` ledger untuk **3.986 item**. Kode membaca keduanya: `view_stok_master` (turunan `tbstok`) untuk validasi transaksi, `tblitem.quantity` sebagai fallback di `servis-add-item-cari.php:601`. | Perlu satu sumber kebenaran. Tanpa itu, saldo awal tidak bisa ditetapkan. |
| **P4** | **Tidak ada nilai di ledger.** `tbstok` hanya punya `tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang` (qty `int`). HPP penjualan tersimpan `0`, servis tanpa kolom HPP (lihat dokumen FIFO §2–§5). Tidak ada tabel nilai persediaan atau average. | Semua tabel PRD §8 (balance, ledger bernilai, average history, transit, PPV, klaim, periode) harus dibuat dari nol. |
| **P5** | **Tidak ada jurnal/COA persediaan.** Tidak ada tabel `%jurnal%` atau `%periode%` persediaan. Yang ada: `tbakun`, `tbakun_pos`, `tblakunkas`, `master_akun_closing_kasir` (milik modul kas/closing kasir), dan `audit_log_closing_kasir`. | PRD §3 dan §14.I mensyaratkan posting jurnal real-time (akun Inventory, Barang dalam Perjalanan, Selisih Harga Pembelian, Piutang Klaim Supplier). COA persediaan belum ada. Perlu keputusan D-07. |

### 3.2 Kondisi teknis yang memengaruhi desain (P6–P12)

| # | Temuan | Bukti | Dampak |
|---|---|---|---|
| **P6** | **Stok dipotong saat simpan, divalidasi saat tambah item.** Validasi memakai `view_stok_master` saat item masuk keranjang (`proses-add-detail/penjualan.php:13-50`); `INSERT INTO tbstok` baru terjadi saat faktur disimpan (`penjualan_add_rst.php:471`). Tidak ada validasi ulang dan tidak ada lock saat simpan. | Dua kasir bisa menjual stok yang sama; saldo bisa negatif. Bertentangan dengan V-01/V-08/EC-09. |
| **P7** | **Jalur tulis `tbstok` tersebar.** ±45 file menyebut `tbstok`, ±30 menulis (pembelian ×5, penjualan ×5, servis ×6, retur ×3+, antar cabang ×3, stok masuk/keluar manual, DO, save_*.php lama). Semua memakai `mysqli_query` langsung tanpa transaksi DB dan banyak yang tidak mengecek hasil query. | Perlu satu pintu posting. Mengubah ±30 file sekaligus berisiko; butuh adapter bertahap. |
| **P8** | **Ada hard delete dan edit.** `retur_pembelian_batal.php:41` dan `retur_penjualan_batal.php:35` menghapus baris `tbstok`; `_include_access_sync.php:1101` melakukan DELETE+INSERT per item saat sync; `pembelian_edit_item.php:66` mengubah `harga_pokok`; ada banyak DELETE `tblservis_barang`. | Bertentangan dengan BR-06, EC-06, EC-07, V-02 (ledger immutable). |
| **P9** | **Backdate dimungkinkan.** `tbstok.tanggal` diisi dari date-picker form (`$txttglpesan`, `$tgl_pilih`). | BR-06 dan BR-12 melarang backdate; tanggal harus tanggal input. |
| **P10** | **Tipe gerakan ambigu.** `tipe='2'` dipakai untuk Pembelian **dan** Terima Antar Cabang; `tipe='3'` untuk Penjualan **dan** Kirim Antar Cabang; `tipe` 1/5/6 belum terdokumentasi; 7=retur beli, 8=retur jual, 9=retur servis; `save_penjualan.php` (lama) memakai `'2'` untuk penjualan. Kolom `tipe` hanya `varchar(1)`. | Ledger baru butuh kode transaksi yang jelas. Laporan lama yang menyaring `tipe` akan salah baca bila kode diubah. |
| **P11** | **Transit antar cabang sudah ada, tapi tanpa nilai.** `tblorder_antarcab_header.status` bernilai `terkirim`, `dikirim`, `selesai` (dua ejaan untuk "dikirim"). Kirim memotong stok asal (`pengadaan_antarcab_proses.php:84`), terima menambah stok tujuan (`pengadaan_antarcab_terima.php:97`). HPP transfer diambil dari `tblitem.hargapokok`, bukan average asal. Tidak ada tabel transit bernilai, tidak ada aging, tidak ada alur gagal kirim. | Alur dasarnya bisa dipakai ulang. Perlu ditambah nilai, status transit eksplisit, dan pembalikan (BR-13–15). |
| **P12** | **Servis memotong stok di beberapa titik.** `servis-reguler-byr.php:585` (saat bayar), plus `servis-input-reguler.php:798`, `...-rst.php:965`, `...-jemput.php:2623`, `...-jemput-rst.php:1196`, `servis-garansi.php:1165`. Belum diverifikasi apakah ada risiko potong ganda antar titik itu. | PRD tidak menyebut kapan stok servis turun (§4.4). Perlu keputusan D-04 dan audit potong ganda. |

### 3.3 Aset yang bisa dipakai ulang

| Aset | Dipakai untuk |
|---|---|
| Semua tabel `tbstok`, `tbitem_masuk/keluar_*`, `tbkoreksi_stok_*`, `tblitem_koreksi_*` (InnoDB) | Semua tabel inti sudah InnoDB (kecuali `tbjabatan`, tidak relevan). Row lock dan transaksi DB bisa dipakai. |
| Alur approval PO (`tblpo_approval_log`, `tb_master_approval_pembelian`), `retur_servis_approve.php`, `konfirmasi_buka_transaksi` | Pola maker-checker (BR-27) |
| RBAC `menu_config.php` + `requirePermission()` | Pembatasan role Finance HQ (V-11), approval berjenjang |
| `tb_user_activity_log`, `audit_log_closing_kasir` | Pola audit log; untuk Who-What-When-Why-Before-After perlu tabel khusus |
| Trigger `trg_alarm_harga_beli` + `alarm_harga_beli` | Tetap berguna; baca harga beli per transaksi |
| Alur antar cabang (`pengadaan_antarcab_*`) | Dasar fitur Transfer + transit |
| Pola migrasi runner `db/migrations/*_run_*.php` (reversible) | Semua perubahan skema |
| Idempotency: `INSERT .. WHERE NOT EXISTS`/unique key | V-07 |

---

## 4. Analisis Kesesuaian per Business Rule

Legenda: ✅ sudah sesuai · 🟡 sebagian · ❌ belum ada · ⚠️ bertentangan dengan kondisi sekarang.

| BR | Ringkasan aturan | Kondisi web base | Status | Task |
|---|---|---|---|---|
| BR-01/02/03 | Average berubah saat masuk, tetap saat keluar; HPP keluar = average saat posting | Tidak ada average | ❌ | F1-03, F1-04 |
| BR-04 | Stok 0 lalu masuk → average = harga masuk | Tidak ada | ❌ | F1-03 |
| BR-05 | Stok minus dilarang total | Validasi hanya di keranjang, tanpa lock; saldo data sudah negatif | ⚠️ | F0-04, F1-05, F2-xx |
| BR-06 | Tanpa backdate | Tanggal diambil dari form | ⚠️ | F2-12 |
| BR-07 | Retur penjualan masuk dengan harga = HPP asal | Retur menambah stok (tipe 8/9) tanpa nilai | 🟡 | F2-07 |
| BR-08 | Periode Open/Closed/Locked | Tidak ada periode persediaan | ❌ | F3-06 |
| BR-09 | Cancel vs Void | Servis cancel ada (`servis-delete.php`), penjualan tidak ada pembeda | 🟡 | F2-09 |
| BR-10 | Average per lokasi | Kunci lokasi tidak konsisten | ⚠️ | F0-01..03 |
| BR-11 | Average Cost History | Tidak ada | ❌ | F1-02 |
| BR-12 | Transaction Date vs Correction Date | Satu kolom `tanggal` | ❌ | F1-02 |
| BR-13/14/15 | Transfer via transit, average tujuan saat terima, batal kembali ke asal | Transit parsial tanpa nilai/pembalikan | 🟡 | F2-08 |
| BR-16 | Beli darurat cabang masuk average cabang itu | Alur pembelian cabang ada (`pembelian_cab_add_proses.php`) | 🟡 | F2-01 |
| BR-17 | Bonus supplier dialokasi proporsional | Tidak ada | ❌ | F4-01 |
| BR-18 | Replacement = retur + beli baru | Tidak ada | ❌ | F4-02 |
| BR-19 | Piutang Klaim Supplier | Tidak ada | ❌ | F4-03 |
| BR-20 | Konsinyasi tidak diimplementasi | — | ✅ (di luar cakupan) | — |
| BR-21 | Koreksi qty via Stock Adjustment hari ini | Ada stok masuk/keluar manual, tanpa referensi/alasan wajib | 🟡 | F3-01 |
| BR-22/23 | Koreksi harga: sisa masuk average, terjual ke Selisih Harga Pembelian | Tidak ada | ❌ | F3-02, F3-03 |
| BR-24/25 | Salah tanggal dicatat; salah lokasi lewat transfer | Tidak ada | ❌ | F3-04 |
| BR-26 | Salah bayar = jurnal koreksi | Di luar persediaan | — | F5-04 |
| BR-27 | Maker-checker + audit Who-What-When-Why-Before-After | Pola approval ada di PO/retur; belum untuk stok | 🟡 | F3-05 |

---

## 5. Keputusan Arsitektur (Usulan)

### 5.1 Satu pintu posting: `InventoryService`

Class PHP tunggal (mis. `app/lib/InventoryService.php`) dengan fungsi tingkat tinggi. Semua modul memanggilnya; tidak ada lagi `INSERT INTO tbstok` langsung.

```text
InventoryService::postIn(lokasi, item, qty, unitCost, refType, refNo, opts)
InventoryService::postOut(lokasi, item, qty, refType, refNo, opts)   // mengembalikan HPP
InventoryService::shipTransfer(...) / receiveTransfer(...) / returnTransfer(...)
InventoryService::postCorrection(...)   // lewat maker-checker
```

Aturan inti di dalam service, dalam satu transaksi DB:

1. `SELECT ... FOR UPDATE` pada baris `inv_balance (lokasi, item)`.
2. Cek idempotency (`UNIQUE (ref_type, ref_no, ref_line)`).
3. Hitung average/HPP sesuai BR.
4. Tolak bila stok keluar melebihi saldo.
5. Tulis `inv_ledger`, `inv_avg_history`, update `inv_balance`.
6. Tulis proyeksi ke `tbstok` (kompatibilitas pembaca lama).
7. Commit; bila gagal, rollback penuh (V-04).

### 5.2 Ledger baru + `tbstok` sebagai proyeksi (rekomendasi)

| Opsi | Kelebihan | Kekurangan |
|---|---|---|
| **A (rekomendasi): tabel baru `inv_*`, `tbstok` tetap ditulis oleh service sebagai proyeksi** | Pembaca lama (`view_stok_master`, `kartu_stok.php`, `MinMaxCalculator`, `pr_auto_draft`, validasi cari item) tidak rusak; migrasi bertahap; rollback mudah (matikan service, kembali ke tulis langsung) | Dua tabel harus selalu cocok → butuh job rekonsiliasi |
| B: tambah kolom nilai ke `tbstok` | Satu tabel | Qty `int`, `tipe varchar(1)`, tanpa timestamp; ratusan ribu baris lama berisi sampah (P2); sulit menegakkan immutability karena sync Access menulis di sini |
| C: ganti `tbstok` seluruhnya | Bersih | Memaksa ubah ±45 file dan semua laporan sekaligus; risiko tertinggi |

### 5.3 Desain data (draf, final di Fase 1)

| Tabel | Fungsi | Kolom kunci |
|---|---|---|
| `inv_location` | Satu kunci lokasi kanonik + alias lama | `kode` (PK, kanonik = `tbcabang.kode_cabang`), `alias` (CDT, PCL, PSL, TRM, 001, ''), `is_pusat` |
| `inv_balance` | Saldo per item per lokasi | `kd_cabang`, `no_item` (UNIQUE), `qty_on_hand`, `average_cost`, `total_value`, `version`, `updated_at` |
| `inv_ledger` | Buku kartu stok bernilai, **append-only** | `id`, `kd_cabang`, `no_item`, `trx_type`, `ref_type`, `ref_no`, `ref_line`, `qty_in`, `qty_out`, `unit_cost`, `value_delta`, `avg_before`, `avg_after`, `qty_after`, `transaction_date`, `correction_date`, `is_correction`, `created_by`, `created_at`; UNIQUE (`ref_type`,`ref_no`,`ref_line`,`trx_type`) |
| `inv_avg_history` | Riwayat perubahan average | `item`, `lokasi`, `avg_before`, `avg_after`, `trigger_type`, `trigger_ref`, `is_correction`, `changed_by`, `changed_at` |
| `inv_transit` | Barang dalam perjalanan | `no_transfer`, `item`, `from`, `to`, `qty`, `unit_cost`, `value`, `status` (in_transit/received/returned), `shipped_at`, `received_at` |
| `inv_ppv_log` | Selisih Harga Pembelian | `item`, `lokasi`, `qty_affected`, `price_difference`, `total_variance`, `correction_ref`, `posted_to_account`, `period` |
| `inv_claim_receivable` | Piutang Klaim Supplier | `item`, `qty`, `value`, `supplier_id`, `status` |
| `inv_correction_request` | Pengajuan koreksi (maker-checker) | `id`, `type`, `ref_origin`, `payload_json`, `reason`, `maker`, `checker`, `approver`, `status`, `created_at`, `decided_at` |
| `inv_audit_log` | Who-What-When-Why-Before-After-Approval | JSON before/after, `ref`, `actor`, `reason`, `approval_id` |
| `inv_config` | Parameter (ambang opname, hari aging transit) | `config_key`, `value`, `updated_by`, `updated_at` |
| `inv_period` | Status periode per lokasi | `period`, `kd_cabang`, `status` (open/closed/locked) |

Semua perubahan lewat migrasi reversible (pola `db/migrations/*_run_*.php [up|down]`), uji dulu di DB lokal.

### 5.4 Strategi transisi: shadow lalu cutover per cabang

1. **Shadow mode.** `InventoryService` menghitung dan mencatat ke `inv_*` **di samping** jalur lama yang tetap berjalan. Dibandingkan harian. Tidak ada perubahan perilaku untuk user.
2. **Cutover per cabang** (satu cabang percontohan dulu, mis. PST yang punya 32 item di ledger, lalu cabang lain). Pada hari cutover: stok fisik dihitung, opening stock dimuat, aturan BR-05 aktif untuk cabang itu.
3. **Feature flag per cabang** (`inv_config`): `inv_mode = off | shadow | live`. Rollback = kembalikan ke `shadow/off`.
4. **Sync Access** untuk cabang yang sudah `live` harus berhenti menulis `tbstok`/`tblitem` (P8). Alternatif: sync hanya ke tabel mirror.

### 5.5 Pembulatan dan satuan (perlu keputusan)

PRD V-09: average dibulatkan 2 desimal, round half up. Dua jebakan:

- **Selisih pembulatan:** nilai persediaan = qty × average yang sudah dibulatkan akan berbeda dari nilai sebenarnya (contoh PRD §7: 50 × 10.666,67 = 533.333,50 vs 533.333). Usulan: simpan `total_value` sebagai angka otoritatif (DECIMAL(18,2)); `average_cost = round(total_value/qty, 2)`; HPP baris = `qty_out × average_cost` dibulatkan per baris. Selisih sisa kecil dicatat di `inv_ledger`. Perlu persetujuan BA (D-08).
- **Kuantitas:** `tbstok.masuk/keluar` bertipe `int`. Konversi satuan (PRD §4.2) butuh pecahan. Usulan: `qty` di `inv_*` bertipe `DECIMAL(14,3)`; konversi satuan ditunda ke Fase 4.

---

## 6. Roadmap dan Task Lengkap

Aturan umum untuk semua task (dari `CLAUDE.md` global/project):

- Investigate-first; laporkan temuan sebelum implementasi.
- Semua migrasi reversible, diuji di DB lokal, runner di `db/migrations/`.
- Tiap fase: tes (termasuk E2E Playwright/browser), commit kecil, update `.ai/changelog`, `.ai/decisions`, update `CLAUDE.md` project (Status Ringkas), dan update checklist-projek (`scripts/update-checklist.sh`).
- Reviewer sebelum merge; **Opus sebagai final judge**.
- Tidak ada hardcoded kredensial. Produksi (shared hosting) dijalankan Rafi lewat runner.
- Ukuran: **S** ≤ 0,5 hari · **M** 1–2 hari · **L** 3–5 hari.

### Fase 0 — Fondasi Data (prasyarat, bisa dimulai tanpa semua keputusan)

Tujuan: satu kunci lokasi pasti, satu sumber stok, saldo awal siap.

| ID | Task | Detail / berkas | Selesai bila | Dep | Ukuran |
|---|---|---|---|---|---|
| F0-01 | Audit kunci lokasi di semua tabel | Daftar semua kolom `kd_cabang`/`kode_cabang` di DB (`information_schema`), distribusi nilainya (lokal **dan produksi**). Hasil: tabel pemetaan alias→kanonik | Dokumen audit + daftar alias lengkap | — | M |
| F0-02 | Putuskan kunci kanonik | Usulan: `tbcabang.kode_cabang`. Konfirmasi Owner untuk `001` dan string kosong | Keputusan D-01 tercatat di `.ai/decisions/` | F0-01 | S |
| F0-03 | Migrasi `inv_location` + normalisasi `tbstok.kd_cabang` | Tabel alias; **jangan** ubah data lama sebelum backup; UPDATE terukur per alias, reversible (`bak_*`) | `SELECT DISTINCT kd_cabang FROM tbstok` hanya berisi kode kanonik | F0-02 | M |
| F0-04 | Audit saldo dan penyebab negatif | Per cabang: berapa pasangan negatif, penyebab (servis migrasi tanpa saldo awal). Cocokkan dengan `tblitem.quantity` | Laporan P2/P3 untuk **produksi** | F0-01 | M |
| F0-05 | Putuskan sumber stok dan strategi saldo awal | Opsi: (a) saldo awal dari **stok opname fisik** per lokasi (rekomendasi), (b) dari `tblitem.quantity`. Putuskan nilai awal biaya (§9, D-02) | Keputusan D-02/D-03 | F0-04 | S |
| F0-06 | Siapkan template opening stock | CSV/halaman unggah: `kd_cabang, no_item, qty_fisik, unit_cost, sumber_biaya`; validasi (item ada, qty ≥ 0, biaya ≥ 0) | Template + validator lulus data uji | F0-05 | M |
| F0-07 | Daftar jalur tulis stok | Tabel final semua file penulis `tbstok`/`tblitem.quantity` dengan tipe, kapan dipanggil, status; tandai kode mati (`save_*.php` lama) | `docs/` daftar lengkap, disetujui | — | M |
| F0-08 | Audit potong stok ganda servis | Verifikasi 6 titik servis (P12) tidak memotong dua kali untuk satu `no_service` | Laporan bukti dengan data uji | F0-07 | M |
| F0-09 | Query baseline produksi | Jalankan query §8 di produksi (Rafi), simpan hasilnya sebagai baseline | Angka baseline tercatat | — | S |

### Fase 1 — Inti: Service, Average, Shadow

| ID | Task | Detail / berkas | Selesai bila | Dep | Ukuran |
|---|---|---|---|---|---|
| F1-01 | Migrasi tabel inti `inv_*` | `inv_balance`, `inv_ledger`, `inv_avg_history`, `inv_config` (+ `inv_audit_log`). Runner reversible | Up/down jalan; indeks dan UNIQUE terbukti | F0-03 | M |
| F1-02 | Desain kontrak `InventoryService` | Tanda tangan fungsi, kode `trx_type`, aturan error, bentuk hasil, flag mode (`off/shadow/live`) | Spesifikasi tertulis + review | F1-01 | M |
| F1-03 | Implementasi `postIn` | Rumus §6.1 PRD, stok nol → average = harga, validasi harga/qty, `FOR UPDATE`, transaksi, idempotency | Uji unit AC-01, Test 1–2, 13, 17–18, 20 | F1-02 | L |
| F1-04 | Implementasi `postOut` | HPP = qty × average saat posting; average tidak berubah; kembalikan HPP | Uji AC-02, Test 3, 14 | F1-03 | M |
| F1-05 | Guard stok minus | Tolak keluar jika qty > saldo; pesan menampilkan stok tersedia; **tanpa pengecualian role** (Test 19) | AC-03 lulus; uji paralel | F1-04 | M |
| F1-06 | Proyeksi ke `tbstok` | Service menulis baris `tbstok` bersamaan di transaksi yang sama, dengan kode `tipe` lama agar laporan lama tetap benar | Kartu stok lama = kartu stok baru pada data uji | F1-04 | M |
| F1-07 | Pembulatan | Terapkan keputusan D-08, fungsi `round_half_up` tunggal | Uji Test 15 | F1-03 | S |
| F1-08 | Feature flag per cabang | `inv_config.inv_mode` per cabang + halaman admin kecil | Mode bisa diubah tanpa deploy | F1-02 | S |
| F1-09 | Mode shadow + rekonsiliasi harian | Jalur lama tetap utama; service dipanggil paralel; skrip bandingkan qty `inv_balance` vs `view_stok_master` | Laporan selisih harian berjalan | F1-06, F1-08 | L |
| F1-10 | Uji konkurensi | Dua proses menjual item sama; hasil benar, tidak ada average salah (Test 43–45) | Skrip uji tersedia dan hijau | F1-05 | M |

### Fase 2 — Menghubungkan Titik Transaksi

Setiap task: ganti `INSERT INTO tbstok` langsung dengan panggilan service, isi `harga_pokok` snapshot, bungkus transaksi DB, cek hasil query. Dikerjakan di mode shadow dulu.

| ID | Task | Berkas utama | Catatan | Dep | Ukuran |
|---|---|---|---|---|---|
| F2-01 | Pembelian (Gudang Pusat dan beli darurat cabang) | `pembelian_add.php`, `pembelian_add_rst.php`, `pembelian_cab_add_proses.php`, `do_receive.php`, `do_from_po.php` | BR-01, BR-16; harga net setelah potongan | F1-09 | L |
| F2-02 | Penjualan reguler | `penjualan_add.php`, `penjualan_add_rst.php`, `proses-add-detail/penjualan.php` | Isi `tblpenjualan_detail.harga_pokok` (menutup T1); validasi ulang + lock saat simpan (P6) | F1-09 | L |
| F2-03 | Penjualan antar cabang / mitra | `penjualan_cab_add_proses.php`, `penjualan_cab_add_proses_mitra.php` | Sisi penjual: HPP; sisi penerima ikut aturan transfer | F2-02 | M |
| F2-04 | Servis: barang dan HPP | `servis-reguler-byr.php`, `servis-input-reguler*.php`, `proses-add-detail/penjualan-brg-servis.php`, `servis-garansi.php` | **Tambah kolom `harga_pokok` di `tblservis_barang`** (T2). Titik potong ikut D-04 | F0-08, F1-09 | L |
| F2-05 | Komisi dan laba membaca HPP snapshot | `_include_komisi_snapshot.php`, `issue_add.php`, `lap_komisi_mekanik.php`, `lap_profit_penjualan.php`, `lap_profit_insentif.php` | Berhenti memakai `tblitem.hargapokok` | F2-02, F2-04 | M |
| F2-06 | Retur pembelian | `save_retur_pembelian.php`, `retur_pembelian_batal.php` | Average tidak berubah (BR-02); **batal** = transaksi pembalik, bukan DELETE (P8) | F1-09 | M |
| F2-07 | Retur penjualan dan retur servis | `save_retur_penjualan.php`, `retur_penjualan_batal.php`, `save_retur_servis.php`, `retur_servis_approve.php`, `retur_servis_batal.php` | BR-07: harga masuk = HPP transaksi asal | F2-02, F2-04 | M |
| F2-08 | Transfer antar lokasi + Barang dalam Perjalanan | `pengadaan_antarcab_proses.php`, `pengadaan_antarcab_push.php`, `pengadaan_antarcab_terima.php`, `pengadaan_antarcab_add.php` | Tambah `inv_transit`; kirim = HPP asal; terima = masuk average tujuan; gagal kirim = kembali ke asal (BR-13–15). Rapikan status `dikirim/terkirim` | F1-09 | L |
| F2-09 | Cancel vs Void | `servis-delete.php`, alur batal penjualan | Pemetaan status "sebelum/ sesudah barang keluar" (D-05) | F2-02, F2-04 | M |
| F2-10 | Stok masuk/keluar manual, penyesuaian | `stok_masuk_add*.php`, `stok_keluar_add*.php`, `so-item-masuk*.php`, `so-item-keluar*.php`, `save_barang.php` | Masuk (+) seperti pembelian; keluar (−) average tetap; alasan wajib | F1-09 | M |
| F2-11 | Matikan penulis lama dan kode mati | `save_pembelian.php`, `save_penjualan.php`, dst (daftar F0-07) | Arsipkan ke `archive/`; kode mati tidak boleh bisa menulis stok | F2-01..10 | S |
| F2-12 | Hentikan backdate | Semua form di atas | `tanggal` = tanggal input server; `transaction_date` hanya keterangan (BR-06, BR-12) | F2-01..10 | M |
| F2-13 | Sync Access berhenti menimpa stok | `_include_access_sync.php:1058-1110` | Tidak menulis `tbstok`/`tblitem.quantity/hargapokok` untuk cabang `live` | F1-08 | M |
| F2-14 | Uji E2E per alur | Playwright/browser: tiap transaksi di atas; bukti DB (`inv_ledger`, `inv_balance`) | Semua alur lulus; screenshot | per task | L |

### Fase 3 — Koreksi Maju, Kontrol, Periode

| ID | Task | Detail | Selesai bila | Dep | Ukuran |
|---|---|---|---|---|---|
| F3-01 | Stock Adjustment (+/−) dengan referensi | Alasan wajib, referensi transaksi asal, tanggal = hari ini (BR-21) | Test 21, 23 | F2-10 | M |
| F3-02 | Koreksi Harga Pembelian (barang masih ada) | Selisih × qty tersisa masuk rumus average (BR-22) | Test 24 | F1-03 | M |
| F3-03 | Koreksi Harga, sebagian/seluruhnya terjual | Pisahkan porsi sisa (average) dan terjual (`inv_ppv_log`, BR-23); HPP lama tidak berubah | AC-05, Test 25–26 | F3-02 | L |
| F3-04 | Koreksi tanggal dan lokasi | Tanggal salah dicatat di keterangan (BR-24); lokasi salah lewat transfer (BR-25) | Test D | F2-08 | S |
| F3-05 | Maker-checker | `inv_correction_request`; alur petugas → Kepala Cabang/Kepala Mekanik → Finance HQ; audit log Who-What-When-Why-Before-After; reuse pola `tblpo_approval_log` | AC-10, Test 28 | F3-01 | L |
| F3-06 | Periode Open/Closed/Locked | `inv_period`; larang edit langsung periode Closed (koreksi tetap boleh hari ini, EC-10); Locked butuh otorisasi khusus | Test 27 | F1-01 | M |
| F3-07 | Larang edit dan hapus transaksi posted | Semua endpoint edit/hapus (P8) menolak dan mengarahkan ke koreksi | AC-04, Test 22 | F3-05 | M |
| F3-08 | RBAC role Finance HQ | Permission baru di `menu_config.php`/RBAC (V-11) | Test 42 | — | S |
| F3-09 | Notifikasi Finance | Setiap entri Selisih Harga Pembelian (V-13) | Notif terkirim pada uji | F3-03 | S |

### Fase 4 — Fitur Lanjutan

| ID | Task | Detail | Dep | Ukuran |
|---|---|---|---|---|
| F4-01 | Bonus supplier proporsional | Harga efektif = total faktur ÷ total qty; validasi total alokasi = total faktur (BR-17, AC-09, Test 11) | F2-01 | M |
| F4-02 | Replacement supplier | Retur + pembelian baru terpisah (BR-18, Test 12) | F2-01, F2-06 | M |
| F4-03 | Sparepart Claim dan Piutang Klaim Supplier | `inv_claim_receivable`, status pending/settled/rejected (BR-19, Test 36–38) | F2-06 | L |
| F4-04 | Stock Opname | Selisih (+)/(−), ambang approval dari `inv_config`, default Rp0 (V-12, AC-12, Test 39–41). Saat ini tidak ada modul opname | F3-05 | L |
| F4-05 | Barang hilang/rusak/scrap, pemakaian internal | Transaksi keluar sebagai beban, bukan HPP; berita acara/approval | F3-05 | M |
| F4-06 | Revaluasi Persediaan | Hanya Finance HQ, approval + alasan tertulis (V-11, Test 42) | F3-05, F3-08 | M |
| F4-07 | Debit/Credit Note | Pengaruh ke average bila barang masih ada | F3-02 | M |
| F4-08 | Konversi satuan | Average per satuan baru = lama ÷ rasio (Test 9). Butuh master rasio satuan (belum ada) | F1-03 | M |
| F4-09 | Alert aging transit | Alert barang transit > X hari (default 3, V-14) | F2-08 | S |

### Fase 5 — Laporan, Akuntansi, Cutover

| ID | Task | Detail | Dep | Ukuran |
|---|---|---|---|---|
| F5-01 | Laporan inti | Kartu stok per lokasi (transaction vs correction date), Inventory Valuation, Average Cost History | F1-09 | L |
| F5-02 | Laporan baru | Barang dalam Perjalanan (+aging), Selisih Harga Pembelian, Piutang Klaim Supplier | F2-08, F3-03, F4-03 | M |
| F5-03 | HPP/Laba Rugi dan neraca persediaan | Total persediaan = Σ balance + saldo transit | F5-01 | M |
| F5-04 | Jurnal akuntansi real-time | Akun Inventory, Barang dalam Perjalanan, Selisih Harga Pembelian, Piutang Klaim Supplier; debit = kredit (Test 46–48). **Butuh COA (D-07)** | D-07 | L |
| F5-05 | Rekonsiliasi harian otomatis | `Σ inv_balance` = jurnal; `inv_balance` = `view_stok_master` | F5-04 | M |
| F5-06 | Cutover cabang percontohan | Opname fisik, muat opening stock (F0-06), `inv_mode=live`, aturan BR-05 aktif; 1–2 minggu pengamatan | Semua Fase 2 | L |
| F5-07 | Cutover cabang lain | Cabang demi cabang; Gudang Pusat (PST) dengan perhatian khusus | F5-06 | L |
| F5-08 | Dokumentasi dan pelatihan | SOP koreksi (pengganti edit/hapus), panduan maker-checker, panduan opname | F3 | M |
| F5-09 | Perbarui aturan project | Ganti Rule "HPP FIFO" menjadi "HPP Moving Average per lokasi" di `CLAUDE.md`; catat `.ai/decisions`, `.ai/changelog` | Persetujuan PRD | S |
| F5-10 | Pasca-cutover | Pantau selisih rekonsiliasi 30 hari; tutup shadow; arsip jalur lama | F5-07 | M |

### Pemetaan test PRD → task

| Test PRD | Task yang menguji |
|---|---|
| 1–4, 13–16 | F1-03, F1-04, F1-07 |
| 5, 8 | F2-07, F2-04 |
| 6, 31–35 | F2-08, F5-02 |
| 7, 21, 23 | F3-01, F2-10 |
| 10 | F2-01 |
| 11–12 | F4-01, F4-02 |
| 9 | F4-08 |
| 17–20, 22 | F1-03, F1-05, F3-07 |
| 24–30 | F3-02, F3-03, F3-06, F3-05, F2-09 |
| 36–38 | F4-03 |
| 39–42 | F4-04, F4-06, F3-08 |
| 43–45 | F1-10 |
| 46–48 | F5-04, F5-05 |

---

## 7. Risiko dan Mitigasi

| # | Risiko | Dampak | Mitigasi |
|---|---|---|---|
| R1 | BR-05 aktif di atas data negatif (P2) | Semua penjualan ditolak | BR-05 hanya aktif per cabang **setelah** opening stock (F5-06); sebelumnya shadow |
| R2 | Mengubah ±30 file penulis stok sekaligus | Regresi di alur kasir/servis yang vital | Adapter satu per satu, shadow dulu, flag per cabang, E2E tiap alur |
| R3 | Kunci lokasi dinormalisasi salah | Saldo/riwayat satu cabang tertukar | Tabel alias, backup `bak_*`, migrasi reversible, uji pada salinan |
| R4 | `tbstok` dan `inv_*` tidak sinkron | Laporan lama dan baru berbeda | Proyeksi di transaksi yang sama + rekonsiliasi harian (F1-09, F5-05) |
| R5 | Kasir/staf menolak larangan stok minus dan larangan edit | Resistensi operasional | Pelatihan, SOP koreksi (F5-08), pilot satu cabang |
| R6 | Aturan "tanpa edit/hapus" bertabrakan dengan kebiasaan (mis. input salah) | Antrian koreksi menumpuk | Maker-checker ringan untuk nilai kecil, target waktu approval |
| R7 | Sync Access menimpa saldo cabang `live` | Saldo rusak | F2-13 sebelum cutover pertama |
| R8 | COA persediaan belum ada | Jurnal tertunda | Pisahkan Fase 5 jurnal; R1–R2 tetap bernilai tanpa jurnal |
| R9 | Pembulatan menimbulkan selisih nilai | Rekonsiliasi tidak pas ke rupiah | Keputusan D-08 sebelum Fase 1 |
| R10 | Produksi berbeda dari lokal (lokal hanya snapshot dev) | Estimasi dan desain meleset | F0-09 dan F0-01/04 di produksi sebelum Fase 1 |
| R11 | Beban `FOR UPDATE` pada `inv_balance` saat jam sibuk | Antrean kunci | Volume ±200 transaksi/hari rendah (PRD); lock singkat; indeks UNIQUE |
| R12 | Satu developer (Rafi) | Jadwal panjang | Fase R1 dulu; jangan paralel (aturan sequential per sesi) |

---

## 8. Query Verifikasi Produksi (read-only, jalankan Rafi sebelum Fase 1)

```sql
-- P1: distribusi kunci lokasi
SELECT kd_cabang, COUNT(*) n FROM tbstok GROUP BY kd_cabang;
SELECT kd_cabang, COUNT(*) n FROM tblservice GROUP BY kd_cabang;
SELECT DISTINCT kode_cabang FROM tbuser;
SELECT kode_cabang, nama_cabang FROM tbcabang;

-- P2: saldo negatif
SELECT COUNT(*) pasangan, SUM(s>0) positif, SUM(s=0) nol, SUM(s<0) negatif, MIN(s) min_s
FROM (SELECT no_item, kd_cabang, SUM(masuk-keluar) s FROM tbstok GROUP BY no_item, kd_cabang) x;

-- P3: ledger vs snapshot master
SELECT COUNT(*) item_beda FROM tblitem i
JOIN (SELECT no_item, SUM(masuk-keluar) s FROM tbstok GROUP BY no_item) t ON t.no_item=i.noitem
WHERE i.quantity<>t.s;

-- Tipe gerakan per cabang
SELECT tipe, kd_cabang, COUNT(*) n FROM tbstok GROUP BY tipe, kd_cabang ORDER BY tipe;

-- HPP penjualan nol
SELECT COUNT(*) total, SUM(harga_pokok=0) hpp_nol FROM tblpenjualan_detail WHERE no_transaksi<>'';

-- Status antar cabang
SELECT status, COUNT(*) n FROM tblorder_antarcab_header GROUP BY status;

-- Mesin tabel (harus InnoDB)
SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() AND ENGINE<>'InnoDB' AND TABLE_TYPE='BASE TABLE';
```

Hasil lokal (referensi, dev snapshot): 320.321 baris `tbstok` (2020-05-15 s/d 2026-09-29); 6.255 pasangan item–cabang; 6.179 negatif; 3.986 item `tblitem.quantity` ≠ ledger; semua tabel inti InnoDB; `tblorder_antarcab_header.status` = `dikirim`, `terkirim`, `selesai`.

---

## 9. Keputusan yang Dibutuhkan (sebelum task terkait dimulai)

| ID | Keputusan | Diperlukan untuk | Rekomendasi |
|---|---|---|---|
| D-01 | Kunci lokasi kanonik, dan arti `001` dan string kosong | F0-02 | `tbcabang.kode_cabang`; `001` dan kosong dipetakan setelah dicek ke data |
| D-02 | **Sumber biaya awal (opening cost)** per item per lokasi. PRD: "data pembelian riil terakhir per item per lokasi". Untuk item tanpa histori beli (184 item di lokal ber-HPP 0)? | F0-05, F5-06 | Beli terakhir → `gabung_hpp_acuan` → `tblitem.hargapokok` → input manual |
| D-03 | **Saldo awal dari stok fisik (opname)** atau dari `tblitem.quantity`? | F0-05 | Opname fisik per lokasi (data ledger tidak bisa dipercaya, P2) |
| D-04 | **Kapan stok servis turun?** Saat item masuk Work Order, atau saat bayar (kondisi sekarang)? PRD §4.4 tidak menyebut waktunya, tapi BR-05 mengharuskan stok dicek saat transaksi | F2-04 | Cek dan reservasi saat item ditambah; potong final saat bayar; atau potong saat ditambah (lebih ketat, mengubah alur) |
| D-05 | Definisi "barang sudah keluar fisik" (Cancel vs Void) untuk penjualan dan servis | F2-09 | Penjualan: Cancel hanya sebelum cetak/serah; servis: sebelum status bayar |
| D-06 | Peran apa yang boleh menyetujui (Kepala Cabang, Kepala Mekanik, Finance HQ) dan nilai ambang | F3-05, F4-04 | Ikuti PRD; ambang default Rp0 |
| D-07 | **COA persediaan dan jurnal**: apakah Fit Motor memakai jurnal akuntansi di web base, dan akun apa? | F5-04 | Putuskan setelah R1–R2 berjalan; Fase 5 jurnal bisa menyusul |
| D-08 | Aturan pembulatan nilai persediaan (§5.5) | F1-07 | `total_value` otoritatif, average dibulatkan 2 desimal |
| D-09 | Nilai awal alert aging transit (X hari) | F4-09 | 3 hari |
| D-10 | Nasib data ledger historis (320 rb baris) | F0-03, F5-06 | Pertahankan sebagai arsip; ledger bernilai dimulai dari opening stock |
| D-11 | Apakah Gudang Pusat (`PST`) dan Cabang punya aturan "pembelian cabang ke supplier lokal" dengan persetujuan | F2-01 | Ikuti BR-16 apa adanya |
| D-12 | Cutover: cabang percontohan dan jadwal | F5-06 | `PST` dulu (volume ledger kecil), lalu cabang kecil, terakhir cabang besar |

### Hal di PRD yang perlu klarifikasi BA

1. **§4.4 "Penjualan Sparepart via WO — Turun"**: waktu pemotongan tidak disebut (lihat D-04).
2. **BR-23 / Contoh 6**: rumus koreksi memakai "qty yang masih ada"; perlu aturan bila `qty_on_hand` sekarang lebih kecil dari sisa lot pembelian asal karena ada transaksi keluar dari pembelian lain (tanpa lapisan FIFO, "sisa dari pembelian tertentu" tidak terdefinisi). Usulan: anggap sisa = `min(qty_on_hand, qty_pembelian_asal − qty_terjual_sejak)`; BA harus menyetujui.
3. **V-09**: pembulatan average 2 desimal vs nilai persediaan (D-08).
4. **Test 19**: "tolak tanpa pengecualian, termasuk Superadmin". Perlu dipastikan alat admin yang ada (backfill, perbaikan data) tidak melanggar aturan ini; beberapa skrip admin menulis `tbstok` langsung.
5. **BR-12**: "Transaction Date" bisa berbeda dari tanggal input hanya untuk koreksi; jelaskan bagaimana petugas mengisinya.
6. **§4.2 Konversi satuan**: master rasio satuan belum ada di web base (kolom `satuan` hanya `varchar(3)`).

---

## 10. Definition of Done per Fase

- [ ] Task fase selesai dan diuji (unit/integrasi/E2E), bukti DB tercatat
- [ ] Migrasi reversible (`up` dan `down` diuji) dan runner siap untuk produksi
- [ ] Tidak ada hardcoded kredensial/path absolut
- [ ] Review cepat (Sonnet) lalu final review (Opus) sebelum merge
- [ ] `.ai/changelog`, `.ai/decisions`, `CLAUDE.md` project (Status Ringkas) diperbarui
- [ ] checklist-projek diperbarui (`scripts/update-checklist.sh`)
- [ ] Rekonsiliasi shadow tanpa selisih tak terjelaskan selama periode uji
- [ ] Commit dan push ke remote

---

## 11. Langkah Berikutnya yang Disarankan (belum dieksekusi)

1. Rafi meninjau dokumen ini dan menjawab keputusan prioritas: **D-01, D-02, D-03, D-04, D-08**.
2. Rafi menjalankan query §8 di produksi (F0-09) dan menyerahkan hasilnya.
3. Setelah itu mulai **F0-01 → F0-09** (hanya audit dan migrasi pelan, tanpa mengubah perilaku aplikasi).
4. Fase 1 dimulai setelah Fase 0 dan keputusan di atas selesai.

---

## Lampiran A — Daftar Jalur Penulis Stok (untuk F0-07)

| Kelompok | Berkas |
|---|---|
| Pembelian | `pembelian_add.php`, `pembelian_add_rst.php`, `pembelian_cab_add_proses.php`, `do_receive.php`, `do_from_po.php`, `save_pembelian.php` (lama) |
| Penjualan | `penjualan_add.php`, `penjualan_add_rst.php`, `penjualan_cab_add_proses.php`, `penjualan_cab_add_proses_mitra.php`, `save_penjualan.php` (lama) |
| Servis | `servis-reguler-byr.php`, `servis-input-reguler.php`, `servis-input-reguler-rst.php`, `servis-input-reguler-jemput.php`, `servis-input-reguler-jemput-rst.php`, `servis-garansi.php`, `admin-backfill-tbstok-servis.php` |
| Retur | `save_retur_pembelian.php`, `retur_pembelian_batal.php`, `save_retur_penjualan.php`, `retur_penjualan_batal.php`, `retur_servis_approve.php`, `retur_servis_batal.php`, `save_retur_servis.php` |
| Antar cabang | `pengadaan_antarcab_proses.php`, `pengadaan_antarcab_push.php`, `pengadaan_antarcab_terima.php` |
| Penyesuaian manual | `stok_masuk_add*.php`, `stok_keluar_add*.php`, `so-item-masuk*.php`, `so-item-keluar*.php`, `save_barang.php` |
| Sinkron Access | `_include_access_sync.php` (upsert saldo ke `tbstok`, DELETE+INSERT per item) |
| Pembaca (jangan rusak) | `view_stok_master`, `kartu_stok.php`, `lib/MinMaxCalculator.php`, `pr_auto_draft.php`, `_ajax/ajax-search-barang.php`, `_ajax/ajax-validate-antarcab-upload.php`, `*_item_cari.php` |

**Catatan:** daftar ini dari `git grep`; sebagian berkas belum dibaca baris per baris (khususnya `stok_*` dan `so-item-*`). F0-07 memverifikasinya.

## Lampiran B — Tipe Gerakan `tbstok` Saat Ini (untuk F1-06)

| `tipe` | Dipakai oleh | Catatan |
|---|---|---|
| `1` | `save_barang.php` ("Stok Awal"), kd_cabang kosong | 24 baris lokal |
| `2` | Pembelian, DO, **terima antar cabang**, `save_penjualan.php` (lama, kemungkinan salah) | Ambigu (P10) |
| `3` | Penjualan, **kirim antar cabang** | Ambigu (P10) |
| `4` | Servis | 320 rb baris |
| `5`, `6` | Belum diverifikasi (kemungkinan stok masuk/keluar manual) | 77 dan 3 baris lokal |
| `7` | Retur pembelian | |
| `8` | Retur penjualan | |
| `9` | Retur servis | |
