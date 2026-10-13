<?php
// Histori pengambilan setoran yang tercatat masuk ke pemasukan gudang (id_pemasukan_gudang) dan
// semua pengambilan berklasifikasi hutang. Dibuka dari tab Histori Hutang di setoran_keuangan.php.
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_admin');
require_once __DIR__ . '/includes/keuangan_pusat_helpers.php';

$tgl_awal  = kpTglValid($_GET['tanggal_awal'] ?? '')  ? $_GET['tanggal_awal']  : date('Y-m-01', strtotime('-2 months'));
$tgl_akhir = kpTglValid($_GET['tanggal_akhir'] ?? '') ? $_GET['tanggal_akhir'] : date('Y-m-d');

$sql = "SELECT ps.kode_pengambilan, ps.tanggal_perencanaan_setor, ps.nama_cabang_keuangan, ps.nominal_diambil,
               ps.klasifikasi, ps.status, ps.id_pemasukan_gudang, pp.kode_transaksi AS kode_pemasukan,
               pp.jumlah AS jumlah_pemasukan, pp.tanggal_transaksi AS tgl_pemasukan
        FROM pengambilan_setoran_closing_kasir ps
        LEFT JOIN pemasukan_pusat_closing_kasir pp ON pp.id = ps.id_pemasukan_gudang
        WHERE (ps.id_pemasukan_gudang IS NOT NULL OR ps.klasifikasi = 'hutang')
          AND ps.tanggal_perencanaan_setor BETWEEN ? AND ?
        ORDER BY ps.tanggal_perencanaan_setor DESC, ps.id DESC LIMIT 500";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $tgl_awal, $tgl_akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = []; $total = 0.0;
while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; $total += (float)$r['nominal_diambil']; }

$page_title = 'Histori Gudang';
include __DIR__ . '/includes/page_open.php';
?>
<div class="kp-card">
    <h1><i class="fas fa-warehouse"></i> Histori Gudang</h1>
    <p class="sub">Pengambilan setoran yang masuk ke pemasukan gudang atau berklasifikasi hutang (maksimal 500 baris).</p>
</div>
<div class="kp-card no-print">
    <form method="get" class="kp-filter">
        <div><label for="tanggal_awal">Rencana setor dari</label><input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>"></div>
        <div><label for="tanggal_akhir">Sampai</label><input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>"></div>
        <div><button type="submit" class="kp-btn"><i class="fas fa-filter"></i> Terapkan</button>
             <a href="setoran_keuangan.php" class="kp-btn sec">Kembali</a></div>
    </form>
</div>
<div class="kp-card">
<?php if (!$rows): ?>
    <div class="kp-empty"><i class="fas fa-warehouse"></i><br>Belum ada histori pada periode ini.</div>
<?php else: ?>
    <table class="kp-table">
        <thead><tr><th>Kode Pengambilan</th><th>Rencana Setor</th><th>Cabang Keuangan</th><th>Klasifikasi</th><th>Status</th><th>Pemasukan Gudang</th><th class="kp-right">Nominal Diambil</th><th class="no-print"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?php echo htmlspecialchars($r['kode_pengambilan']); ?></td>
                <td><?php echo htmlspecialchars((string)$r['tanggal_perencanaan_setor']); ?></td>
                <td><?php echo htmlspecialchars((string)$r['nama_cabang_keuangan']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst((string)$r['klasifikasi'])); ?></td>
                <td><?php echo htmlspecialchars(ucfirst((string)$r['status'])); ?></td>
                <td><?php echo $r['kode_pemasukan'] ? htmlspecialchars($r['kode_pemasukan'] . ' (' . kpRupiah($r['jumlah_pemasukan']) . ', ' . $r['tgl_pemasukan'] . ')') : '<span style="color:#94a3b8">belum tercatat</span>'; ?></td>
                <td class="kp-right"><?php echo kpRupiah($r['nominal_diambil']); ?></td>
                <td class="no-print"><a href="print_serah_terima_pengambilan.php?kode=<?php echo urlencode($r['kode_pengambilan']); ?>" target="_blank"><i class="fas fa-print"></i></a></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="kp-total"><td colspan="6" class="kp-right">Total (<?php echo count($rows); ?> pengambilan)</td><td class="kp-right"><?php echo kpRupiah($total); ?></td><td class="no-print"></td></tr></tfoot>
    </table>
<?php endif; ?>
</div>
<?php include __DIR__ . '/includes/page_close.php'; ?>
