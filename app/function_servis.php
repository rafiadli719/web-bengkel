<?php
/**
 * Generator no_service (nomor tiket servis).
 *
 * FIX 2026-08-23: sebelumnya OtomatisID() pakai SELECT COUNT(no_service),
 * lalu FormatNoTrans() nambah +1 — dua request bersamaan (cabang/staf beda)
 * bisa baca COUNT yang sama dan hasilkan no_service kembar (kolom
 * no_service TIDAK unique/primary key, jadi DB tidak menolak).
 *
 * Fix: pakai tabel counter kecil (tblservice_no_counter, 1 baris per
 * tahun) + pola atomic-increment MySQL "UPDATE ... SET seq =
 * LAST_INSERT_ID(seq + 1)". InnoDB row-lock otomatis nyerialize dua
 * request yang barengan, jadi gak mungkin dua-duanya dapat angka sama.
 * Signature kedua fungsi TETAP SAMA — semua caller lama (save-no-servis-*.php,
 * helper-functions.php) jalan tanpa perlu diubah.
 */
function OtomatisID()
{
    include "../config/koneksi.php";

    $tahun = (int) date('Y');

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS tblservice_no_counter (
        tahun INT NOT NULL PRIMARY KEY,
        seq INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");

    // Baris counter tahun ini belum ada? Seed dari angka tertinggi yang
    // SUDAH terpakai (bukan COUNT) biar gak nabrak no_service existing.
    $qCheck = mysqli_query($koneksi, "SELECT 1 FROM tblservice_no_counter WHERE tahun = $tahun");
    if (!$qCheck || mysqli_num_rows($qCheck) === 0) {
        $thn2 = substr((string) $tahun, 2, 2);
        $prefix = mysqli_real_escape_string($koneksi, "SV" . $thn2);
        $qSeed = mysqli_query($koneksi, "SELECT COALESCE(MAX(CAST(SUBSTRING(no_service, 5) AS UNSIGNED)), 0) AS mx
                                          FROM tblservice WHERE no_service LIKE '{$prefix}%'");
        $seed = 0;
        if ($qSeed && ($rowSeed = mysqli_fetch_assoc($qSeed))) {
            $seed = (int) $rowSeed['mx'];
        }
        mysqli_query($koneksi, "INSERT IGNORE INTO tblservice_no_counter (tahun, seq) VALUES ($tahun, $seed)");
    }

    // Atomic increment — aman dipanggil bersamaan dari cabang/staf berbeda.
    mysqli_query($koneksi, "UPDATE tblservice_no_counter SET seq = LAST_INSERT_ID(seq + 1) WHERE tahun = $tahun");
    $next = (int) mysqli_insert_id($koneksi);

    if ($next <= 0) {
        // Fallback (harusnya tidak pernah kejadian): baca langsung.
        $qFallback = mysqli_query($koneksi, "SELECT seq FROM tblservice_no_counter WHERE tahun = $tahun");
        $rowFallback = mysqli_fetch_assoc($qFallback);
        $next = (int) ($rowFallback['seq'] ?? 0);
    }

    // Dikembalikan sudah "num-1" biar kompatibel dengan FormatNoTrans() lama
    // yang selalu nambah +1 sebelum format — supaya signature/pemanggilan
    // di semua file lain tidak perlu berubah sama sekali.
    return $next - 1;
}

function FormatNoTrans($num) {
            $thn_skr=date('Y');
            $thn=substr($thn_skr,2,2);
        $num=$num+1; switch (strlen($num))
        {
        case 1 : $NoTrans = "SV".$thn."00000000".$num; break;
        case 2 : $NoTrans = "SV".$thn."0000000".$num; break;
        case 3 : $NoTrans = "SV".$thn."000000".$num; break;
        case 4 : $NoTrans = "SV".$thn."00000".$num; break;
        case 5 : $NoTrans = "SV".$thn."0000".$num; break;
        case 6 : $NoTrans = "SV".$thn."000".$num; break;
        case 7 : $NoTrans = "SV".$thn."00".$num; break;
        case 8 : $NoTrans = "SV".$thn."0".$num; break;
        case 9 : $NoTrans = "SV".$thn.$num; break;
        default: $NoTrans = $num;
        }
        return $NoTrans;
}

/**
 * FIX 2026-08-23 (lanjutan): generator no_service lain SELAIN OtomatisID()
 * (save_garapan.php, servis-garansi.php, servis-reguler-jemput.php,
 * ajax-save-service.php) masing-masing punya generator inline sendiri —
 * 3 pakai pola "SELECT MAX(...)+1" (race condition sama seperti
 * OtomatisID() lama) dan 1 pakai rand(1,999) (bisa nabrak, bukan cuma
 * race). Keempatnya punya prefix format beda (GAR-, SRV, SV, SERV) yang
 * dipakai/dicek di file lain, jadi format TIDAK diubah — cuma cara
 * generate nomor urutnya yang diganti ke atomic-increment per prefix,
 * pola sama seperti OtomatisID(): tabel counter kecil + row-lock MySQL
 * "UPDATE ... SET seq = LAST_INSERT_ID(seq + 1)".
 *
 * @param mysqli $koneksi
 * @param string $seqKey  kunci counter unik (biasanya = $prefix, atau
 *                         $prefix + periode kalau prefix-nya per-hari/bulan)
 * @param string $prefix  prefix no_service yang mau dicari MAX-nya buat seed
 * @return int    nomor urut berikutnya (belum di-pad, caller yang format)
 */
function NextServiceSeqByPrefix($koneksi, $seqKey, $prefix)
{
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS tbl_service_seq_counter (
        seq_key VARCHAR(64) NOT NULL PRIMARY KEY,
        seq INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");

    $seqKeyEsc = mysqli_real_escape_string($koneksi, $seqKey);

    $qCheck = mysqli_query($koneksi, "SELECT 1 FROM tbl_service_seq_counter WHERE seq_key = '$seqKeyEsc'");
    if (!$qCheck || mysqli_num_rows($qCheck) === 0) {
        $prefixEsc = mysqli_real_escape_string($koneksi, $prefix);
        // Seed dari nomor tertinggi yang SUDAH terpakai untuk prefix ini,
        // bukan COUNT — biar gak nabrak no_service existing.
        $qSeed = mysqli_query($koneksi, "SELECT COALESCE(MAX(CAST(SUBSTRING(no_service, " . (strlen($prefix) + 1) . ") AS UNSIGNED)), 0) AS mx
                                          FROM tblservice WHERE no_service LIKE '{$prefixEsc}%'");
        $seed = 0;
        if ($qSeed && ($rowSeed = mysqli_fetch_assoc($qSeed))) {
            $seed = (int) $rowSeed['mx'];
        }
        mysqli_query($koneksi, "INSERT IGNORE INTO tbl_service_seq_counter (seq_key, seq) VALUES ('$seqKeyEsc', $seed)");
    }

    // Atomic increment — aman dipanggil bersamaan dari cabang/staf berbeda.
    mysqli_query($koneksi, "UPDATE tbl_service_seq_counter SET seq = LAST_INSERT_ID(seq + 1) WHERE seq_key = '$seqKeyEsc'");
    $next = (int) mysqli_insert_id($koneksi);

    if ($next <= 0) {
        // Fallback (harusnya tidak pernah kejadian): baca langsung.
        $qFallback = mysqli_query($koneksi, "SELECT seq FROM tbl_service_seq_counter WHERE seq_key = '$seqKeyEsc'");
        $rowFallback = mysqli_fetch_assoc($qFallback);
        $next = (int) ($rowFallback['seq'] ?? 0);
    }

    return $next;
}
?>
