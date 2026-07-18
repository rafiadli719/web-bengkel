<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
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

$can_approve = $is_admin;
if(function_exists('rbac_has')){
    $can_approve = $can_approve || rbac_has('issue_approve_supervisor');
}
if(!$can_approve){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(403);
    die('Halaman ini khusus supervisor/admin yang berwenang menyetujui pemindahan aset.');
}

$message = '';
$error_class = 'alert-info';

// Proses Persetujuan & Eksekusi Pindah Tangan
if(isset($_POST['btnapprove']) && !empty($_POST['id'])){
    $id = (int)$_POST['id'];
    
    $q_req = mysqli_query($koneksi, "SELECT * FROM permintaan_pindah_kepemilikan_kendaraan WHERE id={$id} AND status='diajukan'");
    $req = $q_req ? mysqli_fetch_assoc($q_req) : null;
    
    if(!$req){
        $message = 'Pengajuan tidak ditemukan atau sudah diproses.'; $error_class = 'alert-danger';
    } else {
        $confirm_target = trim((string)$_POST['confirm_target']);
        if($confirm_target !== $req['nopelanggan_baru']){
            $message = 'Konfirmasi gagal. Ketik ulang kode pelanggan baru dengan benar.'; $error_class = 'alert-danger';
        } else {
            $id_kendaraan = (int)$req['id_kendaraan'];
            $old_owner = mysqli_real_escape_string($koneksi, $req['nopelanggan_lama']);
            $new_owner = mysqli_real_escape_string($koneksi, $req['nopelanggan_baru']);
            $nopol = mysqli_real_escape_string($koneksi, $req['nopolisi_snapshot']);
            
            // Re-check Blocker: Pastikan tidak ada servis aktif di detik eksekusi ini
            $q_block = mysqli_query($koneksi, "
                SELECT COUNT(*) AS total_blocker 
                FROM tblservice 
                WHERE no_polisi='{$nopol}' 
                  AND status_servis IN ('datang','diproses','selesai')
            ");
            $block = mysqli_fetch_assoc($q_block);
            
            if($block && (int)$block['total_blocker'] > 0){
                $message = 'Eksekusi gagal. Kendaraan saat ini sedang masuk antrian servis aktif.';
                $error_class = 'alert-danger';
            } else {
                mysqli_begin_transaction($koneksi);
                try {
                    // 1. Tutup kepemilikan lama
                    $q_upd_old = mysqli_query($koneksi, "
                        UPDATE kepemilikan_kendaraan 
                        SET tanggal_akhir=CURDATE(), is_current=0 
                        WHERE id_kendaraan={$id_kendaraan} AND nopelanggan='{$old_owner}' AND is_current=1
                    ");
                    if(!$q_upd_old) throw new Exception('Gagal menonaktifkan pemilik lama.');
                    
                    // 2. Buat kepemilikan baru
                    $q_ins_new = mysqli_query($koneksi, "
                        INSERT INTO kepemilikan_kendaraan (id_kendaraan, nopelanggan, tanggal_mulai, is_current, sumber, diinput_oleh) 
                        VALUES ({$id_kendaraan}, '{$new_owner}', CURDATE(), 1, 'jual_beli', {$id_user})
                    ");
                    if(!$q_ins_new) throw new Exception('Gagal mendaftarkan pemilik baru.');
                    
                    // 3. Update master tblkendaraan (pemilik legacy column sync)
                    $q_upd_legacy = mysqli_query($koneksi, "
                        UPDATE tblkendaraan 
                        SET pemilik='{$new_owner}' 
                        WHERE id_kendaraan={$id_kendaraan}
                    ");
                    if(!$q_upd_legacy) throw new Exception('Gagal sinkronisasi data master legacy.');
                    
                    // 4. Update denormalized owner di statistik_kendaraan
                    $q_upd_stat = mysqli_query($koneksi, "
                        UPDATE statistik_kendaraan 
                        SET nopelanggan_current='{$new_owner}' 
                        WHERE id_kendaraan={$id_kendaraan}
                    ");
                    if(!$q_upd_stat) throw new Exception('Gagal memperbarui owner pada tabel statistik.');
                    
                    // 5. Update request status ke 'dieksekusi'
                    $q_upd_req = mysqli_query($koneksi, "
                        UPDATE permintaan_pindah_kepemilikan_kendaraan 
                        SET status='dieksekusi', disetujui_oleh={$id_user}, dieksekusi_oleh={$id_user}, approved_at=NOW(), executed_at=NOW() 
                        WHERE id={$id}
                    ");
                    if(!$q_upd_req) throw new Exception('Gagal menyimpan log persetujuan.');
                    
                    mysqli_commit($koneksi);
                    $message = 'Pemindahan kepemilikan kendaraan berhasil disetujui & dieksekusi.';
                    $error_class = 'alert-success';
                } catch(Exception $e){
                    mysqli_rollback($koneksi);
                    $message = 'Gagal memproses transaksi database: '.$e->getMessage();
                    $error_class = 'alert-danger';
                }
            }
        }
    }
}

// Proses Penolakan
if(isset($_POST['btnreject']) && !empty($_POST['id'])){
    $id = (int)$_POST['id'];
    $catatan = mysqli_real_escape_string($koneksi, trim((string)($_POST['catatan_internal'] ?? '')));
    
    $q_upd = mysqli_query($koneksi, "
        UPDATE permintaan_pindah_kepemilikan_kendaraan 
        SET status='ditolak', disetujui_oleh={$id_user}, catatan_internal='{$catatan}' 
        WHERE id={$id} AND status='diajukan'
    ");
    if($q_upd){
        $message = 'Permohonan berhasil ditolak.'; $error_class = 'alert-warning';
    } else {
        $message = 'Gagal menyimpan penolakan.'; $error_class = 'alert-danger';
    }
}

// Helper detail info pelanggan untuk review atasan
function get_pelanggan_info($koneksi, $nopelanggan){
    $esc = mysqli_real_escape_string($koneksi, $nopelanggan);
    $q = mysqli_query($koneksi, "
        SELECT p.nopelanggan, p.namapelanggan, p.no_wa, p.alamat, p.notlp, s.status_member
        FROM tblpelanggan p
        LEFT JOIN statistik_pelanggan s ON s.no_pelanggan=p.nopelanggan
        WHERE p.nopelanggan='{$esc}'
    ");
    return $q ? mysqli_fetch_assoc($q) : null;
}

// Ambil antrian pengajuan aktif
$antrian = [];
$qa = mysqli_query($koneksi, "
    SELECT r.*, k.nopolisi, k.tipe, k.warna, u.nama_user AS pembuat
    FROM permintaan_pindah_kepemilikan_kendaraan r
    JOIN tblkendaraan k ON k.id_kendaraan = r.id_kendaraan
    JOIN tbuser u ON u.id = r.dibuat_oleh
    WHERE r.status='diajukan'
    ORDER BY r.created_at ASC
");
if($qa){ 
    while($row = mysqli_fetch_assoc($qa)){ 
        $row['owner_lama_info'] = get_pelanggan_info($koneksi, $row['nopelanggan_lama']);
        $row['owner_baru_info'] = get_pelanggan_info($koneksi, $row['nopelanggan_baru']);
        $antrian[] = $row; 
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Persetujuan Pindah Kepemilikan - <?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
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
                    <li class="active">Persetujuan Pindah Kepemilikan</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Persetujuan Pindah Kepemilikan Kendaraan <small><i class="ace-icon fa fa-angle-double-right"></i> Review Dokumen & Approval Supervisor</small></h1>
                </div>

                <?php if($message != ''){ echo '<div class="alert '.$error_class.'">'.htmlspecialchars($message).'</div>'; } ?>

                <div class="row">
                    <div class="col-xs-12">
                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title"><i class="fa fa-list"></i> Antrian Permohonan Menunggu Persetujuan</h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <?php if(count($antrian) > 0){ foreach($antrian as $item){ ?>
                                        <div class="well" style="background:#fcfcfc; border:1px solid #ddd; margin-bottom: 20px; padding:15px;">
                                            <div class="row">
                                                <div class="col-xs-12 col-md-4">
                                                    <h5><strong>Detail Aset Kendaraan</strong></h5>
                                                    <table class="table table-bordered">
                                                        <tr><th>Nopol</th><td><span class="label label-success"><strong><?php echo htmlspecialchars($item['nopolisi']); ?></strong></span></td></tr>
                                                        <tr><th>Unit</th><td><?php echo htmlspecialchars($item['tipe'] . ' (' . $item['warna'] . ')'); ?></td></tr>
                                                        <tr><th>Diajukan Oleh</th><td><?php echo htmlspecialchars($item['pembuat'] . ' / ' . $item['created_at']); ?></td></tr>
                                                    </table>
                                                </div>
                                                <div class="col-xs-12 col-md-4">
                                                    <h5><strong>Perbandingan Profil Pelanggan</strong></h5>
                                                    <table class="table table-bordered">
                                                        <tr class="danger">
                                                            <th>Pemilik Lama (Out)</th>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($item['nopelanggan_lama']); ?></code><br>
                                                                <strong><?php echo htmlspecialchars($item['owner_lama_info']['namapelanggan'] ?? 'Legacy / Tidak Terdaftar'); ?></strong><br>
                                                                <small class="text-muted">
                                                                    WA: <?php echo htmlspecialchars($item['owner_lama_info']['no_wa'] ?? '-'); ?><br>
                                                                    Alamat: <?php echo htmlspecialchars($item['owner_lama_info']['alamat'] ?? '-'); ?>
                                                                </small>
                                                            </td>
                                                        </tr>
                                                        <tr class="success">
                                                            <th>Pemilik Baru (In)</th>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($item['nopelanggan_baru']); ?></code><br>
                                                                <strong><?php echo htmlspecialchars($item['owner_baru_info']['namapelanggan'] ?? 'Legacy / Tidak Terdaftar'); ?></strong><br>
                                                                <small class="text-muted">
                                                                    WA: <?php echo htmlspecialchars($item['owner_baru_info']['no_wa'] ?? '-'); ?><br>
                                                                    Alamat: <?php echo htmlspecialchars($item['owner_baru_info']['alamat'] ?? '-'); ?><br>
                                                                    Status Member: <span class="label label-xs label-purple"><?php echo htmlspecialchars($item['owner_baru_info']['status_member'] ?? 'Non-Member'); ?></span>
                                                                </small>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-xs-12 col-md-4">
                                                    <h5><strong>Verifikasi & Konfirmasi</strong></h5>
                                                    <p><strong>Alasan Jual Beli:</strong> <?php echo nl2br(htmlspecialchars($item['alasan'])); ?></p>
                                                    <hr style="margin:10px 0;">
                                                    
                                                    <form method="post" action="kendaraan_pindah_tangan_approve.php" class="form-inline">
                                                        <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>" />
                                                        
                                                        <div class="form-group" style="display:block; margin-bottom: 10px;">
                                                            <label style="display:block; text-align:left;">Ketik Ulang Kode Target Baru</label>
                                                            <input type="text" name="confirm_target" class="form-control" placeholder="<?php echo htmlspecialchars($item['nopelanggan_baru']); ?>" required style="width:100%;" />
                                                            <span class="help-block small text-muted">Ketik persis ID member baru di atas.</span>
                                                        </div>
                                                        
                                                        <div class="form-group" style="display:block; margin-bottom: 10px;">
                                                            <input type="text" name="catatan_internal" class="form-control" placeholder="Catatan internal jika menolak..." style="width:100%;" />
                                                        </div>
                                                        
                                                        <button type="submit" name="btnapprove" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Setujui & Eksekusi</button>
                                                        <button type="submit" name="btnreject" class="btn btn-xs btn-danger"><i class="fa fa-times"></i> Tolak</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } } else { ?>
                                        <div class="alert alert-warning text-center" style="margin-bottom:0;">Tidak ada antrian pengajuan pindah kepemilikan yang menunggu saat ini.</div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
