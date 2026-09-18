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
keputusan lama. Status TERKINI ada di section "Status Ringkas per 2026-09-13" di bawah,
itu satu-satunya sumber kebenaran, jangan percaya baris history mana pun tanpa cek situ dulu.

## Status Ringkas per 2026-09-13 (baca INI dulu, bukan scroll history di atas)

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
- N+1 minor di 2 POST handler `setoran_keuangan.php` (baris ~718
  `terima_setoran`, baris ~1103 kembalikan-ke-CS) — N dibatasi pilihan
  user, bukan listing tanpa limit, severity rendah, opsional dikerjain.
- 3 gap desain Modul Komplain yang sengaja di-skip (spec ambigu, butuh
  keputusan Rafi): uploader data massal komplain, idempotent submit
  (dedup 60 detik), threshold No-show 7 hari configurable.
- Kredensial DB fallback hardcode (`fitmotor_LOGIN`/`Sayalupa12`) di
  `app/koneksi.php` dan file-file lama sejenis — utang teknis
  codebase-wide, bukan regresi baru, belum dibereskan (scope lintas
  banyak file, belum dijadwalkan).
