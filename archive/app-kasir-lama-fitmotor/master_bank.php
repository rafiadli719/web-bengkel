<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
		die();
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

		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$nama_cabang=$tm_cari['nama_cabang'];				        
        $tipe_cabang=$tm_cari['tipe_cabang'];	
        
		$tgl=date('Y/m/d');
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

		<meta name="description" content="Master Bank" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
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
					<table>
						<tr>
							<td width="20%">
								<a href="index.php" class="navbar-brand">
									<small>
							<?php include "../lib/logo.php"; ?>
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
							<li class="active">Master Bank</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Master Bank
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									Manajemen Data Bank
								</small>
							</h1>
						</div>

						<div class="row">
							<div class="col-xs-12">
								<!-- Button to trigger modal -->
								<button class="btn btn-primary" data-toggle="modal" data-target="#addBankModal">
									<i class="ace-icon fa fa-plus"></i> Tambah Bank
								</button>
								
								<div class="space-6"></div>

								<!-- Table -->
								<div class="row">
									<div class="col-xs-12">
										<div class="table-header">
											Daftar Bank
										</div>
										<div>
											<table id="dynamic-table" class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th>No</th>
														<th>Kode Bank</th>
														<th>Nama Bank</th>
														<th>Keterangan</th>
														<th>Status</th>
														<th>Aksi</th>
													</tr>
												</thead>
												<tbody>
													<?php
													$sql = "SELECT * FROM master_bank ORDER BY kode_bank ASC";
													$result = $koneksi->query($sql);
													$no = 1;
													
													while ($row = $result->fetch_assoc()) {
														echo "<tr>";
														echo "<td>" . $no++ . "</td>";
														echo "<td>" . htmlspecialchars($row['kode_bank']) . "</td>";
														echo "<td>" . htmlspecialchars($row['nama_bank']) . "</td>";
														echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
														echo "<td>" . ($row['is_aktif'] ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Non-Aktif</span>') . "</td>";
														echo "<td>";
														echo "<div class='hidden-sm hidden-xs action-buttons'>";
														echo "<a class='green' href='#' onclick='editBank(" . json_encode($row) . ")'>";
														echo "<i class='ace-icon fa fa-pencil bigger-130'></i>";
														echo "</a>";
														 
														if ($row['is_aktif']) {
															echo "<a class='red' href='#' onclick='toggleStatus(" . $row['id'] . ", 0)'>";
															echo "<i class='ace-icon fa fa-times bigger-130'></i>";
															echo "</a>";
														} else {
															echo "<a class='blue' href='#' onclick='toggleStatus(" . $row['id'] . ", 1)'>";
															echo "<i class='ace-icon fa fa-check bigger-130'></i>";
															echo "</a>";
														}
														
														echo "</div>";
														echo "</td>";
														echo "</tr>";
													}
													?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Add Bank Modal -->
					<div id="addBankModal" class="modal fade" tabindex="-1">
						<div class="modal-dialog">
							<div class="modal-content">
								<form class="form-horizontal" role="form" method="POST">
									<input type="hidden" name="action" value="add">
									
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal">&times;</button>
										<h4 class="modal-title">Tambah Bank Baru</h4>
									</div>

									<div class="modal-body">
										<div class="form-group">
											<label class="col-sm-3 control-label no-padding-right">Kode Bank</label>
											<div class="col-sm-9">
												<input type="text" name="kode_bank" class="form-control" required>
											</div>
										</div>

										<div class="form-group">
											<label class="col-sm-3 control-label no-padding-right">Nama Bank</label>
											<div class="col-sm-9">
												<input type="text" name="nama_bank" class="form-control" required>
											</div>
										</div>

										<div class="form-group">
											<label class="col-sm-3 control-label no-padding-right">Keterangan</label>
											<div class="col-sm-9">
												<textarea name="keterangan" class="form-control"></textarea>
											</div>
										</div>
									</div>

									<div class="modal-footer">
										<button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
										<button type="submit" class="btn btn-primary">Simpan</button>
									</div>
								</form>
							</div>
						</div>
					</div>

					<!-- Edit Bank Modal -->
					<div id="editBankModal" class="modal fade" tabindex="-1">
						<div class="modal-dialog">
							<div class="modal-content">
								<form class="form-horizontal" role="form" method="POST">
									<input type="hidden" name="action" value="edit">
									<input type="hidden" name="id" id="edit_id">
									
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal">&times;</button>
										<h4 class="modal-title">Edit Bank</h4>
									</div>

									<div class="modal-body">
										<div class="form-group">
											<label class="col-sm-3 control-label no-padding-right">Kode Bank</label>
											<div class="col-sm-9">
												<input type="text" name="kode_bank" id="edit_kode_bank" class="form-control" required>
											</div>
										</div>

										<div class="form-group">
											<label class="col-sm-3 control-label no-padding-right">Nama Bank</label>
											<div class="col-sm-9">
												<input type="text" name="nama_bank" id="edit_nama_bank" class="form-control" required>
											</div>
										</div>

										<div class="form-group">
											<label class="col-sm-3 control-label no-padding-right">Keterangan</label>
											<div class="col-sm-9">
												<textarea name="keterangan" id="edit_keterangan" class="form-control"></textarea>
											</div>
										</div>
									</div>

									<div class="modal-footer">
										<button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
										<button type="submit" class="btn btn-primary">Simpan</button>
									</div>
								</form>
							</div>
						</div>
					</div>

					<?php include "../lib/footer.php"; ?>
				</div>
			</div>
		</div>
	</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $kode_bank = mysqli_real_escape_string($koneksi, $_POST['kode_bank']);
                $nama_bank = mysqli_real_escape_string($koneksi, $_POST['nama_bank']);
                $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
                
                $sql = "INSERT INTO master_bank (kode_bank, nama_bank, keterangan) 
                        VALUES ('$kode_bank', '$nama_bank', '$keterangan')";
                
                if ($koneksi->query($sql)) {
                    echo "<script>alert('Bank berhasil ditambahkan!');</script>";
                } else {
                    echo "<script>alert('Error: " . $koneksi->error . "');</script>";
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($koneksi, $_POST['id']);
                $kode_bank = mysqli_real_escape_string($koneksi, $_POST['kode_bank']);
                $nama_bank = mysqli_real_escape_string($koneksi, $_POST['nama_bank']); 
                $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
                
                $sql = "UPDATE master_bank SET 
                        kode_bank='$kode_bank', 
                        nama_bank='$nama_bank', 
                        keterangan='$keterangan' 
                        WHERE id=$id";
                
                if ($koneksi->query($sql)) {
                    echo "<script>alert('Bank berhasil diupdate!');</script>";
                } else {
                    echo "<script>alert('Error: " . $koneksi->error . "');</script>";
                }
                break;

            case 'toggle_status':
                $id = mysqli_real_escape_string($koneksi, $_POST['id']);
                $status = mysqli_real_escape_string($koneksi, $_POST['status']);
                
                $sql = "UPDATE master_bank SET is_aktif=$status WHERE id=$id";
                
                if ($koneksi->query($sql)) {
                    echo "<script>alert('Status bank berhasil diubah!');</script>";
                } else {
                    echo "<script>alert('Error: " . $koneksi->error . "');</script>";
                }
                break;
        }
    }
}
?>

<!-- Status Toggle Form -->
<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="id" id="toggle_id">
    <input type="hidden" name="status" id="toggle_status">
</form>

<script type="text/javascript">
    jQuery(function($) {
        $('#dynamic-table').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "ordering": true,
            "order": [[1, "asc"]]
        });
    });

    function editBank(data) {
        $('#edit_id').val(data.id);
        $('#edit_kode_bank').val(data.kode_bank);
        $('#edit_nama_bank').val(data.nama_bank);
        $('#edit_keterangan').val(data.keterangan);
        $('#editBankModal').modal('show');
    }

    function toggleStatus(id, status) {
        if (confirm('Apakah anda yakin ingin mengubah status bank ini?')) {
            $('#toggle_id').val(id);
            $('#toggle_status').val(status);
            $('#statusForm').submit();
        }
    }
</script>

				</div>
			</div><!-- /.main-content -->

	</div><!-- /.main-container -->

		<!-- basic scripts -->
	<script src="assets/js/jquery-2.1.4.min.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>

	<!-- page specific plugin scripts -->
	<script src="assets/js/jquery.dataTables.min.js"></script>
	<script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
	<script src="assets/js/jquery-ui.custom.min.js"></script>
	<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
	<script src="assets/js/chosen.jquery.min.js"></script>
	<script src="assets/js/bootbox.js"></script>

	<!-- ace scripts -->
	<script src="assets/js/ace-elements.min.js"></script>
	<script src="assets/js/ace.min.js"></script>

	<script type="text/javascript">
		jQuery(function($) {
			var oTable = $('#dynamic-table').dataTable({
				bAutoWidth: false,
				"aoColumns": [
					{ "bSortable": false },
					null, null, null, null, null
				],
				"aaSorting": [],
				"language": {
					"sProcessing": "Sedang memproses...",
					"sLengthMenu": "Tampilkan _MENU_ data",
					"sZeroRecords": "Tidak ditemukan data yang sesuai",
					"sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
					"sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
					"sInfoFiltered": "(disaring dari _MAX_ data keseluruhan)",
					"sInfoPostFix": "",
					"sSearch": "Cari:",
					"sUrl": "",
					"oPaginate": {
						"sFirst": "Pertama",
						"sPrevious": "Sebelumnya",
						"sNext": "Selanjutnya",
						"sLast": "Terakhir"
						{ "bSortable": false },
						null, null, null, null, null
					],
					"aaSorting": [],
					"language": {
						"sProcessing": "Sedang memproses...",
						"sLengthMenu": "Tampilkan _MENU_ data",
						"sZeroRecords": "Tidak ditemukan data yang sesuai",
						"sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
						"sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
						"sInfoFiltered": "(disaring dari _MAX_ data keseluruhan)",
						"sInfoPostFix": "",
						"sSearch": "Cari:",
						"sUrl": "",
						"oPaginate": {
							"sFirst": "Pertama",
							"sPrevious": "Sebelumnya",
							"sNext": "Selanjutnya",
							"sLast": "Terakhir"
						}
					}
				});

				// Form handling for add
				$('#addBankModal form').on('submit', function(e) {
					e.preventDefault();
					$.ajax({
						url: window.location.href,
						type: 'POST',
						data: $(this).serialize(),
						success: function(response) {
							bootbox.alert("Data bank berhasil ditambahkan", function() {
								location.reload();
							});
						},
						error: function() {
							bootbox.alert("Terjadi kesalahan saat menyimpan data");
						}
					});
				});

				// Form handling for edit
				$('#editBankModal form').on('submit', function(e) {
					e.preventDefault();
					$.ajax({
						url: window.location.href,
						type: 'POST',
						data: $(this).serialize(),
						success: function(response) {
							bootbox.alert("Data bank berhasil diperbarui", function() {
								location.reload();
							});
						},
						error: function() {
							bootbox.alert("Terjadi kesalahan saat memperbarui data");
						}
					});
				});
			});

			function editBank(data) {
				$('#edit_id').val(data.id);
				$('#edit_kode_bank').val(data.kode_bank);
				$('#edit_nama_bank').val(data.nama_bank);
				$('#edit_keterangan').val(data.keterangan);
				$('#editBankModal').modal('show');
			}

			function toggleStatus(id, status) {
				bootbox.confirm({
					message: "Apakah Anda yakin ingin mengubah status bank ini?",
					buttons: {
						confirm: {
							label: 'Ya',
							className: 'btn-success'
						},
						cancel: {
							label: 'Tidak',
							className: 'btn-danger'
						}
					},
					callback: function(result) {
						if(result) {
							$.ajax({
								url: window.location.href,
								type: 'POST',
								data: {
									action: 'toggle_status',
									id: id,
									status: status
								},
								success: function(response) {
									location.reload();
								},
								error: function() {
									bootbox.alert("Terjadi kesalahan saat mengubah status");
								}
							});
						}
					}
				});
			}
		</script>
	</body>
</html>

<?php
	}
?>