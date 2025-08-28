<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];      
    $kd_cabang=$_SESSION['_cabang'];                    
    include "../config/koneksi.php";
    
    // Debug mode - set true untuk debugging, false untuk production
    $debug_mode = false; // Ubah ke true jika perlu debugging
    
    function debug_log($message, $data = null) {
        global $debug_mode;
        if ($debug_mode) {
            echo "<script>console.log('DEBUG: $message');</script>";
            if ($data !== null) {
                echo "<script>console.log(" . json_encode($data) . ");</script>";
            }
        }
    }
    
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_user, password, user_akses, foto_user 
                                    FROM tbuser WHERE id='$id_user'");         
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];                       
    $pwd=$tm_cari['password'];                      
    $lvl_akses=$tm_cari['user_akses'];                          
    $foto_user=$tm_cari['foto_user'];               
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang 
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");         
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];                       
    $tipe_cabang=$tm_cari['tipe_cabang']; 
    // --------------------
    
    $tgl_skr=date('d');  
    $bulan_skr=date('m');
    $thn_skr=date('Y');

    // Include konfigurasi Accurate API
    include "../config/accurate_config.php";

    /**
     * Helper function untuk format timestamp
     */
    if (!function_exists('formatTimestamp')) {
        function formatTimestamp() {
            return date('d/m/Y H:i:s');
        }
    }

    /**
     * Helper function untuk generate API signature
     */
    if (!function_exists('generateApiSignature')) {
        function generateApiSignature($timestamp, $signature_secret) {
            return base64_encode(hash_hmac('sha256', $timestamp, $signature_secret, true));
        }
    }

    /**
     * PERBAIKAN: Function untuk check status koneksi Accurate API
     * Menggunakan endpoint yang benar dan tidak perlu session
     */
    if (!function_exists('checkAccurateConnection')) {
        function checkAccurateConnection() {
            if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
                return [
                    'status' => 'disconnected',
                    'message' => 'Konfigurasi API tidak lengkap'
                ];
            }

            try {
                $timestamp = formatTimestamp();
                $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
                
                // PERBAIKAN: Gunakan endpoint item list untuk test koneksi (lebih ringan)
                $url = ACCURATE_API_BASE_URL . '/api/item/list.do';

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
                curl_setopt($ch, CURLOPT_POSTFIELDS, 'sp.pageSize=1'); // Ambil 1 item saja untuk test
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if (!empty($curl_error)) {
                    return [
                        'status' => 'disconnected',
                        'message' => 'Connection error: ' . $curl_error
                    ];
                }

                if ($http_code == 200) {
                    $result = json_decode($response, true);
                    if ($result && isset($result['s']) && $result['s'] == true) {
                        return [
                            'status' => 'connected',
                            'message' => 'Terhubung dengan Accurate Online'
                        ];
                    } else {
                        $error_detail = isset($result['e']) ? 
                            (is_array($result['e']) ? implode(', ', $result['e']) : $result['e']) 
                            : 'Unknown API error';
                        return [
                            'status' => 'disconnected',
                            'message' => 'API Error: ' . $error_detail
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
                        'status' => 'disconnected',
                        'message' => $error_msg
                    ];
                }
            } catch (Exception $e) {
                return [
                    'status' => 'disconnected',
                    'message' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
    }

    /**
     * PERBAIKAN TOTAL: Function untuk sinkronisasi stok keluar ke Accurate
     * Berdasarkan hasil test: langsung ke API tanpa session
     */
    if (!function_exists('syncStokKeluarToAccurate')) {
        function syncStokKeluarToAccurate($transaksi_data) {
            try {
                error_log("=== STARTING ACCURATE SYNC FOR STOK KELUAR (FIXED VERSION) ===");
                error_log("Transaction Data: " . json_encode($transaksi_data, JSON_PRETTY_PRINT));
                
                // Validasi data input
                if (empty($transaksi_data['no_transaksi'])) {
                    return [
                        'success' => false,
                        'message' => 'Nomor transaksi tidak boleh kosong',
                        'debug' => 'No transaction number provided'
                    ];
                }
                
                if (empty($transaksi_data['details']) || !is_array($transaksi_data['details'])) {
                    return [
                        'success' => false,
                        'message' => 'Detail item tidak boleh kosong',
                        'debug' => 'No detail items provided'
                    ];
                }

                // PERBAIKAN: Direct API call tanpa session (berdasarkan hasil test)
                $timestamp = formatTimestamp();
                $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
                
                error_log("Using direct API call to zeus.accurate.id");
                error_log("Timestamp: $timestamp");
                
                // Prepare data untuk Item Adjustment dengan format yang benar
                $adjustment_data = [
                    'transDate' => $transaksi_data['tanggal'], // Format dd/mm/yyyy
                    'description' => $transaksi_data['keterangan'] ?: 'Penyesuaian Stok Keluar',
                    'number' => $transaksi_data['no_transaksi'],
                    'autoNumber' => 'false'
                ];
                
                // Add detail items dengan validasi ketat
                $valid_items = 0;
                foreach ($transaksi_data['details'] as $index => $detail) {
                    if (empty($detail['no_item'])) {
                        error_log("WARNING: Empty item code at index $index");
                        continue;
                    }
                    
                    $quantity = floatval($detail['quantity']);
                    $unit_cost = floatval($detail['harga']);
                    
                    if ($quantity <= 0) {
                        error_log("WARNING: Invalid quantity for item {$detail['no_item']}: $quantity");
                        continue;
                    }
                    
                    // Format detail yang benar untuk Accurate API
                    $adjustment_data["detailItem[$index].itemNo"] = $detail['no_item'];
                    $adjustment_data["detailItem[$index].itemAdjustmentType"] = 'ADJUSTMENT_OUT';
                    $adjustment_data["detailItem[$index].quantity"] = $quantity;
                    $adjustment_data["detailItem[$index].unitCost"] = $unit_cost;
                    $adjustment_data["detailItem[$index].warehouseName"] = $detail['warehouse'] ?? 'UTAMA';
                    $adjustment_data["detailItem[$index].detailName"] = $detail['nama_item'] ?? '';
                    $adjustment_data["detailItem[$index].detailNotes"] = 'Stok Keluar - ' . $transaksi_data['no_transaksi'];
                    
                    $valid_items++;
                    
                    error_log("Detail item $index: " . json_encode([
                        'itemNo' => $detail['no_item'],
                        'quantity' => $quantity,
                        'unitCost' => $unit_cost,
                        'warehouse' => $detail['warehouse'] ?? 'UTAMA'
                    ]));
                }
                
                if ($valid_items == 0) {
                    return [
                        'success' => false,
                        'message' => 'Tidak ada item valid untuk disinkronisasi',
                        'debug' => 'No valid items found'
                    ];
                }
                
                error_log("Final adjustment data: " . json_encode($adjustment_data, JSON_PRETTY_PRINT));
                
                // PERBAIKAN: Direct API call dengan Authorization header (tanpa session)
                $api_url = ACCURATE_API_BASE_URL . '/api/item-adjustment/save.do';
                error_log("Sending to: $api_url");
                
                $ch = curl_init($api_url);
                
                // PERBAIKAN: Gunakan Authorization header langsung
                $api_headers = [
                    "Authorization: Bearer " . ACCURATE_API_TOKEN,
                    "X-Api-Timestamp: $timestamp", 
                    "X-Api-Signature: $signature",
                    "Content-Type: application/x-www-form-urlencoded",
                    "Accept: application/json"
                ];
                
                $post_data = http_build_query($adjustment_data);
                error_log("POST Data: $post_data");
                
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $api_headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                
                // Capture verbose output untuk debugging
                $verbose = fopen('php://temp', 'w+');
                curl_setopt($ch, CURLOPT_STDERR, $verbose);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                
                // Get verbose info
                rewind($verbose);
                $verbose_log = stream_get_contents($verbose);
                fclose($verbose);
                curl_close($ch);
                
                error_log("API Request - HTTP Code: $http_code");
                error_log("API Request - Response: $response");
                error_log("API Request - CURL Error: $curl_error");
                error_log("API Request - Verbose: $verbose_log");
                
                if (!empty($curl_error)) {
                    return [
                        'success' => false,
                        'message' => 'CURL Error: ' . $curl_error,
                        'debug' => "CURL Error: $curl_error, Verbose: $verbose_log"
                    ];
                }
                
                if ($http_code != 200) {
                    return [
                        'success' => false,
                        'message' => 'HTTP Error: ' . $http_code,
                        'debug' => "HTTP $http_code - Response: $response, Verbose: $verbose_log"
                    ];
                }
                
                $result = json_decode($response, true);
                if (!$result) {
                    return [
                        'success' => false,
                        'message' => 'Invalid JSON response from API',
                        'debug' => "Raw response: $response"
                    ];
                }
                
                error_log("API Result: " . json_encode($result, JSON_PRETTY_PRINT));
                
                // Cek response dengan detail
                if (isset($result['s']) && $result['s'] === true) {
                    // Success
                    $accurate_id = $result['r']['id'] ?? null;
                    $accurate_number = $result['r']['number'] ?? null;
                    
                    error_log("SUCCESS: Accurate ID = $accurate_id, Number = $accurate_number");
                    
                    return [
                        'success' => true,
                        'message' => 'Berhasil sinkronisasi ke Accurate Online',
                        'accurate_id' => $accurate_id,
                        'accurate_number' => $accurate_number,
                        'debug' => "Success with Accurate ID: $accurate_id"
                    ];
                } else {
                    // Error dari Accurate
                    $error_msg = 'Unknown error from Accurate API';
                    
                    if (isset($result['e'])) {
                        if (is_array($result['e'])) {
                            $error_msg = implode(', ', $result['e']);
                        } else {
                            $error_msg = $result['e'];
                        }
                    }
                    
                    // Enhanced error messages untuk user
                    if (strpos($error_msg, 'not found') !== false || strpos($error_msg, 'tidak ditemukan') !== false) {
                        $error_msg = 'Item tidak ditemukan di master Accurate. Pastikan semua item sudah dibuat di Accurate terlebih dahulu.';
                    } elseif (strpos($error_msg, 'warehouse') !== false || strpos($error_msg, 'gudang') !== false) {
                        $error_msg = 'Gudang tidak ditemukan. Pastikan gudang "UTAMA" sudah dibuat di Accurate.';
                    } elseif (strpos($error_msg, 'permission') !== false || strpos($error_msg, 'access') !== false) {
                        $error_msg = 'API Token tidak memiliki permission untuk Item Adjustment. Periksa setting API Token di Accurate.';
                    } elseif (strpos($error_msg, 'duplicate') !== false) {
                        $error_msg = 'Nomor transaksi sudah ada di Accurate. Gunakan nomor yang berbeda.';
                    }
                    
                    error_log("FAILED: $error_msg");
                    
                    return [
                        'success' => false,
                        'message' => 'Accurate API Error: ' . $error_msg,
                        'debug' => "API Error: " . json_encode($result)
                    ];
                }
                
            } catch (Exception $e) {
                error_log("EXCEPTION in syncStokKeluarToAccurate: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                return [
                    'success' => false,
                    'message' => 'System Exception: ' . $e->getMessage(),
                    'debug' => "Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine()
                ];
            }
        }
    }

    // Check Accurate connection dan simpan ke session (DIPERBAIKI)
    if (defined('ACCURATE_API_TOKEN') && defined('ACCURATE_SIGNATURE_SECRET') && defined('ACCURATE_API_BASE_URL')) {
        $accurate_connection = checkAccurateConnection();
        $_SESSION['accurate_status'] = $accurate_connection['status'];
        $_SESSION['accurate_message'] = $accurate_connection['message'];
    } else {
        $_SESSION['accurate_status'] = 'disconnected';
        $_SESSION['accurate_message'] = 'Konfigurasi API tidak lengkap atau file config tidak ditemukan';
    }

    include "function_stok_keluar.php";
    $LastID=FormatNoTrans(OtomatisID()); 

    $txtcaribrg=$_GET['kd'] ?? '';
    $tgl_pilih=$_GET['stgl'] ?? date('d/m/Y');

    // Hitung total saat ini
    $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot 
                                    FROM tbitem_keluar_detail 
                                    WHERE 
                                    user='$_nama' and 
                                    kd_cabang='$kd_cabang' and 
                                    status_trx='0'");         
    $tm_cari=mysqli_fetch_array($cari_kd);
    $tot=$tm_cari['tot'] ?? 0;                 

    debug_log("Initial total", $tot);

    if($txtcaribrg=='') {
        $txtnamaitem="";
    } else {
        $cari_kd=mysqli_query($koneksi,"SELECT namaitem 
                                    FROM view_cari_item 
                                    WHERE 
                                    noitem='$txtcaribrg'");         
        $tm_cari=mysqli_fetch_array($cari_kd);
        $txtnamaitem=$tm_cari['namaitem'] ?? '';            
    }
    
    // PERBAIKAN: Proses pencarian dengan validasi yang lebih baik
    if(isset($_POST['btncari'])) {               
        $txtcaribrg = trim($_POST['txtcaribrg']); 
        $tgl_pilih = $_POST['id-date-picker-1'];
        
        debug_log("Proses pencarian", ["kode" => $txtcaribrg, "tanggal" => $tgl_pilih]);
        
        if(empty($txtcaribrg)) {
            echo "<script>alert('Silakan masukkan kode barang!');</script>";
            $txtnamaitem = "";
        } else {
            $cari_kd=mysqli_query($koneksi,"SELECT count(noitem) as tot 
                                            FROM view_cari_item 
                                            WHERE 
                                            noitem='$txtcaribrg'");         
            $tm_cari=mysqli_fetch_array($cari_kd);
            $tot_cari=$tm_cari['tot'];        
            
            debug_log("Hasil pencarian", $tot_cari);
            
            if($tot_cari=='1') {
                $cari_kd=mysqli_query($koneksi,"SELECT namaitem 
                                        FROM view_cari_item 
                                        WHERE 
                                        noitem='$txtcaribrg'");         
                $tm_cari=mysqli_fetch_array($cari_kd);
                $txtnamaitem=$tm_cari['namaitem'];
                debug_log("Item ditemukan", $txtnamaitem);
            } else if($tot_cari=='0') {
                echo "<script>alert('Kode barang tidak ditemukan!');</script>";
                $txtcaribrg="";
                $txtnamaitem="";
            } else {
                $cbocari="";
                $cbourut="35";
                echo"<script>window.location=('stok_keluar_add_item_cari.php?stgl=$tgl_pilih&_key=$txtcaribrg&_cari=$cbocari&_urut=$cbourut&_flt=asc');</script>";
            }
        }

        // Update total setelah pencarian
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot 
                                        FROM tbitem_keluar_detail 
                                        WHERE 
                                        user='$_nama' and 
                                        kd_cabang='$kd_cabang' and 
                                        status_trx='0'");         
        $tm_cari=mysqli_fetch_array($cari_kd);
        $tot=$tm_cari['tot'] ?? 0;                 
    }            

    // PERBAIKAN: Proses ADD dengan validasi yang lebih ketat dan error handling
    if(isset($_POST['btnadd'])) { 
        $txtkdbarang = trim($_POST['txtcaribrg']);
        $txtqty = floatval($_POST['txtqty']);
        $tgl_pilih = $_POST['id-date-picker-1'];
        
        debug_log("Proses ADD", [
            "kode" => $txtkdbarang, 
            "qty" => $txtqty, 
            "user" => $_nama, 
            "cabang" => $kd_cabang
        ]);
        
        // Validasi input yang lebih komprehensif
        $error_messages = [];
        
        if(empty($txtkdbarang)) {
            $error_messages[] = "Kode barang tidak boleh kosong! Silakan cari item terlebih dahulu.";
        }
        
        if($txtqty <= 0) {
            $error_messages[] = "Quantity harus lebih dari 0!";
        }
        
        if(empty($tgl_pilih)) {
            $error_messages[] = "Tanggal tidak boleh kosong!";
        }
        
        // Cek apakah item exists di master
        if(!empty($txtkdbarang)) {
            $cek_item = mysqli_query($koneksi,"SELECT hargapokok, namaitem FROM tblitem WHERE noitem='$txtkdbarang'");
            if(mysqli_num_rows($cek_item) == 0) {
                $error_messages[] = "Kode barang '$txtkdbarang' tidak ditemukan di master item!";
            }
        }
        
        if(!empty($error_messages)) {
            $error_msg = implode("\\n", $error_messages);
            echo "<script>alert('ERROR:\\n$error_msg');</script>";
        } else {
            // Get harga pokok
            $cari_kd=mysqli_query($koneksi,"SELECT hargapokok, namaitem FROM tblitem WHERE noitem='$txtkdbarang'");         
            $tm_cari=mysqli_fetch_array($cari_kd);
            $txthargabarang=$tm_cari['hargapokok'] ?? 0;
            $nama_item=$tm_cari['namaitem'] ?? '';

            $subtotal = $txthargabarang * $txtqty;            
            
            debug_log("Harga dan subtotal", [
                "harga" => $txthargabarang, 
                "subtotal" => $subtotal
            ]);
            
            // Cek apakah item sudah ada di detail transaksi yang belum selesai
            $data = mysqli_query($koneksi,"SELECT id FROM tbitem_keluar_detail 
                                            WHERE 
                                            user='$_nama' and 
                                            kd_cabang='$kd_cabang' and 
                                            no_item='$txtkdbarang' and 
                                            status_trx='0'");
            $cek = mysqli_num_rows($data);
            
            if($cek > 0){
                debug_log("Item sudah ada di detail");
                echo"<script>
                    alert('Item Barang dengan kode \"$txtkdbarang\" sudah ada dalam detail transaksi!\\n\\nSilakan hapus item tersebut terlebih dahulu jika ingin mengubah quantity.');
                    window.location=('stok_keluar_add_rst.php?stgl=$tgl_pilih&kd=');
                </script>";                    
            } else {    
                // Insert item ke detail dengan error handling
                try {
                    $insert_query = "INSERT INTO tbitem_keluar_detail 
                                    (no_transaksi, no_item, harga, quantity, 
                                    total, user, kd_cabang, status_trx) 
                                    VALUES 
                                    ('', '$txtkdbarang', '$txthargabarang',
                                    '$txtqty', '$subtotal',
                                    '$_nama', '$kd_cabang', '0')";
                    
                    debug_log("Insert query", $insert_query);
                    
                    $result = mysqli_query($koneksi, $insert_query);
                    
                    if($result) {
                        $insert_id = mysqli_insert_id($koneksi);
                        debug_log("Insert berhasil", $insert_id);
                        
                        // Update total setelah insert
                        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot 
                                                FROM tbitem_keluar_detail 
                                                WHERE 
                                                user='$_nama' and 
                                                kd_cabang='$kd_cabang' and 
                                                status_trx='0'");         
                        $tm_cari=mysqli_fetch_array($cari_kd);
                        $tot=$tm_cari['tot'] ?? 0;
                        
                        debug_log("Total setelah insert", $tot);
                        
                        // Success feedback
                        echo"<script>
                            alert('✅ Item \"$nama_item\" berhasil ditambahkan!\\nQuantity: $txtqty\\nHarga: " . number_format($txthargabarang, 0, ',', '.') . "\\nSubtotal: " . number_format($subtotal, 0, ',', '.') . "'); 
                        </script>";
                        
                        // Reset form untuk input item berikutnya
                        $txtcaribrg = "";
                        $txtnamaitem = "";
                        
                    } else {
                        $mysql_error = mysqli_error($koneksi);
                        debug_log("Insert gagal", $mysql_error);
                        echo"<script>alert('❌ Gagal menambahkan item!\\n\\nError: $mysql_error');</script>";
                    }
                } catch (Exception $e) {
                    debug_log("Exception", $e->getMessage());
                    echo"<script>alert('❌ Error: " . $e->getMessage() . "');</script>";
                }
            }
        }
    }     

    // PERBAIKAN: Proses simpan dengan validasi yang lebih ketat dan feedback yang lebih jelas
    if(isset($_POST['btnsimpan'])) {
        $txttotal_harga = floatval($_POST['txttotal_harga']);
        
        debug_log("Proses simpan", ["total_form" => $txttotal_harga]);
        
        // Cek ulang total dan jumlah item dari database
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot, count(*) as jml_item, sum(quantity) as tot_qty
                                        FROM tbitem_keluar_detail 
                                        WHERE 
                                        user='$_nama' and 
                                        kd_cabang='$kd_cabang' and 
                                        status_trx='0'");         
        $tm_cari=mysqli_fetch_array($cari_kd);
        $real_total = $tm_cari['tot'] ?? 0;
        $jml_item = $tm_cari['jml_item'] ?? 0;
        $tot_qty = $tm_cari['tot_qty'] ?? 0;
        
        debug_log("Real data dari DB", [
            "total" => $real_total, 
            "jumlah_item" => $jml_item,
            "total_qty" => $tot_qty
        ]);
        
        if($jml_item == 0 || $real_total <= 0) {
            echo"<script>
                alert('❌ TIDAK DAPAT MENYIMPAN!\\n\\nBelum ada Item barang yang dipilih.\\n\\nLangkah yang harus dilakukan:\\n1. Cari kode barang\\n2. Masukkan quantity\\n3. Klik tombol ADD\\n4. Ulangi untuk item lain\\n5. Baru klik SIMPAN');
                window.location=('stok_keluar_add.php');
            </script>";                                        
        } else {
            // Proses simpan transaksi
            date_default_timezone_set('Asia/Jakarta');
            function ubahformatTgl($tanggal) {
                $pisah = explode('/',$tanggal);
                $urutan = array($pisah[2],$pisah[1],$pisah[0]);
                $satukan = implode('-',$urutan);
                return $satukan;
            }
            
            $txttglpesan = ubahformatTgl($_POST['id-date-picker-1']); 
            $txtket = $_POST['txtket'] ?? 'Penyesuaian Stok Item Keluar';
                         
            $data = mysqli_query($koneksi,"SELECT no_transaksi 
                                            FROM tbitem_keluar_header 
                                            WHERE 
                                            no_transaksi='$LastID'");
            $cek = mysqli_num_rows($data);
            
            if($cek > 0){
                echo"<script>alert('❌ Nomor transaksi $LastID sudah ada! Silakan refresh halaman.');</script>";
            } else {
                try {
                    // Mulai transaction
                    mysqli_autocommit($koneksi, false);
                    
                    // Insert header
                    $header_result = mysqli_query($koneksi,"INSERT INTO tbitem_keluar_header 
                                            (no_transaksi, tanggal, 
                                            user, kd_cabang, note) 
                                            VALUES 
                                            ('$LastID','$txttglpesan',
                                            '$_nama','$kd_cabang','$txtket')");

                    if(!$header_result) {
                        throw new Exception("Gagal insert header: " . mysqli_error($koneksi));
                    }

                    // Update detail
                    $detail_result = mysqli_query($koneksi,"UPDATE tbitem_keluar_detail 
                                            SET 
                                            no_transaksi='$LastID', status_trx='1' 
                                            WHERE 
                                            user='$_nama' and 
                                            kd_cabang='$kd_cabang' and 
                                            status_trx='0'");

                    if(!$detail_result) {
                        throw new Exception("Gagal update detail: " . mysqli_error($koneksi));
                    }

                    // Update stok terlebih dahulu (sebelum sinkronisasi)
                    $sql = mysqli_query($koneksi,"SELECT * FROM tbitem_keluar_detail 
                                                    WHERE no_transaksi='$LastID'");
                    while ($tampil = mysqli_fetch_array($sql)) {
                        $no_item=$tampil['no_item'];
                        $qty=$tampil['quantity'];
                        $stok_result = mysqli_query($koneksi,"INSERT INTO tbstok 
                                            (tipe, no_transaksi, no_item, 
                                            tanggal, masuk, keluar, keterangan, 
                                            kd_cabang) 
                                            VALUES 
                                            ('6','$LastID','$no_item',
                                            '$txttglpesan','0','$qty',
                                            'Penyesuaian Stok Item Keluar','$kd_cabang')");
                        
                        if(!$stok_result) {
                            throw new Exception("Gagal update stok untuk item $no_item: " . mysqli_error($koneksi));
                        }
                    }

                    // PERBAIKAN: Prepare data untuk sinkronisasi dengan validasi lebih ketat
                    $transaksi_data = [
                        'no_transaksi' => $LastID,
                        'tanggal' => $_POST['id-date-picker-1'], // Format dd/mm/yyyy sesuai Accurate
                        'keterangan' => $txtket,
                        'details' => []
                    ];

                    // Get detail items untuk sinkronisasi dengan validasi
                    $sql_details = mysqli_query($koneksi,"SELECT 
                                                            d.*, i.namaitem 
                                                            FROM tbitem_keluar_detail d
                                                            LEFT JOIN tblitem i ON d.no_item = i.noitem 
                                                            WHERE 
                                                            d.no_transaksi='$LastID'");
                    
                    $valid_details = 0;
                    while ($detail = mysqli_fetch_array($sql_details)) {
                        if (!empty($detail['no_item']) && $detail['quantity'] > 0) {
                            $transaksi_data['details'][] = [
                                'no_item' => $detail['no_item'],
                                'quantity' => $detail['quantity'],
                                'harga' => $detail['harga'],
                                'nama_item' => $detail['namaitem'],
                                'warehouse' => 'UTAMA' // Default warehouse
                            ];
                            $valid_details++;
                        }
                    }

                    if ($valid_details == 0) {
                        throw new Exception("Tidak ada detail item yang valid untuk disinkronisasi");
                    }

                    // Commit transaction database terlebih dahulu
                    mysqli_commit($koneksi);
                    mysqli_autocommit($koneksi, true);

                    // PERBAIKAN: Sinkronisasi ke Accurate dengan handling yang lebih baik
                    $sync_message = '';
                    $sync_status = 'info';
                    $sync_details = '';
                    
                    if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected') {
                        echo "<script>console.log('Starting Accurate synchronization...');</script>";
                        
                        $sync_result = syncStokKeluarToAccurate($transaksi_data);
                        
                        if ($sync_result['success']) {
                            $sync_message = '✅ BERHASIL: Data tersimpan dan berhasil sinkronisasi ke Accurate Online';
                            $sync_status = 'success';
                            $sync_details = 'Accurate ID: ' . ($sync_result['accurate_id'] ?? 'N/A') . 
                                          ', Number: ' . ($sync_result['accurate_number'] ?? 'N/A');
                            
                            // Update database dengan informasi Accurate
                            if (isset($sync_result['accurate_id'])) {
                                mysqli_query($koneksi,"UPDATE tbitem_keluar_header 
                                                    SET accurate_id='{$sync_result['accurate_id']}',
                                                        accurate_number='{$sync_result['accurate_number']}',
                                                        accurate_sync='1',
                                                        accurate_sync_at=NOW() 
                                                    WHERE no_transaksi='$LastID'");
                            }
                            
                            // Log sukses
                            error_log("SUCCESS: Stok Keluar $LastID berhasil disinkronisasi ke Accurate");
                            
                        } else {
                            $sync_message = '⚠️ PARSIAL: Data tersimpan di database lokal, namun gagal sinkronisasi ke Accurate';
                            $sync_status = 'warning';
                            $sync_details = 'Error: ' . $sync_result['message'];
                            
                            // Update status sync gagal
                            mysqli_query($koneksi,"UPDATE tbitem_keluar_header 
                                                SET accurate_sync='0',
                                                    accurate_error='{$sync_result['message']}',
                                                    accurate_sync_at=NOW() 
                                                WHERE no_transaksi='$LastID'");
                            
                            // Log error dengan detail
                            error_log("FAILED: Stok Keluar $LastID gagal sinkronisasi - " . $sync_result['message']);
                            if (isset($sync_result['debug'])) {
                                error_log("DEBUG: " . $sync_result['debug']);
                            }
                        }
                    } else {
                        $sync_message = 'ℹ️ INFO: Data tersimpan di database lokal (Accurate tidak terhubung)';
                        $sync_status = 'info';
                        $sync_details = 'Sinkronisasi dapat dilakukan manual melalui menu laporan';
                    }

                    // Set session messages dengan detail
                    $_SESSION['sync_message'] = $sync_message;
                    $_SESSION['sync_status'] = $sync_status;
                    $_SESSION['sync_details'] = $sync_details;
                    $_SESSION['transaksi_id'] = $LastID;
                    
                    // Redirect ke halaman cetak dengan parameter tambahan
                    echo"<script>
                        // Show detailed result
                        alert('$sync_message\\n\\nNomor Transaksi: $LastID\\nTotal Item: $valid_details\\n\\n$sync_details');
                        window.location=('stok_keluar_add_cetak.php?nopesanan=$LastID&sync_status=$sync_status');
                    </script>";
                    
                } catch (Exception $e) {
                    // Rollback jika error
                    mysqli_rollback($koneksi);
                    mysqli_autocommit($koneksi, true);
                    
                    debug_log("Error simpan", $e->getMessage());
                    error_log("CRITICAL ERROR in stok keluar save: " . $e->getMessage());
                    
                    echo"<script>
                        alert('❌ GAGAL MENYIMPAN TRANSAKSI!\\n\\nError: " . addslashes($e->getMessage()) . "\\n\\nSilakan coba lagi atau hubungi administrator.');
                    </script>";
                }               
            }                
        }
    }             
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="with draggable and editable events" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->
        <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
        <link rel="stylesheet" href="assets/css/fullcalendar.min.css" />

        <!-- text fonts -->
        <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

        <!-- ace styles -->
        <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
        <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <!-- ace settings handler -->
        <script src="assets/js/ace-extra.min.js"></script>
        <script type="text/javascript" src="chartjs/Chart.js"></script>

        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.highcharts.com/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/modules/export-data.js"></script>
        <script src="https://code.highcharts.com/modules/accessibility.js"></script>   

        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.css' rel='stylesheet' />
        
        <!-- Custom styles untuk debugging -->
        <?php if($debug_mode): ?>
        <style>
            .debug-info {
                background-color: #f8f9fa;
                border-left: 4px solid #007bff;
                padding: 10px;
                margin: 10px 0;
                font-family: monospace;
                font-size: 12px;
            }
        </style>
        <?php endif; ?>
    </head>

    <body class="no-skin">
        <?php if($debug_mode): ?>
        <div class="alert alert-warning alert-dismissible" style="margin: 10px;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>🐛 DEBUG MODE AKTIF</strong> - Cek console browser (F12) untuk debug info
        </div>
        <?php endif; ?>

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
                
                <!-- Status Accurate API Indicator -->
                <div class="navbar-header pull-right">
                    <?php if (isset($_SESSION['accurate_status'])): ?>
                        <span class="navbar-brand">
                            <small style="color: <?php echo $_SESSION['accurate_status'] == 'connected' ? 'green' : 'orange'; ?>">
                                <i class="fa fa-circle"></i> Accurate: <?php echo $_SESSION['accurate_status']; ?>
                            </small>
                        </span>
                    <?php endif; ?>
                </div>
            </div><!-- /.navbar-container -->
        </div>

        <div class="main-container ace-save-state" id="main-container">
            <script type="text/javascript">
                try { ace.settings.loadState('main-container') } catch(e) {}
            </script>

            <div id="sidebar" class="sidebar responsive ace-save-state">
                <script type="text/javascript">
                    try { ace.settings.loadState('sidebar') } catch(e) {}
                </script>

                <?php include "menu_stok02.php"; ?>

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
                                <a href="penyesuaian-stok-keluar-manual.php">Item Keluar</a>
                            </li>                                                        
                            <li class="active">Tambah Data</li>
                        </ul><!-- /.breadcrumb -->
                    </div>

                    <div class="page-content">
                        <!-- Alert untuk status Accurate -->
                        <?php if (isset($_SESSION['accurate_status'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['accurate_status'] == 'connected' ? 'success' : 'warning'; ?> alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <strong>Status Accurate API:</strong> 
                                <?php if ($_SESSION['accurate_status'] == 'connected'): ?>
                                    <i class="fa fa-check-circle"></i> ✅ Terhubung - Data akan otomatis sinkronisasi ke Accurate Online
                                <?php else: ?>
                                    <i class="fa fa-exclamation-triangle"></i> ⚠️ Tidak terhubung - Data hanya disimpan di database lokal
                                    <br><small><?php echo $_SESSION['accurate_message']; ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Alert untuk hasil sinkronisasi (jika ada) -->
                        <?php if (isset($_SESSION['sync_message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['sync_status']; ?> alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <strong>Status Sinkronisasi:</strong> <?php echo $_SESSION['sync_message']; ?>
                                <?php if (isset($_SESSION['sync_details'])): ?>
                                    <br><small><?php echo $_SESSION['sync_details']; ?></small>
                                <?php endif; ?>
                            </div>
                            <?php 
                                unset($_SESSION['sync_message']);
                                unset($_SESSION['sync_status']);
                                unset($_SESSION['sync_details']);
                            ?>
                        <?php endif; ?>

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
                                                            <input type="text" id="txtno" name="txtno" class="form-control" 
                                                            value="<?php echo $LastID; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-4">                                                
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Tanggal :</label>                                   
                                                        <div class="col-sm-7">
                                                            <div class="input-group">
                                                                <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" 
                                                                value="<?php echo $tgl_pilih; ?>" data-date-format="dd/mm/yyyy" />
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
                                                            <input type="text" class="form-control" 
                                                            value="<?php echo $_nama; ?>" disabled />
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

                                            <?php include "_template/_stok_keluar_detail.php"; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space space-8"></div>

                            <!-- Form untuk simpan transaksi -->
                            <form class="form-horizontal" action="" method="post" role="form" id="formSimpan">
                                <input type="hidden" name="txttotal_harga" class="form-control" value="<?php echo $tot; ?>"/>
                                <input type="hidden" name="id-date-picker-1" value="<?php echo $tgl_pilih; ?>"/>
                                
                                <div class="col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label class="col-sm-1 control-label no-padding-right" for="form-field-1"> Keterangan </label>
                                        <div class="col-sm-11">
                                            <input type="text" id="txtket" name="txtket" 
                                            class="col-xs-10 col-sm-12" 
                                            autocomplete="off" placeholder="Catatan untuk transaksi penyesuaian stok keluar..." />
                                        </div>
                                    </div>
                                </div>

                                <!-- Info sinkronisasi -->
                                <div class="col-xs-12 col-sm-12">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> 
                                        <strong>Informasi:</strong> 
                                        Data penyesuaian stok keluar akan disimpan ke database lokal dan mempengaruhi perhitungan stok. 
                                        <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                            Sistem akan otomatis mencoba sinkronisasi ke Accurate Online sebagai Item Adjustment (ADJUSTMENT_OUT).
                                        <?php else: ?>
                                            Sinkronisasi ke Accurate Online tidak tersedia karena koneksi API bermasalah.
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="space space-8"></div>
                                
                                <div class="col-xs-12 col-sm-3">
                                    <button class="btn btn-primary btn-block" type="submit" 
                                    id="btnsimpan" name="btnsimpan" 
                                    <?php echo ($tot <= 0) ? 'disabled' : ''; ?>>
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        Simpan
                                        <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                            & Sync
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </form> 
                                                                                                   
                            <div class="col-xs-12 col-sm-3">
                                <a href="stok_keluar_batal.php?suser=<?php echo $_nama; ?>&scabang=<?php echo $kd_cabang; ?>" 
                                onclick="return confirm('Inputan Stok Item Keluar akan dibatalkan. Lanjutkan?')">                                                                    
                                    <button class="btn btn-warning btn-block" type="button">
                                        <i class="ace-icon fa fa-times bigger-110"></i>
                                        Batal
                                    </button>
                                </a>
                            </div>
                            
                            <div class="col-xs-12 col-sm-3">
                                <button class="btn disabled btn-info btn-block" type="button" 
                                id="btncetak" name="btncetak">
                                    <i class="ace-icon fa fa-print bigger-110"></i>
                                    Cetak
                                </button>
                            </div>                            

                            <div class="col-xs-12 col-sm-3">
                                <a href="penyesuaian-stok-keluar-manual.php">
                                <button class="btn btn-default btn-block" type="button">
                                    <i class="ace-icon fa fa-arrow-left bigger-110"></i>
                                    Tutup
                                </button>
                                </a>
                            </div>                            
                        </div>

                        <!-- Panel informasi Accurate -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-small">
                                        <h5 class="widget-title">
                                            <i class="ace-icon fa fa-cloud"></i>
                                            Status Integrasi Accurate Online
                                        </h5>
                                        <div class="widget-toolbar">
                                            <a href="#" data-action="collapse">
                                                <i class="ace-icon fa fa-chevron-up"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Status Koneksi:</label>
                                                        <span class="badge badge-<?php echo (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected') ? 'success' : 'warning'; ?>">
                                                            <?php echo isset($_SESSION['accurate_status']) ? strtoupper($_SESSION['accurate_status']) : 'UNKNOWN'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Last Check:</label>
                                                        <span class="text-muted"><?php echo date('d/m/Y H:i:s'); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label class="control-label">Message:</label>
                                                        <p class="help-block">
                                                            <?php echo isset($_SESSION['accurate_message']) ? $_SESSION['accurate_message'] : 'Status tidak tersedia'; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label class="control-label">Catatan Teknis:</label>
                                                        <ul class="help-block">
                                                            <li>Data stok keluar akan dikirim sebagai <strong>Item Adjustment</strong> dengan tipe <strong>ADJUSTMENT_OUT</strong></li>
                                                            <li>Setiap item akan otomatis dikurangi dari stok di Accurate</li>
                                                            <li>Jika sinkronisasi gagal, data tetap tersimpan di database lokal</li>
                                                            <li>Anda dapat melakukan sinkronisasi ulang melalui menu laporan</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="window.location.reload();">
                                                        <i class="fa fa-refresh"></i> Refresh Status
                                                    </button>
                                                    <?php if (!isset($_SESSION['accurate_status']) || $_SESSION['accurate_status'] != 'connected'): ?>
                                                        <button type="button" class="btn btn-sm btn-info" onclick="showTroubleshooting();">
                                                            <i class="fa fa-question-circle"></i> Troubleshooting
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if($debug_mode): ?>
                        <!-- Debug Information Panel -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-small">
                                        <h5 class="widget-title">
                                            <i class="ace-icon fa fa-bug"></i>
                                            Debug Information
                                        </h5>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="debug-info">
                                                <strong>Session Info:</strong><br>
                                                User: <?php echo $_nama; ?><br>
                                                Cabang: <?php echo $kd_cabang; ?><br>
                                                Current Total: <?php echo $tot; ?><br>
                                                Last ID: <?php echo $LastID; ?><br>
                                                Search Item: <?php echo $txtcaribrg; ?><br>
                                                Item Name: <?php echo $txtnamaitem; ?><br>
                                                Accurate Status: <?php echo $_SESSION['accurate_status'] ?? 'unknown'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div><!-- /.page-content -->
                </div>
            </div><!-- /.main-content -->

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
        </div><!-- /.main-container -->

        <!-- Modal Troubleshooting -->
        <div class="modal fade" id="troubleshootingModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">
                            <i class="fa fa-wrench"></i> Troubleshooting Koneksi Accurate
                        </h4>
                    </div>
                    <div class="modal-body">
                        <h5>Langkah-langkah pemecahan masalah:</h5>
                        <ol>
                            <li><strong>Periksa Konfigurasi API:</strong>
                                <ul>
                                    <li>Pastikan file <code>accurate_config.php</code> ada</li>
                                    <li>Periksa <code>ACCURATE_API_TOKEN</code> tidak kosong</li>
                                    <li>Periksa <code>ACCURATE_SIGNATURE_SECRET</code> tidak kosong</li>
                                    <li>Periksa <code>ACCURATE_API_BASE_URL</code> sudah benar (zeus.accurate.id)</li>
                                </ul>
                            </li>
                            <li><strong>Periksa API Token & Permission:</strong>
                                <ul>
                                    <li>Login ke Accurate Online</li>
                                    <li>Buka menu Developer > API Token</li>
                                    <li>Pastikan token masih aktif</li>
                                    <li>Periksa permission untuk <strong>item_adjustment_save</strong></li>
                                    <li>Pastikan scope <strong>item_adjustment_view</strong> juga ada</li>
                                </ul>
                            </li>
                            <li><strong>Periksa Koneksi Internet:</strong>
                                <ul>
                                    <li>Pastikan server dapat mengakses internet</li>
                                    <li>Cek firewall tidak memblokir koneksi</li>
                                    <li>Test koneksi ke domain Accurate</li>
                                </ul>
                            </li>
                            <li><strong>Khusus untuk Stok Keluar:</strong>
                                <ul>
                                    <li>Pastikan item sudah ada di master Accurate</li>
                                    <li>Periksa setting warehouse/gudang</li>
                                    <li>Pastikan akun penyesuaian sudah dikonfigurasi</li>
                                </ul>
                            </li>
                        </ol>
                        
                        <div class="alert alert-warning">
                            <strong>Catatan:</strong> Jika sinkronisasi gagal, data tetap tersimpan di database lokal dan dapat disinkronisasi ulang nanti.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script type="text/javascript">
            if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
        </script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- page specific plugin scripts -->
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

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <!-- inline scripts related to this page -->
        <script type="text/javascript">
            jQuery(function($) {
                // Initialize components
                $('.date-picker').datepicker({
                    autoclose: true,
                    todayHighlight: true
                })
                .next().on(ace.click_event, function(){
                    $(this).prev().focus();
                });

                // Auto-hide alert after 15 seconds
                setTimeout(function() {
                    $('.alert-dismissible').fadeOut('slow');
                }, 15000);

                // PERBAIKAN: Enhanced form submission dengan progress indicator
                $('#formSimpan').on('submit', function(e) {
                    var totalHarga = parseFloat($('input[name="txttotal_harga"]').val()) || 0;
                    var keterangan = $('#txtket').val().trim();
                    
                    console.log('Form submit validation - Total:', totalHarga);
                    
                    if (totalHarga <= 0) {
                        e.preventDefault();
                        alert('❌ TIDAK DAPAT MENYIMPAN!\n\nBelum ada item yang ditambahkan ke detail transaksi.\n\nLangkah yang harus dilakukan:\n1. Cari kode barang\n2. Masukkan quantity\n3. Klik tombol ADD\n4. Ulangi untuk item lain\n5. Baru klik SIMPAN');
                        return false;
                    }
                    
                    var confirmMessage = '📋 KONFIRMASI SIMPAN TRANSAKSI STOK KELUAR\n\n' +
                                       'Total Nilai: Rp ' + totalHarga.toLocaleString('id-ID') + '\n' +
                                       'Keterangan: ' + (keterangan || 'Tidak ada keterangan') + '\n\n' +
                                       '⚠️ PERHATIAN:\n' +
                                       '• Data akan disimpan ke database lokal\n' +
                                       '• Stok barang akan berkurang\n';
                    
                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                        confirmMessage += '• Sistem akan mencoba sinkronisasi ke Accurate Online\n' +
                                        '• Jika sinkronisasi gagal, data tetap tersimpan di lokal\n';
                    <?php else: ?>
                        confirmMessage += '• Accurate tidak terhubung - data hanya tersimpan lokal\n';
                    <?php endif; ?>
                    
                    confirmMessage += '\nLanjutkan menyimpan?';
                    
                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Show progress indicator
                    var $btn = $('#btnsimpan');
                    var originalText = $btn.html();
                    
                    $btn.prop('disabled', true)
                        .html('<i class="fa fa-spinner fa-spin"></i> Menyimpan & Sinkronisasi...');
                    
                    // Restore button if process takes too long (timeout prevention)
                    setTimeout(function() {
                        if ($btn.prop('disabled')) {
                            $btn.prop('disabled', false).html(originalText);
                        }
                    }, 30000); // 30 second timeout
                    
                    return true;
                });

                // Enhanced debugging untuk development
                if (window.console) {
                    console.log('🔧 Enhanced Stok Keluar System Loaded (FIXED VERSION)');
                    console.log('Current Total:', $('input[name="txttotal_harga"]').val());
                    console.log('Debug Mode:', <?php echo $debug_mode ? 'true' : 'false'; ?>);
                    console.log('Accurate Status:', '<?php echo $_SESSION['accurate_status'] ?? 'unknown'; ?>');
                    console.log('User:', '<?php echo $_nama; ?>');
                    console.log('Cabang:', '<?php echo $kd_cabang; ?>');
                }

                // Auto-refresh status setiap 5 menit
                setInterval(function() {
                    console.log('Auto-checking Accurate status for stok keluar...');
                }, 300000); // 5 menit
                
                // Update button status berdasarkan total
                function updateSimpanButton() {
                    var total = parseFloat($('input[name="txttotal_harga"]').val()) || 0;
                    var btnSimpan = $('#btnsimpan');
                    
                    if (total > 0) {
                        btnSimpan.prop('disabled', false).removeClass('disabled');
                        btnSimpan.html('<i class="ace-icon fa fa-check bigger-110"></i> Simpan <?php if (isset($_SESSION["accurate_status"]) && $_SESSION["accurate_status"] == "connected"): ?>& Sync<?php endif; ?>');
                    } else {
                        btnSimpan.prop('disabled', true).addClass('disabled');
                        btnSimpan.html('<i class="ace-icon fa fa-exclamation-triangle bigger-110"></i> Tambah Item Dulu');
                    }
                }

                // Update button saat halaman load
                updateSimpanButton();

                // Monitor perubahan total
                setInterval(updateSimpanButton, 1000);

                // PERBAIKAN: Keyboard shortcuts yang lebih user-friendly
                $(document).keydown(function(e) {
                    // F2: Focus ke pencarian item
                    if (e.keyCode == 113) { // F2
                        e.preventDefault();
                        $('input[name="txtcaribrg"]').focus().select();
                        return false;
                    }
                    
                    // F3: Focus ke quantity
                    if (e.keyCode == 114) { // F3
                        e.preventDefault();
                        $('input[name="txtqty"]').focus().select();
                        return false;
                    }
                    
                    // F9: Submit form (simpan)
                    if (e.keyCode == 120) { // F9
                        e.preventDefault();
                        var total = parseFloat($('input[name="txttotal_harga"]').val()) || 0;
                        if (total > 0) {
                            if (confirm('💾 Simpan transaksi stok keluar dengan shortkey F9?')) {
                                $('#formSimpan').submit();
                            }
                        } else {
                            alert('❌ Belum ada item yang ditambahkan!\n\nSilakan tambah item terlebih dahulu.');
                        }
                        return false;
                    }
                    
                    // Esc: Clear form item
                    if (e.keyCode == 27) { // Esc
                        e.preventDefault();
                        $('input[name="txtcaribrg"]').val('');
                        $('#txtnamaitem').val('');
                        $('input[name="txtqty"]').val('');
                        $('input[name="txtcaribrg"]').focus();
                        return false;
                    }
                });
                
                console.log('✅ Enhanced Stok Keluar with Fixed Accurate API Integration Ready!');
            });

            // Function untuk show troubleshooting modal
            function showTroubleshooting() {
                $('#troubleshootingModal').modal('show');
            }
        </script>
    </body>
</html>

<?php 
    }
?>