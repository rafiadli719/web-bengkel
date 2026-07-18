<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
require_once __DIR__ . '/_customer_identity.php';

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
$nopol = strtoupper(trim($_POST['txtnopol'] ?? ''));
$nopelanggan = '';
$bl_pajak = $_POST['cbobulanpajak'] ?? '';
$th_pajak = $_POST['txtthnpajak'] ?? '';
$merek_id = $_POST['cbomerek'] ?? '';
$tipe_id = $_POST['cbotipe'] ?? '';
$jenis_id = $_POST['cbojenis'] ?? '';
$warna_id = $_POST['cbowarna'] ?? '';
$no_wa = trim($_POST['txtnowa'] ?? '');
$informasi_sumber = $_POST['cboinformasisumber'] ?? '';
$kd_cabang = $_SESSION['_cabang'];
$google_maps = trim($_POST['txtgooglemaps'] ?? '');
$jenis_servis = $_POST['jenis_servis'] ?? 'reguler'; // reguler or jemput_antar

// Handle foto rumah upload
$foto_rumah = '';
if (isset($_FILES['fotorumah']) && $_FILES['fotorumah']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($_FILES['fotorumah']['type'], $allowed_types)) {
        header("location:pelanggan_add_servis.php?error=" . urlencode("Tipe file tidak didukung. Gunakan JPG atau PNG."));
        exit;
    }

    if ($_FILES['fotorumah']['size'] > $max_size) {
        header("location:pelanggan_add_servis.php?error=" . urlencode("Ukuran file terlalu besar. Maksimal 2MB."));
        exit;
    }

    $upload_dir = '../file_upload/foto_rumah/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES['fotorumah']['name'], PATHINFO_EXTENSION);
    $foto_rumah = 'rumah_' . strtoupper(str_replace(' ', '_', $nopol)) . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $foto_rumah;

    if (!move_uploaded_file($_FILES['fotorumah']['tmp_name'], $upload_path)) {
        header("location:pelanggan_add_servis.php?error=" . urlencode("Gagal mengupload foto rumah."));
        exit;
    }

    $foto_rumah = 'file_upload/foto_rumah/' . $foto_rumah;
}

// Validasi input wajib
if (empty($namapelanggan) || empty($gender) || empty($tgl_lahir) || empty($valid_tgl_lahir) ||
    empty($alamat_detail) || empty($provinsi) || empty($kota) || empty($kecamatan) || empty($nopol) || empty($bl_pajak) ||
    empty($th_pajak) || empty($merek_id) || empty($tipe_id) || empty($jenis_id) || empty($warna_id) || empty($informasi_sumber)) {
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

$customer_resolution = fitmotorResolveCustomerCodeByPhone($koneksi, $no_wa);
if ($customer_resolution['status'] === 'ambiguous') {
    header("location:pelanggan_add_servis.php?error=" . urlencode("Nomor WA terhubung ke lebih dari satu pelanggan. Rapikan merge pelanggan dulu sebelum tambah motor baru."));
    exit;
}

$nopelanggan = $customer_resolution['status'] === 'existing'
    ? $customer_resolution['code']
    : fitmotorGenerateCustomerCode($koneksi);

// Cek apakah nomor polisi sudah ada di tblkendaraan
$stmt = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ?");
mysqli_stmt_bind_param($stmt, "s", $nopol);
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
if ($customer_resolution['status'] === 'existing') {
    $query = "UPDATE tblpelanggan SET namapelanggan = ?, gender = ?, tgllahir = ?, valid_tgl_lahir = ?, alamat = ?, kota = ?, patokan = ?, telephone = ?, bl_pajak = ?, th_pajak = ?, merek_id = ?, tipe_id = ?, jenis_id = ?, warna_id = ?, propinsi = ?, informasi_sumber = ?, google_maps = ?, foto_rumah = COALESCE(NULLIF(?, ''), foto_rumah) WHERE nopelanggan = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    if ($stmt === false) {
        mysqli_rollback($koneksi);
        header("location:pelanggan_add_servis.php?error=" . urlencode("Gagal menyiapkan query update pelanggan: " . mysqli_error($koneksi)));
        exit;
    }

    mysqli_stmt_bind_param($stmt, "ssssssssssiiiisssss",
        $namapelanggan, $gender, $tgl_lahir, $valid_tgl_lahir,
        $alamat_lengkap, $kota, $patokan, $no_wa, $bl_pajak, $th_pajak,
        $merek_id, $tipe_id, $jenis_id, $warna_id,
        $propinsi, $informasi_sumber, $google_maps, $foto_rumah, $nopelanggan);
} else {
    $query = "INSERT INTO tblpelanggan (
        nopelanggan, namapelanggan, gender, tgllahir, valid_tgl_lahir, alamat, kota, patokan,
        telephone, bl_pajak, th_pajak, merek_id, tipe_id, jenis_id, warna_id,
        propinsi, kodepost, negara, fax, kontakperson, note, potongan, tipepot,
        lavelharga, kgrup, klat, klong, panggilan, saldoawal, pertanggal, id_panggilan, informasi_sumber,
        google_maps, foto_rumah
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    if ($stmt === false) {
        mysqli_rollback($koneksi);
        header("location:pelanggan_add_servis.php?error=" . urlencode("Gagal menyiapkan query pelanggan: " . mysqli_error($koneksi)));
        exit;
    }

    mysqli_stmt_bind_param($stmt, "sssssssssssiiiissssssdssssssdsisss",
        $nopelanggan, $namapelanggan, $gender, $tgl_lahir, $valid_tgl_lahir,
        $alamat_lengkap, $kota, $patokan, $no_wa, $bl_pajak, $th_pajak,
        $merek_id, $tipe_id, $jenis_id, $warna_id,
        $propinsi, $kodepost, $negara, $fax, $kontakperson, $note,
        $potongan, $tipepot, $lavelharga, $kgrup, $klat, $klong,
        $panggilan, $saldoawal, $pertanggal, $id_panggilan, $informasi_sumber,
        $google_maps, $foto_rumah);
}

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
$pemilik = $nopelanggan;
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
    $nopol, $pemilik, $alamat_kendaraan, $merek_id, $tipe, $tipe_id, 
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

// Task 3: kalau pelanggan baru ini dibuat dari alur "Buat Servis dari Nota Penjualan",
// langsung konversi nota jadi servis alih-alih redirect ke input servis kosong.
$notransaksi = trim($_POST['notransaksi'] ?? '');
if ($notransaksi !== '') {
    include "helper-functions.php";
    $hasil = buatServisDariPenjualan($koneksi, $nopelanggan, $notransaksi, $kd_cabang, $id_user);
    if ($hasil['ok']) {
        header("location:servis-input-router.php?snoserv=" . urlencode($hasil['no_service']) . "&tab=items");
    } else {
        header("location:penjualan_buat_servis.php?notransaksi=" . urlencode($notransaksi) . "&error=" . urlencode($hasil['message']));
    }
    exit;
}

// Redirect berdasarkan pilihan jenis servis
if ($jenis_servis === 'jemput_antar') {
    // Redirect ke servis jemput antar
    header("location:servis-input-reguler-jemput.php?nopelanggan=" . urlencode($nopelanggan));
} else {
    // Default: Redirect ke servis reguler
    header("location:servis-input-reguler.php?nopelanggan=" . urlencode($nopelanggan));
}
exit;

mysqli_close($koneksi);
?>
