<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    	$id_user=$_SESSION['_iduser'];	
	$kd_cabang=$_SESSION['_cabang'];		            
	include "../config/koneksi.php";
	include "../config/permission_check.php";

	// Optional debug mode: add ?debug=1 to URL for detailed errors (dev only)
	if (isset($_GET['debug']) && $_GET['debug'] == '1') {
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}

	// Get user data from session (set at login)
	$_nama = $_SESSION['_nama_lengkap'] ?? $_SESSION['_username'] ?? 'User';
	$lvl_akses = $_SESSION['user_akses'] ?? 0;
	$foto_user = $_SESSION['_foto'] ?? 'file_upload/avatar.png';
	if (empty($foto_user)) {
		$foto_user = "file_upload/avatar.png";
	}

	// Permission gate: allow Admin (1) and Kepala Mekanik (10); others require explicit permission
	if (!isAdmin() && (int)$lvl_akses !== 10) {
		checkPermissionOrDie('workorder', 'edit');
	}

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang 
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");			
    if ($cari_kd && mysqli_num_rows($cari_kd) > 0) {
        $tm_cari=mysqli_fetch_array($cari_kd);
        $nama_cabang=$tm_cari['nama_cabang'];			        
        $tipe_cabang=$tm_cari['tipe_cabang'];	
    } else {
        $nama_cabang = 'Cabang';
        $tipe_cabang = '';
    }
    // --------------------

    $tanggal_hari_ini = date('Y-m-d');
    $tanggal_display = date('d F Y');

    // Check if already input for today
    $check_today = mysqli_query($koneksi, "SELECT * FROM tbl_kepala_mekanik_harian 
                                          WHERE kode_cabang='$kd_cabang' 
                                          AND tanggal_kerja='$tanggal_hari_ini'");
    if ($check_today) {
        $sudah_input = mysqli_num_rows($check_today) > 0;
        $data_hari_ini = $sudah_input ? mysqli_fetch_array($check_today) : null;
    } else {
        $sudah_input = false;
        $data_hari_ini = null;
    }

    // Get Kepala Mekanik list from tbl_master_kepala_mekanik (data real KM per cabang)
    $master_kepala = mysqli_query($koneksi, "SELECT
                                                nip_karyawan AS id,
                                                nama_kepala_mekanik
                                            FROM tbl_master_kepala_mekanik
                                            WHERE kode_cabang='$kd_cabang'
                                              AND status_aktif='aktif'
                                              AND nip_karyawan NOT LIKE 'KRY-%'
                                            ORDER BY nama_kepala_mekanik");

    // Fallback: jika tidak ada hasil untuk cabang saat ini, ambil semua KM aktif (tanpa filter cabang)
    if ($master_kepala && mysqli_num_rows($master_kepala) === 0) {
        $master_kepala = mysqli_query($koneksi, "SELECT
                                                    nip_karyawan AS id,
                                                    nama_kepala_mekanik
                                                FROM tbl_master_kepala_mekanik
                                                WHERE status_aktif='aktif'
                                                  AND nip_karyawan NOT LIKE 'KRY-%'
                                                ORDER BY nama_kepala_mekanik");
    } elseif (!$master_kepala) {
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            $pesan = 'Error load data Kepala Mekanik: ' . mysqli_error($koneksi);
            $tipe_pesan = 'danger';
        }
    }

    // ---- Tim Mekanik Hari Ini: dari fitmotor_prototype.masterkeys, dicocokkan ke tblmekanik ----
    // Join by kode_cabang -> tbcabang.cabang_ref_kode (bukan tbcabang.kode_cabang, beda skema penomoran)
    // lalu match nama ke tblmekanik untuk memastikan hanya staff dengan role mekanik yang muncul.
    // GROUP BY nama: tblmekanik punya beberapa kode duplikat untuk nama yang sama (data quality lama),
    // jadi diambil satu kode representatif (MIN) per nama supaya checklist tidak dobel.
    $tim_mekanik_cabang = [];
    $q_tim = mysqli_query($koneksi, "SELECT MIN(tm.nomekanik) AS kode_karyawan, mk.nama_karyawan AS nama
                                      FROM fitmotor_prototype.masterkeys mk
                                      INNER JOIN tbcabang c ON c.cabang_ref_kode = mk.kode_cabang
                                      INNER JOIN tblmekanik tm ON LOWER(TRIM(tm.nama)) = LOWER(TRIM(mk.nama_karyawan))
                                                               AND tm.status='aktif'
                                      WHERE c.kode_cabang = '$kd_cabang'
                                        AND mk.status_aktif = 1
                                      GROUP BY mk.nama_karyawan
                                      ORDER BY mk.nama_karyawan");
    if ($q_tim) {
        while ($row = mysqli_fetch_assoc($q_tim)) {
            $tim_mekanik_cabang[] = $row;
        }
    }

    // Mekanik yang sudah ditandai hadir untuk tanggal hari ini di cabang ini
    $mekanik_hadir_hari_ini = [];
    $q_hadir = mysqli_query($koneksi, "SELECT kode_karyawan FROM tb_kepala_mekanik_schedule
                                        WHERE kode_cabang='$kd_cabang'
                                          AND tanggal_kerja='$tanggal_hari_ini'
                                          AND status_kehadiran='hadir'");
    if ($q_hadir) {
        while ($row = mysqli_fetch_assoc($q_hadir)) {
            $mekanik_hadir_hari_ini[] = $row['kode_karyawan'];
        }
    }

    // Handle form submission
    if(isset($_POST['btnsimpan']) || isset($_POST['btnupdate'])) {
        $tanggal_kerja = mysqli_real_escape_string($koneksi, $_POST['tanggal_kerja']);
        $kepala_mekanik_1 = mysqli_real_escape_string($koneksi, $_POST['kepala_mekanik_1']);
        $kepala_mekanik_2 = mysqli_real_escape_string($koneksi, $_POST['kepala_mekanik_2']);
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

        // Server-side validation: KM1 and KM2 cannot be the same (when both filled)
        // Server-side validation: KM1 and KM2 cannot be the same (when both filled)
        if ($kepala_mekanik_1 !== '' && $kepala_mekanik_2 !== '' && $kepala_mekanik_1 === $kepala_mekanik_2) {
            $pesan = "Kepala Mekanik 1 dan 2 tidak boleh sama.";
            $tipe_pesan = "danger";
        } else {
            // Check if data already exists for this date and branch
            $check_exist = mysqli_query($koneksi, "SELECT id FROM tbl_kepala_mekanik_harian 
                                                  WHERE kode_cabang='$kd_cabang' 
                                                  AND tanggal_kerja='$tanggal_kerja'");
            $is_update = mysqli_num_rows($check_exist) > 0;

            if(!$is_update) {
                // Insert new record
                $sql = "INSERT INTO tbl_kepala_mekanik_harian
                        (kode_cabang, tanggal_kerja, kepala_mekanik_1, kepala_mekanik_2, keterangan, created_by)
                        VALUES ('$kd_cabang', '$tanggal_kerja', '$kepala_mekanik_1', '$kepala_mekanik_2', '$keterangan', '$id_user')";
            } else {
                // Update existing record
                $sql = "UPDATE tbl_kepala_mekanik_harian SET
                        kepala_mekanik_1='$kepala_mekanik_1',
                        kepala_mekanik_2='$kepala_mekanik_2',
                        keterangan='$keterangan'
                        WHERE kode_cabang='$kd_cabang' AND tanggal_kerja='$tanggal_kerja'";
            }
            
            if(mysqli_query($koneksi, $sql)) {
			$pesan = "Data kepala mekanik harian berhasil " . (isset($_POST['btnsimpan']) ? "disimpan" : "diupdate") . "!";
			$tipe_pesan = "success";
			// Log activity
			logActivity('SUCCESS', 'workorder', "Set Kepala Mekanik $tanggal_kerja: KM1=$kepala_mekanik_1, KM2=$kepala_mekanik_2");

			// Sync Tim Mekanik Hari Ini (checklist kehadiran) -> tb_kepala_mekanik_schedule
			// Re-sync penuh per cabang+tanggal: hapus lalu insert ulang yang dicentang sebagai 'hadir'.
			$tim_mekanik_post = $_POST['tim_mekanik'] ?? [];
			mysqli_query($koneksi, "DELETE FROM tb_kepala_mekanik_schedule
			                         WHERE kode_cabang='$kd_cabang' AND tanggal_kerja='$tanggal_kerja'");
			if (is_array($tim_mekanik_post)) {
				foreach ($tim_mekanik_post as $kode_mk) {
					$kode_mk_safe = mysqli_real_escape_string($koneksi, $kode_mk);
					mysqli_query($koneksi, "INSERT INTO tb_kepala_mekanik_schedule
					                         (kode_karyawan, kode_cabang, tanggal_kerja, shift, status_kehadiran)
					                         VALUES ('$kode_mk_safe', '$kd_cabang', '$tanggal_kerja', 'full', 'hadir')");
				}
			}
			$mekanik_hadir_hari_ini = is_array($tim_mekanik_post) ? $tim_mekanik_post : [];

			// Refresh data
			$check_today = mysqli_query($koneksi, "SELECT * FROM tbl_kepala_mekanik_harian
			                                  WHERE kode_cabang='$kd_cabang'
			                                  AND tanggal_kerja='$tanggal_hari_ini'");
			$data_hari_ini = mysqli_fetch_array($check_today);
			$sudah_input = mysqli_num_rows($check_today) > 0;
		} else {
			$pesan = "Error: " . mysqli_error($koneksi);
			$tipe_pesan = "danger";
			// Log failure
			logActivity('FAILED', 'workorder', "Error set Kepala Mekanik $tanggal_kerja: " . mysqli_error($koneksi));
		}
        }
        }

    // Get history data (last 7 days)
    $history_query = mysqli_query($koneksi, "SELECT * FROM tbl_kepala_mekanik_harian 
                                            WHERE kode_cabang='$kd_cabang' 
                                            AND tanggal_kerja >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                            ORDER BY tanggal_kerja DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Daftar Tim Mekanik Hari Ini - <?php echo $nama_cabang; ?></title>

    <meta name="description" content="Daftar Tim Mekanik Hari Ini" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- page specific plugin styles -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/bootstrap-datepicker3.min.css" />
    <link rel="stylesheet" href="assets/css/select2.min.css" />

    <!-- text fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- ace styles -->
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .today-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .today-section h2 {
            color: white;
            margin-bottom: 10px;
        }
        .status-badge {
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
        }
        .status-completed {
            background: #28a745;
            color: white;
        }
        .status-pending {
            background: #ffc107;
            color: #212529;
        }
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .history-table {
            margin-top: 20px;
        }
        .kepala-mekanik-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .kepala-mekanik-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }
        .quick-select {
            margin-top: 10px;
        }
        .quick-select .btn {
            margin: 2px;
            font-size: 12px;
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
                                    <?php include "../lib/logo.php"; ?>
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
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profile" />
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
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li>
                            <a href="#">Servis</a>
                        </li>                            
                        <li class="active">Daftar Tim Mekanik Hari Ini</li>
                    </ul>
                </div>

                <div class="page-content">
                    <!-- Today Status Section -->
                    <div class="today-section">
                        <div class="row">
                            <div class="col-md-8">
                                <h2>
                                    <i class="ace-icon fa fa-calendar"></i>
                                    Daftar Tim Mekanik Hari Ini
                                </h2>
                                <h4><?php echo $tanggal_display; ?> - <?php echo $nama_cabang; ?></h4>
                                
                                <?php if($sudah_input): ?>
                                <div class="status-badge status-completed">
                                    <i class="ace-icon fa fa-check-circle"></i>
                                    Sudah Input - <?php echo $data_hari_ini['kepala_mekanik_1']; ?>
                                    <?php if($data_hari_ini['kepala_mekanik_2']): ?>
                                    & <?php echo $data_hari_ini['kepala_mekanik_2']; ?>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="status-badge status-pending">
                                    <i class="ace-icon fa fa-exclamation-triangle"></i>
                                    Belum Input Kepala Mekanik Hari Ini
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-right">
                                <div style="margin-top: 20px;">
                                    <a href="master_karyawan.php" class="btn btn-white btn-sm">
                                        <i class="ace-icon fa fa-cog"></i>
                                        Master Kepala Mekanik
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if(isset($pesan)): ?>
                    <div class="alert alert-<?php echo $tipe_pesan; ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="ace-icon fa fa-<?php echo $tipe_pesan == 'success' ? 'check' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $pesan; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Form Input -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-<?php echo $sudah_input ? 'edit' : 'plus'; ?>"></i>
                                        <?php echo $sudah_input ? 'Update' : 'Input'; ?> Kepala Mekanik Harian
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form class="form-horizontal" method="post" action="">
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Tanggal Kerja</label>
                                                <div class="col-sm-9">
                                                    <div class="input-group">
                                                        <input type="text" name="tanggal_kerja" class="form-control date-picker" 
                                                               value="<?php echo $sudah_input ? $data_hari_ini['tanggal_kerja'] : $tanggal_hari_ini; ?>" 
                                                               data-date-format="yyyy-mm-dd" required readonly />
                                                        <span class="input-group-addon">
                                                            <i class="fa fa-calendar bigger-110"></i>
                                                        </span>
                                                    </div>
                                                    <span class="help-block">
                                                        <small>Tanggal kerja untuk hari ini</small>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="kepala-mekanik-card">
                                                <h5><i class="ace-icon fa fa-user blue"></i> Kepala Mekanik 1 (Utama)</h5>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Nama</label>
                                                    <div class="col-sm-9">
                                                        <select name="kepala_mekanik_1" class="form-control select2" required>
                                                            <option value=""></option>
                                                            <?php 
                                                            if ($master_kepala && mysqli_num_rows($master_kepala) > 0) {
                                                                mysqli_data_seek($master_kepala, 0);
                                                                while($km = mysqli_fetch_array($master_kepala)):
                                                            ?>
                                                            <option value="<?php echo htmlspecialchars($km['nama_kepala_mekanik']); ?>"
                                                                    <?php echo ($sudah_input && $data_hari_ini && $data_hari_ini['kepala_mekanik_1'] == $km['nama_kepala_mekanik']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($km['nama_kepala_mekanik']); ?>
                                                            </option>
                                                            <?php 
                                                                endwhile; 
                                                            } else { 
                                                            ?>
                                                            <option value="" disabled>Data Kepala Mekanik belum ada</option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="kepala-mekanik-card">
                                                <h5><i class="ace-icon fa fa-user green"></i> Kepala Mekanik 2 (Backup / Izin Setengah Hari)</h5>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Nama</label>
                                                    <div class="col-sm-9">
                                                        <select name="kepala_mekanik_2" class="form-control select2">
                                                            <option value=""></option>
                                                            <?php
                                                            if ($master_kepala && mysqli_num_rows($master_kepala) > 0) {
                                                                mysqli_data_seek($master_kepala, 0);
                                                                while($km = mysqli_fetch_array($master_kepala)):
                                                            ?>
                                                            <option value="<?php echo htmlspecialchars($km['nama_kepala_mekanik']); ?>"
                                                                    <?php echo ($sudah_input && $data_hari_ini && $data_hari_ini['kepala_mekanik_2'] == $km['nama_kepala_mekanik']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($km['nama_kepala_mekanik']); ?>
                                                            </option>
                                                            <?php 
                                                                endwhile; 
                                                            } else { 
                                                            ?>
                                                            <option value="" disabled>Data Kepala Mekanik belum ada</option>
                                                            <?php } ?>
                                                        </select>
                                                        <span class="help-block">
                                                            <small class="text-muted">
                                                                <i class="ace-icon fa fa-info-circle"></i>
                                                                Isi jika ada kepala mekanik yang izin setengah hari atau sebagai backup
                                                            </small>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="kepala-mekanik-card">
                                                <h5><i class="ace-icon fa fa-users orange"></i> Tim Mekanik Hari Ini</h5>
                                                <?php if (empty($tim_mekanik_cabang)): ?>
                                                <p class="text-muted"><small>Data tim mekanik cabang ini belum tersedia.</small></p>
                                                <?php else: ?>
                                                <div class="row">
                                                    <?php foreach ($tim_mekanik_cabang as $tm): ?>
                                                    <div class="col-sm-6">
                                                        <label class="checkbox-inline">
                                                            <input type="checkbox" name="tim_mekanik[]"
                                                                   value="<?php echo htmlspecialchars($tm['kode_karyawan']); ?>"
                                                                   <?php echo in_array($tm['kode_karyawan'], $mekanik_hadir_hari_ini) ? 'checked' : ''; ?> />
                                                            <?php echo htmlspecialchars($tm['nama']); ?>
                                                        </label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <span class="help-block">
                                                    <small class="text-muted">
                                                        <i class="ace-icon fa fa-info-circle"></i>
                                                        Centang mekanik yang hadir/tugas hari ini. Dipakai untuk pilihan mekanik saat input servis.
                                                    </small>
                                                </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Keterangan</label>
                                                <div class="col-sm-9">
                                                    <textarea name="keterangan" class="form-control" rows="3"
                                                              placeholder="Contoh: KM1 izin setengah hari (pagi), digantikan KM2 (siang)"><?php echo $sudah_input ? htmlspecialchars($data_hari_ini['keterangan']) : ''; ?></textarea>
                                                    <span class="help-block">
                                                        <small class="text-muted">
                                                            <i class="ace-icon fa fa-lightbulb-o"></i>
                                                            Jelaskan pembagian waktu kerja jika ada kepala mekanik yang izin setengah hari
                                                        </small>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <div class="col-sm-offset-3 col-sm-9">
                                                    <button type="submit" name="<?php echo $sudah_input ? 'btnupdate' : 'btnsimpan'; ?>" class="btn btn-primary btn-lg">
                                                        <i class="ace-icon fa fa-<?php echo $sudah_input ? 'save' : 'plus'; ?>"></i>
                                                        <?php echo $sudah_input ? 'Update Data Hari Ini' : 'Simpan Data Hari Ini'; ?>
                                                    </button>
                                                    <a href="servis-carinopol.php" class="btn btn-success btn-lg">
                                                        <i class="ace-icon fa fa-arrow-right"></i>
                                                        Lanjut Input Servis
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- History -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-history"></i>
                                        History 7 Hari Terakhir
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="80">Tanggal</th>
                                                        <th>Kepala Mekanik</th>
                                                        <th>Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($history_query && mysqli_num_rows($history_query) > 0): ?>
                                                        <?php while($history = mysqli_fetch_array($history_query)): ?>
                                                        <tr <?php echo ($history['tanggal_kerja'] == $tanggal_hari_ini) ? 'class="success"' : ''; ?>>
                                                            <td>
                                                                <strong><?php echo date('d/m', strtotime($history['tanggal_kerja'])); ?></strong>
                                                                <?php if($history['tanggal_kerja'] == $tanggal_hari_ini): ?>
                                                                <br><small class="text-success"><strong>Hari Ini</strong></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <strong class="blue"><?php echo htmlspecialchars($history['kepala_mekanik_1']); ?></strong>
                                                                    <?php if($history['kepala_mekanik_2']): ?>
                                                                    <br><span class="green"><?php echo htmlspecialchars($history['kepala_mekanik_2']); ?></span>
                                                                    <span class="label label-info label-xs">Backup</span>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php
                                                                    if($history['keterangan']) {
                                                                        echo htmlspecialchars(substr($history['keterangan'], 0, 30));
                                                                        if(strlen($history['keterangan']) > 30) echo '...';
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                    ?>
                                                                </small>
                                                            </td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="3" class="text-center"><em>Belum ada data</em></td>
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
    </div>

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">
        if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
    </script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- page specific plugin scripts -->
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/select2.min.js"></script>

    <!-- ace scripts -->
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        jQuery(function($) {
            // Initialize Select2
            $('.select2').select2({
                placeholder: "Pilih Kepala Mekanik",
                allowClear: true,
                width: '100%'
            });

            // Date picker
            $('.date-picker').datepicker({
                autoclose: true,
                todayHighlight: true,
                format: 'yyyy-mm-dd'
            });

            // Prevent selecting same person for both positions
            $('select[name="kepala_mekanik_1"], select[name="kepala_mekanik_2"]').on('change', function() {
                var km1 = $('select[name="kepala_mekanik_1"]').val();
                var km2 = $('select[name="kepala_mekanik_2"]').val();
                
                if (km1 && km2 && km1 === km2) {
                    alert('Kepala Mekanik 1 dan 2 tidak boleh sama!');
                    $(this).val('').trigger('change');
                }
            });
        });
    </script>
</body>
</html>

<?php 
}
?>
