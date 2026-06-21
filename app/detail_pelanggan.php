<?php
/**
 * DETAIL PELANGGAN
 * File: detail_pelanggan.php
 * Deskripsi: Halaman detail lengkap pelanggan dengan statistik dan riwayat
 * Dibuat: 4 November 2025
 */

session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";
    include "_include_customer_vehicle_sync.php";
    
    // Get user info
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user 
                                        FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = isset($tm_cari['nama_user']) ? $tm_cari['nama_user'] : '';
    $pwd = isset($tm_cari['password']) ? $tm_cari['password'] : '';
    $lvl_akses = isset($tm_cari['user_akses']) ? $tm_cari['user_akses'] : '';
    $foto_user = isset($tm_cari['foto_user']) ? $tm_cari['foto_user'] : '';
    if($foto_user == '') {
        $foto_user = "file_upload/avatar.png";
    }

    // Get cabang info
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = isset($tm_cari['nama_cabang']) ? $tm_cari['nama_cabang'] : '';
    $tipe_cabang = isset($tm_cari['tipe_cabang']) ? $tm_cari['tipe_cabang'] : '';

    // Get nopelanggan from URL
    $nopelanggan = isset($_GET['nopelanggan']) ? mysqli_real_escape_string($koneksi, $_GET['nopelanggan']) : '';

    if(empty($nopelanggan)) {
        echo "<script>alert('Nomor pelanggan tidak ditemukan!'); window.location='statistik_pelanggan_dashboard.php';</script>";
        exit;
    }

    // Get data pelanggan
    $query_pelanggan = "SELECT 
                            p.*,
                            pg.grup as nama_grup,
                            s.total_transaksi,
                            s.total_nominal,
                            s.jumlah_kunjungan,
                            s.rata_rata_transaksi,
                            s.status_member,
                            s.kategori_member_kunjungan,
                            s.tanggal_pertama_transaksi,
                            s.tanggal_terakhir_transaksi,
                            s.lama_tidak_datang,
                            s.lama_menjadi_pelanggan,
                            s.estimasi_datang_berikutnya,
                            s.total_motor,
                            s.kedatangan_terakhir,
                            s.rata_jarak_kunjungan
                        FROM tblpelanggan p
                        LEFT JOIN tblpelanggangrup pg ON p.kgrup = pg.kgrup
                        LEFT JOIN statistik_pelanggan s ON p.nopelanggan = s.no_pelanggan
                        WHERE p.nopelanggan = '$nopelanggan'";
    
    $result_pelanggan = mysqli_query($koneksi, $query_pelanggan);
    
    if(!$result_pelanggan || mysqli_num_rows($result_pelanggan) == 0) {
        echo "<script>alert('Data pelanggan tidak ditemukan!'); window.location='statistik_pelanggan_dashboard.php';</script>";
        exit;
    }
    
    $pelanggan = mysqli_fetch_array($result_pelanggan);
    
    // Get riwayat kendaraan berdasarkan mapping service terbaru atau legacy exact match
    $query_kendaraan = "SELECT DISTINCT
                            k.*
                        FROM tblkendaraan k
                        LEFT JOIN (
                            SELECT
                                ts.no_polisi,
                                SUBSTRING_INDEX(GROUP_CONCAT(ts.no_pelanggan ORDER BY ts.tanggal DESC, ts.jam DESC, ts.no_service DESC SEPARATOR '||'), '||', 1) AS no_pelanggan_map
                            FROM tblservice ts
                            WHERE ts.no_pelanggan IS NOT NULL
                              AND ts.no_pelanggan <> ''
                            GROUP BY ts.no_polisi
                        ) map ON map.no_polisi = k.nopolisi
                        WHERE map.no_pelanggan_map = '$nopelanggan'
                           OR k.nopolisi = '$nopelanggan'
                           OR UPPER(TRIM(k.pemilik)) = UPPER(TRIM('{$nopelanggan}'))
                        ORDER BY k.nopolisi";
    $result_kendaraan = mysqli_query($koneksi, $query_kendaraan);
    
    // Get riwayat service
    $query_service = "SELECT 
                        s.*,
                        DATE_FORMAT(s.tanggal, '%d/%m/%Y') as tanggal_format,
                        k.jenis,
                        k.tipe,
                        pm.merek
                      FROM tblservice s
                      LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
                      LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                      WHERE s.no_pelanggan = '$nopelanggan'
                      ORDER BY s.tanggal DESC
                      LIMIT 20";
    $result_service = mysqli_query($koneksi, $query_service);
    
    // Get riwayat kedatangan
    $query_kedatangan = "SELECT 
                            *,
                            DATE_FORMAT(tanggal_datang, '%d/%m/%Y') as tanggal_format
                         FROM master_kedatangan_pelanggan
                         WHERE no_pelanggan = '$nopelanggan'
                         ORDER BY kedatangan_ke DESC
                         LIMIT 10";
    $result_kedatangan = mysqli_query($koneksi, $query_kedatangan);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Detail Pelanggan" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <!-- Bootstrap & FontAwesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    
    <script src="assets/js/ace-extra.min.js"></script>
    
    <style>
        .profile-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #3498db;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .profile-name {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .profile-info {
            color: #666;
            font-size: 14px;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .stat-box-number {
            font-size: 28px;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-box-label {
            color: #666;
            font-size: 12px;
        }
        .member-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 16px;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
        }
        .member-badge.bronze { background: #CD7F32; }
        .member-badge.silver { background: #C0C0C0; }
        .member-badge.gold { background: #FFD700; color: #333; }
        .member-badge.platinum { background: #E5E4E2; color: #333; }
        .info-row {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            width: 200px;
            display: inline-block;
        }
        .info-value {
            color: #333;
        }
        .timeline-item {
            padding: 15px;
            border-left: 3px solid #3498db;
            margin-left: 20px;
            margin-bottom: 15px;
            position: relative;
        }
        .timeline-item:before {
            content: '';
            width: 12px;
            height: 12px;
            background: #3498db;
            border-radius: 50%;
            position: absolute;
            left: -8px;
            top: 20px;
        }
        .timeline-date {
            color: #999;
            font-size: 12px;
        }
        .timeline-content {
            margin-top: 5px;
        }
    </style>
</head>

<body class="no-skin">
    <?php include "lib/header.php"; ?>
    
    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>
        
        <?php include "lib/sidebar.php"; ?>
        
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li>
                            <a href="statistik_pelanggan_dashboard.php">Statistik Pelanggan</a>
                        </li>
                        <li class="active">Detail Pelanggan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Detail Pelanggan
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Informasi lengkap pelanggan
                            </small>
                        </h1>
                    </div>

                    <div class="row">
                        <!-- Profile Card -->
                        <div class="col-xs-12 col-sm-4">
                            <div class="profile-card">
                                <div class="profile-header">
                                    <div class="profile-avatar">
                                        <?php echo strtoupper(substr($pelanggan['namapelanggan'], 0, 1)); ?>
                                    </div>
                                    <div class="profile-name"><?php echo $pelanggan['namapelanggan']; ?></div>
                                    <div class="profile-info">
                                        <span class="member-badge <?php echo strtolower($pelanggan['status_member'] ?: 'bronze'); ?>">
                                            <?php echo $pelanggan['status_member'] ?: 'Bronze'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label"><i class="fa fa-id-card"></i> No. Pelanggan</span><br>
                                    <span class="info-value"><?php echo $pelanggan['nopelanggan']; ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label"><i class="fa fa-phone"></i> Telepon</span><br>
                                    <span class="info-value"><?php echo $pelanggan['telephone'] ?: '-'; ?></span>
                                </div>

                                <?php if (!empty($pelanggan['no_wa'])): ?>
                                <div class="info-row">
                                    <span class="info-label"><i class="fa fa-whatsapp" style="color:#25D366;"></i> WhatsApp</span><br>
                                    <span class="info-value">
                                        <a href="https://wa.me/<?php echo htmlspecialchars($pelanggan['no_wa']); ?>" target="_blank" class="text-success">
                                            <?php echo htmlspecialchars($pelanggan['no_wa']); ?>
                                        </a>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($pelanggan['domisili_cabang'])): ?>
                                <div class="info-row">
                                    <span class="info-label"><i class="fa fa-map-pin"></i> Domisili Cabang</span><br>
                                    <span class="info-value"><?php echo htmlspecialchars($pelanggan['domisili_cabang']); ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="info-row">
                                    <span class="info-label"><i class="fa fa-map-marker"></i> Alamat</span><br>
                                    <span class="info-value"><?php echo $pelanggan['alamat'] ?: '-'; ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label"><i class="fa fa-users"></i> Grup</span><br>
                                    <span class="info-value"><?php echo $pelanggan['nama_grup'] ?: '-'; ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label"><i class="fa fa-calendar"></i> Pelanggan Sejak</span><br>
                                    <span class="info-value">
                                        <?php 
                                        if($pelanggan['tanggal_pertama_transaksi']) {
                                            echo date('d/m/Y', strtotime($pelanggan['tanggal_pertama_transaksi']));
                                            echo ' (' . $pelanggan['lama_menjadi_pelanggan'] . ' hari)';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div style="margin-top: 20px;">
                                    <a href="pelanggan-edit.php?nopelanggan=<?php echo $pelanggan['nopelanggan']; ?>" class="btn btn-primary btn-block">
                                        <i class="fa fa-edit"></i> Edit Data
                                    </a>
                                    <a href="servis-input-reguler.php?nopelanggan=<?php echo $pelanggan['nopelanggan']; ?>" class="btn btn-success btn-block">
                                        <i class="fa fa-plus"></i> Buat Service Baru
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Statistics & Details -->
                        <div class="col-xs-12 col-sm-8">
                            <!-- Statistics Cards -->
                            <div class="row">
                                <div class="col-xs-6 col-sm-3">
                                    <div class="stat-box">
                                        <div class="stat-box-number text-primary">
                                            <?php echo number_format($pelanggan['total_transaksi'] ?: 0); ?>
                                        </div>
                                        <div class="stat-box-label">Total Transaksi</div>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="stat-box">
                                        <div class="stat-box-number text-success">
                                            <?php echo number_format($pelanggan['jumlah_kunjungan'] ?: 0); ?>
                                        </div>
                                        <div class="stat-box-label">Jumlah Kunjungan</div>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="stat-box">
                                        <div class="stat-box-number text-info">
                                            <?php echo number_format($pelanggan['total_motor'] ?: 0); ?>
                                        </div>
                                        <div class="stat-box-label">Total Motor</div>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="stat-box">
                                        <div class="stat-box-number text-warning">
                                            <?php echo number_format($pelanggan['lama_tidak_datang'] ?: 0); ?>
                                        </div>
                                        <div class="stat-box-label">Hari Tidak Datang</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Financial Stats -->
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5 class="widget-title">
                                        <i class="ace-icon fa fa-money"></i>
                                        Statistik Keuangan
                                    </h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <div class="info-row">
                                                    <span class="info-label">Total Nominal</span><br>
                                                    <span class="info-value" style="font-size: 20px; font-weight: bold; color: #27ae60;">
                                                        Rp <?php echo number_format($pelanggan['total_nominal'] ?: 0, 0, ',', '.'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-xs-6">
                                                <div class="info-row">
                                                    <span class="info-label">Rata-rata Transaksi</span><br>
                                                    <span class="info-value" style="font-size: 20px; font-weight: bold; color: #3498db;">
                                                        Rp <?php echo number_format($pelanggan['rata_rata_transaksi'] ?: 0, 0, ',', '.'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row" style="margin-top: 15px;">
                                            <div class="col-xs-6">
                                                <div class="info-row">
                                                    <span class="info-label">Status Member (Nominal)</span><br>
                                                    <span class="member-badge <?php echo strtolower($pelanggan['status_member'] ?: 'bronze'); ?>">
                                                        <?php echo $pelanggan['status_member'] ?: 'Bronze'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-xs-6">
                                                <div class="info-row">
                                                    <span class="info-label">Kategori Member (Kunjungan)</span><br>
                                                    <span class="member-badge <?php echo strtolower($pelanggan['kategori_member_kunjungan'] ?: 'bronze'); ?>">
                                                        <?php echo $pelanggan['kategori_member_kunjungan'] ?: 'Bronze'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kendaraan -->
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5 class="widget-title">
                                        <i class="ace-icon fa fa-motorcycle"></i>
                                        Kendaraan Terdaftar
                                    </h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>No. Polisi</th>
                                                    <th>Jenis</th>
                                                    <th>Tipe</th>
                                                    <th>Warna</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                if(mysqli_num_rows($result_kendaraan) > 0) {
                                                    while($kendaraan = mysqli_fetch_array($result_kendaraan)): 
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo $kendaraan['nopolisi']; ?></strong></td>
                                                    <td><?php echo $kendaraan['jenis']; ?></td>
                                                    <td><?php echo $kendaraan['tipe']; ?></td>
                                                    <td><?php echo $kendaraan['warna']; ?></td>
                                                    <td>
                                                        <a href="servis-input-reguler.php?nopolisi=<?php echo $kendaraan['nopolisi']; ?>" class="btn btn-xs btn-success" title="Service">
                                                            <i class="fa fa-wrench"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endwhile;
                                                } else {
                                                    echo '<tr><td colspan="5" class="text-center">Belum ada kendaraan terdaftar</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Riwayat Service -->
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5 class="widget-title">
                                        <i class="ace-icon fa fa-history"></i>
                                        Riwayat Service (20 Terakhir)
                                    </h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>No. Service</th>
                                                    <th>Tanggal</th>
                                                    <th>Kendaraan</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                if(mysqli_num_rows($result_service) > 0) {
                                                    while($service = mysqli_fetch_array($result_service)): 
                                                        $status_class = '';
                                                        $status_text = '';
                                                        switch($service['status_servis']) {
                                                            case 'datang':
                                                                $status_class = 'label-info';
                                                                $status_text = 'DATANG';
                                                                break;
                                                            case 'diproses':
                                                                $status_class = 'label-warning';
                                                                $status_text = 'DIPROSES';
                                                                break;
                                                            case 'selesai':
                                                                $status_class = 'label-primary';
                                                                $status_text = 'SELESAI';
                                                                break;
                                                            case 'bayar':
                                                                $status_class = 'label-success';
                                                                $status_text = 'LUNAS';
                                                                break;
                                                            default:
                                                                $status_class = 'label-default';
                                                                $status_text = strtoupper($service['status_servis']);
                                                        }
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo $service['no_service']; ?></strong></td>
                                                    <td><?php echo $service['tanggal_format']; ?></td>
                                                    <td>
                                                        <?php echo $service['no_polisi']; ?><br>
                                                        <small class="text-muted"><?php echo $service['merek'] . ' ' . $service['tipe']; ?></small>
                                                    </td>
                                                    <td>Rp <?php echo number_format($service['total_akhir'] ?: 0, 0, ',', '.'); ?></td>
                                                    <td>
                                                        <span class="label <?php echo $status_class; ?>">
                                                            <?php echo $status_text; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="servis-print.php?snoserv=<?php echo $service['no_service']; ?>" class="btn btn-xs btn-info" title="Print Invoice" target="_blank">
                                                            <i class="fa fa-print"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endwhile;
                                                } else {
                                                    echo '<tr><td colspan="6" class="text-center">Belum ada riwayat service</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline Kedatangan -->
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5 class="widget-title">
                                        <i class="ace-icon fa fa-clock-o"></i>
                                        Timeline Kedatangan (10 Terakhir)
                                    </h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <?php 
                                        if(mysqli_num_rows($result_kedatangan) > 0) {
                                            while($kedatangan = mysqli_fetch_array($result_kedatangan)): 
                                        ?>
                                        <div class="timeline-item">
                                            <div class="timeline-date">
                                                <i class="fa fa-calendar"></i> <?php echo $kedatangan['tanggal_format']; ?>
                                            </div>
                                            <div class="timeline-content">
                                                <strong>Kunjungan ke-<?php echo $kedatangan['kedatangan_ke']; ?></strong><br>
                                                <small class="text-muted">
                                                    No. Service: <?php echo $kedatangan['no_service']; ?> | 
                                                    Total: Rp <?php echo number_format($kedatangan['total_transaksi'], 0, ',', '.'); ?>
                                                    <?php if($kedatangan['jarak_hari'] > 0): ?>
                                                    | Jarak: <?php echo $kedatangan['jarak_hari']; ?> hari dari kunjungan sebelumnya
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <?php 
                                            endwhile;
                                        } else {
                                            echo '<p class="text-center text-muted">Belum ada riwayat kedatangan</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "lib/footer.php"; ?>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
