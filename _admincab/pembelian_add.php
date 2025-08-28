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

		include "function_pembelian.php";
		$LastID=FormatNoTrans(OtomatisID());	

        $txtcaribrg="";
        $txtnamaitem="";
        $tgl_pilih=date('d/m/Y');
        $cbo_supplier="";
        $nopesanan="";        
        $tot="0";
        $total_qty_beli="0";
        $total_qty_order="0";
        
		if(isset($_POST['btncari_pesanan'])) {
			$txtcaribrg= $_POST['txtcaribrg'];	
            $tgl_pilih= $_POST['id-date-picker-1'];
            $cbo_supplier= $_POST['cbosupplier'];
            $nopesanan= $_POST['txtnopesanan'];

            // == Cari Data Pesanan ==
            if($nopesanan<>'') {
                $data = mysqli_query($koneksi,"SELECT 
                                                no_order 
                                                FROM 
                                                tblorder_header 
                                                WHERE 
                                                no_order='$nopesanan' and 
                                                status='0'");
                $cek = mysqli_num_rows($data);                
                if($cek > 0){
                    
                    // Cari data Supplier
                    $cari_kd=mysqli_query($koneksi,"SELECT 
                                                    no_supplier 
                                                    FROM 
                                                    tblorder_header 
                                                    WHERE 
                                                    no_order='$nopesanan'");			
                    $tm_cari=mysqli_fetch_array($cari_kd);
                    $cbo_supplier=$tm_cari['no_supplier'];	
                // End Cari Pelanggan
                
                // Pindahkan Data Pesanan ke pebelian
                    $sql = mysqli_query($koneksi,"SELECT * FROM tblorder_detail 
                                                WHERE no_order='$nopesanan'");
                    while ($tampil = mysqli_fetch_array($sql)) {
                        $no_item=$tampil['no_item'];
                        $txthargabarang=$tampil['harga_pokok'];
                        $txtqty=$tampil['quantity']; 
                        //$txtpot=$tampil['potongan'];
                        $subtotal=$tampil['total'];                       

                        mysqli_query($koneksi,"INSERT INTO tblpembelian_detail 
                                                (no_transaksi, no_item, harga_pokok, 
                                                quantity, qty_order, potongan, total, 
                                                user, kd_cabang) 
                                                VALUES 
                                                ('', '$no_item','$txthargabarang',
                                                '$txtqty','$txtqty','0','$subtotal',
                                                '$_nama','$kd_cabang')");                          
                    }
                // End ====
    
                } else {
                    $kdbrg="";
                    $cbo_supplier="";
                    echo"<script>window.alert('No. Pesanan Tidak ada atau sudah dipakai!');
                    window.location=('pembelian_add_rst.php?stgl=$tgl_pilih&ssup=$cbo_supplier&kd=$kdbrg&spesan=$nopesanan');</script>";			                                
                }
            }
            
            // == Total dari Item Barang ==============
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                sum(total) as tot, 
                                                sum(qty_order) as tot_qty_order, 
                                                sum(quantity) as tot_qty_beli 
                                                FROM tblpembelian_detail 
                                                WHERE 
                                                user='$_nama' and 
                                                kd_cabang='$kd_cabang' and 
                                                status_trx='0'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];                 
                $total_qty_order=$tm_cari['tot_qty_order'];                 
                $total_qty_beli=$tm_cari['tot_qty_beli'];                                               
        }
        
		if(isset($_POST['btncari'])) {				
			$txtcaribrg= $_POST['txtcaribrg'];	
            $tgl_pilih= $_POST['id-date-picker-1'];
            $cbo_supplier= $_POST['cbosupplier'];
            $nopesanan= $_POST['txtnopesanan'];
            
            $cari_kd=mysqli_query($koneksi,"SELECT count(noitem) as tot 
                                            FROM view_cari_item 
                                            WHERE 
                                            noitem='$txtcaribrg'");			
            $tm_cari=mysqli_fetch_array($cari_kd);
            $tot_cari=$tm_cari['tot'];        
            
            if($tot_cari=='1') {
                $cari_kd=mysqli_query($koneksi,"SELECT namaitem 
                                        FROM view_cari_item 
                                        WHERE 
                                        noitem='$txtcaribrg'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $txtnamaitem=$tm_cari['namaitem'];
                $txtcaribrg="$txtcaribrg";
            } else {
                $cbocari="";
                $cbourut="35";
                echo"<script>
                    localStorage.setItem('activeTab', '#item-barang');
                    window.location=('pembelian_add_item_cari.php?stgl=$tgl_pilih&ssup=$cbo_supplier&spesan=$nopesanan&_key=$txtcaribrg&_cari=$cbocari&_urut=$cbourut&_flt=asc');
                </script>";
            }

                                                
            // == Total dari Item Barang ==============
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                sum(total) as tot, 
                                                sum(qty_order) as tot_qty_order, 
                                                sum(quantity) as tot_qty_beli 
                                                FROM tblpembelian_detail 
                                                WHERE 
                                                user='$_nama' and 
                                                kd_cabang='$kd_cabang' and 
                                                status_trx='0'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];                 
                $total_qty_order=$tm_cari['tot_qty_order'];                 
                $total_qty_beli=$tm_cari['tot_qty_beli'];                                 
        }            

        if(isset($_POST['btnadd'])) {	
			$txtkdbarang= $_POST['txtcaribrg'];
			$txtqty= $_POST['txtqty'];
            $txtpot= $_POST['txtpot'];
            $tgl_pilih= $_POST['id-date-picker-1'];
            $cbo_supplier= $_POST['cbosupplier'];
            $nopesanan= $_POST['txtnopesanan'];
            
            $cari_kd=mysqli_query($koneksi,"SELECT hargapokok FROM tblitem WHERE noitem='$txtkdbarang'");			
            $tm_cari=mysqli_fetch_array($cari_kd);
            $txthargabarang=$tm_cari['hargapokok'];        

            $subtotal=($txthargabarang*$txtqty)-(($txthargabarang*$txtqty)*($txtpot/100));
            //$subtotal=$txthargabarang*$txtqty;
            
            if($txtkdbarang<>'') {

                $data = mysqli_query($koneksi,"SELECT * FROM tblpembelian_detail 
                                                WHERE 
                                                user='$_nama' and kd_cabang='$kd_cabang' 
                                                and no_item='$txtkdbarang' and 
                                                status_trx='0'");
                $cek = mysqli_num_rows($data);
                if($cek > 0){
                    $kdbrg="";
                    echo"<script>window.alert('Item Barang sudah ada!');
                    window.location=('pembelian_add_rst.php?stgl=$tgl_pilih&ssup=$cbo_supplier&kd=$kdbrg&spesan=$nopesanan');</script>";			                                
                } else {                
                    mysqli_query($koneksi,"INSERT INTO tblpembelian_detail 
                                            (no_transaksi, no_item, harga_pokok, quantity, 
                                            potongan, total, user, kd_cabang) 
                                            VALUES 
                                            ('', '$txtkdbarang','$txthargabarang',
                                            '$txtqty','$txtpot','$subtotal',
                                            '$_nama','$kd_cabang')");  

                  
                }

            // == Total dari Item Barang ==============
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                sum(total) as tot, 
                                                sum(qty_order) as tot_qty_order, 
                                                sum(quantity) as tot_qty_beli 
                                                FROM tblpembelian_detail 
                                                WHERE 
                                                user='$_nama' and 
                                                kd_cabang='$kd_cabang' and 
                                                status_trx='0'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];                 
                $total_qty_order=$tm_cari['tot_qty_order'];                 
                $total_qty_beli=$tm_cari['tot_qty_beli'];                                  
        
                $txtcaribrg = "";
                $txtnamaitem= "";        
            } else {
                $txtcaribrg = "";
                $txtnamaitem= "";        
            }
        }     

        if(isset($_POST['btnsimpan'])) {
            $txttotal_harga= $_POST['txttotal_harga'];            
            if($txttotal_harga=='0') {
                echo"<script>window.alert('Belum ada Item barang yang dipilih. Transaksi tidak dapat disimpan!');window.location=('pesanan_pembelian_add.php');</script>";			                            
            } else {
            // insert ke order header                
                date_default_timezone_set('Asia/Jakarta');
                $waktuaja_skr=date('h:i');
                function ubahformatTgl($tanggal) {
                    $pisah = explode('/',$tanggal);
                    $urutan = array($pisah[2],$pisah[1],$pisah[0]);
                    $satukan = implode('-',$urutan);
                    return $satukan;
                }
                
                $txttglpesan = ubahformatTgl($_POST['id-date-picker-1']); 
                $nopesanan= $_POST['txtnopesanan'];
                $cbosupplier= $_POST['cbosupplier'];
                $txttotal_harga= $_POST['txttotal_harga'];
                $cbocarabyr= $_POST['cbocarabyr'];                
                $txtsyarat= $_POST['txtsyarat'];                                
                $txtnote= $_POST['txtnote'];                                                
                $txtpotfaktur_persen= $_POST['txtpotfaktur_persen'];  
                $txtpotfaktur_nom= $_POST['txtpotfaktur_nom'];   
                $txtpajak_persen= $_POST['txtpajak_persen'];   
                $txtpajak_nom= $_POST['txtpajak_nom'];   
                $txtnet= $_POST['txtnet'];   
                $txtdp= $_POST['txtdp'];   
                $txtkekurangan= $_POST['txtkekurangan'];
            
                $cari_kd=mysqli_query($koneksi,"SELECT sum(quantity) as tot 
                                                FROM tblpembelian_detail 
                                                WHERE 
                                                user='$_nama' and 
                                                kd_cabang='$kd_cabang' and 
                                                status_trx='0'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot_qty=$tm_cari['tot'];                 
                             

//                $data = mysqli_query($koneksi,"SELECT * FROM tblpembelian_header 
//                                                WHERE 
//                                                notransaksi='$LastID'");
//                $cek = mysqli_num_rows($data);
//                if($cek > 0){
                    
//                } else {
                mysqli_query($koneksi,"INSERT INTO tblpembelian_header 
                                        (notransaksi, status, carabayar, 
                                        tanggal, no_order, tanggal_order, 
                                        no_supplier, note, total_qty_order, 
                                        total_qty, total_beli, 
                                        diskon, total_diskon, 
                                        pajak, total_pajak, 
                                        total_akhir, total_retur, pembayaran, 
                                        tanggal_jt, tanggal_lunas, 
                                        jumlah_bayar, user, kd_cabang, 
                                        lama_hari) 
                                        VALUES 
                                        ('$LastID','Pembelian','$cbocarabyr',
                                        '$txttglpesan','$nopesanan','',
                                        '$cbosupplier','$txtnote','',
                                        '$tot_qty','$txttotal_harga',
                                        '$txtpotfaktur_persen','$txtpotfaktur_nom',
                                        '$txtpajak_persen','$txtpajak_nom',
                                        '$txtnet','','$txtdp',
                                        '','',
                                        '$txtkekurangan',
                                        '$_nama','$kd_cabang', 
                                        '$txtsyarat')");

                mysqli_query($koneksi,"UPDATE tblpembelian_detail 
                                        SET 
                                        no_transaksi='$LastID', status_trx='1' 
                                        WHERE 
                                        user='$_nama' and 
                                        kd_cabang='$kd_cabang' and 
                                        status_trx='0'");

                $sql = mysqli_query($koneksi,"SELECT * FROM tblpembelian_detail 
                                                WHERE no_transaksi='$LastID'");
                while ($tampil = mysqli_fetch_array($sql)) {
                    $no_item=$tampil['no_item'];
                    $qty=$tampil['quantity'];
                    mysqli_query($koneksi,"INSERT INTO tbstok 
                                        (tipe, no_transaksi, no_item, 
                                        tanggal, masuk, keluar, keterangan, 
                                        kd_cabang) 
                                        VALUES 
                                        ('2','$LastID','$no_item',
                                        '$txttglpesan','$qty','0',
                                        'Pembelian','$kd_cabang')"); 

                    if($nopesanan<>'') {
                        mysqli_query($koneksi,"UPDATE tblorder_detail 
                                                SET 
                                                qty_terima='$qty' 
                                                WHERE 
                                                no_order='$nopesanan' and 
                                                no_item='$no_item'");                        
                    }                                        
                }

                if($nopesanan<>'') {
                        mysqli_query($koneksi,"UPDATE tblorder_header 
                                                SET 
                                                status='1' 
                                                WHERE 
                                                no_order='$nopesanan'");                                            
                }
                                
                echo"<script>window.location=('pembelian_cetak.php?nopesanan=$LastID');</script>";                            
//                }

                
            }
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

<?php include "menu_pembelian02.php"; ?>

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
								<a href="pembelian.php">Pembelian</a>
							</li>                            
							<li class="active">Tambah Data</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">

                        <form class="form-horizontal" action="" method="post" role="form">
                        <input type="hidden" name="txttotal_harga"  class="form-control" value="<?php echo $tot; ?>"/>
                        <input type="hidden" name="active_tab" id="active_tab" value="purchase-details"/>
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-blue widget-header-flat">
										<h4 class="widget-title lighter">
											<i class="ace-icon fa fa-truck orange"></i>
											Pembelian #<?php echo $LastID; ?>
										</h4>
                                        <div class="widget-toolbar">
                                            <span class="label label-info arrowed-in arrowed-in-right">Purchase Details</span>
                                        </div>
									</div>

									<div class="widget-body">
										<div class="widget-main padding-12 no-padding-left no-padding-right">
											<div class="tabbable">
												<ul class="nav nav-tabs" id="myTab">
													<li class="active">
														<a data-toggle="tab" href="#purchase-details" aria-expanded="true">
															<i class="green ace-icon fa fa-list-alt bigger-120"></i>
															Purchase Details
														</a>
													</li>

													<li class="">
														<a data-toggle="tab" href="#purchase-items" aria-expanded="false">
															<i class="blue ace-icon fa fa-truck bigger-120"></i>
															Item Barang
														</a>
													</li>

													<li class="">
														<a data-toggle="tab" href="#purchase-payment" aria-expanded="false">
															<i class="orange ace-icon fa fa-credit-card bigger-120"></i>
															Payment Information
														</a>
													</li>
												</ul>

												<div class="tab-content">
													<div id="purchase-details" class="tab-pane fade active in">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<div class="row">
																		<div class="col-xs-12 col-sm-6">
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> No. Transaksi :</label>									
																				<div class="col-sm-8">
																					<input type="text" class="form-control" 
																					value="<?php echo $LastID; ?>" readonly="true" />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Tanggal :</label>									
																				<div class="col-sm-8">
																					<div class="input-group">
																						<input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" 
																						value="<?php echo $tgl_pilih; ?>" data-date-format="dd/mm/yyyy" />
																						<span class="input-group-addon">
																							<i class="fa fa-calendar bigger-110"></i>
																						</span>
																					</div>
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> No. Pesanan :</label>									
																				<div class="col-sm-8">
																					<div class="input-group">
																						<input type="text" class="form-control" id="txtnopesanan" name="txtnopesanan" 
																						value="<?php echo $nopesanan; ?>" placeholder="No. Pesanan Pembelian" />
																						<div class="input-group-btn">
																							<button type="submit" class="btn btn-default no-border btn-sm" 
																							id="btncari_pesanan" name="btncari_pesanan">
																							<i class="ace-icon fa fa-check icon-on-right bigger-110"></i>
																							</button>
																						</div>
																					</div>     
																				</div>
																			</div>
																		</div>
																		<div class="col-xs-12 col-sm-6">
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> User :</label>									
																				<div class="col-sm-9">
																					<input type="text" class="form-control" 
																					value="<?php echo $_nama; ?>" disabled />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> Supplier :</label>									
																				<div class="col-sm-9">
																					<select class="form-control" name="cbosupplier" id="cbosupplier" required >
																					<option value="">- Pilih Supplier -</option>
																					<?php
																						$q = mysqli_query($koneksi,"select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc");
																						while ($row1 = mysqli_fetch_array($q)){
																							$k_id           = $row1['nosupplier'];
																							$k_opis         = $row1['namasupplier'];
																					?>
																					<option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbo_supplier){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
																					<?php
																						}
																					?>
																					</select>
																				</div>
																			</div>
																		</div>
																	</div>
																	
																	<div class="hr hr-24"></div>
																	
																	<div class="row">
																		<div class="col-xs-6">
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Cara Bayar </label>
																				<div class="col-sm-8">
																					<select class="form-control" id="cbocarabyr" name="cbocarabyr">
																						<option value="Tunai">Tunai</option>
																						<option value="Kredit">Kredit</option>
																					</select>
																				</div>
																			</div>
																		</div>
																		<div class="col-xs-6">
																			<div class="form-group">
																				<label class="col-sm-7 control-label no-padding-right"> Jml. Pesan </label>
																				<div class="col-sm-5">
																					<input type="text" class="form-control" value="<?php echo $total_qty_order; ?>" readonly="true" />
																				</div>
																			</div>
																		</div>
																		<div class="col-xs-6">
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Syarat </label>
																				<div class="col-sm-6">
																					<input type="text" class="form-control" id="txtsyarat" name="txtsyarat" placeholder="Syarat pembayaran" />
																				</div>
																				<label class="col-sm-2 control-label no-padding-right"> hari </label>                                                        
																			</div>
																		</div>
																		<div class="col-xs-6">
																			<div class="form-group">
																				<label class="col-sm-7 control-label no-padding-right"> Jml. Beli </label>
																				<div class="col-sm-5">
																					<input type="text" class="form-control" value="<?php echo $total_qty_beli; ?>" readonly="true" />
																				</div>
																			</div>
																		</div>
																		<div class="col-xs-12">
																			<div class="form-group">
																				<label class="col-sm-2 control-label no-padding-right"> Keterangan </label>
																				<div class="col-sm-10">
																					<textarea class="form-control" id="txtnote" name="txtnote" rows="3" placeholder="Keterangan pembelian..."></textarea>
																				</div>
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>
													</div>

													<div id="purchase-items" class="tab-pane fade">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<?php include "_template/_pembelian_detail.php"; ?>
																</div>
															</div>
														</div>
													</div>

													<div id="purchase-payment" class="tab-pane fade">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<div class="row">
																		<div class="col-sm-6">
																			<h5 class="blue">
																				<i class="ace-icon fa fa-credit-card"></i>
																				Informasi Pembayaran
																			</h5>
																			<div class="space-6"></div>
																		</div>
																	</div>
																	
																	<?php include "_template/_pembelian_total.php"; ?>
																	
																	<div class="hr hr-24"></div>
																	
																	<div class="row">
																		<div class="col-xs-3">
																			<button class="btn btn-success btn-block" type="submit" 
																			id="btnsimpan" name="btnsimpan">
																				<i class="ace-icon fa fa-save"></i>
																				Simpan
																			</button>                                                
																		</div>
																		<div class="col-xs-3">
																			<a href="pembelian_batal.php?suser=<?php echo $_nama; ?>&scabang=<?php echo $kd_cabang; ?>" 
																			onclick="return confirm('Inputan Pembelian akan dibatalkan. Lanjutkan?')">                                                                    
																				<button class="btn btn-warning btn-block" type="button">
																					<i class="ace-icon fa fa-times"></i>
																					Batal
																				</button>
																			</a>                                                
																		</div>
																		<div class="col-xs-3">
																			<button class="btn btn-info btn-block disabled" type="button" 
																			id="btncetak" name="btncetak">
																				<i class="ace-icon fa fa-print"></i>
																				Cetak
																			</button>                                                
																		</div>
																		<div class="col-xs-3">
																			<a href="pembelian.php">
																			<button class="btn btn-default btn-block" type="button">
																				<i class="ace-icon fa fa-arrow-left"></i>
																				Tutup
																			</button>  
																			</a>
																		</div>                                                
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
                        </div>
                        </form> 
                        
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

		<!--[if lte IE 8]>
		  <script src="assets/js/excanvas.min.js"></script>
		<![endif]-->
		<script src="assets/js/jquery-ui.custom.min.js"></script>
		<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
		<script src="assets/js/chosen.jquery.min.js"></script>
		<script src="assets/js/spinbox.min.js"></script>
		<script src="assets/js/bootstrap-datepicker.min.js"></script>
		<script src="assets/js/bootstrap-timepicker.min.js"></script>
		<script src="assets/js/moment.min.js"></script>
		<script src="assets/js/daterangepicker.min.js"></script>
		<script src="assets/js/bootstrap-datetimepicker.min.js"></script>
		<script src="assets/js/bootstrap-colorpicker.min.js"></script>
		<script src="assets/js/jquery.knob.min.js"></script>
		<script src="assets/js/autosize.min.js"></script>
		<script src="assets/js/jquery.inputlimiter.min.js"></script>
		<script src="assets/js/jquery.maskedinput.min.js"></script>
		<script src="assets/js/bootstrap-tag.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
				// Tab persistence - maintain active tab on page reload
				function saveActiveTab() {
					var activeTab = $('.nav-tabs li.active a').attr('href');
					if (activeTab) {
						localStorage.setItem('activeTab', activeTab);
						$('#active_tab').val(activeTab.replace('#', ''));
					}
				}
				
				function restoreActiveTab() {
					var activeTab = localStorage.getItem('activeTab');
					var urlParams = new URLSearchParams(window.location.search);
					var targetTab = urlParams.get('tab');
					
					// Priority: URL parameter > localStorage
					if (targetTab) {
						$('.nav-tabs a[href="#' + targetTab + '"]').tab('show');
						localStorage.setItem('activeTab', '#' + targetTab);
					} else if (activeTab) {
						$('.nav-tabs a[href="' + activeTab + '"]').tab('show');
					}
				}
				
				// Save active tab when clicked
				$('.nav-tabs a').on('click', function() {
					saveActiveTab();
				});
				
				// Restore active tab on page load
				restoreActiveTab();
				$('#id-disable-check').on('click', function() {
					var inp = $('#form-input-readonly').get(0);
					if(inp.hasAttribute('disabled')) {
						inp.setAttribute('readonly' , 'true');
						inp.removeAttribute('disabled');
						inp.value="This text field is readonly!";
					}
					else {
						inp.setAttribute('disabled' , 'disabled');
						inp.removeAttribute('readonly');
						inp.value="This text field is disabled!";
					}
				});
			
			
				if(!ace.vars['touch']) {
					$('.chosen-select').chosen({allow_single_deselect:true}); 
					//resize the chosen on window resize
			
					$(window)
					.off('resize.chosen')
					.on('resize.chosen', function() {
						$('.chosen-select').each(function() {
							 var $this = $(this);
							 $this.next().css({'width': $this.parent().width()});
						})
					}).trigger('resize.chosen');
					//resize chosen on sidebar collapse/expand
					$(document).on('settings.ace.chosen', function(e, event_name, event_val) {
						if(event_name != 'sidebar_collapsed') return;
						$('.chosen-select').each(function() {
							 var $this = $(this);
							 $this.next().css({'width': $this.parent().width()});
						})
					});
			
			
					$('#chosen-multiple-style .btn').on('click', function(e){
						var target = $(this).find('input[type=radio]');
						var which = parseInt(target.val());
						if(which == 2) $('#form-field-select-4').addClass('tag-input-style');
						 else $('#form-field-select-4').removeClass('tag-input-style');
					});
				}
			
			
				$('[data-rel=tooltip]').tooltip({container:'body'});
				$('[data-rel=popover]').popover({container:'body'});
			
				autosize($('textarea[class*=autosize]'));
				
				$('textarea.limited').inputlimiter({
					remText: '%n character%s remaining...',
					limitText: 'max allowed : %n.'
				});
			
				$.mask.definitions['~']='[+-]';
				$('.input-mask-date').mask('99/99/9999');
				$('.input-mask-phone').mask('(999) 999-9999');
				$('.input-mask-eyescript').mask('~9.99 ~9.99 999');
				$(".input-mask-product").mask("a*-999-a999",{placeholder:" ",completed:function(){alert("You typed the following: "+this.val());}});
			
			
			
				$( "#input-size-slider" ).css('width','200px').slider({
					value:1,
					range: "min",
					min: 1,
					max: 8,
					step: 1,
					slide: function( event, ui ) {
						var sizing = ['', 'input-sm', 'input-lg', 'input-mini', 'input-small', 'input-medium', 'input-large', 'input-xlarge', 'input-xxlarge'];
						var val = parseInt(ui.value);
						$('#form-field-4').attr('class', sizing[val]).attr('placeholder', '.'+sizing[val]);
					}
				});
			
				$( "#input-span-slider" ).slider({
					value:1,
					range: "min",
					min: 1,
					max: 12,
					step: 1,
					slide: function( event, ui ) {
						var val = parseInt(ui.value);
						$('#form-field-5').attr('class', 'col-xs-'+val).val('.col-xs-'+val);
					}
				});
			
			
				
				//"jQuery UI Slider"
				//range slider tooltip example
				$( "#slider-range" ).css('height','200px').slider({
					orientation: "vertical",
					range: true,
					min: 0,
					max: 100,
					values: [ 17, 67 ],
					slide: function( event, ui ) {
						var val = ui.values[$(ui.handle).index()-1] + "";
			
						if( !ui.handle.firstChild ) {
							$("<div class='tooltip right in' style='display:none;left:16px;top:-6px;'><div class='tooltip-arrow'></div><div class='tooltip-inner'></div></div>")
							.prependTo(ui.handle);
						}
						$(ui.handle.firstChild).show().children().eq(1).text(val);
					}
				}).find('span.ui-slider-handle').on('blur', function(){
					$(this.firstChild).hide();
				});
				
				
				$( "#slider-range-max" ).slider({
					range: "max",
					min: 1,
					max: 10,
					value: 2
				});
				
				$( "#slider-eq > span" ).css({width:'90%', 'float':'left', margin:'15px'}).each(function() {
					// read initial values from markup and remove that
					var value = parseInt( $( this ).text(), 10 );
					$( this ).empty().slider({
						value: value,
						range: "min",
						animate: true
						
					});
				});
				
				$("#slider-eq > span.ui-slider-purple").slider('disable');//disable third item
			
				
				$('#id-input-file-1 , #id-input-file-2').ace_file_input({
					no_file:'No File ...',
					btn_choose:'Choose',
					btn_change:'Change',
					droppable:false,
					onchange:null,
					thumbnail:false //| true | large
					//whitelist:'gif|png|jpg|jpeg'
					//blacklist:'exe|php'
					//onchange:''
					//
				});
				//pre-show a file name, for example a previously selected file
				//$('#id-input-file-1').ace_file_input('show_file_list', ['myfile.txt'])
			
			
				$('#id-input-file-3').ace_file_input({
					style: 'well',
					btn_choose: 'Drop files here or click to choose',
					btn_change: null,
					no_icon: 'ace-icon fa fa-cloud-upload',
					droppable: true,
					thumbnail: 'small'//large | fit
					//,icon_remove:null//set null, to hide remove/reset button
					/**,before_change:function(files, dropped) {
						//Check an example below
						//or examples/file-upload.html
						return true;
					}*/
					/**,before_remove : function() {
						return true;
					}*/
					,
					preview_error : function(filename, error_code) {
						//name of the file that failed
						//error_code values
						//1 = 'FILE_LOAD_FAILED',
						//2 = 'IMAGE_LOAD_FAILED',
						//3 = 'THUMBNAIL_FAILED'
						//alert(error_code);
					}
			
				}).on('change', function(){
					//console.log($(this).data('ace_input_files'));
					//console.log($(this).data('ace_input_method'));
				});
				
				
				//$('#id-input-file-3')
				//.ace_file_input('show_file_list', [
					//{type: 'image', name: 'name of image', path: 'http://path/to/image/for/preview'},
					//{type: 'file', name: 'hello.txt'}
				//]);
			
				
				
			
				//dynamically change allowed formats by changing allowExt && allowMime function
				$('#id-file-format').removeAttr('checked').on('change', function() {
					var whitelist_ext, whitelist_mime;
					var btn_choose
					var no_icon
					if(this.checked) {
						btn_choose = "Drop images here or click to choose";
						no_icon = "ace-icon fa fa-picture-o";
			
						whitelist_ext = ["jpeg", "jpg", "png", "gif" , "bmp"];
						whitelist_mime = ["image/jpg", "image/jpeg", "image/png", "image/gif", "image/bmp"];
					}
					else {
						btn_choose = "Drop files here or click to choose";
						no_icon = "ace-icon fa fa-cloud-upload";
						
						whitelist_ext = null;//all extensions are acceptable
						whitelist_mime = null;//all mimes are acceptable
					}
					var file_input = $('#id-input-file-3');
					file_input
					.ace_file_input('update_settings',
					{
						'btn_choose': btn_choose,
						'no_icon': no_icon,
						'allowExt': whitelist_ext,
						'allowMime': whitelist_mime
					})
					file_input.ace_file_input('reset_input');
					
					file_input
					.off('file.error.ace')
					.on('file.error.ace', function(e, info) {
						//console.log(info.file_count);//number of selected files
						//console.log(info.invalid_count);//number of invalid files
						//console.log(info.error_list);//a list of errors in the following format
						
						//info.error_count['ext']
						//info.error_count['mime']
						//info.error_count['size']
						
						//info.error_list['ext']  = [list of file names with invalid extension]
						//info.error_list['mime'] = [list of file names with invalid mimetype]
						//info.error_list['size'] = [list of file names with invalid size]
						
						
						/**
						if( !info.dropped ) {
							//perhapse reset file field if files have been selected, and there are invalid files among them
							//when files are dropped, only valid files will be added to our file array
							e.preventDefault();//it will rest input
						}
						*/
						
						
						//if files have been selected (not dropped), you can choose to reset input
						//because browser keeps all selected files anyway and this cannot be changed
						//we can only reset file field to become empty again
						//on any case you still should check files with your server side script
						//because any arbitrary file can be uploaded by user and it's not safe to rely on browser-side measures
					});
					
					
					/**
					file_input
					.off('file.preview.ace')
					.on('file.preview.ace', function(e, info) {
						console.log(info.file.width);
						console.log(info.file.height);
						e.preventDefault();//to prevent preview
					});
					*/
				
				});
			
				$('#spinner1').ace_spinner({value:0,min:0,max:200,step:10, btn_up_class:'btn-info' , btn_down_class:'btn-info'})
				.closest('.ace-spinner')
				.on('changed.fu.spinbox', function(){
					//console.log($('#spinner1').val())
				}); 
				$('#spinner2').ace_spinner({value:0,min:0,max:10000,step:100, touch_spinner: true, icon_up:'ace-icon fa fa-caret-up bigger-110', icon_down:'ace-icon fa fa-caret-down bigger-110'});
				$('#spinner3').ace_spinner({value:0,min:-100,max:100,step:10, on_sides: true, icon_up:'ace-icon fa fa-plus bigger-110', icon_down:'ace-icon fa fa-minus bigger-110', btn_up_class:'btn-success' , btn_down_class:'btn-danger'});
				$('#spinner4').ace_spinner({value:0,min:-100,max:100,step:10, on_sides: true, icon_up:'ace-icon fa fa-plus', icon_down:'ace-icon fa fa-minus', btn_up_class:'btn-purple' , btn_down_class:'btn-purple'});
			
				//$('#spinner1').ace_spinner('disable').ace_spinner('value', 11);
				//or
				//$('#spinner1').closest('.ace-spinner').spinner('disable').spinner('enable').spinner('value', 11);//disable, enable or change value
				//$('#spinner1').closest('.ace-spinner').spinner('value', 0);//reset to 0
			
			
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true
				})
				//show datepicker when clicking on the icon
				.next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
			
				//or change it into a date range picker
				$('.input-daterange').datepicker({autoclose:true});
			
			
				//to translate the daterange picker, please copy the "examples/daterange-fr.js" contents here before initialization
				$('input[name=date-range-picker]').daterangepicker({
					'applyClass' : 'btn-sm btn-success',
					'cancelClass' : 'btn-sm btn-default',
					locale: {
						applyLabel: 'Apply',
						cancelLabel: 'Cancel',
					}
				})
				.prev().on(ace.click_event, function(){
					$(this).next().focus();
				});
			
			
				$('#timepicker1').timepicker({
					minuteStep: 1,
					showSeconds: true,
					showMeridian: false,
					disableFocus: true,
					icons: {
						up: 'fa fa-chevron-up',
						down: 'fa fa-chevron-down'
					}
				}).on('focus', function() {
					$('#timepicker1').timepicker('showWidget');
				}).next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
				
				
			
				
				if(!ace.vars['old_ie']) $('#date-timepicker1').datetimepicker({
				 //format: 'MM/DD/YYYY h:mm:ss A',//use this option to display seconds
				 icons: {
					time: 'fa fa-clock-o',
					date: 'fa fa-calendar',
					up: 'fa fa-chevron-up',
					down: 'fa fa-chevron-down',
					previous: 'fa fa-chevron-left',
					next: 'fa fa-chevron-right',
					today: 'fa fa-arrows ',
					clear: 'fa fa-trash',
					close: 'fa fa-times'
				 }
				}).next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
				
			
				$('#colorpicker1').colorpicker();
				//$('.colorpicker').last().css('z-index', 2000);//if colorpicker is inside a modal, its z-index should be higher than modal'safe
			
				$('#simple-colorpicker-1').ace_colorpicker();
				//$('#simple-colorpicker-1').ace_colorpicker('pick', 2);//select 2nd color
				//$('#simple-colorpicker-1').ace_colorpicker('pick', '#fbe983');//select #fbe983 color
				//var picker = $('#simple-colorpicker-1').data('ace_colorpicker')
				//picker.pick('red', true);//insert the color if it doesn't exist
			
			
				$(".knob").knob();
				
				
				var tag_input = $('#form-field-tags');
				try{
					tag_input.tag(
					  {
						placeholder:tag_input.attr('placeholder'),
						//enable typeahead by specifying the source array
						source: ace.vars['US_STATES'],//defined in ace.js >> ace.enable_search_ahead
						/**
						//or fetch data from database, fetch those that match "query"
						source: function(query, process) {
						  $.ajax({url: 'remote_source.php?q='+encodeURIComponent(query)})
						  .done(function(result_items){
							process(result_items);
						  });
						}
						*/
					  }
					)
			
					//programmatically add/remove a tag
					var $tag_obj = $('#form-field-tags').data('tag');
					$tag_obj.add('Programmatically Added');
					
					var index = $tag_obj.inValues('some tag');
					$tag_obj.remove(index);
				}
				catch(e) {
					//display a textarea for old IE, because it doesn't support this plugin or another one I tried!
					tag_input.after('<textarea id="'+tag_input.attr('id')+'" name="'+tag_input.attr('name')+'" rows="3">'+tag_input.val()+'</textarea>').remove();
					//autosize($('#form-field-tags'));
				}
				
				
				/////////
				$('#modal-form input[type=file]').ace_file_input({
					style:'well',
					btn_choose:'Drop files here or click to choose',
					btn_change:null,
					no_icon:'ace-icon fa fa-cloud-upload',
					droppable:true,
					thumbnail:'large'
				})
				
				//chosen plugin inside a modal will have a zero width because the select element is originally hidden
				//and its width cannot be determined.
				//so we set the width after modal is show
				$('#modal-form').on('shown.bs.modal', function () {
					if(!ace.vars['touch']) {
						$(this).find('.chosen-container').each(function(){
							$(this).find('a:first-child').css('width' , '210px');
							$(this).find('.chosen-drop').css('width' , '210px');
							$(this).find('.chosen-search input').css('width' , '200px');
						});
					}
				})
				/**
				//or you can activate the chosen plugin after modal is shown
				//this way select element becomes visible with dimensions and chosen works as expected
				$('#modal-form').on('shown', function () {
					$(this).find('.modal-chosen').chosen();
				})
				*/
			
				
				
				$(document).one('ajaxloadstart.page', function(e) {
					autosize.destroy('textarea[class*=autosize]')
					
					$('.limiterBox,.autosizejs').remove();
					$('.daterangepicker.dropdown-menu,.colorpicker.dropdown-menu,.bootstrap-datetimepicker-widget.dropdown-menu').remove();
				});
			
			});
		</script>

<script type="text/javascript">
    $(document).ready(function() {
        $("#txtpotfaktur_persen").keyup(function() {
            var subtotal = $("#txttotal").val();
            var potfkt_persen  = $("#txtpotfaktur_persen").val();
            var txtpajak_nom = $("#txtpajak_nom").val();
                        
            var potfkt_nom = (parseInt(potfkt_persen))/100 * parseInt(subtotal);
            var net = parseInt(subtotal)-parseInt(potfkt_nom)+parseInt(txtpajak_nom);
            var kekurangan = parseInt(net)-parseInt(net);
            
            $("#txtpotfaktur_nom").val(potfkt_nom);
            $("#txtnet").val(net);
            $("#txtnet1").val(net);
            $("#txtdp").val(net);   
            $("#txtkekurangan").val(kekurangan);            
            $("#txtkekurangan1").val(kekurangan);                                             
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $("#txtpotfaktur_nom").keyup(function() {
            var subtotal = $("#txttotal").val();
            var potfkt_nom  = $("#txtpotfaktur_nom").val();
            var txtpajak_nom = $("#txtpajak_nom").val();
            
            var potfkt_persen = (parseInt(potfkt_nom)/parseInt(subtotal))*100;
            var net = parseInt(subtotal)-parseInt(potfkt_nom)+parseInt(txtpajak_nom);
            var kekurangan = parseInt(net)-parseInt(net);
            
            $("#txtpotfaktur_persen").val(potfkt_persen);
            $("#txtnet").val(net);
            $("#txtnet1").val(net); 
            $("#txtdp").val(net);              
            $("#txtkekurangan").val(kekurangan);            
            $("#txtkekurangan1").val(kekurangan);                                    
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $("#txtpajak_persen").keyup(function() {
            var subtotal = $("#txttotal").val();
            var potfaktur_nom = $("#txtpotfaktur_nom").val();
            var pajak_persen  = $("#txtpajak_persen").val();
            
            var pajak_nom = (parseInt(pajak_persen)/100)*parseInt(subtotal);
            var net = parseInt(subtotal)-parseInt(potfaktur_nom)+parseInt(pajak_nom);
            var kekurangan = parseInt(net)-parseInt(net);
            
            $("#txtpajak_nom").val(pajak_nom);
            $("#txtpajak_nom1").val(pajak_nom);
            $("#txtnet").val(net);
            $("#txtnet1").val(net);         
$("#txtdp").val(net);   
            $("#txtkekurangan").val(kekurangan);            
            $("#txtkekurangan1").val(kekurangan);                                                
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $("#txtdp").keyup(function() {
            var net = $("#txtnet").val();
            var txtdp = $("#txtdp").val();
            var kekurangan = parseInt(net)-parseInt(txtdp);
            
            $("#txtkekurangan").val(kekurangan);            
            $("#txtkekurangan1").val(kekurangan);                                                
        });
    });
</script>

	</body>
</html>

<?php 
	}
?>
