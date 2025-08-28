<?php
// File: ajax-get-keluhan-workorder-list.php
// AJAX endpoint untuk mendapatkan list keluhan dan workorder

session_start();

// Security check
if(empty($_SESSION['_iduser'])){
    exit('Unauthorized access');
}

include "../config/koneksi.php";

// Get input parameter
$no_service = isset($_GET['no_service']) ? trim($_GET['no_service']) : '';

if(empty($no_service)) {
    echo '<tr><td colspan="8" class="text-center text-danger">No service parameter required</td></tr>';
    exit;
}

try {
    // Escape input untuk mencegah SQL injection
    $no_service = mysqli_real_escape_string($koneksi, $no_service);
    
    // Query gabungan keluhan dan workorder
    $query_combined = "SELECT 
                            k.id as keluhan_id,
                            k.keluhan,
                            k.status_pengerjaan as status_keluhan,
                            k.auto_workorder,
                            k.workorder_applied,
                            COALESCE(mk.kode_keluhan, '') as kode_keluhan,
                            COALESCE(mk.nama_keluhan, k.keluhan) as nama_keluhan,
                            COALESCE(mk.kategori, 'Umum') as kategori,
                            COALESCE(mk.tingkat_prioritas, 'sedang') as tingkat_prioritas,
                            COALESCE(mk.estimasi_waktu, 0) as estimasi_waktu,
                            wo.kode_wo,
                            wo.nama_wo,
                            wo.harga as harga_wo,
                            wo.waktu as waktu_wo,
                            sw.status_pengerjaan as status_wo,
                            sw.id as workorder_service_id
                         FROM tbservis_keluhan_status k 
                         LEFT JOIN view_master_keluhan mk ON (k.keluhan = mk.kode_keluhan OR k.keluhan = mk.nama_keluhan)
                         LEFT JOIN tbservis_workorder sw ON (k.no_service = sw.no_service AND k.auto_workorder = sw.kode_wo)
                         LEFT JOIN tbworkorderheader wo ON sw.kode_wo = wo.kode_wo
                         WHERE k.no_service = '$no_service' 
                         ORDER BY k.id ASC";
    
    $result_combined = mysqli_query($koneksi, $query_combined);
    
    if($result_combined && mysqli_num_rows($result_combined) > 0) {
        $no = 1;
        while($row = mysqli_fetch_array($result_combined)) {
            $prioritas_class = '';
            switch($row['tingkat_prioritas']) {
                case 'darurat': $prioritas_class = 'label-danger'; break;
                case 'tinggi': $prioritas_class = 'label-warning'; break;
                case 'sedang': $prioritas_class = 'label-info'; break;
                case 'rendah': $prioritas_class = 'label-success'; break;
                default: $prioritas_class = 'label-default';
            }
            
            echo "<tr>";
            echo "<td class='text-center'>" . $no . "</td>";
            echo "<td>" . htmlspecialchars($row['kode_keluhan']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_keluhan']) . "</td>";
            echo "<td>" . htmlspecialchars($row['kode_wo'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_wo'] ?? '-') . "</td>";
            echo "<td class='text-center'>";
            echo "<span class='label $prioritas_class'>" . ucfirst($row['tingkat_prioritas']) . "</span>";
            echo "</td>";
            echo "<td class='text-center'>";
            $total_waktu = ($row['estimasi_waktu'] ?? 0) + ($row['waktu_wo'] ?? 0);
            echo $total_waktu . " menit";
            echo "</td>";
            echo "<td class='text-center'>";
            
            // Dropdown action button
            echo "<div class='btn-group'>";
            echo "<button type='button' class='btn btn-xs btn-danger' onclick='hapusKeluhanWorkorder(" . $row['keluhan_id'] . ")' title='Hapus Keluhan & WO'>";
            echo "<i class='ace-icon fa fa-trash'></i>";
            echo "</button>";
            
            // Jika ada workorder, tampilkan tombol status
            if($row['kode_wo']) {
                echo "<button type='button' class='btn btn-xs btn-info' onclick='updateStatusWorkorder(" . $row['workorder_service_id'] . ")' title='Update Status WO'>";
                echo "<i class='ace-icon fa fa-edit'></i>";
                echo "</button>";
            }
            
            echo "</div>";
            echo "</td>";
            echo "</tr>";
            $no++;
        }
    } else {
        echo "<tr>";
        echo "<td colspan='8' class='text-center text-muted'>";
        echo "<i class='fa fa-info-circle'></i> Belum ada keluhan dan workorder yang ditambahkan";
        echo "</td>";
        echo "</tr>";
    }
    
} catch (Exception $e) {
    echo "<tr>";
    echo "<td colspan='8' class='text-center text-danger'>";
    echo "<i class='fa fa-exclamation-triangle'></i> Error loading data: " . htmlspecialchars($e->getMessage());
    echo "</td>";
    echo "</tr>";
}
?>