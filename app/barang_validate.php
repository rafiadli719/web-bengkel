<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";

    // Check database connection
    if (!$koneksi) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Data User
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'];
    $lvl_akses = $tm_cari['user_akses'];
    $foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

    // Data Cabang
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'];

    // Get item to validate
    $kd_item = $_GET['kd'] ?? '';
    if (empty($kd_item)) {
        header("location: barang.php");
        exit;
    }

    // Sanitize input
    $kd_item = mysqli_real_escape_string($koneksi, $kd_item);

    // Use simple query without complex JOINs to avoid errors
    $query = "SELECT * FROM tblitem WHERE noitem = '$kd_item'";
    $result = mysqli_query($koneksi, $query);
    
    if (!$result) {
        die("Query error: " . mysqli_error($koneksi));
    }

    $item = mysqli_fetch_array($result);
    
    if (!$item) {
        die("Item not found: $kd_item");
    }
    
    // Set default values
    $item['tipe_item'] = $item['tipe_item'] ?? 'NON_ORI';
    $item['status_validasi'] = $item['status_validasi'] ?? 'pending_validation';
    
    // Get jenis name
    $item['namajenis'] = 'Unknown Type';
    if (!empty($item['jenis'])) {
        $jq = mysqli_query($koneksi, "SELECT namajenis FROM tbljenis WHERE kodejenis='" . mysqli_real_escape_string($koneksi, $item['jenis']) . "'");
        if ($jq && $jr = mysqli_fetch_array($jq)) {
            $item['namajenis'] = $jr['namajenis'];
        }
    }
    
    // Get satuan name  
    $item['satuan_name'] = 'Unknown Unit';
    if (!empty($item['satuan'])) {
        $sq = mysqli_query($koneksi, "SELECT satuan FROM tblsatuan WHERE kodesatuan='" . mysqli_real_escape_string($koneksi, $item['satuan']) . "'");
        if ($sq && $sr = mysqli_fetch_array($sq)) {
            $item['satuan_name'] = $sr['satuan'];
        }
    }

    // Get validation issues
    $validation_issues = [];

    // Check basic data completeness
    if (empty($item['namaitem'])) {
        $validation_issues[] = 'Nama item kosong';
    }
    if (empty($item['jenis'])) {
        $validation_issues[] = 'Jenis item belum ditentukan';
    }
    if (empty($item['satuan'])) {
        $validation_issues[] = 'Satuan item belum ditentukan';
    }
    if (empty($item['tipe_item'])) {
        $validation_issues[] = 'Klasifikasi ORI/NON-ORI belum ditentukan';
    }

    // Check tipe-specific data
    if ($item['tipe_item'] == 'ORI') {
        if (empty($item['merek'])) {
            $validation_issues[] = 'Merek pabrikan belum ditentukan';
        }
        if (empty($item['kode_part_resmi'])) {
            $validation_issues[] = 'Kode part resmi belum diisi';
        }
    } elseif ($item['tipe_item'] == 'NON_ORI') {
        if (empty($item['kategori_rak'])) {
            $validation_issues[] = 'Kategori rak belum ditentukan';
        }
        if (empty($item['penggunaan_motor'])) {
            $validation_issues[] = 'Penggunaan motor belum ditentukan';
        }
    }

    // Check stock data
    $stock_query = "SELECT * FROM tblitem_stok WHERE noitem = '$kd_item' AND kode_cabang = '$kd_cabang'";
    $stock_result = mysqli_query($koneksi, $stock_query);
    $stock_data = mysqli_fetch_array($stock_result);

    if (!$stock_data) {
        $validation_issues[] = 'Data stok belum diinisialisasi';
    }

    // Process validation action
    if (isset($_POST['action'])) {
        $action = mysqli_real_escape_string($koneksi, $_POST['action']);
        $validation_notes = $_POST['validation_notes'] ?? '';

        if ($action == 'validate') {
            // Check if columns exist before updating
            $update_query = "UPDATE tblitem SET status_validasi = 'validated' WHERE noitem = '$kd_item'";

            if (mysqli_query($koneksi, $update_query)) {
                // Redirect to barang.php with success message
                header("Location: barang.php?msg=validated&item=" . urlencode($kd_item));
                exit;
            } else {
                $error_msg = "Gagal memvalidasi item: " . mysqli_error($koneksi);
            }
        } elseif ($action == 'reject') {
            $update_query = "UPDATE tblitem SET status_validasi = 'rejected' WHERE noitem = '$kd_item'";

            if (mysqli_query($koneksi, $update_query)) {
                // Redirect to barang.php with success message
                header("Location: barang.php?msg=rejected&item=" . urlencode($kd_item));
                exit;
            } else {
                $error_msg = "Gagal menolak item: " . mysqli_error($koneksi);
            }
        } elseif ($action == 'fix_and_validate') {
            // Auto-fix common issues
            $fixes_applied = [];
            $has_error = false;

            try {
                // Initialize stock if missing
                if (!$stock_data) {
                    $init_stock = "INSERT INTO tblitem_stok (noitem, kode_cabang, stokmin, stok_maks, stok_awal)
                                  VALUES ('$kd_item', '$kd_cabang', 0, 0, 0)";
                    if (@mysqli_query($koneksi, $init_stock)) {
                        $fixes_applied[] = "Inisialisasi data stok";
                    } else {
                        // Check if stock already exists
                        $check_stock = mysqli_query($koneksi, "SELECT * FROM tblitem_stok WHERE noitem='$kd_item' AND kode_cabang='$kd_cabang'");
                        if ($check_stock && mysqli_num_rows($check_stock) > 0) {
                            $fixes_applied[] = "Data stok sudah ada";
                        }
                    }
                }

                // Auto-classify if empty
                if (empty($item['tipe_item']) || $item['tipe_item'] == 'NON_ORI') {
                    $auto_tipe = 'NON_ORI'; // Default to NON_ORI
                    $update_tipe = "UPDATE tblitem SET tipe_item = '$auto_tipe' WHERE noitem = '$kd_item'";
                    if (@mysqli_query($koneksi, $update_tipe)) {
                        $fixes_applied[] = "Auto-klasifikasi sebagai $auto_tipe";
                    }
                }

                // Then validate
                $update_query = "UPDATE tblitem SET status_validasi = 'validated' WHERE noitem = '$kd_item'";

                if (mysqli_query($koneksi, $update_query)) {
                    // Redirect to barang.php with success message
                    $fixes_msg = !empty($fixes_applied) ? implode(', ', $fixes_applied) : 'Tidak ada perbaikan';
                    header("Location: barang.php?msg=fixed&item=" . urlencode($kd_item) . "&fixes=" . urlencode($fixes_msg));
                    exit;
                } else {
                    $error_msg = "Gagal memvalidasi item: " . mysqli_error($koneksi);
                }
            } catch (Exception $e) {
                $error_msg = "Error saat perbaikan: " . $e->getMessage();
            }
        }

        // Refresh item data after update
        $result = mysqli_query($koneksi, $query);
        $item = mysqli_fetch_array($result);
    }

    // Validation history is not needed for now
    $history_result = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Validasi Item - <?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

    <style>
        .validation-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .issue-warning {
            background-color: #fff3cd;
            border-color: #ffecb5;
        }
        .issue-success {
            background-color: #d1edff;
            border-color: #bee5eb;
        }
        .validation-actions {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
        }
    </style>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <div class="navbar-header pull-left">
                <a href="#" class="navbar-brand">
                    <small><i class="fa fa-cogs"></i> Validasi Item</small>
                </a>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="<?php echo $foto_user; ?>" alt="<?php echo $_nama; ?>" />
                            <span class="user-info"><small>Welcome,</small><?php echo $_nama; ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="barang.php">Master Barang</a></li>
                        <li class="active">Validasi Item</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <h3>Validasi Item: <?php echo $item['noitem']; ?></h3>

                            <?php if (isset($success_msg)): ?>
                                <div class="alert alert-success">
                                    <i class="ace-icon fa fa-check"></i> <?php echo $success_msg; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($error_msg)): ?>
                                <div class="alert alert-danger">
                                    <i class="ace-icon fa fa-times"></i> <?php echo $error_msg; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Item Details -->
                        <div class="col-md-6">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Detail Item</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-striped">
                                            <tr>
                                                <th width="30%">Kode Item</th>
                                                <td><?php echo $item['noitem']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Nama Item</th>
                                                <td><?php echo $item['namaitem'] ?: '<span class="text-danger">Belum diisi</span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Tipe</th>
                                                <td>
                                                    <?php
                                                    if ($item['tipe_item'] == 'ORI') {
                                                        echo '<span class="label label-success">ORI (Genuine)</span>';
                                                    } elseif ($item['tipe_item'] == 'NON_ORI') {
                                                        echo '<span class="label label-warning">NON-ORI (Aftermarket)</span>';
                                                    } else {
                                                        echo '<span class="label label-danger">Belum Diklasifikasi</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Status Validasi</th>
                                                <td>
                                                    <?php
                                                    switch($item['status_validasi']) {
                                                        case 'validated':
                                                            echo '<span class="label label-success">Validated</span>';
                                                            break;
                                                        case 'pending_validation':
                                                            echo '<span class="label label-warning">Pending Validation</span>';
                                                            break;
                                                        case 'rejected':
                                                            echo '<span class="label label-danger">Rejected</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="label label-default">Unknown</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Jenis</th>
                                                <td><?php echo $item['namajenis'] ?: '<span class="text-danger">Belum ditentukan</span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Satuan</th>
                                                <td><?php echo $item['satuan'] ?: '<span class="text-danger">Belum ditentukan</span>'; ?></td>
                                            </tr>

                                            <?php if ($item['tipe_item'] == 'ORI'): ?>
                                            <tr>
                                                <th>Merek</th>
                                                <td><?php echo $item['merek'] ?: '<span class="text-danger">Belum diisi</span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Kode Part Resmi</th>
                                                <td><?php echo $item['kode_part_resmi'] ?: '<span class="text-danger">Belum diisi</span>'; ?></td>
                                            </tr>
                                            <?php elseif ($item['tipe_item'] == 'NON_ORI'): ?>
                                            <tr>
                                                <th>Kategori Rak</th>
                                                <td><?php echo $item['kategori_rak'] ?: '<span class="text-danger">Belum ditentukan</span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Penggunaan Motor</th>
                                                <td><?php echo $item['penggunaan_motor'] ?: '<span class="text-danger">Belum diisi</span>'; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Validation Issues -->
                        <div class="col-md-6">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-exclamation-triangle"></i>
                                        Isu Validasi
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <?php if (empty($validation_issues)): ?>
                                            <div class="alert alert-success">
                                                <i class="ace-icon fa fa-check-circle"></i>
                                                Tidak ada isu ditemukan. Item siap untuk divalidasi.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="ace-icon fa fa-exclamation-triangle"></i>
                                                Ditemukan <?php echo count($validation_issues); ?> isu yang perlu diperbaiki:
                                            </div>
                                            <ul class="list-unstyled">
                                                <?php foreach ($validation_issues as $issue): ?>
                                                    <li><i class="ace-icon fa fa-times text-danger"></i> <?php echo $issue; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Validation Actions -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-cog"></i>
                                        Aksi Validasi
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="post" class="form-horizontal">
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label">Catatan:</label>
                                                <div class="col-sm-8">
                                                    <textarea name="validation_notes" class="form-control" rows="3" placeholder="Tambahkan catatan validasi..."></textarea>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <div class="col-sm-12">
                                                    <?php if ($item['status_validasi'] == 'pending_validation'): ?>
                                                        <?php if (empty($validation_issues)): ?>
                                                            <button type="submit" name="action" value="validate" class="btn btn-success btn-sm">
                                                                <i class="ace-icon fa fa-check"></i> Validasi Item
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="action" value="fix_and_validate" class="btn btn-primary btn-sm">
                                                                <i class="ace-icon fa fa-magic"></i> Perbaiki & Validasi
                                                            </button>
                                                        <?php endif; ?>

                                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                                            <i class="ace-icon fa fa-times"></i> Tolak
                                                        </button>
                                                    <?php else: ?>
                                                        <div class="alert alert-info">
                                                            Item sudah <?php echo $item['status_validasi'] == 'validated' ? 'divalidasi' : 'ditolak'; ?>.
                                                        </div>
                                                    <?php endif; ?>

                                                    <a href="barang_edit_improved.php?kd=<?php echo $item['noitem']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="ace-icon fa fa-edit"></i> Edit Item
                                                    </a>

                                                    <a href="barang.php" class="btn btn-default btn-sm">
                                                        <i class="ace-icon fa fa-arrow-left"></i> Kembali
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Validation History -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-history"></i>
                                        Riwayat Validasi
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>User</th>
                                                    <th>Aksi</th>
                                                    <th>Catatan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($history_result && mysqli_num_rows($history_result) > 0): ?>
                                                    <?php while ($history = mysqli_fetch_array($history_result)): ?>
                                                        <tr>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></td>
                                                            <td><?php echo $history['nama_user'] ?? 'System'; ?></td>
                                                            <td>
                                                                <?php
                                                                switch($history['action']) {
                                                                    case 'validated':
                                                                        echo '<span class="label label-success">Divalidasi</span>';
                                                                        break;
                                                                    case 'rejected':
                                                                        echo '<span class="label label-danger">Ditolak</span>';
                                                                        break;
                                                                    case 'auto_validated':
                                                                        echo '<span class="label label-info">Auto-Validasi</span>';
                                                                        break;
                                                                    default:
                                                                        echo '<span class="label label-default">' . $history['action'] . '</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo $history['notes']; ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">
                                                            <?php if (!$history_result): ?>
                                                                Tabel riwayat belum tersedia
                                                            <?php else: ?>
                                                                Belum ada riwayat validasi
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
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

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>

<?php } ?>