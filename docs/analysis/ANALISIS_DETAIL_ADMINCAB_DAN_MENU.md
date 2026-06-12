# Analisis Detail `_admincab` dan Peta Halaman

## Tujuan Dokumen

Dokumen ini memetakan area `_admincab` secara lebih detail:

- menu RBAC yang didefinisikan di `menu_config.php`
- status file fisik untuk tiap item menu
- fungsi bisnis utama per halaman
- temuan transisi antara sistem baru dan sistem lama

## Posisi `_admincab` dalam Proyek

Folder `_admincab` adalah pusat aplikasi yang paling penting saat ini karena:

- `cek_login.php` mengarahkan user ke `_admincab/index.php`
- sidebar di `_admincab` sudah memakai menu RBAC dinamis
- fitur-fitur baru paling banyak muncul di area ini
- modul servis modern, antrian, loyalty, dan role management berada di sini

Walaupun begitu, `_admincab` belum sepenuhnya homogen. Masih ada campuran:

- halaman baru dengan `lib/header.php` dan `lib/sidebar.php`
- halaman lama dengan layout manual dan include `menu_xxx.php`
- file redirect wrapper
- file backup/legacy

## Mekanisme Menu RBAC

File yang terlibat:

- `_admincab/menu_config.php`
- `_admincab/menu_dashboard.php`
- `_admincab/_include_menu_rbac.php`
- `lib/rbac.php`
- `_admincab/master-posisi.php`

Alurnya:

1. user login dan session `_iduser` tersedia
2. `menu_dashboard.php` memanggil `_include_menu_rbac.php`
3. `_include_menu_rbac.php` membaca permission dari `tb_master_posisi`
4. `menu_config.php` menjadi definisi struktur menu
5. menu difilter berdasarkan permission user
6. HTML sidebar dirender dinamis

## Status Inventori Menu `_admincab`

Hasil pencocokan `menu_config.php` terhadap file fisik di `_admincab`:

### Item menu yang terdeteksi `EXISTS`

- `index.php`
- `barang.php`
- `barang_kategori.php`
- `barang_satuan.php`
- `barang_pabrik.php`
- `barang_rak.php`
- `margin_jual.php`
- `status_harga.php`
- `paket.php`
- `master-keluhan-crud.php`
- `master-workorder-mapping.php`
- `master-fastmoves.php`
- `master-temuan.php`
- `master-temuan-mapping.php`
- `master-barang-custom.php`
- `hjual_jasa.php`
- `pelanggan.php`
- `pelanggan_kategori.php`
- `supplier.php`
- `cabang.php`
- `cabang_tipe.php`
- `setting_antarcabang.php`
- `mekanik_level.php`
- `master-tarif-jemput.php`
- `sales.php`
- `motor_tipe.php`
- `motor_pabrik.php`
- `motor_kategori.php`
- `kendaraan.php`
- `motor_warna.php`
- `prop.php`
- `kab.php`
- `kec.php`
- `desa.php`
- `akun_kas.php`
- `akun_biaya.php`
- `user.php`
- `master-posisi.php`
- `master_karyawan.php`
- `keping.php`
- `pesanan_pembelian.php`
- `pembelian.php`
- `pmby_hutang.php`
- `pesanan_penjualan.php`
- `penjualan.php`
- `pmby_piutang.php`
- `pesanan_penjualan_cab_add.php`
- `penjualan_cab_add.php`
- `pembelian_cab_add.php`
- `input_kepala_mekanik_harian.php`
- `kelola-antrian.php`
- `servis-carinopol.php`
- `servis-reguler.php`
- `servis-carinopol-garansi.php`
- `servis-garansi.php`
- `servis-reguler-jemput.php`
- `report-tracking-keluhan.php`
- `penyesuaian-stok-masuk-manual.php`
- `penyesuaian-stok-keluar-manual.php`
- `penyesuaian-stok-otomatis.php`
- `stok-akhir.php`
- `lap_pesanan_pembelian.php`
- `lap_pembelian.php`
- `lap_pmby_hutang.php`
- `lap_pesanan_penjualan.php`
- `lap_penjualan.php`
- `lap_pmby_piutang.php`
- `lap_servis.php`
- `lap_kas_masuk.php`
- `lap_kas_keluar.php`
- `lap_stok_masuk.php`
- `lap_stok_keluar.php`

### Item menu yang terdeteksi `MISSING`

- `member-loyalty-program.php`
- `statistik-pelanggan.php`
- `mekanik.php`
- `master_kepala_mekanik.php`
- `lap_cancel_servis.php`

## Interpretasi mismatch menu

Mismatch di atas tidak selalu berarti fitur belum ada. Dalam beberapa kasus, implementasinya ada tetapi memakai nama file berbeda.

### Loyalty Member Program

Menu menunjuk ke:

- `member-loyalty-program.php` -> `MISSING`

Implementasi aktual yang terdeteksi:

- `master_kategori_member.php`
- `setting_diskon_member_item.php`
- `setting-highlight-member.php`
- `statistik_pelanggan_dashboard.php`
- `statistik_pelanggan_send_wa.php`

Kesimpulan:

- fitur loyalty nyata ada
- hanya entrypoint menu belum diselaraskan

### Statistik Pelanggan

Menu menunjuk ke:

- `statistik-pelanggan.php` -> `MISSING`

Implementasi aktual:

- `statistik_pelanggan_dashboard.php`
- `_include_statistik_pelanggan.php`
- template statistik pelanggan

Kesimpulan:

- dashboard statistik ada
- nama file di menu belum diperbarui

### Mekanik

Menu menunjuk ke:

- `mekanik.php` -> `MISSING`
- `master_kepala_mekanik.php` -> `MISSING`

Implementasi aktual yang ada:

- `mekanik_management.php`
- `mekanik_add.php`
- `mekanik_edit.php`
- `mekanik_del.php`
- `mekanik_level.php`
- `input_kepala_mekanik_harian.php`
- `get_kepala_mekanik_harian.php`

Kesimpulan:

- modul mekanik ada
- nama menu dan nama implementasi belum sinkron

### Laporan Cancel Service

Menu menunjuk ke:

- `lap_cancel_servis.php` -> `MISSING`

Sinyal implementasi yang terdeteksi:

- ada view database `view_laporan_cancel_servis` pada dump SQL
- ada file SQL terkait pembaruan cancel service

Kesimpulan:

- laporannya kemungkinan sudah ada di level data
- halaman UI `_admincab` belum tersedia atau belum final

## Peta Detail per Kelompok Menu

## 1. Dashboard

### `index.php`

Peran:

- landing dashboard utama setelah login
- saat ini menampilkan ringkasan antrian servis hari ini

Data yang diambil:

- `tb_antrian_servis`

Statistik yang ditampilkan:

- total antrian
- menunggu
- diproses
- selesai

Relasi ke halaman lain:

- tombol ke `dashboard-antrian-servis.php`

Catatan:

- dashboard utama saat ini sangat berfokus pada operasi servis harian
- belum menjadi executive dashboard lintas modul

### `dashboard-antrian-servis.php`

Peran:

- dashboard operasional progress antrian dan mekanik

Data yang diambil:

- `tb_antrian_servis`
- `tblservice`
- `tblpelanggan`
- `tb_progress_mekanik`

Yang terlihat:

- antrian hari ini
- daftar antrian terbaru
- mekanik yang sedang bekerja

## 2. Data Master

## 2.1 Daftar Item

### `barang.php`

Peran:

- daftar master barang
- titik masuk inventory item

Karakter:

- masih bergaya halaman lama
- kemungkinan pakai `view` atau tabel item utama

### `barang_kategori.php`

Peran:

- master kategori barang

### `barang_satuan.php`

Peran:

- master satuan barang

### `barang_pabrik.php`

Peran:

- master pabrik/manufacturer barang

### `barang_rak.php`

Peran:

- pengelompokan atau lokasi rak fisik item

### `margin_jual.php`

Peran:

- mengatur margin harga jual

### `status_harga.php`

Peran:

- pengaturan status harga

Catatan:

- ada file `save_status_harga.php`

### `paket.php`

Peran:

- master work order / paket servis

Relasi data:

- `tbworkorderheader`
- `tbworkorderdetail`

Pola proses:

- menambahkan jasa dan barang ke satu paket
- menghitung total harga

### `hjual_jasa.php`

Peran:

- master harga jual plus jasa

Interpretasi:

- kemungkinan pricing matrix untuk kombinasi barang + jasa

## 2.2 Keluhan, Work Order, Temuan

### `master-keluhan-crud.php`

Peran:

- CRUD master keluhan
- approval keluhan dari cabang ke pusat

Kemampuan yang terdeteksi:

- add keluhan baru
- edit
- soft delete
- activate
- approve
- reject dengan alasan

Tabel:

- `tbmaster_keluhan`

Workflow:

- cabang dapat mengusulkan keluhan baru
- status approval: `pending`, `approved`, `rejected`

### `master-workorder-mapping.php`

Peran:

- memetakan keluhan ke work order default

Tabel:

- `tbmaster_keluhan_workorder`
- `tbworkorderheader`

Kemampuan:

- tambah mapping
- edit mapping
- soft delete
- bulk sync `workorder_default` ke master keluhan

### `master-fastmoves.php`

Peran:

- mapping fast-moving items

Interpretasi:

- kemungkinan untuk rekomendasi part yang sering dipakai

### `master-temuan.php`

Peran:

- CRUD master temuan teknis

Tabel:

- `tbmaster_temuan`

Field penting yang terdeteksi:

- `kode_temuan`
- `nama_temuan`
- `kategori`
- `deskripsi`
- `penyebab_umum`
- `solusi_umum`
- `estimasi_waktu`
- `tingkat_urgensi`
- `is_active`

### `master-temuan-mapping.php`

Peran:

- memetakan temuan ke part atau tindakan

Catatan:

- ada juga file `master-temuan-mapping-jasa.php`
- artinya mapping temuan bisa menyentuh item dan jasa

### `master-barang-custom.php`

Peran:

- master barang custom di luar item standar

Interpretasi:

- berguna untuk barang non-standard atau kebutuhan servis khusus

## 2.3 Pelanggan dan Loyalty

### `pelanggan.php`

Peran:

- daftar master pelanggan

Data sumber:

- `view_cari_pelanggan`

Catatan teknis:

- masih memakai `menu_pelanggan01.php`, bukan sidebar RBAC baru
- include `config/koneksi1.php`, tapi file itu ternyata hanya alias ke `koneksi.php`

### `pelanggan_kategori.php`

Peran:

- kategori pelanggan

### `master_kategori_member.php`

Peran:

- definisi kategori member berdasarkan nominal atau kunjungan

Kemampuan:

- set nama kategori
- tipe kategori
- rentang min/max
- diskon persen
- benefit text
- icon
- warna
- urutan
- status aktif

### `setting_diskon_member_item.php`

Peran:

- mengatur item/kategori yang boleh atau tidak boleh mendapat diskon member

Pola:

- aturan per kategori
- override item spesifik

### `setting-highlight-member.php`

Peran:

- visual highlight pelanggan/member di UI

### `statistik_pelanggan_dashboard.php`

Peran:

- dashboard statistik pelanggan real-time

Data sumber:

- `statistik_pelanggan`

Ringkasan yang dihitung:

- total pelanggan
- total pendapatan
- rata-rata transaksi
- distribusi Bronze/Silver/Gold/Platinum
- pelanggan yang perlu follow-up

Template yang dipakai:

- `_template/_statistik_semua_pelanggan.php`
- `_template/_statistik_followup_pelanggan.php`
- `_template/_statistik_top_pelanggan.php`

### `statistik_pelanggan_send_wa.php`

Peran:

- indikasi fitur follow-up pelanggan via WhatsApp

## 2.4 Supplier

### `supplier.php`

Peran:

- master supplier

Status:

- halaman ada
- pola masih layout lama

## 2.5 Cabang

### `cabang.php`

Peran:

- master cabang

### `cabang_tipe.php`

Peran:

- master tipe cabang

### `setting_antarcabang.php`

Peran:

- pengaturan pricing/margin antar cabang

Terhubung ke:

- `tbl_setting_antarcabang`

## 2.6 Mekanik

### `mekanik_management.php`

Peran:

- kandidat halaman manajemen mekanik utama

### `mekanik_add.php`, `mekanik_edit.php`, `mekanik_del.php`

Peran:

- operasi CRUD mekanik

### `mekanik_level.php`

Peran:

- master level mekanik

### `input_kepala_mekanik_harian.php`

Peran:

- input kepala mekanik harian

### `master-tarif-jemput.php`

Peran:

- pengaturan tarif jemput antar

## 2.7 Sales

### `sales.php`

Peran:

- master sales

Status:

- halaman ada
- pola masih klasik

## 2.8 Kendaraan

Halaman:

- `motor_tipe.php`
- `motor_pabrik.php`
- `motor_kategori.php`
- `kendaraan.php`
- `motor_warna.php`

Peran:

- struktur master kendaraan pelanggan

Relasi penting:

- `tblkendaraan`
- `tblpelanggan`

## 2.9 Wilayah

Halaman:

- `prop.php`
- `kab.php`
- `kec.php`
- `desa.php`

Peran:

- master wilayah administrasi untuk alamat pelanggan/cabang

## 2.10 User, Posisi, Karyawan

### `user.php`

Peran:

- pengelolaan akun user aplikasi

Status:

- file ada
- simpan user dilakukan di `save_user.php`

Temuan penting:

- `save_user.php` menyimpan password mentah ke `tbuser`

### `master-posisi.php`

Peran:

- halaman paling penting untuk RBAC baru

Kemampuan:

- tambah/edit/hapus posisi
- set `user_akses_level`
- set permission JSON dari tree menu
- toggle active/inactive

### `master_karyawan.php`

Peran:

- master karyawan

Status:

- lebih modern daripada banyak halaman lain
- sudah include `config/permission_check.php`
- sebagian check permission masih dinonaktifkan sementara

## 3. Pembelian

## `pesanan_pembelian.php`

Status khusus:

- saat dibuka langsung redirect ke `pembelian_dari_po.php`

Makna:

- file ini dipakai sebagai wrapper/transisi
- UI lama di bawahnya sudah tidak aktif karena ada `header()` dan `__halt_compiler()`

## `pembelian_dari_po.php`

Peran:

- daftar PO approved yang siap diproses jadi pembelian

Fitur:

- filter status
- filter supplier
- keyword
- progress pemenuhan PO

## `pembelian.php`

Peran:

- daftar pembelian header

Status:

- default query diset kosong (`notransaksi='0'`)
- tampaknya halaman mengandalkan hasil pencarian/filter atau flow lain

## `pmby_hutang.php`

Peran:

- daftar pembayaran hutang supplier

Data sumber:

- `view_pembayaran_hutang`

Fitur:

- filter
- sorting
- pagination

## 4. Penjualan

## `pesanan_penjualan.php`

Peran:

- daftar pesanan penjualan

## `penjualan.php`

Peran:

- daftar penjualan header

Data sumber:

- `view_penjualan_header`

Temuan:

- file cukup defensif terhadap perbedaan struktur view
- memakai `SHOW COLUMNS` untuk menyesuaikan nama kolom yang tersedia

Ini menandakan struktur data pernah berubah dan file ini dipatch agar tahan terhadap variasi view.

## `pmby_piutang.php`

Peran:

- daftar pembayaran piutang pelanggan

Data sumber:

- `view_pembayaran_piutang`

## 5. Antar Cabang

## `pesanan_penjualan_cab_add.php`

Status khusus:

- langsung redirect ke `penjualan_antarcab_upload.php`

Makna:

- halaman entrypoint lama masih dipertahankan
- implementasi aktif dipindahkan ke halaman lain

## `penjualan_cab_add.php`

Peran:

- tarik data penjualan antar cabang

## `pembelian_cab_add.php`

Peran:

- penerimaan antar cabang

Catatan domain:

- antar cabang memakai logika margin/setting antar cabang
- transaksi ini tampaknya memanfaatkan `tblorderjual_detail` dan setting margin internal

## 6. Servis

Ini adalah area paling kaya proses.

## 6.1 Servis Reguler

### `servis-carinopol.php`

Peran:

- entrypoint input servis reguler

Fungsi:

- cari pelanggan/kendaraan
- tampilkan data member
- highlight kategori member

Data penting:

- `tblkendaraan`
- `tblpelanggan`
- `statistik_pelanggan`
- `tbpabrik_motor`

### `servis-reguler.php`

Peran:

- daftar data servis reguler

Data penting:

- `tblservice`
- `tblpelanggan`
- `view_cari_kendaraan`
- `tblkendaraan`

Fitur:

- search
- total display robust
- list 100 data terbaru

Catatan:

- file ini masih include `menu_servis01.php`, bukan sidebar RBAC baru

## 6.2 Servis Garansi

### `servis-carinopol-garansi.php`

Peran:

- input servis garansi

Permission:

- sudah memakai `lib/rbac.php`

### `servis-garansi.php`

Peran:

- daftar servis garansi

Filter data:

- hanya status tertentu (`3`, `4`)

Catatan:

- masih memakai menu lama `menu_servis03.php`

## 6.3 Servis Jemput Antar

### `servis-reguler-jemput.php`

Peran:

- jadwal penjemputan servis

### `report-tracking-keluhan.php`

Peran:

- laporan tracking keluhan berdasarkan periode, kategori, prioritas, status, cabang

Output:

- filter web
- print
- export Excel

## 6.4 Antrian

### `kelola-antrian.php`

Peran:

- pengelolaan status antrian service

Kemampuan:

- update status antrian
- batalkan antrian
- sinkronkan status ke `tblservice`
- filter tanggal
- filter status
- filter prioritas
- search

Data sumber:

- `tb_antrian_servis`
- `tblservice`
- `tblpelanggan`
- `tblkendaraan`

## 7. Penyesuaian Stok

### `penyesuaian-stok-masuk-manual.php`

Peran:

- input stok masuk manual

### `penyesuaian-stok-keluar-manual.php`

Peran:

- input stok keluar manual

### `penyesuaian-stok-otomatis.php`

Peran:

- menampilkan item yang berubah stok otomatis dari transaksi

Data sumber:

- `view_stok`

### `stok-akhir.php`

Peran:

- tampilan stok akhir

## 8. Laporan

Halaman laporan utama:

- `lap_pesanan_pembelian.php`
- `lap_pembelian.php`
- `lap_pmby_hutang.php`
- `lap_pesanan_penjualan.php`
- `lap_penjualan.php`
- `lap_pmby_piutang.php`
- `lap_servis.php`
- `lap_kas_masuk.php`
- `lap_kas_keluar.php`
- `lap_stok_masuk.php`
- `lap_stok_keluar.php`

### `lap_servis.php`

Peran:

- filter laporan servis per tanggal dan pelanggan

Output:

- tampilan web
- PDF
- XLS

Data sumber:

- `view_service`

## Pola Halaman yang Perlu Diketahui

### 1. Redirect wrapper

Beberapa menu mengarah ke file yang hanya melakukan redirect:

- `pesanan_pembelian.php` -> `pembelian_dari_po.php`
- `pesanan_penjualan_cab_add.php` -> `penjualan_antarcab_upload.php`

Artinya:

- entrypoint menu dipertahankan untuk kompatibilitas
- implementasi aktif sudah dipindahkan

### 2. Campuran sidebar baru dan menu lama

Contoh halaman yang masih pakai menu lama:

- `pelanggan.php` -> `menu_pelanggan01.php`
- `servis-reguler.php` -> `menu_servis01.php`
- `servis-garansi.php` -> `menu_servis03.php`
- `pembelian_dari_po.php` -> `menu_pembelian02.php`

Artinya:

- walau login sudah diarahkan ke `_admincab`, sebagian halaman di dalamnya belum seragam memakai sidebar RBAC baru

### 3. Campuran layout lama dan layout baru

Layout baru cenderung memakai:

- `lib/header.php`
- `lib/sidebar.php`

Layout lama cenderung:

- membangun navbar dan sidebar manual di file yang sama

## Kesimpulan Operasional

Kalau tim ingin memahami `_admincab` dengan cepat, prioritas baca terbaik adalah:

1. `menu_config.php`
2. `_include_menu_rbac.php`
3. `master-posisi.php`
4. `index.php`
5. `dashboard-antrian-servis.php`
6. `servis-carinopol.php`
7. `kelola-antrian.php`
8. `master-keluhan-crud.php`
9. `master-workorder-mapping.php`
10. `master-temuan.php`
11. `master_kategori_member.php`
12. `statistik_pelanggan_dashboard.php`

Secara praktis, `_admincab` hari ini paling kuat di tiga area:

- servis dan antrian
- master data bengkel
- role/permission dan customer intelligence

Sedangkan area yang masih jelas membutuhkan konsolidasi:

- menu loyalty/statistik yang belum sinkron
- modul mekanik yang nama file dan nama menu belum konsisten
- laporan cancel service yang belum punya UI final
- banyak halaman yang masih membawa menu lama di dalam `_admincab`
