# Audit Database: fitmotor_center (Access gabungan) vs fitmotor_dbbengkel (Web Base)

Tanggal: 2026-11-15. Status: ANALISA + RENCANA. Belum ada perubahan skema/data yang dijalankan.
Lampiran: `LAMPIRAN_KLASIFIKASI_TABEL_DBBENGKEL_2026-11-15.csv` (396 objek, kategori + rekomendasi per tabel/view).

## 1. Sumber yang diaudit

| Lokasi | Database | Isi | Cara akses audit |
|---|---|---|---|
| VPS `51.79.218.96` (container `video-fb-mysql`, forward `127.0.0.1:13306`) | `fitmotor_center` | 82 tabel + 218 view, ~2,52 jt baris, 745 MB | akun read-only `fm_viewer` (hanya SELECT/SHOW VIEW di `fitmotor_center`) lewat tunnel SSH |
| Laragon lokal | `fitmotor_center` | 82 tabel + 215 view, salinan VPS (selisih 33-87 baris di pelanggan/kendaraan/servis, VPS lebih baru) | CLI php.exe |
| Laragon lokal | `fitmotor_dbbengkel` | 334 tabel + 62 view, 22 trigger, 2 function, 92 FK, ~1,65 jt baris | CLI php.exe |
| Laragon lokal | 16 DB lain (`fitmotor_center_new`, `fitmotor_gabung`, `fitmotor_gabung_v2`, `fitmotor_report`, `fitmotor_crm`, `fitmotor_pelanggan`, dst) | eksperimen/turunan lama | tidak dipakai kode web base (hanya `tools/` audit lama) |

Catatan: produksi web base (`fitmotor_dbbengkel`) ada di server WSL FITMOTOR1, bukan di VPS. VPS hanya memuat `fitmotor_center`.

### Apa itu fitmotor_center

Gudang data (warehouse) hasil ETL "BUILD 7 mode=warehouse" (`mig_run`) dari 5 file Access cabang. Semua tabel = struktur
Access asli (PascalCase, `varchar(255)`, `double`) + kolom `kode_cabang` 9 digit. Kunci = `(kode_cabang, No...)`.
Data aktif sampai 2026-11-12, artinya **Access masih sistem utama transaksi harian**.

| kode_cabang center | kd_cabang web (kanonik) | Servis | Penjualan | Pembelian | Data sejak |
|---|---|---:|---:|---:|---|
| 201601001 | PESALAKAN (Adiwerna) | 50.241 | 11.241 | 13.449 | 2015 |
| 201809001 | PACUL | 41.228 | 6.225 | 9.824 | 2018 |
| 202201001 | CIKDITIRO | 16.410 | 730 | 4.389 | 2022 |
| 202505001 | TRAYEMAN | 2.896 | 183 | 1.134 | 2025 |
| 202601001 | PST (Pusat) | 0 | 1.518 | 1.212 | 2026 |

Pemetaan kode ini sudah ada di `app/_include_access_sync.php` (`accessSyncCabangReferenceMap`) dan di kolom referensi `tbcabang`.

## 2. Temuan kritis

1. **Tiga sistem kode cabang campur di dbbengkel.** `tblservice`, `tblservis_barang/jasa`, `tbstok` berisi `PSL/PCL/CDT/TRM`
   (impor lama) dan `PESALAKAN/PACUL/CIKDITIRO/TRAYEMAN/PST` (input web). `tbstok` juga punya `001` (88 baris) dan kosong (24 baris).
   `tbcabang` hanya kenal kode kanonik. Query per cabang otomatis meleset.
2. **Histori servis lama salah atribusi cabang.** Detail barang servis di dbbengkel: PSL 19.018 baris, CDT 215.523 baris.
   Sumber center: PESALAKAN 195.992, CIKDITIRO 77.538. Penyebab: impor lama memakai `no_service` saja sebagai kunci, padahal
   `no_service` bentrok lintas cabang (lihat `.ai/changelog/2026-11-14-no-service-composite-key.md`). Detail cabang lain
   ikut tertempel ke servis CDT. Laporan histori/komisi/HPP dari data lama tidak bisa dipercaya.
3. **Sinkronisasi Access -> web berhenti 2026-06-09** (`sync_access_runs`, terakhir sebagian `failed`). Data impor di dbbengkel
   berhenti Mei-Juli 2026, sedangkan center sudah sampai November 2026. Selisih ~7.300 servis dan seluruh 5 bulan transaksi.
4. **Pembelian, penjualan, order, hutang, mutasi stok praktis kosong di web.** Center: 30.008 faktur beli, 19.897 penjualan,
   6.536 PO, 4.204 hutang. Web: 4 / 2 / 7 / 0 (data uji). Modul ini belum pernah dimuat dari Access.
5. **Tabel inti tanpa PRIMARY KEY**: `tblservice`, `tblpelanggan`, `tblitem`, `tblkendaraan`, `tblsupplier`, `tblmekanik`,
   `tblpembelian_header`, `tblpenjualan_header`, `tblservice_advisor`, `tblhutang_*`, `tblpiutang_*`, dan lainnya (28 tabel aktif).
   `tblitem.noitem` tidak unik (5.919 baris, 5.904 distinct). `tblpenjualan_header`, `tblsupplier` bahkan tanpa index sama sekali.
6. **Master duplikat lintas cabang di sumber.** Center: 166.774 pelanggan = 40.124 distinct (1.600 nopelanggan beda nama
   antar cabang); 22.923 item = 6.081 distinct (805 noitem beda nama antar cabang); 884 supplier = 286 distinct.
   `NoPelanggan` umumnya berisi nomor polisi. Web punya 37.674 pelanggan: 183 tidak ada di center (input web), 2.633 pelanggan
   center belum masuk web.
7. **Collation campur 5 jenis** (`latin1_swedish_ci` 173 tabel, `utf8mb4_unicode_ci` 83, `utf8mb4_general_ci` 44,
   `utf8mb4_0900_ai_ci` 31, `utf8mb3` 3). Sudah terbukti bikin JOIN lambat/gagal diam-diam (insiden `view_laporan_cancel_servis` 3,2 s).
8. **Sampah skema**: 23 tabel backup (`bak_*`, `*_backup*`, salah satunya 167 rb baris), 54 tabel tanpa pemakaian kode
   (sisa modul HR/payroll template, RFQ, SO, koreksi stok lama), 27 view tanpa pemakaian, 9 tabel `gabung_*` mirror.
9. Kredensial DB tertulis di `_sync_gabung_direct.vbs` dan fallback `config/koneksi.php` (utang teknis lama, wajib env var).

## 3. Klasifikasi objek fitmotor_dbbengkel

| Kategori | Jumlah | Arti | Tindakan |
|---|---:|---|---|
| AKTIF | 175 | dipakai kode runtime dan berisi data | pertahankan, rapikan kunci/index |
| AKTIF-KOSONG | 52 | dipakai kode, 0 baris (Moving Average `inv_*`, retur, hutang/piutang, mutasi, promo log) | pertahankan, isi saat cutover |
| VIEW-AKTIF | 35 | view dipakai kode | pertahankan, tinjau performa |
| MATI | 54 | tidak dirujuk kode runtime maupun view | arsip (dump) lalu drop |
| VIEW-MATI | 27 | view tak terpakai | drop |
| BACKUP | 23 | salinan sebelum migrasi | dump ke `backups/` lalu drop |
| STAGING-SYNC | 18 | `stg_access_*`, `sync_access_*` | jadikan lapisan staging resmi |
| MIRROR-GABUNG | 9 | `gabung_*` salinan analitik Access | ganti dengan view/mart dari tabel inti |
| MIGRASI-NS | 3 | `ns_*` karantina no_service | pertahankan sampai karantina selesai |

Metode: setiap nama tabel dicari sebagai token di 2.142 file kode aktif (php/sql/js/vbs, tanpa `archive/`, `vendor/`,
`db/`, `tools/`, `scripts/`, `tests/`, `docs/`), ditambah dependensi dari definisi view. Kode yang membangun nama tabel
secara dinamis (string gabungan) tidak terdeteksi, jadi daftar MATI wajib dicek ulang sebelum drop.

Daftar MATI (54): `audit_log_closing_kasir, closing_transaction_details_closing_kasir, closing_transaction_groups_closing_kasir,
laravel_migrations, log_perubahan_tarif, tb_booking, tb_mekanik_progress, tbabsensi_temp, tbabsensi_temp_rst, tbabsensi_upload,
tbagama, tbbrg_keluar_detail, tbbrg_keluar_header, tbbrg_masuk_detail, tbbrg_masuk_header, tbcara_bayar, tbdarah, tbdivisi,
tbemp_education, tbemp_family, tbemp_training, tbemp_tunjangan, tbhari_kerja, tbhistory_divisi, tbjabatan, tbjenis_bayar, tbjk,
tbkoreksi_stok_detail, tbkoreksi_stok_header, tblist_bpjs, tblkeluhan, tblpr_tracking, tblrfq_detail, tblrfq_header,
tblrfq_supplier_response, tbmaster_barang_nonitem, tbmaster_keluhan_temuan, tbpanggilan, tbpegawai, tbpegawai_salary,
tbpendidikan, tbperusahaan, tbsaldo_piutang, tbso_detail, tbso_header, tbstatus_emp, tbstatus_kehadiran, tbstatus_nikah,
tbtarif_ptkp, tbtarif_ptkph, tbtipe_pajak, tbwork_loc, tbwork_schedule, tbwork_schedule_dtl`.
Catatan: `tbjabatan/tbdivisi/tbpegawai` dkk bisa jadi dipakai modul `_managemen/` (HR) lewat nama dinamis; cek dulu.

## 4. Mapping fitmotor_center -> fitmotor_dbbengkel

Aturan umum semua tabel:
- `kode_cabang` (9 digit) -> `kd_cabang` kanonik lewat kolom referensi `tbcabang` (bukan hardcode).
- Kolom PascalCase -> snake_case / lowercase sesuai kolom dbbengkel yang sudah ada (tabel di bawah).
- Kunci natural tiap transaksi = `(kd_cabang, nomor)`. Jangan pernah upsert dengan nomor saja.
- `NoteAsli` (teks asli Access) -> simpan di `note` atau kolom `note_asli` baru bila `note` sudah diolah.
- `IDTabel` -> `id_tabel` (dipakai sebagai watermark perubahan).
- `double` uang -> `DECIMAL(15,2)`; qty -> `DECIMAL(12,3)` kecuali kolom web yang sudah int (servis qty dibulatkan, lihat 2026-11-09).

| Center | dbbengkel | Kunci target | Transformasi khusus | Status data di web |
|---|---|---|---|---|
| tblcabang | tbcabang | kode_cabang | sudah ada, pakai kolom referensi 9 digit | lengkap |
| tblpelanggan (166 rb) | tblpelanggan (37,6 rb) | nopelanggan (global) | dedup lintas cabang: ambil baris terbaru per nopelanggan, cabang asal -> `domisili_cabang`; 1.600 konflik nama -> antrean review | kurang 2.633 |
| tblkendaraan (186 rb) | tblkendaraan (37 rb) | nopolisi (global) | dedup sama; `Merek/Tipe/Jenis/Warna` teks -> `kode_merek/kode_tipe/kode_jenis/kode_warna` lewat master; `NoRangka/NoMesin/TahunBuat` -> `no_rangka/no_mesin/tahun_buat` | kurang ~2,7 rb |
| tblitem (22,9 rb) | tblitem (master global) + harga/stok per cabang | noitem | master: baris PST, fallback cabang terbaru; harga/qty per cabang ke tabel harga cabang; 805 konflik nama -> review | kurang ~170 |
| tblitemjenis, tblitemsatuan | tblitemjenis, tblitemsatuan | nama | dedup per nama | sebagian |
| tblsupplier (884) | tblsupplier (152) | nosupplier | dedup per nosupplier, 286 distinct | kurang ~130 |
| tblmekanik (444) | tblmekanik + tbuser_karyawan | (kd_cabang, nomekanik) | `NoTelepon` -> `telp`; perlu kolom `kd_cabang` | belum per cabang |
| tblpelanggangrup | tblpelanggangrup | kgrup | sudah ada sinkron tier member | lengkap |
| tblservice (110 rb) | tblservice | (no_service, kd_cabang) unik `uq_service_cabang` | `Tanggal` datetime -> `tanggal`+`jam`; `KmSekarang`->`km_skr`; `Diskon`->`diskon_persen`; `Pajak`->`ppn_persen`; `Pembayaran`->`bayar`; `User`->`id_user`; `Status` Access -> `status` + `status_servis` | stop Mei-Jul 2026, atribusi lama rusak |
| tblserviceitemdt (467 rb) | tblservis_barang | (no_service, kd_cabang, nobaris) | `Harga`->`harga_jual`; `QtyRetur`->`qty_retur`; HPP dari `tblserviceitemdtfifo`/`tblservice_hppsts` -> `harga_pokok` | rusak, muat ulang |
| tblservicejasadt (136 rb) | tblservis_jasa | (no_service, kd_cabang, nobaris) | langsung | rusak, muat ulang |
| tblservice_advisor (74 rb) | tblservice_advisor | (no_service, kd_cabang) | tambah `kd_cabang` | tanpa kunci cabang |
| tblservice_jemputantar (4,2 rb) | tblservice_jemputantar | (no_service, kd_cabang) | langsung | kosong |
| tblpembelianheader/detail (30 rb/148 rb) | tblpembelian_header/detail | (kd_cabang, notransaksi[, nobaris]) | `NoOrder`->`no_order`; `IDTabel`->`Id_tabel` (ganti nama ke `id_tabel`) | kosong |
| tblpenjualanheader/detail (19,9 rb/85,6 rb) | tblpenjualan_header/detail | (kd_cabang, notransaksi[, nobaris]) | sama | kosong |
| tblorderheader/detail | tblorder_header/detail | (kd_cabang, noorder[, nobaris]) | PO | kosong |
| tblorderjualheader/detail | tblorderjual_header/detail | (kd_cabang, noorder[, nobaris]) | pesanan jual | kosong |
| tblhutangheader/detail | tblhutang_header/detail | (kd_cabang, notransaksi) | | kosong |
| tblpiutangheader/detail | tblpiutang_header/detail | (kd_cabang, notransaksi) | | kosong |
| tblreturbeliheader/detail | tblretur_pembelian_header/detail | (kd_cabang, notransaksi) | | kosong |
| tblitemmheader/detail, tblitemkheader/detail | tbitem_masuk_*/tbitem_keluar_* | (kd_cabang, notransaksi) | stok masuk/keluar manual | hampir kosong |
| tblkaskeluarmasuk, tblakunkas, tblrekap | tblkas_keluar_masuk, tblakunkas | (kd_cabang, notransaksi) | kas, cek tumpang tindih dengan modul Keuangan Kasir | hampir kosong |
| tblitemfifo, *dtfifo, *_hppsts | (tidak dimuat) | - | HPP resmi = Moving Average; data FIFO hanya referensi rekonsiliasi | - |
| labarugi_acuan_*, gabung_*, *_temp, stok_opnam_* | (tidak dimuat ke inti) | - | tabel kerja Access; hasilnya dibangun ulang sebagai view/mart | - |
| tbluser, tbluserakses | (tidak dimuat) | - | hak akses web sudah pakai `tbuser` + RBAC sendiri | - |
| siklus, bagi_hasil, persen_insentif | tbsiklus, tbbagi_hasil, tbpersen_insentif | | sudah dimirror | ada |
| history_garansi* (view, VPS saja) | (referensi) | - | dipakai cek garansi lintas cabang | - |

**Stok:** jangan memuat saldo stok dari `tblitem.Quantity` atau FIFO Access ke `tbstok`/`inv_*`. Saldo awal Moving Average
diambil dari opname fisik per cabang saat cutover (keputusan D-03 di `PLANNING_MOVING_AVERAGE_WEBBASE_2026-10-05.md`).
Histori mutasi Access boleh dimuat sebagai data arsip baca-saja.

## 5. Arsitektur target (best practice)

### 5.1 Lapisan data

```
Access MDB x5 --ETL warehouse--> fitmotor_center (RAW, read-only, VPS)
                                        |
                       job sync terjadwal (watermark id_tabel/tanggal)
                                        v
fitmotor_dbbengkel:  stg_center_*  (STAGING, bentuk asli + run_id + hash baris)
                          | validasi + mapping + dedup
                          v
                     tabel INTI (tblservice, tblpelanggan, ...) = satu sumber kebenaran web
                          |
                          v
                     view_* / mart_*  (laporan, rekap, analitik; pengganti gabung_*)
```

Aturan: aplikasi web hanya membaca/menulis tabel INTI dan view. Tidak ada kode web yang JOIN langsung ke `fitmotor_center`.

### 5.2 Konvensi

- Nama tabel: tetap pakai nama yang ada (mengganti nama 300+ tabel = risiko tinggi). Tabel baru: `snake_case`, prefix modul
  (`inv_`, `komplain_`, `kasir_`), tanpa `tb`/`tbl` baru.
- Setiap tabel punya `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` + UNIQUE kunci natural (`kd_cabang` + nomor).
  Tabel inti yang belum punya PK ditambahkan `id` + UNIQUE (reversible).
- `kd_cabang VARCHAR(20) NOT NULL` di semua tabel transaksi, FK ke `tbcabang(kode_cabang)`.
- Kolom audit standar: `created_at`, `updated_at`, `created_by`, `source` (`WEB`/`ACCESS`), `source_run_id`.
- Charset target: `utf8mb4` + `utf8mb4_unicode_ci` untuk semua tabel (konversi bertahap, mulai dari tabel yang sering di-JOIN:
  tblservice, tblservis_*, tblpelanggan, tblkendaraan, tblitem, tbstok, tbcabang). Koneksi saat ini `latin1`, jadi
  konversi koneksi + tabel harus satu paket dan diuji dengan data beraksen/emoji.
- Uang `DECIMAL(15,2)`, qty `DECIMAL(12,3)`, tanggal transaksi `DATE` + `TIME` atau `DATETIME` konsisten per modul.

### 5.3 Relasi (FK) prioritas

| Anak | Induk | Catatan |
|---|---|---|
| tblservice.kd_cabang, semua transaksi | tbcabang.kode_cabang | setelah kode cabang dinormalisasi |
| tblservis_barang/jasa (no_service, kd_cabang) | tblservice (no_service, kd_cabang) | sudah diterapkan di 13 tabel anak (2026-11-14), perluas ke barang/jasa setelah muat ulang |
| tblservice.no_polisi | tblkendaraan.nopolisi | ON UPDATE CASCADE (ganti plat lewat `kendaraan_plat_history`) |
| tblservice.no_pelanggan, tblpenjualan_header.no_pelanggan | tblpelanggan.nopelanggan | |
| *_detail.no_item | tblitem.noitem | setelah noitem unik |
| tblpembelian_header.no_supplier | tblsupplier.nosupplier | |
| *_detail (kd_cabang, notransaksi) | *_header (kd_cabang, notransaksi) | ON DELETE CASCADE |

Tabel sinkron Access sengaja tanpa FK keras di lapisan staging (keputusan 2026-11-14); FK hanya di tabel inti.

### 5.4 Index

- Wajib: UNIQUE kunci natural, index `(kd_cabang, tanggal)` di semua header transaksi, index FK di semua detail.
- `tblitem`: UNIQUE `noitem`, index `namaitem`, `kodebarcode`.
- `tblpelanggan`: index `no_wa`, `namapelanggan` (pencarian kasir).
- `tbstok`: `(kd_cabang, no_item, tanggal)` untuk kartu stok.
- Hapus index duplikat (`idx_tblservice_no_service` tumpang tindih dengan `uq_service_cabang`).
- Validasi tiap index dengan `EXPLAIN` pada query halaman nyata (laporan servis, cari item, statistik pelanggan).

### 5.5 View, stored procedure, trigger

- **View**: pakai untuk bacaan laporan (rekap servis per cabang/hari, omset, kartu stok, histori kendaraan lintas cabang).
  Ganti 9 tabel `gabung_*` dengan view/mart dari tabel inti. Drop 27 view mati. View berat -> tabel `mart_*` yang di-refresh job harian.
- **Stored procedure**: hanya untuk proses batch di database: `sp_sync_center_<dataset>(run_id)` (staging -> inti) dan
  `sp_refresh_mart_*`. Logika bisnis transaksi (bayar servis, potong stok, Moving Average) tetap di PHP
  (`InventoryService`) supaya teruji dan satu tempat.
- **Trigger**: minimal. Yang layak: isi `kd_cabang`/`updated_at` otomatis, log audit perubahan harga/status.
  Hindari trigger yang menghitung stok/komisi (sudah ada `trg_after_service_bayar`, `trg_update_statistik_after_payment`:
  tinjau agar tidak dobel dengan logika PHP). Trigger tidak ikut mysqldump user aplikasi di produksi, jadi wajib ada di migrasi.

### 5.6 Sinkronisasi center -> web (pengganti alur lama)

1. Job CLI (`scripts/sync_center.php`, jalan via cron/Task Scheduler) baca `fitmotor_center` dengan akun read-only.
2. Ambil baris baru/berubah per cabang memakai watermark (`IDTabel`/`Tanggal`) dan hash baris.
3. Tulis ke `stg_center_*` dengan `run_id`; validasi (cabang dikenal, kunci lengkap, angka valid).
4. Upsert ke tabel inti dengan kunci `(kd_cabang, nomor)`, kolom `source='ACCESS'`. Baris `source='WEB'` tidak pernah ditimpa.
5. Rekonsiliasi otomatis per cabang per hari: jumlah transaksi + total rupiah center vs web; selisih -> `sync_center_issue`.
6. Kredensial dari env var, tidak ada nilai default di skrip.

Satu cabang = satu sistem pencatat. Sebelum cutover cabang X: Access sumber, web hanya baca hasil sync. Sesudah cutover:
web sumber, sync dari Access untuk cabang X dimatikan.

## 6. Urutan kerja yang disarankan

| Fase | Isi | Risiko | Reversible |
|---|---|---|---|
| 0 | Backup penuh dbbengkel lokal + produksi; dump 23 tabel backup & 54 tabel mati ke `backups/` | rendah | ya |
| 1 | Normalisasi `kd_cabang` (PSL->PESALAKAN, PCL->PACUL, CDT->CIKDITIRO, TRM->TRAYEMAN, `001`/kosong -> investigasi) di semua tabel + FK ke tbcabang | sedang | ya (tabel map) |
| 2 | PK `id` + UNIQUE kunci natural di 28 tabel tanpa PK; bersihkan duplikat dulu | sedang | ya |
| 3 | Buat `stg_center_*` + job sync master (cabang, pelanggan, kendaraan, item, supplier, mekanik) dengan dedup + antrean review konflik | sedang | ya |
| 4 | Muat ulang histori servis (header, barang, jasa, advisor) dari center dengan kunci gabungan; ganti baris lama `PSL/PCL/CDT/TRM` | tinggi | ya (backup tabel) |
| 5 | Muat pembelian, penjualan, order, hutang, retur, mutasi (histori baca-saja, `source='ACCESS'`) | sedang | ya |
| 6 | Rekonsiliasi harian otomatis + laporan selisih | rendah | ya |
| 7 | Konversi collation ke utf8mb4 bertahap | sedang | ya |
| 8 | Drop tabel/view mati & backup setelah 1 siklus deploy aman | rendah | dari dump |
| 9 | Cutover per cabang (bersama opening stock Moving Average) | tinggi | per cabang |

Semua fase lewat `db/migrations/*_run_*.php [up|down]` (pola yang sudah dipakai), uji lokal dulu, lalu produksi FITMOTOR1.

## 7. Keputusan yang dibutuhkan dari Rafi

1. Fase 4: setuju data histori servis lama `PSL/PCL/CDT/TRM` diganti total dari center? (komisi/laporan lama di web ikut berubah)
2. Konflik master: item dengan noitem sama tapi nama beda (805) dan pelanggan (1.600): master acuan = PST, cabang terbaru, atau review manual?
3. Histori pembelian/penjualan Access: dimuat semua (sejak 2004/2015) atau mulai tahun tertentu?
4. Tabel MATI modul HR (`tbpegawai`, `tbjabatan`, `tbdivisi`, ...): masih ada rencana modul HR di web base? Kalau tidak, drop.
5. 15 DB lokal lain (`fitmotor_center_new`, `fitmotor_gabung_v2`, `fitmotor_report`, dst): boleh dump lalu hapus dari Laragon?
6. Urutan cutover cabang (pilot CIKDITIRO sesuai uji E2E terakhir?).
