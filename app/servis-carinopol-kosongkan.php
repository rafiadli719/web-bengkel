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

    // Tabel anak kini membawa kd_cabang (identitas servis = no_service + kd_cabang), jadi DELETE dibatasi per cabang.

    // Hapus Tabel Keluhan
    $modal=mysqli_query($koneksi,"Delete
                                    FROM tbservis_keluhan
                                    WHERE
                                    no_service='$no_service' AND kd_cabang='$kd_cabang'");

    // Hapus Tabel Item Pengerjaan
    $modal=mysqli_query($koneksi,"Delete
                                    FROM tbservis_pengerjaan
                                    WHERE
                                    no_service='$no_service' AND kd_cabang='$kd_cabang'");

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