<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

// User session data
$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

// Database connection
require_once "../config/koneksi.php";
require_once "function_servis.php";
require_once "_include_customer_vehicle_sync.php";

// Fetch user data using prepared statement
$stmt = mysqli_prepare($koneksi, "SELECT nama_user, password, user_akses, foto_user 
                                FROM tbuser WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

$_nama = $user_data['nama_user'] ?? '';
$pwd = $user_data['password'] ?? '';
$lvl_akses = $user_data['user_akses'] ?? '';
$foto_user = $user_data['foto_user'] ?: "file_upload/avatar.png";

// Fetch branch data using prepared statement (including GPS coordinates)
$stmt = mysqli_prepare($koneksi, "SELECT nama_cabang, tipe_cabang, lat_cabang, long_cabang, alamat_cabang 
                                FROM tbcabang WHERE kode_cabang = ?");
mysqli_stmt_bind_param($stmt, "s", $kd_cabang);
mysqli_stmt_execute($stmt);
$branch_result = mysqli_stmt_get_result($stmt);
$branch_data = mysqli_fetch_assoc($branch_result);
mysqli_stmt_close($stmt);

$nama_cabang = $branch_data['nama_cabang'] ?? '';
$tipe_cabang = $branch_data['tipe_cabang'] ?? '';
$lat_cabang = $branch_data['lat_cabang'] ?? '';
$long_cabang = $branch_data['long_cabang'] ?? '';
$alamat_cabang = $branch_data['alamat_cabang'] ?? "Alamat Cabang " . $nama_cabang;
$telepon_cabang = "021-xxxx-xxxx";

// Initialize service variables
$no_service = $_GET['snoserv'] ?? '';
$no_polisi = $_GET['snopol'] ?? ''; // Get from URL parameter
$no_pelanggan = '';
$nama_pelanggan = '';
$alamat_pelanggan = '';
$telepon_pelanggan = '';
$tanggal_jemput = date('Y-m-d');
$jam_jemput = date('H:i');
$keterangan_jemput = '';
$foto_patokan = '';

// Generate new service number if empty
// FIX 2026-08-23: SELECT MAX(...) lalu +1 rawan race condition kalau dua
// request nabrak barengan. Ganti ke atomic counter per prefix
// (function_servis.php::NextServiceSeqByPrefix). Format no_service TIDAK
// berubah.
if (empty($no_service)) {
    $year = date('Y');
    $prefix = 'SV' . $year;
    $next_num = NextServiceSeqByPrefix($koneksi, $prefix, $prefix);
    $no_service = $prefix . sprintf("%08d", $next_num);
}

// Initialize additional variables
$google_maps_link = '';
$foto_rumah = '';

// Auto-fill customer data if vehicle number is provided
if (!empty($no_polisi)) {
    $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi);
    $vehicleData = $bundle['vehicle'] ?? null;
    $customerData = $bundle['customer'] ?? null;

    if ($vehicleData) {
        $no_pelanggan = $customerData['nopelanggan'] ?? '';
        $nama_pelanggan = $customerData['namapelanggan'] ?? ($vehicleData['pemilik'] ?? '');
        $alamat_pelanggan = $customerData['alamat'] ?? '';
        $telepon_pelanggan = $customerData['telephone'] ?? '';

        // Load existing foto_rumah from foto_tampak_rumah or patokan field
        $foto_patokan = $customerData['foto_tampak_rumah'] ?? ($customerData['patokan'] ?? '');
        $foto_rumah = $foto_patokan;

        // Build Google Maps link from link_gmaps or coordinates
        if (!empty($customerData['link_gmaps'])) {
            $google_maps_link = $customerData['link_gmaps'];
        } elseif (!empty($customerData['klat']) && !empty($customerData['klong'])) {
            $google_maps_link = "https://www.google.com/maps?q=" . $customerData['klat'] . "," . $customerData['klong'];
        }
    }
}

// Fetch existing service data if no_service is provided
if (!empty($no_service)) {
    $stmt = mysqli_prepare($koneksi, "SELECT no_pelanggan, no_polisi,
                                    DATE_FORMAT(tanggal, '%Y-%m-%d') AS tanggal_jemput,
                                    jam, keterangan, foto_motor, keterangan_jemput, foto_patokan
                                    FROM tblservice WHERE no_service = ?");
    mysqli_stmt_bind_param($stmt, "s", $no_service);
    mysqli_stmt_execute($stmt);
    $service_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($service_result) > 0) {
        $service_data = mysqli_fetch_assoc($service_result);
        $no_pelanggan = $service_data['no_pelanggan'];
        $no_polisi = $service_data['no_polisi'];
        $tanggal_jemput = $service_data['tanggal_jemput'];
        $jam_jemput = $service_data['jam'];
        $keterangan_jemput = $service_data['keterangan_jemput'] ?: $service_data['keterangan'];
        $foto_patokan = $service_data['foto_patokan'] ?: $service_data['foto_motor'];

        // Re-fetch customer data if vehicle exists
        if (!empty($no_polisi)) {
            $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi, $no_pelanggan);
            $vehicleData = $bundle['vehicle'] ?? null;
            $customerData = $bundle['customer'] ?? null;

            if ($vehicleData) {
                $nama_pelanggan = $customerData['namapelanggan'] ?? ($vehicleData['pemilik'] ?? '');
                $alamat_pelanggan = $customerData['alamat'] ?? '';
                $telepon_pelanggan = $customerData['telephone'] ?? '';
                if (empty($no_pelanggan) && !empty($customerData['nopelanggan'])) {
                    $no_pelanggan = $customerData['nopelanggan'];
                }

                // Load existing foto from database if available
                $db_foto = $customerData['foto_tampak_rumah'] ?? ($customerData['patokan'] ?? '');
                if (!empty($db_foto) && empty($foto_patokan)) {
                    $foto_patokan = $db_foto;
                    $foto_rumah = $foto_patokan;
                }

                // Build Google Maps link from link_gmaps or coordinates
                if (!empty($customerData['link_gmaps'])) {
                    $google_maps_link = $customerData['link_gmaps'];
                } elseif (!empty($customerData['klat']) && !empty($customerData['klong'])) {
                    $google_maps_link = "https://www.google.com/maps?q=" . $customerData['klat'] . "," . $customerData['klong'];
                }
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if (isset($_POST['btnjadwalkan'])) {
    $no_pelanggan = mysqli_real_escape_string($koneksi, $_POST['txtpelanggan']);
    $no_polisi = mysqli_real_escape_string($koneksi, $_POST['txtnopol']);
    $tanggal_jemput = mysqli_real_escape_string($koneksi, $_POST['txttanggal']);
    $jam_jemput = mysqli_real_escape_string($koneksi, $_POST['txtjam']);
    $keterangan_jemput = mysqli_real_escape_string($koneksi, $_POST['txtketerangan']);
    $google_maps_input = mysqli_real_escape_string($koneksi, $_POST['txtgooglemaps']);

    // Handle file upload
    $foto_patokan = '';
    $should_update_customer = false;
    if (isset($_FILES['foto_patokan']) && $_FILES['foto_patokan']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/foto_rumah_pelanggan/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['foto_patokan']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = "rumah_" . date('YmdHis') . "_" . rand(1000, 9999) . "." . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['foto_patokan']['tmp_name'], $upload_path)) {
                $foto_patokan = "uploads/foto_rumah_pelanggan/" . $new_filename;
                $should_update_customer = true;
            }
        }
    }

    // Extract coordinates from Google Maps link if provided
    $klat = '';
    $klong = '';
    if (!empty($google_maps_input)) {
        // Parse Google Maps URL to extract coordinates
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $google_maps_input, $matches)) {
            $klat = $matches[1];
            $klong = $matches[2];
        } elseif (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $google_maps_input, $matches)) {
            $klat = $matches[1];
            $klong = $matches[2];
        }
        $should_update_customer = true;
    }

    // Check if service exists
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) as count FROM tblservice WHERE no_service = ?");
    mysqli_stmt_bind_param($stmt, "s", $no_service);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $check_data = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($stmt);

    if ($check_data['count'] == 0) {
        // Insert new service with all required fields
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tblservice 
                                        (no_service, tanggal, jam, no_pelanggan, no_polisi, 
                                         keterangan, keterangan_jemput, foto_patokan, kd_cabang, 
                                         id_user, status, status_jemput, status_servis)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1', '1', 'datang')");
        mysqli_stmt_bind_param($stmt, "ssssssssis", $no_service, $tanggal_jemput, $jam_jemput, 
                              $no_pelanggan, $no_polisi, $keterangan_jemput, $keterangan_jemput, 
                              $foto_patokan, $kd_cabang, $id_user);
    } else {
        // Update existing service
        $update_foto = "";
        if (!empty($foto_patokan)) {
            $update_foto = ", foto_patokan = '$foto_patokan'";
        }
        
        $stmt = mysqli_prepare($koneksi, "UPDATE tblservice 
                                        SET tanggal = ?, jam = ?, no_pelanggan = ?, no_polisi = ?,
                                            keterangan_jemput = ?, keterangan = ?$update_foto
                                        WHERE no_service = ?");
        mysqli_stmt_bind_param($stmt, "sssssss", $tanggal_jemput, $jam_jemput, $no_pelanggan, 
                              $no_polisi, $keterangan_jemput, $keterangan_jemput, $no_service);
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // Update tblpelanggan if there's new data (foto or google maps)
        if ($should_update_customer && !empty($no_pelanggan)) {
            $update_fields = [];
            $update_values = [];

            if (!empty($foto_patokan)) {
                $update_fields[] = "patokan = ?";
                $update_values[] = $foto_patokan;
            }
            if (!empty($klat) && !empty($klong)) {
                $update_fields[] = "klat = ?";
                $update_fields[] = "klong = ?";
                $update_values[] = $klat;
                $update_values[] = $klong;
            }

            if (!empty($update_fields)) {
                $update_sql = "UPDATE tblpelanggan SET " . implode(", ", $update_fields) . " WHERE nopelanggan = ?";
                $update_values[] = $no_pelanggan;

                $stmt_update = mysqli_prepare($koneksi, $update_sql);
                $types = str_repeat('s', count($update_values));
                mysqli_stmt_bind_param($stmt_update, $types, ...$update_values);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);
            }
        }

        // Redirect with success message to the service input page
        echo "<script>
            alert('Jadwal penjemputan berhasil disimpan!');
            window.location='servis-input-reguler-jemput.php?snoserv=" . urlencode($no_service) . "';
        </script>";
        exit;
    } else {
        mysqli_stmt_close($stmt);
        echo "<script>alert('Gagal menyimpan jadwal penjemputan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Jadwal Penjemputan Motor">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css">
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css">
    <link rel="stylesheet" href="assets/css/ace.min.css" id="main-ace-style">
    <link rel="stylesheet" href="assets/css/ace-skins.min.css">
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css">

    <!-- Leaflet CSS for Map Preview -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <style>
        .info-section {
            background: #d9edf7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #bce8f1;
        }

        .foto-preview {
            max-width: 300px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 10px;
        }

        .upload-area {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            background: #fafafa;
        }

        .upload-area:hover {
            border-color: #999;
            background: #f0f0f0;
        }

        .customer-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }

        .time-display {
            font-weight: bold;
            color: #2e8b57;
        }

        .form-actions {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>

<body class="no-skin">
    <!-- Navbar -->
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profile">
                            <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
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
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <!-- Breadcrumbs -->
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                        <li><a href="#">Servis Jemput</a></li>
                        <li class="active">Jadwal Penjemputan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-8">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="ace-icon fa fa-calendar"></i> Jadwal Penjemputan Motor</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <!-- Customer Information Display -->
                                        <?php if (!empty($nama_pelanggan)): ?>
                                        <div class="customer-info">
                                            <h5><i class="ace-icon fa fa-user"></i> Informasi Pelanggan</h5>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <strong>Nama:</strong> <?php echo htmlspecialchars($nama_pelanggan); ?><br>
                                                    <strong>No. Polisi:</strong> <?php echo htmlspecialchars($no_polisi); ?><br>
                                                    <strong>Telepon:</strong> <?php echo htmlspecialchars($telepon_pelanggan); ?>
                                                </div>
                                                <div class="col-sm-6">
                                                    <strong>Alamat:</strong> <?php echo htmlspecialchars($alamat_pelanggan); ?><br>
                                                    <?php if (!empty($google_maps_link)): ?>
                                                    <strong>Lokasi:</strong> <a href="<?php echo htmlspecialchars($google_maps_link); ?>" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-map-marker"></i> Lihat Maps</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <form class="form-horizontal" action="" method="post" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">No. Service:</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($no_service); ?>" readonly placeholder="Auto Generate">
                                                    <small class="text-muted">Nomor service akan dibuat otomatis</small>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Tanggal Jemput:</label>
                                                <div class="col-sm-9">
                                                    <input type="date" class="form-control" id="txttanggal" name="txttanggal" value="<?php echo $tanggal_jemput; ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Jam Jemput:</label>
                                                <div class="col-sm-6">
                                                    <input type="time" class="form-control" id="txtjam" name="txtjam" value="<?php echo $jam_jemput; ?>" required>
                                                </div>
                                                <div class="col-sm-3">
                                                    <span class="time-display" id="timeDisplay"><?php echo date('H:i'); ?> WIB</span>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">No. Polisi:</label>
                                                <div class="col-sm-9">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="txtnopol" name="txtnopol" value="<?php echo htmlspecialchars($no_polisi); ?>" placeholder="Nomor polisi kendaraan..." required readonly>
                                                        <span class="input-group-btn">
                                                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalCariKendaraan">
                                                                <i class="ace-icon fa fa-search"></i> Cari
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <input type="hidden" id="txtpelanggan" name="txtpelanggan" value="<?php echo htmlspecialchars($no_pelanggan); ?>"><?php if (!empty($no_pelanggan)): ?>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Kode Pelanggan:</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($no_pelanggan); ?>" placeholder="Kode pelanggan auto-fill..." readonly>
                                                    <small class="text-muted">Otomatis terisi berdasarkan nomor polisi yang dipilih</small>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Link Google Maps Lokasi Penjemputan:</label>
                                                <div class="col-sm-9">
                                                    <div class="input-group">
                                                        <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                                                        <input type="url" class="form-control" id="txtgooglemaps" name="txtgooglemaps" value="<?php echo htmlspecialchars($google_maps_link); ?>" placeholder="https://www.google.com/maps/@-6.1234567,106.1234567...">
                                                        <span class="input-group-btn">
                                                            <button type="button" id="btnHitungJarak" class="btn btn-success" title="Hitung Jarak Rute & Preview Peta">
                                                                <i class="fa fa-road"></i> Hitung Jarak
                                                            </button>
                                                            <?php if (!empty($google_maps_link)): ?>
                                                            <a href="<?php echo htmlspecialchars($google_maps_link); ?>" target="_blank" class="btn btn-info" title="Buka di Google Maps">
                                                                <i class="fa fa-external-link"></i> Buka Maps
                                                            </a>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted">Paste link Google Maps, lalu klik "Hitung Jarak" untuk menghitung jarak dan melihat preview rute</small>
                                                    
                                                    <!-- Inline Route Preview (appears after Hitung Jarak) -->
                                                    <div id="inlineRoutePreview" style="display: none; margin-top: 15px;">
                                                        <div class="panel panel-info">
                                                            <div class="panel-heading">
                                                                <h4 class="panel-title">
                                                                    <i class="fa fa-map"></i> Preview Rute Penjemputan
                                                                </h4>
                                                            </div>
                                                            <div class="panel-body">
                                                                <div id="routeInfoBox" class="alert alert-success" style="margin-bottom: 10px;">
                                                                    <div class="row">
                                                                        <div class="col-xs-6">
                                                                            <strong><i class="fa fa-road"></i> Jarak Rute:</strong>
                                                                            <span id="routeDistance" style="font-size: 20px; font-weight: bold;">-</span>
                                                                        </div>
                                                                        <div class="col-xs-6">
                                                                            <strong><i class="fa fa-clock-o"></i> Estimasi Waktu:</strong>
                                                                            <span id="routeDuration" style="font-size: 20px; font-weight: bold;">-</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div id="routeLoadingBox" class="text-center" style="padding: 30px; display: none;">
                                                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                                    <p>Memuat peta dan menghitung rute...</p>
                                                                </div>
                                                                <div id="routeErrorBox" class="alert alert-danger" style="display: none;">
                                                                    <i class="fa fa-exclamation-triangle"></i>
                                                                    <span id="routeErrorMessage"></span>
                                                                </div>
                                                                <div id="routePreviewMap" style="height: 300px; border-radius: 5px;"></div>
                                                                <div style="margin-top: 8px;">
                                                                    <small class="text-muted">
                                                                        <i class="fa fa-info-circle"></i> 
                                                                        <span style="color: #dc3545;"><i class="fa fa-wrench"></i></span> = Bengkel &nbsp;|&nbsp;
                                                                        <span style="color: #28a745;"><i class="fa fa-home"></i></span> = Pelanggan &nbsp;|&nbsp;
                                                                        Rute via jalan raya (OSRM)
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Keterangan Penjemputan:</label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control" rows="3" id="txtketerangan" name="txtketerangan" placeholder="Catatan tambahan untuk penjemputan motor (keluhan awal, kondisi khusus, dll.)"><?php echo htmlspecialchars($keterangan_jemput); ?></textarea>
                                                </div>
                                            </div>

                                            <!-- KALKULATOR TARIF JEMPUT ANTAR -->
                                            <div class="form-group">
                                                <div class="col-sm-12">
                                                    <div class="alert alert-info" style="background: #d9edf7; border: 1px solid #bce8f1;">
                                                        <h4 style="margin-top: 0;"><i class="fa fa-calculator"></i> Kalkulator Tarif Jemput Antar</h4>

                                                        <div class="row">
                                                            <div class="col-sm-6">
                                                                <label>Kondisi Motor <span class="text-danger">*</span></label>
                                                                <div class="radio">
                                                                    <label>
                                                                        <input type="radio" name="txtkondisi" id="kondisi_jalan" value="jalan" class="ace" <?php echo (!isset($kondisi_motor) || $kondisi_motor == 'jalan') ? 'checked' : ''; ?> />
                                                                        <span class="lbl"> Motor Jalan (bisa dikendarai)</span>
                                                                    </label>
                                                                </div>
                                                                <div class="radio">
                                                                    <label>
                                                                        <input type="radio" name="txtkondisi" id="kondisi_mogok" value="mogok" class="ace" <?php echo (isset($kondisi_motor) && $kondisi_motor == 'mogok') ? 'checked' : ''; ?> />
                                                                        <span class="lbl"> Motor Mogok (tidak bisa jalan)</span>
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="col-sm-6">
                                                                <label>Jarak Penjemputan (KM) <span class="text-danger">*</span></label>
                                                                <div class="input-group">
                                                                    <input type="number" step="0.1" name="txtjarak" id="txtjarak" class="form-control" placeholder="Contoh: 3.5" value="<?php echo isset($jarak_jemput) ? $jarak_jemput : '0'; ?>" required />
                                                                    <span class="input-group-addon">KM</span>
                                                                    <span class="input-group-btn">
                                                                        <button type="button" id="btnHitungTarif" class="btn btn-primary">
                                                                            <i class="fa fa-calculator"></i> Hitung
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row" style="margin-top: 15px;">
                                                            <div class="col-sm-12">
                                                                <div id="hasilTarif" style="display: none; background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                                                                    <div class="row">
                                                                        <div class="col-sm-6">
                                                                            <strong>Tarif Motor Jalan:</strong><br>
                                                                            <span id="tarifJalan" style="font-size: 20px; color: #5cb85c; font-weight: bold;">Rp 0</span>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <strong>Tarif Motor Mogok:</strong><br>
                                                                            <span id="tarifMogok" style="font-size: 20px; color: #d9534f; font-weight: bold;">Rp 0</span>
                                                                        </div>
                                                                    </div>
                                                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                                                        <small class="text-muted">
                                                                            <i class="fa fa-info-circle"></i> Jarak pertama 1 km gratis.<br>
                                                                            <i class="fa fa-info-circle"></i> Perhitungan otomatis mengikuti tarif jemput antar motor.
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <input type="hidden" name="txttarif" id="txttarif" value="<?php echo isset($tarif_jemput) ? $tarif_jemput : '0'; ?>" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Upload Foto Rumah Pelanggan:</label>
                                                <div class="col-sm-9">
                                                    <div class="upload-area">
                                                        <i class="ace-icon fa fa-home fa-2x" style="color: #ccc;"></i>
                                                        <p>Upload foto tampak rumah pelanggan</p>
                                                        <input type="file" name="foto_patokan" id="foto_patokan" accept="image/*" class="form-control">
                                                        <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB) - Foto akan disimpan ke data pelanggan</small>
                                                    </div>
                                                    <?php if (!empty($foto_patokan) || !empty($foto_rumah)) : ?>
                                                        <div style="margin-top: 10px;">
                                                            <p><strong>Foto saat ini (dari data pelanggan):</strong></p>
                                                            <img src="../<?php echo htmlspecialchars($foto_patokan ?: $foto_rumah); ?>" class="foto-preview" alt="Foto Rumah Pelanggan">
                                                            <p class="text-success"><i class="fa fa-check-circle"></i> Foto sudah ada di database, tidak perlu upload ulang kecuali ingin mengganti</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="form-actions">
                                                <div class="row">
                                                    <div class="col-sm-offset-3 col-sm-9">
                                                        <button type="button" class="btn btn-info" id="btnCetakSppm">
                                                            <i class="ace-icon fa fa-print"></i> Cetak SPPM
                                                        </button>
                                                        <button type="submit" name="btnjadwalkan" class="btn btn-success btn-lg">
                                                            <i class="ace-icon fa fa-calendar"></i> Jadwalkan Penjemputan & Lanjut ke Input Servis
                                                        </button>
                                                        <a href="servis-reguler.php" class="btn btn-default">
                                                            <i class="ace-icon fa fa-arrow-left"></i> Kembali
                                                        </a>
                                                        <?php if (!empty($no_service)) : ?>
                                                            <a href="servis-input-reguler-jemput.php?snoserv=<?php echo urlencode($no_service); ?>" class="btn btn-primary">
                                                                <i class="ace-icon fa fa-arrow-right"></i> Lanjut ke Input Servis
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <div class="info-section">
                                <h4><i class="ace-icon fa fa-info-circle"></i> Instruksi Penjemputan</h4>
                                <div class="alert alert-warning">
                                    <strong><i class="ace-icon fa fa-warning"></i> Checklist Penjemputan:</strong>
                                    <ul style="margin-top: 10px; margin-bottom: 0;">
                                        <li>Konfirmasi jadwal dengan pelanggan</li>
                                        <li>Siapkan peralatan penjemputan</li>
                                        <li>Bawa tanda pengenal perusahaan</li>
                                    </ul>
                                </div>
                                <div class="alert alert-info">
                                    <strong><i class="ace-icon fa fa-map-marker"></i> Di Lokasi:</strong>
                                    <ul style="margin-top: 10px; margin-bottom: 0;">
                                        <li>Identifikasi kondisi motor</li>
                                        <li>Ambil foto kondisi motor</li>
                                        <li>Catat keluhan pelanggan</li>
                                        <li>Berikan receipt penjemputan</li>
                                    </ul>
                                </div>
                                <div class="alert alert-success">
                                    <strong><i class="ace-icon fa fa-truck"></i> Setelah Penjemputan:</strong>
                                    <ul style="margin-top: 10px; margin-bottom: 0;">
                                        <li>Update status di sistem</li>
                                        <li>Serahkan motor ke mekanik</li>
                                        <li>Laporkan ke supervisor</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <?php include "../lib/footer.php"; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cari Kendaraan (inline, replaces old popup window) -->
    <div class="modal fade" id="modalCariKendaraan" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="ace-icon fa fa-search"></i> Cari Kendaraan</h4>
                </div>
                <div class="modal-body">
                    <div class="input-group">
                        <input type="text" class="form-control" id="txtCariKendaraanModal"
                               placeholder="No. Polisi, Nama Pemilik, atau No. Telepon..." autocomplete="off">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary" id="btnCariKendaraanModal">
                                <i class="ace-icon fa fa-search"></i> Cari
                            </button>
                        </span>
                    </div>
                    <div id="hasilCariKendaraanModal" style="margin-top:15px;max-height:350px;overflow-y:auto;">
                        <p class="text-muted text-center">Masukkan kata kunci pencarian.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        // Cari Kendaraan modal (replaces old window.open popup — was blocked silently by browsers,
        // leaving No. Polisi empty and the service scheduled with no customer selected)
        jQuery(function($) {
            function jalankanPencarianKendaraan() {
                var q = $('#txtCariKendaraanModal').val().trim();
                if (!q) {
                    $('#hasilCariKendaraanModal').html('<p class="text-muted text-center">Masukkan kata kunci pencarian.</p>');
                    return;
                }
                $('#hasilCariKendaraanModal').html('<p class="text-center"><i class="ace-icon fa fa-spinner fa-spin"></i> Mencari...</p>');
                $.ajax({
                    url: '_ajax/ajax-cari-kendaraan-list.php',
                    type: 'POST',
                    data: { txtsearch: q },
                    dataType: 'json',
                    success: function(response) {
                        if (!response.success || !response.data.length) {
                            $('#hasilCariKendaraanModal').html('<p class="text-muted text-center">Tidak ada kendaraan ditemukan.</p>');
                            return;
                        }
                        var html = '';
                        response.data.forEach(function(d) {
                            html += '<div class="vehicle-row" style="padding:8px 10px;border-bottom:1px solid #eee;cursor:pointer;" ' +
                                    'onclick="pilihKendaraanModal(\'' + d.nopolisi.replace(/'/g, "\\'") + '\')">' +
                                    '<strong>' + $('<div>').text(d.nopolisi).html() + '</strong> — ' + $('<div>').text(d.pemilik || '-').html() +
                                    '<div class="text-muted"><small>' + $('<div>').text((d.merek || '') + ' ' + (d.tipe || '')).html() + '</small></div>' +
                                    '</div>';
                        });
                        $('#hasilCariKendaraanModal').html(html);
                    },
                    error: function() {
                        $('#hasilCariKendaraanModal').html('<p class="text-danger text-center">Gagal memuat data, coba lagi.</p>');
                    }
                });
            }

            $('#btnCariKendaraanModal').on('click', jalankanPencarianKendaraan);
            $('#txtCariKendaraanModal').on('keypress', function(e) {
                if (e.which === 13) { jalankanPencarianKendaraan(); }
            });
            $('#modalCariKendaraan').on('shown.bs.modal', function() {
                $('#txtCariKendaraanModal').val('').focus();
                $('#hasilCariKendaraanModal').html('<p class="text-muted text-center">Masukkan kata kunci pencarian.</p>');
            });
        });

        function pilihKendaraanModal(nopol) {
            setKendaraan(nopol);
            $('#modalCariKendaraan').modal('hide');
        }

        // Image preview
        document.getElementById('foto_patokan').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const existingPreview = document.querySelector('.preview-image');
                    if (existingPreview) existingPreview.remove();

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'foto-preview preview-image';
                    img.style.marginTop = '10px';

                    const previewContainer = document.createElement('div');
                    previewContainer.innerHTML = '<p><strong>Preview:</strong></p>';
                    previewContainer.appendChild(img);
                    previewContainer.className = 'preview-image';

                    document.querySelector('.upload-area').parentNode.appendChild(previewContainer);
                };
                reader.readAsDataURL(file);
            }
        });

        // Real-time clock with WIB timezone
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour12: false,
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('timeDisplay').textContent = timeString + ' WIB';
        }

        // Update time display when time input changes
        document.getElementById('txtjam').addEventListener('change', function() {
            const selectedTime = this.value;
            if (selectedTime) {
                document.getElementById('timeDisplay').textContent = selectedTime + ' WIB';
            }
        });

        // Update clock every second
        setInterval(updateClock, 1000);

        // Set real-time date and time (auto-update every second)
        function updateDateTime() {
            const now = new Date();
            const today = now.toISOString().split('T')[0];
            const currentTime = now.toTimeString().split(' ')[0].substring(0, 5);

            // Update date and time inputs with current real-time values
            document.getElementById('txttanggal').value = today;
            document.getElementById('txtjam').value = currentTime;

            // Update time display
            updateClock();
        }

        // Set default date and time
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize with current date and time
            updateDateTime();

            // Update date and time every second for real-time sync
            setInterval(updateDateTime, 1000);
        });

        // Popup functions - updated to handle customer auto-fill
        function setPelanggan(kode, nama) {
            document.getElementById('txtpelanggan').value = kode;
        }

        function setKendaraan(nopol) {
            document.getElementById('txtnopol').value = nopol;
            // Auto-fill customer data when vehicle is selected
            fetchCustomerData(nopol);
        }

        // Function to fetch customer data via AJAX
        function fetchCustomerData(nopol) {
            if (nopol) {
                $.ajax({
                    url: 'ajax-get-customer-by-vehicle.php',
                    type: 'POST',
                    data: { nopol: nopol },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            document.getElementById('txtpelanggan').value = response.data.no_pelanggan || '';

                            // Update Google Maps link if available
                            if (response.data.google_maps_link) {
                                document.getElementById('txtgooglemaps').value = response.data.google_maps_link;
                            }

                            // Update customer info display
                            updateCustomerInfoDisplay(response.data);

                            // Update foto rumah display if available
                            if (response.data.foto_rumah) {
                                updateFotoRumahDisplay(response.data.foto_rumah);
                            }
                        }
                    },
                    error: function() {
                        console.log('Error fetching customer data');
                    }
                });
            }
        }

        // Function to update customer info display
        function updateCustomerInfoDisplay(data) {
            const existingInfo = document.querySelector('.customer-info');
            if (existingInfo) {
                existingInfo.remove();
            }

            if (data.nama_pelanggan) {
                const mapsButton = data.google_maps_link ?
                    `<strong>Lokasi:</strong> <a href="${data.google_maps_link}" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-map-marker"></i> Lihat Maps</a>` : '';

                const infoHtml = `
                    <div class="customer-info">
                        <h5><i class="ace-icon fa fa-user"></i> Informasi Pelanggan</h5>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Nama:</strong> ${data.nama_pelanggan}<br>
                                <strong>No. Polisi:</strong> ${data.no_polisi}<br>
                                <strong>Telepon:</strong> ${data.telepon}
                            </div>
                            <div class="col-sm-6">
                                <strong>Alamat:</strong> ${data.alamat}<br>
                                ${mapsButton}
                            </div>
                        </div>
                    </div>
                `;

                document.querySelector('.widget-main').insertAdjacentHTML('afterbegin', infoHtml);
            }
        }

        // Function to update foto rumah display
        function updateFotoRumahDisplay(fotoPath) {
            const existingPreview = document.querySelector('.foto-preview-existing');
            if (existingPreview) {
                existingPreview.remove();
            }

            if (fotoPath) {
                const previewHtml = `
                    <div style="margin-top: 10px;" class="foto-preview-existing">
                        <p><strong>Foto saat ini (dari data pelanggan):</strong></p>
                        <img src="../${fotoPath}" class="foto-preview" alt="Foto Rumah Pelanggan">
                        <p class="text-success"><i class="fa fa-check-circle"></i> Foto sudah ada di database, tidak perlu upload ulang kecuali ingin mengganti</p>
                    </div>
                `;
                document.querySelector('.upload-area').insertAdjacentHTML('afterend', previewHtml);
            }
        }

        // ===============================================
        // KALKULATOR TARIF JEMPUT ANTAR
        // ===============================================

        function hitungTarif() {
            const jarak = parseFloat(document.getElementById('txtjarak').value) || 0;

            let tarifJalan = 0;
            let tarifMogok = 0;

            if (jarak > 1.0) {
                // Perhitungan Motor Jalan
                if (jarak >= 1.5) {
                    tarifJalan = 8000; // Base 1.5 km
                    const jarakLebih = jarak - 1.5;
                    if (jarakLebih > 0) {
                        const kelipatan = Math.ceil(jarakLebih / 0.5);
                        tarifJalan += (kelipatan * 2000);
                    }
                } else {
                    // Antara 1.0 - 1.5 km, kasih tarif proporsional
                    const selisih = jarak - 1.0;
                    tarifJalan = Math.ceil((selisih / 0.5) * 8000);
                }

                // Perhitungan Motor Mogok
                if (jarak >= 1.5) {
                    tarifMogok = 11000; // Base 1.5 km
                    const jarakLebih = jarak - 1.5;
                    if (jarakLebih > 0) {
                        const kelipatan = Math.ceil(jarakLebih / 0.5);
                        tarifMogok += (kelipatan * 3000);
                    }
                } else {
                    // Antara 1.0 - 1.5 km
                    const selisih = jarak - 1.0;
                    tarifMogok = Math.ceil((selisih / 0.5) * 11000);
                }
            }

            // Update display
            document.getElementById('tarifJalan').textContent = 'Rp ' + tarifJalan.toLocaleString('id-ID');
            document.getElementById('tarifMogok').textContent = 'Rp ' + tarifMogok.toLocaleString('id-ID');
            document.getElementById('hasilTarif').style.display = 'block';

            // Set hidden field berdasarkan kondisi motor yang dipilih
            const kondisi = document.querySelector('input[name="txtkondisi"]:checked').value;
            const tarifTerpilih = kondisi === 'jalan' ? tarifJalan : tarifMogok;
            document.getElementById('txttarif').value = tarifTerpilih;
        }

        // Event listener untuk tombol hitung
        document.getElementById('btnHitungTarif').addEventListener('click', function() {
            hitungTarif();
        });

        // Auto-hitung saat jarak berubah
        document.getElementById('txtjarak').addEventListener('input', function() {
            if (this.value > 0) {
                hitungTarif();
            }
        });

        // Auto-update tarif saat kondisi motor berubah
        document.querySelectorAll('input[name="txtkondisi"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const jarak = parseFloat(document.getElementById('txtjarak').value) || 0;
                if (jarak > 0) {
                    hitungTarif();
                }
            });
        });

        // Auto-hitung saat halaman load jika sudah ada jarak
        document.addEventListener('DOMContentLoaded', function() {
            const jarak = parseFloat(document.getElementById('txtjarak').value) || 0;
            if (jarak > 0) {
                hitungTarif();
            }
        });

        document.getElementById('btnCetakSppm').addEventListener('click', function() {
            var snoserv = <?php echo json_encode($no_service); ?>;

            var tanggal = document.getElementById('txttanggal') ? document.getElementById('txttanggal').value : '';
            var jam = document.getElementById('txtjam') ? document.getElementById('txtjam').value : '';
            var nopol = document.getElementById('txtnopol') ? document.getElementById('txtnopol').value : '';
            var nopelanggan = document.getElementById('txtpelanggan') ? document.getElementById('txtpelanggan').value : '';
            var gmaps = document.getElementById('txtgooglemaps') ? document.getElementById('txtgooglemaps').value : '';
            var jarak = document.getElementById('txtjarak') ? document.getElementById('txtjarak').value : '';
            var tarif = document.getElementById('txttarif') ? document.getElementById('txttarif').value : '';
            var kondisiEl = document.querySelector('input[name="txtkondisi"]:checked');
            var kondisi = kondisiEl ? kondisiEl.value : '';
            var ket = document.getElementById('txtketerangan') ? document.getElementById('txtketerangan').value : '';

            var url = '_print/print-pickup-schedule.php'
                + '?snoserv=' + encodeURIComponent(snoserv)
                + '&nopelanggan=' + encodeURIComponent(nopelanggan)
                + '&nopol=' + encodeURIComponent(nopol)
                + '&tanggal=' + encodeURIComponent(tanggal)
                + '&jam=' + encodeURIComponent(jam)
                + '&gmaps=' + encodeURIComponent(gmaps)
                + '&jarak=' + encodeURIComponent(jarak)
                + '&tarif=' + encodeURIComponent(tarif)
                + '&kondisi=' + encodeURIComponent(kondisi)
                + '&keterangan=' + encodeURIComponent(ket);

            window.open(url, '_blank');
        });
    </script>


    <!-- Leaflet JS for Map -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- OSRM Route Calculator -->
    <script src="assets/js/osrm-route-calculator.js"></script>

    <!-- OSRM Distance and Route Preview Handler -->
    <script>
        // Koordinat Bengkel (dari PHP)
        var branchLat = <?php echo !empty($lat_cabang) ? floatval($lat_cabang) : 'null'; ?>;
        var branchLng = <?php echo !empty($long_cabang) ? floatval($long_cabang) : 'null'; ?>;
        var branchName = "<?php echo addslashes($nama_cabang); ?>";

        // Hitung Jarak Button Handler - now also shows inline map preview
        document.getElementById('btnHitungJarak').addEventListener('click', async function() {
            const gmapsUrl = document.getElementById('txtgooglemaps').value;
            
            // Get preview elements
            const previewContainer = document.getElementById('inlineRoutePreview');
            const loadingBox = document.getElementById('routeLoadingBox');
            const infoBox = document.getElementById('routeInfoBox');
            const errorBox = document.getElementById('routeErrorBox');
            const mapContainer = document.getElementById('routePreviewMap');
            
            // Validate input
            if (!gmapsUrl || gmapsUrl.trim() === '') {
                alert('⚠️ Masukkan link Google Maps terlebih dahulu!');
                return;
            }

            // Check branch coordinates
            if (!branchLat || !branchLng) {
                alert('⚠️ Koordinat bengkel belum di-setting!\n\nSilakan edit data cabang di Master Cabang dan input Link Google Maps cabang.');
                return;
            }

            // Extract customer coordinates
            const customerCoords = OSRMCalculator.extractCoordinatesFromGMaps(gmapsUrl);
            if (!customerCoords) {
                alert('⚠️ Format Google Maps URL tidak valid!\n\nPastikan URL mengandung koordinat seperti:\n@-6.123456,106.123456\n\natau gunakan format:\n-6.123456,106.123456');
                return;
            }

            // Show loading state on button
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menghitung...';
            btn.disabled = true;

            // Show preview container with loading state
            previewContainer.style.display = 'block';
            loadingBox.style.display = 'block';
            infoBox.style.display = 'none';
            errorBox.style.display = 'none';
            mapContainer.style.display = 'none';

            // Scroll to preview area
            previewContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });

            try {
                // IMPORTANT: Show map container BEFORE initializing the map
                // Leaflet needs the container to be visible for proper rendering
                mapContainer.style.display = 'block';
                
                // Wait for DOM to fully render the visible container
                await new Promise(resolve => setTimeout(resolve, 300));

                // Display route on map (this also calculates distance)
                const routeData = await OSRMCalculator.displayRouteOnMap(
                    'routePreviewMap',
                    branchLat, branchLng,
                    customerCoords.lat, customerCoords.lng,
                    branchName || 'Bengkel',
                    'Lokasi Pelanggan'
                );

                // Update jarak field
                const jarakKm = routeData.distance.toFixed(1);
                document.getElementById('txtjarak').value = jarakKm;

                // Auto-calculate tarif
                hitungTarif();

                // Update info box
                document.getElementById('routeDistance').textContent = OSRMCalculator.formatDistance(routeData.distance);
                document.getElementById('routeDuration').textContent = OSRMCalculator.formatDuration(routeData.duration);

                // Show info box and hide loading
                loadingBox.style.display = 'none';
                infoBox.style.display = 'block';

            } catch (error) {
                console.error('Error calculating distance:', error);
                
                // Show error in preview area
                loadingBox.style.display = 'none';
                errorBox.style.display = 'block';
                mapContainer.style.display = 'none';
                document.getElementById('routeErrorMessage').textContent = 
                    'Gagal menghitung jarak: ' + error.message + '. Pastikan koneksi internet aktif.';
            } finally {
                // Restore button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
