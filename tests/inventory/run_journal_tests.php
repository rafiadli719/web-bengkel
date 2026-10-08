<?php
/**
 * Tes jurnal, konversi satuan, dan cek integritas (F5-04/05, F4-08). DB lokal saja.
 *   php.exe -d extension=php_mysqli.dll -f tests/inventory/run_journal_tests.php
 * Memakai item ZZTEST-J* dan kode akun uji; membersihkan sendiri.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../app/lib/InventoryCorrection.php';
require_once __DIR__ . '/../../app/lib/InventoryJournal.php';

$svc = new InventoryService($koneksi);
$kor = new InventoryCorrection($svc, $koneksi);
$jur = new InventoryJournal($svc, $koneksi);
$lulus = 0; $gagal = 0;
function cek($n, $k, $info = '') { global $lulus, $gagal; if ($k) { $lulus++; echo "  [OK]   $n\n"; } else { $gagal++; echo "  [GAGAL] $n $info\n"; } }
function kode(callable $f) { try { $f(); return ''; } catch (InventoryException $e) { return $e->kode; } }
function hit($k, $sql) { return (int) mysqli_fetch_row(mysqli_query($k, $sql))[0]; }
function val($k, $sql) { return mysqli_fetch_row(mysqli_query($k, $sql))[0]; }
$akunUji = ['acct_inventory' => 'T-INV', 'acct_transit' => 'T-TRN', 'acct_cogs' => 'T-COGS', 'acct_ap_clearing' => 'T-APC',
            'acct_ppv' => 'T-PPV', 'acct_claim' => 'T-CLM', 'acct_adjustment' => 'T-ADJ', 'acct_opening' => 'T-OPN', 'acct_reval' => 'T-REV'];
function bersih($k) {
    mysqli_query($k, "DELETE FROM inv_journal_line WHERE account LIKE 'T-%'");
    mysqli_query($k, "DELETE FROM inv_journal WHERE source_id IN (SELECT id FROM (SELECT id FROM inv_ledger WHERE no_item LIKE 'ZZTEST-J%') x) AND source_type='LEDGER'");
    mysqli_query($k, "DELETE FROM inv_journal WHERE source_type='PPV' AND ref_no LIKE 'ZJ%'");
    mysqli_query($k, "DELETE FROM inv_journal WHERE id NOT IN (SELECT DISTINCT journal_id FROM inv_journal_line)");
    foreach (['inv_ledger', 'inv_avg_history', 'inv_balance', 'inv_ppv_log', 'inv_transit'] as $t) mysqli_query($k, "DELETE FROM $t WHERE no_item LIKE 'ZZTEST-J%'");
    mysqli_query($k, "DELETE FROM inv_config WHERE kd_cabang='*' AND config_key LIKE 'acct\\_%' AND updated_by='tes-j'");
    mysqli_query($k, "DELETE FROM inv_audit_log WHERE actor='tes-j'");
}
bersih($koneksi);

echo "== Konversi satuan (F4-08, Test 9)\n";
$svc->postIn('PST', 'ZZTEST-J1', 10, '12000', 'PEMBELIAN', 'ZJ-J1');
$r = $svc->convertUnit('PST', 'ZZTEST-J1', 12, 'CV-J1', ['user' => 'tes-j']);
cek('10 dus @12.000 x12 -> 120 pcs @1.000, nilai tetap 120.000', $r['qty_after'] === '120.000' && $r['avg_after'] === '1000.00' && $r['value_after'] === '120000.00');
$sv = $svc->saldo('PST', 'ZZTEST-J1');
cek('Saldo konsisten dengan ledger', $sv['qty_on_hand'] === '120.000' && $sv['total_value'] === '120000.00');
cek('Konversi idempoten', $svc->convertUnit('PST', 'ZZTEST-J1', 12, 'CV-J1', ['user' => 'tes-j'])['idempotent'] === true && $svc->saldo('PST', 'ZZTEST-J1')['qty_on_hand'] === '120.000');
$svc->postIn('PST', 'ZZTEST-J2', 10, '12000', 'PEMBELIAN', 'ZJ-J2');
$r = $svc->convertUnit('PST', 'ZZTEST-J2', '0.5', 'CV-J2', ['user' => 'tes-j']);
cek('Rasio 0,5: qty 5, average 24.000, nilai tetap', $r['qty_after'] === '5.000' && $r['avg_after'] === '24000.00' && $r['value_after'] === '120000.00');
cek('Rasio 0 ditolak', kode(function () use ($svc) { $svc->convertUnit('PST', 'ZZTEST-J1', 0, 'CV-X', ['user' => 'tes-j']); }) === 'rasio_invalid');
cek('Stok kosong ditolak', kode(function () use ($svc) { $svc->convertUnit('PST', 'ZZTEST-JKOSONG', 2, 'CV-Y', ['user' => 'tes-j']); }) === 'stok_kosong');
$svc->postOut('PST', 'ZZTEST-J1', 20, 'PENJUALAN', 'J1S');
cek('HPP setelah konversi memakai average baru (20 x 1.000)', $svc->saldo('PST', 'ZZTEST-J1')['total_value'] === '100000.00');

echo "== Cek integritas (F5-05)\n";
$c = $svc->selfCheck('PST');
cek('Saldo = jumlah ledger untuk semua item (sehat)', $c['item_dicek'] >= 2 && count($c['selisih']) === 0, json_encode($c['selisih']));
mysqli_query($koneksi, "UPDATE inv_balance SET qty_on_hand = qty_on_hand + 1 WHERE no_item='ZZTEST-J2' AND kd_cabang='PST'");
$c = $svc->selfCheck('PST');
cek('Saldo yang dirusak langsung terdeteksi', count($c['selisih']) === 1 && $c['selisih'][0]['item'] === 'ZZTEST-J2' && $c['selisih'][0]['selisih_qty'] == 1.0);
mysqli_query($koneksi, "UPDATE inv_balance SET qty_on_hand = qty_on_hand - 1 WHERE no_item='ZZTEST-J2' AND kd_cabang='PST'");

echo "== Jurnal (F5-04)\n";
cek('Tanpa COA: tidak ada yang dijurnal, tidak error', $jur->postPending() === ['jurnal' => 0, 'dilewati' => $jur->postPending()['dilewati']] && hit($koneksi, "SELECT COUNT(*) FROM inv_journal") === 0);
cek('rekonJurnal tanpa COA memberi catatan', array_key_exists('sama', $r0 = $jur->rekonJurnal()) && $r0['sama'] === null && isset($r0['catatan']));
foreach ($akunUji as $k => $v) $jur->setAkun($k, $v, 'tes-j');
cek('Kunci akun tidak dikenal ditolak', kode(function () use ($jur) { $jur->setAkun('acct_ngawur', 'X', 'tes-j'); }) === 'akun_invalid');
$svc->postIn('PST', 'ZZTEST-J3', 10, '1000', 'PEMBELIAN', 'ZJ-BL');
$svc->postOut('PST', 'ZZTEST-J3', 4, 'PENJUALAN', 'ZJ-JL');
$kor->shipTransfer('ZJ-TR', 'PST', 'PACUL', 'ZZTEST-J3', 2, 'tes-j');
$kor->receiveTransfer('ZJ-TR', 'ZZTEST-J3', 'tes-j');
$kor->scrap('PST', 'ZZTEST-J3', 1, 'RUSAK', 'BA jurnal uji', 'tes-j', 'ZJ-SCRAP');
$kor->createClaim('ZJ-KL', 'PST', 'ZZTEST-J3', 1, 'SUP', 'tes-j');
$kor->adjust('PACUL', 'ZZTEST-J3', 1, 'adjust jurnal uji', 'tes-j');
$hasil = $jur->postPending();
cek('Semua baris bernilai dijurnal', $hasil['jurnal'] >= 9, json_encode($hasil));
cek('Setiap jurnal seimbang (debit = kredit)', hit($koneksi, "SELECT COUNT(*) FROM (SELECT journal_id FROM inv_journal_line GROUP BY journal_id HAVING ROUND(SUM(debit)-SUM(credit),2) <> 0) x") === 0);
cek('Total debit = total kredit seluruh jurnal uji', val($koneksi, "SELECT ROUND(SUM(debit)-SUM(credit),2) FROM inv_journal_line WHERE account LIKE 'T-%'") == 0);
cek('Pembelian: Dr Persediaan 10.000 / Cr Clearing', hit($koneksi, "SELECT COUNT(*) FROM inv_journal j JOIN inv_journal_line a ON a.journal_id=j.id AND a.account='T-INV' AND a.debit=10000 JOIN inv_journal_line b ON b.journal_id=j.id AND b.account='T-APC' AND b.credit=10000 WHERE j.ref_no='ZJ-BL'") === 1);
cek('Penjualan: Dr HPP 4.000 / Cr Persediaan', hit($koneksi, "SELECT COUNT(*) FROM inv_journal j JOIN inv_journal_line a ON a.journal_id=j.id AND a.account='T-COGS' AND a.debit=4000 WHERE j.ref_no='ZJ-JL'") === 1);
cek('Transfer lewat akun Barang dalam Perjalanan (kirim dan terima saling hapus)', $jur->saldoAkun('T-TRN') === '0.00');
cek('Klaim: Dr Piutang Klaim 1.000', hit($koneksi, "SELECT COUNT(*) FROM inv_journal j JOIN inv_journal_line a ON a.journal_id=j.id AND a.account='T-CLM' AND a.debit=1000 WHERE j.ref_no='ZJ-KL'") === 1);
cek('Posting ulang tidak menggandakan', $jur->postPending()['jurnal'] === 0);
$rk = $jur->rekonJurnal();
cek('Rekonsiliasi: saldo akun Persediaan = sum(inv_balance), semua dijurnal', $rk['sama'] === true, json_encode($rk));

$svc->postIn('PST', 'ZZTEST-J4', 5, '2000', 'PEMBELIAN', 'ZJ-BL4');
$svc->postOut('PST', 'ZZTEST-J4', 2, 'PENJUALAN', 'ZJ-JL4');
$kor->correctPurchasePrice('PST', 'ZZTEST-J4', 'PEMBELIAN', 'ZJ-BL4', '2500', 'ZJ-K1', 'koreksi harga jurnal uji', 'tes-j');
$jur->postPending();
cek('Koreksi harga: sisa 3 x 500 ke Persediaan, terjual 2 x 500 ke PPV', $jur->saldoAkun('T-PPV') === '1000.00'
    && hit($koneksi, "SELECT COUNT(*) FROM inv_journal j JOIN inv_journal_line a ON a.journal_id=j.id AND a.account='T-INV' AND a.debit=1500 WHERE j.ref_type='CORR_HARGA'") === 1);
$rk = $jur->rekonJurnal();
cek('Rekonsiliasi tetap sama setelah koreksi harga', $rk['sama'] === true, json_encode($rk));

echo "== COA sebagian: yang akunnya belum ada dilewati, bukan gagal\n";
$jur->setAkun('acct_cogs', '', 'tes-j');
$svc->postIn('PST', 'ZZTEST-J5', 3, '100', 'PEMBELIAN', 'ZJ-BL5');
$svc->postOut('PST', 'ZZTEST-J5', 1, 'PENJUALAN', 'ZJ-JL5');
$h = $jur->postPending();
cek('Penjualan tanpa akun HPP dilewati, pembelian tetap dijurnal', $h['dilewati'] >= 1 && $h['jurnal'] >= 1);
$rk = $jur->rekonJurnal();
cek('Rekonsiliasi menandai ada yang belum dijurnal', $rk['sama'] === false && $rk['belum_dijurnal'] >= 1);
$jur->setAkun('acct_cogs', 'T-COGS', 'tes-j');
$jur->postPending();
cek('Setelah COA lengkap, jurnal tertunda terbentuk dan rekonsiliasi sama', $jur->rekonJurnal()['sama'] === true);

bersih($koneksi);
mysqli_query($koneksi, "DELETE FROM inv_audit_log WHERE actor='tes-j'");
echo "\nHasil: $lulus lulus, $gagal gagal\n";
exit($gagal === 0 ? 0 : 1);
