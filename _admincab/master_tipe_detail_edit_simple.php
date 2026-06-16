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
    $is_admin_pengadaan = true; // Semua user bisa akses EDIT
    $is_read_only = false;

    $page_title = "Edit Tipe Detail Motor";
    $error_msg = '';
    $success_msg = '';

    // Get ID and data
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: master_tipe_detail.php');
        exit();
    }

    // Get current data with brand info
    $query = "SELECT td.*,
                     th.nama_model as tipe_header,
                     pm.merek, pm.kode_brand as brand_code
              FROM tbmaster_tipe_detail td
              LEFT JOIN tbmaster_tipe_header th ON td.id_tipe_header = th.id AND th.status = '1'
              LEFT JOIN tbpabrik_motor pm ON th.id_brand = pm.id AND pm.status = '1'
              WHERE td.id = $id AND td.status = '1'";
    $result = mysqli_query($koneksi, $query);
    if (mysqli_num_rows($result) == 0) {
        header('Location: master_tipe_detail.php');
        exit();
    }

    $data = mysqli_fetch_assoc($result);

    // Get dropdown data
    $jenis_query = "SELECT kd, jenis FROM tbjenis_motor WHERE status = '1' ORDER BY jenis ASC";
    $jenis_result = mysqli_query($koneksi, $jenis_query);

    $kategori_query = "SELECT id, kategori FROM tbkategori_motor WHERE status = '1' ORDER BY kategori ASC";
    $kategori_result = mysqli_query($koneksi, $kategori_query);

// Process form submission
if ($_POST) {
    $nama_detail = trim($_POST['nama_detail']);
    if ($nama_detail == '') $nama_detail = '-';
    $cc = !empty($_POST['cc']) ? (int)$_POST['cc'] : null;
    $id_jenis_motor = !empty($_POST['id_jenis_motor']) ? (int)$_POST['id_jenis_motor'] : null;
    $fitur_pembeda = trim($_POST['fitur_pembeda']);
    if ($fitur_pembeda == '') $fitur_pembeda = '-';
    $tahun_awal = !empty($_POST['tahun_awal']) ? (int)$_POST['tahun_awal'] : null;
    $tahun_akhir = trim($_POST['tahun_akhir']);
    if ($tahun_akhir == '') $tahun_akhir = null;
    $no_seri_mesin = trim($_POST['no_seri_mesin']);
    $id_kategori_motor = !empty($_POST['id_kategori_motor']) ? (int)$_POST['id_kategori_motor'] : null;

    // Validasi input
    if (strlen($nama_detail) > 100) {
        $error_msg = "Nama Detail maksimal 100 karakter!";
    } elseif (strlen($fitur_pembeda) > 255) {
        $error_msg = "Fitur Pembeda maksimal 255 karakter!";
    } else {
        // Escape input untuk keamanan
        $nama_detail_escaped = mysqli_real_escape_string($koneksi, $nama_detail);
        $fitur_pembeda_escaped = mysqli_real_escape_string($koneksi, $fitur_pembeda);
        $tahun_akhir_escaped = $tahun_akhir ? mysqli_real_escape_string($koneksi, $tahun_akhir) : null;
        $no_seri_escaped = $no_seri_mesin ? mysqli_real_escape_string($koneksi, $no_seri_mesin) : null;

        // Update data (kode_tipe, kode_brand, id_tipe_header tidak bisa diubah)
        $update_query = "UPDATE tbmaster_tipe_detail SET
                        nama_detail = '$nama_detail_escaped',
                        cc = " . ($cc ? $cc : 'NULL') . ",
                        id_jenis_motor = " . ($id_jenis_motor ? $id_jenis_motor : 'NULL') . ",
                        fitur_pembeda = '$fitur_pembeda_escaped',
                        tahun_awal = " . ($tahun_awal ? $tahun_awal : 'NULL') . ",
                        tahun_akhir = " . ($tahun_akhir_escaped ? "'$tahun_akhir_escaped'" : 'NULL') . ",
                        no_seri_mesin = " . ($no_seri_escaped ? "'$no_seri_escaped'" : 'NULL') . ",
                        id_kategori_motor = " . ($id_kategori_motor ? $id_kategori_motor : 'NULL') . "
                        WHERE id = $id AND status = '1'";

        if (mysqli_query($koneksi, $update_query)) {
            if (mysqli_affected_rows($koneksi) > 0) {
                $_SESSION['success'] = "Data tipe detail '{$data['kode_tipe']}' berhasil diupdate!";
                header('Location: master_tipe_detail.php');
                exit();
            } else {
                $error_msg = "Tidak ada perubahan data atau data tidak ditemukan!";
            }
        } else {
            $error_msg = "Gagal mengupdate data: " . mysqli_error($koneksi);
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

        <meta name="description" content="Edit Tipe Detail Motor" />
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
            .data-awal {
                background-color: #f8f8f8;
                border-left: 4px solid #1f8dd6;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .edit-section {
                background-color: #f0f7ff;
                border-left: 4px solid #5bc0de;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .required {
                color: #d15b47;
            }
            .help-text {
                font-size: 11px;
                color: #999;
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
                            <li>
                                <a href="master_tipe_detail.php">Tipe Detail</a>
                            </li>
                            <li class="active">Edit</li>
                        </ul>
                    </div>

                    <div class="page-content">
                        <div class="row">
                            <div class="col-xs-12">

                                <!-- Alert Messages -->
                                <?php if (!empty($error_msg)): ?>
                                <div class="alert alert-danger">
                                    <button type="button" class="close" data-dismiss="alert">
                                        <i class="ace-icon fa fa-times"></i>
                                    </button>
                                    <strong><i class="ace-icon fa fa-times"></i> Error!</strong>
                                    <?php echo $error_msg; ?>
                                    <br />
                                </div>
                                <?php endif; ?>

                                <div class="col-xs-12 col-sm-10 col-sm-offset-1">
                                    <div class="widget-box">
                                        <div class="widget-header">
                                            <h4 class="widget-title">Edit Tipe Detail Motor</h4>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main no-padding">

                                                <!-- Data Awal -->
                                                <div class="data-awal" style="margin: 20px;">
                                                    <h5><strong><i class="fa fa-info-circle"></i> Data Awal</strong></h5>
                                                    <p class="help-text">Data awal yang tidak dapat diubah. Hanya data pada section "Edit Menjadi" yang bisa dimodifikasi.</p>
                                                    <div class="row" style="margin-top: 15px;">
                                                        <div class="col-sm-3"><strong>Kode Tipe :</strong></div>
                                                        <div class="col-sm-9"><?php echo htmlspecialchars($data['kode_tipe']); ?></div>
                                                    </div>
                                                    <div class="row" style="margin-top: 8px;">
                                                        <div class="col-sm-3"><strong>Brand :</strong></div>
                                                        <div class="col-sm-9"><?php echo htmlspecialchars($data['brand_code'] . ' - ' . $data['merek']); ?></div>
                                                    </div>
                                                    <div class="row" style="margin-top: 8px;">
                                                        <div class="col-sm-3"><strong>Tipe Header :</strong></div>
                                                        <div class="col-sm-9"><?php echo htmlspecialchars($data['tipe_header']); ?></div>
                                                    </div>
                                                </div>

                                                <!-- Edit Section -->
                                                <div class="edit-section" style="margin: 20px;">
                                                    <h5><strong><i class="fa fa-edit"></i> Edit Menjadi</strong></h5>

                                                    <form class="form-horizontal" role="form" method="POST" style="margin-top: 20px;">
                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-1">
                                                                Nama Detail
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <input type="text" id="form-field-1" name="nama_detail"
                                                                       placeholder="Isi '-' jika kosong"
                                                                       class="form-control" maxlength="100"
                                                                       value="<?php echo htmlspecialchars($data['nama_detail']); ?>" />
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Detail spesifik atau kosongkan (maksimal 100 karakter)</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-2">
                                                                CC
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <input type="number" id="form-field-2" name="cc"
                                                                       placeholder="Contoh: 110"
                                                                       class="form-control" min="1" max="9999"
                                                                       value="<?php echo $data['cc']; ?>" />
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Kapasitas mesin dalam cc</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-3">
                                                                Jenis Motor
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <select id="form-field-3" name="id_jenis_motor" class="form-control">
                                                                    <option value="">-- Pilih Jenis --</option>
                                                                    <?php
                                                                    mysqli_data_seek($jenis_result, 0);
                                                                    while ($jenis = mysqli_fetch_assoc($jenis_result)): ?>
                                                                    <option value="<?php echo $jenis['kd']; ?>"
                                                                            <?php echo ($data['id_jenis_motor'] == $jenis['kd']) ? 'selected' : ''; ?>>
                                                                        <?php echo $jenis['jenis']; ?>
                                                                    </option>
                                                                    <?php endwhile; ?>
                                                                </select>
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Jenis sistem bahan bakar (FI, Carbu, dll)</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-4">
                                                                Fitur Pembeda
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <input type="text" id="form-field-4" name="fitur_pembeda"
                                                                       placeholder="Isi '-' jika kosong"
                                                                       class="form-control" maxlength="255"
                                                                       value="<?php echo htmlspecialchars($data['fitur_pembeda']); ?>" />
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Fitur khusus yang membedakan (maksimal 255 karakter)</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-5">
                                                                Tahun Awal
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <input type="number" id="form-field-5" name="tahun_awal"
                                                                       placeholder="Contoh: 2015"
                                                                       class="form-control" min="1900" max="2030"
                                                                       value="<?php echo $data['tahun_awal']; ?>" />
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Tahun mulai produksi</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-6">
                                                                Tahun Akhir
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <input type="text" id="form-field-6" name="tahun_akhir"
                                                                       placeholder="Angka atau SEKARANG"
                                                                       class="form-control" maxlength="10"
                                                                       value="<?php echo htmlspecialchars($data['tahun_akhir']); ?>" />
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Tahun akhir produksi atau "SEKARANG"</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-7">
                                                                Seri Mesin
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <input type="text" id="form-field-7" name="no_seri_mesin"
                                                                       placeholder="Contoh: JF22E"
                                                                       class="form-control" maxlength="20"
                                                                       value="<?php echo htmlspecialchars($data['no_seri_mesin']); ?>" />
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Nomor seri mesin (tanpa spasi)</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right" for="form-field-8">
                                                                Kategori Motor
                                                            </label>
                                                            <div class="col-sm-9">
                                                                <select id="form-field-8" name="id_kategori_motor" class="form-control">
                                                                    <option value="">-- Pilih Kategori --</option>
                                                                    <?php
                                                                    mysqli_data_seek($kategori_result, 0);
                                                                    while ($kategori = mysqli_fetch_assoc($kategori_result)): ?>
                                                                    <option value="<?php echo $kategori['id']; ?>"
                                                                            <?php echo ($data['id_kategori_motor'] == $kategori['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $kategori['kategori']; ?>
                                                                    </option>
                                                                    <?php endwhile; ?>
                                                                </select>
                                                                <span class="help-inline col-xs-12 col-sm-7">
                                                                    <span class="middle">Kategori motor (Matic, Bebek, dll)</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="clearfix form-actions">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button class="btn btn-info" type="submit">
                                                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                                                    Simpan
                                                                </button>

                                                                &nbsp; &nbsp; &nbsp;
                                                                <button class="btn" type="reset">
                                                                    <i class="ace-icon fa fa-undo bigger-110"></i>
                                                                    Reset
                                                                </button>

                                                                &nbsp; &nbsp; &nbsp;
                                                                <a href="master_tipe_detail.php" class="btn btn-success">
                                                                    <i class="ace-icon fa fa-list bigger-110"></i>
                                                                    Lihat Daftar Tipe Detail
                                                                </a>

                                                                &nbsp; &nbsp; &nbsp;
                                                                <a href="index.php" class="btn btn-warning">
                                                                    <i class="ace-icon fa fa-home bigger-110"></i>
                                                                    Ke Menu Awal
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </form>
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

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <script type="text/javascript">
            jQuery(function($) {
                // Auto focus pada nama detail input
                $('#form-field-1').focus();

                // Enter key submit
                $('#form-field-7').on('keypress', function(e) {
                    if (e.which == 13) {
                        $(this).closest('form').submit();
                    }
                });
            });
        </script>
    </body>
</html>

<?php } ?>