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
    
    // Set username session if not exists to prevent login redirect
    if(!isset($_SESSION['username'])) {
        $_SESSION['username'] = $_nama;
    }

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang 
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari ? $tm_cari['nama_cabang'] : '';				        
    $tipe_cabang = $tm_cari ? $tm_cari['tipe_cabang'] : '';	
    // --------------------

    // Handle CRUD Operations
    if(isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if($action == 'add') {
            $kode_keluhan = $_POST['kode_keluhan'];
            $nama_keluhan = $_POST['nama_keluhan'];
            $deskripsi = $_POST['deskripsi'];
            $kategori = $_POST['kategori'];
            $estimasi_waktu = $_POST['estimasi_waktu'];
            $tingkat_prioritas = $_POST['tingkat_prioritas'];
            $workorder_default = $_POST['workorder_default'];
            
            $query = "INSERT INTO tbmaster_keluhan 
                      (kode_keluhan, nama_keluhan, deskripsi, kategori, estimasi_waktu, tingkat_prioritas, workorder_default, status_aktif) 
                      VALUES 
                      ('$kode_keluhan', '$nama_keluhan', '$deskripsi', '$kategori', '$estimasi_waktu', '$tingkat_prioritas', '$workorder_default', '1')";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Data keluhan berhasil ditambahkan!'); window.location='master-keluhan-crud.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'edit') {
            $id = $_POST['id'];
            $kode_keluhan = $_POST['kode_keluhan'];
            $nama_keluhan = $_POST['nama_keluhan'];
            $deskripsi = $_POST['deskripsi'];
            $kategori = $_POST['kategori'];
            $estimasi_waktu = $_POST['estimasi_waktu'];
            $tingkat_prioritas = $_POST['tingkat_prioritas'];
            $workorder_default = $_POST['workorder_default'];
            
            $query = "UPDATE tbmaster_keluhan SET 
                      kode_keluhan='$kode_keluhan', 
                      nama_keluhan='$nama_keluhan', 
                      deskripsi='$deskripsi', 
                      kategori='$kategori', 
                      estimasi_waktu='$estimasi_waktu', 
                      tingkat_prioritas='$tingkat_prioritas', 
                      workorder_default='$workorder_default',
                      updated_at=CURRENT_TIMESTAMP
                      WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Data keluhan berhasil diupdate!'); window.location='master-keluhan-crud.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'delete') {
            $id = $_POST['id'];
            
            // Soft delete
            $query = "UPDATE tbmaster_keluhan SET status_aktif='0' WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Data keluhan berhasil dihapus!'); window.location='master-keluhan-crud.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'activate') {
            $id = $_POST['id'];
            
            $query = "UPDATE tbmaster_keluhan SET status_aktif='1' WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Data keluhan berhasil diaktifkan!'); window.location='master-keluhan-crud.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Pagination
    $limit = 20;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    // Search filter
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    
    $where_conditions = [];
    if($search) {
        $where_conditions[] = "(kode_keluhan LIKE '%$search%' OR nama_keluhan LIKE '%$search%' OR deskripsi LIKE '%$search%')";
    }
    if($kategori_filter) {
        $where_conditions[] = "kategori = '$kategori_filter'";
    }
    if($status_filter !== '') {
        $where_conditions[] = "status_aktif = '$status_filter'";
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Count total records
    $count_query = "SELECT COUNT(*) as total FROM tbmaster_keluhan $where_clause";
    $count_result = mysqli_query($koneksi, $count_query);
    $total_records = mysqli_fetch_array($count_result)['total'];
    $total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Master Keluhan</title>

    <meta name="description" content="Master Keluhan Management" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- page specific plugin styles -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

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

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>

    <!--[if lte IE 8]>
    <script src="assets/js/html5shiv.min.js"></script>
    <script src="assets/js/respond.min.js"></script>
    <![endif]-->
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
                            <a href="#">Master Data</a>
                        </li>                            
                        <li class="active">Master Keluhan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Master Keluhan
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Manajemen Data Keluhan Service
                            </small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-exclamation-triangle"></i>
                                        Data Master Keluhan
                                    </h4>
                                    <div class="widget-toolbar">
                                        <button type="button" class="btn btn-success btn-sm" onclick="showAddModal()">
                                            <i class="ace-icon fa fa-plus"></i> Tambah Keluhan
                                        </button>
                                    </div>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main">
                                        <!-- Filter Section -->
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <form method="GET" action="">
                                                    <div class="row">
                                                        <div class="col-sm-3">
                                                            <div class="form-group">
                                                                <label>Search:</label>
                                                                <input type="text" class="form-control" name="search" 
                                                                       value="<?php echo htmlspecialchars($search); ?>"
                                                                       placeholder="Kode, nama, atau deskripsi...">
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <div class="form-group">
                                                                <label>Kategori:</label>
                                                                <select class="form-control" name="kategori">
                                                                    <option value="">Semua</option>
                                                                    <option value="Mesin" <?php echo ($kategori_filter == 'Mesin') ? 'selected' : ''; ?>>Mesin</option>
                                                                    <option value="Rem" <?php echo ($kategori_filter == 'Rem') ? 'selected' : ''; ?>>Rem</option>
                                                                    <option value="Elektrik" <?php echo ($kategori_filter == 'Elektrik') ? 'selected' : ''; ?>>Elektrik</option>
                                                                    <option value="Ban" <?php echo ($kategori_filter == 'Ban') ? 'selected' : ''; ?>>Ban</option>
                                                                    <option value="Umum" <?php echo ($kategori_filter == 'Umum') ? 'selected' : ''; ?>>Umum</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <div class="form-group">
                                                                <label>Status:</label>
                                                                <select class="form-control" name="status">
                                                                    <option value="">Semua</option>
                                                                    <option value="1" <?php echo ($status_filter == '1') ? 'selected' : ''; ?>>Aktif</option>
                                                                    <option value="0" <?php echo ($status_filter == '0') ? 'selected' : ''; ?>>Nonaktif</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <div class="form-group">
                                                                <label>&nbsp;</label><br>
                                                                <button type="submit" class="btn btn-info btn-sm">
                                                                    <i class="ace-icon fa fa-search"></i> Filter
                                                                </button>
                                                                <a href="master-keluhan-crud.php" class="btn btn-default btn-sm">
                                                                    <i class="ace-icon fa fa-refresh"></i> Reset
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Data Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%" class="center">No</th>
                                                        <th width="8%">Kode</th>
                                                        <th width="20%">Nama Keluhan</th>
                                                        <th width="15%">Kategori</th>
                                                        <th width="8%" class="center">Prioritas</th>
                                                        <th width="8%" class="center">Est. Waktu</th>
                                                        <th width="12%">WorkOrder Default</th>
                                                        <th width="8%" class="center">Status</th>
                                                        <th width="16%" class="center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $query = "SELECT mk.*, wo.nama_wo 
                                                             FROM tbmaster_keluhan mk
                                                             LEFT JOIN tbworkorderheader wo ON mk.workorder_default = wo.kode_wo
                                                             $where_clause
                                                             ORDER BY mk.tingkat_prioritas DESC, mk.kategori ASC, mk.nama_keluhan ASC
                                                             LIMIT $limit OFFSET $offset";
                                                    
                                                    $result = mysqli_query($koneksi, $query);
                                                    $no = $offset + 1;
                                                    
                                                    if($result && mysqli_num_rows($result) > 0) {
                                                        while($row = mysqli_fetch_array($result)) {
                                                            $prioritas_class = '';
                                                            switch($row['tingkat_prioritas']) {
                                                                case 'darurat': $prioritas_class = 'label-danger'; break;
                                                                case 'tinggi': $prioritas_class = 'label-warning'; break;
                                                                case 'sedang': $prioritas_class = 'label-info'; break;
                                                                case 'rendah': $prioritas_class = 'label-success'; break;
                                                                default: $prioritas_class = 'label-default';
                                                            }
                                                            
                                                            echo "<tr>";
                                                            echo "<td class='center'>" . $no . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['kode_keluhan']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['nama_keluhan']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                                                            echo "<td class='center'>";
                                                            echo "<span class='label $prioritas_class'>" . ucfirst($row['tingkat_prioritas']) . "</span>";
                                                            echo "</td>";
                                                            echo "<td class='center'>" . $row['estimasi_waktu'] . " min</td>";
                                                            echo "<td>" . htmlspecialchars($row['workorder_default'] ?? '-') . "<br>";
                                                            echo "<small class='text-muted'>" . htmlspecialchars($row['nama_wo'] ?? '') . "</small></td>";
                                                            echo "<td class='center'>";
                                                            if($row['status_aktif'] == '1') {
                                                                echo "<span class='label label-success'>Aktif</span>";
                                                            } else {
                                                                echo "<span class='label label-default'>Nonaktif</span>";
                                                            }
                                                            echo "</td>";
                                                            echo "<td class='center'>";
                                                            echo "<div class='btn-group'>";
                                                            echo "<button type='button' class='btn btn-xs btn-info' onclick='showEditModal(" . $row['id'] . ")' title='Edit'>";
                                                            echo "<i class='ace-icon fa fa-edit'></i>";
                                                            echo "</button>";
                                                            
                                                            if($row['status_aktif'] == '1') {
                                                                echo "<button type='button' class='btn btn-xs btn-danger' onclick='deleteKeluhan(" . $row['id'] . ")' title='Nonaktifkan'>";
                                                                echo "<i class='ace-icon fa fa-trash'></i>";
                                                                echo "</button>";
                                                            } else {
                                                                echo "<button type='button' class='btn btn-xs btn-success' onclick='activateKeluhan(" . $row['id'] . ")' title='Aktifkan'>";
                                                                echo "<i class='ace-icon fa fa-check'></i>";
                                                                echo "</button>";
                                                            }
                                                            echo "</div>";
                                                            echo "</td>";
                                                            echo "</tr>";
                                                            $no++;
                                                        }
                                                    } else {
                                                        echo "<tr>";
                                                        echo "<td colspan='9' class='center'>Tidak ada data keluhan</td>";
                                                        echo "</tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <?php if($total_pages > 1): ?>
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <div class="dataTables_info">
                                                    Menampilkan <?php echo $offset + 1; ?> sampai <?php echo min($offset + $limit, $total_records); ?> dari <?php echo $total_records; ?> entries
                                                </div>
                                            </div>
                                            <div class="col-xs-6">
                                                <div class="dataTables_paginate paging_simple_numbers pull-right">
                                                    <ul class="pagination">
                                                        <?php if($page > 1): ?>
                                                        <li class="paginate_button previous">
                                                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori_filter); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                                                        </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                                        <li class="paginate_button <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori_filter); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                                                        </li>
                                                        <?php endfor; ?>
                                                        
                                                        <?php if($page < $total_pages): ?>
                                                        <li class="paginate_button next">
                                                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori_filter); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
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

    <!-- Modal Add/Edit Keluhan -->
    <div class="modal fade" id="modal-keluhan" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="modal-title">Tambah Keluhan</h4>
                </div>
                <form id="form-keluhan" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="keluhan_id">
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Kode Keluhan <span class="text-danger">*</span>:</label>
                                    <input type="text" class="form-control" name="kode_keluhan" id="kode_keluhan" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Kategori <span class="text-danger">*</span>:</label>
                                    <select class="form-control" name="kategori" id="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Mesin">Mesin</option>
                                        <option value="Rem">Rem</option>
                                        <option value="Elektrik">Elektrik</option>
                                        <option value="Ban">Ban</option>
                                        <option value="Umum">Umum</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Keluhan <span class="text-danger">*</span>:</label>
                            <input type="text" class="form-control" name="nama_keluhan" id="nama_keluhan" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Deskripsi:</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Estimasi Waktu (menit):</label>
                                    <input type="number" class="form-control" name="estimasi_waktu" id="estimasi_waktu" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Tingkat Prioritas:</label>
                                    <select class="form-control" name="tingkat_prioritas" id="tingkat_prioritas">
                                        <option value="rendah">Rendah</option>
                                        <option value="sedang" selected>Sedang</option>
                                        <option value="tinggi">Tinggi</option>
                                        <option value="darurat">Darurat</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>WorkOrder Default:</label>
                                    <select class="form-control" name="workorder_default" id="workorder_default">
                                        <option value="">Pilih WorkOrder</option>
                                        <?php
                                        $wo_query = "SELECT kode_wo, nama_wo FROM tbworkorderheader ORDER BY nama_wo";
                                        $wo_result = mysqli_query($koneksi, $wo_query);
                                        while($wo_row = mysqli_fetch_array($wo_result)) {
                                            echo "<option value='" . $wo_row['kode_wo'] . "'>" . $wo_row['kode_wo'] . " - " . $wo_row['nama_wo'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Forms for Actions -->
    <form id="form-delete" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>
    
    <form id="form-activate" method="POST" style="display: none;">
        <input type="hidden" name="action" value="activate">
        <input type="hidden" name="id" id="activate_id">
    </form>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/jquery.ui.touch-punch.min.js"></script>
    <script src="assets/js/chosen.jquery.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        function showAddModal() {
            $('#modal-title').text('Tambah Keluhan');
            $('#action').val('add');
            $('#keluhan_id').val('');
            $('#form-keluhan')[0].reset();
            $('#modal-keluhan').modal('show');
        }

        function showEditModal(id) {
            $('#modal-title').text('Edit Keluhan');
            $('#action').val('edit');
            $('#keluhan_id').val(id);
            
            // AJAX to get keluhan data
            $.ajax({
                url: 'ajax-get-keluhan-detail.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        var data = response.data;
                        $('#kode_keluhan').val(data.kode_keluhan);
                        $('#nama_keluhan').val(data.nama_keluhan);
                        $('#deskripsi').val(data.deskripsi);
                        $('#kategori').val(data.kategori);
                        $('#estimasi_waktu').val(data.estimasi_waktu);
                        $('#tingkat_prioritas').val(data.tingkat_prioritas);
                        $('#workorder_default').val(data.workorder_default);
                        $('#modal-keluhan').modal('show');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading data');
                }
            });
        }

        function deleteKeluhan(id) {
            if(confirm('Yakin ingin menonaktifkan keluhan ini?')) {
                $('#delete_id').val(id);
                $('#form-delete').submit();
            }
        }

        function activateKeluhan(id) {
            if(confirm('Yakin ingin mengaktifkan keluhan ini?')) {
                $('#activate_id').val(id);
                $('#form-activate').submit();
            }
        }

        // Form validation
        $('#form-keluhan').on('submit', function(e) {
            var kode = $('#kode_keluhan').val().trim();
            var nama = $('#nama_keluhan').val().trim();
            
            if(!kode || !nama) {
                e.preventDefault();
                alert('Kode keluhan dan nama keluhan harus diisi!');
                return false;
            }
            
            return true;
        });

        // Auto generate kode keluhan
        $('#kategori').on('change', function() {
            var kategori = $(this).val();
            if(kategori && $('#action').val() == 'add') {
                var prefix = '';
                switch(kategori) {
                    case 'Mesin': prefix = 'MSN'; break;
                    case 'Rem': prefix = 'REM'; break;
                    case 'Elektrik': prefix = 'ELK'; break;
                    case 'Ban': prefix = 'BAN'; break;
                    case 'Umum': prefix = 'UMM'; break;
                    default: prefix = 'KEL';
                }
                
                // AJAX to get next kode
                $.ajax({
                    url: 'ajax-get-next-kode-keluhan.php',
                    type: 'GET',
                    data: { prefix: prefix },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            $('#kode_keluhan').val(response.next_kode);
                        }
                    }
                });
            }
        });

        jQuery(function($) {
            if(!ace.vars['touch']) {
                $('.chosen-select').chosen({allow_single_deselect:true}); 
                $(window)
                .off('resize.chosen')
                .on('resize.chosen', function() {
                    $('.chosen-select').each(function() {
                         var $this = $(this);
                         $this.next().css({'width': $this.parent().width()});
                    })
                }).trigger('resize.chosen');
            }
        });
    </script>
</body>
</html>

<?php 
}
?>