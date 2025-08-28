<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user=$_SESSION['_iduser'];	
    $kd_cabang=$_SESSION['_cabang'];		                	
    include "../config/koneksi.php";
    
    // User data
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_user, password, user_akses, foto_user 
                                    FROM tbuser WHERE id='$id_user'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'] ?? '';				        
    $pwd=$tm_cari['password'] ?? '';				        
    $lvl_akses=$tm_cari['user_akses'] ?? '';				                
    $foto_user=$tm_cari['foto_user'] ?? '';				
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang 
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'] ?? '';				        
    $tipe_cabang=$tm_cari['tipe_cabang'] ?? '';	
    // --------------------
    
    $tgl_skr=date('d');	
    $bulan_skr=date('m');
    $thn_skr=date('Y');

    // Initialize search variables
    $search_query = '';
    $where_clause = '';
    $hasil = "Data Servis";
    
    // Handle search
    if(isset($_POST['btncari'])) {
        $search_query = mysqli_real_escape_string($koneksi, $_POST['txtsearch']);
        
        if(!empty($search_query)) {
            $where_clause = "WHERE (s.no_service LIKE '%$search_query%') OR 
                                   (s.no_pelanggan LIKE '%$search_query%') OR 
                                   (s.no_polisi LIKE '%$search_query%') OR 
                                   (p.namapelanggan LIKE '%$search_query%')";
            
            // Count results
            $count_sql = "SELECT COUNT(*) as total 
                         FROM tblservice s
                         LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                         $where_clause";
            $count_result = mysqli_query($koneksi, $count_sql);
            $count_data = mysqli_fetch_assoc($count_result);
            $total_found = $count_data['total'];
            
            $hasil = "Ditemukan $total_found data servis";
        }
    }
    
    // Main query
    $sql_query = "SELECT s.*, p.namapelanggan, v.merek, v.tipe, v.warna
                  FROM tblservice s
                  LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                  LEFT JOIN view_cari_kendaraan v ON s.no_polisi = v.nopolisi
                  $where_clause
                  ORDER BY s.tanggal DESC, s.jam DESC
                  LIMIT 100";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Data Servis Reguler">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css">
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css">
    <link rel="stylesheet" href="assets/css/ace.min.css" id="main-ace-style">
    <link rel="stylesheet" href="assets/css/ace-skins.min.css">
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css">

    <style>
        .service-status {
            font-weight: bold;
        }
        
        .status-draft { color: #f39c12; }
        .status-active { color: #27ae60; }
        .status-completed { color: #3498db; }
        .status-cancelled { color: #e74c3c; }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-buttons {
            white-space: nowrap;
        }
        
        .btn-xs {
            padding: 1px 5px;
            font-size: 10px;
            margin: 1px;
        }
        
        .search-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .highlight-search {
            background-color: #fff3cd;
        }
    </style>
</head>

<body class="no-skin">
    <!-- Navbar -->
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
                    <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profile">
                            <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_servis01.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <!-- Breadcrumbs -->
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                        <li><a href="#">Servis</a></li>
                        <li class="active">Data Servis</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Data Servis
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Manajemen Data Servis Motor
                            </small>
                        </h1>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <div class="row">
                            <div class="col-md-8">
                                <form class="form-horizontal" role="form" action="" method="post">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtsearch">
                                            Cari Data:
                                        </label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="txtsearch" 
                                                       id="txtsearch"
                                                       value="<?php echo htmlspecialchars($search_query); ?>" 
                                                       placeholder="No. Service, No. Polisi, Nama Pelanggan..."
                                                       autocomplete="off">
                                                <div class="input-group-btn">
                                                    <button type="submit" 
                                                            name="btncari" 
                                                            class="btn btn-primary">
                                                        <i class="ace-icon fa fa-search"></i> Cari
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <a href="servis-carinopol.php" class="btn btn-success btn-block">
                                    <i class="fa fa-plus"></i> Tambah Servis Baru
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header">
                                <?php echo $hasil; ?>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr class="info">
                                            <th width="8%">Aksi</th>
                                            <th width="12%">No. Service</th>
                                            <th width="10%">Tanggal</th>
                                            <th width="8%">Jam</th>
                                            <th width="10%">No. Polisi</th>
                                            <th width="20%">Nama Pelanggan</th>
                                            <th width="15%">Kendaraan</th>
                                            <th width="8%">Status</th>
                                            <th width="9%">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $sql = mysqli_query($koneksi, $sql_query);
                                        $no = 1;
                                        
                                        if (mysqli_num_rows($sql) > 0) {
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                // Determine status with proper initialization
                                                $ket_status = '';
                                                $status_class = '';
                                                
                                                // Handle status_jemput
                                                $status_jemput = $tampil['status_jemput'] ?? '0';
                                                $status = $tampil['status'] ?? '1';
                                                
                                                // Determine status description
                                                switch($status) {
                                                    case '0':
                                                        $ket_status = 'Draft';
                                                        $status_class = 'status-draft';
                                                        break;
                                                    case '1':
                                                        if ($status_jemput == '1') {
                                                            $ket_status = 'Dijemput';
                                                            $status_class = 'status-active';
                                                        } else {
                                                            $ket_status = 'Aktif';
                                                            $status_class = 'status-active';
                                                        }
                                                        break;
                                                    case '2':
                                                        $ket_status = 'Selesai';
                                                        $status_class = 'status-completed';
                                                        break;
                                                    case '3':
                                                        $ket_status = 'Batal';
                                                        $status_class = 'status-cancelled';
                                                        break;
                                                    default:
                                                        $ket_status = 'Unknown';
                                                        $status_class = '';
                                                        break;
                                                }
                                                
                                                // Highlight search results
                                                $row_class = '';
                                                if (!empty($search_query)) {
                                                    if (stripos($tampil['no_service'], $search_query) !== false || 
                                                        stripos($tampil['no_polisi'], $search_query) !== false || 
                                                        stripos($tampil['namapelanggan'], $search_query) !== false) {
                                                        $row_class = 'highlight-search';
                                                    }
                                                }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td class="action-buttons">
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown" class="btn dropdown-toggle btn-minier btn-primary">
                                                        Aksi
                                                        <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-default">
                                                        <li>
                                                            <a href="servis-input-reguler.php?snoserv=<?php echo urlencode($tampil['no_service']); ?>">
                                                                <i class="ace-icon fa fa-edit"></i> Edit Servis
                                                            </a>
                                                        </li>
                                                        <?php if ($status_jemput == '1'): ?>
                                                        <li>
                                                            <a href="sp-ambil-motor.php?snosrv=<?php echo urlencode($tampil['no_service']); ?>" target="_blank">
                                                                <i class="ace-icon fa fa-file-pdf-o"></i> Cetak Surat Jemput
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        <li>
                                                            <a href="servis-print.php?snoserv=<?php echo urlencode($tampil['no_service']); ?>" target="_blank">
                                                                <i class="ace-icon fa fa-print"></i> Print Invoice
                                                            </a>
                                                        </li>
                                                        <li class="divider"></li>
                                                        <li>
                                                            <a href="#" onclick="confirmDelete('<?php echo $tampil['no_service']; ?>')" class="text-danger">
                                                                <i class="ace-icon fa fa-trash"></i> Hapus
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($tampil['no_service']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($tampil['tanggal'])); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($tampil['jam']); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($tampil['no_polisi']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($tampil['namapelanggan']); ?>
                                                <?php if ($status_jemput == '1'): ?>
                                                    <br><small class="text-info"><i class="fa fa-truck"></i> Jemput Antar</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($tampil['merek'] . ' ' . $tampil['tipe']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($tampil['warna']); ?></small>
                                            </td>
                                            <td>
                                                <span class="service-status <?php echo $status_class; ?>">
                                                    <?php echo $ket_status; ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <?php 
                                                $total = $tampil['total_grand'] ?? 0;
                                                echo 'Rp ' . number_format($total, 0, ',', '.');
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                            $no++;
                                            }
                                        } else {
                                            echo '<tr><td colspan="9" class="text-center text-muted">
                                                    <h4><i class="ace-icon fa fa-search"></i> Tidak ada data ditemukan</h4>';
                                            if (!empty($search_query)) {
                                                echo '<p>Tidak ada hasil untuk pencarian: <strong>' . htmlspecialchars($search_query) . '</strong></p>';
                                            } else {
                                                echo '<p>Belum ada data servis yang tersedia.</p>';
                                            }
                                            echo '</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($search_query) && mysqli_num_rows($sql) > 0): ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert alert-info">
                                <i class="ace-icon fa fa-info-circle"></i>
                                <strong>Tips:</strong> Gunakan menu aksi untuk mengelola data servis. Klik "Edit Servis" untuk mengubah data atau "Print Invoice" untuk mencetak.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <?php include "../lib/footer.php"; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        $(document).ready(function() {
            // Auto-focus on search field
            $('#txtsearch').focus();
            
            // Search on Enter key
            $('#txtsearch').on('keypress', function(e) {
                if (e.which === 13) {
                    $('button[name="btncari"]').click();
                }
            });
            
            // Enhanced table interactions
            $('.table tbody tr').hover(
                function() {
                    $(this).addClass('active');
                },
                function() {
                    $(this).removeClass('active');
                }
            );
            
            // Auto refresh every 5 minutes if no search is active
            <?php if (empty($search_query)): ?>
            setInterval(function() {
                if (!$('#txtsearch').val()) {
                    location.reload();
                }
            }, 300000); // 5 minutes
            <?php endif; ?>
        });
        
        function confirmDelete(noService) {
            if (confirm('Yakin ingin menghapus data servis ' + noService + '?\n\nPerhatian: Data yang dihapus tidak dapat dikembalikan!')) {
                // Redirect to delete handler
                window.location.href = 'servis-delete.php?snoserv=' + encodeURIComponent(noService);
            }
        }
        
        // Export functionality
        function exportToExcel() {
            var table = document.querySelector('.table');
            var workbook = XLSX.utils.table_to_book(table);
            XLSX.writeFile(workbook, 'data-servis-' + new Date().toISOString().slice(0,10) + '.xlsx');
        }
        
        // Print functionality
        function printTable() {
            var printWindow = window.open('', '', 'height=600,width=800');
            var tableHtml = document.querySelector('.table-responsive').innerHTML;
            
            printWindow.document.write('<html><head><title>Data Servis</title>');
            printWindow.document.write('<style>table{border-collapse:collapse;width:100%;}th,td{border:1px solid #000;padding:5px;font-size:12px;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h2>Data Servis - ' + new Date().toLocaleDateString('id-ID') + '</h2>');
            printWindow.document.write(tableHtml);
            printWindow.document.write('</body></html>');
            
            printWindow.document.close();
            printWindow.print();
        }
        
        // Status filter functionality
        function filterByStatus(status) {
            var url = new URL(window.location.href);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        }
        
        // Live search (optional - uncomment if needed)
        /*
        var searchTimeout;
        $('#txtsearch').on('input', function() {
            clearTimeout(searchTimeout);
            var searchTerm = $(this).val();
            
            if (searchTerm.length >= 3) {
                searchTimeout = setTimeout(function() {
                    // Perform AJAX search here if needed
                    console.log('Searching for: ' + searchTerm);
                }, 500);
            }
        });
        */
    </script>
</body>
</html>

<?php 
}
?>