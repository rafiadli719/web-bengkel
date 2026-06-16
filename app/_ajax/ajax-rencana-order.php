<?php
/**
 * AJAX Endpoint untuk Rencana Order
 * Menangani operasi CRUD dan aksi pada rencana order
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include "../../config/koneksi.php";

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : null;

// Get user name
$user_query = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='$id_user'");
$user_data = mysqli_fetch_array($user_query);
$nama_user = $user_data ? $user_data['nama_user'] : 'System';

try {
    switch ($action) {
        case 'approve':
            // Approve rencana order
            $no_rencana = isset($_POST['no_rencana']) ? mysqli_real_escape_string($koneksi, $_POST['no_rencana']) : '';

            if (empty($no_rencana)) {
                echo json_encode(['success' => false, 'error' => 'No rencana tidak boleh kosong']);
                exit;
            }

            // Check current status
            $check = mysqli_query($koneksi, "SELECT status FROM tblrencana_order_header WHERE no_rencana='$no_rencana'");
            if (!$check || mysqli_num_rows($check) == 0) {
                echo json_encode(['success' => false, 'error' => 'Rencana order tidak ditemukan']);
                exit;
            }

            $row = mysqli_fetch_assoc($check);
            if ($row['status'] != 'draft') {
                echo json_encode(['success' => false, 'error' => 'Hanya rencana dengan status draft yang dapat diapprove']);
                exit;
            }

            // Update status
            $sql = "UPDATE tblrencana_order_header
                    SET status='approved', approved_by='$nama_user', approved_at=NOW()
                    WHERE no_rencana='$no_rencana'";

            if (mysqli_query($koneksi, $sql)) {
                echo json_encode(['success' => true, 'message' => 'Rencana order berhasil diapprove']);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($koneksi)]);
            }
            break;

        case 'reject':
            // Reject/Cancel rencana order
            $no_rencana = isset($_POST['no_rencana']) ? mysqli_real_escape_string($koneksi, $_POST['no_rencana']) : '';
            $reason = isset($_POST['reason']) ? mysqli_real_escape_string($koneksi, $_POST['reason']) : '';

            if (empty($no_rencana)) {
                echo json_encode(['success' => false, 'error' => 'No rencana tidak boleh kosong']);
                exit;
            }

            // Update status back to draft with note
            $sql = "UPDATE tblrencana_order_header
                    SET status='draft', note=CONCAT(IFNULL(note,''), '\nRejected by $nama_user: $reason')
                    WHERE no_rencana='$no_rencana'";

            if (mysqli_query($koneksi, $sql)) {
                echo json_encode(['success' => true, 'message' => 'Rencana order ditolak']);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($koneksi)]);
            }
            break;

        case 'delete':
            // Delete rencana order (only draft)
            $no_rencana = isset($_POST['no_rencana']) ? mysqli_real_escape_string($koneksi, $_POST['no_rencana']) : '';

            if (empty($no_rencana)) {
                echo json_encode(['success' => false, 'error' => 'No rencana tidak boleh kosong']);
                exit;
            }

            // Check status
            $check = mysqli_query($koneksi, "SELECT status FROM tblrencana_order_header WHERE no_rencana='$no_rencana'");
            if (!$check || mysqli_num_rows($check) == 0) {
                echo json_encode(['success' => false, 'error' => 'Rencana order tidak ditemukan']);
                exit;
            }

            $row = mysqli_fetch_assoc($check);
            if ($row['status'] != 'draft') {
                echo json_encode(['success' => false, 'error' => 'Hanya rencana dengan status draft yang dapat dihapus']);
                exit;
            }

            // Start transaction
            mysqli_begin_transaction($koneksi);

            try {
                // Delete detail cabang
                mysqli_query($koneksi, "DELETE FROM tblrencana_order_detail_cabang WHERE no_rencana='$no_rencana'");

                // Delete detail
                mysqli_query($koneksi, "DELETE FROM tblrencana_order_detail WHERE no_rencana='$no_rencana'");

                // Delete header
                mysqli_query($koneksi, "DELETE FROM tblrencana_order_header WHERE no_rencana='$no_rencana'");

                mysqli_commit($koneksi);
                echo json_encode(['success' => true, 'message' => 'Rencana order berhasil dihapus']);
            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'update_detail':
            // Update detail item
            $no_rencana = isset($_POST['no_rencana']) ? mysqli_real_escape_string($koneksi, $_POST['no_rencana']) : '';
            $no_item = isset($_POST['no_item']) ? mysqli_real_escape_string($koneksi, $_POST['no_item']) : '';
            $field = isset($_POST['field']) ? mysqli_real_escape_string($koneksi, $_POST['field']) : '';
            $value = isset($_POST['value']) ? mysqli_real_escape_string($koneksi, $_POST['value']) : '';

            if (empty($no_rencana) || empty($no_item) || empty($field)) {
                echo json_encode(['success' => false, 'error' => 'Parameter tidak lengkap']);
                exit;
            }

            // Validate field name
            $allowed_fields = ['order1_qty_edit', 'order1_supplier_edit', 'order2_qty_edit', 'order2_supplier_edit', 'keterangan'];
            if (!in_array($field, $allowed_fields)) {
                echo json_encode(['success' => false, 'error' => 'Field tidak valid']);
                exit;
            }

            $sql = "UPDATE tblrencana_order_detail SET $field='$value' WHERE no_rencana='$no_rencana' AND no_item='$no_item'";

            if (mysqli_query($koneksi, $sql)) {
                // If qty changed, update final and nilai
                if (strpos($field, 'qty') !== false) {
                    $order_type = strpos($field, 'order1') !== false ? '1' : '2';
                    $harga_query = mysqli_query($koneksi, "SELECT harga, order{$order_type}_qty, order{$order_type}_qty_edit FROM tblrencana_order_detail WHERE no_rencana='$no_rencana' AND no_item='$no_item'");
                    $harga_row = mysqli_fetch_assoc($harga_query);

                    $qty_final = $harga_row["order{$order_type}_qty_edit"] !== null ? $harga_row["order{$order_type}_qty_edit"] : $harga_row["order{$order_type}_qty"];
                    $nilai = $qty_final * $harga_row['harga'];

                    mysqli_query($koneksi, "UPDATE tblrencana_order_detail SET order{$order_type}_qty_final='$qty_final', order{$order_type}_nilai='$nilai' WHERE no_rencana='$no_rencana' AND no_item='$no_item'");
                }

                // If supplier changed, update final
                if (strpos($field, 'supplier') !== false) {
                    $order_type = strpos($field, 'order1') !== false ? '1' : '2';
                    mysqli_query($koneksi, "UPDATE tblrencana_order_detail SET order{$order_type}_supplier_final='$value' WHERE no_rencana='$no_rencana' AND no_item='$no_item'");
                }

                // Recalculate header totals
                $totals_query = mysqli_query($koneksi, "SELECT COUNT(*) as total_item, SUM(order1_qty_final + order2_qty_final) as total_qty, SUM(order1_nilai + order2_nilai) as total_nilai FROM tblrencana_order_detail WHERE no_rencana='$no_rencana'");
                $totals = mysqli_fetch_assoc($totals_query);

                mysqli_query($koneksi, "UPDATE tblrencana_order_header SET total_item='{$totals['total_item']}', total_qty='{$totals['total_qty']}', total_nilai='{$totals['total_nilai']}' WHERE no_rencana='$no_rencana'");

                echo json_encode(['success' => true, 'message' => 'Data berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($koneksi)]);
            }
            break;

        case 'get_detail':
            // Get detail for editing
            $no_rencana = isset($_GET['no_rencana']) ? mysqli_real_escape_string($koneksi, $_GET['no_rencana']) : '';

            if (empty($no_rencana)) {
                echo json_encode(['success' => false, 'error' => 'No rencana tidak boleh kosong']);
                exit;
            }

            // Get header
            $header_query = mysqli_query($koneksi, "SELECT * FROM tblrencana_order_header WHERE no_rencana='$no_rencana'");
            $header = mysqli_fetch_assoc($header_query);

            // Get details
            $detail_query = mysqli_query($koneksi, "SELECT d.*, COALESCE(i.namaitem, d.nama_item) as nama_item_display FROM tblrencana_order_detail d LEFT JOIN tblitem i ON d.no_item = (i.noitem COLLATE utf8mb4_general_ci) WHERE d.no_rencana='$no_rencana' ORDER BY d.no_item");
            $details = [];
            while ($row = mysqli_fetch_assoc($detail_query)) {
                $details[] = $row;
            }

            echo json_encode([
                'success' => true,
                'header' => $header,
                'details' => $details
            ]);
            break;

        case 'create_po':
            // Create PO from rencana order
            $no_rencana = isset($_POST['no_rencana']) ? mysqli_real_escape_string($koneksi, $_POST['no_rencana']) : '';
            $supplier = isset($_POST['supplier']) ? mysqli_real_escape_string($koneksi, $_POST['supplier']) : '';
            $order_type = isset($_POST['order_type']) ? (int)$_POST['order_type'] : 1;

            if (empty($no_rencana) || empty($supplier)) {
                echo json_encode(['success' => false, 'error' => 'Parameter tidak lengkap']);
                exit;
            }

            // Check if approved
            $check = mysqli_query($koneksi, "SELECT status FROM tblrencana_order_header WHERE no_rencana='$no_rencana'");
            $row = mysqli_fetch_assoc($check);
            if ($row['status'] != 'approved') {
                echo json_encode(['success' => false, 'error' => 'Rencana order harus diapprove terlebih dahulu']);
                exit;
            }

            // Get items for this supplier
            $order_field = $order_type == 1 ? 'order1' : 'order2';
            $items_query = mysqli_query($koneksi, "SELECT * FROM tblrencana_order_detail WHERE no_rencana='$no_rencana' AND {$order_field}_supplier_final='$supplier' AND {$order_field}_qty_final > 0");

            if (mysqli_num_rows($items_query) == 0) {
                echo json_encode(['success' => false, 'error' => 'Tidak ada item untuk supplier ini']);
                exit;
            }

            // Generate PO number
            $prefix_po = "PO" . date('Ymd');
            $last_po = mysqli_query($koneksi, "SELECT no_order FROM tbpembelian_header WHERE no_order LIKE '$prefix_po%' ORDER BY no_order DESC LIMIT 1");
            if (mysqli_num_rows($last_po) > 0) {
                $row_po = mysqli_fetch_array($last_po);
                $last_num = (int)substr($row_po['no_order'], -4);
                $new_num = $last_num + 1;
            } else {
                $new_num = 1;
            }
            $no_po = $prefix_po . str_pad($new_num, 4, '0', STR_PAD_LEFT);

            // Start transaction
            mysqli_begin_transaction($koneksi);

            try {
                // Calculate totals
                $total_item = 0;
                $total_qty = 0;
                $total_nilai = 0;

                $items = [];
                while ($item = mysqli_fetch_assoc($items_query)) {
                    $items[] = $item;
                    $total_item++;
                    $total_qty += $item["{$order_field}_qty_final"];
                    $total_nilai += $item["{$order_field}_nilai"];
                }

                // Insert PO header (adapt to existing table structure)
                $sql_header = "INSERT INTO tbpembelian_header
                    (no_order, tanggal, kd_supplier, kd_cabang, total_item, total_qty, total_nilai, status, keterangan, dibuat_oleh, dibuat_tgl)
                    VALUES ('$no_po', CURDATE(), '$supplier', '$kd_cabang', $total_item, $total_qty, $total_nilai, 'pending', 'Dari Rencana Order: $no_rencana', '$nama_user', NOW())";

                mysqli_query($koneksi, $sql_header);

                // Insert PO details
                foreach ($items as $item) {
                    $sql_detail = "INSERT INTO tbpembelian_detail
                        (no_order, no_item, nama_item, qty, harga, subtotal)
                        VALUES ('$no_po', '{$item['no_item']}', '{$item['nama_item']}', '{$item["{$order_field}_qty_final"]}', '{$item['harga']}', '{$item["{$order_field}_nilai"]}')";
                    mysqli_query($koneksi, $sql_detail);

                    // Update rencana order detail with PO number
                    mysqli_query($koneksi, "UPDATE tblrencana_order_detail SET no_po_{$order_field}='$no_po', status_{$order_field}='ordered' WHERE no_rencana='$no_rencana' AND no_item='{$item['no_item']}'");
                }

                // Update rencana order header status
                mysqli_query($koneksi, "UPDATE tblrencana_order_header SET status='ordered' WHERE no_rencana='$no_rencana'");

                mysqli_commit($koneksi);

                echo json_encode([
                    'success' => true,
                    'message' => "PO $no_po berhasil dibuat",
                    'no_po' => $no_po
                ]);

            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'get_suppliers_summary':
            // Get summary of suppliers for a rencana order
            $no_rencana = isset($_GET['no_rencana']) ? mysqli_real_escape_string($koneksi, $_GET['no_rencana']) : '';

            if (empty($no_rencana)) {
                echo json_encode(['success' => false, 'error' => 'No rencana tidak boleh kosong']);
                exit;
            }

            $order1 = [];
            $order1_query = mysqli_query($koneksi, "SELECT order1_supplier_final as supplier, COUNT(*) as jml_item, SUM(order1_qty_final) as total_qty, SUM(order1_nilai) as total_nilai FROM tblrencana_order_detail WHERE no_rencana='$no_rencana' AND order1_qty_final > 0 GROUP BY order1_supplier_final");
            while ($row = mysqli_fetch_assoc($order1_query)) {
                $order1[] = $row;
            }

            $order2 = [];
            $order2_query = mysqli_query($koneksi, "SELECT order2_supplier_final as supplier, COUNT(*) as jml_item, SUM(order2_qty_final) as total_qty, SUM(order2_nilai) as total_nilai FROM tblrencana_order_detail WHERE no_rencana='$no_rencana' AND order2_qty_final > 0 GROUP BY order2_supplier_final");
            while ($row = mysqli_fetch_assoc($order2_query)) {
                $order2[] = $row;
            }

            echo json_encode([
                'success' => true,
                'order1' => $order1,
                'order2' => $order2
            ]);
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
