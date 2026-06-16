<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

// Load user (for header avatar)
$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi, $id_user)."'");
$tm_cari = mysqli_fetch_array($cari_kd);
$_nama = $tm_cari['nama_user'] ?? 'User';
$foto_user = $tm_cari['foto_user'] ?? '';
if ($foto_user == '') { $foto_user = "file_upload/avatar.png"; }

$use_kategori_col = false;
$chk_kategori = mysqli_query($koneksi, "SHOW COLUMNS FROM tbitem_jenis_motor LIKE 'kd_kategori_motor'");
if ($chk_kategori && mysqli_num_rows($chk_kategori) > 0) {
    $use_kategori_col = true;
}

$use_jenis_col = false;
$chk_jenis = mysqli_query($koneksi, "SHOW COLUMNS FROM tbitem_jenis_motor LIKE 'kd_jenis_motor'");
if ($chk_jenis && mysqli_num_rows($chk_jenis) > 0) {
    $use_jenis_col = true;
}

$use_kategori_col_wo = false;
$chk_kategori_wo = mysqli_query($koneksi, "SHOW COLUMNS FROM tbworkorder_jenis_motor LIKE 'kd_kategori_motor'");
if ($chk_kategori_wo && mysqli_num_rows($chk_kategori_wo) > 0) {
    $use_kategori_col_wo = true;
}

$use_jenis_col_wo = false;
$chk_jenis_wo = mysqli_query($koneksi, "SHOW COLUMNS FROM tbworkorder_jenis_motor LIKE 'kd_jenis_motor'");
if ($chk_jenis_wo && mysqli_num_rows($chk_jenis_wo) > 0) {
    $use_jenis_col_wo = true;
}

$entity = $_GET['type'] ?? 'item';
if ($entity !== 'item' && $entity !== 'wo') {
    $entity = 'item';
}

$key = isset($_GET['kd']) ? trim($_GET['kd']) : '';
$key_safe = mysqli_real_escape_string($koneksi, $key);
$mode = ($key !== '') ? 'edit' : 'list';

$q = trim($_GET['q'] ?? '');
$q_safe = mysqli_real_escape_string($koneksi, $q);

$jenis_filter = $_GET['jenis'] ?? 'barang';
if (!in_array($jenis_filter, ['barang', 'jasa', 'semua'], true)) {
    $jenis_filter = 'barang';
}

if (isset($_POST['btn_cleanup_kategori'])) {
    if ($use_kategori_col || $use_kategori_col_wo) {
        $dupq = mysqli_query($koneksi, "SELECT UPPER(REPLACE(TRIM(kategori), ' ', '')) as k, MIN(id) as id_utama, GROUP_CONCAT(id ORDER BY id) as ids, COUNT(*) as c FROM tbkategori_motor GROUP BY UPPER(REPLACE(TRIM(kategori), ' ', '')) HAVING c > 1");
        $dup_count = 0;
        if ($dupq) {
            while ($d = mysqli_fetch_assoc($dupq)) {
                $id_utama = intval($d['id_utama']);
                $ids = array_filter(array_map('intval', explode(',', (string)($d['ids'] ?? ''))));
                foreach ($ids as $id_dup) {
                    if ($id_dup > 0 && $id_dup !== $id_utama) {
                        if ($use_kategori_col) {
                            mysqli_query($koneksi, "UPDATE tbitem_jenis_motor SET kd_kategori_motor=$id_utama WHERE kd_kategori_motor=$id_dup");
                        }
                        if ($use_kategori_col_wo) {
                            mysqli_query($koneksi, "UPDATE tbworkorder_jenis_motor SET kd_kategori_motor=$id_utama WHERE kd_kategori_motor=$id_dup");
                        }
                        mysqli_query($koneksi, "UPDATE tbtipe_motor SET kode_kategori=$id_utama WHERE kode_kategori=$id_dup");
                        mysqli_query($koneksi, "UPDATE tbkategori_motor SET status='0' WHERE id=$id_dup");
                        $dup_count++;
                    }
                }
            }
        }
        $_SESSION['flash_success'] = 'Cleanup kategori selesai. Duplikat dinonaktifkan: ' . intval($dup_count);
    } else {
        $_SESSION['flash_success'] = 'Cleanup kategori dilewati. Kolom kategori belum tersedia.';
    }
    $redir = 'item-motor-mapping.php?type=' . urlencode($entity);
    header('Location: ' . $redir);
    exit;
}

// Handle save mapping
if (isset($_POST['btnsave'])) {
    $entity_post = $_POST['entity'] ?? 'item';
    if ($entity_post !== 'item' && $entity_post !== 'wo') {
        $entity_post = 'item';
    }
    $key_post = $_POST['key'] ?? '';
    $key_post_safe = mysqli_real_escape_string($koneksi, $key_post);
    $selected_motor = isset($_POST['kategori_motor']) && is_array($_POST['kategori_motor']) ? $_POST['kategori_motor'] : array();
    $selected_tipe = isset($_POST['tipe_motor']) && is_array($_POST['tipe_motor']) ? $_POST['tipe_motor'] : array();

    if ($key_post_safe !== '') {
        if ($entity_post === 'item') {
            $cek_item = mysqli_query($koneksi, "SELECT noitem FROM tblitem WHERE noitem='".$key_post_safe."' LIMIT 1");
            if ($cek_item && mysqli_num_rows($cek_item) > 0) {
                mysqli_query($koneksi, "DELETE FROM tbitem_jenis_motor WHERE noitem='".$key_post_safe."'");
                if (!empty($selected_motor)) {
                    foreach ($selected_motor as $jm) {
                        $jm = intval($jm);
                        if ($jm > 0) {
                            $cek_kat = mysqli_query($koneksi, "SELECT id FROM tbkategori_motor WHERE id=$jm AND (status='1' OR status IS NULL) LIMIT 1");
                            if ($cek_kat && mysqli_num_rows($cek_kat) > 0) {
                                if ($use_kategori_col) {
                                    mysqli_query($koneksi, "INSERT IGNORE INTO tbitem_jenis_motor (noitem, kd_kategori_motor) VALUES ('".$key_post_safe."', $jm)");
                                } else {
                                    mysqli_query($koneksi, "INSERT IGNORE INTO tbitem_jenis_motor (noitem, kd_jenis_motor) VALUES ('".$key_post_safe."', $jm)");
                                }
                            }
                        }
                    }
                }

                mysqli_query($koneksi, "DELETE FROM tblitem_spart WHERE noitem='".$key_post_safe."'");
                if (!empty($selected_tipe)) {
                    foreach ($selected_tipe as $kt) {
                        $kt = intval($kt);
                        if ($kt > 0) {
                            $cek_tipe = mysqli_query($koneksi, "SELECT kode_tipe FROM tbtipe_motor WHERE kode_tipe=$kt LIMIT 1");
                            if ($cek_tipe && mysqli_num_rows($cek_tipe) > 0) {
                                mysqli_query($koneksi, "INSERT INTO tblitem_spart (noitem, kode_tipe) VALUES ('".$key_post_safe."', $kt)");
                            }
                        }
                    }
                }

                $_SESSION['flash_success'] = 'Mapping berhasil disimpan untuk Item: ' . htmlspecialchars($key_post);
                header("Location: item-motor-mapping.php?type=item&kd=" . urlencode($key_post));
                exit;
            }
        } else {
            $cek_wo = mysqli_query($koneksi, "SELECT kode_wo FROM tbworkorderheader WHERE kode_wo='".$key_post_safe."' LIMIT 1");
            if ($cek_wo && mysqli_num_rows($cek_wo) > 0) {
                mysqli_query($koneksi, "DELETE FROM tbworkorder_jenis_motor WHERE kode_wo='".$key_post_safe."'");
                if (!empty($selected_motor)) {
                    foreach ($selected_motor as $jm) {
                        $jm = intval($jm);
                        if ($jm > 0) {
                            $cek_kat = mysqli_query($koneksi, "SELECT id FROM tbkategori_motor WHERE id=$jm AND (status='1' OR status IS NULL) LIMIT 1");
                            if ($cek_kat && mysqli_num_rows($cek_kat) > 0) {
                                if ($use_kategori_col_wo) {
                                    mysqli_query($koneksi, "INSERT IGNORE INTO tbworkorder_jenis_motor (kode_wo, kd_kategori_motor) VALUES ('".$key_post_safe."', $jm)");
                                } else {
                                    mysqli_query($koneksi, "INSERT IGNORE INTO tbworkorder_jenis_motor (kode_wo, kd_jenis_motor) VALUES ('".$key_post_safe."', $jm)");
                                }
                            }
                        }
                    }
                }

                $_SESSION['flash_success'] = 'Mapping berhasil disimpan untuk Work Order: ' . htmlspecialchars($key_post);
                header("Location: item-motor-mapping.php?type=wo&kd=" . urlencode($key_post));
                exit;
            }
        }
    }

    $_SESSION['flash_success'] = 'Gagal menyimpan mapping. Data tidak valid: ' . htmlspecialchars($key_post);
    header("Location: item-motor-mapping.php?type=" . urlencode($entity_post));
    exit;
}

// Load item detail if edit mode
$item = null;
if ($mode === 'edit') {
    if ($entity === 'item') {
        $qit = mysqli_query($koneksi, "SELECT noitem, namaitem, jenis FROM tblitem WHERE noitem='".$key_safe."'");
        if ($qit && mysqli_num_rows($qit) > 0) {
            $item = mysqli_fetch_assoc($qit);
        } else {
            $mode = 'list';
        }
    } else {
        $qwo = mysqli_query($koneksi, "SELECT kode_wo, nama_wo FROM tbworkorderheader WHERE kode_wo='".$key_safe."'");
        if ($qwo && mysqli_num_rows($qwo) > 0) {
            $item = mysqli_fetch_assoc($qwo);
        } else {
            $mode = 'list';
        }
    }
}

// Load mapped motors
$mapped_motor = array();
if ($mode === 'edit') {
    if ($entity === 'item') {
        $mmq = mysqli_query($koneksi, "SELECT kd_kategori_motor, kd_jenis_motor FROM tbitem_jenis_motor WHERE noitem='".$key_safe."'");
        if ($mmq) {
            while ($mm = mysqli_fetch_assoc($mmq)) {
                if ($use_kategori_col && !empty($mm['kd_kategori_motor'])) {
                    $mapped_motor[] = intval($mm['kd_kategori_motor']);
                } elseif (!empty($mm['kd_jenis_motor'])) {
                    $mapped_motor[] = intval($mm['kd_jenis_motor']);
                }
            }
        }
    } else {
        $mmq = mysqli_query($koneksi, "SELECT kd_kategori_motor, kd_jenis_motor FROM tbworkorder_jenis_motor WHERE kode_wo='".$key_safe."'");
        if ($mmq) {
            while ($mm = mysqli_fetch_assoc($mmq)) {
                if ($use_kategori_col_wo && !empty($mm['kd_kategori_motor'])) {
                    $mapped_motor[] = intval($mm['kd_kategori_motor']);
                } elseif (!empty($mm['kd_jenis_motor'])) {
                    $mapped_motor[] = intval($mm['kd_jenis_motor']);
                }
            }
        }
    }
    $mapped_motor = array_values(array_unique($mapped_motor));
}

$mapped_tipe = array();
if ($mode === 'edit' && $entity === 'item') {
    $mtq = mysqli_query($koneksi, "SELECT kode_tipe FROM tblitem_spart WHERE noitem='".$key_safe."'");
    if ($mtq) {
        while ($mm = mysqli_fetch_assoc($mtq)) {
            if (!empty($mm['kode_tipe'])) {
                $mapped_tipe[] = intval($mm['kode_tipe']);
            }
        }
    }
    $mapped_tipe = array_values(array_unique($mapped_tipe));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
    <div id="navbar" class="navbar navbar-default          ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <div class="navbar-header pull-left">
                <table>
                    <tr>
                        <td width="20%">
                            <a href="index.php" class="navbar-brand">
                                <small>
                                    <i class="fa fa-leaf"></i>
                                    <?php include "../lib/subtitel.php"; ?>
                                </small>
                            </a>
                        </td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo $_nama; ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>

                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li>
                                <a href="change_pwd.php">
                                    <i class="ace-icon fa fa-cog"></i>
                                    Change Password
                                </a>
                            </li>
                            <li>
                                <a href="profile.php">
                                    <i class="ace-icon fa fa-user"></i>
                                    Profile
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="logout.php">
                                    <i class="ace-icon fa fa-power-off"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-header pull-right">
                <a href="#" class="navbar-brand"><small></small></a>
            </div>
        </div><!-- /.navbar-container -->
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">
                try{ace.settings.loadState('sidebar')}catch(e){}
            </script>

            <?php include "menu_dashboard.php"; ?>

            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li><a href="#">Master Data</a></li>
                        <li class="active">Mapping Barang ke Kategori Motor</li>
                    </ul>
                </div>

                <div class="page-content">
                    <?php if (!empty($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mode === 'list'): ?>
                        <div class="page-header">
                            <h1>
                                Mapping
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    <?php echo ($entity === 'wo') ? 'Daftar Work Order' : 'Daftar Item'; ?>
                                </small>
                            </h1>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="btn-group">
                                    <a class="btn btn-sm <?php echo ($entity === 'item') ? 'btn-primary' : 'btn-default'; ?>" href="item-motor-mapping.php?type=item">Item</a>
                                    <a class="btn btn-sm <?php echo ($entity === 'wo') ? 'btn-primary' : 'btn-default'; ?>" href="item-motor-mapping.php?type=wo">Work Order</a>
                                </div>
                                <form method="post" style="display:inline-block; margin-left:10px;">
                                    <button type="submit" name="btn_cleanup_kategori" class="btn btn-warning btn-sm" onclick="return confirm('Lanjutkan cleanup duplikasi kategori motor?');">Cleanup Duplikasi Kategori</button>
                                </form>
                                <a href="tipe-motor-kategori.php" class="btn btn-info btn-sm" style="margin-left:10px;">Rapikan Kategori Tipe Motor</a>
                                <div class="space-8"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <form class="form-search" method="get" action="">
                                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($entity); ?>" />
                                    <span class="input-icon">
                                        <input type="text" placeholder="Cari kode/nama barang ..." class="nav-search-input" name="q" value="<?php echo htmlspecialchars($q); ?>" autocomplete="off" />
                                        <i class="ace-icon fa fa-search nav-search-icon"></i>
                                    </span>
                                    <?php if ($entity === 'item'): ?>
                                        <select name="jenis" class="form-control" style="display:inline-block; width:auto;">
                                            <option value="barang" <?php echo ($jenis_filter === 'barang') ? 'selected' : ''; ?>>Barang</option>
                                            <option value="jasa" <?php echo ($jenis_filter === 'jasa') ? 'selected' : ''; ?>>Jasa</option>
                                            <option value="semua" <?php echo ($jenis_filter === 'semua') ? 'selected' : ''; ?>>Semua</option>
                                        </select>
                                    <?php endif; ?>
                                    <input class="btn btn-purple btn-sm" type="submit" value="Cari" />
                                    <a href="item-motor-mapping.php?type=<?php echo urlencode($entity); ?>" class="btn btn-default btn-sm">Reset</a>
                                </form>
                                <div class="space-8"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="info">
                                            <?php if ($entity === 'wo'): ?>
                                                <th width="15%">Kode WO</th>
                                                <th>Nama WO</th>
                                                <th width="35%">Mapping Kategori</th>
                                                <th width="15%">Aksi</th>
                                            <?php else: ?>
                                                <th width="15%">Kode</th>
                                                <th>Nama Item</th>
                                                <th width="10%">Jenis</th>
                                                <th width="25%">Mapping Kategori</th>
                                                <th width="15%">Applicable Part</th>
                                                <th width="15%">Aksi</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($entity === 'wo') {
                                            if ($use_kategori_col_wo && $use_jenis_col_wo) {
                                                $sql_list = "SELECT wo.kode_wo, wo.nama_wo,
                                                    (SELECT GROUP_CONCAT(DISTINCT COALESCE(k.kategori, j.jenis) ORDER BY COALESCE(k.kategori, j.jenis) SEPARATOR ', ')
                                                        FROM tbworkorder_jenis_motor m
                                                        LEFT JOIN tbkategori_motor k ON k.id=m.kd_kategori_motor
                                                        LEFT JOIN tbjenis_motor j ON j.kd=m.kd_jenis_motor
                                                        WHERE m.kode_wo=wo.kode_wo) as map_kategori
                                                    FROM tbworkorderheader wo WHERE wo.status='0'";
                                            } elseif ($use_kategori_col_wo) {
                                                $sql_list = "SELECT wo.kode_wo, wo.nama_wo,
                                                    (SELECT GROUP_CONCAT(k.kategori ORDER BY k.kategori SEPARATOR ', ')
                                                        FROM tbworkorder_jenis_motor m
                                                        JOIN tbkategori_motor k ON k.id=m.kd_kategori_motor
                                                        WHERE m.kode_wo=wo.kode_wo) as map_kategori
                                                    FROM tbworkorderheader wo WHERE wo.status='0'";
                                            } else {
                                                $sql_list = "SELECT wo.kode_wo, wo.nama_wo,
                                                    (SELECT GROUP_CONCAT(j.jenis ORDER BY j.jenis SEPARATOR ', ')
                                                        FROM tbworkorder_jenis_motor m
                                                        JOIN tbjenis_motor j ON j.kd=m.kd_jenis_motor
                                                        WHERE m.kode_wo=wo.kode_wo) as map_kategori
                                                    FROM tbworkorderheader wo WHERE wo.status='0'";
                                            }
                                            if ($q !== '') {
                                                $sql_list .= " AND (wo.kode_wo LIKE '%".$q_safe."%' OR wo.nama_wo LIKE '%".$q_safe."%')";
                                            }
                                            $sql_list .= " ORDER BY wo.nama_wo ASC LIMIT 300";
                                            $res_list = mysqli_query($koneksi, $sql_list);
                                            while ($res_list && ($row = mysqli_fetch_assoc($res_list))) {
                                                echo '<tr>';
                                                echo '<td>'.htmlspecialchars($row['kode_wo']).'</td>';
                                                echo '<td>'.htmlspecialchars($row['nama_wo']).'</td>';
                                                echo '<td>'.htmlspecialchars($row['map_kategori'] ?? '-').'</td>';
                                                echo '<td class="center">'
                                                    .'<a class="btn btn-xs btn-primary" href="item-motor-mapping.php?type=wo&kd='.urlencode($row['kode_wo']).'">Mapping</a>'
                                                    .'</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            $sql_list = "SELECT i.noitem, i.namaitem, i.jenis,
                                                (SELECT COUNT(DISTINCT s.kode_tipe) FROM tblitem_spart s WHERE s.noitem=i.noitem) as tipe_cnt";
                                            if ($use_kategori_col && $use_jenis_col) {
                                                $sql_list .= ", (SELECT GROUP_CONCAT(DISTINCT COALESCE(k.kategori, j.jenis) ORDER BY COALESCE(k.kategori, j.jenis) SEPARATOR ', ')
                                                    FROM tbitem_jenis_motor m
                                                    LEFT JOIN tbkategori_motor k ON k.id=m.kd_kategori_motor
                                                    LEFT JOIN tbjenis_motor j ON j.kd=m.kd_jenis_motor
                                                    WHERE m.noitem=i.noitem) as map_kategori";
                                            } elseif ($use_kategori_col) {
                                                $sql_list .= ", (SELECT GROUP_CONCAT(k.kategori ORDER BY k.kategori SEPARATOR ', ')
                                                    FROM tbitem_jenis_motor m
                                                    JOIN tbkategori_motor k ON k.id=m.kd_kategori_motor
                                                    WHERE m.noitem=i.noitem) as map_kategori";
                                            } else {
                                                $sql_list .= ", (SELECT GROUP_CONCAT(j.jenis ORDER BY j.jenis SEPARATOR ', ')
                                                    FROM tbitem_jenis_motor m
                                                    JOIN tbjenis_motor j ON j.kd=m.kd_jenis_motor
                                                    WHERE m.noitem=i.noitem) as map_kategori";
                                            }
                                            $sql_list .= " FROM tblitem i WHERE 1=1";
                                            if ($jenis_filter === 'barang') {
                                                $sql_list .= " AND (i.jenis IS NULL OR i.jenis <> 'SERVIS')";
                                            } elseif ($jenis_filter === 'jasa') {
                                                $sql_list .= " AND i.jenis = 'SERVIS'";
                                            }
                                            if ($q !== '') {
                                                $sql_list .= " AND (i.noitem LIKE '%".$q_safe."%' OR i.namaitem LIKE '%".$q_safe."%')";
                                            }
                                            $sql_list .= " ORDER BY i.namaitem ASC LIMIT 300";
                                            $res_list = mysqli_query($koneksi, $sql_list);
                                            while ($res_list && ($row = mysqli_fetch_assoc($res_list))) {
                                                $jenis_row = ($row['jenis'] === 'SERVIS') ? 'JASA' : 'BARANG';
                                                $tipe_cnt = intval($row['tipe_cnt'] ?? 0);
                                                echo '<tr>';
                                                echo '<td>'.htmlspecialchars($row['noitem']).'</td>';
                                                echo '<td>'.htmlspecialchars($row['namaitem']).'</td>';
                                                echo '<td>'.htmlspecialchars($jenis_row).'</td>';
                                                echo '<td>'.htmlspecialchars($row['map_kategori'] ?? '-').'</td>';
                                                echo '<td class="center">'.($tipe_cnt > 0 ? ($tipe_cnt.' tipe') : '-').'</td>';
                                                echo '<td class="center">'
                                                    .'<a class="btn btn-xs btn-primary" href="item-motor-mapping.php?type=item&kd='.urlencode($row['noitem']).'">Mapping</a>'
                                                    .'</td>';
                                                echo '</tr>';
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="page-header">
                            <h1>
                                <?php if ($entity === 'wo'): ?>
                                    Mapping Kategori Motor untuk Work Order: <?php echo htmlspecialchars(($item['kode_wo'] ?? '').' - '.($item['nama_wo'] ?? '')); ?>
                                <?php else: ?>
                                    Mapping Kategori Motor untuk Item: <?php echo htmlspecialchars(($item['noitem'] ?? '').' - '.($item['namaitem'] ?? '')); ?>
                                <?php endif; ?>
                            </h1>
                        </div>
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="widget-box">
                                    <div class="widget-header"><h4 class="widget-title">Pilih Kategori Motor</h4></div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <form method="post">
                                                <input type="hidden" name="entity" value="<?php echo htmlspecialchars($entity); ?>" />
                                                <input type="hidden" name="key" value="<?php echo htmlspecialchars($entity === 'wo' ? ($item['kode_wo'] ?? '') : ($item['noitem'] ?? '')); ?>" />
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <?php
                                                        $qjm = mysqli_query($koneksi, "SELECT id, kategori FROM tbkategori_motor WHERE (status='1' OR status IS NULL) ORDER BY kategori");
                                                        if ($qjm && mysqli_num_rows($qjm) > 0) {
                                                            while ($jm = mysqli_fetch_assoc($qjm)) {
                                                                $kdj = intval($jm['id']);
                                                                $nmj = $jm['kategori'];
                                                                $checked = in_array($kdj, $mapped_motor) ? 'checked' : '';
                                                                echo '<label class="checkbox-inline" style="margin-right:15px;">'
                                                                    .'<input type="checkbox" name="kategori_motor[]" value="'.$kdj.'" '.$checked.'> '
                                                                    .htmlspecialchars($nmj)
                                                                    .'</label>';
                                                            }
                                                        } else {
                                                            echo '<em>Master kategori motor belum tersedia.</em>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <?php if ($entity === 'item'): ?>
                                                    <hr />
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <div class="widget-box" style="margin-bottom:0;">
                                                                <div class="widget-header"><h4 class="widget-title">Applicable Part - Tipe Motor yang Cocok</h4></div>
                                                                <div class="widget-body">
                                                                    <div class="widget-main">
                                                                        <input type="text" id="filterTipeMotor" class="form-control" placeholder="Cari tipe motor ..." />
                                                                        <div class="space-8"></div>
                                                                        <div style="max-height:260px; overflow:auto; border:1px solid #ddd; padding:10px;">
                                                                            <?php
                                                                            $qt = mysqli_query($koneksi, "SELECT t.kode_tipe, t.tipe, t.kode_kategori, k.kategori AS nama_kategori
                                                                                FROM tbtipe_motor t
                                                                                LEFT JOIN tbkategori_motor k ON k.id = t.kode_kategori
                                                                                WHERE (k.status='1' OR k.status IS NULL OR k.id IS NULL)
                                                                                ORDER BY k.kategori ASC, t.tipe ASC");
                                                                            if ($qt && mysqli_num_rows($qt) > 0) {
                                                                                $last_kat = null;
                                                                                while ($t = mysqli_fetch_assoc($qt)) {
                                                                                    $kt = intval($t['kode_tipe']);
                                                                                    $nm = $t['tipe'];
                                                                                    $kat = $t['nama_kategori'] ?? '';
                                                                                    $kat_norm = trim((string)$kat);
                                                                                    if ($kat_norm === '') {
                                                                                        $kat_norm = 'LAINNYA';
                                                                                    }
                                                                                    if ($last_kat !== $kat_norm) {
                                                                                        echo '<div class="clearfix" style="margin:10px 0 6px 0; padding:6px 8px; background:#f5f5f5; border:1px solid #e5e5e5;">'
                                                                                            .'<strong>'.htmlspecialchars($kat_norm).'</strong>'
                                                                                            .'</div>';
                                                                                        $last_kat = $kat_norm;
                                                                                    }
                                                                                    $checked = in_array($kt, $mapped_tipe) ? 'checked' : '';
                                                                                    $label = htmlspecialchars($nm);
                                                                                    echo '<label class="checkbox-inline tipe-motor-item" data-text="'.htmlspecialchars(strtolower($nm)).'" data-cat="'.htmlspecialchars(strtolower($kat_norm)).'" style="display:inline-block; margin-right:15px; margin-bottom:8px;">'
                                                                                        .'<input type="checkbox" name="tipe_motor[]" value="'.$kt.'" '.$checked.'> '
                                                                                        .$label
                                                                                        .'</label>';
                                                                                }
                                                                            } else {
                                                                                echo '<em>Master tipe motor belum tersedia.</em>';
                                                                            }
                                                                            ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="space-8"></div>
                                                <button type="submit" name="btnsave" class="btn btn-success"><i class="fa fa-save"></i> Simpan Mapping</button>
                                                <a href="item-motor-mapping.php?type=<?php echo urlencode($entity); ?>" class="btn btn-default">Kembali</a>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-inner">
            <div class="footer-content">
                <?php include "../lib/footer.php"; ?>
            </div>
        </div>
    </div>

    <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
        <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
    </a>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script>
        (function(){
            var inp = document.getElementById('filterTipeMotor');
            if(!inp) return;
            inp.addEventListener('input', function(){
                var q = (inp.value || '').toLowerCase();
                var items = document.querySelectorAll('.tipe-motor-item');
                for (var i=0;i<items.length;i++) {
                    var t = items[i].getAttribute('data-text') || '';
                    items[i].style.display = (q === '' || t.indexOf(q) !== -1) ? 'inline-block' : 'none';
                }
            });
        })();
    </script>
</body>
</html>
