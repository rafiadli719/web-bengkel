<?php
session_start();
if (!isset($_SESSION['_iduser']) || empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/koneksi.php";
include "../config/accurate_config.php";

// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tgl_skr = date('Y/m/d');

// Ambil data dari form
$txtkd = $_POST['txtkd'] ?? null;
$txtkdcab = $_POST['txtkdcab'] ?? null;
$txtbarcode = $_POST['txtbarcode'] ?? '';
$txtnama = $_POST['txtnama'] ?? null;
$cbojenis = $_POST['cbojenis'] ?? 'INVENTORY';
$cbosatuan = $_POST['cbosatuan'] ?? '';
$txtqty2 = $_POST['txtqty1b'] ?? 0;
$txtqty3 = $_POST['txtqty2a'] ?? 0;
$txtqty4 = $_POST['txtqty2b'] ?? 0;
$txtqty5 = $_POST['txtqty3a'] ?? 0;
$txthj1 = $_POST['txthj1'] ?? 0;
$txthj2 = $_POST['txthj2'] ?? 0;
$txthj3 = $_POST['txthj3'] ?? 0;
$txtnote = $_POST['txtnote'] ?? '';
$txtstokmin = $_POST['txtstokmin'] ?? 0;
$txtstokmaks = $_POST['txtstokmaks'] ?? 0;
$cbosupplier1 = $_POST['cbosupplier1'] ?? '';
$cbosupplier2 = $_POST['cbosupplier2'] ?? '';
$cbosupplier3 = $_POST['cbosupplier3'] ?? '';
$cborak = $_POST['cborak'] ?? '';
$txthpokok = $_POST['txthpokok'] ?? 0;
$cbostatus = $_POST['cbostatus'] ?? '1';
$cbotipe = $_POST['cbotipe'] ?? '';
$cbopabrik = $_POST['cbopabrik'] ?? '';
$cbojasa = $_POST['cbojasa'] ?? '';

$log_file = 'accurate_item_edit_log.txt';

// Validasi field wajib
if (empty($txtkd) || empty($txtnama) || empty($cbojenis) || empty($cbosatuan) || empty($cbopabrik) || empty($cbostatus) || empty($cbotipe)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Error: Field wajib kosong\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('Error: Semua field wajib harus diisi.');window.location='barang_edit.php?kd=$txtkd';</script>";
    exit;
}

if ($cbojenis == 'SERVIS') {
    $cbojasa = "";
}

// Update Database Lokal - tblitem
$result = mysqli_query($koneksi, "UPDATE tblitem SET 
                        kodebarcode='$txtbarcode', 
                        namaitem='$txtnama',
                        jenis='$cbojenis', 
                        satuan='$cbosatuan',
                        hjqtys1='$txtqty2', 
                        hjqtyd2='$txtqty3', 
                        hjqtyd3='$txtqty4', 
                        hjqtys2='$txtqty5',
                        hargajual='$txthj1', 
                        hargajual2='$txthj2', 
                        hargajual3='$txthj3',
                        note='$txtnote',
                        supplier='$cbosupplier1', 
                        supplier2='$cbosupplier2', 
                        supplier3='$cbosupplier3',
                        hargapokok='$txthpokok', 
                        statusproduk='$cbostatus', 
                        statusitem='$cbotipe',
                        kd_pabrik='$cbopabrik', 
                        jenis_jasa='$cbojasa'
                        WHERE noitem='$txtkd'");

if (!$result) {
    $error = mysqli_error($koneksi);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal update database lokal - tblitem: $error\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('Error: Gagal update database lokal. $error');window.location='barang_edit.php?kd=$txtkd';</script>";
    exit;
}

// Update Database Lokal - tblitem_stok
$data = mysqli_query($koneksi, "SELECT noitem FROM tblitem_stok WHERE kode_cabang='$txtkdcab' and noitem='$txtkd'");
$cek = mysqli_num_rows($data);

if($cek > 0) {		
    $result_stok = mysqli_query($koneksi, "UPDATE tblitem_stok SET 
                                stokmin='$txtstokmin', 
                                stok_maks='$txtstokmaks', 
                                rakbarang='$cborak' 
                                WHERE kode_cabang='$txtkdcab' and noitem='$txtkd'");    
} else {
    $result_stok = mysqli_query($koneksi, "INSERT INTO tblitem_stok 
                            (noitem, kode_cabang, stokmin, stok_maks, rakbarang) 
                            VALUES 
                            ('$txtkd','$txtkdcab','$txtstokmin','$txtstokmaks','$cborak')");            
}

if (!$result_stok) {
    $error = mysqli_error($koneksi);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal update database lokal - tblitem_stok: $error\n", FILE_APPEND | LOCK_EX);
}

// Update Applicable Part
// Hapus data lama
mysqli_query($koneksi, "DELETE FROM tblitem_spart WHERE noitem='$txtkd'");

// Insert data baru
$fields = ['hapus1', 'hapus2', 'hapus3', 'hapus4'];
foreach ($fields as $field) {
    if (isset($_POST[$field]) && is_array($_POST[$field])) {
        $jumlah = count($_POST[$field]);
        for ($i = 0; $i < $jumlah; $i++) {
            $nip = $_POST[$field][$i];
            if (!empty($nip)) {
                mysqli_query($koneksi, "INSERT INTO tblitem_spart 
                                        (noitem, kode_tipe) 
                                        VALUES 
                                        ('$txtkd','$nip')");
            }
        }
    }
}

// Logging
file_put_contents($log_file, "\n======= EDIT ITEM: $txtkd =======\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 💾 LOCAL DATABASE: Item $txtkd - UPDATE SUCCESS ✅\n", FILE_APPEND | LOCK_EX);

// Accurate API Functions
function formatTimestamp() {
    return date('d/m/Y H:i:s');
}

function generateApiSignature($timestamp, $secret) {
    return base64_encode(hash_hmac('sha256', $timestamp, $secret, true));
}

function getAccurateHost($log_file) {
    try {
        if (isset($GLOBALS['ACCURATE_HOST']) && !empty($GLOBALS['ACCURATE_HOST'])) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📋 Using cached host: " . $GLOBALS['ACCURATE_HOST'] . "\n", FILE_APPEND);
            return $GLOBALS['ACCURATE_HOST'];
        }

        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $base_url = ACCURATE_API_BASE_URL;

        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);

        $url = $base_url . '/api/api-token.do';

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 Calling api-token.do: $url\n", FILE_APPEND);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_token",
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 api-token.do HTTP Code: $http_code\n", FILE_APPEND);

        if (!empty($curl_error)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ api-token.do cURL Error: $curl_error\n", FILE_APPEND);
            return false;
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📄 Full api-token.do response: " . $response . "\n", FILE_APPEND);

            if ($result && isset($result['s']) && $result['s'] == true) {
                $host = null;
                $possible_paths = [
                    ['d', 'database', 'host'],
                    ['d', 'data usaha', 'host'],
                    ['d', 'host'],
                    ['host'],
                    ['d', 'dataUsaha', 'host'],
                    ['d', 'company', 'host']
                ];

                foreach ($possible_paths as $path) {
                    $temp = $result;
                    $path_str = implode('.', $path);

                    foreach ($path as $key) {
                        if (isset($temp[$key])) {
                            $temp = $temp[$key];
                        } else {
                            $temp = null;
                            break;
                        }
                    }

                    if ($temp && is_string($temp) && filter_var($temp, FILTER_VALIDATE_URL)) {
                        $host = $temp;
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ Host found at path [$path_str]: $host\n", FILE_APPEND);
                        break;
                    }
                }

                if ($host) {
                    $GLOBALS['ACCURATE_HOST'] = $host;
                    return $host;
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Host not found in any expected path\n", FILE_APPEND);
                    $host = findUrlInResponse($result, $log_file);
                    if ($host) {
                        $GLOBALS['ACCURATE_HOST'] = $host;
                        return $host;
                    }
                }
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ api-token.do returned success=false or invalid structure\n", FILE_APPEND);
                if (isset($result['d'])) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📄 Error details: " . json_encode($result['d']) . "\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ api-token.do HTTP Error: $http_code\n", FILE_APPEND);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📄 Response: " . substr($response, 0, 500) . "\n", FILE_APPEND);
            return false;
        }

        return false;

    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ getAccurateHost Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

function findUrlInResponse($data, $log_file, $path = '') {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $current_path = $path ? "$path.$key" : $key;

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 Found URL at [$current_path]: $value\n", FILE_APPEND);
                return $value;
            } else if (is_array($value)) {
                $result = findUrlInResponse($value, $log_file, $current_path);
                if ($result) {
                    return $result;
                }
            }
        }
    }
    return null;
}

// Function untuk mendapatkan nama supplier berdasarkan kode
function getSupplierName($koneksi, $supplierCode, $log_file) {
    if (empty($supplierCode)) {
        return null;
    }
    
    $query = "SELECT namasupplier FROM tblsupplier WHERE nosupplier = '" . mysqli_real_escape_string($koneksi, $supplierCode) . "'";
    $result = mysqli_query($koneksi, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $supplierName = $row['namasupplier'];
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📋 Supplier mapping: $supplierCode → $supplierName\n", FILE_APPEND);
        return $supplierName;
    } else {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ Supplier $supplierCode not found in local database\n", FILE_APPEND);
        return null;
    }
}

// Function untuk mendapatkan Accurate Item ID berdasarkan nomor item
function getAccurateItemId($txtkd, $log_file) {
    try {
        $host = getAccurateHost($log_file);
        if (!$host) {
            return null;
        }

        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);

        // Search item by no
        $url = $host . '/accurate/api/item/detail.do?no=' . urlencode($txtkd);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 Searching Accurate item: $url\n", FILE_APPEND);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_token",
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 Search HTTP Code: $http_code\n", FILE_APPEND);

        if (!empty($curl_error)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Search cURL Error: $curl_error\n", FILE_APPEND);
            return null;
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true && isset($result['d']['id'])) {
                $itemId = $result['d']['id'];
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ Found Accurate item ID: $itemId for item: $txtkd\n", FILE_APPEND);
                return $itemId;
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ Item $txtkd not found in Accurate\n", FILE_APPEND);
                return null;
            }
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Search HTTP Error: $http_code\n", FILE_APPEND);
            return null;
        }
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ getAccurateItemId Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        return null;
    }
}

function updateToAccurate($data, $log_file) {
    try {
        $endpoint = '/accurate/api/item/save.do';

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🚀 STARTING UPDATE API CALL: " . $endpoint . "\n", FILE_APPEND);

        $host = getAccurateHost($log_file);
        if (!$host) {
            return [
                'success' => false,
                'error' => 'Cannot get Accurate host',
                'endpoint' => 'host_detection_failed'
            ];
        }

        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);

        $url = $host . $endpoint;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 URL: $url\n", FILE_APPEND);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_token",
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📖 Executing cURL request...\n", FILE_APPEND);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 HTTP Code: $http_code\n", FILE_APPEND);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📖 Response: " . substr($response, 0, 500) . "\n", FILE_APPEND);

        if (!empty($curl_error)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ cURL Error: " . $curl_error . "\n", FILE_APPEND);
            return [
                'success' => false,
                'error' => "cURL Error: " . $curl_error,
                'endpoint' => $url
            ];
        }

        if ($http_code == 200) {
            if (stripos($response, 'html') !== false && strpos($response, 'login') !== false) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Received login page\n", FILE_APPEND);
                return [
                    'success' => false,
                    'error' => 'Authentication failed. API Token invalid or expired.',
                    'endpoint' => $url
                ];
            }

            $json_result = json_decode($response, true);
            if ($json_result !== null) {
                if (isset($json_result['s']) && $json_result['s'] === true) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ UPDATE SUCCESS ✅\n", FILE_APPEND | LOCK_EX);
                    return ['success' => true, 'data' => $json_result, 'endpoint' => $url];
                } else if (isset($json_result['s']) && $json_result['s'] === false) {
                    $error_msg = '';
                    if (isset($json_result['d']) && is_array($json_result['d'])) {
                        foreach ($json_result['d'] as $error_item) {
                            if (isset($error_item['d']) && is_array($error_item['d'])) {
                                $error_msg .= implode('; ', $error_item['d']) . '. ';
                            } else {
                                $error_msg .= json_encode($error_item) . '. ';
                            }
                        }
                    } else {
                        $error_msg = is_array($json_result['d']) ? implode(', ', $json_result['d']) : ($json_result['d'] ?? 'Unknown error');
                    }
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ API Error: $error_msg\n", FILE_APPEND | LOCK_EX);
                    return [
                        'success' => false,
                        'error' => "API Error: $error_msg",
                        'endpoint' => $url
                    ];
                }
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ Unexpected response format\n", FILE_APPEND | LOCK_EX);
                return [
                    'success' => false,
                    'error' => 'Unexpected response format',
                    'endpoint' => $url
                ];
            }
        } else {
            $error_messages = [
                401 => 'API Token tidak valid atau sudah expired',
                403 => 'Akses ditolak. Periksa permission API token',
                404 => 'Endpoint tidak ditemukan',
                500 => 'Internal server error'
            ];

            $error_msg = $error_messages[$http_code] ?? "HTTP Error Code: $http_code";
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ HTTP $http_code: $error_msg\n", FILE_APPEND | LOCK_EX);
            return [
                'success' => false,
                'error' => $error_msg,
                'endpoint' => $url
            ];
        }
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage(),
            'endpoint' => 'exception'
        ];
    }
}

// Prepare data for Accurate update
$itemType = 'INVENTORY';
if ($cbojenis == 'SERVIS') {
    $itemType = 'SERVICE';
} elseif ($cbojenis == 'GROUP') {
    $itemType = 'GROUP';
} elseif ($cbojenis == 'NON_INVENTORY') {
    $itemType = 'NON_INVENTORY';
} elseif ($cbojenis == 'PRODUCTION_COST') {
    $itemType = 'PRODUCTION_COST';
}

$category = ($cbojenis == 'AKIGEN') ? 'Default Category' : $cbojenis;

// Cari ID item di Accurate untuk update
$accurateItemId = getAccurateItemId($txtkd, $log_file);

$accurate_data = [
    'itemType' => $itemType,
    'name' => $txtnama,
    'no' => $txtkd,
    'unit1Name' => $cbosatuan,
    'unitPrice' => number_format($txthj1, 6, '.', ''),
    'vendorPrice' => number_format($txthpokok, 6, '.', ''),
    'notes' => $txtnote,
    'controlQuantity' => ($itemType == 'INVENTORY') ? 'true' : 'false',
    'itemCategoryName' => $category,
    'upcNo' => $txtbarcode,
    'vendorUnitName' => $cbosatuan,
    'minimumQuantity' => number_format($txtstokmin, 6, '.', ''),
    'minimumQuantityReorder' => number_format($txtstokmaks, 6, '.', '')
];

// Jika ditemukan ID item di Accurate, tambahkan untuk update
if ($accurateItemId) {
    $accurate_data['id'] = $accurateItemId;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📝 Will UPDATE existing item with ID: $accurateItemId\n", FILE_APPEND);
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📝 Will CREATE new item in Accurate\n", FILE_APPEND);
}

// PERBAIKAN SUPPLIER HANDLING
// Cek dan ambil nama supplier dari database lokal
if (!empty($cbosupplier1)) {
    $supplierName = getSupplierName($koneksi, $cbosupplier1, $log_file);
    if ($supplierName) {
        $accurate_data['preferedVendorName'] = $supplierName;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ Using supplier: $supplierName (code: $cbosupplier1)\n", FILE_APPEND);
    } else {
        // Jika supplier tidak ditemukan, skip field ini daripada error
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ Skipping supplier field - $cbosupplier1 not found locally\n", FILE_APPEND);
    }
}

// Sinkronisasi ke Accurate jika terhubung
if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected') {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 ACCURATE API SYNC: Starting UPDATE\n", FILE_APPEND | LOCK_EX);
    
    // Log data yang akan dikirim (untuk debugging)
    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📤 Sending data: " . json_encode($accurate_data) . "\n", FILE_APPEND);
    
    $result = updateToAccurate($accurate_data, $log_file);

    if ($result['success']) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🎉 ACCURATE UPDATE SUCCESS! Item synchronized via " . $result['endpoint'] . "\n", FILE_APPEND | LOCK_EX);
        echo "<script>alert('🎉 UPDATE SUKSES TOTAL! 🎉\\n\\n✅ Data Barang berhasil diupdate di database lokal\\n✅ Data berhasil disinkronisasi ke Accurate Online\\n\\nKode: $txtkd\\nNama: $txtnama\\nEndpoint: " . $result['endpoint'] . "');window.location='barang.php';</script>";
    } else {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ ACCURATE UPDATE FAILED: " . $result['error'] . "\n", FILE_APPEND | LOCK_EX);
        echo "<script>alert('✅ DATA BERHASIL DIUPDATE DI DATABASE LOKAL\\n❌ GAGAL SINKRONISASI KE ACCURATE\\n\\nError: " . addslashes($result['error']) . "\\nEndpoint: " . $result['endpoint'] . "\\n\\nData tetap aman tersimpan di sistem lokal.\\nKode: $txtkd\\nNama: $txtnama');window.location='barang.php';</script>";
    }
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ ACCURATE SYNC SKIPPED: Not connected\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('✅ DATA BERHASIL DIUPDATE DI DATABASE LOKAL\\n⚠️ Sinkronisasi ke Accurate tidak dilakukan karena koneksi tidak tersedia\\n\\nKode: $txtkd\\nNama: $txtnama');window.location='barang.php';</script>";
}

if (ob_get_level()) {
    ob_end_flush();
}
flush();
exit;
?>