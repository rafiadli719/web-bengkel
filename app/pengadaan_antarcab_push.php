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

if(!$is_pusat){ header("location:pengadaan_antarcab.php"); exit; }

// Pastikan kolom jenis ada
$col_j = mysqli_query($koneksi,"SHOW COLUMNS FROM tblorder_antarcab_header LIKE 'jenis'");
if(!$col_j || mysqli_num_rows($col_j)==0){
    mysqli_query($koneksi,"ALTER TABLE tblorder_antarcab_header ADD COLUMN jenis VARCHAR(10) DEFAULT 'pull' AFTER status");
}

// Daftar cabang aktif (bukan pusat sendiri)
$q_cab = mysqli_query($koneksi,"SELECT kode_cabang, nama_cabang FROM tbcabang WHERE kode_cabang!='$kd_safe' ORDER BY nama_cabang");
$list_cabang = [];
while($r = mysqli_fetch_assoc($q_cab)) $list_cabang[] = $r;

$error_msg = '';

if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['btnsimpan'])){
    $kd_tujuan  = mysqli_real_escape_string($koneksi, $_POST['kd_tujuan'] ?? '');
    $tgl_kirim  = mysqli_real_escape_string($koneksi, $_POST['tgl_kirim'] ?? date('Y-m-d'));
    $catatan    = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    $item_count = (int)($_POST['item_count'] ?? 0);

    if(!$kd_tujuan){ $error_msg = "Pilih cabang tujuan terlebih dahulu."; }
    else {
        $col_kd = mysqli_query($koneksi,"SHOW COLUMNS FROM tbstok LIKE 'kd_cabang'");
        $has_kd_cabang = ($col_kd && mysqli_num_rows($col_kd)>0);

        $q_tuj = mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_tujuan' LIMIT 1");
        $nm_tujuan = ($q_tuj && ($rr=mysqli_fetch_row($q_tuj))) ? $rr[0] : $kd_tujuan;

        $items = [];
        for($i=1; $i<=$item_count; $i++){
            $no_item = trim($_POST["no_item_$i"] ?? '');
            $qty_req = (int)($_POST["qty_req_$i"] ?? 0);
            if($no_item && $qty_req>0){
                $ni_safe = mysqli_real_escape_string($koneksi, $no_item);
                $qh = mysqli_query($koneksi,"SELECT noitem, namaitem, hargapokok FROM tblitem WHERE noitem='$ni_safe' OR kodebarcode='$ni_safe' LIMIT 1");
                if($qh && mysqli_num_rows($qh)>0){
                    $ir = mysqli_fetch_assoc($qh);
                    $items[] = [
                        'no_item'  => $ir['noitem'],
                        'namaitem' => $ir['namaitem'],
                        'qty'      => $qty_req,
                        'hpp'      => (float)$ir['hargapokok'],
                        'subtotal' => (float)$ir['hargapokok']*$qty_req,
                    ];
                } else {
                    $error_msg = "Kode barang '<b>".htmlspecialchars($no_item)."</b>' tidak ditemukan di master item.";
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
            $total_qty   = array_sum(array_column($items,'qty'));
            $total_nilai = array_sum(array_column($items,'subtotal'));
            $nm_safe     = mysqli_real_escape_string($koneksi, $_nama);

            mysqli_begin_transaction($koneksi);
            try {
                // PUSH: kd_cabang_asal=PUSAT (pengirim), kd_cabang_tujuan=cabang (penerima)
                $q = mysqli_query($koneksi,
                    "INSERT INTO tblorder_antarcab_header
                     (no_order,kd_cabang_asal,kd_cabang_tujuan,tanggal_request,status,jenis,
                      total_item,total_qty,total_nilai,catatan,user_request,user_proses,tanggal_kirim)
                     VALUES ('$no_order','$kd_safe','$kd_tujuan','$tgl_kirim','dikirim','push',
                             '$total_item','$total_qty','$total_nilai','$catatan','$nm_safe','$nm_safe','$tgl_kirim')");
                if(!$q) throw new Exception(mysqli_error($koneksi));

                $baris = 1;
                foreach($items as $it){
                    $ni  = mysqli_real_escape_string($koneksi,$it['no_item']);
                    $qty = (int)$it['qty'];
                    $hpp = (float)$it['hpp'];
                    $sub = (float)$it['subtotal'];
                    $qd  = mysqli_query($koneksi,
                        "INSERT INTO tblorder_antarcab_detail
                         (no_order,no_baris,no_item,qty_request,qty_kirim,qty_terima,harga_pokok,subtotal)
                         VALUES ('$no_order','$baris','$ni','$qty','$qty','0','$hpp','$sub')");
                    if(!$qd) throw new Exception(mysqli_error($koneksi));

                    $ket = mysqli_real_escape_string($koneksi,'Pengadaan AC (Push) - Kirim ke '.$nm_tujuan);
                    if($has_kd_cabang){
                        $qs = mysqli_query($koneksi,"INSERT INTO tbstok (no_transaksi,no_item,tanggal,tipe,masuk,keluar,keterangan,kd_cabang)
                            VALUES ('$no_order','$ni','$tgl_kirim','3','0','$qty','$ket','$kd_safe')");
                    } else {
                        $qs = mysqli_query($koneksi,"INSERT INTO tbstok (no_transaksi,no_item,tanggal,tipe,masuk,keluar,keterangan)
                            VALUES ('$no_order','$ni','$tgl_kirim','3','0','$qty','$ket')");
                    }
                    if(!$qs) throw new Exception(mysqli_error($koneksi));
                    $baris++;
                }

                mysqli_commit($koneksi);
                header("location:pengadaan_antarcab.php?msg=push_ok");
                exit;
            } catch(Exception $e){
                mysqli_rollback($koneksi);
                $error_msg = "Gagal menyimpan: ".$e->getMessage();
            }
        }
    }
}
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
    <style>
        .tbl-barang td { vertical-align: middle !important; }
        .qty-input { width:70px; text-align:center; }
        .item-input { width:130px; font-size:12px; }
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
                    <li class="active">Kirim ke Cabang (Inisiasi Pusat)</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Kirim Barang ke Cabang
                        <small><i class="ace-icon fa fa-angle-double-right"></i> Inisiasi Pusat — Tanpa Request</small>
                    </h1>
                </div>

                <div class="alert alert-warning">
                    <i class="fa fa-info-circle"></i>
                    <strong>Mode Push:</strong> Pusat mengirim barang ke cabang tanpa ada permintaan sebelumnya.
                    Stok pusat akan <strong>langsung berkurang</strong> saat disimpan. Cabang mengkonfirmasi penerimaan.
                </div>

                <?php if($error_msg): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <i class="fa fa-times-circle"></i> <?php echo $error_msg; ?>
                </div>
                <?php endif; ?>

                <form method="post" id="frmPush">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="widget-box">
                                <div class="widget-header"><h4 class="widget-title"><i class="fa fa-info-circle"></i> Informasi Pengiriman</h4></div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="form-horizontal">
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label">Cabang Tujuan <span class="text-danger">*</span></label>
                                                <div class="col-sm-7">
                                                    <select name="kd_tujuan" class="form-control" required>
                                                        <option value="">-- Pilih Cabang --</option>
                                                        <?php foreach($list_cabang as $cb): ?>
                                                        <option value="<?php echo htmlspecialchars($cb['kode_cabang']); ?>">
                                                            <?php echo htmlspecialchars($cb['nama_cabang']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label">Tanggal Kirim <span class="text-danger">*</span></label>
                                                <div class="col-sm-5">
                                                    <input type="date" name="tgl_kirim" class="form-control" value="<?php echo date('Y-m-d'); ?>" required/>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label">Catatan / No. Resi</label>
                                                <div class="col-sm-7">
                                                    <input type="text" name="catatan" class="form-control" placeholder="Opsional: nama ekspedisi, no. resi, dll"/>
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
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-list"></i> Daftar Barang yang Dikirim</h4>
                                    <div class="widget-toolbar">
                                        <button type="button" class="btn btn-xs btn-success" id="btnTambahBaris">
                                            <i class="fa fa-plus"></i> Tambah Baris
                                        </button>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <p class="text-muted"><i class="fa fa-info-circle"></i> Ketik kode barang lalu tekan Tab/Enter untuk mencari nama dan HPP otomatis.</p>
                                        <div class="table-responsive">
                                            <table class="table table-bordered tbl-barang" id="tblBarang">
                                                <thead>
                                                    <tr class="active">
                                                        <th style="width:35px">#</th>
                                                        <th style="width:140px">Kode Barang <span class="text-danger">*</span></th>
                                                        <th>Nama Barang</th>
                                                        <th class="text-center" style="width:80px">Qty <span class="text-danger">*</span></th>
                                                        <th class="text-right" style="width:120px">HPP (Rp)</th>
                                                        <th class="text-right" style="width:130px">Subtotal (Rp)</th>
                                                        <th style="width:40px"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyBarang">
                                                    <tr id="row_1">
                                                        <td class="text-center row-no">1</td>
                                                        <td>
                                                            <input type="text" name="no_item_1" class="form-control input-sm item-input kode-input"
                                                                   data-row="1" placeholder="Kode item" autocomplete="off"/>
                                                        </td>
                                                        <td>
                                                            <span class="nama-item text-muted" data-row="1">—</span>
                                                            <input type="hidden" class="hpp-val" data-row="1" value="0"/>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="qty_req_1" class="form-control input-sm qty-input qty-r"
                                                                   data-row="1" min="1" value="1"/>
                                                        </td>
                                                        <td class="text-right"><span class="hpp-disp" data-row="1">0</span></td>
                                                        <td class="text-right sub-disp" data-row="1">0</td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-xs btn-danger btn-hapus" data-row="1"><i class="fa fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="5" class="text-right">Total Nilai (Rp)</th>
                                                        <th class="text-right" id="grand_total">0</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <input type="hidden" name="item_count" id="item_count" value="1"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom:30px;">
                        <div class="col-xs-12">
                            <a href="pengadaan_antarcab.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Batal</a>
                            &nbsp;
                            <button type="submit" name="btnsimpan" id="btn-simpan-push" class="btn btn-primary btn-lg"
                                onclick="if(!confirm('Yakin kirim barang? Stok pusat akan langsung berkurang.')) return false; this.disabled=true; this.innerHTML='<i class=\'fa fa-spinner fa-spin\'></i> Menyimpan...'; return true;">
                                <i class="fa fa-truck"></i> Simpan &amp; Kirim ke Cabang
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
var rowCount = 1;

function formatRp(n){ return Math.round(n).toLocaleString('id-ID'); }

function hitungSubtotal(row){
    var hpp  = parseFloat($('.hpp-val[data-row="'+row+'"]').val())||0;
    var qty  = parseInt($('[name="qty_req_'+row+'"]').val())||0;
    var sub  = hpp*qty;
    $('.sub-disp[data-row="'+row+'"]').text(formatRp(sub));
    hitungTotal();
}

function hitungTotal(){
    var total=0;
    $('.hpp-val').each(function(){
        var row=$(this).data('row');
        var hpp=parseFloat($(this).val())||0;
        var qty=parseInt($('[name="qty_req_'+row+'"]').val())||0;
        total+=hpp*qty;
    });
    $('#grand_total').text(formatRp(total));
}

function lookupItem(row){
    var kode=$('[name="no_item_'+row+'"]').val().trim();
    if(!kode) return;
    $.getJSON('ajax_get_item_hpp.php',{kode:kode},function(d){
        if(d && d.noitem){
            $('.nama-item[data-row="'+row+'"]').text(d.namaitem).removeClass('text-muted text-danger').addClass('text-success');
            $('.hpp-val[data-row="'+row+'"]').val(d.hargapokok);
            $('.hpp-disp[data-row="'+row+'"]').text(formatRp(d.hargapokok));
            hitungSubtotal(row);
        } else {
            $('.nama-item[data-row="'+row+'"]').text('Tidak ditemukan').removeClass('text-success').addClass('text-danger');
            $('.hpp-val[data-row="'+row+'"]').val(0);
            $('.hpp-disp[data-row="'+row+'"]').text('0');
            hitungSubtotal(row);
        }
    });
}

function makeRow(n){
    return '<tr id="row_'+n+'">'
        +'<td class="text-center row-no">'+n+'</td>'
        +'<td><input type="text" name="no_item_'+n+'" class="form-control input-sm item-input kode-input" data-row="'+n+'" placeholder="Kode item" autocomplete="off"/></td>'
        +'<td><span class="nama-item text-muted" data-row="'+n+'">—</span><input type="hidden" class="hpp-val" data-row="'+n+'" value="0"/></td>'
        +'<td><input type="number" name="qty_req_'+n+'" class="form-control input-sm qty-input qty-r" data-row="'+n+'" min="1" value="1"/></td>'
        +'<td class="text-right"><span class="hpp-disp" data-row="'+n+'">0</span></td>'
        +'<td class="text-right sub-disp" data-row="'+n+'">0</td>'
        +'<td class="text-center"><button type="button" class="btn btn-xs btn-danger btn-hapus" data-row="'+n+'"><i class="fa fa-trash"></i></button></td>'
        +'</tr>';
}

$(document).on('blur','.kode-input',function(){ lookupItem($(this).data('row')); });
$(document).on('keydown','.kode-input',function(e){ if(e.key==='Enter'||e.key==='Tab') lookupItem($(this).data('row')); });
$(document).on('input change','.qty-r',function(){ hitungSubtotal($(this).data('row')); });

$('#btnTambahBaris').on('click',function(){
    rowCount++;
    $('#tbodyBarang').append(makeRow(rowCount));
    $('#item_count').val(rowCount);
    renumber();
});

$(document).on('click','.btn-hapus',function(){
    if($('#tbodyBarang tr').length<=1){ alert('Minimal 1 baris barang.'); return; }
    $('#row_'+$(this).data('row')).remove();
    renumber();
    hitungTotal();
});

function renumber(){
    $('#tbodyBarang tr').each(function(i){ $(this).find('.row-no').text(i+1); });
}
</script>
</body>
</html>
