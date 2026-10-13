<?php
// Detail lengkap pengeluaran kas pusat (jenis_data=pusat) dengan filter periode dan cabang.
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_admin');
require_once __DIR__ . '/includes/keuangan_pusat_helpers.php';

$tabel = kpTabel('pengeluaran');
$tgl_awal  = kpTglValid($_GET['tanggal_awal'] ?? '')  ? $_GET['tanggal_awal']  : date('Y-m-01');
$tgl_akhir = kpTglValid($_GET['tanggal_akhir'] ?? '') ? $_GET['tanggal_akhir'] : date('Y-m-d');
$cabang    = trim($_GET['cabang'] ?? '');

$sql = "SELECT pp.tanggal_transaksi, pp.waktu, pp.kode_transaksi, pp.cabang, pp.kode_akun, ma.arti AS nama_akun,
               pp.jumlah, pp.keterangan, u.nama_lengkap AS petugas
        FROM `$tabel` pp
        LEFT JOIN master_akun_closing_kasir ma ON pp.kode_akun = ma.kode_akun
        LEFT JOIN tbuser u ON pp.kode_karyawan = u.kode_karyawan
        WHERE pp.tanggal_transaksi BETWEEN ? AND ?";
$types = 'ss'; $params = [$tgl_awal, $tgl_akhir];
if ($cabang !== '') { $sql .= " AND pp.cabang = ?"; $types .= 's'; $params[] = $cabang; }
$sql .= " ORDER BY pp.tanggal_transaksi DESC, pp.waktu DESC LIMIT 1000";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = []; $total = 0.0;
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; $total += (float)$r['jumlah']; }

$cab_res = mysqli_query($koneksi, "SELECT DISTINCT cabang FROM `$tabel` WHERE cabang IS NOT NULL AND cabang <> '' ORDER BY cabang");
$cabang_list = [];
while ($c = mysqli_fetch_assoc($cab_res)) { $cabang_list[] = $c['cabang']; }

$page_title = 'Detail Pengeluaran Pusat';
include __DIR__ . '/includes/page_open.php';
?>
<div class="kp-card">
    <h1><i class="fas fa-chart-line"></i> Detail Pengeluaran Pusat</h1>
    <p class="sub">Seluruh transaksi pengeluaran kas pusat pada periode terpilih (maksimal 1.000 baris).</p>
</div>
<div class="kp-card no-print">
    <form method="get" class="kp-filter">
        <div><label for="tanggal_awal">Dari</label><input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>"></div>
        <div><label for="tanggal_akhir">Sampai</label><input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>"></div>
        <div><label for="cabang">Cabang</label>
            <select id="cabang" name="cabang"><option value="">- Semua Cabang -</option>
            <?php foreach ($cabang_list as $c): ?><option value="<?php echo htmlspecialchars($c); ?>" <?php echo $c === $cabang ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?>
            </select></div>
        <div><button type="submit" class="kp-btn"><i class="fas fa-filter"></i> Terapkan</button>
             <a href="keuangan_pusat.php" class="kp-btn sec">Kembali</a></div>
    </form>
</div>
<div class="kp-card">
<?php if (!$rows): ?>
    <div class="kp-empty"><i class="fas fa-search"></i><br>Tidak ada transaksi pada periode ini.</div>
<?php else: ?>
    <table class="kp-table">
        <thead><tr><th>Tanggal</th><th>Kode Transaksi</th><th>Cabang</th><th>Akun</th><th>Keterangan</th><th>Petugas</th><th class="kp-right">Jumlah</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?php echo htmlspecialchars($r['tanggal_transaksi']); ?></td>
                <td><?php echo htmlspecialchars($r['kode_transaksi']); ?></td>
                <td><?php echo htmlspecialchars($r['cabang']); ?></td>
                <td><?php echo htmlspecialchars($r['kode_akun'] . ' ' . ($r['nama_akun'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)$r['keterangan']); ?></td>
                <td><?php echo htmlspecialchars((string)$r['petugas']); ?></td>
                <td class="kp-right"><?php echo kpRupiah($r['jumlah']); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="kp-total"><td colspan="6" class="kp-right">Total (<?php echo count($rows); ?> transaksi)</td><td class="kp-right"><?php echo kpRupiah($total); ?></td></tr></tfoot>
    </table>
<?php endif; ?>
</div>
<?php include __DIR__ . '/includes/page_close.php'; ?>
