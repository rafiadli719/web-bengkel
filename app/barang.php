<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];		
		$kd_cabang=$_SESSION['_cabang'];		                		
		include "../config/koneksi.php";
        include "../config/koneksi1.php";
        
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
        $txturut="34";
        $txtflt="asc";
        $tipebtn1="btn-danger";
        $tipebtn2="btn-primary";
        
 // ------- Total Data ----------
		$cari_kd=mysqli_query($koneksi,"SELECT count(noitem) as tot FROM view_cari_item");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$total_item=$tm_cari['tot'];				        
        
        // Count ORI/NON-ORI items
        $ori_count_query = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='ORI' AND statusitem='1'");
        $ori_count = mysqli_fetch_array($ori_count_query)['count'];
        
        $nonori_count_query = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='NON_ORI' AND statusitem='1'");
        $nonori_count = mysqli_fetch_array($nonori_count_query)['count'];
        
        $pending_count_query = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE status_validasi='pending_validation' AND statusitem='1'");
        $pending_count = mysqli_fetch_array($pending_count_query)['count'];
        
        $total_item_brg=number_format($total_item,0);
        $hasil_cari="Total Item: ".$total_item_brg." | ORI: ".number_format($ori_count,0)." | NON-ORI: ".number_format($nonori_count,0)." | Pending: ".number_format($pending_count,0);
        
		if(isset($_POST['btnasc'])) {				
			$txtkey= mysqli_real_escape_string($koneksi, $_POST['txtkey']);	
			$cbocari= mysqli_real_escape_string($koneksi, $_POST['cbocari']);	
			$cbourut= mysqli_real_escape_string($koneksi, $_POST['cbourut']);
			echo"<script>window.location=('barang_rst.php?_key=$txtkey&_cari=$cbocari&_urut=$cbourut&_flt=asc');</script>";            
        }

		if(isset($_POST['btndesc'])) {				
			$txtkey= mysqli_real_escape_string($koneksi, $_POST['txtkey']);	
			$cbocari= mysqli_real_escape_string($koneksi, $_POST['cbocari']);	
			$cbourut= mysqli_real_escape_string($koneksi, $_POST['cbourut']);
			echo"<script>window.location=('barang_rst.php?_key=$txtkey&_cari=$cbocari&_urut=$cbourut&_flt=desc');</script>";            
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
		<style>
		.label-ori { background-color: #5cb85c; }
		.label-nonori { background-color: #f0ad4e; }
		.text-ori { color: #5cb85c; font-weight: bold; }
		.text-nonori { color: #f0ad4e; font-weight: bold; }
		.ori-row { border-left: 3px solid #5cb85c; }
		.nonori-row { border-left: 3px solid #f0ad4e; }
		</style>

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
								<a href="#">Data Master</a>
							</li>                            
							<li>
								<a href="#">Daftar Item</a>
							</li>                                                        
							<li class="active">Master Barang</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">

                        <div class="space space-8"></div>
						<div class="row">
							<div class="col-xs-12 col-sm-12">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">	
                                            <form class="form-horizontal" action="" method="post" role="form">
                                                <?php include "_template/cari-barang-header.php"; ?>
                                            </form>
                                        </div>
									</div>
								</div>	
							</div>
						</div>
                        <!-- ORI/NON-ORI Filter -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="alert alert-info">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Filter Tipe Item:</strong>
                                            <div class="btn-group" data-toggle="buttons">
                                                <label class="btn btn-sm btn-primary active">
                                                    <input type="radio" name="filter_tipe" value="all" checked> Semua
                                                </label>
                                                <label class="btn btn-sm btn-success">
                                                    <input type="radio" name="filter_tipe" value="ORI"> ORI
                                                </label>
                                                <label class="btn btn-sm btn-warning">
                                                    <input type="radio" name="filter_tipe" value="NON_ORI"> NON-ORI
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Filter Status:</strong>
                                            <div class="btn-group" data-toggle="buttons">
                                                <label class="btn btn-sm btn-primary active">
                                                    <input type="radio" name="filter_status" value="all" checked> Semua
                                                </label>
                                                <label class="btn btn-sm btn-success">
                                                    <input type="radio" name="filter_status" value="validated"> Validated
                                                </label>
                                                <label class="btn btn-sm btn-warning">
                                                    <input type="radio" name="filter_status" value="pending"> Pending
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="pull-right">
                                                <strong>Legenda:</strong>
                                                <span class="label label-success">ORI</span> = Genuine Part &nbsp;
                                                <span class="label label-warning">NON-ORI</span> = Aftermarket/Imitasi
                                            </div>
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
									<div class="pull-right">
										<a href="barang_add_improved.php" class="btn btn-success btn-sm">
											<i class="ace-icon fa fa-plus"></i>
											Tambah Item (ORI/NON-ORI)
										</a>
										<a href="barang_list_improved.php" class="btn btn-info btn-sm">
											<i class="ace-icon fa fa-list"></i>
											View Card Mode
										</a>
									</div>
									<div class="clearfix"></div>
								</div>                           
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td class="center" width="4%"></td>
                                            <td class="center" width="3%">No</td>
                                            <td width="6%">Tipe</td>
                                            <td width="8%">Kode Item</td>
                                            <td width="8%">Kode Barcode</td>
                                            <td width="12%">Nama Item</td>
                                            <td width="7%">Jenis</td>
                                            <td width="6%">Satuan</td>
                                            <td width="6%">Rak</td>
                                            <td width="6%">Harga Pokok</td> 
                                            <td width="4%">Stok Min.</td>
                                            <td width="4%">Stok Max.</td>
                                            <td width="4%">Stok Akhir</td>
                                            <td width="10%">Applicable Motors</td>
                                            <td width="6%">Status</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      <?php                                      
                                          $page = (isset($_GET['page']))? (int) $_GET['page'] : 1;
                                          $limit = 50; // Reduced from 100 to 50 for better performance
                                          $limitStart = ($page - 1) * $limit;
                                          
                                          // Optimized query with better indexing and reduced JOINs
                                          $SqlQuery = mysqli_query($con, "SELECT 
                                              vci.*, 
                                              COALESCE(i.tipe_item, 'NON_ORI') as tipe_item,
                                              COALESCE(i.merek, '') as merek,
                                              COALESCE(i.kode_part_resmi, '') as kode_part_resmi,
                                              COALESCE(i.penggunaan_motor, '') as penggunaan_motor,
                                              COALESCE(i.kategori_rak, '') as kategori_rak,
                                              COALESCE(i.status_validasi, 'pending_validation') as status_validasi,
                                              (SELECT GROUP_CONCAT(tm.tipe ORDER BY tm.tipe SEPARATOR ', ') 
                                               FROM tblitem_spart sp 
                                               INNER JOIN tbtipe_motor tm ON sp.kode_tipe = tm.kode_tipe 
                                               WHERE sp.noitem = vci.noitem 
                                               LIMIT 5) as applicable_motors,
                                              (SELECT COUNT(DISTINCT sp2.kode_tipe) 
                                               FROM tblitem_spart sp2 
                                               WHERE sp2.noitem = vci.noitem) as motor_count
                                              FROM view_cari_item vci
                                              LEFT JOIN tblitem i ON vci.noitem = i.noitem 
                                              ORDER BY vci.noitem DESC 
                                              LIMIT ".$limitStart.",".$limit);                                      
                                          $no = $limitStart + 1;

                                          // Pre-fetch stock data for all items to reduce queries
                                          $item_codes = [];
                                          $temp_results = [];
                                          while($SqlQuery && $row = mysqli_fetch_array($SqlQuery)) {
                                              $item_codes[] = "'" . mysqli_real_escape_string($koneksi, $row['noitem']) . "'";
                                              $temp_results[] = $row;
                                          }
                                          
                                          // Bulk fetch stock data
                                          $stock_data_cache = [];
                                          if (!empty($item_codes)) {
                                              $stock_query = "SELECT noitem, stokmin, stok_maks, stok_awal, rakbarang 
                                                             FROM tblitem_stok 
                                                             WHERE noitem IN (" . implode(',', $item_codes) . ") 
                                                             AND kode_cabang='$kd_cabang'";
                                              $stock_result = mysqli_query($koneksi, $stock_query);
                                              while($stock_row = mysqli_fetch_assoc($stock_result)) {
                                                  $stock_data_cache[$stock_row['noitem']] = $stock_row;
                                              }
                                              
                                              // Bulk fetch rack names
                                              $rack_ids = [];
                                              foreach($stock_data_cache as $stock) {
                                                  if (!empty($stock['rakbarang'])) {
                                                      $rack_ids[] = $stock['rakbarang'];
                                                  }
                                              }
                                              $rack_cache = [];
                                              if (!empty($rack_ids)) {
                                                  $rack_query = "SELECT id, rak_barang FROM tbrakbarang WHERE id IN (" . implode(',', array_unique($rack_ids)) . ")";
                                                  $rack_result = mysqli_query($koneksi, $rack_query);
                                                  while($rack_row = mysqli_fetch_assoc($rack_result)) {
                                                      $rack_cache[$rack_row['id']] = $rack_row['rak_barang'];
                                                  }
                                              }
                                              
                                              // Bulk fetch final stock
                                              $final_stock_query = "SELECT no_item, saldo 
                                                                   FROM view_stok_master 
                                                                   WHERE no_item IN (" . implode(',', $item_codes) . ") 
                                                                   AND kd_cabang='$kd_cabang'";
                                              $final_stock_result = mysqli_query($koneksi, $final_stock_query);
                                              $final_stock_cache = [];
                                              while($final_stock_row = mysqli_fetch_assoc($final_stock_result)) {
                                                  $final_stock_cache[$final_stock_row['no_item']] = $final_stock_row['saldo'];
                                              }
                                          }
                                          
                                          // Now loop through cached results
                                          foreach($temp_results as $row) {
                                            $noitem=$row['noitem'];
                                            $tipe_item = $row['tipe_item'];
                                            $status_validasi = $row['status_validasi'];
                                            
                                            // Get from cache
                                            $stock_info = $stock_data_cache[$noitem] ?? null;
                                            $stokmin = $stock_info['stokmin'] ?? '';
                                            $stok_maks = $stock_info['stok_maks'] ?? '';
                                            $stok_awal = $stock_info['stok_awal'] ?? '';
                                            $rakbarang = $stock_info['rakbarang'] ?? '';
                                            $rak_barang = $rack_cache[$rakbarang] ?? '';
                                            $stok_akhir = $final_stock_cache[$noitem] ?? '';
                                          ?>
                                        <tr class="<?php echo ($tipe_item == 'ORI') ? 'ori-row' : 'nonori-row'; ?>">
                                            <td>
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown" class="btn dropdown-toggle btn-minier btn-yellow">
                                                        Aksi
                                                        <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-default">
                                                        <li>
                                                            <a href="barang_edit_improved.php?kd=<?php echo $row['noitem']?>">Edit Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="barang_del.php?kd=<?php echo $row['noitem']?>" 
                                                            onclick="return confirm('Data Barang akan dihapus. Lanjutkan?')">Hapus</a>
                                                        </li>
                                                        <?php if ($status_validasi == 'pending_validation'): ?>
                                                        <li>
                                                            <a href="barang_validate.php?kd=<?php echo $row['noitem']?>">Validasi Item</a>
                                                        </li>
                                                        <?php endif; ?>
                                                        <li class="divider"></li>
                                                        <li>
                                                            <a target="_blank" href="barang_stok_akhir.php?kd=<?php echo $row['noitem']?>">Stok Akhir</a>
                                                        </li>
                                                        <li>
                                                            <a target="_blank" href="barang_kartu_stok.php?kd=<?php echo $row['noitem']?>">Kartu Stok</a>
                                                        </li>
                                                        <li class="divider"></li>
                                                        <li>
                                                            <a target="_blank" href="barang_history_hp.php?kd=<?php echo $row['noitem']?>">History Harga Pokok</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                            
                                            </td>
                                            <td align="center"><?php echo $no++; ?></td>
                                            <td>
                                                <?php 
                                                if ($tipe_item == 'ORI') {
                                                    echo '<span class="label label-success">ORI</span>';
                                                } else {
                                                    echo '<span class="label label-warning">NON-ORI</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo $row['noitem']; ?>
                                                <?php if ($tipe_item == 'ORI' && !empty($row['kode_part_resmi'])): ?>
                                                    <br><small class="text-muted">Part: <?php echo $row['kode_part_resmi']; ?></small>
                                                <?php endif; ?>
                                                <?php if ($tipe_item == 'NON_ORI' && !empty($row['kategori_rak'])): ?>
                                                    <br><small class="text-muted">Cat: <?php echo $row['kategori_rak']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['kodebarcode']; ?></td>
                                            <td>
                                                <?php echo $row['namaitem']; ?>
                                                <?php if ($tipe_item == 'ORI' && !empty($row['merek'])): ?>
                                                    <br><small class="text-primary"><?php echo $row['merek']; ?></small>
                                                <?php endif; ?>
                                                <?php if ($tipe_item == 'NON_ORI' && !empty($row['penggunaan_motor'])): ?>
                                                    <br><small class="text-info"><?php echo $row['penggunaan_motor']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['namajenis']; ?></td>
                                            <td><?php echo $row['satuan']; ?></td>
                                            <td><?php echo $rak_barang; ?></td>
                                            <td align="right"><?php echo number_format($row['hargapokok'],0); ?></td>
                                            <td><?php echo $stokmin; ?></td>
                                            <td><?php echo $stok_maks; ?></td>
                                            <td><?php echo $stok_akhir; ?></td>
                                            <td>
                                                <?php 
                                                $applicable_motors = $row['applicable_motors'];
                                                $motor_count = intval($row['motor_count'] ?? 0);
                                                
                                                if (!empty($applicable_motors)) {
                                                    if ($motor_count <= 3) {
                                                        echo '<small>' . $applicable_motors . '</small>';
                                                    } else {
                                                        $motors_array = explode(', ', $applicable_motors);
                                                        $first_three = array_slice($motors_array, 0, 3);
                                                        echo '<small>' . implode(', ', $first_three) . '</small>';
                                                        echo '<br><small class="text-muted">+' . ($motor_count - 3) . ' lainnya</small>';
                                                    }
                                                } else {
                                                    echo '<small class="text-muted">Tidak ada data</small>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                switch($status_validasi) {
                                                    case 'validated':
                                                        echo '<span class="label label-success">Validated</span>';
                                                        break;
                                                    case 'pending_validation':
                                                        echo '<span class="label label-warning">Pending</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="label label-danger">Rejected</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="label label-default">Unknown</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php           
                                          }
                                      ?>
                                    </tbody>      
                                    
                                </table>

  <div align="right">
    <ul class="pagination">
      <?php
      // Jika page = 1, maka LinkPrev disable
      if($page == 1){ 
      ?>        
        <!-- link Previous Page disable --> 
        <li class="disabled"><a href="#">Previous</a></li>
      <?php
      }
      else{ 
        $LinkPrev = ($page > 1)? $page - 1 : 1;
      ?>
        <!-- link Previous Page --> 
        <li><a href="barang.php?page=<?php echo $LinkPrev; ?>">Previous</a></li>
      <?php
        }
      ?>

      <?php
      $SqlQuery = mysqli_query($con, "SELECT * FROM view_cari_item");        
      
      //Hitung semua jumlah data yang berada pada tabel Sisawa
      $JumlahData = mysqli_num_rows($SqlQuery);
      
      // Hitung jumlah halaman yang tersedia
      $jumlahPage = ceil($JumlahData / $limit); 
      
      // Jumlah link number 
      $jumlahNumber = 1; 

      // Untuk awal link number
      $startNumber = ($page > $jumlahNumber)? $page - $jumlahNumber : 1; 
      
      // Untuk akhir link number
      $endNumber = ($page < ($jumlahPage - $jumlahNumber))? $page + $jumlahNumber : $jumlahPage; 
      
      for($i = $startNumber; $i <= $endNumber; $i++){
        $linkActive = ($page == $i)? ' class="active"' : '';
      ?>
        <li<?php echo $linkActive; ?>><a href="barang.php?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
      <?php
        }
      ?>
      
      <!-- link Next Page -->
      <?php       
      if($page == $jumlahPage){ 
      ?>
        <li class="disabled"><a href="#">Next</a></li>
      <?php
      }
      else{
        $linkNext = ($page < $jumlahPage)? $page + 1 : $jumlahPage;
      ?>
        <li><a href="barang.php?page=<?php echo $linkNext; ?>">Next</a></li>
      <?php
      }
      ?>
    </ul>
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
			
			// ORI/NON-ORI Filter functionality
			$('input[name="filter_tipe"], input[name="filter_status"]').change(function() {
				var filterTipe = $('input[name="filter_tipe"]:checked').val();
				var filterStatus = $('input[name="filter_status"]:checked').val();
				
				$('table tbody tr').each(function() {
					var row = $(this);
					var showRow = true;
					
					// Filter by type
					if (filterTipe !== 'all') {
						var hasOri = row.hasClass('ori-row');
						var hasNonOri = row.hasClass('nonori-row');
						
						if (filterTipe === 'ORI' && !hasOri) {
							showRow = false;
						} else if (filterTipe === 'NON_ORI' && !hasNonOri) {
							showRow = false;
						}
					}
					
					// Filter by status (you can enhance this based on your needs)
					if (filterStatus !== 'all' && showRow) {
						var statusLabel = row.find('td:last .label').text().toLowerCase();
						if (filterStatus === 'validated' && statusLabel !== 'validated') {
							showRow = false;
						} else if (filterStatus === 'pending' && statusLabel !== 'pending') {
							showRow = false;
						}
					}
					
					if (showRow) {
						row.show();
					} else {
						row.hide();
					}
				});
			});
		</script>
	</body>
</html>

<?php 
	}
?>