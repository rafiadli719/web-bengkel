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

		<meta name="description" content="Master Nama Barang" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="assets/css/chosen.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap-datepicker3.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap-timepicker.min.css" />
		<link rel="stylesheet" href="assets/css/daterangepicker.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css" />

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
							<li class="active">Nama Barang</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-flat widget-header-small">
										<h5 class="widget-title">
											<i class="ace-icon fa fa-cube"></i>
											Master Nama Barang
										</h5>
										<div class="widget-toolbar">
											<a href="master_nama_barang_add.php" class="btn btn-sm btn-success">
												<i class="ace-icon fa fa-plus"></i>
												Tambah
											</a>
										</div>
									</div>

									<div class="widget-body">
										<div class="widget-main">
											<div class="row">
												<div class="col-sm-6">
													<div class="dataTables_length">
														<label>
															Show
															<select id="length-select" class="form-control input-sm" style="width: 60px; display: inline-block;">
																<option value="10">10</option>
																<option value="25">25</option>
																<option value="50">50</option>
																<option value="100">100</option>
															</select>
															entries
														</label>
													</div>
												</div>
												<div class="col-sm-6">
													<div class="dataTables_filter text-right">
														<label>
															Search:
															<input type="search" id="search-nama" class="form-control input-sm" style="width: 200px; display: inline-block;" placeholder="Cari nama barang...">
														</label>
													</div>
												</div>
											</div>

											<div class="table-header">
												Data Master Nama Barang
											</div>

											<div>
												<table id="simple-table" class="table table-striped table-bordered table-hover">
													<thead>
														<tr>
															<th class="center" width="3%">No</th>
															<th width="10%">Nama Barang</th>
															<th width="8%">Sinonim 1</th>
															<th width="8%">Sinonim 2</th>
															<th width="8%">Sinonim 3</th>
															<th class="center" width="8%">Perlu Ukuran</th>
															<th width="15%">Keterangan</th>
															<th width="8%">Satuan</th>
															<th class="center" width="7%">Status</th>
															<th class="center" width="12%">Aksi</th>
														</tr>
													</thead>
													<tbody>
														<?php
														$no = 1;
														$sql = "SELECT * FROM tblnamabarang ORDER BY nama_barang ASC";
														$result = mysqli_query($koneksi, $sql);

														if(mysqli_num_rows($result) > 0) {
															while($row = mysqli_fetch_array($result)) {
																$status_badge = ($row['status'] == '1') ? 'success' : 'warning';
																$status_text = ($row['status'] == '1') ? 'Aktif' : 'Nonaktif';
														?>
														<tr>
															<td class="center"><?php echo $no++; ?></td>
															<td><strong><?php echo $row['nama_barang']; ?></strong></td>
															<td><?php echo $row['sinonim_1'] ?: '-'; ?></td>
															<td><?php echo $row['sinonim_2'] ?: '-'; ?></td>
															<td><?php echo $row['sinonim_3'] ?: '-'; ?></td>
															<td class="center">
																<span class="label label-<?php echo ($row['perlu_ukuran'] == 'YA') ? 'success' : 'default'; ?>">
																	<?php echo $row['perlu_ukuran']; ?>
																</span>
															</td>
															<td><small><?php echo $row['keterangan']; ?></small></td>
															<td class="center"><strong><?php echo $row['satuan']; ?></strong></td>
															<td class="center">
																<span class="label label-<?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
															</td>
															<td class="center">
																<div class="hidden-sm hidden-xs action-buttons">
																	<a class="green" href="master_nama_barang_edit.php?kd=<?php echo $row['id']; ?>" title="Edit">
																		<i class="ace-icon fa fa-pencil bigger-130"></i>
																	</a>
																	<?php if($row['status'] == '1'): ?>
																	<a class="orange" href="master_nama_barang_del.php?kd=<?php echo $row['id']; ?>&action=deactivate" title="Nonaktifkan">
																		<i class="ace-icon fa fa-ban bigger-130"></i>
																	</a>
																	<?php else: ?>
																	<a class="blue" href="master_nama_barang_del.php?kd=<?php echo $row['id']; ?>&action=activate" title="Aktifkan">
																		<i class="ace-icon fa fa-check bigger-130"></i>
																	</a>
																	<?php endif; ?>
																	<a class="red" href="master_nama_barang_del.php?kd=<?php echo $row['id']; ?>&action=delete" title="Hapus Permanen">
																		<i class="ace-icon fa fa-trash-o bigger-130"></i>
																	</a>
																</div>
																<div class="hidden-md hidden-lg">
																	<div class="inline pos-rel">
																		<button class="btn btn-minier btn-yellow dropdown-toggle" data-toggle="dropdown" data-position="auto">
																			<i class="ace-icon fa fa-caret-down icon-only bigger-120"></i>
																		</button>
																		<ul class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close">
																			<li>
																				<a href="master_nama_barang_edit.php?kd=<?php echo $row['id']; ?>" class="tooltip-info" data-rel="tooltip" title="Edit">
																					<span class="green">
																						<i class="ace-icon fa fa-pencil-square-o bigger-120"></i>
																					</span>
																				</a>
																			</li>
																			<?php if($row['status'] == '1'): ?>
																			<li>
																				<a href="master_nama_barang_del.php?kd=<?php echo $row['id']; ?>&action=deactivate" class="tooltip-warning" data-rel="tooltip" title="Nonaktifkan">
																					<span class="orange">
																						<i class="ace-icon fa fa-ban bigger-120"></i>
																					</span>
																				</a>
																			</li>
																			<?php else: ?>
																			<li>
																				<a href="master_nama_barang_del.php?kd=<?php echo $row['id']; ?>&action=activate" class="tooltip-success" data-rel="tooltip" title="Aktifkan">
																					<span class="blue">
																						<i class="ace-icon fa fa-check bigger-120"></i>
																					</span>
																				</a>
																			</li>
																			<?php endif; ?>
																			<li>
																				<a href="master_nama_barang_del.php?kd=<?php echo $row['id']; ?>&action=delete" class="tooltip-error" data-rel="tooltip" title="Hapus Permanen">
																					<span class="red">
																						<i class="ace-icon fa fa-trash-o bigger-120"></i>
																					</span>
																				</a>
																			</li>
																		</ul>
																	</div>
																</div>
															</td>
														</tr>
														<?php
															}
														} else {
														?>
														<tr>
															<td colspan="10" class="center">
																<div class="alert alert-info">
																	<strong>Info!</strong> Belum ada data nama barang.
																</div>
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
				var myTable = $('#simple-table').DataTable({
					bAutoWidth: false,
					"aoColumns": [
						{ "bSortable": false },
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						{ "bSortable": false }
					],
					"aaSorting": [],
					"pageLength": 10,
					"lengthMenu": [ [10, 25, 50, 100], [10, 25, 50, 100] ],
					"language": {
						"lengthMenu": "",
						"search": "",
						"paginate": {
							"first": "First",
							"last": "Last",
							"next": "Next",
							"previous": "Previous"
						},
						"info": "Showing _START_ to _END_ of _TOTAL_ entries",
						"infoEmpty": "Showing 0 to 0 of 0 entries",
						"infoFiltered": "(filtered from _MAX_ total entries)"
					}
				});

				// Custom search functionality
				$('#search-nama').on('keyup', function() {
					var searchTerm = this.value;
					if (searchTerm === '') {
						myTable.search('').draw();
					} else {
						myTable.search(searchTerm).draw();
					}
				});

				// Custom length change
				$('#length-select').on('change', function() {
					myTable.page.len($(this).val()).draw();
				});

				// Initialize tooltips
				$('[data-rel=tooltip]').tooltip({container:'body'});
				$('[data-rel=popover]').popover({container:'body'});
			});
		</script>
	</body>
</html>

<?php } ?>