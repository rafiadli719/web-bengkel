<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
include "../config/koneksi.php";

// Get user info
$qu = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$ru = mysqli_fetch_assoc($qu);
$_nama = $ru ? $ru['nama_user'] : 'system';

date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s');

$no_po = isset($_POST['no_po']) ? trim($_POST['no_po']) : '';
$action = isset($_POST['action_type']) ? trim($_POST['action_type']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if($no_po===''){
    header('Location: pesanan_pembelian.php');
    exit;
}

// Normalize current status
$qh = mysqli_query($koneksi, "SELECT status_approval FROM tblorder_header WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
$h = mysqli_fetch_assoc($qh);
$curr = $h ? $h['status_approval'] : 'draft';

if($action==='submit'){
    // Move to pending if currently draft or rejected
    mysqli_query($koneksi, "UPDATE tblorder_header SET status_approval='pending', approved_by=NULL, approved_date=NULL, rejected_by=NULL, rejected_date=NULL, reject_reason=NULL WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
}
elseif($action==='approve'){
    // Approve only if pending
    if($curr==='pending' || $curr==='draft'){
        mysqli_query($koneksi, "UPDATE tblorder_header SET status_approval='approved', approved_by='".mysqli_real_escape_string($koneksi,$_nama)."', approved_date='".$now."', rejected_by=NULL, rejected_date=NULL, reject_reason=NULL WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
        mysqli_query($koneksi, "INSERT INTO tblpo_approval_log (no_po, level_approval, approver, action, notes, action_date) VALUES ('".mysqli_real_escape_string($koneksi,$no_po)."', 1, '".mysqli_real_escape_string($koneksi,$_nama)."', 'approved', '".mysqli_real_escape_string($koneksi,$notes)."', '".$now."')");
    }
}
elseif($action==='reject'){
    // Reject only if pending
    if($curr==='pending' || $curr==='draft'){
        mysqli_query($koneksi, "UPDATE tblorder_header SET status_approval='rejected', rejected_by='".mysqli_real_escape_string($koneksi,$_nama)."', rejected_date='".$now."', reject_reason='".mysqli_real_escape_string($koneksi,$notes)."' WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
        mysqli_query($koneksi, "INSERT INTO tblpo_approval_log (no_po, level_approval, approver, action, notes, action_date) VALUES ('".mysqli_real_escape_string($koneksi,$no_po)."', 1, '".mysqli_real_escape_string($koneksi,$_nama)."', 'rejected', '".mysqli_real_escape_string($koneksi,$notes)."', '".$now."')");
    }
}

header('Location: pesanan_pembelian_detail.php?nopesanan='.urlencode($no_po));
exit;
