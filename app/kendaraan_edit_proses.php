<?php
	include "../config/koneksi.php";
	    
	$txtnopol= $_POST['txtnopol'];    
	$txtnama= $_POST['txtnama'];
    $txtalamat= $_POST['txtalamat'];
    $cbomerek= $_POST['cbomerek'];
    $cbotipe= $_POST['cbotipe'];
    $cbojenis= $_POST['cbojenis'];
    $cbowarna= $_POST['cbowarna'];
    $txtthn_buat= $_POST['txtthn_buat'];
    $txtthn_rakit= $_POST['txtthn_rakit'];
    $txtsilinder= $_POST['txtsilinder'];
    $txtnorangka= $_POST['txtnorangka'];
    $txtnomesin= $_POST['txtnomesin'];
    $txtnote= $_POST['txtnote'];
    
        // Tipe Motor : spt Beat-110, dll
		$cari_kd = mysqli_prepare($koneksi, "SELECT tipe FROM tbtipe_motor WHERE kode_tipe = ?");
		mysqli_stmt_bind_param($cari_kd, "s", $cbotipe);
		mysqli_stmt_execute($cari_kd);
		$tm_cari = mysqli_fetch_array(mysqli_stmt_get_result($cari_kd));
		$tipe_motor = $tm_cari['tipe'];
        // End Tipe Motor

        // Jenis Motor : spt FL, Carbu, dll
		$cari_kd = mysqli_prepare($koneksi, "SELECT jenis FROM tbjenis_motor WHERE kd = ?");
		mysqli_stmt_bind_param($cari_kd, "s", $cbojenis);
		mysqli_stmt_execute($cari_kd);
		$tm_cari = mysqli_fetch_array(mysqli_stmt_get_result($cari_kd));
		$jenis_motor = $tm_cari['jenis'];
        // End Jenis Motor

        // Warna Motor
		$cari_kd = mysqli_prepare($koneksi, "SELECT warna FROM tbwarna WHERE id = ?");
		mysqli_stmt_bind_param($cari_kd, "s", $cbowarna);
		mysqli_stmt_execute($cari_kd);
		$tm_cari = mysqli_fetch_array(mysqli_stmt_get_result($cari_kd));
		$warna_motor = $tm_cari['warna'];
        // End Warna Motor

	$upd = mysqli_prepare($koneksi, "UPDATE tblkendaraan
                        SET pemilik=?, alamat=?,
                        kode_merek=?, kode_tipe=?, kode_jenis=?,
                        tahun_buat=?, tahun_rakit=?, silinder=?,
                        kode_warna=?, no_rangka=?, no_mesin=?,
                        note=?,
                        tipe=?, jenis=?, warna=?
                        WHERE nopolisi=?");
	mysqli_stmt_bind_param($upd, str_repeat('s', 16),
		$txtnama, $txtalamat, $cbomerek, $cbotipe, $cbojenis,
		$txtthn_buat, $txtthn_rakit, $txtsilinder,
		$cbowarna, $txtnorangka, $txtnomesin,
		$txtnote, $tipe_motor, $jenis_motor, $warna_motor, $txtnopol);
	mysqli_stmt_execute($upd);
								
	echo"<script>window.alert('Data Kendaraan Berhasil disimpan!');
    window.location=('kendaraan.php');</script>";
?>