<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php"); exit;
}
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    header("location:retur_servis.php"); exit;
}

include "../config/koneksi.php";
include "function_retur.php";

$id_user    = $_SESSION['_iduser'];
$kd_cabang  = $_SESSION['_cabang'];

$no_service        = isset($_POST['no_service'])        ? mysqli_real_escape_string($koneksi, $_POST['no_service'])        : '';
$note              = isset($_POST['note'])              ? mysqli_real_escape_string($koneksi, $_POST['note'])              : '';
$user_input        = isset($_POST['user_input'])        ? mysqli_real_escape_string($koneksi, $_POST['user_input'])        : '';
$cara_bayar_refund = isset($_POST['cara_bayar_refund']) ? mysqli_real_escape_string($koneksi, $_POST['cara_bayar_refund']) : '';

$item_tipe   = isset($_POST['item_tipe'])   ? $_POST['item_tipe']   : [];
$item_kode   = isset($_POST['item_kode'])   ? $_POST['item_kode']   : [];
$item_qty    = isset($_POST['item_qty'])    ? $_POST['item_qty']    : [];
$item_harga  = isset($_POST['item_harga'])  ? $_POST['item_harga']  : [];
$item_alasan = isset($_POST['item_alasan']) ? $_POST['item_alasan'] : [];

if($no_service == '' || count($item_kode) == 0) {
    header("location:retur_servis_add.php?no_service=".urlencode($no_service)."&err=".urlencode("Data tidak lengkap")); exit;
}
if($cara_bayar_refund == '') {
    header("location:retur_servis_add.php?no_service=".urlencode($no_service)."&err=".urlencode("Cara Bayar Refund harus dipilih")); exit;
}

$cek = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='$no_service' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) {
    header("location:retur_servis_add.php?err=".urlencode("No service tidak valid")); exit;
}
$row_srv = mysqli_fetch_assoc($cek);
if($row_srv['status_servis'] != 'bayar') {
    header("location:retur_servis_add.php?err=".urlencode("Servis belum lunas, tidak bisa diretur")); exit;
}

$tanggal  = date('Y-m-d');
$last_id  = OtomatisIDReturServis($koneksi);
$no_retur = FormatNoReturServis($last_id);

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
    $sql_h = "INSERT INTO tblretur_servis_header
              (noretur, no_service, tanggal, note, cara_bayar_refund, total_qty_retur, total_retur, user, kd_cabang, status_retur, status_refund)
              VALUES
              ('$no_retur','$no_service','$tanggal','$note','$cara_bayar_refund',
               $total_qty, $total_nilai, '$user_input','$kd_cabang','0','0')";
    if(!mysqli_query($koneksi, $sql_h)) throw new Exception("Gagal simpan header: ".mysqli_error($koneksi));

    for($i = 0; $i < $item_count; $i++) {
        $tipe_item = ($item_tipe[$i] ?? '') === 'jasa' ? 'jasa' : 'barang';
        $no_item   = mysqli_real_escape_string($koneksi, $item_kode[$i] ?? '');
        $qty       = (int)($item_qty[$i] ?? 0);
        $harga     = (float)($item_harga[$i] ?? 0);
        $alasan    = mysqli_real_escape_string($koneksi, $item_alasan[$i] ?? '');

        if($qty <= 0 || $no_item == '') continue;

        if($tipe_item === 'barang') {
            $cek_d = mysqli_query($koneksi, "SELECT quantity, qty_retur FROM tblservis_barang
                                             WHERE no_service='$no_service' AND no_item='$no_item'");
            if(mysqli_num_rows($cek_d) == 0) continue;
            $row_d = mysqli_fetch_assoc($cek_d);
            $sisa  = (int)$row_d['quantity'] - (int)$row_d['qty_retur'];
            if($qty > $sisa) $qty = $sisa;
            if($qty <= 0) continue;
            $subtotal = $qty * $harga;

            $sql_upd = "UPDATE tblservis_barang SET qty_retur = qty_retur + $qty
                        WHERE no_service='$no_service' AND no_item='$no_item'";
        } else {
            $cek_d = mysqli_query($koneksi, "SELECT qty_retur FROM tblservis_jasa
                                             WHERE no_service='$no_service' AND no_item='$no_item'");
            if(mysqli_num_rows($cek_d) == 0) continue;
            $row_d = mysqli_fetch_assoc($cek_d);
            $sisa  = 1 - (int)$row_d['qty_retur'];
            if($qty > $sisa) $qty = $sisa;
            if($qty <= 0) continue;
            $subtotal = $qty * $harga;

            $sql_upd = "UPDATE tblservis_jasa SET qty_retur = qty_retur + $qty
                        WHERE no_service='$no_service' AND no_item='$no_item'";
        }

        $sql_d = "INSERT INTO tblretur_servis_detail
                  (no_retur, no_service, tipe_item, no_item, quantity, harga, total, alasan_retur, user, kd_cabang)
                  VALUES
                  ('$no_retur','$no_service','$tipe_item','$no_item',$qty,$harga,$subtotal,'$alasan','$user_input','$kd_cabang')";
        if(!mysqli_query($koneksi, $sql_d)) throw new Exception("Gagal simpan detail: ".mysqli_error($koneksi));

        if(!mysqli_query($koneksi, $sql_upd)) throw new Exception("Gagal update qty_retur: ".mysqli_error($koneksi));
    }

    $res_tot = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_qty_retur),0) AS tq, COALESCE(SUM(total_retur),0) AS tn
                                       FROM tblretur_servis_header WHERE no_service='$no_service'");
    $row_tot     = mysqli_fetch_assoc($res_tot);
    $new_tot_ret = (float)$row_tot['tn'];
    if(!mysqli_query($koneksi, "UPDATE tblservice SET total_retur=$new_tot_ret WHERE no_service='$no_service'"))
        throw new Exception("Gagal update header servis: ".mysqli_error($koneksi));

    mysqli_commit($koneksi);
    header("location:retur_servis_detail.php?noretur=".urlencode($no_retur)."&sukses=1");
    exit;

} catch(Exception $e) {
    mysqli_rollback($koneksi);
    $err = urlencode("Terjadi kesalahan: ".$e->getMessage());
    header("location:retur_servis_add.php?no_service=".urlencode($no_service)."&err=$err");
    exit;
}
?>
