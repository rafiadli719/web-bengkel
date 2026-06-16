<?php
/**
 * AJAX Endpoint untuk validasi dan simpan PO dari upload Excel
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include "../../config/koneksi.php";

$action = isset($_POST['action']) ? $_POST['action'] : '';
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';

// Get user name
$user_query = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='$id_user'");
$user_data = mysqli_fetch_array($user_query);
$nama_user = $user_data ? $user_data['nama_user'] : 'System';

try {
    switch ($action) {
        case 'validate':
            // Validate items from Excel
            $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
            $supplier = isset($_POST['supplier']) ? mysqli_real_escape_string($koneksi, $_POST['supplier']) : '';

            if (empty($items)) {
                echo json_encode(['success' => false, 'error' => 'Tidak ada data item']);
                exit;
            }

            $result = [];
            $summary = ['total' => count($items), 'ok' => 0, 'warning' => 0, 'error' => 0];

            foreach ($items as $item) {
                $kode_input = isset($item['kode']) ? (string)$item['kode'] : '';
                $kode_clean = strtoupper(trim($kode_input));
                $kode_clean = preg_replace('/\s+/', '', $kode_clean);
                $kode_clean = preg_replace('/[^\x20-\x7E]/', '', $kode_clean);
                $kode = mysqli_real_escape_string($koneksi, $kode_clean);

                $nama = isset($item['nama']) ? (string)$item['nama'] : '';
                $qty = isset($item['qty']) ? (int)$item['qty'] : 0;
                $harga = isset($item['harga']) ? (float)$item['harga'] : 0;

                // Check if item exists in database
                $check_query = mysqli_query($koneksi, "SELECT noitem, namaitem, hargapokok FROM tblitem WHERE (noitem='$kode' OR kodebarcode='$kode') LIMIT 1");

                if (!$check_query) {
                    $result[] = [
                        'kode' => $kode_clean,
                        'nama' => $nama,
                        'qty' => $qty,
                        'harga' => $harga,
                        'stok' => 0,
                        'status' => 'ERROR',
                        'message' => 'Query item gagal: ' . mysqli_error($koneksi)
                    ];
                    $summary['error']++;
                    continue;
                }

                if ($check_query && mysqli_num_rows($check_query) > 0) {
                    $db_item = mysqli_fetch_assoc($check_query);

                    // Use database price if not provided
                    if ($harga <= 0) {
                        $harga = (float)$db_item['hargapokok'];
                    }

                    $result[] = [
                        'kode' => $db_item['noitem'],
                        'nama' => $db_item['namaitem'],
                        'qty' => $qty,
                        'harga' => $harga,
                        'stok' => 0,
                        'status' => 'OK',
                        'message' => ''
                    ];
                    $summary['ok']++;
                } else {
                    // Item not found - mark as warning (can still be ordered)
                    $result[] = [
                        'kode' => $kode_clean,
                        'nama' => $nama,
                        'qty' => $qty,
                        'harga' => $harga,
                        'stok' => 0,
                        'status' => 'WARNING',
                        'message' => 'Item tidak ditemukan di master, akan dibuat baru'
                    ];
                    $summary['warning']++;
                }

                // Validate qty
                if ($qty <= 0) {
                    $lastIndex = count($result) - 1;
                    $prevStatus = isset($result[$lastIndex]['status']) ? $result[$lastIndex]['status'] : '';
                    $result[$lastIndex]['status'] = 'ERROR';
                    $result[$lastIndex]['message'] = 'Qty harus lebih dari 0';
                    $summary['error']++;
                    if ($prevStatus === 'OK') $summary['ok']--;
                    if ($prevStatus === 'WARNING') $summary['warning']--;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $result,
                'summary' => $summary
            ]);
            break;

        case 'save':
            // Save validated items to PO
            $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
            $supplier = isset($_POST['supplier']) ? mysqli_real_escape_string($koneksi, $_POST['supplier']) : '';
            $tanggal = isset($_POST['tanggal']) ? mysqli_real_escape_string($koneksi, $_POST['tanggal']) : date('Y-m-d');
            $note = isset($_POST['note']) ? mysqli_real_escape_string($koneksi, $_POST['note']) : '';

            if (empty($items)) {
                echo json_encode(['success' => false, 'error' => 'Tidak ada data item']);
                exit;
            }

            if (empty($supplier)) {
                echo json_encode(['success' => false, 'error' => 'Supplier harus dipilih']);
                exit;
            }

            // Generate PO number
            $prefix = "PS" . date('y') . "000000";
            $last_query = mysqli_query($koneksi, "SELECT no_order FROM tblorder_header WHERE no_order LIKE 'PS" . date('y') . "%' ORDER BY no_order DESC LIMIT 1");
            if ($last_query && mysqli_num_rows($last_query) > 0) {
                $last_row = mysqli_fetch_assoc($last_query);
                $last_num = (int)substr($last_row['no_order'], 4);
                $new_num = $last_num + 1;
            } else {
                $new_num = 1;
            }
            $no_order = "PS" . date('y') . str_pad($new_num, 9, '0', STR_PAD_LEFT);

            // Start transaction
            mysqli_begin_transaction($koneksi);

            try {
                // Calculate totals
                $total_qty = 0;
                $total_order = 0;

                foreach ($items as $item) {
                    $status_item = isset($item['status']) ? $item['status'] : '';
                    if ($status_item !== 'ERROR') {
                        $total_qty += (int)$item['qty'];
                        $total_order += (int)$item['qty'] * (float)$item['harga'];
                    }
                }

                // Insert header
                $sql_header = "INSERT INTO tblorder_header
                    (no_order, status, tanggal, tglkirim, no_supplier, note, total_qty, total_terima, total_order, user, Id_tabel, kd_cabang, status_pesanan, tipe_trx, no_penjualan, status_approval, po_type, payment_term)
                    VALUES
                    ('$no_order', '0', '$tanggal', '', '$supplier', '$note', '$total_qty', '0', '$total_order', '$nama_user', '', '$kd_cabang', '0', 'UPLOAD', '', 'approved', 'regular', '')";

                if (!mysqli_query($koneksi, $sql_header)) {
                    throw new Exception('Gagal menyimpan header: ' . mysqli_error($koneksi));
                }

                // Insert details
                $nobaris = 0;
                foreach ($items as $item) {
                    $status_item = isset($item['status']) ? $item['status'] : '';
                    if ($status_item === 'ERROR') continue;

                    $kode = mysqli_real_escape_string($koneksi, $item['kode']);
                    $qty = (int)$item['qty'];
                    $harga = (float)$item['harga'];

                    if ($harga <= 0) {
                        $qHarga = mysqli_query($koneksi, "SELECT hargapokok FROM tblitem WHERE (noitem='$kode' OR kodebarcode='$kode') LIMIT 1");
                        if ($qHarga && mysqli_num_rows($qHarga) > 0) {
                            $rowHarga = mysqli_fetch_assoc($qHarga);
                            $harga = (float)$rowHarga['hargapokok'];
                        }
                    }
                    $subtotal = $qty * $harga;
                    $nobaris++;

                    // Check if item exists, if not and it's WARNING status, we'll just skip creating new item
                    // The item will be created when actually received

                    $sql_detail = "INSERT INTO tblorder_detail
                        (no_order, nobaris, no_item, quantity, qty_terima, harga_pokok, total, user, kd_cabang, status_trx)
                        VALUES
                        ('$no_order', '$nobaris', '$kode', '$qty', '0', '$harga', '$subtotal', '$nama_user', '$kd_cabang', '1')";

                    if (!mysqli_query($koneksi, $sql_detail)) {
                        throw new Exception('Gagal menyimpan detail: ' . mysqli_error($koneksi));
                    }
                }

                mysqli_commit($koneksi);

                echo json_encode([
                    'success' => true,
                    'message' => 'Pesanan Pembelian berhasil dibuat',
                    'no_order' => $no_order,
                    'total_item' => count($items),
                    'total_qty' => $total_qty,
                    'total_order' => $total_order
                ]);

            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
