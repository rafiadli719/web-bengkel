<?php
/**
 * HALAMAN TEST: servis-input-reg-coba.php
 *
 * Halaman ini digunakan untuk testing redesign tab Temuan & Penawaran
 * Template baru: _template/tab-temuan-penawaran-content-coba.php
 *
 * URL Test: http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reg-coba.php?snoserv=SV25000000272
 */

session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

include "../config/koneksi.php";
include_once "../lib/rbac.php";
rbac_require_any(array('input_servis_read','servis_reguler_read','servis_menu_read','service_create','service_update'));

// Include handlers
include "_handler_temuan_penawaran.php";
include "_handler_barang_custom.php";

// Get user data
$cari_kd = mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari = mysqli_fetch_array($cari_kd);
$_nama = $tm_cari['nama_user'] ?? '';
$lvl_akses = $tm_cari['user_akses'] ?? '';
$foto_user = $tm_cari['foto_user'] ?? '';
if($foto_user == '') {
    $foto_user = "file_upload/avatar.png";
}

if(!isset($_SESSION['username'])) {
    $_SESSION['username'] = $_nama;
}

// Get cabang data
$cari_kd = mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$tm_cari = mysqli_fetch_array($cari_kd);
$nama_cabang = $tm_cari ? $tm_cari['nama_cabang'] : '';
$tipe_cabang = $tm_cari ? $tm_cari['tipe_cabang'] : '';

// Get service number
$no_service = isset($_GET['snoserv']) ? $_GET['snoserv'] : '';
if(empty($no_service) && isset($_GET['no_service'])) {
    $no_service = $_GET['no_service'];
}

// Sanitize
$no_service = mysqli_real_escape_string($koneksi, $no_service);

// Get service data
$tanggal = date('d/m/Y');
$jam = date('H:i');
$kode_pelanggan = '';
$no_polisi = '';
$status_servis = 'datang';
$km_skr = '';
$km_berikut = '';
$namapelanggan = '';

if(!empty($no_service)) {
    $cari_kd = mysqli_query($koneksi,"SELECT tanggal, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_serv,
                                      jam, no_pelanggan, no_polisi, status_servis, km_skr, km_berikutnya
                                      FROM tblservice WHERE no_service='$no_service'");
    if($cari_kd && mysqli_num_rows($cari_kd) > 0) {
        $tm_cari = mysqli_fetch_array($cari_kd);
        $tanggal = $tm_cari['tanggal_serv'] ?? date('d/m/Y');
        $jam = $tm_cari['jam'] ?? date('H:i');
        $kode_pelanggan = $tm_cari['no_pelanggan'] ?? '';
        $no_polisi = $tm_cari['no_polisi'] ?? '';
        $status_servis = $tm_cari['status_servis'] ?? 'datang';
        $km_skr = $tm_cari['km_skr'] ?? '';
        $km_berikut = $tm_cari['km_berikutnya'] ?? '';
    }

    // Get customer name
    if(!empty($kode_pelanggan)) {
        $cari_plg = mysqli_query($koneksi, "SELECT namapelanggan FROM tblpelanggan WHERE nopelanggan='$kode_pelanggan'");
        if($cari_plg && $plg = mysqli_fetch_array($cari_plg)) {
            $namapelanggan = $plg['namapelanggan'] ?? '';
        }
    }
}

// Get vehicle data
$jenis = '';
$merek = '';
$warna = '';
if(!empty($no_polisi)) {
    $cari_kend = mysqli_query($koneksi, "SELECT jenis, tipe as merek, warna FROM tblkendaraan WHERE nopolisi='$no_polisi'");
    if($cari_kend && $kend = mysqli_fetch_array($cari_kend)) {
        $jenis = $kend['jenis'] ?? '';
        $merek = $kend['merek'] ?? '';
        $warna = $kend['warna'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TEST REDESIGN - Tab Temuan & Penawaran | FIT MOTOR</title>

    <!-- Bootstrap & Ace Admin CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css">
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet">
    <link rel="stylesheet" href="assets/css/ace-skins.min.css">

    <style>
    .test-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .test-banner h4 {
        margin: 0;
        font-weight: 600;
    }
    .test-banner .badge {
        background: rgba(255,255,255,0.2);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
    }
    .service-info-card {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    .service-info-card h5 {
        margin: 0 0 12px 0;
        color: #333;
        font-size: 16px;
    }
    .service-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }
    .service-info-item {
        display: flex;
        flex-direction: column;
    }
    .service-info-item label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 2px;
    }
    .service-info-item span {
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #4A90D9;
        text-decoration: none;
        font-size: 14px;
        margin-bottom: 16px;
    }
    .back-link:hover {
        color: #3A7BC8;
        text-decoration: underline;
    }
    .comparison-toggle {
        margin-bottom: 20px;
        padding: 12px 16px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
    }
    .comparison-toggle a {
        color: #856404;
        font-weight: 500;
    }
    </style>
</head>
<body class="no-skin">

<!-- Navbar -->
<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <div class="navbar-header pull-left">
            <a href="../index.php" class="navbar-brand">
                <small><i class="fa fa-wrench"></i> FIT MOTOR - TEST REDESIGN</small>
            </a>
        </div>
        <div class="navbar-buttons navbar-header pull-right">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a href="#">
                        <span class="user-info">
                            <small>Sedang Login,</small>
                            <?php echo $_nama; ?>
                        </span>
                        <i class="ace-icon fa fa-user"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="main-container ace-save-state" id="main-container">
    <div class="main-content">
        <div class="page-content">
            <div class="page-header">
                <h1>
                    <i class="ace-icon fa fa-flask"></i>
                    TEST REDESIGN - Tab Temuan & Penawaran
                </h1>
            </div>

            <div class="row">
                <div class="col-xs-12">

                    <!-- Back Link -->
                    <a href="servis-input-reguler.php?snoserv=<?= htmlspecialchars($no_service) ?>&tab=temuan" class="back-link">
                        <i class="fa fa-arrow-left"></i> Kembali ke halaman asli
                    </a>

                    <!-- Test Banner -->
                    <div class="test-banner">
                        <div>
                            <h4><i class="fa fa-flask"></i> Halaman Test Redesign UI</h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 13px;">
                                Template: <code style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px;">_template/tab-temuan-penawaran-content-coba.php</code>
                            </p>
                        </div>
                        <span class="badge">v2.0 Clean UI</span>
                    </div>

                    <!-- Comparison Link -->
                    <div class="comparison-toggle">
                        <i class="fa fa-exchange"></i>
                        <strong>Bandingkan dengan versi asli:</strong>
                        <a href="servis-input-reguler.php?snoserv=<?= htmlspecialchars($no_service) ?>&tab=temuan" target="_blank">
                            Buka Tab Temuan (Versi Lama) di tab baru <i class="fa fa-external-link"></i>
                        </a>
                    </div>

                    <!-- Service Info Card -->
                    <div class="service-info-card">
                        <h5><i class="fa fa-info-circle"></i> Informasi Service</h5>
                        <div class="service-info-grid">
                            <div class="service-info-item">
                                <label>No. Service</label>
                                <span><?= htmlspecialchars($no_service) ?: '-' ?></span>
                            </div>
                            <div class="service-info-item">
                                <label>Tanggal</label>
                                <span><?= htmlspecialchars($tanggal) ?></span>
                            </div>
                            <div class="service-info-item">
                                <label>Pelanggan</label>
                                <span><?= htmlspecialchars($namapelanggan) ?: '-' ?></span>
                            </div>
                            <div class="service-info-item">
                                <label>No. Polisi</label>
                                <span><?= htmlspecialchars($no_polisi) ?: '-' ?></span>
                            </div>
                            <div class="service-info-item">
                                <label>Kendaraan</label>
                                <span><?= htmlspecialchars($jenis . ' ' . $merek) ?: '-' ?></span>
                            </div>
                            <div class="service-info-item">
                                <label>Status</label>
                                <span style="color: <?= $status_servis == 'bayar' ? '#27ae60' : '#f39c12' ?>;">
                                    <?= ucfirst($status_servis) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if(empty($no_service)): ?>
                    <!-- No Service Selected -->
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Tidak ada nomor service!</strong>
                        <p>Silakan buka halaman ini dengan parameter <code>?snoserv=NOMOR_SERVICE</code></p>
                        <p>Contoh: <code>servis-input-reg-coba.php?snoserv=SV25000000272</code></p>
                    </div>
                    <?php else: ?>

                    <!-- REDESIGNED TAB CONTENT -->
                    <div class="widget-box">
                        <div class="widget-header">
                            <h4 class="widget-title">
                                <i class="ace-icon fa fa-search-plus"></i>
                                Tab Temuan & Penawaran (Redesign v2.0)
                            </h4>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main padding-12">
                                <?php
                                // Include template redesign baru
                                include "_template/tab-temuan-penawaran-content-coba.php";
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>

<!-- Include modal search temuan jika ada -->
<?php
$modal_search = __DIR__ . "/_template/modal-search-temuan.php";
if(file_exists($modal_search)) {
    include $modal_search;
}

// Include modal fast moves jika ada
$modal_fastmoves = __DIR__ . "/_template/modal-fastmoves-v2.php";
if(file_exists($modal_fastmoves)) {
    include $modal_fastmoves;
}

// Include modal tambah part ke temuan
$modal_add_part = __DIR__ . "/_template/_servis_list_temuan_penawaran.php";
// Hanya include bagian modal-nya (skip karena sudah include penuh di template)
?>

<!-- Modal Edit Temuan (dari template asli) -->
<div class="modal fade" id="modalEditTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEditTemuan" method="POST">
                <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">
                <input type="hidden" name="temuan_id" id="edit_temuan_id" value="">
                <input type="hidden" name="btnedittemuan" value="1">
                <input type="hidden" name="kode_temuan" id="edit_kode_temuan" value="">

                <div class="modal-header" style="background: #4A90D9; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Temuan</h4>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Temuan</label>
                                <input type="text" class="form-control" id="edit_nama_temuan_display" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Deskripsi Detail</label>
                                <textarea class="form-control" id="edit_temuan_deskripsi" name="deskripsi_temuan" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Jenis Perbaikan</label>
                                <select class="form-control" id="edit_temuan_jenis" name="jenis_perbaikan">
                                    <option value="setting">Hanya Setting/Servis</option>
                                    <option value="penggantian_part">Penggantian Part</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tingkat Urgensi</label>
                                <select class="form-control" id="edit_temuan_urgensi" name="tingkat_urgensi">
                                    <option value="rendah">Rendah</option>
                                    <option value="sedang">Sedang</option>
                                    <option value="tinggi">Tinggi</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Modal Input Barang Custom -->
<?php
$modal_custom = __DIR__ . "/_template/modal-input-barang-custom.php";
if(file_exists($modal_custom)) {
    include $modal_custom;
}
?>

<!-- Modal Tambah Part ke Temuan -->
<div class="modal fade" id="modalAddPartToTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formAddPartToTemuan" method="POST">
                <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">
                <input type="hidden" name="temuan_id" id="modal_add_part_temuan_id" value="">
                <input type="hidden" name="btnaddpenawaran" value="1">

                <div class="modal-header" style="background: #4A90D9; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-plus-circle"></i> Tambah Part</h4>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Temuan:</strong> <span id="modal_add_part_temuan_name">-</span>
                    </div>

                    <div class="row">
                        <div class="col-sm-5">
                            <div class="form-group">
                                <label>Kode Part</label>
                                <input type="text" class="form-control" id="add_part_kode" name="kode_barang" required>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <div class="form-group">
                                <label>Nama Part</label>
                                <input type="text" class="form-control" id="add_part_nama" name="nama_barang" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-5">
                            <div class="form-group">
                                <label>Harga</label>
                                <input type="number" class="form-control" id="add_part_harga" name="harga_satuan" min="0" required>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>Qty</label>
                                <input type="number" class="form-control" id="add_part_qty" name="quantity" value="1" min="1" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Total</label>
                                <input type="text" class="form-control" id="add_part_total_display" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Calculate total for add part modal
function calcAddPartTotal() {
    var h = parseInt($('#add_part_harga').val() || 0);
    var q = parseInt($('#add_part_qty').val() || 1);
    $('#add_part_total_display').val('Rp ' + (h * q).toLocaleString('id-ID'));
}

// Override applyCustomItemToForm untuk support field V2
var originalApplyCustomItemToForm = typeof applyCustomItemToForm === 'function' ? applyCustomItemToForm : null;

function applyCustomItemToForm(data) {
    // Support untuk template V2 (redesign)
    if ($('#kode_barang_penawaran_v2').length) {
        $('#kode_barang_penawaran_v2').val(data.kode_barang);
        $('#nama_barang_penawaran_v2').val(data.nama_barang);
        $('#harga_satuan_penawaran_v2').val(data.harga_jual);
        $('#quantity_penawaran_v2').val(1);

        // Calculate total
        if (typeof hitungTotalPenawaranV2 === 'function') {
            hitungTotalPenawaranV2();
        }

        // Show penawaran form if collapsed
        var penawaranBody = document.getElementById('penawaran-form-body');
        if (penawaranBody && penawaranBody.style.display === 'none') {
            togglePenawaranForm();
        }
    }

    // Support untuk template lama
    if ($('#kode_barang_penawaran').length) {
        $('#kode_barang_penawaran').val(data.kode_barang);
        $('#nama_barang_penawaran').val(data.nama_barang);
        $('#harga_satuan_penawaran').val(data.harga_jual);
        $('#quantity_penawaran').val(1);

        if (typeof hitungTotalPenawaran === 'function') {
            hitungTotalPenawaran();
        }
    }

    // Visual feedback
    $('#kode_barang_penawaran_v2, #nama_barang_penawaran_v2, #kode_barang_penawaran, #nama_barang_penawaran')
        .css('background-color', '#fcf8e3');
    setTimeout(function() {
        $('#kode_barang_penawaran_v2, #nama_barang_penawaran_v2, #kode_barang_penawaran, #nama_barang_penawaran')
            .css('background-color', '');
    }, 1500);
}

$(document).ready(function() {
    $('#add_part_harga, #add_part_qty').on('input change', calcAddPartTotal);
});
</script>

</body>
</html>
