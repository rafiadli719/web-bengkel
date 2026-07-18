<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
include "../config/koneksi.php";
include_once "../lib/rbac.php";

error_reporting(E_ALL);
ini_set('display_errors', 0);
if(!isset($koneksi) || !$koneksi){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die('DB connection failed: '.mysqli_connect_error());
}
@mysqli_set_charset($koneksi, 'utf8mb4');

$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari && isset($tm_cari['nama_user']) ? $tm_cari['nama_user'] : 'admin';
$lvl_akses = $tm_cari && isset($tm_cari['user_akses']) ? $tm_cari['user_akses'] : '';
$foto_user = $tm_cari && isset($tm_cari['foto_user']) ? $tm_cari['foto_user'] : '';
if($foto_user=='') { $foto_user="file_upload/avatar.png"; }
$is_admin = ($lvl_akses == '1');

$can_detect = $is_admin;
if(function_exists('rbac_has')){
    $can_detect = $can_detect || rbac_has('issue_approve_supervisor') || rbac_has('issue_merge_customer_approve');
}

if(!$can_detect){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(403);
    die('Halaman ini khusus user yang memiliki hak kelola data pelanggan.');
}

$message = '';
$error_class = 'alert-info';

// Helper pembanding kemiripan nama untuk memfilter false positives
function are_names_similar($name1, $name2) {
    $name1 = strtoupper(trim($name1));
    $name2 = strtoupper(trim($name2));
    
    // Hapus gelar, sebutan umum, dan nama badan usaha
    $patterns = [
        '/\b(BPK|IBU|MAS|MBA|H|HAJI|NY|TN|SE|CV|PT|PD|UD|TOKO|BENGKEL)\b/i',
        '/[^A-Z0-9]/i' // Hapus simbol non-alfanumerik
    ];
    
    $clean1 = preg_replace($patterns[0], '', $name1);
    $clean2 = preg_replace($patterns[0], '', $name2);
    
    $clean1 = trim(preg_replace($patterns[1], '', $clean1));
    $clean2 = trim(preg_replace($patterns[1], '', $clean2));
    
    if(empty($clean1) || empty($clean2)) return false;
    
    // 1. Cocok persis setelah dibersihkan
    if ($clean1 === $clean2) return true;
    
    // 2. Salah satu nama merupakan bagian/substring dari nama lain (misal "BUDI" dan "BUDI SANTOSO")
    if (strlen($clean1) > 3 && strlen($clean2) > 3) {
        if (strpos($clean1, $clean2) !== false || strpos($clean2, $clean1) !== false) {
            return true;
        }
    }
    
    // 3. Persentase kecocokan karakter (similar_text) >= 70%
    similar_text($clean1, $clean2, $percent);
    if ($percent >= 70) return true;
    
    // 4. Ejaan suara (Soundex) cocok
    if (soundex($clean1) === soundex($clean2)) return true;
    
    return false;
}

// Eksekusi pengajuan merge (proaktif)
if(isset($_POST['btnsubmit_merge'])){
    $source = trim((string)($_POST['source'] ?? ''));
    $target = trim((string)($_POST['target'] ?? ''));
    $alasan = trim((string)($_POST['alasan'] ?? ''));
    $confirm_target = trim((string)($_POST['confirm_target'] ?? ''));

    if($source === '' || $target === '' || $alasan === ''){
        $message = 'Mohon lengkapi seluruh field pengajuan merge.'; $error_class = 'alert-danger';
    } elseif($source === $target){
        $message = 'Kode pelanggan sumber dan target tidak boleh sama.'; $error_class = 'alert-danger';
    } elseif($confirm_target !== $target){
        $message = 'Konfirmasi target tidak cocok. Ketik ulang dengan benar.'; $error_class = 'alert-danger';
    } else {
        $source_esc = mysqli_real_escape_string($koneksi, $source);
        $target_esc = mysqli_real_escape_string($koneksi, $target);
        
        // Validasi keberadaan pelanggan
        $q_source = mysqli_query($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan='{$source_esc}'");
        $q_target = mysqli_query($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan='{$target_esc}'");
        
        if(mysqli_num_rows($q_source) == 0){
            $message = "Pelanggan Sumber ({$source}) tidak ditemukan."; $error_class = 'alert-danger';
        } elseif(mysqli_num_rows($q_target) == 0){
            $message = "Pelanggan Target ({$target}) tidak ditemukan."; $error_class = 'alert-danger';
        } else {
            // Check status pending log biar gak tabrakan
            $q_chk = mysqli_query($koneksi, "SELECT id FROM customer_merge_log WHERE nopelanggan_source='{$source_esc}' AND status='diajukan'");
            if(mysqli_num_rows($q_chk) > 0){
                $message = "Pelanggan Sumber ({$source}) sudah dalam antrian merge. Batalkan atau selesaikan antrian lama."; $error_class = 'alert-danger';
            } else {
                // Auto insert ke customer_merge_log dengan status 'diajukan'
                $stmt = mysqli_prepare($koneksi, "INSERT INTO customer_merge_log (nopelanggan_source, nopelanggan_target, alasan, dibuat_oleh, status) VALUES (?, ?, ?, ?, 'diajukan')");
                mysqli_stmt_bind_param($stmt, "sssi", $source, $target, $alasan, $id_user);
                if(mysqli_stmt_execute($stmt)){
                    $new_log_id = mysqli_insert_id($koneksi);
                    $message = "Pengajuan merge berhasil dibuat. Silakan arahkan supervisor untuk setujui merge ID: #{$new_log_id} di menu Approve Merge.";
                    $error_class = 'alert-success';
                } else {
                    $message = "Gagal membuat pengajuan merge: " . mysqli_error($koneksi); $error_class = 'alert-danger';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Algoritma Deteksi Duplikat
$tipe_deteksi = isset($_GET['tipe']) ? trim($_GET['tipe']) : 'wa';
$list_duplikat = [];

if($tipe_deteksi === 'wa'){
    // Deteksi berdasarkan No WA sama persis (kecuali kosong/strip)
    $q = mysqli_query($koneksi, "
        SELECT no_wa, COUNT(*) as jumlah, GROUP_CONCAT(nopelanggan ORDER BY nopelanggan ASC SEPARATOR ', ') as pelanggan_list, GROUP_CONCAT(namapelanggan ORDER BY nopelanggan ASC SEPARATOR ' || ') as nama_list
        FROM tblpelanggan
        WHERE no_wa IS NOT NULL AND no_wa NOT IN ('', '-', '0', '08123456789')
        GROUP BY no_wa
        HAVING jumlah > 1
        LIMIT 200
    ");
    if($q){
        while($r = mysqli_fetch_assoc($q)){
            $list_duplikat[] = [
                'kunci' => 'No. WA: ' . $r['no_wa'],
                'jumlah' => $r['jumlah'],
                'list_nopel' => explode(', ', $r['pelanggan_list']),
                'list_nama' => explode(' || ', $r['nama_list']),
            ];
        }
    }
} elseif($tipe_deteksi === 'nopol'){
    // Deteksi berdasarkan Nopol kendaraan sama tapi Nopelanggan beda pada riwayat transaksi
    // Ditambahkan filter kemiripan nama via PHP agar hanya menampilkan pelanggan yang berpotensi sama (bukan transaksi pindah kepemilikan / jual beli kendaraan)
    $q = mysqli_query($koneksi, "
        SELECT s.no_polisi, COUNT(DISTINCT s.no_pelanggan) as jumlah, GROUP_CONCAT(DISTINCT p.nopelanggan ORDER BY p.nopelanggan ASC SEPARATOR ', ') as pelanggan_list, GROUP_CONCAT(DISTINCT p.namapelanggan ORDER BY p.nopelanggan ASC SEPARATOR ' || ') as nama_list
        FROM tblservice s
        JOIN tblpelanggan p ON p.nopelanggan=s.no_pelanggan
        WHERE s.no_polisi IS NOT NULL AND s.no_polisi NOT IN ('', '-')
        GROUP BY s.no_polisi
        HAVING jumlah > 1
        LIMIT 500
    ");
    if($q){
        while($r = mysqli_fetch_assoc($q)){
            $nopels = explode(', ', $r['pelanggan_list']);
            $namas = explode(' || ', $r['nama_list']);
            
            $has_similar = false;
            $n = count($namas);
            for($i = 0; $i < $n; $i++){
                for($j = $i + 1; $j < $n; $j++){
                    if(are_names_similar($namas[$i], $namas[$j])){
                        $has_similar = true;
                        break 2;
                    }
                }
            }
            
            if($has_similar){
                $list_duplikat[] = [
                    'kunci' => 'Nopol: ' . $r['no_polisi'],
                    'jumlah' => $r['jumlah'],
                    'list_nopel' => $nopels,
                    'list_nama' => $namas,
                ];
            }
        }
    }
} elseif($tipe_deteksi === 'nama_soundex'){
    // Deteksi berdasarkan kesamaan Soundex nama (kemiripan ejaan suara)
    $q = mysqli_query($koneksi, "
        SELECT SOUNDEX(namapelanggan) as sd, COUNT(*) as jumlah, GROUP_CONCAT(nopelanggan ORDER BY nopelanggan ASC SEPARATOR ', ') as pelanggan_list, GROUP_CONCAT(namapelanggan ORDER BY nopelanggan ASC SEPARATOR ' || ') as nama_list
        FROM tblpelanggan
        WHERE namapelanggan IS NOT NULL AND namapelanggan NOT IN ('', '-')
        GROUP BY sd
        HAVING jumlah > 1 AND LENGTH(sd) > 2
        ORDER BY jumlah DESC
        LIMIT 100
    ");
    if($q){
        while($r = mysqli_fetch_assoc($q)){
            $list_duplikat[] = [
                'kunci' => 'Bunyi Ejaan (Soundex): ' . $r['sd'],
                'jumlah' => $r['jumlah'],
                'list_nopel' => explode(', ', $r['pelanggan_list']),
                'list_nama' => explode(' || ', $r['nama_list']),
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Deteksi Duplikat Pelanggan - <?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
        .ui-autocomplete {
            z-index: 2000 !important;
            max-height: 250px;
            overflow-y: auto;
            overflow-x: hidden;
        }
    </style>
</head>
<body class="no-skin">
<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <div class="navbar-header pull-left">
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> FIT MOTOR</small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                        <span class="user-info"><small>Welcome,</small><?php echo htmlspecialchars($_nama); ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <?php include "menu_dashboard.php"; ?>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li class="active">Deteksi Duplikat Pelanggan</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Deteksi & Gabung Pelanggan Ganda <small><i class="ace-icon fa fa-angle-double-right"></i> Manajemen Kualitas Data Pelanggan</small></h1>
                </div>
                
                <?php if($message != ''){ echo '<div class="alert '.$error_class.'">'.htmlspecialchars($message).'</div>'; } ?>

                <div class="row">
                    <div class="col-xs-12 col-md-7">
                        <div class="tabbable">
                            <ul class="nav nav-tabs" id="myTab">
                                <li class="<?php echo $tipe_deteksi=='wa'?'active':''; ?>"><a href="admin_deteksi_pelanggan_dobel.php?tipe=wa"><i class="green ace-icon fa fa-whatsapp bigger-120"></i> No. WA Ganda</a></li>
                                <li class="<?php echo $tipe_deteksi=='nopol'?'active':''; ?>"><a href="admin_deteksi_pelanggan_dobel.php?tipe=nopol"><i class="red ace-icon fa fa-motorcycle bigger-120"></i> Nopol Kendaraan Ganda</a></li>
                                <li class="<?php echo $tipe_deteksi=='nama_soundex'?'active':''; ?>"><a href="admin_deteksi_pelanggan_dobel.php?tipe=nama_soundex"><i class="blue ace-icon fa fa-text-width bigger-120"></i> Kemiripan Suara Ejaan (Soundex)</a></li>
                            </ul>
                            
                            <div class="tab-content">
                                <div class="tab-pane fade in active">
                                    <div class="alert alert-info" style="margin-bottom: 10px;">
                                        Menampilkan maksimal 100 kelompok data pelanggan yang terdeteksi ganda berdasarkan filter tab di atas.
                                    </div>
                                    
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Kunci Kemiripan</th>
                                                <th>Jumlah Data</th>
                                                <th>Kandidat Ganda (Kode & Nama)</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($list_duplikat) > 0){ foreach($list_duplikat as $item){ ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($item['kunci']); ?></strong></td>
                                                    <td><span class="badge badge-warning"><?php echo $item['jumlah']; ?></span></td>
                                                    <td>
                                                        <ul style="margin:0; padding-left:15px;">
                                                            <?php for($i=0; $i<count($item['list_nopel']); $i++){
                                                                $np = $item['list_nopel'][$i] ?? '';
                                                                $nm = $item['list_nama'][$i] ?? '';
                                                                echo '<li><code>'.htmlspecialchars($np).'</code> - '.htmlspecialchars($nm).'</li>';
                                                            } ?>
                                                        </ul>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-xs btn-primary" onclick="pilihMerge('<?php echo htmlspecialchars($item['list_nopel'][0] ?? ''); ?>', '<?php echo htmlspecialchars($item['list_nopel'][1] ?? ''); ?>')"><i class="fa fa-compress"></i> Pilih Merge</button>
                                                    </td>
                                                </tr>
                                            <?php } } else { ?>
                                                <tr><td colspan="4" class="text-center" style="padding: 20px;">Tidak ditemukan duplikasi data pelanggan pada filter ini.</td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-md-5">
                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title"><i class="ace-icon fa fa-compress"></i> Formulir Pengajuan Merge Pelanggan</h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <form method="post" action="admin_deteksi_pelanggan_dobel.php" onsubmit="return confirm('Apakah Anda yakin data ini sudah benar? Pengajuan merge memerlukan persetujuan supervisor.');">
                                        <div class="form-group">
                                            <label for="source"><strong>Kode Pelanggan Sumber (Akan Dilebur / Dihapus)</strong></label>
                                            <input type="text" id="source" name="source" class="form-control autocomplete-pelanggan" placeholder="Ketik nama/kode pelanggan..." required />
                                            <small class="text-muted">Seluruh transaksi dari pelanggan ini akan dialihkan ke pelanggan target.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="target"><strong>Kode Pelanggan Target (Master / Dipertahankan)</strong></label>
                                            <input type="text" id="target" name="target" class="form-control autocomplete-pelanggan" placeholder="Ketik nama/kode pelanggan..." required />
                                            <small class="text-muted">Akun ini yang akan dipertahankan dalam database.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="confirm_target"><strong>Konfirmasi Kode Target</strong></label>
                                            <input type="text" id="confirm_target" name="confirm_target" class="form-control" placeholder="Terisi otomatis dari kode target" readonly required />
                                            <small class="text-muted">Field ini dikunci otomatis agar user tidak perlu ketik ulang. Nilainya selalu mengikuti Kode Pelanggan Target.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="alasan"><strong>Alasan Merge</strong></label>
                                            <textarea id="alasan" name="alasan" class="form-control" rows="3" placeholder="Contoh: Nama dan No WA sama persis, salah input saat pendaftaran." required></textarea>
                                        </div>
                                        
                                        <div class="form-actions center" style="margin-bottom:0; background:none; border-top:none;">
                                            <button type="submit" name="btnsubmit_merge" class="btn btn-sm btn-success"><i class="ace-icon fa fa-check bigger-110"></i> Ajukan Merge Pelanggan</button>
                                        </div>
                                    </form>
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
<script src="assets/js/jquery-ui.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>

<script>
function pilihMerge(source, target) {
    document.getElementById('source').value = source;
    document.getElementById('target').value = target;
    document.getElementById('confirm_target').value = target;
    document.getElementById('alasan').value = 'Nama / kontak sama persis pada modul deteksi otomatis.';
    document.getElementById('alasan').focus();
}

function syncConfirmTarget() {
    var targetInput = document.getElementById('target');
    var confirmInput = document.getElementById('confirm_target');
    if (!targetInput || !confirmInput) {
        return;
    }
    confirmInput.value = targetInput.value;
}

$(document).ready(function() {
    $(".autocomplete-pelanggan").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "ajax-autocomplete.php",
                dataType: "json",
                data: {
                    source: "pelanggan",
                    q: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $(this).val(ui.item.value);
            if ($(this).attr('id') === 'target') {
                syncConfirmTarget();
            }
            return false;
        }
    });

    $('#target').on('input change blur', syncConfirmTarget);
    syncConfirmTarget();
});
</script>
</body>
</html>
