<?php
/**
 * AJAX: Search Item untuk Promo (JSON Response)
 * ============================================================================
 * Endpoint khusus untuk pencarian barang/jasa dalam format JSON
 * Digunakan oleh modal pencarian di master-diskon-periode.php
 * 
 * Tipe:
 * - barang: Item dengan jasawaktu = 0 atau NULL (sparepart)
 * - jasa: Item dengan jasawaktu > 0 (jasa service)
 * ============================================================================
 */

session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])) {
    echo json_encode(['error' => true, 'message' => 'Session expired', 'data' => []]);
    exit;
}

include "../config/koneksi.php";

$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, trim($_GET['q'])) : '';
$tipe = isset($_GET['tipe']) ? strtolower(trim($_GET['tipe'])) : 'semua';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

// Build base query - using tblitem directly
// Jasa = jasawaktu > 0 (has service time)
// Barang = jasawaktu = 0 or NULL (no service time, it's a product)
$sql = "SELECT noitem, namaitem, jenis, hargajual, jasawaktu, jenis_jasa,
               CASE WHEN COALESCE(jasawaktu, 0) > 0 THEN 'jasa' ELSE 'barang' END as tipe_item
        FROM tblitem
        WHERE statusitem = '1'";

// Build tipe condition based on jasawaktu (not jenis_jasa)
if($tipe == 'barang' || $tipe == 'sparepart') {
    // Barang/Sparepart: jasawaktu = 0 or NULL
    $sql .= " AND (COALESCE(jasawaktu, 0) = 0)";
} elseif($tipe == 'jasa' || $tipe == 'service') {
    // Jasa: jasawaktu > 0
    $sql .= " AND (COALESCE(jasawaktu, 0) > 0)";
}

// Build search condition
if($search != '') {
    $sql .= " AND (noitem LIKE '%$search%' OR namaitem LIKE '%$search%')";
}

$sql .= " ORDER BY namaitem ASC LIMIT $limit";

$result = mysqli_query($koneksi, $sql);

$data = [];
if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        // Determine jenis jasa label
        $jenis_label = '';
        if($row['tipe_item'] == 'jasa') {
            switch($row['jenis_jasa']) {
                case '1': $jenis_label = 'Jasa Servis'; break;
                case '2': $jenis_label = 'Jasa Perawatan'; break;
                case '3': $jenis_label = 'Jasa Perbaikan'; break;
                default: $jenis_label = 'Jasa Umum'; break;
            }
        } else {
            $jenis_label = $row['jenis'] ?? 'Barang';
        }
        
        $data[] = [
            'id' => $row['noitem'],
            'kode' => $row['noitem'],
            'nama' => $row['namaitem'],
            'jenis' => $jenis_label,
            'harga' => floatval($row['hargajual'] ?? 0),
            'waktu' => intval($row['jasawaktu'] ?? 0),
            'tipe' => $row['tipe_item']
        ];
    }
}

echo json_encode([
    'error' => false,
    'total' => count($data),
    'data' => $data
]);
?>
