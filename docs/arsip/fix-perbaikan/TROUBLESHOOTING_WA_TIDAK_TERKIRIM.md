# TROUBLESHOOTING: WHATSAPP TIDAK TERKIRIM OTOMATIS

## 🔍 MASALAH

WhatsApp tidak terkirim otomatis setelah klik tombol "Bayar" di servis reguler.

---

## ✅ SOLUSI YANG SUDAH DILAKUKAN

### **1. Perbaikan Error Handling**

**File yang diupdate:**
- ✅ `servis-input-reguler.php` (Line 997-1042)
- ✅ `servis-input-reguler-rst.php` (Line 772-805 & 816-849)

**Perubahan:**
- Tambah `try-catch` untuk menangkap error
- Tambah `file_exists()` check sebelum `require_once`
- Tambah `class_exists()` check sebelum inisialisasi
- Tambah `isset()` check untuk result
- Hapus dependency ke `isWhatsAppConfigured()` yang mungkin tidak terdefinisi

**Before:**
```php
require_once 'config_whatsapp.php';
require_once 'class_whatsapp_automation.php';

if(defined('WA_API_ENABLED') && WA_API_ENABLED && 
   defined('WA_AUTO_SEND_AFTER_PAYMENT') && WA_AUTO_SEND_AFTER_PAYMENT && 
   function_exists('isWhatsAppConfigured') && isWhatsAppConfigured()) {
    // ...
}
```

**After:**
```php
try {
    if(file_exists('config_whatsapp.php')) {
        require_once 'config_whatsapp.php';
    }
    
    if(file_exists('class_whatsapp_automation.php')) {
        require_once 'class_whatsapp_automation.php';
    }
    
    if(defined('WA_API_ENABLED') && WA_API_ENABLED && 
       defined('WA_AUTO_SEND_AFTER_PAYMENT') && WA_AUTO_SEND_AFTER_PAYMENT) {
        
        if(class_exists('WhatsAppAutomation')) {
            // ...
        }
    }
} catch(Exception $e) {
    if(function_exists('logWhatsAppActivity')) {
        logWhatsAppActivity($no_service, '', 'error', 'Exception: ' . $e->getMessage());
    }
}
```

---

### **2. File Test Debugging**

**File baru yang dibuat:**

#### **test_wa_debug.php** ✅
**Lokasi:** `web-bengkel/aplikasi/aplikasi/_admincab/test_wa_debug.php`

**Fungsi:**
- Cek file config & class ada
- Cek semua konstanta terdefinisi
- Cek class WhatsAppAutomation ada
- Cek fungsi helper ada
- Cek folder logs writable
- Test inisialisasi class
- Tampilkan service terakhir
- Tampilkan log terakhir
- Cek semua kondisi trigger

**Cara Akses:**
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/test_wa_debug.php
```

#### **test_wa_send.php** ✅
**Lokasi:** `web-bengkel/aplikasi/aplikasi/_admincab/test_wa_send.php`

**Fungsi:**
- Test kirim WhatsApp manual ke service tertentu
- Tampilkan data service & pelanggan
- Tampilkan proses pengiriman step by step
- Tampilkan hasil (success/failed)
- Log hasil pengiriman

**Cara Akses:**
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/test_wa_send.php?no_service=SV25000000001
```

---

## 🔧 LANGKAH TROUBLESHOOTING

### **STEP 1: Jalankan Debug Tool**

1. Buka browser
2. Akses: `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/test_wa_debug.php`
3. Cek semua item:

**Yang HARUS ✅ (hijau):**
- ✅ File config_whatsapp.php ADA
- ✅ File class_whatsapp_automation.php ADA
- ✅ WA_API_ENABLED: TRUE
- ✅ WA_AUTO_SEND_AFTER_PAYMENT: TRUE
- ✅ WA_API_KEY: nv5colO4cvgkAbVqtxWo5tBzSlIrMy
- ✅ WA_SENDER_NUMBER: 6281229608542
- ✅ WA_API_URL: https://wagw.techareadev.biz.id/send-message
- ✅ Class WhatsAppAutomation ADA
- ✅ Folder logs ADA dan WRITABLE
- ✅ SEMUA KONDISI: TERPENUHI

**Jika ada yang ❌ (merah):**
- Perbaiki sesuai petunjuk di halaman debug

---

### **STEP 2: Test Kirim Manual**

1. Di halaman debug, lihat tabel "CEK SERVICE TERAKHIR"
2. Klik link "Test Kirim" pada salah satu service
3. Akan redirect ke `test_wa_send.php`
4. Lihat proses pengiriman:
   - ✅ Inisialisasi class berhasil
   - ✅ Data service lengkap
   - ✅ Nomor telepon ada
   - ✅ Pesan berhasil dikirim

**Jika berhasil:**
```
✅ PESAN BERHASIL DIKIRIM!
Nomor: 628123456789
Cek WhatsApp pelanggan untuk memastikan pesan masuk.
```

**Jika gagal:**
```
❌ GAGAL MENGIRIM PESAN!
Error: [pesan error]
```

---

### **STEP 3: Cek Log File**

**Lokasi:** `web-bengkel/aplikasi/aplikasi/_admincab/logs/whatsapp_log.txt`

**Format Log:**
```
[2025-11-04 19:30:00] Service: SV25000000001 | Phone: 628123456789 | Status: sent | Message: Auto-sent after payment (reguler)
[2025-11-04 19:31:00] Service: SV25000000002 | Phone:  | Status: failed | Message: Nomor telepon tidak valid
[2025-11-04 19:32:00] Service: SV25000000003 | Phone:  | Status: error | Message: Exception: Class not found
```

**Analisa Log:**
- **Status: sent** → Berhasil terkirim ✅
- **Status: failed** → Gagal kirim (cek message) ❌
- **Status: error** → Ada exception (cek message) ❌

---

### **STEP 4: Test Pembayaran Real**

1. Login ke sistem
2. Buka "Service Reguler"
3. Buat service baru atau buka service existing
4. Input pelanggan (pastikan nomor telepon valid: 628xxx)
5. Input barang/jasa
6. Klik tab "Pembayaran"
7. Input jumlah bayar
8. **Klik "BAYAR"**
9. Tunggu 3-5 detik (delay)
10. Cek log file: `logs/whatsapp_log.txt`
11. Cek WhatsApp pelanggan

**Expected Result:**
```
Log: [2025-11-04 19:35:00] Service: SV25000000004 | Phone: 628123456789 | Status: sent | Message: Auto-sent after payment (reguler)
WhatsApp: Pesan terima kasih masuk ke HP pelanggan
```

---

## 🐛 KEMUNGKINAN PENYEBAB & SOLUSI

### **Problem 1: File Tidak Ditemukan**

**Gejala:**
```
Log: Exception: require_once(config_whatsapp.php): failed to open stream
```

**Penyebab:**
- File `config_whatsapp.php` atau `class_whatsapp_automation.php` tidak ada

**Solusi:**
```bash
# Cek file ada
ls -la config_whatsapp.php
ls -la class_whatsapp_automation.php

# Jika tidak ada, download ulang atau restore dari backup
```

---

### **Problem 2: Class Not Found**

**Gejala:**
```
Log: Exception: Class 'WhatsAppAutomation' not found
```

**Penyebab:**
- File class tidak ter-load dengan benar
- Syntax error di file class

**Solusi:**
1. Cek syntax error:
   ```bash
   php -l class_whatsapp_automation.php
   ```

2. Pastikan class terdefinisi:
   ```php
   <?php
   class WhatsAppAutomation {
       // ...
   }
   ?>
   ```

---

### **Problem 3: Konstanta Tidak Terdefinisi**

**Gejala:**
```
Warning: Use of undefined constant WA_API_KEY
```

**Penyebab:**
- Config tidak ter-load
- Konstanta tidak didefinisikan

**Solusi:**
Edit `config_whatsapp.php`:
```php
define('WA_API_ENABLED', true);
define('WA_AUTO_SEND_AFTER_PAYMENT', true);
define('WA_API_KEY', 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy');
define('WA_SENDER_NUMBER', '6281229608542');
define('WA_API_URL', 'https://wagw.techareadev.biz.id/send-message');
```

---

### **Problem 4: Nomor Telepon Tidak Valid**

**Gejala:**
```
Log: Status: failed | Message: Nomor telepon tidak valid
```

**Penyebab:**
- Pelanggan tidak punya nomor telepon
- Format nomor salah (bukan 62xxx)

**Solusi:**
```sql
-- Cek nomor pelanggan
SELECT nopelanggan, namapelanggan, telephone, notlp
FROM tblpelanggan
WHERE nopelanggan = 'AD 1234 AB';

-- Update nomor jika salah
UPDATE tblpelanggan
SET telephone = '628123456789'
WHERE nopelanggan = 'AD 1234 AB';
```

---

### **Problem 5: API Gateway Error**

**Gejala:**
```
Log: Status: failed | Message: Gagal mengirim pesan: HTTP 401
```

**Penyebab:**
- API Key salah
- Device offline
- API Gateway down

**Solusi:**
1. Cek API Key benar
2. Cek device WhatsApp online
3. Test API manual:
   ```bash
   curl -X POST https://wagw.techareadev.biz.id/send-message \
     -H "Content-Type: application/json" \
     -d '{
       "api_key": "nv5colO4cvgkAbVqtxWo5tBzSlIrMy",
       "sender": "6281229608542",
       "number": "628123456789",
       "message": "Test"
     }'
   ```

---

### **Problem 6: Kondisi Tidak Terpenuhi**

**Gejala:**
- Tidak ada log sama sekali
- Tidak ada error
- Tidak ada pengiriman

**Penyebab:**
- `WA_API_ENABLED` = false
- `WA_AUTO_SEND_AFTER_PAYMENT` = false
- Class tidak ada

**Solusi:**
Edit `config_whatsapp.php`:
```php
define('WA_API_ENABLED', true);  // ⚠️ HARUS TRUE
define('WA_AUTO_SEND_AFTER_PAYMENT', true);  // ⚠️ HARUS TRUE
```

---

### **Problem 7: Folder Logs Tidak Writable**

**Gejala:**
```
Warning: file_put_contents(logs/whatsapp_log.txt): failed to open stream
```

**Penyebab:**
- Folder logs tidak ada
- Folder logs tidak writable

**Solusi:**
```bash
# Buat folder
mkdir logs

# Set permission
chmod 777 logs

# Atau via PHP
<?php
if(!is_dir('logs')) {
    mkdir('logs', 0777, true);
}
?>
```

---

## 📊 CHECKLIST DEBUGGING

Gunakan checklist ini untuk memastikan semua sudah benar:

- [ ] File `config_whatsapp.php` ada
- [ ] File `class_whatsapp_automation.php` ada
- [ ] `WA_API_ENABLED` = true
- [ ] `WA_AUTO_SEND_AFTER_PAYMENT` = true
- [ ] `WA_API_KEY` terisi dengan benar
- [ ] `WA_SENDER_NUMBER` terisi dengan benar
- [ ] `WA_API_URL` terisi dengan benar
- [ ] Class `WhatsAppAutomation` terdefinisi
- [ ] Folder `logs` ada dan writable
- [ ] Test debug berhasil (semua hijau)
- [ ] Test kirim manual berhasil
- [ ] Device WhatsApp online
- [ ] Nomor pelanggan valid (format 62xxx)
- [ ] Test pembayaran real berhasil
- [ ] Log file terisi
- [ ] WhatsApp masuk ke HP pelanggan

---

## 🎯 KESIMPULAN

**Perbaikan yang sudah dilakukan:**
1. ✅ Tambah error handling dengan try-catch
2. ✅ Tambah file_exists() check
3. ✅ Tambah class_exists() check
4. ✅ Hapus dependency ke isWhatsAppConfigured()
5. ✅ Buat file test_wa_debug.php
6. ✅ Buat file test_wa_send.php
7. ✅ Update servis-input-reguler.php
8. ✅ Update servis-input-reguler-rst.php

**Langkah selanjutnya:**
1. ⚠️ Jalankan `test_wa_debug.php` untuk cek semua kondisi
2. ⚠️ Jalankan `test_wa_send.php` untuk test kirim manual
3. ⚠️ Cek log file untuk melihat error (jika ada)
4. ⚠️ Test pembayaran real
5. ⚠️ Cek WhatsApp pelanggan

**Jika masih gagal:**
- Screenshot hasil `test_wa_debug.php`
- Copy isi `logs/whatsapp_log.txt`
- Screenshot error message (jika ada)

---

**Dokumentasi dibuat: 4 November 2025**  
**Version: 1.0**  
**Status: Ready for Testing** ✅
