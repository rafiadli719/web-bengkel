<?php
	include "../config/koneksi.php";
	    
	// $txtkd= $_POST['txtkd']; // Kode cabang sekarang auto-generated 
	$txtnama= $_POST['txtnama'];
    $cbolevel= $_POST['cbolevel'];
    $entry_year = mysqli_real_escape_string($koneksi, $_POST['entry_year']);
    $entry_month = mysqli_real_escape_string($koneksi, $_POST['entry_month']);

    // Generate kode cabang otomatis: YYYYMMxxx
    $prefix = $entry_year . $entry_month;
    
    // Cari kode terakhir dengan prefix yang sama
    $query_cek = mysqli_query($koneksi, "SELECT max(kode_cabang) as max_kode FROM tbcabang WHERE kode_cabang LIKE '$prefix%'");
    $data_cek = mysqli_fetch_array($query_cek);
    $max_kode = $data_cek['max_kode'];
    
    if ($max_kode) {
        // Jika sudah ada, ambil nomer urut terakhir dan tambah 1
        $urutan = (int) substr($max_kode, 6, 3); // Ambil 3 digit terakhir (index 6, length 3 karena YYYYMM = 6 char)
        $urutan++;
    } else {
        // Jika belum ada, mulai dari 1
        $urutan = 1;
    }
    
    $txtkd = $prefix . sprintf("%03s", $urutan);

    // Escape input untuk keamanan
    $txtkd_escaped = mysqli_real_escape_string($koneksi, $txtkd);
    $txtnama_escaped = mysqli_real_escape_string($koneksi, $txtnama);
    $cbolevel_escaped = mysqli_real_escape_string($koneksi, $cbolevel);
    
    // Simpan data cabang
	mysqli_query($koneksi,"INSERT INTO tbcabang 
                        (kode_cabang, nama_cabang, tipe_cabang) 
                        VALUES 
                        ('$txtkd_escaped','$txtnama_escaped','$cbolevel_escaped')");
    
    // Redirect ke halaman edit cabang supaya alamat/Google Maps/koordinat
    // bisa dilengkapi - form Tambah Cabang sendiri tidak punya field itu.
    // (sebelumnya redirect salah ke pelanggan_add.php, bug copy-paste)
    $redirect_url = "cabang_edit.php?kd=" . urlencode($txtkd);

	echo"<script>window.alert('Data Cabang Berhasil disimpan! Kode Cabang: $txtkd. Silakan lengkapi alamat & lokasi cabang.');
    window.location=('" . $redirect_url . "');</script>";
?>