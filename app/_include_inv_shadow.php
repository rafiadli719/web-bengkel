<?php
/**
 * Adapter shadow Moving Average (Fase 2). Dipanggil SETELAH jalur lama menulis tbstok.
 *
 * Kontrak keselamatan:
 *  - Tidak pernah melempar error dan tidak pernah mengubah hasil jalur lama.
 *  - inv_mode = off (default) -> tidak menulis apa pun.
 *  - Tabel inv_* belum ada (migrasi belum jalan) -> otomatis nonaktif untuk sisa request.
 *  - Hanya mode shadow yang dipakai di sini; live baru disambung saat cutover (F5-06).
 */
require_once __DIR__ . '/lib/InventoryService.php';

/**
 * Service shadow berjalan di KONEKSI TERPISAH: START TRANSACTION di koneksi utama akan meng-commit
 * transaksi milik file lama (12 file penulis stok memakai transaksi sendiri), dan kunci baris
 * inv_balance tidak boleh tertahan oleh transaksi lama. Koneksi kedua hanya dibuka bila ada
 * cabang ber-mode bukan off.
 */
function inv_shadow_svc($koneksi)
{
    static $svc = null, $mati = false;
    if ($mati) {
        return null;
    }
    if ($svc === null) {
        try {
            // pembacaan murni di koneksi utama: aman di dalam transaksi lama
            $r = @mysqli_query($koneksi,
                "SELECT COUNT(*) c FROM inv_config WHERE config_key = 'inv_mode' AND config_value <> 'off'");
            $aktif = $r ? (int) mysqli_fetch_assoc($r)['c'] : 0;
            if ($aktif === 0) {
                $mati = true;       // tabel belum ada, atau semua cabang off
                return null;
            }
            global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
            if (empty($DB_HOST) || empty($DB_USER) || empty($DB_NAME)) {
                $mati = true;
                return null;
            }
            $kedua = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
            if (!$kedua) {
                $mati = true;
                return null;
            }
            @mysqli_set_charset($kedua, 'latin1');
            $svc = new InventoryService($kedua);
        } catch (Throwable $e) {
            $mati = true;
            return null;
        }
    }
    return $svc;
}

/**
 * True bila lokasi (alias atau kode kanonik) berstatus live. Baca-saja di koneksi utama; false bila tabel belum ada.
 * Dipakai untuk menghentikan penulis lama (mis. sync Access) menimpa saldo cabang yang sudah dikelola ledger baru.
 */
function inv_cabang_live($koneksi, $lokasi)
{
    try {
        $lok = strtoupper(trim((string) $lokasi));
        $st = @mysqli_prepare($koneksi,
            "SELECT c.config_value FROM inv_location l JOIN inv_config c ON c.kd_cabang = l.kode AND c.config_key = 'inv_mode'
             WHERE l.alias = ? LIMIT 1");
        if (!$st) {
            return false;
        }
        mysqli_stmt_bind_param($st, 's', $lok);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        return $row && $row['config_value'] === 'live';
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Saldo ledger baru (float) bila lokasi live, null bila bukan live. Dipakai penegakan BR-05 saat cabang live:
 * stok yang berlaku adalah inv_balance, bukan tbstok.
 */
function inv_live_saldo($koneksi, $lokasi, $item)
{
    try {
        if (!inv_cabang_live($koneksi, $lokasi)) {
            return null;
        }
        $lok = strtoupper(trim((string) $lokasi));
        $st = @mysqli_prepare($koneksi,
            "SELECT COALESCE(b.qty_on_hand, 0) FROM inv_location l LEFT JOIN inv_balance b ON b.kd_cabang = l.kode AND b.no_item = ?
             WHERE l.alias = ? LIMIT 1");
        if (!$st) {
            return null;
        }
        mysqli_stmt_bind_param($st, 'ss', $item, $lok);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_row($res) : null;
        mysqli_stmt_close($st);
        return $row ? (float) $row[0] : 0.0;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Periksa seluruh keranjang penjualan (status_trx 0) terhadap saldo ledger baru. null = aman atau bukan live;
 * array = item pertama yang kurang ['item', 'tersedia', 'diminta'].
 */
function inv_live_cek_keranjang($koneksi, $lokasi, $user)
{
    try {
        if (!inv_cabang_live($koneksi, $lokasi)) {
            return null;
        }
        $st = mysqli_prepare($koneksi,
            "SELECT no_item, SUM(quantity) q FROM tblpenjualan_detail WHERE user = ? AND kd_cabang = ? AND status_trx = '0' GROUP BY no_item");
        mysqli_stmt_bind_param($st, 'ss', $user, $lokasi);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $baris = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $baris[] = $r;
        }
        mysqli_stmt_close($st);
        foreach ($baris as $r) {
            $ada = inv_live_saldo($koneksi, $lokasi, $r['no_item']);
            if ($ada !== null && (float) $r['q'] > $ada + 0.0005) {
                return ['item' => $r['no_item'], 'tersedia' => $ada, 'diminta' => (float) $r['q']];
            }
        }
        return null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Biaya satuan bersih dari total baris: total / qty, jatuh ke $fallback bila total/qty tidak ada. */
function inv_biaya_satuan($total, $qty, $fallback = 0)
{
    $qty = (float) $qty;
    $total = (float) $total;
    if ($qty > 0 && $total > 0) {
        return number_format($total / $qty, 2, '.', '');
    }
    return number_format((float) $fallback, 2, '.', '');
}

/**
 * Biaya satuan dari posting keluar sebelumnya (mis. HPP penjualan asal untuk retur penjualan, HPP kirim untuk
 * terima transfer). Baca-saja di koneksi utama. Mengembalikan string biaya atau null bila tidak ada.
 */
function inv_hpp_asal($koneksi, $refType, $refNo, $item)
{
    try {
        $st = @mysqli_prepare($koneksi,
            "SELECT unit_cost FROM inv_ledger WHERE ref_type = ? AND ref_no = ? AND no_item = ? AND qty_out > 0 ORDER BY id LIMIT 1");
        if (!$st) {
            return null;
        }
        mysqli_stmt_bind_param($st, 'sss', $refType, $refNo, $item);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        return $row ? $row['unit_cost'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Harga satuan di PO (tblorder_detail.harga_pokok) untuk penerimaan DO. null bila tidak ada. */
function inv_harga_po($koneksi, $noPo, $item)
{
    try {
        $st = @mysqli_prepare($koneksi, "SELECT harga_pokok FROM tblorder_detail WHERE no_order = ? AND no_item = ? LIMIT 1");
        if (!$st) {
            return null;
        }
        mysqli_stmt_bind_param($st, 'ss', $noPo, $item);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        return $row ? number_format((float) $row['harga_pokok'], 2, '.', '') : null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Balikkan semua posting sebuah referensi (pengganti DELETE tbstok pada batal retur). Shadow: tidak pernah melempar. */
function inv_shadow_reverse($koneksi, $refType, $refNo, $keterangan = '', $user = '')
{
    try {
        $svc = inv_shadow_svc($koneksi);
        if ($svc === null) {
            return null;
        }
        return $svc->reverseShadow($refType, $refNo, $keterangan, $user);
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Barang masuk (shadow). $unitCost null = pakai average saat ini.
 * $refLine membedakan baris item yang sama dalam satu dokumen (pakai nobaris bila ada).
 */
function inv_shadow_in($koneksi, $lokasi, $item, $qty, $unitCost, $refType, $refNo, $keterangan = '', $user = '', $refLine = 0)
{
    try {
        $svc = inv_shadow_svc($koneksi);
        if ($svc === null) {
            return null;
        }
        return $svc->shadowIn($lokasi, $item, $qty, $unitCost, $refType, $refNo, [
            'trx_type' => $refType, 'keterangan' => $keterangan, 'user' => $user, 'ref_line' => (int) $refLine,
        ]);
    } catch (Throwable $e) {
        return null;
    }
}

/** Barang keluar (shadow). Penolakan stok di shadow hanya dicatat ke inv_audit_log. */
function inv_shadow_out($koneksi, $lokasi, $item, $qty, $refType, $refNo, $keterangan = '', $user = '', $refLine = 0)
{
    try {
        $svc = inv_shadow_svc($koneksi);
        if ($svc === null) {
            return null;
        }
        return $svc->shadowOut($lokasi, $item, $qty, $refType, $refNo, [
            'trx_type' => $refType, 'keterangan' => $keterangan, 'user' => $user, 'ref_line' => (int) $refLine,
        ]);
    } catch (Throwable $e) {
        return null;
    }
}
