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

$cek = mysqli_query($koneksi, "SELECT noretur, nopembelian, status_retur
                                FROM tblretur_pembelian_header
                                WHERE noretur='$noretur' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) {
    header("location:retur_pembelian_rst.php?_key=&_cari="); exit;
}
$row = mysqli_fetch_assoc($cek);
if($row['status_retur'] != '0') {
    header("location:retur_pembelian_rst.php?_key=&_cari="); exit;
}
$nopembelian = mysqli_real_escape_string($koneksi, $row['nopembelian']);

$det = mysqli_query($koneksi, "SELECT no_item, quantity FROM tblretur_pembelian_detail WHERE no_retur='$noretur'");
$items = [];
while($d = mysqli_fetch_assoc($det)) { $items[] = $d; }

mysqli_begin_transaction($koneksi);
try {
    foreach($items as $it) {
        $no_item = mysqli_real_escape_string($koneksi, $it['no_item']);
        $qty     = (int)$it['quantity'];
        $sql_upd = "UPDATE tblpembelian_detail
                    SET qty_retur = GREATEST(0, qty_retur - $qty)
                    WHERE no_transaksi='$nopembelian' AND no_item='$no_item'";
        if(!mysqli_query($koneksi, $sql_upd)) throw new Exception("Gagal rollback qty_retur: ".mysqli_error($koneksi));
    }

    if(!mysqli_query($koneksi, "DELETE FROM tbstok WHERE no_transaksi='$noretur' AND tipe='7'"))
        throw new Exception("Gagal hapus stok: ".mysqli_error($koneksi));

    if(!mysqli_query($koneksi, "DELETE FROM tblretur_pembelian_detail WHERE no_retur='$noretur'"))
        throw new Exception("Gagal hapus detail: ".mysqli_error($koneksi));

    if(!mysqli_query($koneksi, "DELETE FROM tblretur_pembelian_header WHERE noretur='$noretur'"))
        throw new Exception("Gagal hapus header: ".mysqli_error($koneksi));

    $res_tot = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_qty_retur),0) AS tot
                                       FROM tblretur_pembelian_header
                                       WHERE nopembelian='$nopembelian'");
    $row_tot     = mysqli_fetch_assoc($res_tot);
    $new_tot_ret = (int)$row_tot['tot'];
    if(!mysqli_query($koneksi, "UPDATE tblpembelian_header SET total_retur=$new_tot_ret WHERE notransaksi='$nopembelian'"))
        throw new Exception("Gagal update header pembelian: ".mysqli_error($koneksi));

    mysqli_commit($koneksi);
    header("location:retur_pembelian_rst.php?_key=&_cari=");
    exit;

} catch(Exception $e) {
    mysqli_rollback($koneksi);
    header("location:retur_pembelian_rst.php?_key=&_cari=");
    exit;
}
?>
