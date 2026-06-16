<?php
session_start();
if(empty($_SESSION['_iduser'])){
	header("location:../index.php");
} else {
	$id_user=$_SESSION['_iduser'];
	$kd_cabang=$_SESSION['_cabang'];
	include "../config/koneksi.php";

	$cari_kd=mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user
	                                FROM tbuser WHERE id='$id_user'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$_nama=$tm_cari['nama_user'];
	$pwd=$tm_cari['password'];
	$lvl_akses=$tm_cari['user_akses'];
	$foto_user=$tm_cari['foto_user'];
	if($foto_user=='') {
		$foto_user="file_upload/avatar.png";
	}

	// Get ID
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

	// Fetch data
	$query = mysqli_query($koneksi, "SELECT * FROM master_tarif_jemput WHERE id='$id'");
	$data = mysqli_fetch_array($query);

	if(!$data) {
		$_SESSION['error'] = "Data tidak ditemukan!";
		header("Location: master-tarif-jemput.php");
		exit;
	}

	// Handle Update
	if(isset($_POST['btnupdate'])) {
		$jenis_motor = mysqli_real_escape_string($koneksi, $_POST['txtjenis']);
		$jarak_km = floatval($_POST['txtjarak']);
		$tarif = intval($_POST['txttarif']);
		$keterangan = mysqli_real_escape_string($koneksi, $_POST['txtketerangan']);

		$update = mysqli_query($koneksi, "UPDATE master_tarif_jemput SET
		                                   jenis_motor='$jenis_motor',
		                                   jarak_km='$jarak_km',
		                                   tarif='$tarif',
		                                   keterangan='$keterangan'
		                                   WHERE id='$id'");

		if($update) {
			$_SESSION['success'] = "Data tarif berhasil diupdate!";
		} else {
			$_SESSION['error'] = "Gagal update data: " . mysqli_error($koneksi);
		}
		header("Location: master-tarif-jemput.php");
		exit;
	}
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
	<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
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
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>

			<div class="navbar-header pull-left">
				<a href="index.php" class="navbar-brand">
					<small><?php include "../lib/logo.php"; ?> <?php include "../lib/subtitel.php"; ?></small>
				</a>
			</div>

			<div class="navbar-buttons navbar-header pull-right" role="navigation">
				<ul class="nav ace-nav">
					<li class="light-blue dropdown-modal">
						<a data-toggle="dropdown" href="#" class="dropdown-toggle">
							<img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profile" />
							<span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
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
		<div id="sidebar" class="sidebar responsive ace-save-state">
			<?php include "menu_dashboard.php"; ?>
			<div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
				<i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
			</div>
		</div>

		<div class="main-content">
			<div class="main-content-inner">
				<div class="breadcrumbs ace-save-state" id="breadcrumbs">
					<ul class="breadcrumb">
						<li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
						<li><a href="master-tarif-jemput.php">Tarif Jemput Antar</a></li>
						<li class="active">Edit</li>
					</ul>
				</div>

				<div class="page-content">
					<div class="row">
						<div class="col-xs-12 col-sm-8">
							<div class="widget-box">
								<div class="widget-header">
									<h4 class="widget-title"><i class="ace-icon fa fa-edit"></i> Edit Tarif Jemput Antar</h4>
								</div>

								<div class="widget-body">
									<div class="widget-main">
										<form method="post" class="form-horizontal">
											<div class="form-group">
												<label class="col-sm-3 control-label">Jenis Motor <span class="text-danger">*</span></label>
												<div class="col-sm-9">
													<select name="txtjenis" class="form-control" required>
														<option value="Motor Jalan" <?php echo $data['jenis_motor'] == 'Motor Jalan' ? 'selected' : ''; ?>>Motor Jalan</option>
														<option value="Motor Mogok" <?php echo $data['jenis_motor'] == 'Motor Mogok' ? 'selected' : ''; ?>>Motor Mogok</option>
													</select>
												</div>
											</div>

											<div class="form-group">
												<label class="col-sm-3 control-label">Jarak (KM) <span class="text-danger">*</span></label>
												<div class="col-sm-9">
													<input type="number" step="0.1" name="txtjarak" class="form-control" value="<?php echo $data['jarak_km']; ?>" required>
													<small class="text-muted">Gunakan format desimal (contoh: 3.5)</small>
												</div>
											</div>

											<div class="form-group">
												<label class="col-sm-3 control-label">Tarif (Rp) <span class="text-danger">*</span></label>
												<div class="col-sm-9">
													<input type="number" name="txttarif" class="form-control" value="<?php echo $data['tarif']; ?>" required>
												</div>
											</div>

											<div class="form-group">
												<label class="col-sm-3 control-label">Keterangan</label>
												<div class="col-sm-9">
													<textarea name="txtketerangan" class="form-control" rows="3"><?php echo htmlspecialchars($data['keterangan']); ?></textarea>
												</div>
											</div>

											<div class="form-group">
												<div class="col-sm-offset-3 col-sm-9">
													<button type="submit" name="btnupdate" class="btn btn-success">
														<i class="fa fa-save"></i> Update
													</button>
													<a href="master-tarif-jemput.php" class="btn btn-default">
														<i class="fa fa-arrow-left"></i> Kembali
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
				<div class="footer-content"><?php include "../lib/footer.php"; ?></div>
			</div>
		</div>
	</div>

	<script src="assets/js/jquery-2.1.4.min.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>
	<script src="assets/js/ace-elements.min.js"></script>
	<script src="assets/js/ace.min.js"></script>
</body>
</html>
<?php } ?>
