<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Koneksi & RBAC fitmotor (gantiin session role-check + config.php lama) —
// requirePermission('kasir_menu_read') sudah jalan di dalam sini, dan
// $kode_cabang_aktif/$kode_karyawan_aktif sudah resolved dari tbuser/tbcabang.
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_operate');

// Query di bawah masih pakai PDO persis source asli (gak direwrite ke mysqli,
// biar logic INSERT/SELECT full preserved) — koneksi PDO terpisah ke DB yang sama.
$dsn = 'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel');
try {
    $pdo = new PDO($dsn, getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

require_once __DIR__ . '/closing_revision_helpers.php';

$kode_karyawan = $kode_karyawan_aktif;

// Handle transaksi yang dibuka untuk diedit dari dashboard
$kode_transaksi_edit = null;
$transaksi_data_edit = null;

if (isset($_GET['kode_transaksi'])) {
    $kode_transaksi_edit = $_GET['kode_transaksi'];
    
    // Ambil data transaksi yang akan diedit
    $sql_edit = "SELECT * FROM kasir_transactions_closing_kasir WHERE kode_transaksi = :kode_transaksi AND status = 'on proses'";
    $stmt_edit = $pdo->prepare($sql_edit);
    $stmt_edit->bindParam(':kode_transaksi', $kode_transaksi_edit, PDO::PARAM_STR);
    $stmt_edit->execute();
    $transaksi_data_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    
    if ($transaksi_data_edit && userCanAccessTransaction($pdo, $legacy_session_kasir, $transaksi_data_edit)) {
        // Set session untuk melanjutkan transaksi yang sudah ada
        $_SESSION['selected_date'] = $transaksi_data_edit['tanggal_transaksi'];
        $_SESSION['kas_awal'] = $transaksi_data_edit['kas_awal']; // kolom asli kasir_transactions_closing_kasir tetap 'kas_awal', bukan ikut suffix tabel
        $_SESSION['kode_transaksi_edit'] = $kode_transaksi_edit;
    } else {
        $transaksi_data_edit = null;
    }
}

// Data cabang aktif sudah resolved otoritatif dari koneksi_kasir.php (tbuser/tbcabang)
$kode_cabang = $kode_cabang_aktif;
$nama_cabang = $nama_cabang_aktif;

// kode_user (dulu web_kasir.users.kode_user, 3 huruf) = tbuser.nama_user hasil migrasi Task 1
$kode_user = $rowUser['nama_user'] ?? null;
if (!$kode_user) {
    $sql_kode_user = "SELECT nama_user FROM tbuser WHERE kode_karyawan = :kode_karyawan";
    $stmt_kode_user = $pdo->prepare($sql_kode_user);
    $stmt_kode_user->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);
    $stmt_kode_user->execute();
    $kode_user = $stmt_kode_user->fetchColumn();
}

// Ensure kode_user exists
if (!$kode_user) {
    die("Error: User code (kode_user) not found for karyawan: $kode_karyawan");
}

// ** Part 1: Verify Starting Cash and Block Dates with Existing Transactions **
if (!isset($_SESSION['selected_date'])) {
    // Fetch dates with existing transactions for this user and branch
    $sql_existing_dates = "SELECT DISTINCT DATE(tanggal_transaksi) as tanggal_transaksi 
                           FROM kasir_transactions_closing_kasir WHERE kode_karyawan = :kode_karyawan AND kode_cabang = :kode_cabang";
    $stmt_existing_dates = $pdo->prepare($sql_existing_dates);
    $stmt_existing_dates->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);
    $stmt_existing_dates->bindParam(':kode_cabang', $kode_cabang, PDO::PARAM_STR);
    $stmt_existing_dates->execute();
    $existing_dates = $stmt_existing_dates->fetchAll(PDO::FETCH_COLUMN);

    $username = $nama_karyawan_aktif ?? 'Unknown User';
    $cabang = $nama_cabang ?? 'Unknown Cabang';

    // Show initial cash verification page if no date selected
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Starting Cash</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            /* CSS styles sama seperti sebelumnya tetapi dengan tambahan untuk branch info */
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
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 16px;
                padding: 32px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                border: 1px solid var(--border-color);
            }
            .form-group {
                margin-bottom: 24px;
            }
            .form-label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: var(--text-dark);
                font-size: 14px;
            }
            .form-control {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                font-size: 16px;
                transition: all 0.3s ease;
                background: white;
            }
            .form-control:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            }
            .btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                justify-content: center;
            }
            .btn-primary {
                background: linear-gradient(135deg, var(--primary-color), #0056b3);
                color: white;
                box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            }
            .instruction-box {
                background: var(--background-light);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 24px;
                border-left: 4px solid var(--primary-color);
            }
            .instruction-box p {
                color: var(--text-muted);
                margin: 0;
                font-size: 14px;
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
            .branch-info {
                background: linear-gradient(135deg, var(--success-color), #20c997);
                color: white;
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 24px;
                text-align: center;
            }
            .branch-info h3 {
                margin: 0 0 8px 0;
                font-size: 18px;
            }
            .branch-info p {
                margin: 0;
                opacity: 0.9;
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
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var dateInput = document.getElementById('selected_date');
                var blockedDates = <?php echo json_encode($existing_dates); ?>;

                dateInput.addEventListener('input', function() {
                    var selectedDate = this.value;
                    var today = new Date().toISOString().split('T')[0];
                    
                    if (selectedDate < today) {
                        alert('Tidak dapat memilih tanggal yang sudah lewat.');
                        this.value = '';
                        return;
                    }
                    
                    if (blockedDates.includes(selectedDate)) {
                        alert('Tanggal yang Anda pilih sudah memiliki kas awal untuk cabang ini. Silakan pilih tanggal lain.');
                        this.value = '';
                    }
                });
            });
        </script>
    </head>
    <body>
        <div class="sidebar" id="sidebar">
            <a href="index_kasir.php"><i class="fas fa-tachometer-alt"></i> Dashboard Kasir</a>
            <a href="serah_terima.php"><i class="fas fa-handshake"></i> Serah Terima Kasir</a>
            <a href="setoran_keuangan_cs.php"><i class="fas fa-money-bill"></i> Setoran Keuangan CS</a>
            <button class="logout-btn" onclick="window.location.href='logout.php';"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>

        <div class="main-content">
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo htmlspecialchars($cabang); ?>)
                    <p style="color: var(--text-muted); font-size: 12px;">Kasir</p>
                </div>
            </div>

            <div class="breadcrumb">
                <a href="index_kasir.php"><i class="fas fa-home"></i> Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Verifikasi Kas Awal</span>
            </div>

            <div class="page-header">
                <h1><i class="fas fa-money-check-alt"></i> Verify Starting Cash</h1>
                <p class="subtitle">Pilih tanggal untuk memulai transaksi kas awal baru</p>
            </div>

            <!-- Branch Information -->
            <div class="branch-info">
                <h3><i class="fas fa-building"></i> Cabang Aktif</h3>
                <p><?php echo htmlspecialchars($kode_cabang); ?> - <?php echo htmlspecialchars($nama_cabang); ?></p>
            </div>

            <div class="container">
                <div class="instruction-box">
                    <p><i class="fas fa-info-circle"></i> Anda tidak dapat memilih tanggal yang sudah memiliki transaksi di cabang yang sama.</p>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="selected_date" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Pilih Tanggal
                        </label>
                        <input type="date" 
                               id="selected_date" 
                               name="selected_date" 
                               class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-arrow-right"></i> Lanjutkan
                    </button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// PERBAIKAN: Validasi selected_date dan branch data
$tanggal_transaksi_baru = $_POST['selected_date'] ?? $_SESSION['selected_date'];

// Validasi tanggal tidak kosong
if (empty($tanggal_transaksi_baru)) {
    header('Location: verifikasi_kas_awal.php');
    exit;
}

// Validasi tanggal tidak masa lampau
if ($tanggal_transaksi_baru < date('Y-m-d')) {
    $_SESSION['transaksi_error'] = "Tidak dapat memilih tanggal yang sudah lewat.";
    header('Location: verifikasi_kas_awal.php');
    exit;
}

$_SESSION['selected_date'] = $tanggal_transaksi_baru;

// $kode_cabang/$nama_cabang sudah resolved otoritatif tiap request lewat
// koneksi_kasir.php (query langsung tbuser/tbcabang) — cache validated_branch
// ala session_helper.php lama gak diperlukan lagi, dihapus.

// ** Part 2: Process New Transaction Creation **
// Cek jumlah transaksi pada tanggal yang sama dan cabang yang sama
$sql_count_transaksi = "SELECT COUNT(*) as total FROM kasir_transactions_closing_kasir WHERE tanggal_transaksi = :tanggal_transaksi AND kode_cabang = :kode_cabang";
$stmt_count = $pdo->prepare($sql_count_transaksi);
$stmt_count->bindParam(':tanggal_transaksi', $tanggal_transaksi_baru, PDO::PARAM_STR);
$stmt_count->bindParam(':kode_cabang', $kode_cabang, PDO::PARAM_STR);
$stmt_count->execute();
$total_transaksi_hari_ini = $stmt_count->fetchColumn();

// Generate initial transaction code
$year = date('Y', strtotime($tanggal_transaksi_baru));
$month = date('m', strtotime($tanggal_transaksi_baru));
$day = date('d', strtotime($tanggal_transaksi_baru));
$transaction_number = str_pad($total_transaksi_hari_ini + 1, 4, '0', STR_PAD_LEFT);
$kode_transaksi = "TRX-$year$month$day-$kode_user$transaction_number";

// Check for duplicate and adjust transaction number if necessary
while (true) {
    $sql_check_duplicate = "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE kode_transaksi = :kode_transaksi";
    $stmt_check_duplicate = $pdo->prepare($sql_check_duplicate);
    $stmt_check_duplicate->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
    $stmt_check_duplicate->execute();
    $exists = $stmt_check_duplicate->fetchColumn();

    if ($exists == 0) {
        break;
    }

    $transaction_number = str_pad((int)$transaction_number + 1, 4, '0', STR_PAD_LEFT);
    $kode_transaksi = "TRX-$year$month$day-$kode_user$transaction_number";
}

// ** Part 3: Starting Cash and Cash Entry Processing **
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kas_awal'])) {
    $kas_awal = isset($_POST['kas_awal']) ? $_POST['kas_awal'] : 0;
    $waktu = '08:00:00'; // Default time set to 8:00 AM

    $kepingFilled = false;
    if (isset($_POST['keping_closing_kasir']) && is_array($_POST['keping_closing_kasir'])) {
        foreach ($_POST['keping_closing_kasir'] as $nominal => $jumlah_keping) {
            if (!is_numeric($jumlah_keping) || $jumlah_keping < 0 || floor($jumlah_keping) != $jumlah_keping) {
                $error_message = "Number of coins must be a non-negative integer.";
                break;
            }
            if ($jumlah_keping > 0) {
                $kepingFilled = true;
            }
        }
    }

    if ($kas_awal == 0 || !$kepingFilled) {
        $error_message = "Total starting cash cannot be 0, and at least one coin entry must be filled.";
    } else {
        try {
            $pdo->beginTransaction();

            // Branch data sudah divalidasi otoritatif via koneksi_kasir.php (die() di sana
            // kalau invalid) — cukup pastikan masih ada isinya sebelum insert.
            if (empty($kode_cabang) || empty($nama_cabang)) {
                throw new Exception("Data cabang tidak valid saat membuat transaksi. Kode: " . ($kode_cabang ?? 'NULL') . ", Nama: " . ($nama_cabang ?? 'NULL'));
            }
            $current_branch = ['kode_cabang' => $kode_cabang, 'nama_cabang' => $nama_cabang];

            // Insert into kas_awal table
            $sql_kas_awal = "INSERT INTO kas_awal (kode_transaksi, kode_karyawan, total_nilai, tanggal, waktu) 
                             VALUES (:kode_transaksi, :kode_karyawan, :total_nilai, :tanggal, :waktu)";
            $stmt_kas_awal = $pdo->prepare($sql_kas_awal);
            $stmt_kas_awal->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
            $stmt_kas_awal->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);
            $stmt_kas_awal->bindParam(':total_nilai', $kas_awal, PDO::PARAM_STR);
            $stmt_kas_awal->bindParam(':tanggal', $tanggal_transaksi_baru, PDO::PARAM_STR);
            $stmt_kas_awal->bindParam(':waktu', $waktu, PDO::PARAM_STR);
            $stmt_kas_awal->execute();

            // Insert coin details if provided
            if (isset($_POST['keping_closing_kasir']) && is_array($_POST['keping_closing_kasir'])) {
                foreach ($_POST['keping_closing_kasir'] as $nominal => $jumlah_keping) {
                    if ($jumlah_keping > 0) {
                        $sql_detail_kas_awal = "INSERT INTO detail_kas_awal (kode_transaksi, nominal, jumlah_keping) 
                                                VALUES (:kode_transaksi, :nominal, :jumlah_keping)";
                        $stmt_detail = $pdo->prepare($sql_detail_kas_awal);
                        $stmt_detail->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
                        $stmt_detail->bindParam(':nominal', $nominal, PDO::PARAM_INT);
                        $stmt_detail->bindParam(':jumlah_keping', $jumlah_keping, PDO::PARAM_INT);
                        $stmt_detail->execute();
                    }
                }
            }

            // PERBAIKAN: Insert into kasir_transactions_closing_kasir table dengan validasi branch yang ketat
            $sql_trans = "INSERT INTO kasir_transactions_closing_kasir (kode_karyawan, kode_transaksi, kas_awal, tanggal_transaksi, status, kode_cabang, nama_cabang)
                          VALUES (:kode_karyawan, :kode_transaksi, :kas_awal, :tanggal_transaksi, 'on proses', :kode_cabang, :nama_cabang)";
            $stmt_trans = $pdo->prepare($sql_trans);
            $stmt_trans->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);
            $stmt_trans->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
            $stmt_trans->bindParam(':kas_awal', $kas_awal, PDO::PARAM_STR);
            $stmt_trans->bindParam(':tanggal_transaksi', $tanggal_transaksi_baru, PDO::PARAM_STR);
            $stmt_trans->bindParam(':kode_cabang', $current_branch['kode_cabang'], PDO::PARAM_STR);
            $stmt_trans->bindParam(':nama_cabang', $current_branch['nama_cabang'], PDO::PARAM_STR);
            $stmt_trans->execute();

            $pdo->commit();

            $_SESSION['kode_transaksi'] = $kode_transaksi;
            
            // Clear temporary session data
            unset($_SESSION['selected_date']);
            unset($_SESSION['validated_branch']);
            
            header("Location: index_kasir.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollback();
            $error_message = "Error: " . $e->getMessage();
            
            // Log error
            error_log("Transaction creation failed for karyawan $kode_karyawan: " . $e->getMessage());
        }
    }
}

// Fetch coin denominations
$sql_keping = "SELECT nominal FROM keping_closing_kasir ORDER BY nominal DESC";
$stmt_keping = $pdo->query($sql_keping);
$keping_data = $stmt_keping->fetchAll(PDO::FETCH_ASSOC);

$username = $nama_karyawan_aktif ?? 'Unknown User';
$cabang = $nama_cabang ?? 'Unknown Cabang';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kas Awal Kasir</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS styles sama seperti sebelumnya */
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
        .date-info {
            background: var(--background-light);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        .date-info p {
            margin: 0;
            font-weight: 600;
            color: var(--text-dark);
        }
        .transaction-code {
            background: var(--background-light);
            padding: 8px 16px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 12px;
            font-weight: 600;
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
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background: var(--background-light);
            padding: 16px;
            text-align: center;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }
        .table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            text-align: center;
        }
        .table tbody tr:hover {
            background: var(--background-light);
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-control[readonly] {
            background: var(--background-light);
            color: var(--text-muted);
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 14px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
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
        .total-section {
            background: var(--background-light);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 2px solid var(--primary-color);
        }
        .total-section .form-control {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            color: var(--primary-color);
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
        .branch-info {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        }
        .branch-info h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
        }
        .branch-info p {
            margin: 0;
            opacity: 0.9;
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
        <a href="serah_terima.php"><i class="fas fa-handshake"></i> Serah Terima Kasir</a>
        <a href="setoran_keuangan_cs.php"><i class="fas fa-money-bill"></i> Setoran Keuangan CS</a>
        <button class="logout-btn" onclick="window.location.href='logout.php';"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>

    <div class="main-content">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo htmlspecialchars($cabang); ?>)
                <p style="color: var(--text-muted); font-size: 12px;">Kasir</p>
            </div>
        </div>

        <div class="breadcrumb">
            <a href="index_kasir.php"><i class="fas fa-home"></i> Dashboard</a>
            <i class="fas fa-chevron-right"></i>
            <a href="verifikasi_kas_awal.php">Verifikasi Kas Awal</a>
            <i class="fas fa-chevron-right"></i>
            <span>Input Kas Awal</span>
        </div>

        <div class="page-header">
            <h1><i class="fas fa-coins"></i> Kas Awal Kasir</h1>
            <p class="subtitle">Perhitungan Keping dan Total Nilai</p>
            <div class="transaction-code">
                <i class="fas fa-receipt"></i> <?php echo htmlspecialchars($kode_transaksi); ?>
            </div>
        </div>

        <!-- Branch Information -->
        <div class="branch-info">
            <h3><i class="fas fa-building"></i> Cabang Terpilih</h3>
            <p><?php echo htmlspecialchars($kode_cabang); ?> - <?php echo htmlspecialchars($nama_cabang); ?></p>
        </div>

        <div class="date-info">
            <p><i class="fas fa-calendar-alt"></i> Tanggal Transaksi: <strong><?php echo htmlspecialchars($tanggal_transaksi_baru); ?></strong></p>
        </div>

        <!-- Notifikasi Transaksi yang Dibuka untuk Edit -->
        <?php if (isset($_SESSION['kode_transaksi_edit'])): ?>
        <div style="background: #e8f5e8; border: 2px solid var(--success-color); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <i class="fas fa-unlock-alt" style="color: var(--success-color); font-size: 20px; margin-right: 10px;"></i>
                <h4 style="color: var(--success-color); margin: 0;">Melanjutkan Transaksi yang Dibuka</h4>
            </div>
            <p style="margin: 0; color: var(--text-dark); font-size: 14px;">
                <strong>Kode Transaksi:</strong> <?php echo htmlspecialchars($_SESSION['kode_transaksi_edit']); ?><br>
                <strong>Status:</strong> Transaksi ini telah dikonfirmasi oleh Super Admin untuk dibuka kembali dan dapat diedit.<br>
                <strong>Catatan:</strong> Anda dapat melanjutkan input data transaksi ini. Pastikan semua data terisi dengan benar sebelum menyelesaikan transaksi.
            </p>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="kasAwalForm">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-money-bill"></i> NOMINAL</th>
                            <th>×</th>
                            <th><i class="fas fa-coins"></i> KEPING</th>
                            <th>=</th>
                            <th><i class="fas fa-calculator"></i> TOTAL NILAI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keping_data as $row): 
                            $nominal = $row['nominal'];
                            $id = "keping_" . $nominal;
                        ?>
                            <tr>
                                <td style="font-weight: 600;">Rp <?php echo number_format($nominal, 0, ',', '.'); ?></td>
                                <td>×</td>
                                <td>
                                    <input type="number" 
                                           id="<?php echo $id; ?>" 
                                           name="keping_closing_kasir[<?php echo $nominal; ?>]" 
                                           class="form-control" 
                                           value="" 
                                           oninput="hitungTotal('<?php echo $nominal; ?>')" 
                                           min="0" 
                                           step="1"
                                           placeholder="0">
                                </td>
                                <td>=</td>
                                <td>
                                    <input type="text" 
                                           id="total_<?php echo $nominal; ?>" 
                                           class="form-control" 
                                           value="Rp 0" 
                                           readonly>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="form-group">
                    <label for="total_nilai" class="form-label">
                        <i class="fas fa-wallet"></i> Total Kas Awal
                    </label>
                    <input type="text" 
                           id="kas_awal_display" 
                           class="form-control" 
                           value="Rp 0" 
                           readonly>
                    <input type="hidden" 
                           id="kas_awal" 
                           name="kas_awal" 
                           value="0">
                </div>
            </div>

            <button type="submit" class="btn btn-success" style="width: 100%;">
                <i class="fas fa-save"></i> Simpan Kas Awal
            </button>
        </form>
    </div>

    <script>
        function hitungKasAwal() {
            var totalKasAwal = 0;

            <?php foreach ($keping_data as $row): 
                $nominal = $row['nominal']; ?>
                var keping_<?php echo $nominal; ?> = document.getElementById('keping_<?php echo $nominal; ?>').value || 0;
                totalKasAwal += <?php echo $nominal; ?> * parseInt(keping_<?php echo $nominal; ?>);
            <?php endforeach; ?>

            var totalFormatted = "Rp " + totalKasAwal.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            document.getElementById('kas_awal_display').value = totalFormatted;
            document.getElementById('kas_awal').value = totalKasAwal;
        }

        function hitungTotal(nominal) {
            var kepingInput = document.getElementById('keping_' + nominal);
            var keping_closing_kasir = kepingInput.value;

            // Validasi input
            if (keping_closing_kasir < 0 || keping_closing_kasir % 1 !== 0) {
                alert('Jumlah keping_closing_kasir harus berupa bilangan bulat positif.');
                kepingInput.value = 0;
                keping_closing_kasir = 0;
            }

            var totalNilai = nominal * parseInt(keping_closing_kasir);

            var totalFormatted = "Rp " + totalNilai.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            document.getElementById('total_' + nominal).value = totalFormatted;
            hitungKasAwal();
        }

        // Form validation sebelum submit
        document.getElementById('kasAwalForm').addEventListener('submit', function(e) {
            var kasAwal = parseInt(document.getElementById('kas_awal').value);
            
            if (kasAwal <= 0) {
                e.preventDefault();
                alert('Total kas awal harus lebih dari 0. Silakan isi setidaknya satu keping_closing_kasir.');
                return;
            }

            if (!confirm('Anda yakin ingin menyimpan kas awal sebesar Rp ' + kasAwal.toLocaleString('id-ID') + '?')) {
                e.preventDefault();
            }
        });

        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            hitungKasAwal();
        });
    </script>
</body>
</html>
