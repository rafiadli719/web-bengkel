<?php
session_start();
if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
$id_user = $_SESSION['_iduser'];
include "../config/koneksi.php";

// Load user profile (navbar)
$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari['nama_user'] ?? '';
$foto_user = $tm_cari['foto_user'] ?? '';
if($foto_user=='') { $foto_user = "file_upload/avatar.png"; }
if(!isset($_SESSION['username'])) { $_SESSION['username'] = $_nama; }

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$alert = '';
$alert_type = 'info';
$mode = 'create_multi';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing_row = null;
if ($edit_id > 0) {
    $mode = 'update_single';
    $rs = mysqli_query($koneksi, "SELECT * FROM tbmaster_temuan_jasa_mapping WHERE id='$edit_id' LIMIT 1");
    if ($rs && mysqli_num_rows($rs) > 0) { $editing_row = mysqli_fetch_assoc($rs); }
}

// AJAX search jasa endpoint (only jenis=SERVIS)
if (isset($_GET['action']) && $_GET['action']==='search_jasa') {
    header('Content-Type: application/json');
    $q = mysqli_real_escape_string($koneksi, trim($_GET['q'] ?? ''));
    $sql = "SELECT noitem, namaitem, satuan, hargajual FROM tblitem
            WHERE (noitem LIKE '%$q%' OR namaitem LIKE '%$q%')
            AND jenis = 'SERVIS'
            ORDER BY namaitem LIMIT 50";
    $res = mysqli_query($koneksi, $sql);
    $rows = [];
    if ($res) { while($r = mysqli_fetch_assoc($res)) { $rows[] = $r; } }
    echo json_encode($rows);
    exit;
}

// Handle submit
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $post_mode = $_POST['mode'] ?? '';
    if ($post_mode === 'create_multi') {
        $kode_temuan = mysqli_real_escape_string($koneksi, trim($_POST['kode_temuan'] ?? ''));
        $noitems = $_POST['noitem'] ?? [];
        $is_primary = $_POST['is_primary'] ?? [];
        $prioritas = $_POST['prioritas'] ?? [];
        $waktu_estimasi = $_POST['waktu_estimasi'] ?? [];
        $status_aktif = $_POST['status_aktif'] ?? [];
        $keterangan = $_POST['keterangan'] ?? [];

        if ($kode_temuan === '' || empty($noitems)) {
            $alert = 'Pilih temuan dan minimal 1 jasa.';
            $alert_type = 'warning';
        } else {
            $ok=0;$skip=0;$err=0;
            foreach($noitems as $i=>$ni) {
                $ni = mysqli_real_escape_string($koneksi, trim($ni));
                if ($ni === '') { $skip++; continue; }
                $ip = isset($is_primary[$i]) ? 1 : 0;
                $pr = max(1, intval($prioritas[$i] ?? 1));
                $we = max(0, intval($waktu_estimasi[$i] ?? 0));
                $sa = isset($status_aktif[$i]) ? 1 : 0;
                $ket = mysqli_real_escape_string($koneksi, trim($keterangan[$i] ?? ''));

                $cek = mysqli_query($koneksi, "SELECT id FROM tbmaster_temuan_jasa_mapping WHERE kode_temuan='$kode_temuan' AND noitem='$ni'");
                if ($cek && mysqli_num_rows($cek) > 0) {
                    $r = mysqli_fetch_assoc($cek); $mid = intval($r['id']);
                    $sqlu = "UPDATE tbmaster_temuan_jasa_mapping SET is_primary='$ip', prioritas='$pr', waktu_estimasi='$we', status_aktif='$sa', keterangan='$ket', updated_by='$id_user', updated_at=NOW() WHERE id='$mid'";
                    if (mysqli_query($koneksi, $sqlu)) { $ok++; } else { $err++; }
                } else {
                    $sqli = "INSERT INTO tbmaster_temuan_jasa_mapping (kode_temuan,noitem,is_primary,prioritas,waktu_estimasi,keterangan,status_aktif,created_by,created_at) VALUES ('$kode_temuan','$ni','$ip','$pr','$we','$ket','$sa','$id_user',NOW())";
                    if (mysqli_query($koneksi, $sqli)) { $ok++; } else { $err++; }
                }
            }
            $alert = "Selesai simpan: OK=$ok, Skip=$skip, Error=$err";
            $alert_type = 'success';
        }
    }
    elseif ($post_mode === 'update_single') {
        $id = intval($_POST['id'] ?? 0);
        $kode_temuan = mysqli_real_escape_string($koneksi, trim($_POST['kode_temuan'] ?? ''));
        $noitem = mysqli_real_escape_string($koneksi, trim($_POST['noitem_single'] ?? ''));
        $ip = isset($_POST['is_primary_single']) ? 1 : 0;
        $pr = max(1, intval($_POST['prioritas_single'] ?? 1));
        $we = max(0, intval($_POST['waktu_estimasi_single'] ?? 0));
        $sa = intval($_POST['status_aktif_single'] ?? 1) ? 1 : 0;
        $ket = mysqli_real_escape_string($koneksi, trim($_POST['keterangan_single'] ?? ''));

        if ($id>0 && $kode_temuan!=='' && $noitem!=='') {
            $sql = "UPDATE tbmaster_temuan_jasa_mapping SET kode_temuan='$kode_temuan', noitem='$noitem', is_primary='$ip', prioritas='$pr', waktu_estimasi='$we', status_aktif='$sa', keterangan='$ket', updated_by='$id_user', updated_at=NOW() WHERE id='$id'";
            if (mysqli_query($koneksi, $sql)) {
                $alert = 'Data berhasil diupdate';
                $alert_type = 'success';
            } else {
                $alert = 'Gagal update: '.mysqli_error($koneksi);
                $alert_type = 'danger';
            }
        } else {
            $alert = 'Lengkapi data';
            $alert_type = 'warning';
        }
    }
}

// Data temuan
$temuan_rs = mysqli_query($koneksi, "SELECT kode_temuan, nama_temuan FROM tbmaster_temuan WHERE is_active=1 ORDER BY kode_temuan");
if (!$temuan_rs) {
    $temuan_rs = mysqli_query($koneksi, "SELECT kode_keluhan AS kode_temuan, nama_keluhan AS nama_temuan FROM tbmaster_keluhan WHERE status_aktif='1' ORDER BY kode_keluhan");
} else {
    if (mysqli_num_rows($temuan_rs) === 0) {
        $__fb = mysqli_query($koneksi, "SELECT kode_keluhan AS kode_temuan, nama_keluhan AS nama_temuan FROM tbmaster_keluhan WHERE status_aktif='1' ORDER BY kode_keluhan");
        if ($__fb) { $temuan_rs = $__fb; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - <?php echo ($mode==='update_single')? 'Ubah' : 'Tambah'; ?> Mapping Temuan - Jasa</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
    .table thead th {background:#f5f5f5;}
    .relative { position: relative; }
    .suggest-item { cursor:pointer; }
    .widget-box.widget-color-green2 > .widget-header { background: #2e8b57; border-color: #2e8b57; }
    </style>
</head>
<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small></a>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info"><small>Welcome,</small><?php echo esc($_nama); ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_servis01.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="master-temuan-mapping-jasa.php">Master Mapping Jasa</a></li>
                        <li class="active"><?php echo ($mode==='update_single')? 'Ubah' : 'Tambah'; ?> Mapping</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1><?php echo ($mode==='update_single')? 'Ubah' : 'Tambah'; ?> Mapping Temuan - Jasa
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Mapping temuan ke item jasa/servis</small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <?php if($alert!==''){ echo '<div class="alert alert-'.$alert_type.'">'.esc($alert).'</div>'; } ?>

                            <?php if($mode==='update_single'){ ?>
                            <!-- MODE: UPDATE SINGLE -->
                            <div class="widget-box widget-color-green2">
                                <div class="widget-header"><h5 class="widget-title"><i class="fa fa-edit"></i> Ubah Mapping</h5></div>
                                <div class="widget-body"><div class="widget-main">
                                    <form class="form" method="post">
                                        <input type="hidden" name="mode" value="update_single">
                                        <input type="hidden" name="id" value="<?php echo intval($editing_row['id'] ?? 0); ?>">

                                        <div class="form-group">
                                            <label>Kode Temuan <span class="text-danger">*</span></label>
                                            <select name="kode_temuan" class="form-control" required>
                                                <option value="">-- Pilih Temuan --</option>
                                                <?php if($temuan_rs){ mysqli_data_seek($temuan_rs, 0); while($t = mysqli_fetch_assoc($temuan_rs)){ $sel = ($editing_row && $editing_row['kode_temuan']===$t['kode_temuan'])? 'selected' : ''; ?>
                                                    <option value="<?php echo esc($t['kode_temuan']); ?>" <?php echo $sel; ?>><?php echo esc($t['kode_temuan'].' - '.$t['nama_temuan']); ?></option>
                                                <?php } } ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Kode Jasa <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" name="noitem_single" id="noitem_single" class="form-control" value="<?php echo esc($editing_row['noitem'] ?? ''); ?>" placeholder="noitem di tblitem (jenis=SERVIS)" required>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-info" type="button" data-toggle="modal" data-target="#modalCariJasa"><i class="fa fa-search"></i> Cari</button>
                                                </span>
                                            </div>
                                            <small id="nama_jasa_display" class="text-muted"></small>
                                        </div>

                                        <div class="form-group">
                                            <label><input type="checkbox" name="is_primary_single" <?php echo ($editing_row && $editing_row['is_primary'])? 'checked' : ''; ?>> Primary (Jasa Utama/Rekomendasi)</label>
                                        </div>
                                        <div class="form-group">
                                            <label>Prioritas</label>
                                            <input type="number" name="prioritas_single" class="form-control" style="width:120px" value="<?php echo esc($editing_row['prioritas'] ?? 1); ?>" min="1">
                                            <small class="text-muted">1 = tertinggi</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Waktu Estimasi (menit)</label>
                                            <input type="number" name="waktu_estimasi_single" class="form-control" style="width:150px" value="<?php echo esc($editing_row['waktu_estimasi'] ?? 0); ?>" min="0">
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status_aktif_single" class="form-control" style="width:160px">
                                                <option value="1" <?php echo ($editing_row && $editing_row['status_aktif']==1)?'selected':''; ?>>Aktif</option>
                                                <option value="0" <?php echo ($editing_row && $editing_row['status_aktif']==0)?'selected':''; ?>>Nonaktif</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Keterangan</label>
                                            <input type="text" name="keterangan_single" class="form-control" value="<?php echo esc($editing_row['keterangan'] ?? ''); ?>" placeholder="Opsional">
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan</button>
                                            <a href="master-temuan-mapping-jasa.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
                                        </div>
                                    </form>
                                </div></div>
                            </div>
                            <?php } else { ?>

                            <!-- MODE: CREATE MULTI -->
                            <div class="widget-box widget-color-green2">
                                <div class="widget-header"><h5 class="widget-title"><i class="fa fa-plus"></i> Tambah Mapping (Multi Jasa)</h5></div>
                                <div class="widget-body"><div class="widget-main">
                                    <form class="form" method="post" id="formMulti">
                                        <input type="hidden" name="mode" value="create_multi">

                                        <div class="form-group">
                                            <label>Kode Temuan <span class="text-danger">*</span></label>
                                            <select name="kode_temuan" id="kode_temuan" class="form-control" required style="max-width: 500px;">
                                                <option value="">-- Pilih Temuan --</option>
                                                <?php if($temuan_rs){ while($t = mysqli_fetch_assoc($temuan_rs)){ ?>
                                                    <option value="<?php echo esc($t['kode_temuan']); ?>"><?php echo esc($t['kode_temuan'].' - '.$t['nama_temuan']); ?></option>
                                                <?php } } ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <button class="btn btn-info" type="button" data-toggle="modal" data-target="#modalCariJasa"><i class="fa fa-search"></i> Cari Kode Jasa</button>
                                            <small class="text-muted" style="margin-left: 10px;">Hanya item dengan jenis = SERVIS yang akan ditampilkan</small>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="tblDraft">
                                                <thead>
                                                    <tr>
                                                        <th style="width:15%">Kode Jasa</th>
                                                        <th>Nama Jasa</th>
                                                        <th style="width:100px" class="text-right">Harga</th>
                                                        <th style="width:8%" class="text-center">Primary</th>
                                                        <th style="width:10%">Prioritas</th>
                                                        <th style="width:10%">Waktu (mnt)</th>
                                                        <th style="width:8%">Status</th>
                                                        <th style="width:15%">Keterangan</th>
                                                        <th style="width:6%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="text-muted" id="rowEmpty"><td colspan="9" class="text-center">Belum ada jasa dipilih. Klik tombol "Cari Kode Jasa" untuk menambahkan.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan Semua</button>
                                            <a href="master-temuan-mapping-jasa.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
                                        </div>
                                    </form>
                                </div></div>
                            </div>

                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cari Jasa -->
    <div class="modal fade" id="modalCariJasa" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><i class="fa fa-search"></i> Cari Kode Jasa</h4>
          </div>
          <div class="modal-body">
            <div class="input-group" style="margin-bottom:10px;">
              <input type="text" id="qJasa" class="form-control" placeholder="Ketik kode/nama jasa ...">
              <span class="input-group-btn"><button class="btn btn-primary" id="btnCariJasa" type="button"><i class="fa fa-search"></i> Cari</button></span>
            </div>
            <p class="text-muted"><small><i class="fa fa-info-circle"></i> Hanya menampilkan item dengan jenis = SERVIS</small></p>
            <div class="table-responsive" style="max-height:420px; overflow:auto;">
              <table class="table table-striped table-hover" id="tblHasilJasa">
                <thead><tr><th>Kode</th><th>Nama Jasa</th><th>Satuan</th><th class="text-right">Harga</th><th style="width:80px">Aksi</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script>
    function formatRupiah(num) {
        return 'Rp ' + parseInt(num || 0).toLocaleString('id-ID');
    }

    function addRowDraft(item){
        var $tb = $('#tblDraft tbody');
        $('#rowEmpty').remove();

        // Check if already added
        var exists = false;
        $tb.find('input[name^="noitem"]').each(function(){
            if($(this).val() === item.noitem) { exists = true; }
        });
        if(exists) {
            alert('Jasa "' + item.noitem + '" sudah ada dalam daftar.');
            return;
        }

        var idx = $tb.find('tr').length;
        var tr = '<tr>'+
            '<td><input type="hidden" name="noitem['+idx+']" value="'+item.noitem+'"/><code>'+item.noitem+'</code></td>'+
            '<td>'+(item.namaitem||'')+'</td>'+
            '<td class="text-right">'+formatRupiah(item.hargajual)+'</td>'+
            '<td class="text-center"><input type="checkbox" name="is_primary['+idx+']" value="1"></td>'+
            '<td><input type="number" class="form-control input-sm" name="prioritas['+idx+']" value="1" min="1" style="width:60px;"></td>'+
            '<td><input type="number" class="form-control input-sm" name="waktu_estimasi['+idx+']" value="0" min="0" style="width:70px;"></td>'+
            '<td><label><input type="checkbox" name="status_aktif['+idx+']" value="1" checked> Aktif</label></td>'+
            '<td><input type="text" class="form-control input-sm" name="keterangan['+idx+']" placeholder="Ket."></td>'+
            '<td><button type="button" class="btn btn-xs btn-danger btnHapus"><i class="fa fa-trash"></i></button></td>'+
            '</tr>';
        $tb.append(tr);
    }

    $(function(){
        // Search in modal
        function doSearch(){
            var q = $('#qJasa').val().trim(); if(!q){ $('#tblHasilJasa tbody').html(''); return; }
            $('#btnCariJasa').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mencari...');
            $.getJSON('master-temuan-mapping-jasa-add.php', {action:'search_jasa', q:q}, function(rows){
                var html='';
                if(rows && rows.length){
                    rows.forEach(function(it){
                        html += '<tr>'+
                                '<td><code>'+it.noitem+'</code></td>'+
                                '<td>'+(it.namaitem||'')+'</td>'+
                                '<td>'+(it.satuan||'')+'</td>'+
                                '<td class="text-right">'+formatRupiah(it.hargajual)+'</td>'+
                                '<td><button type="button" class="btn btn-xs btn-success btnPilihJasa" data-noitem="'+it.noitem+'" data-nama="'+(it.namaitem||'')+'" data-harga="'+(it.hargajual||0)+'"><i class="fa fa-plus"></i> Pilih</button></td>'+
                                '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center text-muted">Tidak ditemukan item jasa dengan keyword tersebut</td></tr>';
                }
                $('#tblHasilJasa tbody').html(html);
            }).always(function(){ $('#btnCariJasa').prop('disabled', false).html('<i class="fa fa-search"></i> Cari'); });
        }
        $('#btnCariJasa').on('click', doSearch);
        $('#qJasa').on('keypress', function(e){ if(e.which==13){ doSearch(); return false;} });

        // Choose jasa from modal
        $('#tblHasilJasa').on('click', '.btnPilihJasa', function(){
            var item = {
                noitem: $(this).data('noitem'),
                namaitem: $(this).data('nama'),
                hargajual: $(this).data('harga')
            };
            <?php if($mode==='update_single'){ ?>
                $('#noitem_single').val(item.noitem);
                $('#nama_jasa_display').text('Nama: ' + item.namaitem + ' | Harga: ' + formatRupiah(item.hargajual));
                $('#modalCariJasa').modal('hide');
            <?php } else { ?>
                addRowDraft(item);
            <?php } ?>
        });

        // Remove draft row
        $('#tblDraft').on('click', '.btnHapus', function(){
            $(this).closest('tr').remove();
            // Show empty row if no data
            if($('#tblDraft tbody tr').length === 0) {
                $('#tblDraft tbody').html('<tr class="text-muted" id="rowEmpty"><td colspan="9" class="text-center">Belum ada jasa dipilih. Klik tombol "Cari Kode Jasa" untuk menambahkan.</td></tr>');
            }
        });

        // Auto-focus search on modal open
        $('#modalCariJasa').on('shown.bs.modal', function(){
            $('#qJasa').focus();
        });
    });
    </script>
</body>
</html>
