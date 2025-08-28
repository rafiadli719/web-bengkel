<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// Ambil data dari form
$namapelanggan = trim($_POST['txtnama'] ?? '');
$gender = $_POST['cbogender'] ?? '';
$tgl_lahir = $_POST['id-date-picker-1'] ?? '';
$valid_tgl_lahir = $_POST['cbovalid'] ?? '';
$alamat = trim($_POST['txtalamat'] ?? '');
$provinsi = trim($_POST['cboprovinsi'] ?? '');
$kota = trim($_POST['cbokota'] ?? '');
$kecamatan = trim($_POST['cbokecamatan'] ?? '');
$alamat_detail = trim($_POST['txtalamatdetail'] ?? '');
$patokan = trim($_POST['txtpatokan'] ?? '');
$nopelanggan = strtoupper(trim($_POST['txtnopol'] ?? '')); // No polisi
$bl_pajak = $_POST['cbobulanpajak'] ?? '';
$th_pajak = $_POST['txtthnpajak'] ?? '';
$merek_id = $_POST['cbomerek'] ?? '';
$tipe_id = $_POST['cbotipe'] ?? '';
$jenis_id = $_POST['cbojenis'] ?? '';
$warna_id = $_POST['cbowarna'] ?? '';
$no_wa = trim($_POST['txtnowa'] ?? '');
$kd_cabang = $_SESSION['_cabang'];

// Validasi input wajib
if (empty($namapelanggan) || empty($gender) || empty($tgl_lahir) || empty($valid_tgl_lahir) || 
    empty($alamat_detail) || empty($provinsi) || empty($kota) || empty($kecamatan) || empty($nopelanggan) || empty($bl_pajak) || 
    empty($th_pajak) || empty($merek_id) || empty($tipe_id) || empty($jenis_id) || empty($warna_id)) {
    header("location:pelanggan_add_servis.php?error=" . urlencode("Semua field wajib diisi kecuali patokan dan nomor WA"));
    exit;
}

// Validasi gender
if (!in_array($gender, ['Laki-laki', 'Perempuan'])) {
    header("location:pelanggan_add_servis.php?error=" . urlencode("Pilihan gender tidak valid"));
    exit;
}

// Validasi validitas tanggal lahir
if (!in_array($valid_tgl_lahir, ['Valid', 'Non Valid'])) {
    header("location:pelanggan_add_servis.php?error=" . urlencode("Validitas tanggal lahir tidak valid"));
    exit;
}

// Validasi tahun pajak
if (!preg_match('/^\d{4}$/', $th_pajak)) {
    header("location:pelanggan_add_servis.php?error=" . urlencode("Tahun pajak harus 4 digit (YYYY)"));
    exit;
}

// Validasi bulan pajak
if (!in_array($bl_pajak, array_map(function($i) { return sprintf("%02d", $i); }, range(1, 12)))) {
    header("location:pelanggan_add_servis.php?error=" . urlencode("Bulan pajak tidak valid"));
    exit;
}

// Konversi format tanggal lahir
try {
    $tgl_lahir_dt = DateTime::createFromFormat('d/m/Y', $tgl_lahir);
    if ($tgl_lahir_dt === false || $tgl_lahir_dt > new DateTime()) {
        throw new Exception("Tanggal lahir tidak valid atau di masa depan");
    }
    $tgl_lahir = $tgl_lahir_dt->format('Y-m-d');
} catch (Exception $e) {
    header("location:pelanggan_add_servis.php?error=" . urlencode($e->getMessage()));
    exit;
}

// Cek apakah nomor polisi sudah ada di tblpelanggan
$stmt = mysqli_prepare($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan = ?");
mysqli_stmt_bind_param($stmt, "s", $nopelanggan);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    header("location:pelanggan_add_servis.php?error=" . urlencode("Nomor polisi sudah terdaftar"));
    exit;
}
mysqli_stmt_close($stmt);

// Cek apakah nomor polisi sudah ada di tblkendaraan
$stmt = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ?");
mysqli_stmt_bind_param($stmt, "s", $nopelanggan);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    header("location:pelanggan_add_servis.php?error=" . urlencode("Nomor polisi sudah terdaftar di kendaraan"));
    exit;
}
mysqli_stmt_close($stmt);

// Mulai transaksi
mysqli_begin_transaction($koneksi);

// Gabungkan alamat lengkap
$alamat_lengkap = $alamat_detail . ', ' . $kecamatan . ', ' . $kota . ', ' . $provinsi;

// Default values untuk kolom NOT NULL di tblpelanggan
$propinsi = $provinsi; // Gunakan provinsi dari form
$kodepost = '';
$negara = 'Indonesia';
$fax = '';
$kontakperson = '';
$note = '';
$potongan = 0;
$tipepot = '';
$lavelharga = '';
$kgrup = '';
$klat = '';
$klong = '';
$panggilan = '';
$saldoawal = 0;
$pertanggal = date('Y-m-d'); // Gunakan tanggal saat ini sebagai default
$id_panggilan = 0;

// Simpan data pelanggan ke tblpelanggan
// Perbaikan: menggunakan jenis_id bukan jenis yang tidak ada di tabel
$query = "INSERT INTO tblpelanggan (
    nopelanggan, namapelanggan, gender, tgllahir, valid_tgl_lahir, alamat, kota, patokan, 
    telephone, bl_pajak, th_pajak, merek_id, tipe_id, jenis_id, warna_id, 
    propinsi, kodepost, negara, fax, kontakperson, note, potongan, tipepot, 
    lavelharga, kgrup, klat, klong, panggilan, saldoawal, pertanggal, id_panggilan
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $query);
if ($stmt === false) {
    mysqli_rollback($koneksi);
    header("location:pelanggan_add_servis.php?error=" . urlencode("Gagal menyiapkan query pelanggan: " . mysqli_error($koneksi)));
    exit;
}

mysqli_stmt_bind_param($stmt, "sssssssssssiiiissssssdssssssdsi", 
    $nopelanggan, $namapelanggan, $gender, $tgl_lahir, $valid_tgl_lahir, 
    $alamat_lengkap, $kota, $patokan, $no_wa, $bl_pajak, $th_pajak, 
    $merek_id, $tipe_id, $jenis_id, $warna_id, 
    $propinsi, $kodepost, $negara, $fax, $kontakperson, $note, 
    $potongan, $tipepot, $lavelharga, $kgrup, $klat, $klong, 
    $panggilan, $saldoawal, $pertanggal, $id_panggilan);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_rollback($koneksi);
    header("location:pelanggan_add_servis.php?error=" . urlencode("Gagal menyimpan pelanggan: " . mysqli_stmt_error($stmt)));
    exit;
}

mysqli_stmt_close($stmt);

// Simpan data kendaraan ke tblkendaraan
// Ambil nama merek, tipe, jenis, dan warna untuk tblkendaraan
// Perbaikan: Menggunakan kolom primary key yang benar
$merek = '';
$stmt = mysqli_prepare($koneksi, "SELECT merek FROM tbpabrik_motor WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $merek_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $merek = $row['merek'];
    }
    mysqli_stmt_close($stmt);
}

// Perbaikan: Menggunakan kode_tipe sebagai primary key untuk tbtipe_motor  
$stmt = mysqli_prepare($koneksi, "SELECT tipe FROM tbtipe_motor WHERE kode_tipe = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $tipe_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $tipe = $row['tipe'];
    }
    mysqli_stmt_close($stmt);
}

$jenis = '';
// Perbaikan: Menggunakan kd sebagai primary key untuk tbjenis_motor
$stmt = mysqli_prepare($koneksi, "SELECT jenis FROM tbjenis_motor WHERE kd = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $jenis_id); // Menggunakan integer karena kd adalah integer auto_increment
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $jenis = $row['jenis'];
    }
    mysqli_stmt_close($stmt);
}

$warna = '';
$stmt = mysqli_prepare($koneksi, "SELECT warna FROM tbwarna WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $warna_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $warna = $row['warna'];
    }
    mysqli_stmt_close($stmt);
}

// Data default untuk kolom tblkendaraan
$pemilik = $namapelanggan;
$alamat_kendaraan = $alamat_lengkap;
$tahun_buat = $th_pajak; // Asumsi tahun pajak sebagai tahun buat
$tahun_rakit = $th_pajak;
$silinder = '';
$no_rangka = '';
$no_mesin = '';
$note_kendaraan = '';

// Perbaikan: Pastikan semua kolom tblkendaraan sesuai dengan struktur tabel
$query_kendaraan = "INSERT INTO tblkendaraan (
    nopolisi, pemilik, alamat, kode_merek, tipe, kode_tipe, jenis, kode_jenis, 
    tahun_buat, tahun_rakit, silinder, warna, kode_warna, no_rangka, no_mesin, note
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_kendaraan = mysqli_prepare($koneksi, $query_kendaraan);
if ($stmt_kendaraan === false) {
    mysqli_rollback($koneksi);
    header("location:pelanggan_add_servis.php?error=" . urlencode("Gagal menyiapkan query kendaraan: " . mysqli_error($koneksi)));
    exit;
}

mysqli_stmt_bind_param($stmt_kendaraan, "sssisisissssssss", 
    $nopelanggan, $pemilik, $alamat_kendaraan, $merek_id, $tipe, $tipe_id, 
    $jenis, $jenis_id, $tahun_buat, $tahun_rakit, $silinder, $warna, 
    $warna_id, $no_rangka, $no_mesin, $note_kendaraan);

if (!mysqli_stmt_execute($stmt_kendaraan)) {
    mysqli_rollback($koneksi);
    header("location:pelanggan_add_servis.php?error=" . urlencode("Gagal menyimpan kendaraan: " . mysqli_stmt_error($stmt_kendaraan)));
    exit;
}
mysqli_stmt_close($stmt_kendaraan);

// Commit transaksi
mysqli_commit($koneksi);

// Redirect ke input_garapan.php menggunakan nopelanggan sebagai parameter yang lebih reliable
header("location:input_garapan.php?nopelanggan=" . urlencode($nopelanggan));
exit;

mysqli_close($koneksi);
?>