<?php
// Sumber: web_kasir/includes/super_admin_task_reminder.php — widget FAB
// "pengingat eksekusi" super admin (badge counter + modal daftar tugas
// pending), di-include dari includes/sidebar.php. KOREKSI temuan lama:
// file ini BUKAN dead include — aktif dipanggil live di source, salah
// diklaim "tidak ada" waktu porting sidebar.php pertama kali.
//
// Gerbang session asli (SELECT $_SESSION['role'] + cek $pdo) diganti baca
// $is_super_admin yang sudah dihitung include.php pemanggil (sidebar.php)
// dari $legacy_session_kasir. PDO dibuka sendiri di sini (pola sama file
// ported lain) karena sidebar.php cuma punya $koneksi (mysqli) dari
// koneksi_kasir.php, bukan $pdo.
if (($is_super_admin ?? false) !== true) {
    return;
}

$satrPdo = new PDO("mysql:host=localhost;dbname=fitmotor_dbbengkel", "fitmotor_LOGIN", "Sayalupa12");
$satrPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!function_exists('satrTableExists')) {
    function satrTableExists(PDO $pdo, string $tableName): bool
    {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Task reminder table check error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('satrCount')) {
    function satrCount(PDO $pdo, string $tableName, string $sql, array $params = []): int
    {
        if (!satrTableExists($pdo, $tableName)) {
            return 0;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Task reminder count error for ' . $tableName . ': ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('satrExtractRefTrxIds')) {
    function satrExtractRefTrxIds(?string $keterangan): array
    {
        if (!preg_match('/\[Ref\s*TRX\s*:\s*([^\]]+)\]/i', (string) $keterangan, $matches)) {
            return [];
        }

        $ids = [];
        foreach (explode(',', $matches[1]) as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}

if (!function_exists('satrCountReadySetorBank')) {
    function satrCountReadySetorBank(PDO $pdo): int
    {
        if (!satrTableExists($pdo, 'kasir_transactions_closing_kasir') || !satrTableExists($pdo, 'setoran_keuangan_closing_kasir')) {
            return 0;
        }

        $lockedClosingIds = [];

        if (satrTableExists($pdo, 'pengambilan_setoran_closing_kasir')) {
            try {
                $stmtLocked = $pdo->query("SELECT keterangan
                                           FROM pengambilan_setoran_closing_kasir
                                           WHERE parent_kode_pengambilan IS NULL
                                             AND status != 'selesai'
                                             AND COALESCE(nominal_sisa, 0) > 0
                                             AND COALESCE(verified_cabang_penerima_sudah_closing, 0) = 0");

                foreach ($stmtLocked->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $lockedClosingIds = array_merge(
                        $lockedClosingIds,
                        satrExtractRefTrxIds($row['keterangan'] ?? '')
                    );
                }

                $lockedClosingIds = array_values(array_unique(array_filter($lockedClosingIds)));
            } catch (Throwable $e) {
                error_log('Task reminder locked closing check error: ' . $e->getMessage());
                $lockedClosingIds = [];
            }
        }

        $sql = "SELECT COUNT(*)
                FROM kasir_transactions_closing_kasir kt
                LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
                WHERE sk.status = 'Validasi Keuangan OK'
                  AND kt.status = 'end proses'
                  AND kt.deposit_status = 'Validasi Keuangan OK'
                  AND COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) > 0";
        $params = [];

        if (!empty($lockedClosingIds)) {
            $sql .= " AND kt.id NOT IN (" . implode(',', array_fill(0, count($lockedClosingIds), '?')) . ")";
            $params = $lockedClosingIds;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Task reminder ready bank count error: ' . $e->getMessage());
            return 0;
        }
    }
}

$taskReminderItems = [
    [
        'title' => 'Pengajuan Revisi Closing',
        'description' => 'Permintaan pembukaan ulang transaksi closing yang perlu disetujui.',
        'count' => satrCount($satrPdo, 'closing_revision_requests_closing_kasir', "SELECT COUNT(*) FROM closing_revision_requests_closing_kasir WHERE status = 'pending'"),
        'url' => 'closing_revisi_admin.php',
        'icon' => 'fa-code-branch',
        'tone' => 'danger',
    ],
    [
        'title' => 'Konfirmasi Buka Transaksi',
        'description' => 'Permintaan buka transaksi kasir yang masih menunggu keputusan.',
        'count' => satrCount($satrPdo, 'konfirmasi_buka_transaksi_closing_kasir', "SELECT COUNT(*) FROM konfirmasi_buka_transaksi_closing_kasir WHERE status = 'pending'"),
        'url' => 'konfirmasi_buka_transaksi.php',
        'icon' => 'fa-unlock-alt',
        'tone' => 'warning',
    ],
    [
        'title' => 'Terima Setoran',
        'description' => 'Setoran yang sedang dibawa kurir dan perlu diterima staff keuangan.',
        'count' => satrCount($satrPdo, 'setoran_keuangan_closing_kasir', "SELECT COUNT(*) FROM setoran_keuangan_closing_kasir WHERE status = 'Sedang Dibawa Kurir'"),
        'url' => 'setoran_keuangan.php?tab=terima',
        'icon' => 'fa-download',
        'tone' => 'info',
    ],
    [
        'title' => 'Validasi Fisik',
        'description' => 'Setoran yang sudah diterima dan perlu validasi uang fisik.',
        'count' => satrCount($satrPdo, 'kasir_transactions_closing_kasir', "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Diterima Staff Keuangan'"),
        'url' => 'setoran_keuangan.php?tab=validasi',
        'icon' => 'fa-search',
        'tone' => 'info',
    ],
    [
        'title' => 'Validasi Selisih',
        'description' => 'Setoran dengan selisih yang perlu dicek atau diedit.',
        'count' => satrCount($satrPdo, 'kasir_transactions_closing_kasir', "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Validasi Keuangan SELISIH'"),
        'url' => 'setoran_keuangan.php?tab=validasi_selisih',
        'icon' => 'fa-exclamation-triangle',
        'tone' => 'danger',
    ],
    [
        'title' => 'Dikembalikan ke CS',
        'description' => 'Transaksi/setoran yang perlu ditindaklanjuti setelah dikembalikan ke CS.',
        'count' => satrCount($satrPdo, 'kasir_transactions_closing_kasir', "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE deposit_status = 'Dikembalikan ke CS'"),
        'url' => 'setoran_keuangan.php?tab=dikembalikan_cs',
        'icon' => 'fa-undo',
        'tone' => 'warning',
    ],
    [
        'title' => 'Setor ke Bank',
        'description' => 'Transaksi valid yang siap dieksekusi setor bank dan tidak sedang terkunci pengambilan.',
        'count' => satrCountReadySetorBank($satrPdo),
        'url' => 'setoran_keuangan.php?tab=setor_bank',
        'icon' => 'fa-university',
        'tone' => 'success',
    ],
    [
        'title' => 'Bayar Hutang Pengambilan',
        'description' => 'Pengambilan dana hutang yang masih perlu pelunasan/mutasi.',
        'count' => satrCount($satrPdo, 'pengambilan_setoran_closing_kasir', "SELECT COUNT(*) FROM pengambilan_setoran_closing_kasir WHERE parent_kode_pengambilan IS NULL AND klasifikasi = 'hutang' AND status = 'hutang'"),
        'url' => 'setoran_keuangan.php?tab=histori_hutang',
        'icon' => 'fa-file-invoice-dollar',
        'tone' => 'danger',
    ],
];

$taskReminderTotal = array_sum(array_column($taskReminderItems, 'count'));
$taskReminderActive = array_values(array_filter($taskReminderItems, static function (array $item): bool {
    return (int) $item['count'] > 0;
}));
?>

<style>
.sa-task-fab {
    position: fixed !important;
    right: 24px !important;
    bottom: 24px !important;
    width: 58px !important;
    height: 58px !important;
    min-width: 58px !important;
    border: 0 !important;
    border-radius: 50% !important;
    background: #0f172a !important;
    color: #ffffff !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.28) !important;
    cursor: pointer !important;
    z-index: 1300 !important;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease !important;
}
.sa-task-fab:hover {
    transform: translateY(-2px) !important;
    background: #1d4ed8 !important;
    box-shadow: 0 18px 42px rgba(29, 78, 216, 0.35) !important;
}
.sa-task-fab i { font-size: 20px !important; }
.sa-task-fab-count {
    position: absolute !important;
    top: -5px !important;
    right: -5px !important;
    min-width: 24px !important;
    height: 24px !important;
    padding: 0 7px !important;
    border-radius: 999px !important;
    background: #ef4444 !important;
    color: #ffffff !important;
    border: 2px solid #ffffff !important;
    font-size: 12px !important;
    font-weight: 800 !important;
    line-height: 20px !important;
    text-align: center !important;
}
.sa-task-modal {
    position: fixed !important;
    inset: 0 !important;
    background: rgba(15, 23, 42, 0.55) !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 24px !important;
    z-index: 1400 !important;
}
.sa-task-modal.show { display: flex !important; }
.sa-task-card {
    width: min(760px, 100%) !important;
    max-height: min(760px, 88vh) !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
}
.sa-task-head {
    padding: 20px 22px !important;
    border-bottom: 1px solid #e2e8f0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 16px !important;
}
.sa-task-eyebrow {
    color: #64748b !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    letter-spacing: .9px !important;
    text-transform: uppercase !important;
    margin-bottom: 4px !important;
}
.sa-task-head h3 {
    margin: 0 !important;
    color: #0f172a !important;
    font-size: 22px !important;
    font-weight: 800 !important;
}
.sa-task-close {
    width: 36px !important;
    height: 36px !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    background: #f8fafc !important;
    color: #334155 !important;
    cursor: pointer !important;
}
.sa-task-summary {
    margin: 18px 22px 0 !important;
    padding: 14px 16px !important;
    border: 1px solid #bfdbfe !important;
    background: #eff6ff !important;
    border-radius: 8px !important;
    display: flex !important;
    align-items: baseline !important;
    gap: 8px !important;
    color: #1e3a8a !important;
}
.sa-task-summary strong {
    font-size: 24px !important;
    line-height: 1 !important;
}
.sa-task-summary span { font-weight: 700 !important; }
.sa-task-list {
    padding: 18px 22px 22px !important;
    overflow-y: auto !important;
}
.sa-task-item {
    display: grid !important;
    grid-template-columns: 42px minmax(0, 1fr) auto !important;
    gap: 14px !important;
    align-items: center !important;
    padding: 14px !important;
    border: 1px solid #e2e8f0 !important;
    border-left-width: 4px !important;
    border-radius: 8px !important;
    margin-bottom: 10px !important;
    background: #ffffff !important;
}
.sa-task-item.info { border-left-color: #2563eb !important; }
.sa-task-item.success { border-left-color: #16a34a !important; }
.sa-task-item.warning { border-left-color: #f59e0b !important; }
.sa-task-item.danger { border-left-color: #dc2626 !important; }
.sa-task-icon {
    width: 42px !important;
    height: 42px !important;
    border-radius: 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #f1f5f9 !important;
    color: #334155 !important;
}
.sa-task-title {
    color: #0f172a !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    flex-wrap: wrap !important;
}
.sa-task-title span {
    min-width: 24px !important;
    height: 22px !important;
    padding: 0 7px !important;
    border-radius: 999px !important;
    background: #e2e8f0 !important;
    color: #0f172a !important;
    font-size: 12px !important;
    line-height: 22px !important;
    text-align: center !important;
}
.sa-task-copy p {
    margin: 4px 0 0 !important;
    color: #64748b !important;
    font-size: 12px !important;
    line-height: 1.45 !important;
}
.sa-task-link {
    padding: 8px 12px !important;
    border-radius: 8px !important;
    background: #2563eb !important;
    color: #ffffff !important;
    text-decoration: none !important;
    font-size: 12px !important;
    font-weight: 800 !important;
    white-space: nowrap !important;
}
.sa-task-empty {
    padding: 38px 20px !important;
    border: 1px dashed #cbd5e1 !important;
    border-radius: 8px !important;
    text-align: center !important;
    color: #475569 !important;
    display: grid !important;
    gap: 8px !important;
}
.sa-task-empty i {
    color: #16a34a !important;
    font-size: 28px !important;
}
@media (max-width: 768px) {
    .sa-task-fab {
        right: 16px !important;
        bottom: 16px !important;
    }
    .sa-task-modal { padding: 14px !important; }
    .sa-task-card { max-height: 90vh !important; }
    .sa-task-item {
        grid-template-columns: 38px minmax(0, 1fr) !important;
    }
    .sa-task-link {
        grid-column: 1 / -1 !important;
        text-align: center !important;
    }
}
</style>

<button type="button" class="sa-task-fab" onclick="openSuperAdminTaskModal()" title="Pengingat eksekusi super admin">
    <i class="fas fa-bell"></i>
    <?php if ($taskReminderTotal > 0): ?>
        <span class="sa-task-fab-count"><?= (int) $taskReminderTotal ?></span>
    <?php endif; ?>
</button>

<div class="sa-task-modal" id="superAdminTaskModal" onclick="closeSuperAdminTaskModal(event)">
    <div class="sa-task-card" role="dialog" aria-modal="true" aria-labelledby="saTaskTitle">
        <div class="sa-task-head">
            <div>
                <div class="sa-task-eyebrow">Super Admin</div>
                <h3 id="saTaskTitle">Pengingat Eksekusi</h3>
            </div>
            <button type="button" class="sa-task-close" onclick="closeSuperAdminTaskModal(event)" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sa-task-summary">
            <strong><?= (int) $taskReminderTotal ?></strong>
            <span>pekerjaan menunggu tindakan</span>
        </div>

        <div class="sa-task-list">
            <?php if (!empty($taskReminderActive)): ?>
                <?php foreach ($taskReminderActive as $item): ?>
                    <div class="sa-task-item <?= htmlspecialchars($item['tone']) ?>">
                        <div class="sa-task-icon">
                            <i class="fas <?= htmlspecialchars($item['icon']) ?>"></i>
                        </div>
                        <div class="sa-task-copy">
                            <div class="sa-task-title">
                                <?= htmlspecialchars($item['title']) ?>
                                <span><?= (int) $item['count'] ?></span>
                            </div>
                            <p><?= htmlspecialchars($item['description']) ?></p>
                        </div>
                        <a class="sa-task-link" href="<?= htmlspecialchars($item['url']) ?>">
                            Buka
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="sa-task-empty">
                    <i class="fas fa-check-circle"></i>
                    <strong>Tidak ada pekerjaan tertunda.</strong>
                    <span>Semua daftar eksekusi utama sedang bersih.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openSuperAdminTaskModal() {
    const modal = document.getElementById('superAdminTaskModal');
    if (modal) modal.classList.add('show');
}

function closeSuperAdminTaskModal(event) {
    const modal = document.getElementById('superAdminTaskModal');
    if (!modal) return;

    if (!event || event.target === modal || event.currentTarget.classList.contains('sa-task-close')) {
        modal.classList.remove('show');
    }
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('superAdminTaskModal');
        if (modal) modal.classList.remove('show');
    }
});
</script>
