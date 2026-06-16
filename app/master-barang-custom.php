<?php
session_start();
require_once('../config/koneksi.php');
require_once('_handler_barang_custom.php');

// Check login
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];	
    $kd_cabang=$_SESSION['_cabang'];
    
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

    // Data Cabang
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang 
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];				        
    $tipe_cabang=$tm_cari['tipe_cabang'];

$page_title = "Master Barang Custom";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php echo $page_title; ?> - Web Bengkel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- text fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- ace styles -->
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap.min.css" />
    
    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>
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
                                <?php include "../lib/logo.php"; ?>
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
        </div><!-- /.navbar-container -->
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
                <div class="page-content">
                    
                    <!-- Breadcrumbs -->
                    <div class="page-header">
                        <h1>
                            <i class="ace-icon fa fa-cube"></i>
                            <?php echo $page_title; ?>
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Manage barang yang tidak ada di master standar
                            </small>
                        </h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            
                            <!-- Main Widget -->
                            <div class="widget-box">
                                <div class="widget-header widget-header-blue widget-header-flat">
                                    <h4 class="widget-title lighter">
                                        <i class="ace-icon fa fa-list"></i>
                                        Daftar Barang Custom
                                    </h4>
                                    <div class="widget-toolbar">
                                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAddCustom">
                                            <i class="ace-icon fa fa-plus"></i>
                                            Tambah Barang Custom
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="widget-body">
                                    <div class="widget-main">
                                        
                                        <table id="tableCustomItems" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="30">No</th>
                                                    <th width="120">Kode</th>
                                                    <th>Nama Barang</th>
                                                    <th width="120">Harga</th>
                                                    <th width="80">Satuan</th>
                                                    <th width="100">Kategori</th>
                                                    <th width="100">Status</th>
                                                    <th width="80">Dibuat</th>
                                                    <th width="150">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $no = 1;
                                                $query = mysqli_query($koneksi, "
                                                    SELECT * FROM tbmaster_barang_custom 
                                                    ORDER BY id DESC
                                                ");
                                                
                                                while($row = mysqli_fetch_assoc($query)) {
                                                    $status_badge = ($row['status_aktif'] == '1') ? 
                                                        '<span class="label label-success">Aktif</span>' : 
                                                        '<span class="label label-default">Nonaktif</span>';
                                                    
                                                    $status_icon = ($row['status_aktif'] == '1') ? 
                                                        '<i class="ace-icon fa fa-eye-slash"></i> Nonaktifkan' : 
                                                        '<i class="ace-icon fa fa-eye"></i> Aktifkan';
                                                    
                                                    echo "<tr>";
                                                    echo "<td>{$no}</td>";
                                                    echo "<td><strong class='blue'>{$row['kode_barang']}</strong></td>";
                                                    echo "<td>{$row['nama_barang']}";
                                                    if(!empty($row['deskripsi'])) {
                                                        echo "<br><small class='text-muted'>{$row['deskripsi']}</small>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td class='text-right'><strong>Rp " . number_format($row['harga_jual'], 0, ',', '.') . "</strong></td>";
                                                    echo "<td class='center'>{$row['satuan']}</td>";
                                                    echo "<td><span class='label label-info'>{$row['kategori']}</span></td>";
                                                    echo "<td class='center'>{$status_badge}</td>";
                                                    echo "<td class='center'><small>" . date('d/m/Y', strtotime($row['created_at'])) . "</small></td>";
                                                    echo "<td class='center'>";
                                                    
                                                    // Edit button
                                                    echo "<button class='btn btn-xs btn-warning btn-edit-custom' 
                                                          data-id='{$row['id']}'
                                                          data-kode='{$row['kode_barang']}'
                                                          data-nama='{$row['nama_barang']}'
                                                          data-harga='{$row['harga_jual']}'
                                                          data-satuan='{$row['satuan']}'
                                                          data-kategori='{$row['kategori']}'
                                                          data-deskripsi='{$row['deskripsi']}'
                                                          title='Edit'><i class='ace-icon fa fa-pencil'></i></button> ";
                                                    
                                                    // Toggle status
                                                    echo "<a href='?action=toggle&id={$row['id']}' class='btn btn-xs btn-info' 
                                                          onclick=\"return confirm('Yakin ingin mengubah status?')\" 
                                                          title='Toggle Status'><i class='ace-icon fa fa-refresh'></i></a> ";
                                                    
                                                    // Delete button
                                                    echo "<a href='?action=delete&id={$row['id']}' class='btn btn-xs btn-danger' 
                                                          onclick=\"return confirm('Yakin ingin menghapus barang ini?')\" 
                                                          title='Hapus'><i class='ace-icon fa fa-trash-o'></i></a>";
                                                    
                                                    echo "</td>";
                                                    echo "</tr>";
                                                    $no++;
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                        
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                </div>
            </div>
        </div><!-- /.main-content -->
        
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
    </div><!-- /.main-container -->
    
    <!-- Modal Add -->
    <div class="modal fade" id="modalAddCustom" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-plus blue"></i>
                        Tambah Barang Custom
                    </h4>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="ace-icon fa fa-info-circle"></i>
                            Kode barang akan di-generate otomatis (Format: CUSTOM-XXXXX)
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_barang" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Harga Jual <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="harga_jual" min="0" step="1000" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Satuan <span class="text-danger">*</span></label>
                                    <select class="form-control" name="satuan" required>
                                        <option value="PCS">PCS</option>
                                        <option value="UNIT">UNIT</option>
                                        <option value="SET">SET</option>
                                        <option value="PAKET">PAKET</option>
                                        <option value="BTL">BTL (Botol)</option>
                                        <option value="LITER">LITER</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Kategori</label>
                            <select class="form-control" name="kategori">
                                <option value="LAINNYA">LAINNYA</option>
                                <option value="JASA">JASA</option>
                                <option value="IMPORT">IMPORT</option>
                                <option value="MODIFIKASI">MODIFIKASI</option>
                                <option value="AKSESORIS">AKSESORIS</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="2" placeholder="Keterangan tambahan (optional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btnaddcustom" class="btn btn-primary">
                            <i class="ace-icon fa fa-check"></i>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div class="modal fade" id="modalEditCustom" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-pencil orange"></i>
                        Edit Barang Custom
                    </h4>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kode Barang</label>
                            <input type="text" class="form-control" id="edit_kode" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_barang" id="edit_nama" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Harga Jual <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="harga_jual" id="edit_harga" min="0" step="1000" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Satuan <span class="text-danger">*</span></label>
                                    <select class="form-control" name="satuan" id="edit_satuan" required>
                                        <option value="PCS">PCS</option>
                                        <option value="UNIT">UNIT</option>
                                        <option value="SET">SET</option>
                                        <option value="PAKET">PAKET</option>
                                        <option value="BTL">BTL (Botol)</option>
                                        <option value="LITER">LITER</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Kategori</label>
                            <select class="form-control" name="kategori" id="edit_kategori">
                                <option value="LAINNYA">LAINNYA</option>
                                <option value="JASA">JASA</option>
                                <option value="IMPORT">IMPORT</option>
                                <option value="MODIFIKASI">MODIFIKASI</option>
                                <option value="AKSESORIS">AKSESORIS</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btneditcustom" class="btn btn-warning">
                            <i class="ace-icon fa fa-check"></i>
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">
        if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+'<'+"/script>");
    </script>
    <script src="assets/js/bootstrap.min.js"></script>
    
    <!-- ace scripts -->
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <!-- DataTables -->
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
    
    <script>
    jQuery(function($) {
        // DataTables
        $('#tableCustomItems').DataTable({
            "pageLength": 25,
            "order": [[0, "desc"]],
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Tidak ada data",
                "infoFiltered": "(filter dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
        
        // Edit button click
        $('.btn-edit-custom').on('click', function() {
            $('#edit_id').val($(this).data('id'));
            $('#edit_kode').val($(this).data('kode'));
            $('#edit_nama').val($(this).data('nama'));
            $('#edit_harga').val($(this).data('harga'));
            $('#edit_satuan').val($(this).data('satuan'));
            $('#edit_kategori').val($(this).data('kategori'));
            $('#edit_deskripsi').val($(this).data('deskripsi'));
            $('#modalEditCustom').modal('show');
        });
    });
    </script>
    
</body>
</html>
<?php } ?>
