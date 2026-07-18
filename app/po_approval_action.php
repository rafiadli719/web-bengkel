<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
include "../config/koneksi.php";

// Get user info + posisi (untuk cek level approval bertingkat)
$stmt = mysqli_prepare($koneksi, "SELECT nama_user, kode_posisi FROM tbuser WHERE id=?");
mysqli_stmt_bind_param($stmt, "s", $id_user);
mysqli_stmt_execute($stmt);
$ru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$_nama = $ru ? $ru['nama_user'] : 'system';
$_kode_posisi_user = $ru ? $ru['kode_posisi'] : null;

date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s');

$no_po = isset($_POST['no_po']) ? trim($_POST['no_po']) : '';
$action = isset($_POST['action_type']) ? trim($_POST['action_type']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if($no_po===''){
    header('Location: pesanan_pembelian.php');
    exit;
}

// Ambil status & total_order saat ini (dasar cek bracket approval bertingkat)
$stmt = mysqli_prepare($koneksi, "SELECT status_approval, total_order FROM tblorder_header WHERE no_order=?");
mysqli_stmt_bind_param($stmt, "s", $no_po);
mysqli_stmt_execute($stmt);
$h = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$curr = $h ? $h['status_approval'] : 'draft';
$total_order = $h ? (float)$h['total_order'] : 0;

if($action==='submit'){
    // Move to pending if currently draft or rejected
    $stmt = mysqli_prepare($koneksi, "UPDATE tblorder_header SET status_approval='pending', approved_by=NULL, approved_date=NULL, rejected_by=NULL, rejected_date=NULL, reject_reason=NULL WHERE no_order=?");
    mysqli_stmt_bind_param($stmt, "s", $no_po);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
elseif($action==='approve' || $action==='reject'){
    if($curr==='pending' || $curr==='draft'){
        // Cari bracket approval bertingkat sesuai nominal PO (tb_master_approval_pembelian)
        $stmt = mysqli_prepare($koneksi, "SELECT level_approval, nama_level, kode_posisi
                                            FROM tb_master_approval_pembelian
                                            WHERE aktif=1 AND batas_bawah<=?
                                            AND (batas_atas IS NULL OR batas_atas>=?)
                                            ORDER BY urutan DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "dd", $total_order, $total_order);
        mysqli_stmt_execute($stmt);
        $tier = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        $level_approval = 1;
        $boleh = true;
        if($tier){
            $level_approval = (int)$tier['level_approval'];
            // Administrator = full system access, selalu boleh approve semua tier.
            // Selain itu, posisi user harus PERSIS sama dengan posisi minimal bracket ini
            // (bukan perbandingan user_akses_level numerik - field itu ID kategori posisi,
            // bukan urutan otoritas asli, jadi tidak valid dipakai sebagai perbandingan "<=").
            if($_kode_posisi_user !== 'ADM' && $_kode_posisi_user !== $tier['kode_posisi']){
                $boleh = false;
            }
        }

        if(!$boleh){
            header('Location: pesanan_pembelian_detail.php?nopesanan='.urlencode($no_po).'&err='.urlencode('Posisi Anda tidak memenuhi syarat approval level '.$tier['nama_level'].' untuk PO senilai Rp'.number_format($total_order,0)));
            exit;
        }

        if($action==='approve'){
            $stmt = mysqli_prepare($koneksi, "UPDATE tblorder_header SET status_approval='approved', approved_by=?, approved_date=?, rejected_by=NULL, rejected_date=NULL, reject_reason=NULL WHERE no_order=?");
            mysqli_stmt_bind_param($stmt, "sss", $_nama, $now, $no_po);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($koneksi, "INSERT INTO tblpo_approval_log (no_po, level_approval, approver, action, notes, action_date) VALUES (?, ?, ?, 'approved', ?, ?)");
            mysqli_stmt_bind_param($stmt, "sisss", $no_po, $level_approval, $_nama, $notes, $now);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE tblorder_header SET status_approval='rejected', rejected_by=?, rejected_date=?, reject_reason=? WHERE no_order=?");
            mysqli_stmt_bind_param($stmt, "ssss", $_nama, $now, $notes, $no_po);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($koneksi, "INSERT INTO tblpo_approval_log (no_po, level_approval, approver, action, notes, action_date) VALUES (?, ?, ?, 'rejected', ?, ?)");
            mysqli_stmt_bind_param($stmt, "sisss", $no_po, $level_approval, $_nama, $notes, $now);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

header('Location: pesanan_pembelian_detail.php?nopesanan='.urlencode($no_po));
exit;
