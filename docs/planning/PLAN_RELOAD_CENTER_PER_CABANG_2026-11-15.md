# Rencana Muat Ulang Data dari fitmotor_center per Cabang

Tanggal: 2026-11-15. Lanjutan `AUDIT_DATABASE_CENTER_VS_DBBENGKEL_2026-11-15.md`.
Lingkup: database **lokal** `fitmotor_dbbengkel` (Laragon). Produksi FITMOTOR1 menyusul setelah kelima sesi lulus.

## 1. Keputusan Rafi (2026-11-15)

| # | Pertanyaan | Keputusan |
|---|---|---|
| 1 | Histori servis lama PSL/PCL/CDT/TRM | **Diganti total** dari `fitmotor_center` |
| 2 | Konflik master | Minta analisa detail + usulan (bagian 3) |
| 3 | Rentang histori | **Semua data** |
| 4 | Tabel HR (`tbpegawai`, `tbjabatan`, ...) | **Masih direncanakan**, tidak dihapus |
| 5 | 15 DB lokal lain | **Jangan dihapus** |
| 6 | Urutan | Per sesi: **Adiwerna (PESALAKAN) -> Pacul -> Cikditiro -> Trayeman -> Pusat** |

Prinsip tambahan: data lama dibuang, diganti isi center terbaru (sumber = VPS, read-only `fm_viewer`).

## 2. Alat

| File | Fungsi |
|---|---|
| `db/migrations/2026-11-15_run_center_reload_schema.php [up\|down]` | kolom `kd_cabang` di `tblhutang_detail`/`tblpiutang_detail`, 21 index `(nomor, kd_cabang)`, index `tblpembelian_detail(no_item,id)` untuk trigger alarm, tabel `center_reload_log` |
| `scripts/center_reload/reload_cabang.php --cabang=X --mode=preflight\|run\|verify\|rollback [--only=...] [--tag=MMDD]` | muat ulang satu cabang |

Cara kerja `run` per tabel (satu transaksi DB per tabel):
1. Backup semua baris cabang itu (kode lama + kanonik) ke `bak_cr<tag>_<cabang>_<tabel>`. Sekali saja, tidak ditimpa saat diulang.
2. Hapus baris kode lama (`PSL`, `PCL`, `CDT`, `TRM`) dan baris kanonik hasil reload sebelumnya.
   Baris input web asli (kanonik, ada di backup) tidak disentuh, sehingga anak FK CASCADE-nya aman.
3. Isi dari center dengan mapping kolom (lihat kode). Nomor yang bentrok dengan baris web asli dilewati dan dicatat.
4. Master global (pelanggan, kendaraan, item, supplier): hanya ditambah yang belum ada.
   `tblmekanik` di web adalah VIEW, tidak dimuat.
5. Kartu stok `tbstok`: dibangun dari transaksi center (tipe 2 beli, 3 jual, 4 servis, 5 masuk, 6 keluar, 7 retur beli)
   lalu ditambah baris `tipe='M'` `REKON-ACC-<cabang>` supaya saldo per item = `tblitem.Quantity` Access.
6. Alarm harga beli palsu dari trigger `trg_alarm_harga_beli` dihapus setelah muat histori pembelian.
7. Teks lebih panjang dari kolom target dipotong sesuai panjang kolom.
8. Semua langkah dicatat di `center_reload_log`.

Mapping status servis: Access `Status=3` (lunas) -> `status='4'`, `status_servis='bayar'`
(sama dengan `servis-reguler-byr.php`). Impor lama salah memberi `status='1'`, sehingga 48 rb servis lama tidak
muncul di panel riwayat servis (query `s.status='4'`). Reload memperbaiki ini.

Env wajib (tanpa default): `CENTER_DB_HOST`, `CENTER_DB_PORT`, `CENTER_DB_USER`, `CENTER_DB_PASS`, `CENTER_DB_NAME`.
Akses VPS lewat tunnel SSH: `ssh -L 23306:127.0.0.1:13306 <user>@<vps>`.
Gotcha lokal: dari Windows `127.0.0.1:3306` masuk ke MySQL WSL, bukan Laragon. Pakai `localhost` (seperti aplikasi).

Runbook satu sesi:
```
# 0. backup penuh (view_suggested_parts rusak, dikecualikan)
mysqldump.exe -hlocalhost -u<user> --single-transaction --routines --triggers --no-tablespaces \
  --default-character-set=utf8mb4 --ignore-table=fitmotor_dbbengkel.view_suggested_parts fitmotor_dbbengkel > backups/pre-center-reload-<tgl>-<cab>.sql
# 1-3
php scripts/center_reload/reload_cabang.php --cabang=<CAB> --mode=preflight
php scripts/center_reload/reload_cabang.php --cabang=<CAB> --mode=run --tag=<MMDD>
php scripts/center_reload/reload_cabang.php --cabang=<CAB> --mode=verify --tag=<MMDD>
# batal:
php scripts/center_reload/reload_cabang.php --cabang=<CAB> --mode=rollback --tag=<MMDD>
```

## 3. Analisa konflik master dan usulan solusi

Sumber: `fitmotor_center` (gabungan 5 cabang). Access menyalin master antar cabang, jadi satu kode muncul di 1-5 cabang.

### 3.1 Pelanggan (166.774 baris, 40.124 kode unik)

| Fakta | Angka |
|---|---:|
| Kode pelanggan = nomor polisi kendaraan | 39.910 dari 40.124 (99,5%) |
| Salinan identik di semua cabang | 27.842 |
| Nama beda antar cabang | 1.600 |
| - beda hanya spasi/huruf besar/titik | 174 |
| - nama depan sama, sapaan beda (`AHMAD,MAS` vs `AHMAD`) | 425 |
| - orang berbeda atau isian umum (`BAPAK`) | ~1.000 |
| Telepon beda antar cabang | 1.331 |

Makna: identitas sebenarnya = **kendaraan (nopol)**. Nama di tiap cabang = siapa yang datang terakhir ke cabang itu
(anggota keluarga berbeda, atau isian umum `BAPAK`).

Usulan:
1. Kunci global tetap `nopelanggan` (= nopol). Tidak dipecah per cabang.
2. Nama + telepon kanonik diambil dari **cabang dengan servis terakhir** untuk nopol itu (`MAX(tblservice.Tanggal)`),
   karena itu data kontak paling baru. Nama umum (`BAPAK`, `IBU`, `MAS`, `-`) tidak boleh menang selama ada nama lain.
3. Semua varian nama/telepon disimpan di tabel baru `pelanggan_alias (nopelanggan, kd_cabang, nama, telepon, terakhir_servis)`
   untuk pencarian kasir (cari nama lama tetap ketemu) dan audit.
4. 174 beda format: otomatis. 425 beda sapaan: otomatis (pakai versi terlengkap). ~1.000 orang berbeda: otomatis pakai aturan 2,
   ditandai `perlu_review=1` untuk dicek CS saat pelanggan datang (bukan blokir).
5. Saat ini reload hanya MENAMBAH pelanggan yang belum ada; baris lama web tidak ditimpa sampai usulan ini disetujui.

### 3.2 Kendaraan (186.391 baris, 40.054 nopol)

| Fakta | Angka |
|---|---:|
| Merek/tipe beda antar cabang | 3.928 |
| Pemilik beda antar cabang | 1.155 |

Usulan: kunci `nopolisi`. Merek/tipe/no rangka/no mesin diambil dari baris yang **paling lengkap** (kolom terisi terbanyak),
seri dari cabang servis terakhir. Pemilik ikut aturan pelanggan (3.1). Ganti pemilik riil dicatat di
`kendaraan_plat_history` / modul pindah kepemilikan yang sudah direncanakan.

### 3.3 Item / sparepart (22.923 baris, 6.081 kode)

| Fakta | Angka |
|---|---:|
| Ada di Pusat (PST) | 6.037 dari 6.081 |
| Nama beda antar cabang | 805 (21 hanya format) |
| Jenis beda | 450 |
| Satuan beda | 42 |
| Harga jual beda antar cabang | 2.330 |
| Web: nama beda dengan PST | 428 |
| Web: tidak ada di center | 40 |
| Web: kode dobel (`aa` x12, `DMY01`, `10009`, ...) | 5 kode |

Contoh: `06381-KEV-880` = `STANG SEHER SUPRA X` / `STANG SEHER H.SUPRA X`. Kode item = nomor part, jadi barangnya sama,
hanya cara tulis nama yang beda.

Usulan:
1. Master item global = **versi PST** (nama, jenis, satuan, barcode). 44 item tanpa PST: ambil dari cabang yang paling sering menjual.
2. **Harga beda per cabang itu sah**: disimpan per cabang di `tblitem_harga_cabang` (sudah diisi reload, kolom `qty_access` = stok Access).
3. Satuan beda (42): wajib review manual (salah satuan = salah stok). Daftar dikeluarkan sebagai laporan.
4. 5 kode dobel di web (`aa`, `DMY01`, ...) = data uji: dihapus setelah dicek tidak dipakai transaksi.
5. Setelah ini: `tblitem.noitem` diberi UNIQUE (PK `id` + UNIQUE `noitem`).

### 3.4 Supplier (884 baris, 286 kode)

| Fakta | Angka |
|---|---:|
| Kode sama, nama beda | 7 (contoh `AHASSS`: `AHASS 16675` vs `ASTRA MOTOR`) |
| Nama sama, kode beda | 15 |

Usulan: kode supplier global, nama dari PST. 7 kode bentrok dan 15 nama dobel direview manual (sedikit, sekali saja),
lalu dibuat tabel pemetaan `supplier_alias (kd_cabang, nosupplier_asal, nosupplier_kanonik)`.

### 3.5 Mekanik (444 baris)

Tidak ada konflik (0 kode dengan nama beda). Master mekanik global aman. `tblmekanik` di web berupa VIEW;
yang perlu adalah penempatan cabang mekanik (terkait `tbuser_karyawan`).

## 4. Rencana per sesi

Setiap sesi: backup -> preflight -> run -> verify -> cek UI (riwayat servis, laporan, kartu stok) -> update changelog/CLAUDE.md.

| Sesi | Cabang | Syarat sebelum mulai | Catatan khusus |
|---|---|---|---|
| 1 | PESALAKAN (Adiwerna) | migrasi skema up | sesi 2026-11-15 |
| 2 | PACUL | **Fase K (kunci gabungan transaksi)** selesai | data PCL lama 127 rb detail servis |
| 3 | CIKDITIRO | Fase K | baris CDT lama berisi detail cabang lain (215 rb vs sumber 77 rb), dibersihkan total di sesi ini |
| 4 | TRAYEMAN | Fase K | data paling kecil |
| 5 | PST (Pusat) | Fase K | tidak punya kode lama; PST paling banyak data web asli (uji), cek bentrok nomor |

### Fase K: kunci gabungan transaksi (wajib sebelum sesi 2)

Nomor transaksi Access bentrok lintas cabang di center:
PO 2.212, pesanan jual 459, pembelian 9.809, penjualan 6.389, hutang 739, item masuk 1.015, kas 51 (servis 32.750, sudah ditangani 2026-11-14).
Setelah cabang ke-2 dimuat, kode web yang mencari `WHERE notransaksi = ?` tanpa `kd_cabang` akan mencampur dua cabang.

Pekerjaan:
1. PK `tblorder_header` -> `(no_order, kd_cabang)`; tambah `kd_cabang` di `tbldelivery_order_header` dan `tblpo_approval_log`,
   FK `fk_do_po` / `tblpo_approval_log_ibfk_1` diubah ke kunci gabungan. (Gagal di migrasi 2026-11-15 karena FK ini, sengaja ditunda.)
2. Audit + perbaiki query modul Pembelian, Penjualan, Pesanan, Hutang, Piutang, Retur, Stok Masuk/Keluar, Kas agar selalu
   menyertakan `kd_cabang` (pola sama dengan perbaikan `no_service` 2026-11-14, termasuk penjaga pre-commit).
3. UNIQUE `(nomor, kd_cabang)` di header-header tersebut.

### Pekerjaan setelah kelima sesi

1. Terapkan solusi master (bagian 3) setelah disetujui: `pelanggan_alias`, `supplier_alias`, nama item PST, UNIQUE noitem.
2. Hitung ulang `statistik_pelanggan` (kunjungan, total belanja, tier) dari histori baru.
3. Ganti `gabung_*` mirror dengan view dari tabel inti.
4. Sync inkremental harian center -> web (watermark `IDTabel`/`Tanggal`) memakai mapping yang sama dengan `reload_cabang.php`.
5. Uji E2E (Playwright) halaman riwayat servis, laporan servis/penjualan/pembelian, kartu stok per cabang.
6. Produksi FITMOTOR1: backup -> migrasi skema -> reload 5 cabang -> verify.

## 5. Risiko yang diketahui

- Teks non-latin1 dari center (utf8mb4) jadi `?` karena tabel/koneksi aplikasi latin1 (lihat audit, konversi collation).
- `view_suggested_parts` rusak (collation tidak valid untuk latin1), dipakai `app/ajax-get-suggested-parts.php`; tidak bisa di-dump.
- Komisi lama (`servis_komisi`) tidak dihitung ulang untuk histori Access.
- HPP barang servis dari `tblserviceitemdtfifo` (FIFO Access) = referensi histori, bukan HPP Moving Average.

## 6. Hasil sesi

### Sesi 1: PESALAKAN (Adiwerna), 2026-11-15 — SELESAI (lokal)

Backup penuh: `backups/pre-center-reload-20261115.sql` (250 MB, tanpa `view_suggested_parts` yang rusak).
Backup per tabel: `bak_cr1115_PESALAKAN_*` (26 tabel). Sumber: VPS `fitmotor_center`, data sampai 2026-11-12.

| Dataset | Dibuang (lama) | Dimuat (center) | Dilewati |
|---|---:|---:|---:|
| tblservice | 48.312 (PSL) | 50.269 | 0 |
| tblservis_barang | 19.018 | 195.992 | 0 |
| tblservis_jasa | 5.098 | 61.921 | 0 |
| tblservice_advisor | 2.018 | 25.184 | 0 |
| tblservice_jemputantar | 0 | 2.429 | 1 (dobel di sumber) |
| tblpembelian_header / detail | 0 | 13.448 / 62.724 | 1 (bentrok BL26000000004 web) |
| tblpenjualan_header / detail | 0 | 11.241 / 58.744 | 0 |
| tblorder_header / detail (PO) | 0 | 796 / 5.163 | 7 / 28 (lihat bawah) |
| tblorderjual_header / detail | 0 | 1.390 / 16.669 | 0 |
| tblhutang_header / detail | 0 | 3.006 / 3.150 | 0 |
| tblpiutang_header | 0 | 11 | 0 |
| tblretur_pembelian_header / detail | 0 | 12 / 82 | 0 |
| tbitem_masuk_* / tbitem_keluar_* | 0 | 1.289+2.319 / 1.076+2.985 | 1 |
| tblkas_keluar_masuk | 0 | 51 | 0 |
| tblitem_harga_cabang | 0 | 4.180 | 1 |
| tbstok | 18.811 (PSL) | 322.830 mutasi + 182 rekonsiliasi | - |
| master tambah | - | pelanggan 572, kendaraan 642, item 50, supplier 36 | sudah ada |

Verify (jumlah baris + total rupiah, center vs web tanpa baris web asli): servis, barang, jasa, beli detail,
jual, jual detail, hutang **COCOK persis**. Saldo stok 194 item / 2.521 unit = `tblitem.Quantity` Access **COCOK**.
Selisih yang dijelaskan:
- Pembelian -1: `BL26000000004` sudah dipakai transaksi uji web PESALAKAN (2026-06-30, Rp160.000).
- PO -7: nomor Access `PS25000000001/03/05`, `PS26000000007` sudah dipakai PO uji web PST; `PS25000000002/04`,
  `PS26000000006` dipakai PO uji web PESALAKAN. Penomoran PO web dan Access memakai format sama (`PSyy...`).
  Fase K (PK gabungan) menyelesaikan bentrok antar cabang; bentrok dengan PO uji di cabang sama butuh keputusan
  (hapus data uji atau ganti seri nomor web).
- `status='4'` sekarang 50.265 servis (dulu 0 karena impor lama), jadi riwayat servis Adiwerna muncul di panel riwayat.
- 18.699 alarm harga beli palsu dari trigger dihapus (tidak ada alarm asli sebelum reload).

Kejadian saat run (sudah diperbaiki di tool): teks melebihi panjang kolom (`lavelharga`), `tblmekanik` ternyata VIEW,
PO bentrok PK, JOIN alarm beda collation. Run gagal di tengah selalu rollback per tabel, lalu dilanjut dengan `--only`.

Penuntasan sesi 1 (2026-11-15):
- `scripts/center_reload/refresh_statistik_cabang.php --cabang=PESALAKAN --mode=run --tag=1115`: 16.712 pelanggan dihitung ulang
  (fungsi live `updateStatistikPelangganAfterPayment` + floor tier grup Access), 0 gagal, 195 dtk. Tier naik 70, turun 0,
  616 baris statistik baru. Backup `bak_cr1115_PESALAKAN_statistik_pelanggan` (rollback: `--mode=rollback`).
  Total kunjungan turun 52.434 -> 51.615: data lama menghitung detail/servis salah atribusi.
- Uji browser (session admin): cari nopol `G 4593 BN` -> Terakhir Datang 15 hari (servis center 31/10/2026), tier GOLD.
  Laporan Servis 1-14/11/2026: 114 baris = 113 servis Adiwerna lunas + 1 CIKDITIRO; `SV26000003626` barang 145.165 / total 330.000
  sama dengan center. Catatan: `lap_servis.php` tidak memfilter cabang (menampilkan semua cabang) — perilaku lama, ikut ditinjau di Fase K.
- Kartu stok `08232-2MB-K8JN1` Adiwerna Nov 2026: bug lama ditemukan dan diperbaiki di `app/barang_kartu_stok_rst.php`
  (saldo berjalan dulu mulai 0 tiap bulan karena saldo awal = `inv_jmlawal`; sekarang saldo awal = mutasi sebelum tanggal 1,
  baris diurutkan per tanggal). Hasil: saldo awal 82, saldo akhir 65 = stok Access.

### Sesi 2: PACUL, 2026-11-16 — SELESAI (lokal)
- Keputusan Rafi: seri PO web diganti `PW`; usulan master (bagian 3) disetujui; semua cabang `tipe_cabang='2'` kecuali PST (gudang pusat) `'1'`;
  servis `TotalAkhir=0` DIBIARKAN (dicek di center: semua kasus diskon 100% atau kosong, bukan cacat).
- Fase K selesai: PK `tblorder_header` (no_order,kd_cabang), FK DO & approval log gabungan, 5 PO web lama bernomor `PW26...`,
  UNIQUE (nomor,kd_cabang) di 9 header, 184 query/82 file diberi `kd_cabang`, penjaga pre-commit `scripts/audit_nomor_transaksi.php`.
  Migrasi `2026-11-16_run_fase_k_po_composite.php`, `2026-11-16_run_fase_k_unique_header.php`. Uji: PO/DO/approval baru, penjualan lulus.
- Muat PACUL dari salinan lokal `fitmotor_center` (data s/d 2026-11-12; password VPS tidak dipakai). Backup `backups/pre-center-reload-20261116-pacul.sql`,
  tabel `bak_cr1116_PACUL_*`. Verify semua COCOK (servis 41.228, beli 9.824, jual 6.225, PO 2.788, stok 655 item). Statistik 16.387 pelanggan.
- Nomor sama di PACUL & PESALAKAN (mis. `PS21000000001`) tampil terpisah, tidak bercampur. Smoke 250 halaman: 247 OK (3 fragmen tanpa koneksi).
- Sisa: Cikditiro, Trayeman, Pusat; refresh dari VPS bila perlu.

### Sesi 3-5: CIKDITIRO, TRAYEMAN, PST, 2026-11-16 — SELESAI (lokal)
- Dimuat dari salinan lokal `fitmotor_center` (s/d 2026-11-12), tag `1116`, tabel backup `bak_cr1116_<CAB>_*`, dump `backups/pre-center-reload-20261116-cikditiro.sql`.
- Verify COCOK semua cek (CDT servis 16.410, beli 4.389, PO 2.158, hutang 639; TRM servis 2.896, beli 1.134, PO 746; PST beli 1.212, jual 1.518, PO 41, hutang 512, SO 1.716).
- Statistik pelanggan: CDT 6.828, TRM 1.762 dihitung (3 gagal tiap cabang, belum ditelusuri). PST tanpa servis.
- Cek lintas: 0 PO dobel per cabang, 0 baris kode lama (PSL/PCL/CDT/TRM). Smoke 13/15 halaman per cabang (302 redirect dan 404 sama seperti Adiwerna). Penjualan baru lulus di CDT & TRM.
- Catatan: 5 saldo negatif di `view_stok_master`; stok `kd_cabang` ''/'001' (112 baris) bawaan lama, tidak disentuh. Data uji antar cabang TRAYEMAN dihapus oleh muat ulang. Admin CIKDITIRO masih kosong.
- Semua 5 cabang sudah dimuat. Sisa: uji tulis lengkap per cabang (servis bayar, retur, kas, antar cabang), 3 pelanggan gagal statistik, produksi.
