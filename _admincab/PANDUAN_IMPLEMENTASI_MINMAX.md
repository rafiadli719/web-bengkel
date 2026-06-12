# Panduan Implementasi Sistem MIN/MAX Stok

## Deskripsi Sistem

Sistem MIN/MAX Stok adalah fitur untuk menghitung kebutuhan stok minimum dan maksimum per item per cabang berdasarkan data penjualan 84 hari terakhir (12 minggu).

## Aturan Kalkulasi

### 1. Periode Data
- **Periode**: 84 hari (12 minggu)
- **Week 1-12**: W1 = minggu terbaru, W12 = minggu terlama

### 2. Kalkulasi MIN/MAX
- **MIN Stok**: MAX penjualan 1 minggu tertinggi (dari W1-W12)
- **MAX Stok**: MAX penjualan 2 minggu berturut-turut tertinggi

### 3. Klasifikasi Kategori (Berdasarkan Frekuensi Transaksi)

| Kategori | Interval Transaksi | Jumlah Transaksi/84 hari | Keterangan |
|----------|-------------------|--------------------------|------------|
| A | 1-3 hari | 22-84 transaksi | Fast Moving |
| B | 4-12 hari | 7-21 transaksi | Medium Moving |
| C | >12 hari | 4-6 transaksi | Slow Moving |
| D | >30 hari | 1-3 transaksi | Dead Stock |
| E | N/A | 0 transaksi | Non-Stock |

### 4. Lead Time
- Default: 3 hari

## File yang Dibuat/Dimodifikasi

### File Baru

1. **`lib/MinMaxCalculator.php`**
   - Class PHP untuk kalkulasi MIN/MAX
   - Methods:
     - `populatePenjualanHarian()` - Populate data penjualan harian dari tbstok
     - `populatePenjualanMingguan()` - Aggregate ke mingguan
     - `hitungMinMax($kdCabang)` - Hitung MIN/MAX dan klasifikasi
     - `getItemPerluOrder()` - Get item dengan stok < MIN
     - `getSummaryPerKategori()` - Summary per kategori per cabang
     - `getCabangStats()` - Statistik per cabang
     - `generateSaranTransfer()` - Generate saran transfer antar cabang

2. **`_ajax/ajax-minmax-calculation.php`**
   - AJAX endpoint untuk operasi MIN/MAX
   - Actions:
     - `recalculate` - Hitung ulang MIN/MAX
     - `get_items_need_order` - Get item perlu order
     - `get_summary` - Get summary kategori
     - `get_cabang_stats` - Get statistik cabang
     - `get_item_detail` - Get detail satu item
     - `get_transfer_suggest` - Get saran transfer
     - `search_items` - Cari item dengan filter

3. **`database_migrations/rencana_order_tables.sql`**
   - Struktur tabel database yang dinamis
   - Tabel utama:
     - `tblpenjualan_harian` - Data penjualan per hari
     - `tblpenjualan_mingguan` - Agregasi per minggu
     - `tblitem_minmax` - Master MIN/MAX per item per cabang
     - `tblrencana_order_header` - Header rencana order
     - `tblrencana_order_detail` - Detail per item
     - `tblrencana_order_detail_cabang` - Detail per cabang (dinamis)
     - `tblrencana_transfer` - Transfer antar cabang
     - `tblrealisasi_order` - Realisasi order
     - `tblsupplier_tempo` - Master supplier tempo

### File Dimodifikasi

1. **`procurement_dashboard.php`**
   - Ditambahkan tab MIN/MAX Stok dan Item Perlu Order
   - Statistik per cabang dengan klasifikasi A/B/C/D/E
   - Filter dan pencarian item
   - Tombol "Hitung Ulang MIN/MAX"

## Cara Install

### 1. Jalankan Migration Database

**PENTING: Jalankan file SQL secara berurutan!**

**Step 1 - Buat Tabel:**
```sql
-- Jalankan file ini PERTAMA
SOURCE _admincab/database_migrations/01_rencana_order_tables.sql;
```

**Step 2 - Buat Views:**
```sql
-- Jalankan file ini SETELAH step 1 berhasil
SOURCE _admincab/database_migrations/02_rencana_order_views.sql;
```

Atau di phpMyAdmin:
1. Buka tab "SQL"
2. Copy-paste isi file `01_rencana_order_tables.sql` lalu klik "Go"
3. Setelah berhasil, copy-paste isi file `02_rencana_order_views.sql` lalu klik "Go"

### 2. Verifikasi File PHP

Pastikan file-file berikut ada:
- `_admincab/lib/MinMaxCalculator.php`
- `_admincab/_ajax/ajax-minmax-calculation.php`
- `_admincab/procurement_dashboard.php`

### 3. Hitung MIN/MAX Pertama Kali

1. Buka `procurement_dashboard.php`
2. Klik tombol "Hitung Ulang MIN/MAX"
3. Tunggu proses selesai (bisa memakan waktu beberapa menit)

## Cara Penggunaan

### 1. Dashboard MIN/MAX

Akses: `_admincab/procurement_dashboard.php`

Tab "MIN/MAX Stok" menampilkan:
- Jumlah item URGENT (stok < 50% MIN)
- Jumlah item perlu order (stok < MIN)
- Total item dalam sistem
- Jumlah cabang aktif
- Statistik per cabang dengan breakdown kategori A/B/C/D/E

### 2. Item Perlu Order

Tab "Item Perlu Order" menampilkan:
- Daftar item dengan stok di bawah MIN
- Filter: Cabang, Kategori, Pencarian
- Informasi: Stok, MIN, MAX, Kategori, Qty Kurang, Saran Order, Status

### 3. Hitung Ulang MIN/MAX

Tombol "Hitung Ulang MIN/MAX" akan:
1. Mengumpulkan data penjualan 84 hari terakhir
2. Menghitung agregasi mingguan (W1-W12)
3. Menentukan MIN (max 1 minggu) dan MAX (max 2 minggu berturut)
4. Mengklasifikasikan kategori A/B/C/D/E
5. Menghitung stok saat ini dari tbstok

## Struktur Database

### tblitem_minmax

```
+-----------------------+---------------+------+-----+
| Field                 | Type          | Null | Key |
+-----------------------+---------------+------+-----+
| id                    | int           | NO   | PRI |
| no_item               | varchar(50)   | NO   |     |
| kd_cabang             | varchar(10)   | NO   |     |
| w1...w12              | int           | YES  |     |
| max_1w                | int           | YES  |     |
| max_2w                | int           | YES  |     |
| min_stok              | int           | YES  |     |
| max_stok              | int           | YES  |     |
| total_transaksi_84hari| int           | YES  |     |
| avg_interval_hari     | decimal(5,2)  | YES  |     |
| kategori              | char(1)       | YES  |     |
| lead_time_hari        | int           | YES  |     |
| stok_saat_ini         | int           | YES  |     |
| supplier1             | varchar(50)   | YES  |     |
| supplier2             | varchar(50)   | YES  |     |
+-----------------------+---------------+------+-----+
```

### tblrencana_order_detail_cabang

Struktur dinamis per cabang (menggunakan FK ke tbcabang):

```
+----------------------+---------------+------+-----+
| Field                | Type          | Null | Key |
+----------------------+---------------+------+-----+
| id                   | int           | NO   | PRI |
| no_rencana           | varchar(30)   | NO   |     |
| no_item              | varchar(50)   | NO   |     |
| kd_cabang            | varchar(10)   | NO   | FK  |
| stok_awal            | int           | YES  |     |
| min_stok             | int           | YES  |     |
| max_stok             | int           | YES  |     |
| kategori             | char(1)       | YES  |     |
| transfer_masuk       | int           | YES  |     |
| transfer_keluar      | int           | YES  |     |
| stok_setelah_transfer| int           | YES  |     |
| order_qty            | int           | YES  |     |
| jatah_qty            | int           | YES  |     |
| real_qty             | int           | YES  |     |
+----------------------+---------------+------+-----+
```

## Troubleshooting

### Error: Table doesn't exist

Jalankan migration SQL terlebih dahulu.

### Error: Foreign key constraint fails

Pastikan tabel `tbcabang` sudah ada dan memiliki data.

### Kalkulasi tidak berjalan

1. Periksa koneksi database
2. Pastikan tabel `tbstok` memiliki data transaksi
3. Periksa log error PHP

### Data MIN/MAX kosong

1. Jalankan "Hitung Ulang MIN/MAX"
2. Pastikan ada data penjualan (tipe 3 atau 4) di tbstok dalam 84 hari terakhir

## Pengembangan Selanjutnya

1. Fitur Generate Rencana Order otomatis
2. Integrasi dengan modul PO
3. Laporan MIN/MAX per periode
4. Export ke Excel
5. Notifikasi email/WA untuk item URGENT
