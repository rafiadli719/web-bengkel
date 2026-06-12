<?php
/**
 * Mechanic History Template
 * Displays previous mechanics who worked on this vehicle
 * To be included in Work Order tab of all service input files
 */

// Get vehicle plate number
$vehicle_no_polisi = $no_polisi ?? '';

// Initialize mechanic history array
$mechanic_history = [];

if (!empty($vehicle_no_polisi)) {
    // Query to get service history for this vehicle with mechanics
    $query_history = "SELECT 
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
    
    $result_history = mysqli_query($koneksi, $query_history);
    
    if ($result_history && mysqli_num_rows($result_history) > 0) {
        while ($row = mysqli_fetch_array($result_history)) {
            $mechanic_history[] = $row;
        }
    }
}

// Function to get mechanic name from code
function getMechanicName($koneksi, $mechanic_code) {
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

<!-- Mechanic History Section -->
<div class="row" style="margin-top: 20px;">
    <div class="col-xs-12">
        <div class="widget-box">
            <div class="widget-header widget-header-flat widget-header-small">
                <h5 class="widget-title">
                    <i class="ace-icon fa fa-history blue"></i>
                    Riwayat Mekanik Kendaraan (<?php echo htmlspecialchars($vehicle_no_polisi); ?>)
                </h5>
                <div class="widget-toolbar">
                    <span class="badge badge-info"><?php echo count($mechanic_history); ?> Service Sebelumnya</span>
                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main no-padding">
                    <?php if (empty($mechanic_history)): ?>
                        <div class="alert alert-info" style="margin: 15px;">
                            <i class="ace-icon fa fa-info-circle"></i>
                            Belum ada riwayat service dengan mekanik untuk kendaraan ini.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th class="center" style="width: 40px;">No</th>
                                        <th>No. Service</th>
                                        <th>Tanggal</th>
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
                                            $name = getMechanicName($koneksi, $history['kepala_mekanik1']);
                                            $persen = $history['persen_kepala_mekanik1'];
                                            $kepala_mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                        }
                                        if (!empty($history['kepala_mekanik2'])) {
                                            $name = getMechanicName($koneksi, $history['kepala_mekanik2']);
                                            $persen = $history['persen_kepala_mekanik2'];
                                            $kepala_mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                        }
                                        
                                        // Collect mekanik
                                        $mekanik_list = [];
                                        if (!empty($history['mekanik1'])) {
                                            $name = getMechanicName($koneksi, $history['mekanik1']);
                                            $persen = $history['persen_mekanik1'];
                                            $mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                        }
                                        if (!empty($history['mekanik2'])) {
                                            $name = getMechanicName($koneksi, $history['mekanik2']);
                                            $persen = $history['persen_mekanik2'];
                                            $mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                        }
                                        if (!empty($history['mekanik3'])) {
                                            $name = getMechanicName($koneksi, $history['mekanik3']);
                                            $persen = $history['persen_mekanik3'];
                                            $mekanik_list[] = $name . ($persen > 0 ? " ({$persen}%)" : '');
                                        }
                                        if (!empty($history['mekanik4'])) {
                                            $name = getMechanicName($koneksi, $history['mekanik4']);
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
                                        <td><?php echo $history['tanggal_format']; ?></td>
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
                        
                        <div class="alert alert-warning" style="margin: 15px;">
                            <i class="ace-icon fa fa-lightbulb-o"></i>
                            <strong>Tips:</strong> Riwayat ini menampilkan 10 service terakhir yang sudah selesai/lunas dengan mekanik yang ditugaskan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
