# ✅ IMPLEMENTASI CHECKLIST: Master Karyawan & Users

**Database:** fitmotor_dbbengkel  
**Tanggal:** 16 November 2025  
**Status:** Planning Phase

---

## 🔴 PHASE 1: SECURITY FIXES (URGENT - Week 1)

### 1.1 Password Hashing Implementation
- [ ] Create migration script untuk hash semua password di `tb_user_account`
- [ ] Create migration script untuk hash semua password di `tbuser` (jika masih digunakan)
- [ ] Update login script untuk gunakan `password_verify()`
- [ ] Update password change script untuk gunakan `password_hash()`
- [ ] Test login dengan hashed password
- [ ] Document password hashing implementation

**Files to Update:**
- `aplikasi/cek_login.php` - Update password verification
- `aplikasi/_admin/user_edit.php` - Update password change
- `aplikasi/_admincab/master_karyawan_save.php` - Update password creation

**SQL Migration:**
```php
// Pseudocode untuk migration
foreach ($users as $user) {
    $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
    UPDATE tb_user_account SET password_hash = '$hashed' WHERE id = $user['id'];
}
```

### 1.2 SQL Injection Protection
- [ ] Review semua file PHP yang menggunakan database query
- [ ] Convert string concatenation ke prepared statements
- [ ] Test semua query dengan SQL injection payloads
- [ ] Document prepared statements usage

**Files to Update:**
- `aplikasi/_admincab/master-posisi.php` - Convert to prepared statements
- `aplikasi/_admincab/master_karyawan.php` - Convert to prepared statements
- `aplikasi/_admin/user.php` - Convert to prepared statements
- All AJAX handlers

**Example Fix:**
```php
// BEFORE (Vulnerable)
$sql = "SELECT * FROM tb_master_posisi WHERE id = '$edit_id'";

// AFTER (Safe)
$stmt = $koneksi->prepare("SELECT * FROM tb_master_posisi WHERE id = ?");
$stmt->bind_param("i", $edit_id);
$stmt->execute();
$result = $stmt->get_result();
```

### 1.3 Add Foreign Key Constraints
- [ ] Create migration SQL script
- [ ] Test FK constraints dengan invalid data
- [ ] Document FK relationships
- [ ] Add error handling untuk FK constraint violations

**SQL Script:**
```sql
ALTER TABLE tb_master_karyawan 
ADD CONSTRAINT fk_karyawan_posisi 
FOREIGN KEY (kode_posisi) REFERENCES tb_master_posisi(kode_posisi);

ALTER TABLE tb_master_karyawan 
ADD CONSTRAINT fk_karyawan_level 
FOREIGN KEY (kode_level) REFERENCES tb_master_level(kode_level);

ALTER TABLE tb_user_account 
ADD CONSTRAINT fk_user_karyawan 
FOREIGN KEY (kode_karyawan) REFERENCES tb_master_karyawan(kode_karyawan);
```

---

## 🟡 PHASE 2: DATABASE OPTIMIZATION (Week 2)

### 2.1 Add Database Indexes
- [ ] Create migration SQL script untuk add indexes
- [ ] Test query performance sebelum & sesudah
- [ ] Document index strategy
- [ ] Monitor index usage

**SQL Script:**
```sql
ALTER TABLE tb_master_karyawan ADD INDEX idx_kode_posisi (kode_posisi);
ALTER TABLE tb_master_karyawan ADD INDEX idx_kode_level (kode_level);
ALTER TABLE tb_user_account ADD INDEX idx_kode_karyawan (kode_karyawan);
ALTER TABLE tb_user_account ADD INDEX idx_username (username);
```

### 2.2 Data Integrity Checks
- [ ] Create SQL script untuk check orphan records
- [ ] Create SQL script untuk check duplicate data
- [ ] Run checks dan fix issues
- [ ] Document findings

**Checks:**
```sql
-- Check karyawan dengan posisi yang tidak ada
SELECT * FROM tb_master_karyawan 
WHERE kode_posisi NOT IN (SELECT kode_posisi FROM tb_master_posisi);

-- Check user dengan karyawan yang tidak ada
SELECT * FROM tb_user_account 
WHERE kode_karyawan NOT IN (SELECT kode_karyawan FROM tb_master_karyawan);
```

---

## 🟢 PHASE 3: CODE REFACTORING (Week 3-4)

### 3.1 Migrate from tbuser to tb_user_account
- [ ] Create backup dari tbuser
- [ ] Create migration script
- [ ] Test migration dengan sample data
- [ ] Update all references dari tbuser ke tb_user_account
- [ ] Deprecate tbuser (add warning comment)
- [ ] Plan untuk delete tbuser setelah 3 bulan

**Migration Script:**
```php
// Pseudocode
1. Backup tbuser
2. Insert data dari tbuser ke tb_user_account (dengan mapping kode_karyawan)
3. Hash passwords
4. Update all PHP files untuk gunakan tb_user_account
5. Test login dengan semua user
6. Monitor untuk 1 bulan
7. Delete tbuser
```

### 3.2 Standardize Naming Convention
- [ ] Create naming convention document
- [ ] Review semua column names
- [ ] Create migration script untuk rename columns (jika perlu)
- [ ] Update all PHP files dengan new names
- [ ] Test semua functionality

**Naming Convention:**
```
Tabel: tb_[module]_[entity]
Kolom: [entity]_[attribute]
PK: id
FK: [table]_id atau [entity]_code
Status: is_[status] atau [status]
Timestamp: created_at, updated_at
```

### 3.3 Implement Audit Logging
- [ ] Create logging function
- [ ] Add logging ke semua CRUD operations
- [ ] Create dashboard untuk view activity logs
- [ ] Create reports untuk audit trail
- [ ] Test logging functionality

**Logging Function:**
```php
function log_activity($user_id, $action, $module, $description, $ip_address) {
    // Insert ke tb_user_activity_log
}

// Usage:
log_activity($_SESSION['_iduser'], 'CREATE', 'master_karyawan', 
             'Tambah karyawan: KRY-00001', $_SERVER['REMOTE_ADDR']);
```

### 3.4 Add Input Validation
- [ ] Create validation function
- [ ] Add validation ke semua form inputs
- [ ] Test dengan invalid data
- [ ] Create error messages yang user-friendly

**Validation Rules:**
```php
// Kode Karyawan: uppercase alphanumeric, max 20 chars
// Nama Lengkap: tidak boleh kosong, max 100 chars
// Email: valid email format
// Telepon: numeric, 10-15 digits
// Tanggal Masuk: valid date, tidak boleh > hari ini
// Level Akses: numeric, range 1-99
```

---

## 🔵 PHASE 4: TESTING & DOCUMENTATION (Week 5)

### 4.1 Unit Testing
- [ ] Create test cases untuk semua CRUD operations
- [ ] Create test cases untuk authentication
- [ ] Create test cases untuk authorization
- [ ] Create test cases untuk data validation
- [ ] Run tests dan fix bugs

**Test Cases:**
```
1. Create karyawan dengan valid data ✓
2. Create karyawan dengan duplicate kode ✗
3. Create karyawan dengan invalid posisi ✗
4. Update karyawan dengan valid data ✓
5. Delete karyawan (soft delete) ✓
6. Login dengan valid credentials ✓
7. Login dengan invalid password ✗
8. Access protected page tanpa login ✗
```

### 4.2 Integration Testing
- [ ] Test relasi antar tabel
- [ ] Test FK constraints
- [ ] Test cascade delete/update
- [ ] Test transaction handling
- [ ] Test concurrent access

### 4.3 Security Testing
- [ ] Test SQL injection
- [ ] Test XSS attacks
- [ ] Test CSRF attacks
- [ ] Test authentication bypass
- [ ] Test authorization bypass
- [ ] Test password security

### 4.4 Documentation
- [ ] Update API documentation
- [ ] Create database schema documentation
- [ ] Create user guide untuk master karyawan
- [ ] Create developer guide untuk maintenance
- [ ] Create troubleshooting guide

---

## 📋 DETAILED IMPLEMENTATION TASKS

### Task 1: Password Hashing (Priority 1)

**Objective:** Implement password hashing untuk security

**Steps:**
1. Create migration script `migrate_password_hash.php`
2. Hash existing passwords di `tb_user_account`
3. Hash existing passwords di `tbuser`
4. Update `cek_login.php` untuk gunakan `password_verify()`
5. Update password change functionality
6. Test login dengan hashed password
7. Document implementation

**Timeline:** 2-3 hari

**Files:**
- `migrate_password_hash.php` (NEW)
- `aplikasi/cek_login.php` (MODIFY)
- `aplikasi/_admin/user_edit.php` (MODIFY)

---

### Task 2: SQL Injection Protection (Priority 1)

**Objective:** Convert semua queries ke prepared statements

**Steps:**
1. Audit semua PHP files untuk find vulnerable queries
2. Create helper function untuk prepared statements
3. Convert queries satu per satu
4. Test dengan SQL injection payloads
5. Document changes

**Timeline:** 3-5 hari

**Files to Audit:**
- `aplikasi/_admincab/master-posisi.php`
- `aplikasi/_admincab/master_karyawan*.php`
- `aplikasi/_admin/user*.php`
- All AJAX handlers

---

### Task 3: Foreign Key Constraints (Priority 1)

**Objective:** Add FK constraints untuk data integrity

**Steps:**
1. Create migration SQL script
2. Backup database
3. Run migration script
4. Test dengan invalid data
5. Add error handling
6. Document constraints

**Timeline:** 1-2 hari

**SQL:**
```sql
-- See SQL_RELASI_MASTER_KARYAWAN_DAN_USERS.sql
```

---

### Task 4: Database Indexes (Priority 2)

**Objective:** Improve query performance

**Steps:**
1. Identify slow queries
2. Create migration SQL script
3. Run migration script
4. Test performance improvement
5. Monitor index usage
6. Document index strategy

**Timeline:** 1 hari

**SQL:**
```sql
-- See SQL_RELASI_MASTER_KARYAWAN_DAN_USERS.sql
```

---

### Task 5: Migrate from tbuser (Priority 2)

**Objective:** Consolidate user management ke tb_user_account

**Steps:**
1. Create backup dari tbuser
2. Create migration script
3. Test migration dengan sample data
4. Update all PHP files
5. Test login dengan semua user
6. Monitor untuk 1 bulan
7. Delete tbuser

**Timeline:** 5-7 hari

**Migration Script:**
```php
// See migration logic in Phase 3.1
```

---

### Task 6: Audit Logging (Priority 2)

**Objective:** Track semua user activities

**Steps:**
1. Create logging function
2. Add logging ke CRUD operations
3. Create activity log viewer
4. Create audit reports
5. Test logging functionality

**Timeline:** 3-4 hari

**Logging Function:**
```php
// See Phase 3.3
```

---

## 📊 IMPLEMENTATION ROADMAP

```
Week 1: Security Fixes
├── Day 1-2: Password Hashing
├── Day 3-4: SQL Injection Protection
└── Day 5: Foreign Key Constraints

Week 2: Database Optimization
├── Day 1: Database Indexes
└── Day 2-3: Data Integrity Checks

Week 3-4: Code Refactoring
├── Day 1-3: Migrate from tbuser
├── Day 4-5: Standardize Naming
└── Day 6-7: Implement Audit Logging

Week 5: Testing & Documentation
├── Day 1-2: Unit Testing
├── Day 3: Integration Testing
├── Day 4: Security Testing
└── Day 5: Documentation
```

---

## 🎯 SUCCESS CRITERIA

### Phase 1 (Security):
- ✅ All passwords hashed
- ✅ No SQL injection vulnerabilities
- ✅ FK constraints enforced
- ✅ Data integrity maintained

### Phase 2 (Optimization):
- ✅ Query performance improved 50%+
- ✅ No orphan records
- ✅ No duplicate data

### Phase 3 (Refactoring):
- ✅ tbuser fully migrated
- ✅ Naming convention standardized
- ✅ All activities logged
- ✅ All inputs validated

### Phase 4 (Testing):
- ✅ All unit tests pass
- ✅ All integration tests pass
- ✅ All security tests pass
- ✅ Documentation complete

---

## 📝 NOTES & CONSIDERATIONS

1. **Backward Compatibility:** Ensure semua changes backward compatible
2. **Data Migration:** Test migration thoroughly sebelum production
3. **User Communication:** Inform users tentang password reset requirement
4. **Monitoring:** Monitor system closely setelah deployment
5. **Rollback Plan:** Prepare rollback plan untuk setiap phase
6. **Testing Environment:** Test semua changes di staging dulu

---

## 📞 CONTACT & SUPPORT

**For Questions or Issues:**
- Review documentation files
- Check SQL scripts
- Test dengan sample data
- Monitor activity logs

---

**Last Updated:** 16 November 2025  
**Version:** 1.0  
**Status:** Planning Phase - Ready for Implementation
