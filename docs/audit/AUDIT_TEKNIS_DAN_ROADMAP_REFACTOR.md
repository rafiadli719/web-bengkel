# Audit Teknis dan Roadmap Refactor

## Tujuan Dokumen

Dokumen ini merangkum temuan teknis lanjutan dari proyek web bengkel, fokus pada:

- risiko operasional
- technical debt
- area yang perlu ditangani lebih dulu
- roadmap refactor bertahap yang realistis

## Ringkasan Eksekutif

Secara bisnis, sistem ini kuat dan kaya fitur. Secara teknis, sistem ini menyimpan technical debt yang besar tetapi masih bisa ditangani bertahap tanpa memutus operasional.

Area paling kritis:

- keamanan auth dan kredensial
- duplikasi RBAC
- inkonsistensi menu dan routing
- query inline dan logika yang tersebar
- banyak halaman legacy yang masih hidup berdampingan dengan sistem baru

## Temuan Utama

## 1. Autentikasi masih memakai password plaintext

Bukti:

- `cek_login.php` mencocokkan `nama_user` dan `password` langsung di query
- `save_user.php` menyimpan password langsung ke tabel `tbuser`

Risiko:

- keamanan sangat rendah
- kebocoran database langsung membocorkan akun
- tidak memenuhi praktik keamanan modern

Prioritas:

- sangat tinggi

## 2. Kredensial sensitif hardcoded di source code

Bukti:

- `config/koneksi.php` berisi host/user/password/database
- `config/accurate_config.php` berisi token Accurate dan secret signature

Risiko:

- kebocoran repo atau backup = kebocoran akses produksi
- sulit membedakan environment local/staging/production

Prioritas:

- sangat tinggi

## 3. Ada beberapa sistem RBAC berjalan bersamaan

Bukti:

- `config/rbac.php`
- `config/session_check.php`
- `config/permission_check.php`
- `lib/rbac.php`
- `_admincab/_include_menu_rbac.php`
- menu statis `menu_servis01.php`, `menu_pelanggan01.php`, dll

Risiko:

- user bisa melihat halaman yang menu-nya disembunyikan atau sebaliknya
- permission sulit dipastikan konsisten
- maintenance makin berat

Prioritas:

- sangat tinggi

## 4. Sidebar/menu baru belum sinkron penuh dengan file riil

Bukti mismatch:

- `member-loyalty-program.php` tidak ada
- `statistik-pelanggan.php` tidak ada
- `mekanik.php` tidak ada
- `master_kepala_mekanik.php` tidak ada
- `lap_cancel_servis.php` tidak ada

Interpretasi:

- implementasi sebagian sudah ada dengan nama file lain
- tetapi menu dan routing belum dirapikan

Risiko:

- broken navigation
- kebingungan user dan developer

Prioritas:

- tinggi

## 5. Banyak halaman masih memakai menu lama di dalam `_admincab`

Contoh:

- `pelanggan.php` -> `menu_pelanggan01.php`
- `servis-reguler.php` -> `menu_servis01.php`
- `servis-garansi.php` -> `menu_servis03.php`
- `pembelian_dari_po.php` -> `menu_pembelian02.php`

Risiko:

- pengalaman navigasi tidak konsisten
- RBAC baru tidak sepenuhnya mengontrol seluruh UI

Prioritas:

- tinggi

## 6. Query SQL inline mendominasi seluruh aplikasi

Karakter yang terlihat:

- query tertulis langsung di file halaman
- HTML dan SQL bercampur
- sanitasi tidak konsisten
- prepared statement hanya muncul di sebagian kecil file

Risiko:

- rawan injection bila ada kelengahan
- sulit di-test
- sulit di-reuse
- sulit di-debug saat bug lintas modul

Prioritas:

- tinggi

## 7. Struktur halaman banyak yang merupakan wrapper atau legacy shell

Bukti:

- `pesanan_pembelian.php` redirect ke `pembelian_dari_po.php`
- `pesanan_penjualan_cab_add.php` redirect ke `penjualan_antarcab_upload.php`
- ada banyak file `.bak`, `.backup`, `.ori`, `Copy`

Risiko:

- developer baru sulit tahu halaman aktif yang sebenarnya
- rawan edit file yang salah

Prioritas:

- sedang ke tinggi

## 8. Struktur data berubah dan beberapa file dipatch defensif

Contoh jelas:

- `penjualan.php` memakai `SHOW COLUMNS FROM view_penjualan_header`
- lalu menyesuaikan query berdasarkan kolom yang tersedia

Interpretasi:

- struktur view atau tabel pernah berubah
- kode aplikasi berusaha bertahan terhadap variasi schema

Risiko:

- query menjadi makin kompleks
- bug schema bisa tersembunyi

Prioritas:

- sedang

## 9. Integrasi Accurate belum dipisah rapi

Bukti:

- tes integrasi Accurate dijalankan saat login
- status koneksi Accurate disimpan di session login

Risiko:

- login terikat ke dependensi eksternal
- jika API lambat atau error, pengalaman login terpengaruh

Prioritas:

- sedang

## 10. Inklusi layout dan komponen tidak seragam

Ada tiga pola yang bercampur:

- full manual navbar/sidebar di file halaman
- include `menu_xxx.php`
- include `lib/header.php` + `lib/sidebar.php`

Risiko:

- sulit standardisasi UI
- duplikasi markup tinggi

Prioritas:

- sedang

## Audit Per Area

## Auth dan Session

Status:

- bekerja secara operasional
- masih model session manual sederhana

Masalah:

- password plaintext
- redirect base URL hardcoded di `cek_login.php`
- banyak session key tidak distandardisasi

Rekomendasi:

- gunakan `password_hash` / `password_verify`
- buat adapter transisi untuk akun lama
- satukan penamaan session key
- pindahkan redirect base ke config environment

## RBAC

Status:

- arah baru sudah benar
- `tb_master_posisi` + `permissions JSON` cukup fleksibel

Masalah:

- masih coexist dengan sistem lama
- page-level protection belum konsisten
- beberapa permission check dinonaktifkan sementara

Rekomendasi:

- tetapkan `tb_master_posisi` sebagai source of truth tunggal
- ubah semua halaman `_admincab` ke `lib/rbac.php`
- audit page yang masih pakai menu lama

## Servis

Status:

- area paling matang secara bisnis

Kekuatan:

- ada antrian
- ada tracking mekanik
- ada keluhan
- ada temuan
- ada mapping work order
- ada statistik pelanggan

Masalah:

- kode tersebar di banyak file AJAX dan halaman
- belum ada service layer/domain layer

Rekomendasi:

- jadikan domain servis sebagai pilot refactor
- kelompokkan operasi:
  - service order
  - queue
  - complaint
  - finding
  - workorder
  - offering

## Transaksi Pembelian/Penjualan

Status:

- masih berjalan gaya klasik

Masalah:

- banyak halaman daftar masih berbasis view
- beberapa halaman default kosong dan bergantung flow tertentu
- wrapper redirect membuat entrypoint tidak jelas

Rekomendasi:

- dokumentasikan flow aktif per menu
- tandai file shell/wrapper
- standarkan halaman listing, filter, pagination, export

## Loyalty dan Statistik Pelanggan

Status:

- fitur menarik dan bernilai tinggi

Masalah:

- entrypoint menu tidak sinkron
- naming file belum stabil

Rekomendasi:

- satukan ke satu cluster halaman resmi:
  - dashboard statistik
  - kategori member
  - diskon member
  - follow-up WA

## Risiko Keamanan Spesifik

## Kritis

- password plaintext
- token Accurate hardcoded
- kredensial DB hardcoded

## Tinggi

- query SQL manual luas
- permission check tidak konsisten
- broken/misaligned menu bisa membuka celah akses tak terduga

## Sedang

- logging aktivitas ada, tetapi belum terintegrasi konsisten
- beberapa halaman debug/fallback masih ada

## Risiko Operasional

- user bisa bingung karena menu baru dan menu lama hidup bersamaan
- developer bisa salah modifikasi file backup/copy
- bug perubahan schema bisa sulit dilacak karena query defensif tersebar
- deployment antar environment rawan karena config masih inline

## Target Refactor yang Paling Masuk Akal

## Fase 1: Stabilkan keamanan dan konfigurasi

Target:

- pindahkan kredensial DB dan Accurate ke environment/config terpisah
- perbaiki login supaya mendukung hashing password
- hilangkan redirect hardcoded environment

Deliverable:

- file config environment
- login adapter baru
- migration password bertahap

Manfaat:

- risiko kebocoran jauh berkurang
- local/staging/production lebih sehat

## Fase 2: Konsolidasikan RBAC

Target:

- jadikan `tb_master_posisi` sebagai satu-satunya sistem permission aktif untuk `_admincab`
- buat helper tunggal untuk page guard
- hapus ketergantungan menu lama bertahap

Deliverable:

- middleware permission tunggal
- daftar halaman `_admincab` dan permission-nya
- menu baru yang sinkron penuh dengan file fisik

Manfaat:

- akses user bisa diprediksi
- sidebar dan page guard konsisten

## Fase 3: Rapikan routing dan inventori halaman

Target:

- tandai halaman aktif, wrapper, deprecated, backup
- sinkronkan `menu_config.php` dengan file riil

Deliverable:

- daftar halaman aktif resmi
- redirect map
- folder arsip atau penamaan standar untuk legacy

Manfaat:

- onboarding developer lebih cepat
- user tidak menemukan menu mati

## Fase 4: Refactor domain Servis

Target:

- ekstrak logika servis dari halaman ke service/helper layer

Cluster yang disarankan:

- ServiceOrderService
- QueueService
- ComplaintService
- FindingService
- WorkOrderService
- CustomerStatsService

Deliverable:

- helper/domain layer untuk operasi inti servis
- pengurangan SQL inline di halaman penting

Manfaat:

- area bisnis paling penting jadi lebih stabil dan reusable

## Fase 5: Refactor transaksi Pembelian/Penjualan

Target:

- standarkan pola list/detail/add/edit/save/export
- kurangi wrapper redirect dan default page kosong

Deliverable:

- template list transaksi
- query builder/helper standar

Manfaat:

- modul transaksi jadi lebih mudah dipelihara

## Fase 6: Rapikan CRM/Loyalty

Target:

- satukan halaman loyalty ke naming dan menu yang final

Deliverable:

- menu resmi loyalty
- dashboard statistik pelanggan
- setting kategori member
- setting diskon member
- follow-up tools

Manfaat:

- fitur unggulan ini jadi lebih mudah dipakai dan dikembangkan

## Backlog Teknis Disarankan

### P1

- hash password user
- env config untuk DB dan Accurate
- hilangkan token rahasia dari repo
- audit permission halaman `_admincab`

### P2

- sinkronkan `menu_config.php` dengan file fisik
- ganti halaman `_admincab` yang masih include menu lama
- buat daftar file wrapper/deprecated

### P3

- ekstrak domain servis ke helper/service
- standardisasi layout dengan header/sidebar tunggal
- kurangi query SQL inline di halaman top-priority

### P4

- refactor pembelian/penjualan
- satukan naming loyalty/statistik pelanggan
- tambah automated smoke checklist

## Prinsip Refactor yang Disarankan

Jangan refactor seluruh proyek sekaligus. Proyek ini lebih aman dibenahi dengan prinsip:

- protect the business first
- migrate the hot path first
- preserve old URLs while changing internals
- consolidate config and auth before beautifying code
- refactor by domain, not by folder only

## Urutan Kerja Paling Aman

1. amankan kredensial dan login
2. bereskan RBAC dan menu `_admincab`
3. tetapkan inventori halaman aktif
4. rapikan domain servis
5. lanjut ke transaksi pembelian/penjualan
6. rapikan CRM/loyalty

## Kesimpulan Audit

Sistem ini layak dipertahankan dan ditingkatkan karena domain bisnisnya sudah kaya dan bernilai tinggi. Tantangan utamanya bukan kekurangan fitur, tetapi konsistensi teknis dan keamanan.

Kalau dikerjakan bertahap dengan fokus ke auth, RBAC, dan domain servis lebih dulu, proyek ini bisa naik kelas dari monolit legacy yang rawan menjadi platform operasional bengkel yang jauh lebih stabil tanpa harus rewrite total.
