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

        // Mode edit atau add new
        $mode = $_GET['mode'] ?? 'add';
        $kode_jasa = $_GET['kode'] ?? '';
        
        // Inisialisasi variabel
        $namaitem = '';
        $jenis = 'JASA';
        $satuan = 'Unit';
        $hargajual = 0;
        $jasawaktu = 0;
        $jasasatuanwaktu = '1'; // 1=menit, 2=jam
        $jenis_jasa = '1';
        $statusitem = '1';
        $note = '';
        $hargapokok = 0;
        
        // Jika mode edit, ambil data jasa
        if($mode == 'edit' && $kode_jasa != '') {
            $cari_kd = mysqli_query($koneksi,"SELECT * FROM tblitem WHERE noitem='$kode_jasa'");
            if(mysqli_num_rows($cari_kd) > 0) {
                $tm_cari = mysqli_fetch_array($cari_kd);
                $namaitem = $tm_cari['namaitem'];
                $jenis = $tm_cari['jenis'];
                $satuan = $tm_cari['satuan'];
                $hargajual = $tm_cari['hargajual'];
                $jasawaktu = $tm_cari['jasawaktu'];
                $jasasatuanwaktu = $tm_cari['jasasatuanwaktu'];
                $jenis_jasa = $tm_cari['jenis_jasa'];
                $statusitem = $tm_cari['statusitem'];
                $note = $tm_cari['note'];
                $hargapokok = $tm_cari['hargapokok'];
            }
        }

        // Generate kode jasa otomatis untuk mode add
        if($mode == 'add') {
            $query_max = mysqli_query($koneksi,"SELECT MAX(CAST(SUBSTRING(noitem, 4) AS UNSIGNED)) as max_kode 
                                               FROM tblitem 
                                               WHERE noitem LIKE 'JSA%' AND jasawaktu > 0");
            $max_data = mysqli_fetch_array($query_max);
            $max_kode = $max_data['max_kode'] ?? 0;
            $kode_jasa = 'JSA' . str_pad($max_kode + 1, 4, '0', STR_PAD_LEFT);
        }

        // Proses simpan jasa
        if(isset($_POST['btnsimpan'])) {
            $kode_jasa = $_POST['kode_jasa'];
            $namaitem = $_POST['namaitem'];
            $jenis = $_POST['jenis'];
            $satuan = $_POST['satuan'];
            $hargajual = $_POST['hargajual'];
            $hargapokok = $_POST['hargapokok'];
            $jasawaktu = $_POST['jasawaktu'];
            $jasasatuanwaktu = $_POST['jasasatuanwaktu'];
            $jenis_jasa = $_POST['jenis_jasa'];
            $statusitem = $_POST['statusitem'];
            $note = $_POST['note'];
            
            if($mode == 'add') {
                // Insert jasa baru
                $insert = mysqli_query($koneksi,"INSERT INTO tblitem 
                                                (noitem, kodebarcode, namaitem, jenis, satuan, hargapokok, hargajual, 
                                                 totalpokok, quantity, statusitem, jasawaktu, jasasatuanwaktu, 
                                                 jenis_jasa, note, stokmin, stok_maks) 
                                                VALUES 
                                                ('$kode_jasa', '$kode_jasa', '$namaitem', '$jenis', '$satuan', 
                                                 '$hargapokok', '$hargajual', '$hargapokok', '1', '$statusitem', 
                                                 '$jasawaktu', '$jasasatuanwaktu', '$jenis_jasa', '$note', '0', '999')");
                                                 
                if($insert) {
                    echo"<script>window.alert('Jasa service berhasil ditambahkan!');
                    window.location=('jasa-list.php');</script>";
                }
            } else {
                // Update jasa
                $update = mysqli_query($koneksi,"UPDATE tblitem 
                                                SET namaitem='$namaitem', jenis='$jenis', satuan='$satuan',
                                                    hargapokok='$hargapokok', hargajual='$hargajual', 
                                                    jasawaktu='$jasawaktu', jasasatuanwaktu='$jasasatuanwaktu',
                                                    jenis_jasa='$jenis_jasa', statusitem='$statusitem', note='$note'
                                                WHERE noitem='$kode_jasa'");
                                                
                if($update) {
                    echo"<script>window.alert('Jasa service berhasil diupdate!');
                    window.location=('jasa-list.php');</script>";
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

		<meta name="description" content="with draggable and editable events" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="assets/css/fullcalendar.min.css" />

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

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

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

<?php include "menu_servis01.php"; ?>

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
								<a href="#">Master Data</a>
							</li>
                            <li>
								<a href="jasa-list.php">Master Jasa</a>
							</li>                            
							<li class="active"><?php echo ($mode == 'edit') ? 'Edit' : 'Input'; ?> Jasa Service</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
                        <div class="page-header">
                            <h1>
                                <?php echo ($mode == 'edit') ? 'Edit' : 'Input'; ?> Jasa Service
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Kelola data jasa service dan perawatan
                                </small>
                            </h1>
                        </div>

                        <form class="form-horizontal" action="" method="post" role="form">                                            
                            <input type="hidden" name="kode_jasa" value="<?php echo $kode_jasa; ?>"/>
                        
                            <!-- Jasa Service Info -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-wrench"></i>
                                        Informasi Jasa Service
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6">
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Kode Jasa</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" class="form-control" value="<?php echo $kode_jasa; ?>" readonly />
                                                        <span class="help-block">Kode otomatis generate</span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Nama Jasa *</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" class="form-control" name="namaitem" 
                                                               value="<?php echo $namaitem; ?>" required 
                                                               placeholder="Contoh: SERVIS STANDAR MATIC" maxlength="50" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Jenis Item</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" class="form-control" name="jenis" 
                                                               value="<?php echo $jenis; ?>" 
                                                               placeholder="JASA" maxlength="20" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Satuan</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-control" name="satuan">
                                                            <option value="Unit" <?php echo ($satuan=='Unit')?'selected':''; ?>>Unit</option>
                                                            <option value="Pcs" <?php echo ($satuan=='Pcs')?'selected':''; ?>>Pcs</option>
                                                            <option value="Set" <?php echo ($satuan=='Set')?'selected':''; ?>>Set</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6">
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Jenis Jasa</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-control" name="jenis_jasa" required>
                                                            <option value="1" <?php echo ($jenis_jasa=='1')?'selected':''; ?>>Jasa Servis</option>
                                                            <option value="2" <?php echo ($jenis_jasa=='2')?'selected':''; ?>>Jasa Perawatan</option>
                                                            <option value="3" <?php echo ($jenis_jasa=='3')?'selected':''; ?>>Jasa Perbaikan</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Status</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-control" name="statusitem" required>
                                                            <option value="1" <?php echo ($statusitem=='1')?'selected':''; ?>>Aktif</option>
                                                            <option value="0" <?php echo ($statusitem=='0')?'selected':''; ?>>Non-Aktif</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Harga Pokok</label>
                                                    <div class="col-sm-9">
                                                        <input type="number" class="form-control" name="hargapokok" 
                                                               value="<?php echo $hargapokok; ?>" 
                                                               placeholder="0" min="0" />
                                                        <span class="help-block">Harga pokok untuk perhitungan profit</span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Harga Jual *</label>
                                                    <div class="col-sm-9">
                                                        <input type="number" class="form-control" name="hargajual" 
                                                               value="<?php echo $hargajual; ?>" required
                                                               placeholder="0" min="0" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Waktu Service -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-clock-o"></i>
                                        Waktu Pengerjaan
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label no-padding-right">Waktu Jasa *</label>
                                                    <div class="col-sm-8">
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="jasawaktu" 
                                                                   value="<?php echo $jasawaktu; ?>" required
                                                                   placeholder="0" min="1" />
                                                            <span class="input-group-addon">
                                                                <select name="jasasatuanwaktu" style="border:none; background:transparent;">
                                                                    <option value="1" <?php echo ($jasasatuanwaktu=='1')?'selected':''; ?>>Menit</option>
                                                                    <option value="2" <?php echo ($jasasatuanwaktu=='2')?'selected':''; ?>>Jam</option>
                                                                </select>
                                                            </span>
                                                        </div>
                                                        <span class="help-block">Estimasi waktu pengerjaan</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6">
                                                <div class="alert alert-info">
                                                    <i class="ace-icon fa fa-info-circle"></i>
                                                    <strong>Info:</strong> Waktu pengerjaan digunakan untuk:
                                                    <ul style="margin-top: 10px; margin-bottom: 0;">
                                                        <li>Estimasi waktu total service</li>
                                                        <li>Penjadwalan mekanik</li>
                                                        <li>Perhitungan work order</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Keterangan -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-comment"></i>
                                        Keterangan & Catatan
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label no-padding-right">Keterangan</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" name="note" rows="4" 
                                                          placeholder="Keterangan detail jasa service, prosedur khusus, atau catatan penting..."><?php echo $note; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <button class="btn btn-success" type="submit" name="btnsimpan">
                                                    <i class="ace-icon fa fa-save"></i>
                                                    <?php echo ($mode == 'edit') ? 'Update' : 'Simpan'; ?> Jasa Service
                                                </button>
                                                <a href="jasa-list.php" class="btn btn-default">
                                                    <i class="ace-icon fa fa-arrow-left"></i>
                                                    Kembali ke Daftar
                                                </a>
                                                <?php if($mode == 'edit') { ?>
                                                <a href="jasa-detail.php?kode=<?php echo $kode_jasa; ?>" class="btn btn-info">
                                                    <i class="ace-icon fa fa-eye"></i>
                                                    Lihat Detail
                                                </a>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </form>

					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

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

		<!--[if !IE]> -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>

		<!-- <![endif]-->

		<!--[if IE]>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<![endif]-->
		<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
		<script src="assets/js/bootstrap.min.js"></script>

		<!-- page specific plugin scripts -->

		<!--[if lte IE 8]>
		  <script src="assets/js/excanvas.min.js"></script>
		<![endif]-->
		<script src="assets/js/jquery-ui.custom.min.js"></script>
		<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
		<script src="assets/js/chosen.jquery.min.js"></script>
		<script src="assets/js/spinbox.min.js"></script>
		<script src="assets/js/bootstrap-datepicker.min.js"></script>
		<script src="assets/js/bootstrap-timepicker.min.js"></script>
		<script src="assets/js/moment.min.js"></script>
		<script src="assets/js/daterangepicker.min.js"></script>
		<script src="assets/js/bootstrap-datetimepicker.min.js"></script>
		<script src="assets/js/bootstrap-colorpicker.min.js"></script>
		<script src="assets/js/jquery.knob.min.js"></script>
		<script src="assets/js/autosize.min.js"></script>
		<script src="assets/js/jquery.inputlimiter.min.js"></script>
		<script src="assets/js/jquery.maskedinput.min.js"></script>
		<script src="assets/js/bootstrap-tag.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

        <script type="text/javascript">
            jQuery(function($) {
                // Form validation
                $('form').on('submit', function(e) {
                    var namaitem = $('input[name="namaitem"]').val().trim();
                    var hargajual = $('input[name="hargajual"]').val();
                    var jasawaktu = $('input[name="jasawaktu"]').val();
                    
                    if(namaitem == '') {
                        alert('Nama jasa harus diisi!');
                        $('input[name="namaitem"]').focus();
                        return false;
                    }
                    
                    if(hargajual <= 0) {
                        alert('Harga jual harus lebih dari 0!');
                        $('input[name="hargajual"]').focus();
                        return false;
                    }
                    
                    if(jasawaktu <= 0) {
                        alert('Waktu jasa harus lebih dari 0!');
                        $('input[name="jasawaktu"]').focus();
                        return false;
                    }
                    
                    return true;
                });
            });
        </script>
        
	</body>
</html>

<?php 
	}
?>