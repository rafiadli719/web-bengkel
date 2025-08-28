<?php
session_start();

if(empty($_SESSION['_iduser'])){
    echo "<tr><td colspan='7' class='text-center text-danger'>Session expired</td></tr>";
    exit;
}

include "../config/koneksi.php";

$no_service = $_GET['no_service'] ?? '';

if(empty($no_service)) {
    echo "<tr><td colspan='7' class='text-center text-muted'>No service number provided</td></tr>";
    exit;
}

try {
    $query_keluhan = "SELECT k.*, 
                           COALESCE(mk.nama_keluhan, k.keluhan) as nama_keluhan,
                           COALESCE(mk.kategori, 'Umum') as kategori,
                           COALESCE(mk.tingkat_prioritas, 'sedang') as tingkat_prioritas,
                           COALESCE(mk.estimasi_waktu, 0) as estimasi_waktu,
                           COALESCE(mk.kode_keluhan, '') as kode_keluhan
                     FROM tbservis_keluhan_status k 
                     LEFT JOIN view_master_keluhan mk ON (k.keluhan = mk.kode_keluhan OR k.keluhan = mk.nama_keluhan)
                     WHERE k.no_service = '$no_service' 
                     ORDER BY k.id ASC";
    $result_keluhan = mysqli_query($koneksi, $query_keluhan);
    
    if($result_keluhan && mysqli_num_rows($result_keluhan) > 0) {
        $no = 1;
        while($row_keluhan = mysqli_fetch_array($result_keluhan)) {
            $prioritas_class = '';
            switch($row_keluhan['tingkat_prioritas']) {
                case 'darurat': $prioritas_class = 'label-danger'; break;
                case 'tinggi': $prioritas_class = 'label-warning'; break;
                case 'sedang': $prioritas_class = 'label-info'; break;
                case 'rendah': $prioritas_class = 'label-success'; break;
                default: $prioritas_class = 'label-default';
            }
            
            echo "<tr>";
            echo "<td class='text-center'>" . $no . "</td>";
            echo "<td>" . htmlspecialchars($row_keluhan['kode_keluhan']) . "</td>";
            echo "<td>" . htmlspecialchars($row_keluhan['nama_keluhan']) . "</td>";
            echo "<td>" . htmlspecialchars($row_keluhan['kategori']) . "</td>";
            echo "<td class='text-center'>";
            echo "<span class='label $prioritas_class'>" . ucfirst($row_keluhan['tingkat_prioritas']) . "</span>";
            echo "</td>";
            echo "<td class='text-center'>" . $row_keluhan['estimasi_waktu'] . " menit</td>";
            echo "<td class='text-center'>";
            echo "<button type='button' class='btn btn-xs btn-danger' onclick='hapusKeluhan(" . $row_keluhan['id'] . ")' title='Hapus Keluhan'>";
            echo "<i class='ace-icon fa fa-trash'></i>";
            echo "</button>";
            echo "</td>";
            echo "</tr>";
            $no++;
        }
    } else {
        echo "<tr>";
        echo "<td colspan='7' class='text-center text-muted'>";
        echo "<i class='fa fa-info-circle'></i> Belum ada keluhan yang ditambahkan untuk service ini";
        echo "</td>";
        echo "</tr>";
    }
} catch (Exception $e) {
    echo "<tr>";
    echo "<td colspan='7' class='text-center text-danger'>";
    echo "<i class='fa fa-exclamation-triangle'></i> Error loading keluhan data: " . $e->getMessage();
    echo "</td>";
    echo "</tr>";
}
?>
