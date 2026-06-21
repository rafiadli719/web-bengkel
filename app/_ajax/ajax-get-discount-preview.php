<?php
/**
 * AJAX Handler - Get Discount Preview Data
 *
 * Menampilkan preview diskon sebelum input servis:
 * 1. Diskon Periode (jika ada dan aktif)
 * 2. Diskon Member (jika tidak ada diskon periode)
 * 3. Riwayat Servis Sebelumnya
 *
 * @author Claude AI
 * @date 2026-01-05
 */

session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

include "../../config/koneksi.php";
include "../_include_customer_vehicle_sync.php";

function tableExists($koneksi, $tableName) {
    $safeTable = mysqli_real_escape_string($koneksi, $tableName);
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '{$safeTable}'");
    return $result && mysqli_num_rows($result) > 0;
}

function firstExistingTable($koneksi, array $candidates) {
    foreach ($candidates as $candidate) {
        if (tableExists($koneksi, $candidate)) {
            return $candidate;
        }
    }
    return null;
}

function columnExists($koneksi, $tableName, $columnName) {
    $safeTable = mysqli_real_escape_string($koneksi, $tableName);
    $safeColumn = mysqli_real_escape_string($koneksi, $columnName);
    $result = mysqli_query($koneksi, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $result && mysqli_num_rows($result) > 0;
}

$nopol = isset($_POST['nopol']) ? mysqli_real_escape_string($koneksi, $_POST['nopol']) : '';
$service_type = isset($_POST['service_type']) ? mysqli_real_escape_string($koneksi, $_POST['service_type']) : 'reguler';

if(empty($nopol)) {
    echo json_encode(['success' => false, 'message' => 'Nomor polisi tidak valid']);
    exit;
}

$response = [
    'success' => true,
    'customer' => null,
    'discount_type' => null, // 'periode', 'member', 'none'
    'discount_periode' => null,
    'discount_member' => null,
    'service_history' => [],
    'keluhan_history' => [],
    'has_active_promo' => false
];

// 1. Get Customer Data
$bundle = fitmotorGetCustomerVehicleBundle($koneksi, $nopol);
$vehicleRow = $bundle['vehicle'] ?? null;
$customerRow = $bundle['customer'] ?? null;
$mappedCustomerCode = $customerRow['nopelanggan'] ?? ($bundle['mapped_customer_code'] ?? '');

if(!$vehicleRow && !$customerRow) {
    echo json_encode(['success' => false, 'message' => 'Data pelanggan tidak ditemukan']);
    exit;
}

$statistikTotalDiskonExpr = columnExists($koneksi, 'statistik_pelanggan', 'total_diskon_diberikan')
    ? "COALESCE(total_diskon_diberikan, 0) AS total_diskon_diberikan"
    : "0 AS total_diskon_diberikan";
$statistikKunjunganExpr = columnExists($koneksi, 'statistik_pelanggan', 'kategori_member_kunjungan')
    ? "COALESCE(kategori_member_kunjungan, 'Bronze') AS status_member_kunjungan"
    : "'Bronze' AS status_member_kunjungan";

$customerStats = [
    'status_member' => $customerRow['status_member'] ?? 'Bronze',
    'status_member_kunjungan' => 'Bronze',
    'total_transaksi' => 0,
    'total_nominal' => 0,
    'jumlah_kunjungan' => 0,
    'tanggal_terakhir_transaksi' => null,
    'tanggal_pertama_transaksi' => null,
    'total_diskon_diberikan' => 0,
    'rata_jarak_kunjungan' => 0,
    'lama_tidak_datang' => 0,
    'estimasi_datang_berikutnya' => null,
];

if($mappedCustomerCode !== '') {
    $stmtStats = mysqli_prepare(
        $koneksi,
        "SELECT
            COALESCE(status_member, 'Bronze') AS status_member,
            {$statistikKunjunganExpr},
            COALESCE(total_transaksi, 0) AS total_transaksi,
            COALESCE(total_nominal, 0) AS total_nominal,
            COALESCE(jumlah_kunjungan, 0) AS jumlah_kunjungan,
            tanggal_terakhir_transaksi,
            tanggal_pertama_transaksi,
            {$statistikTotalDiskonExpr},
            COALESCE(rata_jarak_kunjungan, 0) AS rata_jarak_kunjungan,
            COALESCE(lama_tidak_datang, 0) AS lama_tidak_datang,
            estimasi_datang_berikutnya
        FROM statistik_pelanggan
        WHERE no_pelanggan = ?
        LIMIT 1"
    );
    if($stmtStats) {
        mysqli_stmt_bind_param($stmtStats, 's', $mappedCustomerCode);
        mysqli_stmt_execute($stmtStats);
        $resultStats = mysqli_stmt_get_result($stmtStats);
        if($rowStats = mysqli_fetch_assoc($resultStats)) {
            $customerStats = array_merge($customerStats, $rowStats);
        }
        mysqli_stmt_close($stmtStats);
    }
}

$response['customer'] = [
    'nopolisi' => $nopol,
    'tipe' => $vehicleRow['tipe'] ?? '',
    'jenis' => $vehicleRow['jenis'] ?? '',
    'warna' => $vehicleRow['warna'] ?? '',
    'nama_pelanggan' => $customerRow['namapelanggan'] ?? ($vehicleRow['pemilik'] ?? ''),
    'nopelanggan' => $mappedCustomerCode,
    'telephone' => $customerRow['telephone'] ?? ($vehicleRow['telephone'] ?? ''),
    'alamat' => $customerRow['alamat'] ?? '',
    'merek' => $vehicleRow['merek'] ?? ($vehicleRow['tipe'] ?? ''),
    'status_member' => $customerStats['status_member'] ?? 'Bronze',
    'status_member_kunjungan' => $customerStats['status_member_kunjungan'] ?? 'Bronze',
    'total_transaksi' => $customerStats['total_transaksi'] ?? 0,
    'total_nominal' => $customerStats['total_nominal'] ?? 0,
    'jumlah_kunjungan' => $customerStats['jumlah_kunjungan'] ?? 0,
    'tanggal_terakhir_transaksi' => $customerStats['tanggal_terakhir_transaksi'] ?? null,
    'tanggal_pertama_transaksi' => $customerStats['tanggal_pertama_transaksi'] ?? null,
    'total_diskon_diberikan' => $customerStats['total_diskon_diberikan'] ?? 0,
    'rata_jarak_kunjungan' => (float)($customerStats['rata_jarak_kunjungan'] ?? 0),
    'lama_tidak_datang' => (int)($customerStats['lama_tidak_datang'] ?? 0),
    'estimasi_datang_berikutnya' => $customerStats['estimasi_datang_berikutnya'] ?? null,
];

// Get which member system is active
$sql_setting = "SELECT tipe_kategori, is_enabled FROM setting_kategori_member WHERE is_enabled = 1";
$result_setting = mysqli_query($koneksi, $sql_setting);
$active_member_type = 'nominal'; // default
if ($result_setting) {
    while($row_setting = mysqli_fetch_assoc($result_setting)) {
        if($row_setting['is_enabled'] == 1) {
            $active_member_type = $row_setting['tipe_kategori'];
            break;
        }
    }
}

// Determine which member status to use
$member_status = ($active_member_type == 'kunjungan')
    ? $response['customer']['status_member_kunjungan']
    : $response['customer']['status_member'];

// 2. Check Active Promo/Diskon Periode
$today = date('Y-m-d');
$promoDescriptionExpr = columnExists($koneksi, 'master_diskon_periode', 'deskripsi')
    ? 'deskripsi'
    : (columnExists($koneksi, 'master_diskon_periode', 'keterangan') ? 'keterangan' : "''");
$promoTargetNameExpr = columnExists($koneksi, 'master_diskon_periode', 'target_nama')
    ? 'target_nama'
    : 'target_id';
$sql_promo = "SELECT
    id_promo,
    nama_promo,
    {$promoDescriptionExpr} as deskripsi,
    tipe_promo,
    nilai_promo,
    tanggal_mulai,
    tanggal_selesai,
    target_type,
    target_id,
    {$promoTargetNameExpr} as target_nama
FROM master_diskon_periode
WHERE status_aktif = 1
    AND tanggal_mulai <= '$today'
    AND tanggal_selesai >= '$today'
ORDER BY created_at DESC";

$result_promo = mysqli_query($koneksi, $sql_promo);
$active_promos = [];

if($result_promo && mysqli_num_rows($result_promo) > 0) {
    while($row_promo = mysqli_fetch_assoc($result_promo)) {
        $active_promos[] = $row_promo;
    }
    $response['has_active_promo'] = true;
    $response['discount_type'] = 'periode';
    $response['discount_periode'] = $active_promos;
}

// 3. Get Member Discount Info (if no active promo OR show anyway for info)
$sql_member = "SELECT
    mkm.id_kategori,
    mkm.nama_kategori,
    mkm.tipe_kategori,
    mkm.min_value,
    mkm.max_value,
    mkm.diskon_persen,
    mkm.diskon_jasa,
    mkm.diskon_barang,
    mkm.benefit_text,
    mkm.icon,
    mkm.warna,
    shm.background_color,
    shm.text_color,
    shm.border_color
FROM master_kategori_member mkm
LEFT JOIN setting_highlight_member shm ON mkm.nama_kategori = shm.kategori_member
WHERE mkm.nama_kategori = '$member_status'
    AND mkm.tipe_kategori = '$active_member_type'
    AND mkm.is_active = 1
LIMIT 1";

$result_member = mysqli_query($koneksi, $sql_member);
if($row_member = mysqli_fetch_assoc($result_member)) {
    $response['discount_member'] = $row_member;

    // If no active promo, use member discount
    if(!$response['has_active_promo']) {
        $response['discount_type'] = 'member';
    }
}

// 4. Get Item Exclusions (items that don't get member discount)
$sql_exclude_mode = "SELECT mode_aktif FROM setting_exclude_mode WHERE id = 1";
$result_mode = tableExists($koneksi, 'setting_exclude_mode') ? mysqli_query($koneksi, $sql_exclude_mode) : false;
$exclude_mode = 'per_jenis';
if($result_mode && ($row_mode = mysqli_fetch_assoc($result_mode))) {
    $exclude_mode = $row_mode['mode_aktif'];
}
$response['exclude_mode'] = $exclude_mode;

// Get excluded categories
$hargaJualKategoriExpr = columnExists($koneksi, 'tbhargajual', 'kategori') ? 'kategori' : "''";
$sql_excluded_jenis = "SELECT jenis, {$hargaJualKategoriExpr} as kategori FROM tbhargajual WHERE exclude_diskon_member = 1";
$result_excluded = mysqli_query($koneksi, $sql_excluded_jenis);
$excluded_jenis = [];
if($result_excluded) {
    while($row_ex = mysqli_fetch_assoc($result_excluded)) {
        $excluded_jenis[] = $row_ex;
    }
}
$response['excluded_categories'] = $excluded_jenis;

// 5. Get Service History (last 5 services)
$serviceBarangTable = firstExistingTable($koneksi, ['tblservis_barang', 'tblservisbarang']);
$serviceJasaTable = firstExistingTable($koneksi, ['tblservis_jasa', 'tblservisjasa']);
$serviceBarangCount = $serviceBarangTable
    ? "(SELECT COUNT(*) FROM {$serviceBarangTable} sb WHERE sb.no_service = s.no_service) as total_barang"
    : "0 as total_barang";
$serviceJasaCount = $serviceJasaTable
    ? "(SELECT COUNT(*) FROM {$serviceJasaTable} sj WHERE sj.no_service = s.no_service) as total_jasa"
    : "0 as total_jasa";
$serviceKeluhanExpr = columnExists($koneksi, 'tblservice', 'keluhan')
    ? 's.keluhan'
    : (columnExists($koneksi, 'tblservice', 'keterangan') ? 's.keterangan' : "''");
$serviceDiskonExpr = columnExists($koneksi, 'tblservice', 'diskon')
    ? 's.diskon'
    : (columnExists($koneksi, 'tblservice', 'total_diskon') ? 's.total_diskon' : "0");
$serviceStatusPembayaranExpr = columnExists($koneksi, 'tblservice', 'status_pembayaran')
    ? 's.status_pembayaran'
    : (columnExists($koneksi, 'tblservice', 'metode_pembayaran') ? 's.metode_pembayaran' : "''");

$sql_history = "SELECT
    s.no_service,
    s.tanggal,
    s.jam,
    {$serviceKeluhanExpr} as keluhan,
    s.status_servis,
    s.subtotal,
    s.total_akhir,
    {$serviceDiskonExpr} as diskon,
    {$serviceStatusPembayaranExpr} as status_pembayaran,
    {$serviceBarangCount},
    {$serviceJasaCount},
    s.mekanik1,
    s.mekanik2,
    s.mekanik3,
    s.mekanik4
FROM tblservice s
WHERE s.no_polisi = '$nopol'
    AND s.status_servis != 'cancel'
ORDER BY s.tanggal DESC, s.jam DESC
LIMIT 5";

$result_history = mysqli_query($koneksi, $sql_history);
if($result_history) {
    while($row_hist = mysqli_fetch_assoc($result_history)) {
        $response['service_history'][] = $row_hist;
    }
}

// Resolve kode mekanik (mekanik1-4) menjadi nama untuk ditampilkan
if(!empty($response['service_history'])) {
    $mekanikCodes = [];
    foreach ($response['service_history'] as $hist) {
        foreach (['mekanik1', 'mekanik2', 'mekanik3', 'mekanik4'] as $key) {
            $code = trim((string) ($hist[$key] ?? ''));
            if ($code !== '') {
                $mekanikCodes[$code] = true;
            }
        }
    }

    $mekanikNames = [];
    if (!empty($mekanikCodes)) {
        $codeList = "'" . implode("','", array_map(function ($c) use ($koneksi) {
            return mysqli_real_escape_string($koneksi, $c);
        }, array_keys($mekanikCodes))) . "'";
        $result_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nomekanik IN ($codeList)");
        if ($result_mekanik) {
            while ($row_mekanik = mysqli_fetch_assoc($result_mekanik)) {
                $mekanikNames[$row_mekanik['nomekanik']] = $row_mekanik['nama'];
            }
        }
    }

    foreach ($response['service_history'] as &$hist) {
        $namaList = [];
        foreach (['mekanik1', 'mekanik2', 'mekanik3', 'mekanik4'] as $key) {
            $code = trim((string) ($hist[$key] ?? ''));
            if ($code !== '') {
                $namaList[] = $mekanikNames[$code] ?? $code;
            }
        }
        $hist['mekanik_nama'] = !empty($namaList) ? implode(', ', $namaList) : '-';
    }
    unset($hist);
}

// 6. Get Keluhan History (complaints from previous services)
$keluhanTable = firstExistingTable($koneksi, ['tblservis_keluhan', 'tbservis_keluhan_status']);
if ($keluhanTable === 'tblservis_keluhan') {
    $sql_keluhan = "SELECT
        sk.id,
        sk.no_service,
        sk.keluhan,
        sk.status_keluhan,
        sk.catatan_penyelesaian,
        sk.created_at,
        s.tanggal as tanggal_service
    FROM tblservis_keluhan sk
    JOIN tblservice s ON sk.no_service = s.no_service
    WHERE s.no_polisi = '$nopol'
    ORDER BY sk.created_at DESC
    LIMIT 10";

    $result_keluhan = mysqli_query($koneksi, $sql_keluhan);
    if($result_keluhan) {
        while($row_kel = mysqli_fetch_assoc($result_keluhan)) {
            $response['keluhan_history'][] = $row_kel;
        }
    }
} elseif ($keluhanTable === 'tbservis_keluhan_status') {
    $sql_keluhan = "SELECT
        sk.id,
        sk.no_service,
        sk.keluhan,
        sk.status_pengerjaan as status_keluhan,
        sk.keterangan_tidak_selesai as catatan_penyelesaian,
        sk.created_at,
        s.tanggal as tanggal_service
    FROM tbservis_keluhan_status sk
    JOIN tblservice s ON sk.no_service = s.no_service
    WHERE s.no_polisi = '$nopol'
    ORDER BY sk.created_at DESC
    LIMIT 10";

    $result_keluhan = mysqli_query($koneksi, $sql_keluhan);
    if($result_keluhan) {
        while($row_kel = mysqli_fetch_assoc($result_keluhan)) {
            $response['keluhan_history'][] = $row_kel;
        }
    }
}

// If table doesn't exist, try alternative from tblservice.keluhan field
if(empty($response['keluhan_history'])) {
    $sql_keluhan_alt = "SELECT
        no_service,
        tanggal as tanggal_service,
        keluhan,
        status_servis,
        '' as catatan_penyelesaian
    FROM tblservice
    WHERE no_polisi = '$nopol'
        AND keluhan != ''
        AND keluhan IS NOT NULL
    ORDER BY tanggal DESC
    LIMIT 10";

    $result_keluhan_alt = mysqli_query($koneksi, $sql_keluhan_alt);
    if($result_keluhan_alt) {
        while($row_kel = mysqli_fetch_assoc($result_keluhan_alt)) {
            $row_kel['status_keluhan'] = ($row_kel['status_servis'] == 'bayar') ? 'selesai' : 'proses';
            $response['keluhan_history'][] = $row_kel;
        }
    }
}

// 7. Check if no discount available
if($response['discount_type'] === null) {
    $response['discount_type'] = 'none';
}

// 8. Get next tier info (for motivation)
if($response['discount_member']) {
    $current_diskon = floatval($response['discount_member']['diskon_jasa']);
    $sql_next_tier = "SELECT
        nama_kategori,
        min_value,
        diskon_jasa,
        diskon_barang,
        benefit_text,
        warna
    FROM master_kategori_member
    WHERE tipe_kategori = '$active_member_type'
        AND is_active = 1
        AND diskon_jasa > $current_diskon
    ORDER BY min_value ASC
    LIMIT 1";

    $result_next = mysqli_query($koneksi, $sql_next_tier);
    if($row_next = mysqli_fetch_assoc($result_next)) {
        $response['next_tier'] = $row_next;

        // Calculate how much more needed
        $current_value = ($active_member_type == 'kunjungan')
            ? $response['customer']['jumlah_kunjungan']
            : $response['customer']['total_nominal'];

        $response['to_next_tier'] = floatval($row_next['min_value']) - floatval($current_value);
    }
}

echo json_encode($response);
?>
