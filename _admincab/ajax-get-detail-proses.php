<?php
// File: ajax-get-detail-proses.php
session_start();
include "../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    echo '<div class="alert alert-danger">Session expired</div>';
    exit;
}

if(!isset($_POST['keluhan_id'])) {
    echo '<div class="alert alert-danger">Keluhan ID tidak ditemukan</div>';
    exit;
}

$keluhan_id = $_POST['keluhan_id'];

try {
    // Get keluhan info
    $sql_keluhan = mysqli_query($koneksi,"SELECT k.*, mk.kode_keluhan, mk.nama_keluhan 
                                         FROM tbservis_keluhan_status k 
                                         LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
                                         WHERE k.id='$keluhan_id'");
    
    $keluhan_data = mysqli_fetch_array($sql_keluhan);
    
    if(!$keluhan_data) {
        echo '<div class="alert alert-danger">Data keluhan tidak ditemukan</div>';
        exit;
    }
    
    // Get proses list if keluhan has master
    if($keluhan_data['kode_keluhan']) {
        $kode_keluhan = $keluhan_data['kode_keluhan'];
        
        $sql_proses = mysqli_query($koneksi,"SELECT kp.*, 
                                            COALESCE(kt.status_proses, 'pending') as current_status,
                                            kt.mekanik_id, kt.waktu_mulai, kt.waktu_selesai, kt.catatan, kt.biaya_actual
                                            FROM tbkeluhan_proses kp
                                            LEFT JOIN tbservis_keluhan_tracking kt ON kp.id = kt.proses_id AND kt.keluhan_id = '$keluhan_id'
                                            WHERE kp.kode_keluhan='$kode_keluhan' AND kp.status_aktif='1'
                                            ORDER BY kp.urutan ASC, kp.nama_proses ASC");
        
        echo '<div class="row">';
        echo '<div class="col-md-12">';
        echo '<h5><strong>Keluhan:</strong> ' . htmlspecialchars($keluhan_data['keluhan']) . '</h5>';
        echo '<p><strong>Master:</strong> ' . $keluhan_data['nama_keluhan'] . ' (' . $keluhan_data['kode_keluhan'] . ')</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th width="5%">No</th>';
        echo '<th width="10%">Tipe</th>';
        echo '<th width="25%">Proses</th>';
        echo '<th width="15%">Status</th>';
        echo '<th width="15%">Mekanik</th>';
        echo '<th width="15%">Waktu</th>';
        echo '<th width="15%">Catatan</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $no = 1;
        while($proses = mysqli_fetch_array($sql_proses)) {
            $status_class = '';
            $status_text = '';
            
            switch($proses['current_status']) {
                case 'pending':
                    $status_class = 'label-default';
                    $status_text = 'Pending';
                    break;
                case 'dikerjakan':
                    $status_class = 'label-warning';
                    $status_text = 'Dikerjakan';
                    break;
                case 'selesai':
                    $status_class = 'label-success';
                    $status_text = 'Selesai';
                    break;
                case 'skip':
                    $status_class = 'label-info';
                    $status_text = 'Skip';
                    break;
            }
            
            echo '<tr>';
            echo '<td class="center">' . $no++ . '</td>';
            echo '<td><span class="label label-' . ($proses['tipe_proses']=='jasa'?'primary':(($proses['tipe_proses']=='barang')?'success':'warning')) . '">' . strtoupper($proses['tipe_proses']) . '</span></td>';
            echo '<td>';
            echo '<strong>' . htmlspecialchars($proses['nama_proses']) . '</strong>';
            if($proses['wajib'] == '1') {
                echo ' <i class="fa fa-asterisk text-danger" title="Wajib"></i>';
            }
            if($proses['deskripsi']) {
                echo '<br><small class="text-muted">' . htmlspecialchars($proses['deskripsi']) . '</small>';
            }
            echo '</td>';
            echo '<td>';
            echo '<select class="form-control input-sm proses-status" data-proses-id="' . $proses['id'] . '">';
            echo '<option value="pending"' . ($proses['current_status']=='pending'?' selected':'') . '>Pending</option>';
            echo '<option value="dikerjakan"' . ($proses['current_status']=='dikerjakan'?' selected':'') . '>Dikerjakan</option>';
            echo '<option value="selesai"' . ($proses['current_status']=='selesai'?' selected':'') . '>Selesai</option>';
            echo '<option value="skip"' . ($proses['current_status']=='skip'?' selected':'') . '>Skip</option>';
            echo '</select>';
            echo '</td>';
            echo '<td>';
            echo '<select class="form-control input-sm proses-mekanik" data-proses-id="' . $proses['id'] . '">';
            echo '<option value="">- Pilih Mekanik -</option>';
            
            // Get mekanik list
            $sql_mekanik = mysqli_query($koneksi,"SELECT nomekanik, nama FROM tblmekanik WHERE nama<>'-' ORDER BY nama ASC");
            while($mekanik = mysqli_fetch_array($sql_mekanik)) {
                $selected = ($proses['mekanik_id'] == $mekanik['nomekanik']) ? ' selected' : '';
                echo '<option value="' . $mekanik['nomekanik'] . '"' . $selected . '>' . $mekanik['nama'] . '</option>';
            }
            
            echo '</select>';
            echo '</td>';
            echo '<td>';
            if($proses['waktu_mulai']) {
                echo '<small>Mulai: ' . date('H:i', strtotime($proses['waktu_mulai'])) . '</small><br>';
            }
            if($proses['waktu_selesai']) {
                echo '<small>Selesai: ' . date('H:i', strtotime($proses['waktu_selesai'])) . '</small>';
            }
            echo '</td>';
            echo '<td>';
            echo '<textarea class="form-control input-sm proses-catatan" rows="2" data-proses-id="' . $proses['id'] . '" placeholder="Catatan...">' . htmlspecialchars($proses['catatan']) . '</textarea>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        // Hidden field for keluhan_id
        echo '<input type="hidden" id="current-keluhan-id" value="' . $keluhan_id . '">';
        
    } else {
        echo '<div class="alert alert-info">';
        echo '<h5><strong>Keluhan:</strong> ' . htmlspecialchars($keluhan_data['keluhan']) . '</h5>';
        echo '<p>Keluhan ini tidak memiliki master proses. Anda bisa menambahkan proses manual jika diperlukan.</p>';
        echo '</div>';
    }
    
} catch(Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}
?>

<script>
// Auto save when status or mekanik changes
$(document).ready(function() {
    $('.proses-status, .proses-mekanik').on('change', function() {
        var prosesId = $(this).data('proses-id');
        autoSaveProses(prosesId);
    });
    
    $('.proses-catatan').on('blur', function() {
        var prosesId = $(this).data('proses-id');
        autoSaveProses(prosesId);
    });
});

function autoSaveProses(prosesId) {
    var keluhanId = $('#current-keluhan-id').val();
    var status = $('.proses-status[data-proses-id="' + prosesId + '"]').val();
    var mekanikId = $('.proses-mekanik[data-proses-id="' + prosesId + '"]').val();
    var catatan = $('.proses-catatan[data-proses-id="' + prosesId + '"]').val();
    
    $.ajax({
        url: 'ajax-save-proses-tracking.php',
        method: 'POST',
        data: {
            keluhan_id: keluhanId,
            proses_id: prosesId,
            status_proses: status,
            mekanik_id: mekanikId,
            catatan: catatan
        },
        success: function(response) {
            // Optional: show save indicator
            console.log('Auto saved proses ' + prosesId);
        },
        error: function() {
            console.log('Error saving proses ' + prosesId);
        }
    });
}
</script>