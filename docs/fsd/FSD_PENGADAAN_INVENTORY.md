# Functional Specification Document — Modul Pengadaan & Inventory

**Versi:** 1.1
**Tanggal:** 2026-07-16 (update dari 2026-07-04)
**Status:** Aktif — keputusan Owner #1, #2 sudah masuk (2026-07-16)
**Referensi:** `docs/audit/ANALISIS_FITMOTOR_APP_PROSES_BISNIS.md` (alarm harga beli), skema `tools/sql/fitmotor_dbbengkel_FIXED_V7.sql`, commit `6f52f9a` (fix alur PR/PO/DO + kalkulasi min-max stok), commit S303 (fix accept button DO antar cabang + error 500)

**Decision yang mengikat dokumen ini** (final, tidak dibuka ulang):
- Pengadaan (PR→PO→Pembelian→DO) dan Inventory (stok, kartu stok, min-max, koreksi stok) digabung 1 dokumen karena alur-nya satu rangkaian ujung ke ujung — bukan 2 modul independen.
- Alarm perubahan harga beli (dikeluarkan dari `FSD_SERVIS.md`) resmi masuk scope dokumen ini.
- RFQ Supplier Response masuk scope sebagai **desain fitur baru** — tabelnya (`tblrfq_supplier_response`) sudah ada di skema tapi 0 referensi di kode aplikasi, jadi ini bukan dokumentasi existing melainkan rancangan dari nol.

---

## 1. Ringkasan & Tujuan

Modul ini mendefinisikan siklus hidup pengadaan barang — dari permintaan internal (PR) sampai barang diterima di cabang (Pembelian/DO) — serta pengelolaan stok yang menyertainya (kartu stok, min-max, koreksi manual).

**Masalah yang diselesaikan** (bukti dari kerja sebelumnya, bukan hipotetis):
- PO list sempat gagal tampil karena filter WHERE terlalu ketat terhadap `tipe_trx` (fixed, expanded ke NULL/kosong) — perlu didokumentasikan resmi biar tidak terulang.
- Tombol "Terima" hilang di list pengadaan antar-cabang untuk cabang penerima, dan error HTTP 500 saat lihat detail order — sudah diperbaiki 30 Jun, perlu jadi baseline resmi state machine DO.
- SQL injection ditemukan di beberapa titik alur PO (header save, print page, save handler) — masih tercatat sebagai kerentanan terbuka per observasi 2475/2476/2478, perlu ditandai ulang di sini sebagai item keamanan yang harus dipastikan sudah tertutup sebelum modul ini dianggap stabil.
- FITMOTOR APP.mdb (Access lama) punya fitur alarm otomatis "harga beli naik/turun" yang mengklasifikasi barang ke beberapa status review — fitur ini **tidak ada** padanannya di web base sama sekali.
- Tidak ada alur RFQ (minta penawaran ke beberapa supplier sebelum PO) yang benar-benar berjalan — tabelnya ada tapi kosong, PO (`tblorder_header`) sudah punya kolom `no_rfq` yang menunjukkan pernah direncanakan tapi belum dibangun.

## 2. Ruang Lingkup

**In scope:**
- Purchase Request (PR): `tblpurchase_request_header/detail` — permintaan internal sampai approve/reject.
- Purchase Order (PO): `tblorder_header` (nama tabel historis, bukan `tblpo_*`) — dari PR yang di-approve, opsional lewat RFQ dulu.
- Pembelian (goods receipt): `tblpembelian_header/detail` — barang fisik diterima dari supplier, update stok.
- Retur Pembelian: `tblretur_pembelian_header/detail` — barang salah/rusak dikembalikan ke supplier.
- Delivery Order (DO) Antar Cabang: `tbldelivery_order_header/detail` — transfer stok antar cabang.
- Kartu Stok: `tbstok` — histori keluar-masuk semua tipe transaksi termasuk servis (`tipe='4'`, lihat `FSD_SERVIS.md` section 11).
- Min-Max Stok & Reorder: `tblitem_stok` (`stokmin`, `stok_maks` per cabang).
- Koreksi Stok: `tbkoreksi_stok_header/detail` — penyesuaian manual (stok opname, dsb).
- Alarm Harga Beli (fitur baru, dari temuan FITMOTOR APP.mdb).
- RFQ Supplier Response (fitur baru, desain dari nol).

**Out of scope** (dibahas di FSD terpisah):
- Kalkulasi HPP servis (FIFO + ACUAN_PKK) → sudah di `FSD_SERVIS.md` section 7 (item yang sama dipakai, tapi kalkulasi HPP dibahas di sana).
- Master data item/barang (kategori, satuan, harga jual) → belum ada FSD terpisah, dianggap given.
- Mapping applicable part per tipe motor (`tblitem_spart`) → dibahas terpisah kalau/ketika brainstorming mapping WO-kategori-part dilanjutkan.
- Akun kas & pembayaran ke supplier (`tblakunkas`, jurnal) → `FSD_KEUANGAN.md` (belum ditulis).

## 3. Aktor & Role

| Aktor | Hak Akses di Modul Ini |
|---|---|
| Staf Cabang (requester) | Buat PR, lihat status PR miliknya. |
| Kepala Cabang / Supervisor | Approve/reject PR level cabang, terima DO antar-cabang masuk. |
| Staf Pengadaan Pusat | Buat PO dari PR yang approved, kirim RFQ ke supplier (kalau dipakai), buat DO antar-cabang. |
| Gudang / Admin Stok | Input Pembelian (goods receipt), input Retur Pembelian, input Koreksi Stok, konfirmasi terima DO. |
| Supervisor (Spv) | Approve PO dalam rentang batas nilai level Spv (lihat section 7.1). |
| Manager | Approve PO dalam rentang batas nilai level Manager (lihat section 7.1). |
| Owner / Admin Pusat | Approve PO di atas batas Manager, lihat laporan alarm harga beli lintas cabang. |
| Supplier (eksternal, tidak akses sistem) | Kirim response RFQ lewat kanal luar sistem (WA/email) — staf pengadaan yang input manual ke `tblrfq_supplier_response`. |
| Sistem (background job) | Cek `stokmin`/`stok_maks` harian untuk reorder alert, klasifikasi alarm harga beli tiap ada Pembelian baru. |

## 4. Glosarium

| Istilah | Arti |
|---|---|
| PR (Purchase Request) | Permintaan barang dari cabang, `status_pr`: draft/submitted/approved/rejected/closed. |
| PO (Purchase Order) | Pesanan resmi ke supplier, tabel `tblorder_header`, `status_approval`: draft/pending/approved/rejected. |
| Pembelian | Transaksi penerimaan fisik barang dari supplier (goods receipt), `tblpembelian_header`. |
| DO (Delivery Order) | Dokumen pengiriman — dipakai untuk transfer antar cabang, `status_do`: draft/confirmed/in_transit/arrived/received/cancelled. |
| RFQ (Request for Quotation) | Permintaan penawaran harga ke beberapa supplier sebelum PO dibuat. |
| Kartu Stok | Histori keluar-masuk barang per item, `tbstok`, dibedakan per `tipe` transaksi. |
| Reorder Point | Titik `stokmin` di `tblitem_stok` — kalau stok aktual di bawah ini, perlu PR baru. |
| Koreksi Stok | Penyesuaian manual stok sistem vs fisik, di luar alur pembelian/penjualan/servis normal. |
| Alarm Harga Beli | Notifikasi otomatis saat harga beli baru berbeda dari sebelumnya, klasifikasi status review harga jual. |

## 5. Model Data

### 5.1 Tabel Existing (tidak diubah strukturnya)

- **`tblpurchase_request_header`** — `no_pr`, `status_pr` (draft/submitted/approved/rejected/closed), `requester`, `departemen`, `kd_cabang`, `approved_by`/`rejected_by`.
- **`tblpurchase_request_detail`** — `no_pr`, `no_item`, `quantity`, `qty_approved`, `qty_po` (qty yang sudah di-PO — dipakai untuk cegah PO dobel dari PR sama), `status_item` (pending/approved/rejected/po_created).
- **`tblorder_header`** (= PO) — `no_order`, `no_pr` (link ke PR), `no_rfq` (link ke RFQ, kolom sudah ada meski RFQ belum dibangun), `status_approval` (draft/pending/approved/rejected), `po_type` (regular/urgent/consignment), `payment_term`.
- **`tblpembelian_header/detail`** — goods receipt, `status_lunas`, `total_retur`, per baris `qty_order` vs `quantity` (aktual diterima) vs `qty_retur`.
- **`tblretur_pembelian_header/detail`** — retur ke supplier, `status_retur`.
- **`tbldelivery_order_header`** — `no_do`, `no_po`, `status_do` (draft/confirmed/in_transit/arrived/received/cancelled), `tanggal_estimasi_tiba`/`tanggal_tiba`.
- **`tbldelivery_order_detail`** — per baris `qty_po`/`qty_kirim`/`qty_terima`/`qty_reject` (selisih kirim vs terima terlacak eksplisit).
- **`tblitem_stok`** — `noitem`, `kode_cabang`, `stokmin`, `stok_maks`, `rakbarang` — per cabang, bukan global.
- **`tbstok`** — kartu stok histori, `tipe` membedakan sumber transaksi (pembelian, servis `'4'`, penjualan, dll — lihat `FSD_SERVIS.md` section 11 untuk detail tipe servis).
- **`tbkoreksi_stok_header/detail`** — `tbkoreksi_stok_detail` punya `stok_sistem`, `penyesuaian`, `tipe_stok` — catat nilai sebelum-sesudah koreksi.
- **`tblrfq_supplier_response`** — `no_rfq`, `no_supplier`, `no_item`, `harga_penawaran`, `lead_time_days`, `status_response` (pending/submitted/selected/rejected). **Ada di skema, 0 pemakaian di kode** — jadi basis desain section 12, bukan dokumentasi existing.

### 5.2 Tabel Baru (diusulkan)

**`tb_master_threshold_harga`** — setting threshold alarm harga beli, editable lewat halaman master (Keputusan Owner 2026-07-16).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `arah` | ENUM('naik','turun') | Threshold untuk harga naik atau turun |
| `persen_threshold` | DOUBLE | Persentase selisih yang dianggap signifikan |
| `aktif` | TINYINT(1) DEFAULT 1 | |
| `updated_by` | VARCHAR(50) | |
| `updated_at` | TIMESTAMP | |

**Contoh data awal:**

| arah | persen_threshold |
|---|---|
| naik | 5.0 |
| turun | 10.0 |

**Catatan:** Owner memutuskan threshold naik dan turun **dibedakan** (2026-07-16). Angka 5% dan 10% adalah rekomendasi awal — bisa diubah kapan saja lewat halaman setting. Kedua threshold ditampilkan dalam **1 halaman** supaya langsung terbaca keduanya.

**`tb_master_approval_pembelian`** — setting batas nilai approval PO bertingkat (Keputusan Owner 2026-07-16, lihat section 7.1).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `level_approval` | VARCHAR(50) | Nama level: Spv, Manager, Owner, dll — fleksibel |
| `batas_bawah` | DOUBLE | Batas bawah nominal PO (inklusif) |
| `batas_atas` | DOUBLE NULL | Batas atas nominal PO (inklusif). NULL = tidak terbatas |
| `urutan` | INT | Urutan level dari rendah ke tinggi |
| `aktif` | TINYINT(1) DEFAULT 1 | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**`alarm_harga_beli`** — catatan alarm tiap ada perubahan harga beli signifikan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `no_item` | VARCHAR(50) FK | |
| `no_transaksi_pembelian` | VARCHAR(50) FK → tblpembelian_header | Transaksi yang memicu alarm |
| `harga_beli_lama` | DOUBLE | |
| `harga_beli_baru` | DOUBLE | |
| `persen_selisih` | DOUBLE | Persentase selisih aktual (positif = naik, negatif = turun) |
| `arah` | ENUM('naik','turun') | Arah perubahan harga |
| `threshold_saat_itu` | DOUBLE | Snapshot threshold yang berlaku saat alarm dibuat |
| `harga_jual_saat_ini` | DOUBLE | Snapshot harga jual saat alarm dibuat |
| `status_klasifikasi` | VARCHAR(50) | Lihat section 11 — label pasti masih perlu konfirmasi ulang ke query Access asli |
| `status_review` | ENUM('belum_direview','direview','harga_disesuaikan','diabaikan') DEFAULT 'belum_direview' | |
| `direview_oleh` | VARCHAR(50) NULL | |
| `created_at` | TIMESTAMP | |


**`rfq_header`** — header permintaan penawaran (RFQ belum punya header table di skema existing, cuma tabel response).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `no_rfq` | VARCHAR(50) PK | |
| `no_pr` | VARCHAR(50) FK NULL | RFQ bisa lahir dari PR atau dibuat manual |
| `tanggal_rfq` | DATE | |
| `batas_waktu_response` | DATE | Deadline supplier submit harga |
| `status_rfq` | ENUM('draft','dikirim','ditutup','dibatalkan') DEFAULT 'draft' | |
| `kd_cabang` | VARCHAR(10) | |
| `created_by` | VARCHAR(50) | |
| `created_at` | TIMESTAMP | |

**`rfq_detail`** — item yang diminta penawaran (RFQ bisa multi-item, `tblrfq_supplier_response` sekarang langsung per-item per-supplier tanpa header list item yang diminta).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `no_rfq` | VARCHAR(50) FK | |
| `no_item` | VARCHAR(50) FK | |
| `quantity` | INT | |

## 6. State Machine — Purchase Request (PR)

```
draft → submitted → approved → (PO dibuat) → closed
              \
               → rejected
```

- **draft**: staf cabang masih menyusun daftar item.
- **submitted**: dikirim ke Kepala Cabang/Supervisor untuk approval.
- **approved**: disetujui — `qty_approved` diisi (bisa lebih kecil dari `quantity` yang diminta), status per baris (`status_item`) jadi `approved`.
- **rejected**: ditolak dengan `reject_reason` wajib diisi.
- **closed**: seluruh baris sudah `po_created` (qty_po = qty_approved di semua baris) — PR selesai tugasnya, tidak bisa dipakai bikin PO lagi.

**Catatan implementasi:** `qty_po` per baris (bukan cuma status header) memastikan PR bisa di-split jadi beberapa PO parsial tanpa kehilangan tracking sisa qty yang belum di-PO-kan.

## 7. State Machine — Purchase Order (PO)

```
draft → pending → approved → (kirim ke supplier, jadi Pembelian saat barang tiba)
              \
               → rejected
```

- **draft**: staf pengadaan susun PO, opsional isi `no_rfq` kalau lahir dari proses RFQ (pilih supplier dengan harga/lead time terbaik).
- **pending**: menunggu approval — level approver ditentukan oleh batas nilai di master (lihat section 7.1).
- **approved**: PO resmi, dikirim ke supplier di luar sistem (WA/email/telepon — tidak ada kanal terintegrasi).
- **rejected**: PO dibatalkan sebelum dikirim, `reject_reason` wajib.

**Gap dari observasi sebelumnya (harus dipastikan tertutup):** SQL injection ditemukan di PO header save, PO print page, dan PO save handler (observasi 2475/2476/2478, 30 Jun). FSD ini **mensyaratkan** semua query di alur PO pakai prepared statement sebelum modul ini dianggap stabil — bukan rekomendasi opsional.


### 7.1 Approval Bertingkat Berdasar Batas Nilai (Keputusan Owner 2026-07-16)

**Keputusan:** Batas nilai rupiah untuk approval PO **tidak hardcode** — disimpan di tabel master sehingga user bisa mengatur sendiri rentang batas per level approval.

**Tabel baru: `tb_master_approval_pembelian`**

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `level_approval` | VARCHAR(50) | Nama level: Spv, Manager, Owner, dll — fleksibel |
| `batas_bawah` | DOUBLE | Batas bawah nominal PO (inklusif) |
| `batas_atas` | DOUBLE NULL | Batas atas nominal PO (inklusif). NULL = tidak terbatas |
| `urutan` | INT | Urutan level dari rendah ke tinggi |
| `aktif` | TINYINT(1) DEFAULT 1 | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Contoh data awal (dari arahan Owner):**

| level_approval | batas_bawah | batas_atas | urutan |
|---|---|---|---|
| Supervisor | 300000 | 1000000 | 1 |
| Manager | 1000001 | NULL | 2 |

**Catatan:** Owner menyebut contoh "300k sampai 1 juta = Spv, di atas 1 juta = Manager". Angka ini **hanya contoh awal** — bisa diubah kapan saja lewat halaman master. Jika di kemudian hari Owner mau menambah level (misal: di atas 10 juta perlu approval Owner sendiri), tinggal tambah baris baru di tabel ini.

**Logic approval:**
1. Saat PO masuk status `pending`, sistem cek total nilai PO.
2. Lookup `tb_master_approval_pembelian` cari baris yang `batas_bawah <= total AND (batas_atas >= total OR batas_atas IS NULL)` dan `aktif=1`.
3. PO hanya bisa di-approve oleh user yang punya role >= `level_approval` yang cocok.
4. PO di bawah `batas_bawah` terendah (< 300k dalam contoh) tidak perlu approval bertingkat — langsung bisa di-approve oleh Staf Pengadaan.

## 8. State Machine — Pembelian (Goods Receipt)

```
(PO approved) → Pembelian dibuat → status_lunas='0' → status_lunas='1' (setelah bayar)
```

- Barang diterima dicatat per baris: `qty_order` (dari PO) vs `quantity` (aktual diterima) vs `qty_retur` (dikembalikan setelah cek kualitas).
- Selisih `qty_order` vs `quantity` **tidak otomatis** memicu apa pun di skema saat ini (tidak ada kolom "status_diskrepansi") — kalau supplier kirim kurang/lebih, staf gudang harus manual follow-up. Dicatat sebagai gap, bukan rencana fix wajib.
- `status_lunas` terpisah dari status penerimaan barang — barang bisa sudah diterima penuh tapi belum lunas dibayar (utang ke supplier, lihat `FSD_KEUANGAN.md` untuk jurnal-nya).

## 9. State Machine — Delivery Order (DO) Antar Cabang

```
draft → confirmed → in_transit → arrived → received
                                      \
                                       → cancelled (dari state manapun sebelum received)
```

- **draft**: cabang pengirim susun DO (barang apa, qty berapa, dari stok cabang mana).
- **confirmed**: DO difinalisasi, siap dikirim.
- **in_transit**: barang dalam perjalanan (`nama_pengirim`, `telp_pengirim`, `no_kendaraan` dicatat).
- **arrived**: barang sampai di cabang tujuan, belum dikonfirmasi diterima.
- **received**: cabang tujuan konfirmasi terima — ini yang memicu update stok cabang tujuan (+) dan cabang asal (-, sudah dikurangi lebih awal saat `confirmed` atau baru di titik ini — **perlu verifikasi ke kode**, tidak diasumsikan di FSD ini).
- **cancelled**: dibatalkan sebelum diterima.

**Baseline resmi dari fix 30 Jun 2026:** tombol "Terima" untuk cabang penerima dan halaman detail order (sempat error 500) sudah diperbaiki — state `arrived`→`received` ini yang jadi acuan resmi ke depan.

Per baris DO (`qty_po`/`qty_kirim`/`qty_terima`/`qty_reject`) memungkinkan pencatatan selisih kirim vs terima secara eksplisit (barang reject di tujuan) — ini sudah lebih baik dari Pembelian (section 8) yang tidak punya kolom serupa untuk PO-ke-supplier.

## 10. Min-Max Stok & Reorder

- `tblitem_stok.stokmin`/`stok_maks` diset **per cabang** (`kode_cabang`), bukan global — cabang beda bisa punya reorder point beda untuk item yang sama.
- Kalkulasi min-max sudah diperbaiki di commit `6f52f9a` (fix perhitungan) — FSD ini mendokumentasikan sebagai baseline: reorder alert menyala kalau stok aktual (`tbstok` running total) turun di bawah `stokmin`.
- **Belum ada** proses otomatis "stok di bawah min → auto-generate draft PR" — saat ini reorder masih manual (staf cek laporan, buat PR sendiri). Ini kandidat improvement, bukan gap kritis.
- `rakbarang` (lokasi rak) ada di tabel yang sama — di luar scope dokumen ini (masuk manajemen gudang fisik, bukan alur pengadaan).

## 11. Alarm Harga Beli

**Sumber (`CEK_PEMBELIAN_NAIK`/`CEK_PEMBELIAN_TURUN` di FITMOTOR APP.mdb):** setiap ada Pembelian baru dengan harga beli beda dari transaksi sebelumnya untuk item yang sama, sistem otomatis mengklasifikasi ke salah satu dari 7 status review (contoh yang sudah dikonfirmasi: "Harga Beli Naik & Harga Jual perlu Naik", "Harga Pokok Turun" — **5 status lainnya belum terkonfirmasi literal, butuh re-ekstraksi query Access asli sebelum implementasi**, lihat section 13 #3).

### 11.1 Keputusan Threshold (Owner 2026-07-16)

Owner memutuskan:
- Threshold harga **NAIK** dan **TURUN dibedakan** (bukan satu angka untuk dua arah).
- Kedua threshold ditampilkan dalam **1 halaman setting** supaya langsung terbaca keduanya.
- Nilai threshold disimpan di `tb_master_threshold_harga` (section 5.2) dan bisa diubah kapan saja tanpa deploy ulang.
- Rekomendasi awal: naik 5%, turun 10% — angka final diisi user lewat halaman master.

### 11.2 Alur Alarm

1. Setiap `tblpembelian_detail` baru diinsert, sistem bandingkan `harga_pokok` dengan transaksi Pembelian terakhir untuk `no_item` yang sama.
2. Hitung `persen_selisih = ((harga_baru - harga_lama) / harga_lama) * 100`.
3. Lookup `tb_master_threshold_harga`:
   - Jika `persen_selisih > 0` (naik) dan `persen_selisih >= threshold_naik` → insert alarm dengan `arah='naik'`.
   - Jika `persen_selisih < 0` (turun) dan `ABS(persen_selisih) >= threshold_turun` → insert alarm dengan `arah='turun'`.
4. Insert ke `alarm_harga_beli` dengan `persen_selisih`, `arah`, `threshold_saat_itu` (snapshot threshold yang berlaku saat itu), `status_klasifikasi`, dan `status_review='belum_direview'`.
5. Dashboard/laporan menampilkan alarm `belum_direview` ke staf pengadaan/owner — **dalam 1 halaman**, kolom naik dan turun langsung terbaca berdampingan.
6. Staf review tiap alarm: sesuaikan harga jual (`harga_disesuaikan`), atau `diabaikan` dengan alasan.
7. Tidak ada aksi otomatis ke harga jual — sistem cuma **memberi tahu**, keputusan tetap manual (konsisten dengan sifat aslinya di Access sebagai alat bantu review, bukan auto-pricing).

### 11.3 UI Halaman Setting Threshold

- 1 halaman, 2 field input: "Threshold Harga Naik (%)" dan "Threshold Harga Turun (%)".
- Tombol simpan update `tb_master_threshold_harga`.
- Validasi: nilai harus > 0.
- Perubahan threshold **tidak retroaktif** — alarm lama tetap pakai threshold yang berlaku saat dibuat (tersimpan di `alarm_harga_beli.threshold_saat_itu`).

## 12. RFQ Supplier Response (Fitur Baru, Desain dari Nol)

**Kenapa didesain dari nol:** tabel `tblrfq_supplier_response` sudah ada di skema (kemungkinan disiapkan untuk rencana sebelumnya) tapi 0 referensi di kode aplikasi — tidak ada form input, tidak ada proses baca. PO (`tblorder_header.no_rfq`) sudah siap menerima link ke RFQ, jadi struktur data separuh jalan sudah ada, tinggal alur dan UI-nya.

**Alur yang diusulkan:**
1. Staf pengadaan buat `rfq_header` (baru, section 5.2) dari PR yang approved — pilih item mana yang mau ditawar ke beberapa supplier, isi `rfq_detail`.
2. RFQ dikirim ke supplier di luar sistem (WA/email — tidak ada portal supplier).
3. Staf pengadaan input manual tiap response supplier ke `tblrfq_supplier_response` (`harga_penawaran`, `lead_time_days`).
4. Staf pengadaan bandingkan semua response per item, pilih satu (`status_response='selected'`), sisanya `rejected`.
5. PO dibuat dari RFQ yang dipilih — `tblorder_header.no_rfq` diisi, harga di PO detail mengikuti `harga_penawaran` yang dipilih.

**Batasan disengaja (YAGNI):** tidak ada portal/akses supplier ke sistem — semua interaksi supplier tetap di luar sistem, cuma hasil akhirnya yang diinput manual. Kalau nanti butuh portal supplier, itu proyek terpisah jauh lebih besar (autentikasi eksternal, dll), bukan bagian dokumen ini.

## 13. Open Questions / Decision untuk Owner

| # | Pertanyaan | Status | Catatan |
|---|---|---|---|
| 1 | Threshold nominal PO yang butuh approval — berapa rupiah, siapa approve? | ✅ **Terjawab 2026-07-16** | Approval bertingkat berdasar master. Contoh: 300k–1jt = Spv, >1jt = Manager. Angka editable lewat `tb_master_approval_pembelian`. Lihat section 7.1. |
| 2 | Threshold alarm harga beli — berapa persen selisih yang dianggap signifikan? | ✅ **Terjawab 2026-07-16** | Threshold naik dan turun **dibedakan**. Disimpan di `tb_master_threshold_harga`, editable. Rekomendasi awal: naik 5%, turun 10%. Lihat section 11.1. |
| 3 | Perlu konfirmasi/re-ekstraksi 5 status klasifikasi alarm harga beli yang belum terverifikasi literal dari Access. | ❓ Belum | Section 11 baru punya 2 dari 7 label pasti — implementasi penuh butuh semua label. |
| 4 | RFQ: wajib untuk semua PO, atau opsional? | ❓ Belum | Menentukan apakah RFQ jadi gerbang wajib atau jalur tambahan. |
| 5 | Selisih qty Pembelian vs PO — perlu status formal "diskrepansi" atau tetap manual? | ❓ Belum | Section 8 catat ini sebagai gap terbuka. |
| 6 | Reorder otomatis (stok di bawah min langsung generate draft PR) — prioritas sekarang atau nanti? | ❓ Belum | Section 10 — saat ini manual. |

---

## Urutan Pengerjaan Selanjutnya (usulan)

1. ~~Owner jawab section 13 #1-#3~~ → #1 dan #2 sudah terjawab (2026-07-16). #3 masih perlu re-ekstraksi label dari Access.
2. Verifikasi keamanan: pastikan 3 temuan SQL injection alur PO (observasi 2475/2476/2478) sudah tertutup.
3. **Implementasi Alarm Harga Beli** — threshold sudah final (section 11.1), tinggal konfirmasi 5 label klasifikasi (#3) sebelum implementasi penuh. Bisa mulai dengan 2 label yang sudah pasti dulu.
4. **Implementasi Approval Bertingkat PO** — desain sudah final (section 7.1), bisa langsung dikerjakan. Buat halaman master + logic approval di alur PO.
5. **Implementasi halaman setting threshold harga** — 1 halaman, 2 field input (section 11.3).
6. Implementasi RFQ (tergantung jawaban #4) — fitur baru penuh, effort lebih besar.
7. Lanjut FSD modul berikutnya: Keuangan (akun kas, jurnal, utang-piutang supplier).
