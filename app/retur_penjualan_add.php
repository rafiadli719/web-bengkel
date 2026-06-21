<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    $cari_kd=mysqli_query($koneksi,"SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

    $cari_kd=mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];

    $init_nopenjualan = isset($_GET['nopenjualan']) ? htmlspecialchars($_GET['nopenjualan']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
        .tbl-item thead th { background: #5a7a35; color: #fff; padding: 6px 8px; }
        .qty-input { width: 70px; text-align: center; }
        #info-penjualan { display:none; }
        #tbl-items-container { display:none; }
        .harga-cell { text-align:right; }
    </style>
</head>
<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </button>
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
                </a>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
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
                        <li><a href="penjualan.php">Penjualan</a></li>
                        <li><a href="retur_penjualan.php">Retur Penjualan</a></li>
                        <li class="active">Input Retur</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-undo"></i> Input Retur Penjualan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">

                                        <div class="row">
                                            <div class="col-xs-12 col-sm-8">
                                                <div class="input-group">
                                                    <span class="input-group-addon"><b>No. Penjualan</b></span>
                                                    <input type="text" id="txt_nopenjualan" class="form-control"
                                                           placeholder="Masukkan nomor penjualan (contoh: JL26000000001)"
                                                           value="<?php echo $init_nopenjualan; ?>" />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-primary" id="btn_load_items" type="button">
                                                            <i class="fa fa-search"></i> Load Items
                                                        </button>
                                                    </span>
                                                </div>
                                                <span id="msg_error" class="text-danger" style="display:none;font-weight:bold;"></span>
                                            </div>
                                        </div>

                                        <div class="space space-8"></div>

                                        <div id="info-penjualan" class="alert alert-info">
                                            <strong>Penjualan:</strong> <span id="lbl_nopenjualan"></span> &nbsp;|&nbsp;
                                            <strong>Pelanggan:</strong> <span id="lbl_pelanggan"></span> &nbsp;|&nbsp;
                                            <strong>Cara Bayar:</strong> <span id="lbl_carabayar"></span>
                                        </div>

                                        <form id="form-retur" action="save_retur_penjualan.php" method="post">
                                            <input type="hidden" name="nopenjualan" id="hid_nopenjualan" />
                                            <input type="hidden" name="kd_cabang" value="<?php echo htmlspecialchars($kd_cabang); ?>" />
                                            <input type="hidden" name="user_input" value="<?php echo htmlspecialchars($_nama); ?>" />

                                            <div id="tbl-items-container">
                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <div style="overflow-x:auto;">
                                                        <table class="table table-bordered tbl-item">
                                                            <thead>
                                                                <tr>
                                                                    <th width="4%" class="center">Pilih</th>
                                                                    <th width="11%">Kode Item</th>
                                                                    <th width="24%">Nama Item</th>
                                                                    <th class="center" width="7%">Qty Jual</th>
                                                                    <th class="center" width="7%">Sdh Retur</th>
                                                                    <th class="center" width="6%">Sisa</th>
                                                                    <th class="center" width="7%">Qty Retur</th>
                                                                    <th class="harga-cell" width="9%">Harga</th>
                                                                    <th class="harga-cell" width="9%">Subtotal</th>
                                                                    <th width="16%">Alasan</th>
                                                                    <th width="15%">Jenis Penggantian</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="tbody_items"></tbody>
                                                        </table>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-xs-12 col-sm-5">
                                                        <div class="form-group">
                                                            <label><strong>Keterangan / Catatan:</strong></label>
                                                            <input type="text" name="note" class="form-control" placeholder="Opsional" maxlength="50" />
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-12 col-sm-4 col-sm-offset-3">
                                                        <table class="table table-condensed" style="margin-top:20px;">
                                                            <tr><td><strong>Total Qty Retur:</strong></td><td align="right" id="lbl_total_qty"><strong>0</strong></td></tr>
                                                            <tr><td><strong>Total Nilai Retur:</strong></td><td align="right" id="lbl_total_nilai"><strong>0</strong></td></tr>
                                                        </table>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <hr />
                                                        <button type="submit" class="btn btn-danger btn-lg">
                                                            <i class="fa fa-save"></i> Simpan Retur Penjualan
                                                        </button>
                                                        &nbsp;
                                                        <a href="retur_penjualan.php" class="btn btn-default btn-lg">
                                                            <i class="fa fa-times"></i> Batal
                                                        </a>
                                                    </div>
                                                </div>
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

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content"><?php include "../lib/footer.php"; ?></div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script>
    var jenisGlobal = [];
    function formatRibuan(n) { return parseFloat(n).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
    function buildJenisOptions() {
        var html = '';
        for(var i=0;i<jenisGlobal.length;i++) html += '<option value="'+jenisGlobal[i].id+'">'+jenisGlobal[i].jenis_penggantian+'</option>';
        return html;
    }
    function hitungTotal() {
        var tq=0, tn=0;
        $('#tbody_items tr').each(function(){
            if($(this).find('.chk-item').prop('checked')){
                var q=parseInt($(this).find('.qty-input').val())||0, h=parseFloat($(this).find('.harga-data').val())||0;
                tq+=q; tn+=q*h; $(this).find('.subtotal-cell').text(formatRibuan(q*h));
            } else { $(this).find('.subtotal-cell').text('0'); }
        });
        $('#lbl_total_qty').html('<strong>'+tq+'</strong>');
        $('#lbl_total_nilai').html('<strong>'+formatRibuan(tn)+'</strong>');
    }
    $('#btn_load_items').on('click', function(){
        var nopenjualan=$.trim($('#txt_nopenjualan').val());
        if(nopenjualan==''){$('#msg_error').text('No. Penjualan tidak boleh kosong').show();return;}
        $('#msg_error').hide();
        var self=this; $(self).prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        $.getJSON('ajax_get_items_retur_penjualan.php',{nopenjualan:nopenjualan},function(data){
            $(self).prop('disabled',false).html('<i class="fa fa-search"></i> Load Items');
            if(data.error){$('#msg_error').text(data.error).show();$('#info-penjualan').hide();$('#tbl-items-container').hide();return;}
            jenisGlobal=data.jenis;
            $('#lbl_nopenjualan').text(data.nopenjualan); $('#lbl_pelanggan').text(data.namapelanggan); $('#lbl_carabayar').text(data.carabayar);
            $('#hid_nopenjualan').val(data.nopenjualan); $('#info-penjualan').show();
            if(data.items.length==0){$('#msg_error').text('Tidak ada item yang bisa diretur.').show();$('#tbl-items-container').hide();return;}
            var html='';
            for(var i=0;i<data.items.length;i++){
                var it=data.items[i];
                html+='<tr><td class="center"><input type="checkbox" class="chk-item"/></td>';
                html+='<td>'+it.no_item+'<input type="hidden" name="item_kode[]" value="'+it.no_item+'"/></td>';
                html+='<td>'+it.namaitem+'</td><td class="center">'+it.qty_jual+'</td><td class="center">'+it.qty_retur+'</td>';
                html+='<td class="center"><strong>'+it.sisa_retur+'</strong></td>';
                html+='<td class="center"><input type="number" class="form-control qty-input" name="item_qty[]" value="1" min="1" max="'+it.sisa_retur+'"/></td>';
                html+='<td class="harga-cell">'+formatRibuan(it.harga_jual)+'<input type="hidden" class="harga-data" name="item_harga[]" value="'+it.harga_jual+'"/></td>';
                html+='<td class="harga-cell subtotal-cell">0</td>';
                html+='<td><input type="text" class="form-control input-sm" name="item_alasan[]" placeholder="Alasan..." maxlength="100"/></td>';
                html+='<td><select class="form-control input-sm" name="item_jenis[]">'+buildJenisOptions()+'</select></td></tr>';
            }
            $('#tbody_items').html(html); $('#tbl-items-container').show(); hitungTotal();
        }).fail(function(){ $(self).prop('disabled',false).html('<i class="fa fa-search"></i> Load Items'); $('#msg_error').text('Gagal menghubungi server.').show(); });
    });
    $(document).on('change','.chk-item',hitungTotal);
    $(document).on('input change','.qty-input',function(){
        var max=parseInt($(this).attr('max'))||1, val=parseInt($(this).val())||1;
        if(val>max)$(this).val(max); if(val<1)$(this).val(1); hitungTotal();
    });
    $('#form-retur').on('submit',function(e){
        if($('#hid_nopenjualan').val()==''){e.preventDefault();alert('Load items penjualan terlebih dahulu.');return;}
        if($('.chk-item:checked').length==0){e.preventDefault();alert('Centang minimal 1 item yang akan diretur.');return;}
        $('.chk-item').each(function(){
            var row=$(this).closest('tr'), enabled=$(this).prop('checked');
            row.find('input[name="item_qty[]"],input[name="item_kode[]"],input[name="item_harga[]"],input[name="item_alasan[]"]').prop('disabled',!enabled);
            row.find('select[name="item_jenis[]"]').prop('disabled',!enabled);
        });
        if(!confirm('Simpan retur penjualan ini?')){
            e.preventDefault();
            $('[name="item_qty[]"],[name="item_kode[]"],[name="item_harga[]"],[name="item_alasan[]"],[name="item_jenis[]"]').prop('disabled',false);
        }
    });
    <?php if($init_nopenjualan): ?>
    $(document).ready(function(){ $('#btn_load_items').trigger('click'); });
    <?php endif; ?>
    </script>
</body>
</html>
<?php } ?>
