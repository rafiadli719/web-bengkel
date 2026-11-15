<?php
/**
 * Muat ulang data satu cabang dari fitmotor_center (warehouse Access) ke fitmotor_dbbengkel.
 * Rencana: docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md
 *
 * Aturan:
 *  - Data lama hasil impor Access (kode cabang lama PSL/PCL/CDT/TRM) DIBUANG, diganti isi center.
 *  - Baris asli input web (kode cabang kanonik, sudah ada sebelum reload pertama) DIPERTAHANKAN.
 *    Bila nomornya bentrok dengan center, baris center dilewati dan dicatat.
 *  - Sebelum hapus, semua baris cabang itu disalin ke bak_cr<tag>_<cabang>_<tabel> (sekali, tidak ditimpa).
 *  - Master global (pelanggan, kendaraan, item, supplier, mekanik) hanya DITAMBAH bila belum ada (tidak menimpa).
 *  - Kartu stok (tbstok) dibangun ulang dari transaksi center + baris rekonsiliasi supaya saldo = tblitem.Quantity Access.
 *
 * Sumber dibaca read-only lewat env (tanpa nilai default untuk kredensial):
 *   CENTER_DB_HOST, CENTER_DB_PORT, CENTER_DB_USER, CENTER_DB_PASS, CENTER_DB_NAME
 * Target memakai config/db_env.php (DB_HOST/DB_USER/DB_PASS/DB_NAME).
 *
 * Pakai:
 *   php reload_cabang.php --cabang=PESALAKAN --mode=preflight
 *   php reload_cabang.php --cabang=PESALAKAN --mode=run [--only=servis_header,servis_barang,stok]
 *   php reload_cabang.php --cabang=PESALAKAN --mode=verify
 *   php reload_cabang.php --cabang=PESALAKAN --mode=rollback
 *   --tag=1115 (default: MMDD hari ini) menentukan nama tabel backup; rollback wajib pakai tag yang sama.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '2G');
set_time_limit(0);
mysqli_report(MYSQLI_REPORT_OFF);

$opt = [];
foreach (array_slice($argv, 1) as $a) { if (preg_match('/^--([a-z_]+)=(.*)$/', $a, $m)) { $opt[$m[1]] = $m[2]; } }
$CAB  = strtoupper(trim($opt['cabang'] ?? ''));
$MODE = $opt['mode'] ?? 'preflight';
$TAG  = preg_replace('/[^0-9a-z]/i', '', $opt['tag'] ?? date('md'));
$ONLY = isset($opt['only']) ? array_filter(explode(',', $opt['only'])) : [];
if (!in_array($MODE, ['preflight', 'run', 'verify', 'rollback'], true)) { fwrite(STDERR, "mode: preflight|run|verify|rollback\n"); exit(1); }

// Kode cabang lama hasil impor Access sebelumnya (bukti: tblservice/tbstok/tblservis_* 2026-11-15).
const LEGACY = ['PESALAKAN' => ['PSL'], 'PACUL' => ['PCL'], 'CIKDITIRO' => ['CDT'], 'TRAYEMAN' => ['TRM'], 'PST' => []];
if (!array_key_exists($CAB, LEGACY)) { fwrite(STDERR, "cabang harus salah satu: " . implode(',', array_keys(LEGACY)) . "\n"); exit(1); }

// ---------- koneksi ----------
$envFile = __DIR__ . '/../../config/db_env.php';
if (is_file($envFile)) { include $envFile; }
$T = mysqli_connect(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME') ?: 'fitmotor_dbbengkel');
if (!$T) { fwrite(STDERR, "Koneksi target gagal: " . mysqli_connect_error() . "\n"); exit(1); }
mysqli_set_charset($T, 'latin1');
foreach (['CENTER_DB_HOST', 'CENTER_DB_PORT', 'CENTER_DB_USER', 'CENTER_DB_PASS'] as $k) {
    if (getenv($k) === false || getenv($k) === '') { fwrite(STDERR, "Env $k wajib diisi\n"); exit(1); }
}
$S = mysqli_connect(getenv('CENTER_DB_HOST'), getenv('CENTER_DB_USER'), getenv('CENTER_DB_PASS'), getenv('CENTER_DB_NAME') ?: 'fitmotor_center', (int)getenv('CENTER_DB_PORT'));
if (!$S) { fwrite(STDERR, "Koneksi sumber gagal: " . mysqli_connect_error() . "\n"); exit(1); }
mysqli_set_charset($S, 'latin1');

function tq($sql) { global $T; $r = mysqli_query($T, $sql); if ($r === false) { throw new RuntimeException("Target SQL: " . mysqli_error($T) . "\n  " . substr($sql, 0, 400)); } return $r; }
function sq($sql, $unbuffered = false) { global $S; $r = mysqli_query($S, $sql, $unbuffered ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT); if ($r === false) { throw new RuntimeException("Sumber SQL: " . mysqli_error($S) . "\n  " . substr($sql, 0, 400)); } return $r; }
function tv($sql) { $r = tq($sql); $x = mysqli_fetch_row($r); return $x ? $x[0] : null; }
function sv($sql) { $r = sq($sql); $x = mysqli_fetch_row($r); return $x ? $x[0] : null; }
function e($v) { global $T; return mysqli_real_escape_string($T, (string)$v); }
function say($s) { echo $s . "\n"; flush(); }

$REF = tv("SELECT cabang_ref_kode FROM tbcabang WHERE kode_cabang='" . e($CAB) . "'");
if (!$REF) { fwrite(STDERR, "cabang_ref_kode untuk $CAB tidak ada di tbcabang\n"); exit(1); }
$CODES = array_merge([$CAB], LEGACY[$CAB]);
$IN_CODES = "'" . implode("','", array_map('e', $CODES)) . "'";
$IN_LEGACY = LEGACY[$CAB] ? "'" . implode("','", array_map('e', LEGACY[$CAB])) . "'" : null;
$RUN = 'CR' . date('YmdHis') . '-' . $CAB;

// ---------- helper nilai ----------
function d($v) { $v = trim((string)$v); return ($v === '' || strpos($v, '0000') === 0) ? '' : substr($v, 0, 10); }
function hm($v) { $v = trim((string)$v); return strlen($v) >= 16 ? substr($v, 11, 5) : ''; }
function n($v) { return $v === null || $v === '' ? 0 : (float)$v; }
function i($v) { return (int)round(n($v)); }
function s($v, $len = 0) { $v = trim((string)$v); return $len ? substr($v, 0, $len) : $v; }
function lunas($r, $totKey) { return (n($r['Pembayaran']) + n($r['JumlahBayar']) >= n($r[$totKey]) - 1) ? '1' : '0'; }
// Access Status 3 = lunas/final -> web status '4' + status_servis 'bayar' (sama dengan servis-reguler-byr.php)
function status_servis($st) { $st = trim((string)$st); return $st === '3' ? ['4', 'bayar'] : ($st === '2' ? ['2', 'diproses'] : ['1', 'datang']); }

// ---------- metadata kolom target untuk isi otomatis kolom NOT NULL tanpa default ----------
$META = [];
function meta($table) {
    global $META;
    if (isset($META[$table])) { return $META[$table]; }
    $r = tq("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . e($table) . "'");
    $m = [];
    while ($x = mysqli_fetch_assoc($r)) { $m[$x['COLUMN_NAME']] = $x; }
    return $META[$table] = $m;
}
function filler($c) {
    $t = $c['DATA_TYPE'];
    if (in_array($t, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'double', 'float', 'decimal'], true)) { return 0; }
    if ($t === 'date') { return '1970-01-01'; }
    if ($t === 'datetime' || $t === 'timestamp') { return '1970-01-01 00:00:00'; }
    if ($t === 'time') { return '00:00:00'; }
    if ($t === 'enum' && preg_match("/^enum\\('([^']*)'/", $c['COLUMN_TYPE'], $m)) { return $m[1]; }
    return '';
}
function lengkapi($table, $row) {
    $meta = meta($table);
    foreach ($row as $k => $v) { if (!isset($meta[$k])) { unset($row[$k]); } }
    foreach ($meta as $name => $c) {
        if (strpos($c['EXTRA'], 'auto_increment') !== false) { unset($row[$name]); continue; }
        if (!array_key_exists($name, $row) || $row[$name] === null) {
            if ($c['IS_NULLABLE'] === 'NO' && $c['COLUMN_DEFAULT'] === null) { $row[$name] = filler($c); }
            else { unset($row[$name]); }
        } elseif ($row[$name] === '' && in_array($c['DATA_TYPE'], ['date', 'datetime', 'timestamp', 'time'], true)) {
            $row[$name] = $c['IS_NULLABLE'] === 'YES' ? null : filler($c);
        } elseif (in_array($c['DATA_TYPE'], ['varchar', 'char'], true) && $c['CHARACTER_MAXIMUM_LENGTH'] && strlen((string)$row[$name]) > (int)$c['CHARACTER_MAXIMUM_LENGTH']) {
            $row[$name] = substr((string)$row[$name], 0, (int)$c['CHARACTER_MAXIMUM_LENGTH']);
        }
    }
    ksort($row);
    return $row;
}

// ---------- definisi dataset ----------
$JEMPUT = [];
$DS = [];
$DS[] = ['key' => 'servis_jemputantar', 'target' => 'tblservice_jemputantar', 'keycols' => ['no_service'],
    'src' => "SELECT * FROM tblservice_jemputantar WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_service' => s($r['NoService']), 'kd_cabang' => $CAB, 'tanggal' => d($r['Tanggal']), 'no_pelanggan' => s($r['NoPelanggan'], 20),
            'no_polisi' => s($r['NoPolisi'], 20), 'jam' => strlen((string)$r['JAM']) >= 16 ? substr($r['JAM'], 11, 8) : null, 'keterangan' => s($r['Keterangan'], 255)];
    }];
$DS[] = ['key' => 'servis_header', 'target' => 'tblservice', 'keycols' => ['no_service'],
    'src' => "SELECT * FROM tblservice WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        global $JEMPUT;
        [$st, $sts] = status_servis($r['Status']);
        $ket = s($r['keterangan']);
        return ['no_service' => s($r['NoService']), 'tanggal' => d($r['Tanggal']), 'jam' => hm($r['Tanggal']),
            'no_pelanggan' => s($r['NoPelanggan'], 20), 'no_polisi' => s($r['NoPolisi'], 20), 'status' => $st, 'kd_cabang' => $CAB,
            'id_user' => 1, 'total' => n($r['SubTotal']), 'diskon_persen' => i($r['Diskon']), 'diskon_nom' => n($r['TotalDiskon']),
            'ppn_persen' => i($r['Pajak']), 'ppn_nom' => n($r['TotalPajak']), 'total_grand' => n($r['TotalAkhir']), 'bayar' => n($r['Pembayaran']),
            'kembali' => 0, 'jenis_bayar' => 1, 'total_waktu' => i($r['TotalWaktu']), 'status_jemput' => isset($JEMPUT[s($r['NoService'])]) ? '1' : '0',
            'keterangan' => substr($ket, 0, 100), 'km_skr' => i($r['KmSekarang']), 'mekanik1' => s($r['Mekanik1'], 50), 'mekanik2' => s($r['Mekanik2'], 50),
            'mekanik3' => s($r['Mekanik3'], 50), 'mekanik4' => s($r['Mekanik4'], 50), 'biayaM1' => n($r['BiayaM1']), 'biayaM2' => n($r['BiayaM2']),
            'biayaM3' => n($r['BiayaM3']), 'biayaM4' => n($r['BiayaM4']), 'km_berikut' => i($r['KmBerikut']), 'subtotal_jasa' => n($r['SubTotalJasa']),
            'subtotal_item' => n($r['SubTotalItem']), 'subtotal' => n($r['SubTotal']), 'total_diskon' => n($r['TotalDiskon']), 'total_pajak' => n($r['TotalPajak']),
            'total_akhir' => n($r['TotalAkhir']), 'status_servis' => $sts, 'boleh_dp' => 0, 'metode_pembayaran' => 'Tunai', 'total_retur' => 0,
            'catatan' => strlen($ket) > 100 ? $ket : null, 'created_at' => s($r['Tanggal']) ?: null];
    }];
$DS[] = ['key' => 'servis_barang', 'target' => 'tblservis_barang', 'keycols' => ['no_service', 'nobaris', 'no_item'],
    'src' => "SELECT d.*, f.hpp FROM tblserviceitemdt d
              LEFT JOIN (SELECT NoTransaksi, NoItem, SUM(HargaPokok * Jumlah) / NULLIF(SUM(Jumlah), 0) hpp
                         FROM tblserviceitemdtfifo WHERE kode_cabang='{ref}' GROUP BY NoTransaksi, NoItem) f
                ON f.NoTransaksi = d.NoService AND f.NoItem = d.NoItem
              WHERE d.kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_service' => s($r['NoService']), 'kd_cabang' => $CAB, 'nobaris' => i($r['NoBaris']), 'no_item' => s($r['NoItem'], 50),
            'quantity' => i($r['Quantity']), 'qty_retur' => i($r['QtyRetur']), 'harga_jual' => n($r['Harga']), 'potongan' => n($r['Potongan']),
            'total' => n($r['Total']), 'asal_barang' => 'SERVIS', 'harga_pokok' => round(n($r['hpp']), 2)];
    }];
$DS[] = ['key' => 'servis_jasa', 'target' => 'tblservis_jasa', 'keycols' => ['no_service', 'nobaris', 'no_item'],
    'src' => "SELECT * FROM tblservicejasadt WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_service' => s($r['NoService']), 'kd_cabang' => $CAB, 'nobaris' => i($r['NoBaris']), 'no_item' => s($r['NoItem'], 50),
            'waktu' => i($r['Waktu']), 'harga' => n($r['Harga']), 'potongan' => n($r['Potongan']), 'total' => n($r['Total']), 'qty_retur' => 0];
    }];
$DS[] = ['key' => 'servis_advisor', 'target' => 'tblservice_advisor', 'keycols' => ['no_service'],
    'src' => "SELECT * FROM tblservice_advisor WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) { return ['no_service' => s($r['NoService']), 'kd_cabang' => $CAB, 'advisor' => s($r['Advisor'], 20)]; }];
$DS[] = ['key' => 'beli_header', 'target' => 'tblpembelian_header', 'keycols' => ['notransaksi'],
    'src' => "SELECT * FROM tblpembelianheader WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['notransaksi' => s($r['NoTransaksi']), 'status' => s($r['Status'], 50), 'carabayar' => s($r['CaraBayar'], 50), 'tanggal' => d($r['Tanggal']),
            'no_order' => s($r['NoOrder'], 50), 'tanggal_order' => d($r['TanggalOrder']), 'no_supplier' => s($r['NoSupplier'], 20), 'note' => s($r['note'], 50),
            'total_qty_order' => i($r['TotalQtyOrder']), 'total_qty' => i($r['TotalQty']), 'total_beli' => n($r['TotalBeli']), 'diskon' => n($r['Diskon']),
            'total_diskon' => n($r['TotalDiskon']), 'pajak' => n($r['Pajak']), 'total_pajak' => n($r['TotalPajak']), 'total_akhir' => n($r['TotalAkhir']),
            'total_retur' => n($r['TotalRetur']), 'pembayaran' => n($r['Pembayaran']), 'tanggal_jt' => d($r['TanggalJT']), 'tanggal_lunas' => d($r['TanggalLunas']),
            'jumlah_bayar' => i($r['JumlahBayar']), 'user' => s($r['User'], 50), 'Id_tabel' => s($r['IDTabel'], 50), 'kd_cabang' => $CAB,
            'status_lunas' => lunas($r, 'TotalAkhir'), 'lama_hari' => 0];
    }];
$DS[] = ['key' => 'beli_detail', 'target' => 'tblpembelian_detail', 'keycols' => ['no_transaksi', 'nobaris', 'no_item'],
    'src' => "SELECT * FROM tblpembeliandetail WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_transaksi' => s($r['NoTransaksi']), 'nobaris' => i($r['NoBaris']), 'no_item' => s($r['NoItem'], 20), 'qty_order' => i($r['QtyOrder']),
            'quantity' => i($r['Quantity']), 'qty_retur' => i($r['QtyRetur']), 'harga_pokok' => n($r['HargaPokok']), 'potongan' => n($r['Potongan']),
            'harga_sp' => n($r['HargaSP']), 'total' => n($r['Total']), 'sts_order' => (string)i($r['StsOrder']), 'id_inv' => s($r['IDInv'], 50),
            'user' => '', 'kd_cabang' => $CAB, 'status_trx' => '1'];
    }];
$DS[] = ['key' => 'jual_header', 'target' => 'tblpenjualan_header', 'keycols' => ['notransaksi'],
    'src' => "SELECT * FROM tblpenjualanheader WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['notransaksi' => s($r['NoTransaksi']), 'status' => s($r['Status'], 50), 'carabayar' => s($r['CaraBayar'], 50), 'tanggal' => d($r['Tanggal']),
            'no_order' => s($r['NoOrder'], 50), 'tanggal_order' => d($r['TanggalOrder']), 'no_sales' => s($r['NoSales'], 50), 'no_pelanggan' => s($r['NoPelanggan'], 50),
            'note' => s($r['note'], 50), 'total_qty_order' => i($r['TotalQtyOrder']), 'total_qty' => i($r['TotalQty']), 'total_jual' => n($r['TotalJual']),
            'diskon' => n($r['Diskon']), 'total_diskon' => n($r['TotalDiskon']), 'pajak' => n($r['Pajak']), 'total_pajak' => n($r['TotalPajak']),
            'total_akhir' => n($r['TotalAkhir']), 'total_retur' => n($r['TotalRetur']), 'pembayaran' => n($r['Pembayaran']), 'tanggal_jt' => d($r['TanggalJT']),
            'tanggal_lunas' => d($r['TanggalLunas']), 'jumlah_bayar' => i($r['JumlahBayar']), 'user' => s($r['User'], 50), 'id_tabel' => s($r['IDTabel'], 50),
            'kd_cabang' => $CAB, 'status_lunas' => lunas($r, 'TotalAkhir'), 'lama_hari' => 0];
    }];
$DS[] = ['key' => 'jual_detail', 'target' => 'tblpenjualan_detail', 'keycols' => ['no_transaksi', 'nobaris', 'no_item'],
    'src' => "SELECT * FROM tblpenjualandetail WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_transaksi' => s($r['NoTransaksi']), 'nobaris' => i($r['NoBaris']), 'no_item' => s($r['NoItem'], 20), 'qty_order' => i($r['QtyOrder']),
            'quantity' => i($r['Quantity']), 'qty_retur' => i($r['QtyRetur']), 'harga_jual' => n($r['HargaJual']), 'potongan' => n($r['Potongan']),
            'harga_sp' => n($r['HargaSP']), 'harga_pokok' => n($r['HargaPokok']), 'total' => n($r['Total']), 'sts_order' => (string)i($r['StsOrder']),
            'user' => '', 'kd_cabang' => $CAB, 'status_trx' => '1'];
    }];
$SKIP_PO = [];
$DS[] = ['key' => 'po_header', 'target' => 'tblorder_header', 'keycols' => ['no_order'], 'pk_global' => 'no_order',
    'src' => "SELECT * FROM tblorderheader WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_order' => s($r['NoOrder']), 'status' => s($r['Status'], 50), 'tanggal' => d($r['Tanggal']), 'tglkirim' => d($r['TglKirim']),
            'no_supplier' => s($r['NoSupplier'], 20), 'note' => s($r['note'], 50), 'total_qty' => i($r['TotalQty']), 'total_terima' => n($r['TotalTerima']),
            'total_order' => n($r['TotalOrder']), 'user' => s($r['User'], 50), 'Id_tabel' => s($r['IDTabel'], 50), 'kd_cabang' => $CAB,
            'status_pesanan' => n($r['TotalTerima']) >= n($r['TotalQty']) && n($r['TotalQty']) > 0 ? '1' : '0', 'tipe_trx' => 'ACCESS',
            'no_penjualan' => '', 'status_approval' => 'approved', 'po_type' => 'regular'];
    }];
$DS[] = ['key' => 'po_detail', 'target' => 'tblorder_detail', 'keycols' => ['no_order', 'nobaris', 'no_item'],
    'src' => "SELECT * FROM tblorderdetail WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        global $SKIP_PO;
        if (isset($SKIP_PO[s($r['NoOrder'])])) { return null; }
        return ['no_order' => s($r['NoOrder']), 'nobaris' => i($r['NoBaris']), 'no_item' => s($r['NoItem'], 20), 'quantity' => i($r['Quantity']),
            'qty_terima' => i($r['QtyTerima']), 'harga_pokok' => n($r['HargaPokok']), 'total' => n($r['Total']), 'user' => '', 'status_trx' => '1', 'kd_cabang' => $CAB];
    }];
$DS[] = ['key' => 'so_header', 'target' => 'tblorderjual_header', 'keycols' => ['no_order'],
    'src' => "SELECT * FROM tblorderjualheader WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_order' => s($r['NoOrder']), 'status' => s($r['Status'], 50), 'tanggal' => d($r['Tanggal']), 'no_sales' => s($r['NoSales'], 50),
            'no_pelanggan' => s($r['NoPelanggan'], 50), 'note' => s($r['note'], 50), 'total_qty' => i($r['TotalQty']), 'total_terima' => i($r['TotalTerima']),
            'total_jual' => n($r['TotalJual']), 'diskon' => i($r['Diskon']), 'total_diskon' => n($r['TotalDiskon']), 'pajak' => i($r['Pajak']),
            'total_pajak' => n($r['TotalPajak']), 'total_akhir' => n($r['TotalAkhir']), 'pembayaran' => n($r['Pembayaran']), 'user' => s($r['User'], 50),
            'id_tabel' => s($r['IDTabel'], 50), 'kd_cabang' => $CAB, 'tipe_trx' => 'ACCESS', 'order_ke' => '',
            'status_pesanan' => n($r['TotalTerima']) >= n($r['TotalQty']) && n($r['TotalQty']) > 0 ? '1' : '0'];
    }];
$DS[] = ['key' => 'so_detail', 'target' => 'tblorderjual_detail', 'keycols' => ['no_order', 'nobaris', 'no_item'],
    'src' => "SELECT * FROM tblorderjualdetail WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_order' => s($r['NoOrder']), 'nobaris' => i($r['NoBaris']), 'no_item' => s($r['NoItem'], 50), 'quantity' => i($r['Quantity']),
            'qty_terima' => i($r['QtyTerima']), 'harga_jual' => n($r['HargaJual']), 'potongan' => n($r['Potongan']), 'harga_sp' => n($r['HargaSP']),
            'harga_pokok' => n($r['HargaPokok']), 'total' => n($r['Total']), 'user' => '', 'kd_cabang' => $CAB, 'status_trx' => '1', 'margin_jual' => 0];
    }];
$DS[] = ['key' => 'hutang_header', 'target' => 'tblhutang_header', 'keycols' => ['no_transaksi'],
    'src' => "SELECT * FROM tblhutangheader WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_transaksi' => s($r['NoTransaksi']), 'tanggal' => d($r['Tanggal']), 'no_supplier' => s($r['NoSupplier'], 50), 'note' => s($r['Note'], 50),
            'total_bayar' => n($r['TotalBayar']), 'user' => s($r['User'], 50), 'id_tabel' => s($r['IDTabel'], 50), 'kd_cabang' => $CAB];
    }];
$DS[] = ['key' => 'hutang_detail', 'target' => 'tblhutang_detail', 'keycols' => ['no_transaksi', 'nobaris'],
    'src' => "SELECT * FROM tblhutangdetail WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_transaksi' => s($r['NoTransaksi']), 'kd_cabang' => $CAB, 'nobaris' => i($r['NoBaris']), 'no_pembelian' => s($r['NoPembelian'], 50),
            'keterangan' => s($r['Keterangan'], 100), 'jumlah_hutang' => n($r['JumlahHutang']), 'jumlah_bayar' => n($r['JumlahBayar']), 'status' => s($r['Status'], 20)];
    }];
$DS[] = ['key' => 'piutang_header', 'target' => 'tblpiutang_header', 'keycols' => ['no_transaksi'],
    'src' => "SELECT * FROM tblpiutangheader WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_transaksi' => s($r['NoTransaksi']), 'tanggal' => d($r['Tanggal']), 'no_pelanggan' => s($r['NoPelanggan'], 50), 'note' => s($r['note'], 50),
            'total_bayar' => n($r['TotalBayar']), 'user' => s($r['User'], 50), 'id_tabel' => s($r['IDTabel'], 50), 'kd_cabang' => $CAB];
    }];
$DS[] = ['key' => 'piutang_detail', 'target' => 'tblpiutang_detail', 'keycols' => ['no_transaksi', 'nobaris'],
    'src' => "SELECT * FROM tblpiutangdetail WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_transaksi' => s($r['NoTransaksi']), 'kd_cabang' => $CAB, 'nobaris' => i($r['NoBaris']), 'no_penjualan' => s($r['NoPenjualan'], 50),
            'keterangan' => s($r['Keterangan'], 100), 'jumlah_piutang' => n($r['JumlahPiutang']), 'jumlah_bayar' => n($r['JumlahBayar']), 'status' => s($r['Status'], 20)];
    }];
$DS[] = ['key' => 'retur_beli_header', 'target' => 'tblretur_pembelian_header', 'keycols' => ['noretur'],
    'src' => "SELECT * FROM tblreturbeliheader WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['noretur' => s($r['NoTransaksi']), 'nopembelian' => s($r['NoBeli'], 50), 'tanggal' => d($r['Tanggal']), 'note' => s($r['note'], 50),
            'total_qty_retur' => i($r['TotalQty']), 'total_retur' => n($r['TotalBeli']), 'diskon' => n($r['Diskon']), 'total_diskon' => n($r['TotalDiskon']),
            'pajak' => n($r['Pajak']), 'total_pajak' => n($r['TotalPajak']), 'total_akhir' => n($r['TotalAkhir']), 'user' => s($r['User'], 50),
            'kd_cabang' => $CAB, 'status_retur' => '1'];
    }];
$DS[] = ['key' => 'retur_beli_detail', 'target' => 'tblretur_pembelian_detail', 'keycols' => ['no_retur', 'no_item'],
    'src' => "SELECT d.*, h.NoBeli FROM tblreturbelidetail d
              LEFT JOIN tblreturbeliheader h ON h.NoTransaksi = d.NoTransaksi AND h.kode_cabang = d.kode_cabang
              WHERE d.kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['no_retur' => s($r['NoTransaksi']), 'no_pembelian' => s($r['NoBeli'], 50), 'no_item' => s($r['NoItem'], 50), 'quantity' => i($r['Quantity']),
            'harga_pokok' => n($r['HargaPokok']), 'potongan' => n($r['Potongan']), 'total' => n($r['Total']), 'user' => '', 'kd_cabang' => $CAB,
            'alasan_retur' => '', 'jenis_penggantian' => 0, 'status_penggantian' => '0'];
    }];
foreach ([['item_masuk', 'tbitem_masuk', 'tblitemm'], ['item_keluar', 'tbitem_keluar', 'tblitemk']] as [$k, $tgt, $src]) {
    $DS[] = ['key' => $k . '_header', 'target' => $tgt . '_header', 'keycols' => ['no_transaksi'],
        'src' => "SELECT * FROM {$src}header WHERE kode_cabang='{ref}'",
        'map' => function ($r) use ($CAB) {
            return ['no_transaksi' => s($r['NoTransaksi']), 'tanggal' => d($r['Tanggal']), 'note' => s($r['note'], 50), 'user' => s($r['User'], 50), 'kd_cabang' => $CAB];
        }];
    $DS[] = ['key' => $k . '_detail', 'target' => $tgt . '_detail', 'keycols' => ['no_transaksi', 'no_item'],
        'src' => "SELECT * FROM {$src}detail WHERE kode_cabang='{ref}'",
        'map' => function ($r) use ($CAB) {
            return ['no_transaksi' => s($r['NoTransaksi']), 'no_item' => s($r['NoItem'], 20), 'quantity' => i($r['Qty']), 'harga' => n($r['Harga']),
                'total' => n($r['Total']), 'keterangan' => s($r['note'], 100), 'user' => '', 'kd_cabang' => $CAB, 'status_trx' => '1',
                'stok_sistem' => 0, 'penyesuaian' => i($r['Qty'])];
        }];
}
$DS[] = ['key' => 'kas', 'target' => 'tblkas_keluar_masuk', 'keycols' => ['kode_km'],
    'src' => "SELECT * FROM tblkaskeluarmasuk WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['kode_km' => s($r['KodeKM']), 'no_bukti' => s($r['NoBukti'], 50), 'kode_akun' => s($r['KodeAkun'], 50), 'tanggal' => d($r['Tanggal']),
            'uraian' => s($r['uraian'], 200), 'masuk' => n($r['Masuk']), 'keluar' => n($r['Keluar']), 'jenis' => s($r['Jenis'], 50), 'kd_cabang' => $CAB,
            'user' => '', 'kode_akun_biaya' => '', 'tipe' => ''];
    }];
$DS[] = ['key' => 'harga_cabang', 'target' => 'tblitem_harga_cabang', 'keycols' => ['noitem'],
    'src' => "SELECT NoItem, HargaPokok, HargaJual, HargaJual2, HargaJual3, Quantity FROM tblitem WHERE kode_cabang='{ref}'",
    'map' => function ($r) use ($CAB) {
        return ['noitem' => s($r['NoItem']), 'kd_cabang' => $CAB, 'hargapokok' => n($r['HargaPokok']), 'hargajual' => n($r['HargaJual']),
            'hargajual2' => n($r['HargaJual2']), 'hargajual3' => n($r['HargaJual3']), 'qty_access' => i($r['Quantity']), 'tanggal_sync' => date('Y-m-d H:i:s')];
    }];
// tabel dengan PRIMARY/UNIQUE pada (kunci, kd_cabang): baris dobel di sumber dilewati
$UNIK = ['tblservice', 'tblorder_header', 'tblservice_jemputantar', 'tblitem_harga_cabang'];

// Master global: hanya tambah yang belum ada (tidak menimpa, konflik ditangani terpisah).
$MASTER = [
    ['key' => 'master_pelanggan', 'target' => 'tblpelanggan', 'pk' => 'nopelanggan',
     'src' => "SELECT * FROM tblpelanggan WHERE kode_cabang='{ref}'",
     'map' => function ($r) use ($CAB) {
        return ['nopelanggan' => s($r['NoPelanggan']), 'namapelanggan' => s($r['NamaPelanggan']), 'alamat' => s($r['Alamat']), 'kota' => s($r['Kota']),
            'propinsi' => s($r['Propinsi']), 'kodepost' => s($r['KodePost']), 'negara' => s($r['Negara']), 'telephone' => s($r['Telephone']), 'fax' => s($r['Fax']),
            'kontakperson' => s($r['KontakPerson']), 'note' => s($r['note']), 'potongan' => n($r['Potongan']), 'tipepot' => s($r['TipePot']),
            'lavelharga' => s($r['LavelHarga']), 'kgrup' => s($r['KGrup']), 'domisili_cabang' => $CAB];
     }],
    ['key' => 'master_kendaraan', 'target' => 'tblkendaraan', 'pk' => 'nopolisi',
     'src' => "SELECT * FROM tblkendaraan WHERE kode_cabang='{ref}'",
     'map' => function ($r) {
        return ['nopolisi' => s($r['NoPolisi']), 'pemilik' => s($r['Pemilik']), 'alamat' => s($r['Alamat']), 'tipe' => s($r['Tipe']), 'jenis' => s($r['Jenis']),
            'tahun_buat' => s($r['TahunBuat']), 'tahun_rakit' => s($r['TahunRakit']), 'silinder' => s($r['Silinder']), 'warna' => s($r['Warna']),
            'no_rangka' => s($r['NoRangka']), 'no_mesin' => s($r['NoMesin']), 'note' => s($r['note'])];
     }],
    ['key' => 'master_item', 'target' => 'tblitem', 'pk' => 'noitem',
     'src' => "SELECT * FROM tblitem WHERE kode_cabang='{ref}'",
     'map' => function ($r) {
        $m = [];
        foreach (['NoItem', 'KodeBarCode', 'NamaItem', 'Jenis', 'Satuan', 'HargaPokok', 'HargaJual', 'HargaJual2', 'HargaJual3', 'HJQtyD2', 'HJQtyD3', 'HJQtyS1', 'HJQtyS2',
                  'TotalPokok', 'StokMin', 'StatusItem', 'Supplier', 'Supplier2', 'Supplier3', 'StatusProduk', 'Gambar', 'note', 'RakBarang', 'JasaWaktu',
                  'JasaSatuanWaktu', 'JenisKomisi', 'KomisiProsen', 'KomisiNominal'] as $k) { $m[strtolower($k)] = $r[$k]; }
        $m['quantity'] = 0;
        return $m;
     }],
    ['key' => 'master_supplier', 'target' => 'tblsupplier', 'pk' => 'nosupplier',
     'src' => "SELECT * FROM tblsupplier WHERE kode_cabang='{ref}'",
     'map' => function ($r) use ($CAB) {
        $m = [];
        foreach (['NoSupplier', 'NamaSupplier', 'Alamat', 'Kota', 'Propinsi', 'KodePost', 'Negara', 'Telephone', 'Fax', 'NamaBank', 'NoAccount', 'AtasNama',
                  'KontakPerson', 'Email', 'note', 'SaldoAwal', 'JmlBayar', 'Sisa'] as $k) { $m[strtolower($k)] = $r[$k]; }
        $m['pertanggal'] = d($r['PerTanggal']); $m['kd_cabang'] = $CAB;
        return $m;
     }],
    // tblmekanik di web adalah VIEW (bukan tabel), master mekanik tidak dimuat di sini.
];

// ---------- util tabel ----------
function bak_name($target) { global $TAG, $CAB; return substr("bak_cr{$TAG}_{$CAB}_{$target}", 0, 64); }
function table_exists($t) { return (int)tv("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . e($t) . "'") > 0; }
function has_id($t) { $m = meta($t); return isset($m['id']) && strpos($m['id']['EXTRA'], 'auto_increment') !== false; }
function keyexpr($cols, $alias) { return "CONCAT_WS('|'," . implode(',', array_map(fn($c) => "$alias.`$c`", $cols)) . ")"; }
function keyval($row, $cols) { return implode('|', array_map(fn($c) => (string)($row[$c] ?? ''), $cols)); }
function log_run($ds, $aksi, $hapus, $isi, $lewat, $bak, $status, $catatan, $mulai) {
    global $RUN, $CAB;
    tq("INSERT INTO center_reload_log (run_id, kd_cabang, dataset, aksi, baris_hapus, baris_isi, baris_lewat, tabel_backup, status, catatan, mulai, selesai)
        VALUES ('" . e($RUN) . "','" . e($CAB) . "','" . e($ds) . "','" . e($aksi) . "',$hapus,$isi,$lewat," . ($bak ? "'" . e($bak) . "'" : 'NULL') . ",'" . e($status) . "','" . e(substr($catatan, 0, 2000)) . "','$mulai',NOW())");
}
function insert_batch($table, &$buf) {
    if (!$buf) { return; }
    $cols = array_keys($buf[0]);
    $vals = [];
    foreach ($buf as $row) {
        $v = [];
        foreach ($cols as $c) { $x = $row[$c] ?? null; $v[] = $x === null ? 'NULL' : "'" . e($x) . "'"; }
        $vals[] = '(' . implode(',', $v) . ')';
    }
    tq("INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES " . implode(',', $vals));
    $buf = [];
}
// Kunci baris web-native = baris berkode kanonik yang ada di backup (sebelum reload pertama).
function web_native_keys($ds) {
    global $CAB;
    $bak = bak_name($ds['target']);
    if (!table_exists($bak)) { return []; }
    $r = tq("SELECT " . keyexpr($ds['keycols'], 'b') . " k FROM `$bak` b WHERE b.kd_cabang = '" . e($CAB) . "'");
    $k = [];
    while ($x = mysqli_fetch_row($r)) { $k[$x[0]] = true; }
    return $k;
}
// Hapus baris cabang ini yang bukan web-native: semua kode lama + baris kanonik yang tidak ada di backup (hasil reload sebelumnya).
function hapus_cabang($ds) {
    global $CAB, $IN_LEGACY, $T;
    $t = $ds['target']; $bak = bak_name($t); $n = 0;
    if ($IN_LEGACY) { tq("DELETE FROM `$t` WHERE kd_cabang IN ($IN_LEGACY)"); $n += mysqli_affected_rows($T); }
    if (has_id($t)) {
        tq("DELETE x FROM `$t` x LEFT JOIN `$bak` b ON b.id = x.id WHERE x.kd_cabang = '" . e($CAB) . "' AND b.id IS NULL");
    } else {
        $on = implode(' AND ', array_map(fn($c) => "b.`$c` <=> x.`$c`", $ds['keycols']));
        tq("DELETE x FROM `$t` x LEFT JOIN `$bak` b ON $on AND b.kd_cabang = x.kd_cabang WHERE x.kd_cabang = '" . e($CAB) . "' AND b.kd_cabang IS NULL");
    }
    return $n + mysqli_affected_rows($T);
}
function aktif($key) { global $ONLY; return !$ONLY || in_array($key, $ONLY, true); }

// ---------- PREFLIGHT ----------
function preflight() {
    global $DS, $CAB, $REF, $IN_CODES, $CODES;
    say("== PREFLIGHT $CAB (ref $REF), kode di target: " . implode(',', $CODES));
    say(sprintf("%-20s %-28s %10s %10s %10s", 'dataset', 'target', 'sumber', 'target_cab', 'kanonik'));
    foreach ($DS as $ds) {
        if (!aktif($ds['key'])) { continue; }
        $src = (int)sv("SELECT COUNT(*) FROM (" . str_replace('{ref}', $REF, $ds['src']) . ") z");
        $tgt = (int)tv("SELECT COUNT(*) FROM `{$ds['target']}` WHERE kd_cabang IN ($IN_CODES)");
        $can = (int)tv("SELECT COUNT(*) FROM `{$ds['target']}` WHERE kd_cabang = '" . e($CAB) . "'");
        say(sprintf("%-20s %-28s %10d %10d %10d", $ds['key'], $ds['target'], $src, $tgt, $can));
    }
    $r = tq("SELECT TABLE_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'tblservice'");
    while ($x = mysqli_fetch_row($r)) {
        $c = (int)tv("SELECT COUNT(*) FROM `{$x[0]}` WHERE kd_cabang IN ($IN_CODES)");
        if ($c) { say("  ! anak FK {$x[0]} punya $c baris cabang ini (baris anak servis berkode lama ikut terhapus CASCADE)"); }
    }
    foreach (['servis_komisi', 'tb_dp_servis', 'tblretur_servis_header', 'tb_approval_diskon'] as $t) {
        $c = (int)tv("SELECT COUNT(*) FROM `$t` WHERE kd_cabang IN ($IN_CODES)");
        if ($c) { say("  i $t punya $c baris cabang ini (tidak disentuh)"); }
    }
    say("  tbstok cabang ini: " . tv("SELECT COUNT(*) FROM tbstok WHERE kd_cabang IN ($IN_CODES)"));
}

// ---------- RUN ----------
function backup($ds) {
    global $IN_CODES;
    $bak = bak_name($ds['target']);
    if (table_exists($bak)) { return [$bak, false]; }
    tq("CREATE TABLE `$bak` LIKE `{$ds['target']}`");
    tq("INSERT INTO `$bak` SELECT * FROM `{$ds['target']}` WHERE kd_cabang IN ($IN_CODES)");
    return [$bak, true];
}
function jalankan($ds) {
    global $REF, $T, $UNIK;
    $mulai = date('Y-m-d H:i:s');
    [$bak, $baru] = backup($ds);
    $native = web_native_keys($ds);
    mysqli_begin_transaction($T);
    try {
        $hapus = hapus_cabang($ds);
        $res = sq(str_replace('{ref}', $REF, $ds['src']), true);
        $buf = []; $isi = 0; $lewat = 0; $seen = [];
        $unik = in_array($ds['target'], $UNIK, true);
        $pakaiLain = [];
        if (!empty($ds['pk_global'])) {
            // PK target hanya nomor (tanpa kd_cabang): nomor yang dipakai cabang lain tidak bisa dimuat
            $rr = tq("SELECT `{$ds['pk_global']}` FROM `{$ds['target']}` WHERE kd_cabang <> '" . e($GLOBALS['CAB']) . "'");
            while ($xx = mysqli_fetch_row($rr)) { $pakaiLain[$xx[0]] = true; }
        }
        while ($r = mysqli_fetch_assoc($res)) {
            $row = ($ds['map'])($r);
            if ($row === null) { $lewat++; continue; }
            if ($pakaiLain && isset($pakaiLain[$row[$ds['pk_global']]])) { $GLOBALS['SKIP_PO'][$row[$ds['pk_global']]] = true; $lewat++; continue; }
            $k = keyval($row, $ds['keycols']);
            if (isset($native[$k])) { $lewat++; continue; }
            if ($unik) { if (isset($seen[$k])) { $lewat++; continue; } $seen[$k] = true; }
            $buf[] = lengkapi($ds['target'], $row); $isi++;
            if (count($buf) >= 500) { insert_batch($ds['target'], $buf); }
        }
        mysqli_free_result($res);
        insert_batch($ds['target'], $buf);
        mysqli_commit($T);
        log_run($ds['key'], 'run', $hapus, $isi, $lewat, $bak, 'ok', $baru ? 'backup baru' : 'backup lama dipakai', $mulai);
        say(sprintf("  %-20s hapus %7d  isi %7d  lewat %5d  backup %s", $ds['key'], $hapus, $isi, $lewat, $bak));
    } catch (Throwable $ex) {
        mysqli_rollback($T);
        log_run($ds['key'], 'run', 0, 0, 0, $bak, 'gagal', $ex->getMessage(), $mulai);
        throw $ex;
    }
}
function jalankan_master($m) {
    global $REF;
    $mulai = date('Y-m-d H:i:s');
    $ada = [];
    $r = tq("SELECT `{$m['pk']}` FROM `{$m['target']}`");
    while ($x = mysqli_fetch_row($r)) { $ada[strtoupper(trim((string)$x[0]))] = true; }
    $res = sq(str_replace('{ref}', $REF, $m['src']), true);
    $buf = []; $isi = 0; $lewat = 0;
    while ($x = mysqli_fetch_assoc($res)) {
        $row = ($m['map'])($x);
        $k = strtoupper(trim((string)$row[$m['pk']]));
        if ($k === '' || isset($ada[$k])) { $lewat++; continue; }
        $ada[$k] = true;
        $buf[] = lengkapi($m['target'], $row); $isi++;
        if (count($buf) >= 500) { insert_batch($m['target'], $buf); }
    }
    mysqli_free_result($res);
    insert_batch($m['target'], $buf);
    log_run($m['key'], 'tambah', 0, $isi, $lewat, null, 'ok', 'insert yang belum ada', $mulai);
    say(sprintf("  %-20s tambah %6d  sudah ada %6d", $m['key'], $isi, $lewat));
}
// Kartu stok: hapus kode lama + hasil reload sebelumnya, bangun dari transaksi center, rekonsiliasi ke tblitem.Quantity.
function bangun_stok() {
    global $CAB, $REF, $IN_LEGACY, $T;
    $mulai = date('Y-m-d H:i:s');
    [$bak] = backup(['target' => 'tbstok']);
    $q = [
        ['2', "SELECT d.NoTransaksi no, d.NoItem item, h.Tanggal tgl, d.Quantity masuk, 0 keluar, 'Pembelian' ket FROM tblpembeliandetail d JOIN tblpembelianheader h ON h.NoTransaksi=d.NoTransaksi AND h.kode_cabang=d.kode_cabang WHERE d.kode_cabang='{ref}'"],
        ['3', "SELECT d.NoTransaksi no, d.NoItem item, h.Tanggal tgl, 0 masuk, d.Quantity - IFNULL(d.QtyRetur,0) keluar, 'Penjualan' ket FROM tblpenjualandetail d JOIN tblpenjualanheader h ON h.NoTransaksi=d.NoTransaksi AND h.kode_cabang=d.kode_cabang WHERE d.kode_cabang='{ref}'"],
        ['4', "SELECT d.NoService no, d.NoItem item, h.Tanggal tgl, 0 masuk, d.Quantity - IFNULL(d.QtyRetur,0) keluar, 'Servis' ket FROM tblserviceitemdt d JOIN tblservice h ON h.NoService=d.NoService AND h.kode_cabang=d.kode_cabang WHERE d.kode_cabang='{ref}'"],
        ['5', "SELECT d.NoTransaksi no, d.NoItem item, h.Tanggal tgl, d.Qty masuk, 0 keluar, 'Penyesuaian Stok Item Masuk' ket FROM tblitemmdetail d JOIN tblitemmheader h ON h.NoTransaksi=d.NoTransaksi AND h.kode_cabang=d.kode_cabang WHERE d.kode_cabang='{ref}'"],
        ['6', "SELECT d.NoTransaksi no, d.NoItem item, h.Tanggal tgl, 0 masuk, d.Qty keluar, 'Penyesuaian Stok Item Keluar' ket FROM tblitemkdetail d JOIN tblitemkheader h ON h.NoTransaksi=d.NoTransaksi AND h.kode_cabang=d.kode_cabang WHERE d.kode_cabang='{ref}'"],
        ['7', "SELECT d.NoTransaksi no, d.NoItem item, h.Tanggal tgl, 0 masuk, d.Quantity keluar, 'Retur Pembelian' ket FROM tblreturbelidetail d JOIN tblreturbeliheader h ON h.NoTransaksi=d.NoTransaksi AND h.kode_cabang=d.kode_cabang WHERE d.kode_cabang='{ref}'"],
    ];
    mysqli_begin_transaction($T);
    try {
        $hapus = 0;
        if ($IN_LEGACY) { tq("DELETE FROM tbstok WHERE kd_cabang IN ($IN_LEGACY)"); $hapus += mysqli_affected_rows($T); }
        tq("DELETE x FROM tbstok x LEFT JOIN `$bak` b ON b.id = x.id WHERE x.kd_cabang = '" . e($CAB) . "' AND b.id IS NULL");
        $hapus += mysqli_affected_rows($T);
        $isi = 0; $buf = [];
        foreach ($q as [$tipe, $sql]) {
            $res = sq(str_replace('{ref}', $REF, $sql), true);
            while ($r = mysqli_fetch_assoc($res)) {
                $m = i($r['masuk']); $k = i($r['keluar']);
                if ($m === 0 && $k === 0) { continue; }
                $buf[] = ['tipe' => $tipe, 'no_transaksi' => s($r['no'], 30), 'no_item' => s($r['item'], 30), 'tanggal' => d($r['tgl']) ?: '1970-01-01',
                    'masuk' => max($m, 0), 'keluar' => max($k, 0) + max(-$m, 0), 'keterangan' => $r['ket'], 'kd_cabang' => $CAB];
                $isi++;
                if (count($buf) >= 1000) { insert_batch('tbstok', $buf); }
            }
            mysqli_free_result($res);
            insert_batch('tbstok', $buf);
        }
        // rekonsiliasi: saldo tbstok cabang (termasuk baris web-native) harus = tblitem.Quantity Access
        $qty = [];
        $res = sq("SELECT NoItem, Quantity FROM tblitem WHERE kode_cabang='$REF'");
        while ($r = mysqli_fetch_assoc($res)) { $qty[s($r['NoItem'], 30)] = i($r['Quantity']); }
        $sal = [];
        $r = tq("SELECT no_item, SUM(masuk - keluar) FROM tbstok WHERE kd_cabang = '" . e($CAB) . "' GROUP BY no_item");
        while ($x = mysqli_fetch_row($r)) { $sal[$x[0]] = (int)$x[1]; }
        $rek = 0;
        foreach (array_unique(array_merge(array_keys($qty), array_keys($sal))) as $item) {
            $diff = ($qty[$item] ?? 0) - ($sal[$item] ?? 0);
            if ($diff === 0) { continue; }
            $buf[] = ['tipe' => 'M', 'no_transaksi' => 'REKON-ACC-' . $CAB, 'no_item' => (string)$item, 'tanggal' => date('Y-m-d'),
                'masuk' => max($diff, 0), 'keluar' => max(-$diff, 0), 'keterangan' => 'Rekonsiliasi saldo Access', 'kd_cabang' => $CAB];
            $rek++;
            if (count($buf) >= 1000) { insert_batch('tbstok', $buf); }
        }
        insert_batch('tbstok', $buf);
        mysqli_commit($T);
        log_run('stok', 'run', $hapus, $isi + $rek, 0, $bak, 'ok', "mutasi $isi, rekonsiliasi $rek item", $mulai);
        say(sprintf("  %-20s hapus %7d  mutasi %7d  rekonsiliasi %d item", 'stok', $hapus, $isi, $rek));
    } catch (Throwable $ex) {
        mysqli_rollback($T);
        log_run('stok', 'run', 0, 0, 0, $bak, 'gagal', $ex->getMessage(), $mulai);
        throw $ex;
    }
}
// Alarm harga beli palsu dari muat histori (trigger trg_alarm_harga_beli)
function bersihkan_alarm($maxIdSebelum) {
    global $CAB, $T;
    tq("DELETE a FROM alarm_harga_beli a
        JOIN tblpembelian_header h ON h.notransaksi COLLATE utf8mb4_unicode_ci = a.no_transaksi_pembelian COLLATE utf8mb4_unicode_ci
         AND h.kd_cabang = '" . e($CAB) . "'
        WHERE a.id > " . (int)$maxIdSebelum);
    say("  alarm_harga_beli palsu dihapus: " . mysqli_affected_rows($T));
}

// ---------- VERIFY ----------
function verify() {
    global $CAB, $REF, $TAG;
    say("== VERIFY $CAB (web dikurangi baris web-native di backup bak_cr{$TAG}_*)");
    $cek = [
        ['servis', 'tblservice', "SELECT COUNT(*), ROUND(SUM(TotalAkhir)) FROM tblservice WHERE kode_cabang='{ref}'", 'total_akhir'],
        ['servis_barang', 'tblservis_barang', "SELECT COUNT(*), ROUND(SUM(Total)) FROM tblserviceitemdt WHERE kode_cabang='{ref}'", 'total'],
        ['servis_jasa', 'tblservis_jasa', "SELECT COUNT(*), ROUND(SUM(Total)) FROM tblservicejasadt WHERE kode_cabang='{ref}'", 'total'],
        ['beli', 'tblpembelian_header', "SELECT COUNT(*), ROUND(SUM(TotalAkhir)) FROM tblpembelianheader WHERE kode_cabang='{ref}'", 'total_akhir'],
        ['beli_detail', 'tblpembelian_detail', "SELECT COUNT(*), ROUND(SUM(Total)) FROM tblpembeliandetail WHERE kode_cabang='{ref}'", 'total'],
        ['jual', 'tblpenjualan_header', "SELECT COUNT(*), ROUND(SUM(TotalAkhir)) FROM tblpenjualanheader WHERE kode_cabang='{ref}'", 'total_akhir'],
        ['jual_detail', 'tblpenjualan_detail', "SELECT COUNT(*), ROUND(SUM(Total)) FROM tblpenjualandetail WHERE kode_cabang='{ref}'", 'total'],
        ['po', 'tblorder_header', "SELECT COUNT(*), ROUND(SUM(TotalOrder)) FROM tblorderheader WHERE kode_cabang='{ref}'", 'total_order'],
        ['hutang', 'tblhutang_header', "SELECT COUNT(*), ROUND(SUM(TotalBayar)) FROM tblhutangheader WHERE kode_cabang='{ref}'", 'total_bayar'],
    ];
    say(sprintf("%-14s %24s %24s  %s", 'cek', 'center (jml, total)', 'web (jml, total)', 'hasil'));
    foreach ($cek as [$nm, $tbl, $a, $col]) {
        $x = mysqli_fetch_row(sq(str_replace('{ref}', $REF, $a)));
        $bak = bak_name($tbl);
        $y = mysqli_fetch_row(tq("SELECT COUNT(*), ROUND(IFNULL(SUM(`$col`),0)) FROM `$tbl` WHERE kd_cabang='" . e($CAB) . "'"));
        if (table_exists($bak)) {
            $z = mysqli_fetch_row(tq("SELECT COUNT(*), ROUND(IFNULL(SUM(`$col`),0)) FROM `$bak` WHERE kd_cabang='" . e($CAB) . "'"));
            $y = [$y[0] - $z[0], $y[1] - $z[1]];
        }
        $ok = ((int)$x[0] === (int)$y[0]) && abs((float)$x[1] - (float)$y[1]) < 1;
        say(sprintf("%-14s %24s %24s  %s", $nm, "{$x[0]}, " . number_format((float)$x[1], 0, ',', '.'), "{$y[0]}, " . number_format((float)$y[1], 0, ',', '.'), $ok ? 'COCOK' : 'SELISIH'));
    }
    $x = mysqli_fetch_row(sq("SELECT COUNT(*), SUM(ROUND(Quantity)) FROM tblitem WHERE kode_cabang='$REF' AND ROUND(Quantity)<>0"));
    $y = mysqli_fetch_row(tq("SELECT COUNT(*), SUM(s) FROM (SELECT no_item, SUM(masuk-keluar) s FROM tbstok WHERE kd_cabang='" . e($CAB) . "' GROUP BY no_item HAVING s<>0) z"));
    say(sprintf("%-14s %24s %24s  %s", 'stok_saldo', "{$x[0]} item, {$x[1]}", "{$y[0]} item, {$y[1]}", ($x[0] == $y[0] && $x[1] == $y[1]) ? 'COCOK' : 'SELISIH'));
    $r = tq("SELECT dataset, aksi, baris_hapus, baris_isi, baris_lewat, status, selesai FROM center_reload_log WHERE kd_cabang='" . e($CAB) . "' ORDER BY id DESC LIMIT 40");
    say("-- log terakhir:");
    while ($x = mysqli_fetch_row($r)) { say('  ' . implode(' | ', $x)); }
}

// ---------- ROLLBACK ----------
// Kembalikan isi sebelum reload pertama. Hanya hapus baris yang TIDAK ada di backup
// (baris web-native tidak disentuh, jadi anak FK CASCADE-nya aman), lalu masukkan lagi baris backup yang hilang.
function rollback() {
    global $DS, $CAB, $T;
    say("== ROLLBACK $CAB dari backup");
    $all = array_merge($DS, [['key' => 'stok', 'target' => 'tbstok', 'keycols' => ['id']]]);
    foreach (array_reverse($all) as $ds) {
        if (!aktif($ds['key'])) { continue; }
        $t = $ds['target']; $bak = bak_name($t);
        if (!table_exists($bak)) { say("  {$ds['key']}: backup $bak tidak ada, lewati"); continue; }
        $mulai = date('Y-m-d H:i:s');
        mysqli_begin_transaction($T);
        $h = hapus_cabang($ds);
        if (has_id($t)) {
            tq("INSERT INTO `$t` SELECT b.* FROM `$bak` b LEFT JOIN `$t` x ON x.id = b.id WHERE x.id IS NULL");
        } else {
            $on = implode(' AND ', array_map(fn($c) => "x.`$c` <=> b.`$c`", $ds['keycols']));
            tq("INSERT INTO `$t` SELECT b.* FROM `$bak` b LEFT JOIN `$t` x ON $on AND x.kd_cabang = b.kd_cabang WHERE x.kd_cabang IS NULL");
        }
        $i = mysqli_affected_rows($T);
        mysqli_commit($T);
        log_run($ds['key'], 'rollback', $h, $i, 0, $bak, 'ok', '', $mulai);
        say(sprintf("  %-20s hapus %7d  pulihkan %7d", $ds['key'], $h, $i));
    }
    say("Catatan: master yang ditambah (pelanggan/kendaraan/item/supplier/mekanik) tidak dihapus otomatis; lihat center_reload_log aksi=tambah.");
}

// ---------- main ----------
try {
    if ($MODE === 'preflight') { preflight(); exit(0); }
    if (!table_exists('center_reload_log')) { throw new RuntimeException('Jalankan dulu db/migrations/2026-11-15_run_center_reload_schema.php up'); }
    if ($MODE === 'verify') { verify(); exit(0); }
    if ($MODE === 'rollback') { rollback(); exit(0); }
    say("== RUN $CAB ref $REF run $RUN");
    $res = sq("SELECT NoService FROM tblservice_jemputantar WHERE kode_cabang='$REF'");
    while ($x = mysqli_fetch_row($res)) { $JEMPUT[trim($x[0])] = true; }
    // --alarm_sejak=YYYY-mm-dd HH:ii:ss : bersihkan alarm palsu dari run sebelumnya yang gagal di tengah
    $maxAlarm = isset($opt['alarm_sejak'])
        ? (int)tv("SELECT IFNULL(MAX(id),0) FROM alarm_harga_beli WHERE created_at < '" . e($opt['alarm_sejak']) . "'")
        : (int)tv("SELECT IFNULL(MAX(id),0) FROM alarm_harga_beli");
    foreach ($MASTER as $m) { if (aktif($m['key'])) { jalankan_master($m); } }
    foreach ($DS as $ds) { if (aktif($ds['key'])) { jalankan($ds); } }
    if (aktif('beli_detail') || isset($opt['alarm_sejak'])) { bersihkan_alarm($maxAlarm); }
    if ($SKIP_PO) { say("  PO dilewati (nomor sudah dipakai cabang lain, tunggu Fase K): " . implode(',', array_keys($SKIP_PO))); }
    if (aktif('stok')) { bangun_stok(); }
    say("== SELESAI. Lanjut: --mode=verify");
} catch (Throwable $ex) {
    fwrite(STDERR, "GAGAL: " . $ex->getMessage() . "\n");
    exit(1);
}
