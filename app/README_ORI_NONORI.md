# Sistem Klasifikasi ORI/NON-ORI - Web Bengkel

## Overview
Sistem klasifikasi baru untuk membedakan part Original (ORI) dari pabrikan resmi dengan part Aftermarket/Imitasi (NON-ORI).

## Fitur Utama

### 1. Klasifikasi Item
- **ORI (Genuine Part)**: Part asli dari pabrikan resmi (Honda, Yamaha, Suzuki, Kawasaki)
- **NON-ORI (Aftermarket/Imitasi)**: Part pengganti dari produsen lain

### 2. ORI Item Features
- Pilihan merek pabrikan (Honda, Yamaha, Suzuki, Kawasaki)
- Input kode part number resmi
- Link validasi ke website pabrikan
- Validasi format part number berdasarkan brand
- Nama part sesuai catalog resmi

### 3. NON-ORI Item Features  
- Auto-generate code dengan format: IM-XXYYYY
  - IM = Prefix untuk imitasi
  - XX = Kategori (KB, EL, RM, MS, CV, RD, CR, FL, CH, BD)
  - YYYY = Nomor urut 4 digit
- Format nama otomatis: [Nama Part] [Penggunaan Motor] IMI
- Kategorisasi berdasarkan jenis part (Kabel, Kelistrikan, Rem, dll)

### 4. Validation System
- Status validasi: pending_validation, validated, rejected
- Admin validation workflow untuk NON-ORI items
- Tracking log untuk semua perubahan status

## File yang Dibuat/Dimodifikasi

### 1. Frontend Files
- `barang_add_improved.php` - Form tambah item dengan klasifikasi ORI/NON-ORI
- `barang_list_improved.php` - Listing item dengan filter dan tampilan card
- `get_next_code.php` - AJAX helper untuk preview auto-generated code

### 2. Backend Files
- `save_barang_improved.php` - Handler untuk menyimpan item baru
- `migrate_database.php` - Script migrasi database dengan UI
- `migrate_standalone.php` - Script migrasi standalone

### 3. Database Changes
- Tambah kolom baru di `tblitem`:
  - `tipe_item` - ENUM('ORI', 'NON_ORI')
  - `merek` - VARCHAR(50) untuk brand ORI
  - `kode_part_resmi` - VARCHAR(50) untuk part number ORI
  - `nama_part_resmi` - VARCHAR(100) untuk nama resmi ORI
  - `penggunaan_motor` - VARCHAR(100) untuk penggunaan NON-ORI
  - `merek_tipe` - VARCHAR(100) untuk merek/tipe NON-ORI
  - `kategori_rak` - VARCHAR(10) untuk kategori NON-ORI
  - `status_validasi` - ENUM validation status
  - `created_by`, `validated_by` - INT untuk tracking user
  - `created_at`, `updated_at` - TIMESTAMP
  
- Table baru:
  - `tbkategori_rak` - Master kategori untuk NON-ORI
  - `tbitem_validation_log` - Log tracking validasi
  
- View baru:
  - `view_item_classified` - View dengan join semua data terkait

## Kategori NON-ORI
- **KB** - Kabel
- **EL** - Kelistrikan  
- **RM** - Rem
- **MS** - Mesin
- **CV** - CVT
- **RD** - Roda
- **CR** - Carbu
- **FL** - Filter
- **CH** - Cairan
- **BD** - Baud

## Format Kode Auto-Generate
Contoh kode yang dihasilkan:
- IM-KB0001 - Kabel pertama
- IM-EL0002 - Kelistrikan kedua
- IM-RM0001 - Rem pertama

## Format Nama NON-ORI
Contoh format nama yang dihasilkan:
- KABEL GAS H. BEAT IMI
- KAMPAS REM DEPAN VARIO IMI
- FILTER UDARA BEAT IMI

## Validasi Part Number ORI
Sistem validasi format part number berdasarkan brand:
- **Honda**: XXXXX-XXX-XXX (contoh: 06455-KVB-900)
- **Yamaha**: XXX-XXXXX-XX (contoh: 5SL-F5885-00)
- **Suzuki**: XXXXX-XXXXX (contoh: 09401-12127)
- **Kawasaki**: XXXXX-XXXX (contoh: 11061-1485)

## Link Validasi Pabrikan
- Honda: https://www.honda.co.jp/parts/
- Yamaha: https://global.yamaha-motor.com/
- Suzuki: https://www.suzuki.co.jp/
- Kawasaki: https://www.kawasaki.com/

## Cara Penggunaan

### 1. Menambah Item ORI
1. Pilih "ORI (Genuine Part)"
2. Pilih merek pabrikan
3. Masukkan kode part resmi
4. Validasi menggunakan link yang disediakan
5. Masukkan nama part resmi
6. Isi data umum (jenis, satuan, harga)
7. Submit

### 2. Menambah Item NON-ORI
1. Pilih "NON ORI (Aftermarket/Imitasi)"
2. Masukkan nama part
3. Masukkan penggunaan motor
4. Masukkan merek/tipe
5. Pilih kategori rak
6. System akan auto-generate code dan format nama
7. Isi data umum (jenis, satuan, harga)
8. Submit

### 3. Melihat Daftar Item
1. Akses `barang_list_improved.php`
2. Filter berdasarkan tipe, status, merek, atau kategori
3. Search berdasarkan kode atau nama
4. View detail dan edit item

## Database Migration
Jalankan `migrate_database.php` atau `migrate_standalone.php` untuk:
1. Menambah kolom baru ke tblitem
2. Membuat table tbkategori_rak dan tbitem_validation_log
3. Membuat view view_item_classified
4. Mengupdate data existing dengan klasifikasi otomatis

## Security & Validation
- Validasi input dengan mysqli_real_escape_string
- Transaction handling untuk data integrity
- Duplicate code checking
- Format validation untuk part numbers
- User authentication required
- Audit trail untuk semua perubahan

## Future Enhancements
- Barcode generation untuk setiap item
- Integrasi dengan sistem inventory
- Export/Import functionality
- Advanced reporting dashboard
- Mobile responsive interface
- API untuk integrasi dengan sistem lain

---
**Dibuat oleh**: Claude Code AI Assistant  
**Tanggal**: 2025-01-15  
**Versi**: 1.0