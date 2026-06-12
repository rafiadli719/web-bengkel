# Analisis Sistem Input Servis - Keluhan, Work Order, Temuan & Penawaran Part

## 📋 **Overview Sistem**

Sistem input servis memiliki 4 komponen utama yang saling terintegrasi:
1. **Keluhan** - Input keluhan pelanggan
2. **Work Order** - Paket pekerjaan standar
3. **Temuan** - Hasil inspeksi/diagnosis
4. **Penawaran Part** - Penawaran suku cadang berdasarkan temuan

## 🗂️ **Struktur File Utama**

### **File Input Servis:**
1. `servis-input-reguler.php` - Input servis reguler (utama)
2. `servis-input-reguler-rst.php` - Hasil/redirect setelah input
3. `servis-input-reguler-jemput.php` - Input servis jemput
4. `servis-input-reguler-jemput-rst.php` - Hasil servis jemput
5. `servis-garansi.php` - Input servis garansi

### **File Handler/Include:**
1. `_handler_temuan_penawaran.php` - Handler temuan & penawaran part
2. `_handler_status_keluhan_wo.php` - Handler update status keluhan & work order
3. `_include_statistik_pelanggan.php` - Statistik pelanggan

## 🗄️ **Struktur Database**

### **Tabel Master:**
```sql
-- Master Keluhan
tbmaster_keluhan (id, kode_keluhan, nama_keluhan, kategori, status)

-- Master Temuan  
tbmaster_temuan (id, kode_temuan, nama_temuan, kategori, status)

-- Master Work Order
tbworkorderheader (kode_wo, nama_wo, keterangan, status)
tbworkorderdetail (kode_wo, kode_barang, jumlah, harga, total, tipe)

-- Relasi Master
tbmaster_keluhan_workorder (id, kode_keluhan, kode_workorder)
tbmaster_keluhan_temuan (id, kode_keluhan, kode_temuan)
```

### **Tabel Transaksi Servis:**
```sql
-- Keluhan Servis
tbservis_keluhan_status (
    id, no_service, keluhan, status_pengerjaan, 
    keterangan_tidak_selesai, auto_workorder, workorder_applied,
    created_at, updated_at
)

-- Work Order Servis
tbservis_workorder (
    id, no_service, kode_wo, status_pengerjaan,
    keterangan_tidak_selesai, created_at, updated_at
)

-- Temuan Servis
tbservis_temuan (
    id, no_service, keluhan_id, kode_temuan, temuan_custom,
    deskripsi_temuan, jenis_perbaikan, status_temuan,
    keterangan_tidak_selesai, tingkat_urgensi, estimasi_biaya,
    created_at, updated_at
)

-- Penawaran Part
tbservis_penawaran_part (
    id, no_service, temuan_id, kode_barang, nama_barang,
    jumlah, harga_satuan, total_harga, status_penawaran,
    keterangan, created_at, updated_at
)
```

### **View Penting:**
```sql
-- View lengkap keluhan dengan relasi
view_servis_keluhan_lengkap - Keluhan + data service + pelanggan + kendaraan

-- View lengkap temuan dengan relasi  
view_servis_temuan_lengkap - Temuan + keluhan + master temuan + penawaran

-- View lengkap work order dengan relasi
view_servis_workorder_lengkap - Work order + master WO + status

-- View summary status servis
view_servis_status_summary - Progress keseluruhan servis
```

## 🔄 **Flow Proses Bisnis**

### **1. Input Keluhan**
```
1. User input keluhan di servis-input-reguler.php
2. Keluhan disimpan ke tbservis_keluhan_status
3. Status awal: 'diproses'
4. Auto work order bisa diterapkan jika ada mapping
```

### **2. Work Order Management**
```
1. User pilih work order dari master (tbworkorderheader)
2. Work order ditambahkan ke tbservis_workorder
3. Auto-add jasa & barang dari tbworkorderdetail
4. Status tracking: diproses → selesai/tidak_selesai
```

### **3. Temuan & Diagnosis**
```
1. Mekanik input temuan berdasarkan keluhan
2. Temuan bisa dari master atau custom
3. Set tingkat urgensi & estimasi biaya
4. Status: ditemukan → ditawarkan → disetujui/ditolak → selesai
```

### **4. Penawaran Part**
```
1. Berdasarkan temuan, sistem generate penawaran part
2. Menggunakan tbmaster_barang_fastmoves untuk part populer
3. Status: pending → disetujui/ditolak
4. Jika disetujui, auto-add ke servis barang
```

## 📊 **Status Management**

### **Status Keluhan:**
- `diproses` - Sedang dikerjakan
- `selesai` - Keluhan selesai ditangani
- `tidak_selesai` - Tidak bisa diselesaikan (perlu keterangan)

### **Status Work Order:**
- `diproses` - Work order sedang dikerjakan
- `selesai` - Work order selesai
- `tidak_selesai` - Work order tidak selesai (perlu keterangan)

### **Status Temuan:**
- `ditemukan` - Temuan baru ditemukan
- `ditawarkan` - Sudah ditawarkan ke pelanggan
- `disetujui` - Pelanggan setuju perbaikan
- `ditolak` - Pelanggan tolak perbaikan
- `selesai` - Perbaikan selesai

### **Status Penawaran:**
- `pending` - Menunggu keputusan pelanggan
- `disetujui` - Pelanggan setuju
- `ditolak` - Pelanggan tolak

## 🎯 **Fitur Utama**

### **1. Auto Work Order**
- Keluhan bisa auto-trigger work order berdasarkan mapping
- Field: `auto_workorder` & `workorder_applied`

### **2. Tracking & Logging**
- `tbservis_keluhan_tracking` - Log perubahan status keluhan
- `tbservis_penawaran_log` - Log perubahan penawaran

### **3. Integration Points**
- Auto-add jasa & barang dari work order ke servis
- Link temuan ke keluhan (`keluhan_id`)
- Link penawaran ke temuan (`temuan_id`)

### **4. Fast Moves System**
- `tbmaster_barang_fastmoves` - Part yang sering digunakan
- Kategorisasi untuk quick access
- Featured items untuk prioritas

## ⚠️ **Potensi Issues & Recommendations**

### **1. Database Issues:**
- Perlu validasi foreign key constraints
- Beberapa tabel mungkin missing indexes untuk performance
- View kompleks bisa slow pada data besar

### **2. Code Issues:**
- Error handling perlu diperbaiki (seperti pada stok masuk)
- Validasi input belum konsisten
- SQL injection protection perlu review

### **3. UX Issues:**
- Flow antar halaman perlu diperjelas
- Status management bisa confusing untuk user
- Need better progress indicators

### **4. Business Logic:**
- Aturan auto work order perlu dokumentasi
- Approval workflow untuk penawaran perlu diperjelas
- Pricing logic untuk estimasi biaya

## 📋 **Next Steps untuk Analisis Lanjutan**

1. **Review Error Handling** - Cek semua query database
2. **Validate Business Rules** - Test flow keluhan → temuan → penawaran
3. **Performance Analysis** - Check view performance dengan data besar
4. **Security Review** - SQL injection & input validation
5. **UX Flow Testing** - Test complete user journey

## 📁 **File yang Perlu Review Detail**

1. `_handler_temuan_penawaran.php` - Logic penawaran part
2. `_handler_status_keluhan_wo.php` - Status management
3. View definitions di database - Performance optimization
4. Master data relationships - Data integrity

---

**Status Analisis:** Completed Overview
**Next:** Detailed code review & testing recommendations
