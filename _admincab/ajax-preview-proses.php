<?php
// File: ajax-preview-proses.php
session_start();
if(empty($_SESSION['_iduser'])){
    echo '<div class="alert alert-danger">Session expired</div>';
    exit;
}

include "../config/koneksi.php";

if(isset($_POST['kode_keluhan'])) {
    $kode_keluhan = $_POST['kode_keluhan'];
    
    // Get keluhan info
    $sql_keluhan = mysqli_query($koneksi, "SELECT * FROM tbmaster_keluhan WHERE kode_keluhan='$kode_keluhan'");
    $keluhan = mysqli_fetch_array($sql_keluhan);
    
    if(!$keluhan) {
        echo '<div class="alert alert-danger">Keluhan tidak ditemukan</div>';
        exit;
    }
    
    // Get proses list
    $sql_proses = mysqli_query($koneksi, "SELECT * FROM tbkeluhan_proses 
                                         WHERE kode_keluhan='$kode_keluhan' 
                                         AND status_aktif='1'
                                         ORDER BY urutan ASC");
    
    echo '<div class="panel panel-info">';
    echo '<div class="panel-heading">';
    echo '<h4><i class="fa fa-info-circle"></i> ' . $keluhan['nama_keluhan'] . '</h4>';
    echo '<small>' . $keluhan['deskripsi'] . '</small>';
    echo '</div>';
    echo '<div class="panel-body">';
    
    if(mysqli_num_rows($sql_proses) > 0) {
        echo '<h5><i class="fa fa-cogs"></i> Proses Pengerjaan:</h5>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-condensed">';
        echo '<thead>';
        echo '<tr class="info">';
        echo '<th width="5%">No</th>';
        echo '<th width="35%">Nama Proses</th>';
        echo '<th width="20%">Admin/Kasir</th>';
        echo '<th width="15%">Estimasi</th>';
        echo '<th width="10%">Wajib</th>';
        echo '<th width="15%">Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $no = 1;
        while($proses = mysqli_fetch_array($sql_proses)) {
            $wajib_class = $proses['wajib'] == '1' ? 'text-danger' : 'text-muted';
            $wajib_icon = $proses['wajib'] == '1' ? 'fa-exclamation-triangle' : 'fa-circle-o';
            $wajib_text = $proses['wajib'] == '1' ? 'Wajib' : 'Opsional';
            
            echo '<tr>';
            echo '<td>' . $no . '</td>';
            echo '<td><strong>' . $proses['nama_proses'] . '</strong><br>';
            echo '<small class="text-muted">' . ($proses['deskripsi_proses'] ?? '') . '</small></td>';
            echo '<td>';
            if(!empty($proses['admin_name'])) {
                echo '<span class="label label-success"><i class="fa fa-user"></i> ' . $proses['admin_name'] . '</span>';
            } else {
                echo '<span class="label label-warning"><i class="fa fa-user-plus"></i> Belum Assign</span>';
            }
            echo '</td>';
            echo '<td><small>' . $proses['estimasi_waktu'] . ' menit</small></td>';
            echo '<td><span class="' . $wajib_class . '"><i class="fa ' . $wajib_icon . '"></i> ' . $wajib_text . '</span></td>';
            echo '<td>';
            
            // Status berdasarkan kondisi
            $status_class = 'default';
            $status_text = 'Belum Mulai';
            $status_icon = 'fa-circle-o';
            
            $status_proses = $proses['status_proses'] ?? '';
            if($status_proses == 'progress') {
                $status_class = 'warning';
                $status_text = 'Sedang Proses';
                $status_icon = 'fa-cogs';
            } elseif($status_proses == 'completed') {
                $status_class = 'success';
                $status_text = 'Selesai';
                $status_icon = 'fa-check';
            } elseif($status_proses == 'pending') {
                $status_class = 'info';
                $status_text = 'Menunggu';
                $status_icon = 'fa-clock-o';
            }
            
            echo '<span class="label label-' . $status_class . '">';
            echo '<i class="fa ' . $status_icon . '"></i> ' . $status_text;
            echo '</span>';
            echo '</td>';
            echo '</tr>';
            $no++;
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        // Summary
        $total_proses = mysqli_num_rows($sql_proses);
        $sql_wajib = mysqli_query($koneksi, "SELECT COUNT(*) as total_wajib FROM tbkeluhan_proses WHERE kode_keluhan='$kode_keluhan' AND wajib='1' AND status_aktif='1'");
        $wajib_count = mysqli_fetch_array($sql_wajib)['total_wajib'];
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<div class="alert alert-info">';
        echo '<i class="fa fa-info-circle"></i> ';
        echo '<strong>Total Proses:</strong> ' . $total_proses . ' langkah<br>';
        echo '<strong>Proses Wajib:</strong> ' . $wajib_count . ' langkah<br>';
        echo '<strong>Estimasi Total:</strong> ' . $keluhan['estimasi_waktu'] . ' menit';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<div class="alert alert-warning">';
        echo '<i class="fa fa-exclamation-triangle"></i> ';
        echo '<strong>Catatan:</strong><br>';
        echo 'Proses wajib harus diselesaikan sebelum service dianggap selesai.';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
    } else {
        echo '<div class="alert alert-info">';
        echo '<i class="fa fa-info-circle"></i> ';
        echo 'Keluhan ini tidak memiliki proses pengerjaan khusus. ';
        echo 'Admin/Kasir dapat menangani secara manual sesuai kebutuhan.';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
} else {
    echo '<div class="alert alert-danger">Parameter tidak lengkap</div>';
}
?>
