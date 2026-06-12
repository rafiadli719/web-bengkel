# ANALISA LENGKAP INPUT SERVIS REGULER
## File: servis-input-reguler.php

**Tanggal Analisa:** 01 Desember 2025
**Tujuan:** Menganalisa alur proses dan struktur tabel database terkait Workorder, Keluhan, Temuan, dan Penawaran Part

---

## 1. STRUKTUR DATABASE

### 1.1 Tabel Utama Service

#### **tblservice** - Tabel Utama Data Service
```sql
CREATE TABLE tblservice (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  tanggal DATE NOT NULL,
  jam TIME NOT NULL,
  no_pelanggan VARCHAR(20),
  no_polisi VARCHAR(20),
  keterangan TEXT,                    -- Keluhan awal / catatan umum
  status_servis ENUM('datang','diproses','selesai','batal'),
  km_skr INT,
  km_berikut INT,
  id_user VARCHAR(10),
  kd_cabang VARCHAR(10),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan data header/master dari setiap transaksi service
- Berhubungan dengan pelanggan (tblpelanggan), kendaraan (tblkendaraan), dan user
- Status service mengikuti alur: datang → diproses → selesai

---

### 1.2 Tabel Keluhan

#### **tbservis_keluhan_status** - Daftar Keluhan Per Service
```sql
CREATE TABLE tbservis_keluhan_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  keluhan VARCHAR(255) NOT NULL,
  kategori VARCHAR(50),
  kode_keluhan VARCHAR(10),            -- Link ke tbmaster_keluhan
  status_pengerjaan ENUM('datang','diproses','selesai','tidak_selesai'),
  auto_workorder VARCHAR(10),          -- Workorder yang otomatis di-suggest
  workorder_applied TINYINT(1) DEFAULT 0,
  keterangan_tidak_selesai VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan **daftar keluhan** yang dilaporkan customer untuk 1 service
- **Satu service bisa punya BANYAK keluhan** (relasi 1:N)
- Setiap keluhan punya status pengerjaan sendiri
- Field `auto_workorder`: menyimpan kode WO yang di-suggest sistem berdasarkan keluhan

**Alur:**
1. Customer datang dengan keluhan
2. Staff input keluhan ke `tbservis_keluhan_status`
3. Sistem bisa auto-suggest workorder yang cocok (misal: "Aki Soak" → WO0002)
4. Staff bisa apply workorder atau cari manual

---

### 1.3 Tabel Workorder

#### **tbservis_workorder** - Workorder yang Diterapkan ke Service
```sql
CREATE TABLE tbservis_workorder (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  kode_wo VARCHAR(10) NOT NULL,       -- Link ke tbworkorderheader
  status_pengerjaan ENUM('diproses','selesai','tidak_selesai'),
  keterangan_tidak_selesai VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan **workorder paket** yang diterapkan ke service
- **Satu service bisa punya BANYAK workorder** (relasi 1:N)
- Contoh: Service ganti oli + tune up → 2 workorder (WO0001 + WO0005)

#### **tbworkorderheader** - Master Workorder
```sql
CREATE TABLE tbworkorderheader (
  kode_wo VARCHAR(10) PRIMARY KEY,
  nama_wo VARCHAR(100) NOT NULL,
  kategori VARCHAR(50),
  deskripsi TEXT,
  estimasi_waktu INT,
  created_at TIMESTAMP
)
```

**Fungsi:**
- Master data paket workorder
- Contoh: WO0001 = "Ganti Oli Mesin", WO0002 = "Service Aki", dll.

#### **tbworkorderdetail** - Detail Item Workorder
```sql
CREATE TABLE tbworkorderdetail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_wo VARCHAR(10) NOT NULL,       -- Link ke tbworkorderheader
  kode_barang VARCHAR(20) NOT NULL,
  tipe ENUM('1','2'),                 -- 1=Jasa, 2=Barang
  jumlah INT,
  harga DECIMAL(15,2),
  total DECIMAL(15,2),
  created_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan **detail barang/jasa** yang ada dalam 1 paket workorder
- Contoh WO0001 (Ganti Oli):
  - OLI001 (Oli Mesin) → tipe 2 (barang) → qty 1 liter
  - SRV001 (Jasa Ganti Oli) → tipe 1 (jasa) → qty 1

---

### 1.4 Tabel Pending Items (Approval Workorder)

#### **tbservis_pending_items** - Item WO yang Menunggu Approval
```sql
CREATE TABLE tbservis_pending_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  wo_id INT,                          -- Link ke tbservis_workorder.id
  kode_item VARCHAR(50) NOT NULL,
  nama_item VARCHAR(255) NOT NULL,
  tipe ENUM('barang','jasa'),
  quantity INT DEFAULT 1,
  harga_satuan DECIMAL(15,2),
  total DECIMAL(15,2),
  waktu INT DEFAULT 0,                -- Untuk jasa (menit)
  status_approval ENUM('pending','disetujui','ditolak'),
  alasan_tolak ENUM('stok_cabang_kosong','stok_supplier_kosong','customer_tidak_mau','lainnya'),
  keterangan_tolak TEXT,
  approved_by VARCHAR(50),
  approved_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

**Fungsi:**
- **PENTING**: Tabel ini menyimpan item dari workorder yang **BELUM** langsung masuk ke SPK
- Ketika staff menambahkan workorder, **semua item workorder masuk ke pending** dulu
- Staff/admin harus **APPROVE/REJECT** satu per satu
- Jika **APPROVE** → masuk ke `tblservis_barang` atau `tblservis_jasa`
- Jika **REJECT** → dicatat di `tblservice.keterangan` dengan alasan

**Alasan Desain:**
- Customer bisa **tidak setuju** dengan beberapa item dari paket workorder
- Stok barang bisa **tidak tersedia** saat service berlangsung
- Harga bisa **tidak sesuai** dengan budget customer
- Memberikan **fleksibilitas** untuk customize paket workorder

---

### 1.5 Tabel Temuan

#### **tbservis_temuan** - Temuan Hasil Pengecekan Mekanik
```sql
CREATE TABLE tbservis_temuan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  keluhan_id INT,                     -- Link ke tbservis_keluhan_status
  mekanik_id VARCHAR(10),
  mekanik_name VARCHAR(100),
  kode_temuan VARCHAR(10),            -- Link ke tbmaster_temuan
  temuan_custom VARCHAR(255),         -- Temuan manual jika tidak ada di master
  deskripsi_temuan TEXT,
  jenis_perbaikan ENUM('setting','penggantian_part'),
  status_temuan ENUM('ditemukan','ditawarkan','disetujui','ditolak','selesai'),
  keterangan_tidak_selesai TEXT,
  tingkat_urgensi ENUM('rendah','sedang','tinggi','kritis'),
  estimasi_biaya DOUBLE DEFAULT 0,
  biaya_actual DOUBLE DEFAULT 0,
  foto_temuan VARCHAR(255),
  created_by VARCHAR(50),
  created_at TIMESTAMP,
  updated_by VARCHAR(50),
  updated_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan **temuan** yang ditemukan mekanik saat mengerjakan service
- **Berbeda dengan keluhan!**
  - **Keluhan** = yang customer sampaikan saat datang
  - **Temuan** = yang mekanik temukan saat pengecekan/pengerjaan
- Contoh:
  - Customer keluhan: "Motor tidak mau nyala"
  - Temuan mekanik: "Aki tekor", "Filter bensin kotor", "Busi mati"

**Relasi:**
- `keluhan_id` → link ke keluhan yang terkait (opsional)
- Satu keluhan bisa punya banyak temuan
- Satu temuan bisa punya banyak penawaran part

**Status Temuan:**
1. `ditemukan` = baru ditemukan, belum ada tindakan
2. `ditawarkan` = sudah ditawarkan part ke customer
3. `disetujui` = customer setuju, akan dikerjakan
4. `ditolak` = customer tidak setuju
5. `selesai` = sudah dikerjakan

---

### 1.6 Tabel Penawaran Part

#### **tbservis_penawaran_part** - Penawaran Part untuk Temuan
```sql
CREATE TABLE tbservis_penawaran_part (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  temuan_id INT,                      -- Link ke tbservis_temuan
  is_from_suggestion TINYINT(1) DEFAULT 0,
  suggestion_priority INT,
  kode_barang VARCHAR(20) NOT NULL,
  nama_barang VARCHAR(255) NOT NULL,
  quantity INT DEFAULT 1,
  harga_satuan DOUBLE,
  total_harga DOUBLE,
  stok_tersedia INT,
  estimasi_ketersediaan VARCHAR(50),  -- 'ready_stock', 'indent_1_hari', dll
  status_penawaran ENUM('pending','disetujui','ditolak'),
  alasan_tolak ENUM('customer_tidak_mau','stok_bengkel_kosong','stok_supplier_kosong','harga_tidak_cocok','lainnya'),
  keterangan_tolak TEXT,
  catatan_penawaran TEXT,
  discount_persen DECIMAL(5,2),
  discount_nominal DECIMAL(15,2),
  harga_final DECIMAL(15,2),
  tanggal_penawaran DATETIME,
  tanggal_respon DATETIME,
  user_penawaran VARCHAR(50),         -- Yang menawarkan (mekanik/SA)
  user_respon VARCHAR(50),            -- Yang approve/reject (customer/SA)
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan **penawaran part** yang ditawarkan untuk mengatasi temuan
- **Satu temuan bisa punya BANYAK penawaran part** (relasi 1:N)
- Part bisa berasal dari:
  1. **Auto-suggest** berdasarkan temuan (`is_from_suggestion=1`)
  2. **Manual** dari staff

**Status Penawaran:**
1. `pending` = menunggu keputusan customer
2. `disetujui` = customer setuju, part akan dipakai
3. `ditolak` = customer tidak setuju

**Jika Disetujui:**
- Auto-insert ke `tblservis_barang` (masuk SPK)
- Harga dan qty sesuai yang di-approve

**Jika Ditolak:**
- Status tetap `ditolak`
- Alasan dan keterangan dicatat untuk audit

---

### 1.7 Tabel Item Barang & Jasa (SPK Final)

#### **tblservis_barang** - Item Barang yang Dipakai
```sql
CREATE TABLE tblservis_barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  nobaris INT DEFAULT 0,
  no_item VARCHAR(20) NOT NULL,       -- Link ke tblitem
  quantity INT,
  qty_retur INT DEFAULT 0,
  harga_jual DECIMAL(15,2),
  potongan DECIMAL(5,2) DEFAULT 0,
  total DECIMAL(15,2),
  created_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan **barang final** yang dipakai dalam service
- Sumber data bisa dari:
  1. Input manual staff
  2. Approved dari `tbservis_pending_items` (workorder)
  3. Approved dari `tbservis_penawaran_part` (temuan)

#### **tblservis_jasa** - Item Jasa yang Dipakai
```sql
CREATE TABLE tblservis_jasa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  nobaris INT DEFAULT 0,
  no_item VARCHAR(20) NOT NULL,       -- Link ke tblitem_jasa
  harga DECIMAL(15,2),
  waktu INT DEFAULT 0,                -- Durasi (menit)
  potongan DECIMAL(5,2) DEFAULT 0,
  total DECIMAL(15,2),
  created_at TIMESTAMP
)
```

**Fungsi:**
- Menyimpan **jasa final** yang dipakai dalam service
- Sumber data sama seperti `tblservis_barang`

---

## 2. ALUR PROSES INPUT SERVIS REGULER

### 2.1 Flow Diagram Lengkap

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. CUSTOMER DATANG                                              │
│    ↓                                                            │
│    Staff buka servis-input-reguler.php                         │
│    ↓                                                            │
│    Tab: DETAIL SERVICE                                         │
│    - Input no. service (auto-generate)                         │
│    - Input tanggal, jam                                        │
│    - Input data pelanggan & kendaraan                          │
│    - Input keluhan awal (OPSIONAL, bisa di tab workorder)      │
│    ↓                                                            │
│    Klik SIMPAN → INSERT ke tblservice                          │
│    ↓                                                            │
│    Generate no_antrian → INSERT ke tb_antrian_servis           │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 2. TAB WORKORDER - INPUT KELUHAN & WORKORDER                   │
│    ↓                                                            │
│    A. INPUT KELUHAN                                            │
│       - Klik tombol "Tambah Keluhan"                           │
│       - Input keluhan customer                                 │
│       - Pilih kode keluhan dari master (opsional)              │
│       - Submit → INSERT ke tbservis_keluhan_status             │
│       - Sistem bisa auto-suggest workorder berdasarkan keluhan │
│    ↓                                                            │
│    B. INPUT WORKORDER                                          │
│       - Klik tombol "Cari Workorder"                           │
│       - Pilih workorder dari modal (servis-add-workorder-cari) │
│       - Submit → INSERT ke tbservis_workorder                  │
│       ↓                                                         │
│       AUTO-PROCESS:                                            │
│       1. Ambil detail workorder dari tbworkorderdetail         │
│       2. Loop setiap item (barang/jasa)                        │
│       3. INSERT ke tbservis_pending_items (status=pending)     │
│       4. Redirect ke Tab Temuan & Penawaran                    │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 3. TAB TEMUAN & PENAWARAN                                      │
│    ↓                                                            │
│    SECTION A: PENAWARAN DARI WORKORDER (PENDING ITEMS)         │
│    ↓                                                            │
│    Tampilkan tabel pending items dengan 2 tombol:             │
│    - [APPROVE] → Handler: btnapprove_wo_item                   │
│      1. Ambil data dari tbservis_pending_items                 │
│      2. Cek tipe (barang/jasa)                                 │
│      3. INSERT ke tblservis_barang ATAU tblservis_jasa         │
│      4. UPDATE status_approval = 'disetujui'                   │
│      5. Redirect kembali ke tab temuan                         │
│    ↓                                                            │
│    - [REJECT] → Handler: btnreject_wo_item                     │
│      1. Tampilkan popup alasan reject                          │
│      2. UPDATE status_approval = 'ditolak'                     │
│      3. UPDATE tblservice.keterangan (tambah catatan)          │
│      4. Redirect kembali ke tab temuan                         │
│    ↓                                                            │
│    SECTION B: INPUT TEMUAN                                     │
│    ↓                                                            │
│    Form input temuan (_servis_input_temuan.php):              │
│    1. Pilih keluhan terkait (dropdown)                         │
│    2. Cari temuan dari master (modal search)                   │
│    3. Input deskripsi detail                                   │
│    4. Pilih tingkat urgensi (rendah/sedang/tinggi/kritis)     │
│    5. Pilih jenis perbaikan:                                   │
│       - Setting/servis saja                                    │
│       - Penggantian part → tampilkan area penawaran part      │
│    6. Submit → Handler: btnaddtemuan                           │
│       - INSERT ke tbservis_temuan                              │
│    ↓                                                            │
│    SECTION C: TAMBAH PENAWARAN PART                            │
│    ↓                                                            │
│    Form penawaran part (tab-temuan-penawaran-content):        │
│    1. Pilih temuan terkait (dropdown, opsional)                │
│    2. Pilih part dengan 2 cara:                                │
│       a. Fast Moves Part → Modal kategori part                 │
│       b. Input Barang Custom → Modal input manual              │
│    3. Input kode, nama, qty, harga                             │
│    4. Submit → Handler: btnaddpenawaran                        │
│       ↓                                                         │
│       Proses:                                                  │
│       1. Validasi duplikasi (cek di tblservis_barang)          │
│       2. Validasi duplikasi penawaran aktif                    │
│       3. INSERT ke tbservis_penawaran_part                     │
│          - status_penawaran = 'pending'                        │
│          - stok_tersedia = 0 (default)                         │
│       4. Redirect ke tab temuan                                │
│    ↓                                                            │
│    SECTION D: APPROVE/REJECT PENAWARAN                         │
│    ↓                                                            │
│    Tampilkan daftar penawaran dengan 2 tombol:                │
│    - [APPROVE] → Handler: btnsetujuipenawaran                  │
│      1. Ambil data dari tbservis_penawaran_part                │
│      2. Cek apakah sudah ada di tblservis_barang               │
│      3. INSERT ke tblservis_barang                             │
│      4. UPDATE status_penawaran = 'disetujui'                  │
│      5. Redirect ke tab temuan                                 │
│    ↓                                                            │
│    - [REJECT] → Handler: btnrejectpenawaran                    │
│      1. Tampilkan popup alasan reject                          │
│      2. UPDATE status_penawaran = 'ditolak'                    │
│      3. UPDATE alasan_tolak & keterangan_tolak                 │
│      4. Redirect ke tab temuan                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 4. TAB ITEM BARANG                                             │
│    ↓                                                            │
│    Tampilkan daftar barang final dari tblservis_barang         │
│    Source data:                                                │
│    1. Input manual staff                                       │
│    2. Approved dari pending items (workorder)                  │
│    3. Approved dari penawaran part (temuan)                    │
│    ↓                                                            │
│    Fitur:                                                      │
│    - Edit qty, harga, potongan                                 │
│    - Hapus item                                                │
│    - Tambah manual (form cari barang)                          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 5. TAB ITEM JASA                                               │
│    ↓                                                            │
│    Sama seperti Tab Item Barang, tapi untuk jasa               │
│    Data dari tblservis_jasa                                    │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 6. TAB ACTIONS                                                 │
│    ↓                                                            │
│    - Assign mekanik & persentase fee                           │
│    - Hitung total barang + jasa                                │
│    - Input diskon member (jika ada)                            │
│    - Pilih metode pembayaran                                   │
│    - Bayar & cetak invoice                                     │
└─────────────────────────────────────────────────────────────────┘
```

---

### 2.2 Handler POST yang Penting

File `servis-input-reguler.php` memiliki banyak handler POST. Berikut yang terkait workorder, temuan, penawaran:

#### Handler 1: `btnaddworkorder` - Tambah Workorder
**Lokasi:** Line 440-614
**Proses:**
1. Validasi kode workorder tidak kosong
2. Cek duplikasi workorder di `tbservis_workorder`
3. Verify workorder exists di `tbworkorderheader`
4. INSERT ke `tbservis_workorder`
5. **AUTO-ADD ITEMS KE PENDING:**
   - Query detail workorder dari `tbworkorderdetail`
   - Loop setiap item (barang/jasa)
   - Get nama item dari `tblitem` atau `tblitem_jasa`
   - INSERT ke `tbservis_pending_items` dengan status `pending`
6. Redirect ke tab temuan dengan alert

**Catatan Penting:**
- Item workorder **TIDAK** langsung masuk ke SPK
- Masuk ke **pending** dulu untuk di-approve/reject
- Ini memberikan fleksibilitas customer untuk customize paket

---

#### Handler 2: `btnapprove_wo_item` - Approve Item Workorder
**Lokasi:** Line 617-698
**Proses:**
1. Get data dari `tbservis_pending_items` by ID
2. Validasi status = `pending`
3. Cek tipe item (barang/jasa)
4. **Jika barang:**
   - Cek duplikasi di `tblservis_barang`
   - INSERT ke `tblservis_barang` (qty, harga, total)
5. **Jika jasa:**
   - Cek duplikasi di `tblservis_jasa`
   - Cek kolom `waktu` exists (backward compatibility)
   - INSERT ke `tblservis_jasa` (harga, waktu, total)
6. UPDATE status `tbservis_pending_items` = `disetujui`
7. Redirect dengan alert sukses

---

#### Handler 3: `btnreject_wo_item` - Reject Item Workorder
**Lokasi:** Line 701-779
**Proses:**
1. Get data dari `tbservis_pending_items` by ID
2. Get alasan & keterangan reject dari POST
3. UPDATE status `tbservis_pending_items` = `ditolak`
4. UPDATE alasan_tolak & keterangan_tolak
5. **Tambah catatan ke service:**
   - Get current `tblservice.keterangan`
   - Append info item ditolak dengan format:
     ```
     [ITEM DITOLAK] Jasa/Part: Nama Item (Kode)
     Alasan: [Customer tidak setuju/Stok kosong/dll]
     Keterangan: [Detail]
     Tanggal: DD/MM/YYYY HH:II
     ```
   - UPDATE `tblservice.keterangan`
6. Redirect dengan alert

**Alasan Reject:**
- `customer_tidak_mau` = Pelanggan tidak setuju
- `stok_cabang_kosong` = Stok barang di bengkel tidak ada
- `stok_supplier_kosong` = Stok barang di supplier tidak ada
- `lainnya` = Alasan lain

---

#### Handler 4: `btnaddpenawaran` - Tambah Penawaran Part
**Lokasi:** File `_handler_temuan_penawaran.php` Line 298-377
**Proses:**
1. Validasi input (kode, nama, qty, harga)
2. **Cek duplikasi di tblservis_barang:**
   - Jika part sudah ada di SPK → tolak, alert duplikasi
3. **Cek duplikasi di tbservis_penawaran_part:**
   - Jika sudah ada penawaran aktif (pending/disetujui) → tolak
4. Set `stok_tersedia = 0` (default)
5. Set `status_penawaran = 'pending'` (default)
6. INSERT ke `tbservis_penawaran_part`
7. Redirect ke tab temuan dengan alert

**Catatan:**
- Semua penawaran default `pending` untuk approval
- Stok tidak dicek realtime (asumsi = 0)
- Approval dilakukan manual oleh SA/admin

---

#### Handler 5: `btnsetujuipenawaran` - Approve Penawaran Part
**Lokasi:** File `_handler_temuan_penawaran.php` Line 380-420
**Lokasi 2:** File `servis-input-reguler.php` Line 784-921
**Proses:**
1. Get data dari `tbservis_penawaran_part` by ID
2. Cek status = `pending`
3. **Cek duplikasi di tblservis_barang**
4. **Jika part ada di tblitem:**
   - Get max nobaris dari `tblservis_barang`
   - INSERT ke `tblservis_barang` (nobaris, kode, qty, harga, total)
5. **Jika part TIDAK ada (custom item):**
   - INSERT langsung ke `tblservis_barang` tanpa cek master
6. UPDATE `tbservis_penawaran_part`:
   - `status_penawaran = 'disetujui'`
   - `tanggal_respon = NOW()`
   - `user_respon = current user`
7. Redirect dengan alert sukses

---

#### Handler 6: `btnrejectpenawaran` - Reject Penawaran Part
**Lokasi:** File `_handler_temuan_penawaran.php` Line 423-452
**Lokasi 2:** File `servis-input-reguler.php` Line 926-987
**Proses:**
1. Get data dari `tbservis_penawaran_part` by ID
2. Get alasan & keterangan reject
3. UPDATE `tbservis_penawaran_part`:
   - `status_penawaran = 'ditolak'`
   - `alasan_tolak = [kode_alasan]`
   - `keterangan_tolak = [text]`
   - `tanggal_respon = NOW()`
   - `user_respon = current user`
4. Redirect dengan alert

**Alasan Reject:**
- `customer_tidak_mau` = Customer tidak mau
- `stok_bengkel_kosong` = Stok bengkel kosong
- `stok_supplier_kosong` = Stok supplier kosong
- `harga_tidak_cocok` = Harga tidak cocok
- `lainnya` = Lainnya

---

## 3. KOMPONEN UI & TEMPLATE

### 3.1 Struktur Tab

File `servis-input-reguler.php` menggunakan Bootstrap tabs:

1. **Tab Detail Service** (`#service-details`)
   - Form input header service
   - Data pelanggan & kendaraan

2. **Tab Work Order** (`#workorder-details`)
   - Include: `_template/_servis_add_header_kanan_workorder_only.php`
   - Form input keluhan
   - Form cari & tambah workorder
   - Daftar workorder yang sudah ditambahkan

3. **Tab Temuan & Penawaran** (`#temuan-penawaran`)
   - **Prioritas check file:**
     - `_template/tab-temuan-penawaran-content.php` (wrapper)
     - `_template/tab-temuan-penawaran-content-improved.php` (versi modern)
     - Fallback: `_template/_servis_input_temuan.php`

4. **Tab Item Barang** (`#service-items`)
   - Include: `_template/_servis_add_detail_barang.php`
   - Daftar barang dari `tblservis_barang`

5. **Tab Item Jasa** (`#service-jasa`)
   - Include: `_template/_servis_add_detail_servis.php`
   - Daftar jasa dari `tblservis_jasa`

6. **Tab Actions** (`#service-actions`)
   - Include: `_template/_servis_actions_tab.php`
   - Assign mekanik, hitung total, bayar

---

### 3.2 Tab Temuan & Penawaran - Structure

File: `_template/tab-temuan-penawaran-content-improved.php`

#### SECTION 1: Form Input Temuan
```
┌────────────────────────────────────────┐
│ INPUT TEMUAN HASIL PENGECEKAN          │
├────────────────────────────────────────┤
│ Include: _servis_input_temuan.php      │
│                                        │
│ Fields:                                │
│ - Keluhan terkait (dropdown)           │
│ - Temuan (modal search)                │
│ - Deskripsi detail                     │
│ - Tingkat urgensi                      │
│ - Jenis perbaikan                      │
│   └─ Setting saja                      │
│   └─ Penggantian part                  │
│       └─ Show area auto-suggest part   │
└────────────────────────────────────────┘
```

#### SECTION 2: Form Tambah Penawaran Part
```
┌────────────────────────────────────────┐
│ TAMBAH PENAWARAN PART                  │
├────────────────────────────────────────┤
│ Form collapsible, toggle show/hide     │
│                                        │
│ Fields:                                │
│ - Temuan terkait (opsional)            │
│ - Pilihan input:                       │
│   ┌──────────────────────────────┐    │
│   │ [Fast Moves Part] [Custom]   │    │
│   └──────────────────────────────┘    │
│ - Kode part                            │
│ - Nama part                            │
│ - Quantity                             │
│ - Harga satuan                         │
│ - Total (auto-calculate)               │
│ - Catatan (opsional)                   │
│                                        │
│ [Reset] [Simpan Penawaran]             │
└────────────────────────────────────────┘
```

#### SECTION 2.5: Penawaran dari Workorder (Pending Items)
```
┌────────────────────────────────────────────────────────┐
│ PENAWARAN DARI WORKORDER - MENUNGGU PERSETUJUAN (N)   │
├────────────────────────────────────────────────────────┤
│ ⚠️ Penting! Item berikut dari paket workorder         │
│    memerlukan persetujuan customer.                   │
├────────────────────────────────────────────────────────┤
│ Table: tbservis_pending_items (status=pending)         │
│                                                        │
│ # │ Workorder │ Kode │ Nama │ Tipe │ Qty │ Harga │ Aksi│
│ 1 │ WO0001    │ OLI1 │ Oli  │ Brg  │ 1   │ 25K   │ ✓ ✗ │
│ 2 │ WO0001    │ SRV1 │ Jasa │ Jasa │ 1   │ 15K   │ ✓ ✗ │
│                                                        │
│ Total: Rp 40,000                                       │
│                                                        │
│ Buttons:                                               │
│ [✓ Approve] = approveWorkorderItem(id, nama)           │
│ [✗ Reject]  = rejectWorkorderItem(id, nama)            │
└────────────────────────────────────────────────────────┘
```

#### SECTION 3: Statistics Summary
```
┌──────────────────────────────────────────────────┐
│ STATISTIK                                        │
├──────────────────────────────────────────────────┤
│ [N] Item WO Pending                              │
│ [N] Total Temuan                                 │
│ [N] Penawaran Part Pending                       │
│ [N] Disetujui                                    │
│ [N] Ditolak                                      │
└──────────────────────────────────────────────────┘
```

#### SECTION 4A: Penawaran Part Umum (Tanpa Temuan Terkait)
```
┌────────────────────────────────────────┐
│ PENAWARAN PART UMUM (N)                │
├────────────────────────────────────────┤
│ Query: temuan_id IS NULL               │
│                                        │
│ # │ Part │ Qty │ Harga │ Total │ Status│Aksi│
│ 1 │ xxx  │ 1   │ xxx   │ xxx   │ Pend. │✓ ✗ │
│                                        │
│ Total Penawaran: Rp xxx,xxx            │
│                                        │
│ Buttons per row:                       │
│ [✓] = approvePenawaran(id)             │
│ [✗] = rejectPenawaran(id)              │
└────────────────────────────────────────┘
```

#### SECTION 4B: List Temuan & Penawaran Terkait
```
┌────────────────────────────────────────────────────┐
│ TEMUAN #1: [Nama Temuan]                          │
│ ┌────────────────────────────────────────────────┐│
│ │ Header:                                        ││
│ │ - Badge urgensi (kritis/tinggi/sedang/rendah) ││
│ │ - Badge status (ditemukan/disetujui/selesai)  ││
│ │ - Kategori, jenis perbaikan                   ││
│ ├────────────────────────────────────────────────┤│
│ │ Body - Left Column:                            ││
│ │ - Keluhan terkait                              ││
│ │ - Ditemukan oleh (mekanik)                     ││
│ │ - Estimasi biaya                               ││
│ │ - Deskripsi detail                             ││
│ ├────────────────────────────────────────────────┤│
│ │ Body - Right Column:                           ││
│ │ ┌──────────────────────────────────────┐       ││
│ │ │ PENAWARAN PART (N)                   │       ││
│ │ ├──────────────────────────────────────┤       ││
│ │ │ Query: temuan_id = {id_temuan}       │       ││
│ │ │                                      │       ││
│ │ │ Part │ Qty │ Harga │ Aksi            │       ││
│ │ │ xxx  │ 1   │ xxx   │ [✓] [✗]         │       ││
│ │ │                                      │       ││
│ │ │ Summary:                             │       ││
│ │ │ Pending: N │ Disetujui: N │ Ditolak: N│      ││
│ │ └──────────────────────────────────────┘       ││
│ ├────────────────────────────────────────────────┤│
│ │ Footer Actions:                                ││
│ │ [Edit] [Hapus]                                 ││
│ └────────────────────────────────────────────────┘│
└────────────────────────────────────────────────────┘

... repeat untuk setiap temuan ...

Empty state jika tidak ada temuan:
┌────────────────────────────────────────┐
│         🔍                             │
│   Belum Ada Temuan                     │
│                                        │
│   Gunakan form di atas untuk           │
│   menambahkan temuan baru.             │
└────────────────────────────────────────┘
```

---

### 3.3 Modal & JavaScript Functions

#### Modal yang Digunakan:
1. `modalFastMovesPart` - Modal pilih part dari kategori
2. `modalInputCustom` - Modal input barang custom
3. `modalSearchTemuan` - Modal cari temuan dari master
4. `modalSearchKeluhan` - Modal cari keluhan dari master
5. `modalStatistikPelanggan` - Modal statistik pelanggan

#### JavaScript Functions (Temuan & Penawaran):

**1. Penawaran Part:**
```javascript
// Toggle form penawaran
toggleFormPenawaran()

// Show modals
showModalFastMoves()
showModalCustom()

// Calculate total
hitungTotalPenawaran()

// Callback dari Fast Moves
window.onFastMovesPartSelected(kode, nama, harga, satuan, qty)

// Approve/reject penawaran
approvePenawaran(penawaranId)
rejectPenawaran(penawaranId)
```

**2. Workorder Items:**
```javascript
// Approve/reject item workorder
approveWorkorderItem(itemId, namaItem)
rejectWorkorderItem(itemId, namaItem)
```

**3. Temuan:**
```javascript
// CRUD temuan
tambahPartKeTemuan(temuanId)
editTemuan(temuanId)
deleteTemuan(temuanId)
```

---

## 4. PERBEDAAN DENGAN SERVIS JEMPUT & GARANSI

### 4.1 Servis Reguler vs Servis Jemput

**Servis Reguler:**
- Customer datang langsung ke bengkel
- Tidak ada fitur jemput/antar
- Tidak ada field tarif jemput, jarak, koordinat
- Flow lebih sederhana

**Servis Jemput:**
- Ada fitur jemput kendaraan dari lokasi customer
- Ada field:
  - `alamat_jemput`
  - `koordinat_lat`, `koordinat_lng`
  - `jarak_km`
  - `tarif_jemput`
  - `status_jemput` (belum, sudah, batal)
- Auto-calculate tarif berdasarkan jarak
- Integrasi Google Maps untuk hitung jarak

### 4.2 Servis Reguler vs Servis Garansi

**Servis Reguler:**
- Service berbayar normal
- Ada kalkulasi harga barang & jasa
- Ada payment & invoice

**Servis Garansi:**
- Service gratis (dalam masa garansi)
- Link ke service sebelumnya (`no_service_asal`)
- Field `tipe_garansi`:
  - `part` = part diganti gratis
  - `jasa` = jasa gratis
  - `full` = full gratis
- Tidak ada pembayaran
- Hanya catat biaya untuk audit

---

## 5. KONSISTENSI TAB TEMUAN & PENAWARAN

### 5.1 File yang Digunakan

**Servis Reguler:**
```php
// Main file
servis-input-reguler.php (line 1418-1428)

// Tab temuan wrapper
_template/tab-temuan-penawaran-content.php
  → include tab-temuan-penawaran-content-improved.php
  → fallback _servis_input_temuan.php
```

**Servis Jemput:**
```php
// Main file
servis-input-reguler-jemput.php

// Tab temuan wrapper
_template/_servis_add_jemput_detail_temuan.php (?)
  atau
_template/tab-temuan-penawaran-content.php (sama dengan reguler)
```

**Servis Garansi:**
```php
// Main file
servis-garansi.php

// Tab temuan wrapper
_template/_servis_garansi_detail_temuan.php (?)
  atau
_template/tab-temuan-penawaran-content.php (sama dengan reguler)
```

### 5.2 Handler yang Digunakan

**Semua servis (reguler, jemput, garansi) include file yang sama:**
```php
include "_handler_temuan_penawaran.php";
```

File ini berisi handler:
- `btnaddtemuan` - Tambah temuan
- `btnupdatestatustemuan` - Update status temuan
- `btndeletetemuan` - Hapus temuan
- `btnaddpenawaran` - Tambah penawaran
- `btnsetujuipenawaran` - Approve penawaran
- `btntolakpenawaran` - Reject penawaran
- `btndeletepenawaran` - Hapus penawaran

**Handler ini UNIVERSAL** untuk semua tipe service.

### 5.3 Masalah Inkonsistensi

**Kemungkinan masalah:**
1. File template berbeda antar tipe service
2. CSS/styling tidak konsisten
3. JavaScript function nama berbeda
4. Struktur HTML berbeda
5. Handler POST duplikat (di main file vs include file)

**Rekomendasi:**
- Gunakan **SATU file template** untuk tab temuan & penawaran
- File: `_template/tab-temuan-penawaran-content-improved.php`
- Include di semua halaman service:
  - `servis-input-reguler.php`
  - `servis-input-reguler-jemput.php`
  - `servis-input-reguler-rst.php`
  - `servis-garansi.php`

---

## 6. REKOMENDASI PERBAIKAN

### 6.1 Standardisasi Template

**File template yang harus SAMA:**
```
_template/
  ├─ tab-temuan-penawaran-content-improved.php  (MAIN)
  ├─ _servis_input_temuan.php                   (Form input)
  ├─ _servis_list_temuan_penawaran.php          (List - jika ada)
  ├─ modal-search-temuan.php                    (Modal temuan)
  ├─ modal-fastmoves-part.php                   (Modal fast moves)
  └─ modal-input-barang-custom.php              (Modal custom)
```

**Include di semua halaman:**
```php
// Di servis-input-reguler.php
include "_handler_temuan_penawaran.php";  // Handler

// Di tab temuan
include "_template/tab-temuan-penawaran-content-improved.php";
```

### 6.2 Standardisasi Handler

**Handler yang HARUS ada di file include (_handler_temuan_penawaran.php):**
- `btnaddtemuan`
- `btnupdatestatustemuan`
- `btndeletetemuan`
- `btnaddpenawaran`
- `btnsetujuipenawaran`
- `btntolakpenawaran`
- `btndeletepenawaran`
- `btnapprove_wo_item` ← **PINDAHKAN dari main file**
- `btnreject_wo_item` ← **PINDAHKAN dari main file**

**Handler yang tetap di main file:**
- `btnsimpan` (save header service)
- `btnaddworkorder` (add workorder - karena ada auto-add pending)
- `btncari` (search barang)
- `btnadd` (add barang manual)
- dll. (yang spesifik per tipe service)

### 6.3 Standardisasi JavaScript

**File JavaScript terpisah:**
```javascript
// assets/js/temuan-penawaran.js

function approvePenawaran(id) { ... }
function rejectPenawaran(id) { ... }
function approveWorkorderItem(id, nama) { ... }
function rejectWorkorderItem(id, nama) { ... }
function tambahPartKeTemuan(temuanId) { ... }
function editTemuan(id) { ... }
function deleteTemuan(id) { ... }
function toggleFormPenawaran() { ... }
function hitungTotalPenawaran() { ... }
```

**Include di semua halaman:**
```html
<script src="assets/js/temuan-penawaran.js"></script>
```

### 6.4 Standardisasi CSS

**File CSS terpisah:**
```css
/* assets/css/temuan-penawaran.css */

.temuan-modern-card { ... }
.penawaran-section { ... }
.badge-urgent-kritis { ... }
.stat-card { ... }
```

**Include di semua halaman:**
```html
<link rel="stylesheet" href="assets/css/temuan-penawaran.css">
```

---

## 7. DIAGRAM RELASI TABEL

```
┌─────────────────┐
│   tblservice    │ ← Master service
└────────┬────────┘
         │ 1
         │
         ├─── N ───┐
         │         │
         │    ┌────▼──────────────────────┐
         │    │ tbservis_keluhan_status   │ ← Keluhan customer
         │    └────┬──────────────────────┘
         │         │ 1
         │         │
         │         └─── N ───┐
         │                   │
         │              ┌────▼────────────┐
         │              │ tbservis_temuan │ ← Temuan mekanik
         │              └────┬────────────┘
         │                   │ 1
         │                   │
         │                   └─── N ───┐
         │                             │
         │                   ┌─────────▼──────────────────┐
         │                   │ tbservis_penawaran_part    │
         │                   └────────────────────────────┘
         │                            │
         │                            │ (jika approve)
         │                            ▼
         ├─── N ───┐          ┌──────────────────┐
         │         │          │ tblservis_barang │ ← Final SPK
         │    ┌────▼───────────────────┐ └──────────────────┘
         │    │ tbservis_workorder     │
         │    └────┬───────────────────┘
         │         │ 1
         │         │
         │         └─── N ───┐
         │                   │
         │            ┌──────▼──────────────────┐
         │            │ tbservis_pending_items  │ ← Item WO pending
         │            └──────┬──────────────────┘
         │                   │
         │                   │ (jika approve)
         │                   ▼
         └─── N ───┐  ┌──────────────────┐
                   │  │ tblservis_barang │
              ┌────▼─────────────┐ └──────────────────┘
              │ tblservis_jasa   │ ← Final SPK
              └──────────────────┘
```

**Keterangan:**
- **tblservice** = 1 service bisa punya banyak keluhan, workorder, barang, jasa
- **tbservis_keluhan_status** = 1 keluhan bisa punya banyak temuan
- **tbservis_temuan** = 1 temuan bisa punya banyak penawaran part
- **tbservis_workorder** = 1 workorder bisa punya banyak pending items
- **tbservis_pending_items** = Item workorder yang menunggu approval
- **tbservis_penawaran_part** = Penawaran part dari temuan
- **tblservis_barang/jasa** = Final SPK (dari 3 sumber: manual, WO, penawaran)

---

## 8. KESIMPULAN

### 8.1 Ringkasan Alur

1. **Customer datang** → Input header service
2. **Input keluhan** → Sistem suggest workorder
3. **Tambah workorder** → Items masuk ke **PENDING**
4. **Approve/reject pending items** → Masuk SPK atau dicatat
5. **Temuan mekanik** → Input temuan hasil pengecekan
6. **Penawaran part** → Tawarkan part untuk temuan
7. **Approve/reject penawaran** → Masuk SPK atau ditolak
8. **Final SPK** → Total barang + jasa
9. **Payment** → Bayar & selesai

### 8.2 Keunggulan Sistem

✅ **Fleksibel:** Customer bisa customize paket workorder
✅ **Transparent:** Semua penawaran harus di-approve
✅ **Audit Trail:** Semua rejection tercatat dengan alasan
✅ **Modular:** Temuan terpisah dari keluhan
✅ **Universal:** Handler sama untuk semua tipe service

### 8.3 Area Perbaikan

⚠️ **Inkonsistensi template** antar tipe service
⚠️ **Duplikasi handler** di main file vs include
⚠️ **JavaScript inline** di template (harus dipindah ke file terpisah)
⚠️ **CSS inline** di template (harus dipindah ke file terpisah)
⚠️ **Dokumentasi** minim untuk developer baru

### 8.4 Action Items

1. ✅ Gunakan 1 template untuk tab temuan & penawaran
2. ✅ Pindahkan semua handler ke `_handler_temuan_penawaran.php`
3. ✅ Pisahkan JavaScript ke file `temuan-penawaran.js`
4. ✅ Pisahkan CSS ke file `temuan-penawaran.css`
5. ✅ Buat dokumentasi lengkap (dokumen ini)
6. 🔲 Testing menyeluruh semua tipe service
7. 🔲 Training untuk user tentang approval flow

---

## 9. REFERENSI FILE

### File Utama:
- `_admincab/servis-input-reguler.php` - Main file servis reguler
- `_admincab/servis-input-reguler-rst.php` - Main file servis RST
- `_admincab/servis-input-reguler-jemput.php` - Main file servis jemput
- `_admincab/servis-garansi.php` - Main file servis garansi

### Handler & Logic:
- `_admincab/_handler_temuan_penawaran.php` - Handler universal temuan & penawaran

### Template:
- `_admincab/_template/tab-temuan-penawaran-content.php` - Wrapper tab temuan
- `_admincab/_template/tab-temuan-penawaran-content-improved.php` - Tab temuan modern
- `_admincab/_template/_servis_input_temuan.php` - Form input temuan
- `_admincab/_template/_servis_add_header_kanan_workorder_only.php` - Form workorder

### Modal:
- `_admincab/_template/modal-search-temuan.php` - Modal cari temuan
- `_admincab/_template/modal-search-keluhan.php` - Modal cari keluhan
- `_admincab/_template/modal-fastmoves-part.php` - Modal fast moves part
- `_admincab/_template/modal-input-barang-custom.php` - Modal input custom

### Database:
- `fitmotor_dbbengkel (1).sql` - Full database schema

---

**END OF DOCUMENT**
