<?php
session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_user, password, user_akses, foto_user
                                    FROM tbuser WHERE id='$id_user'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];
    $pwd=$tm_cari['password'];
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // Check permission
    if($lvl_akses != 1 && $lvl_akses != 7 && $lvl_akses != 10) {
        header("location:index.php");
        exit();
    }

    // Data Cabang
    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_cabang, tipe_cabang
                                    FROM tbcabang
                                    WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari ? $tm_cari['nama_cabang'] : '';
    $tipe_cabang = $tm_cari ? $tm_cari['tipe_cabang'] : '';

    // Handle form submissions
    $message = '';
    $message_type = '';

    // Add Mechanic
    if(isset($_POST['btn_add_mekanik'])) {
        $nomekanik = mysqli_real_escape_string($koneksi, $_POST['nomekanik']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $telp = mysqli_real_escape_string($koneksi, $_POST['telp']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        $keahlian = mysqli_real_escape_string($koneksi, $_POST['keahlian']);
        $status = mysqli_real_escape_string($koneksi, $_POST['status']);
        $tanggal_masuk = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk']);
        $gaji_pokok = str_replace('.', '', $_POST['gaji_pokok']);
        $spesialisasi = mysqli_real_escape_string($koneksi, $_POST['spesialisasi']);
        $sertifikat = mysqli_real_escape_string($koneksi, $_POST['sertifikat']);

        // Check if mechanic code already exists
        $check_mekanik = mysqli_query($koneksi, "SELECT nomekanik FROM tblmekanik WHERE nomekanik='$nomekanik'");
        if(mysqli_num_rows($check_mekanik) > 0) {
            $message = "Kode mekanik sudah ada! Gunakan kode lain.";
            $message_type = "danger";
        } else {
            $query = "INSERT INTO tblmekanik (nomekanik, nama, alamat, telp, email, keahlian, status, tanggal_masuk, gaji_pokok, spesialisasi, sertifikat, created_at)
                     VALUES ('$nomekanik', '$nama', '$alamat', '$telp', '$email', '$keahlian', '$status', '$tanggal_masuk', '$gaji_pokok', '$spesialisasi', '$sertifikat', NOW())";

            if(mysqli_query($koneksi, $query)) {
                $message = "Mekanik berhasil ditambahkan!";
                $message_type = "success";

                // Auto create user account if requested
                if(isset($_POST['create_user_account']) && $_POST['create_user_account'] == 'yes') {
                    $username = strtolower($nomekanik);
                    $password = '123456'; // Default password
                    $user_akses = $keahlian == '1' ? 10 : 4; // 10 for head mechanic, 4 for regular mechanic
                    $role_name = $keahlian == '1' ? 'Kepala Mekanik' : 'Mekanik';

                    $user_query = "INSERT INTO tbuser (nama_user, password, user_akses, role_name, department, foto_user, status_row, is_active, created_at)
                                  VALUES ('$username', '$password', '$user_akses', '$role_name', 'Workshop', 'file_upload/avatar.png', '0', 'active', NOW())";

                    if(mysqli_query($koneksi, $user_query)) {
                        $new_user_id = mysqli_insert_id($koneksi);
                        // Link user with mechanic
                        $mapping_query = "INSERT INTO tb_user_mekanik_mapping (user_id, mekanik_code, is_primary) VALUES ('$new_user_id', '$nomekanik', 'yes')";
                        mysqli_query($koneksi, $mapping_query);

                        $message .= " User account '$username' juga berhasil dibuat dengan password default.";
                    }
                }
            } else {
                $message = "Error: " . mysqli_error($koneksi);
                $message_type = "danger";
            }
        }
    }

    // Update Mechanic
    if(isset($_POST['btn_update_mekanik'])) {
        $nomekanik = mysqli_real_escape_string($koneksi, $_POST['nomekanik']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $telp = mysqli_real_escape_string($koneksi, $_POST['telp']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        $keahlian = mysqli_real_escape_string($koneksi, $_POST['keahlian']);
        $status = mysqli_real_escape_string($koneksi, $_POST['status']);
        $tanggal_masuk = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk']);
        $gaji_pokok = str_replace('.', '', $_POST['gaji_pokok']);
        $spesialisasi = mysqli_real_escape_string($koneksi, $_POST['spesialisasi']);
        $sertifikat = mysqli_real_escape_string($koneksi, $_POST['sertifikat']);

        $query = "UPDATE tblmekanik SET
                  nama='$nama',
                  alamat='$alamat',
                  telp='$telp',
                  email='$email',
                  keahlian='$keahlian',
                  status='$status',
                  tanggal_masuk='$tanggal_masuk',
                  gaji_pokok='$gaji_pokok',
                  spesialisasi='$spesialisasi',
                  sertifikat='$sertifikat',
                  updated_at=NOW()
                  WHERE nomekanik='$nomekanik'";

        if(mysqli_query($koneksi, $query)) {
            $message = "Data mekanik berhasil diupdate!";
            $message_type = "success";
        } else {
            $message = "Error: " . mysqli_error($koneksi);
            $message_type = "danger";
        }
    }

    // Delete Mechanic
    if(isset($_GET['delete_code'])) {
        $delete_code = mysqli_real_escape_string($koneksi, $_GET['delete_code']);

        // Delete from mapping table first
        mysqli_query($koneksi, "DELETE FROM tb_user_mekanik_mapping WHERE mekanik_code='$delete_code'");

        // Delete mechanic
        $query = "DELETE FROM tblmekanik WHERE nomekanik='$delete_code'";
        if(mysqli_query($koneksi, $query)) {
            $message = "Mekanik berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Error: " . mysqli_error($koneksi);
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="Mechanic Management" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->
        <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
        <link rel="stylesheet" href="assets/css/chosen.min.css" />
        <link rel="stylesheet" href="assets/css/bootstrap-datepicker3.min.css" />

        <!-- text fonts -->
        <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

        <!-- ace styles -->
        <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
        <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <!-- ace settings handler -->
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
            </div>
        </div>

        <div class="main-container ace-save-state" id="main-container">
            <div id="sidebar" class="sidebar responsive ace-save-state">
                <?php include "menu_dashboard.php"; ?>
                <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                    <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
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
                                <a href="#">Data Master</a>
                            </li>
                            <li class="active">Mechanic Management</li>
                        </ul>
                    </div>

                    <div class="page-content">

                        <!-- Display Messages -->
                        <?php if(!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fa fa-<?php echo $message_type == 'success' ? 'check' : 'ban'; ?>"></i>
                            <?php echo $message; ?>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-blue widget-header-flat">
                                        <h4 class="widget-title lighter">
                                            <i class="ace-icon fa fa-wrench orange"></i>
                                            Mechanic Management System
                                        </h4>
                                        <div class="widget-toolbar">
                                            <a href="#" data-action="collapse">
                                                <i class="ace-icon fa fa-chevron-up"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="widget-body">
                                        <div class="widget-main">

                                            <!-- Add Mechanic Button -->
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addMekanikModal">
                                                        <i class="ace-icon fa fa-plus"></i>
                                                        Tambah Mekanik Baru
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="space-6"></div>

                                            <!-- Mechanics Table -->
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="table-responsive">
                                                        <table id="dynamic-table" class="table table-striped table-bordered table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Kode</th>
                                                                    <th>Nama</th>
                                                                    <th>Keahlian</th>
                                                                    <th>Telepon</th>
                                                                    <th>Status</th>
                                                                    <th>Gaji Pokok</th>
                                                                    <th>User Account</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                            <?php
                                                                $query = "SELECT m.*,
                                                                         CASE m.keahlian
                                                                            WHEN '1' THEN 'Kepala Mekanik'
                                                                            WHEN '2' THEN 'Mekanik Senior'
                                                                            WHEN '3' THEN 'Mekanik Junior'
                                                                            ELSE 'Tidak Ditentukan'
                                                                         END as keahlian_text,
                                                                         u.nama_user as username,
                                                                         u.is_active as user_status
                                                                         FROM tblmekanik m
                                                                         LEFT JOIN tb_user_mekanik_mapping umm ON m.nomekanik = umm.mekanik_code
                                                                         LEFT JOIN tbuser u ON umm.user_id = u.id
                                                                         ORDER BY m.keahlian, m.nama";
                                                                $result = mysqli_query($koneksi, $query);
                                                                while($row = mysqli_fetch_assoc($result)):
                                                            ?>
                                                                <tr>
                                                                    <td>
                                                                        <strong><?php echo $row['nomekanik']; ?></strong>
                                                                    </td>
                                                                    <td>
                                                                        <strong><?php echo htmlspecialchars($row['nama']); ?></strong>
                                                                        <?php if($row['email']): ?>
                                                                        <br><small class="text-muted">
                                                                            <i class="fa fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?>
                                                                        </small>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <span class="label label-<?php
                                                                            echo $row['keahlian'] == '1' ? 'danger' :
                                                                                ($row['keahlian'] == '2' ? 'warning' : 'info');
                                                                        ?>">
                                                                            <?php echo $row['keahlian_text']; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($row['telp'] ?? '-'); ?></td>
                                                                    <td>
                                                                        <span class="label label-<?php echo $row['status'] == 'aktif' ? 'success' : 'danger'; ?>">
                                                                            <?php echo ucfirst($row['status']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if($row['gaji_pokok']) {
                                                                            echo 'Rp ' . number_format($row['gaji_pokok'], 0, ',', '.');
                                                                        } else {
                                                                            echo '<span class="text-muted">-</span>';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if($row['username']): ?>
                                                                        <span class="label label-success">
                                                                            <i class="fa fa-user"></i> <?php echo $row['username']; ?>
                                                                        </span>
                                                                        <?php if($row['user_status'] == 'inactive'): ?>
                                                                        <br><small class="text-danger">Inactive</small>
                                                                        <?php endif; ?>
                                                                        <?php else: ?>
                                                                        <span class="text-muted">No account</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="center">
                                                                        <div class="btn-group">
                                                                            <button class="btn btn-xs btn-info" onclick="editMekanik('<?php echo $row['nomekanik']; ?>')" title="Edit Mekanik">
                                                                                <i class="ace-icon fa fa-pencil"></i>
                                                                            </button>
                                                                            <button class="btn btn-xs btn-success" onclick="viewDetail('<?php echo $row['nomekanik']; ?>')" title="View Detail">
                                                                                <i class="ace-icon fa fa-eye"></i>
                                                                            </button>
                                                                            <a href="?delete_code=<?php echo $row['nomekanik']; ?>"
                                                                               class="btn btn-xs btn-danger"
                                                                               onclick="return confirm('Are you sure you want to delete this mechanic?')"
                                                                               title="Delete Mekanik">
                                                                                <i class="ace-icon fa fa-trash"></i>
                                                                            </a>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php endwhile; ?>
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
                </div>
            </div>

            <!-- Add Mechanic Modal -->
            <div class="modal fade" id="addMekanikModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">
                                    <i class="ace-icon fa fa-user-plus"></i>
                                    Tambah Mekanik Baru
                                </h4>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Kode Mekanik <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nomekanik" required placeholder="MK001">
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nama" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Alamat</label>
                                            <textarea class="form-control" name="alamat" rows="3"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Telepon</label>
                                            <input type="text" class="form-control" name="telp">
                                        </div>
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="email">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Keahlian <span class="text-danger">*</span></label>
                                            <select class="form-control" name="keahlian" required>
                                                <option value="">- Pilih Keahlian -</option>
                                                <option value="1">Kepala Mekanik</option>
                                                <option value="2">Mekanik Senior</option>
                                                <option value="3">Mekanik Junior</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select class="form-control" name="status">
                                                <option value="aktif">Aktif</option>
                                                <option value="nonaktif">Non-Aktif</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Tanggal Masuk</label>
                                            <input type="date" class="form-control" name="tanggal_masuk">
                                        </div>
                                        <div class="form-group">
                                            <label>Gaji Pokok</label>
                                            <input type="text" class="form-control money" name="gaji_pokok" placeholder="0">
                                        </div>
                                        <div class="form-group">
                                            <label>Spesialisasi</label>
                                            <textarea class="form-control" name="spesialisasi" rows="2" placeholder="Contoh: Mesin, Kelistrikan, Body"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Sertifikat</label>
                                            <textarea class="form-control" name="sertifikat" rows="2" placeholder="Sertifikat yang dimiliki"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>
                                                <input type="checkbox" name="create_user_account" value="yes" class="ace">
                                                <span class="lbl"> Buat User Account</span>
                                            </label>
                                            <br><small class="text-muted">Otomatis membuat akun login dengan username = kode mekanik</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <button type="submit" name="btn_add_mekanik" class="btn btn-primary">
                                    <i class="ace-icon fa fa-save"></i>
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Mechanic Modal -->
            <div class="modal fade" id="editMekanikModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">
                                    <i class="ace-icon fa fa-edit"></i>
                                    Edit Mekanik
                                </h4>
                            </div>
                            <div class="modal-body" id="editMekanikContent">
                                <!-- Content will be loaded via AJAX -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <button type="submit" name="btn_update_mekanik" class="btn btn-primary">
                                    <i class="ace-icon fa fa-save"></i>
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View Detail Modal -->
            <div class="modal fade" id="viewDetailModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title">
                                <i class="ace-icon fa fa-eye"></i>
                                Detail Mekanik
                            </h4>
                        </div>
                        <div class="modal-body" id="viewDetailContent">
                            <!-- Content will be loaded via AJAX -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
        </div>

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- page specific plugin scripts -->
        <script src="assets/js/jquery.dataTables.min.js"></script>
        <script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
        <script src="assets/js/dataTables.buttons.min.js"></script>
        <script src="assets/js/buttons.flash.min.js"></script>
        <script src="assets/js/buttons.html5.min.js"></script>
        <script src="assets/js/buttons.print.min.js"></script>
        <script src="assets/js/buttons.colVis.min.js"></script>
        <script src="assets/js/bootstrap-datepicker.min.js"></script>
        <script src="assets/js/jquery.maskedinput.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <script type="text/javascript">
            jQuery(function($) {
                // Initialize DataTable
                $('#dynamic-table').DataTable({
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ],
                    order: [[ 2, "asc" ], [ 1, "asc" ]]
                });

                // Money format
                $('.money').mask('#.##0', {reverse: true});

                // Date picker
                $('.date-picker').datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    format: 'yyyy-mm-dd'
                });
            });

            // Edit mechanic function
            function editMekanik(mekanikCode) {
                $.ajax({
                    url: 'mekanik_management_ajax.php',
                    type: 'POST',
                    data: {action: 'get_mekanik', mekanik_code: mekanikCode},
                    success: function(response) {
                        $('#editMekanikContent').html(response);
                        $('#editMekanikModal').modal('show');
                    },
                    error: function() {
                        alert('Error loading mechanic data');
                    }
                });
            }

            // View detail function
            function viewDetail(mekanikCode) {
                $.ajax({
                    url: 'mekanik_management_ajax.php',
                    type: 'POST',
                    data: {action: 'view_detail', mekanik_code: mekanikCode},
                    success: function(response) {
                        $('#viewDetailContent').html(response);
                        $('#viewDetailModal').modal('show');
                    },
                    error: function() {
                        alert('Error loading mechanic detail');
                    }
                });
            }

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        </script>
    </body>
</html>