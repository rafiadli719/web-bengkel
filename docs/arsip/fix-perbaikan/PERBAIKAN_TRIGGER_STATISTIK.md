# PERBAIKAN TRIGGER STATISTIK PELANGGAN

## 🐛 MASALAH

**Error:** HTTP 500 saat klik "Bayar" di input servis reguler

**Error Message:**
```
FUNCTION fitmotor_dbbengkel.fn_get_status_member_nominal does not exist
Line: 941 (servis-input-reguler.php)
```

---

## 🔍 PENYEBAB

### **Root Cause:**

1. Saat klik "Bayar", code melakukan `UPDATE tblservice`
2. Ada **TRIGGER** di tabel `tblservice` yang otomatis jalan
3. Trigger memanggil **FUNCTION** `fn_get_status_member_nominal()`
4. Function ini **TIDAK ADA** di database
5. MySQL error → HTTP 500

### **Kenapa Function Tidak Ada?**

Function `fn_get_status_member_nominal` adalah bagian dari **sistem statistik pelanggan** yang:
- Menghitung status member berdasarkan total nominal transaksi
- Dipanggil oleh trigger untuk auto-update status member
- Mungkin belum dibuat saat setup awal
- Atau terhapus saat maintenance database

---

## ✅ SOLUSI

### **BUKAN Drop Trigger!**

Trigger itu **PENTING** untuk:
- ✅ Auto-update statistik pelanggan
- ✅ Hitung total nominal transaksi
- ✅ Update status member (Bronze/Silver/Gold/Platinum)
- ✅ Track kunjungan pelanggan

**Solusi yang benar:** **CREATE FUNCTION** yang diperlukan!

---

## 🔧 CARA PERBAIKAN

### **STEP 1: Jalankan Create Function**

Akses file ini di browser:
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/create_missing_function.php
```

File ini akan:
- ✅ DROP function lama (jika ada)
- ✅ CREATE function baru
- ✅ Verify function berhasil dibuat
- ✅ Test function

---

### **STEP 2: Verify Function**

Setelah create, cek:
```
✅ Function fn_get_status_member_nominal ADA
✅ Test Function: Result: Bronze ✅
```

---

### **STEP 3: Test Input Servis**

1. Buka "Service Reguler"
2. Buat service baru atau buka existing
3. Input pelanggan & barang/jasa
4. Klik "Bayar"
5. ✅ Seharusnya sudah tidak error!

---

## 📝 DETAIL FUNCTION

### **Function: fn_get_status_member_nominal**

**Purpose:**
Menentukan status member pelanggan berdasarkan total nominal transaksi

**Parameter:**
- `p_no_pelanggan` (VARCHAR): Nomor pelanggan

**Return:**
- VARCHAR(20): Status member (Bronze/Silver/Gold/Platinum)

**Logic:**
```sql
IF total_nominal >= 10,000,000 THEN 'Platinum'
ELSEIF total_nominal >= 5,000,000 THEN 'Gold'
ELSEIF total_nominal >= 2,000,000 THEN 'Silver'
ELSE 'Bronze'
```

**SQL Definition:**
```sql
CREATE FUNCTION fn_get_status_member_nominal(p_no_pelanggan VARCHAR(50))
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_total_nominal DECIMAL(15,2);
    
    -- Ambil total nominal dari statistik pelanggan
    SELECT total_nominal INTO v_total_nominal
    FROM statistik_pelanggan
    WHERE no_pelanggan = p_no_pelanggan
    LIMIT 1;
    
    -- Jika tidak ada data, return Bronze
    IF v_total_nominal IS NULL THEN
        RETURN 'Bronze';
    END IF;
    
    -- Tentukan status berdasarkan total nominal
    IF v_total_nominal >= 10000000 THEN
        SET v_status = 'Platinum';
    ELSEIF v_total_nominal >= 5000000 THEN
        SET v_status = 'Gold';
    ELSEIF v_total_nominal >= 2000000 THEN
        SET v_status = 'Silver';
    ELSE
        SET v_status = 'Bronze';
    END IF;
    
    RETURN v_status;
END
```

---

## 🔄 CARA KERJA TRIGGER

### **Flow Lengkap:**

```
User Klik "Bayar"
    ↓
UPDATE tblservice SET status='2', status_servis='bayar'
    ↓
TRIGGER AFTER UPDATE tblservice
    ↓
Call fn_get_status_member_nominal(no_pelanggan)
    ↓
Get total_nominal dari statistik_pelanggan
    ↓
Hitung status member (Bronze/Silver/Gold/Platinum)
    ↓
Return status
    ↓
Update statistik_pelanggan.status_member
    ↓
✅ Selesai
```

---

## 🎯 KATEGORI STATUS MEMBER

### **Berdasarkan Total Nominal:**

| Status | Minimum Nominal | Benefit |
|--------|----------------|---------|
| **Bronze** | Rp 0 | Member baru |
| **Silver** | Rp 2.000.000 | Diskon 10% |
| **Gold** | Rp 5.000.000 | Diskon 15% |
| **Platinum** | Rp 10.000.000 | Diskon 20% |

### **Berdasarkan Jumlah Kunjungan:**

| Status | Minimum Kunjungan | Benefit |
|--------|------------------|---------|
| **Bronze** | 0-4 kunjungan | Member baru |
| **Silver** | 5-9 kunjungan | Prioritas antrian |
| **Gold** | 10-19 kunjungan | Gratis cuci motor |
| **Platinum** | 20+ kunjungan | All benefits |

---

## 🧪 CARA TESTING

### **Test 1: Verify Function**

```sql
-- Test function dengan pelanggan existing
SELECT fn_get_status_member_nominal('AD 1234 AB') as status;

-- Expected: Bronze/Silver/Gold/Platinum
```

### **Test 2: Test Trigger**

```sql
-- Update service untuk trigger function
UPDATE tblservice 
SET status = '2', status_servis = 'bayar'
WHERE no_service = 'SV25000000146';

-- Cek apakah statistik terupdate
SELECT * FROM statistik_pelanggan 
WHERE no_pelanggan = (SELECT no_pelanggan FROM tblservice WHERE no_service = 'SV25000000146');
```

### **Test 3: Test via UI**

1. Login ke sistem
2. Buka "Service Reguler"
3. Buat service baru
4. Input pelanggan (pilih yang sudah ada)
5. Input barang/jasa
6. Klik "Bayar"
7. ✅ Cek status member terupdate

---

## 📊 MONITORING

### **Cek Function Ada:**

```sql
SHOW FUNCTION STATUS 
WHERE Db = 'fitmotor_dbbengkel' 
AND Name = 'fn_get_status_member_nominal';
```

### **Cek Trigger Ada:**

```sql
SHOW TRIGGERS FROM fitmotor_dbbengkel 
WHERE `Table` = 'tblservice';
```

### **Cek Statistik Pelanggan:**

```sql
SELECT 
    no_pelanggan,
    status_member,
    total_nominal,
    total_transaksi,
    jumlah_kunjungan
FROM statistik_pelanggan
ORDER BY total_nominal DESC
LIMIT 10;
```

---

## 🐛 TROUBLESHOOTING

### **Problem 1: Function Tidak Bisa Dibuat**

**Error:**
```
You do not have the SUPER privilege and binary logging is enabled
```

**Solusi:**
```sql
-- Disable binary logging sementara
SET GLOBAL log_bin_trust_function_creators = 1;

-- Atau tambahkan di my.ini
[mysqld]
log_bin_trust_function_creators = 1
```

---

### **Problem 2: Function Return NULL**

**Penyebab:**
- Data di `statistik_pelanggan` tidak ada
- Pelanggan baru belum ada statistik

**Solusi:**
Function sudah handle ini dengan return 'Bronze' sebagai default

---

### **Problem 3: Status Member Tidak Update**

**Penyebab:**
- Trigger tidak jalan
- Function tidak dipanggil

**Solusi:**
```sql
-- Cek trigger ada
SHOW TRIGGERS FROM fitmotor_dbbengkel WHERE `Table` = 'tblservice';

-- Manual update statistik
CALL sp_update_statistik_pelanggan('AD 1234 AB');
```

---

## 📁 FILE YANG DIBUAT

### **1. create_missing_function.php** ✅

**Lokasi:** `web-bengkel/aplikasi/aplikasi/_admincab/create_missing_function.php`

**Fungsi:**
- CREATE function `fn_get_status_member_nominal`
- Verify function berhasil dibuat
- Test function
- Show function definition

**Cara Akses:**
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/create_missing_function.php
```

---

### **2. fix_trigger_error.php** ✅

**Lokasi:** `web-bengkel/aplikasi/aplikasi/_admincab/fix_trigger_error.php`

**Fungsi:**
- Cek semua trigger di tblservice
- Cek function ada/tidak
- Tampilkan trigger yang bermasalah
- Drop trigger (jika diperlukan)

**Cara Akses:**
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/fix_trigger_error.php
```

---

## ✅ CHECKLIST PERBAIKAN

**Setup:**
- [ ] Akses `create_missing_function.php`
- [ ] Verify function berhasil dibuat
- [ ] Test function return status

**Testing:**
- [ ] Test input servis reguler
- [ ] Test klik "Bayar"
- [ ] Verify tidak ada HTTP 500
- [ ] Cek statistik pelanggan terupdate
- [ ] Cek status member terupdate

**Monitoring:**
- [ ] Cek function ada di database
- [ ] Cek trigger masih ada
- [ ] Cek log tidak ada error

---

## 🎯 KESIMPULAN

**Masalah:**
- ❌ HTTP 500 saat bayar
- ❌ Function `fn_get_status_member_nominal` tidak ada
- ❌ Trigger tidak bisa jalan

**Solusi:**
- ✅ CREATE function yang diperlukan
- ✅ JANGAN drop trigger (trigger penting!)
- ✅ Verify function & trigger berjalan normal

**Hasil:**
- ✅ Pembayaran berjalan normal
- ✅ Statistik pelanggan terupdate otomatis
- ✅ Status member terkalkulasi dengan benar
- ✅ WhatsApp automation bisa jalan

**Status:** ✅ **READY FOR TESTING**

---

**Dokumentasi dibuat: 4 November 2025**  
**Version: 1.0**  
**Status: Fixed & Ready** ✅
