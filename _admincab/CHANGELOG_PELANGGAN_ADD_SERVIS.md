# Changelog: Pelanggan Add Servis Enhancement

## Tanggal: 2025-10-11

## Ringkasan Perubahan
Menambahkan fitur-fitur baru pada halaman `pelanggan_add_servis.php` untuk meningkatkan pengalaman pengguna dan kelengkapan data pelanggan.

---

## Fitur Baru yang Ditambahkan

### 1. Field Link Google Maps
- **Lokasi**: `pelanggan_add_servis.php` baris 262-268
- **Deskripsi**: Menambahkan input field untuk menyimpan link Google Maps lokasi rumah pelanggan
- **Tipe Field**: URL input dengan placeholder dan help text
- **Database Column**: `google_maps` (TEXT, NULL)

### 2. Field Upload Foto Tampak Rumah
- **Lokasi**: `pelanggan_add_servis.php` baris 269-275
- **Deskripsi**: Menambahkan upload field untuk foto tampak depan rumah pelanggan
- **Validasi**:
  - Tipe file: JPG/PNG only
  - Max size: 2MB
  - Auto rename: `rumah_[NOPOL]_[TIMESTAMP].[ext]`
- **Database Column**: `foto_rumah` (VARCHAR(255), NULL)
- **Storage Location**: `file_upload/foto_rumah/`

### 3. Semua Field Dapat Diedit
- **Lokasi**: `pelanggan_add_servis.php` baris 603
- **Deskripsi**: Menghapus disabled/readonly pada field pelanggan existing
- **Benefit**: User dapat mengupdate data pelanggan yang sudah ada sebelumnya

### 4. Pilihan Jenis Servis (2 Tombol)
- **Lokasi**: `pelanggan_add_servis.php` baris 367-387
- **Deskripsi**: Mengganti tombol "Lanjut ke Input Garapan" menjadi 2 tombol:
  1. **Servis Reguler** (btn-success dengan icon wrench)
  2. **Servis Jemput Antar** (btn-info dengan icon motorcycle)
- **Value**: Parameter `jenis_servis` (reguler/jemput_antar)

---

## File yang Dimodifikasi

### 1. `pelanggan_add_servis.php`
**Perubahan**:
- Menambahkan field Google Maps link (baris 262-268)
- Menambahkan field upload foto rumah (baris 269-275)
- Menambahkan `enctype="multipart/form-data"` pada form (baris 173)
- Menghapus disable field untuk existing customer (baris 603)
- Mengubah tombol submit menjadi 2 pilihan jenis servis (baris 367-387)

### 2. `save_pelanggan_servis.php`
**Perubahan**:
- Menangani parameter `google_maps` dari form (baris 31)
- Menangani parameter `jenis_servis` dari form (baris 32)
- Menambahkan logic upload foto rumah (baris 34-65)
  - Validasi tipe file (JPG/PNG)
  - Validasi ukuran file (max 2MB)
  - Auto create folder jika belum ada
  - Auto rename file dengan pattern yang konsisten
- Menambahkan kolom `google_maps` dan `foto_rumah` ke query INSERT (baris 161-182)
- Menambahkan redirect berdasarkan pilihan jenis servis (baris 281-288):
  - `jemput_antar` → redirect ke `servis-input-reguler-jemput.php`
  - `reguler` → redirect ke `servis-input-reguler.php`

### 3. `input_pelanggan_awal.php`
**Perubahan**:
- Mengubah redirect dari `pelanggan_add_enhanced.php` ke `pelanggan_add_servis.php` (baris 798, 814)
- Memastikan flow pengecekan nomor WA dan nopol mengarah ke halaman yang benar

---

## Database Migration

### File SQL Migration
**Nama File**: `migration_add_google_maps_foto_rumah.sql`

**Isi Migration**:
```sql
-- Add google_maps column
ALTER TABLE tblpelanggan
ADD COLUMN IF NOT EXISTS google_maps TEXT NULL
COMMENT 'Link Google Maps lokasi rumah pelanggan';

-- Add foto_rumah column
ALTER TABLE tblpelanggan
ADD COLUMN IF NOT EXISTS foto_rumah VARCHAR(255) NULL
COMMENT 'Path file foto tampak depan rumah pelanggan';
```

**Cara Menjalankan Migration**:
1. Buka phpMyAdmin
2. Pilih database yang sesuai (biasanya `fitmotor_dbbengkel`)
3. Buka tab SQL
4. Copy paste isi file `migration_add_google_maps_foto_rumah.sql`
5. Klik "Go" untuk menjalankan

**Atau menggunakan MySQL CLI**:
```bash
mysql -u root -p fitmotor_dbbengkel < migration_add_google_maps_foto_rumah.sql
```

---

## Flow Aplikasi Setelah Perubahan

### Flow 1: Pelanggan & Kendaraan Baru
1. User masuk ke `input_pelanggan_awal.php`
2. User cek nomor WA dan/atau nopol
3. Jika tidak ditemukan → klik "Tambah Pelanggan Baru"
4. Redirect ke `pelanggan_add_servis.php`
5. User mengisi form lengkap termasuk:
   - Data pelanggan (nama, gender, tanggal lahir, dll)
   - Link Google Maps (**BARU**)
   - Upload foto tampak rumah (**BARU**)
   - Data kendaraan (nopol, merek, tipe, dll)
6. User memilih jenis servis (**BARU**):
   - Klik "Servis Reguler" → redirect ke `servis-input-reguler.php`
   - Klik "Servis Jemput Antar" → redirect ke `servis-input-reguler-jemput.php`

### Flow 2: Pelanggan Existing, Kendaraan Baru
1. User masuk ke `input_pelanggan_awal.php`
2. User cek nomor WA (ditemukan)
3. Klik "Tambah Kendaraan Baru"
4. Redirect ke `pelanggan_add_servis.php?phone=[WA]&mode=add_vehicle`
5. Form auto-fill data pelanggan existing (**semua field tetap editable**)
6. User dapat update data pelanggan jika ada perubahan
7. User mengisi data kendaraan baru
8. User memilih jenis servis
9. Redirect sesuai pilihan servis

### Flow 3: Data Lengkap Ditemukan
1. User masuk ke `input_pelanggan_awal.php`
2. User cek nomor WA dan nopol (keduanya ditemukan)
3. Sistem menampilkan pilihan jenis servis langsung
4. User klik salah satu tombol servis
5. Redirect langsung ke halaman input servis yang sesuai

---

## Struktur Folder Upload

```
web-bengkel/
└── aplikasi/
    └── file_upload/
        └── foto_rumah/          <- FOLDER BARU (auto-created)
            ├── rumah_B1234ABC_1696234567.jpg
            ├── rumah_B5678DEF_1696234590.png
            └── ...
```

**Naming Convention**:
- Pattern: `rumah_[NOPOL_UPPERCASE_NO_SPACE]_[UNIX_TIMESTAMP].[ext]`
- Contoh: `rumah_B1234ABC_1696234567.jpg`

---

## Validasi yang Diterapkan

### Form Validation (Client-side)
1. Semua field required tetap required
2. Google Maps link → tipe URL (optional)
3. Foto rumah → accept="image/*" (optional)

### Server Validation (save_pelanggan_servis.php)
1. **Upload Foto**:
   - Allowed types: `image/jpeg`, `image/jpg`, `image/png`
   - Max size: 2MB (2 * 1024 * 1024 bytes)
   - Error handling dengan redirect + error message

2. **Google Maps**:
   - No validation (optional field, TEXT type)

3. **Jenis Servis**:
   - Default: 'reguler'
   - Allowed values: 'reguler', 'jemput_antar'

---

## Testing Checklist

### ✅ Test Case 1: Pelanggan Baru
- [ ] Form dapat diakses dari input_pelanggan_awal.php
- [ ] Semua field dapat diisi
- [ ] Upload foto rumah berhasil (JPG)
- [ ] Upload foto rumah berhasil (PNG)
- [ ] Upload foto >2MB ditolak dengan error message
- [ ] Upload file non-image ditolak dengan error message
- [ ] Google Maps link dapat disimpan
- [ ] Klik "Servis Reguler" redirect ke servis-input-reguler.php
- [ ] Klik "Servis Jemput Antar" redirect ke servis-input-reguler-jemput.php

### ✅ Test Case 2: Update Pelanggan Existing
- [ ] Auto-fill data pelanggan existing
- [ ] Semua field tetap editable
- [ ] Update data pelanggan berhasil disimpan
- [ ] Foto rumah baru dapat diupload untuk pelanggan existing
- [ ] Google Maps link dapat ditambahkan/diupdate

### ✅ Test Case 3: Database
- [ ] Migration SQL berhasil dijalankan tanpa error
- [ ] Kolom google_maps ada di tblpelanggan
- [ ] Kolom foto_rumah ada di tblpelanggan
- [ ] Data tersimpan dengan benar
- [ ] Foto file tersimpan di folder yang benar

---

## Catatan Penting

1. **Backup Database**: Selalu backup database sebelum menjalankan migration
2. **Folder Permission**: Pastikan folder `file_upload/foto_rumah/` memiliki permission write (777 atau sesuai kebutuhan)
3. **File Size Limit**: Jika diperlukan, adjust `upload_max_filesize` dan `post_max_size` di php.ini
4. **Backward Compatibility**: Kolom baru adalah NULL, jadi data lama tetap aman

---

## Kemungkinan Enhancement di Masa Depan

1. **Image Preview**: Menampilkan preview foto sebelum upload
2. **Image Resize**: Auto resize foto ke ukuran standar untuk menghemat storage
3. **Google Maps Embed**: Menampilkan embed map dari link yang diinput
4. **Foto Management**: Halaman untuk view/edit/delete foto rumah pelanggan
5. **Multi Upload**: Upload beberapa foto (tampak depan, samping, dll)
6. **Geolocation**: Auto-detect lokasi dengan HTML5 Geolocation API

---

## Developer Notes

**Author**: Claude AI Assistant
**Date**: 2025-10-11
**Version**: 1.0.0
**Status**: Ready for Testing

**Contact**: Jika ada bug atau pertanyaan, silakan hubungi developer team.
