<?php
/**
 * File: webhook_whatsapp.php
 * Deskripsi: Webhook handler untuk menerima callback dari WhatsApp Gateway API
 * 
 * CARA SETUP:
 * 1. Upload file ini ke server
 * 2. Dapatkan URL webhook: https://yourdomain.com/web-bengkel/_admincab/webhook_whatsapp.php
 * 3. Daftarkan URL ini di dashboard WhatsApp Gateway Anda
 * 4. Test dengan kirim pesan ke nomor WhatsApp bisnis Anda
 * 
 * SUPPORTED PROVIDERS:
 * - Fonnte (https://fonnte.com)
 * - Wablas (https://wablas.com)
 * - WooWA (https://woowa.id)
 */

// Disable error display untuk production
error_reporting(0);
ini_set('display_errors', 0);

// Include config
include "../config/koneksi.php";
include "config_whatsapp.php";

// Log file
$log_file = __DIR__ . '/logs/webhook_log.txt';
$log_dir = dirname($log_file);

// Create log directory if not exists
if(!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Function untuk log webhook activity
function logWebhook($message, $data = null) {
    global $log_file;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}";
    
    if($data) {
        $log_entry .= "\nData: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    
    $log_entry .= "\n" . str_repeat('-', 80) . "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Get raw POST data
$raw_data = file_get_contents('php://input');
logWebhook('Webhook received', ['raw' => $raw_data]);

// Parse data based on content type
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
$webhook_data = [];

if(strpos($content_type, 'application/json') !== false) {
    // JSON data
    $webhook_data = json_decode($raw_data, true);
} else {
    // Form data
    $webhook_data = $_POST;
}

logWebhook('Parsed webhook data', $webhook_data);

// Detect provider
$provider = detectProvider($webhook_data);
logWebhook('Detected provider: ' . $provider);

// Process webhook based on provider
switch($provider) {
    case 'fonnte':
        processFonnteWebhook($webhook_data);
        break;
        
    case 'wablas':
        processWablasWebhook($webhook_data);
        break;
        
    case 'woowa':
        processWoowaWebhook($webhook_data);
        break;
        
    default:
        logWebhook('Unknown provider', $webhook_data);
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown provider']);
        exit;
}

// Send success response
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);

// =====================================================
// PROVIDER DETECTION
// =====================================================

function detectProvider($data) {
    // Fonnte detection
    if(isset($data['device']) || isset($data['sender'])) {
        return 'fonnte';
    }
    
    // Wablas detection
    if(isset($data['messageId']) || isset($data['pushname'])) {
        return 'wablas';
    }
    
    // WooWA detection
    if(isset($data['instance_id']) || isset($data['message_id'])) {
        return 'woowa';
    }
    
    return 'unknown';
}

// =====================================================
// FONNTE WEBHOOK HANDLER
// =====================================================

function processFonnteWebhook($data) {
    global $koneksi;
    
    logWebhook('Processing Fonnte webhook', $data);
    
    // Extract data
    $device = $data['device'] ?? '';
    $sender = $data['sender'] ?? '';
    $message = $data['message'] ?? '';
    $member_name = $data['member_name'] ?? '';
    
    // Clean phone number
    $phone = cleanPhoneNumber($sender);
    
    // Check if this is a delivery status
    if(isset($data['status'])) {
        handleDeliveryStatus($data);
        return;
    }
    
    // Check if this is an incoming message
    if(!empty($message)) {
        handleIncomingMessage($phone, $message, $member_name);
    }
}

// =====================================================
// WABLAS WEBHOOK HANDLER
// =====================================================

function processWablasWebhook($data) {
    global $koneksi;
    
    logWebhook('Processing Wablas webhook', $data);
    
    // Extract data
    $phone = $data['phone'] ?? '';
    $message = $data['message'] ?? '';
    $pushname = $data['pushname'] ?? '';
    
    // Clean phone number
    $phone = cleanPhoneNumber($phone);
    
    // Handle incoming message
    if(!empty($message)) {
        handleIncomingMessage($phone, $message, $pushname);
    }
}

// =====================================================
// WOOWA WEBHOOK HANDLER
// =====================================================

function processWoowaWebhook($data) {
    global $koneksi;
    
    logWebhook('Processing WooWA webhook', $data);
    
    // Extract data
    $phone = $data['from'] ?? '';
    $message = $data['body'] ?? '';
    $name = $data['name'] ?? '';
    
    // Clean phone number
    $phone = cleanPhoneNumber($phone);
    
    // Handle incoming message
    if(!empty($message)) {
        handleIncomingMessage($phone, $message, $name);
    }
}

// =====================================================
// MESSAGE HANDLERS
// =====================================================

function handleIncomingMessage($phone, $message, $name) {
    global $koneksi;
    
    logWebhook('Incoming message', [
        'phone' => $phone,
        'message' => $message,
        'name' => $name
    ]);
    
    // Find customer by phone number
    $query = "SELECT nopelanggan, namapelanggan 
              FROM tblpelanggan 
              WHERE telephone = '$phone' OR notlp = '$phone'
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $customer = mysqli_fetch_array($result);
        
        // Process auto-reply based on message content
        $reply = generateAutoReply($message, $customer);
        
        if($reply) {
            // Send auto-reply
            sendAutoReply($phone, $reply);
        }
    } else {
        // Unknown customer - send welcome message
        $reply = "Halo! Terima kasih telah menghubungi Fit Motor. Untuk informasi lebih lanjut, silakan hubungi kami di jam kerja. 🏍️";
        sendAutoReply($phone, $reply);
    }
    
    // Save incoming message to database (optional)
    saveIncomingMessage($phone, $message, $name);
}

function handleDeliveryStatus($data) {
    logWebhook('Delivery status update', $data);
    
    // Update message status in database
    $status = $data['status'] ?? '';
    $message_id = $data['id'] ?? '';
    
    // You can update your message log here
    // Example: UPDATE message_log SET status = '$status' WHERE message_id = '$message_id'
}

// =====================================================
// AUTO-REPLY LOGIC
// =====================================================

function generateAutoReply($message, $customer) {
    global $koneksi;
    
    $message_lower = strtolower($message);
    $no_pelanggan = $customer['nopelanggan'];
    $nama = $customer['namapelanggan'];
    
    // Check for specific keywords
    
    // 1. Cek status service
    if(strpos($message_lower, 'status') !== false || strpos($message_lower, 'service') !== false) {
        return getServiceStatus($no_pelanggan, $nama);
    }
    
    // 2. Cek member status
    if(strpos($message_lower, 'member') !== false || strpos($message_lower, 'poin') !== false) {
        return getMemberStatus($no_pelanggan, $nama);
    }
    
    // 3. Info harga
    if(strpos($message_lower, 'harga') !== false || strpos($message_lower, 'biaya') !== false) {
        return "Halo {$nama}! Untuk informasi harga service, silakan hubungi kami di 0812-3456-7890 atau kunjungi bengkel kami. Terima kasih! 🏍️";
    }
    
    // 4. Jam operasional
    if(strpos($message_lower, 'jam') !== false || strpos($message_lower, 'buka') !== false) {
        return "Jam Operasional Fit Motor:\nSenin - Sabtu: 08.00 - 17.00\nMinggu: Tutup\n\nAlamat: Jl. Raya Adiwerna, Tegal\nTelp: 0812-3456-7890 🏍️";
    }
    
    // 5. Booking service
    if(strpos($message_lower, 'booking') !== false || strpos($message_lower, 'reservasi') !== false) {
        return "Halo {$nama}! Untuk booking service, silakan hubungi kami di 0812-3456-7890 atau datang langsung ke bengkel. Kami siap melayani Anda! 🏍️";
    }
    
    // Default: No auto-reply
    return null;
}

function getServiceStatus($no_pelanggan, $nama) {
    global $koneksi;
    
    // Get latest service
    $query = "SELECT no_service, tanggal, status_servis, total_akhir
              FROM tblservice
              WHERE no_pelanggan = '$no_pelanggan'
              ORDER BY tanggal DESC
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $service = mysqli_fetch_array($result);
        
        $no_service = $service['no_service'];
        $tanggal = date('d/m/Y', strtotime($service['tanggal']));
        $status = $service['status_servis'];
        $total = number_format($service['total_akhir'], 0, ',', '.');
        
        $status_text = '';
        switch($status) {
            case 'datang': $status_text = 'Baru Datang'; break;
            case 'diproses': $status_text = 'Sedang Diproses'; break;
            case 'selesai': $status_text = 'Selesai Dikerjakan'; break;
            case 'bayar': $status_text = 'Sudah Dibayar'; break;
            default: $status_text = ucfirst($status);
        }
        
        $reply = "Halo {$nama}! 🏍️\n\n";
        $reply .= "Status Service Terakhir:\n";
        $reply .= "• No. Service: {$no_service}\n";
        $reply .= "• Tanggal: {$tanggal}\n";
        $reply .= "• Status: {$status_text}\n";
        
        if($status == 'bayar') {
            $reply .= "• Total: Rp {$total}\n\n";
            $reply .= "Service Anda sudah selesai dan dibayar. Terima kasih! 🙏";
        } else {
            $reply .= "\nUntuk info lebih lanjut, hubungi kami di 0812-3456-7890";
        }
        
        return $reply;
    }
    
    return "Halo {$nama}! Kami belum menemukan riwayat service Anda. Silakan hubungi kami di 0812-3456-7890 untuk informasi lebih lanjut. 🏍️";
}

function getMemberStatus($no_pelanggan, $nama) {
    global $koneksi;
    
    // Get member status
    $query = "SELECT status_member, total_nominal, total_transaksi
              FROM statistik_pelanggan
              WHERE no_pelanggan = '$no_pelanggan'";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $member = mysqli_fetch_array($result);
        
        $status = $member['status_member'];
        $total = number_format($member['total_nominal'], 0, ',', '.');
        $transaksi = $member['total_transaksi'];
        
        $icon = '';
        switch($status) {
            case 'Bronze': $icon = '🥉'; break;
            case 'Silver': $icon = '🥈'; break;
            case 'Gold': $icon = '🥇'; break;
            case 'Platinum': $icon = '💎'; break;
        }
        
        $reply = "Halo {$nama}! 🏍️\n\n";
        $reply .= "Status Member Anda:\n";
        $reply .= "• Level: {$icon} {$status}\n";
        $reply .= "• Total Transaksi: {$transaksi}x\n";
        $reply .= "• Total Nominal: Rp {$total}\n\n";
        
        // Add benefits
        $reply .= "Benefit Member {$status}:\n";
        switch($status) {
            case 'Silver':
                $reply .= "• Diskon 10%\n• Prioritas antrian";
                break;
            case 'Gold':
                $reply .= "• Diskon 15%\n• Prioritas antrian\n• Gratis cuci motor";
                break;
            case 'Platinum':
                $reply .= "• Diskon 20%\n• Prioritas VIP\n• Gratis cuci & oli\n• Jemput antar gratis";
                break;
            default:
                $reply .= "• Akses semua layanan";
        }
        
        return $reply;
    }
    
    return "Halo {$nama}! Status member Anda: 🥉 Bronze. Yuk tingkatkan level dengan service rutin di Fit Motor! 🏍️";
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function cleanPhoneNumber($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert 08xx to 628xx
    if(substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Add 62 if not exists
    if(substr($phone, 0, 2) != '62') {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

function sendAutoReply($phone, $message) {
    global $koneksi;
    
    logWebhook('Sending auto-reply', [
        'phone' => $phone,
        'message' => $message
    ]);
    
    // Include WhatsApp class
    include_once "class_whatsapp_automation.php";
    
    // Get API config
    $api_key = defined('WA_API_KEY') ? WA_API_KEY : '';
    $api_url = defined('WA_API_URL') ? WA_API_URL : '';
    
    if(empty($api_key) || !defined('WA_API_ENABLED') || !WA_API_ENABLED) {
        logWebhook('Auto-reply skipped: API not configured');
        return false;
    }
    
    // Send message
    $wa = new WhatsAppAutomation($koneksi, $api_key, $api_url);
    
    // Use internal method to send
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $phone,
            'message' => $message,
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $api_key
        ),
    ));
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    logWebhook('Auto-reply sent', [
        'http_code' => $http_code,
        'response' => $response
    ]);
    
    return $http_code == 200;
}

function saveIncomingMessage($phone, $message, $name) {
    global $koneksi;
    
    // Optional: Save to database for tracking
    // You can create a table: tbl_whatsapp_messages
    
    $phone = mysqli_real_escape_string($koneksi, $phone);
    $message = mysqli_real_escape_string($koneksi, $message);
    $name = mysqli_real_escape_string($koneksi, $name);
    
    // Example query (create table first):
    /*
    $query = "INSERT INTO tbl_whatsapp_messages 
              (phone, message, sender_name, direction, created_at) 
              VALUES 
              ('$phone', '$message', '$name', 'incoming', NOW())";
    
    mysqli_query($koneksi, $query);
    */
    
    logWebhook('Message saved to database', [
        'phone' => $phone,
        'message' => substr($message, 0, 50) . '...'
    ]);
}

?>
