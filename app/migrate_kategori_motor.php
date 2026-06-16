<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}
include "../config/koneksi.php";

$messages = [];
$errors = [];
$ran = isset($_POST['run']);
$drop_legacy = isset($_POST['drop_legacy']);

function tbl_exists($koneksi, $name) {
    $name = mysqli_real_escape_string($koneksi, $name);
    $res = mysqli_query($koneksi, "SHOW TABLES LIKE '{$name}'");
    return ($res && mysqli_num_rows($res) > 0);
}
function col_exists($koneksi, $table, $col) {
    $table = mysqli_real_escape_string($koneksi, $table);
    $col = mysqli_real_escape_string($koneksi, $col);
    $res = mysqli_query($koneksi, "SHOW COLUMNS FROM {$table} LIKE '{$col}'");
    return ($res && mysqli_num_rows($res) > 0);
}
function idx_exists($koneksi, $table, $key) {
    $table = mysqli_real_escape_string($koneksi, $table);
    $key = mysqli_real_escape_string($koneksi, $key);
    $res = mysqli_query($koneksi, "SHOW INDEX FROM {$table} WHERE Key_name='{$key}'");
    return ($res && mysqli_num_rows($res) > 0);
}
function run_sql($koneksi, $sql, &$messages, &$errors, $label) {
    if (mysqli_query($koneksi, $sql)) {
        $messages[] = $label;
        return true;
    } else {
        $errors[] = $label . ' [ERROR] ' . mysqli_error($koneksi);
        return false;
    }
}

if ($ran) {
    // 1) Ensure new columns exist
    if (tbl_exists($koneksi, 'tbitem_jenis_motor') && !col_exists($koneksi, 'tbitem_jenis_motor', 'kd_kategori_motor')) {
        run_sql($koneksi, "ALTER TABLE tbitem_jenis_motor ADD COLUMN kd_kategori_motor INT NULL", $messages, $errors, 'ADD tbitem_jenis_motor.kd_kategori_motor');
    }
    if (tbl_exists($koneksi, 'tbworkorder_jenis_motor') && !col_exists($koneksi, 'tbworkorder_jenis_motor', 'kd_kategori_motor')) {
        run_sql($koneksi, "ALTER TABLE tbworkorder_jenis_motor ADD COLUMN kd_kategori_motor INT NULL", $messages, $errors, 'ADD tbworkorder_jenis_motor.kd_kategori_motor');
    }

    // 2) Populate kd_kategori_motor
    if (tbl_exists($koneksi, 'tbkategori_motor')) {
        if (tbl_exists($koneksi, 'tbitem_jenis_motor')) {
            // Phase 1: id equal copy
            run_sql($koneksi, "UPDATE tbitem_jenis_motor m JOIN tbkategori_motor k ON k.id = m.kd_jenis_motor SET m.kd_kategori_motor = k.id WHERE m.kd_kategori_motor IS NULL", $messages, $errors, 'Populate item mapping (id match)');
            // Phase 2: via tbjenis_motor name
            if (tbl_exists($koneksi, 'tbjenis_motor')) {
                run_sql($koneksi, "UPDATE tbitem_jenis_motor m JOIN tbjenis_motor j ON j.kd = m.kd_jenis_motor JOIN tbkategori_motor k ON k.kategori = j.jenis_motor SET m.kd_kategori_motor = k.id WHERE m.kd_kategori_motor IS NULL", $messages, $errors, 'Populate item mapping (name match)');
            }
        }
        if (tbl_exists($koneksi, 'tbworkorder_jenis_motor')) {
            // Phase 1: id equal copy
            run_sql($koneksi, "UPDATE tbworkorder_jenis_motor m JOIN tbkategori_motor k ON k.id = m.kd_jenis_motor SET m.kd_kategori_motor = k.id WHERE m.kd_kategori_motor IS NULL", $messages, $errors, 'Populate WO mapping (id match)');
            // Phase 2: via tbjenis_motor name
            if (tbl_exists($koneksi, 'tbjenis_motor')) {
                run_sql($koneksi, "UPDATE tbworkorder_jenis_motor m JOIN tbjenis_motor j ON j.kd = m.kd_jenis_motor JOIN tbkategori_motor k ON k.kategori = j.jenis_motor SET m.kd_kategori_motor = k.id WHERE m.kd_kategori_motor IS NULL", $messages, $errors, 'Populate WO mapping (name match)');
            }
        }
    }

    // 3) Indexes
    if (tbl_exists($koneksi, 'tbitem_jenis_motor')) {
        if (!idx_exists($koneksi, 'tbitem_jenis_motor', 'idx_noitem')) {
            run_sql($koneksi, "ALTER TABLE tbitem_jenis_motor ADD INDEX idx_noitem (noitem)", $messages, $errors, 'Add index tbitem_jenis_motor.idx_noitem');
        }
        if (!idx_exists($koneksi, 'tbitem_jenis_motor', 'idx_kategori')) {
            run_sql($koneksi, "ALTER TABLE tbitem_jenis_motor ADD INDEX idx_kategori (kd_kategori_motor)", $messages, $errors, 'Add index tbitem_jenis_motor.idx_kategori');
        }
    }
    if (tbl_exists($koneksi, 'tbworkorder_jenis_motor')) {
        if (!idx_exists($koneksi, 'tbworkorder_jenis_motor', 'idx_kodewo')) {
            run_sql($koneksi, "ALTER TABLE tbworkorder_jenis_motor ADD INDEX idx_kodewo (kode_wo)", $messages, $errors, 'Add index tbworkorder_jenis_motor.idx_kodewo');
        }
        if (!idx_exists($koneksi, 'tbworkorder_jenis_motor', 'idx_kategori')) {
            run_sql($koneksi, "ALTER TABLE tbworkorder_jenis_motor ADD INDEX idx_kategori (kd_kategori_motor)", $messages, $errors, 'Add index tbworkorder_jenis_motor.idx_kategori');
        }
    }

    // 4) View alias
    run_sql($koneksi, "CREATE OR REPLACE VIEW view_service_kategori_motor AS SELECT no_service, kd_jenis_motor AS kd_kategori_motor FROM view_service_jenis_motor", $messages, $errors, 'Create/Replace view_service_kategori_motor');

    // 5) Optional drop legacy columns
    if ($drop_legacy) {
        if (tbl_exists($koneksi, 'tbitem_jenis_motor') && col_exists($koneksi, 'tbitem_jenis_motor', 'kd_jenis_motor')) {
            run_sql($koneksi, "ALTER TABLE tbitem_jenis_motor DROP COLUMN kd_jenis_motor", $messages, $errors, 'Drop tbitem_jenis_motor.kd_jenis_motor');
        }
        if (tbl_exists($koneksi, 'tbworkorder_jenis_motor') && col_exists($koneksi, 'tbworkorder_jenis_motor', 'kd_jenis_motor')) {
            run_sql($koneksi, "ALTER TABLE tbworkorder_jenis_motor DROP COLUMN kd_jenis_motor", $messages, $errors, 'Drop tbworkorder_jenis_motor.kd_jenis_motor');
        }
    }

    // Counts
    $cnt_item = 0; $cnt_wo = 0;
    if (tbl_exists($koneksi, 'tbitem_jenis_motor')) {
        $r = mysqli_query($koneksi, "SELECT COUNT(*) c FROM tbitem_jenis_motor WHERE kd_kategori_motor IS NOT NULL");
        if ($r && ($rr = mysqli_fetch_assoc($r))) $cnt_item = intval($rr['c']);
    }
    if (tbl_exists($koneksi, 'tbworkorder_jenis_motor')) {
        $r = mysqli_query($koneksi, "SELECT COUNT(*) c FROM tbworkorder_jenis_motor WHERE kd_kategori_motor IS NOT NULL");
        if ($r && ($rr = mysqli_fetch_assoc($r))) $cnt_wo = intval($rr['c']);
    }
    $messages[] = "Mapped counts => tbitem_jenis_motor: {$cnt_item}, tbworkorder_jenis_motor: {$cnt_wo}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Migrasi Kategori Motor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
<div class="main-container" id="main-container">
    <div class="main-content">
        <div class="main-content-inner">
            <div class="page-content">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="widget-box">
                            <div class="widget-header"><h4 class="widget-title">Migrasi ke Kategori Motor</h4></div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <form method="post">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="drop_legacy" class="ace" <?php echo $drop_legacy? 'checked':''; ?>>
                                                <span class="lbl"> Hapus kolom legacy kd_jenis_motor setelah migrasi (opsional)</span>
                                            </label>
                                        </div>
                                        <button type="submit" name="run" value="1" class="btn btn-primary"><i class="fa fa-play"></i> Jalankan Migrasi</button>
                                        <a href="index.php" class="btn btn-default">Kembali</a>
                                    </form>
                                    <hr/>
                                    <?php if ($ran): ?>
                                        <?php if (!empty($messages)): ?>
                                            <div class="alert alert-success">
                                                <ul style="margin:0; padding-left:18px;">
                                                    <?php foreach ($messages as $m): ?>
                                                        <li><?php echo htmlspecialchars($m); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($errors)): ?>
                                            <div class="alert alert-warning">
                                                <strong>Beberapa langkah gagal:</strong>
                                                <ul style="margin:0; padding-left:18px;">
                                                    <?php foreach ($errors as $e): ?>
                                                        <li><?php echo htmlspecialchars($e); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="alert alert-info" style="margin-top:10px;">
                                        Langkah: tambah kolom baru, isi data dari kolom lama/alias nama, tambah index, buat view alias. Opsi untuk hapus kolom lama tersedia.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
</body>
</html>
