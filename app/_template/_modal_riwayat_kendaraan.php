<?php
/**
 * Modal Riwayat Kendaraan
 * Menampilkan Riwayat Service dan Riwayat Mekanik dalam satu modal
 */

// Get vehicle plate number
$vehicle_no_polisi = $no_polisi ?? '';

// Initialize arrays
$service_history = [];

if (!empty($vehicle_no_polisi) && isset($koneksi)) {
    // Query untuk Riwayat Service (selesai/lunas), konsisten dengan Riwayat Mekanik
    $query_service = "SELECT s.no_service,
                             DATE_FORMAT(s.tanggal,'%d/%m/%Y') AS tanggal_serv,
                             s.km_skr,
                             s.status_servis,
                             s.kepala_mekanik1, s.kepala_mekanik2,
                             s.mekanik1, s.mekanik2, s.mekanik3, s.mekanik4,
                             s.persen_kepala_mekanik1, s.persen_kepala_mekanik2,
                             s.persen_mekanik1, s.persen_mekanik2, s.persen_mekanik3, s.persen_mekanik4
                      FROM tblservice s
                      WHERE s.no_polisi='".$vehicle_no_polisi."'
                      AND s.status_servis IN ('selesai','bayar')
                      ORDER BY s.tanggal DESC, s.no_service DESC
                      LIMIT 10";
    
    $result_service = mysqli_query($koneksi, $query_service);
    
    if ($result_service && mysqli_num_rows($result_service) > 0) {
        while ($row = mysqli_fetch_array($result_service)) {
            $service_history[] = $row;
        }
    }
    
}

// Function to get mechanic name
function getMechanicNameModal($koneksi, $mechanic_code) {
    return getMekanikNama($koneksi, $mechanic_code);
}
?>

<!-- Modal Riwayat Kendaraan -->
<style>
.svc-modal-header{background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 20px;border-radius:5px 5px 0 0;}
.svc-modal-header .modal-title{color:#1f2937;font-size:17px;font-weight:600;}
.svc-modal-header .modal-title i{color:#4f46e5;margin-right:6px;}
.svc-modal-header .close{color:#9ca3af;opacity:1;text-shadow:none;}
.svc-modal-header .close:hover{color:#374151;}
</style>
<div class="modal fade" id="modalRiwayatKendaraan" tabindex="-1" role="dialog" aria-labelledby="modalRiwayatKendaraanLabel">
    <div class="modal-dialog modal-lg" role="document" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header svc-modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalRiwayatKendaraanLabel">
                    <i class="ace-icon fa fa-history"></i><strong>Riwayat Kendaraan<?php echo !empty($vehicle_no_polisi) ? ': ' . htmlspecialchars($vehicle_no_polisi) : ''; ?></strong>
                </h4>
            </div>

            <div class="modal-body" style="padding: 20px;">
                <?php if (empty($vehicle_no_polisi)): ?>
                    <!-- Alert jika no polisi kosong -->
                    <div class="alert alert-warning">
                        <i class="ace-icon fa fa-exclamation-triangle"></i>
                        <strong>Perhatian!</strong> Nomor polisi kendaraan belum diisi. Silakan isi nomor polisi terlebih dahulu untuk melihat riwayat kendaraan.
                    </div>
                <?php else: ?>
                        <div class="alert alert-info" style="margin-bottom: 15px;">
                            <i class="ace-icon fa fa-info-circle"></i>
                            <strong>Info:</strong> Menampilkan 10 riwayat service terakhir untuk kendaraan ini
                        </div>

                        <?php if (empty($service_history)): ?>
                            <div class="alert alert-warning">
                                <i class="ace-icon fa fa-exclamation-triangle"></i>
                                Belum ada riwayat service untuk kendaraan ini.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr class="info">
                                            <th class="center" style="width: 40px;">No</th>
                                            <th>No. Service</th>
                                            <th class="center">Tanggal</th>
                                            <th class="center">KM</th>
                                            <th>Keluhan Sebelumnya</th>
                                            <th>Mekanik</th>
                                            <th class="center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        foreach ($service_history as $history):
                                            $no_service_history = $history['no_service'];
                                            
                                            // Status badge
                                            $status_badge = '';
                                            switch($history['status_servis']) {
                                                case 'selesai':
                                                    $status_badge = '<span class="label label-success">Selesai</span>';
                                                    break;
                                                case 'bayar':
                                                    $status_badge = '<span class="label label-info">Lunas</span>';
                                                    break;
                                                default:
                                                    $status_badge = '<span class="label label-default">' . ucfirst($history['status_servis']) . '</span>';
                                            }
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($history['no_service']); ?></strong>
                                            </td>
                                            <td class="center"><?php echo $history['tanggal_serv']; ?></td>
                                            <td class="center"><?php echo number_format($history['km_skr'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php
                                                    $keluhan_history_table = 'tbservis_keluhan_status';
                                                    $sql_keluhan = mysqli_query($koneksi, "SELECT keluhan FROM $keluhan_history_table
                                                                                          WHERE no_service='$no_service_history' LIMIT 5");
                                                    $keluhan_count = 0;
                                                    while ($keluhan = mysqli_fetch_array($sql_keluhan)) {
                                                        $keluhan_count++;
                                                        echo "<small>• " . htmlspecialchars($keluhan['keluhan']) . "</small><br>";
                                                    }
                                                    if($keluhan_count == 0) {
                                                        echo "<em><small class='text-muted'>Tidak ada keluhan tercatat</small></em>";
                                                    }

                                                    $sql_temuan_tolak = mysqli_query($koneksi, "SELECT COALESCE(mt.nama_temuan, t.temuan_custom) AS nama_temuan, t.keterangan_tidak_selesai
                                                                                             FROM tbservis_temuan t
                                                                                             LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                                                                                             WHERE t.no_service='$no_service_history'
                                                                                               AND (t.status_temuan IN ('ditolak','tidak_selesai') OR (t.keterangan_tidak_selesai IS NOT NULL AND t.keterangan_tidak_selesai <> ''))
                                                                                             ORDER BY t.created_at DESC
                                                                                             LIMIT 3");
                                                    if ($sql_temuan_tolak && mysqli_num_rows($sql_temuan_tolak) > 0) {
                                                        echo "<div style='margin-top:6px;'><small class='text-danger'><strong>Temuan ditolak/tidak selesai:</strong></small><br>";
                                                        while ($trow = mysqli_fetch_array($sql_temuan_tolak)) {
                                                            $nm = $trow['nama_temuan'] ?? '';
                                                            $ket = $trow['keterangan_tidak_selesai'] ?? '';
                                                            echo "<small class='text-danger'>• " . htmlspecialchars($nm) . ($ket !== '' ? (": " . htmlspecialchars($ket)) : "") . "</small><br>";
                                                        }
                                                        echo "</div>";
                                                    }

                                                    $sql_pen_tolak = mysqli_query($koneksi, "SELECT nama_barang, alasan_tolak
                                                                                           FROM tbservis_penawaran_part
                                                                                           WHERE no_service='$no_service_history'
                                                                                             AND status_penawaran='ditolak'
                                                                                           ORDER BY tanggal_respon DESC, updated_at DESC, created_at DESC
                                                                                           LIMIT 3");
                                                    if ($sql_pen_tolak && mysqli_num_rows($sql_pen_tolak) > 0) {
                                                        echo "<div style='margin-top:6px;'><small class='text-danger'><strong>Penawaran part ditolak:</strong></small><br>";
                                                        while ($prow = mysqli_fetch_array($sql_pen_tolak)) {
                                                            $nb = $prow['nama_barang'] ?? '';
                                                            $al = $prow['alasan_tolak'] ?? '';
                                                            echo "<small class='text-danger'>• " . htmlspecialchars($nb) . ($al !== '' ? (" (" . htmlspecialchars($al) . ")") : "") . "</small><br>";
                                                        }
                                                        echo "</div>";
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $hist_kepala_list = [];
                                                    if (!empty($history['kepala_mekanik1'])) {
                                                        $p = $history['persen_kepala_mekanik1'];
                                                        $hist_kepala_list[] = getMechanicNameModal($koneksi, $history['kepala_mekanik1']) . ($p > 0 ? " ({$p}%)" : '');
                                                    }
                                                    if (!empty($history['kepala_mekanik2'])) {
                                                        $p = $history['persen_kepala_mekanik2'];
                                                        $hist_kepala_list[] = getMechanicNameModal($koneksi, $history['kepala_mekanik2']) . ($p > 0 ? " ({$p}%)" : '');
                                                    }
                                                    $hist_mekanik_list = [];
                                                    foreach ([1,2,3,4] as $mi) {
                                                        $kode_mek = $history["mekanik$mi"] ?? '';
                                                        if (!empty($kode_mek)) {
                                                            $p = $history["persen_mekanik$mi"];
                                                            $hist_mekanik_list[] = getMechanicNameModal($koneksi, $kode_mek) . ($p > 0 ? " ({$p}%)" : '');
                                                        }
                                                    }
                                                    if (empty($hist_kepala_list) && empty($hist_mekanik_list)) {
                                                        echo '<span class="text-muted">-</span>';
                                                    } else {
                                                        foreach ($hist_kepala_list as $km) {
                                                            echo '<div><small><i class="ace-icon fa fa-user-md blue"></i> ' . htmlspecialchars($km) . '</small></div>';
                                                        }
                                                        foreach ($hist_mekanik_list as $m) {
                                                            echo '<div><small><i class="ace-icon fa fa-wrench green"></i> ' . htmlspecialchars($m) . '</small></div>';
                                                        }
                                                    }
                                                ?>
                                            </td>
                                            <td class="center"><?php echo $status_badge; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>
