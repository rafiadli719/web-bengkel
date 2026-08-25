<?php
/**
 * KIRIM INVOICE KE WHATSAPP
 * File ini mengirim invoice servis ke WhatsApp pelanggan dalam format PDF
 */

session_start();
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

require_once '../config/koneksi.php';
require_once 'config_whatsapp.php';
require_once 'class_whatsapp_automation.php';

// Set header JSON
header('Content-Type: application/json');

// Get no_service
$no_service = isset($_GET['no_service']) ? mysqli_real_escape_string($koneksi, $_GET['no_service']) : '';

if(empty($no_service)) {
    echo json_encode(['success' => false, 'message' => 'Nomor service tidak ditemukan']);
    exit;
}

try {
    // Get service data
    $query = "SELECT 
                s.*,
                p.namapelanggan,
                p.telephone,
                p.nopelanggan
              FROM tblservice s
              LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
              WHERE s.no_service = '$no_service'";
    
    $result = mysqli_query($koneksi, $query);
    
    if(!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Data service tidak ditemukan']);
        exit;
    }
    
    $data = mysqli_fetch_assoc($result);
    $telephone = $data['telephone'];
    $namapelanggan = $data['namapelanggan'];
    $total_akhir = $data['total_akhir'];
    
    // Validasi nomor telepon
    if(empty($telephone)) {
        echo json_encode(['success' => false, 'message' => 'Nomor telepon pelanggan tidak ada']);
        exit;
    }
    
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $telephone);
    
    // Convert to 62 format
    if(substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    } elseif(substr($phone, 0, 2) != '62') {
        $phone = '62' . $phone;
    }
    
    // Cek WA enabled
    if(!defined('WA_API_ENABLED') || !WA_API_ENABLED) {
        echo json_encode(['success' => false, 'message' => 'WhatsApp API tidak aktif']);
        exit;
    }
    
    // Generate PDF URL - dibangun dinamis dari host+path request ini sendiri,
    // sebelumnya hardcode 'http://localhost/.../_admincab/...' (folder gak ada,
    // host gak reachable dari server WA gateway di produksi).
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    $pdf_url = $base_url . '/servis-print-pdf.php?no_service=' . urlencode($no_service);
    
    // Buat pesan
    $message = "🧾 *INVOICE SERVIS*\n\n";
    $message .= "Yth. Bapak/Ibu *" . $namapelanggan . "*\n\n";
    $message .= "Terima kasih telah menggunakan layanan kami.\n\n";
    $message .= "📋 No. Service: *" . $no_service . "*\n";
    $message .= "💰 Total: *Rp " . number_format($total_akhir, 0, ',', '.') . "*\n\n";
    $message .= "Invoice terlampir dalam bentuk PDF.\n\n";
    $message .= "Jika ada pertanyaan, silakan hubungi kami.\n\n";
    $message .= "Terima kasih! 🙏";
    
    // Kirim via WhatsApp API
    $api_url = WA_API_URL;
    $api_key = WA_API_KEY;
    
    // Prepare data
    $postData = [
        'api_key' => $api_key,
        'sender' => WA_SENDER_NUMBER,
        'number' => $phone,
        'message' => $message,
        'file_url' => $pdf_url  // URL to PDF
    ];
    
    // Send via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log activity
    if(function_exists('logWhatsAppActivity')) {
        if($http_code == 200) {
            logWhatsAppActivity($no_service, $phone, 'sent_invoice', 'Invoice PDF sent via WhatsApp');
        } else {
            logWhatsAppActivity($no_service, $phone, 'failed_invoice', 'Failed to send invoice: ' . $curl_error);
        }
    }
    
    // Response
    if($http_code == 200) {
        $response_data = json_decode($response, true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice berhasil dikirim',
            'phone' => $phone,
            'status' => 'sent',
            'response' => $response_data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengirim invoice: HTTP ' . $http_code,
            'error' => $curl_error
        ]);
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
