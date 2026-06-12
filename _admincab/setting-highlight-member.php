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

    // Handle Form Submission
    $message = '';
    $message_type = '';
    
    if(isset($_POST['btn_save'])) {
        $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
        $bg_color = mysqli_real_escape_string($koneksi, $_POST['bg_color']);
        $text_color = mysqli_real_escape_string($koneksi, $_POST['text_color']);
        $border_color = mysqli_real_escape_string($koneksi, $_POST['border_color']);
        $border_width = intval($_POST['border_width']);
        $is_bold = isset($_POST['is_bold']) ? 1 : 0;
        $opacity = floatval($_POST['opacity']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $update_query = "UPDATE setting_highlight_member SET 
                        background_color = '$bg_color',
                        text_color = '$text_color',
                        border_color = '$border_color',
                        border_width = $border_width,
                        is_bold = $is_bold,
                        opacity = $opacity,
                        is_active = $is_active,
                        updated_at = NOW()
                        WHERE kategori_member = '$kategori'";
        
        if(mysqli_query($koneksi, $update_query)) {
            $message = "Setting highlight untuk kategori <strong>$kategori</strong> berhasil disimpan!";
            $message_type = "success";
        } else {
            $message = "Gagal menyimpan setting: " . mysqli_error($koneksi);
            $message_type = "danger";
        }
    }
    
    // Reset to Default - DISABLED for dynamic categories
    if(isset($_POST['btn_reset'])) {
        $message = "Fitur reset default dinonaktifkan karena kategori member sekarang dinamis.";
        $message_type = "warning";
    }
    
    // Load Current Settings
    $settings_query = mysqli_query($koneksi, "SELECT * FROM setting_highlight_member ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Setting Highlight Member - <?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Setting Highlight Kategori Member" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- text fonts -->
		<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

        <style>
            .color-preview {
                width: 50px;
                height: 30px;
                border: 1px solid #ccc;
                border-radius: 3px;
                display: inline-block;
                vertical-align: middle;
            }
            .preview-box {
                padding: 15px;
                border-radius: 5px;
                margin: 10px 0;
                font-weight: normal;
                transition: all 0.3s ease;
            }
            .preview-box:hover {
                opacity: 1 !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
            .form-section {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
                border: 1px solid #e0e0e0;
            }
            .kategori-icon {
                font-size: 24px;
                margin-right: 10px;
            }
        </style>
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

<?php include "menu_servis01.php"; ?>

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
								<a href="#">Setting</a>
							</li>                            
							<li class="active">Highlight Kategori Member</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								<i class="ace-icon fa fa-paint-brush"></i> Setting Highlight Kategori Member
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									Atur warna highlight untuk setiap kategori member
								</small>
							</h1>
						</div>

						<div class="row">
							<div class="col-xs-12">
                                
                                <?php if($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <i class="ace-icon fa fa-<?php echo $message_type == 'success' ? 'check' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo $message; ?>
                                </div>
                                <?php endif; ?>

                                <div class="alert alert-info">
                                    <i class="ace-icon fa fa-info-circle"></i>
                                    <strong>Informasi:</strong> Atur warna highlight untuk membedakan kategori member pada halaman input servis.
                                    Perubahan akan langsung terlihat setelah disimpan.
                                </div>

                                <div class="space-10"></div>

                                <?php 
                                // $icons array removed as it was hardcoded
                                while($setting = mysqli_fetch_assoc($settings_query)): 
                                ?>
                                
                                <div class="form-section">
                                    <form method="POST" action="" id="form_<?php echo $setting['kategori_member']; ?>">
                                        <input type="hidden" name="kategori" value="<?php echo $setting['kategori_member']; ?>">
                                        
                                        <h3 class="header smaller lighter blue">
                                            <span class="kategori-icon"><i class="ace-icon fa fa-tag"></i></span>
                                            Kategori <?php echo $setting['kategori_member']; ?>
                                        </h3>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Background Color:</label>
                                                    <div class="input-group">
                                                        <input type="color" class="form-control" name="bg_color" 
                                                               value="<?php echo $setting['background_color']; ?>"
                                                               onchange="updatePreview('<?php echo $setting['kategori_member']; ?>')">
                                                        <span class="input-group-addon">
                                                            <code><?php echo $setting['background_color']; ?></code>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Text Color:</label>
                                                    <div class="input-group">
                                                        <input type="color" class="form-control" name="text_color" 
                                                               value="<?php echo $setting['text_color']; ?>"
                                                               onchange="updatePreview('<?php echo $setting['kategori_member']; ?>')">
                                                        <span class="input-group-addon">
                                                            <code><?php echo $setting['text_color']; ?></code>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Border Color:</label>
                                                    <div class="input-group">
                                                        <input type="color" class="form-control" name="border_color" 
                                                               value="<?php echo $setting['border_color']; ?>"
                                                               onchange="updatePreview('<?php echo $setting['kategori_member']; ?>')">
                                                        <span class="input-group-addon">
                                                            <code><?php echo $setting['border_color']; ?></code>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Border Width (px):</label>
                                                    <input type="number" class="form-control" name="border_width" 
                                                           value="<?php echo $setting['border_width']; ?>" min="0" max="10"
                                                           onchange="updatePreview('<?php echo $setting['kategori_member']; ?>')">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Opacity (0.0 - 1.0):</label>
                                                    <input type="number" class="form-control" name="opacity" 
                                                           value="<?php echo $setting['opacity']; ?>" min="0" max="1" step="0.1"
                                                           onchange="updatePreview('<?php echo $setting['kategori_member']; ?>')">
                                                    <small class="help-block">0.0 = Transparan, 1.0 = Solid</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>
                                                        <input type="checkbox" name="is_bold" class="ace" 
                                                               <?php echo $setting['is_bold'] ? 'checked' : ''; ?>
                                                               onchange="updatePreview('<?php echo $setting['kategori_member']; ?>')">
                                                        <span class="lbl"> Text Bold</span>
                                                    </label>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>
                                                        <input type="checkbox" name="is_active" class="ace" 
                                                               <?php echo $setting['is_active'] ? 'checked' : ''; ?>>
                                                        <span class="lbl"> Aktif</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label><strong>Preview:</strong></label>
                                            <div class="preview-box" id="preview_<?php echo $setting['kategori_member']; ?>"
                                                 style="background-color: <?php echo $setting['background_color']; ?>;
                                                        color: <?php echo $setting['text_color']; ?>;
                                                        border-left: <?php echo $setting['border_width']; ?>px solid <?php echo $setting['border_color']; ?>;
                                                        opacity: <?php echo $setting['opacity']; ?>;
                                                        font-weight: <?php echo $setting['is_bold'] ? 'bold' : 'normal'; ?>;">
                                                <i class="ace-icon fa fa-user"></i> 
                                                Contoh tampilan baris pelanggan kategori <?php echo $setting['kategori_member']; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="btn_save" class="btn btn-primary">
                                                <i class="ace-icon fa fa-save"></i> Simpan Setting
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <?php endwhile; ?>
                                
                                <div class="space-10"></div>
                                
                                <div class="well">
                                    <form method="POST" action="">
                                        <!-- Reset button removed -->
                                        <a href="servis-carinopol.php" class="btn btn-default">
                                            <i class="ace-icon fa fa-arrow-left"></i> Kembali ke Input Servis
                                        </a>
                                    </form>
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
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<script>
            function updatePreview(kategori) {
                var form = document.getElementById('form_' + kategori);
                var preview = document.getElementById('preview_' + kategori);
                
                var bgColor = form.querySelector('[name="bg_color"]').value;
                var textColor = form.querySelector('[name="text_color"]').value;
                var borderColor = form.querySelector('[name="border_color"]').value;
                var borderWidth = form.querySelector('[name="border_width"]').value;
                var opacity = form.querySelector('[name="opacity"]').value;
                var isBold = form.querySelector('[name="is_bold"]').checked;
                
                preview.style.backgroundColor = bgColor;
                preview.style.color = textColor;
                preview.style.borderLeft = borderWidth + 'px solid ' + borderColor;
                preview.style.opacity = opacity;
                preview.style.fontWeight = isBold ? 'bold' : 'normal';
            }
        </script>
	</body>
</html>

<?php } ?>
