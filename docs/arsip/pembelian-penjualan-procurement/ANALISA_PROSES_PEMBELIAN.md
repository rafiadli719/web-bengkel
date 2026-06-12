# ANALISA PROSES PEMBELIAN & REKOMENDASI MODUL PR-PO-DO

## 📊 ANALISA SISTEM YANG ADA SAAT INI

### 1. **STRUKTUR DATABASE EXISTING**

#### **A. Tabel Pesanan Pembelian (Purchase Order)**
```sql
-- tblorder_header (Header PO)
- no_order (PK)
- status
- tanggal
- tglkirim
- no_supplier
- note
- total_qty
- total_terima
- total_order
- status_pesanan
- kd_cabang

-- tblorder_detail (Detail PO)
- no_order (FK)
- nobaris
- no_item
- quantity
- qty_terima
- harga_pokok
- total
- status_trx
```

#### **B. Tabel Pembelian (Goods Receipt)**
```sql
-- tblpembelian_header (Header Pembelian)
- notransaksi (PK)
- status
- carabayar
- tanggal
- no_order (FK ke PO)
- tanggal_order
- no_supplier
- note
- total_qty_order
- total_qty
- total_beli
- diskon
- total_diskon
- pajak
- total_pajak
- total_akhir
- total_retur
- pembayaran
- tanggal_jt
- tanggal_lunas
- status_lunas
- kd_cabang

-- tblpembelian_detail (Detail Pembelian)
- no_transaksi (FK)
- nobaris
- no_item
- qty_order
- quantity
- qty_retur
- harga_pokok
- potongan
- harga_sp
- total
- sts_order
```

#### **C. Tabel Hutang (Accounts Payable)**
```sql
-- tblhutang_header
- no_transaksi (PK)
- tanggal
- no_supplier
- total_hutang
- pembayaran
- sisa_hutang
- status_lunas

-- tblhutang_detail
- no_transaksi (FK)
- nobaris
- no_pembelian (FK)
- total_hutang
- pembayaran
- sisa_hutang
```

---

### 2. **ALUR PROSES YANG ADA SAAT INI**

```
┌──────────────────┐
│ PESANAN PEMBELIAN│ (tblorder_header/detail)
│ (Purchase Order) │ - Buat PO ke supplier
└────────┬─────────┘ - Status: Draft/Approved
         │
         ↓
┌──────────────────┐
│   PEMBELIAN      │ (tblpembelian_header/detail)
│ (Goods Receipt)  │ - Terima barang dari supplier
└────────┬─────────┘ - Link ke PO (no_order)
         │           - Update qty_terima di PO
         ↓
┌──────────────────┐
│  PEMBAYARAN      │ (tblhutang_header/detail)
│  HUTANG (AP)     │ - Bayar hutang supplier
└──────────────────┘ - Link ke pembelian
```

**Karakteristik:**
- ✅ Sudah ada PO (tblorder_header)
- ✅ Sudah ada Goods Receipt (tblpembelian_header)
- ✅ Sudah ada AP (tblhutang_header)
- ❌ Belum ada PR (Purchase Request)
- ❌ Belum ada DO (Delivery Order) terpisah
- ❌ Approval workflow belum terstruktur

---

### 3. **KEKURANGAN SISTEM SAAT INI**

| No | Kekurangan | Dampak |
|----|------------|--------|
| 1 | Tidak ada Purchase Request | Tidak ada kontrol permintaan pembelian dari user |
| 2 | Tidak ada approval workflow | PO bisa langsung dibuat tanpa persetujuan |
| 3 | Tidak ada DO terpisah | Sulit tracking pengiriman bertahap |
| 4 | Status tidak lengkap | Sulit monitoring progress pembelian |
| 5 | Tidak ada budget control | Tidak ada validasi budget sebelum beli |
| 6 | Tidak ada vendor comparison | Tidak bisa bandingkan harga dari beberapa supplier |

---

## 🎯 REKOMENDASI MODUL PR-PO-DO

### **ALUR PROSES BARU YANG DIREKOMENDASIKAN**

```
┌─────────────────────────────────────────────────────────────────┐
│                    PROCUREMENT FLOW                             │
└─────────────────────────────────────────────────────────────────┘

1. PURCHASE REQUEST (PR)
   ┌──────────────────┐
   │ User Request     │ → User buat permintaan barang
   │ (Draft)          │   - Isi item yang dibutuhkan
   └────────┬─────────┘   - Qty, alasan, tanggal butuh
            │
            ↓ Submit
   ┌──────────────────┐
   │ Waiting Approval │ → Menunggu approval atasan
   │ (Pending)        │   - Level 1: Supervisor
   └────────┬─────────┘   - Level 2: Manager (jika > budget)
            │
            ↓ Approved
   ┌──────────────────┐
   │ Approved         │ → PR disetujui
   │                  │   - Siap dibuatkan PO/RFQ
   └────────┬─────────┘
            │
            ↓
            
2. REQUEST FOR QUOTATION (RFQ) - OPTIONAL
   ┌──────────────────┐
   │ Create RFQ       │ → Minta penawaran dari supplier
   │                  │   - Kirim ke 3-5 supplier
   └────────┬─────────┘   - Bandingkan harga
            │
            ↓ Compare
   ┌──────────────────┐
   │ Select Winner    │ → Pilih supplier terbaik
   │                  │   - Berdasarkan harga, kualitas, delivery
   └────────┬─────────┘
            │
            ↓
            
3. PURCHASE ORDER (PO)
   ┌──────────────────┐
   │ Create PO        │ → Buat PO berdasarkan PR/RFQ
   │ (Draft)          │   - Link ke PR
   └────────┬─────────┘   - Pilih supplier
            │
            ↓ Submit
   ┌──────────────────┐
   │ Waiting Approval │ → PO menunggu approval
   │ (Pending)        │   - Cek budget
   └────────┬─────────┘   - Cek harga vs RFQ
            │
            ↓ Approved
   ┌──────────────────┐
   │ Approved         │ → PO disetujui
   │                  │   - Kirim ke supplier
   └────────┬─────────┘   - Print PO
            │
            ↓ Send to Supplier
   ┌──────────────────┐
   │ Sent to Supplier │ → PO dikirim ke supplier
   │                  │   - Tunggu konfirmasi supplier
   └────────┬─────────┘
            │
            ↓
            
4. DELIVERY ORDER (DO)
   ┌──────────────────┐
   │ Supplier Confirm │ → Supplier konfirmasi pengiriman
   │                  │   - Buat DO
   └────────┬─────────┘   - Jadwal kirim
            │
            ↓
   ┌──────────────────┐
   │ In Transit       │ → Barang dalam perjalanan
   │                  │   - Track pengiriman
   └────────┬─────────┘   - Update status
            │
            ↓ Arrive
   ┌──────────────────┐
   │ Arrived          │ → Barang sampai
   │                  │   - Siap untuk GR
   └────────┬─────────┘
            │
            ↓
            
5. GOODS RECEIPT (GR)
   ┌──────────────────┐
   │ Receive Goods    │ → Terima barang
   │                  │   - QC check
   └────────┬─────────┘   - Match dengan PO/DO
            │
            ↓ QC Pass
   ┌──────────────────┐
   │ GR Posted        │ → GR di-post
   │                  │   - Update stock
   └────────┬─────────┘   - Create invoice
            │
            ↓
            
6. INVOICE & PAYMENT
   ┌──────────────────┐
   │ Invoice Received │ → Terima invoice dari supplier
   │                  │   - Match dengan GR
   └────────┬─────────┘   - Buat hutang
            │
            ↓
   ┌──────────────────┐
   │ Payment          │ → Bayar hutang
   │                  │   - Sesuai term payment
   └──────────────────┘
```

---

## 📋 STRUKTUR DATABASE BARU

### **1. PURCHASE REQUEST (PR)**

```sql
-- Tabel: tblpurchase_request_header
CREATE TABLE `tblpurchase_request_header` (
  `no_pr` VARCHAR(50) NOT NULL PRIMARY KEY,
  `tanggal_pr` DATE NOT NULL,
  `tanggal_butuh` DATE NOT NULL,
  `requester` VARCHAR(50) NOT NULL COMMENT 'User yang request',
  `departemen` VARCHAR(50) NOT NULL,
  `alasan` TEXT NOT NULL,
  `total_qty` INT(11) NOT NULL DEFAULT 0,
  `total_estimasi` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status_pr` ENUM('draft','submitted','approved','rejected','closed') NOT NULL DEFAULT 'draft',
  `approved_by` VARCHAR(50) DEFAULT NULL,
  `approved_date` DATETIME DEFAULT NULL,
  `rejected_by` VARCHAR(50) DEFAULT NULL,
  `rejected_date` DATETIME DEFAULT NULL,
  `reject_reason` TEXT DEFAULT NULL,
  `kd_cabang` VARCHAR(10) NOT NULL,
  `created_by` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status_pr`),
  INDEX `idx_tanggal` (`tanggal_pr`),
  INDEX `idx_cabang` (`kd_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: tblpurchase_request_detail
CREATE TABLE `tblpurchase_request_detail` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_pr` VARCHAR(50) NOT NULL,
  `nobaris` INT(11) NOT NULL,
  `no_item` VARCHAR(50) NOT NULL,
  `nama_item` VARCHAR(100) NOT NULL,
  `quantity` INT(11) NOT NULL,
  `qty_approved` INT(11) NOT NULL DEFAULT 0,
  `qty_po` INT(11) NOT NULL DEFAULT 0 COMMENT 'Qty yang sudah di-PO',
  `satuan` VARCHAR(20) NOT NULL,
  `harga_estimasi` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_estimasi` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` TEXT DEFAULT NULL,
  `status_item` ENUM('pending','approved','rejected','po_created') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (`no_pr`) REFERENCES `tblpurchase_request_header`(`no_pr`) ON DELETE CASCADE,
  INDEX `idx_item` (`no_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: tblpr_approval_log
CREATE TABLE `tblpr_approval_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_pr` VARCHAR(50) NOT NULL,
  `level_approval` INT(11) NOT NULL COMMENT '1=Supervisor, 2=Manager, 3=Director',
  `approver` VARCHAR(50) NOT NULL,
  `action` ENUM('approved','rejected') NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `action_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`no_pr`) REFERENCES `tblpurchase_request_header`(`no_pr`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **2. REQUEST FOR QUOTATION (RFQ) - OPTIONAL**

```sql
-- Tabel: tblrfq_header
CREATE TABLE `tblrfq_header` (
  `no_rfq` VARCHAR(50) NOT NULL PRIMARY KEY,
  `no_pr` VARCHAR(50) NOT NULL,
  `tanggal_rfq` DATE NOT NULL,
  `tanggal_deadline` DATE NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `status_rfq` ENUM('draft','sent','received','evaluated','closed') NOT NULL DEFAULT 'draft',
  `winner_supplier` VARCHAR(50) DEFAULT NULL,
  `kd_cabang` VARCHAR(10) NOT NULL,
  `created_by` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`no_pr`) REFERENCES `tblpurchase_request_header`(`no_pr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: tblrfq_detail
CREATE TABLE `tblrfq_detail` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_rfq` VARCHAR(50) NOT NULL,
  `nobaris` INT(11) NOT NULL,
  `no_item` VARCHAR(50) NOT NULL,
  `quantity` INT(11) NOT NULL,
  `satuan` VARCHAR(20) NOT NULL,
  `spesifikasi` TEXT DEFAULT NULL,
  FOREIGN KEY (`no_rfq`) REFERENCES `tblrfq_header`(`no_rfq`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: tblrfq_supplier_response
CREATE TABLE `tblrfq_supplier_response` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_rfq` VARCHAR(50) NOT NULL,
  `no_supplier` VARCHAR(50) NOT NULL,
  `no_item` VARCHAR(50) NOT NULL,
  `harga_penawaran` DECIMAL(15,2) NOT NULL,
  `lead_time_days` INT(11) NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `status_response` ENUM('pending','submitted','selected','rejected') NOT NULL DEFAULT 'pending',
  `submitted_date` DATETIME DEFAULT NULL,
  FOREIGN KEY (`no_rfq`) REFERENCES `tblrfq_header`(`no_rfq`) ON DELETE CASCADE,
  INDEX `idx_supplier` (`no_supplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **3. PURCHASE ORDER (PO) - UPDATE EXISTING**

```sql
-- Update tblorder_header (tambah kolom baru)
ALTER TABLE `tblorder_header` 
ADD COLUMN `no_pr` VARCHAR(50) DEFAULT NULL COMMENT 'Link ke PR',
ADD COLUMN `no_rfq` VARCHAR(50) DEFAULT NULL COMMENT 'Link ke RFQ',
ADD COLUMN `status_approval` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
ADD COLUMN `approved_by` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `approved_date` DATETIME DEFAULT NULL,
ADD COLUMN `rejected_by` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `rejected_date` DATETIME DEFAULT NULL,
ADD COLUMN `reject_reason` TEXT DEFAULT NULL,
ADD COLUMN `po_type` ENUM('regular','urgent','consignment') NOT NULL DEFAULT 'regular',
ADD COLUMN `payment_term` VARCHAR(50) DEFAULT NULL COMMENT 'Net 30, Net 60, COD, dll',
ADD COLUMN `delivery_address` TEXT DEFAULT NULL,
ADD COLUMN `contact_person` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `contact_phone` VARCHAR(20) DEFAULT NULL,
ADD INDEX `idx_pr` (`no_pr`),
ADD INDEX `idx_status_approval` (`status_approval`);

-- Tabel: tblpo_approval_log
CREATE TABLE `tblpo_approval_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_po` VARCHAR(50) NOT NULL,
  `level_approval` INT(11) NOT NULL,
  `approver` VARCHAR(50) NOT NULL,
  `action` ENUM('approved','rejected') NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `action_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`no_po`) REFERENCES `tblorder_header`(`no_order`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **4. DELIVERY ORDER (DO) - NEW**

```sql
-- Tabel: tbldelivery_order_header
CREATE TABLE `tbldelivery_order_header` (
  `no_do` VARCHAR(50) NOT NULL PRIMARY KEY,
  `no_po` VARCHAR(50) NOT NULL,
  `no_supplier` VARCHAR(50) NOT NULL,
  `tanggal_do` DATE NOT NULL,
  `tanggal_kirim` DATE NOT NULL,
  `tanggal_estimasi_tiba` DATE NOT NULL,
  `tanggal_tiba` DATE DEFAULT NULL,
  `no_surat_jalan` VARCHAR(50) DEFAULT NULL COMMENT 'Dari supplier',
  `no_kendaraan` VARCHAR(20) DEFAULT NULL,
  `nama_pengirim` VARCHAR(100) DEFAULT NULL,
  `telp_pengirim` VARCHAR(20) DEFAULT NULL,
  `alamat_kirim` TEXT NOT NULL,
  `total_qty` INT(11) NOT NULL DEFAULT 0,
  `status_do` ENUM('draft','confirmed','in_transit','arrived','received','cancelled') NOT NULL DEFAULT 'draft',
  `keterangan` TEXT DEFAULT NULL,
  `kd_cabang` VARCHAR(10) NOT NULL,
  `created_by` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`no_po`) REFERENCES `tblorder_header`(`no_order`),
  INDEX `idx_status` (`status_do`),
  INDEX `idx_tanggal_kirim` (`tanggal_kirim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: tbldelivery_order_detail
CREATE TABLE `tbldelivery_order_detail` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_do` VARCHAR(50) NOT NULL,
  `nobaris` INT(11) NOT NULL,
  `no_item` VARCHAR(50) NOT NULL,
  `qty_po` INT(11) NOT NULL COMMENT 'Qty di PO',
  `qty_kirim` INT(11) NOT NULL COMMENT 'Qty yang dikirim',
  `qty_terima` INT(11) NOT NULL DEFAULT 0 COMMENT 'Qty yang diterima (setelah QC)',
  `qty_reject` INT(11) NOT NULL DEFAULT 0 COMMENT 'Qty yang ditolak',
  `keterangan` TEXT DEFAULT NULL,
  FOREIGN KEY (`no_do`) REFERENCES `tbldelivery_order_header`(`no_do`) ON DELETE CASCADE,
  INDEX `idx_item` (`no_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: tbldo_tracking
CREATE TABLE `tbldo_tracking` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_do` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `lokasi` VARCHAR(100) DEFAULT NULL,
  `updated_by` VARCHAR(50) NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`no_do`) REFERENCES `tbldelivery_order_header`(`no_do`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **5. GOODS RECEIPT (GR) - UPDATE EXISTING**

```sql
-- Update tblpembelian_header (tambah kolom baru)
ALTER TABLE `tblpembelian_header`
ADD COLUMN `no_do` VARCHAR(50) DEFAULT NULL COMMENT 'Link ke DO',
ADD COLUMN `no_pr` VARCHAR(50) DEFAULT NULL COMMENT 'Link ke PR',
ADD COLUMN `status_qc` ENUM('pending','passed','failed','partial') DEFAULT 'pending',
ADD COLUMN `qc_by` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `qc_date` DATETIME DEFAULT NULL,
ADD COLUMN `qc_notes` TEXT DEFAULT NULL,
ADD INDEX `idx_do` (`no_do`),
ADD INDEX `idx_pr` (`no_pr`);
```

---

## 📁 STRUKTUR FILE PHP YANG PERLU DIBUAT

```
_admincab/
├── purchase_request/
│   ├── pr_list.php                    # List semua PR
│   ├── pr_add.php                     # Form buat PR baru
│   ├── pr_edit.php                    # Edit PR (draft)
│   ├── pr_view.php                    # View detail PR
│   ├── pr_approval.php                # Halaman approval PR
│   └── pr_report.php                  # Laporan PR
│
├── rfq/
│   ├── rfq_list.php                   # List semua RFQ
│   ├── rfq_add.php                    # Buat RFQ dari PR
│   ├── rfq_send.php                   # Kirim RFQ ke supplier
│   ├── rfq_response.php               # Input response supplier
│   ├── rfq_comparison.php             # Bandingkan penawaran
│   └── rfq_select_winner.php          # Pilih supplier terbaik
│
├── purchase_order/
│   ├── po_list.php                    # List semua PO (update existing)
│   ├── po_add_from_pr.php             # Buat PO dari PR
│   ├── po_add_from_rfq.php            # Buat PO dari RFQ
│   ├── po_approval.php                # Approval PO
│   ├── po_print.php                   # Print PO
│   └── po_send.php                    # Kirim PO ke supplier
│
├── delivery_order/
│   ├── do_list.php                    # List semua DO
│   ├── do_add.php                     # Buat DO (dari supplier)
│   ├── do_tracking.php                # Tracking pengiriman
│   ├── do_receive.php                 # Terima barang
│   └── do_print.php                   # Print DO
│
├── goods_receipt/
│   ├── gr_list.php                    # List semua GR (update existing pembelian.php)
│   ├── gr_add_from_do.php             # Buat GR dari DO
│   ├── gr_qc.php                      # Quality Control
│   └── gr_post.php                    # Post GR ke inventory
│
└── reports/
    ├── procurement_dashboard.php      # Dashboard procurement
    ├── pr_status_report.php           # Laporan status PR
    ├── po_outstanding_report.php      # Laporan PO outstanding
    ├── do_tracking_report.php         # Laporan tracking DO
    └── supplier_performance.php       # Laporan performa supplier
```

---

## 🎯 IMPLEMENTASI BERTAHAP

### **FASE 1: PURCHASE REQUEST (PR)** - Minggu 1-2
1. Buat database PR
2. Buat halaman CRUD PR
3. Buat approval workflow PR
4. Testing

### **FASE 2: UPDATE PURCHASE ORDER (PO)** - Minggu 3-4
1. Update database PO
2. Link PO ke PR
3. Tambah approval workflow PO
4. Testing

### **FASE 3: DELIVERY ORDER (DO)** - Minggu 5-6
1. Buat database DO
2. Buat halaman CRUD DO
3. Buat tracking system
4. Testing

### **FASE 4: UPDATE GOODS RECEIPT (GR)** - Minggu 7-8
1. Update database GR
2. Link GR ke DO
3. Tambah QC process
4. Testing

### **FASE 5: RFQ (OPTIONAL)** - Minggu 9-10
1. Buat database RFQ
2. Buat halaman RFQ
3. Buat comparison tool
4. Testing

### **FASE 6: REPORTS & DASHBOARD** - Minggu 11-12
1. Buat dashboard procurement
2. Buat laporan-laporan
3. Testing keseluruhan
4. Training user

---

## ✅ KESIMPULAN

**Sistem yang ada saat ini:**
- ✅ Sudah ada PO (tblorder_header)
- ✅ Sudah ada GR (tblpembelian_header)
- ✅ Sudah ada AP (tblhutang_header)

**Yang perlu ditambahkan:**
- ❌ Purchase Request (PR) - BARU
- ❌ Delivery Order (DO) - BARU
- ❌ Approval Workflow - BARU
- ❌ RFQ (Optional) - BARU

**Estimasi Waktu:**
- Tanpa RFQ: 8-10 minggu
- Dengan RFQ: 10-12 minggu

**Benefit:**
1. ✅ Kontrol pembelian lebih baik
2. ✅ Approval terstruktur
3. ✅ Tracking pengiriman real-time
4. ✅ Budget control
5. ✅ Supplier performance monitoring
6. ✅ Audit trail lengkap

**Siap untuk diimplementasikan!** 🚀
