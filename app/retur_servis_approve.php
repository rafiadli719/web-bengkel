<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$noretur = isset($_GET['noretur']) ? mysqli_real_escape_string($koneksi, $_GET['noretur']) : '';
if($noretur == '') { header("location:retur_servis.php"); exit; }

$cek = mysqli_query($koneksi, "SELECT status_retur, no_service FROM tblretur_servis_header
                                WHERE noretur='$noretur' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) { header("location:retur_servis.php"); exit; }
$row = mysqli_fetch_assoc($cek);
if($row['status_retur'] != '0') {
    header("location:retur_servis_detail.php?noretur=".urlencode($noretur));
    exit;
}
$no_service = $row['no_service'];
$tanggal = date('Y-m-d');

mysqli_begin_transaction($koneksi);
try {
    // Stok balik cuma di titik approve (bukan di save) — beda dari pola Penjualan
    // karena servis motong stok saat PEMBAYARAN (tipe='4'), bukan saat item ditambah.
    $chk_stok = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM tbstok WHERE no_transaksi='$noretur' AND tipe='9'");
    $r_chk = mysqli_fetch_assoc($chk_stok);
    if((int)$r_chk['cnt'] === 0) {
        $det = mysqli_query($koneksi, "SELECT no_item, quantity, tipe_item FROM tblretur_servis_detail
                                       WHERE no_retur='$noretur' AND tipe_item='barang'");
        while($d = mysqli_fetch_assoc($det)) {
            $no_item = mysqli_real_escape_string($koneksi, $d['no_item']);
            $qty = (int)$d['quantity'];
            $ket = mysqli_real_escape_string($koneksi, "Retur Servis $noretur");
            if(!mysqli_query($koneksi, "INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                                        VALUES ('9','$noretur','$no_item','$tanggal',$qty,0,'$ket','$kd_cabang')"))
                throw new Exception("Gagal insert stok: ".mysqli_error($koneksi));
        }
    }

    if(!mysqli_query($koneksi, "UPDATE tblretur_servis_header SET status_retur='1', status_refund='1', tanggal_refund=NOW()
                                WHERE noretur='$noretur' AND kd_cabang='$kd_cabang'"))
        throw new Exception("Gagal update status retur: ".mysqli_error($koneksi));

    mysqli_commit($koneksi);
    header("location:retur_servis_detail.php?noretur=".urlencode($noretur)."&approved=1");
    exit;
} catch(Exception $e) {
    mysqli_rollback($koneksi);
    header("location:retur_servis_detail.php?noretur=".urlencode($noretur)."&err=".urlencode($e->getMessage()));
    exit;
}
?>
