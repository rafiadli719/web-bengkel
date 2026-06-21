<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php"); exit;
}

if($_SERVER['REQUEST_METHOD'] != 'POST'){
    header("location:retur_pembelian.php"); exit;
}

include "../config/koneksi.php";
include "function_retur.php";

$id_user    = $_SESSION['_iduser'];
$kd_cabang  = $_SESSION['_cabang'];

$nopembelian = isset($_POST['nopembelian']) ? mysqli_real_escape_string($koneksi, $_POST['nopembelian']) : '';
$note        = isset($_POST['note'])        ? mysqli_real_escape_string($koneksi, $_POST['note'])        : '';
$user_input  = isset($_POST['user_input'])  ? mysqli_real_escape_string($koneksi, $_POST['user_input'])  : '';

$item_kode   = isset($_POST['item_kode'])   ? $_POST['item_kode']   : [];
$item_qty    = isset($_POST['item_qty'])    ? $_POST['item_qty']    : [];
$item_harga  = isset($_POST['item_harga'])  ? $_POST['item_harga']  : [];
$item_alasan = isset($_POST['item_alasan']) ? $_POST['item_alasan'] : [];
$item_jenis  = isset($_POST['item_jenis'])  ? $_POST['item_jenis']  : [];

if($nopembelian == '' || count($item_kode) == 0) {
    header("location:retur_pembelian_add.php?err=Data tidak lengkap"); exit;
}

$cek = mysqli_query($koneksi, "SELECT notransaksi FROM tblpembelian_header WHERE notransaksi='$nopembelian' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) {
    header("location:retur_pembelian_add.php?err=No pembelian tidak valid"); exit;
}

$tanggal  = date('Y-m-d');
$last_id  = OtomatisIDReturPembelian($koneksi);
$no_retur = FormatNoReturPembelian($last_id);

$total_qty   = 0;
$total_nilai = 0;
$item_count  = count($item_kode);

for($i = 0; $i < $item_count; $i++) {
    $qty   = (int)($item_qty[$i] ?? 0);
    $harga = (float)($item_harga[$i] ?? 0);
    if($qty > 0) {
        $total_qty   += $qty;
        $total_nilai += $qty * $harga;
    }
}

mysqli_begin_transaction($koneksi);

try {
    $sql_h = "INSERT INTO tblretur_pembelian_header
              (noretur, nopembelian, tanggal, note, total_qty_retur, total_retur, diskon, total_diskon, pajak, total_pajak, total_akhir, user, kd_cabang, status_retur)
              VALUES
              ('$no_retur','$nopembelian','$tanggal','$note',
               $total_qty, $total_nilai, 0, 0, 0, 0, $total_nilai,
               '$user_input','$kd_cabang','0')";
    if(!mysqli_query($koneksi, $sql_h)) throw new Exception("Gagal simpan header: ".mysqli_error($koneksi));

    for($i = 0; $i < $item_count; $i++) {
        $no_item  = mysqli_real_escape_string($koneksi, $item_kode[$i] ?? '');
        $qty      = (int)($item_qty[$i] ?? 0);
        $harga    = (float)($item_harga[$i] ?? 0);
        $alasan   = mysqli_real_escape_string($koneksi, $item_alasan[$i] ?? '');
        $jenis    = (int)($item_jenis[$i] ?? 1);
        $subtotal = $qty * $harga;

        if($qty <= 0 || $no_item == '') continue;

        $cek_d = mysqli_query($koneksi, "SELECT quantity, qty_retur FROM tblpembelian_detail
                                         WHERE no_transaksi='$nopembelian' AND no_item='$no_item'");
        if(mysqli_num_rows($cek_d) == 0) continue;
        $row_d = mysqli_fetch_assoc($cek_d);
        $sisa  = (int)$row_d['quantity'] - (int)$row_d['qty_retur'];
        if($qty > $sisa) $qty = $sisa;
        if($qty <= 0) continue;
        $subtotal = $qty * $harga;

        $sql_d = "INSERT INTO tblretur_pembelian_detail
                  (no_retur, no_pembelian, no_item, quantity, harga_pokok, potongan, total, user, kd_cabang, alasan_retur, jenis_penggantian, status_penggantian)
                  VALUES
                  ('$no_retur','$nopembelian','$no_item',$qty,$harga,0,$subtotal,'$user_input','$kd_cabang','$alasan',$jenis,0)";
        if(!mysqli_query($koneksi, $sql_d)) throw new Exception("Gagal simpan detail: ".mysqli_error($koneksi));

        $sql_upd = "UPDATE tblpembelian_detail
                    SET qty_retur = qty_retur + $qty
                    WHERE no_transaksi='$nopembelian' AND no_item='$no_item'";
        if(!mysqli_query($koneksi, $sql_upd)) throw new Exception("Gagal update qty_retur: ".mysqli_error($koneksi));

        $ket_stok = mysqli_real_escape_string($koneksi, "Retur Pembelian $no_retur");
        $sql_stok = "INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                     VALUES ('7','$no_retur','$no_item','$tanggal',0,$qty,'$ket_stok','$kd_cabang')";
        if(!mysqli_query($koneksi, $sql_stok)) throw new Exception("Gagal simpan stok: ".mysqli_error($koneksi));
    }

    // Recalculate total_retur di header pembelian
    $res_tot = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_qty_retur),0) AS tot
                                       FROM tblretur_pembelian_header
                                       WHERE nopembelian='$nopembelian'");
    $row_tot     = mysqli_fetch_assoc($res_tot);
    $new_tot_ret = (int)$row_tot['tot'];
    $sql_uph = "UPDATE tblpembelian_header SET total_retur=$new_tot_ret WHERE notransaksi='$nopembelian'";
    if(!mysqli_query($koneksi, $sql_uph)) throw new Exception("Gagal update header pembelian: ".mysqli_error($koneksi));

    mysqli_commit($koneksi);
    header("location:retur_pembelian_detail.php?noretur=".urlencode($no_retur)."&sukses=1");
    exit;

} catch(Exception $e) {
    mysqli_rollback($koneksi);
    $err = urlencode("Terjadi kesalahan: ".$e->getMessage());
    header("location:retur_pembelian_add.php?nopembelian=".urlencode($nopembelian)."&err=$err");
    exit;
}
?>
