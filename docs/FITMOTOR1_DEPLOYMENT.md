# Cloning & Deployment ke FITMOTOR1

Status: operasional via Tailscale
Repo: web-bengkel/aplikasi/aplikasi
Diselesaikan: 12 Sep 2026 malam (path URL dirapikan 13 Sep 2026)

## Ringkasan

Aplikasi web-bengkel (folder ini) berhasil di-clone dan dijalankan penuh di
komputer kedua, **FITMOTOR1** (Windows + Laragon + WSL2). Database dimigrasi
dari dump SQL, beberapa masalah infrastruktur Windows/WSL diselesaikan di
tengah jalan, dan aplikasi sekarang bisa diakses dari luar lewat Tailscale.

| Hal | Nilai |
|---|---|
| Database | `fitmotor_dbbengkel` |
| Port publik | 8080 |
| Nginx aktif | 1.27.3 |
| Objek DB dipulihkan | 4 trigger + 2 function |

## Arsitektur akses

Lalu lintas dari internet lewat Tailscale masuk ke WSL, direlay ke Nginx yang
jalan di sisi Windows:

```
Tailscale (100.86.177.112:8080)
    -> socat @ WSL, listen 0.0.0.0:8080, forward ke 192.168.48.1:8080
        -> Nginx Windows 1.27.3 :8080, root C:/laragon/www
            -> PHP-FPM (php_upstream)
                -> MySQL (fitmotor_dbbengkel)
```

## Tahapan migrasi

1. **Salin dump database ke Windows** — dump `fitmotor_dbbengkel` disalin ke
   drive C: supaya bisa diimpor langsung lewat MySQL client native Windows
   (bukan lewat WSL, yang gagal akses path-nya).

2. **Perbaiki MySQL 8.4.3 yang crash saat start** — sempat crash berulang
   (stack trace dekompresi ZSTD, lalu error konfigurasi networking Windows).
   Diselesaikan dengan start ulang pakai `--skip-grant-tables` dulu untuk
   diagnosis, lalu start normal setelah konfigurasi dibetulkan.

3. **Buat ulang user & database** — user `fitmotor_LOGIN` dan database tidak
   ada di instance baru, dibuat ulang beserta hak aksesnya (host restriction
   dilebarkan supaya bisa dipakai dari WSL). *Kredensial disimpan terpisah,
   tidak dicantumkan di sini.*

4. **Bersihkan & jalankan dump final** — dump asli menabrak definisi VIEW
   yang mengacu ke fungsi yang belum ada saat impor awal. VIEW bermasalah
   dilepas dulu dari dump, database di-drop & dibuat ulang bersih, baru
   diimpor dengan flag toleran-error.

5. **Pulihkan trigger, function, dan VIEW yang tertinggal** — 4 trigger + 2
   function diekstrak jadi file SQL terpisah (klausa `DEFINER` dibuang
   karena user definer beda di instance baru), lalu dieksekusi manual
   berikut 1 VIEW yang sebelumnya dilepas.

6. **Konfigurasi Nginx untuk trafik Tailscale** — vhost default Laragon di
   port 8080 awalnya cuma izinkan `127.0.0.1`. Ditambah allow-list untuk
   rentang CGNAT Tailscale (`100.64.0.0/10`) dan subnet WSL
   (`192.168.0.0/16`), lalu di-reload.

7. **Pasang relay socat + firewall** — Nginx Windows tidak langsung
   terjangkau dari jaringan Tailscale, jadi dipasang relay TCP di WSL
   (socat) yang meneruskan ke gateway Windows, ditambah rule Windows
   Firewall untuk port 8080.

   ```
   socat TCP-LISTEN:8080,fork,reuseaddr TCP:192.168.48.1:8080
   ```

8. **Verifikasi end-to-end** — `login.php` merespons HTTP 200 dan
   `index.php` redirect 302 normal ke halaman login — stack dinyatakan
   hidup penuh dari Tailscale sampai MySQL.

9. **Rapikan path URL (13 Sep 2026)** — folder fisik ada dua level
   (`aplikasi/aplikasi`). Ditambah 1 file alias Nginx
   (`C:/laragon/etc/nginx/alias/web-bengkel.conf`) supaya bisa diakses
   lewat path pendek tanpa memindah folder apa pun.

## Titik akses

| URL | Keterangan |
|---|---|
| `/web-bengkel/aplikasi/login.php` | Path pendek (baru) — alias Nginx ke folder fisik ganda di bawah |
| `/web-bengkel/aplikasi/aplikasi/login.php` | Path asli sesuai struktur folder disk, tetap aktif |

Link: `http://100.86.177.112:8080/web-bengkel/aplikasi/login.php`

## Gotcha OPcache setelah git pull (13 Sep 2026 malam)

Verifikasi menu Keuangan Kasir + Penanganan Komplain sempat gagal total di
sidebar (semua role) walau `git log -1` sudah nunjuk commit terbaru
(`22ca462`) dan `grep` konfirmasi `app/menu_config.php` fisik sudah berisi
kode menu itu. Root cause: **respons HTTP nyatanya dilayani `php8.3-fpm` +
Nginx lokal WSL** (bukan Laragon Windows seperti dugaan awal), dan
prosesnya masih nahan OPcache versi lama — `git pull` gak otomatis
invalidate opcode cache proses PHP-FPM yang udah jalan lama.

Fix: restart PAKSA (`stop` lalu `start`, bukan `reload`) `php8.3-fpm` DAN
`nginx` di WSL. Setelah itu, checklist "Akses Sidebar Menu" di
`master-posisi.php` langsung nampilin grup baru, dan RBAC tervalidasi
lewat login 3 role (ADM admin, KSR dev_test_kasir, KEU keu01) — sidebar
masing-masing sesuai scope permission-nya.

**Pelajaran**: `git log` + `grep` di file fisik TIDAK CUKUP buat
verifikasi deploy — cek juga proses PHP yang benar-benar melayani
request (WSL vs Windows/Laragon bisa beda), dan restart paksa service-nya
setelah pull kalau ada perubahan kode yang gak nongol di browser.

## Yang masih perlu diperhatikan

- **Allow-list Tailscale di Nginx sempat kembali ke default** — `00-default.conf`
  saat ini cuma berisi `allow 127.0.0.1; deny all;`. Rule CGNAT/WSL dari sesi
  migrasi awal tampak hilang. Kalau akses dari Tailscale balik 403, ini
  penyebabnya.
- **Relay socat tidak persisten** — dijalankan langsung di terminal WSL,
  bukan lewat service/systemd. Kalau WSL atau Windows restart, relay perlu
  dinyalakan ulang manual.
- **Belum ada HTTPS** — akses masih HTTP polos lewat Tailscale (jaringan
  privat), belum ada sertifikat TLS. Cukup aman selama tetap di dalam
  Tailnet.
