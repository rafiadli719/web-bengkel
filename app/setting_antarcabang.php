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

        $message = '';
        $message_type = '';

        if(isset($_GET['del'])) {
            $id_del = mysqli_real_escape_string($koneksi, $_GET['del']);
            if(mysqli_query($koneksi, "DELETE FROM tbl_setting_antarcabang WHERE id='$id_del'")) {
                $message = "Data setting berhasil dihapus!";
                $message_type = "success";
            } else {
                $message = "Gagal menghapus data: " . mysqli_error($koneksi);
                $message_type = "danger";
            }
        }

        if(isset($_POST['btn_save'])) {
            $id_edit = isset($_POST['id_edit']) ? mysqli_real_escape_string($koneksi, $_POST['id_edit']) : '';
            $kd_cabang_setting = isset($_POST['kd_cabang_setting']) ? mysqli_real_escape_string($koneksi, $_POST['kd_cabang_setting']) : '';
            $tipe_cabang_tujuan = isset($_POST['tipe_cabang_tujuan']) ? mysqli_real_escape_string($koneksi, $_POST['tipe_cabang_tujuan']) : '';

            $diskon_persen = isset($_POST['diskon_persen']) ? (float)$_POST['diskon_persen'] : 0;
            $margin_persen = isset($_POST['margin_persen']) ? (float)$_POST['margin_persen'] : 0;
            $tempo_hari = isset($_POST['tempo_hari']) ? (int)$_POST['tempo_hari'] : 0;
            $cara_bayar = isset($_POST['cara_bayar']) ? mysqli_real_escape_string($koneksi, $_POST['cara_bayar']) : 'Tunai';
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if($tipe_cabang_tujuan=='') {
                $message = "Tipe cabang tujuan wajib dipilih.";
                $message_type = "warning";
            } else {
                if($id_edit!='') {
                    $sql_update = "UPDATE tbl_setting_antarcabang SET 
                                    kd_cabang='$kd_cabang_setting',
                                    tipe_cabang_tujuan='$tipe_cabang_tujuan',
                                    diskon_persen='$diskon_persen',
                                    margin_persen='$margin_persen',
                                    tempo_hari='$tempo_hari',
                                    cara_bayar='$cara_bayar',
                                    aktif='$aktif'
                                    WHERE id='$id_edit'";
                    if(mysqli_query($koneksi, $sql_update)) {
                        $message = "Data setting berhasil diperbarui!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal update data: " . mysqli_error($koneksi);
                        $message_type = "danger";
                    }
                } else {
                    $sql_insert = "INSERT INTO tbl_setting_antarcabang
                                    (kd_cabang, tipe_cabang_tujuan, diskon_persen, margin_persen, tempo_hari, cara_bayar, aktif)
                                   VALUES
                                    ('$kd_cabang_setting', '$tipe_cabang_tujuan', '$diskon_persen', '$margin_persen', '$tempo_hari', '$cara_bayar', '$aktif')
                                   ON DUPLICATE KEY UPDATE
                                    diskon_persen=VALUES(diskon_persen),
                                    margin_persen=VALUES(margin_persen),
                                    tempo_hari=VALUES(tempo_hari),
                                    cara_bayar=VALUES(cara_bayar),
                                    aktif=VALUES(aktif)";
                    if(mysqli_query($koneksi, $sql_insert)) {
                        $message = "Data setting berhasil disimpan!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal simpan data: " . mysqli_error($koneksi);
                        $message_type = "danger";
                    }
                }
            }
        }

        $edit_id = isset($_GET['edit']) ? mysqli_real_escape_string($koneksi, $_GET['edit']) : '';
        $form_kd_cabang = '';
        $form_tipe_cabang_tujuan = '';
        $form_diskon = '0';
        $form_margin = '0';
        $form_tempo = '0';
        $form_cara_bayar = 'Tunai';
        $form_aktif = 1;

        if($edit_id!='') {
            $q_edit = mysqli_query($koneksi, "SELECT * FROM tbl_setting_antarcabang WHERE id='$edit_id'");
            $r_edit = mysqli_fetch_array($q_edit);
            if($r_edit) {
                $form_kd_cabang = $r_edit['kd_cabang'];
                $form_tipe_cabang_tujuan = $r_edit['tipe_cabang_tujuan'];
                $form_diskon = $r_edit['diskon_persen'];
                $form_margin = $r_edit['margin_persen'];
                $form_tempo = $r_edit['tempo_hari'];
                $form_cara_bayar = $r_edit['cara_bayar'];
                $form_aktif = $r_edit['aktif'];
            }
        }

        $q_cabang = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY nama_cabang ASC");
        $q_tipe = mysqli_query($koneksi, "SELECT id, cabang_tipe FROM tbcabang_tipe ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Setting Harga Antar Cabang" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
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
				<div class="navbar-header pull-right">
					<a href="#" class="navbar-brand"><small></small></a>
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
								<a href="#">Cabang</a>
							</li>
							<li class="active">Setting Harga Antar Cabang</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">

                                <?php if($message!='') { ?>
                                <div class="alert alert-<?php echo $message_type; ?>">
                                    <?php echo $message; ?>
                                </div>
                                <?php } ?>

                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">Form Setting</h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <form method="post" action="">
                                                <input type="hidden" name="id_edit" value="<?php echo $edit_id; ?>">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <div class="form-group">
                                                            <label>Cabang</label>
                                                            <select class="form-control" name="kd_cabang_setting">
                                                                <option value="" <?php if($form_kd_cabang=='') { echo 'selected'; } ?>>GLOBAL</option>
                                                                <?php while($r = mysqli_fetch_array($q_cabang)) { ?>
                                                                    <option value="<?php echo $r['kode_cabang']; ?>" <?php if($form_kd_cabang==$r['kode_cabang']) { echo 'selected'; } ?>><?php echo $r['nama_cabang']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div class="form-group">
                                                            <label>Tipe Cabang Tujuan</label>
                                                            <select class="form-control" name="tipe_cabang_tujuan">
                                                                <option value="">- Pilih -</option>
                                                                <?php while($r = mysqli_fetch_array($q_tipe)) { ?>
                                                                    <option value="<?php echo $r['id']; ?>" <?php if($form_tipe_cabang_tujuan==$r['id']) { echo 'selected'; } ?>><?php echo $r['cabang_tipe']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>Diskon (%)</label>
                                                            <input type="text" class="form-control" name="diskon_persen" value="<?php echo $form_diskon; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>Margin (%)</label>
                                                            <input type="text" class="form-control" name="margin_persen" value="<?php echo $form_margin; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>Tempo (Hari)</label>
                                                            <input type="text" class="form-control" name="tempo_hari" value="<?php echo $form_tempo; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>Cara Bayar</label>
                                                            <select class="form-control" name="cara_bayar">
                                                                <option value="Tunai" <?php if($form_cara_bayar=='Tunai') { echo 'selected'; } ?>>Tunai</option>
                                                                <option value="Kredit" <?php if($form_cara_bayar=='Kredit') { echo 'selected'; } ?>>Kredit</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="aktif" <?php if($form_aktif=='1') { echo 'checked'; } ?>> Aktif
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <button type="submit" name="btn_save" class="btn btn-primary">Simpan</button>
                                                        <a href="setting_antarcabang.php" class="btn btn-default">Reset</a>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-12"></div>

                                <div class="table-header">
                                    Daftar Setting Harga Antar Cabang
                                </div>

                                <div>
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="center" width="5%">No</th>
                                                <th>Cabang</th>
                                                <th>Tipe Tujuan</th>
                                                <th class="right">Diskon</th>
                                                <th class="right">Margin</th>
                                                <th class="center">Tempo</th>
                                                <th class="center">Cara Bayar</th>
                                                <th class="center">Aktif</th>
                                                <th class="center" width="10%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                $no=0;
                                                $q_list = mysqli_query($koneksi, "SELECT 
                                                    s.*, 
                                                    c.nama_cabang,
                                                    t.cabang_tipe
                                                    FROM tbl_setting_antarcabang s
                                                    LEFT JOIN tbcabang c ON s.kd_cabang=c.kode_cabang
                                                    LEFT JOIN tbcabang_tipe t ON s.tipe_cabang_tujuan=t.id
                                                    ORDER BY (s.kd_cabang='') DESC, s.kd_cabang ASC, s.tipe_cabang_tujuan ASC");
                                                while($r = mysqli_fetch_array($q_list)) {
                                                    $no++;
                                                    $nama_cabang_setting = $r['kd_cabang']=='' ? 'GLOBAL' : $r['nama_cabang'];
                                            ?>
                                            <tr>
                                                <td class="center"><?php echo $no; ?></td>
                                                <td><?php echo $nama_cabang_setting; ?></td>
                                                <td><?php echo $r['cabang_tipe']; ?></td>
                                                <td align="right"><?php echo $r['diskon_persen']; ?>%</td>
                                                <td align="right"><?php echo $r['margin_persen']; ?>%</td>
                                                <td class="center"><?php echo $r['tempo_hari']; ?></td>
                                                <td class="center"><?php echo $r['cara_bayar']; ?></td>
                                                <td class="center"><?php echo $r['aktif']=='1' ? 'Ya' : 'Tidak'; ?></td>
                                                <td class="center">
                                                    <a class="green" data-rel="tooltip" title="Edit" href="setting_antarcabang.php?edit=<?php echo $r['id']; ?>">
                                                        <i class="ace-icon fa fa-pencil bigger-130"></i>
                                                    </a>
                                                    &nbsp;
                                                    <a class="red" data-rel="tooltip" title="Delete" href="setting_antarcabang.php?del=<?php echo $r['id']; ?>" onclick="return confirm('Data setting akan dihapus. Lanjutkan?')">
                                                        <i class="ace-icon fa fa-trash-o bigger-130"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
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

		<script src="assets/js/jquery-2.1.4.min.js"></script>
		<script src="assets/js/bootstrap.min.js"></script>
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>
	</body>
</html>

<?php
	}
?>
