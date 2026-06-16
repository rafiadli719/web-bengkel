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

        // == Default ==
        $txtkey="";
        $txtcari="";
        $txturut="28";
        $txtflt="asc";
        $tipebtn1="btn-danger";
        $tipebtn2="btn-info";
        $txttgl_periode = "";
        $cbocari = "";
        $cbourut = "";

        $hutang_error = '';
        $view_cols = [];
        $col_res = mysqli_query($koneksi, "SHOW COLUMNS FROM view_pembayaran_hutang");
        if ($col_res) {
            while ($r = mysqli_fetch_assoc($col_res)) {
                $view_cols[strtolower($r['Field'])] = true;
            }
        } else {
            $hutang_error = mysqli_error($koneksi);
        }

        $has_col = function($name) use ($view_cols) {
            return isset($view_cols[strtolower($name)]);
        };

        $trx_field = $has_col('no_transaksi') ? 'no_transaksi' : ($has_col('notransaksi') ? 'notransaksi' : '');
        $tgl_field = $has_col('tanggal') ? 'tanggal' : ($has_col('created_at') ? 'created_at' : '');
        $nosupplier_field = $has_col('no_supplier') ? 'no_supplier' : ($has_col('nosupplier') ? 'nosupplier' : '');
        $namasupplier_field = $has_col('namasupplier') ? 'namasupplier' : ($has_col('nama_supplier') ? 'nama_supplier' : '');
        $total_field = $has_col('total_bayar') ? 'total_bayar' : ($has_col('total') ? 'total' : ($has_col('total_akhir') ? 'total_akhir' : ''));
        $cabang_field = $has_col('kd_cabang') ? 'kd_cabang' : ($has_col('kode_cabang') ? 'kode_cabang' : '');
        
        // Filter logic
        $where_clause = "WHERE 1=1";
        if ($cabang_field !== '') {
            $where_clause .= " AND $cabang_field='$kd_cabang'";
        }
        
        if(isset($_REQUEST['btnasc']) || isset($_REQUEST['btndesc'])) {
            $txtkey = isset($_REQUEST['txtkey']) ? $_REQUEST['txtkey'] : "";
            $cbocari = isset($_REQUEST['cbocari']) ? $_REQUEST['cbocari'] : "";
            $cbourut = isset($_REQUEST['cbourut']) ? $_REQUEST['cbourut'] : "";
            $txttgl_periode = isset($_REQUEST['txttgl_periode']) ? $_REQUEST['txttgl_periode'] : "";
            
            // Build Where Clause
            if(!empty($txtkey)) {
                if(!empty($cbocari)) {
                     // Get column name from tbcari if needed or just use logic here
                     // For simplicity, assuming standard search. 
                     // You typically query tbcari to get the field name.
                     $q_cari = mysqli_query($koneksi, "select field FROM tbcari where id='$cbocari'");
                     if ($q_cari) {
                         $r_cari = mysqli_fetch_array($q_cari);
                         if ($r_cari) {
                            $field = $r_cari['field'];
                            $where_clause .= " AND $field LIKE '%$txtkey%'";
                         }
                     } else {
                         if ($hutang_error === '') {
                             $hutang_error = mysqli_error($koneksi);
                         }
                     }
                } else {
                     // Default search all fields
                     $search_trx = $trx_field !== '' ? $trx_field : 'no_transaksi';
                     $search_sup = $namasupplier_field !== '' ? $namasupplier_field : 'namasupplier';
                     $where_clause .= " AND ($search_trx LIKE '%$txtkey%' OR $search_sup LIKE '%$txtkey%')";
                }
            }
            
            // Date Filter
            if(!empty($txttgl_periode)) {
                $range = explode(" - ", $txttgl_periode);
                if(count($range) == 2) {
                    $start_date = date('Y-m-d', strtotime(str_replace('/', '-', $range[0])));
                    $end_date = date('Y-m-d', strtotime(str_replace('/', '-', $range[1])));
                    $date_field = $tgl_field !== '' ? $tgl_field : 'tanggal';
                    $where_clause .= " AND $date_field BETWEEN '$start_date' AND '$end_date'";
                }
            }
            
            // Order logic
            $order_tgl = $tgl_field !== '' ? $tgl_field : 'tanggal';
            $order_trx = $trx_field !== '' ? $trx_field : 'no_transaksi';
            $order_clause = "ORDER BY $order_tgl DESC, $order_trx DESC";
            if(!empty($cbourut)) {
                 $q_urut = mysqli_query($koneksi, "select field FROM tburut where id='$cbourut'");
                 if ($q_urut) {
                     $r_urut = mysqli_fetch_array($q_urut);
                     if ($r_urut) {
                         $field_urut = $r_urut['field'];
                         $direction = isset($_POST['btnasc']) ? "ASC" : "DESC";
                         $order_clause = "ORDER BY $field_urut $direction";
                         
                         if(isset($_POST['btnasc'])) { $tipebtn1="btn-danger"; $tipebtn2="btn-white"; }
                         else { $tipebtn1="btn-white"; $tipebtn2="btn-info"; }
                     }
                 } else {
                     if ($hutang_error === '') {
                         $hutang_error = mysqli_error($koneksi);
                     }
                 }
            }
        } else {
            // Default view - show recent or specific range if requested via GET logic (optional)
            $order_tgl = $tgl_field !== '' ? $tgl_field : 'tanggal';
            $order_trx = $trx_field !== '' ? $trx_field : 'no_transaksi';
            $order_clause = "ORDER BY $order_tgl DESC, $order_trx DESC";
        }

        // Pagination variables
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($limit < 1) {
            $limit = 10;
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        
        // Count total records
        $count_query = "SELECT COUNT(*) as total FROM view_pembayaran_hutang $where_clause";
        $count_result = mysqli_query($koneksi, $count_query);
        if ($count_result) {
            $tmp_count = mysqli_fetch_array($count_result);
            $total_records = isset($tmp_count['total']) ? (int)$tmp_count['total'] : 0;
        } else {
            $total_records = 0;
            if ($hutang_error === '') {
                $hutang_error = mysqli_error($koneksi);
            }
        }
        $total_pages = (int)ceil($total_records / $limit);
        if ($total_pages < 1) {
            $total_pages = 1;
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $offset = ($page - 1) * $limit;
        
        $hasil_cari = "Menampilkan data - Total: $total_records data (Halaman $page dari $total_pages)";
                
        $sel_trx = $trx_field !== '' ? $trx_field : "''";
        $sel_tgl = $tgl_field !== '' ? $tgl_field : "CURDATE()";
        $sel_nosup = $nosupplier_field !== '' ? $nosupplier_field : "''";
        $sel_namasup = $namasupplier_field !== '' ? $namasupplier_field : "''";
        $sel_total = $total_field !== '' ? $total_field : "0";

        $sql_query = "SELECT 
                        $sel_trx AS no_transaksi,
                        $sel_tgl AS tanggal,
                        $sel_nosup AS no_supplier,
                        $sel_namasup AS namasupplier,
                        $sel_total AS total_bayar
                     FROM view_pembayaran_hutang 
                     $where_clause
                     $order_clause 
                     LIMIT $limit OFFSET $offset";      
        // == End Default ==========
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
								<a href="#">Pembelian</a>
							</li>                            
							<li class="active">Pembayaran Hutang</li>
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
                                                <?php include "_template/_pmby_hutang_cari.php"; ?>
                                            </form>
                                        </div>
									</div>
								</div>	
							</div>
						</div>
 <div class="space space-8"></div> 
						<div class="row">
							<div class="col-xs-12 col-sm-3">
													<a href="pmby_hutang_add.php">
													<button class="btn btn-success btn-block" type="button">Input Data</button>
													</a>
							</div>
							<div class="col-xs-12 col-sm-9">
								<div class="pull-right">
									<form method="GET" style="display: inline-block; margin-right: 10px;">
										<label>Tampilkan: </label>
										<select name="limit" onchange="this.form.submit()" class="form-control" style="display: inline-block; width: auto;">
											<option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5</option>
											<option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
											<option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
											<option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
											<option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
										</select>
										<label> data per halaman</label>
									</form>
								</div>
							</div>
						</div>
 <div class="space space-8"></div> 
                        <div class="row">
							<div class="col-xs-12 col-sm-12">
								<div class="table-header">
                                    <?php echo $hasil_cari; ?>
								</div>                            
								<?php if ($hutang_error !== '') { ?>
									<div class="alert alert-danger" style="margin:10px 0;">
										<?php echo $hutang_error; ?>
									</div>
								<?php } ?>
                                <table id="dynamic-table" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td class="center" width="5%"></td>                                        
                                            <td width="15%">No. Transaksi</td>
                                            <td class="center" width="10%">Tanggal</td>
                                            <td width="10%">Kode Supplier</td>
                                            <td width="45%">Nama Supplier</td>
                                            <td align="right" width="15%">Total Bayar</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        $sql = mysqli_query($koneksi,$sql_query);
                                        if (!$sql) {
                                            $err = mysqli_error($koneksi);
                                            if ($hutang_error === '') {
                                                $hutang_error = $err;
                                            }
                                    ?>
                                        <tr>
                                            <td class="center">&nbsp;</td>
                                            <td><?php echo $err; ?></td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    <?php
                                        } else if (mysqli_num_rows($sql) < 1) {
                                    ?>
                                        <tr>
                                            <td class="center">&nbsp;</td>
                                            <td class="center">Data tidak ditemukan</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                    <?php
                                        } else {
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                            //$status_order=$tampil['status'];
					//				if($status_order=='0') {
					//					$ket_status="Open";
					//				} else {
					//					$ket_status="Closed";
					//				}
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
                                                            <a href="pmby_hutang_detail.php?snotrx=<?php echo $tampil['no_transaksi']; ?>">Detail</a>
                                                        </li>
                                                        <li>
                                                            <a target="_blank"  href="pembayaran_hutang_struk.php?snotrx=<?php echo $tampil['no_transaksi']; ?>">Cetak</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                                        
                                            </td>														
                                            <td><?php echo $tampil['no_transaksi']?></td>														
                                            <td class="center"><?php echo $tampil['tanggal']?></td>	
                                            <td><?php echo $tampil['no_supplier']?></td>														
                                            <td><?php echo $tampil['namasupplier']?></td>
                                            <td align="right"><?php echo number_format($tampil['total_bayar'],0)?></td>														                                                        													                                                        
                                        </tr>


<?php
            				}
          			}
          			?>
												</tbody>
                                </table>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <div class="text-center">
                                            <ul class="pagination">
                                                <!-- Previous -->
                                                <?php 
                                                    $params = "&txtkey=$txtkey&cbocari=$cbocari&cbourut=$cbourut&txttgl_periode=" . urlencode($txttgl_periode) . "&limit=$limit";
                                                    if(isset($_POST['btnasc']) || isset($_GET['btnasc'])) $params .= "&btnasc=1";
                                                    if(isset($_POST['btndesc']) || isset($_GET['btndesc'])) $params .= "&btndesc=1";
                                                ?>
                                                <?php if ($page > 1): ?>
                                                    <li><a href="?page=<?php echo $page-1; ?><?php echo $params; ?>">&laquo; Sebelumnya</a></li>
                                                <?php endif; ?>
                                                
                                                <!-- Page numbers -->
                                                <?php 
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);
                                                for ($i = $start_page; $i <= $end_page; $i++): 
                                                ?>
                                                    <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                                                        <a href="?page=<?php echo $i; ?><?php echo $params; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <!-- Next -->
                                                <?php if ($page < $total_pages): ?>
                                                    <li><a href="?page=<?php echo $page+1; ?><?php echo $params; ?>">Berikutnya &raquo;</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
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
					  null, null, null, null, null
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
			
			
			
				
				//initiate date range picker for ID
				if ($.fn.daterangepicker) {
					$('#id-date-range-picker-1').daterangepicker({
						'applyClass' : 'btn-sm btn-success',
						'cancelClass' : 'btn-sm btn-default',
						locale: {
							applyLabel: 'Apply',
							cancelLabel: 'Cancel',
	                        format: 'DD/MM/YYYY'
						}
					});
				}

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
