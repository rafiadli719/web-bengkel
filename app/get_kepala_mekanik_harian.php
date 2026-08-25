<?php
/**
 * Helper Functions untuk Kepala Mekanik Harian
 *
 * File ini menyediakan:
 * - Function untuk get data kepala mekanik harian
 * - AJAX endpoint untuk auto-fill kepala mekanik di form servis
 */

/**
 * Get data kepala mekanik harian untuk tanggal tertentu
 *
 * @param mysqli $koneksi Database connection
 * @param string $kode_cabang Kode cabang
 * @param string $tanggal Tanggal (Y-m-d), default hari ini
 * @return array|null Data kepala mekanik atau null jika tidak ada
 */
function getKepalaMetanikHarian($koneksi, $kode_cabang, $tanggal = null) {
    // Treat null or empty string as 'today' to be robust against empty query params
    if ($tanggal === null || $tanggal === '') {
        $tanggal = date('Y-m-d');
    }

    $tanggal = mysqli_real_escape_string($koneksi, $tanggal);
    $kode_cabang = mysqli_real_escape_string($koneksi, $kode_cabang);

    $query = "SELECT
                kmh.kepala_mekanik_1,
                kmh.kepala_mekanik_2,
                kmh.keterangan,
                kmh.tanggal_kerja
              FROM tbl_kepala_mekanik_harian kmh
              WHERE kmh.kode_cabang = '$kode_cabang'
              AND kmh.tanggal_kerja = '$tanggal'
              LIMIT 1";

    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result);
    }

    return null;
}

function getMasterKepalaMetanik($koneksi, $kode_cabang) {
    $kode_cabang = mysqli_real_escape_string($koneksi, $kode_cabang);
    
    $query = "SELECT 
                nama_kepala_mekanik,
                nip_karyawan,
                no_telepon
              FROM tbl_master_kepala_mekanik
              WHERE kode_cabang = '$kode_cabang' 
              AND status_aktif = 'aktif'
              ORDER BY nama_kepala_mekanik ASC";
    
    return mysqli_query($koneksi, $query);
}

function hasKepalaMetanikHarian($koneksi, $kode_cabang, $tanggal = null) {
    // Delegate to getKepalaMetanikHarian which already normalizes empty dates
    $data = getKepalaMetanikHarian($koneksi, $kode_cabang, $tanggal);
    return $data !== null;
}

function getKepalaMetanikHarianJSON($koneksi, $kode_cabang, $tanggal = null) {
    $data = getKepalaMetanikHarian($koneksi, $kode_cabang, $tanggal);

    if ($data) {
        return json_encode([
            'success' => true,
            'has_data' => true,
            'data' => [
                'kepala_mekanik_1' => $data['kepala_mekanik_1'],
                'kepala_mekanik_2' => $data['kepala_mekanik_2'],
                'keterangan' => $data['keterangan'],
                'tanggal_kerja' => $data['tanggal_kerja']
            ]
        ]);
    }

    return json_encode([
        'success' => true,
        'has_data' => false,
        'message' => 'Belum ada input kepala mekanik untuk hari ini',
        'redirect_url' => 'input_kepala_mekanik_harian.php'
    ]);
}

// AJAX endpoint - Get kepala mekanik harian
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_kepala_mekanik') {
    session_start();
    if (empty($_SESSION['_iduser'])) {
        echo json_encode(['success' => false, 'message' => 'Session tidak valid']);
        exit;
    }
    
    include "../config/koneksi.php";
    
    $kd_cabang = $_SESSION['_cabang'];
    $tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal']) : null;
    
    header('Content-Type: application/json');
    echo getKepalaMetanikHarianJSON($koneksi, $kd_cabang, $tanggal);
    exit;
}

// AJAX endpoint - Save kepala mekanik to service
if (isset($_POST['action']) && $_POST['action'] == 'save_kepala_mekanik') {
    session_start();
    header('Content-Type: application/json');
    
    if (empty($_SESSION['_iduser'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    include "../config/koneksi.php";
    
    // Get POST data
    $no_service = $_POST['no_service'] ?? '';
    $kepala_mekanik1 = $_POST['kepala_mekanik1'] ?? '';
    $kepala_mekanik2 = $_POST['kepala_mekanik2'] ?? '';
    $persen_kepala1 = $_POST['persen_kepala1'] ?? 0;
    $persen_kepala2 = $_POST['persen_kepala2'] ?? 0;
    
    // Validate input
    if (empty($no_service)) {
        echo json_encode(['success' => false, 'message' => 'No service tidak valid']);
        exit;
    }
    
    // Sanitize input
    $no_service = mysqli_real_escape_string($koneksi, $no_service);
    $kepala_mekanik1 = mysqli_real_escape_string($koneksi, $kepala_mekanik1);
    $kepala_mekanik2 = mysqli_real_escape_string($koneksi, $kepala_mekanik2);
    $persen_kepala1 = floatval($persen_kepala1);
    $persen_kepala2 = floatval($persen_kepala2);
    
    // Update tblservice with kepala mekanik data
    $query = "UPDATE tblservice SET 
              kepala_mekanik1 = '$kepala_mekanik1',
              kepala_mekanik2 = '$kepala_mekanik2',
              persen_kepala_mekanik1 = $persen_kepala1,
              persen_kepala_mekanik2 = $persen_kepala2
              WHERE no_service = '$no_service'";
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode([
            'success' => true,
            'message' => 'Kepala mekanik berhasil disimpan',
            'data' => [
                'no_service' => $no_service,
                'kepala_mekanik1' => $kepala_mekanik1,
                'kepala_mekanik2' => $kepala_mekanik2,
                'persen_kepala1' => $persen_kepala1,
                'persen_kepala2' => $persen_kepala2
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan kepala mekanik: ' . mysqli_error($koneksi)
        ]);
    }
    
    mysqli_close($koneksi);
    exit;
}
?>
