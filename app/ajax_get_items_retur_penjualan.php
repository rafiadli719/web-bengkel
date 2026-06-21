<?php
session_start();
if(empty($_SESSION['_iduser'])) { echo json_encode(['error'=>'Unauthorized']); exit; }

include "../config/koneksi.php";
$kd_cabang   = $_SESSION['_cabang'];
$nopenjualan = isset($_GET['nopenjualan']) ? mysqli_real_escape_string($koneksi, $_GET['nopenjualan']) : '';

if($nopenjualan == '') {
    echo json_encode(['error'=>'No penjualan kosong']); exit;
}

$cek = mysqli_query($koneksi, "SELECT notransaksi, no_pelanggan, carabayar FROM tblpenjualan_header WHERE notransaksi='$nopenjualan' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) {
    echo json_encode(['error'=>'No penjualan tidak ditemukan atau bukan milik cabang ini']); exit;
}
$hdr = mysqli_fetch_assoc($cek);

$pel = mysqli_query($koneksi, "SELECT namapelanggan FROM tblpelanggan WHERE nopelanggan='".mysqli_real_escape_string($koneksi,$hdr['no_pelanggan'])."'");
$row_pel = mysqli_fetch_assoc($pel);
$namapelanggan = $row_pel ? $row_pel['namapelanggan'] : $hdr['no_pelanggan'];

$sql = mysqli_query($koneksi, "SELECT d.no_item, d.quantity, d.qty_retur, d.harga_jual, d.total,
                                       COALESCE(i.namaitem, d.no_item) AS namaitem
                                FROM tblpenjualan_detail d
                                LEFT JOIN tblitem i ON i.noitem = d.no_item
                                WHERE d.no_transaksi='$nopenjualan'
                                AND d.status_trx != '0'
                                AND d.quantity > 0
                                ORDER BY d.nobaris");

$items = [];
while($row = mysqli_fetch_assoc($sql)) {
    $sisa = (int)$row['quantity'] - (int)$row['qty_retur'];
    if($sisa > 0) {
        $items[] = [
            'no_item'      => $row['no_item'],
            'namaitem'     => $row['namaitem'],
            'qty_jual'     => (int)$row['quantity'],
            'qty_retur'    => (int)$row['qty_retur'],
            'sisa_retur'   => $sisa,
            'harga_jual'   => (float)$row['harga_jual'],
        ];
    }
}

$jenis_sql = mysqli_query($koneksi, "SELECT id, jenis_penggantian FROM tbjenis_penggantian_retur ORDER BY id");
$jenis = [];
while($row = mysqli_fetch_assoc($jenis_sql)) { $jenis[] = $row; }

echo json_encode([
    'success'       => true,
    'nopenjualan'   => $hdr['notransaksi'],
    'namapelanggan' => $namapelanggan,
    'no_pelanggan'  => $hdr['no_pelanggan'],
    'carabayar'     => $hdr['carabayar'],
    'items'         => $items,
    'jenis'         => $jenis,
]);
?>
