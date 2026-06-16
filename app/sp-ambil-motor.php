<?php
use Dompdf\Dompdf;
use Dompdf\Options;

include "../config/koneksi.php";
$no_service = $_GET['snosrv'] ?? '';

// Validasi parameter
if (empty($no_service)) {
    die('Error: No service number provided');
}

// Data Perusahaan ===========
$cari_kd = mysqli_query($koneksi, "SELECT * FROM tbsetting");
$tm_cari = mysqli_fetch_array($cari_kd);
$nama_perusahaan = $tm_cari['nama_perusahaan'] ?? '';
$alamat_perusahaan = $tm_cari['alamat'] ?? '';
$notlp = $tm_cari['notlp'] ?? '';
$fax = $tm_cari['fax'] ?? '';
$file_logo = $tm_cari['file_logo'] ?? '';
// ===================

// Data Transaksi Servis ==========
$cari_kd = mysqli_query($koneksi, "SELECT 
                                    DATE_FORMAT(tanggal,'%d-%b-%y') AS tanggal_serv,
                                    DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_print, 
                                    jam, no_pelanggan, no_polisi, 
                                    keterangan, foto_motor 
                                    FROM tblservice 
                                    WHERE no_service='" . mysqli_real_escape_string($koneksi, $no_service) . "'");
$tm_cari = mysqli_fetch_array($cari_kd);

if (!$tm_cari) {
    die('Error: Service data not found for service number: ' . htmlspecialchars($no_service));
}

$tanggal = $tm_cari['tanggal_serv'] ?? '';
$tanggal_print = $tm_cari['tanggal_print'] ?? '';
$jam = $tm_cari['jam'] ?? '';
$kode_pelanggan = $tm_cari['no_pelanggan'] ?? '';
$no_polisi = $tm_cari['no_polisi'] ?? '';
$ket = $tm_cari['keterangan'] ?? '';
$foto_motor = $tm_cari['foto_motor'] ?? '';

$cari_kd = mysqli_query($koneksi, "SELECT 
                                    namapelanggan, alamat, patokan, kgrup, notlp 
                                    FROM tblpelanggan 
                                    WHERE nopelanggan='" . mysqli_real_escape_string($koneksi, $kode_pelanggan) . "'");
$tm_cari = mysqli_fetch_array($cari_kd);
$namapelanggan = $tm_cari['namapelanggan'] ?? '';
$alamat_pelanggan = $tm_cari['alamat'] ?? '';
$patokan = $tm_cari['patokan'] ?? '';
$kgrup = $tm_cari['kgrup'] ?? '';
$notlp_pelanggan = $tm_cari['notlp'] ?? '';

$cari_kd = mysqli_query($koneksi, "SELECT grup, diskon FROM tblpelanggangrup 
                                    WHERE kgrup='" . mysqli_real_escape_string($koneksi, $kgrup) . "'");
$tm_cari = mysqli_fetch_array($cari_kd);
$tipepelanggan = $tm_cari['grup'] ?? 'Regular';

$cari_kd = mysqli_query($koneksi, "SELECT 
                                    pemilik, jenis, merek, warna, 
                                    no_rangka, no_mesin, tipe 
                                    FROM view_cari_kendaraan 
                                    WHERE nopolisi='" . mysqli_real_escape_string($koneksi, $no_polisi) . "'");
$tm_cari = mysqli_fetch_array($cari_kd);
$pemilik = $tm_cari['pemilik'] ?? '';
$jenis = $tm_cari['jenis'] ?? '';
$merek = $tm_cari['merek'] ?? '';
$warna = $tm_cari['warna'] ?? '';
$tipe = $tm_cari['tipe'] ?? '';
$no_rangka = $tm_cari['no_rangka'] ?? '';
$no_mesin = $tm_cari['no_mesin'] ?? '';

$query_keluhan = mysqli_query($koneksi, "SELECT keluhan FROM tbservis_keluhan 
                                        WHERE no_service='" . mysqli_real_escape_string($koneksi, $no_service) . "' 
                                        ORDER BY id ASC");

// Format current time for print timestamp
date_default_timezone_set('Asia/Jakarta');
$cetak_waktu = date('d/m/Y H:i:s');

// Error handling untuk DomPDF
try {
require_once __DIR__ . '/../vendor/autoload.php';
    // Set options untuk DomPDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('chroot', realpath(dirname(__FILE__)));

    $dompdf = new Dompdf($options);
} catch (Exception $e) {
    die('Error initializing DomPDF: ' . $e->getMessage());
}

$html = '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        html, body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        table.table, table.table td, table.table th {
            border: 1px solid black;
        }
        table.table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-section {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .company-info {
            font-size: 10px;
            margin-top: 5px;
        }
        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .info-table td {
            padding: 4px 6px;
            border: 1px solid #000;
            font-size: 11px;
        }
        .label-cell {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: right;
            width: 20%;
        }
        .value-cell {
            background-color: #fff;
        }
        .photo-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #000;
        }
        .photo-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .motor-photo {
            max-width: 300px;
            max-height: 200px;
            border: 1px solid #ccc;
        }
        .footer-info {
            margin-top: 20px;
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        .print-time {
            text-align: right;
            font-size: 9px;
            color: #666;
        }
        .signature-section {
            margin-top: 30px;
        }
        .signature-box {
            float: left;
            width: 45%;
            text-align: center;
            border: 1px solid #000;
            padding: 10px;
            margin: 5px;
            height: 80px;
        }
        .clear {
            clear: both;
        }
        sup {
            font-size: 8px;
        }
    </style>    
</head>
<body>
<div style="margin: 10pt; padding: 0; overflow: none;">
    
    <!-- Header Section -->
    <div class="header-section">
        <div class="document-title">SURAT PERINTAH PENGAMBILAN MOTOR</div>
        <div style="font-size: 14px; font-weight: bold;">BENGKEL FIT MOTOR</div>
        <div class="company-info">
            "Solusi Tepat Servis Matic"<br>
            ' . htmlspecialchars($alamat_perusahaan) . '<br>
            Pesalakan: ' . htmlspecialchars($notlp) . ' | Pacul: 0856 4287 6025 | Cik Ditiro: 0857 9927 2526
        </div>
        <div class="print-time">Cetak: ' . $cetak_waktu . '</div>
    </div>

    <!-- Service Information -->
    <table class="info-table">
        <tr>
            <td class="label-cell">Tanggal:</td>
            <td class="value-cell" style="width: 15%;">' . htmlspecialchars($tanggal) . '</td>
            <td class="label-cell" style="width: 15%;">Jam Ambil:</td>
            <td class="value-cell" style="width: 15%;">' . htmlspecialchars($jam) . '</td>
            <td class="label-cell" style="width: 15%;">No. Servis:</td>
            <td class="value-cell">' . htmlspecialchars($no_service) . '</td>
        </tr>
    </table>

    <!-- Customer Information -->
    <table class="info-table">
        <tr>
            <td class="label-cell">Nama:</td>
            <td class="value-cell" colspan="5"><strong>' . htmlspecialchars($namapelanggan) . '</strong></td>
        </tr>
        <tr>
            <td class="label-cell">Telpon:</td>
            <td class="value-cell">' . htmlspecialchars($notlp_pelanggan) . '</td>
            <td class="label-cell">No Polisi:</td>
            <td class="value-cell"><strong>' . htmlspecialchars($no_polisi) . '</strong></td>
            <td class="label-cell">Merek:</td>
            <td class="value-cell">' . htmlspecialchars($merek) . '</td>
        </tr>
        <tr>
            <td class="label-cell">Tipe:</td>
            <td class="value-cell">' . htmlspecialchars($tipe) . '</td>
            <td class="label-cell">Jenis:</td>
            <td class="value-cell">' . htmlspecialchars($jenis) . '</td>
            <td class="label-cell">Warna:</td>
            <td class="value-cell">' . htmlspecialchars($warna) . '</td>
        </tr>
    </table>

    <!-- Service Details -->
    <table class="info-table">
        <tr>
            <td class="label-cell">Pengerjaan:</td>
            <td class="value-cell">
                <strong>' . htmlspecialchars($tipepelanggan) . '</strong>
                <ol style="margin: 5px 0; padding-left: 20px;">';

$no = 1;
if ($query_keluhan && mysqli_num_rows($query_keluhan) > 0) {
    while ($row = mysqli_fetch_array($query_keluhan)) {
        $html .= "<li style='margin: 2px 0;'>" . htmlspecialchars($row['keluhan']) . "</li>";
        $no++;
    }
} else {
    $html .= "<li style='margin: 2px 0;'>Tidak ada keluhan tercatat</li>";
}

$html .= '      </ol>
            </td>
        </tr>
        <tr>
            <td class="label-cell">Alamat:</td>
            <td class="value-cell">' . htmlspecialchars($alamat_pelanggan) . '</td>
        </tr>
        <tr>
            <td class="label-cell">Patokan:</td>
            <td class="value-cell"><strong>' . htmlspecialchars($patokan) . '</strong></td>
        </tr>';

if (!empty($ket)) {
    $html .= '<tr>
                <td class="label-cell">Keterangan:</td>
                <td class="value-cell"><strong>' . htmlspecialchars($ket) . '</strong></td>
            </tr>';
}

$html .= '</table>';

// Photo section if photo exists
if (!empty($foto_motor) && file_exists('../' . $foto_motor)) {
    try {
        $imageData = base64_encode(file_get_contents('../' . $foto_motor));
        $imageType = pathinfo('../' . $foto_motor, PATHINFO_EXTENSION);
        $imageSrc = 'data:image/' . $imageType . ';base64,' . $imageData;

        $html .= '<div class="photo-section">
                    <div class="photo-title">FOTO KONDISI MOTOR SEBELUM DIJEMPUT</div>
                    <img src="' . $imageSrc . '" alt="Foto Motor" class="motor-photo" />
                  </div>';
    } catch (Exception $e) {
        $html .= '<div class="photo-section">
                    <div class="photo-title">FOTO KONDISI MOTOR SEBELUM DIJEMPUT</div>
                    <p>Error loading image: ' . htmlspecialchars($e->getMessage()) . '</p>
                  </div>';
    }
}

// Signature section
$html .= '<div class="signature-section">
            <div class="signature-box">
                <strong>PETUGAS JEMPUT</strong><br><br><br>
                <div style="border-top: 1px solid #000; margin-top: 40px; padding-top: 5px;">
                    Nama & Tanda Tangan
                </div>
            </div>
            <div class="signature-box">
                <strong>PEMILIK MOTOR</strong><br><br><br>
                <div style="border-top: 1px solid #000; margin-top: 40px; padding-top: 5px;">
                    Nama & Tanda Tangan
                </div>
            </div>
            <div class="clear"></div>
          </div>';

// Footer information
$html .= '<div class="footer-info">
            <div style="text-align: center; font-size: 10px;">
                <em>Dokumen ini dicetak otomatis oleh sistem pada ' . $cetak_waktu . '</em><br>
                <strong>Mohon simpan surat ini sebagai bukti pengambilan motor</strong>
            </div>
          </div>';

$html .= "</div></body></html>";

try {
    $dompdf->loadHtml($html);
    // Setting ukuran dan orientasi kertas
    $dompdf->setPaper('A4', 'portrait');
    // Rendering dari HTML Ke PDF
    $dompdf->render();
    // Melakukan output file Pdf
    $dompdf->stream('surat-pengambilan-motor-' . $no_service . '.pdf', array("Attachment" => 0));
} catch (Exception $e) {
    die('Error generating PDF: ' . $e->getMessage());
}
?>