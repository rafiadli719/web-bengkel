<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd = mysqli_query($koneksi,"SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari  = mysqli_fetch_array($cari_kd);
$_nama     = $tm_cari['nama_user'];
$foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

$kd_safe = mysqli_real_escape_string($koneksi, $kd_cabang);
$cari_cab = mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_safe'");
$tm_cab   = mysqli_fetch_array($cari_cab);
$nama_cabang = $tm_cab['nama_cabang'];

$error_msg  = '';
$success_no = '';

if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['order_ke'])){
    $order_ke   = mysqli_real_escape_string($koneksi, trim($_POST['order_ke'] ?? ''));
    $tgl_req    = mysqli_real_escape_string($koneksi, $_POST['tgl_pesanan'] ?? date('Y-m-d'));
    $catatan    = mysqli_real_escape_string($koneksi, trim($_POST['catatan'] ?? ''));
    $item_count = (int)($_POST['item_count'] ?? 0);

    if(!$order_ke){ $error_msg = "Cabang tujuan harus dipilih."; }
    else {
        $items = [];
        for($i=1; $i<=$item_count; $i++){
            $no_item = trim($_POST["no_item_$i"] ?? '');
            $qty     = (int)($_POST["qty_$i"] ?? 0);
            if($no_item && $qty>0){
                $ni_safe = mysqli_real_escape_string($koneksi, $no_item);
                $qh = mysqli_query($koneksi,"SELECT noitem, namaitem, hargapokok FROM tblitem WHERE noitem='$ni_safe' OR kodebarcode='$ni_safe' LIMIT 1");
                if($qh && mysqli_num_rows($qh)>0){
                    $ir = mysqli_fetch_assoc($qh);
                    $items[] = ['no_item'=>$ir['noitem'],'namaitem'=>$ir['namaitem'],
                                'qty'=>$qty,'harga'=>(float)$ir['hargapokok'],
                                'subtotal'=>(float)$ir['hargapokok']*$qty];
                } else {
                    $error_msg = "Kode '<b>".htmlspecialchars($no_item)."</b>' tidak ditemukan di master item.";
                    break;
                }
            }
        }
        if(!$error_msg && count($items)==0) $error_msg = "Minimal 1 item dengan qty > 0.";

        if(!$error_msg){
            $prefix   = "OJC".date('y');
            $qlast    = mysqli_query($koneksi,"SELECT no_order FROM tblorderjual_header WHERE no_order LIKE '{$prefix}%' ORDER BY no_order DESC LIMIT 1");
            $last_num = 0;
            if($qlast && mysqli_num_rows($qlast)>0){ $rl=mysqli_fetch_row($qlast); $last_num=(int)substr($rl[0],strlen($prefix)); }
            $no_order   = $prefix.str_pad($last_num+1, 9, '0', STR_PAD_LEFT);
            $total_qty  = array_sum(array_column($items,'qty'));
            $total_jual = array_sum(array_column($items,'subtotal'));
            $nm_safe    = mysqli_real_escape_string($koneksi, $_nama);

            mysqli_begin_transaction($koneksi);
            try {
                $q = mysqli_query($koneksi,"INSERT INTO tblorderjual_header
                    (no_order,status,tanggal,no_sales,no_pelanggan,note,total_qty,total_terima,
                     diskon,total_diskon,pajak,total_pajak,total_akhir,pembayaran,
                     user,id_tabel,kd_cabang,tipe_trx,order_ke,total_jual)
                    VALUES ('$no_order','0','$tgl_req','','','$catatan','$total_qty','0',
                            '0','0','0','0','$total_jual','$total_jual',
                            '$nm_safe','','$kd_safe','Antar Cabang','$order_ke','$total_jual')");
                if(!$q) throw new Exception(mysqli_error($koneksi));
                foreach($items as $it){
                    $ni  = mysqli_real_escape_string($koneksi,$it['no_item']);
                    $qr  = (int)$it['qty'];
                    $hrg = (float)$it['harga'];
                    $sub = (float)$it['subtotal'];
                    $qd  = mysqli_query($koneksi,"INSERT INTO tblorderjual_detail
                        (no_order,no_item,harga_jual,quantity,nobaris,qty_terima,
                         potongan,harga_sp,harga_pokok,total,margin_jual,user,kd_cabang)
                        VALUES ('$no_order','$ni','$hrg','$qr','0','0',
                                '0','0','$hrg','$sub','0','$nm_safe','$kd_safe')");
                    if(!$qd) throw new Exception(mysqli_error($koneksi));
                }
                mysqli_commit($koneksi);
                $success_no = $no_order;
            } catch(Exception $e){
                mysqli_rollback($koneksi);
                $error_msg = "Gagal menyimpan: ".$e->getMessage();
            }
        }
    }
}

$q_cab = mysqli_query($koneksi,"SELECT kode_cabang,nama_cabang,tipe_cabang FROM tbcabang WHERE kode_cabang!='$kd_safe' ORDER BY tipe_cabang ASC,nama_cabang ASC");
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
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"
               data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="#">Antar Cabang</a></li>
                    <li class="active">Buat Pesanan Internal</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Buat Pesanan Antar Cabang
                        <small><i class="ace-icon fa fa-angle-double-right"></i> <?php echo htmlspecialchars($nama_cabang); ?></small>
                    </h1>
                </div>

                <?php if($success_no): ?>
                <div class="alert alert-success">
                    <i class="fa fa-check"></i> Pesanan <strong><?php echo htmlspecialchars($success_no); ?></strong> berhasil dibuat.
                    <a href="penjualan_cab_add.php" class="btn btn-sm btn-primary" style="margin-left:10px;">
                        <i class="fa fa-list"></i> Lihat Pesanan Masuk
                    </a>
                    <a href="pesanan_penjualan_cab_add.php" class="btn btn-sm btn-default" style="margin-left:5px;">
                        <i class="fa fa-plus"></i> Buat Pesanan Baru
                    </a>
                </div>
                <?php endif; ?>

                <?php if($error_msg): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <i class="fa fa-times-circle"></i> <?php echo $error_msg; ?>
                </div>
                <?php endif; ?>

                <ul class="nav nav-tabs" style="margin-bottom:15px;">
                    <li class="active"><a href="#"><i class="fa fa-keyboard-o"></i> Input Manual</a></li>
                    <li><a href="penjualan_antarcab_upload.php"><i class="fa fa-upload"></i> Upload Excel</a></li>
                </ul>

                <?php if(!$success_no): ?>
                <form method="post" id="formPesanan">
                <input type="hidden" name="item_count" id="item_count" value="1"/>
                <div class="row">
                    <div class="col-xs-12 col-sm-8">
                        <div class="widget-box">
                            <div class="widget-header"><h4 class="widget-title"><i class="fa fa-file-text-o"></i> Info Pesanan</h4></div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <div class="form-horizontal">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Dari Cabang</label>
                                            <div class="col-sm-7">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($nama_cabang); ?>" readonly/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Ke Cabang <span class="text-danger">*</span></label>
                                            <div class="col-sm-7">
                                                <select name="order_ke" class="form-control" required>
                                                    <option value="">-- Pilih Cabang Tujuan --</option>
                                                    <?php
                                                    $prev_ke = $_POST['order_ke'] ?? '';
                                                    while($r_t = mysqli_fetch_assoc($q_cab)){
                                                        $sfx = (strtolower($r_t['tipe_cabang'])=='pusat'||$r_t['tipe_cabang']=='1') ? ' (Pusat)' : '';
                                                        $sel = ($r_t['kode_cabang']==$prev_ke)?'selected':'';
                                                        echo "<option value='{$r_t['kode_cabang']}' $sel>".htmlspecialchars($r_t['nama_cabang'])."$sfx</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <span class="help-block"><i class="fa fa-info-circle"></i> Cabang yang akan mengirimkan barang</span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Tanggal Pesanan</label>
                                            <div class="col-sm-4">
                                                <input type="date" name="tgl_pesanan" class="form-control" value="<?php echo date('Y-m-d'); ?>" required/>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Catatan</label>
                                            <div class="col-sm-7">
                                                <textarea name="catatan" class="form-control" rows="2" placeholder="Keterangan pesanan..."><?php echo htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title"><i class="fa fa-list"></i> Item Barang</h4>
                                <div class="widget-toolbar">
                                    <button type="button" class="btn btn-xs btn-success" id="btnTambahBaris">
                                        <i class="fa fa-plus"></i> Tambah Baris
                                    </button>
                                </div>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main no-padding">
                                    <table class="table table-bordered table-condensed" id="tblBarang">
                                        <thead>
                                            <tr class="active">
                                                <th style="width:35px">No</th>
                                                <th style="width:170px">Kode Item</th>
                                                <th>Nama Item</th>
                                                <th style="width:110px" class="text-right">Harga HPP</th>
                                                <th style="width:80px" class="text-right">Qty</th>
                                                <th style="width:120px" class="text-right">Subtotal</th>
                                                <th style="width:40px"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyBarang"></tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-right">Total</th>
                                                <th class="text-right" id="totalQty">0</th>
                                                <th class="text-right" id="totalNilai">Rp 0</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-4">
                        <div class="widget-box">
                            <div class="widget-header"><h4 class="widget-title"><i class="fa fa-cogs"></i> Aksi</h4></div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <div class="well well-sm">
                                        <div class="row">
                                            <div class="col-xs-6">Total Qty</div>
                                            <div class="col-xs-6 text-right"><strong id="summQty">0</strong></div>
                                        </div>
                                        <div class="row" style="margin-top:5px;">
                                            <div class="col-xs-6">Total Nilai</div>
                                            <div class="col-xs-6 text-right"><strong id="summNilai">Rp 0</strong></div>
                                        </div>
                                    </div>
                                    <button type="submit" name="btnsimpan" id="btnSimpan" class="btn btn-primary btn-block btn-lg">
                                        <i class="fa fa-paper-plane"></i> Kirim Pesanan
                                    </button>
                                    <div style="margin-top:10px;">
                                        <a href="penjualan_cab_add.php" class="btn btn-default btn-block">
                                            <i class="fa fa-list"></i> Lihat Pesanan Masuk
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
<script>
var rowCount = 0;

function fmt(n){ return 'Rp '+Math.round(n).toLocaleString('id-ID'); }

function hitungSubtotal(n){
    var hpp = parseFloat($('#hpp_'+n).val())||0;
    var qty = parseInt($('#qty_'+n).val())||0;
    var sub = hpp*qty;
    $('#sub_'+n).val(sub);
    $('#sub_disp_'+n).text(fmt(sub));
    hitungTotal();
}

function hitungTotal(){
    var tq=0, tn=0;
    $('#tbodyBarang tr').each(function(){
        var n=$(this).data('row');
        tq += parseInt($('#qty_'+n).val())||0;
        tn += parseFloat($('#sub_'+n).val())||0;
    });
    var tqs=tq.toString(), tns=fmt(tn);
    $('#totalQty').text(tqs); $('#summQty').text(tqs);
    $('#totalNilai').text(tns); $('#summNilai').text(tns);
    $('#item_count').val($('#tbodyBarang tr').length);
}

function makeRow(n){
    return '<tr id="row_'+n+'" data-row="'+n+'">'
        +'<td class="text-center row-no"></td>'
        +'<td style="position:relative;">'
            +'<input type="text" class="form-control input-sm kode-input" id="kode_'+n+'" name="no_item_'+n+'" placeholder="Ketik kode/nama..." autocomplete="off"/>'
            +'<div id="drop_'+n+'" style="display:none;position:absolute;top:100%;left:0;z-index:9999;background:#fff;border:1px solid #ccc;border-radius:4px;max-height:180px;overflow-y:auto;width:320px;box-shadow:0 3px 8px rgba(0,0,0,.15);font-size:12px;"></div>'
        +'</td>'
        +'<td><input type="text" class="form-control input-sm" id="nama_'+n+'" readonly style="background:#f9f9f9"/></td>'
        +'<td class="text-right"><input type="hidden" id="hpp_'+n+'" value="0"/><span id="hpp_disp_'+n+'">Rp 0</span></td>'
        +'<td><input type="number" class="form-control input-sm qty-r" id="qty_'+n+'" name="qty_'+n+'" min="1" value="1" data-row="'+n+'" style="width:65px;text-align:right"/></td>'
        +'<td class="text-right"><input type="hidden" id="sub_'+n+'" name="sub_'+n+'" value="0"/><span id="sub_disp_'+n+'">Rp 0</span></td>'
        +'<td class="text-center"><button type="button" class="btn btn-xs btn-danger btn-hapus" data-row="'+n+'"><i class="fa fa-trash"></i></button></td>'
        +'</tr>';
}

function setupAutocomplete(n){
    var timer;
    $('#kode_'+n).on('input', function(){
        clearTimeout(timer);
        var q=$(this).val().trim();
        if(q.length<2){ $('#drop_'+n).hide(); return; }
        timer=setTimeout(function(){
            $.getJSON('ajax_get_item_hpp.php?search='+encodeURIComponent(q), function(data){
                if(!data||!data.length){ $('#drop_'+n).hide(); return; }
                var html='';
                $.each(data, function(i,it){
                    html+='<div class="ac-item" data-kode="'+it.noitem+'" data-nama="'+it.namaitem.replace(/"/g,'&quot;')+'" data-hpp="'+it.hargapokok+'" data-row="'+n+'" style="padding:5px 8px;cursor:pointer;border-bottom:1px solid #eee;">'
                        +'<strong>'+it.noitem+'</strong> &mdash; '+it.namaitem+'</div>';
                });
                $('#drop_'+n).html(html).show();
            });
        },300);
    });
    $(document).on('mousedown','.ac-item',function(e){
        e.preventDefault();
        var rowId=$(this).data('row');
        $('#kode_'+rowId).val($(this).data('kode'));
        $('#nama_'+rowId).val($(this).data('nama'));
        var hpp=parseFloat($(this).data('hpp'))||0;
        $('#hpp_'+rowId).val(hpp);
        $('#hpp_disp_'+rowId).text(fmt(hpp));
        $('#drop_'+rowId).hide();
        hitungSubtotal(rowId);
    });
    $('#kode_'+n).on('blur',function(){ setTimeout(function(){ $('#drop_'+n).hide(); },200); });
}

$(function(){
    rowCount++;
    $('#tbodyBarang').append(makeRow(rowCount));
    renumber();
    setupAutocomplete(rowCount);
    hitungTotal();

    $('#btnTambahBaris').on('click',function(){
        rowCount++;
        $('#tbodyBarang').append(makeRow(rowCount));
        renumber();
        setupAutocomplete(rowCount);
        hitungTotal();
    });

    $(document).on('click','.btn-hapus',function(){
        if($('#tbodyBarang tr').length<=1){ alert('Minimal 1 baris item.'); return; }
        $('#row_'+$(this).data('row')).remove();
        renumber();
        hitungTotal();
    });

    $(document).on('input change','.qty-r',function(){ hitungSubtotal($(this).data('row')); });

    $('#formPesanan').on('submit',function(){
        var ok=true;
        $('#tbodyBarang tr').each(function(){
            if(!$('.kode-input',this).val().trim()){ ok=false; }
        });
        if(!ok){ alert('Ada baris item dengan kode barang kosong.'); return false; }
        $('#btnSimpan').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    });
});

function renumber(){
    $('#tbodyBarang tr').each(function(i){ $(this).find('.row-no').text(i+1); });
}
</script>
</body>
</html>
