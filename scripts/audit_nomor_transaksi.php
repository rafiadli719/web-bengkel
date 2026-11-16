<?php
// Penjaga Fase K: query ke tabel transaksi non-servis yang memfilter nomor transaksi TANPA kd_cabang.
// Nomor Access bentrok lintas cabang (PO/pembelian/penjualan/hutang/dst), identitas = (nomor, kd_cabang).
// Pakai: php scripts/audit_nomor_transaksi.php --kode file1.php file2.php   (exit 1 bila ada temuan)
// Kecualikan satu statement dengan komentar `/* fasek-ok */` di dalam statement tersebut.
$files = array_slice($argv, 1);
if ($files && $files[0] === '--kode') { array_shift($files); }
$tabs = 'tblpembelian_header|tblpembelian_detail|tblpenjualan_header|tblpenjualan_detail|tblorderjual_header|tblorderjual_detail|tblhutang_header|tblhutang_detail|tblpiutang_header|tblpiutang_detail|tblretur_pembelian_header|tblretur_pembelian_detail|tblretur_penjualan_header|tblretur_penjualan_detail|tbitem_masuk_header|tbitem_masuk_detail|tbitem_keluar_header|tbitem_keluar_detail|tblkas_keluar_masuk|tblorder_header|tblorder_detail';
$col = '(?:notransaksi|no_transaksi|noretur|no_retur|no_order|kode_km|nokas)';
// antar cabang memang lintas cabang: dikecualikan
$skip = '#(_cab_|antarcab|penerimaan_antarcab|helper-functions|/_archive/|/dompdf/|/PHPExcel/)#';
$bad = 0;
foreach ($files as $f) {
    if (!is_file($f) || preg_match($skip, '/' . $f) || !preg_match('/\.php$/', $f)) { continue; }
    $s = file_get_contents($f);
    if (!preg_match_all('/(SELECT|UPDATE|DELETE\s+FROM)[^;]{0,900}?(?:' . $tabs . ')[^;]{0,900}?;/is', $s, $mm, PREG_OFFSET_CAPTURE)) { continue; }
    foreach ($mm[0] as [$chunk, $off]) {
        if (!preg_match('/\b' . $col . '\s*=\s*(?:\'|"|\?|\$)/', $chunk)) { continue; }
        if (preg_match('/kd_cabang|kode_cabang|fasek-ok/', $chunk)) { continue; }
        $bad++;
        if ($bad === 1) { echo "Kode berisiko (nomor transaksi tanpa kd_cabang):\n"; }
        echo '  ' . $f . ':' . (substr_count(substr($s, 0, $off), "\n") + 1) . "\n";
    }
}
exit($bad ? 1 : 0);
