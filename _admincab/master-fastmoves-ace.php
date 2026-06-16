<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
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

    // ============================================
    // HANDLERS
    // ============================================
    
    // Tambah Kategori
    if(isset($_POST['btnaddkategori'])) {
        $kode = mysqli_real_escape_string($koneksi, $_POST['kode_kategori']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
        $icon = mysqli_real_escape_string($koneksi, $_POST['icon']);
        $urutan = mysqli_real_escape_string($koneksi, $_POST['urutan']);
        
        $query = "INSERT INTO tbmaster_kategori_fastmoves (kode_kategori, nama_kategori, icon, urutan) 
                  VALUES ('$kode', '$nama', '$icon', '$urutan')";
        
        if(mysqli_query($koneksi, $query)) {
            echo "<script>alert('Kategori berhasil ditambahkan!'); window.location.href='master-fastmoves.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan kategori: " . mysqli_error($koneksi) . "');</script>";
        }
    }
    
    // Update Kategori
    if(isset($_POST['btnupdatekategori'])) {
        $id = mysqli_real_escape_string($koneksi, $_POST['kategori_id']);
        $kode = mysqli_real_escape_string($koneksi, $_POST['kode_kategori']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
        $icon = mysqli_real_escape_string($koneksi, $_POST['icon']);
        $urutan = mysqli_real_escape_string($koneksi, $_POST['urutan']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $query = "UPDATE tbmaster_kategori_fastmoves 
                  SET kode_kategori='$kode', nama_kategori='$nama', icon='$icon', urutan='$urutan', is_active='$is_active' 
                  WHERE id='$id'";
        
        if(mysqli_query($koneksi, $query)) {
            echo "<script>alert('Kategori berhasil diupdate!'); window.location.href='master-fastmoves.php';</script>";
        } else {
            echo "<script>alert('Gagal update kategori!');</script>";
        }
    }
    
    // Delete Kategori
    if(isset($_POST['btndeletekategori'])) {
        $id = mysqli_real_escape_string($koneksi, $_POST['kategori_id']);
        
        $query = "DELETE FROM tbmaster_kategori_fastmoves WHERE id='$id'";
        
        if(mysqli_query($koneksi, $query)) {
            echo "<script>alert('Kategori berhasil dihapus!'); window.location.href='master-fastmoves.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus kategori!');</script>";
        }
    }
    
    // Tambah Mapping Barang
    if(isset($_POST['btnaddmapping'])) {
        $kode_kategori = mysqli_real_escape_string($koneksi, $_POST['kode_kategori']);
        $kode_barang = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $urutan = mysqli_real_escape_string($koneksi, $_POST['urutan']);
        
        // Check duplicate
        $check = mysqli_query($koneksi, "SELECT id FROM tbmaster_barang_fastmoves 
                                        WHERE kode_kategori='$kode_kategori' AND kode_barang='$kode_barang'");
        if(mysqli_num_rows($check) > 0) {
            echo "<script>alert('Barang sudah ada di kategori ini!');</script>";
        } else {
            $query = "INSERT INTO tbmaster_barang_fastmoves (kode_kategori, kode_barang, is_featured, urutan) 
                      VALUES ('$kode_kategori', '$kode_barang', '$is_featured', '$urutan')";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Barang berhasil ditambahkan!'); window.location.href='master-fastmoves.php';</script>";
            } else {
                echo "<script>alert('Gagal menambahkan barang!');</script>";
            }
        }
    }
    
    // Delete Mapping
    if(isset($_POST['btndeletemapping'])) {
        $id = mysqli_real_escape_string($koneksi, $_POST['mapping_id']);
        
        $query = "DELETE FROM tbmaster_barang_fastmoves WHERE id='$id'";
        
        if(mysqli_query($koneksi, $query)) {
            echo "<script>alert('Barang berhasil dihapus dari kategori!'); window.location.href='master-fastmoves.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus barang!');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Master Fast Moves - <?php include "../lib/subtitel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    
    <style>
        .kategori-card {
            border-left: 4px solid #428bca;
            margin-bottom: 15px;
        }
        .kategori-card:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
    </style>
</head>

<body class="no-skin">
    <!-- Navbar -->
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small><?php include "../lib/logo.php"; ?> <?php include "../lib/subtitel.php"; ?></small>							
                </a>								
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="<?php echo $_nama; ?>" />
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
    
    <!-- Main Container -->
    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <!-- Sidebar -->
        <?php include "menu_dashboard.php"; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="main-content-inner">
                <!-- Breadcrumbs -->
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li><a href="#">Data Master</a></li>
                        <li class="active">Fast Moves Mapping</li>
                    </ul>
                </div>

                <!-- Page Content -->
                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <!-- Page Header -->
                            <div class="page-header">
                                <h1>
                                    <i class="ace-icon fa fa-bolt"></i> Master Fast Moves
                                    <small class="pull-right">
                                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalAddKategori">
                                            <i class="fa fa-plus"></i> Tambah Kategori
                                        </button>
                                    </small>
                                </h1>
                            </div>

                            <!-- Tabs -->
                            <div class="tabbable">
                                <ul class="nav nav-tabs" id="myTab">
                                    <li class="active">
                                        <a data-toggle="tab" href="#tabKategori">
                                            <i class="blue ace-icon fa fa-list bigger-120"></i>
                                            Kategori Fast Moves
                                        </a>
                                    </li>
                                    <li>
                                        <a data-toggle="tab" href="#tabMapping">
                                            <i class="orange ace-icon fa fa-link bigger-120"></i>
                                            Mapping Barang
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Tab Kategori -->
                                    <div id="tabKategori" class="tab-pane fade in active">
                                        <div class="row" style="margin-top: 15px;">
                                            <?php
                                            $query_kategori = mysqli_query($koneksi, "SELECT * FROM tbmaster_kategori_fastmoves ORDER BY urutan");
                                            while($kat = mysqli_fetch_array($query_kategori)) {
                                            ?>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="widget-box kategori-card">
                                                    <div class="widget-header widget-header-flat">
                                                        <h5 class="widget-title">
                                                            <i class="ace-icon fa <?php echo $kat['icon']; ?>"></i>
                                                            <?php echo $kat['nama_kategori']; ?>
                                                        </h5>
                                                        <div class="widget-toolbar">
                                                            <?php if($kat['is_active']) { ?>
                                                            <span class="label label-success">Aktif</span>
                                                            <?php } else { ?>
                                                            <span class="label label-default">Nonaktif</span>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div class="widget-body">
                                                        <div class="widget-main">
                                                            <p class="text-muted">
                                                                <strong>Kode:</strong> <?php echo $kat['kode_kategori']; ?><br>
                                                                <strong>Urutan:</strong> <?php echo $kat['urutan']; ?>
                                                            </p>
                                                            <div class="action-buttons">
                                                                <button class="btn btn-xs btn-warning btn-edit-kategori" 
                                                                        data-id="<?php echo $kat['id']; ?>"
                                                                        data-kode="<?php echo $kat['kode_kategori']; ?>"
                                                                        data-nama="<?php echo $kat['nama_kategori']; ?>"
                                                                        data-icon="<?php echo $kat['icon']; ?>"
                                                                        data-urutan="<?php echo $kat['urutan']; ?>"
                                                                        data-active="<?php echo $kat['is_active']; ?>">
                                                                    <i class="ace-icon fa fa-pencil bigger-120"></i> Edit
                                                                </button>
                                                                <button class="btn btn-xs btn-danger btn-delete-kategori" 
                                                                        data-id="<?php echo $kat['id']; ?>">
                                                                    <i class="ace-icon fa fa-trash-o bigger-120"></i> Hapus
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <!-- Tab Mapping -->
                                    <div id="tabMapping" class="tab-pane fade">
                                        <div style="margin-top: 15px;">
                                            <div class="widget-box">
                                                <div class="widget-header">
                                                    <h4 class="widget-title">
                                                        <i class="ace-icon fa fa-link"></i> Mapping Barang ke Kategori
                                                    </h4>
                                                    <div class="widget-toolbar">
                                                        <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#modalAddMapping">
                                                            <i class="fa fa-plus"></i> Tambah Barang
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="widget-body">
                                                    <div class="widget-main no-padding">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered table-hover">
                                                                <thead>
                                                                    <tr>
                                                                        <th width="5%">No</th>
                                                                        <th width="15%">Kategori</th>
                                                                        <th width="12%">Kode Barang</th>
                                                                        <th width="30%">Nama Barang</th>
                                                                        <th width="12%">Harga</th>
                                                                        <th width="8%">Featured</th>
                                                                        <th width="8%">Urutan</th>
                                                                        <th width="10%">Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $no = 1;
                                                                    $query_mapping = mysqli_query($koneksi, "
                                                                        SELECT 
                                                                            mbf.id,
                                                                            mbf.kode_kategori,
                                                                            kfm.nama_kategori,
                                                                            mbf.kode_barang,
                                                                            item.namaitem as nama_barang,
                                                                            item.hargajual as harga_jual,
                                                                            mbf.is_featured,
                                                                            mbf.urutan
                                                                        FROM tbmaster_barang_fastmoves mbf
                                                                        INNER JOIN tbmaster_kategori_fastmoves kfm ON mbf.kode_kategori = kfm.kode_kategori
                                                                        INNER JOIN tblitem item ON mbf.kode_barang = item.noitem
                                                                        ORDER BY kfm.urutan, mbf.urutan
                                                                    ");
                                                                    while($map = mysqli_fetch_array($query_mapping)) {
                                                                    ?>
                                                                    <tr>
                                                                        <td class="center"><?php echo $no++; ?></td>
                                                                        <td><?php echo $map['nama_kategori']; ?></td>
                                                                        <td><strong><?php echo $map['kode_barang']; ?></strong></td>
                                                                        <td><?php echo $map['nama_barang']; ?></td>
                                                                        <td class="text-right">Rp <?php echo number_format($map['harga_jual'], 0, ',', '.'); ?></td>
                                                                        <td class="center">
                                                                            <?php if($map['is_featured']) { ?>
                                                                            <span class="label label-warning"><i class="fa fa-star"></i> Featured</span>
                                                                            <?php } else { ?>
                                                                            <span class="text-muted">-</span>
                                                                            <?php } ?>
                                                                        </td>
                                                                        <td class="center"><?php echo $map['urutan']; ?></td>
                                                                        <td class="center">
                                                                            <button class="btn btn-xs btn-danger btn-delete-mapping" 
                                                                                    data-id="<?php echo $map['id']; ?>">
                                                                                <i class="ace-icon fa fa-trash-o bigger-120"></i>
                                                                            </button>
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
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <span class="bigger-120">
                        <span class="blue bolder"><?php include "../lib/logo.php"; ?></span>
                        &copy; <?php echo date('Y'); ?>
                    </span>
                </div>
            </div>
        </div>

        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
            <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
        </a>
    </div>

    <!-- Modal Add Kategori -->
    <div class="modal fade" id="modalAddKategori" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-plus blue"></i> Tambah Kategori Fast Moves
                    </h4>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kode Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_kategori" required maxlength="10" placeholder="Contoh: FLT, OLI, BAN">
                        </div>
                        <div class="form-group">
                            <label>Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_kategori" required placeholder="Contoh: Filter Udara">
                        </div>
                        <div class="form-group">
                            <label>Icon (FontAwesome) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="icon" required placeholder="fa-filter">
                            <small class="text-muted">Lihat: <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">FontAwesome 4.7 Icons</a></small>
                        </div>
                        <div class="form-group">
                            <label>Urutan</label>
                            <input type="number" class="form-control" name="urutan" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" name="btnaddkategori" class="btn btn-sm btn-primary">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Kategori -->
    <div class="modal fade" id="modalEditKategori" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-pencil orange"></i> Edit Kategori Fast Moves
                    </h4>
                </div>
                <form method="POST">
                    <input type="hidden" name="kategori_id" id="edit_kategori_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kode Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_kategori" id="edit_kode_kategori" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_kategori" id="edit_nama_kategori" required>
                        </div>
                        <div class="form-group">
                            <label>Icon (FontAwesome) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="icon" id="edit_icon" required>
                        </div>
                        <div class="form-group">
                            <label>Urutan</label>
                            <input type="number" class="form-control" name="urutan" id="edit_urutan">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" id="edit_is_active" class="ace">
                                <span class="lbl"> Aktif</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" name="btnupdatekategori" class="btn btn-sm btn-warning">
                            <i class="fa fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add Mapping -->
    <div class="modal fade" id="modalAddMapping" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-plus blue"></i> Tambah Barang ke Kategori
                    </h4>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kategori <span class="text-danger">*</span></label>
                            <select class="form-control" name="kode_kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php
                                $query_kat = mysqli_query($koneksi, "SELECT * FROM tbmaster_kategori_fastmoves WHERE is_active=1 ORDER BY urutan");
                                while($k = mysqli_fetch_array($query_kat)) {
                                ?>
                                <option value="<?php echo $k['kode_kategori']; ?>"><?php echo $k['nama_kategori']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kode Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_barang" required placeholder="Masukkan kode barang">
                            <small class="text-muted">Pastikan kode barang sudah ada di master barang</small>
                        </div>
                        <div class="form-group">
                            <label>Urutan</label>
                            <input type="number" class="form-control" name="urutan" value="0">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_featured" class="ace">
                                <span class="lbl"> Featured Item</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" name="btnaddmapping" class="btn btn-sm btn-primary">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
    $(document).ready(function() {
        // Edit Kategori
        $('.btn-edit-kategori').click(function() {
            $('#edit_kategori_id').val($(this).data('id'));
            $('#edit_kode_kategori').val($(this).data('kode'));
            $('#edit_nama_kategori').val($(this).data('nama'));
            $('#edit_icon').val($(this).data('icon'));
            $('#edit_urutan').val($(this).data('urutan'));
            $('#edit_is_active').prop('checked', $(this).data('active') == 1);
            $('#modalEditKategori').modal('show');
        });
        
        // Delete Kategori
        $('.btn-delete-kategori').click(function() {
            if(confirm('Yakin ingin menghapus kategori ini?\n\nPeringatan: Semua mapping barang di kategori ini juga akan terhapus!')) {
                var id = $(this).data('id');
                $('<form method="POST"><input type="hidden" name="kategori_id" value="' + id + '"><input type="hidden" name="btndeletekategori" value="1"></form>').appendTo('body').submit();
            }
        });
        
        // Delete Mapping
        $('.btn-delete-mapping').click(function() {
            if(confirm('Yakin ingin menghapus barang dari kategori ini?')) {
                var id = $(this).data('id');
                $('<form method="POST"><input type="hidden" name="mapping_id" value="' + id + '"><input type="hidden" name="btndeletemapping" value="1"></form>').appendTo('body').submit();
            }
        });
    });
    </script>
</body>
</html>

<?php } ?>
