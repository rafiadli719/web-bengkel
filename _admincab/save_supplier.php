<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include file konfigurasi database
include "../config/koneksi.php";

// Ambil data dari form
$txtkd = $_POST['txtkd'] ?? null;
$txtnama = $_POST['txtnama'] ?? null;
$cbotipe = $_POST['cbotipe'] ?? null; // Tipe pemasok baru
$txtalamat = $_POST['txtalamat'] ?? null;
$txtkota = $_POST['txtkota'] ?? null;
$txtprop = $_POST['txtprop'] ?? null;
$txtpos = $_POST['txtpos'] ?? null;
$txtnegara = $_POST['txtnegara'] ?? null;
$txttlp = $_POST['txttlp'] ?? null;
$txtwa = $_POST['txtwa'] ?? null; // WhatsApp baru
$txtfax = $_POST['txtfax'] ?? null;
$txtbank = $_POST['txtbank'] ?? null;
$txtnorek = $_POST['txtnorek'] ?? null;
$txtnmrek = $_POST['txtnmrek'] ?? null;
$txtkontak = $_POST['txtkontak'] ?? null;
$txtemail = $_POST['txtemail'] ?? null;
$txtnote = $_POST['txtnote'] ?? null;
$cbocabang = $_POST['cbocabang'] ?? null;

// Inisialisasi file log
$log_file = 'accurate_supplier_add_log.txt';

// VALIDASI HANYA UNTUK FIELD WAJIB
// Field optional boleh kosong dan TIDAK akan menghentikan proses save
$required_fields = [
    'txtkd' => 'Kode Supplier',
    'txtnama' => 'Nama Supplier', 
    'cbotipe' => 'Tipe Pemasok',
    'txtalamat' => 'Alamat',
    'txtkota' => 'Kota',
    'txtprop' => 'Provinsi',
    'txtnegara' => 'Negara',
    'txttlp' => 'No. Telepon',
    'txtemail' => 'Email'
];

$missing_fields = [];
foreach ($required_fields as $field => $label) {
    if (empty($field)) {
        $missing_fields[] = $label;
    }
}

// HANYA FIELD WAJIB yang dicek - field optional diabaikan
if (!empty($missing_fields)) {
    $missing_list = implode(', ', $missing_fields);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Field wajib kosong: $missing_list\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Field wajib harus diisi: $missing_list');window.location=('supplier_add.php');</script>";
    exit;
}

// LOG: Field optional yang kosong (untuk tracking)
$optional_empty = [];
$optional_fields = ['txtpos', 'txtwa', 'txtfax', 'txtbank', 'txtnorek', 'txtnmrek', 'txtkontak', 'txtnote', 'cbocabang'];
foreach ($optional_fields as $field) {
    if (empty($field)) {
        $optional_empty[] = $field;
    }
}
if (!empty($optional_empty)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ℹ️ Optional fields kosong: " . implode(', ', $optional_empty) . " - TETAP LANJUT SAVE\n", FILE_APPEND | LOCK_EX);
}

// Validasi tipe pemasok
$valid_types = ['perorangan', 'pemerintahan', 'perusahaan'];
if (!in_array($cbotipe, $valid_types)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Tipe pemasok tidak valid: $cbotipe\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Tipe pemasok tidak valid!');window.location=('supplier_add.php');</script>";
    exit;
}

// Validasi format email
if (!filter_var($txtemail, FILTER_VALIDATE_EMAIL)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Format email tidak valid: $txtemail\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Format email tidak valid!');window.location=('supplier_add.php');</script>";
    exit;
}

// Cek duplikasi kode supplier
$check_duplicate = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblsupplier WHERE nosupplier='$txtkd'");
$duplicate_result = mysqli_fetch_array($check_duplicate);
if ($duplicate_result['count'] > 0) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Duplikasi kode supplier: $txtkd\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Kode Supplier sudah ada dalam database!');window.location=('supplier_add.php');</script>";
    exit;
}

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 ADD SUPPLIER - Kode: $txtkd, Nama: $txtnama, Tipe: $cbotipe\n", FILE_APPEND | LOCK_EX);

// Prepare data untuk insert dengan escape
$escape_txtkd = mysqli_real_escape_string($koneksi, $txtkd);
$escape_txtnama = mysqli_real_escape_string($koneksi, $txtnama);
$escape_cbotipe = mysqli_real_escape_string($koneksi, $cbotipe);
$escape_txtalamat = mysqli_real_escape_string($koneksi, $txtalamat);
$escape_txtkota = mysqli_real_escape_string($koneksi, $txtkota);
$escape_txtprop = mysqli_real_escape_string($koneksi, $txtprop);
$escape_txtpos = mysqli_real_escape_string($koneksi, $txtpos);
$escape_txtnegara = mysqli_real_escape_string($koneksi, $txtnegara);
$escape_txttlp = mysqli_real_escape_string($koneksi, $txttlp);
$escape_txtwa = mysqli_real_escape_string($koneksi, $txtwa);
$escape_txtfax = mysqli_real_escape_string($koneksi, $txtfax);
$escape_txtbank = mysqli_real_escape_string($koneksi, $txtbank);
$escape_txtnorek = mysqli_real_escape_string($koneksi, $txtnorek);
$escape_txtnmrek = mysqli_real_escape_string($koneksi, $txtnmrek);
$escape_txtkontak = mysqli_real_escape_string($koneksi, $txtkontak);
$escape_txtemail = mysqli_real_escape_string($koneksi, $txtemail);
$escape_txtnote = mysqli_real_escape_string($koneksi, $txtnote);
$escape_cbocabang = mysqli_real_escape_string($koneksi, $cbocabang);

// Insert ke database lokal - SEMUA FIELD (termasuk yang kosong)
// Field kosong akan disimpan sebagai empty string atau NULL
$insert_query = "INSERT INTO tblsupplier 
                (nosupplier, namasupplier, tipe_pemasok, alamat, kota, propinsi, kodepost, 
                 negara, telephone, no_whatsapp, fax, namabank, noaccount, atasnama, 
                 kontakperson, email, note, kd_cabang) 
                VALUES 
                ('$escape_txtkd', '$escape_txtnama', '$escape_cbotipe', '$escape_txtalamat', '$escape_txtkota', '$escape_txtprop', '$escape_txtpos',
                 '$escape_txtnegara', '$escape_txttlp', '$escape_txtwa', '$escape_txtfax', '$escape_txtbank', '$escape_txtnorek', '$escape_txtnmrek',
                 '$escape_txtkontak', '$escape_txtemail', '$escape_txtnote', '$escape_cbocabang')";

$result = mysqli_query($koneksi, $insert_query);
if (!$result) {
    $error = mysqli_error($koneksi);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal insert database lokal: $error\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Gagal menyimpan ke database lokal. $error');window.location=('supplier_add.php');</script>";
    exit;
}

// Get inserted ID untuk keperluan accurate_id update nanti
$inserted_id = mysqli_insert_id($koneksi);

// Save spare part factory associations
if (isset($_POST["hapus"]) && is_array($_POST["hapus"])) {
    $pabrik_count = 0;
    foreach ($_POST["hapus"] as $nip) {
        $escape_nip = mysqli_real_escape_string($koneksi, $nip);
        if (mysqli_query($koneksi, "INSERT INTO tblsupplier_spart (nosupplier, id_pabrik) VALUES ('$escape_txtkd', '$escape_nip')")) {
            $pabrik_count++;
        }
    }
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📋 Spare parts associations saved: $pabrik_count items\n", FILE_APPEND | LOCK_EX);
}

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 DATABASE LOKAL: Insert supplier '$txtnama' (Kode: $txtkd, Tipe: $cbotipe) - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);

// Include file konfigurasi Accurate
$config_path = '../config/accurate_config.php';
if (!file_exists($config_path)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ File konfigurasi tidak ditemukan di: $config_path\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ DATA BERHASIL DISIMPAN DI DATABASE LOKAL\\n⚠️ File konfigurasi Accurate tidak ditemukan.');window.location=('supplier.php');</script>";
    exit;
}
include_once $config_path;

// Periksa konstanta API
if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Konfigurasi API tidak lengkap\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ DATA BERHASIL DISIMPAN DI DATABASE LOKAL\\n⚠️ Konfigurasi API Accurate tidak lengkap.');window.location=('supplier.php');</script>";
    exit;
}

/**
 * Function untuk format timestamp sesuai dokumentasi Accurate
 */
function formatTimestamp($format = 'accurate') {
    switch ($format) {
        case 'iso8601':
            return date('Y-m-d\TH:i:sP', time() + (7 * 3600)); // WIB +0700
        case 'accurate':
        default:
            return date('d/m/Y H:i:s');
    }
}

/**
 * Function untuk generate API signature dengan HMAC SHA-256
 */
function generateApiSignature($timestamp, $signature_secret) {
    $signature = hash_hmac('sha256', $timestamp, $signature_secret, true);
    return base64_encode($signature);
}

/**
 * Function untuk mendapatkan host dari api-token.do
 */
function getAccurateHost($log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $url = ACCURATE_API_BASE_URL . '/api/api-token.do';

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Getting host from: $url\n", FILE_APPEND | LOCK_EX);

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code != 200) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Failed to get host: HTTP $http_code\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        $result = json_decode($response, true);
        if (!$result || !isset($result['s']) || $result['s'] != true) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Invalid api-token response\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        $host = $result['d']['database']['host'] ?? null;
        if (!$host) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Host not found in response\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Host extracted: $host\n", FILE_APPEND | LOCK_EX);
        return $host;

    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
}

/**
 * Function untuk mapping tipe pemasok ke vendor type Accurate
 */
function mapSupplierTypeToAccurate($tipe_pemasok) {
    $mapping = [
        'perusahaan' => 'CORPORATE',
        'perorangan' => 'INDIVIDUAL', 
        'pemerintahan' => 'GOVERNMENT'
    ];
    
    return $mapping[$tipe_pemasok] ?? 'CORPORATE';
}

/**
 * Function untuk save supplier ke Accurate
 */
function saveSupplierToAccurate($host, $supplier_data, $log_file, $koneksi, $local_id) {
    try {
        $save_url = $host . '/accurate/api/vendor/save.do';
        
        // Prepare data untuk Accurate API berdasarkan dokumentasi
        $accurate_data = [
            'name' => $supplier_data['txtnama'],
            'transDate' => date('d/m/Y'), // Current date
            'vendorNo' => $supplier_data['txtkd'],
            'billStreet' => $supplier_data['txtalamat'],
            'billCity' => $supplier_data['txtkota'],
            'billProvince' => $supplier_data['txtprop'],
            'billZipCode' => $supplier_data['txtpos'],
            'billCountry' => $supplier_data['txtnegara'],
            'workPhone' => $supplier_data['txttlp'],
            'mobilePhone' => !empty($supplier_data['txtwa']) ? $supplier_data['txtwa'] : $supplier_data['txttlp'],
            'fax' => $supplier_data['txtfax'],
            'email' => $supplier_data['txtemail'],
            'notes' => $supplier_data['txtnote'],
            'website' => '', // Default kosong
            'npwpNo' => '', // Default kosong
            'pkpNo' => '', // Default kosong
            'defaultIncTax' => 'false',
            'currencyCode' => 'IDR'
        ];

        // Add branch ID if specified  
        if (!empty($supplier_data['cbocabang']) && is_numeric($supplier_data['cbocabang'])) {
            $accurate_data['branchId'] = (int)$supplier_data['cbocabang'];
        }

        // Add contact person details if provided
        if (!empty($supplier_data['txtkontak'])) {
            $accurate_data['detailContact[0].name'] = $supplier_data['txtkontak'];
            $accurate_data['detailContact[0].mobilePhone'] = !empty($supplier_data['txtwa']) ? $supplier_data['txtwa'] : $supplier_data['txttlp'];
            $accurate_data['detailContact[0].email'] = $supplier_data['txtemail'];
            $accurate_data['detailContact[0].position'] = 'Contact Person';
            $accurate_data['detailContact[0].workPhone'] = $supplier_data['txttlp'];
            
            // Set salutation based on supplier type
            switch($supplier_data['cbotipe']) {
                case 'perorangan':
                    $accurate_data['detailContact[0].salutation'] = 'MR'; // Default, bisa disesuaikan
                    break;
                case 'perusahaan':
                case 'pemerintahan':
                default:
                    // Tidak set salutation untuk perusahaan/pemerintahan
                    break;
            }
        }

        // Add vendor category name based on supplier type
        switch($supplier_data['cbotipe']) {
            case 'perorangan':
                $accurate_data['categoryName'] = 'Supplier Perorangan';
                break;
            case 'pemerintahan':
                $accurate_data['categoryName'] = 'Supplier Pemerintahan';
                break;
            case 'perusahaan':
            default:
                $accurate_data['categoryName'] = 'Supplier Perusahaan';
                break;
        }

        // Log tipe pemasok mapping
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🏷️ Supplier Type: " . $supplier_data['cbotipe'] . " → Category: " . $accurate_data['categoryName'] . "\n", FILE_APPEND | LOCK_EX);

        // OPSI 1: Kirim SEMUA field ke Accurate (termasuk yang kosong)
        // Field kosong akan dikirim sebagai empty string ke Accurate
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📋 Sending ALL fields to Accurate (including empty ones)\n", FILE_APPEND | LOCK_EX);
        
        // OPSI 2: Jika ingin menghapus field kosong, uncomment baris di bawah:
        // $accurate_data = array_filter($accurate_data, function($value) {
        //     return !is_null($value) && $value !== '';
        // });
        // file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📋 Removed empty fields before sending to Accurate\n", FILE_APPEND | LOCK_EX);

        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🌐 Save URL: $save_url\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📤 Data: " . http_build_query($accurate_data) . "\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($save_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($accurate_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📊 Save HTTP Code: $http_code\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📥 Save Response: " . substr($response, 0, 500) . "\n", FILE_APPEND | LOCK_EX);

        if (!empty($curl_error)) {
            return [
                'success' => false,
                'error' => "cURL Error: $curl_error"
            ];
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Supplier created successfully\n", FILE_APPEND | LOCK_EX);
                
                // Get returned ID and update local database
                if (isset($result['d']['id'])) {
                    $returned_id = $result['d']['id'];
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📋 Returned supplier ID: $returned_id\n", FILE_APPEND | LOCK_EX);
                    
                    // Update accurate_id in local database
                    $update_query = "UPDATE tblsupplier SET accurate_id='$returned_id' WHERE id='$local_id'";
                    if (mysqli_query($koneksi, $update_query)) {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 Updated local accurate_id to: $returned_id\n", FILE_APPEND | LOCK_EX);
                    } else {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Failed to update local accurate_id: " . mysqli_error($koneksi) . "\n", FILE_APPEND | LOCK_EX);
                    }
                }
                
                return [
                    'success' => true,
                    'operation' => 'CREATED',
                    'supplier_id' => $result['d']['id'] ?? null,
                    'supplier_number' => $result['d']['number'] ?? null
                ];
            } else {
                $error_msg = isset($result['d']) ? (is_array($result['d']) ? implode(', ', $result['d']) : $result['d']) : 'Unknown error';
                return [
                    'success' => false,
                    'error' => "API Error: $error_msg"
                ];
            }
        } else {
            $error_messages = [
                400 => 'Bad Request - Data yang dikirim tidak valid',
                401 => 'API Token tidak valid atau expired',
                403 => 'Akses ditolak - periksa permission API token (vendor_save)',
                404 => 'Endpoint tidak ditemukan',
                422 => 'Validation Error - Data tidak memenuhi requirement',
                500 => 'Server error'
            ];
            $error_msg = $error_messages[$http_code] ?? "HTTP Error: $http_code";
            return [
                'success' => false,
                'error' => "$error_msg (Response: " . substr($response, 0, 200) . ")"
            ];
        }
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ saveSupplierToAccurate Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// === MAIN EXECUTION ===

// Step 1: Get host
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔄 Starting Accurate synchronization\n", FILE_APPEND | LOCK_EX);

$host = getAccurateHost($log_file);
if (!$host) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Cannot establish Accurate connection\n", FILE_APPEND | LOCK_EX);
    echo "<script>
    var msg = '✅ DATA BERHASIL DISIMPAN DI DATABASE LOKAL\\n';
    msg += '⚠️ Tidak dapat terhubung ke Accurate Online\\n\\n';
    msg += '📋 Detail Supplier:\\n';
    msg += 'Kode: $txtkd\\n';
    msg += 'Nama: $txtnama\\n';
    msg += 'Tipe: " . ucfirst($cbotipe) . "\\n';
    msg += 'Email: $txtemail\\n\\n';
    msg += 'Data tetap aman tersimpan di sistem lokal.';
    window.alert(msg);
    window.location=('supplier.php');
    </script>";
    exit;
}

// Step 2: Prepare supplier data (removed lama hari kirim dan jangka waktu kredit)
$supplier_data = [
    'txtkd' => $txtkd,
    'txtnama' => $txtnama,
    'cbotipe' => $cbotipe,
    'txtalamat' => $txtalamat,
    'txtkota' => $txtkota,
    'txtprop' => $txtprop,
    'txtpos' => $txtpos,
    'txtnegara' => $txtnegara,
    'txttlp' => $txttlp,
    'txtwa' => $txtwa,
    'txtfax' => $txtfax,
    'txtkontak' => $txtkontak,
    'txtemail' => $txtemail,
    'txtnote' => $txtnote,
    'cbocabang' => $cbocabang
];

// Step 3: Save to Accurate
$save_result = saveSupplierToAccurate($host, $supplier_data, $log_file, $koneksi, $inserted_id);

if ($save_result['success']) {
    $operation = $save_result['operation'];
    $supplier_id = $save_result['supplier_id'] ?? 'unknown';
    $supplier_number = $save_result['supplier_number'] ?? 'auto-generated';
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🎉 Synchronization successful: $operation\n", FILE_APPEND | LOCK_EX);
    
    echo "<script>
    var successMsg = '🎉 SUKSES TOTAL! 🎉\\n\\n';
    successMsg += '✅ Data supplier berhasil disimpan di database lokal\\n';
    successMsg += '✅ Data berhasil di$operation di Accurate Online\\n\\n';
    successMsg += '📋 Detail Supplier:\\n';
    successMsg += 'Kode: $txtkd\\n';
    successMsg += 'Nama: $txtnama\\n';
    successMsg += 'Tipe: " . ucfirst($cbotipe) . "\\n';
    successMsg += 'Email: $txtemail\\n';
    successMsg += 'WhatsApp: " . ($txtwa ?: 'Tidak diisi') . "\\n\\n';
    successMsg += '🔗 Accurate Info:\\n';
    successMsg += 'Operation: $operation\\n';
    successMsg += 'Accurate ID: $supplier_id\\n';
    successMsg += 'Supplier Number: $supplier_number';
    window.alert(successMsg);
    window.location=('supplier.php');
    </script>";
} else {
    $error_detail = $save_result['error'];
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Synchronization failed: $error_detail\n", FILE_APPEND | LOCK_EX);
    
    echo "<script>
    var errorMsg = '✅ DATA BERHASIL DISIMPAN DI DATABASE LOKAL\\n';
    errorMsg += '❌ GAGAL SINKRONISASI KE ACCURATE\\n\\n';
    errorMsg += '📋 Detail Supplier:\\n';
    errorMsg += 'Kode: $txtkd\\n';
    errorMsg += 'Nama: $txtnama\\n';
    errorMsg += 'Tipe: " . ucfirst($cbotipe) . "\\n';
    errorMsg += 'Email: $txtemail\\n\\n';
    errorMsg += '❌ Error Detail:\\n';
    errorMsg += '$error_detail\\n\\n';
    errorMsg += '💾 Data tetap aman tersimpan di sistem lokal.\\n';
    errorMsg += 'Anda dapat mencoba sinkronisasi ulang nanti.';
    window.alert(errorMsg);
    window.location=('supplier.php');
    </script>";
}

// Log akhir eksekusi
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🏁 Script execution completed\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] " . str_repeat("=", 80) . "\n", FILE_APPEND | LOCK_EX);
?>