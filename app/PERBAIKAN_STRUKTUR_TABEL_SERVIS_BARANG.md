# PERBAIKAN STRUKTUR TABEL tblservis_barang
**Tanggal:** 1 Desember 2025
**Issue:** Unknown column 'noitem' in 'field list'

---

## 🐛 MASALAH YANG DITEMUKAN

### Error Report:
```
Gagal menolak penawaran! Error: Unknown column 'noitem' in 'field list'
```

**Root Cause:** Query INSERT menggunakan nama kolom yang **SALAH** / **TIDAK ADA** di tabel `tblservis_barang`

---

## 📊 STRUKTUR TABEL AKTUAL

### `tblservis_barang` - Struktur BENAR dari Database:

```sql
CREATE TABLE `tblservis_barang` (
  `no_service` varchar(50) NOT NULL,
  `nobaris` int(11) NOT NULL,
  `no_item` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `qty_retur` int(11) NOT NULL,
  `harga_jual` double NOT NULL,
  `potongan` double NOT NULL,
  `total` double NOT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

### Kolom yang ADA:
1. ✅ `no_service` - Nomor service
2. ✅ `nobaris` - Nomor baris (sequential per service)
3. ✅ `no_item` - Kode item/barang
4. ✅ `quantity` - Jumlah
5. ✅ `qty_retur` - Jumlah retur
6. ✅ `harga_jual` - Harga jual per unit
7. ✅ `potongan` - Diskon/potongan
8. ✅ `total` - Total harga
9. ✅ `id` - Primary key (auto increment)

### Kolom yang TIDAK ADA:
- ❌ `satuan` - TIDAK ADA
- ❌ `harga` - SALAH, seharusnya `harga_jual`
- ❌ `discount` - SALAH, seharusnya `potongan`
- ❌ `keterangan` - TIDAK ADA

---

## 🔧 PERBAIKAN YANG DILAKUKAN

### Query INSERT - SEBELUM (SALAH):

```php
$sql_insert = "INSERT INTO tblservis_barang
              (no_service, no_item, quantity, satuan, harga, discount, total, keterangan)
              VALUES
              ('$no_service_post',
               '{$penawaran_data['kode_barang']}',
               '{$penawaran_data['quantity']}',
               '{$item_data['satuan']}',              // ❌ Kolom tidak ada
               '{$penawaran_data['harga_satuan']}',   // ❌ Nama kolom salah
               0,                                      // ❌ Nama kolom salah
               '{$penawaran_data['total_harga']}',
               'Dari penawaran part')";                // ❌ Kolom tidak ada
```

### Query INSERT - SESUDAH (BENAR):

```php
// Get max nobaris for sequential numbering
$q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) as max_nobaris
                                     FROM tblservis_barang
                                     WHERE no_service='$no_service_post'");
$nobaris_data = mysqli_fetch_array($q_nobaris);
$nobaris = $nobaris_data['max_nobaris'] + 1;

$sql_insert = "INSERT INTO tblservis_barang
              (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total)
              VALUES
              ('$no_service_post',
               $nobaris,                              // ✅ Auto-increment per service
               '{$penawaran_data['kode_barang']}',
               '{$penawaran_data['quantity']}',
               0,                                      // ✅ qty_retur default 0
               '{$penawaran_data['harga_satuan']}',   // ✅ harga_jual
               0,                                      // ✅ potongan default 0
               '{$penawaran_data['total_harga']}')";  // ✅ total
```

---

## 📋 PERUBAHAN DETAIL

### 1. **Tambah Kolom `nobaris`**

**Purpose:** Sequential numbering per service

**Implementation:**
```php
// Get max nobaris for this service
$q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) as max_nobaris
                                     FROM tblservis_barang
                                     WHERE no_service='$no_service_post'");
$nobaris_data = mysqli_fetch_array($q_nobaris);
$nobaris = $nobaris_data['max_nobaris'] + 1;
```

**Result:**
- Item pertama → nobaris = 1
- Item kedua → nobaris = 2
- Item ketiga → nobaris = 3
- dst...

### 2. **Tambah Kolom `qty_retur`**

**Default Value:** 0

**Purpose:** Track quantity yang diretur (untuk future feature)

### 3. **Rename Kolom:**

| Yang Digunakan | Seharusnya | Status |
|---------------|------------|--------|
| `harga` | `harga_jual` | ✅ Fixed |
| `discount` | `potongan` | ✅ Fixed |

### 4. **Remove Kolom yang Tidak Ada:**

| Kolom | Alasan Remove |
|-------|---------------|
| `satuan` | Tidak ada di struktur tabel |
| `keterangan` | Tidak ada di struktur tabel |

---

## 📁 FILE YANG DIPERBAIKI

### `servis-input-reguler.php`

#### A. Handler Approve - Item from Master (Line 808-853)

**Changes:**
- ✅ Added nobaris auto-increment logic
- ✅ Changed `satuan` → removed
- ✅ Changed `harga` → `harga_jual`
- ✅ Changed `discount` → `potongan`
- ✅ Changed `keterangan` → removed
- ✅ Added `qty_retur` = 0

#### B. Handler Approve - Custom Item (Line 854-893)

**Changes:**
- ✅ Added nobaris auto-increment logic
- ✅ Removed hardcoded satuan 'PCS'
- ✅ Changed `harga` → `harga_jual`
- ✅ Changed `discount` → `potongan`
- ✅ Changed `keterangan` → removed
- ✅ Added `qty_retur` = 0

---

## 🧪 TESTING

### Test Case 1: Approve Item from Master

**Steps:**
1. Tambah penawaran dengan item dari master (misal: OLI001)
2. Klik Approve
3. Check di database:
   ```sql
   SELECT * FROM tblservis_barang
   WHERE no_service='SV25000000212'
   ORDER BY nobaris DESC LIMIT 1;
   ```

**Expected Result:**
- ✅ Item inserted successfully
- ✅ `nobaris` = sequential number
- ✅ `harga_jual` = harga dari penawaran
- ✅ `potongan` = 0
- ✅ `qty_retur` = 0
- ✅ `total` = quantity × harga_jual

### Test Case 2: Approve Custom Item

**Steps:**
1. Tambah custom item via modal
2. Submit penawaran
3. Klik Approve
4. Check di database:
   ```sql
   SELECT * FROM tblservis_barang
   WHERE no_service='SV25000000212'
   AND no_item LIKE 'CUSTOM-%'
   ORDER BY nobaris DESC LIMIT 1;
   ```

**Expected Result:**
- ✅ Custom item inserted successfully
- ✅ `nobaris` = sequential number
- ✅ `no_item` = CUSTOM-XXXXX
- ✅ All columns correct

### Test Case 3: Multiple Items Sequential nobaris

**Steps:**
1. Approve 3 items untuk service yang sama
2. Check nobaris:
   ```sql
   SELECT no_item, nobaris FROM tblservis_barang
   WHERE no_service='SV25000000212'
   ORDER BY nobaris;
   ```

**Expected Result:**
```
no_item      | nobaris
-------------|--------
OLI001       | 1
FILTER01     | 2
CUSTOM-00001 | 3
```

---

## 🐛 TROUBLESHOOTING

### Problem: Error "Unknown column 'nobaris'"

**Check:**
```sql
DESCRIBE tblservis_barang;
```

**Solution:**
Pastikan kolom `nobaris` ada. Jika tidak ada, tambahkan:
```sql
ALTER TABLE tblservis_barang
ADD COLUMN nobaris INT(11) NOT NULL DEFAULT 0
AFTER no_service;
```

### Problem: Error "Duplicate entry"

**Check:**
- Apakah ada UNIQUE constraint di `no_item`?
- Apakah ada composite key di (`no_service`, `no_item`)?

**Solution:**
Sudah di-handle dengan check duplicate sebelum insert (line 798-803)

### Problem: nobaris tidak sequential

**Debug:**
```sql
SELECT no_service, nobaris, no_item
FROM tblservis_barang
WHERE no_service='SV25000000212'
ORDER BY nobaris;
```

**Check:**
- Apakah query MAX(nobaris) berjalan dengan benar?
- Apakah ada gap di nobaris (1, 2, 5, 6)?

---

## 📈 IMPACT ANALYSIS

### What Changed:
- ✅ Query INSERT fixed untuk match tabel structure
- ✅ Added nobaris auto-increment
- ✅ Removed columns yang tidak ada
- ✅ Renamed columns ke nama yang benar

### What Did NOT Change:
- ✅ Flow approve/reject tetap sama
- ✅ UI tidak berubah
- ✅ Tabel lain tidak terpengaruh
- ✅ Backward compatibility maintained

### Breaking Changes:
- ❌ NONE - Hanya fix bug, bukan breaking change

---

## 📝 NOTES

1. **nobaris vs id:**
   - `id` = Auto-increment global (unique across all services)
   - `nobaris` = Sequential per service (1, 2, 3... per no_service)

2. **Why nobaris needed?**
   - Untuk ordering items di print/invoice
   - Untuk referensi item di within service
   - Standard practice di legacy system

3. **qty_retur:**
   - Currently default 0
   - Reserved untuk future feature (return/retur functionality)

4. **Satuan information:**
   - Tidak disimpan di `tblservis_barang`
   - Bisa di-JOIN dari `tblitem` saat display
   - Kalau custom item, bisa ambil dari `tbmaster_barang_custom`

---

**Last Updated:** 1 Desember 2025
**Version:** 1.0
**Fixed by:** Claude AI Assistant
