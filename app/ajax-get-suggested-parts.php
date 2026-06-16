<?php
/**
 * AJAX Handler: Get Suggested Parts berdasarkan Temuan
 * Digunakan untuk auto-suggest part saat input temuan dengan jenis perbaikan = penggantian_part
 */

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

include "../config/koneksi.php";

// Get parameters
$kode_temuan = isset($_POST['kode_temuan']) ? mysqli_real_escape_string($koneksi, $_POST['kode_temuan']) : '';
$temuan_id = isset($_POST['temuan_id']) ? intval($_POST['temuan_id']) : 0;

// Jika ada temuan_id, get kode_temuan dari database
if($temuan_id > 0) {
    $query_temuan = "SELECT kode_temuan FROM tbservis_temuan WHERE id = '$temuan_id'";
    $result_temuan = mysqli_query($koneksi, $query_temuan);

    if($result_temuan && mysqli_num_rows($result_temuan) > 0) {
        $data_temuan = mysqli_fetch_array($result_temuan);
        $kode_temuan = $data_temuan['kode_temuan'];
    }
}

// Validasi kode_temuan
if(empty($kode_temuan)) {
    echo json_encode([
        'success' => false,
        'message' => 'Kode temuan tidak ditemukan'
    ]);
    exit;
}

// Query suggested parts menggunakan view jika ada; fallback ke tabel mapping jika view kosong/tidak ada
// View menggunakan alias agar kompatibel dengan field lama
$query_suggested = "SELECT
                        kode_barang,
                        nama_barang,
                        harga_jual,
                        stok,
                        kategori,
                        is_primary,
                        prioritas,
                        qty_default,
                        keterangan,
                        status_stok
                    FROM view_suggested_parts
                    WHERE kode_temuan = '$kode_temuan'
                    ORDER BY is_primary DESC, prioritas ASC";

$result_suggested = mysqli_query($koneksi, $query_suggested);

// Jika view tidak ada atau error, atau tidak mengembalikan baris, gunakan fallback ke mapping
$use_fallback = false;
if(!$result_suggested) {
    $use_fallback = true;
} else if (mysqli_num_rows($result_suggested) === 0) {
    $use_fallback = true;
}

if ($use_fallback) {
    $fallback_query = "SELECT
                            m.noitem AS kode_barang,
                            COALESCE(i.namaitem, m.noitem) AS nama_barang,
                            COALESCE(i.hargajual, 0) AS harga_jual,
                            COALESCE(i.quantity, 0) AS stok,
                            i.jenis AS kategori,
                            m.is_primary AS is_primary,
                            m.prioritas AS prioritas,
                            m.qty_default AS qty_default,
                            m.keterangan AS keterangan,
                            CASE
                                WHEN COALESCE(i.quantity,0) > 5 THEN 'ready_stock'
                                WHEN COALESCE(i.quantity,0) > 0 THEN 'stok_terbatas'
                                ELSE 'indent'
                            END AS status_stok
                        FROM tbmaster_temuan_barang_mapping m
                        LEFT JOIN tblitem i ON i.noitem = m.noitem
                        WHERE m.kode_temuan = '$kode_temuan' AND m.status_aktif = 1
                        ORDER BY m.is_primary DESC, m.prioritas ASC";
    $result_suggested = mysqli_query($koneksi, $fallback_query);
    if(!$result_suggested) {
        echo json_encode([
            'success' => false,
            'message' => 'Error query (fallback): ' . mysqli_error($koneksi)
        ]);
        exit;
    }
}

// Collect results
$parts = array();
while($row = mysqli_fetch_assoc($result_suggested)) {
    // Format harga untuk display
    $row['harga_jual_formatted'] = number_format($row['harga_jual'], 0, ',', '.');

    // Calculate default total
    $default_total = $row['harga_jual'] * $row['qty_default'];
    $row['default_total'] = $default_total;
    $row['default_total_formatted'] = number_format($default_total, 0, ',', '.');

    // Status stok dengan label
    $row['status_stok_label'] = match($row['status_stok']) {
        'ready_stock' => 'Ready Stock',
        'stok_terbatas' => 'Stok Terbatas',
        'indent' => 'Indent',
        default => 'Unknown'
    };

    // Color untuk status stok
    $row['status_stok_color'] = match($row['status_stok']) {
        'ready_stock' => 'success',
        'stok_terbatas' => 'warning',
        'indent' => 'danger',
        default => 'default'
    };

    $parts[] = $row;
}

// Response
echo json_encode([
    'success' => true,
    'kode_temuan' => $kode_temuan,
    'total_parts' => count($parts),
    'data' => $parts
]);
