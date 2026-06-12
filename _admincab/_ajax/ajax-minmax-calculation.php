<?php
/**
 * AJAX Endpoint untuk Kalkulasi MIN/MAX
 * Menghitung ulang MIN/MAX stok berdasarkan 84 hari (12 minggu)
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include "../../config/koneksi.php";
require_once "../lib/MinMaxCalculator.php";

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : null;

$calculator = new MinMaxCalculator($koneksi);

try {
    switch ($action) {
        case 'recalculate':
            // Hitung ulang MIN/MAX
            $targetCabang = isset($_POST['kd_cabang']) ? $_POST['kd_cabang'] : null;
            $result = $calculator->hitungMinMax($targetCabang);

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Kalkulasi MIN/MAX berhasil dijalankan'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            break;

        case 'get_items_need_order':
            // Dapatkan item yang perlu order
            $kategori = isset($_GET['kategori']) ? $_GET['kategori'] : null;
            $filterCabang = isset($_GET['kd_cabang']) ? $_GET['kd_cabang'] : $kd_cabang;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

            $items = $calculator->getItemPerluOrder($filterCabang, $kategori, $limit);

            echo json_encode([
                'success' => true,
                'data' => $items,
                'total' => count($items)
            ]);
            break;

        case 'get_summary':
            // Dapatkan summary per kategori
            $filterCabang = isset($_GET['kd_cabang']) ? $_GET['kd_cabang'] : null;
            $summary = $calculator->getSummaryPerKategori($filterCabang);

            echo json_encode([
                'success' => true,
                'data' => $summary
            ]);
            break;

        case 'get_cabang_stats':
            // Dapatkan statistik per cabang
            $stats = $calculator->getCabangStats();

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        case 'get_item_detail':
            // Dapatkan detail satu item
            $noItem = isset($_GET['no_item']) ? $_GET['no_item'] : '';
            $filterCabang = isset($_GET['kd_cabang']) ? $_GET['kd_cabang'] : null;

            if (empty($noItem)) {
                echo json_encode(['success' => false, 'error' => 'no_item required']);
                exit;
            }

            $detail = $calculator->getItemDetail($noItem, $filterCabang);

            echo json_encode([
                'success' => true,
                'data' => $detail
            ]);
            break;

        case 'get_transfer_suggest':
            // Dapatkan saran transfer antar cabang
            $noItem = isset($_GET['no_item']) ? $_GET['no_item'] : null;
            $transfers = $calculator->generateSaranTransfer($noItem);

            echo json_encode([
                'success' => true,
                'data' => $transfers,
                'total' => count($transfers)
            ]);
            break;

        case 'search_items':
            // Cari item dengan filter
            $search = isset($_GET['q']) ? $_GET['q'] : '';
            $filterCabang = isset($_GET['kd_cabang']) ? $_GET['kd_cabang'] : null;
            $kategori = isset($_GET['kategori']) ? $_GET['kategori'] : null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

            $escapedSearch = mysqli_real_escape_string($koneksi, $search);
            $conditions = [];

            if (!empty($search)) {
                $conditions[] = "(m.no_item LIKE '%{$escapedSearch}%' OR i.namaitem LIKE '%{$escapedSearch}%')";
            }
            if ($filterCabang) {
                $conditions[] = "m.kd_cabang = '" . mysqli_real_escape_string($koneksi, $filterCabang) . "'";
            }
            if ($kategori) {
                $conditions[] = "m.kategori = '" . mysqli_real_escape_string($koneksi, $kategori) . "'";
            }

            $whereClause = count($conditions) > 0 ? "WHERE " . implode(' AND ', $conditions) : "";

            $sql = "SELECT
                        m.no_item,
                        COALESCE(i.namaitem, m.no_item) AS nama_item,
                        m.kd_cabang,
                        c.nama_cabang,
                        m.stok_saat_ini,
                        m.min_stok,
                        m.max_stok,
                        m.kategori,
                        m.w1, m.w2, m.w3, m.w4, m.w5, m.w6, m.w7, m.w8, m.w9, m.w10, m.w11, m.w12,
                        m.total_transaksi_84hari,
                        ROUND(m.avg_interval_hari, 1) AS avg_interval_hari
                    FROM tblitem_minmax m
                    LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
                    LEFT JOIN tbcabang c ON m.kd_cabang = c.kode_cabang
                    {$whereClause}
                    ORDER BY m.kd_cabang, m.no_item
                    LIMIT {$limit}";

            $result = mysqli_query($koneksi, $sql);
            $items = [];

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['kategori_info'] = MinMaxCalculator::getKategoriInfo($row['kategori']);
                    $items[] = $row;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $items,
                'total' => count($items)
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
