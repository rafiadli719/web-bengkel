<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";

    // User data
    $stmt = mysqli_prepare($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id=?");
    mysqli_stmt_bind_param($stmt, "s", $id_user);
    mysqli_stmt_execute($stmt);
    $tm_cari = mysqli_fetch_array(mysqli_stmt_get_result($stmt));
    $_nama = $tm_cari['nama_user'] ?? '';
    $foto_user = $tm_cari['foto_user'] ?? '';
    if($foto_user == '') {
        $foto_user = "file_upload/avatar.png";
    }

    // ========================================
    // AJAX: detail promo lengkap (target/cabang/syarat) untuk modal edit
    // ========================================
    if(isset($_GET['ajax_detail'])) {
        header('Content-Type: application/json');
        $id_promo = (int)$_GET['ajax_detail'];

        $stmt = mysqli_prepare($koneksi, "SELECT * FROM master_diskon_periode WHERE id_promo=?");
        mysqli_stmt_bind_param($stmt, "i", $id_promo);
        mysqli_stmt_execute($stmt);
        $header = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        $stmt = mysqli_prepare($koneksi, "SELECT target_type, target_id, target_nama FROM master_diskon_periode_target WHERE id_promo=?");
        mysqli_stmt_bind_param($stmt, "i", $id_promo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $targets = [];
        while($row = mysqli_fetch_assoc($res)) { $targets[] = $row; }

        $stmt = mysqli_prepare($koneksi, "SELECT kd_cabang FROM master_diskon_periode_cabang WHERE id_promo=?");
        mysqli_stmt_bind_param($stmt, "i", $id_promo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $cabangs = [];
        while($row = mysqli_fetch_assoc($res)) { $cabangs[] = $row['kd_cabang']; }

        $stmt = mysqli_prepare($koneksi, "SELECT jenis_syarat, operator, nilai, rolling_hari FROM master_diskon_periode_syarat WHERE id_promo=?");
        mysqli_stmt_bind_param($stmt, "i", $id_promo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $syarat = [];
        while($row = mysqli_fetch_assoc($res)) { $syarat[] = $row; }

        echo json_encode(['header' => $header, 'targets' => $targets, 'cabangs' => $cabangs, 'syarat' => $syarat]);
        exit;
    }

    // ========================================
    // HANDLE CRUD OPERATIONS
    // ========================================
    $message = '';
    $message_type = '';

    $table_check = mysqli_query($koneksi, "SHOW TABLES LIKE 'master_diskon_periode'");
    $table_exists = mysqli_num_rows($table_check) > 0;

    function simpanTargetCabangSyarat($koneksi, $id_promo, $post) {
        $target_type_arr = $post['target_type'] ?? [];
        $target_id_arr = $post['target_id'] ?? [];
        $target_nama_arr = $post['target_nama'] ?? [];
        $kd_cabang_arr = $post['kd_cabang_list'] ?? [];
        $jenis_syarat_arr = $post['jenis_syarat'] ?? [];
        $operator_arr = $post['operator'] ?? [];
        $nilai_syarat_arr = $post['nilai_syarat'] ?? [];
        $rolling_hari_arr = $post['rolling_hari'] ?? [];

        $stmt_t = mysqli_prepare($koneksi, "INSERT INTO master_diskon_periode_target (id_promo, target_type, target_id, target_nama) VALUES (?,?,?,?)");
        foreach($target_type_arr as $i => $tt) {
            $tid = trim($target_id_arr[$i] ?? '');
            if($tid === '') continue;
            $tnama = $target_nama_arr[$i] ?? '';
            mysqli_stmt_bind_param($stmt_t, "isss", $id_promo, $tt, $tid, $tnama);
            mysqli_stmt_execute($stmt_t);
        }

        $stmt_c = mysqli_prepare($koneksi, "INSERT INTO master_diskon_periode_cabang (id_promo, kd_cabang) VALUES (?,?)");
        foreach($kd_cabang_arr as $kc) {
            if($kc === '') continue;
            mysqli_stmt_bind_param($stmt_c, "is", $id_promo, $kc);
            mysqli_stmt_execute($stmt_c);
        }

        $stmt_s = mysqli_prepare($koneksi, "INSERT INTO master_diskon_periode_syarat (id_promo, jenis_syarat, operator, nilai, rolling_hari) VALUES (?,?,?,?,?)");
        foreach($jenis_syarat_arr as $i => $js) {
            $nl = trim($nilai_syarat_arr[$i] ?? '');
            if($js === '' || $nl === '') continue;
            $op = $operator_arr[$i] ?? '>=';
            $rh = ($js === 'jumlah_kunjungan') ? (int)($rolling_hari_arr[$i] ?? 0) : null;
            mysqli_stmt_bind_param($stmt_s, "isssi", $id_promo, $js, $op, $nl, $rh);
            mysqli_stmt_execute($stmt_s);
        }
    }

    // ADD
    if(isset($_POST['btn_simpan']) && $table_exists) {
        $nama_promo = trim($_POST['nama_promo']);
        $keterangan = trim($_POST['keterangan'] ?? '');
        $tipe_promo = ($_POST['tipe_promo'] === 'nominal') ? 'nominal' : 'persen';
        $nilai_promo = floatval($_POST['nilai_promo']);
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        $stackable = isset($_POST['stackable']) ? 1 : 0;
        $boleh_gabung_diskon_member = isset($_POST['boleh_gabung_diskon_member']) ? 1 : 0;
        $mode_syarat = ($_POST['mode_syarat'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
        $has_target = !empty(array_filter($_POST['target_id'] ?? []));

        if($nama_promo === '' || !$has_target) {
            $message = 'Nama promo dan minimal 1 target (item/jasa) wajib diisi.';
            $message_type = 'danger';
        } else if($tanggal_selesai < $tanggal_mulai) {
            $message = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
            $message_type = 'danger';
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO master_diskon_periode
                    (nama_promo, keterangan, tipe_promo, nilai_promo, tanggal_mulai, tanggal_selesai, stackable, boleh_gabung_diskon_member, mode_syarat, status_aktif)
                    VALUES (?,?,?,?,?,?,?,?,?,1)");
            mysqli_stmt_bind_param($stmt, "sssdssiis", $nama_promo, $keterangan, $tipe_promo, $nilai_promo, $tanggal_mulai, $tanggal_selesai, $stackable, $boleh_gabung_diskon_member, $mode_syarat);

            if(mysqli_stmt_execute($stmt)) {
                $id_promo = mysqli_insert_id($koneksi);
                simpanTargetCabangSyarat($koneksi, $id_promo, $_POST);
                $message = 'Promo berhasil ditambahkan!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($koneksi);
                $message_type = 'danger';
            }
        }
    }

    // EDIT
    if(isset($_POST['btn_update']) && $table_exists) {
        $id_promo = (int)$_POST['id_promo'];
        $nama_promo = trim($_POST['nama_promo']);
        $keterangan = trim($_POST['keterangan'] ?? '');
        $tipe_promo = ($_POST['tipe_promo'] === 'nominal') ? 'nominal' : 'persen';
        $nilai_promo = floatval($_POST['nilai_promo']);
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
        $stackable = isset($_POST['stackable']) ? 1 : 0;
        $boleh_gabung_diskon_member = isset($_POST['boleh_gabung_diskon_member']) ? 1 : 0;
        $mode_syarat = ($_POST['mode_syarat'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
        $has_target = !empty(array_filter($_POST['target_id'] ?? []));

        if($nama_promo === '' || !$has_target) {
            $message = 'Nama promo dan minimal 1 target (item/jasa) wajib diisi.';
            $message_type = 'danger';
        } else if($tanggal_selesai < $tanggal_mulai) {
            $message = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
            $message_type = 'danger';
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE master_diskon_periode SET
                    nama_promo=?, keterangan=?, tipe_promo=?, nilai_promo=?, tanggal_mulai=?, tanggal_selesai=?,
                    status_aktif=?, stackable=?, boleh_gabung_diskon_member=?, mode_syarat=?
                    WHERE id_promo=?");
            mysqli_stmt_bind_param($stmt, "sssdssiiisi", $nama_promo, $keterangan, $tipe_promo, $nilai_promo, $tanggal_mulai, $tanggal_selesai, $status_aktif, $stackable, $boleh_gabung_diskon_member, $mode_syarat, $id_promo);

            if(mysqli_stmt_execute($stmt)) {
                foreach(['master_diskon_periode_target', 'master_diskon_periode_cabang', 'master_diskon_periode_syarat'] as $child_table) {
                    $stmt_del = mysqli_prepare($koneksi, "DELETE FROM $child_table WHERE id_promo=?");
                    mysqli_stmt_bind_param($stmt_del, "i", $id_promo);
                    mysqli_stmt_execute($stmt_del);
                }
                simpanTargetCabangSyarat($koneksi, $id_promo, $_POST);
                $message = 'Promo berhasil diupdate!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($koneksi);
                $message_type = 'danger';
            }
        }
    }

    // DELETE
    if(isset($_POST['btn_hapus']) && $table_exists) {
        $id_promo = (int)$_POST['id_promo'];

        $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) c FROM promo_usage_log WHERE id_promo=?");
        mysqli_stmt_bind_param($stmt, "i", $id_promo);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if(((int)$row['c']) > 0) {
            $message = 'Promo ini sudah pernah dipakai di transaksi — tidak bisa dihapus (histori pemakaian akan hilang). Nonaktifkan saja lewat tombol pause.';
            $message_type = 'danger';
        } else {
            $stmt = mysqli_prepare($koneksi, "DELETE FROM master_diskon_periode WHERE id_promo=?");
            mysqli_stmt_bind_param($stmt, "i", $id_promo);
            if(mysqli_stmt_execute($stmt)) {
                $message = 'Promo berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($koneksi);
                $message_type = 'danger';
            }
        }
    }

    // TOGGLE STATUS
    if(isset($_GET['toggle']) && $table_exists) {
        $id_promo = (int)$_GET['toggle'];
        $stmt = mysqli_prepare($koneksi, "UPDATE master_diskon_periode SET status_aktif = NOT status_aktif WHERE id_promo=?");
        mysqli_stmt_bind_param($stmt, "i", $id_promo);
        mysqli_stmt_execute($stmt);
        header("Location: master-diskon-periode.php");
        exit;
    }

    // Daftar cabang untuk checkbox scope
    $daftar_cabang = [];
    $res_cabang = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY nama_cabang ASC");
    while($row = mysqli_fetch_assoc($res_cabang)) { $daftar_cabang[] = $row; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Diskon Periode</title>
    <meta name="description" content="Master Diskon Periode / Promo" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .promo-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 18px; margin-bottom: 18px; background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%); transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .promo-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .promo-card.expired { opacity: 0.55; background: #f5f5f5; filter: grayscale(30%); }
        .promo-card.active-now { border-left: 5px solid #27ae60; background: linear-gradient(145deg, #ffffff 0%, #f0fff0 100%); }
        .promo-card.upcoming { border-left: 5px solid #f39c12; background: linear-gradient(145deg, #ffffff 0%, #fffdf0 100%); }
        .promo-badge { font-size: 24px; font-weight: 700; line-height: 1.2; }
        .target-badge { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin: 2px 2px 2px 0; }
        .target-workorder { background: #3498db; color: #fff; }
        .target-jasa { background: #27ae60; color: #fff; }
        .target-barang { background: #f39c12; color: #fff; }
        .date-range { font-size: 12px; color: #888; margin-top: 10px; }
        .btn-action { padding: 4px 10px; font-size: 12px; border-radius: 4px; margin-left: 2px; }
        .flag-badge { font-size: 10px; margin-right: 4px; }
        .modal-header-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .modal-header-custom .close { color: #fff; opacity: 0.8; }
        .form-section { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; border: 1px solid #e9ecef; }
        .form-section-title { font-size: 12px; font-weight: 600; color: #495057; margin-bottom: 10px; text-transform: uppercase; }
        .help-text { font-size: 11px; color: #6c757d; margin-top: 4px; }
        .target-row, .syarat-row { border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 28px 8px 8px; margin-bottom: 8px; background: #fff; position: relative; }
        .row-remove-btn { position: absolute; top: 4px; right: 4px; z-index: 2; }
        .target-row .btn-block { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left; }
        .cabang-checklist { max-height: 160px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px; background: #fff; }
        .search-result-list { max-height: 300px; overflow-y: auto; }
        .search-result-item { padding: 8px 12px; border-bottom: 1px solid #eee; cursor: pointer; }
        .search-result-item:hover { background: #f0f7ff; }
        .search-result-item.selected { background: #d4edda; }
    </style>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </button>
            <div class="navbar-header pull-left">
                <table><tr><td width="20%"><a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small></a></td><td></td></tr></table>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo htmlspecialchars($foto_user); ?>" alt="User Profil" />
                            <span class="user-info"><small>Welcome,</small> <?php echo htmlspecialchars($_nama); ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-header pull-right"><a href="#" class="navbar-brand"><small></small></a></div>
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
                        <li><a href="#">Data Master</a></li>
                        <li class="active">Diskon Periode / Promo</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1><i class="fa fa-gift"></i> Master Diskon Periode <small><i class="ace-icon fa fa-angle-double-right"></i> Kelola Promo &amp; Diskon Periode</small></h1>
                    </div>

                    <?php if(!$table_exists): ?>
                    <div class="alert alert-danger"><h4><i class="fa fa-warning"></i> Tabel Belum Dibuat</h4><p>Jalankan migrasi <code>db/migrations/2026-07-18_promo_engine_multi_target_cabang_syarat.sql</code>.</p></div>
                    <?php else: ?>

                    <?php if($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-list"></i> Daftar Promo</h4>
                                    <div class="widget-toolbar">
                                        <button class="btn btn-success btn-sm" onclick="openAddModal()"><i class="fa fa-plus"></i> Tambah Promo</button>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i>
                                            <strong>Promo fleksibel:</strong> 1 promo bisa punya banyak target (campur jasa+barang), banyak cabang, dan banyak syarat kelayakan (AND/OR). Kosongkan cabang = berlaku semua cabang. Kosongkan syarat = berlaku semua customer.
                                        </div>

                                        <div class="row">
                                        <?php
                                        $today = date('Y-m-d');
                                        $query = mysqli_query($koneksi, "SELECT * FROM master_diskon_periode ORDER BY tanggal_mulai DESC, id_promo DESC");

                                        if($query && mysqli_num_rows($query) > 0):
                                            while($row = mysqli_fetch_assoc($query)):
                                                $id_promo_row = (int)$row['id_promo'];
                                                $is_active_now = ($row['status_aktif'] == 1 && $today >= $row['tanggal_mulai'] && $today <= $row['tanggal_selesai']);
                                                $is_upcoming = ($row['status_aktif'] == 1 && $today < $row['tanggal_mulai']);
                                                $is_expired = ($today > $row['tanggal_selesai']);
                                                $card_class = '';
                                                if($is_expired) $card_class = 'expired';
                                                elseif($is_active_now) $card_class = 'active-now';
                                                elseif($is_upcoming) $card_class = 'upcoming';

                                                $stmt_t = mysqli_prepare($koneksi, "SELECT target_type, target_id, target_nama FROM master_diskon_periode_target WHERE id_promo=?");
                                                mysqli_stmt_bind_param($stmt_t, "i", $id_promo_row);
                                                mysqli_stmt_execute($stmt_t);
                                                $targets_row = mysqli_fetch_all(mysqli_stmt_get_result($stmt_t), MYSQLI_ASSOC);

                                                $stmt_cb = mysqli_prepare($koneksi, "SELECT COUNT(*) c FROM master_diskon_periode_cabang WHERE id_promo=?");
                                                mysqli_stmt_bind_param($stmt_cb, "i", $id_promo_row);
                                                mysqli_stmt_execute($stmt_cb);
                                                $jml_cabang = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cb))['c'] ?? 0);

                                                $stmt_sy = mysqli_prepare($koneksi, "SELECT COUNT(*) c FROM master_diskon_periode_syarat WHERE id_promo=?");
                                                mysqli_stmt_bind_param($stmt_sy, "i", $id_promo_row);
                                                mysqli_stmt_execute($stmt_sy);
                                                $jml_syarat = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sy))['c'] ?? 0);
                                        ?>
                                            <div class="col-md-6">
                                                <div class="promo-card <?php echo $card_class; ?>">
                                                    <div class="row">
                                                        <div class="col-xs-8">
                                                            <h4 style="margin-top:0;">
                                                                <?php echo htmlspecialchars($row['nama_promo']); ?>
                                                                <?php if(!$row['status_aktif']): ?><span class="label label-default">Nonaktif</span>
                                                                <?php elseif($is_active_now): ?><span class="label label-success">Aktif</span>
                                                                <?php elseif($is_upcoming): ?><span class="label label-warning">Akan Datang</span>
                                                                <?php elseif($is_expired): ?><span class="label label-default">Berakhir</span>
                                                                <?php endif; ?>
                                                            </h4>
                                                            <p class="text-muted"><?php echo htmlspecialchars($row['keterangan'] ?? ''); ?></p>
                                                            <div>
                                                                <?php foreach($targets_row as $t): ?>
                                                                    <span class="target-badge target-<?php echo htmlspecialchars($t['target_type']); ?>"><?php echo strtoupper(htmlspecialchars($t['target_type'])); ?></span>
                                                                <?php endforeach; ?>
                                                                <small class="text-muted"><?php echo count($targets_row); ?> target</small>
                                                            </div>
                                                            <div style="margin-top:6px;">
                                                                <span class="label label-<?php echo $jml_cabang > 0 ? 'info' : 'default'; ?> flag-badge"><i class="fa fa-map-marker"></i> <?php echo $jml_cabang > 0 ? $jml_cabang.' Cabang' : 'Semua Cabang'; ?></span>
                                                                <span class="label label-<?php echo $jml_syarat > 0 ? 'primary' : 'default'; ?> flag-badge"><i class="fa fa-filter"></i> <?php echo $jml_syarat > 0 ? $jml_syarat.' Syarat ('.$row['mode_syarat'].')' : 'Tanpa Syarat'; ?></span>
                                                                <?php if($row['stackable']): ?><span class="label label-success flag-badge">Stackable</span><?php endif; ?>
                                                                <?php if($row['boleh_gabung_diskon_member']): ?><span class="label label-success flag-badge">+ Member</span><?php endif; ?>
                                                            </div>
                                                            <div class="date-range">
                                                                <i class="fa fa-calendar"></i>
                                                                <?php echo date('d M Y', strtotime($row['tanggal_mulai'])); ?> - <?php echo date('d M Y', strtotime($row['tanggal_selesai'])); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-xs-4 text-right">
                                                            <div class="promo-badge text-<?php echo $row['tipe_promo'] == 'persen' ? 'success' : 'primary'; ?>">
                                                                <?php echo $row['tipe_promo'] == 'persen' ? number_format($row['nilai_promo'], 0).'%' : 'Rp '.number_format($row['nilai_promo'], 0, ',', '.'); ?>
                                                            </div>
                                                            <div style="margin-top:10px;">
                                                                <button class="btn btn-xs btn-info btn-action" onclick="editPromo(<?php echo $id_promo_row; ?>)"><i class="fa fa-edit"></i></button>
                                                                <a href="?toggle=<?php echo $id_promo_row; ?>" class="btn btn-xs btn-<?php echo $row['status_aktif'] ? 'warning' : 'success'; ?> btn-action"><i class="fa fa-<?php echo $row['status_aktif'] ? 'pause' : 'play'; ?>"></i></a>
                                                                <button class="btn btn-xs btn-danger btn-action" onclick="hapusPromo(<?php echo $id_promo_row; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_promo']), ENT_QUOTES); ?>')"><i class="fa fa-trash"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; else: ?>
                                            <div class="col-xs-12"><div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Belum ada promo. Klik <strong>Tambah Promo</strong>.</div></div>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer"><div class="footer-inner"><div class="footer-content"><?php include "../lib/footer.php"; ?></div></div></div>
    </div>

    <!-- Modal Form (dipakai bareng Add & Edit) -->
    <div class="modal fade" id="modalForm" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="formPromo">
                    <input type="hidden" name="id_promo" id="f_id_promo">
                    <div class="modal-header modal-header-custom">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title" id="f_title"><i class="fa fa-plus"></i> Tambah Promo Baru</h4>
                    </div>
                    <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-tag"></i> Informasi Promo</div>
                                    <div class="form-group">
                                        <label>Nama Promo *</label>
                                        <input type="text" name="nama_promo" id="f_nama_promo" class="form-control" placeholder="Contoh: Promo Cuci Gratis Member Gold" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Keterangan</label>
                                        <textarea name="keterangan" id="f_keterangan" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group" id="f_status_wrap" style="display:none;">
                                        <label><input type="checkbox" name="status_aktif" id="f_status_aktif" value="1"> Aktif</label>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-percent"></i> Nilai Diskon</div>
                                    <div class="row">
                                        <div class="col-xs-5">
                                            <label>Tipe *</label>
                                            <select name="tipe_promo" id="f_tipe_promo" class="form-control" required>
                                                <option value="persen">Persen (%)</option>
                                                <option value="nominal">Nominal (Rp)</option>
                                            </select>
                                        </div>
                                        <div class="col-xs-7">
                                            <label>Nilai *</label>
                                            <input type="number" name="nilai_promo" id="f_nilai_promo" class="form-control" step="0.01" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-calendar"></i> Periode Promo</div>
                                    <div class="row">
                                        <div class="col-xs-6"><label>Mulai *</label><input type="date" name="tanggal_mulai" id="f_tanggal_mulai" class="form-control" required></div>
                                        <div class="col-xs-6"><label>Selesai *</label><input type="date" name="tanggal_selesai" id="f_tanggal_selesai" class="form-control" required></div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-link"></i> Aturan Gabungan</div>
                                    <div class="form-group">
                                        <label><input type="checkbox" name="stackable" id="f_stackable" value="1"> Boleh digabung dengan promo lain (stackable)</label>
                                        <div class="help-text">Kalau ada 2+ promo stackable aktif bareng, potongan dihitung berurutan (harga dasar → promo 1 → sisa → promo 2).</div>
                                    </div>
                                    <div class="form-group">
                                        <label><input type="checkbox" name="boleh_gabung_diskon_member" id="f_boleh_gabung_diskon_member" value="1"> Boleh digabung dengan diskon tier member</label>
                                        <div class="help-text">Kalau tidak dicentang, sistem otomatis pilih yang potongannya lebih besar untuk customer (promo vs diskon member).</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-crosshairs"></i> Target Promo (item/jasa/WO)</div>
                                    <div id="target_rows"></div>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addTargetRow()"><i class="fa fa-plus"></i> Tambah Target</button>
                                    <div class="help-text">Boleh campur jasa, barang, dan work order dalam 1 promo.</div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-map-marker"></i> Scope Cabang</div>
                                    <div class="cabang-checklist">
                                        <?php foreach($daftar_cabang as $cb): ?>
                                        <label class="checkbox" style="display:block;">
                                            <input type="checkbox" name="kd_cabang_list[]" value="<?php echo htmlspecialchars($cb['kode_cabang']); ?>" class="f_cabang_checkbox">
                                            <?php echo htmlspecialchars($cb['nama_cabang']); ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="help-text">Tidak dicentang sama sekali = berlaku semua cabang.</div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-filter"></i> Syarat Kelayakan Customer</div>
                                    <div class="form-group">
                                        <label>Mode kombinasi syarat</label>
                                        <select name="mode_syarat" id="f_mode_syarat" class="form-control">
                                            <option value="AND">Semua syarat harus terpenuhi (AND)</option>
                                            <option value="OR">Salah satu syarat cukup (OR)</option>
                                        </select>
                                    </div>
                                    <div id="syarat_rows"></div>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addSyaratRow()"><i class="fa fa-plus"></i> Tambah Syarat</button>
                                    <div class="help-text">Tidak ada baris syarat = berlaku semua customer.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" name="f_submit_btn" id="f_submit_btn" class="btn btn-success"><i class="fa fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Search Item (dipakai per baris target) -->
    <div class="modal fade" id="modalSearch" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-search"></i> Cari <span id="search_type_label">Item</span></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" id="search_keyword" class="form-control" placeholder="Ketik nama atau kode...">
                            <span class="input-group-btn"><button type="button" class="btn btn-primary" onclick="doSearch()"><i class="fa fa-search"></i></button></span>
                        </div>
                    </div>
                    <div id="search_loading" style="display:none; text-align:center; padding:20px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...</div>
                    <div id="search_results" class="search-result-list"></div>
                    <div id="search_empty" style="display:none; text-align:center; padding:20px; color:#999;"><i class="fa fa-inbox fa-2x"></i><br>Tidak ada hasil</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

    <form id="formHapus" method="POST" style="display:none;">
        <input type="hidden" name="id_promo" id="hapus_id_promo">
        <input type="hidden" name="btn_hapus" value="1">
    </form>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
    var targetRowSeq = 0;
    var syaratRowSeq = 0;
    var currentTargetRowId = null;

    function addTargetRow(type, id, nama) {
        targetRowSeq++;
        var rowId = 'trow_' + targetRowSeq;
        var typeVal = type || 'jasa';
        var html = '<div class="target-row" id="' + rowId + '">' +
            '<button type="button" class="btn btn-xs btn-danger row-remove-btn" onclick="$(\'#' + rowId + '\').remove()"><i class="fa fa-times"></i></button>' +
            '<div class="row">' +
            '<div class="col-xs-4"><select class="form-control input-sm" name="target_type[]" onchange="clearTargetPick(\'' + rowId + '\')">' +
            '<option value="jasa"' + (typeVal=='jasa'?' selected':'') + '>Jasa</option>' +
            '<option value="barang"' + (typeVal=='barang'?' selected':'') + '>Barang</option>' +
            '<option value="workorder"' + (typeVal=='workorder'?' selected':'') + '>Work Order</option>' +
            '</select></div>' +
            '<div class="col-xs-8">' +
            '<input type="hidden" name="target_id[]" value="' + (id || '') + '">' +
            '<input type="hidden" name="target_nama[]" value="' + (nama || '') + '">' +
            '<button type="button" class="btn btn-sm btn-default btn-block" onclick="openSearchModal(\'' + rowId + '\')">' +
            '<i class="fa fa-search"></i> <span class="pick-label">' + (id ? (id + ' - ' + nama) : 'Pilih item...') + '</span>' +
            '</button></div></div></div>';
        $('#target_rows').append(html);
    }

    function clearTargetPick(rowId) {
        var $row = $('#' + rowId);
        $row.find('input[name="target_id[]"]').val('');
        $row.find('input[name="target_nama[]"]').val('');
        $row.find('.pick-label').text('Pilih item...');
    }

    function openSearchModal(rowId) {
        currentTargetRowId = rowId;
        var type = $('#' + rowId).find('select[name="target_type[]"]').val();
        var typeLabel = type == 'barang' ? 'Barang / Sparepart' : (type == 'workorder' ? 'Work Order' : 'Jasa');
        $('#search_type_label').text(typeLabel);
        $('#search_keyword').val('');
        $('#search_results').html('');
        $('#search_empty').hide();
        $('#modalSearch').modal('show');
    }

    // Modal bertumpuk (nested): pastikan z-index & backdrop urut biar gak salah posisi/keklik.
    $('.modal').on('show.bs.modal', function() {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    $('#modalSearch').on('shown.bs.modal', function() {
        doSearch();
        $('#search_keyword').focus();
    });

    $('#search_keyword').on('keypress', function(e) { if(e.which == 13) { e.preventDefault(); doSearch(); } });

    function doSearch() {
        if(!currentTargetRowId) return;
        var type = $('#' + currentTargetRowId).find('select[name="target_type[]"]').val();
        var keyword = $('#search_keyword').val();
        $('#search_loading').show();
        $('#search_results').html('');
        $('#search_empty').hide();

        var url = (type == 'workorder')
            ? 'ajax-get-workorder-list.php?q=' + encodeURIComponent(keyword)
            : 'ajax-search-item-promo.php?q=' + encodeURIComponent(keyword) + '&tipe=' + type + '&limit=100';

        $.ajax({ url: url, dataType: 'json', success: function(response) {
            $('#search_loading').hide();
            var data = (type == 'workorder') ? (response || []) : ((response && response.data) ? response.data : []);
            if(data.length > 0) {
                var html = '';
                for(var i = 0; i < data.length; i++) {
                    var itemId = type == 'workorder' ? data[i].kode_wo : data[i].id;
                    var itemNama = type == 'workorder' ? data[i].nama_wo : data[i].nama;
                    html += '<div class="search-result-item" onclick="pickTargetItem(\'' + itemId + '\', \'' + escapeJs(itemNama) + '\')">' +
                            '<strong>' + itemId + '</strong> - ' + itemNama + '</div>';
                }
                $('#search_results').html(html);
            } else { $('#search_empty').show(); }
        }, error: function(xhr) {
            $('#search_loading').hide();
            $('#search_results').html('<div class="alert alert-danger">Gagal memuat data.</div>');
        }});
    }

    function pickTargetItem(id, nama) {
        var $row = $('#' + currentTargetRowId);
        $row.find('input[name="target_id[]"]').val(id);
        $row.find('input[name="target_nama[]"]').val(nama);
        $row.find('.pick-label').text(id + ' - ' + nama);
        $('#modalSearch').modal('hide');
    }

    function escapeJs(text) { return String(text).replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

    function addSyaratRow(jenis, operator, nilai, rolling_hari) {
        syaratRowSeq++;
        var rowId = 'srow_' + syaratRowSeq;
        var j = jenis || 'kategori_member';
        var op = operator || '>=';
        var html = '<div class="syarat-row" id="' + rowId + '">' +
            '<button type="button" class="btn btn-xs btn-danger row-remove-btn" onclick="$(\'#' + rowId + '\').remove()"><i class="fa fa-times"></i></button>' +
            '<div class="row">' +
            '<div class="col-xs-4"><select class="form-control input-sm" name="jenis_syarat[]" onchange="toggleRollingHari(\'' + rowId + '\')">' +
            '<option value="kategori_member"' + (j=='kategori_member'?' selected':'') + '>Kategori Member</option>' +
            '<option value="minimum_total_servis"' + (j=='minimum_total_servis'?' selected':'') + '>Minimum Total Servis</option>' +
            '<option value="jumlah_kunjungan"' + (j=='jumlah_kunjungan'?' selected':'') + '>Jumlah Kunjungan</option>' +
            '<option value="paket_workorder"' + (j=='paket_workorder'?' selected':'') + '>Pernah Beli Paket WO</option>' +
            '</select></div>' +
            '<div class="col-xs-2"><select class="form-control input-sm" name="operator[]">' +
            '<option value=">="' + (op=='>='?' selected':'') + '>&gt;=</option>' +
            '<option value="<="' + (op=='<='?' selected':'') + '>&lt;=</option>' +
            '<option value="="' + (op=='='?' selected':'') + '>=</option>' +
            '<option value="IN"' + (op=='IN'?' selected':'') + '>IN (daftar)</option>' +
            '</select></div>' +
            '<div class="col-xs-3"><input type="text" class="form-control input-sm" name="nilai_syarat[]" value="' + (nilai || '') + '" placeholder="nilai"></div>' +
            '<div class="col-xs-3 rolling-hari-wrap" style="display:' + (j=='jumlah_kunjungan'?'block':'none') + ';">' +
            '<input type="number" class="form-control input-sm" name="rolling_hari[]" value="' + (rolling_hari || 30) + '" placeholder="hari"></div>' +
            '</div>' +
            '<div class="help-text">kategori_member: nama tier (pisah koma kalau IN) &middot; minimum_total_servis/jumlah_kunjungan: angka &middot; paket_workorder: kode_wo (pisah koma)</div>' +
            '</div>';
        $('#syarat_rows').append(html);
    }

    function toggleRollingHari(rowId) {
        var $row = $('#' + rowId);
        var jenis = $row.find('select[name="jenis_syarat[]"]').val();
        $row.find('.rolling-hari-wrap').toggle(jenis == 'jumlah_kunjungan');
    }

    function resetForm() {
        $('#target_rows').html('');
        $('#syarat_rows').html('');
        $('#formPromo')[0].reset();
        $('.f_cabang_checkbox').prop('checked', false);
    }

    function openAddModal() {
        resetForm();
        $('#f_id_promo').val('');
        $('#f_title').html('<i class="fa fa-plus"></i> Tambah Promo Baru');
        $('#f_submit_btn').attr('name', 'btn_simpan').html('<i class="fa fa-save"></i> Simpan');
        $('#f_status_wrap').hide();
        $('#f_tanggal_mulai').val(new Date().toISOString().slice(0,10));
        addTargetRow();
        $('#modalForm').modal('show');
    }

    function editPromo(id) {
        $.getJSON('master-diskon-periode.php?ajax_detail=' + id, function(data) {
            resetForm();
            var h = data.header;
            $('#f_id_promo').val(h.id_promo);
            $('#f_nama_promo').val(h.nama_promo);
            $('#f_keterangan').val(h.keterangan || '');
            $('#f_tipe_promo').val(h.tipe_promo);
            $('#f_nilai_promo').val(h.nilai_promo);
            $('#f_tanggal_mulai').val(h.tanggal_mulai);
            $('#f_tanggal_selesai').val(h.tanggal_selesai);
            $('#f_stackable').prop('checked', h.stackable == 1);
            $('#f_boleh_gabung_diskon_member').prop('checked', h.boleh_gabung_diskon_member == 1);
            $('#f_mode_syarat').val(h.mode_syarat);
            $('#f_status_aktif').prop('checked', h.status_aktif == 1);
            $('#f_status_wrap').show();

            (data.targets || []).forEach(function(t) { addTargetRow(t.target_type, t.target_id, t.target_nama); });
            if((data.targets || []).length == 0) addTargetRow();

            (data.cabangs || []).forEach(function(kc) {
                $('input.f_cabang_checkbox[value="' + kc + '"]').prop('checked', true);
            });

            (data.syarat || []).forEach(function(s) { addSyaratRow(s.jenis_syarat, s.operator, s.nilai, s.rolling_hari); });

            $('#f_title').html('<i class="fa fa-edit"></i> Edit Promo');
            $('#f_submit_btn').attr('name', 'btn_update').html('<i class="fa fa-save"></i> Update');
            $('#modalForm').modal('show');
        });
    }

    function hapusPromo(id, nama) {
        if(confirm('Yakin hapus promo "' + nama + '"?\n\nKalau promo ini sudah pernah dipakai di transaksi, sistem akan menolak dan minta nonaktifkan saja.')) {
            $('#hapus_id_promo').val(id);
            $('#formHapus').submit();
        }
    }
    </script>
</body>
</html>

<?php } ?>
