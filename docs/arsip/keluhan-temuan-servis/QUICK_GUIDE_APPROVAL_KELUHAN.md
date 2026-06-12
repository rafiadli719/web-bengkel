# QUICK GUIDE: SISTEM APPROVAL KELUHAN

## 🚀 INSTALASI (5 Menit)

### Step 1: Update Database
```sql
-- Jalankan file SQL_UPDATE_KELUHAN_APPROVAL.sql
-- Atau copy-paste query berikut:

ALTER TABLE `tbmaster_keluhan` 
ADD COLUMN `status_approval` ENUM('pending','approved','rejected') DEFAULT 'approved',
ADD COLUMN `requested_by` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `requested_from` VARCHAR(10) DEFAULT NULL,
ADD COLUMN `approved_by` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `approved_at` DATETIME DEFAULT NULL,
ADD COLUMN `rejection_reason` TEXT DEFAULT NULL;

UPDATE `tbmaster_keluhan` SET `status_approval` = 'approved';
```

### Step 2: Verifikasi File
✅ File yang sudah diupdate/dibuat:
- `master-keluhan.php` - Halaman approval
- `modal-search-keluhan.php` - Modal dengan tombol tambah
- `ajax-submit-keluhan-baru.php` - Handler AJAX
- `SQL_UPDATE_KELUHAN_APPROVAL.sql` - Script SQL

### Step 3: Test
1. Login sebagai user cabang
2. Buka input servis → Cari keluhan
3. Klik "Tambah Keluhan Baru (Perlu Approval)"
4. Isi form & submit
5. Login sebagai user pusat
6. Buka Master Keluhan
7. Approve/Reject keluhan pending

---

## 👤 UNTUK USER CABANG

### Cara Mengajukan Keluhan Baru

```
1. Buka Input Servis
   ↓
2. Klik tombol 🔍 Cari Keluhan
   ↓
3. Klik "⚠️ Tambah Keluhan Baru (Perlu Approval)"
   ↓
4. Isi Form:
   • Nama Keluhan: "Kampas Kopling Aus"
   • Deskripsi: "Kampas kopling sudah tipis"
   • Kategori: "Transmisi"
   • Alasan: "Banyak customer mengeluhkan"
   ↓
5. Klik "📤 Ajukan Keluhan Baru"
   ↓
6. ✅ Notifikasi: "Keluhan berhasil diajukan"
   ↓
7. ⏳ Tunggu approval dari pusat
```

**Status Keluhan Anda**:
- 🟡 **Pending**: Menunggu approval
- 🟢 **Approved**: Sudah bisa digunakan
- 🔴 **Rejected**: Ditolak (lihat alasan)

---

## 👨‍💼 UNTUK USER PUSAT

### Cara Approve/Reject Keluhan

```
1. Buka Menu: Master Data → Master Keluhan
   ↓
2. Lihat keluhan dengan status "🟡 Pending"
   (Ditampilkan di baris paling atas dengan highlight kuning)
   ↓
3. Review:
   • Nama keluhan
   • Deskripsi & alasan pengajuan
   • Kategori
   • Diajukan oleh: Budi (Cabang 002)
   ↓
4. Putuskan:
   
   APPROVE ✅
   • Klik tombol ✓ (hijau)
   • Konfirmasi
   • Keluhan tersedia untuk semua cabang
   
   REJECT ❌
   • Klik tombol ✗ (merah)
   • Isi alasan penolakan
   • Konfirmasi
   • Cabang bisa lihat alasan
```

---

## 📊 STATUS & BADGE

| Status | Badge | Keterangan | Aksi |
|--------|-------|------------|------|
| 🟡 Pending | `label-warning` | Menunggu approval | ✓ Approve / ✗ Reject |
| 🟢 Approved | `label-success` | Sudah diapprove | 🗑 Hapus |
| 🔴 Rejected | `label-danger` | Ditolak | ℹ️ Lihat Alasan |

---

## 🔍 QUERY CEPAT

### Lihat Keluhan Pending
```sql
SELECT * FROM tbmaster_keluhan 
WHERE status_approval = 'pending' 
ORDER BY created_at DESC;
```

### Approve Keluhan Manual
```sql
UPDATE tbmaster_keluhan 
SET status_approval = 'approved',
    approved_by = 'Admin Pusat',
    approved_at = NOW()
WHERE id = 15;
```

### Reject Keluhan Manual
```sql
UPDATE tbmaster_keluhan 
SET status_approval = 'rejected',
    approved_by = 'Admin Pusat',
    approved_at = NOW(),
    rejection_reason = 'Keluhan terlalu umum, perlu lebih spesifik'
WHERE id = 16;
```

### Reset ke Approved (Data Lama)
```sql
UPDATE tbmaster_keluhan 
SET status_approval = 'approved'
WHERE status_approval IS NULL;
```

---

## ⚠️ TROUBLESHOOTING

### ❌ Keluhan tidak muncul di modal search

**Solusi**:
```sql
-- Pastikan status approved
UPDATE tbmaster_keluhan 
SET status_approval = 'approved' 
WHERE kode_keluhan = 'KEL001';
```

### ❌ Error kolom tidak ada

**Solusi**: Jalankan `SQL_UPDATE_KELUHAN_APPROVAL.sql`

### ❌ Tombol approve tidak muncul

**Cek**: Status harus 'pending'

---

## 📋 CHECKLIST IMPLEMENTASI

### Database
- [ ] Jalankan SQL update
- [ ] Verifikasi kolom baru ada
- [ ] Update data existing ke 'approved'

### File PHP
- [ ] Upload `master-keluhan.php` (updated)
- [ ] Upload `modal-search-keluhan.php` (updated)
- [ ] Upload `ajax-submit-keluhan-baru.php` (new)

### Testing
- [ ] Test pengajuan keluhan baru (cabang)
- [ ] Test approve keluhan (pusat)
- [ ] Test reject keluhan (pusat)
- [ ] Test filter di modal search
- [ ] Test lihat alasan reject

### User Training
- [ ] Brief user cabang cara mengajukan
- [ ] Brief user pusat cara approve/reject
- [ ] Dokumentasi dibagikan

---

## 💡 TIPS CEPAT

### Untuk Cabang
✅ Nama keluhan harus spesifik  
✅ Jelaskan alasan dengan detail  
✅ Cek dulu apakah sudah ada  

### Untuk Pusat
✅ Review dengan teliti  
✅ Berikan alasan reject yang jelas  
✅ Proses keluhan pending secepat mungkin  

---

## 📞 SUPPORT

**Dokumentasi Lengkap**: `DOKUMENTASI_APPROVAL_KELUHAN.md`

**File Penting**:
- `SQL_UPDATE_KELUHAN_APPROVAL.sql` - Update database
- `master-keluhan.php` - Halaman approval
- `ajax-submit-keluhan-baru.php` - Handler submit

**Kontak**: IT Support / Developer

---

**Update**: 10 November 2025  
**Version**: 1.0
