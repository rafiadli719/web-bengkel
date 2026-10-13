<?php
// Helper kecil untuk halaman keuangan pusat (edit/hapus/detail). Tabel dipilih lewat whitelist,
// jangan pernah menyisipkan nama tabel dari input user langsung.
function kpTabel($jenis) {
    if ($jenis === 'pemasukan')   { return 'pemasukan_pusat_closing_kasir'; }
    if ($jenis === 'pengeluaran') { return 'pengeluaran_pusat_closing_kasir'; }
    return null;
}
function kpRupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function kpTglValid($t) {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) && strtotime($t) !== false;
}
