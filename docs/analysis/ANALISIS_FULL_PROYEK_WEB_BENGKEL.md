# Analisis Full Proyek Web Bengkel

## Ringkasan

Proyek ini adalah aplikasi **monolitik PHP + MySQL** untuk operasional bengkel motor dengan cakupan yang sangat luas: master data, pembelian, penjualan, servis reguler dan garansi, antrian servis, stok, kas, laporan, HRD, dan dashboard otoritas user. Basis utamanya masih berupa file PHP prosedural dengan query `mysqli` langsung ke database.

Secara evolusi, proyek terlihat berada di **fase transisi**:

- Sistem lama masih tersebar ke banyak folder role seperti `_admin`, `_kasir`, `_pengadaan`, `_managemen`, `_hrd`
- Sistem baru mulai dipusatkan ke `_admincab`
- Login terbaru mengarahkan user ke `_admincab/index.php`
- Sidebar di `_admincab` sudah memakai **RBAC dinamis berbasis database**

Analisis ini dibuat dari:

- pembacaan struktur file dan folder proyek
- pembacaan file inti login, koneksi, RBAC, menu, dan halaman representatif
- verifikasi browser terhadap `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/index.php`

Hasil verifikasi browser:

- akses `_admincab/index.php` tanpa login diarahkan ke `login.php`
- proteksi session aktif
- halaman login berisi field `Username / Karyawan`, `Password`, dan `Cabang`

## Skala Proyek

Jumlah file PHP per area utama:

- `_admin`: 698 file
- `_admincab`: 1660 file
- `_booking`: 1 file
- `_hrd`: 549 file
- `_kasir`: 932 file
- `_managemen`: 440 file
- `_pengadaan`: 1114 file
- `login_dashboard`: 31 file

Makna praktisnya:

- proyek ini besar dan sudah lama berkembang
- ada banyak duplikasi pola antar folder
- `_admincab` adalah area paling aktif dan paling penting untuk dipahami terlebih dahulu

## Arsitektur Teknis

### Stack

- Backend: PHP native/prosedural
- Database: MySQL/MariaDB
- Koneksi DB: `mysqli`
- Session/Auth: session PHP manual
- PDF export: `dompdf/dompdf`
- Excel: `PHPExcel` dan file import/export Excel lama
- UI template: dominan Ace Admin / Bootstrap
- Frontend library yang terlihat dipakai:
  - Bootstrap
  - jQuery
  - FullCalendar
  - Highcharts
  - Chart.js
  - Select2
  - DataTables
  - jqGrid

### Entry Point Utama

- `index.php` langsung redirect ke `login.php`
- `login.php` adalah halaman login modern
- `cek_login.php` memproses autentikasi

### Koneksi Database

Konfigurasi inti terlihat di:

- `config/koneksi.php`
- `config/config.php`

Database utama yang aktif di aplikasi inti:

- host: `localhost`
- user: `fitmotor_LOGIN`
- database: `fitmotor_dbbengkel`

Ada juga area lain yang memakai database berbeda, terutama di `login_dashboard`, misalnya:

- `fitmotor_maintance-beta`
- `fitmotor_prototype`

Ini menandakan ada bagian sistem yang mulai mengarah ke **multi-database integration**.

## Alur Login dan Session

Login diproses di `cek_login.php` dengan alur:

1. validasi `txtnama`, `txtpass`, `cbocabang`
2. cek user pada tabel `tbuser`
3. set session:
   - `$_SESSION['_iduser']`
   - `$_SESSION['_cabang']`
   - `$_SESSION['user_akses']`
4. untuk akses level tertentu, cabang wajib dipilih
5. setelah login sukses, user diarahkan ke `_admincab/index.php`

Catatan penting:

- password masih dicek langsung terhadap nilai plaintext di query login
- redirect di `cek_login.php` memakai base URL hardcoded `https://fitmotor.web.id/beta/aplikasi/`
- untuk localhost, ini adalah potensi mismatch lingkungan

## Sistem Role dan Permission

Ada **lebih dari satu lapisan RBAC** di proyek ini:

### 1. RBAC statis berbasis level akses

File:

- `config/rbac.php`
- `config/session_check.php`
- `config/permission_check.php`

Sistem ini memetakan `user_akses` ke nama role dan permission statis.

### 2. RBAC dinamis berbasis database

File:

- `_admincab/_include_menu_rbac.php`
- `_admincab/menu_dashboard.php`
- `_admincab/menu_config.php`
- `lib/rbac.php`
- `_admincab/master-posisi.php`

Sistem ini lebih modern:

- permission disimpan di tabel `tb_master_posisi`
- field `permissions` berbentuk JSON
- user sidebar dirender berdasarkan permission itu
- `master-posisi.php` dipakai untuk mengelola role/posisi dan permission tree

### Temuan penting

Proyek sedang berada di masa transisi dari:

- menu statis per role

menjadi:

- satu dashboard pusat dengan menu dinamis berbasis permission

Ini sangat penting karena menjelaskan kenapa banyak file `menu_xxx.php` lama masih ada, tapi `_admincab` sudah memakai konfigurasi menu baru.

## Struktur Modul Bisnis

## 1. `_admincab` sebagai pusat aplikasi baru

`_admincab` adalah area paling strategis. Dari `menu_config.php`, fitur utamanya adalah:

- Dashboard
- Data Master
- Pembelian
- Penjualan
- Antar Cabang
- Servis
- Penyesuaian Stok
- Laporan

### Data Master

Submodul yang terdeteksi:

- Master Barang
- Kategori Barang
- Satuan Barang
- Pabrik Barang
- Rak Barang
- Margin Harga Jual
- Status Harga
- Work Order/Paket
- Master Keluhan
- Keluhan - WO Mapping
- Fast Moves Mapping
- Master Temuan
- Temuan - Part Mapping
- Master Barang Custom
- Harga Jual Plus Jasa
- Master Pelanggan
- Kategori Pelanggan
- Statistik Pelanggan
- Supplier
- Cabang dan tipe cabang
- Mekanik dan level mekanik
- Kepala mekanik
- Tarif jemput antar
- Sales
- Master kendaraan, tipe motor, pabrik motor, kategori motor, warna
- Wilayah administrasi
- Akun sumber kas
- Akun biaya
- User
- Master posisi
- Master karyawan
- Nominal rupiah

### Pembelian

Submodul:

- Pesanan pembelian
- Pembelian
- Pembayaran hutang

### Penjualan

Submodul:

- Pesanan penjualan
- Penjualan
- Pembayaran piutang

### Antar Cabang

Submodul:

- Buat pesanan antar cabang
- Tarik data penjualan
- Penerimaan antar cabang

### Servis

Submodul:

- Servis reguler
- Servis garansi
- Servis jemput antar
- Input kepala mekanik harian
- Kelola antrian service
- Input servis
- Lihat data servis
- Tracking keluhan

### Penyesuaian Stok

Submodul:

- Item masuk manual
- Item keluar manual
- Stok otomatis
- Lihat stok akhir

### Laporan

Submodul:

- Pesanan pembelian
- Pembelian
- Pembayaran hutang
- Pesanan penjualan
- Penjualan
- Pembayaran piutang
- Service
- Laporan cancel service
- Kas masuk
- Pengeluaran kas
- Stok masuk manual
- Stok keluar manual

## 2. `_kasir`

Folder ini masih sangat aktif dan berisi workflow transaksi klasik:

- penjualan
- pembelian
- pembayaran hutang/piutang
- kas masuk/keluar
- servis
- laporan cetak/pdf/xls

Polanya file-nya sangat transaksional:

- `save_*`
- `lap_*`
- `cari_*`
- `function_*`
- `pembelian_*`
- `penjualan_*`
- `pmby_*`
- `servis_*`

Kemungkinan besar ini adalah sistem lama yang masih dipakai sebagian.

## 3. `_pengadaan`

Fokus utamanya:

- pembelian
- stok
- supplier
- item
- servis pendukung sparepart
- penyesuaian stok

Secara struktur sangat mirip `_kasir`, tetapi orientasinya lebih kuat ke inventori dan pengadaan.

## 4. `_admin`

Berisi master data klasik dan banyak halaman lama:

- master barang
- kendaraan
- cabang
- mekanik
- wilayah
- paket/work order

Kemungkinan besar ini cikal bakal area administratif sebelum digabung ke `_admincab`.

## 5. `_managemen`

Fokus ke:

- dashboard manajemen
- laporan-laporan utama
- monitoring periodik

Halaman `index.php` mengarah ke dashboard ringkasan dan filter bulanan/tahunan.

## 6. `_hrd`

Fokus ke:

- master pegawai
- jabatan
- pendidikan
- keluarga
- training
- gaji/tunjangan
- absensi
- upload absensi
- laporan absensi pdf/excel

Ini terlihat seperti sub-sistem HR terpisah tetapi masih satu monolit.

## 7. `_booking`

Saat ini sangat kecil, hanya terlihat `index.php`. Kemungkinan fitur booking belum berkembang besar atau sudah dipindahkan ke area servis.

## 8. `login_dashboard`

Area ini cukup menarik karena berfungsi sebagai dashboard pengaturan user/otoritas yang lebih modern.

Terdeteksi fitur:

- login terpisah
- pengelolaan sidebar
- pengaturan permission
- pengelolaan role
- multi-database employee/branch loading

Ada dokumentasi internal yang menyebut integrasi dua database untuk data karyawan dan cabang.

## Proses Bisnis Inti yang Terdeteksi

## 1. Alur servis reguler

Dari `servis-carinopol.php` dan file terkait, alurnya kurang lebih:

1. cari pelanggan/kendaraan berdasarkan nomor polisi, nama, atau telepon
2. tampilkan data kendaraan + pemilik + kategori member
3. lanjut ke input servis
4. servis disimpan ke `tblservice`
5. item jasa dan barang terkait disimpan ke tabel detail
6. status servis dipantau di halaman lihat data servis

Ciri modern di modul ini:

- ada highlight pelanggan berdasarkan kategori member
- ada integrasi statistik pelanggan
- ada permission check via `lib/rbac.php`

## 2. Alur keluhan -> work order -> temuan

Ini salah satu fitur paling khas dan bernilai tinggi di proyek:

- `master-keluhan-crud.php`
- `master-workorder-mapping.php`
- `master-temuan.php`
- file AJAX keluhan/workorder/temuan

Alurnya:

1. keluhan pelanggan didefinisikan di master keluhan
2. keluhan dapat diajukan cabang dan menunggu approval pusat
3. keluhan dipetakan ke work order default
4. saat servis berjalan, teknisi/operator bisa melacak keluhan
5. temuan teknis disimpan sebagai entitas tersendiri
6. temuan bisa dipetakan ke part yang disarankan
7. akhirnya sistem dapat mendukung penawaran part tambahan

Ini menunjukkan aplikasi tidak hanya mengelola transaksi, tetapi juga **diagnostic workflow bengkel**.

## 3. Alur antrian servis

Dari `kelola-antrian.php` dan `dashboard-antrian-servis.php`:

- ada tabel `tb_antrian_servis`
- status antrian:
  - `menunggu`
  - `diproses`
  - `selesai`
  - `batal`
- saat status berubah, `tblservice.status_servis` juga ikut diperbarui
- ada prioritas:
  - `normal`
  - `urgent`
  - `vip`
- ada pencatatan `jam_mulai`, `jam_selesai`, alasan batal, dan user pembatal

Ini berarti antrian adalah proses operasional nyata, bukan sekadar tampilan.

## 4. Alur customer intelligence / loyalty

Fitur ini cukup matang dan menarik:

- `master_kategori_member.php`
- `setting_diskon_member_item.php`
- `setting-highlight-member.php`
- `_include_statistik_pelanggan.php`
- `statistik_pelanggan_dashboard.php`
- `statistik_pelanggan_send_wa.php`

Kemampuan yang terdeteksi:

- kategori member berbasis nominal transaksi dan/atau jumlah kunjungan
- setting benefit dan diskon per kategori
- pengecualian item yang boleh/tidak boleh didiskon
- highlight visual pelanggan berdasarkan kategori
- dashboard statistik pelanggan
- indikasi follow-up WhatsApp customer

Ini membuat proyek punya unsur **CRM dan loyalty program**, bukan sekadar POS bengkel.

## 5. Alur work order / paket

Dari area `_pengadaan` dan file `paket_*`, `tbworkorderheader`, `tbworkorderdetail`:

- admin dapat membuat paket/work order
- paket dapat berisi jasa dan barang
- harga total dihitung dari detail
- ada relasi ke keluhan default

Kemungkinan dipakai untuk:

- paket servis berkala
- penanganan masalah umum
- template pengerjaan teknisi

## 6. Alur stok dan inventori

Terlihat dari:

- `barang_*`
- `stok_*`
- `penyesuaian-stok-*`
- `view_stok`
- kartu stok

Fungsinya:

- master item dan atributnya
- stok akhir
- penyesuaian manual
- penyesuaian otomatis
- riwayat mutasi stok

## 7. Alur keuangan dan laporan

Terlihat dari:

- `kas_masuk_*`
- `kas_keluar_*`
- `pmby_hutang_*`
- `pmby_piutang_*`
- `lap_*`

Output laporan tersedia dalam beberapa bentuk:

- tampilan web
- PDF
- Excel/XLS
- print

## Integrasi Eksternal

## Accurate Online

Ada integrasi ke Accurate di:

- `config/accurate_config.php`
- `cek_login.php`
- `oauth-callback.php`
- `login_accurate.php`
- file debug terkait Accurate

Fungsi yang terlihat:

- generate signature API
- ambil host/account Accurate
- test koneksi saat login
- simpan status koneksi Accurate ke session

Catatan:

- token API dan secret saat ini tertulis langsung di source code
- ini adalah risiko keamanan serius

## Multi-Database User Dashboard

Area `login_dashboard` punya integrasi multi database untuk mengambil:

- daftar karyawan
- daftar cabang

Sumber data disebut berasal dari:

- database kasir
- database absensi

## Struktur Data Inti yang Terlihat

Tabel penting yang berulang kali muncul:

- `tbuser`
- `tbcabang`
- `tblpelanggan`
- `tblkendaraan`
- `tblservice`
- `tblservis_jasa`
- `tblservis_barang`
- `tb_antrian_servis`
- `tb_progress_mekanik`
- `tbmaster_keluhan`
- `tbmaster_keluhan_workorder`
- `tbmaster_temuan`
- `tbservis_keluhan_status`
- `tbservis_temuan`
- `tbservis_workorder`
- `tbservis_penawaran_part`
- `tbworkorderheader`
- `tbworkorderdetail`
- `tblitem`
- `view_stok`
- `statistik_pelanggan`
- `master_kategori_member`
- `tb_master_posisi`
- `tb_master_level`
- `tb_user_activity_log`

Secara domain, tabel-tabel ini menunjukkan inti aplikasi terdiri dari:

- user dan cabang
- customer dan kendaraan
- service order
- keluhan, temuan, diagnosis, work order
- barang dan stok
- transaksi pembelian/penjualan/kas
- loyalty/CRM

## Pola Kode yang Dominan

Pola implementasi yang paling sering muncul:

- satu file = satu halaman + satu proses CRUD
- query SQL ditulis langsung di file halaman
- HTML, PHP, SQL, dan kadang JS bercampur dalam file yang sama
- redirect menggunakan `echo "<script>window.location=..."`
- banyak file `save_*`, `edit_*`, `del_*`, `rst_*`, `cari_*`
- banyak file backup/copy/original masih tersisa

Keuntungan pola ini:

- cepat dikembangkan
- mudah diubah langsung untuk kebutuhan bisnis

Kekurangannya:

- sulit dirawat dalam jangka panjang
- logika tersebar
- keamanan dan konsistensi sulit dijaga

## Temuan Teknis Penting

## Kekuatan sistem

- domain bisnis bengkel digarap cukup dalam
- modul servis tidak dangkal, sudah menyentuh workflow diagnosa
- ada langkah menuju RBAC dinamis
- ada fitur loyalty dan statistik pelanggan
- ada dukungan laporan operasional yang banyak
- ada integrasi eksternal Accurate

## Risiko / technical debt

### 1. Banyak sistem akses berjalan bersamaan

Ada:

- session check lama
- permission check lama
- RBAC statis
- RBAC dinamis database
- menu statis per role
- menu dinamis `_admincab`

Ini rawan inkonsistensi hak akses.

### 2. Password plaintext

Login masih mencocokkan password langsung di query. Ini menunjukkan belum menggunakan hashing yang aman.

### 3. Kredensial sensitif hardcoded

Yang terlihat hardcoded di source:

- user/password database
- token dan secret Accurate

### 4. SQL inline sangat dominan

Risiko:

- rawan bug
- sulit diuji
- rawan injection bila ada bagian yang lupa sanitasi

### 5. Duplikasi halaman dan folder legacy

Contoh sinyal:

- file `.bak`, `.backup`, `.ori`, `Copy`
- modul yang sama ada di `_admin`, `_kasir`, `_pengadaan`, `_admincab`

### 6. Redirect/environment hardcoded

`cek_login.php` mengandung base URL online, yang bisa mengganggu konsistensi local/staging/production.

### 7. Struktur UI belum sepenuhnya konsisten

Sebagian halaman baru memakai `lib/navbar.php` / `lib/sidebar.php`, sebagian masih menyusun layout manual, sebagian lagi pakai menu lama seperti `menu_servis01.php`.

## Kesimpulan Arsitektural

Kalau diringkas secara jujur, proyek ini adalah:

- **sistem bengkel yang kaya fitur**
- **masih monolitik dan file-based**
- **sedang bermigrasi dari arsitektur lama ke arsitektur yang lebih terpusat**

Pusat gravitasi aplikasi sekarang tampak berada di:

- login modern: `login.php`
- proses login: `cek_login.php`
- dashboard utama baru: `_admincab`
- permission baru: `tb_master_posisi` + `_admincab/menu_config.php`

Jadi jika tujuan Anda adalah memahami proyek untuk maintenance atau pengembangan lanjutan, titik mulai terbaik adalah:

1. `login.php`
2. `cek_login.php`
3. `_admincab/index.php`
4. `_admincab/menu_config.php`
5. `_admincab/_include_menu_rbac.php`
6. `lib/rbac.php`
7. modul servis di `_admincab`

## Rekomendasi Tahap Lanjut

Jika proyek ini ingin dirapikan bertahap tanpa mengganggu operasional, urutan yang paling aman:

1. satukan sistem auth dan permission
2. pindahkan kredensial sensitif ke env/config aman
3. hash password user
4. jadikan `_admincab` sebagai pusat modul aktif
5. tandai modul legacy yang hanya kompatibilitas
6. ekstrak query dan business logic inti servis ke helper/service layer
7. dokumentasikan alur tabel inti servis, stok, penjualan, dan pembelian

## Ringkasan Singkat Peran Sistem

Secara bisnis, aplikasi ini melayani:

- front office penerimaan servis
- kasir transaksi
- teknisi/mekanik
- kepala mekanik
- procurement/sparepart
- manajemen
- HRD
- admin pusat/cabang

Dengan kata lain, ini bukan hanya web kasir bengkel, tetapi **ERP mini khusus bengkel motor** dengan fitur servis, stok, CRM, dan otoritas user yang cukup luas.
