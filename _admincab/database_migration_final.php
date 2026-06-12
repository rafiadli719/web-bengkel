<?php
/**
 * Database Migration Script - Final Cleanup
 * Jalankan script ini SATU KALI untuk menyesuaikan database dengan perubahan terbaru
 * 
 * Perubahan yang dilakukan:
 * 1. Update ENUM tingkat_urgensi di tbservis_temuan - hapus 'kritis'
 * 2. Pastikan kolom kode_keluhan dan kategori ada di tbservis_keluhan_status
 * 3. Verifikasi struktur table
 */

// Include koneksi database
$config_path = __DIR__ . '/../config/koneksi.php';
if (!file_exists($config_path)) {
    die("Error: File koneksi.php tidak ditemukan di: $config_path");
}
require_once $config_path;

// Koneksi ke database
if (!isset($koneksi)) {
    die("Error: Variable \$koneksi tidak terdefinisi dalam koneksi.php");
}

echo "=== DATABASE MIGRATION - FINAL CLEANUP ===\n\n";

// Array untuk menyimpan hasil
$results = [];
$errors = [];

// ============================================================
// 1. UPDATE ENUM tingkat_urgensi di tbservis_temuan
// ============================================================
echo "[1/3] Mengupdate ENUM tingkat_urgensi di tbservis_temuan...\n";

try {
    // Cek struktur tabel saat ini
    $check_query = "SHOW COLUMNS FROM tbservis_temuan LIKE 'tingkat_urgensi'";
    $result = mysqli_query($koneksi, $check_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $current_type = $row['Type'];
        
        echo "   - Tipe saat ini: $current_type\n";
        
        // Cek apakah masih ada 'kritis' di ENUM
        if (strpos($current_type, 'kritis') !== false) {
            echo "   - Menemukan 'kritis' dalam ENUM, akan dihapus...\n";
            
            // Update data yang ber-nilai 'kritis' menjadi 'tinggi'
            $update_data = "UPDATE tbservis_temuan SET tingkat_urgensi = 'tinggi' WHERE tingkat_urgensi = 'kritis'";
            if (mysqli_query($koneksi, $update_data)) {
                $affected = mysqli_affected_rows($koneksi);
                echo "   - Updated $affected rows dari 'kritis' → 'tinggi'\n";
            }
            
            // Alter table untuk menghapus 'kritis' dari ENUM
            $alter_query = "ALTER TABLE tbservis_temuan 
                           MODIFY COLUMN tingkat_urgensi ENUM('rendah','sedang','tinggi') DEFAULT 'sedang'";
            
            if (mysqli_query($koneksi, $alter_query)) {
                echo "   ✅ ENUM tingkat_urgensi berhasil diupdate (hapus 'kritis')\n";
                $results[] = "ENUM tingkat_urgensi updated";
            } else {
                throw new Exception(mysqli_error($koneksi));
            }
        } else {
            echo "   ✅ ENUM tingkat_urgensi sudah benar (tidak ada 'kritis')\n";
            $results[] = "ENUM tingkat_urgensi sudah OK";
        }
    } else {
        throw new Exception("Column tingkat_urgensi tidak ditemukan!");
    }
} catch (Exception $e) {
    $error_msg = "Error update ENUM tingkat_urgensi: " . $e->getMessage();
    echo "   ❌ $error_msg\n";
    $errors[] = $error_msg;
}

echo "\n";

// ============================================================
// 1b. UPDATE ENUM tingkat_urgensi di tbmaster_temuan
// ============================================================
echo "[1b] Mengupdate ENUM tingkat_urgensi di tbmaster_temuan...\n";

try {
    $check_query_master = "SHOW COLUMNS FROM tbmaster_temuan LIKE 'tingkat_urgensi'";
    $result_master = mysqli_query($koneksi, $check_query_master);
    
    if ($result_master && mysqli_num_rows($result_master) > 0) {
        $row_master = mysqli_fetch_assoc($result_master);
        $current_type_master = $row_master['Type'];
        
        echo "   - Tipe saat ini (master): $current_type_master\n";
        
        if (strpos($current_type_master, 'kritis') !== false) {
            echo "   - Menemukan 'kritis' dalam ENUM master, akan dihapus...\n";
            
            // Update data lama ke 'tinggi'
            $update_data_master = "UPDATE tbmaster_temuan SET tingkat_urgensi = 'tinggi' WHERE tingkat_urgensi = 'kritis'";
            if (mysqli_query($koneksi, $update_data_master)) {
                $affected_master = mysqli_affected_rows($koneksi);
                echo "   - Updated $affected_master rows (master) dari 'kritis' → 'tinggi'\n";
            }
            
            // Alter ENUM master
            $alter_query_master = "ALTER TABLE tbmaster_temuan 
                                   MODIFY COLUMN tingkat_urgensi ENUM('rendah','sedang','tinggi') DEFAULT 'sedang'";
            if (mysqli_query($koneksi, $alter_query_master)) {
                echo "   ✅ ENUM tingkat_urgensi (master) berhasil diupdate (hapus 'kritis')\n";
                $results[] = "ENUM tingkat_urgensi (master) updated";
            } else {
                throw new Exception(mysqli_error($koneksi));
            }
        } else {
            echo "   ✅ ENUM master sudah benar (tidak ada 'kritis')\n";
            $results[] = "ENUM tingkat_urgensi master sudah OK";
        }
    } else {
        throw new Exception("Column tingkat_urgensi tidak ditemukan di tbmaster_temuan!");
    }
} catch (Exception $e) {
    $error_msg = "Error update ENUM master: " . $e->getMessage();
    echo "   ❌ $error_msg\n";
    $errors[] = $error_msg;
}

echo "\n";

// ============================================================
// 2. PASTIKAN kolom kode_keluhan dan kategori ada di tbservis_keluhan_status
// ============================================================
echo "[2/3] Verifikasi kolom kode_keluhan dan kategori di tbservis_keluhan_status...\n";

try {
    // Cek apakah kolom kode_keluhan ada
    $check_kode = "SHOW COLUMNS FROM tbservis_keluhan_status LIKE 'kode_keluhan'";
    $result_kode = mysqli_query($koneksi, $check_kode);
    
    if (!$result_kode || mysqli_num_rows($result_kode) == 0) {
        echo "   - Menambahkan kolom kode_keluhan...\n";
        $add_kode = "ALTER TABLE tbservis_keluhan_status 
                     ADD COLUMN kode_keluhan VARCHAR(10) NULL COMMENT 'Link ke tbmaster_keluhan' AFTER keluhan,
                     ADD INDEX idx_kode_keluhan (kode_keluhan)";
        
        if (mysqli_query($koneksi, $add_kode)) {
            echo "   ✅ Kolom kode_keluhan berhasil ditambahkan\n";
            $results[] = "Kolom kode_keluhan ditambahkan";
        } else {
            throw new Exception(mysqli_error($koneksi));
        }
    } else {
        echo "   ✅ Kolom kode_keluhan sudah ada\n";
        $results[] = "Kolom kode_keluhan sudah OK";
    }
    
    // Cek apakah kolom kategori ada
    $check_kategori = "SHOW COLUMNS FROM tbservis_keluhan_status LIKE 'kategori'";
    $result_kategori = mysqli_query($koneksi, $check_kategori);
    
    if (!$result_kategori || mysqli_num_rows($result_kategori) == 0) {
        echo "   - Menambahkan kolom kategori...\n";
        $add_kategori = "ALTER TABLE tbservis_keluhan_status 
                        ADD COLUMN kategori VARCHAR(50) NULL COMMENT 'Kategori keluhan dari master' AFTER kode_keluhan";
        
        if (mysqli_query($koneksi, $add_kategori)) {
            echo "   ✅ Kolom kategori berhasil ditambahkan\n";
            $results[] = "Kolom kategori ditambahkan";
        } else {
            throw new Exception(mysqli_error($koneksi));
        }
    } else {
        echo "   ✅ Kolom kategori sudah ada\n";
        $results[] = "Kolom kategori sudah OK";
    }
    
} catch (Exception $e) {
    $error_msg = "Error verifikasi kolom keluhan: " . $e->getMessage();
    echo "   ❌ $error_msg\n";
    $errors[] = $error_msg;
}

echo "\n";

// ============================================================
// 3. VERIFIKASI struktur final
// ============================================================
echo "[3/3] Verifikasi struktur database final...\n";

try {
    // Cek tbservis_temuan
    echo "   Struktur tbservis_temuan:\n";
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tbservis_temuan");
    $columns = [];
    while ($row = mysqli_fetch_assoc($check)) {
        $columns[] = $row['Field'];
    }
    echo "   - Total kolom: " . count($columns) . "\n";
    echo "   - Kolom penting: " . implode(', ', array_intersect($columns, ['id', 'no_service', 'keluhan_id', 'kode_temuan', 'tingkat_urgensi', 'jenis_perbaikan'])) . "\n";
    
    // Cek tbservis_keluhan_status
    echo "\n   Struktur tbservis_keluhan_status:\n";
    $check2 = mysqli_query($koneksi, "SHOW COLUMNS FROM tbservis_keluhan_status");
    $columns2 = [];
    while ($row = mysqli_fetch_assoc($check2)) {
        $columns2[] = $row['Field'];
    }
    echo "   - Total kolom: " . count($columns2) . "\n";
    echo "   - Kolom penting: " . implode(', ', array_intersect($columns2, ['id', 'no_service', 'keluhan', 'kode_keluhan', 'kategori', 'status_pengerjaan'])) . "\n";
    
    $results[] = "Verifikasi struktur selesai";
    
} catch (Exception $e) {
    $error_msg = "Error verifikasi: " . $e->getMessage();
    echo "   ❌ $error_msg\n";
    $errors[] = $error_msg;
}

// ============================================================
// SUMMARY
// ============================================================
echo "\n";
echo "===========================================\n";
echo "MIGRATION SUMMARY\n";
echo "===========================================\n";
echo "Sukses: " . count($results) . " operasi\n";
foreach ($results as $r) {
    echo "  ✅ $r\n";
}

if (count($errors) > 0) {
    echo "\nError: " . count($errors) . " operasi gagal\n";
    foreach ($errors as $e) {
        echo "  ❌ $e\n";
    }
} else {
    echo "\n✅ MIGRATION SELESAI TANPA ERROR!\n";
}

echo "\n";
echo "===========================================\n";
echo "CATATAN PENTING:\n";
echo "===========================================\n";
echo "1. Field 'estimasi_biaya' di tbservis_temuan TIDAK DIHAPUS\n";
echo "   (untuk backward compatibility, hanya tidak dipakai di UI)\n";
echo "\n";
echo "2. Tingkat Urgensi sekarang hanya 3: rendah, sedang, tinggi\n";
echo "   (option 'kritis' sudah dihapus dari ENUM dan UI)\n";
echo "\n";
echo "3. Keluhan sekarang ter-link ke master via kode_keluhan\n";
echo "   (dengan kategori otomatis dari master)\n";
echo "\n";

// Close connection
mysqli_close($koneksi);
?>
