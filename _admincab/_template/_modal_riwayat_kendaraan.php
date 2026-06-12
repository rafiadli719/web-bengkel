<?php
/**
 * Modal Riwayat Kendaraan
 * Menampilkan Riwayat Service dan Riwayat Mekanik dalam satu modal
 */

// Get vehicle plate number
$vehicle_no_polisi = $no_polisi ?? '';

// Initialize arrays
$service_history = [];
$mechanic_history = [];

if (!empty($vehicle_no_polisi) && isset($koneksi)) {
    // Query untuk Riwayat Service (selesai/lunas), konsisten dengan Riwayat Mekanik
    $query_service = "SELECT s.no_service,
                             DATE_FORMAT(s.tanggal,'%d/%m/%Y') AS tanggal_serv,
                             s.km_skr,
                             s.status_servis
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
    
    // Query untuk Riwayat Mekanik
    $query_mechanic = "SELECT 
                        s.no_service,
                        s.tanggal,
                        s.status_servis,
                        s.kepala_mekanik1,
                        s.kepala_mekanik2,
                        s.mekanik1,
                        s.mekanik2,
                        s.mekanik3,
                        s.mekanik4,
                        s.persen_kepala_mekanik1,
                        s.persen_kepala_mekanik2,
                        s.persen_mekanik1,
                        s.persen_mekanik2,
                        s.persen_mekanik3,
                        s.persen_mekanik4,
                        DATE_FORMAT(s.tanggal, '%d/%m/%Y') as tanggal_format,
                        CONCAT(
                            COALESCE(
                                (SELECT GROUP_CONCAT(DISTINCT wh.nama_wo SEPARATOR ', ')
                                 FROM tbservis_workorder sw
                                 LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                                 WHERE sw.no_service = s.no_service), 
                                ''
                            ),
                            IF(
                                (SELECT COUNT(*) FROM tblservis_jasa WHERE no_service = s.no_service) > 0,
                                CONCAT(
                                    IF(
                                        (SELECT GROUP_CONCAT(DISTINCT wh.nama_wo SEPARATOR ', ')
                                         FROM tbservis_workorder sw
                                         LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                                         WHERE sw.no_service = s.no_service) IS NOT NULL,
                                        ', ',
                                        ''
                                    ),
                                    'Jasa Custom'
                                ),
                                ''
                            )
                        ) as pekerjaan
                      FROM tblservice s
                      WHERE s.no_polisi = '$vehicle_no_polisi'
                      AND s.status_servis IN ('selesai', 'bayar')
                      AND (s.kepala_mekanik1 IS NOT NULL 
                           OR s.kepala_mekanik2 IS NOT NULL
                           OR s.mekanik1 IS NOT NULL 
                           OR s.mekanik2 IS NOT NULL 
                           OR s.mekanik3 IS NOT NULL 
                           OR s.mekanik4 IS NOT NULL)
                      ORDER BY s.tanggal DESC, s.no_service DESC
                      LIMIT 10";
    
    $result_mechanic = mysqli_query($koneksi, $query_mechanic);
    
    if ($result_mechanic && mysqli_num_rows($result_mechanic) > 0) {
        while ($row = mysqli_fetch_array($result_mechanic)) {
            $mechanic_history[] = $row;
        }
    }
}

// Function to get mechanic name
function getMechanicNameModal($koneksi, $mechanic_code) {
    if (empty($mechanic_code)) return '';
    
    $query = "SELECT nama FROM tblmekanik WHERE nomekanik = '$mechanic_code' LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        return $row['nama'];
    }
    
    return $mechanic_code;
}
?>

<!-- Modal Riwayat Kendaraan -->
<div class="modal fade" id="modalRiwayatKendaraan" tabindex="-1" role="dialog" aria-labelledby="modalRiwayatKendaraanLabel">
    <div class="modal-dialog modal-lg" role="document" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalRiwayatKendaraanLabel">
                    <i class="ace-icon fa fa-history"></i>
                    <strong>Riwayat Kendaraan<?php echo !empty($vehicle_no_polisi) ? ': ' . htmlspecialchars($vehicle_no_polisi) : ''; ?></strong>
                </h4>
            </div>
            
            <div class="modal-body" style="padding: 0;">
                <?php if (empty($vehicle_no_polisi)): ?>
                    <!-- Alert jika no polisi kosong -->
                    <div style="padding: 20px;">
                        <div class="alert alert-warning">
                            <i class="ace-icon fa fa-exclamation-triangle"></i>
                            <strong>Perhatian!</strong> Nomor polisi kendaraan belum diisi. Silakan isi nomor polisi terlebih dahulu untuk melihat riwayat kendaraan.
                        </div>
                    </div>
                <?php else: ?>
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" role="tablist" style="margin: 0; padding: 15px 15px 0 15px; background-color: #f5f5f5;">
                    <li role="presentation" class="active">
                        <a href="#tab-service-history" aria-controls="tab-service-history" role="tab" data-toggle="tab">
                            <i class="ace-icon fa fa-list purple"></i>
                            <strong>Riwayat Service</strong>
                            <span class="badge badge-purple"><?php echo count($service_history); ?></span>
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-mechanic-history" aria-controls="tab-mechanic-history" role="tab" data-toggle="tab">
                            <i class="ace-icon fa fa-wrench blue"></i>
                            <strong>Riwayat Mekanik</strong>
                            <span class="badge badge-info"><?php echo count($mechanic_history); ?></span>
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" style="padding: 20px;">
                    
                    <!-- Tab Riwayat Service -->
                    <div role="tabpanel" class="tab-pane active" id="tab-service-history">
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
                                            <td class="center"><?php echo $status_badge; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tab Riwayat Mekanik -->
                    <div role="tabpanel" class="tab-pane" id="tab-mechanic-history">
                        <div class="alert alert-info" style="margin-bottom: 15px;">
                            <i class="ace-icon fa fa-info-circle"></i>
                            <strong>Info:</strong> Menampilkan 10 riwayat service terakhir dengan mekanik yang ditugaskan
                        </div>
                        
                        <?php if (empty($mechanic_history)): ?>
                            <div class="alert alert-warning">
                                <i class="ace-icon fa fa-exclamation-triangle"></i>
                                Belum ada riwayat service dengan mekanik untuk kendaraan ini.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr class="info">
                                            <th class="center" style="width: 40px;">No</th>
                                            <th>No. Service</th>
                                            <th class="center">Tanggal</th>
                                            <th>Pekerjaan</th>
                                            <th>Kepala Mekanik</th>
                                            <th>Mekanik</th>
                                            <th class="center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        foreach ($mechanic_history as $history): 
                                            // Collect kepala mekanik
                                            $kepala_mekanik_list = [];
                                            if (!empty($history['kepala_mekanik1'])) {
                                                $name = getMechanicNameModal($koneksi, $history['kepala_mekanik1']);
                                                $persen = $history['persen_kepala_mekanik1'];
                                                $kepala_mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                            }
                                            if (!empty($history['kepala_mekanik2'])) {
                                                $name = getMechanicNameModal($koneksi, $history['kepala_mekanik2']);
                                                $persen = $history['persen_kepala_mekanik2'];
                                                $kepala_mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                            }
                                            
                                            // Collect mekanik
                                            $mekanik_list = [];
                                            if (!empty($history['mekanik1'])) {
                                                $name = getMechanicNameModal($koneksi, $history['mekanik1']);
                                                $persen = $history['persen_mekanik1'];
                                                $mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                            }
                                            if (!empty($history['mekanik2'])) {
                                                $name = getMechanicNameModal($koneksi, $history['mekanik2']);
                                                $persen = $history['persen_mekanik2'];
                                                $mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                            }
                                            if (!empty($history['mekanik3'])) {
                                                $name = getMechanicNameModal($koneksi, $history['mekanik3']);
                                                $persen = $history['persen_mekanik3'];
                                                $mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                            }
                                            if (!empty($history['mekanik4'])) {
                                                $name = getMechanicNameModal($koneksi, $history['mekanik4']);
                                                $persen = $history['persen_mekanik4'];
                                                $mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                            }
                                            
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
                                            <td class="center"><?php echo $history['tanggal_format']; ?></td>
                                            <td>
                                                <?php 
                                                $pekerjaan = $history['pekerjaan'];
                                                if (empty($pekerjaan)) {
                                                    echo '<span class="text-muted">-</span>';
                                                } else {
                                                    echo '<small>' . htmlspecialchars($pekerjaan) . '</small>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (empty($kepala_mekanik_list)): ?>
                                                    <span class="text-muted">-</span>
                                                <?php else: ?>
                                                    <i class="ace-icon fa fa-user-md blue"></i>
                                                    <?php foreach ($kepala_mekanik_list as $km): ?>
                                                        <div><small><?php echo htmlspecialchars($km); ?></small></div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($mekanik_list)): ?>
                                                    <span class="text-muted">-</span>
                                                <?php else: ?>
                                                    <i class="ace-icon fa fa-wrench green"></i>
                                                    <?php foreach ($mekanik_list as $m): ?>
                                                        <div><small><?php echo htmlspecialchars($m); ?></small></div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="center"><?php echo $status_badge; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
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
