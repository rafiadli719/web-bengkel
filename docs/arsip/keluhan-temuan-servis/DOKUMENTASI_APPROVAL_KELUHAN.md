# DOKUMENTASI SISTEM APPROVAL KELUHAN

## 📋 OVERVIEW

Sistem approval keluhan memungkinkan cabang untuk mengajukan keluhan baru yang akan masuk ke master setelah mendapat persetujuan dari pusat. Ini memastikan kualitas dan konsistensi data keluhan di seluruh cabang.

---

## 🎯 TUJUAN

1. **Kontrol Kualitas**: Pusat dapat memverifikasi keluhan sebelum masuk ke master
2. **Konsistensi Data**: Menghindari duplikasi dan keluhan yang tidak relevan
3. **Audit Trail**: Melacak siapa yang mengajukan dan menyetujui keluhan
4. **Fleksibilitas**: Cabang tetap bisa mengajukan keluhan baru sesuai kebutuhan

---

## 🗄️ PERUBAHAN DATABASE

### Tabel: `tbmaster_keluhan`

**Kolom Baru yang Ditambahkan**:

```sql
status_approval ENUM('pending','approved','rejected') DEFAULT 'approved'
  - Status approval keluhan
  - pending: Menunggu approval pusat
  - approved: Sudah diapprove, bisa digunakan
  - rejected: Ditolak oleh pusat

requested_by VARCHAR(50)
  - Nama user yang mengajukan keluhan

requested_from VARCHAR(10)
  - Kode cabang yang mengajukan

approved_by VARCHAR(50)
  - Nama user pusat yang approve/reject

approved_at DATETIME
  - Tanggal & waktu approve/reject

rejection_reason TEXT
  - Alasan penolakan (wajib jika rejected)
```

### SQL Update

Jalankan file: `SQL_UPDATE_KELUHAN_APPROVAL.sql`

```sql
ALTER TABLE `tbmaster_keluhan` 
ADD COLUMN `status_approval` ENUM('pending','approved','rejected') DEFAULT 'approved',
ADD COLUMN `requested_by` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `requested_from` VARCHAR(10) DEFAULT NULL,
ADD COLUMN `approved_by` VARCHAR(50) DEFAULT NULL,
ADD COLUMN `approved_at` DATETIME DEFAULT NULL,
ADD COLUMN `rejection_reason` TEXT DEFAULT NULL;

-- Update data existing
UPDATE `tbmaster_keluhan` SET `status_approval` = 'approved';
```

---

## 🔄 ALUR PROSES

### 1. Pengajuan Keluhan Baru (Cabang)

```
User Cabang
    │
    ▼
Buka Modal Search Keluhan
    │
    ▼
Klik "Tambah Keluhan Baru (Perlu Approval)"
    │
    ▼
Isi Form:
  - Nama Keluhan *
  - Deskripsi
  - Kategori *
  - Alasan Pengajuan *
    │
    ▼
Submit → AJAX ke ajax-submit-keluhan-baru.php
    │
    ▼
INSERT ke tbmaster_keluhan
  - status_approval = 'pending'
  - requested_by = nama_user
  - requested_from = kode_cabang
    │
    ▼
Notifikasi: "Keluhan berhasil diajukan, menunggu approval"
```

### 2. Approval Keluhan (Pusat)

```
User Pusat
    │
    ▼
Buka master-keluhan.php
    │
    ▼
Lihat Keluhan dengan Status "Pending"
(Ditampilkan di baris paling atas dengan highlight)
    │
    ├─ APPROVE ────────────────┐
    │                          │
    │                          ▼
    │                  UPDATE tbmaster_keluhan:
    │                    - status_approval = 'approved'
    │                    - approved_by = nama_user
    │                    - approved_at = NOW()
    │                          │
    │                          ▼
    │                  Keluhan tersedia untuk semua cabang
    │
    └─ REJECT ─────────────────┐
                               │
                               ▼
                       Input Alasan Penolakan *
                               │
                               ▼
                       UPDATE tbmaster_keluhan:
                         - status_approval = 'rejected'
                         - approved_by = nama_user
                         - approved_at = NOW()
                         - rejection_reason = alasan
                               │
                               ▼
                       Keluhan ditolak, tidak bisa digunakan
```

---

## 📁 FILE YANG DIMODIFIKASI/DIBUAT

### 1. **SQL_UPDATE_KELUHAN_APPROVAL.sql** (BARU)
- Script SQL untuk update struktur database
- Menambahkan kolom approval di tbmaster_keluhan

### 2. **master-keluhan.php** (DIMODIFIKASI)
- Tambah handler approval (btnapprove)
- Update tampilan tabel dengan kolom status & request by
- Tambah modal approve & reject
- Tambah JavaScript untuk handle approval

**Perubahan Utama**:
```php
// Handler Approval
if(isset($_POST['btnapprove'])) {
    $action = $_POST['action']; // 'approve' atau 'reject'
    
    if($action == 'approve') {
        // Update status menjadi approved
    } else if($action == 'reject') {
        // Update status menjadi rejected + alasan
    }
}

// Query dengan filter status_approval
ORDER BY CASE status_approval 
    WHEN 'pending' THEN 1 
    WHEN 'approved' THEN 2 
    WHEN 'rejected' THEN 3 
END
```

### 3. **modal-search-keluhan.php** (DIMODIFIKASI)
- Filter hanya keluhan approved: `status_approval='approved'`
- Tambah tombol "Tambah Keluhan Baru (Perlu Approval)"
- Tambah modal form tambah keluhan baru
- Tambah JavaScript handler submit AJAX

**Perubahan Utama**:
```php
// Filter hanya approved
WHERE status_aktif='1' AND status_approval='approved'

// Tombol tambah keluhan baru
<button onclick="tambahKeluhanBaru()">
    Tambah Keluhan Baru (Perlu Approval)
</button>
```

### 4. **ajax-submit-keluhan-baru.php** (BARU)
- Handler AJAX untuk submit keluhan baru
- Validasi input
- Check duplicate
- Generate kode keluhan otomatis
- Insert dengan status pending

---

## 🎨 TAMPILAN UI

### Master Keluhan (master-keluhan.php)

**Tabel dengan Status**:
```
┌────┬──────┬────────────────┬────────┬──────────┬─────────┬────────────┬──────┐
│ No │ Kode │ Nama Keluhan   │ Desk   │ Kategori │ Status  │ Request By │ Aksi │
├────┼──────┼────────────────┼────────┼──────────┼─────────┼────────────┼──────┤
│ 1  │ KEL015│ Kampas Aus    │ ...    │ Rem      │ Pending │ Budi (002) │ ✓ ✗  │ ← Highlight kuning
│ 2  │ KEL001│ Mesin Mati    │ ...    │ Mesin    │ Approved│ - (-)      │ 🗑   │
│ 3  │ KEL002│ Rem Blong     │ ...    │ Rem      │ Approved│ - (-)      │ 🗑   │
│ 4  │ KEL014│ Filter Kotor  │ ...    │ Mesin    │ Rejected│ Andi (003) │ ℹ️   │
└────┴──────┴────────────────┴────────┴──────────┴─────────┴────────────┴──────┘

Legend:
✓ = Approve
✗ = Reject
🗑 = Hapus
ℹ️ = Lihat Alasan Reject
```

**Badge Status**:
- 🟡 **Pending** (label-warning) - Menunggu approval
- 🟢 **Approved** (label-success) - Sudah diapprove
- 🔴 **Rejected** (label-danger) - Ditolak

### Modal Search Keluhan

**Footer dengan Tombol Baru**:
```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│  [⚠️ Tambah Keluhan Baru]  [Tutup]  [Input Manual]     │
│   (Perlu Approval)                                      │
└─────────────────────────────────────────────────────────┘
```

### Modal Tambah Keluhan Baru

```
┌─────────────────────────────────────────────────────────┐
│ ➕ Tambah Keluhan Baru  [⚠️ Perlu Approval Pusat]      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ ⚠️ Perhatian!                                           │
│ Keluhan baru yang Anda ajukan akan masuk ke sistem     │
│ setelah mendapat approval dari pusat.                   │
│                                                         │
│ Nama Keluhan *                                          │
│ [_____________________________]                         │
│                                                         │
│ Deskripsi                                               │
│ [_____________________________]                         │
│ [_____________________________]                         │
│                                                         │
│ Kategori *                                              │
│ [▼ Pilih Kategori          ]                            │
│                                                         │
│ Alasan Pengajuan *                                      │
│ [_____________________________]                         │
│                                                         │
│                      [Batal]  [📤 Ajukan Keluhan Baru]  │
└─────────────────────────────────────────────────────────┘
```

---

## 🔐 VALIDASI & BUSINESS RULES

### Pengajuan Keluhan Baru

✅ **Validasi**:
- Nama keluhan wajib diisi
- Kategori wajib dipilih
- Alasan pengajuan wajib diisi
- Nama keluhan tidak boleh duplikat (case-insensitive)

✅ **Auto-Generate**:
- Kode keluhan otomatis (KEL001, KEL002, ...)
- Status approval = 'pending'
- requested_by = nama user yang login
- requested_from = kode cabang user

### Approval Keluhan

✅ **Approve**:
- Update status_approval = 'approved'
- Set approved_by & approved_at
- Keluhan langsung tersedia untuk semua cabang

✅ **Reject**:
- Alasan penolakan wajib diisi
- Update status_approval = 'rejected'
- Set approved_by, approved_at, & rejection_reason
- Keluhan tidak bisa digunakan

### Filter di Modal Search

✅ **Hanya Approved**:
```sql
WHERE status_aktif='1' AND status_approval='approved'
```
- Cabang hanya bisa melihat keluhan yang sudah approved
- Keluhan pending/rejected tidak muncul di pilihan

---

## 📊 QUERY PENTING

### Lihat Keluhan Pending
```sql
SELECT * FROM tbmaster_keluhan 
WHERE status_approval = 'pending' 
AND status_aktif = '1'
ORDER BY created_at DESC;
```

### Lihat Keluhan Approved
```sql
SELECT * FROM tbmaster_keluhan 
WHERE status_approval = 'approved' 
AND status_aktif = '1'
ORDER BY nama_keluhan;
```

### Lihat Keluhan Rejected
```sql
SELECT * FROM tbmaster_keluhan 
WHERE status_approval = 'rejected' 
AND status_aktif = '1'
ORDER BY created_at DESC;
```

### Statistik Approval per Cabang
```sql
SELECT 
    requested_from AS cabang,
    COUNT(*) AS total_pengajuan,
    SUM(CASE WHEN status_approval='approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN status_approval='pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status_approval='rejected' THEN 1 ELSE 0 END) AS rejected
FROM tbmaster_keluhan
WHERE requested_from IS NOT NULL
GROUP BY requested_from;
```

---

## 🚀 CARA PENGGUNAAN

### Untuk User Cabang

1. **Buka Halaman Input Servis**
2. **Klik tombol cari keluhan** (ikon 🔍)
3. **Modal Search Keluhan terbuka**
4. **Jika keluhan tidak ada**, klik tombol **"Tambah Keluhan Baru (Perlu Approval)"**
5. **Isi form**:
   - Nama Keluhan (contoh: "Kampas Kopling Aus")
   - Deskripsi (opsional)
   - Kategori (pilih dari dropdown)
   - Alasan Pengajuan (jelaskan kenapa perlu ditambahkan)
6. **Klik "Ajukan Keluhan Baru"**
7. **Notifikasi muncul**: "Keluhan berhasil diajukan, menunggu approval"
8. **Tunggu approval dari pusat**

### Untuk User Pusat

1. **Buka Menu Master Data → Master Keluhan**
2. **Lihat keluhan dengan status "Pending"** (highlight kuning di baris atas)
3. **Review keluhan**:
   - Nama keluhan
   - Deskripsi & alasan pengajuan
   - Kategori
   - Diajukan oleh siapa & dari cabang mana
4. **Putuskan**:
   
   **APPROVE**:
   - Klik tombol ✓ (hijau)
   - Konfirmasi approve
   - Keluhan langsung tersedia untuk semua cabang
   
   **REJECT**:
   - Klik tombol ✗ (merah)
   - Isi alasan penolakan (wajib)
   - Konfirmasi reject
   - Keluhan ditolak, cabang bisa lihat alasan

---

## 💡 TIPS & BEST PRACTICES

### Untuk Cabang

✅ **DO**:
- Gunakan nama keluhan yang jelas dan spesifik
- Jelaskan alasan pengajuan dengan detail
- Cek dulu apakah keluhan serupa sudah ada
- Pilih kategori yang tepat

❌ **DON'T**:
- Mengajukan keluhan yang terlalu umum
- Duplikat keluhan yang sudah ada
- Alasan pengajuan tidak jelas

### Untuk Pusat

✅ **DO**:
- Review keluhan dengan teliti
- Berikan alasan reject yang jelas
- Approve keluhan yang relevan dan spesifik
- Komunikasikan dengan cabang jika perlu klarifikasi

❌ **DON'T**:
- Reject tanpa alasan yang jelas
- Approve keluhan duplikat
- Mengabaikan keluhan pending terlalu lama

---

## 📈 MONITORING & REPORTING

### Dashboard Approval (Rekomendasi)

**Untuk Pusat**:
- Total keluhan pending (perlu action)
- Total keluhan approved bulan ini
- Total keluhan rejected bulan ini
- Cabang dengan pengajuan terbanyak
- Rata-rata waktu approval

**Untuk Cabang**:
- Total keluhan yang diajukan
- Status approval (pending/approved/rejected)
- Keluhan yang ditolak + alasan
- History pengajuan

### Notifikasi (Rekomendasi Future)

- Email ke pusat saat ada keluhan baru
- Email ke cabang saat keluhan approved/rejected
- WhatsApp notification (opsional)
- Dashboard notification badge

---

## 🔧 TROUBLESHOOTING

### Keluhan tidak muncul di modal search

**Penyebab**: Status approval bukan 'approved'

**Solusi**: 
```sql
-- Cek status keluhan
SELECT kode_keluhan, nama_keluhan, status_approval 
FROM tbmaster_keluhan 
WHERE kode_keluhan = 'KEL001';

-- Jika perlu, update manual
UPDATE tbmaster_keluhan 
SET status_approval = 'approved' 
WHERE kode_keluhan = 'KEL001';
```

### Error saat submit keluhan baru

**Penyebab**: Kolom approval belum ada di database

**Solusi**: Jalankan `SQL_UPDATE_KELUHAN_APPROVAL.sql`

### Tombol approve/reject tidak muncul

**Penyebab**: Status bukan 'pending'

**Solusi**: Pastikan status_approval = 'pending'

---

## 📝 CHANGELOG

### Version 1.0 (10 November 2025)

**Added**:
- ✅ Sistem approval keluhan dari pusat
- ✅ Kolom approval di tbmaster_keluhan
- ✅ Modal tambah keluhan baru di search keluhan
- ✅ AJAX handler submit keluhan baru
- ✅ UI approval di master-keluhan.php
- ✅ Filter hanya approved di modal search
- ✅ Audit trail (requested_by, approved_by, timestamps)

**Modified**:
- 📝 master-keluhan.php - Tambah approval handler & UI
- 📝 modal-search-keluhan.php - Tambah tombol & modal
- 📝 tbmaster_keluhan - Tambah kolom approval

**Created**:
- 🆕 SQL_UPDATE_KELUHAN_APPROVAL.sql
- 🆕 ajax-submit-keluhan-baru.php
- 🆕 DOKUMENTASI_APPROVAL_KELUHAN.md

---

## 🎯 FUTURE ENHANCEMENTS

### Prioritas Tinggi
1. ✨ Notifikasi email/WhatsApp untuk approval
2. ✨ Dashboard monitoring approval
3. ✨ Bulk approve untuk multiple keluhan
4. ✨ Export report keluhan approved/rejected

### Prioritas Sedang
1. 💡 History log perubahan status
2. 💡 Comment/diskusi pada keluhan pending
3. 💡 Auto-reject jika tidak direspon dalam X hari
4. 💡 Kategori approval (auto-approve untuk kategori tertentu)

### Prioritas Rendah
1. 🔮 Machine learning untuk suggest approval
2. 🔮 Integration dengan sistem ticketing
3. 🔮 Mobile app untuk approval
4. 🔮 Voting system untuk keluhan populer

---

**Dibuat**: 10 November 2025  
**Versi**: 1.0  
**Status**: ✅ Production Ready
