<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

// Debug helpers to avoid blank 500 and ensure proper charset
error_reporting(E_ALL);
ini_set('display_errors', 1);
if(!isset($koneksi) || !$koneksi){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die('DB connection failed: '.mysqli_connect_error());
}
@mysqli_set_charset($koneksi, 'utf8mb4');
if(function_exists('mysqli_report')){ @mysqli_report(MYSQLI_REPORT_OFF); }

// Get user info
$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari && isset($tm_cari['nama_user']) ? $tm_cari['nama_user'] : 'admin';
$lvl_akses = $tm_cari && isset($tm_cari['user_akses']) ? $tm_cari['user_akses'] : '';
$foto_user = $tm_cari && isset($tm_cari['foto_user']) ? $tm_cari['foto_user'] : '';
if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

// Helpers
function post($key, $default='') { return isset($_POST[$key]) ? trim($_POST[$key]) : $default; }
function get($key, $default='') { return isset($_GET[$key]) ? trim($_GET[$key]) : $default; }

$no_pr = get('no_pr');
if($no_pr=='') { $no_pr = post('no_pr'); }
$message = '';

// ==================== PR LIST LOGIC (when no_pr is empty) ====================
$pr_list_rows = null;
$pr_total_rows = 0;
$pr_total_pages = 1;
$pr_page = 1;
if($no_pr == ''){
    $pr_status = get('status','');
    $pr_from = get('from','');
    $pr_to = get('to','');
    $pr_q = get('q','');
    $pr_limit = (int)(get('limit','20'));
    if($pr_limit<=0||$pr_limit>200) $pr_limit=20;
    $pr_page = (int)(get('page','1'));
    if($pr_page<=0) $pr_page=1;
    $pr_offset = ($pr_page-1)*$pr_limit;

    $pr_where = ["h.kd_cabang='".mysqli_real_escape_string($koneksi,$kd_cabang)."'"];
    if($pr_status!==''){ $pr_where[] = "h.status_pr='".mysqli_real_escape_string($koneksi,$pr_status)."'"; }
    if($pr_from!==''){ $pr_where[] = "h.tanggal_pr >= '".mysqli_real_escape_string($koneksi,$pr_from)."'"; }
    if($pr_to!==''){ $pr_where[] = "h.tanggal_pr <= '".mysqli_real_escape_string($koneksi,$pr_to)."'"; }
    if($pr_q!==''){
        $pr_qs = mysqli_real_escape_string($koneksi,$pr_q);
        $pr_where[] = "(h.no_pr LIKE '%$pr_qs%' OR h.requester LIKE '%$pr_qs%')";
    }
    $pr_wsql = implode(' AND ', $pr_where);

    // Count
    $pr_cnt = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tblpurchase_request_header h WHERE $pr_wsql");
    $pr_total_rows = (int)mysqli_fetch_assoc($pr_cnt)['total'];
    $pr_total_pages = max(1, (int)ceil($pr_total_rows/$pr_limit));
    if($pr_page>$pr_total_pages) $pr_page=$pr_total_pages;
    $pr_offset = ($pr_page-1)*$pr_limit;

    // Query
    $pr_sql = "SELECT h.*,
                   (SELECT COUNT(1) FROM tblpurchase_request_detail d WHERE d.no_pr=h.no_pr) AS jml_item,
                   (SELECT COALESCE(SUM(d.quantity),0) FROM tblpurchase_request_detail d WHERE d.no_pr=h.no_pr) AS tot_qty
            FROM tblpurchase_request_header h
            WHERE $pr_wsql
            ORDER BY h.tanggal_pr DESC, h.no_pr DESC
            LIMIT $pr_limit OFFSET $pr_offset";
    $pr_list_rows = mysqli_query($koneksi, $pr_sql);
}
// ==================== END PR LIST LOGIC ====================

// Minimal XLSX reader for first worksheet (sheet1)
if(!function_exists('xlsx_letters_to_index')){
    function xlsx_letters_to_index($letters){
        $letters = strtoupper(preg_replace('/[^A-Z]/','',$letters));
        $len = strlen($letters); $num = 0;
        for($i=0;$i<$len;$i++){ $num = $num*26 + (ord($letters[$i]) - 64); }
        return $num - 1; // zero-based
    }
}
if(!function_exists('xlsx_read_rows')){
    function xlsx_read_rows($path){
        if(!class_exists('ZipArchive')){ return ['error'=>'ZipArchive not available']; }
        $zip = new ZipArchive();
        if($zip->open($path)!==true){ return ['error'=>'Cannot open xlsx']; }
        $shared = [];
        $idx = $zip->locateName('xl/sharedStrings.xml');
        if($idx!==false){
            $xml = $zip->getFromIndex($idx);
            if($xml!==false){
                $sx = @simplexml_load_string($xml);
                if($sx && isset($sx->si)){
                    foreach($sx->si as $si){
                        $txt = '';
                        if(isset($si->t)){ $txt = (string)$si->t; }
                        elseif(isset($si->r)){
                            foreach($si->r as $r){ $txt .= (string)$r->t; }
                        }
                        $shared[] = $txt;
                    }
                }
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        if($sheet===false){ $zip->close(); return ['error'=>'sheet1 not found']; }
        $sx = @simplexml_load_string($sheet);
        if(!$sx){ $zip->close(); return ['error'=>'Invalid sheet xml']; }
        $rows = [];
        if(isset($sx->sheetData) && isset($sx->sheetData->row)){
            foreach($sx->sheetData->row as $row){
                $cells = [];
                foreach($row->c as $c){
                    $r = isset($c['r']) ? (string)$c['r'] : '';
                    $colLetters = preg_replace('/[0-9]/','',$r);
                    $ci = xlsx_letters_to_index($colLetters);
                    $t = isset($c['t']) ? (string)$c['t'] : '';
                    $val = '';
                    if($t==='s'){
                        $vi = isset($c->v) ? (int)$c->v : 0; $val = isset($shared[$vi]) ? $shared[$vi] : '';
                    } elseif($t==='inlineStr' && isset($c->is->t)){
                        $val = (string)$c->is->t;
                    } else {
                        $val = isset($c->v) ? (string)$c->v : '';
                    }
                    $cells[$ci] = $val;
                }
                if(!empty($cells)){
                    ksort($cells);
                    $rowArr = [];
                    $maxi = max(array_keys($cells));
                    for($i=0;$i<=$maxi;$i++){ $rowArr[] = isset($cells[$i]) ? $cells[$i] : ''; }
                    $rows[] = $rowArr;
                }
            }
        }
        $zip->close();
        return $rows;
    }
}

// Download template CSV untuk import PR
if(isset($_GET['download']) && $_GET['download']==='template_pr_csv'){
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_import_pr.csv"');
    echo "no_item,quantity\r\n";
    echo "BRG0001,10\r\n";
    echo "BRG0002,5\r\n";
    exit;
}

// Download template XLSX untuk import PR
if(isset($_GET['download']) && $_GET['download']==='template_pr_xlsx'){
    // Clear all output buffers to prevent corruption
    while (ob_get_level()) { ob_end_clean(); }

    if(!class_exists('ZipArchive')){
        header('Content-Type: text/plain');
        echo 'ZipArchive tidak tersedia';
        exit;
    }

    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'tplxlsx_');
    if(file_exists($tmp)){ @unlink($tmp); }
    $tmp .= '.xlsx';
    if($zip->open($tmp, ZipArchive::CREATE)!==true){ header('Content-Type:text/plain'); echo 'Gagal membuat file'; exit; }
    // Content_Types
    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8"?>'
       .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
       .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
       .'<Default Extension="xml" ContentType="application/xml"/>'
       .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
       .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
       .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
       .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
       .'</Types>'
    );
    // _rels/.rels
    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8"?>'
       .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
       .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
       .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
       .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
       .'</Relationships>'
    );
    // xl/workbook.xml
    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8"?>'
       .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
       .'<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
       .'</workbook>'
    );
    // xl/_rels/workbook.xml.rels
    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8"?>'
       .'<Relationships xmlns="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
       .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
       .'</Relationships>'
    );
    // docProps/core.xml
    $zip->addFromString('docProps/core.xml',
        '<?xml version="1.0" encoding="UTF-8"?>'
       .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
       .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
       .'xmlns:dcterms="http://purl.org/dc/terms/" '
       .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
       .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
       .'<dc:creator>web-bengkel</dc:creator>'
       .'<cp:lastModifiedBy>web-bengkel</cp:lastModifiedBy>'
       .'<dcterms:created xsi:type="dcterms:W3CDTF">'.date('c').'</dcterms:created>'
       .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.date('c').'</dcterms:modified>'
       .'</cp:coreProperties>'
    );
    // docProps/app.xml
    $zip->addFromString('docProps/app.xml',
        '<?xml version="1.0" encoding="UTF-8"?>'
       .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
       .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
       .'<Application>web-bengkel</Application>'
       .'</Properties>'
    );
    // xl/worksheets/sheet1.xml with headers and sample
    $sheetXml = '<?xml version="1.0" encoding="UTF-8"?>'
       .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
       .'<sheetData>'
       .'<row r="1">'
       .'<c r="A1" t="inlineStr"><is><t>no_item</t></is></c>'
       .'<c r="B1" t="inlineStr"><is><t>quantity</t></is></c>'
       .'</row>'
       .'<row r="2">'
       .'<c r="A2" t="inlineStr"><is><t>BRG0001</t></is></c>'
       .'<c r="B2"><v>10</v></c>'
       .'</row>'
       .'<row r="3">'
       .'<c r="A3" t="inlineStr"><is><t>BRG0002</t></is></c>'
       .'<c r="B3"><v>5</v></c>'
       .'</row>'
       .'</sheetData>'
       .'</worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    // Verify file was created
    if(!file_exists($tmp)){
        header('Content-Type: text/plain');
        echo 'Error: File template gagal dibuat';
        exit;
    }

    // Send file to browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_import_pr.xlsx"');
    header('Content-Length: '.filesize($tmp));
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // Read and output file
    readfile($tmp);

    // Clean up
    @unlink($tmp);
    exit;
}

// Start PR
if(isset($_POST['btnstart'])){
    // Try to use stored procedure, fallback to simple generator
    $no_gen = '';
    if (mysqli_query($koneksi, "CALL sp_generate_no_pr('".$kd_cabang."', @p_no_pr)")) {
        // Flush result set from CALL before issuing next query (MariaDB requirement)
        mysqli_next_result($koneksi);
        $res = mysqli_query($koneksi, "SELECT @p_no_pr as no_pr");
        if ($res) { $row = mysqli_fetch_assoc($res); $no_gen = $row['no_pr']; }
    }
    if ($no_gen == '') {
        $no_gen = 'PR'.date('Ym').$kd_cabang.str_pad(rand(1,99999),5,'0',STR_PAD_LEFT);
    }
    $tgl_now = date('Y-m-d');
    mysqli_query($koneksi, "INSERT INTO tblpurchase_request_header 
        (no_pr, tanggal_pr, tanggal_butuh, requester, departemen, alasan, kd_cabang, created_by, status_pr)
        VALUES
        ('{$no_gen}', '{$tgl_now}', '{$tgl_now}', '{$_nama}', '', '', '{$kd_cabang}', '{$_nama}', 'draft')");
    header("Location: pr_add.php?no_pr=".$no_gen);
    exit;
}

// Save header fields
if(isset($_POST['btnsave_header']) && $no_pr!=''){
    $qst = mysqli_query($koneksi, "SELECT status_pr FROM tblpurchase_request_header WHERE no_pr='".mysqli_real_escape_string($koneksi,$no_pr)."'");
    $st = $qst ? mysqli_fetch_assoc($qst) : null;
    if(!$st || $st['status_pr']!='draft'){
        $message = 'Header hanya bisa disimpan saat status draft.';
    } else {
        $tanggal_butuh = post('tanggal_butuh');
        $departemen    = post('departemen');
        $alasan        = post('alasan');
        if ($tanggal_butuh=='') { $tanggal_butuh = date('Y-m-d'); }
        mysqli_query($koneksi, "UPDATE tblpurchase_request_header SET 
            tanggal_butuh='{$tanggal_butuh}', departemen='{$departemen}', alasan='{$alasan}'
            WHERE no_pr='{$no_pr}'");
        $message = 'Header PR disimpan';
    }
}

// Add item
if(isset($_POST['btnadd_item']) && $no_pr!=''){
    // pastikan PR draft
    $qst = mysqli_query($koneksi, "SELECT status_pr FROM tblpurchase_request_header WHERE no_pr='".mysqli_real_escape_string($koneksi,$no_pr)."'");
    $st = $qst ? mysqli_fetch_assoc($qst) : null;
    if(!$st || $st['status_pr']!='draft'){
        $message = 'PR bukan status draft. Tidak bisa menambah item.';
    } else {
        $no_item = post('no_item');
        $quantity = (int)post('quantity','0');
        $harga_est = (float)post('harga_estimasi','0');
        $satuan = post('satuan');
        $ket = post('keterangan');
        if ($no_item!='' && $quantity>0) {
            $qnm = mysqli_query($koneksi, "SELECT namaitem, satuan FROM tblitem WHERE noitem='".mysqli_real_escape_string($koneksi,$no_item)."'");
            $nm = mysqli_fetch_array($qnm);
            $nama_item = $nm ? $nm['namaitem'] : $no_item;
            if ($satuan=='') { $satuan = $nm ? $nm['satuan'] : ''; }
            $rs = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS nx FROM tblpurchase_request_detail WHERE no_pr='{$no_pr}'");
            $nx = mysqli_fetch_assoc($rs)['nx'];
            $total_est = $harga_est * $quantity;
            mysqli_query($koneksi, "INSERT INTO tblpurchase_request_detail 
                (no_pr, nobaris, no_item, nama_item, quantity, qty_approved, qty_po, satuan, harga_estimasi, total_estimasi, keterangan)
                VALUES
                ('{$no_pr}', {$nx}, '{$no_item}', '".mysqli_real_escape_string($koneksi,$nama_item)."', {$quantity}, 0, 0, '{$satuan}', {$harga_est}, {$total_est}, '".mysqli_real_escape_string($koneksi,$ket)."')");
            $message = 'Item ditambahkan';
        }
    }
}

// Delete item
if(isset($_GET['delid']) && $no_pr!=''){
    $delid = (int)$_GET['delid'];
    $qst = mysqli_query($koneksi, "SELECT status_pr FROM tblpurchase_request_header WHERE no_pr='".mysqli_real_escape_string($koneksi,$no_pr)."'");
    $st = $qst ? mysqli_fetch_assoc($qst) : null;
    if($st && $st['status_pr']=='draft'){
        mysqli_query($koneksi, "DELETE FROM tblpurchase_request_detail WHERE id={$delid} AND no_pr='{$no_pr}'");
        header("Location: pr_add.php?no_pr=".$no_pr);
        exit;
    } else {
        $message = 'PR bukan status draft. Tidak bisa menghapus item.';
    }
}

// Submit PR
if(isset($_POST['btnsubmit']) && $no_pr!=''){
    // hanya boleh submit saat draft dan minimal 1 item
    $qst = mysqli_query($koneksi, "SELECT status_pr FROM tblpurchase_request_header WHERE no_pr='".mysqli_real_escape_string($koneksi,$no_pr)."'");
    $st = $qst ? mysqli_fetch_assoc($qst) : null;
    $qd = mysqli_query($koneksi, "SELECT COUNT(1) AS c FROM tblpurchase_request_detail WHERE no_pr='".mysqli_real_escape_string($koneksi,$no_pr)."'");
    $cd = $qd ? (int)mysqli_fetch_assoc($qd)['c'] : 0;
    if(!$st || $st['status_pr']!='draft'){
        $message = 'PR hanya bisa disubmit dari status draft.';
    } elseif($cd<=0){
        $message = 'PR belum memiliki item, tidak bisa disubmit.';
    } else {
        mysqli_query($koneksi, "UPDATE tblpurchase_request_header SET status_pr='submitted' WHERE no_pr='{$no_pr}'");
        $message = 'PR dikirim untuk persetujuan';
    }
}

// Reopen PR to DRAFT (allowed only if belum dipakai PO)
if(isset($_POST['btnreopen']) && $no_pr!=''){
    $no_pr_esc = mysqli_real_escape_string($koneksi, $no_pr);
    $res_used = mysqli_query($koneksi, "SELECT COALESCE(SUM(qty_po),0) AS used FROM tblpurchase_request_detail WHERE no_pr='$no_pr_esc'");
    if($res_used){
        $rowu = mysqli_fetch_assoc($res_used);
        $used = $rowu ? (int)$rowu['used'] : 0;
        if($used>0){
            $message = 'PR sudah dipakai di PO, tidak bisa dikembalikan ke draft';
        } else {
            mysqli_query($koneksi, "UPDATE tblpurchase_request_header SET status_pr='draft' WHERE no_pr='$no_pr_esc'");
            $message = 'PR dikembalikan ke draft';
        }
    } else {
        // jika query gagal, tetap coba set draft
        mysqli_query($koneksi, "UPDATE tblpurchase_request_header SET status_pr='draft' WHERE no_pr='$no_pr_esc'");
        $message = 'PR dikembalikan ke draft';
    }
}

if(isset($_POST['btnimport_pr']) && $no_pr!=''){
    $qst = mysqli_query($koneksi, "SELECT status_pr FROM tblpurchase_request_header WHERE no_pr='".mysqli_real_escape_string($koneksi,$no_pr)."'");
    $st = $qst ? mysqli_fetch_assoc($qst) : null;
    if(!$st || $st['status_pr']!='draft'){
        $message = 'PR bukan status draft. Import dibatalkan.';
    } else {
        if(isset($_FILES['file_pr_import']) && is_uploaded_file($_FILES['file_pr_import']['tmp_name'])){
            $tmp = $_FILES['file_pr_import']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['file_pr_import']['name'], PATHINFO_EXTENSION));
            $rows = [];
            if($ext==='xlsx'){
                $rows = xlsx_read_rows($tmp);
                if(is_array($rows) && isset($rows['error'])){ $message = 'Gagal membaca XLSX: '.$rows['error']; $rows = []; }
            } elseif($ext==='csv'){
                $f = fopen($tmp,'r');
                if($f){ while(($data = fgetcsv($f, 0, ',')) !== false){ $rows[] = $data; } fclose($f); }
            } else {
                $message = 'Format file harus XLSX atau CSV.';
            }
            if(empty($message)){
                $rownum = 0; $ok=0; $skip=0;
                foreach($rows as $data){
                    $rownum++;
                    if($rownum==1){
                        $h0 = isset($data[0]) ? strtolower(trim($data[0])) : '';
                        if(in_array($h0, ['no_item','kode','kode_item'])){ continue; }
                    }
                    $no_item = isset($data[0]) ? trim($data[0]) : '';
                    $qty = isset($data[1]) ? (int)trim($data[1]) : 0;
                    if($no_item=='' || $qty<=0){ $skip++; continue; }
                    $qnm = mysqli_query($koneksi, "SELECT namaitem, satuan FROM tblitem WHERE noitem='".mysqli_real_escape_string($koneksi,$no_item)."'");
                    $nm = $qnm ? mysqli_fetch_array($qnm) : null;
                    $nama_item = $nm && isset($nm['namaitem']) ? $nm['namaitem'] : $no_item;
                    $satuan = $nm && isset($nm['satuan']) ? $nm['satuan'] : '';
                    $rs = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS nx FROM tblpurchase_request_detail WHERE no_pr='".mysqli_real_escape_string($koneksi,$no_pr)."'");
                    $nx = $rs ? (int)mysqli_fetch_assoc($rs)['nx'] : 1;
                    $harga_est = 0; $total_est = 0; $ket = '';
                    $ins = mysqli_query($koneksi, "INSERT INTO tblpurchase_request_detail (no_pr, nobaris, no_item, nama_item, quantity, qty_approved, qty_po, satuan, harga_estimasi, total_estimasi, keterangan) VALUES ('".mysqli_real_escape_string($koneksi,$no_pr)."', $nx, '".mysqli_real_escape_string($koneksi,$no_item)."', '".mysqli_real_escape_string($koneksi,$nama_item)."', $qty, 0, 0, '".mysqli_real_escape_string($koneksi,$satuan)."', $harga_est, $total_est, '".mysqli_real_escape_string($koneksi,$ket)."')");
                    if($ins){ $ok++; } else { $skip++; }
                }
                $message = 'Import selesai: '.$ok.' baris ditambahkan, '.$skip.' dilewati.';
            }
        } else {
            $message = 'File belum dipilih.';
        }
    }
}

// Load data
$header = null; $details = [];
if($no_pr!=''){
    $qh = mysqli_query($koneksi, "SELECT * FROM tblpurchase_request_header WHERE no_pr='{$no_pr}'");
    $header = mysqli_fetch_assoc($qh);
    $qd = mysqli_query($koneksi, "SELECT * FROM tblpurchase_request_detail WHERE no_pr='{$no_pr}' ORDER BY nobaris ASC");
    while($row = mysqli_fetch_assoc($qd)) { $details[] = $row; }
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
                    <li class="active">Purchase Request</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row">
                    <div class="col-xs-12">
                        <?php if($message!=''){ echo '<div class="alert alert-success">'.$message.'</div>'; } ?>
                        <?php if($no_pr==''){ ?>
                            <!-- PR LIST VIEW -->
                            <div class="well" style="padding:10px;">
                                <form class="form-inline" method="get">
                                    <div class="form-group"><label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="">- Semua -</option>
                                            <?php
                                            $pr_statuses = ['draft','submitted','approved','rejected','closed'];
                                            foreach($pr_statuses as $st){
                                                $pr_sel = ($pr_status===$st)?'selected':'';
                                                echo "<option value=\"$st\" $pr_sel>".ucfirst($st)."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group"><label>Dari</label>
                                        <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($pr_from); ?>" />
                                    </div>
                                    <div class="form-group"><label>Sampai</label>
                                        <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($pr_to); ?>" />
                                    </div>
                                    <div class="form-group"><label>Cari</label>
                                        <input type="text" name="q" class="form-control" placeholder="No. PR/Requester" value="<?php echo htmlspecialchars($pr_q); ?>" />
                                    </div>
                                    <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Filter</button>
                                </form>
                            </div>
                            <div class="widget-box">
                                <div class="widget-header widget-header-blue widget-header-flat">
                                    <h4 class="widget-title lighter"><i class="ace-icon fa fa-list"></i> Daftar Purchase Request</h4>
                                    <div class="widget-toolbar">
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="btnstart" class="btn btn-success btn-xs"><i class="fa fa-plus"></i> Buat PR Baru</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <div class="table-header">Hasil: <?php echo (int)$pr_total_rows; ?> data</div>
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No. PR</th>
                                                    <th>Tanggal</th>
                                                    <th>Requester</th>
                                                    <th class="text-right">Jumlah Item</th>
                                                    <th class="text-right">Total Qty</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if($pr_list_rows && mysqli_num_rows($pr_list_rows)>0){ ?>
                                                <?php while($r = mysqli_fetch_assoc($pr_list_rows)){ ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($r['no_pr']); ?></td>
                                                        <td><?php echo htmlspecialchars($r['tanggal_pr']); ?></td>
                                                        <td><?php echo htmlspecialchars($r['requester']); ?></td>
                                                        <td class="text-right"><?php echo (int)$r['jml_item']; ?></td>
                                                        <td class="text-right"><?php echo (int)$r['tot_qty']; ?></td>
                                                        <td><span class="label <?php
                                                            $st = $r['status_pr'];
                                                            if($st=='draft') echo 'label-default';
                                                            elseif($st=='submitted') echo 'label-info';
                                                            elseif($st=='approved') echo 'label-success';
                                                            elseif($st=='rejected') echo 'label-danger';
                                                            else echo 'label-warning';
                                                        ?>"><?php echo htmlspecialchars($st); ?></span></td>
                                                        <td>
                                                            <a class="btn btn-minier btn-primary" href="pr_add.php?no_pr=<?php echo urlencode($r['no_pr']); ?>"><i class="fa fa-edit"></i> Buka</a>
                                                            <a class="btn btn-minier btn-warning" href="pesanan_pembelian_add.php?pr=<?php echo urlencode($r['no_pr']); ?>"><i class="fa fa-shopping-cart"></i> Buat PO</a>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr><td colspan="7" class="text-center">Tidak ada data</td></tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                        <?php if($pr_total_pages>1){ ?>
                                        <div class="widget-toolbox padding-8 clearfix">
                                            <nav aria-label="Page navigation" class="text-center">
                                                <ul class="pagination" style="margin:0;">
                                                    <?php
                                                    $pr_build_qs = ['status'=>$pr_status,'from'=>$pr_from,'to'=>$pr_to,'q'=>$pr_q,'limit'=>$pr_limit];
                                                    if($pr_page>1){
                                                        $pr_build_qs['page']=$pr_page-1;
                                                        echo '<li><a href="?'.http_build_query($pr_build_qs).'">&laquo;</a></li>';
                                                    }
                                                    for($i=max(1,$pr_page-2); $i<=min($pr_total_pages,$pr_page+2); $i++){
                                                        $pr_act = ($i==$pr_page)?'class="active"':'';
                                                        $pr_build_qs['page']=$i;
                                                        echo "<li $pr_act><a href=\"?".http_build_query($pr_build_qs)."\">$i</a></li>";
                                                    }
                                                    if($pr_page<$pr_total_pages){
                                                        $pr_build_qs['page']=$pr_page+1;
                                                        echo '<li><a href="?'.http_build_query($pr_build_qs).'">&raquo;</a></li>';
                                                    }
                                                    ?>
                                                </ul>
                                            </nav>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <!-- END PR LIST VIEW -->
                        <?php } else { ?>
                            <div class="widget-box">
                                <div class="widget-header widget-header-blue widget-header-flat">
                                    <h4 class="widget-title lighter"><i class="ace-icon fa fa-file-text-o"></i> PR #<?php echo htmlspecialchars($no_pr); ?></h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form class="form-horizontal" method="post" action="pr_add.php?no_pr=<?php echo urlencode($no_pr); ?>">
                                            <input type="hidden" name="no_pr" value="<?php echo htmlspecialchars($no_pr); ?>" />
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Tanggal Butuh</label>
                                                        <div class="col-sm-8"><input type="date" name="tanggal_butuh" class="form-control" value="<?php echo htmlspecialchars($header['tanggal_butuh']); ?>" /></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label">Departemen</label>
                                                        <div class="col-sm-8"><input type="text" name="departemen" class="form-control" value="<?php echo htmlspecialchars($header['departemen']); ?>" /></div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label">Requester</label>
                                                        <div class="col-sm-9"><input type="text" class="form-control" value="<?php echo htmlspecialchars($header['requester']); ?>" readonly /></div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label">Status</label>
                                                        <div class="col-sm-9"><input type="text" class="form-control" value="<?php echo htmlspecialchars($header['status_pr']); ?>" readonly /></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">Alasan</label>
                                                <div class="col-sm-10"><textarea name="alasan" class="form-control" rows="2"><?php echo htmlspecialchars($header['alasan']); ?></textarea></div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-6">
                                                    <?php if(isset($header['status_pr']) && $header['status_pr']=='draft'){ ?>
                                                    <button type="submit" name="btnsave_header" class="btn btn-success"><i class="fa fa-save"></i> Simpan Header</button>
                                                    <?php } ?>
                                                    <a href="pr_add.php" class="btn">PR Baru</a>
                                                </div>
                                                <div class="col-sm-6 text-right">
                                                    <?php if(isset($header['status_pr']) && in_array($header['status_pr'], ['submitted','rejected'])){ ?>
                                                    <button type="submit" name="btnreopen" class="btn btn-warning"><i class="fa fa-unlock"></i> Kembalikan ke Draft</button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </form>
                                        <?php if(isset($header['status_pr']) && $header['status_pr']=='draft'){ ?>
                                        <hr />
                                        <h5>Tambah Item</h5>
                                        <form class="form-inline" method="post" action="pr_add.php?no_pr=<?php echo urlencode($no_pr); ?>">
                                            <input type="hidden" name="no_pr" value="<?php echo htmlspecialchars($no_pr); ?>" />
                                            <div class="form-group"><label>Kode Item</label>
                                                <input type="text" name="no_item" class="form-control" placeholder="Kode Item" required />
                                            </div>
                                            <div class="form-group"><label>Qty</label>
                                                <input type="number" name="quantity" class="form-control" min="1" value="1" required />
                                            </div>
                                            <div class="form-group"><label>Satuan</label>
                                                <input type="text" name="satuan" class="form-control" placeholder="Satuan" />
                                            </div>
                                            <div class="form-group"><label>Harga Est.</label>
                                                <input type="number" step="0.01" name="harga_estimasi" class="form-control" value="0" />
                                            </div>
                                            <div class="form-group" style="width:30%"><label>Keterangan</label>
                                                <input type="text" name="keterangan" class="form-control" style="width:100%" />
                                            </div>
                                            <button type="submit" name="btnadd_item" class="btn btn-primary"><i class="fa fa-plus"></i></button>
                                        </form>
                                        <hr />
                                        <h5>Import dari Excel (.xlsx / .csv)</h5>
                                        <form class="form-inline" method="post" enctype="multipart/form-data" action="pr_add.php?no_pr=<?php echo urlencode($no_pr); ?>">
                                            <input type="hidden" name="no_pr" value="<?php echo htmlspecialchars($no_pr); ?>" />
                                            <div class="form-group"><label>File Excel</label>
                                                <input type="file" name="file_pr_import" class="form-control" accept=".xlsx,.csv" required />
                                            </div>
                                            <button type="submit" name="btnimport_pr" class="btn btn-success"><i class="fa fa-upload"></i> Import</button>
                                            <a class="btn btn-info" href="pr_add.php?download=template_pr_xlsx"><i class="fa fa-download"></i> Template XLSX</a>
                                            <a class="btn" href="pr_add.php?download=template_pr_csv"><i class="fa fa-download"></i> Template CSV</a>
                                        </form>
                                        <?php } ?>
                                        <div class="space-10"></div>
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Kode</th>
                                                    <th>Nama Item</th>
                                                    <th class="text-right">Qty</th>
                                                    <th>Satuan</th>
                                                    <th class="text-right">Harga Est.</th>
                                                    <th class="text-right">Total Est.</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $i=0; $gt=0; foreach($details as $d){ $i++; $gt += $d['total_estimasi']; ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo htmlspecialchars($d['no_item']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['nama_item']); ?></td>
                                                    <td class="text-right"><?php echo (int)$d['quantity']; ?></td>
                                                    <td><?php echo htmlspecialchars($d['satuan']); ?></td>
                                                    <td class="text-right"><?php echo number_format($d['harga_estimasi'],2); ?></td>
                                                    <td class="text-right"><?php echo number_format($d['total_estimasi'],2); ?></td>
                                                    <td>
                                                        <?php if(isset($header['status_pr']) && $header['status_pr']=='draft'){ ?>
                                                        <a class="btn btn-minier btn-danger" href="pr_add.php?no_pr=<?php echo urlencode($no_pr); ?>&delid=<?php echo (int)$d['id']; ?>">Hapus</a>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="6" class="text-right">Grand Total</th>
                                                    <th class="text-right"><?php echo number_format($gt,2); ?></th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <?php if(isset($header['status_pr']) && $header['status_pr']=='draft'){ ?>
                                        <form method="post" action="pr_add.php?no_pr=<?php echo urlencode($no_pr); ?>">
                                            <input type="hidden" name="no_pr" value="<?php echo htmlspecialchars($no_pr); ?>" />
                                            <button type="submit" name="btnsubmit" class="btn btn-warning"><i class="fa fa-paper-plane"></i> Submit PR</button>
                                        </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
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
