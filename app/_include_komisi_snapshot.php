<?php
// Snapshot komisi mekanik/admin ke tabel servis_komisi saat pembayaran servis
// selesai. Formula identik dengan app/lap_komisi_mekanik.php (laporan live) dan
// app/issue_add.php:eksekusi_revisi_komisi (jalur revisi tiket) — jangan diubah
// tanpa menyamakan ketiganya.
//
// dihitung_saat='bayar' di sini membedakan snapshot normal dari
// dihitung_saat='revisi_tiket' yang diinsert issue_add.php.

function snapshot_komisi_servis($koneksi, $no_service, $kd_cabang) {
    $esc_svc = mysqli_real_escape_string($koneksi, $no_service);
    $esc_cab = mysqli_real_escape_string($koneksi, $kd_cabang);

    $qsvc = mysqli_query($koneksi, "SELECT subtotal_jasa,
                                            mekanik1, mekanik2, mekanik3, mekanik4,
                                            persen_mekanik1, persen_mekanik2, persen_mekanik3, persen_mekanik4,
                                            kepala_mekanik1, kepala_mekanik2,
                                            persen_kepala_mekanik1, persen_kepala_mekanik2,
                                            admin1, admin2,
                                            persen_admin1, persen_admin2
                                     FROM tblservice
                                     WHERE no_service='{$esc_svc}' AND kd_cabang='{$esc_cab}'");
    $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
    if (!$svc) {
        error_log("snapshot_komisi_servis: servis tidak ditemukan no_service={$no_service} kd_cabang={$kd_cabang}");
        return 0;
    }

    $jasa_bersih = (float)($svc['subtotal_jasa'] ?? 0);

    $laba_barang = 0.0;
    $qbarang = mysqli_query($koneksi, "SELECT sb.quantity, sb.total, COALESCE(i.hargapokok,0) AS hargapokok
                                        FROM tblservis_barang sb
                                        LEFT JOIN tblitem i ON i.noitem = sb.no_item
                                        WHERE sb.no_service='{$esc_svc}' AND sb.kd_cabang='{$esc_cab}'");
    if ($qbarang) {
        while ($rb = mysqli_fetch_assoc($qbarang)) {
            $qty = (float)($rb['quantity'] ?? 0);
            $line_total = (float)($rb['total'] ?? 0);
            $hpp = (float)($rb['hargapokok'] ?? 0);
            $laba_barang += max(0, $line_total - ($qty * $hpp));
        }
    }

    $slots = [];
    for ($i = 1; $i <= 4; $i++) {
        if (!empty($svc["mekanik{$i}"])) {
            $slots[] = ['peran' => "mekanik{$i}", 'persen' => (int)($svc["persen_mekanik{$i}"] ?? 0)];
        }
    }
    for ($i = 1; $i <= 2; $i++) {
        if (!empty($svc["kepala_mekanik{$i}"])) {
            $slots[] = ['peran' => "kepala_mekanik{$i}", 'persen' => (int)($svc["persen_kepala_mekanik{$i}"] ?? 0)];
        }
    }
    $jml_mek = 0;
    foreach ($slots as $s) {
        if (strpos($s['peran'], 'mekanik') === 0 || strpos($s['peran'], 'kepala_mekanik') === 0) $jml_mek++;
    }

    for ($i = 1; $i <= 2; $i++) {
        if (!empty($svc["admin{$i}"])) {
            $slots[] = ['peran' => "admin{$i}", 'persen' => (int)($svc["persen_admin{$i}"] ?? 0)];
        }
    }

    if (empty($slots)) return 0;

    $stmt = mysqli_prepare($koneksi, "INSERT INTO servis_komisi
        (no_service, kd_cabang, peran, nominal_jasa, nominal_barang, persen_terpakai, dihitung_saat)
        VALUES (?, ?, ?, ?, ?, ?, 'bayar')");

    $inserted = 0;
    foreach ($slots as $slot) {
        $peran = $slot['peran'];
        $persen = $slot['persen'];

        if (strpos($peran, 'admin') === 0) {
            $nominal_jasa = $jasa_bersih * 0.05;
            $nominal_barang = $laba_barang * 0.05;
        } else {
            $divisor = $jml_mek > 0 ? $jml_mek : 1;
            $nominal_jasa = ($jasa_bersih * 0.20) / $divisor;
            $nominal_barang = ($laba_barang * 0.05) / $divisor;
        }

        mysqli_stmt_bind_param($stmt, "sssddi", $no_service, $kd_cabang, $peran, $nominal_jasa, $nominal_barang, $persen);
        if (mysqli_stmt_execute($stmt)) {
            $inserted++;
        } else {
            error_log("snapshot_komisi_servis: insert gagal no_service={$no_service} peran={$peran}: " . mysqli_stmt_error($stmt));
        }
    }
    mysqli_stmt_close($stmt);

    return $inserted;
}
