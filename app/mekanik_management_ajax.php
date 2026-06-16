<?php
session_start();
include "../config/koneksi.php";

// Check if user is logged in and has permission
if(empty($_SESSION['_iduser'])) {
    exit('Unauthorized');
}

$lvl_akses = $_SESSION['_lvl_akses'] ?? 0;
if($lvl_akses != 1 && $lvl_akses != 7 && $lvl_akses != 10) {
    exit('Unauthorized');
}

if(isset($_POST['action'])) {

    if($_POST['action'] == 'get_mekanik') {
        $mekanik_code = mysqli_real_escape_string($koneksi, $_POST['mekanik_code']);

        $query = "SELECT * FROM tblmekanik WHERE nomekanik = '$mekanik_code'";
        $result = mysqli_query($koneksi, $query);
        $mekanik = mysqli_fetch_assoc($result);

        if($mekanik) {
            ?>
            <input type="hidden" name="nomekanik" value="<?php echo $mekanik['nomekanik']; ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Kode Mekanik</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($mekanik['nomekanik']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" value="<?php echo htmlspecialchars($mekanik['nama']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3"><?php echo htmlspecialchars($mekanik['alamat'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" class="form-control" name="telp" value="<?php echo htmlspecialchars($mekanik['telp'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($mekanik['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Keahlian <span class="text-danger">*</span></label>
                        <select class="form-control" name="keahlian" required>
                            <option value="">- Pilih Keahlian -</option>
                            <option value="1" <?php echo $mekanik['keahlian'] == '1' ? 'selected' : ''; ?>>Kepala Mekanik</option>
                            <option value="2" <?php echo $mekanik['keahlian'] == '2' ? 'selected' : ''; ?>>Mekanik Senior</option>
                            <option value="3" <?php echo $mekanik['keahlian'] == '3' ? 'selected' : ''; ?>>Mekanik Junior</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="aktif" <?php echo $mekanik['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo $mekanik['status'] == 'nonaktif' ? 'selected' : ''; ?>>Non-Aktif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Masuk</label>
                        <input type="date" class="form-control" name="tanggal_masuk" value="<?php echo $mekanik['tanggal_masuk'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Gaji Pokok</label>
                        <input type="text" class="form-control money-edit" name="gaji_pokok" value="<?php echo number_format($mekanik['gaji_pokok'] ?? 0, 0, ',', '.'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Spesialisasi</label>
                        <textarea class="form-control" name="spesialisasi" rows="2" placeholder="Contoh: Mesin, Kelistrikan, Body"><?php echo htmlspecialchars($mekanik['spesialisasi'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Sertifikat</label>
                        <textarea class="form-control" name="sertifikat" rows="2" placeholder="Sertifikat yang dimiliki"><?php echo htmlspecialchars($mekanik['sertifikat'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <script>
            // Initialize money mask for edit form
            $('.money-edit').mask('#.##0', {reverse: true});
            </script>
            <?php
        } else {
            echo '<div class="alert alert-danger">Mekanik not found!</div>';
        }
    }

    elseif($_POST['action'] == 'view_detail') {
        $mekanik_code = mysqli_real_escape_string($koneksi, $_POST['mekanik_code']);

        $query = "SELECT m.*,
                  CASE m.keahlian
                     WHEN '1' THEN 'Kepala Mekanik'
                     WHEN '2' THEN 'Mekanik Senior'
                     WHEN '3' THEN 'Mekanik Junior'
                     ELSE 'Tidak Ditentukan'
                  END as keahlian_text,
                  u.nama_user as username,
                  u.is_active as user_status,
                  u.user_akses,
                  u.last_login
                  FROM tblmekanik m
                  LEFT JOIN tb_user_mekanik_mapping umm ON m.nomekanik = umm.mekanik_code
                  LEFT JOIN tbuser u ON umm.user_id = u.id
                  WHERE m.nomekanik = '$mekanik_code'";
        $result = mysqli_query($koneksi, $query);
        $mekanik = mysqli_fetch_assoc($result);

        if($mekanik) {
            ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="widget-box widget-color-blue2">
                        <div class="widget-header">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-user"></i>
                                Informasi Pribadi
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="40%"><strong>Kode Mekanik:</strong></td>
                                        <td><?php echo htmlspecialchars($mekanik['nomekanik']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nama Lengkap:</strong></td>
                                        <td><?php echo htmlspecialchars($mekanik['nama']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Alamat:</strong></td>
                                        <td><?php echo htmlspecialchars($mekanik['alamat'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Telepon:</strong></td>
                                        <td><?php echo htmlspecialchars($mekanik['telp'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($mekanik['email'] ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Masuk:</strong></td>
                                        <td>
                                            <?php
                                            if($mekanik['tanggal_masuk']) {
                                                echo date('d/m/Y', strtotime($mekanik['tanggal_masuk']));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="widget-box widget-color-green2">
                        <div class="widget-header">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-cog"></i>
                                Informasi Kerja
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="40%"><strong>Keahlian:</strong></td>
                                        <td>
                                            <span class="label label-<?php
                                                echo $mekanik['keahlian'] == '1' ? 'danger' :
                                                    ($mekanik['keahlian'] == '2' ? 'warning' : 'info');
                                            ?>">
                                                <?php echo $mekanik['keahlian_text']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="label label-<?php echo $mekanik['status'] == 'aktif' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($mekanik['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Gaji Pokok:</strong></td>
                                        <td>
                                            <?php
                                            if($mekanik['gaji_pokok']) {
                                                echo 'Rp ' . number_format($mekanik['gaji_pokok'], 0, ',', '.');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>User Account:</strong></td>
                                        <td>
                                            <?php if($mekanik['username']): ?>
                                            <span class="label label-success">
                                                <i class="fa fa-user"></i> <?php echo $mekanik['username']; ?>
                                            </span>
                                            <?php if($mekanik['user_status'] == 'inactive'): ?>
                                            <br><small class="text-danger">Account Inactive</small>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <span class="text-muted">No account</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Login:</strong></td>
                                        <td>
                                            <?php
                                            if($mekanik['last_login']) {
                                                echo date('d/m/Y H:i', strtotime($mekanik['last_login']));
                                            } else {
                                                echo '<span class="text-muted">Never</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($mekanik['spesialisasi'] || $mekanik['sertifikat']): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="widget-box widget-color-orange2">
                        <div class="widget-header">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-star"></i>
                                Spesialisasi & Sertifikat
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <div class="row">
                                    <?php if($mekanik['spesialisasi']): ?>
                                    <div class="col-md-6">
                                        <h6><strong>Spesialisasi:</strong></h6>
                                        <p><?php echo nl2br(htmlspecialchars($mekanik['spesialisasi'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if($mekanik['sertifikat']): ?>
                                    <div class="col-md-6">
                                        <h6><strong>Sertifikat:</strong></h6>
                                        <p><?php echo nl2br(htmlspecialchars($mekanik['sertifikat'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Service History Statistics -->
            <?php
            // Get service statistics for this mechanic
            $stats_query = "SELECT
                           COUNT(*) as total_services,
                           SUM(CASE WHEN status_servis = 'selesai' THEN 1 ELSE 0 END) as completed_services,
                           SUM(CASE WHEN status_servis = 'diproses' THEN 1 ELSE 0 END) as ongoing_services
                           FROM tblservice
                           WHERE (mekanik1 = '$mekanik_code' OR mekanik2 = '$mekanik_code' OR
                                  mekanik3 = '$mekanik_code' OR mekanik4 = '$mekanik_code' OR
                                  kepala_mekanik1 = '$mekanik_code' OR kepala_mekanik2 = '$mekanik_code')";
            $stats_result = mysqli_query($koneksi, $stats_query);
            $stats = mysqli_fetch_assoc($stats_result);
            ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="widget-box widget-color-purple2">
                        <div class="widget-header">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-bar-chart"></i>
                                Statistik Service
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="center">
                                            <span class="bigger-200 text-primary"><?php echo $stats['total_services']; ?></span>
                                            <br>
                                            <span class="text-muted">Total Service</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="center">
                                            <span class="bigger-200 text-success"><?php echo $stats['completed_services']; ?></span>
                                            <br>
                                            <span class="text-muted">Selesai</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="center">
                                            <span class="bigger-200 text-warning"><?php echo $stats['ongoing_services']; ?></span>
                                            <br>
                                            <span class="text-muted">Sedang Proses</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            echo '<div class="alert alert-danger">Mekanik not found!</div>';
        }
    }
}
?>