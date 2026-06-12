<?php
session_start();
if(empty($_SESSION['_iduser'])){
	header("location:../index.php");
	exit;
}

include "../config/koneksi.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id > 0) {
	$delete = mysqli_query($koneksi, "DELETE FROM master_tarif_jemput WHERE id='$id'");

	if($delete) {
		$_SESSION['success'] = "Data tarif berhasil dihapus!";
	} else {
		$_SESSION['error'] = "Gagal menghapus data: " . mysqli_error($koneksi);
	}
} else {
	$_SESSION['error'] = "ID tidak valid!";
}

header("Location: master-tarif-jemput.php");
exit;
?>
