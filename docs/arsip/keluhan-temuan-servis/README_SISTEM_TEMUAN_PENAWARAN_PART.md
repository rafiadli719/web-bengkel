# DOKUMENTASI SISTEM TEMUAN & PENAWARAN PART

**Fitur:** Auto-Suggest Part berdasarkan Temuan
**Versi:** 1.0
**Tanggal:** 2025-11-26

---

## 📋 DAFTAR ISI

1. [Pengenalan](#pengenalan)
2. [Fitur Utama](#fitur-utama)
3. [Cara Kerja Sistem](#cara-kerja-sistem)
4. [Instalasi & Setup](#instalasi--setup)
5. [Panduan Penggunaan](#panduan-penggunaan)
6. [Struktur Database](#struktur-database)
7. [File-File Penting](#file-file-penting)
8. [FAQ & Troubleshooting](#faq--troubleshooting)

---

## 📖 PENGENALAN

Sistem Temuan & Penawaran Part adalah fitur tambahan untuk sistem bengkel yang memungkinkan:

- ✅ Mekanik mencatat temuan hasil pengecekan kendaraan
- ✅ **Auto-suggest part** yang sesuai berdasarkan temuan (FITUR UTAMA!)
- ✅ Menawarkan part ke customer dengan approval flow
- ✅ Tracking status temuan dan penawaran
- ✅ Log activity untuk audit trail

### Manfaat Utama

1. **Efisiensi**: Admin tidak perlu cari-cari part manual
2. **Konsistensi**: Part yang ditawarkan sesuai standard bengkel
3. **Transparansi**: Customer tahu part apa saja yang perlu diganti
4. **Audit Trail**: Semua penawaran tercatat dengan lengkap
5. **Analisa**: Bisa lihat part mana yang sering ditolak customer

---

## 🎯 FITUR UTAMA

### 1. Auto-Suggest Part Berdasarkan Temuan

Ketika mekanik input temuan (misal: "Filter Udara Kotor"), sistem **otomatis menyarankan** part yang sesuai:

```
Temuan: Filter Udara Kotor
Auto-Suggest:
  ✓ Filter Udara Honda Beat (Original) - Rp 75.000 [REKOMENDASI]
  ☐ Filter Udara Honda Beat (KW) - Rp 45.000 [Alternatif]
  ☐ Filter Udara Universal - Rp 35.000 [Alternatif]
```

### 2. Jenis Perbaikan

- **Hanya Setting/Servis**: Tidak perlu penggantian part
- **Penggantian Part**: Perlu ganti part (auto-suggest aktif)

### 3. Tingkat Urgensi

- 🟢 **Rendah**: Bisa ditunda
- 🟡 **Sedang**: Perlu segera
- 🟠 **Tinggi**: Urgent
- 🔴 **Kritis**: Bahaya!

### 4. Approval Flow Penawaran Part

```
Penawaran → Pending → Customer Approve/Reject → Part masuk/tidak masuk ke servis
```

- **Approve**: Part otomatis masuk ke `tblservis_barang`
- **Reject**: Catat alasan penolakan untuk analisa

### 5. Upload Foto Temuan

Mekanik bisa upload foto kondisi kendaraan untuk ditunjukkan ke customer.

### 6. Activity Log

Semua activity tercatat:
- Temuan dibuat
- Penawaran dibuat
- Part disetujui/ditolak
- Status berubah

---

## ⚙️ CARA KERJA SISTEM

### Workflow Lengkap

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Customer datang dengan keluhan                           │
│    Admin input keluhan ke sistem                            │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Mekanik melakukan pengecekan kendaraan                   │
│    Ditemukan: Filter Udara Kotor                           │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Mekanik input temuan di sistem                          │
│    - Pilih temuan: "Filter Udara Kotor"                   │
│    - Jenis perbaikan: "Penggantian Part"                   │
│    - Upload foto (optional)                                 │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Sistem AUTO-SUGGEST part yang sesuai                    │
│    ✓ Filter Beat Original (Rp 75.000) [CHECKED]           │
│    ☐ Filter Beat KW (Rp 45.000)                           │
│    ☐ Filter Universal (Rp 35.000)                         │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Admin review & simpan                                    │
│    Penawaran part dibuat (Status: PENDING)                 │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. Admin tunjukkan penawaran ke customer                    │
│    Customer pilih: APPROVE atau REJECT                      │
└────────────────┬────────────────────────────────────────────┘
                 │
         ┌───────┴───────┐
         ▼               ▼
    [APPROVE]        [REJECT]
         │               │
         ▼               ▼
  Part masuk ke     Catat alasan
  servis_barang     penolakan
         │               │
         └───────┬───────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 7. Lanjutkan servis seperti biasa                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 INSTALASI & SETUP

### Step 1: Jalankan SQL Script

```sql
-- Jalankan file ini di database fitmotor_dbbengkel
SOURCE C:/xampp/htdocs/web-bengkel/sql_update_temuan_system.sql;

-- Atau copy-paste isi file dan execute di phpMyAdmin
```

Tabel yang dibuat:
- `tbmaster_temuan_barang_mapping` (mapping temuan → part)
- `tbservis_temuan_activity_log` (log activity)
- `tbservis_penawaran_rejection_summary` (summary reject)
- View: `view_temuan_penawaran_summary`
- View: `view_suggested_parts`
- Stored Procedure: `sp_create_penawaran_from_temuan`

### Step 2: Setup Data Master Mapping

Edit file `sql_update_temuan_system.sql`, sesuaikan sample data dengan kode barang REAL Anda:

```sql
INSERT INTO tbmaster_temuan_barang_mapping
(kode_temuan, kode_barang, is_primary, prioritas, qty_default, keterangan)
VALUES
-- SESUAIKAN DENGAN DATA REAL ANDA!
('TMN001', 'FLT-BEAT-001', 1, 1, 1, 'Filter Udara Honda Beat Original'),
('TMN001', 'FLT-BEAT-KW', 0, 2, 1, 'Filter Udara Honda Beat KW (Alternatif)');
```

**PENTING**: Ganti `'FLT-BEAT-001'` dengan kode barang yang ada di tabel `tbbarang` Anda!

### Step 3: Copy File-File PHP

Pastikan file-file berikut ada di folder `_admincab/`:

```
_admincab/
├── ajax-get-suggested-parts.php
├── servis-temuan-add-proses.php
├── servis-penawaran-approve.php
├── servis-penawaran-reject.php
├── CONTOH-INTEGRASI-TEMUAN.php
└── _template/
    ├── _servis_input_temuan.php
    └── _servis_list_temuan_penawaran.php
```

### Step 4: Integrasi ke Halaman Servis

Buka file servis yang akan ditambahkan fitur temuan (misal: `servis-input-reguler.php`).

**Tambahkan Tab Baru:**

```html
<!-- Di bagian Tab Navigation -->
<li id="tab-temuan-li">
    <a data-toggle="tab" href="#tab-temuan">
        <i class="ace-icon fa fa-search-plus orange"></i>
        Temuan & Penawaran
    </a>
</li>

<!-- Di bagian Tab Content -->
<div id="tab-temuan" class="tab-pane">
    <?php
    // Form Input Temuan
    echo '<form action="servis-temuan-add-proses.php" method="POST" enctype="multipart/form-data">';
    include '_template/_servis_input_temuan.php';
    echo '</form>';

    // List Temuan & Penawaran
    include '_template/_servis_list_temuan_penawaran.php';
    ?>
</div>
```

Lihat file `CONTOH-INTEGRASI-TEMUAN.php` untuk contoh lengkap.

### Step 5: Test Sistem

1. Buka halaman servis
2. Input keluhan customer
3. Klik tab "Temuan & Penawaran"
4. Pilih temuan, sistem auto-suggest part
5. Simpan temuan
6. Approve/Reject penawaran part

---

## 📚 PANDUAN PENGGUNAAN

### Untuk Admin/Kasir

#### 1. Input Temuan Baru

1. Buka halaman servis (misal: Servis Input Reguler)
2. Pastikan sudah ada **keluhan customer** yang terinput
3. Klik tab **"Temuan & Penawaran"**
4. Isi form:
   - **Keluhan Terkait**: Pilih keluhan customer
   - **Temuan**: Pilih dari master atau ketik manual
   - **Deskripsi**: Jelaskan detail kondisi
   - **Tingkat Urgensi**: Pilih rendah/sedang/tinggi/kritis
   - **Jenis Perbaikan**:
     - Pilih "Hanya Setting" jika tidak perlu ganti part
     - Pilih "Penggantian Part" jika perlu ganti part
   - **Upload Foto**: (Optional) untuk ditunjukkan ke customer
5. Jika pilih "Penggantian Part", sistem akan **auto-suggest** part
6. **Centang part** yang akan ditawarkan ke customer
7. Klik **"Simpan Temuan"**

#### 2. Review Penawaran Part

Setelah temuan disimpan, akan muncul di list dengan status **"PENDING"**.

1. Tunjukkan penawaran ke customer
2. Jelaskan part yang ditawarkan + harga
3. Customer pilih:
   - **Setuju**: Klik tombol ✓ (hijau)
   - **Tolak**: Klik tombol ✗ (merah), pilih alasan

#### 3. Jika Customer Setuju (APPROVE)

- Part **otomatis masuk** ke tab "Item Barang"
- Bisa lanjutkan proses servis seperti biasa
- Status penawaran: **DISETUJUI**

#### 4. Jika Customer Tolak (REJECT)

- Pilih alasan:
  1. Customer tidak mau
  2. Stok kosong
  3. Harga tidak cocok
  4. Lainnya
- Bisa tambah keterangan (optional)
- Status penawaran: **DITOLAK**
- Part **tidak masuk** ke servis

### Untuk Mekanik

1. Lakukan pengecekan kendaraan
2. Catat temuan di kertas/foto
3. Laporkan ke admin untuk diinput ke sistem
4. (Optional) Input sendiri jika punya akses

---

## 🗄️ STRUKTUR DATABASE

### Tabel Utama

#### 1. tbmaster_temuan_barang_mapping

Mapping temuan ke part yang sesuai (untuk auto-suggest).

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT | Primary key |
| kode_temuan | VARCHAR(10) | Link ke tbmaster_temuan |
| kode_barang | VARCHAR(20) | Link ke tbbarang |
| is_primary | TINYINT(1) | 1=rekomendasi, 0=alternatif |
| prioritas | INT | Urutan tampil (1=tertinggi) |
| qty_default | INT | Qty default yang disarankan |
| keterangan | VARCHAR(255) | Keterangan (Original, KW, dll) |

**Contoh Data:**

```sql
kode_temuan | kode_barang   | is_primary | prioritas | keterangan
------------|---------------|------------|-----------|------------------
TMN001      | FLT-BEAT-001  | 1          | 1         | Original (Rekomendasi)
TMN001      | FLT-BEAT-KW   | 0          | 2         | KW (Alternatif)
```

#### 2. tbservis_temuan

Temuan hasil pengecekan per servis.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT | Primary key |
| no_service | VARCHAR(50) | Nomor servis |
| keluhan_id | INT | Link ke tbservis_keluhan_status |
| kode_temuan | VARCHAR(10) | Link ke master (NULL jika custom) |
| temuan_custom | VARCHAR(255) | Temuan manual |
| deskripsi_temuan | TEXT | Deskripsi detail |
| jenis_perbaikan | ENUM | setting / penggantian_part |
| status_temuan | ENUM | ditemukan / ditawarkan / disetujui / ditolak / selesai |
| tingkat_urgensi | ENUM | rendah / sedang / tinggi / kritis |
| foto_temuan | VARCHAR(255) | Path foto |
| mekanik_id | VARCHAR(10) | Mekanik yang menemukan |

#### 3. tbservis_penawaran_part

Penawaran part ke customer.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT | Primary key |
| no_service | VARCHAR(50) | Nomor servis |
| temuan_id | INT | Link ke tbservis_temuan |
| kode_barang | VARCHAR(20) | Kode part |
| quantity | INT | Jumlah |
| harga_satuan | DECIMAL | Harga per unit |
| total_harga | DECIMAL | Total |
| status_penawaran | ENUM | pending / disetujui / ditolak |
| is_from_suggestion | TINYINT(1) | 1=dari auto-suggest, 0=manual |
| alasan_tolak | ENUM | Alasan jika ditolak |
| keterangan_tolak | TEXT | Detail alasan |

---

## 📁 FILE-FILE PENTING

### Backend PHP

| File | Fungsi |
|------|--------|
| `ajax-get-suggested-parts.php` | AJAX handler untuk load suggested parts |
| `servis-temuan-add-proses.php` | Proses insert temuan & create penawaran part |
| `servis-penawaran-approve.php` | Approve penawaran (add part to servis) |
| `servis-penawaran-reject.php` | Reject penawaran (catat alasan) |

### Template

| File | Fungsi |
|------|--------|
| `_servis_input_temuan.php` | Form input temuan dengan auto-suggest |
| `_servis_list_temuan_penawaran.php` | List temuan & penawaran per servis |

### Database

| File | Fungsi |
|------|--------|
| `sql_update_temuan_system.sql` | SQL script create tables & views |

### Dokumentasi

| File | Fungsi |
|------|--------|
| `README_SISTEM_TEMUAN_PENAWARAN_PART.md` | Dokumentasi lengkap (file ini) |
| `CONTOH-INTEGRASI-TEMUAN.php` | Contoh integrasi ke halaman servis |

---

## ❓ FAQ & TROUBLESHOOTING

### Q1: Auto-suggest part tidak muncul?

**A:** Cek hal berikut:

1. **Buka Console Browser** (F12 → Console tab)
   - Lihat ada error JavaScript?
   - Lihat response AJAX dari `ajax-get-suggested-parts.php`

2. **Cek Data Mapping**
   ```sql
   SELECT * FROM tbmaster_temuan_barang_mapping;
   ```
   - Pastikan ada data
   - Pastikan `kode_temuan` sesuai dengan yang dipilih
   - Pastikan `kode_barang` ada di tabel `tbbarang`

3. **Cek File AJAX Handler**
   - Pastikan `ajax-get-suggested-parts.php` ada di folder `_admincab/`
   - Test akses langsung: `http://localhost/web-bengkel/_admincab/ajax-get-suggested-parts.php`

### Q2: Part tidak masuk ke servis_barang saat approve?

**A:** Cek hal berikut:

1. **Lihat Error Message**
   - Sistem akan tampilkan alert error jika ada masalah

2. **Cek MySQL Error Log**
   ```sql
   SHOW ERRORS;
   ```

3. **Cek Struktur Tabel**
   - Pastikan tabel `tblservis_barang` punya kolom yang sesuai
   - Cek file `servis-penawaran-approve.php` line ~40-50

### Q3: Form submit tapi tidak ada respon?

**A:** Cek hal berikut:

1. **Cek Form Action**
   ```html
   <form action="servis-temuan-add-proses.php" method="POST">
   ```
   - Pastikan mengarah ke file yang benar

2. **Cek File Permission**
   - File harus readable (644 atau 755)

3. **Cek PHP Error**
   - Aktifkan error display:
     ```php
     ini_set('display_errors', 1);
     error_reporting(E_ALL);
     ```

### Q4: Foto temuan tidak bisa diupload?

**A:** Cek hal berikut:

1. **Cek Folder Exists**
   ```
   _admincab/../file_upload/temuan/
   ```
   - Buat folder jika belum ada

2. **Cek Permission Folder**
   ```bash
   chmod 755 file_upload/temuan/
   ```
   - Atau 777 jika 755 tidak work

3. **Cek PHP Upload Settings**
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```

### Q5: Bagaimana cara menambah mapping temuan-part baru?

**A:** Insert manual ke database:

```sql
INSERT INTO tbmaster_temuan_barang_mapping
(kode_temuan, kode_barang, is_primary, prioritas, qty_default, keterangan, status_aktif)
VALUES
('TMN001', 'BRG-NEW-001', 0, 3, 1, 'Part Baru (Alternatif)', 1);
```

Atau buat halaman CRUD (bonus feature, belum dibuat di tutorial ini).

---

## 🎓 TIPS & BEST PRACTICES

### 1. Setup Data Mapping dengan Baik

- **Part PRIMARY** = Part rekomendasi bengkel (Original, kualitas terbaik)
- **Part Alternatif** = Part cadangan (KW, harga lebih murah)
- Urutkan `prioritas` dari yang paling direkomendasikan

### 2. Konsisten Input Temuan

- Gunakan master temuan sebisa mungkin (jangan manual terus)
- Master temuan bisa di-maintain untuk konsistensi
- Foto temuan sangat membantu customer understand kondisi

### 3. Follow Up Penawaran Ditolak

- Cek report part yang sering ditolak
- Evaluasi: harga terlalu mahal? Stok sering kosong?
- Improve mapping atau stock management

### 4. Training User

- Berikan training ke admin & mekanik
- Jelaskan manfaat sistem untuk efisiensi kerja
- Minta feedback untuk improvement

---

## 📊 FITUR LANJUTAN (Bisa Dikembangkan)

1. **Dashboard Analisa Temuan**
   - Temuan terbanyak per periode
   - Part yang paling sering ditawarkan
   - Tingkat approval rate

2. **Laporan Excel**
   - Export temuan & penawaran per periode
   - Analisa rejection rate

3. **Notifikasi**
   - WhatsApp notification ke customer (foto temuan)
   - Email approval penawaran

4. **Approval Multi-Level**
   - Mekanik → Supervisor → Customer
   - Approval history tracking

5. **Integration**
   - Link ke inventory untuk cek stok real-time
   - Link ke supplier untuk indent part

---

## 📞 SUPPORT

Jika ada pertanyaan atau issue:

1. Cek dokumentasi ini dulu
2. Cek file `CONTOH-INTEGRASI-TEMUAN.php` untuk referensi
3. Debug dengan browser console (F12)
4. Hubungi developer sistem

---

**Selamat menggunakan Sistem Temuan & Penawaran Part! 🚗🔧**

---

_Dokumentasi ini dibuat pada 2025-11-26_
_Version 1.0_
