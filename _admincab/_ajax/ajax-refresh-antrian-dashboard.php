<?php
session_start();
include "../../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    $response['success'] = false;
    $response['message'] = 'Session expired';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$response = array();

try {
    $tgl_hari_ini = date('Y-m-d');
    
    // Ambil statistik antrian servis hari ini
    $query_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini'");
    $total_antrian = mysqli_fetch_array($query_total)['total'];
    
    $query_menunggu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'menunggu'");
    $antrian_menunggu = mysqli_fetch_array($query_menunggu)['total'];
    
    $query_diproses = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'diproses'");
    $antrian_diproses = mysqli_fetch_array($query_diproses)['total'];
    
    $query_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'selesai'");
    $antrian_selesai = mysqli_fetch_array($query_selesai)['total'];
    
    // Ambil daftar antrian terbaru
    $antrian_terbaru = array();
    $query_antrian_terbaru = mysqli_query($koneksi, "
        SELECT a.*, p.namapelanggan, s.no_polisi 
        FROM tb_antrian_servis a 
        LEFT JOIN tblservice s ON a.no_service = s.no_service 
        LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
        WHERE a.tanggal = '$tgl_hari_ini' 
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    
    while($row = mysqli_fetch_array($query_antrian_terbaru)) {
        $antrian_terbaru[] = array(
            'no_antrian' => $row['no_antrian'],
            'no_service' => $row['no_service'],
            'nama_pelanggan' => $row['namapelanggan'] ? $row['namapelanggan'] : 'N/A',
            'no_polisi' => $row['no_polisi'] ? $row['no_polisi'] : 'N/A',
            'jam_ambil' => date('H:i', strtotime($row['jam_ambil'])),
            'prioritas' => $row['prioritas'],
            'status_antrian' => $row['status_antrian'],
            'estimasi_waktu' => $row['estimasi_waktu'] ? $row['estimasi_waktu'] . ' menit' : '-'
        );
    }
    
    // Ambil daftar mekanik yang sedang bekerja
    $mekanik_bekerja = array();
    $query_mekanik_bekerja = mysqli_query($koneksi, "
        SELECT p.*, a.no_antrian, a.no_service, p.nama_mekanik
        FROM tb_progress_mekanik p
        JOIN tb_antrian_servis a ON p.no_service = a.no_service
        WHERE a.tanggal = '$tgl_hari_ini' 
        AND p.status_kerja = 'bekerja'
        ORDER BY p.jam_mulai DESC
        LIMIT 5
    ");
    
    while($row = mysqli_fetch_array($query_mekanik_bekerja)) {
        $mekanik_bekerja[] = array(
            'nama_mekanik' => $row['nama_mekanik'] ? $row['nama_mekanik'] : 'Mekanik #' . $row['id_mekanik'],
            'jenis_kerja' => $row['jenis_mekanik'],
            'no_antrian' => $row['no_antrian'],
            'no_service' => $row['no_service'],
            'persentase_progress' => $row['persen_kerja'],
            'jam_mulai' => date('H:i', strtotime($row['jam_mulai']))
        );
    }
    
    $response['success'] = true;
    $response['data'] = array(
        'statistik' => array(
            'total' => $total_antrian,
            'menunggu' => $antrian_menunggu,
            'diproses' => $antrian_diproses,
            'selesai' => $antrian_selesai
        ),
        'antrian_terbaru' => $antrian_terbaru,
        'mekanik_bekerja' => $mekanik_bekerja,
        'last_update' => date('H:i:s')
    );
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
