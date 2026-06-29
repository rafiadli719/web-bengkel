<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "../config/library.php";
include "../config/fungsi_thumb.php";

// Get user info
$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
$_nama = $_SESSION['_nama'];
$foto_user = $_SESSION['_foto'];

// Filters
$filter_tipe = isset($_GET['tipe']) ? $_GET['tipe'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_merek = isset($_GET['merek']) ? $_GET['merek'] : '';
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = ["i.statusitem = '1'"];

if ($filter_tipe) {
    $where_conditions[] = "i.tipe_item = '" . mysqli_real_escape_string($koneksi, $filter_tipe) . "'";
}

if ($filter_status) {
    $where_conditions[] = "i.status_validasi = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}

if ($filter_merek) {
    $where_conditions[] = "i.merek = '" . mysqli_real_escape_string($koneksi, $filter_merek) . "'";
}

if ($filter_kategori) {
    $where_conditions[] = "i.kategori_rak = '" . mysqli_real_escape_string($koneksi, $filter_kategori) . "'";
}

if ($search) {
    $search_term = mysqli_real_escape_string($koneksi, $search);
    $where_conditions[] = "(i.noitem LIKE '%$search_term%' OR i.namaitem LIKE '%$search_term%' OR i.nama_part_resmi LIKE '%$search_term%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Count total records
$count_query = "SELECT COUNT(*) as total FROM view_item_classified i WHERE $where_clause";
$count_result = mysqli_query($koneksi, $count_query);
$total_records = mysqli_fetch_array($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Get records with applicable parts
$query = "SELECT i.*, 
          GROUP_CONCAT(tm.tipe ORDER BY tm.tipe SEPARATOR ', ') as applicable_motors,
          COUNT(DISTINCT sp.kode_tipe) as motor_count,
          u.nama_user as created_by_name,
          kr.nama_kategori
          FROM view_item_classified i 
          LEFT JOIN tblitem_spart sp ON i.noitem = sp.noitem
          LEFT JOIN tbtipe_motor tm ON sp.kode_tipe = tm.kode_tipe
          LEFT JOIN tbuser u ON i.created_by = u.id
          LEFT JOIN tbkategori_rak kr ON i.kategori_rak = kr.kode
          WHERE $where_clause 
          GROUP BY i.noitem
          ORDER BY i.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Master Barang - Sistem Klasifikasi ORI/NON-ORI" />
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
    .label-ori { background-color: #5cb85c; }
    .label-nonori { background-color: #f0ad4e; }
    .text-ori { color: #5cb85c; font-weight: bold; }
    .text-nonori { color: #f0ad4e; font-weight: bold; }
    .ori-row { border-left: 3px solid #5cb85c; }
    .nonori-row { border-left: 3px solid #f0ad4e; }
    .item-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 15px;
        padding: 15px;
        background: #fff;
    }
    .item-card.ori {
        border-left: 4px solid #5cb85c;
    }
    .item-card.non-ori {
        border-left: 4px solid #f0ad4e;
    }
    .item-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    .badge-ori {
        background: #5cb85c;
        color: white;
    }
    .badge-non-ori {
        background: #f0ad4e;
        color: white;
    }
    .badge-validated {
        background: #5cb85c;
        color: white;
    }
    .badge-pending {
        background: #f0ad4e;
        color: white;
    }
    .badge-rejected {
        background: #d9534f;
        color: white;
    }
    .filter-panel {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
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
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
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
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Data Master</a></li>
                        <li class="active">Master Barang</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>Master Barang<small> <i class="ace-icon fa fa-angle-double-right"></i> Sistem Klasifikasi ORI/NON-ORI</small></h1>
                        <div class="pull-right">
                            <a href="barang_add_improved.php" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Tambah Item
                            </a>
                        </div>
                    </div>

                    <!-- Filter Panel -->
                    <div class="filter-panel">
                        <form method="GET" class="form-inline">
                            <div class="row">
                                <div class="col-md-2">
                                    <select name="tipe" class="form-control">
                                        <option value="">Semua Tipe</option>
                                        <option value="ORI" <?php echo $filter_tipe == 'ORI' ? 'selected' : ''; ?>>ORI</option>
                                        <option value="NON_ORI" <?php echo $filter_tipe == 'NON_ORI' ? 'selected' : ''; ?>>NON-ORI</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="validated" <?php echo $filter_status == 'validated' ? 'selected' : ''; ?>>Validated</option>
                                        <option value="pending_validation" <?php echo $filter_status == 'pending_validation' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="merek" class="form-control">
                                        <option value="">Semua Merek</option>
                                        <?php
                                        $merek_query = mysqli_query($koneksi, "SELECT DISTINCT merek FROM tblitem WHERE merek IS NOT NULL AND merek != '' ORDER BY merek");
                                        while ($merek_row = mysqli_fetch_array($merek_query)) {
                                            $selected = $filter_merek == $merek_row['merek'] ? 'selected' : '';
                                            echo "<option value='{$merek_row['merek']}' $selected>{$merek_row['merek']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="kategori" class="form-control">
                                        <option value="">Semua Kategori</option>
                                        <?php
                                        $kat_query = mysqli_query($koneksi, "SELECT kode, nama_kategori FROM tbkategori_rak ORDER BY nama_kategori");
                                        while ($kat_row = mysqli_fetch_array($kat_query)) {
                                            $selected = $filter_kategori == $kat_row['kode'] ? 'selected' : '';
                                            echo "<option value='{$kat_row['kode']}' $selected>{$kat_row['kode']} - {$kat_row['nama_kategori']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Cari kode/nama item..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-info"><i class="fa fa-search"></i></button>
                                    <a href="barang_list_improved.php" class="btn btn-default"><i class="fa fa-refresh"></i></a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Statistics -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-green">
                                <div class="info-box-icon"><i class="fa fa-star"></i></div>
                                <div class="info-box-content">
                                    <span class="info-box-text">ORI Items</span>
                                    <span class="info-box-number">
                                        <?php
                                        $ori_count = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='ORI' AND statusitem='1'"))['count'];
                                        echo $ori_count;
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-orange">
                                <div class="info-box-icon"><i class="fa fa-cog"></i></div>
                                <div class="info-box-content">
                                    <span class="info-box-text">NON-ORI Items</span>
                                    <span class="info-box-number">
                                        <?php
                                        $nonori_count = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='NON_ORI' AND statusitem='1'"))['count'];
                                        echo $nonori_count;
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-yellow">
                                <div class="info-box-icon"><i class="fa fa-clock-o"></i></div>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pending Validation</span>
                                    <span class="info-box-number">
                                        <?php
                                        $pending_count = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE status_validasi='pending_validation' AND statusitem='1'"))['count'];
                                        echo $pending_count;
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-blue">
                                <div class="info-box-icon"><i class="fa fa-cubes"></i></div>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Items</span>
                                    <span class="info-box-number"><?php echo $total_records; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="row">
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($item = mysqli_fetch_array($result)): ?>
                                <div class="col-md-6">
                                    <div class="item-card <?php echo strtolower($item['tipe_item'] == 'ORI' ? 'ori' : 'non-ori'); ?>">
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <h5>
                                                    <strong><?php echo htmlspecialchars($item['noitem']); ?></strong>
                                                    <span class="item-badge badge-<?php echo strtolower(str_replace('_', '-', $item['tipe_item'])); ?>">
                                                        <?php echo $item['tipe_item']; ?>
                                                    </span>
                                                </h5>
                                                <p class="text-muted"><?php echo htmlspecialchars($item['namaitem']); ?></p>
                                                
                                                <?php if ($item['tipe_item'] == 'ORI'): ?>
                                                    <p><strong>Merek:</strong> <?php echo htmlspecialchars($item['merek']); ?></p>
                                                    <p><strong>Part Number:</strong> <?php echo htmlspecialchars($item['kode_part_resmi']); ?></p>
                                                    <p><strong>Nama Resmi:</strong> <?php echo htmlspecialchars($item['nama_part_resmi']); ?></p>
                                                <?php else: ?>
                                                    <p><strong>Merek:</strong> <?php echo htmlspecialchars($item['merek'] ?? '-'); ?></p>
                                                    <p><strong>Kategori:</strong> <?php echo htmlspecialchars(($item['kategori_rak'] ?? '') . ($item['nama_kategori'] ? ' - ' . $item['nama_kategori'] : '')); ?></p>
                                                <?php endif; ?>
                                                
                                                <!-- Applicable Part Info -->
                                                <?php if (!empty($item['applicable_motors'])): ?>
                                                    <div style="margin-top: 10px; padding: 8px; background-color: #f8f9fa; border-radius: 3px;">
                                                        <strong><i class="fa fa-motorcycle text-info"></i> Applicable for:</strong><br>
                                                        <small class="text-muted">
                                                            <?php 
                                                            $motors = explode(', ', $item['applicable_motors']);
                                                            if (count($motors) > 3) {
                                                                echo implode(', ', array_slice($motors, 0, 3)) . '... <span class="badge badge-info">+' . (count($motors) - 3) . ' more</span>';
                                                            } else {
                                                                echo $item['applicable_motors'];
                                                            }
                                                            ?>
                                                        </small>
                                                    </div>
                                                <?php elseif ($item['motor_count'] == 0): ?>
                                                    <div style="margin-top: 10px; padding: 8px; background-color: #fff3cd; border-radius: 3px;">
                                                        <small class="text-warning"><i class="fa fa-warning"></i> No applicable motors specified</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <span class="item-badge badge-<?php echo str_replace('_', '-', $item['status_validasi']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $item['status_validasi'])); ?>
                                                </span>
                                                <br><br>
                                                <p><strong>Harga Beli:</strong><br>Rp <?php echo number_format($item['hargapokok']); ?></p>
                                                <p><strong>Harga Jual:</strong><br>Rp <?php echo number_format($item['hargajual']); ?></p>
                                                <p><strong>Stok:</strong> <?php echo $item['quantity']; ?></p>
                                                
                                                <div class="btn-group-vertical">
                                                    <a href="barang_edit_improved.php?id=<?php echo $item['noitem']; ?>" class="btn btn-xs btn-info">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <?php if ($item['status_validasi'] == 'pending_validation'): ?>
                                                        <a href="barang_validate.php?id=<?php echo $item['noitem']; ?>" class="btn btn-xs btn-success">
                                                            <i class="fa fa-check"></i> Validate
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <small class="text-muted">
                                                    <?php if (!empty($item['created_by_name'])): ?>
                                                        Created by: <?php echo htmlspecialchars($item['created_by_name']); ?> on 
                                                    <?php endif; ?>
                                                    <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-md-12">
                                <div class="alert alert-info text-center">
                                    <i class="fa fa-info-circle"></i> Tidak ada data item yang ditemukan.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="text-center">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">Previous</a></li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                            <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">Next</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        jQuery(function($) {
            // Auto-submit filter on change
            $('select[name="tipe"], select[name="status"], select[name="merek"], select[name="kategori"]').on('change', function() {
                $(this).closest('form').submit();
            });
        });
    </script>
</body>
</html>