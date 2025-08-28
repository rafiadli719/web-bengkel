<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    include "../config/koneksi.php";
    
    $no_service=$_GET['snosrv'];
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    DATE_FORMAT(tanggal,'%d-%b-%y') AS tanggal_serv, 
                                    tanggal, jam, no_pelanggan, no_polisi, 
                                    keterangan_jemput, foto_patokan, user_input
                                    FROM tblservice 
                                    WHERE no_service='$no_service'");
    $tm_cari=mysqli_fetch_array($cari_kd);	
    $tanggal=$tm_cari['tanggal_serv'];
    $tanggal_srv=$tm_cari['tanggal'];                
    $jam=$tm_cari['jam'];        
    $kode_pelanggan=$tm_cari['no_pelanggan'];        
    $no_polisi=$tm_cari['no_polisi'];  
    $keterangan_jemput=$tm_cari['keterangan_jemput'];
    $foto_patokan=$tm_cari['foto_patokan'];
    $user_input=$tm_cari['user_input'];
            
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    namapelanggan, alamat, patokan, telephone  
                                    FROM tblpelanggan 
                                    WHERE nopelanggan='$kode_pelanggan'");
    $tm_cari=mysqli_fetch_array($cari_kd);	
    $namapelanggan=$tm_cari['namapelanggan'];
    $alamat=$tm_cari['alamat'];
    $patokan=$tm_cari['patokan'];
    $telepon=$tm_cari['telephone'];
    
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    pemilik, jenis, merek, warna, 
                                    no_rangka, no_mesin, tipe 
                                    FROM view_cari_kendaraan 
                                    WHERE nopolisi='$no_polisi'");
    $tm_cari=mysqli_fetch_array($cari_kd);	
    $pemilik=$tm_cari['pemilik'];
    $jenis=$tm_cari['jenis'];
    $merek=$tm_cari['merek'];
    $warna=$tm_cari['warna'];
    $no_rangka=$tm_cari['no_rangka'];
    $no_mesin=$tm_cari['no_mesin'];
    $tipe=$tm_cari['tipe'];

    // Get cabang info
    $kd_cabang=$_SESSION['_cabang'];
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang, alamat, telepon
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];				        
    $tipe_cabang=$tm_cari['tipe_cabang'];
    $alamat_cabang=$tm_cari['alamat'];
    $telepon_cabang=$tm_cari['telepon'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perintah Penjemputan Motor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 11px;
        }
        
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .info-table td {
            padding: 5px;
            vertical-align: top;
        }
        
        .info-table .label {
            width: 20%;
            font-weight: bold;
        }
        
        .info-table .colon {
            width: 2%;
            text-align: center;
        }
        
        .info-table .value {
            width: 28%;
            border-bottom: 1px solid #000;
        }
        
        .content {
            margin: 30px 0;
            line-height: 1.8;
        }
        
        .foto-section {
            margin: 20px 0;
            text-align: center;
            border: 2px dashed #ccc;
            padding: 20px;
        }
        
        .foto-section img {
            max-width: 300px;
            max-height: 200px;
            border: 1px solid #000;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 30%;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 60px;
            margin-bottom: 5px;
        }
        
        .date-section {
            text-align: right;
            margin: 20px 0;
        }
        
        .keterangan-box {
            border: 1px solid #000;
            padding: 10px;
            margin: 20px 0;
            min-height: 80px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        .status-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Cetak</button>
    
    <div class="header">
        <h1><?php echo strtoupper($nama_cabang); ?></h1>
        <h2><?php echo $tipe_cabang; ?></h2>
        <p><?php echo $alamat_cabang; ?></p>
        <p>Telp: <?php echo $telepon_cabang; ?></p>
        <p>"Solusi Tepat Servis Matic"</p>
    </div>
    
    <div class="title">SURAT PERINTAH PENJEMPUTAN MOTOR</div>
    
    <table class="info-table">
        <tr>
            <td class="label">Tanggal</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $tanggal; ?></td>
            <td class="label">Jam Jemput</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $jam; ?></td>
            <td class="label">No Servis</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $no_service; ?></td>
        </tr>
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="value"><?php echo strtoupper($namapelanggan); ?></td>
            <td class="label">No Polisi</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $no_polisi; ?></td>
            <td class="label">Merek</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $merek; ?></td>
        </tr>
        <tr>
            <td class="label">Telpon</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $telepon; ?></td>
            <td class="label">Tipe</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $tipe; ?></td>
            <td class="label">Jenis</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $jenis; ?></td>
        </tr>
        <tr>
            <td class="label">Warna</td>
            <td class="colon">:</td>
            <td class="value"><?php echo strtoupper($warna); ?></td>
            <td class="label">No. Rangka</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $no_rangka; ?></td>
            <td class="label">No. Mesin</td>
            <td class="colon">:</td>
            <td class="value"><?php echo $no_mesin; ?></td>
        </tr>
    </table>
    
    <table class="info-table">
        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td colspan="7" class="value"><?php echo strtoupper($alamat); ?></td>
        </tr>
        <tr>
            <td class="label">Patokan</td>
            <td class="colon">:</td>
            <td colspan="7" class="value"><?php echo strtoupper($patokan); ?></td>
        </tr>
        <tr>
            <td class="label">Petugas</td>
            <td class="colon">:</td>
            <td colspan="7" class="value"><?php echo strtoupper($user_input); ?></td>
        </tr>
    </table>
    
    <div class="keterangan-box">
        <strong>Keluhan/Keterangan Penjemputan:</strong><br>
        <?php echo nl2br(htmlspecialchars($keterangan_jemput)); ?>
    </div>
    
    <?php if($foto_patokan && file_exists($foto_patokan)) { ?>
    <div class="foto-section">
        <strong>FOTO PATOKAN RUMAH</strong><br><br>
        <img src="<?php echo $foto_patokan; ?>" alt="Foto Patokan Rumah" />
        <br><br>
        <small>Dokumentasi lokasi penjemputan untuk memudahkan identifikasi</small>
    </div>
    <?php } else { ?>
    <div class="foto-section">
        <strong>FOTO PATOKAN RUMAH</strong><br><br>
        <div style="border: 1px dashed #ccc; height: 150px; display: flex; align-items: center; justify-content: center;">
            <span style="color: #999;">Foto belum tersedia</span>
        </div>
        <br>
        <small>Dokumentasi lokasi penjemputan untuk memudahkan identifikasi</small>
    </div>
    <?php } ?>
    
    <div class="content">
        <strong>INSTRUKSI PENJEMPUTAN:</strong>
        <ol>
            <li>Pastikan identitas petugas dan konfirmasi jadwal dengan pelanggan</li>
            <li>Lakukan pengecekan kondisi motor sebelum dijemput</li>
            <li>Ambil foto kondisi motor dari berbagai sudut</li>
            <li>Catat keluhan dan kondisi khusus yang ditemukan</li>
            <li>Berikan receipt/bukti penjemputan kepada pelanggan</li>
            <li>Pastikan motor dalam kondisi aman selama pengangkutan</li>
            <li>Laporkan status penjemputan ke supervisor</li>
        </ol>
        
        <br>
        <strong>CATATAN PENTING:</strong>
        <ul>
            <li>Bawa peralatan safety dan dokumentasi</li>
            <li>Konfirmasi kembali alamat dan patokan sebelum berangkat</li>
            <li>Hubungi customer service jika ada kendala: <?php echo $telepon_cabang; ?></li>
        </ul>
    </div>
    
    <div class="date-section">
        <span class="status-badge">JADWAL PENJEMPUTAN</span><br>
        Cetak : <?php echo date('d/m/Y H:i:s'); ?>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div><strong>ADMIN</strong></div>
            <div><?php echo $user_input; ?></div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div><strong>DRIVER/KURIR</strong></div>
            <div>(__________________)</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div><strong>PELANGGAN</strong></div>
            <div><?php echo $namapelanggan; ?></div>
        </div>
    </div>
    
    <script>
        // Auto print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // }
        
        // Close window after printing (optional)
        window.addEventListener('afterprint', function() {
            // window.close();
        });
    </script>
</body>
</html>

<?php 
}
?>