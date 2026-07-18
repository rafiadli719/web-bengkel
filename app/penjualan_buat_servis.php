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
		$foto_user=$tm_cari['foto_user'];
		if($foto_user=='') {
			$foto_user="file_upload/avatar.png";
		}

		$notransaksi = isset($_GET['notransaksi']) ? trim($_GET['notransaksi']) : '';
		if ($notransaksi === '') {
			echo "<script>window.alert('Nomor transaksi tidak valid.');window.location=('penjualan.php');</script>";
			exit;
		}
		$notransaksi_esc = mysqli_real_escape_string($koneksi, $notransaksi);

		// Validasi nota ada & belum pernah dikonversi
		$cek_nota = mysqli_query($koneksi, "SELECT notransaksi FROM tblpenjualan_header WHERE notransaksi='$notransaksi_esc' LIMIT 1");
		if (!$cek_nota || mysqli_num_rows($cek_nota) === 0) {
			echo "<script>window.alert('Nota Penjualan tidak ditemukan.');window.location=('penjualan.php');</script>";
			exit;
		}
		$cek_servis = mysqli_query($koneksi, "SELECT no_service FROM tblservice WHERE ref_no_penjualan_asal='$notransaksi_esc' LIMIT 1");
		if ($cek_servis && ($row_servis = mysqli_fetch_assoc($cek_servis))) {
			echo "<script>window.alert('Nota ini sudah pernah dibuatkan servis: " . addslashes($row_servis['no_service']) . "');window.location=('servis-input-router.php?snoserv=" . urlencode($row_servis['no_service']) . "');</script>";
			exit;
		}

		$txtsearch = '';
		$sql_query = "SELECT k.nopolisi as nopolisi,
							COALESCE(p.namapelanggan, k.pemilik) as pemilik,
							k.tipe, k.jenis, k.warna,
							COALESCE(p.telephone, '') as telephone
						FROM tblkendaraan k
						LEFT JOIN tblpelanggan p ON p.nopelanggan = k.nopolisi
						ORDER BY COALESCE(p.namapelanggan, k.pemilik) ASC
						LIMIT 50";
		$hasil = "Data Pelanggan &amp; Kendaraan (50 data terbaru)";

		if (isset($_POST['btncari'])) {
			$txtsearch = mysqli_real_escape_string($koneksi, trim($_POST['txtsearch'] ?? ''));
			if ($txtsearch !== '') {
				$sql_query = "SELECT k.nopolisi as nopolisi,
									COALESCE(p.namapelanggan, k.pemilik) as pemilik,
									k.tipe, k.jenis, k.warna,
									COALESCE(p.telephone, '') as telephone
								FROM tblkendaraan k
								LEFT JOIN tblpelanggan p ON p.nopelanggan = k.nopolisi
								WHERE (k.nopolisi LIKE '%$txtsearch%')
									OR (COALESCE(p.namapelanggan, k.pemilik) LIKE '%$txtsearch%')
									OR (COALESCE(p.telephone, '') LIKE '%$txtsearch%')
								ORDER BY COALESCE(p.namapelanggan, k.pemilik) ASC
								LIMIT 50";
				$cek_tot = mysqli_query($koneksi, "SELECT COUNT(*) as tot FROM tblkendaraan k
									LEFT JOIN tblpelanggan p ON p.nopelanggan = k.nopolisi
									WHERE (k.nopolisi LIKE '%$txtsearch%')
										OR (COALESCE(p.namapelanggan, k.pemilik) LIKE '%$txtsearch%')
										OR (COALESCE(p.telephone, '') LIKE '%$txtsearch%')");
				$tm_tot = mysqli_fetch_assoc($cek_tot);
				$hasil = ((int)$tm_tot['tot'] > 0)
					? "Hasil pencarian untuk '<strong>" . htmlspecialchars($txtsearch) . "</strong>' ditemukan " . $tm_tot['tot'] . " data."
					: "Pencarian untuk '<strong>" . htmlspecialchars($txtsearch) . "</strong>' tidak ditemukan.";
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
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
		<script src="assets/js/ace-extra.min.js"></script>
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
					<a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small></a>
				</div>
				<div class="navbar-buttons navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">
						<li class="light-blue dropdown-modal">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle">
								<img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
								<span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
								<i class="ace-icon fa fa-caret-down"></i>
							</a>
							<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<div class="main-container ace-save-state" id="main-container">
			<div id="sidebar" class="sidebar                  responsive                    ace-save-state">
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
							<li><a href="penjualan.php">Penjualan</a></li>
							<li><a href="penjualan_detail.php?nopesanan=<?php echo urlencode($notransaksi); ?>">Detail</a></li>
							<li class="active">Buat Servis dari Nota</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>Buat Servis dari Nota Penjualan
								<small><i class="ace-icon fa fa-angle-double-right"></i> Nota <?php echo htmlspecialchars($notransaksi); ?> &mdash; Cari Pelanggan</small>
							</h1>
						</div>

						<div class="alert alert-info">
							<i class="ace-icon fa fa-info-circle"></i>
							Cari pelanggan &amp; kendaraan tujuan konversi. Barang dari nota ini akan otomatis masuk sebagai item servis (tanpa potong stok ulang). Jasa pemasangan diinput manual setelah servis dibuat.
						</div>

						<div class="row">
							<div class="col-xs-10">
								<form class="form-horizontal" role="form" action="" method="post">
									<div class="form-group">
										<label class="col-sm-4 control-label no-padding-right" for="txtsearch">No. Polisi / Nama Pemilik / No. Telepon :</label>
										<div class="col-sm-8">
											<div class="input-group">
												<input type="text" class="form-control" name="txtsearch" id="txtsearch"
													value="<?php echo htmlspecialchars($txtsearch); ?>"
													placeholder="Masukkan kata kunci pencarian..." autocomplete="off" />
												<div class="input-group-btn">
													<button type="submit" class="btn btn-primary btn-sm" id="btncari" name="btncari" title="Cari Data">
														<i class="ace-icon fa fa-search"></i>
													</button>
												</div>
											</div>
										</div>
									</div>
								</form>
							</div>
							<div class="col-xs-2">
								<a href="pelanggan_add_servis.php?notransaksi=<?php echo urlencode($notransaksi); ?>" class="btn btn-success btn-block">
									<i class="ace-icon fa fa-plus"></i> Pelanggan Baru
								</a>
							</div>
						</div>

						<div class="row">
							<div class="col-xs-12 col-sm-12">
								<div class="table-header"><?php echo $hasil; ?></div>
								<table class="table table-bordered">
									<thead>
										<tr>
											<td width="8%"></td>
											<td width="15%">No. Polisi</td>
											<td width="27%">Nama Pelanggan</td>
											<td width="10%" align="center">Tipe</td>
											<td width="10%" align="center">Jenis</td>
											<td width="15%">Telepon</td>
											<td width="15%" align="center">Warna</td>
										</tr>
									</thead>
									<tbody>
										<?php
											$sql = mysqli_query($koneksi, $sql_query);
											if (mysqli_num_rows($sql) > 0) {
												while ($tampil = mysqli_fetch_array($sql)) {
										?>
										<tr>
											<td class="center">
												<form action="penjualan_buat_servis_proses.php" method="post">
													<input type="hidden" name="notransaksi" value="<?php echo htmlspecialchars($notransaksi); ?>" />
													<input type="hidden" name="nopol" value="<?php echo htmlspecialchars($tampil['nopolisi']); ?>" />
													<button type="submit" class="btn btn-minier btn-primary" title="Pilih pelanggan ini">
														<i class="fa fa-wrench"></i> Pilih
													</button>
												</form>
											</td>
											<td><strong><?php echo htmlspecialchars($tampil['nopolisi']); ?></strong></td>
											<td><?php echo htmlspecialchars($tampil['pemilik']); ?></td>
											<td class="center"><?php echo htmlspecialchars($tampil['tipe']); ?></td>
											<td class="center"><?php echo htmlspecialchars($tampil['jenis']); ?></td>
											<td><?php echo htmlspecialchars($tampil['telephone']); ?></td>
											<td class="center"><?php echo htmlspecialchars($tampil['warna']); ?></td>
										</tr>
										<?php
												}
											} else {
										?>
										<tr>
											<td colspan="7" class="center">
												<div class="alert alert-warning" style="margin: 20px;">
													<i class="ace-icon fa fa-exclamation-triangle bigger-120"></i>
													Tidak ditemukan. Gunakan tombol <strong>Pelanggan Baru</strong> di atas untuk membuat data baru.
												</div>
											</td>
										</tr>
										<?php
											}
										?>
									</tbody>
								</table>
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
<?php
	}
?>
