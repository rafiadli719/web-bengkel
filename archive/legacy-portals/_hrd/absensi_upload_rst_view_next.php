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
		$tgl_skr=date('d');	
        
        
        
		$bulan_skr=date('m');
		$thn_skr=date('Y');        
        $kalender=CAL_GREGORIAN;
        $hari=cal_days_in_month($kalender,$bulan_skr,$thn_skr);
        //echo $hari;
        
        $daftar_hari = array( 'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu' );

        $id_upload=$_GET['txtidupload'];
        $cbotgl=$_GET['cbotgl'];
        $cbonip=$_GET['cbonip'];
            
        if($cbotgl=='' AND $cbonip=='') {
            $sql_cari="SELECT 
                        nip, DATE_FORMAT(tgl,'%d/%m/%Y') AS tanggal_absen, 
                        jam_masuk, jam_keluar, 
                        kode_status_kehadiran, keterangan 
                        FROM tbabsensi 
                        WHERE nip<>'0' AND id_upload='$id_upload' 
                        ORDER BY tgl,jam_masuk";            
        }
        if($cbotgl<>'' AND $cbonip=='') {
            $sql_cari="SELECT 
                        nip, DATE_FORMAT(tgl,'%d/%m/%Y') AS tanggal_absen, 
                        jam_masuk, jam_keluar, 
                        kode_status_kehadiran, keterangan  
                        FROM tbabsensi 
                        WHERE nip<>'0' AND id_upload='$id_upload' AND 
                        tgl='$cbotgl' 
                        ORDER BY jam_masuk";            
        }
        if($cbotgl=='' AND $cbonip<>'') {
            $sql_cari="SELECT 
                        nip, DATE_FORMAT(tgl,'%d/%m/%Y') AS tanggal_absen, 
                        jam_masuk, jam_keluar, 
                        kode_status_kehadiran, keterangan  
                        FROM tbabsensi 
                        WHERE nip='$cbonip' AND id_upload='$id_upload' 
                        ORDER BY tgl";            
        }     
        if($cbotgl<>'' AND $cbonip<>'') {
            $sql_cari="SELECT 
                        nip, DATE_FORMAT(tgl,'%d/%m/%Y') AS tanggal_absen, 
                        jam_masuk, jam_keluar, 
                        kode_status_kehadiran, keterangan  
                        FROM tbabsensi 
                        WHERE nip='$cbonip' AND id_upload='$id_upload' 
AND 
                        tgl='$cbotgl'";            
        }                                                                               
                
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        kode_cabang, waktu_upload,  
																					DATE_FORMAT(tgl_upload,'%d/%m/%Y') AS tanggal_upload, 
																					DATE_FORMAT(tgl_absensi_awal,'%d/%m/%Y') AS tanggal_awal, 
																					DATE_FORMAT(tgl_absensi_akhir,'%d/%m/%Y') AS tanggal_akhir                                                                                     
																					FROM tbabsensi_upload 
										WHERE id_upload='$id_upload'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tanggal_upload=$tm_cari['tanggal_upload'];
        $waktu_upload=$tm_cari['waktu_upload'];
        $kode_cabang_upload=$tm_cari['kode_cabang'];
        $tanggal_awal=$tm_cari['tanggal_awal'];
        $tanggal_akhir=$tm_cari['tanggal_akhir'];
        
        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        nama_cabang 
                                                                                        FROM tbcabang 
                                                                                        WHERE kode_cabang='$kode_cabang_upload'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama_cabang_upload=$tm_cari['nama_cabang'];

        $cari_kd=mysqli_query($koneksi,"SELECT 
                                        count(id_upload) as total 
                                        FROM tbabsensi 
                                        WHERE id_upload='$id_upload'");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $total_data=$tm_cari['total'];
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

				<?php include "menu_absensi05.php"; ?>

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
							<li class="active">Hasil Upload</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						
                        <div class="row">
							<div class="col-xs-6">
								<div class="table-header">
                                    HASIL UPLOAD ABSENSI
								</div>                            
                                <table id="dynamic-table" class="table table-bordered">
                                    <tr>
                                        <td width="30%" bgcolor="beige">
                                            Tanggal Upload
                                        </td>
                                        <td width="70%">
                                            <?php echo $tanggal_upload; ?>
                                        </td>  
                                    </tr>
                                    <tr>
                                        <td width="30%" bgcolor="beige">
                                            Waktu Upload
                                        </td>
                                        <td width="70%">
                                            <?php echo $waktu_upload; ?>                                        
                                        </td>                                                                                
                                    </tr>
                                    <tr>
                                        <td width="30%" bgcolor="beige">
                                            Cabang
                                        </td>
                                        <td width="70%">
                                            <?php echo $nama_cabang_upload; ?>                                        
                                        </td>                                                                                
                                    </tr>                                    
                                    <tr>
                                        <td width="30%" bgcolor="beige">
                                            Periode
                                        </td>
                                        <td width="70%">
                                            <?php echo $tanggal_awal; ?>  
                                            &nbsp;&nbsp;-s/d-&nbsp;&nbsp;
                                            <?php echo $tanggal_akhir; ?>
                                        </td>                                                                                
                                    </tr>       
                                                                                            
                                </table>
                            </div>
                            <div class="col-xs-4">
                                <table id="dynamic-table" class="table table-bordered">
                                    <tr>
                                        <td bgcolor="beige" align="center">
                                            
                                            <h4>Jumlah Data <br>yang berhasil di upload</h4>
                                            <h1><?php echo $total_data; ?></h1>
                                            
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <form class="form-horizontal" action="absensi_upload_rst_view_next.php" method="get">
									<input type="hidden" name="txtidupload"  class="form-control" value="<?php echo $id_upload; ?>"/>
                                <div class="col-xs-2">
                                    <select class="col-xs-10 col-sm-12" name="cbotgl" id="cbotgl" >
                                        <option value="">-- Semua Tanggal --</option>
                                        <?php
                                            $q = mysqli_query($koneksi,"SELECT 
                                                distinct(DATE_FORMAT(tgl,'%d/%m/%Y')) AS tanggal_absen, 
                                                tgl 
                                                FROM tbabsensi 
                                                WHERE nip<>'0' AND id_upload='$id_upload' 
                                                ORDER BY tgl");
												while ($row1 = mysqli_fetch_array($q)){
													$k_id           = $row1['tgl'];
													$k_opis         = $row1['tanggal_absen'];
                                        ?>
                                        <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbotgl){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                        <?php
                                            }
                                        ?>
                                    </select>                
                                </div>
                                <div class="col-xs-2">
                                    <select class="col-xs-10 col-sm-12" name="cbonip" id="cbonip" >
                                        <option value="">-- Semua Pegawai --</option>
                                        <?php
                                            $q = mysqli_query($koneksi,"SELECT 
                                                distinct(tbabsensi.nip) as id, tbpegawai.nama 
                                                FROM tbabsensi, tbpegawai 
                                                WHERE 
                                                tbabsensi.nip=tbpegawai.nip AND 
                                                tbabsensi.id_upload='$id_upload' 
                                                order by tbpegawai.nama");
												while ($row1 = mysqli_fetch_array($q)){
													$k_id           = $row1['id'];
													$k_opis         = $row1['nama'];
                                        ?>
                                        <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbonip){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                        <?php
                                            }
                                        ?>
                                    </select>                                
                                </div>   
                                <div class="col-xs-2">
									<button class="btn btn-info btn-block btn-sm" type="submit">
										TAMPILKAN
									</button>
                                </div>
                                <div class="col-xs-2">
                                    <a href="absensi_input_upload.php?stgl=<?php echo $cbotgl; ?>&scab=<?php echo $kode_cabang_upload; ?>&sidupl=<?php echo $id_upload; ?>">
									<button class="btn btn-info btn-block btn-sm" type="button">
										INPUT ABSENSI
									</button>
                                    </a>
                                </div>                                
                                <div class="col-xs-2">
                                    <a href="absensi_upload_rst.php">                                
									<button class="btn btn-danger btn-block btn-sm" type="button">
										TUTUP
									</button>			
                                    </a>
                                </div>
                            </form>
                        </div>        
                        <br>
						<div class="row">
							<div class="col-xs-10">

										<div>
											<table id="dynamic-table" class="table table-bordered table-hover">
												<thead>
													<tr>
														<th class="center"><b>Tanggal</b></th>
														<th class="center"><b>NIP</b></th>                                                        
														<th><b>Nama</b></th>                                                                      
														<th class="center"><b>Status Kehadiran</b></th>                                                                                                  
														<th class="center"><b>Jam Masuk</b></th>
														<th class="center"><b>Jam Pulang</b></th>
														<th><b>Keterangan</b></th>                                                                                                                              
													</tr>
												</thead>
												<tbody>
												<?php 
													$no = 0 ;
													$sql = mysqli_query($koneksi,$sql_cari);
													while ($tampil = mysqli_fetch_array($sql)) {
														$no++;
                                                        $nip_absen=$tampil['nip'];
                                                        $kode_status_kehadiran=$tampil['kode_status_kehadiran'];

                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        nama 
                                                                                        FROM tbpegawai 
                                                                                        WHERE nip='$nip_absen'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama_absen=$tm_cari['nama'];		

                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        status_kehadiran 
                                                                                        FROM tbstatus_kehadiran 
                                                                                        WHERE id='$kode_status_kehadiran'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $status_kehadiran=$tm_cari['status_kehadiran'];													
												?>
													<tr>
														<td class="center"><?php echo $tampil['tanggal_absen']?></td>
														<td class="center"><?php echo $tampil['nip']?></td>
														<td><?php echo $nama_absen; ?></td>
                                                        														<td><?php echo $status_kehadiran; ?></td>
														<td class="center">
                                                            <?php echo $tampil['jam_masuk']?>
                                                            
                                                        </td>	
                                                        <td class="center">

                                                            <?php echo $tampil['jam_keluar']?>
                                                        </td>
                                                        <td><?php echo $tampil['keterangan']?></td>


													</tr>


<?php
            				}
          			?>
												</tbody>
											</table>
										</div>
										
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