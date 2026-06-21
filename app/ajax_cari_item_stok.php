<?php
session_start();
if(empty($_SESSION['_iduser'])){ echo json_encode([]); exit; }

include "../config/koneksi.php";

$q = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, trim($_GET['q'])) : '';
if(strlen($q) < 2) { echo json_encode([]); exit; }

$sql = mysqli_query($koneksi,
    "SELECT noitem, namaitem FROM tblitem
     WHERE (noitem LIKE '%$q%' OR namaitem LIKE '%$q%')
     ORDER BY noitem LIMIT 20");

$result = [];
while($r = mysqli_fetch_assoc($sql)) {
    $result[] = [
        'label' => $r['noitem'].' - '.$r['namaitem'],
        'value' => $r['noitem'],
    ];
}
header('Content-Type: application/json');
echo json_encode($result);
?>
