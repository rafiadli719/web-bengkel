# DOKUMENTASI MASTER KEDATANGAN PELANGGAN

## 📋 DAFTAR ISI
1. [Penjelasan Sistem](#penjelasan-sistem)
2. [Struktur Database](#struktur-database)
3. [Cara Kerja Trigger](#cara-kerja-trigger)
4. [Instalasi](#instalasi)
5. [Contoh Query](#contoh-query)
6. [Integrasi PHP](#integrasi-php)
7. [WhatsApp Automation](#whatsapp-automation)

---

## 📖 PENJELASAN SISTEM

### Konsep Dasar

Sistem **Master Kedatangan Pelanggan** adalah sistem tracking yang mencatat **setiap kunjungan pelanggan** ke bengkel, bukan berdasarkan total nominal transaksi.

### Perbedaan dengan Sistem Lama

| Aspek | Sistem Lama (Statistik) | Sistem Baru (Kedatangan) |
|-------|-------------------------|--------------------------|
| **Basis** | Total nominal transaksi | Jumlah kunjungan |
| **Member** | Bronze/Silver/Gold/Platinum berdasarkan Rp | Bronze/Silver/Gold/Platinum berdasarkan kunjungan |
| **Threshold** | < 2jt, 2-5jt, 5-10jt, >10jt | < 5x, 5-10x, 10-20x, >20x |
| **Focus** | Nilai transaksi | Loyalitas pelanggan |
| **Tracking** | Total spending | Frekuensi kunjungan |

### Kategori Member Baru

- 🥉 **Bronze:** < 5 kunjungan
- 🥈 **Silver:** 5-9 kunjungan (Diskon 10%)
- 🥇 **Gold:** 10-19 kunjungan (Diskon 15%)
- 💎 **Platinum:** ≥ 20 kunjungan (Diskon 20%)

---

## 🗄️ STRUKTUR DATABASE

### Tabel: `master_kedatangan_pelanggan`

```sql
CREATE TABLE `master_kedatangan_pelanggan` (
  `id_kedatangan` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `no_pelanggan` VARCHAR(20) NOT NULL,
  `no_service` VARCHAR(50) DEFAULT NULL,
  `kedatangan_ke` INT(11) NOT NULL DEFAULT 1,
  `tanggal_datang` DATE NOT NULL,
  `tanggal_sebelumnya` DATE DEFAULT NULL,
  `jarak_hari` INT(11) DEFAULT 0,
  `total_transaksi` DECIMAL(15,2) DEFAULT 0.00,
  `jumlah_item` INT(11) DEFAULT 0,
  `rata2_nilai_per_item` DECIMAL(15,2) DEFAULT 0.00,
  `estimasi_datang_berikut` DATE DEFAULT NULL,
  `status_garansi` ENUM('aktif','expired','tidak_ada') DEFAULT 'tidak_ada',
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Penjelasan Kolom

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_kedatangan` | INT | ID unik setiap kedatangan (auto increment) |
| `no_pelanggan` | VARCHAR(20) | Nomor polisi pelanggan (FK ke tblpelanggan) |
| `no_service` | VARCHAR(50) | Nomor service/transaksi |
| `kedatangan_ke` | INT | Urutan kunjungan (1, 2, 3, dst) |
| `tanggal_datang` | DATE | Tanggal servis sekarang |
| `tanggal_sebelumnya` | DATE | Tanggal servis sebelumnya |
| `jarak_hari` | INT | Selisih hari dari kunjungan sebelumnya |
| `total_transaksi` | DECIMAL | Total nominal transaksi |
| `jumlah_item` | INT | Jumlah item/layanan servis |
| `rata2_nilai_per_item` | DECIMAL | Rata-rata nilai per item |
| `estimasi_datang_berikut` | DATE | Estimasi kunjungan berikutnya (+30 hari) |
| `status_garansi` | ENUM | Status garansi (aktif/expired/tidak_ada) |
| `keterangan` | TEXT | Catatan tambahan |

---

## ⚙️ CARA KERJA TRIGGER

### Trigger: `trg_after_service_bayar_kedatangan`

Trigger ini otomatis jalan **SETELAH** status servis diupdate menjadi `'bayar'`.

### Alur Kerja

```
1. Kasir klik tombol BAYAR
   ↓
2. Status servis berubah dari 'selesai' → 'bayar'
   ↓
3. Trigger otomatis jalan
   ↓
4. Sistem cek: Apakah ini kunjungan pertama?
   ├─ YA → kedatangan_ke = 1, jarak_hari = 0
   └─ TIDAK → kedatangan_ke = (terakhir + 1), hitung jarak_hari
   ↓
5. Hitung jumlah item servis
   ↓
6. Hitung rata-rata nilai per item
   ↓
7. Set estimasi datang berikutnya (+30 hari)
   ↓
8. Insert data ke master_kedatangan_pelanggan
   ↓
9. SELESAI (kasir tidak perlu lakukan apapun!)
```

### Contoh Skenario

#### Skenario 1: Pelanggan Baru (Kunjungan Pertama)

**Data Input:**
- No. Pelanggan: AD 1234 AB
- Tanggal: 2025-11-03
- Total: Rp 500.000
- Jumlah Item: 3

**Hasil di Tabel:**
```
id_kedatangan: 1
no_pelanggan: AD 1234 AB
kedatangan_ke: 1
tanggal_datang: 2025-11-03
tanggal_sebelumnya: NULL
jarak_hari: 0
total_transaksi: 500000
jumlah_item: 3
rata2_nilai_per_item: 166666.67
estimasi_datang_berikut: 2025-12-03
```

#### Skenario 2: Pelanggan Lama (Kunjungan Ke-5)

**Data Input:**
- No. Pelanggan: AD 1234 AB (sudah 4x datang sebelumnya)
- Tanggal: 2025-11-03
- Tanggal Terakhir: 2025-10-15 (19 hari lalu)
- Total: Rp 750.000
- Jumlah Item: 4

**Hasil di Tabel:**
```
id_kedatangan: 5
no_pelanggan: AD 1234 AB
kedatangan_ke: 5
tanggal_datang: 2025-11-03
tanggal_sebelumnya: 2025-10-15
jarak_hari: 19
total_transaksi: 750000
jumlah_item: 4
rata2_nilai_per_item: 187500
estimasi_datang_berikut: 2025-12-03
status_member: 🥈 Silver (5 kunjungan)
```

---

## 🚀 INSTALASI

### Step 1: Import Database

```bash
# Via Command Line
mysql -u root -p fitmotor_dbbengkel < database_master_kedatangan_pelanggan.sql

# Via phpMyAdmin
1. Buka phpMyAdmin
2. Pilih database: fitmotor_dbbengkel
3. Tab "Import"
4. Pilih file: database_master_kedatangan_pelanggan.sql
5. Klik "Go"
```

### Step 2: Refresh Data Awal (Opsional)

Jika sudah ada data servis lama, refresh untuk generate data kedatangan:

```sql
CALL sp_refresh_master_kedatangan();
```

### Step 3: Verifikasi

```sql
-- Cek total record
SELECT COUNT(*) AS total_kedatangan FROM master_kedatangan_pelanggan;

-- Lihat 10 data terakhir
SELECT * FROM master_kedatangan_pelanggan ORDER BY created_at DESC LIMIT 10;

-- Lihat ringkasan per pelanggan
SELECT * FROM view_ringkasan_kedatangan_pelanggan LIMIT 10;
```

---

## 📊 CONTOH QUERY

### Query 1: Ringkasan Semua Pelanggan

```sql
SELECT 
    nopelanggan,
    namapelanggan,
    jumlah_kedatangan,
    total_nilai_transaksi,
    rata2_nilai_transaksi,
    tanggal_pertama_datang,
    tanggal_terakhir_datang,
    lama_tidak_datang_hari,
    kategori_member,
    status_pelanggan
FROM view_ringkasan_kedatangan_pelanggan
ORDER BY jumlah_kedatangan DESC
LIMIT 50;
```

**Output:**
```
┌──────────────┬───────────────┬──────────────┬────────────────┬──────────┬────────────┐
│ No. Pelanggan│ Nama          │ Jumlah Datang│ Total Transaksi│ Member   │ Status     │
├──────────────┼───────────────┼──────────────┼────────────────┼──────────┼────────────┤
│ AD 1234 AB   │ Budi Santoso  │ 25x          │ Rp 12.500.000  │ Platinum │ Aktif      │
│ AD 5678 CD   │ Ani Wijaya    │ 15x          │ Rp 8.200.000   │ Gold     │ Aktif      │
│ AD 9012 EF   │ Joko Susilo   │ 8x           │ Rp 4.800.000   │ Silver   │ Follow-up  │
└──────────────┴───────────────┴──────────────┴────────────────┴──────────┴────────────┘
```

### Query 2: Riwayat Kedatangan Per Pelanggan

```sql
SELECT 
    kedatangan_ke,
    tanggal_datang,
    jarak_hari,
    total_transaksi,
    jumlah_item,
    status_garansi_display
FROM view_riwayat_kedatangan_detail
WHERE no_pelanggan = 'AD 1234 AB'
ORDER BY kedatangan_ke DESC;
```

**Output:**
```
┌─────────────┬──────────────┬────────────┬─────────────┬────────┬──────────────┐
│ Kedatangan  │ Tanggal      │ Jarak Hari │ Total       │ Item   │ Garansi      │
├─────────────┼──────────────┼────────────┼─────────────┼────────┼──────────────┤
│ 5           │ 2025-11-03   │ 19 hari    │ Rp 750.000  │ 4      │ Aktif        │
│ 4           │ 2025-10-15   │ 28 hari    │ Rp 650.000  │ 3      │ Expired      │
│ 3           │ 2025-09-17   │ 32 hari    │ Rp 800.000  │ 5      │ Expired      │
│ 2           │ 2025-08-16   │ 45 hari    │ Rp 500.000  │ 2      │ Expired      │
│ 1           │ 2025-07-02   │ 0 hari     │ Rp 600.000  │ 3      │ Expired      │
└─────────────┴──────────────┴────────────┴─────────────┴────────┴──────────────┘
```

### Query 3: Pelanggan Perlu Follow-up

```sql
SELECT 
    nopelanggan,
    namapelanggan,
    telephone,
    hari_tidak_datang,
    total_kunjungan,
    prioritas
FROM view_pelanggan_perlu_followup
WHERE prioritas = 'Urgent'
ORDER BY hari_tidak_datang DESC
LIMIT 20;
```

### Query 4: Analisis Pola Kunjungan

```sql
SELECT 
    kedatangan_ke,
    COUNT(*) AS jumlah_pelanggan,
    AVG(jarak_hari) AS rata2_jarak_hari,
    AVG(total_transaksi) AS rata2_transaksi,
    MIN(jarak_hari) AS jarak_tercepat,
    MAX(jarak_hari) AS jarak_terlama
FROM master_kedatangan_pelanggan
WHERE kedatangan_ke > 1
GROUP BY kedatangan_ke
ORDER BY kedatangan_ke;
```

**Output:**
```
┌─────────────┬──────────────┬─────────────────┬─────────────────┐
│ Kunjungan   │ Jumlah       │ Rata² Jarak     │ Rata² Transaksi │
├─────────────┼──────────────┼─────────────────┼─────────────────┤
│ 2           │ 150          │ 35 hari         │ Rp 550.000      │
│ 3           │ 120          │ 32 hari         │ Rp 600.000      │
│ 4           │ 95           │ 28 hari         │ Rp 650.000      │
│ 5           │ 75           │ 25 hari         │ Rp 700.000      │
└─────────────┴──────────────┴─────────────────┴─────────────────┘
```

---

## 💻 INTEGRASI PHP

### File: `dashboard_kedatangan_pelanggan.php`

```php
<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// Get summary data
$query_summary = "SELECT 
    COUNT(DISTINCT no_pelanggan) as total_pelanggan,
    COUNT(*) as total_kedatangan,
    SUM(total_transaksi) as total_pendapatan,
    AVG(jarak_hari) as rata2_jarak_kunjungan,
    SUM(CASE WHEN DATEDIFF(CURDATE(), tanggal_datang) <= 30 THEN 1 ELSE 0 END) as pelanggan_aktif,
    SUM(CASE WHEN DATEDIFF(CURDATE(), tanggal_datang) > 30 THEN 1 ELSE 0 END) as perlu_followup
FROM master_kedatangan_pelanggan";

$result = mysqli_query($koneksi, $query_summary);
$summary = mysqli_fetch_array($result);

// Get member distribution
$query_member = "SELECT 
    kategori_member,
    COUNT(*) as jumlah
FROM view_ringkasan_kedatangan_pelanggan
GROUP BY kategori_member";

$result_member = mysqli_query($koneksi, $query_member);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Kedatangan Pelanggan</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
</head>
<body>
    <div class="container">
        <h2>📊 Dashboard Kedatangan Pelanggan</h2>
        
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">Total Pelanggan</div>
                    <div class="panel-body">
                        <h3><?php echo number_format($summary['total_pelanggan']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-heading">Total Kedatangan</div>
                    <div class="panel-body">
                        <h3><?php echo number_format($summary['total_kedatangan']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-heading">Rata² Jarak Kunjungan</div>
                    <div class="panel-body">
                        <h3><?php echo number_format($summary['rata2_jarak_kunjungan'], 0); ?> hari</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-warning">
                    <div class="panel-heading">Perlu Follow-up</div>
                    <div class="panel-body">
                        <h3><?php echo number_format($summary['perlu_followup']); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Member Distribution -->
        <div class="panel panel-default">
            <div class="panel-heading"><h4>Distribusi Member</h4></div>
            <div class="panel-body">
                <div class="row text-center">
                    <?php while($row = mysqli_fetch_array($result_member)): ?>
                    <div class="col-md-3">
                        <h2><?php echo $row['kategori_member']; ?></h2>
                        <h3><?php echo $row['jumlah']; ?> pelanggan</h3>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        
        <!-- Tabel Data Pelanggan -->
        <div class="panel panel-default">
            <div class="panel-heading"><h4>Data Pelanggan</h4></div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No. Pelanggan</th>
                            <th>Nama</th>
                            <th>Jumlah Kunjungan</th>
                            <th>Total Transaksi</th>
                            <th>Terakhir Datang</th>
                            <th>Status</th>
                            <th>Member</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM view_ringkasan_kedatangan_pelanggan ORDER BY jumlah_kedatangan DESC LIMIT 50";
                        $result = mysqli_query($koneksi, $query);
                        while($row = mysqli_fetch_array($result)):
                        ?>
                        <tr>
                            <td><?php echo $row['nopelanggan']; ?></td>
                            <td><?php echo $row['namapelanggan']; ?></td>
                            <td><?php echo $row['jumlah_kedatangan']; ?>x</td>
                            <td>Rp <?php echo number_format($row['total_nilai_transaksi'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_terakhir_datang'])); ?></td>
                            <td>
                                <?php if($row['status_pelanggan'] == 'Aktif'): ?>
                                    <span class="label label-success">Aktif</span>
                                <?php elseif($row['status_pelanggan'] == 'Perlu Follow-up'): ?>
                                    <span class="label label-warning">Follow-up</span>
                                <?php else: ?>
                                    <span class="label label-danger">Urgent</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['kategori_member']; ?></td>
                            <td>
                                <a href="detail_kedatangan.php?nopelanggan=<?php echo $row['nopelanggan']; ?>" 
                                   class="btn btn-xs btn-info">
                                    <i class="fa fa-eye"></i> Detail
                                </a>
                                <?php if($row['lama_tidak_datang_hari'] > 30): ?>
                                <a href="kirim_wa.php?nopelanggan=<?php echo $row['nopelanggan']; ?>" 
                                   class="btn btn-xs btn-success">
                                    <i class="fa fa-whatsapp"></i> WA
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
```

### File: `detail_kedatangan.php`

```php
<?php
session_start();
include "../config/koneksi.php";

$nopelanggan = $_GET['nopelanggan'] ?? '';

// Get pelanggan info
$query = "SELECT * FROM view_ringkasan_kedatangan_pelanggan WHERE nopelanggan = '$nopelanggan'";
$result = mysqli_query($koneksi, $query);
$pelanggan = mysqli_fetch_array($result);

// Get riwayat kedatangan
$query_riwayat = "SELECT * FROM view_riwayat_kedatangan_detail WHERE no_pelanggan = '$nopelanggan' ORDER BY kedatangan_ke DESC";
$result_riwayat = mysqli_query($koneksi, $query_riwayat);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Kedatangan - <?php echo $pelanggan['namapelanggan']; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
</head>
<body>
    <div class="container">
        <h2>Detail Kedatangan Pelanggan</h2>
        
        <!-- Info Pelanggan -->
        <div class="panel panel-primary">
            <div class="panel-heading"><h4><?php echo $pelanggan['namapelanggan']; ?></h4></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>No. Pelanggan:</strong> <?php echo $pelanggan['nopelanggan']; ?></p>
                        <p><strong>Domisili:</strong> <?php echo $pelanggan['domisili']; ?></p>
                        <p><strong>Telepon:</strong> <?php echo $pelanggan['telephone']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Kunjungan:</strong> <?php echo $pelanggan['jumlah_kedatangan']; ?>x</p>
                        <p><strong>Total Transaksi:</strong> Rp <?php echo number_format($pelanggan['total_nilai_transaksi'], 0, ',', '.'); ?></p>
                        <p><strong>Member:</strong> <?php echo $pelanggan['kategori_member']; ?></p>
                        <p><strong>Status:</strong> <span class="label label-info"><?php echo $pelanggan['status_pelanggan']; ?></span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Riwayat Kedatangan -->
        <div class="panel panel-default">
            <div class="panel-heading"><h4>Riwayat Kedatangan</h4></div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Kunjungan Ke-</th>
                            <th>Tanggal</th>
                            <th>Jarak dari Sebelumnya</th>
                            <th>Total Transaksi</th>
                            <th>Jumlah Item</th>
                            <th>Garansi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_array($result_riwayat)): ?>
                        <tr>
                            <td><?php echo $row['kedatangan_ke']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_datang'])); ?></td>
                            <td><?php echo $row['jarak_kunjungan']; ?></td>
                            <td>Rp <?php echo number_format($row['total_transaksi'], 0, ',', '.'); ?></td>
                            <td><?php echo $row['jumlah_item']; ?> item</td>
                            <td><?php echo $row['status_garansi_display']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
```

---

## 📱 WHATSAPP AUTOMATION

### Contoh Kirim WhatsApp Otomatis

```php
<?php
// File: kirim_wa_otomatis.php

function kirimWhatsAppReminder($koneksi, $nopelanggan) {
    // Get data pelanggan
    $query = "SELECT * FROM view_pelanggan_perlu_followup WHERE nopelanggan = '$nopelanggan'";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_array($result);
    
    if(!$data) {
        return ['success' => false, 'message' => 'Pelanggan tidak ditemukan'];
    }
    
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $data['telephone']);
    if(substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Generate pesan
    $nama = $data['namapelanggan'];
    $hari = $data['hari_tidak_datang'];
    $kunjungan = $data['total_kunjungan'];
    
    $message = "🏍️ *Halo {$nama}!*\n\n";
    $message .= "Sudah {$hari} hari sejak servis terakhir Anda di Fit Motor.\n\n";
    $message .= "📊 *Riwayat Anda:*\n";
    $message .= "• Total kunjungan: {$kunjungan}x\n";
    $message .= "• Terakhir servis: " . date('d/m/Y', strtotime($data['terakhir_datang'])) . "\n\n";
    $message .= "⏰ *Waktunya servis motor Anda!*\n\n";
    $message .= "Kami tunggu kedatangan Anda di bengkel kami.\n\n";
    $message .= "Terima kasih,\n";
    $message .= "*Tim Fit Motor* 🔧";
    
    // Generate WhatsApp link
    $wa_link = "https://wa.me/{$phone}?text=" . urlencode($message);
    
    return [
        'success' => true,
        'wa_link' => $wa_link,
        'phone' => $phone,
        'message' => $message
    ];
}

// Contoh penggunaan
include "../config/koneksi.php";
$result = kirimWhatsAppReminder($koneksi, 'AD 1234 AB');

if($result['success']) {
    echo "<a href='{$result['wa_link']}' target='_blank' class='btn btn-success'>";
    echo "<i class='fa fa-whatsapp'></i> Kirim WhatsApp";
    echo "</a>";
}
?>
```

---

## 🎯 KESIMPULAN

### Keunggulan Sistem Baru

✅ **Fokus pada Loyalitas** - Menghargai pelanggan setia yang sering datang  
✅ **Tracking Lengkap** - Riwayat setiap kunjungan tersimpan detail  
✅ **Analisis Pola** - Bisa analisis pola kunjungan pelanggan  
✅ **Follow-up Sistematis** - Tahu pelanggan mana yang perlu difollow-up  
✅ **Otomatis** - Trigger bekerja di background, kasir tidak perlu input manual  
✅ **Performa Cepat** - Index yang optimal untuk query ribuan data  

### Next Steps

1. ✅ Install database
2. ✅ Refresh data awal (jika ada data lama)
3. ✅ Test trigger dengan transaksi baru
4. ✅ Implementasi dashboard PHP
5. ✅ Setup WhatsApp automation (opsional)

---

**Dibuat:** 3 November 2025  
**Versi:** 1.0  
**Status:** ✅ SIAP DIGUNAKAN
