# Implementation Plan — Web Bengkel FIT MOTOR
**Sumber:** Hasil Diskusi 28 Juni 2026 + Panduan Meeting v3.0  
**Dibuat:** 29 Juni 2026  
**Diverifikasi ulang:** 2026-07-04 (lihat "Verifikasi Status 2026-07-04" di bawah)  
**Dieksekusi:** 2026-07-04 — F1-D, F1-E (perbaikan bug), F1-C (verifikasi), F2-C, F2-A, F1-A (baru) — lihat "Eksekusi 2026-07-04" di bagian akhir dokumen  
**Dieksekusi:** 2026-07-05 — F2-B (Retur Servis Lunas) dibangun penuh — lihat bagian "F2-B: Q1 — Retur / VOID Servis Lunas" di bawah  
**Status:** Aktif — F1-D/F1-E/F1-C/F2-C/F2-A/F1-A/F2-B sudah live & terverifikasi. Klarifikasi #1/#2/#3/#4/#7 (A3) sudah terjawab (2026-07-04), B1 terjawab (2026-07-05). F1-B **siap dikerjakan** (tidak ada blocker lagi). Q1 (SOP retur formal) masih skip — SOP belum dibuat.

---

## Ringkasan Eksekutif

Dari 10 pertanyaan teknis yang dibahas, semua sudah terjawab oleh tim operasional.  
Analisis codebase menunjukkan **5 fitur sudah ada sebagian**, **7 fitur perlu dibangun dari awal**.  
Ada **5 item yang masih perlu klarifikasi** sebelum implementasi bisa dimulai.

---

## Verifikasi Status 2026-07-04

Cek ulang langsung ke `tools/sql/fitmotor_dbbengkel_FIXED_V7.sql` (dump schema terbaru) untuk pastikan rencana di bawah masih akurat sebelum dikerjakan.

**Konfirmasi: belum ada satupun kolom/tabel Fase 1/2 yang dibuat.**
- `tblservice` — tidak ada `is_garansi`, `tanggal_garansi_expire`, `ref_no_service_original`, `mekanik_original`, `komisi_garansi_mode`, `bayar_tunai`, `bayar_transfer`, `bayar_qris`.
- `status_servis` masih enum `datang,diproses,selesai,bayar,cancel` — **belum ada status `VOID`**.
- Tabel `tb_dp_servis`, `tb_approval_diskon`, `tb_master_garansi` **belum ada**.
- Jadi seluruh Fase 1 & 2 di bawah **masih valid dan belum mulai dikerjakan** — bukan dokumen usang, hanya belum dieksekusi.

**Temuan baru yang mengubah desain F2-B (Retur/VOID):**
Tabel `tb_log_cancel_servis` **sudah ada dan sudah punya field yang relevan**, tapi 0 baris data (belum pernah dipakai):
```
status_barang     enum('belum_diambil','sudah_diambil','dikembalikan')
status_pembayaran enum('belum_bayar','dp_dibayar','lunas','refund')
nominal_dp        double
nominal_refund    double
kategori_alasan   enum('customer_request','no_stock','no_mekanik','customer_no_show','lainnya')
```
Ini nyaris sama persis dengan rancangan `tb_approval_diskon`/retur yang diusulkan di F2-B — **jangan bikin tabel retur baru dari nol**, extend `tb_log_cancel_servis` (tambah kolom `ref_nota_pengganti` kalau perlu) supaya tidak ada dua tabel yang tumpang tindih untuk kasus yang sama.

**Blocker baru untuk F1-B (Komisi Mekanik Garansi) — konflik formula komisi:**
Ditemukan **2 tabel formula komisi yang tidak sinkron** (lihat `docs/GAP_ANALYSIS_JAWABAN_TEMUAN.md` blok B1):
| Sumber | Mekanik Jasa | Mekanik Barang | Admin Jasa | Admin Barang |
|---|---|---|---|---|
| `tbbagi_hasil` (sync Access lama) | 30% | 15% | 6% | 10% |
| `tbpersen_insentif` (setting web) | 35% | 15% | 10% | 10% |
| `FSD_SERVIS.md` (dari `DATA_INSENTIF_SERVIS` di FITMOTOR APP.mdb) | 20% | 5% | 5% | 5% |

**Tiga sumber, tiga angka berbeda.** F1-B ("komisi diambil dari % mekanik original") tidak bisa dihitung dengan benar sampai owner memastikan mana yang jadi acuan resmi — ini item klarifikasi baru, ditambahkan ke Bagian 4 di bawah.

**Blocker baru untuk F1-A (Garansi Auto-Check) — konflik masa garansi:**
Selain "7 hari standar / 14 hari maks" dari jawaban v3 Pak Novian & Marshell, kode existing punya 2 sumber lain yang berbeda:
- WA template (`class_whatsapp_automation.php`): "bergaransi 30 hari atau 1000 KM (mana lebih dulu)".
- `master_kategori_member`: Bronze 0 hari, Silver 7 hari, Gold 11 hari, Platinum 14 hari (bertingkat per level member, bukan angka tunggal).

v3 sudah menjawab **7/14 hari sebagai SOP resmi** — tapi WA template dan `master_kategori_member` yang sudah berjalan di kode belum diselaraskan ke angka itu. F1-A perlu eksplisit memutuskan: pakai 7/14 hari flat (sesuai v3), atau tetap bertingkat per level member (sesuai kode existing) — dan WA template 30 hari harus diperbaiki/dihapus supaya tidak menyesatkan customer.

**Cross-reference:** `docs/fsd/FSD_SERVIS.md` (dibuat 2026-07-04) sudah mengusulkan tabel `servis_komisi` untuk snapshot komisi permanen — desain F1-B/F2-C harus dipadukan dengan tabel itu, bukan bikin mekanisme snapshot komisi terpisah.

---

## Status per Fitur: Sudah Ada vs Perlu Dibangun

| Q | Topik | Status Codebase |
|---|-------|----------------|
| Q2 | Stok dipotong saat input WO | ✅ Sudah ada (INSERT tblservis_barang) |
| Q3 | Stok kembali saat item WO dihapus | ✅ Sudah ada (servis_hapus_item) — perlu verifikasi |
| Q4 | Diskon Member + log diskon_source | ✅ Ada (field diskon_source, SOP member) |
| Q5 | Komisi mekanik real-time (SELECT) | ✅ Sudah ada (tidak disimpan saat closing) |
| Q10 | tipe_item ORI/NON_ORI di master barang | ✅ Ada (ENUM di kolom statusitem) |
| Q1 | Retur/VOID servis lunas | ✅ **Selesai 2026-07-05** (F2-B: `tblretur_servis_header`/`_detail` + approval + refund tracking) |
| Q4b | Diskon Supervisor approval (luar SOP) | ✅ **Selesai 2026-07-04** (F2-C: `tb_approval_diskon` + `approval-diskon.php`) |
| Q6 | Komisi mekanik garansi (sama vs beda) | ❌ Belum ada — F1-B blocked total (B1 formula komisi belum dijawab) |
| Q7 | Garansi auto-check (7 hari, WARNING) | ❌ Belum ada — F1-A blocked total (A3 masa garansi resmi belum dijawab) |
| Q8 | Multi-payment (tunai + transfer + QRIS) | ✅ **Selesai** (F1-C, terverifikasi live 3 flow reguler/jemput/garansi) |
| Q9 | Mekanisme DP / Down Payment | ✅ **Selesai 2026-07-04** (F2-A: `tb_dp_servis` + toggle + catat/batal DP + laporan) |
| Q10b | Part customer: jasa pasang + keterangan terstruktur | ❌ Sebagian (keterangan ada tapi bebas teks) |

---

## FASE 1 — Prioritas Tinggi (Segera Implementasi)
*Keputusan sudah final, tidak ada klarifikasi lagi yang dibutuhkan*

---

### F1-A: Q7 — Garansi Auto-Check + Warning + Supervisor Override
**Kompleksitas:** Sedang | **Estimasi:** 3–4 hari dev

**Keputusan bisnis (final):**
- Garansi berlaku jika customer melakukan paket servis + beli part di bengkel
- Masa garansi STANDAR: 7 hari. Maks: 14 hari (Supervisor)
- Sistem WAJIB WARNING jika lewat masa garansi
- Sistem TETAP bisa terima garansi dengan persetujuan Supervisor

**Yang perlu dibangun:**

**1. Migrasi database:**
```sql
ALTER TABLE tblservice 
  ADD COLUMN tanggal_garansi_expire DATE NULL AFTER tanggal,
  ADD COLUMN is_garansi TINYINT(1) DEFAULT 0,
  ADD COLUMN ref_no_service_original VARCHAR(30) NULL;

CREATE TABLE tb_master_garansi (
  id INT PRIMARY KEY AUTO_INCREMENT,
  kategori_member VARCHAR(20),      -- 'BRONZE','SILVER','GOLD','PLATINUM','ALL'
  jenis_servis VARCHAR(30),         -- 'reguler','garansi','jemput','all'
  masa_garansi_hari INT DEFAULT 7,
  masa_garansi_maks INT DEFAULT 14,
  keterangan TEXT,
  aktif TINYINT(1) DEFAULT 1
);
```

**2. Logic di servis-carinopol-garansi.php:**
- Saat CS memilih service untuk dibuat garansi, hitung `hari_berlalu = DATEDIFF(NOW(), tanggal_servis_asal)`
- Jika `<= 7 hari` → hijau, langsung lanjut
- Jika `8–14 hari` → kuning, WARNING, butuh alasan + Supervisor approval
- Jika `> 14 hari` → merah, BLOCK default, override manual

**3. Logic di servis-garansi.php saat create:**
- Set `tanggal_garansi_expire = tanggal_servis_asal + 7 hari`
- Set `is_garansi = 1`, `ref_no_service_original = no_service_asal`

**4. UI:** Banner warning + badge warna di servis-carinopol-garansi.php

---

### F1-B: Q6 — Komisi Mekanik Garansi (Sama vs Berbeda)
**Kompleksitas:** Sedang | **Estimasi:** 2–3 hari dev
**⚠️ Perlu klarifikasi item #1 (Bagian 4) sebelum 100% selesai**

**Keputusan bisnis (final):**
- Mekanik SAMA mengerjakan garansi → **TIDAK dapat komisi**
- Mekanik BERBEDA → **dapat komisi dari % mekanik original**
- Koreksi Bu Meikha: transaksi sebelumnya mengurangi komisi, transaksi rework baru menambah komisi

**Yang perlu dibangun:**

**1. Field tambahan:**
```sql
ALTER TABLE tblservice
  ADD COLUMN mekanik_original VARCHAR(50) NULL,
  ADD COLUMN komisi_garansi_mode ENUM('skip','transfer') DEFAULT 'skip';
```

**2. Logic di servis-garansi.php saat WO diisi:**
```
IF is_garansi = 1:
  ambil mekanik_original dari ref_no_service_original
  IF mekanik_garansi_ini == mekanik_original:
    -> komisi = 0, tampilkan info "Garansi mekanik sama — komisi tidak dihitung"
  ELSE:
    -> komisi = % dari tarif WO servis asal
    -> tampilkan info "Komisi diambil dari porsi mekanik [nama original]"
```

**3. Di laporan komisi:**
- Tambah kolom `jenis` = Reguler / Garansi-Skip / Garansi-Transfer
- Mekanik bisa lihat garansi yang jadi tanggung jawabnya

---

### F1-C: Q8 — Multi-Payment (Tunai + Transfer + QRIS)
**Kompleksitas:** Rendah-Sedang | **Estimasi:** 2 hari dev
**⚠️ Perlu klarifikasi item #2 (Bagian 4) untuk logika LUNAS**

**Keputusan bisnis (final):**
- Customer BISA bayar lebih dari 1 metode dalam 1 transaksi
- Upload bukti pembayaran: opsional, tidak wajib realtime
- Kolom terpisah: cash vs transfer

**Yang perlu dibangun:**

**1. Migrasi database:**
```sql
ALTER TABLE tblservice
  ADD COLUMN bayar_tunai DECIMAL(15,2) DEFAULT 0,
  ADD COLUMN bayar_transfer DECIMAL(15,2) DEFAULT 0,
  ADD COLUMN bayar_qris DECIMAL(15,2) DEFAULT 0,
  ADD COLUMN ref_transfer VARCHAR(100) NULL,
  ADD COLUMN bukti_transfer VARCHAR(255) NULL;
```

**2. UI di panel-kanan-kasir.php:**
- Ganti dropdown "Metode Pembayaran" menjadi 3 baris input:
  - `Tunai: [  Rp _____  ]`
  - `Transfer: [  Rp _____  ]` + field No. Referensi
  - `QRIS: [  Rp _____  ]`
- Live: Total terbayar = tunai + transfer + QRIS
- Live: Sisa = Total tagihan - terbayar
- Tombol upload bukti (opsional)

---

### F1-D: Q3 — Verifikasi & Fix Stok Kembali Saat Item WO Dihapus
**Kompleksitas:** Rendah | **Estimasi:** 1 hari (audit + fix)

**Keputusan bisnis (final):** Stok kembali otomatis saat item dihapus dari WO, selama belum closing/lunas

**Yang perlu diverifikasi di kode:**
- `servis_hapus_item_garansi.php` — apakah ada UPDATE stok saat hapus?
- `servis-input-reguler.php` DELETE handler — apakah UPDATE tbstok dilakukan?
- Cek semua 3 form: reguler, jemput, garansi

**Guard yang wajib ada:** Hapus item setelah closing → sistem BLOCK dengan pesan error

---

### F1-E: Q10 — Part Customer dari Counter: Input Terstruktur
**Kompleksitas:** Rendah | **Estimasi:** 1 hari dev

**Keputusan bisnis (final):**
- CS hanya input jasa pemasangan (bukan part ke stok)
- Part dicatat di keterangan dengan info: jenis, merek, status ORI/Imitasi

**Yang perlu dibangun di tab Jasa Service:**
- Tambah checkbox: `☐ Part milik customer (bukan dari stok bengkel)`
- Jika dicentang, muncul sub-form: `Nama Part | Merek | ORI/Imitasi`
- Disimpan di kolom `keterangan` di `tblservis_jasa`:  
  Format: `[PART-CUST: Kampas Rem | Bendix | ORI]`
- Di cetak struk: tampil sebagai keterangan terpisah, bukan item stok

---

## FASE 2 — Prioritas Sedang
*Ada detail yang perlu klarifikasi atau SOP dari operasional dulu*

---

### F2-A: Q9 — Mekanisme DP / Down Payment
**Kompleksitas:** Sedang | **Estimasi:** 3–4 hari dev  
**Tunggu:** Klarifikasi item #3 (format laporan DP)

**Keputusan bisnis (final):**
- DP hanya untuk: servis besar mesin ATAU part inden
- DP minimal 50% dari total
- Alur: DP masuk kasir sebagai pemasukan → part ready → DP di-offset sebagai pengeluaran → customer bayar sisa
- Jika batal: DP dikembalikan penuh

**Rancangan database:**
```sql
CREATE TABLE tb_dp_servis (
  id INT PRIMARY KEY AUTO_INCREMENT,
  no_service VARCHAR(30),
  no_dp VARCHAR(30) UNIQUE,
  tanggal_dp DATE,
  jumlah_dp DECIMAL(15,2),
  status ENUM('pending','offset','batal') DEFAULT 'pending',
  tanggal_offset DATE NULL,
  keterangan TEXT,
  id_user VARCHAR(30),
  kd_cabang VARCHAR(10)
);
```

**Alur UI:**
1. Di form servis → tombol "Catat DP" → modal input nominal
2. Generate nota DP + cetak struk DP
3. Saat servis selesai: "Gunakan DP [no_dp]" → sisa tagihan = total - DP
4. Laporan kasir: DP masuk dan offset tampil terpisah (menunggu klarifikasi format)

---

### F2-B: Q1 — Retur / VOID Servis Lunas — ✅ SELESAI 2026-07-05
**Kompleksitas:** Tinggi | **Status:** Live & terverifikasi

**Temuan yang mengubah rencana asli (investigasi 2026-07-05):**
- `tb_log_cancel_servis` **ternyata sudah dipakai** (bukan 0 baris seperti dugaan 2026-07-04) — dipakai `servis-cancel-proses.php` untuk flow CANCEL **pra-bayar**, dan `updateCancelStatistikPelanggan()` COUNT(*) semua barisnya tanpa pembeda jenis untuk statistik "customer sering cancel". Extend tabel ini untuk retur **pasca-bayar** akan mencemari statistik itu. Keputusan final: **tabel baru terpisah**, bukan extend `tb_log_cancel_servis`.
- Servis potong stok saat **PEMBAYARAN** (tbstok tipe='4'), beda dari Penjualan yang potong saat SAVE nota — jadi stok retur servis juga dikembalikan di titik **APPROVE**, bukan di titik SAVE retur.
- Tidak ada status `VOID` yang dibuat — nota servis asli tetap seperti semula (tidak diubah), retur dicatat penuh di tabel terpisah, `tblservice.total_retur` cuma dipakai buat netting laporan.

**Yang dibangun:**
1. Tabel baru `tblretur_servis_header` (noretur, no_service, cara_bayar_refund, status_retur, status_refund, tanggal_refund) + `tblretur_servis_detail` (tipe_item barang/jasa, no_item, qty, alasan) — pola identik `tblretur_penjualan_header/_detail`.
2. `tblservice.total_retur` (baru) + `view_service` diperluas expose `total_akhir`/`total_retur`/`status_servis`.
3. `qty_retur` ditambah ke `tblservis_jasa` (sebelumnya cuma ada di `tblservis_barang`).
4. Flow: `retur_servis.php` (list) → `retur_servis_add.php` (pilih item barang+jasa, alasan, cara bayar refund) → `save_retur_servis.php` (simpan pending) → `retur_servis_approve.php` (stok balik `tbstok` tipe='9' + refund selesai) / `retur_servis_batal.php` (batal kalau masih pending) → `retur_servis_detail.php`.
5. `lap_servis.php`: kolom Retur + Total Akhir (Net), filter `status_servis IN ('bayar','selesai')` (kedua state ini = lunas, tergantung flow reguler vs jemput/garansi) — sebelumnya laporan narik semua status termasuk cancel/belum bayar.
6. Entry point tombol "Retur Servis" di dropdown Aksi `servis-reguler.php` (muncul kalau status Bayar) + menu sidebar "Retur Servis".

**Verifikasi:** end-to-end lewat browser asli (bukan simulasi DB doang) — skenario 200rb lunas, retur 50rb, approve, net di laporan 150rb, stok nambah, refund tercatat.

---

### F2-C: Q4b — Diskon Supervisor Approval + Monitoring Report
**Kompleksitas:** Sedang | **Estimasi:** 3 hari dev

**Keputusan bisnis (final):**
- Kasir bisa langsung diskon sesuai SOP (member/mitra/karyawan) — sudah ada
- Diskon di luar SOP → perlu approval Supervisor
- Laporan monitoring diskon manual untuk audit manajemen

**Database:**
```sql
CREATE TABLE tb_approval_diskon (
  id INT PRIMARY KEY AUTO_INCREMENT,
  no_service VARCHAR(30),
  jenis ENUM('jasa','barang'),
  nominal_diskon DECIMAL(15,2),
  persen_diskon DECIMAL(5,2),
  alasan TEXT,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  id_user_cs VARCHAR(30),
  id_user_supervisor VARCHAR(30) NULL,
  tanggal_request DATETIME,
  tanggal_approval DATETIME NULL
);
```

**UI:** Badge notifikasi Supervisor → approve/reject → log tampil di laporan audit

---

## FASE 3 — Roadmap Jangka Panjang

| Fitur | Sumber | Catatan |
|-------|--------|---------|
| Konversi penjualan counter → servis | Q10 (Mba Indry) | Belum urgent, roadmap |
| Upload bukti transfer realtime | Q8 (Mba Dian) | Ada, tapi opsional dulu |
| Garansi otomatis end-to-end | Q7 (tim IT) | Perlu uji coba kompleksitas dulu |
| Daftar pengecualian garansi (master) | Q7 (Marshell) | Perlu data dari operasional |
| ~~Fitur retur dengan approval bertingkat~~ | Q1 | ✅ Selesai 2026-07-05 (F2-B) — approval single-level (Setujui/Batal), bukan bertingkat |

---

## Bagian 4 — Item yang Masih Perlu Klarifikasi

**JANGAN implementasi bagian ini sebelum ada jawaban:**

| # | Q | Pertanyaan yang perlu dijawab | Ke siapa | Status |
|---|---|-------------------------------|----------|--------|
| 1 | Q6 | Komisi mekanik garansi oleh mekanik lain: **dikurangi dari komisi mekanik original, atau diambil dari pool bengkel?** | Mas Amil | ✅ Terjawab 2026-07-04: dikurangi dari komisi mekanik original |
| 2 | Q8 | Status LUNAS multi-payment mix: **cukup tunai diterima, atau harus tunggu konfirmasi transfer masuk?** | Kasir/Keuangan | ✅ Terjawab 2026-07-04: cukup tunai diterima, tidak tunggu konfirmasi transfer |
| 3 | Q9 | Format laporan DP: **DP masuk dan keluar tampil 2 baris terpisah, atau saling offset (net = 0)?** | Mba Dian | ✅ Terjawab 2026-07-04: tampil terpisah. Penanda "servis mesin besar" di input servis buka opsi DP; servis tetap on-proses (belum lunas) sampai DP di-offset |
| 4 | Q7 | Daftar kondisi yang TIDAK ditanggung garansi (pengecualian) — **perlu list dari operasional** | Marshell | ✅ Terjawab 2026-07-04 — lihat daftar di bawah |
| 5 | Q1 | SOP Retur formal: **siapa yang buat dan kapan target selesai?** | Mba Indry / Pak Novian | ⏸️ Skip sementara 2026-07-04 — SOP belum dibuat, F2-B tetap ditunda |
| 6 | B1 (baru, 2026-07-04) | Formula komisi resmi: **`tbbagi_hasil` (30%/15% jasa/barang), `tbpersen_insentif` (35%/15%), atau formula FITMOTOR APP.mdb (20%/5%) — mana yang berlaku?** Blocking F1-B. | Pak Novian / Mas Amil | ✅ **Terjawab 2026-07-05:** acuan resmi = formula FITMOTOR APP.mdb (20% mekanik jasa / 5% mekanik barang / 5% admin jasa / 5% admin barang), tapi dibuat **master editable** (bukan hardcode) — pola sama seperti `tbmaster_kategori_member` di F1-A. F1-B siap dikerjakan. |
| 7 | A3 (baru, 2026-07-04) | Masa garansi resmi: **7/14 hari flat (sesuai v3) atau bertingkat per level member (0/7/11/14 hari, sesuai `master_kategori_member` yang sudah jalan)?** WA template 30 hari juga perlu diperbaiki. Blocking F1-A. | Pak Novian | ✅ **Terjawab 2026-07-04:** bertingkat per tier member, dibuat dinamis & editable di `tbmaster_kategori_member` (bukan hardcode). WA template diperbaiki. F1-A: masa garansi sudah jalan — komisi mekanik garansi (F1-B) masih blocked B1 |

**Daftar Pengecualian Garansi (jawaban Q7, 2026-07-04, dari operasional) — siap dipakai saat F1-A jalan (masih blocked A3):**
1. Tidak melakukan paket servis (catatan: beli barang + pasang di bengkel tetap bisa garansi)
2. Membawa part dari luar bengkel (bengkel hanya memasangkan saja)
3. Ada history catatan komplain terkait keluhan yang sama (kalau customer komplain tapi sudah ada catatan sebelumnya, dianggap bukan garansi)
4. Komplain di luar dari garapan terakhir

**Update A3 (2026-07-04):** masa garansi resmi dinamis per tier member, bukan flat — lihat "Eksekusi 2026-07-04" untuk detail implementasi. F1-A (auto-check masa garansi) sudah jalan.

**Update B1 (2026-07-05):** formula komisi resmi = angka FITMOTOR APP.mdb (20%/5%/5%/5%), dibuat master editable (tabel baru, bukan hardcode `tbbagi_hasil`/`tbpersen_insentif`). Komisi mekanik garansi dikurangi dari komisi mekanik original (mekanik yang menggarap servis sebelumnya) — sesuai jawaban Q6 2026-07-04. F1-B **siap dikerjakan**, tidak ada blocker lagi.

---

## Urutan Implementasi yang Direkomendasikan

```
Minggu 1 (mulai segera, tidak ada blocker):
  F1-D  Audit & fix stok kembali hapus WO
  F1-E  Part customer — checkbox + keterangan terstruktur

Minggu 2:
  F1-C  Multi-payment UI (tunai/transfer/QRIS)

Sebelum Minggu 3-4 (blocker baru, harus dijawab dulu):
  Klarifikasi #6  Formula komisi resmi (tbbagi_hasil vs tbpersen_insentif vs FITMOTOR APP.mdb)
  Klarifikasi #7  Sumber masa garansi resmi (flat 7/14 hari vs bertingkat per member)

Minggu 3–4 (setelah #6 dan #7 dijawab):
  F1-A  Garansi auto-check + WARNING + Supervisor override
  F1-B  Komisi mekanik garansi (sama vs berbeda)

Sudah selesai 2026-07-04 (tidak ada blocker):
  F2-C  Diskon Supervisor approval — SELESAI, lihat "Eksekusi 2026-07-04"

Setelah klarifikasi diterima:
  F2-A  DP mechanism (tunggu format laporan)
  F2-B  Retur/VOID — extend tb_log_cancel_servis (jangan tabel baru), tunggu SOP dari operasional

Jangka panjang:
  Fase 3 — konversi penjualan, garansi otomatis end-to-end
```

---

## Database Migrations Summary

File SQL yang perlu disiapkan:

| File | Isi | Fase |
|------|-----|------|
| `migration_garansi_fields.sql` | Kolom `is_garansi`, `tanggal_garansi_expire`, `ref_no_service_original` di tblservice | F1-A |
| `create_tb_master_garansi.sql` | Konfigurasi masa garansi per kategori | F1-A |
| `migration_multi_payment.sql` | Kolom `bayar_tunai`, `bayar_transfer`, `bayar_qris`, `ref_transfer`, `bukti_transfer` | F1-C |
| `migration_mekanik_garansi.sql` | Kolom `mekanik_original`, `komisi_garansi_mode` di tblservice | F1-B |
| `create_tb_dp_servis.sql` | Tabel DP/uang muka | F2-A |
| `create_tb_approval_diskon.sql` | Tabel approval diskon luar SOP | F2-C |
| `migration_void_status.sql` | Status `VOID` + keterangan_void di tblservice | F2-B |

---

## Eksekusi 2026-07-04

Item tanpa blocker (F1-D, F1-E, F1-C, F2-C) dieksekusi sampai tuntas dan diverifikasi langsung terhadap DB live (bukan cuma lint kode).

### F1-D — Audit stok kembali saat hapus item
**Hasil:** guard status (`bayar`/`selesai` → blok hapus) sudah ada & benar di keempat handler (`servis-input-reguler.php` inline, `servis_hapus_item_jemput.php`, `servis_hapus_item_garansi.php`). Tidak perlu logic "restore stok" terpisah — arsitekturnya stok baru dipotong ke `tbstok` saat **bayar** (bukan saat item diinput), jadi hapus sebelum bayar otomatis aman karena stok belum pernah dipotong. Ini konsisten dengan jawaban Q2/Q3 v3.

**Bug ditemukan & diperbaiki:** loop insert `tbstok` saat bayar (reguler, jemput) tidak exclude item `PART-CUST` (F1-E) — bikin phantom stock movement untuk item yang harusnya tidak masuk stok bengkel. Sudah difix di 3 file (`servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php`).

**Gap baru ditemukan & diperbaiki:** `servis-garansi.php` (servis garansi/comeback) **sama sekali tidak insert ke `tbstok`** saat bayar — sparepart yang dipakai untuk rework garansi tidak pernah tercatat keluar dari stok fisik. Ditambahkan logic yang sama seperti reguler/jemput (exclude PART-CUST juga).

### F1-E — Part milik customer dari counter
**Ditemukan sudah 100% terimplementasi** sejak commit `bad8174` (30 Jun 2026) — checkbox + sub-form di tab Suku Cadang, handler `btnadd_partcust` di ketiga flow (reguler/jemput/garansi), simpan format `[PART-CUST: nama | merek | kondisi]`. **Tidak perlu dibangun ulang.**

**Bug kritis ditemukan saat verifikasi:** migrasi asli `app/sql/migration_fase1_2026.sql` (30 Jun) **gagal sebagian saat dijalankan** — 2 statement terakhir tidak pernah ter-apply ke DB live:
- `tblservis_barang.keterangan` (kolom penyimpan `[PART-CUST: ...]`) — **tidak ada**, submit form part customer akan SQL error "Unknown column".
- Item placeholder `PART-CUST` di `tblitem` — INSERT aslinya pakai nama kolom salah (`kode_item`/`nama_item`/`harga_jual`/`aktif` — tblitem sebenarnya pakai `noitem`/`namaitem`/`hargajual`, tanpa kolom `aktif`), jadi silently gagal.

Diperbaiki via `db/migrations/2026-07-04_fix_fase1_migration_gaps.sql`, sudah dijalankan & diverifikasi live: kolom `keterangan` ada, item `PART-CUST` ada di `tblitem`.

### F1-C — Multi-payment
**Ditemukan sudah 100% terimplementasi** (migrasi + backend 3 flow + UI kasir 3 baris input tunai/transfer/QRIS dengan live total). Diverifikasi kolom `bayar_tunai`/`bayar_transfer`/`bayar_qris`/`ref_transfer`/`bukti_transfer` semua ada di DB live. **Tidak ada pekerjaan tersisa untuk item ini.**

### F2-C — Diskon Supervisor Approval (baru dibangun)
Dibangun dari nol karena belum ada sama sekali:
- Migrasi `db/migrations/2026-07-04_f2c_diskon_approval.sql`: tabel `tb_approval_diskon` + permission `diskon_approval_approve` di-grant ke role Administrator (1) dan Manager (7) — role Manager dipakai sebagai proxy "Supervisor/Kepala Cabang" karena tidak ada role terpisah bernama itu di `tb_master_posisi`.
- Fungsi `checkDiskonApproval()` di `app/helper-functions.php`, dipanggil dari ketiga handler bayar (reguler/jemput/garansi) — hanya trigger untuk diskon **manual level-invoice** (`txtpotfaktur_persen`/`txtpotfaktur_nom`), bukan diskon item-level via `diskon_source` (member/promo) yang sudah sesuai SOP dan tidak butuh approval.
- Halaman baru `app/approval-diskon.php` (menu "Approval Diskon", gate permission `diskon_approval_approve`) — list pending + approve/reject + riwayat 50 terakhir.
- Diuji dengan 4 skenario (tanpa diskon, diskon baru, diskon masih pending/no-duplicate, diskon sudah approved) — semua PASS terhadap DB live (data uji dibersihkan setelahnya).

**Catatan:** threshold nominal diskon yang butuh approval belum dibedakan (semua diskon manual > 0% kena approval) — kalau owner mau ada batas nominal minimal sebelum wajib approval, itu penyesuaian kecil di `checkDiskonApproval()`.

### F2-A — DP/Down Payment (dibangun setelah klarifikasi Q9 terjawab, 2026-07-04)
Dibangun dari nol karena belum ada sama sekali. Verifikasi: kolom `boleh_dp` di `tblservice` dan tabel `tb_dp_servis` sudah live di DB (dicek via PHP `mysqli_multi_query`, bukan `mysql` CLI — CLI `mysql` Windows via WSL gagal silent tanpa error, jangan dipakai lagi untuk migrasi live sesi berikutnya).

- Migrasi `db/migrations/2026-07-04_f2a_dp_servis.sql`: `tblservice.boleh_dp` (penanda "servis mesin besar / part inden") + tabel `tb_dp_servis` (status pending/offset/batal).
- Helper baru di `helper-functions.php`: `generateNoDP()`, `getDpPendingTotal()`, `offsetDpPending()`.
- UI di `_template/panel-kanan-kasir.php` (shared reguler/jemput/garansi): checkbox toggle "Servis Mesin Besar / Part Inden", tombol "Catat DP" (validasi minimal 50% dari total berjalan), baris "Sudah DP" mengurangi Total Bayar tampilan, tombol "Batalkan DP" (dikembalikan penuh).
- 3 endpoint AJAX baru: `_ajax/ajax-toggle-boleh-dp.php`, `_ajax/ajax-catat-dp.php`, `_ajax/ajax-batal-dp.php`.
- Payment handler ketiga flow (`servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php`) dikurangi DP pending sebelum validasi jumlah bayar; setelah pelunasan sukses, DP pending otomatis ditandai `offset`.
- Halaman baru `laporan-dp.php` (menu "Laporan DP Servis"): DP masuk dan DP keluar (offset/batal) tampil sebagai baris terpisah sesuai jawaban Mba Dian, dengan filter tanggal + total masuk/keluar.

**Catatan:** halaman intake pembuatan service baru (`save-no-servis-reguler.php`) tidak punya form field sama sekali (langsung insert `status_servis='datang'` dari nomor polisi) — jadi penanda "servis mesin besar" ditaruh sebagai toggle di halaman edit service (`panel-kanan-kasir.php`), bukan di form pembuatan awal. Kalau operasional mau ini muncul saat intake pertama kali, perlu penyesuaian form intake terpisah (belum diminta).

### F1-A — Garansi Auto-Check, masa garansi dinamis (dibangun setelah klarifikasi A3 terjawab, 2026-07-04)
Jawaban A3: masa garansi TIDAK flat 7/14 hari, bertingkat per tier member, editable via master (bukan tabel `tb_master_garansi` terpisah seperti rencana awal).

**Temuan bug saat eksekusi:** kode `servis-garansi.php` sudah sejak sebelumnya menulis kolom `is_garansi`/`ref_no_service_original`/`tanggal_garansi_expire`/`mekanik_original`/`komisi_garansi_mode` ke `tblservice` (alur "buat garansi dari service asal" via `ref_service`), tapi kolom-kolom ini **tidak pernah dimigrasikan** — bug tersembunyi yang baru ketahuan saat implementasi A3 ini (sama pola dengan bug F1-E migrasi Fase 1 sebelumnya). Dicek live: kolom-kolom itu **sudah ada** di DB (kemungkinan sempat dimigrasikan manual di luar tracking dokumen ini) — hanya kolom masa garansi dinamis yang belum ada.

- Migrasi `db/migrations/2026-07-04_f1a_garansi_dinamis.sql`: `tbmaster_kategori_member.masa_garansi_hari` (batas standar/hijau) + `masa_garansi_maks_hari` (batas maks/kuning sebelum expired) — editable per tier lewat master. Nilai awal: Bronze 0/7, Silver 7/14, Gold 11/18, Platinum 14/21 hari.
- Helper baru `getMasaGaransiHari($koneksi, $no_pelanggan)` di `_include_kategori_member.php` — return `['standar'=>.., 'maks'=>..]` dari tier member pelanggan (default 7/14 kalau data kosong).
- `servis-garansi.php`: `tanggal_garansi_expire` dihitung dari `getMasaGaransiHari()` milik pelanggan pemilik service asal, bukan hardcode `+7 days`.
- `servis-carinopol-garansi.php`: query utama di-JOIN `statistik_pelanggan` + `tbmaster_kategori_member` (hindari N+1), badge hijau/kuning/merah dan pesan konfirmasi "Buat Garansi"/"Kadaluarsa" pakai batas dinamis per baris, bukan angka 7/14 hardcode.
- `class_whatsapp_automation.php`: pesan WA "bergaransi 30 hari atau 1000 KM" (menyesatkan customer) diganti jadi dinamis sesuai tier member pelanggan.

**Catatan:** F1-B (komisi mekanik garansi sama vs beda mekanik) **masih blocked total** — kolom `mekanik_original`/`komisi_garansi_mode` sudah ada di DB tapi logic penghitungan komisi belum bisa jalan sampai B1 (formula komisi resmi: `tbbagi_hasil` vs `tbpersen_insentif` vs FITMOTOR APP.mdb) dijawab.

---

## FASE 4 — Pengadaan & Inventory (Jawaban Owner 16 Jul 2026)

Sumber: `docs/fsd/FSD_PENGADAAN_INVENTORY.md` §7.1 & §11.1, jawaban final Owner (Pak Novian, 16 Juli 2026) untuk 2 dari 5 poin keputusan bisnis yang blocking.

**Urutan kerja disepakati (17 Jul 2026):**
1. Verifikasi 3 SQL injection alur PO (obs 2475/2476/2478) — **prasyarat wajib** sebelum fitur baru.
2. Implementasi Approval Bertingkat PO (`tb_master_approval_pembelian`) — desain final §7.1.
3. Implementasi Alarm Harga Beli (`tb_master_threshold_harga` + `alarm_harga_beli`) — desain final §11.1.
4. Jalankan sesi planning Promo Engine (pivot dari Program Cuci Motor Gratis) → hasilkan `FSD_PROMO.md` (planning only, belum coding — lihat `docs/PROMPT_CLAUDE_CODE_PLANNING_PROMO_ENGINE.md`).
5. Poin Skema Tier Membership & Angka Batas Naik Level masih **blocked** — belum dijawab Owner.

### Eksekusi 2026-07-17 (in progress)

**Langkah 1 — Verifikasi SQL injection alur PO:** dicek ulang terhadap kode live (commit `ac58a62`), semua 3 titik **masih vulnerable** (belum pernah difix meski sudah tercatat di FSD sejak sebelumnya):
- `app/save_pesanan_pembelian_h.php` (PO header save) — INSERT `tblorder_header` string-concat dari `$_POST`. **FIXED** hari ini → `mysqli_prepare`/`bind_param`.
- `app/save_pesanan_pembelian.php` (PO save handler) — SELECT + UPDATE `tblorder_header`/`tblorder_detail` string-concat dari `$_POST['no_order']`/`txttotal`, plus reflected XSS di redirect. **FIXED** hari ini → prepared statement + `rawurlencode()` di echo redirect.
- `app/pesanan_pembelian_add_print.php` (PO print page) — 3 titik `SELECT ... WHERE no_order='$nopesanan'`/`nosupplier='$no_supplier'` dari `$_GET['nopesanan']` (baris ~37-58, ~283-292). **FIXED 2026-07-17** → semua diubah ke `mysqli_prepare`/`mysqli_stmt_bind_param`/`mysqli_stmt_get_result`, lint `php -l` PASS.

**Langkah 1 SELESAI — 3/3 SQL injection PO fixed** (`save_pesanan_pembelian_h.php`, `save_pesanan_pembelian.php`, `pesanan_pembelian_add_print.php`).

**Langkah 1b — Audit `_pengadaan/` (folder duplikat cabang) SELESAI 2026-07-17:** ternyata `_pengadaan/` punya salinan persis sama (bug-for-bug identik) dari 3 file yang baru difix di `app/`. Semua **FIXED** dengan pola sama, lint `php -l` PASS:
- `_pengadaan/save_pesanan_pembelian_h.php` — INSERT `tblorder_header` → prepared statement + `rawurlencode()` di redirect.
- `_pengadaan/save_pesanan_pembelian.php` — SELECT+UPDATE `tblorder_header`/`tblorder_detail` → prepared statement + `rawurlencode()`.
- `_pengadaan/pesanan_pembelian_add_print.php` — 4 titik SELECT (header, supplier, total, loop item) dari `$_GET['nopesanan']` → semua prepared statement.

**Total sesi ini: 6 file SQL injection PO fixed (3 di `app/`, 3 duplikat di `_pengadaan/`).** File lain di `_pengadaan/` (cari_item_*, lap_*, pesanan_pembelian_add.php dkk) **belum diaudit** — kemungkinan masih ada raw query lain di luar scope 3 file yang jadi fokus temuan awal (obs 2475/2476/2478); kalau mau tuntas 100% perlu audit menyeluruh folder ini terpisah.

**Catatan user 2026-07-17:** audit `_pengadaan/` di luar 3 file yang sudah difix TIDAK diperlukan — user eksplisit minta fokus ke folder `app/` saja untuk sisa pekerjaan.

### Task 2 — Approval Bertingkat PO — SELESAI 2026-07-17

Desain final §7.1 FSD_PENGADAAN_INVENTORY.md: bracket nominal PO (`tblorder_header.total_order`) menentukan posisi minimal yang boleh approve. Ditemukan sistem approval single-level SUDAH ada (`tblorder_header.status_approval`, `tblpo_approval_log` dengan `level_approval` di-hardcode `1`) — diupgrade jadi bertingkat, bukan dibangun dari nol.

**Yang dibangun:**
1. Migrasi `db/migrations/2026-07-17_f4_approval_bertingkat_po.sql`: tabel baru `tb_master_approval_pembelian` (level_approval, nama_level, batas_bawah, batas_atas NULL=unlimited, kode_posisi, urutan, aktif). Dijalankan live via PHP CLI (bukan mysql CLI, sesuai catatan lama). Seed 2 baris sesuai contoh Owner: Level 1 "Manager (300rb-1jt)" kode_posisi=MNG, Level 2 "Administrator (>1jt)" kode_posisi=ADM.
   - **Catatan penting:** tidak ada posisi "Supervisor" di `tb_master_posisi` — dipetakan ke Manager (tier 1) & Administrator (tier 2) sebagai pendekatan terdekat yang sudah ada. Fully editable lewat master page, owner bisa ganti kapan saja kalau mau posisi lain.
2. `app/po_approval_action.php` — rewrite penuh ke prepared statement (sekalian nutup raw-query di file ini) + logic baru: cari bracket sesuai `total_order` PO, cek `user_akses_level` posisi user (dari `tbuser.kode_posisi` → `tb_master_posisi.user_akses_level`, angka lebih kecil = akses lebih tinggi) terhadap posisi minimal bracket tsb. Approve/reject ditolak (redirect dengan pesan error) kalau posisi user tidak cukup. PO di bawah threshold terendah (< Rp300rb) tidak butuh approval bertingkat — lanjut seperti alur lama.
3. Halaman master baru `app/master-approval-pembelian.php` — CRUD (tambah/edit/hapus) bracket approval, dropdown pilih posisi dari `tb_master_posisi` aktif, tabel daftar bracket dengan urutan/batas/posisi/status aktif.
4. Menu baru "Master Approval Bertingkat" ditambahkan ke `app/menu_config.php` (submenu Pesanan Pembelian (PO)), reuse permission `pesanan_pembelian_read` (tidak bikin permission baru).

**Verifikasi:** lint `php -l` PASS semua 3 file (migration dijalankan bukan di-lint, itu SQL). Query bracket-matching ditest via script CLI disposable dengan 4 nominal (100rb/500rb/1jt/5jt) — hasil sesuai ekspektasi (100rb = no tier, 500rb & 1jt = Manager, 5jt = Administrator). **Belum ditest end-to-end via browser** (WSL2 tidak bisa akses Laragon Apache) — user perlu test manual approve/reject PO di berbagai nominal untuk verifikasi UI/flow penuh.

### Task 3 — Alarm Harga Beli — SELESAI 2026-07-17

Desain final §11.1/§11.2 FSD_PENGADAAN_INVENTORY.md: threshold naik/turun dibedakan, 1 halaman setting, alarm otomatis tiap ada perubahan harga beli signifikan.

**Keputusan desain penting:** ditemukan 4 titik insert `tblpembelian_detail` berbeda (`pembelian_add.php`, `pembelian_add_next_rst.php`, `pembelian_add_rst.php`, `pembelian_cab_add_proses.php`). Daripada duplikat logic alarm di 4 file PHP (rawan kelewat satu — persis pola bug migrasi F1-E/F1-A yang pernah kejadian di project ini), dipakai **MySQL TRIGGER** `trg_alarm_harga_beli` (AFTER INSERT ON `tblpembelian_detail`) — menjamin semua titik insert tertangkap otomatis di level DB, tidak bisa lupa taruh di salah satu file.

**Yang dibangun:**
1. Migrasi `db/migrations/2026-07-17_f4_alarm_harga_beli.sql` (2 tabel) + `db/migrations/2026-07-17_run_alarm_harga_beli.php` (runner terpisah karena body trigger berisi banyak `;` yang kepotong kalau dijalankan via `mysqli_multi_query`, dijalankan sebagai single query). Dijalankan live via PHP CLI, sukses:
   - `tb_master_threshold_harga` (arah naik/turun, persen_threshold, aktif, updated_by) — seed naik=5%, turun=10% sesuai rekomendasi FSD.
   - `alarm_harga_beli` (no_item, no_transaksi_pembelian, harga_beli_lama/baru, persen_selisih, arah, threshold_saat_itu snapshot, harga_jual_saat_ini snapshot, status_klasifikasi, status_review).
   - Trigger: bandingkan `harga_pokok` baru vs baris `tblpembelian_detail` terakhir untuk `no_item` sama, hitung persen selisih, lookup threshold sesuai arah, insert alarm kalau `ABS(persen_selisih) >= threshold`. Snapshot `threshold_saat_itu` & `harga_jual_saat_ini` (dari `tblitem.hargajual`) supaya tidak retroaktif kalau threshold diubah nanti.
   - **Catatan Open Item #3 (FSD section 13):** hanya 2 dari 7 label `status_klasifikasi` legacy Access yang terkonfirmasi ("Harga Beli Naik & Harga Jual perlu Naik", "Harga Pokok Turun") — dipakai apa adanya di trigger. 5 label lain masih perlu re-ekstraksi query Access asli sebelum bisa diklasifikasi lebih detail — untuk sekarang semua alarm naik/turun pakai 2 label ini saja.
2. Halaman baru `app/setting-threshold-harga.php` — 1 halaman, 2 field (Threshold Naik %, Threshold Turun %), validasi wajib > 0, catatan "tidak retroaktif" ditampilkan ke user.
3. Halaman baru `app/alarm-harga-beli.php` — dashboard alarm dengan filter "Belum Direview"/"Semua", kolom naik/turun berdampingan (badge merah=naik, kuning=turun), aksi per baris "Sudah Disesuaikan" (`harga_disesuaikan`) / "Abaikan" (`diabaikan`) — sesuai FSD: tidak ada auto-update harga jual, keputusan tetap manual staf.
4. Menu baru "Alarm Harga Beli" ditambahkan ke `app/menu_config.php` (submenu Pembelian), reuse permission `pembelian_read`.

**Verifikasi:** lint `php -l` PASS ketiga file PHP. Trigger ditest langsung di DB live via script CLI disposable dengan 3 skenario (naik 10% dari 16000→17600 = alarm arah naik threshold 5% terpenuhi; turun 15% dari 17600→14960 = alarm arah turun threshold 10% terpenuhi; naik ~1.6% dari 14960→15200 = TIDAK alarm, di bawah threshold) — semua hasil sesuai ekspektasi. Data test dihapus (`no_transaksi='TEST_ALARM_DELETE_ME'`) setelah verifikasi, tidak ada sisa data kotor di DB live. **Belum ditest end-to-end via browser** (WSL2 tidak bisa akses Laragon Apache) — user perlu test manual input Pembelian riil dengan harga beda signifikan untuk verifikasi UI dashboard.

### Testing & Debugging Menyeluruh — 2026-07-17

User minta semua eksekusi (fix SQL injection, Task 2, Task 3) ditest ulang & didebug sebelum lanjut Task 4. Karena WSL2 tidak bisa akses port Windows (curl ke `php -S` built-in server gagal, konsisten catatan lama), testing dilakukan via **subprocess PHP CLI nyata** yang meng-include file produksi langsung dengan `$_SESSION`/`$_POST` di-mock (bukan sekadar re-implementasi query terpisah) — pola: buat session file asli via `session_id()`+`session_start()`, lalu subprocess terpisah `chdir` ke `app/` dan `include` file aslinya, exit di dalam file tidak masalah karena tiap test 1 proses sendiri.

**2 bug ditemukan & diperbaiki:**

1. **Bug otorisasi approval bertingkat (Task 2) — `app/po_approval_action.php`:** logic asli membandingkan `tb_master_posisi.user_akses_level` secara numerik (`user_akses_level > required_level → tolak`), dengan asumsi angka lebih kecil = akses lebih tinggi. Ternyata field ini BUKAN hierarki otoritas asli — cuma ID kategori posisi (CS=2, MNG=7, tapi CS py permission jauh lebih sedikit dari MNG). Akibatnya staff CS (level 2) akan LOLOS approve PO tier Manager (butuh level 7), karena 2 tidak lebih besar dari 7. **Diperbaiki**: logic diganti jadi exact-match `kode_posisi` user vs `kode_posisi` bracket, dengan override universal untuk Administrator (`kode_posisi==='ADM'`, "Full system access"). Ditest ulang lewat 5 skenario nyata (CS ditolak di tier Manager ✓, Manager lolos di tier Manager ✓, Manager ditolak di tier Administrator ✓, Administrator lolos di semua tier ✓, PO di bawah threshold terendah lolos siapa saja ✓) — semua PASS setelah fix.
2. **Pesan error approval hilang — `app/pesanan_pembelian_detail.php`:** `po_approval_action.php` redirect dengan `&err=...` saat approval ditolak, tapi halaman detail tidak pernah membaca `$_GET['err']` — user tidak tahu kenapa approve-nya gagal (silent). **Diperbaiki**: tambah alert box baca `$_GET['err']` di atas panel Approval PO.

**Hasil testing lengkap (semua PASS setelah 2 fix di atas):**
- **Task 2 approval bertingkat**: submit→pending, approve dgn posisi cukup, approve dgn posisi kurang (ditolak), reject + alasan tersimpan, PO rejected tidak bisa langsung di-approve ulang (harus submit dulu), submit ulang reset rejected→pending — semua lewat eksekusi nyata file produksi, bukan simulasi query.
- **Task 2 master CRUD** (`master-approval-pembelian.php`): tambah/edit/hapus bracket ditest via subprocess, semua berfungsi benar.
- **Task 3 alarm harga beli**: trigger DB ditest 3 skenario (naik 10%→alarm, turun 15%→alarm, naik 1.6%→tidak alarm) — semua sesuai; `setting-threshold-harga.php` save + validasi (nilai ≤0 ditolak) ditest via subprocess — PASS; `alarm-harga-beli.php` aksi review (`harga_disesuaikan`) ditest — PASS.
- **Fix SQL injection PO (3 file `app/`)**: ditest end-to-end fungsional (bukan cuma lint) — alur nyata `save_pesanan_pembelian.php` (hitung total_qty benar) → `pesanan_pembelian_add_print.php` (render data PO benar dari prepared statement). Semua data test dibuat & dihapus bersih, tidak ada sisa di DB live.

**Temuan tambahan (bukan bug dari eksekusi sesi ini, pre-existing, di luar scope):**
- `app/save_pesanan_pembelian_h.php` (salah satu dari 3 file yang difix SQL injection-nya) ternyata **ORPHANED — 0 caller di seluruh codebase** (`grep` konfirmasi tidak ada form/link yang submit ke situ). Flow PO aktif sekarang (`pesanan_pembelian_add.php`, menu "Input Manual") punya INSERT `tblorder_header` sendiri yang lengkap kolomnya, tidak lewat file ini. File ini juga punya bug pre-existing terpisah (INSERT tidak isi kolom `status`/`kd_cabang` yang NOT NULL tanpa default — gagal di bawah `sql_mode=STRICT_TRANS_TABLES` yang aktif live) — bug ini SUDAH ADA sebelum fix SQL injection (kolom yang di-insert sama persis, cuma diubah ke prepared statement), bukan regresi dari sesi ini. Karena orphaned & fail-safe (insert gagal bersih, tidak ada data korup), tidak diperbaiki sekarang — cukup didokumentasikan. Kalau mau dituntas, perlu keputusan: hapus file (dead code) atau lengkapi kolomnya (kalau ternyata masih dipakai user via bookmark URL lama).
- `save_pesanan_pembelian.php` DAN `pesanan_pembelian_add_print.php` terkonfirmasi AKTIF dipakai (dipanggil dari `pesanan_pembelian_add_next_rst.php`) — fix SQL injection di keduanya valid dan sudah ditest bekerja benar dengan data real.

**Belum dikerjakan (lanjut sesi berikutnya):**
- Task 4: sesi planning Promo Engine → `FSD_PROMO.md`.
- Open Item: 5 label `status_klasifikasi` lain (dari 7 total) masih perlu re-ekstraksi query Access asli — belum di scope Task 3 ini.
- Keputusan opsional: nasib `app/save_pesanan_pembelian_h.php` (orphaned, insert pre-existing broken) — hapus atau lengkapi, tunggu arahan user.

### Jawaban Owner — Skema Promo Harus Fleksibel (17 Jul 2026, chat WA)

Owner (pak Novian) jawab poin 5 checklist FSD_PROMO soal "Program Cuci Motor Gratis", sekaligus kasih arahan desain untuk skema promo secara umum:

- Program Cuci Motor Gratis: status masih jalan atau tidak, dan syaratnya (per berapa kali servis) — **belum dijawab eksplisit**, perlu ditanya ulang saat sesi planning Promo Engine.
- **Prinsip desain wajib untuk Promo Engine**: skema promo TIDAK BOLEH dedicated/hardcode nama tertentu ("cuci motor gratis" sebagai nama fix di kode). Semua harus jadi **data master** yang user (staf/admin) atur sendiri:
  - Nama program promo — bebas diisi user.
  - Batas waktu promo — bebas ditentukan user (tanggal mulai/selesai).
  - Apa yang dipromokan — bebas: item, jasa, atau kombinasi keduanya.
  - Bisa digabung dengan promo lain atau tidak — juga fleksibel, diatur per promo (flag "boleh gabung").

Ini jadi requirement inti untuk `docs/fsd/FSD_PROMO.md` yang akan dibuat di Task 4 — desain harus generic/rule-based via tabel master, bukan logic promo spesifik ditulis di PHP.

### Commit backlog + Testing browser end-to-end (18 Jul 2026)

**Commit backlog besar:** repo punya 218 file uncommitted menumpuk lintas banyak sesi (24 Jun–17 Jul), belum sempat di-commit. Digabung jadi 1 commit `b11f51d` (216 file, 22720 insertions) — sengaja skip file junk (docx/pdf meeting notes, archive/*.deb, __pycache__, screenshot).

**Testing browser sebagai user awam** (login admin, jalur nyata Pembelian → PO → DO) menemukan **3 bug**, semua diperbaiki & di-commit (`da9ad18`):

1. **Master Approval Bertingkat PO tampil kosong** — kolom `tb_master_approval_pembelian.kode_posisi` kebuat pakai collation default MySQL 8 (`utf8mb4_0900_ai_ci`, migrasi kemarin gak declare COLLATE eksplisit) vs `tb_master_posisi.kode_posisi` yang pakai `utf8mb4_general_ci` (konvensi lama). JOIN gagal "Illegal mix of collations" — silent, `mysqli_query` return false tanpa pesan error, user cuma lihat tabel kosong. **Fixed**: migrasi `db/migrations/2026-07-18_fix_collation_approval_pembelian.sql`.
2. **SQL injection + reflected XSS di `pesanan_pembelian_cetak.php`** (app/ dan duplikat `_pengadaan/`) — halaman cetak nota yang TERNYATA dipakai di alur produksi nyata (auto-redirect setelah PO disimpan dari `pesanan_pembelian_add.php`), terlewat dari audit SQL injection 17 Juli karena beda nama file dari yang sudah difix (`pesanan_pembelian_add_print.php`). **Fixed**: prepared statement + `htmlspecialchars`, di kedua salinan.
3. **KRITIS — approval bertingkat PO bisa di-bypass total**: PO baru default `status_approval='draft'`; `pesanan_pembelian_detail.php` treat draft/NULL/kosong sebagai "boleh lanjut" (sama seperti approved). Proses "ajukan approval" (ubah ke `pending`) OPSIONAL, harus staf klik manual. Tombol **"Lanjut ke DO"** (`do_from_po.php`, entry point utama alur normal) SAMA SEKALI GAK CEK `status_approval` — staf yang cuma ikut alur biasa (Input Manual → Simpan → Lanjut ke DO) lolos approval berapa pun nominalnya. Fitur yang ditandai "SELESAI" 17 Juli efektif gak jalan di jalur pemakaian normal.
   - **Fix** (pilihan user: "Wajibkan approval otomatis"): PO baru yang total-nya masuk bracket approval aktif otomatis di-set `status_approval='pending'` saat disimpan (`pesanan_pembelian_add.php` + duplikat `_pengadaan/`). `do_from_po.php` ditambah gate: tolak lanjut ke DO kalau PO masuk bracket & status bukan `'approved'`.
   - Diverifikasi via subprocess PHP CLI (include file produksi asli, mock session/POST) — blocked saat status draft dengan pesan jelas, lolos saat status approved. Data test dibersihkan, tidak ada sisa di DB live.

**Pelajaran:** fitur "wajib approval" baru harus dicek di SEMUA entry point yang bisa nyampe ke aksi akhir (bukan cuma 1 halaman detail) — termasuk tombol shortcut/redirect otomatis, bukan cuma jalur yang dirancang developer.

**Belum dikerjakan (lanjut sesi berikutnya):**
- Task 4: sesi planning Promo Engine → `FSD_PROMO.md` (masih perlu tanya ulang Owner soal status Program Cuci Motor Gratis).
- UX gap (bukan bug): gak ada halaman "daftar PO menunggu approval" — user harus tau no. PO manual buat buka halaman detail approve/reject.
- Field "Level Approval (urutan angka)" vs "Urutan Pengecekan" di form Master Approval Bertingkat kelihatan duplikatif/bikin bingung user awam — belum diubah, cuma temuan UX.
- Open Item: 5 label `status_klasifikasi` alarm harga beli (dari 7 total) masih perlu re-ekstraksi query Access asli.
- Keputusan opsional: nasib `app/save_pesanan_pembelian_h.php` (orphaned, insert pre-existing broken) — hapus atau lengkapi, tunggu arahan user.

---

*Dokumen ini diupdate setiap ada klarifikasi baru dari tim operasional.*  
*Versi: 1.3 | 29 Juni 2026 (dibuat), 4 Juli 2026 (eksekusi F1-D/E/C + F2-C), 17 Juli 2026 (mulai FASE 4 — fix SQL injection PO, approval bertingkat & alarm harga beli selesai), 18 Juli 2026 (commit backlog 218 file + 3 bug kritis ketemu & fixed dari testing browser: collation silent-fail, SQL injection cetak nota PO, approval bertingkat bisa di-bypass) | Tim IT Web Bengkel FIT MOTOR*
