<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if(empty($_SESSION['_iduser'])){
    http_response_code(401);
    die(json_encode(['error'=>'Unauthorized']));
}
include_once "../config/koneksi.php";
if(!isset($koneksi) || !$koneksi){
    http_response_code(500);
    die(json_encode(['error'=>'DB connection failed']));
}
@mysqli_set_charset($koneksi, 'utf8mb4');
header('Content-Type: application/json; charset=utf-8');

$source = isset($_GET['source']) ? trim($_GET['source']) : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if($source === '' || $q === '' || strlen($q) < 2){
    echo json_encode([]);
    exit;
}

$esc = mysqli_real_escape_string($koneksi, $q);
$result = [];

switch($source){
    case 'tblservice_bayar':
        // Autocomplete no_service dengan status_servis='bayar'
        $res = mysqli_query($koneksi, "SELECT s.no_service, s.no_polisi, s.tanggal, s.status_servis, p.namapelanggan
            FROM tblservice s
            LEFT JOIN tblpelanggan p ON p.nopelanggan=s.no_pelanggan
            WHERE status_servis='bayar'
              AND (s.no_service LIKE '%{$esc}%' OR s.no_polisi LIKE '%{$esc}%' OR p.namapelanggan LIKE '%{$esc}%')
            ORDER BY s.no_service DESC LIMIT 20");
        while($r = mysqli_fetch_assoc($res)){
            $labelParts = [
                $r['no_service'],
                ($r['no_polisi'] ?? '-')
            ];
            if(!empty($r['namapelanggan'])) $labelParts[] = $r['namapelanggan'];
            if(!empty($r['tanggal'])) $labelParts[] = $r['tanggal'];
            $result[] = [
                'label' => implode(' | ', $labelParts),
                'value' => $r['no_service'],
                'extras' => []
            ];
        }
        break;

    case 'tblservice':
        // Semua servis (tidak filter bayar)
        $res = mysqli_query($koneksi, "SELECT s.no_service, s.no_polisi, s.status_servis, s.tanggal, p.namapelanggan
            FROM tblservice s
            LEFT JOIN tblpelanggan p ON p.nopelanggan=s.no_pelanggan
            WHERE s.no_service LIKE '%{$esc}%' OR s.no_polisi LIKE '%{$esc}%' OR p.namapelanggan LIKE '%{$esc}%'
            ORDER BY s.no_service DESC LIMIT 20");
        while($r = mysqli_fetch_assoc($res)){
            $labelParts = [
                $r['no_service'],
                ($r['no_polisi'] ?? '-'),
                ($r['status_servis'] ?? '-')
            ];
            if(!empty($r['namapelanggan'])) $labelParts[] = $r['namapelanggan'];
            if(!empty($r['tanggal'])) $labelParts[] = $r['tanggal'];
            $result[] = [
                'label' => implode(' | ', $labelParts),
                'value' => $r['no_service'],
                'extras' => []
            ];
        }
        break;

    case 'pelanggan':
        $res = mysqli_query($koneksi, "SELECT nopelanggan, namapelanggan, no_wa FROM tblpelanggan
            WHERE namapelanggan LIKE '%{$esc}%' OR nopelanggan LIKE '%{$esc}%' OR no_wa LIKE '%{$esc}%' LIMIT 20");
        while($r = mysqli_fetch_assoc($res)){
            $result[] = [
                'label' => $r['namapelanggan'] . ' (' . $r['nopelanggan'] . ') — WA: ' . $r['no_wa'],
                'value' => $r['nopelanggan'],
                'extras' => []
            ];
        }
        break;


    case 'kendaraan':
        $res = mysqli_query($koneksi, "SELECT nopolisi, pemilik FROM tblkendaraan
            WHERE nopolisi LIKE '%{$esc}%' OR pemilik LIKE '%{$esc}%' LIMIT 20");
        while($r = mysqli_fetch_assoc($res)){
            $labelParts = [$r['nopolisi']];
            if(!empty($r['pemilik'])) $labelParts[] = $r['pemilik'];
            $result[] = [
                'label' => implode(' | ', $labelParts),
                'value' => $r['nopolisi'],
                'extras' => []
            ];
        }
        break;

    case 'daftar_mekanik':
        // Dari tbuser_karyawan yang role mekanik
        $res = mysqli_query($koneksi, "SELECT u.id, u.nama_user, u.user_akses FROM tbuser u
            WHERE u.nama_user LIKE '%{$esc}%' AND u.user_akses IN (4,10) LIMIT 20");
        while($r = mysqli_fetch_assoc($res)){
            $result[] = [
                'label' => $r['nama_user'] . ' (ID: ' . $r['id'] . ')',
                'value' => $r['id'],
                'extras' => ['nama_mekanik' => $r['nama_user']]
            ];
        }
        break;

    default:
        // Unknown source, return empty
        break;
}

echo json_encode($result);
