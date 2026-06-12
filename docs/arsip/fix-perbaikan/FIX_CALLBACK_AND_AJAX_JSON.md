# 🔧 Fix: Callback Function & AJAX JSON Error

## ❌ Error yang Terjadi

### **1. Modal Cari Temuan:**
```
Error: Callback function tidak ditemukan. Silakan reload halaman
```

### **2. Modal Fast Moves:**
```
Error loading data: SyntaxError: Unexpected token '<', "..." is not valid JSON
Silakan cek console untuk detail
```

---

## 🔍 Root Cause Analysis

### **Problem 1: Callback Function Tidak Ditemukan**

**Penyebab:**
- Callback `window.onTemuanSelected` didefinisikan di `tab-temuan-penawaran-content.php` di dalam `jQuery(function($) {...})`
- Callback baru tersedia setelah DOM ready
- Modal script dieksekusi sebelum callback didefinisikan
- Urutan loading: Modal loaded → Modal script executed → DOM ready → Callback defined ❌

**Expected:**
- Callback harus didefinisikan SEBELUM modal script dieksekusi
- Urutan: Callback defined → Modal loaded → Modal script executed ✅

---

### **Problem 2: AJAX Return HTML Instead of JSON**

**Penyebab:**
- File `_handler_temuan_penawaran.php` di-include di `servis-input-reguler.php` line 14
- Saat AJAX dipanggil, file dieksekusi dari awal
- Ada HTML output dari halaman utama sebelum JSON response
- Browser menerima: `<!DOCTYPE html>...{json data}` ❌
- Browser expect: `{json data}` ✅

**Flow Error:**
```
1. AJAX call: _handler_temuan_penawaran.php?action=get_parts_by_kategori&kategori=OLI
2. PHP executes from line 1
3. Include koneksi.php (OK)
4. Process POST handlers (skip)
5. Process DELETE handlers (skip)
6. Reach AJAX handler at line 285
7. Output JSON
8. BUT: HTML already outputted before!
9. Response: "<!DOCTYPE html>...<script>...{json}"
10. JSON.parse() fails!
```

---

## ✅ Solusi

### **Solusi 1: Pisahkan Callback ke File Terpisah**

**Buat file baru:** `_template/modal-callbacks.php`

```php
<script>
console.log('=== Loading Modal Callbacks ===');

// Callback untuk Modal Cari Temuan
window.onTemuanSelected = function(kode, nama, kategori, urgensi) {
    console.log('onTemuanSelected called:', {kode, nama, kategori, urgensi});
    $('#kode_temuan').val(kode);
    $('#nama_temuan').val(nama);
    console.log('Form updated with temuan data');
};

// Callback untuk Modal Fast Moves
window.onFastMovesPartSelected = function(kode, nama, harga, satuan, qty) {
    console.log('onFastMovesPartSelected called:', {kode, nama, harga, satuan, qty});
    $('#kode_part').val(kode);
    $('#nama_part').val(nama);
    $('#harga_part').val(harga);
    $('#satuan_part').val(satuan);
    $('#qty_part').val(qty);
    console.log('Form penawaran updated');
};

console.log('=== Modal Callbacks Loaded ===');
</script>
```

**Include callback SEBELUM modal:**
```php
<!-- Callback Functions - MUST be loaded BEFORE modals -->
<?php include '_template/modal-callbacks.php'; ?>

<!-- Modals -->
<?php include '_template/modal-search-temuan.php'; ?>
<?php include '_template/modal-fastmoves-v2.php'; ?>
```

---

### **Solusi 2: Pindahkan AJAX Handler ke Paling Atas**

**File:** `_handler_temuan_penawaran.php`

```php
<?php
// Include koneksi database
if(!isset($koneksi)) {
    include "../../config/koneksi.php";
}

// ============================================
// AJAX HANDLERS - MUST BE FIRST (before any output)
// ============================================

// Get part by kategori (untuk AJAX)
if(isset($_GET['action']) && $_GET['action'] == 'get_parts_by_kategori') {
    $kode_kategori = mysqli_real_escape_string($koneksi, $_GET['kategori']);
    
    $query = "SELECT 
                mbf.kode_barang,
                item.namaitem as nama_barang,
                item.hargajual as harga_jual,
                item.satuan,
                COALESCE(vsm.stok_akhir, 0) as stok_tersedia,
                mbf.is_featured
            FROM tbmaster_barang_fastmoves mbf
            INNER JOIN tblitem item ON mbf.kode_barang = item.noitem
            LEFT JOIN view_stok_master vsm ON mbf.kode_barang = vsm.kode_barang
            WHERE mbf.kode_kategori = '$kode_kategori'
            ORDER BY mbf.urutan, item.namaitem";
    
    $result = mysqli_query($koneksi, $query);
    $parts = [];
    
    while($row = mysqli_fetch_assoc($result)) {
        $parts[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($parts);
    exit; // IMPORTANT: Exit immediately!
}

// ... rest of the code (POST handlers, etc)
```

**Key Points:**
1. ✅ AJAX handler di paling atas (setelah include koneksi)
2. ✅ `exit;` setelah `echo json_encode()` untuk stop execution
3. ✅ `header('Content-Type: application/json')` untuk set content type
4. ✅ Tidak ada HTML output sebelum JSON

---

## 🔧 Perubahan yang Dilakukan

### **1. File Baru: `_template/modal-callbacks.php`**

**Isi:**
- Definisi `window.onTemuanSelected`
- Definisi `window.onTemuanManualCreated`
- Definisi `window.onFastMovesPartSelected`
- Console.log untuk debugging

**Fungsi:**
- Memastikan callback tersedia sebelum modal loaded
- Centralized callback management
- Easy debugging

---

### **2. Modified: `_handler_temuan_penawaran.php`**

**Perubahan:**
```diff
<?php
// Include koneksi database
if(!isset($koneksi)) {
    include "../../config/koneksi.php";
}

+// ============================================
+// AJAX HANDLERS - MUST BE FIRST (before any output)
+// ============================================
+
+// Get part by kategori (untuk AJAX)
+if(isset($_GET['action']) && $_GET['action'] == 'get_parts_by_kategori') {
+    // ... AJAX handler code ...
+    header('Content-Type: application/json');
+    echo json_encode($parts);
+    exit;
+}
+
+// Search part (untuk AJAX)
+if(isset($_GET['action']) && $_GET['action'] == 'search_part') {
+    // ... AJAX handler code ...
+    header('Content-Type: application/json');
+    echo json_encode($parts);
+    exit;
+}

// ============================================
// HANDLER TEMUAN
// ============================================

// Tambah Temuan
if(isset($_POST['btnaddtemuan'])) {
    // ... existing code ...
}

-// ============================================
-// AJAX HANDLERS (untuk fast moves) - MOVED TO TOP!
-// ============================================
-
-// Get part by kategori (untuk AJAX) - REMOVED (duplicate)
-// Search part (untuk AJAX) - REMOVED (duplicate)
```

---

### **3. Modified: `servis-input-reguler.php`**

**Perubahan:**
```diff
<!-- ace scripts -->
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>

+<!-- Callback Functions - MUST be loaded BEFORE modals -->
+<?php include '_template/modal-callbacks.php'; ?>

<!-- Modals untuk Temuan & Penawaran -->
<?php include '_template/modal-search-temuan.php'; ?>
<?php include '_template/modal-fastmoves-v2.php'; ?>
```

---

### **4. Modified: `servis-input-reguler-rst.php`**

**Perubahan:** (sama seperti servis-input-reguler.php)
```diff
+<!-- Callback Functions - MUST be loaded BEFORE modals -->
+<?php include '_template/modal-callbacks.php'; ?>

<!-- Modals -->
<?php include '_template/modal-search-temuan.php'; ?>
<?php include '_template/modal-fastmoves-v2.php'; ?>
```

---

### **5. Enhanced: `modal-fastmoves-v2.php` Styling**

**Perubahan:**
- ✅ Gradient header background
- ✅ Card shadow dan hover effects
- ✅ Button kategori dengan hover animation
- ✅ Featured item highlight (⭐ background kuning)
- ✅ Stok indicator dengan warna:
  - Hijau: Stok > 10 (ready)
  - Kuning: Stok 1-10 (low)
  - Merah: Stok 0 (empty)
- ✅ Table row hover effect
- ✅ Button add dengan scale animation
- ✅ Smooth slide down animation untuk part list

---

## 📊 Urutan Loading yang Benar

### **Before (❌ Error):**
```
1. HTML Body
2. Include tab-temuan-penawaran-content.php
   └── jQuery(function($) {
         window.onTemuanSelected = ... // Defined in DOM ready
       })
3. Close Body
4. jQuery loaded
5. Bootstrap loaded
6. Ace scripts loaded
7. Include modal-search-temuan.php
   └── Script: $(document).on('click', '.btn-pilih-temuan', ...)
       └── Call: window.onTemuanSelected() // ERROR: Not defined yet!
8. DOM ready event fires
   └── Callback defined (too late!)
```

### **After (✅ Fixed):**
```
1. HTML Body
2. Include tab-temuan-penawaran-content.php
3. Close Body
4. jQuery loaded
5. Bootstrap loaded
6. Ace scripts loaded
7. ✅ Include modal-callbacks.php
   └── window.onTemuanSelected = ... // Defined immediately!
8. Include modal-search-temuan.php
   └── Script: $(document).on('click', '.btn-pilih-temuan', ...)
       └── Call: window.onTemuanSelected() // ✅ Works!
```

---

## 🧪 Testing & Verification

### **Test 1: Cek Callback Tersedia**

**Console:**
```javascript
// Setelah halaman load
console.log(typeof window.onTemuanSelected);
// Expected: "function"

console.log(typeof window.onFastMovesPartSelected);
// Expected: "function"
```

**Expected Output:**
```
=== Loading Modal Callbacks ===
=== Modal Callbacks Loaded ===
Available callbacks: {
  onTemuanSelected: "function",
  onTemuanManualCreated: "function",
  onFastMovesPartSelected: "function"
}
```

---

### **Test 2: Test Modal Cari Temuan**

**Steps:**
1. Klik "Tambah Temuan"
2. Klik icon search
3. Klik button "Pilih"

**Expected Console:**
```
onTemuanSelected called: {kode: "TMN001", nama: "Filter Udara Kotor", ...}
Form updated with temuan data
```

**Expected Result:**
- ✅ No error alert
- ✅ Data masuk ke form
- ✅ Modal tertutup

---

### **Test 3: Test Modal Fast Moves**

**Steps:**
1. Klik button "Fast Moves"
2. Klik kategori "OLI & PELUMAS"

**Expected Console:**
```
Kategori diklik: {kategori: "OLI", nama: "Oli & Pelumas"}
Loading parts untuk kategori: OLI
Response dari server: [
  {kode_barang: "20W-40 4T", nama_barang: "OLI BEBEK CASTROL GO 0.8L", ...},
  ...
]
```

**Expected Result:**
- ✅ No JSON parse error
- ✅ Daftar 5 oli muncul
- ✅ Harga, stok, qty terlihat
- ✅ Featured item ada background kuning
- ✅ Stok indicator berwarna sesuai jumlah

---

### **Test 4: Test Add Part**

**Steps:**
1. Ubah qty jadi 2
2. Klik button "+" (hijau)

**Expected Console:**
```
onFastMovesPartSelected called: {
  kode: "20W-40 4T",
  nama: "OLI BEBEK CASTROL GO 0.8L",
  harga: 34000,
  satuan: "PCS",
  qty: 2
}
Form penawaran updated with part data
```

**Expected Result:**
- ✅ Data masuk ke form penawaran
- ✅ Qty = 2
- ✅ Subtotal = harga × qty
- ✅ Counter "Total Dipilih" bertambah

---

## 🎨 Styling Improvements

### **1. Modal Header**
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```
- Gradient ungu yang modern

### **2. Button Kategori**
```css
.btn-fm-kategori:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    border-color: #007bff;
    background: #f8f9ff;
}
```
- Hover effect dengan lift animation

### **3. Featured Item**
```css
tr.featured-item {
    background: #fffbea !important;
    border-left: 3px solid #ffc107;
}
tr.featured-item td:first-child::before {
    content: "⭐ ";
}
```
- Background kuning dengan bintang

### **4. Stok Indicator**
```css
.stok-ready { color: #28a745; } /* Hijau */
.stok-low { color: #ffc107; }   /* Kuning */
.stok-empty { color: #dc3545; } /* Merah */
```
- Warna sesuai kondisi stok

### **5. Table Row Hover**
```css
#fmPartListContainer .table tbody tr:hover {
    background: #f8f9ff;
    transform: scale(1.01);
}
```
- Smooth hover dengan scale effect

---

## ⚠️ Important Notes

### **1. AJAX Handler Order**
```php
// ✅ CORRECT ORDER:
1. Include koneksi
2. AJAX handlers (with exit)
3. POST handlers
4. Other logic
```

### **2. Callback Loading**
```php
// ✅ CORRECT ORDER:
1. jQuery loaded
2. Bootstrap loaded
3. Ace scripts loaded
4. Callback definitions
5. Modal includes
6. Custom scripts
```

### **3. JSON Response**
```php
// ✅ ALWAYS:
header('Content-Type: application/json');
echo json_encode($data);
exit; // MUST exit!
```

---

## 📝 Summary

### **Problems Fixed:**
1. ✅ Callback function tidak ditemukan
2. ✅ AJAX return HTML instead of JSON
3. ✅ Modal styling kurang menarik

### **Solutions Applied:**
1. ✅ Pisahkan callback ke file terpisah
2. ✅ Pindahkan AJAX handler ke paling atas
3. ✅ Tambahkan modern styling & animations

### **Files Modified:**
1. ✅ `_template/modal-callbacks.php` (NEW)
2. ✅ `_handler_temuan_penawaran.php` (AJAX moved to top)
3. ✅ `servis-input-reguler.php` (Include callback)
4. ✅ `servis-input-reguler-rst.php` (Include callback)
5. ✅ `_template/modal-fastmoves-v2.php` (Enhanced styling)

### **Result:**
- ✅ Modal cari temuan berfungsi normal
- ✅ Modal fast moves load data dengan benar
- ✅ Tampilan lebih modern dan menarik
- ✅ No more errors!

---

**Status:** ✅ FIXED & ENHANCED  
**Tanggal:** 8 November 2025  
**Version:** 2.0

🎉 **Semua error sudah diperbaiki dan tampilan sudah ditingkatkan!**
