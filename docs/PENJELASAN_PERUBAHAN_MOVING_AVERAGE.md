# Penjelasan Perubahan: HPP Moving Average (FIT MOTOR Web Base)

Dokumen ini menjelaskan apa yang dibangun, bagaimana alurnya, file apa yang diubah, dan tabel database apa yang ditambah atau disesuaikan. Tanggal pengerjaan: 2026-10-08. Sumber kebutuhan: `docs/PRD_Moving_Average_Fit_Motor_Webbase_v1.md`, `docs/PRD_TEKNIS_Moving_Average_Fit_Motor_Webbase_v1.md`, `docs/planning/PLANNING_MOVING_AVERAGE_WEBBASE_2026-10-05.md`. Dokumen pendamping: `docs/SOP_PERSEDIAAN_MOVING_AVERAGE.md` (operasional) dan `docs/planning/RUNBOOK_CUTOVER_MOVING_AVERAGE.md` (urutan cutover).

## 1. Ringkasan singkat

HPP resmi sekarang Moving Average per lokasi (cabang), menggantikan rencana FIFO. Setiap barang masuk menghitung ulang rata-rata biaya. Setiap barang keluar memakai rata-rata yang berlaku saat transaksi diposting, dan rata-rata tidak berubah karena barang keluar.

Sistem dibangun berdampingan dengan sistem lama, bukan menggantikannya sekaligus. Tabel stok lama `tbstok` tetap ditulis seperti biasa. Ledger baru (`inv_ledger`) mencatat nilai dan HPP. Tiap cabang punya mode:

| Mode | Perilaku |
|---|---|
| `off` | Default semua cabang. Tidak ada efek sama sekali ke alur lama. |
| `shadow` | Alur lama tetap satu-satunya yang berlaku. Ledger baru ikut mencatat di belakang untuk dibandingkan dengan `tbstok`. Kegagalan shadow tidak pernah membatalkan transaksi. |
| `live` | Stok yang berlaku = saldo ledger baru. Stok minus ditolak, tanggal transaksi dipaksa hari ini (tanpa backdate), HPP disimpan ke detail penjualan dan servis. Transaksi atomik: bila ledger menolak, seluruh transaksi dibatalkan. |

Status sekarang: semua cabang `off`. Kode dan tes selesai di lokal. Produksi belum disentuh.

## 2. Konsep hitung

- Barang masuk: rata-rata baru = (nilai lama + nilai masuk) / (qty lama + qty masuk).
- Barang keluar: biaya keluar = rata-rata saat itu. Rata-rata tidak berubah.
- Hitungan memakai bilangan bulat: qty dalam milli-unit, nilai dalam sen, pembulatan half-up. `total_value` adalah angka otoritatif; rata-rata diturunkan darinya.
- Saldo tidak boleh minus pada mode live (BR-05). Tabel `inv_balance` punya CHECK `qty >= 0`.
- Retur penjualan masuk kembali pada HPP asal penjualan, bukan rata-rata sekarang.
- Selisih harga beli (PPV) dicatat terpisah, tidak mencampuri rata-rata.

## 3. Alur transaksi

### 3.1 Alur umum (semua mode)

1. Halaman lama menjalankan logikanya seperti biasa dan menulis `tbstok`.
2. Tepat setelah itu halaman memanggil adapter `app/_include_inv_shadow.php` (fungsi `inv_shadow_in`, `inv_shadow_out`, atau varian live).
3. Adapter membaca mode cabang. Bila `off`, selesai tanpa melakukan apa pun.

### 3.2 Mode shadow

Adapter membuka koneksi database terpisah, memanggil `InventoryService::postIn/postOut`, dan menelan semua error. Koneksi terpisah dipakai karena 12 halaman lama punya transaksi sendiri; memulai transaksi di koneksi yang sama akan meng-commit transaksi lama secara diam-diam.

### 3.3 Mode live

Ada dua pola, tergantung halaman lama:

- **Halaman yang sudah punya transaksi sendiri** (retur pembelian/penjualan/servis, transfer kirim/terima, terima DO, do_from_po, POS rst): ledger diposting lewat `inv_live_post_in/out` di dalam transaksi yang sama. Bila ledger menolak (stok kurang, periode closed), pengecualian memicu rollback seluruh transaksi.
- **Halaman yang tadinya tidak transaksional** (penjualan, pembelian, stok masuk/keluar, servis bayar dan input, garansi): `inv_live_tx_begin` membuka transaksi, `inv_atomic_in/out` menulis `tbstok` dan ledger, lalu transaksi di-commit otomatis di akhir request. Untuk halaman yang memanggil WhatsApp atau Accurate, `inv_live_tx_commit()` dipanggil sebelum panggilan HTTP itu supaya data lokal tidak ikut tertahan atau hilang bila layanan luar gagal.

Sebelum menyimpan, halaman memanggil `inv_live_cek_keranjang` atau `inv_live_cek_servis` untuk menolak transaksi bila saldo ledger tidak cukup, dan tanggal dipaksa hari ini.

### 3.4 Setelah barang keluar (live)

HPP Moving Average disimpan ke `tblpenjualan_detail.harga_pokok` dan `tblservis_barang.harga_pokok` (snapshot), hanya untuk cabang live. Laporan laba dan komisi membaca snapshot lebih dulu, lalu jatuh ke harga pokok master bila snapshot kosong, sehingga data sebelum cutover tidak berubah.

### 3.5 Koreksi, periode, jurnal

- **Koreksi** (penyesuaian qty/nilai, koreksi harga beli, revaluasi, scrap, opname) memakai maker-checker: pembuat tidak boleh sama dengan penyetuju. Posisi ADM membuat, MNG atau KEU menyetujui (KEU untuk revaluasi dan Finance).
- **Periode** (`inv_period`): open, closed, locked. Posting ke periode tertutup ditolak.
- **Transit antar cabang**: kirim mengurangi stok asal dan mencatat barang dalam perjalanan (`inv_transit`) dengan aging; terima menambah stok tujuan.
- **Jurnal otomatis** (`inv_journal`, `inv_journal_line`): seimbang per baris ledger. Kode akun tidak ditanam di kode; diisi lewat `inv_config` kunci `acct_*`. Selama kosong, jurnal belum terbentuk. Begitu diisi, jurnal tertunda dibentuk otomatis oleh `scripts/inv_harian.php --jurnal`.

### 3.6 Cutover per cabang (urutan)

Siapkan produksi (migrasi) → shadow 1-2 minggu → opname fisik dan muat Opening Stock → live. Rinciannya ada di runbook.

## 4. Tabel database

Semua perubahan lewat runner reversible (`up` / `down`) di `db/migrations/`. Seluruhnya baru diterapkan di database lokal.

### 4.1 Tabel baru (14)

| Tabel | Fungsi |
|---|---|
| `inv_location` | Daftar lokasi kanonik dan alias (CDT=CIKDITIRO, PCL=PACUL, PSL=PESALAKAN, TRM=TRAYEMAN, PST). Kode `001` dan string kosong sengaja tidak dipetakan. |
| `inv_balance` | Saldo per cabang dan barang: qty, rata-rata, total nilai, versi. Ada CHECK `qty >= 0`. |
| `inv_ledger` | Buku besar persediaan, hanya tambah (append-only). Kolom utama: `kd_cabang`, `no_item`, `trx_type`, `ref_type`, `ref_no`, `ref_line`, `qty_in`, `qty_out`, `unit_cost`, `value_delta`, `avg_before`, `avg_after`, `qty_after`. Kunci unik pada referensi mencegah posting ganda. |
| `inv_avg_history` | Riwayat perubahan rata-rata. |
| `inv_config` | Konfigurasi: `inv_mode` per cabang, ambang opname, hari aging transit, kode akun `acct_*`, penanda baseline shadow. |
| `inv_audit_log` | Jejak audit setiap aksi (siapa, apa, sebelum, sesudah, alasan). |
| `inv_transit` | Barang dalam perjalanan antar cabang beserta aging. |
| `inv_ppv_log` | Selisih harga beli (Purchase Price Variance). |
| `inv_claim_receivable` | Piutang klaim ke supplier. |
| `inv_correction_request` | Antrian koreksi maker-checker. |
| `inv_period` | Status periode per cabang (open, closed, locked). |
| `inv_journal` | Header jurnal persediaan. |
| `inv_journal_line` | Baris debit dan kredit jurnal. |
| `bak_20261008_tb_master_posisi` | Backup otomatis tabel posisi sebelum permission diubah. |

### 4.2 Tabel lama yang diubah

| Tabel | Perubahan | Runner |
|---|---|---|
| `tblservis_barang` | Kolom baru `harga_pokok` (snapshot HPP, diisi hanya untuk cabang live). | `2026-10-08_run_inv_hpp_snapshot.php` |
| `tb_master_posisi` | Isi JSON `permissions` ditambah kode `inv_admin`, `inv_opening`, `inv_laporan`, `inv_view_all`, `inv_koreksi`, `inv_approve`, `inv_finance` untuk ADM, MNG, KEU. | `2026-10-08_run_inv_permissions.php` |

### 4.3 Tabel lama yang dibaca atau ditulis kode baru (struktur tidak diubah)

- `tbstok`: tetap ditulis alur lama; adapter hanya menyusul. Saldo historisnya negatif di banyak cabang dan `kd_cabang` sengaja tidak dinormalisasi. Opening stock hanya masuk ledger baru.
- `tblpenjualan_detail.harga_pokok`: sudah ada; kini diisi snapshot untuk cabang live.
- Laporan dan komisi membaca `tblservis_barang.harga_pokok` dan `tblpenjualan_detail.harga_pokok`.

### 4.4 Urutan runner di produksi

1. `2026-10-08_run_inv_core.php up` (tabel inti)
2. `2026-10-08_run_inv_koreksi.php up` (tabel koreksi, transit, PPV, klaim, jurnal)
3. `2026-10-08_run_inv_permissions.php up`
4. `2026-10-08_run_inv_hpp_snapshot.php up`

Backup database penuh wajib sebelum langkah 1. `down` membatalkan tiap langkah; `down` untuk tabel hanya berjalan bila kosong atau memakai argumen `force`.

## 5. File yang dibuat

### 5.1 Inti logika (`app/lib/`)

| File | Isi |
|---|---|
| `InventoryService.php` | Posting masuk/keluar, kunci saldo, mode, periode, rekonsiliasi shadow, konversi satuan, self-check. |
| `InventoryCorrection.php` | Koreksi qty/nilai, koreksi harga beli + PPV, maker-checker, opname, revaluasi, scrap, transit, klaim supplier, bonus proporsional. |
| `InventoryJournal.php` | Jurnal seimbang per baris ledger, pengaturan akun, posting jurnal tertunda, rekonsiliasi jurnal. |

### 5.2 Adapter

`app/_include_inv_shadow.php`: seluruh fungsi penghubung antara halaman lama dan service (shadow, cek live, atomik, snapshot HPP).

### 5.3 Modul layar `app/_inventory/`

`admin.php` (mode dan rekonsiliasi), `opening_stock.php`, `laporan_valuasi.php`, `laporan_kartu_stok.php`, `laporan_lain.php` (transit, PPV, klaim, HPP), `koreksi.php` (maker-checker), serta `koneksi_inv.php`, `_ace_header.php`, `_ace_footer.php`. Menu "Persediaan (Moving Average)" ditambahkan di `app/menu_config.php`.

### 5.4 Skrip, migrasi, tes

- `scripts/inv_harian.php`: cek harian (integritas, selisih shadow, rekonsiliasi jurnal, aging transit).
- `scripts/inv_precutover.php`: pemeriksa kesiapan cutover, hanya membaca.
- `db/migrations/2026-10-08_*`: satu file SQL dan empat runner (bagian 4).
- `tests/inventory/`: `run_inventory_tests.php` (40), `run_shadow_adapter_tests.php` (47), `run_correction_tests.php` (50), `run_journal_tests.php` (26). Total 163 tes.

## 6. File lama yang diubah (`app/`)

### 6.1 Penambahan hook adapter (shadow/live)

| Kelompok | File |
|---|---|
| Pembelian | `pembelian_add.php`, `pembelian_add_rst.php`, `pembelian_cab_add_proses.php` |
| Terima barang (DO) | `do_receive.php`, `do_from_po.php` |
| Penjualan | `penjualan_add.php`, `penjualan_add_rst.php`, `penjualan_cab_add_proses.php`, `penjualan_cab_add_proses_mitra.php`, `proses-add-detail/penjualan.php` |
| Servis | `servis-reguler-byr.php`, `servis-input-reguler.php`, `servis-input-reguler-rst.php`, `servis-input-reguler-jemput.php`, `servis-input-reguler-jemput-rst.php`, `servis-garansi.php` |
| Retur | `save_retur_pembelian.php`, `save_retur_penjualan.php`, `retur_servis_approve.php`, `retur_pembelian_batal.php`, `retur_penjualan_batal.php` |
| Antar cabang | `pengadaan_antarcab_proses.php`, `pengadaan_antarcab_push.php`, `pengadaan_antarcab_terima.php` |
| Stok manual dan opname | `stok_masuk_add.php`, `stok_masuk_add_rst.php`, `stok_keluar_add.php`, `stok_keluar_add_rst.php`, `so-item-masuk-rst.php`, `so-item-keluar-rst.php` |
| Sinkron Access | `_include_access_sync.php` (tidak menimpa saldo cabang live) |

### 6.2 Laporan dan komisi (baca snapshot HPP, fallback ke master)

`lap_komisi_mekanik.php`, `_include_komisi_snapshot.php`, `issue_add.php`.

### 6.3 Larangan edit/hapus transaksi yang sudah diposting

Endpoint hapus/edit item keranjang kini wajib `status_trx='0'`: `pembelian_hapus_item.php`, `pembelian_edit_item.php`, `penjualan_hapus_item.php`, `penjualan_edit_item.php`, `stok_masuk_hapus_item.php`, `stok_masuk_edit_item.php`, `stok_keluar_hapus_item.php`, `stok_keluar_edit_item.php`, serta enam file `pesanan_pembelian_*` dan `pesanan_penjualan_*` (hapus/edit item). Untuk servis, `servis_hapus_item_byr.php`, `servis-barang-hapus.php`, `servis_edit_item_byr.php`, `servis_edit_item_garansi.php`, `servis_edit_item_jemput.php` menolak perubahan bila servis sudah bayar atau selesai. `hapus_bayar_hutang.php` dan `hapus_bayar_piutang.php` menolak bila pembayaran sudah punya header. Perubahan ini berlaku untuk semua cabang, bukan hanya cabang live.

## 7. Bug lama yang ditemukan dan ikut diperbaiki

| File | Masalah | Perbaikan |
|---|---|---|
| `do_receive.php`, `do_from_po.php`, `rencana_order_add.php` | Fatal TypeError PHP 8 di setiap submit: `mysqli_real_escape_string` dipanggil pada array `$_POST['items']`. | Escape per elemen. |
| `servis-garansi.php` | Bayar garansi gagal di MySQL strict: `persen_mekanik1..4` kosong. | Inisialisasi 0 dan muat dari data servis. |
| `penjualan_add_rst.php` | Penjualan langsung tanpa pesanan gagal simpan header (`total_qty_order` NULL). | Default `'0'` dan `error_log` di cabang gagal. |
| `servis-reguler-byr.php` | Tidak punya guard potong stok ganda. | Guard ditambah. |
| Lima jalur servis lain | Guard potong ganda tidak memperhitungkan cabang; servis cabang lain dengan nomor sama bisa lolos tanpa potong stok. | Guard memakai kd_cabang. |

## 8. Cara pengujian dan batasnya

- Tes CLI: 163 tes lulus (empat suite di `tests/inventory/`).
- Harness CLI dengan kode halaman asli (session dan POST disimulasikan, WhatsApp dimatikan, Accurate diarahkan ke URL mati): POS, pembelian, tiga jenis retur, transfer kirim dan terima, terima DO, do_from_po, servis bayar, lima halaman input servis dan garansi, stok masuk/keluar rst dan non-rst. Tiap alur diuji dua kasus: periode closed (rollback total) dan periode open (komit konsisten). Harness perlu `php_curl.dll`.
- Browser: halaman admin, shadow pembelian dan penjualan, maker-checker, opening stock, RBAC, live POS/stok keluar/pembelian, render 20 halaman penulis stok tanpa error PHP, dan submit nyata stok keluar (9 ditolak karena saldo 5, 2 lolos).
- Tidak diuji di browser lokal: submit halaman yang memanggil Accurate, karena `config/accurate_config.php` berisi token asli. Juga belum: klik penuh servis bayar, transfer, retur, dan DO.

## 9. Yang belum dan bukan bagian kode

| Item | Pemilik |
|---|---|
| Menjalankan empat migrasi di produksi | Rafi |
| Opname fisik dan muat Opening Stock per cabang (D-03) | Rafi dan tim cabang |
| Kode akun COA (D-07) | Rafi / akuntansi |
| Cabang pilot, penyetuju per cabang, arti kode lokasi `001`, cara normalisasi `tbstok` | Rafi |
| Membuka gerbang Status Requirement di checklist-projek (lima fitur sudah terdaftar) | Rafi |
| Shadow 1-2 minggu, E2E browser di cabang pilot, lalu mode live | Waktu berjalan |

Aturan lama "HPP FIFO" di CLAUDE.md sudah usang; yang berlaku Moving Average.
