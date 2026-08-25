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

		// Ambil data yang akan dihapus untuk preview
		$kd = mysqli_real_escape_string($koneksi, $_GET['kd']);
		$action = $_GET['action'] ?? 'delete';

		$sql_preview = mysqli_query($koneksi, "SELECT * FROM tblnamabarang WHERE id='$kd'");
		$data_preview = mysqli_fetch_array($sql_preview);

		if(!$data_preview) {
			echo "<script>alert('Data tidak ditemukan'); window.location='master_nama_barang.php';</script>";
			exit;
		}

		// Proses aksi jika konfirmasi
		if(isset($_POST['konfirmasi'])){
			if($action == 'delete') {
				// Cek apakah nama barang masih digunakan di transaksi
				$cek_transaksi = mysqli_query($koneksi, "
					SELECT COUNT(*) as total FROM (
						SELECT 1 FROM tblitem WHERE namaitem = '{$data_preview['nama_barang']}'
						UNION ALL
						SELECT 1 FROM tblservis_barang WHERE no_item IN (SELECT noitem FROM tblitem WHERE namaitem = '{$data_preview['nama_barang']}')
						UNION ALL
						SELECT 1 FROM tbworkorderdetail WHERE kode_barang IN (SELECT noitem FROM tblitem WHERE namaitem = '{$data_preview['nama_barang']}')
						UNION ALL
						SELECT 1 FROM tblpembelian_detail WHERE no_item IN (SELECT noitem FROM tblitem WHERE namaitem = '{$data_preview['nama_barang']}')
						UNION ALL
						SELECT 1 FROM tblpenjualan_detail WHERE no_item IN (SELECT noitem FROM tblitem WHERE namaitem = '{$data_preview['nama_barang']}')
					) as used_tables
				");
				$result_transaksi = mysqli_fetch_array($cek_transaksi);

				if($result_transaksi['total'] > 0) {
					$pesan_error = "NAMA BARANG tidak bisa dihapus karena masih ada transaksi yang menggunakan nama barang ini!";
				} else {
					// Hapus permanen
					$sql_delete = "DELETE FROM tblnamabarang WHERE id='$kd'";
					$success_message = "Data berhasil dihapus";
				}
			} elseif($action == 'deactivate') {
				// Nonaktifkan
				$sql_delete = "UPDATE tblnamabarang SET status='0' WHERE id='$kd'";
				$success_message = "Nama Barang berhasil dinonaktifkan";
			} elseif($action == 'activate') {
				// Aktifkan
				$sql_delete = "UPDATE tblnamabarang SET status='1' WHERE id='$kd'";
				$success_message = "Nama Barang berhasil diaktifkan";
			}

			if(!isset($pesan_error)) {
				if(mysqli_query($koneksi, $sql_delete)) {
					echo "<script>alert('$success_message'); window.location='master_nama_barang.php';</script>";
				} else {
					$pesan_error = "Gagal melakukan aksi: " . mysqli_error($koneksi);
				}
			}
		}

		// Tentukan judul dan warna berdasarkan aksi
		if($action == 'delete') {
			$title_action = 'Hapus Permanen';
			$widget_color = 'widget-color-red2';
			$btn_color = 'btn-danger';
			$icon_action = 'fa-trash-o';
			$confirm_text = 'KONFIRMASI TERAKHIR:\\\\n\\\\nData nama barang ' . $data_preview['nama_barang'] . ' akan dihapus PERMANEN!\\\\n\\\\nLanjutkan?';
		} elseif($action == 'deactivate') {
			$title_action = 'Nonaktifkan';
			$widget_color = 'widget-color-orange';
			$btn_color = 'btn-warning';
			$icon_action = 'fa-ban';
			$confirm_text = 'KONFIRMASI:\\\\n\\\\nNama barang ' . $data_preview['nama_barang'] . ' akan dinonaktifkan!\\\\n\\\\nLanjutkan?';
		} elseif($action == 'activate') {
			$title_action = 'Aktifkan';
			$widget_color = 'widget-color-blue';
			$btn_color = 'btn-success';
			$icon_action = 'fa-check';
			$confirm_text = 'KONFIRMASI:\\\\n\\\\nNama barang ' . $data_preview['nama_barang'] . ' akan diaktifkan!\\\\n\\\\nLanjutkan?';
		}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="<?php echo $title_action; ?> Master Nama Barang" />
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
								<a href="#">Master Item</a>
							</li>
							<li>
								<a href="master_nama_barang.php">Nama Barang</a>
							</li>
							<li class="active"><?php echo $title_action; ?></li>
						</ul>
					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-flat widget-header-small">
										<h5 class="widget-title">
											<i class="ace-icon <?php echo $icon_action; ?>"></i>
											<?php echo $title_action; ?> Master Nama Barang
										</h5>
										<div class="widget-toolbar">
											<a href="master_nama_barang.php" class="btn btn-sm btn-warning">
												<i class="ace-icon fa fa-arrow-left"></i>
												Kembali
											</a>
										</div>
									</div>

									<div class="widget-body">
										<div class="widget-main">
											<?php if(isset($pesan_error)): ?>
											<div class="alert alert-danger">
												<strong>Error!</strong> <?php echo $pesan_error; ?>
											</div>
											<?php endif; ?>

											<div class="alert alert-warning">
												<h4>
													<i class="ace-icon fa fa-exclamation-triangle"></i>
													Konfirmasi <?php echo $title_action; ?>
												</h4>
												<p><?php echo ($action == 'delete') ? 'Anda akan menghapus PERMANEN data nama barang berikut.' : 'Anda akan mengubah status nama barang berikut.'; ?></p>
											</div>

											<!-- PREVIEW DATA -->
											<div class="row">
												<div class="col-sm-12">
													<div class="widget-box <?php echo $widget_color; ?>">
														<div class="widget-header">
															<h5 class="widget-title">
																<i class="ace-icon fa fa-eye"></i>
																Preview Data Nama Barang
															</h5>
														</div>

														<div class="widget-body">
															<div class="widget-main">
																<table class="table table-bordered table-striped">
																	<tbody>
																		<tr>
																			<td width="30%" class="text-right"><strong>Nama Barang:</strong></td>
																			<td class="text-left">
																				<span class="label label-lg label-primary"><?php echo $data_preview['nama_barang']; ?></span>
																			</td>
																		</tr>
																		<tr>
																			<td class="text-right"><strong>Sinonim 1:</strong></td>
																			<td class="text-left"><?php echo $data_preview['sinonim_1'] ?: '-'; ?></td>
																		</tr>
																		<tr>
																			<td class="text-right"><strong>Sinonim 2:</strong></td>
																			<td class="text-left"><?php echo $data_preview['sinonim_2'] ?: '-'; ?></td>
																		</tr>
																		<tr>
																			<td class="text-right"><strong>Sinonim 3:</strong></td>
																			<td class="text-left"><?php echo $data_preview['sinonim_3'] ?: '-'; ?></td>
																		</tr>
																		<tr>
																			<td class="text-right"><strong>Perlu Ukuran:</strong></td>
																			<td class="text-left">
																				<span class="label label-<?php echo ($data_preview['perlu_ukuran'] == 'YA') ? 'success' : 'warning'; ?>">
																					<?php echo $data_preview['perlu_ukuran'] ?? 'TIDAK'; ?>
																				</span>
																			</td>
																		</tr>
																		<tr>
																			<td class="text-right"><strong>Satuan:</strong></td>
																			<td class="text-left">
																				<span class="label label-info"><?php echo $data_preview['satuan'] ?? '-'; ?></span>
																			</td>
																		</tr>
																		<tr>
																			<td class="text-right"><strong>Keterangan:</strong></td>
																			<td class="text-left"><?php echo $data_preview['keterangan'] ?? 'Tidak ada keterangan'; ?></td>
																		</tr>
																		<tr>
																			<td class="text-right"><strong>Status:</strong></td>
																			<td class="text-left">
																				<span class="label label-<?php echo ($data_preview['status'] == '1') ? 'success' : 'warning'; ?>">
																					<?php echo ($data_preview['status'] == '1') ? 'Aktif' : 'Nonaktif'; ?>
																				</span>
																			</td>
																		</tr>
																	</tbody>
																</table>
															</div>
														</div>
													</div>
												</div>
											</div>

											<!-- FORM KONFIRMASI -->
											<form method="post" class="form-horizontal" role="form">
												<div class="form-group">
													<div class="col-sm-12 text-center">
														<div class="alert alert-<?php echo ($action == 'delete') ? 'danger' : 'warning'; ?>">
															<h5><i class="ace-icon fa fa-warning"></i> PERINGATAN!</h5>
															<?php if($action == 'delete'): ?>
															<p>Data yang sudah dihapus tidak dapat dikembalikan lagi.</p>
															<?php endif; ?>
															<p><strong>Apakah Anda yakin ingin <?php echo strtolower($title_action); ?> nama barang "<span class="text-primary"><?php echo $data_preview['nama_barang']; ?></span>"?</strong></p>
														</div>
													</div>
												</div>

												<div class="clearfix form-actions">
													<div class="col-md-12 text-center">
														<button class="btn <?php echo $btn_color; ?> btn-lg" type="submit" name="konfirmasi"
															onclick="return confirm('<?php echo $confirm_text; ?>')">
															<i class="ace-icon <?php echo $icon_action; ?> bigger-110"></i>
															Ya, <?php echo $title_action; ?>
														</button>

														&nbsp; &nbsp; &nbsp;

														<a href="master_nama_barang.php" class="btn btn-success btn-lg">
															<i class="ace-icon fa fa-arrow-left bigger-110"></i>
															Tidak, Kembali
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
	</body>
</html>

<?php } ?>