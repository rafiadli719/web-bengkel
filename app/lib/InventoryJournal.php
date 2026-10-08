<?php
/**
 * Jurnal akuntansi persediaan (F5-04, PRD §3/§14.I). Dibangun dari baris inv_ledger, idempoten per ledger id.
 *
 * COA belum diputuskan (D-07), jadi kode akun TIDAK di-hardcode: dibaca dari inv_config (kd_cabang '*'):
 *   acct_inventory, acct_transit, acct_cogs, acct_ap_clearing, acct_ppv, acct_claim, acct_adjustment, acct_opening, acct_reval
 * Akun yang dibutuhkan sebuah jurnal belum diisi -> baris itu DILEWATI (belum dijurnal), bukan gagal. Jurnal otomatis
 * terbentuk saat COA diisi dan postPending() dijalankan. Setiap jurnal wajib seimbang (debit = kredit), kalau tidak ditolak.
 */
require_once __DIR__ . '/InventoryService.php';

class InventoryJournal
{
    const AKUN = ['acct_inventory', 'acct_transit', 'acct_cogs', 'acct_ap_clearing', 'acct_ppv', 'acct_claim', 'acct_adjustment', 'acct_opening', 'acct_reval'];

    /** @var mysqli */
    private $db;
    /** @var InventoryService */
    private $svc;
    private $akun = null;

    public function __construct(InventoryService $svc, $koneksi)
    {
        $this->svc = $svc;
        $this->db = $koneksi;
    }

    public function akun($reload = false)
    {
        if ($this->akun === null || $reload) {
            $this->akun = [];
            $r = mysqli_query($this->db, "SELECT config_key, config_value FROM inv_config WHERE kd_cabang = '*' AND config_key LIKE 'acct\\_%'");
            while ($x = mysqli_fetch_assoc($r)) {
                if (trim($x['config_value']) !== '') {
                    $this->akun[$x['config_key']] = trim($x['config_value']);
                }
            }
        }
        return $this->akun;
    }

    public function setAkun($key, $kode, $oleh)
    {
        if (!in_array($key, self::AKUN, true)) {
            throw new InventoryException("Kunci akun tidak dikenal: $key", 'akun_invalid');
        }
        $st = mysqli_prepare($this->db,
            "INSERT INTO inv_config (kd_cabang, config_key, config_value, updated_by) VALUES ('*', ?, ?, ?)
             ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_by = VALUES(updated_by)");
        mysqli_stmt_bind_param($st, 'sss', $key, $kode, $oleh);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $this->akun(true);
    }

    /** Pasangan (debit, kredit) untuk sebuah baris ledger. Mengembalikan null bila jenisnya tidak dijurnal. */
    private function pasangan(array $l)
    {
        // trx_type generik IN/OUT (pemanggil langsung ke service) -> pakai ref_type sebagai jenis
        $t = in_array($l['trx_type'], ['IN', 'OUT'], true) ? $l['ref_type'] : $l['trx_type'];
        $void = false;
        if (strpos($t, 'VOID_') === 0) {
            $void = true;
            $t = substr($t, 5);
        }
        $qin = (float) $l['qty_in'] > 0;
        $nilai = abs((float) $l['value_delta']);
        $p = null;
        switch (true) {
            case in_array($t, ['PEMBELIAN', 'DO_TERIMA'], true):
                $p = ['acct_inventory', 'acct_ap_clearing']; break;
            case in_array($t, ['PENJUALAN', 'SERVIS'], true):
                $p = ['acct_cogs', 'acct_inventory']; break;
            case $t === 'TRANSFER_OUT':
                $p = ['acct_transit', 'acct_inventory']; break;
            case in_array($t, ['TRANSFER_IN', 'TRANSFER_RET'], true):
                $p = ['acct_inventory', 'acct_transit']; break;
            case $t === 'OPENING':
                $p = ['acct_inventory', 'acct_opening']; break;
            case $t === 'ADJ_IN':
                $p = ['acct_inventory', 'acct_adjustment']; break;
            case $t === 'ADJ_OUT' || strpos($t, 'SCRAP_') === 0:
                $p = ['acct_adjustment', 'acct_inventory']; break;
            case $t === 'KLAIM_SUPPLIER':
                $p = ['acct_claim', 'acct_inventory']; break;
            case in_array($t, ['STOK_MASUK', 'OPNAME_MASUK'], true):
                $p = ['acct_inventory', 'acct_adjustment']; break;
            case in_array($t, ['STOK_KELUAR', 'OPNAME_KELUAR'], true):
                $p = ['acct_adjustment', 'acct_inventory']; break;
            case $t === 'CORR_VALUE':
                // koreksi harga beli memakai lawan hutang; revaluasi memakai akun revaluasi (dibedakan lewat ref_type)
                $lawan = $l['ref_type'] === 'REVALUASI' ? 'acct_reval' : 'acct_ap_clearing';
                $p = (float) $l['value_delta'] >= 0 ? ['acct_inventory', $lawan] : [$lawan, 'acct_inventory'];
                break;
            case $t === 'UNIT_CONV':
                return null;     // nilai 0, tidak ada jurnal
        }
        if ($p === null || $nilai == 0.0) {
            return null;
        }
        return $void ? [$p[1], $p[0], $nilai] : [$p[0], $p[1], $nilai];
    }

    /** Jurnalkan satu baris ledger. Mengembalikan id jurnal, 'sudah' bila sudah ada, atau null bila dilewati. */
    public function postFromLedger($ledgerId)
    {
        $st = mysqli_prepare($this->db, "SELECT * FROM inv_ledger WHERE id = ?");
        mysqli_stmt_bind_param($st, 'i', $ledgerId);
        mysqli_stmt_execute($st);
        $l = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        if (!$l) {
            throw new InventoryException("Ledger #$ledgerId tidak ada", 'ledger_tidak_ada');
        }
        $p = $this->pasangan($l);
        if ($p === null) {
            return null;
        }
        $akun = $this->akun();
        if (!isset($akun[$p[0]]) || !isset($akun[$p[1]])) {
            return null;         // COA belum lengkap untuk jenis ini
        }
        $ada = mysqli_prepare($this->db, "SELECT id FROM inv_journal WHERE source_type = 'LEDGER' AND source_id = ?");
        mysqli_stmt_bind_param($ada, 'i', $ledgerId);
        mysqli_stmt_execute($ada);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($ada))) {
            mysqli_stmt_close($ada);
            return 'sudah';
        }
        mysqli_stmt_close($ada);
        return $this->tulis('LEDGER', $ledgerId, $l['kd_cabang'], $l['transaction_date'], $l['ref_type'], $l['ref_no'],
            $l['trx_type'] . ' ' . $l['no_item'], [[$akun[$p[0]], $p[2], 0], [$akun[$p[1]], 0, $p[2]]]);
    }

    private function tulis($srcType, $srcId, $kd, $tgl, $refType, $refNo, $desc, array $baris)
    {
        $d = 0; $k = 0;
        foreach ($baris as $b) {
            $d += round($b[1] * 100);
            $k += round($b[2] * 100);
        }
        if ($d !== $k || $d <= 0) {
            throw new InventoryException('Jurnal tidak seimbang (debit != kredit)', 'jurnal_tidak_seimbang');
        }
        mysqli_begin_transaction($this->db);
        try {
            $st = mysqli_prepare($this->db,
                "INSERT INTO inv_journal (source_type, source_id, kd_cabang, jdate, ref_type, ref_no, description) VALUES (?,?,?,?,?,?,?)");
            $desc = substr($desc, 0, 120);
            mysqli_stmt_bind_param($st, 'sisssss', $srcType, $srcId, $kd, $tgl, $refType, $refNo, $desc);
            if (!mysqli_stmt_execute($st)) {
                $e = mysqli_stmt_error($st);
                mysqli_stmt_close($st);
                throw new InventoryException('Gagal menulis jurnal: ' . $e, 'jurnal_gagal');
            }
            $jid = mysqli_insert_id($this->db);
            mysqli_stmt_close($st);
            foreach ($baris as $b) {
                $dv = number_format($b[1], 2, '.', '');
                $cv = number_format($b[2], 2, '.', '');
                $ls = mysqli_prepare($this->db, "INSERT INTO inv_journal_line (journal_id, account, debit, credit) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($ls, 'isss', $jid, $b[0], $dv, $cv);
                mysqli_stmt_execute($ls);
                mysqli_stmt_close($ls);
            }
            mysqli_commit($this->db);
            return $jid;
        } catch (Throwable $e) {
            mysqli_rollback($this->db);
            throw $e;
        }
    }

    /** Jurnalkan selisih harga pembelian (PPV): Dr PPV / Cr AP-clearing untuk selisih positif, sebaliknya untuk negatif. */
    public function postPpv($ppvId)
    {
        $st = mysqli_prepare($this->db, "SELECT * FROM inv_ppv_log WHERE id = ?");
        mysqli_stmt_bind_param($st, 'i', $ppvId);
        mysqli_stmt_execute($st);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        if (!$p) {
            throw new InventoryException("PPV #$ppvId tidak ada", 'ppv_tidak_ada');
        }
        $akun = $this->akun();
        $nilai = abs((float) $p['total_variance']);
        if ($nilai == 0.0 || !isset($akun['acct_ppv'], $akun['acct_ap_clearing'])) {
            return null;
        }
        $ada = mysqli_prepare($this->db, "SELECT id FROM inv_journal WHERE source_type = 'PPV' AND source_id = ?");
        mysqli_stmt_bind_param($ada, 'i', $ppvId);
        mysqli_stmt_execute($ada);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($ada))) {
            mysqli_stmt_close($ada);
            return 'sudah';
        }
        mysqli_stmt_close($ada);
        $pos = (float) $p['total_variance'] > 0;
        $baris = $pos ? [[$akun['acct_ppv'], $nilai, 0], [$akun['acct_ap_clearing'], 0, $nilai]]
                      : [[$akun['acct_ap_clearing'], $nilai, 0], [$akun['acct_ppv'], 0, $nilai]];
        return $this->tulis('PPV', $ppvId, $p['kd_cabang'], date('Y-m-d'), 'PPV', $p['correction_ref'], 'Selisih harga pembelian ' . $p['no_item'], $baris);
    }

    /** Jurnalkan semua yang belum (ledger dan PPV). Mengembalikan ringkasan. */
    public function postPending($batas = 5000)
    {
        $r = ['jurnal' => 0, 'dilewati' => 0];
        $q = mysqli_query($this->db,
            "SELECT l.id FROM inv_ledger l LEFT JOIN inv_journal j ON j.source_type = 'LEDGER' AND j.source_id = l.id
             WHERE j.id IS NULL ORDER BY l.id LIMIT " . (int) $batas);
        while ($x = mysqli_fetch_assoc($q)) {
            $h = $this->postFromLedger((int) $x['id']);
            if ($h === null) { $r['dilewati']++; } elseif ($h !== 'sudah') { $r['jurnal']++; }
        }
        $q = mysqli_query($this->db,
            "SELECT p.id FROM inv_ppv_log p LEFT JOIN inv_journal j ON j.source_type = 'PPV' AND j.source_id = p.id WHERE j.id IS NULL ORDER BY p.id LIMIT " . (int) $batas);
        while ($x = mysqli_fetch_assoc($q)) {
            $h = $this->postPpv((int) $x['id']);
            if ($h === null) { $r['dilewati']++; } elseif ($h !== 'sudah') { $r['jurnal']++; }
        }
        return $r;
    }

    /** Saldo bersih (debit - kredit) sebuah akun, opsional per lokasi. */
    public function saldoAkun($kodeAkun, $lokasi = null)
    {
        $sql = "SELECT COALESCE(SUM(jl.debit - jl.credit), 0) FROM inv_journal_line jl JOIN inv_journal j ON j.id = jl.journal_id WHERE jl.account = ?";
        if ($lokasi !== null) {
            $sql .= " AND j.kd_cabang = ?";
        }
        $st = mysqli_prepare($this->db, $sql);
        if ($lokasi !== null) {
            $kd = $this->svc->resolveLocation($lokasi);
            mysqli_stmt_bind_param($st, 'ss', $kodeAkun, $kd);
        } else {
            mysqli_stmt_bind_param($st, 's', $kodeAkun);
        }
        mysqli_stmt_execute($st);
        $v = mysqli_fetch_row(mysqli_stmt_get_result($st))[0];
        mysqli_stmt_close($st);
        return number_format((float) $v, 2, '.', '');
    }

    /**
     * Rekonsiliasi jurnal (F5-05): saldo akun Persediaan di jurnal harus sama dengan sum(inv_balance.total_value),
     * hanya bila semua ledger sudah dijurnal. Mengembalikan ['sama' => bool, 'jurnal' => ..., 'balance' => ..., 'belum_dijurnal' => n].
     */
    public function rekonJurnal($lokasi = null)
    {
        $akun = $this->akun();
        if (!isset($akun['acct_inventory'])) {
            return ['sama' => null, 'catatan' => 'COA acct_inventory belum diisi'];
        }
        $w = '';
        if ($lokasi !== null) {
            $w = " WHERE kd_cabang = '" . mysqli_real_escape_string($this->db, $this->svc->resolveLocation($lokasi)) . "'";
        }
        $bal = mysqli_fetch_row(mysqli_query($this->db, "SELECT COALESCE(SUM(total_value),0) FROM inv_balance" . $w))[0];
        $bd = mysqli_fetch_row(mysqli_query($this->db,
            "SELECT COUNT(*) FROM inv_ledger l LEFT JOIN inv_journal j ON j.source_type='LEDGER' AND j.source_id=l.id
             WHERE j.id IS NULL AND l.value_delta <> 0" . ($w ? " AND l." . substr($w, 7) : '')))[0];
        $jur = $this->saldoAkun($akun['acct_inventory'], $lokasi);
        return ['sama' => abs((float) $jur - (float) $bal) < 0.005 && (int) $bd === 0,
                'jurnal' => $jur, 'balance' => number_format((float) $bal, 2, '.', ''), 'belum_dijurnal' => (int) $bd];
    }
}
