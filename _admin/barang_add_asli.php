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

		//include "funcion_dokter.php";
		//$LastID=FormatNoTrans(OtomatisID());		                
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
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>		
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

<?php include "menu_master01a.php"; ?>

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
							<li>
								<a href="barang.php">Master Barang</a>
							</li>                                                                                    
							<li class="active">Tambah Data</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<br>
                        <form name="formPendaftaran" class="form-horizontal" action="save_barang.php" method="post" onsubmit="return validateForm()">
						<div class="row">
							<div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Kode </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtkd" name="txtkd" class="col-xs-10 col-sm-12" 
                                        required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Kode Barcode </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtbarcode" name="txtbarcode" class="col-xs-10 col-sm-12" 
                                        autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Nama Item </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtnama" name="txtnama" class="col-xs-10 col-sm-12" 
                                        required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Jenis </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbojenis" id="cbojenis" required >
                                        <option value="">- Pilih -</option>
                                        <?php
                                            $sql="select jenis, namajenis FROM tblitemjenis";
                                            $sql_row=mysqli_query($koneksi,$sql);
                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                            {
                                        ?>
                                        <option value="<?php echo $sql_res["jenis"]; ?>"><?php echo $sql_res["namajenis"]; ?></option>
                                        <?php
                                            }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Satuan </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbosatuan" id="cbosatuan" required >
                                        <option value="">- Pilih -</option>
                                        <?php
                                            $sql="select satuan, namasatuan FROM tblitemsatuan";
                                            $sql_row=mysqli_query($koneksi,$sql);
                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                            {
                                        ?>
                                        <option value="<?php echo $sql_res["satuan"]; ?>"><?php echo $sql_res["namasatuan"]; ?></option>
                                        <?php
                                            }
                                        ?>
                                        </select>
                                    </div>
                                </div>                                
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Pabrik </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbopabrik" id="cbopabrik" required >
                                        <?php
                                            $sql="select id, pabrik_barang FROM tbpabrik_barang";
                                            $sql_row=mysqli_query($koneksi,$sql);
                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                            {
                                        ?>
                                        <option value="<?php echo $sql_res["id"]; ?>"><?php echo $sql_res["pabrik_barang"]; ?></option>
                                        <?php
                                            }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Status Produk </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbostatus" id="cbostatus" required >
                                        <?php
                                            $sql="select id, status FROM tbstatus_produk";
                                            $sql_row=mysqli_query($koneksi,$sql);
                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                            {
                                        ?>
                                        <option value="<?php echo $sql_res["id"]; ?>"><?php echo $sql_res["status"]; ?></option>
                                        <?php
                                            }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Tipe Item </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbotipe" id="cbotipe" 
                                        required onchange="enabledisabletext()" >
                                        <?php
                                            $sql="select id, tipe FROM tbtipe_item";
                                            $sql_row=mysqli_query($koneksi,$sql);
                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                            {
                                        ?>
                                        <option value="<?php echo $sql_res["id"]; ?>"><?php echo $sql_res["tipe"]; ?></option>
                                        <?php
                                            }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Barang+Jasa </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbojasa" id="cbojasa" >
                                        <option value="">- Pilih -</option>
                                        <?php
                                            $sql="select jasa, nilai, id FROM tbhjual_jasa";
                                            $sql_row=mysqli_query($koneksi,$sql);
                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                            {
                                        ?>
                                        <option value="<?php echo $sql_res["id"]; ?>">
                                        <?php echo $sql_res["jasa"]; ?>&nbsp;(<?php echo number_format($sql_res['nilai'],0)?>)
                                        </option>
                                        <?php
                                            }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Keterangan </label>
                                    <div class="col-sm-8">
                                        <textarea class="col-xs-10 col-sm-12" id="txtnote" name="txtnote" rows="2"></textarea>
                                    </div>
                                </div>                                
                            </div>
                            <div class="col-xs-6">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Supplier 1 </label>
                                                    <div class="col-sm-8">
                                                        <select class="col-xs-10 col-sm-12" name="cbosupplier1" id="cbosupplier1" >
                                                        <option value="">- Pilih -</option>
                                                        <?php
                                                            $sql="select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc";
                                                            $sql_row=mysqli_query($koneksi,$sql);
                                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                                            {
                                                        ?>
                                                        <option value="<?php echo $sql_res["nosupplier"]; ?>"><?php echo $sql_res["namasupplier"]; ?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                        </select>
                                                    </div>
                                                </div>                                            
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Supplier 2 </label>
                                                    <div class="col-sm-8">
                                                        <select class="col-xs-10 col-sm-12" name="cbosupplier2" id="cbosupplier2" >
                                                        <option value="">- Pilih -</option>
                                                        <?php
                                                            $sql="select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc";
                                                            $sql_row=mysqli_query($koneksi,$sql);
                                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                                            {
                                                        ?>
                                                        <option value="<?php echo $sql_res["nosupplier"]; ?>"><?php echo $sql_res["namasupplier"]; ?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                        </select>
                                                    </div>
                                                </div>  
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Supplier 3 </label>
                                                    <div class="col-sm-8">
                                                        <select class="col-xs-10 col-sm-12" name="cbosupplier3" id="cbosupplier3" >
                                                        <option value="">- Pilih -</option>
                                                        <?php
                                                            $sql="select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc";
                                                            $sql_row=mysqli_query($koneksi,$sql);
                                                            while($sql_res=mysqli_fetch_assoc($sql_row))	
                                                            {
                                                        ?>
                                                        <option value="<?php echo $sql_res["nosupplier"]; ?>"><?php echo $sql_res["namasupplier"]; ?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                        </select>
                                                    </div>
                                                </div>
                               <hr>        
 <div class="form-group">
                                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Harga Pokok </label>
                                                    <div class="col-sm-8">
                                                        <input type="text" id="txthpokok" name="txthpokok" class="col-xs-10 col-sm-12" 
                                                        required autocomplete="off" />
                                                    </div>
                                                </div>   
                                <hr>
                               <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Harga Jual </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtqty1a" name="txtqty1a" class="col-xs-2" 
                                        disabled value="1" />
                                        <label class="col-xs-2 control-label no-padding-center" for="form-field-1"> s/d </label>
                                        <input type="text" id="txtqty1b" name="txtqty1b" class="col-xs-2" 
                                        required autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-right" for="form-field-1"> Harga &nbsp;</label>
                                        <input type="text" id="txthj1" name="txthj1" class="col-xs-4" 
                                        required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Berdasarkan Jumlah </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtqty2a" name="txtqty2a" class="col-xs-2" 
                                        required autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-center" for="form-field-1"> s/d </label>
                                        <input type="text" id="txtqty2b" name="txtqty2b" class="col-xs-2" 
                                        required autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-right" for="form-field-1"> Harga &nbsp;</label>
                                        <input type="text" id="txthj2" name="txthj2" class="col-xs-4" 
                                        required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> &nbsp; </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtqty3a" name="txtqty3a" class="col-xs-2" 
                                        required autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-center" for="form-field-1"> s/d </label>
                                        <input type="text" id="txtqty4b" name="txtqty4b" class="col-xs-2" 
                                        disabled value="Mak" />
                                        <label class="col-xs-2 control-label no-padding-right" for="form-field-1"> Harga &nbsp;</label>
                                        <input type="text" id="txthj3" name="txthj3" class="col-xs-4" 
                                        required autocomplete="off" />
                                    </div>
                                </div>
							</div>									
                        </div>
                        <div class="space space-8"></div>
						<div class="row">
							<div class="col-xs-12 col-sm-12">
                                <div class="tabbable">
                                    <ul class="nav nav-tabs padding-18 tab-size-bigger" id="myTab">
                                        <li class="active">
                                            <a data-toggle="tab" href="#faq-tab-1">Applicable Part</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content no-border padding-24">
                                        <div id="faq-tab-1" class="tab-pane fade in active">
                                            <div class="row">
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel1.php"; ?>                                                
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel2.php"; ?>                                                                                                
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel3.php"; ?>                                                                                                
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel4.php"; ?>                                                                                                
                                                </div>                                                
                                            </div>                                         
                                        </div>
                                    </div>
                                </div>                            
                            </div>
                        </div>

						<div class="row">
							<div class="col-xs-4">
                                <button class="btn btn-primary btn-block" type="submit">Simpan Barang</button>
                            </div>
							<div class="col-xs-4">
                                <a href="barang_add.php?" 
                                onclick="return confirm('Pengisian Barang dibatalkan. Anda Yakin?')">                                                                    
                                <button class="btn btn-warning btn-block" type="button">
                                    Batal
                                </button>
                                </a>                                                
                            </div>
                            <div class="col-xs-4">
                                <a href="barang.php">
                                    <button class="btn btn-primary btn-block" type="button">
                                        Tutup
                                    </button>  
                                </a>
                            </div>                                                
                        </div>
                    </form>
						
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

<script type='text/javascript'>
$(document).ready(function() {

    $('#cbotipe').change(function(){
        if($(this).val() === '1'){
            $('#cbojasa').attr('disabled', 'disabled');
        }else{
            $('#cbojasa').attr('disabled', false);
        }
    });

});
</script>

<script language="javascript">
function enabledisabletext()
{
	if(document.myform.cbotipe.value=='1')
	{
	document.myform.cbojasa.disabled=false;
	}
	if(document.myform.cbotipe.value=='2')
	{
	document.myform.cbojasa.disabled=true;
	}
}
</script>

<script>
        function validateForm() {
            if (document.forms["formPendaftaran"]["txtqty2a"].value == "") {
                alert("Nama Tidak Boleh Kosong");
                document.forms["formPendaftaran"]["txtqty2a"].focus();
                return false;
            }
        }
    </script>
    
	</body>
</html>

<?php
	}
?>