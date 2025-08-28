<?php
session_start();

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include "../../config/koneksi.php";

// Set header untuk JSON response
header('Content-Type: application/json');

try {
    // Validate required parameters
    if(!isset($_POST['no_service']) || empty($_POST['no_service'])) {
        throw new Exception('No service tidak valid');
    }
    
    $no_service = mysqli_real_escape_string($koneksi, $_POST['no_service']);
    
    // Get field type to update
    $field_type = $_POST['field_type'] ?? '';
    $field_value = $_POST['field_value'] ?? '';
    $field_percentage = $_POST['field_percentage'] ?? 0;
    
    // Validate field type and map to actual database columns
    $field_mappings = [
        'kepala_mekanik1' => ['field' => 'kepala_mekanik1', 'percentage' => 'persen_kepala_mekanik1'],
        'kepala_mekanik2' => ['field' => 'kepala_mekanik2', 'percentage' => 'persen_kepala_mekanik2'],
        'admin1' => ['field' => 'mekanik1', 'percentage' => 'persen_mekanik1'], // Map admin1 to mekanik1
        'admin2' => ['field' => 'mekanik2', 'percentage' => 'persen_mekanik2'], // Map admin2 to mekanik2
        'mekanik1' => ['field' => 'mekanik1', 'percentage' => 'persen_mekanik1'],
        'mekanik2' => ['field' => 'mekanik2', 'percentage' => 'persen_mekanik2'],
        'mekanik3' => ['field' => 'mekanik3', 'percentage' => 'persen_mekanik3'],
        'mekanik4' => ['field' => 'mekanik4', 'percentage' => 'persen_mekanik4']
    ];
    
    if(!isset($field_mappings[$field_type])) {
        throw new Exception('Field type tidak valid: ' . $field_type);
    }
    
    // Get actual database field names
    $db_field = $field_mappings[$field_type]['field'];
    $db_percentage = $field_mappings[$field_type]['percentage'];
    
    // Escape values
    $field_value = mysqli_real_escape_string($koneksi, $field_value);
    $field_percentage = (int)$field_percentage;
    
    // Prepare update query with actual database column names
    $sql = "UPDATE tblservice SET 
            $db_field = '$field_value',
            $db_percentage = $field_percentage
            WHERE no_service = '$no_service'";
    
    // Execute update
    $result = mysqli_query($koneksi, $sql);
    
    if(!$result) {
        throw new Exception('Database error: ' . mysqli_error($koneksi));
    }
    
    // Check if any rows were affected
    if(mysqli_affected_rows($koneksi) > 0) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Data berhasil disimpan',
            'field_type' => $field_type,
            'field_value' => $field_value,
            'field_percentage' => $field_percentage
        ]);
    } else {
        // Still return success if no rows changed (same values)
        echo json_encode([
            'status' => 'success', 
            'message' => 'Data sudah tersimpan',
            'field_type' => $field_type,
            'field_value' => $field_value,
            'field_percentage' => $field_percentage
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

// Close connection
mysqli_close($koneksi);
?>