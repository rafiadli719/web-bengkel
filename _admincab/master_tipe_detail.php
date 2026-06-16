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
    // Semua user bisa akses CRUD operations
    $is_admin_pengadaan = true;
    $is_read_only = false;

    // Handle search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_condition = '';
    if (!empty($search)) {
        $search_condition = "AND (td.kode_tipe LIKE '%$search%' OR td.nama_detail LIKE '%$search%' OR
                                th.nama_model LIKE '%$search%' OR pb.merek LIKE '%$search%' OR
                                td.fitur_pembeda LIKE '%$search%' OR td.no_seri_mesin LIKE '%$search%')";
    }

    // Get data tipe detail with joins
    $query = "SELECT td.*,
                     pb.merek as nama_brand, pb.kode_brand,
                     th.nama_model as nama_tipe_header,
                     jm.jenis as nama_jenis_motor,
                     km.kategori as nama_kategori_motor
              FROM tbmaster_tipe_detail td
              LEFT JOIN tbpabrik_motor pb ON td.kode_brand = pb.kode_brand AND pb.status = '1'
              LEFT JOIN tbmaster_tipe_header th ON td.id_tipe_header = th.id AND th.status = '1'
              LEFT JOIN tbjenis_motor jm ON td.id_jenis_motor = jm.kd AND jm.status = '1'
              LEFT JOIN tbkategori_motor km ON td.id_kategori_motor = km.id AND km.status = '1'
              WHERE td.status = '1' $search_condition
              ORDER BY td.kode_brand, th.nama_model, td.nama_detail";

    // Handle success message from other pages
    $success_msg = '';
    if (isset($_SESSION['delete_success'])) {
        $success_msg = $_SESSION['delete_success'];
        unset($_SESSION['delete_success']);
    }
    if (isset($_SESSION['success'])) {
        $success_msg = $_SESSION['success'];
        unset($_SESSION['success']);
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="Master Tipe Detail Motor" />
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
                                <a href="#">Master Motor</a>
                            </li>
                            <li class="active">Tipe Detail</li>
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
                                            Pencarian Tipe Detail Motor
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <form method="GET" action="">
                                                        <div class="input-group">
                                                            <input type="text" name="search" class="form-control"
                                                                   placeholder="Cari kode tipe, nama tipe, brand, fitur, atau seri mesin..."
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
                                            Action Menu - Master Tipe Detail Motor
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <a href="master_tipe_detail_add_simple.php" class="btn btn-primary btn-sm">
                                                        <i class="ace-icon fa fa-plus bigger-110"></i>
                                                        Tambah Tipe Detail Motor
                                                    </a>

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
                                    Daftar Tipe Detail Motor
                                </div>
                                <div>
                                    <table id="dynamic-table" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="center" width="4%">No</th>
                                                <th width="8%">Kode Tipe</th>
                                                <th width="15%">Brand & Tipe Header</th>
                                                <th width="12%">Nama Detail</th>
                                                <th class="center" width="8%">CC & Jenis</th>
                                                <th width="15%">Fitur Pembeda</th>
                                                <th class="center" width="8%">Tahun</th>
                                                <th width="10%">Seri Mesin</th>
                                                <th width="10%">Kategori</th>
                                                <th class="center" width="10%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $no = 0;
                                            $sql = mysqli_query($koneksi, $query);
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                $no++;
                                                $status = $tampil['status'] ?? '1';
                                                $status_text = ($status == '1') ? 'Aktif' : 'Nonaktif';
                                                $status_class = ($status == '1') ? 'label-success' : 'label-warning';
                                                $row_class = ($status == '0') ? 'style="opacity: 0.6;"' : '';
                                        ?>
                                        <tr <?php echo $row_class; ?>>
                                            <td class="center"><?php echo $no ?></td>
                                            <td><strong><?php echo htmlspecialchars($tampil['kode_tipe']); ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($tampil['kode_brand']); ?></strong>
                                                <?php echo htmlspecialchars($tampil['nama_brand'] ?? ''); ?><br>
                                                <span style="color: #333; font-weight: bold;"><?php echo htmlspecialchars($tampil['nama_tipe_header'] ?? ''); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($tampil['nama_detail'] && $tampil['nama_detail'] != '-'): ?>
                                                    <?php echo htmlspecialchars($tampil['nama_detail']); ?>
                                                <?php else: ?>
                                                    <span style="color: #999; font-style: italic;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="center">
                                                <?php if ($tampil['cc']): ?>
                                                    <span class="label label-info" style="font-size: 10px;"><?php echo $tampil['cc']; ?>cc</span><br>
                                                <?php endif; ?>
                                                <?php if ($tampil['nama_jenis_motor']): ?>
                                                    <small><?php echo htmlspecialchars($tampil['nama_jenis_motor']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tampil['fitur_pembeda'] && $tampil['fitur_pembeda'] != '-'): ?>
                                                    <?php echo htmlspecialchars($tampil['fitur_pembeda']); ?>
                                                <?php else: ?>
                                                    <span style="color: #999; font-style: italic;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="center">
                                                <?php if ($tampil['tahun_awal']): ?>
                                                    <span class="label label-success" style="font-size: 10px;"><?php echo $tampil['tahun_awal']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($tampil['tahun_akhir']): ?>
                                                    <br><small>s/d <?php echo htmlspecialchars($tampil['tahun_akhir']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tampil['no_seri_mesin']): ?>
                                                    <code><?php echo htmlspecialchars($tampil['no_seri_mesin']); ?></code>
                                                <?php else: ?>
                                                    <span style="color: #999; font-style: italic;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tampil['nama_kategori_motor']): ?>
                                                    <small><?php echo htmlspecialchars($tampil['nama_kategori_motor']); ?></small>
                                                <?php else: ?>
                                                    <span style="color: #999; font-style: italic;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="center">
                                                <div class="btn-group">
                                                    <a class="btn btn-xs btn-info" data-rel="tooltip" title="Edit Tipe Detail"
                                                       href="master_tipe_detail_edit_simple.php?id=<?php echo $tampil['id']?>">
                                                        <i class="ace-icon fa fa-pencil"></i>
                                                    </a>
                                                    <?php if ($status == '1') { ?>
                                                    <a class="btn btn-xs btn-danger" data-rel="tooltip" title="Hapus Tipe Detail"
                                                       href="master_tipe_detail_del_simple.php?id=<?php echo $tampil['id']?>"
                                                       onclick="return confirm('Yakin ingin menghapus tipe detail motor ini?\\n\\nTipe detail bisa dihapus jika belum ada transaksi.')">
                                                        <i class="ace-icon fa fa-trash-o"></i>
                                                    </a>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        <?php if (mysqli_num_rows($sql) == 0): ?>
                                        <tr>
                                            <td colspan="10" class="center">
                                                <em>
                                                    <?php if (!empty($search)): ?>
                                                        Tidak ada data tipe detail motor yang sesuai dengan pencarian "<?php echo htmlspecialchars($search); ?>"
                                                    <?php else: ?>
                                                        Belum ada data tipe detail motor
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
                        null,
                        {"bSortable": false},
                        {"bSortable": false},
                        {"bSortable": false},
                        {"bSortable": false},
                        {"bSortable": false},
                        {"bSortable": false}
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

                // Show success message if any
                <?php if ($success_msg): ?>
                setTimeout(function() {
                    var alert = $('<div class="alert alert-success alert-dismissible fade in" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button>' +
                        '<i class="fa fa-check"></i> <?php echo addslashes($success_msg); ?></div>');

                    $('.page-content').prepend(alert);

                    setTimeout(function() {
                        alert.alert('close');
                    }, 3000);
                }, 500);
                <?php endif; ?>
            });
        </script>
    </body>
</html>

<?php } ?>