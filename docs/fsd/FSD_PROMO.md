# Functional Specification Document — Modul Promo Engine

**Versi:** 1.0 Draft
**Tanggal:** 2026-07-18
**Status:** Menunggu approval
**Referensi:** `docs/PROMPT_CLAUDE_CODE_PLANNING_PROMO_ENGINE.md`, `FSD_SERVIS.md` §9 & §12#1 (superseded oleh dokumen ini), `FSD_PENGADAAN_INVENTORY.md` §5.2/§7.1/§11.1 (pola desain rujukan), `FSD_MEMBERSHIP.md` §5/FR-03, `FSD_CRM.md` §5.1

**Decision yang mengikat dokumen ini** (Owner, Pak Novian, 16 Juli 2026 + konfirmasi sesi 18 Juli 2026):
- Nama promo, periode berlaku, dan target yang dipromokan (item dan/atau jasa) **semua fleksibel**, ditentukan user lewat data master — bukan hardcode nama/durasi/target di kode.
- 1 promo boleh mencakup **banyak** item dan/atau jasa sekaligus (campur boleh).
- Stackable (boleh digabung dengan promo lain) **fleksibel per promo**, ditentukan user.
- Kalau 2+ promo stackable aktif bersamaan: hitung **berurutan** — harga dasar → potong promo A → sisa dipotong promo B (bukan sistem memilih kombinasi "paling untung customer").
- Interaksi dengan diskon tier member: **flag per-promo** (`boleh_gabung_diskon_member`), bukan aturan global.
- Yang boleh membuat/mengubah promo: **Owner + Admin Pusat saja** (Kepala Cabang tidak).
- Scope promo: **global semua cabang**, tidak ada promo khusus per cabang.
- Pendekatan implementasi: **extend skema existing** (`master_diskon_periode`), bukan rebuild dari nol.

**Peringatan penting dari hasil investigasi (baca sebelum lanjut):**
1. **`master_diskon_periode` sudah LIVE di production, sudah dipakai (1 data asli), dan sudah terhubung ke 3 alur transaksi servis** (`servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php` via `diskon_source='promo'` + kolom `id_promo` di `tblservis_barang`/`tblservis_jasa`/`tbservis_workorder`) — **tapi modul ini tidak pernah terdokumentasi di FSD manapun sebelum sesi ini.** Dokumen ini bukan desain dari nol, melainkan formalisasi + perluasan sistem yang sudah berjalan diam-diam.
2. **`servis_poin_cuci` dan `servis_voucher_cuci`** (desain lama di `FSD_SERVIS.md` §9, mekanisme akumulasi poin 5-hari-rolling untuk voucher cuci gratis) **CONFIRMED tidak pernah dibangun** — nol jejak di kode maupun schema database manapun (`fitmotor_dbbengkel.sql`, `_FIXED_V5/V6/V7.sql`). Desain itu **di-supersede total** oleh Promo Engine ini. **Jangan ada development di masa depan yang mengacu/coding ke tabel `servis_poin_cuci`/`servis_voucher_cuci` — anggap tidak pernah ada.** `FSD_SERVIS.md` §9 dan §12#1 harus dianggap dokumen mati, menunggu housekeeping (dicoret/diberi catatan superseded) di luar scope sesi ini.

---

## 1. Ringkasan & Tujuan

Modul ini mendefinisikan skema data dan aturan bisnis untuk promo/diskon periode yang generik dan fleksibel — mencakup diskon item dan/atau jasa dalam periode tertentu, dengan dukungan multi-target per promo, aturan stacking antar-promo, dan interaksi eksplisit dengan diskon tier member. Menggantikan rencana lama "Program Cuci Motor Gratis" yang hardcode dengan mekanisme master-data generik yang sudah mulai dibangun (`master_diskon_periode`) tapi perlu diperluas agar memenuhi seluruh syarat Owner.

## 2. Ruang Lingkup

**In scope:** skema promo (header + multi-target), aturan stacking, interaksi dengan diskon member, histori pemakaian promo, migrasi data existing, role akses pembuatan promo.

**Out of scope:** diskon tier member itu sendiri (`FSD_MEMBERSHIP.md`), mekanisme WO kombinasi `is_gratis` (fitur terpisah yang sudah live, promo di sini bisa menyasar target yang sama tapi tidak menggantikan mekanismenya), notifikasi/broadcast promo ke pelanggan (`FSD_CRM.md`), perbaikan SQL injection di `master-diskon-periode.php` (dicatat sebagai Open Item, task terpisah).

## 3. Aktor & Role

| Aktor | Hak Akses |
|---|---|
| Owner / Admin Pusat | Buat, ubah, nonaktifkan promo (header + target). Satu-satunya role yang boleh. |
| Kepala Cabang / Staf Cabang | Tidak bisa buat/ubah promo. Hanya melihat promo aktif yang otomatis diterapkan saat transaksi. |
| CS / Kasir (servis) | Lihat promo apa saja yang berlaku di transaksi berjalan (multi-promo kalau stackable), tidak bisa mengubah nilai promo. |
| Sistem (saat transaksi disimpan) | Cocokkan item/jasa transaksi dengan promo aktif, hitung potongan berurutan sesuai stacking, catat ke histori pemakaian. |

## 4. Glosarium

| Istilah | Arti |
|---|---|
| Promo | 1 baris di `master_diskon_periode` — punya nama, periode, tipe diskon, dan bisa menyasar banyak target. |
| Target Promo | Item atau jasa spesifik yang kena diskon dari 1 promo (relasi 1 promo : banyak target). |
| Stackable | Flag per-promo: boleh (1) atau tidak boleh (0) digabung dengan promo lain yang aktif bersamaan pada target yang sama. |
| `boleh_gabung_diskon_member` | Flag per-promo: boleh (1) atau tidak boleh (0) promo ini digabung dengan diskon tier member di transaksi yang sama. |
| Histori Pemakaian | Catatan audit tiap kali promo benar-benar dipakai di 1 transaksi (`promo_usage_log`) — beda dari "promo aktif" yang cuma status di master. |

## 5. Model Data

### 5.1 Tabel Existing (dipertahankan sebagian, sebagian dipindah — lihat 5.2)

`master_diskon_periode` — sudah ada di MySQL, sudah dipakai production:
`id_promo, nama_promo, deskripsi, tipe_promo (enum persen/nominal), nilai_promo, tanggal_mulai, tanggal_selesai, target_type (enum workorder/jasa/barang), target_id, target_nama, status_aktif, created_by, created_at, updated_at`.

Kolom `id_promo` (FK, nullable) + `diskon_source`/`diskon_persen`/`diskon_nominal` sudah ada di `tblservis_barang`, `tblservis_jasa`, `tbservis_workorder` — **dipertahankan apa adanya**, jadi titik integrasi promo engine baru tidak berubah dari sisi tabel transaksi.

**Catatan desain penting (existing, tidak diubah oleh dokumen ini):** komentar di `app/helper-functions.php` baris 6 menyatakan diskon item-level via `diskon_source` (member/promo) **tidak melalui** `checkDiskonApproval()` (guard approval untuk diskon manual kasir). Ini **intentional**, bukan bug — karena nilai diskon promo sudah final ditentukan lewat master data (bukan input bebas kasir yang perlu approval berjenjang). Dicatat di sini supaya tidak ada yang "memperbaiki" ini secara keliru di masa depan.

### 5.2 Perubahan yang Direkomendasikan

**`master_diskon_periode` — hapus 3 kolom, tambah 2 kolom:**

| Perubahan | Kolom | Keterangan |
|---|---|---|
| Hapus (pindah ke tabel anak) | `target_type`, `target_id`, `target_nama` | Digantikan relasi 1:banyak ke `master_diskon_periode_target` — 1 promo sekarang bisa punya banyak target campur item+jasa. |
| Tambah | `stackable` TINYINT(1) DEFAULT 0 | 1 = boleh digabung dengan promo lain aktif di target yang sama. 0 = eksklusif (lihat §7 aturan bentrok). |
| Tambah | `boleh_gabung_diskon_member` TINYINT(1) DEFAULT 0 | 1 = promo ini boleh dihitung bersamaan dengan diskon tier member customer di transaksi yang sama. |

**Tabel baru: `master_diskon_periode_target`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `id_promo` | INT FK → `master_diskon_periode.id_promo` | |
| `target_type` | ENUM('jasa','barang','workorder') | `workorder` dipertahankan untuk kompatibilitas data lama (paket kombinasi jasa via `kode_wo`) — lihat Open Item #6 soal apakah ini tetap dipakai ke depan. |
| `target_id` | VARCHAR(50) | `no_item` (barang), kode jasa, atau `kode_wo` (workorder). |
| `target_nama` | VARCHAR(200) | Cache nama untuk tampilan, sama pola dengan kolom existing. |

Index: `(id_promo)`, `(target_type, target_id)` — dipakai untuk query matching cepat saat transaksi ("item/jasa ini kena promo apa saja yang aktif hari ini").

**Tabel baru: `promo_usage_log`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `id_promo` | INT FK → `master_diskon_periode.id_promo` | |
| `no_service` | VARCHAR(20) FK → `tblservis_jasa`/`tblservis_barang` | Transaksi tempat promo dipakai. |
| `target_type` | ENUM('jasa','barang','workorder') | Target spesifik yang kena di transaksi ini. |
| `target_id` | VARCHAR(50) | |
| `nilai_potongan` | DECIMAL(15,2) | Nominal rupiah aktual yang terpotong dari promo ini (setelah dihitung sesuai urutan stacking — bukan nilai_promo mentah). |
| `urutan_stacking` | TINYINT | Urutan promo ini dihitung dalam rangkaian stacking (1 = dihitung pertama dari harga dasar). |
| `dipakai_oleh` | INT FK → `tbuser` | Kasir/staf yang memproses transaksi. |
| `tanggal_pakai` | TIMESTAMP DEFAULT current_timestamp() | |

Konsisten dengan pola audit trail project ini (`member_tier_history`, `alarm_harga_beli`).

**Migrasi data existing:** 1 baris `master_diskon_periode` id=1 ("awal taun", `target_type='workorder'`, `target_id='WO0005'`, `target_nama='PAKET SERVIS LENGKAP'`) dipindah utuh jadi 1 baris di `master_diskon_periode_target` (`id_promo=1, target_type='workorder', target_id='WO0005', target_nama='PAKET SERVIS LENGKAP'`). Header id=1 dapat default `stackable=0`, `boleh_gabung_diskon_member=0` (paling konservatif, tidak mengubah perilaku lama yang memang cuma ambil 1 promo tanpa gabung apa pun). Tidak ada baris `promo_usage_log` yang bisa direkonstruksi retroaktif untuk histori pemakaian promo id=1 sebelum sesi ini — dicatat sebagai keterbatasan migrasi, bukan blocker.

## 6. Functional Requirements

### FR-01 — Promo Multi-Target
1 promo (`master_diskon_periode`) dapat memiliki banyak baris target (`master_diskon_periode_target`), campur `jasa` dan `barang` dalam 1 promo yang sama.

### FR-02 — Stackable Flag & Urutan Hitung Berurutan
Setiap promo punya flag `stackable`. Saat transaksi disimpan, sistem ambil **semua** promo aktif (`status_aktif=1`, tanggal berlaku mencakup hari ini) yang match target item/jasa transaksi tersebut. Kalau lebih dari satu promo match pada target yang sama dan semuanya `stackable=1`, potongan dihitung berurutan berdasar `created_at` ASC (promo yang dibuat lebih dulu dihitung lebih dulu): harga dasar → potong promo pertama → sisa harga dipotong promo berikutnya, dst.

### FR-03 — Interaksi dengan Diskon Member
Flag `boleh_gabung_diskon_member` per promo menentukan apakah promo ini ikut dihitung bersamaan dengan diskon tier member (`getActiveDiscountForService()`) di transaksi yang sama. Kalau `0` (tidak boleh gabung), sistem pilih salah satu yang nilai potongannya **lebih besar** untuk customer (promo vs diskon member), bukan keduanya (lihat Open Item #4 — asumsi ini perlu konfirmasi Owner).

### FR-04 — Histori Pemakaian Promo
Setiap kali promo berkontribusi potongan harga di 1 transaksi, sistem insert 1 baris ke `promo_usage_log` per target yang kena, mencatat nilai potongan aktual dan urutan stacking-nya.

### FR-05 — Matching Promo Otomatis Saat Transaksi
Saat servis/jasa/barang diinput di 3 handler existing (`servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php`), sistem query `master_diskon_periode` JOIN `master_diskon_periode_target` untuk cari promo aktif yang match `target_type`+`target_id` item/jasa bersangkutan, hari ini berada di antara `tanggal_mulai` dan `tanggal_selesai`.

### FR-06 — Role Pembuatan/Perubahan Promo
Hanya Owner dan Admin Pusat yang bisa create/update/nonaktifkan promo. Kepala Cabang dan staf cabang lain read-only (transaksi otomatis kena promo, tidak bisa mengubah master).

### FR-07 — Migrasi Data Existing
Data promo id=1 ("awal taun") dipindah ke skema baru tanpa kehilangan informasi, sesuai §5.2.

## 7. Business Rules Konsolidasi

- Promo berlaku **global semua cabang** — tidak ada kolom `kd_cabang`, tidak ada promo khusus 1 cabang.
- **Validasi saat create/update promo:** sistem **menolak simpan** promo baru dengan `stackable=0` kalau rentang tanggalnya overlap dengan promo lain yang aktif dan menyasar target (item/jasa) yang sama — mencegah admin membuat konfigurasi ambigu dari awal, bukan menyelesaikannya saat runtime.
- Urutan stacking default: `created_at` ASC (promo lama duluan). Kalau `created_at` identik (kasus langka, dibuat di detik yang sama), tie-break pakai `id_promo` ASC.
- DELETE fisik promo (`btn_hapus` di `master-diskon-periode.php`) **berisiko** begitu `promo_usage_log` mulai terisi (FK constraint / histori hilang). Rekomendasi: ganti jadi soft-delete (`status_aktif=0` permanen, tombol UI diganti "Nonaktifkan" bukan "Hapus") — **perubahan ini perlu approval eksplisit sebelum diimplementasikan** karena mengubah perilaku tombol existing yang sudah dipakai user (lihat Open Item #5).
- `checkDiskonApproval()` tetap **tidak** berlaku untuk diskon dari promo (lihat §5.1) — perilaku existing dipertahankan, bukan bagian dari perubahan dokumen ini.

## 8. Alur Utama

**Alur A — Owner/Admin Pusat membuat promo baru:**
1. Buka `master-diskon-periode.php`, isi header (nama, deskripsi, tipe diskon, nilai, tanggal mulai/selesai, stackable, boleh_gabung_diskon_member).
2. Tambah 1 atau lebih baris target (pilih jasa dan/atau barang dari master masing-masing).
3. Sistem validasi bentrok (lihat §7) sebelum simpan. Kalau bentrok, tampilkan pesan spesifik promo mana yang konflik dan pada target apa.
4. Simpan → header + semua baris target ter-insert dalam 1 aksi (transaksional).

**Alur B — Transaksi servis kena promo (stacking berurutan):**
1. Kasir input jasa/barang di servis (`servis-input-reguler.php` dkk).
2. Sistem cari semua promo aktif yang match target ini hari ini (`FR-05`).
3. Kalau 0 promo match → harga normal, tidak ada perubahan alur (lihat Edge Case).
4. Kalau 1 promo match → hitung potongan langsung dari harga dasar, insert 1 baris `promo_usage_log`.
5. Kalau >1 promo match dan semua `stackable=1` → urutkan `created_at` ASC, hitung berurutan (harga dasar → promo 1 → sisa dipotong promo 2 → ...), insert 1 baris `promo_usage_log` per promo dengan `urutan_stacking` dan `nilai_potongan` masing-masing.
6. Kalau >1 promo match tapi ada yang `stackable=0` → hanya promo `stackable=0` tersebut yang dipakai (karena validasi §7 mestinya sudah mencegah 2 promo non-stackable bentrok aktif bersamaan di target sama — kalau tetap terjadi karena data lama/migrasi, sistem pakai yang `id_promo` terbesar/terbaru dan catat sebagai anomali di log aplikasi, bukan block transaksi kasir).
7. Cek `boleh_gabung_diskon_member` tiap promo yang kepakai vs diskon member customer (`FR-03`) untuk keputusan akhir gabung/pilih terbesar.
8. Total setelah semua potongan tersimpan normal ke `tblservis_barang`/`tblservis_jasa` (kolom existing `diskon_persen`/`diskon_nominal` diisi hasil akhir gabungan, bukan per-promo — detail per-promo tetap lengkap di `promo_usage_log`).

## 9. Edge Case Handling

| # | Kasus | Penanganan |
|---|---|---|
| 1 | 0 promo aktif match | Harga normal, tidak ada baris `promo_usage_log`, tidak ada error/warning ke kasir. |
| 2 | Banyak promo stackable aktif bareng di 1 target | Hitung berurutan `created_at` ASC sesuai §8 Alur B langkah 5, tiap promo tercatat kontribusinya sendiri di `promo_usage_log`. |
| 3 | Promo non-stackable bentrok promo lain aktif di tanggal & target sama | Dicegah **saat create/update** (§7) — seharusnya tidak pernah terjadi di data baru. Kalau tetap terjadi (data lama/migrasi), lihat §8 Alur B langkah 6 (fallback: id_promo terbesar menang, dicatat sebagai anomali). |
| 4 | Target promo (item/jasa) juga sedang `is_gratis=1` dari mekanisme WO kombinasi (harga sudah dikunci 0) | Promo tetap dihitung tapi hasil potongan dari basis 0 = 0 (tidak error, tidak menambah nilai). Dicatat sebagai gap non-blocking — dua mekanisme diskon-ke-nol berjalan independen, tidak saling cek. |
| 5 | Tanggal mulai > tanggal selesai saat input form | Form tolak simpan, validasi client + server side. |
| 6 | Promo yang sudah punya baris `promo_usage_log` di-nonaktifkan (`status_aktif=0`) | Diperbolehkan — nonaktifkan tidak menghapus histori, promo baru tidak match lagi ke transaksi baru. |
| 7 | Promo dihapus fisik (`btn_hapus`) padahal sudah punya histori pemakaian | Berisiko (lihat §7) — direkomendasikan diblok/diganti soft-delete, tapi perubahan tombol ini **menunggu approval terpisah** (Open Item #5), belum diimplementasikan di sesi ini. |

## 10. Non-Functional Requirements

- Semua query promo baru (matching, CRUD) harus pakai prepared statement (`mysqli_prepare`) mengikuti pola remediasi SQL injection yang sudah diterapkan di modul Pengadaan — **kecuali** perbaikan `master-diskon-periode.php` existing yang sengaja ditunda (Open Item #1, out of scope sesi ini).
- Matching promo dilakukan real-time saat transaksi disimpan (bukan background job) — volume promo aktif diperkirakan kecil (belasan, bukan ribuan), tidak butuh caching khusus.
- Penamaan kolom/tabel baru mengikuti konvensi existing project (snake_case, prefix `master_`/tabel anak dengan suffix `_target`/`_log`).

## 11. Dependency Antar Modul

- **`FSD_MEMBERSHIP.md`** — flag `boleh_gabung_diskon_member` dan `getActiveDiscountForService()` adalah titik integrasi langsung. Perubahan pada FSD Membership (mis. field diskon tier) berdampak ke FR-03 dokumen ini.
- **`FSD_SERVIS.md` §9 & §12#1** — superseded total (lihat peringatan di atas). Perlu housekeeping non-blocking: tambah catatan "superseded oleh FSD_PROMO.md" di file tersebut (di luar scope sesi ini).
- **Mekanisme WO Kombinasi `is_gratis`** (`tbworkorderdetail`, `tbservis_pending_items`, live sejak 2026-07-02) — promo bisa menyasar target yang sama; tidak saling menggantikan (lihat Edge Case #4).
- **`app/helper-functions.php` `checkDiskonApproval()`** — promo tetap bypass guard ini, perilaku existing dipertahankan (§5.1).
- **`docs/PROMPT_CLAUDE_CODE_PLANNING_PROMO_ENGINE.md`** — dokumen prompt sesi ini, jadi rujukan alasan keputusan di atas.

## 12. Kriteria Penerimaan

- [ ] Tabel `master_diskon_periode_target` dan `promo_usage_log` dibuat sesuai §5.2, kolom `target_type`/`target_id`/`target_nama` di header lama dihapus setelah data dipindah.
- [ ] Data existing id=1 ("awal taun") berhasil dimigrasi ke tabel target baru tanpa kehilangan informasi, tetap match ke `WO0005`.
- [ ] Form CRUD promo mendukung banyak target (tambah/hapus baris) dalam 1 form, campur jasa+barang.
- [ ] Validasi tolak simpan promo `stackable=0` yang bentrok tanggal+target dengan promo aktif lain.
- [ ] Transaksi dengan >1 promo stackable aktif menghasilkan potongan berurutan yang benar secara matematis (bisa diverifikasi manual: harga dasar, potongan promo 1, sisa, potongan promo 2).
- [ ] `promo_usage_log` terisi benar per promo per transaksi, termasuk `urutan_stacking` dan `nilai_potongan` aktual.
- [ ] Flag `boleh_gabung_diskon_member` mempengaruhi hasil akhir sesuai FR-03 (bisa diverifikasi kasus gabung vs pilih-terbesar).
- [ ] Hanya user dengan role Owner/Admin Pusat yang bisa akses form create/update/nonaktifkan promo.
- [ ] Tidak ada regresi di 3 handler transaksi existing (`servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php`) untuk transaksi yang tidak kena promo sama sekali.

## 13. Open Items — Butuh Keputusan Sebelum Implementasi

| # | Item | Kenapa Penting |
|---|---|---|
| 1 | **SQL injection ringan di `master-diskon-periode.php`** (field tanggal tidak di-escape via prepared statement, walau field numeric sudah aman lewat `floatval`/`intval`) — perlu jadi task perbaikan tersendiri di luar sesi planning ini. | Risiko keamanan tetap ada sampai diperbaiki terpisah; sengaja tidak difix sesi ini sesuai instruksi (planning-only). |
| 2 | **`servis_poin_cuci`/`servis_voucher_cuci` confirmed dead**, di-supersede oleh dokumen ini — perlu housekeeping `FSD_SERVIS.md` §9/§12#1 (beri catatan superseded) di sesi terpisah. | Mencegah developer masa depan coding ke tabel yang tidak pernah ada berdasarkan FSD lama yang belum diberi catatan. |
| 3 | Tie-break stacking kalau 2+ promo dibuat di `created_at` identik — dokumen ini asumsikan fallback `id_promo` ASC. | Kasus langka, tapi perlu Owner tahu default-nya biar tidak mengejutkan kalau kejadian. |
| 4 | **Asumsi FR-03**: kalau `boleh_gabung_diskon_member=0`, sistem pilih potongan **terbesar** (promo vs diskon member) — bukan otomatis menangkan salah satu. Owner belum eksplisit mengonfirmasi aturan tie-break ini, baru asumsi desain sesi ini. | Kalau ternyata Owner mau aturan berbeda (mis. diskon member selalu menang kalau tidak boleh gabung), FR-03 perlu direvisi sebelum implementasi. |
| 5 | **Ganti tombol "Hapus" promo jadi soft-delete** (`status_aktif=0` permanen) begitu `promo_usage_log` mulai terisi, supaya histori tidak hilang. Ini mengubah perilaku tombol existing yang sudah dipakai user. | Perubahan perilaku UI/data existing — perlu approval eksplisit sebelum dikerjakan, bukan keputusan teknis sepihak. |
| 6 | **`target_type='workorder'`** dipertahankan di tabel target baru untuk kompatibilitas data lama (`WO0005`). Apakah ke depan promo untuk paket kombinasi WO tetap direpresentasikan sebagai 1 target `workorder`, atau dipecah jadi target jasa+barang individual mengikuti definisi ketat Owner ("item dan/atau jasa" saja)? | Menentukan apakah `workorder` tetap valid `target_type` untuk promo baru, atau cuma legacy-support untuk data id=1. |
| 7 | Urutan stacking default `created_at` ASC — cukup, atau perlu field prioritas manual (`prioritas_urutan`) supaya Admin Pusat bisa atur urutan tanpa bergantung kapan promo dibuat? | Kalau kasus di lapangan sering butuh urutan spesifik (bukan sekadar "yang lama duluan"), field tambahan ini perlu ditambah ke skema sebelum implementasi. |

---

## Urutan Pengerjaan Selanjutnya (usulan)

1. Owner jawab Open Items #3–#7 (terutama #4 dan #6, paling berdampak ke logic inti).
2. Kalau disetujui: buat migrasi SQL (tabel baru + migrasi data id=1) sebagai sesi implementasi terpisah.
3. Implementasi form CRUD multi-target + validasi bentrok.
4. Implementasi logic matching & stacking di 3 handler transaksi existing.
5. Housekeeping `FSD_SERVIS.md` (tandai §9/§12#1 superseded) dan perbaikan SQL injection `master-diskon-periode.php` (Open Item #1) — bisa paralel, tidak bergantung pada implementasi promo engine.
