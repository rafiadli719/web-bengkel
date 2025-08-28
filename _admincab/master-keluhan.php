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
        $kode_keluhan = $_POST['kode_keluhan'];
        $nama_keluhan = $_POST['nama_keluhan'];
        $deskripsi = $_POST['deskripsi'];
        $kategori = $_POST['kategori'];
        $estimasi_waktu = $_POST['estimasi_waktu'];
        $tingkat_prioritas = $_POST['tingkat_prioritas'];
        
        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Update
            $id = $_POST['id'];
            mysqli_query($koneksi,"UPDATE tbmaster_keluhan SET 
                                  nama_keluhan='$nama_keluhan',
                                  deskripsi='$deskripsi',
                                  kategori='$kategori',
                                  estimasi_waktu='$estimasi_waktu',
                                  tingkat_prioritas='$tingkat_prioritas'
                                  WHERE id='$id'");
        } else {
            // Insert
            mysqli_query($koneksi,"INSERT INTO tbmaster_keluhan 
                                  (kode_keluhan, nama_keluhan, deskripsi, kategori, estimasi_waktu, tingkat_prioritas) 
                                  VALUES 
                                  ('$kode_keluhan','$nama_keluhan','$deskripsi','$kategori','$estimasi_waktu','$tingkat_prioritas')");
        }
        
        echo "<script>alert('Data berhasil disimpan!'); window.location='master-keluhan.php';</script>";
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
                                            <div class="row">
                                                <div class="col-md-4">
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
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Estimasi Waktu (menit)</label>
                                                        <input type="number" class="form-control" name="estimasi_waktu" value="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Tingkat Prioritas</label>
                                                        <select class="form-control" name="tingkat_prioritas">
                                                            <option value="rendah">Rendah</option>
                                                            <option value="sedang" selected>Sedang</option>
                                                            <option value="tinggi">Tinggi</option>
                                                            <option value="darurat">Darurat</option>
                                                        </select>
                                                    </div>
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
                                                        <th width="8%">Estimasi</th>
                                                        <th width="10%">Prioritas</th>
                                                        <th width="7%">Proses</th>
                                                        <th width="5%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $no = 1;
                                                    $sql = mysqli_query($koneksi,"SELECT * FROM view_master_keluhan ORDER BY nama_keluhan");
                                                    while ($data = mysqli_fetch_array($sql)) {
                                                        $priority_class = 'priority-' . $data['tingkat_prioritas'];
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no++; ?></td>
                                                        <td><?php echo $data['kode_keluhan']; ?></td>
                                                        <td><?php echo $data['nama_keluhan']; ?></td>
                                                        <td>
                                                            <small><?php echo substr($data['deskripsi'], 0, 50) . (strlen($data['deskripsi']) > 50 ? '...' : ''); ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="label label-info"><?php echo $data['kategori']; ?></span>
                                                        </td>
                                                        <td class="center"><?php echo $data['estimasi_waktu']; ?> min</td>
                                                        <td class="center">
                                                            <span class="label <?php echo $priority_class; ?>">
                                                                <?php echo ucfirst($data['tingkat_prioritas']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="center">
                                                            <span class="badge badge-info"><?php echo $data['total_proses']; ?></span>
                                                        </td>
                                                        <td class="center">
                                                            <a href="keluhan-proses.php?kode=<?php echo $data['kode_keluhan']; ?>" 
                                                               class="btn btn-xs btn-warning" title="Kelola Proses">
                                                                <i class="fa fa-cogs"></i>
                                                            </a>
                                                            <a href="?del=<?php echo $data['id']; ?>" 
                                                               class="btn btn-xs btn-danger" title="Hapus"
                                                               onclick="return confirm('Yakin hapus data ini?')">
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
</body>
</html>

<?php } ?>