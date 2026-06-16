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
$now = date('Y-m-d H:i:s');

// =====================
// AJAX ENDPOINTS (JSON/CSV)
// =====================
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'search_item') {
        header('Content-Type: application/json');
        $q = mysqli_real_escape_string($koneksi, trim($_GET['q'] ?? ''));
        $rs = mysqli_query($koneksi, "SELECT noitem, namaitem, satuan, hargajual FROM tblitem WHERE (noitem LIKE '%$q%' OR namaitem LIKE '%$q%') ORDER BY namaitem LIMIT 20");
        $items = [];
        while ($r = $rs && mysqli_fetch_assoc($rs)) { $items[] = $r; }
        echo json_encode($items);
        exit;
    }
    if ($action === 'check_item') {
        header('Content-Type: application/json');
        $noitem = mysqli_real_escape_string($koneksi, trim($_GET['noitem'] ?? ''));
        $rs = mysqli_query($koneksi, "SELECT namaitem FROM tblitem WHERE noitem='$noitem'");
        if ($rs && mysqli_num_rows($rs) > 0) {
            $row = mysqli_fetch_assoc($rs);
            echo json_encode(['exists'=>true,'namaitem'=>$row['namaitem']]);
        } else {
            echo json_encode(['exists'=>false]);
        }
        exit;
    }
    if ($action === 'download_template') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_mapping_temuan.csv"');
        echo "kode_temuan,noitem,is_primary,prioritas,qty_default,status_aktif,keterangan\n";
        echo "TMN001,FILTER-001,1,1,1,1,Filter Udara Original (Rekomendasi)\n";
        echo "TMN001,FILTER-002,0,2,1,1,Filter Udara Alternatif\n";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $kode_temuan = mysqli_real_escape_string($koneksi, trim($_POST['kode_temuan'] ?? ''));
    $noitem = mysqli_real_escape_string($koneksi, trim($_POST['noitem'] ?? ''));
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    $prioritas = intval($_POST['prioritas'] ?? 1);
    $qty_default = max(1, intval($_POST['qty_default'] ?? 1));
    $keterangan = mysqli_real_escape_string($koneksi, trim($_POST['keterangan'] ?? ''));
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

    if ($mode === 'create') {
        if ($kode_temuan === '' || $noitem === '') {
            $alert = 'Kode temuan dan kode part wajib diisi';
        } else {
            $cek = mysqli_query($koneksi, "SELECT id FROM tbmaster_temuan_barang_mapping WHERE kode_temuan='$kode_temuan' AND noitem='$noitem'");
            if ($cek && mysqli_num_rows($cek) > 0) {
                $alert = 'Mapping sudah ada';
            } else {
                $sql = "INSERT INTO tbmaster_temuan_barang_mapping
                        (kode_temuan, noitem, is_primary, prioritas, qty_default, keterangan, status_aktif, created_by, created_at)
                        VALUES
                        ('$kode_temuan', '$noitem', '$is_primary', '$prioritas', '$qty_default', '$keterangan', '$status_aktif', '$id_user', NOW())";
                if (mysqli_query($koneksi, $sql)) { $alert = 'Mapping berhasil ditambahkan'; } else { $alert = 'Gagal tambah: '.mysqli_error($koneksi); }
            }
        }
    } elseif ($mode === 'update' && $id > 0) {
        $sql = "UPDATE tbmaster_temuan_barang_mapping SET 
                    kode_temuan='$kode_temuan', noitem='$noitem', is_primary='$is_primary', prioritas='$prioritas',
                    qty_default='$qty_default', keterangan='$keterangan', status_aktif='$status_aktif',
                    updated_by='$id_user', updated_at=NOW()
                WHERE id='$id'";
        if (mysqli_query($koneksi, $sql)) { $alert = 'Mapping berhasil diupdate'; } else { $alert = 'Gagal update: '.mysqli_error($koneksi); }
    } elseif ($mode === 'import_csv') {
        // Import CSV (header: kode_temuan,noitem,is_primary,prioritas,qty_default,status_aktif,keterangan)
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
            $alert = 'Pilih file CSV yang valid';
        } else {
            $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if ($fh === false) { $alert = 'Tidak bisa membaca file CSV'; }
            else {
                $rownum = 0; $ok = 0; $skip = 0; $err = 0;
                // Deteksi delimiter sederhana
                $firstLine = fgets($fh);
                $delim = (strpos($firstLine, ';') !== false) ? ';' : ',';
                rewind($fh);
                // Baca header
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
                    $qd = intval($cols[$map['qty_default'] ?? 4] ?? 1); if ($qd < 1) $qd = 1;
                    $sa = intval($cols[$map['status_aktif'] ?? 5] ?? 1) ? 1 : 0;
                    $ket = mysqli_real_escape_string($koneksi, $cols[$map['keterangan'] ?? 6] ?? '');
                    $kt = mysqli_real_escape_string($koneksi, trim($kt));
                    $ni = mysqli_real_escape_string($koneksi, trim($ni));
                    if ($kt === '' || $ni === '') { $skip++; continue; }
                    // Upsert sederhana
                    $cek = mysqli_query($koneksi, "SELECT id FROM tbmaster_temuan_barang_mapping WHERE kode_temuan='$kt' AND noitem='$ni'");
                    if ($cek && mysqli_num_rows($cek) > 0) {
                        $r = mysqli_fetch_assoc($cek); $mid = intval($r['id']);
                        $sqlu = "UPDATE tbmaster_temuan_barang_mapping SET is_primary='$ip', prioritas='$pr', qty_default='$qd', status_aktif='$sa', keterangan='$ket', updated_by='$id_user', updated_at=NOW() WHERE id='$mid'";
                        if (mysqli_query($koneksi, $sqlu)) { $ok++; } else { $err++; }
                    } else {
                        $sqli = "INSERT INTO tbmaster_temuan_barang_mapping (kode_temuan,noitem,is_primary,prioritas,qty_default,keterangan,status_aktif,created_by,created_at) VALUES ('$kt','$ni','$ip','$pr','$qd','$ket','$sa','$id_user',NOW())";
                        if (mysqli_query($koneksi, $sqli)) { $ok++; } else { $err++; }
                    }
                }
                fclose($fh);
                $alert = "Import selesai: OK=$ok, Skip=$skip, Error=$err";
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id > 0) {
        $del = mysqli_query($koneksi, "DELETE FROM tbmaster_temuan_barang_mapping WHERE id='$del_id'");
        $alert = $del ? 'Mapping dihapus' : ('Gagal hapus: '.mysqli_error($koneksi));
    }
}

$filter_kode_temuan = mysqli_real_escape_string($koneksi, trim($_GET['f_tmn'] ?? ''));
$q = mysqli_real_escape_string($koneksi, trim($_GET['q'] ?? ''));
$where = 'WHERE 1=1';
if ($filter_kode_temuan !== '') { $where .= " AND m.kode_temuan='$filter_kode_temuan'"; }
if ($q !== '') { $where .= " AND (m.noitem LIKE '%$q%' OR i.namaitem LIKE '%$q%')"; }

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$cnt_rs = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tbmaster_temuan_barang_mapping m LEFT JOIN tblitem i ON i.noitem=m.noitem $where");
$total_rows = 0; if ($cnt_rs) { $cr = mysqli_fetch_assoc($cnt_rs); $total_rows = intval($cr['total']); }
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$temuan_rs = mysqli_query($koneksi, "SELECT kode_temuan, nama_temuan FROM tbmaster_temuan WHERE is_active=1 ORDER BY kode_temuan");

$map_sql = "SELECT m.*, i.namaitem, i.statusitem, i.quantity 
            FROM tbmaster_temuan_barang_mapping m
            LEFT JOIN tblitem i ON i.noitem=m.noitem
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
    <title><?php include "../lib/titel.php"; ?> - Master Mapping Temuan → Part</title>

    <meta name="description" content="Master Mapping Temuan → Part" />
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
    .suggest-box { position: absolute; z-index: 1000; background: #fff; border: 1px solid #ddd; width: 300px; max-height: 240px; overflow-y: auto; display: none; }
    .suggest-item { padding: 6px 10px; cursor: pointer; }
    .suggest-item:hover { background: #f5f5f5; }
    .relative { position: relative; }
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
                        <li class="active">Master Mapping Temuan → Part</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>Master Mapping Temuan → Part
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Manajemen mapping temuan ke part</small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?php if($alert!==''){ echo '<div class="alert alert-info">'.esc($alert).'</div>'; } ?>

                            <div class="clearfix" style="margin-bottom:10px;">
                                <a href="master-temuan-mapping-add.php" class="btn btn-success">
                                    <i class="fa fa-plus"></i> Tambah Mapping
                                </a>
                            </div>

                            <div class="space-10"></div>

                            <div class="widget-box widget-color-blue2">
                                <div class="widget-header"><h5 class="widget-title"><i class="fa fa-list"></i> Daftar Mapping</h5></div>
                                <div class="widget-body"><div class="widget-main">
                                    <form class="form-inline" method="get" style="margin-bottom:10px">
                                        <label>Temuan</label>
                                        <input type="text" class="form-control" name="f_tmn" value="<?php echo esc($filter_kode_temuan); ?>" placeholder="Kode temuan">
                                        <label>Cari</label>
                                        <input type="text" class="form-control" name="q" value="<?php echo esc($q); ?>" placeholder="Kode/Nama part">
                                        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Filter</button>
                                        <a class="btn btn-default" href="master-temuan-mapping.php"><i class="fa fa-refresh"></i> Reset</a>
                                        <a class="btn btn-success" href="master-temuan-mapping.php?action=download_template"><i class="fa fa-download"></i> Download Template CSV</a>
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
                                                    <th style="width:80px">ID</th>
                                                    <th style="width:120px">Kode Temuan</th>
                                                    <th>Kode Part</th>
                                                    <th>Nama Part</th>
                                                    <th style="width:80px">Primary</th>
                                                    <th style="width:90px">Prioritas</th>
                                                    <th style="width:90px">Qty</th>
                                                    <th style="width:90px">Status</th>
                                                    <th style="width:180px">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if($map_rs && mysqli_num_rows($map_rs)>0){ while($m = mysqli_fetch_assoc($map_rs)){ 
                                                    $danger = ($m['namaitem']===null || $m['statusitem']===null) ? ' class="danger"' : '';
                                                ?>
                                                <tr<?php echo $danger; ?>>
                                                    <td><?php echo esc($m['id']); ?></td>
                                                    <td><?php echo esc($m['kode_temuan']); ?></td>
                                                    <td><?php echo esc($m['noitem']); ?></td>
                                                    <td><?php echo esc($m['namaitem'] ?? '-'); ?></td>
                                                    <td class="text-center"><?php echo $m['is_primary']?'<span class="label label-success">YA</span>':'<span class="label label-default">TIDAK</span>'; ?></td>
                                                    <td class="text-center"><?php echo esc($m['prioritas']); ?></td>
                                                    <td class="text-center"><?php echo esc($m['qty_default']); ?></td>
                                                    <td class="text-center"><?php echo $m['status_aktif']?'<span class="label badge-on">Aktif</span>':'<span class="label badge-off">Nonaktif</span>'; ?></td>
                                                    <td>
                                                        <a class="btn btn-xs btn-info" href="master-temuan-mapping-add.php?id=<?php echo esc($m['id']); ?>"><i class="fa fa-edit"></i> Edit</a>
                                                        <a class="btn btn-xs btn-danger" href="?delete=<?php echo esc($m['id']); ?>" onclick="return confirm('Hapus mapping ini?')"><i class="fa fa-trash"></i> Hapus</a>
                                                    </td>
                                                </tr>
                                                <?php } } else { ?>
                                                <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
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
                                        $base = 'master-temuan-mapping.php?';
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
    <script>
function resetForm(){
    $('#mode').val('create');
    $('#id').val('0');
    $('#kode_temuan').val('');
    $('#noitem').val('');
    $('#is_primary').prop('checked', false);
    $('#prioritas').val('1');
    $('#qty_default').val('1');
    $('#status_aktif').val('1');
    $('#keterangan').val('');
}
function editRow(m){
    $('#mode').val('update');
    $('#id').val(m.id);
    $('#kode_temuan').val(m.kode_temuan);
    $('#noitem').val(m.noitem);
    $('#is_primary').prop('checked', m.is_primary==1);
    $('#prioritas').val(m.prioritas);
    $('#qty_default').val(m.qty_default);
    $('#status_aktif').val(m.status_aktif);
    $('#keterangan').val(m.keterangan||'');
    window.scrollTo({top:0, behavior:'smooth'});
}

// Autocomplete & validation for noitem
$(function(){
    var $inp = $('#noitem');
    var $box = $('#noitem_suggest');
    var $info = $('#noitem_info');
    var hideTimer = null;

    function searchItem(q){
        if(q.length < 2){ $box.hide(); return; }
        $.getJSON('master-temuan-mapping.php', {action:'search_item', q:q}, function(rows){
            if(!rows || rows.length===0){ $box.hide(); return; }
            var html='';
            rows.forEach(function(it){
                html += '<div class="suggest-item" data-noitem="'+it.noitem+'" data-namaitem="'+(it.namaitem||'')+'">'
                     + '<strong>'+it.noitem+'</strong> - '+(it.namaitem||'')
                     + '</div>';
            });
            $box.html(html).show();
        });
    }

    $inp.on('keyup', function(){ searchItem(this.value.trim()); });
    $inp.on('focus', function(){ if(this.value.trim().length>=2) searchItem(this.value.trim()); });
    $inp.on('blur', function(){ hideTimer = setTimeout(function(){ $box.hide(); }, 150); });

    $box.on('mousedown', '.suggest-item', function(e){
        e.preventDefault();
        if(hideTimer){ clearTimeout(hideTimer); }
        var ni = $(this).data('noitem'); var nm = $(this).data('namaitem')||'';
        $inp.val(ni);
        $info.text(nm ? ('Nama: '+nm) : '');
        $box.hide();
    });

    // Validate on change/blur
    function validateNoitem(){
        var v = $inp.val().trim(); if(!v){ $info.text(''); return; }
        $.getJSON('master-temuan-mapping.php', {action:'check_item', noitem:v}, function(res){
            if(res && res.exists){ $info.text('Nama: '+(res.namaitem||'')); }
            else { $info.text('Tidak ditemukan di master item').css('color','#d9534f'); }
        });
    }
    $inp.on('change', validateNoitem);
});
</script>
</body>
</html>
