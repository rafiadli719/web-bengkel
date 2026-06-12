<?php
/**
 * Handler untuk Custom Items (Barang Custom)
 * CRUD operations untuk master barang custom
 */

// Session start first
session_start();

// Include koneksi
if(!isset($koneksi)) {
    // Try different paths
    if(file_exists('../config/koneksi.php')) {
        require_once('../config/koneksi.php');
    } elseif(file_exists('config/koneksi.php')) {
        require_once('config/koneksi.php');
    } elseif(file_exists(__DIR__ . '/../config/koneksi.php')) {
        require_once(__DIR__ . '/../config/koneksi.php');
    } else {
        die(json_encode(['success' => false, 'message' => 'Database connection file not found']));
    }
}

// ==========================================
// ADD CUSTOM ITEM
// ==========================================
if(isset($_POST['btnaddcustom'])) {
    $nama_barang = mysqli_real_escape_string($koneksi, trim($_POST['nama_barang']));
    $harga_jual = mysqli_real_escape_string($koneksi, $_POST['harga_jual']);
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'])) : '';
    $created_by = $_SESSION['_nama'] ?? 'System';
    
    // Validate
    if(empty($nama_barang) || empty($harga_jual) || empty($satuan)) {
        echo "<script>alert('Nama barang, harga, dan satuan wajib diisi!'); window.history.back();</script>";
        exit;
    }
    
    // Auto-generate kode_barang (CUSTOM-XXXXX)
    $query_last = mysqli_query($koneksi, "SELECT kode_barang FROM tbmaster_barang_custom ORDER BY id DESC LIMIT 1");
    
    if(mysqli_num_rows($query_last) > 0) {
        $last = mysqli_fetch_assoc($query_last);
        $last_number = intval(substr($last['kode_barang'], 7)); // Get number after "CUSTOM-"
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    $kode_barang = 'CUSTOM-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
    
    // Insert
    $query = "INSERT INTO tbmaster_barang_custom 
              (kode_barang, nama_barang, harga_jual, satuan, kategori, deskripsi, created_by)
              VALUES 
              ('$kode_barang', '$nama_barang', '$harga_jual', '$satuan', '$kategori', '$deskripsi', '$created_by')";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<script>alert('Barang custom berhasil ditambahkan!\\nKode: $kode_barang'); window.location.href='master-barang-custom.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
    exit;
}

// ==========================================
// EDIT CUSTOM ITEM
// ==========================================
if(isset($_POST['btneditcustom'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    $nama_barang = mysqli_real_escape_string($koneksi, trim($_POST['nama_barang']));
    $harga_jual = mysqli_real_escape_string($koneksi, $_POST['harga_jual']);
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'])) : '';
    
    // Validate
    if(empty($nama_barang) || empty($harga_jual) || empty($satuan)) {
        echo "<script>alert('Nama barang, harga, dan satuan wajib diisi!'); window.history.back();</script>";
        exit;
    }
    
    // Update
    $query = "UPDATE tbmaster_barang_custom 
              SET nama_barang='$nama_barang', 
                  harga_jual='$harga_jual', 
                  satuan='$satuan', 
                  kategori='$kategori', 
                  deskripsi='$deskripsi'
              WHERE id='$id'";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<script>alert('Barang custom berhasil diupdate!'); window.location.href='master-barang-custom.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
    exit;
}

// ==========================================
// DELETE CUSTOM ITEM
// ==========================================
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Check if used in any penawaran
    $check = mysqli_query($koneksi, "
        SELECT COUNT(*) as cnt 
        FROM tbservis_penawaran_part 
        WHERE kode_barang IN (SELECT kode_barang FROM tbmaster_barang_custom WHERE id='$id')
    ");
    $row = mysqli_fetch_assoc($check);
    
    if($row['cnt'] > 0) {
        echo "<script>alert('Tidak bisa hapus! Barang ini sudah digunakan di " . $row['cnt'] . " penawaran.\\nSilakan nonaktifkan saja.'); window.location.href='master-barang-custom.php';</script>";
        exit;
    }
    
    // Delete
    if(mysqli_query($koneksi, "DELETE FROM tbmaster_barang_custom WHERE id='$id'")) {
        echo "<script>alert('Barang custom berhasil dihapus!'); window.location.href='master-barang-custom.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($koneksi) . "'); window.location.href='master-barang-custom.php';</script>";
    }
    exit;
}

// ==========================================
// TOGGLE STATUS
// ==========================================
if(isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Get current status
    $query = mysqli_query($koneksi, "SELECT status_aktif FROM tbmaster_barang_custom WHERE id='$id'");
    $row = mysqli_fetch_assoc($query);
    
    $new_status = ($row['status_aktif'] == '1') ? '0' : '1';
    
    if(mysqli_query($koneksi, "UPDATE tbmaster_barang_custom SET status_aktif='$new_status' WHERE id='$id'")) {
        $msg = ($new_status == '1') ? 'diaktifkan' : 'dinonaktifkan';
        echo "<script>alert('Barang custom berhasil $msg!'); window.location.href='master-barang-custom.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($koneksi) . "'); window.location.href='master-barang-custom.php';</script>";
    }
    exit;
}

// ==========================================
// AJAX: Add custom item from service input
// ==========================================
if(isset($_POST['action']) && $_POST['action'] == 'quick_add_custom') {
    header('Content-Type: application/json');
    
    try {
        $nama_barang = mysqli_real_escape_string($koneksi, trim($_POST['nama_barang']));
        $harga_jual = mysqli_real_escape_string($koneksi, $_POST['harga_jual']);
        $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
        $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
        $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'])) : '';
        $created_by = $_SESSION['_nama'] ?? 'System';
        
        // Validate
        if(empty($nama_barang) || empty($harga_jual) || empty($satuan)) {
            throw new Exception('Nama barang, harga, dan satuan wajib diisi!');
        }
        
        // Auto-generate kode
        $query_last = mysqli_query($koneksi, "SELECT kode_barang FROM tbmaster_barang_custom ORDER BY id DESC LIMIT 1");
        
        if(mysqli_num_rows($query_last) > 0) {
            $last = mysqli_fetch_assoc($query_last);
            $last_number = intval(substr($last['kode_barang'], 7));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        $kode_barang = 'CUSTOM-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
        
        // Insert
        $query = "INSERT INTO tbmaster_barang_custom 
                  (kode_barang, nama_barang, harga_jual, satuan, kategori, deskripsi, created_by)
                  VALUES 
                  ('$kode_barang', '$nama_barang', '$harga_jual', '$satuan', '$kategori', '$deskripsi', '$created_by')";
        
        if(!mysqli_query($koneksi, $query)) {
            throw new Exception(mysqli_error($koneksi));
        }
        
        // Return success with item data
        echo json_encode([
            'success' => true,
            'data' => [
                'kode_barang' => $kode_barang,
                'nama_barang' => $nama_barang,
                'harga_jual' => $harga_jual,
                'satuan' => $satuan,
                'kategori' => $kategori
            ],
            'message' => 'Barang custom berhasil ditambahkan!'
        ]);
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// ==========================================
// AJAX: List recent custom items
// ==========================================
if(isset($_GET['action']) && $_GET['action'] == 'list_custom_items') {
    header('Content-Type: application/json');
    
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        if($limit > 100) $limit = 100;
        
        $query = mysqli_query($koneksi, "
            SELECT id, kode_barang, nama_barang, harga_jual, satuan, kategori, deskripsi, status_aktif, created_at 
            FROM tbmaster_barang_custom 
            WHERE status_aktif = '1'
            ORDER BY id DESC 
            LIMIT $limit
        ");
        
        $items = [];
        while($row = mysqli_fetch_assoc($query)) {
            $items[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $items,
            'count' => count($items)
        ]);
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// ==========================================
// AJAX: Search custom items by keyword
// ==========================================
if(isset($_GET['action']) && $_GET['action'] == 'search_custom_items') {
    header('Content-Type: application/json');
    
    try {
        $keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, trim($_GET['keyword'])) : '';
        
        if(empty($keyword)) {
            echo json_encode([
                'success' => false,
                'message' => 'Keyword tidak boleh kosong'
            ]);
            exit;
        }
        
        $query = mysqli_query($koneksi, "
            SELECT id, kode_barang, nama_barang, harga_jual, satuan, kategori, deskripsi, status_aktif, created_at 
            FROM tbmaster_barang_custom 
            WHERE status_aktif = '1'
              AND (kode_barang LIKE '%$keyword%' 
                   OR nama_barang LIKE '%$keyword%' 
                   OR kategori LIKE '%$keyword%'
                   OR deskripsi LIKE '%$keyword%')
            ORDER BY 
                CASE 
                    WHEN nama_barang LIKE '$keyword%' THEN 1
                    WHEN nama_barang LIKE '%$keyword%' THEN 2
                    ELSE 3
                END,
                nama_barang ASC
            LIMIT 50
        ");
        
        $items = [];
        while($row = mysqli_fetch_assoc($query)) {
            $items[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $items,
            'count' => count($items),
            'keyword' => $keyword
        ]);
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>
