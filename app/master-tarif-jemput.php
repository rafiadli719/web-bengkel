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

	// Data Cabang
	$cari_kd=mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang
	                                FROM tbcabang WHERE kode_cabang='$kd_cabang'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_cabang=$tm_cari['nama_cabang'];
	$tipe_cabang=$tm_cari['tipe_cabang'];

	// Handle Add
	if(isset($_POST['btnadd'])) {
		$jenis_motor = mysqli_real_escape_string($koneksi, $_POST['txtjenis']);
		$jarak_min = floatval($_POST['txtjarak_min']);
		$jarak_max = $_POST['txtjarak_max'] ? floatval($_POST['txtjarak_max']) : null;
		$tarif_dasar = intval($_POST['txttarif_dasar']);
		$tarif_per_km = intval($_POST['txttarif_per_km']);
		$keterangan = mysqli_real_escape_string($koneksi, $_POST['txtketerangan']);

		$jarak_max_sql = $jarak_max ? "'$jarak_max'" : "NULL";
		$insert = mysqli_query($koneksi, "INSERT INTO master_tarif_jemput_range (jenis_motor, jarak_min, jarak_max, tarif_dasar, tarif_per_km, keterangan)
		                                   VALUES ('$jenis_motor', '$jarak_min', $jarak_max_sql, '$tarif_dasar', '$tarif_per_km', '$keterangan')");
		if($insert) {
			$_SESSION['success'] = "Data tarif berhasil ditambahkan!";
		} else {
			$_SESSION['error'] = "Gagal menambahkan data: " . mysqli_error($koneksi);
		}
		header("Location: master-tarif-jemput.php");
		exit;
	}

	// Handle Search
	$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
	$filter_jenis = isset($_GET['filter']) ? mysqli_real_escape_string($koneksi, $_GET['filter']) : '';

	$where_conditions = ['aktif = 1'];
	if(!empty($search)) {
		$where_conditions[] = "(jarak_min LIKE '%$search%' OR tarif_dasar LIKE '%$search%' OR keterangan LIKE '%$search%')";
	}
	if(!empty($filter_jenis)) {
		$where_conditions[] = "jenis_motor = '$filter_jenis'";
	}

	$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

	$query = mysqli_query($koneksi, "SELECT * FROM master_tarif_jemput_range $where_clause ORDER BY jenis_motor, jarak_min");

	// Success/Error Message
	$success_msg = isset($_SESSION['success']) ? $_SESSION['success'] : '';
	$error_msg = isset($_SESSION['error']) ? $_SESSION['error'] : '';
	unset($_SESSION['success']);
	unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta charset="utf-8" />
	<title><?php include "../lib/titel.php"; ?></title>
	<meta name="description" content="Master Tarif Jemput Antar Motor" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
	<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
	<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
	<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
	<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

	<script src="assets/js/ace-extra.min.js"></script>

	<style>
		.tarif-card {
			background: #f8f9fa;
			border: 1px solid #dee2e6;
			border-radius: 5px;
			padding: 15px;
			margin-bottom: 15px;
		}
		.tarif-info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
		.label-motor-jalan { background: #5cb85c; }
		.label-motor-mogok { background: #d9534f; }
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
				<i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
			</div>
		</div>

		<div class="main-content">
			<div class="main-content-inner">
				<div class="breadcrumbs ace-save-state" id="breadcrumbs">
					<ul class="breadcrumb">
						<li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
						<li><a href="#">Data Master</a></li>
						<li class="active">Tarif Jemput Antar</li>
					</ul>
				</div>

				<div class="page-content">
					<div class="row">
						<div class="col-xs-12">
							<?php if($success_msg): ?>
							<div class="alert alert-success">
								<button type="button" class="close" data-dismiss="alert">&times;</button>
								<i class="ace-icon fa fa-check"></i> <?php echo $success_msg; ?>
							</div>
							<?php endif; ?>

							<?php if($error_msg): ?>
							<div class="alert alert-danger">
								<button type="button" class="close" data-dismiss="alert">&times;</button>
								<i class="ace-icon fa fa-times"></i> <?php echo $error_msg; ?>
							</div>
							<?php endif; ?>

							<div class="tarif-info">
								<h4><i class="fa fa-info-circle"></i> Sistem Tarif Jemput Antar (Range Based)</h4>
								<p><strong>Keuntungan Sistem Baru:</strong></p>
								<ul>
									<li><strong>Lebih Sederhana:</strong> Menggunakan sistem range, bukan per jarak spesifik</li>
									<li><strong>Mudah Dikelola:</strong> Hanya beberapa range tarif, bukan puluhan data</li>
									<li><strong>Otomatis:</strong> Sistem menghitung tarif berdasarkan range yang sesuai</li>
									<li><strong>Fleksibel:</strong> Mudah menambah atau mengubah range tarif</li>
								</ul>
								<div class="alert alert-success" style="margin-top: 10px;">
									<i class="fa fa-check-circle"></i> <strong>Contoh:</strong> Range 1.1-5.0 KM dengan tarif dasar Rp 8.000 + Rp 2.000 per KM tambahan
								</div>
							</div>

							<div class="widget-box">
								<div class="widget-header">
									<h4 class="widget-title"><i class="ace-icon fa fa-table"></i> Master Tarif Jemput Antar Motor</h4>
									<div class="widget-toolbar">
										<a href="#" data-toggle="modal" data-target="#addModal" class="btn btn-xs btn-success">
											<i class="ace-icon fa fa-plus"></i> Tambah Tarif
										</a>
									</div>
								</div>

								<div class="widget-body">
									<div class="widget-main">
										<!-- Filter & Search -->
										<form method="get" class="form-inline" style="margin-bottom: 15px;">
											<div class="form-group">
												<select name="filter" class="form-control">
													<option value="">Semua Jenis</option>
													<option value="Motor Jalan" <?php echo $filter_jenis == 'Motor Jalan' ? 'selected' : ''; ?>>Motor Jalan</option>
													<option value="Motor Mogok" <?php echo $filter_jenis == 'Motor Mogok' ? 'selected' : ''; ?>>Motor Mogok</option>
												</select>
											</div>
											<div class="form-group">
												<input type="text" name="search" class="form-control" placeholder="Cari jarak, tarif..." value="<?php echo htmlspecialchars($search); ?>">
											</div>
											<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
											<a href="master-tarif-jemput.php" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</a>
										</form>

										<!-- Table -->
										<div class="table-responsive">
											<table class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th width="5%">No</th>
														<th width="15%">Jenis Motor</th>
														<th width="20%">Range Jarak (KM)</th>
														<th width="15%">Tarif Dasar</th>
														<th width="15%">Tarif/KM</th>
														<th width="20%">Keterangan</th>
														<th width="10%">Aksi</th>
													</tr>
												</thead>
												<tbody>
													<?php
													$no = 1;
													while($row = mysqli_fetch_array($query)):
														$label_class = $row['jenis_motor'] == 'Motor Jalan' ? 'label-motor-jalan' : 'label-motor-mogok';
														$jarak_range = number_format($row['jarak_min'], 1);
														if($row['jarak_max']) {
															$jarak_range .= ' - ' . number_format($row['jarak_max'], 1);
														} else {
															$jarak_range .= ' - ∞';
														}
													?>
													<tr>
														<td><?php echo $no++; ?></td>
														<td><span class="label <?php echo $label_class; ?>"><?php echo $row['jenis_motor']; ?></span></td>
														<td><strong><?php echo $jarak_range; ?> KM</strong></td>
														<td><strong>Rp <?php echo number_format($row['tarif_dasar'], 0, ',', '.'); ?></strong></td>
														<td>
															<?php if($row['tarif_per_km'] > 0): ?>
																<span class="text-success">+Rp <?php echo number_format($row['tarif_per_km'], 0, ',', '.'); ?></span>
															<?php else: ?>
																<span class="text-muted">-</span>
															<?php endif; ?>
														</td>
														<td><small><?php echo htmlspecialchars($row['keterangan']); ?></small></td>
														<td>
															<a href="master-tarif-jemput-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-xs btn-info" title="Edit">
																<i class="ace-icon fa fa-pencil"></i>
															</a>
															<a href="#" onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-xs btn-danger" title="Hapus">
																<i class="ace-icon fa fa-trash-o"></i>
															</a>
														</td>
													</tr>
													<?php endwhile; ?>
													<?php if(mysqli_num_rows($query) == 0): ?>
													<tr>
														<td colspan="7" class="text-center"><em>Tidak ada data</em></td>
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
			</div>
		</div>

		<div class="footer">
			<div class="footer-inner">
				<div class="footer-content"><?php include "../lib/footer.php"; ?></div>
			</div>
		</div>
	</div>

	<!-- Add Modal -->
	<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<form method="post">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title"><i class="fa fa-plus"></i> Tambah Range Tarif Jemput Antar</h4>
					</div>
					<div class="modal-body">
						<div class="alert alert-info">
							<i class="fa fa-info-circle"></i> <strong>Sistem Range:</strong> Tentukan range jarak dan tarif yang berlaku untuk range tersebut.
						</div>
						
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Jenis Motor <span class="text-danger">*</span></label>
									<select name="txtjenis" class="form-control" required>
										<option value="">Pilih Jenis</option>
										<option value="Motor Jalan">Motor Jalan</option>
										<option value="Motor Mogok">Motor Mogok</option>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Status</label>
									<select name="txtaktif" class="form-control">
										<option value="1">Aktif</option>
										<option value="0">Nonaktif</option>
									</select>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Jarak Minimum (KM) <span class="text-danger">*</span></label>
									<input type="number" step="0.1" name="txtjarak_min" class="form-control" placeholder="Contoh: 1.1" required>
									<small class="text-muted">Jarak awal range</small>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Jarak Maksimum (KM)</label>
									<input type="number" step="0.1" name="txtjarak_max" class="form-control" placeholder="Contoh: 5.0 (kosong = unlimited)">
									<small class="text-muted">Kosongkan untuk unlimited</small>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Tarif Dasar (Rp) <span class="text-danger">*</span></label>
									<input type="number" name="txttarif_dasar" class="form-control" placeholder="Contoh: 8000" required>
									<small class="text-muted">Tarif dasar untuk range ini</small>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Tarif per KM Tambahan (Rp)</label>
									<input type="number" name="txttarif_per_km" class="form-control" placeholder="Contoh: 2000" value="0">
									<small class="text-muted">Tarif tambahan per KM di atas minimum</small>
								</div>
							</div>
						</div>

						<div class="form-group">
							<label>Keterangan</label>
							<textarea name="txtketerangan" class="form-control" rows="3" placeholder="Penjelasan tarif untuk range ini"></textarea>
						</div>

						<div class="alert alert-warning">
							<i class="fa fa-lightbulb-o"></i> <strong>Contoh:</strong><br>
							- Range 1.1-5.0 KM dengan tarif dasar Rp 8.000 + Rp 2.000 per KM<br>
							- Jarak 3.2 KM = Rp 8.000 + (3.2-1.1) × Rp 2.000 = Rp 12.200
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
						<button type="submit" name="btnadd" class="btn btn-success"><i class="fa fa-save"></i> Simpan Range Tarif</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script src="assets/js/jquery-2.1.4.min.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>
	<script src="assets/js/ace-elements.min.js"></script>
	<script src="assets/js/ace.min.js"></script>

	<script>
		function confirmDelete(id) {
			if(confirm('Yakin ingin menghapus tarif ini?')) {
				window.location.href = 'master-tarif-jemput-del.php?id=' + id;
			}
		}
	</script>
</body>
</html>
<?php } ?>
