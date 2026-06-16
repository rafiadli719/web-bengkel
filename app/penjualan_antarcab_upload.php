<?php
/**
 * Upload Penjualan Antar Cabang dari Excel
 * Alur: Upload Excel -> Pilih Cabang Tujuan -> Preview/Validasi -> Simpan
 */
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_user, password, user_akses, foto_user
                                    FROM tbuser WHERE id='$id_user'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];
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

    // Get margin setting for antar cabang
    $margin_persen = 0;
    $q_setting = mysqli_query($koneksi, "SELECT margin_persen FROM tbl_setting_antarcabang WHERE aktif='1' AND (tipe_cabang_tujuan='2' OR tipe_cabang_tujuan IS NULL OR tipe_cabang_tujuan='') ORDER BY id DESC LIMIT 1");
    if($q_setting && mysqli_num_rows($q_setting) > 0){
        $r_setting = mysqli_fetch_array($q_setting);
        $margin_persen = (float)$r_setting['margin_persen'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

    <script src="assets/js/ace-extra.min.js"></script>

    <!-- SheetJS for Excel parsing -->
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"
        onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';}else if(this.dataset.fallback==='1'){this.dataset.fallback='2';this.src='https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js';}"
    ></script>

    <style>
        .upload-area {
            border: 3px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f9f9f9;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #5cb85c;
            background: #f0fff0;
        }
        .upload-area i {
            font-size: 48px;
            color: #999;
            margin-bottom: 15px;
        }
        .upload-area.dragover i {
            color: #5cb85c;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 10px;
            position: relative;
        }
        .step.active {
            background: #5cb85c;
            color: white;
        }
        .step.completed {
            background: #5cb85c;
            color: white;
        }
        .step-line {
            width: 60px;
            height: 3px;
            background: #ddd;
            margin-top: 18px;
        }
        .step-line.completed {
            background: #5cb85c;
        }
        .step-container {
            display: none;
        }
        .step-container.active {
            display: block;
        }
        .preview-table {
            max-height: 400px;
            overflow-y: auto;
        }
        .status-ok { color: #5cb85c; font-weight: bold; }
        .status-warning { color: #f0ad4e; font-weight: bold; }
        .status-error { color: #d9534f; font-weight: bold; }
        .file-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .summary-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
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
                    <small>
                        <i class="fa fa-leaf"></i>
                        <?php include "../lib/subtitel.php"; ?>
                    </small>
                </a>
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
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li><a href="#">Antar Cabang</a></li>
                        <li class="active">Upload Penjualan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Upload Penjualan Antar Cabang
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Dari <?php echo $nama_cabang; ?> (<?php echo $kd_cabang; ?>)
                            </small>
                        </h1>
                    </div>

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" id="step1-indicator">1</div>
                        <div class="step-line" id="line1"></div>
                        <div class="step" id="step2-indicator">2</div>
                        <div class="step-line" id="line2"></div>
                        <div class="step" id="step3-indicator">3</div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-md-10 col-md-offset-1">

                            <!-- Step 1: Upload File -->
                            <div class="step-container active" id="step1">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-blue">
                                        <h4 class="widget-title">
                                            <i class="fa fa-upload"></i>
                                            Step 1: Upload File Excel
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="upload-area" id="uploadArea">
                                                <i class="fa fa-cloud-upload"></i>
                                                <h4>Drag & Drop file Excel di sini</h4>
                                                <p class="text-muted">atau klik untuk memilih file</p>
                                                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none;">
                                            </div>

                                            <div style="margin-top:15px;">
                                                <button type="button" class="btn btn-sm btn-success" id="btnDownloadTemplate">
                                                    <i class="fa fa-download"></i> Download Contoh Template Excel (.xlsx)
                                                </button>
                                            </div>

                                            <div class="file-info" id="fileInfo" style="display:none;">
                                                <i class="fa fa-file-excel-o text-success"></i>
                                                <span id="fileName"></span>
                                                <span class="pull-right text-muted" id="fileRows"></span>
                                            </div>

                                            <div class="alert alert-info" style="margin-top:20px;">
                                                <i class="fa fa-info-circle"></i>
                                                <strong>Format Excel yang didukung:</strong><br>
                                                <ul style="margin-top:10px;margin-bottom:0;">
                                                    <li>Kolom A: Kode Barang</li>
                                                    <li>Kolom B: Qty</li>
                                                    <li>Kolom C: Harga (opsional, akan menggunakan HPP + margin <?php echo $margin_persen; ?>%)</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Pilih Cabang Tujuan -->
                            <div class="step-container" id="step2">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-blue">
                                        <h4 class="widget-title">
                                            <i class="fa fa-building"></i>
                                            Step 2: Pilih Cabang Tujuan
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="form-group">
                                                <label class="control-label">Cabang Tujuan:</label>
                                                <select class="form-control" id="cabangTujuan" required>
                                                    <option value="">-- Pilih Cabang Tujuan --</option>
                                                    <?php
                                                    // Get list of other branches
                                                    $q_cab = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang, tipe_cabang
                                                                                      FROM tbcabang
                                                                                      WHERE kode_cabang != '$kd_cabang'
                                                                                      ORDER BY nama_cabang");
                                                    if($q_cab) {
                                                        while($r_cab = mysqli_fetch_array($q_cab)){
                                                            $tipe_label = ($r_cab['tipe_cabang'] == '1') ? 'Sendiri' : 'Mitra';
                                                            echo "<option value='{$r_cab['kode_cabang']}' data-tipe='{$r_cab['tipe_cabang']}'>";
                                                            echo htmlspecialchars($r_cab['nama_cabang']) . " ({$r_cab['kode_cabang']}) - {$tipe_label}";
                                                            echo "</option>";
                                                        }
                                                    } else {
                                                        echo "<option value='' disabled>Gagal memuat data cabang</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label">Tanggal:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control date-picker" id="tanggal"
                                                           data-date-format="dd/mm/yyyy" value="<?php echo date('d/m/Y'); ?>">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-calendar"></i>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="control-label">Keterangan:</label>
                                                <textarea class="form-control" id="keterangan" rows="2" placeholder="Keterangan (opsional)"></textarea>
                                            </div>

                                            <div class="summary-box" id="pricingInfo">
                                                <i class="fa fa-info-circle"></i>
                                                <strong>Informasi Harga:</strong><br>
                                                <span id="pricingDetail">Pilih cabang tujuan untuk melihat informasi harga.</span>
                                            </div>

                                            <div style="margin-top:20px;">
                                                <button class="btn btn-default" onclick="goToStep(1)">
                                                    <i class="fa fa-arrow-left"></i> Kembali
                                                </button>
                                                <button class="btn btn-primary pull-right" onclick="validateAndPreview()">
                                                    Lanjutkan <i class="fa fa-arrow-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Preview & Simpan -->
                            <div class="step-container" id="step3">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-blue">
                                        <h4 class="widget-title">
                                            <i class="fa fa-list"></i>
                                            Step 3: Preview & Simpan
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row" id="summaryRow">
                                                <div class="col-xs-3">
                                                    <div class="well text-center">
                                                        <h4 id="totalItems">0</h4>
                                                        <small>Total Item</small>
                                                    </div>
                                                </div>
                                                <div class="col-xs-3">
                                                    <div class="well text-center">
                                                        <h4 class="text-success" id="okCount">0</h4>
                                                        <small>Valid</small>
                                                    </div>
                                                </div>
                                                <div class="col-xs-3">
                                                    <div class="well text-center">
                                                        <h4 class="text-warning" id="warningCount">0</h4>
                                                        <small>Warning</small>
                                                    </div>
                                                </div>
                                                <div class="col-xs-3">
                                                    <div class="well text-center">
                                                        <h4 class="text-danger" id="errorCount">0</h4>
                                                        <small>Error</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="preview-table">
                                                <table class="table table-bordered table-striped" id="previewTable">
                                                    <thead>
                                                        <tr>
                                                            <th width="5%">No</th>
                                                            <th width="15%">Kode</th>
                                                            <th width="30%">Nama Barang</th>
                                                            <th width="10%">Qty</th>
                                                            <th width="15%">Harga</th>
                                                            <th width="15%">Subtotal</th>
                                                            <th width="10%">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="previewBody">
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="5" class="text-right">Total:</th>
                                                            <th id="grandTotal">0</th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            <div style="margin-top:20px;">
                                                <button class="btn btn-default" onclick="goToStep(2)">
                                                    <i class="fa fa-arrow-left"></i> Kembali
                                                </button>
                                                <button class="btn btn-success pull-right" id="btnSave" onclick="saveData()">
                                                    <i class="fa fa-save"></i> Simpan Pesanan Penjualan
                                                </button>
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

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <?php include "../lib/footer.php"; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        var uploadedData = [];
        var validatedData = [];
        var marginPersen = <?php echo $margin_persen; ?>;
        var kdCabangAsal = '<?php echo $kd_cabang; ?>';
        var antarCabangSetting = null;

        function showUploadError(msg) {
            alert(msg);
        }

        function downloadTemplateXlsx() {
            if (typeof XLSX === 'undefined') {
                showUploadError('Library Excel (SheetJS) gagal dimuat. Pastikan koneksi internet tidak memblokir CDN.');
                return;
            }

            try {
                var aoa = [
                    ['Kode Barang', 'Qty', 'Harga (opsional)'],
                    ['12345-ABCDE', 1, 0]
                ];
                var ws = XLSX.utils.aoa_to_sheet(aoa);
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'UPLOAD');
                XLSX.writeFile(wb, 'template_upload_penjualan_antar_cabang.xlsx');
            } catch (err) {
                showUploadError('Gagal membuat template Excel: ' + (err && err.message ? err.message : err));
            }
        }

        function handleFile(file) {
            if (!file) return;

            if (typeof XLSX === 'undefined') {
                showUploadError('Library Excel (SheetJS) gagal dimuat. Pastikan koneksi internet tidak memblokir CDN.');
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, {type: 'array'});

                    var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    var jsonData = XLSX.utils.sheet_to_json(firstSheet, {header: 1});

                    // Skip header row if exists (support template baru & lama)
                    var startRow = 0;
                    var hasHeader = false;
                    var hasNamaColumn = false;
                    if(jsonData.length > 0 && jsonData[0] && jsonData[0].length > 0) {
                        var h0 = (jsonData[0][0] || '').toString().toLowerCase();
                        var h1 = (jsonData[0][1] || '').toString().toLowerCase();
                        var h2 = (jsonData[0][2] || '').toString().toLowerCase();
                        if(h0.indexOf('kode') !== -1 || h0.indexOf('barang') !== -1) {
                            hasHeader = true;
                        }
                        if(h1.indexOf('nama') !== -1) {
                            hasNamaColumn = true;
                        }
                        if(!hasHeader && (h1.indexOf('qty') !== -1 || h2.indexOf('qty') !== -1)) {
                            hasHeader = true;
                        }
                    }
                    if(hasHeader) {
                        startRow = 1;
                    }

                    uploadedData = [];
                    for(var i = startRow; i < jsonData.length; i++) {
                        var row = jsonData[i];
                        if(!row || !row.length) continue;
                        if(row[0] && row[0].toString().trim() !== '') {
                            var kode = row[0] ? row[0].toString().trim() : '';
                            var nama = '';
                            var qty = 0;
                            var harga = 0;

                            // Deteksi template lama vs baru per baris (agar kompatibel)
                            var rowHasNama = hasNamaColumn;
                            if(!hasNamaColumn) {
                                var c1 = row.length > 1 ? row[1] : '';
                                var c2 = row.length > 2 ? row[2] : '';
                                if(c1 !== '' && isNaN(c1) && !isNaN(c2)) {
                                    rowHasNama = true;
                                }
                            }

                            if(rowHasNama) {
                                nama = row[1] ? row[1].toString().trim() : '';
                                qty = row[2] ? parseInt(row[2]) : 0;
                                harga = row[3] ? parseFloat(row[3]) : 0;
                            } else {
                                qty = row[1] ? parseInt(row[1]) : 0;
                                harga = row[2] ? parseFloat(row[2]) : 0;
                            }

                            uploadedData.push({
                                kode: kode,
                                nama: nama,
                                qty: qty,
                                harga: harga
                            });
                        }
                    }

                    document.getElementById('fileName').textContent = file.name;
                    document.getElementById('fileRows').textContent = uploadedData.length + ' baris data';
                    document.getElementById('fileInfo').style.display = 'block';

                    // Auto go to step 2
                    setTimeout(function() {
                        goToStep(2);
                    }, 300);
                } catch (err) {
                    showUploadError('File Excel gagal diproses. Pastikan format sesuai template.\n\nDetail: ' + (err && err.message ? err.message : err));
                }
            };
            reader.onerror = function() {
                showUploadError('Gagal membaca file. Coba ulangi.');
            };
            reader.readAsArrayBuffer(file);
        }

        // Initialize datepicker + handlers
        $(document).ready(function(){
            $('.date-picker').datepicker({
                autoclose: true,
                todayHighlight: true
            });

            var uploadArea = document.getElementById('uploadArea');
            var fileInput = document.getElementById('fileInput');
            var btnDownloadTemplate = document.getElementById('btnDownloadTemplate');

            if (btnDownloadTemplate) {
                btnDownloadTemplate.addEventListener('click', function() {
                    downloadTemplateXlsx();
                });
            }

            if (uploadArea && fileInput) {
                uploadArea.addEventListener('click', function() {
                    try {
                        fileInput.click();
                    } catch (err) {
                        showUploadError('Gagal membuka pemilih file: ' + (err && err.message ? err.message : err));
                    }
                });

                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    uploadArea.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                    var files = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files : [];
                    if(files.length > 0) {
                        handleFile(files[0]);
                        fileInput.value = '';
                    }
                });

                fileInput.addEventListener('change', function(e) {
                    if(e.target.files.length > 0) {
                        handleFile(e.target.files[0]);
                        fileInput.value = '';
                    }
                });
            }

            // Update pricing info when cabang changes
            $('#cabangTujuan').change(function(){
                var cabangTujuan = $(this).val();
                antarCabangSetting = null;
                if(!cabangTujuan) {
                    $('#pricingDetail').html('Pilih cabang tujuan untuk melihat informasi harga.');
                    return;
                }

                $.ajax({
                    url: '_ajax/ajax-validate-antarcab-upload.php',
                    method: 'POST',
                    data: {
                        action: 'get_setting',
                        cabang_tujuan: cabangTujuan
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if(resp && resp.success) {
                            antarCabangSetting = resp.setting;
                            var tipeCabang = resp.tipe_cabang_tujuan;
                            var info = '';
                            if(tipeCabang == '1'){
                                info = 'Cabang Sendiri: Harga = HPP (Harga Pokok Pembelian), Cara Bayar = ' + resp.setting.cara_bayar + ', Diskon ' + resp.setting.diskon_persen + '%';
                            } else {
                                info = 'Cabang Mitra: Harga = HPP + ' + resp.setting.margin_persen + '% margin, Cara Bayar = ' + resp.setting.cara_bayar + ', Tempo ' + resp.setting.tempo_hari + ' hari';
                            }
                            $('#pricingDetail').html(info);
                        } else {
                            $('#pricingDetail').html('Gagal mengambil setting harga.');
                        }
                    },
                    error: function() {
                        $('#pricingDetail').html('Gagal mengambil setting harga.');
                    }
                });
            });
        });

        function goToStep(step) {
            document.querySelectorAll('.step-container').forEach(function(el) {
                el.classList.remove('active');
            });
            document.getElementById('step' + step).classList.add('active');

            // Update indicators
            for(var i = 1; i <= 3; i++) {
                var indicator = document.getElementById('step' + i + '-indicator');
                if(i < step) {
                    indicator.classList.add('completed');
                    indicator.classList.remove('active');
                } else if(i === step) {
                    indicator.classList.add('active');
                    indicator.classList.remove('completed');
                } else {
                    indicator.classList.remove('active', 'completed');
                }
            }

            // Update lines
            document.getElementById('line1').classList.toggle('completed', step > 1);
            document.getElementById('line2').classList.toggle('completed', step > 2);
        }

        function validateAndPreview() {
            var cabangTujuan = document.getElementById('cabangTujuan').value;

            if(!cabangTujuan) {
                alert('Pilih cabang tujuan terlebih dahulu!');
                return;
            }

            if(uploadedData.length === 0) {
                alert('Tidak ada data yang diupload!');
                return;
            }

            // Validate items via AJAX
            $.ajax({
                url: '_ajax/ajax-validate-antarcab-upload.php',
                method: 'POST',
                data: {
                    action: 'validate',
                    items: JSON.stringify(uploadedData),
                    cabang_tujuan: cabangTujuan
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        validatedData = response.data;
                        antarCabangSetting = response.setting ? response.setting : antarCabangSetting;
                        renderPreview(response.data, response.summary);
                        goToStep(3);
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat validasi data');
                }
            });
        }

        function renderPreview(data, summary) {
            var tbody = document.getElementById('previewBody');
            tbody.innerHTML = '';

            var grandTotal = 0;
            var diskonPersen = 0;
            if(antarCabangSetting && antarCabangSetting.diskon_persen !== undefined) {
                diskonPersen = parseFloat(antarCabangSetting.diskon_persen) || 0;
            }

            for(var i = 0; i < data.length; i++) {
                var item = data[i];
                var subtotalBefore = item.qty * item.harga;
                var subtotalAfter = subtotalBefore - ((subtotalBefore * diskonPersen) / 100);
                if(subtotalAfter < 0) subtotalAfter = 0;
                grandTotal += subtotalAfter;

                var statusClass = 'status-ok';
                if(item.status === 'WARNING') statusClass = 'status-warning';
                if(item.status === 'ERROR') statusClass = 'status-error';

                var row = '<tr>' +
                    '<td>' + (i+1) + '</td>' +
                    '<td>' + item.kode + '</td>' +
                    '<td>' + item.nama + '</td>' +
                    '<td class="text-right">' + item.qty.toLocaleString() + '</td>' +
                    '<td class="text-right">' + item.harga.toLocaleString() + '</td>' +
                    '<td class="text-right">' + subtotalAfter.toLocaleString() + '</td>' +
                    '<td class="' + statusClass + '">' + item.status +
                    (item.message ? '<br><small>' + item.message + '</small>' : '') +
                    '</td>' +
                    '</tr>';
                tbody.innerHTML += row;
            }

            document.getElementById('totalItems').textContent = summary.total;
            document.getElementById('okCount').textContent = summary.ok;
            document.getElementById('warningCount').textContent = summary.warning;
            document.getElementById('errorCount').textContent = summary.error;
            document.getElementById('grandTotal').textContent = grandTotal.toLocaleString();

            // Disable save button if there are errors
            document.getElementById('btnSave').disabled = (summary.error > 0);
        }

        function saveData() {
            var cabangTujuan = document.getElementById('cabangTujuan').value;
            var tanggal = document.getElementById('tanggal').value;
            var keterangan = document.getElementById('keterangan').value;

            if(!confirm('Simpan Pesanan Penjualan Antar Cabang?')) return;

            $('#btnSave').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: '_ajax/ajax-validate-antarcab-upload.php',
                method: 'POST',
                data: {
                    action: 'save',
                    items: JSON.stringify(validatedData),
                    cabang_tujuan: cabangTujuan,
                    tanggal: tanggal,
                    note: keterangan
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        alert('Pesanan Penjualan Antar Cabang berhasil dibuat!\n\n' +
                              'No. Pesanan: ' + response.no_order + '\n' +
                              'Total Item: ' + response.total_item + '\n' +
                              'Total Qty: ' + response.total_qty + '\n' +
                              'Total: Rp ' + parseInt(response.total_order).toLocaleString());
                        window.location.href = 'penjualan_add_rst.php?spesan=' + encodeURIComponent(response.no_order) + '&auto=1';
                    } else {
                        alert('Error: ' + response.error);
                        $('#btnSave').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pesanan Penjualan');
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menyimpan data');
                    $('#btnSave').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pesanan Penjualan');
                }
            });
        }
    </script>
</body>
</html>

<?php
}
?>
