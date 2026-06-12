# Workflow PR - PO - DO - Pembelian

## Ringkasan Alur (Summary)

```
PR (Purchase Request)
  ↓ [Submit & Approve]
PO (Purchase Order)
  ↓ [Supplier sends goods]
DO (Delivery Order)
  ↓ [Receive & post]
PEMBELIAN (Invoice/Receipt)
  ↓ [Update stock]
STOK (Inventory)
```

---

## 1. PURCHASE REQUEST (PR)

### 1.1 Tujuan
- User/Departemen mengajukan permintaan pembelian barang
- Approval dari atasan sebelum dibuat PO
- Tracking kebutuhan barang per cabang

### 1.2 Proses Flow

```
[START] → Buat PR Baru → Input Item → Submit PR
   ↓
Approval
   ↓
[Approved] → Konversi ke PO
   ↓
[Rejected] → Batal/Revisi
```

### 1.3 Status PR

| Status | Keterangan | Action |
|--------|------------|--------|
| `draft` | PR baru dibuat, belum disubmit | Edit, Submit, Delete |
| `submitted` | PR sudah disubmit, menunggu approval | Approve, Reject |
| `approved` | PR disetujui, siap dikonversi ke PO | Buat PO |
| `rejected` | PR ditolak | Revisi atau Cancel |
| `partially_ordered` | Sebagian item sudah dibuat PO | Buat PO untuk sisa |
| `fully_ordered` | Semua item sudah dibuat PO | Closed otomatis |
| `closed` | PR selesai | Read-only |
| `cancelled` | PR dibatalkan | Read-only |

### 1.4 Database Tables

**tblpr_header**
```sql
- no_pr (PK): PRYYMMcabang+seq (contoh: PR251101001)
- tanggal: Tanggal pembuatan
- tanggal_butuh: Tanggal kebutuhan barang
- pemohon: Nama user yang meminta
- departemen: Departemen pemohon
- alasan: Alasan permintaan
- status: ENUM (draft, submitted, approved, rejected, etc.)
- kd_cabang: Kode cabang
- approved_by: User yang approve
- approved_at: Waktu approval
- created_by: User pembuat
- created_at: Waktu pembuatan
```

**tblpr_detail**
```sql
- id (PK AUTO_INCREMENT)
- no_pr (FK): Link ke header
- no_item: Kode barang
- qty_req: Qty yang diminta
- qty_ordered: Qty yang sudah dibuat PO (auto update)
- qty_po: Total qty di PO (tracking)
- harga_estimasi: Estimasi harga
- status_line: ENUM (open, ordered, closed, cancelled)
```

### 1.5 File Terkait

- **_admincab/pr_add.php**: Form input PR baru
- **_admincab/pr_list.php**: Daftar semua PR (perlu dibuat)
- **_admincab/pr_approve.php**: Form approval PR (perlu dibuat)

### 1.6 Validasi & Business Rules

1. Qty request harus > 0
2. Hanya PR status `approved` yang bisa dikonversi ke PO
3. PR tidak bisa diedit setelah status `submitted`
4. Auto update status:
   - `partially_ordered` jika ada item yang sudah dibuat PO tapi belum semua
   - `fully_ordered` jika semua item sudah dibuat PO (qty_po >= qty_req)

---

## 2. PURCHASE ORDER (PO)

### 2.1 Tujuan
- Formal order ke supplier
- Link dari PR yang sudah approved
- Tracking order vs penerimaan barang

### 2.2 Proses Flow

```
[PR Approved] → Buat PO dari PR → Pilih Supplier → Input Harga
   ↓
Simpan PO
   ↓
[Status: open] → Tunggu barang datang
   ↓
Barang datang → Buat DO
```

### 2.3 Status PO

| Status | Keterangan | Action |
|--------|------------|--------|
| `open` | PO aktif, menunggu barang | Buat DO saat barang datang |
| `closed` | PO selesai, semua barang sudah diterima | Read-only |
| `cancelled` | PO dibatalkan | Read-only |

### 2.4 Database Tables

**tblorder_header**
```sql
- no_order (PK): PSYY+seq (contoh: PS2500000001)
- no_pr: Link ke PR (optional, bisa null jika PO langsung)
- no_supplier: Kode supplier
- tanggal: Tanggal PO
- kd_cabang: Kode cabang
- status: varchar (open, closed, cancelled)
- total: Total nilai PO
```

**tblorder_detail**
```sql
- no_order (FK): Link ke header
- no_item: Kode barang
- quantity: Qty yang di-order
- qty_terima: Qty yang sudah diterima via DO (auto update)
- harga_pokok: Harga beli per unit
- total: quantity * harga_pokok
- kd_cabang: Kode cabang
- status_trx: '0' = temporary, '1' = saved
```

### 2.5 File Terkait

- **_admincab/pesanan_pembelian_add.php**: Form PO (sudah support link PR)
- **_admincab/pesanan_pembelian.php**: List PO (existing)

### 2.6 Validasi & Business Rules

1. **Jika dari PR**:
   - Qty PO tidak boleh melebihi sisa PR (qty_req - qty_po)
   - Setelah save, update `tblpr_detail.qty_po += qty_order`
   - Auto update PR status ke `partially_ordered` atau `fully_ordered`

2. **PO Langsung (tanpa PR)**:
   - Bisa langsung buat PO tanpa PR
   - Field `no_pr` dikosongkan

3. **Qty Received**:
   - Field `qty_terima` di-update otomatis saat DO dibuat
   - PO status → `closed` jika `qty_terima >= quantity` untuk semua item

---

## 3. DELIVERY ORDER (DO)

### 3.1 Tujuan
- Mencatat pengiriman barang dari supplier
- Tracking qty yang dikirim vs diterima vs reject
- Update stok saat barang diterima
- Update qty_terima di PO

### 3.2 Proses Flow

```
[Barang Datang] → Buat DO dari PO → Input Qty Kirim & Terima
   ↓
Simpan DO
   ↓
[Auto Actions]:
1. Update stok (tbstok): +qty_terima
2. Update PO qty_terima
3. Insert DO tracking
   ↓
Status: received → Buat Invoice Pembelian
```

### 3.3 Status DO

| Status | Keterangan | Action |
|--------|------------|--------|
| `draft` | DO baru, belum final | Edit, Delete |
| `in_transit` | Barang dalam perjalanan | Update tracking |
| `received` | Barang sudah diterima | Buat invoice pembelian |
| `posted` | Sudah dibuat invoice pembelian | Read-only |
| `closed` | DO selesai | Read-only |
| `cancelled` | DO dibatalkan | Read-only |

### 3.4 Database Tables

**tbldelivery_order_header**
```sql
- no_do (PK): DOYYMMcabang+seq (contoh: DO251101001)
- no_po: Link ke PO
- no_supplier: Kode supplier
- tanggal_do: Tanggal DO
- tanggal_kirim: Tanggal pengiriman
- tanggal_estimasi_tiba: Estimasi tiba
- alamat_kirim: Alamat penerimaan
- total_qty: Total qty kirim
- status_do: ENUM (draft, in_transit, received, posted, closed, cancelled)
- kd_cabang: Kode cabang
- created_by: User pembuat
- created_at: Waktu pembuatan
```

**tbldelivery_order_detail**
```sql
- no_do (FK): Link ke header
- nobaris: Nomor baris
- no_item: Kode barang
- qty_po: Qty dari PO (reference)
- qty_kirim: Qty yang dikirim supplier
- qty_terima: Qty yang diterima (good)
- qty_reject: Qty yang reject/rusak
```

**tbldo_tracking**
```sql
- id (PK AUTO_INCREMENT)
- no_do: Link ke DO
- status: Status perubahan
- keterangan: Keterangan
- lokasi: Lokasi saat ini
- updated_by: User yang update
- updated_at: Waktu update
```

### 3.5 File Terkait

- **_admincab/do_from_po.php**: Form buat DO dari PO (sudah lengkap)
- **_admincab/do_list.php**: List DO (perlu dibuat)
- **_admincab/do_tracking.php**: Tracking DO (perlu dibuat)

### 3.6 Validasi & Business Rules

1. **Qty Validation**:
   - `qty_kirim <= sisa PO` (qty PO - qty_terima sebelumnya)
   - `qty_terima <= qty_kirim`
   - `qty_reject <= qty_kirim`
   - `qty_terima + qty_reject <= qty_kirim`

2. **Stock Posting**:
   - Saat DO status = `received`:
     ```sql
     INSERT INTO tbstok (
       tipe='2',  -- tipe 2 = pembelian
       no_transaksi=no_do,
       no_item=...,
       masuk=qty_terima,
       keluar=0,
       keterangan='Penerimaan (DO)',
       kd_cabang=...
     )
     ```

3. **PO Update**:
   - Update `tblorder_detail.qty_terima += qty_terima` untuk setiap item
   - Jika semua item di PO sudah `qty_terima >= quantity`, update PO status = `closed`

4. **Partial Receive**:
   - Bisa buat multiple DO untuk 1 PO
   - Setiap DO hanya update qty yang diterima di DO tersebut

---

## 4. PEMBELIAN (Purchase Invoice)

### 4.1 Tujuan
- Mencatat invoice dari supplier
- Final posting ke accounting
- Link ke DO untuk validasi qty

### 4.2 Proses Flow

```
[DO Received] → Buat Invoice Pembelian → Link ke DO
   ↓
Validasi: Qty invoice <= Qty DO terima
   ↓
Simpan Invoice
   ↓
[Auto Actions]:
1. Update DO status → posted
2. Stock sudah ter-update dari DO (tidak double posting)
```

### 4.3 Database Tables

**tblpembelian_header**
```sql
- notransaksi (PK): BLYY+9digit (contoh: BL2500000001)
- no_supplier: Kode supplier
- no_order: Link ke PO
- no_do: Link ke DO
- total_qty: Total qty
- total_beli: Total harga
- diskon: Diskon
- total_akhir: Total setelah diskon
- kd_cabang: Kode cabang
```

**tblpembelian_detail**
```sql
- no_transaksi (FK): Link ke header
- no_item: Kode barang
- quantity: Qty yang dibeli
- harga_pokok: Harga per unit
- potongan: Potongan per item
- total: (quantity * harga_pokok) - potongan
- kd_cabang: Kode cabang
- status_trx: '0' = temp, '1' = saved
```

### 4.4 File Terkait

- **_admincab/pembelian_add.php**: Form invoice pembelian (sudah support DO link)
- **_admincab/pembelian.php**: List pembelian

### 4.5 Validasi & Business Rules

1. **Jika ada DO**:
   - Qty invoice tidak boleh melebihi qty_terima di DO per item
   - Validasi di pembelian_add.php:273-293
   - Setelah save, update DO status → `posted`

2. **Stock Posting**:
   - **PENTING**: Jika ada DO, stock sudah di-update saat DO received
   - Jika **TIDAK** ada DO (pembelian langsung), baru posting ke tbstok:
     ```sql
     INSERT INTO tbstok (
       tipe='2',
       no_transaksi=notransaksi_pembelian,
       masuk=quantity,
       ...
     )
     ```

---

## 5. COMPLETE WORKFLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────────┐
│                     COMPLETE PR-PO-DO-INVOICE FLOW              │
└─────────────────────────────────────────────────────────────────┘

STEP 1: PURCHASE REQUEST (PR)
┌──────────────────────┐
│  User creates PR     │
│  Status: draft       │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│  Submit PR           │
│  Status: submitted   │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐       ┌──────────────────┐
│  Manager approves    │  NO → │ Status: rejected │
│  Status: approved    │       └──────────────────┘
└──────────┬───────────┘
           │ YES
           ▼

STEP 2: PURCHASE ORDER (PO)
┌──────────────────────────────┐
│  Create PO from PR           │
│  - Link no_pr                │
│  - Select supplier           │
│  - Input harga_pokok         │
│  Status: open                │
└──────────┬───────────────────┘
           │
           ▼ (on save)
┌──────────────────────────────┐
│  Auto update PR:             │
│  - pr_detail.qty_po += qty   │
│  - pr status → partially or  │
│    fully_ordered             │
└──────────┬───────────────────┘
           │
           ▼

STEP 3: DELIVERY ORDER (DO)
┌──────────────────────────────┐
│  Goods arrive from supplier  │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│  Create DO from PO           │
│  - Input qty_kirim           │
│  - Input qty_terima          │
│  - Input qty_reject          │
│  Status: received            │
└──────────┬───────────────────┘
           │
           ▼ (on save)
┌──────────────────────────────┐
│  Auto actions:               │
│  1. Update stock (tbstok)    │
│     +qty_terima              │
│  2. Update PO qty_terima     │
│  3. Insert DO tracking       │
└──────────┬───────────────────┘
           │
           ▼

STEP 4: PURCHASE INVOICE (PEMBELIAN)
┌──────────────────────────────┐
│  Create invoice from DO      │
│  - Validate qty <= DO qty    │
│  - Input prices              │
└──────────┬───────────────────┘
           │
           ▼ (on save)
┌──────────────────────────────┐
│  Auto actions:               │
│  1. Update DO status →       │
│     posted                   │
│  2. NO stock update (already │
│     done in DO)              │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│  COMPLETED                   │
│  - Stock updated             │
│  - Invoice recorded          │
│  - PR closed                 │
│  - PO closed (if all items   │
│    received)                 │
└──────────────────────────────┘
```

---

## 6. STATUS TRACKING MATRIX

### 6.1 PR Status Changes

| From | To | Trigger | Auto Actions |
|------|----|---------|--------------|
| `draft` | `submitted` | User clicks submit | Notify approver |
| `submitted` | `approved` | Manager approves | Allow PO creation |
| `submitted` | `rejected` | Manager rejects | Notify requester |
| `approved` | `partially_ordered` | PO created (partial) | Track qty_po |
| `approved` | `fully_ordered` | PO created (all qty) | Close PR |
| `fully_ordered` | `closed` | All PO completed | Archive |

### 6.2 PO Status Changes

| From | To | Trigger | Auto Actions |
|------|----|---------|--------------|
| `open` | `closed` | All items received (qty_terima >= qty) | Update PR status |
| `open` | `cancelled` | User cancels | Revert PR qty_po |

### 6.3 DO Status Changes

| From | To | Trigger | Auto Actions |
|------|----|---------|--------------|
| `draft` | `received` | Save DO | Update stock, Update PO |
| `received` | `posted` | Invoice created | Lock DO |
| `received` | `closed` | Manual close | Archive |

---

## 7. STOCK POSTING RULES

### 7.1 Stock Update Points

| Transaksi | Stock Update? | Tipe | Keterangan |
|-----------|---------------|------|------------|
| PR | ❌ NO | - | Hanya request, belum ada barang |
| PO | ❌ NO | - | Order ke supplier, belum terima |
| DO | ✅ YES | 2 (in) | +qty_terima saat goods received |
| Invoice (with DO) | ❌ NO | - | Stock sudah di-update di DO |
| Invoice (no DO) | ✅ YES | 2 (in) | +quantity (pembelian langsung) |

### 7.2 Stock Posting Code

**Saat DO received** (do_from_po.php:117-120):
```php
if($qty_terima>0){
    mysqli_query($koneksi, "INSERT INTO tbstok (
        tipe='2',  -- 2 = pembelian masuk
        no_transaksi='{$no_do}',
        no_item='{$no_item}',
        tanggal='{$tanggal_do}',
        masuk={$qty_terima},
        keluar=0,
        keterangan='Penerimaan (DO)',
        kd_cabang='{$kd_cabang}'
    )");
}
```

**Saat Invoice (HANYA jika tidak ada DO)**:
```php
if($no_do == ''){  // Tidak ada DO = pembelian langsung
    mysqli_query($koneksi, "INSERT INTO tbstok (
        tipe='2',
        no_transaksi='{$notransaksi}',
        no_item='{$no_item}',
        masuk={$quantity},
        keluar=0,
        keterangan='Pembelian',
        kd_cabang='{$kd_cabang}'
    )");
}
```

---

## 8. NUMBERING CONVENTION

| Transaksi | Format | Example | Generated By |
|-----------|--------|---------|--------------|
| PR | PRYYMMcabang+seq | PR251101001 | sp_generate_no_pr |
| PO | PSYY+seq | PS2500000001 | sp_generate_no_po |
| DO | DOYYMMcabang+seq | DO251101001 | sp_generate_no_do |
| Invoice | BLYY+seq | BL2500000001 | FormatNoTrans() |

---

## 9. FILES CHECKLIST

### Existing Files (Already Implemented)
- ✅ **pr_add.php**: PR creation (needs table name fix)
- ✅ **pesanan_pembelian_add.php**: PO creation with PR link
- ✅ **do_from_po.php**: DO creation from PO
- ✅ **pembelian_add.php**: Invoice with DO validation

### Missing Files (Need to Create)
- ❌ **pr_list.php**: List all PRs with filter
- ❌ **pr_detail.php**: View PR details
- ❌ **pr_approve.php**: Approve/Reject PR
- ❌ **po_list.php**: List POs (may exist, check)
- ❌ **do_list.php**: List all DOs
- ❌ **do_tracking.php**: Track DO delivery status

---

## 10. NEXT IMPLEMENTATION STEPS

### Phase 1: Fix Existing (HIGH PRIORITY)
1. ✅ Create migration SQL script
2. ⏳ Fix pr_add.php table names (tblpr_header, tblpr_detail)
3. ⏳ Test PR → PO flow
4. ⏳ Test PO → DO flow
5. ⏳ Test DO → Invoice flow

### Phase 2: Create Missing Pages (MEDIUM PRIORITY)
1. Create pr_list.php
2. Create pr_approve.php
3. Create do_list.php
4. Add menu links for PR/DO

### Phase 3: Enhancements (LOW PRIORITY)
1. DO tracking with status history
2. Email notifications for PR approval
3. Reports: PR aging, PO outstanding, DO pending
4. Dashboard widgets for pending approvals

---

## 11. TROUBLESHOOTING

### Issue: "Table doesn't exist" error
**Solution**: Run pr_po_do_migration.sql first

### Issue: PR qty_po not updating after PO save
**Check**: pesanan_pembelian_add.php lines 237-260, ensure UPDATE query runs

### Issue: DO qty validation fails
**Check**:
1. Ensure PO has qty_terima column
2. Check do_from_po.php lines 65-81 validation logic

### Issue: Duplicate stock posting
**Check**:
1. Stock should only post at DO (if DO exists)
2. Or at Invoice (if no DO)
3. NEVER both

---

## 12. TESTING SCENARIOS

### Test Case 1: Full PR → PO → DO → Invoice
```
1. Create PR with 2 items (qty 10 each)
2. Submit PR
3. Approve PR
4. Create PO from PR (all qty)
5. Verify PR status = fully_ordered
6. Create DO from PO (receive all qty)
7. Verify stock increased by 20
8. Create Invoice from DO
9. Verify DO status = posted
10. Verify PO status = closed
```

### Test Case 2: Partial PO from PR
```
1. Create PR with item A (qty 100)
2. Approve PR
3. Create PO1 from PR (qty 60)
4. Verify PR status = partially_ordered
5. Create PO2 from PR (qty 40)
6. Verify PR status = fully_ordered
```

### Test Case 3: Multiple DO from Single PO
```
1. Create PO (qty 100)
2. Create DO1 (receive 60)
3. Verify PO qty_terima = 60
4. Create DO2 (receive 40)
5. Verify PO qty_terima = 100
6. Verify PO status = closed
```

---

**END OF WORKFLOW DOCUMENTATION**
