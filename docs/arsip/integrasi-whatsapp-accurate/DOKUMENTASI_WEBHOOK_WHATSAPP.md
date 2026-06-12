# DOKUMENTASI WEBHOOK WHATSAPP

## 📋 Daftar Isi
1. [Pengenalan](#pengenalan)
2. [URL Webhook](#url-webhook)
3. [Setup di Provider](#setup-di-provider)
4. [Fitur Auto-Reply](#fitur-auto-reply)
5. [Testing Webhook](#testing-webhook)
6. [Troubleshooting](#troubleshooting)

---

## 🎯 Pengenalan

Webhook WhatsApp adalah endpoint yang menerima callback dari WhatsApp Gateway API ketika ada:
- Pesan masuk dari pelanggan
- Status pengiriman pesan (delivered, read, failed)
- Event lainnya dari WhatsApp

### Fitur Webhook:
✅ **Auto-Reply Cerdas** - Balas otomatis berdasarkan keyword  
✅ **Cek Status Service** - Pelanggan bisa cek status via WhatsApp  
✅ **Cek Status Member** - Info poin dan benefit member  
✅ **Info Jam Operasional** - Otomatis kirim jam buka  
✅ **Logging Lengkap** - Semua aktivitas tercatat  
✅ **Multi-Provider** - Support Fonnte, Wablas, WooWA  

---

## 🔗 URL Webhook

### File Webhook:
```
c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\webhook_whatsapp.php
```

### URL Webhook (Setelah Upload ke Server):

**Local Development:**
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php
```

**Production (Contoh):**
```
https://yourdomain.com/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php
```

atau jika sudah custom path:

```
https://fitmotor.com/admin/webhook_whatsapp.php
```

⚠️ **PENTING:** 
- URL webhook harus bisa diakses dari internet (tidak bisa localhost untuk production)
- Gunakan HTTPS untuk keamanan
- Pastikan file webhook bisa diakses (tidak ada .htaccess yang block)

---

## 🛠️ Setup di Provider

### 1. FONNTE (Recommended)

**Langkah Setup:**

1. **Login ke Dashboard Fonnte**
   - Buka: https://fonnte.com
   - Login dengan akun Anda

2. **Masuk ke Menu "Webhook"**
   - Klik menu "Webhook" di sidebar

3. **Set URL Webhook**
   - URL: `https://yourdomain.com/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php`
   - Method: POST
   - Format: JSON

4. **Aktifkan Webhook untuk:**
   - ✅ Incoming Message (Pesan Masuk)
   - ✅ Message Status (Status Pengiriman)
   - ✅ Device Status (Status Device)

5. **Save & Test**
   - Klik "Save"
   - Klik "Test Webhook"
   - Cek log di `logs/webhook_log.txt`

**Screenshot Konfigurasi:**
```
┌─────────────────────────────────────────┐
│ Fonnte - Webhook Configuration         │
├─────────────────────────────────────────┤
│ Webhook URL:                            │
│ https://fitmotor.com/admin/webhook.php  │
│                                         │
│ Events:                                 │
│ ☑ Incoming Message                      │
│ ☑ Message Status                        │
│ ☑ Device Status                         │
│                                         │
│ [Test Webhook] [Save]                   │
└─────────────────────────────────────────┘
```

---

### 2. WABLAS

**Langkah Setup:**

1. **Login ke Console Wablas**
   - Buka: https://console.wablas.com
   - Login dengan akun Anda

2. **Masuk ke Menu "Webhook"**
   - Klik "Settings" → "Webhook"

3. **Set URL Webhook**
   - Webhook URL: `https://yourdomain.com/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php`
   - Webhook Type: Message Received

4. **Aktifkan Webhook**
   - Toggle "Enable Webhook" ON
   - Klik "Save"

5. **Test Webhook**
   - Kirim pesan ke nomor WhatsApp bisnis Anda
   - Cek log di `logs/webhook_log.txt`

---

### 3. WOOWA

**Langkah Setup:**

1. **Login ke Dashboard WooWA**
   - Buka: https://woowa.id
   - Login dengan akun Anda

2. **Masuk ke Menu "Webhook"**
   - Klik "Settings" → "Webhook URL"

3. **Set URL Webhook**
   - Webhook URL: `https://yourdomain.com/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php`

4. **Save & Test**
   - Klik "Save"
   - Test dengan kirim pesan

---

## 🤖 Fitur Auto-Reply

### 1. Cek Status Service

**Keyword:** `status`, `service`

**Contoh Pesan Pelanggan:**
```
Status service saya
```

**Auto-Reply:**
```
Halo Budi Santoso! 🏍️

Status Service Terakhir:
• No. Service: SV25000000139
• Tanggal: 02/11/2025
• Status: Sedang Diproses

Untuk info lebih lanjut, hubungi kami di 0812-3456-7890
```

---

### 2. Cek Status Member

**Keyword:** `member`, `poin`

**Contoh Pesan Pelanggan:**
```
Cek member saya
```

**Auto-Reply:**
```
Halo Budi Santoso! 🏍️

Status Member Anda:
• Level: 🥇 Gold
• Total Transaksi: 15x
• Total Nominal: Rp 7.500.000

Benefit Member Gold:
• Diskon 15%
• Prioritas antrian
• Gratis cuci motor
```

---

### 3. Info Harga

**Keyword:** `harga`, `biaya`

**Contoh Pesan Pelanggan:**
```
Harga service berapa?
```

**Auto-Reply:**
```
Halo Budi Santoso! Untuk informasi harga service, silakan hubungi kami di 0812-3456-7890 atau kunjungi bengkel kami. Terima kasih! 🏍️
```

---

### 4. Jam Operasional

**Keyword:** `jam`, `buka`

**Contoh Pesan Pelanggan:**
```
Jam buka berapa?
```

**Auto-Reply:**
```
Jam Operasional Fit Motor:
Senin - Sabtu: 08.00 - 17.00
Minggu: Tutup

Alamat: Jl. Raya Adiwerna, Tegal
Telp: 0812-3456-7890 🏍️
```

---

### 5. Booking Service

**Keyword:** `booking`, `reservasi`

**Contoh Pesan Pelanggan:**
```
Mau booking service
```

**Auto-Reply:**
```
Halo Budi Santoso! Untuk booking service, silakan hubungi kami di 0812-3456-7890 atau datang langsung ke bengkel. Kami siap melayani Anda! 🏍️
```

---

### 6. Pelanggan Baru (Nomor Tidak Terdaftar)

**Auto-Reply:**
```
Halo! Terima kasih telah menghubungi Fit Motor. Untuk informasi lebih lanjut, silakan hubungi kami di jam kerja. 🏍️
```

---

## 🧪 Testing Webhook

### Test 1: Webhook Terima Data

**Cara Test:**

1. **Kirim Pesan ke WhatsApp Bisnis**
   - Dari HP pribadi, kirim pesan ke nomor WhatsApp bisnis Anda
   - Contoh: "Halo"

2. **Cek Log Webhook**
   ```
   File: c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\logs\webhook_log.txt
   ```

3. **Verifikasi Log**
   ```
   [2025-11-02 15:30:45] Webhook received
   Data: {
       "device": "6281234567890",
       "sender": "6289876543210",
       "message": "Halo",
       "member_name": "Budi"
   }
   --------------------------------------------------------------------------------
   [2025-11-02 15:30:45] Detected provider: fonnte
   --------------------------------------------------------------------------------
   ```

**Expected Result:**
- ✅ Log webhook muncul
- ✅ Provider terdeteksi
- ✅ Data pesan tercatat

---

### Test 2: Auto-Reply Berfungsi

**Cara Test:**

1. **Kirim Pesan dengan Keyword**
   - Kirim: "Status service"

2. **Cek Balasan WhatsApp**
   - Harusnya dapat balasan otomatis dalam beberapa detik

3. **Cek Log**
   ```
   [2025-11-02 15:31:00] Incoming message
   Data: {
       "phone": "6289876543210",
       "message": "Status service",
       "name": "Budi"
   }
   --------------------------------------------------------------------------------
   [2025-11-02 15:31:01] Sending auto-reply
   Data: {
       "phone": "6289876543210",
       "message": "Halo Budi Santoso! ..."
   }
   --------------------------------------------------------------------------------
   [2025-11-02 15:31:02] Auto-reply sent
   Data: {
       "http_code": 200,
       "response": "{\"status\":\"success\"}"
   }
   --------------------------------------------------------------------------------
   ```

**Expected Result:**
- ✅ Pesan masuk tercatat
- ✅ Auto-reply terkirim
- ✅ HTTP code 200 (success)

---

### Test 3: Cek Status Member

**Cara Test:**

1. **Pastikan Pelanggan Ada di Database**
   ```sql
   SELECT * FROM tblpelanggan WHERE telephone = '6289876543210';
   SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB';
   ```

2. **Kirim Pesan**
   - Kirim: "Cek member"

3. **Verifikasi Balasan**
   - Harusnya dapat info status member, total transaksi, benefit

**Expected Result:**
- ✅ Data member benar
- ✅ Total transaksi sesuai database
- ✅ Benefit member ditampilkan

---

## 🔧 Troubleshooting

### Problem 1: Webhook Tidak Menerima Data

**Gejala:**
- Log webhook kosong
- Tidak ada entry di `webhook_log.txt`

**Penyebab:**
- URL webhook salah
- File webhook tidak bisa diakses
- Provider belum setup webhook

**Solusi:**

1. **Cek URL Webhook Bisa Diakses**
   ```
   Buka di browser: https://yourdomain.com/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php
   
   Harusnya muncul:
   {"status":"error","message":"Unknown provider"}
   ```

2. **Cek File Permissions**
   ```bash
   chmod 755 webhook_whatsapp.php
   chmod 777 logs/
   ```

3. **Cek .htaccess**
   - Pastikan tidak ada rule yang block akses ke webhook

4. **Test Manual dengan cURL**
   ```bash
   curl -X POST https://yourdomain.com/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php \
   -H "Content-Type: application/json" \
   -d '{"device":"123","sender":"456","message":"test"}'
   ```

---

### Problem 2: Auto-Reply Tidak Terkirim

**Gejala:**
- Webhook menerima pesan
- Tapi tidak ada balasan otomatis

**Penyebab:**
- API WhatsApp tidak dikonfigurasi
- API key salah
- Saldo API habis

**Solusi:**

1. **Cek Konfigurasi API**
   ```php
   // Edit config_whatsapp.php
   define('WA_API_ENABLED', true); // Harus true
   define('WA_API_KEY', 'your_actual_api_key'); // API key benar
   ```

2. **Cek Log Error**
   ```
   File: logs/webhook_log.txt
   
   Cari:
   [2025-11-02 15:31:02] Auto-reply sent
   Data: {
       "http_code": 401, // ERROR!
       "response": "Invalid API key"
   }
   ```

3. **Test API Manual**
   ```php
   // test_api.php
   include "class_whatsapp_automation.php";
   include "config_whatsapp.php";
   
   $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
   $result = $wa->sendTerimaKasih('SV25000000001');
   print_r($result);
   ```

---

### Problem 3: Pelanggan Tidak Dikenali

**Gejala:**
- Pelanggan kirim pesan
- Dapat balasan "Unknown customer"
- Padahal sudah terdaftar

**Penyebab:**
- Nomor telepon di database berbeda format
- Nomor tidak ada di database

**Solusi:**

1. **Cek Nomor di Database**
   ```sql
   SELECT nopelanggan, namapelanggan, telephone, notlp 
   FROM tblpelanggan 
   WHERE telephone LIKE '%89876543210%' 
   OR notlp LIKE '%89876543210%';
   ```

2. **Update Nomor Telepon**
   ```sql
   -- Format yang benar: 628xxxxxxxxxx
   UPDATE tblpelanggan 
   SET telephone = '6289876543210' 
   WHERE nopelanggan = 'AD 1234 AB';
   ```

3. **Cek Log Webhook**
   ```
   [2025-11-02 15:31:00] Incoming message
   Data: {
       "phone": "6289876543210", // Cek format nomor
       ...
   }
   ```

---

### Problem 4: Log File Tidak Terbuat

**Gejala:**
- File `logs/webhook_log.txt` tidak ada
- Error "Permission denied"

**Solusi:**

1. **Buat Folder Logs Manual**
   ```bash
   mkdir logs
   chmod 777 logs
   ```

2. **Buat File Log Manual**
   ```bash
   touch logs/webhook_log.txt
   chmod 666 logs/webhook_log.txt
   ```

3. **Cek PHP Permissions**
   ```php
   // test_log.php
   $log_file = __DIR__ . '/logs/webhook_log.txt';
   
   if(is_writable(dirname($log_file))) {
       echo "Directory writable: YES";
   } else {
       echo "Directory writable: NO";
   }
   ```

---

## 📊 Monitoring Webhook

### Cek Aktivitas Webhook

**Query Monitoring:**

```sql
-- Jika Anda buat tabel tbl_whatsapp_messages
SELECT 
    DATE(created_at) as tanggal,
    COUNT(*) as total_pesan,
    COUNT(CASE WHEN direction = 'incoming' THEN 1 END) as pesan_masuk,
    COUNT(CASE WHEN direction = 'outgoing' THEN 1 END) as pesan_keluar
FROM tbl_whatsapp_messages
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY tanggal DESC;
```

### Analisa Log File

**Bash Command:**

```bash
# Total webhook received hari ini
grep "Webhook received" logs/webhook_log.txt | grep "$(date +%Y-%m-%d)" | wc -l

# Total auto-reply sent hari ini
grep "Auto-reply sent" logs/webhook_log.txt | grep "$(date +%Y-%m-%d)" | wc -l

# Lihat 10 log terakhir
tail -n 50 logs/webhook_log.txt
```

---

## 🎯 Customisasi Auto-Reply

### Tambah Keyword Baru

Edit file `webhook_whatsapp.php`, fungsi `generateAutoReply()`:

```php
function generateAutoReply($message, $customer) {
    $message_lower = strtolower($message);
    $no_pelanggan = $customer['nopelanggan'];
    $nama = $customer['namapelanggan'];
    
    // TAMBAHKAN KEYWORD BARU DI SINI:
    
    // Contoh: Promo
    if(strpos($message_lower, 'promo') !== false) {
        return "Halo {$nama}! 🎉\n\nPromo Bulan Ini:\n• Ganti Oli: Diskon 20%\n• Service Berkala: Gratis Cuci\n• Member Gold: Extra Diskon 10%\n\nYuk manfaatkan promonya! 🏍️";
    }
    
    // Contoh: Lokasi
    if(strpos($message_lower, 'lokasi') !== false || strpos($message_lower, 'alamat') !== false) {
        return "Lokasi Fit Motor:\n📍 Jl. Raya Adiwerna No. 123, Tegal\n🗺️ Google Maps: https://goo.gl/maps/xxx\n📞 Telp: 0812-3456-7890";
    }
    
    // ... keyword lainnya ...
}
```

---

## 📚 Referensi

- **File Webhook:** `_admincab/webhook_whatsapp.php`
- **Config WhatsApp:** `_admincab/config_whatsapp.php`
- **Class WhatsApp:** `_admincab/class_whatsapp_automation.php`
- **Log File:** `_admincab/logs/webhook_log.txt`

---

## ✅ Checklist Setup Webhook

**Persiapan:**
- [ ] Upload file webhook ke server
- [ ] Pastikan URL webhook bisa diakses dari internet
- [ ] Buat folder `logs/` dengan permission 777
- [ ] Setup API WhatsApp di `config_whatsapp.php`

**Setup di Provider:**
- [ ] Login ke dashboard WhatsApp Gateway
- [ ] Masuk ke menu Webhook
- [ ] Set URL webhook
- [ ] Aktifkan event "Incoming Message"
- [ ] Save & test webhook

**Testing:**
- [ ] Kirim pesan test ke WhatsApp bisnis
- [ ] Cek log webhook muncul
- [ ] Test auto-reply dengan keyword
- [ ] Verifikasi data pelanggan benar

**Monitoring:**
- [ ] Cek log file secara berkala
- [ ] Monitor aktivitas webhook
- [ ] Backup log file mingguan

---

**Webhook siap digunakan!** 🎉

Pelanggan sekarang bisa cek status service dan member via WhatsApp secara otomatis!

---

**Dibuat:** 2 November 2025  
**Versi:** 1.0  
**Developer:** Fit Motor Development Team
