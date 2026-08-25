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

    // ========================================
    // GET SETTING KATEGORI MEMBER (Global Toggle)
    // ========================================
    $setting_nominal_enabled = 1;
    $setting_kunjungan_enabled = 1;
    
    $query_setting = mysqli_query($koneksi, "SELECT tipe_kategori, is_enabled FROM setting_kategori_member");
    if($query_setting) {
        while($row_setting = mysqli_fetch_assoc($query_setting)) {
            if($row_setting['tipe_kategori'] == 'nominal') {
                $setting_nominal_enabled = $row_setting['is_enabled'];
            }
            if($row_setting['tipe_kategori'] == 'kunjungan') {
                $setting_kunjungan_enabled = $row_setting['is_enabled'];
            }
        }
    }

    // Handle form submission
    $message = '';
    $message_type = '';
    
    // ========================================
    // HANDLE TOGGLE SETTING (AJAX via POST)
    // ========================================
    if(isset($_POST['btn_toggle_setting'])) {
        $toggle_type = mysqli_real_escape_string($koneksi, $_POST['toggle_type']);
        $toggle_value = intval($_POST['toggle_value']);
        
        $sql_toggle = "INSERT INTO setting_kategori_member (tipe_kategori, is_enabled, keterangan) 
                       VALUES ('$toggle_type', $toggle_value, 'Member berdasarkan $toggle_type')
                       ON DUPLICATE KEY UPDATE is_enabled = $toggle_value";
        
        if(mysqli_query($koneksi, $sql_toggle)) {
            $message = "Setting sistem $toggle_type berhasil diubah!";
            $message_type = 'success';
            // Refresh setting values
            if($toggle_type == 'nominal') $setting_nominal_enabled = $toggle_value;
            if($toggle_type == 'kunjungan') $setting_kunjungan_enabled = $toggle_value;
        } else {
            $message = 'Error: ' . mysqli_error($koneksi);
            $message_type = 'danger';
        }
    }
    
    if(isset($_POST['btn_simpan'])) {
        $id_kategori = $_POST['id_kategori'] ?? '';
        $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
        $tipe_kategori = mysqli_real_escape_string($koneksi, $_POST['tipe_kategori']);
        $min_value = mysqli_real_escape_string($koneksi, $_POST['min_value']);
        $max_value = $_POST['max_value'] == '' ? NULL : $_POST['max_value'];
        $diskon_persen = mysqli_real_escape_string($koneksi, $_POST['diskon_persen']);
        $diskon_jasa = floatval($_POST['diskon_jasa'] ?? 0);
        $diskon_barang = floatval($_POST['diskon_barang'] ?? 0);
        $benefit_text = mysqli_real_escape_string($koneksi, $_POST['benefit_text']);
        $icon = mysqli_real_escape_string($koneksi, $_POST['icon']);
        $warna = mysqli_real_escape_string($koneksi, $_POST['warna']);
        $urutan = mysqli_real_escape_string($koneksi, $_POST['urutan']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if(empty($id_kategori)) {
            // Insert
            $sql = "INSERT INTO master_kategori_member 
                    (nama_kategori, tipe_kategori, min_value, max_value, diskon_persen, diskon_jasa, diskon_barang, benefit_text, icon, warna, urutan, is_active) 
                    VALUES 
                    ('$nama_kategori', '$tipe_kategori', '$min_value', " . ($max_value === NULL ? "NULL" : "'$max_value'") . ", '$diskon_persen', '$diskon_jasa', '$diskon_barang', '$benefit_text', '$icon', '$warna', '$urutan', '$is_active')";
            
            if(mysqli_query($koneksi, $sql)) {
                // Automatically add to setting_highlight_member
                $check_setting = mysqli_query($koneksi, "SELECT id FROM setting_highlight_member WHERE kategori_member = '$nama_kategori'");
                if(mysqli_num_rows($check_setting) == 0) {
                    $sql_setting = "INSERT INTO setting_highlight_member 
                                   (kategori_member, background_color, text_color, border_color, border_width, is_bold, opacity, is_active)
                                   VALUES 
                                   ('$nama_kategori', '#FFFFFF', '#000000', '#CCCCCC', 1, 0, 1.0, 1)";
                    mysqli_query($koneksi, $sql_setting);
                }

                $message = 'Data berhasil ditambahkan!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($koneksi);
                $message_type = 'danger';
            }
        } else {
            // Get old name for updating setting_highlight_member
            $q_old = mysqli_query($koneksi, "SELECT nama_kategori FROM master_kategori_member WHERE id_kategori = '$id_kategori'");
            $old_data = mysqli_fetch_assoc($q_old);
            $old_name = $old_data['nama_kategori'];

            // Update
            $sql = "UPDATE master_kategori_member SET 
                    nama_kategori = '$nama_kategori',
                    tipe_kategori = '$tipe_kategori',
                    min_value = '$min_value',
                    max_value = " . ($max_value === NULL ? "NULL" : "'$max_value'") . ",
                    diskon_persen = '$diskon_persen',
                    diskon_jasa = '$diskon_jasa',
                    diskon_barang = '$diskon_barang',
                    benefit_text = '$benefit_text',
                    icon = '$icon',
                    warna = '$warna',
                    urutan = '$urutan',
                    is_active = '$is_active'
                    WHERE id_kategori = '$id_kategori'";
            
            if(mysqli_query($koneksi, $sql)) {
                // Update setting_highlight_member if name changed
                if($old_name != $nama_kategori) {
                    mysqli_query($koneksi, "UPDATE setting_highlight_member SET kategori_member = '$nama_kategori' WHERE kategori_member = '$old_name'");
                }
                
                $message = 'Data berhasil diupdate!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($koneksi);
                $message_type = 'danger';
            }
        }
    }
    
    if(isset($_POST['btn_hapus'])) {
        $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
        
        // Get name before delete
        $q_del = mysqli_query($koneksi, "SELECT nama_kategori FROM master_kategori_member WHERE id_kategori = '$id_kategori'");
        $del_data = mysqli_fetch_assoc($q_del);
        $del_name = $del_data['nama_kategori'];

        $sql = "DELETE FROM master_kategori_member WHERE id_kategori = '$id_kategori'";
        
        if(mysqli_query($koneksi, $sql)) {
            // Delete from setting_highlight_member
            mysqli_query($koneksi, "DELETE FROM setting_highlight_member WHERE kategori_member = '$del_name'");

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
    <title>Master Kategori Member - <?php include "../lib/titel.php"; ?></title>
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
        /* Toggle Switch Style */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 28px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 28px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #5cb85c;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(32px);
        }
        .setting-card {
            background: #f9f9f9;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .setting-card.disabled-card {
            background: #fff5f5;
            border-color: #f5c6cb;
        }
        .diskon-preview {
            font-size: 11px;
            color: #666;
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
                            
                            <!-- ========================================== -->
                            <!-- SETTING GLOBAL: Toggle On/Off Sistem -->
                            <!-- ========================================== -->
                            <div class="row" style="margin-bottom: 20px;">
                                <div class="col-sm-6">
                                    <div class="setting-card <?php echo !$setting_nominal_enabled ? 'disabled-card' : ''; ?>">
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <h5 style="margin: 0 0 5px 0;">
                                                    <i class="fa fa-money text-success"></i>
                                                    <strong>Sistem Member Berdasarkan Nominal</strong>
                                                </h5>
                                                <small class="text-muted">Kategori member ditentukan dari total nominal transaksi</small>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <form method="POST" id="formToggleNominal" style="display: inline;">
                                                    <input type="hidden" name="btn_toggle_setting" value="1">
                                                    <input type="hidden" name="toggle_type" value="nominal">
                                                    <input type="hidden" name="toggle_value" id="toggle_nominal_value" value="<?php echo $setting_nominal_enabled ? 0 : 1; ?>">
                                                    <label class="toggle-switch">
                                                        <input type="checkbox" <?php echo $setting_nominal_enabled ? 'checked' : ''; ?> onchange="toggleSetting('nominal', this.checked)">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </form>
                                                <div style="margin-top: 5px;">
                                                    <span class="label label-<?php echo $setting_nominal_enabled ? 'success' : 'default'; ?>">
                                                        <?php echo $setting_nominal_enabled ? 'AKTIF' : 'NONAKTIF'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="setting-card <?php echo !$setting_kunjungan_enabled ? 'disabled-card' : ''; ?>">
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <h5 style="margin: 0 0 5px 0;">
                                                    <i class="fa fa-users text-info"></i>
                                                    <strong>Sistem Member Berdasarkan Kunjungan</strong>
                                                </h5>
                                                <small class="text-muted">Kategori member ditentukan dari jumlah kunjungan</small>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <form method="POST" id="formToggleKunjungan" style="display: inline;">
                                                    <input type="hidden" name="btn_toggle_setting" value="1">
                                                    <input type="hidden" name="toggle_type" value="kunjungan">
                                                    <input type="hidden" name="toggle_value" id="toggle_kunjungan_value" value="<?php echo $setting_kunjungan_enabled ? 0 : 1; ?>">
                                                    <label class="toggle-switch">
                                                        <input type="checkbox" <?php echo $setting_kunjungan_enabled ? 'checked' : ''; ?> onchange="toggleSetting('kunjungan', this.checked)">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </form>
                                                <div style="margin-top: 5px;">
                                                    <span class="label label-<?php echo $setting_kunjungan_enabled ? 'success' : 'default'; ?>">
                                                        <?php echo $setting_kunjungan_enabled ? 'AKTIF' : 'NONAKTIF'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tombol Tambah -->
                            <div class="clearfix" style="margin-bottom: 15px;">
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalForm" onclick="resetForm()">
                                    <i class="fa fa-plus"></i> Tambah Kategori Baru
                                </button>
                                
                                <div class="pull-right">
                                    <a href="setting_diskon_member_item.php" class="btn btn-warning btn-sm" style="margin-right: 5px;">
                                        <i class="fa fa-ban"></i> Atur Item Exclude Diskon
                                    </a>
                                    <a href="statistik_pelanggan_dashboard.php" class="btn btn-info btn-sm">
                                        <i class="fa fa-bar-chart"></i> Dashboard Statistik
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Tabel Kategori Member -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="12%">Kategori</th>
                                            <th width="8%">Tipe</th>
                                            <th width="12%">Min. Value</th>
                                            <th width="12%">Max. Value</th>
                                            <th width="18%">Diskon</th>
                                            <th width="20%">Benefit</th>
                                            <th width="8%">Status</th>
                                            <th width="8%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $query = mysqli_query($koneksi, "SELECT * FROM master_kategori_member 
                                                                          ORDER BY tipe_kategori ASC, urutan ASC, min_value ASC");
                                        while($row = mysqli_fetch_array($query)):
                                            // Check if global setting is disabled for this type
                                            $is_type_disabled = false;
                                            if($row['tipe_kategori'] == 'nominal' && !$setting_nominal_enabled) {
                                                $is_type_disabled = true;
                                            }
                                            if($row['tipe_kategori'] == 'kunjungan' && !$setting_kunjungan_enabled) {
                                                $is_type_disabled = true;
                                            }
                                        ?>
                                        <tr style="<?php echo $is_type_disabled ? 'opacity: 0.5; background: #f5f5f5;' : ''; ?>">
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <?php 
                                                $status = $row['nama_kategori'];
                                                $icon = $row['icon'] ?? '';
                                                $color = $row['warna'] ?? '#6c757d';
                                                $text_color = '#fff'; 
                                                ?>
                                                <span class="badge-preview" style="background: <?php echo $color; ?>; color: <?php echo $text_color; ?>;">
                                                    <?php echo $icon . ' ' . $status; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($row['tipe_kategori'] == 'nominal'): ?>
                                                    <span class="label label-success">Nominal</span>
                                                <?php else: ?>
                                                    <span class="label label-info">Kunjungan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if($row['tipe_kategori'] == 'nominal') {
                                                    echo 'Rp ' . number_format($row['min_value'], 0, ',', '.');
                                                } else {
                                                    echo number_format($row['min_value'], 0) . ' x';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if($row['max_value'] === NULL) {
                                                    echo '<span class="label label-inverse">Unlimited</span>';
                                                } else {
                                                    if($row['tipe_kategori'] == 'nominal') {
                                                        echo 'Rp ' . number_format($row['max_value'], 0, ',', '.');
                                                    } else {
                                                        echo number_format($row['max_value'], 0) . ' x';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $diskon_jasa = $row['diskon_jasa'] ?? $row['diskon_persen'] ?? 0;
                                                $diskon_barang = $row['diskon_barang'] ?? 0;
                                                ?>
                                                <div>
                                                    <i class="fa fa-wrench text-primary"></i> 
                                                    <strong>Jasa:</strong> <?php echo number_format($diskon_jasa, 1); ?>%
                                                </div>
                                                <div>
                                                    <i class="fa fa-cube text-warning"></i> 
                                                    <strong>Barang:</strong> <?php echo number_format($diskon_barang, 1); ?>%
                                                </div>
                                            </td>
                                            <td><div class="benefit-list"><?php echo nl2br($row['benefit_text']); ?></div></td>
                                            <td>
                                                <?php if($is_type_disabled): ?>
                                                    <span class="label label-default">Sistem OFF</span>
                                                <?php elseif($row['is_active']): ?>
                                                    <span class="label label-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="label label-danger">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-xs btn-info" onclick="editData(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-xs btn-danger" onclick="hapusData(<?php echo $row['id_kategori']; ?>, '<?php echo $row['nama_kategori']; ?>')">
                                                    <i class="fa fa-trash"></i>
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
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Nama Kategori <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_kategori" id="nama_kategori" class="form-control" required placeholder="Contoh: Diamond">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Tipe Kategori</label>
                                    <select name="tipe_kategori" id="tipe_kategori" class="form-control">
                                        <option value="nominal">Berdasarkan Nominal</option>
                                        <option value="kunjungan">Berdasarkan Kunjungan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Min. Value <span class="text-danger">*</span></label>
                                    <input type="number" name="min_value" id="min_value" class="form-control" required min="0" step="0.01">
                                    <small class="text-muted">Nominal (Rp) atau Jumlah Kunjungan</small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Max. Value</label>
                                    <input type="number" name="max_value" id="max_value" class="form-control" min="0" step="0.01">
                                    <small class="text-muted">Kosongkan untuk unlimited</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ========================================== -->
                        <!-- DISKON TERPISAH: Jasa & Barang -->
                        <!-- ========================================== -->
                        <div class="alert alert-info" style="padding: 10px; margin-bottom: 15px;">
                            <i class="fa fa-info-circle"></i> <strong>Pengaturan Diskon Member</strong>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label><i class="fa fa-wrench text-primary"></i> Diskon Jasa (%)</label>
                                    <input type="number" name="diskon_jasa" id="diskon_jasa" class="form-control" min="0" max="100" value="0" step="0.1">
                                    <small class="text-muted">Untuk biaya jasa servis</small>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label><i class="fa fa-cube text-warning"></i> Diskon Barang (%)</label>
                                    <input type="number" name="diskon_barang" id="diskon_barang" class="form-control" min="0" max="100" value="0" step="0.1">
                                    <small class="text-muted">Untuk sparepart/barang</small>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Urutan</label>
                                    <input type="number" name="urutan" id="urutan" class="form-control" min="0" value="1">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden field for backward compatibility -->
                        <input type="hidden" name="diskon_persen" id="diskon_persen" value="0">

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Icon (Emoji/Text)</label>
                                    <input type="text" name="icon" id="icon" class="form-control" placeholder="Contoh: 💎">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Warna Badge</label>
                                    <input type="color" name="warna" id="warna" class="form-control" value="#6c757d">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Benefit Member</label>
                            <textarea name="benefit_text" id="benefit_text" class="form-control" rows="4" placeholder="Tulis benefit per baris&#10;Contoh:&#10;• Diskon 10% jasa&#10;• Diskon 3% barang&#10;• Prioritas antrian"></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked> Aktif
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

    <!-- Scripts included in footer.php -->
    
    <script type="text/javascript">
        function resetForm() {
            document.getElementById('id_kategori').value = '';
            document.getElementById('nama_kategori').value = '';
            document.getElementById('tipe_kategori').value = 'nominal';
            document.getElementById('min_value').value = '';
            document.getElementById('max_value').value = '';
            document.getElementById('diskon_persen').value = '0';
            document.getElementById('diskon_jasa').value = '0';
            document.getElementById('diskon_barang').value = '0';
            document.getElementById('benefit_text').value = '';
            document.getElementById('icon').value = '';
            document.getElementById('warna').value = '#6c757d';
            document.getElementById('urutan').value = '1';
            document.getElementById('is_active').checked = true;
            document.getElementById('modalTitle').textContent = 'Tambah Kategori Member';
        }
        
        function editData(data) {
            document.getElementById('id_kategori').value = data.id_kategori;
            document.getElementById('nama_kategori').value = data.nama_kategori;
            document.getElementById('tipe_kategori').value = data.tipe_kategori;
            document.getElementById('min_value').value = data.min_value;
            document.getElementById('max_value').value = data.max_value || '';
            document.getElementById('diskon_persen').value = data.diskon_persen || 0;
            document.getElementById('diskon_jasa').value = data.diskon_jasa || data.diskon_persen || 0;
            document.getElementById('diskon_barang').value = data.diskon_barang || 0;
            document.getElementById('benefit_text').value = data.benefit_text;
            document.getElementById('icon').value = data.icon;
            document.getElementById('warna').value = data.warna;
            document.getElementById('urutan').value = data.urutan;
            document.getElementById('is_active').checked = (data.is_active == 1);
            document.getElementById('modalTitle').textContent = 'Edit Kategori Member';
            $('#modalForm').modal('show');
        }
        
        function hapusData(id, nama) {
            if(confirm('Yakin ingin menghapus kategori ' + nama + '?')) {
                document.getElementById('hapus_id').value = id;
                document.getElementById('btnHapus').click();
            }
        }
        
        // Toggle setting handler
        function toggleSetting(type, isChecked) {
            var value = isChecked ? 1 : 0;
            
            if(type === 'nominal') {
                document.getElementById('toggle_nominal_value').value = value;
                document.getElementById('formToggleNominal').submit();
            } else {
                document.getElementById('toggle_kunjungan_value').value = value;
                document.getElementById('formToggleKunjungan').submit();
            }
        }
        
        // Auto-update diskon_persen when diskon_jasa changes (for backward compatibility)
        document.getElementById('diskon_jasa').addEventListener('change', function() {
            document.getElementById('diskon_persen').value = this.value;
        });
    </script>
</body>
</html>
<?php
}
?>
