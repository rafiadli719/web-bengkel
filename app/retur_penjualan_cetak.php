<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$noretur = isset($_GET['noretur']) ? mysqli_real_escape_string($koneksi, $_GET['noretur']) : '';
if($noretur == '') { header("location:retur_penjualan.php"); exit; }

$hdr = mysqli_query($koneksi, "SELECT h.*, DATE_FORMAT(h.tanggal,'%d/%m/%Y') AS tgl_fmt,
                                      p.namapelanggan, ph.no_pelanggan
                               FROM tblretur_penjualan_header h
                               LEFT JOIN tblpenjualan_header ph ON ph.notransaksi=h.nopembelian
                               LEFT JOIN tblpelanggan p ON p.nopelanggan=ph.no_pelanggan
                               WHERE h.noretur='$noretur' AND h.kd_cabang='$kd_cabang'");
if(mysqli_num_rows($hdr) == 0) { header("location:retur_penjualan.php"); exit; }
$h = mysqli_fetch_assoc($hdr);

$det = mysqli_query($koneksi, "SELECT d.*, COALESCE(i.namaitem, d.no_item) AS namaitem,
                                      j.jenis_penggantian
                               FROM tblretur_penjualan_detail d
                               LEFT JOIN tblitem i ON i.noitem=d.no_item
                               LEFT JOIN tbjenis_penggantian_retur j ON j.id=d.jenis_penggantian
                               WHERE d.no_retur='$noretur'
                               ORDER BY d.id");

$cari_cab = mysqli_query($koneksi, "SELECT * FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$cab = mysqli_fetch_assoc($cari_cab);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Cetak Retur Penjualan - <?php echo htmlspecialchars($noretur); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 10px; }
        .header-perusahaan { text-align: center; margin-bottom: 10px; }
        .header-perusahaan h2 { margin: 0; font-size: 16px; }
        .header-perusahaan p { margin: 2px 0; font-size: 11px; }
        .judul-doc { text-align: center; margin: 10px 0; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 4px 0; }
        .judul-doc h3 { margin: 0; font-size: 14px; }
        table.info { width: 100%; margin-bottom: 10px; }
        table.info td { padding: 2px 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.items th { border: 1px solid #000; padding: 4px; background: #f0f0f0; text-align: center; }
        table.items td { border: 1px solid #000; padding: 4px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .ttd-area { margin-top: 40px; display: flex; justify-content: space-between; }
        .ttd-box { text-align: center; width: 30%; }
        .ttd-box .nama { margin-top: 50px; border-top: 1px solid #000; padding-top: 4px; }
        @media print { .no-print { display: none; } body { margin: 5px; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;">
        <button onclick="window.print()">&#128438; Print</button>
        <button onclick="window.close()">Tutup</button>
    </div>
    <div class="header-perusahaan">
        <h2><?php echo htmlspecialchars($cab['nama_cabang'] ?? 'BENGKEL'); ?></h2>
        <p><?php echo htmlspecialchars($cab['alamat_cabang'] ?? ''); ?></p>
    </div>
    <div class="judul-doc"><h3>BUKTI RETUR PENJUALAN</h3></div>
    <table class="info">
        <tr>
            <td width="20%">No. Retur</td><td width="2%">:</td><td width="28%"><strong><?php echo htmlspecialchars($h['noretur']); ?></strong></td>
            <td width="20%">Tanggal</td><td width="2%">:</td><td><?php echo htmlspecialchars($h['tgl_fmt']); ?></td>
        </tr>
        <tr>
            <td>No. Penjualan</td><td>:</td><td><?php echo htmlspecialchars($h['nopembelian']); ?></td>
            <td>Pelanggan</td><td>:</td><td><?php echo htmlspecialchars($h['namapelanggan']); ?></td>
        </tr>
        <tr><td>Keterangan</td><td>:</td><td colspan="3"><?php echo htmlspecialchars($h['note']); ?></td></tr>
    </table>
    <table class="items">
        <thead>
            <tr>
                <th width="5%">No</th><th width="12%">Kode</th><th width="28%">Nama Item</th>
                <th width="7%">Qty</th><th width="14%">Harga Jual</th><th width="14%">Subtotal</th>
                <th width="14%">Alasan</th><th width="16%">Penggantian</th>
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
    <div class="ttd-area">
        <div class="ttd-box"><div>Dibuat Oleh,</div><div class="nama">(<?php echo htmlspecialchars($h['user']); ?>)</div></div>
        <div class="ttd-box"><div>Diketahui,</div><div class="nama">(______________)</div></div>
        <div class="ttd-box"><div>Pelanggan,</div><div class="nama">(______________)</div></div>
    </div>
</body>
</html>
