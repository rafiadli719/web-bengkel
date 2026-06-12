# 📊 PANDUAN: ROLE MANAGEMENT DASHBOARD

## 🎯 Tujuan

Membuat halaman dashboard dinamis untuk manage otoritas role user secara lengkap, mirip dengan struktur `login_dashboard/`.

---

## 📁 STRUKTUR FOLDER

```
aplikasi/aplikasi/role_management/
├── index.php                    # Main dashboard
├── ajax/
│   ├── get_users.php           # Get all users
│   ├── get_roles.php           # Get all roles
│   ├── get_role.php            # Get single role
│   ├── get_user_permissions.php # Get user permissions
│   ├── save_role.php           # Save new role
│   ├── update_role.php         # Update role
│   ├── save_permissions.php    # Save permissions
│   ├── assign_user_role.php    # Assign role to user
│   └── get_activity_log.php    # Get activity log
├── assets/
│   ├── css/
│   │   └── style.css           # Custom styles
│   └── js/
│       └── script.js           # Custom scripts
└── README.md                    # Documentation
```

---

## 🎨 FITUR UTAMA

### 1. **Daftar Role** (Tab 1)
- ✅ Tampilkan semua role dengan detail
- ✅ Tombol Edit Role
- ✅ Tombol Manage Permission
- ✅ Status badge (Aktif/Tidak Aktif)
- ✅ DataTable dengan search & pagination

### 2. **User & Permission** (Tab 2)
- ✅ Pilih user dari dropdown
- ✅ Tampilkan permission user saat ini
- ✅ Edit permission per module
- ✅ Assign role ke user
- ✅ Save permission changes

### 3. **Pengaturan** (Tab 3)
- ✅ Default role untuk user baru
- ✅ Require approval untuk role change
- ✅ Enable/disable audit logging
- ✅ IP restriction settings
- ✅ Session timeout settings

### 4. **Modal Dialogs**
- ✅ Add Role Modal
- ✅ Edit Role Modal
- ✅ Manage Permissions Modal

---

## 🔧 IMPLEMENTASI STEP-BY-STEP

### Step 1: Create Folder Structure
```bash
mkdir -p c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\role_management\ajax
mkdir -p c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\role_management\assets\css
mkdir -p c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\role_management\assets\js
```

### Step 2: Create Main Files
- `index.php` - Dashboard HTML & PHP
- `ajax/get_users.php` - Get users AJAX
- `ajax/get_roles.php` - Get roles AJAX
- `ajax/save_role.php` - Save role AJAX
- `ajax/save_permissions.php` - Save permissions AJAX

### Step 3: Create CSS & JS
- `assets/css/style.css` - Custom styling
- `assets/js/script.js` - JavaScript functions

### Step 4: Test & Verify
- Test permission check
- Test AJAX calls
- Test role management
- Test permission assignment

---

## 📋 PERMISSION MATRIX

### Module Permissions
```
Module      | View | Add | Edit | Delete | Approve | Reject | Export
------------|------|-----|------|--------|---------|--------|--------
barang      | ✅   | ✅  | ✅   | ✅     | -       | -      | ✅
keluhan     | ✅   | ✅  | ✅   | ✅     | ✅      | ✅     | ✅
workorder   | ✅   | ✅  | ✅   | ✅     | ✅      | ✅     | ✅
reports     | ✅   | -   | -    | -      | -       | -      | ✅
users       | ✅   | ✅  | ✅   | ✅     | -       | -      | ✅
settings    | ✅   | ✅  | ✅   | ✅     | -       | -      | -
```

---

## 🎯 FITUR YANG AKAN DIIMPLEMENTASI

### Dashboard Features:
1. **Role Management**
   - Create new role
   - Edit existing role
   - Delete role
   - View role details

2. **Permission Management**
   - Assign permission to role
   - View role permissions
   - Bulk permission update
   - Permission templates

3. **User Assignment**
   - Assign role to user
   - View user roles
   - Change user role
   - Revoke user access

4. **Activity Logging**
   - View activity log
   - Filter by user/action/date
   - Export activity log
   - Audit trail

5. **Settings**
   - Default role configuration
   - Approval workflow
   - Security settings
   - System configuration

---

## 🔐 SECURITY FEATURES

- ✅ Permission check (only admin)
- ✅ Activity logging for all changes
- ✅ AJAX validation
- ✅ CSRF protection
- ✅ Input sanitization
- ✅ SQL injection prevention
- ✅ Rate limiting

---

## 📊 DATABASE QUERIES

### Get All Roles
```sql
SELECT role_id, role_code, role_name, role_description, department, is_active
FROM tb_user_roles
ORDER BY role_code ASC
```

### Get Role Permissions
```sql
SELECT permissions
FROM tb_user_roles
WHERE role_id = ?
```

### Get User Permissions
```sql
SELECT ua.id, ua.username, r.permissions
FROM tb_user_account ua
LEFT JOIN tb_user_roles r ON ua.user_akses_level = r.role_code
WHERE ua.id = ?
```

### Update Role Permissions
```sql
UPDATE tb_user_roles
SET permissions = ?
WHERE role_id = ?
```

---

## 🧪 TESTING CHECKLIST

- [ ] Access dashboard as admin
- [ ] View all roles
- [ ] Create new role
- [ ] Edit existing role
- [ ] Manage permissions
- [ ] Assign role to user
- [ ] View activity log
- [ ] Test AJAX calls
- [ ] Test permission validation
- [ ] Test error handling

---

## 📈 NEXT STEPS

1. Create folder structure
2. Create `index.php` with dashboard HTML
3. Create AJAX handlers
4. Create CSS & JS files
5. Test all features
6. Deploy to production

---

**Status:** 📋 **PANDUAN READY**  
**Siap untuk:** Implementasi file-by-file
