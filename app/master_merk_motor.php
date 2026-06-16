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

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_cabang, tipe_cabang
                                    FROM tbcabang
                                    WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];
    $tipe_cabang=$tm_cari['tipe_cabang'];
    // --------------------

    $tgl_skr=date('d');
    $bulan_skr=date('m');
    $thn_skr=date('Y');

    // Hilangkan pembatasan hak akses - fokus pada CRUD functionality
    $is_admin_pengadaan = true; // Semua user bisa akses CRUD
    $is_read_only = false; // Tidak ada mode read-only

    // Handle search - sesuai Excel requirement
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_condition = '';
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($koneksi, $search);
        $search_condition = "WHERE (merek LIKE '%$search_escaped%' OR kode_brand LIKE '%$search_escaped%') AND status = '1'";
    } else {
        $search_condition = "WHERE status = '1'";
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="Master Merk Motor" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->
        <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

        <!-- text fonts -->
        <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

        <!-- ace styles -->
        <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
        <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <!-- ace settings handler -->
        <script src="assets/js/ace-extra.min.js"></script>

        <style>
            .action-buttons .btn {
                margin-right: 3px;
                margin-bottom: 2px;
            }
            .action-buttons .btn:last-child {
                margin-right: 0;
            }
            .widget-box .widget-main {
                padding: 15px;
            }
            .table-header {
                background: linear-gradient(135deg, #2d5aa0 0%, #3d71b8 100%);
                border-radius: 4px 4px 0 0;
            }
            .btn-minier {
                font-size: 11px;
                padding: 2px 6px;
            }
            @media (max-width: 768px) {
                .action-buttons .btn {
                    display: block;
                    width: 100%;
                    margin-bottom: 3px;
                }
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
                                <a href="#">Data Master</a>
                            </li>
                            <li>
                                <a href="#">Master Motor</a>
                            </li>
                            <li class="active">Merk Motor</li>
                        </ul>
                    </div>

                    <div class="page-content">
                        <div class="row">
                            <div class="col-xs-12">
                                <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success">
                                    <button type="button" class="close" data-dismiss="alert">
                                        <i class="ace-icon fa fa-times"></i>
                                    </button>
                                    <strong><i class="ace-icon fa fa-check"></i> Berhasil!</strong>
                                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                    <br />
                                </div>
                                <?php endif; ?>

                                <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger">
                                    <button type="button" class="close" data-dismiss="alert">
                                        <i class="ace-icon fa fa-times"></i>
                                    </button>
                                    <strong><i class="ace-icon fa fa-times"></i> Error!</strong>
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <br />
                                </div>
                                <?php endif; ?>
                                <!-- SEARCH BOX -->
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-search"></i>
                                            Pencarian Merk Motor
                                        </h4>
                                        <?php if ($is_read_only): ?>
                                        <div class="widget-toolbar">
                                            <small style="color: #f39c12; font-weight: bold;">
                                                <i class="fa fa-info-circle"></i> CS - Read Only Access
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <form method="GET" action="">
                                                        <div class="input-group">
                                                            <input type="text" name="search" class="form-control"
                                                                   placeholder="Cari kode merk atau keterangan..."
                                                                   value="<?php echo htmlspecialchars($search); ?>" />
                                                            <span class="input-group-btn">
                                                                <button class="btn btn-primary" type="submit">
                                                                    <i class="ace-icon fa fa-search"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    </form>
                                                    <span class="help-block">
                                                        <i class="fa fa-info-circle text-blue"></i>
                                                        Sistem akan mencari yang memuat kata tersebut secara otomatis
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-cogs"></i>
                                            Action Menu
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main" style="padding: 15px;">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <!-- Tombol ADD (Tambah) -->
                                                    <a href="master_merk_motor_add.php" class="btn btn-success btn-sm">
                                                        <i class="ace-icon fa fa-plus bigger-110"></i>
                                                        ADD - Tambah Merk Motor Baru
                                                    </a>

                                                    <!-- Tombol Refresh -->
                                                    <a href="master_merk_motor.php" class="btn btn-info btn-sm">
                                                        <i class="ace-icon fa fa-refresh bigger-110"></i>
                                                        Refresh Data
                                                    </a>

                                                    <!-- Tombol Ke Menu Awal -->
                                                    <a href="index.php" class="btn btn-warning btn-sm">
                                                        <i class="ace-icon fa fa-home bigger-110"></i>
                                                        Ke Menu Awal
                                                    </a>

                                                    <!-- Info CRUD Mode -->
                                                    <span class="pull-right" style="margin-top: 5px;">
                                                        <small class="text-success">
                                                            <i class="fa fa-cogs"></i>
                                                            <strong>CRUD Mode:</strong> Full Access (Create, Read, Update, Delete)
                                                        </small>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-header">
                                    <font color="white">
                                        <i class="fa fa-list"></i>
                                        Daftar Master Merk Motor
                                        <?php if (!empty($search)): ?>
                                        - Hasil Pencarian: "<?php echo htmlspecialchars($search); ?>"
                                        <?php endif; ?>
                                    </font>
                                </div>
                                <div>
                                    <table id="dynamic-table" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="center" width="5%">No</th>
                                                <th width="15%">Kode Merk</th>
                                                <th width="40%">KETERANGAN</th>
                                                <th class="center" width="15%">Status</th>
                                                <th class="center" width="25%">Aksi CRUD</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $no = 0;
                                            $sql = mysqli_query($koneksi,"SELECT * FROM tbpabrik_motor $search_condition ORDER BY kode_brand ASC, merek ASC");
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                $no++;
                                                $status = $tampil['status'] ?? '1';
                                                $status_text = ($status == '1') ? 'Aktif' : 'Nonaktif';
                                                $status_class = ($status == '1') ? 'label-success' : 'label-warning';
                                                $row_class = ($status == '0') ? 'style="opacity: 0.6;"' : '';
                                        ?>
                                        <tr <?php echo $row_class; ?>>
                                            <td class="center"><?php echo $no ?></td>
                                            <td><strong><?php echo htmlspecialchars($tampil['kode_brand']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($tampil['merek']); ?></td>
                                            <td class="center">
                                                <span class="label <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <div class="action-buttons">
                                                    <!-- EDIT Button -->
                                                    <a href="master_merk_motor_edit.php?id=<?php echo $tampil['id']?>"
                                                       class="btn btn-minier btn-success"
                                                       data-rel="tooltip"
                                                       title="EDIT - Update data merk motor <?php echo htmlspecialchars($tampil['kode_brand']); ?>">
                                                        <i class="ace-icon fa fa-pencil"></i>
                                                        EDIT
                                                    </a>

                                                    <?php if ($status == '1') { ?>
                                                    <!-- DELETE Button -->
                                                    <a href="master_merk_motor_del.php?id=<?php echo $tampil['id']?>"
                                                       class="btn btn-minier btn-danger"
                                                       data-rel="tooltip"
                                                       title="DELETE - Hapus data merk motor <?php echo htmlspecialchars($tampil['kode_brand']); ?>"
                                                       onclick="return confirm('⚠️ DELETE CONFIRMATION ⚠️\\n\\nYakin ingin MENGHAPUS merk motor:\\n\\nKode: <?php echo $tampil['kode_brand']; ?>\\nNama: <?php echo $tampil['merek']; ?>\\n\\n❗ Data akan dihapus permanent!\\n\\nLanjutkan DELETE?')">
                                                        <i class="ace-icon fa fa-trash-o"></i>
                                                        DELETE
                                                    </a>
                                                    <?php } else { ?>
                                                    <!-- Data sudah dihapus -->
                                                    <span class="btn btn-minier btn-default disabled" title="Data sudah dihapus">
                                                        <i class="ace-icon fa fa-ban"></i>
                                                        DELETED
                                                    </span>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        <?php if (mysqli_num_rows($sql) == 0): ?>
                                        <tr>
                                            <td colspan="5" class="center" style="padding: 40px;">
                                                <div style="color: #999;">
                                                    <i class="fa fa-info-circle fa-3x" style="margin-bottom: 15px;"></i><br>
                                                    <?php if (!empty($search)): ?>
                                                        <strong style="font-size: 16px;">Tidak ada data merk motor yang sesuai</strong><br>
                                                        <em style="color: #666;">Pencarian: "<?php echo htmlspecialchars($search); ?>"</em><br><br>
                                                        <small>💡 Coba gunakan kata kunci yang berbeda atau
                                                        <a href="master_merk_motor.php" class="btn btn-link btn-sm">lihat semua data</a></small>
                                                    <?php else: ?>
                                                        <strong style="font-size: 16px;">Belum ada data merk motor</strong><br>
                                                        <small style="color: #666;">Database masih kosong</small><br><br>
                                                        <a href="master_merk_motor_add.php" class="btn btn-success btn-sm">
                                                            <i class="fa fa-plus"></i>
                                                            ADD - Tambah Merk Motor Pertama
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
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

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- page specific plugin scripts -->
        <script src="assets/js/jquery.dataTables.min.js"></script>
        <script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <script type="text/javascript">
            jQuery(function($) {
                $('#dynamic-table').dataTable({
                    bAutoWidth: false,
                    "aoColumns": [
                        {"bSortable": false},
                        null,
                        null,
                        {"bSortable": false},
                        <?php if (!$is_read_only): ?>{"bSortable": false}<?php endif; ?>
                    ],
                    "aaSorting": [],
                    "pageLength": 25,
                    "language": {
                        "search": "Cari:",
                        "lengthMenu": "Tampilkan _MENU_ data per halaman",
                        "zeroRecords": "Data tidak ditemukan",
                        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        "infoEmpty": "Tidak ada data",
                        "infoFiltered": "(disaring dari _MAX_ total data)",
                        "paginate": {
                            "previous": "Sebelumnya",
                            "next": "Selanjutnya"
                        }
                    }
                });

                // Auto focus pada search box jika kosong
                <?php if (empty($search)): ?>
                $('input[name="search"]').focus();
                <?php endif; ?>
            });
        </script>
    </body>
</html>

<?php } ?>