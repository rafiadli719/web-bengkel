<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

// User info
$quser = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".$id_user."'");
$u = mysqli_fetch_assoc($quser);
$_nama = $u ? $u['nama_user'] : '';
$foto_user = ($u && $u['foto_user']) ? $u['foto_user'] : 'file_upload/avatar.png';

function get($k,$d=''){return isset($_GET[$k])?trim($_GET[$k]):$d;}

$status = get('status','');
$from = get('from',''); // yyyy-mm-dd
$to   = get('to','');   // yyyy-mm-dd
$q    = get('q','');     // search no_do/no_po
$limit = (int)(get('limit','20')); if($limit<=0||$limit>200) $limit=20;
$page = (int)(get('page','1')); if($page<=0) $page=1; $offset = ($page-1)*$limit;

$where = ["doh.kd_cabang='".mysqli_real_escape_string($koneksi,$kd_cabang)."'"]; 
if($status!==''){ $where[] = "doh.status_do='".mysqli_real_escape_string($koneksi,$status)."'"; }
if($from!==''){ $where[] = "doh.tanggal_do >= '".mysqli_real_escape_string($koneksi,$from)."'"; }
if($to!==''){ $where[] = "doh.tanggal_do <= '".mysqli_real_escape_string($koneksi,$to)."'"; }
if($q!==''){
    $qs = mysqli_real_escape_string($koneksi,$q);
    $where[] = "(doh.no_do LIKE '%$qs%' OR doh.no_po LIKE '%$qs%')";
}
$wsql = implode(' AND ', $where);

// Count
$cnt = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tbldelivery_order_header doh WHERE $wsql");
$total_rows = (int)mysqli_fetch_assoc($cnt)['total'];
$total_pages = max(1, (int)ceil($total_rows/$limit));
if($page>$total_pages) $page=$total_pages; $offset = ($page-1)*$limit;

$sql = "SELECT doh.*, s.namasupplier 
        FROM tbldelivery_order_header doh 
        LEFT JOIN tblsupplier s ON s.nosupplier=doh.no_supplier 
        WHERE $wsql 
        ORDER BY doh.tanggal_do DESC, doh.no_do DESC 
        LIMIT $limit OFFSET $offset";
$rows = mysqli_query($koneksi, $sql);
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
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <script src="assets/js/ace-extra.min.js"></script>
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
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i><?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                        <span class="user-info"><small>Welcome,</small><?php echo $_nama; ?></span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                        <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i>Change Password</a></li>
                        <li><a href="profile.php"><i class="ace-icon fa fa-user"></i>Profile</a></li>
                        <li class="divider"></li>
                        <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
        <?php include "menu_pembelian02.php"; ?>
        <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="#">Pembelian</a></li>
                    <li class="active">Daftar Delivery Order</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row"><div class="col-xs-12">
                    <div class="well" style="padding:10px;">
                        <form class="form-inline" method="get">
                            <div class="form-group"><label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">- Semua -</option>
                                    <?php
                                    $statuses = ['draft','confirmed','in_transit','arrived','received','cancelled'];
                                    foreach($statuses as $st){
                                        $sel = ($status===$st)?'selected':'';
                                        echo "<option value=\"$st\" $sel>".ucfirst($st)."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Dari</label>
                                <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from); ?>" />
                            </div>
                            <div class="form-group"><label>Sampai</label>
                                <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>" />
                            </div>
                            <div class="form-group"><label>Cari</label>
                                <input type="text" name="q" class="form-control" placeholder="No. DO/PO" value="<?php echo htmlspecialchars($q); ?>" />
                            </div>
                            <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Filter</button>
                            <a class="btn btn-success" href="do_from_po.php"><i class="fa fa-plus"></i> Buat DO dari PO</a>
                        </form>
                    </div>
                    <div class="table-header">Hasil: <?php echo (int)$total_rows; ?> data</div>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No. DO</th>
                                <th>No. PO</th>
                                <th>Tanggal DO</th>
                                <th>Supplier</th>
                                <th class="text-right">Total Qty</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($r = mysqli_fetch_assoc($rows)){ ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['no_do']); ?></td>
                                <td><?php echo htmlspecialchars($r['no_po']); ?></td>
                                <td><?php echo htmlspecialchars($r['tanggal_do']); ?></td>
                                <td><?php echo htmlspecialchars($r['namasupplier']); ?></td>
                                <td class="text-right"><?php echo (int)$r['total_qty']; ?></td>
                                <td><?php echo htmlspecialchars($r['status_do']); ?></td>
                                <td>
                                    <a class="btn btn-minier btn-primary" href="do_detail.php?no_do=<?php echo urlencode($r['no_do']); ?>">Detail</a>
                                    <a class="btn btn-minier btn-success" href="pembelian_add.php?do=<?php echo urlencode($r['no_do']); ?>">Buat Invoice</a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <?php if($total_pages>1){ ?>
                    <nav aria-label="Page navigation" class="text-center">
                        <ul class="pagination">
                            <?php if($page>1){ $p=$page-1; echo '<li><a href="?'.http_build_query(['status'=>$status,'from'=>$from,'to'=>$to,'q'=>$q,'limit'=>$limit,'page'=>$p]).'">&laquo;</a></li>'; } ?>
                            <?php for($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++){ $act = ($i==$page)?'class="active"':''; echo "<li $act><a href=\"?".http_build_query(['status'=>$status,'from'=>$from,'to'=>$to,'q'=>$q,'limit'=>$limit,'page'=>$i])."\">$i</a></li>"; } ?>
                            <?php if($page<$total_pages){ $p=$page+1; echo '<li><a href="?'.http_build_query(['status'=>$status,'from'=>$from,'to'=>$to,'q'=>$q,'limit'=>$limit,'page'=>$p]).'">&raquo;</a></li>'; } ?>
                        </ul>
                    </nav>
                    <?php } ?>
                </div></div>
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
