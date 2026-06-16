<?php
// Script untuk update handler keluhan di servis-input-reguler.php

$file_path = 'servis-input-reguler.php';
$content = file_get_contents($file_path);

if($content === false) {
    die("Error: File tidak ditemukan\n");
}

// Pattern untuk mencari handler lama
$start_pattern = '/\/\/ Add Keluhan dengan Auto WorkOrder\s+if\(isset\(\$_POST\[\'btnaddkeluhan\'\]\)\) \{/';
$end_pattern = '/\}\s+(?=\/\/\s*Preserve|echo"<script>window\.location|echo"<script>alert)/';

// Cari posisi start dan end
if(preg_match($start_pattern, $content, $start_matches, PREG_OFFSET_CAPTURE)) {
    $start_pos = $start_matches[0][1];
    
    // Cari closing bracket dengan menghitung bracket
    $bracket_count = 0;
    $current_pos = $start_pos;
    $found_start_bracket = false;
    
    while($current_pos < strlen($content)) {
        $char = $content[$current_pos];
        
        if($char == '{') {
            $bracket_count++;
            $found_start_bracket = true;
        } elseif($char == '}') {
            $bracket_count--;
            if($found_start_bracket && $bracket_count == 0) {
                // Found the closing bracket
                $end_pos = $current_pos + 1;
                break;
            }
        }
        $current_pos++;
    }
    
    if(isset($end_pos)) {
        $old_handler = substr($content, $start_pos, $end_pos - $start_pos);
        
        $new_handler = '        // Add Keluhan dengan Auto WorkOrder (menggunakan trigger + stored procedure)
        if(isset($_POST[\'btnaddkeluhan\'])) {
            $no_service = $_POST[\'txtnosrv\'];
            $txtkeluhan = $_POST[\'txtkeluhan\'];
            
            $km_skr = $_POST[\'txtkm_skr\'];
            $km_berikut = $_POST[\'txtkm_next\'];
            
            $txtcarisrv = $_POST[\'txtcarisrv\'];
            $txtcaribrg = $_POST[\'txtcaribrg\'];
            $txtcariwo = $_POST[\'txtcariwo\'];

            if($txtkeluhan != \'\') {
                // Insert keluhan - trigger otomatis akan handle workorder dan auto-add jasa/barang
                $insert_keluhan = "INSERT INTO tbservis_keluhan_status 
                                  (no_service, keluhan, status_pengerjaan) 
                                  VALUES 
                                  (\'$no_service\',\'$txtkeluhan\',\'datang\')";
                
                if(mysqli_query($koneksi, $insert_keluhan)) {
                    echo "<script>alert(\'Keluhan berhasil ditambahkan. WorkOrder dan items otomatis ditambahkan.\');</script>";
                } else {
                    echo "<script>alert(\'Error: " . mysqli_error($koneksi) . "\');</script>";
                }
            } else {
                echo "<script>alert(\'Keluhan tidak boleh kosong!\');</script>";
            }

            // Preserve current values in redirect
            echo"<script>window.location=(\'servis-input-reguler.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo\');
            </script>";
        }';
        
        $new_content = substr($content, 0, $start_pos) . $new_handler . substr($content, $end_pos);
        
        if(file_put_contents($file_path, $new_content)) {
            echo "✓ Handler berhasil diupdate!\n";
            echo "Old handler length: " . strlen($old_handler) . " chars\n";
            echo "New handler length: " . strlen($new_handler) . " chars\n";
        } else {
            echo "✗ Error writing file\n";
        }
    } else {
        echo "✗ End bracket tidak ditemukan\n";
    }
} else {
    echo "✗ Handler lama tidak ditemukan\n";
}
?>