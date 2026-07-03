<?php
session_start();
if(empty($_SESSION['_iduser'])){ echo json_encode([]); exit; }
include "../config/koneksi.php";

header('Content-Type: application/json');

// ?search= returns array of suggestions for autocomplete
if(isset($_GET['search'])){
    $s = mysqli_real_escape_string($koneksi, trim($_GET['search']));
    if(strlen($s) < 1){ echo json_encode([]); exit; }
    $q = mysqli_query($koneksi,"SELECT noitem, namaitem, hargapokok FROM tblitem WHERE noitem LIKE '$s%' OR namaitem LIKE '%$s%' LIMIT 20");
    $out = [];
    while($q && $r = mysqli_fetch_assoc($q)){
        $out[] = ['noitem'=>$r['noitem'],'namaitem'=>$r['namaitem'],'hargapokok'=>(float)$r['hargapokok']];
    }
    echo json_encode($out);
    exit;
}

// ?kode= returns single exact match
$kode = isset($_GET['kode']) ? mysqli_real_escape_string($koneksi, trim($_GET['kode'])) : '';
if(!$kode){ echo json_encode([]); exit; }

$q = mysqli_query($koneksi,"SELECT noitem, namaitem, hargapokok FROM tblitem WHERE noitem='$kode' OR kodebarcode='$kode' LIMIT 1");
if($q && mysqli_num_rows($q)>0){
    $r = mysqli_fetch_assoc($q);
    echo json_encode([
        'noitem'     => $r['noitem'],
        'namaitem'   => $r['namaitem'],
        'hargapokok' => (float)$r['hargapokok'],
    ]);
} else {
    echo json_encode([]);
}
