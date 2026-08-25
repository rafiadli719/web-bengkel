<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit();
}

include "../config/koneksi.php";

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

$cari_kd = mysqli_query($koneksi,"SELECT user_akses FROM tbuser WHERE id='$id_user'");
$tm_cari = mysqli_fetch_array($cari_kd);
$lvl_akses = $tm_cari['user_akses'];

$is_admin_pengadaan = ($lvl_akses == 'admin' || $lvl_akses == 'pengadaan');
if (!$is_admin_pengadaan) {
    header('Location: master_tipe_detail.php');
    exit();
}

$page_title = "Edit Tipe Detail Motor";
$error_msg = '';
$success_msg = '';

// Get ID dari parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: master_tipe_detail.php');
    exit();
}

// Get data yang akan diedit
$query = "SELECT td.*,
                 pb.merek as nama_brand,
                 th.nama_tipe as nama_tipe_header
          FROM tbmaster_tipe_detail td
          LEFT JOIN tbpabrik_motor pb ON td.kode_brand = pb.kode_brand AND pb.status = '1'
          LEFT JOIN tbmaster_tipe_header th ON td.id_tipe_header = th.id AND th.status = '1'
          WHERE td.id = '$id' AND td.status = '1'";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Data tipe detail tidak ditemukan!";
    header('Location: master_tipe_detail.php');
    exit();
}

$data = mysqli_fetch_assoc($result);

// Get dropdown data
$brands_query = "SELECT kode_brand, merek FROM tbpabrik_motor WHERE status = '1' ORDER BY merek";
$brands_result = mysqli_query($koneksi, $brands_query);

$tipe_headers_query = "SELECT id, nama_tipe, kode_brand FROM tbmaster_tipe_header WHERE status = '1' ORDER BY nama_tipe";
$tipe_headers_result = mysqli_query($koneksi, $tipe_headers_query);

$jenis_motors_query = "SELECT id, jenis FROM tbjenis_motor WHERE status = '1' ORDER BY jenis";
$jenis_motors_result = mysqli_query($koneksi, $jenis_motors_query);

$kategori_motors_query = "SELECT id, kategori FROM tbkategori_motor WHERE status = '1' ORDER BY kategori";
$kategori_motors_result = mysqli_query($koneksi, $kategori_motors_query);

// Process form submission
if ($_POST) {
    $id_tipe_header = (int)$_POST['id_tipe_header'];
    $nama_detail = mysqli_real_escape_string($koneksi, trim($_POST['nama_detail']));
    $cc = !empty($_POST['cc']) ? (int)$_POST['cc'] : null;
    $id_jenis_motor = !empty($_POST['id_jenis_motor']) ? (int)$_POST['id_jenis_motor'] : null;
    $fitur_pembeda = mysqli_real_escape_string($koneksi, trim($_POST['fitur_pembeda']));
    $ciri_fisik_pembeda = mysqli_real_escape_string($koneksi, trim($_POST['ciri_fisik_pembeda']));
    $tahun_awal = !empty($_POST['tahun_awal']) ? (int)$_POST['tahun_awal'] : null;
    $tahun_akhir = mysqli_real_escape_string($koneksi, trim($_POST['tahun_akhir']));
    $no_seri_mesin = mysqli_real_escape_string($koneksi, trim($_POST['no_seri_mesin']));
    $id_kategori_motor = !empty($_POST['id_kategori_motor']) ? (int)$_POST['id_kategori_motor'] : null;

    // Validasi input
    if ($id_tipe_header <= 0) {
        $error_msg = "Tipe Header harus dipilih!";
    } else {
        // Get kode_brand from tipe_header
        $brand_query = "SELECT kode_brand, nama_tipe FROM tbmaster_tipe_header WHERE id = '$id_tipe_header' AND status = '1'";
        $brand_result = mysqli_query($koneksi, $brand_query);

        if (mysqli_num_rows($brand_result) == 0) {
            $error_msg = "Tipe Header tidak valid!";
        } else {
            $brand_data = mysqli_fetch_assoc($brand_result);
            $kode_brand = $brand_data['kode_brand'];
            $nama_tipe_header = $brand_data['nama_tipe'];

            // Validasi nama_detail tidak mengulang tipe header
            if (!empty($nama_detail) && $nama_detail != '-') {
                $detail_words = explode(' ', strtoupper($nama_detail));
                $header_words = explode(' ', strtoupper($nama_tipe_header));

                $duplicate_words = array_intersect($detail_words, $header_words);
                if (!empty($duplicate_words)) {
                    $error_msg = "Nama detail tidak boleh mengulang kata yang ada di tipe header: " . implode(', ', $duplicate_words);
                }
            }

            // Validasi tahun
            if (!empty($tahun_awal) && ($tahun_awal < 1900 || $tahun_awal > date('Y'))) {
                $error_msg = "Tahun awal harus antara 1900 sampai " . date('Y');
            }

            if (!empty($tahun_akhir) && $tahun_akhir != 'SEKARANG' && (!is_numeric($tahun_akhir) || $tahun_akhir < 1900)) {
                $error_msg = "Tahun akhir harus berupa angka atau kata 'SEKARANG'";
            }

            // Validasi no_seri_mesin tanpa spasi
            if (!empty($no_seri_mesin) && strpos($no_seri_mesin, ' ') !== false) {
                $error_msg = "Nomor seri mesin tidak boleh mengandung spasi!";
            }

            if (empty($error_msg)) {
                // Prepare values
                $nama_detail = empty($nama_detail) ? '-' : $nama_detail;
                $fitur_pembeda = empty($fitur_pembeda) ? '-' : $fitur_pembeda;
                $cc_val = $cc ? "'$cc'" : 'NULL';
                $id_jenis_val = $id_jenis_motor ? "'$id_jenis_motor'" : 'NULL';
                $ciri_fisik_val = !empty($ciri_fisik_pembeda) ? "'$ciri_fisik_pembeda'" : 'NULL';
                $tahun_awal_val = $tahun_awal ? "'$tahun_awal'" : 'NULL';
                $tahun_akhir_val = !empty($tahun_akhir) ? "'$tahun_akhir'" : 'NULL';
                $no_seri_val = !empty($no_seri_mesin) ? "'$no_seri_mesin'" : 'NULL';
                $id_kategori_val = $id_kategori_motor ? "'$id_kategori_motor'" : 'NULL';

                // Update data (kode_tipe tidak diupdate karena auto generate)
                $update_query = "UPDATE tbmaster_tipe_detail SET
                               kode_brand = '$kode_brand',
                               id_tipe_header = '$id_tipe_header',
                               nama_detail = '$nama_detail',
                               cc = $cc_val,
                               id_jenis_motor = $id_jenis_val,
                               fitur_pembeda = '$fitur_pembeda',
                               ciri_fisik_pembeda = $ciri_fisik_val,
                               tahun_awal = $tahun_awal_val,
                               tahun_akhir = $tahun_akhir_val,
                               no_seri_mesin = $no_seri_val,
                               id_kategori_motor = $id_kategori_val,
                               updated_at = NOW()
                               WHERE id = '$id'";

                if (mysqli_query($koneksi, $update_query)) {
                    $_SESSION['success'] = "Data tipe detail berhasil diupdate!";
                    header('Location: master_tipe_detail.php');
                    exit();
                } else {
                    $error_msg = "Gagal mengupdate data: " . mysqli_error($koneksi);
                }
            }
        }
    }
} else {
    // Set initial values dari database
    $id_tipe_header = $data['id_tipe_header'];
    $nama_detail = $data['nama_detail'];
    $cc = $data['cc'];
    $id_jenis_motor = $data['id_jenis_motor'];
    $fitur_pembeda = $data['fitur_pembeda'];
    $ciri_fisik_pembeda = $data['ciri_fisik_pembeda'];
    $tahun_awal = $data['tahun_awal'];
    $tahun_akhir = $data['tahun_akhir'];
    $no_seri_mesin = $data['no_seri_mesin'];
    $id_kategori_motor = $data['id_kategori_motor'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title><?php echo $page_title; ?> - Web Bengkel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/ace.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/font-awesome/4.5.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />

    <style>
        .page-header {
            border-bottom: 2px solid #e5e5e5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-actions {
            border-top: 1px solid #e5e5e5;
            padding-top: 20px;
            margin-top: 30px;
        }
        .required {
            color: #d15b47;
        }
        .help-text {
            font-size: 11px;
            color: #999;
        }
        .original-data {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 25px;
            border-left: 4px solid #5bc0de;
        }
        .original-data h5 {
            margin-top: 0;
            color: #31708f;
        }
        .data-compare {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
        .data-compare .col {
            padding: 15px;
            border-radius: 3px;
        }
        .original-col {
            background-color: #d9edf7;
            border: 1px solid #bce8f1;
        }
        .edit-col {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
        }
        .kode-tipe-readonly {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
            color: #337ab7;
        }
        .empty-value {
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body class="no-skin">
    <div class="main-content">
        <div class="main-content-inner">
            <div class="page-content">
                <div class="row">
                    <div class="col-md-10 col-md-offset-1">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h1>
                                <?php echo $page_title; ?>
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Update Data Tipe Detail Motor
                                </small>
                            </h1>
                        </div>

                        <div class="form-container">
                            <!-- Alert Messages -->
                            <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="ace-icon fa fa-times"></i>
                                </button>
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>Error!</strong> <?php echo $error_msg; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Data Asli -->
                            <div class="original-data">
                                <h5><i class="fa fa-info-circle"></i> Data Asli</h5>
                                <div class="data-compare">
                                    <div class="col original-col">
                                        <strong>Kode Tipe:</strong> <?php echo htmlspecialchars($data['kode_tipe']); ?><br>
                                        <strong>Brand:</strong> <?php echo htmlspecialchars($data['kode_brand'] . ' ' . $data['nama_brand']); ?><br>
                                        <strong>Tipe Header:</strong> <?php echo htmlspecialchars($data['nama_tipe_header']); ?><br>
                                        <strong>Nama Detail:</strong> <?php echo $data['nama_detail'] ? htmlspecialchars($data['nama_detail']) : '-'; ?><br>
                                        <strong>CC:</strong> <?php echo $data['cc'] ? $data['cc'] . 'cc' : '-'; ?><br>
                                        <strong>Fitur:</strong> <?php echo $data['fitur_pembeda'] ? htmlspecialchars($data['fitur_pembeda']) : '-'; ?><br>
                                        <strong>Tahun:</strong>
                                        <?php
                                        if ($data['tahun_awal']) {
                                            echo $data['tahun_awal'];
                                            if ($data['tahun_akhir']) echo ' s/d ' . $data['tahun_akhir'];
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                    <div class="col edit-col">
                                        <strong>Status:</strong> <span class="text-info">Sedang diedit...</span><br>
                                        <small class="text-muted">Data akan berubah setelah tombol "Update" diklik</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Form -->
                            <form method="POST" action="" class="form-horizontal">
                                <!-- Kode Tipe (Read Only) -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Kode Tipe
                                    </label>
                                    <div class="col-sm-3">
                                        <div class="kode-tipe-readonly">
                                            <?php echo htmlspecialchars($data['kode_tipe']); ?>
                                        </div>
                                        <div class="help-text">
                                            Kode tipe tidak bisa diubah (auto generate)
                                        </div>
                                    </div>
                                </div>

                                <!-- Tipe Header -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Tipe Header <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-6">
                                        <select name="id_tipe_header" class="form-control" id="tipe_header_select" required>
                                            <option value="">-- Pilih Tipe Header --</option>
                                            <?php
                                            mysqli_data_seek($tipe_headers_result, 0);
                                            while ($th = mysqli_fetch_assoc($tipe_headers_result)) {
                                                $selected = ($id_tipe_header == $th['id']) ? 'selected' : '';
                                                echo "<option value='{$th['id']}' data-brand='{$th['kode_brand']}' data-nama='{$th['nama_tipe']}' $selected>";
                                                echo "{$th['kode_brand']} {$th['nama_tipe']}";
                                                echo "</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="help-text">
                                            Jika mengubah tipe header, kode brand akan ikut berubah
                                        </div>
                                    </div>
                                </div>

                                <!-- Nama Detail -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Nama Detail
                                    </label>
                                    <div class="col-sm-4">
                                        <input type="text" name="nama_detail" class="form-control"
                                               value="<?php echo ($nama_detail && $nama_detail != '-') ? htmlspecialchars($nama_detail) : ''; ?>"
                                               placeholder="Contoh: STREET"
                                               maxlength="100"
                                               style="text-transform: uppercase;"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Jangan mengulang kata yang ada di tipe header. Kosongkan atau isi "-" jika tidak ada detail
                                        </div>
                                    </div>
                                </div>

                                <!-- CC -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        CC (Cubic Centimeter)
                                    </label>
                                    <div class="col-sm-3">
                                        <input type="number" name="cc" class="form-control"
                                               value="<?php echo $cc ? $cc : ''; ?>"
                                               placeholder="110"
                                               min="50" max="9999"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Hanya bisa diisi angka (ketik manual)
                                        </div>
                                    </div>
                                </div>

                                <!-- Jenis Motor -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Jenis Motor
                                    </label>
                                    <div class="col-sm-4">
                                        <select name="id_jenis_motor" class="form-control">
                                            <option value="">-- Pilih Jenis Motor --</option>
                                            <?php
                                            mysqli_data_seek($jenis_motors_result, 0);
                                            while ($jm = mysqli_fetch_assoc($jenis_motors_result)) {
                                                $selected = ($id_jenis_motor == $jm['id']) ? 'selected' : '';
                                                echo "<option value='{$jm['id']}' $selected>{$jm['jenis']}</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="help-text">
                                            Pilih dari master jenis motor
                                        </div>
                                    </div>
                                </div>

                                <!-- Fitur Pembeda -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Fitur Pembeda
                                    </label>
                                    <div class="col-sm-6">
                                        <input type="text" name="fitur_pembeda" class="form-control"
                                               value="<?php echo ($fitur_pembeda && $fitur_pembeda != '-') ? htmlspecialchars($fitur_pembeda) : ''; ?>"
                                               placeholder="Contoh: ESP K25G-H"
                                               maxlength="255"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Jika tidak ada fitur pembeda, kosongkan atau isi dengan "-"
                                        </div>
                                    </div>
                                </div>

                                <!-- Ciri Fisik Pembeda -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Ciri Fisik Pembeda
                                    </label>
                                    <div class="col-sm-8">
                                        <textarea name="ciri_fisik_pembeda" class="form-control" rows="3"
                                                  placeholder="Contoh: Sein nempel lampu, posisi lebih tinggi dari lampu utama. Stater bunyinya halus"><?php echo htmlspecialchars($ciri_fisik_pembeda); ?></textarea>
                                        <div class="help-text">
                                            Diisi manual bebas. Bisa dikosongkan jika belum tahu
                                        </div>
                                    </div>
                                </div>

                                <!-- Tahun Awal -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Tahun Awal Produksi
                                    </label>
                                    <div class="col-sm-2">
                                        <input type="number" name="tahun_awal" class="form-control"
                                               value="<?php echo $tahun_awal ? $tahun_awal : ''; ?>"
                                               placeholder="2015"
                                               min="1900" max="<?php echo date('Y'); ?>"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Hanya bisa diisi angka 4 digit, &le; dari tahun saat ini
                                        </div>
                                    </div>
                                </div>

                                <!-- Tahun Akhir -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Tahun Akhir Produksi
                                    </label>
                                    <div class="col-sm-3">
                                        <input type="text" name="tahun_akhir" class="form-control"
                                               value="<?php echo htmlspecialchars($tahun_akhir); ?>"
                                               placeholder="2016 atau SEKARANG"
                                               maxlength="10"
                                               style="text-transform: uppercase;"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Hanya bisa diisi angka atau kata "SEKARANG"
                                        </div>
                                    </div>
                                </div>

                                <!-- No Seri Mesin -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        No. Seri Mesin
                                    </label>
                                    <div class="col-sm-3">
                                        <input type="text" name="no_seri_mesin" class="form-control"
                                               value="<?php echo htmlspecialchars($no_seri_mesin); ?>"
                                               placeholder="JFP2E"
                                               maxlength="20"
                                               style="text-transform: uppercase;"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Diisi manual tanpa spasi
                                        </div>
                                    </div>
                                </div>

                                <!-- Kategori Motor -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Kategori Motor
                                    </label>
                                    <div class="col-sm-4">
                                        <select name="id_kategori_motor" class="form-control">
                                            <option value="">-- Pilih Kategori Motor --</option>
                                            <?php
                                            mysqli_data_seek($kategori_motors_result, 0);
                                            while ($km = mysqli_fetch_assoc($kategori_motors_result)) {
                                                $selected = ($id_kategori_motor == $km['id']) ? 'selected' : '';
                                                echo "<option value='{$km['id']}' $selected>{$km['kategori']}</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="help-text">
                                            Pilih dari kategori motor
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update
                                        </button>

                                        <a href="master_tipe_detail.php" class="btn btn-success">
                                            <i class="fa fa-list"></i> Lihat Daftar Tipe Detail
                                        </a>

                                        <a href="menu_master01h.php" class="btn btn-default">
                                            <i class="fa fa-home"></i> Ke Menu Awal
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <!-- Info -->
                            <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #f0ad4e;">
                                <h5><strong>Perhatian saat Edit:</strong></h5>
                                <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                    <li>• Kode tipe tidak bisa diubah (auto generate saat pertama input)</li>
                                    <li>• Jika mengubah tipe header, kode brand akan ikut berubah</li>
                                    <li>• Nama detail tetap tidak boleh mengulang tipe header</li>
                                    <li>• Data yang dikosongkan akan masuk ke daftar permintaan pengisian</li>
                                    <li>• Pastikan data sudah benar sebelum klik "Update"</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        $(document).ready(function() {
            // Auto focus pada tipe header
            $('#tipe_header_select').focus();

            // Remove spaces from no_seri_mesin
            $('input[name="no_seri_mesin"]').on('input', function() {
                this.value = this.value.replace(/\s+/g, '');
            });

            // Validasi form sebelum submit
            $('form').on('submit', function(e) {
                var tipeHeader = $('#tipe_header_select').val();
                var namaDetail = $('input[name="nama_detail"]').val().trim();
                var tahunAwal = $('input[name="tahun_awal"]').val();
                var tahunAkhir = $('input[name="tahun_akhir"]').val().trim().toUpperCase();
                var noSeriMesin = $('input[name="no_seri_mesin"]').val();

                if (!tipeHeader) {
                    alert('Tipe Header harus dipilih!');
                    e.preventDefault();
                    return false;
                }

                // Validasi nama detail tidak mengulang tipe header
                if (namaDetail && namaDetail !== '-') {
                    var selectedTipeHeader = $('#tipe_header_select option:selected').data('nama').toUpperCase();
                    var detailWords = namaDetail.toUpperCase().split(' ');
                    var headerWords = selectedTipeHeader.split(' ');

                    var duplicateFound = false;
                    detailWords.forEach(function(word) {
                        if (headerWords.indexOf(word) !== -1) {
                            duplicateFound = true;
                        }
                    });

                    if (duplicateFound) {
                        alert('Nama detail tidak boleh mengulang kata yang ada di tipe header!');
                        e.preventDefault();
                        return false;
                    }
                }

                // Validasi tahun akhir
                if (tahunAkhir && tahunAkhir !== 'SEKARANG' && !/^\d{4}$/.test(tahunAkhir)) {
                    alert('Tahun akhir harus berupa angka 4 digit atau kata "SEKARANG"!');
                    e.preventDefault();
                    return false;
                }

                // Validasi no seri mesin tanpa spasi
                if (noSeriMesin && noSeriMesin.indexOf(' ') !== -1) {
                    alert('Nomor seri mesin tidak boleh mengandung spasi!');
                    e.preventDefault();
                    return false;
                }

                // Konfirmasi sebelum update
                return confirm('Yakin ingin mengupdate data tipe detail motor ini?');
            });
        });
    </script>
</body>
</html>