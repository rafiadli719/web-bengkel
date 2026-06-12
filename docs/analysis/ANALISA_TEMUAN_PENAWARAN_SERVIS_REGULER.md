# ANALISA SISTEM TEMUAN & PENAWARAN PART - SERVIS REGULER

**File Analisa**: servis-input-reguler.php
**Tanggal Analisa**: 2025-12-04
**Database**: fitmotor_dbbengkel.sql

---

## 1. RINGKASAN EKSEKUTIF

Sistem Temuan & Penawaran Part adalah fitur dalam modul servis reguler yang memungkinkan mekanik/service advisor untuk:
1. Mencatat temuan hasil pengecekan kendaraan
2. Menawarkan part/sparepart kepada pelanggan berdasarkan temuan
3. Mengelola approval/rejection penawaran part
4. Mengintegrasikan dengan Work Order yang memiliki item barang/jasa

---

## 2. STRUKTUR DATABASE

### 2.1 Tabel Utama: `tbservis_temuan`

**Fungsi**: Menyimpan temuan hasil pengecekan kendaraan oleh mekanik

**Struktur Kolom**:
```sql
CREATE TABLE `tbservis_temuan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `keluhan_id` int(11) DEFAULT NULL COMMENT 'Link ke tbservis_keluhan_status',
  `mekanik_id` varchar(10) DEFAULT NULL COMMENT 'Mekanik yang menemukan',
  `mekanik_name` varchar(100) DEFAULT NULL,
  `kode_temuan` varchar(10) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan',
  `temuan_custom` varchar(255) DEFAULT NULL COMMENT 'Temuan manual jika tidak ada di master',
  `deskripsi_temuan` text DEFAULT NULL,
  `jenis_perbaikan` enum('setting','penggantian_part') NOT NULL DEFAULT 'setting',
  `status_temuan` enum('ditemukan','ditawarkan','disetujui','ditolak','selesai') DEFAULT 'ditemukan',
  `keterangan_tidak_selesai` text DEFAULT NULL,
  `tingkat_urgensi` enum('rendah','sedang','tinggi','kritis') DEFAULT 'sedang',
  `estimasi_biaya` double DEFAULT 0,
  `biaya_actual` double DEFAULT 0,
  `foto_temuan` varchar(255) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Field Krusial**:
- `keluhan_id`: Menghubungkan temuan dengan keluhan pelanggan
- `kode_temuan`: Referensi ke master temuan (standardisasi)
- `temuan_custom`: Untuk temuan yang tidak ada di master
- `jenis_perbaikan`: Menentukan apakah perlu ganti part atau hanya setting
- `status_temuan`: Lifecycle status temuan

**Status Lifecycle**:
1. `ditemukan` - Baru ditemukan oleh mekanik
2. `ditawarkan` - Sudah ditawarkan ke customer (ada penawaran part terkait)
3. `disetujui` - Customer setuju untuk diperbaiki
4. `ditolak` - Customer menolak perbaikan
5. `selesai` - Perbaikan sudah selesai dilakukan

---

### 2.2 Tabel Penawaran: `tbservis_penawaran_part`

**Fungsi**: Menyimpan penawaran part ke pelanggan berdasarkan temuan

**Struktur Kolom**:
```sql
CREATE TABLE `tbservis_penawaran_part` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `temuan_id` int(11) DEFAULT NULL COMMENT 'Link ke tbservis_temuan',
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari auto-suggest, 0=manual',
  `suggestion_priority` int(11) DEFAULT NULL COMMENT 'Prioritas dari suggestion (1=primary)',
  `kode_barang` varchar(20) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` double NOT NULL,
  `total_harga` double NOT NULL,
  `stok_tersedia` int(11) DEFAULT NULL COMMENT 'Stok saat penawaran dibuat',
  `estimasi_ketersediaan` varchar(50) DEFAULT NULL,
  `status_penawaran` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `alasan_tolak` enum('customer_tidak_mau','stok_bengkel_kosong','stok_supplier_kosong','harga_tidak_cocok','lainnya') DEFAULT NULL,
  `keterangan_tolak` text DEFAULT NULL,
  `catatan_penawaran` text DEFAULT NULL,
  `discount_persen` decimal(5,2) DEFAULT 0.00,
  `discount_nominal` decimal(15,2) DEFAULT 0.00,
  `harga_final` decimal(15,2) DEFAULT NULL,
  `tanggal_penawaran` datetime DEFAULT current_timestamp(),
  `tanggal_respon` datetime DEFAULT NULL,
  `user_penawaran` varchar(50) DEFAULT NULL,
  `user_respon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Field Krusial**:
- `temuan_id`: Link ke temuan yang menjadi dasar penawaran
- `is_from_suggestion`: Flag untuk tracking apakah dari auto-suggest atau manual
- `status_penawaran`: pending/disetujui/ditolak
- `alasan_tolak`: Enum untuk alasan penolakan (untuk analytics)
- `user_penawaran` & `user_respon`: Tracking siapa yang menawarkan dan merespon

**Status Lifecycle**:
1. `pending` - Menunggu keputusan customer/admin
2. `disetujui` - Approved, part akan ditambahkan ke tblservis_barang
3. `ditolak` - Rejected, tidak akan masuk ke servis

---

### 2.3 Tabel Pending Items: `tbservis_pending_items`

**Fungsi**: Menyimpan item dari Work Order yang perlu approval sebelum masuk ke servis

**Struktur Kolom**:
```sql
CREATE TABLE `tbservis_pending_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `wo_id` int(11) DEFAULT NULL COMMENT 'Link ke tbservis_workorder',
  `kode_item` varchar(50) NOT NULL,
  `nama_item` varchar(255) NOT NULL,
  `tipe` enum('barang','jasa') NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `harga_satuan` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `waktu` int(11) DEFAULT 0 COMMENT 'Waktu untuk jasa (menit)',
  `status_approval` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `alasan_tolak` enum('stok_cabang_kosong','stok_supplier_kosong','customer_tidak_mau','lainnya') DEFAULT NULL,
  `keterangan_tolak` text DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Field Krusial**:
- `wo_id`: Link ke Work Order yang menambahkan item ini
- `tipe`: barang atau jasa
- `status_approval`: pending/disetujui/ditolak

**Perbedaan dengan tbservis_penawaran_part**:
- `tbservis_pending_items`: Item dari Work Order (otomatis)
- `tbservis_penawaran_part`: Penawaran manual berdasarkan temuan

---

### 2.4 Tabel Servis Barang: `tblservis_barang`

**Fungsi**: Menyimpan barang/part yang benar-benar digunakan dalam servis

**Struktur Kolom**:
```sql
CREATE TABLE `tblservis_barang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `nobaris` int(11) NOT NULL,
  `no_item` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `qty_retur` int(11) NOT NULL,
  `harga_jual` double NOT NULL,
  `potongan` double NOT NULL,
  `total` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

**Relasi**:
- Item masuk ke tabel ini SETELAH:
  1. Penawaran disetujui (dari tbservis_penawaran_part)
  2. Pending item disetujui (dari tbservis_pending_items)
  3. Manual add dari tab Item Barang

---

### 2.5 Tabel Servis Jasa: `tblservis_jasa`

**Fungsi**: Menyimpan jasa yang digunakan dalam servis

**Struktur Kolom**:
```sql
CREATE TABLE `tblservis_jasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `nobaris` int(11) NOT NULL,
  `no_item` varchar(50) NOT NULL,
  `waktu` int(11) NOT NULL,
  `harga` double NOT NULL,
  `potongan` double NOT NULL,
  `total` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

---

### 2.6 Tabel Work Order Header: `tbworkorderheader`

**Fungsi**: Master paket Work Order (template servis)

**Struktur Kolom**:
```sql
CREATE TABLE `tbworkorderheader` (
  `kode_wo` varchar(10) NOT NULL,
  `nama_wo` varchar(50) NOT NULL,
  `keterangan` varchar(100) NOT NULL,
  `status` varchar(1) NOT NULL DEFAULT '0',
  `waktu` int(11) NOT NULL,
  `harga` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

**Contoh Data**:
- WO0001: SERVIS STANDAR MATIC/BEBEK (45 menit, Rp 62.000)
- WO0002: ONGKOS GANTI KAMPAS BELAKANG (30 menit, Rp 15.000)

---

### 2.7 Tabel Work Order Detail: `tbworkorderdetail`

**Fungsi**: Detail item yang termasuk dalam Work Order

**Struktur Kolom**:
```sql
CREATE TABLE `tbworkorderdetail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_wo` varchar(10) NOT NULL,
  `kode_barang` varchar(20) NOT NULL,
  `tipe` varchar(1) NOT NULL COMMENT '1=jasa, 2=barang',
  `jumlah` int(11) NOT NULL,
  `harga` double NOT NULL,
  `total` double NOT NULL,
  `satuan` varchar(20) NOT NULL,
  `diskon` int(11) NOT NULL,
  `status_diskon` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

**Catatan Penting**:
- `tipe = '1'`: Item adalah jasa
- `tipe = '2'`: Item adalah barang

---

### 2.8 Tabel Servis Work Order: `tbservis_workorder`

**Fungsi**: Link antara service dengan Work Order yang digunakan

**Struktur Kolom**:
```sql
CREATE TABLE `tbservis_workorder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `kode_wo` varchar(10) NOT NULL,
  `status_pengerjaan` enum('diproses','selesai','tidak_selesai') DEFAULT 'diproses',
  `keterangan_tidak_selesai` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

---

## 3. ALUR PROSES BISNIS

### 3.1 Alur Umum Servis Reguler

```
1. Customer datang → Input data service (Detail Service tab)
   - No service auto-generate
   - Input data pelanggan & kendaraan
   - Input keluhan awal

2. Pilih Work Order → Work Order tab
   - Pilih paket WO (misal: Servis Rutin)
   - Item WO masuk ke PENDING (butuh approval)

3. Mekanik cek kendaraan → Temuan & Penawaran tab
   - Catat temuan hasil pengecekan
   - Buat penawaran part jika diperlukan

4. Admin/SA review → Approve/Reject
   - Review pending items dari WO
   - Review penawaran part
   - Yang approved masuk ke Item Barang/Jasa

5. Eksekusi servis → Item Barang & Item Jasa tab
   - Lihat semua part & jasa yang akan digunakan
   - Bisa tambah manual jika perlu

6. Penyelesaian → Actions tab
   - Hitung total
   - Proses pembayaran
   - Cetak invoice
```

---

### 3.2 Alur Detail: TEMUAN

**File Handler**: `_handler_temuan_penawaran.php` (baris 192-291)

#### A. Input Temuan Baru

**Trigger**: POST button `btnaddtemuan`

**Input Fields**:
- `no_service`: Nomor service
- `keluhan_id`: ID keluhan terkait (optional)
- `kode_temuan`: Kode dari master temuan (optional)
- `nama_temuan`: Nama temuan (jika custom)
- `jenis_perbaikan`: 'setting' atau 'penggantian_part'
- `tingkat_urgensi`: 'rendah', 'sedang', 'tinggi', 'kritis'
- `deskripsi_temuan`: Deskripsi detail
- `estimasi_biaya`: Estimasi biaya (default 0)

**Proses**:
```php
// File: _handler_temuan_penawaran.php baris 192-226
1. Validasi: nama_temuan harus diisi
2. Insert ke tbservis_temuan dengan status = 'ditemukan'
3. Redirect ke tab temuan dengan alert success
```

**Query Insert**:
```sql
INSERT INTO tbservis_temuan
(no_service, keluhan_id, kode_temuan, temuan_custom, deskripsi_temuan,
 jenis_perbaikan, status_temuan, tingkat_urgensi, estimasi_biaya)
VALUES
('SV...', keluhan_id, kode_temuan, nama_temuan, deskripsi,
 jenis_perbaikan, 'ditemukan', tingkat_urgensi, estimasi_biaya)
```

#### B. Update Status Temuan

**Trigger**: POST button `btnupdatestatustemuan`

**Status Yang Bisa Diset**:
- `ditemukan`
- `ditawarkan`
- `disetujui`
- `ditolak`
- `selesai`

**Logika Khusus** (baris 247-268):
- Jika status = 'tidak_selesai', wajib isi keterangan
- Keterangan akan dicatat ke tblservice.catatan
- Format catatan: `[TEMUAN TIDAK SELESAI] {nama_temuan}: {keterangan}`

#### C. Delete Temuan

**Trigger**: POST button `btndeletetemuan`

**Proses**:
```sql
DELETE FROM tbservis_temuan WHERE id = {temuan_id}
```

**Catatan**: Hard delete, tidak ada soft delete

---

### 3.3 Alur Detail: PENAWARAN PART

**File Handler**: `_handler_temuan_penawaran.php` (baris 297-467)

#### A. Tambah Penawaran

**Trigger**: POST button `btnaddpenawaran`

**Input Fields**:
- `no_service`: Nomor service
- `temuan_id`: ID temuan terkait (optional)
- `kode_barang`: Kode part
- `nama_barang`: Nama part
- `quantity`: Jumlah
- `harga_satuan`: Harga per unit
- `user_penawaran`: User yang membuat penawaran

**Validasi & Pencegahan Duplikasi** (baris 314-340):

1. **Cek di tblservis_barang**:
   ```sql
   SELECT COUNT(*) FROM tblservis_barang
   WHERE no_service = '{no_service}'
   AND no_item = '{kode_barang}'
   ```
   - Jika sudah ada → REJECT dengan pesan duplikasi

2. **Cek penawaran aktif**:
   ```sql
   SELECT COUNT(*) FROM tbservis_penawaran_part
   WHERE no_service = '{no_service}'
   AND kode_barang = '{kode_barang}'
   AND status_penawaran IN ('pending','disetujui')
   ```
   - Jika sudah ada → REJECT dengan pesan duplikasi

**Proses Insert** (baris 350-376):
```sql
INSERT INTO tbservis_penawaran_part
(no_service, temuan_id, kode_barang, nama_barang, quantity,
 harga_satuan, total_harga, status_penawaran, user_penawaran)
VALUES
('{no_service}', {temuan_id}, '{kode_barang}', '{nama_barang}',
 {quantity}, {harga_satuan}, {total_harga}, 'pending', '{user}')
```

**Catatan Penting**:
- Semua penawaran default status = 'pending'
- Stok selalu di-set 0 (sistem tidak tracking stok real-time)
- Status temuan TIDAK diubah otomatis

#### B. Approve Penawaran

**Trigger**: POST button `btnsetujuipenawaran`

**Lokasi Kode**:
- File handler: baris 380-420
- File main: servis-input-reguler.php baris 784-921

**Proses di Handler** (baris 380-420):
```php
1. Get data penawaran by ID
2. Update status penawaran = 'disetujui'
3. Set tanggal_respon = NOW()
4. Set user_respon = current user
5. Auto-add ke tblservis_barang:
   - Get nobaris terbesar + 1
   - Insert dengan potongan = 0
   - qty_retur = 0
```

**Proses di Main File** (baris 784-921):
```php
1. Get penawaran data
2. Check if item sudah ada di tblservis_barang
3. Jika belum ada:
   - Coba get dari tblitem untuk validasi
   - Get max nobaris
   - Insert ke tblservis_barang
4. Update status penawaran = 'disetujui'
5. Redirect ke tab temuan
```

**Query Insert ke tblservis_barang**:
```sql
INSERT INTO tblservis_barang
(no_service, nobaris, no_item, quantity, qty_retur,
 harga_jual, potongan, total)
VALUES
('{no_service}', {nobaris}, '{kode_barang}', {quantity},
 0, {harga_satuan}, 0, {total_harga})
```

#### C. Reject Penawaran

**Trigger**: POST button `btnrejectpenawaran` atau `btntolakpenawaran`

**Input Fields**:
- `penawaran_id`: ID penawaran
- `alasan_tolak`: Enum value
  - customer_tidak_mau
  - stok_bengkel_kosong
  - stok_supplier_kosong
  - harga_tidak_cocok
  - lainnya
- `keterangan_tolak`: Keterangan tambahan (text)

**Proses** (baris 423-452):
```php
1. Update tbservis_penawaran_part:
   - status_penawaran = 'ditolak'
   - alasan_tolak = {alasan}
   - keterangan_tolak = {keterangan}
   - tanggal_respon = NOW()
   - user_respon = current user

2. Redirect dengan alert
```

**Catatan**:
- Tidak ada catatan ke tblservice (berbeda dengan reject pending items)
- Status temuan TIDAK diubah

#### D. Delete Penawaran

**Trigger**: POST button `btndeletepenawaran`

**Proses** (baris 455-467):
```sql
DELETE FROM tbservis_penawaran_part WHERE id = {penawaran_id}
```

**Catatan**: Hard delete, tidak ada soft delete

---

### 3.4 Alur Detail: PENDING ITEMS (dari Work Order)

**File**: servis-input-reguler.php baris 439-614

#### A. Tambah Work Order ke Servis

**Trigger**: POST button `btnaddworkorder`

**Input Fields**:
- `no_service`: Nomor service
- `kode_wo`: Kode work order

**Proses** (baris 439-614):

1. **Validasi Work Order** (baris 452-461):
   ```sql
   -- Cek duplikasi
   SELECT COUNT(*) FROM tbservis_workorder
   WHERE no_service = '{no_service}'
   AND kode_wo = '{kode_wo}'

   -- Cek exist di master
   SELECT COUNT(*) FROM tbworkorderheader
   WHERE kode_wo = '{kode_wo}'
   ```

2. **Insert ke tbservis_workorder** (baris 463-466):
   ```sql
   INSERT INTO tbservis_workorder
   (no_service, kode_wo, status_pengerjaan)
   VALUES ('{no_service}', '{kode_wo}', 'diproses')
   ```

3. **Get Detail Items dari WO** (baris 478-502):
   ```sql
   SELECT kode_barang, tipe, harga, total, jumlah
   FROM tbworkorderdetail
   WHERE kode_wo = '{kode_wo}'
   ```

   **Fallback untuk Kode Padding** (baris 483-501):
   - WO0001 → cari juga WO1, WO01, WO001, WO0001, WO00001
   - Untuk handle inconsistency di database

4. **Auto-add ke PENDING ITEMS** (baris 504-575):
   ```php
   foreach (item dari WO detail):
     - Get nama item dari tblitem_jasa atau tblitem
     - Check duplikasi di tbservis_pending_items
     - Insert dengan status_approval = 'pending'
   ```

**Query Insert Pending Item**:
```sql
INSERT INTO tbservis_pending_items
(no_service, wo_id, kode_item, nama_item, tipe, quantity,
 harga_satuan, total, waktu, status_approval)
VALUES
('{no_service}', {wo_id}, '{kode_item}', '{nama_item}',
 '{tipe}', {quantity}, {harga_satuan}, {total},
 {waktu}, 'pending')
```

**Alert Message**:
- Jika WO tidak punya detail: "tidak ada item yang perlu di-approve"
- Jika ada item: "{X} item masuk ke Tab Temuan & Penawaran sebagai penawaran pending"

#### B. Approve Pending Item

**Trigger**: POST button `btnapprove_wo_item`

**Proses** (baris 617-697):

1. **Get Pending Item Data**:
   ```sql
   SELECT * FROM tbservis_pending_items
   WHERE id = '{pending_item_id}'
   AND no_service = '{no_service}'
   AND status_approval = 'pending'
   ```

2. **Insert ke Tabel Final**:
   - Jika tipe = 'barang':
     ```sql
     INSERT INTO tblservis_barang
     (no_service, no_item, quantity, qty_retur,
      harga_jual, potongan, total)
     VALUES (...)
     ```

   - Jika tipe = 'jasa':
     ```sql
     INSERT INTO tblservis_jasa
     (no_service, no_item, harga, waktu, potongan, total)
     VALUES (...)
     ```

3. **Update Status**:
   ```sql
   UPDATE tbservis_pending_items
   SET status_approval = 'disetujui',
       updated_at = NOW()
   WHERE id = '{pending_item_id}'
   ```

**Catatan**:
- Check duplikasi sebelum insert
- Handle kolom `waktu` yang mungkin tidak ada di tblservis_jasa

#### C. Reject Pending Item

**Trigger**: POST button `btnreject_wo_item`

**Input Fields**:
- `pending_item_id`: ID item
- `alasan_reject`: Enum value
  - customer_tidak_mau
  - stok_cabang_kosong
  - stok_supplier_kosong
  - lainnya
- `keterangan_reject`: Keterangan tambahan

**Proses** (baris 701-778):

1. **Update Status Pending Item**:
   ```sql
   UPDATE tbservis_pending_items
   SET status_approval = 'ditolak',
       alasan_tolak = '{alasan}',
       keterangan_tolak = '{keterangan}',
       updated_at = NOW()
   WHERE id = '{pending_item_id}'
   ```

2. **Catat ke tblservice.keterangan** (baris 725-758):
   ```php
   Format catatan:
   [ITEM DITOLAK] {tipe}: {nama_item} ({kode_item})
   Alasan: {alasan_text}
   Keterangan: {keterangan}
   Tanggal: {dd/mm/yyyy HH:ii}
   ```

**Mapping Alasan**:
```php
'customer_tidak_mau' => 'Pelanggan tidak setuju'
'stok_cabang_kosong' => 'Stok barang di bengkel tidak ada'
'stok_supplier_kosong' => 'Stok barang di supplier tidak ada'
'lainnya' => 'Lainnya'
```

---

### 3.5 Alur Integrasi: Auto-Suggest Part

**File Handler**: `_handler_temuan_penawaran.php` baris 92-185

**Endpoint**: AJAX `action=get_recommended_parts_for_temuan`

**Input**: `temuan` (text deskripsi temuan)

**Proses**:

#### Strategi 1: Match by Kategori (baris 108-144)

```php
1. Cari di master kategori (tbmaster_kategori_fastmoves)
2. Match nama_kategori dengan temuan (case-insensitive)
3. Jika match:
   - Get parts dari tbmaster_barang_fastmoves
   - WHERE kode_kategori = matched_kategori
   - ORDER BY urutan, nama_barang
   - LIMIT 50
4. Return dengan strategy = 'kategori'
```

**Contoh**:
- Temuan: "oli kotor perlu diganti"
- Match kategori: "oli"
- Return: Semua part dalam kategori oli

#### Strategi 2: Keyword Search (baris 145-174)

```php
1. Clean temuan text:
   - Lowercase
   - Remove special chars
   - Remove stopwords (kotor, bocor, rusak, perlu, dll)

2. Tokenize:
   - Split by space
   - Filter empty strings

3. Build query:
   WHERE namaitem LIKE '%token1%'
   AND namaitem LIKE '%token2%'
   ...

4. LIMIT 50
5. Return dengan strategy = 'keyword'
```

**Stopwords**:
```php
['kotor','kebocoran','bocor','rusak','perlu','butuh',
 'diganti','ganti','hanya','di','yang','dan','untuk',
 'agar','cek','periksa','servis','service','saja','aja',
 'itu','ini','pergantian','perbaikan','per','penggantian',
 'temuan','masalah','kerusakan','harus','segera']
```

**Response Format**:
```json
{
  "strategy": "kategori|keyword",
  "temuan": "input temuan text",
  "kode_kategori": "KAT001",
  "nama_kategori": "Oli & Pelumas",
  "parts": [
    {
      "kode_barang": "OLI001",
      "nama_barang": "Oli Mesin SAE 10W-40",
      "harga_jual": 50000,
      "satuan": "Liter",
      "stok_tersedia": 0,
      "is_featured": 1
    }
  ]
}
```

---

## 4. RELASI ANTAR TABEL

### 4.1 Entity Relationship

```
tblservice (no_service)
    |
    +-- tbservis_temuan (no_service, keluhan_id)
    |       |
    |       +-- tbservis_penawaran_part (temuan_id)
    |               |
    |               +-- [APPROVED] --> tblservis_barang (no_service, no_item)
    |
    +-- tbservis_workorder (no_service, kode_wo)
    |       |
    |       +-- tbservis_pending_items (wo_id)
    |               |
    |               +-- [APPROVED BARANG] --> tblservis_barang
    |               +-- [APPROVED JASA] --> tblservis_jasa
    |
    +-- tblservis_barang (no_service)
    +-- tblservis_jasa (no_service)

tbworkorderheader (kode_wo)
    |
    +-- tbworkorderdetail (kode_wo)
            |
            +-- [tipe='1'] --> tblitem_jasa (noitem)
            +-- [tipe='2'] --> tblitem (noitem)
```

### 4.2 Foreign Keys (Logical)

```sql
-- Temuan
tbservis_temuan.no_service → tblservice.no_service
tbservis_temuan.keluhan_id → tbservis_keluhan_status.id
tbservis_temuan.kode_temuan → tbmaster_temuan.kode_temuan

-- Penawaran
tbservis_penawaran_part.no_service → tblservice.no_service
tbservis_penawaran_part.temuan_id → tbservis_temuan.id
tbservis_penawaran_part.kode_barang → tblitem.noitem

-- Pending Items
tbservis_pending_items.no_service → tblservice.no_service
tbservis_pending_items.wo_id → tbservis_workorder.id
tbservis_pending_items.kode_item → tblitem.noitem | tblitem_jasa.noitem

-- Servis Barang & Jasa
tblservis_barang.no_service → tblservice.no_service
tblservis_barang.no_item → tblitem.noitem
tblservis_jasa.no_service → tblservice.no_service
tblservis_jasa.no_item → tblitem_jasa.noitem

-- Work Order
tbservis_workorder.no_service → tblservice.no_service
tbservis_workorder.kode_wo → tbworkorderheader.kode_wo
tbworkorderdetail.kode_wo → tbworkorderheader.kode_wo
```

**Catatan**: Database ini tidak menggunakan FOREIGN KEY constraint, hanya logical relationship

---

## 5. FLOW DIAGRAM

### 5.1 User Journey - Temuan & Penawaran

```
START: Customer datang dengan kendaraan
   |
   v
[Tab Detail Service]
   - Input data customer
   - Input no polisi
   - Input keluhan awal
   - SIMPAN → generate no_service
   |
   v
[Tab Work Order] (Optional)
   - Pilih paket WO (misal: Servis Rutin)
   - Klik "Tambah Work Order"
   - Item WO → tbservis_pending_items (status: pending)
   |
   v
[Mekanik Cek Kendaraan]
   |
   v
[Tab Temuan & Penawaran]
   - Mekanik/SA input temuan
   - Klik "Tambah Temuan"
   - Data masuk tbservis_temuan (status: ditemukan)
   |
   v
[Rekomendasi Part] (Optional)
   - Sistem auto-suggest part berdasarkan temuan
   - Tampil list part yang relevan
   |
   v
[Tambah Penawaran Part]
   - Pilih part dari rekomendasi ATAU manual input
   - Set quantity & harga
   - Klik "Tambah Penawaran"
   - Data masuk tbservis_penawaran_part (status: pending)
   |
   v
[Review Penawaran]
   - Admin/SA review semua pending items & penawaran
   - Per item:
     |
     +--> APPROVE --> masuk tblservis_barang/jasa
     |
     +--> REJECT --> catat alasan + keterangan
   |
   v
[Tab Item Barang & Jasa]
   - Review final list part & jasa
   - Bisa tambah manual jika ada tambahan
   |
   v
[Tab Actions]
   - Hitung total
   - Input diskon (jika ada)
   - Pilih metode pembayaran
   - PROSES PEMBAYARAN
   - Cetak invoice
   |
   v
END: Servis selesai
```

### 5.2 State Diagram - Status Temuan

```
[ditemukan]
   |
   +-- User klik "Ubah Status" --> [ditawarkan]
   |                                    |
   |                                    +-- Customer setuju --> [disetujui]
   |                                    |                            |
   |                                    |                            +-- Perbaikan selesai --> [selesai]
   |                                    |
   |                                    +-- Customer tolak --> [ditolak]
   |
   +-- Tidak bisa diperbaiki --> [tidak_selesai] (+ keterangan wajib)
```

### 5.3 State Diagram - Status Penawaran

```
[pending] (default saat dibuat)
   |
   +-- Admin APPROVE --> [disetujui] --> Auto-insert ke tblservis_barang
   |
   +-- Admin REJECT --> [ditolak] (+ alasan + keterangan)
```

### 5.4 State Diagram - Status Pending Items (dari WO)

```
[pending] (default saat WO ditambahkan)
   |
   +-- Admin APPROVE:
   |     |
   |     +-- tipe = 'barang' --> Insert ke tblservis_barang
   |     |
   |     +-- tipe = 'jasa' --> Insert ke tblservis_jasa
   |
   +-- Admin REJECT --> [ditolak]
         - Simpan alasan + keterangan
         - Catat ke tblservice.keterangan
```

---

## 6. VALIDASI & BUSINESS RULES

### 6.1 Validasi Input

#### Temuan
```php
// Wajib diisi
- nama_temuan (baik dari master atau custom)

// Optional
- keluhan_id (boleh NULL)
- kode_temuan (boleh NULL jika custom)
- deskripsi_temuan
- estimasi_biaya (default 0)

// Enum validation
- jenis_perbaikan: 'setting' | 'penggantian_part'
- tingkat_urgensi: 'rendah' | 'sedang' | 'tinggi' | 'kritis'
```

#### Penawaran Part
```php
// Wajib diisi
- kode_barang
- nama_barang
- quantity (min 1)
- harga_satuan (min 0)

// Optional
- temuan_id (boleh NULL)
- catatan_penawaran

// Computed
- total_harga = quantity * harga_satuan
```

#### Update Status Temuan
```php
// Conditional validation
if (status_temuan == 'tidak_selesai') {
    keterangan_tidak_selesai REQUIRED
}
```

### 6.2 Business Rules

#### Prevent Duplicate Part

**Rule 1**: Tidak boleh ada penawaran duplikat untuk part yang sama dalam 1 service
```php
// Check di tbservis_penawaran_part
WHERE no_service = X
AND kode_barang = Y
AND status_penawaran IN ('pending', 'disetujui')
```

**Rule 2**: Tidak boleh buat penawaran jika part sudah ada di tblservis_barang
```php
// Check di tblservis_barang
WHERE no_service = X
AND no_item = Y
```

#### Prevent Duplicate Work Order

**Rule**: Tidak boleh tambah WO yang sama 2x dalam 1 service
```php
// Check di tbservis_workorder
WHERE no_service = X
AND kode_wo = Y
```

#### Status Temuan Lifecycle

**Rule**: Status temuan TIDAK diubah otomatis oleh sistem
- Harus manual update oleh user
- Terpisah dari status penawaran
- Misal: Temuan bisa status 'ditemukan' meski penawarannya sudah 'disetujui'

#### Rejection Must Have Reason

**Rule**: Reject penawaran/pending item wajib isi alasan
```php
// Untuk penawaran
alasan_tolak: enum required
keterangan_tolak: text optional

// Untuk pending items
alasan_tolak: enum required
keterangan_tolak: text optional
```

#### Auto-Insert After Approval

**Rule**: Approved item otomatis masuk ke tabel final
- Penawaran approved → tblservis_barang
- Pending item approved (barang) → tblservis_barang
- Pending item approved (jasa) → tblservis_jasa

#### Nobaris Auto-Increment

**Rule**: Saat insert ke tblservis_barang/jasa, nobaris harus unique per service
```php
$nobaris = MAX(nobaris) + 1
WHERE no_service = X
```

---

## 7. DATA FLOW

### 7.1 Input Temuan → Penawaran → Servis Barang

```
USER INPUT:
{
  "no_service": "SV25000000193",
  "nama_temuan": "Oli kotor perlu diganti",
  "jenis_perbaikan": "penggantian_part",
  "tingkat_urgensi": "sedang"
}
   |
   v
INSERT tbservis_temuan:
{
  "id": 1,
  "no_service": "SV25000000193",
  "temuan_custom": "Oli kotor perlu diganti",
  "jenis_perbaikan": "penggantian_part",
  "status_temuan": "ditemukan",
  "tingkat_urgensi": "sedang",
  "estimasi_biaya": 0
}
   |
   v
AJAX GET RECOMMENDATIONS:
GET /action=get_recommended_parts_for_temuan&temuan=oli kotor
   |
   v
RESPONSE:
{
  "strategy": "kategori",
  "kode_kategori": "KAT_OLI",
  "parts": [
    {
      "kode_barang": "OLI001",
      "nama_barang": "Oli Mesin SAE 10W-40",
      "harga_jual": 50000
    }
  ]
}
   |
   v
USER SELECT & INPUT PENAWARAN:
{
  "temuan_id": 1,
  "kode_barang": "OLI001",
  "nama_barang": "Oli Mesin SAE 10W-40",
  "quantity": 1,
  "harga_satuan": 50000
}
   |
   v
INSERT tbservis_penawaran_part:
{
  "id": 1,
  "no_service": "SV25000000193",
  "temuan_id": 1,
  "kode_barang": "OLI001",
  "nama_barang": "Oli Mesin SAE 10W-40",
  "quantity": 1,
  "harga_satuan": 50000,
  "total_harga": 50000,
  "status_penawaran": "pending"
}
   |
   v
ADMIN APPROVE:
POST btnsetujuipenawaran
   |
   v
UPDATE tbservis_penawaran_part:
{
  "status_penawaran": "disetujui",
  "tanggal_respon": "2025-12-04 10:30:00",
  "user_respon": "Admin1"
}
   |
   v
INSERT tblservis_barang:
{
  "no_service": "SV25000000193",
  "nobaris": 1,
  "no_item": "OLI001",
  "quantity": 1,
  "qty_retur": 0,
  "harga_jual": 50000,
  "potongan": 0,
  "total": 50000
}
```

### 7.2 Work Order → Pending Items → Servis Barang/Jasa

```
USER SELECT WO:
{
  "no_service": "SV25000000193",
  "kode_wo": "WO0001"
}
   |
   v
INSERT tbservis_workorder:
{
  "id": 53,
  "no_service": "SV25000000193",
  "kode_wo": "WO0001",
  "status_pengerjaan": "diproses"
}
   |
   v
GET WO DETAILS:
SELECT * FROM tbworkorderdetail WHERE kode_wo = 'WO0001'
   |
   v
RESULT:
[
  {
    "kode_barang": "SRV002",
    "tipe": "1",  // jasa
    "jumlah": 1,
    "harga": 15000,
    "total": 15000
  },
  {
    "kode_barang": "OLI001",
    "tipe": "2",  // barang
    "jumlah": 1,
    "harga": 25000,
    "total": 25000
  }
]
   |
   v
LOOP INSERT tbservis_pending_items:
[
  {
    "id": 1,
    "no_service": "SV25000000193",
    "wo_id": 53,
    "kode_item": "SRV002",
    "nama_item": "Jasa Servis Berkala",
    "tipe": "jasa",
    "quantity": 1,
    "harga_satuan": 15000,
    "total": 15000,
    "status_approval": "pending"
  },
  {
    "id": 2,
    "no_service": "SV25000000193",
    "wo_id": 53,
    "kode_item": "OLI001",
    "nama_item": "Oli Mesin",
    "tipe": "barang",
    "quantity": 1,
    "harga_satuan": 25000,
    "total": 25000,
    "status_approval": "pending"
  }
]
   |
   v
ADMIN APPROVE ID=1 (JASA):
POST btnapprove_wo_item {pending_item_id: 1}
   |
   v
INSERT tblservis_jasa:
{
  "no_service": "SV25000000193",
  "nobaris": 1,
  "no_item": "SRV002",
  "harga": 15000,
  "waktu": 0,
  "potongan": 0,
  "total": 15000
}
   |
UPDATE tbservis_pending_items:
{
  "status_approval": "disetujui",
  "updated_at": "2025-12-04 10:35:00"
}
   |
   v
ADMIN APPROVE ID=2 (BARANG):
POST btnapprove_wo_item {pending_item_id: 2}
   |
   v
INSERT tblservis_barang:
{
  "no_service": "SV25000000193",
  "nobaris": 2,
  "no_item": "OLI001",
  "quantity": 1,
  "qty_retur": 0,
  "harga_jual": 25000,
  "potongan": 0,
  "total": 25000
}
   |
UPDATE tbservis_pending_items:
{
  "status_approval": "disetujui",
  "updated_at": "2025-12-04 10:36:00"
}
```

---

## 8. QUERY REFERENCE

### 8.1 Get All Temuan for Service

```sql
SELECT
    t.id,
    t.no_service,
    t.keluhan_id,
    COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan,
    t.deskripsi_temuan,
    t.jenis_perbaikan,
    t.status_temuan,
    t.tingkat_urgensi,
    t.estimasi_biaya,
    t.biaya_actual,
    t.created_at,
    -- Get keluhan text
    k.keluhan as keluhan_text,
    k.status_pengerjaan as keluhan_status
FROM tbservis_temuan t
LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
LEFT JOIN tbservis_keluhan_status k ON t.keluhan_id = k.id
WHERE t.no_service = '{no_service}'
ORDER BY t.created_at DESC;
```

### 8.2 Get Pending Penawaran for Service

```sql
SELECT
    p.id,
    p.no_service,
    p.temuan_id,
    p.kode_barang,
    p.nama_barang,
    p.quantity,
    p.harga_satuan,
    p.total_harga,
    p.status_penawaran,
    p.tanggal_penawaran,
    p.user_penawaran,
    -- Get temuan info
    COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan,
    t.jenis_perbaikan
FROM tbservis_penawaran_part p
LEFT JOIN tbservis_temuan t ON p.temuan_id = t.id
LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
WHERE p.no_service = '{no_service}'
AND p.status_penawaran = 'pending'
ORDER BY p.tanggal_penawaran DESC;
```

### 8.3 Get Pending Items from Work Order

```sql
SELECT
    pi.id,
    pi.no_service,
    pi.wo_id,
    pi.kode_item,
    pi.nama_item,
    pi.tipe,
    pi.quantity,
    pi.harga_satuan,
    pi.total,
    pi.status_approval,
    -- Get WO info
    wo.kode_wo,
    woh.nama_wo
FROM tbservis_pending_items pi
LEFT JOIN tbservis_workorder wo ON pi.wo_id = wo.id
LEFT JOIN tbworkorderheader woh ON wo.kode_wo = woh.kode_wo
WHERE pi.no_service = '{no_service}'
AND pi.status_approval = 'pending'
ORDER BY pi.created_at ASC;
```

### 8.4 Get Total Summary for Service

```sql
-- Total barang
SELECT COALESCE(SUM(total), 0) as total_barang
FROM tblservis_barang
WHERE no_service = '{no_service}';

-- Total jasa
SELECT COALESCE(SUM(total), 0) as total_jasa
FROM tblservis_jasa
WHERE no_service = '{no_service}';

-- Total keseluruhan
SELECT
    COALESCE(SUM(b.total), 0) + COALESCE(SUM(j.total), 0) as grand_total
FROM tblservice s
LEFT JOIN tblservis_barang b ON s.no_service = b.no_service
LEFT JOIN tblservis_jasa j ON s.no_service = j.no_service
WHERE s.no_service = '{no_service}';
```

### 8.5 Get Service Summary with All Details

```sql
SELECT
    s.no_service,
    s.tanggal,
    s.no_pelanggan,
    s.no_polisi,
    -- Customer info
    p.namapelanggan,
    p.phone,
    -- Vehicle info
    k.jenis,
    k.tipe as merek,
    k.warna,
    -- Counts
    (SELECT COUNT(*) FROM tbservis_temuan WHERE no_service = s.no_service) as jumlah_temuan,
    (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE no_service = s.no_service AND status_penawaran = 'pending') as jumlah_penawaran_pending,
    (SELECT COUNT(*) FROM tbservis_pending_items WHERE no_service = s.no_service AND status_approval = 'pending') as jumlah_pending_items,
    (SELECT COUNT(*) FROM tblservis_barang WHERE no_service = s.no_service) as jumlah_barang,
    (SELECT COUNT(*) FROM tblservis_jasa WHERE no_service = s.no_service) as jumlah_jasa,
    -- Totals
    (SELECT COALESCE(SUM(total), 0) FROM tblservis_barang WHERE no_service = s.no_service) as total_barang,
    (SELECT COALESCE(SUM(total), 0) FROM tblservis_jasa WHERE no_service = s.no_service) as total_jasa
FROM tblservice s
LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
WHERE s.no_service = '{no_service}';
```

---

## 9. POTENSI MASALAH & REKOMENDASI

### 9.1 Masalah Database Design

#### Issue 1: Tidak Ada Foreign Key Constraint
**Impact**:
- Data orphan (child tanpa parent)
- Inconsistency data
- Sulit maintain referential integrity

**Rekomendasi**:
```sql
-- Tambahkan FK constraint
ALTER TABLE tbservis_temuan
ADD CONSTRAINT fk_temuan_service
FOREIGN KEY (no_service) REFERENCES tblservice(no_service)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE tbservis_penawaran_part
ADD CONSTRAINT fk_penawaran_temuan
FOREIGN KEY (temuan_id) REFERENCES tbservis_temuan(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- dst untuk semua relasi
```

#### Issue 2: Kolom `nobaris` Tidak Konsisten
**Impact**:
- Di code selalu insert nobaris = 0
- Tidak ada unique constraint
- Sulit track urutan item

**Rekomendasi**:
```sql
-- Option 1: Hapus nobaris, pakai auto_increment id
ALTER TABLE tblservis_barang DROP COLUMN nobaris;

-- Option 2: Enforce unique per service
ALTER TABLE tblservis_barang
ADD CONSTRAINT uk_service_nobaris
UNIQUE (no_service, nobaris);

-- Dan fix code untuk generate nobaris yang benar
```

#### Issue 3: No Soft Delete
**Impact**:
- Data terhapus permanent
- Tidak bisa audit trail
- Tidak bisa undo

**Rekomendasi**:
```sql
-- Tambah kolom deleted_at di semua tabel
ALTER TABLE tbservis_temuan
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Update delete query jadi soft delete
UPDATE tbservis_temuan
SET deleted_at = NOW()
WHERE id = X;

-- Select query filter deleted
SELECT * FROM tbservis_temuan
WHERE deleted_at IS NULL;
```

### 9.2 Masalah Code Quality

#### Issue 1: SQL Injection Vulnerable
**Impact**: Security risk

**Contoh Vulnerable Code**:
```php
// servis-input-reguler.php line 83
$query_last_service = "SELECT no_service FROM tblservice
WHERE no_service LIKE '$prefix_service%' ORDER BY no_service DESC LIMIT 1";
```

**Rekomendasi**:
```php
// Gunakan prepared statement
$stmt = mysqli_prepare($koneksi, "SELECT no_service FROM tblservice
WHERE no_service LIKE ? ORDER BY no_service DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $prefix_service);
mysqli_stmt_execute($stmt);
```

#### Issue 2: No Transaction
**Impact**:
- Partial update jika error
- Data inconsistency

**Rekomendasi**:
```php
// Wrap multi-table operation dalam transaction
mysqli_begin_transaction($koneksi);
try {
    // Insert penawaran
    mysqli_query($koneksi, $query_insert_penawaran);

    // Update status temuan
    mysqli_query($koneksi, $query_update_temuan);

    // Commit
    mysqli_commit($koneksi);
} catch (Exception $e) {
    // Rollback jika error
    mysqli_rollback($koneksi);
    throw $e;
}
```

#### Issue 3: Hard-coded String di Multiple Place
**Impact**: Sulit maintain

**Rekomendasi**:
```php
// Buat constant untuk status
define('STATUS_TEMUAN_DITEMUKAN', 'ditemukan');
define('STATUS_TEMUAN_DITAWARKAN', 'ditawarkan');
define('STATUS_TEMUAN_DISETUJUI', 'disetujui');

// Atau gunakan ENUM class
class StatusTemuan {
    const DITEMUKAN = 'ditemukan';
    const DITAWARKAN = 'ditawarkan';
    const DISETUJUI = 'disetujui';
}
```

### 9.3 Masalah Functional

#### Issue 1: No Inventory Tracking
**Impact**:
- Tidak tahu stok real-time
- Part bisa oversell

**Current**:
```php
// _handler_temuan_penawaran.php line 344
$stok_tersedia = 0;  // Hard-coded!
```

**Rekomendasi**:
```php
// Get real stock from inventory
$q = "SELECT stok FROM tblitem WHERE noitem = '$kode_barang'";
$result = mysqli_query($koneksi, $q);
$stok_tersedia = mysqli_fetch_array($result)['stok'];

// Validate stock
if ($stok_tersedia < $quantity) {
    // Auto change status or alert
}
```

#### Issue 2: Duplicate Check Incomplete
**Impact**: Bisa ada duplicate dengan variant kode

**Current**:
```php
// Hanya check exact match
WHERE kode_barang = 'OLI001'
```

**Rekomendasi**:
```php
// Check juga variant (OLI1, OLI01, OLI001, etc)
// Atau normalize kode sebelum insert
```

#### Issue 3: No Notification System
**Impact**:
- Admin tidak tahu ada pending approval
- Delay in processing

**Rekomendasi**:
- Tambah email/SMS notification saat ada pending approval
- Dashboard counter untuk pending items
- Push notification (jika ada)

### 9.4 Masalah UX

#### Issue 1: No Confirmation Dialog
**Impact**: Accidental delete

**Rekomendasi**:
```javascript
// Tambah confirm sebelum delete
function deleteTemuan(id) {
    if (confirm('Yakin ingin menghapus temuan ini?')) {
        // Process delete
    }
}
```

#### Issue 2: Success Message Tidak Informatif
**Current**:
```javascript
alert('Penawaran berhasil ditambahkan!');
```

**Rekomendasi**:
```javascript
alert('Penawaran berhasil ditambahkan!\n\nPart: Oli Mesin SAE 10W-40\nJumlah: 1\nTotal: Rp 50.000\n\nStatus: Menunggu approval');
```

---

## 10. TESTING CHECKLIST

### 10.1 Functional Testing

#### Temuan
- [ ] Bisa tambah temuan dengan master
- [ ] Bisa tambah temuan custom
- [ ] Bisa update status temuan
- [ ] Bisa delete temuan
- [ ] Validasi keterangan saat status tidak_selesai
- [ ] Catatan masuk ke tblservice saat tidak_selesai

#### Penawaran
- [ ] Bisa tambah penawaran manual
- [ ] Bisa tambah penawaran dari auto-suggest
- [ ] Prevent duplicate penawaran
- [ ] Prevent penawaran jika part sudah di servis
- [ ] Approve penawaran masuk ke tblservis_barang
- [ ] Reject penawaran dengan alasan
- [ ] Delete penawaran

#### Pending Items
- [ ] Tambah WO, items masuk pending
- [ ] Handle WO tanpa detail
- [ ] Handle WO dengan kode variant (padding)
- [ ] Approve barang masuk tblservis_barang
- [ ] Approve jasa masuk tblservis_jasa
- [ ] Reject items dengan catatan ke service

#### Auto-Suggest
- [ ] Suggest by kategori
- [ ] Suggest by keyword
- [ ] Handle stopwords
- [ ] Return empty jika tidak match

### 10.2 Integration Testing

- [ ] Flow lengkap: Service baru → Temuan → Penawaran → Approve → Bayar
- [ ] Flow Work Order: Tambah WO → Approve items → Total benar
- [ ] Mixed flow: WO + Temuan/Penawaran dalam 1 service
- [ ] Prevent duplicate di berbagai skenario

### 10.3 Data Validation Testing

- [ ] Required field validation
- [ ] Enum value validation
- [ ] Numeric validation (quantity, harga)
- [ ] SQL injection prevention
- [ ] XSS prevention

---

## 11. DOKUMENTASI TAMBAHAN

### 11.1 File Structure

```
_admincab/
├── servis-input-reguler.php          # Main file
├── _handler_temuan_penawaran.php     # Business logic handler
├── _template/
│   ├── tab-temuan-penawaran-content.php       # Tab content wrapper
│   ├── _servis_input_temuan.php               # Form input temuan
│   ├── _servis_list_temuan_penawaran.php      # List temuan & penawaran
│   ├── modal-search-temuan.php                # Modal cari temuan dari master
│   ├── modal-fastmoves-v2.php                 # Modal fast moves kategori
│   └── modal-fastmoves-part.php               # Modal fast moves part
└── assets/
    └── js/
        └── (custom JS untuk handling AJAX)
```

### 11.2 Dependencies

**PHP Extensions**:
- mysqli
- session
- json

**JavaScript Libraries**:
- jQuery
- Bootstrap 3.x
- Ace Admin Template

**Database**:
- MySQL 5.7+ atau MariaDB 10.x+

### 11.3 Configuration

**Session Requirements**:
```php
$_SESSION['_iduser']      // User ID
$_SESSION['_cabang']      // Kode cabang
$_SESSION['username']     // Username
$_SESSION['_nama']        // Nama user
```

**Database Connection**:
```php
include "../config/koneksi.php";
// Provides: $koneksi (mysqli connection)
```

---

## 12. KESIMPULAN

### 12.1 Summary

Sistem Temuan & Penawaran Part adalah fitur kompleks yang mengintegrasikan:
1. **Temuan mekanik** - Recording findings
2. **Penawaran part** - Manual quotation
3. **Work Order items** - Auto-suggest from package
4. **Approval workflow** - Admin review & decision
5. **Integration** - Seamless to final invoice

### 12.2 Key Features

✅ **Flexible Input**
- Master temuan atau custom
- Manual penawaran atau auto-suggest
- Work Order package dengan approval

✅ **Complete Lifecycle**
- Track status dari ditemukan sampai selesai
- Approval/rejection dengan alasan
- History tracking via timestamps

✅ **Smart Suggestions**
- Auto-suggest part by kategori
- Keyword-based search
- Stopwords filtering

✅ **Data Integrity**
- Prevent duplicate penawaran
- Prevent duplicate WO
- Validation di setiap step

### 12.3 Areas for Improvement

⚠️ **Critical**
- Add foreign key constraints
- Implement transaction for multi-table operations
- Use prepared statements (SQL injection prevention)

⚠️ **Important**
- Real-time inventory tracking
- Soft delete implementation
- Better error handling & logging

⚠️ **Nice to Have**
- Notification system for pending approvals
- Better UX dengan confirmation dialogs
- Audit trail logging

---

**END OF DOCUMENT**

Prepared by: AI Analysis
Date: 2025-12-04
Version: 1.0
