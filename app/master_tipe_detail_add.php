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

$page_title = "Input Tipe Detail Motor Baru";
$error_msg = '';
$success_msg = '';

// Get dropdown data
$brands_query = "SELECT kode_brand, merek FROM tbpabrik_motor WHERE status = '1' ORDER BY merek";
$brands_result = mysqli_query($koneksi, $brands_query);

$tipe_headers_query = "SELECT id, nama_tipe, kode_brand FROM tbmaster_tipe_header WHERE status = '1' ORDER BY nama_tipe";
$tipe_headers_result = mysqli_query($koneksi, $tipe_headers_query);

$jenis_motors_query = "SELECT id, jenis FROM tbjenis_motor WHERE status = '1' ORDER BY jenis";
$jenis_motors_result = mysqli_query($koneksi, $jenis_motors_query);

$kategori_motors_query = "SELECT id, kategori FROM tbkategori_motor WHERE status = '1' ORDER BY kategori";
$kategori_motors_result = mysqli_query($koneksi, $kategori_motors_query);

// Function to generate kode_tipe
function generateKodeTipe($koneksi, $id_tipe_header) {
    // Get 3 digit pertama dari tipe header
    $header_query = "SELECT nama_tipe FROM tbmaster_tipe_header WHERE id = '$id_tipe_header' AND status = '1'";
    $header_result = mysqli_query($koneksi, $header_query);

    if (mysqli_num_rows($header_result) > 0) {
        $header_data = mysqli_fetch_assoc($header_result);
        $prefix = strtoupper(substr($header_data['nama_tipe'], 0, 3));

        // Get running number
        $count_query = "SELECT COUNT(*) + 1 as next_num FROM tbmaster_tipe_detail
                       WHERE id_tipe_header = '$id_tipe_header' AND status = '1'";
        $count_result = mysqli_query($koneksi, $count_query);
        $count_data = mysqli_fetch_assoc($count_result);

        $running_number = str_pad($count_data['next_num'], 3, '0', STR_PAD_LEFT);

        return $prefix . $running_number;
    }

    return '';
}

// Process form submission
if ($_POST) {
    $id_tipe_header = (int)$_POST['id_tipe_header'];
    $nama_detail = trim($_POST['nama_detail']);
    $cc = !empty($_POST['cc']) ? (int)$_POST['cc'] : null;
    $id_jenis_motor = !empty($_POST['id_jenis_motor']) ? (int)$_POST['id_jenis_motor'] : null;
    $fitur_pembeda = trim($_POST['fitur_pembeda']);
    $ciri_fisik_pembeda = trim($_POST['ciri_fisik_pembeda']);
    $tahun_awal = !empty($_POST['tahun_awal']) ? (int)$_POST['tahun_awal'] : null;
    $tahun_akhir = trim($_POST['tahun_akhir']);
    $no_seri_mesin = trim($_POST['no_seri_mesin']);
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
                // Generate kode_tipe
                $kode_tipe = generateKodeTipe($koneksi, $id_tipe_header);

                // Cek duplikat kode_tipe
                $check_query = "SELECT id FROM tbmaster_tipe_detail WHERE kode_tipe = '$kode_tipe' AND status = '1'";
                $check_result = mysqli_query($koneksi, $check_query);

                if (mysqli_num_rows($check_result) > 0) {
                    $error_msg = "Kode tipe '$kode_tipe' sudah ada! Silakan coba lagi.";
                } else {
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

                    // Insert data
                    $insert_query = "INSERT INTO tbmaster_tipe_detail
                                   (kode_tipe, kode_brand, id_tipe_header, nama_detail, cc, id_jenis_motor,
                                    fitur_pembeda, ciri_fisik_pembeda, tahun_awal, tahun_akhir, no_seri_mesin,
                                    id_kategori_motor, status, created_at)
                                   VALUES ('$kode_tipe', '$kode_brand', '$id_tipe_header', '$nama_detail', $cc_val,
                                          $id_jenis_val, '$fitur_pembeda', $ciri_fisik_val, $tahun_awal_val,
                                          $tahun_akhir_val, $no_seri_val, $id_kategori_val, '1', NOW())";

                    if (mysqli_query($koneksi, $insert_query)) {
                        $_SESSION['success'] = "Data tipe detail berhasil tersimpan dengan kode: $kode_tipe";
                        header('Location: master_tipe_detail.php');
                        exit();
                    } else {
                        $error_msg = "Gagal menyimpan data: " . mysqli_error($koneksi);
                    }
                }
            }
        }
    }
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
        .auto-fill-section {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .preview-kode {
            font-weight: bold;
            color: #337ab7;
            font-size: 14px;
        }
        .optional-fields {
            background-color: #fcf8e3;
            border: 1px solid #faebcc;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
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
                                    Tambah Data Tipe Detail Motor
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

                            <!-- Auto Fill Section -->
                            <div class="auto-fill-section">
                                <h5><i class="fa fa-magic"></i> Kode Tipe Otomatis</h5>
                                <div id="kode-preview">
                                    <span class="preview-kode">Pilih tipe header untuk melihat preview kode tipe</span>
                                </div>
                                <small class="text-muted">Kode tipe akan otomatis terisi: 3 digit pertama tipe header + running number</small>
                            </div>

                            <!-- Form -->
                            <form method="POST" action="" class="form-horizontal">
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
                                                $selected = (isset($id_tipe_header) && $id_tipe_header == $th['id']) ? 'selected' : '';
                                                echo "<option value='{$th['id']}' data-brand='{$th['kode_brand']}' data-nama='{$th['nama_tipe']}' $selected>";
                                                echo "{$th['kode_brand']} {$th['nama_tipe']}";
                                                echo "</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="help-text">
                                            Setelah pilih, otomatis muncul kode brandnya dan preview kode tipe
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
                                               value="<?php echo isset($nama_detail) ? htmlspecialchars($nama_detail) : ''; ?>"
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
                                               value="<?php echo isset($cc) ? $cc : ''; ?>"
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
                                                $selected = (isset($id_jenis_motor) && $id_jenis_motor == $jm['id']) ? 'selected' : '';
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
                                               value="<?php echo isset($fitur_pembeda) ? htmlspecialchars($fitur_pembeda) : ''; ?>"
                                               placeholder="Contoh: ESP K25G-H"
                                               maxlength="255"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Jika tidak ada fitur pembeda, kosongkan atau isi dengan "-"
                                        </div>
                                    </div>
                                </div>

                                <div class="optional-fields">
                                    <h5><i class="fa fa-info-circle"></i> Field Opsional (Boleh Dikosongkan)</h5>
                                    <p class="text-muted">Field di bawah ini boleh dikosongkan jika belum tahu. Data yang kosong akan masuk ke daftar permintaan pengisian data.</p>
                                </div>

                                <!-- Ciri Fisik Pembeda -->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Ciri Fisik Pembeda
                                    </label>
                                    <div class="col-sm-8">
                                        <textarea name="ciri_fisik_pembeda" class="form-control" rows="3"
                                                  placeholder="Contoh: Sein nempel lampu, posisi lebih tinggi dari lampu utama. Stater bunyinya halus"><?php echo isset($ciri_fisik_pembeda) ? htmlspecialchars($ciri_fisik_pembeda) : ''; ?></textarea>
                                        <div class="help-text">
                                            Diisi manual bebas. Jika belum tahu bisa dilewati (dikosongkan)
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
                                               value="<?php echo isset($tahun_awal) ? $tahun_awal : ''; ?>"
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
                                               value="<?php echo isset($tahun_akhir) ? htmlspecialchars($tahun_akhir) : ''; ?>"
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
                                               value="<?php echo isset($no_seri_mesin) ? htmlspecialchars($no_seri_mesin) : ''; ?>"
                                               placeholder="JFP2E"
                                               maxlength="20"
                                               style="text-transform: uppercase;"
                                               autocomplete="off">
                                        <div class="help-text">
                                            Diisi manual tanpa spasi. Jika belum tahu bisa dilewati
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
                                                $selected = (isset($id_kategori_motor) && $id_kategori_motor == $km['id']) ? 'selected' : '';
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
                                            <i class="fa fa-save"></i> Simpan
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

            // Update preview kode tipe
            $('#tipe_header_select').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var brand = selectedOption.data('brand');
                var nama = selectedOption.data('nama');

                if (nama) {
                    var prefix = nama.substring(0, 3).toUpperCase();
                    var preview = prefix + '001';
                    $('#kode-preview').html('<span class="preview-kode">Preview: ' + preview + '</span><br><small class="text-muted">Brand: ' + brand + ' | Tipe: ' + nama + '</small>');
                } else {
                    $('#kode-preview').html('<span class="preview-kode">Pilih tipe header untuk melihat preview kode tipe</span>');
                }
            });

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

                return true;
            });
        });
    </script>
</body>
</html>