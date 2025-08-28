<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";
    include "../config/accurate_config.php";

    // Data User
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'];
    $pwd = $tm_cari['password'];
    $lvl_akses = $tm_cari['user_akses'];
    $foto_user = $tm_cari['foto_user'];
    if ($foto_user == '') {
        $foto_user = "file_upload/avatar.png";
    }

    // Data Cabang
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'];
    $tipe_cabang = $tm_cari['tipe_cabang'];

    $tgl_skr = date('d');
    $bulan_skr = date('m');
    $thn_skr = date('Y');

    include "function_stok_masuk.php";
    $LastID = FormatNoTrans(OtomatisID());

    $txtcaribrg = isset($_GET['kd']) ? $_GET['kd'] : "";
    $tgl_pilih = isset($_GET['stgl']) ? $_GET['stgl'] : date('d/m/Y');
    $tot = "0";

    // Global variable untuk session Accurate
    $GLOBALS['ACCURATE_SESSION_ID'] = null;

    /**
     * Helper function untuk format timestamp
     */
    function formatTimestamp() {
        return gmdate('Y-m-d\TH:i:s.000\Z');
    }

    /**
     * Helper function untuk generate API signature
     */
    function generateApiSignature($timestamp, $secret) {
        return base64_encode(hash_hmac('sha256', $timestamp, $secret, true));
    }

    /**
     * Function untuk establish session dengan Accurate
     */
    function establishAccurateSession($log_file) {
        try {
            global $GLOBALS;
            $api_token = ACCURATE_API_TOKEN;
            $signature_secret = ACCURATE_SIGNATURE_SECRET;
            $base_url = ACCURATE_API_BASE_URL;

            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, $signature_secret);

            $url = $base_url . '/api/open-session.do';

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

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 open-session.do: HTTP $http_code\n", FILE_APPEND);

            if (!empty($curl_error)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ open-session.do cURL Error: $curl_error\n", FILE_APPEND);
                return false;
            }

            if ($http_code == 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['s']) && $result['s'] == true && isset($result['session'])) {
                    $GLOBALS['ACCURATE_SESSION_ID'] = $result['session'];
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ Session established: " . $result['session'] . "\n", FILE_APPEND);
                    $_SESSION['accurate_status'] = 'connected';
                    return $result['session'];
                }
            }

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Session establishment failed\n", FILE_APPEND);
            $_SESSION['accurate_status'] = 'disconnected';
            return false;

        } catch (Exception $e) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Session Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            $_SESSION['accurate_status'] = 'disconnected';
            return false;
        }
    }

    /**
     * Function untuk get host dengan detection yang lebih robust
     */
    function getAccurateHostWithImprovedDetection($log_file) {
        try {
            global $GLOBALS;

            if (!empty($GLOBALS['ACCURATE_HOST'])) {
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
                }
            }

            return false;

        } catch (Exception $e) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ getAccurateHost Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    /**
     * Recursive function to find URL in response
     */
    function findUrlInResponse($data, $log_file, $path = '') {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $current_path = $path ? "$path.$key" : $key;

                if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 Found URL at [$current_path]: $value\n", FILE_APPEND);
                    return $value;
                } elseif (is_array($value)) {
                    $result = findUrlInResponse($value, $log_file, $current_path);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Function untuk kirim data ke Accurate
     */
    function sendToAccurateFixed($data, $log_file) {
        try {
            $endpoint = '/accurate/api/item-adjustment/save.do';

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🚀 STARTING FIXED API CALL: " . $endpoint . "\n", FILE_APPEND);

            // Get host
            $host = getAccurateHostWithImprovedDetection($log_file);
            if (!$host) {
                return [
                    'success' => false,
                    'error' => 'Cannot get Accurate host',
                    'endpoint' => 'host_detection_failed'
                ];
            }

            // Establish session
            $session_id = establishAccurateSession($log_file);
            if (!$session_id) {
                return [
                    'success' => false,
                    'error' => 'Cannot establish Accurate session',
                    'endpoint' => 'session_failed'
                ];
            }

            // Prepare data
            $accurate_data = [
                'adjustmentAccountNo' => $data['adjustmentAccountNo'] ?? '110401',
                'transDate' => $data['transDate'],
                'description' => $data['description'] ?? '',
                'branchName' => $data['branchName'] ?? 'PESALAKAN',
                'detailItem[0].itemAdjustmentType' => 'ADJUSTMENT_IN',
                'detailItem[0].itemNo' => $data['itemNo'],
                'detailItem[0].unitCost' => $data['unitCost'],
                'detailItem[0].quantity' => $data['quantity'],
                'detailItem[0].warehouseName' => $data['warehouseName'] ?? 'Utama'
            ];

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📤 Data: " . http_build_query($accurate_data) . "\n", FILE_APPEND);

            // Generate timestamp dan signature
            $api_token = ACCURATE_API_TOKEN;
            $signature_secret = ACCURATE_SIGNATURE_SECRET;
            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, $signature_secret);

            // Build URL
            $url = $host . $endpoint;
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 Fixed URL: $url\n", FILE_APPEND);

            // Setup cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $api_token",
                "X-Api-Timestamp: $timestamp",
                "X-Api-Signature: $signature",
                "X-Session-ID: $session_id",
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($accurate_data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

            // Execute
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 Fixed HTTP Code: $http_code\n", FILE_APPEND);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📥 Fixed Response: " . substr($response, 0, 500) . "\n", FILE_APPEND);

            if (!empty($curl_error)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Fixed cURL Error: " . $curl_error . "\n", FILE_APPEND);
                return [
                    'success' => false,
                    'error' => "cURL Error: " . $curl_error,
                    'endpoint' => $url
                ];
            }

            if ($http_code == 200) {
                if (stripos($response, '<html>') !== false && stripos($response, 'login') !== false) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Received login page\n", FILE_APPEND);
                    return [
                        'success' => false,
                        'error' => 'Authentication failed. API Token invalid or expired.',
                        'endpoint' => $url
                    ];
                }

                $json_result = json_decode($response, true);
                if ($json_result !== null) {
                    if (isset($json_result['s']) && $json_result['s'] == true) {
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ FIXED SUCCESS!\n", FILE_APPEND);
                        return ['success' => true, 'data' => $json_result, 'endpoint' => $url];
                    } elseif (isset($json_result['s']) && $json_result['s'] == false) {
                        $error_msg = isset($json_result['d']) ? (is_array($json_result['d']) ? implode(', ', $json_result['d']) : $json_result['d']) : 'Unknown error';
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Fixed API Error: $error_msg\n", FILE_APPEND);
                        return [
                            'success' => false,
                            'error' => "API Error: $error_msg",
                            'endpoint' => $url
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'error' => 'Unexpected response format',
                'endpoint' => $url
            ];

        } catch (Exception $e) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ FIXED EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'endpoint' => 'exception'
            ];
        }
    }

    // Handle form submissions
    if (isset($_POST['btncari'])) {
        $txtcaribrg = $_POST['txtcaribrg'];
        $tgl_pilih = $_POST['id-date-picker-1'];

        $cari_kd = mysqli_query($koneksi, "SELECT count(noitem) as tot FROM view_cari_item WHERE noitem='$txtcaribrg'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $tot_cari = $tm_cari['tot'];

        if ($tot_cari == '1') {
            $cari_kd = mysqli_query($koneksi, "SELECT namaitem FROM view_cari_item WHERE noitem='$txtcaribrg'");
            $tm_cari = mysqli_fetch_array($cari_kd);
            $txtnamaitem = $tm_cari['namaitem'];
            $txtcaribrg = "$txtcaribrg";
        } else {
            $cbocari = "";
            $cbourut = "35";
            echo "<script>window.location=('stok_masuk_add_item_cari.php?stgl=$tgl_pilih&_key=$txtcaribrg&_cari=$cbocari&_urut=$cbourut&_flt=asc');</script>";
        }

        $cari_kd = mysqli_query($koneksi, "SELECT sum(total) as tot FROM tbitem_masuk_detail WHERE user='$_nama' and kd_cabang='$kd_cabang' and status_trx='0'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $tot = $tm_cari['tot'];
    }

    if (isset($_POST['btnadd'])) {
        $txtkdbarang = $_POST['txtcaribrg'];
        $txtqty = $_POST['txtqty'];
        $tgl_pilih = $_POST['id-date-picker-1'];

        $cari_kd = mysqli_query($koneksi, "SELECT hargapokok FROM tblitem WHERE noitem='$txtkdbarang'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $txthargabarang = $tm_cari['hargapokok'];

        $subtotal = $txthargabarang * $txtqty;
        if ($txtkdbarang <> '') {
            $data = mysqli_query($koneksi, "SELECT * FROM tbitem_masuk_detail WHERE user='$_nama' and kd_cabang='$kd_cabang' and no_item='$txtkdbarang' and status_trx='0'");
            $cek = mysqli_num_rows($data);
            if ($cek > 0) {
                $kdbrg = "";
                echo "<script>window.alert('Item Barang sudah ada!');window.location=('stok_masuk_add_rst.php?stgl=$tgl_pilih&kd=$kdbrg');</script>";
            } else {
                mysqli_query($koneksi, "INSERT INTO tbitem_masuk_detail (no_transaksi, no_item, harga, quantity, total, user, kd_cabang) VALUES ('', '$txtkdbarang','$txthargabarang', '$txtqty','$subtotal', '$_nama','$kd_cabang')");
            }

            $cari_kd = mysqli_query($koneksi, "SELECT sum(total) as tot FROM tbitem_masuk_detail WHERE user='$_nama' and kd_cabang='$kd_cabang' and status_trx='0'");
            $tm_cari = mysqli_fetch_array($cari_kd);
            $tot = $tm_cari['tot'];

            $txtcaribrg = "";
            $txtnamaitem = "";
        } else {
            $txtcaribrg = "";
            $txtnamaitem = "";
        }
    }

    if (isset($_POST['btnsimpan'])) {
        $txttotal_harga = $_POST['txttotal_harga'];
        if ($txttotal_harga == '0') {
            echo "<script>window.alert('Belum ada Item barang yang dipilih. Transaksi tidak dapat disimpan!');window.location=('stok_masuk_add_rst.php');</script>";
        } else {
            date_default_timezone_set('Asia/Jakarta');

            function ubahformatTgl($tanggal) {
                $pisah = explode('/', $tanggal);
                $urutan = array($pisah[2], $pisah[1], $pisah[0]);
                $satukan = implode('-', $urutan);
                return $satukan;
            }

            function ubahformatTglToAccurate($tanggal) {
                $pisah = explode('/', $tanggal);
                $urutan = array($pisah[0], $pisah[1], $pisah[2]);
                $satukan = implode('/', $urutan);
                return $satukan;
            }

            $txttglpesan = ubahformatTgl($_POST['id-date-picker-1']);
            $txttglpesan_accurate = ubahformatTglToAccurate($_POST['id-date-picker-1']);
            $txttotal_harga = $_POST['txttotal_harga'];
            $txtket = $_POST['txtket'];

            $cari_kd = mysqli_query($koneksi, "SELECT sum(quantity) as tot FROM tbitem_masuk_detail WHERE user='$_nama' and kd_cabang='$kd_cabang' and status_trx='0'");
            $tm_cari = mysqli_fetch_array($cari_kd);
            $tot_qty = $tm_cari['tot'];

            $data = mysqli_query($koneksi, "SELECT no_transaksi FROM tbitem_masuk_header WHERE no_transaksi='$LastID'");
            $cek = mysqli_num_rows($data);
            if ($cek > 0) {
                // Do nothing if already exists
            } else {
                // Simpan ke database lokal
                mysqli_query($koneksi, "INSERT INTO tbitem_masuk_header (no_transaksi, tanggal, user, kd_cabang, note) VALUES ('$LastID','$txttglpesan', '$_nama','$kd_cabang','$txtket')");
                mysqli_query($koneksi, "UPDATE tbitem_masuk_detail SET no_transaksi='$LastID', status_trx='1' WHERE user='$_nama' and kd_cabang='$kd_cabang' and status_trx='0'");

                $sql = mysqli_query($koneksi, "SELECT * FROM tbitem_masuk_detail WHERE no_transaksi='$LastID'");
                $sql_count = mysqli_num_rows($sql);

                $log_file = 'accurate_stock_update_log.txt';
                file_put_contents($log_file, "\n======= NEW TRANSACTION: $LastID =======\n", FILE_APPEND | LOCK_EX);
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 LOCAL DATABASE: Rows = $sql_count, LastID = $LastID - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);

                // Prepare items untuk sinkronisasi ke Accurate
                $items_to_update = [];
                while ($tampil = mysqli_fetch_array($sql)) {
                    $no_item = $tampil['no_item'];
                    $qty = $tampil['quantity'];
                    $harga = $tampil['harga'];

                    // Update stok lokal
                    mysqli_query($koneksi, "INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang) VALUES ('5','$LastID','$no_item', '$txttglpesan','$qty','0', 'Penyesuaian Stok Item Masuk','$kd_cabang')");

                    $items_to_update[] = [
                        'itemNo' => $no_item,
                        'quantity' => number_format($qty, 6, '.', ''),
                        'unitCost' => number_format($harga, 6, '.', ''),
                        'transDate' => $txttglpesan_accurate,
                        'description' => $txtket,
                        'adjustmentAccountNo' => '',
                        'branchName' => 'PESALAKAN',
                        'warehouseName' => 'Utama'
                    ];
                }

                if (empty($items_to_update)) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ No items to sync\n", FILE_APPEND);
                    echo "<script>window.alert('✅ Data berhasil disimpan ke database lokal.');window.location=('stok_masuk_add_cetak.php?nopesanan=$LastID');</script>";
                    exit;
                }

                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 ACCURATE API SYNC: Starting dengan " . count($items_to_update) . " items\n", FILE_APPEND);

                $accurate_success = true;
                $accurate_error = '';
                $success_endpoint = '';
                $success_count = 0;

                foreach ($items_to_update as $index => $item) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📦 Processing Item " . ($index + 1) . "/" . count($items_to_update) . ": " . $item['itemNo'] . " (Qty: " . $item['quantity'] . ")\n", FILE_APPEND);

                    try {
                        $result = sendToAccurateFixed($item, $log_file);
                        if (!$result['success']) {
                            $accurate_success = false;
                            $accurate_error = $result['error'];
                            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Item " . ($index + 1) . " FAILED: " . $result['error'] . "\n", FILE_APPEND);
                            break;
                        } else {
                            $success_endpoint = $result['endpoint'];
                            $success_count++;
                            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ Item " . ($index + 1) . " SUCCESS via " . $result['endpoint'] . "\n", FILE_APPEND);
                        }
                    } catch (Exception $e) {
                        $accurate_success = false;
                        $accurate_error = 'Exception: ' . $e->getMessage();
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Item " . ($index + 1) . " EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
                        break;
                    }
                }

                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🏁 SYNC COMPLETED. Success: " . ($accurate_success ? 'YES' : 'NO') . ", Count: $success_count/" . count($items_to_update) . "\n", FILE_APPEND);

                if (!$accurate_success) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ ACCURATE SYNC FAILED: $accurate_error (Processed: $success_count/" . count($items_to_update) . ")\n", FILE_APPEND);
                    echo "<script>window.alert('✅ DATA TERSIMPAN DI DATABASE LOKAL\\n❌ GAGAL SINKRONISASI KE ACCURATE\\n\\nError: $accurate_error\\n\\nItems berhasil sync: $success_count/" . count($items_to_update) . "\\n\\nData tetap aman tersimpan di sistem lokal.');window.location=('stok_masuk_add_cetak.php?nopesanan=$LastID');</script>";
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🎉 ACCURATE SYNC SUCCESS! All " . count($items_to_update) . " items synchronized via $success_endpoint\n", FILE_APPEND);
                    echo "<script>window.alert('🎉 SUKSES TOTAL! 🎉\\n\\n✅ Data tersimpan di database lokal\\n✅ Data berhasil disinkronisasi ke Accurate Online\\n\\nTotal items: " . count($items_to_update) . "\\nEndpoint: $success_endpoint');window.location=('stok_masuk_add_cetak.php?nopesanan=$LastID');</script>";
                }

                exit;
            }
        }
    }

    if ($txtcaribrg == '') {
        $txtnamaitem = "";
    } else {
        $cari_kd = mysqli_query($koneksi, "SELECT namaitem FROM view_cari_item WHERE noitem='$txtcaribrg'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $txtnamaitem = $tm_cari['namaitem'];
    }

    $cari_kd = mysqli_query($koneksi, "SELECT sum(total) as tot FROM tbitem_masuk_detail WHERE user='$_nama' and kd_cabang='$kd_cabang' and status_trx='0'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $tot = $tm_cari['tot'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="with draggable and editable events" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fullcalendar.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.css' rel='stylesheet' />

    <script src="assets/js/ace-extra.min.js"></script>
    <script type="text/javascript" src="chartjs/Chart.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <div class="navbar-header pull-left">
                <table>
                    <tr>
                        <td width="20%">
                            <a href="index.php" class="navbar-brand">
                                <small>
                                    <i class="fa fa-leaf"></i>
                                    <?php include "../lib/subtitel.php"; ?>
                                </small>
                            </a>
                        </td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo $_nama; ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>

                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li>
                                <a href="change_pwd.php">
                                    <i class="ace-icon fa fa-cog"></i>
                                    Change Password
                                </a>
                            </li>
                            <li>
                                <a href="profile.php">
                                    <i class="ace-icon fa fa-user"></i>
                                    Profile
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="logout.php">
                                    <i class="ace-icon fa fa-power-off"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-header pull-right">
                <?php if (isset($_SESSION['accurate_status'])): ?>
                    <span class="navbar-brand">
                        <small style="color: <?php echo $_SESSION['accurate_status'] == 'connected' ? 'green' : 'orange'; ?>">
                            <i class="fa fa-circle"></i> Accurate: <?php echo $_SESSION['accurate_status']; ?>
                        </small>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">
                try{ace.settings.loadState('sidebar')}catch(e){}
            </script>

            <?php include "menu_stok01.php"; ?>

            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li>
                            <a href="">Penyesuaian Stok Manual</a>
                        </li>
                        <li>
                            <a href="penyesuaian-stok-masuk-manual.php">Item Masuk</a>
                        </li>
                        <li class="active">Tambah Data</li>
                    </ul>
                </div>

                <div class="page-content">
                    <?php if (isset($_SESSION['accurate_status'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['accurate_status'] == 'connected' ? 'success' : 'warning'; ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <strong>Status Accurate API:</strong>
                            <?php if ($_SESSION['accurate_status'] == 'connected'): ?>
                                ✅ Terhubung - Data akan otomatis sinkronisasi ke Accurate Online
                            <?php else: ?>
                                ⚠️ Tidak terhubung - Data hanya disimpan di database lokal
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form class="form-horizontal" action="" method="post" role="form">
                        <input type="hidden" name="txttotal_harga" class="form-control" value="<?php echo $tot; ?>"/>

                        <div class="row">
                            <div class="col-xs-12 col-sm-12">
                                <div class="widget-box">
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <br>
                                            <div class="row">
                                                <div class="col-xs-12 col-sm-4">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtno"> No :</label>
                                                        <div class="col-sm-7">
                                                            <input type="text" id="txtno" name="txtno" class="form-control" value="<?php echo $LastID; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-4">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Tanggal :</label>
                                                        <div class="col-sm-7">
                                                            <div class="input-group">
                                                                <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" value="<?php echo $tgl_pilih; ?>" data-date-format="dd/mm/yyyy" />
                                                                <span class="input-group-addon">
                                                                    <i class="fa fa-calendar bigger-110"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-4">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right" for="txtuser"> User :</label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $_nama; ?>" disabled />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12">
                                <div class="widget-box">
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <?php include "_template/_stok_masuk_detail.php"; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space space-8"></div>
                            <div class="col-xs-12 col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-1 control-label no-padding-right" for="form-field-1"> Keterangan </label>
                                    <div class="col-sm-11">
                                        <input type="text" id="txtket" name="txtket" class="col-xs-10 col-sm-12" autocomplete="off" />
                                    </div>
                                </div>
                            </div>
                            <div class="space space-8"></div>

                            <div class="col-xs-12 col-sm-3">
                                <button class="btn btn-primary btn-block" type="submit" id="btnsimpan" name="btnsimpan">
                                    <i class="fa fa-save"></i> Simpan
                                </button>
                            </div>
                        </form>

                        <div class="col-xs-12 col-sm-3">
                            <a href="stok_masuk_batal.php?suser=<?php echo $_nama; ?>&scabang=<?php echo $kd_cabang; ?>" onclick="return confirm('Inputan Stok Item Masuk akan dibatalkan. Lanjutkan?')">
                                <button class="btn btn-warning btn-block" type="button">
                                    <i class="fa fa-times"></i> Batal
                                </button>
                            </a>
                        </div>

                        <div class="col-xs-12 col-sm-3">
                            <button class="btn disabled btn-info btn-block" type="submit" id="btncetak" name="btncetak">
                                <i class="fa fa-print"></i> Cetak
                            </button>
                        </div>

                        <div class="col-xs-12 col-sm-3">
                            <a href="penyesuaian-stok-masuk-manual.php">
                                <button class="btn btn-default btn-block" type="button">
                                    <i class="fa fa-arrow-left"></i> Tutup
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <?php include "../lib/footer.php"; ?>
                </div>
            </div>
        </div>

        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
            <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
        </a>
    </div>

    <!-- JavaScript Files -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">
        if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
    </script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/jquery.ui.touch-punch.min.js"></script>
    <script src="assets/js/chosen.jquery.min.js"></script>
    <script src="assets/js/spinbox.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/bootstrap-timepicker.min.js"></script>
    <script src="assets/js/moment.min.js"></script>
    <script src="assets/js/daterangepicker.min.js"></script>
    <script src="assets/js/bootstrap-datetimepicker.min.js"></script>
    <script src="assets/js/bootstrap-colorpicker.min.js"></script>
    <script src="assets/js/jquery.knob.min.js"></script>
    <script src="assets/js/autosize.min.js"></script>
    <script src="assets/js/jquery.inputlimiter.min.js"></script>
    <script src="assets/js/jquery.maskedinput.min.js"></script>
    <script src="assets/js/bootstrap-tag.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        jQuery(function($) {
            // Initialize chosen select
            if(!ace.vars['touch']) {
                $('.chosen-select').chosen({allow_single_deselect:true}); 
                $(window)
                .off('resize.chosen')
                .on('resize.chosen', function() {
                    $('.chosen-select').each(function() {
                         var $this = $(this);
                         $this.next().css({'width': $this.parent().width()});
                    })
                }).trigger('resize.chosen');
                $(document).on('settings.ace.chosen', function(e, event_name, event_val) {
                    if(event_name != 'sidebar_collapsed') return;
                    $('.chosen-select').each(function() {
                         var $this = $(this);
                         $this.next().css({'width': $this.parent().width()});
                    })
                });
            }

            // Initialize tooltips and popovers
            $('[data-rel=tooltip]').tooltip({container:'body'});
            $('[data-rel=popover]').popover({container:'body'});

            // Initialize autosize for textareas
            autosize($('textarea[class*=autosize]'));
            
            // Input masks
            $.mask.definitions['~']='[+-]';
            $('.input-mask-date').mask('99/99/9999');
            $('.input-mask-phone').mask('(999) 999-9999');

            // Date picker initialization
            $('.date-picker').datepicker({
                autoclose: true,
                todayHighlight: true
            }).next().on(ace.click_event, function(){
                $(this).prev().focus();
            });

            // Form validation
            $('#btnsimpan').on('click', function(e) {
                var total = $('input[name="txttotal_harga"]').val();
                if (total == '0' || total == '') {
                    e.preventDefault();
                    alert('Belum ada item barang yang dipilih. Silakan tambahkan item terlebih dahulu.');
                    return false;
                }
                
                return confirm('Apakah Anda yakin ingin menyimpan data ini?\n\nData akan disimpan ke database lokal dan akan dicoba sinkronisasi ke Accurate Online (jika terhubung).');
            });

            // Auto-hide alert after 10 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 10000);

            // Focus pada field pencarian barang
            $('#txtcaribrg').focus();

            // Enter key handler untuk form pencarian
            $('#txtcaribrg').on('keypress', function(e) {
                if (e.which == 13) {
                    $('#btncari').click();
                    return false;
                }
            });

            // Enter key handler untuk form quantity
            $('#txtqty').on('keypress', function(e) {
                if (e.which == 13) {
                    $('#btnadd').click();
                    return false;
                }
            });

            // Auto format number untuk quantity
            $('#txtqty').on('input', function() {
                var value = $(this).val();
                value = value.replace(/[^0-9.]/g, '');
                var parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                $(this).val(value);
            });

            // Prevent negative values
            $('#txtqty').on('keydown', function(e) {
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                    (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    return;
                }
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });

            // Auto calculate total when quantity changes
            $('#txtqty').on('input', function() {
                var qty = parseFloat($(this).val()) || 0;
                var harga = parseFloat($('#txtharga').val()) || 0;
                var total = qty * harga;
                $('#txtsubtotal').val(total.toFixed(2));
            });

            // Clear form after add item
            $('#btnadd').on('click', function() {
                setTimeout(function() {
                    $('#txtcaribrg').val('').focus();
                    $('#txtnamaitem').val('');
                    $('#txtqty').val('');
                }, 100);
            });

            // Auto refresh total when item added/removed
            setInterval(function() {
                // Implementation depends on _stok_masuk_detail.php structure
            }, 5000);
        });
    </script>
</body>
</html>

<?php 
}
?>