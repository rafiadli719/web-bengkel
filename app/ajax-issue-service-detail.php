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

$no_service = isset($_GET['no_service']) ? trim($_GET['no_service']) : '';
if($no_service === ''){
    echo json_encode(['error'=>'No service wajib diisi']);
    exit;
}
$esc = mysqli_real_escape_string($koneksi, $no_service);
$q = mysqli_query($koneksi, "SELECT no_service,status_servis,no_polisi,subtotal_jasa,subtotal_item,
    mekanik1,mekanik2,mekanik3,mekanik4,
    kepala_mekanik1,kepala_mekanik2,admin1,admin2,
    persen_mekanik1,persen_mekanik2,persen_mekanik3,persen_mekanik4,
    persen_kepala_mekanik1,persen_kepala_mekanik2,persen_admin1,persen_admin2
    FROM tblservice WHERE no_service='{$esc}' LIMIT 1");
$row = $q ? mysqli_fetch_assoc($q) : null;
if(!$row){
    echo json_encode(['error'=>'Service tidak ditemukan']);
    exit;
}

$pilihan_mekanik = [];
for($i=1;$i<=4;$i++){
    $nama = trim((string)($row['mekanik'.$i] ?? ''));
    $persen = (int)($row['persen_mekanik'.$i] ?? 0);
    if($nama !== '' || $persen > 0){
        $pilihan_mekanik[] = [
            'value' => (string)$i,
            'peran' => 'mekanik'.$i,
            'label' => 'Mekanik '.$i.($nama !== '' ? ' - '.$nama : '').' ('.$persen.'%)',
            'nama' => $nama,
            'persen_lama' => $persen,
        ];
    }
}
if(count($pilihan_mekanik) === 0){
    for($i=1;$i<=4;$i++){
        $pilihan_mekanik[] = [
            'value' => (string)$i,
            'peran' => 'mekanik'.$i,
            'label' => 'Mekanik '.$i.' (0%)',
            'nama' => '',
            'persen_lama' => 0,
        ];
    }
}

$pilihan_peran_komisi = [];
for($i=1;$i<=4;$i++){
    $nama = trim((string)($row['mekanik'.$i] ?? ''));
    $persen = (int)($row['persen_mekanik'.$i] ?? 0);
    $pilihan_peran_komisi[] = ['value'=>'mekanik'.$i, 'label'=>'Mekanik '.$i.($nama!==''?' - '.$nama:'').' ('.$persen.'%)', 'persen_lama'=>$persen];
}
for($i=1;$i<=2;$i++){
    $nama = trim((string)($row['kepala_mekanik'.$i] ?? ''));
    $persen = (int)($row['persen_kepala_mekanik'.$i] ?? 0);
    $pilihan_peran_komisi[] = ['value'=>'kepala_mekanik'.$i, 'label'=>'Kepala Mekanik '.$i.($nama!==''?' - '.$nama:'').' ('.$persen.'%)', 'persen_lama'=>$persen];
}
for($i=1;$i<=2;$i++){
    $nama = trim((string)($row['admin'.$i] ?? ''));
    $persen = (int)($row['persen_admin'.$i] ?? 0);
    $pilihan_peran_komisi[] = ['value'=>'admin'.$i, 'label'=>'Admin '.$i.($nama!==''?' - '.$nama:'').' ('.$persen.'%)', 'persen_lama'=>$persen];
}

$persen_opsi = [0,10,20,25,30,40,50,60,70,75,80,90,100];
$alasan_komisi = [
    'Salah input persentase',
    'Mekanik tidak ikut kerja',
    'Mekanik tambahan belum dicatat',
    'Pembagian kerja direvisi Supervisor',
    'Koreksi payroll setelah verifikasi'
];
$alasan_nopol = [
    'Salah pilih kendaraan',
    'Typo input nopol',
    'Kendaraan pelanggan tertukar',
    'Koreksi data setelah validasi CS'
];

echo json_encode([
    'service' => [
        'no_service' => $row['no_service'],
        'status_servis' => $row['status_servis'],
        'no_polisi' => $row['no_polisi'],
        'subtotal_jasa' => $row['subtotal_jasa'],
        'subtotal_item' => $row['subtotal_item'],
    ],
    'pilihan_mekanik' => $pilihan_mekanik,
    'pilihan_peran_komisi' => $pilihan_peran_komisi,
    'persen_opsi' => $persen_opsi,
    'alasan_komisi' => $alasan_komisi,
    'alasan_nopol' => $alasan_nopol,
], JSON_UNESCAPED_UNICODE);
