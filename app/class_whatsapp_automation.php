<?php
/**
 * CLASS WHATSAPP AUTOMATION
 * File: class_whatsapp_automation.php
 * Deskripsi: Class untuk otomasi pengiriman pesan WhatsApp ke pelanggan
 * Dibuat: 2 November 2025
 * 
 * CARA PENGGUNAAN:
 * 1. Setelah trigger update statistik pelanggan
 * 2. Panggil class ini untuk kirim pesan otomatis
 * 3. Bisa diintegrasikan dengan WhatsApp API (Fonnte, Wablas, dll)
 */

class WhatsAppAutomation {
    
    private $koneksi;
    private $api_key;
    private $api_url;
    
    /**
     * Constructor
     * @param mysqli $koneksi - Koneksi database
     * @param string $api_key - API Key WhatsApp Gateway (opsional)
     * @param string $api_url - URL WhatsApp Gateway API (opsional)
     */
    public function __construct($koneksi, $api_key = '', $api_url = '') {
        $this->koneksi = $koneksi;
        $this->api_key = $api_key;
        $this->api_url = $api_url;
    }
    
    /**
     * Kirim ucapan terima kasih setelah transaksi
     * @param string $no_service - Nomor service
     * @return array - Status pengiriman
     */
    public function sendTerimaKasih($no_service) {
        // Get data service
        $query = "SELECT 
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
                WHERE s.no_service = '$no_service'";
        
        $result = mysqli_query($this->koneksi, $query);
        $data = mysqli_fetch_array($result);
        
        if(!$data) {
            return ['success' => false, 'message' => 'Data service tidak ditemukan'];
        }
        
        // Tentukan nomor WhatsApp
        $phone = $this->cleanPhoneNumber($data['telephone'] ?: $data['notlp']);
        
        if(empty($phone)) {
            return ['success' => false, 'message' => 'Nomor telepon tidak valid'];
        }
        
        // Generate pesan
        $message = $this->generatePesanTerimaKasih($data);
        
        // Kirim pesan (jika API tersedia)
        if(!empty($this->api_key) && !empty($this->api_url)) {
            return $this->sendViaAPI($phone, $message);
        } else {
            // Return WhatsApp Web link jika tidak ada API
            $wa_link = $this->generateWhatsAppLink($phone, $message);
            return [
                'success' => true,
                'message' => 'Link WhatsApp berhasil dibuat',
                'wa_link' => $wa_link,
                'phone' => $phone,
                'text' => $message
            ];
        }
    }
    
    /**
     * Kirim reminder untuk pelanggan yang lama tidak datang
     * @param string $no_pelanggan - Nomor pelanggan
     * @return array - Status pengiriman
     */
    public function sendReminderFollowUp($no_pelanggan) {
        // Get data pelanggan
        $query = "SELECT * FROM view_pelanggan_follow_up WHERE nopelanggan = '$no_pelanggan'";
        $result = mysqli_query($this->koneksi, $query);
        $data = mysqli_fetch_array($result);
        
        if(!$data) {
            return ['success' => false, 'message' => 'Data pelanggan tidak ditemukan'];
        }
        
        // Tentukan nomor WhatsApp
        $phone = $this->cleanPhoneNumber($data['telephone'] ?: $data['notlp']);
        
        if(empty($phone)) {
            return ['success' => false, 'message' => 'Nomor telepon tidak valid'];
        }
        
        // Generate pesan dari template
        $message = $data['template_pesan_wa'];
        
        // Kirim pesan
        if(!empty($this->api_key) && !empty($this->api_url)) {
            return $this->sendViaAPI($phone, $message);
        } else {
            $wa_link = $this->generateWhatsAppLink($phone, $message);
            return [
                'success' => true,
                'message' => 'Link WhatsApp berhasil dibuat',
                'wa_link' => $wa_link,
                'phone' => $phone,
                'text' => $message
            ];
        }
    }
    
    /**
     * Generate pesan terima kasih
     * @param array $data - Data service
     * @return string - Pesan WhatsApp
     */
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
        // F1-A: masa garansi dinamis per tier member (jawaban A3, 2026-07-04),
        // bukan flat 30 hari — sebelumnya menyesatkan customer.
        $masa_garansi = 7;
        $member_esc = mysqli_real_escape_string($this->koneksi, $member);
        $q_mg = mysqli_query($this->koneksi, "SELECT masa_garansi_hari FROM tbmaster_kategori_member WHERE status_member='$member_esc' LIMIT 1");
        if ($q_mg && ($row_mg = mysqli_fetch_assoc($q_mg)) && isset($row_mg['masa_garansi_hari'])) {
            $masa_garansi = (int)$row_mg['masa_garansi_hari'];
        }

        $message .= "✅ *Garansi Service:*\n";
        $message .= "Service Anda bergaransi {$masa_garansi} hari untuk tier member {$member}\n\n";
        $message .= "📅 *Reminder Service Berikutnya:*\n";
        $message .= "Estimasi: {$estimasi}\n";
        $message .= "Kami akan mengingatkan Anda saat waktunya service!\n\n";
        
        // Tambahan benefit member
        switch($member) {
            case 'Silver':
                $message .= "🎁 *Benefit Member Silver:*\n";
                $message .= "• Diskon 10% untuk service\n";
                $message .= "• Prioritas antrian\n\n";
                break;
            case 'Gold':
                $message .= "🎁 *Benefit Member Gold:*\n";
                $message .= "• Diskon 15% untuk service\n";
                $message .= "• Prioritas antrian\n";
                $message .= "• Gratis cuci motor\n\n";
                break;
            case 'Platinum':
                $message .= "🎁 *Benefit Member Platinum:*\n";
                $message .= "• Diskon 20% untuk service\n";
                $message .= "• Prioritas antrian VIP\n";
                $message .= "• Gratis cuci motor & oli\n";
                $message .= "• Jemput antar gratis\n\n";
                break;
        }
        
        $message .= "Jika ada keluhan atau pertanyaan, jangan ragu untuk menghubungi kami!\n\n";
        $message .= "Salam hangat,\n";
        $message .= "*Tim Fit Motor* 🔧";
        
        return $message;
    }
    
    /**
     * Clean phone number (format Indonesia)
     * @param string $phone - Nomor telepon
     * @return string - Nomor telepon yang sudah dibersihkan
     */
    private function cleanPhoneNumber($phone) {
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
    
    /**
     * Generate WhatsApp Web link
     * @param string $phone - Nomor telepon
     * @param string $message - Pesan
     * @return string - WhatsApp link
     */
    private function generateWhatsAppLink($phone, $message) {
        $encoded_message = urlencode($message);
        return "https://wa.me/{$phone}?text={$encoded_message}";
    }
    
    /**
     * Kirim pesan via WhatsApp API
     * @param string $phone - Nomor telepon
     * @param string $message - Pesan
     * @return array - Status pengiriman
     */
    private function sendViaAPI($phone, $message) {
        // Load config
        require_once 'config_whatsapp.php';
        
        $curl = curl_init();
        
        // Tentukan format request berdasarkan provider
        if(WA_PROVIDER == 'techarea') {
            // TechArea Gateway - Support POST JSON dan GET
            $postData = json_encode([
                'api_key' => WA_API_KEY,
                'sender' => WA_SENDER_NUMBER,
                'number' => $phone,
                'message' => $message
            ]);
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        } 
        elseif(WA_PROVIDER == 'fonnte') {
            // Fonnte API
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $phone,
                    'message' => $message,
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $this->api_key
                ),
            ));
        }
        elseif(WA_PROVIDER == 'wablas') {
            // Wablas API
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'phone' => $phone,
                    'message' => $message,
                ]),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $this->api_key,
                    'Content-Type: application/json'
                ),
            ));
        }
        else {
            // Default/Generic API
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $phone,
                    'message' => $message,
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $this->api_key
                ),
            ));
        }
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        
        curl_close($curl);
        
        // Log activity
        if(function_exists('logWhatsAppActivity')) {
            logWhatsAppActivity('AUTO', $phone, $http_code == 200 ? 'success' : 'failed', 
                               $http_code == 200 ? 'Sent' : $curl_error);
        }
        
        if($http_code == 200) {
            return [
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'response' => json_decode($response, true),
                'phone' => $phone
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . ($curl_error ?: 'HTTP ' . $http_code),
                'response' => json_decode($response, true),
                'phone' => $phone
            ];
        }
    }
    
    /**
     * Kirim broadcast ke semua pelanggan yang perlu follow up
     * @return array - Hasil broadcast
     */
    public function broadcastFollowUp() {
        $query = "SELECT * FROM view_pelanggan_follow_up WHERE hari_tidak_datang > 30 LIMIT 50";
        $result = mysqli_query($this->koneksi, $query);
        
        $sent = 0;
        $failed = 0;
        $results = [];
        
        while($row = mysqli_fetch_array($result)) {
            $send_result = $this->sendReminderFollowUp($row['nopelanggan']);
            
            if($send_result['success']) {
                $sent++;
            } else {
                $failed++;
            }
            
            $results[] = [
                'nopelanggan' => $row['nopelanggan'],
                'nama' => $row['namapelanggan'],
                'status' => $send_result['success'] ? 'sent' : 'failed',
                'message' => $send_result['message']
            ];
            
            // Delay untuk menghindari spam (jika menggunakan API)
            if(!empty($this->api_key)) {
                sleep(2); // 2 detik delay
            }
        }
        
        return [
            'success' => true,
            'total' => $sent + $failed,
            'sent' => $sent,
            'failed' => $failed,
            'details' => $results
        ];
    }
}

/**
 * CONTOH PENGGUNAAN:
 * 
 * // 1. Inisialisasi class
 * $wa = new WhatsAppAutomation($koneksi, 'YOUR_API_KEY', 'https://api.fonnte.com/send');
 * 
 * // 2. Kirim terima kasih setelah transaksi
 * $result = $wa->sendTerimaKasih('SV25000000001');
 * if($result['success']) {
 *     echo "Pesan berhasil dikirim!";
 * }
 * 
 * // 3. Kirim reminder follow up
 * $result = $wa->sendReminderFollowUp('AD 1234 AB');
 * 
 * // 4. Broadcast ke semua pelanggan yang perlu follow up
 * $result = $wa->broadcastFollowUp();
 * echo "Terkirim: " . $result['sent'] . ", Gagal: " . $result['failed'];
 */
?>
