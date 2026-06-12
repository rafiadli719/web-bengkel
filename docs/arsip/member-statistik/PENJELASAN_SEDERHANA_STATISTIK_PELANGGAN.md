# PENJELASAN SEDERHANA: STATISTIK PELANGGAN DI INPUT SERVIS

## 🎯 Konsep Dasar (Sangat Penting!)

### Pertanyaan: Bagaimana statistik pelanggan terupdate?

**Jawaban Singkat:** 
**OTOMATIS! Kasir tidak perlu melakukan apapun yang berbeda.**

Kasir tetap input servis seperti biasa:
1. Pilih pelanggan
2. Input jasa & barang
3. Klik BAYAR
4. **SELESAI!** → Statistik otomatis update di background

---

## 📊 Ilustrasi Proses Lengkap

### Skenario: Pelanggan "Budi Santoso" Service Motor

```
┌─────────────────────────────────────────────────────────────┐
│  HARI 1: Service Pertama                                    │
├─────────────────────────────────────────────────────────────┤
│  1. Kasir buka halaman: servis-input-reguler.php           │
│  2. Pilih pelanggan: Budi Santoso (AD 1234 AB)             │
│  3. Input service:                                          │
│     - Ganti oli: Rp 50.000                                  │
│     - Tune up: Rp 100.000                                   │
│     - Total: Rp 150.000                                     │
│  4. Kasir klik tombol "BAYAR"                               │
│                                                             │
│  ⚡ YANG TERJADI DI BACKGROUND (OTOMATIS):                  │
│                                                             │
│  A. Data disimpan ke tblservice:                            │
│     UPDATE tblservice                                       │
│     SET status_servis = 'bayar',                            │
│         total_akhir = 150000                                │
│     WHERE no_service = 'SV25000000001'                      │
│                                                             │
│  B. TRIGGER OTOMATIS JALAN!                                 │
│     (trg_after_service_bayar)                               │
│                                                             │
│  C. Trigger hitung statistik:                               │
│     - Total transaksi Budi: 1x                              │
│     - Total nominal Budi: Rp 150.000                        │
│     - Status member: Bronze (< 2 juta)                      │
│                                                             │
│  D. Trigger update/insert ke statistik_pelanggan:          │
│     INSERT INTO statistik_pelanggan                         │
│     (no_pelanggan, total_transaksi, total_nominal,          │
│      status_member, ...)                                    │
│     VALUES                                                  │
│     ('AD 1234 AB', 1, 150000, 'Bronze', ...)                │
│                                                             │
│  ✅ SELESAI! Kasir tidak perlu input apapun lagi            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  HARI 30: Service Kedua                                     │
├─────────────────────────────────────────────────────────────┤
│  1. Kasir input service lagi untuk Budi                     │
│  2. Total kali ini: Rp 500.000                              │
│  3. Kasir klik "BAYAR"                                      │
│                                                             │
│  ⚡ TRIGGER OTOMATIS JALAN LAGI:                            │
│                                                             │
│  - Hitung ulang total transaksi Budi: 2x                    │
│  - Hitung ulang total nominal: Rp 650.000                   │
│  - Status member masih: Bronze                              │
│                                                             │
│  UPDATE statistik_pelanggan                                 │
│  SET total_transaksi = 2,                                   │
│      total_nominal = 650000,                                │
│      status_member = 'Bronze'                               │
│  WHERE no_pelanggan = 'AD 1234 AB'                          │
│                                                             │
│  ✅ SELESAI! Update otomatis                                │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  HARI 60: Service Ketiga (Besar!)                          │
├─────────────────────────────────────────────────────────────┤
│  1. Kasir input service besar untuk Budi                    │
│  2. Total kali ini: Rp 2.000.000 (ganti mesin)              │
│  3. Kasir klik "BAYAR"                                      │
│                                                             │
│  ⚡ TRIGGER OTOMATIS JALAN:                                 │
│                                                             │
│  - Hitung ulang total transaksi: 3x                         │
│  - Hitung ulang total nominal: Rp 2.650.000                 │
│  - Status member NAIK: Silver! (>= 2 juta)                  │
│                                                             │
│  UPDATE statistik_pelanggan                                 │
│  SET total_transaksi = 3,                                   │
│      total_nominal = 2650000,                               │
│      status_member = 'Silver' ← NAIK!                       │
│  WHERE no_pelanggan = 'AD 1234 AB'                          │
│                                                             │
│  ✅ Budi sekarang member Silver!                            │
│  ✅ Dapat benefit: Diskon 10%, Prioritas antrian            │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 Teknologi yang Digunakan: MySQL TRIGGER

### Apa itu Trigger?

**Trigger = Kode yang jalan OTOMATIS saat ada event tertentu**

Analogi sederhana:
```
Trigger itu seperti ALARM di rumah:
- Anda tidak perlu tekan tombol alarm
- Alarm otomatis bunyi saat ada pencuri (event)
- Alarm jalan sendiri tanpa Anda suruh

Trigger di database:
- Kasir tidak perlu tekan tombol "update statistik"
- Trigger otomatis jalan saat status_servis = 'bayar' (event)
- Trigger update statistik sendiri tanpa kasir suruh
```

### Trigger yang Digunakan: `trg_after_service_bayar`

**Kapan trigger jalan?**
```sql
-- Trigger jalan SETELAH (AFTER) ada UPDATE di tblservice
-- DAN status_servis berubah menjadi 'bayar'

CREATE TRIGGER trg_after_service_bayar
AFTER UPDATE ON tblservice
FOR EACH ROW
BEGIN
    -- Cek apakah status_servis berubah jadi 'bayar'
    IF NEW.status_servis = 'bayar' AND NEW.total_akhir > 0 THEN
        -- JALANKAN KODE UPDATE STATISTIK
    END IF;
END;
```

**Apa yang dilakukan trigger?**
```sql
-- 1. Hitung total transaksi pelanggan
SELECT COUNT(*) FROM tblservice 
WHERE no_pelanggan = 'AD 1234 AB' 
AND status_servis = 'bayar';
-- Hasil: 3x

-- 2. Hitung total nominal
SELECT SUM(total_akhir) FROM tblservice 
WHERE no_pelanggan = 'AD 1234 AB' 
AND status_servis = 'bayar';
-- Hasil: Rp 2.650.000

-- 3. Tentukan status member
IF total_nominal < 2000000 THEN 'Bronze'
ELSEIF total_nominal < 5000000 THEN 'Silver' ← Budi di sini
ELSEIF total_nominal < 10000000 THEN 'Gold'
ELSE 'Platinum'

-- 4. Update ke statistik_pelanggan
UPDATE statistik_pelanggan
SET total_transaksi = 3,
    total_nominal = 2650000,
    status_member = 'Silver'
WHERE no_pelanggan = 'AD 1234 AB';
```

---

## 💻 Kode di Halaman Servis Input

### File: `servis-input-reguler.php`

#### Bagian 1: Tampilkan Status Member (OPSIONAL)

```php
<?php
// Di bagian atas file (setelah include koneksi)
include "_include_statistik_pelanggan.php";

// Setelah get data pelanggan
$kode_pelanggan = 'AD 1234 AB'; // Dari form

// TAMPILKAN STATUS MEMBER
if(!empty($kode_pelanggan)) {
    echo displayStatistikPelangganInfo($koneksi, $kode_pelanggan);
}
?>
```

**Output di halaman:**
```
┌───────────────────────────────────────────────────────┐
│ 🏆 Status Member: 🥈 Silver                          │
│                                                       │
│ Total Transaksi: 3x                                   │
│ Total Nominal: Rp 2.650.000                           │
│ Rata-rata: Rp 883.333                                 │
│                                                       │
│ 🎁 Benefit Member Silver:                            │
│ • Diskon 10% untuk service                            │
│ • Prioritas antrian                                   │
│                                                       │
│ Progress ke Gold:                                     │
│ ████████░░ 53%                                        │
│ Kurang Rp 2.350.000 lagi!                             │
└───────────────────────────────────────────────────────┘
```

**Benefit untuk kasir:**
- Kasir tahu pelanggan ini VIP (Silver)
- Kasir bisa tawarkan diskon 10%
- Kasir bisa prioritaskan antrian

---

#### Bagian 2: Proses Pembayaran (TIDAK PERLU DIUBAH!)

```php
<?php
// Kode existing kasir (TIDAK PERLU DIUBAH!)
if(isset($_POST['btnbayar'])) {
    $no_service = $_POST['txtnosrv'];
    $bayar = $_POST['txtbayar'];
    $kembali = $_POST['txtkembali'];
    $total_akhir = $_POST['txtnet'];
    
    // Update status servis menjadi bayar
    $query = "UPDATE tblservice 
              SET status_servis = 'bayar',
                  bayar = '$bayar',
                  kembali = '$kembali',
                  total_akhir = '$total_akhir'
              WHERE no_service = '$no_service'";
    
    mysqli_query($koneksi, $query);
    
    // ⚡ TRIGGER OTOMATIS JALAN DI SINI!
    // Kasir tidak perlu tambah kode apapun
    // Statistik otomatis update
    
    // Redirect
    echo "<script>window.location='servis-input-reguler.php?snoserv=$no_service';</script>";
}
?>
```

**PENTING:** 
- Kasir **TIDAK PERLU** tambah kode untuk update statistik
- Trigger MySQL yang handle semua
- Kode kasir tetap sama seperti sebelumnya

---

#### Bagian 3: Notifikasi WhatsApp (OPSIONAL)

```php
<?php
// OPSIONAL: Kirim WhatsApp setelah pembayaran
if(isset($_POST['btnbayar'])) {
    // ... kode pembayaran existing ...
    
    mysqli_query($koneksi, $query); // Update status bayar
    
    // ⚡ TRIGGER JALAN (otomatis)
    
    // OPSIONAL: Kirim WhatsApp
    include "config_whatsapp.php";
    
    if(WA_AUTO_SEND_AFTER_PAYMENT) {
        include "class_whatsapp_automation.php";
        $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
        $wa->sendTerimaKasih($no_service);
    }
    
    // Redirect dengan parameter wa=1 untuk tampilkan tombol WhatsApp
    echo "<script>window.location='servis-input-reguler.php?snoserv=$no_service&wa=1';</script>";
}

// Tampilkan tombol WhatsApp setelah bayar
if(isset($_GET['wa']) && $_GET['wa'] == '1') {
?>
    <div class="alert alert-success">
        <h4>✅ Pembayaran Berhasil!</h4>
        <p>Statistik pelanggan otomatis terupdate.</p>
        
        <a href="statistik_pelanggan_send_wa.php?no_service=<?php echo $no_service; ?>" 
           target="_blank" 
           class="btn btn-success">
            <i class="fa fa-whatsapp"></i> Kirim Ucapan Terima Kasih
        </a>
    </div>
<?php
}
?>
```

---

## 📋 Tabel Database yang Terlibat

### 1. Tabel `tblpelanggan` (Master Pelanggan)

```sql
CREATE TABLE tblpelanggan (
    nopelanggan VARCHAR(20) PRIMARY KEY,  -- AD 1234 AB
    namapelanggan VARCHAR(100),           -- Budi Santoso
    alamat TEXT,                          -- Jl. Merdeka No. 123
    telephone VARCHAR(20),                -- 081234567890
    kgrup VARCHAR(1)                      -- B/S/G/P (Bronze/Silver/Gold/Platinum)
);
```

**Contoh data:**
```
nopelanggan | namapelanggan | telephone     | kgrup
------------|---------------|---------------|------
AD 1234 AB  | Budi Santoso  | 081234567890  | S
AD 5678 CD  | Ani Wijaya    | 081298765432  | G
```

---

### 2. Tabel `tblservice` (Transaksi Service)

```sql
CREATE TABLE tblservice (
    no_service VARCHAR(50) PRIMARY KEY,   -- SV25000000001
    no_pelanggan VARCHAR(20),             -- AD 1234 AB (FK)
    tanggal DATE,                         -- 2025-11-02
    total_akhir DECIMAL(15,2),            -- 150000.00
    status_servis VARCHAR(20),            -- datang/diproses/selesai/bayar
    FOREIGN KEY (no_pelanggan) REFERENCES tblpelanggan(nopelanggan)
);
```

**Contoh data:**
```
no_service      | no_pelanggan | tanggal    | total_akhir | status_servis
----------------|--------------|------------|-------------|---------------
SV25000000001   | AD 1234 AB   | 2025-10-01 | 150000      | bayar
SV25000000002   | AD 1234 AB   | 2025-10-30 | 500000      | bayar
SV25000000003   | AD 1234 AB   | 2025-11-02 | 2000000     | bayar
```

---

### 3. Tabel `statistik_pelanggan` (Statistik Agregat)

```sql
CREATE TABLE statistik_pelanggan (
    id_statistik INT PRIMARY KEY AUTO_INCREMENT,
    no_pelanggan VARCHAR(20),             -- AD 1234 AB (FK)
    total_transaksi INT,                  -- 3
    total_nominal DECIMAL(15,2),          -- 2650000.00
    rata_rata_transaksi DECIMAL(15,2),    -- 883333.33
    status_member ENUM('Bronze','Silver','Gold','Platinum'), -- Silver
    tanggal_pertama_transaksi DATE,       -- 2025-10-01
    tanggal_terakhir_transaksi DATE,      -- 2025-11-02
    lama_tidak_datang INT,                -- 0 (hari sejak transaksi terakhir)
    estimasi_datang_berikutnya DATE,      -- 2025-12-02 (30 hari dari terakhir)
    FOREIGN KEY (no_pelanggan) REFERENCES tblpelanggan(nopelanggan)
);
```

**Contoh data:**
```
no_pelanggan | total_transaksi | total_nominal | status_member | tanggal_terakhir
-------------|-----------------|---------------|---------------|------------------
AD 1234 AB   | 3               | 2650000       | Silver        | 2025-11-02
AD 5678 CD   | 15              | 7500000       | Gold          | 2025-10-15
```

---

## 🎬 Timeline Lengkap (Step by Step)

### Transaksi 1: Rp 150.000

```
[Kasir Input Service]
↓
UPDATE tblservice SET status_servis='bayar', total_akhir=150000 WHERE no_service='SV001'
↓
[TRIGGER JALAN OTOMATIS]
↓
SELECT COUNT(*), SUM(total_akhir) FROM tblservice WHERE no_pelanggan='AD 1234 AB' AND status_servis='bayar'
→ Hasil: 1 transaksi, Rp 150.000
↓
Status member: 150000 < 2000000 → Bronze
↓
INSERT INTO statistik_pelanggan (no_pelanggan, total_transaksi, total_nominal, status_member)
VALUES ('AD 1234 AB', 1, 150000, 'Bronze')
↓
[SELESAI - Kasir tidak perlu lakukan apapun]
```

### Transaksi 2: Rp 500.000

```
[Kasir Input Service Lagi]
↓
UPDATE tblservice SET status_servis='bayar', total_akhir=500000 WHERE no_service='SV002'
↓
[TRIGGER JALAN OTOMATIS LAGI]
↓
SELECT COUNT(*), SUM(total_akhir) FROM tblservice WHERE no_pelanggan='AD 1234 AB' AND status_servis='bayar'
→ Hasil: 2 transaksi, Rp 650.000 (150000 + 500000)
↓
Status member: 650000 < 2000000 → Masih Bronze
↓
UPDATE statistik_pelanggan 
SET total_transaksi=2, total_nominal=650000, status_member='Bronze'
WHERE no_pelanggan='AD 1234 AB'
↓
[SELESAI]
```

### Transaksi 3: Rp 2.000.000

```
[Kasir Input Service Besar]
↓
UPDATE tblservice SET status_servis='bayar', total_akhir=2000000 WHERE no_service='SV003'
↓
[TRIGGER JALAN OTOMATIS]
↓
SELECT COUNT(*), SUM(total_akhir) FROM tblservice WHERE no_pelanggan='AD 1234 AB' AND status_servis='bayar'
→ Hasil: 3 transaksi, Rp 2.650.000 (150000 + 500000 + 2000000)
↓
Status member: 2650000 >= 2000000 → NAIK KE SILVER! 🎉
↓
UPDATE statistik_pelanggan 
SET total_transaksi=3, total_nominal=2650000, status_member='Silver'
WHERE no_pelanggan='AD 1234 AB'
↓
UPDATE tblpelanggan SET kgrup='S' WHERE nopelanggan='AD 1234 AB'
↓
[SELESAI - Pelanggan sekarang Silver!]
```

---

## ❓ FAQ (Pertanyaan yang Sering Ditanyakan)

### Q1: Apakah kasir harus klik tombol "Update Statistik"?

**A: TIDAK!** Statistik update otomatis saat kasir klik "BAYAR". Tidak ada tombol tambahan.

---

### Q2: Bagaimana jika kasir lupa update statistik?

**A: Tidak mungkin lupa!** Karena trigger MySQL yang handle, bukan kasir. Trigger jalan otomatis 100% tanpa campur tangan kasir.

---

### Q3: Apakah workflow kasir berubah?

**A: TIDAK!** Kasir tetap input servis seperti biasa:
1. Pilih pelanggan
2. Input jasa & barang
3. Klik BAYAR
4. Selesai

Tidak ada langkah tambahan.

---

### Q4: Kapan statistik terupdate?

**A: LANGSUNG saat kasir klik BAYAR!** 

Prosesnya sangat cepat (< 1 detik):
```
Kasir klik BAYAR → Trigger jalan → Statistik update → Selesai
(semua dalam 1 detik)
```

---

### Q5: Bagaimana jika ada 2 kasir input service bersamaan?

**A: Aman!** MySQL trigger handle concurrent transactions dengan baik. Setiap transaksi diproses secara terpisah dan akurat.

---

### Q6: Apakah bisa lihat statistik real-time?

**A: YA!** Buka dashboard:
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/statistik_pelanggan_dashboard.php
```

Dashboard menampilkan:
- Total pelanggan per status member
- Top pelanggan
- Pelanggan yang perlu follow-up
- Dan lainnya (semua real-time!)

---

### Q7: Bagaimana jika transaksi dibatalkan?

**A: Statistik otomatis update!**

Jika status_servis diubah dari 'bayar' ke 'batal':
```sql
UPDATE tblservice SET status_servis='batal' WHERE no_service='SV003';
```

Trigger akan hitung ulang (hanya hitung transaksi dengan status='bayar'):
```
Total transaksi: 2 (bukan 3)
Total nominal: Rp 650.000 (bukan 2.650.000)
Status member: Bronze (turun dari Silver)
```

---

### Q8: Apakah perlu install software tambahan?

**A: TIDAK!** Semua menggunakan fitur MySQL standar (Trigger). Tidak perlu install apapun.

---

### Q9: Bagaimana cara cek trigger sudah jalan?

**A: Cek tabel statistik_pelanggan:**

```sql
-- Sebelum bayar
SELECT * FROM statistik_pelanggan WHERE no_pelanggan='AD 1234 AB';
-- Hasil: total_transaksi=2, total_nominal=650000

-- Kasir input service baru dan bayar (total Rp 2.000.000)

-- Setelah bayar (cek lagi)
SELECT * FROM statistik_pelanggan WHERE no_pelanggan='AD 1234 AB';
-- Hasil: total_transaksi=3, total_nominal=2650000 ✅ TERUPDATE!
```

---

### Q10: Bagaimana jika trigger tidak jalan?

**A: Cek instalasi trigger:**

```sql
-- Cek trigger ada
SHOW TRIGGERS LIKE 'tblservice';

-- Harusnya muncul: trg_after_service_bayar

-- Jika tidak ada, install ulang:
source database_statistik_pelanggan_otomatis.sql
```

---

## ✅ Kesimpulan

### Yang Perlu Diingat:

1. **Kasir TIDAK PERLU melakukan apapun yang berbeda**
   - Input servis seperti biasa
   - Klik BAYAR seperti biasa
   - Selesai!

2. **Statistik update OTOMATIS via MySQL Trigger**
   - Trigger jalan saat status_servis = 'bayar'
   - Hitung total transaksi & nominal
   - Tentukan status member
   - Update tabel statistik_pelanggan

3. **Status member naik OTOMATIS**
   - Bronze → Silver (Rp 2 juta)
   - Silver → Gold (Rp 5 juta)
   - Gold → Platinum (Rp 10 juta)

4. **Dashboard real-time**
   - Buka kapan saja untuk lihat statistik
   - Data selalu update
   - Tidak perlu refresh manual

5. **WhatsApp otomatis (opsional)**
   - Kirim ucapan terima kasih
   - Info garansi service
   - Reminder service berikutnya

---

## 🎓 Analogi Sederhana

Bayangkan sistem statistik pelanggan seperti **SPEEDOMETER di mobil**:

```
Anda mengendarai mobil:
- Anda tidak perlu tekan tombol "update kecepatan"
- Speedometer otomatis update saat mobil jalan
- Semakin cepat mobil, angka speedometer naik
- Anda hanya fokus menyetir

Kasir input servis:
- Kasir tidak perlu tekan tombol "update statistik"
- Statistik otomatis update saat kasir klik BAYAR
- Semakin banyak transaksi, status member naik
- Kasir hanya fokus input servis
```

---

**Semoga penjelasan ini membantu!** 🎉

Jika masih ada yang kurang jelas, silakan tanyakan bagian mana yang perlu dijelaskan lebih detail.

---

**Dibuat:** 2 November 2025  
**Versi:** 1.0  
**Developer:** Fit Motor Development Team
