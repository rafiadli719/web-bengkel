<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

// Load user (for header avatar)
$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi, $id_user)."'");
$tm_cari = mysqli_fetch_array($cari_kd);
$_nama = $tm_cari['nama_user'] ?? 'User';
$foto_user = $tm_cari['foto_user'] ?? '';
if ($foto_user == '') { $foto_user = "file_upload/avatar.png"; }

$noitem = isset($_GET['kd']) ? trim($_GET['kd']) : '';
$noitem_safe = mysqli_real_escape_string($koneksi, $noitem);
$mode = ($noitem !== '') ? 'edit' : 'list';

$q = trim($_GET['q'] ?? '');
$q_safe = mysqli_real_escape_string($koneksi, $q);

// Handle save mapping
if (isset($_POST['btnsave'])) {
    $noitem = $_POST['noitem'] ?? '';
    $noitem_safe = mysqli_real_escape_string($koneksi, $noitem);
    $selected_motor = isset($_POST['kategori_motor']) && is_array($_POST['kategori_motor']) ? $_POST['kategori_motor'] : array();

    mysqli_query($koneksi, "DELETE FROM tbitem_jenis_motor WHERE noitem='".$noitem_safe."'");
    if (!empty($selected_motor)) {
        foreach ($selected_motor as $jm) {
            $jm = intval($jm);
            if ($jm > 0) {
                $sql1 = "INSERT INTO tbitem_jenis_motor (noitem, kd_kategori_motor) VALUES ('".$noitem_safe."', $jm)";
                if (!mysqli_query($koneksi, $sql1)) {
                    mysqli_query($koneksi, "INSERT INTO tbitem_jenis_motor (noitem, kd_jenis_motor) VALUES ('".$noitem_safe."', $jm)");
                }
            }
        }
    }
    $_SESSION['flash_success'] = 'Mapping kategori motor berhasil disimpan untuk Jasa: ' . htmlspecialchars($noitem);
    header("Location: jasa-motor-mapping.php?kd=" . urlencode($noitem));
    exit;
}

// Load Jasa detail if edit mode
$item = null;
if ($mode === 'edit') {
    $qit = mysqli_query($koneksi, "SELECT noitem, namaitem, jenis FROM tblitem WHERE noitem='".$noitem_safe."'");
    if ($qit && mysqli_num_rows($qit) > 0) {
        $item = mysqli_fetch_assoc($qit);
    } else {
        $mode = 'list';
    }
}

// Load mapped motors
$mapped_motor = array();
if ($mode === 'edit') {
    $mmq = mysqli_query($koneksi, "SELECT kd_kategori_motor FROM tbitem_jenis_motor WHERE noitem='".$noitem_safe."'");
    if ($mmq) {
        while ($mm = mysqli_fetch_assoc($mmq)) {
            if (isset($mm['kd_kategori_motor'])) {
                $mapped_motor[] = intval($mm['kd_kategori_motor']);
            }
        }
    }
    if (empty($mapped_motor)) {
        $mmq2 = mysqli_query($koneksi, "SELECT kd_jenis_motor FROM tbitem_jenis_motor WHERE noitem='".$noitem_safe."'");
        if ($mmq2) {
            while ($mm = mysqli_fetch_assoc($mmq2)) {
                if (isset($mm['kd_jenis_motor'])) {
                    $mapped_motor[] = intval($mm['kd_jenis_motor']);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
    <div id="navbar" class="navbar navbar-default          ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <div class="navbar-header pull-left">
                <table>
                    <tr>
                        <td width="20%">
                            <a href="index.php" class="navbar-brand">
                                <small>
                                    <i class="fa fa-leaf"></i>
                                    <?php include "../lib/subtitel.php"; ?>
                                </small>
                            </a>
                        </td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo $_nama; ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>

                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li>
                                <a href="change_pwd.php">
                                    <i class="ace-icon fa fa-cog"></i>
                                    Change Password
                                </a>
                            </li>
                            <li>
                                <a href="profile.php">
                                    <i class="ace-icon fa fa-user"></i>
                                    Profile
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="logout.php">
                                    <i class="ace-icon fa fa-power-off"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-header pull-right">
                <a href="#" class="navbar-brand"><small></small></a>
            </div>
        </div><!-- /.navbar-container -->
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <div id="sidebar" class="sidebar                  responsive                    ace-save-state">
            <script type="text/javascript">
                try{ace.settings.loadState('sidebar')}catch(e){}
            </script>

            <?php include "menu_dashboard.php"; ?>

            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li><a href="#">Master Data</a></li>
                        <li class="active">Mapping Jasa ke Kategori Motor</li>
                    </ul>
                </div>
                <div class="page-content">
                    <?php if (!empty($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mode === 'list'): ?>
                        <div class="page-header">
                            <h1>
                                Mapping Jasa - Kategori Motor
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Daftar Jasa
                                </small>
                            </h1>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <form class="form-search" method="get" action="">
                                    <span class="input-icon">
                                        <input type="text" placeholder="Cari kode/nama jasa ..." class="nav-search-input" name="q" value="<?php echo htmlspecialchars($q); ?>" autocomplete="off" />
                                        <i class="ace-icon fa fa-search nav-search-icon"></i>
                                    </span>
                                    <input class="btn btn-purple btn-sm" type="submit" value="Cari" />
                                    <a href="jasa-motor-mapping.php" class="btn btn-default btn-sm">Reset</a>
                                </form>
                                <div class="space-8"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="info">
                                            <th width="15%">Kode</th>
                                            <th>Nama Jasa</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql_list = "SELECT noitem, namaitem FROM tblitem WHERE jenis='SERVIS'";
                                        if ($q !== '') {
                                            $sql_list .= " AND (noitem LIKE '%".$q_safe."%' OR namaitem LIKE '%".$q_safe."%')";
                                        }
                                        $sql_list .= " ORDER BY namaitem ASC LIMIT 300";
                                        $res_list = mysqli_query($koneksi, $sql_list);
                                        while ($res_list && ($row = mysqli_fetch_assoc($res_list))) {
                                            echo '<tr>';
                                            echo '<td>'.htmlspecialchars($row['noitem']).'</td>';
                                            echo '<td>'.htmlspecialchars($row['namaitem']).'</td>';
                                            echo '<td class="center">'
                                                .'<a class="btn btn-xs btn-primary" href="jasa-motor-mapping.php?kd='.urlencode($row['noitem']).'">Mapping</a>'
                                                .'</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="page-header"><h1>Mapping Kategori Motor untuk Jasa: <?php echo htmlspecialchars($item['noitem'].' - '.$item['namaitem']); ?></h1></div>
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="widget-box">
                                    <div class="widget-header"><h4 class="widget-title">Pilih Kategori Motor</h4></div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <form method="post">
                                                <input type="hidden" name="noitem" value="<?php echo htmlspecialchars($item['noitem']); ?>" />
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <?php
                                                        $qjm = mysqli_query($koneksi, "SELECT id, kategori FROM tbkategori_motor ORDER BY kategori");
                                                        if ($qjm && mysqli_num_rows($qjm) > 0) {
                                                            while ($jm = mysqli_fetch_assoc($qjm)) {
                                                                $kdj = intval($jm['id']);
                                                                $nmj = $jm['kategori'];
                                                                $checked = in_array($kdj, $mapped_motor) ? 'checked' : '';
                                                                echo '<label class="checkbox-inline" style="margin-right:15px;">'
                                                                    .'<input type="checkbox" name="kategori_motor[]" value="'.$kdj.'" '.$checked.'> '
                                                                    .htmlspecialchars($nmj)
                                                                    .'</label>';
                                                            }
                                                        } else {
                                                            echo '<em>Master kategori motor belum tersedia.</em>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="space-8"></div>
                                                <button type="submit" name="btnsave" class="btn btn-success"><i class="fa fa-save"></i> Simpan Mapping</button>
                                                <a href="jasa-motor-mapping.php" class="btn btn-default">Kembali</a>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-inner">
            <div class="footer-content">
                <?php include "../lib/footer.php"; ?>
            </div>
        </div>
    </div>

    <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
        <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
    </a>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
