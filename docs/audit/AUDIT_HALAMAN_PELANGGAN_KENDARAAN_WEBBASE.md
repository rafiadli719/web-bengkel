# Audit Halaman Web Base — Pelanggan, Kendaraan, Statistik

**Tanggal:** 2026-07-03
**Tujuan:** Temukan perbaikan yang bisa dikerjakan SEKARANG (tanpa nunggu FSD/migrasi tabel baru disetujui), dipisah dari yang WAJIB nunggu FSD.
**Sumber:** Penjelajahan langsung kode `app/*.php` — bukan Access, ini kode PHP yang sedang jalan produksi.

---

## Temuan Paling Berbahaya — Ditemukan Hari Ini

### T1. `nopelanggan` DIISI DARI NOMOR POLISI KENDARAAN (bukan kode independen)

**Lokasi:** `app/save_pelanggan_only.php:71-172`, dikonfirmasi ulang di `app/check_phone.php:56-60`.

Alur "tambah pelanggan enhanced" (form gabungan pelanggan+kendaraan) melakukan:
```
cek existing WHERE telephone=? OR nopelanggan=$nopol
kalau tidak ada -> INSERT tblpelanggan (nopelanggan = $nopol)   <-- nomor polisi jadi kode pelanggan!
lalu INSERT/UPDATE tblkendaraan (nopolisi = $nopol)
```

**Akibat konkret, terjadi tiap hari:** kalau 1 orang punya 2 motor dan didaftarkan lewat form ini, sistem bikin **2 baris `tblpelanggan` berbeda** — satu per motor — karena kodenya diambil dari plat, bukan dari identitas orangnya. Ini kemungkinan besar **akar penyebab langsung** duplikat "SUGENG, BPK" x43, "YUSUF, BPK" x35 yang ditemukan di data produksi.

**Ini bukan bug lama yang cuma warisan Access** — ini kode PHP aktif yang **terus menambah duplikat baru setiap hari**, terlepas dari FSD disetujui kapanpun.

### T2. Pencarian Pelanggan AJAX Cuma Ambil 1 Hasil Teratas (`LIMIT 1`)

**Lokasi:** `app/_ajax/ajax-cari-pelanggan.php:22-33`

Query cari pelanggan (dipakai form servis/kasir) pakai `LIKE` di 3 kolom sekaligus (`nopelanggan`, `namapelanggan`, `telephone`) tapi **`LIMIT 1`** — kalau ada beberapa "SUGENG, BPK" yang match, staf cuma dikasih 1 hasil (yang mana? tergantung urutan database, bukan yang paling tepat) tanpa tahu ada 42 baris lain yang mirip. Staf bisa transaksi ke pelanggan yang salah tanpa sadar.

### T3. SQL Injection — String Interpolation Langsung

**Lokasi:** `app/save_pelanggan.php:35-53`, `app/kendaraan_edit_proses.php:48-55`

Kedua file ini menyusun query pakai variabel input form langsung disambung ke string SQL (bukan prepared statement/parameter binding). Ini celah keamanan nyata — kalau ada input jahat lewat form ini, bisa mengeksekusi perintah SQL sembarangan.

### T4. Field `pemilik` di Kendaraan Murni Teks Bebas

**Lokasi:** `app/kendaraan_edit_proses.php:48-55`, `app/kendaraan.php:290,319,353`

`tblkendaraan.pemilik` diisi bebas dari form, tidak pernah dicek balik ke `tblpelanggan`. Konsisten dengan temuan arsitektur sebelumnya, tapi ini pembuktian di level form aktif: staf bisa ketik nama apapun di kolom pemilik tanpa validasi.

### T5. Sistem Sudah "Menebak-nebak" Relasi Pelanggan-Kendaraan

**Lokasi:** `app/_include_customer_vehicle_sync.php` (fungsi `fitmotorFindCustomerByVehicleOwner` baris 73-113, `fitmotorGetCustomerVehicleBundle` baris 115-144)

Helper ini punya 4 strategi berlapis buat nebak "kendaraan ini punya siapa": cek kode eksplisit -> cek riwayat servis -> anggap nopol=nopelanggan -> cocokkan nama teks (fuzzy). Ini **bukti langsung** developer sebelumnya sudah sadar masalahnya dan bikin workaround tebak-tebakan — bukan solusi asli, karena memang tidak ada FK asli.

## Temuan Sedang

### T6. Tombol "Tambah Pelanggan" Tidak Wajib Cari Dulu

**Lokasi:** `app/pelanggan.php:260-266`

Tombol langsung ke `pelanggan_add.php` tanpa memaksa staf cari data existing dulu.

### T7. Cek Duplikat Saat Tambah Pelanggan Cuma Berdasarkan Telepon

**Lokasi:** `app/save_pelanggan.php:35-37`

Hanya cek `telephone` yang sama. Tidak cek `namapelanggan` mirip. Kalau nomor telepon kosong (kasus banyak di data lama), pengecekan ini otomatis lolos — cocok persis dengan pola "SUGENG, BPK" tanpa telepon yang lolos berkali-kali.

### T8. Laporan Rekap Kunjungan Tidak Filter Cabang

**Lokasi:** `app/lap_rekap_kunjungan.php:6-7 vs 40-68`

Variabel `$kd_cabang` diambil dari session tapi cuma dipakai buat tampilkan nama cabang di judul — **query utamanya tidak memfilter cabang**, jadi laporan ini sebenarnya nampilin data gabungan semua cabang walau kelihatannya laporan per-cabang. Perlu dikonfirmasi apakah ini disengaja (laporan pusat) atau bug.

## Yang Sudah Cukup Baik (Tidak Perlu Diubah Sekarang)

- `app/statistik_pelanggan_dashboard.php` — sudah benar pakai tabel pre-agregat `statistik_pelanggan`, bukan hitung on-the-fly tiap buka halaman. Update dilakukan via trigger database saat pembayaran, bukan batch manual seperti Access dulu. Ini **sudah sesuai** rekomendasi arsitektur.
- `app/_include_statistik_pelanggan.php` — fungsi `updateStatistikPelangganAfterPayment()` sudah menghitung `total_motor` per pelanggan secara real-time. Fondasinya sudah benar, tinggal nanti diperluas jadi tabel `statistik_kendaraan` per unit (sesuai FSD Kendaraan) kalau FSD disetujui.

---

## Mana yang Bisa Dikerjakan SEKARANG (Tanpa Nunggu FSD)?

| # | Perbaikan | Kenapa Aman Dikerjakan Sekarang | Butuh Ubah Skema Tabel? |
|---|---|---|---|
| 1 | **Stop pola `nopelanggan = nopolisi`** di `save_pelanggan_only.php` (T1) | Ini bug aktif yang terus bikin duplikat baru — makin lama dibiarkan makin banyak sampah data baru. Fix-nya: kalau nomor HP/nama match pelanggan existing, jangan buat `nopelanggan` baru dari nopol, pakai `nopelanggan` yang sudah ada. | Tidak — perbaikan logic di file yang sama |
| 2 | **Hilangkan `LIMIT 1`** di pencarian AJAX (T2), tampilkan semua match biar staf pilih manual | Mencegah salah pilih pelanggan secara diam-diam | Tidak |
| 3 | **Perbaiki SQL Injection** (T3) — ganti ke prepared statement | Ini murni perbaikan keamanan, wajib dikerjakan tanpa nunggu apapun | Tidak |
| 4 | **Tambah cek duplikat nama+HP** (bukan cuma HP) saat tambah pelanggan (T7) | Mengurangi laju duplikat baru mulai sekarang | Tidak |
| 5 | **Wajibkan cari dulu sebelum tombol "Tambah Pelanggan" aktif** (T6) | Selaras `FSD_CUSTOMER.md` FR-01, tapi versi sederhananya bisa jalan duluan tanpa nunggu tabel baru | Tidak |
| 6 | Klarifikasi T8 (laporan lintas cabang) ke owner — disengaja atau bug | Tidak berisiko, tinggal konfirmasi kebijakan | Tidak |

## Mana yang WAJIB Nunggu FSD Disetujui

| # | Kenapa Harus Nunggu |
|---|---|
| Perbaikan T4/T5 secara struktural (field `pemilik` jadi FK asli, hilangkan fungsi "tebak-tebak") | Butuh tabel `kepemilikan_kendaraan` baru dari `FSD_KENDARAAN.md` — kalau dibangun sebelum FSD final, berisiko harus dibongkar ulang kalau ada keputusan owner yang beda |
| Statistik per kendaraan (`statistik_kendaraan`) | Butuh tabel baru dari FSD, dan butuh keputusan soal `id_kendaraan` |
| Proses merge pelanggan resmi | Butuh `customer_merge_log`, `customer_alias`, dan keputusan siapa yang boleh approve (Open Item FSD Customer) |

---

## Rekomendasi Urutan Kerja

**Bisa mulai sekarang, gak nunggu approval FSD:** item 1-6 di atas — ini murni perbaikan bug/keamanan di kode yang sudah ada, tidak mengubah struktur tabel, dan **langsung mengurangi laju pertambahan duplikat baru** mulai hari ini. Item #1 dan #3 paling prioritas (satu bikin duplikat terus-menerus, satu celah keamanan).

**Tunggu FSD:** perbaikan struktural yang butuh tabel baru.
