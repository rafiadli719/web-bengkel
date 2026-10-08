<?php
/**
 * InventoryService: satu-satunya pintu posting persediaan Moving Average (PRD Moving Average v1, planning §5.1).
 *
 * Aturan inti (semua dalam SATU transaksi DB):
 *  1. Kunci baris inv_balance (lokasi, item) dengan SELECT ... FOR UPDATE.
 *  2. Idempotency lewat UNIQUE inv_ledger (ref_type, ref_no, ref_line, trx_type, lokasi, item).
 *  3. Average hanya berubah saat barang masuk (BR-01/02/03); HPP keluar = qty x average saat posting.
 *  4. Stok keluar melebihi saldo ditolak tanpa pengecualian role (BR-05).
 *  5. Tulis inv_ledger (append-only), inv_avg_history, update inv_balance; opsional proyeksi ke tbstok.
 *
 * Aritmetika memakai integer (qty dalam milli-unit, nilai dalam sen) agar pembulatan half-up deterministik (D-08).
 * Mode per cabang di inv_config.inv_mode: off | shadow | live. Pembaca lama tidak berubah selama off/shadow.
 */
class InventoryException extends Exception
{
    public $kode;
    public $tersedia;
    public function __construct($pesan, $kode = 'inv_error', $tersedia = null)
    {
        parent::__construct($pesan);
        $this->kode = $kode;
        $this->tersedia = $tersedia;
    }
}

class InventoryService
{
    const MODE_OFF = 'off';
    const MODE_SHADOW = 'shadow';
    const MODE_LIVE = 'live';

    /** @var mysqli */
    private $db;

    public function __construct($koneksi)
    {
        $this->db = $koneksi;
    }

    // ---------- util angka ----------

    /** Pembulatan half-up untuk pembagian integer: round(a / b), b > 0, a >= 0. */
    public static function divRound($a, $b)
    {
        return intdiv($a * 2 + $b, $b * 2);
    }

    /** "12.5" / 12.5 -> milli-unit (12500). Menolak lebih dari 3 desimal. */
    public static function toMilli($qty)
    {
        $s = trim((string) $qty);
        if (!preg_match('/^-?\d+(\.\d{1,3})?$/', $s)) {
            throw new InventoryException("Qty tidak valid: $s", 'qty_invalid');
        }
        return (int) round(((float) $s) * 1000);
    }

    /** Nilai rupiah dengan maks 2 desimal -> sen. */
    public static function toSen($nilai)
    {
        $s = trim((string) $nilai);
        if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $s)) {
            throw new InventoryException("Nilai tidak valid: $s", 'nilai_invalid');
        }
        return (int) round(((float) $s) * 100);
    }

    public static function fromMilli($m)
    {
        return number_format($m / 1000, 3, '.', '');
    }

    public static function fromSen($c)
    {
        return number_format($c / 100, 2, '.', '');
    }

    // ---------- lokasi & mode ----------

    /** Alias (CDT, PCL, ...) atau kode kanonik -> kode kanonik. Lokasi tak dikenal ditolak (D-01). */
    public function resolveLocation($lokasi)
    {
        $lokasi = strtoupper(trim((string) $lokasi));
        $st = mysqli_prepare($this->db, "SELECT kode FROM inv_location WHERE alias = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 's', $lokasi);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        if (!$row) {
            throw new InventoryException("Lokasi tidak dikenal: '$lokasi'", 'lokasi_tidak_dikenal');
        }
        return $row['kode'];
    }

    public function getMode($lokasi)
    {
        try {
            $kd = $this->resolveLocation($lokasi);
        } catch (InventoryException $e) {
            return self::MODE_OFF;
        }
        $st = mysqli_prepare($this->db, "SELECT config_value FROM inv_config WHERE kd_cabang = ? AND config_key = 'inv_mode'");
        mysqli_stmt_bind_param($st, 's', $kd);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        $m = $row ? $row['config_value'] : self::MODE_OFF;
        return in_array($m, [self::MODE_SHADOW, self::MODE_LIVE], true) ? $m : self::MODE_OFF;
    }

    public function setMode($lokasi, $mode, $oleh)
    {
        if (!in_array($mode, [self::MODE_OFF, self::MODE_SHADOW, self::MODE_LIVE], true)) {
            throw new InventoryException("Mode tidak valid: $mode", 'mode_invalid');
        }
        $kd = $this->resolveLocation($lokasi);
        $modeSebelum = $this->getMode($kd);
        $st = mysqli_prepare($this->db,
            "INSERT INTO inv_config (kd_cabang, config_key, config_value, updated_by) VALUES (?, 'inv_mode', ?, ?)
             ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_by = VALUES(updated_by)");
        mysqli_stmt_bind_param($st, 'sss', $kd, $mode, $oleh);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        if ($mode !== self::MODE_OFF) {
            // baseline rekonsiliasi: hanya transaksi tbstok SETELAH shadow aktif yang dibandingkan
            $r = mysqli_query($this->db, "SELECT COALESCE(MAX(id), 0) m FROM tbstok");
            $maks = $r ? (int) mysqli_fetch_assoc($r)['m'] : 0;
            $st = mysqli_prepare($this->db,
                "INSERT INTO inv_config (kd_cabang, config_key, config_value, updated_by) VALUES (?, 'shadow_since_tbstok_id', ?, ?)"
                . ($modeSebelum === self::MODE_OFF ? " ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_by = VALUES(updated_by)" : " ON DUPLICATE KEY UPDATE kd_cabang = kd_cabang"));
            $mk = (string) $maks;
            mysqli_stmt_bind_param($st, 'sss', $kd, $mk, $oleh);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
        $this->audit($oleh, 'set_mode', $kd, "mode=$mode", null, ['mode' => $mode]);
    }

    public function getConfig($lokasi, $key, $default = null)
    {
        $kd = $lokasi === '*' ? '*' : $this->resolveLocation($lokasi);
        $st = mysqli_prepare($this->db, "SELECT config_value FROM inv_config WHERE kd_cabang = ? AND config_key = ?");
        mysqli_stmt_bind_param($st, 'ss', $kd, $key);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        return $row ? $row['config_value'] : $default;
    }

    // ---------- rekonsiliasi shadow (F1-09) ----------

    /**
     * Bandingkan pergerakan qty per item sejak shadow aktif: jalur lama (tbstok, id > baseline, semua alias lokasi)
     * lawan inv_ledger (tanpa OPENING). Hasil: daftar selisih + ringkasan. Hanya baca.
     */
    public function reconcile($lokasi)
    {
        $kd = $this->resolveLocation($lokasi);
        $baseline = (int) $this->getConfig($kd, 'shadow_since_tbstok_id', 0);

        $alias = [];
        $st = mysqli_prepare($this->db, "SELECT alias FROM inv_location WHERE kode = ?");
        mysqli_stmt_bind_param($st, 's', $kd);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        while ($r = mysqli_fetch_assoc($res)) {
            $alias[] = $r['alias'];
        }
        mysqli_stmt_close($st);
        if (!$alias) {
            $alias = [$kd];
        }
        $in = "'" . implode("','", array_map(function ($x) {
            return mysqli_real_escape_string($this->db, $x);
        }, $alias)) . "'";

        $lama = [];
        $r = mysqli_query($this->db,
            "SELECT no_item, SUM(masuk - keluar) q FROM tbstok WHERE id > $baseline AND kd_cabang IN ($in) GROUP BY no_item");
        while ($x = mysqli_fetch_assoc($r)) {
            $lama[$x['no_item']] = (float) $x['q'];
        }
        $baru = [];
        $kdq = mysqli_real_escape_string($this->db, $kd);
        $r = mysqli_query($this->db,
            "SELECT no_item, SUM(qty_in - qty_out) q FROM inv_ledger WHERE kd_cabang = '$kdq' AND trx_type <> 'OPENING' GROUP BY no_item");
        while ($x = mysqli_fetch_assoc($r)) {
            $baru[$x['no_item']] = (float) $x['q'];
        }
        $gagal = 0;
        $r = mysqli_query($this->db,
            "SELECT COUNT(*) c FROM inv_audit_log WHERE action IN ('shadow_gagal','shadow_void_gagal') AND JSON_UNQUOTE(JSON_EXTRACT(after_json, '$.lokasi')) IN ($in)");
        if ($r) {
            $gagal = (int) mysqli_fetch_assoc($r)['c'];
        }

        $selisih = [];
        foreach (array_unique(array_merge(array_keys($lama), array_keys($baru))) as $item) {
            $a = $lama[$item] ?? 0.0;
            $b = $baru[$item] ?? 0.0;
            if (abs($a - $b) > 0.0005) {
                $selisih[] = ['no_item' => $item, 'tbstok' => $a, 'inv' => $b, 'selisih' => $b - $a];
            }
        }
        usort($selisih, function ($x, $y) {
            return abs($y['selisih']) <=> abs($x['selisih']);
        });
        return [
            'lokasi' => $kd, 'baseline_tbstok_id' => $baseline,
            'item_tbstok' => count($lama), 'item_inv' => count($baru),
            'item_selisih' => count($selisih), 'shadow_gagal' => $gagal, 'selisih' => $selisih,
        ];
    }

    // ---------- posting ----------

    /**
     * Barang masuk. Average baru = (nilai lama + qty x biaya) / (qty lama + qty).
     * Stok 0 lalu masuk: average = biaya masuk (BR-04).
     * $opts: transaction_date (Y-m-d, default hari ini server; BR-06), keterangan, user, trx_type,
     *        ref_line, tbstok_tipe (isi => proyeksi ke tbstok), tbstok_kd_cabang, manage_tx (default true).
     */
    public function postIn($lokasi, $item, $qty, $unitCost, $refType, $refNo, array $opts = [])
    {
        return $this->post('in', $lokasi, $item, $qty, $unitCost, $refType, $refNo, $opts);
    }

    /** Barang keluar. HPP = qty x average saat posting; average tidak berubah (BR-02/03). */
    public function postOut($lokasi, $item, $qty, $refType, $refNo, array $opts = [])
    {
        return $this->post('out', $lokasi, $item, $qty, 0, $refType, $refNo, $opts);
    }

    /** Retry otomatis bila deadlock/timeout (hanya saat service sendiri yang memegang transaksi). */
    private function post($arah, $lokasi, $item, $qty, $unitCost, $refType, $refNo, array $opts)
    {
        $manageTx = array_key_exists('manage_tx', $opts) ? (bool) $opts['manage_tx'] : true;
        $maks = $manageTx ? 5 : 1;
        for ($coba = 1; ; $coba++) {
            try {
                return $this->postSekali($arah, $lokasi, $item, $qty, $unitCost, $refType, $refNo, $opts);
            } catch (InventoryException $e) {
                if ($e->kode !== 'lock_gagal' || $coba >= $maks) {
                    throw $e;
                }
                usleep(20000 * $coba * mt_rand(1, 3));
            }
        }
    }

    private function postSekali($arah, $lokasi, $item, $qty, $unitCost, $refType, $refNo, array $opts)
    {
        $item = trim((string) $item);
        $refType = trim((string) $refType);
        $refNo = trim((string) $refNo);
        if ($item === '' || $refType === '' || $refNo === '') {
            throw new InventoryException('Item, ref_type, dan ref_no wajib', 'param_kurang');
        }
        $qtyM = self::toMilli($qty);
        if ($qtyM <= 0) {
            throw new InventoryException('Qty harus lebih dari 0', 'qty_invalid');
        }
        $costC = 0;
        // unitCost null pada barang masuk = pakai average saat ini (mis. retur masuk tanpa harga asal)
        $pakaiAvg = ($arah === 'in' && $unitCost === null);
        if ($arah === 'in' && !$pakaiAvg) {
            $costC = self::toSen($unitCost);
            if ($costC < 0) {
                throw new InventoryException('Biaya tidak boleh negatif', 'biaya_invalid');
            }
        }
        $kd = $this->resolveLocation($lokasi);
        $refLine = (int) ($opts['ref_line'] ?? 0);
        $trxType = $opts['trx_type'] ?? ($arah === 'in' ? 'IN' : 'OUT');
        $tgl = $opts['transaction_date'] ?? date('Y-m-d');
        $ket = substr((string) ($opts['keterangan'] ?? ''), 0, 100);
        $user = substr((string) ($opts['user'] ?? ''), 0, 50);
        $manageTx = array_key_exists('manage_tx', $opts) ? (bool) $opts['manage_tx'] : true;

        $this->assertPeriodOpen($kd, $tgl, !empty($opts['is_correction']));
        if ($manageTx) {
            mysqli_begin_transaction($this->db);
        }
        try {
            // idempotency: panggilan ulang dengan referensi sama tidak menggandakan
            $dup = $this->findLedger($kd, $item, $refType, $refNo, $refLine, $trxType);
            if ($dup) {
                if ($manageTx) {
                    mysqli_commit($this->db);
                }
                return $this->hasil($dup, true);
            }

            $bal = $this->lockBalance($kd, $item);

            $oldQ = self::toMilli($bal['qty_on_hand']);
            $oldAvg = self::toSen($bal['average_cost']);
            $oldTv = self::toSen($bal['total_value']);

            if ($arah === 'in') {
                if ($pakaiAvg) {
                    $costC = $oldAvg;
                }
                $addValue = self::divRound($qtyM * $costC, 1000);
                $newQ = $oldQ + $qtyM;
                $newTv = $oldTv + $addValue;
                $newAvg = $newQ > 0 ? self::divRound($newTv * 1000, $newQ) : 0;
                $valueDelta = $addValue;
                $qtyIn = $qtyM;
                $qtyOut = 0;
                $unitCostSen = $costC;
            } else {
                if ($qtyM > $oldQ) {
                    throw new InventoryException(
                        'Stok tidak cukup di ' . $kd . ' untuk ' . $item . '. Tersedia: ' . rtrim(rtrim(self::fromMilli($oldQ), '0'), '.'),
                        'stok_tidak_cukup',
                        self::fromMilli($oldQ)
                    );
                }
                $newQ = $oldQ - $qtyM;
                // keluar semua sisa: habiskan nilai persediaan agar tidak ada residu pembulatan
                $out = ($qtyM === $oldQ) ? $oldTv : self::divRound($qtyM * $oldAvg, 1000);
                $newTv = $oldTv - $out;
                $newAvg = $newQ > 0 ? $oldAvg : 0;
                $valueDelta = -$out;
                $qtyIn = 0;
                $qtyOut = $qtyM;
                $unitCostSen = $oldAvg;
            }

            $qIn = self::fromMilli($qtyIn);
            $qOut = self::fromMilli($qtyOut);
            $uc = self::fromSen($unitCostSen);
            $vd = self::fromSen($valueDelta);
            $ab = self::fromSen($oldAvg);
            $aa = self::fromSen($newAvg);
            $qa = self::fromMilli($newQ);
            $va = self::fromSen($newTv);

            $st = mysqli_prepare($this->db,
                "INSERT INTO inv_ledger (kd_cabang, no_item, trx_type, ref_type, ref_no, ref_line, qty_in, qty_out, unit_cost,
                    value_delta, avg_before, avg_after, qty_after, value_after, transaction_date, keterangan, created_by,
                    is_correction, correction_date)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $isKoreksi = !empty($opts['is_correction']) ? 1 : 0;
            $tglKoreksi = $isKoreksi ? date('Y-m-d') : null;
            mysqli_stmt_bind_param($st, 'sssssisssssssssssis', $kd, $item, $trxType, $refType, $refNo, $refLine,
                $qIn, $qOut, $uc, $vd, $ab, $aa, $qa, $va, $tgl, $ket, $user, $isKoreksi, $tglKoreksi);
            if (!mysqli_stmt_execute($st)) {
                $no = mysqli_stmt_errno($st);
                $err = mysqli_stmt_error($st);
                mysqli_stmt_close($st);
                throw new InventoryException('Gagal menulis ledger: ' . $err, in_array($no, [1213, 1205], true) ? 'lock_gagal' : 'ledger_gagal');
            }
            $ledgerId = mysqli_insert_id($this->db);
            mysqli_stmt_close($st);

            if ($newAvg !== $oldAvg) {
                $st = mysqli_prepare($this->db,
                    "INSERT INTO inv_avg_history (kd_cabang, no_item, avg_before, avg_after, trigger_type, trigger_ref, changed_by)
                     VALUES (?,?,?,?,?,?,?)");
                $trigRef = substr($refType . ':' . $refNo, 0, 40);
                mysqli_stmt_bind_param($st, 'sssssss', $kd, $item, $ab, $aa, $trxType, $trigRef, $user);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);
            }

            $st = mysqli_prepare($this->db,
                "UPDATE inv_balance SET qty_on_hand = ?, average_cost = ?, total_value = ?, version = version + 1
                 WHERE kd_cabang = ? AND no_item = ?");
            mysqli_stmt_bind_param($st, 'sssss', $qa, $aa, $va, $kd, $item);
            if (!mysqli_stmt_execute($st)) {
                $no = mysqli_stmt_errno($st);
                $err = mysqli_stmt_error($st);
                mysqli_stmt_close($st);
                throw new InventoryException('Gagal update saldo: ' . $err, in_array($no, [1213, 1205], true) ? 'lock_gagal' : 'saldo_gagal');
            }
            mysqli_stmt_close($st);

            // proyeksi ke tbstok (F1-06) hanya bila diminta dan qty bulat
            if (!empty($opts['tbstok_tipe'])) {
                if ($qtyM % 1000 !== 0) {
                    throw new InventoryException('Proyeksi tbstok butuh qty bulat', 'proyeksi_qty_pecahan');
                }
                $this->proyeksiTbstok((string) $opts['tbstok_tipe'], $refNo, $item, $tgl, intdiv($qtyIn, 1000), intdiv($qtyOut, 1000),
                    $ket, (string) ($opts['tbstok_kd_cabang'] ?? $kd));
            }

            if ($manageTx) {
                mysqli_commit($this->db);
            }
            return [
                'idempotent' => false,
                'ledger_id' => $ledgerId,
                'lokasi' => $kd,
                'item' => $item,
                'qty' => self::fromMilli($qtyM),
                'unit_cost' => $uc,
                'hpp_total' => $arah === 'out' ? self::fromSen(-$valueDelta) : '0.00',
                'hpp_unit' => $arah === 'out' ? $uc : '0.00',
                'avg_before' => $ab,
                'avg_after' => $aa,
                'qty_after' => $qa,
                'value_after' => $va,
            ];
        } catch (Throwable $e) {
            if ($manageTx) {
                mysqli_rollback($this->db);
            }
            throw $e;
        }
    }

    /**
     * Kunci baris saldo. FOR UPDATE dulu; baris dibuat hanya bila belum ada. INSERT IGNORE di depan akan
     * mengambil lock S pada baris yang sudah ada dan menimbulkan deadlock saat banyak proses naik ke lock X.
     * Kegagalan lock (deadlock 1213 / timeout 1205) dilempar sebagai 'lock_gagal' agar bisa di-retry.
     */
    private function lockBalance($kd, $item)
    {
        for ($i = 0; $i < 2; $i++) {
            $st = mysqli_prepare($this->db,
                "SELECT qty_on_hand, average_cost, total_value FROM inv_balance WHERE kd_cabang = ? AND no_item = ? FOR UPDATE");
            mysqli_stmt_bind_param($st, 'ss', $kd, $item);
            if (!mysqli_stmt_execute($st)) {
                $no = mysqli_stmt_errno($st);
                $err = mysqli_stmt_error($st);
                mysqli_stmt_close($st);
                throw new InventoryException('Gagal mengunci saldo: ' . $err, in_array($no, [1213, 1205], true) ? 'lock_gagal' : 'saldo_gagal');
            }
            $res = mysqli_stmt_get_result($st);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($st);
            if ($row) {
                return $row;
            }
            $st = mysqli_prepare($this->db, "INSERT IGNORE INTO inv_balance (kd_cabang, no_item) VALUES (?, ?)");
            mysqli_stmt_bind_param($st, 'ss', $kd, $item);
            if (!mysqli_stmt_execute($st)) {
                $no = mysqli_stmt_errno($st);
                $err = mysqli_stmt_error($st);
                mysqli_stmt_close($st);
                throw new InventoryException('Gagal membuat saldo: ' . $err, in_array($no, [1213, 1205], true) ? 'lock_gagal' : 'saldo_gagal');
            }
            mysqli_stmt_close($st);
        }
        throw new InventoryException('Saldo tidak ditemukan setelah dibuat', 'saldo_gagal');
    }

    private function proyeksiTbstok($tipe, $noTransaksi, $item, $tgl, $masuk, $keluar, $ket, $kdCabang)
    {
        $st = mysqli_prepare($this->db,
            "INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang) VALUES (?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st, 'ssssiiss', $tipe, $noTransaksi, $item, $tgl, $masuk, $keluar, $ket, $kdCabang);
        if (!mysqli_stmt_execute($st)) {
            $err = mysqli_stmt_error($st);
            mysqli_stmt_close($st);
            throw new InventoryException('Gagal proyeksi tbstok: ' . $err, 'proyeksi_gagal');
        }
        mysqli_stmt_close($st);
    }

    private function findLedger($kd, $item, $refType, $refNo, $refLine, $trxType)
    {
        $st = mysqli_prepare($this->db,
            "SELECT * FROM inv_ledger WHERE kd_cabang = ? AND no_item = ? AND ref_type = ? AND ref_no = ? AND ref_line = ? AND trx_type = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 'ssssis', $kd, $item, $refType, $refNo, $refLine, $trxType);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        return $row ?: null;
    }

    private function hasil(array $l, $idem)
    {
        $out = (float) $l['qty_out'] > 0;
        return [
            'idempotent' => $idem,
            'ledger_id' => (int) $l['id'],
            'lokasi' => $l['kd_cabang'],
            'item' => $l['no_item'],
            'qty' => $out ? $l['qty_out'] : $l['qty_in'],
            'unit_cost' => $l['unit_cost'],
            'hpp_total' => $out ? number_format(-(float) $l['value_delta'], 2, '.', '') : '0.00',
            'hpp_unit' => $out ? $l['unit_cost'] : '0.00',
            'avg_before' => $l['avg_before'],
            'avg_after' => $l['avg_after'],
            'qty_after' => $l['qty_after'],
            'value_after' => $l['value_after'],
        ];
    }

    // ---------- shadow ----------

    /**
     * Dipanggil adapter transaksi lama. off: tidak melakukan apa pun. shadow: jalankan posting di transaksi
     * TERPISAH dan telan semua error (dicatat ke inv_audit_log), sehingga alur lama tidak pernah terganggu.
     * Penolakan stok di shadow = sinyal "akan ditolak BR-05" untuk laporan rekonsiliasi.
     * live: lempar error ke pemanggil.
     */
    public function shadowIn($lokasi, $item, $qty, $unitCost, $refType, $refNo, array $opts = [])
    {
        return $this->shadow('in', $lokasi, $item, $qty, $unitCost, $refType, $refNo, $opts);
    }

    public function shadowOut($lokasi, $item, $qty, $refType, $refNo, array $opts = [])
    {
        return $this->shadow('out', $lokasi, $item, $qty, 0, $refType, $refNo, $opts);
    }

    private function shadow($arah, $lokasi, $item, $qty, $unitCost, $refType, $refNo, array $opts)
    {
        $mode = $this->getMode($lokasi);
        if ($mode === self::MODE_OFF) {
            return null;
        }
        if ($mode === self::MODE_SHADOW) {
            $opts['tbstok_tipe'] = null;          // jalur lama tetap penulis tbstok
            $opts['manage_tx'] = true;
            try {
                return $arah === 'in'
                    ? $this->postIn($lokasi, $item, $qty, $unitCost, $refType, $refNo, $opts)
                    : $this->postOut($lokasi, $item, $qty, $refType, $refNo, $opts);
            } catch (Throwable $e) {
                $this->audit((string) ($opts['user'] ?? ''), 'shadow_gagal', "$refType:$refNo", $e->getMessage(), null,
                    ['arah' => $arah, 'lokasi' => $lokasi, 'item' => $item, 'qty' => (string) $qty,
                     'kode' => $e instanceof InventoryException ? $e->kode : 'error']);
                return null;
            }
        }
        return $arah === 'in'
            ? $this->postIn($lokasi, $item, $qty, $unitCost, $refType, $refNo, $opts)
            : $this->postOut($lokasi, $item, $qty, $refType, $refNo, $opts);
    }

    // ---------- periode (BR-08) ----------

    /**
     * Periode closed menolak transaksi biasa; koreksi (is_correction) tetap boleh karena dicatat pada hari ini (EC-10).
     * Periode locked menolak semuanya. Tabel/baris periode tidak ada = open.
     */
    public function assertPeriodOpen($kd, $tanggal, $koreksi = false)
    {
        $per = substr((string) $tanggal, 0, 7);
        $st = @mysqli_prepare($this->db, "SELECT status FROM inv_period WHERE period = ? AND kd_cabang = ?");
        if (!$st) {
            return;
        }
        mysqli_stmt_bind_param($st, 'ss', $per, $kd);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        if (!$row || $row['status'] === 'open') {
            return;
        }
        if ($row['status'] === 'locked' || !$koreksi) {
            throw new InventoryException("Periode $per di $kd berstatus {$row['status']}", 'periode_tertutup');
        }
    }

    public function setPeriod($lokasi, $period, $status, $oleh)
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $period) || !in_array($status, ['open', 'closed', 'locked'], true)) {
            throw new InventoryException('Periode/status tidak valid', 'param_invalid');
        }
        $kd = $this->resolveLocation($lokasi);
        $st = mysqli_prepare($this->db,
            "INSERT INTO inv_period (period, kd_cabang, status, updated_by) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), updated_by = VALUES(updated_by)");
        mysqli_stmt_bind_param($st, 'ssss', $period, $kd, $status, $oleh);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $this->audit($oleh, 'set_period', "$kd:$period", "status=$status", null, ['status' => $status]);
    }

    // ---------- koreksi nilai ----------

    /**
     * Koreksi NILAI persediaan tanpa mengubah qty (mis. koreksi harga pembelian atas sisa barang, revaluasi).
     * Average dihitung ulang dari total_value baru. Baris ledger bertanda is_correction dengan correction_date hari ini.
     * $delta bertanda (string, maks 2 desimal). Stok 0 tidak bisa diberi nilai.
     */
    public function postValueAdjust($lokasi, $item, $delta, $refType, $refNo, array $opts = [])
    {
        $item = trim((string) $item);
        $deltaC = self::toSen($delta);
        $kd = $this->resolveLocation($lokasi);
        $user = substr((string) ($opts['user'] ?? ''), 0, 50);
        $ket = substr((string) ($opts['keterangan'] ?? ''), 0, 100);
        $refLine = (int) ($opts['ref_line'] ?? 0);
        $tgl = date('Y-m-d');
        $this->assertPeriodOpen($kd, $tgl, true);
        $manageTx = array_key_exists('manage_tx', $opts) ? (bool) $opts['manage_tx'] : true;
        if ($manageTx) {
            mysqli_begin_transaction($this->db);
        }
        try {
            $dup = $this->findLedger($kd, $item, $refType, $refNo, $refLine, 'CORR_VALUE');
            if ($dup) {
                if ($manageTx) {
                    mysqli_commit($this->db);
                }
                return $this->hasil($dup, true);
            }
            $bal = $this->lockBalance($kd, $item);
            $q = self::toMilli($bal['qty_on_hand']);
            $oldAvg = self::toSen($bal['average_cost']);
            $oldTv = self::toSen($bal['total_value']);
            if ($q <= 0) {
                throw new InventoryException('Stok 0: tidak ada nilai yang bisa dikoreksi', 'stok_kosong');
            }
            $newTv = $oldTv + $deltaC;
            if ($newTv < 0) {
                throw new InventoryException('Koreksi membuat nilai persediaan negatif', 'nilai_negatif');
            }
            $newAvg = self::divRound($newTv * 1000, $q);
            $vd = self::fromSen($deltaC);
            $ab = self::fromSen($oldAvg);
            $aa = self::fromSen($newAvg);
            $qa = self::fromMilli($q);
            $va = self::fromSen($newTv);
            $st = mysqli_prepare($this->db,
                "INSERT INTO inv_ledger (kd_cabang, no_item, trx_type, ref_type, ref_no, ref_line, qty_in, qty_out, unit_cost,
                    value_delta, avg_before, avg_after, qty_after, value_after, transaction_date, correction_date, is_correction, keterangan, created_by)
                 VALUES (?,?,'CORR_VALUE',?,?,?,0,0,0,?,?,?,?,?,?,?,1,?,?)");
            mysqli_stmt_bind_param($st, 'ssssisssssssss', $kd, $item, $refType, $refNo, $refLine, $vd, $ab, $aa, $qa, $va, $tgl, $tgl, $ket, $user);
            if (!mysqli_stmt_execute($st)) {
                $err = mysqli_stmt_error($st);
                mysqli_stmt_close($st);
                throw new InventoryException('Gagal menulis ledger koreksi: ' . $err, 'ledger_gagal');
            }
            $lid = mysqli_insert_id($this->db);
            mysqli_stmt_close($st);
            if ($newAvg !== $oldAvg) {
                $st = mysqli_prepare($this->db,
                    "INSERT INTO inv_avg_history (kd_cabang, no_item, avg_before, avg_after, trigger_type, trigger_ref, is_correction, changed_by)
                     VALUES (?,?,?,?,'CORR_VALUE',?,1,?)");
                $tr = substr($refType . ':' . $refNo, 0, 40);
                mysqli_stmt_bind_param($st, 'ssssss', $kd, $item, $ab, $aa, $tr, $user);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);
            }
            $st = mysqli_prepare($this->db,
                "UPDATE inv_balance SET average_cost = ?, total_value = ?, version = version + 1 WHERE kd_cabang = ? AND no_item = ?");
            mysqli_stmt_bind_param($st, 'ssss', $aa, $va, $kd, $item);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            if ($manageTx) {
                mysqli_commit($this->db);
            }
            return ['idempotent' => false, 'ledger_id' => $lid, 'lokasi' => $kd, 'item' => $item, 'avg_before' => $ab,
                    'avg_after' => $aa, 'qty_after' => $qa, 'value_after' => $va, 'value_delta' => $vd];
        } catch (Throwable $e) {
            if ($manageTx) {
                mysqli_rollback($this->db);
            }
            throw $e;
        }
    }

    /** Qty terjual/keluar di lokasi+item setelah ledger id tertentu (untuk membagi sisa vs terjual saat koreksi harga). */
    public function qtyKeluarSejak($lokasi, $item, $ledgerId)
    {
        $kd = $this->resolveLocation($lokasi);
        $st = mysqli_prepare($this->db, "SELECT COALESCE(SUM(qty_out),0) FROM inv_ledger WHERE kd_cabang = ? AND no_item = ? AND id > ?");
        mysqli_stmt_bind_param($st, 'ssi', $kd, $item, $ledgerId);
        mysqli_stmt_execute($st);
        $q = mysqli_fetch_row(mysqli_stmt_get_result($st))[0];
        mysqli_stmt_close($st);
        return (string) $q;
    }

    public function saldo($lokasi, $item)
    {
        $kd = $this->resolveLocation($lokasi);
        $st = mysqli_prepare($this->db, "SELECT * FROM inv_balance WHERE kd_cabang = ? AND no_item = ?");
        mysqli_stmt_bind_param($st, 'ss', $kd, $item);
        mysqli_stmt_execute($st);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        return $row ?: ['qty_on_hand' => '0.000', 'average_cost' => '0.00', 'total_value' => '0.00'];
    }

    // ---------- pembalikan ----------

    /**
     * Balikkan semua posting (ref_type, ref_no) yang belum dibalik, sebagai transaksi pembalik baru
     * (ledger tetap append-only; BR-06/P8 melarang DELETE). Masuk dibalik jadi keluar pada average saat ini
     * bila stok cukup, keluar dibalik jadi masuk pada unit_cost aslinya.
     * Mengembalikan jumlah baris yang dibalik. Di mode off tidak melakukan apa pun.
     */
    public function reverseShadow($refType, $refNo, $keterangan = '', $user = '')
    {
        $st = mysqli_prepare($this->db,
            "SELECT l.* FROM inv_ledger l
             WHERE l.ref_type = ? AND l.ref_no = ? AND l.trx_type NOT LIKE 'VOID\\_%'
               AND NOT EXISTS (SELECT 1 FROM inv_ledger v WHERE v.kd_cabang = l.kd_cabang AND v.no_item = l.no_item
                               AND v.ref_type = l.ref_type AND v.ref_no = l.ref_no AND v.ref_line = l.ref_line
                               AND v.trx_type = CONCAT('VOID_', l.trx_type))
             ORDER BY l.id");
        mysqli_stmt_bind_param($st, 'ss', $refType, $refNo);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
        mysqli_stmt_close($st);

        $n = 0;
        foreach ($rows as $l) {
            if ($this->getMode($l['kd_cabang']) === self::MODE_OFF) {
                continue;
            }
            $opts = ['trx_type' => 'VOID_' . $l['trx_type'], 'ref_line' => (int) $l['ref_line'],
                     'keterangan' => $keterangan, 'user' => $user];
            try {
                if ((float) $l['qty_in'] > 0) {
                    $this->postOut($l['kd_cabang'], $l['no_item'], $l['qty_in'], $l['ref_type'], $l['ref_no'], $opts);
                } else {
                    $this->postIn($l['kd_cabang'], $l['no_item'], $l['qty_out'], $l['unit_cost'], $l['ref_type'], $l['ref_no'], $opts);
                }
                $n++;
            } catch (Throwable $e) {
                $this->audit($user, 'shadow_void_gagal', $refType . ':' . $refNo, $e->getMessage(), null,
                    ['item' => $l['no_item'], 'lokasi' => $l['kd_cabang']]);
            }
        }
        return $n;
    }

    // ---------- audit ----------

    public function audit($actor, $action, $ref, $reason, $before, $after, $approvalId = null)
    {
        $b = $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE);
        $a = $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE);
        $actor = substr((string) $actor, 0, 50);
        $action = substr((string) $action, 0, 40);
        $ref = substr((string) $ref, 0, 60);
        $reason = substr((string) $reason, 0, 255);
        $st = mysqli_prepare($this->db,
            "INSERT INTO inv_audit_log (actor, action, ref, reason, before_json, after_json, approval_id) VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st, 'ssssssi', $actor, $action, $ref, $reason, $b, $a, $approvalId);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }
}
