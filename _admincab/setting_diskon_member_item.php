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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Setting Diskon Member - <?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Setting Diskon Member" />
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
    
    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>
    
    <style>
        /* Desain Baru untuk Dashboard Setting Diskon */
        .info-box {
            background-color: #d9edf7;
            border-left: 5px solid #31708f;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        /* Switch Toggle CSS */
        .switch {
          position: relative;
          display: inline-block;
          width: 50px;
          height: 24px;
          margin-top: 5px;
        }
        
        .switch input { 
          opacity: 0;
          width: 0;
          height: 0;
        }
        
        .slider {
          position: absolute;
          cursor: pointer;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: #ccc;
          -webkit-transition: .4s;
          transition: .4s;
        }
        
        .slider:before {
          position: absolute;
          content: "";
          height: 16px;
          width: 16px;
          left: 4px;
          bottom: 4px;
          background-color: white;
          -webkit-transition: .4s;
          transition: .4s;
        }
        
        input:checked + .slider {
          background-color: #5cb85c; /* Success Green */
        }
        
        input:focus + .slider {
          box-shadow: 0 0 1px #5cb85c;
        }
        
        input:checked + .slider:before {
          -webkit-transform: translateX(26px);
          -ms-transform: translateX(26px);
          transform: translateX(26px);
        }
        
        /* Rounded sliders */
        .slider.round {
          border-radius: 34px;
        }
        
        .slider.round:before {
          border-radius: 50%;
        }
        
        .list-group-item {
            transition: all 0.3s;
        }
        .list-group-item-success {
            background-color: #dff0d8;
        }
        .search-area {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #e3e3e3;
            margin-bottom: 15px;
        }
    </style>
</head>

<body class="no-skin">
    <?php include "lib/navbar.php"; ?>

    <div class="main-container" id="main-container">
        <script type="text/javascript">
            try{ace.settings.check('main-container' , 'fixed')}catch(e){}
        </script>

        <?php include "lib/sidebar.php"; ?>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li>
                            <a href="#">Data Master</a>
                        </li>
                        <li class="active">Setting Diskon Member</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            
                            <h3 class="header smaller lighter blue">
                                <i class="fa fa-percent"></i> Pengaturan Diskon Member
                            </h3>

                            <div class="info-box">
                                <h4><i class="fa fa-question-circle"></i> Panduan Cara Pakai</h4>
                                <p>Gunakan halaman ini untuk mengatur barang mana yang boleh didiskon untuk member.</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Langkah 1: Atur per Kategori (Panel Kiri)</strong><br>
                                        Tentukan secara umum, apakah kategori tersebut dapat diskon atau tidak.<br>
                                        <span class="label label-success"><i class="fa fa-check"></i> Hijau</span> = Semua barang di kategori ini DAPAT diskon.<br>
                                        <span class="label label-default"><i class="fa fa-times"></i> Abu-abu</span> = TIDAK dapat diskon.
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Langkah 2: Pengecualian Barang (Panel Kanan)</strong><br>
                                        Cari barang spesifik di sini JIKA Anda ingin mengubah aturannya khusus untuk barang itu saja.<br>
                                        <em>Contoh: Kategori OLI diset "Dapat Diskon", tapi Anda ingin "Oli Mahal X" TIDAK dapat diskon -> Cari Olinya, lalu klik tombol <span class="label label-warning">N</span>.</em>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- KOLOM KIRI: ATURAN KATEGORI -->
                                <div class="col-md-4">
                                    <div class="widget-box">
                                        <div class="widget-header">
                                            <h4 class="widget-title">Aturan Per Kategori</h4>
                                            <div class="widget-toolbar">
                                                <a href="#" onclick="loadKategori(); return false;"><i class="fa fa-refresh"></i></a>
                                            </div>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main padding-8">
                                                <div id="loading-kategori" class="text-center" style="display:none;">
                                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                </div>
                                                <div id="list-kategori" style="max-height: 600px; overflow-y: auto;">
                                                    <!-- Loaded via AJAX -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KOLOM KANAN: PENCARIAN & OVERRIDE -->
                                <div class="col-md-8">
                                    <div class="widget-box">
                                        <div class="widget-header">
                                            <h4 class="widget-title">Pengecualian Khusus (Cari Item)</h4>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <div class="search-area">
                                                    <div class="row">
                                                        <div class="col-sm-5">
                                                            <div class="input-group">
                                                                <input type="text" id="searchItem" class="form-control" placeholder="Cari Kode / Nama Item...">
                                                                <span class="input-group-btn">
                                                                    <button class="btn btn-sm btn-primary" type="button" onclick="searchItems()">
                                                                        <i class="ace-icon fa fa-search"></i> Cari
                                                                    </button>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <select id="filterTipe" class="form-control" onchange="searchItems()">
                                                                <option value="semua">Semua Tipe</option>
                                                                <option value="barang">Barang</option>
                                                                <option value="jasa">Jasa</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-4 text-right">
                                                             <button class="btn btn-sm btn-warning" onclick="testConnection()">
                                                                <i class="fa fa-bolt"></i> Test Tombol
                                                             </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="searchResults">
                                                    <div class="alert alert-info text-center">
                                                        <i class="fa fa-search fa-3x text-muted"></i><br><br>
                                                        Silakan cari item untuk mengatur pengecualian khusus.<br>
                                                        Gunakan fitur ini JIKA ingin item tertentu memiliki aturan berbeda dari kategorinya.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Back Button -->
                                    <div style="margin-top: 20px;">
                                        <a href="pelanggan_kategori.php" class="btn btn-default btn-sm">
                                            <i class="fa fa-arrow-left"></i> Kembali ke Kategori Member
                                        </a>
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

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <script type="text/javascript">
        console.log("Setting Diskon Member Loaded");
        
        $(document).ready(function() {
            // Load kategori on startup
            loadKategori();
            
            // Enter key trigger search
            $('#searchItem').keypress(function(e) {
                if(e.which == 13) {
                    searchItems();
                    return false;
                }
            });
            
            // Event delegation for Kategori Toggle
            $(document).on('change', '.toggle-kategori', function() {
                var jenis = $(this).data('jenis');
                var isChecked = $(this).is(':checked');
                var val = isChecked ? 1 : 0; // 1 = Dapat Diskon
                
                updateKategori(jenis, val, $(this));
            });
        });

        // ------------------ KATEGORI FUNCTIONS ------------------
        
        function loadKategori() {
            $('#loading-kategori').show();
            $('#list-kategori').hide();
            
            $.ajax({
                url: 'ajax_list_kategori.php',
                type: 'GET',
                success: function(response) {
                    $('#loading-kategori').hide();
                    $('#list-kategori').html(response).fadeIn();
                },
                error: function() {
                    $('#loading-kategori').hide();
                    $('#list-kategori').html('<div class="alert alert-danger">Gagal memuat kategori</div>').show();
                }
            });
        }
        
        function updateKategori(jenis, val, toggleEl) {
            // Visual feedback on the row
            var $row = toggleEl.closest('.list-group-item');
            var $statusText = $row.find('.status-text');
            var originalBg = $row.css('background-color');
            
            // Show processing state
            $row.addClass('disabled').css('opacity', '0.6');
            $('body').css('cursor', 'wait');
            
            $.ajax({
                url: 'ajax_list_kategori.php',
                type: 'POST',
                data: {
                    update_kategori: 1,
                    jenis: jenis,
                    val: val
                },
                dataType: 'json',
                success: function(response) {
                    $('body').css('cursor', 'default');
                    $row.removeClass('disabled').css('opacity', '1');
                    
                    if(response.success) {
                        // Update visual immediately without reload to feel snappy
                        if(val == 1) { // Dapat Diskon
                            $row.addClass('list-group-item-success');
                            $statusText.html('<i class="fa fa-check-circle text-success"></i> Dapat Diskon Member');
                        } else {
                            $row.removeClass('list-group-item-success');
                            $statusText.html('<i class="fa fa-times-circle text-danger"></i> TIDAK Dapat Diskon');
                        }
                        
                        // Also refresh search results if visible, because item colors depend on category
                        if($('#searchResults table').length > 0) {
                            searchItems();
                        }
                    } else {
                        alert('Gagal update kategori: ' + response.message);
                        // Revert toggle
                        toggleEl.prop('checked', !toggleEl.is(':checked'));
                    }
                },
                error: function(e) {
                    $('body').css('cursor', 'default');
                    $row.removeClass('disabled').css('opacity', '1');
                    alert('Koneksi Error');
                    // Revert toggle
                    toggleEl.prop('checked', !toggleEl.is(':checked'));
                }
            });
        }
        
        // ------------------ ITEM FUNCTIONS ------------------

        function testConnection() {
            alert('JavaScript Running! Mencoba koneksi ke server...');
            $.ajax({
                url: 'ajax_update_item_diskon.php', // Check if reachable
                success: function() { alert('Koneksi ke backend AJAX Berhasil!'); },
                error: function(e) { alert('Gagal connect ke backend: ' + e.statusText); }
            });
        }

        function searchItems() {
            var search = $('#searchItem').val();
            var filterTipe = $('#filterTipe').val();
            
            if(search.length < 2) {
                alert('Masukkan minimal 2 karakter untuk mencari');
                return;
            }
            
            $('#searchResults').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
            
            $.ajax({
                url: 'ajax_search_item_diskon.php',
                type: 'POST',
                data: { 
                    search: search,
                    filter_tipe: filterTipe
                },
                success: function(response) {
                    $('#searchResults').html(response);
                },
                error: function() {
                    $('#searchResults').html('<div class="alert alert-danger">Terjadi kesalahan saat mencari</div>');
                }
            });
        }
        
        // Global function for update item status (Robust Method)
        window.updateStatusItem = function(noitem, exclude, needConfirm) {
            console.log('Update called:', noitem, exclude, needConfirm);
            
            if(needConfirm && !confirm('Reset item ini ke pengaturan jenisnya?')) {
                return;
            }
            
            $('body').css('cursor', 'wait');
            
            $.ajax({
                url: 'ajax_update_item_diskon.php',
                type: 'POST',
                data: { 
                    ajax_update_item: 1,
                    noitem: noitem,
                    exclude_value: exclude
                },
                dataType: 'json',
                success: function(response) {
                    $('body').css('cursor', 'default');
                    if(response.success) {
                        alert('Berhasil disimpan!');
                        searchItems(); // Refresh table visual
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('body').css('cursor', 'default');
                    alert('Gagal menghubungi server: ' + error);
                }
            });
        };
    </script>
</body>
</html>
<?php
}
?>
