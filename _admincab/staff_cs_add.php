<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];
		include "../config/koneksi.php";

		$cari_kd=mysqli_query($koneksi,"SELECT
                                        nama_user, password, user_akses, foto_user
                                        FROM tbuser WHERE id='$id_user'");
		$tm_cari=mysqli_fetch_array($cari_kd);
		$_nama=$tm_cari['nama_user'];
		$foto_user=$tm_cari['foto_user'];
		if($foto_user=='') {
			$foto_user="file_upload/avatar.png";
		}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Tambah Staff Customer Service" />
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

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
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

				<?php include "menu_user.php"; ?>

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
								<a href="#">Divisi Staff</a>
							</li>
							<li>
								<a href="staff_cs.php">Staff CS</a>
							</li>
							<li class="active">Tambah Data</li>
						</ul>
					</div>

					<div class="page-content">
						<br>
						<div class="row">
							<div class="col-xs-12">
								<form class="form-horizontal" action="staff_cs_save.php" method="post" enctype="multipart/form-data">

									<div class="widget-box">
										<div class="widget-header widget-header-blue widget-header-flat">
											<h4 class="widget-title lighter">
												<i class="ace-icon fa fa-user-plus"></i>
												Tambah Staff Customer Service - Data Master & User Login
											</h4>
										</div>
										<div class="widget-body">
											<div class="widget-main">

												<h4 class="blue">
													<i class="ace-icon fa fa-lock"></i>
													Data Login
												</h4>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="username">Username *</label>
													<div class="col-sm-9">
														<input type="text" id="username" name="username" class="col-xs-10 col-sm-5"
														required autocomplete="off" placeholder="Username untuk login" />
														<span class="help-inline col-xs-12 col-sm-7">
															<small class="red">Username harus unik</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="password">Password *</label>
													<div class="col-sm-9">
														<input type="password" id="password" name="password" class="col-xs-10 col-sm-5"
														required autocomplete="off" placeholder="Password untuk login" />
													</div>
												</div>

												<div class="space-6"></div>
												<h4 class="green">
													<i class="ace-icon fa fa-user"></i>
													Data Personal
												</h4>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="nama_staff">Nama Lengkap *</label>
													<div class="col-sm-9">
														<input type="text" id="nama_staff" name="nama_staff" class="col-xs-10 col-sm-5"
														required autocomplete="off" placeholder="Nama lengkap staff CS" />
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="email">Email</label>
													<div class="col-sm-9">
														<input type="email" id="email" name="email" class="col-xs-10 col-sm-5"
														autocomplete="off" placeholder="alamat@email.com" />
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="telepon">No. Telepon</label>
													<div class="col-sm-9">
														<input type="text" id="telepon" name="telepon" class="col-xs-10 col-sm-5"
														autocomplete="off" placeholder="08123456789" />
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="alamat">Alamat</label>
													<div class="col-sm-9">
														<textarea id="alamat" name="alamat" class="col-xs-10 col-sm-5" rows="3"
														placeholder="Alamat lengkap"></textarea>
													</div>
												</div>

												<div class="space-6"></div>
												<h4 class="orange">
													<i class="ace-icon fa fa-briefcase"></i>
													Data Pekerjaan
												</h4>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="shift_kerja">Shift Kerja *</label>
													<div class="col-sm-9">
														<select name="shift_kerja" id="shift_kerja" class="col-xs-10 col-sm-5" required>
															<option value="">Pilih Shift</option>
															<option value="pagi">Pagi (08:00 - 16:00)</option>
															<option value="siang">Siang (16:00 - 24:00)</option>
															<option value="malam">Malam (24:00 - 08:00)</option>
														</select>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="spesialisasi">Spesialisasi</label>
													<div class="col-sm-9">
														<input type="text" id="spesialisasi" name="spesialisasi" class="col-xs-10 col-sm-5"
														autocomplete="off" placeholder="Contoh: Service Motor, Layanan Pelanggan VIP" />
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="foto">Foto</label>
													<div class="col-sm-9">
														<input type="file" id="foto" name="foto" accept="image/*" />
														<span class="help-inline">
															<small>Upload foto (opsional, format: JPG/PNG, max 2MB)</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="status_staff">Status *</label>
													<div class="col-sm-9">
														<select name="status_staff" id="status_staff" class="col-xs-10 col-sm-5" required>
															<option value="aktif">Aktif</option>
															<option value="nonaktif">Non-Aktif</option>
														</select>
													</div>
												</div>

											</div>
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
											<a href="staff_cs.php" class="btn btn-default">
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

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>
	</body>
</html>

<?php
	}
?>