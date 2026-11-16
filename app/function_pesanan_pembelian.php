<?php
// Seri nomor PO web = "PW" + yy + 9 digit urut. Seri "PS" dipakai PO dari Access (reload center),
// jadi nomor web tidak boleh lagi bentrok dengan nomor Access (keputusan Rafi 2026-11-16).
function OtomatisID()
{
    include "../config/koneksi.php";
    $thn = substr(date('Y'), 2, 2);
    $pref = "PW" . $thn;
    $result = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(no_order, 5) AS UNSIGNED)) AS LastID FROM tblorder_header WHERE no_order LIKE '" . $pref . "%'");
    $row = $result ? mysqli_fetch_array($result) : null;
    return (int)($row['LastID'] ?? 0);
}

function FormatNoTrans($num) {
    $thn = substr(date('Y'), 2, 2);
    return "PW" . $thn . sprintf('%09d', (int)$num + 1);
}
?>