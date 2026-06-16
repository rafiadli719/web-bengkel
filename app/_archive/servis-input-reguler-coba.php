<?php
/**
 * REDESIGN: Servis Input Reguler - Complete UI Overhaul
 * Version: 2.0 (Test Page)
 *
 * File ini adalah halaman test untuk redesign UI/UX complete
 * dari servis-input-reguler.php
 */

session_start();

// Check session - sama dengan file asli
if(empty($_SESSION['_iduser'])) {
    // Untuk testing, redirect ke halaman login
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'] ?? 'CAB001';

include '../config/koneksi.php';

// Include helper functions
if(file_exists('_include_kategori_member.php')) {
    include_once '_include_kategori_member.php';
}

// Get parameters
$no_service = isset($_GET['snoserv']) ? mysqli_real_escape_string($koneksi, $_GET['snoserv']) : '';
// Fallback jika pakai parameter berbeda
if(empty($no_service) && isset($_GET['no_service'])) {
    $no_service = mysqli_real_escape_string($koneksi, $_GET['no_service']);
}
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'detail';

// Debug mode - tambahkan ?debug=1 di URL untuk melihat info debug
$debug = isset($_GET['debug']);

// Redirect if no service number
if(empty($no_service)) {
    if($debug) {
        echo "<h3>Error: No. Service tidak diberikan</h3>";
        echo "<p>Gunakan format URL: <code>servis-input-reguler-coba.php?snoserv=NOMOR_SERVICE</code></p>";
        echo "<p><a href='check_service.php'>Lihat daftar service</a></p>";
        exit;
    }
    echo "<script>alert('No. Service tidak ditemukan!'); window.location='servis-reguler.php';</script>";
    exit;
}

// Get service data - query sederhana dulu
$sql_query = "SELECT * FROM tblservice WHERE no_service = '$no_service'";
$sql_service = mysqli_query($koneksi, $sql_query);

if($debug) {
    echo "<div style='background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;'>";
    echo "<strong>Debug Info:</strong><br>";
    echo "No Service: <code>$no_service</code><br>";
    echo "Query: <code>$sql_query</code><br>";
    if(!$sql_service) {
        echo "Error: <span style='color:red'>".mysqli_error($koneksi)."</span><br>";
    } else {
        echo "Rows Found: ".mysqli_num_rows($sql_service)."<br>";
    }
    echo "</div>";
}

if(!$sql_service) {
    echo "<h3 style='color:red;'>Query Error!</h3>";
    echo "<p>".mysqli_error($koneksi)."</p>";
    exit;
}

if(mysqli_num_rows($sql_service) == 0) {
    if($debug) {
        echo "<h3>Data service tidak ditemukan di database</h3>";
        echo "<p><a href='check_service.php'>Lihat daftar service yang tersedia</a></p>";
        exit;
    }
    echo "<script>alert('Data service \"$no_service\" tidak ditemukan!'); window.location='servis-reguler.php';</script>";
    exit;
}

$service = mysqli_fetch_array($sql_service);

// Extract service data dari tabel tblservice
$tanggal = $service['tgl_service'] ?? $service['tanggal'] ?? date('Y-m-d');
$jam = $service['jam'] ?? date('H:i');
$kode_pelanggan = $service['kode_pelanggan'] ?? $service['no_pelanggan'] ?? '';
$no_polisi = $service['no_polisi'] ?? $service['nopol'] ?? '';
$status_servis = $service['status'] ?? $service['status_servis'] ?? 'proses';
$km_skr = $service['km_skr'] ?? 0;
$km_berikut = $service['km_berikut'] ?? 0;
$status_jemput = $service['status_jemput'] ?? '0';
$kd_cabang = $service['kode_cabang'] ?? $_SESSION['_cabang'] ?? 'CAB001';

// Get pelanggan data
$namapelanggan = '';
$kategori_pelanggan = '';
$potongan_pelanggan = 0;
if(!empty($kode_pelanggan)) {
    $sql_pel = mysqli_query($koneksi, "SELECT nama, kategori, potongan FROM tblpelanggan WHERE kode_pelanggan='$kode_pelanggan'");
    if($sql_pel && $pel = mysqli_fetch_array($sql_pel)) {
        $namapelanggan = $pel['nama'] ?? '';
        $kategori_pelanggan = $pel['kategori'] ?? '';
        $potongan_pelanggan = $pel['potongan'] ?? 0;
    }
}
$no_pelanggan = $kode_pelanggan;

// Get kendaraan data
$merek = '';
$jenis = '';
$warna = '';
$tahun_kendaraan = '';
$no_rangka = '';
$no_mesin = '';
if(!empty($no_polisi)) {
    $sql_kend = mysqli_query($koneksi, "SELECT * FROM tblkendaraan WHERE nopol='$no_polisi'");
    if($sql_kend && $kend = mysqli_fetch_array($sql_kend)) {
        $merek = $kend['merk'] ?? $kend['merek'] ?? '';
        $jenis = $kend['jenis'] ?? $kend['tipe'] ?? '';
        $warna = $kend['warna'] ?? '';
        $tahun_kendaraan = $kend['tahun'] ?? '';
        $no_rangka = $kend['no_rangka'] ?? '';
        $no_mesin = $kend['no_mesin'] ?? '';
    }
}

// Get totals
$total_barang = 0;
$total_service = 0;
$sql_barang = mysqli_query($koneksi, "SELECT SUM(total) as total FROM tblservis_barang WHERE no_service='$no_service'");
if($sql_barang) {
    $total_barang = mysqli_fetch_array($sql_barang)['total'] ?? 0;
}
$sql_jasa = mysqli_query($koneksi, "SELECT SUM(total) as total FROM tblservis_jasa WHERE no_service='$no_service'");
if($sql_jasa) {
    $total_service = mysqli_fetch_array($sql_jasa)['total'] ?? 0;
}
$tot = $total_barang + $total_service;
$discount_amount = 0;
$net = $tot;
$bayar = $service['bayar'] ?? 0;
$kembalian = $service['kembalian'] ?? 0;

// Variables for templates
$txtcariwo = $_POST['txtcariwo'] ?? '';
$txtnamawo = '';
if(!empty($txtcariwo)) {
    $cari_wo = mysqli_query($koneksi, "SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='".mysqli_real_escape_string($koneksi, $txtcariwo)."'");
    if($cari_wo && $row = mysqli_fetch_array($cari_wo)) {
        $txtnamawo = $row['nama_wo'];
    }
}

// Mekanik data
$kepala_mekanik1 = $service['kepala_mekanik_1'] ?? '';
$kepala_mekanik2 = $service['kepala_mekanik_2'] ?? '';
$mekanik1 = $service['mekanik_1'] ?? '';
$mekanik2 = $service['mekanik_2'] ?? '';
$mekanik3 = $service['mekanik_3'] ?? '';
$mekanik4 = $service['mekanik_4'] ?? '';
$admin1 = $service['admin_1'] ?? '';
$admin2 = $service['admin_2'] ?? '';
$persen_kepala1 = $service['persen_kepala_1'] ?? 0;
$persen_kepala2 = $service['persen_kepala_2'] ?? 0;
$persen_kerja1 = $service['persen_kerja_1'] ?? 0;
$persen_kerja2 = $service['persen_kerja_2'] ?? 0;
$persen_kerja3 = $service['persen_kerja_3'] ?? 0;
$persen_kerja4 = $service['persen_kerja_4'] ?? 0;
$persen_admin1 = $service['persen_admin_1'] ?? 0;
$persen_admin2 = $service['persen_admin_2'] ?? 0;
$metode_pembayaran = $service['metode_pembayaran'] ?? 'Tunai';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Service Reguler - <?= htmlspecialchars($no_service) ?> | FIT MOTOR</title>

    <!-- Bootstrap & jQuery -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Redesign Styles -->
    <?php include '_template/_redesign_styles.php'; ?>

    <style>
    body {
        background: #f0f2f5;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .rd-page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .rd-page-header {
        background: white;
        border-radius: var(--rd-radius);
        padding: 20px 24px;
        margin-bottom: 20px;
        box-shadow: var(--rd-shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .rd-page-title {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .rd-page-title h1 {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        color: var(--rd-text);
    }

    .rd-page-title .rd-badge {
        font-size: 12px;
    }

    .rd-service-info {
        display: flex;
        align-items: center;
        gap: 24px;
        font-size: 14px;
    }

    .rd-service-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .rd-service-info-item i {
        color: var(--rd-primary);
    }

    /* Main Tab Navigation */
    .rd-main-tabs {
        background: white;
        border-radius: var(--rd-radius);
        box-shadow: var(--rd-shadow);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .rd-main-tabs-nav {
        display: flex;
        border-bottom: 1px solid var(--rd-border);
        overflow-x: auto;
    }

    .rd-main-tab-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 16px 24px;
        color: var(--rd-text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
        transition: all 0.2s ease;
    }

    .rd-main-tab-link:hover {
        color: var(--rd-primary);
        background: var(--rd-bg-light);
        text-decoration: none;
    }

    .rd-main-tab-link.active {
        color: var(--rd-primary);
        border-bottom-color: var(--rd-primary);
        background: rgba(74, 144, 217, 0.05);
    }

    .rd-main-tab-link i {
        font-size: 16px;
    }

    .rd-main-tab-link .rd-badge {
        font-size: 10px;
        padding: 2px 6px;
    }

    .rd-tab-content {
        display: none;
        padding: 24px;
    }

    .rd-tab-content.active {
        display: block;
    }

    /* Quick Summary Bar */
    .rd-quick-summary {
        background: linear-gradient(135deg, var(--rd-primary) 0%, #3A7BC8 100%);
        color: white;
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .rd-quick-summary-item {
        text-align: center;
    }

    .rd-quick-summary-item .value {
        font-size: 20px;
        font-weight: 700;
    }

    .rd-quick-summary-item .label {
        font-size: 11px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    </style>
</head>
<body>
    <div class="rd-page-container">
        <!-- Page Header -->
        <div class="rd-page-header">
            <div class="rd-page-title">
                <a href="servis-reguler.php" class="rd-btn outline-primary">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div>
                    <h1><i class="fa fa-tools"></i> Input Service Reguler</h1>
                    <small class="rd-text-muted">Kelola detail service kendaraan</small>
                </div>
            </div>
            <div class="rd-service-info">
                <div class="rd-service-info-item">
                    <i class="fa fa-hashtag"></i>
                    <strong><?= htmlspecialchars($no_service) ?></strong>
                </div>
                <div class="rd-service-info-item">
                    <i class="fa fa-motorcycle"></i>
                    <strong><?= htmlspecialchars($no_polisi) ?></strong>
                </div>
                <div class="rd-service-info-item">
                    <i class="fa fa-calendar"></i>
                    <?= date('d M Y', strtotime($tanggal)) ?>
                </div>
                <span class="rd-badge solid-<?= $status_servis == 'bayar' ? 'success' : ($status_servis == 'proses' ? 'warning' : 'info') ?>">
                    <?= strtoupper($status_servis) ?>
                </span>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="" enctype="multipart/form-data" id="formServisReguler">
            <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">

            <!-- Main Tabs Container -->
            <div class="rd-main-tabs">
                <!-- Quick Summary -->
                <div class="rd-quick-summary">
                    <?php
                    $count_keluhan = 0;
                    $sql_kel = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tbservis_keluhan_status WHERE no_service='$no_service'");
                    if($sql_kel) $count_keluhan = mysqli_fetch_array($sql_kel)['c'] ?? 0;

                    $count_wo = 0;
                    $sql_wo = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tbservis_workorder WHERE no_service='$no_service'");
                    if($sql_wo) $count_wo = mysqli_fetch_array($sql_wo)['c'] ?? 0;

                    $count_barang = 0;
                    $sql_brg = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_barang WHERE no_service='$no_service'");
                    if($sql_brg) $count_barang = mysqli_fetch_array($sql_brg)['c'] ?? 0;

                    $count_jasa = 0;
                    $sql_jasa_c = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_jasa WHERE no_service='$no_service'");
                    if($sql_jasa_c) $count_jasa = mysqli_fetch_array($sql_jasa_c)['c'] ?? 0;
                    ?>
                    <div class="rd-quick-summary-item">
                        <div class="value"><?= $count_keluhan + $count_wo ?></div>
                        <div class="label">SPK</div>
                    </div>
                    <div class="rd-quick-summary-item">
                        <div class="value"><?= $count_barang ?></div>
                        <div class="label">Barang</div>
                    </div>
                    <div class="rd-quick-summary-item">
                        <div class="value"><?= $count_jasa ?></div>
                        <div class="label">Jasa</div>
                    </div>
                    <div class="rd-quick-summary-item">
                        <div class="value">Rp <?= number_format($tot, 0, ',', '.') ?></div>
                        <div class="label">Total</div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="rd-main-tabs-nav">
                    <a href="#tab-detail" class="rd-main-tab-link <?= $active_tab == 'detail' ? 'active' : '' ?>" data-tab="detail">
                        <i class="fa fa-info-circle"></i> Detail Service
                    </a>
                    <a href="#tab-workorder" class="rd-main-tab-link <?= $active_tab == 'workorder' ? 'active' : '' ?>" data-tab="workorder">
                        <i class="fa fa-clipboard-list"></i> Work Order
                        <span class="rd-badge info"><?= $count_keluhan + $count_wo ?></span>
                    </a>
                    <a href="#tab-temuan" class="rd-main-tab-link <?= $active_tab == 'temuan' ? 'active' : '' ?>" data-tab="temuan">
                        <i class="fa fa-search-plus"></i> Temuan & Penawaran
                    </a>
                    <a href="#tab-barang" class="rd-main-tab-link <?= $active_tab == 'barang' ? 'active' : '' ?>" data-tab="barang">
                        <i class="fa fa-box"></i> Item Barang
                        <span class="rd-badge purple"><?= $count_barang ?></span>
                    </a>
                    <a href="#tab-jasa" class="rd-main-tab-link <?= $active_tab == 'jasa' ? 'active' : '' ?>" data-tab="jasa">
                        <i class="fa fa-cogs"></i> Item Jasa
                        <span class="rd-badge success"><?= $count_jasa ?></span>
                    </a>
                    <a href="#tab-actions" class="rd-main-tab-link <?= $active_tab == 'actions' ? 'active' : '' ?>" data-tab="actions">
                        <i class="fa fa-money-bill-wave"></i> Pembayaran
                    </a>
                </div>

                <!-- Tab Contents -->
                <div id="tab-detail" class="rd-tab-content <?= $active_tab == 'detail' ? 'active' : '' ?>">
                    <?php include '_template/tab-detail-service-coba.php'; ?>
                </div>

                <div id="tab-workorder" class="rd-tab-content <?= $active_tab == 'workorder' ? 'active' : '' ?>">
                    <?php include '_template/tab-workorder-coba.php'; ?>
                </div>

                <div id="tab-temuan" class="rd-tab-content <?= $active_tab == 'temuan' ? 'active' : '' ?>">
                    <?php include '_template/tab-temuan-penawaran-content-coba.php'; ?>
                </div>

                <div id="tab-barang" class="rd-tab-content <?= $active_tab == 'barang' ? 'active' : '' ?>">
                    <?php include '_template/tab-item-barang-coba.php'; ?>
                </div>

                <div id="tab-jasa" class="rd-tab-content <?= $active_tab == 'jasa' ? 'active' : '' ?>">
                    <?php include '_template/tab-item-jasa-coba.php'; ?>
                </div>

                <div id="tab-actions" class="rd-tab-content <?= $active_tab == 'actions' ? 'active' : '' ?>">
                    <?php include '_template/tab-actions-coba.php'; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Modals -->
    <?php
    // Include modals
    if(file_exists('_template/modal-search-keluhan.php')) {
        include '_template/modal-search-keluhan.php';
    }
    if(file_exists('_template/modal-input-barang-custom.php')) {
        include '_template/modal-input-barang-custom.php';
    }
    if(file_exists('_template/_modal_riwayat_kendaraan.php')) {
        include '_template/_modal_riwayat_kendaraan.php';
    }
    if(file_exists('_template/_modal_update_status_keluhan.php')) {
        include '_template/_modal_update_status_keluhan.php';
    }
    if(file_exists('_template/_modal_cancel_service.php')) {
        include '_template/_modal_cancel_service.php';
    }
    if(file_exists('modal-tambah-keluhan-baru.php')) {
        include 'modal-tambah-keluhan-baru.php';
    }
    ?>

    <!-- Modal Update Status Work Order -->
    <div class="modal fade" id="modalUpdateStatusWO" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit"></i> Update Status Work Order</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="wo_id" id="wo_id">
                        <div class="form-group">
                            <label>Work Order:</label>
                            <input type="text" class="form-control" id="wo_text" readonly>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status_wo" id="status_wo" class="form-control">
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="tidak_selesai">Tidak Selesai</option>
                            </select>
                        </div>
                        <div class="form-group" id="keterangan_wo_group" style="display:none;">
                            <label>Keterangan:</label>
                            <textarea name="keterangan_wo" id="keterangan_wo" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btnupdatewo" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Update Status Keluhan -->
    <div class="modal fade" id="modalUpdateStatusKeluhan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit"></i> Update Status Keluhan</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="keluhan_id" id="keluhan_id">
                        <div class="form-group">
                            <label>Keluhan:</label>
                            <input type="text" class="form-control" id="keluhan_text" readonly>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status_keluhan" id="status_keluhan" class="form-control">
                                <option value="datang">Datang</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="tidak_selesai">Tidak Selesai</option>
                            </select>
                        </div>
                        <div class="form-group" id="keterangan_keluhan_group" style="display:none;">
                            <label>Keterangan:</label>
                            <textarea name="keterangan_keluhan" id="keterangan_keluhan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btnupdatekeluhan" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Tab Navigation
    $(document).ready(function() {
        // Tab click handler
        $('.rd-main-tab-link').on('click', function(e) {
            e.preventDefault();

            var tabId = $(this).attr('href');
            var tabName = $(this).data('tab');

            // Update active states
            $('.rd-main-tab-link').removeClass('active');
            $(this).addClass('active');

            $('.rd-tab-content').removeClass('active');
            $(tabId).addClass('active');

            // Update URL without reload
            var newUrl = window.location.pathname + '?snoserv=<?= $no_service ?>&tab=' + tabName;
            history.pushState(null, '', newUrl);
        });

        // Status keluhan change handler
        $('#status_keluhan').on('change', function() {
            if($(this).val() == 'tidak_selesai') {
                $('#keterangan_keluhan_group').slideDown();
            } else {
                $('#keterangan_keluhan_group').slideUp();
            }
        });

        // Status WO change handler
        $('#status_wo').on('change', function() {
            if($(this).val() == 'tidak_selesai') {
                $('#keterangan_wo_group').slideDown();
            } else {
                $('#keterangan_wo_group').slideUp();
            }
        });

        // Update status keluhan modal handler
        $('#update_keluhan_id').length && $('#modalUpdateStatusKeluhan').on('shown.bs.modal', function() {
            // Handle if needed
        });
    });

    // Global helper functions - tersedia sebelum tab di-load

    // Collapsible card handler
    function toggleCardBody(header) {
        var body = header.nextElementSibling;
        header.classList.toggle('collapsed');
        if(body.style.display === 'none') {
            body.style.display = 'block';
        } else {
            body.style.display = 'none';
        }
    }

    // Show statistik pelanggan modal
    function showStatistikPelanggan() {
        if(typeof jQuery !== 'undefined' && jQuery('#modalStatistikPelanggan').length) {
            jQuery('#modalStatistikPelanggan').modal('show');
        } else {
            alert('Modal statistik pelanggan belum tersedia di halaman redesign ini.');
        }
    }

    // Show riwayat kendaraan modal
    function showRiwayatKendaraan() {
        if(typeof jQuery !== 'undefined' && jQuery('#modalRiwayatKendaraan').length) {
            jQuery('#modalRiwayatKendaraan').modal('show');
        } else {
            alert('Modal riwayat kendaraan tidak tersedia');
        }
    }

    // Update status keluhan
    function updateStatusKeluhan(id) {
        if(typeof jQuery !== 'undefined') {
            if(jQuery('#modalUpdateStatusKeluhan').length) {
                jQuery('#modalUpdateStatusKeluhan').modal('show');
                jQuery('#keluhan_id').val(id);
            } else {
                alert('Modal update status keluhan tidak tersedia');
            }
        }
    }

    // Update status work order
    function updateStatusWO(id, nama) {
        if(typeof jQuery !== 'undefined') {
            if(jQuery('#modalUpdateStatusWO').length) {
                jQuery('#modalUpdateStatusWO').modal('show');
                jQuery('#wo_id').val(id);
                jQuery('#wo_text').val(nama || '');
            } else {
                alert('Modal update status work order tidak tersedia');
            }
        }
    }

    // Show notification
    function showNotifV2(message, type) {
        type = type || 'info';
        var bgColor = type === 'success' ? 'var(--rd-success)' :
                      type === 'warning' ? 'var(--rd-warning)' :
                      type === 'danger' ? 'var(--rd-danger)' : 'var(--rd-info)';

        var notif = $('<div class="fm-notif">' + message + '</div>');
        notif.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            background: bgColor,
            color: 'white',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            zIndex: 9999,
            animation: 'rd-fadeIn 0.3s ease'
        });

        $('body').append(notif);

        setTimeout(function() {
            notif.fadeOut(300, function() {
                notif.remove();
            });
        }, 2000);
    }
    </script>
</body>
</html>
