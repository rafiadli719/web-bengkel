<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";
    include "database_checker.php";

    // Auto-check and fix database if needed
    $db_fixed = autoCheckDatabase($koneksi);

    // Data User
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'];
    $lvl_akses = $tm_cari['user_akses'];
    $foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

    // Data Cabang
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'];

    // Get item to edit
    $kd_item = $_GET['kd'] ?? '';
    if (empty($kd_item)) {
        header("location: barang.php");
        exit;
    }

    // Get item details with all related data (handle missing tables)
    $query = "SELECT i.*,
                     COALESCE(i.tipe_item, 'NON_ORI') as tipe_item,
                     COALESCE(i.status_validasi, 'pending_validation') as status_validasi,
                     CASE WHEN j.namajenis IS NOT NULL THEN j.namajenis ELSE 'Unknown Type' END as namajenis,
                     CASE WHEN s.satuan IS NOT NULL THEN s.satuan ELSE 'Unknown Unit' END as satuan_name,
                     CASE WHEN sup.namasupplier IS NOT NULL THEN sup.namasupplier ELSE 'Unknown Supplier' END as nama_supplier,
                     CASE WHEN rak.rak_barang IS NOT NULL THEN rak.rak_barang ELSE 'Unknown Rack' END as rak_barang
              FROM tblitem i
              LEFT JOIN tbljenis j ON i.jenis = j.kodejenis
              LEFT JOIN tblsatuan s ON i.satuan = s.kodesatuan
              LEFT JOIN tblsupplier sup ON i.supplier = sup.nosupplier
              LEFT JOIN tbrakbarang rak ON i.rakbarang = rak.id
              WHERE i.noitem = '$kd_item'";

    $result = @mysqli_query($koneksi, $query);

    if (!$result) {
        // If query fails due to missing tables, try simpler query step by step
        $simple_query = "SELECT i.*,
                               COALESCE(i.tipe_item, 'NON_ORI') as tipe_item,
                               COALESCE(i.status_validasi, 'pending_validation') as status_validasi
                        FROM tblitem i
                        WHERE i.noitem = '$kd_item'";
        $result = @mysqli_query($koneksi, $simple_query);

        if (!$result) {
            // Try even simpler query
            $basic_query = "SELECT * FROM tblitem WHERE noitem = '$kd_item'";
            $result = @mysqli_query($koneksi, $basic_query);

            if (!$result) {
                echo "<div class='alert alert-danger'>Error: Item not found or database connection issue.</div>";
                echo "<p><a href='barang.php' class='btn btn-primary'>Back to Item List</a></p>";
                exit;
            }
        }

        $item = mysqli_fetch_array($result);
        if ($item) {
            // Set default values for missing fields
            $item['tipe_item'] = $item['tipe_item'] ?? 'NON_ORI';
            $item['status_validasi'] = $item['status_validasi'] ?? 'pending_validation';
            $item['namajenis'] = 'Unknown Type';
            $item['satuan_name'] = 'Unknown Unit';
            $item['nama_supplier'] = 'Unknown Supplier';
            $item['rak_barang'] = 'Unknown Rack';
        }
    } else {
        $item = mysqli_fetch_array($result);

        // Ensure required fields have values
        if ($item) {
            $item['tipe_item'] = $item['tipe_item'] ?? 'NON_ORI';
            $item['status_validasi'] = $item['status_validasi'] ?? 'pending_validation';
            $item['namajenis'] = $item['namajenis'] ?? 'Unknown Type';
            $item['satuan_name'] = $item['satuan_name'] ?? 'Unknown Unit';
            $item['nama_supplier'] = $item['nama_supplier'] ?? 'Unknown Supplier';
            $item['rak_barang'] = $item['rak_barang'] ?? 'Unknown Rack';
        }
    }

    if (!$item) {
        header("location: barang.php");
        exit;
    }

    // Get stock data (with error handling)
    $stock_query = "SELECT * FROM tblitem_stok WHERE noitem = '$kd_item' AND kode_cabang = '$kd_cabang'";
    $stock_result = @mysqli_query($koneksi, $stock_query);
    $stock_data = $stock_result ? mysqli_fetch_array($stock_result) : null;

    // Process form submission
    if (isset($_POST['btnsimpan'])) {
        mysqli_begin_transaction($koneksi);

        try {
            $tipe_item = mysqli_real_escape_string($koneksi, $_POST['tipe_item']);
            $nama_item = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
            $jenis = mysqli_real_escape_string($koneksi, $_POST['cbojenis']);
            $satuan = mysqli_real_escape_string($koneksi, $_POST['cbosatuan']);
            $harga_beli = floatval($_POST['txthargabeli']);
            $harga_jual = floatval($_POST['txthargajual']);
            $supplier = $_POST['cbosupplier'] ?? '';
            $rak_barang = $_POST['cborak'] ?? '';

            // Prepare base update query
            $update_fields = [
                "namaitem = '$nama_item'",
                "jenis = '$jenis'",
                "satuan = '$satuan'",
                "hargapokok = '$harga_beli'",
                "hargajual = '$harga_jual'",
                "supplier = '$supplier'",
                "rakbarang = '$rak_barang'",
                "tipe_item = '$tipe_item'",
                "updated_at = NOW()"
            ];

            // Handle type-specific fields
            if ($tipe_item == 'ORI') {
                $merek = $_POST['cbomerek'] ?? '';
                $kode_part = mysqli_real_escape_string($koneksi, $_POST['txtkodepart']);
                $nama_resmi = mysqli_real_escape_string($koneksi, $_POST['txtnamaresmi']);

                $update_fields[] = "merek = '$merek'";
                $update_fields[] = "kode_part_resmi = '$kode_part'";
                $update_fields[] = "nama_part_resmi = '$nama_resmi'";
                // Clear NON-ORI fields
                $update_fields[] = "penggunaan_motor = NULL";
                $update_fields[] = "merek_tipe = NULL";
                $update_fields[] = "kategori_rak = NULL";

                // ORI items are auto-validated
                $update_fields[] = "status_validasi = 'validated'";

            } else { // NON_ORI
                $penggunaan_motor = mysqli_real_escape_string($koneksi, $_POST['txtpenggunaan']);
                $merek_tipe = mysqli_real_escape_string($koneksi, $_POST['txtmerektipe']);
                $kategori_rak = $_POST['cbokategorirak'] ?? '';

                $update_fields[] = "penggunaan_motor = '$penggunaan_motor'";
                $update_fields[] = "merek_tipe = '$merek_tipe'";
                $update_fields[] = "kategori_rak = '$kategori_rak'";
                // Clear ORI fields
                $update_fields[] = "merek = NULL";
                $update_fields[] = "kode_part_resmi = NULL";
                $update_fields[] = "nama_part_resmi = NULL";

                // Format nama untuk NON-ORI: [Nama Part] [Penggunaan Motor] IMI
                if (!empty($penggunaan_motor) && strpos($nama_item, 'IMI') === false) {
                    $nama_formatted = $nama_item . " " . $penggunaan_motor . " IMI";
                    $update_fields[0] = "namaitem = '$nama_formatted'";
                }

                // NON-ORI needs validation if significant changes
                $update_fields[] = "status_validasi = 'pending_validation'";
            }

            // Update main item
            $update_query = "UPDATE tblitem SET " . implode(', ', $update_fields) . " WHERE noitem = '$kd_item'";

            if (!mysqli_query($koneksi, $update_query)) {
                throw new Exception("Gagal update item: " . mysqli_error($koneksi));
            }

            // Update or insert stock data
            $stokmin = intval($_POST['txtstokmin'] ?? 0);
            $stok_maks = intval($_POST['txtstokmaks'] ?? 0);
            $stok_awal = intval($_POST['txtstokawal'] ?? 0);

            if ($stock_data) {
                // Update existing stock
                $stock_update = "UPDATE tblitem_stok SET
                                stokmin = '$stokmin',
                                stok_maks = '$stok_maks',
                                stok_awal = '$stok_awal'
                                WHERE noitem = '$kd_item' AND kode_cabang = '$kd_cabang'";

                if (!mysqli_query($koneksi, $stock_update)) {
                    throw new Exception("Gagal update stok: " . mysqli_error($koneksi));
                }
            } else {
                // Insert new stock data
                $stock_insert = "INSERT INTO tblitem_stok (noitem, kode_cabang, stokmin, stok_maks, stok_awal)
                                VALUES ('$kd_item', '$kd_cabang', '$stokmin', '$stok_maks', '$stok_awal')";

                if (!mysqli_query($koneksi, $stock_insert)) {
                    throw new Exception("Gagal insert stok: " . mysqli_error($koneksi));
                }
            }

            // Log the edit action (with error handling)
            $log_notes = "Item diedit: $nama_item (Tipe: $tipe_item)";
            $log_query = "INSERT INTO tbitem_validation_log (noitem, action, notes, user_id, created_at)
                         VALUES ('$kd_item', 'edited', '$log_notes', '$id_user', NOW())";
            @mysqli_query($koneksi, $log_query);

            mysqli_commit($koneksi);
            $success_msg = "Item berhasil diupdate!";

            // Refresh data
            $result = mysqli_query($koneksi, $query);
            $item = mysqli_fetch_array($result);
            $stock_result = mysqli_query($koneksi, $stock_query);
            $stock_data = mysqli_fetch_array($stock_result);

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error_msg = $e->getMessage();
        }
    }

    // Get dropdown data (with error handling)
    $jenis_query = @mysqli_query($koneksi, "SELECT * FROM tbljenis ORDER BY namajenis");
    $satuan_query = @mysqli_query($koneksi, "SELECT * FROM tblsatuan ORDER BY satuan");
    $supplier_query = @mysqli_query($koneksi, "SELECT nosupplier, namasupplier FROM tblsupplier ORDER BY namasupplier");
    $rak_query = @mysqli_query($koneksi, "SELECT id, rak_barang FROM tbrakbarang ORDER BY rak_barang");
    $kategori_query = @mysqli_query($koneksi, "SELECT kode, nama_kategori FROM tbkategori_rak ORDER BY nama_kategori");

    // Handle missing tables gracefully
    if (!$jenis_query) $jenis_query = [];
    if (!$satuan_query) $satuan_query = [];
    if (!$supplier_query) $supplier_query = [];
    if (!$rak_query) $rak_query = [];
    if (!$kategori_query) $kategori_query = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Edit Item - <?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/chosen.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

    <style>
        .ori-section { background-color: #e8f5e8; border-left: 4px solid #5cb85c; }
        .nonori-section { background-color: #fff3cd; border-left: 4px solid #f0ad4e; }
        .validation-status {
            position: absolute;
            top: 10px;
            right: 15px;
            z-index: 10;
        }
    </style>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <div class="navbar-header pull-left">
                <a href="#" class="navbar-brand">
                    <small><i class="fa fa-edit"></i> Edit Item</small>
                </a>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="<?php echo $foto_user; ?>" alt="<?php echo $_nama; ?>" />
                            <span class="user-info"><small>Welcome,</small><?php echo $_nama; ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
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
                        <li><a href="barang.php">Master Barang</a></li>
                        <li class="active">Edit Item</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header" style="position: relative;">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-edit"></i>
                                        Edit Item: <?php echo $item['noitem']; ?>
                                    </h4>

                                    <div class="validation-status">
                                        <?php
                                        switch($item['status_validasi']) {
                                            case 'validated':
                                                echo '<span class="label label-success">Validated</span>';
                                                break;
                                            case 'pending_validation':
                                                echo '<span class="label label-warning">Pending Validation</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="label label-danger">Rejected</span>';
                                                break;
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main">
                                        <?php if (isset($success_msg)): ?>
                                            <div class="alert alert-success">
                                                <i class="ace-icon fa fa-check"></i> <?php echo $success_msg; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($error_msg)): ?>
                                            <div class="alert alert-danger">
                                                <i class="ace-icon fa fa-times"></i> <?php echo $error_msg; ?>
                                            </div>
                                        <?php endif; ?>

                                        <form class="form-horizontal" method="post" role="form">
                                            <!-- Klasifikasi ORI/NON-ORI -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label no-padding-right">Klasifikasi Item</label>
                                                <div class="col-sm-9">
                                                    <label class="radio-inline">
                                                        <input type="radio" name="tipe_item" value="ORI" <?php echo ($item['tipe_item'] == 'ORI') ? 'checked' : ''; ?> onchange="toggleItemType()">
                                                        <span class="lbl bigger-120 text-success"> ORI (Genuine Part)</span>
                                                    </label>
                                                    <label class="radio-inline">
                                                        <input type="radio" name="tipe_item" value="NON_ORI" <?php echo ($item['tipe_item'] == 'NON_ORI') ? 'checked' : ''; ?> onchange="toggleItemType()">
                                                        <span class="lbl bigger-120 text-warning"> NON-ORI (Aftermarket/Imitasi)</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="space-4"></div>

                                            <!-- ORI Section -->
                                            <div id="ori-section" class="ori-section" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; <?php echo ($item['tipe_item'] != 'ORI') ? 'display: none;' : ''; ?>">
                                                <h5 class="text-success"><i class="fa fa-certificate"></i> Data ORI (Genuine Part)</h5>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Merek Pabrikan</label>
                                                    <div class="col-sm-6">
                                                        <select name="cbomerek" class="form-control">
                                                            <option value="">- Pilih Merek -</option>
                                                            <option value="Honda" <?php echo ($item['merek'] == 'Honda') ? 'selected' : ''; ?>>Honda</option>
                                                            <option value="Yamaha" <?php echo ($item['merek'] == 'Yamaha') ? 'selected' : ''; ?>>Yamaha</option>
                                                            <option value="Suzuki" <?php echo ($item['merek'] == 'Suzuki') ? 'selected' : ''; ?>>Suzuki</option>
                                                            <option value="Kawasaki" <?php echo ($item['merek'] == 'Kawasaki') ? 'selected' : ''; ?>>Kawasaki</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Kode Part Resmi</label>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="txtkodepart" class="form-control" value="<?php echo $item['kode_part_resmi']; ?>" placeholder="Contoh: 06455-KVB-900" />
                                                        <span class="help-block">Format: Honda (XXXXX-XXX-XXX), Yamaha (XXX-XXXXX-XX)</span>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Nama Part Resmi</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="txtnamaresmi" class="form-control" value="<?php echo $item['nama_part_resmi']; ?>" placeholder="Nama sesuai catalog resmi pabrikan" />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- NON-ORI Section -->
                                            <div id="nonori-section" class="nonori-section" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; <?php echo ($item['tipe_item'] == 'ORI') ? 'display: none;' : ''; ?>">
                                                <h5 class="text-warning"><i class="fa fa-cogs"></i> Data NON-ORI (Aftermarket/Imitasi)</h5>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Penggunaan Motor</label>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="txtpenggunaan" class="form-control" value="<?php echo $item['penggunaan_motor']; ?>" placeholder="Contoh: H. BEAT, VARIO 125, SCOOPY" />
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Merek/Tipe</label>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="txtmerektipe" class="form-control" value="<?php echo $item['merek_tipe']; ?>" placeholder="Contoh: TDR, BRT, NPP" />
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Kategori Rak</label>
                                                    <div class="col-sm-6">
                                                        <select name="cbokategorirak" class="form-control">
                                                            <option value="">- Pilih Kategori -</option>
                                                            <?php if ($kategori_query && mysqli_num_rows($kategori_query) > 0): ?>
                                                                <?php while ($kategori = mysqli_fetch_array($kategori_query)): ?>
                                                                    <option value="<?php echo $kategori['kode']; ?>" <?php echo ($item['kategori_rak'] == $kategori['kode']) ? 'selected' : ''; ?>>
                                                                        <?php echo $kategori['kode']; ?> - <?php echo $kategori['nama_kategori']; ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <option value="KB">KB - Kabel</option>
                                                                <option value="EL">EL - Kelistrikan</option>
                                                                <option value="MS">MS - Mesin</option>
                                                                <option value="RM">RM - Rem</option>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Common Fields -->
                                            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px;">
                                                <h5><i class="fa fa-list"></i> Data Umum Item</h5>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Nama Item</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="txtnama" class="form-control" value="<?php echo $item['namaitem']; ?>" required />
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Jenis</label>
                                                    <div class="col-sm-6">
                                                        <select name="cbojenis" class="form-control" required>
                                                            <option value="">- Pilih Jenis -</option>
                                                            <?php if ($jenis_query && mysqli_num_rows($jenis_query) > 0): ?>
                                                                <?php while ($jenis = mysqli_fetch_array($jenis_query)): ?>
                                                                    <option value="<?php echo $jenis['kodejenis']; ?>" <?php echo ($item['jenis'] == $jenis['kodejenis']) ? 'selected' : ''; ?>>
                                                                        <?php echo $jenis['namajenis']; ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <option value="SP">Spare Part</option>
                                                                <option value="OLI">Oli & Pelumas</option>
                                                                <option value="ACCS">Aksesoris</option>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Satuan</label>
                                                    <div class="col-sm-4">
                                                        <select name="cbosatuan" class="form-control" required>
                                                            <option value="">- Pilih Satuan -</option>
                                                            <?php if ($satuan_query && mysqli_num_rows($satuan_query) > 0): ?>
                                                                <?php while ($satuan = mysqli_fetch_array($satuan_query)): ?>
                                                                    <option value="<?php echo $satuan['kodesatuan']; ?>" <?php echo ($item['satuan'] == $satuan['kodesatuan']) ? 'selected' : ''; ?>>
                                                                        <?php echo $satuan['satuan']; ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <option value="PCS">Pcs</option>
                                                                <option value="SET">Set</option>
                                                                <option value="LITER">Liter</option>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Supplier</label>
                                                    <div class="col-sm-6">
                                                        <select name="cbosupplier" class="form-control">
                                                            <option value="">- Pilih Supplier -</option>
                                                            <?php if ($supplier_query && mysqli_num_rows($supplier_query) > 0): ?>
                                                                <?php while ($supplier = mysqli_fetch_array($supplier_query)): ?>
                                                                    <option value="<?php echo $supplier['nosupplier']; ?>" <?php echo ($item['supplier'] == $supplier['nosupplier']) ? 'selected' : ''; ?>>
                                                                        <?php echo $supplier['namasupplier']; ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <option value="SUP001">Default Supplier</option>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Rak Barang</label>
                                                    <div class="col-sm-6">
                                                        <select name="cborak" class="form-control">
                                                            <option value="">- Pilih Rak -</option>
                                                            <?php if ($rak_query && mysqli_num_rows($rak_query) > 0): ?>
                                                                <?php while ($rak = mysqli_fetch_array($rak_query)): ?>
                                                                    <option value="<?php echo $rak['id']; ?>" <?php echo ($item['rakbarang'] == $rak['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $rak['rak_barang']; ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <option value="1">Rak A-01</option>
                                                                <option value="2">Rak B-01</option>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Harga Beli</label>
                                                    <div class="col-sm-4">
                                                        <input type="number" name="txthargabeli" class="form-control" value="<?php echo $item['hargapokok']; ?>" step="0.01" />
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Harga Jual</label>
                                                    <div class="col-sm-4">
                                                        <input type="number" name="txthargajual" class="form-control" value="<?php echo $item['hargajual']; ?>" step="0.01" />
                                                    </div>
                                                </div>

                                                <!-- Stock Data -->
                                                <h6 class="text-info"><i class="fa fa-cubes"></i> Data Stok</h6>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Stok Minimum</label>
                                                    <div class="col-sm-3">
                                                        <input type="number" name="txtstokmin" class="form-control" value="<?php echo $stock_data['stokmin'] ?? 0; ?>" />
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Stok Maksimum</label>
                                                    <div class="col-sm-3">
                                                        <input type="number" name="txtstokmaks" class="form-control" value="<?php echo $stock_data['stok_maks'] ?? 0; ?>" />
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Stok Awal</label>
                                                    <div class="col-sm-3">
                                                        <input type="number" name="txtstokawal" class="form-control" value="<?php echo $stock_data['stok_awal'] ?? 0; ?>" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="clearfix form-actions">
                                                <div class="col-md-offset-3 col-md-9">
                                                    <button class="btn btn-success btn-sm" type="submit" name="btnsimpan">
                                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                                        Update Item
                                                    </button>

                                                    <a href="barang.php" class="btn btn-grey btn-sm">
                                                        <i class="ace-icon fa fa-undo bigger-110"></i>
                                                        Batal
                                                    </a>

                                                    <?php if ($item['status_validasi'] == 'pending_validation'): ?>
                                                        <a href="barang_validate.php?kd=<?php echo $item['noitem']; ?>" class="btn btn-warning btn-sm">
                                                            <i class="ace-icon fa fa-check-square-o"></i>
                                                            Validasi Item
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
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
    <script src="assets/js/chosen.jquery.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        function toggleItemType() {
            var oriRadio = document.querySelector('input[name="tipe_item"][value="ORI"]');
            var nonOriRadio = document.querySelector('input[name="tipe_item"][value="NON_ORI"]');
            var oriSection = document.getElementById('ori-section');
            var nonOriSection = document.getElementById('nonori-section');

            if (oriRadio.checked) {
                oriSection.style.display = 'block';
                nonOriSection.style.display = 'none';
            } else if (nonOriRadio.checked) {
                oriSection.style.display = 'none';
                nonOriSection.style.display = 'block';
            }
        }

        jQuery(function($) {
            // Initialize chosen selects
            $('.chosen-select').chosen({
                allow_single_deselect: true
            });

            // Form validation
            $('form').on('submit', function(e) {
                var tipeItem = $('input[name="tipe_item"]:checked').val();
                var isValid = true;
                var errorMsg = '';

                if (!tipeItem) {
                    isValid = false;
                    errorMsg += 'Pilih klasifikasi item (ORI/NON-ORI)\n';
                }

                if (tipeItem === 'ORI') {
                    var merek = $('select[name="cbomerek"]').val();
                    var kodePart = $('input[name="txtkodepart"]').val();
                    var namaResmi = $('input[name="txtnamaresmi"]').val();

                    if (!merek) {
                        isValid = false;
                        errorMsg += 'Pilih merek pabrikan untuk item ORI\n';
                    }
                    if (!kodePart) {
                        isValid = false;
                        errorMsg += 'Masukkan kode part resmi\n';
                    }
                    if (!namaResmi) {
                        isValid = false;
                        errorMsg += 'Masukkan nama part resmi\n';
                    }
                } else if (tipeItem === 'NON_ORI') {
                    var penggunaan = $('input[name="txtpenggunaan"]').val();
                    var kategoriRak = $('select[name="cbokategorirak"]').val();

                    if (!penggunaan) {
                        isValid = false;
                        errorMsg += 'Masukkan penggunaan motor untuk item NON-ORI\n';
                    }
                    if (!kategoriRak) {
                        isValid = false;
                        errorMsg += 'Pilih kategori rak untuk item NON-ORI\n';
                    }
                }

                if (!isValid) {
                    alert(errorMsg);
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>

<?php } ?>