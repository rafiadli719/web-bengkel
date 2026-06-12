# QUICK START - DATABASE OPTIMIZATION

**Estimasi Waktu**: 5-10 menit
**Risk Level**: 🟢 LOW (auto backup)

---

## 🚀 LANGKAH CEPAT

### 1️⃣ Buka Browser
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/run_database_optimization.php
```

### 2️⃣ Check ✅ Semua Green

Pastikan muncul:
- ✅ SQL file found
- ✅ Database connection OK
- ✅ Backup directory exists

### 3️⃣ Klik "▶️ Start Optimization"

Tunggu sampai selesai (~5-10 menit)

### 4️⃣ Verify Results

**Good Result**:
- Success: >60
- Skipped: 10-20 (NORMAL)
- Errors: 0-5 (safe errors OK)

### 5️⃣ Test Aplikasi

Buka dan test:
```
_admincab/test_ajax_endpoints_temuan.html
```

Semua endpoint harus **SUCCESS** ✅

---

## ⚠️ CATATAN PENTING

**Error "Duplicate key name"** → Abaikan (NORMAL)
**Error "Already exists"** → Abaikan (NORMAL)
**Error "Cannot add FK"** → Perlu action (lihat troubleshooting)

**Backup otomatis** disimpan di folder `backups/`

---

## 🔄 ROLLBACK (Jika Perlu)

Via phpMyAdmin:
1. Import → Choose File
2. Pilih: `backups/backup_before_optimization_*.sql`
3. Go

---

## ✅ DONE!

Setelah optimization:
- ⚡ Query lebih cepat
- ✅ Data lebih konsisten
- 📊 Analytics ready

---

**Dokumentasi Lengkap**: `CARA_JALANKAN_OPTIMIZATION_PHP.md`
**Support**: Check log di `_admincab/database_optimization_log.txt`
