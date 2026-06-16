<?php
session_start();

if(empty($_SESSION['_iduser'])){
    echo "<tr><td colspan='5' class='text-center text-danger'>Session expired</td></tr>";
    exit;
}

include "../config/koneksi.php";

$no_service = $_GET['no_service'] ?? '';

if(empty($no_service)) {
    echo "<tr><td colspan='5' class='text-center text-muted'>No service number provided</td></tr>";
    exit;
}

try {
    // Gunakan logika yang sama dengan _servis_add_header_kanan_workorder_only.php
    // Prioritaskan load dari VIEW jika ada, jika tidak ada fallback ke tabel status langsung
    $query_keluhan = "SELECT * FROM view_servis_keluhan_lengkap WHERE no_service = '$no_service' ORDER BY created_at DESC";
    $result_keluhan = mysqli_query($koneksi, $query_keluhan);

    if(!$result_keluhan){
        // Fallback sederhana (tanpa kolom badge dari VIEW)
        $query_keluhan = "SELECT id, keluhan, status_pengerjaan, keterangan_tidak_selesai, created_at FROM tbservis_keluhan_status WHERE no_service='$no_service' ORDER BY created_at DESC";
        $result_keluhan = mysqli_query($koneksi, $query_keluhan);
    }
    
    if($result_keluhan && mysqli_num_rows($result_keluhan) > 0) {
        $no = 1;
        while($row_keluhan = mysqli_fetch_array($result_keluhan)) {
            // Highlight row jika tidak selesai
            $row_class = ($row_keluhan['status_pengerjaan'] == 'tidak_selesai') ? 'class="danger"' : '';
            
            // Icon status
            $status_icon = '';
            switch($row_keluhan['status_pengerjaan']) {
                case 'selesai': $status_icon = '<i class="ace-icon fa fa-check"></i> '; break;
                case 'diproses': $status_icon = '<i class="ace-icon fa fa-cog fa-spin"></i> '; break;
                case 'tidak_selesai': $status_icon = '<i class="ace-icon fa fa-times"></i> '; break;
                case 'datang': $status_icon = '<i class="ace-icon fa fa-clock-o"></i> '; break;
                default: $status_icon = '<i class="ace-icon fa fa-info-circle"></i> '; break;
            }
            
            // Badge color fallback jika kolom view tidak tersedia
            $badge_color = isset($row_keluhan['status_badge_color']) ? $row_keluhan['status_badge_color'] : (
                $row_keluhan['status_pengerjaan'] == 'selesai' ? 'success' : (
                $row_keluhan['status_pengerjaan'] == 'diproses' ? 'warning' : (
                $row_keluhan['status_pengerjaan'] == 'tidak_selesai' ? 'danger' : 'info')));
            
            echo "<tr $row_class>";
            
            // Column 1: No
            echo "<td class='text-center'>" . $no . "</td>";
            
            // Column 2: Keluhan
            echo "<td>" . htmlspecialchars($row_keluhan['keluhan']) . "</td>";
            
            // Column 3: Status
            echo "<td class='text-center'>";
            echo "<span class='label label-" . $badge_color . "'>";
            echo $status_icon . strtoupper($row_keluhan['status_pengerjaan']);
            echo "</span>";
            echo "</td>";
            
            // Column 4: Keterangan
            echo "<td>";
            if($row_keluhan['status_pengerjaan'] == 'tidak_selesai' && !empty($row_keluhan['keterangan_tidak_selesai'])) {
                echo "<strong class='text-danger'>" . htmlspecialchars($row_keluhan['keterangan_tidak_selesai']) . "</strong>";
            } else {
                echo "<em class='text-muted'>-</em>";
            }
            echo "</td>";
            
            // Column 5: Aksi (Edit & Delete)
            echo "<td class='text-center'>";
            
            // Tombol Update Status (Edit)
            echo "<button type='button' class='btn btn-xs btn-info btn-update-status-keluhan' ";
            echo "data-id='" . $row_keluhan['id'] . "' ";
            echo "data-keluhan='" . htmlspecialchars($row_keluhan['keluhan'], ENT_QUOTES) . "' ";
            echo "data-status='" . $row_keluhan['status_pengerjaan'] . "' ";
            echo "data-keterangan='" . htmlspecialchars($row_keluhan['keterangan_tidak_selesai'] ?? '', ENT_QUOTES) . "' ";
            echo "title='Update Status'>";
            echo "<i class='ace-icon fa fa-edit'></i>";
            echo "</button> ";
            
            // Tombol Hapus
            echo "<button type='button' class='btn btn-xs btn-danger' onclick='hapusKeluhan(" . $row_keluhan['id'] . ")' title='Hapus Keluhan'>";
            echo "<i class='ace-icon fa fa-trash'></i>";
            echo "</button>";
            
            echo "</td>";
            echo "</tr>";
            $no++;
        }
    } else {
        echo "<tr>";
        echo "<td colspan='5' class='text-center text-muted'>";
        echo "<i class='fa fa-info-circle'></i> Belum ada keluhan yang ditambahkan untuk service ini";
        echo "</td>";
        echo "</tr>";
    }
} catch (Exception $e) {
    echo "<tr>";
    echo "<td colspan='5' class='text-center text-danger'>";
    echo "<i class='fa fa-exclamation-triangle'></i> Error loading keluhan data: " . $e->getMessage();
    echo "</td>";
    echo "</tr>";
}
?>
