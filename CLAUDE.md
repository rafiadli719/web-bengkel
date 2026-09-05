## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## Lapor Progress ke checklist-projek (Dashboard Web Base)

Repo ini ("FIT MOTOR WEB BASE") dimonitor progress kesiapan fiturnya di dashboard terpusat
`checklist-projek` — produksi `https://checklist.fitmotor.web.id` (default), lokal `http://localhost:8090` (lihat `CHECKLIST_BASE_URL` di `.env`).

Dashboard sekarang menggunakan **Hierarki 4-Level Murni** di `/web-base`:
1. **Modul Utama** (Servis, Pembelian, Penjualan, Laporan & Keterangan)
2. **Sub Modul** (Dilengkapi narasi alur proses kerja bertahap per nama file `.php`)
3. **Halaman** (Dipetakan langsung ke file fisik `.php` di `app/`)
4. **Fitur Checklist** (Status RAG: 🟢 Hijau = Siap Pakai / Lolos UAT, 🟡 Kuning = Tahap Uji Coba, 🔴 Merah = Pengerjaan)

### Cara Lapor Progress via Script

Panggil `scripts/update-checklist.sh` saat Claude Code menyelesaikan pengerjaan/pengujian suatu fitur:

```bash
./scripts/update-checklist.sh "nama fitur atau ID fitur" "status" "progress_percent" "keterangan"
# contoh:
./scripts/update-checklist.sh "feat-booking-servis" "hijau" 100 "Form booking servis reguler lolos pengujian lapangan"
./scripts/update-checklist.sh "feat-monitor-antrian" "uat" 80 "Antrian mekanik siap ditest di bengkel"
```

Status valid:
- **RAG Status**: `hijau` (siap pakai) | `kuning` (tahap uji coba / UAT) | `merah` (dalam pengerjaan)
- **Status Pengembangan**: `selesai` (100%) | `uat` (80%) | `development` (30-60%) | `belum_mulai` (0%)

### Cara Lapor Progress via HTTP / Webhook Langsung

Endpoint serbaguna (support JSON payload):
```http
POST /api/webhook/progress
X-API-Key: <API_KEY_WEBBENGKEL>
Content-Type: application/json

{
  "feature": "feat-booking-servis",
  "status": "hijau",
  "progress_percent": 100,
  "keterangan": "Lolos pengujian lapangan di bengkel Trayeman"
}
```

### Daftar ID & Nama Fitur Resmi di Dashboard Web Base

Gunakan ID atau Nama Fitur berikut saat memanggil script/webhook:

| ID Fitur | Halaman File .php | Nama Fitur Terdaftar | Status |
|:---|:---|:---|:---:|
| `feat-cari-nopol` | `servis-carinopol.php` | Pencarian Nopol & Cek Riwayat Servis | 🟢 Hijau |
| `feat-validasi-kontak` | `servis-carinopol.php` | Validasi Kontak WhatsApp Pelanggan | 🟡 Kuning |
| `feat-tambah-pelanggan` | `input_pelanggan_awal.php` | Registrasi Pelanggan & Motor Baru | 🟡 Kuning |
| `feat-booking-servis` | `servis-input-reguler.php` | Form Booking Servis & Catat Keluhan | 🟢 Hijau |
| `feat-nomor-antrean` | `servis-reguler.php` | Cetak Tiket Nomor Antrian Fisik | 🟢 Hijau |
| `feat-monitor-antrian` | `servis-reguler.php` | Monitor Antrian & Pengerjaan Mekanik Realtime | 🟡 Kuning |
| `feat-workorder-mekanik`| `workorder-input.php` | Pencatatan Work Order & Temuan Mekanik | 🟡 Kuning |
| `feat-bayar-servis` | `servis-reguler-byr.php` | Proses Kasir Pembayaran & Cetak Struk Lunas | 🟡 Kuning |
| `feat-validasi-garansi` | `servis-carinopol-garansi.php` | Cek Histori Servis & Validasi Masa Garansi | 🟡 Kuning |
| `feat-klaim-garansi` | `servis-garansi.php` | Input Klaim & Pengerjaan Ulang Garansi | 🟡 Kuning |
| `feat-cancel-antrean` | `servis-reguler.php (modal)` | Verifikasi Antrian & Modal Konfirmasi Batal | 🟡 Kuning |
| `feat-cancel-eksekusi` | `servis-cancel-proses.php` | Eksekusi Hapus Antrian & Catat Log Batal | 🟡 Kuning |
| `feat-pencarian-produk` | `cari_item_pembelian.php` | Pencarian Sparepart & Cek Ketersediaan Stok | 🟢 Hijau |
| `feat-input-pembelian` | `pembelian_add.php` | Input Faktur Beli & Auto-Tambah Stok Toko | 🟡 Kuning |
| `feat-riwayat-pembelian`| `pembelian.php` | Riwayat Faktur & Pelunasan Hutang Supplier | 🟡 Kuning |
| `feat-stok-masuk` | `stok_masuk_add.php` | Input Penerimaan Barang & Update Kartu Stok | 🟡 Kuning |
| `feat-pos-cari-item` | `penjualan_add_item_cari.php`| Pencarian Data Pelanggan & Katalog Belanja | 🟡 Kuning |
| `feat-pos-pelayanan` | `penjualan_add.php` | Form Transaksi Kasir POS & Auto-Potong Stok | 🟡 Kuning |
| `feat-pos-struk` | `penjualan_struk.php` | Cetak Struk/Nota Pembayaran Kasir | 🟡 Kuning |
| `feat-riwayat-penjualan`| `penjualan.php` | Riwayat Transaksi Kasir & Rekap Harian | 🟡 Kuning |
| `feat-monitor-antarcab` | `pengadaan_antarcab.php` | Monitor Permintaan & Buat Tiket Mutasi Stok | 🟡 Kuning |
| `feat-kirim-antarcab` | `pengadaan_antarcab_proses.php`| Konfirmasi Pengiriman & Potong Stok Asal | 🟡 Kuning |
| `feat-terima-antarcab` | `pengadaan_antarcab_terima.php`| Konfirmasi Penerimaan Fisik & Tambah Stok | 🟡 Kuning |
| `feat-lap-servis` | `lap_servis.php` | Rekap Pendapatan Servis & Komisi Mekanik | 🟡 Kuning |
| `feat-lap-pembelian` | `lap_pembelian.php` | Rekap Faktur Pembelian & Hutang Supplier | 🟡 Kuning |
| `feat-lap-penjualan` | `lap_penjualan.php` | Rekap Omset Penjualan & Margin Profit | 🟡 Kuning |
| `feat-lap-antarcab` | `lap_antarcab.php` | Rekap Riwayat Mutasi Antar Cabang | 🟡 Kuning |
| `feat-stok-akhir` | `stok-akhir.php` | Cek Posisi Sisa Stok Fisik per Cut-off | 🟡 Kuning |
| `feat-catatan-keterangan`| Form Penjualan / Stok Log | Kolom Keterangan / Memo Khusus per Transaksi | 🟡 Kuning |

### Endpoint API CRUD Web Base yang Tersedia

Dashboard checklist-projek menyediakan API RESTful lengkap:
- `GET /api/web-base/tree` — Ambil struktur hierarki 4-level lengkap beserta alur proses dan rollup RAG
- `GET /api/web-base/stats` — Ambil ringkasan statistik kesiapan
- `GET|POST|PUT|DELETE /api/web-base/modules` — CRUD Modul Utama
- `GET|POST|PUT|DELETE /api/web-base/sub-modules` — CRUD Sub Modul (mendukung field `alur_proses`)
- `GET|POST|PUT|DELETE /api/web-base/pages` — CRUD Halaman per Sub Modul
- `GET|POST|PUT|DELETE /api/web-base/features` — CRUD Fitur per Halaman (status_rag: hijau/kuning/merah)

## Progress Merge Modul Kasir   Keuangan (2026-09-03)

Plan lengkap: `docs/superpowers/plans/2026-09-03-merge-modul-kasir-keuangan.md`
(34 task). Status per 2026-09-03 malam:

- **Task 1-10 SELESAI & commit**: backup DB (372MB), DDL 25 tabel
  `*_closing_kasir` + 10 VIEW, migrasi data ~50rb+ baris (row-count match
  semua), RBAC (`tb_master_posisi.permissions` +5 kode `kasir_*` di
  ADM/KEU/KSR).
- **Task 11-12 SELESAI & commit**: porting `app/_keuangan/kasir/` -
  `koneksi_kasir.php`, `kas_awal.php`, `kas_akhir.php`,
  `closing_revision_helpers.php`, `pemasukan.php`, `pengeluaran.php`,
  `process_closing_transaction.php`, `utils.php`. Sumber real beda dari
  draft plan awal (dashboard besar, bukan file kecil) - lihat commit
  message masing-masing buat detail keputusan porting.
- **Keputusan susulan (2026-09-03 malam)**: tabel `kas_awal_closing_kasir`
    di-rename jadi **`kas_awal`** (dan `kas_akhir_closing_kasir`  
  `kas_akhir`, `detail_kas_awal_closing_kasir`   `detail_kas_awal`,
  `detail_kas_akhir_closing_kasir`   `detail_kas_akhir`) - TIDAK ada
  tabrakan nama sama tabel fitmotor lama (dicek: gak ada tabel `kas_awal`/
  `kas_akhir` asli di `fitmotor_dbbengkel`, fitur kasir lama fitmotor
  pakai `tbkas_kasir_header`/`tbkeping` bukan nama itu). File lama
  `app/kas_awal.php`/`app/kas_akhir.php` (pakai `tbkas_kasir_header`)
  MASIH LIVE, belum diganti - penggantian menu ke versi baru
  `app/_keuangan/kasir/kas_awal.php` ditahan sampai Task 15 (menu
  wiring), biar gak putus fitur user tanpa checkpoint.
- **Task 13-34 BELUM dikerjakan**: closing (`close_transaksi1.php`
  141KB), closing revisi, `setoran_keuangan.php` (424KB, terbesar
  seprojek), cutover (Task 17) & drop tabel mati (Task 18) - 2 task
  terakhir itu WAJIB tanya konfirmasi eksplisit dulu sebelum eksekusi
  (irreversible, sistem kasir/keuangan live).
- **Update 2026-09-04**: sejak catatan di atas ditulis, banyak task
  gap-analysis (14b dst, lihat `git log`) sudah jalan lewat porting
  copy-paste + patch per file nyata (bukan urutan linear 13-34 di plan
  awal — plan lama dipakai sebagai checklist referensi, bukan urutan
  eksekusi ketat). **Task 29 (CRUD master data admin) SELESAI & commit
  c45867a**: `master_akun.php`, `master_nama_transaksi.php`,
  `master_rekening_cabang.php`, `keping.php` - semua pakai
  `koneksi_kasir.php` + PDO prepared statement (bukan concat SQL
  mentah source asli). `master_rekening_cabang.php` dropdown cabang
  diganti `tbcabang.cabang_ref_kode` (bukan tabel `cabang` web_kasir).
  Data sumber sudah termigrasi & terverifikasi (34/159/5/10 baris).
  Cutover (Task 17) & drop tabel mati (Task 18) masih TETAP butuh
  konfirmasi eksplisit sebelum eksekusi.
- **Update 2026-09-04 malam**: Task 25 (lib laporan phpspreadsheet+tcpdf+fpdf,
  commit 5c75534/479ba7c/b1b9423), **Task 31 (monitoring & riwayat transaksi,
  commit 6716bdf)**, dan **Task 30 (port laporan/export, commit 6160b3b)**
  SELESAI. Task 30 deviasi dari spec plan (17 file -> 2 file gabungan
  excel.php/pdf.php): dipertahankan 12 file terpisah
  (`app/_keuangan/kasir/export/export_*.php` + `generate_excel.php`) karena
  logic tiap laporan beda signifikan. Sebelum commit ditemukan & difix
  kredensial DB hardcode (`fitmotor_LOGIN`/`Sayalupa12` + host/dbname literal)
  nempel langsung di tiap `new PDO(...)` meski file sudah require
  `koneksi_kasir.php` (itu cuma expose RBAC + `$koneksi` mysqli, bukan PDO) -
  diganti `getenv('DB_HOST'/'DB_USER'/'DB_PASS'/'DB_NAME')` pola sama seperti
  `app/koneksi.php`. Divalidasi: php -l lolos semua 12 file + smoke test PDO
  connect & query ke 10 tabel `*_closing_kasir` real (data ada, bukan tabel
  kosong). Sisa backlog: **Task 32** (migrasi file fisik uploads), **Task 33**
  (bersihkan file berbahaya webroot web_kasir lama, independen dari cutover),
  **Task 34** (retire masterkey.php setelah Task 21), closing
  (`close_transaksi1.php` 141KB) & closing revisi belum disentuh. Cutover
  (Task 17) & drop tabel mati (Task 18) tetap WAJIB konfirmasi eksplisit.
- **Update 2026-09-05**: **Task 32 SELESAI-DENGAN-TEMUAN (no-op)** — dicek
  source (`fitmotor_maintance-beta.pengambilan_setoran`) & migrasi
  (`pengambilan_setoran_closing_kasir`), sama-sama 6 baris semua
  `mutasi_dokumen_path` NULL, nol referensi file. 12 file fisik di
  `web_kasir/uploads/pelunasan_hutang/` = orphan murni, gak dipindah
  (push back dari spec, gak sesuai kondisi nyata). **Task 33 dikonfirmasi
  ulang SELESAI** (sudah dieksekusi sesi sebelumnya, diverifikasi lagi:
  webroot bersih, repo `web_kasir` 0 commit history jadi Step 4 gak
  relevan). **Task 15 (wire menu) SELESAI, commit 46a693f/6a5d58b/
  cc1859d/8671064**: grup menu "Keuangan Kasir" (19 item) di
  `app/menu_config.php`. Investigasi Task 15 nemu 3 gap tersembunyi yang
  ikut difix: (1) `index_kasir.php` (Dashboard Kasir, 811 baris) BELUM
  PERNAH diport sejak awal migrasi — diport baru sekarang, table rename
  + `users`→`tbuser`; (2) `setoran_keuangan.php` fetch() 3 tombol
  (pelunasan manual/setor bank/edit nominal) masih ke nama file API lama
  yang udah digabung Task 14 (`pengambilan_setoran.php` dispatch action/
  `setoran_bank.php`) — 404 kalau diklik, direroute; (3) `closing.php`
  fetch backup ke `api_backup_closing.php` (trigger .bat Windows
  Access-era, gak applicable MySQL) — step backup dicopot dari JS
  (keputusan Rafi), backup DB sekarang lewat dump terjadwal. Bonus fix:
  bug null-reference JS `checkFormValidity()` di `serah_terima.php`
  (ketahuan live smoke test). Smoke test browser (login admin real):
  19/19 halaman lolos, console bersih. Sisa gap dicatat (bukan
  diblokir): 7 link "edit mode" di Dashboard Kasir (edit_kas_awal.php,
  edit_kas_akhir.php, input_penjualan_servis.php, edit_pemasukan1.php,
  edit_pengeluaran1.php, edit_omset1.php, cek_data.php) belum pernah
  diport, ditandai TODO di kode — backlog terpisah dari Task 15.
- **Update 2026-09-05 lanjutan**: 7 link "edit mode" di atas **SELESAI
  DIPORT semua, commit 9e3bd72/5538a6e/44eadc2**. Chain ternyata lebih
  besar dari perkiraan — `edit_pemasukan1.php`/`edit_pengeluaran1.php`
  masing-masing punya 2 dependency lagi (`edit_pemasukan.php`/
  `hapus_pemasukan1.php`, sama pola buat pengeluaran) = total 11 file
  baru, bukan 7. Ketemu+fix gap tersembunyi lagi: **Task 23** (DDL+migrasi
  `data_penjualan`/`data_servis`) ternyata belum pernah dieksekusi sama
  sekali sejak awal migrasi — `input_penjualan_servis.php` fatal error
  tabel gak ada pas smoke test. DDL 2 tabel `*_closing_kasir` + migrasi
  2175 baris masing-masing dari `fitmotor_maintance-beta`, 100% sukses
  (0 gagal, 0 orphan `kode_karyawan`). Semua 11 file pola sama: RBAC
  `koneksi_kasir.php`, PDO `getenv()`, table rename sed map, `users`→
  `tbuser`. Smoke test browser 11/11 halaman render benar (title +
  data live TRX-20241101-0001; 2 file "edit by id" dgn id bukan milik
  user login nampilin pesan "tidak ditemukan" sesuai desain, bukan
  fatal). **Task 15 + Task 23 + Task 24 semua SELESAI TUNTAS.**
- **UAT E2E penuh 2026-09-05 (browser + CLI-simulasi POST)**: alur
  lengkap divalidasi sampai closing — Verifikasi Kas Awal → Kas Awal
  (Rp310rb) → Pemasukan (Rp50rb) → Pengeluaran (Rp20rb) → Omset
  (penjualan Rp150rb+servis Rp75rb) → Kas Akhir (Rp535rb) → Closing
  (status jadi 'end proses', omset/setoran/selisih semua kehitung
  benar) → muncul benar di Serah Terima. Browser native (dialog
  `confirm()` bikin CDP freeze 30 detik+, harus `window.confirm=()=>true`
  patch dulu tiap page load) DAN simulasi PHP-CLI POST langsung
  (lebih reliable, dipakai buat isolasi bug) sama-sama dipakai. Ketemu+
  fix **3 bug fatal baru** yang gak kelihatan dari smoke-test biasa:
  (1) `verifikasi_kas_awal.php` KELEWAT TOTAL dari Task 11 — flow
  "Mulai Kas Awal Baru" infinite-loop, gak pernah bisa bikin transaksi
  sama sekali (link ke `kas_awal.php` langsung, skip step session-set);
  (2) kolom `kode_transaksi` varchar(20) di 9 tabel overflow buat akun
  fitmotor-native `nama_user`>3 huruf (`admin`/`adm01`/`keu01`) — widen
  ke varchar(50), dikonfirmasi eksplisit Rafi; (3) **paling parah**:
  `process_closing_transaction.php` (required `pemasukan.php`) punya
  guard top-level cek `$_SESSION['kode_karyawan']`/`['role']` (key
  legacy web_kasir yang fitmotor gak pernah set) — bikin **pemasukan.php
  TOTAL GAK BISA DIPAKAI SATU USER PUN sejak Task 14b diport**, exit
  diam-diam tanpa pesan error apapun. Ketahuan cuma karena testing pakai
  PHP-CLI session bersih (browser session kebetulan kebawa sisa login
  test `web_kasir.test` yang nyamarin bug ini). Juga ketemu+fix: sed
  table-rename Task 11 kena nama FILE (`serah_terima_kasir.php`→
  `serah_terima_kasir_closing_kasir.php`, 404) di 7 file, dan 23 link
  export tanpa prefix folder `export/` (Task 30 mindahin file tapi
  caller-nya kelewat) di 6 file. Semua commit terpisah, lint bersih.
- **Update 2026-09-05 sore — verifikasi 7 link edit-mode + checklist-projek**:
  dicek ulang 11 file edit-mode (edit_kas_awal, edit_kas_akhir,
  input_penjualan_servis, edit_pemasukan1/edit_pemasukan/hapus_pemasukan1,
  edit_pengeluaran1/edit_pengeluaran/hapus_pengeluaran1, edit_omset1,
  cek_data) — semua ADA, lint bersih, link di `index_kasir.php` bener
  (bukan 404). Komentar TODO basi di `index_kasir.php` (nyebut "belum
  diport") dibersihin. **checklist-projek diupdate 8 fitur**: 7 fitur
  yang dieksekusi langsung di UAT E2E (Kas Awal, Kas Akhir, Pemasukan,
  Pengeluaran, Closing, Serah Terima, Input Omset Penjualan/Servis)
  naik ke hijau/selesai/100%; 3 fitur "Edit Data ... (mode revisi)"
  naik dari merah/0% ke kuning/uat/60% (file diport+lint bersih, belum
  diklik-UAT browser). Sisa backlog gak berubah: cabang-resolution
  inconsistency (koneksi_kasir.php vs pemasukan.php) masih nunggu
  keputusan Rafi; Task 17/18 tetap WAJIB konfirmasi eksplisit; Task 34
  tetap blocked Task 21.
