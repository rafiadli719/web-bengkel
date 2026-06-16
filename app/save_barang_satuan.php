<?php
session_start();
include "../config/koneksi.php";
include "../config/accurate_config.php";

// Function to save unit to Accurate
function saveToAccurate($unit_code, $unit_name) {
    $validation = validateAccurateConfig();
    if ($validation !== true) {
        return [
            'status' => false,
            'message' => 'Konfigurasi API tidak lengkap: ' . implode(', ', $validation)
        ];
    }

    $host = getAccurateHost();
    if (!$host) {
        logAccurateDebug("Failed to get Accurate host");
        return [
            'status' => false,
            'message' => 'Gagal mendapatkan host Accurate'
        ];
    }

    $timestamp = formatTimestamp();
    $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
    $url = $host . '/accurate/api/unit/save.do';

    $data = [
        'name' => $unit_name
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ACCURATE_API_TOKEN,
        "X-Api-Timestamp: $timestamp",
        "X-Api-Signature: $signature",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    logAccurateDebug("Save to Accurate - HTTP Code: $http_code, Response: $response, Error: $curl_error");

    if (!empty($curl_error)) {
        return [
            'status' => false,
            'message' => 'Connection error: ' . $curl_error
        ];
    }

    if ($http_code == 200) {
        $result = json_decode($response, true);
        if ($result && isset($result['s']) && $result['s'] == true) {
            return [
                'status' => true,
                'message' => 'Berhasil disimpan ke Accurate Online'
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Gagal menyimpan ke Accurate: ' . ($result['d'] ?? 'Unknown error')
            ];
        }
    } else {
        $error_messages = [
            401 => 'API Token tidak valid atau expired',
            403 => 'Akses ditolak - periksa permission API token',
            404 => 'Endpoint tidak ditemukan',
            500 => 'Server error'
        ];
        $error_msg = $error_messages[$http_code] ?? "HTTP Error: $http_code";
        return [
            'status' => false,
            'message' => $error_msg . " (Response: $response)"
        ];
    }
}

// Main processing
$txtkd = $_POST['txtkd'];
$txtnama = $_POST['txtnama'];

// Validate inputs
if (empty($txtkd) || empty($txtnama)) {
    echo "<script>window.alert('Kode dan Nama Satuan harus diisi!'); window.location=('barang_satuan_add.php');</script>";
    exit;
}

// Save to local database
$insert_query = "INSERT INTO tblitemsatuan (satuan, namasatuan) VALUES ('$txtkd', '$txtnama')";
$local_success = mysqli_query($koneksi, $insert_query);

if ($local_success) {
    $message = "Data Satuan Barang Berhasil disimpan ke database lokal!";
    
    // Check Accurate connection and attempt sync
    if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected') {
        $accurate_result = saveToAccurate($txtkd, $txtnama);
        if ($accurate_result['status']) {
            $message .= "\\nData juga berhasil disinkronkan ke Accurate Online!";
        } else {
            $message .= "\\nGagal sinkronisasi ke Accurate: " . $accurate_result['message'];
        }
    } else {
        $message .= "\\nSinkronisasi ke Accurate tidak dilakukan karena koneksi tidak tersedia.";
    }
    
    echo "<script>window.alert('$message'); window.location=('barang_satuan.php');</script>";
} else {
    echo "<script>window.alert('Gagal menyimpan ke database lokal!'); window.location=('barang_satuan_add.php');</script>";
}
?>