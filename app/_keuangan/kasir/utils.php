<?php
/**
 * Shared utility functions
 */

/**
 * Function untuk menentukan jenis closing otomatis
 */
function determineClosingType($pdo, $kode_transaksi, $nama_cabang) {
    try {
        // 1. Cek apakah transaksi ini sebelumnya dipinjam dari kasir lain
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kasir_transactions_closing_kasir 
                              WHERE kode_transaksi = ? AND jenis_closing = 'dipinjam'");
        $stmt->execute([$kode_transaksi]);
        if ($stmt->fetchColumn() > 0) {
            return 'dipinjam';
        }

        // 2. Cek apakah ada transaksi sedang berjalan dengan pemasukan "DARI CLOSING" hari ini
        $sql = "SELECT COUNT(*) FROM pemasukan_kasir_closing_kasir pk
                JOIN kasir_transactions_closing_kasir kt ON pk.nomor_transaksi_closing = kt.kode_transaksi
                WHERE (
                    pk.kode_akun = 'DRCLSG'
                    OR UPPER(pk.keterangan_transaksi) LIKE '%DARI CLOSING%'
                )
                AND kt.nama_cabang = ?
                AND DATE(pk.tanggal) = CURDATE()
                AND kt.status = 'on proses'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama_cabang]);
        if ($stmt->fetchColumn() > 0) {
            return 'meminjam';
        }

        // 3. Default: closing normal
        return 'closing';

    } catch (PDOException $e) {
        error_log("Error in determineClosingType: " . $e->getMessage());
        return 'closing'; // default fallback
    }
}
?>