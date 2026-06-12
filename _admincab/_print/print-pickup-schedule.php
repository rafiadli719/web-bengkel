<?php
session_start();

if (empty($_SESSION['_iduser'])) {
    header("Location: ../../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'] ?? '';

require_once "../../config/koneksi.php";

$no_service = $_GET['snoserv'] ?? ($_GET['no_service'] ?? '');
$no_service = trim($no_service);

if ($no_service === '') {
    echo "No service tidak ditemukan.";
    exit;
}

$setting = mysqli_query($koneksi, "SELECT nama_perusahaan, alamat, notlp, file_logo FROM tbsetting LIMIT 1");
$setting_data = $setting ? mysqli_fetch_assoc($setting) : null;
$nama_perusahaan = $setting_data['nama_perusahaan'] ?? '';
$alamat_perusahaan = $setting_data['alamat'] ?? '';
$telp_perusahaan = $setting_data['notlp'] ?? '';
$file_logo = $setting_data['file_logo'] ?? '';

$cabang_data = null;
if ($kd_cabang !== '') {
    $stmt = mysqli_prepare($koneksi, "SELECT nama_cabang, alamat_cabang, lat_cabang, long_cabang FROM tbcabang WHERE kode_cabang = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $kd_cabang);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $cabang_data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }
}

$nama_cabang = $cabang_data['nama_cabang'] ?? '';
$alamat_cabang = $cabang_data['alamat_cabang'] ?? '';

$service_data = null;
$stmt = mysqli_prepare($koneksi, "SELECT no_service, DATE_FORMAT(tanggal, '%Y-%m-%d') AS tanggal, jam, no_pelanggan, no_polisi, keterangan, keterangan_jemput, foto_patokan, kd_cabang FROM tblservice WHERE no_service = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $no_service);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $service_data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

$no_pelanggan = $_GET['nopelanggan'] ?? '';
$no_polisi = $_GET['nopol'] ?? '';
$tanggal_jemput = $_GET['tanggal'] ?? '';
$jam_jemput = $_GET['jam'] ?? '';
$keterangan_jemput = $_GET['keterangan'] ?? '';
$google_maps_link = $_GET['gmaps'] ?? '';
$jarak_km = $_GET['jarak'] ?? '';
$tarif = $_GET['tarif'] ?? '';
$kondisi_motor = $_GET['kondisi'] ?? '';

if ($service_data) {
    $no_pelanggan = $service_data['no_pelanggan'] ?? $no_pelanggan;
    $no_polisi = $service_data['no_polisi'] ?? $no_polisi;
    $tanggal_jemput = $service_data['tanggal'] ?? $tanggal_jemput;
    $jam_jemput = $service_data['jam'] ?? $jam_jemput;
    $keterangan_jemput = ($service_data['keterangan_jemput'] ?? '') ?: (($service_data['keterangan'] ?? '') ?: $keterangan_jemput);
}

$foto_tampak_rumah = '';
if ($service_data && !empty($service_data['foto_patokan'])) {
    $foto_tampak_rumah = $service_data['foto_patokan'];
}

$nama_pelanggan = '';
$alamat_pelanggan = '';
$telepon_pelanggan = '';
$link_gmaps_pelanggan = '';

if ($no_pelanggan !== '') {
    $stmt = mysqli_prepare($koneksi, "SELECT namapelanggan, alamat, telephone, link_gmaps, klat, klong, patokan, foto_tampak_rumah FROM tblpelanggan WHERE nopelanggan = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $no_pelanggan);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $pel_data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($pel_data) {
            $nama_pelanggan = $pel_data['namapelanggan'] ?? '';
            $alamat_pelanggan = $pel_data['alamat'] ?? '';
            $telepon_pelanggan = $pel_data['telephone'] ?? '';
            $link_gmaps_pelanggan = $pel_data['link_gmaps'] ?? '';

            if ($google_maps_link === '' && $link_gmaps_pelanggan !== '') {
                $google_maps_link = $link_gmaps_pelanggan;
            }

            if ($google_maps_link === '' && !empty($pel_data['klat']) && !empty($pel_data['klong'])) {
                $google_maps_link = 'https://www.google.com/maps?q=' . $pel_data['klat'] . ',' . $pel_data['klong'];
            }

            if ($foto_tampak_rumah === '') {
                $foto_tampak_rumah = ($pel_data['foto_tampak_rumah'] ?? '') ?: ($pel_data['patokan'] ?? '');
            }
        }
    }
}

$kendaraan = null;
if ($no_polisi !== '') {
    $no_polisi_esc = mysqli_real_escape_string($koneksi, $no_polisi);
    $kendaraan_q = mysqli_query($koneksi, "SELECT pemilik, jenis, merek, warna, no_rangka, no_mesin FROM view_cari_kendaraan WHERE nopolisi='$no_polisi_esc'");
    $kendaraan = $kendaraan_q ? mysqli_fetch_assoc($kendaraan_q) : null;
}

$nama_pemilik = $kendaraan['pemilik'] ?? '';
$jenis_motor = $kendaraan['jenis'] ?? '';
$merek_motor = $kendaraan['merek'] ?? '';
$warna_motor = $kendaraan['warna'] ?? '';
$no_rangka = $kendaraan['no_rangka'] ?? '';
$no_mesin = $kendaraan['no_mesin'] ?? '';

$fmt_tanggal = '';
if ($tanggal_jemput !== '') {
    $ts = strtotime($tanggal_jemput);
    if ($ts) {
        $fmt_tanggal = date('d/m/Y', $ts);
    }
}

$now_print = date('d/m/Y H:i');

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function normalize_rel_path($path) {
    $path = trim((string)$path);
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^(\.\./)+#', '', $path);
    $path = ltrim($path, '/');
    return $path;
}

$tarif_num = is_numeric($tarif) ? (int)$tarif : 0;
$jarak_num = is_numeric($jarak_km) ? (float)$jarak_km : 0.0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo h($nama_perusahaan); ?> - SPPM</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <style>
        body { background: #fff; }
        .paper { max-width: 900px; margin: 10px auto; padding: 18px; }
        .doc-title { font-size: 18px; font-weight: 700; margin: 0; }
        .doc-subtitle { margin: 0; color: #555; }
        .meta { font-size: 12px; color: #555; }
        .section-title { font-weight: 700; margin-top: 12px; }
        .table td, .table th { padding: 6px 8px; }
        .sign { margin-top: 24px; }
        .sign-box { height: 90px; border: 1px solid #ddd; }
        .logo-img { height: 34px; width: auto; vertical-align: middle; }
        .foto-rumah { max-width: 260px; border: 1px solid #ddd; padding: 4px; border-radius: 4px; }
        @media print {
            .no-print { display: none !important; }
            .paper { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
<div class="paper">
    <div class="row">
        <div class="col-xs-8">
            <div style="display:flex; gap:10px; align-items:center;">
                <div style="width:42px;">
                    <?php
                        $logo_rel = normalize_rel_path($file_logo);
                        $logo_fs = $logo_rel !== '' ? realpath(__DIR__ . '/../../' . $logo_rel) : false;
                        if ($logo_rel !== '' && $logo_fs && file_exists($logo_fs)) {
                            echo '<img src="../../' . h($logo_rel) . '" class="logo-img" alt="' . h($nama_perusahaan) . '">';
                        } else {
                            echo '<i class="fa fa-leaf"></i>';
                        }
                    ?>
                </div>
                <div>
                    <div class="doc-title"><?php echo h($nama_perusahaan); ?></div>
                    <div class="doc-subtitle"><?php echo h($alamat_perusahaan); ?></div>
                    <div class="meta"><?php echo h($telp_perusahaan); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xs-4 text-right">
            <div class="meta">Dicetak: <?php echo h($now_print); ?></div>
            <div class="meta">User: <?php echo h($_SESSION['username'] ?? ''); ?></div>
            <div class="meta">Cabang: <?php echo h($nama_cabang); ?></div>
        </div>
    </div>

    <hr style="margin: 10px 0;" />

    <div class="text-center" style="margin-bottom: 10px;">
        <div class="doc-title">SURAT PERINTAH PENGAMBILAN MOTOR (SPPM)</div>
        <div class="meta">No. Dokumen: <?php echo h($no_service); ?></div>
    </div>

    <table class="table table-bordered">
        <tr>
            <td width="25%"><strong>No. Service</strong></td>
            <td width="25%"><?php echo h($no_service); ?></td>
            <td width="25%"><strong>Tanggal Jemput</strong></td>
            <td width="25%"><?php echo h($fmt_tanggal); ?></td>
        </tr>
        <tr>
            <td><strong>Jam Jemput</strong></td>
            <td><?php echo h($jam_jemput); ?></td>
            <td><strong>No. Polisi</strong></td>
            <td><?php echo h($no_polisi); ?></td>
        </tr>
    </table>

    <div class="section-title">Data Pelanggan</div>
    <table class="table table-bordered">
        <tr>
            <td width="25%"><strong>Kode Pelanggan</strong></td>
            <td width="75%"><?php echo h($no_pelanggan); ?></td>
        </tr>
        <tr>
            <td><strong>Nama</strong></td>
            <td><?php echo h($nama_pelanggan); ?></td>
        </tr>
        <tr>
            <td><strong>Telepon</strong></td>
            <td><?php echo h($telepon_pelanggan); ?></td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td><?php echo h($alamat_pelanggan); ?></td>
        </tr>
        <tr>
            <td><strong>Link Maps</strong></td>
            <td>
                <?php if (trim($google_maps_link) !== ''): ?>
                    <span><?php echo h($google_maps_link); ?></span>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td><strong>Foto Tampak Rumah</strong></td>
            <td>
                <?php if (trim($foto_tampak_rumah) !== ''): ?>
                    <?php $foto_rel = normalize_rel_path($foto_tampak_rumah); ?>
                    <img src="../../<?php echo h($foto_rel); ?>" class="foto-rumah" alt="Foto Tampak Rumah">
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="section-title">Data Kendaraan</div>
    <table class="table table-bordered">
        <tr>
            <td width="25%"><strong>Pemilik</strong></td>
            <td width="25%"><?php echo h($nama_pemilik); ?></td>
            <td width="25%"><strong>Merek/Jenis</strong></td>
            <td width="25%"><?php echo h(trim($merek_motor . ' ' . $jenis_motor)); ?></td>
        </tr>
        <tr>
            <td><strong>Warna</strong></td>
            <td><?php echo h($warna_motor); ?></td>
            <td><strong>No. Rangka</strong></td>
            <td><?php echo h($no_rangka); ?></td>
        </tr>
        <tr>
            <td><strong>No. Mesin</strong></td>
            <td colspan="3"><?php echo h($no_mesin); ?></td>
        </tr>
    </table>

    <div class="section-title">Detail Penjemputan</div>
    <table class="table table-bordered">
        <tr>
            <td width="25%"><strong>Kondisi Motor</strong></td>
            <td width="25%"><?php echo h($kondisi_motor); ?></td>
            <td width="25%"><strong>Jarak (KM)</strong></td>
            <td width="25%"><?php echo $jarak_num > 0 ? h(number_format($jarak_num, 1, ',', '.')) : '-'; ?></td>
        </tr>
        <tr>
            <td><strong>Estimasi Tarif</strong></td>
            <td colspan="3"><?php echo $tarif_num > 0 ? 'Rp ' . h(number_format($tarif_num, 0, ',', '.')) : '-'; ?></td>
        </tr>
        <tr>
            <td><strong>Keterangan</strong></td>
            <td colspan="3"><?php echo h($keterangan_jemput); ?></td>
        </tr>
    </table>

    <div class="section-title">Checklist Pengambilan</div>
    <table class="table table-bordered">
        <tr>
            <td width="50%">
                <div>[ ] Konfirmasi jadwal dengan pelanggan</div>
                <div>[ ] Identifikasi kondisi motor</div>
                <div>[ ] Catat keluhan pelanggan</div>
            </td>
            <td width="50%">
                <div>[ ] Ambil foto kondisi motor</div>
                <div>[ ] Pastikan barang bawaan pelanggan dicatat</div>
                <div>[ ] Serahkan bukti pengambilan</div>
            </td>
        </tr>
    </table>

    <div class="row sign">
        <div class="col-xs-4 text-center">
            <div class="meta">Petugas</div>
            <div class="sign-box"></div>
            <div class="meta">(..............................)</div>
        </div>
        <div class="col-xs-4 text-center">
            <div class="meta">Pelanggan</div>
            <div class="sign-box"></div>
            <div class="meta">(..............................)</div>
        </div>
        <div class="col-xs-4 text-center">
            <div class="meta">Admin</div>
            <div class="sign-box"></div>
            <div class="meta">(..............................)</div>
        </div>
    </div>

    <div class="no-print" style="margin-top: 15px;">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        <button class="btn btn-default" onclick="window.close()">Tutup</button>
    </div>
</div>
</body>
</html>
