# PRD Teknis — FIT MOTOR Web Bengkel

> **Versi:** 1.0 (reverse-engineered dari kode + database live)
> **Tanggal analisis:** 2026-09-25 · **Branch:** `main` (commit terakhir `c973e6d`)
> **Audiens:** developer, DBA, DevOps, reviewer teknis
> **Dokumen pendamping:** [PRD_BISNIS.md](./PRD_BISNIS.md) (versi non-IT) · [ERD.md](./ERD.md) · [LRS.md](./LRS.md)
> **Konvensi:** `[TBC]` = belum bisa dipastikan dari kode/DB, perlu konfirmasi Rafi. Semua angka baris tabel = estimasi `information_schema.TABLE_ROWS` pada 2026-09-25.

---

## 1. Ringkasan Sistem

FIT MOTOR Web Bengkel adalah **ERP mini untuk jaringan bengkel motor** (1 pusat + 4 cabang) yang menggantikan aplikasi desktop Microsoft Access. Cakupan: master data, servis (reguler/garansi/jemput-antar), antrian & progres mekanik, diagnosa (keluhan → temuan → penawaran part), pembelian (PR→RFQ→PO→DO→Invoice→Hutang), penjualan & piutang, antar cabang, stok, kasir & setoran keuangan, promo/member/CRM, komplain pelanggan, HRD/absensi, serta sinkronisasi dengan data Access selama masa transisi.

| Aspek | Nilai |
|---|---|
| Tipe aplikasi | Monolit PHP prosedural (server-rendered), 1 database MySQL/MariaDB bersama untuk semua cabang |
| Runtime produksi | Shared hosting **tanpa Docker** (`fitmotor.web.id`); lokal: Laragon (Windows) + WSL2 |
| PHP | 8.3.16 (dikunci di `composer.json`), driver `mysqli` |
| Database | `fitmotor_dbbengkel` — **294 tabel aktif**, 8 tabel backup, **62 view**, 4 trigger, 2 stored function |
| Kode | ± 768 file PHP di `app/` (root) + `app/_keuangan/kasir` (± 45 file) + `app/_komplain` (± 16 file) + `app/_ajax` (35 endpoint) + `api/sync/*` |
| Library PHP | `dompdf`, `tcpdf`, `fpdf`, `phpoffice/phpspreadsheet`, `phpoffice/phpword`, `smalot/pdfparser` (ekstraksi bukti transfer) |
| Front-end | Ace Admin (Bootstrap 3) + jQuery, DataTables, Select2, Chart.js/Highcharts, FullCalendar |
| Integrasi | Accurate Online (OAuth/signature), Access → MySQL sync (VBS + endpoint `api/sync`), WhatsApp (link) |
| Skala data | 100.504 servis, 359.339 baris barang-servis, 91.371 baris jasa-servis, ± 36,8 rb pelanggan & kendaraan, 318.890 mutasi stok, 55 user, 98 karyawan |

> Catatan: aturan "Docker First" di `~/.claude/CLAUDE.md` berlaku untuk project **baru**; sistem produksi ini memang berjalan di shared hosting dan tidak diretrofit.

---

## 2. Tujuan & Non-Tujuan

**Tujuan produk**
1. Satu sumber kebenaran data servis/stok/keuangan lintas cabang, menggantikan Access per-cabang.
2. Alur servis lengkap dari pelanggan datang sampai bayar, termasuk garansi, DP, retur, komisi.
3. Kontrol keuangan cabang: kas awal → transaksi → closing → setoran ke keuangan pusat → setoran ke bank.
4. Loyalty/CRM otomatis (tier member, promo periode, statistik kunjungan).
5. Traceability: audit log, log approval, log perubahan status.

**Non-tujuan (saat ini)**
- Bukan aplikasi API/SPA; tidak ada REST API publik selain `api/sync/*` dan endpoint AJAX internal.
- Belum multi-tenant per perusahaan (`master_perusahaan` ada tapi hanya 1 baris).
- Retrofit Docker/Laravel/NextJS pada sistem lama **tidak** dilakukan (lihat catatan §1).

---

## 3. Arsitektur

### 3.1 Lapisan
```
Browser ──> app/*.php (halaman + handler POST di file sama) ──> mysqli ──> MySQL fitmotor_dbbengkel
              │  include : config/koneksi.php, config/rbac.php, app/_include_*.php, app/functions/*
              │  ajax    : app/_ajax/*, app/ajax_*.php, app/_komplain/ajax_*.php
              │  export  : dompdf/tcpdf/fpdf (PDF), PhpSpreadsheet/PHPExcel (XLS), print HTML
              └─ integrasi: Accurate OAuth · Access sync (api/sync, _sync_gabung_direct.vbs)
```
- Pola dominan: **satu file = halaman + proses CRUD + SQL inline**. Prefix nama file menunjukkan fungsi: `save_*` (simpan), `edit_*`, `del_*`/`hapus_*`, `cari_*` (modal pencarian), `lap_*` (laporan), `pmby_*` (pembayaran), `master*`, `ajax_*`.

| Folder | Isi |
|---|---|
| `app/` | Halaman utama semua modul (menu ditentukan `app/menu_config.php`) |
| `app/_keuangan/kasir/` | Modul Keuangan Kasir (closing, setoran, master akun); bootstrap `koneksi_kasir.php` |
| `app/_komplain/` | Modul Penanganan Komplain (dibangun 2026-09); bootstrap `koneksi_komplain.php` |
| `app/_ajax/`, `app/functions/`, `app/includes/` | Endpoint AJAX, helper fungsi (`function_servis.php` dll), include layout |
| `app/_print/`, `app/dompdf/`, `app/PHPExcel/` | Template cetak dan library ekspor |
| `config/` | `koneksi.php`, `rbac.php`, `session_check.php`, `permission_check.php`, `auth_session.php`, `accurate_config.php`, `db_env.php` |
| `db/migrations/` | Migrasi SQL manual bertanggal (`YYYY-MM-DD_*.sql` + runner `*_run_*.php`) |
| `api/sync/` | Endpoint penerima data sync Access (`master-data`, `transactions`, `report-run`) |
| `docs/` | FSD, analisis, planning, keputusan bisnis |
| `archive/`, `app/_archive/` | Kode lama/dead-code (tidak dipakai; jangan dijadikan acuan) |

### 3.2 Konfigurasi & Environment
- Kredensial DB dibaca dari `app/db_env.php` **dan** `config/db_env.php` (dua file terpisah; tiap `koneksi.php` mencari `db_env.php` di direktorinya sendiri; keduanya `.gitignore`, berisi `putenv()` `DB_HOST/DB_USER/DB_PASS/DB_NAME`). Setup mesin baru wajib membuat keduanya.
- **Utang teknis:** masih ada fallback kredensial hardcode di `app/koneksi.php` dan file lama sejenis (codebase-wide, belum dijadwalkan).
- Redirect login pernah hardcode base URL produksi (`cek_login.php`) → rawan mismatch lokal/prod. [TBC apakah sudah dibersihkan]
- `display_errors` sudah dimatikan (audit menu_config Batch 1).

### 3.3 Autentikasi & Otorisasi (RBAC)
1. Login: `login.php` → `cek_login.php` (username/kode karyawan + password + **cabang**) → session `_iduser`, `_cabang`, `user_akses`.
2. Data user: `tbuser` (55 baris) berelasi ke `tbuser_karyawan` (98 baris) via `id_karyawan`; keduanya menyimpan `kode_posisi`, `kode_level`, `kode_cabang`.
3. **RBAC dinamis**: `tb_master_posisi.permissions` (JSON) berisi daftar permission code; `app/_include_menu_rbac.php` memuat permission user dari `kode_posisi`, lalu `filterMenuByPermissions()` memfilter `app/menu_config.php`. Grup tanpa key `permission` (mis. *Penanganan Komplain*) tampil bila minimal satu submenu lolos.
4. Guard halaman: `lib/rbac.php` / `config/permission_check.php`. Audit 2026-09 menemukan dan menutup 10 + 26 handler tanpa guard login.
5. Lapisan RBAC lama (level numerik `user_akses` di `config/rbac.php`, tabel `tb_user_roles` 9 baris) masih hidup berdampingan → risiko inkonsistensi hak akses.

**Posisi (role) & level** (data live `tb_master_posisi`, `tb_master_level`)

| Kode | Nama | Departemen | `user_akses_level` | Level |
|---|---|---|---|---|
| ADM | Administrator | Management | 1 | ADM-1 |
| CS | Customer Service | Front Office | 2 | CS-1, CS-2 |
| KSR | Kasir | Front Office | 2 | KSR-1, KSR-2 |
| MK | Mekanik | Workshop | 4 | MK-1..3 |
| KM | Kepala Mekanik | Workshop | 10 | KM-1, KM-2 |
| PGD | Pengadaan | Purchasing | 5 | PGD-1 |
| CRM | CRM Staff | Marketing | 6 | CRM-1 |
| MNG | Manager | Management | 7 | MNG-1 |
| KACAB | Kepala Cabang | Management | 7 | — |
| KEU | Keuangan | Finance | 8 | KEU-1 |
| HRD | HRD Staff | Human Resource | 9 | HRD-1 |

Permission code mengikuti pola `<modul>_read|add|approve|...` (mis. `kasir_menu_read`, `kasir_approve`, `kasir_admin`, `komplain_input`, `komplain_approve_rework`, `issue_approve_supervisor`, `diskon_approval_approve`). Daftar lengkap = seluruh key `permission` di `app/menu_config.php`.

### 3.4 Multi-Cabang
- Cabang: `PST` (Pusat/Gudang), `PESALAKAN` (Adiwerna), `PACUL`, `CIKDITIRO`, `TRAYEMAN` (tipe cabang 2 = mitra/eksternal; lainnya tipe 1 = internal).
- Hampir semua tabel transaksi punya `kd_cabang`. **Identitas transaksi servis = `(no_service, kd_cabang)`** — `no_service` **tidak unik lintas cabang** (30 rb+ duplikat; audit 17 file JOIN `tblservice` tuntas 2026-07-19, sisa 3 file risiko rendah). Semua JOIN ke `tblservice` **wajib** menyertakan `kd_cabang`.
- Dua kolom kode cabang berbeda: `tbcabang.kode_cabang` (mis. `PACUL`) dan `tbcabang.cabang_ref_kode` (numerik, mis. `201809001`); tabel modul kasir mereferensi `cabang_ref_kode` lewat FK, tabel lain memakai `kode_cabang`. Jaga saat JOIN lintas modul.

---

## 4. Inventaris Modul & Halaman

Sumber: `app/menu_config.php` (menu aktif) + folder modul. Nama tabel merujuk [ERD.md](./ERD.md) / [LRS.md](./LRS.md).

### 4.1 Dashboard & Isu
| Halaman | Fungsi | Tabel |
|---|---|---|
| `index.php`, `menu_dashboard.php` | Dashboard, sidebar RBAC | `tbuser`, `tb_master_posisi` |
| `issue_add.php` (+ approval) | Lapor masalah data (pelanggan/kendaraan/komisi/stok/sistem); approval `self/supervisor/owner` per jenis masalah | `tbl_issue`, `tbl_issue_progress_log`, `master_jenis_masalah` |
| `admin_deteksi_pelanggan_dobel.php`, `customer_merge_approve.php` | Deteksi duplikat & merge pelanggan (snapshot JSON, alias nomor lama) | `customer_merge_log`, `customer_alias` |
| `kendaraan_pindah_tangan*.php` | Pindah kepemilikan kendaraan — **menu dinonaktifkan sementara** (uncomment di `menu_config.php`) menunggu migrasi Access | `permintaan_pindah_kepemilikan_kendaraan`, `kepemilikan_kendaraan`, `kendaraan_plat_history` |

### 4.2 Data Master
| Grup | Halaman | Tabel |
|---|---|---|
| Daftar Item | `barang.php`, `barang_kategori`, `barang_satuan`, `barang_pabrik`, `barang_rak`, `margin_jual`, `status_harga`, `workorder-list` (paket), `jasa-list`, `*-motor-mapping` (WO/Jasa/Item ↔ jenis motor), `master-keluhan-crud`, `master-workorder-mapping`, `master-fastmoves`, `master-temuan`, `master-temuan-mapping(-jasa)`, `master-barang-custom`, `hjual_jasa` | `tblitem`, `tblitemjenis`, `tblitemsatuan`, `tbkategori_rak`, `tbpabrik_barang`, `tbhargajual`, `tbworkorderheader/detail`, `tbmaster_*` |
| Pelanggan | `pelanggan.php`, `pelanggan_kategori`, `member-loyalty-program`, `statistik-pelanggan` | `tblpelanggan`, `master_kategori_member`, `statistik_pelanggan` |
| Supplier | `supplier.php` | `tblsupplier`, `tblsupplier_spart` |
| Cabang | `cabang`, `cabang_tipe`, `setting_antarcabang` | `tbcabang`, `tbcabang_tipe`, `tbl_setting_antarcabang` |
| Mekanik | `mekanik`, `mekanik_level`, `master_kepala_mekanik`, `master-tarif-jemput` | `tbuser_karyawan`, `tbmekanik_level`, `tb_kepala_mekanik_schedule`, `master_tarif_jemput_range` |
| Kendaraan | `motor_tipe/pabrik/kategori/warna`, `kendaraan` | `tbtipe_motor`, `tbpabrik_motor`, `tbkategori_motor`, `tbwarna`, `tblkendaraan` |
| Wilayah | `prop`, `kab`, `kec`, `desa` | `tbwilayah` |
| Akuntansi ringan | `akun_kas`, `akun_biaya`, `keping` (nominal rupiah) | `tblakunkas`, `tbakun`, `tbkeping` |
| Akses | `user`, `master-posisi`, `master_karyawan`, `master_perusahaan` | `tbuser`, `tb_master_posisi`, `tb_master_level`, `tbuser_karyawan`, `master_perusahaan` |
| Sync Access | `access-sync`, `access-sync-report`, `access-sync-monitor` | `sync_access_runs`, `sync_access_row_errors`, `stg_access_*` |

### 4.3 Pembelian & Pengadaan
Alur: **Dashboard MIN/MAX → Rencana Order → PR → (RFQ) → PO (approval bertingkat) → DO → Pembelian/Invoice (QC) → Hutang/Pembayaran → Retur**.

| Halaman | Fungsi | Tabel |
|---|---|---|
| `procurement_dashboard.php`, `rencana_order.php`, `pr_auto_draft.php` | Perhitungan min/max/ROP (window 12 minggu), auto-draft PR bila stok < minimum | `tblitem_minmax`, `tblpenjualan_mingguan`, `tblpurchase_request_*` |
| `pr_add.php` | Purchase Request + approval log | `tblpurchase_request_header/detail`, `tblpr_approval_log`, `tblpr_tracking` |
| RFQ (di alur PR) | Permintaan penawaran & respon supplier | `tblrfq_header/detail`, `tblrfq_supplier_response` |
| `pesanan_pembelian_*`, `pesanan_pembelian_upload.php`, `master-approval-pembelian.php` | PO manual/Excel, **approval bertingkat berdasar nilai** (`tb_master_approval_pembelian`: batas bawah/atas, posisi approver) | `tblorder_header/detail`, `tblpo_approval_log` |
| `do_from_po.php`, `do_list.php` | Delivery Order & tracking | `tbldelivery_order_*`, `tbldo_tracking` |
| `pembelian*.php`, `pembelian_dari_po.php` | Invoice pembelian, QC (`status_qc`), update stok | `tblpembelian_header/detail` |
| `pmby_hutang.php` | Pembayaran hutang supplier | `tblhutang_header/detail`, `tbsaldo_hutang` |
| `retur_pembelian.php` | Retur ke supplier | `tblretur_pembelian_*` |
| `alarm-harga-beli.php` | Alarm kenaikan/penurunan harga beli melewati threshold (trigger `trg_alarm_harga_beli`) | `alarm_harga_beli`, `tb_master_threshold_harga` |

Bug kritis yang sudah diperbaiki di modul ini (2026-07-18): collation JOIN silent-fail, SQL injection cetak nota PO, approval bertingkat PO bisa di-bypass lewat "Lanjut ke DO".

### 4.4 Penjualan
| Halaman | Fungsi | Tabel |
|---|---|---|
| `pesanan_penjualan*.php` | Sales order | `tblorderjual_header/detail` |
| `penjualan*.php` (`penjualan_dari_so`, `penjualan_add`, `penjualan_struk`) | Kasir/POS, potong stok, cetak struk | `tblpenjualan_header/detail`, `tbstok` |
| `retur_penjualan.php` | Retur & refund | `tblretur_penjualan_*` |
| `pmby_piutang*.php` | Pembayaran piutang | `tblpiutang_*`, `tbsaldo_piutang` |

Toggle "Buat Servis dari Penjualan" (Task 3): `tblservis_barang.asal_barang` = `SERVIS` / `PART-CUST` / `PENJUALAN`, `tblservice.ref_no_penjualan_asal`, guard duplikat.

### 4.5 Antar Cabang
Tiga jalur (lihat `docs/PANDUAN_ALUR_PUSAT_CABANG.md`):
1. **Internal (transfer jual-beli)**: Buat Pesanan → Tarik Data (Kirim) → Penerimaan (PO otomatis) → Pembelian "Dari PO" (stok cabang tujuan baru bertambah setelah nota diinput).
2. **Pengadaan Barang**: Permintaan/Push → Konfirmasi Kirim (potong stok asal) → Konfirmasi Terima (tambah stok) — tanpa nota terpisah.
3. **Cabang Mitra (eksternal)**: upload Excel/manual + penerimaan mitra. Harga/diskon/tempo dari `tbl_setting_antarcabang`.

Tabel: `tblorder_antarcab_header/detail`, `tbl_setting_antarcabang`, `gabung_rekonsil_antarcabang`.

### 4.6 Servis (modul inti)
| Halaman | Fungsi |
|---|---|
| `input_kepala_mekanik_harian.php` | Jadwal kepala mekanik harian (`tbl_kepala_mekanik_harian`) |
| `servis-carinopol.php` → `servis-input-reguler.php` | Cari nopol/pelanggan → form booking servis (keluhan, jemput-antar, foto) |
| `kelola-antrian.php`, `dashboard-antrian-servis.php`, `servis-reguler.php` | Antrian (`menunggu→diproses→selesai/batal`; prioritas `normal/urgent/vip`), monitor mekanik, sinkron `tblservice.status_servis` |
| `workorder-input.php`, keluhan/temuan | Work order mekanik; temuan → penawaran part/jasa → persetujuan pelanggan (`tbservis_temuan`, `tbservis_penawaran_part/jasa`, `tbservis_pending_items`) |
| `servis-reguler-byr.php` | Pembayaran (tunai/transfer/QRIS split), cetak nota; memicu trigger statistik & komisi |
| `servis-carinopol-garansi.php`, `servis-garansi.php` | Servis garansi: `is_garansi=1`, `ref_no_service_original`, masa garansi per kategori member (`tb_master_garansi`, `tbmaster_kategori_member.masa_garansi_*`) |
| `servis-cancel-proses.php`, `_modal_cancel_service*.php` | Cancel + kategori alasan + refund DP (`tb_log_cancel_servis`) |
| `servis-reguler-jemput.php`, `report-tracking-keluhan.php` | Jemput-antar (tarif jarak berjenjang) & tracking keluhan |
| `master-diskon-periode.php`, `approval-diskon.php` | Promo periode & approval diskon manual (`tb_approval_diskon`) |
| `laporan-dp.php` | DP servis (`tb_dp_servis`: pending/offset/batal) |
| `retur_servis.php` | Retur jasa/barang servis (`tblretur_servis_*`) |

**Status servis** (`tblservice.status_servis`): `datang → diproses → selesai → bayar`, cabang: `cancel`. Distribusi live: bayar 103.513, diproses 18, datang 11, selesai 8. `tblservice.status` (char 1) = kolom legacy Access (`'3'` = final). **Komisi** disimpan permanen di `servis_komisi` (peran: mekanik1-4, kepala_mekanik1-2, admin1-2) saat `selesai/bayar/revisi_tiket`; persentase snapshot di `tblservice.persen_*`.

**Diagnosa:** `tbmaster_keluhan` (116 baris, ada approval cabang→pusat) → `tbmaster_keluhan_workorder` (WO default) → `tbservis_keluhan_status` → `tbservis_temuan` (`jenis_perbaikan`: setting/penggantian_part) → `tbservis_penawaran_part/jasa` (status pending/disetujui/ditolak + alasan tolak) → dipindah ke `tblservis_barang/jasa` saat disetujui. `tbservis_penawaran_rejection_summary` menghitung acceptance rate lewat trigger.

### 4.7 Stok
| Halaman | Fungsi |
|---|---|
| `penyesuaian-stok-masuk-manual.php` / `keluar-manual.php` / `otomatis.php` | Penyesuaian stok |
| `stok-akhir.php`, `kartu_stok.php` | Stok akhir (`view_stok`) & kartu stok |

Kartu stok = tabel `tbstok` (318.890 baris), kolom `tipe`: 1 Stok Awal · 2 Pembelian/DO · 3 Penjualan · 4 Servis · 5 Penyesuaian Masuk · 6 Penyesuaian Keluar · (7 Retur Pembelian, 8 Retur Penjualan — dipetakan di UI `kartu_stok.php`, belum ada datanya). Stok = Σ masuk − Σ keluar per `(no_item, kd_cabang)`.

### 4.8 Laporan (± 49 file `lap_*` + `laporan_*`)
Pembelian, PO, retur beli, hutang (per supplier/detail), penjualan, profit penjualan, retur jual, piutang, antar cabang (kirim/terima/ringkasan), servis, rekap kunjungan, cancel servis, profit & insentif, kas masuk/keluar, stok masuk/keluar manual, konsolidasi Access. Output: web + PDF + XLS + print.

### 4.9 Keuangan Kasir (`app/_keuangan/kasir`)
Alur harian cabang → pusat:
```
Kas Awal ─> Transaksi (Pemasukan / Pengeluaran / Data Penjualan+Servis) ─> Kas Akhir ─> Closing
  └ nominal_minimum per cabang (kas_awal_config)                                        │
Closing ─> [Revisi closing: ajukan → approve/reject] ─> Serah Terima Kasir (pergantian shift)
Closing ─> Setoran ke Staff Keuangan (kurir) ─> Validasi Keuangan (OK / SELISIH / Dikembalikan ke CS)
Setoran valid ─> Setoran ke Bank (Tunai/Transfer + bukti) ─> Pengambilan setoran (internal/hutang cabang; verifikasi mutasi bank)
```
- `kasir_transactions.status`: `on proses`, `end proses`, `dibatalkan`. `deposit_status`: `Belum Disetor → Sedang Dibawa Kurir → Diterima Staff Keuangan → Validasi Keuangan OK|SELISIH → (Dikembalikan ke CS) → Sudah Disetor ke Bank`, plus `Diserahterimakan`, `Pending Serah Terima`.
- Pinjam-meminjam antar closing (`jenis_closing`: `closing/dipinjam/meminjam`) via `closing_transaction_groups/details`.
- Pembukaan kembali transaksi butuh konfirmasi admin (`konfirmasi_buka_transaksi`); perubahan tercatat di `audit_log_closing_kasir`.
- Bukti transfer diekstrak (`smalot/pdfparser`) dengan `confidence` high/medium/low (`pengambilan_setoran_pembayaran`).
- Master: akun (34 akun: pemasukan/pengeluaran, kategori biaya, umur pakai), nama transaksi (159), rekening cabang (5), nominal/keping, kas awal per cabang.
- Sidebar hanya menampilkan **Dashboard Kasir** sebagai pintu masuk; halaman transaksional butuh parameter `kode_transaksi` sehingga sengaja tidak di-link langsung (keputusan Rafi 2026-09-18).
- Permission: `kasir_menu_read` (grup), `kasir_approve` (master & approval), `kasir_admin` (keuangan pusat).
- Cutover (2026-09-12): Task 16 smoke test 5 cabang PASS; Task 17 blokir `web_kasir` lama; Task 18 drop 5 tabel orphan — 4 tabel akuntansi (`tbakun`, `tbakun_pos`, `tblakunkas`, `tblkas_keluar_masuk`) sengaja dipertahankan.

### 4.10 Penanganan Komplain (`app/_komplain`)
- Alur: **Input** (CS; wajib pilih *No Service Asli* dengan nopol sama) → kategori menentukan PIC (`REWORK`→Kepala Mekanik; `HARGA/SIKAP/ANTRIAN/FASILITAS/LAINNYA`→Kepala Cabang) → **Antrian Rework** (KM usul Terima/Tolak) → **Antrian Approval** (Kepala Cabang: Setuju / Minta Revisi) → **Eskalasi Manajemen** → **Dashboard** & **Export**.
- Status: `Open, Diajukan, Dijadwalkan, Dikerjakan, Selesai, Ditutup - Ditolak, No-show, Eskalasi Manajemen`.
- **REWORK-to-Warranty (2026-09-17):** komplain REWORK yang disetujui otomatis membuat servis garansi (`createServisGaransi()` di `function_servis.php`, `is_garansi=1`, prioritas urgent); `tblkomplain.no_service_rework` menyimpan hasilnya.
- Tabel: `tblkomplain`, `tblkomplain_kategori`, `tblkomplain_log`, `tblkomplain_export_log`. Posisi `KACAB` dan permission `komplain_*` ditambahkan.
- **Blocked:** akun `KACAB` & `KM` untuk 4 cabang non-pusat belum dibuat (butuh data staf riil dari Rafi; jangan auto-generate akun/password).
- Gap desain sengaja di-skip: upload massal komplain, idempotent submit 60 dtk, threshold no-show 7 hari configurable.
- Komplain Garansi lama di modul servis **tidak disentuh** (terpisah, keputusan Rafi).

### 4.11 CRM, Member & Promo
- **Tier member** (`master_kategori_member`): berdasar nominal (Bronze 0–1.999.999 · Silver 2 jt–4.999.999 · Gold 5 jt–9.999.999 · Platinum ≥ 10 jt) **atau** kunjungan (0–4 · 5–9 · 10–19 · ≥ 20). Diskon jasa/barang: Silver 10%/7%, Gold 15%/11%, Platinum 20%/14%. Dihitung ulang oleh trigger saat servis berstatus `bayar` (`statistik_pelanggan`, `master_kedatangan_pelanggan`).
- Tier lokal (`statistik_pelanggan.status_member`) dan tier sinkron Access (`tblpelanggan.kgrup`) berjalan paralel; helper `fitmotorApplyMemberTierFloor()` menerapkan *floor* (hanya menaikkan).
- **Promo engine** (`master_diskon_periode` + `_cabang/_syarat/_target`): tipe persen/nominal, `stackable`, `boleh_gabung_diskon_member`, syarat AND/OR (kategori member, minimum total servis, jumlah kunjungan, paket WO), target jasa/barang/workorder; pemakaian tercatat di `promo_usage_log`; selesai & tervalidasi 2026-07-19.
- Statistik pelanggan: `statistik-pelanggan.php`, follow-up WA (`view_pelanggan_follow_up`), `view_top_pelanggan`.

### 4.12 HRD & Absensi
Tabel `tbpegawai` (44 kolom), `tbabsensi` (+ `_upload/_temp`), `tbwork_schedule*`, `tbwork_loc` (geofence lat/long, jarak masuk/pulang), tunjangan/PTKP, keluarga/pendidikan/training. Absensi ditandai **PWA** (aturan project). `master_jabatan`/`master_karyawan` sedang direstrukturisasi (`docs/PLANNING_RESTRUKTURISASI_KARYAWAN.md`). [TBC — cakupan HRD di menu aktif; folder `_hrd` lama sudah tidak ada di `app/`]

### 4.13 Integrasi & Konsolidasi Access
- Data dari Access cabang masuk lewat `api/sync/*` (`master-data`, `transactions`) atau upload manual → staging `stg_access_*` → merge ke tabel produksi (`tblpelanggan`, `tblkendaraan`, `tblitem_harga_cabang`, dst.) → konsolidasi `konsolidasi_*` dan agregat `gabung_*` (rekap konsumen, HPP acuan, statistik item mingguan, rekonsil antar cabang).
- Pencatatan eksekusi: `sync_access_runs` (859 run), `sync_access_row_errors`, `sync_gabung_log`. **Jangan commit** `sync_settings.env` (berisi token asli).
- Helper: `app/_include_access_sync.php` (± 127 KB), `_include_customer_vehicle_sync.php`.
- Akan dihentikan pada rencana **full cutover sekali jalan** (tanpa delta-sync rutin) — `docs/migration/RENCANA_MIGRASI_DATA_ACCESS.md`.

### 4.14 Accurate Online
`config/accurate_config.php`, `oauth-callback.php`, `login_accurate.php`; kolom `accurate_id` di `tblitemjenis/itemsatuan/supplier/pabrik_barang`; tabel `tokens`. Status pemakaian aktif [TBC].

---

## 5. Model Data (ringkas)

Detail lengkap: [ERD.md](./ERD.md) (diagram per domain, 294 tabel) dan [LRS.md](./LRS.md) (notasi LRS + daftar relasi).

| Domain | Jml tabel | Entitas inti |
|---|---|---|
| D01 Organisasi & Akses | 29 | `tbcabang`, `tbuser`, `tbuser_karyawan`, `tb_master_posisi`, `tb_master_level` |
| D02 Master Barang/Jasa/Paket | 37 | `tblitem` (50 kolom, 5.889 baris), `tbworkorderheader/detail`, `tbmaster_keluhan/temuan` |
| D03 Pelanggan/Kendaraan/CRM | 30 | `tblpelanggan` (37 kolom), `tblkendaraan`, `statistik_pelanggan` |
| D04 Servis | 40 | `tblservice` (81 kolom, PK komposit), `tblservis_jasa/barang`, `tb_antrian_servis`, `tbservis_temuan`, `servis_komisi` |
| D05 Promo | 5 | `master_diskon_periode*` |
| D06 Pembelian/Pengadaan | 26 | PR/RFQ/PO/DO/`tblpembelian_*`/`tblhutang_*` |
| D07 Penjualan | 14 | `tblpenjualan_*`, `tblorderjual_*`, `tblretur_penjualan_*`, `tblpiutang_*` |
| D08 Antar Cabang | 3 | `tblorder_antarcab_*` |
| D09 Stok | 15 | `tbstok` (318 rb baris), `tbitem_masuk/keluar_*`, `tbbrg_*`, `tbkoreksi_stok_*` |
| D10 Kasir & Setoran | 33 | `kasir_transactions_closing_kasir` (39 kolom), `setoran_*`, `pengambilan_setoran_*` |
| D11 Komplain & Issue | 7 | `tblkomplain*`, `tbl_issue*` |
| D12 HRD | 22 | `tbpegawai`, `tbabsensi` |
| D13 Integrasi Access | 33 | `stg_access_*`, `gabung_*`, `konsolidasi_*`, `sync_*` |

**Objek DB lain**
- **Trigger:** `trg_after_service_bayar` & `trg_update_statistik_after_payment` (keduanya AFTER UPDATE `tblservice`, menghitung statistik/tier saat status → `bayar`; **fungsi tumpang-tindih** — konsolidasi [TBC]), `trg_alarm_harga_beli` (AFTER INSERT `tblpembelian_detail`), `trg_update_rejection_summary` (AFTER UPDATE `tbservis_penawaran_part`).
- **Function:** `fn_hargajual_cabang`, `fn_hargapokok_cabang` (harga per cabang).
- **View (62):** kompatibilitas Access (`view_service`, `view_stok`, `view_penjualan_*`, `view_pembelian_*`, `view_cari_*`), pelaporan servis/keluhan/temuan, status PR/PO/DO (`v_pr_status`, `v_po_status`, `v_do_status`), kasir (`view_pemasukan_*`, `view_transaksi_*`), statistik pelanggan. Daftar lengkap di akhir [LRS.md](./LRS.md).

**Aturan integritas data yang penting**
1. `tblservice` PK `(no_service, kd_cabang)` — lihat §3.4. Nomor servis digenerate via `tbl_service_seq_counter` / `tblservice_no_counter`.
2. **Migration wajib reversible**; jalankan lewat runner PHP browser (`db/migrations/*_run_*.php`), **bukan** lewat `mysql` CLI (GUI phpMyAdmin juga tidak reliabel untuk DDL).
3. **HPP FIFO** adalah aturan project; implementasi belum lengkap (gap analysis 2026-08-09: `gabung_hpp_acuan` hanya mirror Access, `tblitem.hargapokok` masih satu harga) — [TBC status terkini].
4. Kolom tanggal legacy (`tblpembelian_header.tanggal`, dll.) bertipe `varchar(50)`, bukan `date`.

---

## 6. Kebutuhan Fungsional Utama

| ID | Kebutuhan | Status (dari kode / CLAUDE.md) |
|---|---|---|
| FR-SRV-01 | Cari nopol/pelanggan, tampilkan riwayat & badge member | Selesai (hijau) |
| FR-SRV-02 | Booking servis + keluhan + nomor antrian | Selesai (hijau) |
| FR-SRV-03 | Antrian & progres mekanik realtime | Uji coba (kuning) |
| FR-SRV-04 | Work order/temuan/penawaran part & jasa | Uji coba |
| FR-SRV-05 | Pembayaran multi-metode + nota + trigger tier | Uji coba (bug trigger fatal diperbaiki 2026-07-04) |
| FR-SRV-06 | Garansi (dinamis per member), klaim, rework | Uji coba |
| FR-SRV-07 | Cancel servis + kategori alasan + refund DP | Uji coba |
| FR-SRV-08 | DP servis & offset | Uji coba |
| FR-SRV-09 | Komisi mekanik/KM/admin tersimpan permanen | Live (`b7a242a`) |
| FR-PBL-01 | PR → RFQ → PO bertingkat → DO → Invoice + QC | Live (Fase 4) |
| FR-PBL-02 | Alarm harga beli & threshold | Live |
| FR-PJL-01 | POS + retur/refund + piutang | Uji coba |
| FR-ANC-01 | Antar cabang internal, mitra, pengadaan | Uji coba |
| FR-STK-01 | Kartu stok lintas transaksi termasuk servis | Live |
| FR-KSR-01 | Kas awal→closing→setoran→bank + audit | Live (smoke test 5 cabang PASS) |
| FR-KMP-01 | Komplain 3 tahap + rework→garansi | Live; blocked akun KACAB/KM 4 cabang |
| FR-PRM-01 | Promo engine multi-syarat/target/stackable | Live |
| FR-CRM-01 | Tier member otomatis, highlight, follow-up WA | Live |
| FR-SYN-01 | Sync/konsolidasi Access + monitor | Live (masa transisi) |
| FR-HRD-01 | Master pegawai, absensi geofence | [TBC] |

Status resmi tiap fitur dipantau di dashboard `checklist-projek` (`https://checklist.fitmotor.web.id/web-base`, hierarki 4-level Modul → Sub Modul → Halaman → Fitur; RAG hijau/kuning/merah) lewat `scripts/update-checklist.sh`.

---

## 7. Kebutuhan Non-Fungsional

| Area | Kondisi saat ini | Target |
|---|---|---|
| Keamanan – input | SQL injection disapu di 34/52 `save_*.php` dan 444 halaman self-processing (2026-08); sapuan 885 file untuk 3 pola bug (nol sistemik) | Semua query prepared/escaped; SAST rutin |
| Keamanan – kredensial | Fallback DB hardcode masih ada di file lama | Semua via env/`db_env.php`; nol default secret |
| Keamanan – password | Analisa lama melaporkan pencocokan plaintext di `cek_login.php`; `tbuser.password` varchar(255) [TBC sudah hash?] | `password_hash` |
| Keamanan – akses | Dua sistem RBAC berdampingan | Satu RBAC dinamis |
| Performa | N+1 di listing `setoran_keuangan.php` sudah diperbaiki (`ea3d7be`); 2 handler POST minor tersisa | Query terindeks; pagination wajib |
| Reliabilitas | Trigger tumpang-tindih; 13 tabel legacy tanpa PK | Konsolidasi trigger; tambah PK/unique key |
| Auditabilitas | `audit_log_closing_kasir`, `tb_user_activity_log`, `tbl_issue_progress_log`, `tblkomplain_log`, `tbservis_temuan_activity_log` | Log seragam lintas modul |
| Deployability | Shared hosting, deploy manual; dev = Laragon | (Proyek baru: Docker First) |
| Kompatibilitas | PHP 8.3, MySQL/MariaDB, collation campuran (pernah silent-fail JOIN) | Satu collation |

---

## 8. Integrasi Eksternal

| Sistem | Arah | Mekanisme | Catatan |
|---|---|---|---|
| Access (desktop lama, per cabang) | Masuk | VBS (`_sync_gabung_direct.vbs`) → `api/sync/*` → `stg_access_*` → merge | Dihentikan saat cutover; monitor `access-sync-monitor.php` |
| Accurate Online | Dua arah | OAuth + signature HMAC | [TBC pemakaian] |
| checklist-projek | Keluar | `scripts/update-checklist.sh` / webhook `POST /api/webhook/progress` (`X-API-Key`) | Kunci via env `API_KEY_WEBBENGKEL` |
| WhatsApp | Keluar | Link `wa.me` / `statistik_pelanggan_send_wa.php` | `no_wa` di `tblpelanggan` |
| Bank (bukti transfer/mutasi) | Masuk (upload manual) | Ekstraksi PDF/gambar + skor confidence | Modul setoran |

---

## 9. Utang Teknis & Risiko

| # | Temuan | Dampak | Saran |
|---|---|---|---|
| 1 | Hanya 66 dari ± 370 relasi yang menjadi FOREIGN KEY; 13 tabel tanpa PK | Orphan record; integritas hanya dijaga kode | Tambah PK/unique/FK bertahap (mulai `tblservice`, header/detail) |
| 2 | `no_service` tidak unik lintas cabang | JOIN salah tanpa `kd_cabang` | Pertahankan aturan JOIN komposit; 3 file risiko rendah tersisa |
| 3 | Kredensial fallback hardcode | Kebocoran (repo diakses PKL) | Hapus fallback |
| 4 | Dua lapisan RBAC | Celah akses | Migrasi total ke RBAC dinamis |
| 5 | Dua trigger tier tumpang-tindih | Beban update ganda; pernah fatal | Sisakan satu |
| 6 | Tanggal `varchar` di tabel legacy | Sort/filter salah, tanpa index | Migrasi ke `date` |
| 7 | 61 halaman fungsional tidak terhubung ke menu (audit 2026-09-15) + dead-code | Kebingungan, permukaan serangan | Selesaikan audit menu_config (5/8 batch selesai; lanjut Batch 6 Stok+Laporan) |
| 8 | Task 34: retire `masterkey.php` terblokir Task 21 (migrasi SSO `priori-tech` → `tbuser`) | Bypass auth lama | Koordinasi eksternal |
| 9 | Master ganda (`tbtipemotor` vs `tbtipe_motor`, `tbmaster_kategori_member` vs `master_kategori_member`, `tblnamabarang` vs `tbmaster_nama_barang`) | Duplikasi/ketidakkonsistenan | Konsolidasi |
| 10 | Konvensi penamaan campur (`tbl*`, `tb_*`, `tb*`, `*_closing_kasir`) | Sulit dipelihara | Dokumentasikan; jangan menambah gaya baru |

---

## 10. Backlog / Pending (sumber: `CLAUDE.md` § Status Ringkas)

1. **Akun KACAB & KM** di PACUL, PESALAKAN, TRAYEMAN, CIKDITIRO — memblokir approval komplain (butuh nama staf & kode karyawan dari Rafi).
2. **Task 34** retire `masterkey.php` (blocked Task 21).
3. N+1 minor di 2 handler POST `setoran_keuangan.php`.
4. 3 gap desain Modul Komplain (uploader massal, idempotent submit, threshold no-show).
5. Kredensial DB fallback hardcode codebase-wide.
6. Audit `menu_config` Batch 6–8.
7. Fitur baru dalam spesifikasi: **Checklist Pengecekan Mekanik** (`docs/superpowers/specs/2026-09-19-checklist-pengecekan-mekanik-design.md`, belum di-commit).
8. Gap analysis Access vs Web (2026-08-09): HPP FIFO, Insentif/Bagi Hasil (`tbbagi_hasil`, `tbpersen_insentif`, `tbinsentif_jual_servis` masih mirror), Laba Rugi belum ada, data `gabung_*` belum dipakai UI.
9. Pindah Kepemilikan Kendaraan: menu dinonaktifkan sampai migrasi Access selesai.

---

## 11. Definition of Done (aturan proyek)

Alur kerja 5 langkah: **kerjakan → test E2E → commit → update `.claude/memory.md` → update checklist-projek**. DoD global: test lulus (termasuk E2E browser), tidak ada nilai hardcode, README/`.ai/changelog` diperbarui, review Sonnet cepat + Opus sebagai *judge* sebelum merge, commit di-push. Validasi fix lewat query DB langsung, bukan hanya tampilan UI.

---

## 12. Lampiran

- Skema lengkap: [ERD.md](./ERD.md), [LRS.md](./LRS.md).
- Dokumen sumber: `docs/fsd/FSD_*.md` (Servis, CRM, Customer, Kendaraan, Membership, Pengadaan/Inventory, Promo), `docs/analysis/*`, `docs/migration/RENCANA_MIGRASI_DATA_ACCESS.md`, `docs/Modul_Penanganan_Komplain_Fit_Motor.md`, `docs/PANDUAN_ALUR_PUSAT_CABANG.md`, `docs/DOKUMENTASI_ALUR_PROCUREMENT.md`.
- Cara analisis: skema diambil langsung dari `information_schema` DB live (bukan dump lama); halaman/menu dari `app/menu_config.php`; aturan bisnis dari `CLAUDE.md` root, FSD, dan migrasi `db/migrations/`. Analisis **statis** — tidak membuka tiap halaman satu per satu; fitur berlabel [TBC] perlu verifikasi manual.
