<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$noretur = isset($_GET['noretur']) ? mysqli_real_escape_string($koneksi, $_GET['noretur']) : '';
if($noretur == '') { header("location:retur_servis.php"); exit; }

$cek = mysqli_query($koneksi, "SELECT noretur, no_service, status_retur
                                FROM tblretur_servis_header
                                WHERE noretur='$noretur' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) { header("location:retur_servis.php"); exit; }
$row = mysqli_fetch_assoc($cek);
if($row['status_retur'] != '0') { header("location:retur_servis.php"); exit; }
$no_service = mysqli_real_escape_string($koneksi, $row['no_service']);

$det = mysqli_query($koneksi, "SELECT tipe_item, no_item, quantity FROM tblretur_servis_detail WHERE no_retur='$noretur'");
$items = [];
while($d = mysqli_fetch_assoc($det)) { $items[] = $d; }

mysqli_begin_transaction($koneksi);
try {
    foreach($items as $it) {
        $no_item = mysqli_real_escape_string($koneksi, $it['no_item']);
        $qty     = (int)$it['quantity'];
        $table   = $it['tipe_item'] === 'jasa' ? 'tblservis_jasa' : 'tblservis_barang';
        if(!mysqli_query($koneksi, "UPDATE $table
                                    SET qty_retur = GREATEST(0, qty_retur - $qty)
                                    WHERE no_service='$no_service' AND no_item='$no_item'"))
            throw new Exception("Gagal rollback qty_retur: ".mysqli_error($koneksi));
    }

    // Retur pending belum pernah insert tbstok (baru terjadi saat approve),
    // jadi tidak perlu hapus tbstok di sini.
    if(!mysqli_query($koneksi, "DELETE FROM tblretur_servis_detail WHERE no_retur='$noretur'"))
        throw new Exception("Gagal hapus detail: ".mysqli_error($koneksi));

    if(!mysqli_query($koneksi, "DELETE FROM tblretur_servis_header WHERE noretur='$noretur'"))
        throw new Exception("Gagal hapus header: ".mysqli_error($koneksi));

    $res_tot = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_retur),0) AS tn
                                       FROM tblretur_servis_header WHERE no_service='$no_service'");
    $row_tot = mysqli_fetch_assoc($res_tot);
    $new_tot_ret = (float)$row_tot['tn'];
    if(!mysqli_query($koneksi, "UPDATE tblservice SET total_retur=$new_tot_ret WHERE no_service='$no_service'"))
        throw new Exception("Gagal update header servis: ".mysqli_error($koneksi));

    mysqli_commit($koneksi);
    header("location:retur_servis.php?batal=1");
    exit;

} catch(Exception $e) {
    mysqli_rollback($koneksi);
    header("location:retur_servis_detail.php?noretur=".urlencode($noretur)."&err=".urlencode($e->getMessage()));
    exit;
}
?>
