<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user=$_SESSION['_iduser'];        
    $kd_cabang=$_SESSION['_cabang'];        
    include "../config/koneksi.php";
    
    // User data
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_user, password, user_akses, foto_user 
                                    FROM tbuser WHERE id='$id_user'");            
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'] ?? '';                        
    $pwd=$tm_cari['password'] ?? '';                        
    $lvl_akses=$tm_cari['user_akses'] ?? '';                                
    $foto_user=$tm_cari['foto_user'] ?? '';                
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang, alamat_cabang
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");            
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'] ?? '';                        
    $tipe_cabang=$tm_cari['tipe_cabang'] ?? '';    
    $alamat_cabang=$tm_cari['alamat_cabang'] ?? '';    
    $telepon_cabang = '';  // Column doesn't exist in database   
    $email_cabang = '';    // Column doesn't exist in database    
    // --------------------
    
    $no_service = $_GET['snoserv'] ?? '';
    
    if(empty($no_service)) {
        echo "<script>alert('No. Service tidak ditemukan!'); window.close();</script>";
        exit;
    }
    
    // Get service data
    $cari_kd=mysqli_query($koneksi,"SELECT s.*, p.namapelanggan, p.alamat, p.telephone,
                                           v.merek, v.jenis, v.warna, v.no_rangka, v.no_mesin, v.pemilik
                                    FROM tblservice s 
                                    LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                                    LEFT JOIN view_cari_kendaraan v ON s.no_polisi = v.nopolisi
                                    WHERE s.no_service='$no_service'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    
    if(!$tm_cari) {
        echo "<script>alert('Data service tidak ditemukan!'); window.close();</script>";
        exit;
    }
    
    $tanggal = date('d/m/Y', strtotime($tm_cari['tanggal']));
    $jam = $tm_cari['jam'];
    $kode_pelanggan = $tm_cari['no_pelanggan'];
    $no_polisi = $tm_cari['no_polisi'];
    $namapelanggan = $tm_cari['namapelanggan'];
    $alamat = $tm_cari['alamat'];
    $telepon = $tm_cari['telephone'];
    $merek = $tm_cari['merek'] ?? '';
    $jenis = $tm_cari['jenis'] ?? '';
    $warna = $tm_cari['warna'] ?? '';
    $no_rangka = $tm_cari['no_rangka'] ?? '';
    $no_mesin = $tm_cari['no_mesin'] ?? '';
    $pemilik = $tm_cari['pemilik'] ?? '';
    $km_sekarang = $tm_cari['km_sekarang'] ?? 0;
    $status_servis = $tm_cari['status_servis'] ?? 'datang';
    
    // Get service details (jasa)
    $sql_jasa = mysqli_query($koneksi,"SELECT sj.*, wh.nama_wo as nama_jasa, wh.harga as harga_standar
                                       FROM tblservis_jasa sj
                                       LEFT JOIN tbworkorderheader wh ON sj.no_item = wh.kode_wo
                                       WHERE sj.no_service='$no_service'
                                       ORDER BY sj.id ASC");
    
    // Get barang details
    $sql_barang = mysqli_query($koneksi,"SELECT sb.*, vi.namaitem as nama_barang, vi.harga_jual as harga_standar
                                         FROM tblservis_barang sb
                                         LEFT JOIN view_cari_item vi ON sb.no_item = vi.noitem
                                         WHERE sb.no_service='$no_service'
                                         ORDER BY sb.id ASC");
    
    // Get work orders
    $sql_wo = mysqli_query($koneksi,"SELECT sw.*, wh.nama_wo as nama_workorder, wh.harga as harga_workorder
                                     FROM tbservis_workorder sw
                                     LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                                     WHERE sw.no_service='$no_service'
                                     ORDER BY sw.id ASC");
    
    // Get mechanics - using data from service table directly
    // Mechanic data is stored in the service table itself
    $mechanic_data = array();
    
    // Get mechanic names if IDs are stored in service table
    if(!empty($tm_cari['kepala_mekanik1'])) {
        $mek_query = mysqli_query($koneksi,"SELECT nama FROM tblmekanik WHERE nomekanik='{$tm_cari['kepala_mekanik1']}'");
        $mek_data = mysqli_fetch_array($mek_query);
        $mechanic_data[] = array('tipe' => 'Kepala Mekanik', 'nama' => $mek_data['nama'] ?? '');
    }
    
    if(!empty($tm_cari['mekanik1'])) {
        $mek_query = mysqli_query($koneksi,"SELECT nama FROM tblmekanik WHERE nomekanik='{$tm_cari['mekanik1']}'");
        $mek_data = mysqli_fetch_array($mek_query);
        $mechanic_data[] = array('tipe' => 'Mekanik', 'nama' => $mek_data['nama'] ?? '');
    }
    
    // Get complaints/keluhan
    $sql_keluhan = mysqli_query($koneksi,"SELECT keluhan FROM tbservis_keluhan_status 
                                          WHERE no_service='$no_service' 
                                          ORDER BY id ASC");
    
    // Calculate totals
    $total_jasa = 0;
    $total_barang = 0;
    $total_wo = 0;
    
    // Calculate jasa total
    mysqli_data_seek($sql_jasa, 0);
    while($jasa = mysqli_fetch_array($sql_jasa)) {
        $total_jasa += $jasa['total'];
    }
    
    // Calculate barang total
    mysqli_data_seek($sql_barang, 0);
    while($barang = mysqli_fetch_array($sql_barang)) {
        $total_barang += $barang['total'];
    }
    
    // Calculate work order total
    mysqli_data_seek($sql_wo, 0);
    while($wo = mysqli_fetch_array($sql_wo)) {
        $total_wo += $wo['harga_workorder'];
    }
    
    $grand_total = $total_jasa + $total_barang + $total_wo;
    
    // Reset result pointers
    mysqli_data_seek($sql_jasa, 0);
    mysqli_data_seek($sql_barang, 0);
    mysqli_data_seek($sql_wo, 0);
    mysqli_data_seek($sql_mek, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Service - <?php echo $no_service; ?></title>
    
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .invoice-container { box-shadow: none !important; }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        
        .header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }
        
        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .info-left, .info-right {
            width: 48%;
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        
        .info-value {
            color: #333;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin: 25px 0 15px 0;
            text-transform: uppercase;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .table td {
            font-size: 11px;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .total-section {
            margin-top: 30px;
            border-top: 2px solid #007bff;
            padding-top: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .total-label {
            font-weight: bold;
            color: #555;
        }
        
        .total-value {
            font-weight: bold;
            color: #333;
        }
        
        .grand-total {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 16px;
            color: #007bff;
        }
        
        .mechanics-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .mechanic-item {
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .complaints-section {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        
        .complaint-item {
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-datang { background: #ffc107; color: #333; }
        .status-diproses { background: #17a2b8; color: white; }
        .status-selesai { background: #28a745; color: white; }
        .status-bayar { background: #007bff; color: white; }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <i class="fa fa-print"></i> Print Invoice
    </button>
    
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name"><?php echo strtoupper($nama_cabang); ?></div>
                <div class="company-details">
                    <?php if($alamat_cabang): ?>
                        <?php echo $alamat_cabang; ?><br>
                    <?php endif; ?>
                    <?php if($telepon_cabang): ?>
                        Telp: <?php echo $telepon_cabang; ?>
                    <?php endif; ?>
                    <?php if($email_cabang): ?>
                        | Email: <?php echo $email_cabang; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="invoice-title">Invoice Service Motor</div>
        </div>
        
        <!-- Service Info -->
        <div class="info-section">
            <div class="info-left">
                <div class="info-group">
                    <span class="info-label">No. Service:</span>
                    <span class="info-value"><strong><?php echo $no_service; ?></strong></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Tanggal:</span>
                    <span class="info-value"><?php echo $tanggal; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Jam:</span>
                    <span class="info-value"><?php echo $jam; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php echo $status_servis; ?>">
                            <?php echo ucfirst($status_servis); ?>
                        </span>
                    </span>
                </div>
            </div>
            <div class="info-right">
                <div class="info-group">
                    <span class="info-label">Pelanggan:</span>
                    <span class="info-value"><?php echo $namapelanggan; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Alamat:</span>
                    <span class="info-value"><?php echo $alamat; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Telepon:</span>
                    <span class="info-value"><?php echo $telepon; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo $email; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Info -->
        <div class="section-title">Informasi Kendaraan</div>
        <div class="info-section">
            <div class="info-left">
                <div class="info-group">
                    <span class="info-label">No. Polisi:</span>
                    <span class="info-value"><strong><?php echo $no_polisi; ?></strong></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Merk/Tipe:</span>
                    <span class="info-value"><?php echo $merek . ' ' . $jenis; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Warna:</span>
                    <span class="info-value"><?php echo $warna; ?></span>
                </div>
            </div>
            <div class="info-right">
                <div class="info-group">
                    <span class="info-label">No. Rangka:</span>
                    <span class="info-value"><?php echo $no_rangka; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">No. Mesin:</span>
                    <span class="info-value"><?php echo $no_mesin; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">KM Sekarang:</span>
                    <span class="info-value"><?php echo number_format($km_sekarang, 0, ',', '.'); ?> km</span>
                </div>
            </div>
        </div>
        
        <!-- Complaints Section -->
        <?php if(mysqli_num_rows($sql_keluhan) > 0): ?>
        <div class="section-title">Keluhan Pelanggan</div>
        <div class="complaints-section">
            <?php while($keluhan = mysqli_fetch_array($sql_keluhan)): ?>
            <div class="complaint-item">• <?php echo htmlspecialchars($keluhan['keluhan']); ?></div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
        
        <!-- Mechanics Section -->
        <?php if(count($mechanic_data) > 0): ?>
        <div class="section-title">Penanggung Jawab</div>
        <div class="mechanics-section">
            <?php foreach($mechanic_data as $mek): ?>
            <div class="mechanic-item">
                <strong><?php echo $mek['tipe']; ?>:</strong> 
                <?php echo $mek['nama']; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Work Orders -->
        <?php if(mysqli_num_rows($sql_wo) > 0): ?>
        <div class="section-title">Paket Service (Work Order)</div>
        <table class="table">
            <thead>
                <tr>
                    <th width="10%">No</th>
                    <th width="60%">Nama Paket</th>
                    <th width="30%" class="text-right">Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($wo = mysqli_fetch_array($sql_wo)): 
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($wo['nama_workorder']); ?></td>
                    <td class="text-right">Rp <?php echo number_format($wo['harga_workorder'], 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Services -->
        <?php if(mysqli_num_rows($sql_jasa) > 0): ?>
        <div class="section-title">Jasa Service</div>
        <table class="table">
            <thead>
                <tr>
                    <th width="10%">No</th>
                    <th width="40%">Nama Jasa</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="15%" class="text-right">Harga</th>
                    <th width="20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($jasa = mysqli_fetch_array($sql_jasa)): 
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($jasa['nama_jasa']); ?></td>
                    <td class="text-center"><?php echo $jasa['qty']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($jasa['harga'], 0, ',', '.'); ?></td>
                    <td class="text-right">Rp <?php echo number_format($jasa['total'], 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Parts/Barang -->
        <?php if(mysqli_num_rows($sql_barang) > 0): ?>
        <div class="section-title">Suku Cadang / Barang</div>
        <table class="table">
            <thead>
                <tr>
                    <th width="10%">No</th>
                    <th width="40%">Nama Barang</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="15%" class="text-right">Harga</th>
                    <th width="20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($barang = mysqli_fetch_array($sql_barang)): 
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                    <td class="text-center"><?php echo $barang['qty']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($barang['harga'], 0, ',', '.'); ?></td>
                    <td class="text-right">Rp <?php echo number_format($barang['total'], 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Totals -->
        <div class="total-section">
            <?php if($total_wo > 0): ?>
            <div class="total-row">
                <span class="total-label">Sub Total Paket Service:</span>
                <span class="total-value">Rp <?php echo number_format($total_wo, 0, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($total_jasa > 0): ?>
            <div class="total-row">
                <span class="total-label">Sub Total Jasa:</span>
                <span class="total-value">Rp <?php echo number_format($total_jasa, 0, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($total_barang > 0): ?>
            <div class="total-row">
                <span class="total-label">Sub Total Barang:</span>
                <span class="total-value">Rp <?php echo number_format($total_barang, 0, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="total-row grand-total">
                <span class="total-label">TOTAL KESELURUHAN:</span>
                <span class="total-value">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih atas kepercayaan Anda menggunakan layanan kami.</p>
            <p>Invoice ini dicetak pada: <?php echo date('d/m/Y H:i:s'); ?> oleh <?php echo $_nama; ?></p>
        </div>
    </div>
    
    <script>
        // Auto focus for print
        window.onload = function() {
            // Optional: Auto print when page loads
            // window.print();
        }
    </script>
</body>
</html>
<?php } ?>
