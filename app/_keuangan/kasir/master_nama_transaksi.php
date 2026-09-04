<?php
// Sumber: web_kasir/master_nama_transaksi.php — CRUD master nama transaksi
// (dropdown pemasukan/pengeluaran, terikat kode_akun). Gerbang session mysqli
// asli (['admin','super_admin']) diganti koneksi_kasir.php + requirePermission
// ('kasir_approve'), sama seperti master_akun.php. Query CREATE/UPDATE/DELETE
// source pakai concat string mentah (SQL injection: nama_transaksi/kode_akun/
// keterangan_default/status/id semua langsung disisip) — diganti PDO prepared
// statement, konsisten pola security-fix project (lihat commit 44b534e).
// Tabel master_nama_transaksi -> master_nama_transaksi_closing_kasir,
// master_akun -> master_akun_closing_kasir per sed map.
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$is_super_admin = ($legacy_session_kasir['role'] ?? '') === 'super_admin';
$is_admin       = ($legacy_session_kasir['role'] ?? '') === 'admin';
$username       = $nama_karyawan_aktif;
$cabang_user    = $nama_cabang_aktif;
$role           = $legacy_session_kasir['role'] ?? 'User';

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function is_duplicate_nama_transaksi(PDO $pdo, $nama_transaksi, $id = null) {
    $sql = "SELECT * FROM master_nama_transaksi_closing_kasir WHERE nama_transaksi = ?";
    $params = [$nama_transaksi];
    if ($id) {
        $sql .= " AND id != ?";
        $params[] = $id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

$success_message = '';
$error_message = '';

// CREATE (Menambah data nama transaksi baru)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $nama_transaksi = strtoupper($_POST['nama_transaksi']);
    $kode_akun = $_POST['kode_akun'];
    $keterangan_default = strtoupper($_POST['keterangan_default']);

    if (is_duplicate_nama_transaksi($pdo, $nama_transaksi)) {
        $error_message = "Error: Nama transaksi '$nama_transaksi' sudah ada!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO master_nama_transaksi_closing_kasir (nama_transaksi, kode_akun, keterangan_default)
                    VALUES (?, ?, ?)");
            $stmt->execute([$nama_transaksi, $kode_akun, $keterangan_default]);
            $success_message = "Data nama transaksi berhasil ditambahkan!";
            header("Refresh: 2; URL=master_nama_transaksi.php");
        } catch (Throwable $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// UPDATE (Mengupdate data nama transaksi)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $nama_transaksi = strtoupper($_POST['nama_transaksi']);
    $kode_akun = $_POST['kode_akun'];
    $keterangan_default = strtoupper($_POST['keterangan_default']);
    $status = $_POST['status'];

    if (is_duplicate_nama_transaksi($pdo, $nama_transaksi, $id)) {
        $error_message = "Error: Nama transaksi '$nama_transaksi' sudah ada!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE master_nama_transaksi_closing_kasir
                    SET nama_transaksi = ?, kode_akun = ?, keterangan_default = ?, status = ?
                    WHERE id = ?");
            $stmt->execute([$nama_transaksi, $kode_akun, $keterangan_default, $status, $id]);
            $success_message = "Data nama transaksi berhasil diupdate!";
            header("Refresh: 2; URL=master_nama_transaksi.php");
        } catch (Throwable $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// DELETE (Menghapus data nama transaksi)
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM master_nama_transaksi_closing_kasir WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Data nama transaksi berhasil dihapus!";
        header("Refresh: 2; URL=master_nama_transaksi.php");
    } catch (Throwable $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// FETCH ALL DATA
$stmt = $pdo->query("SELECT mnt.*, ma.arti, ma.jenis_akun
        FROM master_nama_transaksi_closing_kasir mnt
        JOIN master_akun_closing_kasir ma ON mnt.kode_akun = ma.kode_akun
        ORDER BY ma.jenis_akun, mnt.nama_transaksi");
$rows_all = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH MASTER AKUN untuk dropdown (PEMASUKAN DAN PENGELUARAN)
$stmt_akun = $pdo->query("SELECT * FROM master_akun_closing_kasir ORDER BY jenis_akun, kode_akun");
$master_akun = $stmt_akun->fetchAll(PDO::FETCH_ASSOC);

// FETCH ONE DATA FOR EDIT
$edit = false;
if (isset($_GET['edit'])) {
    $edit = true;
    $id = (int) $_GET['edit'];
    $stmt_edit = $pdo->prepare("SELECT * FROM master_nama_transaksi_closing_kasir WHERE id = ?");
    $stmt_edit->execute([$id]);
    $row = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Nama Transaksi - Admin Dashboard</title>
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
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
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
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .form-card h3 {
            margin-bottom: 20px;
            color: var(--text-dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 14px;
        }
        .form-control {
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: white;
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
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid transparent;
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
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
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
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }
        .table-header {
            background: var(--background-light);
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
        }
        .table-header h3 {
            margin: 0;
            color: var(--text-dark);
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
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
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-active {
            background: rgba(40,167,69,0.1);
            color: var(--success-color);
        }
        .status-inactive {
            background: rgba(220,53,69,0.1);
            color: var(--danger-color);
        }
        .jenis-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .jenis-pemasukan {
            background: rgba(40,167,69,0.1);
            color: var(--success-color);
        }
        .jenis-pengeluaran {
            background: rgba(220,53,69,0.1);
            color: var(--danger-color);
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
        .text-required {
            color: var(--danger-color);
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
                padding: 20px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .info-tags {
                flex-direction: column;
                gap: 10px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="user-profile">
        <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <div>
            <strong><?php echo htmlspecialchars($username); ?></strong>
            <p style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars(ucfirst($role)); ?></p>
        </div>
    </div>

    <div class="welcome-card">
        <h1><i class="fas fa-file-signature"></i> Master Nama Transaksi</h1>
        <p style="color: var(--text-muted); margin-bottom: 0;">Kelola master nama transaksi untuk input pemasukan dan pengeluaran kasir</p>
        <div class="info-tags">
            <div class="info-tag"><i class="fas fa-user"></i> User: <?php echo htmlspecialchars($username); ?></div>
            <div class="info-tag"><i class="fas fa-shield-alt"></i> Role: <?php echo htmlspecialchars(ucfirst($role)); ?></div>
            <div class="info-tag"><i class="fas fa-calendar-day"></i> Tanggal: <?php echo date('d M Y'); ?></div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="form-card">
        <h3>
            <i class="fas fa-<?php echo $edit ? 'edit' : 'plus'; ?>"></i>
            <?php echo $edit ? 'Edit Nama Transaksi' : 'Tambah Nama Transaksi'; ?>
        </h3>
        <form action="" method="POST">
            <input type="hidden" name="id" value="<?php echo $edit ? $row['id'] : ''; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nama_transaksi" class="form-label">
                        <i class="fas fa-signature"></i> Nama Transaksi <span class="text-required">*</span>
                    </label>
                    <input type="text" name="nama_transaksi" class="form-control" required
                           value="<?php echo $edit ? $row['nama_transaksi'] : ''; ?>"
                           placeholder="Contoh: TRANSFER, KOMPRESOR, HVS, JUAL SPAREPART"
                           oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="form-group">
                    <label for="kode_akun" class="form-label">
                        <i class="fas fa-code"></i> Kode Akun <span class="text-required">*</span>
                    </label>
                    <select name="kode_akun" class="form-control" required>
                        <option value="">-- Pilih Kode Akun --</option>
                        <?php
                        $current_jenis = '';
                        foreach ($master_akun as $akun):
                            if ($current_jenis !== $akun['jenis_akun']) {
                                if ($current_jenis !== '') echo '</optgroup>';
                                echo '<optgroup label="' . strtoupper($akun['jenis_akun']) . '">';
                                $current_jenis = $akun['jenis_akun'];
                            }
                        ?>
                            <option value="<?php echo $akun['kode_akun']; ?>"
                                    <?php echo ($edit && $row['kode_akun'] === $akun['kode_akun']) ? 'selected' : ''; ?>>
                                <?php echo $akun['kode_akun'] . ' - ' . $akun['arti']; ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_jenis !== '') echo '</optgroup>'; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="keterangan_default" class="form-label">
                        <i class="fas fa-sticky-note"></i> Keterangan Default
                    </label>
                    <input type="text" name="keterangan_default" class="form-control"
                           value="<?php echo $edit ? $row['keterangan_default'] : ''; ?>"
                           placeholder="Contoh: PEMBAYARAN LEWAT TRANSFER - PLAT NOMOR: , PENJUALAN SPAREPART - CUSTOMER: "
                           oninput="this.value = this.value.toUpperCase()">
                    <small style="color: var(--text-muted); font-size: 12px;">
                        Keterangan ini akan otomatis muncul saat nama transaksi dipilih
                    </small>
                </div>
                <?php if ($edit): ?>
                <div class="form-group">
                    <label for="status" class="form-label">
                        <i class="fas fa-toggle-on"></i> Status
                    </label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo ($edit && $row['status'] === 'active') ? 'selected' : ''; ?>>ACTIVE</option>
                        <option value="inactive" <?php echo ($edit && $row['status'] === 'inactive') ? 'selected' : ''; ?>>INACTIVE</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="<?php echo $edit ? 'update' : 'create'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit ? 'Update' : 'Tambah'; ?>
                </button>
                <?php if ($edit): ?>
                    <a href="master_nama_transaksi.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-table"></i> Data Master Nama Transaksi</h3>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Transaksi</th>
                    <th>Kode Akun</th>
                    <th>Jenis</th>
                    <th>Arti Akun</th>
                    <th>Keterangan Default</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($rows_all) > 0) {
                    $no = 1;
                    foreach ($rows_all as $row) {
                        $status_class = strtolower($row['status']);
                        $jenis_class = strtolower($row['jenis_akun']);

                        echo "<tr>";
                        echo "<td><strong>$no</strong></td>";
                        echo "<td style='font-weight: 600;'>" . htmlspecialchars($row['nama_transaksi']) . "</td>";
                        echo "<td><code>" . htmlspecialchars($row['kode_akun']) . "</code></td>";
                        echo "<td><span class='jenis-badge jenis-$jenis_class'>" . htmlspecialchars($row['jenis_akun']) . "</span></td>";
                        echo "<td>" . htmlspecialchars($row['arti']) . "</td>";
                        echo "<td style='font-size: 12px;'>" . htmlspecialchars($row['keterangan_default'] ?? '-') . "</td>";
                        echo "<td><span class='status-badge status-$status_class'>" . htmlspecialchars($row['status']) . "</span></td>";
                        echo "<td class='action-buttons'>
                            <a href='?edit={$row['id']}' class='btn btn-warning btn-sm' title='Edit'>
                                <i class='fas fa-edit'></i>
                            </a>
                            <a href='?delete={$row['id']}' class='btn btn-danger btn-sm' title='Hapus'
                               onclick=\"return confirm('Yakin ingin menghapus transaksi {$row['nama_transaksi']}?');\">
                                <i class='fas fa-trash'></i>
                            </a>
                        </td>";
                        echo "</tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='8' class='no-data'><i class='fas fa-inbox'></i><br>Belum ada data nama transaksi</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Adjust sidebar width based on content
    function adjustSidebarWidth() {
        const sidebar = document.getElementById('sidebar');
        const links = sidebar.getElementsByTagName('a');
        let maxWidth = 0;

        for (let link of links) {
            link.style.whiteSpace = 'nowrap';
            const width = link.getBoundingClientRect().width;
            if (width > maxWidth) {
                maxWidth = width;
            }
        }

        const minWidth = 250;
        sidebar.style.width = maxWidth > minWidth ? `${maxWidth + 30}px` : `${minWidth}px`;
        document.querySelector('.main-content').style.marginLeft = sidebar.style.width;
    }

    // Run on page load and window resize
    window.addEventListener('load', adjustSidebarWidth);
    window.addEventListener('resize', adjustSidebarWidth);

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    });
</script>

</body>
</html>
