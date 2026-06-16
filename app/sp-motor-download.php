<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
$no_service = $_GET['snoserv'] ?? '';

if (empty($no_service)) {
    echo "<script>alert('Nomor service tidak ditemukan!'); history.back();</script>";
    exit;
}

require_once "../config/koneksi.php";

// Get service data
$query = "SELECT s.*, p.namapelanggan, p.alamat, p.telephone,
                 v.merek, v.jenis, v.warna, v.no_rangka, v.no_mesin
          FROM tblservice s
          LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
          LEFT JOIN view_cari_kendaraan v ON s.no_polisi = v.nopolisi
          WHERE s.no_service = '$no_service'";

$result = mysqli_query($koneksi, $query);
$service_data = mysqli_fetch_assoc($result);

if (!$service_data) {
    echo "<script>alert('Data service tidak ditemukan!'); history.back();</script>";
    exit;
}

// Get cabang data separately
$cabang_query = "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang = '$kd_cabang'";
$cabang_result = mysqli_query($koneksi, $cabang_query);
$cabang_data = mysqli_fetch_assoc($cabang_result);

// Get user data
$user_query = "SELECT nama_user FROM tbuser WHERE id = '$id_user'";
$user_result = mysqli_query($koneksi, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Get keluhan data
$keluhan_query = "SELECT keluhan FROM tbservis_keluhan_status WHERE no_service = '$no_service' ORDER BY id ASC";
$keluhan_result = mysqli_query($koneksi, $keluhan_query);

// Set headers for download
$filename = "SP_Motor_" . $no_service . "_" . date('Ymd') . ".html";
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Penyerahan Motor - <?php echo $no_service; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-info { text-align: center; margin-bottom: 20px; }
        .service-info { margin: 20px 0; }
        .customer-info { margin: 20px 0; }
        .vehicle-info { margin: 20px 0; }
        .keluhan-info { margin: 20px 0; }
        .footer { margin-top: 50px; }
        .signature { margin-top: 60px; }
        table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px; border: none; }
        .border-table { border: 1px solid #000; }
        .border-table td, .border-table th { border: 1px solid #000; padding: 8px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="company-info">
        <h2><?php echo htmlspecialchars($cabang_data['nama_cabang'] ?? 'Workshop Nama'); ?></h2>
        <p>Alamat Workshop (Update di master cabang)</p>
        <p>Telp: (Update di master cabang)</p>
    </div>

    <div class="header">
        <h3>SURAT PENYERAHAN MOTOR</h3>
        <p>No. Service: <strong><?php echo htmlspecialchars($no_service); ?></strong></p>
        <p>Tanggal: <?php echo date('d/m/Y', strtotime($service_data['tanggal'])); ?></p>
    </div>

    <div class="customer-info">
        <h4>INFORMASI PELANGGAN</h4>
        <table class="info-table">
            <tr>
                <td style="width: 150px;"><strong>Nama Pelanggan</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['namapelanggan']); ?></td>
            </tr>
            <tr>
                <td><strong>Alamat</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['alamat']); ?></td>
            </tr>
            <tr>
                <td><strong>Telepon</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['telephone']); ?></td>
            </tr>
        </table>
    </div>

    <div class="vehicle-info">
        <h4>INFORMASI KENDARAAN</h4>
        <table class="info-table">
            <tr>
                <td style="width: 150px;"><strong>No. Polisi</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['no_polisi']); ?></td>
            </tr>
            <tr>
                <td><strong>Merk/Jenis</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['merek'] . ' ' . $service_data['jenis']); ?></td>
            </tr>
            <tr>
                <td><strong>Warna</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['warna']); ?></td>
            </tr>
            <tr>
                <td><strong>No. Rangka</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['no_rangka']); ?></td>
            </tr>
            <tr>
                <td><strong>No. Mesin</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['no_mesin']); ?></td>
            </tr>
        </table>
    </div>

    <?php if(mysqli_num_rows($keluhan_result) > 0): ?>
    <div class="keluhan-info">
        <h4>KELUHAN YANG DITERIMA</h4>
        <table class="border-table">
            <thead>
                <tr>
                    <th style="width: 10%;">No</th>
                    <th>Keluhan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($keluhan = mysqli_fetch_assoc($keluhan_result)): 
                ?>
                <tr>
                    <td class="center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($keluhan['keluhan']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="service-info">
        <h4>INFORMASI SERVICE</h4>
        <table class="info-table">
            <tr>
                <td style="width: 150px;"><strong>Tipe Service</strong></td>
                <td>: Service Jemput</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td>: <?php echo htmlspecialchars($service_data['status_servis'] ?? 'Dalam Proses'); ?></td>
            </tr>
            <tr>
                <td><strong>KM Sekarang</strong></td>
                <td>: <?php echo number_format($service_data['km_skr'] ?? 0); ?> KM</td>
            </tr>
            <tr>
                <td><strong>KM Service Berikut</strong></td>
                <td>: <?php echo number_format($service_data['km_berikut'] ?? 0); ?> KM</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p><strong>PERNYATAAN:</strong></p>
        <p>Dengan ini saya menyerahkan kendaraan tersebut di atas untuk dilakukan service/perbaikan. 
        Saya memahami dan menyetujui syarat dan ketentuan yang berlaku.</p>
        
        <table style="width: 100%; margin-top: 50px;">
            <tr>
                <td style="width: 50%; text-align: center;">
                    <p>Penerima,</p>
                    <br><br><br>
                    <p>(_______________________)</p>
                    <p><?php echo htmlspecialchars($user_data['nama_user']); ?></p>
                </td>
                <td style="width: 50%; text-align: center;">
                    <p>Yang Menyerahkan,</p>
                    <br><br><br>
                    <p>(_______________________)</p>
                    <p><?php echo htmlspecialchars($service_data['namapelanggan']); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
        <p>Dokumen ini digenerate pada <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>
</html>