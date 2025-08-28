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

        $tgl_pilih=$_GET['stgl'];
        $nopelanggan=$_GET['ssup'];
        $cbosales=$_GET['ssales'];
        $spesan=$_GET['spesan'];
        
        $txtkey= $_GET['_key'];
        $txtcari= $_GET['_cari'];
        $txturut= $_GET['_urut'];
        $txtflt= $_GET['_flt'];

        if($txtflt=='asc') {
            $tipebtn1="btn-danger";
            $tipebtn2="btn-info";
        } else {
            $tipebtn1="btn-info";                        
            $tipebtn2="btn-danger";
        }

                
           // Cari ================
        if($txtcari=='') {
            $sql_cari="";
        } else {
            if($txtcari=='18') {
                $sql_cari="nopelanggan";
            }
            if($txtcari=='19') {
                $sql_cari="namapelanggan";
            }
            if($txtcari=='20') {
                $sql_cari="alamat";
            }
            if($txtcari=='21') {
                $sql_cari="kota";
            }
            if($txtcari=='22') {
                $sql_cari="telephone";
            }
            if($txtcari=='23') {
                $sql_cari="grup";
            }
        }
    // end ===========

    
    
    // urut ================
        if($txturut=='22') {
            $sql_urut="nopelanggan";
        }
        if($txturut=='23') {
            $sql_urut="namapelanggan";
        }
        if($txturut=='24') {
            $sql_urut="kota";
        }
        if($txturut=='25') {
            $sql_urut="grup";
        }

    // end ===========

        if($txtflt=='asc') {
            IF($sql_cari=="") {
                $sql_query="SELECT * FROM view_cari_pelanggan 
                            WHERE 
                            (nopelanggan like '%".$txtkey."%') OR 
                            (namapelanggan like '%".$txtkey."%') OR 
                            (alamat like '%".$txtkey."%') OR 
                            (kota like '%".$txtkey."%') OR 
                            (telephone like '%".$txtkey."%') OR 
                            (grup like '%".$txtkey."%') 
                            order by ".$sql_urut." asc"; 

                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_pelanggan 
                                                WHERE 
                                                (nopelanggan like '%".$txtkey."%') OR 
                            (namapelanggan like '%".$txtkey."%') OR 
                            (alamat like '%".$txtkey."%') OR 
                            (kota like '%".$txtkey."%') OR 
                            (telephone like '%".$txtkey."%') OR 
                            (grup like '%".$txtkey."%')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];               
            } ELSE {
                $sql_query=" SELECT * FROM view_cari_pelanggan 
                            WHERE ".$sql_cari." like '%".$txtkey."%' order by ".$sql_urut." asc";
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_pelanggan 
                            WHERE ".$sql_cari." like '%".$txtkey."%'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];				                        
            }
        } else {
            IF($sql_cari=="") {
                $sql_query="SELECT * FROM view_cari_pelanggan 
                            WHERE 
                            (nopelanggan like '%".$txtkey."%') OR 
                            (namapelanggan like '%".$txtkey."%') OR 
                            (alamat like '%".$txtkey."%') OR 
                            (kota like '%".$txtkey."%') OR 
                            (telephone like '%".$txtkey."%') OR 
                            (grup like '%".$txtkey."%') 
                            order by ".$sql_urut." desc"; 

                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_pelanggan 
                                                WHERE 
                                                (nopelanggan like '%".$txtkey."%') OR 
                            (namapelanggan like '%".$txtkey."%') OR 
                            (alamat like '%".$txtkey."%') OR 
                            (kota like '%".$txtkey."%') OR 
                            (telephone like '%".$txtkey."%') OR 
                            (grup like '%".$txtkey."%')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];                               
            } else {
                $sql_query=" SELECT * FROM view_cari_pelanggan 
                            WHERE ".$sql_cari." like '%".$txtkey."%' order by ".$sql_urut." desc";
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_pelanggan 
                            WHERE ".$sql_cari." like '%".$txtkey."%'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];				                                    
            }
        }

        $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";
    
		if(isset($_POST['btnasc'])) {	
			$tgl_pilih= $_POST['txttgl'];
			$nopelanggan= $_POST['txtsup'];
			$cbosales= $_POST['txtsales'];
			$txtpesan= $_POST['txtpesan'];            
                                    
			$txtkey= $_POST['txtkey'];	
			$cbocari= $_POST['cbocari'];	
			$cbourut= $_POST['cbourut'];
            echo"<script>window.location=('penjualan_add_pelanggan_cari.php?stgl=$tgl_pilih&ssup=$nopelanggan&ssales=$cbosales&spesan=$txtpesan&_key=$txtkey&_cari=$cbocari&_urut=$cbourut&_flt=asc');</script>";
        }

		if(isset($_POST['btndesc'])) {				
			$tgl_pilih= $_POST['txttgl'];
			$nopelanggan= $_POST['txtsup'];
			$cbosales= $_POST['txtsales'];
			$txtpesan= $_POST['txtpesan'];            
            
			$txtkey= $_POST['txtkey'];	
			$cbocari= $_POST['cbocari'];	
			$cbourut= $_POST['cbourut'];
            echo"<script>window.location=('penjualan_add_pelanggan_cari.php?stgl=$tgl_pilih&ssup=$nopelanggan&ssales=$cbosales&spesan=$txtpesan&_key=$txtkey&_cari=$cbocari&_urut=$cbourut&_flt=desc');</script>";
        }


?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="with draggable and editable events" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="assets/css/fullcalendar.min.css" />

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
	<script type="text/javascript" src="chartjs/Chart.js"></script>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>	


        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.css' rel='stylesheet' />
		
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

<?php include "menu_penjualan02.php"; ?>

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
								<a href="#">Penjualan</a>
							</li>                            
							<li class="active">Cari Pelanggan</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">

						<br>
						<div class="row">
							<div class="col-xs-12 col-sm-12">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">	
                                            <form class="form-horizontal" action="" method="post" role="form">
                                                <input type="hidden" name="txttgl"  class="form-control" value="<?php echo $tgl_pilih; ?>"/>
                                                <input type="hidden" name="txtsup"  class="form-control" value="<?php echo $nopelanggan; ?>"/>                        
                                                <input type="hidden" name="txtsales"  class="form-control" value="<?php echo $cbosales; ?>"/>  
                                                <input type="hidden" name="txtpesan"  class="form-control" value="<?php echo $spesan; ?>"/>                                                                        
                                                <?php include "_template/_cari_pelanggan.php"; ?>
                                            </form>
                                        </div>
									</div>
								</div>	
							</div>
						</div>
                        <div class="space space-8"></div> 
                        <div class="row">
							<div class="col-xs-12 col-sm-12">
								<div class="table-header">
                                    <?php echo $hasil_cari; ?>
								</div>                            
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="center" width="10%"></th>
                                            <th width="10%">Kode</th>
                                            <th width="20%">Nama Pelanggan</th>
                                            <th width="30%">Alamat</th>
                                            <th width="10%">Kota</th>
                                            <th width="10%">No. Telepon</th>                                                        
                                            <th width="10%" class="center">Kategori</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        $sql = mysqli_query($koneksi,$sql_query);
                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                $noitem="";
                                                //$nopelanggan=$tampil['noitem'];
                                    ?>
                                        <tr>
                                            <td class="center">
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown" class="btn dropdown-toggle btn-minier btn-yellow">
                                                        Aksi
                                                        <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-default">
                                                        <li>
                                                            <a href="penjualan_add_rst.php?stgl=<?php echo $tgl_pilih; ?>&ssup=<?php echo $tampil['nopelanggan']; ?>&ssales=<?php echo $cbosales; ?>&kd=<?php echo $noitem; ?>&spesan=<?php echo $spesan; ?>">Pilih</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                                                                                    
                                            </td>														
                                            <td><?php echo $tampil['nopelanggan']?></td>														
                                            <td><?php echo $tampil['namapelanggan']?></td>														                                                        
                                            <td><?php echo $tampil['alamat']?></td>														                                                                                                                
                                            <td><?php echo $tampil['kota']?></td>														                                                                                                                
                                            <td><?php echo $tampil['telephone']?></td>														                                                                                                                                                                                                        
                                            <td class="center"><?php echo $tampil['grup']?></td>														                                                                                                                
                                        </tr>

                                    <?php
                                        }
                                    ?>
                                    </tbody>                                    
                                </table>
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