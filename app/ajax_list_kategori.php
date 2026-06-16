<?php
session_start();
if(empty($_SESSION['_iduser'])){
    die("Sesi berakhir");
}

include "../config/koneksi.php";

// Handle Update Request
if(isset($_POST['update_kategori'])) {
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $val = (int)$_POST['val']; // 0 = Dapat, 1 = Tidak
    
    // Invert logic for DB: DB stores 'exclude' (1=Exclude/Tidak Dapat)
    // UI sends: 1 = Dapat (Green), 0 = Tidak (Red)
    // So if UI 1 -> DB 0. If UI 0 -> DB 1.
    // Wait, let's keep it simple.
    // Let's standardise: UI should send 'exclude' value directly?
    // No, toggle ON usually means "Active/Positive".
    // Let's say: Toggle ON (1) means "Dapat Diskon".
    // DB: exclude=0 means "Dapat Diskon".
    // So: DB Value = (UI Value == 1 ? 0 : 1).
    
    $db_exclude = ($val == 1) ? 0 : 1;
    
    $query = "UPDATE tbhargajual SET exclude_diskon_member = $db_exclude WHERE jenis = '$jenis'";
    if(mysqli_query($koneksi, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($koneksi)]);
    }
    exit;
}

// List Categories
// Group by jenis just in case
$query = "SELECT jenis, MAX(exclude_diskon_member) as exclude_status 
          FROM tbhargajual 
          GROUP BY jenis 
          ORDER BY jenis ASC";
$result = mysqli_query($koneksi, $query);

if(mysqli_num_rows($result) > 0) {
    echo '<div class="list-group">';
    while($row = mysqli_fetch_assoc($result)) {
        $jenis = htmlspecialchars($row['jenis']);
        $exclude = $row['exclude_status']; // 0 or 1
        
        // Logic: 0 = Dapat Diskon (Green), 1 = Tidak Dapat (Red/Grey)
        $is_dapat = ($exclude == 0);
        $status_class = $is_dapat ? 'list-group-item-success' : '';
        $icon = $is_dapat ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
        $text = $is_dapat ? 'Dapat Diskon Member' : 'TIDAK Dapat Diskon';
        $btn_class = $is_dapat ? 'btn-success' : 'btn-default';
        $toggle_val = $is_dapat ? 0 : 1; // Value to switch TO
        
        echo '<div class="list-group-item clearfix '.$status_class.'" id="cat-row-'.md5($jenis).'">';
            echo '<div class="pull-left">';
                echo '<h5 class="list-group-item-heading" style="margin:0; font-weight:bold;">'.$jenis.'</h5>';
                echo '<small class="text-muted status-text"><i class="fa '.$icon.'"></i> '.$text.'</small>';
            echo '</div>';
            echo '<div class="pull-right">';
                // Toggle Button
                echo '<label class="switch">';
                // Checkbox: Checked = Dapat Diskon
                $checked = $is_dapat ? 'checked' : '';
                echo '<input type="checkbox" class="toggle-kategori" data-jenis="'.$jenis.'" '.$checked.'>';
                echo '<span class="slider round"></span>';
                echo '</label>';
            echo '</div>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<div class="alert alert-warning">Belum ada data kategori barang.</div>';
}
?>
