<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";

    // Data User
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'];
    $pwd = $tm_cari['password'];
    $lvl_akses = $tm_cari['user_akses'];
    $foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

    // Data Cabang
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'];
    $tipe_cabang = $tm_cari['tipe_cabang'];

    // Process form submission
    if (isset($_POST['btnsimpan'])) {
        $tipe_item = $_POST['tipe_item'];
        $nama_item = $_POST['txtnama'];
        $jenis = $_POST['cbojenis'];
        $satuan = $_POST['cbosatuan'];
        $harga_beli = $_POST['txthargabeli'];
        $harga_jual = $_POST['txthargajual'];
        $supplier = $_POST['cbosupplier'] ?? '';
        $rak_barang = $_POST['cborak'] ?? '';
        
        if ($tipe_item == 'ORI') {
            // ORI (Genuine Part) Processing
            $merek = $_POST['cbomerek'];
            $kode_part = $_POST['txtkodepart'];
            $nama_resmi = $_POST['txtnamaresmi'];
            
            // Validate if part code already exists
            $check_query = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE noitem='$kode_part'");
            $check_result = mysqli_fetch_array($check_query);
            
            if ($check_result['count'] > 0) {
                $error_msg = "Kode part sudah ada dalam database!";
            } else {
                // Insert ORI item
                $insert_query = "INSERT INTO tblitem (
                    noitem, namaitem, jenis, satuan, hargapokok, hargajual, 
                    supplier, rakbarang, tipe_item, merek, kode_part_resmi, 
                    nama_part_resmi, status_validasi, statusitem, created_by
                ) VALUES (
                    '$kode_part', '$nama_resmi', '$jenis', '$satuan', '$harga_beli', '$harga_jual',
                    '$supplier', '$rak_barang', 'ORI', '$merek', '$kode_part', '$nama_resmi',
                    'validated', '1', '$id_user'
                )";
                
                if (mysqli_query($koneksi, $insert_query)) {
                    $success_msg = "Item ORI berhasil ditambahkan!";
                } else {
                    $error_msg = "Gagal menambahkan item: " . mysqli_error($koneksi);
                }
            }
        } else {
            // NON-ORI (Aftermarket/Imitasi) Processing
            $penggunaan_motor = $_POST['txtpenggunaan'];
            $merek_tipe = $_POST['txtmerektipe'];
            $kategori_rak = $_POST['cbokategorirak'];
            
            // Generate auto code IM-XXYYYY
            $prefix = "IM";
            
            // Get last number for this category
            $last_query = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(noitem, 6) AS UNSIGNED)) as last_num 
                                               FROM tblitem 
                                               WHERE noitem LIKE '$prefix-$kategori_rak%'");
            $last_result = mysqli_fetch_array($last_query);
            $next_num = ($last_result['last_num'] ?? 0) + 1;
            $kode_auto = $prefix . "-" . $kategori_rak . str_pad($next_num, 4, '0', STR_PAD_LEFT);
            
            // Format nama item: [Nama Part] [Penggunaan Motor] IMI
            $nama_formatted = $nama_item . " " . $penggunaan_motor . " IMI";
            
            // Insert NON-ORI item
            $insert_query = "INSERT INTO tblitem (
                noitem, namaitem, jenis, satuan, hargapokok, hargajual,
                supplier, rakbarang, tipe_item, penggunaan_motor, merek_tipe,
                kategori_rak, status_validasi, statusitem, created_by
            ) VALUES (
                '$kode_auto', '$nama_formatted', '$jenis', '$satuan', '$harga_beli', '$harga_jual',
                '$supplier', '$rak_barang', 'NON_ORI', '$penggunaan_motor', '$merek_tipe',
                '$kategori_rak', 'pending_validation', '1', '$id_user'
            )";
            
            if (mysqli_query($koneksi, $insert_query)) {
                $success_msg = "Item NON-ORI berhasil ditambahkan dengan kode: $kode_auto";
            } else {
                $error_msg = "Gagal menambahkan item: " . mysqli_error($koneksi);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Tambah Master Item - FitMotor</title>
    <meta name="description" content="Tambah Master Item dengan klasifikasi ORI/NON-ORI" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

    <style>
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .section-header {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .tipe-selection {
            background: #fff;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tipe-selection:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .tipe-selection.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .brand-links {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .brand-link {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 5px 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        .brand-link:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }
        .validation-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
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
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-wrench"></i> FitMotor - Tambah Master Item</small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right">
                <ul class="nav ace-nav">
                    <li class="grey dropdown-modal">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <img class="nav-user-photo" src="<?php echo $foto_user; ?>" alt="<?php echo $_nama; ?>" />
                            <span class="user-info"><small>Welcome,</small><?php echo $_nama; ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_master01a.php"; ?>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Data Master</a></li>
                        <li><a href="barang.php">Master Barang</a></li>
                        <li class="active">Tambah Item</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>Tambah Master Item<small> <i class="ace-icon fa fa-angle-double-right"></i> Sistem Klasifikasi ORI/NON-ORI</small></h1>
                    </div>

                    <?php if (isset($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="ace-icon fa fa-check"></i> <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="ace-icon fa fa-times"></i> <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <form class="form-horizontal" method="post" action="save_barang_improved.php" id="formAddItem">
                        <!-- Step 1: Pilih Tipe Item -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="ace-icon fa fa-tag"></i> Step 1: Pilih Tipe Item
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="tipe-selection" data-tipe="ORI">
                                        <input type="radio" name="tipe_item" value="ORI" id="tipe_ori" required>
                                        <label for="tipe_ori" style="cursor: pointer; margin-left: 10px;">
                                            <strong><i class="ace-icon fa fa-star text-success"></i> ORI (Genuine Part)</strong>
                                            <br><small>Part asli dari pabrikan resmi (Honda, Yamaha, Suzuki, dll)</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="tipe-selection" data-tipe="NON_ORI">
                                        <input type="radio" name="tipe_item" value="NON_ORI" id="tipe_non_ori" required>
                                        <label for="tipe_non_ori" style="cursor: pointer; margin-left: 10px;">
                                            <strong><i class="ace-icon fa fa-cog text-warning"></i> NON ORI (Aftermarket/Imitasi)</strong>
                                            <br><small>Part pengganti dari produsen lain/imitasi</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form untuk ORI -->
                        <div id="form_ori" class="form-section" style="display: none;">
                            <div class="section-header">
                                <i class="ace-icon fa fa-star text-success"></i> Form ORI (Genuine Part)
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Merek Pabrikan:</label>
                                        <div class="col-sm-8">
                                            <select name="cbomerek" id="cbomerek" class="form-control">
                                                <option value="">- Pilih Merek -</option>
                                                <option value="HONDA">Honda</option>
                                                <option value="YAMAHA">Yamaha</option>
                                                <option value="SUZUKI">Suzuki</option>
                                                <option value="KAWASAKI">Kawasaki</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Kode Part Resmi:</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="txtkodepart" id="txtkodepart" class="form-control" 
                                                   placeholder="Masukkan part number resmi" autocomplete="off">
                                            <div class="validation-info">
                                                <small><i class="fa fa-info-circle"></i> Gunakan kode part sesuai dengan part number resmi dari pabrikan</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="brand_links" class="brand-links" style="display: none;">
                                        <strong>Link Validasi Part Number:</strong><br>
                                        <a href="#" id="link_honda" class="brand-link" target="_blank" style="display: none;">
                                            <i class="fa fa-external-link"></i> Honda Parts Catalog
                                        </a>
                                        <a href="#" id="link_yamaha" class="brand-link" target="_blank" style="display: none;">
                                            <i class="fa fa-external-link"></i> Yamaha Parts Catalog
                                        </a>
                                        <a href="#" id="link_suzuki" class="brand-link" target="_blank" style="display: none;">
                                            <i class="fa fa-external-link"></i> Suzuki Parts Catalog
                                        </a>
                                        <a href="#" id="link_kawasaki" class="brand-link" target="_blank" style="display: none;">
                                            <i class="fa fa-external-link"></i> Kawasaki Parts Catalog
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Nama Part Resmi:</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="txtnamaresmi" id="txtnamaresmi" class="form-control" 
                                                   placeholder="Nama sesuai catalog resmi" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Nama Item (Display):</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="txtnama" id="txtnama_ori" class="form-control" 
                                                   placeholder="Nama untuk tampilan di sistem" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form untuk NON-ORI -->
                        <div id="form_non_ori" class="form-section" style="display: none;">
                            <div class="section-header">
                                <i class="ace-icon fa fa-cog text-warning"></i> Form NON-ORI (Aftermarket/Imitasi)
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Nama Part:</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="txtnama" id="txtnama_non_ori" class="form-control" 
                                                   placeholder="Contoh: KABEL GAS" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Penggunaan Motor:</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="txtpenggunaan" id="txtpenggunaan" class="form-control" 
                                                   placeholder="Contoh: H. BEAT" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Merek/Tipe/Ukuran:</label>
                                        <div class="col-sm-8">
                                            <input type="text" name="txtmerektipe" id="txtmerektipe" class="form-control" 
                                                   placeholder="Contoh: ASPIRA, TDR, dll" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Kategori Rak:</label>
                                        <div class="col-sm-8">
                                            <select name="cbokategorirak" id="cbokategorirak" class="form-control">
                                                <option value="">- Pilih Kategori -</option>
                                                <option value="KB">KB - Kabel</option>
                                                <option value="EL">EL - Kelistrikan</option>
                                                <option value="RM">RM - Rem</option>
                                                <option value="MS">MS - Mesin</option>
                                                <option value="CV">CV - CVT</option>
                                                <option value="RD">RD - Roda</option>
                                                <option value="CR">CR - Carbu</option>
                                                <option value="FL">FL - Filter</option>
                                                <option value="CH">CH - Cairan</option>
                                                <option value="BD">BD - Baud</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <strong>Auto Generate Code:</strong><br>
                                        Format: IM-[Kategori][Nomor Urut]<br>
                                        <small>Contoh: IM-KB0001 untuk kabel pertama</small>
                                        <div id="code_preview" style="margin-top: 10px; display: none;">
                                            <strong>Kode yang akan dibuat:</strong> <span id="preview_code" class="text-primary"></span>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning">
                                        <strong>Format Nama:</strong><br>
                                        [Nama Part] [Penggunaan Motor] IMI<br>
                                        <small>Contoh: KABEL GAS H. BEAT IMI</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Applicable Part -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="ace-icon fa fa-motorcycle"></i> Applicable Part - Tipe Motor yang Cocok
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> 
                                <strong>Pilih tipe motor/kendaraan yang cocok dengan part ini.</strong><br>
                                Ini akan membantu dalam pencarian dan rekomendasi part yang tepat untuk setiap motor.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="widget-box">
                                        <div class="widget-header widget-header-small">
                                            <h6 class="widget-title">Kolom 1</h6>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main" style="max-height: 300px; overflow-y: auto;">
                                                <table class="table table-condensed">
                                                    <tbody>
                                                    <?php 
                                                        $sql = mysqli_query($koneksi,"SELECT kode_tipe, tipe FROM tbtipe_motor ORDER BY tipe LIMIT 30");
                                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                    ?>
                                                        <tr>
                                                            <td class="center" style="width: 20px;">
                                                                <input type="checkbox" name="hapus1[]" value="<?php echo $tampil['kode_tipe']; ?>" class="ace">
                                                                <span class="lbl"></span>
                                                            </td>
                                                            <td><small><?php echo $tampil['tipe']?></small></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="widget-box">
                                        <div class="widget-header widget-header-small">
                                            <h6 class="widget-title">Kolom 2</h6>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main" style="max-height: 300px; overflow-y: auto;">
                                                <table class="table table-condensed">
                                                    <tbody>
                                                    <?php 
                                                        $sql = mysqli_query($koneksi,"SELECT kode_tipe, tipe FROM tbtipe_motor ORDER BY tipe LIMIT 30,30");
                                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                    ?>
                                                        <tr>
                                                            <td class="center" style="width: 20px;">
                                                                <input type="checkbox" name="hapus2[]" value="<?php echo $tampil['kode_tipe']; ?>" class="ace">
                                                                <span class="lbl"></span>
                                                            </td>
                                                            <td><small><?php echo $tampil['tipe']?></small></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="widget-box">
                                        <div class="widget-header widget-header-small">
                                            <h6 class="widget-title">Kolom 3</h6>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main" style="max-height: 300px; overflow-y: auto;">
                                                <table class="table table-condensed">
                                                    <tbody>
                                                    <?php 
                                                        $sql = mysqli_query($koneksi,"SELECT kode_tipe, tipe FROM tbtipe_motor ORDER BY tipe LIMIT 60,30");
                                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                    ?>
                                                        <tr>
                                                            <td class="center" style="width: 20px;">
                                                                <input type="checkbox" name="hapus3[]" value="<?php echo $tampil['kode_tipe']; ?>" class="ace">
                                                                <span class="lbl"></span>
                                                            </td>
                                                            <td><small><?php echo $tampil['tipe']?></small></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="widget-box">
                                        <div class="widget-header widget-header-small">
                                            <h6 class="widget-title">Kolom 4</h6>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main" style="max-height: 300px; overflow-y: auto;">
                                                <table class="table table-condensed">
                                                    <tbody>
                                                    <?php 
                                                        $sql = mysqli_query($koneksi,"SELECT kode_tipe, tipe FROM tbtipe_motor ORDER BY tipe LIMIT 90,30");
                                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                    ?>
                                                        <tr>
                                                            <td class="center" style="width: 20px;">
                                                                <input type="checkbox" name="hapus4[]" value="<?php echo $tampil['kode_tipe']; ?>" class="ace">
                                                                <span class="lbl"></span>
                                                            </td>
                                                            <td><small><?php echo $tampil['tipe']?></small></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-warning">
                                        <strong>Tips:</strong>
                                        <ul style="margin-bottom: 0;">
                                            <li>Untuk <strong>ORI parts</strong>: Pilih motor sesuai dengan part number yang tertera di katalog resmi</li>
                                            <li>Untuk <strong>NON-ORI parts</strong>: Pilih semua motor yang bisa menggunakan part aftermarket ini</li>
                                            <li>Semakin spesifik pilihan motor, semakin akurat rekomendasi part untuk customer</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Data Umum -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="ace-icon fa fa-list"></i> Data Umum Item
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Jenis:</label>
                                        <div class="col-sm-8">
                                            <select name="cbojenis" id="cbojenis" class="form-control" required>
                                                <option value="">- Pilih Jenis -</option>
                                                <?php
                                                $sql = "SELECT jenis, namajenis FROM tblitemjenis WHERE status='1'";
                                                $result = mysqli_query($koneksi, $sql);
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='{$row['jenis']}'>{$row['namajenis']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Satuan:</label>
                                        <div class="col-sm-8">
                                            <select name="cbosatuan" id="cbosatuan" class="form-control" required>
                                                <option value="">- Pilih Satuan -</option>
                                                <?php
                                                $sql = "SELECT satuan, namasatuan FROM tblitemsatuan";
                                                $result = mysqli_query($koneksi, $sql);
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='{$row['satuan']}'>{$row['namasatuan']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Harga Beli:</label>
                                        <div class="col-sm-8">
                                            <input type="number" name="txthargabeli" id="txthargabeli" class="form-control" 
                                                   placeholder="0" min="0" step="1" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Harga Jual:</label>
                                        <div class="col-sm-8">
                                            <input type="number" name="txthargajual" id="txthargajual" class="form-control" 
                                                   placeholder="0" min="0" step="1" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Supplier:</label>
                                        <div class="col-sm-8">
                                            <select name="cbosupplier" id="cbosupplier" class="form-control">
                                                <option value="">- Pilih Supplier -</option>
                                                <?php
                                                $sql = "SELECT nosupplier, namasupplier FROM tblsupplier ORDER BY namasupplier";
                                                $result = mysqli_query($koneksi, $sql);
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='{$row['nosupplier']}'>{$row['namasupplier']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">Rak Barang:</label>
                                        <div class="col-sm-8">
                                            <select name="cborak" id="cborak" class="form-control">
                                                <option value="">- Pilih Rak -</option>
                                                <?php
                                                $sql = "SELECT id, rak_barang FROM tbrakbarang";
                                                $result = mysqli_query($koneksi, $sql);
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='{$row['id']}'>{$row['rak_barang']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-section">
                            <div class="text-center">
                                <button type="submit" name="btnsimpan" class="btn btn-success btn-lg">
                                    <i class="ace-icon fa fa-save"></i> Simpan Item
                                </button>
                                <a href="barang.php" class="btn btn-default btn-lg">
                                    <i class="ace-icon fa fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        $(document).ready(function() {
            // Handle tipe item selection
            $('input[name="tipe_item"]').change(function() {
                var tipe = $(this).val();
                
                // Reset forms
                $('#form_ori, #form_non_ori').hide();
                $('.tipe-selection').removeClass('selected');
                
                // Show selected form
                $(this).closest('.tipe-selection').addClass('selected');
                
                if (tipe === 'ORI') {
                    $('#form_ori').show();
                    // Clear NON-ORI fields
                    $('#form_non_ori input, #form_non_ori select').val('');
                } else if (tipe === 'NON_ORI') {
                    $('#form_non_ori').show();
                    // Clear ORI fields
                    $('#form_ori input, #form_ori select').val('');
                }
            });

            // Handle brand selection for ORI
            $('#cbomerek').change(function() {
                var merek = $(this).val();
                
                if (merek) {
                    $('#brand_links').show();
                    $('.brand-link').hide();
                    
                    // Show relevant brand link with improved URLs
                    if (merek === 'HONDA') {
                        $('#link_honda').show().attr('href', 'https://www.honda.co.jp/parts/');
                    } else if (merek === 'YAMAHA') {
                        $('#link_yamaha').show().attr('href', 'https://global.yamaha-motor.com/');
                    } else if (merek === 'SUZUKI') {
                        $('#link_suzuki').show().attr('href', 'https://www.suzuki.co.jp/');
                    } else if (merek === 'KAWASAKI') {
                        $('#link_kawasaki').show().attr('href', 'https://www.kawasaki.com/');
                    }
                    
                    // Clear part number when brand changes
                    $('#txtkodepart, #txtnamaresmi').val('');
                } else {
                    $('#brand_links').hide();
                    $('#txtkodepart, #txtnamaresmi').val('');
                }
            });

            // Part number validation based on brand
            $('#txtkodepart').on('input', function() {
                var partNumber = $(this).val().trim().toUpperCase();
                var brand = $('#cbomerek').val();
                var isValid = false;
                var message = '';
                
                if (partNumber && brand) {
                    // Basic validation patterns for each brand
                    if (brand === 'HONDA') {
                        // Honda: typically 5-5-3 format like 06455-KVB-900
                        isValid = /^[0-9A-Z]{3,7}-[0-9A-Z]{3,4}-[0-9A-Z]{3,4}$/.test(partNumber);
                        message = isValid ? 'Format Honda valid' : 'Format Honda biasanya: XXXXX-XXX-XXX';
                    } else if (brand === 'YAMAHA') {
                        // Yamaha: various formats like 5SL-F5885-00
                        isValid = /^[0-9A-Z]{3,6}-[0-9A-Z]{3,6}-[0-9A-Z]{2,3}$/.test(partNumber);
                        message = isValid ? 'Format Yamaha valid' : 'Format Yamaha biasanya: XXX-XXXXX-XX';
                    } else if (brand === 'SUZUKI') {
                        // Suzuki: formats like 09401-12127
                        isValid = /^[0-9A-Z]{5,6}-[0-9A-Z]{5,6}$/.test(partNumber);
                        message = isValid ? 'Format Suzuki valid' : 'Format Suzuki biasanya: XXXXX-XXXXX';
                    } else if (brand === 'KAWASAKI') {
                        // Kawasaki: formats like 11061-1485
                        isValid = /^[0-9A-Z]{5,6}-[0-9A-Z]{4,5}$/.test(partNumber);
                        message = isValid ? 'Format Kawasaki valid' : 'Format Kawasaki biasanya: XXXXX-XXXX';
                    }
                    
                    // Update validation display
                    var $validationInfo = $(this).siblings('.validation-info');
                    if (isValid) {
                        $validationInfo.html('<small class="text-success"><i class="fa fa-check"></i> ' + message + '</small>');
                    } else {
                        $validationInfo.html('<small class="text-warning"><i class="fa fa-warning"></i> ' + message + '</small>');
                    }
                }
                
                // Update input value to uppercase
                $(this).val(partNumber);
            });

            // Auto format nama for NON-ORI and preview code
            $('#txtnama_non_ori, #txtpenggunaan, #cbokategorirak').on('input change', function() {
                var nama = $('#txtnama_non_ori').val();
                var penggunaan = $('#txtpenggunaan').val();
                var kategori = $('#cbokategorirak').val();
                
                // Preview formatted name
                if (nama && penggunaan) {
                    var namaFormatted = nama + ' ' + penggunaan + ' IMI';
                    if (!$('#preview_nama').length) {
                        $('#txtpenggunaan').after('<small id="preview_nama" class="text-muted"></small>');
                    }
                    $('#preview_nama').text('Preview: ' + namaFormatted);
                }
                
                // Preview auto-generated code
                if (kategori) {
                    // Get next number via AJAX
                    $.ajax({
                        url: 'get_next_code.php',
                        method: 'POST',
                        data: { kategori: kategori },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#preview_code').text(response.code);
                                $('#code_preview').show();
                            }
                        },
                        error: function() {
                            // Fallback preview without actual number
                            $('#preview_code').text('IM-' + kategori + 'XXXX');
                            $('#code_preview').show();
                        }
                    });
                } else {
                    $('#code_preview').hide();
                }
            });

            // Auto calculate harga jual (margin 30%)
            $('#txthargabeli').on('input', function() {
                var hargaBeli = parseFloat($(this).val()) || 0;
                if (hargaBeli > 0) {
                    var hargaJual = Math.ceil(hargaBeli * 1.3);
                    $('#txthargajual').val(hargaJual);
                }
            });

            // Form validation
            $('#formAddItem').submit(function(e) {
                var tipeItem = $('input[name="tipe_item"]:checked').val();
                
                if (!tipeItem) {
                    alert('Pilih tipe item terlebih dahulu!');
                    e.preventDefault();
                    return false;
                }
                
                if (tipeItem === 'ORI') {
                    var merek = $('#cbomerek').val();
                    var kodePart = $('#txtkodepart').val();
                    var namaResmi = $('#txtnamaresmi').val();
                    
                    if (!merek || !kodePart || !namaResmi) {
                        alert('Lengkapi semua field untuk item ORI!');
                        e.preventDefault();
                        return false;
                    }
                } else if (tipeItem === 'NON_ORI') {
                    var nama = $('#txtnama_non_ori').val();
                    var penggunaan = $('#txtpenggunaan').val();
                    var kategori = $('#cbokategorirak').val();
                    
                    if (!nama || !penggunaan || !kategori) {
                        alert('Lengkapi semua field untuk item NON-ORI!');
                        e.preventDefault();
                        return false;
                    }
                }
                
                return true;
            });
        });
    </script>
</body>
</html>

<?php
}
?>