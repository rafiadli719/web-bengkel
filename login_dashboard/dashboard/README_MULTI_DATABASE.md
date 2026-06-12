# Multi-Database Integration untuk Dashboard User

## Deskripsi
Sistem ini telah diintegrasikan untuk mengambil data karyawan dari dua database berbeda:
- **Database Kasir** (`fitmotor_maintance-beta`)
- **Database Absensi** (`fitmotor_prototype`)

## File yang Ditambahkan/Dimodifikasi

### 1. File Baru:
- `multi_database_connection.php` - Class untuk mengelola koneksi ke kedua database
- `get_employees_multi.php` - Endpoint untuk mengambil data karyawan dari kedua database
- `README_MULTI_DATABASE.md` - Dokumentasi ini

### 2. File yang Dimodifikasi:
- `dashboard_user.php` - Diupdate untuk menggunakan multi-database connection

## Struktur Database yang Diasumsikan

### Database Kasir (fitmotor_maintance-beta):
```sql
Table: users
- kode_karyawan
- nama_karyawan
- role
- nama_cabang
```

### Database Absensi (fitmotor_prototype):
```sql
Table: employees
- employee_id (mapped ke kode_karyawan)
- employee_name (mapped ke nama_karyawan)
- position (mapped ke role)
- branch (mapped ke nama_cabang)
```

## Fitur Multi-Database

### 1. **Kombinasi Data Karyawan**
- Mengambil karyawan dari database kasir dan absensi
- Menghapus duplikat berdasarkan kode_karyawan
- Mengurutkan berdasarkan nama karyawan

### 2. **Kombinasi Cabang**
- Mengambil daftar cabang dari kedua database
- Menghapus duplikat nama cabang
- Mengurutkan secara alfabetis

### 3. **Error Handling**
- Fallback ke database tunggal jika terjadi error
- Logging error untuk debugging
- Notifikasi user-friendly

## Cara Menggunakan

### 1. Pastikan Struktur Database Sesuai
Jika struktur tabel `employees` di database absensi berbeda, silakan sesuaikan query di file `multi_database_connection.php` pada method:
- `getAbsensiEmployees()`
- `getAbsensiBranches()`

### 2. Konfigurasi Database
Update kredensial database di `multi_database_connection.php` jika diperlukan:
```php
private $host = 'localhost';
private $username = 'fitmotor_LOGIN';
private $password = 'Sayalupa12';
private $kasir_db = 'fitmotor_maintance-beta';
private $absensi_db = 'fitmotor_prototype';
```

### 3. Testing
Untuk test sistem:
1. Akses halaman dashboard user
2. Pilih "Pengaturan User Otoritas" (hanya untuk super_admin)
3. Pilih cabang dari dropdown - seharusnya muncul cabang dari kedua database
4. Data karyawan akan dimuat dari kedua database

## Troubleshooting

### Error "Database connection failed"
- Pastikan kedua database exist
- Periksa kredensial database
- Periksa apakah service MySQL berjalan

### Data karyawan tidak muncul
- Periksa struktur tabel sesuai dokumentasi
- Periksa log error di `error_log` file
- Pastikan ada data di kedua database

### Duplikat data
- System otomatis menghapus duplikat berdasarkan `kode_karyawan`
- Jika masih ada duplikat, periksa konsistensi data

## Monitoring
System akan mencatat error di log file untuk monitoring:
- Connection errors
- Query errors
- Data inconsistencies

## Future Enhancements
- Cache untuk performa lebih baik
- Database connection pooling
- Real-time sync antara database
- Admin interface untuk mapping field database