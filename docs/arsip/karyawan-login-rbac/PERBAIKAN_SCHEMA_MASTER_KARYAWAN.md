# PERBAIKAN SCHEMA MASTER KARYAWAN

## 🔍 Masalah yang Ditemukan

Error di database:
```
Exception: Unknown column 'status_aktif' in 'field list'
```

**Penyebab:** Tabel `tb_master_karyawan` di database tidak memiliki kolom `status_aktif`, tetapi PHP code mencoba mengakses kolom tersebut.

## 📊 Struktur Tabel Aktual

Tabel `tb_master_karyawan` memiliki kolom:
- `id` (int, PRIMARY KEY)
- `kode_karyawan` (varchar, UNIQUE)
- `nik` (varchar)
- `nama_lengkap` (varchar)
- `nama_panggilan` (varchar)
- `kode_posisi` (varchar, FOREIGN KEY)
- `kode_level` (varchar, FOREIGN KEY)
- `kode_cabang` (varchar)
- `email` (varchar)
- `telp` (varchar)
- `alamat` (text)
- `tanggal_masuk` (date)
- **`tanggal_keluar` (date)** ← Untuk menandai status karyawan
- `spesialisasi` (text)
- `sertifikat` (text)
- `foto` (varchar)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## ✅ Perbaikan yang Dilakukan

### 1. Backend Files

#### master_karyawan_ajax.php
**Perubahan:** Mengganti `status_aktif` dengan `tanggal_keluar`

```php
// SEBELUM:
SELECT ... status_aktif ... FROM tb_master_karyawan
WHERE status_aktif = 'aktif'

// SESUDAH:
SELECT ... tanggal_keluar ... FROM tb_master_karyawan
WHERE tanggal_keluar IS NULL (untuk aktif)
WHERE tanggal_keluar IS NOT NULL (untuk non-aktif)
```

#### master_karyawan_save.php
**Perubahan:** Mengganti `status_aktif` dengan `tanggal_keluar`

```php
// INSERT query
INSERT INTO tb_master_karyawan (
    ...
    tanggal_keluar,  // Ganti dari status_aktif
    ...
) VALUES (...)

// UPDATE query
UPDATE tb_master_karyawan SET
    tanggal_keluar = $tanggal_keluar,  // Ganti dari status_aktif
    ...
WHERE id = $id
```

### 2. Frontend Files

#### master_karyawan.php
**Perubahan:** Menampilkan status berdasarkan `tanggal_keluar`

```javascript
// SEBELUM:
var statusClass = row.status_aktif == 'aktif' ? 'status-aktif' : 'status-nonaktif';
var statusText = row.status_aktif == 'aktif' ? 'Aktif' : 'Non-Aktif';

// SESUDAH:
var isAktif = row.tanggal_keluar === null || row.tanggal_keluar === '';
var statusClass = isAktif ? 'label label-success' : 'label label-danger';
var statusText = isAktif ? 'Aktif' : 'Non-Aktif';
```

#### master_karyawan_add.php
**Perubahan:** Mengganti dropdown `status_aktif` dengan input `tanggal_keluar`

```html
<!-- SEBELUM: -->
<select name="status_aktif" class="form-control">
    <option value="aktif" selected>Aktif</option>
    <option value="nonaktif">Non-Aktif</option>
</select>

<!-- SESUDAH: -->
<input type="date" name="tanggal_keluar" class="form-control" 
       placeholder="Tanggal keluar dari perusahaan">
<small>(Kosongkan jika masih aktif)</small>
```

#### master_karyawan_edit.php
**Perubahan:** Mengganti dropdown `status_aktif` dengan input `tanggal_keluar`

```html
<!-- SEBELUM: -->
<select name="status_aktif" class="form-control">
    <option value="aktif">Aktif</option>
    <option value="nonaktif">Non-Aktif</option>
</select>

<!-- SESUDAH: -->
<input type="date" name="tanggal_keluar" class="form-control" 
       value="<?php echo $karyawan['tanggal_keluar'] ?? ''; ?>">
<small>(Kosongkan jika masih aktif)</small>
```

## 🔄 Logika Status Karyawan

### Penentuan Status:
```
Jika tanggal_keluar IS NULL → Status: AKTIF ✅
Jika tanggal_keluar IS NOT NULL → Status: NON-AKTIF ❌
```

### Filter Status:
```
Filter "Aktif" → WHERE tanggal_keluar IS NULL
Filter "Non-Aktif" → WHERE tanggal_keluar IS NOT NULL
```

## 📝 Contoh Data

### Karyawan Aktif:
```
id: 1
kode_karyawan: KRY-00001
nama_lengkap: Administrator
tanggal_masuk: 2025-01-15
tanggal_keluar: NULL  ← Masih aktif
```

### Karyawan Non-Aktif:
```
id: 2
kode_karyawan: KRY-00002
nama_lengkap: Mekanik Lama
tanggal_masuk: 2024-01-15
tanggal_keluar: 2025-11-15  ← Sudah keluar
```

## 🧪 Testing

### Test 1: Load Data
```
URL: http://localhost/aplikasi/aplikasi/_admincab/master_karyawan.php
Expected: Tabel menampilkan data karyawan dengan status yang benar
```

### Test 2: Filter Status
```
1. Buka halaman master_karyawan.php
2. Pilih Filter Status: "Aktif"
3. Klik Filter
4. Expected: Hanya karyawan dengan tanggal_keluar = NULL yang ditampilkan
```

### Test 3: Tambah Karyawan
```
1. Klik "Tambah Karyawan"
2. Isi form (tanggal_keluar kosongkan untuk aktif)
3. Klik Simpan
4. Expected: Karyawan baru ditambahkan dengan status Aktif
```

### Test 4: Edit Karyawan
```
1. Klik Edit pada karyawan
2. Isi tanggal_keluar untuk menandai keluar
3. Klik Simpan Perubahan
4. Expected: Status berubah menjadi Non-Aktif
```

## 📋 File yang Dimodifikasi

1. ✅ `master_karyawan_ajax.php` - Query dan filter
2. ✅ `master_karyawan_save.php` - INSERT dan UPDATE
3. ✅ `master_karyawan.php` - Display status
4. ✅ `master_karyawan_add.php` - Form tambah
5. ✅ `master_karyawan_edit.php` - Form edit

## 🚀 Status

**✅ SELESAI** - Semua file sudah diperbaiki sesuai dengan struktur database aktual.

Silakan test halaman dan share hasil atau error yang muncul!

---

**Last Updated:** 15 November 2025
**Status:** Ready for Testing
