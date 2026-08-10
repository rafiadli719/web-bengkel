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
        
		$tgl=date('Y/m/d');
		$tgl_skr=date('d/m/Y');	
        
        
        
		$bulan_skr=date('m');
		$thn_skr=date('Y');        
        $kalender=CAL_GREGORIAN;
        $hari=cal_days_in_month($kalender,$bulan_skr,$thn_skr);
        //echo $hari;
        
        $daftar_hari = array( 'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu' );
		

		$nama_file_baru = basename($_FILES['filepegawai']['name']) ;
		//echo $target;
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="overview &amp; stats" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->

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

		<!-- inline styles related to this page -->

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
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
							<i class="fa fa-leaf"></i>
							<?php include "../lib/subtitel.php"; ?>
									</small>							
								</a>								
							</td>
							<td>

                            </td>							
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

				<?php include "menu_pegawai.php"; ?>

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
								<a href="pegawai.php">Data Pegawai</a>
							</li>
							<li class="active">Import Data dari EXCEL</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						
						<div class="row">
							<div class="col-xs-12 col-sm-12">

								<form class="" action="pegawai_upload_proses.php" method="post">							
									<input type="hidden" name="txtfile"  class="form-control" value="<?php echo $nama_file_baru; ?>" />		

												<?php
													$ext = pathinfo($_FILES['filepegawai']['name'], PATHINFO_EXTENSION); // Ambil ekstensi filenya apa
													$tmp_file = $_FILES['filepegawai']['tmp_name'];

													if(is_file('tmp_excel/'.$nama_file_baru)) // Jika file tersebut ada
														unlink('tmp_excel/'.$nama_file_baru); // Hapus file tersebut
													// Cek apakah file yang diupload adalah file Excel 2007 (.xlsx)
													if($ext == "xlsx"){
														move_uploaded_file($tmp_file, 'tmp_excel/'.$nama_file_baru);
														require_once 'PHPExcel/PHPExcel.php';

														$excelreader = new PHPExcel_Reader_Excel2007();
														$loadexcel = $excelreader->load('tmp_excel/'.$nama_file_baru); // Load file yang tadi diupload ke folder tmp
														$sheet = $loadexcel->getActiveSheet()->toArray(null, true, true ,true);

														// Buat sebuah div untuk alert validasi kosong
														echo "<div class='alert alert-danger' id='kosong'>
														Preview Data Employee Hasil Dari Import Excel.
														</div>";	
												?>

													<table class="table table-bordered">
														<tr>
															<th width="5%"><font size="1">Company Id</font></th>
															<th width="5%"><font size="1">Employee Id</font></th>
															<th width="5%"><font size="1">Name</font></th>
															<th width="5%"><font size="1">Citizenship</font></th>
															<th width="5%"><font size="1">Gender</font></th>
															<th width="5%"><font size="1">Marital Status</font></th>
															<th width="5%"><font size="1">Place of Birth</font></th>
															<th width="5%"><font size="1">Date of Birth</font></th>
															<th width="5%"><font size="1">Religion</font></th>
															<th width="5%"><font size="1">Blood Type</font></th>
															<th width="5%"><font size="1">Address</font></th>
															<th width="5%"><font size="1">City</font></th>
															<th width="5%"><font size="1">Postal Code</font></th>
															<th width="5%"><font size="1">State/Province</font></th>
															<th width="5%"><font size="1">Phone</font></th>
															<th width="5%"><font size="1">Email</font></th>
															<th width="5%"><font size="1">KTP No</font></th>
															<th width="15%"><font size="1">Last Education Level</font></th>		

															<th width="15%"><font size="1">Supervisor Name</font></th>
															<th width="15%"><font size="1">Employee Status</font></th>
															<th width="15%"><font size="1">Employee Type</font></th>
															<th width="15%"><font size="1">Tanggal Masuk</font></th>
															<th width="15%"><font size="1">Tanggal Akhir</font></th>
															<th width="15%"><font size="1">Kode Posisi</font></th>
															<th width="15%"><font size="1">Work Location</font></th>
															<th width="15%"><font size="1">Jadwal Kerja</font></th>
															<th width="15%"><font size="1">Gaji Pokok</font></th>
															<th width="15%"><font size="1">Grade Karyawan</font></th>																											
															<th width="15%"><font size="1">NPWP</font></th>																																										
														</tr>												
												
												<?php 
														$numrow = 1;
														$kosong = 0;
														foreach($sheet as $row){ // Lakukan perulangan dari data yang ada di excel
															// Ambil data pada excel sesuai Kolom
															$fld1 = $row['A']; // Ambil data NIS
															$fld2 = $row['B']; // Ambil data nama
															$fld3 = $row['C']; // Ambil data jenis kelamin
															$fld4 = $row['D']; // Ambil data telepon
															$fld5 = $row['E']; // Ambil data jenis kelamin
															$fld6 = $row['F']; // Ambil data telepon
															$fld7 = $row['G']; // Ambil data telepon
															$fld8 = $row['H']; // Ambil data NIS
															$fld9 = $row['I']; // Ambil data nama
															$fld10 = $row['J']; // Ambil data jenis kelamin
															$fld11 = $row['K']; // Ambil data telepon
															$fld12 = $row['L']; // Ambil data jenis kelamin
															$fld13 = $row['M']; // Ambil data telepon
															$fld14 = $row['N']; // Ambil data telepon
															$fld15 = $row['O']; // Ambil data telepon
															$fld16 = $row['P']; // Ambil data jenis kelamin
															$fld17 = $row['Q']; // Ambil data telepon
															$fld18 = $row['R']; // Ambil data telepon
															$fld19 = $row['S']; // Ambil data nama
															$fld20 = $row['T']; // Ambil data jenis kelamin
															$fld21 = $row['U']; // Ambil data telepon
															$fld22 = $row['V']; // Ambil data jenis kelamin
															$fld23 = $row['W']; // Ambil data telepon
															$fld24 = $row['X']; // Ambil data telepon
															$fld25 = $row['Y']; // Ambil data telepon
															$fld26 = $row['Z']; // Ambil data jenis kelamin
															$fld27 = $row['AA']; // Ambil data telepon
															$fld28 = $row['AB']; // Ambil data telepon
															$fld29 = $row['AC']; // Ambil data telepon
															
															// Cek jika semua data tidak diisi
															if($fld1 == "" && $fld2 == "" && $fld3 == "" && $fld4 == "" && $fld5 == "" && $fld6 == "" && $fld7 == "" 
																		&& $fld8 == "" && $fld9 == "" && $fld10 == "" && $fld11 == "" && $fld12 == "" && $fld13 == "" && $fld14 == "" 
																		&& $fld15 == "" && $fld16 == "" && $fld17 == "" && $fld18 == "" && $fld19 == "" && $fld20 == "" 																		
																		&& $fld21 == "" && $fld22 == "" && $fld23 == "" && $fld24 == "" && $fld25 == "" 																		
																		&& $fld26 == "" && $fld27 == "" && $fld28 == "" && $fld29 == "")																		
																continue; // Lewat data pada baris ini (masuk ke looping selanjutnya / baris selanjutnya)

															// Cek $numrow apakah lebih dari 1
															// Artinya karena baris pertama adalah nama-nama kolom
															// Jadi dilewat saja, tidak usah diimport
															if($numrow > 1){
																// Validasi apakah semua data telah diisi
																$fld1_td = ( ! empty($fld1))? "" : " style='background: #E07171;'"; // Jika NIS kosong, beri warna merah
																$fld2_td = ( ! empty($fld2))? "" : " style='background: #E07171;'"; // Jika Nama kosong, beri warna merah
																$fld3_td = ( ! empty($fld3))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld4_td = ( ! empty($fld4))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld5_td = ( ! empty($fld5))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld6_td = ( ! empty($fld6))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld7_td = ( ! empty($fld7))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah																
																$fld8_td = ( ! empty($fld8))? "" : " style='background: #E07171;'"; // Jika NIS kosong, beri warna merah
																$fld9_td = ( ! empty($fld9))? "" : " style='background: #E07171;'"; // Jika Nama kosong, beri warna merah
																$fld10_td = ( ! empty($fld10))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld11_td = ( ! empty($fld11))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld12_td = ( ! empty($fld12))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld13_td = ( ! empty($fld13))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld14_td = ( ! empty($fld14))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah																																
																$fld15_td = ( ! empty($fld15))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld16_td = ( ! empty($fld16))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld17_td = ( ! empty($fld17))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld18_td = ( ! empty($fld18))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah																
																$fld19_td = ( ! empty($fld19))? "" : " style='background: #E07171;'"; // Jika Nama kosong, beri warna merah
																$fld20_td = ( ! empty($fld20))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld21_td = ( ! empty($fld21))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld22_td = ( ! empty($fld22))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld23_td = ( ! empty($fld23))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld24_td = ( ! empty($fld24))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah																																
																$fld25_td = ( ! empty($fld25))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld26_td = ( ! empty($fld26))? "" : " style='background: #E07171;'"; // Jika Jenis Kelamin kosong, beri warna merah
																$fld27_td = ( ! empty($fld27))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld28_td = ( ! empty($fld28))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah
																$fld29_td = ( ! empty($fld29))? "" : " style='background: #E07171;'"; // Jika Telepon kosong, beri warna merah																
																
																// Jika salah satu data ada yang kosong
																if($fld1 == "" or $fld2 == "" or $fld3 == "" or $fld4 == "" or $fld5 == "" or $fld6 == "" or $fld7 == "" 
																or $fld8 == "" or $fld9 == "" or $fld10 == "" or $fld11 == "" or $fld12 == "" or $fld13 == "" or $fld14 == "" 
																or $fld15 == "" or $fld16 == "" or $fld17 == "" or $fld18 == "" 
																or $fld19 == "" or $fld20 == "" or $fld21 == "" or $fld22 == "" 
																or $fld23 == "" or $fld24 == "" or $fld25 == "" or $fld26 == "" or $fld27 == "" or $fld28 == "" or $fld29 == ""){
																	$kosong++; // Tambah 1 variabel $kosong
																}

																echo "<tr>";
																echo "<td".$fld1_td."><font size=1>".$fld1."</font></td>";
																echo "<td".$fld2_td."><font size=1>".$fld2."</font></td>";
																echo "<td".$fld3_td."><font size=1>".$fld3."</font></td>";
																echo "<td".$fld4_td."><font size=1>".$fld4."</font></td>";
																echo "<td".$fld5_td."><font size=1>".$fld5."</font></td>";
																echo "<td".$fld6_td."><font size=1>".$fld6."</font></td>";
																echo "<td".$fld7_td."><font size=1>".$fld7."</font></td>";
																echo "<td".$fld8_td."><font size=1>".$fld8."</font></td>";
																echo "<td".$fld9_td."><font size=1>".$fld9."</font></td>";
																echo "<td".$fld10_td."><font size=1>".$fld10."</font></td>";
																echo "<td".$fld11_td."><font size=1>".$fld11."</font></td>";
																echo "<td".$fld12_td."><font size=1>".$fld12."</font></td>";
																echo "<td".$fld13_td."><font size=1>".$fld13."</font></td>";
																echo "<td".$fld14_td."><font size=1>".$fld14."</font></td>";
																echo "<td".$fld15_td."><font size=1>".$fld15."</font></td>";
																echo "<td".$fld16_td."><font size=1>".$fld16."</font></td>";
																echo "<td".$fld17_td."><font size=1>".$fld17."</font></td>";
																echo "<td".$fld18_td."><font size=1>".$fld18."</font></td>";
																echo "<td".$fld19_td."><font size=1>".$fld19."</font></td>";
																echo "<td".$fld20_td."><font size=1>".$fld20."</font></td>";
																echo "<td".$fld21_td."><font size=1>".$fld21."</font></td>";
																echo "<td".$fld22_td."><font size=1>".$fld22."</font></td>";
																echo "<td".$fld23_td."><font size=1>".$fld23."</font></td>";
																echo "<td".$fld24_td."><font size=1>".$fld24."</font></td>";
																echo "<td".$fld25_td."><font size=1>".$fld25."</font></td>";
																echo "<td".$fld26_td."><font size=1>".$fld26."</font></td>";
																echo "<td".$fld27_td."><font size=1>".$fld27."</font></td>";
																echo "<td".$fld28_td."><font size=1>".$fld28."</font></td>";
																echo "<td".$fld29_td."><font size=1>".$fld29."</font></td>";																
																echo "</tr>";
															}

															$numrow++; // Tambah 1 setiap kali looping
														}												
												?>

												<?php 
																									}else{ // Jika file yang diupload bukan File Excel 2007 (.xlsx)
														// Munculkan pesan validasi
														echo "<div class='alert alert-danger'>
														Hanya File Excel 2007 (.xlsx) yang diperbolehkan
														</div>";
													}
													?>
											</table>
											<br>
									<button type="submit" name="import" class="btn btn-primary"><span class="glyphicon glyphicon-upload"></span> Import</button>							
								</form>


													
							</div>
							
						</div>	


					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

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
		</div><!-- /.main-container -->

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
				//initiate dataTables plugin
				var myTable = 
				$('#dynamic-table')
				//.wrap("<div class='dataTables_borderWrap' />")   //if you are applying horizontal scrolling (sScrollX)
				.DataTable( {
					bAutoWidth: false,
					"aoColumns": [
					  { "bSortable": false },
					  null, null,null, null, null,
					  { "bSortable": false }
					],
					"aaSorting": [],
					
					
					//"bProcessing": true,
			        //"bServerSide": true,
			        //"sAjaxSource": "http://127.0.0.1/table.php"	,
			
					//,
					//"sScrollY": "200px",
					//"bPaginate": false,
			
					//"sScrollX": "100%",
					//"sScrollXInner": "120%",
					//"bScrollCollapse": true,
					//Note: if you are applying horizontal scrolling (sScrollX) on a ".table-bordered"
					//you may want to wrap the table inside a "div.dataTables_borderWrap" element
			
					//"iDisplayLength": 50
			
			
					select: {
						style: 'multi'
					}
			    } );
			
				
				
				$.fn.dataTable.Buttons.defaults.dom.container.className = 'dt-buttons btn-overlap btn-group btn-overlap';
				
				new $.fn.dataTable.Buttons( myTable, {
					buttons: [
					  {
						"extend": "colvis",
						"text": "<i class='fa fa-search bigger-110 blue'></i> <span class='hidden'>Show/hide columns</span>",
						"className": "btn btn-white btn-primary btn-bold",
						columns: ':not(:first):not(:last)'
					  },
					  {
						"extend": "copy",
						"text": "<i class='fa fa-copy bigger-110 pink'></i> <span class='hidden'>Copy to clipboard</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "csv",
						"text": "<i class='fa fa-database bigger-110 orange'></i> <span class='hidden'>Export to CSV</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "excel",
						"text": "<i class='fa fa-file-excel-o bigger-110 green'></i> <span class='hidden'>Export to Excel</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "pdf",
						"text": "<i class='fa fa-file-pdf-o bigger-110 red'></i> <span class='hidden'>Export to PDF</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "print",
						"text": "<i class='fa fa-print bigger-110 grey'></i> <span class='hidden'>Print</span>",
						"className": "btn btn-white btn-primary btn-bold",
						autoPrint: false,
						message: 'This print was produced using the Print button for DataTables'
					  }		  
					]
				} );
				myTable.buttons().container().appendTo( $('.tableTools-container') );
				
				//style the message box
				var defaultCopyAction = myTable.button(1).action();
				myTable.button(1).action(function (e, dt, button, config) {
					defaultCopyAction(e, dt, button, config);
					$('.dt-button-info').addClass('gritter-item-wrapper gritter-info gritter-center white');
				});
				
				
				var defaultColvisAction = myTable.button(0).action();
				myTable.button(0).action(function (e, dt, button, config) {
					
					defaultColvisAction(e, dt, button, config);
					
					
					if($('.dt-button-collection > .dropdown-menu').length == 0) {
						$('.dt-button-collection')
						.wrapInner('<ul class="dropdown-menu dropdown-light dropdown-caret dropdown-caret" />')
						.find('a').attr('href', '#').wrap("<li />")
					}
					$('.dt-button-collection').appendTo('.tableTools-container .dt-buttons')
				});
			
				////
			
				setTimeout(function() {
					$($('.tableTools-container')).find('a.dt-button').each(function() {
						var div = $(this).find(' > div').first();
						if(div.length == 1) div.tooltip({container: 'body', title: div.parent().text()});
						else $(this).tooltip({container: 'body', title: $(this).text()});
					});
				}, 500);
				
				
				
				
				
				myTable.on( 'select', function ( e, dt, type, index ) {
					if ( type === 'row' ) {
						$( myTable.row( index ).node() ).find('input:checkbox').prop('checked', true);
					}
				} );
				myTable.on( 'deselect', function ( e, dt, type, index ) {
					if ( type === 'row' ) {
						$( myTable.row( index ).node() ).find('input:checkbox').prop('checked', false);
					}
				} );
			
			
			
			
				/////////////////////////////////
				//table checkboxes
				$('th input[type=checkbox], td input[type=checkbox]').prop('checked', false);
				
				//select/deselect all rows according to table header checkbox
				$('#dynamic-table > thead > tr > th input[type=checkbox], #dynamic-table_wrapper input[type=checkbox]').eq(0).on('click', function(){
					var th_checked = this.checked;//checkbox inside "TH" table header
					
					$('#dynamic-table').find('tbody > tr').each(function(){
						var row = this;
						if(th_checked) myTable.row(row).select();
						else  myTable.row(row).deselect();
					});
				});
				
				//select/deselect a row when the checkbox is checked/unchecked
				$('#dynamic-table').on('click', 'td input[type=checkbox]' , function(){
					var row = $(this).closest('tr').get(0);
					if(this.checked) myTable.row(row).deselect();
					else myTable.row(row).select();
				});
			
			
			
				$(document).on('click', '#dynamic-table .dropdown-toggle', function(e) {
					e.stopImmediatePropagation();
					e.stopPropagation();
					e.preventDefault();
				});
				
				
				
				//And for the first simple table, which doesn't have TableTools or dataTables
				//select/deselect all rows according to table header checkbox
				var active_class = 'active';
				$('#simple-table > thead > tr > th input[type=checkbox]').eq(0).on('click', function(){
					var th_checked = this.checked;//checkbox inside "TH" table header
					
					$(this).closest('table').find('tbody > tr').each(function(){
						var row = this;
						if(th_checked) $(row).addClass(active_class).find('input[type=checkbox]').eq(0).prop('checked', true);
						else $(row).removeClass(active_class).find('input[type=checkbox]').eq(0).prop('checked', false);
					});
				});
				
				//select/deselect a row when the checkbox is checked/unchecked
				$('#simple-table').on('click', 'td input[type=checkbox]' , function(){
					var $row = $(this).closest('tr');
					if($row.is('.detail-row ')) return;
					if(this.checked) $row.addClass(active_class);
					else $row.removeClass(active_class);
				});
			
				
			
				/********************************/
				//add tooltip for small view action buttons in dropdown menu
				$('[data-rel="tooltip"]').tooltip({placement: tooltip_placement});
				
				//tooltip placement on right or left
				function tooltip_placement(context, source) {
					var $source = $(source);
					var $parent = $source.closest('table')
					var off1 = $parent.offset();
					var w1 = $parent.width();
			
					var off2 = $source.offset();
					//var w2 = $source.width();
			
					if( parseInt(off2.left) < parseInt(off1.left) + parseInt(w1 / 2) ) return 'right';
					return 'left';
				}
				
				
				
				
				/***************/
				$('.show-details-btn').on('click', function(e) {
					e.preventDefault();
					$(this).closest('tr').next().toggleClass('open');
					$(this).find(ace.vars['.icon']).toggleClass('fa-angle-double-down').toggleClass('fa-angle-double-up');
				});
				/***************/
				
				
				
				
				
				/**
				//add horizontal scrollbars to a simple table
				$('#simple-table').css({'width':'2000px', 'max-width': 'none'}).wrap('<div style="width: 1000px;" />').parent().ace_scroll(
				  {
					horizontal: true,
					styleClass: 'scroll-top scroll-dark scroll-visible',//show the scrollbars on top(default is bottom)
					size: 2000,
					mouseWheelLock: true
				  }
				).css('padding-top', '12px');
				*/
			
			
			})
		</script>
	</body>
</html>

<?php 
	}
?>