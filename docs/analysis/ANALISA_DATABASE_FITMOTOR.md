# ANALISA DATABASE FITMOTOR_DBBENGKEL

## Executive Summary

Database fitmotor_dbbengkel adalah sistem database untuk aplikasi bengkel motor dengan **242 tabel** yang mencakup manajemen servis, inventori, penjualan, pembelian, SDM, dan keuangan.

### File yang Dianalisa
- **fitmotor_dbbengkel.sql** - 33,614 baris, 3.9MB
- **fitmotor_dbbengkel (1).sql** - 33,022 baris, 3.9MB
- Kedua file **IDENTIK** (binary comparison sama)

---

## 📊 Statistik Database

### Objek Database
| Jenis Objek | Jumlah |
|------------|--------|
| **Tables** | 242 |
| **Triggers** | 2 |
| **Stored Procedures** | 0 |
| **Functions** | 0 |
| **Foreign Keys** | 30 |
| **Indexes** | 198 |

### Kategorisasi Tabel (198 tabel utama)

| Kategori | Jumlah | Keterangan |
|----------|--------|------------|
| **Master Data** | 25 | Data referensi (kategori, posisi, tarif, dll) |
| **Transaction - Service** | 19 | Transaksi servis kendaraan |
| **Transaction - Sales/Purchase** | 12 | Penjualan & pembelian |
| **Inventory/Items** | 28 | Manajemen barang/sparepart/stok |
| **Customer/Vehicle** | 9 | Data pelanggan & kendaraan |
| **HR/Employee** | 28 | Karyawan, mekanik, user, absensi |
| **Finance** | 15 | Hutang, piutang, kas, akuntansi |
| **View Tables** | 44 | Tabel view_ untuk reporting |
| **Logs/Tracking** | 20 | Log aktivitas & backup |
| **Configuration** | 6 | Pengaturan sistem |
| **Lainnya** | 64 | Tabel support lainnya |

---

## 🔍 Analisa View Tables (44 tabel)

**TEMUAN PENTING**: Terdapat **44 tabel dengan prefix `view_`** yang merupakan **TABLE kosong**, bukan VIEW database.

### Kategorisasi View Tables:

1. **Master Data Views (6)**
   - view_cari_item
   - view_item_classified
   - view_master_kategori_member
   - view_master_keluhan
   - view_master_kepala_mekanik_aktif
   - view_stok_master

2. **Transaction Views (19)**
   - view_service, view_workorder_complete
   - view_pembelian_detail, view_pembelian_header
   - view_penjualan_detail, view_penjualan_header
   - view_servis_keluhan_lengkap, view_servis_temuan_lengkap
   - view_po_with_pr, view_pr_complete
   - Dan 10 view transaksi lainnya

3. **Reporting/Summary Views (5)**
   - view_pembayaran_hutang, view_pembayaran_piutang
   - view_statistik_pelanggan, view_top_pelanggan
   - view_ringkasan_kedatangan_pelanggan

4. **Search/Lookup Views (3)**
   - view_cari_kendaraan, view_cari_pelanggan
   - view_suggested_parts

5. **Status/Tracking Views (1)**
   - view_pelanggan_follow_up

6. **Other Views (10)**
   - view_absensi, view_mekanik_users, view_user_details
   - view_pelanggan_kendaraan, view_tarif_jemput, dll

### Status View Tables:
- ✅ Semua 44 view_ tables **TIDAK memiliki INSERT statements** (kosong)
- ⚠️ Semua adalah **TABLE**, bukan **VIEW**
- 💡 Kemungkinan ini hasil export yang tidak benar atau perlu dikonversi ke VIEW

---

## ⚠️ MASALAH KRITIS YANG DITEMUKAN

### 1. **PRIMARY KEY HILANG** 🚨 CRITICAL
- **241 dari 242 tabel TIDAK memiliki PRIMARY KEY**
- Hanya 1 tabel yang memiliki PK: `_backup_tbl_master_kepala_mekanik_20251115`
- **Dampak**:
  - Performa query sangat lambat
  - Tidak bisa melakukan update/delete yang efisien
  - Risiko data duplikat tinggi
  - JOIN operation tidak optimal
  - Masalah replikasi database

**Rekomendasi**: Segera tambahkan PRIMARY KEY ke semua tabel!

### 2. **Foreign Key Sangat Sedikit** ⚠️ HIGH
- Hanya **30 Foreign Keys** untuk 242 tabel (12.4%)
- **Dampak**:
  - Integritas referensial lemah
  - Data orphan/yatim piatu tidak terkontrol
  - Kesulitan maintain konsistensi data
  - Risiko data corruption tinggi

**Tabel yang paling banyak direferensi**:
1. tblpurchase_request_header (3 FK)
2. tbuser_karyawan (3 FK)
3. tblpelanggan (2 FK)
4. tbldelivery_order_header (2 FK)
5. tblorder_header (2 FK)

**Rekomendasi**: Tambahkan Foreign Key constraints untuk relasi antar tabel.

### 3. **Character Set Tidak Konsisten** ⚠️ MEDIUM
```
- latin1: 132 tabel (54.5%)
- utf8mb4: 57 tabel (23.6%)
- utf8: 3 tabel (1.2%)
```
**Dampak**:
- Masalah encoding character special (éàü, dll)
- Emoji tidak bisa disimpan di latin1
- Performa collation berbeda-beda

**Rekomendasi**: Standardisasi ke utf8mb4 untuk support Unicode penuh.

### 4. **Storage Engine** ✅ OK
```
- InnoDB: 191 tabel (99.5%) ✅
- MyISAM: 1 tabel (0.5%)
```
InnoDB sudah dominan (bagus untuk ACID compliance & FK support).

### 5. **Tabel Backup & Temporary** ⚠️ LOW
- **7 tabel backup** masih ada di production
- **2 tabel temporary** (_temp, _rst)

**Rekomendasi**: Pindahkan backup ke database terpisah.

---

## 🔧 Triggers Database

### 1. `trg_after_service_bayar`
- **Event**: AFTER UPDATE on tblservice
- **Fungsi**: Update statistik pelanggan (total transaksi, nominal, status member)
- **Target**: statistik_pelanggan table

### 2. `trg_update_rejection_summary`
- **Event**: AFTER UPDATE on tbservis_penawaran_part
- **Fungsi**: Track penawaran part yang disetujui/ditolak
- **Target**: tbservis_penawaran_rejection_summary

---

## 📋 Tabel-Tabel Penting

### Core Business Tables

#### 1. **Service Management**
- `tblservice` - Transaksi servis utama
- `tblservice_advisor` - Detail advisor servis
- `tblservis_barang` - Sparepart yang digunakan
- `tblservis_jasa` - Jasa servis yang dilakukan
- `tbservis_keluhan` - Keluhan pelanggan
- `tbservis_temuan` - Temuan mekanik
- `tbworkorderheader` & `tbworkorderdetail` - Work order

#### 2. **Customer & Vehicle**
- `tblpelanggan` - Master pelanggan (dengan kolom foto_rumah, gmaps, notlp)
- `tblkendaraan` - Data kendaraan
- `master_kategori_member` - Kategori member (Bronze/Silver/Gold)
- `statistik_pelanggan` - Statistik kunjungan

#### 3. **Inventory Management**
- `tblitem` - Master item/barang
- `tblitem_stok` - Stok barang per cabang
- `tbstok` - Transaksi stok
- `tblitem_spart` - Sparepart
- `tblitem_jasa` - Jasa/service

#### 4. **Sales & Purchase**
- `tblpenjualan_header` & `tblpenjualan_detail`
- `tblpembelian_header` & `tblpembelian_detail`
- `tblorder_header` & `tblorder_detail` (PO)
- `tblpurchase_request_header` & `tblpurchase_request_detail` (PR)
- `tbldelivery_order_header` & `tbldelivery_order_detail` (DO)

#### 5. **Finance**
- `tblpiutang_header` & `tblpiutang_detail`
- `tblhutang_header` & `tblhutang_detail`
- `tbkas_kasir_header` & `tbkas_kasir_detail`
- `tbakun` - Chart of Accounts

#### 6. **HR & User Management**
- `tbuser` - User aplikasi (dengan RBAC)
- `tb_user_roles` & `tb_permissions` - Role-based access control
- `tbpegawai` - Data karyawan
- `tblmekanik` - Data mekanik
- `tbabsensi` - Absensi karyawan
- `tb_kepala_mekanik_harian` - Kepala mekanik harian

---

## 💡 REKOMENDASI URGENT

### Priority 1: CRITICAL 🔴
1. **Tambahkan PRIMARY KEY ke semua tabel**
   ```sql
   -- Contoh untuk tblpelanggan
   ALTER TABLE tblpelanggan ADD PRIMARY KEY (nopelanggan);

   -- Contoh untuk tblservice
   ALTER TABLE tblservice ADD PRIMARY KEY (noservis);
   ```

2. **Konversi view_ tables dari TABLE ke VIEW**
   - Buat script untuk CREATE VIEW berdasarkan logic bisnis
   - Drop table view_xxx setelah VIEW berhasil dibuat
   - Dokumentasikan query untuk setiap view

### Priority 2: HIGH 🟡
3. **Tambahkan Foreign Key Constraints**
   ```sql
   -- Contoh
   ALTER TABLE tblservice
   ADD CONSTRAINT fk_service_pelanggan
   FOREIGN KEY (nopelanggan) REFERENCES tblpelanggan(nopelanggan);
   ```

4. **Standardisasi Character Set ke utf8mb4**
   ```sql
   ALTER TABLE nama_tabel CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

### Priority 3: MEDIUM 🟢
5. **Bersihkan tabel backup dari production**
   - Backup dulu, lalu drop tabel _backup_*

6. **Buat INDEX untuk kolom yang sering di-query**
   ```sql
   -- Contoh
   CREATE INDEX idx_service_tanggal ON tblservice(tglservis);
   CREATE INDEX idx_pelanggan_nama ON tblpelanggan(namapelanggan);
   ```

7. **Tambahkan dokumentasi untuk setiap tabel**
   - Table comments
   - Column comments untuk kolom penting

### Priority 4: LOW 🔵
8. **Pertimbangkan membuat Stored Procedures**
   - Untuk business logic kompleks
   - Untuk reporting yang sering digunakan

9. **Implementasi database versioning**
   - Gunakan migration tools (Flyway, Liquibase)
   - Track perubahan schema

---

## 📝 Catatan Tambahan

### Fitur Menarik yang Sudah Ada:
- ✅ Master tarif jemput dengan perhitungan jarak
- ✅ Sistem keluhan & temuan untuk servis
- ✅ Tracking penawaran part (approval/rejection)
- ✅ Statistik pelanggan otomatis (via trigger)
- ✅ Role-based access control (RBAC)
- ✅ Log cancel servis
- ✅ Multi-cabang support
- ✅ Integrasi Google Maps (kolom link_gmaps, foto_rumah)
- ✅ WhatsApp integration (kolom notlp untuk WA)

### Area yang Perlu Perhatian:
- ⚠️ Banyak kolom date dengan default '0000-00-00' (tidak valid di MySQL strict mode)
- ⚠️ Naming convention tidak konsisten (tb*, tbl*, master_*, tb_master_*)
- ⚠️ Beberapa tabel memiliki kolom yang tidak terpakai

---

## 🎯 Next Steps

1. **Audit Primary Keys**
   - Identifikasi kolom yang tepat untuk PK setiap tabel
   - Generate script ALTER TABLE untuk semua tabel

2. **Dokumentasi Relasi**
   - Buat ERD (Entity Relationship Diagram)
   - Dokumentasikan business rules

3. **Performance Tuning**
   - Analyze slow queries
   - Tambahkan index yang tepat
   - Optimize trigger performance

4. **Data Validation**
   - Check untuk data duplikat
   - Check untuk orphan records
   - Validate foreign key relationships

---

**Analisa dibuat**: 2025-11-29
**Tools**: Python analysis scripts + grep
**Status**: ✅ Complete
