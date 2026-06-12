# Audit Keamanan Tahap 1

## Status

Dokumen ini mencatat hardening keamanan yang sudah dieksekusi langsung di codebase PHP native, plus daftar risiko yang masih perlu dilanjutkan pada tahap berikutnya.

## Perbaikan Yang Sudah Diterapkan

### 1. Proteksi SQL Injection dan validasi input pada flow servis

File yang sudah diamankan:

- `_admincab/save-no-servis-reguler.php`
- `_admincab/save-no-servis-reguler-jemput.php`
- `_admincab/ajax-save-proses-tracking.php`
- `_admincab/ajax-preview-proses.php`
- `_admincab/ajax-get-detail-proses.php`

Perbaikan yang diterapkan:

- validasi session login
- pembatasan method request
- casting dan sanitasi input numerik/string
- prepared statement untuk query yang menerima input user
- guard untuk pembuatan servis ganda aktif
- error handling lebih aman agar detail SQL tidak bocor ke browser

### 2. Proteksi SQL Injection pada endpoint CRUD akun

File yang sudah diamankan:

- `_admincab/akun_kas_del.php`
- `_admincab/akun_biaya_del.php`
- `_admincab/akun_kel_biaya_del.php`
- `_admincab/akun_kas_edit_proses.php`
- `_admincab/akun_biaya_edit_proses.php`
- `_admincab/akun_kel_biaya_edit_proses.php`

Perbaikan yang diterapkan:

- validasi session
- validasi id dan field wajib
- prepared statement untuk update/delete
- pengurangan kemungkinan manipulasi parameter langsung dari URL

### 3. Hardening login dan ganti password

File yang sudah diamankan:

- `cek_login.php`
- `_admincab/change_pwd.php`
- `_admincab/change_pwd_proses.php`

Perbaikan yang diterapkan:

- login dipindah ke prepared statement agar tidak rawan SQL injection
- `session_regenerate_id(true)` setelah login berhasil untuk mengurangi risiko session fixation
- form ganti password tidak lagi menampilkan password lama di layar
- proses ganti password sekarang memverifikasi password saat ini, konfirmasi password baru, panjang minimal, dan kecocokan user session
- update password memakai prepared statement

## Temuan Risiko Yang Masih Ada

### 1. Password masih disimpan plaintext

Status:

- login dan perubahan password sekarang lebih aman dari sisi query dan session
- tetapi isi kolom `tbuser.password` masih diperlakukan sebagai plaintext oleh sistem lama

Dampak:

- jika database bocor, password user langsung terbaca
- belum ada `password_hash()` / `password_verify()` sehingga standar keamanan modern belum tercapai

Rekomendasi:

- migrasi bertahap ke hash password kompatibel backward
- sediakan fallback verifikasi untuk akun lama lalu rehash saat login sukses

### 2. Banyak endpoint legacy masih memakai query interpolasi langsung

Cluster yang masih berisiko tinggi dan perlu diprioritaskan berikutnya:

- `_admincab/change_pwd_proses.php` sebelumnya sudah rawan dan kini sudah dipatch, tetapi pola yang sama masih tersebar
- `_admincab/user_edit_proses.php`
- `_admincab/user_management.php`
- `_admincab/_handler_status_keluhan_wo.php`
- `_admincab/_handler_temuan_penawaran.php`
- `_admincab/ajax-hapus-keluhan-workorder.php`
- `_admincab/ajax_update_item_diskon.php`
- `_admincab/cari_kab.php`
- `_admincab/cari_kec.php`
- `_admincab/cari_kel.php`
- berbagai file `*_del.php`, `*_edit_proses.php`, dan handler AJAX lain di `_admincab`

Risiko utama:

- SQL injection
- manipulasi data via parameter GET/POST
- destructive action masih sering memakai method GET

### 3. Belum ada proteksi CSRF yang konsisten

Status:

- banyak form proses dan endpoint delete/update belum memakai token CSRF

Dampak:

- user yang sedang login masih bisa dipancing menjalankan request berbahaya dari situs lain

Rekomendasi:

- buat helper token CSRF terpusat
- terapkan minimal pada create, update, delete, approval, dan reset password

### 4. Informasi sensitif masih tersimpan di file config

Contoh temuan:

- ada kredensial database/API yang tertulis langsung di beberapa file area `login_dashboard`

Rekomendasi:

- pindahkan ke environment variable atau file config yang tidak ikut tersalin ke repo publik
- audit ulang seluruh file backup dan file percobaan

## Prioritas Tahap Berikutnya

1. Hardening cluster user management dan reset password.
2. Hardening seluruh endpoint delete/update berbasis GET di `_admincab`.
3. Tambahkan CSRF token helper dan rollout ke form proses utama.
4. Migrasi password plaintext ke hash modern secara kompatibel.
5. Audit folder role lain selain `_admincab`: `_kasir`, `_pengadaan`, `_admin`, `_hrd`, `_managemen`.

## Catatan Validasi

Validasi yang sudah dilakukan setelah patch tahap ini:

- `php -l` pada file-file yang dipatch: lolos
- smoke test browser pada flow servis utama: lolos
- halaman ganti password tetap bisa dibuka setelah perubahan form

Catatan:

- audit ini belum berarti seluruh project sudah aman
- tahap ini fokus pada celah paling kritis yang aktif dipakai dan paling mudah dieksploitasi
