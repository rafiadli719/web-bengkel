================================================================================
CARA INSTALL DATABASE VIEWS - VERSI FINAL (SUDAH DIPERBAIKI)
================================================================================

MASALAH YANG TERJADI:
---------------------
Error: #1347 - 'fitmotor_dbbengkel.view_cari_item' is not VIEW

PENYEBAB:
---------
Saat export database, phpMyAdmin membuat TABLE PLACEHOLDER untuk VIEW.
Jadi objek seperti view_cari_item, view_stok, dll adalah TABLE kosong,
bukan VIEW yang sebenarnya.

SOLUSI:
-------
Script baru akan MENGHAPUS TABLE PLACEHOLDER dan menggantinya dengan VIEW.

================================================================================
LANGKAH INSTALASI (IKUTI URUTAN INI!)
================================================================================

STEP 0: CEK DULU APA YANG ADA DI DATABASE
==========================================
1. Buka phpMyAdmin
2. Pilih database: fitmotor_dbbengkel
3. Tab SQL
4. Jalankan query dari file: check_view_or_table.sql

Ini akan menampilkan:
- Objek mana yang TABLE
- Objek mana yang VIEW

STEP 1: BACKUP DATABASE (WAJIB!)
=================================
1. Di phpMyAdmin, pilih database fitmotor_dbbengkel
2. Tab "Export"
3. Pilih "Quick"
4. Klik "Go"
5. Simpan file backup

STEP 2: INSTALL VIEWS (FILE UTAMA)
===================================
Gunakan file: create_views_safe.sql

Cara Install:
-------------
1. Buka phpMyAdmin
2. Pilih database: fitmotor_dbbengkel
3. Tab "SQL"
4. Buka file create_views_safe.sql di Notepad
5. COPY SEMUA ISI FILE (Ctrl+A, Ctrl+C)
6. PASTE di SQL editor phpMyAdmin (Ctrl+V)
7. Klik tombol "Go" atau "Kirim"
8. Tunggu proses selesai

Yang Akan Terjadi:
------------------
- Script akan DROP TABLE placeholder (yang kosong)
- Script akan CREATE VIEW yang sebenarnya
- Total 15 VIEW akan dibuat

STEP 3: VERIFIKASI HASIL
=========================
Jalankan query ini di phpMyAdmin:

-- Lihat semua VIEW
SHOW FULL TABLES WHERE table_type = 'VIEW';

-- Hitung jumlah VIEW
SELECT COUNT(*) as total_views
FROM information_schema.TABLES
WHERE table_schema = 'fitmotor_dbbengkel'
AND table_type = 'VIEW';

-- Test beberapa VIEW
SELECT * FROM view_cari_item LIMIT 5;
SELECT * FROM view_stok_master LIMIT 5;
SELECT * FROM view_pelanggan_kendaraan LIMIT 5;
SELECT * FROM v_po_status LIMIT 5;

Expected Result:
----------------
- Jumlah VIEW: 15
- Semua SELECT berhasil tanpa error
- Data muncul (jika ada data di table)

================================================================================
DAFTAR VIEW YANG AKAN DIBUAT
================================================================================

No  | Nama VIEW                    | Fungsi
----|------------------------------|----------------------------------------
1   | view_cari_item              | Pencarian barang/item dengan stok
2   | view_cari_kendaraan         | Pencarian kendaraan lengkap
3   | view_cari_pelanggan         | Pencarian pelanggan dengan kategori
4   | view_pelanggan_kendaraan    | Gabungan pelanggan + kendaraan
5   | view_stok                   | Transaksi stok detail
6   | view_stok_master            | Saldo stok per cabang
7   | view_master_keluhan         | Master keluhan aktif
8   | view_keluhan_workorder      | Mapping keluhan-workorder
9   | view_tarif_jemput           | Tarif jemput motor
10  | v_po_status                 | Status Purchase Order
11  | view_pembelian_header       | Header transaksi pembelian
12  | view_pembelian_detail       | Detail transaksi pembelian
13  | view_penjualan_header       | Header transaksi penjualan
14  | view_penjualan_detail       | Detail transaksi penjualan
15  | view_user_details           | Detail user dengan cabang

================================================================================
TROUBLESHOOTING
================================================================================

ERROR: "Table 'xxx' doesn't exist"
-----------------------------------
Solusi: Cek nama tabel yang benar di database Anda
Query: SHOW TABLES LIKE '%nama_tabel%';

ERROR: "Unknown column 'xxx'"
------------------------------
Solusi: Cek struktur tabel
Query: DESCRIBE nama_tabel;

ERROR: "Foreign key constraint"
-------------------------------
Solusi: Script sudah disable foreign key check
Jika masih error, hapus manual: DROP TABLE nama_view;

ERROR: "Access denied"
----------------------
Solusi: Login sebagai user dengan privilege CREATE VIEW

VIEW tidak muncul data
----------------------
Kemungkinan:
- Table sumber belum ada data
- Join condition tidak match
- Filter WHERE menghilangkan semua data

Cek: SELECT COUNT(*) FROM nama_table_sumber;

================================================================================
FILE-FILE YANG TERSEDIA
================================================================================

1. create_views_safe.sql         ⭐⭐⭐ FILE UTAMA - GUNAKAN INI
   Fungsi: Membuat 15 VIEW (menghapus placeholder table)

2. check_view_or_table.sql       ⭐⭐ CEK DULU
   Fungsi: Cek objek mana yang TABLE atau VIEW

3. create_all_views_fixed.sql    ❌ JANGAN PAKAI (ada error)
   Fungsi: Versi lama yang coba DROP VIEW

4. create_views_simple_test.sql  ❌ JANGAN PAKAI (ada error)
   Fungsi: Versi test yang coba DROP VIEW

5. CARA_INSTALL_VIEWS.md         📖 DOKUMENTASI
   Fungsi: Dokumentasi lengkap

6. README_INSTALL_VIEWS.txt      📖 DOKUMENTASI (ini file)
   Fungsi: Quick reference

================================================================================
RINGKASAN
================================================================================

✅ GUNAKAN: create_views_safe.sql (FILE UTAMA)
✅ CEK DULU: check_view_or_table.sql
❌ JANGAN: create_all_views_fixed.sql (error)
❌ JANGAN: create_views_simple_test.sql (error)

LANGKAH SINGKAT:
1. Backup database
2. Buka create_views_safe.sql
3. Copy semua isi
4. Paste di phpMyAdmin SQL
5. Klik Go
6. Verifikasi dengan SHOW FULL TABLES WHERE table_type = 'VIEW';

STATUS AKHIR:
- 15 VIEW akan dibuat
- TABLE placeholder akan dihapus
- Siap digunakan aplikasi

================================================================================
Dibuat: 2025-11-28
Versi: 2.0 (Safe Mode - Drop Table First)
================================================================================
