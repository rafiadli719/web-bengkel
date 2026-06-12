# Quick Reference - Status Pengerjaan

## 🚀 Cara Cepat Implementasi

### **1. Run SQL (1 menit)**
```bash
mysql -u root -p fitmotor_dbbengkel < FIX_STATUS_PENGERJAAN_VIEWS.sql
```

### **2. Update Query PHP (2 menit)**
```php
// Ganti query dari tabel ke view
$query = "SELECT * FROM view_servis_keluhan_lengkap WHERE no_service='$no_service'";
```

### **3. Tampilkan Status (3 menit)**
```php
// Badge status
echo '<span class="label label-' . $row['status_badge_color'] . '">';
echo strtoupper($row['status_pengerjaan']);
echo '</span>';
```

---

## 📊 Kolom yang Tersedia di View

### **view_servis_keluhan_lengkap**
```
✅ status_pengerjaan (datang/diproses/selesai/tidak_selesai)
✅ keterangan_tidak_selesai
✅ status_badge_color (success/warning/danger/info)
✅ jumlah_temuan
✅ hp_pelanggan (telephone)
✅ hp_pelanggan2 (notlp)
✅ kode_merek, tipe_kendaraan, jenis_kendaraan
```

### **view_servis_workorder_lengkap**
```
✅ status_pengerjaan (diproses/selesai/tidak_selesai)
✅ keterangan_tidak_selesai
✅ status_badge_color
✅ progress_percentage (0/50/100)
✅ jumlah_item, jumlah_jasa, jumlah_barang
```

### **view_servis_status_summary**
```
✅ total_keluhan, keluhan_selesai, keluhan_diproses, keluhan_tidak_selesai
✅ total_workorder, workorder_selesai, workorder_diproses, workorder_tidak_selesai
✅ progress_percentage (overall)
```

---

## 🎨 Badge Colors

| Status | Color | Class |
|--------|-------|-------|
| selesai | 🟢 Hijau | `label-success` |
| diproses | 🟡 Kuning | `label-warning` |
| tidak_selesai | 🔴 Merah | `label-danger` |
| datang | 🔵 Biru | `label-info` |

---

## 💻 Copy-Paste Code

### **Tabel Keluhan dengan Status**
```php
<table class="table table-striped">
    <thead>
        <tr>
            <th>Keluhan</th>
            <th>Status</th>
            <th>Keterangan</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $query = "SELECT * FROM view_servis_keluhan_lengkap WHERE no_service='$no_service'";
        $result = mysqli_query($koneksi, $query);
        while($row = mysqli_fetch_array($result)) {
        ?>
        <tr>
            <td><?php echo $row['keluhan']; ?></td>
            <td>
                <span class="label label-<?php echo $row['status_badge_color']; ?>">
                    <?php echo strtoupper($row['status_pengerjaan']); ?>
                </span>
            </td>
            <td>
                <?php 
                if($row['status_pengerjaan'] == 'tidak_selesai') {
                    echo '<strong class="text-danger">' . $row['keterangan_tidak_selesai'] . '</strong>';
                } else {
                    echo '<em class="text-muted">-</em>';
                }
                ?>
            </td>
            <td>
                <button class="btn btn-xs btn-info" data-toggle="modal" data-target="#modalUpdateStatus">
                    Update
                </button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
```

### **Modal Update Status**
```php
<div class="modal fade" id="modalUpdateStatus">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h4>Update Status Keluhan</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="keluhan_id" id="keluhan_id">
                    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                    
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status_keluhan" id="status_keluhan" class="form-control">
                            <option value="datang">Datang</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="tidak_selesai">Tidak Selesai</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="keterangan_group" style="display:none;">
                        <label>Keterangan:</label>
                        <textarea name="keterangan_keluhan" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#status_keluhan').change(function() {
    if($(this).val() == 'tidak_selesai') {
        $('#keterangan_group').show();
    } else {
        $('#keterangan_group').hide();
    }
});
</script>
```

### **Dashboard Summary**
```php
<?php
$query = "SELECT * FROM view_servis_status_summary WHERE no_service='$no_service'";
$summary = mysqli_fetch_array(mysqli_query($koneksi, $query));
$progress = $summary['progress_percentage'];
?>

<div class="progress">
    <div class="progress-bar progress-bar-<?php echo $progress == 100 ? 'success' : 'warning'; ?>" 
         style="width: <?php echo $progress; ?>%">
        <?php echo $progress; ?>% Complete
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h5>Keluhan</h5>
        <p>Total: <?php echo $summary['total_keluhan']; ?></p>
        <p>Selesai: <?php echo $summary['keluhan_selesai']; ?></p>
        <p>Tidak Selesai: <?php echo $summary['keluhan_tidak_selesai']; ?></p>
    </div>
    <div class="col-md-6">
        <h5>Work Order</h5>
        <p>Total: <?php echo $summary['total_workorder']; ?></p>
        <p>Selesai: <?php echo $summary['workorder_selesai']; ?></p>
        <p>Tidak Selesai: <?php echo $summary['workorder_tidak_selesai']; ?></p>
    </div>
</div>
```

### **Auto-collect Catatan (saat Pembayaran)**
```php
// Di handler btnbayar
if(isset($_POST['btnbayar'])) {
    $no_service = $_POST['txtnosrv'];
    
    // Collect catatan dari item tidak selesai
    $catatan_array = [];
    
    // Dari keluhan
    $q_keluhan = "SELECT keluhan, keterangan_tidak_selesai 
                  FROM view_servis_keluhan_lengkap 
                  WHERE no_service='$no_service' AND status_pengerjaan='tidak_selesai'";
    $r_keluhan = mysqli_query($koneksi, $q_keluhan);
    while($row = mysqli_fetch_array($r_keluhan)) {
        $catatan_array[] = "KELUHAN TIDAK SELESAI: " . $row['keluhan'] . " - " . $row['keterangan_tidak_selesai'];
    }
    
    // Dari work order
    $q_wo = "SELECT nama_wo, keterangan_tidak_selesai 
             FROM view_servis_workorder_lengkap 
             WHERE no_service='$no_service' AND status_pengerjaan='tidak_selesai'";
    $r_wo = mysqli_query($koneksi, $q_wo);
    while($row = mysqli_fetch_array($r_wo)) {
        $catatan_array[] = "WORK ORDER TIDAK SELESAI: " . $row['nama_wo'] . " - " . $row['keterangan_tidak_selesai'];
    }
    
    // Gabungkan ke catatan service
    if(count($catatan_array) > 0) {
        $catatan_final = implode("\n", $catatan_array);
        $catatan_final = mysqli_real_escape_string($koneksi, $catatan_final);
        
        mysqli_query($koneksi, "UPDATE tblservice 
                                SET catatan = CONCAT(COALESCE(catatan, ''), '\n\n', '$catatan_final')
                                WHERE no_service='$no_service'");
    }
    
    // ... proses pembayaran lainnya ...
}
```

---

## 🔍 Query Berguna

### **Keluhan Belum Selesai**
```sql
SELECT * FROM view_servis_keluhan_lengkap 
WHERE status_pengerjaan IN ('datang', 'diproses')
ORDER BY created_at ASC;
```

### **Work Order Tidak Selesai**
```sql
SELECT * FROM view_servis_workorder_lengkap 
WHERE status_pengerjaan = 'tidak_selesai'
ORDER BY created_at DESC;
```

### **Service dengan Progress < 100%**
```sql
SELECT * FROM view_servis_status_summary 
WHERE progress_percentage < 100
ORDER BY progress_percentage ASC;
```

### **Item Tidak Selesai Hari Ini**
```sql
SELECT * FROM view_servis_status_summary 
WHERE (keluhan_tidak_selesai > 0 OR workorder_tidak_selesai > 0)
AND tanggal = CURDATE();
```

---

## 🐛 Common Issues

### **Issue: Kolom tidak ditemukan**
```
Error: Unknown column 'p.nohp'
Fix: Gunakan FIX_STATUS_PENGERJAAN_VIEWS.sql (sudah diperbaiki)
```

### **Issue: View tidak ada**
```
Error: Table 'view_servis_keluhan_lengkap' doesn't exist
Fix: Run SQL file untuk create view
```

### **Issue: Keterangan tidak muncul**
```
Problem: Keterangan tidak selesai tidak tampil
Fix: Pastikan kolom keterangan_tidak_selesai di-select dari view
```

---

## 📞 Files Reference

| File | Fungsi |
|------|--------|
| `FIX_STATUS_PENGERJAAN_VIEWS.sql` | SQL untuk create view (FIXED) |
| `IMPLEMENTASI_STATUS_PENGERJAAN.md` | Dokumentasi lengkap |
| `PERBAIKAN_KOLOM_DATABASE.md` | Penjelasan perbaikan error |
| `CONTOH_UI_STATUS_PENGERJAAN.html` | Contoh tampilan UI |
| `QUICK_REFERENCE_STATUS.md` | Quick guide (file ini) |

---

**Last Updated:** 2025-01-09  
**Version:** 1.1  
**Status:** ✅ Ready to Use
