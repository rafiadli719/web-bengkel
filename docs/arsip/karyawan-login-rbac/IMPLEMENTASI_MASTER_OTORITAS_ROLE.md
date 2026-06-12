# 🔐 IMPLEMENTASI: MASTER OTORITAS ROLE & PENGATURAN

## 📋 Ringkasan

Implementasi sistem kontrol akses berbasis role (RBAC) untuk semua file di folder `_admincab` dengan:
- ✅ Permission check di setiap halaman
- ✅ Conditional UI berdasarkan permission
- ✅ AJAX permission validation
- ✅ Activity logging untuk audit trail
- ✅ Flexible role management

---

## 📁 FILE YANG DIBUAT

### 1. **config/permission_check.php** ✅
**Status:** Created

**Fungsi Utama:**
```php
hasPermission($module, $action)           // Check permission
checkPermissionOrDie($module, $action)    // Check or die
logActivity($status, $module, $description) // Log activity
isAdmin()                                 // Check if admin
getRoleName()                             // Get role name
getAccessLevel()                          // Get access level
getBranchCode()                           // Get branch code
hasAllPermissions($module, $actions)      // Check all actions
hasAnyPermission($module, $actions)       // Check any action
validateAjaxRequest($module, $action)     // Validate AJAX
ajaxResponse($success, $message, $data)   // AJAX response
ajaxError($message, $code)                // AJAX error
ajaxSuccess($message, $data)              // AJAX success
```

---

## 🔧 IMPLEMENTASI DI SETIAP FILE

### Template 1: Main Page (View)

**File: `barang_kategori.php`** (example)

```php
<?php
session_start();
include "../config/koneksi.php";
include "../config/permission_check.php";

// Check session
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

// Check permission untuk view barang
checkPermissionOrDie('barang', 'view');

// Log activity
logActivity('SUCCESS', 'barang', 'Viewed barang kategori list');

// Get data
$query = "SELECT * FROM barang_kategori WHERE status_row='0'";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Barang Kategori</title>
</head>
<body>
    <div class="container">
        <h1>Daftar Barang Kategori</h1>
        
        <!-- Tombol Tambah (conditional) -->
        <?php if (hasPermission('barang', 'add')) { ?>
            <a href="barang_kategori_add.php" class="btn btn-primary">
                <i class="fa fa-plus"></i> Tambah Kategori
            </a>
        <?php } ?>
        
        <!-- Tabel Data -->
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['nama_kategori']; ?></td>
                        <td>
                            <!-- Edit Button (conditional) -->
                            <?php if (hasPermission('barang', 'edit')) { ?>
                                <a href="barang_kategori_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            <?php } ?>
                            
                            <!-- Delete Button (conditional) -->
                            <?php if (hasPermission('barang', 'delete')) { ?>
                                <a href="barang_kategori_del.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin?')">
                                    <i class="fa fa-trash"></i> Hapus
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
```

---

### Template 2: Add Page

**File: `barang_kategori_add.php`** (example)

```php
<?php
session_start();
include "../config/koneksi.php";
include "../config/permission_check.php";

// Check session
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

// Check permission untuk add barang
checkPermissionOrDie('barang', 'add');

// Process form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kategori = $_POST['nama_kategori'];
    
    $query = "INSERT INTO barang_kategori (nama_kategori, status_row, created_at) 
              VALUES ('$nama_kategori', '0', NOW())";
    
    if (mysqli_query($koneksi, $query)) {
        logActivity('SUCCESS', 'barang', "Added new kategori: $nama_kategori");
        header("location:barang_kategori.php?success=1");
    } else {
        logActivity('FAILED', 'barang', "Failed to add kategori: $nama_kategori");
        $error = "Gagal menambah kategori";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Barang Kategori</title>
</head>
<body>
    <div class="container">
        <h1>Tambah Barang Kategori</h1>
        
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="nama_kategori" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="barang_kategori.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>
```

---

### Template 3: Edit Page

**File: `barang_kategori_edit.php`** (example)

```php
<?php
session_start();
include "../config/koneksi.php";
include "../config/permission_check.php";

// Check session
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

// Check permission untuk edit barang
checkPermissionOrDie('barang', 'edit');

$id = $_GET['id'];

// Get data
$query = "SELECT * FROM barang_kategori WHERE id='$id'";
$result = mysqli_query($koneksi, $query);
$row = mysqli_fetch_assoc($result);

// Process form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kategori = $_POST['nama_kategori'];
    
    $query = "UPDATE barang_kategori SET nama_kategori='$nama_kategori' WHERE id='$id'";
    
    if (mysqli_query($koneksi, $query)) {
        logActivity('SUCCESS', 'barang', "Updated kategori ID $id to: $nama_kategori");
        header("location:barang_kategori.php?success=1");
    } else {
        logActivity('FAILED', 'barang', "Failed to update kategori ID $id");
        $error = "Gagal mengupdate kategori";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Barang Kategori</title>
</head>
<body>
    <div class="container">
        <h1>Edit Barang Kategori</h1>
        
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="nama_kategori" class="form-control" value="<?php echo $row['nama_kategori']; ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="barang_kategori.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>
```

---

### Template 4: Delete Page

**File: `barang_kategori_del.php`** (example)

```php
<?php
session_start();
include "../config/koneksi.php";
include "../config/permission_check.php";

// Check session
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

// Check permission untuk delete barang
checkPermissionOrDie('barang', 'delete');

$id = $_GET['id'];

// Get data
$query = "SELECT * FROM barang_kategori WHERE id='$id'";
$result = mysqli_query($koneksi, $query);
$row = mysqli_fetch_assoc($result);

// Delete
$query = "DELETE FROM barang_kategori WHERE id='$id'";

if (mysqli_query($koneksi, $query)) {
    logActivity('SUCCESS', 'barang', "Deleted kategori ID $id: " . $row['nama_kategori']);
    header("location:barang_kategori.php?success=1");
} else {
    logActivity('FAILED', 'barang', "Failed to delete kategori ID $id");
    header("location:barang_kategori.php?error=1");
}
?>
```

---

### Template 5: AJAX Handler

**File: `ajax-add-kategori.php`** (example)

```php
<?php
session_start();
include "../config/koneksi.php";
include "../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('barang', 'add')) {
    ajaxError("Unauthorized access", 403);
}

// Get data
$nama_kategori = $_POST['nama_kategori'] ?? '';

// Validate input
if (empty($nama_kategori)) {
    logActivity('FAILED', 'barang', "AJAX: Empty kategori name");
    ajaxError("Nama kategori tidak boleh kosong");
}

// Insert
$query = "INSERT INTO barang_kategori (nama_kategori, status_row, created_at) 
          VALUES (?, '0', NOW())";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("s", $nama_kategori);

if ($stmt->execute()) {
    logActivity('SUCCESS', 'barang', "AJAX: Added new kategori: $nama_kategori");
    ajaxSuccess("Kategori berhasil ditambahkan", ['id' => $koneksi->insert_id]);
} else {
    logActivity('FAILED', 'barang', "AJAX: Failed to add kategori: $nama_kategori");
    ajaxError("Gagal menambah kategori");
}

$stmt->close();
?>
```

---

## 📊 PERMISSION MATRIX

### Barang Module
```
Role          | View | Add | Edit | Delete | Export
--------------|------|-----|------|--------|--------
Admin (1)     | ✅   | ✅  | ✅   | ✅     | ✅
CS (2)        | ✅   | ❌  | ❌   | ❌     | ❌
Kasir (3)     | ✅   | ❌  | ❌   | ❌     | ❌
Mekanik (4)   | ✅   | ❌  | ❌   | ❌     | ❌
Pengadaan (5) | ✅   | ✅  | ✅   | ❌     | ✅
CRM (6)       | ✅   | ❌  | ❌   | ❌     | ❌
Manajemen (7) | ✅   | ❌  | ❌   | ❌     | ✅
Keuangan (8)  | ✅   | ❌  | ❌   | ❌     | ✅
HRD (9)       | ❌   | ❌  | ❌   | ❌     | ❌
Kepala Mek(10)| ✅   | ❌  | ❌   | ❌     | ❌
```

### Keluhan Module
```
Role          | View | Add | Edit | Delete | Approve | Reject | Export
--------------|------|-----|------|--------|---------|--------|--------
Admin (1)     | ✅   | ✅  | ✅   | ✅     | ✅      | ✅     | ✅
CS (2)        | ✅   | ✅  | ✅   | ❌     | ❌      | ❌     | ❌
Kasir (3)     | ✅   | ❌  | ❌   | ❌     | ❌      | ❌     | ❌
Mekanik (4)   | ✅   | ❌  | ❌   | ❌     | ❌      | ❌     | ❌
Pengadaan (5) | ❌   | ❌  | ❌   | ❌     | ❌      | ❌     | ❌
CRM (6)       | ✅   | ❌  | ❌   | ❌     | ❌      | ❌     | ❌
Manajemen (7) | ✅   | ❌  | ❌   | ❌     | ❌      | ❌     | ✅
Keuangan (8)  | ✅   | ❌  | ❌   | ❌     | ❌      | ❌     | ✅
HRD (9)       | ❌   | ❌  | ❌   | ❌     | ❌      | ❌     | ❌
Kepala Mek(10)| ✅   | ❌  | ❌   | ❌     | ✅      | ✅     | ❌
```

---

## 🧪 TESTING CHECKLIST

### Test dengan Admin (Level 1)
- [ ] Akses semua halaman
- [ ] Bisa view, add, edit, delete
- [ ] Activity log tercatat

### Test dengan CS (Level 2)
- [ ] Akses barang (view only)
- [ ] Akses keluhan (view, add, edit)
- [ ] Tidak bisa delete
- [ ] Tidak bisa akses settings

### Test dengan Kasir (Level 3)
- [ ] Akses barang (view only)
- [ ] Tidak bisa add/edit/delete
- [ ] Bisa process payment
- [ ] Activity log tercatat

### Test dengan Mekanik (Level 4)
- [ ] Akses workorder (view, update_status)
- [ ] Tidak bisa add/edit/delete
- [ ] Activity log tercatat

### Test AJAX Requests
- [ ] AJAX dengan permission ✅
- [ ] AJAX tanpa permission ❌
- [ ] Activity log tercatat

---

## 📈 IMPLEMENTASI ROADMAP

### Phase 1: Setup (1 hari)
- [x] Create `config/permission_check.php`
- [ ] Update `cek_login.php` untuk load permissions dari tb_user_roles
- [ ] Test permission functions

### Phase 2: Core Modules (2-3 hari)
- [ ] Update `barang_*.php` files (10+ files)
- [ ] Update `keluhan_*.php` files (10+ files)
- [ ] Update `workorder_*.php` files (10+ files)
- [ ] Update `akun_*.php` files (5+ files)

### Phase 3: AJAX Handlers (1 hari)
- [ ] Update `ajax-*.php` files (20+ files)
- [ ] Update `ajax_*.php` files (10+ files)

### Phase 4: Testing & Refinement (1 hari)
- [ ] Test dengan setiap role
- [ ] Verify activity logs
- [ ] Performance testing

---

## 🚀 NEXT STEPS

1. **Update `cek_login.php`** untuk load permissions dari tb_user_roles
2. **Create permission matrix** untuk setiap module
3. **Update setiap file** dengan permission check
4. **Test dengan setiap role**
5. **Monitor activity logs**

---

**Status:** ✅ **IMPLEMENTASI READY**  
**File Created:** `config/permission_check.php`  
**Siap untuk:** Phase 1 setup
