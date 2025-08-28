<?php
// File: keluhan-proses.php
session_start();

// Handle AJAX requests for service keluhan
if(isset($_POST['action']) && $_POST['action'] == 'add') {
    header('Content-Type: application/json');
    
    if(empty($_SESSION['_iduser'])){
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }
    
    $id_user = $_SESSION['_iduser'];        
    $kd_cabang = $_SESSION['_cabang'];        
    include "../config/koneksi.php";
    
    $no_service = $_POST['no_service'] ?? '';
    $keluhan = $_POST['keluhan'] ?? '';
    
    if(empty($no_service) || empty($keluhan)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }
    
    try {
        // Check if service exists
        $check_service = mysqli_query($koneksi, "SELECT no_service FROM tblservice WHERE no_service='$no_service'");
        if(!$check_service || mysqli_num_rows($check_service) == 0) {
            echo json_encode(['success' => false, 'message' => 'Service tidak ditemukan']);
            exit;
        }
        
        // Check if keluhan already exists for this service
        $check_existing = mysqli_query($koneksi, "SELECT id FROM tbservis_keluhan WHERE no_service='$no_service' AND keluhan='$keluhan'");
        if($check_existing && mysqli_num_rows($check_existing) > 0) {
            echo json_encode(['success' => false, 'message' => 'Keluhan sudah ada untuk service ini']);
            exit;
        }
        
        // Try to find matching keluhan in master data
        $kode_keluhan = '';
        $master_keluhan = mysqli_query($koneksi, "SELECT kode_keluhan FROM view_master_keluhan WHERE nama_keluhan LIKE '%$keluhan%' LIMIT 1");
        if($master_keluhan && $row = mysqli_fetch_array($master_keluhan)) {
            $kode_keluhan = $row['kode_keluhan'];
        }
        
        // Insert keluhan to service
        $insert_query = "INSERT INTO tbservis_keluhan (no_service, kode_keluhan, keluhan, tanggal_input, user_input) 
                        VALUES ('$no_service', '$kode_keluhan', '$keluhan', NOW(), '$id_user')";
        
        if(mysqli_query($koneksi, $insert_query)) {
            echo json_encode(['success' => true, 'message' => 'Keluhan berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan keluhan: ' . mysqli_error($koneksi)]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle progress update for keluhan
if(isset($_POST['action']) && $_POST['action'] == 'update_progress') {
    header('Content-Type: application/json');
    
    if(empty($_SESSION['_iduser'])){
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }
    
    include "../config/koneksi.php";
    
    $keluhan_id = $_POST['keluhan_id'] ?? '';
    $no_service = $_POST['no_service'] ?? '';
    $progress_persen = $_POST['progress_persen'] ?? '';
    
    if(empty($keluhan_id) || empty($no_service) || $progress_persen === '') {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }
    
    // Validate progress percentage
    $progress_persen = intval($progress_persen);
    if($progress_persen < 0 || $progress_persen > 100) {
        echo json_encode(['success' => false, 'message' => 'Progress harus antara 0-100']);
        exit;
    }
    
    try {
        // Update progress keluhan
        $update_query = "UPDATE tbservis_keluhan SET 
                       progress_persen = '$progress_persen',
                       updated_at = NOW()
                       WHERE id = '$keluhan_id' AND no_service = '$no_service'";
        
        $result = mysqli_query($koneksi, $update_query);
        
        if($result) {
            // Auto-update status based on progress
            $new_status = '';
            if($progress_persen == 0) {
                $new_status = 'datang';
            } elseif($progress_persen > 0 && $progress_persen < 100) {
                $new_status = 'dikerjakan';
            } elseif($progress_persen == 100) {
                $new_status = 'selesai';
            }
            
            if($new_status) {
                mysqli_query($koneksi, "UPDATE tbservis_keluhan SET status_keluhan = '$new_status' WHERE id = '$keluhan_id'");
            }
            
            echo json_encode(['success' => true, 'message' => 'Progress berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($koneksi)]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating progress: ' . $e->getMessage()]);
    }
    
    exit;
}

// Handle status update for keluhan
if(isset($_POST['btnupdatestatus'])) {
    if(empty($_SESSION['_iduser'])){
        echo "<script>alert('Session expired'); window.location.href='../index.php';</script>";
        exit;
    }
    
    include "../config/koneksi.php";
    
    $no_service = $_POST['txtnosrv'] ?? '';
    $keluhan_id = $_POST['keluhan_id'] ?? '';
    $status_keluhan = $_POST['status_keluhan'] ?? '';
    
    if(!empty($no_service) && !empty($keluhan_id) && !empty($status_keluhan)) {
        try {
            // Update status keluhan
            $update_query = "UPDATE tbservis_keluhan SET 
                           status_keluhan = '$status_keluhan',
                           updated_at = NOW()
                           WHERE id = '$keluhan_id' AND no_service = '$no_service'";
            
            $result = mysqli_query($koneksi, $update_query);
            
            if($result) {
                // Auto-update progress based on status
                $progress_persen = 0;
                switch($status_keluhan) {
                    case 'datang': $progress_persen = 10; break;
                    case 'dikerjakan': $progress_persen = 50; break;
                    case 'selesai': $progress_persen = 100; break;
                    case 'pending': $progress_persen = 25; break;
                }
                
                // Update progress
                mysqli_query($koneksi, "UPDATE tbservis_keluhan SET progress_persen = '$progress_persen' WHERE id = '$keluhan_id'");
                
                echo "<script>
                    alert('Status keluhan berhasil diupdate!');
                    window.location.href = window.location.href;
                </script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Error updating status: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Data tidak lengkap untuk update status!');</script>";
    }
}

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

    // Get kode keluhan from URL
    $kode_keluhan = isset($_GET['kode']) ? $_GET['kode'] : '';
    if(empty($kode_keluhan)) {
        echo "<script>alert('Kode keluhan tidak ditemukan!'); window.location='master-keluhan.php';</script>";
        exit;
    }

    // Get keluhan info
    $sql_keluhan = mysqli_query($koneksi,"SELECT * FROM tbmaster_keluhan WHERE kode_keluhan='$kode_keluhan' AND status_aktif='1'");
    $keluhan_info = mysqli_fetch_array($sql_keluhan);
    if(!$keluhan_info) {
        echo "<script>alert('Data keluhan tidak ditemukan!'); window.location='master-keluhan.php';</script>";
        exit;
    }

    // Handle form submissions
    if(isset($_POST['btnsimpan'])) {
        $tipe_proses = $_POST['tipe_proses'];
        $nama_proses = $_POST['nama_proses'];
        $deskripsi = $_POST['deskripsi'];
        $estimasi_waktu = $_POST['estimasi_waktu'];
        $harga_estimasi = $_POST['harga_estimasi'];
        $wajib = isset($_POST['wajib']) ? '1' : '0';
        $urutan = $_POST['urutan'];
        $kode_item = $_POST['kode_item'];
        
        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Update
            $id = $_POST['id'];
            mysqli_query($koneksi,"UPDATE tbkeluhan_proses SET 
                                  tipe_proses='$tipe_proses',
                                  nama_proses='$nama_proses',
                                  deskripsi='$deskripsi',
                                  estimasi_waktu='$estimasi_waktu',
                                  harga_estimasi='$harga_estimasi',
                                  wajib='$wajib',
                                  urutan='$urutan',
                                  kode_item='$kode_item'
                                  WHERE id='$id'");
        } else {
            // Insert
            mysqli_query($koneksi,"INSERT INTO tbkeluhan_proses 
                                  (kode_keluhan, tipe_proses, nama_proses, deskripsi, estimasi_waktu, harga_estimasi, wajib, urutan, kode_item) 
                                  VALUES 
                                  ('$kode_keluhan','$tipe_proses','$nama_proses','$deskripsi','$estimasi_waktu','$harga_estimasi','$wajib','$urutan','$kode_item')");
        }
        
        echo "<script>alert('Data proses berhasil disimpan!'); window.location='keluhan-proses.php?kode=$kode_keluhan';</script>";
    }

    if(isset($_GET['del'])) {
        $id = $_GET['del'];
        mysqli_query($koneksi,"UPDATE tbkeluhan_proses SET status_aktif='0' WHERE id='$id'");
        echo "<script>alert('Data proses berhasil dihapus!'); window.location='keluhan-proses.php?kode=$kode_keluhan';</script>";
    }

    // Get max urutan
    $query_max = mysqli_query($koneksi,"SELECT MAX(urutan) as max_urutan FROM tbkeluhan_proses WHERE kode_keluhan='$kode_keluhan' AND status_aktif='1'");
    $data_max = mysqli_fetch_array($query_max);
    $next_urutan = ($data_max['max_urutan'] ?? 0) + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Kelola Proses Keluhan - Bengkel System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
    
    <style>
    .priority-badge {
        font-size: 11px;
        padding: 2px 6px;
    }
    .priority-rendah { background-color: #5cb85c; }
    .priority-sedang { background-color: #f0ad4e; }
    .priority-tinggi { background-color: #d9534f; }
    .priority-darurat { background-color: #d9534f; animation: blink 1s infinite; }
    
    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .tipe-jasa { background-color: #d1ecf1; }
    .tipe-barang { background-color: #d4edda; }
    .tipe-inspeksi { background-color: #fff3cd; }
    </style>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-leaf"></i> Bengkel System</small>							
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
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="master-keluhan.php">Master Keluhan</a></li>							
                        <li class="active">Proses Keluhan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <!-- Info Keluhan -->
                            <div class="widget-box">
                                <div class="widget-header widget-header-blue">
                                    <h4 class="widget-title">
                                        <i class="fa fa-info-circle"></i> Informasi Keluhan
                                    </h4>
                                    <div class="widget-toolbar">
                                        <a href="master-keluhan.php" class="btn btn-xs btn-warning">
                                            <i class="fa fa-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h4><?php echo $keluhan_info['nama_keluhan']; ?></h4>
                                                <p><strong>Kode:</strong> <?php echo $keluhan_info['kode_keluhan']; ?></p>
                                                <p><strong>Deskripsi:</strong> <?php echo $keluhan_info['deskripsi']; ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p><strong>Kategori:</strong> 
                                                    <span class="label label-info"><?php echo $keluhan_info['kategori']; ?></span>
                                                </p>
                                                <p><strong>Prioritas:</strong> 
                                                    <span class="label priority-<?php echo $keluhan_info['tingkat_prioritas']; ?>">
                                                        <?php echo ucfirst($keluhan_info['tingkat_prioritas']); ?>
                                                    </span>
                                                </p>
                                                <p><strong>Estimasi:</strong> <?php echo $keluhan_info['estimasi_waktu']; ?> menit</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Input Proses -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-plus"></i> Tambah Proses Keluhan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="post" role="form">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Tipe Proses *</label>
                                                        <select class="form-control" name="tipe_proses" required onchange="toggleItemField()">
                                                            <option value="">Pilih Tipe Proses</option>
                                                            <option value="inspeksi">Inspeksi</option>
                                                            <option value="jasa">Jasa</option>
                                                            <option value="barang">Barang</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Nama Proses *</label>
                                                        <input type="text" class="form-control" name="nama_proses" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Deskripsi</label>
                                                <textarea class="form-control" name="deskripsi" rows="2"></textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Estimasi Waktu (menit)</label>
                                                        <input type="number" class="form-control" name="estimasi_waktu" value="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Harga Estimasi</label>
                                                        <input type="number" class="form-control" name="harga_estimasi" value="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Urutan</label>
                                                        <input type="number" class="form-control" name="urutan" value="<?php echo $next_urutan; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>&nbsp;</label>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="wajib" class="ace">
                                                                <span class="lbl"> Proses Wajib</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group" id="item-field" style="display:none;">
                                                <label>Kode Item/Jasa</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="kode_item" id="kode_item" readonly>
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-info" onclick="showModalSearchItem()">
                                                            <i class="fa fa-search"></i> Cari
                                                        </button>
                                                    </span>
                                                </div>
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

                            <!-- Data Table Proses -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-list"></i> Daftar Proses Keluhan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="5%">Urutan</th>
                                                        <th width="10%">Tipe</th>
                                                        <th width="25%">Nama Proses</th>
                                                        <th width="25%">Deskripsi</th>
                                                        <th width="10%">Estimasi</th>
                                                        <th width="10%">Harga</th>
                                                        <th width="5%">Wajib</th>
                                                        <th width="5%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $no = 1;
                                                    $sql = mysqli_query($koneksi,"SELECT * FROM tbkeluhan_proses 
                                                                                 WHERE kode_keluhan='$kode_keluhan' AND status_aktif='1' 
                                                                                 ORDER BY urutan ASC, nama_proses ASC");
                                                    while ($data = mysqli_fetch_array($sql)) {
                                                        $tipe_class = 'tipe-' . $data['tipe_proses'];
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no++; ?></td>
                                                        <td class="center">
                                                            <span class="badge badge-info"><?php echo $data['urutan']; ?></span>
                                                        </td>
                                                        <td class="center">
                                                            <span class="label label-<?php echo ($data['tipe_proses']=='jasa')?'primary':(($data['tipe_proses']=='barang')?'success':'warning'); ?>">
                                                                <?php echo strtoupper($data['tipe_proses']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $data['nama_proses']; ?></td>
                                                        <td>
                                                            <small><?php echo substr($data['deskripsi'], 0, 50) . (strlen($data['deskripsi']) > 50 ? '...' : ''); ?></small>
                                                        </td>
                                                        <td class="center"><?php echo $data['estimasi_waktu']; ?> min</td>
                                                        <td class="center">
                                                            <?php if($data['harga_estimasi'] > 0) { ?>
                                                                Rp <?php echo number_format($data['harga_estimasi'],0,',','.'); ?>
                                                            <?php } else { ?>
                                                                <span class="text-muted">-</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="center">
                                                            <?php if($data['wajib'] == '1') { ?>
                                                                <i class="fa fa-check text-success" title="Wajib"></i>
                                                            <?php } else { ?>
                                                                <i class="fa fa-minus text-muted" title="Opsional"></i>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="center">
                                                            <a href="?kode=<?php echo $kode_keluhan; ?>&del=<?php echo $data['id']; ?>" 
                                                               class="btn btn-xs btn-danger" title="Hapus"
                                                               onclick="return confirm('Yakin hapus proses ini?')">
                                                                <i class="fa fa-trash"></i>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <script>
    function toggleItemField() {
        var tipeProses = document.querySelector('select[name="tipe_proses"]').value;
        var itemField = document.getElementById('item-field');
        
        if(tipeProses === 'jasa' || tipeProses === 'barang') {
            itemField.style.display = 'block';
        } else {
            itemField.style.display = 'none';
            document.getElementById('kode_item').value = '';
        }
    }
    
    function showModalSearchItem() {
        var tipeProses = document.querySelector('select[name="tipe_proses"]').value;
        if(tipeProses === 'jasa') {
            // Open modal for jasa
            alert('Modal pencarian jasa akan dibuka');
        } else if(tipeProses === 'barang') {
            // Open modal for barang
            alert('Modal pencarian barang akan dibuka');
        }
    }
    </script>
</body>
</html>

<?php } ?>