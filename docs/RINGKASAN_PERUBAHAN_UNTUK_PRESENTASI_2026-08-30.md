# RINGKASAN PERUBAHAN — Bahan Presentasi

**Tanggal disusun:** 30 Agustus 2026
**Cakupan:** SELURUH kerjaan dari checkpoint dokumen meeting 28 Juni 2026 sampai sekarang — 46 commit di `main` + 16 commit di branch `fix/servis-garansi-wo-fraud-validation` (belum merge) = **62 commit, ~2 bulan kerja**.
**Cara pakai:** BAGIAN 0 = perjalanan lengkap kronologis (buat gambaran besar). BAGIAN 1 = detail batch terbaru (16 commit terakhir). BAGIAN 2-4 = cross-check keputusan/requirement vs kode. Tinggal dibaca urut buat presentasi.

---

## BAGIAN 0 — PERJALANAN LENGKAP (28 Juni → 30 Agustus 2026)

### Fase 1 — Implementasi Langsung dari Keputusan Meeting 28 Juni (29 Jun–3 Jul)
Ini eksekusi langsung dari 10 keputusan di `PANDUAN MEETING WEB BENGKEL v3.docx` (lihat Bagian 2).

| Commit | Perubahan | Halaman |
|---|---|---|
| `dd1dfcb` | Redesign layout kasir jadi 3 kolom — servis reguler, jemput, garansi dipisah jelas | Halaman input servis (kasir) |
| `bad8174` | Implementasi Fase 1 (F1-A s/d F1-E) dari planning meeting | Multi-halaman servis |
| `b30bd6d` | Part yang dibawa customer sendiri dipindah dari tab Jasa ke tab Suku Cadang (biar gak ketuker akuntansinya) | Tab input servis |
| `2ad6e01`, `09dd458` | Slider persentase staff servis + auto-fill "Admin 1" dari user yang login (gak perlu pilih manual) | Form input servis |
| `f301cab` | Master Work Order bisa dikombinasi + opsi "gratis per item" (dukung kasus servis garansi sebagian item) | `paket.php` / Master WO |
| `ac58a62` | Popup slider persentase sekarang lompat kelipatan 10 saat diklik (UX) | Form persentase staff |

### Fase 4 — Pengadaan (18 Juli)
| Commit | Perubahan | Halaman |
|---|---|---|
| `b11f51d` | SQL injection fix + approval bertingkat PO (Purchase Order gak bisa lolos tanpa approval sesuai nilai) + alarm harga beli | Modul Pengadaan (PR→PO→DO) |
| `da9ad18` | Bug kritis ketemu pas testing browser fase 4, langsung di-fix | — |
| `6f52f9a` | Perbaikan alur pengadaan + kalkulasi min-max stok | Rencana order/procurement |

### Promo Engine — Task 4 (18-19 Juli)
| Commit | Perubahan | Halaman |
|---|---|---|
| `14b24c0` | Bangun Promo Engine: multi-target, multi-cabang, syarat kelayakan | Master Promo/Diskon (baru) |
| `e890096` | Fix regresi — kolom `target_type`/`target_id` sempat ke-drop migrasi sebelumnya | — |
| `c4f4fca` | Promo di-wire ke 25 titik transaksi servis (biar promo beneran kepakai pas transaksi, bukan cuma ada di master) | 25 file transaksi servis |
| `04052b1` | Menu Master Promo/Diskon didaftarkan + fix bug collation search barang | `menu_config.php` |
| `705385b` | Permission `promo_diskon_read` resmi didaftarkan ke RBAC | — |

### Audit Kritis — `no_service` Tidak Unik Lintas Cabang (19-21 Juli)
**Temuan besar:** `tblservice.no_service` cuma unik per cabang, bukan global — 30.889 grup nomor kembar lintas 5 cabang. Ini bikin banyak `JOIN` di kode nyampur data cabang lain kalau nomor servisnya kebetulan sama.

| Commit | Perubahan | Halaman |
|---|---|---|
| `57cb882` | Ketemu: `helper-functions.php` JOIN `tblservice` tanpa `kd_cabang` → data dobel/campur | `helper-functions.php` |
| `84a25e6` | Fix JOIN di atas | `helper-functions.php` |
| `082e604` | Cegah batal/kosongkan servis ikut hapus data cabang LAIN saat nomor servis bentrok | — |
| `d936b15`, `a52ca6a`, `8cf1389` | Guard `kd_cabang` ditambah ke 15+ file High-Risk group (batch 1, 2, 3) | Banyak file transaksi servis |
| `e8c1b81` | Menu "Pindah Kepemilikan Kendaraan" disembunyikan sementara — nunggu migrasi data Access kelar dulu (sengaja, bukan bug) | `menu_config.php` |

### Komisi Mekanik — Wiring Snapshot (21 Juli)
| Commit | Perubahan | Halaman |
|---|---|---|
| `b7a242a` | Komisi mekanik di-snapshot permanen ke tabel `servis_komisi` di 3 titik bayar servis (dulu dihitung real-time, rawan berubah kalau data lama diutak-atik) | 3 titik proses bayar servis |

### QA Fix Batch — Bersih-bersih & Bug Fatal (9-10 Agustus)
| Commit | Perubahan | Halaman |
|---|---|---|
| `d1cc2e7` | Arsip 75 file/folder gak terpakai ke `archive/` | — |
| `e4a838c` | 2 bug di `scripts/update-progress.sh` (sistem lapor progress lama, sebelum diganti checklist-projek) | — |
| `8e042e8` | 5 halaman fatal error diam-diam di-fix + pagination Statistik Pelanggan (tadinya load 190MB sekali buka!) | 5 halaman + `statistik_pelanggan` |
| `bc988ed` | 4 fatal error di modul Antar Cabang + Master Karyawan (root cause: migration schema gak sinkron) | Antar Cabang, Master Karyawan |

### Test Servis End-to-End — Silent Fail (11 Agustus)
| Commit | Perubahan | Halaman |
|---|---|---|
| `4c53f06` | Bug besar: insert jasa servis gagal DIAM-DIAM (kolom `keterangan` gak ada + `kd_cabang` hilang) — transaksi keliatan sukses padahal data gak masuk | Proses bayar servis |
| `de86152`, `fae7c84`, `901dc24` | `kd_cabang` gak keisi di 9+ titik INSERT `tblservis_jasa`/`tblservis_barang` lain, termasuk jalur approve temuan/penawaran | Banyak file servis |

### Refactor Nota/PDF — Unifikasi Header-Footer (20-25 Agustus)
| Commit | Perubahan | Halaman |
|---|---|---|
| `65c4e0e` | Fix bug nota transaksi, mulai unifikasi header/footer PDF semua jenis nota | — |
| `f232a3b` | 8 nota dompdf sisanya dimigrasi ke partial header/footer seragam | 8 file cetak nota |
| `f2dfa46` | Unifikasi header/footer nota keluarga browser-print (retur, antar cabang) | Nota retur, antar cabang |
| `f9ab0e4` | `workorder-print.php` dimigrasi ke partial seragam | `workorder-print.php` |
| `95185b9` | WA invoice PDF yang rusak di-fix + 5 asset gambar 404 dibenerin (temuan QA) | Invoice WA |
| `ba4261a` | `servis-estimasi-pdf.php` dimigrasi ke partial header/footer seragam | `servis-estimasi-pdf.php` |
| `40c43a9` | 5 file orphan modul servis (nol referensi) diarsip | — |

### Fix Terakhir Sebelum Branch Sekarang (Akhir Agustus)
| Commit | Perubahan | Halaman |
|---|---|---|
| `d547a7a` | `no_service` generator (`OtomatisID()`) punya race condition — kalau 2 transaksi masuk bersamaan, bisa dapat nomor sama | Generator nomor servis |
| `8691bc6` | 4 generator `no_service` sisanya masih ada race condition/rand collision, di-fix juga | 4 titik generator |

---

## BAGIAN 1 — BATCH TERBARU: 16 Commit Branch `fix/servis-garansi-wo-fraud-validation`

### 1.1 Bug Keamanan — Fraud Klaim Garansi Servis
**Halaman:** `app/servis-garansi.php`

| | Sebelum | Sesudah |
|---|---|---|
| Validasi WO | Cek WO ada di `tbworkorderheader` (master seluruh cabang/history) — asal ADA, boleh diklaim gratis | Wajib WO tercatat di `tbservis_workorder` dengan `no_service` = servis referensi yang diklaim garansinya |
| Celah | Bisa masukin kode WO servis motor LAIN yang gak nyambung, digratisin 100% | Ditutup — WO harus beneran bagian riwayat servis asal |
| Pesan error | "Kode WO tidak ditemukan di master" (gak jelas) | Sebut nomor servis referensinya |

---

### 1.2 SQL Injection — Sapuan 1: 34 file `save_*.php`
**Halaman:** `save_akun_kas.php`, `save_barang_kategori.php`, `save_kendaraan.php`, `save_hutang_h.php`, dan 30 file `save_*.php` lain (Data Master & transaksi).

**Sebelum:** input form ditempel langsung ke query SQL tanpa escape.
**Sesudah:** semua input di-`mysqli_real_escape_string()`.

---

### 1.3 SQL Injection — Sapuan 2: 444 file self-processing page
**Halaman:** hampir semua file yang proses submit ke dirinya sendiri — `barang.php`, `antarcab_list.php`, `akun_kas_edit.php`, dll.

**Kenapa terpisah dari 1.2:** pola beda (bukan lewat `save_*.php`), kelewat di audit pertama, ditangkap di sapuan kedua.

---

### 1.4 Bug `save_mekanik.php` + 3 Temuan UI/UX
**Halaman:** `motor_warna.php`, `pelanggan_add.php`, `save_cabang.php`. Sekalian arsip 5 file mekanik lama yang dead code.

- `pelanggan_add.php` — validasi input ditambah (dulu ada input yang lolos tanpa validasi).
- `save_cabang.php` — logic simpan cabang diperbaiki.

---

### 1.5 Perbaikan Tampilan Transaksi
| Halaman | Sebelum | Sesudah |
|---|---|---|
| `pembelian.php` | List kosong = tabel kosong polos, user bingung | Dikasih pesan/petunjuk |
| `penjualan.php` | Bocor teks `"?>"` di daftar transaksi (tag PHP ke-print) | Dihapus, tampilan bersih |
| `pmby_piutang.php` | Field/pencarian pakai kolom PELANGGAN padahal ini piutang ke SUPPLIER — ketuker konsep | Diganti field supplier yang benar + ditambah pagination |
| `barang_kategori_add_new.php` | Link navigasi nyasar ke 7 file lama yang orphan | Diarahkan ke file aktif, 7 file lama diarsip |
| `servis-carinopol-garansi.php`, `servis-reguler.php` | Pencarian dibatasi `LIMIT 100` tanpa pemberitahuan | User dikasih tahu eksplisit kalau hasil dipotong limit |
| `pembelian_add_rst.php`, `pesanan_pembelian_add_rst.php` | Field wajib "Supplier" ada di tab tersembunyi → submit gagal diam-diam | Validasi cross-tab: submit di-blok + auto-lompat ke tab bermasalah |

---

### 1.6 Beres-Beres Dead Code
106 file (60 + 46) script debug/dev/duplikat tanpa referensi kepake dipindah ke `app/_archive/` (bukan dihapus, aman kalau ternyata masih perlu). Dicek 2x sebelum diarsip.

---

### 1.7 Tooling Internal — Lapor Progress
Sistem `update-checklist.sh` buat lapor progress otomatis ke dashboard `checklist-projek`, gantiin Google Sheet lama. Ditambah mode `--create` buat bikin modul baru tanpa daftar manual.

---

## BAGIAN 2 — KEPUTUSAN MEETING (28 JUNI 2026) VS KODE SEKARANG

Sumber: `archive/docx/PANDUAN MEETING WEB BENGKEL v3.docx` — 10 keputusan resmi tim operasional (Pak Novian, Mba Indry, Mas Amil, Marshell, Dianra).

| # | Topik Keputusan | Status di Kode | Halaman Kunci |
|---|---|---|---|
| Q1 | Refund servis lunas → via inputan baru, bukan hapus | ✅ Ada, ditest UAT, 2 bug ditemukan & di-fix | `retur_servis.php`, `retur_servis_detail.php` |
| Q2/Q3 | Stok potong saat input ke WO, kembali otomatis kalau dihapus sebelum closing | ✅ Konsisten | — |
| Q4 | Diskon kasir langsung + approval sesuai SOP kategori | ✅ Ada, ditest, 1 bug di-fix | `approval-diskon.php` |
| Q5 | Komisi real-time, terkunci saat closing | ⚠️ Beda pendekatan — pakai snapshot permanen, bukan hitung real-time | `_include_komisi_snapshot.php` |
| Q6 | Komisi mekanik pengganti garansi (beda mekanik = dapat komisi dari % original) | ✅ Field ada; **logika pembagian pool belum terverifikasi** | `servis-garansi.php` |
| Q7 | Garansi 7 hari standar, maks 14 dengan approval Supervisor | ⚠️ **Kontradiksi** — DB nyimpen skema tier otomatis (Silver 7 / Gold 11 / Platinum 14) tanpa approval gate | `master_kategori_member` |
| Q8 | Multi-payment (>1 metode per transaksi) | ❌ **Belum ada** — kolom `metode_pembayaran` cuma 1 field, 100% data pakai "Tunai" | `tblservice` |
| Q9 | DP servis 50% untuk servis besar/part inden | ✅ Ada, ditest — **gap:** gak ada warning kalau DP > tagihan | `laporan-dp.php` |
| Q10 | Part beli counter + pasang servis, no double-potong stok | ⚠️ Belum diverifikasi baris-per-baris | — |
| — | Status VOID pengganti hapus data | ❌ Nol ditemukan — kemungkinan dipakai pola closing-lock, bukan status eksplisit | — |

---

## BAGIAN 3 — GAP ANALYSIS: DOKUMEN REQUIREMENT VS KODE (per 9 Agustus 2026)

Sumber: `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md`, dibandingkan ke `docs/summary/SISTEM INFORMASI BENGKEL FIT MOTOR.md`.

### 🔴 Kritis — akurasi angka duit taruhannya
1. **Acuan HPP** (harga beli termahal dari 4 pembelian terakhir) — cuma di-mirror dari Access, nol dihitung sendiri di web (`access-sync.php:59`).
2. **HPP FIFO** — nol implementasi sama sekali, padahal ditulis sebagai Rule di `CLAUDE.md`.
3. **Insentif/Bagi Hasil/Siklus** — data sync jalan (`tbpersen_insentif`, `tbbagi_hasil`, `tbsiklus`), **nol kode yang makai**.
4. **`no_service` gak unik lintas cabang** — 30.889 grup kembar; sebagian besar sudah di-guard, sisa 3 file risiko rendah.

### 🟠 Tinggi — dampak besar, effort kecil
5. 2 laporan sudah jadi tapi gak ada di menu: `lap_komisi_mekanik.php`, `lap_profit_insentif.php`.
6. KM Berikut servis masih manual, harusnya auto dari Master Jadwal Oli.
7. Dashboard Manajemen/Cabang tersebar di banyak file, gak terintegrasi.
8. Rekonsiliasi antar cabang — data ada, laporan belum ada.

### 🟡 Sedang — pelengkap, wajar belum ada
- Akuntansi Keuangan/Laba Rugi — nol sama sekali.
- Reminder servis via WA — data ada, UI/scheduler belum.
- Komisi Sales & Service Advisor — mentok di definisi "Admin 5%" yang belum jelas.

### 🟢 Sengaja Ditunda (keputusan bisnis, bukan kelalaian)
- Cuci gratis & poin cuci, Booking Servis, Pindah Kepemilikan Kendaraan (nunggu migrasi data Access).

---

## BAGIAN 4 — PERTANYAAN YANG MASIH NGEGANTUNG

Detail lengkap 27 pertanyaan ada di `docs/PERTANYAAN_OWNER_2026-08-30.md`. Yang paling nge-blok:

1. **Metode HPP resmi** — terakhir / rata-rata / FIFO / acuan-4-termahal? (4 sumber beda-beda nyebut logika berbeda)
2. **Definisi "Admin 5%"** di formula insentif — service advisor / kepala mekanik / admin kantor?
3. **Durasi garansi resmi** — SOP meeting bilang 7/14 hari dgn approval, DB nyimpen tier 7/11/14 otomatis. Mana yang bener-bener jalan?
4. **Multi-payment** — keputusan meeting bilang bisa, kode belum mendukung. Mau dibangun atau keputusan direvisi?

---

## BAGIAN 5 — ALUR KERJA YANG DIPAKAI (buat dijelasin kalau ditanya "gimana caranya nemu semua ini")

1. **Audit kode** — baca source langsung, grep pola berulang (SQL injection, dead code, dll), bukan cuma baca dokumentasi.
2. **Cross-check 3 sumber** — dokumen requirement (docx/md), database aktual (struktur tabel), kode PHP yang jalan sekarang. Kalau 3 sumber beda cerita → itu yang dicatat sebagai gap/kontradiksi.
3. **Verifikasi sebelum klaim fixed** — tiap fix ditest manual/UAT sebelum ditutup, bukan cuma "kelihatannya udah bener".
4. **Commit granular** — 1 commit = 1 masalah spesifik, pesan jelas (masalah + akibat), gampang di-audit ulang.
5. **Pertanyaan yang gak bisa dijawab kode disisihkan** — bukan ditebak, tapi didaftar eksplisit buat Owner putuskan (bisnis, bukan teknis).

---

*Sumber dokumen: commit log branch `fix/servis-garansi-wo-fraud-validation`, `archive/docx/PANDUAN MEETING WEB BENGKEL v3.docx`, `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md`, `docs/GAP_ANALYSIS_RINGKASAN.md`, `docs/GAP_ANALYSIS_JAWABAN_TEMUAN.md`, `docs/PERTANYAAN_OWNER_2026-08-30.md`.*
