<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

if (isset($_POST['btnsimpan'])) {
    // Get form data
    $tipe_item = mysqli_real_escape_string($koneksi, $_POST['tipe_item']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['cbojenis']);
    $satuan = mysqli_real_escape_string($koneksi, $_POST['cbosatuan']);
    $harga_beli = floatval($_POST['txthargabeli']);
    $harga_jual = floatval($_POST['txthargajual']);
    $supplier = mysqli_real_escape_string($koneksi, $_POST['cbosupplier'] ?? '');
    $rak_barang = intval($_POST['cborak'] ?? 0);
    
    $success = false;
    $error_msg = '';
    $kode_generated = '';
    
    try {
        mysqli_autocommit($koneksi, false); // Start transaction
        
        if ($tipe_item == 'ORI') {
            // ORI (Genuine Part) Processing
            $merek = mysqli_real_escape_string($koneksi, $_POST['cbomerek']);
            $kode_part = mysqli_real_escape_string($koneksi, $_POST['txtkodepart']);
            $nama_resmi = mysqli_real_escape_string($koneksi, $_POST['txtnamaresmi']);
            $nama_item = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
            
            // Validate required fields
            if (empty($merek) || empty($kode_part) || empty($nama_resmi) || empty($nama_item)) {
                throw new Exception("Semua field ORI harus diisi!");
            }
            
            // Validate if part code already exists
            $check_query = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE noitem='$kode_part'");
            if (!$check_query) {
                throw new Exception("Error checking existing part code: " . mysqli_error($koneksi));
            }
            
            $check_result = mysqli_fetch_array($check_query);
            if ($check_result['count'] > 0) {
                throw new Exception("Kode part '$kode_part' sudah ada dalam database!");
            }
            
            // Insert ORI item
            $insert_query = "INSERT INTO tblitem (
                noitem, namaitem, jenis, satuan, hargapokok, hargajual, 
                supplier, rakbarang, tipe_item, merek, kode_part_resmi, 
                nama_part_resmi, status_validasi, statusitem, created_by,
                stokmin, quantity, totalpokok, kodebarcode
            ) VALUES (
                '$kode_part', '$nama_item', '$jenis', '$satuan', '$harga_beli', '$harga_jual',
                '$supplier', '$rak_barang', 'ORI', '$merek', '$kode_part', '$nama_resmi',
                'validated', '1', '$id_user', 0, 0, 0, ''
            )";
            
            if (!mysqli_query($koneksi, $insert_query)) {
                throw new Exception("Gagal menambahkan item ORI: " . mysqli_error($koneksi));
            }
            
            $kode_generated = $kode_part;
            $success = true;
            
        } else if ($tipe_item == 'NON_ORI') {
            // NON-ORI (Aftermarket/Imitasi) Processing
            $nama_item = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
            $penggunaan_motor = mysqli_real_escape_string($koneksi, $_POST['txtpenggunaan']);
            $merek_tipe = mysqli_real_escape_string($koneksi, $_POST['txtmerektipe']);
            $kategori_rak = mysqli_real_escape_string($koneksi, $_POST['cbokategorirak']);
            
            // Validate required fields
            if (empty($nama_item) || empty($penggunaan_motor) || empty($kategori_rak)) {
                throw new Exception("Nama part, penggunaan motor, dan kategori rak harus diisi!");
            }
            
            // Generate auto code IM-XXYYYY with improved logic
            $prefix = "IM";
            
            // Check if category exists in kategori_rak table
            $cat_check = mysqli_query($koneksi, "SELECT kode FROM tbkategori_rak WHERE kode = '$kategori_rak'");
            if (!$cat_check || mysqli_num_rows($cat_check) == 0) {
                throw new Exception("Kategori rak '$kategori_rak' tidak valid!");
            }
            
            // Get last number for this category with better query
            $last_query = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(noitem, 6) AS UNSIGNED)) as last_num 
                                               FROM tblitem 
                                               WHERE noitem LIKE '$prefix-$kategori_rak%' 
                                               AND tipe_item = 'NON_ORI'");
            
            if (!$last_query) {
                throw new Exception("Error generating auto code: " . mysqli_error($koneksi));
            }
            
            $last_result = mysqli_fetch_array($last_query);
            $next_num = ($last_result['last_num'] ?? 0) + 1;
            
            // Ensure we don't exceed 9999 items per category
            if ($next_num > 9999) {
                throw new Exception("Maksimum items untuk kategori $kategori_rak sudah tercapai (9999)!");
            }
            
            $kode_auto = $prefix . "-" . $kategori_rak . str_pad($next_num, 4, '0', STR_PAD_LEFT);
            
            // Double check for uniqueness
            $unique_check = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE noitem = '$kode_auto'");
            if ($unique_check) {
                $unique_result = mysqli_fetch_array($unique_check);
                if ($unique_result['count'] > 0) {
                    // Try next number if somehow exists
                    $next_num++;
                    $kode_auto = $prefix . "-" . $kategori_rak . str_pad($next_num, 4, '0', STR_PAD_LEFT);
                }
            }
            
            // Format nama item: [Nama Part] [Penggunaan Motor] IMI
            $nama_formatted = $nama_item . " " . $penggunaan_motor . " IMI";
            
            // Insert NON-ORI item
            $insert_query = "INSERT INTO tblitem (
                noitem, namaitem, jenis, satuan, hargapokok, hargajual,
                supplier, rakbarang, tipe_item, penggunaan_motor, merek_tipe,
                kategori_rak, status_validasi, statusitem, created_by,
                stokmin, quantity, totalpokok, kodebarcode
            ) VALUES (
                '$kode_auto', '$nama_formatted', '$jenis', '$satuan', '$harga_beli', '$harga_jual',
                '$supplier', '$rak_barang', 'NON_ORI', '$penggunaan_motor', '$merek_tipe',
                '$kategori_rak', 'pending_validation', '1', '$id_user',
                0, 0, 0, ''
            )";
            
            if (!mysqli_query($koneksi, $insert_query)) {
                throw new Exception("Gagal menambahkan item NON-ORI: " . mysqli_error($koneksi));
            }
            
            $kode_generated = $kode_auto;
            $success = true;
            
        } else {
            throw new Exception("Tipe item tidak valid!");
        }
        
        // Insert initial stock record
        if ($success && $kd_cabang) {
            $stock_query = "INSERT INTO tblitem_stok (noitem, kode_cabang, stokmin, stok_maks, stok_awal, rakbarang) 
                           VALUES ('$kode_generated', '$kd_cabang', 0, 100, 0, '$rak_barang')
                           ON DUPLICATE KEY UPDATE rakbarang='$rak_barang'";
            
            if (!mysqli_query($koneksi, $stock_query)) {
                // Don't fail the whole transaction for stock insert error, just log it
                error_log("Warning: Failed to insert stock record for $kode_generated: " . mysqli_error($koneksi));
            }
        }
        
        // Insert applicable part data
        if ($success) {
            $applicable_inserted = 0;
            $fields = ['hapus1', 'hapus2', 'hapus3', 'hapus4'];
            
            foreach ($fields as $field) {
                if (isset($_POST[$field]) && is_array($_POST[$field])) {
                    foreach ($_POST[$field] as $kode_tipe) {
                        if (!empty($kode_tipe)) {
                            $kode_tipe_escaped = mysqli_real_escape_string($koneksi, $kode_tipe);
                            $applicable_query = "INSERT INTO tblitem_spart (noitem, kode_tipe) 
                                               VALUES ('$kode_generated', '$kode_tipe_escaped')";
                            
                            if (mysqli_query($koneksi, $applicable_query)) {
                                $applicable_inserted++;
                            } else {
                                error_log("Warning: Failed to insert applicable part for $kode_generated, kode_tipe: $kode_tipe_escaped: " . mysqli_error($koneksi));
                            }
                        }
                    }
                }
            }
            
            // Log applicable part insertion
            if ($applicable_inserted > 0) {
                error_log("Info: Inserted $applicable_inserted applicable part records for item $kode_generated");
            }
        }
        
        // Commit transaction
        mysqli_commit($koneksi);
        mysqli_autocommit($koneksi, true);
        
        // Success redirect
        $success_type = ($tipe_item == 'ORI') ? 'ORI' : 'NON-ORI';
        $redirect_url = "barang_add_improved.php?success=1&type=$success_type&code=$kode_generated";
        header("Location: $redirect_url");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($koneksi);
        mysqli_autocommit($koneksi, true);
        
        $error_msg = $e->getMessage();
        $redirect_url = "barang_add_improved.php?error=" . urlencode($error_msg);
        header("Location: $redirect_url");
        exit;
    }
    
} else {
    // Invalid access
    header("Location: barang_add_improved.php?error=" . urlencode("Akses tidak valid!"));
    exit;
}

// Function to validate part number format (can be enhanced)
function validatePartNumber($part_number, $brand) {
    // Basic validation - can be enhanced with brand-specific rules
    $part_number = trim($part_number);
    
    if (empty($part_number)) {
        return false;
    }
    
    // Honda part numbers usually follow specific patterns
    if ($brand == 'HONDA') {
        // Honda parts often have format like: 06455-KVB-900
        return preg_match('/^[0-9A-Z\-]+$/', $part_number);
    }
    
    // Yamaha part numbers
    if ($brand == 'YAMAHA') {
        return preg_match('/^[0-9A-Z\-]+$/', $part_number);
    }
    
    // General validation for other brands
    return preg_match('/^[0-9A-Z\-\.]+$/', $part_number);
}

// Function to get brand website URL
function getBrandWebsite($brand) {
    $websites = [
        'HONDA' => 'https://www.honda.co.jp/parts/',
        'YAMAHA' => 'https://global.yamaha-motor.com/',
        'SUZUKI' => 'https://www.suzuki.co.jp/',
        'KAWASAKI' => 'https://www.kawasaki.com/'
    ];
    
    return $websites[$brand] ?? '#';
}

// Function to log item operations
function logItemOperation($koneksi, $noitem, $operation, $user_id, $details = '') {
    $log_query = "INSERT INTO tbitem_validation_log (noitem, status_baru, keterangan, validated_by) 
                 VALUES ('$noitem', '$operation', '$details', '$user_id')";
    mysqli_query($koneksi, $log_query);
}
?>