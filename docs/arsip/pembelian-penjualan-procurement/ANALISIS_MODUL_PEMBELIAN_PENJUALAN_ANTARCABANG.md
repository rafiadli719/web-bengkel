# ANALISIS LENGKAP MODUL PEMBELIAN, PENJUALAN & ANTAR CABANG
## SISTEM INFORMASI BENGKEL FIT MOTOR

**Tanggal Analisis:** 18 Desember 2024  
**Database:** fitmotor_dbbengkel  
**Lokasi Aplikasi:** C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab

---

## DAFTAR ISI

1. [Executive Summary](#executive-summary)
2. [Modul Pembelian (Procurement)](#modul-pembelian)
3. [Modul Penjualan (Sales)](#modul-penjualan)
4. [Modul Antar Cabang (Inter-Branch)](#modul-antar-cabang)
5. [Struktur Database](#struktur-database)
6. [Alur Proses Bisnis](#alur-proses-bisnis)
7. [Integrasi Antar Modul](#integrasi-antar-modul)
8. [Rekomendasi & Improvements](#rekomendasi-improvements)

---

## EXECUTIVE SUMMARY

### Rangkuman Modul

Sistem terdiri dari 3 modul utama yang saling terintegrasi untuk mengelola supply chain bengkel multi-cabang:

| Modul | File Count | Key Tables | Status |
|-------|-----------|-----------|---------|
| **Pembelian** | 8 files | 12 tables | ✅ Active |
| **Penjualan** | 3 files | 6 tables | ✅ Active |
| **Antar Cabang** | 3 files | Shared tables | ✅ Active |

### Fitur Utama
- ✅ Purchase Request (PR) → Purchase Order (PO) → Delivery Order (DO) → Invoice
- ✅ Sales Order → Sales Invoice
- ✅ Inter-branch transfer with automated pricing
- ✅ Multi-user & multi-branch support
- ✅ Payment tracking (receivables & payables)
- ✅ Real-time stock integration

---

## MODUL PEMBELIAN

### 1. FILE STRUCTURE & FUNGSI

#### 1.1 Purchase Request (PR) System
**File:** `pr_add.php` (775 lines)

**Fitur Utama:**
- Create PR dengan status workflow (draft → submitted → approved)
- Import items dari CSV/XLSX
- Generate nomor PR otomatis: `PR{YYYYMM}{KD_CABANG}{XXXXX}`
- Multi-item entry dengan estimasi harga
- Validation: PR tidak bisa diubah jika sudah di-PO

**Database Tables:**
```sql
tblpurchase_request_header
├─ no_pr (PK)
├─ tanggal_pr
├─ tanggal_butuh
├─ requester
├─ departemen
├─ alasan
├─ status_pr (draft/submitted/approved/rejected/closed)
└─ kd_cabang

tblpurchase_request_detail
├─ no_pr (FK)
├─ nobaris
├─ no_item
├─ nama_item
├─ quantity
├─ qty_approved
├─ qty_po (tracking untuk PO)
└─ harga_estimasi
```

**Key Functions:**
```php
// Generate PR Number
sp_generate_no_pr(kd_cabang, @p_no_pr)

// Submit PR (change status from draft to submitted)
UPDATE tblpurchase_request_header 
SET status_pr='submitted' 
WHERE no_pr='{$no_pr}'

// Reopen PR to draft (only if not used in PO)
// Check: SUM(qty_po) = 0
```

#### 1.2 Procurement Dashboard
**File:** `procurement_dashboard.php` (218 lines)

**Fitur:**
- Tab-based view: PR / PO / DO
- Latest 20 records per tab
- Quick access untuk create PO dari PR
- Quick access untuk create Invoice dari DO

**Display Data:**
- **PR Tab:** Shows PR dengan jumlah item & total qty
- **PO Tab:** Shows PO dengan sisa qty & jumlah DO
- **DO Tab:** Shows DO dengan status penerimaan

#### 1.3 Document Chain Viewer
**File:** `procurement_chain.php` (352 lines)

**Fitur Luar Biasa:**
- Track complete document chain: PR → PO → DO → Invoice
- Bidirectional linking (bisa start dari mana saja)
- Auto-detect document type (PR/PO/DO/INV)
- Visual representation dengan panel warna berbeda

**Use Case:**
```
User input: "PR202412PSL00001"
System shows:
  ├─ PR: PR202412PSL00001
  ├─ PO: PO202412PSL00123, PO202412PSL00124
  ├─ DO: DO202412PSL00045, DO202412PSL00046
  └─ Invoice: INV202412PSL00089
```

#### 1.4 Purchase Order (Pesanan Pembelian)
**File:** `pesanan_pembelian.php` (671 lines)

**Database Tables:**
```sql
tblorder_header
├─ no_order (PK)
├─ status (0=Open, 1=Closed)
├─ tanggal
├─ no_supplier
├─ no_pr (link to PR)
├─ total_qty
├─ total_order
└─ kd_cabang

tblorder_detail
├─ no_order (FK)
├─ nobaris
├─ no_item
├─ quantity
├─ qty_terima (received quantity)
├─ harga_pokok
└─ total
```

**View:** `view_pesanan_pembelian_header`
- Join dengan tblsupplier untuk nama supplier
- Aggregation total_qty & total_order

#### 1.5 Delivery Order System
**File:** `do_from_po.php` (533 lines)

**Proses Penerimaan Barang:**

1. **Load PO:**
   - Display PO details dengan sisa qty (qty_po - qty_terima)
   - Show PR terkait jika ada
   - List DO yang sudah dibuat

2. **Create DO:**
   - Input qty_kirim & qty_terima per item
   - Validation: qty tidak boleh melebihi sisa PO
   - Generate no_do otomatis: `DO{YYYYMM}{KD_CABANG}{XXXXX}`

3. **Update Stock:**
```php
// Insert to stock
INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, kd_cabang)
VALUES ('2', '$no_do', '$no_item', '$tanggal_do', $qty_terima, 0, '$kd_cabang')

// Update qty_terima di PO
UPDATE tblorder_detail 
SET qty_terima = qty_terima + $qty_terima
WHERE no_order='$no_po' AND no_item='$no_item'
```

**Database Tables:**
```sql
tbldelivery_order_header
├─ no_do (PK)
├─ no_po (FK to tblorder_header)
├─ no_supplier
├─ tanggal_do
├─ tanggal_kirim
├─ tanggal_estimasi_tiba
├─ alamat_kirim
├─ total_qty
├─ status_do (received/cancelled)
└─ kd_cabang

tbldelivery_order_detail
├─ no_do (FK)
├─ nobaris
├─ no_item
├─ qty_po
├─ qty_kirim
├─ qty_terima
└─ qty_reject
```

**File:** `do_list.php` (190 lines)
- List semua DO dengan filter (status, date range, search)
- Pagination support
- Link ke pembelian_add.php untuk create invoice

#### 1.6 Pembelian (Purchase Invoice)
**File:** `pembelian.php` (674 lines)

**Database Tables:**
```sql
tblpembelian_header
├─ notransaksi (PK)
├─ status
├─ carabayar (Tunai/Kredit)
├─ tanggal
├─ no_order (FK to PO)
├─ no_do (FK to DO)
├─ tanggal_order
├─ no_supplier
├─ total_qty
├─ total_akhir
├─ jatuh_tempo (untuk kredit)
└─ kd_cabang

tblpembelian_detail
├─ no_transaksi (FK)
├─ nobaris
├─ no_item
├─ quantity
├─ harga_pokok
├─ potongan
└─ total
```

**View:** `view_pembelian_header`
- Join dengan tblsupplier
- Menampilkan info lengkap untuk list pembelian

#### 1.7 Pembayaran Hutang
**File:** `pmby_hutang.php` (666 lines)

**Database Tables:**
```sql
tblpembayaran_hutang_header
├─ no_transaksi (PK)
├─ tanggal
├─ no_supplier
├─ total_bayar
└─ kd_cabang

tblpembayaran_hutang_detail
├─ no_transaksi (FK)
├─ nobaris
├─ no_pembelian (link to invoice)
├─ total_faktur
├─ bayar
└─ sisa
```

**View:** `view_pembayaran_hutang`
- Aggregate payment per supplier
- Track outstanding payables

---

## MODUL PENJUALAN

### 2. FILE STRUCTURE & FUNGSI

#### 2.1 Pesanan Penjualan (Sales Order)
**File:** `pesanan_penjualan.php` (672 lines)

**Database Tables:**
```sql
tblorderjual_header
├─ no_order (PK)
├─ status (0=Open, 1=Closed)
├─ tanggal
├─ no_sales
├─ no_pelanggan
├─ note
├─ total_qty
├─ total_jual
├─ diskon (%)
├─ total_diskon
├─ pajak (%)
├─ total_pajak
├─ total_akhir
├─ pembayaran (DP)
├─ tipe_trx (Normal/Antar Cabang)
├─ order_ke (kd_cabang tujuan untuk antar cabang)
└─ kd_cabang

tblorderjual_detail
├─ no_order (FK)
├─ nobaris
├─ no_item
├─ harga_jual
├─ quantity
├─ potongan (%)
├─ total
├─ user (temp user untuk entry)
└─ status_trx (0=temp, 1=saved)
```

**View:** `view_pesanan_penjualan_h`
- Join dengan tblpelanggan & tblsales
- Display complete sales order info

**Fitur:**
- Multi-item order dengan diskon per item
- Diskon & pajak di level header
- DP (Down Payment) tracking
- Support untuk customer biasa & antar cabang

#### 2.2 Penjualan (Sales Invoice)
**File:** `penjualan.php` (671 lines)

**Database Tables:**
```sql
tblpenjualan_header
├─ notransaksi (PK)
├─ status
├─ carabayar (Tunai/Kredit)
├─ tanggal
├─ no_order (link to sales order)
├─ tanggal_order
├─ no_pelanggan
├─ total_qty
├─ total_akhir
├─ jatuh_tempo
└─ kd_cabang

tblpenjualan_detail
├─ no_transaksi (FK)
├─ nobaris
├─ no_item
├─ quantity
├─ harga_jual
├─ potongan
└─ total
```

**View:** `view_penjualan_header`
- Complete sales info dengan pelanggan
- Untuk display list penjualan

**Stock Integration:**
```php
// Update stock saat penjualan
INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, kd_cabang)
VALUES ('1', '$notransaksi', '$no_item', '$tanggal', 0, $quantity, '$kd_cabang')
```

#### 2.3 Pembayaran Piutang
**File:** `pmby_piutang.php` (663 lines)

**Database Tables:**
```sql
tblpembayaran_piutang_header
├─ no_transaksi (PK)
├─ tanggal
├─ no_pelanggan
├─ total_bayar
└─ kd_cabang

tblpembayaran_piutang_detail
├─ no_transaksi (FK)
├─ nobaris
├─ no_penjualan (link to invoice)
├─ total_faktur
├─ bayar
└─ sisa
```

**View:** `view_pembayaran_piutang`
- Track customer payments
- Outstanding receivables per customer

---

## MODUL ANTAR CABANG

### 3. INTER-BRANCH TRANSFER SYSTEM

#### 3.1 Pesanan Antar Cabang (Sender Side)
**File:** `pesanan_penjualan_cab_add.php` (1036 lines)

**Konsep Bisnis:**
Cabang pengirim membuat pesanan penjualan ke cabang lain dengan ketentuan:

**Pricing Logic:**
```php
// Cabang Sendiri (tipe_cabang='1')
$harga = hargapokok  // Cost price
$diskon = 100%       // Full discount
$cara_bayar = 'Tunai'

// Cabang Mitra/Eksternal (tipe_cabang!='1')
$harga = hargapokok * 1.05  // Cost + 5% margin
$diskon = 0%
$cara_bayar = 'Kredit'
$jatuh_tempo = 10 hari
```

**Workflow:**
1. Pilih cabang tujuan (cbocabang)
2. Pilih items dengan qty
3. Harga otomatis calculate berdasarkan tipe cabang
4. Save ke tblorderjual_header dengan:
   - `tipe_trx='Antar Cabang'`
   - `order_ke={kd_cabang_tujuan}`
5. Generate cetak/export untuk pengiriman

**Special Features:**
- Tabbed interface (Header / Items / Summary)
- Auto-calculate totals dengan diskon & pajak
- Validation: item tidak boleh duplikat
- Temporary cart system (status_trx='0' untuk temp)

#### 3.2 Penjualan Antar Cabang (Sender - Invoice)
**File:** `penjualan_cab_add.php` (807 lines)

- Convert sales order ke sales invoice
- Same pricing rules as pesanan
- Generate faktur untuk cabang penerima

#### 3.3 Pembelian Antar Cabang (Receiver Side)
**File:** `pembelian_cab_add.php` (810 lines)

**Workflow Penerima:**
1. Pilih cabang pengirim
2. Load nota/faktur yang belum diterima
3. Data penjualan dari cabang pengirim → pesanan pembelian di cabang penerima
4. Penerima terima barang & buat invoice pembelian
5. Stock masuk otomatis

**Key Logic:**
```php
// Cabang penerima melihat:
// - Nota yang statusnya sudah dikirim dari cabang lain
// - Filter: order_ke = kd_cabang_penerima

// Saat terima:
// 1. Create tblorder_header (PO) di cabang penerima
// 2. Harga & terms sesuai yang dikirim cabang pengirim
// 3. Stock movement masuk
```

---

## STRUKTUR DATABASE

### 4. DATABASE SCHEMA OVERVIEW

#### 4.1 Purchasing Tables (12 Tables)

```
PURCHASE REQUEST FLOW:
┌─────────────────────────────────┐
│ tblpurchase_request_header      │ (PR Header)
│  - no_pr (PK)                   │
│  - status_pr                    │
│  - requester, departemen        │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tblpurchase_request_detail      │ (PR Detail)
│  - no_pr (FK)                   │
│  - qty, qty_po                  │
└─────────────────────────────────┘

PURCHASE ORDER FLOW:
┌─────────────────────────────────┐
│ tblorder_header                 │ (PO Header)
│  - no_order (PK)                │
│  - no_pr (FK optional)          │
│  - no_supplier                  │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tblorder_detail                 │ (PO Detail)
│  - no_order (FK)                │
│  - qty, qty_terima              │
└─────────────────────────────────┘

DELIVERY ORDER FLOW:
┌─────────────────────────────────┐
│ tbldelivery_order_header        │ (DO Header)
│  - no_do (PK)                   │
│  - no_po (FK)                   │
│  - status_do                    │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tbldelivery_order_detail        │ (DO Detail)
│  - no_do (FK)                   │
│  - qty_kirim, qty_terima        │
└─────────────────────────────────┘

PURCHASE INVOICE FLOW:
┌─────────────────────────────────┐
│ tblpembelian_header             │ (Invoice Header)
│  - notransaksi (PK)             │
│  - no_do (FK)                   │
│  - no_order (FK)                │
│  - carabayar, jatuh_tempo       │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tblpembelian_detail             │ (Invoice Detail)
│  - no_transaksi (FK)            │
│  - qty, harga_pokok             │
└─────────────────────────────────┘

PAYMENT FLOW:
┌─────────────────────────────────┐
│ tblpembayaran_hutang_header     │ (Payment Header)
│  - no_transaksi (PK)            │
│  - no_supplier                  │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tblpembayaran_hutang_detail     │ (Payment Detail)
│  - no_pembelian (FK)            │
│  - bayar, sisa                  │
└─────────────────────────────────┘

TRACKING:
┌─────────────────────────────────┐
│ tbldo_tracking                  │
│  - no_do                        │
│  - status, keterangan           │
│  - updated_by, timestamp        │
└─────────────────────────────────┘
```

#### 4.2 Sales Tables (6 Tables)

```
SALES ORDER FLOW:
┌─────────────────────────────────┐
│ tblorderjual_header             │ (SO Header)
│  - no_order (PK)                │
│  - no_pelanggan                 │
│  - tipe_trx                     │
│  - order_ke (for inter-branch)  │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tblorderjual_detail             │ (SO Detail)
│  - no_order (FK)                │
│  - qty, harga_jual              │
│  - status_trx (0=temp,1=saved)  │
└─────────────────────────────────┘

SALES INVOICE FLOW:
┌─────────────────────────────────┐
│ tblpenjualan_header             │ (Invoice Header)
│  - notransaksi (PK)             │
│  - no_order (FK)                │
│  - carabayar, jatuh_tempo       │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tblpenjualan_detail             │ (Invoice Detail)
│  - no_transaksi (FK)            │
│  - qty, harga_jual              │
└─────────────────────────────────┘

PAYMENT FLOW:
┌─────────────────────────────────┐
│ tblpembayaran_piutang_header    │ (Payment Header)
│  - no_transaksi (PK)            │
│  - no_pelanggan                 │
└─────────┬───────────────────────┘
          │ 1:N
          ▼
┌─────────────────────────────────┐
│ tblpembayaran_piutang_detail    │ (Payment Detail)
│  - no_penjualan (FK)            │
│  - bayar, sisa                  │
└─────────────────────────────────┘
```

#### 4.3 Stock Movement Table

```sql
CREATE TABLE tbstok (
  tipe VARCHAR(1) NOT NULL,         -- '1'=Keluar, '2'=Masuk
  no_transaksi VARCHAR(30) NOT NULL,
  no_item VARCHAR(30) NOT NULL,
  tanggal DATE NOT NULL,
  masuk DECIMAL(10,2) NOT NULL,     -- Qty in
  keluar DECIMAL(10,2) NOT NULL,    -- Qty out
  keterangan VARCHAR(255),
  kd_cabang VARCHAR(10) NOT NULL,
  user VARCHAR(50),
  PRIMARY KEY (tipe, no_transaksi, no_item, kd_cabang)
)
```

**Stock Movement Types:**
- `tipe='1'`: Sales (keluar)
- `tipe='2'`: Purchase (masuk)
- `tipe='3'`: Adjustment
- `tipe='4'`: Transfer out
- `tipe='5'`: Transfer in

#### 4.4 Master Data Tables

```
CORE MASTERS:
├─ tblitem (Master Barang)
│   ├─ noitem (PK)
│   ├─ namaitem
│   ├─ hargapokok
│   ├─ hargajual
│   └─ kategori, satuan, supplier
│
├─ tblsupplier (Master Supplier)
│   ├─ nosupplier (PK)
│   ├─ namasupplier
│   ├─ alamat, telepon
│   └─ termin_pembayaran
│
├─ tblpelanggan (Master Pelanggan)
│   ├─ nopelanggan (PK)
│   ├─ namapelanggan
│   ├─ alamat, wa
│   └─ kategori_pelanggan
│
├─ tbcabang (Master Cabang)
│   ├─ kode_cabang (PK)
│   ├─ nama_cabang
│   ├─ tipe_cabang (1=Own, 2=Partner)
│   └─ alamat
│
└─ tbuser (User Management)
    ├─ id (PK)
    ├─ nama_user
    ├─ user_akses (level)
    └─ foto_user
```

---

## ALUR PROSES BISNIS

### 5. BUSINESS PROCESS FLOWS

#### 5.1 Standard Purchasing Flow

```
START: Kebutuhan Barang
  │
  ▼
┌─────────────────────────────────┐
│ 1. CREATE PURCHASE REQUEST      │
│    File: pr_add.php             │
│    - Input items & qty          │
│    - Status: draft              │
│    - User: Requester            │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 2. SUBMIT PR FOR APPROVAL       │
│    - Change status: submitted   │
│    - Email/notif ke approver    │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 3. APPROVE PR                   │
│    - Status: approved           │
│    - PR siap dijadikan PO       │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 4. CREATE PURCHASE ORDER        │
│    File: pesanan_pembelian_add  │
│    - Link to PR (optional)      │
│    - Select supplier            │
│    - Confirm prices             │
│    - Update qty_po di PR detail │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 5. SEND PO TO SUPPLIER          │
│    - Print/email PO             │
│    - Wait for delivery          │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 6. RECEIVE GOODS (CREATE DO)    │
│    File: do_from_po.php         │
│    - Input qty received         │
│    - Handle partial delivery    │
│    - Auto update stock (masuk)  │
│    - Update qty_terima di PO    │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 7. CREATE PURCHASE INVOICE      │
│    File: pembelian_add.php      │
│    - Link to DO                 │
│    - Confirm prices & amounts   │
│    - Select payment: Cash/Credit│
│    - Set due date if credit     │
└─────────┬───────────────────────┘
          │
          ▼ (If Credit)
┌─────────────────────────────────┐
│ 8. PAY SUPPLIER (OPTIONAL)      │
│    File: pmby_hutang_add.php    │
│    - Select invoices to pay     │
│    - Enter payment amount       │
│    - Track outstanding balance  │
└─────────┬───────────────────────┘
          │
          ▼
        END
```

**Key Points:**
- PR is optional (can create PO directly)
- Partial delivery supported (multiple DO per PO)
- Stock automatically updated on DO receive
- Payment tracking for credit transactions

#### 5.2 Standard Sales Flow

```
START: Customer Order
  │
  ▼
┌─────────────────────────────────┐
│ 1. CREATE SALES ORDER           │
│    File: pesanan_penjualan_add  │
│    - Select customer            │
│    - Add items & qty            │
│    - Apply discount per item    │
│    - Apply discount & tax @hdr  │
│    - Calculate DP if needed     │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 2. CONFIRM & SAVE SO            │
│    - Status: Open (0)           │
│    - Print for warehouse        │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 3. PREPARE GOODS                │
│    - Warehouse picks items      │
│    - Quality check              │
└─────────┬───────────────────────┘
          │
          ▼
┌─────────────────────────────────┐
│ 4. CREATE SALES INVOICE         │
│    File: penjualan_add.php      │
│    - Link to SO                 │
│    - Confirm qty & prices       │
│    - Auto update stock (keluar) │
│    - Payment: Cash/Credit       │
└─────────┬───────────────────────┘
          │
          ▼ (If Credit)
┌─────────────────────────────────┐
│ 5. RECEIVE PAYMENT (OPTIONAL)   │
│    File: pmby_piutang_add.php   │
│    - Select invoices            │
│    - Enter payment received     │
│    - Track outstanding          │
└─────────┬───────────────────────┘
          │
          ▼
        END
```

**Validation Rules:**
- Item dengan `stok=0` & `harga_naik` tidak bisa dijual
- Diskon bisa % atau nominal
- DP tracked di SO header

#### 5.3 Inter-Branch Transfer Flow

```
BRANCH A (Sender)                    BRANCH B (Receiver)
     │                                        │
     ▼                                        │
┌─────────────────────────┐                  │
│ 1. CREATE INTER-BRANCH  │                  │
│    SALES ORDER          │                  │
│    - Select branch B    │                  │
│    - Add items          │                  │
│    - Auto pricing:      │                  │
│      * Own: cost price  │                  │
│      * Partner: +5%     │                  │
└─────────┬───────────────┘                  │
          │                                  │
          ▼                                  │
┌─────────────────────────┐                  │
│ 2. CREATE SALES INVOICE │                  │
│    - tipe_trx='Antar    │                  │
│      Cabang'            │                  │
│    - order_ke=Branch B  │                  │
└─────────┬───────────────┘                  │
          │                                  │
          ▼                                  │
┌─────────────────────────┐                  │
│ 3. SHIP GOODS           │                  │
│    - Physical transfer  │                  │
│    - With invoice copy  │                  │
└─────────┬───────────────┘                  │
          │                                  │
          │    ┌────────────────────┐        │
          └────▶ GOODS IN TRANSIT   │────────┘
               └────────────────────┘        │
                                            ▼
                              ┌─────────────────────────┐
                              │ 4. RECEIVE NOTIFICATION │
                              │    - Branch B sees      │
                              │      incoming shipment  │
                              └─────────┬───────────────┘
                                        │
                                        ▼
                              ┌─────────────────────────┐
                              │ 5. RECEIVE GOODS        │
                              │    File: pembelian_cab  │
                              │    - Select sender      │
                              │    - View invoice       │
                              │    - Create PO (auto)   │
                              │    - Confirm receipt    │
                              └─────────┬───────────────┘
                                        │
                                        ▼
                              ┌─────────────────────────┐
                              │ 6. CREATE PURCHASE INV  │
                              │    - Prices from sender │
                              │    - Terms from sender  │
                              │    - Stock in (masuk)   │
                              └─────────┬───────────────┘
                                        │
                                        ▼
                              ┌─────────────────────────┐
                              │ 7. PAY (If Credit)      │
                              │    - 10 days term       │
                              │    - Pay to Branch A    │
                              └─────────────────────────┘
```

**Business Rules:**

| Cabang Type | Harga | Diskon | Payment | Term |
|-------------|-------|--------|---------|------|
| **Own Branch** | Cost | 100% | Tunai | - |
| **Partner** | Cost + 5% | 0% | Kredit | 10 hari |

**Data Synchronization:**
```sql
-- Branch A: Sales
INSERT INTO tblorderjual_header (..., tipe_trx='Antar Cabang', order_ke='B')
INSERT INTO tblpenjualan_header (...) -- Stock out at Branch A

-- Branch B: Purchase
SELECT * FROM tblorderjual_header WHERE order_ke='B' -- See incoming
INSERT INTO tblorder_header (...) -- Create PO
INSERT INTO tblpembelian_header (...) -- Stock in at Branch B
```

---

## INTEGRASI ANTAR MODUL

### 6. MODULE INTEGRATION POINTS

#### 6.1 Purchase → Sales Integration

**Scenario:** Barang yang dibeli untuk dijual kembali

```
FLOW:
Purchase DO (Receive) → Stock Masuk → Available for Sales → Sales Invoice → Stock Keluar
```

**Integration Point:** `tbstok`
```sql
-- Saat receive (DO)
INSERT INTO tbstok (tipe='2', masuk=100, keluar=0) -- +100 stock

-- Saat penjualan
INSERT INTO tbstok (tipe='1', masuk=0, keluar=50) -- -50 stock

-- Current stock calculation
SELECT 
  no_item,
  SUM(masuk) - SUM(keluar) AS stock_available
FROM tbstok
WHERE kd_cabang = 'XXX'
GROUP BY no_item
```

#### 6.2 Inter-Branch Integration

**Kompleks Flow:**
```
Branch A (Sales Side)               Branch B (Purchase Side)
        │                                   │
        ├─ tblorderjual_header              │
        │  (tipe_trx='Antar Cabang')        │
        │                                   │
        ├─ tblpenjualan_header              │
        │  (stock keluar di A)              │
        │                                   │
        └─────────────────┐                 │
                          │                 │
                 [Transfer Data]            │
                          │                 │
                          └─────────────────┤
                                            │
                              ┌─────────────┴──────────┐
                              │                        │
                              ├─ tblorder_header       │
                              │  (PO di B from A)      │
                              │                        │
                              ├─ tblpembelian_header   │
                              │  (stock masuk di B)    │
                              │                        │
                              └────────────────────────┘
```

**Shared Data Elements:**
- Invoice dari Branch A = reference untuk PO di Branch B
- Harga & terms dibawa dari A ke B
- No transfer tracking (bisa improve dengan transfer_id)

#### 6.3 Document Chain Integration

**Complete Traceability:**
```
PR → PO → DO → Purchase Invoice → Payment

Example:
PR202412PSL00001 (Request for 10 items)
  └─ PO202412PSL00123 (Order 10 items)
      ├─ DO202412PSL00045 (Receive 5 items) ──┐
      │                                       │
      │  └─ INV202412PSL00089 (Invoice 5)    │
      │                                       │
      └─ DO202412PSL00046 (Receive 5 items) ──┤
                                              │
         └─ INV202412PSL00090 (Invoice 5)    │
                                              │
         All link back to original PR ────────┘
```

**Benefit:**
- Audit trail lengkap
- Track outstanding items
- Monitor partial deliveries
- Prevent duplicate processing

---

## REKOMENDASI & IMPROVEMENTS

### 7. RECOMMENDATIONS

#### 7.1 CRITICAL ISSUES (HIGH PRIORITY) ⚠️

**1. SQL Injection Vulnerability**
```php
// Current (VULNERABLE):
$sql = "SELECT * FROM tblitem WHERE noitem='$txtcaribrg'";

// Fixed (RECOMMENDED):
$stmt = $koneksi->prepare("SELECT * FROM tblitem WHERE noitem=?");
$stmt->bind_param("s", $txtcaribrg);
$stmt->execute();
```

**Impact:** ALL module files vulnerable
**Action:** Implement prepared statements across all files

**2. Missing Foreign Key Constraints**
```sql
-- Current: No FK constraints
-- Recommended:
ALTER TABLE tblorder_detail
  ADD CONSTRAINT fk_order_header 
  FOREIGN KEY (no_order) REFERENCES tblorder_header(no_order)
  ON DELETE CASCADE;

ALTER TABLE tblorder_header
  ADD CONSTRAINT fk_purchase_request
  FOREIGN KEY (no_pr) REFERENCES tblpurchase_request_header(no_pr)
  ON DELETE SET NULL;

ALTER TABLE tbldelivery_order_header
  ADD CONSTRAINT fk_purchase_order
  FOREIGN KEY (no_po) REFERENCES tblorder_header(no_order)
  ON DELETE RESTRICT;
```

**3. Transaction Management**
```php
// Current: Individual INSERTs without transaction
mysqli_query($koneksi, "INSERT INTO header...");
mysqli_query($koneksi, "INSERT INTO detail...");
mysqli_query($koneksi, "UPDATE stock...");

// Recommended:
mysqli_begin_transaction($koneksi);
try {
    mysqli_query($koneksi, "INSERT INTO header...");
    mysqli_query($koneksi, "INSERT INTO detail...");
    mysqli_query($koneksi, "UPDATE stock...");
    mysqli_commit($koneksi);
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    throw $e;
}
```

#### 7.2 PERFORMANCE IMPROVEMENTS (MEDIUM PRIORITY) 🚀

**1. Add Indexes**
```sql
-- High-usage queries need indexes
CREATE INDEX idx_order_supplier ON tblorder_header(no_supplier, kd_cabang);
CREATE INDEX idx_order_date ON tblorder_header(tanggal, kd_cabang);
CREATE INDEX idx_pr_status ON tblpurchase_request_header(status_pr, kd_cabang);
CREATE INDEX idx_do_po ON tbldelivery_order_header(no_po);
CREATE INDEX idx_stock_item ON tbstok(no_item, kd_cabang, tanggal);

-- Composite indexes for common WHERE clauses
CREATE INDEX idx_orderjual_branch ON tblorderjual_header(kd_cabang, tipe_trx, order_ke);
CREATE INDEX idx_penjualan_date ON tblpenjualan_header(kd_cabang, tanggal, carabayar);
```

**2. Optimize Views**
```sql
-- Current views use * and multiple JOINs
-- Recommended: Select only needed columns
CREATE OR REPLACE VIEW view_pesanan_pembelian_header AS
SELECT 
  h.no_order, h.tanggal, h.no_supplier, h.total_qty, h.total_order,
  s.namasupplier, h.kd_cabang
FROM tblorder_header h
INNER JOIN tblsupplier s ON h.no_supplier = s.nosupplier;

-- Add indexes on view columns
```

**3. Pagination Already Implemented ✅**
- Good: All list pages use LIMIT/OFFSET
- Maintain this pattern

#### 7.3 FUNCTIONAL ENHANCEMENTS (LOW PRIORITY) 💡

**1. Enhanced Document Tracking**
```sql
-- Add transfer tracking table
CREATE TABLE tbl_transfer_tracking (
  transfer_id VARCHAR(50) PRIMARY KEY,
  dari_cabang VARCHAR(10),
  ke_cabang VARCHAR(10),
  no_sales_order VARCHAR(50),
  no_purchase_order VARCHAR(50),
  status VARCHAR(20), -- pending/in_transit/received/cancelled
  tanggal_kirim DATE,
  tanggal_terima DATE,
  created_by VARCHAR(50),
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**2. Email/WhatsApp Notifications**
```php
// PR approved → notify requester
// PO created → notify supplier
// Goods shipped (inter-branch) → notify receiver
// Payment due → notify branch
```

**3. Approval Workflow Enhancement**
```sql
-- Add approval history
CREATE TABLE tbl_approval_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  document_type VARCHAR(20), -- PR/PO/etc
  document_no VARCHAR(50),
  approver VARCHAR(50),
  action VARCHAR(20), -- approve/reject
  comment TEXT,
  approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**4. Stock Reservation**
```php
// When SO created, reserve stock
// When SO cancelled, release reservation
// Check available = stock - reserved before allowing sales
```

**5. Batch Operations**
```php
// DO from PO: Already supports multiple items ✅
// Payment: Support batch payment multiple invoices ✅
// Add: Batch approve multiple PRs
// Add: Batch close completed POs
```

#### 7.4 USER EXPERIENCE IMPROVEMENTS

**1. Dashboard Enhancements**
- Add KPIs: Total pending PR, overdue payments, stock alerts
- Graphical charts for trends
- Quick action buttons

**2. Search & Filter**
- Date range filters (already implemented ✅)
- Advanced filters: by status, supplier, branch
- Export to Excel (partially available ✅)

**3. Mobile Responsiveness**
- Current: Bootstrap responsive (good ✅)
- Consider: Mobile app for warehouse receiving

**4. Audit Trail**
- Log all create/update/delete operations
- Track who changed what when
- Cannot delete documents, only cancel/void

#### 7.5 DATA INTEGRITY IMPROVEMENTS

**1. Validation Rules**
```php
// Enforce business rules at DB level
ALTER TABLE tblorder_detail
  ADD CONSTRAINT chk_quantity_positive 
  CHECK (quantity > 0);

ALTER TABLE tblpembelian_header
  ADD CONSTRAINT chk_jatuh_tempo
  CHECK (jatuh_tempo >= tanggal);
```

**2. Cascading Updates**
```sql
-- When PO cancelled, update PR qty_po
-- When DO cancelled, update PO qty_terima
-- Implement triggers or application logic
```

**3. Duplicate Prevention**
```sql
-- Prevent duplicate PR numbers
ALTER TABLE tblpurchase_request_header
  ADD UNIQUE KEY uk_no_pr (no_pr);

-- Prevent duplicate items in same order
ALTER TABLE tblorder_detail
  ADD UNIQUE KEY uk_order_item (no_order, no_item);
```

---

## APPENDIX

### A. File Summary Table

| # | Module | File Name | LOC | Purpose | Tables Used |
|---|--------|-----------|-----|---------|-------------|
| 1 | Purchase | pr_add.php | 775 | Create/manage PR | tblpurchase_request_header, _detail |
| 2 | Purchase | procurement_dashboard.php | 218 | Dashboard PR/PO/DO | All procurement tables |
| 3 | Purchase | procurement_chain.php | 352 | Document tracking | All procurement tables |
| 4 | Purchase | pesanan_pembelian.php | 671 | List PO | view_pesanan_pembelian_header |
| 5 | Purchase | do_from_po.php | 533 | Receive goods | tbldelivery_order_*, tbstok |
| 6 | Purchase | do_list.php | 190 | List DO | tbldelivery_order_header |
| 7 | Purchase | pembelian.php | 674 | List invoices | view_pembelian_header |
| 8 | Purchase | pmby_hutang.php | 666 | Payables | view_pembayaran_hutang |
| 9 | Sales | pesanan_penjualan.php | 672 | List SO | view_pesanan_penjualan_h |
| 10 | Sales | penjualan.php | 671 | List sales invoices | view_penjualan_header |
| 11 | Sales | pmby_piutang.php | 663 | Receivables | view_pembayaran_piutang |
| 12 | Inter-Branch | pesanan_penjualan_cab_add.php | 1036 | Create inter-branch SO | tblorderjual_* |
| 13 | Inter-Branch | penjualan_cab_add.php | 807 | Inter-branch invoice | tblpenjualan_* |
| 14 | Inter-Branch | pembelian_cab_add.php | 810 | Receive inter-branch | tblorder_*, tblpembelian_* |

**Total:** 14 files, ~8,738 lines of code

### B. Database Views List

1. `view_pesanan_pembelian_header` - PO list with supplier info
2. `view_pembelian_header` - Purchase invoice list
3. `view_pembayaran_hutang` - Payables summary
4. `view_pesanan_penjualan_h` - Sales order list
5. `view_penjualan_header` - Sales invoice list
6. `view_pembayaran_piutang` - Receivables summary
7. `view_cari_item` - Item search helper

### C. Key Functions & Stored Procedures

```sql
-- Generate PR number
CALL sp_generate_no_pr(kd_cabang, @p_no_pr)

-- Other auto-numbering (in PHP)
FormatNoTrans(OtomatisID()) -- PO, SO, Invoice numbers
```

---

## KESIMPULAN

### System Strengths ✅
1. **Complete procurement cycle** dari PR sampai payment
2. **Multi-branch support** dengan automated pricing
3. **Stock integration** real-time
4. **Document traceability** yang bagus
5. **User-friendly interface** dengan ACE admin template
6. **Pagination & search** sudah implemented

### Critical Improvements Needed ⚠️
1. **Security:** SQL injection fixes (ALL files)
2. **Data integrity:** Foreign keys & constraints
3. **Transaction management:** Atomic operations
4. **Performance:** Database indexes
5. **Audit trail:** Logging system

### Recommended Next Steps 🎯
1. **Phase 1 (1-2 weeks):** Security fixes & prepared statements
2. **Phase 2 (1 week):** Add foreign keys & indexes
3. **Phase 3 (2 weeks):** Implement transaction management
4. **Phase 4 (1 week):** Add audit logging
5. **Phase 5 (ongoing):** Feature enhancements

---

**Document Version:** 1.0  
**Author:** System Analyst  
**Date:** 18 Desember 2024  
**Status:** Complete Analysis

---

**Files Referenced:**
- SISTEM INFORMASI BENGKEL FIT MOTOR.md
- fitmotor_dbbengkel.sql
- All _admincab/*.php files

**For Questions Contact:** IT Development Team
