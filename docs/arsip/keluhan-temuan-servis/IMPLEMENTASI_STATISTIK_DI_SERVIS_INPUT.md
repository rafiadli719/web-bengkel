# IMPLEMENTASI STATISTIK PELANGGAN DI HALAMAN SERVIS INPUT

## 📋 Ringkasan

Dokumen ini menjelaskan cara mengintegrasikan sistem statistik pelanggan ke dalam halaman servis input yang sudah ada.

**File yang Dimodifikasi:**
1. ✅ `servis-input-reguler.php`
2. ✅ `servis-input-reguler-rst.php`
3. ✅ `servis-input-reguler-jemput.php`
4. ✅ `servis-input-reguler-jemput-rst.php`

**File Helper yang Dibuat:**
- ✅ `_include_statistik_pelanggan.php` - Helper functions
- ✅ `config_whatsapp.php` - Konfigurasi WhatsApp API

---

## 🎯 Fitur yang Ditambahkan

### 1. **Tampilan Status Member Pelanggan**
- Badge status member (Bronze/Silver/Gold/Platinum)
- Total transaksi dan nominal
- Progress ke level berikutnya
- Benefit member

### 2. **Notifikasi WhatsApp Otomatis** (Opsional)
- Ucapan terima kasih setelah pembayaran
- Info garansi service
- Reminder service berikutnya
- Benefit member

### 3. **Link ke Dashboard Statistik**
- Tombol untuk melihat statistik lengkap
- Akses cepat ke data pelanggan

---

## 📝 Langkah Implementasi

### STEP 1: Include Helper File

Tambahkan di bagian atas file (setelah `session_start()` dan `include koneksi`):

```php
<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// TAMBAHKAN INI:
include "_include_statistik_pelanggan.php";

// ... kode lainnya ...
?>
```

**Lokasi di file:**
- `servis-input-reguler.php` → Baris ~20-25
- `servis-input-reguler-rst.php` → Baris ~20-25
- `servis-input-reguler-jemput.php` → Baris ~20-25
- `servis-input-reguler-jemput-rst.php` → Baris ~20-25

---

### STEP 2: Tampilkan Status Member di UI

Tambahkan setelah informasi pelanggan ditampilkan:

```php
<?php
// Setelah query get data pelanggan
$cari_kd = mysqli_query($koneksi, "SELECT namapelanggan, alamat, telephone 
                                    FROM tblpelanggan 
                                    WHERE nopelanggan='$kode_pelanggan'");
$tm_cari = mysqli_fetch_array($cari_kd);
$nama_pelanggan = $tm_cari['namapelanggan'];

// TAMBAHKAN INI:
// Tampilkan status member pelanggan
if(!empty($kode_pelanggan)) {
    echo displayStatistikPelangganInfo($koneksi, $kode_pelanggan);
}
?>
```

**Lokasi di file:**
Cari bagian yang menampilkan informasi pelanggan (biasanya di dalam form atau panel informasi service), lalu tambahkan kode di atas setelah nama pelanggan ditampilkan.

**Contoh Lokasi:**
```php
<!-- Existing code -->
<div class="form-group">
    <label>Nama Pelanggan:</label>
    <input type="text" value="<?php echo $nama_pelanggan; ?>" readonly>
</div>

<!-- TAMBAHKAN DI SINI -->
<?php
if(!empty($kode_pelanggan)) {
    echo displayStatistikPelangganInfo($koneksi, $kode_pelanggan);
}
?>

<!-- Continue with other fields -->
```

---

### STEP 3: Tambahkan Notifikasi WhatsApp Setelah Pembayaran

Modifikasi bagian proses pembayaran (`btnbayar`):

```php
<?php
// Proses pembayaran existing
if(isset($_POST['btnbayar'])) {
    $no_service = $_POST['txtnosrv'];
    $bayar = $_POST['txtbayar'];
    $kembali = $_POST['txtkembali'];
    // ... kode existing ...
    
    // Update status servis menjadi bayar
    $query = "UPDATE tblservice 
              SET status_servis = 'bayar',
                  bayar = '$bayar',
                  kembali = '$kembali',
                  total_akhir = '$total_akhir'
              WHERE no_service = '$no_service'";
    
    mysqli_query($koneksi, $query);
    
    // TAMBAHKAN INI:
    // Trigger otomatis jalan di sini!
    // Kirim WhatsApp (opsional)
    include "config_whatsapp.php";
    
    if(WA_AUTO_SEND_AFTER_PAYMENT) {
        // Auto send via API
        $wa_result = sendWhatsAppAfterPayment($koneksi, $no_service, true);
        
        if($wa_result['success']) {
            // Log activity
            logWhatsAppActivity($no_service, $wa_result['phone'], 'sent', 'Auto-send after payment');
        }
    }
    
    // Redirect dengan parameter untuk tampilkan tombol WhatsApp
    echo "<script>window.location='servis-input-reguler.php?snoserv=$no_service&tab=payment&wa=1';</script>";
}
?>
```

**Lokasi di file:**
Cari bagian `if(isset($_POST['btnbayar']))` atau proses pembayaran, biasanya di baris ~850-950.

---

### STEP 4: Tampilkan Tombol WhatsApp Setelah Pembayaran

Tambahkan di bagian tab payment atau setelah pembayaran berhasil:

```php
<?php
// Cek jika baru selesai bayar
if(isset($_GET['wa']) && $_GET['wa'] == '1' && $status_servis == 'bayar') {
?>
<div class="alert alert-success">
    <h4><i class="fa fa-check-circle"></i> Pembayaran Berhasil!</h4>
    <p>Transaksi telah selesai. Statistik pelanggan otomatis terupdate.</p>
    
    <div class="space-10"></div>
    
    <!-- Tombol WhatsApp -->
    <a href="statistik_pelanggan_send_wa.php?no_service=<?php echo $no_service; ?>" 
       target="_blank" 
       class="btn btn-success btn-lg">
        <i class="fa fa-whatsapp"></i> Kirim Ucapan Terima Kasih via WhatsApp
    </a>
    
    <a href="statistik_pelanggan_dashboard.php" 
       target="_blank" 
       class="btn btn-info btn-lg">
        <i class="fa fa-bar-chart"></i> Lihat Statistik Pelanggan
    </a>
</div>
<?php
}
?>
```

**Lokasi di file:**
Di dalam tab "Payment" atau setelah form pembayaran, biasanya di bagian bawah halaman.

---

### STEP 5: Tambahkan Link ke Dashboard Statistik di Menu

Tambahkan tombol akses cepat ke dashboard statistik:

```php
<!-- Di bagian header atau toolbar -->
<div class="widget-toolbar">
    <a href="statistik_pelanggan_dashboard.php" target="_blank" class="btn btn-xs btn-info">
        <i class="fa fa-bar-chart"></i> Statistik Pelanggan
    </a>
</div>
```

---

## 🎨 Tampilan Visual

### Sebelum Implementasi:
```
┌─────────────────────────────────────────┐
│ Input Service Reguler                   │
├─────────────────────────────────────────┤
│ Nama Pelanggan: Budi Santoso            │
│ No. Polisi: AD 1234 AB                  │
│ Alamat: Jl. Merdeka No. 123             │
│                                         │
│ [Form input service...]                 │
└─────────────────────────────────────────┘
```

### Setelah Implementasi:
```
┌─────────────────────────────────────────┐
│ Input Service Reguler                   │
├─────────────────────────────────────────┤
│ Nama Pelanggan: Budi Santoso            │
│ No. Polisi: AD 1234 AB                  │
│ Alamat: Jl. Merdeka No. 123             │
│                                         │
│ ┌───────────────────────────────────┐   │
│ │ 🏆 Status Member: 🥇 Gold         │   │
│ │                                   │   │
│ │ Total Transaksi: 15x              │   │
│ │ Total Nominal: Rp 7.500.000       │   │
│ │ Rata-rata: Rp 500.000             │   │
│ │                                   │   │
│ │ 🎁 Benefit Member:                │   │
│ │ • Diskon 15% untuk service        │   │
│ │ • Prioritas antrian               │   │
│ │ • Gratis cuci motor               │   │
│ │                                   │   │
│ │ Progress ke Platinum:             │   │
│ │ ████████░░ 75%                    │   │
│ │ Kurang Rp 2.500.000 lagi!         │   │
│ └───────────────────────────────────┘   │
│                                         │
│ [Form input service...]                 │
└─────────────────────────────────────────┘
```

### Setelah Pembayaran:
```
┌─────────────────────────────────────────┐
│ ✅ Pembayaran Berhasil!                 │
│                                         │
│ Transaksi telah selesai.                │
│ Statistik pelanggan otomatis terupdate. │
│                                         │
│ [📱 Kirim Ucapan Terima Kasih]          │
│ [📊 Lihat Statistik Pelanggan]          │
└─────────────────────────────────────────┘
```

---

## 📱 Setup WhatsApp API (Opsional)

### Jika Ingin Auto-Send WhatsApp:

1. **Daftar di WhatsApp Gateway**
   - Fonnte: https://fonnte.com (Recommended)
   - Wablas: https://wablas.com
   - WooWA: https://woowa.id

2. **Edit `config_whatsapp.php`**
   ```php
   define('WA_API_ENABLED', true); // Aktifkan
   define('WA_API_KEY', 'abc123xyz'); // API key Anda
   define('WA_AUTO_SEND_AFTER_PAYMENT', true); // Auto-send
   ```

3. **Test Kirim Pesan**
   ```php
   // Test di browser
   http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/test_whatsapp.php
   ```

### Jika Tidak Pakai API (Mode Manual):

Biarkan konfigurasi default:
```php
define('WA_API_ENABLED', false);
define('WA_AUTO_SEND_AFTER_PAYMENT', false);
```

Sistem akan generate WhatsApp Web link yang bisa diklik manual.

---

## 🧪 Testing

### Test 1: Tampilan Status Member

1. Buka halaman servis input
2. Pilih pelanggan yang sudah pernah transaksi
3. Cek apakah muncul box status member
4. Verifikasi data sesuai dengan database

**Expected Result:**
- Box status member muncul
- Data total transaksi, nominal, status member benar
- Progress bar ke level berikutnya tampil

### Test 2: Update Statistik Setelah Bayar

1. Input servis baru
2. Isi semua data (jasa, barang, dll)
3. Klik tombol BAYAR
4. Cek database `statistik_pelanggan`

**Query Test:**
```sql
SELECT * FROM statistik_pelanggan 
WHERE no_pelanggan = 'AD 1234 AB';
```

**Expected Result:**
- Total transaksi bertambah 1
- Total nominal bertambah sesuai transaksi
- Status member update jika mencapai threshold
- Tanggal terakhir transaksi = hari ini

### Test 3: WhatsApp Notification

1. Setup API WhatsApp (atau gunakan mode manual)
2. Input servis dan bayar
3. Cek tombol WhatsApp muncul
4. Klik tombol WhatsApp

**Expected Result:**
- Tombol WhatsApp muncul setelah pembayaran
- Klik tombol → redirect ke WhatsApp Web
- Pesan sudah terisi otomatis
- Nomor tujuan benar

---

## 🔧 Troubleshooting

### Problem: Box status member tidak muncul

**Solusi:**
```php
// Cek apakah file helper sudah di-include
var_dump(function_exists('displayStatistikPelangganInfo'));
// Harusnya return: bool(true)

// Cek apakah ada data pelanggan
$data = getStatusMemberPelanggan($koneksi, 'AD 1234 AB');
print_r($data);
```

### Problem: Statistik tidak update setelah bayar

**Solusi:**
```sql
-- Cek trigger ada
SHOW TRIGGERS LIKE 'tblservice';

-- Cek status servis
SELECT no_service, status_servis, total_akhir 
FROM tblservice 
WHERE no_service = 'SV25000000001';

-- Manual refresh
CALL sp_refresh_statistik_pelanggan();
```

### Problem: WhatsApp tidak terkirim

**Solusi:**
```php
// Cek konfigurasi
include "config_whatsapp.php";
echo "API Enabled: " . (WA_API_ENABLED ? 'Yes' : 'No') . "<br>";
echo "API Key: " . WA_API_KEY . "<br>";
echo "Auto Send: " . (WA_AUTO_SEND_AFTER_PAYMENT ? 'Yes' : 'No') . "<br>";

// Test kirim manual
include "class_whatsapp_automation.php";
$wa = new WhatsAppAutomation($koneksi);
$result = $wa->sendTerimaKasih('SV25000000001');
print_r($result);
```

---

## 📚 File Reference

| File | Lokasi | Fungsi |
|------|--------|--------|
| `_include_statistik_pelanggan.php` | `_admincab/` | Helper functions |
| `config_whatsapp.php` | `_admincab/` | Konfigurasi WhatsApp |
| `class_whatsapp_automation.php` | `_admincab/` | Class WhatsApp |
| `statistik_pelanggan_dashboard.php` | `_admincab/` | Dashboard statistik |
| `statistik_pelanggan_send_wa.php` | `_admincab/` | Handler kirim WA |

---

## ✅ Checklist Implementasi

**Persiapan:**
- [ ] Import `database_statistik_pelanggan_otomatis.sql`
- [ ] Aktifkan event scheduler
- [ ] Jalankan `sp_refresh_statistik_pelanggan()`
- [ ] Verifikasi trigger berfungsi

**Implementasi di File Servis:**
- [ ] Include helper di `servis-input-reguler.php`
- [ ] Include helper di `servis-input-reguler-rst.php`
- [ ] Include helper di `servis-input-reguler-jemput.php`
- [ ] Include helper di `servis-input-reguler-jemput-rst.php`
- [ ] Tambahkan tampilan status member
- [ ] Tambahkan notifikasi WhatsApp
- [ ] Tambahkan link dashboard

**Setup WhatsApp (Opsional):**
- [ ] Daftar di WhatsApp Gateway
- [ ] Edit `config_whatsapp.php`
- [ ] Test kirim pesan
- [ ] Aktifkan auto-send

**Testing:**
- [ ] Test tampilan status member
- [ ] Test update statistik setelah bayar
- [ ] Test WhatsApp notification
- [ ] Test dashboard statistik

---

## 🎯 Kesimpulan

Implementasi ini **TIDAK MENGUBAH** workflow kasir yang sudah ada. Kasir tetap input servis seperti biasa, sistem otomatis:

1. ✅ Update statistik pelanggan di background
2. ✅ Tampilkan status member untuk info kasir
3. ✅ Kirim WhatsApp otomatis (jika diaktifkan)
4. ✅ Provide link ke dashboard statistik

**Benefit:**
- Kasir lebih aware tentang status pelanggan
- Pelanggan dapat apresiasi langsung
- Manajemen dapat monitor statistik real-time
- Tidak ada pekerjaan tambahan untuk kasir

---

**Dibuat:** 2 November 2025  
**Versi:** 1.0  
**Developer:** Fit Motor Development Team
