---
name: lapor-checklist
description: Lapor progress fitur FIT MOTOR WEB BASE ke dashboard checklist-projek (scripts/update-checklist.sh / webhook). Pakai tiap selesai mengerjakan atau menguji satu fitur; berisi daftar ID fitur resmi dan endpoint API web-base.
---

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

