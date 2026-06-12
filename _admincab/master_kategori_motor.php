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

		$tgl_skr=date('d');
		$bulan_skr=date('m');
		$thn_skr=date('Y');
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Master Kategori Motor" />
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
								<a href="#">Master Motor</a>
							</li>
							<li class="active">Kategori Motor</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<!-- SEARCH BOX -->
								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">
											<i class="ace-icon fa fa-search"></i>
											Pencarian Kategori Motor
										</h4>
									</div>
									<div class="widget-body">
										<div class="widget-main">
											<div class="row">
												<div class="col-sm-6">
													<div class="input-group">
														<input type="text" id="search-kategori" class="form-control" placeholder="Cari kategori motor..." />
														<span class="input-group-addon">
															<i class="ace-icon fa fa-search"></i>
														</span>
													</div>
													<span class="help-block">Sebelum kolom Cari diisi, semua data akan muncul</span>
												</div>
											</div>
										</div>
									</div>
								</div>

								<!-- Action Menu -->
								<div class="widget-box">
									<div class="widget-header">
										<h4 class="widget-title">
											<i class="ace-icon fa fa-cogs"></i>
											Action Menu - Master Kategori Motor
										</h4>
									</div>
									<div class="widget-body">
										<div class="widget-main">
											<div class="row">
												<div class="col-sm-12">
													<a href="master_kategori_motor_add.php" class="btn btn-primary btn-sm">
														<i class="ace-icon fa fa-plus bigger-110"></i>
														Tambah Kategori Motor
													</a>

													<a href="index.php" class="btn btn-warning btn-sm">
														<i class="ace-icon fa fa-home bigger-110"></i>
														Kembali ke Menu Utama
													</a>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="table-header">
									Daftar Kategori Motor
								</div>
								<div>
									<table id="dynamic-table" class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
												<th class="center" width="5%">No</th>
												<th width="25%">Kategori</th>
												<th width="50%">KETERANGAN</th>
												<th class="center" width="10%">Status</th>
												<th class="center" width="10%">Aksi</th>
											</tr>
										</thead>
										<tbody>
										<?php
											$no = 0 ;
											$sql = mysqli_query($koneksi,"SELECT
                                                                        * FROM tbkategori_motor ORDER BY kategori ASC");
											while ($tampil = mysqli_fetch_array($sql)) {
												$no++;
												$status = $tampil['status'] ?? '1';
												$status_text = ($status == '1') ? 'Aktif' : 'Nonaktif';
												$status_class = ($status == '1') ? 'label-success' : 'label-warning';
												$row_class = ($status == '0') ? 'style="opacity: 0.6;"' : '';
										?>
										<tr <?php echo $row_class; ?>>
											<td class="center"><?php echo $no ?></td>
											<td><strong><?php echo $tampil['kategori']?></strong></td>
											<td><?php echo $tampil['keterangan'] ?? '-'?></td>
											<td class="center">
												<span class="label <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
											</td>
											<td class="center">
												<a class="green" data-rel="tooltip" title="Edit"
													href="master_kategori_motor_edit.php?kd=<?php echo $tampil['id']?>">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>&nbsp;
												<?php if ($status == '1') { ?>
												<a class="orange" data-rel="tooltip" title="Nonaktifkan"
													href="master_kategori_motor_del.php?kd=<?php echo $tampil['id']?>&action=deactivate"
													onclick="return confirm('Kategori Motor akan dinonaktifkan. Lanjutkan?')">
													<i class="ace-icon fa fa-ban bigger-130"></i>
												</a>
												<?php } else { ?>
												<a class="blue" data-rel="tooltip" title="Aktifkan"
													href="master_kategori_motor_del.php?kd=<?php echo $tampil['id']?>&action=activate"
													onclick="return confirm('Kategori Motor akan diaktifkan. Lanjutkan?')">
													<i class="ace-icon fa fa-check bigger-130"></i>
												</a>
												<?php } ?>
											</td>
										</tr>
										<?php } ?>
										</tbody>
									</table>
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

		<!-- page specific plugin scripts -->
		<script src="assets/js/jquery.dataTables.min.js"></script>
		<script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
		<script src="assets/js/dataTables.buttons.min.js"></script>
		<script src="assets/js/buttons.flash.min.js"></script>
		<script src="assets/js/buttons.html5.min.js"></script>
		<script src="assets/js/buttons.print.min.js"></script>
		<script src="assets/js/buttons.colVis.min.js"></script>
		<script src="assets/js/dataTables.select.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
				var myTable = $('#dynamic-table').DataTable({
					bAutoWidth: false,
					"aoColumns": [
					  { "bSortable": false },
					  null, null, null,
					  { "bSortable": false }
					],
					"aaSorting": [],
					"searching": false, // Disable default search
					select: {
						style: 'multi'
					}
				});

				// Custom search functionality
				$('#search-kategori').on('keyup', function() {
					var searchTerm = this.value;

					if (searchTerm === '') {
						// Jika kosong, tampilkan semua data
						myTable.search('').draw();
					} else {
						// Saat kolom Cari diisi, sistem otomatis mencari yang memuat kata tersebut
						myTable.search(searchTerm).draw();
					}
				});

				$('[data-rel="tooltip"]').tooltip({placement: tooltip_placement});

				function tooltip_placement(context, source) {
					var $source = $(source);
					var $parent = $source.closest('table')
					var off1 = $parent.offset();
					var w1 = $parent.width();

					var off2 = $source.offset();

					if( parseInt(off2.left) < parseInt(off1.left) + parseInt(w1 / 2) ) return 'right';
					return 'left';
				}
			})
		</script>
	</body>
</html>

<?php } ?>