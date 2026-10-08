# Planning Implementasi: Checklist Pengecekan Mekanik di FIT MOTOR Web Base

Tanggal: 2026-10-01
Status: DRAFT, menunggu keputusan Rafi (bagian 3)
Sumber analisa:
- Proyek `C:\laragon\www\pengecekan_mekanik` (selanjutnya: **proyek Cek Mekanik**), dibaca: CLAUDE.md, PRD/FSD/DESIGN/PLANNING, `.ai/decisions` + `.ai/changelog`, skema migration, `CekMekanikResolver`, `CekMekanikController`, route API, master JSON.
- Spec desain web base `docs/superpowers/specs/2026-09-19-checklist-pengecekan-mekanik-design.md`.
- Skema live `fitmotor_dbbengkel` (dibaca 2026-10-01, read-only).

Aturan kerja: planning dulu, belum ada kode yang diubah.

---

## 1. Ringkasan temuan

### 1.1 Proyek Cek Mekanik sudah jauh lebih matang daripada spec web base

Dua dokumen merancang fitur yang sama secara terpisah. Proyek Cek Mekanik (dimulai 2026-09-07) sudah punya:

| Area | Isi |
|---|---|
| Master data | 8 kategori motor, 6 jenis servis, 17 area, 80 komponen, 494 kombinasi komponen x kategori, 1.905 rule. Sumber: xlsx resmi, dikonversi ke `cek_mekanik_master.json` (357 KB). |
| Logika | `CekMekanikResolver`: rule per kategori x jenis servis, default kosong = `tidak_termasuk`, status jawaban dibatasi opsi komponen, snapshot label dibuat server. |
| API | Header (CS bisa membuat dulu, mekanik mengklaim), detail upsert, temuan lain, upload foto bukti, finish dengan cek komponen wajib, riwayat + hasil. |
| PWA | Mekanik (1.039 baris TSX) dan CS (493 baris), offline queue, service worker, installable. |
| Admin | 12 halaman Blade: kategori, area, komponen, opsi, jenis servis, rule matrix, foto, foto massal, dashboard, karyawan, hak akses. |
| Kualitas | Test backend (68+ tes) dan 6 skenario Playwright. Laporan progres 2026-09-19 menyebut 90%, pilot belum jalan. |

Spec web base (2026-09-19) memakai nama tabel `pengecekan_*` dan istilah "grup motor x kategori garapan x template". Itu padanan dari `kategori motor x jenis servis x rule` di proyek Cek Mekanik. **Satu model data cukup, jangan dibangun dua kali.**

### 1.2 Kendala utama: stack tidak cocok dengan produksi web base

- Web base produksi = PHP prosedural di **shared hosting tanpa Docker** (PRD_TEKNIS §1).
- Proyek Cek Mekanik = Laravel + Next.js + Docker, jalan di VPS.
- Akibatnya proyek Cek Mekanik tidak bisa mengakses `tblservice` produksi (CLAUDE.md-nya: "Akses ke DB sistem utama dari Docker belum tersedia"). Untuk menyiasatinya mereka membangun **sync Access -> MySQL** (skrip di PC cabang, belum terpasang di cabang mana pun), impor karyawan dari absensi, login CS/mekanik sendiri, dan heuristik kategori dari data Access.
- Semua infrastruktur sempalan itu sudah ada native di web base: `tblservice` live, `tbuser_karyawan`, `tbcabang`, RBAC `tb_master_posisi`, `tblkendaraan`.

### 1.3 Fakta skema live web base yang memengaruhi desain

| Fakta | Dampak |
|---|---|
| `tblservice` tanpa kolom id. Pasangan (`kd_cabang`, `no_service`) **unik**: 0 pasangan dobel dari 103.551 baris. Tetapi `no_service` sendirian dobel di **30.889** nomor lintas cabang. | Semua tabel baru dan semua JOIN wajib memuat kedua kolom. Selaras dengan R-1 proyek Cek Mekanik. |
| `tblservice` charset **latin1**; `tbservis_temuan` dan tabel baru `utf8mb4_unicode_ci`; `tb_antrian_servis`/`tb_progress_mekanik` `utf8mb4_general_ci`. | JOIN lintas tabel wajib `COLLATE` eksplisit (pernah silent-fail). |
| `tbservis_temuan` **tidak punya `kd_cabang`**; `no_service` varchar(50); `mekanik_id` varchar(10). | Temuan otomatis dari checklist bisa menempel ke servis cabang lain bila `no_service` kebetulan sama. Perlu kolom `kd_cabang` (lihat 4.4). Cek panjang `kode_karyawan` (varchar(20)) vs `mekanik_id` varchar(10). |
| `tb_progress_mekanik` berisi **0 baris**; `ajax-save-service.php` (yang menurut spec menulis ke tabel itu) kini kode mati. Penunjukan mekanik nyata ada di `tblservice.mekanik1..4`, `kepala_mekanik1/2` + persen (dipakai komisi, wired 2026-07-21). | Asumsi Fase C spec (layar kamek menulis `tb_progress_mekanik`) sudah basi. Fase C harus diselidiki ulang dan **dikeluarkan dari MVP**. |
| `tbuser` per posisi: MK **3**, KM 6, CS 4, KACAB 4, ADM 9. Tetapi `tbuser_karyawan` punya **84 MK**. Izin posisi MK hanya 5 (`servis_menu_read`, dst.), tanpa izin temuan/pengecekan. | Mayoritas mekanik belum punya akun login web base. Ini keputusan D3. |
| `tblkendaraan.kode_tipe` terisi hanya **8 dari 37.355**. Kolom `tipe` (teks) dan `jenis` ada. `tbtipe_motor` 104 tipe, `tbkategori_motor` 10 baris dengan duplikat (MATIC id 2 dan 6, SUPERMATIC 7 dan 8, SUPERSPORT 5 dan 10, DMY). | Saran kategori otomatis tidak bisa lewat `kode_tipe`. Jangan membuat FK ke `tbkategori_motor` yang kotor. |
| Servis aktif (`datang`/`diproses`) saat ini: PCL 9, PESALAKAN 8, CDT 6, TRM 3, PST 2, TRAYEMAN 2. | Ukuran beban pilot kecil dan realistis. |
| `tbservis_temuan` 15 baris (14 `ditemukan`). Handler pembuat temuan: `_handler_temuan_penawaran.php:884`. Tabel log: `tbservis_temuan_activity_log`. | Titik integrasi sudah jelas, pipeline penawaran part tidak perlu diubah. |
| File xlsx di web base (`75f9c3cc...`) **berbeda hash** dari file di proyek Cek Mekanik (`81ef3a92...`). Proyek Cek Mekanik memutakhirkan master 2026-10-01 (Scoopy ESP) lewat chat dan JSON-nya sudah dibangun ulang. | Sebelum impor, tetapkan satu sumber kebenaran (Fase 0). |

### 1.4 Peringatan keamanan dari proyek Cek Mekanik

- Folder `app/` (admin PHP lama) memuat fallback kredensial DB hardcode; ada insiden kebocoran PAT GitHub + password MySQL (`.ai/incidents/2026-09-19-kebocoran-kredensial-repo.md`).
- `backend/storage/app/impor/*.csv` berisi data pribadi karyawan; `sync_settings*.env` dan `.env` berisi token.
- **Aturan porting: hanya salin master JSON, logika, foto, dan tes. Jangan menyalin `.env`, `app/config/koneksi.php`, folder `impor/`, `sync/paket/`, `.venv`, `.claude*`.**

---

## 2. Yang dipakai ulang dan yang dibuang

| Dipakai ulang (aset) | Dibuang (diganti yang sudah native di web base) |
|---|---|
| `cek_mekanik_master.json` (sumber seed) | Laravel, Next.js, Docker, ngrok |
| Aturan resolver: rule per kategori x servis, default `tidak_termasuk`, opsi 2 atau 3 pilihan | Tabel token `mekanik_tokens`/`cs_tokens`, login tunggal |
| Validasi server: status harus termasuk opsi komponen, komponen harus ada di checklist, snapshot label dari server | Impor absensi (`masterkeys`, `users`, `cabang`), `master_karyawan`, `karyawan:import` |
| Alur CS membuat header lalu mekanik mengklaim (dual-mode permanen, keputusan Rafi 2026-09-22) | Sync Access (`sync_cabang`, `sync_servis`, `SyncController`, `pasang_cek_mekanik.bat`) |
| Daftar tes (48 kombinasi kategori x servis, API test) sebagai spesifikasi perilaku | Admin PHP lama di `app/` dan panel hak akses sendiri (diganti RBAC web base) |
| 59 foto ilustrasi + `kredit-foto.md` | |
| Logika `TipeMotor::kategoriOtomatis` (97,9% dari 2.925 servis di data Access) | |
| Layar Riwayat, Hasil, "Salin ringkasan" untuk WA | |

---

## 3. Keputusan yang dibutuhkan dari Rafi

Rekomendasi saya ditandai (R).

**D1. Arsitektur integrasi**

| Opsi | Isi | Catatan |
|---|---|---|
| **A (R). Port ke PHP native di web base** (`app/_cekmekanik/`) | Master + transaksi di `fitmotor_dbbengkel`, auth/RBAC/cabang/servis/karyawan native. | Satu sistem, tanpa sync, cocok shared hosting. Biaya: tulis ulang PWA dan admin (sekitar 1.500 baris TSX + 12 view). |
| B. Bridge | Proyek Cek Mekanik tetap di VPS, web base memanggil API-nya, temuan didorong ke `tbservis_temuan`. | Perlu akses DB lintas jaringan atau sync Access (belum terpasang), token lintas sistem, dua deploy. Blocker lama belum selesai. |
| C. Terpisah | Tetap standalone, hanya tautan. | Tidak menghasilkan temuan/penawaran otomatis, inti nilai bisnis hilang. |

Selama porting berjalan, proyek Cek Mekanik di VPS boleh tetap dipakai sebagai demo. Tidak ada data nyata yang perlu dimigrasi (pilot belum jalan; konfirmasi di Fase 0).

**D2. Nama tabel.** (R) Skema proyek Cek Mekanik dengan prefix `cek_` (hindari tabrakan dengan `master_*` web base dan `tbkategori_motor`): `cek_kategori_motor`, `cek_area`, `cek_komponen`, `cek_komponen_kategori`, `cek_opsi_jawaban`, `cek_jenis_servis`, `cek_rule`, `cek_status`, `cek_header`, `cek_detail`, `cek_temuan_lain`, `cek_memori_kendaraan`, `cek_mapping_tipe`. Menggantikan nama `pengecekan_*` di spec 19 Sep.

**D3. Identitas mekanik.** Hanya 3 dari 84 mekanik punya akun `tbuser`.
- (R) Fase awal: pemeriksaan diisi lewat sesi login yang ada (MK/KM/CS), dengan dropdown **"Mekanik penanggung jawab"** (diambil dari `tbuser_karyawan` posisi MK/KM, cabang sesi, aktif). Tanpa membuat akun massal; `mekanik_id` = `kode_karyawan` yang dipilih. Akuntabilitas lebih lemah (siapa saja bisa memilih nama).
- Alternatif: buat akun `tbuser` per mekanik. Perlu kebijakan Rafi soal nama dan password nyata; saya tidak akan membuat akun otomatis.

**D4. Satu atau beberapa jenis servis per pemeriksaan.**
- Spec 19 Sep (hasil chat Pak Novian): boleh beberapa, hasil digabung dan item kembar dibuang. Proyek Cek Mekanik: satu jenis per pemeriksaan.
- (R) Dukung beberapa sejak awal lewat tabel anak `cek_header_jenis` dan resolver gabungan (union per komponen; `wajib` menang atas `opsional`). Perubahan murni di resolver; kasus satu jenis tetap identik dengan perilaku proyek Cek Mekanik.

**D5. Kolom `kd_cabang` di `tbservis_temuan`.** (R) Tambah kolom nullable lewat migration reversible; hanya baris baru dari modul ini yang mengisinya, tanpa backfill (30.889 `no_service` ambigu tidak bisa ditebak). Alternatif: tidak menyentuh tabel inti dan memakai penelusuran `cek_detail.temuan_id` saja (kurang aman untuk laporan temuan).

**D6. Offline.** (R) Dua tahap: MVP online dengan simpan otomatis + indikator gagal + ulang; tahap berikutnya antrean offline dan installable. Spec 19 Sep mengeluarkan offline dari lingkup awal, PRD Cek Mekanik mewajibkan PWA.

**D7. Kategori otomatis.** (R) Porting heuristik `kategoriOtomatis` ke `tblkendaraan.tipe` + `jenis`, setelah Fase 0 membuktikan cakupannya. Mapping admin dan memori nopol tetap menang. Tidak ada FK ke `tbkategori_motor`; penggabungan duplikat kategori tetap keputusan terpisah (backlog Rafi).

**D8. Cakupan Fase C (layar kamek).** (R) Keluar dari MVP sampai penunjukan mekanik diselidiki ulang (lihat 1.3). Menulis ke `tblservice.mekanik1..4` menyentuh komisi.

**D9. Pilot.** Cabang pilot dan pemilik fotografi (open item #4 FSD) belum ditentukan.

---

## 4. Rancangan teknis

### 4.1 Struktur kode

```
app/_cekmekanik/
  koneksi_cek.php        # guard sesi ($_SESSION['_iduser']), kd_cabang dari sesi, helper
  lib_resolver.php       # port CekMekanikResolver (prepared statement mysqli)
  lib_temuan.php         # buat/batal temuan di tbservis_temuan + activity log
  lib_kategori_auto.php  # port TipeMotor::kategoriOtomatis
  index.php              # layar mekanik (mobile-first)
  cs.php                 # CS: tentukan kategori + jenis servis
  riwayat.php, hasil.php
  ajax_*.php             # kategori, servis, checklist, header, detail, temuan, upload, finish, riwayat
  master_*.php           # admin ACE (kategori, area/komponen, opsi, jenis servis, rule matrix, foto)
  manifest.json, sw.js   # tahap PWA
db/migrations/2026-10-xx_cek_mekanik.sql + _run_cek_mekanik.php (+ rollback)
db/seed/cek_mekanik_master.json + seeder idempotent
```

Mengikuti pola `_komplain/` (partial `_ace_header.php`/`_ace_footer.php` untuk halaman admin; halaman mekanik mobile boleh memakai layout ringan sendiri).

### 4.2 Data

- 13 tabel `cek_*` (D2), collation `utf8mb4_unicode_ci` eksplisit, semua transaksi membawa `kd_cabang` (varchar(10)).
- `cek_header`: unik (`kd_cabang`, `no_service`) untuk status `draft`; `no_mekanik` nullable (header dari CS). Race draft ganda (utang teknis proyek Cek Mekanik) diselesaikan dengan transaksi + `FOR UPDATE` atau unique parsial.
- `cek_detail`: unik (`id_header`, `id_komponen`), snapshot nama komponen + area + label ikut disalin supaya riwayat tidak berubah saat master diedit. `temuan_id` nullable.
- Seeder: **insert-if-absent per kode, tidak menimpa suntingan admin**. (Seeder proyek Cek Mekanik mengisi ulang `master_opsi_jawaban`; jangan ditiru di produksi.)
- Migration lewat runner PHP browser `db/migrations/*_run_*.php`, reversible, bukan `mysql` CLI.

### 4.3 Alur

1. CS (atau mekanik) memilih servis aktif cabang dari `tblservice` (`status_servis` datang/diproses, `kd_cabang` sesi).
2. Kategori motor: saran otomatis (memori nopol -> mapping admin -> heuristik -> manual). Jenis servis: daftar dari `servicesFor(kategori)` + jumlah item.
3. Header dibuat (CS) atau diklaim (mekanik). Checklist di-resolve dari master, bukan hardcode.
4. Tiap ketukan = upsert `cek_detail` (idempoten, aman untuk retry/offline).
5. Selesai hanya bila semua komponen `wajib` terjawab. Hasil: ringkasan Baik/Perhatian/Bermasalah, temuan, foto bukti, "Salin ringkasan".

### 4.4 Integrasi temuan dan penawaran

- `bad` -> satu baris `tbservis_temuan`: `temuan_custom` = "<komponen>: <label>", `deskripsi_temuan` = catatan, `tingkat_urgensi` = `tinggi`, `jenis_perbaikan` = `setting`, `status_temuan` = `ditemukan`, `mekanik_id/name`, `kd_cabang` (D5), `created_by`. Tulis `tbservis_temuan_activity_log` (`temuan_created`). Simpan `cek_detail.temuan_id`.
- `warn` -> saran + tombol "Jadikan temuan" (`sedang`).
- Ubah `bad` -> `ok`: temuan dibatalkan hanya bila masih `ditemukan`; bila sudah `ditawarkan` atau lebih, tolak dengan pesan jelas.
- Pipeline penawaran part dan approval tidak diubah.
- Opsional (Fase 4b): `cek_komponen.kode_temuan` -> `tbmaster_temuan` agar saran part dari mapping temuan ikut muncul. `tbmaster_temuan` baru 10 baris, perlu keputusan konten.

### 4.5 Keamanan dan RBAC

- Semua handler: guard sesi seperti sibling, prepared statement, `kd_cabang` dari sesi (bukan dari klien; beda dengan klien -> 403).
- Izin baru di `tb_master_posisi.permissions` lewat migration: `cek_isi` (MK, KM), `cek_lihat` (CS, KM, KACAB, ADM, MNG), `cek_buat_header` (CS, KM), `cek_master` (ADM; KM opsional), `cek_laporan` (KM, KACAB, ADM, MNG). Menu grup baru di `menu_config.php`. Guard memakai `requirePermission()` yang sudah diperbaiki (66fcc3d).
- Foto bukti: **jangan** dilayani publik tanpa auth (utang proyek Cek Mekanik). Simpan di luar webroot atau sajikan lewat PHP berguard + validasi mime/ukuran, nama acak.
- Jangan porting bug lama: antrean offline harus dibersihkan saat logout dan 4xx tidak boleh dibuang diam-diam; `submitDetail` tidak menimpa `created_at`.

---

## 5. Fase

| Fase | Isi | Selesai bila | Ukuran |
|---|---|---|---|
| 0 | Keputusan D1-D9. Investigasi: (a) satu sumber xlsx/JSON (bandingkan isi kedua xlsx, pakai JSON 2026-10-01); (b) cakupan `tblkendaraan.tipe/jenis` terhadap heuristik kategori; (c) panjang `kode_karyawan` vs `mekanik_id` varchar(10); (d) konfirmasi tidak ada data nyata di VPS Cek Mekanik; (e) konfirmasi 2 anomali xlsx ke Pak Novian (TPS karbu di Servis Rutin; Handel Rem dobel). | Laporan temuan + keputusan tercatat di `.ai/decisions/`. | S |
| 1 | Migration 13 tabel (reversible) + seeder idempoten + halaman master read-only. | Jumlah baris DB = JSON: 8/6/17/80/494/1.905, diverifikasi query. Tes 48 kombinasi kategori x servis (port dari `CekMekanikResolverTest`) lulus. | M |
| 2 | Resolver + endpoint AJAX (header, claim, detail, temuan lain, upload, finish, riwayat). | Skrip tes PHP CLI: validasi opsi, komponen di luar checklist ditolak, `kd_cabang` salah -> 403, finish menolak bila wajib kosong. | M |
| 3 | Layar mekanik mobile + layar CS + tombol/badge "Cek" di `servis-reguler.php`. | E2E browser viewport HP: pilih servis -> kategori -> jenis -> isi -> selesai -> hasil. | L |
| 4 | Integrasi temuan (4.4) + kolom `kd_cabang` di `tbservis_temuan`. | E2E: `bad` menghasilkan baris `tbservis_temuan` dan log (verifikasi query DB), `warn` -> jadikan temuan, revert ditolak setelah `ditawarkan`. | M |
| 5 | Admin master ACE: kategori, area/komponen, opsi, jenis servis, rule matrix, foto + RBAC + menu + kredit foto di UI. | Admin mengubah rule, checklist berubah tanpa deploy. Permission diuji per posisi. | L |
| 6 | PWA: manifest, service worker (HTTPS), antrean offline idempoten. | Putus jaringan saat mengisi, data terkirim setelah online, tidak ada kehilangan data. | M |
| 7 | Laporan: temuan per mekanik, komponen paling sering `bad`, konversi bad -> temuan -> penawaran -> disetujui. Dashboard KM. | Angka cocok dengan query langsung. | M |
| 8 | UAT pilot 1 cabang, Playwright E2E, review Sonnet lalu Opus judge, migration produksi, update CLAUDE.md + `.ai/changelog` + checklist-projek. | DoD global §11 terpenuhi. | M |
| 9 (ditunda) | Layar kamek / penunjukan mekanik (setelah investigasi ulang), multi-garapan di form CS, cetak/WA ke pelanggan. | Keputusan terpisah. | - |

Urutan kerja tiap fase: kerjakan -> test E2E browser -> commit -> update `.ai/changelog` -> update checklist-projek (workflow 5 langkah).

## 6. Risiko

1. **`no_service` dobel lintas cabang** (30.889). Mitigasi: pasangan (`kd_cabang`, `no_service`) di semua tabel, query, dan JOIN; audit grep sebelum merge.
2. **Collation campur** (latin1 / unicode_ci / general_ci). Mitigasi: `COLLATE` eksplisit; tes JOIN pada data nyata.
3. **Temuan menempel ke cabang salah** (D5).
4. **Akun mekanik** (3 dari 84). Mitigasi: D3.
5. **Cakupan kategori otomatis tidak terbukti** (97,9% diukur di data Access, bukan di `tblkendaraan`). Mitigasi: Fase 0(b); fallback manual selalu ada.
6. **Foto referensi**: 494 kombinasi, baru 59 komponen dengan ilustrasi (lisensi CC BY/BY-SA butuh atribusi di UI), 21 komponen masih placeholder. Fallback placeholder tidak boleh memblokir alur.
7. **Kualitas xlsx**: dua anomali belum dikonfirmasi bisnis.
8. **Beban kerja porting**: tulis ulang UI. Mitigasi: fase kecil, tes proyek lama sebagai spesifikasi.
9. **Lingkungan tes**: WSL lambat di `/mnt/c`, tab Chrome sering `hidden`; ikuti catatan gotcha di memory.
10. **Kebocoran rahasia saat menyalin**: ikuti aturan 1.4.

## 7. Di luar lingkup

Cetak/WA hasil ke pelanggan (kecuali "Salin ringkasan"), approval berjenjang kepala mekanik, perubahan pipeline penawaran part, modul Komplain/Garansi/Kasir, penggabungan `tbkategori_motor`.
