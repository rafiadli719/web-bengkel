<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php"); exit;
}

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$noretur = isset($_GET['noretur']) ? mysqli_real_escape_string($koneksi, $_GET['noretur']) : '';
if($noretur == '') { header("location:retur_pembelian.php"); exit; }

$hdr = mysqli_query($koneksi, "SELECT h.*, DATE_FORMAT(h.tanggal,'%d/%m/%Y') AS tgl_fmt,
                                      s.namasupplier, ph.no_supplier
                               FROM tblretur_pembelian_header h
                               LEFT JOIN tblpembelian_header ph ON ph.notransaksi=h.nopembelian
                               LEFT JOIN tblsupplier s ON s.nosupplier=ph.no_supplier
                               WHERE h.noretur='$noretur' AND h.kd_cabang='$kd_cabang'");
if(mysqli_num_rows($hdr) == 0) { header("location:retur_pembelian.php"); exit; }
$h = mysqli_fetch_assoc($hdr);

$det = mysqli_query($koneksi, "SELECT d.*, COALESCE(i.namaitem, d.no_item) AS namaitem,
                                      j.jenis_penggantian
                               FROM tblretur_pembelian_detail d
                               LEFT JOIN tblitem i ON i.noitem=d.no_item
                               LEFT JOIN tbjenis_penggantian_retur j ON j.id=d.jenis_penggantian
                               WHERE d.no_retur='$noretur'
                               ORDER BY d.id");

$cari_cab = mysqli_query($koneksi, "SELECT * FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$cab = mysqli_fetch_assoc($cari_cab);

$q_setting = mysqli_query($koneksi, "SELECT * FROM tbsetting LIMIT 1");
$setting = ($q_setting && mysqli_num_rows($q_setting) > 0) ? mysqli_fetch_assoc($q_setting) : [];
$nama_perusahaan   = $setting['nama_perusahaan'] ?? ($cab['nama_cabang'] ?? 'BENGKEL');
$alamat_perusahaan = $setting['alamat'] ?? ($cab['alamat_cabang'] ?? '');
$telp_perusahaan   = $setting['notlp'] ?? '';
$judul_doc         = 'BUKTI RETUR PEMBELIAN';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Cetak Retur Pembelian - <?php echo htmlspecialchars($noretur); ?></title>
    <style>
<?php include "_template/_nota_print_style.php"; ?>
        table.info { width: 100%; margin-bottom: 10px; }
        table.info td { padding: 2px 4px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;">
        <button onclick="window.print()">&#128438; Print</button>
        <button onclick="window.close()">Tutup</button>
    </div>
    <div class="container">
    <?php include "_template/_nota_print_header.php"; ?>

    <table class="info">
        <tr>
            <td width="20%">No. Retur</td><td width="2%">:</td><td width="28%"><strong><?php echo htmlspecialchars($h['noretur']); ?></strong></td>
            <td width="20%">Tanggal</td><td width="2%">:</td><td><?php echo htmlspecialchars($h['tgl_fmt']); ?></td>
        </tr>
        <tr>
            <td>No. Pembelian</td><td>:</td><td><?php echo htmlspecialchars($h['nopembelian']); ?></td>
            <td>Supplier</td><td>:</td><td><?php echo htmlspecialchars($h['namasupplier']); ?></td>
        </tr>
        <tr>
            <td>Keterangan</td><td>:</td><td colspan="3"><?php echo htmlspecialchars($h['note']); ?></td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Kode</th>
                <th width="28%">Nama Item</th>
                <th width="7%">Qty</th>
                <th width="14%">Harga Pokok</th>
                <th width="14%">Subtotal</th>
                <th width="14%">Alasan</th>
                <th width="16%">Penggantian</th>
            </tr>
        </thead>
        <tbody>
        <?php $no=1; $grand=0; while($d=mysqli_fetch_assoc($det)): $grand+=$d['total']; ?>
        <tr>
            <td class="text-center"><?php echo $no++; ?></td>
            <td><?php echo htmlspecialchars($d['no_item']); ?></td>
            <td><?php echo htmlspecialchars($d['namaitem']); ?></td>
            <td class="text-center"><?php echo $d['quantity']; ?></td>
            <td class="text-right"><?php echo number_format($d['harga_pokok'],0,',','.'); ?></td>
            <td class="text-right"><?php echo number_format($d['total'],0,',','.'); ?></td>
            <td><?php echo htmlspecialchars($d['alasan_retur']); ?></td>
            <td><?php echo htmlspecialchars($d['jenis_penggantian']); ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="5" class="text-right"><strong>Total Nilai Retur</strong></td>
            <td class="text-right"><strong><?php echo number_format($grand,0,',','.'); ?></strong></td>
            <td colspan="2"></td>
        </tr>
        </tbody>
    </table>

    <?php
        $ttd_cols = [
            ['label' => 'Dibuat Oleh,', 'nama' => $h['user']],
            ['label' => 'Diketahui,',   'nama' => '______________'],
            ['label' => 'Supplier,',    'nama' => '______________'],
        ];
        include "_template/_nota_print_ttd.php";
    ?>
    </div>
</body>
</html>
