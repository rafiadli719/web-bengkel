# Implementation Guide - PR PO DO Module

## Panduan Implementasi Lengkap

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Database Setup](#2-database-setup)
3. [Fix Existing Files](#3-fix-existing-files)
4. [Create Missing Files](#4-create-missing-files)
5. [Update Menu](#5-update-menu)
6. [Testing](#6-testing)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Prerequisites

### 1.1 Requirements
- ✅ XAMPP/WAMP/LAMP dengan PHP 7.4+
- ✅ MySQL/MariaDB 5.7+
- ✅ Database `fitmotor_dbbengkel` sudah ada
- ✅ User dengan akses ke `aplikasi/_admincab` folder
- ✅ Browser modern (Chrome, Firefox, Edge)

### 1.2 Backup Database
**SANGAT PENTING**: Backup database sebelum migrasi!

```bash
# Via command line
cd C:\xampp\mysql\bin
mysqldump -u root -p fitmotor_dbbengkel > backup_before_pr_po_do.sql

# Atau via phpMyAdmin:
# 1. Buka http://localhost/phpmyadmin
# 2. Pilih database fitmotor_dbbengkel
# 3. Tab "Export" → Quick Export → Go
```

---

## 2. Database Setup

### 2.1 Run Migration Script

**Option A: Via phpMyAdmin**
```
1. Buka http://localhost/phpmyadmin
2. Pilih database: fitmotor_dbbengkel
3. Tab "SQL"
4. Copy-paste isi file: pr_po_do_migration.sql
5. Klik "Go"
6. Check for errors
```

**Option B: Via Command Line**
```bash
cd C:\xampp\htdocs\web-bengkel
mysql -u root -p fitmotor_dbbengkel < pr_po_do_migration.sql
```

### 2.2 Verify Migration

Jalankan query berikut untuk memastikan semua berhasil:

```sql
-- Check tables
SHOW TABLES LIKE 'tbldelivery_order%';
SHOW TABLES LIKE 'tblpr%';
SHOW TABLES LIKE 'tbldo_tracking';

-- Check stored procedures
SHOW PROCEDURE STATUS WHERE Db = 'fitmotor_dbbengkel' AND Name LIKE 'sp_generate_no_%';

-- Check views
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Test stored procedures
CALL sp_generate_no_pr('001', @test_pr);
SELECT @test_pr;
-- Expected: PR251101001 atau similar

CALL sp_generate_no_do('001', @test_do);
SELECT @test_do;
-- Expected: DO251101001 atau similar

CALL sp_generate_no_po(@test_po);
SELECT @test_po;
-- Expected: PS2500000001 atau similar
```

**Expected Output**:
```
✅ tbldelivery_order_header exists
✅ tbldelivery_order_detail exists
✅ tbldo_tracking exists
✅ sp_generate_no_pr exists
✅ sp_generate_no_do exists
✅ sp_generate_no_po exists
✅ v_pr_status exists
✅ v_po_status exists
✅ v_do_status exists
```

---

## 3. Fix Existing Files

### 3.1 Fix pr_add.php

File location: `aplikasi/_admincab/pr_add.php`

**ISSUE**: File ini menggunakan table name yang salah

**FIX**: Replace semua occurrence

```bash
# Find and replace (manual via text editor):
OLD: tblpurchase_request_header
NEW: tblpr_header

OLD: tblpurchase_request_detail
NEW: tblpr_detail
```

**Detailed Changes**:

**Line 51-54** (Original):
```php
mysqli_query($koneksi, "INSERT INTO tblpurchase_request_header
    (no_pr, tanggal_pr, tanggal_butuh, requester, departemen, alasan, kd_cabang, created_by)
    VALUES
    ('{$no_gen}', '{$tgl_now}', '{$tgl_now}', '{$_nama}', '', '', '{$kd_cabang}', '{$_nama}')");
```

**Line 51-54** (Fixed):
```php
mysqli_query($koneksi, "INSERT INTO tblpr_header
    (no_pr, tanggal, tanggal_butuh, pemohon, departemen, alasan, kd_cabang, created_by)
    VALUES
    ('{$no_gen}', '{$tgl_now}', '{$tgl_now}', '{$_nama}', '', '', '{$kd_cabang}', '{$_nama}')");
```

**Other lines to fix**:
- Line ~80: tblpurchase_request_detail → tblpr_detail
- Line ~100+: All SELECT queries
- Line ~150+: All INSERT queries
- Line ~200+: All DELETE queries

**ALSO**: Update column names:
- `tanggal_pr` → `tanggal`
- `requester` → `pemohon`

### 3.2 Fix pesanan_pembelian_add.php

File location: `aplikasi/_admincab/pesanan_pembelian_add.php`

**ISSUE**: References `tblpurchase_request_detail` instead of `tblpr_detail`

**Lines to fix** (~52, ~192, ~238, ~252):
```php
OLD: tblpurchase_request_detail
NEW: tblpr_detail
```

### 3.3 Verify do_from_po.php

File location: `aplikasi/_admincab/do_from_po.php`

This file should already be correct (uses tbldelivery_order_header/detail).
But verify lines 99-102, 110-113, 118-119 use correct table names.

---

## 4. Create Missing Files

### 4.1 Create pr_list.php

File: `aplikasi/_admincab/pr_list.php`

```php
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

// Filters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

$where = "WHERE h.kd_cabang='".$kd_cabang."'";
if($filter_status != '') {
    $where .= " AND h.status='".mysqli_real_escape_string($koneksi, $filter_status)."'";
}
if($filter_bulan != '') {
    $where .= " AND DATE_FORMAT(h.tanggal, '%Y-%m')='".mysqli_real_escape_string($koneksi, $filter_bulan)."'";
}

$query = "SELECT h.*,
    (SELECT COUNT(*) FROM tblpr_detail d WHERE d.no_pr=h.no_pr) as jml_item,
    (SELECT SUM(qty_req) FROM tblpr_detail d WHERE d.no_pr=h.no_pr) as total_qty
    FROM tblpr_header h
    $where
    ORDER BY h.tanggal DESC, h.no_pr DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
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
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User" />
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
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="#">Pembelian</a></li>
                    <li class="active">Purchase Request (PR)</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row"><div class="col-xs-12">
                    <div class="widget-box">
                        <div class="widget-header widget-header-blue widget-header-flat">
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-list"></i> Daftar Purchase Request</h4>
                            <div class="widget-toolbar">
                                <a href="pr_add.php" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Buat PR Baru</a>
                            </div>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <form method="get" class="form-inline">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control input-sm">
                                        <option value="">Semua</option>
                                        <option value="draft" <?php echo $filter_status=='draft'?'selected':''; ?>>Draft</option>
                                        <option value="submitted" <?php echo $filter_status=='submitted'?'selected':''; ?>>Submitted</option>
                                        <option value="approved" <?php echo $filter_status=='approved'?'selected':''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $filter_status=='rejected'?'selected':''; ?>>Rejected</option>
                                        <option value="partially_ordered" <?php echo $filter_status=='partially_ordered'?'selected':''; ?>>Partially Ordered</option>
                                        <option value="fully_ordered" <?php echo $filter_status=='fully_ordered'?'selected':''; ?>>Fully Ordered</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Bulan:</label>
                                    <input type="month" name="bulan" class="form-control input-sm" value="<?php echo htmlspecialchars($filter_bulan); ?>" />
                                </div>
                                <button type="submit" class="btn btn-info btn-sm"><i class="fa fa-search"></i> Filter</button>
                                <a href="pr_list.php" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Reset</a>
                            </form>
                            <hr/>
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No PR</th>
                                        <th>Tanggal</th>
                                        <th>Pemohon</th>
                                        <th>Jumlah Item</th>
                                        <th>Total Qty</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['no_pr']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['pemohon']); ?></td>
                                        <td class="text-right"><?php echo (int)$row['jml_item']; ?></td>
                                        <td class="text-right"><?php echo (int)$row['total_qty']; ?></td>
                                        <td>
                                            <?php
                                            $status = $row['status'];
                                            $badge = 'default';
                                            if($status=='approved') $badge = 'success';
                                            elseif($status=='submitted') $badge = 'warning';
                                            elseif($status=='rejected') $badge = 'danger';
                                            elseif($status=='fully_ordered') $badge = 'info';
                                            echo '<span class="label label-'.$badge.'">'.strtoupper($status).'</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="pr_detail.php?no_pr=<?php echo urlencode($row['no_pr']); ?>" class="btn btn-xs btn-info" title="Detail"><i class="fa fa-eye"></i></a>
                                            <?php if($status == 'approved'){ ?>
                                            <a href="pesanan_pembelian_add.php?pr=<?php echo urlencode($row['no_pr']); ?>" class="btn btn-xs btn-success" title="Buat PO"><i class="fa fa-shopping-cart"></i> PO</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div></div>
                    </div>
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
```

### 4.2 Create do_list.php

File: `aplikasi/_admincab/do_list.php`

```php
<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

$quser = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".$id_user."'");
$u = mysqli_fetch_assoc($quser);
$_nama = $u ? $u['nama_user'] : '';
$foto_user = ($u && $u['foto_user']) ? $u['foto_user'] : 'file_upload/avatar.png';

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

$where = "WHERE h.kd_cabang='".$kd_cabang."'";
if($filter_status != '') {
    $where .= " AND h.status_do='".mysqli_real_escape_string($koneksi, $filter_status)."'";
}
if($filter_bulan != '') {
    $where .= " AND DATE_FORMAT(h.tanggal_do, '%Y-%m')='".mysqli_real_escape_string($koneksi, $filter_bulan)."'";
}

$query = "SELECT h.*,
    s.nama_supplier,
    (SELECT COUNT(*) FROM tbldelivery_order_detail d WHERE d.no_do=h.no_do) as jml_item
    FROM tbldelivery_order_header h
    LEFT JOIN tbsupplier s ON h.no_supplier = s.no_supplier
    $where
    ORDER BY h.tanggal_do DESC, h.no_do DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
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
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User" />
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
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <?php include "menu_pembelian02.php"; ?>
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
                    <li class="active">Delivery Order (DO)</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row"><div class="col-xs-12">
                    <div class="widget-box">
                        <div class="widget-header widget-header-blue widget-header-flat">
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-truck"></i> Daftar Delivery Order</h4>
                            <div class="widget-toolbar">
                                <a href="do_from_po.php" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Buat DO dari PO</a>
                            </div>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <form method="get" class="form-inline">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control input-sm">
                                        <option value="">Semua</option>
                                        <option value="draft" <?php echo $filter_status=='draft'?'selected':''; ?>>Draft</option>
                                        <option value="in_transit" <?php echo $filter_status=='in_transit'?'selected':''; ?>>In Transit</option>
                                        <option value="received" <?php echo $filter_status=='received'?'selected':''; ?>>Received</option>
                                        <option value="posted" <?php echo $filter_status=='posted'?'selected':''; ?>>Posted</option>
                                        <option value="closed" <?php echo $filter_status=='closed'?'selected':''; ?>>Closed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Bulan:</label>
                                    <input type="month" name="bulan" class="form-control input-sm" value="<?php echo htmlspecialchars($filter_bulan); ?>" />
                                </div>
                                <button type="submit" class="btn btn-info btn-sm"><i class="fa fa-search"></i> Filter</button>
                                <a href="do_list.php" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Reset</a>
                            </form>
                            <hr/>
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No DO</th>
                                        <th>Tanggal</th>
                                        <th>No PO</th>
                                        <th>Supplier</th>
                                        <th>Jml Item</th>
                                        <th>Total Qty</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['no_do']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_do'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['no_po']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_supplier']); ?></td>
                                        <td class="text-right"><?php echo (int)$row['jml_item']; ?></td>
                                        <td class="text-right"><?php echo (int)$row['total_qty']; ?></td>
                                        <td>
                                            <?php
                                            $status = $row['status_do'];
                                            $badge = 'default';
                                            if($status=='received') $badge = 'success';
                                            elseif($status=='in_transit') $badge = 'warning';
                                            elseif($status=='posted') $badge = 'info';
                                            echo '<span class="label label-'.$badge.'">'.strtoupper($status).'</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($status == 'received'){ ?>
                                            <a href="pembelian_add.php?do=<?php echo urlencode($row['no_do']); ?>" class="btn btn-xs btn-primary" title="Buat Invoice"><i class="fa fa-file-text"></i> Invoice</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div></div>
                    </div>
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
```

---

## 5. Update Menu

### 5.1 Edit menu_pembelian02.php

File: `aplikasi/_admincab/menu_pembelian02.php`

Add these menu items in the appropriate section:

```php
<li class="">
    <a href="#" class="dropdown-toggle">
        <i class="menu-icon fa fa-shopping-cart"></i>
        <span class="menu-text">Procurement</span>
        <b class="arrow fa fa-angle-down"></b>
    </a>
    <b class="arrow"></b>
    <ul class="submenu">
        <li class="">
            <a href="pr_list.php">
                <i class="menu-icon fa fa-file-text-o"></i>
                Purchase Request (PR)
            </a>
            <b class="arrow"></b>
        </li>
        <li class="">
            <a href="pesanan_pembelian.php">
                <i class="menu-icon fa fa-shopping-cart"></i>
                Purchase Order (PO)
            </a>
            <b class="arrow"></b>
        </li>
        <li class="">
            <a href="do_list.php">
                <i class="menu-icon fa fa-truck"></i>
                Delivery Order (DO)
            </a>
            <b class="arrow"></b>
        </li>
        <li class="">
            <a href="pembelian.php">
                <i class="menu-icon fa fa-file-text"></i>
                Invoice Pembelian
            </a>
            <b class="arrow"></b>
        </li>
    </ul>
</li>
```

---

## 6. Testing

### 6.1 Test Database Migration

```sql
-- 1. Test PR number generation
CALL sp_generate_no_pr('001', @pr_no);
SELECT @pr_no;
-- Expected: PR251101001

-- 2. Test DO number generation
CALL sp_generate_no_do('001', @do_no);
SELECT @do_no;
-- Expected: DO251101001

-- 3. Check views
SELECT * FROM v_pr_status;
SELECT * FROM v_po_status;
SELECT * FROM v_do_status;
```

### 6.2 Test PR Flow

**Step 1: Create PR**
1. Login ke aplikasi
2. Menu: Procurement → Purchase Request (PR)
3. Klik "Buat PR Baru"
4. Isi form:
   - Tanggal kebutuhan
   - Departemen
   - Alasan
5. Add item (gunakan typeahead search)
6. Qty: 10
7. Klik "Submit PR"

**Verify**:
```sql
SELECT * FROM tblpr_header ORDER BY created_at DESC LIMIT 1;
SELECT * FROM tblpr_detail WHERE no_pr='PR251101001';
-- Status should be: 'submitted'
```

**Step 2: Approve PR**
1. Update manual di database (approval page belum dibuat):
```sql
UPDATE tblpr_header
SET status='approved', approved_by='Manager', approved_at=NOW()
WHERE no_pr='PR251101001';
```

**Step 3: Create PO from PR**
1. Menu: Procurement → Purchase Request (PR)
2. Find approved PR
3. Klik button "PO"
4. Akan redirect ke pesanan_pembelian_add.php?pr=PR251101001
5. Items otomatis ter-load dari PR
6. Pilih supplier
7. Input harga
8. Simpan

**Verify**:
```sql
SELECT * FROM tblorder_header WHERE no_pr='PR251101001';
SELECT * FROM tblorder_detail WHERE no_order='PS2500000001';
SELECT * FROM tblpr_detail WHERE no_pr='PR251101001';
-- qty_po should be updated
SELECT * FROM tblpr_header WHERE no_pr='PR251101001';
-- status should be 'fully_ordered'
```

### 6.3 Test DO Flow

**Step 1: Create DO from PO**
1. Menu: Procurement → Delivery Order (DO)
2. Klik "Buat DO dari PO"
3. Input No PO: PS2500000001
4. Klik "Load PO"
5. Input Qty Kirim & Qty Terima
6. Klik "Simpan & Terima"

**Verify**:
```sql
SELECT * FROM tbldelivery_order_header ORDER BY created_at DESC LIMIT 1;
SELECT * FROM tbldelivery_order_detail WHERE no_do='DO251101001';
SELECT * FROM tbstok WHERE no_transaksi='DO251101001';
-- masuk should equal qty_terima
SELECT * FROM tblorder_detail WHERE no_order='PS2500000001';
-- qty_terima should be updated
```

### 6.4 Test Invoice Flow

**Step 1: Create Invoice from DO**
1. Menu: Procurement → Delivery Order (DO)
2. Find DO with status 'received'
3. Klik button "Invoice"
4. Akan redirect ke pembelian_add.php?do=DO251101001
5. Items otomatis ter-load dari DO
6. Input harga (if not from PO)
7. Simpan

**Verify**:
```sql
SELECT * FROM tblpembelian_header WHERE no_do='DO251101001';
SELECT * FROM tblpembelian_detail WHERE no_transaksi='BL2500000001';
SELECT * FROM tbldelivery_order_header WHERE no_do='DO251101001';
-- status_do should be 'posted'
SELECT * FROM tbstok WHERE no_transaksi='BL2500000001';
-- Should NOT have new entry (stock already posted at DO)
```

---

## 7. Troubleshooting

### 7.1 Migration Errors

**Error**: "Table already exists"
```
Solution: The migration script uses CREATE TABLE IF NOT EXISTS.
This is safe. Ignore the warning.
```

**Error**: "Procedure already exists"
```
Solution: The script drops procedures first (DROP PROCEDURE IF EXISTS).
Re-run the migration script.
```

**Error**: "Unknown column"
```
Solution: Check table structure:
DESCRIBE tblpr_header;
DESCRIBE tblpr_detail;

If columns missing, run ALTER TABLE manually from migration script.
```

### 7.2 PR Issues

**Issue**: "Table 'tblpurchase_request_header' doesn't exist"
```
Solution: pr_add.php not fixed yet.
Fix all occurrences of tblpurchase_request_* → tblpr_*
```

**Issue**: "PR status not updating to fully_ordered"
```
Solution: Check pesanan_pembelian_add.php lines 237-260.
Ensure UPDATE query for tblpr_detail runs.
Add debug:
echo "Updated PR qty_po for item: $no_item";
```

### 7.3 DO Issues

**Issue**: "Qty validation fails"
```
Solution:
1. Check PO has qty_terima column:
   ALTER TABLE tblorder_detail ADD COLUMN qty_terima INT DEFAULT 0;

2. Check do_from_po.php lines 65-81 validation logic
```

**Issue**: "Stock not updating"
```
Solution: Check tbstok table:
SELECT * FROM tbstok WHERE no_transaksi='DO251101001';

If empty, check do_from_po.php line 117-120.
Ensure INSERT query runs.
```

**Issue**: "Duplicate stock posting"
```
Solution:
- Stock should ONLY post at DO (if DO exists)
- Or at Invoice (if NO DO)
- Check pembelian_add.php: should skip stock posting if no_do != ''
```

### 7.4 Stored Procedure Issues

**Issue**: "Procedure not found"
```sql
Solution: Check procedure exists:
SHOW PROCEDURE STATUS WHERE Db='fitmotor_dbbengkel';

If not found, re-run migration script section 3.
```

**Issue**: "Number generation returns NULL"
```sql
Solution:
1. Check table exists:
   SHOW TABLES LIKE 'tblpr_header';

2. Test manually:
   CALL sp_generate_no_pr('001', @test);
   SELECT @test;

3. Check for syntax errors in procedure.
```

### 7.5 Menu Not Showing

**Issue**: "Menu PR/DO not visible"
```
Solution:
1. Check menu_pembelian02.php has been updated
2. Clear browser cache (Ctrl+F5)
3. Check user access level (user_akses)
```

---

## 8. Rollback Plan

If migration fails and you need to rollback:

```sql
-- 1. Restore from backup
-- Via command line:
mysql -u root -p fitmotor_dbbengkel < backup_before_pr_po_do.sql

-- 2. Or drop newly created objects manually:
DROP TABLE IF EXISTS tbldelivery_order_header;
DROP TABLE IF EXISTS tbldelivery_order_detail;
DROP TABLE IF EXISTS tbldo_tracking;
DROP TABLE IF EXISTS tblpr_tracking;
DROP VIEW IF EXISTS v_pr_status;
DROP VIEW IF EXISTS v_po_status;
DROP VIEW IF EXISTS v_do_status;
DROP PROCEDURE IF EXISTS sp_generate_no_pr;
DROP PROCEDURE IF EXISTS sp_generate_no_do;
DROP PROCEDURE IF EXISTS sp_generate_no_po;

-- 3. Revert code changes
-- Use git to restore pr_add.php, pesanan_pembelian_add.php
```

---

## 9. Post-Implementation Checklist

- [ ] Database migration completed successfully
- [ ] Stored procedures tested and working
- [ ] pr_add.php fixed (table names)
- [ ] pesanan_pembelian_add.php fixed (table names)
- [ ] pr_list.php created
- [ ] do_list.php created
- [ ] Menu updated
- [ ] Test Case 1: Full PR→PO→DO→Invoice passed
- [ ] Test Case 2: Partial PO from PR passed
- [ ] Test Case 3: Multiple DO from PO passed
- [ ] Stock posting verified (no duplicates)
- [ ] User training completed
- [ ] Documentation delivered

---

## 10. Next Steps (Optional Enhancements)

### Phase 2 Features:
1. **PR Approval Workflow**
   - Create pr_approve.php for manager approval
   - Email notifications

2. **DO Tracking**
   - Create do_tracking.php
   - Show delivery status history
   - GPS integration (future)

3. **Reports**
   - PR Aging Report
   - PO Outstanding Report
   - DO Pending Invoice Report

4. **API Integration**
   - Supplier portal for DO updates
   - Mobile app for DO receipt

---

**END OF IMPLEMENTATION GUIDE**

Jika ada error atau kendala, silakan hubungi technical support dengan menyertakan:
1. Error message lengkap
2. Screenshot
3. Query yang error (dari MySQL log)
4. Versi PHP dan MySQL
