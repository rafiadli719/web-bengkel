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
		$id = $_GET['kd'];
		$sql_edit = mysqli_query($koneksi, "SELECT * FROM tbmaster_jenis_item WHERE id='$id'");
		$data_edit = mysqli_fetch_array($sql_edit);

		if(!$data_edit) {
			echo "<script>alert('Data tidak ditemukan'); window.location='master_jenis_item.php';</script>";
			exit;
		}

		if(isset($_POST['submit'])){
			$kode = strtoupper(trim($_POST['kode']));
			$keterangan = trim($_POST['keterangan']);
			$persentase = floatval($_POST['persentase']);

			// Validasi input sesuai Excel
			if(empty($kode)) {
				$pesan = "Kode tidak boleh kosong!";
			} elseif(strlen($kode) != 3) {
				$pesan = "Kode harus berisi 3 digit huruf!";
			} elseif(!ctype_alpha($kode)) {
				$pesan = "Kode hanya boleh huruf (tidak boleh angka atau simbol)!";
			} elseif(empty($keterangan)) {
				$pesan = "Keterangan tidak boleh kosong!";
			} elseif(!is_numeric($_POST['persentase'])) {
				$pesan = "Persentase hanya bisa diisi angka!";
			} elseif($persentase < 0 || $persentase > 100) {
				$pesan = "Persentase harus antara 0-100%!";
			} else {
				// Cek duplikasi kode (kecuali data sendiri)
				$cek_kode = mysqli_query($koneksi, "SELECT id FROM tbmaster_jenis_item WHERE kode='$kode' AND id != '$id'");
				if(mysqli_num_rows($cek_kode) > 0) {
					$pesan = "Kode sudah ada, kode harus diganti!";
				} else {
					// Update data
					$sql = "UPDATE tbmaster_jenis_item SET
							kode = '$kode',
							nama_jenis = '$keterangan',
							margin = $persentase,
							updated_at = NOW()
							WHERE id = '$id'";

					if(mysqli_query($koneksi, $sql)) {
						echo "<script>alert('Data berhasil diperbarui'); window.location='master_jenis_item.php';</script>";
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

		<meta name="description" content="Edit Master Jenis Item" />
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

				<?php include "menu_master01b.php"; ?>

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
							<li>
								<a href="master_jenis_item.php">Jenis Item</a>
							</li>
							<li class="active">Edit</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">Edit Master Jenis Item</h4>
										<div class="widget-toolbar">
											<a href="master_jenis_item.php" class="btn btn-sm btn-warning">
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

											<form method="post" class="form-horizontal" role="form">
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="kode">
														Kode <span class="red">*</span>
													</label>
													<div class="col-sm-9">
														<input type="text" id="kode" name="kode" class="form-control"
																placeholder="Contoh: ORI, IMI, AFT" maxlength="3"
																value="<?php echo isset($_POST['kode']) ? $_POST['kode'] : $data_edit['kode']; ?>" required />
														<span class="help-block">
															<strong>Wajib 3 digit huruf</strong> (tidak boleh ada kode yang sama persis)
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="keterangan">
														Keterangan <span class="red">*</span>
													</label>
													<div class="col-sm-9">
														<textarea id="keterangan" name="keterangan" class="form-control" rows="2"
																placeholder="Diisi bebas sesuai arti kode..."
																required><?php echo isset($_POST['keterangan']) ? $_POST['keterangan'] : $data_edit['nama_jenis']; ?></textarea>
														<span class="help-block">Keterangan diisi bebas sesuai arti kode</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="persentase">
														Persentase <span class="red">*</span>
													</label>
													<div class="col-sm-9">
														<div class="input-group">
															<input type="text" id="persentase" name="persentase" class="form-control"
																	placeholder="0"
																	value="<?php echo isset($_POST['persentase']) ? $_POST['persentase'] : number_format($data_edit['margin'], 2); ?>" required />
															<span class="input-group-addon">%</span>
														</div>
														<span class="help-block">
															<strong>Persentase hanya bisa diisi angka</strong> (0-100)
														</span>
													</div>
												</div>

												<div class="form-group">
													<div class="col-sm-12">
														<div class="alert alert-info">
															<h5><i class="ace-icon fa fa-info-circle"></i> Data Saat Ini:</h5>
															<ul class="list-unstyled">
																<li><strong>Kode:</strong> <?php echo $data_edit['kode']; ?></li>
																<li><strong>Keterangan:</strong> <?php echo $data_edit['nama_jenis']; ?></li>
																<li><strong>Persentase:</strong> <?php echo number_format($data_edit['margin'], 2); ?>%</li>
															</ul>
														</div>
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
														<a href="master_jenis_item.php" class="btn btn-warning">
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
				// Transform kode to uppercase dan validasi 3 huruf
				$('#kode').on('input', function() {
					// Hapus karakter yang bukan huruf
					this.value = this.value.replace(/[^A-Za-z]/g, '');
					// Convert ke uppercase
					this.value = this.value.toUpperCase();
					// Maksimal 3 karakter
					if(this.value.length > 3) {
						this.value = this.value.substring(0, 3);
					}
				});

				// Validasi persentase hanya angka
				$('#persentase').on('input', function() {
					// Hapus karakter yang bukan angka atau titik
					this.value = this.value.replace(/[^0-9.]/g, '');

					// Pastikan hanya satu titik desimal
					var parts = this.value.split('.');
					if (parts.length > 2) {
						this.value = parts[0] + '.' + parts.slice(1).join('');
					}

					// Validasi range 0-100
					var nilai = parseFloat(this.value);
					if (nilai > 100) {
						this.value = '100';
					}
				});

				// Validasi form sebelum submit
				$('form').on('submit', function(e) {
					var kode = $('#kode').val().trim();
					var keterangan = $('#keterangan').val().trim();
					var persentase = $('#persentase').val().trim();

					if (kode.length != 3) {
						alert('Kode harus berisi tepat 3 digit huruf!');
						$('#kode').focus();
						e.preventDefault();
						return false;
					}

					if (!/^[A-Z]{3}$/.test(kode)) {
						alert('Kode hanya boleh berisi huruf (3 karakter)!');
						$('#kode').focus();
						e.preventDefault();
						return false;
					}

					if (keterangan == '') {
						alert('Keterangan tidak boleh kosong!');
						$('#keterangan').focus();
						e.preventDefault();
						return false;
					}

					if (persentase == '' || isNaN(persentase)) {
						alert('Persentase hanya bisa diisi angka!');
						$('#persentase').focus();
						e.preventDefault();
						return false;
					}

					var nilaiPersentase = parseFloat(persentase);
					if (nilaiPersentase < 0 || nilaiPersentase > 100) {
						alert('Persentase harus antara 0-100!');
						$('#persentase').focus();
						e.preventDefault();
						return false;
					}
				});
			});
		</script>
	</body>
</html>

<?php } ?>