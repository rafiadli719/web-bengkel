<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php"); exit;
}
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    header("location:retur_penjualan.php"); exit;
}

include "../config/koneksi.php";
include "function_retur.php";

$id_user    = $_SESSION['_iduser'];
$kd_cabang  = $_SESSION['_cabang'];

$nopenjualan       = isset($_POST['nopenjualan'])       ? mysqli_real_escape_string($koneksi, $_POST['nopenjualan'])       : '';
$note              = isset($_POST['note'])              ? mysqli_real_escape_string($koneksi, $_POST['note'])              : '';
$user_input        = isset($_POST['user_input'])        ? mysqli_real_escape_string($koneksi, $_POST['user_input'])        : '';
$cara_bayar_refund = isset($_POST['cara_bayar_refund']) ? mysqli_real_escape_string($koneksi, $_POST['cara_bayar_refund']) : '';

if($cara_bayar_refund == '') {
    header("location:retur_penjualan_add.php?nopenjualan=".urlencode($nopenjualan)."&err=".urlencode("Cara Bayar Refund harus dipilih")); exit;
}

$item_kode   = isset($_POST['item_kode'])   ? $_POST['item_kode']   : [];
$item_qty    = isset($_POST['item_qty'])    ? $_POST['item_qty']    : [];
$item_harga  = isset($_POST['item_harga'])  ? $_POST['item_harga']  : [];
$item_alasan = isset($_POST['item_alasan']) ? $_POST['item_alasan'] : [];
$item_jenis  = isset($_POST['item_jenis'])  ? $_POST['item_jenis']  : [];

if($nopenjualan == '' || count($item_kode) == 0) {
    header("location:retur_penjualan_add.php?err=Data tidak lengkap"); exit;
}

$cek = mysqli_query($koneksi, "SELECT notransaksi FROM tblpenjualan_header WHERE notransaksi='$nopenjualan' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) {
    header("location:retur_penjualan_add.php?err=No penjualan tidak valid"); exit;
}

$tanggal  = date('Y-m-d');
$last_id  = OtomatisIDReturPenjualan($koneksi);
$no_retur = FormatNoReturPenjualan($last_id);

$total_qty   = 0;
$total_nilai = 0;
$item_count  = count($item_kode);

for($i = 0; $i < $item_count; $i++) {
    $qty   = (int)($item_qty[$i] ?? 0);
    $harga = (float)($item_harga[$i] ?? 0);
    if($qty > 0) { $total_qty += $qty; $total_nilai += $qty * $harga; }
}

mysqli_begin_transaction($koneksi);
try {
    $sql_h = "INSERT INTO tblretur_penjualan_header
              (noretur, nopembelian, tanggal, note, cara_bayar_refund, total_qty_retur, total_retur, diskon, total_diskon, pajak, total_pajak, total_akhir, user, kd_cabang, status_retur, status_refund)
              VALUES
              ('$no_retur','$nopenjualan','$tanggal','$note','$cara_bayar_refund',
               $total_qty, $total_nilai, 0, 0, 0, 0, $total_nilai,
               '$user_input','$kd_cabang','0','0')";
    if(!mysqli_query($koneksi, $sql_h)) throw new Exception("Gagal simpan header: ".mysqli_error($koneksi));

    for($i = 0; $i < $item_count; $i++) {
        $no_item  = mysqli_real_escape_string($koneksi, $item_kode[$i] ?? '');
        $qty      = (int)($item_qty[$i] ?? 0);
        $harga    = (float)($item_harga[$i] ?? 0);
        $alasan   = mysqli_real_escape_string($koneksi, $item_alasan[$i] ?? '');
        $jenis    = (int)($item_jenis[$i] ?? 1);

        if($qty <= 0 || $no_item == '') continue;

        $cek_d = mysqli_query($koneksi, "SELECT quantity, qty_retur FROM tblpenjualan_detail
                                         WHERE no_transaksi='$nopenjualan' AND no_item='$no_item'");
        if(mysqli_num_rows($cek_d) == 0) continue;
        $row_d = mysqli_fetch_assoc($cek_d);
        $sisa  = (int)$row_d['quantity'] - (int)$row_d['qty_retur'];
        if($qty > $sisa) $qty = $sisa;
        if($qty <= 0) continue;
        $subtotal = $qty * $harga;

        $sql_d = "INSERT INTO tblretur_penjualan_detail
                  (no_retur, no_pembelian, no_item, quantity, harga_pokok, potongan, total, user, kd_cabang, alasan_retur, jenis_penggantian, status_penggantian)
                  VALUES
                  ('$no_retur','$nopenjualan','$no_item',$qty,$harga,0,$subtotal,'$user_input','$kd_cabang','$alasan',$jenis,0)";
        if(!mysqli_query($koneksi, $sql_d)) throw new Exception("Gagal simpan detail: ".mysqli_error($koneksi));

        $sql_upd = "UPDATE tblpenjualan_detail
                    SET qty_retur = qty_retur + $qty
                    WHERE no_transaksi='$nopenjualan' AND no_item='$no_item'";
        if(!mysqli_query($koneksi, $sql_upd)) throw new Exception("Gagal update qty_retur: ".mysqli_error($koneksi));

        $ket_stok = mysqli_real_escape_string($koneksi, "Retur Penjualan $no_retur");
        $sql_stok = "INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                     VALUES ('8','$no_retur','$no_item','$tanggal',$qty,0,'$ket_stok','$kd_cabang')";
        if(!mysqli_query($koneksi, $sql_stok)) throw new Exception("Gagal simpan stok: ".mysqli_error($koneksi));
    }

    $res_tot = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_qty_retur),0) AS tot
                                       FROM tblretur_penjualan_header WHERE nopembelian='$nopenjualan'");
    $row_tot     = mysqli_fetch_assoc($res_tot);
    $new_tot_ret = (int)$row_tot['tot'];
    if(!mysqli_query($koneksi, "UPDATE tblpenjualan_header SET total_retur=$new_tot_ret WHERE notransaksi='$nopenjualan'"))
        throw new Exception("Gagal update header penjualan: ".mysqli_error($koneksi));

    mysqli_commit($koneksi);
    header("location:retur_penjualan_detail.php?noretur=".urlencode($no_retur)."&sukses=1");
    exit;

} catch(Exception $e) {
    mysqli_rollback($koneksi);
    $err = urlencode("Terjadi kesalahan: ".$e->getMessage());
    header("location:retur_penjualan_add.php?nopenjualan=".urlencode($nopenjualan)."&err=$err");
    exit;
}
?>
