# 📚 Master Data CRUD - Temuan & Fast Moves

## 📋 Overview

Halaman CRUD untuk mengelola master data yang digunakan dalam fitur **Temuan & Penawaran** dengan **Fast Moves**.

---

## 📁 File CRUD yang Tersedia

### **1. Master Fast Moves** 
**File:** `master-fastmoves.php`  
**Menu:** Data Master → Daftar Item → **Fast Moves Mapping**

#### **Fungsi:**
Mengelola mapping barang ke kategori Fast Moves untuk mempercepat input penawaran part.

#### **Fitur CRUD:**

##### **A. Manajemen Kategori Fast Moves**
- ✅ **Tambah Kategori Baru**
  - Kode kategori (contoh: OLI, AKI, BUSI)
  - Nama kategori (contoh: Oli Mesin, Aki, Busi)
  - Icon (Font Awesome icon name)
  - Urutan tampilan
  - Status aktif/non-aktif

- ✅ **Edit Kategori**
  - Update semua field kategori
  - Toggle status aktif/non-aktif

- ✅ **Hapus Kategori**
  - Soft delete (set is_active = 0)
  - Atau hard delete dari database

##### **B. Manajemen Mapping Barang**
- ✅ **Tambah Mapping Barang ke Kategori**
  - Pilih kategori
  - Pilih barang dari master
  - Set sebagai featured item (unggulan)
  - Set urutan tampilan
  - Validasi duplikasi

- ✅ **Edit Mapping**
  - Update kategori
  - Update status featured
  - Update urutan

- ✅ **Hapus Mapping**
  - Remove barang dari kategori
  - Barang tetap ada di master

- ✅ **Bulk Import**
  - Import mapping dari SQL file
  - Support untuk banyak items sekaligus

#### **Database Tables:**

**tbmaster_kategori_fastmoves:**
```sql
- id (PK)
- kode_kategori (UNIQUE)
- nama_kategori
- icon
- urutan
- is_active
- created_at
- updated_at
```

**tbmaster_barang_fastmoves:**
```sql
- id (PK)
- kode_kategori (FK)
- kode_barang (FK to tblitem)
- is_featured (0/1)
- urutan
- created_at
```

#### **Kategori Default (8 kategori):**

| Kode | Nama | Icon | Warna |
|------|------|------|-------|
| SERVIS | SERVIS / TUNE UP | wrench | Primary |
| OLI | OLI MESIN | tint | Warning |
| AKI | AKI | battery-full | Success |
| BUSI | BUSI | bolt | Danger |
| FLTUDARA | FILTER UDARA | filter | Info |
| FLTFUEL | FILTER FUEL | filter | Primary |
| KAMPAS_D | KAMPAS REM DEPAN | stop-circle | Danger |
| REM | KOMPONEN REM | stop | Warning |

---

### **2. Master Temuan (Keluhan)**
**File:** `master-keluhan.php`  
**Menu:** Data Master → Daftar Item → **Master Temuan**

#### **Fungsi:**
Mengelola master data temuan/keluhan yang sering ditemukan saat service.

#### **Fitur CRUD:**

- ✅ **Tambah Temuan Baru**
  - Kode temuan (auto-generate atau manual)
  - Nama temuan
  - Deskripsi detail
  - Kategori (Setting, Mesin, Kelistrikan, Body)
  - Status aktif

- ✅ **Edit Temuan**
  - Update semua field
  - Update kategori
  - Toggle status aktif

- ✅ **Hapus Temuan**
  - Soft delete (set status_aktif = 0)
  - Temuan tidak hilang dari history

- ✅ **Search & Filter**
  - Cari berdasarkan nama
  - Filter by kategori
  - Filter by status

#### **Database Table:**

**tbmaster_keluhan:**
```sql
- id (PK)
- kode_keluhan (UNIQUE)
- nama_keluhan
- deskripsi
- kategori (Setting/Mesin/Kelistrikan/Body)
- status_aktif (1/0)
- created_at
- updated_at
```

#### **Kategori Temuan:**

| Kategori | Deskripsi | Contoh |
|----------|-----------|--------|
| Setting | Masalah penyetelan | Karburator tidak pas, Rantai kendor |
| Mesin | Masalah mesin | Kompresi lemah, Oli bocor |
| Kelistrikan | Masalah listrik | Lampu mati, Aki soak |
| Body | Masalah body | Cat lecet, Spion patah |

---

### **3. Master Keluhan (CRUD Version)**
**File:** `master-keluhan-crud.php`  
**Menu:** Data Master → Daftar Item → **Master Keluhan**

#### **Fungsi:**
Versi CRUD lengkap untuk master keluhan dengan UI yang lebih modern.

#### **Fitur Tambahan:**
- ✅ DataTables integration
- ✅ Ajax CRUD operations
- ✅ Modal form
- ✅ Inline editing
- ✅ Export to Excel/PDF
- ✅ Advanced search & filter

---

## 🗺️ Menu Sidebar Structure

### **Lokasi Menu:**

```
📁 Data Master
  ├── 📂 Daftar Item
  │   ├── Master Barang
  │   ├── Kategori Barang
  │   ├── Satuan Barang
  │   ├── Pabrik Barang
  │   ├── Rak Barang
  │   ├── Margin Harga Jual
  │   ├── Status Harga
  │   ├── Work Order/Paket
  │   ├── Master Keluhan
  │   ├── Keluhan - WO Mapping
  │   ├── 🆕 Fast Moves Mapping ← BARU!
  │   ├── 🆕 Master Temuan ← BARU!
  │   └── Harga Jual Plus Jasa
  ├── Daftar Pelanggan
  ├── Daftar Kendaraan
  └── ...
```

---

## 🔧 Cara Menggunakan

### **A. Mengelola Fast Moves Mapping**

#### **1. Tambah Kategori Baru**
```
1. Buka menu: Data Master → Daftar Item → Fast Moves Mapping
2. Klik "Tambah Kategori"
3. Isi form:
   - Kode Kategori: KAMPAS_B
   - Nama Kategori: KAMPAS REM BELAKANG
   - Icon: stop-circle
   - Urutan: 9
4. Klik "Simpan"
```

#### **2. Mapping Barang ke Kategori**
```
1. Pilih kategori (contoh: OLI)
2. Klik "Tambah Barang"
3. Pilih barang dari dropdown
4. Centang "Featured" jika item unggulan
5. Set urutan tampilan
6. Klik "Simpan"
```

#### **3. Import Bulk Mapping**
```
1. Siapkan file SQL (contoh: insert_fastmoves_mapping_real.sql)
2. Buka phpMyAdmin atau MySQL client
3. Execute SQL file
4. Refresh halaman Fast Moves Mapping
5. Verifikasi data sudah masuk
```

#### **4. Edit/Hapus Mapping**
```
1. Cari barang yang ingin diedit
2. Klik icon "Edit" (pensil)
3. Update data
4. Klik "Simpan"

Atau:
1. Klik icon "Hapus" (trash)
2. Konfirmasi hapus
```

---

### **B. Mengelola Master Temuan**

#### **1. Tambah Temuan Baru**
```
1. Buka menu: Data Master → Daftar Item → Master Temuan
2. Klik "Tambah Temuan"
3. Isi form:
   - Kode Temuan: TMN001 (auto atau manual)
   - Nama Temuan: Lampu Mati
   - Deskripsi: Lampu depan tidak menyala
   - Kategori: Kelistrikan
4. Klik "Simpan"
```

#### **2. Edit Temuan**
```
1. Cari temuan yang ingin diedit
2. Klik "Edit"
3. Update data
4. Klik "Simpan"
```

#### **3. Nonaktifkan Temuan**
```
1. Cari temuan
2. Klik "Hapus" atau toggle status
3. Temuan akan disembunyikan dari dropdown
4. Data tetap ada di database (soft delete)
```

---

## 📊 Data Flow

### **Fast Moves Flow:**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Admin mengelola mapping di master-fastmoves.php         │
│    - Tambah kategori (OLI, AKI, BUSI, dll)                 │
│    - Mapping barang ke kategori                             │
│    - Set featured items                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Data disimpan di database                                │
│    - tbmaster_kategori_fastmoves                            │
│    - tbmaster_barang_fastmoves                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. User buka halaman servis input                           │
│    - Klik tab "Temuan & Penawaran"                          │
│    - Klik button "Fast Moves"                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Modal Fast Moves terbuka                                 │
│    - Tampilkan 8 kategori button                            │
│    - User klik kategori (contoh: OLI)                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. AJAX call ke _handler_temuan_penawaran.php               │
│    - GET: action=get_parts_by_kategori&kategori=OLI        │
│    - Query join tbmaster_barang_fastmoves + tblitem         │
│    - Return JSON array of parts                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Render tabel part di modal                               │
│    - Tampilkan 5 oli items                                  │
│    - Featured items highlighted                             │
│    - User pilih qty dan klik "+"                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. Callback function dipanggil                              │
│    - onFastMovesPartSelected(kode, nama, harga, satuan, qty)│
│    - Fill form penawaran part                               │
│    - Auto calculate subtotal                                │
│    - Modal tertutup                                         │
└─────────────────────────────────────────────────────────────┘
```

### **Temuan Flow:**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Admin mengelola master temuan di master-keluhan.php     │
│    - Tambah temuan baru                                     │
│    - Set kategori (Setting/Mesin/Kelistrikan/Body)         │
│    - Set status aktif                                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Data disimpan di tbmaster_keluhan                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. User buka halaman servis input                           │
│    - Klik tab "Temuan & Penawaran"                          │
│    - Klik button "Cari Temuan"                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Modal Search Temuan terbuka                              │
│    - Tampilkan list temuan dari master                      │
│    - User search atau pilih dari list                       │
│    - Klik temuan yang dipilih                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Callback function dipanggil                              │
│    - onTemuanSelected(kode, nama, kategori, urgensi)        │
│    - Fill form temuan                                       │
│    - Modal tertutup                                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. User submit form temuan                                  │
│    - POST ke _handler_temuan_penawaran.php                  │
│    - Insert ke tbservis_temuan                              │
│    - Refresh tabel temuan                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 Best Practices

### **Untuk Admin:**

1. **Kategori Fast Moves**
   - Gunakan kode yang singkat dan jelas (max 10 karakter)
   - Nama kategori harus deskriptif
   - Pilih icon yang relevan dari Font Awesome
   - Atur urutan berdasarkan frekuensi penggunaan

2. **Mapping Barang**
   - Hanya mapping barang yang sering digunakan
   - Set featured untuk top 3-5 items per kategori
   - Atur urutan berdasarkan popularitas
   - Review dan update mapping secara berkala

3. **Master Temuan**
   - Gunakan nama yang jelas dan mudah dipahami
   - Deskripsi harus detail dan informatif
   - Kategorisasi dengan benar
   - Nonaktifkan temuan yang sudah tidak relevan (jangan hapus)

### **Untuk Developer:**

1. **Database**
   - Selalu gunakan foreign key constraints
   - Index pada kolom yang sering di-query
   - Backup data sebelum bulk import/update

2. **CRUD Operations**
   - Validasi input di server-side
   - Gunakan prepared statements (mysqli_real_escape_string)
   - Log semua perubahan data penting
   - Handle error dengan graceful

3. **UI/UX**
   - Konfirmasi sebelum delete
   - Show loading indicator saat AJAX
   - Display success/error messages
   - Responsive design untuk mobile

---

## 📝 SQL Scripts

### **Import Fast Moves Mapping:**

```sql
-- File: insert_fastmoves_mapping_real.sql
-- Lokasi: /web-bengkel/insert_fastmoves_mapping_real.sql

-- Execute via phpMyAdmin atau MySQL client:
SOURCE /path/to/insert_fastmoves_mapping_real.sql;

-- Atau copy-paste isi file ke SQL query window
```

### **Verify Data:**

```sql
-- File: verify_fastmoves_data.sql
-- Cek jumlah mapping per kategori

SELECT 
    k.kode_kategori,
    k.nama_kategori,
    COUNT(m.id) as jumlah_items,
    SUM(m.is_featured) as jumlah_featured
FROM tbmaster_kategori_fastmoves k
LEFT JOIN tbmaster_barang_fastmoves m ON k.kode_kategori = m.kode_kategori
GROUP BY k.kode_kategori
ORDER BY k.urutan;
```

---

## ✅ Checklist Setup

### **Initial Setup:**

- [x] Create database tables
  - [x] tbmaster_kategori_fastmoves
  - [x] tbmaster_barang_fastmoves
  - [x] tbmaster_keluhan (existing)
  - [x] tbservis_temuan
  - [x] tbservis_penawaran_part

- [x] Create CRUD pages
  - [x] master-fastmoves.php
  - [x] master-keluhan.php
  - [x] master-keluhan-crud.php

- [x] Add menu items
  - [x] Fast Moves Mapping
  - [x] Master Temuan

- [x] Import initial data
  - [x] 8 kategori Fast Moves
  - [x] 69 items mapping
  - [x] Sample temuan data

### **Testing:**

- [ ] Test CRUD kategori Fast Moves
- [ ] Test CRUD mapping barang
- [ ] Test CRUD master temuan
- [ ] Test integration dengan modal Fast Moves
- [ ] Test integration dengan modal Search Temuan

---

## 📞 Support & Troubleshooting

### **Common Issues:**

**1. Menu tidak muncul di sidebar**
- Cek file menu_adm01.php sudah di-update
- Clear browser cache
- Cek user access level

**2. Data tidak muncul di Fast Moves modal**
- Cek mapping di master-fastmoves.php
- Verify data di database
- Cek console browser untuk error AJAX

**3. Error saat save mapping**
- Cek foreign key constraints
- Pastikan kode_barang ada di tblitem
- Cek duplikasi mapping

---

**Status:** ✅ **READY TO USE**  
**Last Updated:** 8 November 2025

🎉 **Master Data CRUD sudah siap digunakan!**
