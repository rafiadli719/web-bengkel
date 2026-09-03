# Merge Modul Kasir Closing (web_kasir) ke FIT MOTOR Web Base — Design & Task Spec

**Tanggal:** 2026-09-03
**Status:** Approved (menunggu writing-plans / eksekusi)
**Author:** Claude Code (brainstorming session bareng Rafi)

## 1. Latar Belakang

FIT MOTOR Web Base (`fitmotor_dbbengkel`) dulu punya modul kasir (buka/tutup
kasir shift) tapi sudah **mati** — file (`app/kas kasir/*.php`,
`app/kas_awal.php`, `app/kas_akhir.php`) tidak lagi terdaftar di
`app/menu_config.php`, tabelnya (`tbkas_kasir_header`, `tbkas_kasir_detail`,
`tbkas_kasir`, `tblakunkas`, `tbakun`, `tbakun_pos`, `tblkas_keluar_masuk`)
isinya 0–27 baris saja.

Sebagai gantinya dibangun projek terpisah **web_kasir**
(`C:\laragon\www\web_kasir\website_kasir`, DB `fitmotor_maintance-beta`,
48 tabel) — sistem closing kasir lengkap yang **sudah dipakai produksi
harian** di 5 cabang: kas awal/akhir per shift, closing per cabang
(termasuk grouping "dipinjam/meminjam" antar kasir), validasi setoran ke
keuangan pusat → bank, serah terima kasir, revisi closing, master akun /
nama transaksi. Data riil: `kasir_transactions` 2222 baris, `kas_awal`
2224, `kas_akhir` 2213, `pengeluaran_kasir` 22156, `setoran_ke_bank` 337.

**Kedua sistem sudah didesain untuk nyambung:**
- `web_kasir.cabang.kode_cabang` = `fitmotor.tbcabang.cabang_ref_kode`
  (persis cocok, 5 cabang sama).
- `web_kasir.users.kode_karyawan` = `fitmotor.tbuser.kode_karyawan`
  (format sama).
- Ada bridge API HPP aktif: `hpp_api_keys` / `hpp_sync_status` /
  `hpp_bypass` di DB web_kasir — web_kasir manggil data HPP dari
  fitmotor lewat API per-cabang, bukan akses DB langsung.

Auth/role web_kasir (`users.role`: super_admin/admin/user/kasir) terpisah
dari RBAC fitmotor (`tb_user_roles` / `tb_permissions`).

Task ini: gabungkan web_kasir ke FIT MOTOR Web Base sebagai modul
**Keuangan > Kasir**, satu DB satu app, matikan web_kasir & bridge API
HPP-nya.

## 2. Keputusan Desain (disepakati dengan Rafi)

| Keputusan | Pilihan |
|---|---|
| Bentuk gabung | **Full merge** — 1 DB (`fitmotor_dbbengkel`), 1 app. Bukan embed-app-DB-terpisah, bukan iframe/link doang. |
| Auth & role | **Full pakai fitmotor** — `tbuser` + `tb_user_roles` + `tb_permissions`. Tabel `users` punya web_kasir didrop setelah migrasi data. |
| Fasing eksekusi | **Big bang** — satu window migrasi total, bukan bertahap per sub-modul. |
| Tabel kasir lama fitmotor (dead) | Backup dump dulu ke `backups/`, DROP setelah cutover modul baru sukses (langkah terpisah, bukan bareng window migrasi utama). |

## 3. Arsitektur Target

### 3.1 Skema Database — tabel web_kasir masuk `fitmotor_dbbengkel`, dibedakan pakai suffix `_closing_kasir`

Semua masuk `fitmotor_dbbengkel`. Suffix baru: `*_closing_kasir` (nama asli dipertahankan, cuma ditambah suffix) — bukan skema rename konseptual (keputusan direvisi 2026-09-03, Rafi: "gak mengubah proses/struktur"). Nama tabel sumber web_kasir dipertahankan APA ADANYA, cuma ditempel `_closing_kasir` di belakang buat bedain dari tabel fitmotor yang konsepnya mirip (mis. `data_penjualan` (kasir) vs `tblpenjualan_header` (fitmotor) — bukan `tblpenjualan_closing_kasir`, karena nama sumbernya memang `data_penjualan`, bukan `tblpenjualan`). Ini juga bikin proses porting ~180 file PHP lebih aman: query di dalam kode cukup tempel `_closing_kasir` di nama tabel, TANPA perlu mikir ulang nama baru — turunin resiko salah ketik/salah mapping pas refactor massal.

| Tabel lama (web_kasir) | Tabel baru (fitmotor) | Catatan |
|---|---|---|
| `cabang` | — (drop) | pakai `tbcabang.cabang_ref_kode` langsung |
| `users` | — (drop) | pakai `tbuser` + RBAC fitmotor |
| `kasir_transactions` | `kasir_transactions_closing_kasir` | tabel transaksional utama, 2222 baris |
| `closing_transaction_groups` | `closing_transaction_groups_closing_kasir` | |
| `closing_transaction_details` | `closing_transaction_details_closing_kasir` | |
| `closing_revision_requests` | `closing_revision_requests_closing_kasir` | |
| `kas_awal` | `kas_awal_closing_kasir` | 2224 baris |
| `kas_akhir` | `kas_akhir_closing_kasir` | 2213 baris |
| `detail_kas_awal` | `detail_kas_awal_closing_kasir` | |
| `detail_kas_akhir` | `detail_kas_akhir_closing_kasir` | |
| `pemasukan_kasir` | `pemasukan_kasir_closing_kasir` | 1723 baris |
| `pemasukan_pusat` | `pemasukan_pusat_closing_kasir` | |
| `pengeluaran_kasir` | `pengeluaran_kasir_closing_kasir` | 22156 baris — tabel terbesar |
| `pengeluaran_pusat` | `pengeluaran_pusat_closing_kasir` | |
| `setoran_ke_bank` | `setoran_ke_bank_closing_kasir` | 337 baris |
| `setoran_ke_bank_detail` | `setoran_ke_bank_detail_closing_kasir` | |
| `setoran_keuangan` | `setoran_keuangan_closing_kasir` | |
| `pengambilan_setoran` | `pengambilan_setoran_closing_kasir` | |
| `pengambilan_setoran_edit_log` | `pengambilan_setoran_edit_log_closing_kasir` | |
| `pengambilan_setoran_pembayaran` | `pengambilan_setoran_pembayaran_closing_kasir` | |
| `serah_terima_kasir` | `serah_terima_kasir_closing_kasir` | |
| `master_akun` | `master_akun_closing_kasir` | 34 baris |
| `master_nama_transaksi` | `master_nama_transaksi_closing_kasir` | 159 baris |
| `master_rekening_cabang` | `master_rekening_cabang_closing_kasir` | |
| `kas_awal_config` | `kas_awal_config_closing_kasir` | |
| `konfirmasi_buka_transaksi` | `konfirmasi_buka_transaksi_closing_kasir` | |
| `audit_log` | `audit_log_closing_kasir` | |
| `keping` | *(cek dulu)* | verifikasi dulu apa dupe `tbkas_kasir_detail` fitmotor sebelum putuskan nama |
| `hpp_api_keys`, `hpp_bypass`, `hpp_bypass_request`, `hpp_sync_log`, `hpp_sync_status` | **DROP** | bridge API mati, HPP diakses langsung via query same-DB |
| `dynamic_sidebars`, `user_sidebar_settings` | **DROP** | spesifik app lama, gak relevan di fitmotor |
| `users`, `masterkeys` | **JANGAN DROP dulu** | lihat §3.8 — dipakai live oleh bridge eksternal `login_dashboard` → priori-tech, drop cuma boleh SETELAH bridge itu dialihkan ke `tbuser` fitmotor |
| `v_transaksi_ada_selisih`, `v_transaksi_dikembalikan_cs`, `v_transaksi_perlu_validasi`, `view_pemasukan_combined`, `view_pemasukan_kasir`, `view_pemasukan_pusat`, `view_pemasukan_with_closing`, `view_pengeluaran_kasir`, `view_pengeluaran_pusat`, `view_setoran_with_closing` | rebuild ulang | recreate VIEW definition setelah tabel base di-rename |

FK baru: semua kolom `kode_cabang` → `tbcabang.cabang_ref_kode`, semua
`kode_karyawan` → `tbuser.kode_karyawan`.

### 3.2 Struktur file aplikasi

```
app/_keuangan/kasir/
├── dashboard.php             (rekap/summary — port dari admin_dashboard.php)
├── kas_awal.php              (buka kasir/shift)
├── kas_akhir.php             (tutup kasir/shift)
├── closing.php               (closing per cabang, grouping dipinjam/meminjam)
├── closing_revisi.php
├── pemasukan.php / pengeluaran.php
├── setoran_bank.php
├── serah_terima.php
├── validasi_keuangan_pusat.php
└── ... (~140 file web_kasir dipindah & direfactor ke sini)
```

`admin_dashboard.php` (18.5KB) satu-satunya dashboard AKTIF — dilink
dari `includes/sidebar.php`. 3 varian lain
(`kasir_closing_dashboard.php`, `kasir_dashboard_baru.php`,
`kasir_dashboard_baru1..php`) tidak dilink sidebar → dead variant, tidak
diport (pola sama kayak file `_asli`/bernomor lain di source). Isi
dashboard: cek role user (`SELECT role FROM users WHERE kode_karyawan =
?`), rekap `kasir_transactions` per cabang/tanggal.

Ikuti pola direktori underscore fitmotor yang sudah ada (`_admincab`,
`_ajax`, `_template`, `_tools`). Koneksi DB pakai `app/koneksi.php`
fitmotor (bukan `config.php` mysqli+PDO ganda punya web_kasir). Session
pakai session fitmotor yang sudah jalan (`$_SESSION['_cabang']`, dst) —
bukan tabel `users` sendiri.

### 3.3 Auth & RBAC mapping

| Role web_kasir lama | Role/permission fitmotor |
|---|---|
| `super_admin` | RBAC admin penuh (akses semua cabang + approval) |
| `admin` | Role keuangan pusat |
| `user` | Role staff cabang |
| `kasir` | Role kasir cabang |

Didaftarkan sebagai permission baru di `tb_permissions` / `tb_user_roles`
mengikuti pola modul lain yang sudah ada di fitmotor (bukan bikin sistem
role paralel).

### 3.4 Migrasi data

Script sekali-jalan (CLI via `php.exe`, pola disposable script — lihat
`.claude` memory `web-bengkel-environment`), urutan wajib:

1. **Validasi mapping** — cek semua `kode_cabang` web_kasir match ke
   `tbcabang.cabang_ref_kode`, semua `kode_karyawan` match ke
   `tbuser.kode_karyawan`. Kalau ada yang yatim (gak match) → **STOP**,
   laporkan daftar yatim, jangan lanjut insert.
2. **Tabel independen dulu**: `master_akun`, `master_nama_transaksi`,
   `master_rekening_cabang`, `kas_awal_config`.
3. **Tabel transaksional besar**: `kas_awal` → `kas_akhir` →
   `kasir_transactions` (2222 baris) → `pemasukan_kasir`/`pusat` →
   `pengeluaran_kasir` (22156 baris, tabel terbesar) →
   `setoran_ke_bank`(+detail) → `pengambilan_setoran`(+log/pembayaran) →
   `serah_terima_kasir` → `closing_transaction_groups`(+details) →
   `closing_revision_requests` → `konfirmasi_buka_transaksi` →
   `audit_log`.
4. **Rebuild VIEW** setelah semua base table pindah nama.
5. **Verifikasi angka**: total per tabel migrasi harus sama persis
   dengan `COUNT(*)` sumber; sample beberapa `kode_transaksi` bandingkan
   nominal `total_closing`/`kas_akhir` sebelum-sesudah.

### 3.5 Cutover (big bang)

Satu window:
1. Matikan akses web_kasir lama (set read-only / offline).
2. Full DB dump `fitmotor_dbbengkel` (rollback point) + snapshot
   `fitmotor_maintance-beta`.
3. Jalankan migrasi skema + data (§3.4).
4. Tambah menu "Keuangan > Kasir" ke `app/menu_config.php` dengan
   permission baru (§3.3).
5. Smoke test tiap 5 cabang: buka kasir, input pemasukan/pengeluaran,
   tutup kasir, closing, validasi setoran.
6. Umumkan live ke semua cabang.

**Rollback plan**: restore dump `fitmotor_dbbengkel` dari langkah 2 kalau
migrasi/smoke test gagal; web_kasir lama tetap idle-tapi-utuh (belum
didrop) sampai cutover dinyatakan sukses beberapa hari.

### 3.6 Tabel kasir lama fitmotor (dead code) — langkah terpisah

Setelah cutover modul baru sukses (bukan bareng window migrasi utama):
1. Dump `tbkas_kasir_header`, `tbkas_kasir_detail`, `tbkas_kasir`,
   `tblakunkas`, `tbakun`, `tbakun_pos`, `tblkas_keluar_masuk` ke
   `backups/`.
2. `DROP TABLE` ketujuh tabel itu.
3. Archive file PHP mati (`app/kas kasir/*.php`, `app/kas_awal.php`,
   `app/kas_akhir.php`, dst yang gak ada di menu_config.php) ke
   `archive/` — pola arsip yang sudah dipakai di repo ini.

### 3.7 Testing

- Playwright E2E per role: kasir buka/tutup kasir, input
  pemasukan/pengeluaran, closing, validasi setoran keuangan pusat, serah
  terima kasir.
- Bandingkan angka closing sebelum/sesudah migrasi untuk beberapa
  sample tanggal (`total_closing`, `kas_akhir.total_nilai` harus cocok).
- Regression check: pastikan bridge HPP lama dihapus tidak merusak
  fitur HPP fitmotor yang sudah ada (HPP FIFO dsb, lihat memory
  `project_gap_analysis_access_vs_webapp_2026-08-09`).

### 3.8 Dependensi eksternal — bridge SSO ke priori-tech (temuan 2026-09-03, setelah spec awal)

`web_kasir/includes/sidebar.php` redirect login ke
`../../login_dashboard/login.php` — projek KETIGA,
`C:\laragon\www\login_dashboard` (SSO hub, punya `login.php`,
`sso_token.php`, `sync_users_api.php`, DB target sama:
`fitmotor_maintance-beta`). `sync_users_api.php` di situ adalah jembatan
API buat projek KEEMPAT, **"priori-tech"** (VPS terpisah, tidak bisa
akses DB shared hosting langsung) — endpoint ini query
`users JOIN masterkeys` (proteksi shared-secret header) dan
**MASIH AKTIF DIPAKAI** (dikonfirmasi Rafi 2026-09-03).

**Dampak ke plan**: tabel `users` dan `masterkeys` di
`fitmotor_maintance-beta` TIDAK BOLEH langsung didrop pas cutover (beda
dari asumsi awal spec ini). Pendekatan: alihkan query
`sync_users_api.php` supaya baca dari `tbuser`/RBAC fitmotor
(`fitmotor_dbbengkel`) alih-alih `users`/`masterkeys` lama — biar cuma
ada SATU sumber data karyawan (`tbuser`), bukan dua sumber paralel yang
gampang out-of-sync. Endpoint tetap harus mengembalikan bentuk JSON yang
sama (`kode_karyawan, nama_karyawan, role, nama_cabang, kode_cabang`)
supaya priori-tech tidak perlu berubah di sisi mereka. Baru setelah
bridge dialihkan & diverifikasi jalan, `users`+`masterkeys` boleh
didrop.

`login_dashboard/sync_users_api.php` punya secret key **hardcoded
plaintext** di source (`$sync_secret = '...'`) — di luar scope merge
ini buat diperbaiki langsung (bukan bagian dari repo `web-bengkel`),
tapi dicatat sebagai temuan security buat dilaporkan ke Rafi terpisah.

### 3.9 Audit lanjutan (2026-09-03, permintaan Rafi "cek jangan ada yang kelewat")

Audit ulang seluruh 155 file root `website_kasir` (bukan cuma ~40 file
yang kesampel di brainstorming awal) menemukan modul-modul yang belum
tercakup spec/plan awal:

**Landing page ganda** — `login.php` route beda per role: admin/super_admin
→ `admin_dashboard.php`, role `user`/`kasir` → **`index_kasir.php`**
(33KB) — dashboard utama kasir harian, TIDAK sama dengan admin dashboard,
sebelumnya sama sekali kelewat.

**Modul Keuangan Pusat sendiri** — `keuangan_pusat.php` (100KB),
`laporan_keuangan_pusat.php`, `edit_keuangan_pusat.php`,
`hapus_keuangan_pusat.php` — lebih besar & terpisah dari
"validasi_keuangan_pusat.php" yang diasumsikan simpel di plan awal.

**`setoran_keuangan.php` (434KB) + `setoran_keuangan1.php` (268KB) +
`setoran_keuangan_cs.php` + `_cs_enhanced.php`** — file terbesar di
seluruh projek, jauh lebih kompleks dari "setoran_bank.php" yang
diasumsikan di Task 14 lama.

**OCR pipeline nyata** untuk verifikasi bukti transfer/pelunasan hutang —
`services/pelunasan_hutang/{DocumentReader,ExtractorService,OCRService,ParserService}.php`,
dipicu dari form upload di `setoran_keuangan.php`, pakai
`smalot/pdfparser`. Field `mutasi_hasil_json`/`mutasi_confidence` di
`pengambilan_setoran` (sudah ada di skema §3.1) adalah hasil OCR ini.

**Modul laporan/export — 17 file**, pakai
`phpoffice/phpspreadsheet` + `tecnickcom/tcpdf` + `phpoffice/phpword`
(composer.json web_kasir) — beda generasi lib dari fitmotor
(`dompdf/dompdf` doang di `composer.json` fitmotor + `app/PHPExcel`
vendored lama). **Keputusan: lib baru (phpspreadsheet/tcpdf) di-`composer
require` KHUSUS buat modul Keuangan Kasir, TIDAK mengganti PHPExcel/dompdf
yang dipakai modul laporan fitmotor lain** — supaya modul lain gak
kesenggol perubahan lib.

**CRUD master data terpisah dari data migrasi** — `master_akun.php`,
`master_nama_transaksi.php`, `master_rekening_cabang.php`, `keping.php`,
`kas_awal_config_crud.php` butuh UI admin, bukan cuma tabelnya pindah
(Task 2/4 di plan lama cuma migrasi data, belum ada UI-nya).
`setup_master_cabang.php` **di-drop** — itu setup UI buat tabel `cabang`
web_kasir sendiri yang sudah diputuskan drop (§3.1), cabang dikelola
fitmotor via `tbcabang`.

**Manajemen karyawan kasir** (`edit_employee.php`, `delete_employee.php`,
`view_employees.php`, `get_employees.php`, `update_employee.php`,
`users.php`) — **diputuskan: TIDAK diport sebagai modul terpisah**,
manajemen karyawan tetap satu sumber di `tbuser` fitmotor. Tapi
`users.kode_user` (varchar(3) unik, kode singkat kayak "AAA"/"BOA" —
dipakai kasir buat quick-login/ganti shift) **tidak ada padanannya di
`tbuser`** — ditambahkan sebagai kolom baru `tbuser.kode_kasir` (lihat
§3.10) supaya fitur quick-login kasir tetap jalan tanpa employee module
terpisah.

**Input manual penjualan/servis harian** — `input_penjualan_servis.php`,
`edit_penjualan_servis.php` baca/tulis tabel `data_penjualan`/
`data_servis` — **2 tabel ini KELEWAT dari mapping skema §3.1**, harus
ditambahkan. **Diputuskan: tetap diport** (ada alasan bisnis dari Rafi —
cross-check manual kasir vs sistem, meski fitmotor punya data live di
`tblpenjualan_header`/`tblservice`).

**Monitoring/riwayat terpisah dari dashboard** — `monitoring_setoran.php`,
`monitor_branch_data.php`, `view_transaksi.php`, `view_transaksi_admin.php`.

**File fisik belum ada rencana migrasi** — `uploads/pelunasan_hutang/`
(bukti transfer/dokumen hasil OCR) perlu dipindah ke storage fitmotor,
bukan cuma path di DB yang di-migrasi.

**`masterkey.php`/`store_masterkey.php`** — UI admin buat tabel
`masterkeys` (dipertahankan sementara buat bridge priori-tech, §3.8).
Setelah Task 21 (redirect bridge ke `tbuser`), UI ini **jadi obsolete**
(status aktif karyawan cukup dari `tbuser.is_active`) — tidak diport.

**`process_pengadaan_verification.php`** — nama menyesatkan (bukan
modul pengadaan fitmotor), isinya helper yang **auto-ALTER TABLE saat
runtime** kalau kolom `pengambilan_setoran_edit_log`/
`pengambilan_setoran_pembayaran` belum ada — pola auto-DDL-diam-diam
yang TIDAK BOLEH diport apa adanya (resiko race condition/DDL diam-diam
di production). DDL yang di-auto-generate script ini harus dijadikan
DDL eksplisit di skema §3.1/Task 3, bukan logic runtime.

**Temuan keamanan** — file `fitmotor_maintance-beta (1).sql` (4.3MB,
dump database mentah) dan `error_log` (798KB) nangkring langsung di
web-root `website_kasir/`, berpotensi diakses publik. File dev/debug
mati lain: `debug_closing_dropdown.php`, `debug_rekening.php`,
`cleanup_debug_files.php`, `create_test_closing.php`, `temp_js.txt`,
`temp_php.txt`, `temp_php2.txt`, `restore_pga.php`,
`repair_missing_data.php`, `fix_orphaned_payments.php`, `fix_sisa.php`,
`patch_ocr_upload.php` (script sekali-pakai edit source), `run_sync.php`
(script test manual hardcoded). Semua **TIDAK diport**, dan dump
SQL+error_log **perlu dihapus dari web_kasir SEGERA**, independen dari
timeline cutover — ini resiko aktif selama web_kasir masih online.

### 3.10 Perubahan skema `tbuser` fitmotor (kolom baru)

```sql
ALTER TABLE tbuser ADD COLUMN kode_kasir VARCHAR(3) NULL UNIQUE AFTER kode_karyawan;
```
Diisi dari `web_kasir.users.kode_user` saat migrasi (Task 22), dipetakan
by `kode_karyawan` yang match.

## 4. Ambiguitas / hal yang perlu diverifikasi saat implementasi

- Tabel `keping` web_kasir — cek dulu isinya, apakah duplikat konsep
  `tbkas_kasir_detail` (pecahan uang) fitmotor sebelum putuskan nama
  akhir / apakah perlu di-drop juga.
- Session variable exact yang dipakai fitmotor untuk `kode_karyawan`
  login (belum diverifikasi nama variabelnya secara langsung — perlu
  dicek di file login/`_admincab` saat implementasi, bukan diasumsikan).
- `dynamic_sidebars` / `user_sidebar_settings` — pastikan gak ada fitur
  UI kasir yang bergantung ke situ sebelum didrop total.

## 5. Next Step

Lanjut ke `writing-plans` skill untuk breakdown jadi implementation plan
bertahap (task list dengan file per task, urutan eksekusi, checkpoint
review) berdasarkan spec ini.
