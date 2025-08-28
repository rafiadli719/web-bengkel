<?php
// Test script untuk validasi auto-save functionality
session_start();

// Simulate session for testing
if(!isset($_SESSION['_iduser'])) {
    $_SESSION['_iduser'] = 1;
}

include "../../config/koneksi.php";

// Test data
$test_data = [
    'no_service' => 'SRV001', // Replace with actual service number
    'tests' => [
        ['field_type' => 'kepala_mekanik1', 'field_value' => 'MEK001', 'field_percentage' => 50],
        ['field_type' => 'kepala_mekanik2', 'field_value' => 'MEK002', 'field_percentage' => 50],
        ['field_type' => 'admin1', 'field_value' => 'ADM001', 'field_percentage' => 60],
        ['field_type' => 'admin2', 'field_value' => 'ADM002', 'field_percentage' => 40],
        ['field_type' => 'mekanik1', 'field_value' => 'MEK003', 'field_percentage' => 25],
        ['field_type' => 'mekanik2', 'field_value' => 'MEK004', 'field_percentage' => 25],
        ['field_type' => 'mekanik3', 'field_value' => 'MEK005', 'field_percentage' => 25],
        ['field_type' => 'mekanik4', 'field_value' => 'MEK006', 'field_percentage' => 25]
    ]
];

echo "<h2>Auto-Save Functionality Test</h2>";
echo "<p>Testing service: " . $test_data['no_service'] . "</p>";

foreach($test_data['tests'] as $test) {
    echo "<h3>Testing: " . $test['field_type'] . "</h3>";
    
    // Simulate POST data
    $_POST = [
        'no_service' => $test_data['no_service'],
        'field_type' => $test['field_type'],
        'field_value' => $test['field_value'],
        'field_percentage' => $test['field_percentage']
    ];
    
    // Capture output
    ob_start();
    include 'auto_save_mekanik.php';
    $result = ob_get_clean();
    
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    echo "<hr>";
}

// Test KM auto-save
echo "<h3>Testing KM Auto-Save</h3>";
$_POST = [
    'no_service' => $test_data['no_service'],
    'km_skr' => 15000,
    'km_berikut' => 20000
];

ob_start();
include 'auto_save_km.php';
$km_result = ob_get_clean();

echo "<pre>" . htmlspecialchars($km_result) . "</pre>";

echo "<h3>Database Verification</h3>";
$verify_query = "SELECT kepala_mekanik1, kepala_mekanik2, 
                        persen_kepala_mekanik1, persen_kepala_mekanik2,
                        mekanik1, mekanik2, mekanik3, mekanik4,
                        persen_mekanik1, persen_mekanik2, persen_mekanik3, persen_mekanik4,
                        km_skr, km_berikut
                 FROM tblservice 
                 WHERE no_service = '" . $test_data['no_service'] . "'";

$verify_result = mysqli_query($koneksi, $verify_query);
if($verify_result && $row = mysqli_fetch_assoc($verify_result)) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach($row as $field => $value) {
        echo "<tr><td>$field</td><td>$value</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No service found with number: " . $test_data['no_service'] . "</p>";
    echo "<p>Available services:</p>";
    $list_services = mysqli_query($koneksi, "SELECT no_service FROM tblservice LIMIT 5");
    while($service = mysqli_fetch_assoc($list_services)) {
        echo "<p>- " . $service['no_service'] . "</p>";
    }
}
?>
