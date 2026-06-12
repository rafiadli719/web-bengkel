# 🚀 PANDUAN AUTO KALKULASI JARAK

**Halaman:** Jadwal Penjemputan Motor
**File:** `servis-reguler-jemput.php`
**Fitur:** Auto-calculate jarak dari Bengkel Cabang ke Lokasi Customer

---

## ✅ FITUR SUDAH DIIMPLEMENTASIKAN!

Sistem **SUDAH BISA** menghitung jarak otomatis menggunakan **Haversine Formula** (GPS coordinates).

### Cara Kerja:

```
┌─────────────────────────────────────────────────────────────┐
│  1. User pilih kendaraan customer                           │
│     ↓                                                        │
│  2. Sistem ambil GPS Cabang (lat_cabang, long_cabang)      │
│     ↓                                                        │
│  3. Sistem ambil GPS Customer (klat, klong dari pelanggan)  │
│     ↓                                                        │
│  4. JavaScript hitung jarak dengan Haversine Formula        │
│     ↓                                                        │
│  5. AUTO-FILL field "Jarak Penjemputan"                    │
│     ↓                                                        │
│  6. AUTO-CALCULATE tarif berdasarkan kondisi motor          │
└─────────────────────────────────────────────────────────────┘
```

---

## 📋 SYARAT AGAR AUTO-KALKULASI BERFUNGSI

### Syarat 1: Cabang Punya Koordinat GPS ✅

**Cara Setting:**
1. Buka **Master Cabang** → Klik **Edit**
2. Isi **Google Maps Link** cabang:
   ```
   https://www.google.com/maps/@-6.2088,106.8456,17z
   ```
3. **Otomatis:** Latitude & Longitude ter-fill
4. Klik **Save**

**Verifikasi:**
- Badge di Master Cabang berubah: "No GPS" → "GPS OK" ✅

### Syarat 2: Customer Punya Koordinat GPS ✅

**Cara Setting:**
Ada 2 cara:

**Cara A: Via Form Jadwal Penjemputan** (Recommended)
1. Buka form Jadwal Penjemputan
2. Pilih kendaraan customer
3. Isi **Google Maps Link Lokasi Penjemputan**:
   ```
   https://www.google.com/maps/@-6.1754,106.8272,17z
   ```
4. Upload **Foto Rumah Pelanggan**
5. Submit → Otomatis tersimpan ke data pelanggan

**Cara B: Via Master Pelanggan** (Manual)
1. Edit data pelanggan di Master Pelanggan
2. Isi koordinat GPS customer (klat, klong)

---

## 🎯 DEMO PENGGUNAAN

### Scenario 1: Auto-Calculate (GPS Sudah Ada)

```
STEP 1: Pilih Kendaraan
┌──────────────────────────────────────────┐
│ No. Polisi: [B1234XYZ] [🔍 Cari]       │
└──────────────────────────────────────────┘
     ↓ Klik "Cari" → Pilih kendaraan

STEP 2: Data Auto-Fill
┌──────────────────────────────────────────┐
│ Nama: Budi Santoso                       │
│ Alamat: Jl. Sudirman No. 123, Jakarta    │
│ 📍 Lihat Maps                            │
└──────────────────────────────────────────┘
     ↓ Sistem ambil GPS

STEP 3: Jarak & Tarif AUTO-CALCULATE! ✨
┌──────────────────────────────────────────┐
│ Kondisi Motor:                           │
│ ○ Motor Jalan  ● Motor Mogok            │
│                                          │
│ Jarak Penjemputan: [3.5] KM 🔁 Hitung   │
│                                          │
│ ✅ Tarif Motor Jalan: Rp 12,000         │
│ ✅ Tarif Motor Mogok: Rp 17,000         │
└──────────────────────────────────────────┘
     ↓ Otomatis ter-hitung!

STEP 4: Submit
[✓ Jadwalkan Penjemputan & Lanjut ke Input Servis]
```

### Scenario 2: Manual Input (GPS Belum Ada)

```
STEP 1: Pilih Kendaraan
┌──────────────────────────────────────────┐
│ No. Polisi: [B5678ABC] [🔍 Cari]       │
└──────────────────────────────────────────┘
     ↓ Customer belum punya GPS

STEP 2: Input Manual
┌──────────────────────────────────────────┐
│ Jarak Penjemputan: [4.2] KM 🔁 Hitung   │
│                      ↑                   │
│                 Isi manual               │
└──────────────────────────────────────────┘
     ↓ Klik "Hitung"

STEP 3: Tarif Calculate
┌──────────────────────────────────────────┐
│ ✅ Tarif Motor Jalan: Rp 14,000         │
│ ✅ Tarif Motor Mogok: Rp 20,000         │
└──────────────────────────────────────────┘

STEP 4: Input Google Maps (Untuk Next Time)
┌──────────────────────────────────────────┐
│ Google Maps Link:                        │
│ [https://maps.google.com/...] 📍        │
│                                          │
│ Upload Foto Rumah: [Choose File]        │
└──────────────────────────────────────────┘
     ↓ Tersimpan untuk next time
```

---

## 🔧 KODE YANG SUDAH DIIMPLEMENTASIKAN

### 1. JavaScript: Haversine Formula (Line 678)

```javascript
// Library: assets/js/haversine-distance.js
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth radius in KM
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon/2) * Math.sin(dLon/2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; // Distance in KM
}
```

### 2. JavaScript: Auto-Calculate saat Pilih Kendaraan (Line 794-805)

```javascript
// Saat customer dipilih
fetchCustomerData(nopol) {
    // ... fetch data customer ...

    // Auto-calculate distance
    if (branchLat && branchLng && customerLat && customerLng) {
        var distance = calculateDistance(
            branchLat, branchLng,
            customerLat, customerLng
        );

        // Update field jarak
        $('#txtjarak').val(distance.toFixed(1)); // 3.5 KM

        // Auto-calculate tarif
        hitungTarif();
    }
}
```

### 3. PHP: Load GPS Cabang (Line 32-46)

```php
// Ambil koordinat cabang
$stmt = mysqli_prepare($koneksi, "SELECT
    nama_cabang, tipe_cabang,
    alamat_cabang, google_maps_cabang,
    lat_cabang, long_cabang  // ← Koordinat GPS Cabang
FROM tbcabang WHERE kode_cabang = ?");

$lat_cabang = $branch_data["lat_cabang"] ?? "";
$long_cabang = $branch_data["long_cabang"] ?? "";
```

### 4. PHP: Pass GPS ke JavaScript (Line 680-684)

```php
<script>
    // Pass koordinat cabang ke JavaScript
    var branchLat = <?php echo !empty($lat_cabang) ? $lat_cabang : 'null'; ?>;
    var branchLng = <?php echo !empty($long_cabang) ? $long_cabang : 'null'; ?>;
</script>
```

### 5. PHP: Save ke Database (Line 171-173, 218-220)

```php
// Extract dari form
$kondisi_motor = mysqli_real_escape_string($koneksi, $_POST['txtkondisi'] ?? 'jalan');
$jarak_jemput = mysqli_real_escape_string($koneksi, $_POST['txtjarak'] ?? '0');
$tarif_jemput = mysqli_real_escape_string($koneksi, $_POST['txttarif'] ?? '0');

// Save ke database
INSERT INTO tblservice (
    ..., kondisi_motor, jarak_jemput, tarif_jemput
) VALUES (
    ..., ?, ?, ?
);
```

---

## 📊 FORMULA TARIF

### Tarif Motor Jalan (Bisa Dikendarai)
```
Jarak 0-1 km     : Gratis (Rp 0)
Jarak 1.5 km     : Rp 8,000
Jarak 2.0 km     : Rp 8,000 + Rp 2,000 = Rp 10,000
Jarak 2.5 km     : Rp 8,000 + Rp 4,000 = Rp 12,000
Jarak 3.0 km     : Rp 8,000 + Rp 6,000 = Rp 14,000

Formula:
- Base (1.5km): Rp 8,000
- Per 0.5km tambahan: +Rp 2,000
```

### Tarif Motor Mogok (Tidak Bisa Jalan)
```
Jarak 0-1 km     : Gratis (Rp 0)
Jarak 1.5 km     : Rp 11,000
Jarak 2.0 km     : Rp 11,000 + Rp 3,000 = Rp 14,000
Jarak 2.5 km     : Rp 11,000 + Rp 6,000 = Rp 17,000
Jarak 3.0 km     : Rp 11,000 + Rp 9,000 = Rp 20,000

Formula:
- Base (1.5km): Rp 11,000
- Per 0.5km tambahan: +Rp 3,000
```

### Contoh Kalkulasi Real

**Contoh 1: Bengkel Pusat ke Customer A**
```
Bengkel: Lat -6.2088, Lng 106.8456 (Jakarta Pusat)
Customer: Lat -6.1754, Lng 106.8272 (Jakarta Barat)
Jarak: ~4.2 KM

Motor Jalan: Rp 8,000 + (6 x Rp 2,000) = Rp 20,000
Motor Mogok: Rp 11,000 + (6 x Rp 3,000) = Rp 29,000
```

**Contoh 2: Bengkel Cabang ke Customer B**
```
Bengkel: Lat -6.3025, Lng 106.8954 (Depok)
Customer: Lat -6.2800, Lng 106.8920 (Depok)
Jarak: ~2.6 KM

Motor Jalan: Rp 8,000 + (3 x Rp 2,000) = Rp 14,000
Motor Mogok: Rp 11,000 + (3 x Rp 3,000) = Rp 20,000
```

---

## 🐛 TROUBLESHOOTING

### Problem 1: Jarak Tidak Auto-Calculate

**Penyebab:**
- Cabang belum punya GPS
- Customer belum punya GPS

**Solusi:**
```
1. Cek Master Cabang
   - Badge "GPS OK"? ✅ OK
   - Badge "No GPS"? ❌ Edit cabang, input Google Maps

2. Cek Customer
   - Buka browser Console (F12)
   - Lihat error: "branchLat is null" atau "customerLat is null"
   - Input Google Maps customer via form penjemputan
```

### Problem 2: Koordinat Tidak Ter-Extract dari Google Maps

**Format URL yang Valid:**
```
✅ https://www.google.com/maps/@-6.2088,106.8456,17z
✅ https://www.google.com/maps?q=-6.2088,106.8456
✅ https://maps.google.com/maps?ll=-6.2088,106.8456

❌ https://goo.gl/maps/xxxxx (shortened URL)
❌ https://www.google.com/maps/place/Jakarta (place name)
```

**Cara Dapat URL yang Benar:**
```
1. Buka Google Maps
2. Klik kanan di lokasi yang diinginkan
3. Klik "What's here?"
4. Copy koordinat atau URL dari address bar
```

### Problem 3: Tarif Tidak Sesuai

**Debug:**
```javascript
// Buka Console (F12), test manual:
var jarak = 3.5; // KM
var tarifJalan = 8000 + (Math.ceil((jarak - 1.5) / 0.5) * 2000);
var tarifMogok = 11000 + (Math.ceil((jarak - 1.5) / 0.5) * 3000);

console.log("Tarif Jalan: Rp " + tarifJalan);
console.log("Tarif Mogok: Rp " + tarifMogok);
```

---

## 📝 CHECKLIST IMPLEMENTASI

### Setup Awal (One-time)
- [ ] Import database: `UPDATE_DATABASE_JARAK_OTOMATIS.sql`
- [ ] Verify columns exist: `tbcabang` (lat_cabang, long_cabang)
- [ ] Verify columns exist: `tblservice` (kondisi_motor, jarak_jemput, tarif_jemput)
- [ ] File exists: `assets/js/haversine-distance.js`

### Setup Cabang (One-time per cabang)
- [ ] Buka Master Cabang → Edit
- [ ] Input Google Maps Link cabang
- [ ] Verify Latitude & Longitude auto-fill
- [ ] Save
- [ ] Verify badge "GPS OK"

### Setup Customer (Per customer, bisa via form)
- [ ] Option A: Via form penjemputan (auto-save)
  - [ ] Input Google Maps Link lokasi penjemputan
  - [ ] Upload foto rumah
  - [ ] Submit → Auto-save ke master pelanggan
- [ ] Option B: Via master pelanggan (manual)
  - [ ] Edit pelanggan
  - [ ] Input klat, klong

### Testing
- [ ] Test auto-calculate (pilih customer dengan GPS)
- [ ] Verify jarak auto-fill
- [ ] Verify tarif auto-calculate
- [ ] Test manual input (customer tanpa GPS)
- [ ] Verify tarif calculate after click "Hitung"
- [ ] Submit form
- [ ] Check database: jarak_jemput & tarif_jemput tersimpan

---

## 🎯 KESIMPULAN

### ✅ Fitur SUDAH BISA Kalkulasi Jarak Otomatis!

**Yang Sudah Diimplementasikan:**
1. ✅ Haversine Formula untuk hitung jarak GPS
2. ✅ Auto-fill jarak saat pilih kendaraan
3. ✅ Auto-calculate tarif berdasarkan kondisi motor
4. ✅ Save jarak & tarif ke database
5. ✅ Support manual input jika GPS belum ada

**Cara Mengaktifkan:**
1. ⚠️ Import database schema (2 menit)
2. ⚠️ Input GPS cabang di Master Cabang (3 menit)
3. ⚠️ Input GPS customer via form penjemputan (otomatis)
4. ✅ Auto-calculate langsung jalan!

**Benefit:**
- ⚡ Cepat: Tidak perlu input manual
- 🎯 Akurat: Menggunakan GPS real
- 💰 Transparan: Customer tahu tarif berdasarkan jarak real
- 📊 Traceable: Semua data tersimpan di database

---

**File ini:** `PANDUAN_AUTO_KALKULASI_JARAK.md`
**Developed by:** Claude Code
**Date:** 2025-10-06
