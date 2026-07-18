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

$kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

if($kategori !== ''){
    $esc = mysqli_real_escape_string($koneksi, $kategori);
    $q = mysqli_query($koneksi, "SELECT id_jenis, kategori, nama_masalah, skema_field, role_approval, target_eksekusi
        FROM master_jenis_masalah WHERE kategori='{$esc}' AND is_active=1 ORDER BY id_jenis");
} else {
    $q = mysqli_query($koneksi, "SELECT id_jenis, kategori, nama_masalah, skema_field, role_approval, target_eksekusi
        FROM master_jenis_masalah WHERE is_active=1 ORDER BY kategori, id_jenis");
}

$result = [];
while($r = mysqli_fetch_assoc($q)){
    // Parsing skema_field untuk ambil deskripsi_singkat
    $skema = json_decode($r['skema_field'], true);
    $deskripsi_singkat = '';
    if(is_array($skema) && count($skema) > 0){
        $wajib_count = 0;
        foreach($skema as $f){
            if(!empty($f['wajib'])) $wajib_count++;
        }
        $deskripsi_singkat = count($skema) . ' field' . ($wajib_count > 0 ? ', ' . $wajib_count . ' wajib' : '');
    }
    $result[] = [
        'id_jenis'         => (int)$r['id_jenis'],
        'kategori'          => $r['kategori'],
        'nama_masalah'      => $r['nama_masalah'],
        'skema_field'       => $r['skema_field'],
        'role_approval'     => $r['role_approval'],
        'target_eksekusi'   => $r['target_eksekusi'],
        'deskripsi_singkat' => $deskripsi_singkat,
    ];
}

echo json_encode($result);
