# Implementasi Status Pengerjaan - Keluhan, Temuan, dan Work Order

## 📋 Overview

Dokumen ini menjelaskan implementasi status pengerjaan untuk tracking progress keluhan, temuan, dan work order dalam sistem servis bengkel.

---

## 🎯 Status yang Tersedia

### **1. Keluhan (tbservis_keluhan_status)**
```sql
status_pengerjaan ENUM('datang', 'diproses', 'selesai', 'tidak_selesai')
keterangan_tidak_selesai VARCHAR(255)
```

**Status Flow:**
```
datang → diproses → selesai
                 ↘ tidak_selesai (+ keterangan)
```

### **2. Temuan (tbservis_temuan)**
```sql
status_temuan ENUM('ditemukan', 'ditawarkan', 'disetujui', 'ditolak', 'selesai')
```

**Status Flow:**
```
ditemukan → ditawarkan → disetujui → selesai
                      ↘ ditolak
```

### **3. Work Order (tbservis_workorder)**
```sql
status_pengerjaan ENUM('diproses', 'selesai', 'tidak_selesai')
keterangan_tidak_selesai VARCHAR(255)
```

**Status Flow:**
```
diproses → selesai
        ↘ tidak_selesai (+ keterangan)
```

---

## 📊 Database Views yang Dibuat

### **1. view_servis_keluhan_lengkap**

**Kolom Utama:**
- `id`, `no_service`, `keluhan`
- `status_pengerjaan` ⭐ (datang/diproses/selesai/tidak_selesai)
- `keterangan_tidak_selesai` ⭐
- `status_badge_color` (success/warning/danger/info)
- `jumlah_temuan` (count related)
- Info service, customer, vehicle

**Penggunaan:**
```sql
-- Lihat semua keluhan dengan status
SELECT * FROM view_servis_keluhan_lengkap;

-- Filter keluhan yang belum selesai
SELECT * FROM view_servis_keluhan_lengkap 
WHERE status_pengerjaan IN ('datang', 'diproses');

-- Keluhan tidak selesai dengan keterangan
SELECT * FROM view_servis_keluhan_lengkap 
WHERE status_pengerjaan = 'tidak_selesai';
```

---

### **2. view_servis_temuan_lengkap**

**Kolom Utama:**
- `id`, `no_service`, `temuan_custom`, `nama_temuan`
- `status_pengerjaan` ⭐ (mapped from status_temuan)
- `jenis_perbaikan` (ringan/sedang/berat)
- `tingkat_urgensi` (rendah/sedang/tinggi/urgent)
- `estimasi_biaya`
- `status_badge_color`, `urgency_badge_color`
- `jumlah_penawaran`, `penawaran_pending`
- Info keluhan, service, customer, vehicle

**Penggunaan:**
```sql
-- Lihat semua temuan dengan status
SELECT * FROM view_servis_temuan_lengkap;

-- Temuan urgent yang belum selesai
SELECT * FROM view_servis_temuan_lengkap 
WHERE tingkat_urgensi = 'urgent' 
AND status_pengerjaan != 'selesai';

-- Temuan dengan penawaran pending
SELECT * FROM view_servis_temuan_lengkap 
WHERE penawaran_pending > 0;
```

---

### **3. view_servis_workorder_lengkap**

**Kolom Utama:**
- `id`, `no_service`, `kode_wo`, `nama_wo`
- `status_pengerjaan` ⭐ (diproses/selesai/tidak_selesai)
- `keterangan_tidak_selesai` ⭐
- `status_badge_color`
- `progress_percentage` (0/50/100)
- `jumlah_item`, `jumlah_jasa`, `jumlah_barang`
- Info service, customer, vehicle

**Penggunaan:**
```sql
-- Lihat semua work order dengan status
SELECT * FROM view_servis_workorder_lengkap;

-- Work order yang sedang diproses
SELECT * FROM view_servis_workorder_lengkap 
WHERE status_pengerjaan = 'diproses';

-- Work order tidak selesai dengan keterangan
SELECT * FROM view_servis_workorder_lengkap 
WHERE status_pengerjaan = 'tidak_selesai';
```

---

### **4. view_servis_status_summary** ⭐ (Dashboard View)

**Kolom Utama:**
- `no_service`, `tanggal`, `jam`
- `total_keluhan`, `keluhan_selesai`, `keluhan_diproses`, `keluhan_tidak_selesai`
- `total_temuan`, `temuan_selesai`, `temuan_disetujui`, `temuan_ditolak`
- `total_workorder`, `workorder_selesai`, `workorder_diproses`, `workorder_tidak_selesai`
- `progress_percentage` ⭐ (Overall completion)

**Penggunaan:**
```sql
-- Dashboard summary
SELECT * FROM view_servis_status_summary 
ORDER BY tanggal DESC LIMIT 20;

-- Services dengan item tidak selesai
SELECT * FROM view_servis_status_summary 
WHERE keluhan_tidak_selesai > 0 OR workorder_tidak_selesai > 0;

-- Services dengan progress < 100%
SELECT * FROM view_servis_status_summary 
WHERE progress_percentage < 100;
```

---

## 🎨 Badge Colors untuk UI

### **Status Keluhan & Work Order**
| Status | Badge Color | Bootstrap Class |
|--------|-------------|-----------------|
| selesai | success (hijau) | `badge-success` |
| diproses | warning (kuning) | `badge-warning` |
| tidak_selesai | danger (merah) | `badge-danger` |
| datang | info (biru) | `badge-info` |

### **Status Temuan**
| Status | Badge Color | Bootstrap Class |
|--------|-------------|-----------------|
| selesai | success (hijau) | `badge-success` |
| disetujui | primary (biru tua) | `badge-primary` |
| ditawarkan | warning (kuning) | `badge-warning` |
| ditolak | danger (merah) | `badge-danger` |
| ditemukan | info (biru) | `badge-info` |

### **Tingkat Urgensi Temuan**
| Urgensi | Badge Color | Bootstrap Class |
|---------|-------------|-----------------|
| urgent | danger (merah) | `badge-danger` |
| tinggi | warning (kuning) | `badge-warning` |
| sedang | info (biru) | `badge-info` |
| rendah | default (abu) | `badge-default` |

---

## 💻 Implementasi di PHP

### **1. Tampilkan Status di Tabel Keluhan**

```php
// Query menggunakan view
$query = "SELECT * FROM view_servis_keluhan_lengkap 
          WHERE no_service = '$no_service' 
          ORDER BY created_at DESC";
$result = mysqli_query($koneksi, $query);

while($row = mysqli_fetch_array($result)) {
    echo '<tr>';
    echo '<td>' . $row['keluhan'] . '</td>';
    
    // Status badge dengan warna dinamis
    echo '<td>';
    echo '<span class="badge badge-' . $row['status_badge_color'] . '">';
    echo strtoupper($row['status_pengerjaan']);
    echo '</span>';
    echo '</td>';
    
    // Keterangan jika tidak selesai
    if($row['status_pengerjaan'] == 'tidak_selesai') {
        echo '<td>' . $row['keterangan_tidak_selesai'] . '</td>';
    }
    
    echo '</tr>';
}
```

### **2. Form Update Status Keluhan**

```php
<form method="post" action="">
    <input type="hidden" name="keluhan_id" value="<?php echo $keluhan_id; ?>">
    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
    
    <div class="form-group">
        <label>Status Pengerjaan:</label>
        <select name="status_keluhan" class="form-control" id="status_keluhan">
            <option value="datang">Datang</option>
            <option value="diproses">Diproses</option>
            <option value="selesai">Selesai</option>
            <option value="tidak_selesai">Tidak Selesai</option>
        </select>
    </div>
    
    <div class="form-group" id="keterangan_group" style="display:none;">
        <label>Keterangan Tidak Selesai:</label>
        <textarea name="keterangan_keluhan" class="form-control" rows="3"></textarea>
    </div>
    
    <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-primary">
        Update Status
    </button>
</form>

<script>
// Show/hide keterangan field
$('#status_keluhan').change(function() {
    if($(this).val() == 'tidak_selesai') {
        $('#keterangan_group').show();
    } else {
        $('#keterangan_group').hide();
    }
});
</script>
```

### **3. Handler Update Status (sudah ada di file)**

```php
// File: servis-input-reguler.php (Line 514-526)
if(isset($_POST['btnupdatestatuskeluhan'])) {
    $keluhan_id = $_POST['keluhan_id'];
    $status_keluhan = $_POST['status_keluhan'];
    $keterangan = $_POST['keterangan_keluhan'] ?? '';
    
    $query = "UPDATE tbservis_keluhan_status 
              SET status_pengerjaan='$status_keluhan', 
                  keterangan_tidak_selesai='$keterangan' 
              WHERE id='$keluhan_id'";
    
    mysqli_query($koneksi, $query);
    
    echo "<script>
        alert('Status keluhan berhasil diupdate!');
        window.location='servis-input-reguler.php?snoserv=$no_service';
    </script>";
}
```

### **4. Tampilkan Progress Bar**

```php
// Get summary data
$query = "SELECT * FROM view_servis_status_summary WHERE no_service = '$no_service'";
$summary = mysqli_fetch_array(mysqli_query($koneksi, $query));

$progress = $summary['progress_percentage'];
$progress_color = $progress == 100 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
?>

<div class="progress">
    <div class="progress-bar progress-bar-<?php echo $progress_color; ?>" 
         style="width: <?php echo $progress; ?>%">
        <?php echo $progress; ?>% Complete
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="widget-box">
            <div class="widget-header">
                <h5>Keluhan</h5>
            </div>
            <div class="widget-body">
                <p>Total: <?php echo $summary['total_keluhan']; ?></p>
                <p>Selesai: <?php echo $summary['keluhan_selesai']; ?></p>
                <p>Diproses: <?php echo $summary['keluhan_diproses']; ?></p>
                <p>Tidak Selesai: <?php echo $summary['keluhan_tidak_selesai']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="widget-box">
            <div class="widget-header">
                <h5>Work Order</h5>
            </div>
            <div class="widget-body">
                <p>Total: <?php echo $summary['total_workorder']; ?></p>
                <p>Selesai: <?php echo $summary['workorder_selesai']; ?></p>
                <p>Diproses: <?php echo $summary['workorder_diproses']; ?></p>
                <p>Tidak Selesai: <?php echo $summary['workorder_tidak_selesai']; ?></p>
            </div>
        </div>
    </div>
</div>
```

---

## 📝 Catatan Servis untuk Item Tidak Selesai

### **Alur Pencatatan:**

1. **Saat Update Status ke "Tidak Selesai":**
   - User wajib mengisi `keterangan_tidak_selesai`
   - Keterangan disimpan di tabel masing-masing (keluhan/workorder)

2. **Saat Pembayaran/Selesai Service:**
   - Sistem mengumpulkan semua item dengan status `tidak_selesai`
   - Gabungkan keterangan ke field `catatan` di `tblservice`

### **Implementasi Auto-collect Catatan:**

```php
// File: servis-input-reguler-rst.php (saat btnbayar)
if(isset($_POST['btnbayar'])) {
    $no_service = $_POST['txtnosrv'];
    
    // Collect catatan dari keluhan tidak selesai
    $catatan_keluhan = [];
    $query_keluhan = "SELECT keluhan, keterangan_tidak_selesai 
                      FROM tbservis_keluhan_status 
                      WHERE no_service='$no_service' 
                      AND status_pengerjaan='tidak_selesai'";
    $result_keluhan = mysqli_query($koneksi, $query_keluhan);
    while($row = mysqli_fetch_array($result_keluhan)) {
        $catatan_keluhan[] = "KELUHAN: " . $row['keluhan'] . 
                            " - " . $row['keterangan_tidak_selesai'];
    }
    
    // Collect catatan dari workorder tidak selesai
    $catatan_wo = [];
    $query_wo = "SELECT nama_wo, keterangan_tidak_selesai 
                 FROM view_servis_workorder_lengkap 
                 WHERE no_service='$no_service' 
                 AND status_pengerjaan='tidak_selesai'";
    $result_wo = mysqli_query($koneksi, $query_wo);
    while($row = mysqli_fetch_array($result_wo)) {
        $catatan_wo[] = "WORK ORDER: " . $row['nama_wo'] . 
                       " - " . $row['keterangan_tidak_selesai'];
    }
    
    // Gabungkan semua catatan
    $all_catatan = array_merge($catatan_keluhan, $catatan_wo);
    $catatan_final = implode("\n", $all_catatan);
    
    // Update tblservice dengan catatan
    $query_update = "UPDATE tblservice 
                     SET catatan = CONCAT(COALESCE(catatan, ''), '\n\n', '$catatan_final')
                     WHERE no_service='$no_service'";
    mysqli_query($koneksi, $query_update);
    
    // ... proses pembayaran lainnya ...
}
```

---

## 🔍 Query Berguna untuk Monitoring

### **1. Keluhan Pending per Mekanik**
```sql
SELECT 
    k.keluhan,
    k.status_pengerjaan,
    s.mekanik1,
    s.mekanik2,
    k.created_at
FROM view_servis_keluhan_lengkap k
JOIN tblservice s ON k.no_service = s.no_service
WHERE k.status_pengerjaan IN ('datang', 'diproses')
ORDER BY k.created_at ASC;
```

### **2. Work Order Overdue**
```sql
SELECT 
    wo.*,
    DATEDIFF(NOW(), wo.created_at) as days_open
FROM view_servis_workorder_lengkap wo
WHERE wo.status_pengerjaan = 'diproses'
AND DATEDIFF(NOW(), wo.created_at) > 3
ORDER BY days_open DESC;
```

### **3. Services dengan Item Tidak Selesai**
```sql
SELECT 
    s.*,
    GROUP_CONCAT(DISTINCT k.keluhan SEPARATOR '; ') as keluhan_tidak_selesai,
    GROUP_CONCAT(DISTINCT wo.nama_wo SEPARATOR '; ') as wo_tidak_selesai
FROM view_servis_status_summary s
LEFT JOIN tbservis_keluhan_status k ON s.no_service = k.no_service 
    AND k.status_pengerjaan = 'tidak_selesai'
LEFT JOIN view_servis_workorder_lengkap wo ON s.no_service = wo.no_service 
    AND wo.status_pengerjaan = 'tidak_selesai'
WHERE s.keluhan_tidak_selesai > 0 OR s.workorder_tidak_selesai > 0
GROUP BY s.no_service;
```

---

## ✅ Checklist Implementasi

### **Database:**
- [x] Kolom `status_pengerjaan` sudah ada di tabel
- [x] Kolom `keterangan_tidak_selesai` sudah ada
- [ ] Run SQL: `ADD_STATUS_PENGERJAAN_TO_VIEWS.sql`
- [ ] Test semua view yang dibuat

### **PHP Backend:**
- [x] Handler update status keluhan (sudah ada)
- [x] Handler update status work order (sudah ada)
- [ ] Auto-collect catatan saat pembayaran
- [ ] Validasi wajib isi keterangan jika tidak selesai

### **UI Frontend:**
- [ ] Tampilkan kolom status di tabel keluhan
- [ ] Tampilkan kolom status di tabel work order
- [ ] Form update status dengan dropdown
- [ ] Show/hide keterangan field dengan JavaScript
- [ ] Badge warna untuk status
- [ ] Progress bar per service
- [ ] Dashboard summary widget

### **Testing:**
- [ ] Test update status keluhan
- [ ] Test update status work order
- [ ] Test keterangan tidak selesai
- [ ] Test auto-collect catatan
- [ ] Test view performance

---

## 🚀 Cara Implementasi

### **Step 1: Run SQL**
```bash
# Via phpMyAdmin atau MySQL CLI
mysql -u root -p fitmotor_dbbengkel < ADD_STATUS_PENGERJAAN_TO_VIEWS.sql
```

### **Step 2: Update PHP Files**
- Tambahkan kolom status di tabel display
- Tambahkan form update status
- Implementasi auto-collect catatan

### **Step 3: Test**
- Buat service baru
- Tambah keluhan & work order
- Update status ke "tidak selesai"
- Isi keterangan
- Proses pembayaran
- Cek catatan di tblservice

---

## 📞 Support

Jika ada pertanyaan atau issue, silakan dokumentasikan di:
- File: `TESTING_CHECKLIST.md`
- Section: Status Pengerjaan Implementation

---

**Last Updated:** 2025-01-09
**Version:** 1.0
