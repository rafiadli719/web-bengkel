<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    $stmt=mysqli_prepare($koneksi,"SELECT nama_user, foto_user FROM tbuser WHERE id=?");
    mysqli_stmt_bind_param($stmt,"s",$id_user);
    mysqli_stmt_execute($stmt);
    $tm_cari=mysqli_fetch_array(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $_nama=$tm_cari['nama_user'];
    $foto_user = $tm_cari['foto_user'] ?? 'file_upload/avatar.png';
    if (empty($foto_user)) $foto_user = "file_upload/avatar.png";

    $pesan = '';

    // --- Handler simpan (tambah/edit) ---
    if(isset($_POST['btnsimpan'])){
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nama_level = trim($_POST['nama_level']);
        $batas_bawah = str_replace(['.',','], ['',''], trim($_POST['batas_bawah']));
        $batas_atas_raw = trim($_POST['batas_atas']);
        $batas_atas = ($batas_atas_raw==='') ? null : str_replace(['.',','], ['',''], $batas_atas_raw);
        $kode_posisi = trim($_POST['kode_posisi']);
        $level_approval = (int)$_POST['level_approval'];
        $urutan = (int)$_POST['urutan'];
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if($id > 0){
            $stmt=mysqli_prepare($koneksi,"UPDATE tb_master_approval_pembelian
                                            SET level_approval=?, nama_level=?, batas_bawah=?, batas_atas=?, kode_posisi=?, urutan=?, aktif=?
                                            WHERE id=?");
            mysqli_stmt_bind_param($stmt,"isddsiii",$level_approval,$nama_level,$batas_bawah,$batas_atas,$kode_posisi,$urutan,$aktif,$id);
        } else {
            $stmt=mysqli_prepare($koneksi,"INSERT INTO tb_master_approval_pembelian
                                            (level_approval, nama_level, batas_bawah, batas_atas, kode_posisi, urutan, aktif)
                                            VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt,"isddsii",$level_approval,$nama_level,$batas_bawah,$batas_atas,$kode_posisi,$urutan,$aktif);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("location:master-approval-pembelian.php?saved=1");
        exit;
    }

    // --- Handler hapus ---
    if(isset($_GET['hapus'])){
        $id = (int)$_GET['hapus'];
        $stmt=mysqli_prepare($koneksi,"DELETE FROM tb_master_approval_pembelian WHERE id=?");
        mysqli_stmt_bind_param($stmt,"i",$id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("location:master-approval-pembelian.php?deleted=1");
        exit;
    }

    // --- Data untuk form edit ---
    $edit_row = null;
    if(isset($_GET['edit'])){
        $id = (int)$_GET['edit'];
        $stmt=mysqli_prepare($koneksi,"SELECT * FROM tb_master_approval_pembelian WHERE id=?");
        mysqli_stmt_bind_param($stmt,"i",$id);
        mysqli_stmt_execute($stmt);
        $edit_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }

    // --- Data posisi untuk dropdown ---
    $list_posisi = mysqli_query($koneksi,"SELECT kode_posisi, nama_posisi FROM tb_master_posisi WHERE is_active='active' ORDER BY user_akses_level ASC");

    // --- List semua tier ---
    $list_tier = mysqli_query($koneksi,"SELECT a.*, p.nama_posisi
                                        FROM tb_master_approval_pembelian a
                                        LEFT JOIN tb_master_posisi p ON p.kode_posisi = a.kode_posisi
                                        ORDER BY a.urutan ASC");
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
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
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
                <a href="index.php" class="navbar-brand"><small><?php include "../lib/subtitel.php"; ?></small></a>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Pembelian</a></li>
                        <li class="active">Master Approval Bertingkat PO</li>
                    </ul>
                </div>

                <div class="page-content">
                    <h4 class="header blue"><i class="fa fa-sitemap"></i> Master Approval Bertingkat PO</h4>
                    <small><i class="ace-icon fa fa-angle-double-right"></i> Atur bracket nominal PO dan posisi minimal yang boleh approve</small>

                    <?php if(isset($_GET['saved'])): ?>
                        <div class="alert alert-success">Data berhasil disimpan.</div>
                    <?php endif; ?>
                    <?php if(isset($_GET['deleted'])): ?>
                        <div class="alert alert-success">Data berhasil dihapus.</div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xs-12 col-sm-5">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-<?php echo $edit_row ? 'edit' : 'plus'; ?>"></i> <?php echo $edit_row ? 'Edit' : 'Tambah'; ?> Level Approval</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="POST" action="master-approval-pembelian.php">
                                            <input type="hidden" name="id" value="<?php echo $edit_row ? (int)$edit_row['id'] : 0; ?>">
                                            <div class="form-group">
                                                <label>Level Approval (urutan angka)</label>
                                                <input type="number" name="level_approval" class="form-control" required value="<?php echo $edit_row ? htmlspecialchars($edit_row['level_approval']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Level</label>
                                                <input type="text" name="nama_level" class="form-control" required value="<?php echo $edit_row ? htmlspecialchars($edit_row['nama_level']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Batas Bawah (Rp)</label>
                                                <input type="text" name="batas_bawah" class="form-control" required value="<?php echo $edit_row ? htmlspecialchars($edit_row['batas_bawah']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Batas Atas (Rp) — kosongkan untuk unlimited</label>
                                                <input type="text" name="batas_atas" class="form-control" value="<?php echo ($edit_row && $edit_row['batas_atas']!==null) ? htmlspecialchars($edit_row['batas_atas']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Posisi Minimal yang Boleh Approve</label>
                                                <select name="kode_posisi" class="form-control" required>
                                                    <option value="">-- pilih posisi --</option>
                                                    <?php mysqli_data_seek($list_posisi, 0); while($p = mysqli_fetch_assoc($list_posisi)): ?>
                                                        <option value="<?php echo htmlspecialchars($p['kode_posisi']); ?>" <?php echo ($edit_row && $edit_row['kode_posisi']==$p['kode_posisi']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($p['nama_posisi']." (".$p['kode_posisi'].")"); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Urutan Pengecekan</label>
                                                <input type="number" name="urutan" class="form-control" required value="<?php echo $edit_row ? htmlspecialchars($edit_row['urutan']) : ''; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" name="aktif" value="1" <?php echo (!$edit_row || $edit_row['aktif']=='1') ? 'checked' : ''; ?>> Aktif
                                                </label>
                                            </div>
                                            <button type="submit" name="btnsimpan" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                                            <?php if($edit_row): ?>
                                                <a href="master-approval-pembelian.php" class="btn btn-default">Batal</a>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-7">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-list"></i> Daftar Bracket Approval</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Urutan</th>
                                                    <th>Nama Level</th>
                                                    <th>Batas Bawah</th>
                                                    <th>Batas Atas</th>
                                                    <th>Posisi Min.</th>
                                                    <th>Aktif</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($row = mysqli_fetch_assoc($list_tier)): ?>
                                                <tr>
                                                    <td><?php echo (int)$row['urutan']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['nama_level']); ?></td>
                                                    <td align="right"><?php echo number_format($row['batas_bawah'],0); ?></td>
                                                    <td align="right"><?php echo $row['batas_atas']===null ? 'Unlimited' : number_format($row['batas_atas'],0); ?></td>
                                                    <td><?php echo htmlspecialchars($row['nama_posisi'] ?? $row['kode_posisi']); ?></td>
                                                    <td><?php echo $row['aktif']=='1' ? '<span class="label label-success">Aktif</span>' : '<span class="label label-default">Nonaktif</span>'; ?></td>
                                                    <td>
                                                        <a href="master-approval-pembelian.php?edit=<?php echo (int)$row['id']; ?>" class="btn btn-xs btn-info"><i class="fa fa-edit"></i></a>
                                                        <a href="master-approval-pembelian.php?hapus=<?php echo (int)$row['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Hapus bracket ini?');"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
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
<?php
}
?>
