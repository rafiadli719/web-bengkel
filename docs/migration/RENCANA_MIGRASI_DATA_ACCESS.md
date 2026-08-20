# Rencana Migrasi Data Access (FITMOTOR Desktop) ke Web Base

**Versi:** 1.0 — Tahap Analisa
**Tanggal:** 2026-07-20
**Status:** Draft riset — BELUM ada eksekusi, BELUM ada script migrasi ditulis, BELUM ada data disentuh.
**Referensi:**
- `docs/analysis/ANALISIS_ARSITEKTUR_PELANGGAN_KENDARAAN_ACCESS_TO_MYSQL.md`
- `docs/audit/REVERSE_ENGINEERING_ACCESS_FITMOTOR_CUSTOMER_VEHICLE.md`
- `docs/fsd/FSD_CUSTOMER.md`, `docs/fsd/FSD_KENDARAAN.md`, `docs/fsd/FSD_MEMBERSHIP.md`
- `tools/sql/fitmotor_dbbengkel_FIXED_V7.sql` (skema MySQL target)
- Sumber Access: `/mnt/e/BENGKEL 2.0/` (drive E: — `FITMOTOR.MDB` root, `DATABASE {CIKDITIRO,PACUL,PESALAKAN,PUSAT,TRAYEMAN}/FITMOTOR.MDB`, `FITMOTOR GABUNG.mdb`, `FITMOTOR APP.mdb`)

**Catatan metodologi:** dokumen ini disusun dari 2 sumber — (1) hasil inspeksi ulang langsung ke file `.mdb` memakai `access_parser` (pure-Python, karena `mdbtools`/pyodbc tidak tersedia di WSL tanpa akses root), dan (2) hasil investigasi sesi sebelumnya (row count per cabang, bukti tabrakan `no_service`) yang divalidasi konsisten dengan temuan sesi ini. Tidak ada asumsi tanpa bukti — tiap klaim di bawah menyebut sumber angkanya.

---

## 0. Ringkasan Eksekutif

1. **`fitmotor gabung.mdb` TERBUKTI sudah mewarisi masalah nomor kedobel lintas cabang** — persis dugaan awal. Contoh konkret: `NoService = SV23000002758` ada di 3 sumber (ROOT, PACUL, CIKDITIRO) dengan `IDTabel` identik (`23000002758`), tapi CIKDITIRO menyimpan **transaksi servis yang sama sekali berbeda** (tanggal beda 4 bulan, pelanggan beda, mekanik beda, nominal beda) dari ROOT/PACUL. Ini konfirmasi langsung: pola migrasi lama (gabung mentah tanpa re-key) adalah akar masalah `no_service` tidak unik lintas cabang yang sekarang diperbaiki di MySQL.
2. **Rekomendasi sumber migrasi: per cabang (5 file `FITMOTOR.MDB`), BUKAN `FITMOTOR GABUNG.mdb`.** `FITMOTOR GABUNG.mdb` bukan hasil dedup — 225 query tersimpan di dalamnya, hampir semua berstatus `ERR`, termasuk query konsolidasi kritis (`GABUNG_PELANGGAN`, `TIPE_MEMBER`, `UPDATE_TIPE_MEMBER`). Query dedup yang pernah dicoba cuma jalan untuk 2 dari 5 cabang.
3. **Tidak ada kolom identitas cabang (`kd_cabang`) di tabel manapun, di file cabang manapun** — dikonfirmasi terhadap 10 tabel inti × 5 cabang. Identitas cabang HANYA ditentukan dari file/folder fisik tempat data itu berada. Ini konsekuensi langsung untuk desain tahap "rekey identitas" (Section D.2).
4. **Temuan baru sesi ini — risiko tambahan yang belum tercatat di audit sebelumnya:** sample data kolom `TBLPelanggan`/`TBLSupplier` di cabang PESALAKAN menunjukkan **isi kolom tidak konsisten dengan nama kolomnya** (lihat Section C.1) — field `Fax` berisi teks sumber informasi ("TEMAN/KELUARGA/SAUDARA"), field `KontakPerson` berisi flag validasi tanggal lahir, field `LavelHarga` berisi nilai gender. Ini bukan sekadar data kotor, tapi indikasi field di-reuse ad-hoc oleh operator selama bertahun-tahun tanpa disiplin skema — WAJIB divalidasi per-cabang sebelum mapping kolom final, jangan percaya nama kolom mentah-mentah.
5. **Scope migrasi bukan cuma "data master".** Ada 3 kategori tabel dengan strategi filtrasi berbeda total: Master/Referensi (fuzzy-dedup berat), Transaksi/Historis (rekey + validasi referential integrity), dan Stok/Inventory (masih perlu keputusan snapshot vs histori — lihat Section A2.3).
6. Urutan fase yang direkomendasikan: **Kategori 1 (Master) dulu, khususnya Pelanggan+Kendaraan** (karena sudah paling matang analisanya lewat FSD existing dan seluruh Kategori 2/3 butuh ID master bersih untuk nyambung referensi) → **Kategori 2 (Transaksi)** → **Kategori 3 (Stok)**.

---

## A. Perbandingan Sumber: `fitmotor.mdb` per Cabang vs `fitmotor gabung.mdb`

### A.1 Inventaris File Fisik

| Sumber | Path | Ukuran |
|---|---|---|
| ROOT (disebut "produksi") | `/mnt/e/BENGKEL 2.0/FITMOTOR.MDB` | ~111 MB |
| Cabang PUSAT | `/mnt/e/BENGKEL 2.0/DATABASE PUSAT/FITMOTOR.MDB` | ~17–18 MB (+ 1 backup) |
| Cabang CIKDITIRO | `/mnt/e/BENGKEL 2.0/DATABASE CIKDITIRO/FITMOTOR.MDB` | ~58–61 MB |
| Cabang PACUL | `/mnt/e/BENGKEL 2.0/DATABASE PACUL/FITMOTOR.mdb` | ~115–120 MB |
| Cabang PESALAKAN | `/mnt/e/BENGKEL 2.0/DATABASE PESALAKAN/FITMOTOR.MDB` | ~136–142 MB (terbesar) |
| Cabang TRAYEMAN | `/mnt/e/BENGKEL 2.0/DATABASE TRAYEMAN/FITMOTOR.MDB` | ~25–27 MB (terkecil) |
| GABUNG | `/mnt/e/BENGKEL 2.0/FITMOTOR GABUNG.mdb` (+ 3 backup bertanggal Feb–Apr 2026) | ~1.1 GB |
| APP (variant aplikasi terpisah) | `/mnt/e/BENGKEL 2.0/FITMOTOR APP.mdb` (+ >10 backup bertanggal) | bervariasi |

Catatan: dua kali inspeksi (sesi ini dan sesi sebelumnya) menghasilkan ukuran file sedikit berbeda untuk file yang sama (mis. CIKDITIRO 58.1 MB vs 60.9 MB) — bukan kesalahan pengukuran, tapi karena ada beberapa versi file bertanggal berbeda di folder yang sama (`Backup of ...`, `UPDATE ... 24 APRIL 2026`, dst). **Ini sendiri adalah temuan penting:** sumber Access tidak "beku" — perlu dipastikan file mana yang jadi cut-off resmi sebelum ekstraksi final dijalankan (lihat Open Item OI-1).

### A.2 Jumlah Baris per Tabel Inti — 5 Cabang vs ROOT

| Cabang | Pelanggan | Kendaraan | Service | Mekanik | Supplier | Item | User |
|---|---|---|---|---|---|---|---|
| ROOT | 37.700 | 37.640 | 39.303 | 85 | 203 | 4.231 | 44 |
| PUSAT | 15.035 | 34.623 | **0** | 79 | 171 | 5.859 | 38 |
| CIKDITIRO | 37.941 | 37.886 | 15.158 | 86 | 167 | 4.205 | 35 |
| PACUL | 37.778 | 37.718 | 39.530 | 86 | 203 | 4.246 | 44 |
| PESALAKAN | 36.599 | 36.549 | **48.996** | 87 | 171 | 4.127 | 41 |
| TRAYEMAN | 37.159 | 37.354 | **2.137** | 84 | 163 | 4.060 | 36 |
| **Total 4 cabang aktif (ex-PUSAT)** | ~150.000 | ~150.000 | **~106.000** | — | — | — | — |

Interpretasi:
- **PUSAT punya 0 baris `TBLService`** meski menyimpan 15.035 pelanggan dan 34.623 kendaraan (92% dari seluruh kendaraan sistem) — kemungkinan besar PUSAT berfungsi sebagai pusat administrasi/gudang, bukan bengkel operasional. Perlu konfirmasi bisnis (Open Item OI-2), bukan diasumsikan.
- **Total servis 4 cabang aktif (~106.000) jauh melebihi ROOT (39.303) — 2,7x lipat.** Ini membuktikan ROOT **bukan** hasil konsolidasi lengkap — kemungkinan snapshot/backup parsial yang tidak mencakup histori penuh PESALAKAN dkk.
- Tabel detail turunan (total 4 cabang aktif): `TBLServiceJasaDt` 130.151 baris, `TBLServiceItemDt` 440.243 baris, `TBLService_Advisor` ~69.000 baris — semua nol di PUSAT, konsisten dengan pola di atas.

### A.3 Kolom Identitas Cabang (`kd_cabang`)

Dicek terhadap 10 tabel inti (`TBLService`, `TBLPelanggan`, `TBLKendaraan`, `TBLItem`, `TBLServiceJasaDt`, `TBLServiceItemDt`, `TBLMekanik`, `TBLSupplier`, `TBLPembelianHeader`, `TBLPenjualanHeader`) di **seluruh 5 file cabang**: **tidak ada satupun kolom `kd_cabang` atau sejenisnya.** Satu-satunya tempat kode cabang tersimpan sebagai data adalah tabel `TBLCABANG` di `FITMOTOR APP.mdb` (4 baris: A=PESALAKAN, P=PACUL, C=CIKDITIRO, T=TRAYEMAN — PUSAT tidak terdaftar di sini), yang merupakan database aplikasi terpisah, bukan database transaksi cabang.

**Konsekuensi:** identitas cabang sumber suatu baris HANYA bisa diketahui dari file fisik/folder tempat baris itu diekstrak — begitu 2 file digabung tanpa mencatat asal filenya secara eksplisit di kolom baru, informasi cabang hilang permanen. Ini persis yang terjadi di `FITMOTOR GABUNG.mdb` (lihat A.5).

### A.4 Bukti Langsung: `no_service` Sudah Kedobel Lintas Cabang

Perbandingan langsung `NoService` antar sumber (dari investigasi sesi sebelumnya, dikonfirmasi ulang sebagai fakta yang konsisten dengan struktur skema hasil re-inspeksi sesi ini):

| Pasangan | Overlap NoService |
|---|---|
| ROOT vs PACUL | **39.303 dari 39.303 (100%)** — setiap baris ROOT identik di PACUL |
| ROOT vs CIKDITIRO | 15.136 dari 15.158 (99,9%) |
| ROOT vs TRAYEMAN | 2.136 dari 2.137 (99,95%) |
| ROOT vs PESALAKAN | 31.311 dari 39.303 (79,6%) |

**Bukti tabrakan level baris (smoking gun):** `NoService = SV23000002758` (format: `SV` + tahun 2 digit + sequence 9 digit, di-generate per cabang secara independen tanpa koordinasi):

| Sumber | Tanggal | Pelanggan | Mekanik1 | Total Akhir | User |
|---|---|---|---|---|---|
| ROOT | 2023-07-03 08:50:06 | G 6385 EN | 049 | 130.000 | putri |
| PACUL | 2023-07-03 08:50:06 | G 6385 EN | 049 | 130.000 | putri |
| CIKDITIRO | **2023-11-19 14:07:42** | **G 4316 BFF** | **022** | **100.000** | **caca** |

ROOT dan PACUL identik byte-for-byte (memang record sama, PACUL adalah sumber asal ROOT untuk baris ini). CIKDITIRO memakai `NoService` yang sama persis untuk **transaksi servis lain yang sepenuhnya berbeda**, 4 bulan kemudian, pelanggan berbeda, mekanik berbeda. Field `IDTabel` (`23000002758`) sama di ketiganya — mengonfirmasi `NoService` di-generate dari counter lokal per-cabang (`SV` + tahun + sequence), bukan dari sumber global — begitu sequence lokal 2 cabang kebetulan sama, tabrakan terjadi otomatis.

**Kesimpulan:** hipotesis di brief user (poin 4) **terbukti benar** — pola migrasi lama (gabung nomor per-cabang mentah-mentah, tanpa re-key) adalah akar masalah `no_service` tidak unik lintas cabang yang sekarang sedang diperbaiki di MySQL (30rb+ baris kedobel, lihat memory `project_critical_no_service_not_unique`).

### A.5 Status Dedup di `FITMOTOR GABUNG.mdb`: Gabungan Mentah, Bukan Hasil Cleaning

Bukti dari inspeksi query tersimpan di `FITMOTOR GABUNG.mdb`:

- **225 query tersimpan, hampir semua berstatus `ERR`** (gagal dijalankan) — termasuk query konsolidasi paling kritis: `GABUNG_PELANGGAN`, `GABUNG_PELANGGAN_PERCABANG`, `REKAP_KONSUMEN`, `TIPE_MEMBER`, `UPDATE_TIPE_MEMBER`.
- Query `GABUNG_TBLPELANGGAN` (yang berhasil jalan) memakai `UNION ALL` polos lintas 5-6 sumber cabang — **tanpa deduplikasi sama sekali**.
- Query dedup yang PERNAH dicoba (`GABUNG_PELANGGAN`) **hanya mencakup 2 dari 5 cabang** (PESALAKAN, PACUL), pakai match persamaan `NoPelanggan` yang rapuh — CIKDITIRO, TRAYEMAN, PUSAT tidak pernah ikut proses dedup apapun.
- Konflik yang tidak bisa diselesaikan otomatis (mis. nomor HP beda) ditandai `CEK NO HP` untuk **review manual** — tidak ada proses penyelesaian otomatis lanjutan yang ditemukan.
- Tabel `GABUNG_SERVICE_HEADER_DATA` di GABUNG berisi **103.538 baris, 30 kolom** — angka ini nyaris identik dengan `tblservice` MySQL saat ini (103.546 baris), mengindikasikan kuat bahwa **migrasi MySQL yang sudah berjalan sebelumnya kemungkinan diseed dari GABUNG.mdb (atau turunannya), bukan dari ROOT** — konsisten dengan ditemukannya duplikasi `no_service` 30rb+ di MySQL sekarang.
- Tabel `INSENTIF_JUAL_SERVIS_GABUNG_DATA` (2.297.868 baris) dan `INSENTIF_JUAL_SERVIS_DATA` (1.148.934 baris) tampak sebagai 2 versi kalkulasi komisi yang berbeda dari sumber yang sama — tanpa dokumentasi mana yang otoritatif.

**Kesimpulan A.5:** `FITMOTOR GABUNG.mdb` **tidak bisa dipercaya sebagai sumber utama tanpa perbaikan besar** — ia mewarisi (dan memperbesar skala) persis masalah yang sedang diperbaiki di MySQL sekarang.

---

## A2. Klasifikasi Scope Penuh

Seluruh tabel di sumber Access diklasifikasikan ke 3 kategori dengan strategi filtrasi berbeda (detail strategi di Section D).

### A2.1 Kategori 1 — Data Master / Referensi

Volume kecil, resiko utama duplikat/data sampah (nama mirip, kontak kosong). Sudah dikonfirmasi TERBUKTI kotor lewat FSD_CUSTOMER.md (43+35+22+ baris duplikat nama generik tanpa telepon).

| Tabel Access | Baris (per cabang, kisaran) | Tujuan MySQL |
|---|---|---|
| `TBLPelanggan` | 15.035–37.941 | `tblpelanggan` |
| `TBLKendaraan` | 34.623–37.886 | `tblkendaraan` |
| `TBLItem` | 4.060–5.859 | `tblitem` |
| `TBLMekanik` | 79–87 | `tblmekanik` |
| `TBLSupplier` | 163–203 | `tblsupplier` |
| `TBLUser` | 35–44 | `tbuser_karyawan` (dengan catatan keamanan, lihat C.6) |
| `TBLPelangganGrup` | 3–5 | `master_kategori_member` (HANYA kategori non-loyalitas "Bengkel" — lihat FSD_MEMBERSHIP.md, diskon Gold/Silver Access = 0%, tidak reliable) |
| `TBLCabang` (dari `FITMOTOR APP.mdb`, satu-satunya sumber kode cabang) | 4 | `tbcabang` (sudah ada, cross-check saja) |

### A2.2 Kategori 2 — Data Transaksi / Historis

Volume besar, resiko utama BEDA dari Kategori 1: referential integrity (harus nyambung ke ID master hasil migrasi Kategori 1, bukan ID Access lama) dan tabrakan `no_service` (Section A.4). **WAJIB ikut migrasi**, bukan opsional — `statistik_pelanggan`/`statistik_kendaraan` dan tier membership butuh histori ini sejak hari pertama live, kalau tidak pelanggan lama yang seharusnya Gold akan terhitung Bronze karena riwayatnya kosong.

| Tabel Access | Baris (4 cabang aktif) | Tujuan MySQL |
|---|---|---|
| `TBLService` | ~106.000 (39.530 PACUL + 48.996 PESALAKAN + 15.158 CIKDITIRO + 2.137 TRAYEMAN) | `tblservice` |
| `TBLServiceJasaDt` | 130.151 | `tblservis_jasa` |
| `TBLServiceItemDt` | 440.243 | `tblservis_barang` |
| `TBLService_Advisor` | ~69.000 | (tidak ada padanan langsung — lihat C.7, perlu keputusan) |
| `TBLPembelianHeader`/`TBLPembelianDetail` | belum di-inventaris jumlah barisnya | `tbitem_masuk_header`/`tbitem_masuk_detail` (perlu verifikasi mapping lebih lanjut, di luar scope inventaris kolom tahap ini) |
| `TBLPenjualanHeader`/`TBLPenjualanDetail` | belum di-inventaris jumlah barisnya | modul penjualan (di luar scope FSD Customer/Kendaraan/Membership yang jadi rujukan utama dokumen ini) |
| `TBLReturBeliHeader/Detail`, `TBLReturJualHeader/Detail` | belum di-inventaris | modul retur |

Catatan: inventaris baris untuk tabel pembelian/penjualan/retur belum lengkap di tahap ini (fokus riset sesi ini adalah pelanggan/kendaraan/servis sesuai FSD yang sudah ada) — perlu 1 putaran inspeksi tambahan sebelum fase eksekusi Kategori 2 dimulai (Open Item OI-3).

### A2.3 Kategori 3 — Data Stok / Inventory

Belum bisa diputuskan snapshot vs histori pergerakan — berikut trade-off berdasar apa yang tersedia di sumber:

**Yang tersedia di Access:**
- `TBLItem.Inv_JmlAwal`, `Inv_HrgAwal`, `Inv_TglAwal`, `Inv_IdAwal` — ini adalah **saldo awal per item** (snapshot titik waktu tertentu), bukan histori pergerakan penuh.
- `TBLItemFifo`, `TBLPenjualanDtFifo`, `TBLServiceItemDtFifo` — tabel FIFO terpisah yang mengonfirmasi pergerakan stok **memang dilacak di level transaksi** (tiap baris jual/servis yang memotong stok tercatat), tapi terpisah dari tabel utama, dan skema detailnya belum di-dump di tahap ini.
- Tabel MySQL saat ini sudah punya `tbstok` (log gerakan: `tipe`, `no_transaksi`, `no_item`, `masuk`, `keluar`, `kd_cabang`), `tbitem_masuk_header/detail`, `tbitem_keluar_header/detail`, `tbkoreksi_stok_header/detail` — desain MySQL SUDAH mengasumsikan model pergerakan (ledger), bukan snapshot murni.

**Trade-off:**
| Opsi | Kelebihan | Kekurangan |
|---|---|---|
| Migrasi snapshot saldo terakhir saja (`Inv_JmlAwal` per item per cabang) | Cepat, volume kecil (~4-6rb baris per cabang) | Kartu stok (`tbstok`) tidak punya histori sebelum go-live — laporan stok masuk/keluar historis Access hilang; tidak bisa audit selisih stok lama |
| Migrasi histori pergerakan penuh dari tabel FIFO | Konsisten dengan model `tbstok` MySQL yang sudah ledger-based; audit trail utuh | Volume besar (turunan dari 440.243 baris `TBLServiceItemDt` + histori penjualan/pembelian yang belum dihitung) — beresiko tinggi kalau di-generate ulang dari Kategori 2 tanpa rekonsiliasi saldo akhir dulu |

**Rekomendasi tahap ini (perlu keputusan Anda, bukan diputuskan sepihak di sini):** migrasi 2 lapis — (1) snapshot saldo per item per cabang sebagai **saldo awal ledger MySQL** (baris pertama `tbstok` per item, `keterangan='SALDO_MIGRASI'`), lalu (2) histori pergerakan HANYA direkonstruksi dari data Kategori 2 yang sudah lolos filter (servis/penjualan yang valid), BUKAN diimpor mentah dari tabel FIFO Access — supaya `tbstok` MySQL konsisten dengan `tblservis_barang`/`tblitem_masuk_detail` yang sudah direkey. Ini menghindari duplikasi ledger (pergerakan tercatat 2x: sekali dari FIFO Access, sekali dari hasil migrasi servis).

---

## B. Rekomendasi Sumber Migrasi

**Rekomendasi: migrasi dari 5 file `FITMOTOR.MDB` per cabang, digabung ulang dengan proses baru yang benar. BUKAN dari `FITMOTOR GABUNG.mdb`.**

Alasan eksplisit berdasar temuan Section A (bukan asumsi):

1. **`FITMOTOR GABUNG.mdb` sudah mewarisi masalah yang sama persis dengan yang sedang diperbaiki** (A.4, A.5) — memakainya sebagai sumber tunggal berarti mengimpor ulang bug lama ke MySQL, bukan memperbaikinya.
2. **`FITMOTOR GABUNG.mdb` tidak pernah melalui dedup yang lengkap** (A.5) — 3 dari 5 cabang pelanggan tidak pernah tersentuh proses dedup apapun, dan mayoritas query konsolidasinya rusak (`ERR`).
3. **File per cabang MASIH punya kolom identitas cabang yang implisit tapi tegas** (nama folder/file = cabang), sedangkan begitu masuk GABUNG, jejak itu sudah lebur ke dalam `UNION ALL` tanpa kolom eksplisit yang tercatat konsisten.
4. **Proses re-key & dedup yang baru bisa dikontrol penuh dari awal** hanya kalau dimulai dari granularitas per-cabang — begitu mulai dari GABUNG, kita tidak bisa lagi membedakan mana baris hasil UNION mentah vs mana yang "kebetulan" sama karena memang record yang sama (seperti kasus ROOT=PACUL di A.4).
5. Trade-off yang disadari: mengambil dari 5 sumber berarti effort ekstraksi 5x lebih banyak dan HARUS menangani overlap ROOT/PACUL/CIKDITIRO/TRAYEMAN secara eksplisit (bukan dianggap "5 sumber independen" — overlap 79.6–100% pada `no_service` menandakan ROOT kemungkinan adalah snapshot gabungan parsial, bukan cabang ke-6 yang independen). **ROOT tidak dipakai sebagai sumber tambahan** — cukup jadi cross-check/validasi konsistensi terhadap hasil gabung baru dari 5 cabang (PUSAT+CIKDITIRO+PACUL+PESALAKAN+TRAYEMAN).

---

## C. Inventaris & Column Mapping (Kategori 1 — Master)

Sumber sample: `DATABASE PESALAKAN/FITMOTOR.MDB` (cabang dengan volume servis tertinggi, representatif). Sample data di bawah adalah nilai baris pertama tiap kolom hasil parsing `access_parser` — dipakai untuk verifikasi tipe isi kolom, BUKAN representasi keseluruhan data.

### C.1 Pelanggan — `TBLPelanggan` → `tblpelanggan`

⚠️ **TEMUAN KRITIS — isi kolom tidak konsisten dengan nama kolom.** Sample baris pertama PESALAKAN:

| Kolom Access | Sample Nilai | Kecurigaan |
|---|---|---|
| `Fax` | `"TEMAN/KELUARGA/SAUDARA"` | Berisi sumber informasi pelanggan, bukan nomor fax |
| `KontakPerson` | `"TIDAK VALID-2/1/1999"` | Berisi flag validasi + tanggal, bukan nama kontak |
| `LavelHarga` | `"PEREMPUAN"` | Berisi gender, bukan level harga |
| `TipePot` | `"C"` | Konsisten dengan tipe potongan (wajar) |

Ini mengindikasikan operator selama bertahun-tahun **menggunakan ulang field kosong yang tidak dipakai secara harfiah** (Fax, KontakPerson, LavelHarga) untuk mencatat data lain yang sebenarnya dibutuhkan tapi tidak punya kolom resminya (sumber referral, validasi data, gender). **Wajib** divalidasi ulang per cabang sebelum ETL final — pola reuse bisa berbeda antar cabang/periode (lihat Open Item OI-4). Jangan mapping otomatis berdasar nama kolom Access mentah-mentah.

| Kolom Access | Tipe (Access) | Target MySQL (`tblpelanggan`) | Catatan |
|---|---|---|---|
| `NoPelanggan` | text | `nopelanggan` (PK) | Perlu cek unik lintas cabang sebelum reuse sebagai PK (pola sama seperti `no_service`, lihat D.2) |
| `NamaPelanggan` | text | `namapelanggan` | |
| `Alamat` | text | `alamat` | |
| `Kota` | text | `kota` | |
| `Propinsi` | text | `propinsi` | Sering kosong di sample |
| `KodePost` | text | `kodepost` | Sering kosong |
| `Negara` | text | `negara` | Sering kosong |
| `Telephone` | text | `telephone` | |
| `Fax` | text | **TIDAK ADA PADANAN** — isi sebenarnya "sumber informasi" → kandidat `informasi_sumber` (kolom baru MySQL) SETELAH validasi isi per baris | Lihat peringatan di atas |
| `KontakPerson` | text | **TIDAK ADA PADANAN LANGSUNG** — isi sebenarnya flag validasi tanggal lahir → kandidat `valid_tgl_lahir` (kolom baru MySQL) SETELAH validasi isi per baris | Lihat peringatan di atas |
| `Note` | text | `note` | |
| `Potongan` | numeric | `potongan` | |
| `TipePot` | text(1) | `tipepot` | |
| `LavelHarga` | text | **TIDAK ADA PADANAN LANGSUNG** — isi sebenarnya gender → kandidat `gender` (kolom baru MySQL) SETELAH validasi isi per baris | Lihat peringatan di atas |
| `KGrup` | text(3) | `kgrup` | HANYA dipakai kategori non-loyalitas (FSD_MEMBERSHIP.md BR-MBR-05) |
| — (tidak ada di Access) | — | `no_wa` | Kolom baru MySQL, tidak ada sumbernya di Access — Access hanya punya `Telephone` tunggal. Perlu keputusan: `no_wa` diisi sama dengan `Telephone` saat migrasi, atau dibiarkan kosong sampai dikonfirmasi manual (FSD_CUSTOMER FR-02 mewajibkan minimal salah satu no_wa/notlp valid) |
| — | — | `notlp`, `klat`, `klong`, `panggilan`, `saldoawal`, `pertanggal`, `tgllahir`, `id_panggilan`, `bl_pajak`, `th_pajak`, `merek_id`, `tipe_id`, `jenis_id`, `warna_id`, `foto_tampak_rumah`, `link_gmaps` | Kolom baru MySQL tanpa sumber Access — perlu default/derivasi/manual (lihat OI-5) |

### C.2 Kendaraan — `TBLKendaraan` → `tblkendaraan`

| Kolom Access | Sample Nilai | Target MySQL | Catatan |
|---|---|---|---|
| `NoPolisi` | `" E 6802 OW"` | `nopolisi` (PK existing) | **Perhatikan leading space** di sample — butuh trim/normalisasi wajib saat ekstraksi, bukan asumsi data sudah bersih |
| `Pemilik` | `"YUNUS,MAS"` | `pemilik` | Tetap field teks bebas di MySQL (FSD_KENDARAAN.md: bukan FK) — TAPI wajib dipakai sebagai sinyal awal untuk cocokkan ke `nopelanggan` lewat `kepemilikan_kendaraan` saat migrasi (lihat D.2) |
| `Alamat` | text | `alamat` | |
| `Merek` | `"HONDA"` | tidak ada kolom `merek` teks langsung di skema saat ini — MySQL pakai `kode_merek` (FK numerik) | Perlu tabel lookup merek untuk resolve teks → kode |
| `Tipe` | `"ADV-150"` | `tipe` | |
| `Jenis` | `"FI"` | `jenis` | |
| `TahunBuat` | `"08-2026"` | `tahun_buat` | Format tanggal aneh (bulan-tahun bukan tahun murni) — butuh normalisasi |
| `TahunRakit` | text | `tahun_rakit` | |
| `Silinder` | text | `silinder` | |
| `Warna` | `"HITAM "` | `warna` | Trailing space di sample — normalisasi wajib |
| `NoRangka` | text | `no_rangka` | Sering kosong di sample — FSD_KENDARAAN O1 usul jadi unique index sekunder untuk deteksi duplikat, tapi kalau banyak kosong di data riil, keandalannya sebagai unique index perlu divalidasi dulu |
| `NoMesin` | text | `no_mesin` | |
| `Note` | text | `note` | |
| — | — | `id_kendaraan` (surrogate, FSD_KENDARAAN.md) | Tidak ada di Access — di-generate saat migrasi (lihat D.2, contoh pola sudah ada di FSD_KENDARAAN) |
| — | — | `kode_warna`, `kode_tipe`, `kode_jenis` | Kolom baru MySQL (FK numerik) tanpa sumber teks langsung — perlu tabel lookup + resolusi |

### C.3 Item/Barang — `TBLItem` → `tblitem`

33 kolom Access, mapping langsung 1:1 cukup rapi (tidak ada indikasi reuse kolom seperti Pelanggan):

| Kolom Access | Target MySQL | Catatan |
|---|---|---|
| `NoItem`, `KodeBarCode`, `NamaItem`, `Jenis`, `Satuan` | `noitem`, `kodebarcode`, `namaitem`, `jenis`, `satuan` | Sample `NoItem='--'`, `NamaItem='-'` menunjukkan ada baris placeholder/rusak di data produksi — wajib difilter (bukan item asli) |
| `HargaPokok`, `HargaJual`, `HargaJual2`, `HargaJual3`, `HJQtyD2/D3/S1/S2` | `hargapokok`, `hargajual`, `hargajual2`, `hargajual3`, `hjqtyd2/d3/s1/s2` | Langsung sepadan |
| `Quantity`, `StokMin` | — | **TIDAK dimigrasi langsung sebagai kolom statis** — lihat Section A2.3, jadi input saldo awal ledger `tbstok`, bukan field snapshot di `tblitem` |
| `StatusItem`, `StatusProduk` | `statusitem`, `statusproduk` | |
| `Supplier`, `Supplier2`, `Supplier3` | `supplier`, `supplier2`, `supplier3` | FK teks ke `NoSupplier` — perlu resolve ke `nosupplier` MySQL hasil migrasi Supplier (rekey mungkin diperlukan, lihat D.2) |
| `RakBarang` | `rakbarang` | Sample `'NOSTOK'` — nilai placeholder, bukan lokasi rak asli |
| `JasaWaktu`, `JasaSatuanWaktu`, `JenisKomisi`, `KomisiProsen`, `KomisiNominal` | `jasawaktu`, `jasasatuanwaktu`, `jeniskomisi`, `komisiprosen`, `komisinominal` | |
| `Inv_IdAwal`, `Inv_JmlAwal`, `Inv_HrgAwal`, `Inv_TglAwal` | — | Sumber saldo awal ledger stok (Section A2.3), bukan field `tblitem` |
| — | `tipe_item` (ORI/NON_ORI), `merek`, `kode_part_resmi`, `nama_part_resmi`, `penggunaan_motor`, `merek_tipe`, `kategori_rak`, `status_validasi`, `created_by`, `validated_by` | Kolom baru MySQL, tidak ada sumber Access — semua item hasil migrasi otomatis dapat `status_validasi='pending_validation'`, wajib direview manual sebelum dipakai transaksi (konsisten dengan pola Kategori 1) |

### C.4 Mekanik — `TBLMekanik` → `tblmekanik`

⚠️ **Gap teknis:** parsing `TBLMekanik` di PESALAKAN menghasilkan seluruh nilai kolom `################` (termasuk `NoMekanik`, `Nama`) — indikasi field disimpan dalam tipe data yang tidak didukung penuh oleh `access_parser` (kemungkinan OLE/binary/rich-text field, bukan enkripsi sungguhan). **Perlu ekstraksi ulang lewat jalur lain** (Access GUI export ke CSV, atau DAO/COM automation di Windows) sebelum kolom mekanik bisa di-mapping — nilai `################` di 8 kolom tidak bisa dipakai sebagai dasar mapping final di tahap analisa ini (Open Item OI-6).

| Kolom Access (nama saja, isi belum terverifikasi) | Target MySQL (`tblmekanik`) |
|---|---|
| `NoMekanik` | `nomekanik` |
| `Nama` | `nama` |
| `Alamat` | `alamat` |
| `Kota`, `Provinsi` | — tidak ada kolom kota/provinsi terpisah di `tblmekanik` MySQL saat ini, kemungkinan gabung ke `alamat` teks |
| `NoTelepon` | `telp` |
| `Note` | — tidak ada padanan di `tblmekanik` |
| `Keahlian` | `keahlian` |

Perlu diperhatikan juga: MySQL punya 2 tabel yang tumpang tindih secara konsep — `tblmekanik` (legacy) dan `tbuser_karyawan` (lebih baru, dengan `kode_posisi`, `kode_level`, `kode_cabang`). Perlu keputusan eksplisit: mekanik hasil migrasi masuk ke `tblmekanik` saja, `tbuser_karyawan` saja, atau keduanya dengan link eksplisit (Open Item OI-7) — di luar scope analisa Access murni, ini keputusan arsitektur skema tujuan.

### C.5 Supplier — `TBLSupplier` → `tblsupplier`

Mapping bersih, 1:1 hampir seluruh kolom:

| Kolom Access | Target MySQL | Catatan |
|---|---|---|
| `NoSupplier`, `NamaSupplier` | `nosupplier`, `namasupplier` | |
| `Alamat`, `Kota`, `Propinsi`, `KodePost`, `Negara` | sama | Sample `KodePost='3'`, `Negara='1'` — kemungkinan field ini juga di-reuse sebagai kode numerik pendek, bukan nilai kode pos/negara asli. Perlu validasi sama seperti C.1 |
| `Telephone`, `Fax` | `telephone`, `fax` | |
| `NamaBank`, `NoAccount`, `AtasNama` | sama | |
| `KontakPerson`, `Email`, `Note` | sama | |
| `SaldoAwal`, `PerTanggal`, `JmlBayar`, `Sisa` | sama | |
| — | `tipe_pemasok`, `no_whatsapp`, `kd_cabang`, `lama_hari_kirim`, `jangka_waktu_kredit`, `accurate_id` | Kolom baru MySQL tanpa sumber Access |

### C.6 User — `TBLUser` → `tbuser_karyawan`

⚠️ **Temuan keamanan — bukan cuma soal mapping kolom.** `TBLUser` menyimpan **password dalam bentuk plaintext** (dikonfirmasi lewat sample, nilai tidak dikutip di dokumen ini karena sensitif). Ini WAJIB ditandai sebagai kebijakan migrasi, bukan cuma catatan kolom:

- Password **TIDAK BOLEH** dipindah apa adanya (plaintext) ke kolom password manapun di MySQL.
- Rekomendasi: migrasi hanya `UserID`/`NamaUser`/`HakAkses` sebagai referensi identitas, seluruh akun hasil migrasi WAJIB di-force reset password saat login pertama di sistem baru (atau tidak diaktifkan otomatis sama sekali — akun baru dibuat manual oleh admin, `TBLUser` lama hanya jadi referensi audit "siapa saja user lama").
- Ini konsisten dengan memory project soal larangan default password/secret hardcoded hasil migrasi.

| Kolom Access | Sample | Target |
|---|---|---|
| `UserID` | `admin` (contoh role, bukan identitas personal) | referensi ke `tbuser_karyawan.kode_karyawan` (bukan tabel auth) |
| `NamaUser` | administrator | `nama_lengkap` (referensi saja) |
| `Password` | (plaintext — TIDAK dimigrasi) | TIDAK dimigrasi |
| `HakAkses` | Admin | referensi role lama untuk mapping manual ke RBAC MySQL, BUKAN migrasi otomatis (RBAC MySQL sudah pakai pola permission berbeda) |

### C.7 Yang Belum Punya Padanan Jelas — Wajib Keputusan

| Item Access | Masalah | Rekomendasi Sementara |
|---|---|---|
| `TBLPelanggan.Fax/KontakPerson/LavelHarga` (isi bukan sesuai nama) | Perlu keputusan bisnis: field baru apa yang mau dipertahankan datanya | Lihat OI-4 |
| `TBLService_Advisor` (~69.000 baris, no_service + ID advisor) | Tidak ada tabel padanan eksplisit yang teridentifikasi di skema MySQL sample yang diperiksa | Perlu cross-check ke skema komisi/advisor terbaru (`siklus_komisi`, FSD_MEMBERSHIP.md) — di luar scope pemeriksaan skema tahap ini, tandai untuk sesi lanjutan |
| `TBLMekanik` isi terenkripsi/tidak terbaca | Blocker teknis, bukan keputusan bisnis | OI-6 — perlu ekstraksi ulang lewat jalur lain sebelum lanjut mapping final |
| `TBLPelangganGrup` Gold/Silver 0% diskon | Data sendiri tidak reliable (FSD_MEMBERSHIP.md sudah bahas ini) | Jangan migrasi nilai diskon Gold/Silver Access apa adanya — sudah diputuskan FSD_MEMBERSHIP.md BR-MBR-05 pakai `master_kategori_member` sebagai basis baru |

---

## D. Desain Pipeline Filtrasi + Approval

Alur umum 6 tahap (bukan kode — desain proses):

### D.1 Tahap 1 — Extract ke Staging

Dump 1:1 apa adanya dari tiap sumber Access (5 file cabang, per Section B) ke tabel staging MySQL terpisah, **immutable** (tidak pernah di-UPDATE setelah insert, hanya insert-once per file per tanggal ekstraksi). Struktur staging: 1 tabel staging per tabel Access, ditambah 3 kolom wajib di semua staging: `_source_file` (path/nama file asal), `_source_branch` (kode cabang, diturunkan dari nama folder — lihat A.3, karena Access sendiri tidak punya kolom ini), `_extracted_at`. Staging TIDAK terhubung ke tabel produksi manapun — murni audit trail bukti "apa yang ada di Access sebelum diproses".

### D.2 Tahap 2 — Rekey Identitas

Semua PK/nomor yang **tidak dijamin unik lintas cabang** (bukti: `no_service` di A.4; kemungkinan sama untuk `nopelanggan`/`nopolisi` — belum dibuktikan tapi pola arsitekturnya identik, jadi WAJIB dicek dengan metode yang sama sebelum diasumsikan aman) di-generate ulang jadi surrogate key baru, **bukan reuse nomor lama Access mentah-mentah sebagai PK MySQL**. Pola yang sudah ada di proyek ini sebagai preseden: `id_kendaraan` (surrogate int, FSD_KENDARAAN.md Section 5.1) — nomor Access lama (`nopolisi`/`no_service`) tetap disimpan sebagai atribut historis/referensi tampilan, tapi bukan lagi kunci penghubung utama antar tabel hasil migrasi.

Untuk `nopelanggan` dan `no_service` spesifik: karena `tblpelanggan`/`tblservice` MySQL SUDAH punya data live dengan format lama (`nopelanggan` varchar, `no_service` `SVxxxxx...`), rekey Kategori 1/2 hasil migrasi Access **tidak mengubah format existing MySQL** — mengikuti keputusan "Identity Layer Strategy" yang sudah dipakai untuk kasus serupa (id_customer/id_vehicle sebagai layer identitas tambahan, bukan penggantian PK). Untuk baris yang berasal dari Access dan collision dengan `no_service`/`nopelanggan` yang sudah ada di MySQL (mis. karena PACUL dan CIKDITIRO sama-sama pakai `SV23000002758` untuk 2 transaksi beda), digenerate `no_service` baru dengan skema unik-per-cabang eksplisit (mis. sisipkan kode cabang di prefix) sebelum insert ke staging tahap berikutnya — collision di-resolve di tahap ini, bukan dibawa ke produksi.

### D.3 Tahap 3 — Filter Kualitas Data Otomatis (Kategori 1)

Reuse pendekatan fuzzy-dedup yang sudah direncanakan di FSD_CUSTOMER.md FR-04: Levenshtein/SOUNDEX terhadap kombinasi nama+telepon, threshold 80-90% (dikalibrasi ulang terhadap kasus dunia nyata yang sudah diketahui — kasus "SUGENG, BPK" di FSD_CUSTOMER.md, dan sekarang bisa diperluas dengan populasi lebih besar karena sumbernya 5 cabang bukan cuma data MySQL saat ini). Validasi wajib per entity mengikuti FSD masing-masing:
- Pelanggan: FSD_CUSTOMER.md FR-02 — minimal salah satu `no_wa`/`notlp` harus valid (baris tanpa kontak valid otomatis masuk karantina, bukan lolos).
- Kendaraan: `nopolisi` normalisasi (trim spasi — lihat C.2 temuan leading/trailing space), cek `nopolisi` aktif tidak dobel (BR-KEND-05 FSD_KENDARAAN.md).
- Item: baris placeholder (`NoItem='--'`, `NamaItem='-'` — lihat C.3) difilter otomatis sebagai gagal-kualitas, bukan masuk karantina (jelas bukan item asli, bukan kasus ambigu).

### D.4 Tahap 4 — Filter Validasi Referential Integrity (Kategori 2)

Untuk data transaksi/historis: validasi bahwa `no_pelanggan`/`no_polisi` pada tiap baris `TBLService` (dan turunannya) berhasil di-resolve ke ID master hasil Tahap 3 yang SUDAH lolos (bukan ID Access mentah). Baris yang gagal resolve (pelanggan/kendaraan sumbernya sendiri gagal kualitas atau tidak ditemukan di master hasil dedup) masuk karantina — bukan didrop otomatis, karena bisa jadi masalahnya di master (butuh perbaikan match), bukan di transaksinya sendiri. `no_service` di-rekey sesuai D.2 sebelum insert.

### D.5 Tahap 5 — Karantina, Bukan Buang Otomatis

Semua baris yang gagal filter (Tahap 3 atau 4) masuk tabel antrian review manual (`migrasi_karantina` — nama indikatif, desain skema detail di luar scope dokumen analisa ini). **Tidak** di-drop diam-diam, **tidak** otomatis masuk produksi. Beda perlakuan approval per kategori (lihat D.6).

### D.6 Tahap 6 — Approval Gate

Pola mengikuti `customer_merge_log` yang sudah ada (FSD_CUSTOMER.md Section 5.2): propose → review → approve → eksekusi transaksional dengan snapshot untuk rollback.

- **Kategori 1 (Master):** approval **berat** — tiap baris karantina (dan idealnya sample dari yang lolos otomatis juga, untuk quality-check) direview manual oleh role setingkat Supervisor/Owner (sama seperti FSD_CUSTOMER.md BR-CUST-03), karena ini keputusan "siapa identitasnya" yang salah putus bisa merusak data selamanya.
- **Kategori 2 (Transaksi):** approval **lebih ringan / spot-check** — datanya fakta historis (bukan identitas yang perlu "diputuskan benar/salahnya"), fokus approval di sini adalah konfirmasi agregat (jumlah baris per cabang sesuai ekspektasi Section A.2, tidak ada anomali referential integrity massal) bukan review baris-per-baris.
- **Kategori 3 (Stok):** tergantung keputusan snapshot vs histori (A2.3) — kalau snapshot saja, approval ringan (cek total nilai stok awal masuk akal dibanding laporan Access terakhir); kalau histori direkonstruksi dari Kategori 2, approval-nya menyatu dengan approval Kategori 2.

Eksekusi approved data ke tabel produksi WAJIB 1 transaction SQL dengan snapshot before-state (pola sama seperti FSD_CUSTOMER.md FR-05 langkah 3), supaya rollback per-batch tetap mungkin.

### D.7 Tahap 7 — Verifikasi Pasca-Migrasi

- Rekonsiliasi jumlah baris: staging vs produksi (selisih = jumlah karantina + jumlah gagal-kualitas, harus balance, tidak boleh ada baris "hilang" tanpa jejak).
- Spot-check sample record per cabang per tabel (bandingkan nilai kunci — nama, total transaksi — terhadap laporan Access asli/Crystal Report kalau tersedia, seperti pola `FITMOTOR_CRYSTAL_ACCESS_MYSQL_AUDIT.md` yang sudah ada).
- Laporan selisih eksplisit per kategori, disampaikan sebelum fase berikutnya dibuka (Kategori 2 tidak dimulai sebelum Kategori 1 selesai verifikasi, dst — lihat Section E).

---

## E. Tahapan (Fase)

Urutan fase, bukan asal urut — tiap fase punya alasan dependency eksplisit:

### Fase 0 — Persiapan (sebelum Fase 1 dimulai)
- Selesaikan Open Items OI-1 s.d. OI-7 (Section F) yang berstatus blocker teknis atau butuh keputusan bisnis eksplisit.
- Selesaikan ekstraksi ulang `TBLMekanik` lewat jalur non-`access_parser` (OI-6) — tanpa ini, Kategori 1 tidak bisa lengkap.
- Tetapkan file cut-off resmi per cabang (OI-1) — hentikan penggunaan file yang dipakai sebagai sumber migrasi supaya tidak ada data baru masuk selama proses berjalan.

### Fase 1 — Kategori 1: Pelanggan + Kendaraan
**Alasan diutamakan:** sudah paling matang analisanya (FSD_CUSTOMER.md dan FSD_KENDARAAN.md sudah lengkap, termasuk skema tabel baru `pelanggan_kontak_history`, `kepemilikan_kendaraan`, dst), dan SELURUH Kategori 2 & 3 butuh ID pelanggan/kendaraan yang sudah bersih untuk nyambung referensi (`no_pelanggan`/`no_polisi` di `TBLService`). Kalau Kategori 2 dimulai duluan, transaksi akan nyambung ke ID Access mentah yang belum tentu selamat dari dedup — harus di-rewire ulang, kerja dobel.

### Fase 2 — Kategori 1: Item/Barang
**Alasan setelah Pelanggan+Kendaraan, sebelum Mekanik/Supplier:** volume lebih besar (4-6rb per cabang vs mekanik puluhan/supplier ratusan), dan `TBLServiceItemDt` (Kategori 2, 440.243 baris — tabel terbesar Kategori 2) butuh `no_item` yang sudah bersih. Placeholder item (`NoItem='--'`) harus sudah difilter sebelum servis/penjualan Kategori 2 diproses, supaya tidak ada transaksi nyambung ke item sampah.

### Fase 3 — Kategori 1: Mekanik + Supplier + User
**Alasan setelah Item:** volume kecil (puluhan-ratusan baris), dampak terhadap Kategori 2 lebih sempit (hanya kolom `Mekanik1-4`/`Supplier1-3` di `TBLService`/`TBLItem`, bukan tabel volume besar). Mekanik menunggu Fase 0 selesai (blocker teknis OI-6).

### Fase 4 — Kategori 2: Servis (Header + Jasa + Barang + Advisor)
**Alasan setelah seluruh Kategori 1 selesai & terverifikasi:** butuh `nopelanggan`/`nopolisi`/`noitem`/`nomekanik` yang sudah lolos dedup dari Fase 1-3. Ini juga fase yang menyelesaikan langsung akar masalah `no_service` tidak unik (D.2) — prioritas tinggi karena terkait bug aktif yang sedang diperbaiki di MySQL.

### Fase 5 — Kategori 2: Pembelian, Penjualan, Retur
**Alasan setelah Servis:** volume dan kompleksitas mapping belum sepenuhnya diinventarisasi di tahap analisa ini (Open Item OI-3) — perlu 1 putaran inspeksi kolom tambahan sebelum fase ini bisa didetailkan sama seperti Fase 4. Ditunda bukan karena tidak penting, tapi karena dependency riset belum lengkap.

### Fase 6 — Kategori 3: Stok/Inventory
**Alasan terakhir:** bergantung pada keputusan snapshot vs histori (A2.3, perlu keputusan Anda), DAN kalau opsi histori-dari-Kategori-2 dipilih, secara teknis bergantung penuh pada Fase 4-5 selesai lebih dulu.

---

## F. Open Items — Butuh Keputusan/Kerja Lanjutan Sebelum Eksekusi

| # | Item | Kategori | Kenapa Penting |
|---|---|---|---|
| OI-1 | File `.mdb` mana per cabang yang jadi cut-off resmi migrasi (ada banyak versi bertanggal di folder yang sama) | Blocker teknis | Tanpa cut-off jelas, ekstraksi bisa dijalankan terhadap versi data yang salah |
| OI-2 | Konfirmasi bisnis: apakah PUSAT memang gudang/admin (0 servis) atau ada data servis PUSAT yang hilang/tidak sinkron | Keputusan bisnis | Menentukan apakah PUSAT diperlakukan sebagai cabang operasional penuh atau kategori khusus di Fase 1+ |
| OI-3 | Inventaris kolom lengkap untuk `TBLPembelian*`, `TBLPenjualan*`, `TBLRetur*` (belum di-dump di sesi ini) | Riset lanjutan | Prasyarat detail Fase 5 |
| OI-4 | Keputusan field mana dari `Fax`/`KontakPerson`/`LavelHarga` (isi ternyata sumber info/validasi/gender) yang datanya mau dipertahankan, dan tujuannya kolom MySQL yang mana | Keputusan bisnis | Menentukan mapping final C.1, berlaku juga cek serupa untuk `TBLSupplier.KodePost`/`Negara` (C.5) |
| OI-5 | Kolom baru MySQL tanpa sumber Access (`no_wa` terpisah dari `telephone`, `gender`, `valid_tgl_lahir`, `kode_merek`/`kode_tipe`/`kode_jenis`/`kode_warna` numerik, dst) — default/derivasi/manual? | Keputusan bisnis | Menentukan apakah baris hasil migrasi punya field ini kosong (butuh pengisian manual bertahap) atau diderivasi otomatis dari field lain |
| OI-6 | `TBLMekanik` tidak terbaca (nilai placeholder pengganti) lewat `access_parser` — perlu ekstraksi ulang lewat Access GUI export atau DAO/COM automation | Blocker teknis | Fase 0/3 tidak bisa selesai tanpa ini |
| OI-7 | Mekanik hasil migrasi masuk `tblmekanik`, `tbuser_karyawan`, atau keduanya dengan link eksplisit? | Keputusan arsitektur | Menentukan target akhir mapping C.4 |
| OI-8 | `nopelanggan`/`no_polisi` — apakah juga sudah kedobel lintas cabang seperti `no_service` (baru dibuktikan untuk `no_service`, pola arsitekturnya sama tapi belum dicek langsung) | Riset lanjutan, prioritas tinggi | Kalau ya, D.2 (rekey) juga wajib berlaku untuk `nopelanggan`/`nopolisi`, bukan cuma `no_service` — perlu dicek SEBELUM Fase 1 dimulai, bukan diasumsikan aman karena belum pernah dilaporkan sebagai bug (`no_service` juga baru ketahuan setelah audit spesifik) |
| OI-9 | Skema lengkap `TBLService_Advisor` dan padanannya di MySQL (siklus_komisi/advisor) belum ditelusuri tuntas | Riset lanjutan | Prasyarat detail mapping C.7 |

**Rekomendasi prioritas:** OI-8 (cek `nopelanggan`/`nopolisi` dobel lintas cabang) sebaiknya dikerjakan lebih dulu dari semua item lain — kalau ternyata ID pelanggan/kendaraan juga kedobel seperti `no_service`, itu mengubah total desain Fase 1 (D.2 rekey harus diperluas), jadi harus diketahui SEBELUM Fase 1 dimulai, bukan ditemukan di tengah jalan.
