# PROSES PENGIRIMAN WHATSAPP SETELAH SERVICE

## 📋 OVERVIEW

Sistem ini akan otomatis mengirimkan pesan WhatsApp ucapan terima kasih kepada pelanggan setelah transaksi service selesai dan pembayaran dilakukan.

---

## 🔄 ALUR PROSES LENGKAP

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLOW PENGIRIMAN WHATSAPP                     │
└─────────────────────────────────────────────────────────────────┘

1. PELANGGAN DATANG SERVICE
   ↓
2. MEKANIK KERJAKAN SERVICE
   ↓
3. SERVICE SELESAI
   ↓
4. KASIR INPUT PEMBAYARAN
   ↓
5. PEMBAYARAN BERHASIL
   ↓
6. TRIGGER KIRIM WHATSAPP ← OTOMATIS
   ↓
7. SISTEM AMBIL DATA SERVICE
   ↓
8. SISTEM AMBIL DATA PELANGGAN
   ↓
9. SISTEM AMBIL STATUS MEMBER
   ↓
10. SISTEM GENERATE PESAN
   ↓
11. SISTEM KIRIM VIA API
   ↓
12. WHATSAPP TERKIRIM KE PELANGGAN ✅
```

---

## 📊 DETAIL PROSES STEP BY STEP

### **STEP 1: PELANGGAN DATANG SERVICE**

**Lokasi:** Halaman Service / Kasir

**Yang Dilakukan:**
1. Kasir buka halaman service
2. Input data pelanggan (nama, nomor telepon, motor)
3. Input keluhan motor
4. Mekanik mulai service

**Data yang Dicatat:**
- `no_service` (Nomor service)
- `no_pelanggan` (Nomor pelanggan)
- `tanggal` (Tanggal service)
- `keluhan` (Keluhan motor)

---

### **STEP 2-3: SERVICE DIKERJAKAN & SELESAI**

**Lokasi:** Workshop

**Yang Dilakukan:**
1. Mekanik kerjakan service
2. Input spare part yang digunakan
3. Input jasa service
4. Hitung total biaya
5. Service selesai

**Data yang Dicatat:**
- `total_sparepart` (Total harga spare part)
- `total_jasa` (Total jasa service)
- `total_akhir` (Total yang harus dibayar)

---

### **STEP 4-5: PEMBAYARAN**

**Lokasi:** Halaman Pembayaran Service

**File:** `servis_byr.php` atau `servis_byr_proses.php`

**Yang Dilakukan:**
1. Kasir buka halaman pembayaran
2. Input jumlah bayar
3. Hitung kembalian
4. Klik tombol "Bayar"
5. Data pembayaran disimpan ke database

**Data yang Disimpan:**
```sql
UPDATE tblservice SET
    status = 'Lunas',
    tgl_bayar = NOW(),
    jumlah_bayar = [jumlah_bayar],
    kembalian = [kembalian]
WHERE no_service = '[no_service]'
```

---

### **STEP 6: TRIGGER KIRIM WHATSAPP** ⚡

**Lokasi:** Setelah pembayaran berhasil

**Ada 2 Cara:**

#### **CARA 1: OTOMATIS (Recommended)**

**File:** `servis_byr_proses.php`

```php
<?php
// Setelah pembayaran berhasil disimpan
if($pembayaran_sukses) {
    
    // Load config & class
    require_once 'config_whatsapp.php';
    require_once 'class_whatsapp_automation.php';
    
    // Cek apakah WA API enabled
    if(WA_API_ENABLED && isWhatsAppConfigured()) {
        
        // Inisialisasi class
        $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
        
        // Kirim terima kasih
        $result = $wa->sendTerimaKasih($no_service);
        
        // Log hasil
        if($result['success']) {
            logWhatsAppActivity($no_service, $result['phone'], 'sent', 'Auto-sent after payment');
        } else {
            logWhatsAppActivity($no_service, '', 'failed', $result['message']);
        }
    }
    
    // Redirect ke struk
    echo "<script>window.location='servis_struk.php?_id=$no_service';</script>";
}
?>
```

#### **CARA 2: MANUAL (Tombol)**

**File:** `servis_struk.php`

```php
<!-- Tombol Kirim WhatsApp di Struk -->
<button onclick="kirimWhatsApp('<?php echo $no_service; ?>')">
    <i class="fa fa-whatsapp"></i> Kirim WhatsApp
</button>

<script>
function kirimWhatsApp(no_service) {
    $.ajax({
        url: 'ajax_send_whatsapp.php',
        type: 'POST',
        data: { no_service: no_service },
        success: function(response) {
            if(response.success) {
                alert('WhatsApp berhasil dikirim!');
            } else {
                alert('Gagal kirim WhatsApp: ' + response.message);
            }
        }
    });
}
</script>
```

---

### **STEP 7-9: SISTEM AMBIL DATA**

**Lokasi:** Class `WhatsAppAutomation` → Method `sendTerimaKasih()`

**File:** `class_whatsapp_automation.php`

**Query yang Dijalankan:**
```sql
SELECT 
    s.no_service,
    s.no_pelanggan,
    s.tanggal,
    s.total_akhir,
    p.namapelanggan,
    p.telephone,
    p.notlp,
    sp.status_member,
    sp.estimasi_datang_berikutnya
FROM tblservice s
INNER JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
LEFT JOIN statistik_pelanggan sp ON s.no_pelanggan = sp.no_pelanggan
WHERE s.no_service = 'SV25000000001'
```

**Data yang Didapat:**
- Nomor service: `SV25000000001`
- Nama pelanggan: `Budi Santoso`
- Nomor telepon: `08123456789`
- Total bayar: `Rp 500.000`
- Status member: `Gold`
- Estimasi service berikutnya: `03 Desember 2025`

---

### **STEP 10: SISTEM GENERATE PESAN**

**Lokasi:** Method `generatePesanTerimaKasih()`

**Proses:**
1. Ambil data dari database
2. Format tanggal (d/m/Y)
3. Format rupiah (number_format)
4. Tentukan benefit berdasarkan status member
5. Generate pesan dengan template

**Template Pesan:**
```
🏍️ *Terima Kasih - Fit Motor* 🏍️

Halo *Budi Santoso*,

Terima kasih telah mempercayakan service motor Anda kepada kami!

📋 *Detail Transaksi:*
• No. Service: SV25000000001
• Tanggal: 03/11/2025
• Total: Rp 500.000
• Status Member: *Gold*

✅ *Garansi Service:*
Service Anda bergaransi 30 hari atau 1000 KM (mana yang tercapai lebih dulu)

📅 *Reminder Service Berikutnya:*
Estimasi: 03/12/2025
Kami akan mengingatkan Anda saat waktunya service!

🎁 *Benefit Member Gold:*
• Diskon 15% untuk service
• Prioritas antrian
• Gratis cuci motor

Jika ada keluhan atau pertanyaan, jangan ragu untuk menghubungi kami!

Salam hangat,
*Tim Fit Motor* 🔧
```

**Code:**
```php
private function generatePesanTerimaKasih($data) {
    $nama = $data['namapelanggan'];
    $no_service = $data['no_service'];
    $tanggal = date('d/m/Y', strtotime($data['tanggal']));
    $total = number_format($data['total_akhir'], 0, ',', '.');
    $member = $data['status_member'] ?: 'Bronze';
    $estimasi = date('d/m/Y', strtotime($data['estimasi_datang_berikutnya']));
    
    $message = "🏍️ *Terima Kasih - Fit Motor* 🏍️\n\n";
    $message .= "Halo *{$nama}*,\n\n";
    $message .= "Terima kasih telah mempercayakan service motor Anda kepada kami!\n\n";
    $message .= "📋 *Detail Transaksi:*\n";
    $message .= "• No. Service: {$no_service}\n";
    $message .= "• Tanggal: {$tanggal}\n";
    $message .= "• Total: Rp {$total}\n";
    $message .= "• Status Member: *{$member}*\n\n";
    // ... dst
    
    return $message;
}
```

---

### **STEP 11: SISTEM KIRIM VIA API**

**Lokasi:** Method `sendViaAPI()`

**Proses:**
1. Clean nomor telepon (format 62xxx)
2. Load config WhatsApp
3. Tentukan provider (TechArea/Fonnte/Wablas)
4. Prepare request data
5. Kirim via cURL
6. Log hasil pengiriman

**Code untuk TechArea Gateway:**
```php
private function sendViaAPI($phone, $message) {
    // Clean phone number
    $phone = $this->cleanPhoneNumber($phone);
    // Output: 628123456789
    
    // Prepare data
    $postData = json_encode([
        'api_key' => 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy',
        'sender' => '62888xxxx',  // Nomor device Anda
        'number' => $phone,        // Nomor pelanggan
        'message' => $message      // Pesan yang sudah di-generate
    ]);
    
    // Send via cURL
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://wagw.techareadev.biz.id/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    ));
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Check result
    if($http_code == 200) {
        return [
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'phone' => $phone
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Gagal mengirim pesan',
            'phone' => $phone
        ];
    }
}
```

**Request ke API:**
```json
POST https://wagw.techareadev.biz.id/send-message
Content-Type: application/json

{
  "api_key": "nv5colO4cvgkAbVqtxWo5tBzSlIrMy",
  "sender": "62888xxxx",
  "number": "628123456789",
  "message": "🏍️ *Terima Kasih - Fit Motor* 🏍️\n\nHalo *Budi Santoso*,\n\n..."
}
```

**Response dari API:**
```json
{
  "status": "success",
  "message": "Message sent successfully",
  "data": {
    "message_id": "xxxxx",
    "sender": "62888xxxx",
    "recipient": "628123456789",
    "timestamp": "2025-11-03 14:00:00"
  }
}
```

---

### **STEP 12: WHATSAPP TERKIRIM** ✅

**Yang Terjadi:**
1. API Gateway terima request
2. API Gateway kirim ke device WhatsApp
3. Device WhatsApp kirim pesan ke pelanggan
4. Pelanggan terima pesan di HP

**Waktu Pengiriman:**
- Instant (< 5 detik) jika device online
- Delay jika device offline (akan terkirim saat online)

**Log Activity:**
```
[2025-11-03 14:00:00] Service: SV25000000001 | Phone: 628123456789 | Status: success | Message: Sent
```

---

## 🔧 IMPLEMENTASI DI SISTEM

### **OPTION 1: OTOMATIS SETELAH PEMBAYARAN**

**File yang Perlu Diedit:** `servis_byr_proses.php`

**Tambahkan Code:**
```php
<?php
// ... code pembayaran existing ...

// Setelah pembayaran berhasil
if(mysqli_query($koneksi, $sql_update_pembayaran)) {
    
    // ========== TAMBAHKAN CODE INI ==========
    // Load WhatsApp automation
    require_once 'config_whatsapp.php';
    require_once 'class_whatsapp_automation.php';
    
    // Cek apakah WA enabled
    if(WA_API_ENABLED && isWhatsAppConfigured()) {
        
        // Delay (opsional, untuk memastikan data tersimpan)
        sleep(WA_SEND_DELAY);
        
        // Inisialisasi class
        $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
        
        // Kirim terima kasih
        $result = $wa->sendTerimaKasih($no_service);
        
        // Log hasil
        if($result['success']) {
            logWhatsAppActivity($no_service, $result['phone'], 'sent', 'Auto-sent after payment');
        } else {
            logWhatsAppActivity($no_service, '', 'failed', $result['message']);
        }
    }
    // ========== END CODE ==========
    
    // Redirect ke struk
    echo "<script>window.location='servis_struk.php?_id=$no_service';</script>";
}
?>
```

---

### **OPTION 2: MANUAL DENGAN TOMBOL**

**File yang Perlu Dibuat:** `ajax_send_whatsapp.php`

```php
<?php
session_start();
require_once '../config/koneksi.php';
require_once 'config_whatsapp.php';
require_once 'class_whatsapp_automation.php';

header('Content-Type: application/json');

if(isset($_POST['no_service'])) {
    $no_service = $_POST['no_service'];
    
    // Inisialisasi class
    $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
    
    // Kirim terima kasih
    $result = $wa->sendTerimaKasih($no_service);
    
    // Return JSON
    echo json_encode($result);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No service tidak ditemukan'
    ]);
}
?>
```

**File yang Perlu Diedit:** `servis_struk.php`

**Tambahkan Tombol:**
```html
<!-- Di bagian bawah struk, sebelum tombol print -->
<div class="form-group">
    <button type="button" class="btn btn-success btn-lg" id="btnKirimWA">
        <i class="fa fa-whatsapp"></i> Kirim WhatsApp
    </button>
</div>

<script>
$('#btnKirimWA').click(function() {
    var no_service = '<?php echo $no_service; ?>';
    var btn = $(this);
    
    // Disable button
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');
    
    // Send AJAX
    $.ajax({
        url: 'ajax_send_whatsapp.php',
        type: 'POST',
        data: { no_service: no_service },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('✅ WhatsApp berhasil dikirim ke ' + response.phone);
                btn.html('<i class="fa fa-check"></i> Terkirim');
            } else {
                alert('❌ Gagal kirim WhatsApp: ' + response.message);
                btn.prop('disabled', false).html('<i class="fa fa-whatsapp"></i> Kirim WhatsApp');
            }
        },
        error: function() {
            alert('❌ Terjadi kesalahan sistem');
            btn.prop('disabled', false).html('<i class="fa fa-whatsapp"></i> Kirim WhatsApp');
        }
    });
});
</script>
```

---

## 📊 MONITORING & LOGGING

### **Log File**

**Lokasi:** `logs/whatsapp_log.txt`

**Format:**
```
[2025-11-03 14:00:00] Service: SV25000000001 | Phone: 628123456789 | Status: success | Message: Sent
[2025-11-03 14:05:30] Service: SV25000000002 | Phone: 628987654321 | Status: failed | Message: Invalid phone number
[2025-11-03 14:10:15] Service: SV25000000003 | Phone: 628111222333 | Status: success | Message: Sent
```

### **Dashboard Monitoring**

**Buat Halaman:** `whatsapp_monitor.php`

```php
<?php
// Baca log file
$log_file = 'logs/whatsapp_log.txt';
$logs = file($log_file);

// Hitung statistik
$total = count($logs);
$success = 0;
$failed = 0;

foreach($logs as $log) {
    if(strpos($log, 'Status: success') !== false) {
        $success++;
    } else {
        $failed++;
    }
}

$success_rate = ($total > 0) ? round(($success / $total) * 100, 2) : 0;
?>

<div class="row">
    <div class="col-md-4">
        <div class="widget-box">
            <div class="widget-header">
                <h4>Total Pesan</h4>
            </div>
            <div class="widget-body">
                <h2><?php echo $total; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="widget-box">
            <div class="widget-header">
                <h4>Berhasil</h4>
            </div>
            <div class="widget-body">
                <h2 class="text-success"><?php echo $success; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="widget-box">
            <div class="widget-header">
                <h4>Success Rate</h4>
            </div>
            <div class="widget-body">
                <h2 class="text-info"><?php echo $success_rate; ?>%</h2>
            </div>
        </div>
    </div>
</div>
```

---

## 🐛 TROUBLESHOOTING

### **Problem 1: Pesan Tidak Terkirim**

**Penyebab:**
- API Key salah
- Nomor sender salah
- Device WhatsApp offline
- Nomor pelanggan tidak valid

**Solusi:**
1. Cek `config_whatsapp.php`:
   ```php
   define('WA_API_KEY', 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy'); // Harus benar
   define('WA_SENDER_NUMBER', '62888xxxx'); // Harus benar
   ```

2. Cek device WhatsApp online

3. Cek format nomor pelanggan:
   ```php
   // Harus format: 628123456789
   // Bukan: 08123456789 atau +628123456789
   ```

4. Cek log:
   ```bash
   tail -f logs/whatsapp_log.txt
   ```

---

### **Problem 2: Pesan Terkirim Tapi Tidak Sampai**

**Penyebab:**
- Nomor pelanggan salah
- Pelanggan block nomor sender
- WhatsApp pelanggan tidak aktif

**Solusi:**
1. Verifikasi nomor pelanggan di database
2. Test kirim manual ke nomor Anda sendiri
3. Minta pelanggan cek WhatsApp

---

### **Problem 3: Delay Pengiriman**

**Penyebab:**
- Device offline sementara
- Koneksi internet lambat
- API Gateway overload

**Solusi:**
1. Pastikan device online 24/7
2. Gunakan koneksi internet stabil
3. Increase timeout di config

---

## ✅ CHECKLIST IMPLEMENTASI

- [ ] File `config_whatsapp.php` sudah dikonfigurasi
- [ ] API Key sudah benar
- [ ] Nomor sender sudah diisi
- [ ] `WA_API_ENABLED` set ke `true`
- [ ] Class `WhatsAppAutomation` sudah ada
- [ ] Code trigger sudah ditambahkan di `servis_byr_proses.php`
- [ ] Test kirim pesan berhasil
- [ ] Log file bisa ditulis
- [ ] Device WhatsApp online
- [ ] Monitoring dashboard dibuat

---

## 📞 SUPPORT

Jika ada masalah:
1. Cek log di `logs/whatsapp_log.txt`
2. Test manual dengan file test
3. Hubungi support TechArea Gateway

---

**Dokumentasi dibuat: 3 November 2025**  
**Version: 1.0**
