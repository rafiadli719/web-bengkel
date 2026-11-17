<?php
/**
 * Generator dokumen pemetaan fitmotor_center -> fitmotor_dbbengkel.
 * Membaca reload_cabang.php sebagai TEKS (tidak menjalankan, tidak butuh koneksi sumber),
 * jadi dokumen selalu sama dengan kode. Jalankan ulang tiap reload_cabang.php berubah.
 *
 *   php scripts/center_reload/generate_mapping_doc.php > docs/MAPPING_CENTER_KE_WEBBASE.md
 *   php scripts/center_reload/generate_mapping_doc.php --counts   # tambah jumlah baris per cabang dari center_reload_log (butuh config/db_env.php)
 */
$src = file_get_contents(__DIR__ . '/reload_cabang.php');
$withCounts = in_array('--counts', $argv, true);

$start = strpos($src, '// ---------- definisi dataset ----------');
$end   = strpos($src, '// ---------- util tabel ----------');
$body  = substr($src, $start, $end - $start);

function pairs($expr) {
    $out = [];
    if (!preg_match_all("/'([A-Za-z_][A-Za-z0-9_]*)' => (.*?)(?=,\s*'[A-Za-z_][A-Za-z0-9_]*' => |\s*\];\s*\n|\s*\];\s*$)/s", $expr, $m, PREG_SET_ORDER)) { return $out; }
    foreach ($m as $x) { $out[] = [$x[1], trim(preg_replace('/\s+/', ' ', $x[2]))]; }
    return $out;
}
function sumber($expr) {
    $cols = [];
    if (preg_match_all("/\\\$r\['([A-Za-z0-9_]+)'\]/", $expr, $m)) { $cols = array_values(array_unique($m[1])); }
    return $cols;
}
function jenis($expr, $cols) {
    if (!$cols && strpos($expr, '$CAB') !== false) { return 'kode cabang yang direload'; }
    if (!$cols) { return 'konstanta / default'; }
    return 'turunan';
}
function tabel_sumber($sql) {
    if (!$sql) { return '-'; }
    preg_match_all('/\b(?:FROM|JOIN)\s+([A-Za-z0-9_]+)/i', $sql, $m);
    return implode(', ', array_unique($m[1]));
}

$datasets = [];
$chunks = preg_split('/(?=\$DS\[\] = \[|\n    \[\'key\' => \'master_)/', $body);
foreach ($chunks as $c) {
    if (preg_match("/'key' => \\\$k \. '(_header|_detail)', 'target' => \\\$tgt \. '(_header|_detail)'/", $c, $k)) {
        $k = [0, '$k' . $k[1], '$tgt' . $k[2]];   // ditandai, diekspansi di bawah
    } elseif (!preg_match("/'key' => '([^']+)', 'target' => '([^']+)'/", $c, $k)) { continue; }
    $isMaster = strpos($k[1], 'master_') === 0;
    $d = ['key' => $k[1], 'target' => $k[2], 'master' => $isMaster];
    if (preg_match("/'keycols' => \[([^\]]*)\]/", $c, $m)) { $d['keycols'] = array_map(fn($x) => trim($x, " '"), explode(',', $m[1])); }
    if (preg_match("/'pk' => '([^']+)'/", $c, $m)) { $d['keycols'] = [$m[1]]; }
    if (preg_match("/'src' => \"(.*?)\",\s*\n\s*'map'/s", $c, $m)) { $d['src'] = trim(preg_replace('/\s+/', ' ', $m[1])); }
    $mp = strstr($c, "'map' =>");
    $ret = $mp !== false ? strstr($mp, 'return [') : false;
    $d['pairs'] = $ret === false ? [] : pairs(substr($ret, 7));
    // master item/supplier: kolom identik lewat foreach (lowercase nama kolom center)
    if (!$d['pairs'] && $mp !== false && preg_match("/foreach \(\[(.*?)\] as \\\$k\)/s", $mp, $m) && preg_match_all("/'([A-Za-z0-9_]+)'/", $m[1], $mm)) {
        foreach ($mm[1] as $col) { $d['pairs'][] = [strtolower($col), "\$r['$col']"]; }
        if (strpos($mp, "\$m['quantity'] = 0") !== false) { $d['pairs'][] = ['quantity', '0']; }
        if (strpos($mp, "\$m['pertanggal']") !== false) { $d['pairs'][] = ['pertanggal', "d(\$r['PerTanggal'])"]; $d['pairs'][] = ['kd_cabang', '$CAB']; }
    }
    $datasets[$d['key']] = $d;
}
// dataset bertemplat item_masuk / item_keluar (foreach pada sumber)
foreach ($datasets as $k => $d) {
    if (strpos($d['key'], '$k') === false) { continue; }
    unset($datasets[$k]);
    foreach (['item_masuk' => ['tbitem_masuk', 'tblitemm'], 'item_keluar' => ['tbitem_keluar', 'tblitemk']] as $base => [$tgt, $sc]) {
        $suf = strpos($d['key'], '_header') !== false ? '_header' : '_detail';
        $nd = $d;
        $nd['key'] = $base . $suf;
        $nd['target'] = $tgt . $suf;
        $nd['src'] = "SELECT * FROM {$sc}" . ($suf === '_header' ? 'header' : 'detail') . " WHERE kode_cabang='{ref}'";
        $datasets[$nd['key']] = $nd;
    }
}

$counts = [];
if ($withCounts) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $env = __DIR__ . '/../../config/db_env.php';
    if (is_file($env)) { include $env; }
    $T = @mysqli_connect(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME') ?: 'fitmotor_dbbengkel');
    if ($T) {
        $r = mysqli_query($T, "SELECT kd_cabang, dataset, baris_isi, baris_lewat FROM center_reload_log l WHERE aksi IN ('run','tambah') AND status='ok'
                               AND id = (SELECT MAX(id) FROM center_reload_log x WHERE x.kd_cabang=l.kd_cabang AND x.dataset=l.dataset AND x.aksi IN ('run','tambah') AND x.status='ok')");
        while ($r && ($x = mysqli_fetch_row($r))) { $counts[$x[1]][$x[0]] = [(int)$x[2], (int)$x[3]]; }
    } else { fwrite(STDERR, "Koneksi target gagal, bagian jumlah baris dilewati\n"); }
}
$CABS = ['PESALAKAN', 'PACUL', 'CIKDITIRO', 'TRAYEMAN', 'PST'];
$UNIK = ['tblservice', 'tblorder_header', 'tblservice_jemputantar', 'tblitem_harga_cabang'];

$o = [];
$o[] = "# Pemetaan fitmotor_center → fitmotor_dbbengkel (Web Base)";
$o[] = "";
$o[] = "> **Dokumen ini DIGENERATE** dari `scripts/center_reload/reload_cabang.php` oleh `scripts/center_reload/generate_mapping_doc.php`. Jangan edit tangan; ubah kode lalu jalankan ulang generator.";
$o[] = "> Konteks keputusan, konflik master, dan hasil per sesi: `docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md`. Audit struktur: `docs/planning/AUDIT_DATABASE_CENTER_VS_DBBENGKEL_2026-11-15.md`.";
$o[] = "";
$o[] = "## 1. Prinsip";
$o[] = "";
$o[] = "- Sumber `fitmotor_center` (warehouse 5 MDB Access) dibaca **read-only**. Satu cabang dipilih lewat `tbcabang.cabang_ref_kode` (nilai `{ref}` pada query sumber).";
$o[] = "- Target `fitmotor_dbbengkel`: kolom `kd_cabang` diisi kode kanonik (`PESALAKAN`, `PACUL`, `CIKDITIRO`, `TRAYEMAN`, `PST`).";
$o[] = "- Data lama hasil impor Access (kode lama `PSL`, `PCL`, `CDT`, `TRM`) **dibuang** dan diganti isi center. Baris asli input web (kode kanonik yang sudah ada sebelum reload pertama) **dipertahankan**. Bila nomornya bentrok, baris center dilewati dan dicatat.";
$o[] = "- Sebelum hapus, baris cabang itu disalin sekali ke `bak_cr<MMDD>_<CABANG>_<tabel>` (tidak ditimpa). `--mode=rollback` mengembalikannya.";
$o[] = "- Master global (pelanggan, kendaraan, item, supplier) **hanya ditambah bila belum ada**, tidak menimpa. Master mekanik tidak dimuat karena `tblmekanik` di web adalah VIEW.";
$o[] = "- Tiap dataset berjalan dalam satu transaksi; gagal = rollback dataset itu. Hasil dicatat di tabel `center_reload_log`.";
$o[] = "- Kolom target yang tidak dipetakan diisi `lengkapi()`: NOT NULL tanpa default → `0` / `''` / `1970-01-01`; teks dipotong ke panjang kolom; tanggal kosong → NULL bila boleh.";
$o[] = "- Koneksi memakai charset `latin1` di sumber dan target.";
$o[] = "";
$o[] = "## 2. Fungsi pembantu pada kolom Ekspresi";
$o[] = "";
$o[] = "| Fungsi | Arti |";
$o[] = "|---|---|";
$o[] = "| `s(x, n)` | trim teks, dipotong `n` karakter bila `n` diberikan |";
$o[] = "| `n(x)` | angka desimal, kosong/NULL → 0 |";
$o[] = "| `i(x)` | `round(n(x))` jadi integer |";
$o[] = "| `d(x)` | tanggal `YYYY-MM-DD`; kosong atau `0000...` → `''` |";
$o[] = "| `hm(x)` | jam `HH:MM` dari datetime |";
$o[] = "| `lunas(r, kolom)` | `'1'` bila `Pembayaran + JumlahBayar >= total - 1`, selain itu `'0'` |";
$o[] = "| `status_servis(st)` | Access `3` → status `4` + `bayar`; `2` → `2` + `diproses`; lainnya → `1` + `datang` |";
$o[] = "| `\$CAB` | kode cabang kanonik yang sedang direload |";
$o[] = "";
$o[] = "## 3. Ringkasan tabel";
$o[] = "";
$o[] = "| Dataset | Tabel center (sumber) | Tabel web (target) | Kunci baris | Jenis |";
$o[] = "|---|---|---|---|---|";
foreach ($datasets as $d) {
    $o[] = sprintf("| `%s` | %s | `%s` | `%s` | %s |", $d['key'], tabel_sumber($d['src'] ?? ''), $d['target'],
        implode(', ', $d['keycols'] ?? []), $d['master'] ? 'master (hanya tambah)' : 'transaksi (hapus + isi ulang)');
}
$o[] = "| `stok` | tblpembeliandetail, tblpenjualandetail, tblserviceitemdt, tblitemmdetail, tblitemkdetail, tblreturbelidetail, tblitem | `tbstok` | - | dibangun ulang (bagian 5) |";
$o[] = "";
$o[] = "## 4. Pemetaan kolom per dataset";
foreach ($datasets as $d) {
    $o[] = "";
    $o[] = "### `{$d['key']}` → `{$d['target']}`";
    $o[] = "";
    $o[] = "- Jenis: " . ($d['master'] ? 'master global, hanya ditambah bila kunci belum ada (tidak menimpa)' : 'transaksi, baris cabang dihapus lalu diisi ulang dari center');
    $o[] = "- Kunci: `" . implode(', ', $d['keycols'] ?? []) . '`';
    $o[] = "- Query sumber: `" . str_replace('`', "'", $d['src'] ?? '-') . "`";
    if (in_array($d['target'], $UNIK, true)) {
        $o[] = "- Baris dobel pada sumber (kunci sama) dilewati karena target punya UNIQUE pada (kunci, kd_cabang).";
    }
    $o[] = "";
    $o[] = "| Kolom web | Kolom center | Jenis | Ekspresi |";
    $o[] = "|---|---|---|---|";
    foreach ($d['pairs'] as [$col, $expr]) {
        $cs = sumber($expr);
        $o[] = sprintf("| `%s` | %s | %s | `%s` |", $col, $cs ? '`' . implode('`, `', $cs) . '`' : '-', jenis($expr, $cs), str_replace('|', '\\|', $expr));
    }
}
$o[] = "";
$o[] = "## 5. Kartu stok `tbstok` (dibangun ulang, bukan salin)";
$o[] = "";
$o[] = "Baris cabang yang bukan web-native dihapus, lalu dibangun dari transaksi center. Kolom: `tipe`, `no_transaksi`, `no_item`, `tanggal`, `masuk`, `keluar`, `keterangan`, `kd_cabang`.";
$o[] = "";
$o[] = "| tipe | Sumber center | masuk | keluar | keterangan |";
$o[] = "|---|---|---|---|---|";
$o[] = "| `2` | tblpembeliandetail + header | Quantity | 0 | Pembelian |";
$o[] = "| `3` | tblpenjualandetail + header | 0 | Quantity - QtyRetur | Penjualan |";
$o[] = "| `4` | tblserviceitemdt + tblservice | 0 | Quantity - QtyRetur | Servis |";
$o[] = "| `5` | tblitemmdetail + header | Qty | 0 | Penyesuaian Stok Item Masuk |";
$o[] = "| `6` | tblitemkdetail + header | 0 | Qty | Penyesuaian Stok Item Keluar |";
$o[] = "| `7` | tblreturbelidetail + header | 0 | Quantity | Retur Pembelian |";
$o[] = "| `M` | selisih saldo vs `tblitem.Quantity` Access | max(selisih,0) | max(-selisih,0) | Rekonsiliasi saldo Access (`REKON-ACC-<CABANG>`) |";
$o[] = "";
$o[] = "Baris dengan masuk = keluar = 0 dilewati. Setelah semua mutasi masuk, saldo per item (termasuk baris web-native) dibandingkan dengan `tblitem.Quantity` Access; selisih ditutup satu baris rekonsiliasi. Hasil akhir: saldo `tbstok` = Quantity Access.";
$o[] = "";
$o[] = "## 6. Verifikasi (`--mode=verify`)";
$o[] = "";
$o[] = "Jumlah baris dan total rupiah di center dibandingkan dengan web dikurangi baris web-native di backup. Hasil `COCOK` bila jumlah sama dan selisih total < 1.";
$o[] = "";
$o[] = "| Cek | Tabel web | Kolom total |";
$o[] = "|---|---|---|";
foreach ([['servis', 'tblservice', 'total_akhir'], ['servis_barang', 'tblservis_barang', 'total'], ['servis_jasa', 'tblservis_jasa', 'total'], ['beli', 'tblpembelian_header', 'total_akhir'],
          ['beli_detail', 'tblpembelian_detail', 'total'], ['jual', 'tblpenjualan_header', 'total_akhir'], ['jual_detail', 'tblpenjualan_detail', 'total'],
          ['po', 'tblorder_header', 'total_order'], ['hutang', 'tblhutang_header', 'total_bayar'], ['stok_saldo', 'tbstok', 'SUM(masuk-keluar) per item']] as $c) {
    $o[] = "| {$c[0]} | `{$c[1]}` | `{$c[2]}` |";
}
$o[] = "";
$o[] = "## 7. Perintah";
$o[] = "";
$o[] = "```bash";
$o[] = "# env sumber (tanpa default, jangan ditulis ke file): CENTER_DB_HOST CENTER_DB_PORT CENTER_DB_USER CENTER_DB_PASS [CENTER_DB_NAME]";
$o[] = "php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=preflight";
$o[] = "php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=run [--only=servis_header,stok] [--tag=MMDD]";
$o[] = "php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=verify";
$o[] = "php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=rollback --tag=MMDD";
$o[] = "php scripts/center_reload/generate_mapping_doc.php --counts > docs/MAPPING_CENTER_KE_WEBBASE.md";
$o[] = "```";
if ($counts) {
    $o[] = "";
    $o[] = "## 8. Jumlah baris terakhir per cabang (dari `center_reload_log`, status ok)";
    $o[] = "";
    $o[] = "Format `isi / lewat`: baris dimuat / baris dilewati (bentrok web-native, dobel, atau PO dilewati).";
    $o[] = "";
    $o[] = "| Dataset | " . implode(' | ', $CABS) . " |";
    $o[] = "|---|" . str_repeat('---|', count($CABS));
    foreach (array_merge(array_keys($datasets), ['stok']) as $key) {
        if (!isset($counts[$key])) { continue; }
        $row = [];
        foreach ($CABS as $cb) { $row[] = isset($counts[$key][$cb]) ? number_format($counts[$key][$cb][0], 0, ',', '.') . ' / ' . $counts[$key][$cb][1] : '-'; }
        $o[] = "| `$key` | " . implode(' | ', $row) . " |";
    }
}
$o[] = "";
echo implode("\n", $o);
