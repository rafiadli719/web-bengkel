<?php
session_start();
if(empty($_SESSION['_iduser'])) { echo json_encode(['error'=>'Unauthorized']); exit; }

include "../config/koneksi.php";
$kd_cabang  = $_SESSION['_cabang'];
$no_service = isset($_GET['no_service']) ? mysqli_real_escape_string($koneksi, $_GET['no_service']) : '';

if($no_service == '') {
    echo json_encode(['error'=>'No service kosong']); exit;
}

$cek = mysqli_query($koneksi, "SELECT no_service, no_pelanggan, status_servis, metode_pembayaran FROM tblservice WHERE no_service='$no_service' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) {
    echo json_encode(['error'=>'No service tidak ditemukan atau bukan milik cabang ini']); exit;
}
$hdr = mysqli_fetch_assoc($cek);

if($hdr['status_servis'] != 'bayar') {
    echo json_encode(['error'=>'Servis ini belum lunas — retur hanya untuk servis berstatus bayar/lunas']); exit;
}

$pel = mysqli_query($koneksi, "SELECT namapelanggan FROM tblpelanggan WHERE nopelanggan='".mysqli_real_escape_string($koneksi,$hdr['no_pelanggan'])."'");
$row_pel = mysqli_fetch_assoc($pel);
$namapelanggan = $row_pel ? $row_pel['namapelanggan'] : $hdr['no_pelanggan'];

$items = [];

$sql_brg = mysqli_query($koneksi, "SELECT b.no_item, b.quantity, b.qty_retur, b.harga_jual, b.total,
                                           COALESCE(i.namaitem, b.no_item) AS namaitem
                                    FROM tblservis_barang b
                                    LEFT JOIN tblitem i ON i.noitem = b.no_item
                                    WHERE b.no_service='$no_service' AND b.no_item != 'PART-CUST'");
while($row = mysqli_fetch_assoc($sql_brg)) {
    $sisa = (int)$row['quantity'] - (int)$row['qty_retur'];
    if($sisa > 0) {
        $items[] = [
            'tipe_item'  => 'barang',
            'no_item'    => $row['no_item'],
            'namaitem'   => $row['namaitem'],
            'qty_asal'   => (int)$row['quantity'],
            'qty_retur'  => (int)$row['qty_retur'],
            'sisa_retur' => $sisa,
            'harga'      => (float)$row['harga_jual'],
        ];
    }
}

$sql_jsa = mysqli_query($koneksi, "SELECT j.no_item, j.harga, j.total, j.qty_retur,
                                           COALESCE(i.namaitem, j.no_item) AS namaitem
                                    FROM tblservis_jasa j
                                    LEFT JOIN tblitem i ON i.noitem = j.no_item
                                    WHERE j.no_service='$no_service'");
while($row = mysqli_fetch_assoc($sql_jsa)) {
    $sisa = 1 - (int)$row['qty_retur'];
    if($sisa > 0) {
        $items[] = [
            'tipe_item'  => 'jasa',
            'no_item'    => $row['no_item'],
            'namaitem'   => $row['namaitem'],
            'qty_asal'   => 1,
            'qty_retur'  => (int)$row['qty_retur'],
            'sisa_retur' => $sisa,
            'harga'      => (float)$row['harga'],
        ];
    }
}

echo json_encode([
    'success'       => true,
    'no_service'    => $hdr['no_service'],
    'namapelanggan' => $namapelanggan,
    'no_pelanggan'  => $hdr['no_pelanggan'],
    'carabayar'     => $hdr['metode_pembayaran'],
    'items'         => $items,
]);
?>
