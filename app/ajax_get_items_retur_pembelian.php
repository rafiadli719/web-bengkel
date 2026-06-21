<?php
session_start();
if(empty($_SESSION['_iduser'])) { echo json_encode(['error'=>'Unauthorized']); exit; }

include "../config/koneksi.php";
$kd_cabang = $_SESSION['_cabang'];
$nopembelian = isset($_GET['nopembelian']) ? mysqli_real_escape_string($koneksi, $_GET['nopembelian']) : '';

if($nopembelian == '') {
    echo json_encode(['error'=>'No pembelian kosong']); exit;
}

$cek = mysqli_query($koneksi, "SELECT notransaksi, no_supplier, carabayar FROM tblpembelian_header WHERE notransaksi='$nopembelian' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) {
    echo json_encode(['error'=>'No pembelian tidak ditemukan atau bukan milik cabang ini']); exit;
}
$hdr = mysqli_fetch_assoc($cek);

$sup = mysqli_query($koneksi, "SELECT namasupplier FROM tblsupplier WHERE nosupplier='".mysqli_real_escape_string($koneksi,$hdr['no_supplier'])."'");
$row_sup = mysqli_fetch_assoc($sup);
$namasupplier = $row_sup ? $row_sup['namasupplier'] : $hdr['no_supplier'];

$sql = mysqli_query($koneksi, "SELECT d.no_item, d.quantity, d.qty_retur, d.harga_pokok, d.total,
                                       COALESCE(i.namaitem, d.no_item) AS namaitem
                                FROM tblpembelian_detail d
                                LEFT JOIN tblitem i ON i.noitem = d.no_item
                                WHERE d.no_transaksi='$nopembelian'
                                AND d.status_trx != '0'
                                AND d.quantity > 0
                                ORDER BY d.nobaris");

$items = [];
while($row = mysqli_fetch_assoc($sql)) {
    $sisa = (int)$row['quantity'] - (int)$row['qty_retur'];
    if($sisa > 0) {
        $items[] = [
            'no_item'     => $row['no_item'],
            'namaitem'    => $row['namaitem'],
            'qty_beli'    => (int)$row['quantity'],
            'qty_retur'   => (int)$row['qty_retur'],
            'sisa_retur'  => $sisa,
            'harga_pokok' => (float)$row['harga_pokok'],
        ];
    }
}

$jenis_sql = mysqli_query($koneksi, "SELECT id, jenis_penggantian FROM tbjenis_penggantian_retur ORDER BY id");
$jenis = [];
while($row = mysqli_fetch_assoc($jenis_sql)) { $jenis[] = $row; }

echo json_encode([
    'success'      => true,
    'nopembelian'  => $hdr['notransaksi'],
    'namasupplier' => $namasupplier,
    'no_supplier'  => $hdr['no_supplier'],
    'carabayar'    => $hdr['carabayar'],
    'items'        => $items,
    'jenis'        => $jenis,
]);
?>
