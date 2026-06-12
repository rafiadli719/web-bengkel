# DOKUMENTASI SISTEM APPROVAL WORKORDER
**Version: 1.0**
**Tanggal: 1 Desember 2025**

---

## 📋 RINGKASAN PERUBAHAN

Sistem Workorder telah diubah agar **item barang dan jasa tidak langsung masuk** ke Tab Item Barang/Jasa, melainkan masuk ke **Tab Temuan & Penawaran** sebagai **penawaran pending** yang memerlukan approval customer terlebih dahulu.

### ✅ Perubahan Utama:

1. **Item workorder masuk ke pending** - Tidak langsung masuk ke SPK
2. **Approval system** - Admin/SA approve atau reject setiap item
3. **Jika approve** → Item masuk ke Tab Item Barang/Jasa
4. **Jika reject** → Item masuk ke catatan service dengan pilihan alasan:
   - Pelanggan tidak setuju
   - Stok barang di bengkel tidak ada
   - Stok barang di supplier tidak ada
   - Lainnya

---

## 🔄 ALUR PROSES BARU

### Alur Lama (Sebelum Update):
```
User Tambah Workorder
   ↓
Item langsung masuk ke Tab Item Barang/Jasa
   ↓
Selesai
```

### Alur Baru (Setelah Update):
```
User Tambah Workorder
   ↓
Item masuk ke tbservis_pending_items (status='pending')
   ↓
Tampil di Tab Temuan & Penawaran - Section "Penawaran dari Workorder"
   ↓
Admin/SA Review setiap item
   ↓
   ├─ APPROVE → Item masuk ke tblservis_barang/tblservis_jasa
   │             ↓
   │          Tampil di Tab Item Barang/Jasa
   │             ↓
   │          Status di tbservis_pending_items = 'disetujui'
   │
   └─ REJECT  → Pilih alasan penolakan
                 ↓
              Masuk ke catatan service (tblservice.keterangan)
                 ↓
              Status di tbservis_pending_items = 'ditolak'
```

---

## 📁 FILE YANG DIUBAH

### 1. `servis-input-reguler.php`

#### A. Handler `btnaddworkorder` (Line 440-544)

**SEBELUM:**
```php
// Auto-add jasa dan barang dari workorder detail
if ($detail['tipe'] == '1') {
    // Insert langsung ke tblservis_jasa
} else {
    // Insert langsung ke tblservis_barang
}
```

**SESUDAH:**
```php
// Auto-add jasa dan barang ke PENDING ITEMS
// Insert to tbservis_pending_items dengan status='pending'
```

**Perubahan:**
- ✅ Tidak langsung insert ke `tblservis_barang` dan `tblservis_jasa`
- ✅ Insert ke `tbservis_pending_items` dengan `status_approval='pending'`
- ✅ Simpan `wo_id` untuk tracking asal workorder
- ✅ Redirect ke tab temuan setelah tambah workorder
- ✅ Alert inform user bahwa items masuk ke pending

#### B. Handler `btnapprove_wo_item` (Line 568-650)

**Fungsi:** Approve pending item dari workorder

**Proses:**
1. Get data dari `tbservis_pending_items`
2. Check tipe (barang atau jasa)
3. Insert ke `tblservis_barang` (jika barang) atau `tblservis_jasa` (jika jasa)
4. Update status di `tbservis_pending_items` menjadi `'disetujui'`
5. Alert sukses dan redirect

#### C. Handler `btnreject_wo_item` (Line 652-731)

**Fungsi:** Reject pending item dari workorder

**Proses:**
1. Get data dari `tbservis_pending_items`
2. Update status menjadi `'ditolak'`
3. Simpan `alasan_reject` dan `keterangan_reject`
4. Get current `keterangan` dari `tblservice`
5. Append catatan penolakan:
   ```
   [ITEM DITOLAK] Part: Nama Item (Kode)
   Alasan: {alasan}
   Keterangan: {keterangan}
   Tanggal: DD/MM/YYYY HH:mm
   ```
6. Update `tblservice.keterangan`
7. Alert sukses dan redirect

---

### 2. `_template/tab-temuan-penawaran-content-improved.php`

#### A. Section Baru: Penawaran dari Workorder (Line 441-556)

**Lokasi:** Di atas statistics summary

**Tampilan:**
- Table dengan header warning (kuning)
- List semua pending items dengan status='pending'
- Columns: #, Workorder, Kode Item, Nama Item, Tipe, Qty, Harga, Aksi
- Tombol Approve (hijau) dan Reject (merah) untuk setiap item
- Total nilai di footer

**Query:**
```sql
SELECT pi.*, woh.nama_wo, woh.kode_wo
FROM tbservis_pending_items pi
LEFT JOIN tbservis_workorder swo ON pi.wo_id = swo.id
LEFT JOIN tbworkorderheader woh ON swo.kode_wo = woh.kode_wo
WHERE pi.no_service='$no_service'
AND pi.status_approval='pending'
ORDER BY pi.created_at DESC
```

#### B. Update Statistics Card (Line 573-594)

**Tambahan Card:**
```html
<div class="stat-card" style="border-left: 3px solid #f39c12;">
    <div class="stat-number stat-pending">{count}</div>
    <div class="stat-label">Item WO Pending</div>
</div>
```

#### C. JavaScript Functions (Line 975-1023)

##### `approveWorkorderItem(itemId, namaItem)`
**Fungsi:** Submit approve request via POST

**Parameter:**
- `itemId`: ID dari tbservis_pending_items
- `namaItem`: Nama item untuk konfirmasi

**Flow:**
1. Konfirmasi user
2. Create form with POST data:
   - `pending_item_id`
   - `txtnosrv`
   - `btnapprove_wo_item`
3. Submit form

##### `rejectWorkorderItem(itemId, namaItem)`
**Fungsi:** Submit reject request via POST dengan pilihan alasan

**Parameter:**
- `itemId`: ID dari tbservis_pending_items
- `namaItem`: Nama item untuk konfirmasi

**Flow:**
1. Prompt pilih alasan (1-4)
2. Prompt keterangan tambahan (optional)
3. Konfirmasi dengan preview alasan
4. Create form with POST data:
   - `pending_item_id`
   - `txtnosrv`
   - `alasan_reject`
   - `keterangan_reject`
   - `btnreject_wo_item`
5. Submit form

---

## 🗂️ DATABASE TABLES

### Tabel yang Digunakan

#### 1. `tbservis_pending_items`

**Struktur:**
```sql
CREATE TABLE `tbservis_pending_items` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `no_service` VARCHAR(50) NOT NULL,
  `wo_id` INT NULL COMMENT 'Link ke tbservis_workorder',
  `kode_item` VARCHAR(50) NOT NULL,
  `nama_item` VARCHAR(255) NOT NULL,
  `tipe` ENUM('barang','jasa') NOT NULL,
  `quantity` INT DEFAULT 1,
  `harga_satuan` DECIMAL(15,2) NOT NULL,
  `total` DECIMAL(15,2) NOT NULL,
  `waktu` INT DEFAULT 0 COMMENT 'Waktu untuk jasa (menit)',
  `status_approval` ENUM('pending','disetujui','ditolak') DEFAULT 'pending',
  `alasan_reject` VARCHAR(100) NULL,
  `keterangan_reject` TEXT NULL,
  `keterangan` TEXT NULL COMMENT 'Info tambahan (misal: dari workorder)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Index:**
- `idx_no_service` ON `no_service`
- `idx_wo_id` ON `wo_id`
- `idx_status_approval` ON `status_approval`

**Foreign Keys:**
- `wo_id` → `tbservis_workorder.id`
- `no_service` → `tblservice.no_service`

#### 2. `tbservis_workorder`

**Fields terkait:**
- `id` - Primary key, digunakan di `tbservis_pending_items.wo_id`
- `no_service` - Nomor service
- `kode_wo` - Link ke `tbworkorderheader.kode_wo`

#### 3. `tblservice`

**Fields terkait:**
- `no_service` - Primary key
- `keterangan` - Catatan service (untuk item yang di-reject)

---

## 🚀 CARA MENGGUNAKAN

### Scenario 1: Tambah Workorder ke SPK

1. Buka halaman **Servis Input Reguler**
2. Klik tab **"Workorder"**
3. Pilih keluhan customer
4. Pilih workorder dari dropdown
5. Klik **"Tambah Work Order"**
6. **Alert muncul:**
   ```
   Work Order berhasil ditambahkan!

   5 item masuk ke Tab Temuan & Penawaran sebagai penawaran pending.
   Silakan approve/reject di tab tersebut.
   ```
7. Otomatis redirect ke **Tab "Temuan & Penawaran"**

### Scenario 2: Approve Item dari Workorder

1. Di tab **"Temuan & Penawaran"**
2. Lihat section **"Penawaran dari Workorder - Menunggu Persetujuan"**
3. Review list item yang pending
4. Klik tombol **"✓ Approve"** pada item yang disetujui
5. **Konfirmasi popup:**
   ```
   Setujui item ini?

   Oli Yamalube 800ml

   Item akan ditambahkan ke daftar Barang/Jasa.
   ```
6. Klik **OK**
7. **Alert sukses:**
   ```
   Item berhasil disetujui dan ditambahkan ke Item Barang!
   ```
8. Item hilang dari list pending
9. Item muncul di **Tab "Item Barang"** atau **"Item Jasa"**

### Scenario 3: Reject Item dari Workorder

1. Di tab **"Temuan & Penawaran"**
2. Klik tombol **"✗ Reject"** pada item yang ditolak
3. **Prompt pilih alasan:**
   ```
   Alasan penolakan:

   1. Pelanggan tidak setuju
   2. Stok barang di bengkel tidak ada
   3. Stok barang di supplier tidak ada
   4. Lainnya

   Ketik nomor (1-4):
   ```
4. Input nomor, misal: **1**
5. **Prompt keterangan tambahan:**
   ```
   Keterangan tambahan (optional):
   ```
6. Input keterangan, misal: **"Customer bilang sudah punya di rumah"**
7. **Konfirmasi:**
   ```
   Tolak item ini?

   Oli Yamalube 800ml

   Alasan: Pelanggan tidak setuju

   Item akan dicatat di catatan service.
   ```
8. Klik **OK**
9. **Alert sukses:**
   ```
   Item ditolak dan dicatat di catatan service!

   Alasan: Pelanggan tidak setuju
   ```
10. Item hilang dari list pending
11. Catatan ditambahkan ke **Tab "Detail Service"** → Field Keterangan:
    ```
    [ITEM DITOLAK] Part: Oli Yamalube 800ml (OLI001)
    Alasan: Pelanggan tidak setuju
    Keterangan: Customer bilang sudah punya di rumah
    Tanggal: 01/12/2025 14:30
    ```

---

## 📊 MONITORING & STATISTICS

### Dashboard Statistics

Di atas Tab Temuan & Penawaran, ditampilkan 5 card statistics:

1. **Item WO Pending** (Orange) - Total item dari workorder yang pending
2. **Total Temuan** (Biru) - Total temuan yang ditemukan
3. **Penawaran Part** (Kuning) - Total penawaran part yang pending
4. **Disetujui** (Hijau) - Total penawaran yang sudah disetujui
5. **Ditolak** (Merah) - Total penawaran yang ditolak

### Query untuk Monitoring

**Total pending items per service:**
```sql
SELECT COUNT(*) as total
FROM tbservis_pending_items
WHERE no_service='SV25000000001'
AND status_approval='pending';
```

**Total approved items:**
```sql
SELECT COUNT(*) as total
FROM tbservis_pending_items
WHERE no_service='SV25000000001'
AND status_approval='disetujui';
```

**Total rejected items:**
```sql
SELECT COUNT(*) as total
FROM tbservis_pending_items
WHERE no_service='SV25000000001'
AND status_approval='ditolak';
```

**Items rejected dengan alasan tertentu:**
```sql
SELECT * FROM tbservis_pending_items
WHERE no_service='SV25000000001'
AND status_approval='ditolak'
AND alasan_reject='customer_tidak_setuju';
```

---

## 🐛 TROUBLESHOOTING

### Problem: Pending items tidak muncul di tab

**Solusi:**
1. Check query di `tab-temuan-penawaran-content-improved.php` line 444-453
2. Pastikan `tbservis_pending_items` memiliki data dengan `status_approval='pending'`
3. Check join ke `tbservis_workorder` dan `tbworkorderheader`

### Problem: Approve button tidak berfungsi

**Solusi:**
1. Check JavaScript function `approveWorkorderItem()` ada di page
2. Check handler `btnapprove_wo_item` di `servis-input-reguler.php`
3. Check POST data di browser network tab

### Problem: Item tidak masuk ke barang/jasa setelah approve

**Solusi:**
1. Check apakah item sudah ada di `tblservis_barang` atau `tblservis_jasa`
2. Check duplicate prevention di handler (line 585-597)
3. Check error di browser console dan server error log

### Problem: Catatan reject tidak muncul

**Solusi:**
1. Check field `keterangan` di `tblservice`
2. Check handler reject (line 688-710)
3. Pastikan query update sukses

---

## 📝 BEST PRACTICES

### Untuk Admin/SA:

1. **Review semua pending items** sebelum lanjut ke tab lain
2. **Komunikasi dengan customer** sebelum reject item
3. **Berikan keterangan jelas** saat reject untuk tracking
4. **Approve sekaligus** jika customer sudah confirm semua item

### Untuk Developer:

1. **Maintain backward compatibility** - Old services tetap bisa di-proses
2. **Handle edge cases** - Item duplicate, workorder tidak ada, dll
3. **Log important actions** - Approve/reject sebaiknya di-log
4. **Performance optimization** - Gunakan index yang tepat
5. **Test approval flow** - Test approve dan reject scenarios

---

## 🔐 SECURITY NOTES

1. **Validasi input** - Semua input di-escape dengan `mysqli_real_escape_string()`
2. **Check ownership** - Pastikan `no_service` match dengan pending item
3. **Status validation** - Hanya pending items yang bisa di-approve/reject
4. **Prevent duplicate** - Check existing items di barang/jasa table

---

## 📈 FUTURE ENHANCEMENTS

### Possible Improvements:

1. **Batch Approve/Reject** - Select multiple items dan approve/reject sekaligus
2. **Approval History Log** - Table khusus untuk log approve/reject actions
3. **Customer Notification** - Email/WhatsApp notify customer saat reject
4. **Analytics Dashboard** - Report reject reasons untuk business insight
5. **Partial Approval** - Adjust quantity sebelum approve
6. **Auto-approval Rules** - Rules untuk auto-approve items tertentu
7. **Approval Levels** - Multi-level approval (SA → Manager → Owner)

---

**Last Updated:** 1 Desember 2025
**Version:** 1.0
**Developed by:** Claude AI Assistant
