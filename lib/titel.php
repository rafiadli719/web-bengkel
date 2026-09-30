<?php
// Judul tab browser. Dulu selalu "FIT MOTOR" (110 dari 152 halaman berjudul sama,
// tab tidak bisa dibedakan). Sekarang diturunkan dari app/menu_config.php
// berdasarkan URL halaman: "Judul Menu - Grup Menu - FIT MOTOR". Halaman yang
// tidak ada di menu (mis. halaman edit) tetap "FIT MOTOR".
// Dipakai HANYA di dalam <title> (410 halaman), jadi cukup mencetak teks.
if (!function_exists('fitmotorPageTitle')) {
    function fitmotorPageTitle()
    {
        static $peta = null;
        $merek = 'FIT MOTOR';
        if ($peta === null) {
            $peta = array();
            $cfg = @include __DIR__ . '/../app/menu_config.php';
            if (is_array($cfg)) {
                $telusur = function ($daftar, $grup) use (&$telusur, &$peta) {
                    foreach ($daftar as $item) {
                        if (!is_array($item)) continue;
                        if (!empty($item['url']) && !isset($peta[$item['url']])) {
                            $peta[$item['url']] = array($item['title'] ?? '', $grup);
                        }
                        if (!empty($item['submenu']) && is_array($item['submenu'])) {
                            // grup = induk terdekat (mis. "Pesanan Pembelian", "Pembelian"),
                            // supaya "Input Manual" di dua induk tidak berjudul kembar
                            $telusur($item['submenu'], $item['title'] ?? $grup);
                        }
                    }
                };
                $telusur($cfg, '');
            }
        }
        $skrip = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $pos = strpos($skrip, '/app/');
        if ($pos === false) return $merek;
        $rel = rawurldecode(substr($skrip, $pos + 5));
        if (!isset($peta[$rel])) return $merek;
        list($judul, $grup) = $peta[$rel];
        if ($judul === '') return $merek;
        $bagian = array($judul);
        if ($grup !== '' && $grup !== $judul) $bagian[] = $grup;
        $bagian[] = $merek;
        return implode(' - ', $bagian);
    }
}
echo htmlspecialchars(fitmotorPageTitle(), ENT_QUOTES, 'UTF-8');
