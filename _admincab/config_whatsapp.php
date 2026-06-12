<?php
/**
 * File: config_whatsapp.php
 * Deskripsi: Konfigurasi WhatsApp API untuk otomasi pesan
 * 
 * CARA SETUP:
 * 1. Daftar di WhatsApp Gateway (Fonnte/Wablas/WooWA)
 * 2. Dapatkan API Key
 * 3. Isi konfigurasi di bawah ini
 * 4. Set WA_API_ENABLED = true untuk aktifkan auto-send
 */

// =====================================================
// WHATSAPP API CONFIGURATION
// =====================================================

// Enable/Disable WhatsApp Auto Send
// Set true untuk kirim otomatis via API
// Set false untuk mode manual (WhatsApp Web link)
define('WA_API_ENABLED', true); // Ubah ke true jika sudah setup API

// WhatsApp API Key
// Dapatkan dari dashboard WhatsApp Gateway Anda
define('WA_API_KEY', 'nv5colO4cvgkAbVqtxWo5tBzSlIrMy'); // API Key dari TechArea Gateway

// WhatsApp Sender Number (nomor pengirim)
define('WA_SENDER_NUMBER', '6281229608542'); // Ganti dengan nomor device Anda

// WhatsApp API URL
// URL endpoint API untuk kirim pesan
// TechArea Gateway: https://wagw.techareadev.biz.id/send-message
// Fonnte: https://api.fonnte.com/send
// Wablas: https://console.wablas.com/api/send-message
// WooWA: https://api.woowa.id/send
define('WA_API_URL', 'https://wagw.techareadev.biz.id/send-message'); // TechArea Gateway

// =====================================================
// WHATSAPP SETTINGS
// =====================================================

// Auto send WhatsApp after payment
// Set true untuk otomatis kirim setelah pembayaran
// Set false untuk manual (tampilkan tombol saja)
define('WA_AUTO_SEND_AFTER_PAYMENT', true); // Ubah ke true untuk auto-send

// Delay before sending (seconds)
// Delay waktu sebelum kirim pesan (dalam detik)
// Berguna untuk memastikan data sudah tersimpan
define('WA_SEND_DELAY', 3);

// Send to customer phone or admin phone for testing
// Set true untuk kirim ke nomor admin (testing mode)
// Set false untuk kirim ke nomor pelanggan (production mode)
define('WA_TESTING_MODE', false);
define('WA_ADMIN_PHONE', '6281229608542'); // Nomor admin untuk testing

// =====================================================
// MESSAGE TEMPLATES
// =====================================================

// Custom message prefix (opsional)
// Akan ditambahkan di awal setiap pesan
define('WA_MESSAGE_PREFIX', ''); // Contoh: '[FIT MOTOR] '

// Custom message footer (opsional)
// Akan ditambahkan di akhir setiap pesan
define('WA_MESSAGE_FOOTER', ''); // Contoh: '\n\nTerima kasih!'

// =====================================================
// LOGGING
// =====================================================

// Enable logging untuk tracking pesan terkirim
define('WA_ENABLE_LOGGING', true);

// Log file path
define('WA_LOG_FILE', __DIR__ . '/logs/whatsapp_log.txt');

// =====================================================
// FUNCTIONS
// =====================================================

/**
 * Log WhatsApp activity
 */
function logWhatsAppActivity($no_service, $phone, $status, $message = '') {
    if(!WA_ENABLE_LOGGING) return;
    
    $log_dir = dirname(WA_LOG_FILE);
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] Service: {$no_service} | Phone: {$phone} | Status: {$status} | Message: {$message}\n";
    
    file_put_contents(WA_LOG_FILE, $log_entry, FILE_APPEND);
}

/**
 * Check if WhatsApp is configured
 */
function isWhatsAppConfigured() {
    if(!WA_API_ENABLED) return false;
    if(WA_API_KEY == 'your_api_key_here') return false;
    if(empty(WA_API_KEY)) return false;
    
    return true;
}

// =====================================================
// PROVIDER SPECIFIC SETTINGS
// =====================================================

// Provider yang digunakan
define('WA_PROVIDER', 'techarea'); // Options: techarea, fonnte, wablas, woowa

// Provider-specific configurations
switch(WA_PROVIDER) {
    case 'techarea':
        // TechArea Gateway specific settings
        // Tidak perlu konfigurasi tambahan
        // API sudah lengkap dengan api_key, sender, number, message
        break;
        
    case 'fonnte':
        // Fonnte specific settings
        define('WA_DEVICE_ID', ''); // Opsional, untuk multi-device
        break;
        
    case 'wablas':
        // Wablas specific settings
        define('WA_DOMAIN', 'https://console.wablas.com'); // Domain Wablas Anda
        break;
        
    case 'woowa':
        // WooWA specific settings
        define('WA_INSTANCE_ID', ''); // Instance ID WooWA
        break;
}

// =====================================================
// NOTES
// =====================================================

/*
CARA SETUP WHATSAPP API:

1. TECHAREA GATEWAY (CURRENT - Custom Gateway)
   - API Key: nv5colO4cvgkAbVqtxWo5tBzSlIrMy
   - Endpoint: https://wagw.techareadev.biz.id/send-message
   - Method: POST atau GET
   - Parameters:
     * api_key: nv5colO4cvgkAbVqtxWo5tBzSlIrMy
     * sender: 62888xxxx (nomor device Anda)
     * number: 62888xxxx (nomor penerima)
     * message: Pesan yang akan dikirim
   
   Contoh Request (POST JSON):
   {
     "api_key": "nv5colO4cvgkAbVqtxWo5tBzSlIrMy",
     "sender": "62888xxxx",
     "number": "62888xxxx",
     "message": "Hello World"
   }
   
   Contoh Request (GET URL):
   https://wagw.techareadev.biz.id/send-message?api_key=nv5colO4cvgkAbVqtxWo5tBzSlIrMy&sender=62888xxxx&number=62888xxxx&message=Hello World

2. FONNTE (Alternative - Mulai Rp 50.000/bulan)
   - Daftar: https://fonnte.com
   - Login ke dashboard
   - Copy API Key dari menu "API"
   - Set WA_API_KEY dengan API key Anda
   - Set WA_API_URL = 'https://api.fonnte.com/send'
   - Set WA_PROVIDER = 'fonnte'

3. WABLAS (Alternative - Mulai Rp 100.000/bulan)
   - Daftar: https://wablas.com
   - Login ke console
   - Copy API Token
   - Set WA_API_KEY dengan token Anda
   - Set WA_API_URL = 'https://console.wablas.com/api/send-message'
   - Set WA_PROVIDER = 'wablas'

4. WOOWA (Alternative - Mulai Rp 75.000/bulan)
   - Daftar: https://woowa.id
   - Login ke dashboard
   - Copy API Key dan Instance ID
   - Set WA_API_KEY dan WA_INSTANCE_ID
   - Set WA_API_URL = 'https://api.woowa.id/send'
   - Set WA_PROVIDER = 'woowa'

TESTING:
1. Set WA_TESTING_MODE = true
2. Set WA_ADMIN_PHONE dengan nomor Anda
3. Set WA_SENDER_NUMBER dengan nomor device Anda
4. Test kirim pesan
5. Jika berhasil, set WA_TESTING_MODE = false

PRODUCTION:
1. Set WA_API_ENABLED = true
2. Set WA_SENDER_NUMBER dengan nomor device yang benar
3. Set WA_AUTO_SEND_AFTER_PAYMENT = true (jika ingin auto-send)
4. Set WA_TESTING_MODE = false
5. Monitor log di logs/whatsapp_log.txt
*/
?>
