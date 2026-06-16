<?php
session_start();
if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
$id_user = $_SESSION['_iduser'];
$nama_user = $_SESSION['_nama'] ?? '';
include "../config/koneksi.php";

// Load user profile for navbar consistency
$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari['nama_user'] ?? ($nama_user ?: '');
$foto_user = $tm_cari['foto_user'] ?? '';
if($foto_user=='') { $foto_user = "file_upload/avatar.png"; }
if(!isset($_SESSION['username'])) { $_SESSION['username'] = $_nama; }

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$alert = '';
$alert_type = 'info';
$now = date('Y-m-d H:i:s');

// =====================
// AJAX ENDPOINTS (JSON)
// =====================
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Search jasa item (jenis=SERVIS)
    if ($action === 'search_jasa') {
        header('Content-Type: application/json');
        $q = mysqli_real_escape_string($koneksi, trim($_GET['q'] ?? ''));
        $rs = mysqli_query($koneksi, "SELECT noitem, namaitem, satuan, hargajual FROM tblitem
                                       WHERE (noitem LIKE '%$q%' OR namaitem LIKE '%$q%')
                                       AND jenis = 'SERVIS'
                                       ORDER BY namaitem LIMIT 20");
        $items = [];
        while ($r = $rs && mysqli_fetch_assoc($rs)) { $items[] = $r; }
        echo json_encode($items);
        exit;
    }

    // Check if jasa exists
    if ($action === 'check_jasa') {
        header('Content-Type: application/json');
        $noitem = mysqli_real_escape_string($koneksi, trim($_GET['noitem'] ?? ''));
        $rs = mysqli_query($koneksi, "SELECT namaitem, hargajual FROM tblitem WHERE noitem='$noitem' AND jenis='SERVIS'");
        if ($rs && mysqli_num_rows($rs) > 0) {
            $row = mysqli_fetch_assoc($rs);
            echo json_encode(['exists'=>true,'namaitem'=>$row['namaitem'],'hargajual'=>$row['hargajual']]);
        } else {
            echo json_encode(['exists'=>false]);
        }
        exit;
    }

    // Download CSV template
    if ($action === 'download_template') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_mapping_temuan_jasa.csv"');
        echo "kode_temuan,noitem,is_primary,prioritas,waktu_estimasi,status_aktif,keterangan\n";
        echo "TMN001,SRV-001,1,1,30,1,Jasa utama untuk temuan ini\n";
        echo "TMN001,SRV-002,0,2,45,1,Jasa alternatif\n";
        exit;
    }
}

// =====================
// POST HANDLERS
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $kode_temuan = mysqli_real_escape_string($koneksi, trim($_POST['kode_temuan'] ?? ''));
    $noitem = mysqli_real_escape_string($koneksi, trim($_POST['noitem'] ?? ''));
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    $prioritas = intval($_POST['prioritas'] ?? 1);
    $waktu_estimasi = intval($_POST['waktu_estimasi'] ?? 0);
    $keterangan = mysqli_real_escape_string($koneksi, trim($_POST['keterangan'] ?? ''));
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

    if ($mode === 'create') {
        if ($kode_temuan === '' || $noitem === '') {
            $alert = 'Kode temuan dan kode jasa wajib diisi';
            $alert_type = 'danger';
        } else {
            // Verify jasa exists and is type SERVIS
            $check_jasa = mysqli_query($koneksi, "SELECT noitem FROM tblitem WHERE noitem='$noitem' AND jenis='SERVIS'");
            if (!$check_jasa || mysqli_num_rows($check_jasa) == 0) {
                $alert = 'Kode jasa tidak ditemukan atau bukan jenis SERVIS';
                $alert_type = 'danger';
            } else {
                $cek = mysqli_query($koneksi, "SELECT id FROM tbmaster_temuan_jasa_mapping WHERE kode_temuan='$kode_temuan' AND noitem='$noitem'");
                if ($cek && mysqli_num_rows($cek) > 0) {
                    $alert = 'Mapping sudah ada';
                    $alert_type = 'warning';
                } else {
                    $sql = "INSERT INTO tbmaster_temuan_jasa_mapping
                            (kode_temuan, noitem, is_primary, prioritas, waktu_estimasi, keterangan, status_aktif, created_by, created_at)
                            VALUES
                            ('$kode_temuan', '$noitem', '$is_primary', '$prioritas', '$waktu_estimasi', '$keterangan', '$status_aktif', '$id_user', NOW())";
                    if (mysqli_query($koneksi, $sql)) {
                        $alert = 'Mapping berhasil ditambahkan';
                        $alert_type = 'success';
                    } else {
                        $alert = 'Gagal tambah: '.mysqli_error($koneksi);
                        $alert_type = 'danger';
                    }
                }
            }
        }
    } elseif ($mode === 'update' && $id > 0) {
        $sql = "UPDATE tbmaster_temuan_jasa_mapping SET
                    kode_temuan='$kode_temuan', noitem='$noitem', is_primary='$is_primary', prioritas='$prioritas',
                    waktu_estimasi='$waktu_estimasi', keterangan='$keterangan', status_aktif='$status_aktif',
                    updated_by='$id_user', updated_at=NOW()
                WHERE id='$id'";
        if (mysqli_query($koneksi, $sql)) {
            $alert = 'Mapping berhasil diupdate';
            $alert_type = 'success';
        } else {
            $alert = 'Gagal update: '.mysqli_error($koneksi);
            $alert_type = 'danger';
        }
    } elseif ($mode === 'import_csv') {
        // Import CSV
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
            $alert = 'Pilih file CSV yang valid';
            $alert_type = 'danger';
        } else {
            $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if ($fh === false) {
                $alert = 'Tidak bisa membaca file CSV';
                $alert_type = 'danger';
            } else {
                $rownum = 0; $ok = 0; $skip = 0; $err = 0;
                $firstLine = fgets($fh);
                $delim = (strpos($firstLine, ';') !== false) ? ';' : ',';
                rewind($fh);
                $header = fgetcsv($fh, 0, $delim);
                $map = [];
                if ($header && count($header) > 1) {
                    foreach ($header as $idx => $h) { $map[strtolower(trim($h))] = $idx; }
                }
                while (($cols = fgetcsv($fh, 0, $delim)) !== false) {
                    $rownum++;
                    $kt = $cols[$map['kode_temuan'] ?? 0] ?? '';
                    $ni = $cols[$map['noitem'] ?? 1] ?? '';
                    $ip = intval($cols[$map['is_primary'] ?? 2] ?? 0) ? 1 : 0;
                    $pr = intval($cols[$map['prioritas'] ?? 3] ?? 1);
                    $we = intval($cols[$map['waktu_estimasi'] ?? 4] ?? 0);
                    $sa = intval($cols[$map['status_aktif'] ?? 5] ?? 1) ? 1 : 0;
                    $ket = mysqli_real_escape_string($koneksi, $cols[$map['keterangan'] ?? 6] ?? '');
                    $kt = mysqli_real_escape_string($koneksi, trim($kt));
                    $ni = mysqli_real_escape_string($koneksi, trim($ni));
                    if ($kt === '' || $ni === '') { $skip++; continue; }

                    // Upsert
                    $cek = mysqli_query($koneksi, "SELECT id FROM tbmaster_temuan_jasa_mapping WHERE kode_temuan='$kt' AND noitem='$ni'");
                    if ($cek && mysqli_num_rows($cek) > 0) {
                        $r = mysqli_fetch_assoc($cek); $mid = intval($r['id']);
                        $sqlu = "UPDATE tbmaster_temuan_jasa_mapping SET is_primary='$ip', prioritas='$pr', waktu_estimasi='$we', status_aktif='$sa', keterangan='$ket', updated_by='$id_user', updated_at=NOW() WHERE id='$mid'";
                        if (mysqli_query($koneksi, $sqlu)) { $ok++; } else { $err++; }
                    } else {
                        $sqli = "INSERT INTO tbmaster_temuan_jasa_mapping (kode_temuan,noitem,is_primary,prioritas,waktu_estimasi,keterangan,status_aktif,created_by,created_at) VALUES ('$kt','$ni','$ip','$pr','$we','$ket','$sa','$id_user',NOW())";
                        if (mysqli_query($koneksi, $sqli)) { $ok++; } else { $err++; }
                    }
                }
                fclose($fh);
                $alert = "Import selesai: OK=$ok, Skip=$skip, Error=$err";
                $alert_type = 'info';
            }
        }
    }
}

// DELETE handler
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id > 0) {
        $del = mysqli_query($koneksi, "DELETE FROM tbmaster_temuan_jasa_mapping WHERE id='$del_id'");
        $alert = $del ? 'Mapping dihapus' : ('Gagal hapus: '.mysqli_error($koneksi));
        $alert_type = $del ? 'success' : 'danger';
    }
}

// Note: Edit is handled by master-temuan-mapping-jasa-add.php

// Filter & Pagination
$filter_kode_temuan = mysqli_real_escape_string($koneksi, trim($_GET['f_tmn'] ?? ''));
$q = mysqli_real_escape_string($koneksi, trim($_GET['q'] ?? ''));
$where = 'WHERE 1=1';
if ($filter_kode_temuan !== '') { $where .= " AND m.kode_temuan='$filter_kode_temuan'"; }
if ($q !== '') { $where .= " AND (m.noitem LIKE '%$q%' OR i.namaitem LIKE '%$q%')"; }

$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$cnt_rs = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tbmaster_temuan_jasa_mapping m LEFT JOIN tblitem i ON i.noitem=m.noitem $where");
$total_rows = 0; if ($cnt_rs) { $cr = mysqli_fetch_assoc($cnt_rs); $total_rows = intval($cr['total']); }
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// Get temuan list for dropdown
$temuan_rs = mysqli_query($koneksi, "SELECT kode_temuan, nama_temuan FROM tbmaster_temuan WHERE is_active=1 ORDER BY kode_temuan");

// Get mapping data
$map_sql = "SELECT m.*, i.namaitem, i.statusitem, i.hargajual, mt.nama_temuan
            FROM tbmaster_temuan_jasa_mapping m
            LEFT JOIN tblitem i ON i.noitem=m.noitem
            LEFT JOIN tbmaster_temuan mt ON mt.kode_temuan = m.kode_temuan
            $where
            ORDER BY m.kode_temuan, m.is_primary DESC, m.prioritas ASC, m.noitem
            LIMIT $offset, $per_page";
$map_rs = mysqli_query($koneksi, $map_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Master Mapping Temuan - Jasa</title>

    <meta name="description" content="Master Mapping Temuan - Jasa" />
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
    .badge-on {background:#5cb85c;} .badge-off{background:#d9534f;}
    .table thead th {background:#f5f5f5;}
    .form-inline .form-control{margin-right:6px}
    tr.danger td{background:#fff5f5}
    </style>
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
                <table><tr>
                    <td width="20%">
                        <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small></a>
                    </td><td></td>
                </tr></table>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info"><small>Welcome,</small><?php echo $_nama; ?></span>
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
                        <li class="active">Master Mapping Temuan - Jasa</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>Master Mapping Temuan - Jasa
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Manajemen mapping temuan ke jasa/servis</small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?php if($alert!==''){ echo '<div class="alert alert-'.$alert_type.'">'.esc($alert).'</div>'; } ?>

                            <div class="clearfix" style="margin-bottom:10px;">
                                <a href="master-temuan-mapping-jasa-add.php" class="btn btn-success">
                                    <i class="fa fa-plus"></i> Tambah Mapping
                                </a>
                            </div>

                            <div class="space-10"></div>

                            <!-- List Mapping -->
                            <div class="widget-box widget-color-blue2">
                                <div class="widget-header"><h5 class="widget-title"><i class="fa fa-list"></i> Daftar Mapping Temuan - Jasa</h5></div>
                                <div class="widget-body"><div class="widget-main">
                                    <form class="form-inline" method="get" style="margin-bottom:10px">
                                        <label>Temuan</label>
                                        <select name="f_tmn" class="form-control">
                                            <option value="">-- Semua --</option>
                                            <?php
                                            mysqli_data_seek($temuan_rs, 0);
                                            while($t = mysqli_fetch_assoc($temuan_rs)):
                                                $selected = ($filter_kode_temuan == $t['kode_temuan']) ? 'selected' : '';
                                            ?>
                                            <option value="<?= esc($t['kode_temuan']) ?>" <?= $selected ?>><?= esc($t['kode_temuan'].' - '.$t['nama_temuan']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <label>Cari</label>
                                        <input type="text" class="form-control" name="q" value="<?php echo esc($q); ?>" placeholder="Kode/Nama jasa">
                                        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Filter</button>
                                        <a class="btn btn-default" href="master-temuan-mapping-jasa.php"><i class="fa fa-refresh"></i> Reset</a>
                                        <a class="btn btn-info" href="master-temuan-mapping-jasa.php?action=download_template"><i class="fa fa-download"></i> Download Template CSV</a>
                                    </form>
                                    <form class="form-inline" method="post" enctype="multipart/form-data" style="margin-bottom:15px">
                                        <input type="hidden" name="mode" value="import_csv">
                                        <label>Import CSV</label>
                                        <input type="file" name="csv_file" accept=".csv" class="form-control">
                                        <button type="submit" class="btn btn-warning"><i class="fa fa-upload"></i> Import</button>
                                    </form>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover table-condensed">
                                            <thead>
                                                <tr>
                                                    <th style="width:60px">ID</th>
                                                    <th style="width:100px">Kode Temuan</th>
                                                    <th>Nama Temuan</th>
                                                    <th style="width:120px">Kode Jasa</th>
                                                    <th>Nama Jasa</th>
                                                    <th style="width:100px">Harga</th>
                                                    <th style="width:70px">Primary</th>
                                                    <th style="width:70px">Prioritas</th>
                                                    <th style="width:80px">Waktu</th>
                                                    <th style="width:70px">Status</th>
                                                    <th style="width:140px">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if($map_rs && mysqli_num_rows($map_rs)>0){ while($m = mysqli_fetch_assoc($map_rs)){
                                                    $danger = ($m['namaitem']===null) ? ' class="danger"' : '';
                                                ?>
                                                <tr<?php echo $danger; ?>>
                                                    <td><?php echo esc($m['id']); ?></td>
                                                    <td><?php echo esc($m['kode_temuan']); ?></td>
                                                    <td><?php echo esc($m['nama_temuan'] ?? '-'); ?></td>
                                                    <td><code><?php echo esc($m['noitem']); ?></code></td>
                                                    <td><?php echo esc($m['namaitem'] ?? '<span class="text-danger">Tidak ditemukan</span>'); ?></td>
                                                    <td class="text-right"><?php echo $m['hargajual'] ? 'Rp '.number_format($m['hargajual'],0,',','.') : '-'; ?></td>
                                                    <td class="text-center"><?php echo $m['is_primary']?'<span class="label label-success">YA</span>':'<span class="label label-default">TIDAK</span>'; ?></td>
                                                    <td class="text-center"><?php echo esc($m['prioritas']); ?></td>
                                                    <td class="text-center"><?php echo $m['waktu_estimasi'] ? $m['waktu_estimasi'].' mnt' : '-'; ?></td>
                                                    <td class="text-center"><?php echo $m['status_aktif']?'<span class="label badge-on">Aktif</span>':'<span class="label badge-off">Nonaktif</span>'; ?></td>
                                                    <td>
                                                        <a class="btn btn-xs btn-info" href="master-temuan-mapping-jasa-add.php?id=<?php echo esc($m['id']); ?>"><i class="fa fa-edit"></i> Edit</a>
                                                        <a class="btn btn-xs btn-danger" href="?delete=<?php echo esc($m['id']); ?>" onclick="return confirm('Hapus mapping ini?')"><i class="fa fa-trash"></i> Hapus</a>
                                                    </td>
                                                </tr>
                                                <?php } } else { ?>
                                                <tr><td colspan="11" class="text-center text-muted">Tidak ada data</td></tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php
                                    if ($total_pages > 1) {
                                        $base_qs = [];
                                        if ($filter_kode_temuan !== '') $base_qs['f_tmn'] = $filter_kode_temuan;
                                        if ($q !== '') $base_qs['q'] = $q;
                                        echo '<nav><ul class="pagination">';
                                        $base = 'master-temuan-mapping-jasa.php?';
                                        $prev = max(1, $page-1);
                                        $qs_prev = http_build_query(array_merge($base_qs, ['page'=>$prev]));
                                        $disabled_prev = ($page==1)?' class="disabled"':'';
                                        echo '<li'.$disabled_prev.'><a href="'.($page==1?'#':$base.$qs_prev).'">&laquo;</a></li>';
                                        $start = max(1, $page-2); $end = min($total_pages, $page+2);
                                        for ($p=$start; $p<=$end; $p++) {
                                            $qs_p = http_build_query(array_merge($base_qs, ['page'=>$p]));
                                            $active = ($p==$page)?' class="active"':'';
                                            echo '<li'.$active.'><a href="'.$base.$qs_p.'">'.$p.'</a></li>';
                                        }
                                        $next = min($total_pages, $page+1);
                                        $qs_next = http_build_query(array_merge($base_qs, ['page'=>$next]));
                                        $disabled_next = ($page==$total_pages)?' class="disabled"':'';
                                        echo '<li'.$disabled_next.'><a href="'.($page==$total_pages?'#':$base.$qs_next).'">&raquo;</a></li>';
                                        echo '</ul></nav>';
                                    }
                                    ?>
                                </div></div>
                            </div>

                            <div class="space-10"></div>

                            <!-- Link to Part Mapping -->
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                Halaman ini untuk mapping <strong>Temuan - Jasa</strong>.
                                Untuk mapping <strong>Temuan - Part/Barang</strong>, gunakan halaman
                                <a href="master-temuan-mapping.php" class="alert-link">Master Mapping Temuan - Part</a>.
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
