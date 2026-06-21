<?php
/**
 * File: statistik_pelanggan_send_wa.php
 * Deskripsi: Handler untuk pengiriman WhatsApp ke pelanggan
 */

session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "class_whatsapp_automation.php";

// Get parameter
$no_pelanggan = $_GET['nopelanggan'] ?? '';

if(empty($no_pelanggan)) {
    echo "<script>alert('Nomor pelanggan tidak valid!'); window.history.back();</script>";
    exit;
}

// Get data pelanggan
$query = "SELECT * FROM view_pelanggan_follow_up WHERE nopelanggan = '$no_pelanggan'";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_array($result);

if(!$data) {
    echo "<script>alert('Data pelanggan tidak ditemukan!'); window.history.back();</script>";
    exit;
}

// Inisialisasi WhatsApp Automation
// Jika tidak ada API, akan generate WhatsApp Web link
$wa = new WhatsAppAutomation($koneksi);

// Kirim reminder
$send_result = $wa->sendReminderFollowUp($no_pelanggan);

// Clean phone number untuk WhatsApp
function cleanPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if(substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    if(substr($phone, 0, 2) != '62') {
        $phone = '62' . $phone;
    }
    return $phone;
}

// Prefer no_wa (sudah format 628xx dari migrasi) daripada telephone
$rawWa = $data['no_wa'] ?? '';
if (!empty($rawWa)) {
    $phone = preg_replace('/[^0-9]/', '', $rawWa);
} else {
    $phone = cleanPhone($data['telephone'] ?: $data['notlp']);
}
$message = $data['template_pesan_wa'];
$wa_link = "https://wa.me/{$phone}?text=" . urlencode($message);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Kirim WhatsApp - Fit Motor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
</head>

<body class="no-skin">
    <div class="main-container" style="padding: 50px;">
        <div class="row">
            <div class="col-xs-12 col-sm-8 col-sm-offset-2">
                <div class="widget-box">
                    <div class="widget-header widget-header-flat widget-header-large">
                        <h3 class="widget-title">
                            <i class="ace-icon fa fa-whatsapp green"></i>
                            Kirim Pesan WhatsApp
                        </h3>
                    </div>
                    
                    <div class="widget-body">
                        <div class="widget-main">
                            <div class="alert alert-info">
                                <i class="ace-icon fa fa-info-circle"></i>
                                <strong>Pelanggan:</strong> <?php echo $data['namapelanggan']; ?><br>
                                <strong>No. Polisi:</strong> <?php echo $data['nopelanggan']; ?><br>
                                <strong>Telepon:</strong> <?php echo $phone; ?><br>
                                <strong>Status Member:</strong> <span class="label label-success"><?php echo $data['status_member']; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fa fa-comment"></i> Preview Pesan:</label>
                                <textarea class="form-control" rows="12" readonly><?php echo $message; ?></textarea>
                            </div>
                            
                            <div class="space-10"></div>
                            
                            <div class="text-center">
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success btn-lg">
                                    <i class="fa fa-whatsapp"></i> Buka WhatsApp & Kirim Pesan
                                </a>
                                <a href="statistik_pelanggan_dashboard.php" class="btn btn-default btn-lg">
                                    <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
                                </a>
                            </div>
                            
                            <div class="space-10"></div>
                            
                            <div class="alert alert-warning">
                                <i class="ace-icon fa fa-lightbulb-o"></i>
                                <strong>Tips:</strong> Setelah WhatsApp terbuka, pesan sudah otomatis terisi. 
                                Anda tinggal klik tombol kirim di WhatsApp.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    
    <script>
    // Auto redirect ke WhatsApp setelah 2 detik
    setTimeout(function() {
        if(confirm('Buka WhatsApp sekarang?')) {
            window.open('<?php echo $wa_link; ?>', '_blank');
        }
    }, 2000);
    </script>
</body>
</html>
