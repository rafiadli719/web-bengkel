<?php
session_start();
include "../../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    die("Unauthorized access");
}

$response = array('success' => false, 'message' => '');

// Skema asli tb_progress_mekanik: mekanik_id / nama_mekanik / jenis_mekanik
// (enum). Dulu handler ini pakai id_mekanik/jenis_kerja yang tidak ada dan
// tidak mengisi nama_mekanik -> simpan & update progress selalu gagal (tabel
// kosong). Sekarang prepared statement (dulu $_POST mentah ke SQL).
try {
    $no_service = trim($_POST['no_service'] ?? '');
    $no_antrian = trim($_POST['no_antrian'] ?? '');
    $mekanik_id = trim($_POST['mekanik_id'] ?? '');
    $nama_mekanik = trim($_POST['nama_mekanik'] ?? '');
    $jenis_mekanik = $_POST['jenis_mekanik'] ?? '';
    $persen_kerja = max(0, min(100, (int)($_POST['persen_kerja'] ?? 0)));
    $status_kerja = $_POST['status_kerja'] ?? '';
    $jam_mulai = trim($_POST['jam_mulai'] ?? '') ?: null;
    $jam_selesai = trim($_POST['jam_selesai'] ?? '') ?: null;
    $catatan_kerja = trim($_POST['catatan_kerja'] ?? '');

    if($no_service === '' || $mekanik_id === '' || $nama_mekanik === '') {
        throw new Exception('Data mekanik tidak lengkap');
    }
    if(!in_array($jenis_mekanik, ['kepala_mekanik', 'mekanik', 'admin'], true)) {
        throw new Exception('Jenis mekanik tidak valid');
    }
    if(!in_array($status_kerja, ['belum_mulai', 'sedang_bekerja', 'selesai', 'batal'], true)) {
        throw new Exception('Status kerja tidak valid');
    }

    $stmt = mysqli_prepare($koneksi, "SELECT id FROM tb_progress_mekanik WHERE no_service = ? AND mekanik_id = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $no_service, $mekanik_id);
    mysqli_stmt_execute($stmt);
    $ada = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);

    if($ada) {
        // jam_mulai/jam_selesai/catatan hanya ditimpa kalau relevan & terisi,
        // sama seperti perilaku asli.
        $set = "persen_kerja = ?, status_kerja = ?";
        $types = 'is';
        $vals = [$persen_kerja, $status_kerja];
        if($status_kerja === 'sedang_bekerja' && $jam_mulai !== null) { $set .= ", jam_mulai = ?"; $types .= 's'; $vals[] = $jam_mulai; }
        if($status_kerja === 'selesai' && $jam_selesai !== null) { $set .= ", jam_selesai = ?"; $types .= 's'; $vals[] = $jam_selesai; }
        if($catatan_kerja !== '') { $set .= ", catatan_kerja = ?"; $types .= 's'; $vals[] = $catatan_kerja; }
        $types .= 'ss';
        $vals[] = $no_service;
        $vals[] = $mekanik_id;
        $stmt = mysqli_prepare($koneksi, "UPDATE tb_progress_mekanik SET $set, updated_at = CURRENT_TIMESTAMP WHERE no_service = ? AND mekanik_id = ?");
        mysqli_stmt_bind_param($stmt, $types, ...$vals);
        if(!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal update progress mekanik: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        $response['success'] = true;
        $response['message'] = 'Progress mekanik berhasil diupdate';
    } else {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO tb_progress_mekanik
             (no_service, no_antrian, mekanik_id, nama_mekanik, jenis_mekanik,
              persen_kerja, status_kerja, jam_mulai, jam_selesai, catatan_kerja)
             VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'sssssissss', $no_service, $no_antrian, $mekanik_id, $nama_mekanik,
            $jenis_mekanik, $persen_kerja, $status_kerja, $jam_mulai, $jam_selesai, $catatan_kerja);
        if(!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal simpan progress mekanik: ' . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        $response['success'] = true;
        $response['message'] = 'Progress mekanik berhasil disimpan';
    }

    // Log aktivitas (skema asli tb_log_antrian: aktivitas + user_nama wajib)
    $user_id = (string)$_SESSION['_iduser'];
    $qu = mysqli_prepare($koneksi, "SELECT nama_user FROM tbuser WHERE id = ?");
    mysqli_stmt_bind_param($qu, 's', $user_id);
    mysqli_stmt_execute($qu);
    $user_nama = (string)(mysqli_fetch_row(mysqli_stmt_get_result($qu))[0] ?? '');
    mysqli_stmt_close($qu);
    $ket = "Mekanik: $nama_mekanik - Progress: $persen_kerja% - Status: $status_kerja";
    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO tb_log_antrian (no_antrian, no_service, aktivitas, keterangan, user_id, user_nama) VALUES (?,?,?,?,?,?)");
    $aktivitas = 'update_progress';
    mysqli_stmt_bind_param($stmt, 'ssssss', $no_antrian, $no_service, $aktivitas, $ket, $user_id, $user_nama);
    mysqli_stmt_execute($stmt); // log best-effort, jangan gagalkan simpan progress
    mysqli_stmt_close($stmt);

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
