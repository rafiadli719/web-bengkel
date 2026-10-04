<?php
// Uploader data massal komplain (spec: untuk kebutuhan impor data ke depan, BUKAN untuk
// komplain historis Excel yang disepakati tidak dimigrasikan). CSV, validasi per baris
// dengan aturan yang sama dengan input manual (ajax_submit_komplain.php), semua-atau-tidak
// sama sekali dalam satu transaksi. Default "hanya validasi" supaya aman dicoba dulu.
require __DIR__ . '/koneksi_komplain.php';

if (!cekPermissionKomplain('komplain_master_kategori')) {
    http_response_code(403);
    die('Tidak punya akses impor massal komplain.');
}

const IMPORT_MAX_BARIS = 500;
const IMPORT_MAX_BYTES = 1048576;
$kolomWajib = ['nama_pelanggan', 'no_hp', 'nopol', 'kode_kategori', 'channel_lapor', 'detail_keluhan', 'no_service_asli'];
$channelValid = ['Telepon', 'WA', 'Datang langsung', 'Lainnya'];
$bolehLintasCabang = cekPermissionKomplain('komplain_view_all');

if (($_GET['template'] ?? '') === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_import_komplain.csv"');
    echo implode(',', array_merge($kolomWajib, ['kode_cabang'])) . "\r\n";
    echo 'Budi,081234567890,G 1234 AB,REWORK,Telepon,"Rem blong lagi setelah servis",SV26000000001,' . "\r\n";
    exit;
}

$pesan = '';
$hasil = [];       // per baris: [no, status, keterangan]
$berhasilSimpan = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanyaValidasi = !isset($_POST['simpan']);
    $f = $_FILES['file_csv'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
        $pesan = 'File CSV belum dipilih atau gagal diunggah.';
    } elseif ($f['size'] > IMPORT_MAX_BYTES) {
        $pesan = 'File terlalu besar (maksimal 1 MB).';
    } else {
        $fh = fopen($f['tmp_name'], 'r');
        $header = fgetcsv($fh);
        if ($header) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); }
        $header = array_map(function ($h) { return strtolower(trim((string)$h)); }, $header ?: []);
        $kurang = array_diff($kolomWajib, $header);
        if ($kurang) {
            $pesan = 'Kolom wajib belum ada: ' . implode(', ', $kurang) . '. Unduh template.';
        } else {
            $stmtService = $koneksi_komplain->prepare("SELECT no_polisi FROM tblservice WHERE no_service = :ns");
            $stmtKat = $koneksi_komplain->prepare("SELECT pic_role FROM tblkomplain_kategori WHERE kode_kategori = :k AND is_active = 'active'");
            $stmtCab = $koneksi->prepare("SELECT 1 FROM tbcabang WHERE kode_cabang = ?");
            $baris = [];
            $no = 1;
            while (($cols = fgetcsv($fh)) !== false) {
                $no++;
                if (count($cols) === 1 && trim((string)$cols[0]) === '') continue; // baris kosong
                if (count($baris) >= IMPORT_MAX_BARIS) { $pesan = 'Maksimal ' . IMPORT_MAX_BARIS . ' baris per file; sisanya diabaikan.'; break; }
                $r = [];
                foreach ($header as $i => $h) {
                    $v = trim((string)($cols[$i] ?? ''));
                    // koneksi PDO komplain latin1: ubah CSV UTF-8 supaya tidak jadi mojibake
                    $r[$h] = function_exists('mb_convert_encoding') ? mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8') : $v;
                }
                $err = [];
                foreach ($kolomWajib as $k) { if (($r[$k] ?? '') === '') $err[] = "$k kosong"; }
                $cabang = $kode_cabang_aktif;
                if (!$err) {
                    if (!in_array($r['channel_lapor'], $channelValid, true)) $err[] = 'channel_lapor harus: ' . implode('/', $channelValid);
                    if (($r['kode_cabang'] ?? '') !== '') {
                        $cabang = $r['kode_cabang'];
                        if ($cabang !== $kode_cabang_aktif && !$bolehLintasCabang) {
                            $err[] = 'tidak boleh impor untuk cabang lain';
                        } else {
                            $stmtCab->bind_param('s', $cabang); $stmtCab->execute();
                            if (!$stmtCab->get_result()->fetch_row()) $err[] = 'kode_cabang tidak dikenal';
                        }
                    }
                    $stmtService->execute([':ns' => $r['no_service_asli']]);
                    $polisi = $stmtService->fetchColumn();
                    if ($polisi === false) $err[] = 'no_service_asli tidak ditemukan';
                    elseif (strcasecmp($polisi, $r['nopol']) !== 0) $err[] = 'no_service_asli tidak cocok dengan nopol';
                    $stmtKat->execute([':k' => $r['kode_kategori']]);
                    $picRole = $stmtKat->fetchColumn();
                    if ($picRole === false) $err[] = 'kode_kategori tidak valid';
                }
                if ($err) {
                    $hasil[] = [$no, 'GAGAL', implode('; ', $err)];
                } else {
                    $hasil[] = [$no, 'OK', htmlspecialchars($r['nopol']) . ' / ' . htmlspecialchars($r['kode_kategori'])];
                    $baris[] = [$r, $cabang, $picRole];
                }
            }
            fclose($fh);

            $adaGagal = false;
            foreach ($hasil as $h) { if ($h[1] === 'GAGAL') { $adaGagal = true; break; } }

            if ($hanyaValidasi) {
                $pesan = ($pesan ? $pesan . ' ' : '') . ($adaGagal
                    ? 'Validasi: ada baris gagal. Perbaiki lalu unggah ulang (belum ada data tersimpan).'
                    : 'Validasi lolos: ' . count($baris) . ' baris siap disimpan. Centang "Simpan sungguhan" untuk menyimpan.');
            } elseif ($adaGagal || !$baris) {
                $pesan = 'Tidak disimpan: ' . ($adaGagal ? 'masih ada baris gagal (semua-atau-tidak-sama-sekali).' : 'tidak ada baris valid.');
            } else {
                try {
                    $koneksi_komplain->beginTransaction();
                    $ins = $koneksi_komplain->prepare(
                        "INSERT INTO tblkomplain
                         (no_komplain, nama_pelanggan, no_hp, nopol, kode_cabang, kode_kategori, channel_lapor, detail_keluhan, no_service_asli, pic_kode_karyawan, status)
                         VALUES (:no, :nama, :hp, :nopol, :cabang, :kategori, :channel, :detail, :noservice, :pic, 'Open')"
                    );
                    foreach ($baris as [$r, $cabang, $picRole]) {
                        $pic = $picRole === 'KEPALA_MEKANIK' ? getPicRework($koneksi_komplain, $cabang) : getPicNonRework($koneksi_komplain, $cabang);
                        $id = null; $noK = null;
                        for ($p = 1; $p <= 3; $p++) {
                            $noK = generateNoKomplain($koneksi_komplain);
                            try {
                                $ins->execute([
                                    ':no' => $noK, ':nama' => $r['nama_pelanggan'], ':hp' => $r['no_hp'], ':nopol' => $r['nopol'],
                                    ':cabang' => $cabang, ':kategori' => $r['kode_kategori'], ':channel' => $r['channel_lapor'],
                                    ':detail' => $r['detail_keluhan'], ':noservice' => $r['no_service_asli'], ':pic' => $pic,
                                ]);
                                $id = (int)$koneksi_komplain->lastInsertId();
                                break;
                            } catch (PDOException $e) {
                                if ($e->getCode() === '23000' && $p < 3) continue;
                                throw $e;
                            }
                        }
                        catatLogKomplain($koneksi_komplain, $id, 'input_massal', null, 'Open', "Impor CSV ({$r['channel_lapor']})");
                        $berhasilSimpan++;
                    }
                    $koneksi_komplain->commit();
                    $pesan = "Berhasil menyimpan $berhasilSimpan komplain.";
                } catch (Throwable $e) {
                    if ($koneksi_komplain->inTransaction()) $koneksi_komplain->rollBack();
                    error_log('[komplain] impor massal gagal: ' . $e->getMessage());
                    $berhasilSimpan = 0;
                    $pesan = 'Gagal menyimpan, tidak ada data yang masuk (rollback). Coba lagi.';
                }
            }
        }
    }
}

$pageTitle = 'Impor Massal Komplain';
include __DIR__ . '/_ace_header.php';
?>
  <?php if ($pesan): ?><div class="alert alert-info"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>

  <div class="card mb-4"><div class="card-body">
    <h6>Unggah CSV</h6>
    <p class="text-muted">Kolom: <code><?= htmlspecialchars(implode(', ', $kolomWajib)) ?></code> + <code>kode_cabang</code> (opsional). Maksimal <?= IMPORT_MAX_BARIS ?> baris, 1 MB.
      <a href="import_massal.php?template=1">Unduh template</a></p>
    <form method="post" enctype="multipart/form-data">
      <div class="mb-2"><input type="file" name="file_csv" accept=".csv,text/csv" required></div>
      <div class="mb-2"><label><input type="checkbox" name="simpan" value="1"> Simpan sungguhan (kalau tidak dicentang: hanya validasi)</label></div>
      <button class="btn btn-primary" type="submit">Proses</button>
      <a class="btn btn-default" href="master_kategori.php">Kembali</a>
    </form>
  </div></div>

  <?php if ($hasil): ?>
  <table class="table table-sm table-bordered">
    <thead><tr><th>Baris</th><th>Status</th><th>Keterangan</th></tr></thead>
    <tbody>
    <?php foreach ($hasil as $h): ?>
      <tr class="<?= $h[1] === 'OK' ? '' : 'danger' ?>"><td><?= (int)$h[0] ?></td><td><?= $h[1] ?></td><td><?= $h[1] === 'OK' ? $h[2] : htmlspecialchars($h[2]) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
<?php include __DIR__ . '/_ace_footer.php'; ?>
</body>
</html>
