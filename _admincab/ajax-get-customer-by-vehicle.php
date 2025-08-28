<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Database connection
require_once "../config/koneksi.php";

// Set content type to JSON
header('Content-Type: application/json');

// Check if POST request with nopol parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nopol'])) {
    $nopol = mysqli_real_escape_string($koneksi, $_POST['nopol']);
    
    try {
        // Query to get customer data based on vehicle number
        $stmt = mysqli_prepare($koneksi, "SELECT 
                                        vpk.nopolisi,
                                        vpk.pemilik as nama_pelanggan,
                                        vpk.telephone,
                                        vpk.alamat,
                                        vpk.tipe,
                                        vpk.jenis,
                                        vpk.warna,
                                        vpk.merek,
                                        tbl.nopelanggan,
                                        tbl.namapelanggan,
                                        tbl.alamat as alamat_lengkap,
                                        tbl.telephone as tlp_pelanggan,
                                        tbl.patokan
                                        FROM view_pelanggan_kendaraan vpk
                                        LEFT JOIN tblpelanggan tbl ON vpk.pemilik = tbl.namapelanggan
                                        WHERE vpk.nopolisi = ?");
        
        mysqli_stmt_bind_param($stmt, "s", $nopol);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($data = mysqli_fetch_assoc($result)) {
            // Prepare response data
            $response_data = [
                'no_polisi' => $data['nopolisi'],
                'nama_pelanggan' => $data['nama_pelanggan'],
                'no_pelanggan' => $data['nopelanggan'] ?: $nopol, // Use nopol if no customer code
                'telepon' => $data['tlp_pelanggan'] ?: $data['telephone'],
                'alamat' => $data['alamat_lengkap'] ?: $data['alamat'],
                'patokan' => $data['patokan'] ?: '',
                'tipe' => $data['tipe'],
                'jenis' => $data['jenis'],
                'warna' => $data['warna'],
                'merek' => $data['merek']
            ];
            
            mysqli_stmt_close($stmt);
            
            echo json_encode([
                'success' => true,
                'data' => $response_data,
                'message' => 'Data pelanggan berhasil ditemukan'
            ]);
        } else {
            mysqli_stmt_close($stmt);
            
            echo json_encode([
                'success' => false,
                'message' => 'Data kendaraan tidak ditemukan',
                'data' => null
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'data' => null
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method or missing parameters',
        'data' => null
    ]);
}

mysqli_close($koneksi);
?>