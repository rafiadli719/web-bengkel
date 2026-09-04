<?php
// Sumber: web_kasir/includes/sidebar.php — nav dashboard admin/keuangan.
// Gerbang session + role lookup asli (SELECT role FROM users) dibuang;
// halaman pemanggil sudah lewat koneksi_kasir.php + requirePermission(),
// role admin/super_admin cukup dibaca dari $legacy_session_kasir yang
// sudah tersedia di scope pemanggil (KEU=admin, ADM=super_admin).
$is_super_admin = ($legacy_session_kasir['role'] ?? '') === 'super_admin';
$is_admin       = ($legacy_session_kasir['role'] ?? '') === 'admin';
$kode_karyawan  = $kode_karyawan_aktif ?? '';

$current_page = basename($_SERVER['PHP_SELF']);

// Map halaman ke ID kategori (untuk auto-expand + highlight header)
$page_cat = [
    'detail_pemasukan.php'          => 'laporan',
    'detail_pengeluaran.php'        => 'laporan',
    'detail_omset.php'              => 'laporan',
    'laporan_setoran.php'           => 'laporan',
    'laporan_keuangan_pusat.php'    => 'laporan',
    'master_akun.php'               => 'master',
    'master_nama_transaksi.php'     => 'master',
    'keping.php'                    => 'master',
    'users.php'                     => 'master',
    'masterkey.php'                 => 'master',
    'cabang.php'                    => 'master',
    'master_rekening_cabang.php'    => 'master',
    'index_kasir.php'               => 'operasional',
    'konfirmasi_buka_transaksi.php' => 'operasional',
    'admin_closing_revision.php'    => 'operasional',
    'setoran_keuangan.php'          => 'keuangan',
    'keuangan_pusat.php'            => 'keuangan',
    'setoran_bank_rekap.php'        => 'keuangan',
    'monitoring_setoran.php'        => 'keuangan',
];
$active_cat = $page_cat[$current_page] ?? '';

// Helper: "open" class jika kategori ini adalah yang aktif
function catOpen(string $id, string $active): string {
    return $id === $active ? 'open' : '';
}
// Helper: class rotated untuk chevron
function chevRot(string $id, string $active): string {
    return $id === $active ? 'rotated' : '';
}
// Helper: class "has-active" untuk wrapper kategori
function catHasActive(string $id, string $active): string {
    return $id === $active ? 'has-active' : '';
}
// Helper: active class untuk link
function isActive(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}
?>

<button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Buka menu admin" aria-expanded="false">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" hidden></div>

<div class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <i class="fas fa-user-shield"></i>
        <span>Dashboard Admin</span>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">

        <!-- Dashboard -->
        <a href="admin_dashboard.php" class="nav-link <?= isActive('admin_dashboard.php', $current_page) ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <!-- ── LAPORAN ── -->
        <div class="nav-category <?= catHasActive('laporan', $active_cat) ?>" id="nav-laporan">
            <div class="nav-cat-header" onclick="toggleCat('laporan')">
                <span class="cat-label">
                    <i class="fas fa-chart-bar cat-icon"></i>Laporan
                </span>
                <i class="fas fa-chevron-right nav-chevron <?= chevRot('laporan', $active_cat) ?>"></i>
            </div>
            <div class="nav-cat-items <?= catOpen('laporan', $active_cat) ?>" id="cat-laporan">
                <a href="detail_pemasukan.php" class="nav-link sub-item <?= isActive('detail_pemasukan.php', $current_page) ?>">
                    <i class="fas fa-arrow-circle-down"></i><span>Detail Pemasukan</span>
                </a>
                <a href="detail_pengeluaran.php" class="nav-link sub-item <?= isActive('detail_pengeluaran.php', $current_page) ?>">
                    <i class="fas fa-arrow-circle-up"></i><span>Detail Pengeluaran</span>
                </a>
                <a href="detail_omset.php" class="nav-link sub-item <?= isActive('detail_omset.php', $current_page) ?>">
                    <i class="fas fa-chart-line"></i><span>Detail Omset</span>
                </a>
            </div>
        </div>

        <!-- ── MASTER DATA ── -->
        <div class="nav-category <?= catHasActive('master', $active_cat) ?>" id="nav-master">
            <div class="nav-cat-header" onclick="toggleCat('master')">
                <span class="cat-label">
                    <i class="fas fa-database cat-icon"></i>Master Data
                </span>
                <i class="fas fa-chevron-right nav-chevron <?= chevRot('master', $active_cat) ?>"></i>
            </div>
            <div class="nav-cat-items <?= catOpen('master', $active_cat) ?>" id="cat-master">
                <a href="master_akun.php" class="nav-link sub-item <?= isActive('master_akun.php', $current_page) ?>">
                    <i class="fas fa-users-cog"></i><span>Master Akun</span>
                </a>
                <a href="master_nama_transaksi.php" class="nav-link sub-item <?= isActive('master_nama_transaksi.php', $current_page) ?>">
                    <i class="fas fa-file-signature"></i><span>Nama Transaksi</span>
                </a>
                <a href="keping.php" class="nav-link sub-item <?= isActive('keping.php', $current_page) ?>">
                    <i class="fas fa-coins"></i><span>Master Nominal</span>
                </a>
                <?php if ($is_super_admin): ?>
                <a href="users.php" class="nav-link sub-item <?= isActive('users.php', $current_page) ?>">
                    <i class="fas fa-user-friends"></i><span>Master User</span>
                </a>
                <a href="masterkey.php" class="nav-link sub-item <?= isActive('masterkey.php', $current_page) ?>">
                    <i class="fas fa-id-card"></i><span>Master Karyawan</span>
                </a>
                <a href="cabang.php" class="nav-link sub-item <?= isActive('cabang.php', $current_page) ?>">
                    <i class="fas fa-building"></i><span>Master Cabang</span>
                </a>
                <a href="master_rekening_cabang.php" class="nav-link sub-item <?= isActive('master_rekening_cabang.php', $current_page) ?>">
                    <i class="fas fa-university"></i><span>Master Rekening</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── OPERASIONAL (admin + super_admin) ── -->
        <?php if ($is_admin || $is_super_admin): ?>
        <div class="nav-category <?= catHasActive('operasional', $active_cat) ?>" id="nav-operasional">
            <div class="nav-cat-header" onclick="toggleCat('operasional')">
                <span class="cat-label">
                    <i class="fas fa-cash-register cat-icon"></i>Operasional
                </span>
                <i class="fas fa-chevron-right nav-chevron <?= chevRot('operasional', $active_cat) ?>"></i>
            </div>
            <div class="nav-cat-items <?= catOpen('operasional', $active_cat) ?>" id="cat-operasional">
                <a href="index_kasir.php" class="nav-link sub-item <?= isActive('index_kasir.php', $current_page) ?>">
                    <i class="fas fa-store"></i><span>Dashboard Kasir</span>
                </a>
                <?php if ($is_super_admin): ?>
                <a href="konfirmasi_buka_transaksi.php" class="nav-link sub-item <?= isActive('konfirmasi_buka_transaksi.php', $current_page) ?>">
                    <i class="fas fa-unlock-alt"></i><span>Konfirmasi Transaksi</span>
                </a>
                <a href="admin_closing_revision.php" class="nav-link sub-item <?= isActive('admin_closing_revision.php', $current_page) ?>">
                    <i class="fas fa-code-branch"></i><span>Approval Revisi Closing</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── KEUANGAN (super_admin) ── -->
        <?php if ($is_super_admin): ?>
        <div class="nav-category <?= catHasActive('keuangan', $active_cat) ?>" id="nav-keuangan">
            <div class="nav-cat-header" onclick="toggleCat('keuangan')">
                <span class="cat-label">
                    <i class="fas fa-wallet cat-icon"></i>Keuangan
                </span>
                <i class="fas fa-chevron-right nav-chevron <?= chevRot('keuangan', $active_cat) ?>"></i>
            </div>
            <div class="nav-cat-items <?= catOpen('keuangan', $active_cat) ?>" id="cat-keuangan">
                <a href="setoran_keuangan.php" class="nav-link sub-item <?= isActive('setoran_keuangan.php', $current_page) ?>">
                    <i class="fas fa-hand-holding-usd"></i><span>Manajemen Setoran</span>
                </a>
                <a href="keuangan_pusat.php" class="nav-link sub-item <?= isActive('keuangan_pusat.php', $current_page) ?>">
                    <i class="fas fa-wallet"></i><span>Keuangan Pusat</span>
                </a>
                <a href="setoran_bank_rekap.php" class="nav-link sub-item <?= isActive('setoran_bank_rekap.php', $current_page) ?>">
                    <i class="fas fa-university"></i><span>Riwayat Setoran Bank</span>
                </a>
                <a href="monitoring_setoran.php" class="nav-link sub-item <?= isActive('monitoring_setoran.php', $current_page) ?>">
                    <i class="fas fa-chart-line"></i><span>Monitoring Setoran</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

    </nav><!-- /sidebar-nav -->

    <!-- Footer / Logout -->
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>

</div><!-- /sidebar -->

<script>
(function () {
    'use strict';

    /* ── Toggle kategori sidebar dengan animasi smooth ── */
    window.toggleCat = function (id) {
        var items   = document.getElementById('cat-' + id);
        var wrapper = document.getElementById('nav-' + id);
        if (!items) return;

        var isOpen = items.classList.toggle('open');

        // Rotate chevron
        var chevron = wrapper ? wrapper.querySelector('.nav-chevron') : null;
        if (chevron) chevron.classList.toggle('rotated', isOpen);

        // Simpan state ke localStorage
        try { localStorage.setItem('sc_' + id, isOpen ? '1' : '0'); } catch (e) {}
    };

    /* ── Restore state dari localStorage saat halaman load ── */
    document.addEventListener('DOMContentLoaded', function () {
        var cats = ['laporan', 'master', 'operasional', 'keuangan'];

        cats.forEach(function (id) {
            var items   = document.getElementById('cat-' + id);
            var wrapper = document.getElementById('nav-' + id);
            if (!items) return;

            // Jika sudah open dari PHP (halaman aktif) → jangan ubah, hanya sinkronkan chevron
            if (items.classList.contains('open')) {
                var ch = wrapper ? wrapper.querySelector('.nav-chevron') : null;
                if (ch) ch.classList.add('rotated');
                return;
            }

            // Restore dari localStorage jika user pernah membuka kategori ini
            try {
                if (localStorage.getItem('sc_' + id) === '1') {
                    items.classList.add('open');
                    var ch2 = wrapper ? wrapper.querySelector('.nav-chevron') : null;
                    if (ch2) ch2.classList.add('rotated');
                }
            } catch (e) {}
        });

        /* Scroll active link into view */
        var activeLink = document.querySelector('.sidebar .nav-link.active');
        if (activeLink) {
            activeLink.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    });

    function isMobileAdminView() {
        return window.matchMedia('(max-width: 768px)').matches;
    }

    function closeAdminSidebar() {
        var sb = document.getElementById('sidebar');
        var toggle = document.getElementById('adminSidebarToggle');
        var backdrop = document.getElementById('adminSidebarBackdrop');

        if (sb) sb.classList.remove('active');
        document.body.classList.remove('admin-sidebar-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.innerHTML = '<i class="fas fa-bars"></i>';
        }
        if (backdrop) backdrop.hidden = true;
    }

    function openAdminSidebar() {
        var sb = document.getElementById('sidebar');
        var toggle = document.getElementById('adminSidebarToggle');
        var backdrop = document.getElementById('adminSidebarBackdrop');

        if (sb) {
            sb.classList.remove('hidden');
            sb.classList.add('active');
        }
        document.body.classList.add('admin-sidebar-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.innerHTML = '<i class="fas fa-times"></i>';
        }
        if (backdrop) backdrop.hidden = false;
    }

    function toggleAdminSidebar() {
        var isOpen = document.body.classList.contains('admin-sidebar-open');
        if (isOpen) closeAdminSidebar();
        else openAdminSidebar();
    }

    /* ── Paksa sidebar width desktop, mobile dibuat off-canvas ── */
    function fixSidebarWidth() {
        var sb   = document.getElementById('sidebar');
        var main = document.querySelector('.main-content');
        if (isMobileAdminView()) {
            if (sb) {
                sb.style.width = '';
                sb.style.minWidth = '';
            }
            if (main) {
                main.style.marginLeft = '0';
                main.style.width = '100%';
            }
            if (!document.body.classList.contains('admin-sidebar-open')) {
                closeAdminSidebar();
            }
            return;
        }

        closeAdminSidebar();
        if (sb)   { sb.style.width = '260px'; sb.style.minWidth = '260px'; }
        if (main) { main.style.marginLeft = '260px'; main.style.width = 'calc(100% - 260px)'; }
    }

    /* Override fungsi adjustSidebarWidth dari halaman lama sebelum ia dijalankan */
    window.adjustSidebarWidth = fixSidebarWidth;

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('adminSidebarToggle');
        var backdrop = document.getElementById('adminSidebarBackdrop');

        if (toggle) toggle.addEventListener('click', toggleAdminSidebar);
        if (backdrop) backdrop.addEventListener('click', closeAdminSidebar);

        document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobileAdminView()) closeAdminSidebar();
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeAdminSidebar();
        });

        fixSidebarWidth();
    });
    window.addEventListener('load', function () {
        /* Jalankan setelah semua load handler (termasuk adjustSidebarWidth dari halaman) */
        setTimeout(fixSidebarWidth, 50);
    });
    window.addEventListener('resize', fixSidebarWidth);

})();
</script>

<?php
// KOREKSI (2026-09-04): temuan lama "file tidak ada" SALAH — file ada
// dan aktif di source (includes/super_admin_task_reminder.php), sekarang
// sudah diport. $is_super_admin sudah dihitung di atas file ini.
include __DIR__ . '/super_admin_task_reminder.php';

