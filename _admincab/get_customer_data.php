<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['_iduser'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Include database connection
include_once '../config/koneksi.php';

// Check if phone parameter is provided
if (!isset($_POST['phone']) || empty($_POST['phone'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Phone parameter required']);
    exit;
}

$phone = trim($_POST['phone']);

try {
    // Prepare and execute query to check if phone exists
    $query = "SELECT namapelanggan, gender, tgllahir, valid_tgl_lahir, alamat, patokan, telephone, propinsi, kota 
              FROM tblpelanggan 
              WHERE telephone = ? 
              LIMIT 1";
    
    $stmt = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Customer found
        $response = [
            'exists' => true,
            'data' => [
                'nama' => $row['namapelanggan'],
                'gender' => $row['gender'],
                'tgl_lahir' => $row['tgllahir'],
                'valid_tgl_lahir' => $row['valid_tgl_lahir'],
                'alamat' => $row['alamat'],
                'patokan' => $row['patokan'],
                'phone' => $row['telephone'],
                'provinsi' => $row['propinsi'],
                'kota' => $row['kota']
            ]
        ];
    } else {
        // Customer not found
        $response = [
            'exists' => false,
            'data' => null
        ];
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    $response = [
        'error' => 'Database error: ' . $e->getMessage()
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

mysqli_close($koneksi);
?>
