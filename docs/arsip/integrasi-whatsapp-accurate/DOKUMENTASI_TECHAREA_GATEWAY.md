# DOKUMENTASI TECHAREA WHATSAPP GATEWAY

## 📱 INFORMASI API

**Provider:** TechArea WhatsApp Gateway  
**Endpoint:** `https://wagw.techareadev.biz.id/send-message`  
**API Key:** `nv5colO4cvgkAbVqtxWo5tBzSlIrMy`  
**Method:** POST (JSON) atau GET (URL Parameters)

---

## 🔧 KONFIGURASI

### **File: config_whatsapp.php**

```php
// API Key
define('WA_API_KEY', 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy');

// Nomor Sender (ganti dengan nomor device Anda)
define('WA_SENDER_NUMBER', '62888xxxx');

// API URL
define('WA_API_URL', 'https://wagw.techareadev.biz.id/send-message');

// Provider
define('WA_PROVIDER', 'techarea');

// Enable API
define('WA_API_ENABLED', true); // Set true untuk aktifkan
```

---

## 📡 API SPECIFICATION

### **Send Message API**

**Endpoint:** `https://wagw.techareadev.biz.id/send-message`  
**Method:** POST | GET

### **Parameters:**

| Parameter | Type   | Required | Description                          |
|-----------|--------|----------|--------------------------------------|
| api_key   | string | Yes      | API Key: nv5colO4cvgkAbVqtxWo5tBzSlIrMy |
| sender    | string | Yes      | Nomor device pengirim (ex: 62888xxxx) |
| number    | string | Yes      | Nomor penerima (ex: 62888xxxx)       |
| message   | string | Yes      | Pesan yang akan dikirim              |

---

## 📝 CONTOH REQUEST

### **1. POST Request (JSON)**

**cURL:**
```bash
curl -X POST https://wagw.techareadev.biz.id/send-message \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "nv5colO4cvgkAbVqtxWo5tBzSlIrMy",
    "sender": "62888xxxx",
    "number": "62888xxxx",
    "message": "Hello World"
  }'
```

**PHP (cURL):**
```php
<?php
$curl = curl_init();

$data = json_encode([
    'api_key' => 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy',
    'sender' => '62888xxxx',
    'number' => '62888xxxx',
    'message' => 'Hello World'
]);

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://wagw.techareadev.biz.id/send-message',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $response;
?>
```

**JavaScript (Fetch):**
```javascript
fetch('https://wagw.techareadev.biz.id/send-message', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    api_key: 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy',
    sender: '62888xxxx',
    number: '62888xxxx',
    message: 'Hello World'
  })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

---

### **2. GET Request (URL Parameters)**

**URL:**
```
https://wagw.techareadev.biz.id/send-message?api_key=nv5colO4cvgkAbVqtxWo5tBzSlIrMy&sender=62888xxxx&number=62888xxxx&message=Hello%20World
```

**cURL:**
```bash
curl "https://wagw.techareadev.biz.id/send-message?api_key=nv5colO4cvgkAbVqtxWo5tBzSlIrMy&sender=62888xxxx&number=62888xxxx&message=Hello%20World"
```

**PHP (file_get_contents):**
```php
<?php
$params = http_build_query([
    'api_key' => 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy',
    'sender' => '62888xxxx',
    'number' => '62888xxxx',
    'message' => 'Hello World'
]);

$url = 'https://wagw.techareadev.biz.id/send-message?' . $params;
$response = file_get_contents($url);

echo $response;
?>
```

---

## 🔄 RESPONSE FORMAT

### **Success Response:**
```json
{
  "status": "success",
  "message": "Message sent successfully",
  "data": {
    "message_id": "xxxxx",
    "sender": "62888xxxx",
    "recipient": "62888xxxx",
    "timestamp": "2025-11-03 13:30:00"
  }
}
```

### **Error Response:**
```json
{
  "status": "error",
  "message": "Invalid API Key",
  "error_code": "AUTH_FAILED"
}
```

---

## 🚀 IMPLEMENTASI DI SISTEM

### **1. Kirim Pesan Manual**

**File: test_send_wa.php**
```php
<?php
require_once 'config_whatsapp.php';

function sendWhatsApp($phone, $message) {
    $curl = curl_init();
    
    $data = json_encode([
        'api_key' => WA_API_KEY,
        'sender' => WA_SENDER_NUMBER,
        'number' => $phone,
        'message' => $message
    ]);
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => WA_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    return [
        'success' => $http_code == 200,
        'response' => json_decode($response, true),
        'http_code' => $http_code
    ];
}

// Test kirim pesan
$result = sendWhatsApp('628123456789', 'Test pesan dari sistem bengkel');

if($result['success']) {
    echo "✅ Pesan berhasil dikirim!\n";
} else {
    echo "❌ Gagal kirim pesan: HTTP " . $result['http_code'] . "\n";
}

print_r($result['response']);
?>
```

---

### **2. Kirim Otomatis Setelah Transaksi**

**File: proses_pembayaran.php**
```php
<?php
// Setelah pembayaran berhasil
if($pembayaran_sukses) {
    // Load WhatsApp class
    require_once 'class_whatsapp_automation.php';
    require_once 'config_whatsapp.php';
    
    // Inisialisasi
    $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
    
    // Kirim terima kasih
    $result = $wa->sendTerimaKasih($no_service);
    
    if($result['success']) {
        echo "WhatsApp terkirim ke " . $result['phone'];
    }
}
?>
```

---

### **3. Kirim Reminder Follow Up**

**File: cron_reminder_followup.php**
```php
<?php
require_once '../config/koneksi.php';
require_once 'class_whatsapp_automation.php';
require_once 'config_whatsapp.php';

// Inisialisasi
$wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);

// Broadcast ke pelanggan yang perlu follow up
$result = $wa->broadcastFollowUp();

echo "Total: " . $result['total'] . "\n";
echo "Terkirim: " . $result['sent'] . "\n";
echo "Gagal: " . $result['failed'] . "\n";

// Detail
foreach($result['details'] as $detail) {
    echo $detail['nama'] . " - " . $detail['status'] . "\n";
}
?>
```

---

## 📋 FORMAT NOMOR TELEPON

### **Format yang Benar:**

✅ **Dengan kode negara (62):**
- `628123456789`
- `6281234567890`

✅ **Tanpa tanda plus (+):**
- `628123456789`

❌ **Format yang SALAH:**
- `+628123456789` (jangan pakai +)
- `08123456789` (harus pakai 62)
- `62-812-3456-789` (jangan pakai strip)
- `62 812 3456 789` (jangan pakai spasi)

### **Fungsi Clean Phone Number:**
```php
function cleanPhoneNumber($phone) {
    // Hapus karakter non-numeric
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Jika dimulai dengan 0, ganti dengan 62
    if(substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Jika belum ada 62, tambahkan
    if(substr($phone, 0, 2) != '62') {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

// Contoh penggunaan
echo cleanPhoneNumber('08123456789');     // Output: 628123456789
echo cleanPhoneNumber('+62 812-3456-789'); // Output: 628123456789
echo cleanPhoneNumber('628123456789');     // Output: 628123456789
```

---

## 📝 TEMPLATE PESAN

### **1. Terima Kasih Setelah Service**
```
Terima kasih telah menggunakan layanan kami! 🙏

Detail Service:
📋 No Service: SV25000000001
📅 Tanggal: 03 November 2025
💰 Total: Rp 500.000

Status Member: 🥇 Gold
Benefit: Diskon 10% untuk service berikutnya

Kami tunggu kunjungan Anda berikutnya! 😊

Salam,
FIT MOTOR
```

### **2. Reminder Follow Up**
```
Halo Bapak/Ibu [NAMA],

Sudah lama tidak berkunjung ke bengkel kami 😊

Terakhir service: [TANGGAL]
Sudah: [HARI] hari yang lalu

Yuk service kendaraan Anda sekarang!
Dapatkan diskon spesial untuk Anda 🎁

Hubungi kami:
📞 08123456789
📍 Jl. Raya No. 123

Terima kasih,
FIT MOTOR
```

### **3. Promo Member**
```
🎉 PROMO SPESIAL UNTUK MEMBER GOLD! 🎉

Dapatkan diskon 15% untuk:
✅ Ganti oli
✅ Tune up
✅ Service berkala

Berlaku sampai: 30 November 2025

Buruan booking sekarang!
📞 08123456789

*Syarat & ketentuan berlaku
```

---

## 🔐 KEAMANAN

### **Best Practices:**

1. **Jangan Hardcode API Key di Code**
   ```php
   // ❌ JANGAN seperti ini
   $api_key = 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy';
   
   // ✅ Gunakan config file
   require_once 'config_whatsapp.php';
   $api_key = WA_API_KEY;
   ```

2. **Simpan API Key di File Terpisah**
   - Buat file `config_whatsapp.php`
   - Jangan commit ke Git (tambahkan ke `.gitignore`)

3. **Validasi Nomor Telepon**
   ```php
   function isValidPhoneNumber($phone) {
       // Harus dimulai dengan 62
       if(substr($phone, 0, 2) != '62') return false;
       
       // Panjang 10-13 digit
       if(strlen($phone) < 10 || strlen($phone) > 13) return false;
       
       return true;
   }
   ```

4. **Rate Limiting**
   ```php
   // Delay antar pesan untuk menghindari spam
   sleep(2); // 2 detik delay
   ```

5. **Logging**
   ```php
   // Log setiap pengiriman
   logWhatsAppActivity($no_service, $phone, 'sent', 'Success');
   ```

---

## 🐛 TROUBLESHOOTING

### **1. HTTP 401 - Unauthorized**
**Penyebab:** API Key salah  
**Solusi:**
- Cek API Key di `config_whatsapp.php`
- Pastikan: `nv5colO4cvgkAbVqtxWo5tBzSlIrMy`

### **2. HTTP 400 - Bad Request**
**Penyebab:** Parameter tidak lengkap  
**Solusi:**
- Cek semua parameter: api_key, sender, number, message
- Pastikan format JSON benar

### **3. Pesan Tidak Terkirim**
**Penyebab:** Nomor tidak valid atau device offline  
**Solusi:**
- Cek format nomor (harus 62xxx)
- Pastikan device WhatsApp online
- Cek nomor sender sudah benar

### **4. Timeout**
**Penyebab:** Koneksi lambat  
**Solusi:**
- Increase timeout: `CURLOPT_TIMEOUT => 60`
- Cek koneksi internet

### **5. cURL Error**
**Penyebab:** cURL tidak enabled  
**Solusi:**
- Enable cURL di php.ini
- Restart Apache

---

## 📊 MONITORING

### **1. Log File**
Lokasi: `logs/whatsapp_log.txt`

```
[2025-11-03 13:30:00] Service: SV25000000001 | Phone: 628123456789 | Status: success | Message: Sent
[2025-11-03 13:30:05] Service: SV25000000002 | Phone: 628987654321 | Status: failed | Message: Invalid number
```

### **2. Dashboard Monitoring**
Buat halaman untuk monitoring:
- Total pesan terkirim hari ini
- Total pesan gagal
- Success rate
- Last 10 messages

---

## 📞 SUPPORT

**Jika ada masalah:**
1. Cek log di `logs/whatsapp_log.txt`
2. Test manual dengan `test_send_wa.php`
3. Hubungi support TechArea Gateway

---

## ✅ CHECKLIST SETUP

- [ ] API Key sudah diisi di `config_whatsapp.php`
- [ ] Nomor sender sudah diisi
- [ ] `WA_API_ENABLED` set ke `true`
- [ ] `WA_PROVIDER` set ke `techarea`
- [ ] Test kirim pesan berhasil
- [ ] Log file bisa ditulis
- [ ] Nomor telepon terformat dengan benar
- [ ] Device WhatsApp online

---

**Dokumentasi dibuat: 3 November 2025**  
**Version: 1.0**  
**Provider: TechArea WhatsApp Gateway**
