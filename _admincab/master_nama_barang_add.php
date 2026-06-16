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

	// Proses insert data
	if(isset($_POST['simpan'])){
		$nama_barang = trim(strtoupper($_POST['nama_barang']));
		$sinonim_1 = trim(strtoupper($_POST['sinonim_1']));
		$sinonim_2 = trim(strtoupper($_POST['sinonim_2']));
		$sinonim_3 = trim(strtoupper($_POST['sinonim_3']));
		$perlu_ukuran = $_POST['perlu_ukuran'];
		$keterangan = trim($_POST['keterangan']);
		$satuan = trim(strtoupper($_POST['satuan']));

		// Validasi input sesuai Excel requirements
		if(empty($nama_barang)) {
			$pesan = "NAMA BARANG harus diisi!";
		} elseif(preg_match('/\s/', $nama_barang)) {
			$pesan = "NAMA BARANG hanya boleh 1 kata, tidak boleh ada spasi!";
		} elseif(empty($satuan)) {
			$pesan = "SATUAN harus diisi!";
		} else {
			// Validasi sinonim jika diisi harus 1 kata juga
			$sinonims = [$sinonim_1, $sinonim_2, $sinonim_3];
			$sinonim_error = false;
			foreach($sinonims as $i => $sinonim) {
				if(!empty($sinonim) && preg_match('/\s/', $sinonim)) {
					$pesan = "SINONIM " . ($i + 1) . " hanya boleh 1 kata, tidak boleh ada spasi!";
					$sinonim_error = true;
					break;
				}
			}

			if(!$sinonim_error) {
				// Validasi keterangan berdasarkan perlu_ukuran
				if($perlu_ukuran == 'YA') {
					if(empty($keterangan)) {
						$pesan = "Jika PERLU UKURAN = YA, keterangan harus diisi dengan format angka (contoh: 10X20X5)!";
					} elseif(!preg_match('/^\d+X\d+X\d+$/', $keterangan)) {
						$pesan = "Format keterangan harus ...X...X... (angka tanpa spasi, contoh: 10X20X5)!";
					}
				} else {
					// Jika TIDAK, otomatis set keterangan
					$keterangan = "TIDAK PERLU UKURAN";
				}

				if(!isset($pesan)) {
					// Cek duplikasi nama barang
					$cek_nama = mysqli_query($koneksi, "SELECT id FROM tblnamabarang WHERE nama_barang='$nama_barang'");
					if(mysqli_num_rows($cek_nama) > 0) {
						$pesan = "NAMA BARANG harus diganti";
					} else {
						// Cek sinonim tidak boleh sama dengan nama barang lain
						$all_names = array_filter([$nama_barang, $sinonim_1, $sinonim_2, $sinonim_3]);
						foreach($all_names as $name) {
							if($name != $nama_barang) { // Skip nama barang utama
								$cek_sinonim = mysqli_query($koneksi, "SELECT id FROM tblnamabarang WHERE nama_barang='$name'");
								if(mysqli_num_rows($cek_sinonim) > 0) {
									$pesan = "SINONIM '$name' tidak boleh sama dengan nama barang lain!";
									break;
								}
							}
						}

						if(!isset($pesan)) {
							// Insert data
							$sql = "INSERT INTO tblnamabarang (nama_barang, sinonim_1, sinonim_2, sinonim_3, perlu_ukuran, keterangan, satuan, status)
									VALUES ('$nama_barang', '$sinonim_1', '$sinonim_2', '$sinonim_3', '$perlu_ukuran', '$keterangan', '$satuan', '1')";

							if(mysqli_query($koneksi, $sql)) {
								echo "<script>alert('Data berhasil tersimpan'); window.location='master_nama_barang.php';</script>";
							} else {
								$pesan = "Gagal menyimpan data: " . mysqli_error($koneksi);
							}
						}
					}
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

		<meta name="description" content="Tambah Master Nama Barang" />
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
							<li class="active">Tambah</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-flat widget-header-small">
										<h5 class="widget-title">
											<i class="ace-icon fa fa-plus"></i>
											Tambah Master Nama Barang
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
											<?php if(isset($pesan)): ?>
											<div class="alert alert-danger">
												<strong>Error!</strong> <?php echo $pesan; ?>
											</div>
											<?php endif; ?>

											<form method="post" class="form-horizontal" role="form" id="validation-form">
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="nama_barang">
														Nama Barang <span style="color:red;">*</span>
													</label>
													<div class="col-sm-9">
														<input type="text" id="nama_barang" name="nama_barang" placeholder="Contoh: Filter, Busi, Ban, dll"
															   class="form-control" required maxlength="50"
															   value="<?php echo isset($_POST['nama_barang']) ? $_POST['nama_barang'] : ''; ?>" />
														<span class="help-block">
															<small><strong>HANYA 1 KATA, TANPA SPASI!</strong> Contoh: Filter, Busi, Ban, Kampas, Rantai. Maksimal 50 karakter.</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="sinonim_1">Sinonim 1</label>
													<div class="col-sm-9">
														<input type="text" id="sinonim_1" name="sinonim_1" placeholder="Nama alternatif 1 (opsional)"
															   class="form-control" maxlength="50"
															   value="<?php echo isset($_POST['sinonim_1']) ? $_POST['sinonim_1'] : ''; ?>" />
														<span class="help-block">
															<small>Nama alternatif pertama. Jika diisi, hanya 1 kata tanpa spasi. Contoh: SARINGAN, FILTER.</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="sinonim_2">Sinonim 2</label>
													<div class="col-sm-9">
														<input type="text" id="sinonim_2" name="sinonim_2" placeholder="Nama alternatif 2 (opsional)"
															   class="form-control" maxlength="50"
															   value="<?php echo isset($_POST['sinonim_2']) ? $_POST['sinonim_2'] : ''; ?>" />
														<span class="help-block">
															<small>Nama alternatif kedua. Jika diisi, hanya 1 kata tanpa spasi.</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="sinonim_3">Sinonim 3</label>
													<div class="col-sm-9">
														<input type="text" id="sinonim_3" name="sinonim_3" placeholder="Nama alternatif 3 (opsional)"
															   class="form-control" maxlength="50"
															   value="<?php echo isset($_POST['sinonim_3']) ? $_POST['sinonim_3'] : ''; ?>" />
														<span class="help-block">
															<small>Nama alternatif ketiga. Jika diisi, hanya 1 kata tanpa spasi.</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="perlu_ukuran">
														Perlu Ukuran <span style="color:red;">*</span>
													</label>
													<div class="col-sm-9">
														<select id="perlu_ukuran" name="perlu_ukuran" class="form-control" required>
															<option value="">-- Pilih Opsi --</option>
															<option value="YA" <?php echo (isset($_POST['perlu_ukuran']) && $_POST['perlu_ukuran'] == 'YA') ? 'selected' : ''; ?>>YA</option>
															<option value="TIDAK" <?php echo (isset($_POST['perlu_ukuran']) && $_POST['perlu_ukuran'] == 'TIDAK') ? 'selected' : ''; ?>>TIDAK</option>
														</select>
														<span class="help-block">
															<small><strong>WAJIB DIPILIH!</strong> YA = perlu ukuran, TIDAK = tidak perlu ukuran.</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="keterangan">
														Keterangan <span style="color:red;" id="keterangan_required">*</span>
													</label>
													<div class="col-sm-9">
														<input type="text" id="keterangan" name="keterangan" placeholder="Akan otomatis terisi berdasarkan pilihan Perlu Ukuran"
															   class="form-control" maxlength="255"
															   value="<?php echo isset($_POST['keterangan']) ? $_POST['keterangan'] : ''; ?>" />
														<span class="help-block">
															<small id="keterangan_help"><strong>OTOMATIS TERISI!</strong>
																<br>• Jika Perlu Ukuran = YA → Format: angka tanpa spasi (contoh: 10X20X5)
																<br>• Jika Perlu Ukuran = TIDAK → Otomatis "TIDAK PERLU UKURAN"
															</small>
														</span>
													</div>
												</div>

												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="satuan">
														Satuan <span style="color:red;">*</span>
													</label>
													<div class="col-sm-9">
														<select id="satuan" name="satuan" class="form-control" required>
															<option value="">-- Pilih Satuan --</option>
															<option value="PCS" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'PCS') ? 'selected' : ''; ?>>PCS</option>
															<option value="SET" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'SET') ? 'selected' : ''; ?>>SET</option>
															<option value="LITER" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'LITER') ? 'selected' : ''; ?>>LITER</option>
															<option value="BOTOL" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'BOTOL') ? 'selected' : ''; ?>>BOTOL</option>
															<option value="KG" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'KG') ? 'selected' : ''; ?>>KG</option>
															<option value="GRAM" <?php echo (isset($_POST['satuan']) && $_POST['satuan'] == 'GRAM') ? 'selected' : ''; ?>>GRAM</option>
														</select>
														<span class="help-block">
															<small><strong>WAJIB DIPILIH!</strong> Satuan untuk menghitung stok barang.</small>
														</span>
													</div>
												</div>


												<div class="clearfix form-actions">
													<div class="col-md-offset-3 col-md-9">
														<button class="btn btn-success" type="submit" name="simpan">
															<i class="ace-icon fa fa-check bigger-110"></i>
															Simpan
														</button>

														&nbsp; &nbsp; &nbsp;

														<button class="btn" type="reset">
															<i class="ace-icon fa fa-undo bigger-110"></i>
															Reset
														</button>

														&nbsp; &nbsp; &nbsp;

														<a href="master_nama_barang.php" class="btn btn-danger">
															<i class="ace-icon fa fa-times bigger-110"></i>
															Batal
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

		<!-- page specific plugin scripts -->
		<script src="assets/js/jquery.validate.min.js"></script>
		<script src="assets/js/jquery-additional-methods.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
				// Validation
				$('#validation-form').validate({
					errorElement: 'div',
					errorClass: 'help-block',
					focusInvalid: false,
					ignore: "",
					rules: {
						nama_barang: {
							required: true,
							maxlength: 50
						},
						sinonim_1: {
							maxlength: 50
						},
						sinonim_2: {
							maxlength: 50
						},
						sinonim_3: {
							maxlength: 50
						},
						perlu_ukuran: {
							required: true
						},
						keterangan: {
							maxlength: 255
						},
						satuan: {
							required: true
						}
					},

					messages: {
						nama_barang: {
							required: "NAMA BARANG harus diisi",
							maxlength: "NAMA BARANG maksimal 50 karakter"
						},
						sinonim_1: {
							maxlength: "SINONIM 1 maksimal 50 karakter"
						},
						sinonim_2: {
							maxlength: "SINONIM 2 maksimal 50 karakter"
						},
						sinonim_3: {
							maxlength: "SINONIM 3 maksimal 50 karakter"
						},
						perlu_ukuran: {
							required: "PERLU UKURAN harus dipilih"
						},
						keterangan: {
							maxlength: "Keterangan maksimal 255 karakter"
						},
						satuan: {
							required: "SATUAN harus dipilih"
						}
					},

					highlight: function (e) {
						$(e).closest('.form-group').removeClass('has-info').addClass('has-error');
					},

					success: function (e) {
						$(e).closest('.form-group').removeClass('has-error');//.addClass('has-info');
						$(e).remove();
					},

					errorPlacement: function (error, element) {
						if(element.is('input[type=checkbox]') || element.is('input[type=radio]')) {
							var controls = element.closest('div[class*="col-"]');
							if(controls.find(':checkbox,:radio').length > 1) controls.append(error);
							else error.insertAfter(element.nextAll('.lbl:eq(0)').eq(0));
						}
						else if(element.is('.select2')) {
							error.insertAfter(element.siblings('[class*="select2-container"]:eq(0)'));
						}
						else if(element.is('.chosen-select')) {
							error.insertAfter(element.siblings('[class*="chosen-container"]:eq(0)'));
						}
						else error.insertAfter(element.parent());
					},

					submitHandler: function (form) {
						if(confirm('Apakah data sudah benar dan akan disimpan?')) {
							form.submit();
						}
						return false;
					},
					invalidHandler: function (form) {
					}
				});

				// Prevent spaces and auto-uppercase for nama_barang (Excel requirement: 1 word only)
				$('#nama_barang').on('input', function() {
					var originalValue = this.value;
					// Remove ALL whitespace (spaces, tabs, newlines) and convert to uppercase
					var cleanValue = this.value.replace(/\s+/g, '').toUpperCase();
					this.value = cleanValue;

					// Show warning if any whitespace was removed
					if(originalValue !== cleanValue && /\s/.test(originalValue)) {
						alert('PERINGATAN: Hanya boleh 1 kata, tidak boleh ada spasi atau karakter kosong lainnya!');
					}
				});

				// Store initial value
				$('#nama_barang').data('prev-value', $('#nama_barang').val());

				// Apply same rules to sinonim fields
				$('#sinonim_1, #sinonim_2, #sinonim_3').on('input', function() {
					var originalValue = this.value;
					// Remove ALL whitespace and convert to uppercase
					var cleanValue = this.value.replace(/\s+/g, '').toUpperCase();
					this.value = cleanValue;

					// Show warning if any whitespace was removed
					if(originalValue !== cleanValue && /\s/.test(originalValue)) {
						alert('PERINGATAN: Sinonim hanya boleh 1 kata, tidak boleh ada spasi atau karakter kosong lainnya!');
					}
				});

				// Store initial values for sinonim fields
				$('#sinonim_1, #sinonim_2, #sinonim_3').each(function() {
					$(this).data('prev-value', $(this).val());
				});

				// Conditional behavior for Perlu Ukuran
				$('#perlu_ukuran').on('change', function() {
					var perluUkuran = $(this).val();
					var $keterangan = $('#keterangan');
					var $keteranganRequired = $('#keterangan_required');
					var $keteranganHelp = $('#keterangan_help');

					if(perluUkuran === 'YA') {
						$keterangan.prop('readonly', false);
						$keterangan.prop('required', true);
						$keterangan.attr('placeholder', 'Format: angka tanpa spasi (contoh: 10X20X5)');
						$keterangan.val('');
						$keteranganRequired.show();
						$keteranganHelp.html('<strong>WAJIB DIISI!</strong><br>Format: ...X...X... (angka tanpa spasi, contoh: 10X20X5)');

						// Add pattern validation
						$keterangan.attr('pattern', '^\\d+X\\d+X\\d+$');
						$keterangan.attr('title', 'Format harus angka tanpa spasi, contoh: 10X20X5');

					} else if(perluUkuran === 'TIDAK') {
						$keterangan.prop('readonly', true);
						$keterangan.prop('required', false);
						$keterangan.val('TIDAK PERLU UKURAN');
						$keteranganRequired.hide();
						$keteranganHelp.html('<strong>OTOMATIS TERISI!</strong><br>Tidak perlu ukuran untuk barang ini.');

						// Remove pattern validation
						$keterangan.removeAttr('pattern');
						$keterangan.removeAttr('title');
					} else {
						$keterangan.prop('readonly', false);
						$keterangan.prop('required', false);
						$keterangan.attr('placeholder', 'Akan otomatis terisi berdasarkan pilihan Perlu Ukuran');
						$keterangan.val('');
						$keteranganRequired.show();
						$keteranganHelp.html('<strong>OTOMATIS TERISI!</strong><br>• Jika Perlu Ukuran = YA → Format: angka tanpa spasi (contoh: 10X20X5)<br>• Jika Perlu Ukuran = TIDAK → Otomatis "TIDAK PERLU UKURAN"');
					}
				});

				// Trigger change event on page load
				$('#perlu_ukuran').trigger('change');
			});
		</script>
	</body>
</html>

<?php } ?>