<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd = mysqli_query($koneksi,"SELECT nama_user, user_akses FROM tbuser WHERE id='$id_user'");
$tm_cari  = mysqli_fetch_array($cari_kd);
$_nama    = $tm_cari['nama_user'];

$kd_safe = mysqli_real_escape_string($koneksi, $kd_cabang);
$cari_cab = mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_safe'");
$tm_cab   = mysqli_fetch_array($cari_cab);
$tipe_cabang = $tm_cab['tipe_cabang'];
$is_pusat    = ($tipe_cabang=='1' || strtolower($tipe_cabang)=='pusat');

$no_order = isset($_GET['no']) ? mysqli_real_escape_string($koneksi, $_GET['no']) : '';
if(!$no_order){ header("location:pengadaan_antarcab.php"); exit; }

$qh = mysqli_query($koneksi,"SELECT h.*,
    COALESCE(h.jenis,'pull') AS jenis_order,
    ca.nama_cabang AS nama_asal,
    ct.nama_cabang AS nama_tujuan
    FROM tblorder_antarcab_header h
    LEFT JOIN tbcabang ca ON ca.kode_cabang=h.kd_cabang_asal
    LEFT JOIN tbcabang ct ON ct.kode_cabang=h.kd_cabang_tujuan
    WHERE h.no_order='$no_order' LIMIT 1");
if(!$qh || mysqli_num_rows($qh)==0){ header("location:pengadaan_antarcab.php"); exit; }
$hdr = mysqli_fetch_assoc($qh);
$jenis_order = $hdr['jenis_order'];

if(!$is_pusat){
    $ok = ($jenis_order=='push' && $hdr['kd_cabang_tujuan']==$kd_cabang)
       || ($jenis_order!='push' && $hdr['kd_cabang_asal']==$kd_cabang);
    if(!$ok){ header("location:pengadaan_antarcab.php"); exit; }
}

$qd = mysqli_query($koneksi,"SELECT d.*, i.namaitem, i.satuan FROM tblorder_antarcab_detail d
    LEFT JOIN tblitem i ON i.noitem=d.no_item
    WHERE d.no_order='$no_order' ORDER BY d.no_baris");
$details = [];
while($r = mysqli_fetch_assoc($qd)) $details[] = $r;

// Ambil nama perusahaan dari setting
$q_setting = mysqli_query($koneksi,"SELECT * FROM tbsetting LIMIT 1");
$setting = ($q_setting && mysqli_num_rows($q_setting)>0) ? mysqli_fetch_assoc($q_setting) : [];
$nama_perusahaan  = $setting['nama_perusahaan'] ?? 'FitMotor';
$alamat_perusahaan = $setting['alamat'] ?? '';
$telp_perusahaan  = $setting['telp'] ?? '';

if($jenis_order=='push'){
    $label_pengirim = $hdr['nama_asal'];
    $label_penerima = $hdr['nama_tujuan'];
} else {
    $label_pengirim = $hdr['nama_tujuan'];
    $label_penerima = $hdr['nama_asal'];
}
$tgl_doc = $hdr['tanggal_kirim'] ? date('d/m/Y',strtotime($hdr['tanggal_kirim'])) : date('d/m/Y',strtotime($hdr['tanggal_request']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<title>Nota AC — <?php echo htmlspecialchars($no_order); ?></title>
<style>
<?php include "_template/_nota_print_style.php"; ?>
.push-badge { background:#5bc0de; }
.pull-badge { background:#777; }
</style>
</head>
<body>

<div class="no-print" style="padding:10px;background:#eee;text-align:center;">
    <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;font-size:13px;">
        🖨️ Cetak / Print
    </button>
    &nbsp;&nbsp;
    <a href="pengadaan_antarcab_detail.php?no=<?php echo urlencode($no_order); ?>"
       style="padding:8px 20px;background:#fff;border:1px solid #ccc;text-decoration:none;border-radius:3px;">
        ← Kembali ke Detail
    </a>
</div>

<div class="container">

    <?php
        $judul_doc = 'Nota Pengiriman Antar Cabang';
        include "_template/_nota_print_header.php";
    ?>
    <div class="header-nota" style="border-bottom:none;padding-bottom:0;margin-bottom:15px;">
        <p>
            <span class="badge-type <?php echo $jenis_order=='push'?'push-badge':'pull-badge'; ?>">
                <?php echo $jenis_order=='push' ? 'PUSH — Inisiasi Pusat' : 'PULL — Request Cabang'; ?>
            </span>
        </p>
    </div>

    <div class="info-grid">
        <div class="info-col" style="padding-right:8px;">
            <table>
                <tr><td>No. Transaksi</td><td><strong><?php echo htmlspecialchars($no_order); ?></strong></td></tr>
                <tr><td>Tanggal</td><td><?php echo $tgl_doc; ?></td></tr>
                <tr><td>Status</td><td><?php echo ucfirst($hdr['status']); ?></td></tr>
                <?php if($hdr['catatan']): ?>
                <tr><td>Catatan</td><td><?php echo htmlspecialchars($hdr['catatan']); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="info-col" style="padding-left:8px;">
            <table>
                <tr><td>Pengirim</td><td><strong><?php echo htmlspecialchars($label_pengirim?:'—'); ?></strong></td></tr>
                <tr><td>Penerima</td><td><strong><?php echo htmlspecialchars($label_penerima?:'—'); ?></strong></td></tr>
                <tr><td>Dibuat Oleh</td><td><?php echo htmlspecialchars($hdr['user_request']); ?></td></tr>
                <?php if($hdr['user_proses'] && $hdr['user_proses']!=$hdr['user_request']): ?>
                <tr><td>Dikerjakan</td><td><?php echo htmlspecialchars($hdr['user_proses']); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <table class="detail">
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width:45px">Sat.</th>
                <th style="width:60px">Req</th>
                <th style="width:60px">Kirim</th>
                <th style="width:60px">Terima</th>
                <th style="width:90px;text-align:right">HPP (Rp)</th>
                <th style="width:100px;text-align:right">Subtotal (Rp)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($details as $i=>$d): ?>
        <tr>
            <td style="text-align:center"><?php echo $i+1; ?></td>
            <td><?php echo htmlspecialchars($d['no_item']); ?></td>
            <td><?php echo htmlspecialchars($d['namaitem']?:'—'); ?></td>
            <td style="text-align:center"><?php echo htmlspecialchars($d['satuan']?:'pcs'); ?></td>
            <td style="text-align:center"><?php echo $d['qty_request']; ?></td>
            <td style="text-align:center"><?php echo $d['qty_kirim']??0; ?></td>
            <td style="text-align:center"><?php echo $d['qty_terima']??0; ?></td>
            <td style="text-align:right"><?php echo number_format($d['harga_pokok'],0,',','.'); ?></td>
            <td style="text-align:right"><?php echo number_format($d['subtotal'],0,',','.'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" style="text-align:right">Total Nilai (Rp)</td>
                <td style="text-align:right"><?php echo number_format($hdr['total_nilai'],0,',','.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="ttd-row">
        <div class="ttd-col">
            <p><b>Dibuat / Pengirim</b><br/><?php echo htmlspecialchars($label_pengirim?:'—'); ?></p>
            <div class="ttd-box"></div>
            <p>(__________________)</p>
            <p style="margin-top:3px;">Tgl: _______ / _______ / _______</p>
        </div>
        <div class="ttd-col">
            <p><b>Ekspedisi / Pengantar</b><br/>&nbsp;</p>
            <div class="ttd-box"></div>
            <p>(__________________)</p>
            <p style="margin-top:3px;">Tgl: _______ / _______ / _______</p>
        </div>
        <div class="ttd-col">
            <p><b>Penerima</b><br/><?php echo htmlspecialchars($label_penerima?:'—'); ?></p>
            <div class="ttd-box"></div>
            <p>(__________________)</p>
            <p style="margin-top:3px;">Tgl: _______ / _______ / _______</p>
        </div>
    </div>

    <p style="margin-top:15px;font-size:10px;color:#aaa;text-align:center;">
        Dicetak: <?php echo date('d/m/Y H:i'); ?> oleh <?php echo htmlspecialchars($_nama); ?>
    </p>
</div>
</body>
</html>
