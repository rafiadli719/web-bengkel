<?php
// Ambil data progress mekanik jika ada
$progress_mekanik = array();
if(!empty($no_service)) {
    $query_progress = "SELECT * FROM tb_progress_mekanik WHERE no_service = '$no_service' ORDER BY jenis_mekanik, nama_mekanik";
    $result_progress = mysqli_query($koneksi, $query_progress);
    while($row = mysqli_fetch_array($result_progress)) {
        $progress_mekanik[] = $row;
    }
}

// Ambil data mekanik dari database (sesuaikan dengan struktur database yang ada)
$query_mekanik = "SELECT DISTINCT nama_user FROM tbuser WHERE user_akses IN ('mekanik', 'kepala_mekanik', 'admin') ORDER BY nama_user";
$result_mekanik = mysqli_query($koneksi, $query_mekanik);
$daftar_mekanik = array();
while($row = mysqli_fetch_array($result_mekanik)) {
    $daftar_mekanik[] = $row['nama_user'];
}
?>

<div class="widget-box">
    <div class="widget-header">
        <h4 class="header green">
            <i class="fa fa-users"></i> Progress Pengerjaan Mekanik
        </h4>
    </div>
    <div class="widget-body">
        <div class="widget-main">
            <!-- Form untuk menambah/update progress mekanik -->
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-xs-12">
                    <form id="formProgressMekanik" class="form-horizontal">
                        <input type="hidden" name="no_service" value="<?php echo $no_service; ?>">
                        <input type="hidden" name="no_antrian" value="<?php echo $no_antrian ?? ''; ?>">
                        
                        <div class="row">
                            <div class="col-xs-12 col-sm-3">
                                <label>Mekanik:</label>
                                <select name="mekanik_id" id="cbomekanik" class="form-control input-sm" required>
                                    <option value="">Pilih Mekanik</option>
                                    <?php foreach($daftar_mekanik as $mekanik): ?>
                                        <option value="<?php echo $mekanik; ?>"><?php echo $mekanik; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-xs-12 col-sm-3">
                                <label>Jenis:</label>
                                <select name="jenis_mekanik" id="cbojenis_mekanik" class="form-control input-sm" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="kepala_mekanik">Kepala Mekanik</option>
                                    <option value="mekanik">Mekanik</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            
                            <div class="col-xs-12 col-sm-2">
                                <label>Progress (%):</label>
                                <input type="number" name="persen_kerja" id="txtpersen_kerja" 
                                       class="form-control input-sm" min="0" max="100" value="0" required>
                            </div>
                            
                            <div class="col-xs-12 col-sm-2">
                                <label>Status:</label>
                                <select name="status_kerja" id="cbostatus_kerja" class="form-control input-sm" required>
                                    <option value="belum_mulai">Belum Mulai</option>
                                    <option value="sedang_bekerja">Sedang Bekerja</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="batal">Batal</option>
                                </select>
                            </div>
                            
                            <div class="col-xs-12 col-sm-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-success btn-sm btn-block">
                                    <i class="fa fa-save"></i> Simpan
                                </button>
                            </div>
                        </div>
                        
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-xs-12 col-sm-6">
                                <label>Jam Mulai:</label>
                                <input type="time" name="jam_mulai" id="txtjam_mulai" class="form-control input-sm">
                            </div>
                            
                            <div class="col-xs-12 col-sm-6">
                                <label>Jam Selesai:</label>
                                <input type="time" name="jam_selesai" id="txtjam_selesai" class="form-control input-sm">
                            </div>
                        </div>
                        
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-xs-12">
                                <label>Catatan Kerja:</label>
                                <textarea name="catatan_kerja" id="txtcatatan_kerja" 
                                          class="form-control input-sm" rows="2" 
                                          placeholder="Catatan pengerjaan..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabel Progress Mekanik -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Mekanik</th>
                            <th>Jenis</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyProgressMekanik">
                        <?php if(count($progress_mekanik) > 0): ?>
                            <?php foreach($progress_mekanik as $index => $progress): ?>
                                <tr id="row_progress_<?php echo $progress['id']; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $progress['nama_mekanik']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $progress['jenis_mekanik'] == 'kepala_mekanik' ? 'primary' : ($progress['jenis_mekanik'] == 'mekanik' ? 'info' : 'success'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $progress['jenis_mekanik'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="margin-bottom: 0;">
                                            <div class="progress-bar progress-bar-success" role="progressbar" 
                                                 style="width: <?php echo $progress['persen_kerja']; ?>%">
                                                <?php echo $progress['persen_kerja']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($progress['status_kerja']) {
                                            case 'belum_mulai':
                                                $status_class = 'default';
                                                $status_text = 'Belum Mulai';
                                                break;
                                            case 'sedang_bekerja':
                                                $status_class = 'info';
                                                $status_text = 'Sedang Bekerja';
                                                break;
                                            case 'selesai':
                                                $status_class = 'success';
                                                $status_text = 'Selesai';
                                                break;
                                            case 'batal':
                                                $status_class = 'danger';
                                                $status_text = 'Batal';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $progress['jam_mulai'] ?: '-'; ?></td>
                                    <td><?php echo $progress['jam_selesai'] ?: '-'; ?></td>
                                    <td><?php echo $progress['catatan_kerja'] ?: '-'; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning" 
                                                onclick="editProgress(<?php echo $progress['id']; ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-danger" 
                                                onclick="deleteProgress(<?php echo $progress['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    Belum ada data progress mekanik
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission
    $('#formProgressMekanik').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var mekanik_id = $('#cbomekanik').val();
        var nama_mekanik = $('#cbomekanik option:selected').text();
        
        // Tambahkan nama mekanik ke formData
        formData += '&nama_mekanik=' + encodeURIComponent(nama_mekanik);
        
        $.ajax({
            url: '_ajax/ajax-update-progress-mekanik.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Progress mekanik berhasil disimpan');
                    location.reload(); // Reload halaman untuk update data
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menyimpan progress mekanik');
            }
        });
    });
    
    // Auto-fill jam saat status berubah
    $('#cbostatus_kerja').change(function() {
        var status = $(this).val();
        var now = new Date();
        var timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        
        if(status == 'sedang_bekerja') {
            $('#txtjam_mulai').val(timeString);
        } else if(status == 'selesai') {
            $('#txtjam_selesai').val(timeString);
        }
    });
});

// Fungsi untuk edit progress
function editProgress(id) {
    // Implementasi edit progress
    alert('Fitur edit progress akan segera tersedia');
}

// Fungsi untuk delete progress
function deleteProgress(id) {
    if(confirm('Apakah Anda yakin ingin menghapus progress ini?')) {
        // Implementasi delete progress
        alert('Fitur delete progress akan segera tersedia');
    }
}
</script>
