<?php
session_start();
include "../config/koneksi.php";

if (empty($_SESSION['_iduser'])) {
    echo '<div class="alert alert-danger">Session expired</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<div class="alert alert-danger">Metode request tidak valid</div>';
    exit;
}

$keluhan_id = isset($_POST['keluhan_id']) ? (int) $_POST['keluhan_id'] : 0;
if ($keluhan_id <= 0) {
    echo '<div class="alert alert-danger">Keluhan ID tidak ditemukan</div>';
    exit;
}

try {
    $stmtKeluhan = mysqli_prepare(
        $koneksi,
        "SELECT k.*, mk.kode_keluhan, mk.nama_keluhan
         FROM tbservis_keluhan_status k
         LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
         WHERE k.id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmtKeluhan, "i", $keluhan_id);
    mysqli_stmt_execute($stmtKeluhan);
    $keluhanResult = mysqli_stmt_get_result($stmtKeluhan);
    $keluhan_data = $keluhanResult ? mysqli_fetch_assoc($keluhanResult) : null;
    mysqli_stmt_close($stmtKeluhan);

    if (!$keluhan_data) {
        echo '<div class="alert alert-danger">Data keluhan tidak ditemukan</div>';
        exit;
    }

    if (!empty($keluhan_data['kode_keluhan'])) {
        $kode_keluhan = $keluhan_data['kode_keluhan'];
        $stmtProses = mysqli_prepare(
            $koneksi,
            "SELECT kp.*,
                    COALESCE(kt.status_proses, 'pending') as current_status,
                    kt.mekanik_id, kt.waktu_mulai, kt.waktu_selesai, kt.catatan, kt.biaya_actual
             FROM tbkeluhan_proses kp
             LEFT JOIN tbservis_keluhan_tracking kt ON kp.id = kt.proses_id AND kt.keluhan_id = ?
             WHERE kp.kode_keluhan = ? AND kp.status_aktif = '1'
             ORDER BY kp.urutan ASC, kp.nama_proses ASC"
        );
        mysqli_stmt_bind_param($stmtProses, "is", $keluhan_id, $kode_keluhan);
        mysqli_stmt_execute($stmtProses);
        $sql_proses = mysqli_stmt_get_result($stmtProses);

        echo '<div class="row"><div class="col-md-12">';
        echo '<h5><strong>Keluhan:</strong> ' . htmlspecialchars($keluhan_data['keluhan']) . '</h5>';
        echo '<p><strong>Master:</strong> ' . htmlspecialchars($keluhan_data['nama_keluhan']) . ' (' . htmlspecialchars($keluhan_data['kode_keluhan']) . ')</p>';
        echo '</div></div>';

        echo '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th width="5%">No</th><th width="10%">Tipe</th><th width="25%">Proses</th><th width="15%">Status</th><th width="15%">Mekanik</th><th width="15%">Waktu</th><th width="15%">Catatan</th></tr></thead><tbody>';

        $mekanikOptions = [];
        $mekanikQuery = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama <> '-' ORDER BY nama ASC");
        if ($mekanikQuery) {
            while ($mekanik = mysqli_fetch_assoc($mekanikQuery)) {
                $mekanikOptions[] = $mekanik;
            }
        }

        $no = 1;
        while ($proses = mysqli_fetch_assoc($sql_proses)) {
            echo '<tr>';
            echo '<td class="center">' . $no++ . '</td>';
            echo '<td><span class="label label-' . ($proses['tipe_proses'] === 'jasa' ? 'primary' : ($proses['tipe_proses'] === 'barang' ? 'success' : 'warning')) . '">' . htmlspecialchars(strtoupper($proses['tipe_proses'])) . '</span></td>';
            echo '<td><strong>' . htmlspecialchars($proses['nama_proses']) . '</strong>';
            if ($proses['wajib'] == '1') {
                echo ' <i class="fa fa-asterisk text-danger" title="Wajib"></i>';
            }
            if (!empty($proses['deskripsi'])) {
                echo '<br><small class="text-muted">' . htmlspecialchars($proses['deskripsi']) . '</small>';
            }
            echo '</td>';
            echo '<td><select class="form-control input-sm proses-status" data-proses-id="' . (int) $proses['id'] . '">';
            foreach (['pending' => 'Pending', 'dikerjakan' => 'Dikerjakan', 'selesai' => 'Selesai', 'skip' => 'Skip'] as $value => $label) {
                $selected = $proses['current_status'] === $value ? ' selected' : '';
                echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
            }
            echo '</select></td>';
            echo '<td><select class="form-control input-sm proses-mekanik" data-proses-id="' . (int) $proses['id'] . '">';
            echo '<option value="">- Pilih Mekanik -</option>';
            foreach ($mekanikOptions as $mekanik) {
                $selected = ($proses['mekanik_id'] == $mekanik['nomekanik']) ? ' selected' : '';
                echo '<option value="' . htmlspecialchars($mekanik['nomekanik']) . '"' . $selected . '>' . htmlspecialchars($mekanik['nama']) . '</option>';
            }
            echo '</select></td>';
            echo '<td>';
            if (!empty($proses['waktu_mulai'])) {
                echo '<small>Mulai: ' . htmlspecialchars(date('H:i', strtotime($proses['waktu_mulai']))) . '</small><br>';
            }
            if (!empty($proses['waktu_selesai'])) {
                echo '<small>Selesai: ' . htmlspecialchars(date('H:i', strtotime($proses['waktu_selesai']))) . '</small>';
            }
            echo '</td>';
            echo '<td><textarea class="form-control input-sm proses-catatan" rows="2" data-proses-id="' . (int) $proses['id'] . '" placeholder="Catatan...">' . htmlspecialchars($proses['catatan'] ?? '') . '</textarea></td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
        echo '<input type="hidden" id="current-keluhan-id" value="' . $keluhan_id . '">';
        mysqli_stmt_close($stmtProses);
    } else {
        echo '<div class="alert alert-info">';
        echo '<h5><strong>Keluhan:</strong> ' . htmlspecialchars($keluhan_data['keluhan']) . '</h5>';
        echo '<p>Keluhan ini tidak memiliki master proses. Anda bisa menambahkan proses manual jika diperlukan.</p>';
        echo '</div>';
    }
} catch (Throwable $e) {
    echo '<div class="alert alert-danger">Terjadi kesalahan saat memuat detail proses</div>';
}
?>

<script>
$(document).ready(function() {
    $('.proses-status, .proses-mekanik').on('change', function() {
        var prosesId = $(this).data('proses-id');
        autoSaveProses(prosesId);
    });

    $('.proses-catatan').on('blur', function() {
        var prosesId = $(this).data('proses-id');
        autoSaveProses(prosesId);
    });
});

function autoSaveProses(prosesId) {
    var keluhanId = $('#current-keluhan-id').val();
    var status = $('.proses-status[data-proses-id="' + prosesId + '"]').val();
    var mekanikId = $('.proses-mekanik[data-proses-id="' + prosesId + '"]').val();
    var catatan = $('.proses-catatan[data-proses-id="' + prosesId + '"]').val();

    $.ajax({
        url: 'ajax-save-proses-tracking.php',
        method: 'POST',
        dataType: 'json',
        data: {
            keluhan_id: keluhanId,
            proses_id: prosesId,
            status_proses: status,
            mekanik_id: mekanikId,
            catatan: catatan
        }
    });
}
</script>
