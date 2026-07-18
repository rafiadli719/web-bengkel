<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    include "../config/koneksi.php";

    $stmt=mysqli_prepare($koneksi,"SELECT nama_user FROM tbuser WHERE id=?");
    mysqli_stmt_bind_param($stmt,"s",$id_user);
    mysqli_stmt_execute($stmt);
    $tm_cari=mysqli_fetch_array(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $_nama=$tm_cari['nama_user'];

    $error = '';
    if(isset($_POST['btnsimpan'])){
        $naik = str_replace(',', '.', trim($_POST['threshold_naik']));
        $turun = str_replace(',', '.', trim($_POST['threshold_turun']));

        if(!is_numeric($naik) || (float)$naik <= 0 || !is_numeric($turun) || (float)$turun <= 0){
            $error = 'Kedua threshold harus berupa angka lebih besar dari 0.';
        } else {
            $stmt=mysqli_prepare($koneksi,"UPDATE tb_master_threshold_harga SET persen_threshold=?, updated_by=? WHERE arah='naik'");
            mysqli_stmt_bind_param($stmt,"ds",$naik,$_nama);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt=mysqli_prepare($koneksi,"UPDATE tb_master_threshold_harga SET persen_threshold=?, updated_by=? WHERE arah='turun'");
            mysqli_stmt_bind_param($stmt,"ds",$turun,$_nama);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("location:setting-threshold-harga.php?saved=1");
            exit;
        }
    }

    $data = ['naik'=>5.0,'turun'=>10.0];
    $r = mysqli_query($koneksi,"SELECT arah, persen_threshold FROM tb_master_threshold_harga");
    while($row = mysqli_fetch_assoc($r)) $data[$row['arah']] = $row['persen_threshold'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </button>
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand"><small><?php include "../lib/subtitel.php"; ?></small></a>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Pembelian</a></li>
                        <li class="active">Setting Threshold Harga Beli</li>
                    </ul>
                </div>

                <div class="page-content">
                    <h4 class="header blue"><i class="fa fa-bell"></i> Setting Threshold Alarm Harga Beli</h4>
                    <small><i class="ace-icon fa fa-angle-double-right"></i> Persentase perubahan harga beli yang dianggap signifikan (naik/turun dibedakan)</small>

                    <?php if(isset($_GET['saved'])): ?>
                        <div class="alert alert-success">Threshold berhasil disimpan. Perubahan tidak retroaktif — alarm lama tetap pakai threshold lama.</div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-cog"></i> Threshold</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="POST" action="setting-threshold-harga.php">
                                            <div class="form-group">
                                                <label>Threshold Harga Naik (%)</label>
                                                <input type="text" name="threshold_naik" class="form-control" required value="<?php echo htmlspecialchars($data['naik']); ?>">
                                                <p class="help-block">Kalau harga beli naik &ge; persentase ini dari transaksi sebelumnya, sistem bikin alarm.</p>
                                            </div>
                                            <div class="form-group">
                                                <label>Threshold Harga Turun (%)</label>
                                                <input type="text" name="threshold_turun" class="form-control" required value="<?php echo htmlspecialchars($data['turun']); ?>">
                                                <p class="help-block">Kalau harga beli turun &ge; persentase ini dari transaksi sebelumnya, sistem bikin alarm.</p>
                                            </div>
                                            <button type="submit" name="btnsimpan" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                                            <a href="alarm-harga-beli.php" class="btn btn-default"><i class="fa fa-list"></i> Lihat Daftar Alarm</a>
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

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
<?php
}
?>
