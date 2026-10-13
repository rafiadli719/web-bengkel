<?php
// Edit transaksi keuangan pusat: tanggal transaksi, jumlah, keterangan (+ umur pakai untuk pengeluaran).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_admin');
require_once __DIR__ . '/includes/keuangan_pusat_helpers.php';

$jenis = $_GET['jenis'] ?? ($_POST['jenis'] ?? '');
$id    = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$tabel = kpTabel($jenis);
if ($id <= 0 || $tabel === null) {
    die('Parameter tidak valid.');
}

$pesan_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl    = $_POST['tanggal_transaksi'] ?? '';
    $jumlah = str_replace(['.', ','], ['', '.'], trim($_POST['jumlah'] ?? ''));
    $ket    = trim($_POST['keterangan'] ?? '');
    if (!kpTglValid($tgl)) {
        $pesan_err = 'Tanggal transaksi tidak valid.';
    } elseif (!is_numeric($jumlah) || (float)$jumlah <= 0) {
        $pesan_err = 'Jumlah harus angka lebih dari 0.';
    } else {
        $jml = (float)$jumlah;
        if ($jenis === 'pengeluaran') {
            $umur = ($_POST['umur_pakai'] ?? '') === '' ? null : (int)$_POST['umur_pakai'];
            $stmt = mysqli_prepare($koneksi, "UPDATE `$tabel` SET tanggal_transaksi=?, jumlah=?, keterangan=?, umur_pakai=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sdsii', $tgl, $jml, $ket, $umur, $id);
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE `$tabel` SET tanggal_transaksi=?, jumlah=?, keterangan=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sdsi', $tgl, $jml, $ket, $id);
        }
        mysqli_stmt_execute($stmt);
        header('Location: keuangan_pusat.php?tab=history&msg=edit_ok');
        exit;
    }
}

$stmt = mysqli_prepare($koneksi, "SELECT * FROM `$tabel` WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$row) { die('Transaksi tidak ditemukan.'); }

$page_title = 'Edit Transaksi Keuangan Pusat';
include __DIR__ . '/includes/page_open.php';
?>
<div class="kp-card">
    <h1><i class="fas fa-edit"></i> Edit <?php echo $jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'; ?> Pusat</h1>
    <p class="sub">Kode transaksi <?php echo htmlspecialchars($row['kode_transaksi']); ?> &middot; Cabang <?php echo htmlspecialchars($row['cabang']); ?> &middot; Akun <?php echo htmlspecialchars($row['kode_akun']); ?></p>
</div>
<div class="kp-card">
    <?php if ($pesan_err !== ''): ?><div class="kp-alert err"><?php echo htmlspecialchars($pesan_err); ?></div><?php endif; ?>
    <form method="post" class="kp-form">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="jenis" value="<?php echo htmlspecialchars($jenis); ?>">
        <div class="row"><label for="tanggal_transaksi">Tanggal Transaksi</label>
            <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" required
                   value="<?php echo htmlspecialchars($_POST['tanggal_transaksi'] ?? $row['tanggal_transaksi']); ?>"></div>
        <div class="row"><label for="jumlah">Jumlah (Rp)</label>
            <input type="text" id="jumlah" name="jumlah" required inputmode="decimal"
                   value="<?php echo htmlspecialchars($_POST['jumlah'] ?? (string)(float)$row['jumlah']); ?>"></div>
        <?php if ($jenis === 'pengeluaran'): ?>
        <div class="row"><label for="umur_pakai">Umur Pakai (bulan, kosongkan jika tidak berlaku)</label>
            <input type="number" min="0" id="umur_pakai" name="umur_pakai"
                   value="<?php echo htmlspecialchars((string)($_POST['umur_pakai'] ?? ($row['umur_pakai'] ?? ''))); ?>"></div>
        <?php endif; ?>
        <div class="row"><label for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" rows="3"><?php echo htmlspecialchars($_POST['keterangan'] ?? (string)$row['keterangan']); ?></textarea></div>
        <button type="submit" class="kp-btn ok"><i class="fas fa-save"></i> Simpan</button>
        <a href="keuangan_pusat.php?tab=history" class="kp-btn sec">Batal</a>
    </form>
</div>
<?php include __DIR__ . '/includes/page_close.php'; ?>
