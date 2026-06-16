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
            $kode_keluhan = mysqli_real_escape_string($koneksi, $_POST['kode_keluhan']);
            $nama_keluhan = mysqli_real_escape_string($koneksi, $_POST['nama_keluhan']);
            $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
            $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
            
            // Keluhan baru perlu approval dari pusat
            $query = "INSERT INTO tbmaster_keluhan 
                      (kode_keluhan, nama_keluhan, deskripsi, kategori, 
                       status_aktif, status_approval, requested_by, requested_from) 
                      VALUES 
                      ('$kode_keluhan', '$nama_keluhan', '$deskripsi', '$kategori', 
                       '1', 'pending', '$_nama', '$kd_cabang')";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Keluhan baru berhasil diajukan!\\nMenunggu approval dari pusat.'); window.location='master-keluhan-crud.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'edit') {
            $id = mysqli_real_escape_string($koneksi, $_POST['id']);
            $kode_keluhan = mysqli_real_escape_string($koneksi, $_POST['kode_keluhan']);
            $nama_keluhan = mysqli_real_escape_string($koneksi, $_POST['nama_keluhan']);
            $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
            $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
            
            $query = "UPDATE tbmaster_keluhan SET 
                      kode_keluhan='$kode_keluhan', 
                      nama_keluhan='$nama_keluhan', 
                      deskripsi='$deskripsi', 
                      kategori='$kategori',
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
        
        // Handle Approval (untuk user pusat)
        if($action == 'approve') {
            $id = mysqli_real_escape_string($koneksi, $_POST['id']);
            
            $query = "UPDATE tbmaster_keluhan SET 
                      status_approval='approved',
                      approved_by='$_nama',
                      approved_at=NOW()
                      WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Keluhan berhasil diapprove!'); window.location='master-keluhan-crud.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'reject') {
            $id = mysqli_real_escape_string($koneksi, $_POST['id']);
            $rejection_reason = mysqli_real_escape_string($koneksi, $_POST['rejection_reason']);
            
            if(empty($rejection_reason)) {
                echo "<script>alert('Alasan penolakan harus diisi!'); window.history.back();</script>";
                exit;
            }
            
            $query = "UPDATE tbmaster_keluhan SET 
                      status_approval='rejected',
                      approved_by='$_nama',
                      approved_at=NOW(),
                      rejection_reason='$rejection_reason'
                      WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Keluhan ditolak!'); window.location='master-keluhan-crud.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }
    
    // Count pending approvals untuk notifikasi
    $pending_count_query = "SELECT COUNT(*) as total FROM tbmaster_keluhan WHERE status_approval='pending' AND status_aktif='1'";
    $pending_count_result = mysqli_query($koneksi, $pending_count_query);
    $pending_count = mysqli_fetch_array($pending_count_result)['total'];

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
        $where_conditions[] = "(mk.kode_keluhan LIKE '%$search%' OR mk.nama_keluhan LIKE '%$search%' OR mk.deskripsi LIKE '%$search%')";
    }
    if($kategori_filter) {
        $where_conditions[] = "mk.kategori = '$kategori_filter'";
    }
    if($status_filter !== '') {
        $where_conditions[] = "mk.status_aktif = '$status_filter'";
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Count total records
    $count_query = "SELECT COUNT(*) as total FROM tbmaster_keluhan mk $where_clause";
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
                                        <?php if($pending_count > 0): ?>
                                        <span class="badge badge-warning" style="margin-right: 10px; font-size: 14px;">
                                            <i class="ace-icon fa fa-bell"></i> <?php echo $pending_count; ?> Pending Approval
                                        </span>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-success btn-sm" onclick="showAddModal()">
                                            <i class="ace-icon fa fa-plus"></i> Tambah Keluhan
                                        </button>
                                    </div>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main">
                                        <?php if($pending_count > 0): ?>
                                        <div class="alert alert-warning">
                                            <button type="button" class="close" data-dismiss="alert">
                                                <i class="ace-icon fa fa-times"></i>
                                            </button>
                                            <strong><i class="ace-icon fa fa-bell"></i> Notifikasi!</strong>
                                            Ada <strong><?php echo $pending_count; ?> keluhan</strong> yang menunggu approval dari pusat.
                                            <a href="#" onclick="filterPending(); return false;" class="alert-link">Lihat sekarang</a>
                                        </div>
                                        <?php endif; ?>
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
                                                        <th width="10%">Kode</th>
                                                        <th width="25%">Nama Keluhan</th>
                                                        <th width="12%">Kategori</th>
                                                        <th width="12%" class="center">Status Approval</th>
                                                        <th width="15%">Request By</th>
                                                        <th width="8%" class="center">Status</th>
                                                        <th width="13%" class="center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $query = "SELECT mk.* 
                                                             FROM tbmaster_keluhan mk
                                                             $where_clause
                                                             ORDER BY 
                                                               CASE COALESCE(mk.status_approval, 'approved')
                                                                 WHEN 'pending' THEN 1 
                                                                 WHEN 'approved' THEN 2 
                                                                 WHEN 'rejected' THEN 3 
                                                               END,
                                                               mk.kategori ASC, mk.nama_keluhan ASC
                                                             LIMIT $limit OFFSET $offset";
                                                    
                                                    $result = mysqli_query($koneksi, $query);
                                                    
                                                    // Debug: Tampilkan error jika ada
                                                    if(!$result) {
                                                        echo "<tr><td colspan='8' class='center' style='color:red;'>";
                                                        echo "<strong>Query Error:</strong> " . mysqli_error($koneksi);
                                                        echo "<br><small>Query: " . htmlspecialchars($query) . "</small>";
                                                        echo "</td></tr>";
                                                    }
                                                    
                                                    $no = $offset + 1;
                                                    
                                                    if($result && mysqli_num_rows($result) > 0) {
                                                        while($row = mysqli_fetch_array($result)) {
                                                            $status_approval = $row['status_approval'] ?? 'approved';
                                                            $requested_by = $row['requested_by'] ?? '-';
                                                            $requested_from = $row['requested_from'] ?? '-';
                                                            
                                                            // Badge color based on status
                                                            $approval_badge_class = '';
                                                            $approval_badge_text = '';
                                                            switch($status_approval) {
                                                                case 'pending':
                                                                    $approval_badge_class = 'label-warning';
                                                                    $approval_badge_text = 'Pending';
                                                                    break;
                                                                case 'approved':
                                                                    $approval_badge_class = 'label-success';
                                                                    $approval_badge_text = 'Approved';
                                                                    break;
                                                                case 'rejected':
                                                                    $approval_badge_class = 'label-danger';
                                                                    $approval_badge_text = 'Rejected';
                                                                    break;
                                                            }
                                                            
                                                            $row_class = ($status_approval == 'pending') ? 'warning' : '';
                                                            
                                                            echo "<tr class='$row_class'>";
                                                            echo "<td class='center'>" . $no . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['kode_keluhan']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['nama_keluhan']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                                                            
                                                            // Status Approval
                                                            echo "<td class='center'>";
                                                            echo "<span class='label $approval_badge_class'>$approval_badge_text</span>";
                                                            echo "</td>";
                                                            
                                                            // Request By
                                                            echo "<td>";
                                                            echo "<small>$requested_by<br>";
                                                            echo "<span class='text-muted'>($requested_from)</span></small>";
                                                            echo "</td>";
                                                            
                                                            // Status Aktif
                                                            echo "<td class='center'>";
                                                            if($row['status_aktif'] == '1') {
                                                                echo "<span class='label label-success'>Aktif</span>";
                                                            } else {
                                                                echo "<span class='label label-default'>Nonaktif</span>";
                                                            }
                                                            echo "</td>";
                                                            
                                                            // Aksi
                                                            echo "<td class='center'>";
                                                            echo "<div class='btn-group'>";
                                                            
                                                            if($status_approval == 'pending') {
                                                                // Tombol Approve/Reject untuk keluhan pending
                                                                echo "<button type='button' class='btn btn-xs btn-success' onclick='approveKeluhan(" . $row['id'] . ", \"" . addslashes($row['nama_keluhan']) . "\")' title='Approve'>";
                                                                echo "<i class='ace-icon fa fa-check'></i>";
                                                                echo "</button>";
                                                                echo "<button type='button' class='btn btn-xs btn-danger' onclick='rejectKeluhan(" . $row['id'] . ", \"" . addslashes($row['nama_keluhan']) . "\")' title='Reject'>";
                                                                echo "<i class='ace-icon fa fa-times'></i>";
                                                                echo "</button>";
                                                            } else if($status_approval == 'approved') {
                                                                // Tombol Edit/Delete untuk keluhan approved
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
                                                            } else if($status_approval == 'rejected') {
                                                                // Tombol Info untuk keluhan rejected
                                                                echo "<button type='button' class='btn btn-xs btn-info' onclick='showRejectionReason(\"" . addslashes($row['rejection_reason'] ?? 'Tidak ada alasan') . "\")' title='Lihat Alasan'>";
                                                                echo "<i class='ace-icon fa fa-info-circle'></i>";
                                                                echo "</button>";
                                                            }
                                                            
                                                            echo "</div>";
                                                            echo "</td>";
                                                            echo "</tr>";
                                                            $no++;
                                                        }
                                                    } else {
                                                        echo "<tr>";
                                                        echo "<td colspan='8' class='center'>Tidak ada data keluhan</td>";
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
                            <textarea class="form-control" name="deskripsi" id="deskripsi" rows="4" placeholder="Jelaskan detail keluhan..."></textarea>
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

    <!-- Modal Approve -->
    <div id="modalApprove" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="ace-icon fa fa-check-circle text-success"></i> Approve Keluhan</h4>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" id="approve_id">
                        <p>Apakah Anda yakin ingin <strong>menyetujui</strong> keluhan berikut?</p>
                        <div class="alert alert-info">
                            <strong id="approve_nama_keluhan"></strong>
                        </div>
                        <p class="text-muted"><small>Keluhan yang diapprove akan tersedia untuk digunakan di semua cabang.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                            <i class="ace-icon fa fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="ace-icon fa fa-check"></i> Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Reject -->
    <div id="modalReject" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="ace-icon fa fa-times-circle text-danger"></i> Reject Keluhan</h4>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" id="reject_id">
                        <p>Apakah Anda yakin ingin <strong>menolak</strong> keluhan berikut?</p>
                        <div class="alert alert-warning">
                            <strong id="reject_nama_keluhan"></strong>
                        </div>
                        <div class="form-group">
                            <label>Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="4" 
                                      placeholder="Jelaskan alasan penolakan..." required></textarea>
                            <small class="text-muted">Alasan penolakan akan dikirim ke cabang yang mengajukan.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                            <i class="ace-icon fa fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="ace-icon fa fa-times-circle"></i> Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Rejection Reason -->
    <div id="modalRejectionReason" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="ace-icon fa fa-info-circle text-danger"></i> Alasan Penolakan</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <p id="rejection_reason_text"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/jquery.ui.touch-punch.min.js"></script>
    <script src="assets/js/chosen.jquery.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        // Debug: Pastikan jQuery loaded
        console.log('jQuery version:', $.fn.jquery);
        
        // Define functions in global scope
        function showAddModal() {
            console.log('showAddModal called');
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

        // Fungsi Approval
        function approveKeluhan(id, nama) {
            $('#approve_id').val(id);
            $('#approve_nama_keluhan').text(nama);
            $('#modalApprove').modal('show');
        }
        
        function rejectKeluhan(id, nama) {
            $('#reject_id').val(id);
            $('#reject_nama_keluhan').text(nama);
            $('#modalReject').modal('show');
        }
        
        function showRejectionReason(reason) {
            $('#rejection_reason_text').text(reason);
            $('#modalRejectionReason').modal('show');
        }
        
        // Filter Pending
        function filterPending() {
            // Scroll ke tabel
            $('html, body').animate({
                scrollTop: $(".table-responsive").offset().top - 100
            }, 500);
            
            // Highlight baris pending
            $('.table tbody tr.warning').effect("highlight", {color: "#fcf8e3"}, 2000);
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