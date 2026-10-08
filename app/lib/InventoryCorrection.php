<?php
/**
 * Koreksi, kontrol, dan fitur lanjutan Moving Average (Fase 3-4 planning).
 * Dibangun di atas InventoryService; semua perubahan stok/nilai lewat service itu.
 *
 *  - adjust            : Stock Adjustment +/- dengan alasan wajib, tanggal hari ini (BR-21)
 *  - correctPurchasePrice : koreksi harga beli, sisa masuk average, terjual masuk PPV (BR-22/23)
 *  - submit/approve/reject : maker-checker, maker != checker (BR-27), audit lengkap
 *  - submitOpname      : selisih fisik -> auto adjust (di bawah ambang) atau antrian approval (V-12)
 *  - ship/receive/returnTransfer + agingTransit : barang dalam perjalanan (BR-13..15, V-14)
 *  - createClaim/settleClaim : Piutang Klaim Supplier (BR-19)
 *  - scrap             : barang hilang/rusak/pemakaian internal sebagai beban
 *  - postPurchaseWithBonus : bonus supplier proporsional (BR-17)
 *  - revalue           : revaluasi persediaan (disetujui Finance HQ lewat maker-checker)
 */
require_once __DIR__ . '/InventoryService.php';

class InventoryCorrection
{
    /** @var InventoryService */
    private $svc;
    /** @var mysqli */
    private $db;

    public function __construct(InventoryService $svc, $koneksi)
    {
        $this->svc = $svc;
        $this->db = $koneksi;
    }

    private static function wajibAlasan($alasan)
    {
        if (strlen(trim((string) $alasan)) < 5) {
            throw new InventoryException('Alasan wajib diisi (minimal 5 karakter)', 'alasan_wajib');
        }
    }

    private static function unik($awal)
    {
        return $awal . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    /** qtyMilli x sen / 1000, half-up, dengan tanda. */
    private static function kaliNilai($qtyMilli, $sen)
    {
        $neg = (($qtyMilli < 0) xor ($sen < 0));   // kurung wajib: `=` lebih kuat dari `xor`
        $v = InventoryService::divRound(abs($qtyMilli) * abs($sen), 1000);
        return $neg ? -$v : $v;
    }

    // ---------- Stock Adjustment (F3-01) ----------

    /**
     * $qty bertanda: positif = masuk (biaya = $opts['unit_cost'] atau average saat ini), negatif = keluar.
     * Tanggal selalu hari ini; $opts['ref_asal'] = transaksi yang dikoreksi.
     */
    public function adjust($lokasi, $item, $qty, $alasan, $user, array $opts = [])
    {
        self::wajibAlasan($alasan);
        $qtyM = InventoryService::toMilli($qty);
        if ($qtyM === 0) {
            throw new InventoryException('Qty adjustment tidak boleh 0', 'qty_invalid');
        }
        $ref = $opts['ref_no'] ?? self::unik('ADJ');
        $o = ['trx_type' => $qtyM > 0 ? 'ADJ_IN' : 'ADJ_OUT', 'user' => $user,
              'keterangan' => substr('Adj: ' . $alasan . (isset($opts['ref_asal']) ? ' ref ' . $opts['ref_asal'] : ''), 0, 100),
              'is_correction' => true];
        $hasil = $qtyM > 0
            ? $this->svc->postIn($lokasi, $item, InventoryService::fromMilli($qtyM), $opts['unit_cost'] ?? null, 'ADJ', $ref, $o)
            : $this->svc->postOut($lokasi, $item, InventoryService::fromMilli(-$qtyM), 'ADJ', $ref, $o);
        $this->svc->audit($user, 'adjust', "$lokasi:$item", $alasan, null, ['qty' => (string) $qty, 'ref' => $ref]);
        return $hasil;
    }

    // ---------- Koreksi harga pembelian (F3-02/03, F4-07) ----------

    /**
     * Barang masih ada: selisih x qty tersisa masuk rumus average (BR-22).
     * Sebagian/seluruhnya terjual: porsi terjual masuk inv_ppv_log, HPP lama tidak berubah (BR-23).
     * $corrRef unik per koreksi (mis. id correction_request); dipakai sebagai kunci idempotency.
     */
    public function correctPurchasePrice($lokasi, $item, $refType, $refNo, $hargaBaru, $corrRef, $alasan, $user)
    {
        self::wajibAlasan($alasan);
        $kd = $this->svc->resolveLocation($lokasi);
        $st = mysqli_prepare($this->db,
            "SELECT id, qty_in, unit_cost FROM inv_ledger WHERE kd_cabang = ? AND no_item = ? AND ref_type = ? AND ref_no = ?
             AND qty_in > 0 AND trx_type NOT LIKE 'VOID\\_%' ORDER BY id LIMIT 1");
        mysqli_stmt_bind_param($st, 'ssss', $kd, $item, $refType, $refNo);
        mysqli_stmt_execute($st);
        $asal = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        if (!$asal) {
            throw new InventoryException("Transaksi asal $refType:$refNo untuk $item tidak ada di ledger $kd", 'asal_tidak_ada');
        }
        $qtyBeli = InventoryService::toMilli($asal['qty_in']);
        $lama = InventoryService::toSen($asal['unit_cost']);
        $baru = InventoryService::toSen($hargaBaru);
        if ($baru < 0) {
            throw new InventoryException('Harga baru tidak boleh negatif', 'biaya_invalid');
        }
        $selisih = $baru - $lama;
        if ($selisih === 0) {
            throw new InventoryException('Harga baru sama dengan harga lama', 'tanpa_selisih');
        }
        $keluarSejak = InventoryService::toMilli($this->svc->qtyKeluarSejak($kd, $item, (int) $asal['id']));
        $saldo = $this->svc->saldo($kd, $item);
        $onHand = InventoryService::toMilli($saldo['qty_on_hand']);
        $sisa = max(0, min($onHand, $qtyBeli - $keluarSejak));
        $terjual = $qtyBeli - $sisa;

        mysqli_begin_transaction($this->db);
        try {
            $nilaiSisa = self::kaliNilai($sisa, $selisih);
            $variance = self::kaliNilai($terjual, $selisih);
            $adj = null;
            if ($sisa > 0 && $nilaiSisa !== 0) {
                $adj = $this->svc->postValueAdjust($kd, $item, InventoryService::fromSen($nilaiSisa), 'CORR_HARGA', $corrRef,
                    ['user' => $user, 'keterangan' => substr("Koreksi harga $refNo: $alasan", 0, 100), 'manage_tx' => false]);
            }
            if ($terjual > 0) {
                $per = date('Y-m');
                $q = InventoryService::fromMilli($terjual);
                $d = InventoryService::fromSen($selisih);
                $v = InventoryService::fromSen($variance);
                $ps = mysqli_prepare($this->db,
                    "INSERT INTO inv_ppv_log (kd_cabang, no_item, correction_ref, qty_affected, price_difference, total_variance, period)
                     VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($ps, 'sssssss', $kd, $item, $corrRef, $q, $d, $v, $per);
                if (!mysqli_stmt_execute($ps)) {
                    $e = mysqli_stmt_error($ps);
                    mysqli_stmt_close($ps);
                    throw new InventoryException('Gagal mencatat PPV: ' . $e, 'ppv_gagal');
                }
                mysqli_stmt_close($ps);
            }
            $this->svc->audit($user, 'koreksi_harga', "$kd:$item:$refNo", $alasan, ['harga_lama' => $asal['unit_cost']],
                ['harga_baru' => (string) $hargaBaru, 'sisa' => InventoryService::fromMilli($sisa), 'terjual' => InventoryService::fromMilli($terjual),
                 'ppv' => InventoryService::fromSen($variance), 'corr_ref' => $corrRef]);
            mysqli_commit($this->db);
        } catch (Throwable $e) {
            mysqli_rollback($this->db);
            throw $e;
        }
        return [
            'qty_sisa' => InventoryService::fromMilli($sisa), 'qty_terjual' => InventoryService::fromMilli($terjual),
            'nilai_masuk_average' => InventoryService::fromSen($nilaiSisa), 'ppv' => InventoryService::fromSen($variance),
            'avg_after' => $adj['avg_after'] ?? $saldo['average_cost'],
        ];
    }

    // ---------- Maker-checker (F3-05) ----------

    /** Ajukan koreksi. $type: ADJUST | CORR_HARGA | REVALUASI | SCRAP. Belum mengubah apa pun. */
    public function submit($type, $lokasi, array $payload, $alasan, $maker, $refOrigin = '')
    {
        self::wajibAlasan($alasan);
        if (!in_array($type, ['ADJUST', 'CORR_HARGA', 'REVALUASI', 'SCRAP'], true)) {
            throw new InventoryException("Jenis koreksi tidak dikenal: $type", 'jenis_invalid');
        }
        $kd = $this->svc->resolveLocation($lokasi);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $st = mysqli_prepare($this->db,
            "INSERT INTO inv_correction_request (type, kd_cabang, ref_origin, payload_json, reason, maker) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st, 'ssssss', $type, $kd, $refOrigin, $json, $alasan, $maker);
        mysqli_stmt_execute($st);
        $id = mysqli_insert_id($this->db);
        mysqli_stmt_close($st);
        $this->svc->audit($maker, 'koreksi_diajukan', "REQ:$id", $alasan, null, ['type' => $type, 'payload' => $payload]);
        return $id;
    }

    private function ambilRequest($id)
    {
        $st = mysqli_prepare($this->db, "SELECT * FROM inv_correction_request WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        if (!$r) {
            throw new InventoryException("Pengajuan #$id tidak ada", 'request_tidak_ada');
        }
        return $r;
    }

    /** Setujui lalu terapkan. Checker tidak boleh sama dengan maker. Hasil penerapan disimpan di result_json. */
    public function approve($id, $checker, $catatan = '')
    {
        mysqli_begin_transaction($this->db);
        try {
            $r = $this->ambilRequest($id);
            if ($r['status'] !== 'pending') {
                throw new InventoryException("Pengajuan #$id berstatus {$r['status']}", 'status_invalid');
            }
            if ($r['maker'] === $checker) {
                throw new InventoryException('Pengaju dan penyetuju tidak boleh orang yang sama', 'maker_sama');
            }
            // klaim status sekarang supaya persetujuan ganda/paralel tidak menerapkan dua kali
            $u = mysqli_prepare($this->db, "UPDATE inv_correction_request SET status = 'approved', checker = ? WHERE id = ?");
            mysqli_stmt_bind_param($u, 'si', $checker, $id);
            mysqli_stmt_execute($u);
            mysqli_stmt_close($u);
            mysqli_commit($this->db);
        } catch (Throwable $e) {
            mysqli_rollback($this->db);
            throw $e;
        }
        $payload = json_decode($r['payload_json'], true) ?: [];
        $lok = $r['kd_cabang'];
        $status = 'applied';
        $hasil = null;
        try {
            switch ($r['type']) {
                case 'ADJUST':
                    $hasil = $this->adjust($lok, $payload['item'], $payload['qty'], $r['reason'], $r['maker'],
                        ['unit_cost' => $payload['unit_cost'] ?? null, 'ref_no' => 'ADJ-REQ' . $id, 'ref_asal' => $r['ref_origin']]);
                    break;
                case 'CORR_HARGA':
                    $hasil = $this->correctPurchasePrice($lok, $payload['item'], $payload['ref_type'] ?? 'PEMBELIAN', $payload['ref_no'],
                        $payload['harga_baru'], 'REQ' . $id, $r['reason'], $r['maker']);
                    break;
                case 'REVALUASI':
                    $hasil = $this->revalue($lok, $payload['item'], $payload['average_baru'], $r['reason'], $r['maker'], 'REQ' . $id);
                    break;
                case 'SCRAP':
                    $hasil = $this->scrap($lok, $payload['item'], $payload['qty'], $payload['jenis'], $payload['berita_acara'], $r['maker'], 'SCRAP-REQ' . $id);
                    break;
            }
        } catch (Throwable $e) {
            $status = 'failed';
            $hasil = ['error' => $e->getMessage()];
        }
        $res = json_encode($hasil, JSON_UNESCAPED_UNICODE);
        $st = mysqli_prepare($this->db,
            "UPDATE inv_correction_request SET status = ?, checker = ?, decision_note = ?, result_json = ?, decided_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($st, 'ssssi', $status, $checker, $catatan, $res, $id);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $this->svc->audit($checker, $status === 'applied' ? 'koreksi_disetujui' : 'koreksi_gagal', "REQ:$id", $catatan, null, $hasil, $id);
        if ($status === 'failed') {
            throw new InventoryException('Penerapan gagal: ' . ($hasil['error'] ?? ''), 'terapkan_gagal');
        }
        return $hasil;
    }

    public function reject($id, $checker, $catatan)
    {
        self::wajibAlasan($catatan);
        $r = $this->ambilRequest($id);
        if ($r['status'] !== 'pending') {
            throw new InventoryException("Pengajuan #$id berstatus {$r['status']}", 'status_invalid');
        }
        if ($r['maker'] === $checker) {
            throw new InventoryException('Pengaju dan penolak tidak boleh orang yang sama', 'maker_sama');
        }
        $st = mysqli_prepare($this->db,
            "UPDATE inv_correction_request SET status = 'rejected', checker = ?, decision_note = ?, decided_at = NOW() WHERE id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($st, 'ssi', $checker, $catatan, $id);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $this->svc->audit($checker, 'koreksi_ditolak', "REQ:$id", $catatan, null, null, $id);
    }

    // ---------- Stock Opname (F4-04) ----------

    /**
     * $lines = [['item' => ..., 'qty_fisik' => ...]]. Selisih nilai <= ambang (inv_config opname_threshold, default 0)
     * dan ambang > 0 diterapkan otomatis; selain itu masuk antrian approval.
     */
    public function submitOpname($lokasi, array $lines, $alasan, $user)
    {
        self::wajibAlasan($alasan);
        $kd = $this->svc->resolveLocation($lokasi);
        $ambang = InventoryService::toSen($this->svc->getConfig('*', 'opname_threshold', '0') ?? '0');
        $ringkas = ['otomatis' => 0, 'diajukan' => 0, 'sama' => 0, 'request_id' => []];
        foreach ($lines as $ln) {
            $saldo = $this->svc->saldo($kd, $ln['item']);
            $fisik = InventoryService::toMilli($ln['qty_fisik']);
            $sistem = InventoryService::toMilli($saldo['qty_on_hand']);
            $diff = $fisik - $sistem;
            if ($diff === 0) {
                $ringkas['sama']++;
                continue;
            }
            $nilai = abs(self::kaliNilai($diff, InventoryService::toSen($saldo['average_cost'])));
            $payload = ['item' => $ln['item'], 'qty' => InventoryService::fromMilli($diff)];
            if ($ambang > 0 && $nilai <= $ambang) {
                $this->adjust($kd, $ln['item'], $payload['qty'], 'Opname: ' . $alasan, $user);
                $ringkas['otomatis']++;
            } else {
                $ringkas['request_id'][] = $this->submit('ADJUST', $kd, $payload, 'Opname: ' . $alasan, $user);
                $ringkas['diajukan']++;
            }
        }
        return $ringkas;
    }

    // ---------- Revaluasi dan scrap (F4-05/06) ----------

    /** Set average baru (nilai = qty x average_baru). Wajib lewat maker-checker oleh pemanggil (Finance HQ). */
    public function revalue($lokasi, $item, $averageBaru, $alasan, $user, $corrRef)
    {
        self::wajibAlasan($alasan);
        $saldo = $this->svc->saldo($lokasi, $item);
        $q = InventoryService::toMilli($saldo['qty_on_hand']);
        $nilaiBaru = self::kaliNilai($q, InventoryService::toSen($averageBaru));
        $delta = $nilaiBaru - InventoryService::toSen($saldo['total_value']);
        if ($delta === 0) {
            throw new InventoryException('Average baru sama dengan nilai sekarang', 'tanpa_selisih');
        }
        $h = $this->svc->postValueAdjust($lokasi, $item, InventoryService::fromSen($delta), 'REVALUASI', $corrRef,
            ['user' => $user, 'keterangan' => substr('Revaluasi: ' . $alasan, 0, 100)]);
        $this->svc->audit($user, 'revaluasi', "$lokasi:$item", $alasan, ['average' => $saldo['average_cost']], ['average' => $h['avg_after']]);
        return $h;
    }

    /** Barang hilang/rusak/scrap/pemakaian internal: keluar pada average, nilainya menjadi beban. Berita acara wajib. */
    public function scrap($lokasi, $item, $qty, $jenis, $beritaAcara, $user, $refNo = null)
    {
        if (!in_array($jenis, ['HILANG', 'RUSAK', 'SCRAP', 'INTERNAL'], true)) {
            throw new InventoryException("Jenis tidak dikenal: $jenis", 'jenis_invalid');
        }
        self::wajibAlasan($beritaAcara);
        $ref = $refNo ?: self::unik('SCRAP');
        $h = $this->svc->postOut($lokasi, $item, $qty, 'SCRAP', $ref, [
            'trx_type' => 'SCRAP_' . $jenis, 'user' => $user, 'keterangan' => substr('BA: ' . $beritaAcara, 0, 100), 'is_correction' => true]);
        $this->svc->audit($user, 'scrap', "$lokasi:$item", $beritaAcara, null, ['jenis' => $jenis, 'beban' => $h['hpp_total']]);
        return $h;
    }

    // ---------- Transfer dan barang dalam perjalanan (F2-08, F4-09) ----------

    public function shipTransfer($noTransfer, $asal, $tujuan, $item, $qty, $user)
    {
        $kdA = $this->svc->resolveLocation($asal);
        $kdT = $this->svc->resolveLocation($tujuan);
        if ($kdA === $kdT) {
            throw new InventoryException('Lokasi asal dan tujuan sama', 'lokasi_sama');
        }
        mysqli_begin_transaction($this->db);
        try {
            $h = $this->svc->postOut($kdA, $item, $qty, 'TRANSFER', $noTransfer, [
                'trx_type' => 'TRANSFER_OUT', 'user' => $user, 'keterangan' => "Kirim ke $kdT", 'manage_tx' => false]);
            if (!$h['idempotent']) {
                $q = $h['qty'];
                $uc = $h['hpp_unit'];
                $val = $h['hpp_total'];
                $st = mysqli_prepare($this->db,
                    "INSERT INTO inv_transit (no_transfer, no_item, kd_asal, kd_tujuan, qty, unit_cost, value) VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($st, 'sssssss', $noTransfer, $item, $kdA, $kdT, $q, $uc, $val);
                if (!mysqli_stmt_execute($st)) {
                    $e = mysqli_stmt_error($st);
                    mysqli_stmt_close($st);
                    throw new InventoryException('Gagal mencatat transit: ' . $e, 'transit_gagal');
                }
                mysqli_stmt_close($st);
            }
            mysqli_commit($this->db);
            return $h;
        } catch (Throwable $e) {
            mysqli_rollback($this->db);
            throw $e;
        }
    }

    private function ambilTransit($noTransfer, $item)
    {
        $st = mysqli_prepare($this->db, "SELECT * FROM inv_transit WHERE no_transfer = ? AND no_item = ? FOR UPDATE");
        mysqli_stmt_bind_param($st, 'ss', $noTransfer, $item);
        mysqli_stmt_execute($st);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        if (!$r) {
            throw new InventoryException("Transit $noTransfer/$item tidak ada", 'transit_tidak_ada');
        }
        return $r;
    }

    private function tutupTransit($noTransfer, $item, $status, $user, $trxType, $lokasi)
    {
        mysqli_begin_transaction($this->db);
        try {
            $t = $this->ambilTransit($noTransfer, $item);
            if ($t['status'] !== 'in_transit') {
                throw new InventoryException("Transit sudah {$t['status']}", 'status_invalid');
            }
            $h = $this->svc->postIn($lokasi, $item, $t['qty'], $t['unit_cost'], 'TRANSFER', $noTransfer, [
                'trx_type' => $trxType, 'user' => $user, 'keterangan' => "Transit $status", 'manage_tx' => false]);
            $st = mysqli_prepare($this->db, "UPDATE inv_transit SET status = ?, closed_at = NOW() WHERE id = ?");
            $id = (int) $t['id'];
            mysqli_stmt_bind_param($st, 'si', $status, $id);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            mysqli_commit($this->db);
            return $h;
        } catch (Throwable $e) {
            mysqli_rollback($this->db);
            throw $e;
        }
    }

    /** Terima: masuk ke average lokasi tujuan pada biaya kirim (BR-14). */
    public function receiveTransfer($noTransfer, $item, $user)
    {
        $t = $this->peekTransit($noTransfer, $item);
        return $this->tutupTransit($noTransfer, $item, 'received', $user, 'TRANSFER_IN', $t['kd_tujuan']);
    }

    /** Gagal kirim / dibatalkan: kembali ke lokasi asal pada biaya semula (BR-15). */
    public function returnTransfer($noTransfer, $item, $user)
    {
        $t = $this->peekTransit($noTransfer, $item);
        return $this->tutupTransit($noTransfer, $item, 'returned', $user, 'TRANSFER_RET', $t['kd_asal']);
    }

    private function peekTransit($noTransfer, $item)
    {
        $st = mysqli_prepare($this->db, "SELECT * FROM inv_transit WHERE no_transfer = ? AND no_item = ?");
        mysqli_stmt_bind_param($st, 'ss', $noTransfer, $item);
        mysqli_stmt_execute($st);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        if (!$r) {
            throw new InventoryException("Transit $noTransfer/$item tidak ada", 'transit_tidak_ada');
        }
        return $r;
    }

    /** Transit melewati batas hari (default inv_config transit_aging_hari = 3, V-14). */
    public function agingTransit($hari = null)
    {
        $hari = (int) ($hari ?? $this->svc->getConfig('*', 'transit_aging_hari', 3));
        $st = mysqli_prepare($this->db,
            "SELECT *, DATEDIFF(NOW(), shipped_at) umur_hari FROM inv_transit WHERE status = 'in_transit'
             AND shipped_at < (NOW() - INTERVAL ? DAY) ORDER BY shipped_at");
        mysqli_stmt_bind_param($st, 'i', $hari);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $o = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $o[] = $r;
        }
        mysqli_stmt_close($st);
        return $o;
    }

    // ---------- Klaim supplier (F4-03) ----------

    /** Barang rusak dari supplier: keluar dari stok pada average, nilainya jadi Piutang Klaim Supplier. */
    public function createClaim($noKlaim, $lokasi, $item, $qty, $supplierId, $user)
    {
        mysqli_begin_transaction($this->db);
        try {
            $h = $this->svc->postOut($lokasi, $item, $qty, 'KLAIM', $noKlaim, [
                'trx_type' => 'KLAIM_SUPPLIER', 'user' => $user, 'keterangan' => "Klaim supplier $supplierId", 'manage_tx' => false]);
            if (!$h['idempotent']) {
                $kd = $h['lokasi'];
                $q = $h['qty'];
                $v = $h['hpp_total'];
                $st = mysqli_prepare($this->db,
                    "INSERT INTO inv_claim_receivable (no_klaim, kd_cabang, no_item, qty, value, supplier_id) VALUES (?,?,?,?,?,?)");
                mysqli_stmt_bind_param($st, 'ssssss', $noKlaim, $kd, $item, $q, $v, $supplierId);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);
            }
            mysqli_commit($this->db);
            return $h;
        } catch (Throwable $e) {
            mysqli_rollback($this->db);
            throw $e;
        }
    }

    /** settled = supplier mengganti/membayar; rejected = klaim ditolak, nilai menjadi beban (dicatat di audit). */
    public function settleClaim($noKlaim, $item, $status, $user)
    {
        if (!in_array($status, ['settled', 'rejected'], true)) {
            throw new InventoryException('Status klaim tidak valid', 'status_invalid');
        }
        $st = mysqli_prepare($this->db,
            "UPDATE inv_claim_receivable SET status = ?, settled_at = NOW() WHERE no_klaim = ? AND no_item = ? AND status = 'pending'");
        mysqli_stmt_bind_param($st, 'sss', $status, $noKlaim, $item);
        mysqli_stmt_execute($st);
        $n = mysqli_stmt_affected_rows($st);
        mysqli_stmt_close($st);
        if ($n !== 1) {
            throw new InventoryException('Klaim tidak ada atau sudah diselesaikan', 'klaim_tidak_ada');
        }
        $this->svc->audit($user, 'klaim_' . $status, "$noKlaim:$item", '', null, ['status' => $status]);
    }

    // ---------- Bonus supplier proporsional (F4-01) ----------

    /**
     * $lines = [['item', 'qty', 'bonus' => bool]]. Harga efektif = total faktur / total qty (termasuk bonus), BR-17.
     * Nilai tiap baris dibulatkan; selisih pembulatan dibebankan ke baris terakhir agar total alokasi = total faktur.
     * Mengembalikan hasil posting per baris.
     */
    public function postPurchaseWithBonus($lokasi, $noFaktur, $totalFaktur, array $lines, $user)
    {
        $totalC = InventoryService::toSen($totalFaktur);
        $totalQ = 0;
        foreach ($lines as $l) {
            $totalQ += InventoryService::toMilli($l['qty']);
        }
        if ($totalQ <= 0 || $totalC < 0) {
            throw new InventoryException('Qty total / total faktur tidak valid', 'param_invalid');
        }
        $alokasi = [];
        $sisa = $totalC;
        $n = count($lines);
        foreach ($lines as $i => $l) {
            $q = InventoryService::toMilli($l['qty']);
            $nilai = ($i === $n - 1) ? $sisa : InventoryService::divRound($totalC * $q, $totalQ);
            $sisa -= $nilai;
            $alokasi[] = $nilai;
        }
        mysqli_begin_transaction($this->db);
        try {
            $out = [];
            foreach ($lines as $i => $l) {
                $q = InventoryService::toMilli($l['qty']);
                // biaya satuan = nilai alokasi / qty; total_value baris tetap = nilai alokasi (+-0,01 pembulatan satuan)
                $unit = InventoryService::divRound($alokasi[$i] * 1000, $q);
                $out[] = $this->svc->postIn($lokasi, $l['item'], InventoryService::fromMilli($q), InventoryService::fromSen($unit),
                    'PEMBELIAN', $noFaktur, ['trx_type' => 'PEMBELIAN', 'ref_line' => $i, 'user' => $user,
                    'keterangan' => !empty($l['bonus']) ? 'Bonus supplier' : 'Pembelian', 'manage_tx' => false]);
            }
            mysqli_commit($this->db);
            return $out;
        } catch (Throwable $e) {
            mysqli_rollback($this->db);
            throw $e;
        }
    }
}
