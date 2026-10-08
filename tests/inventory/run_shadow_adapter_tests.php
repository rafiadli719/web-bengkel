<?php
/**
 * Tes adapter shadow (app/_include_inv_shadow.php). Jalankan dua kali:
 *   php.exe -d extension=php_mysqli.dll -f tests/inventory/run_shadow_adapter_tests.php          (mode shadow)
 *   php.exe -d extension=php_mysqli.dll -f tests/inventory/run_shadow_adapter_tests.php off      (mode off)
 * DB lokal saja. Item ZZTEST-AD*, dibersihkan sendiri.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../app/lib/InventoryService.php';

$modeOff = (($argv[1] ?? '') === 'off');
$svcAdmin = new InventoryService($koneksi);
$awal = $svcAdmin->getMode('PST');
// semua cabang off di mode off, supaya $mati=true terpicu; PST shadow di mode shadow
$cabangSemua = [];
$rc = mysqli_query($koneksi, "SELECT kode_cabang FROM tbcabang");
while ($c = mysqli_fetch_assoc($rc)) { $cabangSemua[$c['kode_cabang']] = $svcAdmin->getMode($c['kode_cabang']); }
foreach ($cabangSemua as $k => $m) { $svcAdmin->setMode($k, ($k === 'PST' && !$modeOff) ? 'shadow' : 'off', 'tes-adapter'); }

require_once __DIR__ . '/../../app/_include_inv_shadow.php';

$lulus = 0; $gagal = 0;
function cek($n, $k) { global $lulus, $gagal; if ($k) { $lulus++; echo "  [OK]   $n\n"; } else { $gagal++; echo "  [GAGAL] $n\n"; } }
function hit($k, $sql) { $r = mysqli_query($k, $sql); return (int) mysqli_fetch_row($r)[0]; }
function bersih($k) {
    mysqli_query($k, "DELETE FROM inv_ledger WHERE no_item LIKE 'ZZTEST-AD%'");
    mysqli_query($k, "DELETE FROM inv_avg_history WHERE no_item LIKE 'ZZTEST-AD%'");
    mysqli_query($k, "DELETE FROM inv_balance WHERE no_item LIKE 'ZZTEST-AD%'");
    mysqli_query($k, "DELETE FROM tbstok WHERE no_item LIKE 'ZZTEST-AD%'");
    mysqli_query($k, "DELETE FROM inv_audit_log WHERE actor IN ('tes-adapter') OR ref LIKE 'ZZTAD%'");
    mysqli_query($k, "DELETE FROM tblpenjualan_detail WHERE user='zz-keranjang'");
    mysqli_query($k, "DELETE FROM tblservis_barang WHERE no_service='ZZ-SV-1'");
}
bersih($koneksi);

if ($modeOff) {
    echo "== Mode off: tidak menulis apa pun\n";
    inv_shadow_in($koneksi, 'PST', 'ZZTEST-AD1', 5, '100', 'PEMBELIAN', 'ZZTAD-1', 'tes', 'tes');
    inv_shadow_out($koneksi, 'PST', 'ZZTEST-AD1', 1, 'PENJUALAN', 'ZZTAD-2', 'tes', 'tes');
    cek('Tidak ada ledger/saldo/audit saat semua cabang off', hit($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE no_item LIKE 'ZZTEST-AD%'") === 0
        && hit($koneksi, "SELECT COUNT(*) FROM inv_balance WHERE no_item LIKE 'ZZTEST-AD%'") === 0);
} else {
    echo "== Biaya satuan\n";
    cek('total/qty = 12.50', inv_biaya_satuan(25, 2, 0) === '12.50');
    cek('total kosong -> fallback harga_pokok', inv_biaya_satuan(0, 2, 8) === '8.00');
    cek('qty 0 -> fallback', inv_biaya_satuan(10, 0, 3) === '3.00');

    echo "== Isolasi transaksi (12 file lama pakai transaksi sendiri)\n";
    mysqli_begin_transaction($koneksi);
    mysqli_query($koneksi, "INSERT INTO tbstok (tipe,no_transaksi,no_item,tanggal,masuk,keluar,keterangan,kd_cabang) VALUES ('2','ZZTAD-A','ZZTEST-AD1',CURDATE(),5,0,'tes','PST')");
    inv_shadow_in($koneksi, 'PST', 'ZZTEST-AD1', 5, '100', 'PEMBELIAN', 'ZZTAD-A', 'tes', 'tes-adapter');
    $dalamTx = hit($koneksi, "SELECT COUNT(*) FROM tbstok WHERE no_item='ZZTEST-AD1'");
    mysqli_rollback($koneksi);
    cek('Transaksi lama masih terbuka setelah shadow (tidak ada commit implisit)', $dalamTx === 1 && hit($koneksi, "SELECT COUNT(*) FROM tbstok WHERE no_item='ZZTEST-AD1'") === 0);
    cek('Rollback transaksi lama tidak membatalkan shadow (koneksi terpisah)', hit($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE no_item='ZZTEST-AD1' AND ref_no='ZZTAD-A'") === 1);

    echo "== Alur lama tidak terganggu saat shadow gagal\n";
    $r = inv_shadow_out($koneksi, 'PST', 'ZZTEST-AD1', 99, 'PENJUALAN', 'ZZTAD-B', 'tes', 'tes-adapter');
    cek('Keluar melebihi saldo: tidak melempar, mengembalikan null', $r === null);
    cek('Penolakan tercatat di audit', hit($koneksi, "SELECT COUNT(*) FROM inv_audit_log WHERE action='shadow_gagal' AND ref='PENJUALAN:ZZTAD-B'") === 1);
    $r = inv_shadow_in($koneksi, 'LOKASI-NGAWUR', 'ZZTEST-AD1', 1, '1', 'PEMBELIAN', 'ZZTAD-C', 'tes', 'tes-adapter');
    cek('Lokasi tidak dikenal: tidak melempar', $r === null);
    $r = inv_shadow_out($koneksi, 'PST', 'ZZTEST-AD1', 'abc', 'PENJUALAN', 'ZZTAD-D', 'tes', 'tes-adapter');
    cek('Qty sampah: tidak melempar', $r === null);

    echo "== HPP asal (retur penjualan, terima transfer)\n";
    inv_shadow_in($koneksi, 'PST', 'ZZTEST-AD2', 10, '200', 'PEMBELIAN', 'ZZTAD-E', 'tes', 'tes-adapter');
    inv_shadow_out($koneksi, 'PST', 'ZZTEST-AD2', 4, 'PENJUALAN', 'ZZTAD-F', 'tes', 'tes-adapter');
    cek('inv_hpp_asal mengembalikan HPP posting keluar', inv_hpp_asal($koneksi, 'PENJUALAN', 'ZZTAD-F', 'ZZTEST-AD2') === '200.00');
    cek('inv_hpp_asal null bila referensi tidak ada', inv_hpp_asal($koneksi, 'PENJUALAN', 'TIDAK-ADA', 'ZZTEST-AD2') === null);
    $r = inv_shadow_in($koneksi, 'PST', 'ZZTEST-AD2', 1, inv_hpp_asal($koneksi, 'PENJUALAN', 'ZZTAD-F', 'ZZTEST-AD2'), 'RETUR_JUAL', 'ZZTAD-G', 'tes', 'tes-adapter');
    cek('Retur penjualan masuk pada HPP asal, average tidak bergeser', $r !== null && $r['avg_after'] === '200.00' && $r['qty_after'] === '7.000');
    $r = inv_shadow_in($koneksi, 'PST', 'ZZTEST-AD2', 1, null, 'OPNAME_MASUK', 'ZZTAD-H', 'tes', 'tes-adapter');
    cek('Biaya null = pakai average saat ini', $r !== null && $r['avg_after'] === '200.00' && $r['qty_after'] === '8.000');

    echo "== Pembalikan (pengganti DELETE tbstok saat batal retur)\n";
    inv_shadow_out($koneksi, 'PST', 'ZZTEST-AD3', 0.5, 'RETUR_BELI', 'ZZTAD-I', 'tes', 'tes-adapter'); // ditolak (tidak ada saldo)
    inv_shadow_in($koneksi, 'PST', 'ZZTEST-AD3', 10, '300', 'PEMBELIAN', 'ZZTAD-J', 'tes', 'tes-adapter');
    inv_shadow_out($koneksi, 'PST', 'ZZTEST-AD3', 3, 'RETUR_BELI', 'ZZTAD-K', 'tes', 'tes-adapter');
    $n = inv_shadow_reverse($koneksi, 'RETUR_BELI', 'ZZTAD-K', 'batal', 'tes-adapter');
    $b = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT qty_on_hand, average_cost FROM inv_balance WHERE no_item='ZZTEST-AD3'"));
    cek('Batal retur membalik 1 posting; saldo kembali 10 @300', $n === 1 && $b['qty_on_hand'] === '10.000' && $b['average_cost'] === '300.00');
    cek('Pembalikan kedua tidak menggandakan', inv_shadow_reverse($koneksi, 'RETUR_BELI', 'ZZTAD-K', 'batal', 'tes-adapter') === 0);
    cek('Ledger tetap append-only (baris asli + VOID)', hit($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE ref_no='ZZTAD-K'") === 2);

    echo "== Rekonsiliasi (F1-09)\n";
    $adaBase = $svcAdmin->getConfig('PST', 'shadow_since_tbstok_id', null) !== null;
    cek('Baseline tbstok tercatat saat shadow aktif', $adaBase);
    mysqli_query($koneksi, "INSERT INTO tbstok (tipe,no_transaksi,no_item,tanggal,masuk,keluar,keterangan,kd_cabang) VALUES ('2','ZZTAD-R1','ZZTEST-AD9',CURDATE(),5,0,'tes','PST')");
    inv_shadow_in($koneksi, 'PST', 'ZZTEST-AD9', 5, '100', 'PEMBELIAN', 'ZZTAD-R1', 'tes', 'tes-adapter');
    mysqli_query($koneksi, "INSERT INTO tbstok (tipe,no_transaksi,no_item,tanggal,masuk,keluar,keterangan,kd_cabang) VALUES ('2','ZZTAD-R2','ZZTEST-AD8',CURDATE(),3,0,'tes','PST')");
    $rk = $svcAdmin->reconcile('PST');
    $peta = [];
    foreach ($rk['selisih'] as $s) { $peta[$s['no_item']] = $s['selisih']; }
    cek('Item yang tercatat di kedua jalur tidak muncul sebagai selisih', !isset($peta['ZZTEST-AD9']));
    cek('Item hanya di jalur lama muncul dengan selisih -3', isset($peta['ZZTEST-AD8']) && abs($peta['ZZTEST-AD8'] + 3) < 0.001);
    mysqli_query($koneksi, "DELETE FROM inv_config WHERE kd_cabang='PST' AND config_key='shadow_since_tbstok_id' AND updated_by='tes-adapter'");

    echo "== Guard cabang live (F2-13 sync Access)\n";
    cek('Shadow bukan live', inv_cabang_live($koneksi, 'PST') === false);
    $svcAdmin->setMode('PACUL', 'live', 'tes-adapter');
    cek('Live terdeteksi lewat kode kanonik dan alias (PCL)', inv_cabang_live($koneksi, 'PACUL') === true && inv_cabang_live($koneksi, 'PCL') === true);
    cek('Cabang lain tidak ikut live', inv_cabang_live($koneksi, 'CDT') === false);
    cek('Lokasi tak dikenal = bukan live', inv_cabang_live($koneksi, 'XYZ') === false);

    echo "== Penegakan live (BR-05)\n";
    cek('Cabang bukan live: inv_live_saldo null (alur lama berlaku)', inv_live_saldo($koneksi, 'PST', 'ZZTEST-AD7') === null);
    $svcAdmin->postIn('PACUL', 'ZZTEST-AD7', 5, '100', 'OPENING', 'ZZTAD-OP', ['trx_type' => 'OPENING', 'user' => 'tes-adapter']);
    cek('Cabang live: inv_live_saldo = saldo ledger (5)', inv_live_saldo($koneksi, 'PACUL', 'ZZTEST-AD7') === 5.0);
    cek('Alias cabang live juga terbaca', inv_live_saldo($koneksi, 'PCL', 'ZZTEST-AD7') === 5.0);
    cek('Item tanpa saldo di cabang live = 0', inv_live_saldo($koneksi, 'PACUL', 'ZZTEST-AD-TIDAKADA') === 0.0);
    mysqli_query($koneksi, "INSERT INTO tblpenjualan_detail (no_transaksi,nobaris,no_item,qty_order,quantity,qty_retur,harga_jual,potongan,harga_sp,harga_pokok,total,sts_order,user,kd_cabang,status_trx) VALUES ('',0,'ZZTEST-AD7',9,9,0,1,0,0,0,9,'','zz-keranjang','PACUL','0')");
    $kk = inv_live_cek_keranjang($koneksi, 'PACUL', 'zz-keranjang');
    cek('Keranjang melebihi saldo ledger ditolak dengan item dan jumlahnya', is_array($kk) && $kk['item'] === 'ZZTEST-AD7' && $kk['tersedia'] === 5.0 && $kk['diminta'] === 9.0);
    mysqli_query($koneksi, "UPDATE tblpenjualan_detail SET quantity=4, qty_order=4 WHERE user='zz-keranjang'");
    cek('Keranjang dalam batas saldo lolos', inv_live_cek_keranjang($koneksi, 'PACUL', 'zz-keranjang') === null);
    mysqli_query($koneksi, "DELETE FROM tblpenjualan_detail WHERE user='zz-keranjang'");
    cek('Bukan live: cek keranjang tidak mengganggu', inv_live_cek_keranjang($koneksi, 'PST', 'zz-keranjang') === null);
    mysqli_query($koneksi, "INSERT INTO tblservis_barang (no_service,kd_cabang,nobaris,no_item,quantity,qty_retur,harga_jual,potongan,total,asal_barang) VALUES ('ZZ-SV-1','PACUL',1,'ZZTEST-AD7',9,0,1,0,9,'SERVIS'),('ZZ-SV-1','PACUL',2,'ZZTEST-AD6',50,0,1,0,50,'PART-CUST')");
    $sv = inv_live_cek_servis($koneksi, 'PACUL', 'ZZ-SV-1', true);
    cek('Servis: barang SERVIS melebihi saldo ledger ditolak', is_array($sv) && $sv['item'] === 'ZZTEST-AD7' && $sv['diminta'] === 9.0);
    cek('Servis: PART-CUST (milik pelanggan) tidak dihitung pada jalur berfilter', is_array($sv) && $sv['item'] !== 'ZZTEST-AD6');
    $sv2 = inv_live_cek_servis($koneksi, 'PACUL', 'ZZ-SV-1', false);
    cek('Servis tanpa filter (jalur rst) ikut menghitung semua baris', is_array($sv2));
    mysqli_query($koneksi, "UPDATE tblservis_barang SET quantity=3 WHERE no_service='ZZ-SV-1' AND no_item='ZZTEST-AD7'");
    cek('Servis dalam batas saldo lolos (jalur berfilter)', inv_live_cek_servis($koneksi, 'PACUL', 'ZZ-SV-1', true) === null);
    cek('Servis nomor sama di cabang lain tidak ikut terhitung', inv_live_cek_servis($koneksi, 'PST', 'ZZ-SV-1', true) === null);
    mysqli_query($koneksi, "DELETE FROM tblservis_barang WHERE no_service='ZZ-SV-1'");
}

bersih($koneksi);
foreach ($cabangSemua as $k => $m) { $svcAdmin->setMode($k, $m, 'tes-restore'); }
mysqli_query($koneksi, "DELETE FROM inv_audit_log WHERE actor IN ('tes-adapter','tes-restore')");
echo "\nHasil: $lulus lulus, $gagal gagal\n";
exit($gagal === 0 ? 0 : 1);
