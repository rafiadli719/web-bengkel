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

// Fetch branch data using prepared statement
$stmt = mysqli_prepare($koneksi, "SELECT nama_cabang, tipe_cabang 
                                FROM tbcabang WHERE kode_cabang = ?");
mysqli_stmt_bind_param($stmt, "s", $kd_cabang);
mysqli_stmt_execute($stmt);
$branch_result = mysqli_stmt_get_result($stmt);
$branch_data = mysqli_fetch_assoc($branch_result);
mysqli_stmt_close($stmt);

$nama_cabang = $branch_data['nama_cabang'] ?? '';
$tipe_cabang = $branch_data['tipe_cabang'] ?? '';
$alamat_cabang = "Alamat Cabang " . $nama_cabang;
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
if (empty($no_service)) {
    $no_service = "SV" . date('Y') . sprintf("%08d", rand(1, 99999999));
}

// Auto-fill customer data if vehicle number is provided
if (!empty($no_polisi)) {
    $stmt = mysqli_prepare($koneksi, "SELECT vpk.pemilik, vpk.telephone, vpk.alamat, 
                                    tbl.nopelanggan, tbl.namapelanggan, tbl.alamat as alamat_lengkap, 
                                    tbl.telephone as tlp_pelanggan
                                    FROM view_pelanggan_kendaraan vpk
                                    LEFT JOIN tblpelanggan tbl ON vpk.pemilik = tbl.namapelanggan
                                    WHERE vpk.nopolisi = ?");
    mysqli_stmt_bind_param($stmt, "s", $no_polisi);
    mysqli_stmt_execute($stmt);
    $customer_result = mysqli_stmt_get_result($stmt);
    
    if ($customer_data = mysqli_fetch_assoc($customer_result)) {
        $no_pelanggan = $customer_data['nopelanggan'] ?: $no_polisi; // Use nopol if no customer code
        $nama_pelanggan = $customer_data['pemilik'];
        $alamat_pelanggan = $customer_data['alamat_lengkap'] ?: $customer_data['alamat'];
        $telepon_pelanggan = $customer_data['tlp_pelanggan'] ?: $customer_data['telephone'];
    }
    mysqli_stmt_close($stmt);
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
            $stmt2 = mysqli_prepare($koneksi, "SELECT vpk.pemilik, vpk.telephone, vpk.alamat, 
                                            tbl.nopelanggan, tbl.namapelanggan, tbl.alamat as alamat_lengkap, 
                                            tbl.telephone as tlp_pelanggan
                                            FROM view_pelanggan_kendaraan vpk
                                            LEFT JOIN tblpelanggan tbl ON vpk.pemilik = tbl.namapelanggan
                                            WHERE vpk.nopolisi = ?");
            mysqli_stmt_bind_param($stmt2, "s", $no_polisi);
            mysqli_stmt_execute($stmt2);
            $customer_result2 = mysqli_stmt_get_result($stmt2);
            
            if ($customer_data2 = mysqli_fetch_assoc($customer_result2)) {
                $nama_pelanggan = $customer_data2['pemilik'];
                $alamat_pelanggan = $customer_data2['alamat_lengkap'] ?: $customer_data2['alamat'];
                $telepon_pelanggan = $customer_data2['tlp_pelanggan'] ?: $customer_data2['telephone'];
            }
            mysqli_stmt_close($stmt2);
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

    // Handle file upload
    $foto_patokan = '';
    if (isset($_FILES['foto_patokan']) && $_FILES['foto_patokan']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/foto_patokan/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['foto_patokan']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = "patokan_" . date('YmdHis') . "_" . rand(1000, 9999) . "." . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['foto_patokan']['tmp_name'], $upload_path)) {
                $foto_patokan = "uploads/foto_patokan/" . $new_filename;
            }
        }
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
        
        // Redirect with success message to the service input page
        echo "<script>
            alert('Jadwal penjemputan berhasil disimpan!');
            window.location='servis-input-reguler-jemput-rst.php?snoserv=" . urlencode($no_service) . "';
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
            <?php include "menu_servis01.php"; ?>
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
                                                    <strong>No. Polisi:</strong> <?php echo htmlspecialchars($no_polisi); ?>
                                                </div>
                                                <div class="col-sm-6">
                                                    <strong>Telepon:</strong> <?php echo htmlspecialchars($telepon_pelanggan); ?><br>
                                                    <strong>Alamat:</strong> <?php echo htmlspecialchars($alamat_pelanggan); ?>
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
                                                            <button type="button" class="btn btn-info" onclick="window.open('popup-cari-kendaraan.php','popup','width=800,height=600')">
                                                                <i class="ace-icon fa fa-search"></i> Cari
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Kode Pelanggan:</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" id="txtpelanggan" name="txtpelanggan" value="<?php echo htmlspecialchars($no_pelanggan); ?>" placeholder="Kode pelanggan auto-fill..." readonly>
                                                    <small class="text-muted">Otomatis terisi berdasarkan nomor polisi yang dipilih</small>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Keterangan Penjemputan:</label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control" rows="3" id="txtketerangan" name="txtketerangan" placeholder="Catatan tambahan untuk penjemputan motor (keluhan awal, kondisi khusus, dll.)"><?php echo htmlspecialchars($keterangan_jemput); ?></textarea>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Upload Foto Patokan Rumah:</label>
                                                <div class="col-sm-9">
                                                    <div class="upload-area">
                                                        <i class="ace-icon fa fa-camera fa-2x" style="color: #ccc;"></i>
                                                        <p>Upload foto patokan/tampak rumah pelanggan</p>
                                                        <input type="file" name="foto_patokan" id="foto_patokan" accept="image/*" class="form-control">
                                                        <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB)</small>
                                                    </div>
                                                    <?php if (!empty($foto_patokan)) : ?>
                                                        <div style="margin-top: 10px;">
                                                            <p><strong>Foto saat ini:</strong></p>
                                                            <img src="../<?php echo htmlspecialchars($foto_patokan); ?>" class="foto-preview" alt="Foto Patokan">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="form-actions">
                                                <div class="row">
                                                    <div class="col-sm-offset-3 col-sm-9">
                                                        <button type="submit" name="btnjadwalkan" class="btn btn-success btn-lg">
                                                            <i class="ace-icon fa fa-calendar"></i> Jadwalkan Penjemputan & Lanjut ke Input Servis
                                                        </button>
                                                        <a href="servis-reguler.php" class="btn btn-default">
                                                            <i class="ace-icon fa fa-arrow-left"></i> Kembali
                                                        </a>
                                                        <?php if (!empty($no_service)) : ?>
                                                            <a href="servis-input-reguler-jemput-rst.php?snoserv=<?php echo urlencode($no_service); ?>" class="btn btn-primary">
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

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
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

        // Set default date and time
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const today = now.toISOString().split('T')[0];
            const currentTime = now.toTimeString().split(' ')[0].substring(0, 5);

            if (!document.getElementById('txttanggal').value) {
                document.getElementById('txttanggal').value = today;
            }
            if (!document.getElementById('txtjam').value) {
                document.getElementById('txtjam').value = currentTime;
            }

            // Initialize clock
            updateClock();
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
                            document.getElementById('txtpelanggan').value = response.data.no_pelanggan || nopol;
                            
                            // Update customer info display
                            updateCustomerInfoDisplay(response.data);
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
                const infoHtml = `
                    <div class="customer-info">
                        <h5><i class="ace-icon fa fa-user"></i> Informasi Pelanggan</h5>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Nama:</strong> ${data.nama_pelanggan}<br>
                                <strong>No. Polisi:</strong> ${data.no_polisi}
                            </div>
                            <div class="col-sm-6">
                                <strong>Telepon:</strong> ${data.telepon}<br>
                                <strong>Alamat:</strong> ${data.alamat}
                            </div>
                        </div>
                    </div>
                `;
                
                document.querySelector('.widget-main').insertAdjacentHTML('afterbegin', infoHtml);
            }
        }
    </script>
</body>
</html>