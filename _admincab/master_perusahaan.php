<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    die();
} else {
    $id_user=$_SESSION['_iduser'];		
    $kd_cabang=$_SESSION['_cabang'];		                		
    include "../config/koneksi.php";

    $cari_kd=mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];				        
    $pwd=$tm_cari['password'];				        
    $lvl_akses=$tm_cari['user_akses'];				                
    $foto_user=$tm_cari['foto_user'];				
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    $cari_kd=mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];				        
    $tipe_cabang=$tm_cari['tipe_cabang'];	
    
    $tgl=date('Y/m/d');
    $tgl_skr=date('d');	
    $bulan_skr=date('m');
    $thn_skr=date('Y');

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_perusahaan']);
                    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_perusahaan']);
                    $singkat = mysqli_real_escape_string($koneksi, $_POST['nama_singkat']);
                    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
                    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
                    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
                    $website = mysqli_real_escape_string($koneksi, $_POST['website']);
                    $npwp = mysqli_real_escape_string($koneksi, $_POST['npwp']);
                    $direktur = mysqli_real_escape_string($koneksi, $_POST['direktur']);
                    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
                    
                    // Handle logo upload
                    $logo_path = NULL;
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'svg');
                        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $upload_dir = 'file_upload/logo/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $new_filename = 'logo_' . time() . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                                $logo_path = $upload_path;
                            }
                        }
                    }
                    
                    $sql = "INSERT INTO master_perusahaan (kode_perusahaan, nama_perusahaan, nama_singkat, alamat, telepon, email, website, logo, npwp, direktur, keterangan, is_aktif) 
                            VALUES ('$kode', '$nama', '$singkat', '$alamat', '$telepon', '$email', '$website', " . ($logo_path ? "'$logo_path'" : "NULL") . ", '$npwp', '$direktur', '$keterangan', 1)";
                    
                    if (mysqli_query($koneksi, $sql)) {
                        echo "<script>alert('Perusahaan berhasil ditambahkan!'); window.location.href='master_perusahaan.php';</script>";
                    } else {
                        echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
                    }
                    break;

                case 'edit':
                    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
                    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_perusahaan']);
                    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_perusahaan']); 
                    $singkat = mysqli_real_escape_string($koneksi, $_POST['nama_singkat']);
                    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
                    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
                    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
                    $website = mysqli_real_escape_string($koneksi, $_POST['website']);
                    $npwp = mysqli_real_escape_string($koneksi, $_POST['npwp']);
                    $direktur = mysqli_real_escape_string($koneksi, $_POST['direktur']);
                    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
                    
                    // Handle logo upload
                    $logo_update = "";
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'svg');
                        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            // Get old logo to delete
                            $old_logo_query = mysqli_query($koneksi, "SELECT logo FROM master_perusahaan WHERE id=$id");
                            $old_logo_data = mysqli_fetch_assoc($old_logo_query);
                            $old_logo = $old_logo_data['logo'];
                            
                            $upload_dir = 'file_upload/logo/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $new_filename = 'logo_' . time() . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                                // Delete old logo if exists
                                if ($old_logo && file_exists($old_logo)) {
                                    unlink($old_logo);
                                }
                                $logo_update = ", logo='$upload_path'";
                            }
                        }
                    }
                    
                    $sql = "UPDATE master_perusahaan SET 
                            kode_perusahaan='$kode', nama_perusahaan='$nama', nama_singkat='$singkat',
                            alamat='$alamat', telepon='$telepon', email='$email', website='$website',
                            npwp='$npwp', direktur='$direktur', keterangan='$keterangan'$logo_update 
                            WHERE id=$id";
                    
                    if (mysqli_query($koneksi, $sql)) {
                        echo "<script>alert('Perusahaan berhasil diupdate!'); window.location.href='master_perusahaan.php';</script>";
                    } else {
                        echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
                    }
                    break;

                case 'toggle_status':
                    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
                    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
                    
                    $sql = "UPDATE master_perusahaan SET is_aktif=$status WHERE id=$id";
                    
                    if (mysqli_query($koneksi, $sql)) {
                        $status_text = ($status == 1) ? 'diaktifkan' : 'dinonaktifkan';
                        echo "<script>alert('Status perusahaan berhasil $status_text!'); window.location.href='master_perusahaan.php';</script>";
                    } else {
                        echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
                    }
                    break;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Master Perusahaan" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
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
                                <small><?php include "../lib/logo.php"; ?> <?php include "../lib/subtitel.php"; ?></small>							
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
                            <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-header pull-right"><a href="#" class="navbar-brand"><small></small></a></div>
        </div>
    </div>
    
    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_master01a.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Data Master</a></li>                            
                        <li class="active">Master Perusahaan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>Master Perusahaan <small><i class="ace-icon fa fa-angle-double-right"></i> Manajemen Data Perusahaan</small></h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#addModal">
                                <i class="ace-icon fa fa-plus"></i> Tambah Perusahaan
                            </button>
                            <div class="space-6"></div>

                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="table-header">Daftar Perusahaan</div>
                                    <div>
                                        <table id="dynamic-table" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Kode</th>
                                                    <th>Logo</th>
                                                    <th>Nama Perusahaan</th>
                                                    <th>Nama Singkat</th>
                                                    <th>Telepon</th>
                                                    <th>Email</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM master_perusahaan ORDER BY kode_perusahaan ASC";
                                                $result = mysqli_query($koneksi, $sql);
                                                $no = 1;
                                                
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    echo "<td>" . $no++ . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['kode_perusahaan']) . "</td>";
                                                    // Logo preview
                                                    echo "<td>";
                                                    if (!empty($row['logo']) && file_exists($row['logo'])) {
                                                        echo "<img src='" . htmlspecialchars($row['logo']) . "' alt='Logo' style='max-height: 40px; max-width: 80px;'>";
                                                    } else {
                                                        echo "<i class='fa fa-image text-muted'></i> <small>No logo</small>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>" . htmlspecialchars($row['nama_perusahaan']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['nama_singkat']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['telepon']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                                    echo "<td>" . ($row['is_aktif'] ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Non-Aktif</span>') . "</td>";
                                                    echo "<td><div class='hidden-sm hidden-xs action-buttons'>";
                                                    echo "<a class='green' href='#' onclick='editData(" . htmlspecialchars(json_encode($row)) . ")'><i class='ace-icon fa fa-pencil bigger-130'></i></a> ";
                                                    if ($row['is_aktif']) {
                                                        echo "<a class='red' href='#' onclick='toggleStatus(" . $row['id'] . ", 0)' title='Nonaktifkan'><i class='ace-icon fa fa-times bigger-130'></i></a>";
                                                    } else {
                                                        echo "<a class='blue' href='#' onclick='toggleStatus(" . $row['id'] . ", 1)' title='Aktifkan'><i class='ace-icon fa fa-check bigger-130'></i></a>";
                                                    }
                                                    echo "</div></td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Modal -->
                    <div id="addModal" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form class="form-horizontal" role="form" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="add">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title">Tambah Perusahaan Baru</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Kode <span class="text-danger">*</span></label>
                                                    <div class="col-sm-8"><input type="text" name="kode_perusahaan" class="form-control" required></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Nama Perusahaan <span class="text-danger">*</span></label>
                                                    <div class="col-sm-8"><input type="text" name="nama_perusahaan" class="form-control" required></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Nama Singkat</label>
                                                    <div class="col-sm-8"><input type="text" name="nama_singkat" class="form-control" placeholder="Untuk logo"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Logo</label>
                                                    <div class="col-sm-8">
                                                        <input type="file" name="logo" accept="image/*" class="form-control">
                                                        <small class="text-muted">Format: JPG, PNG, GIF, SVG (Max 2MB)</small>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Telepon</label>
                                                    <div class="col-sm-8"><input type="text" name="telepon" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Email</label>
                                                    <div class="col-sm-8"><input type="email" name="email" class="form-control"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Website</label>
                                                    <div class="col-sm-8"><input type="text" name="website" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">NPWP</label>
                                                    <div class="col-sm-8"><input type="text" name="npwp" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Direktur</label>
                                                    <div class="col-sm-8"><input type="text" name="direktur" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Alamat</label>
                                                    <div class="col-sm-8"><textarea name="alamat" class="form-control" rows="3"></textarea></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Keterangan</label>
                                            <div class="col-sm-10"><textarea name="keterangan" class="form-control" rows="2"></textarea></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div id="editModal" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form class="form-horizontal" role="form" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" id="edit_id">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title">Edit Perusahaan</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Kode <span class="text-danger">*</span></label>
                                                    <div class="col-sm-8"><input type="text" name="kode_perusahaan" id="edit_kode_perusahaan" class="form-control" required></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Nama Perusahaan <span class="text-danger">*</span></label>
                                                    <div class="col-sm-8"><input type="text" name="nama_perusahaan" id="edit_nama_perusahaan" class="form-control" required></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Nama Singkat</label>
                                                    <div class="col-sm-8"><input type="text" name="nama_singkat" id="edit_nama_singkat" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Logo</label>
                                                    <div class="col-sm-8">
                                                        <div id="edit_logo_preview" style="margin-bottom: 10px;"></div>
                                                        <input type="file" name="logo" accept="image/*" class="form-control">
                                                        <small class="text-muted">Format: JPG, PNG, GIF, SVG (Max 2MB). Kosongkan jika tidak ingin mengubah logo.</small>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Telepon</label>
                                                    <div class="col-sm-8"><input type="text" name="telepon" id="edit_telepon" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Email</label>
                                                    <div class="col-sm-8"><input type="email" name="email" id="edit_email" class="form-control"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Website</label>
                                                    <div class="col-sm-8"><input type="text" name="website" id="edit_website" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">NPWP</label>
                                                    <div class="col-sm-8"><input type="text" name="npwp" id="edit_npwp" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Direktur</label>
                                                    <div class="col-sm-8"><input type="text" name="direktur" id="edit_direktur" class="form-control"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-4 control-label">Alamat</label>
                                                    <div class="col-sm-8"><textarea name="alamat" id="edit_alamat" class="form-control" rows="3"></textarea></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Keterangan</label>
                                            <div class="col-sm-10"><textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <form id="statusForm" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" id="toggle_id">
                        <input type="hidden" name="status" id="toggle_status">
                    </form>

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
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        jQuery(function($) {
            $('#dynamic-table').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "ordering": true,
                "order": [[1, "asc"]]
            });
        });

        function editData(data) {
            $('#edit_id').val(data.id);
            $('#edit_kode_perusahaan').val(data.kode_perusahaan);
            $('#edit_nama_perusahaan').val(data.nama_perusahaan);
            $('#edit_nama_singkat').val(data.nama_singkat);
            $('#edit_alamat').val(data.alamat);
            $('#edit_telepon').val(data.telepon);
            $('#edit_email').val(data.email);
            $('#edit_website').val(data.website);
            $('#edit_npwp').val(data.npwp);
            $('#edit_direktur').val(data.direktur);
            $('#edit_keterangan').val(data.keterangan);
            
            // Show logo preview
            if (data.logo) {
                $('#edit_logo_preview').html('<img src="' + data.logo + '" alt="Current Logo" style="max-height: 80px; max-width: 150px; border: 1px solid #ddd; padding: 5px;">');
            } else {
                $('#edit_logo_preview').html('<small class="text-muted">Belum ada logo</small>');
            }
            
            $('#editModal').modal('show');
        }

        function toggleStatus(id, status) {
            var statusText = (status == 1) ? 'mengaktifkan' : 'menonaktifkan';
            if (confirm('Apakah anda yakin ingin ' + statusText + ' perusahaan ini?')) {
                $('#toggle_id').val(id);
                $('#toggle_status').val(status);
                $('#statusForm').submit();
            }
        }
    </script>
</body>
</html>

<?php } ?>
