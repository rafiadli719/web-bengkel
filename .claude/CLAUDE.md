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

## Progress Merge Modul Kasir   Keuangan (2026-09-03)

Plan lengkap: `docs/superpowers/plans/2026-09-03-merge-modul-kasir-keuangan.md`
(34 task). Status per 2026-09-03 malam:

- **Task 1-10 SELESAI & commit**: backup DB (372MB), DDL 25 tabel
  `*_closing_kasir` + 10 VIEW, migrasi data ~50rb+ baris (row-count match
  semua), RBAC (`tb_master_posisi.permissions` +5 kode `kasir_*` di
  ADM/KEU/KSR).
- **Task 11-12 SELESAI & commit**: porting `app/_keuangan/kasir/` -
  `koneksi_kasir.php`, `kas_awal.php`, `kas_akhir.php`,
  `closing_revision_helpers.php`, `pemasukan.php`, `pengeluaran.php`,
  `process_closing_transaction.php`, `utils.php`. Sumber real beda dari
  draft plan awal (dashboard besar, bukan file kecil) - lihat commit
  message masing-masing buat detail keputusan porting.
- **Keputusan susulan (2026-09-03 malam)**: tabel `kas_awal_closing_kasir`
    di-rename jadi **`kas_awal`** (dan `kas_akhir_closing_kasir`  
  `kas_akhir`, `detail_kas_awal_closing_kasir`   `detail_kas_awal`,
  `detail_kas_akhir_closing_kasir`   `detail_kas_akhir`) - TIDAK ada
  tabrakan nama sama tabel fitmotor lama (dicek: gak ada tabel `kas_awal`/
  `kas_akhir` asli di `fitmotor_dbbengkel`, fitur kasir lama fitmotor
  pakai `tbkas_kasir_header`/`tbkeping` bukan nama itu). File lama
  `app/kas_awal.php`/`app/kas_akhir.php` (pakai `tbkas_kasir_header`)
  MASIH LIVE, belum diganti - penggantian menu ke versi baru
  `app/_keuangan/kasir/kas_awal.php` ditahan sampai Task 15 (menu
  wiring), biar gak putus fitur user tanpa checkpoint.
- **Task 13-34 BELUM dikerjakan**: closing (`close_transaksi1.php`
  141KB), closing revisi, `setoran_keuangan.php` (424KB, terbesar
  seprojek), cutover (Task 17) & drop tabel mati (Task 18) - 2 task
  terakhir itu WAJIB tanya konfirmasi eksplisit dulu sebelum eksekusi
  (irreversible, sistem kasir/keuangan live).
