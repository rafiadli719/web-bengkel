<?php
// Runner sekali-pakai untuk 2026-07-17_f4_alarm_harga_beli.sql.
// Trigger dijalankan sebagai single query terpisah (bukan mysqli_multi_query)
// supaya ';' di dalam body trigger tidak kepotong oleh multi-statement splitting.
include __DIR__ . "/../../config/koneksi.php";

$sql_tables = file_get_contents(__DIR__ . "/2026-07-17_f4_alarm_harga_beli.sql");
if (mysqli_multi_query($koneksi, $sql_tables)) {
    do {
        if ($result = mysqli_store_result($koneksi)) mysqli_free_result($result);
    } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));
    echo "OK - tabel tb_master_threshold_harga & alarm_harga_beli dibuat + seed\n";
} else {
    echo "ERROR tabel: " . mysqli_error($koneksi) . "\n";
}

mysqli_query($koneksi, "DROP TRIGGER IF EXISTS trg_alarm_harga_beli");

$trigger = "
CREATE TRIGGER trg_alarm_harga_beli AFTER INSERT ON tblpembelian_detail
FOR EACH ROW
BEGIN
  DECLARE v_harga_lama DOUBLE DEFAULT NULL;
  DECLARE v_persen DOUBLE DEFAULT NULL;
  DECLARE v_arah VARCHAR(10) DEFAULT NULL;
  DECLARE v_threshold DOUBLE DEFAULT NULL;
  DECLARE v_hargajual DOUBLE DEFAULT NULL;
  DECLARE v_klasifikasi VARCHAR(50) DEFAULT NULL;

  IF NEW.harga_pokok > 0 THEN
    SELECT harga_pokok INTO v_harga_lama
      FROM tblpembelian_detail
      WHERE no_item = NEW.no_item AND id <> NEW.id
      ORDER BY id DESC LIMIT 1;

    IF v_harga_lama IS NOT NULL AND v_harga_lama > 0 AND NEW.harga_pokok <> v_harga_lama THEN
      SET v_persen = ((NEW.harga_pokok - v_harga_lama) / v_harga_lama) * 100;

      IF v_persen > 0 THEN
        SET v_arah = 'naik';
        SELECT persen_threshold INTO v_threshold FROM tb_master_threshold_harga WHERE arah='naik' AND aktif=1 LIMIT 1;
      ELSE
        SET v_arah = 'turun';
        SELECT persen_threshold INTO v_threshold FROM tb_master_threshold_harga WHERE arah='turun' AND aktif=1 LIMIT 1;
      END IF;

      IF v_threshold IS NOT NULL AND ABS(v_persen) >= v_threshold THEN
        SELECT hargajual INTO v_hargajual FROM tblitem WHERE noitem = NEW.no_item LIMIT 1;
        SET v_klasifikasi = IF(v_arah='naik', 'Harga Beli Naik & Harga Jual perlu Naik', 'Harga Pokok Turun');

        INSERT INTO alarm_harga_beli
          (no_item, no_transaksi_pembelian, harga_beli_lama, harga_beli_baru, persen_selisih, arah, threshold_saat_itu, harga_jual_saat_ini, status_klasifikasi, status_review)
        VALUES
          (NEW.no_item, NEW.no_transaksi, v_harga_lama, NEW.harga_pokok, v_persen, v_arah, v_threshold, v_hargajual, v_klasifikasi, 'belum_direview');
      END IF;
    END IF;
  END IF;
END
";

if (mysqli_query($koneksi, $trigger)) {
    echo "OK - trigger trg_alarm_harga_beli dibuat\n";
} else {
    echo "ERROR trigger: " . mysqli_error($koneksi) . "\n";
}
