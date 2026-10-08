# Design: Checklist Pengecekan Mekanik (Modul Servis)

Tanggal: 2026-09-19
Status: DRAFT, menunggu review Rafi
Sumber kebutuhan: chat Pak Novian Ardhiansyah Yusuf (18/9/2026) + `docs/KATEGORI, DAFTAR PENGECEKAN PART, KONDISI.xlsx`

## 1. Latar belakang

Pak Novian menjelaskan alur servis setelah CS input data pelanggan:

1. CS input: (a) data pelanggan, (b) jenis garapan, (c) keluhan.
2. Data masuk ke daftar antrian.
3. Kepala mekanik (kamek) menunjuk mekanik sesuai prioritas antrian.
4. Mekanik melakukan pengecekan.

Langkah 4 belum ada di sistem. Saat ini mekanik hanya mencatat temuan bebas (`tbservis_temuan`). File xlsx mendefinisikan daftar pengecekan baku per grup motor dan per kategori garapan, lengkap dengan pilihan kondisi tiap item.

## 2. Keputusan yang sudah disepakati

| # | Keputusan | Pilihan |
|---|---|---|
| 1 | Arti "jenis garapan" | 6 kategori dari xlsx. Satu servis boleh memilih beberapa kategori (mis. Rutin + Ganti Oli). Item kembar dihapus saat digabung. |
| 2 | Perangkat mekanik | HP/tablet di bengkel. Desain mobile-first. |
| 3 | Kondisi tidak OK | Kondisi **buruk** otomatis menjadi temuan (`tbservis_temuan`). Kondisi **waspada** tampil sebagai saran yang bisa dijadikan temuan dengan satu klik. |
| 4 | Pendekatan data | Tabel khusus checklist (bukan JSON, bukan menumpang di master temuan). |
| 5 | Urutan fase | A (data master) → B (garapan di form CS) → D (layar mekanik) → C (layar kamek). |

## 3. Temuan analisa xlsx

- 8 sheet grup motor: Matic Injeksi Radiator, Matic Injeksi Non Radiator, Matic Karbu Radiator, Matic Karbu Non Radiator, Bebek Injeksi, Bebek Karbu, Sport Injeksi Radiator, Sport Injeksi Non Radiator. Sheet `KATEGORI MOTOR` memetakan tipe motor ke grup.
- 6 kategori garapan: Servis Lengkap, Servis Rutin, Servis CVT (hanya Matic), Ganti Oli, Ganti Ban Depan, Ganti Ban Belakang.
- 1.906 baris item, hanya **80 nama item unik**, 17 area. Ini bisa dinormalisasi menjadi satu master item plus template.
- Kondisi melekat pada item: tidak ada item yang punya dua pola kondisi berbeda. 48 pola unik, 42 berisi 3 pilihan, 6 berisi 2 pilihan. Urutan pilihan selalu dari baik ke buruk.
- Pada sheet Matic Injeksi Radiator, semua kategori lain adalah subset Servis Lengkap dan Servis Rutin adalah subset Servis Lengkap. Ini tidak berlaku mutlak di semua sheet (lihat anomali).
- Selisih antar grup kecil. Matic karbu tidak punya injektor/ISC/TPS tetapi punya pilotjet, selang bensin, karet vakum. Bebek dan Sport punya `CEK RANTAI`. Non-radiator tidak punya `CEK AIR RADIATOR`.

### Anomali yang diperbaiki saat import

| # | Anomali | Perlakuan |
|---|---|---|
| 1 | `CEK AIR RADIATOR` ada di Servis CVT sheet Matic Karbu Non Radiator (motor tanpa radiator) | Dibuang dari template itu |
| 2 | `CEK SENSOR TPS (SCANNING)` ada di Servis Rutin sheet karbu, tidak ada di Servis Lengkap-nya | Dibuang dari template Rutin karbu. Perlu konfirmasi Rafi/Pak Novian. |
| 3 | `CEK AIR RADIATOR` masuk area Roda Belakang di satu sheet, area Busi & Radiator di sheet lain | Disatukan ke Busi & Radiator |
| 4 | `CEK HANDEL REM` muncul dua kali dalam satu kategori (Sport Injeksi Radiator, Ganti Oli) | Dicek apakah depan/belakang, lalu dibedakan namanya atau digabung |
| 5 | Area "Filter Udara" dan "CVT" memuat `CEK FILTER UDARA` yang sama | Satu area saja |

Ejaan istilah bengkel ("peang", "selek", "njeber") tidak diubah. Semua keputusan pembersihan dicatat di `.ai/decisions/`.

## 4. Alur: kondisi saat ini vs target

| Langkah | Kondisi saat ini | Perubahan |
|---|---|---|
| 1. CS input | Pelanggan dan keluhan sudah ada (`tbservis_keluhan_status`). Garapan masih Workorder paket. | Tambah pilihan jenis garapan (multi-pilih, 6 kategori). Fase B. |
| 2. Antrian | `tb_antrian_servis` sudah punya `status_antrian` dan `prioritas` (normal/urgent/vip). | Tidak ada perubahan struktur. |
| 3. Kamek menunjuk mekanik | `ajax-save-service.php` menyimpan kepala mekanik dan mekanik ke `tb_progress_mekanik` di form yang sama dengan input CS. | Layar terpisah untuk kamek, antrian diurutkan berdasarkan prioritas. Fase C. |
| 4. Mekanik mengecek | Temuan bebas (`tbservis_temuan`). | Checklist per item dari template. Fase D. |

Catatan: kolom "kondisi saat ini" dibuat dari pembacaan file kunci dan dokumen `docs/analysis/ANALISA_INPUT_SERVIS_REGULER.md`. Detailnya diverifikasi ulang di awal tiap fase.

## 5. Model data (draft)

Semua tabel baru memakai collation `utf8mb4_unicode_ci` secara eksplisit. `tb_antrian_servis` dan `tb_progress_mekanik` memakai `utf8mb4_general_ci`, sedangkan `tbservis_temuan` memakai `utf8mb4_unicode_ci`. Setiap JOIN lintas tabel-tabel itu wajib memakai `COLLATE` eksplisit (pernah menyebabkan gagal diam-diam).

### 5.1 Master (Fase A)

- `pengecekan_area` (id, nama, urut).
- `pengecekan_kondisi_pola` (id, label). Contoh label: `normal/redup/mati`.
- `pengecekan_kondisi_opsi` (id, pola_id, urut, label, level enum `ok`, `waspada`, `buruk`). Pola 3 pilihan: ok, waspada, buruk. Pola 2 pilihan: ok, buruk.
- `pengecekan_item` (id, kode, nama, area_id, pola_id, is_active). 80 baris.
- `pengecekan_grup_motor` (id, kode, nama, punya_cvt tinyint). 8 baris.
- `pengecekan_grup_motor_map` (grup_id, kd_kategori_motor). Petakan grup ke kategori motor yang sudah ada di sistem (`view_service_kategori_motor`).
- `pengecekan_kategori_garapan` (id, kode, nama, hanya_matic tinyint). 6 baris.
- `pengecekan_template` (grup_id, kategori_id, item_id, urut). Unique (grup_id, kategori_id, item_id). Sekitar 1.900 baris hasil import xlsx.

### 5.2 Transaksi (Fase B dan D)

Kunci ke servis: pasangan (`kd_cabang`, `no_service`), bukan `no_service` sendirian. `no_service` tidak unik lintas cabang (kasus 30 ribu+ data dobel). Di dump `tools/sql/fitmotor_dbbengkel_FIXED_V6.sql`, `tblservice` tidak punya kolom `id` (hanya index `idx_tblservice_no_service`), jadi tidak ada surrogate key. Setiap query dan JOIN wajib memuat kedua kolom. Di awal Fase B, cek ulang skema live dan cek apakah pasangan ini benar-benar unik di dalam satu cabang. Kalau tidak unik, tambah kolom kunci baru di `tblservice` lewat migration tersendiri sebelum tabel transaksi dibuat.

- `servis_garapan` (id, kd_cabang, no_service, kategori_id, dibuat_oleh, created_at).
- `servis_pengecekan` (id, kd_cabang, no_service, grup_id, status enum `belum`, `proses`, `selesai`, mekanik_id, mekanik_nama, mulai_at, selesai_at). Satu per servis. Unique (kd_cabang, no_service).
- `servis_pengecekan_detail` (id, pengecekan_id, item_id, item_nama, area_nama, opsi_id, opsi_label, level, catatan, foto, temuan_id nullable, diisi_at). Nama item dan area disalin (snapshot) supaya edit template di kemudian hari tidak mengubah riwayat servis.

Saat mekanik menekan "Mulai Pengecekan", sistem membentuk baris detail dari gabungan template semua kategori garapan yang dipilih (dedupe per item, urutan mengikuti area lalu urutan template).

### 5.3 Migration

- Semua migration reversible (aturan project). Setiap file `.sql` punya pasangan rollback.
- Dijalankan lewat runner PHP `db/migrations/*_run_*.php` (pola yang sama dengan `2026-09-15_run_komplain_rework_garansi.php`), bukan `mysql` CLI.
- Import template xlsx berupa skrip PHP idempoten yang membaca hasil parse xlsx dan mencatat baris yang dibuang (anomali di bagian 3).

## 6. Integrasi dengan temuan dan penawaran part

- Mekanik memilih kondisi level **buruk**: sistem membuat satu baris `tbservis_temuan` (`temuan_custom` = `<item>: <kondisi>`, `deskripsi_temuan` = catatan mekanik, `tingkat_urgensi` = `tinggi`, `jenis_perbaikan` default `setting`, `mekanik_id` dan `mekanik_name` dari mekanik yang mengisi, `status_temuan` = `ditemukan`). Baris detail menyimpan `temuan_id`.
- Kondisi **waspada**: baris detail ditandai sebagai saran. Ada tombol "Jadikan temuan" yang membuat temuan dengan `tingkat_urgensi` = `sedang`.
- Setelah temuan tercipta, alur penawaran part dan approval yang sudah ada tidak diubah.
- Mekanik mengubah kondisi dari buruk ke OK: temuan terkait dibatalkan hanya jika `status_temuan` masih `ditemukan`. Jika sudah `ditawarkan` atau lebih, perubahan ditolak dengan pesan jelas.
- `tbservis_temuan.mekanik_id` bertipe `varchar(10)`. Format ID mekanik yang dipakai perlu dicek supaya tidak terpotong.

## 7. Layar mekanik (Fase D, mobile-first)

- Dibuka dari daftar "Tugas Saya" (servis yang ditunjuk kamek ke mekanik itu).
- Item dikelompokkan per area dalam accordion. Satu item satu baris dengan 2-3 tombol besar (target sentuh minimal 48px). Level dibedakan dengan warna **dan** ikon/teks, bukan warna saja.
- Tombol "Semua Normal" per area untuk mempercepat pengisian (50-70 item per servis).
- Simpan otomatis per ketukan (AJAX). Kalau gagal tersimpan, tampilkan indikator dan ulangi. Indikator progres "34/61 item".
- Kondisi buruk membuka kolom catatan dan tombol foto (opsional).
- Tombol "Selesai Pengecekan" aktif hanya jika semua item terisi. Setelah selesai, ringkasan menampilkan temuan otomatis dan saran waspada.

## 8. Layar kamek (Fase C)

- Daftar antrian `menunggu` diurutkan `vip` > `urgent` > `normal`, lalu `jam_ambil`.
- Kamek memilih mekanik (dari mekanik cabang yang hadir, memanfaatkan data kepala mekanik harian yang sudah ada). Hasilnya ditulis ke `tb_progress_mekanik` dan status antrian menjadi `diproses`.
- Prioritas diisi CS saat input (sudah ada di form: `prioritas_antrian`). Kamek boleh mengubahnya.

## 9. Keamanan dan RBAC

- Semua handler baru memakai guard login yang sama dengan sibling file (`$_SESSION['_iduser']`) dan prepared statement.
- Permission RBAC baru (mis. `pengecekan_isi`, `pengecekan_master`, `antrian_tunjuk_mekanik`). Posisi yang boleh dipetakan: mekanik, kamek, admin. Pemetaan final ditentukan Rafi.
- Tampil hanya data cabang aktif user.

## 10. Fase dan hasil

| Fase | Isi | Selesai bila |
|---|---|---|
| A | Tabel master, import xlsx ter-bersih, halaman master read-only untuk verifikasi | Jumlah baris master dan template cocok dengan xlsx (dikurangi anomali yang dicatat), diverifikasi lewat query DB |
| B | Verifikasi skema live `tblservice` (keunikan `kd_cabang` + `no_service`), lalu multi-pilih garapan di form CS (`servis-input-reguler.php` dan varian jemput/garansi jika relevan) | Pilihan tersimpan di `servis_garapan`, terbaca kembali saat edit |
| D | Layar mekanik + generate temuan | E2E di HP viewport: isi checklist, buruk menjadi temuan, waspada menjadi saran |
| C | Layar kamek + urutan prioritas | Antrian tampil terurut, penunjukan mekanik memperbarui `tb_progress_mekanik` |

Di akhir tiap fase: tes E2E lewat browser (login admin, cabang PST), verifikasi lewat query DB, commit, update `.ai/changelog`, update checklist-projek.

## 11. Risiko

1. **`no_service` tidak unik lintas cabang, dan `tblservice` tidak punya surrogate key** (di dump V6). Semua tabel transaksi baru memakai pasangan (`kd_cabang`, `no_service`). Keunikan pasangan itu di dalam satu cabang diverifikasi di Fase B. Catatan: `tb_antrian_servis` dan `tb_progress_mekanik` di dump yang sama tidak punya `kd_cabang`, jadi JOIN ke keduanya harus disaring lewat `tblservice` agar tidak campur antar cabang. Dokumen `docs/analysis/ANALISA_INPUT_SERVIS_REGULER.md` menyebut `tblservice.id`, tetapi dump tidak mendukungnya. Skema live yang jadi acuan.
2. **Collation campur** (`general_ci` vs `unicode_ci`). Semua JOIN memakai `COLLATE` eksplisit.
3. **Mapping tipe motor ke 8 grup.** Sheet `KATEGORI MOTOR` memetakan nama tipe, sedangkan sistem memakai `kd_kategori_motor`. Perlu dicek di Fase A apakah granularitasnya cocok. Kalau tidak cocok, tambah mapping manual di halaman master.
4. **Akses browser dari WSL.** E2E hanya lewat tool browser (Chrome/Playwright), bukan `curl`.
5. **Kualitas xlsx.** Anomali bagian 3 dikonfirmasi ke Pak Novian sebelum template dianggap final.
6. **Sinyal di bengkel.** Simpan otomatis membutuhkan koneksi. Mode offline (PWA) tidak masuk lingkup awal. Cukup ada indikator gagal simpan dan ulangi otomatis.

## 12. Di luar lingkup

- Cetak atau WA hasil pengecekan ke pelanggan.
- Mode offline penuh.
- Perubahan pada pipeline penawaran part dan approval.
- Perubahan pada modul Komplain, Garansi, atau Kasir.

## 13. Pertanyaan terbuka

1. Anomali #2 (TPS di karbu) dan #4 (Handel Rem dobel): mana yang benar?
2. Mekanik yang mengisi checklist: boleh lebih dari satu mekanik per servis (`tb_progress_mekanik` punya 4 slot mekanik)? Usulan awal: satu penanggung jawab checklist, mekanik lain hanya membantu.
3. Siapa yang boleh mengubah template (master) setelah import? Usulan: hanya admin/kamek dengan permission `pengecekan_master`.
