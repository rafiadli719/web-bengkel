# QA Temuan Browser — WEB BENGKEL FIT MOTOR

**Tanggal:** 2026-08-09
**Tester:** QA Agent (otomatis, browser Chromium headless + verifikasi query DB langsung)
**Scope:** SELURUH halaman di `app/menu_config.php` — 127 entri menu (Master → Transaksi/Servis → Pengadaan → Penjualan → Antar Cabang → Stok → Laporan)
**Environment:** Laragon lokal, `http://localhost/web-bengkel/aplikasi/aplikasi/` (dari WSL: `http://172.22.0.1/...`), DB `fitmotor_dbbengkel`, login `admin`, cabang **PST (FIT MOTOR PUSAT)**

## Metodologi

1. Daftar halaman diambil langsung dari `app/menu_config.php` (rekursif sampai sub-submenu) — 127 URL, bukan tebakan.
2. Tiap halaman dibuka di browser sungguhan (Chromium, viewport 1440x900): cek HTTP status, console error, network error (404/500), waktu render, jumlah baris tabel, dan horizontal overflow (layout pecah).
3. Silang-cek dengan **PHP error log** (`C:\laragon\tmp\php_errors.log`) — ini kunci, karena `config/koneksi.php` menyetel `display_errors=0` + `error_reporting(E_ERROR|E_PARSE)`, jadi **error fatal TIDAK terlihat di layar**, halaman cuma "terpotong" diam-diam.
4. Tiap dugaan bug SQL diverifikasi dengan menjalankan query-nya langsung ke MySQL (bukan cuma nebak dari tampilan).
5. **Tidak ada tombol destruktif yang diklik**, tidak ada data yang dibuat/diubah/dihapus, tidak ada dialog native yang di-trigger. Semua halaman diakses read-only (GET).

**Tidak ada satu baris kode pun yang diubah** dalam sesi QA ini. Semua temuan di bawah untuk direview dulu.

## Ringkasan

| Status | Jumlah |
|---|---|
| OK | 109 |
| Kritis | 11 |
| Sedang | 3 (+3 catatan alur) |
| Ringan | 4 |
| **Total halaman dicek** | **127** |

Catatan: 109 halaman "OK" berarti ke-render penuh, tanpa error PHP/console/network, tanpa layout pecah. **Horizontal overflow = 0 di SEMUA halaman** — tidak ada layout yang pecah/berantakan di resolusi desktop.

---

## A. TEMUAN KRITIS

### A-1. Sembilan halaman mati diam-diam (Fatal error PHP tidak terlihat user)

**Ini pola bug yang sama dan paling berbahaya.** Query SQL gagal → `mysqli_query()` balik `false` → dilempar ke `mysqli_fetch_array()`/`mysqli_num_rows()` → **PHP Fatal error**. Karena `display_errors=0`, user **tidak melihat pesan error apa pun** — halaman cuma berhenti di tengah: header + filter tampil, tabel data kosong, footer hilang. User akan menyimpulkan "datanya memang kosong", padahal halamannya rusak.

Bukti visual: `output/playwright/qa-2026-08-09/laporan_hutang_summary.php.png` — kotak laporan kosong melompong tanpa pesan error apa pun.

| # | Halaman | Menu Path | File:Baris | Akar Masalah (sudah diverifikasi ke DB) |
|---|---|---|---|---|
| 1 | Hutang per Supplier | Laporan > Hutang > Hutang per Supplier | `app/laporan_hutang_summary.php:251` | `GROUP BY ph.no_supplier` tapi SELECT ikut `s.namasupplier`, `s.alamatsupplier`, dll → ditolak `sql_mode=ONLY_FULL_GROUP_BY` |
| 2 | Detail Hutang | Laporan > Hutang > Detail Hutang | `app/laporan_hutang_detail.php:246` | Kolom `s.alamatsupplier` & `s.tlpsupplier` **tidak ada**. Nama asli di `tblsupplier`: `alamat`, `telephone` |
| 3 | Piutang per Pelanggan | Laporan > Piutang > Piutang per Pelanggan | `app/laporan_piutang_summary.php:251` | Sama seperti #1 — `ONLY_FULL_GROUP_BY` |
| 4 | Detail Piutang | Laporan > Piutang > Detail Piutang | `app/laporan_piutang_detail.php:244` | Kolom `p.alamatpelanggan` & `p.tlppelanggan` **tidak ada**. Nama asli di `tblpelanggan`: `alamat`, `telephone` |
| 5 | Laporan Pengiriman Antar Cabang | Laporan > Antar Cabang > Pengiriman | `app/lap_antarcab_kirim.php:189` | `tblorderjual_header` **tidak punya** kolom `total_order`, `tipe_transaksi`, `kd_cabang_tujuan`. Yang ada: `total_akhir`, `tipe_trx` |
| 6 | Laporan Penerimaan Antar Cabang | Laporan > Antar Cabang > Penerimaan | `app/lap_antarcab_terima.php:150` | `tbcabang` **tidak punya** kolom `status` (query pakai `AND status='1'`) |
| 7 | Input Manual Cabang Mitra | Antar Cabang > Cabang Mitra (Eksternal) > Input Manual | `app/penjualan_mitra_add.php:288` | Sama seperti #6 — `tbcabang.status` tidak ada. Dropdown cabang tujuan gagal, form tidak bisa dipakai sama sekali |
| 8 | Setting Harga Antar Cabang | Data Master > Cabang > Setting Harga Antar Cabang | `app/setting_antarcabang.php:375` | Tabel **`tbl_setting_antarcabang` tidak ada** di database |
| 9 | Lihat Data Servis Garansi | Servis > Servis Garansi > Lihat Data | `app/servis-reguler.php:302` (via `?filter=garansi`) | `tblservice` **tidak punya** kolom `tipe_service`. Kolom yang benar: **`is_garansi`** |

**Langkah reproduksi (berlaku untuk semuanya):** login sebagai admin cabang PST → buka menu di kolom "Menu Path" → halaman tampil tapi tabel data kosong tanpa pesan error → cek `C:\laragon\tmp\php_errors.log`, ada baris `PHP Fatal error: Uncaught TypeError: mysqli_fetch_array()/mysqli_num_rows(): Argument #1 ($result) must be of type mysqli_result, false given`.

**Rekomendasi (JANGAN dieksekusi sebelum direview):**
- #1 & #3: tambahkan kolom non-agregat ke `GROUP BY`, atau bungkus dengan `MAX()/ANY_VALUE()`. Jangan diselesaikan dengan mematikan `ONLY_FULL_GROUP_BY` di server — hosting produksi bisa beda konfigurasi dan bug ini akan muncul lagi.
- #2 & #4: ganti nama kolom ke `alamat` / `telephone`.
- #5: petakan ulang ke kolom asli (`total_akhir`, `tipe_trx`), dan cek dulu apakah konsep "cabang tujuan" memang belum ada di skema — ini bisa jadi fitur yang memang belum jadi, bukan sekadar salah ketik.
- #6 & #7: hapus filter `status='1'` atau tambahkan kolom `status` ke `tbcabang` (keputusan bisnis: perlu tidak konsep cabang aktif/nonaktif?).
- #8: perlu keputusan — apakah fitur Setting Harga Antar Cabang memang belum di-migrasi (tabelnya belum dibuat), atau tabelnya hilang. **Jangan langsung bikin tabel** sebelum dikonfirmasi.
- #9: ganti `s.tipe_service LIKE '%garansi%'` → `s.is_garansi = 1` (sesuaikan dengan nilai aslinya). Lihat juga temuan B-1 di bawah, satu paket.

### A-2. Statistik Pelanggan menghasilkan halaman 190 MB

- **Halaman:** Statistik Pelanggan (`statistik-pelanggan.php` → redirect ke `statistik_pelanggan_dashboard.php`)
- **Menu Path:** Data Master > Pelanggan > Statistik Pelanggan
- **Status:** Kritis
- **Temuan:** Satu request menghasilkan **190.414.695 byte (≈190 MB) HTML** dengan **74.219 baris tabel**, waktu server 4,8 detik. Halaman ini tidak dibuka di browser saat pengujian karena hampir pasti membuat tab browser hang/kehabisan memori.
- **Reproduksi:** login → Data Master > Pelanggan > Statistik Pelanggan.
- **Rekomendasi:** wajib pagination / server-side DataTables / limit + filter periode. Kalau dipakai bersamaan oleh beberapa user di hosting produksi, ini berisiko menghabiskan memori & bandwidth server.

### A-3. Master Karyawan tidak bisa menampilkan data sama sekali (AJAX 500)

- **Halaman:** Master Karyawan (`master_karyawan.php`)
- **Menu Path:** Data Master > Master Karyawan
- **Status:** Kritis
- **Temuan:** Halaman menampilkan banner merah `Server error (500). Check console for details.` dan tabel Daftar Karyawan kosong. Endpoint `app/master_karyawan_ajax.php` balas HTTP 500 dengan body: `{"success":false,"message":"Query error: Table 'fitmotor_dbbengkel.karyawan' doesn't exist"}`.
- **Akar masalah:** query menyebut tabel `karyawan`; nama tabel sebenarnya di DB adalah **`tbuser_karyawan`**.
- **Reproduksi:** login → Data Master > Master Karyawan.
- **Bukti:** `output/playwright/qa-2026-08-09/master_karyawan.php.png`
- **Rekomendasi:** perbaiki nama tabel di `master_karyawan_ajax.php`. Perlu dicek juga apakah aksi `delete`/`save` di file yang sama menyebut tabel yang salah — kalau iya, tombol Hapus/Simpan juga akan gagal.

---

## B. TEMUAN SEDANG

### B-1. Tombol Edit di daftar servis salah arah karena kolom yang dibaca tidak ada

- **File:** `app/servis-reguler.php:401-403`
- **Temuan:** kode memeriksa `strpos(strtolower($tampil['tipe_service'] ?? ''), 'garansi')` untuk memilih URL edit (`servis-garansi.php` vs form reguler). Kolom `tipe_service` **tidak ada** di `tblservice`, jadi kondisi ini **selalu false** → servis garansi akan diarahkan ke form servis reguler.
- **Status:** Sedang (alur salah, bukan crash). Satu akar masalah dengan A-1 #9.
- **Rekomendasi:** ganti ke kolom `is_garansi`. **Tidak diperbaiki sendiri** karena menyangkut alur bisnis servis garansi.

### B-2. Menu "Daftar PO" tidak membuka daftar PO

- **File:** `app/pesanan_pembelian.php:7` — isinya cuma `header("Location: pembelian_dari_po.php");`
- **Menu Path:** Pembelian > Pesanan Pembelian (PO) > Daftar PO
- **Temuan:** klik "Daftar PO" mendarat di halaman **"Pembelian (Invoice) > Dari PO"** — halaman pembuatan invoice, bukan daftar PO. Halaman tujuannya sendiri normal (HTTP 200), tapi label menu tidak sesuai isi.
- **Rekomendasi:** konfirmasi ke user — apakah daftar PO memang sengaja digabung ke halaman itu (kalau ya, ganti label menu), atau redirect ini sisa refactor yang belum dibereskan.

### B-3. Menu "Master Kepala Mekanik" membuka form input harian

- **File:** `app/master_kepala_mekanik.php:2` — `header("Location: input_kepala_mekanik_harian.php");`
- **Menu Path:** Data Master > Mekanik > Master Kepala Mekanik
- **Temuan:** menu di grup "Data Master" justru membuka halaman transaksi harian (halaman yang sama dengan Servis > Servis Reguler > Input Kepala Mekanik Harian). Tidak ada halaman master kepala mekanik yang sebenarnya.
- **Rekomendasi:** konfirmasi ke user apakah master kepala mekanik memang tidak diperlukan (kalau ya, hapus/rename entri menu supaya tidak membingungkan).

### B-4. Tiga halaman lambat (>6 detik)

| Halaman | Menu Path | Waktu render |
|---|---|---|
| Input Servis Garansi (`servis-carinopol-garansi.php`) | Servis > Servis Garansi > Input Servis | **11,7 detik** |
| Master Barang (`barang.php`) | Data Master > Daftar Item > Master Barang | **7,6 detik** |
| Desa/Kelurahan (`desa.php`) | Data Master > Wilayah > Desa/Kelurahan | **6,5 detik** (payload 1,06 MB) |

- **Status:** Sedang. Halaman tetap benar, hanya lambat. Di hosting produksi (lebih lambat dari lokal) ini berpotensi timeout.
- **Rekomendasi:** cek query N+1 dan jumlah baris yang dikirim ke browser; `desa.php` mengirim seluruh data desa sekaligus — kandidat kuat untuk dropdown ber-AJAX/pagination.

---

## C. TEMUAN RINGAN

### C-1. Lima file asset hilang (404) di 4 halaman

File yang direferensikan tapi **tidak ada** di `app/assets/`:

| File hilang | Dipakai di |
|---|---|
| `assets/js/dataTables.colVis.min.js` | `paket.php` (Data Master > Daftar Item > Work Order/Paket) |
| `assets/js/dataTables.tableTools.min.js` | `paket.php` |
| `assets/css/dataTables.bootstrap.min.css` | `master-barang-custom.php`, `lap_rekap_kunjungan.php` |
| `assets/js/dataTables.bootstrap.min.js` | `lap_rekap_kunjungan.php` |
| `assets/css/datepicker.min.css` | `antarcab_list.php` (Antar Cabang > Daftar Transaksi) |

- **Dampak:** tabel kehilangan styling/fitur DataTables (kolom show-hide, export), datepicker tampil tanpa gaya. Halaman tetap berfungsi.
- **Rekomendasi:** tambahkan file yang hilang, atau hapus tag `<link>`/`<script>`-nya. Catatan: untuk datepicker sudah ada `assets/css/bootstrap-datepicker3.min.css` di folder yang sama — kemungkinan cuma salah nama file.

### C-2. Semua halaman punya `<title>` yang sama: "FIT MOTOR"

- 127 halaman semuanya bertitel `FIT MOTOR` (sumber: `lib/titel.php`). Akibatnya tab browser dan riwayat/bookmark tidak bisa dibedakan, dan cetak PDF dari browser semua bernama sama.
- **Rekomendasi:** tambahkan nama halaman ke title. Perubahan ini menyentuh banyak file, jadi **tidak dikerjakan** di sesi QA.

### C-3. Ketidakkonsistenan sapaan di navbar

- Mayoritas halaman: `Welcome, admin`. Halaman `master_karyawan.php`: `WELCOME, Administrator` (huruf besar + nama berbeda).
- Murni kosmetik, tapi **tidak diubah** karena menyangkut sumber data user yang berbeda antar-halaman (bisa jadi indikasi halaman itu pakai header versi lain), bukan sekadar typo.

### C-4. Ketergantungan CDN eksternal (Highcharts)

- Hampir semua halaman memuat `https://code.highcharts.com/highcharts.js` + modul `exporting`/`accessibility`/`export-data` dari internet.
- **Catatan penting:** di lingkungan pengujian ini akses internet diblokir, jadi 403 yang muncul **bukan bug aplikasi** dan tidak dihitung sebagai temuan. Yang perlu diperhatikan: kalau bengkel offline/internet mati, grafik di Dashboard dkk tidak akan tampil. Pertimbangkan hosting Highcharts secara lokal.

---

## D. Daftar Status Per Halaman

Semua halaman di bawah **OK** (render penuh, tanpa error PHP/console/network, tanpa layout pecah), kecuali yang ditandai.

### D-1. Dashboard & Umum (4 halaman — semua OK)
Dashboard (`index.php`), Lapor Masalah (`issue_add.php`), Approve Merge Pelanggan (`customer_merge_approve.php`), Deteksi Duplikat Pelanggan (`admin_deteksi_pelanggan_dobel.php`).

### D-2. Data Master (50 halaman — 46 OK, 2 Kritis, 2 Sedang, 2 Ringan)

**Daftar Item (19):** Master Barang *(Sedang — lambat 7,6s)*, Kategori Barang, Satuan Barang, Pabrik Barang, Rak Barang, Margin Harga Jual, Status Harga, Work Order/Paket *(Ringan — asset 404)*, WO-Jenis Motor Mapping, Jasa-Jenis Motor Mapping, Item-Jenis Motor Mapping, Master Keluhan, Keluhan-WO Mapping, Fast Moves Mapping, Master Temuan, Temuan-Part Mapping, Temuan-Jasa Mapping, Master Barang Custom *(Ringan — asset 404)*, Harga Jual Plus Jasa.

**Pelanggan (4):** Master Pelanggan, Kategori Pelanggan, Loyalty Member Program, **Statistik Pelanggan — KRITIS (A-2)**.

**Supplier (1):** OK.

**Cabang (3):** Master Cabang, Tipe Cabang, **Setting Harga Antar Cabang — KRITIS (A-1 #8)**.

**Mekanik (4):** Master Mekanik, Level Mekanik, Master Kepala Mekanik *(Sedang — B-3)*, Tarif Jemput Antar.

**Sales (1), Kendaraan (5), Wilayah (4):** semua OK, kecuali Desa/Kelurahan *(Sedang — lambat 6,5s)*.

**Lain-lain (9):** Akun Sumber Kas, Akun Biaya, Data User, Master Posisi, **Master Karyawan — KRITIS (A-3)**, Nominal Rupiah, Access Sync, Laporan Sync Access, Monitor Sync Otomatis.

### D-3. Pembelian / Pengadaan (16 halaman — 16 OK, 1 catatan alur)
Dashboard MIN/MAX, Rencana Order, Purchase Request (PR), Cek Stok Minimal (Auto-Draft PR), Daftar PO *(Sedang — B-2, redirect salah sasaran; halaman tujuan sendiri OK)*, PO Input Manual, PO Upload Excel, Master Approval Bertingkat, Delivery Order (DO), Daftar DO, Daftar Pembelian, Pembelian Dari PO, Pembelian Input Manual, Retur Pembelian, Pembayaran Hutang, Alarm Harga Beli.

### D-4. Penjualan (8 halaman — semua OK)
Daftar Pesanan, Pesanan Input Manual, Daftar Penjualan, Penjualan Dari Pesanan, Penjualan Input Manual, Retur Penjualan, Pembayaran Piutang, Input Pembayaran Piutang.

### D-5. Antar Cabang (9 halaman — 7 OK, 1 Kritis, 1 Ringan)
Buat Pesanan, Tarik Data (Kirim), Penerimaan, Mitra Upload Excel, **Mitra Input Manual — KRITIS (A-1 #7)**, Penerimaan Mitra, Daftar Transaksi *(Ringan — asset 404)*, Pengadaan Daftar Permintaan, Pengadaan Buat Permintaan.

### D-6. Servis (12 halaman — 10 OK, 1 Kritis, 1 Sedang)
Input Kepala Mekanik Harian, Kelola Antrian Service, Input Servis Reguler, Lihat Data Servis Reguler, Input Servis Garansi *(Sedang — lambat 11,7s)*, **Lihat Data Servis Garansi — KRITIS (A-1 #9)**, Jadwal Penjemputan, Tracking Keluhan, Master Promo/Diskon, Approval Diskon, Laporan DP Servis, Retur Servis.

### D-7. Penyesuaian Stok (5 halaman — semua OK)
Item Masuk Manual, Item Keluar Manual, Otomatis, Lihat Stok Akhir, Kartu Stok.

### D-8. Laporan (23 halaman — 16 OK, 6 Kritis, 1 Ringan)
Pesanan Pembelian, Pembelian, Retur Pembelian, Pembayaran Hutang, **Hutang per Supplier — KRITIS**, **Detail Hutang — KRITIS**, Pesanan Penjualan, Penjualan, Profit Penjualan, Retur Penjualan, Pembayaran Piutang, **Piutang per Pelanggan — KRITIS**, **Detail Piutang — KRITIS**, **Antar Cabang Pengiriman — KRITIS**, **Antar Cabang Penerimaan — KRITIS**, Service, Rekap Kunjungan Pelanggan *(Ringan — asset 404)*, Konsolidasi Access, Laporan Cancel Service, Kas Masuk, Pengeluaran Kas, Stok Masuk (Manual), Stok Keluar (Manual).

*(6 halaman kritis di grup Laporan = A-1 nomor 1-6.)*

---

## E. Yang BELUM Dites (perlu tindak lanjut manual)

Semua 127 halaman menu sudah dibuka dan diperiksa, tapi pengujian ini **read-only**. Yang belum tercakup:

1. **Submit form & alur transaksi end-to-end** — buat servis baru, bayar, PO → DO → Invoice, retur, approval diskon. Tidak dilakukan karena mengubah data. **Perlu tes manual dengan data dummy** di database test.
2. **Tombol destruktif** (Hapus, Batal Transaksi, Cancel Service) — sengaja tidak diklik.
3. **Validasi form** — tidak diisi/di-submit karena banyak form langsung menyimpan saat submit.
4. **Halaman non-menu** — ada 900+ file di `app/`, sementara menu hanya menunjuk 127. Sisanya (halaman edit, cetak nota, endpoint AJAX) belum disisir. Endpoint AJAX khususnya berisiko: A-3 membuktikan bug bisa bersembunyi di sana tanpa terlihat di halaman utama.
5. **Peran/role lain** — semua pengujian pakai `admin` (akses penuh) di cabang PST. Halaman bisa berperilaku beda untuk role cs/kasir/mekanik/pengadaan, dan untuk cabang selain PST.
6. **Tampilan mobile/tablet** — hanya diuji di 1440x900.
7. **Halaman Statistik Pelanggan** — tidak dibuka di browser sama sekali (risiko hang karena 190 MB); hanya diukur di level HTTP.

---

## F. Catatan Prioritas untuk Review

Urutan usulan penanganan (untuk dikonfirmasi user dulu):

1. **A-1 #1-#4 (laporan Hutang & Piutang)** — 4 laporan keuangan mati total. Dampak bisnis paling langsung.
2. **A-3 (Master Karyawan)** — 1 baris nama tabel, dampak besar, risiko rendah.
3. **A-1 #7 & #9** — Input Manual Cabang Mitra dan Lihat Data Servis Garansi memblokir alur kerja harian.
4. **A-2 (Statistik Pelanggan 190 MB)** — risiko stabilitas server di produksi.
5. **A-1 #5, #6, #8** — perlu keputusan skema/bisnis dulu, bukan sekadar perbaikan kode.
6. **B-2, B-3** — perlu konfirmasi maksud awalnya sebelum diubah.
7. **C-1 s/d C-4** — kosmetik, bisa dikerjakan belakangan.

Satu benang merah untuk semua temuan A-1: **`display_errors=0` membuat halaman rusak terlihat seperti halaman kosong yang normal.** Sembilan halaman rusak ini kemungkinan sudah lama begitu tanpa ada yang melapor, karena user mengira datanya memang kosong. Usulan yang layak dibahas terpisah: tampilkan pesan error yang ramah (bukan stack trace) saat query gagal, supaya kerusakan seperti ini ketahuan sejak awal.

---

**Lampiran screenshot:** `output/playwright/qa-2026-08-09/`
- `laporan_hutang_summary.php.png` — contoh halaman mati diam-diam
- `servis-reguler.php_filter_garansi.png` — Lihat Data Servis Garansi terpotong
- `lap_antarcab_terima.php.png` — Laporan Penerimaan Antar Cabang terpotong
- `master_karyawan.php.png` — banner Server error (500)
- `index.php.png` — Dashboard (kondisi normal, sebagai pembanding)
