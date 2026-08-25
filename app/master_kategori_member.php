<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];		
    $kd_cabang = $_SESSION['_cabang'];        
    include "../config/koneksi.php";
    
    // User data
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user 
                                        FROM tbuser WHERE id='$id_user'");			
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'] ?? '';				        
    $pwd = $tm_cari['password'] ?? '';				        
    $lvl_akses = $tm_cari['user_akses'] ?? '';				                
    $foto_user = $tm_cari['foto_user'] ?? '';				
    if($foto_user == '') {
        $foto_user = "file_upload/avatar.png";
    }

    // Cabang data
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");			
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'] ?? '';				        
    $tipe_cabang = $tm_cari['tipe_cabang'] ?? '';	

    // Handle form submission
    $message = '';
    $message_type = '';
    
    if(isset($_POST['btn_simpan'])) {
        $id_kategori = $_POST['id_kategori'] ?? '';
        $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
        $tipe_kategori = mysqli_real_escape_string($koneksi, $_POST['tipe_kategori']);
        $min_value = mysqli_real_escape_string($koneksi, $_POST['min_value']);
        $max_value = $_POST['max_value'] == '' ? NULL : $_POST['max_value'];
        $diskon_persen = mysqli_real_escape_string($koneksi, $_POST['diskon_persen']);
        $benefit_text = mysqli_real_escape_string($koneksi, $_POST['benefit_text']);
        $icon = mysqli_real_escape_string($koneksi, $_POST['icon']);
        $warna = mysqli_real_escape_string($koneksi, $_POST['warna']);
        $urutan = mysqli_real_escape_string($koneksi, $_POST['urutan']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if(empty($id_kategori)) {
            // Insert
            $sql = "INSERT INTO master_kategori_member 
                    (nama_kategori, tipe_kategori, min_value, max_value, diskon_persen, benefit_text, icon, warna, urutan, is_active) 
                    VALUES 
                    ('$nama_kategori', '$tipe_kategori', '$min_value', " . ($max_value === NULL ? "NULL" : "'$max_value'") . ", '$diskon_persen', '$benefit_text', '$icon', '$warna', '$urutan', '$is_active')";
        } else {
            // Update
            $sql = "UPDATE master_kategori_member SET 
                    nama_kategori = '$nama_kategori',
                    tipe_kategori = '$tipe_kategori',
                    min_value = '$min_value',
                    max_value = " . ($max_value === NULL ? "NULL" : "'$max_value'") . ",
                    diskon_persen = '$diskon_persen',
                    benefit_text = '$benefit_text',
                    icon = '$icon',
                    warna = '$warna',
                    urutan = '$urutan',
                    is_active = '$is_active'
                    WHERE id_kategori = '$id_kategori'";
        }
        
        if(mysqli_query($koneksi, $sql)) {
            $message = empty($id_kategori) ? 'Data berhasil ditambahkan!' : 'Data berhasil diupdate!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . mysqli_error($koneksi);
            $message_type = 'danger';
        }
    }
    
    if(isset($_POST['btn_hapus'])) {
        $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
        $sql = "DELETE FROM master_kategori_member WHERE id_kategori = '$id_kategori'";
        
        if(mysqli_query($koneksi, $sql)) {
            $message = 'Data berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . mysqli_error($koneksi);
            $message_type = 'danger';
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Master Kategori Member" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    
    <script src="assets/js/ace-extra.min.js"></script>
    
    <style>
        .badge-preview {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 12px;
        }
        .color-box {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 1px solid #ccc;
            border-radius: 4px;
            vertical-align: middle;
            margin-right: 5px;
        }
        .benefit-list {
            white-space: pre-line;
            font-size: 11px;
        }
    </style>
</head>

<body class="no-skin">
    <?php include "lib/header.php"; ?>
    
    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>
        
        <?php include "lib/sidebar.php"; ?>
        
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li class="active">Master Kategori Member</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Master Kategori Member
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Pengaturan Status Member Berdasarkan Nominal & Kunjungan
                            </small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?php if($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php echo $message; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Tombol Tambah -->
                            <div class="clearfix" style="margin-bottom: 15px;">
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalForm" onclick="resetForm()">
                                    <i class="fa fa-plus"></i> Tambah Kategori Baru
                                </button>
                                
                                <div class="pull-right">
                                    <a href="statistik_pelanggan_dashboard.php" class="btn btn-info btn-sm">
                                        <i class="fa fa-bar-chart"></i> Dashboard Statistik
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Tab untuk Nominal dan Kunjungan -->
                            <div class="tabbable">
                                <ul class="nav nav-tabs" id="myTab">
                                    <li class="active">
                                        <a data-toggle="tab" href="#tab-nominal">
                                            <i class="fa fa-money green"></i>
                                            Berdasarkan Nominal
                                        </a>
                                    </li>
                                    <li>
                                        <a data-toggle="tab" href="#tab-kunjungan">
                                            <i class="fa fa-users blue"></i>
                                            Berdasarkan Kunjungan
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Tab Nominal -->
                                    <div id="tab-nominal" class="tab-pane fade in active">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="15%">Kategori</th>
                                                        <th width="15%">Min. Nominal</th>
                                                        <th width="15%">Max. Nominal</th>
                                                        <th width="10%">Diskon</th>
                                                        <th width="25%">Benefit</th>
                                                        <th width="8%">Status</th>
                                                        <th width="12%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 1;
                                                    $query = mysqli_query($koneksi, "SELECT * FROM master_kategori_member 
                                                                                      WHERE tipe_kategori = 'nominal' 
                                                                                      ORDER BY urutan");
                                                    while($row = mysqli_fetch_array($query)):
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
                                                        <td>
                                                            <span class="badge-preview" style="background: <?php echo $row['warna']; ?>; color: <?php echo ($row['nama_kategori'] == 'Gold' || $row['nama_kategori'] == 'Platinum') ? '#000' : '#fff'; ?>;">
                                                                <?php echo $row['icon']; ?> <?php echo $row['nama_kategori']; ?>
                                                            </span>
                                                        </td>
                                                        <td>Rp <?php echo number_format($row['min_value'], 0, ',', '.'); ?></td>
                                                        <td>
                                                            <?php 
                                                            if($row['max_value'] === NULL) {
                                                                echo '<span class="label label-success">Unlimited</span>';
                                                            } else {
                                                                echo 'Rp ' . number_format($row['max_value'], 0, ',', '.');
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo $row['diskon_persen']; ?>%</td>
                                                        <td><div class="benefit-list"><?php echo nl2br($row['benefit_text']); ?></div></td>
                                                        <td>
                                                            <?php if($row['is_active']): ?>
                                                            <span class="label label-success">Aktif</span>
                                                            <?php else: ?>
                                                            <span class="label label-default">Nonaktif</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-xs btn-info" onclick="editData(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                                <i class="fa fa-edit"></i> Edit
                                                            </button>
                                                            <button type="button" class="btn btn-xs btn-danger" onclick="hapusData(<?php echo $row['id_kategori']; ?>, '<?php echo $row['nama_kategori']; ?>')">
                                                                <i class="fa fa-trash"></i> Hapus
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Kunjungan -->
                                    <div id="tab-kunjungan" class="tab-pane fade">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="15%">Kategori</th>
                                                        <th width="15%">Min. Kunjungan</th>
                                                        <th width="15%">Max. Kunjungan</th>
                                                        <th width="10%">Diskon</th>
                                                        <th width="25%">Benefit</th>
                                                        <th width="8%">Status</th>
                                                        <th width="12%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 1;
                                                    $query = mysqli_query($koneksi, "SELECT * FROM master_kategori_member 
                                                                                      WHERE tipe_kategori = 'kunjungan' 
                                                                                      ORDER BY urutan");
                                                    while($row = mysqli_fetch_array($query)):
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
                                                        <td>
                                                            <span class="badge-preview" style="background: <?php echo $row['warna']; ?>; color: <?php echo ($row['nama_kategori'] == 'Gold' || $row['nama_kategori'] == 'Platinum') ? '#000' : '#fff'; ?>;">
                                                                <?php echo $row['icon']; ?> <?php echo $row['nama_kategori']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo number_format($row['min_value'], 0); ?>x</td>
                                                        <td>
                                                            <?php 
                                                            if($row['max_value'] === NULL) {
                                                                echo '<span class="label label-success">Unlimited</span>';
                                                            } else {
                                                                echo number_format($row['max_value'], 0) . 'x';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo $row['diskon_persen']; ?>%</td>
                                                        <td><div class="benefit-list"><?php echo nl2br($row['benefit_text']); ?></div></td>
                                                        <td>
                                                            <?php if($row['is_active']): ?>
                                                            <span class="label label-success">Aktif</span>
                                                            <?php else: ?>
                                                            <span class="label label-default">Nonaktif</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-xs btn-info" onclick="editData(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                                <i class="fa fa-edit"></i> Edit
                                                            </button>
                                                            <button type="button" class="btn btn-xs btn-danger" onclick="hapusData(<?php echo $row['id_kategori']; ?>, '<?php echo $row['nama_kategori']; ?>')">
                                                                <i class="fa fa-trash"></i> Hapus
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
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

        <?php include "lib/footer.php"; ?>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="modalForm" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title" id="modalTitle">Tambah Kategori Member</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_kategori" id="id_kategori">
                        
                        <div class="form-group">
                            <label>Nama Kategori <span class="text-danger">*</span></label>
                            <select name="nama_kategori" id="nama_kategori" class="form-control" required>
                                <option value="Bronze">🥉 Bronze</option>
                                <option value="Silver">🥈 Silver</option>
                                <option value="Gold">🥇 Gold</option>
                                <option value="Platinum">💎 Platinum</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tipe Kategori <span class="text-danger">*</span></label>
                            <select name="tipe_kategori" id="tipe_kategori" class="form-control" required onchange="updateLabels()">
                                <option value="nominal">Berdasarkan Nominal (Rupiah)</option>
                                <option value="kunjungan">Berdasarkan Kunjungan (Jumlah)</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label id="label_min">Nilai Minimum <span class="text-danger">*</span></label>
                                    <input type="number" name="min_value" id="min_value" class="form-control" required min="0" step="0.01">
                                    <small class="text-muted" id="help_min">Contoh: 2000000</small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label id="label_max">Nilai Maksimum</label>
                                    <input type="number" name="max_value" id="max_value" class="form-control" min="0" step="0.01">
                                    <small class="text-muted">Kosongkan untuk unlimited</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Diskon (%)</label>
                            <input type="number" name="diskon_persen" id="diskon_persen" class="form-control" min="0" max="100" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Benefit Member</label>
                            <textarea name="benefit_text" id="benefit_text" class="form-control" rows="4" placeholder="Tulis benefit per baris&#10;Contoh:&#10;Diskon 10%&#10;Prioritas antrian"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Icon Emoji</label>
                                    <input type="text" name="icon" id="icon" class="form-control" maxlength="10" placeholder="🥉">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Warna (Hex)</label>
                                    <input type="color" name="warna" id="warna" class="form-control" value="#CD7F32">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Urutan</label>
                                    <input type="number" name="urutan" id="urutan" class="form-control" min="0" value="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                                Aktif
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_simpan" class="btn btn-primary">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form Hapus (Hidden) -->
    <form method="POST" id="formHapus" style="display:none;">
        <input type="hidden" name="id_kategori" id="hapus_id">
        <button type="submit" name="btn_hapus" id="btnHapus"></button>
    </form>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <script type="text/javascript">
        jQuery(function($) {
            // Enable Bootstrap tabs
            $('#myTab a').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
        });
        
        function resetForm() {
            document.getElementById('id_kategori').value = '';
            document.getElementById('nama_kategori').value = 'Bronze';
            document.getElementById('tipe_kategori').value = 'nominal';
            document.getElementById('min_value').value = '';
            document.getElementById('max_value').value = '';
            document.getElementById('diskon_persen').value = '0';
            document.getElementById('benefit_text').value = '';
            document.getElementById('icon').value = '🥉';
            document.getElementById('warna').value = '#CD7F32';
            document.getElementById('urutan').value = '1';
            document.getElementById('is_active').checked = true;
            document.getElementById('modalTitle').textContent = 'Tambah Kategori Member';
            updateLabels();
        }
        
        function editData(data) {
            document.getElementById('id_kategori').value = data.id_kategori;
            document.getElementById('nama_kategori').value = data.nama_kategori;
            document.getElementById('tipe_kategori').value = data.tipe_kategori;
            document.getElementById('min_value').value = data.min_value;
            document.getElementById('max_value').value = data.max_value || '';
            document.getElementById('diskon_persen').value = data.diskon_persen;
            document.getElementById('benefit_text').value = data.benefit_text;
            document.getElementById('icon').value = data.icon;
            document.getElementById('warna').value = data.warna;
            document.getElementById('urutan').value = data.urutan;
            document.getElementById('is_active').checked = data.is_active == 1;
            document.getElementById('modalTitle').textContent = 'Edit Kategori Member';
            updateLabels();
            $('#modalForm').modal('show');
        }
        
        function hapusData(id, nama) {
            if(confirm('Yakin ingin menghapus kategori ' + nama + '?')) {
                document.getElementById('hapus_id').value = id;
                document.getElementById('btnHapus').click();
            }
        }
        
        function updateLabels() {
            var tipe = document.getElementById('tipe_kategori').value;
            if(tipe == 'nominal') {
                document.getElementById('label_min').textContent = 'Nilai Minimum (Rp)';
                document.getElementById('label_max').textContent = 'Nilai Maksimum (Rp)';
                document.getElementById('help_min').textContent = 'Contoh: 2000000';
            } else {
                document.getElementById('label_min').textContent = 'Jumlah Kunjungan Minimum';
                document.getElementById('label_max').textContent = 'Jumlah Kunjungan Maksimum';
                document.getElementById('help_min').textContent = 'Contoh: 5';
            }
        }
    </script>
</body>
</html>

<?php
}
?>
