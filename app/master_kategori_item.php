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

    // Cek hak akses - sementara semua user bisa akses full
    $is_admin_pengadaan = true; // ($lvl_akses == 'admin' || $lvl_akses == 'pengadaan');
    $is_read_only = false; // !$is_admin_pengadaan;

    // Handle search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_condition = '';
    if (!empty($search)) {
        $search_condition = "WHERE (kategori_item LIKE '%$search%' OR keterangan LIKE '%$search%') AND status = '1'";
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

        <meta name="description" content="Master Kategori Item" />
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
                                <a href="#">Master Item</a>
                            </li>
                            <li class="active">Kategori Item</li>
                        </ul>
                    </div>

                    <div class="page-content">
                        <div class="row">
                            <div class="col-xs-12">
                                <!-- SEARCH BOX -->
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-search"></i>
                                            Pencarian Kategori Item
                                        </h4>
                                        <?php if ($is_read_only): ?>
                                        <div class="widget-toolbar">
                                            <small style="color: #f39c12; font-weight: bold;">
                                                <i class="fa fa-info-circle"></i> Read-Only Access
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
                                                                   placeholder="Cari kategori item atau keterangan..."
                                                                   value="<?php echo htmlspecialchars($search); ?>" />
                                                            <span class="input-group-btn">
                                                                <button class="btn btn-primary" type="submit">
                                                                    <i class="ace-icon fa fa-search"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    </form>
                                                    <span class="help-block">Sistem akan mencari yang memuat kata tersebut</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Menu -->
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-cogs"></i>
                                            Action Menu - Master Kategori Item
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <?php if (!$is_read_only): ?>
                                                    <a href="master_kategori_item_add_simple.php" class="btn btn-primary btn-sm">
                                                        <i class="ace-icon fa fa-plus bigger-110"></i>
                                                        Tambah Kategori Item
                                                    </a>
                                                    <?php endif; ?>

                                                    <a href="index.php" class="btn btn-warning btn-sm">
                                                        <i class="ace-icon fa fa-home bigger-110"></i>
                                                        Kembali ke Menu Utama
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-header">
                                    Daftar Kategori Item
                                </div>

                                <div>
                                    <table id="dynamic-table" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="center" width="5%">No</th>
                                                <th width="20%">Kategori Item</th>
                                                <th width="30%">KETERANGAN</th>
                                                <th class="center" width="15%">Margin Sesuai Jenis?</th>
                                                <th class="center" width="10%">Margin Kategori</th>
                                                <th class="center" width="10%">Status</th>
                                                <?php if (!$is_read_only): ?>
                                                <th class="center" width="10%">Aksi</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $no = 0;
                                            $sql = mysqli_query($koneksi,"SELECT * FROM tbmaster_kategori_item $search_condition ORDER BY kategori_item ASC");
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                $no++;
                                                $status = $tampil['status'] ?? '1';
                                                $status_text = ($status == '1') ? 'Aktif' : 'Nonaktif';
                                                $status_class = ($status == '1') ? 'label-success' : 'label-warning';
                                                $row_class = ($status == '0') ? 'style="opacity: 0.6;"' : '';
                                        ?>
                                        <tr <?php echo $row_class; ?>>
                                            <td class="center"><?php echo $no ?></td>
                                            <td><strong><?php echo htmlspecialchars($tampil['kategori_item']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($tampil['keterangan']); ?></td>
                                            <td class="center">
                                                <?php if ($tampil['margin_sesuai_jenis'] == 'YA'): ?>
                                                    <span class="label label-success">YA</span>
                                                <?php else: ?>
                                                    <span class="label label-warning">TIDAK</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="center">
                                                <?php if ($tampil['margin_sesuai_jenis'] == 'TIDAK' && $tampil['margin_kategori']): ?>
                                                    <strong><?php echo number_format($tampil['margin_kategori'], 0); ?>%</strong>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="center">
                                                <span class="label <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <?php if (!$is_read_only): ?>
                                            <td class="center">
                                                <a class="green" data-rel="tooltip" title="Edit"
                                                   href="master_kategori_item_edit_simple.php?id=<?php echo $tampil['id']?>">
                                                    <i class="ace-icon fa fa-pencil bigger-130"></i>
                                                </a>&nbsp;
                                                <?php if ($status == '1') { ?>
                                                <a class="red" data-rel="tooltip" title="Hapus"
                                                   href="master_kategori_item_del_simple.php?id=<?php echo $tampil['id']?>"
                                                   onclick="return confirm('Yakin ingin menghapus kategori item ini?\\n\\nKategori item bisa dihapus jika belum ada transaksi.')">
                                                    <i class="ace-icon fa fa-trash-o bigger-130"></i>
                                                </a>
                                                <?php } ?>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php } ?>
                                        <?php if (mysqli_num_rows($sql) == 0): ?>
                                        <tr>
                                            <td colspan="<?php echo $is_read_only ? '6' : '7'; ?>" class="center">
                                                <em>
                                                    <?php if (!empty($search)): ?>
                                                        Tidak ada data kategori item yang sesuai dengan pencarian "<?php echo htmlspecialchars($search); ?>"
                                                    <?php else: ?>
                                                        Belum ada data kategori item
                                                    <?php endif; ?>
                                                </em>
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
                        {"bSortable": false},
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