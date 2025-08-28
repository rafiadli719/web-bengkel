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

        if(isset($_POST['btnrst'])) {
            $bulan_skr= $_POST['cbobulan'];
            $thn_skr= $_POST['cbotahun'];            
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
								<a href="#">Home</a>
							</li>
							<li class="active">Dashboard</li>
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
                                        
                                                <div class="row">
                                                    <div class="col-xs-8 col-sm-2">
                                                        <select class="col-xs-8 col-sm-12" name="cbobulan" id="cbobulan">
                                                        <?php
                                                            $q = mysqli_query($koneksi,"select 
                                                                            bulan, nama, id 
                                                                            FROM bulan_transaksi 
                                                                            order by id asc");
                                                            while ($row1 = mysqli_fetch_array($q)){
                                                                $k_id           = $row1['bulan'];
                                                                $k_opis         = $row1['nama'];
                                                        ?>
                                                        <option value='<?php echo $k_id; ?>' <?php if ($k_id == $bulan_skr){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                        </select>                                                    
                                                    </div>
                                                    <div class="col-xs-8 col-sm-2">   
                                                        <select class="col-xs-8 col-sm-12" name="cbotahun" id="cbotahun">
                                                        <?php
                                                            $q = mysqli_query($koneksi,"SELECT 
                                                                                        distinct(year(tanggal)) as tahun 
                                                                                        FROM view_stok 
                                                                                        order by year(tanggal)");
                                                            while ($row1 = mysqli_fetch_array($q)){
                                                                $k_id           = $row1['tahun'];
                                                                $k_opis         = $row1['tahun'];
                                                        ?>
                                                        <option value='<?php echo $k_id; ?>' <?php if ($k_id == $thn_skr){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                        </select>                                                    
                                                    </div>
                                                    <div class="col-xs-8 col-sm-2">   
                                                        <button class="btn btn-sm btn-primary btn-block" type="submit" 
                                                        id="btnrst" name="btnrst">
                                                        Tampilkan
                                                        </button>
                                                    </div>
                                                </div>
                                        
                                            </form>
                                        
                                        </div>
									</div>
								</div>	
							</div>
						</div>

						<div class="row">
							<div class="col-xs-12 col-sm-8">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">
                                            <h4><b><font color="blue">Penjualan & Services</font></b></h4>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <td bgcolor="gainsboro" width="10%"><b>Kode</b></td>
                                                        <td bgcolor="gainsboro" width="30%"><b>Nama Cabang</b></td>
                                                        <td bgcolor="gainsboro" width="15%"><b>Tipe</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="15%"><b>Penjualan</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="15%"><b>Service</b></td>                                                        
                                                        <td bgcolor="gainsboro" align="right" width="15%"><b>Total</b></td>
                                                    <tr>
                                                </thead>
                                                <tbody>
												<?php 
                                                    $total_jual=0;
                                                    $total_service=0;
                                                    $total_penjualan=0;
                                                    
													$sql = mysqli_query($koneksi,"SELECT * FROM tbcabang");
													while ($tampil = mysqli_fetch_array($sql)) {
                                                        $kode_cabang=$tampil['kode_cabang'];
                                                        $tipe_cabang=$tampil['tipe_cabang'];
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        cabang_tipe 
                                                                                        FROM tbcabang_tipe 
                                                                                        WHERE id='$tipe_cabang'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $cabang_tipe=$tm_cari['cabang_tipe'];				                                                                

                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_akhir) as tot_jual, 
                                                                                        sum(pembayaran) as tot_bayar 
                                                                                        FROM tblpenjualan_header 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_jual=$tm_cari['tot_jual'];				                                                                                                                        
                                                        $tot_bayar=$tm_cari['tot_bayar'];				                                                                                                                                                                                
                                                        $total_jual=$total_jual+$tot_jual;
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_grand) as tot_jual 
                                                                                        FROM tblservice 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_service=$tm_cari['tot_jual'];	
                                                        $total_service=$total_service+$tot_service;
                                                    
                                                        $penjualan_total=$tot_jual+$tot_service;
                                                        $total_penjualan=$total_penjualan+$penjualan_total;
                                                    ?>
													<tr>
                                                        <td><?php echo $tampil['kode_cabang']?></td>														
														<td><?php echo $tampil['nama_cabang']?></td>														
														<td><?php echo $cabang_tipe; ?></td>														                                                        
                                                        <td align="right"><?php echo number_format($tot_jual,0)?></td>
                                                        <td align="right"><?php echo number_format($tot_service,0)?></td>
                                                        <td align="right"><?php echo number_format($penjualan_total,0)?></td>                                                        
                                                    </tr>
                                                    <?php
                                                            }
                                                    ?>
													<tr>
                                                        <td bgcolor="blue" colspan="3" align="right"><b><font color="white">Total Penjualan Keseluruhan Cabang &nbsp;</font></b></td>														                                                        
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($total_jual,0)?></font></b></td>
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($total_service,0)?></font></b></td>
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($total_penjualan,0)?></font></b></td>                                                        
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
									</div>
								</div>	
							</div>
							<div class="col-xs-12 col-sm-4">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">
                                            <h4><b><font color="blue">Total Piutang</font></b></h4>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <td bgcolor="gainsboro" width="60%"><b>Nama Cabang</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="40%"><b>Total Piutang</b></td>
                                                    <tr>
                                                </thead>
                                                <tbody>
												<?php 
                                                    $piutang_keseluruhan=0;                                                    
													$sql = mysqli_query($koneksi,"SELECT * FROM tbcabang");
													while ($tampil = mysqli_fetch_array($sql)) {
                                                        $kode_cabang=$tampil['kode_cabang'];
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_akhir) as tot_jual, 
                                                                                        sum(pembayaran) as tot_bayar 
                                                                                        FROM tblpenjualan_header 
                                                                                        WHERE kd_cabang='$kode_cabang' and 
                                                                                        carabayar='Kredit' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_jualk=$tm_cari['tot_jual'];				                                                                                                                        
                                                        $tot_bayark=$tm_cari['tot_bayar'];				                                                                                                                                                                                
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_bayar) as tot 
                                                                                        FROM tblpiutang_header 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_bayar_piutang=$tm_cari['tot'];	

                                                        $total_piutang=$tot_jualk-($tot_bayark+$tot_bayar_piutang);
                                                        $piutang_keseluruhan=$piutang_keseluruhan+$total_piutang;

                                                    ?>
													<tr>
														<td><?php echo $tampil['nama_cabang']?></td>														
                                                        <td align="right"><?php echo number_format($total_piutang,0)?></td>
                                                    </tr>
                                                    <?php
                                                            }
                                                    ?>
													<tr>
                                                        <td bgcolor="blue" align="right"><b><font color="white">Piutang Keseluruhan Cabang</font></b></td>														                                                        
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($piutang_keseluruhan,0)?></font></b></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
									</div>
								</div>	
							</div>
							<div class="col-xs-12 col-sm-8">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">
                                            <h4><b><font color="red">Pembelian</font></b></h4>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <td bgcolor="gainsboro" width="10%"><b>Kode</b></td>
                                                        <td bgcolor="gainsboro" width="30%"><b>Nama Cabang</b></td>
                                                        <td bgcolor="gainsboro" width="15%"><b>Tipe</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="45%"><b>Pembelian</b></td>
                                                    <tr>
                                                </thead>
                                                <tbody>
												<?php 
                                                    $total_beli=0;
                                                    
													$sql = mysqli_query($koneksi,"SELECT * FROM tbcabang");
													while ($tampil = mysqli_fetch_array($sql)) {
                                                        $kode_cabang=$tampil['kode_cabang'];
                                                        $tipe_cabang=$tampil['tipe_cabang'];
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        cabang_tipe 
                                                                                        FROM tbcabang_tipe 
                                                                                        WHERE id='$tipe_cabang'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $cabang_tipe=$tm_cari['cabang_tipe'];				                                                                

                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_beli) as tot_beli 
                                                                                        FROM tblpembelian_header 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_beli=$tm_cari['tot_beli'];				                                                                                                                        

                                                        $total_beli=$total_beli+$tot_beli;
                                                    ?>
													<tr>
                                                        <td><?php echo $tampil['kode_cabang']?></td>														
														<td><?php echo $tampil['nama_cabang']?></td>														
														<td><?php echo $cabang_tipe; ?></td>														                                                        
                                                        <td align="right"><?php echo number_format($tot_beli,0)?></td>
                                                    </tr>
                                                    <?php
                                                            }
                                                    ?>
													<tr>
                                                        <td bgcolor="red" colspan="3" align="right"><b><font color="white">Total Pembelian Keseluruhan Cabang &nbsp;</font></b></td>														                                                        
                                                        <td bgcolor="red" align="right"><b><font color="white"><?php echo number_format($total_beli,0)?></font></b></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
									</div>
								</div>	
							</div>
							<div class="col-xs-12 col-sm-4">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">
                                            <h4><b><font color="red">Total Hutang</font></b></h4>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <td bgcolor="gainsboro" width="60%"><b>Nama Cabang</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="40%"><b>Total Hutang</b></td>
                                                    <tr>
                                                </thead>
                                                <tbody>
												<?php 
                                                    $hutang_keseluruhan=0;                                                    
													$sql = mysqli_query($koneksi,"SELECT * FROM tbcabang");
													while ($tampil = mysqli_fetch_array($sql)) {
                                                        $kode_cabang=$tampil['kode_cabang'];
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_akhir) as tot_jual, 
                                                                                        sum(pembayaran) as tot_bayar 
                                                                                        FROM tblpembelian_header 
                                                                                        WHERE kd_cabang='$kode_cabang' and 
                                                                                        carabayar='Kredit' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_belik=$tm_cari['tot_jual'];				                                                                                                                        
                                                        $tot_bayarblk=$tm_cari['tot_bayar'];				                                                                                                                                                                                
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_bayar) as tot 
                                                                                        FROM tblhutang_header 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_bayar_hutang=$tm_cari['tot'];	

                                                        $total_hutang=$tot_belik-($tot_bayarblk+$tot_bayar_hutang);
                                                        $hutang_keseluruhan=$hutang_keseluruhan+$total_hutang;

                                                    ?>
													<tr>
														<td><?php echo $tampil['nama_cabang']?></td>														
                                                        <td align="right"><?php echo number_format($total_hutang,0)?></td>
                                                    </tr>
                                                    <?php
                                                            }
                                                    ?>
													<tr>
                                                        <td bgcolor="red" align="right"><b><font color="white">Hutang Keseluruhan Cabang</font></b></td>														                                                        
                                                        <td bgcolor="red" align="right"><b><font color="white"><?php echo number_format($hutang_keseluruhan,0)?></font></b></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
									</div>
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
		<script src="assets/js/jquery-ui.custom.min.js"></script>
		<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
		<script src="assets/js/moment.min.js"></script>
		<script src="assets/js/fullcalendar.min.js"></script>
		<script src="assets/js/bootbox.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

<?php
include "../config/config.php"; // connection file with database
$query = "SELECT status, jumlah FROM view_graph_empl_status_rst"; // get the records on which pie chart is to be drawn
$getData = $connection->query($query);
?>
<script>
    // Build the chart
    Highcharts.chart('container', {
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: 'Employee Status'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        accessibility: {
            point: {
                valueSuffix: '%'
            }
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
					format: '<b>{point.name}</b>: {point.y} ({point.percentage:.1f}%)',
                },
                showInLegend: true
            }
        },
        series: [{
            name: 'Percentage',
            colorByPoint: true,
            data: [
                <?php
                $data = '';
                if ($getData->num_rows>0){
                    while ($row = $getData->fetch_object()){
                        $data.='{ name:"'.$row->status.'",y:'.$row->jumlah.'},';
                    }
                }
                echo $data;
                ?>
            ]
        }]
    });
</script>

<?php
include "../config/config.php"; // connection file with database
$query = "SELECT nama_divisi, jumlah FROM view_graph_empl_depts"; // get the records on which pie chart is to be drawn
$getData = $connection->query($query);
?>
<script>
    // Build the chart
    Highcharts.chart('container1', {
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: 'Head Count by Divisi'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        accessibility: {
            point: {
                valueSuffix: '%'
            }
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
					format: '<b>{point.name}</b>: {point.y} ({point.percentage:.1f}%)',
                },
                showInLegend: true
            }
        },
        series: [{
            name: 'Percentage',
            colorByPoint: true,
            data: [
                <?php
                $data = '';
                if ($getData->num_rows>0){
                    while ($row = $getData->fetch_object()){
                        $data.='{ name:"'.$row->nama_divisi.'",y:'.$row->jumlah.'},';
                    }
                }
                echo $data;
                ?>
            ]
        }]
    });
</script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {

/* initialize the external events
	-----------------------------------------------------------------*/

	$('#external-events div.external-event').each(function() {

		// create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
		// it doesn't need to have a start or end
		var eventObject = {
			title: $.trim($(this).text()) // use the element's text as the event title
		};

		// store the Event Object in the DOM element so we can get to it later
		$(this).data('eventObject', eventObject);

		// make the event draggable using jQuery UI
		$(this).draggable({
			zIndex: 999,
			revert: true,      // will cause the event to go back to its
			revertDuration: 0  //  original position after the drag
		});
		
	});




	/* initialize the calendar
	-----------------------------------------------------------------*/

	var date = new Date();
	var d = date.getDate();
	var m = date.getMonth();
	var y = date.getFullYear();


	var calendar = $('#calendar').fullCalendar({
		//isRTL: true,
		//firstDay: 1,// >> change first day of week 
		
		buttonHtml: {
			prev: '<i class="ace-icon fa fa-chevron-left"></i>',
			next: '<i class="ace-icon fa fa-chevron-right"></i>'
		},
	
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay'
		},
		events: [

		]
		,
		
		/**eventResize: function(event, delta, revertFunc) {

			alert(event.title + " end is now " + event.end.format());

			if (!confirm("is this okay?")) {
				revertFunc();
			}

		},*/
		
		editable: true,
		droppable: true, // this allows things to be dropped onto the calendar !!!
		drop: function(date) { // this function is called when something is dropped
		
			// retrieve the dropped element's stored Event Object
			var originalEventObject = $(this).data('eventObject');
			var $extraEventClass = $(this).attr('data-class');
			
			
			// we need to copy it, so that multiple events don't have a reference to the same object
			var copiedEventObject = $.extend({}, originalEventObject);
			
			// assign it the date that was reported
			copiedEventObject.start = date;
			copiedEventObject.allDay = false;
			if($extraEventClass) copiedEventObject['className'] = [$extraEventClass];
			
			// render the event on the calendar
			// the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
			$('#calendar').fullCalendar('renderEvent', copiedEventObject, true);
			
			// is the "remove after drop" checkbox checked?
			if ($('#drop-remove').is(':checked')) {
				// if so, remove the element from the "Draggable Events" list
				$(this).remove();
			}
			
		}
		,
		selectable: true,
		selectHelper: true,
		select: function(start, end, allDay) {
			
			bootbox.prompt("New Event Title:", function(title) {
				if (title !== null) {
					calendar.fullCalendar('renderEvent',
						{
							title: title,
							start: start,
							end: end,
							allDay: allDay,
							className: 'label-info'
						},
						true // make the event "stick"
					);
				}
			});
			

			calendar.fullCalendar('unselect');
		}
		,
		eventClick: function(calEvent, jsEvent, view) {

			//display a modal
			var modal = 
			'<div class="modal fade">\
			  <div class="modal-dialog">\
			   <div class="modal-content">\
				 <div class="modal-body">\
				   <button type="button" class="close" data-dismiss="modal" style="margin-top:-10px;">&times;</button>\
				   <form class="no-margin">\
					  <label>Change event name &nbsp;</label>\
					  <input class="middle" autocomplete="off" type="text" value="' + calEvent.title + '" />\
					 <button type="submit" class="btn btn-sm btn-success"><i class="ace-icon fa fa-check"></i> Save</button>\
				   </form>\
				 </div>\
				 <div class="modal-footer">\
					<button type="button" class="btn btn-sm btn-danger" data-action="delete"><i class="ace-icon fa fa-trash-o"></i> Delete Event</button>\
					<button type="button" class="btn btn-sm" data-dismiss="modal"><i class="ace-icon fa fa-times"></i> Cancel</button>\
				 </div>\
			  </div>\
			 </div>\
			</div>';
		
		
			var modal = $(modal).appendTo('body');
			modal.find('form').on('submit', function(ev){
				ev.preventDefault();

				calEvent.title = $(this).find("input[type=text]").val();
				calendar.fullCalendar('updateEvent', calEvent);
				modal.modal("hide");
			});
			modal.find('button[data-action=delete]').on('click', function() {
				calendar.fullCalendar('removeEvents' , function(ev){
					return (ev._id == calEvent._id);
				})
				modal.modal("hide");
			});
			
			modal.modal('show').on('hidden', function(){
				modal.remove();
			});


			//console.log(calEvent.id);
			//console.log(jsEvent);
			//console.log(view);

			// change the border color just for fun
			//$(this).css('border-color', 'red');

		}
		
	});


})
		</script>
		
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.js'></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"
            integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    events: [<?php 

    //melakukan koneksi ke database
    $koneksi    = mysqli_connect('localhost', 'root', '', 'db_payroll');
    //mengambil data dari tabel jadwal
    $data       = mysqli_query($koneksi,'select id, tgl, keterangan FROM tblibur');
    //melakukan looping
    while($d = mysqli_fetch_array($data)){     
?>
{
    title: '<?php echo $d['keterangan']; ?>', //menampilkan title dari tabel
    start: '<?php echo $d['tgl']; ?>', //menampilkan tgl mulai dari tabel
    end: '<?php echo $d['tgl']; ?>' //menampilkan tgl selesai dari tabel
},
<?php } ?> ],
                    selectOverlap: function (event) {
                        return event.rendering === 'background';
                    }
                });
    
                calendar.render();
            });
        </script>
		
	</body>
</html>

<?php 
	}
?>
