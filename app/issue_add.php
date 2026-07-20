<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";
include_once "../lib/rbac.php";
error_reporting(E_ALL);
ini_set('display_errors', 0);
if(!isset($koneksi) || !$koneksi){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die('DB connection failed: '.mysqli_connect_error());
}
@mysqli_set_charset($koneksi, 'utf8mb4');
if(function_exists('mysqli_report')){ @mysqli_report(MYSQLI_REPORT_OFF); }

$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari && isset($tm_cari['nama_user']) ? $tm_cari['nama_user'] : 'admin';
$lvl_akses = $tm_cari && isset($tm_cari['user_akses']) ? $tm_cari['user_akses'] : '';
$foto_user = $tm_cari && isset($tm_cari['foto_user']) ? $tm_cari['foto_user'] : '';
if($foto_user=='') { $foto_user="file_upload/avatar.png"; }
$is_admin = ($lvl_akses == '1');
if(function_exists('rbac_has') && !$is_admin && !rbac_has('issue_read')){
    http_response_code(403);
    die('Anda tidak punya akses ke modul Lapor Masalah.');
}

function post($k, $d='') { return isset($_POST[$k]) ? trim($_POST[$k]) : $d; }
function get($k, $d='') { return isset($_GET[$k]) ? trim($_GET[$k]) : $d; }

$id_issue = get('id_issue');
$message = isset($_SESSION['issue_flash_message']) ? (string)$_SESSION['issue_flash_message'] : '';
$error_class = isset($_SESSION['issue_flash_class']) ? (string)$_SESSION['issue_flash_class'] : 'alert-info';
unset($_SESSION['issue_flash_message'], $_SESSION['issue_flash_class']);

$kategori_divisi = [
    'data_kendaraan' => 'CS',
    'komisi'         => 'Kasir',
    'stok'           => 'Gudang',
    'sistem'         => 'IT',
    'lainnya'        => 'IT',
];
$prioritas_opsi = ['low','medium','high','critical'];
$status_opsi = ['open','in_progress','waiting_approval','resolved','closed','rejected'];
$status_label = ['open'=>'Laporan baru masuk','in_progress'=>'Sedang diproses','waiting_approval'=>'Menunggu persetujuan atasan','resolved'=>'Sudah selesai diproses','closed'=>'Selesai dan ditutup','rejected'=>'Pengajuan ditolak'];
$status_class = ['open'=>'label-default','in_progress'=>'label-info','waiting_approval'=>'label-warning','resolved'=>'label-success','closed'=>'label-success','rejected'=>'label-danger'];

function issue_next_action_label($row){
    $status = $row['status'] ?? '';
    $divisi = $row['divisi_terkait'] ?? 'Tim terkait';
    $role = $row['role_approval'] ?? '';
    if($status === 'waiting_approval'){
        if($role === 'owner') return 'Menunggu Owner';
        return 'Menunggu Supervisor';
    }
    if($status === 'open') return 'Perlu dicek '.$divisi;
    if($status === 'in_progress') return 'Sedang dikerjakan '.$divisi;
    if($status === 'resolved') return 'Sudah selesai diproses';
    if($status === 'closed') return 'Selesai dan ditutup';
    if($status === 'rejected') return 'Lihat alasan penolakan';
    return '-';
}

function issue_priority_label($priority){
    $map = ['low'=>'Rendah','medium'=>'Sedang','high'=>'Tinggi','critical'=>'Kritis'];
    return $map[$priority] ?? ucfirst((string)$priority);
}


function issue_required_permission($issue_or_jenis){
    $target = '';
    $roleApproval = '';
    $kategori = '';
    $nama = '';
    if(is_array($issue_or_jenis)){
        $target = $issue_or_jenis['target_eksekusi'] ?? '';
        $roleApproval = $issue_or_jenis['role_approval'] ?? '';
        $kategori = $issue_or_jenis['kategori'] ?? '';
        $nama = $issue_or_jenis['nama_masalah'] ?? '';
    }
    if($target === 'revisi_komisi_pasca_bayar' || $target === 'koreksi_persen_kerja_mekanik' || $kategori === 'komisi'){
        return 'issue_approve_finansial';
    }
    if($target === 'koreksi_nopol_servis' || $kategori === 'data_kendaraan'){
        return 'issue_approve_data_kendaraan';
    }
    if($roleApproval === 'owner') return 'issue_approve_owner';
    if($roleApproval === 'supervisor') return 'issue_approve_supervisor';
    return null;
}

function user_can_approve_issue($issue_or_jenis, $is_admin){
    if($is_admin) return true;
    $perm = issue_required_permission($issue_or_jenis);
    if($perm === null) return false;
    return function_exists('rbac_has') ? rbac_has($perm) : false;
}

function is_auto_approval_issue($issue){
    if(!is_array($issue)) return false;
    return !empty($issue['target_eksekusi']);
}

function infer_legacy_issue_structure($koneksi, $issue){
    $out = [
        'nama_masalah' => $issue['nama_masalah'] ?? null,
        'payload_json' => $issue['payload_json'] ?? null,
        'detail_payload' => [],
        'jenis_detail' => null,
        'id_jenis_masalah' => $issue['id_jenis_masalah'] ?? null,
        'target_eksekusi' => $issue['target_eksekusi'] ?? null,
        'role_approval' => $issue['role_approval'] ?? null,
    ];
    if(!empty($issue['payload_json'])) return $out;
    $desc = (string)($issue['deskripsi'] ?? '');
    if(strpos($desc, '[Template:') !== 0) return $out;

    $lines = preg_split('/
|
|
/', $desc);
    $template = '';
    if(preg_match('/^\[Template:\s*(.*?)\]$/', trim($lines[0] ?? ''), $m)) $template = trim($m[1]);
    $map = [];
    foreach($lines as $ln){
        if(strpos($ln, ':') !== false){
            [$k,$v] = array_map('trim', explode(':', $ln, 2));
            $map[$k] = $v;
        }
    }

    $namaMap = [
        'Salah persentase Mekanik di servis' => 'Koreksi Persentase Kerja Mekanik',
        'Salah persentase Kepala Mekanik' => 'Koreksi Kepala Mekanik Salah Assign',
        'Salah persentase Admin 1/2 di servis' => 'Revisi Komisi Servis Pasca-Bayar',
        'Salah nominal jasa/barang di transaksi' => null,
    ];
    $namaBaru = $namaMap[$template] ?? null;
    if(!$namaBaru) return $out;

    $escNama = mysqli_real_escape_string($koneksi, $namaBaru);
    $qj = mysqli_query($koneksi, "SELECT * FROM master_jenis_masalah WHERE nama_masalah='{$escNama}' LIMIT 1");
    $jr = $qj ? mysqli_fetch_assoc($qj) : null;
    if(!$jr) return $out;

    $payload = [];
    if($namaBaru === 'Koreksi Persentase Kerja Mekanik'){
        $payload = [
            'no_service' => $map['No. Service'] ?? '',
            'mekanik_ke' => preg_match('/mekanik(\d+)/i', $map['Pihak'] ?? '', $mm) ? ($mm[1] ?? '1') : '1',
            'persen_lama' => str_replace('%','',$map['Persentase salah'] ?? ''),
            'persen_baru' => str_replace('%','',$map['Persentase benar'] ?? ''),
            'alasan' => $map['Catatan'] ?? ($map['Alasan'] ?? ''),
        ];
    } elseif($namaBaru === 'Revisi Komisi Servis Pasca-Bayar'){
        $payload = [
            'no_service' => $map['No. Service'] ?? '',
            'peran' => preg_match('/admin(\d+)/i', $map['Pihak'] ?? '', $mm) ? ('admin'.($mm[1] ?? '1')) : 'admin1',
            'persen_lama' => str_replace('%','',$map['Persentase salah'] ?? ''),
            'persen_baru' => str_replace('%','',$map['Persentase benar'] ?? ''),
            'alasan' => $map['Catatan'] ?? ($map['Alasan'] ?? ''),
        ];
    }

    $out['nama_masalah'] = $jr['nama_masalah'];
    $out['payload_json'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $out['detail_payload'] = $payload;
    $out['jenis_detail'] = json_decode($jr['skema_field'], true);
    $out['id_jenis_masalah'] = $jr['id_jenis'];
    $out['target_eksekusi'] = $jr['target_eksekusi'];
    $out['role_approval'] = $jr['role_approval'];
    return $out;
}

function eksekusi_revisi_komisi($koneksi, $id_issue, $payload, $id_user, $kd_cabang){
    $no_service  = $payload['no_service'] ?? '';
    $peran       = $payload['peran'] ?? '';
    if($peran === '' && !empty($payload['mekanik_ke'])) $peran = 'mekanik'.preg_replace('/[^1-4]/','', (string)$payload['mekanik_ke']);
    $persen_baru = (int)($payload['persen_baru'] ?? 0);
    if(!$no_service || !$peran || !$persen_baru) return false;
    $esc_svc = mysqli_real_escape_string($koneksi, $no_service);
    $esc_cab = mysqli_real_escape_string($koneksi, $kd_cabang);
    $qsvc = mysqli_query($koneksi, "SELECT no_service, subtotal_jasa, subtotal_item FROM tblservice WHERE no_service='{$esc_svc}' AND kd_cabang='{$esc_cab}'");
    $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
    if(!$svc) return false;

    $qpersen = mysqli_query($koneksi, "SELECT persen_mekanik1,persen_mekanik2,persen_mekanik3,persen_mekanik4 FROM tblservice WHERE no_service='{$esc_svc}' AND kd_cabang='{$esc_cab}'");
    $row_persen = $qpersen ? mysqli_fetch_assoc($qpersen) : [];
    $jml_mek = 0;
    for($i=1;$i<=4;$i++){ if(!empty($row_persen["persen_mekanik{$i}"]) && (int)$row_persen["persen_mekanik{$i}"] > 0) $jml_mek++; }

    $jasa_bersih = (float)($svc['subtotal_jasa'] ?? 0);
    // Live schema belum punya snapshot laba_barang di tblservice.
    // Hitung dari detail servis vs hargapokok master item: total line - (qty * hargapokok).
    $laba_barang = 0;
    $qbarang = mysqli_query($koneksi, "SELECT sb.quantity, sb.total, COALESCE(i.hargapokok,0) AS hargapokok FROM tblservis_barang sb LEFT JOIN tblitem i ON i.noitem=sb.no_item WHERE sb.no_service='{$esc_svc}' AND sb.kd_cabang='{$esc_cab}'");
    if($qbarang){
        while($rb = mysqli_fetch_assoc($qbarang)){
            $qty = (float)($rb['quantity'] ?? 0);
            $line_total = (float)($rb['total'] ?? 0);
            $hpp = (float)($rb['hargapokok'] ?? 0);
            $laba_barang += $line_total - ($qty * $hpp);
        }
    }
    if($laba_barang < 0) $laba_barang = 0;
    $nominal_jasa = 0; $nominal_barang = 0;

    if(strpos($peran,'mekanik')===0 || strpos($peran,'kepala_mekanik')===0){
        // Kalau data lama belum punya persen_mekanik terisi, tetap hitung target revisi sebagai 1 pelaksana.
        if($jml_mek <= 0) $jml_mek = 1;
        $nominal_jasa   = ($jasa_bersih * 0.20) / $jml_mek;
        $nominal_barang = ($laba_barang * 0.05) / $jml_mek;
    } elseif(strpos($peran,'admin')===0){
        $nominal_jasa   = $jasa_bersih * 0.05;
        $nominal_barang = $laba_barang * 0.05;
    }

    $stmt = mysqli_prepare($koneksi, "INSERT INTO servis_komisi (no_service,peran,nominal_jasa,nominal_barang,persen_terpakai,dihitung_saat,id_issue_ref) VALUES (?,?,?,?,?,'revisi_tiket',?)");
    mysqli_stmt_bind_param($stmt,"ssddis",$no_service,$peran,$nominal_jasa,$nominal_barang,$persen_baru,$id_issue);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}


function eksekusi_koreksi_nopol_servis($koneksi, $id_issue, $payload, $id_user, $kd_cabang){
    $no_service = $payload['no_service'] ?? '';
    $nopol_benar = trim((string)($payload['nopol_benar'] ?? ''));
    if($no_service === '' || $nopol_benar === '') return false;
    $stmt = mysqli_prepare($koneksi, "UPDATE tblservice SET no_polisi=? WHERE no_service=? AND kd_cabang=?");
    mysqli_stmt_bind_param($stmt, 'sss', $nopol_benar, $no_service, $kd_cabang);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

// === CREATE TICKET ===
if(isset($_POST['btncreate'])){
    $id_jenis_masalah = (int)post('id_jenis_masalah');
    if(!$id_jenis_masalah){
        $message = 'Pilih jenis masalah terlebih dahulu.'; $error_class = 'alert-danger';
    } else {
        $qj = mysqli_query($koneksi, "SELECT * FROM master_jenis_masalah WHERE id_jenis={$id_jenis_masalah} AND is_active=1");
        $jenis = $qj ? mysqli_fetch_assoc($qj) : null;
        if(!$jenis){
            $message = 'Jenis masalah tidak ditemukan.'; $error_class = 'alert-danger';
        } else {
            $kategori = $jenis['kategori'];
            $divisi = $kategori_divisi[$kategori] ?? 'IT';
            $prioritas = in_array(post('prioritas'),$prioritas_opsi) ? post('prioritas') : 'medium';
            $skema = json_decode($jenis['skema_field'], true);
            if(!is_array($skema)){
                $message = 'Skema field tidak valid.'; $error_class = 'alert-danger';
            } else {
                $payload = []; $valid = true; $deskripsi = '';
                $ref_nopelanggan = post('ref_nopelanggan','');
                $ref_no_polisi = post('ref_no_polisi','');
                foreach($skema as $fd){
                    $nama = $fd['nama']; $wajib = !empty($fd['wajib']);
                    $nilai = post('f_'.$nama, '');
                    if($wajib && $nilai === ''){
                        $message = 'Field "'.($fd['label']??$nama).'" wajib diisi.'; $error_class = 'alert-danger';
                        $valid = false; break;
                    }
                    $payload[$nama] = ($fd['tipe']==='number' && $nilai!=='') ? (float)$nilai : $nilai;
                    if($fd['tipe']!=='readonly' && $nilai!==''){
                        $deskripsi .= ($fd['label']??$nama).': '.$nilai."
";
                    }
                }
                if($valid){
                    $tgl = date('Ymd');
                    $rc = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbl_issue WHERE id_issue LIKE 'ISS-{$tgl}-%'");
                    $urut = $rc ? ((int)mysqli_fetch_assoc($rc)['c']+1) : 1;
                    $new_id = 'ISS-'.$tgl.'-'.str_pad($urut,4,'0',STR_PAD_LEFT);
                    $payload_json = json_encode($payload);
                    $deskripsi_clean = trim($deskripsi);
                    $status_awal = ($jenis['role_approval'] === 'self') ? 'open' : 'waiting_approval';

                    $stmt = mysqli_prepare($koneksi, "INSERT INTO tbl_issue (id_issue,pelapor,kd_cabang,divisi_terkait,kategori,id_jenis_masalah,prioritas,status,deskripsi,payload_json,ref_nopelanggan,ref_no_polisi) VALUES (?,?,?,?,?,?,?, ?,?,?,?,?)");
                    mysqli_stmt_bind_param($stmt,"sisssissssss",$new_id,$id_user,$kd_cabang,$divisi,$kategori,$id_jenis_masalah,$prioritas,$status_awal,$deskripsi_clean,$payload_json,$ref_nopelanggan,$ref_no_polisi);
                    if(mysqli_stmt_execute($stmt)){
                        mysqli_stmt_close($stmt);
                        $stmt2 = mysqli_prepare($koneksi, "INSERT INTO tbl_issue_progress_log (id_issue,oleh,catatan,status_before,status_after) VALUES (?,?,?,?,?)");
                        $kosong_status = "";
                        $catatan_awal = ($status_awal === "waiting_approval") ? "Tiket dibuat dan menunggu approval" : "Tiket dibuat"; mysqli_stmt_bind_param($stmt2,"sisss",$new_id,$id_user,$catatan_awal,$kosong_status,$status_awal); mysqli_stmt_execute($stmt2); mysqli_stmt_close($stmt2);

                        $_SESSION['issue_flash_message'] = 'Laporan berhasil dikirim. Nomor tiket: '.$new_id.'. Status saat ini: '.$status_label[$status_awal].'.';
                        $_SESSION['issue_flash_class'] = 'alert-success';
                        header("Location: issue_add.php?id_issue=".urlencode($new_id)); exit;
                    }
                    mysqli_stmt_close($stmt);
                    $message = 'Gagal menyimpan tiket.'; $error_class = 'alert-danger';
                }
            }
        }
    }
}

// === UPDATE STATUS ===
if(isset($_POST['btnupdate_status']) && $id_issue!==''){
    $status_baru = post('status_baru');
    $catatan = post('catatan');
    if(!in_array($status_baru,$status_opsi)){
        $message = 'Status tidak valid.'; $error_class = 'alert-danger';
    } elseif($status_baru === 'rejected' && trim($catatan) === ''){
        $message = 'Alasan penolakan wajib diisi.'; $error_class = 'alert-danger';
    } else {
        $qc = mysqli_query($koneksi, "SELECT * FROM tbl_issue WHERE id_issue='".mysqli_real_escape_string($koneksi,$id_issue)."'");
        $rc = $qc ? mysqli_fetch_assoc($qc) : null;
        if(!$rc){
            $message = 'Tiket tidak ditemukan.'; $error_class = 'alert-danger';
        } else {
            $status_lama = $rc['status'];
            if(in_array($status_baru,['resolved','closed','rejected']) && !user_can_approve_issue($rc, $is_admin)){
                $message = 'Anda tidak punya permission untuk approve/tolak tiket ini.'; $error_class = 'alert-danger';
            } else {
                $solusi_val = $catatan!=='' ? $catatan : null;
                $log_ext = '';
                if($status_baru==='resolved' && !empty($rc['id_jenis_masalah']) && !empty($rc['payload_json'])){
                    $qj2 = mysqli_query($koneksi, "SELECT * FROM master_jenis_masalah WHERE id_jenis={$rc['id_jenis_masalah']}");
                    $jr = $qj2 ? mysqli_fetch_assoc($qj2) : null;
                    if($jr && !empty($jr['target_eksekusi'])){
                        $payload = json_decode($rc['payload_json'], true);
                        if(!is_array($payload)){
                            $message = 'Approve gagal: payload tiket tidak valid.'; $error_class = 'alert-danger';
                        } else {
                            $fn = $jr['target_eksekusi'];
                            $ok = false;
                            if($fn === 'revisi_komisi_pasca_bayar'){ $ok = eksekusi_revisi_komisi($koneksi,$id_issue,$payload,$id_user,$rc['kd_cabang']); }
                            elseif($fn === 'koreksi_nopol_servis'){ $ok = eksekusi_koreksi_nopol_servis($koneksi,$id_issue,$payload,$id_user,$rc['kd_cabang']); }
                            if(!$ok){
                                $message = 'Approve gagal: eksekusi otomatis untuk tiket ini tidak berhasil.'; $error_class = 'alert-danger';
                            } else {
                                $log_ext = "
[Eksekusi otomatis: OK - {$fn}]";
                            }
                        }
                    }
                }
                if($message === '' || $error_class !== 'alert-danger'){
                    $stmt = mysqli_prepare($koneksi, "UPDATE tbl_issue SET status=?, solusi=?, updated_at=NOW() WHERE id_issue=?");
                    $solusi_baru = ($solusi_val?$solusi_val:'').$log_ext;
                    mysqli_stmt_bind_param($stmt,"sss",$status_baru,$solusi_baru,$id_issue);
                    if(mysqli_stmt_execute($stmt)){
                        mysqli_stmt_close($stmt);
                        $st2 = mysqli_prepare($koneksi, "INSERT INTO tbl_issue_progress_log (id_issue,oleh,catatan,status_before,status_after) VALUES (?,?,?,?,?)");
                        mysqli_stmt_bind_param($st2,"sisss",$id_issue,$id_user,$catatan,$status_lama,$status_baru);
                        mysqli_stmt_execute($st2); mysqli_stmt_close($st2);
                        $message = 'Status tiket diperbarui.';
                    } else {
                        mysqli_stmt_close($stmt);
                        $message = 'Gagal update status.'; $error_class = 'alert-danger';
                    }
                }
            }
        }
    }
}

// === QUERY LIST ===
$list_rows = null; $total_rows = 0;
if($id_issue===''){
    $f_status = get('status'); $f_kategori = get('kategori'); $f_q = get('q');
    $where = [];
    if(!$is_admin){ $where[] = "t.kd_cabang='".mysqli_real_escape_string($koneksi,$kd_cabang)."'"; }
    if($f_status!==''){ $where[] = "t.status='".mysqli_real_escape_string($koneksi,$f_status)."'"; }
    if($f_kategori!==''){ $where[] = "t.kategori='".mysqli_real_escape_string($koneksi,$f_kategori)."'"; }
    if($f_q!==''){
        $qs = mysqli_real_escape_string($koneksi,$f_q);
        $where[] = "(t.id_issue LIKE '%{$qs}%' OR t.deskripsi LIKE '%{$qs}%' OR m.nama_masalah LIKE '%{$qs}%')";
    }
    $wsql = count($where)>0 ? 'WHERE '.implode(' AND ',$where) : '';
    $list_rows = mysqli_query($koneksi, "SELECT t.*, m.nama_masalah, m.role_approval, u.nama_user AS nama_pelapor,
        (SELECT MAX(p2.tanggal) FROM tbl_issue_progress_log p2 WHERE p2.id_issue=t.id_issue) AS last_progress_at
        FROM tbl_issue t
        LEFT JOIN master_jenis_masalah m ON m.id_jenis=t.id_jenis_masalah
        LEFT JOIN tbuser u ON u.id=t.pelapor
        {$wsql}
        ORDER BY COALESCE((SELECT MAX(p3.tanggal) FROM tbl_issue_progress_log p3 WHERE p3.id_issue=t.id_issue), t.created_at) DESC
        LIMIT 100");
    $total_rows = $list_rows ? mysqli_num_rows($list_rows) : 0;
}

// === PRELOAD JENIS MASALAH UNTUK UI MODAL ===
$preload_jenis_masalah = [];
$q_preload_jenis = mysqli_query($koneksi, "SELECT id_jenis,kategori,nama_masalah,skema_field,role_approval,target_eksekusi FROM master_jenis_masalah WHERE is_active=1 AND kategori<>'data_pelanggan' ORDER BY FIELD(kategori,'data_kendaraan','komisi','stok','sistem','lainnya'), id_jenis");
if($q_preload_jenis){ while($rj = mysqli_fetch_assoc($q_preload_jenis)){ $preload_jenis_masalah[] = $rj; } }

// === QUERY DETAIL ===
$detail = null; $progress = []; $jenis_detail = null; $detail_payload = [];
if($id_issue!==''){
    $qd = mysqli_query($koneksi, "SELECT t.*, m.nama_masalah, m.skema_field, m.role_approval, m.target_eksekusi FROM tbl_issue t LEFT JOIN master_jenis_masalah m ON m.id_jenis=t.id_jenis_masalah WHERE t.id_issue='".mysqli_real_escape_string($koneksi,$id_issue)."'");
    $detail = $qd ? mysqli_fetch_assoc($qd) : null;
    if($detail){
        if($detail['payload_json']){ $detail_payload = json_decode($detail['payload_json'],true); if(!is_array($detail_payload)) $detail_payload = []; }
        $qp = mysqli_query($koneksi, "SELECT p.*, u.nama_user FROM tbl_issue_progress_log p LEFT JOIN tbuser u ON u.id=p.oleh WHERE p.id_issue='".mysqli_real_escape_string($koneksi,$id_issue)."' ORDER BY p.tanggal ASC");
        while($rw = mysqli_fetch_assoc($qp)){ $progress[] = $rw; }
        if($detail['skema_field']){ $jenis_detail = json_decode($detail['skema_field'],true); }
        if((empty($detail['nama_masalah']) || empty($detail_payload))){
            $legacy = infer_legacy_issue_structure($koneksi, $detail);
            if(!empty($legacy['nama_masalah'])) $detail['nama_masalah'] = $legacy['nama_masalah'];
            if(empty($detail_payload) && !empty($legacy['detail_payload'])) $detail_payload = $legacy['detail_payload'];
            if(empty($jenis_detail) && !empty($legacy['jenis_detail'])) $jenis_detail = $legacy['jenis_detail'];
            if(empty($detail['target_eksekusi']) && !empty($legacy['target_eksekusi'])) $detail['target_eksekusi'] = $legacy['target_eksekusi'];
            if(empty($detail['role_approval']) && !empty($legacy['role_approval'])) $detail['role_approval'] = $legacy['role_approval'];
            if(empty($detail['id_jenis_masalah']) && !empty($legacy['id_jenis_masalah'])) $detail['id_jenis_masalah'] = $legacy['id_jenis_masalah'];
        }
    }
}
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
    <style>
        .jenis-card { cursor:pointer; transition:all 0.15s; }
        .jenis-card:hover { background:#f5f5f5; }
        .jenis-card.active { background:#d9edf7; border-color:#337ab7; }
        .diff-old { background:#fdd; text-decoration:line-through; padding:2px 6px; border-radius:3px; }
        .diff-new { background:#dfd; padding:2px 6px; border-radius:3px; }
        .payload-diff-row { margin-bottom:8px; padding:6px; border-bottom:1px solid #eee; }
        .jenis-group-title { margin-top:16px; margin-bottom:8px; font-weight:bold; color:#2b7dbc; }
        .helper-box { background:#f8fbff; border:1px solid #d9edf7; padding:12px; border-radius:4px; margin-bottom:15px; }
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
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i><?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                        <span class="user-info"><small>Welcome,</small><?php echo htmlspecialchars($_nama); ?></span>
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
                    <li class="active">Lapor Masalah</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row">
                    <div class="col-xs-12">
                        <?php if($message!=''){ echo '<div class="alert '.$error_class.'">'.htmlspecialchars($message).'</div>'; } ?>
                        <?php if($id_issue===''){ ?>
                        <!-- LIST VIEW -->
                        <div class="helper-box">
                            <strong>Tujuan modul ini:</strong> lapor kesalahan input transaksi yang spesifik, lalu minta tindak lanjut/approval. Untuk gabung data pelanggan dobel, pakai menu <strong><a href="admin_deteksi_pelanggan_dobel.php">Deteksi Duplikat Pelanggan</a></strong>.
                        </div>
                        <div class="well" style="padding:10px;">
                            <form class="form-inline" method="get">
                                <div class="form-group"><label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">- Semua -</option>
                                        <?php foreach($status_opsi as $s){ $sel=(get('status')===$s)?'selected':''; echo '<option value="'.htmlspecialchars($s).'" '.$sel.'>'.$status_label[$s].'</option>'; } ?>
                                    </select>
                                </div>
                                <div class="form-group"><label>Kategori</label>
                                    <select name="kategori" class="form-control">
                                        <option value="">- Semua -</option>
                                        <?php foreach($kategori_divisi as $kk=>$dv){ $sel=(get('kategori')===$kk)?'selected':''; echo '<option value="'.htmlspecialchars($kk).'" '.$sel.'>'.ucfirst(str_replace('_',' ',$kk)).'</option>'; } ?>
                                    </select>
                                </div>
                                <div class="form-group"><label>Cari</label>
                                    <input type="text" name="q" class="form-control" placeholder="ID tiket / nama masalah / catatan" value="<?php echo htmlspecialchars(get('q')); ?>" />
                                </div>
                                <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Filter</button>
                            </form>
                        </div>
                        <div class="widget-box">
                            <div class="widget-header widget-header-blue widget-header-flat">
                                <h4 class="widget-title lighter"><i class="ace-icon fa fa-exclamation-triangle"></i> Daftar Laporan Masalah</h4>
                                <div class="widget-toolbar">
                                    <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#modalTiketBaru"><i class="fa fa-plus"></i> Buat Laporan Baru</button>
                                </div>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main no-padding">
                                    <div class="table-header">Hasil: <?php echo (int)$total_rows; ?> data<?php echo $is_admin ? ' (semua cabang)' : ' (cabang '.htmlspecialchars($kd_cabang).')'; ?></div>
                                    <div class="alert alert-info" style="margin:10px; margin-bottom:0;">
                                        <strong>Cara baca cepat:</strong> fokus ke kolom <strong>Masalah</strong>, <strong>Butuh Aksi</strong>, dan <strong>Update Terakhir</strong>. Itu kolom paling penting untuk operasional harian.
                                    </div>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr><th>ID Tiket</th><th>Masalah</th><th>Pelapor</th><th>Butuh Aksi</th><th>Prioritas</th><th>Status</th><th>Update Terakhir</th><th>Aksi</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php if($list_rows && mysqli_num_rows($list_rows)>0){ while($r = mysqli_fetch_assoc($list_rows)){ ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($r['id_issue']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($r['nama_masalah'] ?? ucfirst(str_replace('_',' ',$r['kategori']))); ?></strong><br />
                                                    <small class="text-muted"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$r['kategori']))); ?> · Cabang <?php echo htmlspecialchars($r['kd_cabang']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($r['nama_pelapor'] ?? ('User#'.($r['pelapor'] ?? '-'))); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars(issue_next_action_label($r)); ?></strong><br />
                                                    <small class="text-muted">Divisi: <?php echo htmlspecialchars($r['divisi_terkait']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars(issue_priority_label($r['prioritas'])); ?></td>
                                                <td><span class="label <?php echo $status_class[$r['status']]; ?>"><?php echo $status_label[$r['status']]; ?></span></td>
                                                <td><?php echo htmlspecialchars($r['last_progress_at'] ?: ($r['updated_at'] ?? $r['created_at'] ?? $r['tanggal_lapor'])); ?></td>
                                                <td><a class="btn btn-minier btn-primary" href="issue_add.php?id_issue=<?php echo urlencode($r['id_issue']); ?>"><i class="fa fa-eye"></i> Buka</a></td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="8" class="text-center">Tidak ada data</td></tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL TIKET BARU -->
                        <div class="modal fade" id="modalTiketBaru" tabindex="-1" data-backdrop="static">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="post" action="issue_add.php" id="formTiketBaru">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            <h4 class="modal-title">Buat Laporan Masalah Baru</h4>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info" style="margin-bottom:12px;">
                                                <strong>Cara pakai:</strong> pilih masalah yang paling mirip, isi detail yang diminta, lalu kirim. Fokus ke masalah yang terjadi, tidak perlu paham istilah teknis aplikasi.
                                            </div>
                                            <!-- Step 1: Pilih masalah -->
                                            <div id="stepJenis">
                                                <h5>Langkah 1: Pilih Jenis Masalah</h5>
                                                <div id="jenisList" class="list-group" style="margin-top:10px;"></div>
                                                <input type="hidden" name="id_jenis_masalah" id="id_jenis_masalah" value="" />
                                            </div>
                                            <!-- Step 2: Dynamic fields -->
                                            <div id="stepFields" style="display:none;">
                                                <h5>Langkah 2: Lengkapi Detail <small id="selectedJenisLabel"></small></h5>
                                                <div id="dynamicFields" style="margin-top:10px;"></div>
                                                <div class="form-group" style="margin-top:15px;">
                                                    <label>Tingkat urgensi</label>
                                                    <select name="prioritas" class="form-control">
                                                        <?php foreach($prioritas_opsi as $p){ $sel=($p=='medium')?'selected':''; echo '<option value="'.htmlspecialchars($p).'" '.$sel.'>'.ucfirst($p).'</option>'; } ?>
                                                    </select>
                                                    <p class="help-block" style="margin-bottom:0;">Pilih <strong>medium</strong> bila ragu. Pilih <strong>high</strong> hanya jika mengganggu transaksi aktif.</p>
                                                </div>
                                            </div>
                                            <!-- Navigation -->
                                            <div id="stepNav" style="margin-top:15px;">
                                                <button type="button" class="btn btn-sm btn-default" id="btnBackStep" style="display:none;"><i class="fa fa-arrow-left"></i> Kembali</button>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn" data-dismiss="modal">Batal</button>
                                            <button type="submit" name="btncreate" id="btnKirimTiket" class="btn btn-success" style="display:none;"><i class="fa fa-send"></i> Kirim Laporan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <script>
                        window.preloadedJenisMasalah = <?php echo json_encode($preload_jenis_masalah, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                        (function(){
                            var jenisData = Array.isArray(window.preloadedJenisMasalah) ? window.preloadedJenisMasalah : [];

                            var kategoriLabelMap = {
                                data_kendaraan: 'Kendaraan',
                                komisi: 'Komisi & Persentase',
                                stok: 'Stok',
                                sistem: 'Sistem',
                                lainnya: 'Lainnya'
                            };

                            var kategoriDescMap = {
                                data_kendaraan: 'Pakai ini bila salah pilih nopol atau data kendaraan pada servis.',
                                komisi: 'Pakai ini bila persen mekanik, admin, atau kepala mekanik salah.',
                                stok: 'Pakai ini bila ada selisih stok terkait transaksi tertentu.',
                                sistem: 'Pakai ini bila aplikasi error atau alur layar bermasalah.',
                                lainnya: 'Pakai ini bila masalah tidak cocok dengan pilihan lain.'
                            };

                            function buildJenisCard(j){
                                var a = document.createElement('a');
                                a.href = '#';
                                a.className = 'list-group-item jenis-pick';
                                a.setAttribute('data-id', j.id_jenis);
                                a.setAttribute('data-skema', j.skema_field);
                                var desc = '';
                                try {
                                    var parsed = JSON.parse(j.skema_field || '[]');
                                    var wajibCount = 0;
                                    parsed.forEach(function(fd){ if(fd.wajib){ wajibCount++; } });
                                    desc = parsed.length + ' field' + (wajibCount > 0 ? ', ' + wajibCount + ' wajib' : '');
                                } catch(err) {}
                                a.innerHTML = '<strong>' + j.nama_masalah + '</strong><br><small>' + desc + '</small>';
                                a.addEventListener('click', function(e){
                                    e.preventDefault();
                                    document.getElementById('id_jenis_masalah').value = j.id_jenis;
                                    document.getElementById('selectedJenisLabel').textContent = '> ' + j.nama_masalah;
                                    document.querySelectorAll('.jenis-pick').forEach(function(x){ x.classList.remove('active'); });
                                    this.classList.add('active');
                                    renderDynamicFields(j.skema_field);
                                });
                                return a;
                            }

                            function renderJenisList(){
                                var container = document.getElementById('jenisList');
                                if(!container) return;
                                container.innerHTML = '';
                                var grouped = {};
                                jenisData.forEach(function(j){
                                    if(!grouped[j.kategori]) grouped[j.kategori] = [];
                                    grouped[j.kategori].push(j);
                                });
                                ['data_kendaraan','komisi','stok','sistem','lainnya'].forEach(function(kat){
                                    var list = grouped[kat] || [];
                                    if(list.length === 0) return;
                                    var title = document.createElement('div');
                                    title.className = 'jenis-group-title';
                                    title.textContent = kategoriLabelMap[kat] || kat;
                                    container.appendChild(title);
                                    var desc = document.createElement('div');
                                    desc.className = 'text-muted';
                                    desc.style.marginBottom = '8px';
                                    desc.textContent = kategoriDescMap[kat] || '';
                                    container.appendChild(desc);
                                    list.forEach(function(j){ container.appendChild(buildJenisCard(j)); });
                                });
                            }

                            renderJenisList();

                            var currentServiceDetail = null;

                            function makeSelect(name, required){
                                var sel = document.createElement('select');
                                sel.name = name;
                                sel.id = name;
                                sel.className = 'form-control';
                                if(required) sel.setAttribute('required', '');
                                var opt0 = document.createElement('option');
                                opt0.value = '';
                                opt0.textContent = '- Pilih -';
                                sel.appendChild(opt0);
                                return sel;
                            }

                            function fillSelect(selectEl, options){
                                var old = selectEl.value;
                                selectEl.innerHTML = '';
                                var opt0 = document.createElement('option');
                                opt0.value = '';
                                opt0.textContent = '- Pilih -';
                                selectEl.appendChild(opt0);
                                (options || []).forEach(function(o){
                                    var opt = document.createElement('option');
                                    opt.value = o.value;
                                    opt.textContent = o.label;
                                    if(o.persen_lama !== undefined) opt.setAttribute('data-persen-lama', o.persen_lama);
                                    if(o.peran !== undefined) opt.setAttribute('data-peran', o.peran);
                                    selectEl.appendChild(opt);
                                });
                                if(old) selectEl.value = old;
                            }

                            function setFieldValue(fieldName, value){
                                var el = document.getElementById('f_' + fieldName) || document.querySelector('[name="f_' + fieldName + '"]');
                                if(el) el.value = value;
                            }

                            function loadServiceDetail(noService){
                                if(!noService) return;
                                var xhr = new XMLHttpRequest();
                                xhr.open('GET', 'ajax-issue-service-detail.php?no_service=' + encodeURIComponent(noService), true);
                                xhr.onload = function(){
                                    if(xhr.status !== 200) return;
                                    try { currentServiceDetail = JSON.parse(xhr.responseText); } catch(e){ currentServiceDetail = null; }
                                    if(!currentServiceDetail || currentServiceDetail.error) return;

                                    setFieldValue('nopol_salah', currentServiceDetail.service.no_polisi || '');

                                    var mekanikSelect = document.getElementById('f_mekanik_ke');
                                    if(mekanikSelect) fillSelect(mekanikSelect, currentServiceDetail.pilihan_mekanik || []);

                                    var roleSelect = document.getElementById('f_peran');
                                    if(roleSelect) fillSelect(roleSelect, currentServiceDetail.pilihan_peran_komisi || []);

                                    var persenSelect = document.getElementById('f_persen_baru');
                                    if(persenSelect && (currentServiceDetail.persen_opsi || []).length > 0){
                                        var opts = currentServiceDetail.persen_opsi.map(function(v){ return {value:String(v), label:String(v)+'%'}; });
                                        fillSelect(persenSelect, opts);
                                    }

                                    var alasanSelect = document.getElementById('f_alasan');
                                    if(alasanSelect){
                                        var presetType = alasanSelect.getAttribute('data-preset');
                                        var arr = presetType === 'nopol' ? currentServiceDetail.alasan_nopol : currentServiceDetail.alasan_komisi;
                                        var opts2 = (arr || []).map(function(v){ return {value:v, label:v}; });
                                        fillSelect(alasanSelect, opts2);
                                    }
                                };
                                xhr.send();
                            }

                            function renderDynamicFields(skemaJson){
                                currentServiceDetail = null;
                                var container = document.getElementById('dynamicFields');
                                container.innerHTML = '';
                                document.getElementById('stepFields').style.display = 'block';
                                document.getElementById('btnKirimTiket').style.display = 'inline-block';
                                document.getElementById('btnBackStep').style.display = 'inline-block';
                                document.getElementById('btnBackStep').onclick = function(){
                                    document.getElementById('stepFields').style.display = 'none';
                                    document.getElementById('btnKirimTiket').style.display = 'none';
                                    document.getElementById('stepJenis').style.display = 'block';
                                    document.getElementById('btnBackStep').style.display = 'none';
                                };
                                try { var fields = JSON.parse(skemaJson); } catch(e) { return; }
                                fields.forEach(function(f){
                                    var g = document.createElement('div');
                                    g.className = 'form-group';
                                    var label = document.createElement('label');
                                    label.innerHTML = (f.label || f.nama) + (f.wajib ? ' <span class="text-danger">*</span>' : '');
                                    g.appendChild(label);

                                    var nama = 'f_' + f.nama;
                                    var tipe = f.tipe;
                                    if((f.nama === 'no_service' || f.nama === 'no_service_asal') && tipe === 'text'){
                                        tipe = 'autocomplete';
                                        f.sumber_lookup = f.sumber_lookup || 'tblservice';
                                    }
                                    if((f.nama === 'nopol' || f.nama === 'no_polisi' || f.nama === 'nopol_benar') && tipe === 'text'){
                                        tipe = 'autocomplete';
                                        f.sumber_lookup = f.sumber_lookup || 'kendaraan';
                                    }
                                    if(tipe === 'textarea'){
                                        var ta = document.createElement('textarea');
                                        ta.name = nama; ta.id = nama; ta.className = 'form-control'; ta.rows = 3;
                                        if(f.wajib) ta.setAttribute('required', '');
                                        g.appendChild(ta);
                                    } else if(tipe === 'readonly' || tipe === 'readonly_auto'){
                                        var inpR = document.createElement('input');
                                        inpR.type = 'text'; inpR.name = nama; inpR.id = nama; inpR.className = 'form-control'; inpR.readOnly = true;
                                        g.appendChild(inpR);
                                    } else if(tipe === 'number'){
                                        var inpN = document.createElement('input');
                                        inpN.type = 'number'; inpN.step = '0.01'; inpN.name = nama; inpN.id = nama; inpN.className = 'form-control';
                                        if(f.wajib) inpN.setAttribute('required', '');
                                        g.appendChild(inpN);
                                    } else if(tipe === 'autocomplete'){
                                        var wrapA = document.createElement('div');
                                        wrapA.className = 'input-group';
                                        var inpA = document.createElement('input');
                                        inpA.type = 'text'; inpA.name = nama; inpA.id = nama; inpA.className = 'form-control';
                                        inpA.setAttribute('data-autocomplete', f.sumber_lookup || '');
                                        inpA.setAttribute('autocomplete', 'off');
                                        inpA.setAttribute('placeholder', 'Ketik no servis / nopol minimal 2 karakter');
                                        if(f.wajib) inpA.setAttribute('required', '');
                                        wrapA.appendChild(inpA);
                                        var spanA = document.createElement('span');
                                        spanA.className = 'input-group-btn';
                                        var btnA = document.createElement('button');
                                        btnA.type = 'button';
                                        btnA.className = 'btn btn-info btn-lookup';
                                        btnA.textContent = 'Cari & Pilih';
                                        btnA.setAttribute('data-target-input', nama);
                                        btnA.setAttribute('data-source', f.sumber_lookup || '');
                                        btnA.setAttribute('data-label', f.label || f.nama);
                                        spanA.appendChild(btnA);
                                        wrapA.appendChild(spanA);
                                        g.appendChild(wrapA);
                                        var small = document.createElement('small');
                                        small.className = 'text-muted'; small.textContent = 'Ketik no servis atau nopol, lalu pilih hasil. Bisa juga pakai tombol Cari & Pilih.';
                                        g.appendChild(small);
                                    } else if(tipe === 'enum' && f.opsi){
                                        var sel = makeSelect(nama, f.wajib);
                                        f.opsi.forEach(function(o){
                                            var opt = document.createElement('option');
                                            opt.value = o; opt.textContent = o;
                                            sel.appendChild(opt);
                                        });
                                        g.appendChild(sel);
                                    } else if(tipe === 'service_mechanic_select'){
                                        var selM = makeSelect(nama, f.wajib);
                                        selM.setAttribute('data-dependent', 'service_mechanic');
                                        selM.addEventListener('change', function(){
                                            var opt = this.options[this.selectedIndex];
                                            setFieldValue('persen_lama', opt ? (opt.getAttribute('data-persen-lama') || '') : '');
                                        });
                                        g.appendChild(selM);
                                        var sm = document.createElement('small'); sm.className='text-muted'; sm.textContent='Pilih No. Service dulu, lalu pilih mekanik dari daftar.'; g.appendChild(sm);
                                    } else if(tipe === 'service_role_select'){
                                        var selR = makeSelect(nama, f.wajib);
                                        selR.setAttribute('data-dependent', 'service_role');
                                        selR.addEventListener('change', function(){
                                            var opt = this.options[this.selectedIndex];
                                            setFieldValue('persen_lama', opt ? (opt.getAttribute('data-persen-lama') || '') : '');
                                        });
                                        g.appendChild(selR);
                                        var sr = document.createElement('small'); sr.className='text-muted'; sr.textContent='Pilih No. Service dulu, lalu pilih peran dari daftar.'; g.appendChild(sr);
                                    } else if(tipe === 'preset_percent'){
                                        var selP = makeSelect(nama, f.wajib);
                                        [0,10,20,25,30,40,50,60,70,75,80,90,100].forEach(function(v){
                                            var opt = document.createElement('option'); opt.value = String(v); opt.textContent = String(v)+'%'; selP.appendChild(opt);
                                        });
                                        g.appendChild(selP);
                                    } else if(tipe === 'preset_reason'){
                                        var selA = makeSelect(nama, f.wajib);
                                        selA.setAttribute('data-preset', f.preset || 'komisi');
                                        var reasons = (f.preset === 'nopol') ? ['Salah pilih kendaraan','Typo input nopol','Kendaraan pelanggan tertukar','Koreksi data setelah validasi CS'] : ['Salah input persentase','Mekanik tidak ikut kerja','Mekanik tambahan belum dicatat','Pembagian kerja direvisi Supervisor','Koreksi payroll setelah verifikasi'];
                                        reasons.forEach(function(v){ var opt = document.createElement('option'); opt.value=v; opt.textContent=v; selA.appendChild(opt); });
                                        g.appendChild(selA);
                                    } else {
                                        var inpT = document.createElement('input');
                                        inpT.type = 'text'; inpT.name = nama; inpT.id = nama; inpT.className = 'form-control';
                                        if(f.wajib) inpT.setAttribute('required', '');
                                        g.appendChild(inpT);
                                    }
                                    container.appendChild(g);
                                });
                            }

                            // Autocomplete
                            document.addEventListener('click', function(e){
                                var btn = e.target.closest ? e.target.closest('.btn-lookup') : null;
                                if(!btn) return;
                                e.preventDefault();
                                openLookupPicker(btn.getAttribute('data-source'), btn.getAttribute('data-target-input'), btn.getAttribute('data-label'));
                            });

                            function ensureLookupModal(){
                                var modal = document.getElementById('lookupPickerModal');
                                if(modal) return modal;
                                var html = ''+
                                    '<div class="modal fade" id="lookupPickerModal" tabindex="-1" role="dialog" aria-hidden="true">'+
                                    '  <div class="modal-dialog modal-lg" role="document">'+
                                    '    <div class="modal-content">'+
                                    '      <div class="modal-header">'+
                                    '        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                                    '        <h4 class="modal-title" id="lookupPickerTitle">Cari Data</h4>'+
                                    '      </div>'+
                                    '      <div class="modal-body">'+
                                    '        <div class="input-group">'+
                                    '          <input type="text" class="form-control" id="lookupPickerQuery" placeholder="Ketik minimal 2 karakter untuk mencari">'+
                                    '          <span class="input-group-btn"><button type="button" class="btn btn-primary" id="lookupPickerSearch">Cari</button></span>'+
                                    '        </div>'+
                                    '        <div id="lookupPickerInfo" class="text-muted" style="margin-top:8px;">Cari data lalu pilih salah satu hasil.</div>'+
                                    '        <div class="list-group" id="lookupPickerResults" style="margin-top:10px;max-height:360px;overflow-y:auto;"></div>'+
                                    '      </div>'+
                                    '      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>'+
                                    '    </div>'+
                                    '  </div>'+
                                    '</div>';
                                var holder = document.createElement('div');
                                holder.innerHTML = html;
                                document.body.appendChild(holder.firstChild);
                                return document.getElementById('lookupPickerModal');
                            }

                            function openLookupPicker(source, targetInputId, label){
                                var modal = ensureLookupModal();
                                var title = document.getElementById('lookupPickerTitle');
                                var query = document.getElementById('lookupPickerQuery');
                                var info = document.getElementById('lookupPickerInfo');
                                var results = document.getElementById('lookupPickerResults');
                                title.textContent = 'Cari ' + (label || 'Data');
                                query.value = '';
                                results.innerHTML = '';
                                info.className = 'text-muted';
                                info.textContent = 'Ketik minimal 2 karakter, lalu tekan Cari atau Enter.';
                                modal.setAttribute('data-source', source || '');
                                modal.setAttribute('data-target-input', targetInputId || '');

                                function runSearch(){
                                    var val = query.value.trim();
                                    results.innerHTML = '';
                                    if(val.length < 2){
                                        info.className = 'text-warning';
                                        info.textContent = 'Minimal 2 karakter.';
                                        return;
                                    }
                                    info.className = 'text-muted';
                                    info.textContent = 'Mencari data...';
                                    var xhr = new XMLHttpRequest();
                                    xhr.open('GET', 'ajax-autocomplete.php?source=' + encodeURIComponent(source || '') + '&q=' + encodeURIComponent(val), true);
                                    xhr.onload = function(){
                                        var data = [];
                                        if(xhr.status === 200){
                                            try { data = JSON.parse(xhr.responseText); } catch(e){ data = []; }
                                        }
                                        if(!data || data.length === 0){
                                            info.className = 'text-danger';
                                            info.textContent = 'Data tidak ditemukan. Coba kata kunci lain.';
                                            return;
                                        }
                                        info.className = 'text-success';
                                        info.textContent = data.length + ' data ditemukan. Pilih salah satu.';
                                        data.forEach(function(item){
                                            var a = document.createElement('a');
                                            a.href = '#';
                                            a.className = 'list-group-item';
                                            a.textContent = item.label;
                                            a.addEventListener('click', function(ev){
                                                ev.preventDefault();
                                                selectLookupItem(targetInputId, item);
                                                if(window.jQuery){ jQuery('#lookupPickerModal').modal('hide'); }
                                            });
                                            results.appendChild(a);
                                        });
                                    };
                                    xhr.send();
                                }

                                document.getElementById('lookupPickerSearch').onclick = runSearch;
                                query.onkeydown = function(ev){ if(ev.key === 'Enter'){ ev.preventDefault(); runSearch(); } };
                                if(window.jQuery){ jQuery('#lookupPickerModal').modal('show'); setTimeout(function(){ query.focus(); }, 400); }
                            }

                            function selectLookupItem(targetInputId, item){
                                var input = document.getElementById(targetInputId);
                                if(!input) return;
                                input.value = item.value;
                                if(input.name === 'f_no_service'){
                                    loadServiceDetail(item.value);
                                }
                                if(item.extras){
                                    for(var key in item.extras){
                                        var el = document.getElementById('f_' + key) || document.querySelector('[name="f_' + key + '"]');
                                        if(el) el.value = item.extras[key];
                                    }
                                }
                            }

                            document.addEventListener('focusin', function(e){
                                var el = e.target;
                                if(el.tagName === 'INPUT' && el.getAttribute('data-autocomplete')){
                                    if(!el._ac_attached){
                                        el._ac_attached = true;
                                        var lastVal = '';
                                        el.addEventListener('input', function(){
                                            var val = this.value;
                                            if(val.length < 2 || val === lastVal) return;
                                            lastVal = val;
                                            var xhr = new XMLHttpRequest();
                                            xhr.open('GET', 'ajax-autocomplete.php?source=' + encodeURIComponent(this.getAttribute('data-autocomplete')) + '&q=' + encodeURIComponent(val), true);
                                            xhr.onload = function(){
                                                if(xhr.status === 200){
                                                    try { showAutocomplete(el, JSON.parse(xhr.responseText)); } catch(e){}
                                                }
                                            };
                                            xhr.send();
                                        });
                                    }
                                }
                            });

                            function showAutocomplete(input, data){
                                var existing = document.getElementById('ac_' + input.name);
                                if(existing) existing.remove();
                                var div = document.createElement('div');
                                div.id = 'ac_' + input.name;
                                div.style.cssText = 'position:absolute;z-index:9999;background:white;border:1px solid #ccc;max-height:200px;overflow-y:auto;';
                                var rect = input.getBoundingClientRect();
                                div.style.top = (rect.bottom + window.scrollY) + 'px';
                                div.style.left = (rect.left + window.scrollX) + 'px';
                                data.forEach(function(item){
                                    var a = document.createElement('a');
                                    a.href = '#';
                                    a.style.cssText = 'display:block;padding:8px 12px;color:#333;text-decoration:none;';
                                    a.textContent = item.label;
                                    a.addEventListener('click', function(e){
                                        e.preventDefault();
                                        input.value = item.value;
                                        div.remove();
                                        if(input.name === 'f_no_service'){
                                            loadServiceDetail(item.value);
                                        }
                                        if(item.extras){
                                            for(var key in item.extras){
                                                var el = document.getElementById('f_' + key) || document.querySelector('[name="f_' + key + '"]');
                                                if(el) el.value = item.extras[key];
                                            }
                                        }
                                    });
                                    div.appendChild(a);
                                });
                                document.body.appendChild(div);
                                setTimeout(function(){
                                    document.addEventListener('click', function _close(e){
                                        if(!div.contains(e.target) && e.target !== input){
                                            div.remove(); document.removeEventListener('click', _close);
                                        }
                                    });
                                }, 10);
                            }
                        })();
                        </script>
                        <!-- END LIST VIEW -->
                        <?php } else { ?>
                        <!-- DETAIL VIEW -->
                        <?php if(!$detail){ ?>
                            <div class="alert alert-danger">Laporan tidak ditemukan. <a href="issue_add.php">Kembali ke daftar</a></div>
                        <?php } else { ?>
                        <div class="widget-box">
                            <div class="widget-header widget-header-blue widget-header-flat">
                                <h4 class="widget-title lighter"><i class="ace-icon fa fa-exclamation-triangle"></i> Detail Laporan <?php echo htmlspecialchars($detail['id_issue']); ?><?php if(!empty($detail['nama_masalah'])) echo ': '.htmlspecialchars($detail['nama_masalah']); ?></h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <table class="table table-bordered">
                                        <tr><th style="width:200px;">Status</th><td><span class="label <?php echo $status_class[$detail['status']]; ?>"><?php echo $status_label[$detail['status']]; ?></span></td></tr>
                                        <tr><th>Jenis Masalah</th><td><?php echo htmlspecialchars($detail['nama_masalah'] ?? 'Umum'); ?></td></tr>
                                        <tr><th>Kategori</th><td><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$detail['kategori']))); ?></td></tr>
                                        <tr><th>Divisi Terkait</th><td><?php echo htmlspecialchars($detail['divisi_terkait']); ?></td></tr>
                                        <tr><th>Prioritas</th><td><?php echo htmlspecialchars(ucfirst($detail['prioritas'])); ?></td></tr>
                                        <tr><th>Cabang</th><td><?php echo htmlspecialchars($detail['kd_cabang']); ?></td></tr>
                                        <tr><th>Tanggal Laporan</th><td><?php echo htmlspecialchars($detail['tanggal_lapor']); ?></td></tr>
                                        <?php if(!empty($detail['deadline'])){ ?><tr><th>Deadline</th><td><?php echo htmlspecialchars($detail['deadline']); ?></td></tr><?php } ?>
                                    </table>

                                    <?php if(!empty($detail_payload) && is_array($jenis_detail)){ ?>
                                    <h5>Detail Masalah</h5>
                                    <table class="table table-bordered table-striped">
                                        <?php foreach($jenis_detail as $fd){ $fname=$fd['nama']; $flabel=$fd['label'] ?? $fname; $fvalue=$detail_payload[$fname] ?? '-'; ?>
                                        <tr>
                                            <th style="width:220px;"><?php echo htmlspecialchars($flabel); ?></th>
                                            <td><?php echo htmlspecialchars(is_array($fvalue) ? json_encode($fvalue) : (string)$fvalue); ?></td>
                                        </tr>
                                        <?php } ?>
                                    </table>
                                    <?php } elseif(!empty($detail_payload)){ ?>
                                    <h5>Detail Masalah</h5>
                                    <table class="table table-bordered table-striped">
                                        <?php foreach($detail_payload as $k=>$v){ ?>
                                        <tr><th style="width:220px;"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$k))); ?></th><td><?php echo htmlspecialchars(is_array($v)?json_encode($v):(string)$v); ?></td></tr>
                                        <?php } ?>
                                    </table>
                                    <?php } ?>

                                    <?php if($detail['deskripsi']){ ?><h5>Catatan</h5><div class="well well-sm"><?php echo nl2br(htmlspecialchars($detail['deskripsi'])); ?></div><?php } ?>
                                    <?php if($detail['ref_nopelanggan']){ ?><p><strong>Ref. No Pelanggan:</strong> <?php echo htmlspecialchars($detail['ref_nopelanggan']); ?></p><?php } ?>
                                    <?php if($detail['ref_no_polisi']){ ?><p><strong>Ref. No Polisi:</strong> <?php echo htmlspecialchars($detail['ref_no_polisi']); ?></p><?php } ?>
                                    <?php if($detail['solusi']){ ?><h5>Hasil Tindak Lanjut / Catatan Atasan</h5><div class="well well-sm"><?php echo nl2br(htmlspecialchars($detail['solusi'])); ?></div><?php } ?>

                                    <?php if(in_array($detail['status'], ['waiting_approval','open']) && is_auto_approval_issue($detail) && user_can_approve_issue($detail, $is_admin) && !empty($detail_payload) && is_array($jenis_detail)){ ?>
                                    <hr />
                                    <h5>Review & Approve</h5>
                                    <div class="well" style="background:#fcfcfc;">
                                        <?php foreach($jenis_detail as $fd){
                                            $fname = $fd['nama'];
                                            $flabel = $fd['label'] ?? $fname;
                                            if(($fd['tipe'] ?? '') === 'readonly') continue;
                                            $nilai = $detail_payload[$fname] ?? '-';
                                            $nilai_lama = '-';
                                            if(!empty($fd['sumber_lookup']) && !empty($detail_payload['no_service'])){
                                                $esc_svc = mysqli_real_escape_string($koneksi, $detail_payload['no_service']);
                                                if($fd['sumber_lookup'] === 'servis_komisi_persen'){
                                                    $q_lama = mysqli_query($koneksi, "SELECT persen_terpakai FROM servis_komisi WHERE no_service='{$esc_svc}' AND peran='".mysqli_real_escape_string($koneksi, $detail_payload['peran'] ?? '')."' ORDER BY id DESC LIMIT 1");
                                                    if($rl = mysqli_fetch_assoc($q_lama)) $nilai_lama = $rl['persen_terpakai'];
                                                } elseif($fd['sumber_lookup'] === 'persen_mekanik'){
                                                    $mek_idx = (int)($detail_payload['mekanik_ke'] ?? 1);
                                                    $col = 'persen_mekanik'.$mek_idx;
                                                    $q_lama = mysqli_query($koneksi, "SELECT {$col} FROM tblservice WHERE no_service='{$esc_svc}'");
                                                    if($rl = mysqli_fetch_assoc($q_lama)) $nilai_lama = $rl[$col];
                                                } elseif($fd['sumber_lookup'] === 'nopol_servis'){
                                                    $q_lama = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='{$esc_svc}'");
                                                    if($rl = mysqli_fetch_assoc($q_lama)) $nilai_lama = $rl['no_polisi'];
                                                }
                                            }
                                        ?>
                                        <div class="payload-diff-row">
                                            <strong><?php echo htmlspecialchars($flabel); ?></strong><br>
                                            <span class="diff-old">Lama: <?php echo htmlspecialchars((string)$nilai_lama); ?></span>
                                            <span style="margin:0 10px;">&rarr;</span>
                                            <span class="diff-new">Baru: <?php echo htmlspecialchars((string)$nilai); ?></span>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <form method="post" action="issue_add.php?id_issue=<?php echo urlencode($id_issue); ?>">
                                        <div class="form-group"><input type="text" name="catatan" class="form-control" placeholder="Catatan persetujuan atau alasan penolakan" style="max-width:500px;" /></div>
                                        <input type="hidden" name="status_baru" id="approve_status" value="" />
                                        <button type="submit" name="btnupdate_status" class="btn btn-success" onclick="document.getElementById('approve_status').value='resolved';"><i class="fa fa-check"></i> Setujui & Jalankan</button>
                                        <button type="submit" name="btnupdate_status" class="btn btn-danger" onclick="document.getElementById('approve_status').value='rejected';"><i class="fa fa-times"></i> Tolak Pengajuan</button>
                                    </form>
                                    <?php } elseif(in_array($detail['status'], ['waiting_approval','open']) && is_auto_approval_issue($detail) && !user_can_approve_issue($detail, $is_admin)){ ?>
                                    <div class="alert alert-warning">Laporan ini sedang menunggu persetujuan Supervisor/Owner.</div>
                                    <?php } ?>

                                    <?php if(!in_array($detail['status'],['resolved','closed','rejected']) && !is_auto_approval_issue($detail)){ ?>
                                    <hr />
                                    <h5>Ubah Status Laporan</h5>
                                    <form method="post" action="issue_add.php?id_issue=<?php echo urlencode($id_issue); ?>" class="form-inline">
                                        <div class="form-group">
                                            <select name="status_baru" class="form-control">
                                                <?php foreach($status_opsi as $s){
                                                    if($s === $detail['status'] || $s === 'rejected') continue;
                                                    if($s === 'resolved' && !$is_admin) continue;
                                                    echo '<option value="'.htmlspecialchars($s).'">'.$status_label[$s].'</option>';
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" name="catatan" class="form-control" placeholder="Catatan tambahan (opsional)" style="width:300px" />
                                        </div>
                                        <button type="submit" name="btnupdate_status" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Status</button>
                                    </form>
                                    <p class="text-muted"><small>Persetujuan atau penolakan hanya bisa dilakukan Supervisor/Owner.</small></p>
                                    <?php } ?>

                                    <hr />
                                    <h5>Riwayat Perubahan</h5>
                                    <table class="table table-bordered table-striped">
                                        <thead><tr><th>Tanggal</th><th>Oleh</th><th>Status</th><th>Catatan</th></tr></thead>
                                        <tbody>
                                        <?php if(count($progress)>0){ foreach($progress as $p){ ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($p['tanggal']); ?></td>
                                                <td><?php echo htmlspecialchars($p['nama_user'] ?? ('User#'.$p['oleh'])); ?></td>
                                                <td><?php echo htmlspecialchars(($status_label[$p['status_before']] ?? $p['status_before']).' → '.($status_label[$p['status_after']] ?? $p['status_after'])); ?></td>
                                                <td><?php echo htmlspecialchars($p['catatan']); ?></td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="4" class="text-center">Belum ada perubahan status</td></tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                    <a href="issue_add.php" class="btn">&laquo; Kembali ke Daftar</a>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <!-- END DETAIL VIEW -->
                        <?php } ?>
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
