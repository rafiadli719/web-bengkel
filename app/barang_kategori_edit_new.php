<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";
    include "../config/accurate_config.php";

    // Get data kategori untuk edit
    $kd = mysqli_real_escape_string($koneksi, $_GET['kd']);
    $cari_kd = mysqli_query($koneksi, "SELECT * FROM tblitemjenis WHERE id='$kd'");
    $tm_cari = mysqli_fetch_array($cari_kd);    
    $jenis = $tm_cari['jenis'];
    $namajenis = $tm_cari['namajenis'];
    $keterangan = $tm_cari['keterangan'] ?? '';
    $ikut_margin_jenis = $tm_cari['ikut_margin_jenis'] ?? '1';
    $margin_khusus = $tm_cari['margin_khusus'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
</head>

<body class="no-skin">
    <div class="main-container">
        <div class="main-content">
            <div class="main-content-inner">
                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">EDIT KATEGORI ITEM</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form class="form-horizontal" action="update_barang_kategori_new.php" method="post">
                                            <input type="hidden" name="txtid" value="<?php echo $kd; ?>"/>
                                            
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">KATEGORI ITEM</label>
                                                <div class="col-sm-9">
                                                    <input type="text" name="txtkategori" class="form-control" 
                                                           value="<?php echo $jenis; ?>" readonly style="background-color: #f5f5f5;" />
                                                    <small class="text-muted">Data awal yang muncul hanya tampilan, bisa mengubahnya di kolom "edit menjadi"</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">KETERANGAN</label>
                                                <div class="col-sm-9">
                                                    <textarea name="txtketerangan_awal" class="form-control" rows="2" readonly style="background-color: #f5f5f5;"><?php echo $keterangan; ?></textarea>
                                                    <small class="text-muted">Data awal yang muncul hanya tampilan, bisa mengubahnya di kolom "edit menjadi"</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">MARGIN SESUAI JENIS</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" 
                                                           value="<?php echo ($ikut_margin_jenis == '1') ? 'YA' : 'TIDAK'; ?>" 
                                                           readonly style="background-color: #f5f5f5;" />
                                                    <small class="text-muted">Data awal yang muncul hanya tampilan, bisa mengubahnya di kolom "edit menjadi"</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">MARGIN KATEGORI</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" 
                                                           value="<?php echo ($margin_khusus != '') ? $margin_khusus . '%' : '-'; ?>" 
                                                           readonly style="background-color: #f5f5f5;" />
                                                    <small class="text-muted">Data awal yang muncul hanya tampilan, bisa mengubahnya di kolom "edit menjadi"</small>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            <h5><strong>EDIT MENJADI</strong></h5>
                                            
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">KETERANGAN</label>
                                                <div class="col-sm-9">
                                                    <textarea name="txtketerangan" class="form-control" rows="3" required><?php echo $keterangan; ?></textarea>
                                                    <small class="text-muted">Muncul isi keterangan dari awal, namun bisa diubah</small>
                                                </div>
                                            </div>
                                            
                                            <div class="clearfix form-actions">
                                                <div class="col-md-offset-3 col-md-9">
                                                    <button class="btn btn-info btn-lg" type="submit">
                                                        <i class="fa fa-check"></i> SIMPAN
                                                    </button>
                                                    <a href="barang_kategori_new.php" class="btn btn-default btn-lg">
                                                        <i class="fa fa-list"></i> LIHAT DAFTAR KATEGORI ITEM
                                                    </a>
                                                    <a href="index.php" class="btn btn-warning btn-lg">
                                                        <i class="fa fa-home"></i> KE MENU AWAL
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

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
        // Auto-uppercase untuk keterangan
        $('textarea[name="txtketerangan"]').on('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Form validation
        $('form').on('submit', function(e) {
            var keterangan = $('textarea[name="txtketerangan"]').val().trim();
            
            if (keterangan === '') {
                e.preventDefault();
                alert('Keterangan harus diisi!');
                return false;
            }
            
            return confirm('Apakah Anda yakin ingin menyimpan perubahan?');
        });
    </script>
</body>
</html>
