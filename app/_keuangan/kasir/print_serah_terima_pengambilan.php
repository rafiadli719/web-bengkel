<?php
// Cetak bukti serah terima pengambilan setoran (satu kode_pengambilan). Dibuka dari tombol Print
// di setoran_keuangan.php. Izin sama dengan halaman asal: kasir_admin.
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_admin');
require_once __DIR__ . '/includes/keuangan_pusat_helpers.php';

$kode = trim($_GET['kode'] ?? '');
if ($kode === '') { die('Kode pengambilan tidak diberikan.'); }
$stmt = mysqli_prepare($koneksi, "SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $kode);
mysqli_stmt_execute($stmt);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$p) { die('Data pengambilan tidak ditemukan.'); }

$namaInput = $p['kode_karyawan_input'];
$st = mysqli_prepare($koneksi, "SELECT nama_lengkap FROM tbuser WHERE kode_karyawan = ? LIMIT 1");
mysqli_stmt_bind_param($st, 's', $p['kode_karyawan_input']);
mysqli_stmt_execute($st);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
if ($u && $u['nama_lengkap']) { $namaInput = $u['nama_lengkap']; }

$page_title = 'Serah Terima ' . $kode;
include __DIR__ . '/includes/page_open.php';
?>
<div class="kp-card">
    <h1><i class="fas fa-file-signature"></i> Bukti Serah Terima Pengambilan Setoran</h1>
    <p class="sub">Kode pengambilan <strong><?php echo htmlspecialchars($p['kode_pengambilan']); ?></strong>
        <?php if (!empty($p['parent_kode_pengambilan'])): ?> &middot; turunan dari <?php echo htmlspecialchars($p['parent_kode_pengambilan']); ?><?php endif; ?></p>
</div>
<div class="kp-card">
    <table class="kp-table">
        <tr><th style="width:240px">Cabang Keuangan</th><td><?php echo htmlspecialchars((string)$p['nama_cabang_keuangan']); ?> (<?php echo htmlspecialchars((string)$p['kode_cabang_keuangan']); ?>)</td></tr>
        <tr><th>Diinput oleh</th><td><?php echo htmlspecialchars($namaInput); ?></td></tr>
        <tr><th>Nominal diambil</th><td><strong><?php echo kpRupiah($p['nominal_diambil']); ?></strong></td></tr>
        <tr><th>Nominal sisa</th><td><?php echo kpRupiah($p['nominal_sisa']); ?></td></tr>
        <tr><th>Klasifikasi</th><td><?php echo htmlspecialchars(ucfirst((string)$p['klasifikasi'])); ?> (rekening <?php echo htmlspecialchars((string)$p['jenis_rekening']); ?>)</td></tr>
        <tr><th>Rekening penerima</th><td><?php echo htmlspecialchars((string)$p['no_rekening_penerima']); ?> &middot; cabang <?php echo htmlspecialchars((string)$p['kode_cabang_penerima']); ?></td></tr>
        <tr><th>Rencana setor</th><td><?php echo htmlspecialchars((string)$p['tanggal_perencanaan_setor']); ?></td></tr>
        <tr><th>Tanggal penyerahan</th><td><?php echo htmlspecialchars((string)($p['tanggal_penyerahan_setor'] ?? '-')); ?></td></tr>
        <tr><th>Setor bank (riil)</th><td><?php echo htmlspecialchars((string)($p['tanggal_riil_setor_bank'] ?? '-')); ?> &middot; <?php echo htmlspecialchars((string)$p['status_setor_bank']); ?></td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars(ucfirst((string)$p['status'])); ?></td></tr>
        <tr><th>Keterangan</th><td><?php echo htmlspecialchars((string)$p['keterangan']); ?></td></tr>
    </table>
    <table class="kp-table" style="margin-top:40px">
        <tr style="text-align:center">
            <td style="width:33%"><br>Yang menyerahkan<br><br><br><br>(____________________)</td>
            <td style="width:34%"><br>Yang menerima<br><br><br><br>(____________________)</td>
            <td style="width:33%"><br>Mengetahui<br><br><br><br>(____________________)</td>
        </tr>
    </table>
    <p class="no-print" style="margin-top:20px">
        <button class="kp-btn" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
        <a class="kp-btn sec" href="setoran_keuangan.php">Kembali</a>
    </p>
</div>
<?php include __DIR__ . '/includes/page_close.php'; ?>
