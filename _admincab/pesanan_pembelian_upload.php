<?php
/**
 * UPLOAD PESANAN PEMBELIAN DARI EXCEL
 * Tahap awal input PO dengan upload file Excel
 */
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user=$_SESSION['_iduser'];
$kd_cabang=$_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd=mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari=mysqli_fetch_array($cari_kd);
$_nama=$tm_cari['nama_user'];
$foto_user=$tm_cari['foto_user'];
if($foto_user=='') {
    $foto_user="file_upload/avatar.png";
}

// Data Cabang
$cari_kd=mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$tm_cari=mysqli_fetch_array($cari_kd);
$nama_cabang=$tm_cari['nama_cabang'];

// Get suppliers
$suppliers = mysqli_query($koneksi, "SELECT kode_supplier, nama_supplier FROM tblsupplier WHERE is_aktif=1 ORDER BY nama_supplier");
$suppliers_code_field = 'kode_supplier';
$suppliers_name_field = 'nama_supplier';
if(!$suppliers){
    $suppliers = mysqli_query($koneksi, "SELECT nosupplier AS kode_supplier, namasupplier AS nama_supplier FROM tblsupplier WHERE statusaktif='1' ORDER BY namasupplier");
    $suppliers_code_field = 'kode_supplier';
    $suppliers_name_field = 'nama_supplier';
}
if(!$suppliers){
    $suppliers = mysqli_query($koneksi, "SELECT nosupplier AS kode_supplier, namasupplier AS nama_supplier FROM tblsupplier ORDER BY namasupplier");
    $suppliers_code_field = 'kode_supplier';
    $suppliers_name_field = 'nama_supplier';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .upload-box {
            border: 3px dashed #ccc;
            border-radius: 10px;
            padding: 50px 20px;
            text-align: center;
            background: #f9f9f9;
            margin: 20px 0;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-box:hover, .upload-box.dragover {
            border-color: #438eb9;
            background: #e8f4fc;
        }
        .upload-box i {
            font-size: 60px;
            color: #ccc;
        }
        .upload-box:hover i {
            color: #438eb9;
        }
        .step-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #438eb9;
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
        }
        .preview-table {
            max-height: 400px;
            overflow-y: auto;
        }
        .status-ok { color: #5cb85c; }
        .status-error { color: #d9534f; }
        .status-warning { color: #f0ad4e; }
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        #loadingOverlay .spinner {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>

<body class="no-skin">
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p style="margin-top: 15px;">Memproses file Excel...</p>
        </div>
    </div>

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
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_pembelian02.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                        <li><a href="pesanan_pembelian.php">Pesanan Pembelian</a></li>
                        <li class="active">Upload Excel</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Upload Pesanan Pembelian
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Import dari File Excel
                            </small>
                        </h1>
                    </div>

                    <div class="row">
                        <!-- Step 1: Upload File -->
                        <div class="col-md-12">
                            <div class="step-box" id="step1">
                                <h4><span class="step-number">1</span> Upload File Excel</h4>
                                <hr>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="upload-box" id="dropZone">
                                            <i class="fa fa-cloud-upload"></i>
                                            <h4>Drag & Drop file Excel di sini</h4>
                                            <p>atau klik untuk memilih file</p>
                                            <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none;">
                                            <p><small class="text-muted">Format yang didukung: .xlsx, .xls, .csv</small></p>
                                        </div>
                                        <div id="fileInfo" style="display:none;" class="alert alert-info">
                                            <i class="fa fa-file-excel-o"></i> <span id="fileName"></span>
                                            <button type="button" class="btn btn-xs btn-danger pull-right" onclick="clearFile()">
                                                <i class="fa fa-times"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="well">
                                            <h5><i class="fa fa-info-circle"></i> Format Excel</h5>
                                            <p>File Excel harus memiliki kolom:</p>
                                            <ol>
                                                <li><strong>KODE</strong> - Kode Item</li>
                                                <li><strong>QTY</strong> - Jumlah Order</li>
                                                <li><strong>HARGA</strong> - Harga Satuan (opsional)</li>
                                            </ol>
                                            <a href="template/template_po.php" class="btn btn-sm btn-info btn-block">
                                                <i class="fa fa-download"></i> Download Template
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Pilih Supplier -->
                        <div class="col-md-12" id="step2Container" style="display:none;">
                            <div class="step-box" id="step2">
                                <h4><span class="step-number">2</span> Pilih Supplier</h4>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Supplier <span class="text-danger">*</span></label>
                                            <select id="cboSupplier" class="form-control">
                                                <option value="">-- Pilih Supplier --</option>
                                                <?php if($suppliers){ while ($sup = mysqli_fetch_assoc($suppliers)) { ?>
                                                <option value="<?php echo $sup[$suppliers_code_field]; ?>">
                                                    <?php echo $sup[$suppliers_code_field]; ?> - <?php echo $sup[$suppliers_name_field]; ?>
                                                </option>
                                                <?php } } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Tanggal Order</label>
                                            <input type="date" id="txtTanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Catatan</label>
                                            <input type="text" id="txtNote" class="form-control" placeholder="Catatan (opsional)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Preview Data -->
                        <div class="col-md-12" id="step3Container" style="display:none;">
                            <div class="step-box" id="step3">
                                <h4><span class="step-number">3</span> Preview Data</h4>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="alert alert-info">
                                            <strong>Total Item:</strong> <span id="totalItem">0</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-success">
                                            <strong>Item OK:</strong> <span id="itemOk">0</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-danger">
                                            <strong>Item Error:</strong> <span id="itemError">0</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="preview-table">
                                    <table class="table table-bordered table-striped table-hover" id="previewTable">
                                        <thead>
                                            <tr class="info">
                                                <th class="center" width="5%">No</th>
                                                <th width="15%">Kode Item</th>
                                                <th width="30%">Nama Item</th>
                                                <th class="center" width="10%">Qty</th>
                                                <th class="right" width="15%">Harga</th>
                                                <th class="right" width="15%">Subtotal</th>
                                                <th class="center" width="10%">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="previewBody">
                                        </tbody>
                                        <tfoot>
                                            <tr class="warning">
                                                <th colspan="5" class="right"><strong>TOTAL</strong></th>
                                                <th class="right"><strong id="grandTotal">0</strong></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-12" id="actionButtons" style="display:none;">
                            <div class="text-center">
                                <hr>
                                <a href="pesanan_pembelian.php" class="btn btn-default btn-lg">
                                    <i class="fa fa-arrow-left"></i> Kembali
                                </a>
                                <button type="button" id="btnProcess" class="btn btn-primary btn-lg" onclick="processUpload()">
                                    <i class="fa fa-check"></i> Proses Upload
                                </button>
                                <button type="button" id="btnSave" class="btn btn-success btn-lg" onclick="saveOrder()" style="display:none;">
                                    <i class="fa fa-save"></i> Simpan Pesanan Pembelian
                                </button>
                            </div>
                        </div>
                    </div>

                </div><!-- /.page-content -->
            </div>
        </div><!-- /.main-content -->
    </div><!-- /.main-container -->

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
    var uploadedData = [];
    var validatedData = [];

    // Drag and Drop
    var dropZone = document.getElementById('dropZone');
    var fileInput = document.getElementById('fileInput');

    dropZone.onclick = function() { fileInput.click(); };

    dropZone.ondragover = function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    };

    dropZone.ondragleave = function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    };

    dropZone.ondrop = function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    };

    fileInput.onchange = function(e) {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    };

    function handleFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();

        if (!['xlsx', 'xls', 'csv'].includes(ext)) {
            alert('Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv');
            return;
        }

        $('#fileName').text(file.name + ' (' + formatBytes(file.size) + ')');
        $('#fileInfo').show();
        $('#step2Container').show();
        $('#actionButtons').show();

        parseExcel(file);
    }

    function clearFile() {
        fileInput.value = '';
        $('#fileInfo').hide();
        $('#step2Container').hide();
        $('#step3Container').hide();
        $('#actionButtons').hide();
        uploadedData = [];
        validatedData = [];
    }

    function parseExcel(file) {
        $('#loadingOverlay').css('display', 'flex');

        function normalizeHeaderCell(val) {
            return String(val || '').trim().toUpperCase();
        }

        function parseIntLoose(val) {
            if (val === null || typeof val === 'undefined') return 0;
            if (typeof val === 'number' && isFinite(val)) return Math.floor(val);
            var s = String(val).trim();
            if (!s) return 0;
            s = s.replace(/[^0-9.,-]/g, '');
            s = s.replace(/[.,]/g, '');
            var n = parseInt(s, 10);
            return isNaN(n) ? 0 : n;
        }

        function parseFloatLoose(val) {
            if (val === null || typeof val === 'undefined') return 0;
            if (typeof val === 'number' && isFinite(val)) return val;
            var s = String(val).trim();
            if (!s) return 0;
            s = s.replace(/[^0-9.,-]/g, '');
            s = s.replace(/[.,]/g, '');
            var n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {type: 'array'});
                var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                var jsonData = XLSX.utils.sheet_to_json(firstSheet, {header: 1});

                // Parse data (skip header row)
                uploadedData = [];
                var headerRow = (jsonData && jsonData.length > 0) ? jsonData[0] : [];
                var idxKode = -1;
                var idxQty = -1;
                var idxHarga = -1;
                var idxNama = -1;
                if (headerRow && headerRow.length) {
                    for (var h = 0; h < headerRow.length; h++) {
                        var head = normalizeHeaderCell(headerRow[h]);
                        if (head === 'KODE' || head === 'KODE ITEM' || head === 'NO ITEM' || head === 'NOITEM') idxKode = h;
                        if (head === 'QTY' || head === 'JUMLAH' || head === 'QTY ORDER') idxQty = h;
                        if (head === 'HARGA' || head === 'HARGA POKOK' || head === 'HARGA BELI') idxHarga = h;
                        if (head === 'NAMA' || head === 'NAMA ITEM' || head === 'ITEM') idxNama = h;
                    }
                }
                for (var i = 1; i < jsonData.length; i++) {
                    var row = jsonData[i];
                    if (row.length >= 2 && row[0]) {
                        var kode = '';
                        if (idxKode >= 0 && typeof row[idxKode] !== 'undefined' && row[idxKode] !== null && String(row[idxKode]).trim() !== '') {
                            kode = String(row[idxKode]).trim();
                        } else {
                            kode = String(row[0]).trim();
                        }

                        var qty = 0;
                        var harga = 0;

                        // Format baru: KODE, QTY, HARGA
                        // Format lama (masih didukung): KODE, NAMA, QTY, HARGA
                        if (idxQty >= 0) {
                            qty = parseIntLoose(row[idxQty]);
                        } else if (row.length >= 3 && !isNaN(parseFloat(row[1]))) {
                            qty = parseIntLoose(row[1]);
                        } else {
                            qty = parseIntLoose(row[2]);
                        }

                        if (idxHarga >= 0) {
                            harga = parseFloatLoose(row[idxHarga]);
                        } else if (row.length >= 3 && !isNaN(parseFloat(row[1]))) {
                            harga = parseFloatLoose(row[2]);
                        } else {
                            harga = parseFloatLoose(row[3]);
                        }

                        uploadedData.push({
                            kode: kode,
                            nama: '',
                            qty: qty,
                            harga: harga
                        });
                    }
                }

                $('#loadingOverlay').hide();

                if (uploadedData.length === 0) {
                    alert('Tidak ada data yang valid dalam file Excel.');
                    return;
                }

                alert('Berhasil membaca ' + uploadedData.length + ' baris data. Klik "Proses Upload" untuk validasi.');

            } catch (err) {
                $('#loadingOverlay').hide();
                alert('Error membaca file: ' + err.message);
            }
        };
        reader.readAsArrayBuffer(file);
    }

    function processUpload() {
        var supplier = $('#cboSupplier').val();
        if (!supplier) {
            alert('Pilih supplier terlebih dahulu!');
            $('#cboSupplier').focus();
            return;
        }

        if (uploadedData.length === 0) {
            alert('Tidak ada data untuk diproses!');
            return;
        }

        $('#loadingOverlay').css('display', 'flex');

        // Send to server for validation
        $.ajax({
            url: '_ajax/ajax-validate-po-upload.php',
            method: 'POST',
            data: {
                action: 'validate',
                items: JSON.stringify(uploadedData),
                supplier: supplier
            },
            dataType: 'json',
            success: function(response) {
                $('#loadingOverlay').hide();

                if (response.success) {
                    validatedData = response.data;
                    displayPreview(validatedData, response.summary);
                    $('#step3Container').show();
                    $('#btnProcess').hide();
                    $('#btnSave').show();
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                $('#loadingOverlay').hide();
                alert('Error koneksi: ' + error);
            }
        });
    }

    function displayPreview(data, summary) {
        var html = '';
        var grandTotal = 0;

        for (var i = 0; i < data.length; i++) {
            var item = data[i];
            var statusClass = item.status === 'OK' ? 'status-ok' :
                              item.status === 'WARNING' ? 'status-warning' : 'status-error';
            var statusIcon = item.status === 'OK' ? 'fa-check' :
                             item.status === 'WARNING' ? 'fa-exclamation-triangle' : 'fa-times';

            var subtotal = item.qty * item.harga;
            grandTotal += subtotal;

            html += '<tr class="' + (item.status === 'ERROR' ? 'danger' : '') + '">';
            html += '<td class="center">' + (i + 1) + '</td>';
            html += '<td>' + item.kode + '</td>';
            html += '<td>' + item.nama + (item.message ? '<br><small class="text-muted">' + item.message + '</small>' : '') + '</td>';
            html += '<td class="center">' + item.qty.toLocaleString() + '</td>';
            html += '<td class="right">' + item.harga.toLocaleString() + '</td>';
            html += '<td class="right">' + subtotal.toLocaleString() + '</td>';
            html += '<td class="center"><span class="' + statusClass + '"><i class="fa ' + statusIcon + '"></i> ' + item.status + '</span></td>';
            html += '</tr>';
        }

        $('#previewBody').html(html);
        $('#grandTotal').text(grandTotal.toLocaleString());

        $('#totalItem').text(summary.total);
        $('#itemOk').text(summary.ok);
        $('#itemError').text(summary.error);
    }

    function saveOrder() {
        var supplier = $('#cboSupplier').val();
        var tanggal = $('#txtTanggal').val();
        var note = $('#txtNote').val();

        if (!supplier) {
            alert('Pilih supplier!');
            return;
        }

        // Filter only OK and WARNING items
        var itemsToSave = validatedData.filter(function(item) {
            return item.status !== 'ERROR';
        });

        if (itemsToSave.length === 0) {
            alert('Tidak ada item valid untuk disimpan!');
            return;
        }

        if (!confirm('Simpan ' + itemsToSave.length + ' item ke Pesanan Pembelian?')) {
            return;
        }

        $('#loadingOverlay').css('display', 'flex');

        $.ajax({
            url: '_ajax/ajax-validate-po-upload.php',
            method: 'POST',
            data: {
                action: 'save',
                items: JSON.stringify(itemsToSave),
                supplier: supplier,
                tanggal: tanggal,
                note: note
            },
            dataType: 'json',
            success: function(response) {
                $('#loadingOverlay').hide();

                if (response.success) {
                    alert('Pesanan Pembelian berhasil dibuat!\nNo. Order: ' + response.no_order);
                    window.location.href = 'pesanan_pembelian_detail.php?nopesanan=' + response.no_order;
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                $('#loadingOverlay').hide();
                alert('Error koneksi: ' + error);
            }
        });
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    </script>
</body>
</html>
