<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);
if(!isset($koneksi) || !$koneksi){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die('DB connection failed: '.mysqli_connect_error());
}
@mysqli_set_charset($koneksi, 'utf8mb4');
if(function_exists('mysqli_report')){ @mysqli_report(MYSQLI_REPORT_OFF); }

$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari && isset($tm_cari['nama_user']) ? $tm_cari['nama_user'] : 'admin';
$foto_user = $tm_cari && isset($tm_cari['foto_user']) ? $tm_cari['foto_user'] : '';
if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

$message = '';
$kd_cabang_esc = mysqli_real_escape_string($koneksi, $kd_cabang);

// Generate draft PR dari item terpilih
if(isset($_POST['btngenerate']) && !empty($_POST['pilih'])){
    $items = $_POST['pilih'];
    $tgl_now = date('Y-m-d');
    $no_gen = '';
    if (mysqli_query($koneksi, "CALL sp_generate_no_pr('".$kd_cabang_esc."', @p_no_pr)")) {
        mysqli_next_result($koneksi);
        $res = mysqli_query($koneksi, "SELECT @p_no_pr as no_pr");
        if ($res) { $row = mysqli_fetch_assoc($res); $no_gen = $row['no_pr']; }
    }
    if ($no_gen == '') {
        $no_gen = 'PR'.date('Ym').$kd_cabang.str_pad(rand(1,99999),5,'0',STR_PAD_LEFT);
    }
    mysqli_query($koneksi, "INSERT INTO tblpurchase_request_header
        (no_pr, tanggal_pr, tanggal_butuh, requester, departemen, alasan, kd_cabang, created_by, status_pr)
        VALUES
        ('{$no_gen}', '{$tgl_now}', '{$tgl_now}', '{$_nama}', 'Gudang', 'Auto-generated: stok di bawah minimal', '{$kd_cabang_esc}', '{$_nama}', 'draft')");

    $nobaris = 1; $jml = 0;
    foreach($items as $encoded){
        // format: no_item|qty
        $parts = explode('|', $encoded, 2);
        if(count($parts)!=2) continue;
        $no_item = mysqli_real_escape_string($koneksi, $parts[0]);
        $qty = (int)$parts[1];
        if($qty<=0) continue;
        $qnm = mysqli_query($koneksi, "SELECT namaitem, satuan FROM tblitem WHERE noitem='{$no_item}'");
        $nm = $qnm ? mysqli_fetch_array($qnm) : null;
        $nama_item = $nm && isset($nm['namaitem']) ? $nm['namaitem'] : $no_item;
        $satuan = $nm && isset($nm['satuan']) ? $nm['satuan'] : '';
        mysqli_query($koneksi, "INSERT INTO tblpurchase_request_detail
            (no_pr, nobaris, no_item, nama_item, quantity, qty_approved, qty_po, satuan, harga_estimasi, total_estimasi, keterangan)
            VALUES
            ('{$no_gen}', {$nobaris}, '{$no_item}', '".mysqli_real_escape_string($koneksi,$nama_item)."', {$qty}, 0, 0, '".mysqli_real_escape_string($koneksi,$satuan)."', 0, 0, 'Auto-generated dari cek stok minimal')");
        $nobaris++; $jml++;
    }
    if($jml>0){
        header("Location: pr_add.php?no_pr=".urlencode($no_gen));
        exit;
    }
    $message = 'Tidak ada item valid untuk dibuatkan draft PR.';
}

// Item yang statusnya sudah punya PR aktif (draft/submitted/approved) untuk cabang ini - cegah draft dobel
$sudah_di_pr = [];
$qex = mysqli_query($koneksi, "SELECT DISTINCT d.no_item
    FROM tblpurchase_request_detail d
    JOIN tblpurchase_request_header h ON h.no_pr = d.no_pr
    WHERE h.kd_cabang='{$kd_cabang_esc}' AND h.status_pr IN ('draft','submitted','approved')
    AND d.status_item != 'po_created'");
if($qex){ while($r = mysqli_fetch_assoc($qex)){ $sudah_di_pr[$r['no_item']] = true; } }

// Hitung stok aktual per item untuk cabang ini, bandingkan ke stokmin
$low_stock = [];
$q = mysqli_query($koneksi, "
    SELECT s.noitem, s.stokmin, s.stok_maks,
           COALESCE((SELECT SUM(t.masuk)-SUM(t.keluar) FROM tbstok t WHERE t.no_item=s.noitem AND t.kd_cabang=s.kode_cabang),0) AS stok_aktual,
           i.namaitem
    FROM tblitem_stok s
    LEFT JOIN tblitem i ON i.noitem = s.noitem
    WHERE s.kode_cabang='{$kd_cabang_esc}' AND s.stokmin > 0
");
if($q){
    while($r = mysqli_fetch_assoc($q)){
        if((int)$r['stok_aktual'] < (int)$r['stokmin']){
            $low_stock[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only">Toggle sidebar</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <div class="navbar-header pull-left">
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i><?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                        <span class="user-info"><small>Welcome,</small><?php echo htmlspecialchars($_nama); ?></span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                        <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i>Change Password</a></li>
                        <li><a href="profile.php"><i class="ace-icon fa fa-user"></i>Profile</a></li>
                        <li class="divider"></li>
                        <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
        <?php include "menu_dashboard.php"; ?>
        <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="#">Pembelian</a></li>
                    <li class="active">Cek Stok Minimal</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row">
                    <div class="col-xs-12">
                        <?php if($message!=''){ echo '<div class="alert alert-warning">'.htmlspecialchars($message).'</div>'; } ?>
                        <div class="widget-box">
                            <div class="widget-header widget-header-blue widget-header-flat">
                                <h4 class="widget-title lighter"><i class="ace-icon fa fa-exclamation-circle"></i> Item Stok Di Bawah Minimal — Cabang <?php echo htmlspecialchars($kd_cabang); ?></h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <?php if(count($low_stock)==0){ ?>
                                        <div class="alert alert-success">Tidak ada item yang stoknya di bawah batas minimal untuk cabang ini.</div>
                                    <?php } else { ?>
                                    <form method="post" action="pr_auto_draft.php">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th style="width:30px;"><input type="checkbox" id="chkall" /></th>
                                                    <th>Kode Item</th>
                                                    <th>Nama Item</th>
                                                    <th class="text-right">Stok Aktual</th>
                                                    <th class="text-right">Stok Min</th>
                                                    <th class="text-right">Stok Maks</th>
                                                    <th class="text-right">Qty Diminta</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach($low_stock as $r){
                                                $qty_diminta = max(1, (int)$r['stok_maks'] - (int)$r['stok_aktual']);
                                                $sudah_ada = isset($sudah_di_pr[$r['noitem']]);
                                                $enc = htmlspecialchars($r['noitem'].'|'.$qty_diminta);
                                            ?>
                                                <tr>
                                                    <td><input type="checkbox" name="pilih[]" value="<?php echo $enc; ?>" <?php echo $sudah_ada ? 'disabled' : ''; ?> /></td>
                                                    <td><?php echo htmlspecialchars($r['noitem']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['namaitem'] ?? $r['noitem']); ?></td>
                                                    <td class="text-right"><?php echo (int)$r['stok_aktual']; ?></td>
                                                    <td class="text-right"><?php echo (int)$r['stokmin']; ?></td>
                                                    <td class="text-right"><?php echo (int)$r['stok_maks']; ?></td>
                                                    <td class="text-right"><?php echo $qty_diminta; ?></td>
                                                    <td><?php echo $sudah_ada ? '<span class="label label-warning">Sudah ada PR aktif</span>' : '<span class="label label-default">Belum di-PR</span>'; ?></td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                        <button type="submit" name="btngenerate" class="btn btn-success"><i class="fa fa-magic"></i> Buat Draft PR dari Item Terpilih</button>
                                    </form>
                                    <script>
                                        document.getElementById('chkall').addEventListener('change', function(){
                                            var boxes = document.querySelectorAll('input[name="pilih[]"]:not(:disabled)');
                                            var self = this;
                                            boxes.forEach(function(b){ b.checked = self.checked; });
                                        });
                                    </script>
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
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
</body>
</html>
