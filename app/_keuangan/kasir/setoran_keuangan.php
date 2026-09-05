<?php
// Sumber: web_kasir/setoran_keuangan.php (8515 baris) — dashboard admin
// verifikasi & approval setoran keuangan cabang. Gerbang asli baris
// pertama sudah redirect kalau role bukan persis 'super_admin' (cek
// kedua admin/super_admin di bawahnya jadi tidak pernah reachable oleh
// admin) -> dipetakan ke izin kasir_admin (Task 10: satu-satunya kode
// yang persis ADM only).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_admin');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/process_pengadaan_verification.php';

$is_super_admin = true;
$is_admin = false;
$username = $nama_karyawan_aktif;
$role = 'super_admin';

$kode_karyawan = $kode_karyawan_aktif;
$message = $message ?? null;
$error = $error ?? null;

// Debug: Log all POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST REQUEST received to setoran_keuangan_closing_kasir.php");
    error_log("POST keys: " . print_r(array_keys($_POST), true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_notrx_pengadaan'])) {
    $kodePga = trim($_POST['kode_pengambilan'] ?? '');
    if ($kodePga !== '') {
        try {
            markPengadaanNoTrxVerified($pdo, $kodePga);
            $message = "Verifikasi No.Trx pengadaan berhasil untuk kode: " . $kodePga;
        } catch (Throwable $e) {
            $error = "Gagal verifikasi No.Trx pengadaan: " . $e->getMessage();
        }
    }
}

 function extractRefTrxIdsFromKeterangan($keterangan) {
     $keterangan = (string)$keterangan;
     if ($keterangan === '') {
         return [];
     }

     if (!preg_match('/\[Ref\s*TRX\s*:\s*([^\]]+)\]/i', $keterangan, $m)) {
         return [];
     }

     $raw = $m[1];
     $parts = array_map('trim', explode(',', $raw));
     $ids = [];
     foreach ($parts as $part) {
         if ($part === '') continue;
         if (ctype_digit($part)) {
             $ids[] = (int)$part;
         }
     }
     $ids = array_values(array_unique(array_filter($ids, function ($v) { return $v > 0; })));
     return $ids;
 }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_closing_pengadaan'])) {
    $kodePga = trim($_POST['kode_pengambilan'] ?? '');
    if ($kodePga !== '') {
        try {
            markPengadaanClosingVerified($pdo, $kodePga);
            $message = "Verifikasi sudah closing berhasil untuk kode: " . $kodePga;
        } catch (Throwable $e) {
            $error = "Gagal verifikasi sudah closing: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_mutasi_pengadaan'])) {
    $kodePga = trim($_POST['kode_pengambilan'] ?? '');
    if ($kodePga !== '') {
        try {
            markPengadaanMutasiBankVerified($pdo, $kodePga);
            $message = "Verifikasi mutasi bank berhasil untuk kode: " . $kodePga;
        } catch (Throwable $e) {
            $error = "Gagal verifikasi mutasi bank: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_mutasi_pengadaan'])) {
    $kodePga = trim($_POST['kode_pengambilan'] ?? '');
    if ($kodePga !== '') {
        try {
            if (empty($_FILES['dokumen_mutasi_keuangan']) || !is_array($_FILES['dokumen_mutasi_keuangan'])) {
                throw new RuntimeException('File mutasi pengembalian wajib diupload.');
            }

            $result = processPengambilanMutasiUpload(
                $pdo,
                $kodePga,
                $_FILES['dokumen_mutasi_keuangan'],
                $kode_karyawan
            );

            $nominalMutasi = (float)($result['document_meta']['nominal_terdeteksi'] ?? 0);
            $message = "Mutasi pengembalian berhasil diverifikasi untuk kode {$kodePga}. Nominal terbaca " . formatRupiah($nominalMutasi) . ".";
        } catch (Throwable $e) {
            $error = "Gagal upload mutasi pengembalian: " . $e->getMessage();
        }
    }
}

// Check if validation columns exist in kasir_transactions_closing_kasir table
$validation_columns_exist = false;
$selisih_fisik_is_generated = false;

try {
    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM kasir_transactions_closing_kasir LIKE 'jumlah_diterima_fisik'");
    $validation_columns_exist = $stmt->rowCount() > 0;
    
    // Check if selisih_fisik is a generated column
    if ($validation_columns_exist) {
        $stmt = $pdo->query("SHOW COLUMNS FROM kasir_transactions_closing_kasir LIKE 'selisih_fisik'");
        $column_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column_info && strpos(strtoupper($column_info['Extra']), 'GENERATED') !== false) {
            $selisih_fisik_is_generated = true;
        }
    }
} catch (Exception $e) {
    // Column doesn't exist, that's ok
    error_log("Column check error: " . $e->getMessage());
}

// Helper function to detect closing transactions
function isClosingTransaction($kode_transaksi) {
    return strpos($kode_transaksi, 'CLOSING') !== false || strpos($kode_transaksi, 'CLO') !== false;
}

// Helper: build safe regex patterns for closing date text (avoid TGL 2 matching TGL 29)
function buildClosingRegexPatterns(DateTime $tanggal) {
    $day = (int)$tanggal->format('j');
    $mon = strtoupper($tanggal->format('M'));
    $year = $tanggal->format('Y');
    $regex_date = '(^|[^0-9])0?' . $day . '[[:space:]]+' . $mon . '([[:space:]]+' . $year . ')?([^0-9]|$)';
    $regex_tgl = '(^|[^0-9])TGL[[:space:]]*0?' . $day . '([^0-9]|$)';
    return [$regex_date, $regex_tgl];
}

// Helper: normalize very small floating differences to zero (Rupiah precision)
function normalizeSelisih($nilai) {
    $num = (float)$nilai;
    return (abs($num) < 0.5) ? 0.0 : $num;
}

function sumClosingBorrowedByDirectLink($pdo, $kode_transaksi) {
    static $cache = [];

    $kode_transaksi = trim((string)$kode_transaksi);
    if ($kode_transaksi === '') {
        return 0.0;
    }

    if (isset($cache[$kode_transaksi])) {
        return $cache[$kode_transaksi];
    }

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0)
                           FROM pemasukan_kasir_closing_kasir
                           WHERE kode_akun = 'DRCLSG'
                             AND TRIM(nomor_transaksi_closing) = TRIM(?)");
    $stmt->execute([$kode_transaksi]);

    return $cache[$kode_transaksi] = (float)$stmt->fetchColumn();
}

function sumClosingBorrowedByDateFallback($pdo, $kode_cabang, $tanggal_transaksi, $kode_setoran = null, $window_days = 14) {
    static $cache = [];

    $cache_key = implode('|', [
        trim((string)$kode_cabang),
        trim((string)$tanggal_transaksi),
        trim((string)$kode_setoran),
        (int)$window_days
    ]);

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    if (empty($kode_cabang) || empty($tanggal_transaksi)) {
        return $cache[$cache_key] = 0.0;
    }

    try {
        $tanggal_obj = $tanggal_transaksi instanceof DateTimeInterface
            ? new DateTime($tanggal_transaksi->format('Y-m-d'))
            : new DateTime((string)$tanggal_transaksi);

        [$regex_date, $regex_tgl] = buildClosingRegexPatterns($tanggal_obj);
        $tanggal_awal = $tanggal_obj->format('Y-m-d');
        $tanggal_akhir_obj = clone $tanggal_obj;
        $tanggal_akhir_obj->modify('+' . max(0, (int)$window_days) . ' days');
        $tanggal_akhir = $tanggal_akhir_obj->format('Y-m-d');

        $sql = "SELECT COALESCE(SUM(pk.jumlah), 0)
                FROM pemasukan_kasir_closing_kasir pk
                JOIN kasir_transactions_closing_kasir ktp ON ktp.kode_transaksi = pk.kode_transaksi
                WHERE pk.kode_akun = 'DRCLSG'
                  AND (pk.nomor_transaksi_closing IS NULL OR pk.nomor_transaksi_closing = '')
                  AND ktp.kode_cabang = ?
                  AND UPPER(pk.keterangan_transaksi) LIKE '%CLOSING%'
                  AND (
                       UPPER(pk.keterangan_transaksi) REGEXP ?
                       OR UPPER(pk.keterangan_transaksi) REGEXP ?
                  )
                  AND pk.tanggal BETWEEN ? AND ?";

        $params = [$kode_cabang, $regex_date, $regex_tgl, $tanggal_awal, $tanggal_akhir];

        if (!empty($kode_setoran)) {
            $sql .= " AND ktp.kode_setoran = ?";
            $params[] = $kode_setoran;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $cache[$cache_key] = (float)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Closing fallback calculation error: " . $e->getMessage());
        return $cache[$cache_key] = 0.0;
    }
}

function sumClosingBorrowedByCashierFallback($pdo, $kode_transaksi, $kode_cabang, $kode_karyawan, $tanggal_transaksi, $window_days = 3) {
    static $cache = [];

    $cache_key = implode('|', [
        trim((string)$kode_transaksi),
        trim((string)$kode_cabang),
        trim((string)$kode_karyawan),
        trim((string)$tanggal_transaksi),
        (int)$window_days
    ]);

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    if (empty($kode_transaksi) || empty($kode_cabang) || empty($kode_karyawan) || empty($tanggal_transaksi)) {
        return $cache[$cache_key] = 0.0;
    }

    try {
        $tanggal_obj = $tanggal_transaksi instanceof DateTimeInterface
            ? new DateTime($tanggal_transaksi->format('Y-m-d'))
            : new DateTime((string)$tanggal_transaksi);

        $tanggal_awal = $tanggal_obj->format('Y-m-d');
        $tanggal_akhir_obj = clone $tanggal_obj;
        $tanggal_akhir_obj->modify('+' . max(1, (int)$window_days) . ' days');
        $tanggal_akhir = $tanggal_akhir_obj->format('Y-m-d');

        $sql = "SELECT COALESCE(SUM(pk.jumlah), 0)
                FROM pemasukan_kasir_closing_kasir pk
                JOIN kasir_transactions_closing_kasir ktp ON ktp.kode_transaksi = pk.kode_transaksi
                WHERE pk.kode_akun = 'DRCLSG'
                  AND (pk.nomor_transaksi_closing IS NULL OR TRIM(pk.nomor_transaksi_closing) = '')
                  AND ktp.kode_transaksi <> ?
                  AND ktp.kode_cabang = ?
                  AND (
                      ktp.kode_karyawan = ?
                      OR ktp.kasir_asal = ?
                  )
                  AND ktp.tanggal_transaksi > ?
                  AND ktp.tanggal_transaksi <= ?
                  AND NOT EXISTS (
                      SELECT 1
                      FROM kasir_transactions_closing_kasir kt_prev
                      WHERE kt_prev.kode_transaksi <> ?
                        AND kt_prev.kode_cabang = ?
                        AND (
                            kt_prev.kode_karyawan = ?
                            OR kt_prev.kasir_asal = ?
                        )
                        AND kt_prev.status = 'end proses'
                        AND kt_prev.tanggal_transaksi > ?
                        AND kt_prev.tanggal_transaksi < ktp.tanggal_transaksi
                  )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            trim((string)$kode_transaksi),
            trim((string)$kode_cabang),
            trim((string)$kode_karyawan),
            trim((string)$kode_karyawan),
            $tanggal_awal,
            $tanggal_akhir,
            trim((string)$kode_transaksi),
            trim((string)$kode_cabang),
            trim((string)$kode_karyawan),
            trim((string)$kode_karyawan),
            $tanggal_awal
        ]);

        return $cache[$cache_key] = (float)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Closing cashier fallback calculation error: " . $e->getMessage());
        return $cache[$cache_key] = 0.0;
    }
}

function getClosingBorrowedAmount($pdo, $kode_transaksi, $kode_setoran, $kode_cabang, $tanggal_transaksi, $kode_karyawan = null) {
    static $cache = [];

    $cache_key = implode('|', [
        trim((string)$kode_transaksi),
        trim((string)$kode_setoran),
        trim((string)$kode_cabang),
        trim((string)$tanggal_transaksi),
        trim((string)$kode_karyawan)
    ]);

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $direct_borrowed = sumClosingBorrowedByDirectLink($pdo, $kode_transaksi);
    $same_setoran_borrowed = sumClosingBorrowedByDateFallback($pdo, $kode_cabang, $tanggal_transaksi, $kode_setoran, 14);
    $next_cashier_borrowed = sumClosingBorrowedByCashierFallback($pdo, $kode_transaksi, $kode_cabang, $kode_karyawan, $tanggal_transaksi, 3);
    $total_borrowed = $direct_borrowed + $same_setoran_borrowed + $next_cashier_borrowed;

    $has_closing_context = isClosingTransaction($kode_transaksi) || $total_borrowed > 0;

    if (!$has_closing_context) {
        return $cache[$cache_key] = 0.0;
    }

    if ($total_borrowed > 0) {
        return $cache[$cache_key] = $total_borrowed;
    }

    if (isClosingTransaction($kode_transaksi)) {
        return $cache[$cache_key] = sumClosingBorrowedByDateFallback($pdo, $kode_cabang, $tanggal_transaksi, null, 14);
    }

    return $cache[$cache_key] = 0.0;
}

function calculateExpectedPhysicalAmount($setoran_real, $borrowed_amount = 0) {
    return max(0, (float)$setoran_real - max(0, (float)$borrowed_amount));
}

function getInternalClosingUsageInfo($pdo, $kode_transaksi) {
    static $cache = [];

    $kode_transaksi = trim((string)$kode_transaksi);
    if ($kode_transaksi === '') {
        return [
            'amount' => 0.0,
            'count' => 0,
            'references' => []
        ];
    }

    if (isset($cache[$kode_transaksi])) {
        return $cache[$kode_transaksi];
    }

    $stmt = $pdo->prepare("SELECT
                                COALESCE(SUM(jumlah), 0) AS total_amount,
                                COUNT(*) AS total_rows,
                                GROUP_CONCAT(DISTINCT TRIM(nomor_transaksi_closing) ORDER BY nomor_transaksi_closing SEPARATOR ', ') AS closing_refs
                           FROM pemasukan_kasir_closing_kasir
                           WHERE kode_transaksi = ?
                             AND kode_akun = 'DRCLSG'");
    $stmt->execute([$kode_transaksi]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $references = [];
    if (!empty($row['closing_refs'])) {
        $references = array_values(array_filter(array_map('trim', explode(',', $row['closing_refs']))));
    }

    return $cache[$kode_transaksi] = [
        'amount' => (float)($row['total_amount'] ?? 0),
        'count' => (int)($row['total_rows'] ?? 0),
        'references' => $references
    ];
}

function enrichClosingTransactionRow($pdo, array $row) {
    $kode_transaksi = trim((string)($row['kode_transaksi'] ?? ''));
    $tanggal_transaksi = $row['tanggal_transaksi'] ?? null;

    if ($kode_transaksi === '' || empty($tanggal_transaksi)) {
        return $row;
    }

    $row['resolved_kode_cabang'] = $row['resolved_kode_cabang'] ?? ($row['kode_cabang'] ?? null);
    $row['resolved_nama_cabang'] = $row['resolved_nama_cabang'] ?? ($row['nama_cabang_setoran'] ?? ($row['nama_cabang'] ?? null));

    $kode_karyawan = $row['kasir_asal'] ?? $row['kt_kode_karyawan'] ?? $row['kode_karyawan'] ?? null;
    $borrowed_amount = getClosingBorrowedAmount(
        $pdo,
        $kode_transaksi,
        $row['kode_setoran'] ?? null,
        $row['resolved_kode_cabang'],
        $tanggal_transaksi,
        $kode_karyawan
    );

    $is_closing = isClosingTransaction($kode_transaksi) || $borrowed_amount > 0;
    $row['jenis_transaksi'] = $is_closing ? 'DARI CLOSING' : 'TRANSAKSI BIASA';

    $total_borrowed = $is_closing ? $borrowed_amount : 0.0;
    $row['total_pemasukan_closing'] = $total_borrowed;
    $row['total_closing_borrowed'] = $total_borrowed;
    $row['expected_physical_amount'] = calculateExpectedPhysicalAmount($row['setoran_real'] ?? 0, $total_borrowed);
    $row['expected_physical'] = $row['expected_physical_amount'];

    $internal_closing_usage = getInternalClosingUsageInfo($pdo, $kode_transaksi);
    $row['internal_closing_usage_amount'] = $internal_closing_usage['amount'];
    $row['internal_closing_usage_count'] = $internal_closing_usage['count'];
    $row['internal_closing_usage_refs'] = $internal_closing_usage['references'];
    $row['has_internal_closing_usage'] = $internal_closing_usage['amount'] > 0;
    $row['jenis_transaksi_tampil'] = $is_closing
        ? 'DARI CLOSING'
        : ($row['has_internal_closing_usage'] ? 'MEMINJAM DARI CLOSING' : 'TRANSAKSI BIASA');

    return $row;
}

// PERBAIKAN: Enhanced function to get closing transaction details dengan kalkulasi gabungan yang benar
function getClosingTransactionDetails($pdo, $kode_setoran) {
    $sql = "SELECT 
                kt.*,
                CASE 
                    WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                    WHEN EXISTS (
                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                        WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                    ) THEN 'DARI CLOSING'
                    WHEN EXISTS (
                        SELECT 1
                        FROM pemasukan_kasir_closing_kasir pkx
                        JOIN kasir_transactions_closing_kasir ktx ON ktx.kode_transaksi = pkx.kode_transaksi
                        WHERE ktx.kode_setoran = kt.kode_setoran
                          AND ktx.kode_cabang = kt.kode_cabang
                          AND (
                               pkx.nomor_transaksi_closing = kt.kode_transaksi
                               OR (
                                    (pkx.nomor_transaksi_closing IS NULL OR pkx.nomor_transaksi_closing = '')
                                    AND pkx.kode_akun = 'DRCLSG'
                                    AND UPPER(pkx.keterangan_transaksi) LIKE '%CLOSING%'
                               )
                          )
                    ) THEN 'DARI CLOSING'
                    ELSE 'TRANSAKSI BIASA'
                END as jenis_transaksi,
                sk.nama_cabang AS nama_cabang_setoran, 
                sk.tanggal_setoran, 
                sk.nama_pengantar,
                COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan,
                -- TAMBAHAN: Informasi pemasukan terkait untuk closing
                pk.jumlah as jumlah_pemasukan_closing,
                pk.keterangan_transaksi as keterangan_closing,
                (
                    SELECT COALESCE(SUM(pk3.jumlah), 0) 
                    FROM pemasukan_kasir_closing_kasir pk3 
                    JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                    WHERE kt3.kode_setoran = kt.kode_setoran
                      AND kt3.kode_cabang = kt.kode_cabang
                      AND (
                            pk3.nomor_transaksi_closing = kt.kode_transaksi
                         OR (
                              (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                              AND pk3.kode_akun = 'DRCLSG'
                              AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                            )
                         )
                ) as total_closing_borrowed_agg
            FROM kasir_transactions_closing_kasir kt
            LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
            LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
            LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
            WHERE kt.kode_setoran = ?
            ORDER BY 
                CASE 
                    WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 0
                    WHEN EXISTS (
                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk2 
                        WHERE pk2.nomor_transaksi_closing = kt.kode_transaksi
                    ) THEN 0
                    ELSE 1
                END,
                kt.tanggal_transaksi ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kode_setoran]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row = enrichClosingTransactionRow($pdo, $row);
    }
    unset($row);

    return $rows;
}

// PERBAIKAN: Enhanced function to get aggregated closing info dengan kalkulasi gabungan
function getClosingAggregatedInfo($pdo, $kode_setoran) {
    $transactions = getClosingTransactionDetails($pdo, $kode_setoran);
    $closingInfo = [];
    
    // Group by jenis_transaksi and cabang
    foreach ($transactions as $trans) {
        $key = $trans['nama_cabang'] . '_' . $trans['jenis_transaksi'];
        
        if (!isset($closingInfo[$key])) {
            $closingInfo[$key] = [
                'cabang' => $trans['nama_cabang'],
                'jenis' => $trans['jenis_transaksi'],
                'transactions' => [],
                'total_sistem' => 0,
                'total_diterima' => 0,
                'total_selisih' => 0,
                'count' => 0,
                // TAMBAHAN: Informasi closing gabungan
                'total_closing_original' => 0,
                'total_closing_borrowed' => 0,
                'total_closing_lent' => 0
            ];
        }
        
        $closingInfo[$key]['transactions'][] = $trans;
        
        // PERBAIKAN: Kalkulasi gabungan untuk closing
        $borrowedAgg = isset($trans['total_closing_borrowed_agg']) ? (float)$trans['total_closing_borrowed_agg'] : 0;
        if ($borrowedAgg > 0) {
            // Jika ada indikasi gabungan (pinjam) untuk transaksi ini, kurangi dari sistem
            $closingInfo[$key]['total_closing_borrowed'] += abs($borrowedAgg);
            $closingInfo[$key]['total_sistem'] += $trans['setoran_real'] - abs($borrowedAgg);
        } else {
            $closingInfo[$key]['total_sistem'] += $trans['setoran_real'];
        }
        
        if (isset($trans['jumlah_diterima_fisik']) && $trans['jumlah_diterima_fisik'] !== null) {
            $closingInfo[$key]['total_diterima'] += $trans['jumlah_diterima_fisik'];
            if (isset($trans['selisih_fisik'])) {
                $closingInfo[$key]['total_selisih'] += $trans['selisih_fisik'];
            } else {
                $closingInfo[$key]['total_selisih'] += ($trans['jumlah_diterima_fisik'] - $trans['setoran_real']);
            }
        } else {
            $closingInfo[$key]['total_diterima'] += $trans['setoran_real'];
        }
        
        $closingInfo[$key]['count']++;
    }
    
    return $closingInfo;
}

// Handle penerimaan setoran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['terima_setoran'])) {
    $setoran_ids = $_POST['setoran_ids'] ?? [];
    
    if (empty($setoran_ids)) {
        $error = "Pilih setidaknya satu setoran untuk diterima.";
    } else {
        $pdo->beginTransaction();
        try {
            $success_count = 0;
            $received_setorans = []; // Untuk menyimpan data setoran yang diterima
            
            foreach ($setoran_ids as $setoran_id) {
                // Get setoran data before update
                $sql_get_detail = "SELECT * FROM setoran_keuangan_closing_kasir WHERE id = ? AND status = 'Sedang Dibawa Kurir'";
                $stmt_get_detail = $pdo->prepare($sql_get_detail);
                $stmt_get_detail->execute([$setoran_id]);
                $setoran_detail = $stmt_get_detail->fetch(PDO::FETCH_ASSOC);
                
                if ($setoran_detail) {
                    $sql_update = "UPDATE setoran_keuangan_closing_kasir SET 
                                  status = 'Diterima Staff Keuangan', 
                                  updated_by = ?, 
                                  updated_at = NOW()
                                  WHERE id = ? AND status = 'Sedang Dibawa Kurir'";
                    $stmt_update = $pdo->prepare($sql_update);
                    if ($stmt_update->execute([$kode_karyawan, $setoran_id])) {
                        if ($stmt_update->rowCount() > 0) {
                            $success_count++;
                            $received_setorans[] = $setoran_detail; // Simpan data untuk bukti penerimaan
                            
                            $sql_update_kasir = "UPDATE kasir_transactions_closing_kasir SET 
                                                deposit_status = 'Diterima Staff Keuangan'
                                                WHERE kode_setoran = ? AND deposit_status = 'Sedang Dibawa Kurir'";
                            $stmt_update_kasir = $pdo->prepare($sql_update_kasir);
                            $stmt_update_kasir->execute([$setoran_detail['kode_setoran']]);
                        }
                    }
                }
            }

            $pdo->commit();
            $message = "$success_count setoran berhasil diterima. Silakan lakukan validasi fisik selanjutnya.";
            
            // Store received setorans in session untuk ditampilkan sebagai bukti
            $_SESSION['received_setorans'] = $received_setorans;
            $_SESSION['received_by'] = $username;
            $_SESSION['received_at'] = date('Y-m-d H:i:s');
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error: " . $e->getMessage();
        }
    }
}

// PERBAIKAN: Handle validasi fisik transaksi individual dengan kalkulasi closing yang benar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['validasi_individual'])) {
    $transaksi_id = $_POST['transaksi_id'];
    $jumlah_diterima = str_replace(['Rp ', '.', ','], ['', '', ''], $_POST['jumlah_diterima'] ?? 0);
    $catatan_validasi = $_POST['catatan_validasi'] ?? '';

    if (!is_numeric($jumlah_diterima) || $jumlah_diterima < 0) {
        $error = "Jumlah diterima tidak valid.";
    } else {
        // Closing context will be recalculated from direct link/fallback after data is loaded
        $is_closing = false;
        
        // PERBAIKAN: Enhanced query untuk mendapatkan informasi closing lengkap
        $sql_transaksi = "SELECT kt.setoran_real, kt.kode_setoran, kt.tanggal_transaksi, kt.kode_cabang,
                                 -- Informasi closing jika ada
                                 pk.jumlah as jumlah_pemasukan_closing,
                                 pk.keterangan_transaksi as keterangan_closing,
                                 -- Hitung total gabungan closing dengan fallback deskripsi dan dalam 1 kode setoran
                                 (
                                    SELECT COALESCE(SUM(pk3.jumlah), 0)
                                    FROM pemasukan_kasir_closing_kasir pk3
                                    JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                                    WHERE kt3.kode_setoran = kt.kode_setoran
                                      AND kt3.kode_cabang = kt.kode_cabang
                                      AND (
                                           pk3.nomor_transaksi_closing = kt.kode_transaksi
                                           OR (
                                                (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                                                AND pk3.kode_akun = 'DRCLSG'
                                                AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                                                AND (
                                                    -- Cocokkan tanggal closing dengan regex aman (hindari TGL 2 match TGL 29)
                                                    UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])0?', DAY(kt.tanggal_transaksi), '[[:space:]]+', UPPER(DATE_FORMAT(kt.tanggal_transaksi, '%b')), '([[:space:]]+', DATE_FORMAT(kt.tanggal_transaksi, '%Y'), ')?([^0-9]|$)')
                                                    OR UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])TGL[[:space:]]*0?', DAY(kt.tanggal_transaksi), '([^0-9]|$)')
                                                )
                                           )
                                      )
                                 ) as total_closing_borrowed
                          FROM kasir_transactions_closing_kasir kt
                          LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
                          WHERE kt.kode_transaksi = ? AND kt.deposit_status = 'Diterima Staff Keuangan'";
        $stmt = $pdo->prepare($sql_transaksi);
        $stmt->execute([$transaksi_id]);
        $data_transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data_transaksi) {
            $borrowed_amount = getClosingBorrowedAmount(
                $pdo,
                $transaksi_id,
                $data_transaksi['kode_setoran'] ?? '',
                $data_transaksi['kode_cabang'] ?? '',
                $data_transaksi['tanggal_transaksi'] ?? ''
            );
            $data_transaksi['total_closing_borrowed'] = $borrowed_amount;
            $is_closing = isClosingTransaction($transaksi_id) || $borrowed_amount > 0;

            // PERBAIKAN: Kalkulasi selisih dengan mempertimbangkan gabungan closing
            $setoran_real = $data_transaksi['setoran_real'];
            $borrowed_amount = max(0, (float)$data_transaksi['total_closing_borrowed']);
             
            // Jika ini transaksi closing yang meminjam/dipinjam, sesuaikan kalkulasi
            if ($is_closing && $borrowed_amount > 0) {
                // Untuk transaksi yang ada peminjamannya, sistem seharusnya menghitung:
                // Setoran Real - Jumlah yang dipinjam = Setoran yang seharusnya diterima fisik
                $expected_physical = calculateExpectedPhysicalAmount($setoran_real, $borrowed_amount);
                $selisih = $jumlah_diterima - $expected_physical;
                 
                // Log untuk debugging
                error_log("Closing Transaction Validation: $transaksi_id - Setoran Real: $setoran_real, Borrowed: $borrowed_amount, Expected: $expected_physical, Received: $jumlah_diterima, Selisih: $selisih");
            } else {
                $selisih = $jumlah_diterima - $setoran_real;
            }
            $selisih = normalizeSelisih($selisih);
            
            $kode_setoran = $data_transaksi['kode_setoran'];

            $pdo->beginTransaction();
            try {
                $new_status = ($selisih == 0) ? 'Validasi Keuangan OK' : 'Validasi Keuangan SELISIH';

                if ($validation_columns_exist) {
                    // Handle generated vs manual selisih_fisik column
                    if ($selisih_fisik_is_generated) {
                        $sql_update = "UPDATE kasir_transactions_closing_kasir SET 
                                      jumlah_diterima_fisik = ?, 
                                      deposit_status = ?, 
                                      catatan_validasi = ?,
                                      validasi_at = NOW(),
                                      validasi_by = ?
                                      WHERE kode_transaksi = ? AND deposit_status = 'Diterima Staff Keuangan'";
                        $stmt_update = $pdo->prepare($sql_update);
                        $stmt_update->execute([$jumlah_diterima, $new_status, $catatan_validasi, $kode_karyawan, $transaksi_id]);
                    } else {
                        $sql_update = "UPDATE kasir_transactions_closing_kasir SET 
                                      jumlah_diterima_fisik = ?, 
                                      selisih_fisik = ?, 
                                      deposit_status = ?, 
                                      catatan_validasi = ?,
                                      validasi_at = NOW(),
                                      validasi_by = ?
                                      WHERE kode_transaksi = ? AND deposit_status = 'Diterima Staff Keuangan'";
                        $stmt_update = $pdo->prepare($sql_update);
                        $stmt_update->execute([$jumlah_diterima, $selisih, $new_status, $catatan_validasi, $kode_karyawan, $transaksi_id]);
                    }
                } else {
                    $sql_update = "UPDATE kasir_transactions_closing_kasir SET 
                                  deposit_status = ?
                                  WHERE kode_transaksi = ? AND deposit_status = 'Diterima Staff Keuangan'";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->execute([$new_status, $transaksi_id]);
                }

                if ($stmt_update->rowCount() > 0) {
                    // Update setoran_keuangan_closing_kasir status with improved logic for closing transactions
                    updateSetoranKeuanganStatus($pdo, $kode_setoran, $kode_karyawan, $validation_columns_exist, $selisih_fisik_is_generated);
                    
                    $pdo->commit();
                    
                    // Enhanced success message for closing transactions
                    $closing_info = $is_closing ? " [DARI CLOSING]" : "";
                    if ($data_transaksi['total_closing_borrowed'] > 0) {
                        $closing_info .= " [GABUNGAN: " . formatRupiah($data_transaksi['total_closing_borrowed']) . " dipinjam]";
                    }
                    $message = "Validasi berhasil$closing_info. Status: $new_status. Total diterima: " . formatRupiah($jumlah_diterima) . 
                              ($selisih != 0 ? " | Selisih: " . formatRupiah($selisih) : " | Sesuai dengan sistem");
                } else {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = "Transaksi tidak dapat divalidasi. Pastikan status transaksi adalah 'Diterima Staff Keuangan'.";
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Error validasi: " . $e->getMessage();
                error_log("Validation error: " . $e->getMessage());
            }
        } else {
            $error = "Transaksi tidak ditemukan atau belum diterima.";
        }
    }
}

// PERBAIKAN: Handle edit selisih dengan kalkulasi closing yang benar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_selisih'])) {
    $transaksi_id = $_POST['transaksi_id'];
    $jumlah_diterima_baru = str_replace(['Rp ', '.', ','], ['', '', ''], $_POST['jumlah_diterima_baru'] ?? 0);
    $catatan_validasi = $_POST['catatan_validasi'] ?? '';

    if (!is_numeric($jumlah_diterima_baru) || $jumlah_diterima_baru < 0) {
        $error = "Jumlah diterima tidak valid.";
    } else {
        $is_closing = false;
        
        // PERBAIKAN: Enhanced query untuk edit selisih closing
        $sql_transaksi = "SELECT kt.setoran_real, kt.kode_setoran, kt.tanggal_transaksi, kt.kode_cabang,
                                 -- Informasi closing jika ada
                                 pk.jumlah as jumlah_pemasukan_closing,
                                 pk.keterangan_transaksi as keterangan_closing,
                                 -- Hitung total gabungan closing dengan fallback deskripsi dan dalam 1 kode setoran
                                 (
                                    SELECT COALESCE(SUM(pk3.jumlah), 0)
                                    FROM pemasukan_kasir_closing_kasir pk3
                                    JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                                    WHERE kt3.kode_setoran = kt.kode_setoran
                                      AND kt3.kode_cabang = kt.kode_cabang
                                      AND (
                                           pk3.nomor_transaksi_closing = kt.kode_transaksi
                                           OR (
                                                (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                                                AND pk3.kode_akun = 'DRCLSG'
                                                AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                                                AND (
                                                    UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])0?', DAY(kt.tanggal_transaksi), '[[:space:]]+', UPPER(DATE_FORMAT(kt.tanggal_transaksi, '%b')), '([[:space:]]+', DATE_FORMAT(kt.tanggal_transaksi, '%Y'), ')?([^0-9]|$)')
                                                    OR UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])TGL[[:space:]]*0?', DAY(kt.tanggal_transaksi), '([^0-9]|$)')
                                                )
                                           )
                                      )
                                 ) as total_closing_borrowed
                          FROM kasir_transactions_closing_kasir kt
                          LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
                          WHERE kt.kode_transaksi = ?";
        $stmt = $pdo->prepare($sql_transaksi);
        $stmt->execute([$transaksi_id]);
        $data_transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data_transaksi) {
            $borrowed_amount = getClosingBorrowedAmount(
                $pdo,
                $transaksi_id,
                $data_transaksi['kode_setoran'] ?? '',
                $data_transaksi['kode_cabang'] ?? '',
                $data_transaksi['tanggal_transaksi'] ?? ''
            );
            $data_transaksi['total_closing_borrowed'] = $borrowed_amount;
            $is_closing = isClosingTransaction($transaksi_id) || $borrowed_amount > 0;

            // PERBAIKAN: Kalkulasi selisih baru dengan mempertimbangkan gabungan closing
            $setoran_real = $data_transaksi['setoran_real'];
            $borrowed_amount = max(0, (float)$data_transaksi['total_closing_borrowed']);
             
            // Jika ini transaksi closing yang meminjam/dipinjam, sesuaikan kalkulasi
            if ($is_closing && $borrowed_amount > 0) {
                $expected_physical = calculateExpectedPhysicalAmount($setoran_real, $borrowed_amount);
                $selisih_baru = $jumlah_diterima_baru - $expected_physical;
            } else {
                $selisih_baru = $jumlah_diterima_baru - $setoran_real;
            }
            $selisih_baru = normalizeSelisih($selisih_baru);
            
            $kode_setoran = $data_transaksi['kode_setoran'];

            $pdo->beginTransaction();
            try {
                $new_status = ($selisih_baru == 0) ? 'Validasi Keuangan OK' : 'Validasi Keuangan SELISIH';

                if ($validation_columns_exist) {
                    if ($selisih_fisik_is_generated) {
                        $sql_update = "UPDATE kasir_transactions_closing_kasir SET 
                                      jumlah_diterima_fisik = ?, 
                                      deposit_status = ?, 
                                      catatan_validasi = ?,
                                      validasi_at = NOW(),
                                      validasi_by = ?
                                      WHERE kode_transaksi = ?";
                        $stmt_update = $pdo->prepare($sql_update);
                        $stmt_update->execute([$jumlah_diterima_baru, $new_status, $catatan_validasi, $kode_karyawan, $transaksi_id]);
                    } else {
                        $sql_update = "UPDATE kasir_transactions_closing_kasir SET 
                                      jumlah_diterima_fisik = ?, 
                                      selisih_fisik = ?, 
                                      deposit_status = ?, 
                                      catatan_validasi = ?,
                                      validasi_at = NOW(),
                                      validasi_by = ?
                                      WHERE kode_transaksi = ?";
                        $stmt_update = $pdo->prepare($sql_update);
                        $stmt_update->execute([$jumlah_diterima_baru, $selisih_baru, $new_status, $catatan_validasi, $kode_karyawan, $transaksi_id]);
                    }
                } else {
                    $sql_update = "UPDATE kasir_transactions_closing_kasir SET 
                                  deposit_status = ?
                                  WHERE kode_transaksi = ?";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->execute([$new_status, $transaksi_id]);
                }

                if ($stmt_update->rowCount() > 0) {
                    // Update setoran_keuangan_closing_kasir status
                    updateSetoranKeuanganStatus($pdo, $kode_setoran, $kode_karyawan, $validation_columns_exist, $selisih_fisik_is_generated);

                    $pdo->commit();
                    
                    $closing_info = $is_closing ? " [DARI CLOSING]" : "";
                    if ($data_transaksi['total_closing_borrowed'] > 0) {
                        $closing_info .= " [GABUNGAN: " . formatRupiah($data_transaksi['total_closing_borrowed']) . " dipinjam]";
                    }
                    $message = "Edit selisih berhasil$closing_info. Status: $new_status. Total diterima: " . formatRupiah($jumlah_diterima_baru) . 
                              ($selisih_baru != 0 ? " | Selisih: " . formatRupiah($selisih_baru) : " | Sesuai dengan sistem");
                } else {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = "Transaksi tidak dapat diupdate.";
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Error edit selisih: " . $e->getMessage();
                error_log("Edit selisih error: " . $e->getMessage());
            }
        } else {
            $error = "Transaksi tidak ditemukan atau bukan status selisih.";
        }
    }
}

// Handle kembalikan setoran ke CS pengirim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kembalikan_ke_cs'])) {
    $transaksi_id = $_POST['transaksi_id'];
    $alasan_kembalikan = $_POST['alasan_kembalikan'] ?? '';
    
    // Validasi transaksi exists dan status yang bisa dikembalikan (selisih atau masih dalam validasi)
    $sql_check = "SELECT kt.*, sk.kode_karyawan, sk.kode_cabang, u.nama_lengkap AS nama_karyawan, cb.nama_cabang
                  FROM kasir_transactions_closing_kasir kt
                  LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
                  LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
                  LEFT JOIN tbcabang cb ON cb.cabang_ref_kode = sk.kode_cabang
                  WHERE kt.kode_transaksi = ? AND kt.deposit_status IN ('Validasi Keuangan SELISIH', 'Diterima Staff Keuangan')";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$transaksi_id]);
    $data_transaksi = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($data_transaksi) {
        $pdo->beginTransaction();
        try {
            // PERBAIKAN: Update status transaksi ke "Dikembalikan ke CS" dan handle closing transactions
            $transaksi_dikembalikan = [$transaksi_id];
            $catatan_kembalikan = "DIKEMBALIKAN KE CS - Alasan: " . $alasan_kembalikan;
            
            // Check if this is a closing transaction that involves related transactions
            // 1. Check if this transaction is taken by another transaction (closing B mengambil dari A)
            $sql_check_taken = "
                SELECT kt_taking.kode_transaksi as taking_transaction
                FROM pemasukan_kasir_closing_kasir pk 
                INNER JOIN kasir_transactions_closing_kasir kt_taking ON pk.kode_transaksi = kt_taking.kode_transaksi
                WHERE pk.nomor_transaksi_closing = ?
                AND kt_taking.kode_setoran = ?
            ";
            $stmt_taken = $pdo->prepare($sql_check_taken);
            $stmt_taken->execute([$transaksi_id, $data_transaksi['kode_setoran']]);
            $taking_transaction = $stmt_taken->fetchColumn();
            
            if ($taking_transaction) {
                $transaksi_dikembalikan[] = $taking_transaction;
                $catatan_kembalikan .= " (Termasuk transaksi terkait: {$taking_transaction})";
            }
            
            // 2. Check if this transaction takes from another transaction (closing A yang diambil oleh B)
            $sql_check_takes_from = "
                SELECT pk.nomor_transaksi_closing as source_transaction
                FROM pemasukan_kasir_closing_kasir pk 
                INNER JOIN kasir_transactions_closing_kasir kt_source ON pk.nomor_transaksi_closing = kt_source.kode_transaksi
                WHERE pk.kode_transaksi = ?
                AND kt_source.kode_setoran = ?
            ";
            $stmt_takes_from = $pdo->prepare($sql_check_takes_from);
            $stmt_takes_from->execute([$transaksi_id, $data_transaksi['kode_setoran']]);
            $source_transaction = $stmt_takes_from->fetchColumn();
            
            if ($source_transaction && !in_array($source_transaction, $transaksi_dikembalikan)) {
                $transaksi_dikembalikan[] = $source_transaction;
                $catatan_kembalikan .= " (Termasuk sumber transaksi: {$source_transaction})";
            }
            
            // Update all related transactions
            $updated_count = 0;
            foreach ($transaksi_dikembalikan as $kode_trans) {
                $sql_update = "UPDATE kasir_transactions_closing_kasir SET 
                              deposit_status = 'Dikembalikan ke CS',
                              catatan_validasi = ?,
                              validasi_at = NOW(),
                              validasi_by = ?
                              WHERE kode_transaksi = ? AND kode_setoran = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$catatan_kembalikan, $kode_karyawan_aktif, $kode_trans, $data_transaksi['kode_setoran']]);
                $updated_count += $stmt_update->rowCount();
            }
            
            if ($updated_count > 0) {
                // Update setoran_keuangan_closing_kasir status
                updateSetoranKeuanganStatus($pdo, $data_transaksi['kode_setoran'], $data_transaksi['kode_karyawan'], $validation_columns_exist, $selisih_fisik_is_generated);
                
                // Log aktivitas
                $transaksi_list = implode(', ', $transaksi_dikembalikan);
                $log_message = "Transaksi closing dikembalikan: {$transaksi_list} ke CS {$data_transaksi['nama_karyawan']} - Cabang {$data_transaksi['nama_cabang']}. Alasan: {$alasan_kembalikan}";
                error_log("KEMBALIKAN KE CS CLOSING: " . $log_message);
                
                $pdo->commit();
                
                if (count($transaksi_dikembalikan) > 1) {
                    $message = "Berhasil dikembalikan " . count($transaksi_dikembalikan) . " transaksi terkait closing ke CS: " . $data_transaksi['nama_karyawan'] . " (" . $data_transaksi['nama_cabang'] . ")\\n\\nTransaksi: " . $transaksi_list . "\\n\\nSemua transaksi akan otomatis dikurangi dari setoran.";
                } else {
                    $message = "Transaksi berhasil dikembalikan ke CS pengirim: " . $data_transaksi['nama_karyawan'] . " (" . $data_transaksi['nama_cabang'] . ")";
                }
            } else {
                throw new Exception("Gagal mengupdate status transaksi");
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error kembalikan ke CS: " . $e->getMessage();
            error_log("Kembalikan ke CS error: " . $e->getMessage());
        }
    } else {
        $error = "Transaksi tidak ditemukan atau bukan status selisih.";
    }
}

// Helper function to update setoran_keuangan_closing_kasir status
function updateSetoranKeuanganStatus($pdo, $kode_setoran, $kode_karyawan, $validation_columns_exist, $selisih_fisik_is_generated) {
    // Count total and validated transactions (termasuk yang dikembalikan ke CS)
    $sql_count_total = "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE kode_setoran = ? AND deposit_status IN ('Diterima Staff Keuangan', 'Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Dikembalikan ke CS')";
    $stmt_total = $pdo->prepare($sql_count_total);
    $stmt_total->execute([$kode_setoran]);
    $total_transaksi = $stmt_total->fetchColumn();

    $sql_count_validated = "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE kode_setoran = ? AND deposit_status IN ('Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Dikembalikan ke CS')";
    $stmt_validated = $pdo->prepare($sql_count_validated);
    $stmt_validated->execute([$kode_setoran]);
    $validated_transaksi = $stmt_validated->fetchColumn();

    if ($total_transaksi == $validated_transaksi && $total_transaksi > 0) {
        // Check berbagai status untuk menentukan status setoran
        $sql_count_selisih = "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE kode_setoran = ? AND deposit_status = 'Validasi Keuangan SELISIH'";
        $stmt_selisih = $pdo->prepare($sql_count_selisih);
        $stmt_selisih->execute([$kode_setoran]);
        $selisih_count = $stmt_selisih->fetchColumn();
        
        $sql_count_dikembalikan = "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE kode_setoran = ? AND deposit_status = 'Dikembalikan ke CS'";
        $stmt_dikembalikan = $pdo->prepare($sql_count_dikembalikan);
        $stmt_dikembalikan->execute([$kode_setoran]);
        $dikembalikan_count = $stmt_dikembalikan->fetchColumn();

        // Tentukan status berdasarkan prioritas: Dikembalikan > Selisih > OK
        if ($dikembalikan_count > 0) {
            $setoran_status = 'Ada yang Dikembalikan ke CS';
        } elseif ($selisih_count > 0) {
            $setoran_status = 'Validasi Keuangan SELISIH';
        } else {
            $setoran_status = 'Validasi Keuangan OK';
        }

        $stmt_sum = $pdo->prepare("SELECT kode_transaksi, kode_setoran, kode_cabang, tanggal_transaksi, setoran_real, jumlah_diterima_fisik
                                   FROM kasir_transactions_closing_kasir
                                   WHERE kode_setoran = ?");
        $stmt_sum->execute([$kode_setoran]);
        $sum_rows = $stmt_sum->fetchAll(PDO::FETCH_ASSOC);

        $total_diterima = 0.0;
        $total_selisih = 0.0;

        foreach ($sum_rows as $row) {
            $diterima = isset($row['jumlah_diterima_fisik']) && $row['jumlah_diterima_fisik'] !== null
                ? (float)$row['jumlah_diterima_fisik']
                : (float)$row['setoran_real'];

            $borrowed_amount = getClosingBorrowedAmount(
                $pdo,
                $row['kode_transaksi'] ?? '',
                $row['kode_setoran'] ?? '',
                $row['kode_cabang'] ?? '',
                $row['tanggal_transaksi'] ?? ''
            );

            $expected_amount = (isClosingTransaction($row['kode_transaksi'] ?? '') || $borrowed_amount > 0)
                ? calculateExpectedPhysicalAmount($row['setoran_real'] ?? 0, $borrowed_amount)
                : (float)($row['setoran_real'] ?? 0);

            $total_diterima += $diterima;
            $total_selisih += normalizeSelisih($diterima - $expected_amount);
        }

        $sum_data = [
            'total_diterima' => $total_diterima,
            'total_selisih' => normalizeSelisih($total_selisih)
        ];

        $sql_update_setoran = "UPDATE setoran_keuangan_closing_kasir SET 
                              jumlah_diterima = ?, 
                              selisih_setoran = ?, 
                              status = ?, 
                              updated_by = ?, 
                              updated_at = NOW()
                              WHERE kode_setoran = ?
                              AND status IN ('Diterima Staff Keuangan', 'Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Ada yang Dikembalikan ke CS')";
        $stmt_update_setoran = $pdo->prepare($sql_update_setoran);
        $stmt_update_setoran->execute([
            $sum_data['total_diterima'], 
            $sum_data['total_selisih'], 
            $setoran_status,
            $kode_karyawan, 
            $kode_setoran
        ]);
    }
}
// Handle setor ke bank
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setor_bank'])) {
    // Debug logging
    error_log("SETOR BANK: POST data received");
    error_log("closing_ids: " . print_r($_POST['closing_ids'] ?? [], true));
    error_log("rekening_cabang_id: " . ($_POST['rekening_cabang_id'] ?? 'empty'));
    error_log("tanggal_setoran: " . ($_POST['tanggal_setoran'] ?? 'empty'));
    
    $closing_ids = $_POST['closing_ids'] ?? [];
    $rekening_cabang_id = $_POST['rekening_cabang_id'] ?? '';
    $tanggal_input = trim($_POST['tanggal_setoran'] ?? date('Y-m-d'));
    $tanggal_setor_db = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_input) ? $tanggal_input : date('Y-m-d');

    if (empty($closing_ids)) {
        $error = "Pilih transaksi closing untuk disetor.";
        error_log("SETOR BANK ERROR: No closing transactions selected");
    } elseif (empty($rekening_cabang_id)) {
        $error = "Pilih rekening cabang tujuan.";
        error_log("SETOR BANK ERROR: No rekening selected");
    } else {
        // Check if all selected closing transactions are valid and from same cabang as rekening
        $placeholders = array_fill(0, count($closing_ids), '?');
        
        // Get no_rekening from selected rekening to allow multiple cabang with same rekening
        // Handle multiple rekening IDs (comma separated)
        error_log("SETOR BANK: Processing rekening_cabang_id: " . $rekening_cabang_id);
        $rekening_ids = explode(',', $rekening_cabang_id);
        $first_rekening_id = $rekening_ids[0];
        error_log("SETOR BANK: First rekening ID: " . $first_rekening_id);
        
        $sql_get_rekening = "SELECT no_rekening FROM master_rekening_cabang_closing_kasir WHERE id = ?";
        $stmt_get_rekening = $pdo->prepare($sql_get_rekening);
        $stmt_get_rekening->execute([$first_rekening_id]);
        $target_no_rekening = $stmt_get_rekening->fetchColumn();
        
        if (!$target_no_rekening) {
            $error = "Rekening cabang tidak ditemukan.";
        } else {
            // Get all kode_cabang that use the same no_rekening
            $sql_get_cabang_list = "SELECT kode_cabang FROM master_rekening_cabang_closing_kasir WHERE no_rekening = ? AND status = 'active'";
            $stmt_get_cabang_list = $pdo->prepare($sql_get_cabang_list);
            $stmt_get_cabang_list->execute([$target_no_rekening]);
            $allowed_cabang = $stmt_get_cabang_list->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($allowed_cabang)) {
                $error = "Tidak ada cabang yang menggunakan rekening ini.";
            } else {
                // Check if all closing transactions are valid and from cabang that use the same no_rekening
                $cabang_placeholders = array_fill(0, count($allowed_cabang), '?');
                $sql_check = "SELECT COUNT(*) FROM kasir_transactions_closing_kasir kt
                             LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
                             WHERE kt.id IN (" . implode(',', $placeholders) . ") 
                             AND (kt.deposit_status != 'Validasi Keuangan OK' OR sk.kode_cabang NOT IN (" . implode(',', $cabang_placeholders) . "))";
                $stmt_check = $pdo->prepare($sql_check);
                $params_check = array_merge($closing_ids, $allowed_cabang);
                $stmt_check->execute($params_check);
            
                if ($stmt_check->fetchColumn() > 0) {
                    $error = "Tidak dapat setor ke bank. Pastikan semua transaksi closing dari cabang yang menggunakan rekening tujuan yang sama dan tidak ada selisih.";
                } else {
                $sql_check_status = "SELECT COUNT(*) FROM kasir_transactions_closing_kasir kt LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran WHERE kt.id IN (" . implode(',', $placeholders) . ") AND (kt.deposit_status NOT IN ('Validasi Keuangan OK') OR sk.status NOT IN ('Validasi Keuangan OK'))";
                $stmt_check_status = $pdo->prepare($sql_check_status);
                foreach ($closing_ids as $index => $id) {
                    $stmt_check_status->bindValue($index + 1, $id, PDO::PARAM_INT);
                }
                $stmt_check_status->execute();
                
                if ($stmt_check_status->fetchColumn() > 0) {
                    $error = "Semua transaksi closing harus dalam status 'Validasi Keuangan OK' sebelum disetor ke bank.";
                } else {
                    // PERBAIKAN: Gunakan jumlah yang sudah divalidasi, bukan setoran_real
                    $sql_total = "SELECT SUM(COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real)) FROM kasir_transactions_closing_kasir kt WHERE kt.id IN (" . implode(',', $placeholders) . ")";
                    $stmt_total = $pdo->prepare($sql_total);
                    foreach ($closing_ids as $index => $id) {
                        $stmt_total->bindValue($index + 1, $id, PDO::PARAM_INT);
                    }
                    $stmt_total->execute();
                    $total_setoran = $stmt_total->fetchColumn();

                    // Get setoran IDs from selected closing transactions
                    $sql_get_setoran_ids = "SELECT DISTINCT sk.id FROM kasir_transactions_closing_kasir kt 
                                           JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran 
                                           WHERE kt.id IN (" . implode(',', $placeholders) . ")";
                    $stmt_get_setoran_ids = $pdo->prepare($sql_get_setoran_ids);
                    foreach ($closing_ids as $index => $id) {
                        $stmt_get_setoran_ids->bindValue($index + 1, $id, PDO::PARAM_INT);
                    }
                    $stmt_get_setoran_ids->execute();
                    $setoran_ids = $stmt_get_setoran_ids->fetchAll(PDO::FETCH_COLUMN);

                    // Temporarily disable the trigger to avoid recursive update conflict
                    // Note: DDL statements (DROP/CREATE TRIGGER) implicitly commit transactions
                    try {
                        $pdo->exec("DROP TRIGGER IF EXISTS tr_update_setoran_status_backup");
                        $pdo->exec("CREATE TRIGGER tr_update_setoran_status_backup AFTER UPDATE ON kasir_transactions_closing_kasir FOR EACH ROW BEGIN END");
                        $pdo->exec("DROP TRIGGER IF EXISTS tr_update_setoran_status");
                        error_log("SETOR BANK: Trigger temporarily disabled");
                    } catch (Exception $trigger_drop_error) {
                        error_log("SETOR BANK WARNING: Could not drop trigger: " . $trigger_drop_error->getMessage());
                    }
                    
                    $pdo->beginTransaction();
                    try {
                        $year = date('Y');
                        // Gunakan MAX sequence agar tetap unik meski ada row yang pernah dihapus
                        $sql_count = "SELECT MAX(CAST(SUBSTRING_INDEX(kode_setoran, '-', -1) AS UNSIGNED))
                                      FROM setoran_ke_bank_closing_kasir
                                      WHERE kode_setoran LIKE ?";
                        $stmt_count = $pdo->prepare($sql_count);
                        $stmt_count->execute(["BANK-$year-%"]);
                        $max_seq = (int)$stmt_count->fetchColumn();
                        $count = $max_seq + 1;
                        $kode_setoran_bank = "BANK-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);

                        // Get rekening info - use first rekening ID from the selected group
                        $selected_rekening_ids = explode(',', $rekening_cabang_id);
                        $first_selected_id = $selected_rekening_ids[0];
                        
                        $sql_rekening = "SELECT * FROM master_rekening_cabang_closing_kasir WHERE id = ?";
                        $stmt_rekening = $pdo->prepare($sql_rekening);
                        $stmt_rekening->execute([$first_selected_id]);
                        $rekening_info = $stmt_rekening->fetch(PDO::FETCH_ASSOC);

                        $rekening_tujuan = $rekening_info['nama_bank'] . ' - ' . $rekening_info['no_rekening'] . ' (' . $rekening_info['nama_rekening'] . ')';

                        $sql_bank = "INSERT INTO setoran_ke_bank_closing_kasir (kode_setoran, tanggal_setoran, metode_setoran, rekening_tujuan, total_setoran, created_by)
                                     VALUES (?, ?, 'Tunai', ?, ?, ?)";
                        $stmt_bank = $pdo->prepare($sql_bank);
                        $stmt_bank->execute([$kode_setoran_bank, $tanggal_setor_db, $rekening_tujuan, $total_setoran, $kode_karyawan]);

                        $setoran_ke_bank_id = $pdo->lastInsertId();

                        $sql_detail = "INSERT INTO setoran_ke_bank_detail_closing_kasir (setoran_ke_bank_id, setoran_keuangan_id) VALUES (?, ?)";
                        $stmt_detail = $pdo->prepare($sql_detail);
                        foreach ($setoran_ids as $id) {
                            $stmt_detail->execute([$setoran_ke_bank_id, $id]);
                        }

                        // Fix A: Link pengambilan_setoran_closing_kasir -> setoran_ke_bank_closing_kasir
                        // Collect kasir_transaction IDs yang masuk ke setoran ini
                        if (!empty($closing_ids)) {
                            $ph_cids = implode(',', array_fill(0, count($closing_ids), '?'));
                            // Update pengambilan yang punya [Ref TRX: <id>] dalam keterangan
                            // (semua id closing yang baru saja disetor)
                            $sql_link_pga = "UPDATE pengambilan_setoran_closing_kasir
                                            SET id_setoran_bank = ?
                                            WHERE id_setoran_bank IS NULL
                                              AND keterangan REGEXP CONCAT('\\\\[Ref TRX:[^]]*\\\\b(', ?, ')[^]]*\\\\]')";
                            // Simpler approach: update one by one per closing_id
                            $stmt_link_pga = $pdo->prepare(
                                "UPDATE pengambilan_setoran_closing_kasir
                                 SET id_setoran_bank = ?,
                                     setoran_ke_bank_id = ?,
                                     status_setor_bank = 'disetor',
                                     tanggal_penyerahan_setor = COALESCE(tanggal_penyerahan_setor, ?),
                                     tanggal_riil_setor_bank = COALESCE(tanggal_riil_setor_bank, ?)
                                 WHERE id_setoran_bank IS NULL
                                   AND keterangan LIKE CONCAT('%[Ref TRX: ', ?, '%')"
                            );
                            foreach ($closing_ids as $cid) {
                                $stmt_link_pga->execute([
                                    $setoran_ke_bank_id,
                                    $setoran_ke_bank_id,
                                    $tanggal_setor_db,
                                    $tanggal_setor_db,
                                    $cid
                                ]);
                            }
                            error_log("Fix A: Linked pengambilan to setoran_ke_bank_id=$setoran_ke_bank_id for closing_ids: " . implode(',', $closing_ids));
                        }

                        // Now we can safely update both tables since trigger is disabled
                        // Create placeholders for setoran_ids
                        $setoran_placeholders = array_fill(0, count($setoran_ids), '?');
                        $sql_update_sk = "UPDATE setoran_keuangan_closing_kasir SET status = 'Sudah Disetor ke Bank', updated_by = ?, updated_at = NOW() 
                                         WHERE id IN (" . implode(',', $setoran_placeholders) . ")";
                        $stmt_update_sk = $pdo->prepare($sql_update_sk);
                        $stmt_update_sk->bindValue(1, $kode_karyawan, PDO::PARAM_STR);
                        foreach ($setoran_ids as $index => $id) {
                            $stmt_update_sk->bindValue($index + 2, $id, PDO::PARAM_INT);
                        }
                        $stmt_update_sk->execute();

                        // Update kasir_transactions_closing_kasir
                        $sql_update_trans = "UPDATE kasir_transactions_closing_kasir kt
                                            JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
                                            SET kt.deposit_status = 'Sudah Disetor ke Bank', kt.rekening_tujuan_id = ?, kt.validasi_by = ?
                                            WHERE sk.id IN (" . implode(',', $setoran_placeholders) . ")";
                        $stmt_update_trans = $pdo->prepare($sql_update_trans);
                        $stmt_update_trans->bindValue(1, $first_rekening_id, PDO::PARAM_INT);
                        $stmt_update_trans->bindValue(2, $kode_karyawan, PDO::PARAM_STR);
                        foreach ($setoran_ids as $index => $id) {
                            $stmt_update_trans->bindValue($index + 3, $id, PDO::PARAM_INT);
                        }
                        $stmt_update_trans->execute();

                        $pdo->commit();
                        $message = "Setoran ke bank berhasil dengan kode: " . $kode_setoran_bank . " | Rekening: " . $rekening_tujuan;
                        error_log("SETOR BANK SUCCESS: " . $message);
                        
                        // Recreate the original trigger after successful transaction
                        try {
                            $pdo->exec("DROP TRIGGER IF EXISTS tr_update_setoran_status");
                            
                            $trigger_sql = "CREATE TRIGGER tr_update_setoran_status
                                AFTER UPDATE ON kasir_transactions_closing_kasir
                                FOR EACH ROW
                                BEGIN
                                    DECLARE total_transaksi INT DEFAULT 0;
                                    DECLARE transaksi_validated INT DEFAULT 0;
                                    DECLARE transaksi_selisih INT DEFAULT 0;
                                    DECLARE transaksi_dikembalikan INT DEFAULT 0;
                                    DECLARE new_status VARCHAR(50);
                                    
                                    SELECT COUNT(*) INTO total_transaksi
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran 
                                    AND deposit_status IN ('Diterima Staff Keuangan', 'Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Dikembalikan ke CS');
                                    
                                    SELECT COUNT(*) INTO transaksi_validated
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran
                                    AND deposit_status IN ('Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Dikembalikan ke CS');
                                    
                                    SELECT COUNT(*) INTO transaksi_selisih
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran
                                    AND deposit_status = 'Validasi Keuangan SELISIH';
                                    
                                    SELECT COUNT(*) INTO transaksi_dikembalikan
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran
                                    AND deposit_status = 'Dikembalikan ke CS';
                                    
                                    IF total_transaksi = transaksi_validated AND total_transaksi > 0 THEN
                                        IF transaksi_dikembalikan > 0 THEN
                                            SET new_status = 'Ada yang Dikembalikan ke CS';
                                        ELSEIF transaksi_selisih > 0 THEN
                                            SET new_status = 'Validasi Keuangan SELISIH';
                                        ELSE
                                            SET new_status = 'Validasi Keuangan OK';
                                        END IF;
                                        
                                        UPDATE setoran_keuangan_closing_kasir 
                                        SET 
                                            status = new_status,
                                            updated_at = CURRENT_TIMESTAMP,
                                            updated_by = NEW.validasi_by
                                        WHERE kode_setoran = NEW.kode_setoran;
                                    END IF;
                                END";
                            $pdo->exec($trigger_sql);
                            error_log("SETOR BANK: Trigger recreated after successful transaction");
                        } catch (Exception $trigger_recreate_error) {
                            error_log("SETOR BANK WARNING: Failed to recreate trigger after success: " . $trigger_recreate_error->getMessage());
                        }
                    } catch (Exception $e) {
                        error_log("SETOR BANK EXCEPTION: " . $e->getMessage());
                        error_log("SETOR BANK TRACE: " . $e->getTraceAsString());
                        
                        // Only rollback if transaction is active
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                            error_log("SETOR BANK: Transaction rolled back");
                        } else {
                            error_log("SETOR BANK: No active transaction to rollback");
                        }
                        
                        $error = "Error: " . $e->getMessage();
                        error_log("SETOR BANK ERROR: " . $e->getMessage());
                        
                        // Always recreate trigger even on error (outside transaction)
                        try {
                            $pdo->exec("DROP TRIGGER IF EXISTS tr_update_setoran_status");
                            
                            $trigger_sql = "CREATE TRIGGER tr_update_setoran_status
                                AFTER UPDATE ON kasir_transactions_closing_kasir
                                FOR EACH ROW
                                BEGIN
                                    DECLARE total_transaksi INT DEFAULT 0;
                                    DECLARE transaksi_validated INT DEFAULT 0;
                                    DECLARE transaksi_selisih INT DEFAULT 0;
                                    DECLARE transaksi_dikembalikan INT DEFAULT 0;
                                    DECLARE new_status VARCHAR(50);
                                    
                                    SELECT COUNT(*) INTO total_transaksi
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran 
                                    AND deposit_status IN ('Diterima Staff Keuangan', 'Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Dikembalikan ke CS');
                                    
                                    SELECT COUNT(*) INTO transaksi_validated
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran
                                    AND deposit_status IN ('Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Dikembalikan ke CS');
                                    
                                    SELECT COUNT(*) INTO transaksi_selisih
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran
                                    AND deposit_status = 'Validasi Keuangan SELISIH';
                                    
                                    SELECT COUNT(*) INTO transaksi_dikembalikan
                                    FROM kasir_transactions_closing_kasir
                                    WHERE kode_setoran = NEW.kode_setoran
                                    AND deposit_status = 'Dikembalikan ke CS';
                                    
                                    IF total_transaksi = transaksi_validated AND total_transaksi > 0 THEN
                                        IF transaksi_dikembalikan > 0 THEN
                                            SET new_status = 'Ada yang Dikembalikan ke CS';
                                        ELSEIF transaksi_selisih > 0 THEN
                                            SET new_status = 'Validasi Keuangan SELISIH';
                                        ELSE
                                            SET new_status = 'Validasi Keuangan OK';
                                        END IF;
                                        
                                        UPDATE setoran_keuangan_closing_kasir 
                                        SET 
                                            status = new_status,
                                            updated_at = CURRENT_TIMESTAMP,
                                            updated_by = NEW.validasi_by
                                        WHERE kode_setoran = NEW.kode_setoran;
                                    END IF;
                                END";
                            $pdo->exec($trigger_sql);
                            error_log("SETOR BANK ERROR RECOVERY: Trigger recreated after error");
                        } catch (Exception $trigger_error) {
                            error_log("SETOR BANK CRITICAL: Failed to recreate trigger after error: " . $trigger_error->getMessage());
                        }
                    }
                }
                }
            }
        }
    }
}

// ===== HANDLER: AMBIL SEBAGIAN UNTUK PENGADAAN =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ambil_sebagian_pengadaan'])) {
    $closing_ids_str = $_POST['closing_ids_ambil'] ?? '';
    $closing_ids = array_values(array_filter(array_map('intval', explode(',', $closing_ids_str))));
    $tanggal_setoran_input = trim($_POST['tanggal_setoran_ambil'] ?? date('Y-m-d'));
    $nominal_diambil = (float)($_POST['nominal_diambil'] ?? 0);
    $nominal_sisa = (float)($_POST['nominal_sisa'] ?? 0);
    $kode_cabang_penerima = trim($_POST['kode_cabang_penerima'] ?? '');
    $keterangan_ambil = htmlspecialchars(trim($_POST['keterangan_ambil'] ?? ''), ENT_QUOTES);
    $tanggal_setor_bank_input = trim($_POST['tanggal_setor_bank_ambil'] ?? '');
    $tanggal_penyerahan_setor = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_setor_bank_input) ? $tanggal_setor_bank_input : null;

    // P2: sisa boleh 0 — validasi hanya nominal_diambil > 0 dan cabang penerima wajib
    if (empty($closing_ids) || $nominal_diambil <= 0) {
        $error = "Data tidak lengkap untuk proses pengambilan.";
    } elseif (empty($kode_cabang_penerima)) {
        $error = "Cabang penerima wajib dipilih.";
    } else {
        try {
            $pdo->beginTransaction();

            $tanggal_perencanaan_setor = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_setoran_input) ? $tanggal_setoran_input : date('Y-m-d');

            // Ambil data cabang dari transaksi setoran yang dipilih
            $stmt_asal = $pdo->prepare("SELECT kode_cabang, nama_cabang FROM kasir_transactions_closing_kasir WHERE id = ?");
            $stmt_asal->execute([$closing_ids[0]]);
            $asal_data = $stmt_asal->fetch(PDO::FETCH_ASSOC);

            // Rekening peminjam = rekening aktif dari cabang asal setoran
            $kode_cabang_keu = $asal_data['kode_cabang'] ?? $kode_cabang_aktif ?? '';
            $nama_cabang_keu = $asal_data['nama_cabang'] ?? $nama_cabang_aktif ?? '';

            // Dapatkan rekening peminjam
            $rekening_peminjam = getCabangPenerimaRekening($pdo, $kode_cabang_keu);
            if (!$rekening_peminjam || empty($rekening_peminjam['no_rekening'])) {
                throw new RuntimeException('Rekening cabang asal setoran tidak ditemukan atau belum terdaftar.');
            }

            $rekening_penerima = getCabangPenerimaRekening($pdo, $kode_cabang_penerima);
            if (!$rekening_penerima || empty($rekening_penerima['no_rekening'])) {
                throw new RuntimeException('Rekening cabang penerima belum terdaftar.');
            }

            $klasifikasi = classifyPengambilan($rekening_peminjam['no_rekening'], $rekening_penerima['no_rekening']);
            $jenis_rekening = $klasifikasi === 'internal' ? 'internal' : 'eksternal';
            // P1/P10: lock tidak diberlakukan lagi — canProcessSetoran tidak dipanggil

            $kode_pengambilan = generateKodePengambilan($pdo);

            // P11: internal dan eksternal sama-sama butuh input kasir (P3)
            if ($klasifikasi === 'internal') {
                $status_pengambilan = 'proses';
                $status_setor_bank  = 'belum';
                $v_nominal = 0; $v_notrx = 0; $v_closing = 0; $v_mutasi = 0;
            } else {
                $status_pengambilan = 'hutang';
                $status_setor_bank  = 'belum';
                $v_nominal = 0; $v_notrx = 0; $v_closing = 0; $v_mutasi = 0;
            }
            
            // Tambahkan relasi ID transaksi ke dalam keterangan agar ada rekam jejak
            $keterangan_ambil_simpan = $keterangan_ambil . " [Ref TRX: " . implode(',', $closing_ids) . "]";

            $stmt_ins = $pdo->prepare("INSERT INTO pengambilan_setoran_closing_kasir
                (kode_pengambilan, nominal_diambil, nominal_sisa, jenis_rekening, klasifikasi,
                 no_rekening_peminjam, no_rekening_penerima, kode_cabang_penerima, tanggal_perencanaan_setor,
                 tanggal_penyerahan_setor,
                 status, status_setor_bank, verified_nominal_kas_masuk, verified_notrx_kas_masuk,
                 verified_cabang_penerima_sudah_closing, verified_mutasi_bank,
                 kode_cabang_keuangan, nama_cabang_keuangan, kode_karyawan_input, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->execute([
                $kode_pengambilan, $nominal_diambil, $nominal_sisa,
                $jenis_rekening, $klasifikasi,
                normalizePengambilanNoRekening($rekening_peminjam['no_rekening']),
                normalizePengambilanNoRekening($rekening_penerima['no_rekening']),
                $kode_cabang_penerima, $tanggal_perencanaan_setor,
                $tanggal_penyerahan_setor,
                $status_pengambilan, $status_setor_bank,
                $v_nominal, $v_notrx, $v_closing, $v_mutasi,
                $kode_cabang_keu, $nama_cabang_keu, $kode_karyawan, $keterangan_ambil_simpan
            ]);

            // DEDUKSI PROPORSIONAL KASIR_TRANSACTIONS & SETORAN_KEUANGAN
            $sisa_deduksi = (float)$nominal_diambil;
            
            $placeholders_deduksi = implode(',', array_fill(0, count($closing_ids), '?'));
            $stmt_get_trx = $pdo->prepare("SELECT id, kode_setoran, COALESCE(jumlah_diterima_fisik, setoran_real) as nominal_aktif FROM kasir_transactions_closing_kasir WHERE id IN ($placeholders_deduksi) ORDER BY id ASC FOR UPDATE");
            $stmt_get_trx->execute($closing_ids);
            $trx_list = $stmt_get_trx->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt_update_trx = $pdo->prepare("UPDATE kasir_transactions_closing_kasir SET jumlah_diterima_fisik = ? WHERE id = ?");
            $stmt_update_sk = $pdo->prepare("UPDATE setoran_keuangan_closing_kasir SET jumlah_diterima = COALESCE(jumlah_diterima, jumlah_setoran) - ?, jumlah_setoran = jumlah_setoran - ? WHERE kode_setoran = ?");
            
            foreach ($trx_list as $trx) {
                if ($sisa_deduksi <= 0) break;
                
                $nominal_aktif = (float)$trx['nominal_aktif'];
                if ($nominal_aktif <= 0) continue;
                
                $potong = 0;
                if ($nominal_aktif >= $sisa_deduksi) {
                    $potong = $sisa_deduksi;
                    $nominal_baru = $nominal_aktif - $potong;
                    $stmt_update_trx->execute([$nominal_baru, $trx['id']]);
                    $sisa_deduksi = 0;
                } else {
                    $potong = $nominal_aktif;
                    $stmt_update_trx->execute([0, $trx['id']]);
                    $sisa_deduksi -= $potong;
                }
                
                if ($potong > 0) {
                    $stmt_update_sk->execute([$potong, $potong, $trx['kode_setoran']]);
                }
            }
            
            if ($sisa_deduksi > 0) {
                throw new RuntimeException("Saldo setoran tidak mencukupi untuk dipotong sebesar Rp " . number_format($nominal_diambil, 0, ',', '.'));
            }

            $pdo->commit();
            error_log("AMBIL SEBAGIAN OK: kode=$kode_pengambilan, diambil=$nominal_diambil, sisa=$nominal_sisa");
            $msg_redirect = urlencode("Pengambilan berhasil! Kode: $kode_pengambilan. Klasifikasi: " . strtoupper($klasifikasi) . ". Nominal Rp " . number_format($nominal_diambil, 0, ',', '.') . " ditampung di Histori Pengambilan Dana.");
            header("Location: ?tab=histori_pengambilan_dana&message=" . $msg_redirect);
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Error proses pengambilan: " . $e->getMessage();
            error_log("AMBIL SEBAGIAN ERROR: " . $e->getMessage());
        }
    }
}
// ===== END HANDLER AMBIL SEBAGIAN =====

// Initialize tab variable dengan default value
$tab = $_GET['tab'] ?? $_POST['tab_filter'] ?? 'terima';

// Fetch setoran data with filters - IMPROVED with closing transaction handling
$tanggal_awal = $_POST['tanggal_awal'] ?? $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_POST['tanggal_akhir'] ?? $_GET['tanggal_akhir'] ?? '';
$cabang = $_POST['cabang'] ?? $_GET['cabang'] ?? 'all';
$status = $_POST['status'] ?? $_GET['status'] ?? 'all';
$status_filter = $_POST['status_filter'] ?? $_GET['status_filter'] ?? 'all';
$rekening_filter = $_POST['rekening_filter'] ?? $_GET['rekening_filter'] ?? 'all';
$pengambilan_scope = $_POST['pengambilan_scope'] ?? $_GET['pengambilan_scope'] ?? 'all';
$pengambilan_status = $_POST['pengambilan_status'] ?? $_GET['pengambilan_status'] ?? 'all';

// Debug logging
error_log("Rekening filter: " . $rekening_filter);
error_log("Tab: " . $tab);

$sql_setoran = "
    SELECT sk.*, COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
    FROM setoran_keuangan_closing_kasir sk
    LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
    WHERE 1=1";

$params = [];

if ($tab == 'terima') {
    $sql_setoran .= " AND sk.status = 'Sedang Dibawa Kurir'";
} elseif ($tab == 'validasi') {
    // IMPROVED query to show closing transaction details
    $sql_setoran = "
        SELECT 
            kt.*, 
            COALESCE(NULLIF(sk.kode_cabang, ''), NULLIF(kt.kode_cabang, '')) AS resolved_kode_cabang,
            COALESCE(NULLIF(sk.nama_cabang, ''), NULLIF(kt.nama_cabang, '')) AS resolved_nama_cabang,
            sk.nama_cabang AS nama_cabang_setoran, 
            sk.tanggal_setoran, 
            sk.nama_pengantar, 
            COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan,
            CASE 
                WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                WHEN EXISTS (
                    SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                    WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                ) THEN 'DARI CLOSING'
                WHEN EXISTS (
                    SELECT 1
                    FROM pemasukan_kasir_closing_kasir pkx
                    JOIN kasir_transactions_closing_kasir ktx ON ktx.kode_transaksi = pkx.kode_transaksi
                    WHERE ktx.kode_setoran = kt.kode_setoran
                      AND ktx.kode_cabang = kt.kode_cabang
                      AND (
                           pkx.nomor_transaksi_closing = kt.kode_transaksi
                           OR (
                                (pkx.nomor_transaksi_closing IS NULL OR pkx.nomor_transaksi_closing = '')
                                AND pkx.kode_akun = 'DRCLSG'
                                AND UPPER(pkx.keterangan_transaksi) LIKE '%CLOSING%'
                           )
                      )
                ) THEN 'DARI CLOSING'
                ELSE 'TRANSAKSI BIASA'
            END as jenis_transaksi,
            -- TAMBAHAN: Informasi closing gabungan
            pk.jumlah as jumlah_pemasukan_closing,
            pk.keterangan_transaksi as keterangan_closing,
            (SELECT COUNT(*) FROM kasir_transactions_closing_kasir kt2 
             WHERE kt2.kode_setoran = kt.kode_setoran 
             AND (kt2.kode_transaksi LIKE '%CLOSING%' OR kt2.kode_transaksi LIKE '%CLO%'
                  OR EXISTS (
                      SELECT 1 FROM pemasukan_kasir_closing_kasir pk2 
                      WHERE pk2.nomor_transaksi_closing = kt2.kode_transaksi
                  ))) as total_closing_in_setoran,
            (
                SELECT COALESCE(SUM(pk3.jumlah), 0)
                FROM pemasukan_kasir_closing_kasir pk3
                JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                WHERE kt3.kode_setoran = kt.kode_setoran
                  AND kt3.kode_cabang = kt.kode_cabang
                  AND (
                       pk3.nomor_transaksi_closing = kt.kode_transaksi
                       OR (
                            (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                            AND pk3.kode_akun = 'DRCLSG'
                            AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                            AND (
                                 UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])0?', DAY(kt.tanggal_transaksi), '[[:space:]]+', UPPER(DATE_FORMAT(kt.tanggal_transaksi, '%b')), '([[:space:]]+', DATE_FORMAT(kt.tanggal_transaksi, '%Y'), ')?([^0-9]|$)')
                                 OR UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])TGL[[:space:]]*0?', DAY(kt.tanggal_transaksi), '([^0-9]|$)')
                            )
                       )
                  )
            ) AS total_closing_borrowed,
            (
                COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) - (
                    SELECT COALESCE(SUM(pk3.jumlah), 0)
                    FROM pemasukan_kasir_closing_kasir pk3
                    JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                    WHERE kt3.kode_setoran = kt.kode_setoran
                      AND kt3.kode_cabang = kt.kode_cabang
                      AND (
                           pk3.nomor_transaksi_closing = kt.kode_transaksi
                           OR (
                                (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                                AND pk3.kode_akun = 'DRCLSG'
                                AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                                AND (
                                     UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])0?', DAY(kt.tanggal_transaksi), '[[:space:]]+', UPPER(DATE_FORMAT(kt.tanggal_transaksi, '%b')), '([[:space:]]+', DATE_FORMAT(kt.tanggal_transaksi, '%Y'), ')?([^0-9]|$)')
                                     OR UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])TGL[[:space:]]*0?', DAY(kt.tanggal_transaksi), '([^0-9]|$)')
                                )
                           )
                      )
                )
            ) AS expected_physical
        FROM kasir_transactions_closing_kasir kt
        LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
        LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
        LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
        WHERE kt.deposit_status = 'Diterima Staff Keuangan'";
} elseif ($tab == 'validasi_selisih') {
    // IMPROVED query to show closing transaction details for selisih
    $sql_setoran = "
        SELECT 
            kt.*, 
            COALESCE(NULLIF(sk.kode_cabang, ''), NULLIF(kt.kode_cabang, '')) AS resolved_kode_cabang,
            COALESCE(NULLIF(sk.nama_cabang, ''), NULLIF(kt.nama_cabang, '')) AS resolved_nama_cabang,
            sk.nama_cabang AS nama_cabang_setoran, 
            sk.tanggal_setoran, 
            sk.nama_pengantar, 
            COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan,
            CASE 
                WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                WHEN EXISTS (
                    SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                    WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                ) THEN 'DARI CLOSING'
                WHEN EXISTS (
                    SELECT 1
                    FROM pemasukan_kasir_closing_kasir pkx
                    JOIN kasir_transactions_closing_kasir ktx ON ktx.kode_transaksi = pkx.kode_transaksi
                    WHERE ktx.kode_setoran = kt.kode_setoran
                      AND ktx.kode_cabang = kt.kode_cabang
                      AND (
                           pkx.nomor_transaksi_closing = kt.kode_transaksi
                           OR (
                                (pkx.nomor_transaksi_closing IS NULL OR pkx.nomor_transaksi_closing = '')
                                AND pkx.kode_akun = 'DRCLSG'
                                AND UPPER(pkx.keterangan_transaksi) LIKE '%CLOSING%'
                           )
                      )
                ) THEN 'DARI CLOSING'
                ELSE 'TRANSAKSI BIASA'
            END as jenis_transaksi,
            -- TAMBAHAN: Informasi closing gabungan
            pk.jumlah as jumlah_pemasukan_closing,
            pk.keterangan_transaksi as keterangan_closing,
            (SELECT COUNT(*) FROM kasir_transactions_closing_kasir kt2 
             WHERE kt2.kode_setoran = kt.kode_setoran 
             AND (kt2.kode_transaksi LIKE '%CLOSING%' OR kt2.kode_transaksi LIKE '%CLO%'
                  OR EXISTS (
                      SELECT 1 FROM pemasukan_kasir_closing_kasir pk2 
                      WHERE pk2.nomor_transaksi_closing = kt2.kode_transaksi
                  ))) as total_closing_in_setoran,
            (
                SELECT COALESCE(SUM(pk3.jumlah), 0)
                FROM pemasukan_kasir_closing_kasir pk3
                JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                WHERE kt3.kode_setoran = kt.kode_setoran
                  AND kt3.kode_cabang = kt.kode_cabang
                  AND (
                       pk3.nomor_transaksi_closing = kt.kode_transaksi
                       OR (
                            (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                            AND pk3.kode_akun = 'DRCLSG'
                            AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                            AND (
                                 UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])0?', DAY(kt.tanggal_transaksi), '[[:space:]]+', UPPER(DATE_FORMAT(kt.tanggal_transaksi, '%b')), '([[:space:]]+', DATE_FORMAT(kt.tanggal_transaksi, '%Y'), ')?([^0-9]|$)')
                                 OR UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])TGL[[:space:]]*0?', DAY(kt.tanggal_transaksi), '([^0-9]|$)')
                            )
                       )
                  )
            ) AS total_closing_borrowed,
            (
                COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) - (
                    SELECT COALESCE(SUM(pk3.jumlah), 0)
                    FROM pemasukan_kasir_closing_kasir pk3
                    JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                    WHERE kt3.kode_setoran = kt.kode_setoran
                      AND kt3.kode_cabang = kt.kode_cabang
                      AND (
                           pk3.nomor_transaksi_closing = kt.kode_transaksi
                           OR (
                                (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                                AND pk3.kode_akun = 'DRCLSG'
                                AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                                AND (
                                     UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])0?', DAY(kt.tanggal_transaksi), '[[:space:]]+', UPPER(DATE_FORMAT(kt.tanggal_transaksi, '%b')), '([[:space:]]+', DATE_FORMAT(kt.tanggal_transaksi, '%Y'), ')?([^0-9]|$)')
                                     OR UPPER(pk3.keterangan_transaksi) REGEXP CONCAT('(^|[^0-9])TGL[[:space:]]*0?', DAY(kt.tanggal_transaksi), '([^0-9]|$)')
                                )
                           )
                      )
                )
            ) AS expected_physical
        FROM kasir_transactions_closing_kasir kt
        LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
        LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
        LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
        WHERE kt.deposit_status = 'Validasi Keuangan SELISIH'";
} elseif ($tab == 'dikembalikan_cs') {
    // Tab untuk menampilkan dan mengelola transaksi yang dikembalikan ke CS
    $sql_setoran = "
        SELECT DISTINCT
            kt.id,
            kt.kode_transaksi,
            kt.tanggal_transaksi,
            kt.tanggal_closing,
            kt.setoran_real,
            kt.omset,
            kt.data_setoran,
            kt.deposit_status,
            kt.kode_setoran,
            kt.nama_cabang,
            kt.kode_karyawan as kt_kode_karyawan,
            kt.catatan_validasi,
            kt.validasi_at,
            kt.validasi_by,
            sk.nama_pengantar,
            sk.tanggal_setoran,
            sk.kode_karyawan as sk_kode_karyawan,
            COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan,
            CASE
                WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                WHEN EXISTS (
                    SELECT 1 FROM pemasukan_kasir_closing_kasir pk
                    WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                ) THEN 'DARI CLOSING'
                ELSE 'TRANSAKSI BIASA'
            END as jenis_transaksi,
            -- Informasi pemasukan terkait untuk closing
            pk.jumlah as jumlah_pemasukan_closing,
            pk.keterangan_transaksi as keterangan_closing
        FROM kasir_transactions_closing_kasir kt
        LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
        LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
        LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
        WHERE kt.deposit_status = 'Dikembalikan ke CS'
        AND kt.status = 'end proses'";
} elseif ($tab == 'setor_bank') {
    // Filter siap setor bank by status, show individual closing transactions instead of grouped setoran

     $locked_closing_ids = [];
     $pengambilan_info_by_closing_id = [];
     try {
         $stmt_locked = $pdo->query("SELECT keterangan FROM pengambilan_setoran_closing_kasir
                                     WHERE parent_kode_pengambilan IS NULL
                                       AND status != 'selesai'
                                       AND COALESCE(nominal_sisa, 0) > 0
                                       AND COALESCE(verified_cabang_penerima_sudah_closing, 0) = 0");
         while ($row_locked = $stmt_locked->fetch(PDO::FETCH_ASSOC)) {
             $locked_closing_ids = array_merge($locked_closing_ids, extractRefTrxIdsFromKeterangan($row_locked['keterangan'] ?? ''));
         }
         $locked_closing_ids = array_values(array_unique(array_filter($locked_closing_ids, function ($v) { return (int)$v > 0; })));

         $stmt_unlocked = $pdo->query("SELECT ps.kode_pengambilan,
                                              ps.nominal_diambil,
                                              ps.kode_cabang_penerima,
                                              COALESCE(c.nama_cabang, '') AS nama_cabang_penerima,
                                              ps.keterangan,
                                              ps.created_at
                                       FROM pengambilan_setoran_closing_kasir ps
                                       LEFT JOIN tbcabang c ON c.cabang_ref_kode = ps.kode_cabang_penerima
                                       WHERE ps.parent_kode_pengambilan IS NULL
                                         AND COALESCE(ps.nominal_diambil, 0) > 0
                                         AND ps.keterangan LIKE '%[Ref TRX:%'
                                         AND COALESCE(ps.verified_cabang_penerima_sudah_closing, 0) = 1
                                         AND ps.created_at >= (NOW() - INTERVAL 90 DAY)");

         while ($ps = $stmt_unlocked->fetch(PDO::FETCH_ASSOC)) {
             $refIds = extractRefTrxIdsFromKeterangan($ps['keterangan'] ?? '');
             if (empty($refIds)) continue;
             foreach ($refIds as $cid) {
                 if (!isset($pengambilan_info_by_closing_id[$cid])) {
                     $pengambilan_info_by_closing_id[$cid] = [];
                 }
                 $pengambilan_info_by_closing_id[$cid][] = [
                     'kode_pengambilan' => $ps['kode_pengambilan'] ?? '',
                     'nominal_diambil' => (float)($ps['nominal_diambil'] ?? 0),
                     'kode_cabang_penerima' => $ps['kode_cabang_penerima'] ?? '',
                     'nama_cabang_penerima' => $ps['nama_cabang_penerima'] ?? '',
                     'created_at' => $ps['created_at'] ?? null,
                 ];
             }
         }
     } catch (Throwable $e) {
         error_log('Failed to load locked closing ids: ' . $e->getMessage());
         $locked_closing_ids = [];
         $pengambilan_info_by_closing_id = [];
     }

    $sql_setoran = "
        SELECT
            kt.id,
            kt.kode_transaksi,
            kt.tanggal_transaksi,
            kt.tanggal_closing,
            kt.jam_closing,
            kt.setoran_real,
            COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) as saldo_siap_setor,
            kt.omset,
            kt.data_setoran,
            kt.deposit_status,
            kt.kode_setoran,
            kt.kasir_asal,
            kt.nama_cabang,
            COALESCE(NULLIF(sk.kode_cabang, ''), NULLIF(kt.kode_cabang, '')) as resolved_kode_cabang,
            COALESCE(NULLIF(sk.nama_cabang, ''), NULLIF(kt.nama_cabang, '')) as resolved_nama_cabang,
            kt.kode_karyawan as kt_kode_karyawan,
            sk.kode_setoran as setoran_kode,
            sk.tanggal_setoran,
            sk.jumlah_setoran,
            sk.nama_pengantar,
            sk.status as setoran_status,
            sk.kode_karyawan,
            sk.kode_cabang,
            sk.nama_cabang as sk_nama_cabang,
            COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
        FROM kasir_transactions_closing_kasir kt
        LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
        LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
        WHERE sk.status = 'Validasi Keuangan OK'
        AND kt.status = 'end proses'
        AND kt.deposit_status IN ('Validasi Keuangan OK')
        AND COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) > 0";

     if (!empty($locked_closing_ids)) {
         $placeholders_locked = implode(',', array_fill(0, count($locked_closing_ids), '?'));
         $sql_setoran .= " AND kt.id NOT IN ($placeholders_locked)";
         $params = array_merge($params, $locked_closing_ids);
     }

    // Add rekening filter for setor_bank - filter by cabang matching rekening with same no_rekening
    if ($rekening_filter !== 'all' && !empty($rekening_filter)) {
        // Handle multiple rekening IDs (comma separated)
        $rekening_ids = explode(',', $rekening_filter);
        $placeholders = array_fill(0, count($rekening_ids), '?');
        $sql_setoran .= " AND COALESCE(NULLIF(sk.kode_cabang, ''), NULLIF(kt.kode_cabang, '')) IN (
            SELECT kode_cabang FROM master_rekening_cabang_closing_kasir 
            WHERE id IN (" . implode(',', $placeholders) . ") AND status = 'active'
        )";
        $params = array_merge($params, $rekening_ids);
        error_log("Adding rekening filter with IDs: " . $rekening_filter);
    }
}

// Apply filters
if ($tanggal_awal && $tanggal_akhir) {
    if ($tab == 'validasi' || $tab == 'validasi_selisih') {
        $sql_setoran .= " AND sk.tanggal_setoran BETWEEN ? AND ?";
    } elseif ($tab == 'dikembalikan_cs') {
        $sql_setoran .= " AND kt.validasi_at BETWEEN ? AND ?";
    } elseif ($tab == 'setor_bank') {
        // Filter Setor Bank by tanggal_setoran to ensure all dates in range are shown
        $sql_setoran .= " AND sk.tanggal_setoran BETWEEN ? AND ?";
    } else {
        $sql_setoran .= " AND sk.tanggal_setoran BETWEEN ? AND ?";
    }
    $params[] = $tanggal_awal;
    $params[] = $tanggal_akhir;
}

if ($cabang !== 'all') {
    if ($tab == 'validasi' || $tab == 'validasi_selisih') {
        $sql_setoran .= " AND COALESCE(NULLIF(sk.nama_cabang, ''), NULLIF(kt.nama_cabang, '')) = ?";
    } elseif ($tab == 'dikembalikan_cs') {
        $sql_setoran .= " AND kt.nama_cabang = ?";
    } elseif ($tab == 'setor_bank') {
        $sql_setoran .= " AND COALESCE(NULLIF(sk.nama_cabang, ''), NULLIF(kt.nama_cabang, '')) = ?";
    } else {
        $sql_setoran .= " AND sk.nama_cabang = ?";
    }
    $params[] = $cabang;
}



// Add ORDER BY
if ($tab == 'validasi' || $tab == 'validasi_selisih') {
    $sql_setoran .= " ORDER BY
        CASE
            WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 0
            WHEN EXISTS (
                SELECT 1 FROM pemasukan_kasir_closing_kasir pk3
                WHERE pk3.nomor_transaksi_closing = kt.kode_transaksi
            ) THEN 0
            ELSE 1
        END,
        sk.tanggal_setoran DESC, kt.tanggal_transaksi DESC";

      $stmt = $pdo->prepare($sql_setoran);
      $stmt->execute($params);
      $data_setoran = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($tab == 'dikembalikan_cs') {
    $sql_setoran .= " ORDER BY
        CASE
            WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 0
            WHEN EXISTS (
                SELECT 1 FROM pemasukan_kasir_closing_kasir pk3
                WHERE pk3.nomor_transaksi_closing = kt.kode_transaksi
            ) THEN 0
            ELSE 1
        END,
        kt.validasi_at DESC, kt.tanggal_transaksi DESC";
} elseif ($tab == 'setor_bank') {
    // Order by tanggal closing for setor_bank tab (per closing transaction)
    $sql_setoran .= " ORDER BY kt.tanggal_closing DESC, kt.jam_closing DESC";
} else {
    $sql_setoran .= " ORDER BY sk.tanggal_setoran DESC";
}

// Execute query
$stmt_setoran = $pdo->prepare($sql_setoran);
$stmt_setoran->execute($params);
$setoran_list = $stmt_setoran->fetchAll(PDO::FETCH_ASSOC);

// Debug output
error_log("Query: " . $sql_setoran);
error_log("Params: " . print_r($params, true));
error_log("Result count: " . count($setoran_list));

if (($tab == 'validasi' || $tab == 'validasi_selisih') && !empty($setoran_list)) {
    foreach ($setoran_list as &$row) {
        $row = enrichClosingTransactionRow($pdo, $row);
    }
    unset($row);
} elseif ($tab == 'setor_bank' && !empty($setoran_list)) {
    foreach ($setoran_list as &$row) {
        $row = enrichClosingTransactionRow($pdo, $row);
    }
    unset($row);
}

$sql_cabang = "SELECT DISTINCT nama_cabang FROM setoran_keuangan_closing_kasir WHERE nama_cabang IS NOT NULL AND nama_cabang != '' ORDER BY nama_cabang";
$stmt_cabang = $pdo->query($sql_cabang);
$cabang_list = $stmt_cabang->fetchAll(PDO::FETCH_COLUMN);

// Get rekening list for dropdown grouped by no_rekening with all cabang names
$sql_rekening = "
    SELECT 
        mr.no_rekening,
        mr.nama_bank,
        MAX(mr.nama_rekening) as nama_rekening,
        MAX(mr.jenis_rekening) as jenis_rekening,
        GROUP_CONCAT(DISTINCT CONCAT(c.nama_cabang, '|', mr.id) ORDER BY c.nama_cabang SEPARATOR ';;') as cabang_info,
        GROUP_CONCAT(DISTINCT mr.id ORDER BY c.nama_cabang) as rekening_ids
    FROM master_rekening_cabang_closing_kasir mr
    JOIN tbcabang c ON c.cabang_ref_kode = mr.kode_cabang
    WHERE mr.status = 'active' 
    GROUP BY mr.no_rekening, mr.nama_bank
    ORDER BY mr.nama_bank, mr.no_rekening
";

// Also keep individual rekening list for form dropdown
$sql_rekening_individual = "
    SELECT mr.*, c.nama_cabang 
    FROM master_rekening_cabang_closing_kasir mr
    JOIN tbcabang c ON c.cabang_ref_kode = mr.kode_cabang
    WHERE mr.status = 'active' 
    ORDER BY c.nama_cabang, mr.nama_bank
";
$stmt_rekening = $pdo->query($sql_rekening);
$rekening_list = $stmt_rekening->fetchAll(PDO::FETCH_ASSOC);

$stmt_rekening_individual = $pdo->query($sql_rekening_individual);
$rekening_individual_list = $stmt_rekening_individual->fetchAll(PDO::FETCH_ASSOC);

// Query results ready for dropdown display
$cabang_penerima_options = getCabangPenerimaOptions($pdo);
$blocked_cabang_map = getPengambilanBlockedCabangMap($pdo);
// P1/P10: lock tidak diberlakukan lagi — canProcessSetoran tidak dipanggil sebagai gate
$rekening_lock_info = ['ok' => true];

$pengambilan_history_rows = [];
$pengambilan_hutang_rows = [];
$pengambilan_summary = fetchPengambilanSummary($pdo);

if ($tab === 'histori_pengambilan_dana') {
    $historyFilters = [
        'status' => $pengambilan_status,
        'tanggal_awal' => $tanggal_awal,
        'tanggal_akhir' => $tanggal_akhir,
    ];
    if (in_array($pengambilan_scope, ['internal', 'hutang'], true)) {
        $historyFilters['klasifikasi'] = $pengambilan_scope;
    }
    $pengambilan_history_rows = fetchPengambilanRows($pdo, $historyFilters);
} elseif ($tab === 'histori_hutang') {
    $pengambilan_hutang_rows = fetchHutangRootRows($pdo, [
        'status' => $pengambilan_status,
        'tanggal_awal' => $tanggal_awal,
        'tanggal_akhir' => $tanggal_akhir,
    ]);
}

// Handle detail view for closing transactions
$detail_view = null;
$transaksi_detail = [];
$closing_info = [];
if (isset($_GET['detail_id'])) {
    $detail_id = $_GET['detail_id'];
    
    $sql_detail = "SELECT sk.*, u.nama_lengkap AS nama_karyawan FROM setoran_keuangan_closing_kasir sk
                   LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan 
                   WHERE sk.id = ?";
    $stmt_detail = $pdo->prepare($sql_detail);
    $stmt_detail->execute([$detail_id]);
    $detail_view = $stmt_detail->fetch(PDO::FETCH_ASSOC);

    if ($detail_view) {
        $transaksi_detail = getClosingTransactionDetails($pdo, $detail_view['kode_setoran']);
        $closing_info = getClosingAggregatedInfo($pdo, $detail_view['kode_setoran']);
        
        $sql_bank = "SELECT sb.*, u.nama_lengkap as created_by_name 
                     FROM setoran_ke_bank_closing_kasir sb 
                     JOIN setoran_ke_bank_detail_closing_kasir sbd ON sb.id = sbd.setoran_ke_bank_id
                     JOIN tbuser u ON sb.created_by = u.kode_karyawan
                     WHERE sbd.setoran_keuangan_id = ?";
        $stmt_bank = $pdo->prepare($sql_bank);
        $stmt_bank->execute([$detail_id]);
        $bank_detail = $stmt_bank->fetch(PDO::FETCH_ASSOC);
        
        if ($bank_detail) {
            $detail_view['bank_info'] = $bank_detail;
        }
    }
}
// Handle bank detail view for closing report
$bank_detail_view = null;
$closing_detail = [];
$all_closing_detail = [];
$bank_pengambilan_rows = [];
if (isset($_GET['bank_detail_id'])) {
    $bank_detail_id = $_GET['bank_detail_id'];
    
    $sql_bank_detail = "SELECT sb.*, u.nama_lengkap as created_by_name 
                       FROM setoran_ke_bank_closing_kasir sb 
                       LEFT JOIN tbuser u ON sb.created_by = u.kode_karyawan 
                       WHERE sb.id = ?";
    $stmt_bank_detail = $pdo->prepare($sql_bank_detail);
    $stmt_bank_detail->execute([$bank_detail_id]);
    $bank_detail_view = $stmt_bank_detail->fetch(PDO::FETCH_ASSOC);

    if ($bank_detail_view) {
        // Get all setoran details grouped by cabang dengan closing info (untuk ringkasan)
        $sql_closing = "SELECT 
                           sk.kode_cabang,
                           c.nama_cabang,
                           COUNT(sk.id) as total_setoran,
                           SUM(sk.jumlah_diterima) as total_nominal,
                           GROUP_CONCAT(sk.kode_setoran ORDER BY sk.tanggal_setoran) as kode_setoran_list,
                           MIN(sk.tanggal_setoran) as tanggal_awal,
                           MAX(sk.tanggal_setoran) as tanggal_akhir,
                           SUM(CASE WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' 
                                    OR EXISTS (
                                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                                        WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                    ) THEN 1 ELSE 0 END) as total_closing_transactions
                       FROM setoran_ke_bank_detail_closing_kasir sbd
                       JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                       JOIN tbcabang c ON c.cabang_ref_kode = sk.kode_cabang
                       LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                       WHERE sbd.setoran_ke_bank_id = ?
                       GROUP BY sk.kode_cabang, c.nama_cabang
                       ORDER BY c.nama_cabang";
        $stmt_closing = $pdo->prepare($sql_closing);
        $stmt_closing->execute([$bank_detail_id]);
        $closing_detail = $stmt_closing->fetchAll(PDO::FETCH_ASSOC);

        // Ambil keseluruhan detail transaksi (lintas cabang) untuk ditampilkan sekaligus
        $sql_all_detail = "SELECT 
                                c.nama_cabang,
                                sk.kode_setoran,
                                sk.tanggal_setoran,
                                kt.kode_transaksi,
                                kt.tanggal_transaksi,
                                kt.setoran_real,
                                CASE 
                                    WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                                    WHEN EXISTS (
                                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk2 
                                        WHERE pk2.nomor_transaksi_closing = kt.kode_transaksi
                                    ) THEN 'DARI CLOSING'
                                    ELSE 'TRANSAKSI BIASA'
                                END as jenis_transaksi
                           FROM setoran_ke_bank_detail_closing_kasir sbd
                           JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                           JOIN tbcabang c ON c.cabang_ref_kode = sk.kode_cabang
                           LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                           WHERE sbd.setoran_ke_bank_id = ?
                           ORDER BY sk.tanggal_setoran, kt.tanggal_transaksi";
        $stmt_all = $pdo->prepare($sql_all_detail);
        $stmt_all->execute([$bank_detail_id]);
        $all_closing_detail = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        // Fix B: Cari pengambilan via id_setoran_bank ATAU via [Ref TRX:] keterangan
        // Step 1: ambil semua kasir_transaction ID yang masuk ke setoran_ke_bank_closing_kasir ini
        $sql_closing_ids_in_bank = "SELECT DISTINCT kt.id
                                    FROM setoran_ke_bank_detail_closing_kasir sbd
                                    JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                                    JOIN kasir_transactions_closing_kasir kt ON kt.kode_setoran = sk.kode_setoran
                                    WHERE sbd.setoran_ke_bank_id = ?";
        $stmt_cids = $pdo->prepare($sql_closing_ids_in_bank);
        $stmt_cids->execute([$bank_detail_id]);
        $bank_closing_ids = array_column($stmt_cids->fetchAll(PDO::FETCH_ASSOC), 'id');

        // Step 2: Cari pengambilan via id_setoran_bank ATAU via [Ref TRX:] keterangan
        $pga_where_parts = ['ps.id_setoran_bank = :bkid'];
        $pga_params = [':bkid' => $bank_detail_id];
        foreach ($bank_closing_ids as $idx => $cid) {
            $p = ':cid' . $idx;
            $pga_where_parts[] = "ps.keterangan LIKE CONCAT('%[Ref TRX: ', $p, '%')";
            $pga_params[$p] = $cid;
        }
        $sql_pengambilan_bank = "SELECT ps.kode_pengambilan,
                                        ps.tanggal_perencanaan_setor,
                                        ps.nominal_diambil,
                                        ps.nominal_sisa,
                                        ps.klasifikasi,
                                        ps.no_rekening_peminjam,
                                        ps.no_rekening_penerima,
                                        ps.status,
                                        c.nama_cabang AS nama_cabang_penerima
                                 FROM pengambilan_setoran_closing_kasir ps
                                 LEFT JOIN tbcabang c ON c.cabang_ref_kode = ps.kode_cabang_penerima
                                 WHERE ps.parent_kode_pengambilan IS NULL
                                   AND (" . implode(' OR ', $pga_where_parts) . ")
                                 ORDER BY ps.created_at DESC";
        $stmt_pengambilan_bank = $pdo->prepare($sql_pengambilan_bank);
        $stmt_pengambilan_bank->execute($pga_params);
        $bank_pengambilan_rows = $stmt_pengambilan_bank->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle specific cabang closing detail with enhanced closing info
$cabang_closing_detail = [];
if (isset($_GET['cabang_closing']) && isset($_GET['bank_detail_id'])) {
    $cabang_name = $_GET['cabang_closing'];
    $bank_detail_id = $_GET['bank_detail_id'];
    
    $sql_cabang_detail = "SELECT
                             sk.*,
                             kt.kode_transaksi,
                             kt.tanggal_transaksi,
                             COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) as setoran_real,
                             kt.deposit_status,
                             CASE
                                 WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                                 WHEN EXISTS (
                                     SELECT 1 FROM pemasukan_kasir_closing_kasir pk
                                     WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                 ) THEN 'DARI CLOSING'
                                 ELSE 'TRANSAKSI BIASA'
                             END as jenis_transaksi
                         FROM setoran_ke_bank_detail_closing_kasir sbd
                         JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                         JOIN tbcabang c ON c.cabang_ref_kode = sk.kode_cabang
                         LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                         WHERE sbd.setoran_ke_bank_id = ? AND c.nama_cabang = ?
                         ORDER BY 
                             CASE 
                                 WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 0
                                 WHEN EXISTS (
                                     SELECT 1 FROM pemasukan_kasir_closing_kasir pk2 
                                     WHERE pk2.nomor_transaksi_closing = kt.kode_transaksi
                                 ) THEN 0
                                 ELSE 1
                             END,
                             sk.tanggal_setoran, kt.tanggal_transaksi";
    $stmt_cabang_detail = $pdo->prepare($sql_cabang_detail);
    $stmt_cabang_detail->execute([$bank_detail_id, $cabang_name]);
    $cabang_closing_detail = $stmt_cabang_detail->fetchAll(PDO::FETCH_ASSOC);
}

// PERBAIKAN: Handle validation modal for individual transactions dengan info closing gabungan
$transaksi_detail = null;
if (isset($_GET['validate_id'])) {
    $sql_detail = "SELECT kt.*,
                          COALESCE(NULLIF(sk.kode_cabang, ''), NULLIF(kt.kode_cabang, '')) AS resolved_kode_cabang,
                          COALESCE(NULLIF(sk.nama_cabang, ''), NULLIF(kt.nama_cabang, '')) AS resolved_nama_cabang,
                          sk.nama_cabang AS nama_cabang_setoran, sk.tanggal_setoran, sk.nama_pengantar, 
                          COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan,
                          CASE 
                              WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                              WHEN EXISTS (
                                  SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                                  WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                              ) THEN 'DARI CLOSING'
                              WHEN EXISTS (
                                  SELECT 1
                                  FROM pemasukan_kasir_closing_kasir pkx
                                  JOIN kasir_transactions_closing_kasir ktx ON ktx.kode_transaksi = pkx.kode_transaksi
                                  WHERE ktx.kode_setoran = kt.kode_setoran
                                    AND ktx.kode_cabang = kt.kode_cabang
                                    AND (
                                         pkx.nomor_transaksi_closing = kt.kode_transaksi
                                         OR (
                                              (pkx.nomor_transaksi_closing IS NULL OR pkx.nomor_transaksi_closing = '')
                                              AND pkx.kode_akun = 'DRCLSG'
                                              AND UPPER(pkx.keterangan_transaksi) LIKE '%CLOSING%'
                                         )
                                    )
                              ) THEN 'DARI CLOSING'
                              ELSE 'TRANSAKSI BIASA'
                          END as jenis_transaksi,
                          -- TAMBAHAN: Informasi closing gabungan
                          pk.jumlah as jumlah_pemasukan_closing,
                          pk.keterangan_transaksi as keterangan_closing,
                          (
                              SELECT COALESCE(SUM(pk3.jumlah), 0)
                              FROM pemasukan_kasir_closing_kasir pk3
                              JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                              WHERE kt3.kode_setoran = kt.kode_setoran
                                AND kt3.kode_cabang = kt.kode_cabang
                                AND (
                                     pk3.nomor_transaksi_closing = kt.kode_transaksi
                                     OR (
                                          (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                                          AND pk3.kode_akun = 'DRCLSG'
                                          AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                                     )
                                )
                          ) as total_closing_borrowed
                   FROM kasir_transactions_closing_kasir kt
                   LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
                   LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan 
                   LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
                   WHERE kt.kode_transaksi = ? AND kt.deposit_status = 'Diterima Staff Keuangan'";
    $stmt_detail = $pdo->prepare($sql_detail);
    $stmt_detail->execute([$_GET['validate_id']]);
    $transaksi_detail = $stmt_detail->fetch(PDO::FETCH_ASSOC);
    
    if ($transaksi_detail) {
        $transaksi_detail = enrichClosingTransactionRow($pdo, $transaksi_detail);
    }
    
    // Get additional closing info if this is a closing transaction
    if ($transaksi_detail && $transaksi_detail['jenis_transaksi'] == 'DARI CLOSING') {
        $transaksi_detail['closing_info'] = getClosingAggregatedInfo($pdo, $transaksi_detail['kode_setoran']);
    }
}

// PERBAIKAN: Handle edit selisih modal for individual transactions dengan info closing gabungan
$edit_selisih_detail = null;
if (isset($_GET['edit_selisih_id'])) {
    $sql_detail = "SELECT kt.*,
                          COALESCE(NULLIF(sk.kode_cabang, ''), NULLIF(kt.kode_cabang, '')) AS resolved_kode_cabang,
                          COALESCE(NULLIF(sk.nama_cabang, ''), NULLIF(kt.nama_cabang, '')) AS resolved_nama_cabang,
                          sk.nama_cabang AS nama_cabang_setoran, sk.tanggal_setoran, sk.nama_pengantar, 
                          COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan,
                          CASE 
                              WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                              WHEN EXISTS (
                                  SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                                  WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                              ) THEN 'DARI CLOSING'
                              WHEN EXISTS (
                                  SELECT 1
                                  FROM pemasukan_kasir_closing_kasir pkx
                                  JOIN kasir_transactions_closing_kasir ktx ON ktx.kode_transaksi = pkx.kode_transaksi
                                  WHERE ktx.kode_setoran = kt.kode_setoran
                                    AND ktx.kode_cabang = kt.kode_cabang
                                    AND (
                                         pkx.nomor_transaksi_closing = kt.kode_transaksi
                                         OR (
                                              (pkx.nomor_transaksi_closing IS NULL OR pkx.nomor_transaksi_closing = '')
                                              AND pkx.kode_akun = 'DRCLSG'
                                              AND UPPER(pkx.keterangan_transaksi) LIKE '%CLOSING%'
                                         )
                                    )
                              ) THEN 'DARI CLOSING'
                              ELSE 'TRANSAKSI BIASA'
                          END as jenis_transaksi,
                          -- TAMBAHAN: Informasi closing gabungan
                          pk.jumlah as jumlah_pemasukan_closing,
                          pk.keterangan_transaksi as keterangan_closing,
                          (
                              SELECT COALESCE(SUM(pk3.jumlah), 0)
                              FROM pemasukan_kasir_closing_kasir pk3
                              JOIN kasir_transactions_closing_kasir kt3 ON kt3.kode_transaksi = pk3.kode_transaksi
                              WHERE kt3.kode_setoran = kt.kode_setoran
                                AND kt3.kode_cabang = kt.kode_cabang
                                AND (
                                     pk3.nomor_transaksi_closing = kt.kode_transaksi
                                     OR (
                                          (pk3.nomor_transaksi_closing IS NULL OR pk3.nomor_transaksi_closing = '')
                                          AND pk3.kode_akun = 'DRCLSG'
                                          AND UPPER(pk3.keterangan_transaksi) LIKE '%CLOSING%'
                                     )
                                )
                          ) as total_closing_borrowed
                   FROM kasir_transactions_closing_kasir kt
                   LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
                   LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan 
                   LEFT JOIN pemasukan_kasir_closing_kasir pk ON pk.nomor_transaksi_closing = kt.kode_transaksi
                   WHERE kt.kode_transaksi = ? AND kt.deposit_status = 'Validasi Keuangan SELISIH'";
    $stmt_detail = $pdo->prepare($sql_detail);
    $stmt_detail->execute([$_GET['edit_selisih_id']]);
    $edit_selisih_detail = $stmt_detail->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_selisih_detail) {
        $edit_selisih_detail = enrichClosingTransactionRow($pdo, $edit_selisih_detail);
    }
    
    // Get additional closing info if this is a closing transaction
    if ($edit_selisih_detail && $edit_selisih_detail['jenis_transaksi'] == 'DARI CLOSING') {
        $edit_selisih_detail['closing_info'] = getClosingAggregatedInfo($pdo, $edit_selisih_detail['kode_setoran']);
    }
}

function formatRupiah($angka) {
    if ($angka === null || $angka === '') {
        return 'Rp 0';
    }
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Setoran Keuangan Pusat</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="includes/sidebar.css" rel="stylesheet">
    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-color: #007bff;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --secondary-color: #6c757d;
    --background-light: #f8fafc;
    --text-dark: #334155;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --closing-color: #9c27b0; /* Purple for closing transactions */
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--background-light);
    color: var(--text-dark);
    display: flex;
    min-height: 100vh;
}

.main-content.fullscreen {
    margin-left: 0;
    width: 100%;
}

.sidebar.hidden {
    transform: translateX(-100%);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.welcome-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    margin-bottom: 24px;
}

.welcome-card h1 {
    font-size: 24px;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.info-tags {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.info-tag {
    background: var(--background-light);
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 14px;
    color: var(--text-dark);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stats-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    border-left: 4px solid var(--primary-color);
}

.stats-card.success {
    border-left-color: var(--success-color);
}

.stats-card.warning {
    border-left-color: var(--warning-color);
}

.stats-card.info {
    border-left-color: var(--info-color);
}

.stats-card.danger {
    border-left-color: var(--danger-color);
}

.stats-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stats-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: var(--text-muted);
    font-weight: 500;
}

.stats-info .stats-number {
    font-size: 20px;
    font-weight: bold;
    margin: 0;
    color: var(--text-dark);
}

.stats-icon {
    font-size: 28px;
    opacity: 0.7;
    color: var(--primary-color);
}

.stats-card.success .stats-icon {
    color: var(--success-color);
}

.stats-card.warning .stats-icon {
    color: var(--warning-color);
}

.stats-card.info .stats-icon {
    color: var(--info-color);
}

.stats-card.danger .stats-icon {
    color: var(--danger-color);
}

/* Receipt card styles - Updated for simple format */
.receipt-card {
    background: white;
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 24px;
    margin: 20px 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.receipt-header {
    text-align: center;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.receipt-title {
    font-size: 20px;
    font-weight: bold;
    color: var(--text-dark);
    margin-bottom: 5px;
}

.receipt-subtitle {
    color: var(--text-muted);
    font-size: 14px;
}

.receipt-body {
    margin-bottom: 20px;
}

.receipt-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dotted var(--border-color);
}

.receipt-row:last-child {
    border-bottom: none;
    font-weight: bold;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid var(--border-color);
}

.receipt-footer {
    text-align: center;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
    color: var(--text-muted);
    font-size: 12px;
}

.nav-tabs {
    background: white;
    border-radius: 16px 16px 0 0;
    padding: 0;
    border: 1px solid var(--border-color);
    border-bottom: none;
    margin-bottom: 0;
    display: flex;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    scrollbar-width: thin;
}

.nav-tabs .nav-item {
    margin-bottom: 0;
    flex: 0 0 auto;
}

.nav-tabs .nav-link {
    padding: 16px 24px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    border-radius: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.nav-tabs .nav-link:hover {
    background: var(--background-light);
    color: var(--text-dark);
}

.nav-tabs .nav-link.active {
    background: var(--primary-color);
    color: white;
    position: relative;
}

.nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary-color);
}

.badge {
    background: rgba(255,255,255,0.2);
    color: inherit;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 5px;
}

.nav-tabs .nav-link:not(.active) .badge {
    background: var(--background-light);
    color: var(--text-dark);
}

.filter-card {
    background: white;
    border-radius: 0 0 16px 16px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    border-top: none;
    margin-bottom: 24px;
}

.form-inline {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--text-dark);
    font-size: 14px;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    background: white;
    min-width: 120px;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    border: 1px solid transparent;
}

.form-inline .btn {
    height: 38px;
    padding-top: 8px;
    padding-bottom: 8px;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: #1e7e34;
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #bd2130;
}

.btn-warning {
    background-color: var(--warning-color);
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.btn-info {
    background-color: var(--info-color);
    color: white;
}

.btn-info:hover {
    background-color: #138496;
}

.btn-secondary {
    background-color: var(--secondary-color);
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: none;
    align-items: center;
    gap: 10px;
    border: 1px solid transparent;
}

.alert.show {
    display: flex;
}

.alert-success {
    background: rgba(40,167,69,0.1);
    color: var(--success-color);
    border-color: rgba(40,167,69,0.2);
}

.alert-danger {
    background: rgba(220,53,69,0.1);
    color: var(--danger-color);
    border-color: rgba(220,53,69,0.2);
}

.alert-info {
    background: rgba(23,162,184,0.1);
    color: var(--info-color);
    border-color: rgba(23,162,184,0.2);
}

.content-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    overflow: visible; /* Jangan hidden — memotong scroll tabel horizontal di mobile */
    margin-bottom: 24px;
}

.content-header {
    background: var(--background-light);
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.content-header h3 {
    margin: 0;
    color: var(--text-dark);
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.content-body {
    padding: 24px;
}

.bulk-actions {
    background: var(--background-light);
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
}

/* ===== PERBAIKAN: Enhanced table container untuk SEMUA tab ===== */
.table-container {
    overflow: visible; /* Jangan hidden — memotong scroll horizontal di mobile */
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
    background: white;
    position: relative;
}

.table-wrapper {
    overflow-x: auto !important; /* PENTING: Force scroll horizontal untuk semua tabel */
    overflow-y: hidden; /* hidden (bukan visible) agar tidak terpotong secara vertikal di mobile */
    max-width: 100%;
    position: relative;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    border-radius: 12px; /* Pindah dari table-container agar clipping tetap terjaga saat scroll */
    /* Enhanced scroll bar yang visible untuk SEMUA tab */
    scrollbar-width: auto;
    scrollbar-color: #007bff #f8f9fa;
}

/* Enhanced WebKit scrollbar styling - BERLAKU UNTUK SEMUA TAB */
.table-wrapper::-webkit-scrollbar {
    height: 12px !important; /* Force tinggi scrollbar */
    background: #f8f9fa;
    border-radius: 6px;
}

.table-wrapper::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.table-wrapper::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 6px;
    border: 1px solid #0056b3;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #0056b3, #004494);
    cursor: pointer;
}

.table-wrapper::-webkit-scrollbar-thumb:active {
    background: #004494;
}

/* Scroll indicators untuk SEMUA tab */
.table-container::before,
.table-container::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 12px;
    width: 20px;
    pointer-events: none;
    z-index: 10;
    transition: opacity 0.3s ease;
}

.table-container::before {
    left: 0;
    background: linear-gradient(to right, rgba(255,255,255,0.9), transparent);
    opacity: 0;
}

.table-container::after {
    right: 0;
    background: linear-gradient(to left, rgba(255,255,255,0.9), transparent);
    opacity: 1;
}

.table-container.scrolled-left::before {
    opacity: 1;
}

.table-container.scrolled-right::after {
    opacity: 0;
}

/* ===== PERBAIKAN: Table styling untuk SEMUA tab ===== */
.table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    /* PENTING: Lebar minimum berbeda per tab */
    min-width: 1200px; /* Default untuk tab terima */
    background: white;
    position: relative;
}

/* PERBAIKAN: Lebar tabel spesifik per tab */
/* Tab Validasi & Edit Selisih - lebih lebar karena banyak kolom */
.tab-validasi .table,
.tab-validasi_selisih .table {
    min-width: 1800px !important; /* Lebih lebar untuk kolom validasi */
}

/* Tab Setor Bank - sedang */
.tab-setor_bank .table {
    min-width: 1400px !important;
}

/* Enhanced horizontal scrollbar untuk tab setor bank */
.tab-setor_bank .table-wrapper,
#setorBankTableWrapper {
    overflow-x: scroll !important;
    overflow-y: visible !important;
    scrollbar-width: thick !important;
    scrollbar-color: #dc3545 #f8f9fa !important;
}

.tab-setor_bank .table-wrapper::-webkit-scrollbar,
#setorBankTableWrapper::-webkit-scrollbar {
    height: 20px !important;
    background: #f8f9fa !important;
    border-radius: 10px !important;
    border: 2px solid #dee2e6 !important;
    display: block !important;
    -webkit-appearance: none !important;
}

.tab-setor_bank .table-wrapper::-webkit-scrollbar-track,
#setorBankTableWrapper::-webkit-scrollbar-track {
    background: #e9ecef !important;
    border-radius: 10px !important;
    border: 1px solid #ced4da !important;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.2) !important;
}

.tab-setor_bank .table-wrapper::-webkit-scrollbar-thumb,
#setorBankTableWrapper::-webkit-scrollbar-thumb {
    background: #dc3545 !important;
    border-radius: 10px !important;
    border: 3px solid #fff !important;
    box-shadow: 0 4px 8px rgba(220,53,69,0.5) !important;
    min-width: 60px !important;
    -webkit-appearance: none !important;
}

.tab-setor_bank .table-wrapper::-webkit-scrollbar-thumb:hover,
#setorBankTableWrapper::-webkit-scrollbar-thumb:hover {
    background: #c82333 !important;
    cursor: grab !important;
    transform: scale(1.1) !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 6px 12px rgba(220,53,69,0.7) !important;
}

.tab-setor_bank .table-wrapper::-webkit-scrollbar-thumb:active,
#setorBankTableWrapper::-webkit-scrollbar-thumb:active {
    background: #a71e2a !important;
    cursor: grabbing !important;
    transform: scale(1.05) !important;
}

/* Tambahan untuk memastikan scrollbar selalu terlihat */
.tab-setor_bank .table-container {
    position: relative;
    border: 2px solid #007bff;
    border-radius: 12px;
    background: #fff;
    overflow: visible;
}

.tab-setor_bank .table {
    min-width: 1500px !important;
    margin-bottom: 20px !important;
}

/* Force scrollbar visibility with CSS injection */
body.tab-setor_bank #setorBankTableWrapper {
    overflow-x: scroll !important;
    -webkit-overflow-scrolling: touch !important;
}

/* Tab Bank History - dengan text wrapping dan horizontal scroll */
.tab-bank_history .table {
    min-width: 1600px !important;
    table-layout: fixed !important;
}

.tab-bank_history .table td {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    white-space: normal !important;
    vertical-align: top !important;
    padding: 8px !important;
}

.tab-bank_history .table th {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    white-space: normal !important;
    vertical-align: top !important;
    padding: 8px !important;
}

.tab-bank_history .table code {
    word-break: break-all !important;
    white-space: normal !important;
}

/* Enhanced horizontal scrollbar untuk tab bank history */
.tab-bank_history .table-wrapper,
#bankHistoryTableWrapper {
    overflow-x: scroll !important;
    overflow-y: visible !important;
    scrollbar-width: thick !important;
    scrollbar-color: #dc3545 #f8f9fa !important;
}

.tab-bank_history .table-wrapper::-webkit-scrollbar,
#bankHistoryTableWrapper::-webkit-scrollbar {
    height: 18px !important;
    background: #f8f9fa !important;
    border-radius: 9px !important;
    border: 2px solid #dee2e6 !important;
    display: block !important;
    -webkit-appearance: none !important;
}

.tab-bank_history .table-wrapper::-webkit-scrollbar-track,
#bankHistoryTableWrapper::-webkit-scrollbar-track {
    background: #e9ecef !important;
    border-radius: 9px !important;
    border: 1px solid #ced4da !important;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.2) !important;
}

.tab-bank_history .table-wrapper::-webkit-scrollbar-thumb,
#bankHistoryTableWrapper::-webkit-scrollbar-thumb {
    background: #dc3545 !important;
    border-radius: 9px !important;
    border: 2px solid #fff !important;
    box-shadow: 0 4px 8px rgba(220,53,69,0.5) !important;
    min-width: 60px !important;
    -webkit-appearance: none !important;
}

.tab-bank_history .table-wrapper::-webkit-scrollbar-thumb:hover,
#bankHistoryTableWrapper::-webkit-scrollbar-thumb:hover {
    background: #c82333 !important;
    cursor: grab !important;
    transform: scale(1.05) !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 6px 12px rgba(220,53,69,0.7) !important;
}

.tab-bank_history .table-wrapper::-webkit-scrollbar-thumb:active,
#bankHistoryTableWrapper::-webkit-scrollbar-thumb:active {
    background: #a71e2a !important;
    cursor: grabbing !important;
    transform: scale(1.02) !important;
}

/* Tambahan untuk memastikan scrollbar selalu terlihat */
.tab-bank_history .table-container {
    position: relative;
    border: 2px solid #007bff;
    border-radius: 12px;
    background: #fff;
    overflow: visible;
}

/* Fixed styling untuk grand total row yang tidak boleh hilang */
.grand-total-row-fixed,
#grandTotalRow {
    background: #28a745 !important;
    color: white !important;
    font-weight: bold !important;
    font-size: 16px !important;
    border: 2px solid #007bff !important;
    position: relative !important;
    z-index: 999 !important;
    display: table-row !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.grand-total-row-fixed td,
#grandTotalRow td {
    background: #28a745 !important;
    color: white !important;
    font-weight: bold !important;
    font-size: 16px !important;
    display: table-cell !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Header tabel untuk SEMUA tab */
/* CATATAN: position:sticky dihapus — di dalam overflow-x:auto container,
   sticky pada th menyebabkan konten kosong saat scroll horizontal di iOS/Android */
.table th {
    background: var(--background-light);
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 13px;
    border-bottom: 2px solid var(--border-color);
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Column width specifications untuk validasi dan edit selisih */
.tab-validasi .table th:nth-child(1),
.tab-validasi_selisih .table th:nth-child(1) { min-width: 100px; } /* Tanggal */
.tab-validasi .table th:nth-child(2),
.tab-validasi_selisih .table th:nth-child(2) { min-width: 180px; } /* Kode Transaksi */
.tab-validasi .table th:nth-child(3),
.tab-validasi_selisih .table th:nth-child(3) { min-width: 120px; } /* Jenis */  
.tab-validasi .table th:nth-child(4),
.tab-validasi_selisih .table th:nth-child(4) { min-width: 150px; } /* Kode Setoran */
.tab-validasi .table th:nth-child(5),
.tab-validasi_selisih .table th:nth-child(5) { min-width: 120px; } /* Cabang */
.tab-validasi .table th:nth-child(6),
.tab-validasi_selisih .table th:nth-child(6) { min-width: 120px; } /* Kasir */
.tab-validasi .table th:nth-child(7),
.tab-validasi_selisih .table th:nth-child(7) { min-width: 140px; } /* Nominal */
.tab-validasi .table th:nth-child(8),
.tab-validasi_selisih .table th:nth-child(8) { min-width: 200px; } /* Info Closing */
.tab-validasi .table th:nth-child(9),
.tab-validasi_selisih .table th:nth-child(9) { min-width: 120px; } /* Status */
.tab-validasi .table th:nth-child(10),
.tab-validasi_selisih .table th:nth-child(10) { min-width: 180px; } /* Info tambahan */
.tab-validasi .table th:nth-child(11),
.tab-validasi_selisih .table th:nth-child(11) { min-width: 100px; } /* Aksi */

.table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
    vertical-align: middle;
    white-space: nowrap;
}

.table tbody tr:hover {
    background: rgba(0,123,255,0.05);
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table-enhanced {
    background: white;
    border-radius: 12px;
    overflow: visible;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
}

.table-wrapper > .table-enhanced,
.table-responsive > .table-enhanced {
    display: block;
    width: auto;
    min-width: 0;
    max-width: none;
    overflow-x: visible !important;
    overflow-y: visible !important;
}

.table-enhanced .table {
    margin: 0;
}

.table-enhanced .table th {
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
    font-weight: 600;
    border: none;
    box-shadow: 0 2px 8px rgba(0,123,255,0.3);
}

/* Enhanced table focus state */
.table-wrapper:focus-within {
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    border-radius: 12px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.status-badge.bg-warning {
    background: rgba(255,193,7,0.2);
    color: #856404;
}

.status-badge.bg-info {
    background: rgba(23,162,184,0.2);
    color: #0c5460;
}

.status-badge.bg-success {
    background: rgba(40,167,69,0.2);
    color: #155724;
}

.status-badge.bg-primary {
    background: rgba(0,123,255,0.2);
    color: #004085;
}

.status-badge.bg-danger {
    background: rgba(220,53,69,0.2);
    color: #721c24;
}

/* New styles for closing transactions */
.status-badge.bg-closing {
    background: rgba(156,39,176,0.2);
    color: #4a148c;
}

/* Closing transaction specific scrolling */
.closing-transaction {
    background: rgba(156,39,176,0.05) !important;
    border-left: 4px solid var(--closing-color) !important;
    transition: all 0.3s ease;
}

.closing-transaction:hover {
    background: rgba(156,39,176,0.15) !important;
    transform: translateX(2px);
}

.closing-info-badge {
    background: var(--closing-color);
    color: white;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
    margin-left: 5px;
}

.transaction-type-indicator {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    overflow-y: auto;
    padding: 20px;
}

.modal.show {
    display: flex;
}

.modal-dialog {
    background: white;
    border-radius: 16px;
    max-width: 90%;
    max-height: 90%;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-lg {
    max-width: 800px;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
    flex: 1;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-muted);
    padding: 0;
    margin-left: 10px;
    text-decoration: none;
}

.btn-close:hover {
    color: var(--text-dark);
}

.modal-body {
    padding: 24px;
}

.modal .table-wrapper,
.modal .table-responsive,
.summary-table-scroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto !important;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.validation-summary {
    background: var(--background-light);
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
}

/* Enhanced styles for closing transaction validation */
.closing-validation-info {
    background: linear-gradient(135deg, rgba(156,39,176,0.1), rgba(156,39,176,0.05));
    border: 1px solid rgba(156,39,176,0.2);
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.closing-validation-info h6 {
    color: var(--closing-color);
    font-weight: 600;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.closing-aggregation {
    background: white;
    border: 1px solid rgba(156,39,176,0.2);
    border-radius: 8px;
    padding: 12px;
    margin-top: 10px;
}

.closing-summary-item {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 13px;
}

.closing-summary-item.total {
    font-weight: bold;
    border-top: 1px solid var(--border-color);
    margin-top: 8px;
    padding-top: 8px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.detail-value {
    font-size: 16px;
    font-weight: 500;
    color: var(--text-dark);
}

.detail-value.amount {
    font-size: 18px;
    font-weight: 600;
    color: var(--success-color);
}

.no-data {
    text-align: center;
    padding: 40px;
    color: var(--text-muted);
}

.no-data i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.workflow-info {
    background: linear-gradient(135deg, rgba(23,162,184,0.1), rgba(23,162,184,0.05));
    border: 1px solid rgba(23,162,184,0.2);
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}
.workflow-info h6 {
    color: var(--info-color);
    font-weight: 600;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.workflow-info p {
    margin: 0;
    color: var(--text-dark);
    font-size: 14px;
}

.required {
    color: var(--danger-color);
}

.closing-summary {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
}

.closing-summary h4 {
    color: var(--text-dark);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.closing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.closing-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.closing-label {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 5px;
}

.closing-value {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
}

.closing-value.amount {
    color: var(--success-color);
    font-size: 18px;
}

/* PERBAIKAN: Scroll hint untuk user experience - SEMUA TAB */
.scroll-hint {
    position: absolute;
    bottom: 15px;
    right: 20px;
    background: rgba(0,123,255,0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
    z-index: 15;
    animation: fadeInOut 3s ease-in-out;
    pointer-events: none;
}

.scroll-progress-container {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(0,0,0,0.1);
    z-index: 10;
}

.scroll-progress {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--info-color));
    width: 0%;
    transition: width 0.3s ease;
}

.table-scroll-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 15;
    background: rgba(0,123,255,0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    opacity: 0;
}

.table-scroll-left {
    left: 5px;
}

.table-scroll-right {
    right: 5px;
}

.closing-borrowed-info {
    background: rgba(156,39,176,0.1);
    border: 1px solid rgba(156,39,176,0.2);
    padding: 10px;
    border-radius: 8px;
    margin: 10px 0;
    font-size: 12px;
    color: var(--closing-color);
}

.expected-amount {
    font-weight: 600;
    color: var(--warning-color);
}

.borrowed-amount {
    font-weight: 600;
    color: var(--danger-color);
}

.export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.no-setoran-message {
    background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,193,7,0.05));
    border: 1px solid rgba(255,193,7,0.2);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 20px;
}

.no-setoran-message h6 {
    color: var(--warning-color);
    font-weight: 600;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

/* Hide/show sidebar controls */
.sidebar-toggle {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1100;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
    display: none;
}

.sidebar-toggle.show {
    display: block;
}

/* PERBAIKAN: Responsive styles dengan scroll yang lebih baik - SEMUA TAB */
@media (max-width: 768px) {
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .form-inline {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-group {
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .closing-grid {
        grid-template-columns: 1fr;
    }
    
    .nav-tabs {
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 2px;
        scroll-snap-type: x proximity;
    }

    .nav-tabs .nav-link {
        padding: 14px 16px;
        min-height: 48px;
        scroll-snap-align: start;
    }

    .content-card {
        border-radius: 14px;
    }

    .content-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 16px;
    }

    .content-header h3 {
        font-size: 16px;
        line-height: 1.4;
        align-items: flex-start;
    }

    .content-body {
        padding: 16px;
    }

    .export-buttons {
        width: 100%;
        margin-bottom: 0;
    }

    .export-buttons .btn {
        flex: 1 1 140px;
        width: auto;
    }

    .filter-card {
        padding: 16px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .modal-dialog {
        max-width: 95%;
        margin: 10px;
    }
    
    /* PERBAIKAN: Mobile table styles - SEMUA TAB */
    .table-container {
        margin: 0 -16px;
        border-radius: 0;
        border-left: none;
        border-right: none;
    }

    .table-wrapper {
        padding-bottom: 4px;
        border-radius: 0; /* Sesuaikan dengan table-container mobile */
        overflow-x: auto !important;
        overflow-y: hidden !important;
        -webkit-overflow-scrolling: touch !important;
        /* Pastikan konten tidak terpotong saat digeser */
        display: block;
        width: 100%;
    }
    
    .table-wrapper::-webkit-scrollbar {
        height: 8px !important; /* Smaller on mobile */
    }
    
    .table {
        font-size: 12px;
        /* Mobile minimum widths - lebih kecil tapi tetap scrollable */
        min-width: 1000px !important; /* Override semua min-width di mobile */
    }
    
    .tab-validasi .table,
    .tab-validasi_selisih .table {
        min-width: 1400px !important; /* Tetap lebar untuk validasi di mobile */
    }
    
    .table th,
    .table td {
        padding: 8px 10px;
    }

    .btn-group-vertical {
        min-width: 140px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-sm {
        width: 100%;
        justify-content: center;
    }
    
    /* Mobile scroll indicators */
    .table-container::before,
    .table-container::after {
        bottom: 8px;
    }
    
    .closing-transaction {
        border-left: 2px solid var(--closing-color) !important;
    }
}

/* Print styles */
@media print {
    body * {
        visibility: hidden;
    }
    .receipt-card, .receipt-card * {
        visibility: visible;
    }
    .receipt-card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        border: 2px solid #000;
    }
    .no-print {
        display: none !important;
    }
}

/* CSS animations for closing transactions */
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(156,39,176,0.4); }
    70% { box-shadow: 0 0 0 10px rgba(156,39,176,0); }
    100% { box-shadow: 0 0 0 0 rgba(156,39,176,0); }
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

@keyframes fadeInOut {
    0%, 100% { opacity: 0; }
    50% { opacity: 1; }
}

.closing-info-badge {
    animation: pulse 2s infinite;
}
    </style>
</head>
<body class="tab-<?php echo $tab; ?>">
<!-- Sidebar Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
    <div class="user-profile">
        <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <div>
            <strong><?php echo htmlspecialchars($username); ?></strong>
            <p style="color: var(--text-muted); font-size: 12px;">Staff Keuangan Pusat</p>
        </div>
    </div>

    <div class="welcome-card">
        <h1><i class="fas fa-hand-holding-usd"></i> Manajemen Setoran Keuangan Pusat</h1>
        <p style="color: var(--text-muted); margin-bottom: 0;">Kelola penerimaan, validasi, dan penyetoran dana dari seluruh cabang FIT MOTOR dengan dukungan khusus untuk transaksi closing</p>
        <div class="info-tags">
            <div class="info-tag"><i class="fas fa-user"></i> User: <?php echo htmlspecialchars($username); ?></div>
            <div class="info-tag"><i class="fas fa-shield-alt"></i> Role: Staff Keuangan Pusat</div>
            <div class="info-tag"><i class="fas fa-calendar-day"></i> Tanggal: <?php echo date('d M Y'); ?></div>
            <div class="info-tag"><i class="fas fa-sync-alt"></i> Closing Support: Active</div>
        </div>
    </div>

    <!-- Enhanced Statistics Grid with Closing Info -->
    <div class="stats-grid">
        <div class="stats-card info">
            <div class="stats-content">
                <div class="stats-info">
                    <h4>Sedang Dibawa Kurir</h4>
                    <p class="stats-number"><?php 
                        $stmt_count = $pdo->query("SELECT COUNT(*) FROM setoran_keuangan_closing_kasir WHERE status = 'Sedang Dibawa Kurir'");
                        echo $stmt_count->fetchColumn();
                    ?></p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-truck"></i>
                </div>
            </div>
        </div>
        <div class="stats-card warning">
            <div class="stats-content">
                <div class="stats-info">
                    <h4>Perlu Validasi</h4>
                    <p class="stats-number"><?php 
                        $stmt_count = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Diterima Staff Keuangan'");
                        $total_validasi = $stmt_count->fetchColumn();
                        echo $total_validasi;
                    ?></p>
                    <small style="font-size: 10px; color: var(--text-muted);">
                        <?php 
                        $stmt_closing = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Diterima Staff Keuangan' AND (kode_transaksi LIKE '%CLOSING%' OR kode_transaksi LIKE '%CLO%' OR EXISTS (SELECT 1 FROM pemasukan_kasir_closing_kasir pk WHERE pk.nomor_transaksi_closing = kasir_transactions_closing_kasir.kode_transaksi))");
                        $closing_count = $stmt_closing->fetchColumn();
                        echo $closing_count > 0 ? "($closing_count dari closing)" : "";
                        ?>
                    </small>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
        <div class="stats-card danger">
            <div class="stats-content">
                <div class="stats-info">
                    <h4>Ada Selisih</h4>
                    <p class="stats-number"><?php 
                        $stmt_count = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Validasi Keuangan SELISIH'");
                        $total_selisih = $stmt_count->fetchColumn();
                        echo $total_selisih;
                    ?></p>
                    <small style="font-size: 10px; color: var(--text-muted);">
                        <?php 
                        $stmt_closing_selisih = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Validasi Keuangan SELISIH' AND (kode_transaksi LIKE '%CLOSING%' OR kode_transaksi LIKE '%CLO%' OR EXISTS (SELECT 1 FROM pemasukan_kasir_closing_kasir pk WHERE pk.nomor_transaksi_closing = kasir_transactions_closing_kasir.kode_transaksi))");
                        $closing_selisih_count = $stmt_closing_selisih->fetchColumn();
                        echo $closing_selisih_count > 0 ? "($closing_selisih_count dari closing)" : "";
                        ?>
                    </small>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        <div class="stats-card warning">
            <div class="stats-content">
                <div class="stats-info">
                    <h4>Dikembalikan ke CS</h4>
                    <p class="stats-number"><?php 
                        $stmt_count = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Dikembalikan ke CS'");
                        $total_dikembalikan = $stmt_count->fetchColumn();
                        echo $total_dikembalikan;
                    ?></p>
                    <small style="font-size: 10px; color: var(--text-muted);">
                        <?php 
                        $stmt_closing_dikembalikan = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Dikembalikan ke CS' AND (kode_transaksi LIKE '%CLOSING%' OR kode_transaksi LIKE '%CLO%' OR EXISTS (SELECT 1 FROM pemasukan_kasir_closing_kasir pk WHERE pk.nomor_transaksi_closing = kasir_transactions_closing_kasir.kode_transaksi))");
                        $closing_dikembalikan_count = $stmt_closing_dikembalikan->fetchColumn();
                        echo $closing_dikembalikan_count > 0 ? "($closing_dikembalikan_count dari closing)" : "";
                        ?>
                    </small>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-undo"></i>
                </div>
            </div>
        </div>
        <div class="stats-card success">
            <div class="stats-content">
                <div class="stats-info">
                    <h4>Transaksi Siap Setor Bank</h4>
                    <p class="stats-number"><?php 
                        $stmt_count = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir kt LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran WHERE sk.status = 'Validasi Keuangan OK' AND kt.status = 'end proses' AND kt.deposit_status = 'Validasi Keuangan OK'");
                        echo $stmt_count->fetchColumn();
                    ?></p>
                </div>
                <div class="stats-icon">
                    <i class="fas fa-university"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Card for Received Deposits -->
    <?php if (isset($_SESSION['received_setorans']) && !empty($_SESSION['received_setorans'])): ?>
    <div class="receipt-card">
        <div class="receipt-header">
            <div class="receipt-title">BUKTI PENERIMAAN SETORAN</div>
            <div class="receipt-subtitle">FIT MOTOR - KEUANGAN PUSAT</div>
        </div>
        <div class="receipt-body">
            <div class="receipt-row">
                <span>Tanggal Penerimaan:</span>
                <span><?php echo date('d/m/Y H:i', strtotime($_SESSION['received_at'])); ?></span>
            </div>
            <div class="receipt-row">
                <span>Diterima Oleh:</span>
                <span><?php echo htmlspecialchars($_SESSION['received_by']); ?></span>
            </div>
            <div class="receipt-row">
                <span>Jumlah Setoran:</span>
                <span><?php echo count($_SESSION['received_setorans']); ?> paket</span>
            </div>
            <hr style="margin: 15px 0;">
            <?php foreach ($_SESSION['received_setorans'] as $setoran): ?>
            <div class="receipt-row">
                <span><?php echo htmlspecialchars($setoran['kode_setoran']); ?> - <?php echo htmlspecialchars($setoran['nama_cabang']); ?></span>
                <span>Diterima</span>
            </div>
            <?php endforeach; ?>
            <div class="receipt-row">
                <span><strong>TOTAL PAKET DITERIMA:</strong></span>
                <span><strong><?php echo count($_SESSION['received_setorans']); ?> paket</strong></span>
            </div>
        </div>
        <div class="receipt-footer">
            <p>Bukti ini merupakan konfirmasi penerimaan setoran dari kurir cabang</p>
            <p>Simpan bukti ini sebagai dokumen penerimaan</p>
            <div class="no-print" style="margin-top: 15px;">
                <a href="export_excel_setoran.php?type=receipt&data=<?php echo base64_encode(json_encode($_SESSION['received_setorans'])); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <button onclick="closeReceipt()" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
    <?php 
        // Clear the session after showing
        unset($_SESSION['received_setorans']);
        unset($_SESSION['received_by']);
        unset($_SESSION['received_at']);
    endif; 
    ?>

    <?php if (isset($message)): ?>
        <div class="alert alert-success show">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger show">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <?php if (true): ?>
    <div class="nav-tabs">
        <div class="nav-item">
            <a class="nav-link <?php echo $tab == 'terima' ? 'active' : ''; ?>" href="?tab=terima">
                <i class="fas fa-download"></i> Terima Setoran 
                <span class="badge"><?php 
                    $stmt_count = $pdo->query("SELECT COUNT(*) FROM setoran_keuangan_closing_kasir WHERE status = 'Sedang Dibawa Kurir'");
                    echo $stmt_count->fetchColumn();
                ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?php echo $tab == 'validasi' ? 'active' : ''; ?>" href="?tab=validasi">
                <i class="fas fa-search"></i> Validasi Fisik 
                <span class="badge"><?php 
                    $stmt_count = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Diterima Staff Keuangan'");
                    echo $stmt_count->fetchColumn();
                ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?php echo $tab == 'validasi_selisih' ? 'active' : ''; ?>" href="?tab=validasi_selisih">
                <i class="fas fa-edit"></i> Edit Selisih
                <span class="badge"><?php
                    $stmt_count = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Validasi Keuangan SELISIH'");
                    echo $stmt_count->fetchColumn();
                ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?php echo $tab == 'dikembalikan_cs' ? 'active' : ''; ?>" href="?tab=dikembalikan_cs">
                <i class="fas fa-undo"></i> Dikembalikan ke CS
                <span class="badge badge-warning"><?php echo $total_dikembalikan; ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?php echo $tab == 'setor_bank' ? 'active' : ''; ?>" href="?tab=setor_bank">
                <i class="fas fa-university"></i> Aksi
                <span class="badge"><?php
                    $stmt_count = $pdo->query("SELECT COUNT(*) FROM kasir_transactions_closing_kasir kt LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran WHERE sk.status = 'Validasi Keuangan OK' AND kt.status = 'end proses' AND kt.deposit_status = 'Validasi Keuangan OK' AND COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) > 0");
                    echo $stmt_count->fetchColumn();
                ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?php echo $tab == 'histori_pengambilan_dana' ? 'active' : ''; ?>" href="?tab=histori_pengambilan_dana" style="color:#f59e0b;">
                <i class="fas fa-hand-holding-usd"></i> Histori Pengambilan
                <span class="badge" style="background:#f59e0b; color:#fff;"><?php
                    echo (int)(
                        $pengambilan_summary['proses']['cnt']
                        + $pengambilan_summary['hutang']['cnt']
                        + $pengambilan_summary['selesai']['cnt']
                    );
                ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?php echo $tab == 'histori_hutang' ? 'active' : ''; ?>" href="?tab=histori_hutang" style="color:#dc2626;">
                <i class="fas fa-file-invoice-dollar"></i> Histori Hutang
                <span class="badge" style="background:#dc2626; color:#fff;"><?php
                    echo (int)$pengambilan_summary['hutang']['cnt'];
                ?></span>
            </a>
        </div>
    </div>
    <?php endif; ?>


    <!-- Filter Card -->
    <?php if ($tab === 'histori_pengambilan_dana' || $tab === 'histori_hutang'): ?>
    <div class="filter-card">
        <form action="" method="GET" class="form-inline">
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <div class="form-group">
                <label class="form-label">Tanggal Awal:</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?php echo htmlspecialchars($tanggal_awal); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Akhir:</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
            </div>
            <?php if ($tab === 'histori_pengambilan_dana'): ?>
            <div class="form-group">
                <label class="form-label">Klasifikasi:</label>
                <select name="pengambilan_scope" class="form-control">
                    <option value="all" <?php echo $pengambilan_scope === 'all' ? 'selected' : ''; ?>>Semua</option>
                    <option value="internal" <?php echo $pengambilan_scope === 'internal' ? 'selected' : ''; ?>>Internal</option>
                    <option value="hutang" <?php echo $pengambilan_scope === 'hutang' ? 'selected' : ''; ?>>Hutang</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">Status:</label>
                <select name="pengambilan_status" class="form-control">
                    <option value="all" <?php echo $pengambilan_status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="proses" <?php echo $pengambilan_status === 'proses' ? 'selected' : ''; ?>>Proses</option>
                    <option value="hutang" <?php echo $pengambilan_status === 'hutang' ? 'selected' : ''; ?>>Hutang</option>
                    <option value="selesai" <?php echo $pengambilan_status === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="filter-card">
        <form action="" method="POST" class="form-inline">
            <input type="hidden" name="tab_filter" value="<?php echo $tab; ?>">
            <div class="form-group">
                <label class="form-label">Tanggal Awal:</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?php echo htmlspecialchars($tanggal_awal); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Akhir:</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
            </div>
            <?php if ($tab != 'setor_bank'): ?>
            <div class="form-group">
                <label class="form-label">Cabang:</label>
                <select name="cabang" class="form-control">
                    <option value="all">Semua Cabang</option>
                    <?php foreach ($cabang_list as $nama_cabang): ?>
                        <option value="<?php echo htmlspecialchars($nama_cabang); ?>" <?php echo $cabang == $nama_cabang ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($nama_cabang)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if ($tab == 'setor_bank'): ?>
            <div class="form-group">
                <label class="form-label">Rekening Cabang:</label>
                <select name="rekening_filter" class="form-control" onchange="filterByCabang(this.value)">
                    <option value="all">Pilih Rekening Cabang</option>
                    <?php foreach ($rekening_list as $rekening): ?>
                        <?php
                        $cabang_items = explode(';;', $rekening['cabang_info']);
                        $rekening_ids = explode(',', $rekening['rekening_ids']);
                        $cabang_names = array();
                        foreach ($cabang_items as $item) {
                            $parts = explode('|', $item);
                            if (count($parts) == 2) {
                                $cabang_names[] = $parts[0];
                            }
                        }
                        $cabang_display = '(' . implode('-', $cabang_names) . ')';
                        $jenis_badge = $rekening['jenis_rekening'] == 'Mitra' ? ' (MITRA)' : ' (MILIK SENDIRI)';
                        $all_rekening_ids = $rekening['rekening_ids'];
                        $display_text = $rekening['nama_bank'] . ' (' . $rekening['no_rekening'] . ') - ' . $cabang_display . $jenis_badge;
                        ?>
                        <option value="<?php echo htmlspecialchars($all_rekening_ids); ?>" <?php echo ($rekening_filter !== 'all' && !empty($rekening_filter) && ($rekening_filter == $all_rekening_ids || in_array($rekening_filter, explode(',', $all_rekening_ids)))) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($display_text); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Tab Content -->
    <?php if ($tab == 'terima'): ?>
    <div class="content-card">
        <div class="content-header">
            <h3><i class="fas fa-download"></i> Terima Setoran dari CS/Kasir Cabang</h3>
            <div class="export-buttons">
                <a href="export_excel_setoran.php?type=terima&tab=<?php echo $tab; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="export_csv.php?type=terima&tab=<?php echo $tab; ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="content-body">
            <div class="workflow-info">
                <h6><i class="fas fa-info-circle"></i> Informasi Workflow</h6>
                <p>Setoran yang sedang dibawa oleh kurir dan menunggu konfirmasi penerimaan dari staff keuangan pusat. Sistem mendukung penuh transaksi closing yang merupakan gabungan dari berbagai transaksi per cabang.</p>
            </div>
            
            <?php if ($setoran_list): ?>
            <div class="bulk-actions">
                <form action="" method="POST" id="terimaSetoranForm">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 8px; margin: 0;">
                            <input type="checkbox" id="selectAllTerima" class="form-check-input">
                            <span style="font-weight: 500;">Pilih Semua</span>
                        </label>
                        <button type="submit" name="terima_setoran" class="btn btn-success" onclick="return confirm('Yakin ingin menerima setoran yang dipilih?')">
                            <i class="fas fa-check"></i> Terima Setoran Terpilih
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="50">Pilih</th>
                                <th>Tanggal</th>
                                <th>Kode Setoran</th>
                                <th>Cabang</th>
                                <th>Kasir</th>
                                <th>Pengantar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($setoran_list): ?>
                                <?php foreach ($setoran_list as $row): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="setoran_ids[]" value="<?php echo $row['id']; ?>" 
                                                   class="terimaCheckbox form-check-input" form="terimaSetoranForm">
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_setoran'])); ?></td>
                                        <td><code><?php echo htmlspecialchars($row['kode_setoran']); ?></code></td>
                                        <td><?php echo htmlspecialchars(ucfirst($row['nama_cabang'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_karyawan']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_pengantar']); ?></td>
                                        <td>
                                            <span class="status-badge bg-info">Sedang Dibawa Kurir</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-inbox"></i><br>
                                        Tidak ada setoran yang perlu diterima
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>





    <!-- Tab Setor Bank - Updated with better UX -->
    <?php if ($tab == 'setor_bank'): ?>
    <div class="content-card">
        <div class="content-header">
            <h3><i class="fas fa-university"></i> Aksi</h3>
            <div class="export-buttons">
                <a href="export_excel_setoran.php?type=setor_bank&tab=<?php echo $tab; ?>&rekening_filter=<?php echo $rekening_filter; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="export_csv.php?type=setor_bank&tab=<?php echo $tab; ?>&rekening_filter=<?php echo $rekening_filter; ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="content-body">
            <div class="workflow-info">
                <h6><i class="fas fa-info-circle"></i> Informasi Workflow</h6>
                <p>Transaksi closing yang sudah divalidasi tanpa selisih dan siap diproses. Dari tab ini admin keuangan bisa memilih setor penuh ke bank atau ambil sebagian untuk pengadaan, lalu seluruh histori akan otomatis terhubung ke tab pengambilan dan hutang.</p>
            </div>

            <?php if ($rekening_filter == 'all' || empty($setoran_list)): ?>
            <div class="no-setoran-message">
                <h6><i class="fas fa-exclamation-triangle"></i> Pilih Rekening Cabang</h6>
                <p>Silakan pilih rekening cabang di filter untuk menampilkan transaksi closing yang siap disetor ke bank dari cabang tersebut.</p>
            </div>
            <?php endif; ?>
            
            <form action="" method="POST" id="setorBankForm">
                <?php if ($rekening_filter !== 'all' && !empty($rekening_filter)): ?>
                    <!-- Rekening sudah dipilih dari filter, otomatis set sebagai tujuan -->
                    <input type="hidden" name="rekening_cabang_id" value="<?php echo htmlspecialchars($rekening_filter); ?>">
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                        <strong>Rekening Tujuan:</strong> Otomatis menggunakan rekening yang dipilih dari filter di atas.
                    </div>
                <?php else: ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Rekening Cabang Tujuan <span class="required">*</span></label>
                        <select name="rekening_cabang_id" id="rekeningCabang" class="form-control" required>
                            <option value="">Pilih Rekening Cabang (Gabungan: <?php echo date('H:i:s'); ?>)</option>
                            <?php foreach ($rekening_list as $rekening): ?>
                                <?php
                                // Parse cabang info to get all cabang names and IDs
                                $cabang_items = explode(';;', $rekening['cabang_info']);
                                $rekening_ids = explode(',', $rekening['rekening_ids']);
                                $cabang_names = array();
                                foreach ($cabang_items as $item) {
                                    $parts = explode('|', $item);
                                    if (count($parts) == 2) {
                                        $cabang_names[] = $parts[0];
                                    }
                                }
                                $cabang_display = '(' . implode('-', $cabang_names) . ')';
                                $jenis_badge = $rekening['jenis_rekening'] == 'Mitra' ? ' (MITRA)' : ' (MILIK SENDIRI)';
                                
                                // Use all rekening IDs as comma separated values for the option
                                $all_rekening_ids = $rekening['rekening_ids'];
                                
                                // Format: Nama Bank (No Rek) - (Cabang1-Cabang2) (Jenis)
                                $display_text = $rekening['nama_bank'] . ' (' . $rekening['no_rekening'] . ') - ' . $cabang_display . $jenis_badge;
                                ?>
                                <option value="<?php echo htmlspecialchars($all_rekening_ids); ?>">
                                    <?php echo htmlspecialchars($display_text); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-muted); font-size: 12px; margin-top: 5px;">
                            Pilih rekening tujuan. Sistem akan menampilkan setoran dari semua cabang yang menggunakan nomor rekening yang sama.
                        </small>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="tanggal_setoran" id="tanggalSetoranMainInput" value="<?php echo date('Y-m-d'); ?>">

                <?php /* P1/P10: lock dihapus — tidak ada alert block di sini */ ?>

                <?php if ($rekening_filter !== 'all' && !empty($setoran_list)): ?>
                <div class="alert alert-info show" style="margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i>
                    Menampilkan transaksi closing dari cabang yang sesuai dengan rekening yang dipilih. Total transaksi closing yang dapat disetor: <strong><?php echo count($setoran_list); ?> transaksi</strong>
                </div>

                <div class="table-container">
                    <div class="table-wrapper" id="setorBankTableWrapper" style="overflow-x: scroll !important; overflow-y: visible !important; max-width: 100%; width: 100%; border: 1px solid #dee2e6; border-radius: 8px; scrollbar-width: thick; scrollbar-color: #dc3545 #f8f9fa;">
                        <div class="table-enhanced" style="min-width: 1800px; width: 1800px;">
                            <table class="table" style="min-width: 1800px !important; width: 1800px !important; white-space: nowrap; table-layout: fixed;">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAllBank">
                                        </th>
                                        <th>Tanggal Closing</th>
                                        <th>Kode Transaksi</th>
                                        <th>Kode Setoran</th>
                                        <th>Cabang</th>
                                        <th>Setoran Real</th>
                                        <th>Kas Masuk</th>
                                        <th>Pengambilan Dana</th>
                                        <th>Netto ke Bank</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_all_setoran = 0;
                                    $total_all_kas_masuk = 0;
                                    $total_all_diambil  = 0;
                                    $total_all_netto    = 0;
                                    foreach ($setoran_list as $row):
                                        $setoranReal = (float)($row['setoran_real'] ?? 0);
                                        $kasMasukRow = max(0, (float)($row['total_closing_borrowed'] ?? 0));
                                        $saldoSiapSetor = (float)($row['saldo_siap_setor'] ?? $setoranReal);
                                        $closingId   = (int)($row['id'] ?? 0);
                                        $pengInfoList = (!empty($closingId) && isset($pengambilan_info_by_closing_id[$closingId]))
                                            ? $pengambilan_info_by_closing_id[$closingId] : [];
                                        $nominalDiambilRow = 0;
                                        foreach ($pengInfoList as $pi) {
                                            $nominalDiambilRow += (float)($pi['nominal_diambil'] ?? 0);
                                        }
                                        $nettoRow = $saldoSiapSetor;
                                        $total_all_setoran += $setoranReal;
                                        $total_all_kas_masuk += $kasMasukRow;
                                        $total_all_diambil  += $nominalDiambilRow;
                                        $total_all_netto    += $nettoRow;
                                        $hasPengambilan = !empty($pengInfoList);
                                        $isClosingRow = (($row['jenis_transaksi'] ?? '') === 'DARI CLOSING');
                                        $displayPengInfoList = $isClosingRow ? $pengInfoList : [];
                                        $hasDisplayPengambilan = !empty($displayPengInfoList);
                                    ?>
                                        <tr class="<?php echo $isClosingRow ? 'closing-transaction' : ''; ?>" data-kode-transaksi="<?php echo htmlspecialchars((string)($row['kode_transaksi'] ?? '')); ?>" data-kode-setoran="<?php echo htmlspecialchars((string)($row['kode_setoran'] ?? '')); ?>" data-setoran-gross="<?php echo htmlspecialchars((string)round($setoranReal)); ?>" data-kas-masuk="<?php echo htmlspecialchars((string)round($kasMasukRow)); ?>" data-netto-setor="<?php echo htmlspecialchars((string)round($nettoRow)); ?>" style="<?php echo $hasDisplayPengambilan ? 'border-left: 4px solid #f59e0b;' : ''; ?>">
                                            <td><input type="checkbox" name="closing_ids[]" value="<?php echo $row['id']; ?>" class="bankCheckbox"></td>
                                            <td><?php 
                                                $tgl_closing = $row['tanggal_closing'];
                                                $jam_closing = $row['jam_closing'];
                                                if ($tgl_closing) {
                                                    echo date('d/m/Y', strtotime($tgl_closing));
                                                    if ($jam_closing) {
                                                        echo '<br><small>' . $jam_closing . '</small>';
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                            ?></td>

                                            <td>
                                                <code><?php echo htmlspecialchars($row['kode_transaksi']); ?></code>
                                            </td>
                                            <td><code style="font-size: 11px;"><?php echo htmlspecialchars($row['kode_setoran']); ?></code></td>
                                            <td><?php echo htmlspecialchars(ucfirst($row['resolved_nama_cabang'] ?? $row['nama_cabang'])); ?></td>
                                            <td style="text-align: right; font-weight: 600;"><?php echo formatRupiah($setoranReal); ?></td>
                                            <td style="text-align: right; font-weight: 600;"><?php echo formatRupiah($kasMasukRow); ?></td>
                                            <td style="text-align: right; background:<?php echo $hasDisplayPengambilan ? '#fff7ed' : 'transparent'; ?>;">
                                                <?php if ($hasDisplayPengambilan): ?>
                                                    <?php foreach ($displayPengInfoList as $pengInfo):
                                                        $namaCabangPenerima = trim((string)($pengInfo['nama_cabang_penerima'] ?? ''));
                                                        $kodeCabangPenerima = trim((string)($pengInfo['kode_cabang_penerima'] ?? ''));
                                                        $labelCabangPenerima = $namaCabangPenerima !== '' ? $namaCabangPenerima
                                                            : ($kodeCabangPenerima !== '' ? $kodeCabangPenerima : '-');
                                                    ?>
                                                    <div style="font-size:12px; line-height:1.4; color:#92400e; margin-bottom:4px;">
                                                        <strong style="color:#b45309;"><?php echo formatRupiah((float)($pengInfo['nominal_diambil'] ?? 0)); ?></strong><br>
                                                        <span style="font-size:11px;"><?php echo htmlspecialchars((string)($pengInfo['kode_pengambilan'] ?? '')); ?></span><br>
                                                        <span style="font-size:11px;">→ <?php echo htmlspecialchars($labelCabangPenerima); ?></span>
                                                    </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span style="color:#94a3b8; font-size:12px;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right; font-weight: 700; background:#f0fdf4; color:<?php echo $hasPengambilan ? '#166534' : '#0f172a'; ?>;">
                                                <?php echo formatRupiah($nettoRow); ?>
                                                <?php if ($hasPengambilan): ?>
                                                    <br><small style="font-size:10px; font-weight:400; color:#64748b;">Saldo siap setor</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $dStat = $row['deposit_status'];
                                                if (strtolower($dStat) === 'lengkap') $dStat = 'Selesai';
                                                ?>
                                                <span class="status-badge bg-success"><?php echo htmlspecialchars($dStat); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background: #f8fafc; font-weight: bold;">
                                        <td colspan="5" style="text-align: right; padding-right:12px;">Total Gross Setoran:</td>
                                        <td style="text-align: right; color: #0f172a;"><?php echo formatRupiah($total_all_setoran); ?></td>
                                        <td style="text-align: right; color: #0f172a;"><?php echo formatRupiah($total_all_kas_masuk); ?></td>
                                        <td style="text-align: right; background:#fff7ed; color:#b45309;"><?php echo formatRupiah($total_all_diambil); ?></td>
                                        <td style="text-align: right; background:#f0fdf4; color:#166534; font-size:15px;"><?php echo formatRupiah($total_all_netto); ?></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <style>
                    /* Force scrollbar untuk setor bank table - Super specific */
                    #setorBankTableWrapper {
                        overflow-x: scroll !important;
                        overflow-y: visible !important;
                        max-width: 100% !important;
                        scrollbar-width: thick !important;
                        scrollbar-color: #dc3545 #f8f9fa;
                    }
                    
                    #setorBankTableWrapper::-webkit-scrollbar {
                        height: 16px !important;
                        background: #f1f1f1 !important;
                        border: 2px solid #ccc !important;
                        border-radius: 8px !important;
                        -webkit-appearance: none !important;
                        display: block !important;
                    }
                    
                    #setorBankTableWrapper::-webkit-scrollbar-track {
                        background: #e0e0e0 !important;
                        border-radius: 8px !important;
                        border: 1px solid #bbb !important;
                    }
                    
                    #setorBankTableWrapper::-webkit-scrollbar-thumb {
                        background: #dc3545 !important;
                        border-radius: 8px !important;
                        border: 2px solid #fff !important;
                        min-width: 40px !important;
                        -webkit-appearance: none !important;
                    }
                    
                    #setorBankTableWrapper::-webkit-scrollbar-thumb:hover {
                        background: #c82333 !important;
                        cursor: grab !important;
                    }
                    
                    #setorBankTableWrapper::-webkit-scrollbar-thumb:active {
                        background: #a71e2a !important;
                        cursor: grabbing !important;
                    }
                </style>

                <script>
                    // Force scrollbar visibility on page load
                    document.addEventListener('DOMContentLoaded', function() {
                        const wrapper = document.getElementById('setorBankTableWrapper');
                        if (wrapper) {
                            wrapper.style.overflowX = 'scroll';
                            wrapper.style.overflowY = 'visible';
                            wrapper.style.maxWidth = '100%';
                            wrapper.style.width = '100%';
                            
                            // Force redraw
                            setTimeout(() => {
                                wrapper.style.display = 'none';
                                wrapper.offsetHeight; // trigger reflow
                                wrapper.style.display = 'block';
                            }, 100);
                        }
                    });
                </script>

                <div style="margin-top: 20px;">
                    <button
                        type="button"
                        class="btn btn-success"
                        onclick="showSetoranPilihanModal()"
                        title="Pilih aksi untuk setoran terpilih">
                        <i class="fas fa-university"></i> Aksi
                    </button>
                    <button type="submit" name="setor_bank" id="setor_bank_hidden_submit" class="btn btn-success" style="display:none;">
                        <i class="fas fa-university"></i> Klik Validasi
                    </button>
                    <button type="button" class="btn btn-info" onclick="showSetoranSummary()">
                        <i class="fas fa-calculator"></i> Lihat Ringkasan
                    </button>
                </div>
                <?php endif; ?>
            </form>

            <!-- Modal Pilihan Setoran -->
            <div id="setoranPilihanModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center;">
              <div style="background:#fff; border-radius:12px; padding:32px; max-width:560px; width:92%; box-shadow:0 20px 60px rgba(0,0,0,0.35);">
                <h4 style="margin-bottom:8px; color:#1e293b;"><i class="fas fa-university"></i> Aksi Setoran</h4>
                <p style="color:#64748b; margin-bottom:18px; font-size:14px;">Semua histori pengambilan dan hutang akan terintegrasi otomatis dari proses ini.</p>

                <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px; margin-bottom:16px;">
                  <div style="font-size:12px; color:#16a34a; font-weight:600;">TOTAL SETORAN AWAL TERPILIH</div>
                  <div id="totalSetoranDisplay" style="font-size:20px; font-weight:700; color:#16a34a;">Rp 0</div>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                  <label style="display:flex; gap:10px; align-items:flex-start; border:1px solid #cbd5e1; border-radius:10px; padding:12px; cursor:pointer;">
                    <input type="radio" name="aksi_setoran_mode" value="full" checked onchange="toggleAksiSetoranMode()">
                    <span>
                      <strong>Setor Penuh ke Bank</strong>
                      <div style="font-size:13px; color:#64748b; margin-top:2px;">Seluruh nominal masuk ke riwayat setoran bank.</div>
                    </span>
                  </label>
                  <label style="display:flex; gap:10px; align-items:flex-start; border:1px solid #fbbf24; border-radius:10px; padding:12px; cursor:pointer; background:#fffbeb;">
                    <input type="radio" name="aksi_setoran_mode" value="partial" onchange="toggleAksiSetoranMode()">
                    <span>
                      <strong>Ambil Sebagian</strong>
                      <div style="font-size:13px; color:#78350f; margin-top:2px;">Nominal diambil untuk pengadaan, sisa otomatis masuk ke bank.</div>
                    </span>
                  </label>
                </div>

                <!-- Tanggal Setor ke Penyetor — hanya tampil saat mode Setor Penuh ke Bank -->
                <div id="setoranPenuhDateSection" style="margin-bottom:14px;">
                  <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Tanggal Setor ke Penyetor <span style="color:red">*</span></label>
                  <input type="date" id="tanggalPerencanaanSetor" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div id="ambilSebagianForm" style="display:none; border-top:1px solid #e2e8f0; padding-top:16px;">
                  <h6 style="margin-bottom:14px; color:#1e293b;"><i class="fas fa-calculator"></i> Detail Pengambilan</h6>
                  <div style="margin-bottom:12px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Tanggal Pengambilan Dana <span style="color:red">*</span></label>
                    <input type="date" id="tanggalSetorKeBank" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    <small style="color:#64748b; font-size:12px;">Tanggal dana diambil dan diserahkan ke cabang penerima.</small>
                  </div>
                  <div style="margin-bottom:12px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Cabang Penerima <span style="color:red">*</span></label>
                    <select id="kodeCabangPenerima" class="form-control" onchange="validateCabangPenerimaLock()">
                      <option value="">Pilih cabang penerima</option>
                      <?php foreach ($cabang_penerima_options as $option): ?>
                      <option value="<?php echo htmlspecialchars($option['kode_cabang']); ?>">
                        <?php echo htmlspecialchars($option['nama_cabang']); ?> - <?php echo htmlspecialchars($option['nama_bank']); ?> (<?php echo htmlspecialchars($option['no_rekening']); ?>)
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div id="cabangPenerimaLockNotice" style="display:none; margin-bottom:12px; background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; border-radius:8px; padding:10px 12px; font-size:13px;"></div>
                  <div style="margin-bottom:12px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Nominal Diambil <span style="color:red">*</span></label>
                    <input type="number" id="nominalDiambil" class="form-control" placeholder="0" min="1" step="1" oninput="updateSisaSetoran()" style="font-size:15px;">
                  </div>
                  <div style="background:#eff6ff; border:1px solid #93c5fd; border-radius:8px; padding:12px; margin-bottom:12px;">
                    <div style="font-size:12px; color:#1d4ed8; font-weight:600;">SISA KE BANK</div>
                    <div id="sisaSetoranDisplay" style="font-size:18px; font-weight:700; color:#1d4ed8;">Rp 0</div>
                  </div>
                  <div style="margin-bottom:14px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:4px;">Keterangan</label>
                    <input type="text" id="keteranganAmbil" class="form-control" placeholder="Contoh: kebutuhan pengadaan stok" maxlength="255">
                  </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                  <button class="btn btn-success" id="modalKlikValidasiButton" style="flex:1;" onclick="submitAksiSetoran()">
                    <i class="fas fa-check-circle"></i> Klik Validasi
                  </button>
                  <button class="btn btn-secondary" onclick="closeSetoranPilihanModal()">Batal</button>
                </div>
              </div>
            </div>

            <!-- Hidden form untuk submit ambil sebagian -->
            <form id="ambilSebagianHiddenForm" method="POST" action="?tab=setor_bank" style="display:none;">
              <input type="hidden" name="closing_ids_ambil" id="closingIdsAmbil">
              <input type="hidden" name="rekening_cabang_id_ambil" id="rekeningCabangIdAmbil">
              <input type="hidden" name="tanggal_setoran_ambil" id="tanggalSetoranAmbil">
              <input type="hidden" name="nominal_diambil" id="nominalDiambilHidden">
              <input type="hidden" name="nominal_sisa" id="nominalSisaHidden">
              <input type="hidden" name="kode_cabang_penerima" id="kodeCabangPenerimaHidden">
              <input type="hidden" name="keterangan_ambil" id="keteranganAmbilHidden">
              <input type="hidden" name="tanggal_setor_bank_ambil" id="tanggalSetorBankHidden">
              <input type="hidden" name="ambil_sebagian_pengadaan" value="1">
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tab == 'histori_pengambilan_dana'): ?>
    <div class="content-card">
        <div class="content-header">
            <h3><i class="fas fa-hand-holding-usd"></i> Histori Pengambilan Dana</h3>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <span class="badge" style="background:#f8fafc; color:#334155;">Semua: <?php echo count($pengambilan_history_rows); ?></span>
                <span class="badge" style="background:#fffbeb; color:#92400e;">Internal: <?php echo count(array_filter($pengambilan_history_rows, fn($item) => ($item['resolved_klasifikasi'] ?? '') === 'internal')); ?></span>
                <span class="badge" style="background:#fee2e2; color:#991b1b;">Hutang: <?php echo count(array_filter($pengambilan_history_rows, fn($item) => ($item['resolved_klasifikasi'] ?? '') === 'hutang')); ?></span>
            </div>
        </div>
        <div class="content-body">
            <div class="workflow-info">
                <h6><i class="fas fa-info-circle"></i> Informasi Workflow</h6>
                <p>Semua record pengambilan dana, baik internal maupun hutang, dipantau di sini. Kode validasi yang dipakai kasir penerima akan otomatis mengisi timeline pembayaran dan checklist nominal cabang.</p>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:18px;">
                <div style="padding:14px 16px; border-radius:14px; border:1px solid #e2e8f0; background:#fff;">
                    <div style="font-size:12px; color:#64748b; text-transform:uppercase; letter-spacing:.05em;">Total Proses</div>
                    <div style="margin-top:6px; font-size:24px; font-weight:700; color:#0f172a;"><?php echo (int)$pengambilan_summary['proses']['cnt']; ?></div>
                    <div style="margin-top:4px; font-size:13px; color:#475569;">Pengambilan internal yang belum selesai.</div>
                </div>
                <div style="padding:14px 16px; border-radius:14px; border:1px solid #fecaca; background:#fff7ed;">
                    <div style="font-size:12px; color:#991b1b; text-transform:uppercase; letter-spacing:.05em;">Total Hutang</div>
                    <div style="margin-top:6px; font-size:24px; font-weight:700; color:#991b1b;"><?php echo (int)$pengambilan_summary['hutang']['cnt']; ?></div>
                    <div style="margin-top:4px; font-size:13px; color:#7c2d12;">Memerlukan pelunasan transfer rekening.</div>
                </div>
                <div style="padding:14px 16px; border-radius:14px; border:1px solid #bbf7d0; background:#f0fdf4;">
                    <div style="font-size:12px; color:#166534; text-transform:uppercase; letter-spacing:.05em;">Total Selesai</div>
                    <div style="margin-top:6px; font-size:24px; font-weight:700; color:#166534;"><?php echo (int)$pengambilan_summary['selesai']['cnt']; ?></div>
                    <div style="margin-top:4px; font-size:13px; color:#166534;">Semua proses selesai.</div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-wrapper">
                    <table class="table" style="min-width: 1200px;">
                        <thead>
                            <tr>
                                <th style="width:170px;">Kode & Tanggal</th>
                                <th style="width:220px;">Pemberi</th>
                                <th style="width:180px;">Penerima</th>
                                <th style="width:150px;">Nominal</th>
                                <th style="width:230px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pengambilan_history_rows)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:var(--text-muted); padding:30px;">
                                    Belum ada histori pengambilan dana.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($pengambilan_history_rows as $row): ?>
                            <?php
                                $badgeClass = $row['status'] === 'selesai' ? 'status-badge bg-success' : ($row['status'] === 'hutang' ? 'status-badge bg-danger' : 'status-badge bg-warning');
                                $klasifikasiColor = $row['resolved_klasifikasi'] === 'hutang' ? '#dc2626' : '#16a34a';
                                $isChildRow = !empty($row['is_child']);
                                $isEditNominalLocked = isPengambilanNominalLocked($row);

                                // Parse TRX refs dari keterangan — ID numerik, lookup ke kode_transaksi
                                $refTrxKodes = [];
                                if (!empty($row['keterangan']) && preg_match('/\[Ref TRX:\s*([^\]]+)\]/i', $row['keterangan'], $m)) {
                                    $numericIds = array_values(array_filter(array_map('trim', explode(',', $m[1]))));
                                    if (!empty($numericIds)) {
                                        $ph = implode(',', array_fill(0, count($numericIds), '?'));
                                        $stmtTrx = $pdo->prepare("SELECT id, kode_transaksi FROM kasir_transactions_closing_kasir WHERE id IN ($ph)");
                                        $stmtTrx->execute($numericIds);
                                        $trxMap = $stmtTrx->fetchAll(PDO::FETCH_KEY_PAIR);
                                        foreach ($numericIds as $id) {
                                            $refTrxKodes[] = $trxMap[$id] ?? "TRX#$id";
                                        }
                                    }
                                }
                                $keteranganBersih = preg_replace('/\[Ref TRX:[^\]]+\]/i', '', $row['keterangan'] ?? '');
                            ?>
                            <tr>
                                <!-- Kolom 1: Kode & Tanggal -->
                                <td>
                                    <code style="font-size:12px;"><?php echo htmlspecialchars($row['kode_pengambilan']); ?></code>
                                    <?php if ($isChildRow): ?>
                                    <div style="font-size:11px; color:#64748b; margin-top:3px;">Child dari <?php echo htmlspecialchars($row['parent_kode_pengambilan']); ?></div>
                                    <?php endif; ?>
                                    <div style="font-size:13px; color:#0f172a; margin-top:5px; font-weight:600;">
                                        <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                    </div>
                                    <div style="font-size:11px; color:#64748b;">
                                        <?php echo date('H:i', strtotime($row['created_at'])); ?>
                                    </div>
                                    <?php if (!$isChildRow): ?>
                                    <div style="margin-top:8px; display:flex; flex-direction:column; gap:5px;">
                                        <?php if (!$isEditNominalLocked): ?>
                                        <button type="button"
                                                onclick='openEditNominalKeuangan(<?php echo json_encode($row['kode_pengambilan']); ?>, <?php echo json_encode((float)$row['nominal_diambil']); ?>)'
                                                style="padding:5px 9px;border:1px solid #f59e0b;background:#fffbeb;color:#b45309;border-radius:8px;cursor:pointer;font-size:11px;font-weight:700;">
                                            <i class="fas fa-edit"></i> Edit Nominal
                                        </button>
                                        <?php else: ?>
                                        <span style="display:inline-block;padding:4px 8px;border-radius:8px;background:<?php echo !empty($row['verified_cabang_penerima_sudah_closing']) || !empty($row['verified_cabang_closing_at']) ? '#eff6ff' : '#f0fdf4'; ?>;color:<?php echo !empty($row['verified_cabang_penerima_sudah_closing']) || !empty($row['verified_cabang_closing_at']) ? '#1d4ed8' : '#166534'; ?>;font-size:11px;font-weight:700;border:1px solid <?php echo !empty($row['verified_cabang_penerima_sudah_closing']) || !empty($row['verified_cabang_closing_at']) ? '#93c5fd' : '#bbf7d0'; ?>;">
                                            <?php echo !empty($row['verified_cabang_penerima_sudah_closing']) || !empty($row['verified_cabang_closing_at']) ? 'Terkunci: Sudah Closing' : 'Sudah Disetor'; ?>
                                        </span>
                                        <?php endif; ?>
                                        <a href="print_serah_terima_pengambilan.php?kode=<?php echo urlencode($row['kode_pengambilan']); ?>"
                                           target="_blank"
                                           style="display:inline-block;padding:5px 9px;border:1px solid #0ea5e9;background:#f0f9ff;color:#0369a1;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Kolom 2: Pemberi -->
                                <td style="vertical-align:top;">
                                    <div style="font-weight:600;color:#0f172a;margin-bottom:4px;font-size:12px;">
                                        <?php echo htmlspecialchars($row['no_rekening_peminjam'] ?? '-'); ?>
                                    </div>
                                    <?php if (!empty($refTrxKodes)): ?>
                                    <div style="margin-top:5px;">
                                        <div style="font-size:11px;color:#64748b;font-weight:600;margin-bottom:2px;">
                                            Transaksi diambil (<?php echo count($refTrxKodes); ?>):
                                        </div>
                                        <?php $maxShow = 2; ?>
                                        <?php foreach (array_slice($refTrxKodes, 0, $maxShow) as $kode): ?>
                                        <div style="font-size:11px;color:#1d4ed8;font-family:monospace;line-height:1.6;">
                                            • <?php echo htmlspecialchars($kode); ?>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (count($refTrxKodes) > $maxShow): ?>
                                        <?php $sisaTrx = count($refTrxKodes) - $maxShow; $trxUid = 'trx_' . $row['kode_pengambilan']; ?>
                                        <div style="font-size:11px;color:#2563eb;margin-top:2px;cursor:pointer;font-weight:600;"
                                             onclick="var n=document.getElementById('<?php echo $trxUid; ?>');var show=n.style.display==='none';n.style.display=show?'block':'none';this.textContent=show?'[Sembunyikan ▲]':'[+<?php echo $sisaTrx; ?> lainnya ▼]';">
                                            [+<?php echo $sisaTrx; ?> lainnya ▼]
                                        </div>
                                        <div id="<?php echo $trxUid; ?>" style="display:none;">
                                            <?php foreach (array_slice($refTrxKodes, $maxShow) as $kode): ?>
                                            <div style="font-size:11px;color:#1d4ed8;font-family:monospace;line-height:1.6;">
                                                • <?php echo htmlspecialchars($kode); ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div style="font-size:11px;color:#94a3b8;font-style:italic;">Tidak ada TRX terkait</div>
                                    <?php endif; ?>
                                </td>

                                <!-- Kolom 3: Penerima -->
                                <td style="vertical-align:top;">
                                    <div style="font-weight:600;color:#0f172a;margin-bottom:4px;font-size:12px;">
                                        <?php echo htmlspecialchars($row['no_rekening_penerima'] ?? '-'); ?>
                                    </div>
                                    <div style="font-size:11px;color:#475569;margin-bottom:5px;">
                                        <i class="fas fa-building" style="color:#94a3b8;"></i>
                                        <?php echo htmlspecialchars($row['nama_cabang_penerima'] ?? '-'); ?>
                                    </div>
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;
                                        background:<?php echo $row['resolved_klasifikasi']==='hutang'?'#fef2f2':'#f0fdf4'; ?>;
                                        color:<?php echo $klasifikasiColor; ?>;
                                        border:1px solid <?php echo $row['resolved_klasifikasi']==='hutang'?'#fecaca':'#bbf7d0'; ?>;">
                                        <i class="fas fa-<?php echo $row['resolved_klasifikasi']==='hutang'?'file-invoice-dollar':'building-columns'; ?>"></i>
                                        <?php echo strtoupper($row['resolved_klasifikasi']); ?>
                                    </span>
                                </td>

                                <!-- Kolom 3: Nominal -->
                                <td style="font-weight:700; color:#16a34a;">
                                    <?php echo formatRupiah($row['nominal_diambil']); ?>
                                    <div style="font-size:12px; color:#64748b; margin-top:4px; font-weight:400;">
                                        Terbayar: <?php echo formatRupiah($row['nominal_terbayar']); ?>
                                    </div>
                                    <?php if (($row['resolved_klasifikasi'] ?? '') === 'hutang' && !$isChildRow): ?>
                                    <div style="font-size:12px; color:#dc2626; margin-top:4px; font-weight:600;">
                                        Sisa hutang: <?php echo formatRupiah($row['nominal_sisa_hutang']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Kolom 4: Status dinamis -->
                                <td style="max-width:240px; word-break:break-word; white-space:normal;">
                                    <?php
                                    $vNominal  = !empty($row['verified_nominal_kas_masuk']);
                                    $vNotrx    = !empty($row['verified_notrx_kas_masuk']);
                                    $vClosing  = !empty($row['verified_cabang_penerima_sudah_closing']);
                                    $vMutasi   = !empty($row['verified_mutasi_bank']);
                                    $ssBank    = $row['status_setor_bank'] ?? 'belum';
                                    $ssBadge   = ['belum'=>'#f59e0b','siap'=>'#2563eb','disetor'=>'#16a34a'][$ssBank] ?? '#94a3b8';
                                    $ssLabel   = ['belum'=>'Belum Setor','siap'=>'Siap Setor','disetor'=>'Sudah Disetor'][$ssBank] ?? $ssBank;
                                    $isHutang  = ($row['resolved_klasifikasi'] ?? '') === 'hutang';
                                    ?>
                                    <div style="display:flex; flex-direction:column; gap:5px; font-size:12px;">

                                        <?php /* --- Nominal Kas Masuk --- */ ?>
                                        <div>
                                            <span>
                                                <i class="fas fa-<?php echo $vNominal ? 'check-circle' : 'times-circle'; ?>"
                                                   style="color:<?php echo $vNominal ? '#16a34a' : '#dc2626'; ?>;"></i>
                                                Nominal Kas Masuk
                                            </span>
                                            <?php if ($vNominal): ?>
                                                <div style="margin-top:3px;font-size:11px;color:#166534;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:3px 7px;font-weight:700;">
                                                    <?php echo formatRupiah($row['jumlah_pemasukan_aktual'] ?? 0); ?>
                                                </div>
                                            <?php else: ?>
                                                <?php if ($isHutang && $vNotrx): ?>
                                                    <div style="margin-top:3px;font-size:11px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:3px 7px;">
                                                        <i class="fas fa-clock"></i> Menunggu keuangan upload mutasi pelunasan
                                                    </div>
                                                <?php elseif ($isHutang && !$vNotrx): ?>
                                                    <div style="margin-top:3px;font-size:11px;color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:3px 7px;">
                                                        <i class="fas fa-exclamation-circle"></i> Kasir belum input pemasukan
                                                    </div>
                                                <?php else: ?>
                                                    <div style="margin-top:3px;font-size:11px;color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:3px 7px;">
                                                        <i class="fas fa-exclamation-circle"></i> Kasir belum input / nominal belum lunas
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                        <?php /* --- No Trx Kas Masuk --- */ ?>
                                        <div>
                                            <span>
                                                <i class="fas fa-<?php echo $vNotrx ? 'check-circle' : 'times-circle'; ?>"
                                                   style="color:<?php echo $vNotrx ? '#16a34a' : '#dc2626'; ?>;"></i>
                                                No Trx Kas Masuk
                                            </span>
                                            <?php if ($vNotrx): ?>
                                                <div style="margin-top:3px;font-size:11px;color:#1d4ed8;background:#eff6ff;border:1px solid #93c5fd;border-radius:6px;padding:3px 7px;font-weight:600;font-family:monospace;">
                                                    <?php echo htmlspecialchars($row['no_trx_pemasukan_aktual'] ?? '-'); ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="margin-top:3px;font-size:11px;color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:3px 7px;">
                                                    <i class="fas fa-exclamation-circle"></i> Kasir belum input pemasukan untuk kode ini
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php /* --- Cabang Penerima Closing --- */ ?>
                                        <div>
                                            <span>
                                                <i class="fas fa-<?php echo $vClosing ? 'check-circle' : 'times-circle'; ?>"
                                                   style="color:<?php echo $vClosing ? '#16a34a' : '#dc2626'; ?>;"></i>
                                                Cabang Penerima Sudah Closing
                                            </span>
                                            <?php if ($vClosing): ?>
                                                <div style="margin-top:3px;font-size:11px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:3px 7px;font-weight:600;">
                                                    SUDAH CLOSING <?php
                                                        $closingVerifiedAt = $row['resolved_verified_cabang_closing_at'] ?? ($row['verified_cabang_closing_at'] ?? null);
                                                        echo !empty($closingVerifiedAt) ? date('d/m/Y H:i', strtotime($closingVerifiedAt)) : 'Sudah Closing';
                                                    ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="margin-top:3px;font-size:11px;color:#92400e;background:#fff7ed;border:1px solid #fdba74;border-radius:6px;padding:3px 7px;">
                                                    <i class="fas fa-hourglass-half"></i> Menunggu closing dari cabang penerima
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php /* --- Pelunasan Hutang (hutang only) --- */ ?>
                                        <?php if ($isHutang && !$isChildRow): ?>
                                        <div>
                                            <span>
                                                <i class="fas fa-<?php echo $vMutasi ? 'check-circle' : 'times-circle'; ?>"
                                                   style="color:<?php echo $vMutasi ? '#16a34a' : '#dc2626'; ?>;"></i>
                                                Pelunasan Hutang
                                            </span>
                                            <?php if ($vMutasi): ?>
                                                <?php
                                                // Tentukan identifier: no_transaksi jika ada, fallback ke kode_validasi_input
                                                $kodePln         = trim($row['kode_validasi_pelunasan'] ?? '');
                                                $noRefBank       = trim($row['no_transaksi_pelunasan'] ?? '');
                                                $tglTransfer     = $row['tanggal_transfer_pelunasan'] ?? '';
                                                $nominalDibayar  = $row['nominal_dibayar_pelunasan'] ?? null;
                                                $sumberPelunasan = $row['sumber_pelunasan'] ?? '';
                                                ?>
                                                <div style="margin-top:3px;font-size:11px;color:#5b21b6;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:6px;padding:4px 7px;word-break:break-all;">
                                                    <?php if ($kodePln): ?>
                                                    <div style="font-weight:700;font-family:monospace;color:#4c1d95;font-size:12px;">
                                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($kodePln); ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($noRefBank): ?>
                                                    <div style="color:#6b21a8;margin-top:2px;">Ref Bank: <?php echo htmlspecialchars($noRefBank); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($tglTransfer): ?>
                                                    <div style="color:#6b21a8;">Tgl: <?php echo date('d/m/Y', strtotime($tglTransfer)); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($nominalDibayar): ?>
                                                    <div style="color:#6b21a8;">Nominal: <?php echo formatRupiah($nominalDibayar); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="margin-top:3px;font-size:11px;color:#6b21a8;background:#faf5ff;border:1px solid #d8b4fe;border-radius:6px;padding:3px 7px;">
                                                    <i class="fas fa-upload"></i> Keuangan perlu upload bukti mutasi di tab Histori Hutang
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

                                        <span style="margin-top:4px;display:inline-block;padding:3px 8px;border-radius:999px;background:<?php echo $ssBadge; ?>22;color:<?php echo $ssBadge; ?>;font-weight:700;font-size:11px;border:1px solid <?php echo $ssBadge; ?>44;"><?php echo $ssLabel; ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tab == 'histori_hutang'): ?>
    <div class="content-card">
        <div class="content-header">
            <h3><i class="fas fa-file-invoice-dollar"></i> Histori Hutang</h3>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <span class="badge" style="background:#fee2e2; color:#991b1b;">Total Hutang: <?php echo count($pengambilan_hutang_rows); ?></span>
                <a href="histori_gudang.php" class="btn btn-sm btn-outline-danger" style="text-decoration:none; border:1px solid #dc2626; color:#dc2626; padding:8px 12px; border-radius:8px;">
                    <i class="fas fa-warehouse"></i> Buka Histori Gudang
                </a>
            </div>
        </div>
        <div class="content-body">
            <div class="workflow-info">
                <h6><i class="fas fa-info-circle"></i> Informasi Workflow</h6>
                <p>Upload dan verifikasi bukti mutasi pengembalian hutang dilakukan di sini oleh tim keuangan. Setelah lolos, kasir cukup memilih kode validasi yang sama pada form pemasukan tanpa perlu upload ulang dokumen.</p>
            </div>

            <?php if (empty($pengambilan_hutang_rows)): ?>
            <div style="padding:30px; text-align:center; color:#64748b;">Belum ada histori hutang yang tercatat.</div>
            <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <?php foreach ($pengambilan_hutang_rows as $row): ?>
                <details style="border:1px solid #e2e8f0; border-radius:12px; background:#fff; overflow:hidden;" <?php echo ($row['status'] !== 'selesai' ? 'open' : ''); ?>>
                    <summary style="list-style:none; cursor:pointer; padding:18px 20px; display:flex; justify-content:space-between; gap:16px; align-items:flex-start; background:#fff7ed;">
                        <div>
                            <strong><?php echo htmlspecialchars($row['kode_pengambilan']); ?></strong>
                            <div style="font-size:13px; color:#475569; margin-top:4px;">
                                Rek peminjam: <?php echo htmlspecialchars($row['no_rekening_peminjam'] ?? '-'); ?> |
                                Penerima: <?php echo htmlspecialchars(($row['no_rekening_penerima'] ?? '-') . ' (' . ($row['nama_cabang_penerima'] ?? '-') . ')'); ?>
                            </div>
                        </div>
                        <span class="<?php echo $row['status'] === 'selesai' ? 'status-badge bg-success' : 'status-badge bg-danger'; ?>">
                            <?php echo htmlspecialchars(strtoupper($row['status'])); ?>
                        </span>
                    </summary>
                    <div style="padding:20px;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:18px;">
                            <div style="padding:12px; border:1px solid #e2e8f0; border-radius:10px;">
                                <div style="font-size:12px; color:#64748b;">Nominal asli dipinjam</div>
                                <div style="font-weight:700; color:#0f172a;"><?php echo formatRupiah($row['nominal_diambil']); ?></div>
                            </div>
                            <div style="padding:12px; border:1px solid #e2e8f0; border-radius:10px;">
                                <div style="font-size:12px; color:#64748b;">Total dibayar</div>
                                <div style="font-weight:700; color:#16a34a;"><?php echo formatRupiah($row['nominal_terbayar']); ?></div>
                            </div>
                            <div style="padding:12px; border:1px solid #fee2e2; background:#fff1f2; border-radius:10px;">
                                <div style="font-size:12px; color:#9f1239;">Sisa hutang</div>
                                <div style="font-weight:700; color:#be123c;"><?php echo formatRupiah($row['nominal_sisa_hutang']); ?></div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin-bottom:18px;">
                            <div style="padding:14px; border:1px solid #bfdbfe; background:#eff6ff; border-radius:12px;">
                                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:#1d4ed8; font-weight:700;">No Rek Pemberi Pinjaman</div>
                                <div style="margin-top:8px; font-size:20px; font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($row['no_rekening_peminjam'] ?? '-'); ?></div>
                                <div style="margin-top:6px; font-size:12px; color:#1e40af;">Akan menjadi rekening tujuan saat pelunasan transfer.</div>
                            </div>
                            <div style="padding:14px; border:1px solid #fecaca; background:#fff1f2; border-radius:12px;">
                                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:#be123c; font-weight:700;">No Rek Penerima Pinjaman</div>
                                <div style="margin-top:8px; font-size:20px; font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($row['no_rekening_penerima'] ?? '-'); ?></div>
                                <div style="margin-top:6px; font-size:12px; color:#9f1239;">Akan menjadi rekening pengirim saat pelunasan dari cabang/gudang.</div>
                            </div>
                            <div style="padding:14px; border:1px solid #fde68a; background:#fffbeb; border-radius:12px;">
                                <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:#92400e; font-weight:700;">Tanggal Setor ke Penyetor & Cabang</div>
                                <div style="margin-top:8px; font-size:16px; font-weight:700; color:#0f172a;"><?php echo !empty($row['tanggal_perencanaan_setor']) ? date('d/m/Y', strtotime($row['tanggal_perencanaan_setor'])) : '-'; ?></div>
                                <div style="margin-top:6px; font-size:12px; color:#7c2d12;"><?php echo htmlspecialchars($row['nama_cabang_penerima'] ?? '-'); ?></div>
                            </div>
                        </div>

                        <div style="margin-bottom:18px;">
                            <h6 style="margin-bottom:8px; color:#0f172a;">Validasi mutasi pengembalian</h6>
                            <?php if (!empty($row['mutasi_ready'])): ?>
                            <div style="padding:14px; border:1px solid #bbf7d0; background:#f0fdf4; border-radius:12px;">
                                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
                                    <div>
                                        <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:#15803d; font-weight:700;">Mutasi siap dipakai kasir</div>
                                        <div style="margin-top:8px; font-size:18px; font-weight:700; color:#0f172a;">
                                            <?php echo !empty($row['mutasi_nominal_terdeteksi']) ? formatRupiah($row['mutasi_nominal_terdeteksi']) : '-'; ?>
                                        </div>
                                        <div style="margin-top:8px; font-size:12px; color:#166534; line-height:1.6;">
                                            Pengirim: <strong><?php echo htmlspecialchars($row['mutasi_rekening_pengirim'] ?? '-'); ?></strong><br>
                                            Penerima: <strong><?php echo htmlspecialchars($row['mutasi_rekening_penerima'] ?? '-'); ?></strong><br>
                                            Confidence: <strong><?php echo htmlspecialchars(strtoupper($row['mutasi_confidence'] ?? 'medium')); ?></strong>
                                        </div>
                                    </div>
                                    <div style="text-align:right; min-width:220px;">
                                        <div style="font-size:12px; color:#166534;">Dokumen: <strong><?php echo htmlspecialchars($row['mutasi_dokumen_nama_asli'] ?? '-'); ?></strong></div>
                                        <div style="font-size:12px; color:#166534; margin-top:6px;">Diverifikasi: <?php echo !empty($row['mutasi_verified_at']) ? date('d/m/Y H:i', strtotime($row['mutasi_verified_at'])) : '-'; ?></div>
                                        <?php if (!empty($row['mutasi_dokumen_path'])): ?>
                                        <div style="margin-top:10px;">
                                            <?php
                                            $ext = strtolower(pathinfo($row['mutasi_dokumen_path'], PATHINFO_EXTENSION));
                                            if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                                <div style="margin-bottom:8px;">
                                                    <img src="<?php echo htmlspecialchars($row['mutasi_dokumen_path']); ?>" alt="Bukti Mutasi" style="max-width:100%; max-height:200px; border-radius:8px; border:1px solid #e2e8f0; object-fit:contain;">
                                                </div>
                                            <?php endif; ?>
                                            <a href="<?php echo htmlspecialchars($row['mutasi_dokumen_path']); ?>" target="_blank" style="color:#15803d; font-size:12px; font-weight:700; text-decoration:none; display:inline-block; padding:4px 0;">
                                                <i class="fas fa-paperclip"></i> Buka / Unduh Dokumen Mutasi
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php elseif ($row['status'] === 'selesai'): ?>
                            <div style="padding:14px; border:1px solid #d1fae5; background:#ecfdf5; border-radius:12px; color:#166534; font-size:13px;">
                                Hutang sudah selesai. Tidak ada upload mutasi tambahan yang dibutuhkan.
                            </div>
                            <?php else: ?>
                            <div style="padding:16px; border:1px dashed #fb923c; background:#fff7ed; border-radius:14px;">
                                <div style="font-size:13px; color:#9a3412; margin-bottom:16px;">
                                    Input pelunasan hutang untuk kode <strong><?php echo htmlspecialchars($row['kode_pengambilan']); ?></strong>. Sisa hutang: <strong><?php echo formatRupiah($row['nominal_sisa_hutang']); ?></strong>.
                                </div>
                                <?php $ocr_uid = 'ocr_' . preg_replace('/[^a-z0-9]/i','_', $row['kode_pengambilan']); ?>
                                <form method="POST" enctype="multipart/form-data" id="form_<?php echo $ocr_uid; ?>">
                                    <input type="hidden" name="tab_filter" value="histori_hutang">
                                    <input type="hidden" name="kode_pengambilan" value="<?php echo htmlspecialchars($row['kode_pengambilan']); ?>">
                                    <div style="font-size:12px; color:#9a3412; font-weight:700; margin-bottom:6px;">
                                        <i class="fas fa-robot"></i> Upload Bukti Transfer (OCR &amp; Verifikasi Otomatis)
                                        <span style="font-weight:400; color:#b45309; font-size:11px; margin-left:4px;">JPG/PNG/PDF/DOCX, maks 8MB</span>
                                    </div>
                                    <div id="dz_<?php echo $ocr_uid; ?>"
                                         style="border:2px dashed #fb923c; border-radius:10px; padding:14px; background:#fffbf5; cursor:pointer; text-align:center;"
                                         onclick="document.getElementById('fi_<?php echo $ocr_uid; ?>').click()"
                                         ondragover="event.preventDefault();this.style.background='#fef3c7';"
                                         ondragleave="this.style.background='#fffbf5';"
                                         ondrop="handleOcrDrop(event,'<?php echo $ocr_uid; ?>')">
                                        <input type="file" id="fi_<?php echo $ocr_uid; ?>" name="dokumen_mutasi_keuangan"
                                               accept=".pdf,.docx,.jpg,.jpeg,.png" style="display:none;" required
                                               onchange="previewOcr('<?php echo $ocr_uid; ?>',this)">
                                        <div id="ph_<?php echo $ocr_uid; ?>" style="color:#c2410c;">
                                            <i class="fas fa-cloud-upload-alt" style="font-size:22px;margin-bottom:6px;display:block;"></i>
                                            <span style="font-size:13px;">Klik atau seret file ke sini</span>
                                        </div>
                                        <div id="pv_<?php echo $ocr_uid; ?>" style="display:none;">
                                            <img id="img_<?php echo $ocr_uid; ?>" src="" alt="Preview"
                                                 style="max-width:100%;max-height:150px;border-radius:8px;object-fit:contain;margin-bottom:6px;display:none;">
                                            <div id="fn_<?php echo $ocr_uid; ?>" style="font-size:12px;color:#92400e;font-weight:600;"></div>
                                            <button type="button" onclick="clearOcr(event,'<?php echo $ocr_uid; ?>')"
                                                    style="margin-top:5px;background:none;border:none;color:#dc2626;font-size:12px;cursor:pointer;">
                                                <i class="fas fa-times"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                    <button type="submit" name="upload_mutasi_pengadaan"
                                            style="margin-top:10px;width:100%;padding:10px;background:#dc2626;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                                        <i class="fas fa-robot"></i> Upload &amp; Verifikasi OCR
                                    </button>
                                </form>
                                <div style="display:flex;align-items:center;gap:10px;margin:14px 0;">
                                    <div style="flex:1;height:1px;background:#fed7aa;"></div>
                                    <span style="font-size:12px;color:#9a3412;font-weight:600;">— atau —</span>
                                    <div style="flex:1;height:1px;background:#fed7aa;"></div>
                                </div>
                                <button type="button"
                                        onclick="openManualPelunasan('<?php echo htmlspecialchars($row['kode_pengambilan']); ?>',<?php echo (float)$row['nominal_sisa_hutang']; ?>)"
                                        style="width:100%;padding:10px;background:#f59e0b;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                                    <i class="fas fa-keyboard"></i> Input Manual Pelunasan
                                </button>
                            </div>

                            </div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom:18px;">
                            <h6 style="margin-bottom:8px; color:#0f172a;">Riwayat pembayaran</h6>
                            <?php if (empty($row['payments'])): ?>
                            <div style="font-size:13px; color:#64748b;">Belum ada pembayaran yang match ke kode validasi ini.</div>
                            <?php else: ?>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <?php foreach ($row['payments'] as $payment): ?>
                                <div style="padding:12px; border:1px solid #e2e8f0; border-radius:10px; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; background:#fff;">
                                    <div style="flex:1; min-width:240px;">
                                        <strong><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></strong>
                                        <div style="font-size:12px; color:#64748b;">oleh <?php echo htmlspecialchars($payment['nama_input'] ?? $payment['kode_karyawan_input']); ?></div>
                                        <?php if (!empty($payment['dokumen_nama_asli'])): ?>
                                        <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid #dbeafe; background:#eff6ff;">
                                            <div style="font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:#1d4ed8; font-weight:700;">Bukti TF / Dokumen Pelunasan</div>
                                            <div style="margin-top:6px; font-weight:700; color:#0f172a;">
                                                <?php echo htmlspecialchars($payment['dokumen_nama_asli']); ?>
                                                <?php if (!empty($payment['dokumen_tipe'])): ?>
                                                <span style="font-size:11px; color:#1d4ed8;">(<?php echo htmlspecialchars(strtoupper($payment['dokumen_tipe'])); ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="margin-top:6px; font-size:12px; color:#1e3a8a; line-height:1.5;">
                                                <?php if (($payment['sumber'] ?? 'ocr') === 'manual'): ?>
                                                    No. TF: <strong><?php echo htmlspecialchars($payment['no_transaksi'] ?? '-'); ?></strong><br>
                                                    Tgl Transfer: <strong><?php echo !empty($payment['tanggal_transfer']) ? date('d/m/Y', strtotime($payment['tanggal_transfer'])) : '-'; ?></strong><br>
                                                    <?php if (!empty($payment['catatan'])): ?>
                                                    Catatan: <strong><?php echo htmlspecialchars($payment['catatan']); ?></strong>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    Pengirim: <strong><?php echo htmlspecialchars($payment['rekening_pengirim_terdeteksi'] ?? '-'); ?></strong><br>
                                                    Penerima: <strong><?php echo htmlspecialchars($payment['rekening_penerima_terdeteksi'] ?? '-'); ?></strong><br>
                                                    Nominal Invoice: <strong><?php echo !empty($payment['nominal_terdeteksi']) ? formatRupiah($payment['nominal_terdeteksi']) : '-'; ?></strong><br>
                                                    Confidence: <strong><?php echo htmlspecialchars(strtoupper($payment['confidence'] ?? 'low')); ?></strong>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($payment['dokumen_path'])): ?>
                                            <div style="margin-top:8px;">
                                                <?php
                                                $ext = strtolower(pathinfo($payment['dokumen_path'], PATHINFO_EXTENSION));
                                                if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                                    <div style="margin-bottom:8px;">
                                                        <img src="<?php echo htmlspecialchars($payment['dokumen_path']); ?>" alt="Bukti TF" style="max-width:100%; max-height:200px; border-radius:8px; border:1px solid #e2e8f0; object-fit:contain;">
                                                    </div>
                                                <?php endif; ?>
                                                <a href="<?php echo htmlspecialchars($payment['dokumen_path']); ?>" target="_blank" style="color:#1d4ed8; font-size:12px; font-weight:700; text-decoration:none; display:inline-block; padding:4px 0;">
                                                    <i class="fas fa-paperclip"></i> Buka / Unduh Dokumen
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:700; color:#16a34a;"><?php echo formatRupiah($payment['nominal_dibayar']); ?></div>
                                        <div style="font-size:12px; color:#64748b;">kode: <?php echo htmlspecialchars($payment['kode_validasi_input'] ?? $payment['kode_pengambilan']); ?></div>
                                        <?php if (($payment['sumber'] ?? 'ocr') === 'manual'): ?>
                                        <div style="margin-top:4px;"><span style="font-size:11px; background:#fef3c7; color:#92400e; padding:2px 7px; border-radius:999px; font-weight:700;"><i class="fas fa-keyboard"></i> MANUAL</span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom:18px;">
                            <h6 style="margin-bottom:8px; color:#0f172a;">Hutang lain dari rekening peminjam ini</h6>
                            <?php if (empty($row['other_hutang'])): ?>
                            <div style="font-size:13px; color:#64748b;">Tidak ada hutang lain dari rekening ini.</div>
                            <?php else: ?>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <?php foreach ($row['other_hutang'] as $other): ?>
                                <div style="padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px;">
                                    <strong><?php echo htmlspecialchars($other['kode_pengambilan']); ?></strong> |
                                    <?php echo formatRupiah($other['nominal_diambil']); ?> |
                                    <?php echo htmlspecialchars(strtoupper($other['status'])); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div style="padding:12px 14px; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; font-size:13px; color:#475569;">
                            Semua pelunasan hutang tetap memakai kode validasi yang sama. Bila masih ada sisa hutang, tim keuangan cukup upload mutasi berikutnya di panel atas dan kasir tetap memilih kode yang sama saat input pemasukan.
                        </div>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PERBAIKAN UTAMA: Tab Validasi Fisik dengan informasi closing yang detail -->
    <?php if ($tab == 'validasi'): ?>
    <div class="content-card tab-validasi">
        <div class="content-header">
            <h3><i class="fas fa-search"></i> Validasi Fisik Uang per Transaksi - DIPERBAIKI UNTUK CLOSING</h3>
            <div class="export-buttons">
                <a href="export_excel_setoran.php?type=validasi&tab=<?php echo $tab; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="export_csv.php?type=validasi&tab=<?php echo $tab; ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="content-body">
            <div class="workflow-info">
                <h6><i class="fas fa-info-circle"></i> Informasi Workflow - PERBAIKAN CLOSING</h6>
                <p><strong>PERBAIKAN:</strong> Validasi dilakukan per transaksi individual dengan kalkulasi yang diperbaiki untuk transaksi closing. Untuk transaksi "DARI CLOSING" yang memiliki pemasukan terkait (dipinjam), sistem akan menghitung <strong>Expected Physical Amount = Setoran Real - Pemasukan</strong>. Transaksi closing ditandai dengan warna ungu dan informasi detail.</p>
            </div>
            
            <div class="table-container">
                <div class="table-wrapper">
                    <div class="table-enhanced" style="overflow-x: auto;">
                        <table class="table" style="min-width: 1200px; white-space: nowrap;">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kode Transaksi</th>
                                    <th>Jenis</th>
                                    <th>Kode Setoran</th>
                                    <th>Cabang</th>
                                    <th>Kasir</th>
                                    <th>Nominal Sistem</th>
                                    <th class="closing-info-column">Info Closing (DIPERBAIKI)</th>
                                    <th class="closing-details-column">Expected Physical</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($setoran_list): ?>
                                    <?php foreach ($setoran_list as $row): ?>
                                        <tr class="<?php echo $row['jenis_transaksi'] == 'DARI CLOSING' ? 'closing-transaction' : ''; ?>">
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                                            <td>
                                                <div class="transaction-type-indicator">
                                                    <code><?php echo htmlspecialchars($row['kode_transaksi']); ?></code>
                                                    <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                        <span class="closing-info-badge">CLOSING</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <span class="status-badge bg-closing">DARI CLOSING</span>
                                                    <?php if ($row['total_closing_in_setoran'] > 1): ?>
                                                        <small style="display: block; font-size: 10px; color: var(--text-muted); margin-top: 2px;">
                                                            <?php echo $row['total_closing_in_setoran']; ?> transaksi closing dalam setoran ini
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (!empty($row['has_internal_closing_usage'])): ?>
                                                        <span class="status-badge bg-warning">MEMINJAM DARI CLOSING</span>
                                                    <?php else: ?>
                                                        <span class="status-badge bg-primary">TRANSAKSI BIASA</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($row['kode_setoran']); ?></code></td>
                                            <td><?php echo htmlspecialchars(ucfirst($row['resolved_nama_cabang'] ?? $row['nama_cabang'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_karyawan']); ?></td>
                                            <td style="text-align: right; font-weight: 600;"><?php echo formatRupiah($row['setoran_real']); ?></td>
                                            <td class="closing-info-column">
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING' && !empty($row['total_pemasukan_closing'])): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Dipinjam:</strong> <span class="borrowed-amount"><?php echo formatRupiah($row['total_pemasukan_closing']); ?></span></div>
                                                        <small><?php echo htmlspecialchars($row['keterangan_closing_gabungan'] ?? 'Transaksi closing gabungan'); ?></small>
                                                    </div>
                                                <?php elseif ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Closing Dari</strong></div>
                                                    </div>
                                                <?php elseif (!empty($row['has_internal_closing_usage'])): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Meminjam dari closing:</strong> <span class="borrowed-amount"><?php echo formatRupiah($row['internal_closing_usage_amount']); ?></span></div>
                                                        <small><?php echo htmlspecialchars(implode(', ', array_slice((array)($row['internal_closing_usage_refs'] ?? []), 0, 3))); ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="closing-details-column">
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <div class="expected-physical-display">
                                                        <span class="amount"><?php echo formatRupiah($row['expected_physical_amount'] ?? $row['setoran_real']); ?></span>
                                                        <small class="label">Harusnya Diterima</small>
                                                        <?php if (!empty($row['total_pemasukan_closing']) && $row['total_pemasukan_closing'] > 0): ?>
                                                            <br><small style="color: var(--text-muted);">Dipinjam: <?php echo formatRupiah($row['total_pemasukan_closing']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="text-align: center; color: var(--text-muted);">
                                                        <?php echo formatRupiah($row['setoran_real']); ?>
                                                        <br><small>Normal</small>
                                                        <?php if (!empty($row['has_internal_closing_usage'])): ?>
                                                            <br><small style="color: var(--warning-color);">Memakai dana closing</small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge bg-warning">Diterima Staff Keuangan</span>
                                            </td>
                                            <td>
                                                <a href="?tab=validasi&validate_id=<?php echo $row['kode_transaksi']; ?>" 
                                                   class="btn <?php echo $row['jenis_transaksi'] == 'DARI CLOSING' ? 'btn-danger' : 'btn-warning'; ?> btn-sm"
                                                   title="<?php echo $row['jenis_transaksi'] == 'DARI CLOSING' ? 'Validasi Transaksi Closing' : 'Validasi Transaksi Biasa'; ?>">
                                                    <i class="fas fa-edit"></i> Validasi
                                                    <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                        <span style="font-size: 9px;">CLOSING</span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="no-data">
                                            <i class="fas fa-search"></i><br>
                                            Tidak ada transaksi yang perlu divalidasi fisik
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PERBAIKAN UTAMA: Tab Validasi Selisih dengan informasi closing yang detail -->
    <?php if ($tab == 'validasi_selisih'): ?>
    <div class="content-card tab-validasi_selisih">
        <div class="content-header">
            <h3><i class="fas fa-edit"></i> Edit Validasi Selisih - DIPERBAIKI UNTUK CLOSING</h3>
            <div class="export-buttons">
                <a href="export_excel_setoran.php?type=validasi_selisih&tab=<?php echo $tab; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="export_csv.php?type=validasi_selisih&tab=<?php echo $tab; ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="content-body">
            <div class="workflow-info">
                <h6><i class="fas fa-info-circle"></i> Informasi Workflow - PERBAIKAN CLOSING & FITUR KEMBALIKAN</h6>
                <p><strong>PERBAIKAN:</strong> Transaksi yang memiliki selisih dalam validasi fisik dengan kalkulasi yang diperbaiki untuk transaksi closing. Anda dapat mengedit ulang jumlah yang diterima untuk mengoreksi selisih. Sistem telah diperbaiki untuk menghitung selisih berdasarkan <strong>Expected Physical Amount</strong> untuk transaksi closing yang memiliki pinjaman.</p>
                <p><strong>FITUR BARU:</strong> <i class="fas fa-undo" style="color: var(--warning-color);"></i> <strong>Kembalikan ke CS</strong> - Untuk transaksi dengan selisih yang tidak dapat diperbaiki, Anda dapat mengembalikannya ke CS pengirim untuk diperbaiki di sumber. CS akan menerima notifikasi dan dapat melakukan perbaikan sebelum mengirim ulang.</p>
            </div>
            
            <div class="table-container">
                <div class="table-wrapper">
                    <div class="table-enhanced" style="overflow-x: auto;">
                        <table class="table" style="min-width: 1400px;">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kode Transaksi</th>
                                    <th>Jenis</th>
                                    <th>Kode Setoran</th>
                                    <th>Cabang</th>
                                    <th>Kasir</th>
                                    <th>Nominal Sistem</th>
                                    <th>Diterima Fisik</th>
                                    <th>Selisih</th>
                                    <th class="closing-info-column">Info Closing (DIPERBAIKI)</th>
                                    <th class="closing-details-column">Expected Physical</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($setoran_list): ?>
                                    <?php foreach ($setoran_list as $row): ?>
                                        <tr class="<?php echo $row['jenis_transaksi'] == 'DARI CLOSING' ? 'closing-transaction' : ''; ?>">
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                                            <td>
                                                <div class="transaction-type-indicator">
                                                    <code><?php echo htmlspecialchars($row['kode_transaksi']); ?></code>
                                                    <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                        <span class="closing-info-badge">CLOSING</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <span class="status-badge bg-closing">DARI CLOSING</span>
                                                    <?php if ($row['total_closing_in_setoran'] > 1): ?>
                                                        <small style="display: block; font-size: 10px; color: var(--text-muted); margin-top: 2px;">
                                                            <?php echo $row['total_closing_in_setoran']; ?> transaksi closing dalam setoran ini
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (!empty($row['has_internal_closing_usage'])): ?>
                                                        <span class="status-badge bg-warning">MEMINJAM DARI CLOSING</span>
                                                    <?php else: ?>
                                                        <span class="status-badge bg-primary">TRANSAKSI BIASA</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($row['kode_setoran']); ?></code></td>
                                            <td><?php echo htmlspecialchars(ucfirst($row['resolved_nama_cabang'] ?? $row['nama_cabang'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_karyawan']); ?></td>
                                            <td style="text-align: right; font-weight: 600;"><?php echo formatRupiah($row['setoran_real']); ?></td>
                                            <td style="text-align: right; font-weight: 600;">
                                                <?php 
                                                $diterima_fisik = ($validation_columns_exist && isset($row['jumlah_diterima_fisik'])) 
                                                    ? $row['jumlah_diterima_fisik'] 
                                                    : $row['setoran_real'];
                                                echo formatRupiah($diterima_fisik); 
                                                ?>
                                            </td>
                                            <td style="text-align: right; font-weight: 600; color: var(--danger-color);">
                                                <?php 
                                                // PERBAIKAN: Hitung selisih berdasarkan expected_physical_amount untuk closing
                                                $expected_amount = $row['expected_physical_amount'] ?? $row['setoran_real'];
                                                
                                                if (($row['jenis_transaksi'] ?? '') === 'DARI CLOSING') {
                                                    $selisih = $diterima_fisik - $expected_amount;
                                                } elseif ($validation_columns_exist && isset($row['selisih_fisik'])) {
                                                    $selisih = $row['selisih_fisik'];
                                                } elseif ($validation_columns_exist && isset($row['jumlah_diterima_fisik'])) {
                                                    $selisih = $row['jumlah_diterima_fisik'] - $expected_amount;
                                                } else {
                                                    $selisih = 0;
                                                }
                                                $selisih = normalizeSelisih($selisih);
                                                echo formatRupiah($selisih);
                                                ?>
                                            </td>
                                            <td class="closing-info-column">
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING' && !empty($row['total_pemasukan_closing'])): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Dipinjam:</strong> <span class="borrowed-amount"><?php echo formatRupiah($row['total_pemasukan_closing']); ?></span></div>
                                                        <small><?php echo htmlspecialchars($row['keterangan_closing_gabungan'] ?? 'Transaksi closing gabungan'); ?></small>
                                                    </div>
                                                <?php elseif ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Closing Murni</strong></div>
                                                        <small>Tidak ada pinjaman</small>
                                                    </div>
                                                <?php elseif (!empty($row['has_internal_closing_usage'])): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Meminjam dari closing:</strong> <span class="borrowed-amount"><?php echo formatRupiah($row['internal_closing_usage_amount']); ?></span></div>
                                                        <small><?php echo htmlspecialchars(implode(', ', array_slice((array)($row['internal_closing_usage_refs'] ?? []), 0, 3))); ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="closing-details-column">
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <div class="expected-physical-display">
                                                        <span class="amount"><?php echo formatRupiah($expected_amount); ?></span>
                                                        <small class="label">Harusnya Diterima</small>
                                                        <?php if (!empty($row['total_pemasukan_closing']) && $row['total_pemasukan_closing'] > 0): ?>
                                                            <br><small style="color: var(--text-muted);">Dipinjam: <?php echo formatRupiah($row['total_pemasukan_closing']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="text-align: center; color: var(--text-muted);">
                                                        <?php echo formatRupiah($row['setoran_real']); ?>
                                                        <br><small>Normal</small>
                                                        <?php if (!empty($row['has_internal_closing_usage'])): ?>
                                                            <br><small style="color: var(--warning-color);">Memakai dana closing</small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical" style="gap: 5px;">
                                                    <a href="?tab=validasi_selisih&edit_selisih_id=<?php echo $row['kode_transaksi']; ?>" 
                                                       class="btn <?php echo $row['jenis_transaksi'] == 'DARI CLOSING' ? 'btn-danger' : 'btn-warning'; ?> btn-sm"
                                                       title="<?php echo $row['jenis_transaksi'] == 'DARI CLOSING' ? 'Edit Selisih Transaksi Closing' : 'Edit Selisih Transaksi Biasa'; ?>">
                                                        <i class="fas fa-edit"></i> Edit Selisih
                                                        <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                            <span style="font-size: 9px;">CLOSING</span>
                                                        <?php endif; ?>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-secondary btn-sm"
                                                            onclick="showKembalikanKeCSModal('<?php echo $row['kode_transaksi']; ?>', '<?php echo htmlspecialchars($row['nama_karyawan'] ?? 'CS'); ?>', '<?php echo htmlspecialchars($row['resolved_nama_cabang'] ?? $row['nama_cabang'] ?? 'Cabang'); ?>')"
                                                            title="Kembalikan setoran ini ke CS pengirim untuk diperbaiki">
                                                        <i class="fas fa-undo"></i> Kembalikan ke CS
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" class="no-data">
                                            <i class="fas fa-check-circle"></i><br>
                                            Tidak ada transaksi dengan selisih yang perlu diperbaiki
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tab == 'dikembalikan_cs'): ?>
    <div class="content-card tab-dikembalikan_cs">
        <div class="content-header">
            <h3><i class="fas fa-undo"></i> Transaksi Dikembalikan ke CS</h3>
            <div class="export-buttons">
                <a href="export_excel_setoran.php?type=dikembalikan_cs&tab=<?php echo $tab; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="export_csv.php?type=dikembalikan_cs&tab=<?php echo $tab; ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="content-body">
            <div class="table-container">
                <div class="table-responsive">
                    <div class="table-enhanced" style="overflow-x: auto;">
                        <table class="table" style="min-width: 1400px;">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Kode Transaksi</th>
                                    <th style="width: 80px;">Jenis</th>
                                    <th style="width: 100px;">Tanggal Transaksi</th>
                                    <th style="width: 120px;">Cabang</th>
                                    <th style="width: 100px;">Kasir</th>
                                    <th style="width: 100px;">Setoran (Rp)</th>
                                    <th style="width: 100px;">Omset (Rp)</th>
                                    <th style="width: 120px;">Info Closing</th>
                                    <th style="width: 150px;">Catatan Validasi</th>
                                    <th style="width: 120px;">Dikembalikan Pada</th>
                                    <th style="width: 100px;">Dikembalikan Oleh</th>
                                    <th style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($setoran_list): ?>
                                    <?php foreach ($setoran_list as $row): ?>
                                        <tr>
                                            <td class="code-column">
                                                <div class="code-wrapper">
                                                    <strong><?php echo htmlspecialchars($row['kode_transaksi']); ?></strong>
                                                    <div class="tanggal-closing">
                                                        <?php if (!empty($row['tanggal_closing'])): ?>
                                                            <small>Closing: <?php echo date('d/m/Y', strtotime($row['tanggal_closing'])); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <span class="closing-badge">CLOSING</span>
                                                <?php else: ?>
                                                    <span class="regular-badge">BIASA</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                                            <td>
                                                <div class="cabang-info">
                                                    <strong><?php echo htmlspecialchars($row['nama_cabang']); ?></strong>
                                                    <?php if (!empty($row['nama_pengantar'])): ?>
                                                        <small>Pengantar: <?php echo htmlspecialchars($row['nama_pengantar']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['nama_karyawan'] ?? 'Unknown'); ?></td>
                                            <td class="amount"><?php echo formatRupiah($row['setoran_real']); ?></td>
                                            <td class="amount"><?php echo formatRupiah($row['omset']); ?></td>
                                            <td class="closing-info-column">
                                                <?php if ($row['jenis_transaksi'] == 'DARI CLOSING' && !empty($row['jumlah_pemasukan_closing'])): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Dipinjam:</strong> <span class="borrowed-amount"><?php echo formatRupiah($row['jumlah_pemasukan_closing']); ?></span></div>
                                                        <small><?php echo htmlspecialchars($row['keterangan_closing'] ?? 'Transaksi closing'); ?></small>
                                                    </div>
                                                <?php elseif ($row['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                    <div class="closing-borrowed-info">
                                                        <div><strong>Closing Murni</strong></div>
                                                        <small>Tidak ada pinjaman</small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="catatan-validasi">
                                                    <?php if (!empty($row['catatan_validasi'])): ?>
                                                        <div class="catatan-text"><?php echo htmlspecialchars($row['catatan_validasi']); ?></div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['validasi_at'])): ?>
                                                    <div class="datetime-info">
                                                        <div><?php echo date('d/m/Y', strtotime($row['validasi_at'])); ?></div>
                                                        <small><?php echo date('H:i:s', strtotime($row['validasi_at'])); ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['validasi_by'] ?? '-'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="detail_closing.php?kode_transaksi=<?php echo $row['kode_transaksi']; ?>"
                                                       class="btn btn-info btn-sm" target="_blank"
                                                       title="Lihat detail transaksi">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" class="no-data">
                                            <i class="fas fa-inbox"></i><br>
                                            Tidak ada transaksi yang dikembalikan ke CS
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PERBAIKAN: Enhanced Validation Modal for Closing Transactions dengan informasi gabungan -->
    <?php if ($transaksi_detail): ?>
    <div class="modal show" style="z-index: 10000;">
        <div class="modal-dialog modal-lg" style="margin: 20px auto; max-width: 800px;">
            <div class="modal-content" style="background: white; border-radius: 16px; position: relative;">
                <div class="modal-header" style="position: relative; z-index: 10001;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Validasi Fisik Transaksi
                        <?php if ($transaksi_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                            <span class="closing-info-badge">CLOSING</span>
                        <?php endif; ?>
                    </h5>
                    <a href="?tab=validasi" class="btn-close" style="position: relative; z-index: 10002;">&times;</a>
                </div>
                <form action="" method="POST">
                    <div class="modal-body" style="position: relative; z-index: 10001; max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="transaksi_id" value="<?php echo htmlspecialchars($transaksi_detail['kode_transaksi']); ?>">
                        
                        <!-- PERBAIKAN: Enhanced info for closing transactions dengan kalkulasi gabungan -->
                        <?php if ($transaksi_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                        <div class="closing-validation-info">
                            <h6><i class="fas fa-sync-alt"></i> Informasi Transaksi Closing Gabungan</h6>
                            <p style="margin: 0; font-size: 14px;">
                                Transaksi ini adalah hasil closing yang merupakan gabungan dari transaksi closing, transaksi yang dipinjam, dan transaksi yang meminjam untuk cabang <strong><?php echo htmlspecialchars($transaksi_detail['nama_cabang']); ?></strong>.
                            </p>
                            
                            <?php if (!empty($transaksi_detail['total_closing_borrowed']) && $transaksi_detail['total_closing_borrowed'] > 0): ?>
                            <div class="closing-aggregation">
                                <h6 style="font-size: 13px; margin-bottom: 8px; color: var(--closing-color);">Kalkulasi Gabungan:</h6>
                                <div class="closing-summary-item">
                                    <span>Nominal Closing Asli</span>
                                    <span><?php echo formatRupiah($transaksi_detail['setoran_real']); ?></span>
                                </div>
                                <div class="closing-summary-item">
                                    <span>Di Kurangi Pemasukan </span>
                                    <span class="borrowed-amount">-<?php echo formatRupiah($transaksi_detail['total_closing_borrowed']); ?></span>
                                </div>
                                <div class="closing-summary-item total">
                                    <span>Seharusnya Diterima Fisik</span>
                                    <span class="expected-amount"><?php echo formatRupiah(max(0, $transaksi_detail['setoran_real'] - $transaksi_detail['total_closing_borrowed'])); ?></span>
                                </div>
                                <small style="color: var(--text-muted); font-size: 11px;">
                                    Keterangan: <?php echo htmlspecialchars((string)($transaksi_detail['keterangan_closing'] ?? '')); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($transaksi_detail['closing_info']) && !empty($transaksi_detail['closing_info'])): ?>
                            <div class="closing-aggregation">
                                <h6 style="font-size: 13px; margin-bottom: 8px; color: var(--closing-color);">Rincian Gabungan per Cabang:</h6>
                                <?php foreach ($transaksi_detail['closing_info'] as $info): ?>
                                <div class="closing-summary-item">
                                    <span><?php echo $info['jenis']; ?> (<?php echo $info['count']; ?> transaksi)</span>
                                    <span><?php echo formatRupiah($info['total_sistem']); ?></span>
                                </div>
                                <?php endforeach; ?>
                                <div class="closing-summary-item total">
                                    <span>Total Gabungan</span>
                                    <span><?php echo formatRupiah(array_sum(array_column($transaksi_detail['closing_info'], 'total_sistem'))); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($transaksi_detail['jenis_transaksi'] != 'DARI CLOSING' && !empty($transaksi_detail['has_internal_closing_usage'])): ?>
                        <div class="workflow-info" style="margin-bottom: 16px; border-left-color: var(--warning-color); background: rgba(245, 158, 11, 0.08);">
                            <h6><i class="fas fa-hand-holding-usd"></i> Informasi Pemakaian Dana Closing</h6>
                            <p style="margin: 0; font-size: 14px;">
                                Transaksi ini <strong>tetap transaksi biasa</strong>, tetapi di dalam transaksi kasirnya terdapat pemasukan akun <strong>DRCLSG</strong> sebesar
                                <strong><?php echo formatRupiah($transaksi_detail['internal_closing_usage_amount']); ?></strong>
                                yang memakai dana dari closing lain.
                            </p>
                            <?php if (!empty($transaksi_detail['internal_closing_usage_refs'])): ?>
                            <small style="display: block; margin-top: 8px; color: var(--text-muted);">
                                Referensi closing: <?php echo htmlspecialchars(implode(', ', array_slice((array)$transaksi_detail['internal_closing_usage_refs'], 0, 5))); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="validation-summary">
                            <h6 style="margin-bottom: 15px; color: var(--text-dark);"><i class="fas fa-info-circle"></i> Informasi Transaksi</h6>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Kode Transaksi</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($transaksi_detail['kode_transaksi']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Jenis Transaksi</div>
                                    <div class="detail-value">
                                        <?php if ($transaksi_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                            <span style="color: var(--closing-color); font-weight: 600;">DARI CLOSING</span>
                                        <?php else: ?>
                                            <?php if (!empty($transaksi_detail['has_internal_closing_usage'])): ?>
                                                <span style="color: var(--warning-color); font-weight: 600;">MEMINJAM DARI CLOSING</span>
                                                <br><small style="color: var(--warning-color);">Meminjam dana dari closing lain</small>
                                            <?php else: ?>
                                                <span style="color: var(--primary-color); font-weight: 600;">TRANSAKSI BIASA</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Kode Setoran</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($transaksi_detail['kode_setoran']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Tanggal Transaksi</div>
                                    <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($transaksi_detail['tanggal_transaksi'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Cabang</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($transaksi_detail['resolved_nama_cabang'] ?? $transaksi_detail['nama_cabang']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Kasir</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($transaksi_detail['nama_karyawan']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Nominal Sistem</div>
                                    <div class="detail-value amount"><?php echo formatRupiah($transaksi_detail['setoran_real']); ?></div>
                                </div>
                                <?php if (!empty($transaksi_detail['total_closing_borrowed']) && $transaksi_detail['total_closing_borrowed'] > 0): ?>
                                <div class="detail-item">
                                    <div class="detail-label">Yang Seharusnya Diterima</div>
                                    <div class="detail-value expected-amount"><?php echo formatRupiah(max(0, $transaksi_detail['setoran_real'] - $transaksi_detail['total_closing_borrowed'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h6 style="margin-bottom: 10px; color: var(--text-dark);"><i class="fas fa-calculator"></i> Input Validasi Fisik Uang</h6>
                        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">
                            <?php if ($transaksi_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                <?php if (!empty($transaksi_detail['total_closing_borrowed']) && $transaksi_detail['total_closing_borrowed'] > 0): ?>
                                    Masukkan jumlah uang yang benar-benar diterima secara fisik. Sistem akan otomatis menghitung selisih berdasarkan jumlah yang seharusnya diterima (<?php echo formatRupiah(max(0, $transaksi_detail['setoran_real'] - $transaksi_detail['total_closing_borrowed'])); ?>):
                                <?php else: ?>
                                    Masukkan jumlah uang gabungan yang benar-benar diterima secara fisik untuk transaksi closing ini:
                                <?php endif; ?>
                            <?php else: ?>
                                Masukkan jumlah uang yang benar-benar diterima secara fisik untuk transaksi ini:
                            <?php endif; ?>
                        </p>
                        <div class="detail-grid" style="margin-bottom: 15px;">
                            <div class="detail-item">
                                <label class="form-label">Jumlah Diterima Fisik:</label>
                                <input type="text" name="jumlah_diterima" id="jumlahDiterima" class="form-control" 
                                       value="<?php 
                                       // PERBAIKAN: Set default value berdasarkan kalkulasi gabungan
                                       if (!empty($transaksi_detail['total_closing_borrowed']) && $transaksi_detail['total_closing_borrowed'] > 0) {
                                           echo formatRupiah(max(0, $transaksi_detail['setoran_real'] - $transaksi_detail['total_closing_borrowed']));
                                       } else {
                                           echo formatRupiah($transaksi_detail['setoran_real']);
                                       }
                                       ?>" required oninput="hitungSelisihTransaksi()">
                            </div>
                        </div>
                        <div class="detail-grid" id="selisihRow" style="display: none; margin-bottom: 15px;">
                            <div class="detail-item">
                                <label class="form-label">Selisih:</label>
                                <span id="selisihAmount" style="font-weight: 600; font-size: 16px;"></span>
                            </div>
                        </div>

                        <div class="detail-item">
                            <label class="form-label">Catatan Validasi (opsional):</label>
                            <textarea name="catatan_validasi" class="form-control" rows="3" 
                                      placeholder="<?php echo $transaksi_detail['jenis_transaksi'] == 'DARI CLOSING' ? 'Tambahkan catatan untuk transaksi closing ini...' : 'Tambahkan catatan jika ada selisih atau keterangan khusus...'; ?>"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="?tab=validasi" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                        <button type="button" 
                                class="btn btn-warning"
                                onclick="showKembalikanKeCSModal('<?php echo $transaksi_detail['kode_transaksi']; ?>', '<?php echo htmlspecialchars($transaksi_detail['nama_karyawan'] ?? 'CS'); ?>', '<?php echo htmlspecialchars($transaksi_detail['resolved_nama_cabang'] ?? $transaksi_detail['nama_cabang'] ?? 'Cabang'); ?>')"
                                title="Kembalikan setoran ini ke CS pengirim tanpa validasi">
                            <i class="fas fa-undo"></i> Kembalikan ke CS
                        </button>
                        <button type="submit" name="validasi_individual" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Validasi
                            <?php if ($transaksi_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                Closing
                            <?php endif; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PERBAIKAN: Enhanced Edit Selisih Modal for Closing Transactions dengan informasi gabungan -->
    <?php if ($edit_selisih_detail): ?>
    <div class="modal show" style="z-index: 10000;">
        <div class="modal-dialog modal-lg" style="margin: 20px auto; max-width: 800px;">
            <div class="modal-content" style="background: white; border-radius: 16px; position: relative;">
                <div class="modal-header" style="position: relative; z-index: 10001;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Selisih Transaksi
                        <?php if ($edit_selisih_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                            <span class="closing-info-badge">CLOSING</span>
                        <?php endif; ?>
                    </h5>
                    <a href="?tab=validasi_selisih" class="btn-close" style="position: relative; z-index: 10002;">&times;</a>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="transaksi_id" value="<?php echo htmlspecialchars($edit_selisih_detail['kode_transaksi']); ?>">
                        
                        <!-- PERBAIKAN: Enhanced info for closing transactions dengan kalkulasi gabungan -->
                        <?php if ($edit_selisih_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                        <div class="closing-validation-info">
                            <h6><i class="fas fa-sync-alt"></i> Informasi Transaksi Closing Gabungan</h6>
                            <p style="margin: 0; font-size: 14px;">
                                Transaksi ini adalah hasil closing yang merupakan gabungan dari transaksi closing, transaksi yang dipinjam, dan transaksi yang meminjam untuk cabang <strong><?php echo htmlspecialchars($edit_selisih_detail['resolved_nama_cabang'] ?? $edit_selisih_detail['nama_cabang']); ?></strong>.
                            </p>
                            
                            <?php if (!empty($edit_selisih_detail['total_closing_borrowed']) && $edit_selisih_detail['total_closing_borrowed'] > 0): ?>
                            <div class="closing-aggregation">
                                <h6 style="font-size: 13px; margin-bottom: 8px; color: var(--closing-color);">Kalkulasi Gabungan:</h6>
                                <div class="closing-summary-item">
                                    <span>Nominal Closing Asli</span>
                                    <span><?php echo formatRupiah($edit_selisih_detail['setoran_real']); ?></span>
                                </div>
                                <div class="closing-summary-item">
                                    <span>Dipinjam/Digunakan</span>
                                    <span class="borrowed-amount">-<?php echo formatRupiah($edit_selisih_detail['total_closing_borrowed']); ?></span>
                                </div>
                                <div class="closing-summary-item total">
                                    <span>Seharusnya Diterima Fisik</span>
                                    <span class="expected-amount"><?php echo formatRupiah(max(0, $edit_selisih_detail['setoran_real'] - $edit_selisih_detail['total_closing_borrowed'])); ?></span>
                                </div>
                                <small style="color: var(--text-muted); font-size: 11px;">
                                    Keterangan: <?php echo htmlspecialchars($edit_selisih_detail['keterangan_closing']); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($edit_selisih_detail['closing_info']) && !empty($edit_selisih_detail['closing_info'])): ?>
                            <div class="closing-aggregation">
                                <h6 style="font-size: 13px; margin-bottom: 8px; color: var(--closing-color);">Rincian Gabungan per Cabang:</h6>
                                <?php foreach ($edit_selisih_detail['closing_info'] as $info): ?>
                                <div class="closing-summary-item">
                                    <span><?php echo $info['jenis']; ?> (<?php echo $info['count']; ?> transaksi)</span>
                                    <span><?php echo formatRupiah($info['total_sistem']); ?></span>
                                </div>
                                <?php endforeach; ?>
                                <div class="closing-summary-item total">
                                    <span>Total Gabungan</span>
                                    <span><?php echo formatRupiah(array_sum(array_column($edit_selisih_detail['closing_info'], 'total_sistem'))); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($edit_selisih_detail['jenis_transaksi'] != 'DARI CLOSING' && !empty($edit_selisih_detail['has_internal_closing_usage'])): ?>
                        <div class="workflow-info" style="margin-bottom: 16px; border-left-color: var(--warning-color); background: rgba(245, 158, 11, 0.08);">
                            <h6><i class="fas fa-hand-holding-usd"></i> Informasi Pemakaian Dana Closing</h6>
                            <p style="margin: 0; font-size: 14px;">
                                Transaksi ini <strong>tetap transaksi biasa</strong>, tetapi di dalam transaksi kasirnya terdapat pemasukan akun <strong>DRCLSG</strong> sebesar
                                <strong><?php echo formatRupiah($edit_selisih_detail['internal_closing_usage_amount']); ?></strong>
                                yang memakai dana dari closing lain.
                            </p>
                            <?php if (!empty($edit_selisih_detail['internal_closing_usage_refs'])): ?>
                            <small style="display: block; margin-top: 8px; color: var(--text-muted);">
                                Referensi closing: <?php echo htmlspecialchars(implode(', ', array_slice((array)$edit_selisih_detail['internal_closing_usage_refs'], 0, 5))); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="validation-summary">
                            <h6 style="margin-bottom: 15px; color: var(--text-dark);"><i class="fas fa-info-circle"></i> Informasi Transaksi</h6>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Kode Transaksi</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($edit_selisih_detail['kode_transaksi']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Jenis Transaksi</div>
                                    <div class="detail-value">
                                        <?php if ($edit_selisih_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                            <span style="color: var(--closing-color); font-weight: 600;">DARI CLOSING</span>
                                        <?php else: ?>
                                            <?php if (!empty($edit_selisih_detail['has_internal_closing_usage'])): ?>
                                                <span style="color: var(--warning-color); font-weight: 600;">MEMINJAM DARI CLOSING</span>
                                                <br><small style="color: var(--warning-color);">Meminjam dana dari closing lain</small>
                                            <?php else: ?>
                                                <span style="color: var(--primary-color); font-weight: 600;">TRANSAKSI BIASA</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Kode Setoran</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($edit_selisih_detail['kode_setoran']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Tanggal Transaksi</div>
                                    <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($edit_selisih_detail['tanggal_transaksi'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Cabang</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($edit_selisih_detail['resolved_nama_cabang'] ?? $edit_selisih_detail['nama_cabang']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Kasir</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($edit_selisih_detail['nama_karyawan']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Nominal Sistem</div>
                                    <div class="detail-value amount"><?php echo formatRupiah($edit_selisih_detail['setoran_real']); ?></div>
                                </div>
                                <?php if (!empty($edit_selisih_detail['total_closing_borrowed']) && $edit_selisih_detail['total_closing_borrowed'] > 0): ?>
                                <div class="detail-item">
                                    <div class="detail-label">Yang Seharusnya Diterima</div>
                                    <div class="detail-value expected-amount"><?php echo formatRupiah(max(0, $edit_selisih_detail['setoran_real'] - $edit_selisih_detail['total_closing_borrowed'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h6 style="margin-bottom: 10px; color: var(--text-dark);"><i class="fas fa-calculator"></i> Edit Jumlah Diterima Fisik</h6>
                        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">
                            <?php if ($edit_selisih_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                <?php if (!empty($edit_selisih_detail['total_closing_borrowed']) && $edit_selisih_detail['total_closing_borrowed'] > 0): ?>
                                    Koreksi jumlah uang yang benar-benar diterima secara fisik. Sistem akan otomatis menghitung selisih berdasarkan jumlah yang seharusnya diterima (<?php echo formatRupiah(max(0, $edit_selisih_detail['setoran_real'] - $edit_selisih_detail['total_closing_borrowed'])); ?>):
                                <?php else: ?>
                                    Koreksi jumlah uang gabungan yang benar-benar diterima secara fisik untuk transaksi closing ini:
                                <?php endif; ?>
                            <?php else: ?>
                                Koreksi jumlah uang yang benar-benar diterima secara fisik:
                            <?php endif; ?>
                        </p>
                        <div class="detail-grid" style="margin-bottom: 15px;">
                            <div class="detail-item">
                                <label class="form-label">Jumlah Diterima Fisik (Baru):</label>
                                <input type="text" name="jumlah_diterima_baru" id="jumlahDiterimaBaru" class="form-control" 
                                       value="<?php echo formatRupiah(($validation_columns_exist && isset($edit_selisih_detail['jumlah_diterima_fisik'])) ? $edit_selisih_detail['jumlah_diterima_fisik'] : $edit_selisih_detail['setoran_real']); ?>" required oninput="hitungSelisihEdit()">
                            </div>
                        </div>
                        <div class="detail-grid" id="selisihEditRow" style="margin-bottom: 15px;">
                            <div class="detail-item">
                                <label class="form-label">Selisih:</label>
                                <span id="selisihEditAmount" style="font-weight: 600; font-size: 16px;"></span>
                            </div>
                        </div>

                        <div class="detail-item">
                            <label class="form-label">Catatan Validasi (opsional):</label>
                            <textarea name="catatan_validasi" class="form-control" rows="3" 
                                      placeholder="<?php echo $edit_selisih_detail['jenis_transaksi'] == 'DARI CLOSING' ? 'Tambahkan catatan untuk koreksi transaksi closing ini...' : 'Tambahkan catatan untuk koreksi ini...'; ?>"><?php echo htmlspecialchars(($validation_columns_exist && isset($edit_selisih_detail['catatan_validasi'])) ? $edit_selisih_detail['catatan_validasi'] : ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="?tab=validasi_selisih" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                        <button type="submit" name="edit_selisih" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                            <?php if ($edit_selisih_detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                Closing
                            <?php endif; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced Bank Detail Modal (Closing Summary) -->
    <?php if (isset($bank_detail_view) && !empty($bank_detail_view)): ?>
    <div class="modal show">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: white; border-radius: 16px;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-university"></i> Detail Setoran Bank - <?php echo htmlspecialchars($bank_detail_view['kode_setoran']); ?></h5>
                    <a href="?tab=bank_history" class="btn-close">&times;</a>
                </div>
                <div class="modal-body">
                    <div class="closing-summary">
                        <h4><i class="fas fa-info-circle"></i> Informasi Setoran Bank</h4>
                        <div class="closing-grid">
                            <div class="closing-item">
                                <div class="closing-label">Tanggal Setoran</div>
                                <div class="closing-value"><?php echo date('d/m/Y', strtotime($bank_detail_view['tanggal_setoran'])); ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Rekening Tujuan</div>
                                <div class="closing-value"><?php echo htmlspecialchars($bank_detail_view['rekening_tujuan']); ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Total Setoran</div>
                                <div class="closing-value amount"><?php echo formatRupiah($bank_detail_view['total_setoran']); ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Disetor Oleh</div>
                                <div class="closing-value"><?php echo htmlspecialchars($bank_detail_view['created_by_name']); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($bank_pengambilan_rows)): ?>
                    <div class="closing-summary" style="margin-top:18px; background:#fff7ed; border:1px solid #fed7aa;">
                        <h4><i class="fas fa-hand-holding-usd"></i> Terkait Pengambilan Cabang/Gudang</h4>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <?php foreach ($bank_pengambilan_rows as $pengambilanBank): ?>
                            <div style="padding:14px 16px; border-radius:12px; background:#fff; border:1px solid #fdba74;">
                                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                                    <div>
                                        <div style="font-weight:700; color:#7c2d12;"><?php echo htmlspecialchars($pengambilanBank['kode_pengambilan']); ?></div>
                                        <div style="font-size:13px; color:#7c2d12; margin-top:4px;">
                                            Cabang penerima: <?php echo htmlspecialchars($pengambilanBank['nama_cabang_penerima'] ?? '-'); ?> |
                                            Tanggal setor ke penyetor: <?php echo !empty($pengambilanBank['tanggal_perencanaan_setor']) ? date('d/m/Y', strtotime($pengambilanBank['tanggal_perencanaan_setor'])) : '-'; ?>
                                        </div>
                                    </div>
                                    <span class="status-badge <?php echo ($pengambilanBank['klasifikasi'] ?? 'internal') === 'hutang' ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo htmlspecialchars(strtoupper($pengambilanBank['klasifikasi'] ?? 'internal')); ?>
                                    </span>
                                </div>
                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-top:12px;">
                                    <div>
                                        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#64748b;">Nominal Diambil</div>
                                        <div style="font-weight:700; color:#0f172a;"><?php echo formatRupiah($pengambilanBank['nominal_diambil']); ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#64748b;">Sisa Disetor ke Bank</div>
                                        <div style="font-weight:700; color:#1d4ed8;"><?php echo formatRupiah($pengambilanBank['nominal_sisa']); ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#64748b;">No Rek Pemberi</div>
                                        <div style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($pengambilanBank['no_rekening_peminjam'] ?? '-'); ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#64748b;">No Rek Penerima</div>
                                        <div style="font-weight:700; color:#0f172a;"><?php echo htmlspecialchars($pengambilanBank['no_rekening_penerima'] ?? '-'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <h6 style="margin-bottom: 15px; color: var(--text-dark);"><i class="fas fa-list"></i> Detail Seluruh Transaksi Setoran (Semua Cabang)</h6>
                    <div class="table-container">
                        <div class="table-wrapper">
                            <div class="table-enhanced" style="overflow-x: auto;">
                                <table class="table" style="min-width: 900px;">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Cabang</th>
                                            <th>Kode Setoran</th>
                                            <th>Kode Transaksi</th>
                                            <th>Jenis</th>
                                            <th>Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($all_closing_detail)): ?>
                                            <?php $grand = 0; foreach ($all_closing_detail as $detail): $grand += (float)$detail['setoran_real']; ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($detail['tanggal_transaksi'] ?: $detail['tanggal_setoran'])); ?></td>
                                                    <td><strong><?php echo htmlspecialchars(strtoupper($detail['nama_cabang'])); ?></strong></td>
                                                    <td><code><?php echo htmlspecialchars($detail['kode_setoran']); ?></code></td>
                                                    <td><code><?php echo htmlspecialchars($detail['kode_transaksi']); ?></code></td>
                                                    <td>
                                                        <?php if ($detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                            <span class="status-badge bg-closing">CLOSING</span>
                                                        <?php else: ?>
                                                            <span class="status-badge bg-primary">BIASA</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="text-align: right; font-weight: 600;"><?php echo formatRupiah($detail['setoran_real']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="grand-total-row-fixed" style="background: #28a745; color: #fff; font-weight: bold;">
                                                <td colspan="5" style="text-align: right;">TOTAL KESELURUHAN:</td>
                                                <td style="text-align: right;"><?php echo formatRupiah($grand); ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="no-data">Tidak ada transaksi ditemukan</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="export_excel_setoran.php?type=bank_detail&bank_id=<?php echo $bank_detail_view['id']; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="?tab=bank_history" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Tutup
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced Cabang Closing Detail Modal -->
    <?php if (isset($cabang_closing_detail) && !empty($cabang_closing_detail)): ?>
    <div class="modal show">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: white; border-radius: 16px;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-building"></i> Detail Setoran Closing - <?php echo htmlspecialchars(strtoupper($_GET['cabang_closing'])); ?></h5>
                    <a href="?tab=bank_history" class="btn-close">&times;</a>
                </div>
                <div class="modal-body">
                    <div class="closing-summary">
                        <h4><i class="fas fa-calendar-alt"></i> Periode: <?php echo date('d/m/Y', strtotime($cabang_closing_detail[0]['tanggal_setoran'])); ?> - <?php echo date('d/m/Y', strtotime(end($cabang_closing_detail)['tanggal_setoran'])); ?></h4>
                        <div class="closing-grid">
                            <div class="closing-item">
                                <div class="closing-label">Total Setoran</div>
                                <div class="closing-value"><?php echo count(array_unique(array_column($cabang_closing_detail, 'kode_setoran'))); ?> setoran</div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Total Transaksi</div>
                                <div class="closing-value"><?php echo count($cabang_closing_detail); ?> transaksi</div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Transaksi Closing</div>
                                <div class="closing-value">
                                    <?php 
                                    $closing_count = count(array_filter($cabang_closing_detail, function($item) {
                                        return $item['jenis_transaksi'] == 'DARI CLOSING';
                                    }));
                                    echo $closing_count;
                                    ?>
                                </div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Total Nominal</div>
                                <div class="closing-value amount"><?php echo formatRupiah(array_sum(array_column($cabang_closing_detail, 'setoran_real'))); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-wrapper">
                            <div class="table-enhanced" style="overflow-x: auto;">
                                <table class="table" style="min-width: 900px;">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode Setoran</th>
                                            <th>Kode Transaksi</th>
                                            <th>Jenis</th>
                                            <th>Nominal Closing</th>
                                            <th>Setor</th>
                                            <th>Nominal Setor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        foreach ($cabang_closing_detail as $detail): 
                                            $row_class = $detail['jenis_transaksi'] == 'DARI CLOSING' ? 'closing-transaction' : '';
                                        ?>
                                            <tr class="<?php echo $row_class; ?>">
                                                <td><?php echo date('d/m/Y', strtotime($detail['tanggal_transaksi'])); ?></td>
                                                <td><code><?php echo htmlspecialchars($detail['kode_setoran']); ?></code></td>
                                                <td>
                                                    <div class="transaction-type-indicator">
                                                        <code><?php echo htmlspecialchars($detail['kode_transaksi']); ?></code>
                                                        <?php if ($detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                            <span class="closing-info-badge">CLOSING</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($detail['jenis_transaksi'] == 'DARI CLOSING'): ?>
                                                        <span class="status-badge bg-closing">DARI CLOSING</span>
                                                    <?php else: ?>
                                                        <span class="status-badge bg-primary">BIASA</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right;"><?php echo formatRupiah($detail['setoran_real']); ?></td>
                                                <td style="text-align: center;">√</td>
                                                <td style="text-align: right;"><?php echo formatRupiah($detail['setoran_real']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <!-- Grand total -->
                                        <tr id="grandTotalRow" class="grand-total-row-fixed" style="background: #28a745 !important; color: white !important; font-weight: bold !important; font-size: 16px !important; border: 2px solid #007bff !important; position: relative !important; z-index: 999 !important;">
                                            <td colspan="5" style="text-align: right !important; padding: 12px !important; font-size: 16px !important; background: #28a745 !important; color: white !important;">TOTAL KESELURUHAN:</td>
                                            <td style="text-align: right !important; padding: 12px !important; background: #28a745 !important; color: white !important;">√</td>
                                            <td style="text-align: right !important; padding: 12px !important; font-size: 16px !important; background: #28a745 !important; color: white !important;">
                                                <?php 
                                                $total_keseluruhan = 0;
                                                if (!empty($cabang_closing_detail)) {
                                                    foreach ($cabang_closing_detail as $detail) {
                                                        $total_keseluruhan += $detail['setoran_real'];
                                                    }
                                                }
                                                echo formatRupiah($total_keseluruhan); 
                                                ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    // Protect grand total row from disappearing
                    function protectGrandTotalRow() {
                        const grandTotalRow = document.getElementById('grandTotalRow');
                        if (grandTotalRow) {
                            // Force styles to prevent disappearing
                            grandTotalRow.style.cssText = 'background: #28a745 !important; color: white !important; font-weight: bold !important; font-size: 16px !important; border: 2px solid #007bff !important; position: relative !important; z-index: 999 !important; display: table-row !important; visibility: visible !important; opacity: 1 !important;';
                            
                            // Ensure all td elements have correct styling
                            const cells = grandTotalRow.querySelectorAll('td');
                            cells.forEach(cell => {
                                cell.style.cssText = 'background: #28a745 !important; color: white !important; font-weight: bold !important; padding: 12px !important; display: table-cell !important; visibility: visible !important; opacity: 1 !important;';
                            });
                            
                            // Create observer to watch for changes
                            const observer = new MutationObserver(() => {
                                if (grandTotalRow.style.display === 'none' || 
                                    grandTotalRow.style.visibility === 'hidden' ||
                                    grandTotalRow.style.opacity === '0') {
                                    grandTotalRow.style.cssText = 'background: #28a745 !important; color: white !important; font-weight: bold !important; font-size: 16px !important; border: 2px solid #007bff !important; position: relative !important; z-index: 999 !important; display: table-row !important; visibility: visible !important; opacity: 1 !important;';
                                }
                            });
                            
                            observer.observe(grandTotalRow, { 
                                attributes: true, 
                                attributeFilter: ['style', 'class'] 
                            });
                        }
                    }

                    // Run protection immediately and periodically
                    document.addEventListener('DOMContentLoaded', protectGrandTotalRow);
                    setTimeout(protectGrandTotalRow, 1000);
                    setTimeout(protectGrandTotalRow, 3000);
                    setTimeout(protectGrandTotalRow, 5000);
                    
                    // Also protect when window resizes or other events
                    window.addEventListener('resize', protectGrandTotalRow);
                    
                    // Override reinitializeTableScrolling to protect total row
                    const originalReinitialize = window.tableScrolling?.reinitialize;
                    if (originalReinitialize) {
                        window.tableScrolling.reinitialize = function() {
                            originalReinitialize.apply(this, arguments);
                            setTimeout(protectGrandTotalRow, 100);
                        };
                    }
                </script>

                <div class="modal-footer">
                    <a href="export_excel_setoran.php?type=cabang_closing&bank_id=<?php echo $_GET['bank_detail_id']; ?>&cabang=<?php echo urlencode($_GET['cabang_closing']); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="?tab=bank_history" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// PERBAIKAN: Lanjutan JavaScript dengan dukungan kalkulasi gabungan closing yang lebih baik

// Sidebar toggle functionality
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    sidebar.classList.toggle('hidden');
    mainContent.classList.toggle('fullscreen');
    
    if (sidebar.classList.contains('hidden')) {
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    } else {
        sidebarToggle.innerHTML = '<i class="fas fa-times"></i>';
    }
}

// PERBAIKAN: Enhanced table scrolling functionality
function initializeTableScrolling() {
    const tableContainers = document.querySelectorAll('.table-container');
    
    tableContainers.forEach((container, index) => {
        // Add scroll wrapper if not exists
        const existingWrapper = container.querySelector('.table-wrapper');
        if (!existingWrapper) {
            const table = container.querySelector('.table');
            if (table) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-wrapper';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        }
        
        const wrapper = container.querySelector('.table-wrapper');
        if (wrapper) {
            // Add scroll event listener untuk visual feedback
            wrapper.addEventListener('scroll', function() {
                const isScrolledLeft = this.scrollLeft > 10;
                const isScrolledRight = this.scrollLeft < (this.scrollWidth - this.clientWidth - 10);
                
                // Add/remove classes for styling
                container.classList.toggle('scrolled-left', isScrolledLeft);
                container.classList.toggle('scrolled-right', isScrolledRight);
                
                // Update scroll indicators
                updateScrollIndicators(container, this);
            });
            
            // Add keyboard navigation
            wrapper.addEventListener('keydown', function(e) {
                const scrollAmount = 100;
                
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.scrollLeft -= scrollAmount;
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.scrollLeft += scrollAmount;
                        break;
                    case 'Home':
                        e.preventDefault();
                        this.scrollLeft = 0;
                        break;
                    case 'End':
                        e.preventDefault();
                        this.scrollLeft = this.scrollWidth;
                        break;
                }
            });
            
            // Make wrapper focusable for keyboard navigation
            wrapper.setAttribute('tabindex', '0');
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', 'Scrollable table');
            
            // Initial check
            wrapper.dispatchEvent(new Event('scroll'));
            
            // Show scroll hint for first table
            if (index === 0 && wrapper.scrollWidth > wrapper.clientWidth) {
                showScrollHint(container);
            }
        }
    });
}

// Function to update scroll indicators
function updateScrollIndicators(container, wrapper) {
    const isAtStart = wrapper.scrollLeft <= 10;
    const isAtEnd = wrapper.scrollLeft >= (wrapper.scrollWidth - wrapper.clientWidth - 10);
    
    // Update container classes
    container.classList.toggle('at-start', isAtStart);
    container.classList.toggle('at-end', isAtEnd);
    
    // Update scroll progress indicator if exists
    const progressIndicator = container.querySelector('.scroll-progress');
    if (progressIndicator) {
        const progress = (wrapper.scrollLeft / (wrapper.scrollWidth - wrapper.clientWidth)) * 100;
        progressIndicator.style.width = Math.min(100, Math.max(0, progress)) + '%';
    }
}

// Function to show scroll hint
function showScrollHint(container) {
    const hint = document.createElement('div');
    hint.className = 'scroll-hint';
    hint.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Scroll untuk melihat kolom lainnya';
    
    container.style.position = 'relative';
    container.appendChild(hint);
    
    // Remove hint after animation
    setTimeout(() => {
        if (hint.parentNode) {
            hint.remove();
        }
    }, 3500);
}

// Function to add scroll progress indicator
function addScrollProgressIndicator() {
    const tableContainers = document.querySelectorAll('.table-container');
    
    tableContainers.forEach(container => {
        const wrapper = container.querySelector('.table-wrapper');
        if (wrapper && wrapper.scrollWidth > wrapper.clientWidth) {
            // Create progress container
            const progressContainer = document.createElement('div');
            progressContainer.className = 'scroll-progress-container';
            
            // Create progress bar
            const progressBar = document.createElement('div');
            progressBar.className = 'scroll-progress';
            
            progressContainer.appendChild(progressBar);
            container.appendChild(progressContainer);
        }
    });
}

// Enhanced smooth scrolling functions
function scrollTableTo(container, direction) {
    const wrapper = container.querySelector('.table-wrapper');
    if (!wrapper) return;
    
    const scrollAmount = wrapper.clientWidth * 0.8; // Scroll 80% of visible width
    const targetScroll = direction === 'left' 
        ? wrapper.scrollLeft - scrollAmount 
        : wrapper.scrollLeft + scrollAmount;
    
    // Smooth scroll
    wrapper.scrollTo({
        left: targetScroll,
        behavior: 'smooth'
    });
}

// Add scroll buttons for better UX
function addScrollButtons() {
    const tableContainers = document.querySelectorAll('.table-container');
    
    tableContainers.forEach(container => {
        const wrapper = container.querySelector('.table-wrapper');
        if (wrapper && wrapper.scrollWidth > wrapper.clientWidth) {
            // Left scroll button
            const leftButton = document.createElement('button');
            leftButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
            leftButton.className = 'table-scroll-btn table-scroll-left';
            
            // Right scroll button
            const rightButton = document.createElement('button');
            rightButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
            rightButton.className = 'table-scroll-btn table-scroll-right';
            rightButton.style.opacity = '1';
            
            // Add event listeners
            leftButton.addEventListener('click', () => scrollTableTo(container, 'left'));
            rightButton.addEventListener('click', () => scrollTableTo(container, 'right'));
            
            // Add hover effects
            [leftButton, rightButton].forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(0,123,255,1)';
                    this.style.transform = 'translateY(-50%) scale(1.1)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.background = 'rgba(0,123,255,0.9)';
                    this.style.transform = 'translateY(-50%) scale(1)';
                });
            });
            
            container.appendChild(leftButton);
            container.appendChild(rightButton);
            
            // Update button visibility on scroll
            wrapper.addEventListener('scroll', function() {
                const isAtStart = this.scrollLeft <= 10;
                const isAtEnd = this.scrollLeft >= (this.scrollWidth - this.clientWidth - 10);
                
                leftButton.style.opacity = isAtStart ? '0' : '1';
                rightButton.style.opacity = isAtEnd ? '0' : '1';
            });
        }
    });
}

// Touch/swipe support for mobile
function isHorizontalScrollTarget(element) {
    if (!element || typeof element.closest !== 'function') {
        return false;
    }

    return Boolean(element.closest('.table-wrapper, .table-responsive, .table-enhanced, .summary-table-scroll, .nav-tabs, .table-scroll-btn'));
}

function addTouchSupport() {
    const tableWrappers = document.querySelectorAll('.table-wrapper, .table-responsive, .table-enhanced, .summary-table-scroll');
    
    tableWrappers.forEach(wrapper => {
        if (wrapper.dataset.mobileTouchReady === '1') {
            return;
        }
        wrapper.dataset.mobileTouchReady = '1';

        let startX = 0;
        let startY = 0;
        let scrollStart = 0;
        let isDragging = false;
        let isHorizontalIntent = false;
        let scrollElement = wrapper;
        
        wrapper.addEventListener('touchstart', function(e) {
            scrollElement = this.classList.contains('table-enhanced')
                ? (this.closest('.table-wrapper, .table-responsive, .summary-table-scroll') || this)
                : this;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            scrollStart = scrollElement.scrollLeft;
            isDragging = true;
            isHorizontalIntent = false;
            scrollElement.style.scrollBehavior = 'auto';
        }, { passive: true });
        
        wrapper.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;
            const diffX = startX - currentX;
            const diffY = startY - currentY;

            if (!isHorizontalIntent) {
                isHorizontalIntent = Math.abs(diffX) > 8 && Math.abs(diffX) > Math.abs(diffY);
            }
            
            // Hanya ambil alih gesture jika benar-benar gesture horizontal.
            if (isHorizontalIntent) {
                e.preventDefault();
                scrollElement.scrollLeft = scrollStart + diffX;
            }
        }, { passive: false });
        
        wrapper.addEventListener('touchend', function() {
            isDragging = false;
            isHorizontalIntent = false;
            scrollElement.style.scrollBehavior = 'smooth';
        }, { passive: true });
        
        wrapper.addEventListener('touchcancel', function() {
            isDragging = false;
            isHorizontalIntent = false;
            scrollElement.style.scrollBehavior = 'smooth';
        }, { passive: true });
    });
}

// Re-initialize when content changes (for dynamic content)
function reinitializeTableScrolling() {
    // Remove existing elements
    document.querySelectorAll('.scroll-progress-container, .table-scroll-btn, .scroll-hint').forEach(el => el.remove());
    
    // Re-initialize
    setTimeout(() => {
        initializeTableScrolling();
        addScrollProgressIndicator();
        addTouchSupport();
    }, 100);
}

// Auto-hide sidebar on bank_history tab
document.addEventListener('DOMContentLoaded', function() {
    const currentTab = '<?php echo $tab; ?>';
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (currentTab === 'bank_history') {
        sidebar.classList.add('hidden');
        mainContent.classList.add('fullscreen');
        sidebarToggle.classList.add('show');
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    } else {
        sidebar.classList.remove('hidden');
        mainContent.classList.remove('fullscreen');
        sidebarToggle.classList.remove('show');
    }
    
    // Initialize closing transaction highlighting
    initializeClosingTransactionHighlighting();
    
    // Initialize enhanced tooltips for closing transactions
    initializeClosingTooltips();
    
    // PERBAIKAN: Initialize table scroll untuk tabel yang lebar
    initializeTableScrolling();
    addScrollProgressIndicator();
    addTouchSupport();
});

// Initialize closing transaction highlighting
function initializeClosingTransactionHighlighting() {
    const closingRows = document.querySelectorAll('.closing-transaction');
    closingRows.forEach(row => {
        // Add hover effect
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(156,39,176,0.15)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'rgba(156,39,176,0.05)';
        });
        
        // Add pulse animation for new closing transactions
        if (row.querySelector('.closing-info-badge')) {
            row.style.animation = 'pulse 2s infinite';
        }
    });
}

// PERBAIKAN: Initialize enhanced tooltips for closing transactions dengan info gabungan
function initializeClosingTooltips() {
    const closingBadges = document.querySelectorAll('.closing-info-badge');
    closingBadges.forEach(badge => {
        badge.title = 'Transaksi Closing: Gabungan dari transaksi closing, transaksi yang dipinjam, dan transaksi yang meminjam per cabang';
        badge.style.cursor = 'help';
    });
    
    const closingTransactions = document.querySelectorAll('.closing-transaction');
    closingTransactions.forEach(row => {
        row.title = 'Baris ini mengandung transaksi closing gabungan';
        row.style.cursor = 'pointer';
    });
    
    // PERBAIKAN: Add tooltips untuk closing borrowed info
    const closingBorrowedInfos = document.querySelectorAll('.closing-borrowed-info');
    closingBorrowedInfos.forEach(info => {
        info.title = 'Informasi gabungan: menampilkan jumlah yang dipinjam dan yang seharusnya diterima secara fisik';
        info.style.cursor = 'help';
    });
}

// Initialize currency formatting for validation inputs
document.getElementById('jumlahDiterima')?.addEventListener('input', function(e) {
    formatCurrencyInputValue(e);
    const isClosing = checkIfClosingTransaction();
    hitungSelisihTransaksi(isClosing);
});

document.getElementById('jumlahDiterimaBaru')?.addEventListener('input', function(e) {
    formatCurrencyInputValue(e);
    const isClosing = checkIfClosingTransaction();
    hitungSelisihEdit(isClosing);
});

// Helper function to format currency input value
function formatCurrencyInputValue(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    if (value) {
        value = parseInt(value).toLocaleString('id-ID');
        e.target.value = 'Rp ' + value;
    } else {
        e.target.value = '';
    }
}

// PERBAIKAN: Enhanced function to check if current transaction is closing dengan context gabungan
function checkIfClosingTransaction() {
    // Check from PHP data or modal indicators
    const closingBadge = document.querySelector('.closing-info-badge');
    const closingInfo = document.querySelector('.closing-validation-info');
    
    // Check juga dari data transaksi yang ada
    const closingBorrowedInfo = document.querySelector('.closing-borrowed-info');
    
    return closingBadge !== null || closingInfo !== null || closingBorrowedInfo !== null;
}

// PERBAIKAN: Function untuk mendapatkan data closing borrowed dari PHP
function getClosingBorrowedAmount() {
    // Ambil dari PHP data yang sudah di-pass ke JavaScript
    const transaksiDetail = <?php echo json_encode($transaksi_detail ?? null); ?>;
    const editSelisihDetail = <?php echo json_encode($edit_selisih_detail ?? null); ?>;
    
    let borrowedAmount = 0;
    
    if (transaksiDetail && transaksiDetail.total_closing_borrowed) {
        borrowedAmount = parseFloat(transaksiDetail.total_closing_borrowed) || 0;
    } else if (editSelisihDetail && editSelisihDetail.total_closing_borrowed) {
        borrowedAmount = parseFloat(editSelisihDetail.total_closing_borrowed) || 0;
    }

    return Math.max(0, borrowedAmount);
}

// PERBAIKAN: Enhanced calculation function untuk transaksi validation dengan closing support dan kalkulasi gabungan
function hitungSelisihTransaksi(isClosing = false) {
    const sistemAmount = <?php echo isset($transaksi_detail['setoran_real']) ? $transaksi_detail['setoran_real'] : 0; ?>;
    const borrowedAmount = getClosingBorrowedAmount();
    
    let diterima = document.getElementById('jumlahDiterima')?.value.replace(/[^0-9]/g, '') || 0;
    diterima = parseInt(diterima) || 0;

    // PERBAIKAN: Kalkulasi selisih dengan mempertimbangkan gabungan closing
    let expectedAmount = sistemAmount;
    if (isClosing && borrowedAmount > 0) {
        // Untuk transaksi closing dengan pinjaman, yang diharapkan diterima = setoran_real - yang dipinjam
        expectedAmount = sistemAmount - borrowedAmount;
    }
    expectedAmount = Math.max(0, expectedAmount);

    const selisih = diterima - expectedAmount;
    const selisihRow = document.getElementById('selisihRow');
    const selisihAmount = document.getElementById('selisihAmount');

    if (selisihRow && selisihAmount) {
        if (selisih !== 0) {
            selisihRow.style.display = 'block';
            
            let selisihText = '';
            let selisihColor = '';
            
            if (selisih > 0) {
                selisihText = '<i class="fas fa-arrow-up"></i> Rp ' + selisih.toLocaleString('id-ID');
                selisihColor = 'var(--success-color)';
            } else {
                selisihText = '<i class="fas fa-arrow-down"></i> Rp ' + Math.abs(selisih).toLocaleString('id-ID');
                selisihColor = 'var(--danger-color)';
            }
            
            selisihAmount.style.color = selisihColor;
            selisihAmount.innerHTML = selisihText;
            
            // PERBAIKAN: Add closing transaction indicator dengan info gabungan
            if (isClosing) {
                let closingInfo = ' <span class="closing-info-badge" style="margin-left: 5px;">CLOSING</span>';
                if (borrowedAmount > 0) {
                    closingInfo += '<br><small style="font-size: 10px; color: var(--text-muted);">Dipinjam: Rp ' + borrowedAmount.toLocaleString('id-ID') + '</small>';
                }
                selisihAmount.innerHTML += closingInfo;
            }
        } else {
            selisihRow.style.display = 'none';
        }
    }
    
    // Update validation button text for closing transactions
    updateValidationButtonText(isClosing, selisih, borrowedAmount);
}
// PERBAIKAN: Enhanced calculation function untuk edit selisih dengan closing support dan kalkulasi gabungan
function hitungSelisihEdit(isClosing = false) {
    const sistemAmount = <?php echo isset($edit_selisih_detail['setoran_real']) ? $edit_selisih_detail['setoran_real'] : 0; ?>;
    const borrowedAmount = getClosingBorrowedAmount();
    
    let diterima = document.getElementById('jumlahDiterimaBaru')?.value.replace(/[^0-9]/g, '') || 0;
    diterima = parseInt(diterima) || 0;

    // PERBAIKAN: Kalkulasi selisih dengan mempertimbangkan gabungan closing
    let expectedAmount = sistemAmount;
    if (isClosing && borrowedAmount > 0) {
        // Untuk transaksi closing dengan pinjaman, yang diharapkan diterima = setoran_real - yang dipinjam
        expectedAmount = sistemAmount - borrowedAmount;
    }
    expectedAmount = Math.max(0, expectedAmount);

    const selisih = diterima - expectedAmount;
    const selisihRow = document.getElementById('selisihEditRow');
    const selisihAmount = document.getElementById('selisihEditAmount');

    if (selisihRow && selisihAmount) {
        let selisihText = '';
        let selisihColor = '';
        
        if (selisih > 0) {
            selisihText = '<i class="fas fa-arrow-up"></i> Rp ' + selisih.toLocaleString('id-ID');
            selisihColor = 'var(--success-color)';
        } else if (selisih < 0) {
            selisihText = '<i class="fas fa-arrow-down"></i> Rp ' + Math.abs(selisih).toLocaleString('id-ID');
            selisihColor = 'var(--danger-color)';
        } else {
            selisihText = '<i class="fas fa-check"></i> Sesuai Sistem';
            selisihColor = 'var(--text-dark)';
        }
        
        selisihAmount.style.color = selisihColor;
        selisihAmount.innerHTML = selisihText;
        
        // PERBAIKAN: Add closing transaction indicator dengan info gabungan
        if (isClosing) {
            let closingInfo = ' <span class="closing-info-badge" style="margin-left: 5px;">CLOSING</span>';
            if (borrowedAmount > 0) {
                closingInfo += '<br><small style="font-size: 10px; color: var(--text-muted);">Dipinjam: Rp ' + borrowedAmount.toLocaleString('id-ID') + '</small>';
            }
            selisihAmount.innerHTML += closingInfo;
        }
    }
    
    // Update edit button text for closing transactions
    updateEditButtonText(isClosing, selisih, borrowedAmount);
}

// PERBAIKAN: Function to update validation button text based on transaction type dengan info gabungan
function updateValidationButtonText(isClosing, selisih, borrowedAmount = 0) {
    const validationButton = document.querySelector('button[name="validasi_individual"]');
    if (validationButton) {
        let buttonText = '<i class="fas fa-save"></i> Simpan Validasi';
        
        if (isClosing) {
            buttonText += ' Closing';
            
            if (borrowedAmount > 0) {
                buttonText += ' (Gabungan)';
            }
            
            if (selisih !== 0) {
                buttonText = '<i class="fas fa-exclamation-triangle"></i> Simpan Validasi Closing (Ada Selisih)';
                validationButton.className = 'btn btn-warning';
            } else {
                buttonText = '<i class="fas fa-check"></i> Simpan Validasi Closing (Sesuai)';
                validationButton.className = 'btn btn-success';
            }
        } else {
            if (selisih !== 0) {
                validationButton.className = 'btn btn-warning';
            } else {
                validationButton.className = 'btn btn-primary';
            }
        }
        
        validationButton.innerHTML = buttonText;
    }
}

// PERBAIKAN: Function to update edit button text based on transaction type dengan info gabungan
function updateEditButtonText(isClosing, selisih, borrowedAmount = 0) {
    const editButton = document.querySelector('button[name="edit_selisih"]');
    if (editButton) {
        let buttonText = '<i class="fas fa-save"></i> Simpan Perubahan';
        
        if (isClosing) {
            buttonText += ' Closing';
            
            if (borrowedAmount > 0) {
                buttonText += ' (Gabungan)';
            }
            
            if (selisih !== 0) {
                buttonText = '<i class="fas fa-exclamation-triangle"></i> Simpan Perubahan Closing (Ada Selisih)';
                editButton.className = 'btn btn-warning';
            } else {
                buttonText = '<i class="fas fa-check"></i> Simpan Perubahan Closing (Sesuai)';
                editButton.className = 'btn btn-success';
            }
        } else {
            if (selisih !== 0) {
                editButton.className = 'btn btn-warning';
            } else {
                editButton.className = 'btn btn-primary';
            }
        }
        
        editButton.innerHTML = buttonText;
    }
}

// Enhanced filter function with closing transaction awareness
function filterByCabang(rekeningId) {
    console.log('Selected rekening ID:', rekeningId);
    const params = new URLSearchParams(window.location.search);
    params.set('tab', 'setor_bank');

    if (rekeningId === '' || rekeningId === 'all') {
        params.set('rekening_filter', 'all');
    } else {
        // Send ALL IDs for proper gabungan filtering
        params.set('rekening_filter', rekeningId);
    }

    // Preserve date filters if present in the form
    const tanggalAwalInput = document.querySelector('input[name="tanggal_awal"]');
    const tanggalAkhirInput = document.querySelector('input[name="tanggal_akhir"]');
    const cabangSelect = document.querySelector('select[name="cabang"]');

    if (tanggalAwalInput && tanggalAwalInput.value) {
        params.set('tanggal_awal', tanggalAwalInput.value);
    } else {
        params.delete('tanggal_awal');
    }

    if (tanggalAkhirInput && tanggalAkhirInput.value) {
        params.set('tanggal_akhir', tanggalAkhirInput.value);
    } else {
        params.delete('tanggal_akhir');
    }

    if (cabangSelect && cabangSelect.value && cabangSelect.value !== 'all') {
        params.set('cabang', cabangSelect.value);
    } else {
        params.delete('cabang');
    }

    window.location.href = `?${params.toString()}`;
}

// Enhanced form submission validation for setor bank with closing support
let isFormSubmittingFinal = false; // Flag to allow final submission

document.getElementById('setorBankForm')?.addEventListener('submit', function(e) {
    const rekeningCabang = document.getElementById('rekeningCabang') || document.querySelector('#setorBankForm [name="rekening_cabang_id"]');
    const tanggalSetor = document.getElementById('tanggalSetoranMainInput');
    const checkedBoxes = document.querySelectorAll('.bankCheckbox:checked');
    
    if (!rekeningCabang || !rekeningCabang.value || rekeningCabang.value === '') {
        e.preventDefault();
        showNotification('Pilih rekening cabang tujuan terlebih dahulu.', 'warning');
        if (rekeningCabang && typeof rekeningCabang.focus === 'function') {
            rekeningCabang.focus();
        }
        return false;
    }

    if (!tanggalSetor || !tanggalSetor.value) {
        e.preventDefault();
        showNotification('Tanggal perencanaan setor wajib diisi.', 'warning');
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        showNotification('Pilih setoran yang akan disetor ke bank.', 'warning');
        return false;
    }
    
    // Check if any closing transactions are included
    const closingTransactions = document.querySelectorAll('.bankCheckbox:checked').length;
    let hasClosingTransactions = false;
    
    checkedBoxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        if (row && row.classList.contains('closing-transaction')) {
            hasClosingTransactions = true;
        }
    });
    
    let confirmMessage = 'Yakin ingin setor ke bank? Pastikan semua data sudah benar.';
    if (hasClosingTransactions) {
        confirmMessage = 'Yakin ingin setor ke bank? Termasuk transaksi closing. Pastikan semua data sudah benar.';
    }
    
    if (!confirm(confirmMessage)) {
        e.preventDefault();
        return false;
    }
    
    return true;
});

// PERBAIKAN: Enhanced setoran summary dengan closing transaction details dan kalkulasi gabungan
function showSetoranSummary() {
    const checkedBoxes = document.querySelectorAll('.bankCheckbox:checked');
    if (checkedBoxes.length === 0) {
        showNotification('Pilih setoran yang akan disetor terlebih dahulu.', 'warning');
        return;
    }
    
    let totalAmount = 0;
    let setoranList = [];
    let closingCount = 0;
    let totalClosingAmount = 0;
    
    checkedBoxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const kodeSetoran = getBankRowKodeSetoran(row);
        const cabang = getBankRowCabang(row);
        const nominal = getBankRowNettoSetor(row);
        const isClosing = row.classList.contains('closing-transaction');
        
        if (isClosing) {
            closingCount++;
            totalClosingAmount += nominal;
        }
        
        totalAmount += nominal;
        setoranList.push({
            kode: kodeSetoran,
            cabang: cabang,
            nominal: nominal,
            isClosing: isClosing
        });
    });
    
    let summaryHTML = `
        <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 15px; color: var(--text-dark);">
                <i class="fas fa-calculator"></i> Ringkasan Setoran ke Bank
            </h4>
            <div style="margin-bottom: 15px;">
                <strong>Jumlah Setoran Dipilih:</strong> ${setoranList.length} paket<br>
                ${closingCount > 0 ? `<strong>Transaksi Closing Gabungan:</strong> <span style="color: var(--closing-color);">${closingCount} paket (${formatRupiah(totalClosingAmount)})</span><br>` : ''}
                <strong>Total Nominal:</strong> <span style="color: var(--success-color); font-size: 18px; font-weight: bold;">Rp ${totalAmount.toLocaleString('id-ID')}</span>
            </div>
            ${closingCount > 0 ? `
            <div style="background: rgba(156,39,176,0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(156,39,176,0.2);">
                <small style="color: var(--closing-color); font-weight: 600;">
                    <i class="fas fa-info-circle"></i> ${closingCount} setoran mengandung transaksi closing gabungan yang merupakan hasil dari transaksi closing, transaksi yang dipinjam, dan transaksi yang meminjam per cabang. Total nilai closing: ${formatRupiah(totalClosingAmount)}.
                </small>
            </div>
            ` : ''}
            <div class="summary-table-scroll" style="max-height: 220px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; -webkit-overflow-scrolling: touch;">
                <table class="table" style="min-width: 620px; width: 100%; font-size: 12px; white-space: nowrap;">
                    <thead>
                        <tr style="background: var(--background-light);">
                            <th style="padding: 5px; text-align: left;">Kode Setoran</th>
                            <th style="padding: 5px; text-align: left;">Cabang</th>
                            <th style="padding: 5px; text-align: center;">Jenis</th>
                            <th style="padding: 5px; text-align: right;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    setoranList.forEach(setoran => {
        const jenisLabel = setoran.isClosing ? 
            '<span class="status-badge bg-closing" style="font-size: 9px;">CLOSING</span>' : 
            '<span class="status-badge bg-primary" style="font-size: 9px;">BIASA</span>';
            
        summaryHTML += `
            <tr class="${setoran.isClosing ? 'closing-transaction' : ''}">
                <td style="padding: 3px;">${setoran.kode}</td>
                <td style="padding: 3px;">${setoran.cabang}</td>
                <td style="padding: 3px; text-align: center;">${jenisLabel}</td>
                <td style="padding: 3px; text-align: right;">Rp ${setoran.nominal.toLocaleString('id-ID')}</td>
            </tr>
        `;
    });
    
    summaryHTML += `
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 15px; text-align: right;">
                <button onclick="closeSummary()" class="btn btn-secondary btn-sm" style="margin-right: 10px;">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    `;
    
    showModal('summaryModal', summaryHTML);
}

// PERBAIKAN: Helper function untuk format rupiah
function formatRupiah(amount) {
    return 'Rp ' + amount.toLocaleString('id-ID');
}

// Direct submission function that bypasses modal validation
function submitDirectly() {
    const form = document.getElementById('setorBankForm');
    const rekeningCabang = document.getElementById('rekeningCabang');
    const checkedBoxes = document.querySelectorAll('.bankCheckbox:checked');
    
    if (!rekeningCabang || !rekeningCabang.value || rekeningCabang.value === '') {
        showNotification('Pilih rekening cabang tujuan terlebih dahulu.', 'warning');
        if (rekeningCabang) rekeningCabang.focus();
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        showNotification('Pilih setoran yang akan disetor ke bank.', 'warning');
        return false;
    }
    
    if (confirm('Yakin ingin setor ke bank secara langsung? Pastikan semua data sudah benar.')) {
        // Set flag and submit directly
        isFormSubmittingFinal = true;
        console.log('Direct submission initiated');
        form.submit();
    }
}

// Generic modal display function
function showModal(modalId, content) {
    // Remove existing modal
    const existingModal = document.getElementById(modalId);
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal overlay
    const modalOverlay = document.createElement('div');
    modalOverlay.id = modalId;
    modalOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        max-width: 600px;
        max-height: 80%;
        overflow-y: auto;
        margin: 20px;
    `;
    modalContent.innerHTML = content;
    
    modalOverlay.appendChild(modalContent);
    document.body.appendChild(modalOverlay);
    
    // Close on overlay click
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.remove();
        }
    });
}

// Close summary modal
function closeSummary() {
    const modal = document.getElementById('summaryModal');
    if (modal) {
        modal.remove();
    }
}

// Close receipt card
function closeReceipt() {
    const receiptCard = document.querySelector('.receipt-card');
    if (receiptCard) {
        receiptCard.style.display = 'none';
    }
}

// Select all checkboxes functionality with closing transaction awareness
document.getElementById('selectAllTerima')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.terimaCheckbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    
    // Show info about closing transactions if any
    if (this.checked) {
        const closingCount = document.querySelectorAll('.closing-transaction .terimaCheckbox').length;
        if (closingCount > 0) {
            showNotification(`Dipilih ${checkboxes.length} setoran, termasuk ${closingCount} dengan transaksi closing gabungan.`, 'info');
        }
    }
});

document.getElementById('selectAllBank')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.bankCheckbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    
    updateSummaryButtonVisibility();
    
    // Show info about closing transactions if any
    if (this.checked) {
        const closingCount = document.querySelectorAll('.closing-transaction .bankCheckbox').length;
        if (closingCount > 0) {
            showNotification(`Dipilih ${checkboxes.length} setoran, termasuk ${closingCount} dengan transaksi closing gabungan.`, 'info');
        }
    }
});

// Update summary button visibility and text
document.querySelectorAll('.bankCheckbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        updateSummaryButtonVisibility();
        updateSelectAllCheckbox();
        
        // PERBAIKAN: Update button text based on closing transactions dengan info gabungan
        const checkedBoxes = document.querySelectorAll('.bankCheckbox:checked');
        let closingCount = 0;
        let totalClosingAmount = 0;
        
        checkedBoxes.forEach(cb => {
            const row = cb.closest('tr');
            if (row && row.classList.contains('closing-transaction')) {
                closingCount++;
                totalClosingAmount += getBankRowNettoSetor(row);
            }
        });
        
        const summaryButton = document.querySelector('button[onclick="showSetoranSummary()"]');
        if (summaryButton && checkedBoxes.length > 0) {
            let buttonText = '<i class="fas fa-calculator"></i> Lihat Ringkasan';
            if (closingCount > 0) {
                buttonText += ` (${closingCount} Closing: ${formatRupiah(totalClosingAmount)})`;
            }
            summaryButton.innerHTML = buttonText;
        }
    });
});

function updateSummaryButtonVisibility() {
    const checkedCount = document.querySelectorAll('.bankCheckbox:checked').length;
    const summaryButton = document.querySelector('button[onclick="showSetoranSummary()"]');
    if (summaryButton) {
        summaryButton.style.display = checkedCount > 0 ? 'inline-flex' : 'none';
    }
}

function updateSelectAllCheckbox() {
    const checkedCount = document.querySelectorAll('.bankCheckbox:checked').length;
    const totalCheckboxes = document.querySelectorAll('.bankCheckbox').length;
    const selectAllBank = document.getElementById('selectAllBank');
    
    if (selectAllBank) {
        selectAllBank.checked = checkedCount === totalCheckboxes;
        selectAllBank.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
    }
}

// Enhanced notification system with closing transaction support
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} show`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease-out;
    `;
    
    const iconMap = {
        'success': 'check-circle',
        'danger': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    notification.innerHTML = `
        <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()" style="margin-left: auto;">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, duration);
}

const serverNotification = <?php
    if (isset($message)) {
        echo json_encode(['type' => 'success', 'message' => $message], JSON_UNESCAPED_UNICODE);
    } elseif (isset($error)) {
        echo json_encode(['type' => 'danger', 'message' => $error], JSON_UNESCAPED_UNICODE);
    } else {
        echo 'null';
    }
?>;

if (serverNotification && serverNotification.message) {
    document.addEventListener('DOMContentLoaded', function() {
        showNotification(serverNotification.message, serverNotification.type, serverNotification.type === 'danger' ? 7000 : 6000);
    });
}

// Close modals when clicking outside with closing transaction context
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            const currentTab = '<?php echo $tab; ?>';
            const isClosingModal = this.querySelector('.closing-validation-info') !== null;
            
            if (isClosingModal) {
                const confirmMessage = 'Menutup modal validasi transaksi closing. Data yang dimasukkan akan hilang. Lanjutkan?';
                if (!confirm(confirmMessage)) {
                    return;
                }
            }
            
            // Redirect based on current tab
            if (currentTab === 'validasi') {
                window.location.href = '?tab=validasi';
            } else if (currentTab === 'validasi_selisih') {
                window.location.href = '?tab=validasi_selisih';
            } else if (currentTab === 'bank_history') {
                window.location.href = '?tab=bank_history';
            } else {
                window.location.href = '?tab=' + currentTab;
            }
        }
    });
});

// Auto hide alerts with enhanced timing for closing transactions
document.querySelectorAll('.alert.show').forEach(alert => {
    const isClosingAlert = alert.textContent.includes('CLOSING') || alert.textContent.includes('closing');
    const duration = isClosingAlert ? 7000 : 5000; // Longer display for closing alerts
    
    setTimeout(() => {
        alert.style.animation = 'fadeOut 0.5s ease-out';
        setTimeout(() => alert.classList.remove('show'), 500);
    }, duration);
});

// Enhanced table interactions with closing transaction awareness
document.querySelectorAll('.table tbody tr').forEach(row => {
    const isClosing = row.classList.contains('closing-transaction');
    
    row.addEventListener('mouseenter', function() {
        if (isClosing) {
            this.style.backgroundColor = 'rgba(156,39,176,0.15)';
            this.style.borderLeft = '4px solid var(--closing-color)';
        } else {
            this.style.backgroundColor = 'rgba(0,123,255,0.05)';
        }
    });
    
    row.addEventListener('mouseleave', function() {
        if (isClosing) {
            this.style.backgroundColor = 'rgba(156,39,176,0.05)';
            this.style.borderLeft = '4px solid var(--closing-color)';
        } else {
            this.style.backgroundColor = '';
            this.style.borderLeft = '';
        }
    });
});

// Keyboard shortcuts with closing transaction support
document.addEventListener('keydown', function(e) {
    // Ctrl + P for print (when modal is open)
    if (e.ctrlKey && e.key === 'p') {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            e.preventDefault();
            window.print();
        }
    }
    
    // Escape to close modals with closing transaction confirmation
    if (e.key === 'Escape') {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            const isClosingModal = modal.querySelector('.closing-validation-info') !== null;
            
            if (isClosingModal) {
                const confirmMessage = 'Menutup modal transaksi closing. Data yang dimasukkan akan hilang. Lanjutkan?';
                if (!confirm(confirmMessage)) {
                    return;
                }
            }
            
            const closeButton = modal.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }
        
        // Close summary modal
        const summaryModal = document.getElementById('summaryModal');
        if (summaryModal) {
            closeSummary();
        }
        
        // Close sidebar on mobile
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && !sidebar.classList.contains('hidden')) {
                toggleSidebar();
            }
        }
    }
    
    // Ctrl + B to toggle sidebar
    if (e.ctrlKey && e.key === 'b') {
        e.preventDefault();
        toggleSidebar();
    }
    
    // Ctrl + C to show closing transaction info (when viewing tables)
    if (e.ctrlKey && e.key === 'c' && !e.target.matches('input, textarea')) {
        e.preventDefault();
        showClosingTransactionSummary();
    }
});
// Function to show closing transaction summary
function showClosingTransactionSummary() {
    const closingRows = document.querySelectorAll('.closing-transaction');
    if (closingRows.length === 0) {
        showNotification('Tidak ada transaksi closing dalam tampilan saat ini.', 'info');
        return;
    }
    
    let summaryHTML = `
        <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 15px; color: var(--closing-color);">
                <i class="fas fa-sync-alt"></i> Ringkasan Transaksi Closing
            </h4>
            <div style="margin-bottom: 15px;">
                <p><strong>Total Transaksi Closing:</strong> ${closingRows.length}</p>
                <p style="font-size: 14px; color: var(--text-muted);">
                    Transaksi closing adalah gabungan dari transaksi closing, transaksi yang dipinjam, dan transaksi yang meminjam per cabang.
                </p>
            </div>
            <div style="text-align: right;">
                <button onclick="closeClosingSummary()" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    `;
    
    showModal('closingSummaryModal', summaryHTML);
}

function closeClosingSummary() {
    const modal = document.getElementById('closingSummaryModal');
    if (modal) {
        modal.remove();
    }
}

// Mobile touch handlers with closing transaction support
let touchStartX = 0;
let touchEndX = 0;
let touchStartY = 0;
let touchEndY = 0;
let touchStartedInHorizontalArea = false;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
    touchStartY = e.changedTouches[0].screenY;
    touchStartedInHorizontalArea = isHorizontalScrollTarget(e.target);
}, { passive: true });

document.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    touchEndY = e.changedTouches[0].screenY;
    handleGesture();
}, { passive: true });

function handleGesture() {
    const swipeThreshold = 100;
    const swipeDistance = touchEndX - touchStartX;
    const verticalDistance = Math.abs(touchEndY - touchStartY);

    if (touchStartedInHorizontalArea || verticalDistance > 80) {
        return;
    }
    
    if (window.innerWidth <= 768) {
        // Swipe right to open sidebar
        if (swipeDistance > swipeThreshold) {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && sidebar.classList.contains('hidden')) {
                toggleSidebar();
            }
        }
        
        // Swipe left to close sidebar
        if (swipeDistance < -swipeThreshold) {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && !sidebar.classList.contains('hidden')) {
                toggleSidebar();
            }
        }
    }
}

// Responsive adjustments with closing transaction considerations
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const currentTab = '<?php echo $tab; ?>';
    
    if (window.innerWidth > 768) {
        // Desktop: restore normal layout except for bank_history
        if (currentTab !== 'bank_history') {
            sidebar.classList.remove('hidden');
            mainContent.classList.remove('fullscreen');
            sidebarToggle.classList.remove('show');
        }
    } else {
        // Mobile: always show toggle button
        sidebarToggle.classList.add('show');
    }
    
    // Adjust closing transaction highlighting for mobile
    const closingTransactions = document.querySelectorAll('.closing-transaction');
    closingTransactions.forEach(row => {
        if (window.innerWidth <= 768) {
            row.style.borderLeft = '2px solid var(--closing-color)';
        } else {
            row.style.borderLeft = '4px solid var(--closing-color)';
        }
    });
    
    // Re-initialize table scrolling after resize
    setTimeout(() => {
        reinitializeTableScrolling();
    }, 300);
});

// Initialize page with closing transaction support
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on mobile
    if (window.innerWidth <= 768) {
        const sidebarToggle = document.getElementById('sidebarToggle');
        sidebarToggle.classList.add('show');
    }
    
    // Auto-focus on first input in modals
    const modal = document.querySelector('.modal.show');
    if (modal) {
        const firstInput = modal.querySelector('input[type="text"], input[type="number"], textarea');
        if (firstInput) {
            setTimeout(() => {
                firstInput.focus();
                // Special handling for closing transaction modals
                const isClosingModal = modal.querySelector('.closing-validation-info') !== null;
                if (isClosingModal) {
                    showNotification('Modal transaksi closing dibuka. Perhatikan informasi gabungan transaksi.', 'info', 3000);
                }
            }, 300);
        }
    }
    
    // Initialize summary button visibility
    updateSummaryButtonVisibility();
    
    // Initialize closing transaction calculations
    const isClosing = checkIfClosingTransaction();
    if (document.getElementById('jumlahDiterima')) {
        hitungSelisihTransaksi(isClosing);
    }
    if (document.getElementById('jumlahDiterimaBaru')) {
        hitungSelisihEdit(isClosing);
    }
});

// Export functionality helpers with closing transaction info
function exportToExcel(type, additionalParams = '') {
    const currentTab = '<?php echo $tab; ?>';
    let url = `export_excel_setoran.php?type=${type}&tab=${currentTab}`;
    
    // Add additional parameters if provided
    if (additionalParams) {
        url += '&' + additionalParams;
    }
    
    // Add current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const relevantParams = ['tanggal_awal', 'tanggal_akhir', 'cabang', 'rekening_filter'];
    
    relevantParams.forEach(param => {
        if (urlParams.has(param)) {
            url += `&${param}=${urlParams.get(param)}`;
        }
    });
    
    // Add closing transaction flag
    const hasClosingTransactions = document.querySelectorAll('.closing-transaction').length > 0;
    if (hasClosingTransactions) {
        url += '&has_closing=true';
    }
    
    window.open(url, '_blank');
}

function exportToCSV(type, additionalParams = '') {
    const currentTab = '<?php echo $tab; ?>';
    let url = `export_csv.php?type=${type}&tab=${currentTab}`;
    
    // Add additional parameters if provided
    if (additionalParams) {
        url += '&' + additionalParams;
    }
    
    // Add current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const relevantParams = ['tanggal_awal', 'tanggal_akhir', 'cabang', 'rekening_filter'];
    
    relevantParams.forEach(param => {
        if (urlParams.has(param)) {
            url += `&${param}=${urlParams.get(param)}`;
        }
    });
    
    // Add closing transaction flag
    const hasClosingTransactions = document.querySelectorAll('.closing-transaction').length > 0;
    if (hasClosingTransactions) {
        url += '&has_closing=true';
    }
    
    window.open(url, '_blank');
}

// Performance optimization: Debounce search with closing transaction awareness
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Apply debounce to search functions with closing highlighting
const debouncedSearch = debounce(function(searchTerm, rows) {
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
        
        // Maintain closing transaction highlighting for visible rows
        if (isVisible && row.classList.contains('closing-transaction')) {
            row.style.backgroundColor = 'rgba(156,39,176,0.05)';
            row.style.borderLeft = '4px solid var(--closing-color)';
        }
    });
}, 300);

// Export functions for external use
window.tableScrolling = {
    initialize: initializeTableScrolling,
    reinitialize: reinitializeTableScrolling,
    scrollTo: scrollTableTo,
    addButtons: addScrollButtons
};

// Service worker registration for offline capability (optional)
// Temporarily disabled to fix 404 error
/*
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed');
            });
    });
}
*/

// Final initialization
console.log('Setoran Keuangan system initialized with enhanced table scrolling and closing transaction support');

// Fungsi untuk menampilkan modal kembalikan ke CS
function showKembalikanKeCSModal(kodeTransaksi, namaKaryawan, namaCabang) {
    const modal = document.getElementById('kembalikanKeCSModal');
    const form = document.getElementById('formKembalikanKeCS');
    
    // Set data ke form
    document.getElementById('kembalikanTransaksiId').value = kodeTransaksi;
    document.getElementById('kembalikanInfoText').innerHTML = `
        <strong>Kode Transaksi:</strong> ${kodeTransaksi}<br>
        <strong>CS Pengirim:</strong> ${namaKaryawan}<br>
        <strong>Cabang:</strong> ${namaCabang}
    `;
    
    // Reset form
    document.getElementById('alasanKembalikan').value = '';
    
    // Show modal
    modal.style.display = 'flex';
    modal.classList.add('show');
}

function closeKembalikanKeCSModal() {
    const modal = document.getElementById('kembalikanKeCSModal');
    modal.style.display = 'none';
    modal.classList.remove('show');
}

// Event listener untuk modal
window.onclick = function(event) {
    const modal = document.getElementById('kembalikanKeCSModal');
    if (event.target === modal) {
        closeKembalikanKeCSModal();
    }
}
</script>

<!-- Modal Kembalikan ke CS -->
<div id="kembalikanKeCSModal" class="modal" style="display: none; z-index: 15000;">
    <div class="modal-dialog modal-lg" style="margin: 20px auto; max-width: 600px;">
        <div class="modal-content" style="background: white; border-radius: 16px; position: relative;">
            <div class="modal-header" style="position: relative; z-index: 15001;">
                <h5 class="modal-title"><i class="fas fa-undo"></i> Kembalikan Setoran ke CS Pengirim</h5>
                <button type="button" class="btn-close" onclick="closeKembalikanKeCSModal()" style="position: relative; z-index: 15002;">&times;</button>
            </div>
        
        <form id="formKembalikanKeCS" method="POST" action="">
            <div class="modal-body">
                <div class="alert alert-warning" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Transaksi akan dikembalikan ke CS pengirim untuk diperbaiki. 
                    Pastikan sudah berkomunikasi dengan CS yang bersangkutan.
                </div>
                
                <input type="hidden" id="kembalikanTransaksiId" name="transaksi_id" value="">
                
                <div class="detail-section">
                    <h6>Detail Transaksi:</h6>
                    <div id="kembalikanInfoText" class="info-text">
                        <!-- Info akan diisi oleh JavaScript -->
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label for="alasanKembalikan" class="form-label">
                        <i class="fas fa-comment"></i> Alasan Kembalikan ke CS: <span style="color: red;">*</span>
                    </label>
                    <textarea id="alasanKembalikan" name="alasan_kembalikan" class="form-control" rows="4" 
                              placeholder="Jelaskan alasan mengapa transaksi ini dikembalikan ke CS (contoh: Selisih terlalu besar, uang tidak sesuai catatan, dll.)" 
                              required style="resize: vertical;"></textarea>
                    <small class="form-text text-muted">
                        Alasan ini akan dicatat dalam sistem dan dapat dilihat oleh CS pengirim
                    </small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeKembalikanKeCSModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" name="kembalikan_ke_cs" class="btn btn-warning"
                        onclick="return confirm('Yakin ingin mengembalikan transaksi ini ke CS pengirim?')">
                    <i class="fas fa-undo"></i> Kembalikan ke CS
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<style>
.btn-group-vertical {
    display: flex;
    flex-direction: column;
}

.info-text {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    border-left: 4px solid var(--primary-color);
    font-size: 14px;
    line-height: 1.5;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-text {
    font-size: 12px;
    margin-top: 5px;
}

/* Horizontal scroll styling for monitoring table */
.table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: #888 #f1f1f1;
}

.table-wrapper::-webkit-scrollbar {
    height: 12px;
}

.table-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 6px;
}

.table-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 6px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Table styling for better horizontal scroll experience */
.table-enhanced table {
    table-layout: fixed;
    white-space: nowrap;
}

.table-enhanced th, 
.table-enhanced td {
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 8px 6px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-wrapper > .table-enhanced,
    .table-responsive > .table-enhanced {
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
    }
}
</style>

<script>
// ===== MODAL SETORAN PILIHAN =====
const pengambilanBlockedCabangMap = <?php echo json_encode($blocked_cabang_map, JSON_UNESCAPED_UNICODE); ?>;

function getBankRowNettoSetor(row) {
    if (!row) return 0;
    const nominal = parseInt((row.dataset && row.dataset.nettoSetor ? row.dataset.nettoSetor : '0'), 10);
    return Number.isFinite(nominal) ? nominal : 0;
}

function getBankRowKodeSetoran(row) {
    if (!row) return '';
    if (row.dataset && row.dataset.kodeSetoran) {
        return row.dataset.kodeSetoran;
    }
    const codeEl = row.querySelector('code');
    return codeEl ? codeEl.textContent.trim() : '';
}

function getBankRowCabang(row) {
    if (!row || !row.cells || row.cells.length <= 4) {
        return '';
    }
    return row.cells[4].textContent.trim();
}

function showSetoranPilihanModal() {
    const checked = document.querySelectorAll('.bankCheckbox:checked');
    if (checked.length === 0) {
        showNotification('Pilih minimal satu transaksi closing terlebih dahulu.', 'warning');
        return;
    }

    let total = 0;
    checked.forEach(function(cb) {
        const row = cb.closest('tr');
        if (row) {
            total += getBankRowNettoSetor(row);
        }
    });

    const modal = document.getElementById('setoranPilihanModal');
    document.getElementById('totalSetoranDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('tanggalPerencanaanSetor').value = document.getElementById('tanggalSetoranMainInput').value || '<?php echo date('Y-m-d'); ?>';
    document.querySelector('input[name="aksi_setoran_mode"][value="full"]').checked = true;
    document.getElementById('nominalDiambil').value = '';
    document.getElementById('kodeCabangPenerima').value = '';
    document.getElementById('keteranganAmbil').value = '';
    document.getElementById('sisaSetoranDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
    modal.style.display = 'flex';
    modal._totalSetoran = total;
    toggleAksiSetoranMode();
    validateCabangPenerimaLock();
}

function closeSetoranPilihanModal() {
    document.getElementById('setoranPilihanModal').style.display = 'none';
}

function toggleAksiSetoranMode() {
    const mode = document.querySelector('input[name="aksi_setoran_mode"]:checked')?.value || 'full';
    document.getElementById('ambilSebagianForm').style.display = mode === 'partial' ? 'block' : 'none';
    document.getElementById('setoranPenuhDateSection').style.display = mode === 'full' ? 'block' : 'none';
    validateCabangPenerimaLock();
}

function updateSisaSetoran() {
    const modal = document.getElementById('setoranPilihanModal');
    const total = modal._totalSetoran || 0;
    const diambil = parseInt(document.getElementById('nominalDiambil').value) || 0;
    const sisa = total - diambil;
    document.getElementById('sisaSetoranDisplay').textContent = 'Rp ' + Math.max(0, sisa).toLocaleString('id-ID');
}

function validateCabangPenerimaLock() {
    const mode = document.querySelector('input[name="aksi_setoran_mode"]:checked')?.value || 'full';
    const button = document.getElementById('modalKlikValidasiButton');
    const notice = document.getElementById('cabangPenerimaLockNotice');
    const cabang = document.getElementById('kodeCabangPenerima')?.value || '';

    if (mode !== 'partial') {
        button.disabled = false;
        notice.style.display = 'none';
        notice.textContent = '';
        return true;
    }

    if (cabang && pengambilanBlockedCabangMap[cabang]) {
        button.disabled = true;
        notice.style.display = 'block';
        notice.textContent = 'Cabang ini masih punya pengambilan belum selesai: ' + pengambilanBlockedCabangMap[cabang];
        return false;
    }

    button.disabled = false;
    notice.style.display = 'none';
    notice.textContent = '';
    return true;
}

function submitAksiSetoran() {
    const mode = document.querySelector('input[name="aksi_setoran_mode"]:checked')?.value || 'full';

    if (mode === 'partial') {
        submitAmbilSebagian();
        return;
    }

    const tanggal = document.getElementById('tanggalPerencanaanSetor').value;
    if (!tanggal) {
        showNotification('Tanggal setor ke penyetor wajib diisi.', 'warning');
        return;
    }

    document.getElementById('tanggalSetoranMainInput').value = tanggal;
    closeSetoranPilihanModal();
    document.getElementById('setor_bank_hidden_submit').click();
}

function submitAmbilSebagian() {
    const modal = document.getElementById('setoranPilihanModal');
    const total = modal._totalSetoran || 0;
    const diambil = parseInt(document.getElementById('nominalDiambil').value) || 0;
    const cabangPenerima = document.getElementById('kodeCabangPenerima').value;
    const tanggal = document.getElementById('tanggalSetorKeBank').value;

    if (!validateCabangPenerimaLock()) {
        showNotification('Cabang ini masih punya pengambilan belum selesai yang harus diselesaikan (sampai tahap closing).', 'error');
        return;
    }
    if (!tanggal) { showNotification('Tanggal pengambilan dana wajib diisi.', 'warning'); return; }
    if (!cabangPenerima) { showNotification('Cabang penerima wajib dipilih.', 'warning'); return; }
    if (diambil <= 0) { showNotification('Masukkan nominal yang diambil.', 'warning'); return; }
    if (diambil > total) { showNotification('Nominal yang diambil tidak boleh melebihi total setoran.', 'warning'); return; }

    const sisa = total - diambil;
    const keterangan = document.getElementById('keteranganAmbil').value;

    const checked = document.querySelectorAll('.bankCheckbox:checked');
    const ids = Array.from(checked).map(function(cb){ return cb.value; }).join(',');

    const rekeningEl = document.querySelector('#setorBankForm [name="rekening_cabang_id"]') ||
                       document.querySelector('[name="rekening_cabang_id"]');

    document.getElementById('closingIdsAmbil').value = ids;
    document.getElementById('rekeningCabangIdAmbil').value = rekeningEl ? rekeningEl.value : '';
    document.getElementById('tanggalSetoranAmbil').value = tanggal;
    document.getElementById('nominalDiambilHidden').value = diambil;
    document.getElementById('nominalSisaHidden').value = sisa;
    document.getElementById('kodeCabangPenerimaHidden').value = cabangPenerima;
    document.getElementById('keteranganAmbilHidden').value = keterangan;
    const tanggalBank = document.getElementById('tanggalSetorKeBank');
    if (tanggalBank) document.getElementById('tanggalSetorBankHidden').value = tanggalBank.value;

    if (confirm('Konfirmasi:\n• Rp ' + diambil.toLocaleString('id-ID') + ' diambil untuk Pengadaan\n• Sisa Rp ' + sisa.toLocaleString('id-ID') + ' akan disetor ke bank\n\nLanjutkan?')) {
        document.getElementById('ambilSebagianHiddenForm').submit();
    }
}

// Tutup modal saat klik backdrop
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('setoranPilihanModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeSetoranPilihanModal();
        });
    }
});
</script>

<!-- Modal: Input Manual Pelunasan Hutang (P16) -->
<div id="modalManualPelunasan" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1500;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;border-radius:18px;padding:28px 32px;width:100%;max-width:480px;margin:20px auto;">
        <h3 style="margin:0 0 6px;font-size:18px;"><i class="fas fa-keyboard"></i> Input Manual Pelunasan Hutang</h3>
        <p id="manualPelunasanInfo" style="font-size:13px;color:#64748b;margin:0 0 12px;"></p>
        <div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:9px 12px;margin-bottom:12px;font-size:12px;color:#1d4ed8;">
            <i class="fas fa-tag"></i> Kode pelunasan unik (<strong>PLN-XXXX-XXXX</strong>) akan dibuat otomatis oleh sistem.
        </div>
        <input type="hidden" id="mpKode">
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <label style="font-size:13px;font-weight:600;">No. Referensi Transfer <span style="color:#64748b;font-weight:400;">(Opsional)</span></label>
                <input type="text" id="mpNoTrx" placeholder="Contoh: nomor referensi dari bukti transfer bank"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:13px;box-sizing:border-box;">
                <small style="color:#94a3b8;font-size:11px;margin-top:3px;display:block;">Isi jika ada nomor referensi dari bank. Kosongkan jika tidak ada.</small>
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;">Nominal Dibayar (Rp) <span style="color:#dc2626;">*</span></label>
                <input type="number" id="mpNominal" step="1" min="1"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:15px;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;">Tanggal Transfer <span style="color:#dc2626;">*</span></label>
                <input type="date" id="mpTanggal"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:13px;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;">Catatan</label>
                <input type="text" id="mpCatatan" placeholder="Opsional"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:13px;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;">Upload Bukti TF <span style="color:#64748b;font-weight:400;">(Opsional — JPG/PNG/PDF, maks 8MB)</span></label>
                <div style="margin-top:4px; border:2px dashed #cbd5e1; border-radius:10px; padding:12px; background:#f8fafc; cursor:pointer;" onclick="document.getElementById('mpBukti').click()">
                    <input type="file" id="mpBukti" accept=".jpg,.jpeg,.png,.pdf"
                           style="display:none;" onchange="previewBuktiTF(this)">
                    <div id="mpBuktiPlaceholder" style="text-align:center; color:#94a3b8; font-size:13px;">
                        <i class="fas fa-cloud-upload-alt" style="font-size:22px; margin-bottom:6px; display:block;"></i>
                        Klik untuk memilih file bukti transfer
                    </div>
                    <div id="mpBuktiPreview" style="display:none; text-align:center;">
                        <img id="mpBuktiImg" src="" alt="Preview" style="max-width:100%; max-height:160px; border-radius:8px; object-fit:contain; margin-bottom:6px;">
                        <div id="mpBuktiFileName" style="font-size:12px; color:#2563eb; font-weight:600;"></div>
                        <button type="button" onclick="clearBuktiTF(event)" style="margin-top:6px;background:none;border:none;color:#dc2626;font-size:12px;cursor:pointer;"><i class="fas fa-times"></i> Hapus</button>
                    </div>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button onclick="submitManualPelunasan()"
                    style="flex:1;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                <i class="fas fa-save"></i> Simpan Pelunasan
            </button>
            <button onclick="closeManualPelunasanModal()"
                    style="flex:1;padding:11px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:600;cursor:pointer;">
                Batal
            </button>
        </div>
        <div id="mpMsg" style="margin-top:12px;font-size:13px;display:none;padding:10px;border-radius:8px;"></div>
    </div>
</div>

<!-- Modal: Setorkan ke Bank (P3) -->
<div id="modalSetorBank" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1500;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;padding:28px 32px;width:100%;max-width:420px;margin:auto;">
        <h3 style="margin:0 0 6px;font-size:18px;"><i class="fas fa-university"></i> Setorkan ke Bank</h3>
        <p id="setorBankInfo" style="font-size:13px;color:#64748b;margin:0 0 16px;"></p>
        <input type="hidden" id="sbKode">
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <label style="font-size:13px;font-weight:600;">Tanggal Penyerahan Setor <span style="color:#dc2626;">*</span></label>
                <input type="date" id="sbTglPenyerahan"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:13px;">
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;">Tanggal Riil Setor Bank <span style="color:#dc2626;">*</span></label>
                <input type="date" id="sbTglRiil"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:13px;">
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button onclick="submitSetorBank()"
                    style="flex:1;padding:11px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                <i class="fas fa-check-circle"></i> Konfirmasi Setor
            </button>
            <button onclick="closeSetorBankModal()"
                    style="flex:1;padding:11px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:600;cursor:pointer;">
                Batal
            </button>
        </div>
        <div id="sbMsg" style="margin-top:12px;font-size:13px;display:none;padding:10px;border-radius:8px;"></div>
    </div>
</div>

<!-- Modal: Edit Nominal Pengambilan (sisi keuangan) (P7) -->
<div id="modalEditNominalKeuangan" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1500;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;padding:28px 32px;width:100%;max-width:420px;margin:auto;">
        <h3 style="margin:0 0 6px;font-size:18px;"><i class="fas fa-edit"></i> Edit Nominal Pengambilan</h3>
        <p style="font-size:13px;color:#64748b;margin:0 0 16px;">Mengubah nominal akan menghapus input pemasukan kasir yang sudah ada. Kasir cabang penerima akan mendapat notifikasi untuk input ulang.</p>
        <input type="hidden" id="enkKode">
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <label style="font-size:13px;font-weight:600;">Nominal Baru (Rp) <span style="color:#dc2626;">*</span></label>
                <input type="number" id="enkNominal" step="1" min="0"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:15px;">
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;">Alasan Perubahan <span style="color:#dc2626;">*</span></label>
                <input type="text" id="enkAlasan" placeholder="Contoh: Cabang B juga butuh dana"
                       style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;margin-top:4px;font-size:13px;">
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button onclick="submitEditNominalKeuangan()"
                    style="flex:1;padding:11px;background:#f59e0b;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                <i class="fas fa-save"></i> Simpan
            </button>
            <button onclick="closeEditNominalKeuanganModal()"
                    style="flex:1;padding:11px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:600;cursor:pointer;">
                Batal
            </button>
        </div>
        <div id="enkMsg" style="margin-top:12px;font-size:13px;display:none;padding:10px;border-radius:8px;"></div>
    </div>
</div>

<script>
// ===== Manual Pelunasan Modal =====
function openManualPelunasan(kode, sisaHutang) {
    document.getElementById('mpKode').value = kode;
    document.getElementById('mpNominal').value = Math.round(sisaHutang);
    document.getElementById('mpNoTrx').value = '';
    document.getElementById('mpTanggal').value = new Date().toISOString().split('T')[0];
    document.getElementById('mpCatatan').value = '';
    document.getElementById('mpMsg').style.display = 'none';
    // Reset file input & preview
    document.getElementById('mpBukti').value = '';
    document.getElementById('mpBuktiPreview').style.display = 'none';
    document.getElementById('mpBuktiPlaceholder').style.display = 'block';
    document.getElementById('mpBuktiImg').src = '';
    document.getElementById('mpBuktiFileName').textContent = '';

    if (sisaHutang <= 0) {
        document.getElementById('manualPelunasanInfo').innerHTML = '<strong style="color:#166534;">Hutang sudah lunas secara nominal (Sisa Rp 0).</strong><br>Form ini akan memverifikasi mutasi pelunasan secara manual.';
        document.getElementById('mpNominal').parentElement.style.display = 'none';
    } else {
        document.getElementById('manualPelunasanInfo').textContent = 'PENTING: Pastikan Anda telah mengecek mutasi masuk di rekening penerima sebelum menginput pelunasan manual ini.';
        document.getElementById('mpNominal').parentElement.style.display = 'block';
    }
    document.getElementById('modalManualPelunasan').style.display = 'flex';
}
function closeManualPelunasanModal() { document.getElementById('modalManualPelunasan').style.display = 'none'; }
function previewBuktiTF(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('mpBuktiFileName').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('mpBuktiImg').src = e.target.result;
            document.getElementById('mpBuktiImg').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('mpBuktiImg').style.display = 'none';
    }
    document.getElementById('mpBuktiPlaceholder').style.display = 'none';
    document.getElementById('mpBuktiPreview').style.display = 'block';
}
function clearBuktiTF(e) {
    e.stopPropagation();
    document.getElementById('mpBukti').value = '';
    document.getElementById('mpBuktiPreview').style.display = 'none';
    document.getElementById('mpBuktiPlaceholder').style.display = 'block';
    document.getElementById('mpBuktiImg').src = '';
}
function submitManualPelunasan() {
    const kode    = document.getElementById('mpKode').value;
    const noTrx   = document.getElementById('mpNoTrx').value.trim();
    const nominal = document.getElementById('mpNominal').value;
    const tgl     = document.getElementById('mpTanggal').value;
    const catatan = document.getElementById('mpCatatan').value.trim();
    const bukti   = document.getElementById('mpBukti').files[0];
    const msgEl   = document.getElementById('mpMsg');
    
    if (!nominal || !tgl) {
        msgEl.style.cssText = 'display:block;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;';
        msgEl.textContent = 'Nominal dan tanggal transfer wajib diisi.'; return;
    }
    
    const formData = new FormData();
    formData.append('action', 'pelunasan_manual');
    formData.append('kode_pengambilan', kode);
    formData.append('nominal', nominal);
    formData.append('no_transaksi', noTrx);
    formData.append('tanggal_transfer', tgl);
    formData.append('catatan', catatan);
    if (bukti) {
        formData.append('bukti_tf', bukti);
    }

    fetch('pengambilan_setoran.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(data => {
            msgEl.style.display = 'block';
            if (data.success) {
                msgEl.style.cssText = 'display:block;color:#16a34a;background:#f0fdf4;border:1px solid #86efac;';
                msgEl.textContent = 'Pelunasan berhasil disimpan. Halaman dimuat ulang...';
                setTimeout(() => location.reload(), 1200);
            } else {
                msgEl.style.cssText = 'display:block;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;';
                msgEl.textContent = data.message || 'Gagal menyimpan pelunasan.';
            }
        }).catch(() => { msgEl.style.display = 'block'; msgEl.textContent = 'Error jaringan.'; });
}

// ===== Setor ke Bank Modal (P3) =====
function openSetorBankModal(kode, nominalSisa) {
    document.getElementById('sbKode').value = kode;
    document.getElementById('sbTglPenyerahan').value = new Date().toISOString().split('T')[0];
    document.getElementById('sbTglRiil').value = new Date().toISOString().split('T')[0];
    document.getElementById('sbMsg').style.display = 'none';
    document.getElementById('setorBankInfo').textContent =
        'Kode: ' + kode + ' — Sisa ke bank: Rp ' + Math.round(nominalSisa).toLocaleString('id-ID');
    document.getElementById('modalSetorBank').style.display = 'flex';
}
function closeSetorBankModal() { document.getElementById('modalSetorBank').style.display = 'none'; }
function submitSetorBank() {
    const kode        = document.getElementById('sbKode').value;
    const tglPenyerahan = document.getElementById('sbTglPenyerahan').value;
    const tglRiil     = document.getElementById('sbTglRiil').value;
    const msgEl       = document.getElementById('sbMsg');
    if (!tglPenyerahan || !tglRiil) {
        msgEl.style.cssText = 'display:block;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;';
        msgEl.textContent = 'Kedua tanggal wajib diisi.'; return;
    }
    const body = new URLSearchParams({ kode_pengambilan: kode, tanggal_penyerahan: tglPenyerahan, tanggal_riil: tglRiil });
    fetch('setoran_bank.php', { method: 'POST', body })
        .then(r => r.json()).then(data => {
            msgEl.style.display = 'block';
            if (data.success) {
                msgEl.style.cssText = 'display:block;color:#16a34a;background:#f0fdf4;border:1px solid #86efac;';
                msgEl.textContent = 'Berhasil disetor ke bank. Halaman dimuat ulang...';
                setTimeout(() => location.reload(), 1200);
            } else {
                msgEl.style.cssText = 'display:block;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;';
                msgEl.textContent = data.message || 'Gagal menyetor.';
            }
        }).catch(() => { msgEl.style.display = 'block'; msgEl.textContent = 'Error jaringan.'; });
}

// ===== OCR Upload Area helpers =====
function previewOcr(uid, input) {
    const file = input.files[0];
    if (!file) return;
    const ph  = document.getElementById('ph_' + uid);
    const pv  = document.getElementById('pv_' + uid);
    const img = document.getElementById('img_' + uid);
    const fn  = document.getElementById('fn_' + uid);
    fn.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
        reader.readAsDataURL(file);
    } else {
        img.style.display = 'none';
    }
    ph.style.display = 'none';
    pv.style.display = 'block';
}
function clearOcr(e, uid) {
    e.stopPropagation();
    const fi  = document.getElementById('fi_' + uid);
    const ph  = document.getElementById('ph_' + uid);
    const pv  = document.getElementById('pv_' + uid);
    const img = document.getElementById('img_' + uid);
    fi.value = '';
    img.src  = '';
    img.style.display = 'none';
    pv.style.display = 'none';
    ph.style.display = 'block';
}
function handleOcrDrop(e, uid) {
    e.preventDefault();
    const dz = document.getElementById('dz_' + uid);
    dz.style.background = '#fffbf5';
    const fi = document.getElementById('fi_' + uid);
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    fi.files = dt.files;
    previewOcr(uid, fi);
}

// ===== Edit Nominal (sisi keuangan) Modal (P7) =====
function openEditNominalKeuangan(kode, nominalSekarang) {
    document.getElementById('enkKode').value = kode;
    document.getElementById('enkNominal').value = Math.round(nominalSekarang);
    document.getElementById('enkAlasan').value = '';
    document.getElementById('enkMsg').style.display = 'none';
    document.getElementById('modalEditNominalKeuangan').style.display = 'flex';
}
function closeEditNominalKeuanganModal() { document.getElementById('modalEditNominalKeuangan').style.display = 'none'; }
function submitEditNominalKeuangan() {
    const kode    = document.getElementById('enkKode').value;
    const nominal = document.getElementById('enkNominal').value;
    const alasan  = document.getElementById('enkAlasan').value.trim();
    const msgEl   = document.getElementById('enkMsg');
    const nominalValue = parseFloat(nominal);
    if (nominal === '' || Number.isNaN(nominalValue) || nominalValue < 0) {
        msgEl.style.cssText = 'display:block;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;';
        msgEl.textContent = 'Nominal wajib diisi dan tidak boleh negatif.'; return;
    }
    if (!alasan) {
        msgEl.style.cssText = 'display:block;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;';
        msgEl.textContent = 'Alasan perubahan wajib diisi.'; return;
    }
    const body = new URLSearchParams({ action: 'edit_nominal', kode_pengambilan: kode, nominal_baru: nominal, alasan, role: 'keuangan' });
    fetch('pengambilan_setoran.php', { method: 'POST', body })
        .then(r => r.json()).then(data => {
            msgEl.style.display = 'block';
            if (data.success) {
                msgEl.style.cssText = 'display:block;color:#16a34a;background:#f0fdf4;border:1px solid #86efac;';
                msgEl.textContent = 'Berhasil diperbarui. Halaman dimuat ulang...';
                setTimeout(() => location.reload(), 1200);
            } else {
                msgEl.style.cssText = 'display:block;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;';
                msgEl.textContent = data.message || 'Gagal menyimpan.';
            }
        }).catch(() => { msgEl.style.display = 'block'; msgEl.textContent = 'Error jaringan.'; });
}
</script>
</body>
</html>
