# ANALISA RENCANA ORDER 21052025.xlsx

## 1. RINGKASAN EKSEKUTIF

File Excel "RENCANA ORDER 21052025.xlsx" adalah sistem perencanaan Purchase Order (PO) yang komprehensif untuk bengkel motor dengan **4 cabang**:
- **PACUL** (Cabang Pusat/ADW)
- **PESALAKAN** (ADW = Pesalakan)
- **CIKDITIRO** (CDT)
- **TRAYEMAN** (TRY)

File ini berisi **29 sheets** dengan **775 kode item** produk yang dikelola.

---

## 2. STRUKTUR SHEETS

### 2.1 Sheets Utama

| No | Sheet Name | Fungsi |
|----|------------|--------|
| 1 | **ORDER** | Sheet utama perhitungan rencana order (780 rows x 75 cols) |
| 2 | **FR_HITUNG_MINMAX** | Master item dengan kalkulasi MIN/MAX stok (1476 items) |
| 3 | **SUPPLIER** | Daftar supplier (58 supplier) |
| 4 | **REALISASI ORDER** | Tracking realisasi order yang sudah dilakukan |
| 5 | **ANTARCABANG** | Rencana transfer antar cabang (61 items) |
| 6 | **KETENTUAN** | Aturan kategori untuk ORDER 1 dan ORDER 2 |

### 2.2 Sheets Stok Per Cabang

| Sheet | Cabang |
|-------|--------|
| STOK PACUL | Stok di cabang Pacul |
| STOK PESALAKAN | Stok di cabang Pesalakan |
| STOK CIKDITIRO | Stok di cabang Cikditiro |
| STOK TRAYEMAN | Stok di cabang Trayeman |

### 2.3 Sheets Kategori Per Cabang

| Sheet | Cabang |
|-------|--------|
| KATEGORI PACUL | Kategori item Pacul |
| KATEGORI PESALAKAN | Kategori item Pesalakan |
| KATEGORI CIKDITIRO | Kategori item Cikditiro |
| KATEGORI TRAYEMAN | Kategori item Trayeman |

### 2.4 Sheets Barang Datang

| Sheet | Cabang |
|-------|--------|
| BR DTG ADW | Barang datang ADW (Pesalakan) |
| BR DTG PACUL | Barang datang Pacul |
| BR DTG CIKDITIRO | Barang datang Cikditiro |
| BR DTG TRAYEMAN | Barang datang Trayeman |

### 2.5 Sheets Pendukung

- **NOSTOK PACUL/PESALAKAN/CIKDITIRO/TRAYEMAN** - Item dengan stok kosong
- **CEK PACUL D / CEK PESALAKAN D / CEK CIK DITIRO D** - Cek item kategori D
- **PERSAMAAN** - Mapping item persamaan/substitusi
- **WA SUPPLIER** - Kontak WhatsApp supplier
- **BRG OTW NSA** - Barang yang sedang dalam perjalanan dari NSA

---

## 3. KOLOM-KOLOM SHEET ORDER

### 3.1 Identifikasi Item (A-S)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| A | KODE ITEM | Kode unik barang |
| B | STOK PACUL | Stok saat ini di Pacul |
| C | MAX PACUL | Stok maksimum Pacul |
| D | STOK ADW | Stok saat ini di ADW (Pesalakan) |
| E | MAX ADW | Stok maksimum ADW |
| F | STOK CDT | Stok saat ini di Cikditiro |
| G | MAX CDT | Stok maksimum Cikditiro |
| H | STOK TRY | Stok saat ini di Trayeman |
| I | MAX TRY | Stok maksimum Trayeman |
| J | KTG PACUL | Kategori item di Pacul (A/B/C/D/E) |
| K | KTG ADW | Kategori item di ADW |
| L | KTG CDT | Kategori item di Cikditiro |
| M | KTG TRY | Kategori item di Trayeman |
| N | NAMA BARANG | Nama lengkap barang |
| O | JENIS | Jenis barang (ORISIN/OLIGEN/AKIGEN) |
| P | KELIPATAN ORDER 1 | Kelipatan qty untuk ORDER 1 |
| Q | KELIPATAN ORDER 2 | Kelipatan qty untuk ORDER 2 |
| R | SAT BELI VS JUAL | Rasio satuan beli vs jual |
| S | HARGA | Harga pokok barang |

### 3.2 Perhitungan Kelebihan Stok (T-W)

| Kolom | Header | Rumus |
|-------|--------|-------|
| T | LEBIH PACUL | `=B-C` (Stok - Max) |
| U | LEBIH ADW | `=D-E` |
| V | LEBIH CDT | `=F-G` |
| W | LEBIH TRY | `=H-I` |

### 3.3 Transfer Antar Cabang (X-AI)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| X | PACUL KE ADW | Qty transfer dari Pacul ke ADW |
| Y | EDIT PACUL KE ADW | Override manual |
| Z | REAL PACUL KE ADW | Final qty transfer |
| AA | ADW KE PACUL | Qty transfer dari ADW ke Pacul |
| AB | EDIT ADW KE PACUL | Override manual |
| AC | REAL ADW KE PACUL | Final qty transfer |
| AD | ADW KE CDT | Qty transfer dari ADW ke Cikditiro |
| AE | EDIT ADW KE CDT | Override manual |
| AF | REAL ADW KE CDT | Final qty transfer |
| AG | ADW KE TRY | Qty transfer dari ADW ke Trayeman |
| AH | EDIT ADW KE TRY | Override manual |
| AI | REAL ADW KE TRY | Final qty transfer |

### 3.4 Perhitungan Order Per Cabang (AJ-AM)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| AJ | ORDER PACUL | Qty order untuk Pacul |
| AK | ORDER ADW | Qty order untuk ADW |
| AL | ORDER CDT | Qty order untuk Cikditiro |
| AM | ORDER TRY | Qty order untuk Trayeman |

### 3.5 ORDER 1 - Supplier Tempo <= 14 Hari (AN-AV)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| AN | EST S/D MAX STOK=ORDER1 | Estimasi order sampai stok max |
| AO | EST CEK PERSAMAAN | Cek persamaan item |
| AP | EDIT ORDER1 | Override manual |
| AQ | FINAL ORDER 1 | Qty final ORDER 1 |
| AR | SUPPLIER1<=TEMPO 14HARI | Supplier dengan tempo <= 14 hari |
| AS | EDIT SUPPLIER1 | Override supplier |
| AT | FINAL SUPPLIER1 | Supplier final |
| AU | ESTIMASI JML HRGA SUPPLIER1 | Estimasi nilai (Qty x Harga) |
| AV | TOTAL ORDER/ SUPPLIER1 | Total per supplier |

### 3.6 ORDER 2 - Supplier Tempo > 14 Hari (AW-BD)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| AW | EST UTK NSA/YG LAMA DTG=ORDER2 | Estimasi untuk NSA |
| AX | EDIT ORDER2 | Override manual |
| AY | ORDER 2 | Qty ORDER 2 |
| AZ | SUPPLIER2>TEMPO 14HARI | Supplier tempo > 14 hari |
| BA | EDIT SUPPLIER2 | Override supplier |
| BB | SUPPLIER2 | Supplier final ORDER 2 |
| BC | ESTIMASI JML HRGA SUPPLIER2 | Estimasi nilai ORDER 2 |
| BD | TOTAL ORDER/ SUPPLIER2 | Total per supplier |

### 3.7 Tracking & Realisasi (BE-BN)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| BE | KETERANGAN | Catatan |
| BF | REALISASI ORDER | Qty yang sudah direalisasi |
| BG | STOK PACUL AFTER ANTAR CABANG | Stok setelah transfer |
| BH | STOK ADW AFTER ANTAR CABANG | Stok setelah transfer |
| BI | STOK CDT AFTER ANTAR CABANG | Stok setelah transfer |
| BJ | STOK TRY AFTER ANTAR CABANG | Stok setelah transfer |
| BK | KEKURANGAN PACUL | Kekurangan stok Pacul |
| BL | KEKURANGAN ADW | Kekurangan stok ADW |
| BM | KEKURANGAN CDT | Kekurangan stok CDT |
| BN | KEKURANGAN TRY | Kekurangan stok Trayeman |

### 3.8 Distribusi Jatah (BO-BR)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| BO | JATAH PACUL | Alokasi untuk Pacul |
| BP | JATAH ADW | Alokasi untuk ADW |
| BQ | JATAH CDT | Alokasi untuk CDT |
| BR | JATAH TRY | Alokasi untuk Trayeman |

### 3.9 Order Segera (BS-BW)

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| BS | KODE BARANG | Duplikat kode item |
| BT | STOK UTK 3 HARI | Kebutuhan stok 3 hari |
| BU | ORDER SEGERA | Qty order urgent |
| BV | SUPPLIER ORDER SEGERA | Supplier untuk order urgent |
| BW | KETERANGAN2 | Catatan tambahan |

---

## 4. RUMUS-RUMUS PENTING

### 4.1 Rumus Stok dan MAX

```excel
// Stok Pacul - ambil dari sheet STOK PACUL atau KATEGORI PACUL
=IFERROR(IFERROR(VLOOKUP(A5,'STOK PACUL'!C:H,6,0),VLOOKUP(A5,'KATEGORI PACUL'!A:C,3,0)),0)

// MAX Pacul - ambil dari sheet KATEGORI PACUL
=IFERROR(VLOOKUP(A5,'KATEGORI PACUL'!A:D,4,0),0)
```

### 4.2 Rumus Kategori Item

```excel
// Kategori ditentukan berdasarkan posisi stok vs nostok
=IFERROR(VLOOKUP(A5,'NOSTOK PACUL'!A:C,3,0),
    IFERROR(IF(VLOOKUP(A5,'STOK PACUL'!A:B,2,0)="NOSTOK","E",
        VLOOKUP(A5,'KATEGORI PACUL'!A:B,2,0)),"E"))
```

**Sistem Kategori:**
- **A** = Fast Moving (penjualan tinggi, stok harus banyak)
- **B** = Medium Moving
- **C** = Slow Moving
- **D** = Dead Stock (ada stok tapi jarang laku)
- **E** = Non-Stock (tidak perlu distok)

### 4.3 Rumus Kelebihan Stok

```excel
// Kelebihan = Stok Aktual - Stok Maksimum
=B5-C5  // LEBIH PACUL = STOK PACUL - MAX PACUL
```

### 4.4 Rumus Transfer Antar Cabang

```excel
// Transfer dari Pacul ke ADW
=ROUNDDOWN(IF(AND(B5>C5,D5<E5),
    MIN(B5-C5,E5-D5),
    IF(AND(B5>0.5*C5,D5<0.5*E5),
        MIN(B5-(0.5*C5),(0.5*E5)-D5),0)),0)
```

**Logika Transfer:**
1. Jika Pacul > Max DAN ADW < Max -> Transfer selisih minimum
2. Jika Pacul > 50% Max DAN ADW < 50% Max -> Transfer sampai masing-masing 50%
3. Selain itu -> 0 (tidak transfer)

### 4.5 Rumus Order Per Cabang

```excel
// Order Pacul
=ROUND(IF(H5="E",0,                           // Jika kategori E, tidak order
    IF(AND(H5="D",B5>=1),0,                   // Jika kategori D dan stok >= 1, tidak order
        IF(H5="D",1-B5,                       // Jika kategori D, order sampai 1
            IF(C5*0.75-B5-Y5+V5<0,0,          // Hitung kebutuhan 75% dari max
                C5*0.75-B5-Y5+V5)))),0)       // dikurangi stok, transfer keluar, plus transfer masuk
```

**Logika Order:**
1. Kategori E -> Tidak order
2. Kategori D dengan stok >= 1 -> Tidak order
3. Kategori D dengan stok < 1 -> Order sampai qty 1
4. Kategori lain -> Order sampai 75% dari MAX

### 4.6 Rumus Estimasi Total Order

```excel
// Total ORDER 1 = Sum semua cabang, bulatkan ke kelipatan
=ROUNDUP((AJ5+AK5+AL5+AM5)/P5/R5,0)*P5
```

### 4.7 Rumus Supplier Selection

```excel
// Supplier 1 (Tempo <= 14 hari)
=IF(AQ5=0,"-",VLOOKUP(A5,FR_HITUNG_MINMAX!A:H,8,0))

// Supplier 2 (Tempo > 14 hari / NSA)
=IF(AY5=0,"-",VLOOKUP(A5,FR_HITUNG_MINMAX!A:I,9,0))
```

### 4.8 Rumus Estimasi Nilai Order

```excel
// Estimasi nilai = Qty x Harga x Rasio satuan
=AQ5*S5*R5  // FINAL ORDER 1 x HARGA x SAT BELI VS JUAL

// Total per supplier
=SUMIF(AT:AT,AT5,AU:AU)  // Sum nilai dimana supplier = supplier baris ini
```

### 4.9 Rumus Stok After Transfer

```excel
// Stok Pacul setelah transfer antar cabang
=B5-Z5+AC5  // Stok awal - keluar ke ADW + masuk dari ADW
```

### 4.10 Rumus Distribusi Jatah

```excel
// Jatah Pacul dari realisasi order
=IFERROR(ROUNDDOWN(BF5*BK5/(BK5+BL5+BM5),0),0)
// Proporsi berdasarkan kekurangan masing-masing cabang
```

### 4.11 Rumus Order Segera

```excel
// Qty untuk 3 hari penjualan
=ROUNDUP(AL2/2,0)  // MIN_STOK / 2 (asumsi MIN = penjualan 6 hari)
```

---

## 5. SHEET FR_HITUNG_MINMAX

### 5.1 Struktur Kolom

| Kolom | Header | Deskripsi |
|-------|--------|-----------|
| A | NoItem | Kode item |
| B | NamaItem | Nama barang |
| C | RakBarang | Lokasi rak |
| D | Jenis | ORISIN/OLIGEN/AKIGEN |
| E | STATUS | Kategori A/B/C/D |
| F | HargaPokok | Harga beli |
| G | StokMin | Stok minimum |
| H | Supplier | Supplier utama (tempo <= 14 hari) |
| I | Supplier2 | Supplier backup (tempo > 14 hari) |
| J | Supplier3 | Default 1 |
| K | HJQtyS1 | Kelipatan order |
| L-W | W1-W12 | Penjualan per minggu (12 minggu) |
| X | MAX_1W | Maximum penjualan 1 minggu |
| Y-AJ | 2W1-2W12 | Kumulatif 2 minggu |
| AK | MAX_2W | Maximum penjualan 2 minggu |
| AL | MIN_STOK | Stok minimum (berdasarkan penjualan) |
| AM | MAX_STOK | Stok maksimum |
| AN | STS | Status cabang (PACUL/PESALAKAN/dll) |
| AO | Quantity | Qty stok |
| AP | BR DTG | Barang datang |
| AQ | SO+BR DTG | Stok + Barang datang |
| AR | STOK | Stok final |
| AS | JUAL 3 HARI | Kebutuhan 3 hari |
| AT | CEK JML ITEM | Validasi duplikat |

### 5.2 Rumus MIN/MAX Stok

```excel
// MIN_STOK = Maximum penjualan 1 minggu
MIN_STOK = MAX_1W

// MAX_STOK = Maximum penjualan 2 minggu
MAX_STOK = MAX_2W

// JUAL 3 HARI = MIN_STOK / 2
=ROUNDUP(AL2/2,0)
```

---

## 6. DAFTAR SUPPLIER

| Kode | Nama Supplier | Keterangan |
|------|---------------|------------|
| NSA | NSA | Supplier utama tempo > 14 hari |
| GSM | GSM | Supplier utama tempo <= 14 hari |
| HKJ | HKJ | Supplier oli dan spare part |
| AHASS | AHASS | Dealer resmi Honda |
| NJM | NJM | - |
| BJP | BJP | - |
| IMAM | IMAM | - |
| SAPTA AJI | Sapta Aji | - |
| CVMMMS | CV MMMS | - |
| AAR | AAR | - |
| AIT | AIT | - |
| AJM | AJM | - |
| ATFF | ATFF | - |
| ATFS | ATFS | - |

---

## 7. TOTAL NILAI ORDER

Berdasarkan data di sheet ORDER row 1:

| Supplier | Total Nilai |
|----------|-------------|
| SUPPLIER 1 (Tempo <= 14 hari) | Rp 14,638,935 |
| SUPPLIER 2 (Tempo > 14 hari) | Rp 81,131,262 |
| **TOTAL** | **Rp 95,770,197** |

---

## 8. WORKFLOW RENCANA ORDER

```
+------------------------------------------------------------------+
|                    PROSES RENCANA ORDER                           |
+------------------------------------------------------------------+

1. UPDATE STOK
   +----------------+
   | Import stok    |--> STOK PACUL/PESALAKAN/CIKDITIRO/TRAYEMAN
   | dari sistem    |
   +----------------+

2. HITUNG MIN/MAX
   +----------------+     +----------------+
   | Ambil data     |-->  | Hitung         |--> MIN_STOK = MAX penjualan 1 minggu
   | penjualan      |     | MIN/MAX        |    MAX_STOK = MAX penjualan 2 minggu
   | 12 minggu      |     |                |
   +----------------+     +----------------+

3. KATEGORISASI
   +----------------+
   | Tentukan       |--> A = Fast Moving
   | kategori       |    B = Medium Moving
   | A/B/C/D/E      |    C = Slow Moving
   |                |    D = Dead Stock
   |                |    E = Non Stock
   +----------------+

4. TRANSFER ANTAR CABANG
   +----------------+     +----------------+
   | Cek kelebihan  |-->  | Generate       |--> Transfer dari cabang surplus
   | per cabang     |     | rencana        |    ke cabang defisit
   |                |     | transfer       |
   +----------------+     +----------------+

5. HITUNG ORDER PER CABANG
   +----------------+
   | Order =        |--> 75% MAX - Stok - Transfer Out + Transfer In
   | Kebutuhan -    |
   | Stok - Trans   |
   +----------------+

6. ALOKASI KE SUPPLIER
   +----------------+     +----------------+
   | ORDER 1        |-->  | Supplier       |  Tempo <= 14 hari (GSM, HKJ, dll)
   |                |     | Cepat          |
   +----------------+     +----------------+
   | ORDER 2        |-->  | Supplier       |  Tempo > 14 hari (NSA)
   |                |     | Lambat         |
   +----------------+     +----------------+

7. REALISASI & DISTRIBUSI
   +----------------+     +----------------+
   | Catat          |-->  | Distribusi     |--> Alokasi proporsional
   | realisasi      |     | ke cabang      |    ke tiap cabang
   | order          |     |                |
   +----------------+     +----------------+
```

---

## 9. REKOMENDASI IMPLEMENTASI DI MODUL PO

### 9.1 Fitur yang Perlu Diimplementasikan

1. **Master MIN/MAX Stok**
   - Hitung MIN = MAX penjualan 1 minggu terakhir
   - Hitung MAX = MAX penjualan 2 minggu terakhir
   - Simpan per item per cabang

2. **Sistem Kategorisasi Item**
   - Otomatis kategorisasi A/B/C/D/E berdasarkan pola penjualan
   - Dashboard untuk review kategori

3. **Rencana Transfer Antar Cabang**
   - Auto-suggest transfer dari cabang surplus ke defisit
   - Approval workflow untuk transfer

4. **Kalkulasi Kebutuhan Order**
   - Target 75% dari MAX setelah transfer
   - Kelipatan order per item

5. **Splitting Order ke Supplier**
   - ORDER 1: Supplier tempo <= 14 hari (urgent)
   - ORDER 2: Supplier tempo > 14 hari (reguler)

6. **Tracking Realisasi**
   - Record barang yang sudah dipesan
   - Distribusi ke cabang saat barang datang

7. **Alert Order Segera**
   - Item dengan stok < kebutuhan 3 hari
   - Push notification ke supervisor

### 9.2 Database Schema yang Diperlukan

```sql
-- Tabel MIN/MAX per item per cabang
CREATE TABLE IF NOT EXISTS tblitem_minmax (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_item VARCHAR(50),
    kd_cabang VARCHAR(10),
    min_stok INT DEFAULT 0,
    max_stok INT DEFAULT 0,
    kategori CHAR(1) DEFAULT 'E',  -- A/B/C/D/E
    kelipatan_order INT DEFAULT 1,
    supplier1 VARCHAR(50),  -- Tempo <= 14 hari
    supplier2 VARCHAR(50),  -- Tempo > 14 hari
    updated_at DATETIME,
    UNIQUE KEY (no_item, kd_cabang)
);

-- Tabel rencana transfer antar cabang
CREATE TABLE IF NOT EXISTS tblrencana_transfer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE,
    no_item VARCHAR(50),
    dari_cabang VARCHAR(10),
    ke_cabang VARCHAR(10),
    qty INT,
    status ENUM('draft','approved','executed') DEFAULT 'draft',
    approved_by VARCHAR(50),
    executed_by VARCHAR(50),
    created_at DATETIME,
    updated_at DATETIME
);

-- Tabel rencana order
CREATE TABLE IF NOT EXISTS tblrencana_order (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE,
    no_item VARCHAR(50),
    qty_pacul INT DEFAULT 0,
    qty_adw INT DEFAULT 0,
    qty_cdt INT DEFAULT 0,
    qty_try INT DEFAULT 0,
    qty_total INT DEFAULT 0,
    order_type ENUM('ORDER1','ORDER2') DEFAULT 'ORDER1',
    supplier VARCHAR(50),
    estimasi_nilai DECIMAL(15,2),
    status ENUM('draft','approved','ordered','received') DEFAULT 'draft',
    no_po VARCHAR(50),  -- Link ke PO setelah diorder
    created_at DATETIME,
    updated_at DATETIME
);

-- Tabel realisasi order
CREATE TABLE IF NOT EXISTS tblrealisasi_order (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_po VARCHAR(50),
    no_item VARCHAR(50),
    qty_order INT,
    qty_received INT DEFAULT 0,
    jatah_pacul INT DEFAULT 0,
    jatah_adw INT DEFAULT 0,
    jatah_cdt INT DEFAULT 0,
    jatah_try INT DEFAULT 0,
    status ENUM('pending','partial','complete') DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME
);

-- Tabel penjualan mingguan untuk hitung MIN/MAX
CREATE TABLE IF NOT EXISTS tblpenjualan_mingguan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_item VARCHAR(50),
    kd_cabang VARCHAR(10),
    tahun INT,
    minggu INT,  -- 1-52
    qty_jual INT DEFAULT 0,
    created_at DATETIME,
    UNIQUE KEY (no_item, kd_cabang, tahun, minggu)
);
```

### 9.3 Prioritas Implementasi

| Prioritas | Fitur | Kompleksitas |
|-----------|-------|--------------|
| 1 | Master MIN/MAX Stok | Medium |
| 2 | Kalkulasi Kebutuhan Order | High |
| 3 | Splitting ke Supplier | Medium |
| 4 | Rencana Transfer Antar Cabang | High |
| 5 | Tracking Realisasi | Medium |
| 6 | Alert Order Segera | Low |

---

## 10. KESIMPULAN

File Excel "RENCANA ORDER 21052025.xlsx" adalah sistem perencanaan PO yang matang dengan fitur:

1. **Multi-Branch Support** - Mengelola stok 4 cabang secara terpadu
2. **ABC Analysis** - Kategorisasi item berdasarkan pergerakan
3. **MIN/MAX System** - Stok optimal berdasarkan pola penjualan
4. **Auto Transfer Suggestion** - Optimasi stok antar cabang
5. **Dual Supplier System** - Pembagian order berdasarkan tempo
6. **Realization Tracking** - Distribusi proporsional saat barang datang

Sistem ini perlu didigitalisasi ke dalam modul PO aplikasi untuk:
- Mengurangi waktu perencanaan manual
- Meningkatkan akurasi perhitungan
- Memudahkan tracking dan audit trail
- Integrasi dengan stok real-time

---

*Dokumen ini dibuat berdasarkan analisis file RENCANA ORDER 21052025.xlsx*
*Tanggal: 19 Desember 2025*
