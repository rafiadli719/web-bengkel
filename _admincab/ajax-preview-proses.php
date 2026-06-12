<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    echo '<div class="alert alert-danger">Session expired</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<div class="alert alert-danger">Metode request tidak valid</div>';
    exit;
}

include "../config/koneksi.php";

$kode_keluhan = isset($_POST['kode_keluhan']) ? trim($_POST['kode_keluhan']) : '';
if ($kode_keluhan === '') {
    echo '<div class="alert alert-danger">Parameter tidak lengkap</div>';
    exit;
}

$stmtKeluhan = mysqli_prepare($koneksi, "SELECT * FROM tbmaster_keluhan WHERE kode_keluhan = ? LIMIT 1");
mysqli_stmt_bind_param($stmtKeluhan, "s", $kode_keluhan);
mysqli_stmt_execute($stmtKeluhan);
$keluhanResult = mysqli_stmt_get_result($stmtKeluhan);
$keluhan = $keluhanResult ? mysqli_fetch_assoc($keluhanResult) : null;
mysqli_stmt_close($stmtKeluhan);

if (!$keluhan) {
    echo '<div class="alert alert-danger">Keluhan tidak ditemukan</div>';
    exit;
}

$stmtProses = mysqli_prepare(
    $koneksi,
    "SELECT * FROM tbkeluhan_proses WHERE kode_keluhan = ? AND status_aktif = '1' ORDER BY urutan ASC"
);
mysqli_stmt_bind_param($stmtProses, "s", $kode_keluhan);
mysqli_stmt_execute($stmtProses);
$sql_proses = mysqli_stmt_get_result($stmtProses);

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';
echo '<h4><i class="fa fa-info-circle"></i> ' . htmlspecialchars($keluhan['nama_keluhan']) . '</h4>';
echo '<small>' . htmlspecialchars($keluhan['deskripsi'] ?? '') . '</small>';
echo '</div>';
echo '<div class="panel-body">';

if ($sql_proses && mysqli_num_rows($sql_proses) > 0) {
    echo '<h5><i class="fa fa-cogs"></i> Proses Pengerjaan:</h5>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-condensed">';
    echo '<thead><tr class="info"><th width="5%">No</th><th width="35%">Nama Proses</th><th width="20%">Admin/Kasir</th><th width="15%">Estimasi</th><th width="10%">Wajib</th><th width="15%">Status</th></tr></thead>';
    echo '<tbody>';

    $no = 1;
    while ($proses = mysqli_fetch_assoc($sql_proses)) {
        $wajib_class = $proses['wajib'] == '1' ? 'text-danger' : 'text-muted';
        $wajib_icon = $proses['wajib'] == '1' ? 'fa-exclamation-triangle' : 'fa-circle-o';
        $wajib_text = $proses['wajib'] == '1' ? 'Wajib' : 'Opsional';

        $status_class = 'default';
        $status_text = 'Belum Mulai';
        $status_icon = 'fa-circle-o';
        $status_proses = $proses['status_proses'] ?? '';
        if ($status_proses === 'progress') {
            $status_class = 'warning';
            $status_text = 'Sedang Proses';
            $status_icon = 'fa-cogs';
        } elseif ($status_proses === 'completed') {
            $status_class = 'success';
            $status_text = 'Selesai';
            $status_icon = 'fa-check';
        } elseif ($status_proses === 'pending') {
            $status_class = 'info';
            $status_text = 'Menunggu';
            $status_icon = 'fa-clock-o';
        }

        echo '<tr>';
        echo '<td>' . $no . '</td>';
        echo '<td><strong>' . htmlspecialchars($proses['nama_proses']) . '</strong><br><small class="text-muted">' . htmlspecialchars($proses['deskripsi_proses'] ?? '') . '</small></td>';
        echo '<td>';
        if (!empty($proses['admin_name'])) {
            echo '<span class="label label-success"><i class="fa fa-user"></i> ' . htmlspecialchars($proses['admin_name']) . '</span>';
        } else {
            echo '<span class="label label-warning"><i class="fa fa-user-plus"></i> Belum Assign</span>';
        }
        echo '</td>';
        echo '<td><small>' . (int) $proses['estimasi_waktu'] . ' menit</small></td>';
        echo '<td><span class="' . $wajib_class . '"><i class="fa ' . $wajib_icon . '"></i> ' . $wajib_text . '</span></td>';
        echo '<td><span class="label label-' . $status_class . '"><i class="fa ' . $status_icon . '"></i> ' . $status_text . '</span></td>';
        echo '</tr>';
        $no++;
    }
    echo '</tbody></table></div>';

    mysqli_data_seek($sql_proses, 0);
    $total_proses = mysqli_num_rows($sql_proses);
    $stmtWajib = mysqli_prepare($koneksi, "SELECT COUNT(*) as total_wajib FROM tbkeluhan_proses WHERE kode_keluhan = ? AND wajib = '1' AND status_aktif = '1'");
    mysqli_stmt_bind_param($stmtWajib, "s", $kode_keluhan);
    mysqli_stmt_execute($stmtWajib);
    $wajibResult = mysqli_stmt_get_result($stmtWajib);
    $wajibRow = $wajibResult ? mysqli_fetch_assoc($wajibResult) : ['total_wajib' => 0];
    mysqli_stmt_close($stmtWajib);

    echo '<div class="row">';
    echo '<div class="col-md-6"><div class="alert alert-info"><i class="fa fa-info-circle"></i> ';
    echo '<strong>Total Proses:</strong> ' . $total_proses . ' langkah<br>';
    echo '<strong>Proses Wajib:</strong> ' . (int) $wajibRow['total_wajib'] . ' langkah<br>';
    echo '<strong>Estimasi Total:</strong> ' . (int) ($keluhan['estimasi_waktu'] ?? 0) . ' menit';
    echo '</div></div>';
    echo '<div class="col-md-6"><div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> <strong>Catatan:</strong><br>Proses wajib harus diselesaikan sebelum service dianggap selesai.</div></div>';
    echo '</div>';
} else {
    echo '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Keluhan ini tidak memiliki proses pengerjaan khusus. Admin/Kasir dapat menangani secara manual sesuai kebutuhan.</div>';
}

mysqli_stmt_close($stmtProses);
echo '</div></div>';
?>
