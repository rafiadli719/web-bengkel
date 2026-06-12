<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi, $id_user)."'");
$tm_cari = mysqli_fetch_array($cari_kd);
$_nama = $tm_cari['nama_user'] ?? 'User';
$foto_user = $tm_cari['foto_user'] ?? '';
if ($foto_user == '') { $foto_user = "file_upload/avatar.png"; }

$q = trim($_GET['q'] ?? '');
$q_safe = mysqli_real_escape_string($koneksi, $q);

$pabrik_id = intval($_GET['pabrik_id'] ?? 0);
$kategori_id = intval($_GET['kategori_id'] ?? 0);
$mode = $_GET['mode'] ?? 'invalid';
if (!in_array($mode, ['invalid', 'all'], true)) {
    $mode = 'invalid';
}

if (isset($_POST['btnsave'])) {
    $kategori_id_post = intval($_POST['kategori_id'] ?? 0);
    $selected = isset($_POST['kode_tipe']) && is_array($_POST['kode_tipe']) ? $_POST['kode_tipe'] : [];

    $cek_kat = null;
    if ($kategori_id_post > 0) {
        $cek_kat = mysqli_query($koneksi, "SELECT id FROM tbkategori_motor WHERE id=$kategori_id_post AND (status='1' OR status IS NULL) LIMIT 1");
    }

    if ($cek_kat && mysqli_num_rows($cek_kat) > 0 && !empty($selected)) {
        $ids = [];
        foreach ($selected as $id) {
            $id = intval($id);
            if ($id > 0) $ids[] = $id;
        }

        if (!empty($ids)) {
            $in = implode(',', $ids);
            mysqli_query($koneksi, "UPDATE tbtipe_motor SET kode_kategori=$kategori_id_post WHERE kode_tipe IN ($in)");
            $_SESSION['flash_success'] = 'Berhasil update kategori untuk ' . count($ids) . ' tipe motor.';
        } else {
            $_SESSION['flash_success'] = 'Tidak ada tipe motor yang dipilih.';
        }
    } else {
        $_SESSION['flash_success'] = 'Gagal update. Pastikan kategori dipilih dan ada tipe motor yang dicentang.';
    }

    $redir = 'tipe-motor-kategori.php?mode=' . urlencode($mode)
        . '&q=' . urlencode($q)
        . '&pabrik_id=' . urlencode((string)$pabrik_id)
        . '&kategori_id=' . urlencode((string)$kategori_id);
    header('Location: ' . $redir);
    exit;
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
<div id="navbar" class="navbar navbar-default ace-save-state">
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
    </div>
</div>

<div class="main-container ace-save-state" id="main-container">
    <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>

    <div id="sidebar" class="sidebar responsive ace-save-state">
        <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
        <?php include "menu_servis01.php"; ?>
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
                    <li class="active">Kategori untuk Tipe Motor</li>
                </ul>
            </div>

            <div class="page-content">
                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    </div>
                <?php endif; ?>

                <div class="page-header">
                    <h1>
                        Perapihan Kategori Tipe Motor
                        <small>
                            <i class="ace-icon fa fa-angle-double-right"></i>
                            Bulk assign `tbtipe_motor.kode_kategori`
                        </small>
                    </h1>
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <form class="form-inline" method="get" action="">
                            <div class="form-group" style="margin-right:8px;">
                                <label>Mode</label>
                                <select name="mode" class="form-control">
                                    <option value="invalid" <?php echo ($mode === 'invalid') ? 'selected' : ''; ?>>Invalid/Belum rapi</option>
                                    <option value="all" <?php echo ($mode === 'all') ? 'selected' : ''; ?>>Semua</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-right:8px;">
                                <label>Pabrik</label>
                                <select name="pabrik_id" class="form-control">
                                    <option value="0">Semua</option>
                                    <?php
                                    $qp = mysqli_query($koneksi, "SELECT id, merek FROM tbpabrik_motor ORDER BY merek ASC");
                                    while ($qp && ($p = mysqli_fetch_assoc($qp))) {
                                        $pid = intval($p['id']);
                                        $sel = ($pid === $pabrik_id) ? 'selected' : '';
                                        echo '<option value="'.$pid.'" '.$sel.'>'.htmlspecialchars($p['merek']).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-right:8px;">
                                <label>Kategori</label>
                                <select name="kategori_id" class="form-control">
                                    <option value="0">Semua</option>
                                    <?php
                                    $qk = mysqli_query($koneksi, "SELECT id, kategori FROM tbkategori_motor WHERE (status='1' OR status IS NULL) ORDER BY kategori ASC");
                                    while ($qk && ($k = mysqli_fetch_assoc($qk))) {
                                        $kid = intval($k['id']);
                                        $sel = ($kid === $kategori_id) ? 'selected' : '';
                                        echo '<option value="'.$kid.'" '.$sel.'>'.htmlspecialchars($k['kategori']).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-right:8px;">
                                <label>Cari</label>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" class="form-control" placeholder="contoh: BEAT" />
                            </div>

                            <button type="submit" class="btn btn-purple btn-sm">Filter</button>
                            <a href="tipe-motor-kategori.php" class="btn btn-default btn-sm">Reset</a>
                            <a href="item-motor-mapping.php?type=item" class="btn btn-info btn-sm">Kembali ke Mapping</a>
                        </form>
                        <div class="space-8"></div>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="mode" value="<?php echo htmlspecialchars($mode); ?>" />
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>" />
                    <input type="hidden" name="pabrik_id" value="<?php echo htmlspecialchars((string)$pabrik_id); ?>" />
                    <input type="hidden" name="kategori_id" value="<?php echo htmlspecialchars((string)$kategori_id); ?>" />

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Assign Kategori untuk Tipe Motor yang dipilih</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="form-inline">
                                            <div class="form-group" style="margin-right:8px;">
                                                <label>Kategori baru</label>
                                                <select name="kategori_id" class="form-control" required>
                                                    <option value="">- pilih -</option>
                                                    <?php
                                                    $qk2 = mysqli_query($koneksi, "SELECT id, kategori FROM tbkategori_motor WHERE (status='1' OR status IS NULL) ORDER BY kategori ASC");
                                                    while ($qk2 && ($k2 = mysqli_fetch_assoc($qk2))) {
                                                        $kid = intval($k2['id']);
                                                        echo '<option value="'.$kid.'">'.htmlspecialchars($k2['kategori']).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <button type="submit" name="btnsave" class="btn btn-success" onclick="return confirm('Update kategori untuk tipe motor yang dicentang?');">
                                                <i class="fa fa-save"></i> Simpan
                                            </button>
                                            <button type="button" class="btn btn-default" id="btnSelectAll">Select All</button>
                                            <button type="button" class="btn btn-default" id="btnSelectNone">Select None</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="info">
                                        <th width="5%"></th>
                                        <th width="10%">Kode</th>
                                        <th>Tipe</th>
                                        <th width="20%">Pabrik</th>
                                        <th width="15%">Kategori</th>
                                        <th width="10%">Tahun</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT t.kode_tipe, t.tipe, t.kode_pabrik, t.kode_kategori, t.tahun,
                                            p.merek,
                                            k.kategori, k.status as kstatus
                                            FROM tbtipe_motor t
                                            LEFT JOIN tbpabrik_motor p ON p.id=t.kode_pabrik
                                            LEFT JOIN tbkategori_motor k ON k.id=t.kode_kategori";

                                    $where = [];
                                    if ($mode === 'invalid') {
                                        $where[] = "(t.kode_kategori=0 OR k.id IS NULL OR k.status='0')";
                                    }
                                    if ($pabrik_id > 0) {
                                        $where[] = "t.kode_pabrik=$pabrik_id";
                                    }
                                    if ($kategori_id > 0) {
                                        $where[] = "t.kode_kategori=$kategori_id";
                                    }
                                    if ($q !== '') {
                                        $where[] = "(t.tipe LIKE '%".$q_safe."%' OR CAST(t.kode_tipe AS CHAR) LIKE '%".$q_safe."%')";
                                    }
                                    if (!empty($where)) {
                                        $sql .= " WHERE " . implode(' AND ', $where);
                                    }
                                    $sql .= " ORDER BY p.merek ASC, k.kategori ASC, t.tipe ASC LIMIT 800";

                                    $res = mysqli_query($koneksi, $sql);
                                    if ($res && mysqli_num_rows($res) > 0) {
                                        while ($r = mysqli_fetch_assoc($res)) {
                                            $kid = intval($r['kode_tipe']);
                                            $kat = $r['kategori'] ?? '';
                                            $kat = ($kat === '' || $kat === null) ? '-' : $kat;
                                            echo '<tr>';
                                            echo '<td class="center"><input type="checkbox" class="chkTipe" name="kode_tipe[]" value="'.$kid.'"></td>';
                                            echo '<td>'.htmlspecialchars((string)$kid).'</td>';
                                            echo '<td>'.htmlspecialchars($r['tipe']).'</td>';
                                            echo '<td>'.htmlspecialchars($r['merek'] ?? '-').'</td>';
                                            echo '<td>'.htmlspecialchars($kat).'</td>';
                                            echo '<td>'.htmlspecialchars($r['tahun'] ?? '-').'</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="center text-muted">Data tidak ditemukan.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
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
<script>
    (function(){
        var btnAll = document.getElementById('btnSelectAll');
        var btnNone = document.getElementById('btnSelectNone');
        function setAll(val){
            var c = document.querySelectorAll('.chkTipe');
            for (var i=0;i<c.length;i++) c[i].checked = val;
        }
        if (btnAll) btnAll.addEventListener('click', function(){ setAll(true); });
        if (btnNone) btnNone.addEventListener('click', function(){ setAll(false); });
    })();
</script>
</body>
</html>
