## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## Lapor Progress ke checklist-projek (Dashboard Web Base)

Repo ini ("FIT MOTOR WEB BASE") dimonitor progress kesiapan fiturnya di dashboard terpusat
`checklist-projek` — produksi `https://checklist.fitmotor.web.id` (default), lokal `http://localhost:8090` (lihat `CHECKLIST_BASE_URL` di `.env`).

Dashboard sekarang menggunakan **Hierarki 4-Level Murni** di `/web-base`:
1. **Modul Utama** (Servis, Pembelian, Penjualan, Laporan & Keterangan)
2. **Sub Modul** (Dilengkapi narasi alur proses kerja bertahap per nama file `.php`)
3. **Halaman** (Dipetakan langsung ke file fisik `.php` di `app/`)
4. **Fitur Checklist** (Status RAG: 🟢 Hijau = Siap Pakai / Lolos UAT, 🟡 Kuning = Tahap Uji Coba, 🔴 Merah = Pengerjaan)

### Cara Lapor Progress via Script

Panggil `scripts/update-checklist.sh` saat Claude Code menyelesaikan pengerjaan/pengujian suatu fitur:

```bash
./scripts/update-checklist.sh "nama fitur atau ID fitur" "status" "progress_percent" "keterangan"
# contoh:
./scripts/update-checklist.sh "feat-booking-servis" "hijau" 100 "Form booking servis reguler lolos pengujian lapangan"
./scripts/update-checklist.sh "feat-monitor-antrian" "uat" 80 "Antrian mekanik siap ditest di bengkel"
```

Status valid:
- **RAG Status**: `hijau` (siap pakai) | `kuning` (tahap uji coba / UAT) | `merah` (dalam pengerjaan)
- **Status Pengembangan**: `selesai` (100%) | `uat` (80%) | `development` (30-60%) | `belum_mulai` (0%)

### Cara Lapor Progress via HTTP / Webhook Langsung

Endpoint serbaguna (support JSON payload):
```http
POST /api/webhook/progress
X-API-Key: <API_KEY_WEBBENGKEL>
Content-Type: application/json

{
  "feature": "feat-booking-servis",
  "status": "hijau",
  "progress_percent": 100,
  "keterangan": "Lolos pengujian lapangan di bengkel Trayeman"
}
```

### Daftar ID & Nama Fitur Resmi di Dashboard Web Base

Gunakan ID atau Nama Fitur berikut saat memanggil script/webhook:

| ID Fitur | Halaman File .php | Nama Fitur Terdaftar | Status |
|:---|:---|:---|:---:|
| `feat-cari-nopol` | `servis-carinopol.php` | Pencarian Nopol & Cek Riwayat Servis | 🟢 Hijau |
| `feat-validasi-kontak` | `servis-carinopol.php` | Validasi Kontak WhatsApp Pelanggan | 🟡 Kuning |
| `feat-tambah-pelanggan` | `input_pelanggan_awal.php` | Registrasi Pelanggan & Motor Baru | 🟡 Kuning |
| `feat-booking-servis` | `servis-input-reguler.php` | Form Booking Servis & Catat Keluhan | 🟢 Hijau |
| `feat-nomor-antrean` | `servis-reguler.php` | Cetak Tiket Nomor Antrian Fisik | 🟢 Hijau |
| `feat-monitor-antrian` | `servis-reguler.php` | Monitor Antrian & Pengerjaan Mekanik Realtime | 🟡 Kuning |
| `feat-workorder-mekanik`| `workorder-input.php` | Pencatatan Work Order & Temuan Mekanik | 🟡 Kuning |
| `feat-bayar-servis` | `servis-reguler-byr.php` | Proses Kasir Pembayaran & Cetak Struk Lunas | 🟡 Kuning |
| `feat-validasi-garansi` | `servis-carinopol-garansi.php` | Cek Histori Servis & Validasi Masa Garansi | 🟡 Kuning |
| `feat-klaim-garansi` | `servis-garansi.php` | Input Klaim & Pengerjaan Ulang Garansi | 🟡 Kuning |
| `feat-cancel-antrean` | `servis-reguler.php (modal)` | Verifikasi Antrian & Modal Konfirmasi Batal | 🟡 Kuning |
| `feat-cancel-eksekusi` | `servis-cancel-proses.php` | Eksekusi Hapus Antrian & Catat Log Batal | 🟡 Kuning |
| `feat-pencarian-produk` | `cari_item_pembelian.php` | Pencarian Sparepart & Cek Ketersediaan Stok | 🟢 Hijau |
| `feat-input-pembelian` | `pembelian_add.php` | Input Faktur Beli & Auto-Tambah Stok Toko | 🟡 Kuning |
| `feat-riwayat-pembelian`| `pembelian.php` | Riwayat Faktur & Pelunasan Hutang Supplier | 🟡 Kuning |
| `feat-stok-masuk` | `stok_masuk_add.php` | Input Penerimaan Barang & Update Kartu Stok | 🟡 Kuning |
| `feat-pos-cari-item` | `penjualan_add_item_cari.php`| Pencarian Data Pelanggan & Katalog Belanja | 🟡 Kuning |
| `feat-pos-pelayanan` | `penjualan_add.php` | Form Transaksi Kasir POS & Auto-Potong Stok | 🟡 Kuning |
| `feat-pos-struk` | `penjualan_struk.php` | Cetak Struk/Nota Pembayaran Kasir | 🟡 Kuning |
| `feat-riwayat-penjualan`| `penjualan.php` | Riwayat Transaksi Kasir & Rekap Harian | 🟡 Kuning |
| `feat-monitor-antarcab` | `pengadaan_antarcab.php` | Monitor Permintaan & Buat Tiket Mutasi Stok | 🟡 Kuning |
| `feat-kirim-antarcab` | `pengadaan_antarcab_proses.php`| Konfirmasi Pengiriman & Potong Stok Asal | 🟡 Kuning |
| `feat-terima-antarcab` | `pengadaan_antarcab_terima.php`| Konfirmasi Penerimaan Fisik & Tambah Stok | 🟡 Kuning |
| `feat-lap-servis` | `lap_servis.php` | Rekap Pendapatan Servis & Komisi Mekanik | 🟡 Kuning |
| `feat-lap-pembelian` | `lap_pembelian.php` | Rekap Faktur Pembelian & Hutang Supplier | 🟡 Kuning |
| `feat-lap-penjualan` | `lap_penjualan.php` | Rekap Omset Penjualan & Margin Profit | 🟡 Kuning |
| `feat-lap-antarcab` | `lap_antarcab.php` | Rekap Riwayat Mutasi Antar Cabang | 🟡 Kuning |
| `feat-stok-akhir` | `stok-akhir.php` | Cek Posisi Sisa Stok Fisik per Cut-off | 🟡 Kuning |
| `feat-catatan-keterangan`| Form Penjualan / Stok Log | Kolom Keterangan / Memo Khusus per Transaksi | 🟡 Kuning |

### Endpoint API CRUD Web Base yang Tersedia

Dashboard checklist-projek menyediakan API RESTful lengkap:
- `GET /api/web-base/tree` — Ambil struktur hierarki 4-level lengkap beserta alur proses dan rollup RAG
- `GET /api/web-base/stats` — Ambil ringkasan statistik kesiapan
- `GET|POST|PUT|DELETE /api/web-base/modules` — CRUD Modul Utama
- `GET|POST|PUT|DELETE /api/web-base/sub-modules` — CRUD Sub Modul (mendukung field `alur_proses`)
- `GET|POST|PUT|DELETE /api/web-base/pages` — CRUD Halaman per Sub Modul
- `GET|POST|PUT|DELETE /api/web-base/features` — CRUD Fitur per Halaman (status_rag: hijau/kuning/merah)

## Progress Merge Modul Kasir & Keuangan (2026-09-03)

Plan lengkap: `docs/superpowers/plans/2026-09-03-merge-modul-kasir-keuangan.md` (34 task).
History detail tiap update (2026-09-03 s/d 2026-09-07) dipindah ke
`.ai/changelog/2026-09-kasir-keuangan-merge-history.md` — baca situ kalau butuh jejak
keputusan lama. Status TERKINI ada di section "Status Ringkas per 2026-09-27" di bawah,
itu satu-satunya sumber kebenaran, jangan percaya baris history mana pun tanpa cek situ dulu.

## Status Ringkas per 2026-09-27 (baca INI dulu, bukan scroll history di atas)

Log di atas ("Update YYYY-MM-DD ...") adalah jurnal historis — akurat
PADA SAAT ditulis, tapi bisa jadi basi kalau gak ada entry baru yang
mengoreksi. Section ini satu-satunya sumber kebenaran soal status
SEKARANG; kalau bingung status sesuatu, cek di sini dulu sebelum percaya
baris history manapun di atas. Update section ini tiap kali status
berubah (jangan biarin basi lagi kayak sebelumnya).

**Selesai, bukan backlog lagi:**
- Task 16 (smoke test E2E 5 cabang, commit `93cc6de`), Task 17
  (blokir web_kasir lama, commit `f9e9787`, Step 2 di-skip permanen atas
  keputusan Rafi), Task 18 revisi (drop 5/9 tabel orphan, commit
  `115f12f`, 4 tabel akuntansi aktif sengaja gak didrop) — semua tuntas
  2026-09-12.
- N+1 query listing utama `setoran_keuangan.php` (paling parah, 4
  query/baris tanpa limit) — fixed commit `ea3d7be` (2026-09-07).
- Log hygiene `setoran_keuangan.php` — fixed commit `9353db1`
  (2026-09-07).
- Trigger lifecycle `tr_update_setoran_status` — fixed commit `b038463`
  (2026-09-07).
- Semua commit lokal numpuk (sampai `383d10c`) sudah di-push ke origin
  2026-09-13.
- **Modul Penanganan Komplain Tahap 1+2 SELESAI TUNTAS** (branch
  `feat/modul-komplain`, merge ke `fix/servis-garansi-wo-fraud-validation`
  2026-09-13, plan: `docs/superpowers/plans/2026-09-12-modul-komplain-implementation.md`,
  spec: `docs/Modul_Penanganan_Komplain_Fit_Motor.md`). 13 task, semua
  commit + smoke test live + final code review (5 temuan, semua difix:
  permission bocor di endpoint riwayat nopol, race condition nomor
  komplain, hardcoded DB credential fallback di file baru, duplicate-key
  handling master kategori, migration file gak reproducible). Checklist-
  projek diupdate 8 fitur, semua selesai/100%. `app/_komplain/` folder
  baru, 4 tabel `tblkomplain*`, posisi `KACAB` baru, permission RBAC
  `komplain_*` di posisi CS/ADM/KM/MNG. Fitur Komplain Garansi existing
  di modul servis TIDAK disentuh (tetap terpisah, sesuai keputusan Rafi).
- **REWORK-to-Warranty Integration SELESAI & LIVE** (commit `93190ce`,
  2026-09-17, di atas Tahap 1+2 di atas). Komplain kategori REWORK yang
  di-approve Kepala Cabang sekarang otomatis bikin servis garansi
  (is_garansi=1, prioritas urgent) via `createServisGaransi()` di
  `function_servis.php`, bukan servis reguler/jemput lagi. Kolom baru
  `tblkomplain.no_service_asli`/`no_service_rework` (migration
  `db/migrations/2026-09-15_komplain_rework_garansi.sql`, sudah jalan
  live). Form input komplain sekarang wajib pilih "No Service Asli"
  (dropdown tervalidasi ke `tblservice`, harus nopol sama). Endpoint baru
  `ajax_cari_service_nopol.php`. Sudah E2E test via browser (login test
  account KACAB, submit komplain -> approve Setuju -> servis garansi
  ke-generate benar, ref_no_service_original match) — data test dihapus
  setelah verifikasi, akun test dihapus juga.
  Catatan environment: modul komplain butuh `app/db_env.php` DAN
  `config/db_env.php` (2 file terpisah, tiap `koneksi.php` cari
  `db_env.php` di direktori sendiri) — keduanya gitignored, isi
  `putenv()` DB_HOST/DB_USER/DB_PASS/DB_NAME. Kalau setup ulang di mesin
  lain, kedua file itu harus dibuat manual (gak ke-commit by design,
  sesuai [[feedback_secrets_no_hardcoded_default]]).
- **Modul Komplain — tema ACE global (navbar/sidebar/footer) SELESAI &
  LIVE** (commit `f3c158a`, `bbc7006`, 2026-09-27). 7 halaman
  (`antrian_approval.php`, `antrian_rework.php`, `dashboard_manajemen.php`,
  `detail.php`, `eskalasi_manajemen.php`, `input.php`,
  `master_kategori.php`) tadinya standalone Bootstrap5+CDN, gak nyambung
  sidebar/navbar global — sekarang pakai partial baru
  `app/_komplain/_ace_header.php` / `_ace_footer.php` (include
  `menu_dashboard.php`, `lib/logo.php`, `lib/footer.php`, sama pola
  `servis-reguler.php` dkk). `koneksi_komplain.php` nambah `$_nama`/
  `$foto_user` dari `tbuser` buat navbar. `export.php` gak disentuh (murni
  download xlsx). Commit kedua (`bbc7006`) rapiin grid form
  `input.php` dari class Bootstrap5 (`row g-3`, `form-label`,
  `form-select`) ke Bootstrap3 (`form-group`, `control-label`,
  `form-control`) biar match CSS tema ACE. Divalidasi E2E browser pakai
  session admin live (bukan bikin akun test baru — udah ada sesi aktif),
  semua 7 halaman render sidebar/navbar/breadcrumb/footer benar. Sekalian
  bersih-bersih data test nyasar `tblkomplain.id=10`
  (`KPL-20260927-0005`, "[TEST-ALUR] E-tolak") yang lolos dari cleanup
  sesi REWORK-to-Warranty sebelumnya — dihapus via script sekali-pakai
  yang langsung dihapus lagi setelah jalan (gak masuk git).
- **N+1 minor 2 POST handler `setoran_keuangan.php` SELESAI** (commit
  `eb2da78`, 2026-09-27). Handler `terima_setoran` (baris ~718, tadinya
  3 query/item loop) dan `kembalikan_ke_cs` (baris ~1103, tadinya 1
  query/item loop) dibatch pakai `WHERE ... IN (...)`. Behavior-
  preserving, kolom/kondisi WHERE sama persis dgn query original — cuma
  divalidasi via review kode + php lint (gak ada data live "Sedang
  Dibawa Kurir" buat E2E browser saat itu).
- **`includes/sidebar.css` modul Keuangan Kasir dibuat** (commit
  `8f6dccb`, `b352eee`, 2026-09-27). File ini gak pernah ada di repo
  (404 dari awal) — dipakai `includes/sidebar.php` di 11 halaman
  (`setoran_keuangan`, `keuangan_pusat`, `master_akun`,
  `master_nama_transaksi`, `master_rekening_cabang`,
  `monitoring_setoran`, `setoran_bank_rekap`,
  `konfirmasi_buka_transaksi`, `closing_revisi_admin`, `keping`),
  sidebar tampil polos tanpa styling di semua halaman itu. Divalidasi
  E2E browser (session admin live) di 2 halaman beda, sidebar dark +
  kategori collapsible + active state render benar. Sempat coba ganti
  animasi `max-height` accordion ke `grid-template-rows` (0fr/1fr) buat
  fix temuan linter impeccable "layout-transition", tapi gagal ditest
  live (height tetap 0px walau class `.open` ke-apply) — di-revert balik
  ke `max-height` (commit `b352eee`), temuan linter di-suppress via
  `.impeccable/config.json` dengan alasan tercatat (accordion click-
  triggered, max 4 kategori, bukan animasi scroll/frequent).

**Masih pending/backlog beneran (bukan salah baca history):**
- **Task 1 Step 4 Modul Komplain** — akun Kepala Cabang (posisi `KACAB`)
  per cabang + lengkapi akun Kepala Mekanik (`KM`) di cabang PACUL,
  PESALAKAN, TRAYEMAN, CIKDITIRO (cuma PST yang punya KM sekarang).
  BLOCKING approval REWORK/non-REWORK beneran jalan di 4 cabang itu.
  Butuh dari Rafi: nama staf real + kode karyawan per cabang — JANGAN
  auto-generate akun/password.
- **Task 34** (retire `masterkey.php`) — masih blocked Task 21 (migrasi
  SSO bridge `priori-tech` → `tbuser`), Task 21 butuh koordinasi
  eksternal, belum ada progress baru.
- 3 gap desain Modul Komplain yang sengaja di-skip (spec ambigu, butuh
  keputusan Rafi): uploader data massal komplain, idempotent submit
  (dedup 60 detik), threshold No-show 7 hari configurable.
- Kredensial DB fallback hardcode (`fitmotor_LOGIN`/`Sayalupa12`) di
  `app/koneksi.php` dan file-file lama sejenis — utang teknis
  codebase-wide, bukan regresi baru, belum dibereskan (scope lintas
  banyak file, belum dijadwalkan).
- `closing_revisi_admin.php` link `includes/sidebar.css` tapi gak include
  sidebar sama sekali (TODO Task 15 di baris ~295, sidebar web_kasir gak
  diport) — halaman tampil tanpa sidebar, bukan regresi.
- Audit menu_config: Batch 0-7 selesai (Batch 6 Stok+Laporan sudah di
  commit `2c5db74`/`f79db7e`, Batch 7 Keuangan Kasir bersih per
  2026-09-28: semua halaman+handler guarded, sidebar 9 halaman
  terverifikasi live). Sisa Batch 8 Komplain (cek ulang cepat).

**Update 2026-09-28 (commit `7e9c055`):** sidebar 9 halaman Keuangan
Kasir diverifikasi live (bg dark, 260px, 18 link, 1 active tiap
halaman). Redirect login `koneksi_kasir.php` & `koneksi_komplain.php`
diganti dari `/index.php` (root domain, salah di Laragon) ke path
relatif ke `index.php` login app.

**Update 2026-09-29 (commit `e268742`):** lanjutan E2E lapangan setelah
fix kritis Penjualan/Pembelian (`fe857f8`/`af2d220`) — ketemu bug baru
saat verify live browser: `app/penjualan_add_item_cari.php` (menu
Penjualan > Tambah Data > Item Barang) fatal error tiap kali cari item
apapun, tabel hasil selalu kosong walau teks bilang "ditemukan N data"
(gak keliatan di layar karena `display_errors` off). Root cause: query
`SELECT status_harga_naik FROM tblitem` — kolom itu gak pernah ada di
skema, fitur badge "HARGA NAIK" per-item ini nyasar/gak nyambung ke
desain asli alarm harga beli (`db/migrations/2026-07-17_f4_alarm_harga_beli.sql`,
tabel terpisah `alarm_harga_beli` + trigger DB, bukan kolom di tblitem).
`mysqli_fetch_array(false)` fatal TypeError PHP 8, while loop mati
sebelum baris pertama ke-render. Fix: hapus query+cabang logic itu
(blocked_reason-nya toh gak pernah ditampilkan ke user juga), balik ke
validasi stok polos. Cuma 1 file kena (`pesanan_penjualan_add_item_cari.php`
dan `pesanan_penjualan_cab_add_item_cari.php` sudah dicek, bersih).
Divalidasi live: cari "20W-40MATIC" -> row render -> Pilih -> masuk
keranjang qty 2 -> item dihapus lagi (cleanup, gak checkout beneran).
Checklist-projek `feat-pos-cari-item` diupdate ke uat/80%.

**Update 2026-09-29 lanjutan (commit `8dc399a`, `d25b18d`):** verify live
Pembelian Input Manual ketemu pola sama — `pembelian_add_item_cari.php`
juga render SEMUA 5761 baris `view_cari_item` tanpa LIMIT (beda file dari
`cari_item_pembelian.php` yang udah dibatasi commit `a5794d9` sebelumnya,
kelewat). Sekalian header "Hasil Pencarian ditemukan N data" ke-render
2x (blok HTML kepasang dobel). Fixed (`8dc399a`).

Nyari lebih jauh: pola `SELECT * FROM view_cari_item` 4-varian
(asc/desc x kosong/isi keyword) tanpa LIMIT ternyata di-copy-paste ke
25 file modal cari item/jasa lintas modul — Penjualan (jl, jl_pesan),
Pembelian (bl), Pesanan Pembelian/Penjualan (+cab), Servis (item/jasa,
jemput, garansi), Stok Masuk/Keluar, Paket, Master Barang. Disapu
sekaligus LIMIT 200 (commit `d25b18d`), $tot tetap query count terpisah.
Divalidasi: php -l lolos 25 file, diff cuma nambah " LIMIT 200", live
browser 2 sample (stok_masuk_add_item_cari.php, servis-add-item-cari.php
struktur beda app_score) render normal.

**Update 2026-09-29 lanjutan lagi (commit `f6e98b4`):** backlog di atas
(pencarian PELANGGAN/KENDARAAN/PIUTANG-HUTANG) SELESAI DISAPU juga, di
sesi yang sama. Dicek dulu satu-satu apa ada WHERE kd_cabang yang perlu
hati-hati — ternyata semua view (`view_cari_pelanggan`,
`view_cari_kendaraan`, `view_penjualan_header`, `view_pembelian_header`,
`view_pesanan_pembelian_header`, `view_pesanan_penjualan_h`,
`view_pembayaran_hutang`, `view_pembayaran_piutang`) gak difilter
kd_cabang di WHERE sql_query-nya (2 file `penyesuaian-stok-*-manual-rst.php`
malah udah ada WHERE kd_cabang, tinggal nambah LIMIT dalam scope cabang
itu) — jadi LIMIT aman ditambah tanpa mengubah correctness. 17 file
disapu LIMIT 200 sekaligus: cari_pelanggan_jl.php + _pesan_rst + _rst,
cari_pelanggan_rst.php, kendaraan_rst.php, pelanggan_rst.php,
pembelian_rst.php, penjualan_rst.php, pesanan_pembelian_rst.php,
pesanan_penjualan_rst.php, penjualan_add_pelanggan_cari.php,
pesanan_penjualan_add_pelanggan_cari.php, pmby_piutang_add_pelanggan_cari.php,
pmby_hutang_rst.php, pmby_piutang_rst.php,
penyesuaian-stok-keluar/masuk-manual-rst.php. php -l lolos semua, diff
cuma nambah " LIMIT 200". Divalidasi live: pelanggan.php -> klik
Ascending -> pelanggan_rst.php render cepat walau total 37,674 baris
pelanggan. Checklist-projek diupdate (`feat-bayar-servis`).

Sapuan LIMIT search-modal lintas app (42 file total: 25 item/jasa +
17 pelanggan/kendaraan/transaksi/piutang-hutang) TUNTAS per commit
`d25b18d` + `f6e98b4`. Gak ada backlog serupa yang tersisa dari temuan
E2E lapangan sesi ini.

**Update 2026-09-29 Servis E2E penuh:** alur booking -> Work Order ->
item barang/jasa -> halaman bayar divalidasi live pakai data real
(A 2036 IH / ANDI, MAS / CBR-150, servis SV26000103579, dihapus bersih
setelah tes — no_service + tbservis_workorder + tblservis_barang/jasa +
tbservis_pending_items semua ke-cleanup, gak ada sisa).

Temuan (bukan bug kode, dicatat buat keputusan Rafi):
- **Duplikat kategori motor**: `tbkategori_motor` punya baris dobel
  untuk kategori yang sama — MATIC (id 2 & 6), SUPERMATIC (id 7 & 8),
  SUPER SPORT vs SUPERSPORT (id 5 & 10, beda ejaan doang). Akibatnya
  guard "WO tidak sesuai kategori motor" di `servis-input-reguler.php`
  (fungsi `_get_kd_kategori_motor_by_service`) BISA nge-block WO yang
  sebenarnya valid, kalau motor ke-mapping ke kategori id yang beda dari
  yang dipakai waktu setting mapping WO-nya (kejadian nyata: CBR-150
  ke-kategori id 5 "SUPER SPORT", tapi WO0001 di-mapping ke id 10
  "SUPERSPORT" — dianggap gak cocok padahal sama). Guard logic-nya
  sendiri BENER, cuma kena data kotor. Perlu keputusan Rafi: merge
  kategori duplikat (butuh cek semua FK/mapping yang nunjuk ke id lama
  sebelum didrop) — bukan sesuatu yang aman diputuskan sepihak.
- **Data sampah di `tbworkorderheader`**: WO0003 nama kosong, WO0004
  nama "2444" + waktu 2147483647 menit (integer overflow, jelas dummy),
  WO0006 "coba", WO0007 "DUMMYQA WO DUPLICATE TEST", dan WO0001 kode
  dobel (row lain juga "WO0001" tapi nama "Servis Rutin Matic" beda
  dari "SERVIS STANDAR MATIC/BEBEK"). Belum dibersihkan sesi ini,
  bukan bug tapi ganggu kalau dipakai staf beneran.
- **`view_service_kategori_motor` gak ada** — tapi kode udah defensif
  (`_tbl_exists_local()` check dulu sebelum query), jadi otomatis
  fallback ke derive dari `tblkendaraan`+`tbtipe_motor`+`tbkategori_motor`
  tanpa error. Bukan bug, cuma catatan kalau view itu emang gak pernah
  dibuat (mungkin sisa rencana lama yang gak jadi dipakai).
- Setiap klik submit (Tambah WO, dkk) di `servis-input-reguler.php`
  pakai pola `alert()` + `window.location.href` bawaan legacy — bikin
  tab browser automation freeze ~10-40 detik nunggu resource/dialog,
  padahal proses server-side-nya sendiri cepat begitu selesai (row
  DB langsung ke-insert). Bukan bug, tapi jadi catatan buat E2E
  browser session berikutnya: jangan buru-buru nutup tab pas macet,
  cek DB langsung buat verifikasi state, baru lanjut.

**Update 2026-09-30 Pesanan Pembelian/Penjualan E2E (commit `ea2c1a6`):** bug
KRITIS sama persis pola Penjualan/Pembelian/Stok Masuk-Keluar sebelumnya — INSERT
ke `tblorder_detail`/`tblorderjual_detail`/`tblorderjual_header` di jalur "Pilih"
modal cari item (`pesanan_pembelian_add_rst.php`,
`proses-add-detail/pesanan-penjualan.php`, `pesanan_penjualan_add_rst.php`,
`pesanan_penjualan_cab_add_rst.php`) hilang kolom NOT NULL tanpa default
(`nobaris`, `qty_terima`, `harga_sp`, `harga_pokok`, `margin_jual`,
`total_terima`, `id_tabel`, `tipe_trx`, `order_ke` — beda-beda per file). Fix:
tambah kolom hilang, `nobaris` dihitung `MAX(nobaris)+1` per user/cabang.
Halaman `_add.php` utama (form langsung, bukan lewat modal) TIDAK kena, sudah
lengkap dari awal. Divalidasi E2E live 3x (fetch POST sesi test, bukan klik UI):
Pesanan Pembelian, Pesanan Penjualan Input Manual, Pesanan Penjualan Antar
Cabang — semua sukses simpan header+detail, data test dihapus. Checklist E2E
lapangan: Servis, Penjualan, Pembelian, Antar Cabang, Stok Masuk/Keluar,
Pesanan Pembelian/Penjualan, **Laporan** SELESAI. Sisa: Keuangan Kasir, Komplain,
Data Master.

**Update 2026-09-30 Laporan E2E (commit `4118c84`):** crawl otomatis 42 halaman
`lap_*.php`. Fix `lap_komisi_mekanik.php` 8906ms->1943ms (subquery laba_barang
scan 361rb baris `tblservis_barang` tanpa filter tanggal, ditambah `WHERE
no_service IN (...)` date-filtered). Halaman ini gak dilink `menu_config.php`
(akses URL langsung), formula referensi buat `_include_komisi_snapshot.php`.
500 error di `lap_kas_keluar/masuk_pdf/xls.php` + `lap_servis_pdf/xls.php` pas
crawl tanpa GET params itu artifact crawl doang (halaman utama selalu isi
default date range dulu), bukan bug reachable — gak difix.
`laporan-cancel-servis.php` 2111ms bukan N+1, view `view_laporan_cancel_servis`
sendiri berat (984ms buat 1 baris) — ditunda, fitur jarang dipakai.
