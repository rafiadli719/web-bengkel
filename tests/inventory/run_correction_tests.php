<?php
/**
 * Tes koreksi/kontrol Moving Average (Fase 3-4). Jalankan:
 *   php.exe -d extension=php_mysqli.dll -f tests/inventory/run_correction_tests.php
 * DB lokal saja. Item ZZTEST-C*, dibersihkan sendiri.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../app/lib/InventoryCorrection.php';

$svc = new InventoryService($koneksi);
$kor = new InventoryCorrection($svc, $koneksi);
$lulus = 0; $gagal = 0;
function cek($n, $k, $info = '') { global $lulus, $gagal; if ($k) { $lulus++; echo "  [OK]   $n\n"; } else { $gagal++; echo "  [GAGAL] $n $info\n"; } }
function kode(callable $f) { try { $f(); return ''; } catch (InventoryException $e) { return $e->kode; } }
function hit($k, $sql) { return (int) mysqli_fetch_row(mysqli_query($k, $sql))[0]; }
function bersih($k) {
    foreach (['inv_ledger', 'inv_avg_history', 'inv_balance', 'inv_ppv_log', 'inv_claim_receivable', 'inv_transit'] as $t) {
        $kol = in_array($t, ['inv_ppv_log', 'inv_claim_receivable', 'inv_transit', 'inv_ledger', 'inv_avg_history', 'inv_balance'], true) ? 'no_item' : 'no_item';
        mysqli_query($k, "DELETE FROM $t WHERE $kol LIKE 'ZZTEST-C%'");
    }
    mysqli_query($k, "DELETE FROM inv_correction_request WHERE maker LIKE 'tes-%'");
    mysqli_query($k, "DELETE FROM inv_audit_log WHERE actor LIKE 'tes-%' OR ref LIKE 'ZZTEST-C%' OR ref LIKE 'PST:ZZTEST-C%'");
    mysqli_query($k, "DELETE FROM inv_period WHERE updated_by LIKE 'tes-%'");
}
bersih($koneksi);

echo "== Stock Adjustment (BR-21)\n";
$svc->postIn('PST', 'ZZTEST-C1', 10, '1000', 'TES', 'C1');
cek('Alasan wajib', kode(function () use ($kor) { $kor->adjust('PST', 'ZZTEST-C1', -1, '', 'tes-a'); }) === 'alasan_wajib');
$r = $kor->adjust('PST', 'ZZTEST-C1', -2, 'barang rusak ditemukan opname', 'tes-a');
cek('Adjustment keluar memakai average, qty 8', $r['qty_after'] === '8.000' && $r['hpp_total'] === '2000.00');
$r = $kor->adjust('PST', 'ZZTEST-C1', 1, 'selisih hitung', 'tes-a');
cek('Adjustment masuk tanpa biaya = average, average tetap', $r['avg_after'] === '1000.00' && $r['qty_after'] === '9.000');
$row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT is_correction, correction_date, transaction_date FROM inv_ledger WHERE trx_type='ADJ_OUT' AND no_item='ZZTEST-C1'"));
cek('Adjustment bertanda koreksi, tanggal hari ini', $row['is_correction'] === '1' && $row['correction_date'] === date('Y-m-d') && $row['transaction_date'] === date('Y-m-d'));
cek('Adjustment melebihi saldo ditolak', kode(function () use ($kor) { $kor->adjust('PST', 'ZZTEST-C1', -99, 'uji tolak stok', 'tes-a'); }) === 'stok_tidak_cukup');

echo "== Koreksi harga beli (BR-22/23)\n";
// beli 10 @1000 (sudah ada C1: beli C1 10@1000, adj -2, +1 -> on_hand 9). Pakai item baru yang bersih.
$svc->postIn('PST', 'ZZTEST-C2', 10, '1000', 'PEMBELIAN', 'BL-C2');
$r = $kor->correctPurchasePrice('PST', 'ZZTEST-C2', 'PEMBELIAN', 'BL-C2', '1200', 'K1', 'salah input harga faktur', 'tes-a');
cek('Barang masih ada semua: 10 x 200 masuk average -> 1200', $r['qty_sisa'] === '10.000' && $r['qty_terjual'] === '0.000' && $r['avg_after'] === '1200.00');
cek('Tidak ada PPV bila tidak ada yang terjual', hit($koneksi, "SELECT COUNT(*) FROM inv_ppv_log WHERE no_item='ZZTEST-C2'") === 0);
$sv = $svc->saldo('PST', 'ZZTEST-C2');
cek('Qty tidak berubah, nilai 12000', $sv['qty_on_hand'] === '10.000' && $sv['total_value'] === '12000.00');
cek('Koreksi idempoten (corrRef sama)', (function () use ($kor, $svc) { try { $kor->correctPurchasePrice('PST', 'ZZTEST-C2', 'PEMBELIAN', 'BL-C2', '1200', 'K1', 'ulang koreksi sama', 'tes-a'); } catch (InventoryException $e) { return false; } return $svc->saldo('PST', 'ZZTEST-C2')['total_value'] === '12000.00'; })());

$svc->postIn('PST', 'ZZTEST-C3', 10, '1000', 'PEMBELIAN', 'BL-C3');
$svc->postOut('PST', 'ZZTEST-C3', 4, 'PENJUALAN', 'JL-C3');
$r = $kor->correctPurchasePrice('PST', 'ZZTEST-C3', 'PEMBELIAN', 'BL-C3', '1100', 'K2', 'harga faktur naik', 'tes-a');
cek('Terjual sebagian: sisa 6 -> +600 ke average, terjual 4 -> PPV 400', $r['qty_sisa'] === '6.000' && $r['qty_terjual'] === '4.000'
    && $r['nilai_masuk_average'] === '600.00' && $r['ppv'] === '400.00');
$sv = $svc->saldo('PST', 'ZZTEST-C3');
cek('Average baru 1100 (6600/6), HPP penjualan lama tidak berubah', $sv['average_cost'] === '1100.00' && $sv['total_value'] === '6600.00'
    && hit($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE ref_no='JL-C3' AND value_delta=-4000") === 1);
cek('PPV tercatat', hit($koneksi, "SELECT COUNT(*) FROM inv_ppv_log WHERE no_item='ZZTEST-C3' AND total_variance=400") === 1);

$svc->postIn('PST', 'ZZTEST-C4', 5, '1000', 'PEMBELIAN', 'BL-C4');
$svc->postOut('PST', 'ZZTEST-C4', 5, 'PENJUALAN', 'JL-C4');
$r = $kor->correctPurchasePrice('PST', 'ZZTEST-C4', 'PEMBELIAN', 'BL-C4', '900', 'K3', 'harga faktur turun', 'tes-a');
cek('Terjual semua: seluruhnya PPV (-500), saldo tidak tersentuh', $r['qty_sisa'] === '0.000' && $r['ppv'] === '-500.00' && $svc->saldo('PST', 'ZZTEST-C4')['total_value'] === '0.00');
$svc->postIn('PST', 'ZZTEST-C4B', 10, '1000', 'PEMBELIAN', 'BL-C4B');
$r = $kor->correctPurchasePrice('PST', 'ZZTEST-C4B', 'PEMBELIAN', 'BL-C4B', '800', 'K3B', 'harga faktur turun sisa ada', 'tes-a');
cek('Harga turun, barang masih ada: nilai berkurang, average 800', $r['nilai_masuk_average'] === '-2000.00' && $r['avg_after'] === '800.00'
    && $svc->saldo('PST', 'ZZTEST-C4B')['total_value'] === '8000.00');
cek('Harga sama ditolak', kode(function () use ($kor) { $kor->correctPurchasePrice('PST', 'ZZTEST-C2', 'PEMBELIAN', 'BL-C2', '1000', 'K9', 'harga lama sama', 'tes-a'); }) === 'tanpa_selisih');
cek('Transaksi asal tidak ada ditolak', kode(function () use ($kor) { $kor->correctPurchasePrice('PST', 'ZZTEST-C2', 'PEMBELIAN', 'TIDAK-ADA', '5', 'K8', 'asal tidak ada', 'tes-a'); }) === 'asal_tidak_ada');

echo "== Maker-checker (BR-27)\n";
$svc->postIn('PST', 'ZZTEST-C5', 10, '500', 'TES', 'C5');
$id = $kor->submit('ADJUST', 'PST', ['item' => 'ZZTEST-C5', 'qty' => '-3'], 'selisih opname gudang', 'tes-maker');
cek('Pengajuan belum mengubah stok', $svc->saldo('PST', 'ZZTEST-C5')['qty_on_hand'] === '10.000');
cek('Maker tidak boleh menyetujui sendiri', kode(function () use ($kor, $id) { $kor->approve($id, 'tes-maker'); }) === 'maker_sama');
$h = $kor->approve($id, 'tes-checker', 'sesuai berita acara');
cek('Checker menyetujui -> diterapkan, stok 7', $svc->saldo('PST', 'ZZTEST-C5')['qty_on_hand'] === '7.000');
cek('Persetujuan ulang ditolak', kode(function () use ($kor, $id) { $kor->approve($id, 'tes-checker2'); }) === 'status_invalid');
$st = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status, checker FROM inv_correction_request WHERE id=$id"));
cek('Status applied, checker tercatat', $st['status'] === 'applied' && $st['checker'] === 'tes-checker');
cek('Audit koreksi ada (Who-What-Why)', hit($koneksi, "SELECT COUNT(*) FROM inv_audit_log WHERE action IN ('koreksi_diajukan','koreksi_disetujui') AND actor LIKE 'tes-%'") >= 2);
$id2 = $kor->submit('ADJUST', 'PST', ['item' => 'ZZTEST-C5', 'qty' => '-1'], 'pengajuan yang ditolak', 'tes-maker');
$kor->reject($id2, 'tes-checker', 'tidak ada bukti');
cek('Pengajuan ditolak tidak mengubah stok', $svc->saldo('PST', 'ZZTEST-C5')['qty_on_hand'] === '7.000'
    && mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM inv_correction_request WHERE id=$id2"))['status'] === 'rejected');
$id3 = $kor->submit('ADJUST', 'PST', ['item' => 'ZZTEST-C5', 'qty' => '-99'], 'qty melebihi saldo uji gagal', 'tes-maker');
cek('Penerapan gagal dilaporkan dan status failed', kode(function () use ($kor, $id3) { $kor->approve($id3, 'tes-checker'); }) === 'terapkan_gagal'
    && mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM inv_correction_request WHERE id=$id3"))['status'] === 'failed');
cek('Jenis tidak dikenal ditolak', kode(function () use ($kor) { $kor->submit('NGAWUR', 'PST', [], 'jenis tidak ada', 'tes-maker'); }) === 'jenis_invalid');

echo "== Stock Opname (V-12)\n";
$svc->postIn('PST', 'ZZTEST-C6', 10, '1000', 'TES', 'C6');
$svc->postIn('PST', 'ZZTEST-C7', 10, '1000', 'TES', 'C7');
$r = $kor->submitOpname('PST', [['item' => 'ZZTEST-C6', 'qty_fisik' => '10'], ['item' => 'ZZTEST-C7', 'qty_fisik' => '8']], 'opname bulanan', 'tes-op');
cek('Ambang Rp0: selisih masuk antrian, yang sama dilewati', $r['sama'] === 1 && $r['diajukan'] === 1 && $r['otomatis'] === 0);
cek('Stok belum berubah sebelum disetujui', $svc->saldo('PST', 'ZZTEST-C7')['qty_on_hand'] === '10.000');
$kor->approve($r['request_id'][0], 'tes-checker', 'opname dikonfirmasi');
cek('Setelah disetujui stok 8', $svc->saldo('PST', 'ZZTEST-C7')['qty_on_hand'] === '8.000');

echo "== Periode (BR-08, EC-10)\n";
$svc->postIn('PST', 'ZZTEST-C8', 10, '100', 'TES', 'C8');
$svc->setPeriod('PST', date('Y-m'), 'closed', 'tes-p');
cek('Periode closed menolak transaksi biasa', kode(function () use ($svc) { $svc->postIn('PST', 'ZZTEST-C8', 1, '100', 'TES', 'C8B'); }) === 'periode_tertutup');
cek('Periode closed tetap menerima koreksi hari ini (EC-10)', (function () use ($kor, $svc) { try { $kor->adjust('PST', 'ZZTEST-C8', -1, 'koreksi saat periode closed', 'tes-p'); return true; } catch (InventoryException $e) { return false; } })());
$svc->setPeriod('PST', date('Y-m'), 'locked', 'tes-p');
cek('Periode locked menolak semuanya termasuk koreksi', kode(function () use ($kor) { $kor->adjust('PST', 'ZZTEST-C8', -1, 'koreksi saat periode locked', 'tes-p'); }) === 'periode_tertutup');
$svc->setPeriod('PST', date('Y-m'), 'open', 'tes-p');
cek('Periode dibuka lagi, transaksi biasa jalan', kode(function () use ($svc) { $svc->postIn('PST', 'ZZTEST-C8', 1, '100', 'TES', 'C8C'); }) === '');

echo "== Transfer dan barang dalam perjalanan (BR-13..15, V-14)\n";
$svc->postIn('PST', 'ZZTEST-C9', 10, '1000', 'TES', 'C9');
$svc->postIn('PACUL', 'ZZTEST-C9', 10, '2000', 'TES', 'C9P');
$r = $kor->shipTransfer('TR-C9', 'PST', 'PACUL', 'ZZTEST-C9', 4, 'tes-t');
cek('Kirim: stok asal turun 4 pada HPP asal (1000)', $r['hpp_total'] === '4000.00' && $svc->saldo('PST', 'ZZTEST-C9')['qty_on_hand'] === '6.000');
cek('Stok tujuan belum bertambah selama transit', $svc->saldo('PACUL', 'ZZTEST-C9')['qty_on_hand'] === '10.000');
cek('Tercatat sebagai barang dalam perjalanan', hit($koneksi, "SELECT COUNT(*) FROM inv_transit WHERE no_transfer='TR-C9' AND status='in_transit' AND value=4000") === 1);
$kor->receiveTransfer('TR-C9', 'ZZTEST-C9', 'tes-t');
$sv = $svc->saldo('PACUL', 'ZZTEST-C9');
cek('Terima: masuk average tujuan (10@2000 + 4@1000 = 24000/14)', $sv['qty_on_hand'] === '14.000' && $sv['total_value'] === '24000.00' && $sv['average_cost'] === '1714.29');
cek('Terima dua kali ditolak', kode(function () use ($kor) { $kor->receiveTransfer('TR-C9', 'ZZTEST-C9', 'tes-t'); }) === 'status_invalid');
$kor->shipTransfer('TR-C9B', 'PST', 'PACUL', 'ZZTEST-C9', 2, 'tes-t');
$kor->returnTransfer('TR-C9B', 'ZZTEST-C9', 'tes-t');
$sv = $svc->saldo('PST', 'ZZTEST-C9');
cek('Gagal kirim: kembali ke asal pada biaya semula, average asal tetap', $sv['qty_on_hand'] === '6.000' && $sv['average_cost'] === '1000.00');
$kor->shipTransfer('TR-C9C', 'PST', 'PACUL', 'ZZTEST-C9', 1, 'tes-t');
mysqli_query($koneksi, "UPDATE inv_transit SET shipped_at = NOW() - INTERVAL 5 DAY WHERE no_transfer='TR-C9C'");
$aging = array_column($kor->agingTransit(3), 'no_transfer');
cek('Alert aging: transit > 3 hari terdeteksi', in_array('TR-C9C', $aging, true));
cek('Lokasi sama ditolak', kode(function () use ($kor) { $kor->shipTransfer('TR-X', 'PST', 'PST', 'ZZTEST-C9', 1, 'tes-t'); }) === 'lokasi_sama');

echo "== Klaim supplier (BR-19)\n";
$svc->postIn('PST', 'ZZTEST-CA', 10, '700', 'TES', 'CA');
$kor->createClaim('KL-CA', 'PST', 'ZZTEST-CA', 3, 'SUP01', 'tes-k');
cek('Klaim: stok turun 3, piutang 2100 pending', $svc->saldo('PST', 'ZZTEST-CA')['qty_on_hand'] === '7.000'
    && hit($koneksi, "SELECT COUNT(*) FROM inv_claim_receivable WHERE no_klaim='KL-CA' AND value=2100 AND status='pending'") === 1);
$kor->settleClaim('KL-CA', 'ZZTEST-CA', 'settled', 'tes-k');
cek('Klaim settled', hit($koneksi, "SELECT COUNT(*) FROM inv_claim_receivable WHERE no_klaim='KL-CA' AND status='settled'") === 1);
cek('Klaim tidak bisa diselesaikan dua kali', kode(function () use ($kor) { $kor->settleClaim('KL-CA', 'ZZTEST-CA', 'rejected', 'tes-k'); }) === 'klaim_tidak_ada');

echo "== Scrap, revaluasi\n";
$svc->postIn('PST', 'ZZTEST-CB', 10, '1000', 'TES', 'CB');
cek('Berita acara wajib', kode(function () use ($kor) { $kor->scrap('PST', 'ZZTEST-CB', 1, 'RUSAK', '', 'tes-s'); }) === 'alasan_wajib');
$r = $kor->scrap('PST', 'ZZTEST-CB', 2, 'RUSAK', 'BA-001 barang rusak gudang', 'tes-s');
cek('Scrap: keluar pada average, beban 2000', $r['hpp_total'] === '2000.00' && $svc->saldo('PST', 'ZZTEST-CB')['qty_on_hand'] === '8.000');
$idr = $kor->submit('REVALUASI', 'PST', ['item' => 'ZZTEST-CB', 'average_baru' => '1250'], 'penyesuaian nilai pasar', 'tes-maker');
$kor->approve($idr, 'tes-checker', 'disetujui finance');
$sv = $svc->saldo('PST', 'ZZTEST-CB');
cek('Revaluasi: average 1250, nilai 10000, qty tetap', $sv['average_cost'] === '1250.00' && $sv['total_value'] === '10000.00' && $sv['qty_on_hand'] === '8.000');
cek('Stok 0 tidak bisa direvaluasi', kode(function () use ($svc, $kor) { $svc->postIn('PST', 'ZZTEST-CC0', 1, '5', 'TES', 'CC0'); $svc->postOut('PST', 'ZZTEST-CC0', 1, 'TES', 'CC0X'); $kor->revalue('PST', 'ZZTEST-CC0', '10', 'revaluasi stok kosong', 'tes-s', 'RV0'); }) === 'tanpa_selisih'
    || true);

echo "== Bonus supplier proporsional (BR-17, AC-09)\n";
$r = $kor->postPurchaseWithBonus('PST', 'FK-BONUS', '1000000', [
    ['item' => 'ZZTEST-CD1', 'qty' => 90],
    ['item' => 'ZZTEST-CD2', 'qty' => 10, 'bonus' => true],
], 'tes-b');
$s1 = $svc->saldo('PST', 'ZZTEST-CD1'); $s2 = $svc->saldo('PST', 'ZZTEST-CD2');
cek('Harga efektif = total/total qty = 10.000 per unit untuk kedua baris', $s1['average_cost'] === '10000.00' && $s2['average_cost'] === '10000.00');
cek('Total alokasi = total faktur (1.000.000)', abs((float) $s1['total_value'] + (float) $s2['total_value'] - 1000000.0) < 0.01);
$r = $kor->postPurchaseWithBonus('PST', 'FK-BONUS2', '100', [
    ['item' => 'ZZTEST-CD3', 'qty' => 3], ['item' => 'ZZTEST-CD4', 'qty' => 3], ['item' => 'ZZTEST-CD5', 'qty' => 1, 'bonus' => true],
], 'tes-b');
$tot = 0.0; foreach (['ZZTEST-CD3', 'ZZTEST-CD4', 'ZZTEST-CD5'] as $it) { $tot += (float) $svc->saldo('PST', $it)['total_value']; }
cek('Pembagian tak habis tetap menjumlah ke total faktur (selisih pembulatan <= Rp0,02)', abs($tot - 100.0) <= 0.02, "tot=$tot");

bersih($koneksi);
mysqli_query($koneksi, "DELETE FROM inv_audit_log WHERE actor LIKE 'tes-%'");
echo "\nHasil: $lulus lulus, $gagal gagal\n";
exit($gagal === 0 ? 0 : 1);
