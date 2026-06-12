# DATABASE STRUCTURE ANALYSIS
## FITMOTOR BENGKEL MANAGEMENT SYSTEM

**Generated:** 2025-11-28  
**Database:** fitmotor_dbbengkel  
**Total Tables:** 242

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Database Overview](#database-overview)
3. [Table Categories](#table-categories)
4. [Core Modules](#core-modules)
5. [Detailed Table Analysis](#detailed-table-analysis)
6. [Relationships & Dependencies](#relationships--dependencies)
7. [Naming Conventions](#naming-conventions)
8. [Database Design Issues](#database-design-issues)
9. [Recommendations](#recommendations)

---

## Executive Summary

This document provides a comprehensive analysis of the FitMotor Bengkel database structure, 
which manages all aspects of a motorcycle workshop including service, sales, purchases, 
inventory, and customer management.

### Key Statistics

- **Total Tables:** 242
- **Master Data Tables:** 40
- **Transaction Tables:** 58
- **Views:** 47
- **System Tables:** 31

### Critical Issues Identified

- 50 tables without primary keys
- 110 tables without timestamp columns
- Limited use of foreign key constraints
- Inconsistent naming conventions

---

## Database Overview

The FitMotor Bengkel database is designed to support the complete operations of a motorcycle 
service center. It encompasses:

### Core Business Functions

1. **Customer & Vehicle Management**
   - Customer registration and profile management
   - Vehicle registration linked to customers
   - Customer categorization and membership tiers

2. **Service Management**
   - Service order creation and tracking
   - Work order management
   - Complaint/issue tracking (keluhan)
   - Service findings and recommendations (temuan)
   - Parts and labor tracking

3. **Inventory Management**
   - Product/item master data
   - Stock tracking across branches
   - Stock movement (in/out)
   - Item categorization

4. **Sales & Purchase**
   - Sales transactions
   - Purchase orders
   - Supplier management
   - Returns processing

5. **Procurement Chain**
   - Purchase Requests (PR)
   - Purchase Orders (PO)
   - Delivery Orders (DO)
   - Approval workflows

6. **Financial Management**
   - Accounts receivable (piutang)
   - Accounts payable (hutang)
   - Cash management

7. **HR & Payroll**
   - Employee management
   - Attendance tracking
   - Salary processing

8. **User Management & RBAC**
   - User accounts
   - Role-based access control
   - Permission management
   - Activity logging

---

## Table Categories

Tables are organized into the following functional categories:

### Master Data (40 tables)

- `master_bank` (7 columns) [PK]
- `master_kategori_member` (13 columns) [PK]
- `master_kedatangan_pelanggan` (15 columns) [PK]
- `master_perusahaan` (15 columns) [PK]
- `master_tarif_jemput` (7 columns) [NO PK]
- `master_tarif_jemput_range` (10 columns) [PK]
- `tbl_adm` (3 columns) [PK]
- `tbl_kepala_mekanik_harian` (10 columns) [PK]
- `tbl_master_kepala_mekanik` (11 columns) [NO PK]
- `tblist_bpjs` (2 columns) [PK]
- `tblitem` (49 columns) [NO PK]
- `tblitem_jasa` (4 columns) [NO PK]
- `tblitem_spart` (3 columns) [PK]
- `tblitemjenis` (10 columns) [PK]
- `tblitemsatuan` (7 columns) [PK]
- `tbljenis` (4 columns) [PK]
- `tblkeluhan` (6 columns) [PK]
- `tblkendaraan` (16 columns) [NO PK]
- `tblmekanik` (13 columns) [NO PK]
- `tblnamabarang` (9 columns) [PK]
- ... and 20 more tables

### Transaksi (58 tables)

- `tb_antrian_servis` (16 columns) [PK]
- `tbakun` (8 columns) [NO PK]
- `tbakun_pos` (1 columns) [NO PK]
- `tbbrg_keluar_detail` (12 columns) [PK]
- `tbbrg_keluar_header` (6 columns) [NO PK]
- `tbbrg_masuk_detail` (12 columns) [PK]
- `tbbrg_masuk_header` (6 columns) [NO PK]
- `tbitem_keluar_detail` (12 columns) [PK]
- `tbitem_keluar_header` (5 columns) [NO PK]
- `tbitem_masuk_detail` (12 columns) [PK]
- `tbitem_masuk_header` (5 columns) [NO PK]
- `tbjenis_penggantian_retur` (2 columns) [PK]
- `tbkas_kasir` (19 columns) [PK]
- `tbkas_kasir_detail` (4 columns) [NO PK]
- `tbkas_kasir_header` (9 columns) [NO PK]
- `tbkoreksi_stok_detail` (11 columns) [PK]
- `tbkoreksi_stok_header` (5 columns) [NO PK]
- `tblakunkas` (3 columns) [PK]
- `tblhutang_detail` (7 columns) [NO PK]
- `tblhutang_header` (8 columns) [NO PK]
- ... and 38 more tables

### System & Configuration (31 tables)

- `bulan_transaksi` (3 columns) [PK]
- `setting_highlight_member` (11 columns) [PK]
- `statistik_pelanggan` (26 columns) [PK]
- `tb_bank` (5 columns) [PK]
- `tb_booking` (9 columns) [PK]
- `tb_kepala_mekanik_schedule` (7 columns) [PK]
- `tb_master_level` (9 columns) [PK]
- `tb_master_posisi` (10 columns) [PK]
- `tb_mekanik_progress` (8 columns) [PK]
- `tb_progress_mekanik` (13 columns) [PK]
- `tbcabang` (8 columns) [PK]
- `tbcabang_tipe` (2 columns) [PK]
- `tbcara_bayar` (2 columns) [PK]
- `tbcari` (3 columns) [PK]
- `tbdivisi` (2 columns) [NO PK]
- `tbhargajual` (5 columns) [PK]
- `tbhjual_jasa` (3 columns) [PK]
- `tbitem_validation_log` (7 columns) [PK]
- `tbjabatan` (4 columns) [NO PK]
- `tbkeluhan_proses` (12 columns) [PK]
- ... and 11 more tables

### Lookup/Reference (21 tables)

- `tbagama` (2 columns) [PK]
- `tbdarah` (2 columns) [NO PK]
- `tbhari_kerja` (2 columns) [PK]
- `tbjenis_bayar` (1 columns) [NO PK]
- `tbjenis_motor` (4 columns) [PK]
- `tbjk` (2 columns) [PK]
- `tbkategori_motor` (4 columns) [PK]
- `tbkategori_rak` (4 columns) [PK]
- `tbpanggilan` (2 columns) [PK]
- `tbpendidikan` (2 columns) [PK]
- `tbstatus_emp` (2 columns) [PK]
- `tbstatus_harga` (2 columns) [PK]
- `tbstatus_kehadiran` (2 columns) [PK]
- `tbstatus_nikah` (2 columns) [PK]
- `tbstatus_produk` (2 columns) [PK]
- `tbtipe_item` (2 columns) [PK]
- `tbtipe_motor` (5 columns) [PK]
- `tbtipe_pajak` (2 columns) [PK]
- `tbtipe_potongan` (2 columns) [NO PK]
- `tbwarna` (2 columns) [PK]
- ... and 1 more tables

### Logging & Audit (3 tables)

- `log_perubahan_tarif` (8 columns) [PK]
- `tb_log_antrian` (8 columns) [PK]
- `tb_log_cancel_servis` (13 columns) [PK]

### Views (47 tables)

- `v_do_status` (0 columns) [NO PK]
- `v_po_status` (13 columns) [NO PK]
- `v_pr_status` (0 columns) [NO PK]
- `view_absensi` (13 columns) [NO PK]
- `view_cari_item` (18 columns) [NO PK]
- `view_cari_kendaraan` (17 columns) [NO PK]
- `view_cari_pelanggan` (20 columns) [NO PK]
- `view_do_tracking_latest` (23 columns) [NO PK]
- `view_item_classified` (14 columns) [NO PK]
- `view_keluhan_workorder` (0 columns) [NO PK]
- `view_laporan_cancel_servis` (20 columns) [NO PK]
- `view_master_kategori_member` (16 columns) [NO PK]
- `view_master_keluhan` (8 columns) [NO PK]
- `view_master_kepala_mekanik_aktif` (0 columns) [NO PK]
- `view_mekanik_users` (12 columns) [NO PK]
- `view_pelanggan_follow_up` (11 columns) [NO PK]
- `view_pelanggan_kendaraan` (18 columns) [NO PK]
- `view_pembayaran_hutang` (9 columns) [NO PK]
- `view_pembayaran_piutang` (9 columns) [NO PK]
- `view_pembelian_detail` (13 columns) [NO PK]
- ... and 27 more tables

### Backup Tables (7 tables)

- `_backup_tbl_kepala_mekanik_harian_20251115` (10 columns) [PK]
- `_backup_tbl_master_kepala_mekanik_20251115` (11 columns) [PK]
- `_backup_tblmekanik_20251115` (13 columns) [PK]
- `backup_tblservice_orphan` (5 columns) [NO PK]
- `master_tarif_jemput_backup` (7 columns) [NO PK]
- `tblitem_backup_categories` (4 columns) [NO PK]
- `tblservice_backup` (39 columns) [NO PK]

### User Management & RBAC (8 tables)

- `tb_permissions` (6 columns) [PK]
- `tb_user_account` (10 columns) [PK]
- `tb_user_activity_log` (8 columns) [PK]
- `tb_user_mekanik_mapping` (5 columns) [PK]
- `tb_user_roles` (9 columns) [PK]
- `tblevel` (2 columns) [PK]
- `tbuser` (27 columns) [PK]
- `tbuser_karyawan` (18 columns) [PK]

### Procurement (11 tables)

- `tbldelivery_order_detail` (9 columns) [PK]
- `tbldelivery_order_header` (19 columns) [PK]
- `tbldo_tracking` (7 columns) [PK]
- `tblpo_approval_log` (7 columns) [PK]
- `tblpr_approval_log` (7 columns) [PK]
- `tblpr_tracking` (7 columns) [PK]
- `tblpurchase_request_detail` (13 columns) [PK]
- `tblpurchase_request_header` (18 columns) [PK]
- `tblrfq_detail` (7 columns) [PK]
- `tblrfq_header` (10 columns) [PK]
- `tblrfq_supplier_response` (9 columns) [PK]

### HR & Payroll (16 tables)

- `tbabsensi` (17 columns) [PK]
- `tbabsensi_temp` (5 columns) [NO PK]
- `tbabsensi_temp_rst` (4 columns) [NO PK]
- `tbabsensi_upload` (7 columns) [PK]
- `tbemp_education` (7 columns) [PK]
- `tbemp_family` (7 columns) [PK]
- `tbemp_training` (5 columns) [PK]
- `tbemp_tunjangan` (4 columns) [PK]
- `tbhistory_divisi` (6 columns) [PK]
- `tbpegawai` (44 columns) [NO PK]
- `tbpegawai_salary` (4 columns) [PK]
- `tbtarif_ptkp` (5 columns) [PK]
- `tbtarif_ptkph` (4 columns) [PK]
- `tbwork_loc` (5 columns) [NO PK]
- `tbwork_schedule` (5 columns) [PK]
- `tbwork_schedule_dtl` (5 columns) [PK]

---

## Core Modules

### Module 1: Customer & Vehicle Management

#### Primary Tables

**TBLPELANGGAN (Customer Master)**

- **Total Columns:** 35
- **Primary Key:** NOT DEFINED
- **Key Columns:**
  - `nopelanggan`: varchar(20) NOT NULL
  - `namapelanggan`: varchar(50) NOT NULL
  - `alamat`: varchar(100) NOT NULL
  - `kota`: varchar(20) NOT NULL
  - `propinsi`: varchar(20) NOT NULL
  - `kodepost`: varchar(10) NOT NULL
  - `negara`: varchar(20) NOT NULL
  - `telephone`: varchar(20) NOT NULL
  - `fax`: varchar(20) NOT NULL
  - `kontakperson`: varchar(30) NOT NULL
  - `note`: varchar(100) NOT NULL
  - `potongan`: decimal(10
  - `tipepot`: varchar(1) NOT NULL
  - `lavelharga`: varchar(1) NOT NULL
  - `kgrup`: varchar(3) NOT NULL

**TBLKENDARAAN (Vehicle Registration)**

- **Total Columns:** 16
- **Primary Key:** NOT DEFINED
- **Key Columns:**
  - `nopolisi`: varchar(20) NOT NULL
  - `pemilik`: varchar(50) NOT NULL
  - `alamat`: varchar(50) NOT NULL
  - `kode_merek`: int(11) NOT NULL
  - `tipe`: varchar(50) NOT NULL
  - `kode_tipe`: int(11) NOT NULL
  - `jenis`: varchar(50) NOT NULL
  - `kode_jenis`: int(11) NOT NULL
  - `tahun_buat`: varchar(10) NOT NULL
  - `tahun_rakit`: varchar(10) NOT NULL

#### Relationships

```
TBLPELANGGAN (1) ----< (*) TBLKENDARAAN
TBLPELANGGAN (1) ----< (*) TBLSERVICE
TBLKENDARAAN (1) ----< (*) TBLSERVICE
```

### Module 2: Service Management

#### Primary Tables

**TBLSERVICE (Service Transaction Header)**

- **Total Columns:** 68
- **Primary Key:** NOT DEFINED
- **Indexes:** 10
- **Key Columns:**
  - `no_service`
  - `tanggal`
  - `jam`
  - `no_pelanggan`
  - `no_polisi`
  - `status`
  - `kd_cabang`
  - `id_user`
  - `total`
  - `diskon_persen`
  - `diskon_nom`
  - `ppn_persen`
  - `ppn_nom`
  - `total_grand`
  - `bayar`
  - `kembali`
  - `jenis_bayar`
  - `total_waktu`
  - `status_jemput`
  - `kondisi_motor`

**Service Detail Tables:**

- `TBLSERVIS_BARANG` - Parts used in service
- `TBLSERVIS_JASA` - Labor/services performed
- `TBSERVIS_KELUHAN` - Customer complaints/issues
- `TBSERVIS_TEMUAN` - Service findings/recommendations
- `TBSERVIS_WORKORDER` - Work order details

### Module 3: Inventory Management

**TBLITEM (Product/Item Master)**

- **Total Columns:** 49
- **Primary Key:** NOT DEFINED
- **Related Tables:**
  - `TBLITEM_STOK` - Stock levels per branch
  - `TBLITEM_JASA` - Service items
  - `TBLITEM_SPART` - Spare parts mapping
  - `TBSTOK` - Stock transactions

### Module 4: Sales & Purchase

#### Purchase Transactions

**Header-Detail Structure:**

- `TBLPEMBELIAN_HEADER` (1) ----< (*) `TBLPEMBELIAN_DETAIL`
- Linked to: `TBLSUPPLIER`

#### Sales Transactions

- `TBLPENJUALAN_HEADER` (1) ----< (*) `TBLPENJUALAN_DETAIL`
- Linked to: `TBLPELANGGAN`

### Module 5: Procurement Chain

The procurement process follows this workflow:

```
1. Purchase Request (PR)
   TBLPURCHASE_REQUEST_HEADER -> TBLPURCHASE_REQUEST_DETAIL

2. Purchase Order (PO)
   TBLORDER_HEADER -> TBLORDER_DETAIL

3. Delivery Order (DO)
   TBLDELIVERY_ORDER_HEADER -> TBLDELIVERY_ORDER_DETAIL
```

**Approval & Tracking:**

- `TBLPO_APPROVAL_LOG` - PO approval history
- `TBLPR_APPROVAL_LOG` - PR approval history
- `TBLPR_TRACKING` - PR tracking
- `TBLDO_TRACKING` - DO tracking

---

## Relationships & Dependencies

### Header-Detail Pairs

The following tables follow a standard header-detail pattern:

- `tbbrg_keluar_header` (1) ----< (*) `tbbrg_keluar_detail`
- `tbbrg_masuk_header` (1) ----< (*) `tbbrg_masuk_detail`
- `tbitem_keluar_header` (1) ----< (*) `tbitem_keluar_detail`
- `tbitem_masuk_header` (1) ----< (*) `tbitem_masuk_detail`
- `tbkas_kasir_header` (1) ----< (*) `tbkas_kasir_detail`
- `tbkoreksi_stok_header` (1) ----< (*) `tbkoreksi_stok_detail`
- `tbldelivery_order_header` (1) ----< (*) `tbldelivery_order_detail`
- `tblhutang_header` (1) ----< (*) `tblhutang_detail`
- `tblorderjual_header` (1) ----< (*) `tblorderjual_detail`
- `tblorder_header` (1) ----< (*) `tblorder_detail`
- `tblpembelian_header` (1) ----< (*) `tblpembelian_detail`
- `tblpenjualan_header` (1) ----< (*) `tblpenjualan_detail`
- `tblpiutang_header` (1) ----< (*) `tblpiutang_detail`
- `tblpurchase_request_header` (1) ----< (*) `tblpurchase_request_detail`
- `tblretur_pembelian_header` (1) ----< (*) `tblretur_pembelian_detail`
- `tblretur_penjualan_header` (1) ----< (*) `tblretur_penjualan_detail`
- `tblrfq_header` (1) ----< (*) `tblrfq_detail`
- `tbmaster_tipe_header` (1) ----< (*) `tbmaster_tipe_detail`
- `tbso_header` (1) ----< (*) `tbso_detail`
- `view_pembelian_header` (1) ----< (*) `view_pembelian_detail`
- `view_penjualan_header` (1) ----< (*) `view_penjualan_detail`
- `view_pesanan_pembelian_header` (1) ----< (*) `view_pesanan_pembelian_detail`

### Core Entity Relationships

**tblpelanggan**
- Description: Customer master table
- Has many: tblkendaraan, tblservice, tblpenjualan_header

**tblkendaraan**
- Description: Vehicle registration table
- Belongs to: tblpelanggan
- Has many: tblservice

**tblservice**
- Description: Service transaction header
- Belongs to: tblpelanggan, tblkendaraan
- Has many: tblservis_barang, tblservis_jasa, tbservis_keluhan, tbservis_temuan

**tblservis_barang**
- Description: Service parts/items detail
- Belongs to: tblservice, tblitem

**tblservis_jasa**
- Description: Service labor detail
- Belongs to: tblservice

**tblitem**
- Description: Item/product master
- Has many: tblitem_stok, tblservis_barang, tblpembelian_detail, tblpenjualan_detail

**tblsupplier**
- Description: Supplier master
- Has many: tblpembelian_header

**tblpembelian_header**
- Description: Purchase transaction header
- Belongs to: tblsupplier
- Has many: tblpembelian_detail

**tblpenjualan_header**
- Description: Sales transaction header
- Belongs to: tblpelanggan
- Has many: tblpenjualan_detail

---

## Naming Conventions

### Table Name Patterns

The database uses multiple naming patterns:

- `tbl_*` : 3 tables
- `tbl*` : 54 tables
- `tb*` : 122 tables
- `master_*` : 7 tables
- `view_*` : 44 tables
- `v_*` : 3 tables
- `_backup*` : 4 tables
- `no_prefix*` : 5 tables

### Analysis

**Issues Identified:**

1. **Inconsistent Prefixes** - Mix of `tbl`, `tb`, `tbl_`, and no prefix
2. **Mixed Naming Styles** - Some tables use plural, others singular
3. **Redundant Prefixes** - Both `master_` and `tbmaster_` exist

**Recommendations:**

- Standardize to single prefix convention (suggested: `tbl_`)
- Use snake_case consistently
- Avoid abbreviations unless widely understood
- Document naming standards for future development

---

## Database Design Issues

### Critical Issues

#### 1. Missing Primary Keys (50 tables)

Tables without primary keys:

1. `backup_tblservice_orphan`
2. `master_tarif_jemput`
3. `master_tarif_jemput_backup`
4. `tbabsensi_temp`
5. `tbabsensi_temp_rst`
6. `tbakun`
7. `tbakun_pos`
8. `tbbrg_keluar_header`
9. `tbbrg_masuk_header`
10. `tbdarah`
11. `tbdivisi`
12. `tbitem_keluar_header`
13. `tbitem_masuk_header`
14. `tbjabatan`
15. `tbjenis_bayar`
16. `tbkas_kasir_detail`
17. `tbkas_kasir_header`
18. `tbkoreksi_stok_header`
19. `tbl_master_kepala_mekanik`
20. `tblhutang_detail`
... and 30 more

**Impact:** Data integrity issues, slow queries, cannot establish proper relationships

#### 2. Missing Timestamps (110 tables)

**Impact:** Cannot track when records were created or modified

#### 3. Missing Foreign Key Constraints

**Impact:** No referential integrity enforcement at database level

#### 4. Large Tables

Tables with excessive columns (>40):

- `tblitem`: 49 columns
- `tblservice`: 68 columns
- `tbpegawai`: 44 columns

---

## Recommendations

### Priority 1: Critical (Immediate Action Required)

1. **Add Primary Keys to All Tables**
   - Define appropriate primary keys for all 50 tables currently lacking them
   - Use AUTO_INCREMENT INT for surrogate keys where natural keys don't exist
   - For transaction tables, consider composite keys

2. **Implement Foreign Key Constraints**
   - Add FK constraints for all identified relationships
   - Define appropriate ON DELETE and ON UPDATE actions
   - Ensures referential integrity at database level

3. **Add Indexes on Foreign Key Columns**
   - Improves query performance significantly
   - Essential for JOIN operations

### Priority 2: High (Within 1-2 Months)

4. **Add Timestamp Columns**
   - Add `created_at` and `updated_at` to all tables
   - Consider adding `created_by` and `updated_by` for audit trail

5. **Standardize Naming Conventions**
   - Choose one table prefix convention and stick to it
   - Rename tables following the standard
   - Update all application code accordingly

6. **Create Database Documentation**
   - Document all tables, columns, and relationships
   - Maintain data dictionary
   - Document business rules and constraints

### Priority 3: Medium (Within 3-6 Months)

7. **Normalize Large Tables**
   - Review tables with >40 columns
   - Split into logical related tables
   - Reduce data redundancy

8. **Optimize Indexes**
   - Analyze query patterns
   - Create composite indexes for common queries
   - Remove unused indexes

9. **Clean Up Backup Tables**
   - Move backup tables to separate schema
   - Implement proper backup/restore procedures
   - Remove temporary tables from production

### Priority 4: Low (Ongoing)

10. **Performance Monitoring**
    - Implement query performance monitoring
    - Identify slow queries
    - Optimize based on actual usage patterns

11. **Data Archival Strategy**
    - Implement data archival for old transactions
    - Keep production database lean
    - Maintain historical data in archive

---

## Appendix: Detailed Table List

For a complete list of all tables with detailed column information, 
refer to `DATABASE_TABLE_LIST.txt`

For entity relationship diagrams, refer to `DATABASE_ERD.txt`

For complete list of issues, refer to `DATABASE_ISSUES.txt`

---

*Document generated automatically from database schema analysis*
