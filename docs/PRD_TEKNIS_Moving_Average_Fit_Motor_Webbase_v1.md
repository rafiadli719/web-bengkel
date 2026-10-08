# PRD TEKNIS (TRD) — Implementasi Moving Average Inventory Costing
## Fit Motor Webbase

| Dokumen | Keterangan |
|---|---|
| Nama | PRD Teknis — Moving Average Inventory Costing |
| Pasangan | `docs/PRD_Moving_Average_Fit_Motor_Webbase_v1.md` (PRD bisnis v1.0). Dokumen ini **tidak mengubah kebijakan bisnis**; ia menerjemahkannya ke desain teknis untuk web base yang ada. |
| Turunan dari | `docs/planning/PLANNING_MOVING_AVERAGE_WEBBASE_2026-10-05.md` (roadmap/task), `docs/planning/ANALISIS_HPP_FIFO_2026-10-05.md` (kondisi HPP saat ini) |
| Ditujukan kepada | Programmer, QA, DBA, DevOps (Rafi), reviewer |
| Versi / Status | **v0.1 — DRAFT teknis, menunggu keputusan §17.** Belum ada yang diimplementasikan. |
| Tanggal | 2026-10-05 |
| Konvensi | **MUST/SHALL** = wajib; **SHOULD** = disarankan; **MAY** = opsional. ID persyaratan: `TR-xxx`. ID keputusan: `D-xx`. ID temuan: `P-xx` (lihat planning §3). |

> **Cara membaca.** §1–2 konteks, §3 arsitektur, §4 data, §5 algoritma (inti), §6 kontrak service, §7 integrasi per modul, §8–§12 kontrol/laporan/UI, §13 migrasi dan cutover, §14 non-fungsional, §15 pengujian, §16 rilis, §17 keputusan terbuka dan temuan di PRD bisnis, §18 traceability, lampiran.

---

## 1. Tujuan, Ruang Lingkup, Asumsi

### 1.1 Tujuan teknis

1. Menyediakan **satu pintu posting persediaan** (`InventoryService`) yang menghitung Moving Average per item per lokasi, mencatat HPP pada setiap transaksi keluar, dan menolak stok minus secara atomik.
2. Menyediakan **ledger bernilai yang append-only** dengan jejak audit penuh, tanpa mengubah/menghapus transaksi historis (forward-only).
3. Melakukan transisi **tanpa big-bang**: mode `off → shadow → live` per cabang, dengan kompatibilitas pembaca lama (`tbstok`, `view_stok_master`).
4. Menutup temuan HPP saat ini: `tblpenjualan_detail.harga_pokok = 0`, `tblservis_barang` tanpa HPP, `tblitem.hargapokok` basi.

### 1.2 Di dalam cakupan

Inventory core (average, ledger, saldo), Purchasing (beli, retur, bonus, replacement, klaim), Sales (penjualan, retur, cancel/void, antar cabang), Service (barang via WO, retur, garansi), Transfer + transit, Koreksi maju + maker-checker, periode, opname, scrap/pemakaian internal, revaluasi, jurnal akuntansi persediaan, laporan, migrasi dan cutover.

### 1.3 Di luar cakupan

Produksi/Assembly, backdate/recalculation berantai, konsinyasi (BR-20), bundling/unbundling, pricing engine, perubahan modul Hutang/Piutang selain titik integrasi (§7.12), migrasi tabel FIFO Access.

### 1.4 Asumsi

| # | Asumsi | Sumber |
|---|---|---|
| A1 | Satu database terpusat, ±200 transaksi/hari seluruh lokasi → pemrosesan **sinkron**, tanpa queue | PRD bisnis §2.1 |
| A2 | Lokasi = 5 kode kanonik di `tbcabang.kode_cabang`: `PST` (Gudang Pusat), `PESALAKAN`, `PACUL`, `CIKDITIRO`, `TRAYEMAN` | DB lokal; keputusan D-01 |
| A3 | Stack: PHP + `mysqli` prosedural, MySQL/MariaDB InnoDB, Apache (Laragon lokal; **shared hosting tanpa Docker** di produksi) | `CLAUDE.md`, `config/koneksi.php` |
| A4 | Semua tabel inti sudah InnoDB (kecuali `tbjabatan`); row lock dan transaksi DB dapat dipakai | DB lokal |
| A5 | Produksi belum diukur; angka lokal adalah snapshot dev | Planning §8 |

### 1.5 Batasan lingkungan yang memengaruhi desain

| Batasan | Konsekuensi desain |
|---|---|
| `mysqli_report(MYSQLI_REPORT_OFF)` global (`config/koneksi.php`) → query gagal **diam-diam** | Service MUST mengaktifkan mode exception **lokal** (`MYSQLI_REPORT_ERROR \| MYSQLI_REPORT_STRICT`) di dalam transaksi dan memulihkan setelahnya; atau cek return setiap query |
| `error_reporting(E_ERROR \| E_PARSE)`, `display_errors=0` | Service MUST menulis log sendiri (`inv_audit_log` / `error_log`), tidak mengandalkan notice PHP |
| Koneksi `latin1` (`mysqli_set_charset($koneksi,'latin1')`) | Tabel baru MUST memakai charset/collation **sama** dengan `tblitem`/`tbstok` agar JOIN tidak mismatch (preseden: `view_laporan_cancel_servis` 3,2s→1ms, commit `0021419`). Migrasi membaca charset dari `information_schema` (§4.1) |
| Kode legacy menyusun SQL dengan string concat (`mysqli_real_escape_string`) | Service MUST memakai **prepared statement**; tidak boleh menerima SQL mentah dari pemanggil |
| `$_SESSION['_cabang']` = lokasi aktif user; `$_nama` = nama user | Service menerima `kd_cabang` dan `user` sebagai **argumen eksplisit**; tidak membaca session sendiri (agar bisa dites CLI) |
| Produksi: runner migrasi dijalankan manual lewat browser/CLI oleh Rafi | Semua DDL idempotent (`IF NOT EXISTS`) dan reversible (`up`/`down`) |
| MySQL versi produksi belum diketahui | Hindari fitur ≥8.0 yang tidak wajib (CHECK, window function, generated column). Jika tersedia, boleh sebagai pengaman tambahan |

---

## 2. Kondisi Sistem Saat Ini (As-Is, Ringkas)

Detail ada di planning §3 dan analisis FIFO §2. Ringkasan yang relevan untuk desain:

| Aspek | Kondisi | Dampak desain |
|---|---|---|
| Saldo | `view_stok_master` = `SUM(masuk-keluar) GROUP BY kd_cabang,no_item` dari `tbstok` | Dipertahankan sebagai antarmuka baca lama; definisinya akan diganti saat `live` (§13.5) |
| `tbstok` | `id,kd_cabang,keluar,keterangan,masuk,no_item,no_transaksi,tanggal,tipe,` qty `int`; tanpa nilai, tanpa `created_at`; indeks hanya `PRIMARY` dan `idx_sync_key(no_item,kd_cabang,no_transaksi)` | Tidak bisa jadi ledger bernilai (§4 opsi A) |
| Tipe `tbstok.tipe` | 1 stok awal, 2 pembelian **dan** terima antar cabang, 3 penjualan **dan** kirim antar cabang, 4 servis, 5/6 stok masuk/keluar manual (dugaan), 7 retur beli, 8 retur jual, 9 retur servis | Ambigu; ledger baru memakai `trx_type` eksplisit (§5.9) |
| Kunci lokasi | `tbstok.kd_cabang` campur (`CDT/PCL/PSL/PESALAKAN/PST/001/''`), `tbcabang` kode panjang | Normalisasi sebelum apa pun (§13.1) |
| Saldo | 6.179 dari 6.255 pasangan item–cabang negatif (lokal) | BR-05 hanya boleh aktif setelah opening stock (§13.2) |
| HPP | `tblpenjualan_detail.harga_pokok='0'` di semua jalur insert; `tblservis_barang` tanpa kolom HPP; `tblitem.hargapokok` hanya berubah via edit manual/sync Access | Snapshot HPP per baris transaksi (§7) |
| Transaksi DB | Antar cabang (`pengadaan_antarcab_proses/terima.php`) dan retur pembelian (`save_retur_pembelian.php`) **sudah** memakai `mysqli_begin_transaction`; penjualan/pembelian/servis **tidak** | Pola yang sudah ada dipakai ulang; sisanya dibungkus transaksi |
| Validasi stok | Saat item masuk keranjang (`view_stok_master`), tidak diulang saat simpan, tanpa lock | Re-validasi di bawah lock saat posting (§5.2) |
| Edit/hapus | `pembelian_edit_item.php` mengubah harga; DELETE `tbstok` di `retur_pembelian_batal.php:41`, `retur_penjualan_batal.php:35`, `_include_access_sync.php:1101` | Diganti transaksi pembalik (§7.5, §13.6) |
| Approval | Pola ada: `tblpo_approval_log`, `tblpr_approval_log`, `tb_master_approval_pembelian`, `retur_servis_approve.php` | Dipakai ulang untuk maker-checker (§8) |
| RBAC | `requirePermission($koneksi,$id_user,$perm)`, `hasPermission()`, menu di `menu_config.php` (`'permission' => 'xxx_read'`) | Permission `inv_*` baru (§8.2) |
| Antar cabang | `tblorder_antarcab_header(status: dikirim/terkirim/selesai, jenis: push/pull, kd_cabang_asal/tujuan, total_nilai)` + `_detail(qty_request,qty_kirim,qty_terima,harga_pokok,subtotal)` | Transit dibangun di atas alur ini (§5.5, §7.8) |

---

## 3. Arsitektur Target

### 3.1 Komponen

```
┌────────────────────────── Lapisan UI / Modul (PHP halaman) ──────────────────────────┐
│ pembelian_*  penjualan_*  servis-*  retur_*  pengadaan_antarcab_*  stok_masuk/keluar  │
│ inv_koreksi_*  inv_opname_*  inv_transit  inv_period  inv_config  laporan inv_*       │
└───────────────┬───────────────────────────────────────────────────────────────┬──────┘
                │ memanggil (hanya lewat facade)                                │ baca
                ▼                                                               ▼
┌──────────────────────── app/lib/inventory/ (InventoryService) ───────────┐  ┌───────────────┐
│ InventoryService   (facade, kontrak publik §6)                           │  │ View kompat   │
│  ├─ AverageCalculator  (rumus murni, tanpa DB, mudah diuji)              │  │ view_stok_    │
│  ├─ LedgerRepository   (insert append-only, idempotency)                 │  │ master, kartu │
│  ├─ BalanceRepository  (SELECT … FOR UPDATE, upsert saldo)               │  └───────▲───────┘
│  ├─ TransferService    (ship/receive/return, transit)                    │          │
│  ├─ CorrectionService  (BR-21..25, PPV, maker-checker)                   │   proyeksi (satu transaksi)
│  ├─ PeriodGuard        (open/closed/locked)                              │          │
│  ├─ ProjectionWriter   (tulis tbstok kompat)  ───────────────────────────┼──────────┘
│  ├─ JournalPoster      (Fase 5, butuh COA)                               │
│  └─ AuditLogger        (inv_audit_log)                                   │
└───────────────────────────────────┬──────────────────────────────────────┘
                                    ▼
        inv_balance · inv_ledger · inv_avg_history · inv_transfer(+line) · inv_ppv_log ·
        inv_claim_receivable · inv_correction_request · inv_audit_log · inv_config ·
        inv_period · inv_location(+alias) · inv_journal(+line) · inv_recon_run
```

### 3.2 Tata letak berkas (usulan)

```
app/lib/inventory/
  InventoryService.php        facade + fungsi publik (§6)
  AverageCalculator.php       fungsi murni: applyIn(), applyOut(), round2(), round6()
  LedgerRepository.php        insertLedger(), existsIdempotent()
  BalanceRepository.php       lockOrCreate(), update()
  TransferService.php
  CorrectionService.php
  PeriodGuard.php
  ProjectionWriter.php        tbstok kompat
  JournalPoster.php           (Fase 5)
  AuditLogger.php
  InventoryException.php      kode error INV-xxx (§9.3)
  inv_bootstrap.php           autoload sederhana (tanpa composer; shared hosting)
app/inv_*.php                 halaman UI (§12)
db/migrations/2026-10-XX_run_inv_*.php      runner reversible
tests/inventory/run.php       test runner CLI (tanpa PHPUnit)
tests/inventory/cases/*.php   fixture numerik §15
docs/ops/inventory-runbook.md
```

Autoload sederhana (`require_once` berurutan) karena produksi tanpa composer.

### 3.3 Prinsip desain (MUST)

| ID | Prinsip |
|---|---|
| TR-001 | **Satu pintu.** Tidak ada `INSERT/UPDATE/DELETE` ke `tbstok`, `tblitem.quantity`, `inv_*` di luar `InventoryService` setelah cutover cabang bersangkutan. |
| TR-002 | **Atomik.** Satu pemanggilan posting = satu transaksi DB (ledger + saldo + history + proyeksi `tbstok` + audit). Gagal sebagian → rollback penuh. |
| TR-003 | **Append-only.** `inv_ledger` tidak boleh di-UPDATE/DELETE (dijaga trigger dan aplikasi, §4.4). Koreksi = baris baru yang merujuk baris asal. |
| TR-004 | **Idempotent.** `UNIQUE(ref_type,ref_no,ref_line,trx_type,kd_cabang)`; posting kedua dengan kunci sama mengembalikan hasil pertama (bukan error kritis) atau `INV-301`, sesuai flag `on_duplicate`. |
| TR-005 | **Forward-only.** Tidak ada penulisan ulang HPP historis. Tanggal efektif posting = tanggal server saat posting (§5.10). |
| TR-006 | **Deterministik.** Perhitungan angka hanya di `AverageCalculator` (fungsi murni), dengan pembulatan tunggal (§5.1). |
| TR-007 | **Dapat dimatikan.** Flag mode per cabang (`off/shadow/live`); `off` = perilaku lama persis. |
| TR-008 | **Dapat dirujuk balik.** Setiap baris ledger menyimpan `ref_type/ref_no/ref_line` ke dokumen sumber dan `origin_ledger_id`/`reversal_of` untuk koreksi. |

### 3.4 Mode operasi per cabang

`inv_config` (`config_key='inv_mode:<kd_cabang>'`) bernilai:

| Mode | Perilaku service | Perilaku modul pemanggil |
|---|---|---|
| `off` | Pemanggilan menjadi no-op (mengembalikan `skipped`) | Jalur lama menulis `tbstok` langsung |
| `shadow` | Menghitung dan menulis `inv_*`, **tidak** menolak stok minus (hanya mencatat `would_reject`), **tidak** menulis proyeksi `tbstok` | Jalur lama tetap sumber kebenaran; service dipanggil **setelah** jalur lama sukses |
| `live` | Sumber kebenaran. Menolak stok minus, menulis proyeksi `tbstok`, memblok jalur tulis lama | Modul memanggil service saja |

Transisi mode hanya lewat halaman admin + audit (§12). `live → shadow` diizinkan (rollback darurat) dan dicatat.

---

## 4. Model Data

### 4.1 Aturan umum DDL

- Engine **InnoDB**. Charset/collation: **ikuti `tblitem`**. Runner membaca:
  ```sql
  SELECT CCSA.CHARACTER_SET_NAME cs, T.TABLE_COLLATION coll
  FROM information_schema.TABLES T
  JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA ON CCSA.COLLATION_NAME=T.TABLE_COLLATION
  WHERE T.TABLE_SCHEMA=DATABASE() AND T.TABLE_NAME='tblitem';
  ```
  lalu menyisipkan ke DDL (`DEFAULT CHARSET=<cs> COLLATE=<coll>`). Kolom kunci (`kd_cabang`, `no_item`) MUST bertipe/panjang kompatibel: `kd_cabang VARCHAR(10)`, `no_item VARCHAR(50)` (`tblitem.noitem` = `varchar(50)`, `tbstok.no_item` = `varchar(30)`).
- Uang: `DECIMAL(18,2)`. Biaya satuan internal: `DECIMAL(18,6)`. Kuantitas: `DECIMAL(14,3)` (konversi satuan; tampil integer bila `.000`).
- Semua tabel punya `created_at DATETIME`/`posted_at DATETIME` diisi **dari aplikasi** (`date('Y-m-d H:i:s')` dengan `date_default_timezone_set('Asia/Jakarta')`), bukan `NOW()` server shared hosting yang zona waktunya tidak pasti.
- Nama tabel prefiks `inv_`. Tidak ada FOREIGN KEY ke tabel legacy (legacy `latin1`, banyak data kotor); integritas dijaga aplikasi + job rekonsiliasi. FK hanya antar tabel `inv_*` yang aman.
- Migrasi: `db/migrations/YYYY-MM-DD_run_inv_<nama>.php [up|down]`, `up` idempotent, `down` hanya boleh bila tabel kosong atau setelah backup `bak_YYYYMMDD_*`.

### 4.2 `inv_location` dan `inv_location_alias`

```sql
CREATE TABLE IF NOT EXISTS inv_location (
  kode        VARCHAR(10) NOT NULL,            -- = tbcabang.kode_cabang (kanonik)
  nama        VARCHAR(100) NOT NULL,
  is_pusat    TINYINT(1) NOT NULL DEFAULT 0,
  aktif       TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (kode)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inv_location_alias (
  alias       VARCHAR(10) NOT NULL,            -- CDT, PCL, PSL, TRM, 001, '' ...
  kode        VARCHAR(10) NOT NULL,
  catatan     VARCHAR(100) NULL,
  PRIMARY KEY (alias),
  KEY idx_kode (kode),
  CONSTRAINT fk_loc_alias FOREIGN KEY (kode) REFERENCES inv_location(kode)
) ENGINE=InnoDB;
```

Seed awal (pemetaan dari `_include_access_sync.php:2817`): `PSL→PESALAKAN`, `PCL→PACUL`, `CDT→CIKDITIRO`, `TRM→TRAYEMAN`. Arti `001` dan `''` menunggu D-01.

### 4.3 `inv_balance` — saldo per item per lokasi

```sql
CREATE TABLE IF NOT EXISTS inv_balance (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_cabang      VARCHAR(10) NOT NULL,
  no_item        VARCHAR(50) NOT NULL,
  qty_on_hand    DECIMAL(14,3) NOT NULL DEFAULT 0,
  total_value    DECIMAL(18,2) NOT NULL DEFAULT 0,   -- otoritatif, dilacak per delta
  average_cost   DECIMAL(18,6) NOT NULL DEFAULT 0,   -- internal 6 dp; tampil 2 dp (round half up)
  last_ledger_id BIGINT UNSIGNED NULL,
  version        INT UNSIGNED NOT NULL DEFAULT 0,    -- naik tiap posting (deteksi anomali)
  updated_at     DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_loc_item (kd_cabang, no_item)
) ENGINE=InnoDB;
-- Jika MySQL >= 8.0.16 / MariaDB >= 10.2.1, tambahkan pengaman:
-- ALTER TABLE inv_balance ADD CONSTRAINT chk_qty_nonneg CHECK (qty_on_hand >= 0), ADD CONSTRAINT chk_val_nonneg CHECK (total_value >= 0);
```

Invarian (diperiksa job rekonsiliasi §13.7): `qty_on_hand = Σ(qty_in − qty_out)` ledger; `total_value = Σ value_delta` ledger; `qty_on_hand = 0 ⇒ total_value = 0`.

### 4.4 `inv_ledger` — kartu stok bernilai, append-only

```sql
CREATE TABLE IF NOT EXISTS inv_ledger (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_cabang        VARCHAR(10) NOT NULL,
  no_item          VARCHAR(50) NOT NULL,
  trx_type         VARCHAR(24) NOT NULL,        -- katalog §5.9
  ref_type         VARCHAR(24) NOT NULL,        -- PEMBELIAN, PENJUALAN, SERVIS, RETUR_BELI, ...
  ref_no           VARCHAR(40) NOT NULL,        -- nomor dokumen sumber
  ref_line         INT NOT NULL DEFAULT 0,      -- nobaris / urutan item di dokumen
  qty_in           DECIMAL(14,3) NOT NULL DEFAULT 0,
  qty_out          DECIMAL(14,3) NOT NULL DEFAULT 0,
  unit_cost        DECIMAL(18,6) NOT NULL DEFAULT 0,  -- biaya satuan efektif transaksi ini
  value_delta      DECIMAL(18,2) NOT NULL,            -- +masuk / -keluar (nilai transaksi)
  qty_before       DECIMAL(14,3) NOT NULL,
  qty_after        DECIMAL(14,3) NOT NULL,
  value_before     DECIMAL(18,2) NOT NULL,
  value_after      DECIMAL(18,2) NOT NULL,
  avg_before       DECIMAL(18,6) NOT NULL,
  avg_after        DECIMAL(18,6) NOT NULL,
  transaction_date DATE NOT NULL,               -- tanggal bisnis (info)
  correction_date  DATE NOT NULL,               -- tanggal posting efektif (BR-06/BR-12)
  posted_at        DATETIME NOT NULL,
  is_correction    TINYINT(1) NOT NULL DEFAULT 0,
  is_cancel        TINYINT(1) NOT NULL DEFAULT 0,
  origin_ledger_id BIGINT UNSIGNED NULL,        -- baris yang dikoreksi/dibatalkan
  reversal_of      BIGINT UNSIGNED NULL,
  note             VARCHAR(255) NULL,
  created_by       VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_idem (ref_type, ref_no, ref_line, trx_type, kd_cabang),
  KEY idx_card (kd_cabang, no_item, id),
  KEY idx_ref (ref_no),
  KEY idx_date (kd_cabang, correction_date)
) ENGINE=InnoDB;
```

Penegakan append-only (MUST, selain disiplin aplikasi):

```sql
DELIMITER $$
CREATE TRIGGER trg_inv_ledger_no_update BEFORE UPDATE ON inv_ledger
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='INV-501 inv_ledger append-only';
END$$
CREATE TRIGGER trg_inv_ledger_no_delete BEFORE DELETE ON inv_ledger
FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='INV-501 inv_ledger append-only';
END$$
DELIMITER ;
```

Catatan produksi: trigger ada precedennya (`trg_alarm_harga_beli`, `trg_after_service_bayar`), tapi `DEFINER` pernah menyebabkan masalah (`db/migrations/2026-07-04_fix_trigger_bayar_missing_function.sql`). Runner MUST membuat trigger tanpa `DEFINER` eksplisit dan mengujinya.

### 4.5 `inv_avg_history`

```sql
CREATE TABLE IF NOT EXISTS inv_avg_history (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_cabang    VARCHAR(10) NOT NULL,
  no_item      VARCHAR(50) NOT NULL,
  avg_before   DECIMAL(18,6) NOT NULL,
  avg_after    DECIMAL(18,6) NOT NULL,
  trigger_type VARCHAR(24) NOT NULL,
  trigger_ref  VARCHAR(40) NOT NULL,
  ledger_id    BIGINT UNSIGNED NOT NULL,
  is_correction TINYINT(1) NOT NULL DEFAULT 0,
  changed_by   VARCHAR(50) NOT NULL,
  changed_at   DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_item (kd_cabang, no_item, id)
) ENGINE=InnoDB;
```

Baris hanya ditulis bila `avg_after <> avg_before` (BR-11), termasuk koreksi.

### 4.6 Transfer dan transit

```sql
CREATE TABLE IF NOT EXISTS inv_transfer (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_transfer   VARCHAR(40) NOT NULL,           -- = tblorder_antarcab_header.no_order
  from_lokasi   VARCHAR(10) NOT NULL,
  to_lokasi     VARCHAR(10) NOT NULL,
  status        ENUM('in_transit','partially_received','received','returned','written_off') NOT NULL,
  shipped_at    DATETIME NOT NULL,
  received_at   DATETIME NULL,
  closed_at     DATETIME NULL,
  shipped_by    VARCHAR(50) NOT NULL,
  received_by   VARCHAR(50) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_no (no_transfer),
  KEY idx_status (status, shipped_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inv_transfer_line (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  transfer_id   BIGINT UNSIGNED NOT NULL,
  ref_line      INT NOT NULL,
  no_item       VARCHAR(50) NOT NULL,
  qty_shipped   DECIMAL(14,3) NOT NULL,
  qty_received  DECIMAL(14,3) NOT NULL DEFAULT 0,
  qty_returned  DECIMAL(14,3) NOT NULL DEFAULT 0,
  qty_written_off DECIMAL(14,3) NOT NULL DEFAULT 0,
  unit_cost     DECIMAL(18,6) NOT NULL,         -- avg asal saat kirim
  value_shipped DECIMAL(18,2) NOT NULL,         -- otoritatif
  value_received DECIMAL(18,2) NOT NULL DEFAULT 0,
  value_returned DECIMAL(18,2) NOT NULL DEFAULT 0,
  value_written_off DECIMAL(18,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_line (transfer_id, ref_line),
  CONSTRAINT fk_tl_t FOREIGN KEY (transfer_id) REFERENCES inv_transfer(id)
) ENGINE=InnoDB;
```

Invarian: `qty_shipped = qty_received + qty_returned + qty_written_off + qty_in_transit`; `value_shipped = value_received + value_returned + value_written_off + value_in_transit`. Saldo "Barang dalam Perjalanan" = Σ(value_shipped − value_received − value_returned − value_written_off) untuk transfer belum tutup.

### 4.7 `inv_ppv_log` (Selisih Harga Pembelian) dan `inv_claim_receivable`

```sql
CREATE TABLE IF NOT EXISTS inv_ppv_log (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_cabang        VARCHAR(10) NOT NULL,
  no_item          VARCHAR(50) NOT NULL,
  correction_id    BIGINT UNSIGNED NOT NULL,       -- inv_correction_request.id
  ref_pembelian    VARCHAR(40) NOT NULL,
  qty_purchased    DECIMAL(14,3) NOT NULL,
  qty_remaining_part DECIMAL(14,3) NOT NULL,       -- R: porsi masuk average
  qty_sold_part    DECIMAL(14,3) NOT NULL,         -- S: porsi ke PPV
  price_old        DECIMAL(18,6) NOT NULL,
  price_new        DECIMAL(18,6) NOT NULL,
  price_difference DECIMAL(18,6) NOT NULL,         -- new - old (boleh negatif)
  value_to_inventory DECIMAL(18,2) NOT NULL,       -- delta × R
  total_variance   DECIMAL(18,2) NOT NULL,         -- delta × S → akun Selisih Harga Pembelian
  period           CHAR(7) NOT NULL,               -- YYYY-MM periode posting
  posted_to_account VARCHAR(30) NULL,
  ledger_id        BIGINT UNSIGNED NULL,
  created_at       DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_period (period, kd_cabang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inv_claim_receivable (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_cabang   VARCHAR(10) NOT NULL,
  no_item     VARCHAR(50) NOT NULL,
  qty         DECIMAL(14,3) NOT NULL,
  value       DECIMAL(18,2) NOT NULL,
  supplier_id VARCHAR(30) NOT NULL,
  claim_ref   VARCHAR(40) NOT NULL,
  status      ENUM('pending','settled','rejected') NOT NULL DEFAULT 'pending',
  ledger_id   BIGINT UNSIGNED NULL,
  settled_at  DATETIME NULL,
  created_at  DATETIME NOT NULL,
  created_by  VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_claim (claim_ref, no_item),
  KEY idx_status (status, supplier_id)
) ENGINE=InnoDB;
```

### 4.8 Maker-checker dan audit

```sql
CREATE TABLE IF NOT EXISTS inv_correction_request (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_cabang     VARCHAR(10) NOT NULL,
  type          VARCHAR(24) NOT NULL,   -- ADJ_PLUS, ADJ_MINUS, PRICE_CORRECTION, OPNAME, SCRAP, INTERNAL_USE, REVALUATION, DEBIT_NOTE, CREDIT_NOTE
  ref_origin    VARCHAR(40) NULL,       -- dokumen yang dikoreksi (wajib utk PRICE_CORRECTION & ADJ)
  payload_json  TEXT NOT NULL,          -- parameter terstruktur (§5.8), divalidasi saat submit
  reason_code   VARCHAR(24) NOT NULL,
  reason_text   VARCHAR(500) NOT NULL,
  est_value     DECIMAL(18,2) NOT NULL DEFAULT 0,  -- dampak nilai (dipakai ambang approval)
  status        ENUM('draft','submitted','checked','approved','rejected','posted','cancelled') NOT NULL DEFAULT 'draft',
  maker         VARCHAR(50) NOT NULL,
  maker_at      DATETIME NOT NULL,
  checker       VARCHAR(50) NULL,
  checker_at    DATETIME NULL,
  approver      VARCHAR(50) NULL,
  approver_at   DATETIME NULL,
  decision_note VARCHAR(500) NULL,
  ledger_ref    VARCHAR(40) NULL,       -- ref_no ledger hasil posting
  PRIMARY KEY (id),
  KEY idx_status (status, kd_cabang),
  KEY idx_origin (ref_origin)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inv_audit_log (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  at          DATETIME NOT NULL,
  actor       VARCHAR(50) NOT NULL,       -- WHO
  action      VARCHAR(40) NOT NULL,       -- WHAT
  ref_type    VARCHAR(24) NULL,
  ref_no      VARCHAR(40) NULL,
  kd_cabang   VARCHAR(10) NULL,
  reason      VARCHAR(500) NULL,          -- WHY
  before_json TEXT NULL,                  -- BEFORE
  after_json  TEXT NULL,                  -- AFTER
  approval_id BIGINT UNSIGNED NULL,       -- APPROVAL → inv_correction_request.id
  ip          VARCHAR(45) NULL,
  PRIMARY KEY (id),
  KEY idx_ref (ref_type, ref_no),
  KEY idx_at (at)
) ENGINE=InnoDB;
```

### 4.9 Konfigurasi dan periode

```sql
CREATE TABLE IF NOT EXISTS inv_config (
  config_key  VARCHAR(60) NOT NULL,
  value       VARCHAR(255) NOT NULL,
  updated_by  VARCHAR(50) NOT NULL,
  updated_at  DATETIME NOT NULL,
  PRIMARY KEY (config_key)
) ENGINE=InnoDB;
-- Seed:
--  inv_mode:PST / inv_mode:PESALAKAN / ... = 'off'
--  opname_hq_threshold     = '0'      (V-12, default Rp0 sesuai PRD §15)
--  transit_alert_days      = '3'      (V-14)
--  adj_branch_threshold    = '0'      (D-06)
--  round_policy            = 'half_up_2'

CREATE TABLE IF NOT EXISTS inv_period (
  period     CHAR(7) NOT NULL,          -- YYYY-MM
  kd_cabang  VARCHAR(10) NOT NULL,
  status     ENUM('open','closed','locked') NOT NULL DEFAULT 'open',
  closed_by  VARCHAR(50) NULL,
  closed_at  DATETIME NULL,
  locked_by  VARCHAR(50) NULL,
  locked_at  DATETIME NULL,
  PRIMARY KEY (period, kd_cabang)
) ENGINE=InnoDB;
```

### 4.10 Jurnal (Fase 5, bergantung D-07)

```sql
CREATE TABLE IF NOT EXISTS inv_account_map (
  event_key    VARCHAR(30) NOT NULL,     -- PURCHASE_IN, COGS, ... (§10.2)
  side         ENUM('D','K') NOT NULL,
  account_code VARCHAR(30) NOT NULL,
  PRIMARY KEY (event_key, side)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inv_journal (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  jdate       DATE NOT NULL,
  period      CHAR(7) NOT NULL,
  kd_cabang   VARCHAR(10) NOT NULL,
  event_key   VARCHAR(30) NOT NULL,
  ref_type    VARCHAR(24) NOT NULL,
  ref_no      VARCHAR(40) NOT NULL,
  ledger_id   BIGINT UNSIGNED NULL,
  posted_at   DATETIME NOT NULL,
  posted_by   VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ev (event_key, ref_type, ref_no, ledger_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inv_journal_line (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  journal_id  BIGINT UNSIGNED NOT NULL,
  account_code VARCHAR(30) NOT NULL,
  debit       DECIMAL(18,2) NOT NULL DEFAULT 0,
  kredit      DECIMAL(18,2) NOT NULL DEFAULT 0,
  no_item     VARCHAR(50) NULL,
  PRIMARY KEY (id),
  KEY idx_j (journal_id),
  CONSTRAINT fk_jl_j FOREIGN KEY (journal_id) REFERENCES inv_journal(id)
) ENGINE=InnoDB;
```

Invarian MUST: `Σ debit = Σ kredit` per `journal_id` (dicek aplikasi sebelum commit).

### 4.11 Rekonsiliasi dan batch opening

```sql
CREATE TABLE IF NOT EXISTS inv_recon_run (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_at       DATETIME NOT NULL,
  scope        VARCHAR(30) NOT NULL,      -- 'daily','cutover:PST',...
  kd_cabang    VARCHAR(10) NULL,
  checks_json  TEXT NOT NULL,             -- hasil tiap cek
  mismatches   INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id), KEY idx_at (run_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inv_opening_batch (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kd_cabang    VARCHAR(10) NOT NULL,
  uploaded_by  VARCHAR(50) NOT NULL,
  uploaded_at  DATETIME NOT NULL,
  file_name    VARCHAR(120) NOT NULL,
  rows_total   INT NOT NULL,
  rows_ok      INT NOT NULL,
  status       ENUM('validated','committed','rejected') NOT NULL,
  committed_at DATETIME NULL,
  PRIMARY KEY (id), KEY idx_loc (kd_cabang)
) ENGINE=InnoDB;
```

### 4.12 Perubahan pada tabel legacy

| Tabel | Perubahan | Tujuan | Risiko |
|---|---|---|---|
| `tblservis_barang` | `ADD harga_pokok DOUBLE NOT NULL DEFAULT 0`, `ADD ledger_id BIGINT NULL` | Snapshot HPP servis (P4/T2) | Rendah; `DEFAULT` agar INSERT lama tetap jalan |
| `tblpenjualan_detail` | `ADD ledger_id BIGINT NULL` (kolom `harga_pokok` sudah ada) | Rujukan balik ke ledger | Rendah |
| `tblpembelian_detail` | `ADD qty_bonus DECIMAL(14,3) NOT NULL DEFAULT 0`, `ADD unit_cost_eff DOUBLE NULL`, `ADD ledger_id BIGINT NULL` | Bonus proporsional (BR-17), biaya efektif | Rendah |
| `tbstok` | `MODIFY masuk/keluar DECIMAL(14,3) NOT NULL` | Proyeksi qty pecahan (konversi satuan) | Sedang: diuji terhadap `view_stok_master` dan semua `SUM`; ditunda sampai Fase 4 bila konversi satuan ditunda |
| `tbstok` | `ADD created_at DATETIME NULL`, `ADD ledger_id BIGINT NULL`, `ADD INDEX idx_cab_item (kd_cabang,no_item)` | Audit + performa saldo | Rendah; `ALTER` pada 320 rb baris diuji waktunya dulu |
| `view_stok_master` | Didefinisikan ulang (§13.5) | Sumber saldo mengikuti mode | Sedang; dibuat di migrasi tersendiri dengan backup definisi lama |

---

## 5. Algoritma dan Aturan Perhitungan (Inti)

### 5.1 Model angka dan pembulatan

Keputusan teknis yang direkomendasikan (menunggu D-08, lihat §17):

| Besaran | Tipe | Aturan |
|---|---|---|
| `qty` | `DECIMAL(14,3)` | Jumlah mutlak > 0 pada input; stok tidak boleh < 0 |
| `average_cost` | `DECIMAL(18,6)` | **Hanya dihitung ulang saat transaksi MASUK**; pada transaksi KELUAR **tidak diubah** (BR-02 terpenuhi persis, bukan "kurang lebih") |
| `total_value` | `DECIMAL(18,2)` | **Otoritatif.** Dilacak sebagai jumlah delta (+masuk, −keluar), bukan diturunkan dari `qty × average` |
| Tampilan average | 2 desimal | `round_half_up(average_cost, 2)` (V-09) |
| `value_delta` baris | `DECIMAL(18,2)` | `round_half_up(qty × unit_cost, 2)` per baris |

`round_half_up(x, d)`: implementasi MUST memakai aritmetika string/`bcmath` atau `round($x, $d, PHP_ROUND_HALF_UP)` pada nilai yang sudah dinormalisasi; MUST NOT memakai `floor($x*100+0.5)/100` pada `float` biner tanpa koreksi (risiko `10.005 → 10.00`). Satu fungsi tunggal `AverageCalculator::round2()` dipakai semua modul (Test 15). Bila ekstensi `bcmath` tak tersedia di hosting, pakai representasi integer (sen / mikro-rupiah, Lampiran A); ini diverifikasi dengan fixture di §15.

Alasan memilih model ini: ia mereproduksi angka PRD bisnis §7 persis (§15.2) dan menjaga `total_value` konsisten dengan jurnal (jurnal memakai delta yang sama).

### 5.2 `postOut` — transaksi keluar (BR-02, BR-03, BR-05)

Masukan: `(lokasi, item, qty, ref_type, ref_no, ref_line, trx_type, transaction_date, user, opts)`.

```
BEGIN TX
  guard: mode(lokasi) in {shadow,live}; period(lokasi, hari ini) status allows posting (§9.4)
  validate: qty > 0; item exists (tblitem); lokasi valid
  idempotency: if exists ledger(ref_type,ref_no,ref_line,trx_type,lokasi) → return stored result
  bal := SELECT … FROM inv_balance WHERE (lokasi,item) FOR UPDATE      -- buat dulu bila belum ada (§5.11)
  if mode == live AND bal.qty_on_hand < qty → ROLLBACK, throw INV-201 (tersedia=bal.qty_on_hand, diminta=qty)
  if qty == bal.qty_on_hand:  hpp_value := bal.total_value               -- flush sisa pembulatan
  else:                       hpp_value := round2(qty × bal.average_cost)
  hpp_value := min(hpp_value, bal.total_value)                           -- pengaman
  new_qty   := bal.qty_on_hand − qty
  new_value := bal.total_value − hpp_value
  new_avg   := bal.average_cost                                          -- TIDAK berubah (BR-02)
  insert ledger(qty_out=qty, unit_cost=hpp_value/qty (6dp), value_delta=−hpp_value, …)
  update inv_balance(qty,value,avg,version+1,last_ledger_id)
  [live] write tbstok projection (keluar=qty, tipe legacy §5.9)
  insert audit (jika aksi sensitif)
COMMIT
return { ledger_id, hpp_value, hpp_unit, qty_after, value_after, average_cost }
```

Catatan:
- `shadow`: bila stok tak cukup, **tidak** throw dan **tidak** menulis ledger (saldo tidak boleh negatif); service mencatat `inv_audit_log(action='SHADOW_WOULD_REJECT')`. Rinci di §13.4.
- Pemanggil menyimpan `hpp_unit` ke kolom snapshot (`tblpenjualan_detail.harga_pokok`, `tblservis_barang.harga_pokok`).

### 5.3 `postIn` — transaksi masuk (BR-01, BR-04)

Masukan: `(lokasi, item, qty, unit_cost | total_value, …)`. Tepat salah satu `unit_cost` atau `total_value` diberikan; yang lain diturunkan: `total_value = round2(qty × unit_cost)`; `unit_cost = total_value / qty`.

```
BEGIN TX
  guard, validate (qty>0, in_value>=0; in_value==0 hanya bila opts.bonus_confirmed — EC-03), idempotency
  bal := SELECT … FOR UPDATE
  new_qty   := bal.qty_on_hand + qty
  new_value := bal.total_value + in_value
  if bal.qty_on_hand == 0:  new_value := in_value ; residual := bal.total_value (→ dicatat sbg 'ROUNDING' bila ≠ 0)
  new_avg   := round6(new_value / new_qty)                               -- BR-04: bila qty lama 0 ⇒ avg = unit_cost
  insert ledger(qty_in=qty, unit_cost, value_delta=+in_value, …)
  insert avg_history bila new_avg ≠ avg_before
  update inv_balance; [live] write tbstok projection (masuk=qty)
COMMIT
```

Tipe masuk yang memakai `postIn`: pembelian, retur penjualan/servis/void/cancel (harga = HPP asal), adjustment (+), opname (+), penerimaan transfer, pembatalan transit (kembali ke asal), opening stock. Koreksi harga dan revaluasi memakai `postValueAdjust` (§5.7).

### 5.4 Penerapan pada kondisi khusus (rangkuman §6.2 PRD)

| Kondisi | Operasi | Rumus nilai masuk | Average |
|---|---|---|---|
| Pembelian pertama / stok 0 | `postIn` | `qty × harga` | `= harga` |
| Pembelian harga beda | `postIn` | `qty × harga_efektif` | `round6(new_value/new_qty)` |
| Retur pembelian | `postOut` (trx `PURCHASE_RETURN_OUT`) | HPP = `qty × avg` | tetap (BR-02) |
| Retur penjualan | `postIn` (`SALE_RETURN_IN`) | `round2(qty × hpp_unit_asal)` — `hpp_unit_asal` dibaca dari snapshot baris transaksi asal | Masuk rumus normal (BR-07) |
| Adjustment (+) | `postIn` | `qty × harga_input` (default `average_cost`) | normal |
| Adjustment (−), scrap, internal | `postOut` | `qty × avg` | tetap |
| Penerimaan transfer | `postIn` (`TRANSFER_IN`) | `value_received` dari `inv_transfer_line` | normal (BR-14) |
| Bonus supplier | `postIn` | `total_value = round2(qty_utama × harga_net)`, `qty = qty_utama + qty_bonus` | normal (BR-17) |

### 5.5 Transfer dan Barang dalam Perjalanan (BR-13/14/15)

**Kirim** `shipTransfer(no_transfer, from, to, lines[], user)`:

```
BEGIN TX
  untuk tiap line (diurutkan no_item ASC — urutan kunci konsisten, cegah deadlock):
    r := postOut(from, item, qty, ref_type='TRANSFER', ref_no=no_transfer, ref_line, trx_type='TRANSFER_OUT')
    inv_transfer_line(qty_shipped=qty, unit_cost=r.hpp_unit, value_shipped=r.hpp_value)
  inv_transfer(status='in_transit', shipped_at=now)
COMMIT
```
Lokasi tujuan **tidak disentuh**. Harga transfer = HPP asal (at cost, BR-14); untuk Gudang Pusat → Cabang sama dengan `average_cost` Pusat saat kirim.

**Terima** `receiveTransfer(no_transfer, lines[{ref_line, qty_received}], user)`:

```
BEGIN TX
  transfer := SELECT … FOR UPDATE (inv_transfer) ; status ∈ {in_transit, partially_received}
  untuk tiap line (urut no_item ASC):
    sisa := qty_shipped − qty_received − qty_returned − qty_written_off
    qty_received_now ≤ sisa  (INV-102 bila melebihi)
    v := (qty_received_now == sisa) ? (value_shipped − value_received − value_returned − value_written_off)
                                    : round2(value_shipped × qty_received_now / qty_shipped)
    postIn(to, item, qty_received_now, total_value=v, ref_type='TRANSFER', ref_no, ref_line, trx_type='TRANSFER_IN')
    line.qty_received += …, line.value_received += v
  status := semua line tuntas ? 'received' : 'partially_received'
COMMIT
```

**Kembali/gagal kirim** `returnTransfer(...)` (BR-15) — barang kembali ke asal:
`postIn(from, item, qty, total_value = nilai tersisa line, trx_type='TRANSFER_RETURN')`. **Catatan teknis (lihat §17.3):** bila di lokasi asal ada transaksi masuk lain selama transit, average asal dapat bergeser (rumus rata-rata tertimbang). Hasilnya **sama dengan** "tidak terpengaruh" (BR-15) hanya bila tidak ada transaksi masuk lain di antaranya.

**Selisih terima (kirim ≠ terima)**: sisa tidak otomatis hilang. Tetap `partially_received` sampai diputuskan: `returnTransfer` (kembali) atau `writeOffTransfer` (kerugian, memerlukan maker-checker, `trx_type='TRANSFER_LOSS'`, dibukukan sebagai kerugian). PRD bisnis tidak mengatur ini (D-15).

### 5.6 Cancel vs Void (BR-09)

| | Definisi teknis | Ledger |
|---|---|---|
| **Cancel** | Dokumen belum melewati titik "barang keluar fisik" (D-05) | Baris pembalik (`trx_type='CANCEL_IN'`, `is_cancel=1`, `reversal_of=<id>`): `postIn` pada `unit_cost = hpp_unit` baris asal. Laporan kartu stok **menyembunyikan pasangan** `is_cancel` secara default (memenuhi "tanpa jejak" pada tampilan, jejak tetap ada di tabel untuk audit) |
| **Void** | Sesudah barang keluar | `postIn` `trx_type='VOID_RETURN'` mengikuti BR-07 (HPP asal), `is_cancel=0` |

Kedua jalur memakai rumus rata-rata tertimbang. Penegasan teknis di §17.3: "kembali ke average semula" hanya terpenuhi bila tidak ada transaksi masuk lain setelah transaksi asal.

### 5.7 Koreksi maju (BR-21..25)

Prinsip: koreksi = **permintaan** (`inv_correction_request`) → maker-checker (§8) → eksekusi sebagai posting baru. Eksekusi hanya oleh `CorrectionService::execute($request_id)` setelah status `approved`.

**Stock Adjustment ±** (BR-21): `ADJ_PLUS` → `postIn`; `ADJ_MINUS` → `postOut`. Wajib `reason_code`, `ref_origin`. Tanggal posting = hari ini (BR-06).

**Koreksi Harga Pembelian** (BR-22/23) — masukan: `ref_pembelian`, `no_item`, `price_old`, `price_new`, `Q` (qty pada baris pembelian itu), lokasi.

```
delta := price_new − price_old                       -- boleh negatif
R     := min(qty_on_hand_now, Q)                      -- porsi yang "masih ada"   (asumsi D-16)
S     := Q − R                                        -- porsi yang "sudah terjual"
value_inv := round2(delta × R)                        -- masuk persediaan
variance  := round2(delta × S)                        -- ke Selisih Harga Pembelian

BEGIN TX  (lock inv_balance)
  if R > 0:  postValueAdjust(lokasi, item, +value_inv, qty tidak berubah)
             → new_value := value + value_inv ; new_avg := round6(new_value / qty_on_hand) ; ledger trx 'PRICE_CORR_INV'
             guard: new_value >= 0
  if S > 0:  inv_ppv_log(variance, period=YYYY-MM hari ini) ; ledger trx 'PRICE_CORR_PPV' (qty 0, value_delta 0, hanya jejak)
  catatan: HPP transaksi penjualan lama TIDAK diubah
COMMIT
```
Contoh angka (PRD Contoh 6) di §15.2 T8. Interaksi dengan Hutang: perubahan harga mengubah jumlah yang harus dibayar ke supplier; **bukan** diubah oleh service ini. Titik integrasi: Debit/Credit Note di modul Hutang (§7.12, D-17).

**Salah tanggal (BR-24)**: tidak ada operasi; hanya `inv_audit_log` (`action='DATE_ERROR_NOTE'`). **Salah lokasi (BR-25)**: dikoreksi dengan Transfer biasa (`shipTransfer`/`receiveTransfer`) bertanggal hari ini.

**Revaluasi** (§4.5 PRD): `postValueAdjust(lokasi, item, new_total_value − total_value)`; `qty` tetap; avg dihitung ulang; hanya permission `inv_revalue` (Finance HQ), approval wajib; alasan tertulis.

### 5.8 Bonus, replacement, klaim

- **Bonus (BR-17):** di form pembelian, tiap baris punya `qty` dan `qty_bonus`. `postIn(qty = qty + qty_bonus, total_value = round2(qty × harga_net))`. Validasi: `Σ in_value = total nilai faktur baris` (AC-09). `unit_cost_eff = total_value / (qty+qty_bonus)` disimpan di `tblpembelian_detail.unit_cost_eff`.
- **Replacement (BR-18):** dua dokumen terpisah: `PURCHASE_RETURN_OUT` (`postOut`) lalu `PURCHASE_IN` baru (`postIn`). Tautan opsional `ref_origin` agar laporan menampilkan pasangannya.
- **Klaim (BR-19):** `claimOut`: `postOut(trx_type='CLAIM_OUT')` + baris `inv_claim_receivable(status='pending', value = hpp_value)`. `settled` → tidak ada dampak ke persediaan (hanya jurnal Kas/Hutang vs Piutang Klaim). `rejected` → jurnal Piutang Klaim ke Beban, tanpa dampak persediaan.

### 5.9 Katalog `trx_type` dan pemetaan ke `tbstok.tipe` (proyeksi)

| `trx_type` | Arah | `ref_type` | `tbstok.tipe` proyeksi (kompat) | Catatan |
|---|---|---|---|---|
| `OPENING` | in | `OPENING` | `1` | Saldo awal go-live |
| `PURCHASE_IN` | in | `PEMBELIAN` | `2` | termasuk DO penerimaan |
| `SALE_OUT` | out | `PENJUALAN` | `3` | |
| `SERVICE_OUT` | out | `SERVIS` | `4` | |
| `ADJ_IN` | in | `ADJ` | `5` | stok masuk manual |
| `ADJ_OUT` | out | `ADJ` | `6` | stok keluar manual |
| `PURCHASE_RETURN_OUT` | out | `RETUR_BELI` | `7` | |
| `SALE_RETURN_IN` | in | `RETUR_JUAL` | `8` | |
| `SERVICE_RETURN_IN` | in | `RETUR_SERVIS` | `9` | |
| `TRANSFER_OUT` | out | `TRANSFER` | **`A`** (baru) | semula `3` (ambigu) |
| `TRANSFER_IN` | in | `TRANSFER` | **`B`** (baru) | semula `2` (ambigu) |
| `TRANSFER_RETURN` | in | `TRANSFER` | `B` | |
| `TRANSFER_LOSS` | – | `TRANSFER` | – | tanpa gerakan fisik baru; jejak beban |
| `CANCEL_IN` / `VOID_RETURN` | in | sesuai dokumen asal | sesuai tipe pembalik | |
| `OPNAME_IN` / `OPNAME_OUT` | in/out | `OPNAME` | `5` / `6` | |
| `SCRAP_OUT`, `INTERNAL_OUT` | out | `SCRAP`, `INTERNAL` | `6` | beban, bukan HPP |
| `CLAIM_OUT` | out | `KLAIM` | `6` | |
| `REPLACEMENT_IN` | in | `PEMBELIAN` | `2` | |
| `PRICE_CORR_INV`, `PRICE_CORR_PPV`, `REVALUATION` | nilai | koreksi | – (tidak menyentuh qty) | tidak diproyeksi ke `tbstok` |

**Catatan kompatibilitas pembaca** (MUST diaudit, task F0-10): `lib/MinMaxCalculator.php:44` menganggap `tipe 3` = penjualan dan `4` = servis sebagai permintaan. Karena `tipe 3` saat ini juga dipakai **kirim antar cabang**, permintaan terhitung berlebih (cacat yang sudah ada). Memisahkan transfer ke `A/B` memperbaikinya, tetapi **setiap pembaca `tbstok.tipe`** MUST diaudit sebelum kode `A/B` dipakai (`kartu_stok.php`, `MinMaxCalculator.php`, `pr_auto_draft.php`, laporan stok). Keputusan D-14.

### 5.10 Tanggal (BR-06, BR-12)

- `correction_date = tanggal server saat posting` (selalu). `transaction_date` = tanggal bisnis yang diisi pemanggil (info; untuk koreksi bisa tanggal transaksi asal).
- Form legacy yang memiliki date-picker (`id-date-picker-1`) MUST dibuat **read-only default hari ini** untuk cabang `live`; nilai POST diabaikan server (server menetapkan tanggal), kecuali jenis transaksi yang tanggal bisnisnya boleh berbeda (mis. tanggal faktur supplier) → disimpan hanya di `transaction_date`.
- `tbstok.tanggal` proyeksi = `correction_date`.

### 5.11 Konkurensi dan locking (V-08, EC-09, EC-11)

1. Transaksi `READ COMMITTED` **atau** default `REPEATABLE READ` dengan `SELECT … FOR UPDATE` pada baris saldo. Keduanya aman karena semua keputusan diambil dari baris yang terkunci.
2. Baris `inv_balance` belum ada: `INSERT IGNORE INTO inv_balance(kd_cabang,no_item,…) VALUES(…)` lalu `SELECT … FOR UPDATE`.
3. **Urutan kunci:** dokumen multi-baris mengunci item dalam urutan `(kd_cabang, no_item)` menaik. Transfer hanya mengunci satu lokasi per transaksi (kirim = asal; terima = tujuan), sehingga tidak ada transaksi yang mengunci dua lokasi sekaligus.
4. **Deadlock/timeout:** tangkap errno `1213` (deadlock) dan `1205` (lock wait timeout) → retry maksimal 3× dengan jeda acak 50–200 ms; setelah itu `INV-302`.
5. `innodb_lock_wait_timeout` sesi diset 10 detik di dalam service (`SET SESSION innodb_lock_wait_timeout=10`).
6. Transaksi posting MUST singkat: tidak ada pemanggilan eksternal, rendering, atau `sleep` di dalam transaksi.
7. Re-validasi stok di bawah lock menggantikan validasi di keranjang (menutup P6). Validasi keranjang tetap sebagai UX awal.

### 5.12 Opname, scrap, pemakaian internal, konversi satuan

- **Opname:** selisih = `qty_fisik − qty_on_hand`. `> 0` → `OPNAME_IN` harga = `average_cost` (override butuh approval); `< 0` → `OPNAME_OUT` (beban/kerugian). Approval: bila `|est_value| > opname_hq_threshold` → butuh Finance HQ; kalau tidak cukup Kepala Cabang (AC-12). Default ambang `0`.
- **Scrap/hilang/rusak, pemakaian internal:** `postOut` dengan `trx_type` terpisah; **bukan HPP penjualan** (BR §4.2); berita acara wajib (nomor dokumen + alasan).
- **Konversi satuan:** `postConvert(item, lokasi, ratio)` → `qty_after = qty × ratio`, `average_after = average / ratio`, `total_value` tetap. Prasyarat: master rasio satuan (belum ada), `tbstok` DECIMAL. Ditunda ke Fase 4.

### 5.13 Reservasi stok (opsional)

Opsi untuk mencegah dua kasir memegang barang yang sama di keranjang: tabel `inv_reservation(kd_cabang,no_item,qty,ref_type,ref_no,user,expires_at)`; `available = on_hand − Σ reservasi aktif`. **Baseline tidak memakainya** (re-validasi di bawah lock cukup untuk ±40 transaksi/lokasi/hari). Aktifkan hanya bila operasional mengeluhkan "stok habis saat simpan". Keputusan D-04b.

---

## 6. Kontrak `InventoryService` (API Internal)

Semua fungsi mengembalikan array hasil atau melempar `InventoryException` (kode §9.3). Tidak ada `echo`/redirect di dalam service.

```php
final class InventoryService {
  public function __construct(mysqli $db, array $ctx);   // ctx: ['user'=>..., 'ip'=>..., 'tz'=>'Asia/Jakarta']

  /** Masuk. Tepat salah satu: unit_cost atau total_value (di $o). */
  public function postIn(string $kdCabang, string $noItem, string $qty, array $o): array;
  /** Keluar. Mengembalikan hpp_value, hpp_unit. */
  public function postOut(string $kdCabang, string $noItem, string $qty, array $o): array;
  /** Penyesuaian nilai tanpa qty (koreksi harga, revaluasi). */
  public function postValueAdjust(string $kdCabang, string $noItem, string $valueDelta, array $o): array;

  public function postDocument(string $refType, string $refNo, array $lines, array $o): array;  // banyak baris, 1 transaksi, urut kunci

  public function shipTransfer(string $noTransfer, string $from, string $to, array $lines): array;
  public function receiveTransfer(string $noTransfer, array $lines): array;
  public function returnTransfer(string $noTransfer, array $lines): array;
  public function writeOffTransfer(string $noTransfer, array $lines, int $approvalId): array;

  public function submitCorrection(array $req): int;              // → inv_correction_request.id
  public function checkCorrection(int $id, string $note): void;
  public function approveCorrection(int $id, string $note): void;
  public function rejectCorrection(int $id, string $note): void;
  public function executeCorrection(int $id): array;              // hanya status approved

  public function getBalance(string $kdCabang, string $noItem): array;
  public function availableQty(string $kdCabang, string $noItem): string;   // tanpa lock (UX)
}
```

Parameter `$o` (opsi) umum:

| Kunci | Tipe | Wajib | Arti |
|---|---|---|---|
| `trx_type` | string | ya | katalog §5.9 |
| `ref_type`, `ref_no`, `ref_line` | string,string,int | ya | kunci idempotency |
| `transaction_date` | `Y-m-d` | ya | tanggal bisnis |
| `unit_cost` / `total_value` | decimal string | postIn | salah satu |
| `origin_ledger_id` | int | koreksi/cancel | baris yang dikoreksi |
| `reason_code`, `note` | string | adj/koreksi | |
| `bonus_confirmed` | bool | bila harga 0 | EC-03 |
| `on_duplicate` | `'return'` \| `'throw'` | tidak | default `return` |

Contoh pemanggilan (penjualan reguler, menggantikan `INSERT INTO tbstok` di `penjualan_add_rst.php:471`):

```php
$svc = new InventoryService($koneksi, ['user'=>$_nama, 'ip'=>$_SERVER['REMOTE_ADDR']]);
try {
  mysqli_begin_transaction($koneksi);                   // header + detail + stok satu transaksi
  // ... insert tblpenjualan_header ...
  foreach ($lines as $ln) {                              // $lines urut no_item ASC
    $r = $svc->postOut($kd_cabang, $ln['no_item'], $ln['qty'], [
        'trx_type'=>'SALE_OUT','ref_type'=>'PENJUALAN','ref_no'=>$LastID,'ref_line'=>$ln['nobaris'],
        'transaction_date'=>$txttglpesan]);
    // snapshot HPP (menutup T1)
    mysqli_query($koneksi, "UPDATE tblpenjualan_detail SET harga_pokok='".$r['hpp_unit']."', ledger_id=".$r['ledger_id']." WHERE id=".$ln['id']);
  }
  mysqli_commit($koneksi);
} catch (InventoryException $e) {
  mysqli_rollback($koneksi);
  // tampilkan $e->getCode() / $e->getMessage() ke user, tanpa alert() tak terkontrol
}
```

Catatan: bila pemanggil sudah membuka transaksi, service MUST memakai transaksi itu (deteksi via flag/`SAVEPOINT`), tidak membuka transaksi bersarang; bila belum, service membuka sendiri. Prepared statement dipakai di dalam service; contoh di atas memakai string hanya untuk keringkasan.

---

## 7. Spesifikasi Integrasi per Modul

Format: *kondisi sekarang* → *perubahan* → *data yang ditulis* → *validasi*. Semua perubahan MUST dibungkus transaksi DB dan mengecek hasil query (aturan "INSERT gagal diam-diam" dari sapuan 2026-09-30).

### 7.1 Pembelian (BR-01, BR-16, BR-17)

| | |
|---|---|
| Berkas | `pembelian_add.php`, `pembelian_add_rst.php`, `pembelian_cab_add_proses.php`, `do_receive.php`, `do_from_po.php` |
| Sekarang | Simpan header, `UPDATE tblpembelian_detail SET status_trx='1'`, `INSERT INTO tbstok ('2', …)` per baris, tanpa transaksi DB |
| Perubahan | Ganti insert `tbstok` dengan `postIn(trx_type='PURCHASE_IN')`; satu transaksi untuk header+detail+ledger; kolom `qty_bonus`; `unit_cost_eff` |
| Biaya masuk | `harga_net = harga_pokok − potongan%` per baris; **diskon faktur dan PPN: keputusan D-13** (header menyimpan `diskon`, `total_diskon`, `pajak`, `total_pajak`; rekomendasi: alokasikan diskon faktur proporsional ke baris; PPN masukan dikecualikan dari biaya bila PKP, dimasukkan bila non-PKP) |
| Cabang beli darurat | Sama; `kd_cabang` = cabang pembeli (BR-16); tidak menyentuh lokasi lain |
| Validasi | `qty>0`, `harga>=0` (0 hanya bonus terkonfirmasi), item ada, supplier ada, periode open, tanggal server |
| Lain | `trg_alarm_harga_beli` tetap jalan (AFTER INSERT `tblpembelian_detail`) |

### 7.2 Penjualan reguler dan pesanan (BR-03, BR-05)

| | |
|---|---|
| Berkas | `penjualan_add.php`, `penjualan_add_rst.php`, `proses-add-detail/penjualan.php`, `pesanan_penjualan_*` |
| Sekarang | Cek stok di keranjang; `INSERT tblpenjualan_detail … harga_pokok='0'`; saat simpan `INSERT tbstok ('3', …)` |
| Perubahan | Saat simpan: `postOut(trx_type='SALE_OUT')` per baris di bawah lock; isi `tblpenjualan_detail.harga_pokok = hpp_unit`, `ledger_id`; tolak seluruh faktur bila ada baris gagal (INV-201) dengan daftar baris bermasalah |
| Validasi keranjang | Tetap, tapi memakai `availableQty()` dari `inv_balance` untuk cabang `live` |
| Stok pesanan | Pesanan penjualan **tidak** mengurangi stok (hanya saat faktur) — perilaku sekarang dipertahankan |

### 7.3 Penjualan antar cabang / mitra

`penjualan_cab_add_proses.php`, `penjualan_cab_add_proses_mitra.php`: sisi penjual `postOut(SALE_OUT)` dengan HPP; harga jual ke cabang = HPP Pusat at cost (PRD bisnis §2.1). Sisi penerima mengikuti §7.8 bila berupa kiriman stok; bila penjualan tunai antar lokasi, penerima mencatat pembelian (`postIn`) pada harga transfer.

### 7.4 Servis (BR-03, D-04)

| | |
|---|---|
| Berkas | `servis-reguler-byr.php:585`, `servis-input-reguler.php:798`, `…-rst.php:965`, `…-jemput.php:2623`, `…-jemput-rst.php:1196`, `servis-garansi.php:1165`, `proses-add-detail/penjualan-brg-servis.php` |
| Sekarang | `INSERT tblservis_barang` tanpa HPP; `tbstok ('4')` di beberapa titik (risiko potong ganda belum diaudit — F0-08) |
| Perubahan | Satu titik posting per `no_service` (ditentukan D-04); `postOut(SERVICE_OUT)`; isi `tblservis_barang.harga_pokok` (kolom baru) |
| Opsi D-04 | **(a)** potong saat bayar (sekarang) + re-validasi di bawah lock saat bayar (stok bisa habis saat bayar → UX buruk); **(b)** potong saat item ditambah ke WO + pembalikan saat item dihapus/WO batal (konsisten PRD §4.3/4.4, ledger lebih ramai); **(c)** reservasi saat tambah, potong saat bayar (§5.13). **Rekomendasi: (b)** untuk konsistensi BR-05 dan "HPP saat transaksi diposting" |
| Garansi | `servis-garansi.php`: pemakaian barang garansi memakai `SERVICE_OUT` dengan tanda `is_warranty`; beban garansi ke akun terpisah (jurnal §10) |
| Komisi | `_include_komisi_snapshot.php:33-41`: `laba_barang = total − qty × tblitem.hargapokok` → ubah ke `SUM(total − qty × tblservis_barang.harga_pokok)` |

### 7.5 Retur (BR-07, BR-18)

| | |
|---|---|
| Retur pembelian | `save_retur_pembelian.php` (sudah transaksional, sudah menjaga `qty ≤ sisa`): ganti `INSERT tbstok ('7')` → `postOut(PURCHASE_RETURN_OUT)`. Nilai ke supplier = harga faktur; nilai persediaan keluar = HPP rata-rata; **selisih** keduanya ke Selisih Harga Pembelian (jurnal §10, D-17) |
| Batal retur pembelian | `retur_pembelian_batal.php`: **dihapus perilaku `DELETE FROM tbstok`**; diganti `postIn(trx_type='CANCEL_IN', reversal_of=…)` pada HPP asal |
| Retur penjualan | `save_retur_penjualan.php`: `postIn(SALE_RETURN_IN)` pada `hpp_unit` baris `tblpenjualan_detail` asal (BR-07). Bila baris asal berstatus `harga_pokok=0` (transaksi sebelum cutover): pakai `average_cost` saat ini dan beri `note='HPP asal tidak tercatat'` (aturan transisi, D-10) |
| Batal retur penjualan | `retur_penjualan_batal.php`: pembalik, bukan DELETE |
| Retur servis | `save_retur_servis.php`, `retur_servis_approve.php`: `postIn(SERVICE_RETURN_IN)` pada `tblservis_barang.harga_pokok` asal; approval yang sudah ada dipertahankan |

### 7.6 Stok masuk/keluar manual dan penyesuaian

`stok_masuk_add*.php`, `stok_keluar_add*.php`, `so-item-masuk*.php`, `so-item-keluar*.php`, `save_barang.php`. Untuk cabang `live`, halaman ini **hanya membuat `inv_correction_request`** (ADJ_PLUS/ADJ_MINUS/SCRAP/INTERNAL/OPNAME), bukan posting langsung. `tbitem_masuk/keluar_*` tetap dipakai sebagai dokumen sumber (`ref_no`) agar laporan lama tetap hidup.

### 7.7 Item baru

`save_barang.php` menulis `tbstok ('1','-',…,'Stok Awal')`. Untuk cabang `live`: item baru dibuat tanpa stok; stok awal hanya lewat `OPENING`/pembelian/adjustment.

### 7.8 Transfer antar cabang (BR-13..15)

| | |
|---|---|
| Berkas | `pengadaan_antarcab_add.php` (request), `…_push.php`, `…_proses.php` (kirim, baris 84), `…_terima.php` (baris 97), `…_print.php`, `…_detail.php` |
| Sekarang | Kirim: `INSERT tbstok ('3', keluar)` + `status='dikirim'`; HPP dari `tblitem.hargapokok`; Terima: `INSERT tbstok ('2', masuk)` + `status='selesai'` (sudah transaksional); `jenis` = `push`/`pull`; status berejaan ganda (`dikirim`/`terkirim`) |
| Perubahan | Kirim → `shipTransfer`; Terima → `receiveTransfer`; simpan `harga_pokok` kirim di `tblorder_antarcab_detail` dari `hpp_unit` (bukan master); tambah aksi **Kembalikan** dan **Hapus buku selisih** (dengan approval); normalisasi nilai `status` (migrasi kecil: `terkirim` → `dikirim` setelah dipastikan artinya) |
| PUSH/PULL | Lokasi asal/tujuan stok mengikuti logika yang sudah ada (`$kd_penerima`, `$kd_safe`); tidak diubah |
| Monitor | Halaman `inv_transit.php` (§12) menampilkan aging dan alert > `transit_alert_days` |

### 7.9 Komisi, laba, laporan profit

| Berkas | Perubahan |
|---|---|
| `lap_profit_penjualan.php:54` | Sudah memakai `d.harga_pokok`; hasilnya jadi benar setelah §7.2. Transaksi lama (HPP 0) ditandai "HPP tidak tercatat" pada laporan |
| `lap_profit_insentif.php:68` | Membaca `tothpp` hasil sync Access; untuk periode setelah cutover baca snapshot web (UNION) — D-10 |
| `lap_komisi_mekanik.php`, `issue_add.php:206` | Baca `tblservis_barang.harga_pokok` |
| `_include_komisi_snapshot.php` | Seperti §7.4 |

### 7.10 Sync Access (`_include_access_sync.php`)

| | |
|---|---|
| Sekarang | Upsert `tblitem` (termasuk `hargapokok`, `quantity`), `DELETE FROM tbstok WHERE no_item=? AND kd_cabang=? AND no_transaksi=?` lalu `INSERT` saldo (baris 1101–1110) |
| Perubahan (F2-13) | Untuk lokasi `live`: **lewati** penulisan `tblitem.quantity`, `tblitem.hargapokok`, dan seluruh penulisan `tbstok`. Untuk `shadow`: tetap (jalur lama masih sumber kebenaran). Pemeriksaan mode di awal fungsi upsert stok |

### 7.11 Barang master

`barang_edit_proses.php:75`, `barang_edit_improved.php:125`, `jasa-input.php:112` mengubah `tblitem.hargapokok` manual. Setelah cutover, `tblitem.hargapokok` **bukan sumber HPP**; field dijadikan *informasi harga beli referensi* dan diberi label "Harga Pokok Referensi (bukan HPP)". Edit manual tidak memengaruhi `inv_balance`.

### 7.12 Titik integrasi Hutang/Piutang (di luar cakupan implementasi, tetapi antarmuka MUST ada)

| Peristiwa | Antarmuka |
|---|---|
| Koreksi harga pembelian | Service menghasilkan `ppv_amount` dan `delta_total = delta × Q`; modul Hutang membuat Debit/Credit Note (D-17) |
| Retur pembelian | Selisih harga faktur vs HPP keluar ke PPV |
| Klaim supplier | `inv_claim_receivable` menjadi sumber piutang klaim |

---

## 8. Maker-Checker, RBAC, dan Audit

### 8.1 Mesin status `inv_correction_request`

```
draft → submitted → checked → approved → posted
            │           │          │
            └──→ rejected ←────────┘            (dari submitted/checked/approved)
draft/submitted → cancelled (oleh maker)
```

| Transisi | Pelaku | Syarat |
|---|---|---|
| `draft→submitted` | maker (petugas) | payload valid, `reason_code`+`reason_text` terisi, `ref_origin` bila wajib |
| `submitted→checked` | atasan: Kepala Cabang / Kepala Mekanik (BR-27) | bukan maker (INV-403) |
| `checked→approved` | Finance/HQ **bila** `est_value` melebihi ambang atau jenis ∈ {PRICE_CORRECTION, REVALUATION, TRANSFER_LOSS}; selain itu approver = atasan (checker) | approver ≠ maker (≠ checker untuk jenis berdampak keuangan) |
| `approved→posted` | sistem (`executeCorrection`) | periode memperbolehkan (§9.4), saldo cukup (untuk keluar) |

Reuse: pola kolom dan log `tblpo_approval_log` (`db_update_procurement.php:291`) dan `retur_servis_approve.php`.

### 8.2 Permission baru (`menu_config.php` + RBAC)

| Permission | Fungsi | Posisi yang disarankan (D-06) |
|---|---|---|
| `inv_view` | lihat kartu stok, valuasi | semua staf cabang, ADM, MNG |
| `inv_adjust_request` | ajukan koreksi/adjustment/opname/scrap | CS, ADM, KM |
| `inv_check` | memeriksa koreksi | KACAB, KM |
| `inv_approve_hq` | menyetujui dampak keuangan | **posisi baru "FINANCE HQ"** (belum ada di `tbuser`/posisi) |
| `inv_revalue` | revaluasi | FINANCE HQ saja (V-11) |
| `inv_period_close` | tutup periode | KACAB / FINANCE HQ |
| `inv_period_lock` | kunci periode | FINANCE HQ |
| `inv_config_edit` | ubah ambang/mode | admin pusat |
| `inv_opening_load` | muat opening stock | admin pusat (satu kali per cabang) |
| `inv_transit_manage` | kembalikan/hapus buku transit | KACAB, FINANCE HQ |

Aturan keras (MUST): tidak ada role yang boleh mem-bypass INV-201 (stok minus), termasuk Superadmin (Test 19). Skrip admin yang menulis `tbstok` langsung (`admin-backfill-tbstok-servis.php`) MUST ditandai nonaktif untuk lokasi `live`.

### 8.3 Audit

`AuditLogger::log()` dipanggil untuk: perubahan mode, opening load, tiap transisi koreksi, eksekusi koreksi, penutupan/penguncian periode, perubahan `inv_config`, revaluasi, tolak stok minus (INV-201, hanya hitung/ringkas agar tabel tidak membengkak), dan transisi transfer. Format: WHO (`actor`), WHAT (`action`), WHEN (`at`), WHY (`reason`), BEFORE/AFTER (`*_json`), APPROVAL (`approval_id`).

---

## 9. Validasi, Kontrol, dan Error

### 9.1 Daftar validasi (traceability ke V-xx PRD)

| ID | Validasi | Titik | Kode error |
|---|---|---|---|
| V-01 | Stok keluar tidak boleh melebihi saldo (tanpa pengecualian) | `postOut` di bawah lock | INV-201 |
| V-02 | Tidak ada edit/hapus transaksi posted | trigger ledger + guard endpoint | INV-501 / INV-405 |
| V-03 | Perubahan average tercatat | `postIn`/`postValueAdjust` | (otomatis) |
| V-04 | Atomik | semua posting | INV-502 (rollback) |
| V-05 | Harga ≥ 0; 0 hanya bonus terkonfirmasi | `postIn` | INV-103, INV-104 |
| V-06 | Qty > 0 | semua | INV-101 |
| V-07 | Idempotency | `uq_idem` | INV-301 |
| V-08 | Row lock | `FOR UPDATE` | INV-302 |
| V-09 | Pembulatan 2 dp half-up | `AverageCalculator` | – |
| V-10 | Maker-checker | `CorrectionService` | INV-403 |
| V-11 | Revaluasi hanya Finance HQ | RBAC | INV-402 |
| V-12 | Ambang opname dari config | `inv_config` | – |
| V-13 | Notifikasi Finance atas PPV | `CorrectionService` | – |
| V-14 | Alert aging transit | job harian | – |

### 9.2 Aturan input

- Semua parameter numerik diterima sebagai **string desimal** dan divalidasi regex `^\d+(\.\d{1,6})?$`; MUST NOT diterima sebagai `float` dari form.
- `no_item`, `kd_cabang`: whitelist terhadap `tblitem`/`inv_location` (tidak cukup `mysqli_real_escape_string`).
- Panjang `ref_no` ≤ 40, `note` ≤ 255.

### 9.3 Katalog kode error

| Kode | Arti | Dipakai |
|---|---|---|
| INV-101 | Qty tidak valid (≤0) | semua |
| INV-102 | Qty melebihi sisa dokumen (mis. terima > kirim) | transfer, retur |
| INV-103 | Harga negatif | postIn |
| INV-104 | Harga nol tanpa konfirmasi bonus | postIn |
| INV-105 | Item/lokasi tidak ditemukan | semua |
| INV-106 | Mode lokasi `off` | semua |
| INV-201 | **Stok tidak mencukupi** (sertakan `tersedia` dan `diminta`) | postOut |
| INV-202 | Nilai persediaan akan negatif | koreksi/revaluasi |
| INV-301 | Duplikat posting (idempotency) | semua |
| INV-302 | Gagal mendapatkan lock / deadlock setelah retry | semua |
| INV-401 | Periode Closed/Locked tidak mengizinkan operasi ini | semua |
| INV-402 | Tidak berwenang (permission) | koreksi/revaluasi/config |
| INV-403 | Maker = checker/approver | maker-checker |
| INV-404 | Status permintaan tidak valid untuk transisi | koreksi |
| INV-405 | Edit/hapus transaksi posted dilarang; gunakan koreksi | endpoint legacy |
| INV-501 | Pelanggaran append-only (trigger) | DB |
| INV-502 | Gagal menyimpan; rollback | semua |
| INV-503 | Invarian dilanggar (qty/value tidak cocok) | rekonsiliasi |

Pesan ke pengguna berbahasa Indonesia, jelas, dan menampilkan data yang dibutuhkan (mis. INV-201: *"Qty tidak mencukupi. Tersedia: 120, diminta: 150."*). Pemanggil MUST menampilkannya dengan cara yang tidak membekukan sesi (hindari `alert()`+`window.location` berantai).

### 9.4 Perilaku periode (BR-08, EC-10)

| Status | Posting transaksi normal | Koreksi (tanggal hari ini) | Edit langsung | Catatan |
|---|---|---|---|---|
| `open` | boleh | boleh (sesuai kewenangan) | dilarang (BR-06) | |
| `closed` | **ditolak** untuk dokumen bertanggal di periode closed; posting hari ini (di periode `open` berjalan) tetap boleh | boleh — koreksi bertanggal hari ini bukan edit periode closed (EC-10) | dilarang | |
| `locked` | seperti `closed` | butuh otorisasi tambahan (`inv_period_lock` + approval Finance HQ) | dilarang | |

Karena `correction_date` selalu hari ini, periode yang relevan untuk posting adalah **periode berjalan**. Periode lampau tertutup untuk posting baru yang menyasar tanggal lampau (yang memang dilarang oleh BR-06). Penutupan periode memotong snapshot `inv_balance` per lokasi ke tabel arsip `inv_balance_snapshot(period,kd_cabang,no_item,qty,value,avg)` untuk laporan valuasi historis.

---

## 10. Jurnal Akuntansi (Fase 5, bergantung D-07)

### 10.1 Prinsip

- Posting jurnal **dalam transaksi yang sama** dengan ledger (real-time, PRD §3/V-04) bila `inv_account_map` terisi; jika COA belum ada, posting jurnal dinonaktifkan dan ledger tetap berjalan (R1/R2 tidak terblokir).
- Debit = kredit per jurnal (diperiksa sebelum commit).
- Akun diambil dari `inv_account_map` (konfigurasi), tidak di-hardcode.

### 10.2 Peristiwa dan jurnal

| `event_key` | Debit | Kredit | Catatan |
|---|---|---|---|
| `PURCHASE_IN` | Persediaan | Hutang Usaha / Kas (dari modul Pembelian) | Kaki hutang dimiliki modul Pembelian; service memposting kaki persediaan |
| `COGS` | HPP | Persediaan | nilai = `hpp_value` |
| `SALE_RETURN_IN` | Persediaan | HPP | membalik HPP |
| `PURCHASE_RETURN_OUT` | Hutang/Piutang Supplier | Persediaan (+ selisih ke PPV) | |
| `TRANSFER_OUT` | Barang dalam Perjalanan | Persediaan (asal) | |
| `TRANSFER_IN` | Persediaan (tujuan) | Barang dalam Perjalanan | |
| `TRANSFER_RETURN` | Persediaan (asal) | Barang dalam Perjalanan | |
| `TRANSFER_LOSS` | Kerugian Persediaan | Barang dalam Perjalanan | |
| `ADJ_IN`, `OPNAME_IN` | Persediaan | Selisih/Pendapatan Persediaan | |
| `ADJ_OUT`, `OPNAME_OUT`, `SCRAP_OUT` | Kerugian Persediaan | Persediaan | beban, bukan HPP |
| `INTERNAL_OUT` | Beban Operasional | Persediaan | |
| `PPV` | Selisih Harga Pembelian (±) | Hutang Usaha (±) | per `inv_ppv_log` |
| `CLAIM_OUT` | Piutang Klaim Supplier | Persediaan | BR-19 |
| `CLAIM_SETTLED` | Kas/Hutang | Piutang Klaim Supplier | |
| `CLAIM_REJECTED` | Beban | Piutang Klaim Supplier | |
| `REVALUATION` | Persediaan (±) | Selisih Revaluasi (±) | hanya Finance HQ |

Akun yang sudah ada di DB (`tbakun`, `tbakun_pos`, `tblakunkas`, `master_akun_closing_kasir`) milik modul kas; **tidak** otomatis menjadi COA persediaan. D-07 menentukan apakah dipakai ulang atau dibuat COA baru.

### 10.3 Rekonsiliasi jurnal

`Σ inv_balance.total_value (semua lokasi) + saldo Barang dalam Perjalanan = saldo akun Persediaan (+ transit)` per tanggal (Test 35, 47). Dicek harian (§13.7).

---

## 11. Laporan (Spesifikasi Query dan Performa)

| Laporan | Sumber | Filter utama | Indeks | Catatan |
|---|---|---|---|---|
| Kartu Stok per lokasi | `inv_ledger` | lokasi, item, rentang `correction_date` | `idx_card`, `idx_date` | Kolom `transaction_date` dan `correction_date` terpisah; pasangan `is_cancel` disembunyikan default |
| Inventory Valuation | `inv_balance` (+ `inv_balance_snapshot` untuk tanggal lampau) | lokasi, kategori | `uq_loc_item` | `qty × average` ditampilkan; **nilai resmi = `total_value`** |
| Average Cost History | `inv_avg_history` | item, lokasi | `idx_item` | flag koreksi |
| Barang dalam Perjalanan | `inv_transfer(+line)` | status, aging | `idx_status` | aging = `NOW()-shipped_at`; alert > `transit_alert_days` |
| Selisih Harga Pembelian | `inv_ppv_log` | periode, lokasi | `idx_period` | rekonsiliasi dengan daftar koreksi periode itu (Test 48) |
| Piutang Klaim Supplier | `inv_claim_receivable` | supplier, status | `idx_status` | |
| HPP / Laba Rugi | `tblpenjualan_detail.harga_pokok`, `tblservis_barang.harga_pokok` | periode | – | HPP historis tidak berubah |
| Neraca persediaan | `inv_balance` + saldo transit | tanggal | – | |

Target performa: laporan kartu stok satu item satu lokasi satu tahun < 1 detik; valuasi seluruh item satu lokasi < 3 detik (≈6 rb item). Laporan agregat berat memakai `LIMIT`/paging (pelajaran pencarian modal 5.761 baris tanpa LIMIT).

---

## 12. Spesifikasi UI

Semua halaman baru memakai kerangka ACE yang sama dengan halaman lain (`menu_dashboard.php`, `lib/logo.php`, `lib/footer.php`; lihat partial `_komplain/_ace_header.php` sebagai contoh) dan `requirePermission()` di awal.

| Halaman | Fungsi | Permission |
|---|---|---|
| `inv_admin_mode.php` | Atur `inv_mode` per cabang; tampilkan hasil rekonsiliasi terakhir; tombol `shadow→live` hanya aktif bila rekonsiliasi hijau | `inv_config_edit` |
| `inv_opening_stock.php` | Unggah CSV opening stock, validasi dulu (tidak menulis), lalu commit satu kali per cabang | `inv_opening_load` |
| `inv_koreksi_add.php`, `inv_koreksi_list.php`, `inv_koreksi_detail.php` | Ajukan, periksa, setujui, tolak, tampilkan audit | `inv_adjust_request`, `inv_check`, `inv_approve_hq` |
| `inv_opname.php` | Hitung selisih opname dan ajukan koreksi | `inv_adjust_request` |
| `inv_transit.php` | Daftar transfer in-transit/partial, aging, aksi kembalikan/hapus buku | `inv_transit_manage` |
| `inv_kartu_stok.php`, `inv_valuasi.php`, `inv_avg_history.php`, `inv_ppv.php`, `inv_claim.php` | Laporan §11 | `inv_view` |
| `inv_period.php` | Tutup/kunci periode | `inv_period_close`, `inv_period_lock` |
| `inv_config.php` | Ambang opname/aging, pemetaan akun | `inv_config_edit` |
| `inv_recon.php` | Hasil rekonsiliasi harian dan detail selisih | `inv_view` |

Perilaku UI wajib:

1. Pesan error sesuai §9.3, tampil inline (bukan `alert()` berantai).
2. Form transaksi di cabang `live`: kolom tanggal read-only (tanggal server), tombol simpan dinonaktifkan setelah klik pertama (cegah double-submit; idempotency tetap penjaga terakhir).
3. Tombol Edit/Hapus pada transaksi `posted` disembunyikan; endpoint tetap menolak (INV-405).
4. Item `qty_bonus` tampil di form pembelian.
5. Label "Harga Pokok Referensi (bukan HPP)" pada master barang (§7.11).

---

## 13. Migrasi, Cutover, dan Rekonsiliasi

### 13.1 Normalisasi kunci lokasi (F0-01..03)

1. Audit: daftar semua kolom `kd_cabang`/`kode_cabang` + distribusi nilai (lokal dan produksi).
2. Backup tabel yang akan diubah: `CREATE TABLE bak_YYYYMMDD_tbstok AS SELECT * FROM tbstok` (dijalankan dan diverifikasi jumlah barisnya).
3. Seed `inv_location`, `inv_location_alias`.
4. Normalisasi **hanya `tbstok`** dulu:
   ```sql
   UPDATE tbstok t JOIN inv_location_alias a ON a.alias = t.kd_cabang
   SET t.kd_cabang = a.kode;                       -- CDT→CIKDITIRO dst.
   ```
   `001` dan `''` dipetakan setelah D-01 (butuh penjelasan asal-usul 88 + 24 baris lokal).
5. `tblservice.kd_cabang` **tidak** diubah massal pada tahap ini (dipakai banyak modul dan laporan lama; lihat risiko di planning R3). Pembaca baru memakai fungsi `inv_loc_canon($kd)` yang memetakan alias → kanonik.
6. Verifikasi: `SELECT DISTINCT kd_cabang FROM tbstok` ⊆ `inv_location.kode`; jumlah baris sama dengan backup; `SUM(masuk-keluar)` total tidak berubah.
7. Rollback: `down` mengembalikan dari `bak_*`.

### 13.2 Opening stock (F0-05/06, F5-06)

Format CSV (satu file per lokasi): `kd_cabang,no_item,qty_fisik,unit_cost,sumber_biaya`.

| Validasi | Aturan |
|---|---|
| `kd_cabang` | ada di `inv_location`, sama dengan lokasi batch |
| `no_item` | ada di `tblitem`; duplikat dalam file ditolak |
| `qty_fisik` | ≥ 0 desimal ≤ 3 dp |
| `unit_cost` | ≥ 0; bila 0 dan `qty_fisik>0` → wajib `sumber_biaya='MANUAL_KONFIRMASI'` (D-02) |
| `sumber_biaya` | `BELI_TERAKHIR`, `ACUAN_ACCESS`, `MASTER`, `MANUAL_KONFIRMASI` |

Urutan sumber biaya awal (rekomendasi D-02): harga beli terakhir per item per lokasi → `gabung_hpp_acuan.acuan_hpp` → `tblitem.hargapokok` → input manual. Tahap commit: untuk tiap baris `postIn(trx_type='OPENING', ref_type='OPENING', ref_no=<batch>, ref_line=<n>)`; `inv_opening_batch.status='committed'`; **sekali per lokasi** (batch kedua ditolak kecuali dibatalkan lewat koreksi). Tidak ada baris untuk `qty_fisik=0`.

### 13.3 Backfill HPP transaksi lama

Bukan prasyarat go-live. Bila Owner menginginkan (D-10): isi `tblpenjualan_detail.harga_pokok` untuk baris `=0` dari rekonstruksi (mis. `tbbeli_peritem_history`/`gabung_hpp_acuan`) — **ditandai `hpp_source='BACKFILL'`**, dihitung terpisah, dan tidak masuk `inv_*`. Laporan menampilkan kolom "sumber HPP".

### 13.4 Mode shadow (F1-09)

- Jalur lama tetap menulis `tbstok`. Setelah jalur lama sukses, pemanggil memanggil service (tidak dalam transaksi jalur lama) dengan `mode=shadow`.
- Service menulis `inv_*` tetapi **tidak** menulis proyeksi `tbstok` dan **tidak** menolak stok minus. Karena `inv_balance` dimulai dari saldo opening yang sah (bukan ledger kotor), shadow dimulai **setelah opening stock** dimuat untuk lokasi itu. Sebelum opening stock, `shadow` hanya mencatat HPP kandidat tanpa saldo (mode `shadow_cost_only`).
- Bila `qty > on_hand` pada shadow: ledger **tidak** ditulis; `inv_audit_log(action='SHADOW_WOULD_REJECT', after_json={tersedia,diminta,ref})`. Jumlah kejadian ini adalah metrik kesiapan go-live (§13.7).
- Kriteria keluar shadow: rekonsiliasi harian 14 hari berturut-turut tanpa selisih tak terjelaskan **dan** `SHADOW_WOULD_REJECT` dapat dijelaskan (data/proses, bukan bug).

### 13.5 Cutover per cabang (F5-06/07)

Urutan (satu cabang pada satu waktu; jendela di luar jam operasional):

1. Freeze: `inv_mode=off` lalu hentikan transaksi di cabang (pemberitahuan).
2. Opname fisik; susun CSV; validasi; commit opening (§13.2).
3. Rekonsiliasi awal: `Σ inv_balance` = total CSV.
4. Ganti definisi `view_stok_master` (lihat bawah) dan jalankan uji asap penjualan/pembelian/servis uji.
5. `inv_mode=live`. Blokir jalur tulis lama untuk cabang itu (§13.6).
6. Pengamatan intensif 1–2 minggu; rekonsiliasi harian.
7. Rollback (bila perlu dalam jendela pengamatan): `live→shadow`, kembalikan definisi view lama, aktifkan jalur lama; ledger `inv_*` dipertahankan untuk analisis (tidak dihapus).

Definisi `view_stok_master` setelah cutover (mempertahankan antarmuka `kd_cabang,no_item,saldo`):

```sql
CREATE OR REPLACE VIEW view_stok_master AS
SELECT b.kd_cabang, b.no_item, b.qty_on_hand AS saldo
FROM inv_balance b
JOIN inv_config c ON c.config_key = CONCAT('inv_mode:', b.kd_cabang) AND c.value = 'live'
UNION ALL
SELECT t.kd_cabang, t.no_item, SUM(t.masuk - t.keluar) AS saldo
FROM tbstok t
LEFT JOIN inv_config c ON c.config_key = CONCAT('inv_mode:', t.kd_cabang)
WHERE c.value IS NULL OR c.value <> 'live'
GROUP BY t.kd_cabang, t.no_item;
```

Definisi lama (`SELECT kd_cabang, no_item, SUM(masuk-keluar) AS saldo FROM tbstok GROUP BY kd_cabang, no_item`) disimpan di `db/migrations/…_view_stok_master_before.sql` sebagai bahan rollback. Performa diuji terhadap `view_cari_item`/halaman pencarian yang memakainya.

### 13.6 Pemblokiran jalur tulis lama setelah `live`

Setiap berkas penulis lama (Lampiran A planning) MUST memanggil `inv_assert_legacy_write_allowed($kd_cabang)` di awal blok tulis; untuk `live` fungsi melempar INV-405 dan mengarahkan ke jalur baru. Hanya `InventoryService` yang boleh menulis `tbstok` untuk cabang `live`. Kode mati (`save_pembelian.php`, `save_penjualan.php`, dll.) diarsipkan (F2-11).

### 13.7 Rekonsiliasi (harian dan cutover)

Job (`inv_recon.php`, dapat dijalankan manual/cron hosting) memeriksa dan menulis ke `inv_recon_run`:

| Cek | SQL (ringkas) | Toleransi |
|---|---|---|
| C1 qty ledger = saldo | `SELECT b.kd_cabang,b.no_item FROM inv_balance b LEFT JOIN (SELECT kd_cabang,no_item,SUM(qty_in-qty_out) q FROM inv_ledger GROUP BY 1,2) l USING(kd_cabang,no_item) WHERE b.qty_on_hand <> COALESCE(l.q,0)` | 0 |
| C2 value ledger = saldo | sama dengan `SUM(value_delta)` vs `total_value` | 0,00 |
| C3 qty = 0 ⇒ value = 0 | `WHERE qty_on_hand=0 AND total_value<>0` | 0 |
| C4 tak ada qty negatif | `WHERE qty_on_hand < 0` | 0 |
| C5 saldo `inv_balance` = proyeksi `tbstok` (cabang live) | bandingkan `SUM(masuk-keluar)` | 0 |
| C6 transit | `Σ(value_shipped − received − returned − written_off)` = saldo akun transit (bila jurnal aktif) | 0,00 |
| C7 jurnal seimbang | `Σ debit = Σ kredit` per `journal_id` | 0,00 |
| C8 ledger tanpa idempotency ganda | `GROUP BY ref… HAVING COUNT(*)>1` | 0 |
| C9 shadow vs lama | `inv_balance` vs `view_stok_master` (cabang shadow) | dijelaskan |

Selisih non-nol → `mismatches>0`, notifikasi, dan tombol `shadow→live` terkunci.

---

## 14. Persyaratan Non-Fungsional

| ID | Persyaratan | Target / Cara verifikasi |
|---|---|---|
| TR-101 | Latensi posting satu baris | p95 < 150 ms; dokumen 20 baris p95 < 800 ms (diukur di lokal dengan 320 rb baris `tbstok`) |
| TR-102 | Ketahanan | Tidak ada data sebagian setelah kegagalan di tengah posting (Test 44): uji dengan memicu error pada langkah ke-N |
| TR-103 | Konkurensi | 2–10 proses paralel pada item+lokasi sama: tidak ada saldo negatif, average benar, tidak ada deadlock tak tertangani (Test 43/45) |
| TR-104 | Keamanan | Prepared statement; whitelist item/lokasi; permission di tiap halaman dan aksi; tidak ada kredensial hardcode di file baru (`db_env.php` via env; tidak mengulang fallback `fitmotor_LOGIN` di kode baru) |
| TR-105 | Audit | Semua aksi sensitif tercatat (§8.3); `inv_ledger` immutable |
| TR-106 | Observabilitas | `error_log` terstruktur `[INV][kode][ref]`; metrik harian: jumlah INV-201, retry deadlock, `SHADOW_WOULD_REJECT`, selisih rekonsiliasi |
| TR-107 | Kompatibilitas | Pembaca lama tetap benar sepanjang transisi (C5, C9) |
| TR-108 | Portabilitas | Tidak butuh composer/ekstensi di luar `mysqli`; `bcmath` opsional dengan fallback integer teruji |
| TR-109 | Reversibilitas | Semua migrasi `up/down`; cutover bisa dibatalkan per cabang (§13.5) |
| TR-110 | Zona waktu | `date_default_timezone_set('Asia/Jakarta')` di bootstrap service; tanggal posting dari aplikasi |
| TR-111 | Charset | Semua tabel `inv_*` sama dengan `tblitem`; diuji dengan JOIN lintas tabel (`inv_ledger` ⋈ `tblitem`) |
| TR-112 | Backup | Backup penuh sebelum tiap migrasi produksi dan sebelum tiap cutover |
| TR-113 | Dokumentasi | Runbook (`docs/ops/inventory-runbook.md`), SOP koreksi untuk staf |
| TR-114 | Pengujian | Tidak boleh merge bila ada tes gagal (aturan global); Playwright E2E untuk alur kasir/servis |

Volume referensi: ≈200 transaksi/hari ⇒ ±1–3 baris ledger per transaksi ⇒ ±10 rb baris/tahun; tidak butuh partisi. Ledger 5 tahun < 100 rb baris; kartu stok per item sangat ringan.

---

## 15. Strategi Pengujian

### 15.1 Tingkat uji

| Tingkat | Cakupan | Alat |
|---|---|---|
| Unit | `AverageCalculator` (fungsi murni): rumus, pembulatan, flush, edge | Runner CLI sederhana `tests/inventory/run.php` (tanpa composer) |
| Integrasi DB | `InventoryService` terhadap DB uji (skema `inv_*` + data seed) di MySQL lokal; tiap kasus mengatur ulang saldo | PHP CLI dengan `php.exe` (jalur di memori `web-bengkel-environment`) |
| Konkurensi | N proses paralel (via beberapa `php.exe` bersamaan atau `curl` paralel ke endpoint uji) | Skrip uji + verifikasi invarian C1–C4 |
| Fault injection | Matikan di tengah posting (throw setelah ledger sebelum saldo) → pastikan rollback | Hook uji (`$o['_fail_at']`, hanya di mode uji) |
| E2E | Alur nyata kasir/servis/antar cabang lewat browser | Playwright (aturan global), bukti DB |
| Rekonsiliasi | C1–C9 pada data uji dan pada shadow | `inv_recon.php` |
| Regresi pembaca lama | `kartu_stok.php`, `MinMaxCalculator`, `pr_auto_draft`, pencarian item | uji perbandingan hasil lama vs baru |

### 15.2 Fixture numerik (hasil tepat yang diharapkan)

Konvensi: `avg6` = `average_cost` (6 dp); tampil = 2 dp half-up.

| ID | Skenario | Langkah | Hasil yang diharapkan |
|---|---|---|---|
| T1 | Pembelian pertama (AC-01) | Lokasi PST, stok 0; beli 100 @ 10.000 | qty 100, value 1.000.000,00, avg6 10000,000000 |
| T2 | Pembelian kedua (Contoh 1 PRD) | + beli 50 @ 12.000 | qty 150, value 1.600.000,00, avg6 10666,666667 (tampil 10.666,67) |
| T3 | Penjualan (Contoh 2) | jual 30 | HPP 320.000,00 ; qty 120 ; value 1.280.000,00 ; avg6 **tetap** 10666,666667 |
| T4 | Tolak stok minus (Contoh 3, AC-03) | jual 150 | INV-201 *"Tersedia: 120, diminta: 150"*; saldo tidak berubah; tidak ada ledger |
| T5 | Retur penjualan (Contoh 4, BR-07) | retur 10 pada `hpp_unit` 10666,666667 | in_value 106.666,67 ; qty 130 ; value 1.386.666,67 ; avg6 10666,666692 (tampil 10.666,67) |
| T6 | Kirim transfer (Contoh 5) | dari T3 (qty 120/value 1.280.000): kirim 50 | HPP/value_shipped 533.333,33 ; PST qty 70, value 746.666,67, avg6 10666,666667 ; tujuan tidak berubah |
| T7 | Terima di Cabang A | A stok awal 40 @ 9.800 (value 392.000,00); terima value 533.333,33 untuk 50 | A qty 90 ; value 925.333,33 ; avg6 10281,481444 (**tampil 10.281,48**) — lihat §17.1 (PRD bisnis menulis 10.148,15) |
| T8 | Koreksi harga, sebagian terjual (Contoh 6, AC-05) | A: beli 100 @ 10.000 (value 1.000.000), jual 60 (HPP 600.000,00; qty 40, value 400.000,00); koreksi 10.000→10.500, Q=100 | delta 500 ; R=40, S=60 ; ke persediaan +20.000,00 → value 420.000,00, avg6 10500,000000 ; PPV 30.000,00 ; HPP penjualan lama tetap 600.000,00 |
| T9 | Bonus (Contoh 7, AC-09) | faktur 100 @ 10.000 + bonus 5 | qty 105, value 1.000.000,00, avg6 9523,809524 (tampil 9.523,81) |
| T10 | Menghabiskan stok (Test 14) | dari saldo qty 3, value 100,00 (avg6 33,333333): jual 1, 1, 1 | HPP 33,33 ; 33,33 ; **33,34** (flush) ; akhir qty 0, value 0,00 |
| T11 | Stok nol lalu masuk (BR-04) | dari T10, beli 5 @ 20,00 | avg6 20,000000 ; value 100,00 |
| T12 | Idempotency (AC-11) | posting ulang `ref_no` sama | tidak ada baris baru; hasil sama (atau INV-301 sesuai flag) |
| T13 | Harga negatif/nol/qty ≤ 0 (Test 17/18) | | INV-103 / INV-104 / INV-101 |
| T14 | Transit gagal (EC-08) | kirim 50 lalu `returnTransfer` tanpa transaksi masuk lain | qty/value asal kembali ke nilai sebelum kirim; avg6 asal identik |
| T15 | Transit gagal dengan pembelian di antaranya | kirim 50; beli lagi @ harga lain; return | avg6 asal berubah sesuai rata-rata tertimbang (dokumentasi §17.3) |
| T16 | Terima sebagian | kirim 50, terima 30 | status `partially_received`; 20 tetap di transit; value_received proporsional |
| T17 | Koreksi tanpa approval (Test 28) | eksekusi sebelum `approved` | INV-404 |
| T18 | Maker = checker | | INV-403 |
| T19 | Superadmin jual melebihi stok (Test 19) | | INV-201 (tanpa pengecualian) |
| T20 | Konkurensi (Test 43) | 10 proses jual 1 unit dari stok 5 | tepat 5 sukses, 5 INV-201; saldo 0; C1–C4 hijau |
| T21 | Fault injection (Test 44) | gagal setelah ledger | tidak ada ledger/saldo/proyeksi tersimpan |
| T22 | Append-only (V-02) | `UPDATE/DELETE inv_ledger` | INV-501 |
| T23 | Periode closed | dokumen bertanggal periode closed | INV-401; koreksi hari ini tetap diterima (EC-10, Test 27) |
| T24 | Pembulatan | `10.005`, `10.015`, `0.005` | `10.01`, `10.02`, `0.01` (half-up) |

Tes PRD bisnis no. 1–48 dipetakan ke fixture ini dan ke E2E pada §18.

### 15.3 Kriteria selesai pengujian per fase

- Semua T-fixture hijau; invarian C1–C8 hijau pada akhir setiap suite.
- E2E Playwright untuk alur penjualan, pembelian, servis, retur, transfer, koreksi; tangkapan layar dan bukti query DB.
- Tidak ada regresi pembaca lama (§15.1).

---

## 16. Rilis dan Operasional

| Langkah | Detail |
|---|---|
| Lingkungan | Lokal (Laragon) → produksi shared hosting (migrasi dijalankan Rafi). Tidak ada staging terpisah; **salinan DB produksi** dipakai untuk latihan migrasi dan cutover bila tersedia |
| Urutan rilis | (1) migrasi skema `inv_*` (tanpa perubahan perilaku) → (2) kode service + halaman (mode `off`) → (3) normalisasi lokasi → (4) opening stock + `shadow` per cabang → (5) cutover `live` bertahap |
| Feature flag | `inv_mode:<cabang>` (§3.4) |
| Backup | Sebelum tiap langkah migrasi/cutover (`bak_*` + dump) |
| Monitoring | Dashboard ringkas di `inv_recon.php`: C1–C9, INV-201 hari ini, transit > ambang, `SHADOW_WOULD_REJECT` |
| Rollback | Per cabang (`live→shadow`), per migrasi (`down`), definisi view lama (§13.5) |
| Dokumentasi | Runbook ops, SOP koreksi, panduan opname, panduan maker-checker |
| Update proyek | `.ai/changelog`, `.ai/decisions` (D-xx), `CLAUDE.md` project (ganti Rule HPP FIFO → Moving Average), checklist-projek (`scripts/update-checklist.sh`) |
| Gerbang merge | Review cepat Sonnet, final Opus; tidak merge bila tes gagal |

---

## 17. Keputusan Terbuka dan Temuan atas PRD Bisnis

### 17.1 Temuan angka pada PRD bisnis (perlu dikoreksi BA)

| # | Lokasi | Temuan | Perhitungan |
|---|---|---|---|
| F-1 | §7 Contoh 5 | **Kesalahan hitung.** Rumus `((40 × 9.800) + (50 × 10.666,67)) / 90` bernilai **10.281,48**, bukan 10.148,15 yang tertulis. Nilai 10.148,15 baru benar bila stok awal Cabang A = 40 pcs @ **9.500** | 392.000 + 533.333,50 = 925.333,50 ; ÷ 90 = 10.281,48 |
| F-2 | §7 Contoh 2 | HPP `30 × 10.666,67 = 320.000,10`, bukan `320.000`, bila average dibulatkan 2 dp sebelum dikali. Angka `320.000` hanya tercapai bila presisi internal dijaga (`avg6`) | model §5.1 menghasilkan 320.000,00 |
| F-3 | §7 Contoh 5 | `50 × 10.666,67 = 533.333,50`, bukan `533.333`; model §5.1 menghasilkan 533.333,33 | |
| F-4 | §7 Contoh 6 | Rumus `((40×10.000)+(40×500))/40` memakai "40 pcs" dua kali padahal tidak ada qty baru; hasil 10.500 benar tetapi notasinya menyesatkan; implementasi memakai `value + delta×R` | |

### 17.2 Hal yang tidak diatur PRD bisnis (butuh keputusan)

| ID | Keputusan | Opsi / rekomendasi | Diperlukan untuk |
|---|---|---|---|
| D-01 | Kunci lokasi kanonik; arti `001` dan `''` | `tbcabang.kode_cabang`; selidiki asal 88 + 24 baris | §13.1 |
| D-02 | Sumber biaya awal opening stock | Beli terakhir → acuan Access → master → manual | §13.2 |
| D-03 | Saldo awal dari opname fisik atau `tblitem.quantity` | Opname fisik (ledger tak bisa dipercaya) | §13.2 |
| D-04 | Kapan stok servis turun | (a) saat bayar, (b) saat item ditambah, (c) reservasi. **Rekomendasi (b)** | §7.4 |
| D-04b | Perlu reservasi keranjang? | Tidak pada baseline | §5.13 |
| D-05 | Definisi "barang sudah keluar fisik" | Penjualan: setelah cetak/serah; servis: sebelum status `bayar` | §5.6 |
| D-06 | Peran dan ambang approval; apakah posisi "Finance HQ" dibuat | Buat posisi baru | §8.2 |
| D-07 | COA dan jurnal persediaan | Putuskan setelah R1–R2 | §10 |
| D-08 | Model pembulatan | **Rekomendasi §5.1** (avg 6 dp, value otoritatif) | §5.1 |
| D-10 | HPP transaksi lama (0) dan laporan | Tidak di-backfill; tandai "HPP tidak tercatat" | §7.5, §13.3 |
| D-13 | Biaya masuk: diskon faktur dan PPN | Alokasi diskon faktur proporsional; PPN dikecualikan bila PKP | §7.1 |
| D-14 | Kode `tbstok.tipe` transfer `A/B` vs mempertahankan 2/3 | `A/B` setelah audit pembaca | §5.9 |
| D-15 | Penerimaan sebagian / selisih transit | Tetap `partially_received` sampai kembali atau hapus buku (approval) | §5.5 |
| D-16 | Definisi "qty yang masih ada" untuk koreksi harga (tanpa lot) | `R = min(qty_on_hand, Q)` | §5.7 |
| D-17 | Integrasi koreksi harga/retur ke modul Hutang | Debit/Credit Note dari `ppv_amount` | §7.12 |

### 17.3 Nuansa aturan PRD yang tidak sepenuhnya benar secara matematis

1. **BR-15 (batal transit):** "average asal tidak terpengaruh" hanya benar bila **tidak ada transaksi masuk lain** di lokasi asal selama transit. Jika ada, mengembalikan barang pada biaya lamanya menggeser average (rata-rata tertimbang). Implementasi mengikuti rumus rata-rata tertimbang; hasilnya identik dengan PRD pada kasus sederhana (T14) dan berbeda pada kasus T15.
2. **BR-09 Cancel "tanpa jejak":** bertentangan dengan V-02 (ledger tidak boleh dihapus). Implementasi: baris pembalik `is_cancel=1` yang disembunyikan di tampilan default; "kembali ke average semula" dijamin hanya bila tidak ada transaksi masuk lain setelah transaksi asal.
3. **BR-23 tanpa lot:** Moving Average tidak mengenal identitas lot, sehingga "qty yang sudah terjual dari pembelian ini" tidak terdefinisi; dipakai asumsi D-16.
4. **V-09 dan nilai persediaan:** mengalikan average yang sudah dibulatkan menghasilkan selisih sen terhadap nilai sebenarnya; diselesaikan dengan `total_value` otoritatif dan flush saat qty = 0.

---

## 18. Traceability

### 18.1 Business Rule → Persyaratan teknis

| BR | Persyaratan teknis | Bagian |
|---|---|---|
| BR-01/02/03/04 | `postIn`, `postOut`, `AverageCalculator` | §5.2–5.3 |
| BR-05 | INV-201 di bawah lock; tanpa bypass role | §5.2, §5.11, §8.2 |
| BR-06 | `correction_date` = server; form read-only; larang endpoint edit | §5.10, §7, §13.6 |
| BR-07 | `postIn` pada `hpp_unit` asal | §5.4, §7.5 |
| BR-08 | `inv_period`, `PeriodGuard` | §4.9, §9.4 |
| BR-09 | `CANCEL_IN` vs `VOID_RETURN` | §5.6 |
| BR-10 | `inv_balance` per `(kd_cabang,no_item)`; normalisasi lokasi | §4.3, §13.1 |
| BR-11 | `inv_avg_history` | §4.5 |
| BR-12 | `transaction_date` vs `correction_date` | §4.4, §5.10 |
| BR-13/14/15 | `inv_transfer(+line)`, kirim/terima/kembali | §4.6, §5.5, §7.8 |
| BR-16 | pembelian per lokasi | §7.1 |
| BR-17/18/19 | bonus, replacement, klaim | §5.8, §4.7 |
| BR-20 | di luar cakupan | §1.3 |
| BR-21..25 | `CorrectionService` | §5.7 |
| BR-26 | jurnal koreksi (modul finance) | §10 |
| BR-27 | maker-checker + audit | §8 |

### 18.2 Acceptance Criteria → Uji

| AC | Uji |
|---|---|
| AC-01 | T1 |
| AC-02 | T3 |
| AC-03 | T4, T19 |
| AC-04 | T22 + guard endpoint (INV-405) |
| AC-05 | T8 |
| AC-06/07 | T6, T7 |
| AC-08 | T5 |
| AC-09 | T9 |
| AC-10 | T17, T18 |
| AC-11 | T12 |
| AC-12 | integrasi: ubah `opname_hq_threshold`, posting berikutnya mengikuti nilai baru |

### 18.3 Hubungan dengan task planning

Task di `PLANNING_MOVING_AVERAGE_WEBBASE_2026-10-05.md` §6 tetap berlaku. Pemetaan komponen → task: `AverageCalculator`/`LedgerRepository`/`BalanceRepository` → F1-01..07; `ProjectionWriter` → F1-06; flag mode → F1-08; shadow/rekonsiliasi → F1-09, §13.4/13.7; integrasi modul → F2-01..14 (§7); `CorrectionService`/RBAC/periode → F3-01..09 (§5.7, §8, §9.4); fitur lanjutan → F4-xx (§5.8, §5.12); laporan/jurnal/cutover → F5-xx (§10, §11, §13.5).

Tambahan task dari dokumen ini (usulan nomor): **F0-10** audit pembaca `tbstok.tipe` (D-14); **F0-11** uji `ALTER tbstok` (index, `created_at`, `ledger_id`) pada salinan DB; **F1-11** helper `inv_loc_canon()` + `inv_assert_legacy_write_allowed()`; **F1-12** trigger append-only + uji `DEFINER`; **F2-15** perubahan `tblservis_barang`, `tblpembelian_detail`, `tblpenjualan_detail` (§4.12); **F5-11** rekonsiliasi C1–C9 sebagai halaman + notifikasi.

---

## Lampiran A — Pseudocode `AverageCalculator` (fungsi murni)

```php
final class AverageCalculator {
  /** Semua angka dalam string desimal; gunakan bcmath bila ada, fallback integer-scaled. */
  public static function round2(string $x): string   { /* half-up, 2 dp */ }
  public static function round6(string $x): string   { /* half-up, 6 dp */ }

  /** Masuk. Mengembalikan [new_qty,new_value,new_avg,rounding_residual]. */
  public static function applyIn(string $q0, string $v0, string $a0, string $qIn, string $inValue): array {
    if (bccomp($q0,'0',3) === 0) {            // BR-04
      $resid = $v0;                           // sisa pembulatan bila ada
      $nv = $inValue; $nq = $qIn;
    } else { $resid='0'; $nq = bcadd($q0,$qIn,3); $nv = bcadd($v0,$inValue,2); }
    $na = self::round6(bcdiv($nv, $nq, 8));
    return [$nq,$nv,$na,$resid];
  }

  /** Keluar. Mengembalikan [new_qty,new_value,avg(tetap),hpp_value]. */
  public static function applyOut(string $q0, string $v0, string $a0, string $qOut): array {
    if (bccomp($qOut,$q0,3) === 0)  $hpp = $v0;                       // flush
    else                            $hpp = self::round2(bcmul($qOut,$a0,8));
    if (bccomp($hpp,$v0,2) > 0)     $hpp = $v0;                       // pengaman
    return [bcsub($q0,$qOut,3), bcsub($v0,$hpp,2), $a0, $hpp];
  }

  /** Penyesuaian nilai tanpa qty (koreksi harga/revaluasi). */
  public static function applyValueAdjust(string $q0, string $v0, string $a0, string $dv): array {
    $nv = bcadd($v0,$dv,2);
    if (bccomp($nv,'0',2) < 0) throw new InventoryException('INV-202');
    $na = bccomp($q0,'0',3) === 0 ? $a0 : self::round6(bcdiv($nv,$q0,8));
    return [$q0,$nv,$na];
  }
}
```

Fallback tanpa `bcmath`: representasikan uang dalam sen (`int`) dan biaya dalam mikro-rupiah (`int`) sehingga semua operasi integer; PHP 64-bit menampung ≤ 9,2·10¹⁸. Kasus uji T24 memverifikasi kedua implementasi menghasilkan angka identik.

## Lampiran B — Pseudocode `InventoryService::postOut` (kerangka)

```php
public function postOut(string $loc, string $item, string $qty, array $o): array {
  $this->assertMode($loc); $this->periodGuard->assertCanPost($loc);
  $this->validate($qty, $item, $loc, $o);
  return $this->tx(function() use ($loc,$item,$qty,$o) {            // retry 1213/1205 ×3, jeda acak
    if ($hit = $this->ledger->findIdem($o,$loc)) return $this->onDup($hit,$o);
    $bal = $this->balance->lockOrCreate($loc,$item);                 // SELECT … FOR UPDATE
    if ($this->mode($loc)==='live' && bccomp($bal['qty_on_hand'],$qty,3) < 0)
        throw new InventoryException('INV-201', ['tersedia'=>$bal['qty_on_hand'],'diminta'=>$qty]);
    [$nq,$nv,$na,$hpp] = AverageCalculator::applyOut($bal['qty_on_hand'],$bal['total_value'],$bal['average_cost'],$qty);
    $lid = $this->ledger->insert([... 'qty_out'=>$qty,'value_delta'=>'-'.$hpp, ...]);
    $this->balance->update($loc,$item,$nq,$nv,$na,$lid);
    if ($this->mode($loc)==='live') $this->projection->writeOut($loc,$item,$qty,$o,$lid);
    return ['ledger_id'=>$lid,'hpp_value'=>$hpp,'hpp_unit'=>bcdiv($hpp,$qty,6),
            'qty_after'=>$nq,'value_after'=>$nv,'average_cost'=>$na];
  });
}
```

## Lampiran C — Skrip uji kasus konkurensi (garis besar)

1. Seed: PST, item uji, qty 5, value 50.000.
2. Jalankan 10 proses `php.exe` paralel, masing-masing memanggil `postOut(qty=1, ref_no=UNIQ_i)`.
3. Harapan: 5 sukses, 5 `INV-201`; `qty_on_hand=0`, `total_value=0`; `SUM(value_delta)` konsisten; C1–C4 hijau; tidak ada deadlock yang lolos ke pemanggil.
4. Ulangi dengan 2 item dan urutan baris terbalik (uji urutan kunci).

## Lampiran D — Query operasional

```sql
-- Saldo per lokasi
SELECT kd_cabang, COUNT(*) item, SUM(qty_on_hand) qty, SUM(total_value) nilai FROM inv_balance GROUP BY kd_cabang;

-- Kartu stok satu item
SELECT id, correction_date, transaction_date, trx_type, ref_no, qty_in, qty_out, unit_cost, avg_after, qty_after, value_after
FROM inv_ledger WHERE kd_cabang=? AND no_item=? AND is_cancel=0 ORDER BY id;

-- Transit melewati ambang
SELECT t.no_transfer, t.from_lokasi, t.to_lokasi, DATEDIFF(NOW(), t.shipped_at) hari
FROM inv_transfer t WHERE t.status IN ('in_transit','partially_received')
  AND DATEDIFF(NOW(), t.shipped_at) > (SELECT value FROM inv_config WHERE config_key='transit_alert_days');

-- PPV periode
SELECT period, kd_cabang, SUM(total_variance) total FROM inv_ppv_log GROUP BY period, kd_cabang;

-- Kesiapan go-live (shadow)
SELECT DATE(at) hari, COUNT(*) would_reject FROM inv_audit_log WHERE action='SHADOW_WOULD_REJECT' GROUP BY DATE(at);
```

## Lampiran E — Glosarium

| Istilah | Arti |
|---|---|
| Lokasi | `kd_cabang` kanonik; Gudang Pusat = `PST` |
| Average / `average_cost` | Biaya rata-rata bergerak per item per lokasi (6 dp internal, 2 dp tampil) |
| HPP | Nilai keluar = `qty × average` saat posting (di-flush bila stok habis) |
| Proyeksi | Baris `tbstok` yang ditulis service agar pembaca lama tetap benar |
| Shadow / Live | Mode: service mencatat paralel tanpa memblok / sumber kebenaran |
| PPV | Purchase Price Variance (Selisih Harga Pembelian) |
| Forward-only | Koreksi hanya sebagai transaksi baru bertanggal posting hari ini |
