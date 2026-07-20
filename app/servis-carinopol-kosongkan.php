<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
		exit;
	}
	$kd_cabang = $_SESSION['_cabang'];
	include "../config/koneksi.php";

    $no_service = mysqli_real_escape_string($koneksi, $_GET['snoserv']);

    // Guard: pastikan no_service ini benar milik cabang session ini.
    // tbservis_keluhan & tbservis_pengerjaan TIDAK punya kolom kd_cabang,
    // jadi validasi kepemilikan cabang harus dilakukan di sini, bukan di WHERE per tabel.
    $chk = mysqli_query($koneksi, "SELECT no_service FROM tblservice WHERE no_service='$no_service' AND kd_cabang='$kd_cabang' LIMIT 1");
    if (!$chk || mysqli_num_rows($chk) === 0) {
        die("Service tidak ditemukan di cabang Anda.");
    }

    // Guard tambahan: no_service legacy Access tidak dijamin unik lintas cabang
    // (data produksi terbukti ada no_service yang sama dipakai >1 cabang).
    // tbservis_keluhan & tbservis_pengerjaan tidak punya kd_cabang untuk membedakan
    // baris mana milik cabang mana, jadi DELETE by no_service saja bisa ikut menghapus
    // data cabang lain kalau no_service ini kebetulan bentrok. Tolak dulu sampai
    // ditangani manual, daripada diam-diam menghapus data cabang lain.
    $dupChk = mysqli_query($koneksi, "SELECT COUNT(DISTINCT kd_cabang) AS c FROM tblservice WHERE no_service='$no_service'");
    $dupRow = $dupChk ? mysqli_fetch_assoc($dupChk) : null;
    if ($dupRow && (int)$dupRow['c'] > 1) {
        die("No_service ini terdeteksi dipakai lebih dari 1 cabang (data legacy bermasalah). Kosongkan tidak bisa diproses otomatis, hubungi admin untuk penanganan manual.");
    }

    // Hapus Tabel Keluhan
    $modal=mysqli_query($koneksi,"Delete
                                    FROM tbservis_keluhan
                                    WHERE
                                    no_service='$no_service'");

    // Hapus Tabel Item Pengerjaan
    $modal=mysqli_query($koneksi,"Delete
                                    FROM tbservis_pengerjaan
                                    WHERE
                                    no_service='$no_service'");

    // Hapus Tabel Item Barang
    $modal=mysqli_query($koneksi,"Delete
                                    FROM tblservis_barang
                                    WHERE
                                    no_service='$no_service' AND kd_cabang='$kd_cabang'");

    // Hapus Tabel Item Paket
    $modal=mysqli_query($koneksi,"Delete
                                    FROM tblservis_jasa
                                    WHERE
                                    no_service='$no_service' AND kd_cabang='$kd_cabang'");
                                    
            $kdbrg="";
            $kdjasa="";
            echo"<script>window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$kdbrg&kdjasa=$kdjasa');</script>";        
?>