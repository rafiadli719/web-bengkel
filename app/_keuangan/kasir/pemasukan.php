<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Koneksi & RBAC fitmotor (gantiin session role-check + config.php lama)
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_operate');

// Query di bawah masih pakai PDO/mysqli persis source asli (logic full preserved) —
// koneksi terpisah ke DB yang sama, $conn (mysqli) dan $pdo (PDO) sama kayak config.php lama.
$conn = mysqli_connect("localhost", "fitmotor_LOGIN", "Sayalupa12", "fitmotor_dbbengkel");
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
$pdo = new PDO("mysql:host=localhost;dbname=fitmotor_dbbengkel", "fitmotor_LOGIN", "Sayalupa12");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/process_closing_transaction.php'; // processClosingTransaction(), getAvailableClosingTransactions()
// NOTE: process_pengadaan_verification.php (updatePengadaanVerification/markPengambilanReadyForBank/
// markEditLogDibaca) SENGAJA belum diport — depend vendor OCR composer, satu paket sama Task 14
// (pengambilan_setoran). Flow "PENGAMBILAN DANA" (DRKUAN) di form ini jadi belum fungsional
// penuh sampai Task 14 selesai — sisanya (pemasukan manual, DARI CLOSING) tetap jalan normal.
if (!function_exists('updatePengadaanVerification')) {
    function updatePengadaanVerification(PDO $pdo, string $kodePengambilan, int $pemasukanId, float $nominalInput, string $kodeKaryawanInput = '', ?string $catatan = null, ?array $documentMeta = null): int
    {
        throw new RuntimeException('Fitur Pengambilan Dana belum aktif — nunggu Task 14 (porting pengambilan_setoran).');
    }
}
if (!function_exists('markPengambilanReadyForBank')) {
    function markPengambilanReadyForBank(PDO $pdo, string $kodePengambilan): void {}
}
if (!function_exists('markEditLogDibaca')) {
    function markEditLogDibaca(PDO $pdo, string $kodeCabangPenerima): void {}
}

date_default_timezone_set('Asia/Jakarta');

$kode_karyawan = $kode_karyawan_aktif;

// Get transaction code from URL or session
if (isset($_GET['kode_transaksi'])) {
    $kode_transaksi = $_GET['kode_transaksi'];
} else {
    die("Kode transaksi tidak ditemukan.");
}

// Data cabang aktif sudah resolved otoritatif dari koneksi_kasir.php
$user_kode_cabang = $kode_cabang_aktif;
$user_nama_cabang = $nama_cabang_aktif;

// Process form submission for "Pemasukan Kasir"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pemasukan'])) {
    $kode_akun = $_POST['kode_akun'] ?? '';
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $keterangan_transaksi = $_POST['keterangan_transaksi'];
    $nama_transaksi = trim($_POST['nama_transaksi'] ?? '');
    $nomor_transaksi_closing = trim($_POST['nomor_transaksi_closing'] ?? '');

    if (strtoupper($nama_transaksi) === 'DARI CLOSING') {
        $kode_akun = 'DRCLSG';
    } elseif (in_array(strtoupper($nama_transaksi), ['DARI KEUANGAN', 'PENGAMBILAN DANA'], true)) {
        $kode_akun = 'DRKUAN';
    } else {
        $sql_kode_akun = "SELECT mnt.kode_akun
                           FROM master_nama_transaksi_closing_kasir mnt
                           JOIN master_akun_closing_kasir ma ON mnt.kode_akun = ma.kode_akun
                           WHERE mnt.nama_transaksi = :nama_transaksi
                             AND mnt.status = 'active'
                             AND ma.jenis_akun = 'pemasukan'
                           LIMIT 1";
        $stmt_kode_akun = $pdo->prepare($sql_kode_akun);
        $stmt_kode_akun->bindParam(':nama_transaksi', $nama_transaksi);
        $stmt_kode_akun->execute();
        $kode_akun_data = $stmt_kode_akun->fetch(PDO::FETCH_ASSOC);
        if (!empty($kode_akun_data['kode_akun'])) {
            $kode_akun = $kode_akun_data['kode_akun'];
        }
    }

    $is_closing_reference_required = strtoupper(trim((string)$kode_akun)) === 'DRCLSG';
    $is_keuangan_reference_required = strtoupper(trim((string)$kode_akun)) === 'DRKUAN';
    $kode_pengambilan_ref = trim($_POST['kode_pengambilan_ref'] ?? '');
    $pelunasan_document_meta = null;

    if (!$is_closing_reference_required) {
        $nomor_transaksi_closing = '';
    }
    if (!$is_keuangan_reference_required) {
        $kode_pengambilan_ref = '';
    }

    // Set tanggal dan waktu otomatis
    $tanggal = date('Y-m-d');  // Tanggal saat ini
    $waktu = date('H:i:s');    // Waktu saat ini

    // Validasi angka jumlah agar hanya bilangan bulat
    if (!is_numeric($jumlah) || floor($jumlah) != $jumlah) {
        echo "<script>alert('Jumlah harus berupa bilangan bulat!');</script>";
        exit;
    }

    // Validasi khusus untuk transaksi akun closing
    if ($is_closing_reference_required) {
        // Validasi nomor transaksi closing wajib diisi
        if (empty($nomor_transaksi_closing)) {
            echo "<script>alert('Nomor Transaksi Closing wajib dipilih untuk transaksi akun closing (DRCLSG)!');</script>";
            exit;
        }

        // Ambil nilai setoran dari transaksi closing yang dipilih
        $sql_closing = "SELECT setoran_real FROM kasir_transactions_closing_kasir WHERE kode_transaksi = :kode_transaksi AND status = 'end proses'";
        $stmt_closing = $pdo->prepare($sql_closing);
        $stmt_closing->bindParam(':kode_transaksi', $nomor_transaksi_closing);
        $stmt_closing->execute();
        $closing_data = $stmt_closing->fetch(PDO::FETCH_ASSOC);

        if (!$closing_data) {
            echo "<script>alert('Transaksi closing tidak ditemukan atau belum end proses!');</script>";
            exit;
        }

        $setoran_closing = $closing_data['setoran_real'];

        // Validasi jumlah pemasukan tidak boleh lebih besar dari setoran closing
        if ($jumlah > $setoran_closing) {
            echo "<script>alert('Jumlah pemasukan (" . number_format($jumlah, 0, ',', '.') . ") tidak boleh lebih besar dari nilai setoran transaksi closing (" . number_format($setoran_closing, 0, ',', '.') . "). Silakan cek kembali jumlah pemasukan atau nomor transaksi closing yang dipilih.');</script>";
            exit;
        }
    }

    // Validasi khusus untuk transaksi PENGAMBILAN DANA (DRKUAN)
    if ($is_keuangan_reference_required) {
        if (empty($kode_pengambilan_ref)) {
            echo "<script>alert('Pilih kode pengambilan dana dari daftar!');</script>";
            exit;
        }
        // P19: hanya yang sesuai cabang penerima user yang login
        $stmt_cek_pga = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? AND kode_cabang_penerima = ? AND parent_kode_pengambilan IS NULL AND status != 'selesai' LIMIT 1");
        $stmt_cek_pga->execute([$kode_pengambilan_ref, $user_kode_cabang]);
        $pga_data = $stmt_cek_pga->fetch(PDO::FETCH_ASSOC);
        if (!$pga_data) {
            echo "<script>alert('Kode pengambilan dana tidak ditemukan untuk cabang ini atau sudah selesai!');</script>";
            exit;
        }
        // P4: auto-fill nominal dari pengambilan dana, tidak perlu input manual
        $jumlah = (int)round((float)$pga_data['nominal_diambil']);
        if ($jumlah <= 0) {
            echo "<script>alert('Nominal pengambilan dana tidak valid.');</script>";
            exit;
        }
    }

    // Validasi keterangan wajib diisi
    if (empty(trim($keterangan_transaksi))) {
        echo "<script>alert('Keterangan transaksi wajib diisi!');</script>";
        exit;
    }

    // Insert into the `pemasukan_kasir_closing_kasir` table (gunakan PDO untuk keamanan)
    $stmt_insert = $pdo->prepare("INSERT INTO pemasukan_kasir_closing_kasir (kode_transaksi, kode_karyawan, kode_akun, kode_pengambilan_ref, jumlah, keterangan_transaksi, tanggal, waktu, nomor_transaksi_closing)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert_ok = $stmt_insert->execute([
        $kode_transaksi, $kode_karyawan, $kode_akun,
        ($is_keuangan_reference_required && $kode_pengambilan_ref !== '') ? $kode_pengambilan_ref : null,
        $jumlah, $keterangan_transaksi, $tanggal, $waktu,
        $nomor_transaksi_closing ?: null
    ]);

    if ($insert_ok) {
        $pemasukan_id_new = (int)$pdo->lastInsertId();

        // Trigger verifikasi PENGAMBILAN DANA
        if ($is_keuangan_reference_required && !empty($kode_pengambilan_ref)) {
            try {
                updatePengadaanVerification(
                    $pdo,
                    $kode_pengambilan_ref,
                    $pemasukan_id_new,
                    (float)$jumlah,
                    $kode_karyawan,
                    trim($keterangan_transaksi),
                    null
                );
                // P3: setelah pemasukan kasir berhasil, otomatis set status_setor_bank='siap'
                try {
                    markPengambilanReadyForBank($pdo, $kode_pengambilan_ref);
                } catch (Throwable $readyEx) {
                    error_log('markPengambilanReadyForBank: ' . $readyEx->getMessage());
                }
                // P9: tandai edit-log sudah dibaca untuk cabang ini
                markEditLogDibaca($pdo, $user_kode_cabang);
            } catch (Throwable $verificationException) {
                $pdo->prepare("DELETE FROM pemasukan_kasir_closing_kasir WHERE id = ?")->execute([$pemasukan_id_new]);
                echo "<script>alert('" . addslashes($verificationException->getMessage()) . "');</script>";
                exit;
            }
        }

        // Jika transaksi akun closing, proses relasi closing
        if ($is_closing_reference_required && !empty($nomor_transaksi_closing)) {
            $pemasukan_id = $pemasukan_id_new;

            $stmtUpdateBukti = $pdo->prepare("UPDATE kasir_transactions_closing_kasir SET bukti_transaksi = ? WHERE kode_transaksi = ?");
            $stmtUpdateBukti->execute([$pemasukan_id, $nomor_transaksi_closing]);

            // Proses closing transaction dengan jenis otomatis
            $closing_data = [
                'nomor_transaksi_closing' => $nomor_transaksi_closing,
                'nama_cabang' => $user_nama_cabang,
                'tanggal' => $tanggal
            ];

            $result = processClosingTransaction($pdo, $closing_data);

            if (!$result['success']) {
                // Log error tapi jangan stop proses karena pemasukan sudah berhasil
                error_log("Closing transaction processing failed: " . $result['message']);
            }
        }

        // Setelah berhasil menyimpan, redirect untuk menghindari resubmission
        header("Location: pemasukan.php?kode_transaksi=$kode_transaksi&success=1");
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan pemasukan. Silakan coba lagi.');</script>";
    }
}

// Retrieve data from the `master_akun_closing_kasir` table where jenis_akun is 'pemasukan'
$sql = "SELECT * FROM master_akun_closing_kasir WHERE jenis_akun = 'pemasukan'";
$result = mysqli_query($conn, $sql);
$master_akun_closing_kasir = [];
while ($row = mysqli_fetch_assoc($result)) {
    $master_akun_closing_kasir[] = $row;
}

// Retrieve master nama transaksi untuk pemasukan
$sqlNamaTransaksi = "SELECT mnt.*, ma.arti 
                     FROM master_nama_transaksi_closing_kasir mnt 
                     JOIN master_akun_closing_kasir ma ON mnt.kode_akun = ma.kode_akun 
                     WHERE mnt.status = 'active' AND ma.jenis_akun = 'pemasukan'
                     ORDER BY mnt.nama_transaksi";
$resultNamaTransaksi = mysqli_query($conn, $sqlNamaTransaksi);
$master_nama_transaksi_closing_kasir = [];
while ($row = mysqli_fetch_assoc($resultNamaTransaksi)) {
    $master_nama_transaksi_closing_kasir[] = $row;
}

// PERBAIKAN: Retrieve transaksi closing yang belum disetor untuk cabang yang sama dengan kasir login
// Dan hanya menampilkan closing yang statusnya 'end proses'
// TAMBAHAN: Include transaksi yang dikembalikan ke CS
$sql_closing_available = "SELECT kode_transaksi, tanggal_transaksi, setoran_real, kode_karyawan, deposit_status
                         FROM kasir_transactions_closing_kasir
                         WHERE kode_cabang = :kode_cabang
                         AND status = 'end proses'
                         AND (
                             (deposit_status IS NULL OR deposit_status = '' OR deposit_status = 'Belum Disetor')
                             OR deposit_status = 'Dikembalikan ke CS'
                         )
                         AND kode_transaksi NOT IN (
                             SELECT DISTINCT nomor_transaksi_closing
                             FROM pemasukan_kasir_closing_kasir
                             WHERE nomor_transaksi_closing IS NOT NULL AND nomor_transaksi_closing != ''
                         )
                         ORDER BY
                             CASE WHEN deposit_status = 'Dikembalikan ke CS' THEN 0 ELSE 1 END,
                             tanggal_transaksi DESC";
$stmt_closing_available = $pdo->prepare($sql_closing_available);
$stmt_closing_available->bindParam(':kode_cabang', $user_kode_cabang);
$stmt_closing_available->execute();
$available_closing = $stmt_closing_available->fetchAll(PDO::FETCH_ASSOC);

// Debug: Log available closing transactions
if (empty($available_closing)) {
    error_log("No available closing transactions found for kode_cabang: " . $user_kode_cabang);
} else {
    error_log("Found " . count($available_closing) . " available closing transactions for kode_cabang: " . $user_kode_cabang);
}

// P19: hanya ambil pengambilan yang kode_cabang_penerima = cabang user login, belum verified, belum selesai
$stmt_pengadaan = $pdo->prepare("SELECT kode_pengambilan, nominal_diambil, keterangan, created_at, nama_cabang_keuangan, status, klasifikasi
                                  FROM pengambilan_setoran_closing_kasir
                                  WHERE kode_cabang_penerima = ?
                                    AND verified_nominal_kas_masuk = 0
                                    AND status != 'selesai'
                                    AND parent_kode_pengambilan IS NULL
                                  ORDER BY created_at DESC");
$stmt_pengadaan->execute([$user_kode_cabang]);
$available_pengadaan = $stmt_pengadaan->fetchAll(PDO::FETCH_ASSOC);
// P4: map sederhana — kode => {nominal, keterangan_auto}
$available_pengadaan_map = [];
foreach ($available_pengadaan as $pga) {
    $keterangan_auto = 'Pengambilan dana dari keuangan'
        . ($pga['nama_cabang_keuangan'] ? ' — ' . $pga['nama_cabang_keuangan'] : '')
        . ' — kode ' . $pga['kode_pengambilan'];
    $available_pengadaan_map[$pga['kode_pengambilan']] = [
        'kode_pengambilan'  => $pga['kode_pengambilan'],
        'nominal_diambil'   => (float)$pga['nominal_diambil'],
        'keterangan_auto'   => $keterangan_auto,
        'nama_cabang_keuangan' => $pga['nama_cabang_keuangan'] ?? null,
        'klasifikasi'       => $pga['klasifikasi'] ?? 'internal',
        'status'            => $pga['status'] ?? null,
    ];
}

// Query for displaying latest pemasukan data
$sqlKasMasuk = "
    SELECT pk.*, u.nama_lengkap AS nama_karyawan, c.nama_cabang
    FROM pemasukan_kasir_closing_kasir pk
    JOIN tbuser u ON CONVERT(pk.kode_karyawan USING utf8mb4) = CONVERT(u.kode_karyawan USING utf8mb4)
    LEFT JOIN tbcabang c ON c.kode_cabang = u.kode_cabang
    WHERE CONVERT(pk.kode_karyawan USING utf8mb4) = CONVERT('$kode_karyawan' USING utf8mb4)
    AND CONVERT(pk.kode_transaksi USING utf8mb4) = CONVERT('$kode_transaksi' USING utf8mb4)
    ORDER BY pk.tanggal DESC, pk.waktu DESC";

$resultKasMasuk = mysqli_query($conn, $sqlKasMasuk);

if (!$resultKasMasuk) {
    die("Query failed: " . mysqli_error($conn));
}

// Ambil kode_karyawan dan nama_cabang dari kasir_transactions_closing_kasir
$sql_user_cabang = "SELECT kode_karyawan, nama_cabang FROM kasir_transactions_closing_kasir WHERE kode_transaksi = :kode_transaksi";
$stmt_user_cabang = $pdo->prepare($sql_user_cabang);
$stmt_user_cabang->bindParam(':kode_transaksi', $kode_transaksi);
$stmt_user_cabang->execute();
$user_cabang_data = $stmt_user_cabang->fetch(PDO::FETCH_ASSOC);

$kode_karyawan_display = $user_cabang_data['kode_karyawan'] ?? 'Tidak diketahui';
$cabang = $user_cabang_data['nama_cabang'] ?? 'Tidak diketahui';

// Ambil nama karyawan dan nama cabang berdasarkan kode_karyawan pemilik transaksi (bisa beda
// dari user login kalau yang lihat itu admin/keuangan) — dari tbuser+tbcabang, bukan users lama.
$sql_nama_karyawan = "SELECT u.nama_lengkap AS nama_karyawan, c.nama_cabang
                       FROM tbuser u LEFT JOIN tbcabang c ON c.kode_cabang = u.kode_cabang
                       WHERE u.kode_karyawan = :kode_karyawan";
$stmt_nama_karyawan = $pdo->prepare($sql_nama_karyawan);
$stmt_nama_karyawan->bindParam(':kode_karyawan', $kode_karyawan_display);
$stmt_nama_karyawan->execute();
$nama_karyawan_data = $stmt_nama_karyawan->fetch(PDO::FETCH_ASSOC);

$nama_karyawan = $nama_karyawan_data['nama_karyawan'] ?? 'Tidak diketahui';
$nama_cabang = $nama_karyawan_data['nama_cabang'] ?? 'Tidak diketahui';
// Gabungkan kode_karyawan dan nama_karyawan
$karyawan_info = $kode_karyawan_display . ' - ' . ($nama_karyawan ?? 'Tidak diketahui');

$username = $nama_karyawan_aktif ?? 'Unknown User';
$cabang_user = $nama_cabang_aktif ?? $user_nama_cabang ?? 'Unknown Cabang';
$closing_detect_nama_cabang = $user_nama_cabang ?: $cabang_user;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemasukan Kasir</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            --secondary-color: #6c757d;
            --warning-color: #ffc107;
            --background-light: #f8fafc;
            --text-dark: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #1e293b;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
            transition: width 0.3s ease;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .logout-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            text-align: left;
            margin-top: 20px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
            flex: 1;
            transition: margin-left 0.3s ease;
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
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        .page-header .subtitle {
            color: var(--text-muted);
            font-size: 16px;
        }
        .transaction-code {
            background: var(--background-light);
            padding: 8px 16px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 12px;
            font-weight: 600;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-card .label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .info-card .value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 14px;
        }
        .form-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-left: 10px;
            font-style: italic;
        }
        .form-control, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-control[readonly] {
            background: var(--background-light);
            color: var(--text-muted);
            cursor: not-allowed;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            justify-content: center;
        }
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #c82333);
            color: white;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #e0a800);
            color: white;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-top: 32px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background: var(--background-light);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }
        .table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        .table tbody tr:hover {
            background: var(--background-light);
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 32px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border-color);
        }
        .form-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .search-container {
            position: relative;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .search-results.show {
            display: block;
        }
        .search-result-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.3s ease;
        }
        .search-result-item:hover {
            background: var(--background-light);
        }
        .search-result-item:last-child {
            border-bottom: none;
        }
        .search-result-item .nama {
            font-weight: 600;
            color: var(--text-dark);
        }
        .search-result-item .kode {
            font-size: 12px;
            color: var(--text-muted);
        }
        .search-result-item.highlight {
            background: var(--primary-color);
            color: white;
        }
        .search-result-item.highlight .nama,
        .search-result-item.highlight .kode {
            color: white;
        }
        .no-results {
            padding: 16px;
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
        }
        .search-hint {
            background: rgba(0,123,255,0.05);
            border: 1px solid rgba(0,123,255,0.2);
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 4px;
            font-size: 12px;
            color: var(--primary-color);
        }
        .closing-info {
            background: #e3f2fd;
            border: 1px solid #1976d2;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            display: none;
        }
        .closing-info.show {
            display: block;
        }
        .closing-info h6 {
            color: #1976d2;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .closing-info .closing-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .closing-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .closing-detail-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .closing-detail-value {
            font-weight: 600;
            color: var(--text-dark);
        }
        .closing-detail-value.amount {
            color: var(--success-color);
            font-size: 16px;
        }
        .required {
            color: var(--danger-color);
        }
        .validation-message {
            background: rgba(220,53,69,0.1);
            border: 1px solid rgba(220,53,69,0.2);
            color: var(--danger-color);
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 12px;
            display: none;
        }
        .validation-message.show {
            display: block;
        }

        .keuangan-workflow-card {
            background: linear-gradient(180deg, #fff7ed 0%, #ffffff 30%);
            border: 1px solid #fed7aa;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 18px 40px rgba(249, 115, 22, 0.08);
        }
        .keuangan-workflow-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }
        .keuangan-kicker {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .keuangan-workflow-head h4 {
            margin: 0 0 6px;
            font-size: 22px;
            color: #7c2d12;
        }
        .keuangan-workflow-head p {
            margin: 0;
            color: #9a3412;
            font-size: 14px;
            max-width: 680px;
            line-height: 1.6;
        }
        .keuangan-badge {
            background: #dc2626;
            color: #fff;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 12px 24px rgba(220, 38, 38, 0.18);
        }
        .keuangan-expected-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin: 16px 0;
        }
        .expected-info-card {
            border-radius: 18px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .expected-info-card.expected-lender {
            border-color: #bfdbfe;
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
        }
        .expected-info-card.expected-borrower {
            border-color: #fecaca;
            background: linear-gradient(180deg, #fff1f2 0%, #ffffff 100%);
        }
        .expected-info-card.expected-balance {
            border-color: #fde68a;
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
        }
        .expected-label {
            font-size: 11px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .expected-value {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
            word-break: break-word;
        }
        .expected-hint {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        .keuangan-flow-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #fff;
            border: 1px dashed #fdba74;
            color: #9a3412;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        .pelunasan-upload-box {
            border-radius: 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 18px;
        }
        .pelunasan-upload-copy {
            margin-bottom: 12px;
        }
        .upload-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        .upload-subtitle {
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }
        .pelunasan-upload-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
        }
        .upload-filename {
            margin-top: 10px;
            font-size: 12px;
            color: #64748b;
        }
        .keuangan-inline-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
        }
        .keuangan-inline-alert.info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }
        .keuangan-inline-alert.warning {
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #9a3412;
        }
        .keuangan-inline-alert.error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
        }
        .keuangan-inline-alert.success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #15803d;
        }
        .pelunasan-result-card {
            margin-top: 16px;
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 18px;
            padding: 18px;
        }
        .pelunasan-result-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .pelunasan-result-item {
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .result-label {
            font-size: 11px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .result-value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }
        .pelunasan-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }
        .pelunasan-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .pelunasan-check.ok {
            background: #dcfce7;
            color: #166534;
            border-color: #86efac;
        }
        .pelunasan-check.fail {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        /* PERBAIKAN: Style untuk keterangan default */
        .keterangan-default {
            background: rgba(0,123,255,0.05);
            border: 1px solid rgba(0,123,255,0.2);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--primary-color);
            display: none;
        }
        .keterangan-default.show {
            display: block;
        }
        .keterangan-default strong {
            color: var(--text-dark);
        }

        /* PERBAIKAN: Style untuk validasi input nama transaksi */
        .form-control.valid {
            border-color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.05);
        }
        .form-control.invalid {
            border-color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.05);
        }
        .form-control.empty {
            border-color: var(--border-color);
            background-color: white;
        }
        .validation-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            display: none;
        }
        .validation-icon.valid {
            color: var(--success-color);
            display: block;
        }
        .validation-icon.invalid {
            color: var(--danger-color);
            display: block;
        }
        @media (max-width: 768px) {
            .keuangan-workflow-head {
                flex-direction: column;
            }
            .pelunasan-upload-actions {
                grid-template-columns: 1fr;
            }
            .expected-value {
                font-size: 18px;
            }
        }
        .validation-icon.empty {
            display: none;
        }
        .input-group {
            position: relative;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .table {
                font-size: 12px;
            }
            .table th,
            .table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <a href="index_kasir.php"><i class="fas fa-tachometer-alt"></i> Dashboard Kasir</a>
        <a href="serah_terima_kasir_closing_kasir.php"><i class="fas fa-handshake"></i> Serah Terima Kasir</a>
        <a href="setoran_keuangan_cs.php"><i class="fas fa-money-bill"></i> Setoran Keuangan CS</a>
        <button class="logout-btn" onclick="window.location.href='logout.php';"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>

    <div class="main-content">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo htmlspecialchars($cabang_user); ?>)
                <p style="color: var(--text-muted); font-size: 12px;">Kasir</p>
            </div>
        </div>

        <div class="breadcrumb">
            <a href="index_kasir.php"><i class="fas fa-home"></i> Dashboard</a>
            <i class="fas fa-chevron-right"></i>
            <span>Pemasukan Kasir</span>
        </div>

        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Pemasukan Kasir</h1>
            <p class="subtitle">Input dan kelola data pemasukan kas</p>
            <div class="transaction-code">
                <i class="fas fa-receipt"></i> <?php echo htmlspecialchars($kode_transaksi); ?>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="info-grid">
            <div class="info-card">
                <div class="label">Karyawan</div>
                <div class="value"><?php echo htmlspecialchars($karyawan_info); ?></div>
            </div>
            <div class="info-card">
                <div class="label">Cabang</div>
                <div class="value"><?php echo htmlspecialchars($nama_cabang); ?></div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Pemasukan kasir berhasil ditambahkan.
            </div>
        <?php endif; ?>

        <!-- Form Section -->
        <div class="form-section">
            <h3 style="margin-bottom: 20px; color: var(--text-dark);"><i class="fas fa-edit"></i> Form Input Pemasukan</h3>
            <form method="POST" action="" id="pemasukanForm">
                <!-- Nama Transaksi dengan Auto Search - PERBAIKAN -->
                <div class="form-group">
                    <label for="nama_transaksi" class="form-label">
                        <i class="fas fa-search"></i> Nama Transaksi <span class="required">*</span>
                        <span class="form-hint">Pilih dari daftar transaksi yang tersedia. Tidak bisa membuat nama transaksi sendiri.</span>
                    </label>
                    <div class="search-container">
                        <div class="input-group">
                            <input type="text" id="nama_transaksi" name="nama_transaksi" class="form-control empty" 
                                   placeholder="Klik untuk mencari transaksi pemasukan..." 
                                   autocomplete="off" readonly required>
                            <div class="validation-icon empty" id="validation_icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                        </div>
                        <div class="search-hint">
                            <i class="fas fa-keyboard"></i> Klik untuk mencari transaksi, gunakan ↑↓ untuk navigasi, Enter untuk memilih
                        </div>
                        <div class="search-results" id="search_results">
                            <!-- Results will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Kolom Nomor Transaksi Closing (Hidden by default) -->
                <div class="form-group" id="closing_section" style="display: none;">
                    <label for="nomor_transaksi_closing" class="form-label">
                        <i class="fas fa-list-ol"></i> Nomor Transaksi Closing <span class="required">*</span>
                        <span class="form-hint">Pilih transaksi closing dari cabang Anda yang sudah end proses dan belum disetor, termasuk yang dikembalikan ke CS.</span>
                    </label>
                    <select name="nomor_transaksi_closing" id="nomor_transaksi_closing" class="form-select">
                        <option value="">-- Pilih Transaksi Closing --</option>
                        <?php foreach ($available_closing as $closing): ?>
                            <option value="<?php echo htmlspecialchars($closing['kode_transaksi']); ?>"
                                    data-setoran="<?php echo $closing['setoran_real']; ?>"
                                    data-tanggal="<?php echo date('d/m/Y', strtotime($closing['tanggal_transaksi'])); ?>"
                                    data-karyawan="<?php echo htmlspecialchars($closing['kode_karyawan']); ?>"
                                    data-status="<?php echo htmlspecialchars($closing['deposit_status'] ?? 'Belum Disetor'); ?>"
                                    <?php echo ($closing['deposit_status'] == 'Dikembalikan ke CS') ? 'style="background-color: #fff3cd; color: #856404;"' : ''; ?>>
                                <?php
                                $status_label = '';
                                if ($closing['deposit_status'] == 'Dikembalikan ke CS') {
                                    $status_label = ' 🔄 [DIKEMBALIKAN KE CS]';
                                }
                                ?>
                                <?php echo htmlspecialchars($closing['kode_transaksi']); ?> -
                                <?php echo date('d/m/Y', strtotime($closing['tanggal_transaksi'])); ?> -
                                Kasir: <?php echo htmlspecialchars($closing['kode_karyawan']); ?> -
                                Rp <?php echo number_format($closing['setoran_real'], 0, ',', '.'); ?><?php echo $status_label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- P9: Banner notif edit nominal pengambilan -->
                <?php
                $editLogs = getEditLogUnreadForCabang($pdo, $user_kode_cabang);
                if (!empty($editLogs)) {
                    markEditLogDibaca($pdo, $user_kode_cabang);
                }
                if (!empty($editLogs)):
                ?>
                <div class="alert" style="background:#fefce8;border:1px solid #fde047;color:#713f12;border-radius:12px;padding:14px 18px;margin-bottom:16px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Terdapat <?php echo count($editLogs); ?> pengambilan dana yang nominalnya telah diperbarui oleh keuangan:
                    <ul style="margin:8px 0 0 20px;">
                        <?php foreach ($editLogs as $log): ?>
                        <li>Kode <strong><?php echo htmlspecialchars($log['kode_pengambilan']); ?></strong> — nominal sekarang <strong>Rp <?php echo number_format((float)$log['nominal_baru'], 0, ',', '.'); ?></strong>. <?php echo ((float)$log['nominal_baru'] > 0) ? 'Silakan input ulang pemasukan dari keuangan.' : 'Pengambilan ini sudah menjadi Rp 0, jadi tidak perlu input ulang.'; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- P19: Dropdown pilih pengambilan dana (hanya cabang sendiri, belum diinput) -->
                <div class="form-group" id="keuangan_section" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-hand-holding-usd"></i> Pilih Pengambilan Dana <span class="required">*</span>
                        <span class="form-hint">Hanya menampilkan pengambilan untuk cabang Anda yang belum diinput pemasukannya.</span>
                    </label>
                    <?php if (empty($available_pengadaan)): ?>
                    <div style="padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;color:#64748b;font-size:13px;">
                        <i class="fas fa-info-circle"></i> Tidak ada pengambilan dana aktif untuk cabang ini.
                    </div>
                    <?php else: ?>
                    <select name="kode_pengambilan_ref" id="kode_pengambilan_ref" class="form-select" required onchange="onPengambilanSelected(this.value)">
                        <option value="">-- Pilih Kode Pengambilan Dana --</option>
                        <?php foreach ($available_pengadaan as $pga): ?>
                        <option value="<?php echo htmlspecialchars($pga['kode_pengambilan']); ?>"
                                data-nominal="<?php echo (int)$pga['nominal_diambil']; ?>"
                                data-keterangan="<?php echo htmlspecialchars('Pengambilan dana dari keuangan' . ($pga['nama_cabang_keuangan'] ? ' — ' . $pga['nama_cabang_keuangan'] : '') . ' — kode ' . $pga['kode_pengambilan']); ?>">
                            <?php echo htmlspecialchars($pga['kode_pengambilan']); ?> —
                            Rp <?php echo number_format((float)$pga['nominal_diambil'], 0, ',', '.'); ?>
                            <?php echo $pga['nama_cabang_keuangan'] ? '(dari ' . htmlspecialchars($pga['nama_cabang_keuangan']) . ')' : ''; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>

                <!-- Info Jenis Closing Otomatis -->
                <div class="form-group" id="jenis_closing_info" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-robot"></i> <strong>Jenis Closing Otomatis</strong>
                        <p class="mb-0">Sistem akan menentukan jenis closing secara otomatis:</p>
                        <ul class="mb-0 mt-2">
                            <li><strong>Dipinjam:</strong> Jika transaksi sebelumnya dipinjam dari kasir lain</li>
                            <li><strong>Meminjam:</strong> Jika ada transaksi sedang berjalan dengan pemasukan "DARI CLOSING"</li>
                            <li><strong>Normal:</strong> Kondisi lainnya (closing standar)</li>
                        </ul>
                        <div id="detected_closing_type" class="mt-2" style="display: none;">
                            <span class="badge badge-primary">Jenis Terdeteksi: <span id="closing_type_text"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Info Closing yang Dipilih -->
                <div class="closing-info" id="closing_info">
                    <h6><i class="fas fa-info-circle"></i> Informasi Transaksi Closing</h6>
                    <div class="closing-details">
                        <div class="closing-detail-item">
                            <span class="closing-detail-label">Kode Transaksi:</span>
                            <span class="closing-detail-value" id="closing_kode">-</span>
                        </div>
                        <div class="closing-detail-item">
                            <span class="closing-detail-label">Tanggal:</span>
                            <span class="closing-detail-value" id="closing_tanggal">-</span>
                        </div>
                        <div class="closing-detail-item">
                            <span class="closing-detail-label">Kasir Closing:</span>
                            <span class="closing-detail-value" id="closing_karyawan">-</span>
                        </div>
                        <div class="closing-detail-item">
                            <span class="closing-detail-label">Nilai Setoran:</span>
                            <span class="closing-detail-value amount" id="closing_setoran">-</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kode_akun" class="form-label">
                        <i class="fas fa-code"></i> Kode Akun
                        <span class="form-hint">Otomatis terisi sesuai dengan nama transaksi yang dipilih.</span>
                    </label>
                    <select name="kode_akun" id="kode_akun" class="form-select" readonly style="pointer-events: none; background: var(--background-light); cursor: not-allowed;">
                        <option value="">-- Otomatis terisi berdasarkan nama transaksi --</option>
                        <?php foreach ($master_akun_closing_kasir as $akun): ?>
                            <option value="<?php echo $akun['kode_akun']; ?>"><?php echo $akun['kode_akun'] . ' - ' . $akun['arti']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah" class="form-label">
                        <i class="fas fa-money-bill-wave"></i> Jumlah (Rp) <span class="required">*</span>
                        <span class="form-hint">Masukkan jumlah dalam rupiah. Untuk pengambilan dana, nominal otomatis terisi dari data keuangan.</span>
                    </label>
                    <input type="number" name="jumlah" id="jumlah" class="form-control" step="1" placeholder="Masukkan jumlah" required>
                    <div class="validation-message" id="validation_message"></div>
                </div>

                <div class="form-group">
                    <label for="keterangan_transaksi" class="form-label">
                        <i class="fas fa-sticky-note"></i> Keterangan Transaksi <span class="required">*</span>
                        <span class="form-hint">Keterangan wajib diisi untuk melengkapi data transaksi.</span>
                    </label>
                    <!-- PERBAIKAN: Tampilkan keterangan default di atas input tanpa auto-fill -->
                    <div class="keterangan-default" id="keterangan_default">
                        <strong>Keterangan Default:</strong> <span id="default_text">-</span>
                    </div>
                    <input type="text" name="keterangan_transaksi" id="keterangan_transaksi" class="form-control" oninput="this.value = this.value.toUpperCase()" placeholder="Masukkan keterangan" required>
                </div>

                <button type="submit" name="submit_pemasukan" class="btn btn-success" style="width: 100%;" id="submit_btn">
                    <i class="fas fa-save"></i> Tambah Pemasukan
                </button>
            </form>
        </div>

        <!-- Data Table Section -->
        <h2 class="section-title"><i class="fas fa-table"></i> Data Pemasukan Kasir Terbaru</h2>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Kasir</th>
                        <th>Kode Akun</th>
                        <th>Jumlah (Rp)</th>
                        <th>Keterangan</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>No. Trx Closing</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while ($row = mysqli_fetch_assoc($resultKasMasuk)) : ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $row['nama_karyawan']; ?></td>
                        <td style="font-weight: 600;"><?php echo $row['kode_akun']; ?></td>
                        <td style="font-weight: 600; color: var(--success-color);">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                        <td><?php echo $row['keterangan_transaksi']; ?></td>
                        <td><?php echo $row['tanggal']; ?></td>
                        <td><?php echo $row['waktu']; ?></td>
                        <td>
                            <?php if (!empty($row['kode_pengambilan_ref'])): ?>
                                <span style="color: var(--text-muted);"></span>
                            <?php elseif (!empty($row['nomor_transaksi_closing'])): ?>
                                <a href="detail_closing.php?kode_transaksi=<?php echo $row['nomor_transaksi_closing']; ?>" 
                                   class="btn btn-info btn-sm" style="text-decoration: none;">
                                    <i class="fas fa-link"></i> <?php echo htmlspecialchars($row['nomor_transaksi_closing']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['kode_pengambilan_ref'])): ?>
                            <a href="hapus_pemasukan.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pemasukan pengambilan dana ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                            <?php elseif (($row['kode_akun'] ?? '') === 'DRKUAN'): ?>
                                <span style="font-size:12px; color:#64748b; font-style:italic;">
                                    <i class="fas fa-lock"></i> Terkunci
                                </span>
                            <?php else: ?>
                            <a href="edit_pemasukan.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="hapus_pemasukan.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const masterNamaTransaksi = <?php echo json_encode($master_nama_transaksi_closing_kasir); ?>;
        const masterAkun = <?php echo json_encode($master_akun_closing_kasir); ?>;
        const availablePengadaanMap = <?php echo json_encode($available_pengadaan_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        function onPengambilanSelected(kode) {
            const data = availablePengadaanMap[kode];
            const jumlahEl = document.getElementById('jumlah');
            const ketEl = document.getElementById('keterangan_transaksi');
            if (!data) {
                if (jumlahEl) { jumlahEl.value = ''; jumlahEl.readOnly = false; jumlahEl.style.background = '#fff'; }
                return;
            }
            if (jumlahEl) {
                jumlahEl.value = Math.round(data.nominal_diambil || 0);
                jumlahEl.readOnly = true;
                jumlahEl.style.background = 'var(--background-light)';
                jumlahEl.style.cursor = 'not-allowed';
            }
            if (ketEl) { ketEl.value = data.keterangan_auto || ''; }
        }

        let selectedTransaksi = null;
        let currentHighlightIndex = -1;
        let filteredResults = [];
        let isValidSelection = false;
        const namaTransaksiInput = document.getElementById('nama_transaksi');
        const searchResults = document.getElementById('search_results');
        const validationIcon = document.getElementById('validation_icon');
        const kodePengambilanInput = document.getElementById('kode_pengambilan_ref');
        const pelunasanUploadStatus = document.getElementById('pelunasan_upload_status');
        const pelunasanResultCard = document.getElementById('pelunasan_result_card');
        const jumlahInput = document.getElementById('jumlah');

        function formatRupiah(value) {
            return 'Rp ' + (parseInt(value || 0, 10) || 0).toLocaleString('id-ID');
        }

        function setPelunasanAmountState(locked, nominal = '', forceClear = false) {
            jumlahInput.readOnly = locked;
            jumlahInput.style.background = locked ? 'var(--background-light)' : '#fff';
            jumlahInput.style.cursor = locked ? 'not-allowed' : 'text';
            if (nominal !== '') {
                jumlahInput.value = nominal;
            } else if (forceClear || !locked) {
                jumlahInput.value = '';
            }
        }

        function resetPelunasanDocumentState(options = {}) {
            const preserveCode = options.preserveCode === true;
            const preserveExpected = options.preserveExpected === true;
            if (pelunasanResultCard) pelunasanResultCard.style.display = 'none';
            setUploadStatus('info', '', false);
            setPelunasanAmountState(false);

            if (!preserveCode && kodePengambilanInput) {
                kodePengambilanInput.value = '';
            }

            if (!preserveExpected) {
                const expectedInfo = document.getElementById('keuangan_expected_info');
                if (expectedInfo) expectedInfo.style.display = 'none';
                const elRekPemberi = document.getElementById('rekening_pemberi_pinjaman');
                if (elRekPemberi) elRekPemberi.textContent = '-';
                const elRekPenerima = document.getElementById('rekening_penerima_pinjaman');
                if (elRekPenerima) elRekPenerima.textContent = '-';
                const elSisaHutang = document.getElementById('sisa_hutang_reference');
                if (elSisaHutang) elSisaHutang.textContent = 'Rp 0';
                const elCaption = document.getElementById('keuangan_reference_caption');
                if (elCaption) elCaption.textContent = 'Data rekening diambil dari record pengambilan aktif.';
            }

            setCheckState('check_sender', false, 'Pengirim belum diverifikasi');
            setCheckState('check_receiver', false, 'Penerima belum diverifikasi');
            setCheckState('check_nominal', false, 'Nominal belum diverifikasi');
        }

        function setUploadStatus(type, message, visible = true) {
            if (!pelunasanUploadStatus) {
                return;
            }

            pelunasanUploadStatus.className = `keuangan-inline-alert ${type}`;
            pelunasanUploadStatus.innerHTML = message ? `<i class="fas fa-info-circle"></i> ${message}` : '';
            pelunasanUploadStatus.style.display = visible && message ? 'flex' : 'none';
        }

        function setCheckState(elementId, passed, label) {
            const element = document.getElementById(elementId);
            if (!element) {
                return;
            }

            element.classList.remove('ok', 'fail');
            element.classList.add(passed ? 'ok' : 'fail');
            element.innerHTML = `<i class="fas ${passed ? 'fa-check-circle' : 'fa-times-circle'}"></i> ${label}`;
        }

        function renderStoredMutasiInfo(data) {
            document.getElementById('hasil_status_mutasi').textContent = 'SUDAH DIVERIFIKASI';
            document.getElementById('hasil_pengirim').textContent = data.mutasi_rekening_pengirim || '-';
            document.getElementById('hasil_penerima').textContent = data.mutasi_rekening_penerima || '-';
            document.getElementById('hasil_nominal').textContent = data.mutasi_nominal_terdeteksi ? formatRupiah(data.mutasi_nominal_terdeteksi) : '-';
            document.getElementById('hasil_confidence').textContent = (data.mutasi_confidence || 'medium').toUpperCase();
            document.getElementById('hasil_dokumen').textContent = data.mutasi_dokumen_nama_asli || '-';

            setCheckState(
                'check_sender',
                !!data.mutasi_rekening_pengirim,
                data.mutasi_rekening_pengirim ? 'Rekening pengirim sudah cocok' : 'Rekening pengirim belum tersedia'
            );
            setCheckState(
                'check_receiver',
                !!data.mutasi_rekening_penerima,
                data.mutasi_rekening_penerima ? 'Rekening penerima sudah cocok' : 'Rekening penerima belum tersedia'
            );
            setCheckState(
                'check_nominal',
                !!data.mutasi_nominal_terdeteksi,
                data.mutasi_nominal_terdeteksi ? 'Nominal mutasi siap dipakai kasir' : 'Nominal mutasi belum tersedia'
            );

            if (pelunasanResultCard) pelunasanResultCard.style.display = 'block';
        }

        function renderPendingMutasiInfo(data) {
            document.getElementById('hasil_status_mutasi').textContent = 'MENUNGGU VERIFIKASI KEUANGAN';
            document.getElementById('hasil_pengirim').textContent = '-';
            document.getElementById('hasil_penerima').textContent = '-';
            document.getElementById('hasil_nominal').textContent = '-';
            document.getElementById('hasil_confidence').textContent = '-';
            document.getElementById('hasil_dokumen').textContent = data.mutasi_dokumen_nama_asli || '-';

            setCheckState('check_sender', false, 'Pengirim belum diverifikasi keuangan');
            setCheckState('check_receiver', false, 'Penerima belum diverifikasi keuangan');
            setCheckState('check_nominal', false, 'Nominal belum diverifikasi keuangan');

            if (pelunasanResultCard) pelunasanResultCard.style.display = 'block';
        }

        function renderKeuanganReferenceInfo() {
            if (!kodePengambilanInput) return;
            const kode = kodePengambilanInput.value.trim().toUpperCase();
            const data = availablePengadaanMap[kode];
            if (!data) return;
            // P4: auto-fill nominal dan keterangan
            if (jumlahInput) {
                jumlahInput.value = Math.round(data.nominal_diambil || 0);
                jumlahInput.readOnly = true;
                jumlahInput.style.background = 'var(--background-light)';
            }
            const ketInput = document.getElementById('keterangan_transaksi');
            if (ketInput) {
                ketInput.value = data.keterangan_auto || '';
            }
        }

        // PERBAIKAN: Event listener untuk klik pada input (mengaktifkan mode edit)
        namaTransaksiInput.addEventListener('click', function() {
            if (this.readOnly) {
                this.readOnly = false;
                this.focus();
                this.value = '';
                this.placeholder = "Ketik untuk mencari transaksi pemasukan...";
                updateValidationState('empty');
            }
        });

        // PERBAIKAN: Event listener untuk blur (keluar dari input)
        namaTransaksiInput.addEventListener('blur', function(e) {
            // Delay untuk memungkinkan klik pada dropdown
            setTimeout(() => {
                if (!searchResults.matches(':hover')) {
                    validateCurrentInput();
                }
            }, 200);
        });

        // Input event listener for real-time search
        namaTransaksiInput.addEventListener('input', function() {
            const query = this.value.trim();
            currentHighlightIndex = -1;
            
            if (query.length >= 1) {
                filteredResults = masterNamaTransaksi.filter(item => 
                    item.nama_transaksi.toLowerCase().includes(query.toLowerCase()) ||
                    item.kode_akun.toLowerCase().includes(query.toLowerCase()) ||
                    item.arti.toLowerCase().includes(query.toLowerCase())
                );
                populateDropdown(filteredResults);
                searchResults.classList.add('show');
                
                // Check if current input exactly matches any result
                const exactMatch = masterNamaTransaksi.find(item => 
                    item.nama_transaksi.toLowerCase() === query.toLowerCase()
                );
                updateValidationState(exactMatch ? 'valid' : 'invalid');
            } else {
                searchResults.classList.remove('show');
                filteredResults = [];
                updateValidationState('empty');
                resetForm();
            }
        });

        // Keyboard navigation
        namaTransaksiInput.addEventListener('keydown', function(e) {
            const items = searchResults.querySelectorAll('.search-result-item:not(.no-results)');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentHighlightIndex = Math.min(currentHighlightIndex + 1, items.length - 1);
                updateHighlight(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentHighlightIndex = Math.max(currentHighlightIndex - 1, 0);
                updateHighlight(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentHighlightIndex >= 0 && items[currentHighlightIndex]) {
                    const index = parseInt(items[currentHighlightIndex].dataset.index);
                    selectTransaksi(filteredResults[index]);
                }
            } else if (e.key === 'Escape') {
                searchResults.classList.remove('show');
                currentHighlightIndex = -1;
                validateCurrentInput();
            }
        });

        function updateHighlight(items) {
            items.forEach((item, index) => {
                if (index === currentHighlightIndex) {
                    item.classList.add('highlight');
                } else {
                    item.classList.remove('highlight');
                }
            });
        }

        // Populate dropdown with filtered results
        function populateDropdown(results) {
            searchResults.innerHTML = '';
            
            if (results.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.innerHTML = '<i class="fas fa-search"></i> Tidak ada transaksi yang ditemukan';
                searchResults.appendChild(noResults);
                return;
            }
            
            results.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.dataset.index = index;
                
                // Highlight matching text
                const query = namaTransaksiInput.value.toLowerCase();
                const namaHighlighted = highlightText(item.nama_transaksi, query);
                const kodeHighlighted = highlightText(`${item.kode_akun} - ${item.arti}`, query);
                
                div.innerHTML = `
                    <div class="nama">${namaHighlighted}</div>
                    <div class="kode">${kodeHighlighted}</div>
                `;
                
                div.addEventListener('click', function() {
                    selectTransaksi(item);
                });
                
                div.addEventListener('mouseenter', function() {
                    currentHighlightIndex = index;
                    updateHighlight(searchResults.querySelectorAll('.search-result-item:not(.no-results)'));
                });
                
                searchResults.appendChild(div);
            });
        }

        function highlightText(text, query) {
            if (!query) return text;
            
            const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
            return text.replace(regex, '<mark style="background: yellow; padding: 0;">$1</mark>');
        }

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function resetForm() {
            selectedTransaksi = null;
            document.getElementById('kode_akun').value = '';
            document.getElementById('keterangan_default').classList.remove('show');
            document.getElementById('closing_section').style.display = 'none';
            document.getElementById('jenis_closing_info').style.display = 'none';
            document.getElementById('closing_info').classList.remove('show');
            document.getElementById('nomor_transaksi_closing').required = false;
            const keuanganSection = document.getElementById('keuangan_section');
            if (keuanganSection) {
                keuanganSection.style.display = 'none';
                const kpRef = document.getElementById('kode_pengambilan_ref');
                if (kpRef) kpRef.required = false;
            }
            if (jumlahInput) { jumlahInput.readOnly = false; jumlahInput.style.background = '#fff'; jumlahInput.style.cursor = 'text'; jumlahInput.value = ''; }
            hideValidationMessage();
        }

        // PERBAIKAN: Function untuk validasi input saat ini
        function validateCurrentInput() {
            const currentValue = namaTransaksiInput.value.trim();
            
            if (currentValue === '') {
                namaTransaksiInput.readOnly = true;
                namaTransaksiInput.placeholder = "Klik untuk mencari transaksi pemasukan...";
                updateValidationState('empty');
                resetForm();
                searchResults.classList.remove('show');
                return;
            }
            
            const isValid = masterNamaTransaksi.some(item => 
                item.nama_transaksi.toLowerCase() === currentValue.toLowerCase()
            );
            
            if (isValid) {
                const matchedItem = masterNamaTransaksi.find(item => 
                    item.nama_transaksi.toLowerCase() === currentValue.toLowerCase()
                );
                selectTransaksi(matchedItem, false); // false = tidak dari dropdown click
            } else {
                // Reset ke nilai kosong jika tidak valid
                namaTransaksiInput.value = '';
                updateValidationState('empty');
                resetForm();
            }
            
            // Set kembali ke readonly mode
            namaTransaksiInput.readOnly = true;
            namaTransaksiInput.placeholder = "Klik untuk mencari transaksi pemasukan...";
            searchResults.classList.remove('show');
        }

        // PERBAIKAN: Function untuk update status validasi visual
        function updateValidationState(state) {
            const input = namaTransaksiInput;
            const icon = validationIcon;
            
            // Remove all validation classes
            input.classList.remove('valid', 'invalid', 'empty');
            icon.classList.remove('valid', 'invalid', 'empty');
            
            switch(state) {
                case 'valid':
                    isValidSelection = true;
                    input.classList.add('valid');
                    icon.classList.add('valid');
                    icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    break;
                case 'invalid':
                    isValidSelection = false;
                    input.classList.add('invalid');
                    icon.classList.add('invalid');
                    icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                    break;
                case 'empty':
                default:
                    isValidSelection = false;
                    input.classList.add('empty');
                    icon.classList.add('empty');
                    icon.innerHTML = '<i class="fas fa-question-circle"></i>';
                    break;
            }
        }

        function selectTransaksi(item, fromDropdown = true) {
            selectedTransaksi = item;
            
            // Set nama transaksi
            namaTransaksiInput.value = item.nama_transaksi;
            
            // Set kode akun
            document.getElementById('kode_akun').value = item.kode_akun;
            
            // PERBAIKAN: Tampilkan keterangan default di atas input, tidak auto-fill ke input
            const keteranganDefault = document.getElementById('keterangan_default');
            const defaultText = document.getElementById('default_text');
            
            if (item.keterangan_default) {
                defaultText.textContent = item.keterangan_default;
                keteranganDefault.classList.add('show');
            } else {
                keteranganDefault.classList.remove('show');
            }
            
            // Kosongkan keterangan hanya saat user memilih transaksi baru dari dropdown
            if (fromDropdown) {
                document.getElementById('keterangan_transaksi').value = '';
            }
            
            // Show/hide closing section for akun closing
            const closingSection = document.getElementById('closing_section');
            const jenisClosingInfo = document.getElementById('jenis_closing_info');
            const closingInfo = document.getElementById('closing_info');
            const needsClosingReference = item && (
                (item.nama_transaksi || '').toUpperCase() === 'DARI CLOSING' ||
                (item.kode_akun || '').toUpperCase() === 'DRCLSG'
            );
            
            const needsKeuanganReference = item && (
                (item.nama_transaksi || '').toUpperCase() === 'PENGAMBILAN DANA' ||
                (item.kode_akun || '').toUpperCase() === 'DRKUAN'
            );
            const keuanganSection = document.getElementById('keuangan_section');
            const kodeRef = document.getElementById('kode_pengambilan_ref');

            if (needsClosingReference) {
                closingSection.style.display = 'block';
                jenisClosingInfo.style.display = 'block';
                document.getElementById('nomor_transaksi_closing').required = true;
                if (keuanganSection) {
                    keuanganSection.style.display = 'none';
                    if (kodeRef) kodeRef.required = false;
                    resetPelunasanDocumentState();
                }
            } else if (needsKeuanganReference) {
                closingSection.style.display = 'none';
                jenisClosingInfo.style.display = 'none';
                closingInfo.classList.remove('show');
                document.getElementById('nomor_transaksi_closing').required = false;
                document.getElementById('nomor_transaksi_closing').value = '';
                if (keuanganSection) {
                    keuanganSection.style.display = 'block';
                    if (kodeRef) kodeRef.required = true;
                    renderKeuanganReferenceInfo();
                }
            } else {
                closingSection.style.display = 'none';
                jenisClosingInfo.style.display = 'none';
                closingInfo.classList.remove('show');
                document.getElementById('nomor_transaksi_closing').required = false;
                document.getElementById('nomor_transaksi_closing').value = '';
                if (keuanganSection) {
                    keuanganSection.style.display = 'none';
                    if (kodeRef) kodeRef.required = false;
                    resetPelunasanDocumentState();
                }
            }
            
            // Update validation state
            updateValidationState('valid');
            
            if (fromDropdown) {
                // Set ke readonly mode setelah selection
                namaTransaksiInput.readOnly = true;
                namaTransaksiInput.placeholder = "Klik untuk mengubah nama transaksi";
                
                // Hide search results
                searchResults.classList.remove('show');
                currentHighlightIndex = -1;
            }
            
            // Reset validation
            hideValidationMessage();
        }

        // Handle closing transaction selection
        document.getElementById('nomor_transaksi_closing').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const closingInfo = document.getElementById('closing_info');

            if (this.value) {
                const setoran = selectedOption.dataset.setoran;
                const tanggal = selectedOption.dataset.tanggal;
                const karyawan = selectedOption.dataset.karyawan;
                const status = selectedOption.dataset.status || 'Belum Disetor';
                const kode = this.value;

                document.getElementById('closing_kode').textContent = kode;
                document.getElementById('closing_tanggal').textContent = tanggal;
                document.getElementById('closing_karyawan').textContent = karyawan;
                document.getElementById('closing_setoran').textContent = 'Rp ' + parseInt(setoran).toLocaleString('id-ID');

                // Add status information untuk transaksi yang dikembalikan ke CS
                let statusElement = document.getElementById('closing_status');
                if (!statusElement) {
                    // Create status element if it doesn't exist
                    const statusDiv = document.createElement('div');
                    statusDiv.className = 'closing-detail-item';
                    statusDiv.innerHTML = `
                        <span class="closing-detail-label">Status Deposit:</span>
                        <span class="closing-detail-value" id="closing_status">-</span>
                    `;
                    document.querySelector('.closing-details').appendChild(statusDiv);
                    statusElement = document.getElementById('closing_status');
                }

                if (status === 'Dikembalikan ke CS') {
                    statusElement.textContent = '🔄 Dikembalikan ke CS';
                    statusElement.style.color = 'var(--warning-color)';
                    statusElement.style.fontWeight = 'bold';

                    // Show additional info for returned transactions
                    const returnedInfo = document.createElement('div');
                    returnedInfo.id = 'returned_info';
                    returnedInfo.className = 'alert';
                    returnedInfo.style.backgroundColor = '#fff3cd';
                    returnedInfo.style.borderColor = '#ffeaa7';
                    returnedInfo.style.color = '#856404';
                    returnedInfo.style.marginTop = '12px';
                    returnedInfo.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Perhatian:</strong> Transaksi ini dikembalikan ke CS. Pastikan untuk menggunakan nilai yang sesuai.
                    `;

                    // Remove existing returned info
                    const existingInfo = document.getElementById('returned_info');
                    if (existingInfo) existingInfo.remove();

                    closingInfo.appendChild(returnedInfo);
                } else {
                    statusElement.textContent = status;
                    statusElement.style.color = 'var(--success-color)';
                    statusElement.style.fontWeight = 'normal';

                    // Remove returned info if exists
                    const existingInfo = document.getElementById('returned_info');
                    if (existingInfo) existingInfo.remove();
                }

                closingInfo.classList.add('show');

                // Detect and show closing type
                detectClosingType(kode);

                // Validate current jumlah input
                validateJumlahClosing();
            } else {
                closingInfo.classList.remove('show');
                hideValidationMessage();
            }
        });

        // Validate jumlah for closing-linked transactions
        document.getElementById('jumlah').addEventListener('input', function() {
            if (selectedTransaksi && (
                (selectedTransaksi.nama_transaksi || '').toUpperCase() === 'DARI CLOSING' ||
                (selectedTransaksi.kode_akun || '').toUpperCase() === 'DRCLSG'
            )) {
                validateJumlahClosing();
            }
        });

        function validateJumlahClosing() {
            const nomorClosing = document.getElementById('nomor_transaksi_closing').value;
            const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
            
            if (nomorClosing && jumlah > 0) {
                const selectedOption = document.getElementById('nomor_transaksi_closing').options[document.getElementById('nomor_transaksi_closing').selectedIndex];
                const setoranClosing = parseInt(selectedOption.dataset.setoran) || 0;
                
                if (jumlah > setoranClosing) {
                    showValidationMessage(`Jumlah pemasukan (${jumlah.toLocaleString('id-ID')}) tidak boleh lebih besar dari nilai setoran transaksi closing (${setoranClosing.toLocaleString('id-ID')}). Silakan cek kembali jumlah pemasukan atau nomor transaksi closing yang dipilih.`);
                    document.getElementById('submit_btn').disabled = true;
                } else {
                    hideValidationMessage();
                    document.getElementById('submit_btn').disabled = false;
                }
            }
        }

        function showValidationMessage(message) {
            const validationDiv = document.getElementById('validation_message');
            validationDiv.textContent = message;
            validationDiv.classList.add('show');
        }

        function hideValidationMessage() {
            const validationDiv = document.getElementById('validation_message');
            validationDiv.classList.remove('show');
            document.getElementById('submit_btn').disabled = false;
        }

        // PERBAIKAN: Form validation dengan pengecekan lebih ketat
        document.getElementById('pemasukanForm').addEventListener('submit', function(e) {
            if (!selectedTransaksi || !isValidSelection) {
                e.preventDefault();
                alert('Pilih nama transaksi yang valid terlebih dahulu!');
                namaTransaksiInput.focus();
                return false;
            }
            
            // Validasi nama transaksi ada dalam master data
            const currentValue = namaTransaksiInput.value.trim();
            const isValidTransaksi = masterNamaTransaksi.some(item => 
                item.nama_transaksi.toLowerCase() === currentValue.toLowerCase()
            );
            
            if (!isValidTransaksi) {
                e.preventDefault();
                alert('Nama transaksi tidak valid! Silakan pilih dari daftar yang tersedia.');
                namaTransaksiInput.focus();
                return false;
            }
            
            const namaTransaksi = selectedTransaksi.nama_transaksi;
            const jumlah = parseInt(document.getElementById('jumlah').value);
            const nomorClosing = document.getElementById('nomor_transaksi_closing').value;
            const kodePengambilan = kodePengambilanInput ? kodePengambilanInput.value.trim().toUpperCase() : '';
            
            const selectedKodeAkun = selectedTransaksi && selectedTransaksi.kode_akun ? selectedTransaksi.kode_akun : '';
            const needsClosingReference = (namaTransaksi || '').toUpperCase() === 'DARI CLOSING'
                || (selectedKodeAkun.toUpperCase() === 'DRCLSG');
            const needsKeuanganReference = (namaTransaksi || '').toUpperCase() === 'PENGAMBILAN DANA'
                || (selectedKodeAkun.toUpperCase() === 'DRKUAN');

            if (needsClosingReference) {
                if (!nomorClosing) {
                    e.preventDefault();
                    alert('Nomor Transaksi Closing wajib dipilih untuk transaksi akun closing (DRCLSG)!');
                    return false;
                }

                const selectedOption = document.getElementById('nomor_transaksi_closing').options[document.getElementById('nomor_transaksi_closing').selectedIndex];
                const setoranClosing = parseInt(selectedOption.dataset.setoran);

                if (jumlah > setoranClosing) {
                    e.preventDefault();
                    alert(`Jumlah pemasukan (${jumlah.toLocaleString('id-ID')}) tidak boleh lebih besar dari nilai setoran transaksi closing (${setoranClosing.toLocaleString('id-ID')}). Silakan cek kembali.`);
                    return false;
                }
            }

            if (needsKeuanganReference) {
                // P8: tidak perlu validasi manual — cukup pastikan kode dipilih dari dropdown
                if (!kodePengambilan) {
                    e.preventDefault();
                    alert('Pilih kode pengambilan dana dari daftar.');
                    if (kodePengambilanInput) kodePengambilanInput.focus();
                    return false;
                }
                const keuanganData = availablePengadaanMap[kodePengambilan];
                if (!keuanganData) {
                    e.preventDefault();
                    alert('Kode pengambilan dana tidak ditemukan. Pilih dari daftar yang tersedia.');
                    return false;
                }
            }
            
            // Validate keterangan is filled
            const keterangan = document.getElementById('keterangan_transaksi').value.trim();
            if (!keterangan) {
                e.preventDefault();
                alert('Keterangan transaksi wajib diisi!');
                document.getElementById('keterangan_transaksi').focus();
                return false;
            }
            
            return true;
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                searchResults.classList.remove('show');
                currentHighlightIndex = -1;
                if (!namaTransaksiInput.readOnly) {
                    validateCurrentInput();
                }
            }
        });

        // Detect closing type automatically
        function detectClosingType(kodeTransaksi) {
            fetch('detect_closing_type.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'kode_transaksi=' + encodeURIComponent(kodeTransaksi) + '&nama_cabang=' + encodeURIComponent(<?php echo json_encode($closing_detect_nama_cabang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const detectedType = document.getElementById('detected_closing_type');
                    const closingTypeText = document.getElementById('closing_type_text');
                    
                    let typeLabel = '';
                    switch(data.jenis_closing) {
                        case 'dipinjam':
                            typeLabel = 'Dipinjam (transaksi sebelumnya dipinjam dari kasir lain)';
                            break;
                        case 'meminjam':
                            typeLabel = 'Meminjam (ada transaksi sedang berjalan dengan pemasukan DARI CLOSING)';
                            break;
                        case 'closing':
                        default:
                            typeLabel = 'Normal (closing standar)';
                            break;
                    }
                    
                    closingTypeText.textContent = typeLabel;
                    detectedType.style.display = 'block';
                } else {
                    console.error('Error detecting closing type:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Format number input
        document.getElementById('jumlah').addEventListener('input', function() {
            // Remove non-numeric characters except for the value itself
            let value = this.value.replace(/[^\d]/g, '');
            this.value = value;
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial validation state
            updateValidationState('empty');

            if (kodePengambilanInput) {
                kodePengambilanInput.addEventListener('input', function() {
                    renderKeuanganReferenceInfo();
                });
            }
        });
</script>
<script>
// Menghapus blok gambar kedua berdasarkan urutan DOM (solusi cepat)
document.addEventListener('DOMContentLoaded', function() {
  const imgs = document.querySelectorAll('img');
  if (imgs.length >= 2) {
    imgs[1].remove();
  }
});
</script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
