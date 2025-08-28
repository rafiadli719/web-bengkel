<?php
// Debug script untuk form keluhan + workorder
session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// Debug POST data
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check if btnaddkeluhan is set
    if(isset($_POST['btnaddkeluhan'])) {
        echo "<h3>btnaddkeluhan detected!</h3>";
        
        $no_service = $_POST['txtnosrv'] ?? 'MISSING';
        $txtkeluhan = $_POST['txtkeluhan'] ?? 'MISSING';
        
        echo "No Service: $no_service<br>";
        echo "Keluhan: $txtkeluhan<br>";
        
        if($txtkeluhan != 'MISSING' && $txtkeluhan != '') {
            echo "<h4>Testing keluhan processing...</h4>";
            
            // Test insert keluhan
            $test_insert = "INSERT INTO tbservis_keluhan_status 
                           (no_service, keluhan, status_pengerjaan) 
                           VALUES 
                           ('$no_service','$txtkeluhan','datang')";
            
            echo "SQL Query: $test_insert<br>";
            
            if(mysqli_query($koneksi, $test_insert)) {
                echo "<span style='color: green;'>✓ Keluhan berhasil ditambahkan!</span><br>";
                $keluhan_id = mysqli_insert_id($koneksi);
                echo "Keluhan ID: $keluhan_id<br>";
                
                // Test workorder search
                echo "<h4>Testing workorder search...</h4>";
                $workorder_query = "SELECT workorder_default, tingkat_prioritas 
                                   FROM tbmaster_keluhan 
                                   WHERE (nama_keluhan = '$txtkeluhan' OR kode_keluhan = '$txtkeluhan')
                                     AND status_aktif = '1' 
                                     AND workorder_default IS NOT NULL
                                   LIMIT 1";
                
                echo "WO Query: $workorder_query<br>";
                $workorder_result = mysqli_query($koneksi, $workorder_query);
                
                if($workorder_result && mysqli_num_rows($workorder_result) > 0) {
                    $wo_data = mysqli_fetch_array($workorder_result);
                    $auto_workorder = $wo_data['workorder_default'];
                    echo "<span style='color: green;'>✓ WorkOrder ditemukan: $auto_workorder</span><br>";
                    
                    // Test workorder details
                    $detail_query = "SELECT kode_barang, tipe, harga, total, jumlah 
                                    FROM tbworkorderdetail 
                                    WHERE kode_wo='$auto_workorder'";
                    
                    $detail_result = mysqli_query($koneksi, $detail_query);
                    if($detail_result) {
                        $detail_count = mysqli_num_rows($detail_result);
                        echo "WorkOrder detail items: $detail_count<br>";
                        
                        while($detail = mysqli_fetch_array($detail_result)) {
                            $type = $detail['tipe'] == '1' ? 'Jasa' : 'Barang';
                            echo "- {$detail['kode_barang']} ($type): Rp" . number_format($detail['harga']) . "<br>";
                        }
                    }
                } else {
                    echo "<span style='color: orange;'>⚠ Tidak ada workorder yang sesuai dengan keluhan</span><br>";
                }
                
            } else {
                echo "<span style='color: red;'>✗ Error: " . mysqli_error($koneksi) . "</span><br>";
            }
        } else {
            echo "<span style='color: red;'>✗ Keluhan kosong!</span><br>";
        }
    } else {
        echo "<h3>btnaddkeluhan NOT detected!</h3>";
        echo "Available POST keys: " . implode(', ', array_keys($_POST));
    }
    
    echo "<hr><a href='javascript:history.back()'>← Kembali</a>";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Form Keluhan</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .form-group { margin: 15px 0; }
        input, button { padding: 8px; margin: 5px; }
        button { background: #007bff; color: white; border: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Debug Form Keluhan + WorkOrder</h1>
    
    <form method="POST">
        <div class="form-group">
            <label>No Service:</label><br>
            <input type="text" name="txtnosrv" value="TEST001" required>
        </div>
        
        <div class="form-group">
            <label>Keluhan:</label><br>
            <input type="text" name="txtkeluhan" placeholder="Contoh: suara mesin kasar" required>
        </div>
        
        <div class="form-group">
            <label>KM Sekarang:</label><br>
            <input type="number" name="txtkm_skr" value="1000">
        </div>
        
        <div class="form-group">
            <label>KM Berikut:</label><br>
            <input type="number" name="txtkm_next" value="1500">
        </div>
        
        <input type="hidden" name="txtcarisrv" value="">
        <input type="hidden" name="txtcaribrg" value="">
        <input type="hidden" name="txtcariwo" value="">
        
        <div class="form-group">
            <button type="submit" name="btnaddkeluhan">Tambah Keluhan + WO</button>
        </div>
    </form>
    
    <h3>Available Test Data:</h3>
    <ul>
        <li>Keluhan: "Mesin Tidak Mau Hidup" → WO0005</li>
        <li>Keluhan: "Suara Mesin Kasar" → WO0005</li>
        <li>Keluhan: "suara mesin kasar" → keyword match WO0005</li>
    </ul>
</body>
</html>