<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		include "../config/koneksi.php";

		$id = intval($_GET['id'] ?? 0);
		$kode = mysqli_real_escape_string($koneksi, $_GET['kode'] ?? '');

		if($id > 0 && $kode != '') {
			mysqli_query($koneksi,"DELETE FROM tbworkorderdetail WHERE id='$id' AND kode_wo='$kode' LIMIT 1");
		}

		echo"<script>window.location=('workorder-input.php?mode=edit&kode=$kode');</script>";
	}
?>
