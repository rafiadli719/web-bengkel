<?php
// File: master-posisi.php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

include "../config/koneksi.php";
require_once "_include_menu_rbac.php";

if (!canAccessPage($koneksi, $id_user, 'master_posisi_read')) {
    header("location:403.php?permission=master_posisi_read");
    exit;
}

$cari_kd  = mysqli_query($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari  = mysqli_fetch_array($cari_kd);
$_nama    = $tm_cari['nama_user'];
$foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

$form_title        = "Tambah Posisi Baru";
$form_button_label = "Simpan";
$edit_id           = "";
$form_values = [
    'kode_posisi'      => '',
    'nama_posisi'      => '',
    'departemen'       => '',
    'deskripsi'        => '',
    'user_akses_level' => '',
    'is_active'        => 'active',
    'permissions'      => []
];

// Load menu items for permission tree
$menu_items = include 'menu_config.php';

// Helper function to render menu items recursively
function render_permission_item($item, $role_perms, $level = 0) {
    $padding = $level * 20;
    $html = '';
    
    // If item has a permission, render checkbox
    if (isset($item['permission'])) {
        $code = $item['permission'];
        $checked = in_array($code, $role_perms, true) ? 'checked' : '';
        $name = htmlspecialchars($item['title']);
        $code_safe = htmlspecialchars($code);
        
        $html .= "<div style='padding-left: {$padding}px; margin-bottom: 5px;'>";
        $html .= "<label><input type='checkbox' name='perm[]' value='{$code_safe}' {$checked} /> <strong>{$name}</strong> <span class='text-muted' style='font-size:0.8em'>({$code_safe})</span></label>";
        $html .= "</div>";
    } else {
        // If no permission but has title (group header), show title
        if (isset($item['title'])) {
            $name = htmlspecialchars($item['title']);
            $html .= "<div style='padding-left: {$padding}px; margin-bottom: 5px; margin-top: 10px;'>";
            $html .= "<span class='text-primary'><strong>{$name}</strong></span>";
            $html .= "</div>";
        }
    }

    // Render submenu if exists
    if (isset($item['submenu']) && is_array($item['submenu'])) {
        foreach ($item['submenu'] as $sub) {
            $html .= render_permission_item($sub, $role_perms, $level + 1);
        }
    }
    
    return $html;
}

// Helper untuk sanitasi input
function sanitize_input($conn, $value) {
    return mysqli_real_escape_string($conn, trim($value));
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $toggle_id = intval($_GET['toggle']);
    mysqli_query($koneksi, "UPDATE tb_master_posisi 
                            SET is_active = IF(is_active='active','inactive','active'),
                                updated_at = NOW()
                            WHERE id = '$toggle_id'");
    echo "<script>window.location='master-posisi.php';</script>";
    exit;
}

// Handle delete
if (isset($_GET['del'])) {
    $delete_id = intval($_GET['del']);
    if (mysqli_query($koneksi, "DELETE FROM tb_master_posisi WHERE id = '$delete_id'")) {
        echo "<script>alert('Posisi berhasil dihapus.'); window.location='master-posisi.php';</script>";
    } else {
        $error = mysqli_error($koneksi);
        $msg = addslashes("Gagal menghapus posisi. Pastikan tidak sedang digunakan.\nDetail: " . $error);
        echo "<script>alert('$msg'); window.location='master-posisi.php';</script>";
    }
    exit;
}

// Load data untuk edit
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $query   = mysqli_query($koneksi, "SELECT * FROM tb_master_posisi WHERE id = '$edit_id'");
    if ($data_edit = mysqli_fetch_assoc($query)) {
        $form_title        = "Edit Posisi: " . htmlspecialchars($data_edit['kode_posisi']);
        $form_button_label = "Update";
        $form_values = [
            'kode_posisi'      => $data_edit['kode_posisi'],
            'nama_posisi'      => $data_edit['nama_posisi'],
            'departemen'       => $data_edit['departemen'],
            'deskripsi'        => $data_edit['deskripsi'],
            'user_akses_level' => $data_edit['user_akses_level'],
            'is_active'        => $data_edit['is_active'],
            'permissions'      => json_decode($data_edit['permissions'] ?: '[]', true)
        ];
    } else {
        echo "<script>alert('Data tidak ditemukan.'); window.location='master-posisi.php';</script>";
        exit;
    }
}

// Handle submit form
if (isset($_POST['btnsimpan'])) {
    $post_id            = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $kode_posisi        = strtoupper(sanitize_input($koneksi, $_POST['kode_posisi'] ?? ''));
    $nama_posisi        = sanitize_input($koneksi, $_POST['nama_posisi'] ?? '');
    $departemen         = sanitize_input($koneksi, $_POST['departemen'] ?? '');
    $deskripsi          = sanitize_input($koneksi, $_POST['deskripsi'] ?? '');
    $user_akses_level   = isset($_POST['user_akses_level']) ? (int)$_POST['user_akses_level'] : null;
    $is_active          = ($_POST['is_active'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    
    // Process permissions
    $perms = isset($_POST['perm']) && is_array($_POST['perm']) ? array_values(array_unique(array_map('strval', $_POST['perm']))) : [];
    $permissions_json = mysqli_real_escape_string($koneksi, json_encode($perms));

    if ($kode_posisi === '' || $nama_posisi === '' || $departemen === '' || $user_akses_level === null) {
        echo "<script>alert('Mohon lengkapi data wajib (kode, nama, departemen, level akses).'); window.history.back();</script>";
        exit;
    }

    if ($user_akses_level < 1) {
        $user_akses_level = 1;
    }

    if ($post_id > 0) {
        // Update
        $cek_kode = mysqli_query($koneksi, "SELECT id FROM tb_master_posisi WHERE kode_posisi='$kode_posisi' AND id <> '$post_id'");
        if (mysqli_num_rows($cek_kode) > 0) {
            echo "<script>alert('Kode posisi sudah digunakan.'); window.history.back();</script>";
            exit;
        }

        $sql_update = "UPDATE tb_master_posisi SET
                        nama_posisi = '$nama_posisi',
                        departemen = '$departemen',
                        deskripsi = '$deskripsi',
                        user_akses_level = '$user_akses_level',
                        permissions = '$permissions_json',
                        is_active = '$is_active',
                        updated_at = NOW()
                       WHERE id = '$post_id'";
        if (mysqli_query($koneksi, $sql_update)) {
            echo "<script>alert('Data posisi berhasil diperbarui.'); window.location='master-posisi.php';</script>";
            exit;
        } else {
            $error = addslashes(mysqli_error($koneksi));
            echo "<script>alert('Gagal memperbarui data. Detail: $error'); window.history.back();</script>";
            exit;
        }
    } else {
        // Insert
        $cek_kode = mysqli_query($koneksi, "SELECT id FROM tb_master_posisi WHERE kode_posisi='$kode_posisi'");
        if (mysqli_num_rows($cek_kode) > 0) {
            echo "<script>alert('Kode posisi sudah terdaftar.'); window.history.back();</script>";
            exit;
        }

        $sql_insert = "INSERT INTO tb_master_posisi
                       (kode_posisi, nama_posisi, departemen, deskripsi, user_akses_level, permissions, is_active, created_at, updated_at)
                       VALUES
                       ('$kode_posisi', '$nama_posisi', '$departemen', '$deskripsi', '$user_akses_level', '$permissions_json', '$is_active', NOW(), NOW())";
        if (mysqli_query($koneksi, $sql_insert)) {
            echo "<script>alert('Data posisi baru berhasil disimpan.'); window.location='master-posisi.php';</script>";
            exit;
        } else {
            $error = addslashes(mysqli_error($koneksi));
            echo "<script>alert('Gagal menyimpan data. Detail: $error'); window.history.back();</script>";
            exit;
        }
    }
}

// Ambil data untuk tabel
$sql_posisi = mysqli_query(
    $koneksi,
    "SELECT p.*, COUNT(l.id) AS total_level
     FROM tb_master_posisi p
     LEFT JOIN tb_master_level l ON l.kode_posisi = p.kode_posisi
     GROUP BY p.id
     ORDER BY p.nama_posisi"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Master Posisi - Bengkel System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />

    <style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    .status-active {
        background-color: #5cb85c;
        color: #fff;
    }
    .status-inactive {
        background-color: #d9534f;
        color: #fff;
    }
    .form-note {
        font-size: 11px;
        color: #888;
    }
    </style>
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
                        <?php include "../lib/logo.php"; ?>
                        <?php include "../lib/subtitel.php"; ?>
                                </small>							
                            </a>								
                        </td>
                        <td>
                        </td>							
                    </tr>
                </table>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo htmlspecialchars($foto_user); ?>" alt="User Profile" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo htmlspecialchars($_nama); ?>
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
                        <li class="active">Master Posisi</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-briefcase"></i> <?php echo htmlspecialchars($form_title); ?></h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="post" role="form">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_id); ?>">

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Kode Posisi *</label>
                                                        <input type="text" class="form-control" name="kode_posisi"
                                                               value="<?php echo htmlspecialchars($form_values['kode_posisi']); ?>"
                                                               placeholder="Misal: MK, KM, ADM"
                                                               <?php echo $edit_id ? 'readonly' : ''; ?> required>
                                                        <p class="form-note">Gunakan huruf kapital, maksimal 20 karakter.</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Nama Posisi *</label>
                                                        <input type="text" class="form-control" name="nama_posisi"
                                                               value="<?php echo htmlspecialchars($form_values['nama_posisi']); ?>"
                                                               placeholder="Contoh: Mekanik" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Departemen *</label>
                                                        <input type="text" class="form-control" name="departemen"
                                                               value="<?php echo htmlspecialchars($form_values['departemen']); ?>"
                                                               placeholder="Contoh: Workshop" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Level Akses Default *</label>
                                                        <input type="number" class="form-control" name="user_akses_level"
                                                               value="<?php echo htmlspecialchars($form_values['user_akses_level']); ?>"
                                                               min="1" max="99" required>
                                                        <p class="form-note">Sesuaikan dengan level akses di sistem (1-99).</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <select class="form-control" name="is_active">
                                                            <option value="active" <?php echo $form_values['is_active'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                            <option value="inactive" <?php echo $form_values['is_active'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Deskripsi</label>
                                                        <textarea class="form-control" name="deskripsi" rows="1"
                                                                  placeholder="Opsional"><?php echo htmlspecialchars($form_values['deskripsi']); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <div class="widget-box transparent">
                                                        <div class="widget-header widget-header-flat">
                                                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-sitemap orange"></i> Akses Sidebar Menu</h4>
                                                        </div>
                                                        <div class="widget-body">
                                                            <div class="widget-main padding-8">
                                                                <?php 
                                                                $role_perms = $form_values['permissions'];
                                                                foreach($menu_items as $top_item): 
                                                                ?>
                                                                <div class="panel panel-default">
                                                                    <div class="panel-heading">
                                                                        <h4 class="panel-title">
                                                                            <a data-toggle="collapse" href="#collapse-<?php echo md5($top_item['title']); ?>">
                                                                                <?php echo htmlspecialchars($top_item['title']); ?>
                                                                            </a>
                                                                        </h4>
                                                                    </div>
                                                                    <div id="collapse-<?php echo md5($top_item['title']); ?>" class="panel-collapse collapse in">
                                                                        <div class="panel-body">
                                                                            <?php echo render_permission_item($top_item, $role_perms, 0); ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <button type="submit" name="btnsimpan" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-save"></i> <?php echo htmlspecialchars($form_button_label); ?>
                                                </button>
                                                <?php if ($edit_id): ?>
                                                <a href="master-posisi.php" class="btn btn-sm btn-default">
                                                    <i class="fa fa-refresh"></i> Batal Edit
                                                </a>
                                                <?php else: ?>
                                                <button type="reset" class="btn btn-sm btn-default">
                                                    <i class="fa fa-undo"></i> Reset
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-table"></i> Daftar Posisi</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th style="width:5%">No</th>
                                                        <th style="width:10%">Kode</th>
                                                        <th style="width:20%">Nama Posisi</th>
                                                        <th style="width:15%">Departemen</th>
                                                        <th style="width:10%">Level Akses</th>
                                                        <th style="width:10%">Jumlah Level</th>
                                                        <th style="width:10%">Status</th>
                                                        <th style="width:10%">Dibuat</th>
                                                        <th style="width:10%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $no = 1;
                                                    while ($row = mysqli_fetch_assoc($sql_posisi)) {
                                                        $status_class = $row['is_active'] === 'active' ? 'status-active' : 'status-inactive';
                                                        $status_label = $row['is_active'] === 'active' ? 'Aktif' : 'Nonaktif';
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no++; ?></td>
                                                        <td><?php echo htmlspecialchars($row['kode_posisi']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['nama_posisi']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['departemen']); ?></td>
                                                        <td class="center"><?php echo htmlspecialchars($row['user_akses_level']); ?></td>
                                                        <td class="center">
                                                            <span class="badge badge-info"><?php echo (int)$row['total_level']; ?></span>
                                                        </td>
                                                        <td class="center">
                                                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))); ?></td>
                                                        <td class="center">
                                                            <div class="btn-group">
                                                                <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-xs btn-success" title="Toggle Status">
                                                                    <i class="fa fa-toggle-on"></i>
                                                                </a>
                                                                <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-xs btn-info" title="Edit">
                                                                    <i class="fa fa-edit"></i>
                                                                </a>
                                                                <a href="?del=<?php echo $row['id']; ?>" class="btn btn-xs btn-danger" title="Hapus"
                                                                   onclick="return confirm('Yakin hapus posisi ini?')">
                                                                    <i class="fa fa-trash"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                    <?php if ($no === 1): ?>
                                                    <tr>
                                                        <td colspan="9" class="center">Belum ada data posisi.</td>
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
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script src="assets/js/ace-extra.min.js"></script>
    
    <script type="text/javascript">
        try{ace.settings.loadState('main-container')}catch(e){}
        try{ace.settings.loadState('sidebar')}catch(e){}
    </script>
    <script type="text/javascript">
    $(function(){
        if ($('#perm_check_all').length === 0) {
            var control = $('<div class="clearfix" style="margin-bottom:8px;"><label><input type="checkbox" id="perm_check_all"> Checklist Semua</label></div>');
            var $permContainer = $(".widget-title:contains('Akses Sidebar Menu')").closest('.widget-box').find('.widget-main.padding-8').first();
            if ($permContainer.length) {
                $permContainer.prepend(control);
            }
        }
        $(document).on('change', '#perm_check_all', function(){
            var checked = this.checked;
            $("input[name='perm[]']").prop('checked', checked);
        });
    });
    </script>
</body>
</html>

