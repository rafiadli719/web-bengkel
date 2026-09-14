<?php
	include "../config/koneksi.php";

	$txtuser= mysqli_real_escape_string($koneksi, $_POST['txtuser']);
	$txtpwd= mysqli_real_escape_string($koneksi, $_POST['txtpwd']);
	$cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);
	$kode_posisi = mysqli_real_escape_string($koneksi, trim($_POST['kode_posisi'] ?? ''));
	$kode_cabang = mysqli_real_escape_string($koneksi, trim($_POST['kode_cabang'] ?? ''));

	// kode_posisi/kode_cabang wajib diisi mulai sekarang — sebelum ini
	// save_user.php gak pernah isi kolom itu sama sekali di tbuser, jadi
	// akun yang dibikin lewat "Data User" gak kedetect lookup approval
	// per-cabang (mis. app/_komplain/koneksi_komplain.php cari KACAB/KM
	// via kode_posisi+kode_cabang). Validasi terhadap tb_master_posisi &
	// tbcabang biar gak ada nilai sembarangan nyangkut.
	$posisiCheck = mysqli_query($koneksi, "SELECT nama_posisi FROM tb_master_posisi WHERE kode_posisi = '$kode_posisi' AND is_active = 'active'");
	if (!$posisiCheck || mysqli_num_rows($posisiCheck) === 0) {
		echo "<script>window.alert('Posisi tidak valid!'); window.history.back();</script>";
		exit;
	}
	$cabangCheck = mysqli_query($koneksi, "SELECT nama_cabang FROM tbcabang WHERE kode_cabang = '$kode_cabang'");
	if (!$cabangCheck || mysqli_num_rows($cabangCheck) === 0) {
		echo "<script>window.alert('Cabang tidak valid!'); window.history.back();</script>";
		exit;
	}

	// Generate kode_karyawan: prefix YYYYMM + 4 digit urut, discope per
	// prefix. Pola sama kayak app/master_karyawan_save.php (sudah jalan
	// buat tbuser_karyawan) - dipakai lagi di sini karena tbuser (tabel
	// login) belum pernah punya kode_karyawan walau kolomnya udah ada.
	$prefix = date('Ym');
	$kode_karyawan = null;
	for ($attempt = 0; $attempt < 5; $attempt++) {
		$seqRes = mysqli_query($koneksi,
			"SELECT MAX(CAST(RIGHT(kode_karyawan, 4) AS UNSIGNED)) AS seq
			 FROM tbuser WHERE kode_karyawan LIKE '{$prefix}%'");
		$seq = 1;
		if ($seqRes) {
			$sr = mysqli_fetch_assoc($seqRes);
			if (!empty($sr['seq'])) { $seq = (int)$sr['seq'] + 1; }
		}
		$candidate = sprintf('%s%04d', $prefix, $seq);
		$check = mysqli_query($koneksi, "SELECT id FROM tbuser WHERE kode_karyawan = '$candidate'");
		if ($check && mysqli_num_rows($check) === 0) {
			$kode_karyawan = $candidate;
			break;
		}
	}
	if ($kode_karyawan === null) {
		echo "<script>window.alert('Gagal generate kode karyawan, coba lagi.'); window.history.back();</script>";
		exit;
	}

	mysqli_query($koneksi,"INSERT INTO tbuser
						(kode_karyawan, nama_user, password, foto_user, user_akses, kode_posisi, kode_cabang, is_active)
						VALUES
						('$kode_karyawan', '$txtuser','$txtpwd','file_upload/avatar.png','$cbolevel','$kode_posisi','$kode_cabang','active')");

	echo"<script>window.alert('Data User Berhasil disimpan! Kode karyawan: $kode_karyawan');
	window.location=('user.php');</script>";
?>
