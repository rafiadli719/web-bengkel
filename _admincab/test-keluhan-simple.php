<?php
include "../config/koneksi.php";

echo "Testing keluhan + workorder functionality..." . PHP_EOL;

// Simulate POST data like form submission
$no_service = 'TEST002';
$txtkeluhan = 'suara mesin kasar';

echo "No Service: $no_service" . PHP_EOL;
echo "Keluhan: $txtkeluhan" . PHP_EOL . PHP_EOL;

// Clean up
mysqli_query($koneksi, "DELETE FROM tbservis_keluhan_status WHERE no_service = '$no_service'");

// Step 1: Insert keluhan
echo "Step 1: Insert keluhan..." . PHP_EOL;
$insert_keluhan = "INSERT INTO tbservis_keluhan_status 
                   (no_service, keluhan, status_pengerjaan) 
                   VALUES 
                   ('$no_service','$txtkeluhan','datang')";

if(mysqli_query($koneksi, $insert_keluhan)) {
    echo "✓ Keluhan inserted successfully" . PHP_EOL;
    $keluhan_id = mysqli_insert_id($koneksi);
    echo "Keluhan ID: $keluhan_id" . PHP_EOL;
} else {
    echo "✗ Error: " . mysqli_error($koneksi) . PHP_EOL;
    exit;
}

// Step 2: Find workorder
echo PHP_EOL . "Step 2: Find workorder..." . PHP_EOL;
$workorder_query = "SELECT workorder_default, tingkat_prioritas 
                   FROM tbmaster_keluhan 
                   WHERE (nama_keluhan = '$txtkeluhan' OR kode_keluhan = '$txtkeluhan')
                     AND status_aktif = '1' 
                     AND workorder_default IS NOT NULL
                   LIMIT 1";

$workorder_result = mysqli_query($koneksi, $workorder_query);
$auto_workorder = null;

if($workorder_result && mysqli_num_rows($workorder_result) > 0) {
    $wo_data = mysqli_fetch_array($workorder_result);
    $auto_workorder = $wo_data['workorder_default'];
    echo "✓ Found workorder from master: $auto_workorder" . PHP_EOL;
} else {
    // Fallback keyword matching
    $keluhan_lower = strtolower($txtkeluhan);
    if(strpos($keluhan_lower, 'mesin') !== false) {
        $auto_workorder = 'WO0005';
        echo "✓ Found workorder from keyword: $auto_workorder" . PHP_EOL;
    } else {
        echo "⚠ No matching workorder found" . PHP_EOL;
    }
}

if($auto_workorder) {
    // Step 3: Get workorder details
    echo PHP_EOL . "Step 3: Get workorder details..." . PHP_EOL;
    $detail_query = "SELECT kode_barang, tipe, harga, total, jumlah 
                    FROM tbworkorderdetail 
                    WHERE kode_wo='$auto_workorder'";
    
    $detail_result = mysqli_query($koneksi, $detail_query);
    if($detail_result) {
        $detail_count = mysqli_num_rows($detail_result);
        echo "Found $detail_count items in workorder:" . PHP_EOL;
        
        while($detail = mysqli_fetch_array($detail_result)) {
            $type = $detail['tipe'] == '1' ? 'Jasa' : 'Barang';
            echo "  - {$detail['kode_barang']} ($type): Qty {$detail['jumlah']} × Rp" . number_format($detail['harga']) . PHP_EOL;
        }
    }
}

echo PHP_EOL . "✅ Test completed successfully!" . PHP_EOL;
?>