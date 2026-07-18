<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";
include_once "../lib/rbac.php";

error_reporting(E_ALL);
ini_set('display_errors', 0);
if(!isset($koneksi) || !$koneksi){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die('DB connection failed: '.mysqli_connect_error());
}
@mysqli_set_charset($koneksi, 'utf8mb4');

$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari && isset($tm_cari['nama_user']) ? $tm_cari['nama_user'] : 'admin';
$lvl_akses = $tm_cari && isset($tm_cari['user_akses']) ? $tm_cari['user_akses'] : '';
$foto_user = $tm_cari && isset($tm_cari['foto_user']) ? $tm_cari['foto_user'] : '';
if($foto_user=='') { $foto_user="file_upload/avatar.png"; }
$is_admin = ($lvl_akses == '1');

$can_request = $is_admin;
if(function_exists('rbac_has')){
    $can_request = $can_request || rbac_has('service_create') || rbac_has('customer_create');
}
if(!$can_request){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(403);
    die('Anda tidak memiliki hak mengajukan pemindahan kepemilikan kendaraan.');
}

$message = '';
$error_class = 'alert-info';

// Simpan Pengajuan Baru
if(isset($_POST['btnsubmit_request'])){
    $id_kendaraan = (int)$_POST['id_kendaraan'];
    $nopelanggan_lama = trim((string)$_POST['nopelanggan_lama']);
    $nopelanggan_baru = trim((string)$_POST['nopelanggan_baru']);
    $nopolisi_snapshot = trim((string)$_POST['nopolisi']);
    $alasan = trim((string)$_POST['alasan']);
    $confirm_target = trim((string)$_POST['confirm_target']);
    
    if($id_kendaraan <= 0 || $nopelanggan_lama === '' || $nopelanggan_baru === '' || $alasan === ''){
        $message = 'Lengkapi seluruh data formulir.'; $error_class = 'alert-danger';
    } elseif($nopelanggan_lama === $nopelanggan_baru){
        $message = 'Pemilik baru tidak boleh sama dengan pemilik lama.'; $error_class = 'alert-danger';
    } elseif($confirm_target !== $nopelanggan_baru){
        $message = 'Konfirmasi kode pelanggan baru tidak cocok. Silakan pilih ulang dari pencarian.'; $error_class = 'alert-danger';
    } else {
        // Cek Blocker: apakah ada servis menggantung
        $q_block = mysqli_query($koneksi, "
            SELECT COUNT(*) AS total_blocker 
            FROM tblservice 
            WHERE no_polisi='".mysqli_real_escape_string($koneksi, $nopolisi_snapshot)."' 
              AND status_servis IN ('datang','diproses','selesai')
        ");
        $block = mysqli_fetch_assoc($q_block);
        
        if($block && (int)$block['total_blocker'] > 0){
            $message = 'Gagal mengajukan. Terdapat transaksi servis aktif yang belum selesai/lunas untuk kendaraan ini.';
            $error_class = 'alert-danger';
        } else {
            // Cek apakah sudah ada request diajukan yang masih pending untuk motor ini
            $q_pending = mysqli_query($koneksi, "SELECT id FROM permintaan_pindah_kepemilikan_kendaraan WHERE id_kendaraan={$id_kendaraan} AND status='diajukan'");
            if(mysqli_num_rows($q_pending) > 0){
                $message = 'Sudah ada pengajuan pindah tangan yang menunggu persetujuan untuk kendaraan ini.';
                $error_class = 'alert-danger';
            } else {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO permintaan_pindah_kepemilikan_kendaraan (id_kendaraan, nopelanggan_lama, nopelanggan_baru, nopolisi_snapshot, tanggal_efektif, alasan, status, dibuat_oleh) VALUES (?, ?, ?, ?, CURDATE(), ?, 'diajukan', ?)");
                mysqli_stmt_bind_param($stmt, "issssi", $id_kendaraan, $nopelanggan_lama, $nopelanggan_baru, $nopolisi_snapshot, $alasan, $id_user);
                if(mysqli_stmt_execute($stmt)){
                    $message = 'Permohonan pindah kepemilikan berhasil diajukan ke Supervisor.';
                    $error_class = 'alert-success';
                } else {
                    $message = 'Gagal menyimpan permohonan: '.mysqli_error($koneksi);
                    $error_class = 'alert-danger';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Pencarian Kendaraan Aktif
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$detail_motor = null;
$total_blocker = 0;
$riwayat_owner_motor = [];

if($search !== ''){
    $esc_search = mysqli_real_escape_string($koneksi, $search);
    $q_motor = mysqli_query($koneksi, "
        SELECT k.*, kk.nopelanggan, p.namapelanggan
        FROM tblkendaraan k
        LEFT JOIN kepemilikan_kendaraan kk ON kk.id_kendaraan = k.id_kendaraan AND kk.is_current = 1
        LEFT JOIN tblpelanggan p ON p.nopelanggan = kk.nopelanggan
        WHERE k.nopolisi='{$esc_search}' LIMIT 1
    ");
    $detail_motor = $q_motor ? mysqli_fetch_assoc($q_motor) : null;
    
    if($detail_motor){
        // Hitung transaksi menggantung
        $q_block = mysqli_query($koneksi, "
            SELECT COUNT(*) AS total_blocker 
            FROM tblservice 
            WHERE no_polisi='".mysqli_real_escape_string($koneksi, $detail_motor['nopolisi'])."' 
              AND status_servis IN ('datang','diproses','selesai')
        ");
        $block = mysqli_fetch_assoc($q_block);
        $total_blocker = $block ? (int)$block['total_blocker'] : 0;
        
        // Tarik riwayat kepemilikan motor ini sebelumnya
        $q_owner_hist = mysqli_query($koneksi, "
            SELECT kk.*, p.namapelanggan
            FROM kepemilikan_kendaraan kk
            LEFT JOIN tblpelanggan p ON p.nopelanggan=kk.nopelanggan
            WHERE kk.id_kendaraan = {$detail_motor['id_kendaraan']}
            ORDER BY kk.tanggal_mulai DESC, kk.id DESC
        ");
        if($q_owner_hist){
            while($rh = mysqli_fetch_assoc($q_owner_hist)){ $riwayat_owner_motor[] = $rh; }
        }
    }
}

// Ambil riwayat pengajuan milik cabang/dibuat user
$riwayat = [];
$qr = mysqli_query($koneksi, "
    SELECT r.*, k.nopolisi, pl.namapelanggan AS nama_lama, pb.namapelanggan AS nama_baru, u.nama_user AS pembuat
    FROM permintaan_pindah_kepemilikan_kendaraan r
    JOIN tblkendaraan k ON k.id_kendaraan = r.id_kendaraan
    JOIN tblpelanggan pl ON pl.nopelanggan = r.nopelanggan_lama
    JOIN tblpelanggan pb ON pb.nopelanggan = r.nopelanggan_baru
    JOIN tbuser u ON u.id = r.dibuat_oleh
    ORDER BY r.created_at DESC LIMIT 50
");
if($qr){ while($row = mysqli_fetch_assoc($qr)){ $riwayat[] = $row; } }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Pindah Kepemilikan Kendaraan - <?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
        .ui-autocomplete { z-index: 2000 !important; max-height: 250px; overflow-y: auto; }
        .blocker-alert { background: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; }
        .success-alert { background: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body class="no-skin">
<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <div class="navbar-header pull-left">
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> FIT MOTOR</small></a>
        </div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <?php include "menu_dashboard.php"; ?>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li class="active">Pindah Kepemilikan</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Pengajuan Pindah Kepemilikan (Motor Dijual) <small><i class="ace-icon fa fa-angle-double-right"></i> Manajemen Kepemilikan Aset Kendaraan</small></h1>
                </div>

                <?php if($message != ''){ echo '<div class="alert '.$error_class.'">'.htmlspecialchars($message).'</div>'; } ?>

                <div class="row">
                    <div class="col-xs-12 col-md-5">
                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title"><i class="fa fa-search"></i> Langkah 1: Cari Nomor Polisi</h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <form method="get" action="kendaraan_pindah_tangan.php" class="form-inline">
                                        <input type="text" name="search" class="form-control input-large" placeholder="Contoh: G 3191 ZN" value="<?php echo htmlspecialchars($search); ?>" required />
                                        <button type="submit" class="btn btn-info btn-sm"><i class="fa fa-search"></i> Periksa</button>
                                    </form>
                                    
                                    <?php if($search !== '' && !$detail_motor){ ?>
                                        <hr />
                                        <div class="alert alert-danger">Nomor polisi <strong><?php echo htmlspecialchars($search); ?></strong> tidak ditemukan di database.</div>
                                    <?php } elseif($detail_motor) { ?>
                                        <hr />
                                        <table class="table table-bordered">
                                            <tr><th>Nopol</th><td><span class="label label-lg label-success"><strong><?php echo htmlspecialchars($detail_motor['nopolisi']); ?></strong></span></td></tr>
                                            <tr><th>Merek/Tipe</th><td><?php echo htmlspecialchars($detail_motor['tipe'] . ' (' . $detail_motor['warna'] . ')'); ?></td></tr>
                                            <tr><th>No. Rangka</th><td><?php echo htmlspecialchars($detail_motor['no_rangka'] ?: '-'); ?></td></tr>
                                            <tr><th>Pemilik Aktif</th><td><code><?php echo htmlspecialchars($detail_motor['nopelanggan']); ?></code> - <strong><?php echo htmlspecialchars($detail_motor['namapelanggan'] ?: 'Legacy Text: ' . $detail_motor['pemilik']); ?></strong></td></tr>
                                        </table>
                                        
                                        <?php if($total_blocker > 0){ ?>
                                            <div class="blocker-alert">
                                                <i class="fa fa-warning"></i> DIBLOKIR: Ada <?php echo $total_blocker; ?> servis aktif.
                                            </div>
                                        <?php } else { ?>
                                            <div class="success-alert">
                                                <i class="fa fa-check"></i> SIAP: Tidak ada transaksi menggantung.
                                            </div>
                                        <?php } ?>

                                        <?php if(count($riwayat_owner_motor) > 0){ ?>
                                            <h5><strong>Riwayat Pemilik Motor Ini</strong></h5>
                                            <table class="table table-bordered table-striped" style="font-size: 12px;">
                                                <thead>
                                                    <tr><th>Mulai</th><th>Sampai</th><th>Pemilik</th><th>Sumber</th></tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($riwayat_owner_motor as $rh){ ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($rh['tanggal_mulai']); ?></td>
                                                            <td><?php echo htmlspecialchars($rh['tanggal_akhir'] ?: 'Sekarang'); ?></td>
                                                            <td><code><?php echo htmlspecialchars($rh['nopelanggan']); ?></code> - <?php echo htmlspecialchars($rh['namapelanggan']); ?></td>
                                                            <td><span class="label label-xs"><?php echo htmlspecialchars($rh['sumber']); ?></span></td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-12 col-md-7">
                        <?php if($detail_motor && $total_blocker == 0){ ?>
                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title"><i class="fa fa-exchange"></i> Langkah 2: Lengkapi Formulir Pemilik Baru</h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <form method="post" action="kendaraan_pindah_tangan.php" onsubmit="return confirm('Apakah Anda yakin data pemindahan aset ini sudah valid?');">
                                        <input type="hidden" name="id_kendaraan" value="<?php echo (int)$detail_motor['id_kendaraan']; ?>" />
                                        <input type="hidden" name="nopelanggan_lama" value="<?php echo htmlspecialchars($detail_motor['nopelanggan'] ?: ''); ?>" />
                                        <input type="hidden" name="nopolisi" value="<?php echo htmlspecialchars($detail_motor['nopolisi']); ?>" />
                                        
                                        <div class="form-group">
                                            <label><strong>Pemilik Baru (Target Transfer)</strong></label>
                                            <input type="text" id="target_pelanggan" class="form-control" placeholder="Ketik nama / no member pelanggan baru..." required />
                                            <input type="hidden" id="nopelanggan_baru" name="nopelanggan_baru" required />
                                            <small class="text-muted">Cari pembeli motor menggunakan autocomplete.</small>
                                        </div>

                                        <div class="form-group" id="block_id_baru" style="display:none; background:#f4f9f4; padding:10px; border:1px solid #d6e9c6; border-radius:4px; margin-bottom:15px;">
                                            <label><strong>ID Pelanggan Terpilih:</strong></label>
                                            <input type="text" id="selected_id_display" class="form-control" readonly style="background:#fff; color:#3c763d; font-weight:bold; font-family:monospace;" />
                                        </div>

                                        <div class="form-group">
                                            <label><strong>Ketik Ulang Kode Pelanggan Baru (Konfirmasi Keamanan)</strong></label>
                                            <input type="text" id="confirm_target" name="confirm_target" class="form-control" placeholder="Ketik ulang kode member baru diatas" required autocomplete="off" />
                                            <small class="text-muted">Salin persis kode member yang muncul setelah memilih di atas.</small>
                                        </div>

                                        <div class="form-group">
                                            <label><strong>Alasan Jual Beli / Pindah Tangan</strong></label>
                                            <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Unit terjual ke pemilik baru, melampirkan kuitansi jual beli." required></textarea>
                                        </div>

                                        <button type="submit" name="btnsubmit_request" class="btn btn-success"><i class="fa fa-check"></i> Kirim Permohonan Pindah Kepemilikan</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php } else { ?>
                            <div class="alert alert-warning">Cari nomor polisi kendaraan terlebih dahulu pada panel kiri untuk mengaktifkan formulir pindah tangan.</div>
                        <?php } ?>
                    </div>
                </div>

                <div class="row" style="margin-top:20px;">
                    <div class="col-xs-12">
                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title"><i class="fa fa-history"></i> Log Pengajuan Pindah Kepemilikan Terbaru</h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main no-padding">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Nopol</th>
                                                <th>Pemilik Lama</th>
                                                <th>Pemilik Baru</th>
                                                <th>Pengaju</th>
                                                <th>Status</th>
                                                <th>Eksekusi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($riwayat) > 0){ foreach($riwayat as $r){ ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                                                    <td><code><?php echo htmlspecialchars($r['nopolisi_snapshot']); ?></code></td>
                                                    <td><?php echo htmlspecialchars($r['nama_lama']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['nama_baru']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['pembuat']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $badge = 'label-default';
                                                        if($r['status'] == 'diajukan') $badge = 'label-warning';
                                                        if($r['status'] == 'dieksekusi') $badge = 'label-success';
                                                        if($r['status'] == 'ditolak') $badge = 'label-danger';
                                                        ?>
                                                        <span class="label <?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($r['status'])); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($r['executed_at'] ?: '-'); ?></td>
                                                </tr>
                                            <?php } } else { ?>
                                                <tr><td colspan="7" class="text-center">Belum ada riwayat pengajuan.</td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/jquery-ui.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>

<script>
$(document).ready(function() {
    $("#target_pelanggan").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "ajax-autocomplete.php",
                dataType: "json",
                data: {
                    source: "pelanggan",
                    q: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $("#target_pelanggan").val(ui.item.label);
            $("#nopelanggan_baru").val(ui.item.value);
            $("#selected_id_display").val(ui.item.value);
            $("#block_id_baru").fadeIn(200);
            return false;
        }
    });
});
</script>
</body>
</html>
