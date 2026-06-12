# DOKUMENTASI INTEGRASI TOMBOL INPUT BARANG CUSTOM
**Tanggal:** 1 Desember 2025
**Lokasi:** Tab Temuan & Penawaran - Form Penawaran Part

---

## 📋 RINGKASAN PERUBAHAN

Telah ditambahkan tombol **"Input Barang Custom"** di bagian Penawaran Part yang memungkinkan user untuk menambahkan barang yang tidak ada di master database secara cepat.

### ✅ Perubahan yang Dilakukan:

1. **Tombol baru di form Penawaran Part**
   - Posisi: Di sebelah tombol "Fast Moves Part"
   - Warna: Hijau dengan gradient
   - Icon: Cube (fa-cube)
   - Aksi: Membuka modal `modalInputCustom`

2. **Modal Input Barang Custom**
   - File: `_template/modal-input-barang-custom.php`
   - Include otomatis di `tab-temuan-penawaran-content-improved.php`

3. **Auto-fill Form Penawaran**
   - Setelah barang custom disimpan, form penawaran terisi otomatis
   - User tinggal klik submit untuk menambahkan ke penawaran

---

## 🎨 TAMPILAN TOMBOL

```html
<button type="button" class="btn btn-modern btn-custom-item btn-lg"
        onclick="$('#modalInputCustom').modal('show');">
    <i class="ace-icon fa fa-cube"></i>
    <strong>Input Barang Custom</strong>
</button>
```

**CSS Styling:**
- Background: Gradient hijau (#5cb85c → #449d44)
- Hover effect: Darker green dengan transform translateY
- Border radius: 8px
- Box shadow untuk depth effect

---

## 🔄 ALUR PENGGUNAAN

### Scenario: Tambah Part Custom yang Tidak Ada di Master

1. User buka halaman **Servis Input Reguler**
2. Klik tab **"Temuan & Penawaran"**
3. Scroll ke **"Form Penawaran Part"**
4. Klik tombol **"Input Barang Custom"**
5. **Modal muncul** dengan form:
   - Nama Barang (required)
   - Harga Jual (required)
   - Satuan (required) - dropdown: PCS, UNIT, SET, PAKET, BTL, LITER
   - Kategori (optional) - dropdown: LAINNYA, JASA, IMPORT, MODIFIKASI, AKSESORIS
   - Deskripsi (optional)

6. User isi form dan klik **"Simpan & Gunakan"**
7. **AJAX request** ke `_handler_barang_custom.php`
8. Handler:
   - Auto-generate kode barang: `CUSTOM-XXXXX` (5 digit)
   - Insert ke table `tbmaster_barang_custom`
   - Return JSON response dengan data barang

9. **Response berhasil:**
   - Modal tutup otomatis
   - Form Penawaran Part terisi otomatis:
     - Kode Part = CUSTOM-XXXXX
     - Nama Part = [Nama yang diinput]
     - Harga Satuan = [Harga yang diinput]
     - Quantity = 1
   - Visual feedback: Input fields highlight kuning (animation)
   - Alert: "Barang custom berhasil ditambahkan!"

10. User tinggal submit form penawaran

---

## 📁 FILE YANG DIUBAH

### 1. `_template/tab-temuan-penawaran-content-improved.php`

#### A. Tombol Input Barang Custom (Line 369-374)

**Ditambahkan:**
```html
<button type="button" class="btn btn-modern btn-custom-item btn-lg"
        onclick="$('#modalInputCustom').modal('show');"
        style="margin-left: 10px;">
    <i class="ace-icon fa fa-cube"></i>
    <strong>Input Barang Custom</strong>
</button>
```

**Lokasi:** Di sebelah tombol Fast Moves Part

#### B. CSS Styling (Line 1050-1075)

**Ditambahkan:**
```css
.btn-custom-item {
    background: linear-gradient(135deg, #5cb85c 0%, #449d44 100%);
    border: none;
    color: white;
    padding: 12px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
```

#### C. Include Modal (Line 1078-1084)

**Ditambahkan:**
```php
<?php
$modal_custom_path = __DIR__ . "/modal-input-barang-custom.php";
if (file_exists($modal_custom_path)) {
    include "modal-input-barang-custom.php";
}
?>
```

### 2. `_template/modal-input-barang-custom.php`

#### Updated AJAX Success Handler (Line 114-145)

**Perubahan:**
- Menambahkan check `typeof hitungTotalPenawaran === 'function'` sebelum call
- Support multiple form IDs: `formTambahPenawaran` dan `formAddPenawaran`
- Menambahkan visual feedback dengan `animated-input` class
- Memanggil `hitungTotalPenawaran()` untuk auto-calculate total

**Before:**
```javascript
$('#total_penawaran_display').val('Rp ' + formatNumber(total));
$('#formTambahPenawaran').slideDown();
```

**After:**
```javascript
if (typeof hitungTotalPenawaran === 'function') {
    hitungTotalPenawaran();
}
$('#formTambahPenawaran').slideDown();
$('#formAddPenawaran').slideDown();

// Visual feedback
$('#kode_barang_penawaran, #nama_barang_penawaran').addClass('animated-input');
setTimeout(function() {
    $('#kode_barang_penawaran, #nama_barang_penawaran').removeClass('animated-input');
}, 1000);
```

---

## 🗂️ DATABASE

### Tabel yang Digunakan

#### `tbmaster_barang_custom`

**Struktur:**
```sql
CREATE TABLE `tbmaster_barang_custom` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `kode_barang` varchar(20) NOT NULL UNIQUE,
  `nama_barang` varchar(255) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `satuan` varchar(20) NOT NULL,
  `kategori` varchar(50) DEFAULT 'LAINNYA',
  `deskripsi` text DEFAULT NULL,
  `status_aktif` tinyint(1) DEFAULT 1,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Format Kode Barang:**
- Pattern: `CUSTOM-XXXXX`
- Contoh: `CUSTOM-00001`, `CUSTOM-00002`, etc.
- Auto-increment berdasarkan record terakhir

**Status Aktif:**
- `1` = Aktif (default)
- `0` = Nonaktif (masih tersimpan tapi tidak muncul di list)

---

## 🔌 HANDLER API

### Endpoint: `_handler_barang_custom.php`

#### Action: `quick_add_custom`

**Request (POST):**
```javascript
{
    action: 'quick_add_custom',
    nama_barang: 'Spare Part Import Khusus',
    harga_jual: 150000,
    satuan: 'PCS',
    kategori: 'IMPORT',
    deskripsi: 'Part khusus untuk motor custom'
}
```

**Response Success (JSON):**
```json
{
    "success": true,
    "data": {
        "kode_barang": "CUSTOM-00001",
        "nama_barang": "Spare Part Import Khusus",
        "harga_jual": "150000",
        "satuan": "PCS",
        "kategori": "IMPORT"
    },
    "message": "Barang custom berhasil ditambahkan!"
}
```

**Response Error (JSON):**
```json
{
    "success": false,
    "message": "Nama barang, harga, dan satuan wajib diisi!"
}
```

---

## 📊 FORM FIELD MAPPING

| Modal Field ID | Form Penawaran Field ID | Description |
|---------------|------------------------|-------------|
| `custom_nama` | `kode_barang_penawaran` | Auto-filled dengan kode CUSTOM-XXXXX |
| `custom_nama` | `nama_barang_penawaran` | Nama barang yang diinput |
| `custom_harga` | `harga_satuan_penawaran` | Harga jual |
| - | `quantity_penawaran` | Default value: 1 |

**Auto-calculation:**
- Total = `harga_satuan_penawaran` × `quantity_penawaran`
- Dilakukan oleh function `hitungTotalPenawaran()`

---

## 🐛 TROUBLESHOOTING

### Problem: Modal tidak muncul saat klik tombol

**Solusi:**
1. Check apakah modal di-include dengan benar
2. Check console browser untuk JavaScript error
3. Pastikan jQuery dan Bootstrap modal library loaded

**Check:**
```javascript
console.log($('#modalInputCustom').length); // Should return 1
```

### Problem: Form tidak terisi setelah simpan

**Solusi:**
1. Check response dari AJAX di Network tab browser
2. Pastikan field IDs sesuai:
   - `kode_barang_penawaran`
   - `nama_barang_penawaran`
   - `harga_satuan_penawaran`
   - `quantity_penawaran`
3. Check console untuk JavaScript error

### Problem: Insert gagal ke database

**Solusi:**
1. Check apakah table `tbmaster_barang_custom` ada
2. Check permission koneksi database
3. Check log error di `_handler_barang_custom.php`

**Test Query:**
```sql
SELECT * FROM tbmaster_barang_custom ORDER BY id DESC LIMIT 5;
```

### Problem: Kode barang duplicate

**Solusi:**
Handler sudah handle auto-increment dengan query:
```php
SELECT kode_barang FROM tbmaster_barang_custom ORDER BY id DESC LIMIT 1
```

Jika masih duplicate, check:
- Apakah ada multiple request bersamaan
- Consider add mutex/lock saat generate kode

---

## 🎯 KEUNGGULAN FITUR

1. **Quick Input** - Tidak perlu ke menu master barang custom terlebih dahulu
2. **Auto-fill** - Langsung mengisi form penawaran setelah simpan
3. **Kode Auto-generate** - Tidak perlu input kode manual
4. **Reusable** - Barang custom tersimpan di master dan bisa digunakan lagi
5. **Visual Feedback** - Animasi highlight saat form terisi
6. **Validation** - Form validation untuk field required
7. **Error Handling** - Menampilkan error message yang jelas

---

## 📝 BEST PRACTICES

### Untuk User:

1. **Isi nama barang dengan jelas** - Akan tersimpan di master
2. **Tentukan kategori yang tepat** - Untuk memudahkan filter di kemudian hari
3. **Tambahkan deskripsi** - Untuk referensi/dokumentasi

### Untuk Developer:

1. **Maintain unique kode_barang** - Gunakan transaction jika perlu
2. **Validate input** - Server-side dan client-side
3. **Log custom items** - Untuk tracking siapa yang input apa
4. **Regular cleanup** - Archive atau delete custom items yang tidak pernah digunakan
5. **Add search feature** - Untuk cari existing custom items sebelum buat baru

---

## 🔐 SECURITY NOTES

1. **Input Sanitization** - Semua input di-escape dengan `mysqli_real_escape_string()`
2. **Session Check** - Handler check session untuk `created_by`
3. **Status Aktif** - Soft delete menggunakan status_aktif flag
4. **Validation** - Required fields validated di client dan server
5. **JSON Response** - Gunakan `header('Content-Type: application/json')` untuk AJAX

---

## 📈 FUTURE ENHANCEMENTS

### Possible Improvements:

1. **Foto Product** - Upload foto untuk custom items
2. **Supplier Info** - Link ke supplier untuk part import
3. **Stock Tracking** - Tambah field stok untuk custom items
4. **Price History** - Log perubahan harga
5. **Usage Analytics** - Report custom items yang paling sering dipakai
6. **Batch Import** - Import multiple custom items via Excel/CSV
7. **Approval System** - Require approval untuk harga di atas threshold tertentu

---

**Last Updated:** 1 Desember 2025
**Version:** 1.0
**Developed by:** Claude AI Assistant
