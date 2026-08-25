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

    require_once "_include_menu_rbac.php";
    if (!canAccessPage($koneksi, $id_user, 'user_read')) {
        header("location:403.php?permission=user_read");
        exit();
    }

    $role_options = [];
    $role_result = mysqli_query(
        $koneksi,
        "SELECT kode_posisi, nama_posisi, departemen, user_akses_level
         FROM tb_master_posisi
         WHERE is_active='active'
         ORDER BY departemen ASC, nama_posisi ASC"
    );
    if ($role_result) {
        while ($role_row = mysqli_fetch_assoc($role_result)) {
            $role_options[] = $role_row;
        }
    }

    $role_lookup = [];
    foreach ($role_options as $role_option) {
        $role_lookup[$role_option['kode_posisi']] = [
            'kode_posisi' => $role_option['kode_posisi'],
            'name' => $role_option['nama_posisi'],
            'dept' => $role_option['departemen'],
            'user_akses' => (int) $role_option['user_akses_level'],
            'is_workshop' => in_array($role_option['kode_posisi'], ['MK', 'KM'], true),
        ];
    }

    $renderRoleOptions = function ($selected = '') use ($role_options) {
        $html = '<option value="">- Pilih Role -</option>';
        foreach ($role_options as $role_option) {
            $isSelected = $selected === $role_option['kode_posisi'] ? ' selected' : '';
            $label = htmlspecialchars($role_option['nama_posisi'] . ' [' . $role_option['kode_posisi'] . ']');
            $value = htmlspecialchars($role_option['kode_posisi']);
            $html .= "<option value=\"{$value}\"{$isSelected}>{$label}</option>";
        }
        return $html;
    };

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

    // Add User
    if(isset($_POST['btn_add_user'])) {
        $nama_user = mysqli_real_escape_string($koneksi, $_POST['nama_user']);
        $password = mysqli_real_escape_string($koneksi, $_POST['password']);
        $kode_posisi = mysqli_real_escape_string($koneksi, trim($_POST['kode_posisi'] ?? ''));
        $is_active = mysqli_real_escape_string($koneksi, $_POST['is_active']);

        if (!isset($role_lookup[$kode_posisi])) {
            $message = "Role/posisi tidak valid.";
            $message_type = "danger";
        } else {
            $user_akses = $role_lookup[$kode_posisi]['user_akses'];
            $role_name = mysqli_real_escape_string($koneksi, $role_lookup[$kode_posisi]['name']);
            $department = mysqli_real_escape_string($koneksi, $role_lookup[$kode_posisi]['dept']);

            // Check if username already exists
            $check_user = mysqli_query($koneksi, "SELECT id FROM tbuser WHERE nama_user='$nama_user'");
            if(mysqli_num_rows($check_user) > 0) {
                $message = "Username sudah ada! Gunakan username lain.";
                $message_type = "danger";
            } else {
                $query = "INSERT INTO tbuser (nama_user, password, user_akses, kode_posisi, role_name, department, foto_user, status_row, is_active, created_at)
                         VALUES ('$nama_user', '$password', '$user_akses', '$kode_posisi', '$role_name', '$department', 'file_upload/avatar.png', '0', '$is_active', NOW())";

                if(mysqli_query($koneksi, $query)) {
                    $message = "User berhasil ditambahkan!";
                    $message_type = "success";

                    if($role_lookup[$kode_posisi]['is_workshop']) {
                        $mekanik_code = $_POST['mekanik_code'] ?? '';
                        if(!empty($mekanik_code)) {
                            $user_id = mysqli_insert_id($koneksi);
                            $mapping_query = "INSERT INTO tb_user_mekanik_mapping (user_id, mekanik_code, is_primary) VALUES ('$user_id', '$mekanik_code', 'yes')";
                            mysqli_query($koneksi, $mapping_query);
                        }
                    }
                } else {
                    $message = "Error: " . mysqli_error($koneksi);
                    $message_type = "danger";
                }
            }
        }
    }

    // Update User
    if(isset($_POST['btn_update_user'])) {
        $user_id = intval($_POST['user_id']);
        $nama_user = mysqli_real_escape_string($koneksi, $_POST['nama_user']);
        $kode_posisi = mysqli_real_escape_string($koneksi, trim($_POST['kode_posisi'] ?? ''));
        $is_active = mysqli_real_escape_string($koneksi, $_POST['is_active']);

        if (!isset($role_lookup[$kode_posisi])) {
            $message = "Role/posisi tidak valid.";
            $message_type = "danger";
        } else {
            $user_akses = $role_lookup[$kode_posisi]['user_akses'];
            $role_name = mysqli_real_escape_string($koneksi, $role_lookup[$kode_posisi]['name']);
            $department = mysqli_real_escape_string($koneksi, $role_lookup[$kode_posisi]['dept']);

            $query = "UPDATE tbuser SET
                      nama_user='$nama_user',
                      user_akses='$user_akses',
                      kode_posisi='$kode_posisi',
                      role_name='$role_name',
                      department='$department',
                      is_active='$is_active',
                      updated_at=NOW()
                      WHERE id='$user_id'";

            if(mysqli_query($koneksi, $query)) {
                mysqli_query($koneksi, "DELETE FROM tb_user_mekanik_mapping WHERE user_id='$user_id'");
                if($role_lookup[$kode_posisi]['is_workshop']) {
                    $mekanik_code = $_POST['mekanik_code'] ?? '';
                    if(!empty($mekanik_code)) {
                        $mapping_query = "INSERT INTO tb_user_mekanik_mapping (user_id, mekanik_code, is_primary) VALUES ('$user_id', '$mekanik_code', 'yes')";
                        mysqli_query($koneksi, $mapping_query);
                    }
                }
                $message = "User berhasil diupdate!";
                $message_type = "success";
            } else {
                $message = "Error: " . mysqli_error($koneksi);
                $message_type = "danger";
            }
        }
    }

    // Change Password
    if(isset($_POST['btn_change_password'])) {
        $user_id = intval($_POST['user_id']);
        $new_password = mysqli_real_escape_string($koneksi, $_POST['new_password']);

        $query = "UPDATE tbuser SET password='$new_password', updated_at=NOW() WHERE id='$user_id'";

        if(mysqli_query($koneksi, $query)) {
            $message = "Password berhasil diubah!";
            $message_type = "success";
        } else {
            $message = "Error: " . mysqli_error($koneksi);
            $message_type = "danger";
        }
    }

    // Delete User
    if(isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']);

        // Delete from mapping table first
        mysqli_query($koneksi, "DELETE FROM tb_user_mekanik_mapping WHERE user_id='$delete_id'");

        // Delete user
        $query = "DELETE FROM tbuser WHERE id='$delete_id'";
        if(mysqli_query($koneksi, $query)) {
            $message = "User berhasil dihapus!";
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

        <meta name="description" content="User Management" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->
        <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
        <link rel="stylesheet" href="assets/css/chosen.min.css" />
        <link rel="stylesheet" href="assets/css/bootstrap-datepicker3.min.css" />
        <link rel="stylesheet" href="assets/css/bootstrap-timepicker.min.css" />
        <link rel="stylesheet" href="assets/css/daterangepicker.min.css" />
        <link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css" />
        <link rel="stylesheet" href="assets/css/bootstrap-colorpicker.min.css" />

        <!-- text fonts -->
        <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

        <!-- ace styles -->
        <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

        <!--[if lte IE 9]>
            <link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
        <![endif]-->
        <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <!--[if lte IE 9]>
          <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
        <![endif]-->

        <!-- inline styles related to this page -->
        <link rel="stylesheet" href="assets/css/jquery.gritter.min.css" />

        <!-- ace settings handler -->
        <script src="assets/js/ace-extra.min.js"></script>

        <!--[if lte IE 8]>
        <script src="assets/js/html5shiv.min.js"></script>
        <script src="assets/js/respond.min.js"></script>
        <![endif]-->
    </head>

    <body class="no-skin">
        <div id="navbar" class="navbar navbar-default          ace-save-state">
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
            </div><!-- /.navbar-container -->
        </div>

        <div class="main-container ace-save-state" id="main-container">
            <script type="text/javascript">
                try{ace.settings.loadState('main-container')}catch(e){}
            </script>

            <div id="sidebar" class="sidebar                  responsive                    ace-save-state">
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
                                <a href="#">Data Master</a>
                            </li>
                            <li class="active">User Management</li>
                        </ul><!-- /.breadcrumb -->
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
                                            <i class="ace-icon fa fa-users orange"></i>
                                            User Management System
                                        </h4>
                                        <div class="widget-toolbar">
                                            <a href="#" data-action="collapse">
                                                <i class="ace-icon fa fa-chevron-up"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="widget-body">
                                        <div class="widget-main">

                                            <!-- Add User Button -->
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
                                                        <i class="ace-icon fa fa-plus"></i>
                                                        Tambah User Baru
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="space-6"></div>

                                            <!-- Users Table -->
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="table-responsive">
                                                        <table id="dynamic-table" class="table table-striped table-bordered table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>ID</th>
                                                                    <th>Username</th>
                                                                    <th>Role</th>
                                                                    <th>Department</th>
                                                                    <th>Status</th>
                                                                    <th>Last Login</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                            <?php
                                                                $query = "SELECT u.*, p.nama_posisi, p.kode_posisi AS posisi_kode
                                                                         FROM tbuser u
                                                                         LEFT JOIN tb_master_posisi p ON p.kode_posisi = u.kode_posisi
                                                                         ORDER BY u.id";
                                                                $result = mysqli_query($koneksi, $query);
                                                                while($row = mysqli_fetch_assoc($result)):
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $row['id']; ?></td>
                                                                    <td>
                                                                        <strong><?php echo htmlspecialchars($row['nama_user']); ?></strong>
                                                                        <?php if(in_array($row['kode_posisi'], ['MK', 'KM'], true)): ?>
                                                                        <br><small class="text-muted">
                                                                            <i class="fa fa-wrench"></i> Workshop Staff
                                                                        </small>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <span class="label label-<?php
                                                                            echo $row['kode_posisi'] === 'ADM' ? 'danger' :
                                                                                ($row['kode_posisi'] === 'KM' ? 'warning' :
                                                                                ($row['kode_posisi'] === 'MK' ? 'info' : 'default'));
                                                                        ?>">
                                                                            <?php echo htmlspecialchars($row['nama_posisi'] ?: ($row['role_name'] ?: 'Unknown')); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($row['department'] ?? '-'); ?></td>
                                                                    <td>
                                                                        <span class="label label-<?php echo ($row['is_active'] ?? 'active') == 'active' ? 'success' : 'danger'; ?>">
                                                                            <?php echo ucfirst($row['is_active'] ?? 'active'); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if($row['last_login']) {
                                                                            echo date('d/m/Y H:i', strtotime($row['last_login']));
                                                                        } else {
                                                                            echo '<span class="text-muted">Never</span>';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td class="center">
                                                                        <div class="btn-group">
                                                                            <button class="btn btn-xs btn-info" onclick="editUser(<?php echo $row['id']; ?>)" title="Edit User">
                                                                                <i class="ace-icon fa fa-pencil"></i>
                                                                            </button>
                                                                            <button class="btn btn-xs btn-warning" onclick="changePassword(<?php echo $row['id']; ?>)" title="Change Password">
                                                                                <i class="ace-icon fa fa-key"></i>
                                                                            </button>
                                                                            <?php if($row['id'] != $id_user): // Can't delete current user ?>
                                                                            <a href="?delete_id=<?php echo $row['id']; ?>"
                                                                               class="btn btn-xs btn-danger"
                                                                               onclick="return confirm('Are you sure you want to delete this user?')"
                                                                               title="Delete User">
                                                                                <i class="ace-icon fa fa-trash"></i>
                                                                            </a>
                                                                            <?php endif; ?>
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
                    </div><!-- /.page-content -->
                </div>
            </div><!-- /.main-content -->

            <!-- Add User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">
                                    <i class="ace-icon fa fa-user-plus"></i>
                                    Tambah User Baru
                                </h4>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama_user" required>
                                </div>
                                <div class="form-group">
                                    <label>Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="form-group">
                                    <label>Role <span class="text-danger">*</span></label>
                                    <select class="form-control" name="kode_posisi" id="user_akses_add" required onchange="updateRoleInfo('add')"><?php echo $renderRoleOptions(); ?></select>
                                </div>
                                <div class="form-group">
                                    <label>Role Name</label>
                                    <input type="text" class="form-control" name="role_name" id="role_name_add" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Department</label>
                                    <input type="text" class="form-control" name="department" id="department_add">
                                </div>
                                <div class="form-group" id="mekanik_section_add" style="display: none;">
                                    <label>Link dengan Mekanik <small class="text-muted">(Khusus role Workshop)</small></label>
                                    <select class="form-control" name="mekanik_code">
                                        <option value="">- Pilih Mekanik (Opsional) -</option>
                                        <?php
                                        $mekanik_query = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE status='aktif' ORDER BY nama");
                                        while($mekanik = mysqli_fetch_assoc($mekanik_query)):
                                        ?>
                                        <option value="<?php echo $mekanik['nomekanik']; ?>">
                                            <?php echo $mekanik['nama']; ?> (<?php echo $mekanik['nomekanik']; ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="is_active">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    <strong>Info:</strong>
                                    Anda dapat menambah user untuk semua role: Administrator, CS & Kasir, Mekanik, Kepala Mekanik, Pengadaan, CRM, Manajemen, Keuangan, dan HRD.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <button type="submit" name="btn_add_user" class="btn btn-primary">
                                    <i class="ace-icon fa fa-save"></i>
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">
                                    <i class="ace-icon fa fa-edit"></i>
                                    Edit User
                                </h4>
                            </div>
                            <div class="modal-body" id="editUserContent">
                                <!-- Content will be loaded via AJAX -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <button type="submit" name="btn_update_user" class="btn btn-primary">
                                    <i class="ace-icon fa fa-save"></i>
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password Modal -->
            <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">
                                    <i class="ace-icon fa fa-key"></i>
                                    Change Password
                                </h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="user_id" id="change_password_user_id">
                                <div class="form-group">
                                    <label>New Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="new_password" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <button type="submit" name="btn_change_password" class="btn btn-warning">
                                    <i class="ace-icon fa fa-key"></i>
                                    Change Password
                                </button>
                            </div>
                        </form>
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
        </div><!-- /.main-container -->

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
        <script src="assets/js/dataTables.select.min.js"></script>

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
                    order: [[ 0, "desc" ]]
                });
            });

            // Update role information based on selection
            function updateRoleInfo(mode) {
                var selectElement = document.getElementById('user_akses_' + mode);
                var roleNameElement = document.getElementById('role_name_' + mode);
                var departmentElement = document.getElementById('department_' + mode);
                var mekanikSection = document.getElementById('mekanik_section_' + mode);

                var selectedValue = selectElement.value;
                var roleData = <?php echo json_encode($role_lookup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

                if(roleData[selectedValue]) {
                    roleNameElement.value = roleData[selectedValue].name;
                    departmentElement.value = roleData[selectedValue].dept;

                    if(roleData[selectedValue].is_workshop) {
                        mekanikSection.style.display = 'block';
                    } else {
                        mekanikSection.style.display = 'none';
                    }
                } else {
                    roleNameElement.value = '';
                    departmentElement.value = '';
                    mekanikSection.style.display = 'none';
                }
            }

            // Edit user function
            function editUser(userId) {
                $.ajax({
                    url: 'user_management_ajax.php',
                    type: 'POST',
                    data: {action: 'get_user', user_id: userId},
                    success: function(response) {
                        $('#editUserContent').html(response);
                        $('#editUserModal').modal('show');
                    },
                    error: function() {
                        alert('Error loading user data');
                    }
                });
            }

            // Change password function
            function changePassword(userId) {
                $('#change_password_user_id').val(userId);
                $('#changePasswordModal').modal('show');
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        </script>
    </body>
</html>
