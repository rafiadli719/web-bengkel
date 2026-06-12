<?php
session_start();
include "../config/koneksi.php";
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
    exit;
}

$keluhan_id = isset($_POST['keluhan_id']) ? (int) $_POST['keluhan_id'] : 0;
$proses_id = isset($_POST['proses_id']) ? (int) $_POST['proses_id'] : 0;
$status_proses = isset($_POST['status_proses']) ? trim($_POST['status_proses']) : '';
$mekanik_id = isset($_POST['mekanik_id']) && $_POST['mekanik_id'] !== '' ? trim($_POST['mekanik_id']) : null;
$catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

$allowedStatus = ['pending', 'dikerjakan', 'selesai', 'skip'];
if ($keluhan_id <= 0 || $proses_id <= 0 || !in_array($status_proses, $allowedStatus, true)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid']);
    exit;
}

try {
    $stmtCheck = mysqli_prepare(
        $koneksi,
        "SELECT id, waktu_mulai FROM tbservis_keluhan_tracking WHERE keluhan_id = ? AND proses_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmtCheck, "ii", $keluhan_id, $proses_id);
    mysqli_stmt_execute($stmtCheck);
    $checkResult = mysqli_stmt_get_result($stmtCheck);
    $trackingData = $checkResult ? mysqli_fetch_assoc($checkResult) : null;
    mysqli_stmt_close($stmtCheck);

    $current_time = date('Y-m-d H:i:s');
    $mekanikValue = ($mekanik_id !== null && $mekanik_id !== '') ? $mekanik_id : null;

    if ($trackingData) {
        $trackingId = (int) $trackingData['id'];
        $startedAt = $trackingData['waktu_mulai'];

        if ($status_proses === 'dikerjakan' && empty($startedAt)) {
            $stmtUpdate = mysqli_prepare(
                $koneksi,
                "UPDATE tbservis_keluhan_tracking SET status_proses = ?, mekanik_id = ?, waktu_mulai = ?, catatan = ?, updated_at = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmtUpdate, "sssssi", $status_proses, $mekanikValue, $current_time, $catatan, $current_time, $trackingId);
        } elseif ($status_proses === 'selesai') {
            $waktuMulai = empty($startedAt) ? $current_time : $startedAt;
            $stmtUpdate = mysqli_prepare(
                $koneksi,
                "UPDATE tbservis_keluhan_tracking SET status_proses = ?, mekanik_id = ?, waktu_mulai = ?, waktu_selesai = ?, catatan = ?, updated_at = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmtUpdate, "ssssssi", $status_proses, $mekanikValue, $waktuMulai, $current_time, $catatan, $current_time, $trackingId);
        } else {
            $stmtUpdate = mysqli_prepare(
                $koneksi,
                "UPDATE tbservis_keluhan_tracking SET status_proses = ?, mekanik_id = ?, catatan = ?, updated_at = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmtUpdate, "ssssi", $status_proses, $mekanikValue, $catatan, $current_time, $trackingId);
        }

        if (!$stmtUpdate || !mysqli_stmt_execute($stmtUpdate)) {
            throw new RuntimeException('Gagal memperbarui tracking proses');
        }
        mysqli_stmt_close($stmtUpdate);
    } else {
        $waktuMulai = null;
        $waktuSelesai = null;
        if ($status_proses === 'dikerjakan' || $status_proses === 'selesai') {
            $waktuMulai = $current_time;
        }
        if ($status_proses === 'selesai') {
            $waktuSelesai = $current_time;
        }

        $stmtInsert = mysqli_prepare(
            $koneksi,
            "INSERT INTO tbservis_keluhan_tracking (keluhan_id, proses_id, status_proses, mekanik_id, waktu_mulai, waktu_selesai, catatan, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmtInsert,
            "iisssssss",
            $keluhan_id,
            $proses_id,
            $status_proses,
            $mekanikValue,
            $waktuMulai,
            $waktuSelesai,
            $catatan,
            $current_time,
            $current_time
        );

        if (!$stmtInsert || !mysqli_stmt_execute($stmtInsert)) {
            throw new RuntimeException('Gagal menyimpan tracking proses');
        }
        mysqli_stmt_close($stmtInsert);
    }

    updateKeluhanStatus($koneksi, $keluhan_id);
    echo json_encode(['success' => true, 'message' => 'Proses berhasil disimpan']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan proses']);
}

function updateKeluhanStatus($koneksi, $keluhan_id) {
    $stmtKeluhan = mysqli_prepare(
        $koneksi,
        "SELECT k.id, mk.kode_keluhan
         FROM tbservis_keluhan_status k
         LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
         WHERE k.id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmtKeluhan, "i", $keluhan_id);
    mysqli_stmt_execute($stmtKeluhan);
    $keluhanResult = mysqli_stmt_get_result($stmtKeluhan);
    $keluhanData = $keluhanResult ? mysqli_fetch_assoc($keluhanResult) : null;
    mysqli_stmt_close($stmtKeluhan);

    if (!$keluhanData || empty($keluhanData['kode_keluhan'])) {
        return;
    }

    $kodeKeluhan = $keluhanData['kode_keluhan'];

    $stmtTotal = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) as total FROM tbkeluhan_proses WHERE kode_keluhan = ? AND status_aktif = '1'"
    );
    mysqli_stmt_bind_param($stmtTotal, "s", $kodeKeluhan);
    mysqli_stmt_execute($stmtTotal);
    $totalResult = mysqli_stmt_get_result($stmtTotal);
    $totalRow = $totalResult ? mysqli_fetch_assoc($totalResult) : ['total' => 0];
    mysqli_stmt_close($stmtTotal);

    $stmtCompleted = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) as completed FROM tbservis_keluhan_tracking WHERE keluhan_id = ? AND status_proses = 'selesai'"
    );
    mysqli_stmt_bind_param($stmtCompleted, "i", $keluhan_id);
    mysqli_stmt_execute($stmtCompleted);
    $completedResult = mysqli_stmt_get_result($stmtCompleted);
    $completedRow = $completedResult ? mysqli_fetch_assoc($completedResult) : ['completed' => 0];
    mysqli_stmt_close($stmtCompleted);

    $totalProses = (int) ($totalRow['total'] ?? 0);
    $completedProses = (int) ($completedRow['completed'] ?? 0);
    $new_status = 'datang';
    if ($completedProses > 0) {
        $new_status = ($totalProses > 0 && $completedProses >= $totalProses) ? 'selesai' : 'diproses';
    }

    $stmtUpdate = mysqli_prepare(
        $koneksi,
        "UPDATE tbservis_keluhan_status SET status_pengerjaan = ?, updated_at = NOW() WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmtUpdate, "si", $new_status, $keluhan_id);
    mysqli_stmt_execute($stmtUpdate);
    mysqli_stmt_close($stmtUpdate);
}
?>
