<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
$no_service = $_GET['snosrv'] ?? '';

if (empty($no_service)) {
    echo "<script>alert('Nomor service tidak ditemukan!'); history.back();</script>";
    exit;
}

require_once "../config/koneksi.php";

// Get service data
$stmt = mysqli_prepare($koneksi, "SELECT s.*, p.namapelanggan, p.alamat, p.telephone,
                                 v.merek, v.tipe, v.warna, v.no_rangka, v.no_mesin,
                                 c.nama_cabang, c.alamat as alamat_cabang, c.telepon as telepon_cabang
                                 FROM tblservice s
                                 LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                                 LEFT JOIN view_cari_kendaraan v ON s.no_polisi = v.nopolisi
                                 LEFT JOIN tbcabang c ON s.kd_cabang = c.kode_cabang
                                 WHERE s.no_service = ?");
mysqli_stmt_bind_param($stmt, "s", $no_service);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$service_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$service_data) {
    echo "<script>alert('Data service tidak ditemukan!'); history.back();</script>";
    exit;
}

// Get user data
$stmt = mysqli_prepare($koneksi, "SELECT nama_user FROM tbuser WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

// Set headers for download
$filename = "SP_Ambil_Motor_" . $no_service . "_" . date('Ymd') . ".html";
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Ambil Motor - <?php echo htmlspecialchars($no_service); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 11px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .document-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            color: #e74c3c;
        }
        
        .service-number {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            color: #2980b9;
        }
        
        .content-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            background-color: #3498db;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .info-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .info-table td.label {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 30%;
            color: #2c3e50;
        }
        
        .info-table td.value {
            background-color: #fff;
            width: 70%;
        }
        
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #333;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 60px;
            color: #2c3e50;
        }
        
        .signature-name {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin: 20px 0;
            border-radius: 3px;
        }
        
        .warning-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 10px;
        }
        
        .warning-text {
            color: #856404;
            font-size: 11px;
            line-height: 1.5;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .status-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        @media print {
            body { margin: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">
            <?php echo htmlspecialchars($service_data['nama_cabang'] ?? 'BENGKEL MOTOR'); ?>
        </div>
        <div class="company-address">
            <?php echo htmlspecialchars($service_data['alamat_cabang'] ?? 'Alamat Bengkel'); ?><br>
            Telp: <?php echo htmlspecialchars($service_data['telepon_cabang'] ?? '021-xxxx-xxxx'); ?>
        </div>
        <div class="document-title">
            SURAT PERINTAH AMBIL MOTOR
        </div>
        <div class="service-number">
            No. Service: <?php echo htmlspecialchars($no_service); ?>
            <span class="status-badge">JEMPUT ANTAR</span>
        </div>
    </div>

    <div class="content-section">
        <div class="section-title">INFORMASI PELANGGAN</div>
        <table class="info-table">
            <tr>
                <td class="label">Nama Pelanggan</td>
                <td class="value"><?php echo htmlspecialchars($service_data['namapelanggan'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="value"><?php echo htmlspecialchars($service_data['alamat'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">Telepon</td>
                <td class="value"><?php echo htmlspecialchars($service_data['telephone'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">Kode Pelanggan</td>
                <td class="value"><?php echo htmlspecialchars($service_data['no_pelanggan'] ?? '-'); ?></td>
            </tr>
        </table>
    </div>

    <div class="content-section">
        <div class="section-title">INFORMASI KENDARAAN</div>
        <table class="info-table">
            <tr>
                <td class="label">Nomor Polisi</td>
                <td class="value"><strong><?php echo htmlspecialchars($service_data['no_polisi'] ?? '-'); ?></strong></td>
            </tr>
            <tr>
                <td class="label">Merek / Tipe</td>
                <td class="value"><?php echo htmlspecialchars(($service_data['merek'] ?? '') . ' ' . ($service_data['tipe'] ?? '')); ?></td>
            </tr>
            <tr>
                <td class="label">Warna</td>
                <td class="value"><?php echo htmlspecialchars($service_data['warna'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">No. Rangka</td>
                <td class="value"><?php echo htmlspecialchars($service_data['no_rangka'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">No. Mesin</td>
                <td class="value"><?php echo htmlspecialchars($service_data['no_mesin'] ?? '-'); ?></td>
            </tr>
        </table>
    </div>

    <div class="content-section">
        <div class="section-title">INFORMASI SERVICE</div>
        <table class="info-table">
            <tr>
                <td class="label">Tanggal Jemput</td>
                <td class="value"><?php echo date('d F Y', strtotime($service_data['tanggal'])); ?></td>
            </tr>
            <tr>
                <td class="label">Jam Jemput</td>
                <td class="value"><?php echo htmlspecialchars($service_data['jam'] ?? '-'); ?> WIB</td>
            </tr>
            <tr>
                <td class="label">Keterangan</td>
                <td class="value"><?php echo nl2br(htmlspecialchars($service_data['keterangan'] ?? 'Tidak ada keterangan khusus')); ?></td>
            </tr>
            <tr>
                <td class="label">Petugas Input</td>
                <td class="value"><?php echo htmlspecialchars($user_data['nama_user'] ?? '-'); ?></td>
            </tr>
        </table>
    </div>

    <div class="warning-box">
        <div class="warning-title">⚠️ PENTING - INSTRUKSI PENJEMPUTAN</div>
        <div class="warning-text">
            <strong>SEBELUM MENGAMBIL MOTOR:</strong><br>
            1. Konfirmasi ulang dengan pelanggan sebelum berangkat<br>
            2. Bawa surat perintah ini dan identitas perusahaan<br>
            3. Siapkan peralatan pengamanan motor (tali/rantai)<br>
            4. Pastikan motor dalam kondisi aman untuk dibawa<br><br>
            
            <strong>SAAT DI LOKASI:</strong><br>
            1. Periksa kondisi fisik motor dan dokumentasikan<br>
            2. Catat keluhan detail dari pelanggan<br>
            3. Ambil foto kondisi motor sebelum dibawa<br>
            4. Berikan tanda terima kepada pelanggan<br><br>
            
            <strong>SETELAH PENJEMPUTAN:</strong><br>
            1. Segera bawa motor ke bengkel<br>
            2. Serahkan kepada mekanik yang bertugas<br>
            3. Update status di sistem<br>
            4. Laporkan kepada supervisor
        </div>
    </div>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-title">PELANGGAN</div>
                    <div class="signature-name">
                        ( <?php echo htmlspecialchars($service_data['namapelanggan'] ?? '________________'); ?> )
                    </div>
                </td>
                <td>
                    <div class="signature-title">PETUGAS JEMPUT</div>
                    <div class="signature-name">
                        ( ________________ )
                    </div>
                </td>
                <td>
                    <div class="signature-title">SUPERVISOR</div>
                    <div class="signature-name">
                        ( ________________ )
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>
            <strong>Dokumen ini dicetak otomatis pada:</strong> 
            <?php echo date('d F Y, H:i:s'); ?> WIB<br>
            <em>Harap simpan dokumen ini sebagai bukti penjemputan motor</em>
        </p>
    </div>
</body>
</html>