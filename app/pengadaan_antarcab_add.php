<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd = mysqli_query($koneksi,"SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari  = mysqli_fetch_array($cari_kd);
$_nama     = $tm_cari['nama_user'];
$foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

$kd_safe = mysqli_real_escape_string($koneksi, $kd_cabang);
$cari_cab = mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_safe'");
$tm_cab   = mysqli_fetch_array($cari_cab);
$nama_cabang = $tm_cab['nama_cabang'];
$tipe_cabang = $tm_cab['tipe_cabang'];
$is_pusat    = ($tipe_cabang=='1' || strtolower($tipe_cabang)=='pusat');

if($is_pusat){ header("location:pengadaan_antarcab.php"); exit; }

$error_msg = '';

if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['btnsimpan'])){
    $kd_tujuan  = mysqli_real_escape_string($koneksi, $_POST['kd_tujuan'] ?? '');
    $tgl_req    = mysqli_real_escape_string($koneksi, $_POST['tgl_request'] ?? '');
    $catatan    = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    $item_count = (int)($_POST['item_count'] ?? 0);

    if(!$kd_tujuan)  { $error_msg = "Cabang tujuan harus dipilih."; }
    elseif(!$tgl_req){ $error_msg = "Tanggal harus diisi."; }
    else {
        $items = [];
        for($i=1; $i<=$item_count; $i++){
            $no_item = trim($_POST["no_item_$i"] ?? '');
            $qty_req = (int)($_POST["qty_req_$i"] ?? 0);
            if($no_item && $qty_req>0){
                $ni_safe = mysqli_real_escape_string($koneksi, $no_item);
                $qh = mysqli_query($koneksi,"SELECT noitem, hargapokok FROM tblitem WHERE noitem='$ni_safe' OR kodebarcode='$ni_safe' LIMIT 1");
                if($qh && mysqli_num_rows($qh)>0){
                    $ir = mysqli_fetch_assoc($qh);
                    $items[] = ['no_item'=>$ir['noitem'], 'qty_request'=>$qty_req,
                                'harga_pokok'=>(float)$ir['hargapokok'],
                                'subtotal'=>(float)$ir['hargapokok']*$qty_req];
                } else {
                    $error_msg = "Kode barang '<b>".htmlspecialchars($no_item)."</b>' tidak ditemukan.";
                    break;
                }
            }
        }
        if(!$error_msg && count($items)==0) $error_msg = "Minimal 1 barang dengan qty > 0.";

        if(!$error_msg){
            $prefix   = "PA".date('y');
            $qlast    = mysqli_query($koneksi,"SELECT no_order FROM tblorder_antarcab_header WHERE no_order LIKE '{$prefix}%' ORDER BY no_order DESC LIMIT 1");
            $last_num = 0;
            if($qlast && mysqli_num_rows($qlast)>0){ $rl=mysqli_fetch_row($qlast); $last_num=(int)substr($rl[0],strlen($prefix)); }
            $no_order    = $prefix.str_pad($last_num+1, 9, '0', STR_PAD_LEFT);
            $total_item  = count($items);
            $total_qty   = array_sum(array_column($items,'qty_request'));
            $total_nilai = array_sum(array_column($items,'subtotal'));

            mysqli_begin_transaction($koneksi);
            try {
                $q = mysqli_query($koneksi,
                    "INSERT INTO tblorder_antarcab_header
                     (no_order,kd_cabang_asal,kd_cabang_tujuan,tanggal_request,status,total_item,total_qty,total_nilai,catatan,user_request)
                     VALUES ('$no_order','$kd_safe','$kd_tujuan','$tgl_req','terkirim','$total_item','$total_qty','$total_nilai','$catatan','$_nama')");
                if(!$q) throw new Exception(mysqli_error($koneksi));
                $baris=1;
                foreach($items as $it){
                    $ni  = mysqli_real_escape_string($koneksi,$it['no_item']);
                    $qr  = (int)$it['qty_request'];
                    $hpp = (float)$it['harga_pokok'];
                    $sub = (float)$it['subtotal'];
                    $qd  = mysqli_query($koneksi,
                        "INSERT INTO tblorder_antarcab_detail (no_order,no_baris,no_item,qty_request,qty_kirim,qty_terima,harga_pokok,subtotal)
                         VALUES ('$no_order','$baris','$ni','$qr','0','0','$hpp','$sub')");
                    if(!$qd) throw new Exception(mysqli_error($koneksi));
                    $baris++;
                }
                mysqli_commit($koneksi);
                header("location:pengadaan_antarcab.php?msg=ok");
                exit;
            } catch(Exception $e){
                mysqli_rollback($koneksi);
                $error_msg = "Gagal menyimpan: ".$e->getMessage();
            }
        }
    }
}

$q_tujuan = mysqli_query($koneksi,"SELECT kode_cabang, nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang!='$kd_safe' ORDER BY tipe_cabang ASC, nama_cabang ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta charset="utf-8"/>
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css"/>
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style"/>
    <link rel="stylesheet" href="assets/css/ace-skins.min.css"/>
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css"/>
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only">Toggle sidebar</span>
            <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
        </button>
        <div class="navbar-header pull-left">
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="Profil"/>
                        <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                        <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <script>try{ace.settings.loadState('main-container')}catch(e){}</script>
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <script>try{ace.settings.loadState('sidebar')}catch(e){}</script>
        <?php include "menu_dashboard.php"; ?>
        <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="pengadaan_antarcab.php">Permintaan Antar Cabang</a></li>
                    <li class="active">Buat Permintaan</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Buat Permintaan Barang
                        <small><i class="ace-icon fa fa-angle-double-right"></i> <?php echo htmlspecialchars($nama_cabang); ?></small>
                    </h1>
                </div>

                <?php if($error_msg): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <i class="fa fa-times-circle"></i> <?php echo $error_msg; ?>
                </div>
                <?php endif; ?>

                <form method="post" id="formPermintaan">
                <div class="row">
                    <div class="col-xs-12 col-sm-9">
                        <div class="widget-box">
                            <div class="widget-header"><h4 class="widget-title"><i class="fa fa-file-text-o"></i> Info Permintaan</h4></div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <div class="form-horizontal">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Dari Cabang</label>
                                            <div class="col-sm-6">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($nama_cabang); ?>" readonly/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Ke Cabang <span class="text-danger">*</span></label>
                                            <div class="col-sm-6">
                                                <select name="kd_tujuan" class="form-control" required>
                                                    <option value="">-- Pilih Cabang Tujuan --</option>
                                                    <?php
                                                    $prev_tujuan = $_POST['kd_tujuan'] ?? '';
                                                    while($r_t = mysqli_fetch_assoc($q_tujuan)){
                                                        $tipe_t = $r_t['tipe_cabang'];
                                                        $sfx    = ($tipe_t=='1'||strtolower($tipe_t)=='pusat') ? ' ★ PUSAT' : '';
                                                        $sel    = ($r_t['kode_cabang']==$prev_tujuan)?'selected':'';
                                                        echo "<option value='{$r_t['kode_cabang']}' $sel>".htmlspecialchars($r_t['nama_cabang'])."$sfx</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <span class="help-block"><i class="fa fa-info-circle"></i> Pilih cabang yang akan mengirimkan barang (biasanya Pusat)</span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Tanggal <span class="text-danger">*</span></label>
                                            <div class="col-sm-4">
                                                <input type="date" name="tgl_request" class="form-control" value="<?php echo $_POST['tgl_request']??date('Y-m-d'); ?>" required/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Catatan</label>
                                            <div class="col-sm-8">
                                                <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"><?php echo htmlspecialchars($_POST['catatan']??''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <div class="widget-box">
                            <div class="widget-header"><h4 class="widget-title"><i class="fa fa-list"></i> Daftar Barang yang Diminta</h4></div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <p class="text-info"><i class="fa fa-lightbulb-o"></i> Ketik kode barang lalu tekan <kbd>Enter</kbd> atau klik tombol <i class="fa fa-search"></i> untuk mencari nama barang.</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-condensed" id="tblItem">
                                            <thead>
                                                <tr class="active">
                                                    <th style="width:35px">#</th>
                                                    <th style="width:200px">Kode Barang</th>
                                                    <th>Nama Barang</th>
                                                    <th style="width:90px">Qty Diminta</th>
                                                    <th style="width:40px"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemBody"></tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-default" id="btnTambah">
                                        <i class="fa fa-plus"></i> Tambah Baris
                                    </button>
                                    <input type="hidden" name="item_count" id="item_count" value="0"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-bottom:30px;">
                    <div class="col-xs-12">
                        <a href="pengadaan_antarcab.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Batal</a>
                        &nbsp;
                        <button type="submit" name="btnsimpan" class="btn btn-primary btn-lg">
                            <i class="fa fa-paper-plane"></i> Kirim Permintaan ke Pusat
                        </button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
    <div class="footer"><div class="footer-inner"><div class="footer-content"><?php include "../lib/footer.php"; ?></div></div></div>
</div>
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
<script>
var rowCount = 0;
function addRow(){
    rowCount++;
    $('#item_count').val(rowCount);
    var r = '<tr id="row_'+rowCount+'">'
        +'<td class="text-center text-muted">'+rowCount+'</td>'
        +'<td><div class="input-group input-group-sm">'
        +'<input type="text" name="no_item_'+rowCount+'" id="kode_'+rowCount+'" class="form-control" placeholder="Kode / barcode" autocomplete="off"/>'
        +'<span class="input-group-btn"><button type="button" class="btn btn-default" onclick="cariBarang('+rowCount+')"><i class="fa fa-search"></i></button></span>'
        +'</div></td>'
        +'<td><input type="text" id="nama_'+rowCount+'" class="form-control input-sm" readonly style="background:#f9f9f9;" placeholder="—"/></td>'
        +'<td><input type="number" name="qty_req_'+rowCount+'" class="form-control input-sm" min="1" value="1"/></td>'
        +'<td class="text-center"><button type="button" class="btn btn-xs btn-danger" onclick="$(\'#row_'+rowCount+'\').remove()"><i class="fa fa-times"></i></button></td>'
        +'</tr>';
    $('#itemBody').append(r);
    $('#kode_'+rowCount).focus();
}
function cariBarang(n){
    var kode=$('#kode_'+n).val().trim();
    if(!kode){alert('Masukkan kode barang.');return;}
    $.post('_ajax/ajax-cari-barang.php',{kode_barang:kode},function(res){
        if(res.success){$('#kode_'+n).val(res.kode_barang);$('#nama_'+n).val(res.nama_barang);}
        else{$('#nama_'+n).val('Tidak ditemukan');alert('Barang "'+kode+'" tidak ditemukan.');}
    },'json').fail(function(){alert('Gagal koneksi server.');});
}
$(document).on('keypress','[id^="kode_"]',function(e){
    if(e.which==13){e.preventDefault();var n=$(this).attr('id').replace('kode_','');cariBarang(n);}
});
$('#btnTambah').on('click',addRow);
$(function(){addRow();addRow();addRow();});
</script>
</body>
</html>
