# IMPLEMENTASI REQUIREMENTS - MODUL PEMBELIAN, PENJUALAN & ANTAR CABANG

## Status: 🔄 In Progress
**Created:** 18 Desember 2024  
**Last Update:** 18 Desember 2024

---

## GAP ANALYSIS: Requirements vs Current Implementation

### ✅ ALREADY IMPLEMENTED

#### Modul Pembelian
- ✅ PR → PO → DO → Invoice workflow
- ✅ Pembayaran Hutang module exists
- ✅ Multi-supplier support
- ✅ Stock integration

#### Modul Penjualan
- ✅ Sales Order → Invoice workflow
- ✅ Pembayaran Piutang module exists
- ✅ Discount & tax calculation
- ✅ Customer management

#### Modul Antar Cabang
- ✅ Inter-branch sales order creation
- ✅ Auto pricing: Own branch (cost price), Partner (+5%)
- ✅ Auto discount: Own (100%), Partner (0%)
- ✅ Payment terms: Own (Tunai), Partner (Kredit 10 hari)
- ✅ Receiver can create purchase from sender's sales

---

### ❌ MISSING FEATURES (Requirements Not Yet Implemented)

#### 1. PEMBELIAN Module

**A. Missing Database Fields**
- ❌ Kolom `no_faktur` di tblpembelian_header
- ❌ Kolom `tanggal_faktur` di tblpembelian_header

**B. Enhanced Search/Filter** (pembelian.php)
- ❌ Filter by "Tipe Supplier" (Eksternal / Antar Cabang)
- ⚠️ Filter by Status Pembayaran (partial - needs enhancement)
- ✅ Filter by Date (already exists)
- ✅ Filter by Supplier (already exists)

**C. Pembayaran Hutang Enhancement** (pmby_hutang.php)
- ❌ Filter by "Tipe Supplier" (Eksternal / Antar Cabang)
- ❌ Filter by "Rentang Tanggal Jatuh Tempo"
- ❌ **Batch Payment with Checkbox Selection** (pelunasan beberapa faktur sekaligus)

**D. Laporan Hutang**
- ❌ Laporan hutang detail per faktur per supplier
- ❌ Laporan hutang total per supplier
- ❌ WA integration untuk kirim ke manajemen

---

#### 2. PENJUALAN Module

**A. Stock Validation** (penjualan_add.php)
- ❌ **Block item with stok=0 & harga_naik** 
  - Requirement: "BARANG DGN STATUS STOK KOSONG & HARGA NAIK TIDAK BISA DIINPUT KE TRANSAKSI"
  - Need to check: tblitem.stok, tblitem.status_harga_naik

**B. Enhanced Features**
- ⚠️ Pelunasan penjualan (pmby_piutang.php) - exists but may need enhancement

---

#### 3. ANTAR CABANG Module

**A. Penerimaan Antar Cabang** (pembelian_cab_add.php)
- ⚠️ Filter "hanya nota yang belum pernah diterima" - needs verification
- ✅ Data penjualan → pesanan pembelian (already implemented)
- ✅ Transfer margin & terms (already implemented)

**B. UI/UX Enhancement**
- ❌ Better visualization untuk cabang pengirim selection
- ❌ Status tracking: "sudah diterima" vs "belum diterima"

---

#### 4. SECURITY & PERFORMANCE (Critical)

**A. SQL Injection** ⚠️ URGENT
- ❌ All module files use direct SQL without prepared statements
- Files affected: ALL 14 files

**B. Database Constraints**
- ❌ Missing Foreign Keys
- ❌ Missing CHECK constraints
- ❌ Missing Indexes for performance

**C. Transaction Management**
- ❌ No BEGIN/COMMIT/ROLLBACK in multi-table operations

---

## IMPLEMENTATION PLAN

### 🔴 PHASE 1: Critical Database Changes (Priority: URGENT)

#### Task 1.1: Add Missing Fields to tblpembelian_header
```sql
ALTER TABLE tblpembelian_header 
ADD COLUMN no_faktur VARCHAR(50) NULL AFTER notransaksi,
ADD COLUMN tanggal_faktur DATE NULL AFTER no_faktur;

-- Add index
CREATE INDEX idx_pembelian_faktur ON tblpembelian_header(no_faktur);
```

**Files to Modify:**
- `pembelian_add.php` - Add input fields for no_faktur & tanggal_faktur
- `pembelian.php` - Add columns to display table
- `pembelian_detail.php` - Display in detail view
- `pembelian_struk.php` - Add to print layout

**Estimated Time:** 2-3 hours

---

#### Task 1.2: Add tipe_supplier to Distinguish External vs Inter-Branch
```sql
-- Option 1: Add to tblsupplier
ALTER TABLE tblsupplier 
ADD COLUMN tipe_supplier ENUM('Eksternal', 'Antar Cabang') DEFAULT 'Eksternal';

-- Option 2: Use flag in tblpembelian_header
ALTER TABLE tblpembelian_header
ADD COLUMN tipe_transaksi ENUM('Normal', 'Antar Cabang') DEFAULT 'Normal';
```

**Recommendation:** Use Option 2 (flag in header) because:
- Supplier bisa digunakan untuk both types
- Lebih fleksibel untuk tracking per transaction

**Estimated Time:** 1-2 hours

---

### 🟡 PHASE 2: Enhanced Search & Filters (Priority: HIGH)

#### Task 2.1: Enhanced Pembelian Search
**File:** `pembelian.php`

Add filters:
- Tipe Transaksi (Normal / Antar Cabang)
- Status Pembayaran (Lunas / Belum Lunas)
- Date Range (already exists, enhance if needed)

```php
// Add to search form
<select name="cbotipe" class="form-control">
    <option value="">-- Semua Tipe --</option>
    <option value="Normal">Normal</option>
    <option value="Antar Cabang">Antar Cabang</option>
</select>

<select name="cbostatus" class="form-control">
    <option value="">-- Semua Status --</option>
    <option value="Lunas">Lunas</option>
    <option value="Belum Lunas">Belum Lunas</option>
</select>
```

**Estimated Time:** 2-3 hours

---

#### Task 2.2: Enhanced Pembayaran Hutang Search
**File:** `pmby_hutang_add.php` (create if not exists)

Add filters:
- Nama Supplier
- Tipe Supplier (Eksternal / Antar Cabang)
- Rentang Tanggal Jatuh Tempo

**Estimated Time:** 3-4 hours

---

### 🟠 PHASE 3: Batch Payment Implementation (Priority: HIGH)

#### Task 3.1: Batch Payment with Checkbox Selection
**File:** `pmby_hutang_add.php`

**Features:**
1. Display unpaid invoices with checkboxes
2. User can select multiple invoices
3. Calculate total payment
4. Process batch payment in single transaction

**Implementation Steps:**

1. **Display Invoice List with Checkboxes:**
```php
<form method="post" id="formBatchPayment">
<table class="table">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>No. Faktur</th>
            <th>Tanggal</th>
            <th>Supplier</th>
            <th>Total Faktur</th>
            <th>Sisa Hutang</th>
            <th>Jatuh Tempo</th>
            <th>Bayar</th>
        </tr>
    </thead>
    <tbody>
    <?php
    // Query unpaid invoices
    $sql = "SELECT p.notransaksi, p.tanggal, p.no_supplier, 
                   s.namasupplier, p.total_akhir, p.jatuh_tempo,
                   COALESCE(SUM(pb.bayar), 0) as total_bayar,
                   p.total_akhir - COALESCE(SUM(pb.bayar), 0) as sisa_hutang
            FROM tblpembelian_header p
            INNER JOIN tblsupplier s ON p.no_supplier = s.nosupplier
            LEFT JOIN tblpembayaran_hutang_detail pb ON p.notransaksi = pb.no_pembelian
            WHERE p.carabayar = 'Kredit' 
              AND p.kd_cabang = '$kd_cabang'
              AND p.total_akhir > COALESCE(SUM(pb.bayar), 0)
            GROUP BY p.notransaksi
            HAVING sisa_hutang > 0
            ORDER BY p.jatuh_tempo ASC";
    
    $result = mysqli_query($koneksi, $sql);
    while($row = mysqli_fetch_array($result)) {
        $sisa = $row['sisa_hutang'];
    ?>
        <tr>
            <td>
                <input type="checkbox" name="invoice[]" 
                       value="<?php echo $row['notransaksi']; ?>"
                       data-sisa="<?php echo $sisa; ?>"
                       class="chk-invoice">
            </td>
            <td><?php echo $row['notransaksi']; ?></td>
            <td><?php echo $row['tanggal']; ?></td>
            <td><?php echo $row['namasupplier']; ?></td>
            <td align="right"><?php echo number_format($row['total_akhir'], 0); ?></td>
            <td align="right"><?php echo number_format($sisa, 0); ?></td>
            <td><?php echo $row['jatuh_tempo']; ?></td>
            <td>
                <input type="number" 
                       name="bayar[<?php echo $row['notransaksi']; ?>]"
                       class="form-control input-bayar"
                       max="<?php echo $sisa; ?>"
                       value="0"
                       disabled>
            </td>
        </tr>
    <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7" align="right"><strong>Total Bayar:</strong></td>
            <td><input type="text" id="totalBayar" class="form-control" readonly></td>
        </tr>
    </tfoot>
</table>

<button type="submit" name="btnSaveBatch" class="btn btn-success">
    <i class="fa fa-save"></i> Simpan Pembayaran
</button>
</form>

<script>
// Auto-calculate total
$('.chk-invoice').on('change', function() {
    var notrans = $(this).val();
    var isChecked = $(this).is(':checked');
    var inputBayar = $('input[name="bayar['+notrans+']"]');
    var sisa = $(this).data('sisa');
    
    if(isChecked) {
        inputBayar.prop('disabled', false).val(sisa);
    } else {
        inputBayar.prop('disabled', true).val(0);
    }
    
    calculateTotal();
});

$('.input-bayar').on('keyup', function() {
    calculateTotal();
});

function calculateTotal() {
    var total = 0;
    $('.input-bayar:enabled').each(function() {
        total += parseFloat($(this).val() || 0);
    });
    $('#totalBayar').val(number_format(total, 0));
}

// Select all
$('#selectAll').on('change', function() {
    $('.chk-invoice').prop('checked', $(this).is(':checked')).trigger('change');
});
</script>
```

2. **Process Batch Payment (Backend):**
```php
if(isset($_POST['btnSaveBatch'])) {
    $invoices = $_POST['invoice'];
    $bayar = $_POST['bayar'];
    
    if(empty($invoices)) {
        echo "<script>alert('Pilih minimal 1 invoice!');</script>";
        exit;
    }
    
    // Generate payment number
    $no_pembayaran = generate_no_pembayaran();
    $tanggal = date('Y-m-d');
    
    // Start transaction
    mysqli_begin_transaction($koneksi);
    
    try {
        $total_bayar = 0;
        $no_supplier = '';
        
        // Insert payment header
        foreach($invoices as $notransaksi) {
            $bayar_amount = floatval($bayar[$notransaksi]);
            
            if($bayar_amount > 0) {
                // Get supplier from first invoice
                if($no_supplier == '') {
                    $check = mysqli_query($koneksi, "SELECT no_supplier FROM tblpembelian_header WHERE notransaksi='$notransaksi'");
                    $data = mysqli_fetch_array($check);
                    $no_supplier = $data['no_supplier'];
                }
                
                $total_bayar += $bayar_amount;
            }
        }
        
        // Insert payment header
        $sql_header = "INSERT INTO tblpembayaran_hutang_header 
                      (no_transaksi, tanggal, no_supplier, total_bayar, kd_cabang, user)
                      VALUES ('$no_pembayaran', '$tanggal', '$no_supplier', '$total_bayar', '$kd_cabang', '$_nama')";
        mysqli_query($koneksi, $sql_header);
        
        // Insert payment details
        $nobaris = 1;
        foreach($invoices as $notransaksi) {
            $bayar_amount = floatval($bayar[$notransaksi]);
            
            if($bayar_amount > 0) {
                // Get invoice total & previous payments
                $check = mysqli_query($koneksi, "
                    SELECT p.total_akhir,
                           COALESCE(SUM(pb.bayar), 0) as total_bayar_sebelumnya
                    FROM tblpembelian_header p
                    LEFT JOIN tblpembayaran_hutang_detail pb ON p.notransaksi = pb.no_pembelian
                    WHERE p.notransaksi = '$notransaksi'
                    GROUP BY p.notransaksi
                ");
                $data = mysqli_fetch_array($check);
                $total_faktur = $data['total_akhir'];
                $bayar_sebelumnya = $data['total_bayar_sebelumnya'];
                $sisa = $total_faktur - $bayar_sebelumnya - $bayar_amount;
                
                $sql_detail = "INSERT INTO tblpembayaran_hutang_detail
                              (no_transaksi, nobaris, no_pembelian, total_faktur, bayar, sisa)
                              VALUES ('$no_pembayaran', '$nobaris', '$notransaksi', '$total_faktur', '$bayar_amount', '$sisa')";
                mysqli_query($koneksi, $sql_detail);
                
                $nobaris++;
            }
        }
        
        mysqli_commit($koneksi);
        
        echo "<script>
                alert('Pembayaran berhasil disimpan!');
                window.location='pmby_hutang.php';
              </script>";
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
```

**Estimated Time:** 6-8 hours

---

### 🟢 PHASE 4: Stock Validation (Priority: MEDIUM)

#### Task 4.1: Block Zero Stock Items with Price Increase
**File:** `penjualan_add.php`

**Implementation:**

1. **Add field to tblitem:**
```sql
ALTER TABLE tblitem
ADD COLUMN status_harga_naik TINYINT(1) DEFAULT 0 COMMENT '0=Normal, 1=Harga Naik';

-- Or use price history comparison
```

2. **Validate on item selection:**
```php
// In penjualan_add.php when adding item
if(isset($_POST['btnadd'])) {
    $txtkdbarang = $_POST['txtcaribrg'];
    $txtqty = $_POST['txtqty'];
    
    // Check stock & price status
    $check = mysqli_query($koneksi, "
        SELECT 
            i.noitem, i.namaitem,
            COALESCE(SUM(s.masuk) - SUM(s.keluar), 0) as stok_tersedia,
            i.status_harga_naik
        FROM tblitem i
        LEFT JOIN tbstok s ON i.noitem = s.no_item AND s.kd_cabang = '$kd_cabang'
        WHERE i.noitem = '$txtkdbarang'
        GROUP BY i.noitem
    ");
    
    $item = mysqli_fetch_array($check);
    
    // Validation: Stok kosong DAN harga naik = TIDAK BOLEH
    if($item['stok_tersedia'] <= 0 && $item['status_harga_naik'] == 1) {
        echo "<script>
                alert('PERHATIAN: Barang " . $item['namaitem'] . " tidak dapat dijual karena stok kosong dan harga sedang naik!');
                window.location='penjualan_add.php';
              </script>";
        exit;
    }
    
    // Check if qty requested > available stock
    if($txtqty > $item['stok_tersedia']) {
        echo "<script>
                alert('Qty melebihi stok tersedia (" . $item['stok_tersedia'] . ")!');
              </script>";
        // Don't exit, allow user to adjust
    }
    
    // Continue with add to cart...
}
```

3. **Add visual indicator in item list:**
```php
// In item search results
<?php
$sql_item = "SELECT i.*, 
              COALESCE(SUM(s.masuk) - SUM(s.keluar), 0) as stok_tersedia
              FROM tblitem i
              LEFT JOIN tbstok s ON i.noitem = s.no_item AND s.kd_cabang = '$kd_cabang'
              GROUP BY i.noitem";

while($item = mysqli_fetch_array($result)) {
    $is_blocked = ($item['stok_tersedia'] <= 0 && $item['status_harga_naik'] == 1);
    $row_class = $is_blocked ? 'danger' : '';
?>
    <tr class="<?php echo $row_class; ?>">
        <td><?php echo $item['noitem']; ?></td>
        <td><?php echo $item['namaitem']; ?></td>
        <td><?php echo $item['stok_tersedia']; ?></td>
        <td>
            <?php if($is_blocked) { ?>
                <span class="label label-danger">TIDAK BISA DIJUAL</span>
            <?php } else { ?>
                <button class="btn btn-sm btn-success" onclick="selectItem('<?php echo $item['noitem']; ?>')">
                    Pilih
                </button>
            <?php } ?>
        </td>
    </tr>
<?php } ?>
```

**Estimated Time:** 4-5 hours

---

### 🔵 PHASE 5: Reporting Enhancement (Priority: LOW)

#### Task 5.1: Laporan Hutang Detail per Faktur per Supplier
**File:** `laporan_hutang_detail.php` (new file)

```php
// Generate report with filters:
// - Date range
// - Supplier
// - Status (Lunas/Belum Lunas)

// Export to Excel/PDF
// WA Integration (optional)
```

**Estimated Time:** 6-8 hours

---

### 🔴 PHASE 6: Security Fixes (Priority: URGENT)

#### Task 6.1: Implement Prepared Statements
**All Files:** 14 files need refactoring

**Example Fix:**
```php
// BEFORE (Vulnerable):
$sql = "SELECT * FROM tblitem WHERE noitem='$txtcaribrg'";
$result = mysqli_query($koneksi, $sql);

// AFTER (Secure):
$stmt = $koneksi->prepare("SELECT * FROM tblitem WHERE noitem=?");
$stmt->bind_param("s", $txtcaribrg);
$stmt->execute();
$result = $stmt->get_result();
```

**Files Priority:**
1. pmby_hutang_add.php (payment processing)
2. pembelian_add.php (invoice creation)
3. penjualan_add.php (sales processing)
4. do_from_po.php (goods receipt)
5. All other files

**Estimated Time:** 20-30 hours (for all 14 files)

---

## IMPLEMENTATION PRIORITY SUMMARY

| Priority | Phase | Task | Estimated Time | Impact |
|----------|-------|------|---------------|--------|
| 🔴 URGENT | 1.1 | Add no_faktur fields | 2-3 hours | HIGH - Required by user |
| 🔴 URGENT | 1.2 | Add tipe_supplier | 1-2 hours | HIGH - For filtering |
| 🔴 URGENT | 6.1 | Security fixes | 20-30 hours | CRITICAL - SQL injection |
| 🟡 HIGH | 2.1 | Enhanced search pembelian | 2-3 hours | MEDIUM - UX improvement |
| 🟡 HIGH | 2.2 | Enhanced search hutang | 3-4 hours | MEDIUM - UX improvement |
| 🟡 HIGH | 3.1 | Batch payment | 6-8 hours | HIGH - Core feature request |
| 🟢 MEDIUM | 4.1 | Stock validation | 4-5 hours | MEDIUM - Business rule |
| 🔵 LOW | 5.1 | Laporan hutang | 6-8 hours | LOW - Reporting |

**Total Estimated Time:** 45-65 hours (~6-8 working days)

---

## TESTING CHECKLIST

### Phase 1 Testing
- [ ] No_faktur can be input and saved
- [ ] Tanggal_faktur displays correctly
- [ ] Old data without no_faktur still works
- [ ] Print layout includes new fields

### Phase 2 Testing
- [ ] Filters work correctly
- [ ] Combined filters work (e.g., date + type + status)
- [ ] Search returns correct results
- [ ] Pagination works with filters

### Phase 3 Testing
- [ ] Can select multiple invoices
- [ ] Total calculates correctly
- [ ] Payment saves to both header & detail tables
- [ ] Payment reduces outstanding balance
- [ ] Cannot overpay an invoice
- [ ] Transaction rollback works on error

### Phase 4 Testing
- [ ] Zero stock items are blocked
- [ ] Price increase flag works correctly
- [ ] Items with stock can be sold normally
- [ ] Error messages are clear

### Phase 6 Testing
- [ ] SQL injection attempts fail
- [ ] All CRUD operations still work
- [ ] Performance is not degraded
- [ ] No breaking changes

---

## DEPLOYMENT PLAN

### Pre-Deployment
1. Backup database
2. Backup code files
3. Test on development environment
4. User Acceptance Testing (UAT)

### Deployment Steps
1. Deploy database changes (Phase 1)
2. Deploy code changes (Phase 2-4)
3. Security fixes (Phase 6)
4. Monitor for errors
5. User training if needed

### Rollback Plan
- Keep SQL rollback scripts
- Keep backup of original files
- Document all changes

---

## NOTES

### Assumptions
- Database: MySQL/MariaDB
- PHP Version: 7.x or 8.x
- Bootstrap UI framework in use
- ACE Admin template in use

### Dependencies
- jQuery for client-side interactions
- DataTables for pagination
- Bootstrap for UI components

### Future Enhancements (Post-Implementation)
- Mobile app for warehouse
- Real-time notifications (Email/WA)
- Dashboard analytics
- Advanced reporting with charts
- API for third-party integration

---

**Status Legend:**
- ✅ Complete
- 🔄 In Progress  
- ❌ Not Started
- ⚠️ Needs Verification

**Next Action:** Start with Phase 1 (Database changes) as foundation for other phases.
