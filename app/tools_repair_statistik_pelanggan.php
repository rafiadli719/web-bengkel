<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "_include_statistik_pelanggan.php";

set_time_limit(0);

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'] ?? '';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$scope = $_POST['scope'] ?? 'current';
$mode = $_POST['mode'] ?? 'preview';
$include_cancel = isset($_POST['include_cancel']) ? true : false;

$results = null;
$error_message = '';

if (isset($_POST['run_repair'])) {
    $scope = ($scope === 'all') ? 'all' : 'current';
    $mode = ($mode === 'execute') ? 'execute' : 'preview';

    if (!isset($koneksi) || !$koneksi) {
        $error_message = 'Koneksi database tidak tersedia.';
    } else {
        $where = "status_servis='bayar' AND (total_akhir IS NULL OR total_akhir=0) AND total_grand>0";
        if ($scope === 'current' && $kd_cabang !== '') {
            $where .= " AND kd_cabang='" . mysqli_real_escape_string($koneksi, $kd_cabang) . "'";
        }

        $q_count = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_grand),0) AS sum_grand FROM tblservice WHERE $where");
        $row_count = $q_count ? mysqli_fetch_assoc($q_count) : null;
        $need_fix_count = (int)($row_count['cnt'] ?? 0);
        $need_fix_sum_grand = (float)($row_count['sum_grand'] ?? 0);

        $backfill_affected = 0;
        if ($mode === 'execute' && $need_fix_count > 0) {
            $upd = mysqli_query($koneksi, "UPDATE tblservice SET total_akhir = total_grand WHERE $where");
            if ($upd) {
                $backfill_affected = (int)mysqli_affected_rows($koneksi);
            } else {
                $error_message = 'Gagal update total_akhir: ' . mysqli_error($koneksi);
            }
        }

        $customer_query = "SELECT DISTINCT no_pelanggan FROM tblservice WHERE status_servis='bayar' AND no_pelanggan IS NOT NULL AND no_pelanggan<>''";
        if ($scope === 'current' && $kd_cabang !== '') {
            $customer_query .= " AND kd_cabang='" . mysqli_real_escape_string($koneksi, $kd_cabang) . "'";
        }
        $customer_query .= " ORDER BY no_pelanggan";

        $customers = [];
        $rs_customers = mysqli_query($koneksi, $customer_query);
        if ($rs_customers) {
            while ($r = mysqli_fetch_assoc($rs_customers)) {
                $customers[] = $r['no_pelanggan'];
            }
        } else {
            if ($error_message === '') {
                $error_message = 'Gagal ambil daftar pelanggan: ' . mysqli_error($koneksi);
            }
        }

        $rebuild_total = count($customers);
        $rebuild_ok = 0;
        $rebuild_fail = 0;
        $cancel_ok = 0;
        $cancel_fail = 0;

        if ($mode === 'execute' && $error_message === '' && $rebuild_total > 0) {
            foreach ($customers as $no_pelanggan) {
                $ok = updateStatistikPelangganAfterPayment($koneksi, $no_pelanggan, '');
                if ($ok) {
                    $rebuild_ok++;
                } else {
                    $rebuild_fail++;
                }

                if ($include_cancel) {
                    $ok_cancel = updateCancelStatistikPelanggan($koneksi, $no_pelanggan);
                    if ($ok_cancel) {
                        $cancel_ok++;
                    } else {
                        $cancel_fail++;
                    }
                }
            }
        }

        $results = [
            'scope' => $scope,
            'mode' => $mode,
            'need_fix_count' => $need_fix_count,
            'need_fix_sum_grand' => $need_fix_sum_grand,
            'backfill_affected' => $backfill_affected,
            'customers_total' => $rebuild_total,
            'rebuild_ok' => $rebuild_ok,
            'rebuild_fail' => $rebuild_fail,
            'include_cancel' => $include_cancel,
            'cancel_ok' => $cancel_ok,
            'cancel_fail' => $cancel_fail,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Tools - Repair Statistik Pelanggan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
</head>
<body style="padding: 20px;">
<div class="container-fluid">
    <div class="row">
        <div class="col-xs-12">
            <h3 style="margin-top:0;">Repair Statistik Pelanggan</h3>

            <?php if ($error_message !== ''): ?>
                <div class="alert alert-danger">
                    <?php echo h($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (is_array($results)): ?>
                <div class="alert alert-info">
                    <div><strong>Scope:</strong> <?php echo h($results['scope']); ?></div>
                    <div><strong>Mode:</strong> <?php echo h($results['mode']); ?></div>
                </div>

                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td width="40%"><strong>Service bayar yang butuh backfill total_akhir</strong></td>
                            <td><?php echo number_format((int)$results['need_fix_count'], 0, ',', '.'); ?> transaksi</td>
                        </tr>
                        <tr>
                            <td><strong>Total nominal (dari total_grand) yang akan dipindahkan</strong></td>
                            <td>Rp <?php echo number_format((float)$results['need_fix_sum_grand'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Rows ter-update (execute mode)</strong></td>
                            <td><?php echo number_format((int)$results['backfill_affected'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total pelanggan yang akan direbuild</strong></td>
                            <td><?php echo number_format((int)$results['customers_total'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Rebuild sukses</strong></td>
                            <td><?php echo number_format((int)$results['rebuild_ok'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Rebuild gagal</strong></td>
                            <td><?php echo number_format((int)$results['rebuild_fail'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Update cancel stats</strong></td>
                            <td><?php echo $results['include_cancel'] ? 'Ya' : 'Tidak'; ?></td>
                        </tr>
                        <?php if ($results['include_cancel']): ?>
                        <tr>
                            <td><strong>Cancel update sukses</strong></td>
                            <td><?php echo number_format((int)$results['cancel_ok'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Cancel update gagal</strong></td>
                            <td><?php echo number_format((int)$results['cancel_fail'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="panel panel-default">
                <div class="panel-heading"><strong>Jalankan Repair</strong></div>
                <div class="panel-body">
                    <form method="post">
                        <div class="form-group">
                            <label>Scope</label>
                            <select class="form-control" name="scope">
                                <option value="current" <?php echo ($scope === 'current') ? 'selected' : ''; ?>>Cabang saat ini (<?php echo h($kd_cabang); ?>)</option>
                                <option value="all" <?php echo ($scope === 'all') ? 'selected' : ''; ?>>Semua cabang</option>
                            </select>
                            <small class="text-muted">Catatan: Rebuild statistik pelanggan tetap menghitung total bayar dari semua cabang (berdasarkan no_pelanggan).</small>
                        </div>

                        <div class="form-group">
                            <label>Mode</label>
                            <select class="form-control" name="mode">
                                <option value="preview" <?php echo ($mode === 'preview') ? 'selected' : ''; ?>>Preview (tidak mengubah data)</option>
                                <option value="execute" <?php echo ($mode === 'execute') ? 'selected' : ''; ?>>Execute (jalankan update)</option>
                            </select>
                        </div>

                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="include_cancel" value="1" <?php echo $include_cancel ? 'checked' : ''; ?> />
                                Update juga statistik cancel (jumlah_cancel, cancel_rate, dll)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" name="run_repair" value="1">Jalankan</button>
                        <a class="btn btn-default" href="tools_repair_statistik_pelanggan.php">Reset</a>
                    </form>
                </div>
            </div>

            <div class="alert alert-warning">
                Pastikan backup database sebelum menjalankan mode Execute.
            </div>
        </div>
    </div>
</div>
</body>
</html>
