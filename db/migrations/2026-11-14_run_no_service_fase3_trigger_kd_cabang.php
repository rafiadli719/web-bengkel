<?php
// Fase 3a rencana no_service: pengaman di batas DB. BEFORE INSERT trigger pada 18 tabel anak servis
// mengisi kd_cabang bila INSERT tidak menyebutkannya (kode lama / jalur yang terlewat), dengan urutan:
//   1. induk tunggal: no_service hanya ada di satu cabang di tblservice -> cabang itu (otoritatif).
//   2. konteks sesi: @kd_cabang_filter (diset config/koneksi.php dari $_SESSION['_cabang']).
//   3. selain itu NULL (baris tidak ikut join gabungan; terlihat di audit sebagai "kosong").
// INSERT yang sudah menulis kd_cabang secara eksplisit tidak diubah.
// Pakai: php 2026-11-14_run_no_service_fase3_trigger_kd_cabang.php [up|down]   (default up, idempotent)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? 'up';
$tabel = ['master_kedatangan_pelanggan','promo_usage_log','tb_antrian_servis','tb_log_antrian','tb_log_cancel_servis','tb_mekanik_progress',
    'tb_progress_mekanik','tbservis_keluhan','tbservis_keluhan_status','tbservis_keluhan_tracking','tbservis_penawaran_jasa','tbservis_penawaran_log',
    'tbservis_penawaran_part','tbservis_pending_items','tbservis_pengerjaan','tbservis_temuan','tbservis_temuan_activity_log','tbservis_workorder'];
foreach ($tabel as $t) {
    $nama = "trg_ns_kdcab_$t";
    if (!mysqli_query($koneksi, "DROP TRIGGER IF EXISTS `$nama`")) { echo "Error: " . mysqli_error($koneksi) . "\n"; exit(1); }
    if ($arah === 'down') { echo "Down: $nama\n"; continue; }
    $sql = "CREATE TRIGGER `$nama` BEFORE INSERT ON `$t` FOR EACH ROW
      BEGIN
        IF NEW.kd_cabang IS NULL OR NEW.kd_cabang = '' THEN
          SET NEW.kd_cabang = COALESCE(
            (SELECT MIN(s.kd_cabang) FROM tblservice s WHERE s.no_service = NEW.no_service HAVING COUNT(DISTINCT s.kd_cabang) = 1),
            NULLIF(@kd_cabang_filter, '')
          );
        END IF;
      END";
    if (!mysqli_query($koneksi, $sql)) { echo "Error $nama: " . mysqli_error($koneksi) . "\n"; exit(1); }
    echo "Up: $nama\n";
}
echo "Migration $arah executed successfully!\n";
