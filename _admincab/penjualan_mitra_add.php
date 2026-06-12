<?php
/**
 * Input Manual Penjualan ke Cabang Mitra (Eksternal)
 * Untuk input penjualan ke cabang mitra yang tidak terintegrasi sistem
 */
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
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // Data Cabang
    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_cabang, tipe_cabang
                                    FROM tbcabang
                                    WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];
    $tipe_cabang=$tm_cari['tipe_cabang'];

    // Generate No Transaksi
    include "function_pesanan_penjualan.php";
    $LastID = FormatNoTrans(OtomatisID());

    // Default values
    $tgl_transaksi = date('Y-m-d');
    $cabang_tujuan = '';
    $keterangan = '';
    $items = [];
    $error_msg = '';
    $success_msg = '';

    // Process form submission
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $tgl_transaksi = isset($_POST['tgl_transaksi']) ? $_POST['tgl_transaksi'] : date('Y-m-d');
        $cabang_tujuan = isset($_POST['cabang_tujuan']) ? mysqli_real_escape_string($koneksi, $_POST['cabang_tujuan']) : '';
        $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan']) : '';

        // Get items from POST
        $item_codes = isset($_POST['item_code']) ? $_POST['item_code'] : [];
        $item_qtys = isset($_POST['item_qty']) ? $_POST['item_qty'] : [];
        $item_prices = isset($_POST['item_price']) ? $_POST['item_price'] : [];

        if(isset($_POST['btn_simpan'])){
            // Validate
            if($cabang_tujuan == ''){
                $error_msg = 'Pilih cabang tujuan!';
            } elseif(count($item_codes) == 0 || $item_codes[0] == ''){
                $error_msg = 'Tambahkan minimal 1 item!';
            } else {
                // Start transaction
                mysqli_begin_transaction($koneksi);

                try {
                    // Calculate totals
                    $total_qty = 0;
                    $total_order = 0;

                    for($i = 0; $i < count($item_codes); $i++){
                        if($item_codes[$i] != ''){
                            $qty = floatval($item_qtys[$i]);
                            $price = floatval(str_replace(['.', ','], ['', '.'], $item_prices[$i]));
                            $subtotal = $qty * $price;
                            $total_qty += $qty;
                            $total_order += $subtotal;
                        }
                    }

                    // Insert header
                    $sql_h = "INSERT INTO tblorderjual_header
                              (no_order, tanggal, no_pelanggan, no_sales, total_qty, total_order,
                               status, tipe_transaksi, kd_cabang, kd_cabang_tujuan, keterangan, user_input)
                              VALUES
                              ('$LastID', '$tgl_transaksi', '', '', '$total_qty', '$total_order',
                               '0', 'MITRA_EKSTERNAL', '$kd_cabang', '$cabang_tujuan', '$keterangan', '$_nama')";

                    if(!mysqli_query($koneksi, $sql_h)){
                        throw new Exception("Gagal insert header: " . mysqli_error($koneksi));
                    }

                    // Insert details
                    for($i = 0; $i < count($item_codes); $i++){
                        if($item_codes[$i] != ''){
                            $no_item = mysqli_real_escape_string($koneksi, $item_codes[$i]);
                            $qty = floatval($item_qtys[$i]);
                            $price = floatval(str_replace(['.', ','], ['', '.'], $item_prices[$i]));
                            $subtotal = $qty * $price;

                            $sql_d = "INSERT INTO tblorderjual_detail
                                      (no_order, no_item, quantity, harga_jual, potongan, total)
                                      VALUES
                                      ('$LastID', '$no_item', '$qty', '$price', '0', '$subtotal')";

                            if(!mysqli_query($koneksi, $sql_d)){
                                throw new Exception("Gagal insert detail: " . mysqli_error($koneksi));
                            }
                        }
                    }

                    mysqli_commit($koneksi);
                    $success_msg = "Data berhasil disimpan dengan No. Transaksi: $LastID";

                    // Reset form
                    $LastID = FormatNoTrans(OtomatisID());
                    $cabang_tujuan = '';
                    $keterangan = '';
                    $items = [];

                } catch(Exception $e){
                    mysqli_rollback($koneksi);
                    $error_msg = $e->getMessage();
                }
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

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .item-row { margin-bottom: 10px; }
        .remove-item { color: #d9534f; cursor: pointer; }
        .remove-item:hover { color: #c9302c; }
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
                <a href="index.php" class="navbar-brand">
                    <small>
                        <i class="fa fa-leaf"></i>
                        <?php include "../lib/subtitel.php"; ?>
                    </small>
                </a>
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

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">
                try{ace.settings.loadState('sidebar')}catch(e){}
            </script>

            <?php include "menu_penjualan_unified.php"; ?>

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
                        <li><a href="#">Antar Cabang</a></li>
                        <li><a href="#">Cabang Mitra</a></li>
                        <li class="active">Input Manual</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Input Penjualan Cabang Mitra
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Input Manual
                            </small>
                        </h1>
                    </div>

                    <?php if($error_msg != ''){ ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                    </div>
                    <?php } ?>

                    <?php if($success_msg != ''){ ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> <?php echo $success_msg; ?>
                    </div>
                    <?php } ?>

                    <form method="post" id="form_input">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">Informasi Transaksi</h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="form-group">
                                                <label>No. Transaksi</label>
                                                <input type="text" class="form-control" value="<?php echo $LastID; ?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Tanggal</label>
                                                <input type="date" name="tgl_transaksi" class="form-control"
                                                       value="<?php echo $tgl_transaksi; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Cabang Asal</label>
                                                <input type="text" class="form-control"
                                                       value="<?php echo $kd_cabang; ?> - <?php echo $nama_cabang; ?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Cabang Mitra Tujuan <span class="text-danger">*</span></label>
                                                <select name="cabang_tujuan" class="form-control" required>
                                                    <option value="">-- Pilih Cabang Mitra --</option>
                                                    <?php
                                                    $q_cab = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang
                                                                                      WHERE kode_cabang != '$kd_cabang'
                                                                                      AND tipe_cabang = 'MITRA'
                                                                                      AND status='1'
                                                                                      ORDER BY nama_cabang");
                                                    while($r_cab = mysqli_fetch_array($q_cab)){
                                                        $sel = ($r_cab['kode_cabang'] == $cabang_tujuan) ? 'selected' : '';
                                                        echo "<option value='{$r_cab['kode_cabang']}' $sel>{$r_cab['kode_cabang']} - {$r_cab['nama_cabang']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Keterangan</label>
                                                <textarea name="keterangan" class="form-control" rows="2"><?php echo $keterangan; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">Daftar Item</h4>
                                        <div class="widget-toolbar">
                                            <button type="button" class="btn btn-xs btn-success" onclick="addItemRow()">
                                                <i class="fa fa-plus"></i> Tambah Item
                                            </button>
                                        </div>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div id="item_container">
                                                <div class="item-row row">
                                                    <div class="col-sm-5">
                                                        <input type="text" name="item_code[]" class="form-control input-sm"
                                                               placeholder="Kode Item" list="item_list">
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <input type="number" name="item_qty[]" class="form-control input-sm"
                                                               placeholder="Qty" min="1" value="1">
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <input type="text" name="item_price[]" class="form-control input-sm price-input"
                                                               placeholder="Harga">
                                                    </div>
                                                    <div class="col-sm-1">
                                                        <i class="fa fa-trash remove-item" onclick="removeItemRow(this)"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <datalist id="item_list">
                                                <?php
                                                $q_item = mysqli_query($koneksi, "SELECT kode_barang, nama_barang FROM tblbarang
                                                                                   WHERE status='1' ORDER BY nama_barang LIMIT 500");
                                                while($r_item = mysqli_fetch_array($q_item)){
                                                    echo "<option value='{$r_item['kode_barang']}'>{$r_item['nama_barang']}</option>";
                                                }
                                                ?>
                                            </datalist>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-12">
                                <hr>
                                <button type="submit" name="btn_simpan" class="btn btn-success">
                                    <i class="fa fa-save"></i> Simpan
                                </button>
                                <a href="antarcab_list.php" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> Kembali
                                </a>
                            </div>
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
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
    function addItemRow(){
        var html = '<div class="item-row row">' +
                   '<div class="col-sm-5"><input type="text" name="item_code[]" class="form-control input-sm" placeholder="Kode Item" list="item_list"></div>' +
                   '<div class="col-sm-2"><input type="number" name="item_qty[]" class="form-control input-sm" placeholder="Qty" min="1" value="1"></div>' +
                   '<div class="col-sm-4"><input type="text" name="item_price[]" class="form-control input-sm price-input" placeholder="Harga"></div>' +
                   '<div class="col-sm-1"><i class="fa fa-trash remove-item" onclick="removeItemRow(this)"></i></div>' +
                   '</div>';
        $('#item_container').append(html);
    }

    function removeItemRow(el){
        if($('.item-row').length > 1){
            $(el).closest('.item-row').remove();
        }
    }

    // Format price input
    $(document).on('keyup', '.price-input', function(){
        var val = $(this).val().replace(/\D/g, '');
        $(this).val(val.replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
    });
    </script>
</body>
</html>

<?php
}
?>
