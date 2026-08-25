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

		// Ambil data yang akan diedit
		$kd = mysqli_real_escape_string($koneksi, $_GET['kd']);
		$sql_edit = mysqli_query($koneksi, "SELECT * FROM tbkategori_motor WHERE id='$kd'");
		$data_edit = mysqli_fetch_array($sql_edit);

		if(!$data_edit) {
			echo "<script>alert('Data tidak ditemukan'); window.location='master_kategori_motor.php';</script>";
			exit;
		}

		if(isset($_POST['submit'])){
			$kategori = strtoupper(trim($_POST['kategori']));
			$keterangan = trim($_POST['keterangan']);

			// Validasi input sesuai Excel MASTER KATEGORI MOTOR
			if(empty($kategori)) {
				$pesan = "Kategori Motor tidak boleh kosong!";
			} elseif(strpos($kategori, ' ') !== false) {
				$pesan = "Hanya boleh 1 kata, tidak boleh ada spasi!";
			} elseif(empty($keterangan)) {
				$pesan = "Keterangan tidak boleh kosong!";
			} else {
				// Cek duplikasi kategori (kecuali data sendiri)
				$cek_kategori = mysqli_query($koneksi, "SELECT id FROM tbkategori_motor WHERE kategori='$kategori' AND id != '$kd'");
				if(mysqli_num_rows($cek_kategori) > 0) {
					$pesan = "KATEGORI MOTOR harus diganti";
				} else {
					// Update data
					$sql = "UPDATE tbkategori_motor SET
							kategori = '$kategori',
							keterangan = '$keterangan'
							WHERE id = '$kd'";

					if(mysqli_query($koneksi, $sql)) {
						echo "<script>alert('Data berhasil diperbarui'); window.location='master_kategori_motor.php';</script>";
					} else {
						$pesan = "Gagal memperbarui data: " . mysqli_error($koneksi);
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

		<meta name="description" content="Edit Master Kategori Motor" />
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
							<li>
								<a href="master_kategori_motor.php">Kategori Motor</a>
							</li>
							<li class="active">Edit</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">Edit Master Kategori Motor</h4>
										<div class="widget-toolbar">
											<a href="master_kategori_motor.php" class="btn btn-sm btn-warning">
												<i class="ace-icon fa fa-arrow-left"></i>
												Kembali
											</a>
										</div>
									</div>

									<div class="widget-body">
										<div class="widget-main">
											<?php if(isset($pesan)): ?>
											<div class="alert alert-danger">
												<strong>Error!</strong> <?php echo $pesan; ?>
											</div>
											<?php endif; ?>

											<!-- DATA AWAL YANG MUNCUL HANYA TAMPILAN -->
											<div class="alert alert-info">
												<h4><i class="ace-icon fa fa-eye"></i> Data Awal (Hanya Tampilan)</h4>
												<table class="table table-bordered">
													<tr>
														<td width="30%"><strong>Kategori Motor:</strong></td>
														<td><?php echo $data_edit['kategori']; ?></td>
													</tr>
													<tr>
														<td><strong>Keterangan:</strong></td>
														<td><?php echo $data_edit['keterangan'] ?? 'Tidak ada keterangan'; ?></td>
													</tr>
												</table>
											</div>

											<form method="post" class="form-horizontal" role="form">
												<div class="alert alert-warning">
													<h5><i class="ace-icon fa fa-edit"></i> Perubahan bisa dilakukan di kolom "Edit Menjadi" di bawah ini</h5>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="kategori">
														Edit Kategori Motor Menjadi <span class="red">*</span>
													</label>
													<div class="col-sm-9">
														<input type="text" id="kategori" name="kategori" class="form-control"
															placeholder="Masukkan kategori motor baru..."
															maxlength="20"
															value="<?php echo isset($_POST['kategori']) ? mysqli_real_escape_string($koneksi, $_POST['kategori']) : $data_edit['kategori']; ?>" required />
														<span class="help-block">
															<strong>Hanya boleh 1 kata, tidak boleh ada spasi</strong>, otomatis huruf besar
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="keterangan">
														Edit Keterangan Menjadi <span class="red">*</span>
													</label>
													<div class="col-sm-9">
														<textarea id="keterangan" name="keterangan" class="form-control" rows="3"
															placeholder="Masukkan keterangan baru..."
															required><?php echo isset($_POST['keterangan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan']) : $data_edit['keterangan']; ?></textarea>
														<span class="help-block">Muncul isi keterangan dari data awal, namun masih bisa diubah</span>
													</div>
												</div>

												<div class="clearfix form-actions">
													<div class="col-md-offset-3 col-md-9">
														<button class="btn btn-info" type="submit" name="submit">
															<i class="ace-icon fa fa-check bigger-110"></i>
															Update
														</button>

														&nbsp; &nbsp; &nbsp;
														<button class="btn" type="reset">
															<i class="ace-icon fa fa-undo bigger-110"></i>
															Reset
														</button>

														&nbsp; &nbsp; &nbsp;
														<a href="master_kategori_motor.php" class="btn btn-warning">
															<i class="ace-icon fa fa-arrow-left bigger-110"></i>
															Kembali
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
		</div>

		<!-- basic scripts -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>
		<script src="assets/js/bootstrap.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<script type="text/javascript">
			jQuery(function($) {
				// Transform kategori to uppercase dan hapus spasi
				$('#kategori').on('input', function() {
					// Hapus spasi dan karakter khusus, hanya huruf dan angka
					this.value = this.value.replace(/[^A-Za-z0-9]/g, '');
					// Convert ke uppercase
					this.value = this.value.toUpperCase();
				});

				// Validasi form sebelum submit
				$('form').on('submit', function(e) {
					var kategori = $('#kategori').val().trim();
					var keterangan = $('#keterangan').val().trim();

					if (kategori == '') {
						alert('Kategori Motor tidak boleh kosong!');
						$('#kategori').focus();
						e.preventDefault();
						return false;
					}

					if (kategori.indexOf(' ') !== -1) {
						alert('Hanya boleh 1 kata, tidak boleh ada spasi!');
						$('#kategori').focus();
						e.preventDefault();
						return false;
					}

					if (keterangan == '') {
						alert('Keterangan tidak boleh kosong!');
						$('#keterangan').focus();
						e.preventDefault();
						return false;
					}
				});
			});
		</script>
	</body>
</html>

<?php } ?>