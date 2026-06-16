<?php
session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

if (!isset($_POST['no_service']) || empty($_POST['no_service'])) {
    echo json_encode(['success' => false, 'message' => 'Nomor service tidak valid']);
    exit;
}

$no_service = $_POST['no_service'];

// Get mechanic data from POST
$kepala_mekanik1 = $_POST['kepala_mekanik1'] ?? '';
$persen_kepala1 = intval($_POST['persen_kepala1'] ?? 0);
$kepala_mekanik2 = $_POST['kepala_mekanik2'] ?? '';
$persen_kepala2 = intval($_POST['persen_kepala2'] ?? 0);
$admin1 = $_POST['admin1'] ?? '';
$persen_admin1 = intval($_POST['persen_admin1'] ?? 0);
$admin2 = $_POST['admin2'] ?? '';
$persen_admin2 = intval($_POST['persen_admin2'] ?? 0);
$mekanik1 = $_POST['mekanik1'] ?? '';
$persen_kerja1 = intval($_POST['persen_kerja1'] ?? 0);
$mekanik2 = $_POST['mekanik2'] ?? '';
$persen_kerja2 = intval($_POST['persen_kerja2'] ?? 0);
$mekanik3 = $_POST['mekanik3'] ?? '';
$persen_kerja3 = intval($_POST['persen_kerja3'] ?? 0);
$mekanik4 = $_POST['mekanik4'] ?? '';
$persen_kerja4 = intval($_POST['persen_kerja4'] ?? 0);

try {
    // Check if service exists
    $query_check = "SELECT COUNT(*) as count FROM tblservice WHERE no_service = ?";
    $stmt_check = mysqli_prepare($koneksi, $query_check);
    mysqli_stmt_bind_param($stmt_check, "s", $no_service);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $check_data = mysqli_fetch_assoc($result_check);
    
    if ($check_data['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Service tidak ditemukan']);
        exit;
    }
    
    // Update mechanic data in tblservice
    $query_update = "UPDATE tblservice SET 
                     kepala_mekanik1 = ?, persen_kepala_mekanik1 = ?,
                     kepala_mekanik2 = ?, persen_kepala_mekanik2 = ?,
                     mekanik1 = ?, persen_mekanik1 = ?,
                     mekanik2 = ?, persen_mekanik2 = ?,
                     mekanik3 = ?, persen_mekanik3 = ?,
                     mekanik4 = ?, persen_mekanik4 = ?,
                     updated_at = NOW()
                     WHERE no_service = ?";
    
    $stmt_update = mysqli_prepare($koneksi, $query_update);
    mysqli_stmt_bind_param($stmt_update, "sisisisisisisss", 
        $kepala_mekanik1, $persen_kepala1,
        $kepala_mekanik2, $persen_kepala2,
        $mekanik1, $persen_kerja1,
        $mekanik2, $persen_kerja2,
        $mekanik3, $persen_kerja3,
        $mekanik4, $persen_kerja4,
        $no_service
    );
    
    if (mysqli_stmt_execute($stmt_update)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt_update);
        
        // Also save to separate mechanic assignment table if exists
        saveMechanicAssignment($koneksi, $no_service, [
            'kepala_mekanik1' => $kepala_mekanik1,
            'persen_kepala1' => $persen_kepala1,
            'kepala_mekanik2' => $kepala_mekanik2,
            'persen_kepala2' => $persen_kepala2,
            'mekanik1' => $mekanik1,
            'persen_kerja1' => $persen_kerja1,
            'mekanik2' => $mekanik2,
            'persen_kerja2' => $persen_kerja2,
            'mekanik3' => $mekanik3,
            'persen_kerja3' => $persen_kerja3,
            'mekanik4' => $mekanik4,
            'persen_kerja4' => $persen_kerja4
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Data mekanik berhasil disimpan',
            'affected_rows' => $affected_rows
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data mekanik']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt_check)) mysqli_stmt_close($stmt_check);
    if (isset($stmt_update)) mysqli_stmt_close($stmt_update);
}

function saveMechanicAssignment($koneksi, $no_service, $mechanic_data) {
    // Check if we have a separate mechanic assignment table
    $table_exists = false;
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE 'tb_servis_mekanik_assignment'");
    if (mysqli_num_rows($result) > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        try {
            // Delete existing assignments
            $query_delete = "DELETE FROM tb_servis_mekanik_assignment WHERE no_service = ?";
            $stmt_delete = mysqli_prepare($koneksi, $query_delete);
            mysqli_stmt_bind_param($stmt_delete, "s", $no_service);
            mysqli_stmt_execute($stmt_delete);
            
            // Insert new assignments
            $assignments = [
                ['kepala_mekanik', $mechanic_data['kepala_mekanik1'], $mechanic_data['persen_kepala1']],
                ['kepala_mekanik', $mechanic_data['kepala_mekanik2'], $mechanic_data['persen_kepala2']],
                ['mekanik', $mechanic_data['mekanik1'], $mechanic_data['persen_kerja1']],
                ['mekanik', $mechanic_data['mekanik2'], $mechanic_data['persen_kerja2']],
                ['mekanik', $mechanic_data['mekanik3'], $mechanic_data['persen_kerja3']],
                ['mekanik', $mechanic_data['mekanik4'], $mechanic_data['persen_kerja4']]
            ];
            
            foreach ($assignments as $assignment) {
                if (!empty($assignment[1]) && $assignment[2] > 0) {
                    $query_insert = "INSERT INTO tb_servis_mekanik_assignment 
                                    (no_service, jenis_mekanik, nama_mekanik, persentase, created_at) 
                                    VALUES (?, ?, ?, ?, NOW())";
                    $stmt_insert = mysqli_prepare($koneksi, $query_insert);
                    mysqli_stmt_bind_param($stmt_insert, "sssi", $no_service, $assignment[0], $assignment[1], $assignment[2]);
                    mysqli_stmt_execute($stmt_insert);
                }
            }
            
        } catch (Exception $e) {
            // Ignore errors from optional table
        }
    }
}
?>