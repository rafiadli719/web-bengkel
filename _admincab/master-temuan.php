<?php
// File: master-temuan.php
// CRUD untuk master data temuan
session_start();
if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";
// RBAC check disabled - uncomment below if needed
// include_once "../lib/rbac.php";
// rbac_require_any(array('master_data_read','master_data_manage','temuan_read','temuan_manage'));

// Load user profile for navbar
$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari['nama_user'] ?? '';
$foto_user = $tm_cari['foto_user'] ?? '';
if($foto_user=='') { $foto_user = "file_upload/avatar.png"; }
if(!isset($_SESSION['username'])) { $_SESSION['username'] = $_nama; }

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$alert = '';
$alert_type = 'info';

// =====================
// HANDLE FORM SUBMISSIONS
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $kode_temuan = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['kode_temuan'] ?? '')));
    $nama_temuan = mysqli_real_escape_string($koneksi, trim($_POST['nama_temuan'] ?? ''));
    $kategori = mysqli_real_escape_string($koneksi, trim($_POST['kategori'] ?? ''));
    $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
    $penyebab_umum = mysqli_real_escape_string($koneksi, trim($_POST['penyebab_umum'] ?? ''));
    $solusi_umum = mysqli_real_escape_string($koneksi, trim($_POST['solusi_umum'] ?? ''));
    $estimasi_waktu = intval($_POST['estimasi_waktu'] ?? 0);
    $tingkat_urgensi = mysqli_real_escape_string($koneksi, trim($_POST['tingkat_urgensi'] ?? 'sedang'));
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($mode === 'create') {
        if ($kode_temuan === '' || $nama_temuan === '') {
            $alert = 'Kode temuan dan nama temuan wajib diisi';
            $alert_type = 'danger';
        } else {
            // Check if kode_temuan already exists
            $cek = mysqli_query($koneksi, "SELECT id FROM tbmaster_temuan WHERE kode_temuan='$kode_temuan'");
            if ($cek && mysqli_num_rows($cek) > 0) {
                $alert = 'Kode temuan sudah ada';
                $alert_type = 'warning';
            } else {
                $sql = "INSERT INTO tbmaster_temuan 
                        (kode_temuan, nama_temuan, kategori, deskripsi, penyebab_umum, solusi_umum, 
                         estimasi_waktu, tingkat_urgensi, is_active, created_by, created_at)
                        VALUES 
                        ('$kode_temuan', '$nama_temuan', '$kategori', '$deskripsi', '$penyebab_umum', '$solusi_umum',
                         '$estimasi_waktu', '$tingkat_urgensi', '$is_active', '$id_user', NOW())";
                if (mysqli_query($koneksi, $sql)) { 
                    $alert = 'Master temuan berhasil ditambahkan'; 
                    $alert_type = 'success';
                } else { 
                    $alert = 'Gagal tambah: '.mysqli_error($koneksi); 
                    $alert_type = 'danger';
                }
            }
        }
    } elseif ($mode === 'update' && $id > 0) {
        if ($nama_temuan === '') {
            $alert = 'Nama temuan wajib diisi';
            $alert_type = 'danger';
        } else {
            $sql = "UPDATE tbmaster_temuan SET 
                        nama_temuan='$nama_temuan', kategori='$kategori', deskripsi='$deskripsi',
                        penyebab_umum='$penyebab_umum', solusi_umum='$solusi_umum',
                        estimasi_waktu='$estimasi_waktu', tingkat_urgensi='$tingkat_urgensi', 
                        is_active='$is_active', updated_by='$id_user', updated_at=NOW()
                    WHERE id='$id'";
            if (mysqli_query($koneksi, $sql)) { 
                $alert = 'Master temuan berhasil diupdate'; 
                $alert_type = 'success';
            } else { 
                $alert = 'Gagal update: '.mysqli_error($koneksi); 
                $alert_type = 'danger';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id > 0) {
        // Soft delete - set is_active = 0
        $del = mysqli_query($koneksi, "UPDATE tbmaster_temuan SET is_active=0 WHERE id='$del_id'");
        $alert = $del ? 'Master temuan berhasil dihapus' : ('Gagal hapus: '.mysqli_error($koneksi));
        $alert_type = $del ? 'success' : 'danger';
    }
}

// Handle restore
if (isset($_GET['restore'])) {
    $restore_id = intval($_GET['restore']);
    if ($restore_id > 0) {
        $res = mysqli_query($koneksi, "UPDATE tbmaster_temuan SET is_active=1 WHERE id='$restore_id'");
        $alert = $res ? 'Master temuan berhasil diaktifkan kembali' : ('Gagal restore: '.mysqli_error($koneksi));
        $alert_type = $res ? 'success' : 'danger';
    }
}

// Filter parameters
$filter_kategori = mysqli_real_escape_string($koneksi, trim($_GET['kategori'] ?? ''));
$filter_urgensi = mysqli_real_escape_string($koneksi, trim($_GET['urgensi'] ?? ''));
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'active';
$q = mysqli_real_escape_string($koneksi, trim($_GET['q'] ?? ''));

$where = 'WHERE 1=1';
if ($filter_kategori !== '') { $where .= " AND kategori='$filter_kategori'"; }
if ($filter_urgensi !== '') { $where .= " AND tingkat_urgensi='$filter_urgensi'"; }
if ($filter_status === 'active') { $where .= " AND is_active=1"; }
elseif ($filter_status === 'inactive') { $where .= " AND is_active=0"; }
if ($q !== '') { $where .= " AND (kode_temuan LIKE '%$q%' OR nama_temuan LIKE '%$q%' OR deskripsi LIKE '%$q%')"; }

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$cnt_rs = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tbmaster_temuan $where");
$total_rows = 0; if ($cnt_rs) { $cr = mysqli_fetch_assoc($cnt_rs); $total_rows = intval($cr['total']); }
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// Statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN is_active=0 THEN 1 ELSE 0 END) as nonaktif,
                SUM(CASE WHEN tingkat_urgensi='tinggi' AND is_active=1 THEN 1 ELSE 0 END) as urgent_tinggi,
                SUM(CASE WHEN tingkat_urgensi='sedang' AND is_active=1 THEN 1 ELSE 0 END) as urgent_sedang,
                SUM(CASE WHEN tingkat_urgensi='rendah' AND is_active=1 THEN 1 ELSE 0 END) as urgent_rendah
              FROM tbmaster_temuan";
$stats_result = mysqli_query($koneksi, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get kategori list for filter
$kategori_rs = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM tbmaster_temuan WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");

// Main data query
$data_sql = "SELECT * FROM tbmaster_temuan $where ORDER BY kode_temuan ASC LIMIT $offset, $per_page";
$data_rs = mysqli_query($koneksi, $data_sql);

// Prepare edit data if edit mode
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_rs = mysqli_query($koneksi, "SELECT * FROM tbmaster_temuan WHERE id='$edit_id'");
    if ($edit_rs && mysqli_num_rows($edit_rs) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_rs);
    }
}

// Generate new kode_temuan
$query_max = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(kode_temuan, 4) AS UNSIGNED)) as max_no FROM tbmaster_temuan WHERE kode_temuan LIKE 'TMN%'");
$data_max = mysqli_fetch_array($query_max);
$next_no = ($data_max['max_no'] ?? 0) + 1;
$kode_temuan_baru = 'TMN' . str_pad($next_no, 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Master Temuan</title>

    <meta name="description" content="Master Data Temuan" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
        /* Stat Cards */
        .stat-card {
            padding: 15px;
            border-radius: 8px;
            color: white;
            text-align: center;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .stat-card h3 { margin: 0; font-size: 28px; font-weight: 700; }
        .stat-card p { margin: 5px 0 0; font-size: 12px; opacity: 0.9; }
        .stat-total { background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); }
        .stat-aktif { background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%); }
        .stat-nonaktif { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        
        /* Table */
        .table thead th { background: #f5f5f5; font-weight: 600; }
        .table td { vertical-align: middle !important; }
        
        /* Urgency badges */
        .badge-urgensi-tinggi { background: #e74c3c; color: white; }
        .badge-urgensi-sedang { background: #f39c12; color: white; }
        .badge-urgensi-rendah { background: #27ae60; color: white; }
        
        /* Quick filters */
        .quick-filters { margin-bottom: 15px; }
        .quick-filters .label { padding: 5px 10px; border-radius: 12px; margin-right: 5px; }
        
        /* Form widget */
        .widget-box.collapsed .widget-body { display: none; }
        .form-horizontal .form-group { margin-bottom: 12px; }
        
        /* Inactive row */
        tr.inactive { opacity: 0.6; background: #f9f9f9; }
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
            <?php include "menu_servis01.php"; ?>
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
                        <li class="active">Master Temuan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            <i class="ace-icon fa fa-search-plus"></i>
                            Master Data Temuan
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Kelola master temuan service</small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?php if($alert!==''){ ?>
                            <div class="alert alert-<?php echo $alert_type; ?>">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php echo esc($alert); ?>
                            </div>
                            <?php } ?>

                            <!-- Statistics Cards -->
                            <div class="row">
                                <div class="col-sm-2 col-xs-4">
                                    <div class="stat-card stat-total">
                                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                                        <p><i class="fa fa-list"></i> Total</p>
                                    </div>
                                </div>
                                <div class="col-sm-2 col-xs-4">
                                    <div class="stat-card stat-aktif">
                                        <h3><?php echo $stats['aktif'] ?? 0; ?></h3>
                                        <p><i class="fa fa-check"></i> Aktif</p>
                                    </div>
                                </div>
                                <div class="col-sm-2 col-xs-4">
                                    <div class="stat-card stat-nonaktif">
                                        <h3><?php echo $stats['nonaktif'] ?? 0; ?></h3>
                                        <p><i class="fa fa-times"></i> Nonaktif</p>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xs-12">
                                    <div class="quick-filters" style="padding-top: 10px;">
                                        <span class="text-muted">Urgensi:</span>
                                        <span class="label badge-urgensi-tinggi">
                                            <i class="fa fa-exclamation-triangle"></i> Tinggi: <?php echo $stats['urgent_tinggi'] ?? 0; ?>
                                        </span>
                                        <span class="label badge-urgensi-sedang">
                                            <i class="fa fa-exclamation-circle"></i> Sedang: <?php echo $stats['urgent_sedang'] ?? 0; ?>
                                        </span>
                                        <span class="label badge-urgensi-rendah">
                                            <i class="fa fa-info-circle"></i> Rendah: <?php echo $stats['urgent_rendah'] ?? 0; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Input/Edit -->
                            <div class="widget-box widget-color-blue">
                                <div class="widget-header">
                                    <h5 class="widget-title">
                                        <i class="fa fa-<?php echo $edit_data ? 'edit' : 'plus'; ?>"></i> 
                                        <?php echo $edit_data ? 'Edit Master Temuan' : 'Tambah Master Temuan'; ?>
                                    </h5>
                                    <div class="widget-toolbar">
                                        <a href="#" data-action="collapse">
                                            <i class="ace-icon fa fa-chevron-<?php echo $edit_data ? 'up' : 'down'; ?>"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="widget-body" <?php echo $edit_data ? '' : 'style="display:none;"'; ?>>
                                    <div class="widget-main">
                                        <form method="post" class="form-horizontal">
                                            <input type="hidden" name="mode" value="<?php echo $edit_data ? 'update' : 'create'; ?>">
                                            <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? 0; ?>">
                                            
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Kode Temuan *</label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" name="kode_temuan" 
                                                                   value="<?php echo esc($edit_data['kode_temuan'] ?? $kode_temuan_baru); ?>" 
                                                                   <?php echo $edit_data ? 'readonly' : ''; ?> required>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Nama Temuan *</label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" name="nama_temuan" 
                                                                   value="<?php echo esc($edit_data['nama_temuan'] ?? ''); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Kategori</label>
                                                        <div class="col-sm-8">
                                                            <select name="kategori" class="form-control">
                                                                <option value="">- Pilih Kategori -</option>
                                                                <option value="Mesin" <?php echo ($edit_data['kategori'] ?? '')=='Mesin'?'selected':''; ?>>Mesin</option>
                                                                <option value="Rem" <?php echo ($edit_data['kategori'] ?? '')=='Rem'?'selected':''; ?>>Rem</option>
                                                                <option value="Kelistrikan" <?php echo ($edit_data['kategori'] ?? '')=='Kelistrikan'?'selected':''; ?>>Kelistrikan</option>
                                                                <option value="Transmisi" <?php echo ($edit_data['kategori'] ?? '')=='Transmisi'?'selected':''; ?>>Transmisi</option>
                                                                <option value="Suspensi" <?php echo ($edit_data['kategori'] ?? '')=='Suspensi'?'selected':''; ?>>Suspensi</option>
                                                                <option value="Body" <?php echo ($edit_data['kategori'] ?? '')=='Body'?'selected':''; ?>>Body</option>
                                                                <option value="Ban" <?php echo ($edit_data['kategori'] ?? '')=='Ban'?'selected':''; ?>>Ban</option>
                                                                <option value="Lainnya" <?php echo ($edit_data['kategori'] ?? '')=='Lainnya'?'selected':''; ?>>Lainnya</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Tingkat Urgensi</label>
                                                        <div class="col-sm-8">
                                                            <select name="tingkat_urgensi" class="form-control">
                                                                <option value="rendah" <?php echo ($edit_data['tingkat_urgensi'] ?? '')=='rendah'?'selected':''; ?>>Rendah</option>
                                                                <option value="sedang" <?php echo ($edit_data['tingkat_urgensi'] ?? 'sedang')=='sedang'?'selected':''; ?>>Sedang</option>
                                                                <option value="tinggi" <?php echo ($edit_data['tingkat_urgensi'] ?? '')=='tinggi'?'selected':''; ?>>Tinggi</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Deskripsi</label>
                                                        <div class="col-sm-8">
                                                            <textarea class="form-control" name="deskripsi" rows="2"><?php echo esc($edit_data['deskripsi'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Penyebab Umum</label>
                                                        <div class="col-sm-8">
                                                            <textarea class="form-control" name="penyebab_umum" rows="2"><?php echo esc($edit_data['penyebab_umum'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Solusi Umum</label>
                                                        <div class="col-sm-8">
                                                            <textarea class="form-control" name="solusi_umum" rows="2"><?php echo esc($edit_data['solusi_umum'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Est. Waktu (menit)</label>
                                                        <div class="col-sm-4">
                                                            <input type="number" class="form-control" name="estimasi_waktu" min="0"
                                                                   value="<?php echo $edit_data['estimasi_waktu'] ?? 0; ?>">
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <label class="checkbox" style="padding-top: 7px;">
                                                                <input type="checkbox" name="is_active" value="1" 
                                                                       <?php echo ($edit_data['is_active'] ?? 1) == 1 ? 'checked' : ''; ?>> Aktif
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fa fa-save"></i> Simpan
                                                    </button>
                                                    <?php if($edit_data): ?>
                                                    <a href="master-temuan.php" class="btn btn-default">
                                                        <i class="fa fa-times"></i> Batal
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="space-10"></div>

                            <!-- Data Table with Filter -->
                            <div class="widget-box widget-color-blue2">
                                <div class="widget-header">
                                    <h5 class="widget-title"><i class="fa fa-table"></i> Daftar Master Temuan</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <!-- Filter Form -->
                                        <form class="form-inline" method="get" style="margin-bottom: 15px;">
                                            <div class="form-group">
                                                <label>Kategori:</label>
                                                <select name="kategori" class="form-control">
                                                    <option value="">Semua</option>
                                                    <?php while($kat = mysqli_fetch_assoc($kategori_rs)): ?>
                                                    <option value="<?php echo esc($kat['kategori']); ?>" <?php echo $filter_kategori==$kat['kategori']?'selected':''; ?>><?php echo esc($kat['kategori']); ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Urgensi:</label>
                                                <select name="urgensi" class="form-control">
                                                    <option value="">Semua</option>
                                                    <option value="tinggi" <?php echo $filter_urgensi=='tinggi'?'selected':''; ?>>Tinggi</option>
                                                    <option value="sedang" <?php echo $filter_urgensi=='sedang'?'selected':''; ?>>Sedang</option>
                                                    <option value="rendah" <?php echo $filter_urgensi=='rendah'?'selected':''; ?>>Rendah</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status:</label>
                                                <select name="status" class="form-control">
                                                    <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>Semua</option>
                                                    <option value="active" <?php echo $filter_status=='active'?'selected':''; ?>>Aktif</option>
                                                    <option value="inactive" <?php echo $filter_status=='inactive'?'selected':''; ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Cari:</label>
                                                <input type="text" class="form-control" name="q" value="<?php echo esc($q); ?>" placeholder="Kode/Nama temuan...">
                                            </div>
                                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Filter</button>
                                            <a class="btn btn-default" href="master-temuan.php"><i class="fa fa-refresh"></i> Reset</a>
                                        </form>
                                        
                                        <!-- Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="3%">No</th>
                                                        <th width="10%">Kode</th>
                                                        <th width="20%">Nama Temuan</th>
                                                        <th width="10%">Kategori</th>
                                                        <th width="20%">Deskripsi</th>
                                                        <th width="8%">Urgensi</th>
                                                        <th width="7%">Est. Waktu</th>
                                                        <th width="7%">Status</th>
                                                        <th width="15%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $no = $offset + 1;
                                                    if($data_rs && mysqli_num_rows($data_rs) > 0) {
                                                        while($d = mysqli_fetch_assoc($data_rs)) {
                                                            $urgensi_class = match($d['tingkat_urgensi']) {
                                                                'tinggi' => 'badge-urgensi-tinggi',
                                                                'sedang' => 'badge-urgensi-sedang',
                                                                'rendah' => 'badge-urgensi-rendah',
                                                                default => 'label-default'
                                                            };
                                                            $row_class = $d['is_active'] == 0 ? 'inactive' : '';
                                                    ?>
                                                    <tr class="<?php echo $row_class; ?>">
                                                        <td class="center"><?php echo $no++; ?></td>
                                                        <td><strong><?php echo esc($d['kode_temuan']); ?></strong></td>
                                                        <td><?php echo esc($d['nama_temuan']); ?></td>
                                                        <td>
                                                            <?php if($d['kategori']): ?>
                                                            <span class="label label-info"><?php echo esc($d['kategori']); ?></span>
                                                            <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><small><?php echo esc(substr($d['deskripsi'], 0, 80)); ?><?php echo strlen($d['deskripsi']) > 80 ? '...' : ''; ?></small></td>
                                                        <td class="center">
                                                            <span class="label <?php echo $urgensi_class; ?>"><?php echo ucfirst($d['tingkat_urgensi']); ?></span>
                                                        </td>
                                                        <td class="center"><?php echo $d['estimasi_waktu']; ?> mnt</td>
                                                        <td class="center">
                                                            <?php if($d['is_active']): ?>
                                                            <span class="label label-success">Aktif</span>
                                                            <?php else: ?>
                                                            <span class="label label-danger">Nonaktif</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="center">
                                                            <a class="btn btn-xs btn-info" href="?edit=<?php echo $d['id']; ?>" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <a class="btn btn-xs btn-primary" href="master-temuan-mapping.php?f_tmn=<?php echo esc($d['kode_temuan']); ?>" title="Lihat Mapping Parts">
                                                                <i class="fa fa-link"></i>
                                                            </a>
                                                            <?php if($d['is_active']): ?>
                                                            <a class="btn btn-xs btn-danger" href="?delete=<?php echo $d['id']; ?>" 
                                                               onclick="return confirm('Nonaktifkan temuan ini?')" title="Nonaktifkan">
                                                                <i class="fa fa-times"></i>
                                                            </a>
                                                            <?php else: ?>
                                                            <a class="btn btn-xs btn-success" href="?restore=<?php echo $d['id']; ?>" 
                                                               onclick="return confirm('Aktifkan kembali temuan ini?')" title="Aktifkan">
                                                                <i class="fa fa-check"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php 
                                                        }
                                                    } else { 
                                                    ?>
                                                    <tr><td colspan="9" class="center text-muted">Tidak ada data</td></tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                        <nav>
                                            <ul class="pagination">
                                                <?php
                                                $base_qs = [];
                                                if ($filter_kategori !== '') $base_qs['kategori'] = $filter_kategori;
                                                if ($filter_urgensi !== '') $base_qs['urgensi'] = $filter_urgensi;
                                                if ($filter_status !== '') $base_qs['status'] = $filter_status;
                                                if ($q !== '') $base_qs['q'] = $q;
                                                
                                                $prev = max(1, $page - 1);
                                                $qs_prev = http_build_query(array_merge($base_qs, ['page' => $prev]));
                                                ?>
                                                <li<?php echo $page == 1 ? ' class="disabled"' : ''; ?>>
                                                    <a href="<?php echo $page == 1 ? '#' : 'master-temuan.php?' . $qs_prev; ?>">&laquo;</a>
                                                </li>
                                                <?php
                                                $start = max(1, $page - 2);
                                                $end = min($total_pages, $page + 2);
                                                for ($p = $start; $p <= $end; $p++):
                                                    $qs_p = http_build_query(array_merge($base_qs, ['page' => $p]));
                                                ?>
                                                <li<?php echo $p == $page ? ' class="active"' : ''; ?>>
                                                    <a href="master-temuan.php?<?php echo $qs_p; ?>"><?php echo $p; ?></a>
                                                </li>
                                                <?php endfor; ?>
                                                <?php
                                                $next = min($total_pages, $page + 1);
                                                $qs_next = http_build_query(array_merge($base_qs, ['page' => $next]));
                                                ?>
                                                <li<?php echo $page == $total_pages ? ' class="disabled"' : ''; ?>>
                                                    <a href="<?php echo $page == $total_pages ? '#' : 'master-temuan.php?' . $qs_next; ?>">&raquo;</a>
                                                </li>
                                            </ul>
                                        </nav>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted">
                                            <small>Menampilkan <?php echo min($total_rows, $offset + 1); ?> - <?php echo min($total_rows, $offset + $per_page); ?> dari <?php echo $total_rows; ?> data</small>
                                        </div>
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
    <script>
    jQuery(function($) {
        // Widget collapse
        $('.widget-header [data-action="collapse"]').click(function(e){
            e.preventDefault();
            var $box = $(this).closest('.widget-box');
            var $body = $box.find('.widget-body');
            var $icon = $(this).find('[class*="fa-chevron"]');
            $body.slideToggle();
            $icon.toggleClass('fa-chevron-up fa-chevron-down');
        });
        
        // Auto hide alert after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
    </script>
</body>
</html>
