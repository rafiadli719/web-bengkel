<?php
// Satu ruang nomor kode_karyawan buat 2 tabel (tbuser_karyawan + tbuser)
// - sebelum ini masing-masing generate sendiri-sendiri, discope cuma ke
// tabelnya sendiri, jadi 2 orang beda tabel bisa kebagian kode identik.
// Lihat docs/superpowers/plans/2026-09-14-rapikan-master-karyawan-posisi-user-rbac.md
// Task 2.

if (!function_exists('generateKodeKaryawan')) {
    // $referenceTimestamp: dasar bulan buat prefix kode — default sekarang
    // (dipakai user_management.php), tapi master_karyawan_save.php pakai
    // tanggal_masuk karyawan (kode nyerminin bulan mulai kerja, bukan
    // bulan akun dibuat).
    function generateKodeKaryawan(mysqli $koneksi, ?int $referenceTimestamp = null): ?string {
        $prefix = date('Ym', $referenceTimestamp ?? time());
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $seqKaryawan = 0;
            $r1 = mysqli_query($koneksi,
                "SELECT MAX(CAST(RIGHT(kode_karyawan, 4) AS UNSIGNED)) AS seq
                 FROM tbuser_karyawan WHERE kode_karyawan LIKE '{$prefix}%'");
            if ($r1 && ($row = mysqli_fetch_assoc($r1)) && !empty($row['seq'])) {
                $seqKaryawan = (int) $row['seq'];
            }

            $seqUser = 0;
            $r2 = mysqli_query($koneksi,
                "SELECT MAX(CAST(RIGHT(kode_karyawan, 4) AS UNSIGNED)) AS seq
                 FROM tbuser WHERE kode_karyawan LIKE '{$prefix}%'");
            if ($r2 && ($row = mysqli_fetch_assoc($r2)) && !empty($row['seq'])) {
                $seqUser = (int) $row['seq'];
            }

            $seq = max($seqKaryawan, $seqUser) + 1;
            $candidate = sprintf('%s%04d', $prefix, $seq);

            $c1 = mysqli_query($koneksi, "SELECT id FROM tbuser_karyawan WHERE kode_karyawan = '$candidate'");
            $c2 = mysqli_query($koneksi, "SELECT id FROM tbuser WHERE kode_karyawan = '$candidate'");
            $taken = ($c1 && mysqli_num_rows($c1) > 0) || ($c2 && mysqli_num_rows($c2) > 0);
            if (!$taken) {
                return $candidate;
            }
        }
        return null;
    }
}
