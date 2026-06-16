<?php
// File: master-keluhan.php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];        
    $kd_cabang=$_SESSION['_cabang'];        
    include "../config/koneksi.php";
    
    $cari_kd=mysqli_query($koneksi,"SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];				        
    $foto_user=$tm_cari['foto_user'];				
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // Handle form submissions
    if(isset($_POST['btnsimpan'])) {
        $kode_keluhan = mysqli_real_escape_string($koneksi, $_POST['kode_keluhan']);
        $nama_keluhan = mysqli_real_escape_string($koneksi, $_POST['nama_keluhan']);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
        
        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Update
            $id = mysqli_real_escape_string($koneksi, $_POST['id']);
            mysqli_query($koneksi,"UPDATE tbmaster_keluhan SET 
                                  nama_keluhan='$nama_keluhan',
                                  deskripsi='$deskripsi',
                                  kategori='$kategori'
                                  WHERE id='$id'");
            echo "<script>alert('Data berhasil diupdate!'); window.location='master-keluhan.php';</script>";
        } else {
            // Insert - Keluhan baru perlu approval dari pusat
            mysqli_query($koneksi,"INSERT INTO tbmaster_keluhan 
                                  (kode_keluhan, nama_keluhan, deskripsi, kategori, 
                                   status_approval, requested_by, requested_from) 
                                  VALUES 
                                  ('$kode_keluhan','$nama_keluhan','$deskripsi','$kategori',
                                   'pending', '$_nama', '$kd_cabang')");
            echo "<script>alert('Keluhan baru berhasil diajukan!\\nMenunggu approval dari pusat.'); window.location='master-keluhan.php';</script>";
        }
    }
    
    // Handle Approval (untuk user pusat)
    if(isset($_POST['btnapprove'])) {
        $id = mysqli_real_escape_string($koneksi, $_POST['id']);
        $action = $_POST['action']; // 'approve' atau 'reject'
        $rejection_reason = isset($_POST['rejection_reason']) ? mysqli_real_escape_string($koneksi, $_POST['rejection_reason']) : '';
        
        if($action == 'approve') {
            mysqli_query($koneksi,"UPDATE tbmaster_keluhan SET 
                                  status_approval='approved',
                                  approved_by='$_nama',
                                  approved_at=NOW()
                                  WHERE id='$id'");
            echo "<script>alert('Keluhan berhasil diapprove!'); window.location='master-keluhan.php';</script>";
        } else if($action == 'reject') {
            if(empty($rejection_reason)) {
                echo "<script>alert('Alasan penolakan harus diisi!'); window.history.back();</script>";
                exit;
            }
            mysqli_query($koneksi,"UPDATE tbmaster_keluhan SET 
                                  status_approval='rejected',
                                  approved_by='$_nama',
                                  approved_at=NOW(),
                                  rejection_reason='$rejection_reason'
                                  WHERE id='$id'");
            echo "<script>alert('Keluhan ditolak!'); window.location='master-keluhan.php';</script>";
        }
    }

    if(isset($_GET['del'])) {
        $id = $_GET['del'];
        mysqli_query($koneksi,"UPDATE tbmaster_keluhan SET status_aktif='0' WHERE id='$id'");
        echo "<script>alert('Data berhasil dihapus!'); window.location='master-keluhan.php';</script>";
    }

    // Generate kode keluhan otomatis
    $query_max = mysqli_query($koneksi,"SELECT MAX(CAST(SUBSTRING(kode_keluhan, 4) AS UNSIGNED)) as max_no FROM tbmaster_keluhan WHERE kode_keluhan LIKE 'KEL%'");
    $data_max = mysqli_fetch_array($query_max);
    $next_no = ($data_max['max_no'] ?? 0) + 1;
    $kode_keluhan_baru = 'KEL' . str_pad($next_no, 3, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Master Keluhan - Bengkel System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
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
                <a href="index.php" class="navbar-brand">
                    <small><?php include "../lib/logo.php"; ?> <?php include "../lib/subtitel.php"; ?></small>							
                </a>								
            </div>
            
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profile" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo $_nama; ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
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
        
        <!-- Sidebar -->
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
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Master Data</a></li>							
                        <li class="active">Master Keluhan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <!-- Form Input -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-plus"></i> Form Master Keluhan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="post" role="form">
                                            <div class="form-group">
                                                <label>Kode Keluhan</label>
                                                <input type="text" class="form-control" name="kode_keluhan" 
                                                       value="<?php echo $kode_keluhan_baru; ?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Keluhan *</label>
                                                <input type="text" class="form-control" name="nama_keluhan" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Deskripsi</label>
                                                <textarea class="form-control" name="deskripsi" rows="3"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Kategori</label>
                                                <select class="form-control" name="kategori">
                                                    <option value="">Pilih Kategori</option>
                                                    <option value="Mesin">Mesin</option>
                                                    <option value="Rem">Rem</option>
                                                    <option value="Kelistrikan">Kelistrikan</option>
                                                    <option value="Transmisi">Transmisi</option>
                                                    <option value="Ban">Ban</option>
                                                    <option value="Body">Body</option>
                                                    <option value="Lainnya">Lainnya</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" name="btnsimpan" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-save"></i> Simpan
                                                </button>
                                                <button type="reset" class="btn btn-sm btn-default">
                                                    <i class="fa fa-refresh"></i> Reset
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-table"></i> Data Master Keluhan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="10%">Kode</th>
                                                        <th width="25%">Nama Keluhan</th>
                                                        <th width="20%">Deskripsi</th>
                                                        <th width="10%">Kategori</th>
                                                        <th width="10%">Status</th>
                                                        <th width="15%">Request By</th>
                                                        <th width="5%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $no = 1;
                                                    $sql = mysqli_query($koneksi,"SELECT * FROM tbmaster_keluhan WHERE status_aktif='1' ORDER BY 
                                                                                  CASE status_approval 
                                                                                    WHEN 'pending' THEN 1 
                                                                                    WHEN 'approved' THEN 2 
                                                                                    WHEN 'rejected' THEN 3 
                                                                                  END, 
                                                                                  created_at DESC");
                                                    while ($data = mysqli_fetch_array($sql)) {
                                                        $status_approval = $data['status_approval'] ?? 'approved';
                                                        $requested_by = $data['requested_by'] ?? '-';
                                                        $requested_from = $data['requested_from'] ?? '-';
                                                        
                                                        // Badge color based on status
                                                        $badge_class = '';
                                                        $badge_text = '';
                                                        switch($status_approval) {
                                                            case 'pending':
                                                                $badge_class = 'label-warning';
                                                                $badge_text = 'Pending';
                                                                break;
                                                            case 'approved':
                                                                $badge_class = 'label-success';
                                                                $badge_text = 'Approved';
                                                                break;
                                                            case 'rejected':
                                                                $badge_class = 'label-danger';
                                                                $badge_text = 'Rejected';
                                                                break;
                                                        }
                                                    ?>
                                                    <tr class="<?php echo ($status_approval == 'pending') ? 'warning' : ''; ?>">
                                                        <td class="center"><?php echo $no++; ?></td>
                                                        <td><?php echo $data['kode_keluhan']; ?></td>
                                                        <td><?php echo $data['nama_keluhan']; ?></td>
                                                        <td>
                                                            <small><?php echo substr($data['deskripsi'], 0, 50) . (strlen($data['deskripsi']) > 50 ? '...' : ''); ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="label label-info"><?php echo $data['kategori']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="label <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                <?php echo $requested_by; ?><br>
                                                                <span class="text-muted">(<?php echo $requested_from; ?>)</span>
                                                            </small>
                                                        </td>
                                                        <td class="center">
                                                            <?php if($status_approval == 'pending') { ?>
                                                                <!-- Tombol Approve/Reject untuk user pusat -->
                                                                <button class="btn btn-xs btn-success" 
                                                                        onclick="approveKeluhan(<?php echo $data['id']; ?>, '<?php echo addslashes($data['nama_keluhan']); ?>')" 
                                                                        title="Approve">
                                                                    <i class="fa fa-check"></i>
                                                                </button>
                                                                <button class="btn btn-xs btn-danger" 
                                                                        onclick="rejectKeluhan(<?php echo $data['id']; ?>, '<?php echo addslashes($data['nama_keluhan']); ?>')" 
                                                                        title="Reject">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            <?php } else if($status_approval == 'approved') { ?>
                                                                <a href="?del=<?php echo $data['id']; ?>" 
                                                                   class="btn btn-xs btn-danger" title="Hapus"
                                                                   onclick="return confirm('Yakin hapus data ini?')">
                                                                    <i class="fa fa-trash"></i>
                                                                </a>
                                                            <?php } else if($status_approval == 'rejected') { ?>
                                                                <button class="btn btn-xs btn-info" 
                                                                        onclick="showRejectionReason('<?php echo addslashes($data['rejection_reason']); ?>')" 
                                                                        title="Lihat Alasan">
                                                                    <i class="fa fa-info-circle"></i>
                                                                </button>
                                                            <?php } ?>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Approve -->
    <div id="modalApprove" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-check-circle text-success"></i> Approve Keluhan</h4>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="approve_id">
                        <input type="hidden" name="action" value="approve">
                        <p>Apakah Anda yakin ingin <strong>menyetujui</strong> keluhan berikut?</p>
                        <div class="alert alert-info">
                            <strong id="approve_nama_keluhan"></strong>
                        </div>
                        <p class="text-muted"><small>Keluhan yang diapprove akan tersedia untuk digunakan di semua cabang.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" name="btnapprove" class="btn btn-sm btn-success">
                            <i class="fa fa-check"></i> Approve
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
                    <h4 class="modal-title"><i class="fa fa-times-circle text-danger"></i> Reject Keluhan</h4>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="reject_id">
                        <input type="hidden" name="action" value="reject">
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
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" name="btnapprove" class="btn btn-sm btn-danger">
                            <i class="fa fa-times-circle"></i> Reject
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
                    <h4 class="modal-title"><i class="fa fa-info-circle text-danger"></i> Alasan Penolakan</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <p id="rejection_reason_text"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <script>
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
    
    // Auto-hide alerts after 5 seconds
    $(document).ready(function() {
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
    </script>
</body>
</html>

<?php } ?>