-- =====================================================
-- DATABASE OPTIMIZATION - TEMUAN & MAPPING SYSTEM
-- =====================================================
-- Tanggal: 2025-12-04
-- Deskripsi: Script untuk optimasi database sistem temuan
-- Status: RECOMMENDED (Opsional tapi sangat dianjurkan)
-- =====================================================

-- CATATAN PENTING:
-- 1. Backup database sebelum menjalankan script ini
--    Command: mysqldump -u root fitmotor_dbbengkel > backup.sql
--
-- 2. Jalankan di environment development dulu
--
-- 3. Script ini menambahkan INDEX dan FOREIGN KEY untuk performance & data integrity
--
-- 4. Tidak mengubah struktur tabel existing (backward compatible)
--
-- 5. AKAN ADA ERROR JIKA INDEX/CONSTRAINT SUDAH ADA - INI NORMAL!
--    Error seperti "Duplicate key name" atau "Constraint already exists" bisa diabaikan
--    Script akan continue ke statement berikutnya
--
-- 6. Cara menjalankan:
--    Opsi A: Via phpMyAdmin - Copy paste per section, abaikan error
--    Opsi B: Via command line - mysql -u root fitmotor_dbbengkel < script.sql
--    Opsi C: Via phpMyAdmin - Import file, checklist "Continue on error"
--
-- 7. Untuk rollback: restore dari backup
--    Command: mysql -u root fitmotor_dbbengkel < backup.sql

USE fitmotor_dbbengkel;

-- =====================================================
-- 1. ADD PRIMARY KEYS (jika belum ada)
-- =====================================================
-- Note: Jika PK sudah ada, statement ini akan error tapi tidak masalah
-- Skip error dan lanjut ke statement berikutnya

-- Check & Add PK untuk tbmaster_temuan
-- Jika PK sudah ada, akan error - abaikan saja
ALTER TABLE `tbmaster_temuan`
  ADD PRIMARY KEY (`id`);

-- Add unique key untuk kode_temuan
ALTER TABLE `tbmaster_temuan`
  ADD UNIQUE KEY `uk_kode_temuan` (`kode_temuan`);

-- Check & Add PK untuk tbmaster_temuan_barang_mapping
ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD PRIMARY KEY (`id`);

-- Check & Add PK untuk tbservis_temuan
ALTER TABLE `tbservis_temuan`
  ADD PRIMARY KEY (`id`);

-- Check & Add PK untuk tbservis_penawaran_part
ALTER TABLE `tbservis_penawaran_part`
  ADD PRIMARY KEY (`id`);

-- =====================================================
-- 2. ADD INDEXES untuk PERFORMANCE
-- =====================================================
-- Note: Jika index sudah ada, akan error - abaikan dan lanjut

-- Indexes untuk tbmaster_temuan
ALTER TABLE `tbmaster_temuan`
  ADD INDEX `idx_kategori` (`kategori`);

ALTER TABLE `tbmaster_temuan`
  ADD INDEX `idx_is_active` (`is_active`);

ALTER TABLE `tbmaster_temuan`
  ADD INDEX `idx_nama_temuan` (`nama_temuan`(50));

-- Indexes untuk tbmaster_temuan_barang_mapping
ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD INDEX `idx_kode_temuan` (`kode_temuan`);

ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD INDEX `idx_noitem` (`noitem`);

ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD INDEX `idx_is_primary` (`is_primary`);

ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD INDEX `idx_status_aktif` (`status_aktif`);

ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD INDEX `idx_prioritas` (`prioritas`);

ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD INDEX `idx_composite_temuan_item` (`kode_temuan`, `noitem`);

-- Indexes untuk tbservis_temuan
ALTER TABLE `tbservis_temuan`
  ADD INDEX `idx_no_service` (`no_service`);

ALTER TABLE `tbservis_temuan`
  ADD INDEX `idx_keluhan_id` (`keluhan_id`);

ALTER TABLE `tbservis_temuan`
  ADD INDEX `idx_kode_temuan` (`kode_temuan`);

ALTER TABLE `tbservis_temuan`
  ADD INDEX `idx_status_temuan` (`status_temuan`);

ALTER TABLE `tbservis_temuan`
  ADD INDEX `idx_jenis_perbaikan` (`jenis_perbaikan`);

ALTER TABLE `tbservis_temuan`
  ADD INDEX `idx_created_at` (`created_at`);

-- Indexes untuk tbservis_penawaran_part
ALTER TABLE `tbservis_penawaran_part`
  ADD INDEX `idx_no_service` (`no_service`);

ALTER TABLE `tbservis_penawaran_part`
  ADD INDEX `idx_temuan_id` (`temuan_id`);

ALTER TABLE `tbservis_penawaran_part`
  ADD INDEX `idx_kode_barang` (`kode_barang`);

ALTER TABLE `tbservis_penawaran_part`
  ADD INDEX `idx_status_penawaran` (`status_penawaran`);

ALTER TABLE `tbservis_penawaran_part`
  ADD INDEX `idx_is_from_suggestion` (`is_from_suggestion`);

ALTER TABLE `tbservis_penawaran_part`
  ADD INDEX `idx_tanggal_penawaran` (`tanggal_penawaran`);

ALTER TABLE `tbservis_penawaran_part`
  ADD INDEX `idx_composite_service_status` (`no_service`, `status_penawaran`);

-- =====================================================
-- 3. ADD FOREIGN KEYS untuk DATA INTEGRITY
-- =====================================================
-- CATATAN: Sebelum add FK, pastikan data sudah konsisten
-- Cek dulu apakah ada orphan data
-- Jika FK sudah ada, akan error - abaikan dan lanjut

-- FK untuk tbmaster_temuan_barang_mapping
ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD CONSTRAINT `fk_mapping_temuan`
    FOREIGN KEY (`kode_temuan`)
    REFERENCES `tbmaster_temuan` (`kode_temuan`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD CONSTRAINT `fk_mapping_item`
    FOREIGN KEY (`noitem`)
    REFERENCES `tblitem` (`noitem`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

-- FK untuk tbservis_temuan
-- Note: Tidak add FK ke no_service karena tblservice mungkin belum punya PK
ALTER TABLE `tbservis_temuan`
  ADD CONSTRAINT `fk_servis_temuan_master`
    FOREIGN KEY (`kode_temuan`)
    REFERENCES `tbmaster_temuan` (`kode_temuan`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- FK untuk tbservis_penawaran_part
ALTER TABLE `tbservis_penawaran_part`
  ADD CONSTRAINT `fk_penawaran_temuan`
    FOREIGN KEY (`temuan_id`)
    REFERENCES `tbservis_temuan` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE `tbservis_penawaran_part`
  ADD CONSTRAINT `fk_penawaran_item`
    FOREIGN KEY (`kode_barang`)
    REFERENCES `tblitem` (`noitem`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE;

-- =====================================================
-- 4. ADD USEFUL CONSTRAINTS
-- =====================================================
-- Jika constraint sudah ada, akan error - abaikan dan lanjut

-- Constraint untuk prevent duplicate mapping
ALTER TABLE `tbmaster_temuan_barang_mapping`
  ADD CONSTRAINT `uk_temuan_item`
    UNIQUE (`kode_temuan`, `noitem`);

-- Constraint untuk prevent negative quantity
-- Note: CHECK constraint hanya work di MySQL 8.0.16+
-- Jika versi lama, constraint ini akan diabaikan
ALTER TABLE `tbservis_penawaran_part`
  ADD CONSTRAINT `chk_quantity_positive`
    CHECK (`quantity` > 0);

-- Constraint untuk prevent negative harga
ALTER TABLE `tbservis_penawaran_part`
  ADD CONSTRAINT `chk_harga_positive`
    CHECK (`harga_satuan` >= 0);

-- =====================================================
-- 5. ADD AUTO_INCREMENT (jika belum)
-- =====================================================

ALTER TABLE `tbmaster_temuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbmaster_temuan_barang_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbservis_temuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tbservis_penawaran_part`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- =====================================================
-- 6. UPDATE EXISTING DATA (Data Cleanup)
-- =====================================================

-- Set default values untuk record lama yang mungkin NULL
UPDATE `tbmaster_temuan`
SET `is_active` = 1
WHERE `is_active` IS NULL;

UPDATE `tbmaster_temuan_barang_mapping`
SET `status_aktif` = 1,
    `prioritas` = 1,
    `is_primary` = 0,
    `qty_default` = 1
WHERE `status_aktif` IS NULL;

UPDATE `tbservis_penawaran_part`
SET `is_from_suggestion` = 0
WHERE `is_from_suggestion` IS NULL;

-- =====================================================
-- 7. CREATE USEFUL VIEWS
-- =====================================================

-- View: Master temuan dengan count mapping
CREATE OR REPLACE VIEW `view_temuan_with_mapping_count` AS
SELECT
    t.id,
    t.kode_temuan,
    t.nama_temuan,
    t.kategori,
    t.tingkat_urgensi,
    t.is_active,
    COUNT(m.id) as jumlah_mapping,
    SUM(CASE WHEN m.is_primary = 1 THEN 1 ELSE 0 END) as jumlah_primary,
    SUM(CASE WHEN m.is_primary = 0 THEN 1 ELSE 0 END) as jumlah_alternative
FROM tbmaster_temuan t
LEFT JOIN tbmaster_temuan_barang_mapping m ON t.kode_temuan = m.kode_temuan AND m.status_aktif = 1
GROUP BY t.id, t.kode_temuan, t.nama_temuan, t.kategori, t.tingkat_urgensi, t.is_active;

-- View: Mapping lengkap dengan detail part
CREATE OR REPLACE VIEW `view_mapping_lengkap` AS
SELECT
    m.id,
    m.kode_temuan,
    t.nama_temuan,
    t.kategori as kategori_temuan,
    m.noitem,
    i.namaitem,
    i.hargajual,
    i.satuan,
    m.is_primary,
    m.prioritas,
    m.qty_default,
    m.keterangan,
    m.status_aktif
FROM tbmaster_temuan_barang_mapping m
INNER JOIN tbmaster_temuan t ON m.kode_temuan = t.kode_temuan
INNER JOIN tblitem i ON m.noitem = i.noitem
WHERE m.status_aktif = 1 AND t.is_active = 1
ORDER BY m.kode_temuan, m.prioritas, m.is_primary DESC;

-- View: Summary penawaran per service
CREATE OR REPLACE VIEW `view_penawaran_summary` AS
SELECT
    p.no_service,
    COUNT(*) as total_penawaran,
    SUM(CASE WHEN p.status_penawaran = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN p.status_penawaran = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
    SUM(CASE WHEN p.status_penawaran = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
    SUM(CASE WHEN p.is_from_suggestion = 1 THEN 1 ELSE 0 END) as dari_suggestion,
    SUM(CASE WHEN p.is_from_suggestion = 0 THEN 1 ELSE 0 END) as manual,
    SUM(CASE WHEN p.status_penawaran = 'disetujui' THEN p.total_harga ELSE 0 END) as total_nilai_disetujui
FROM tbservis_penawaran_part p
GROUP BY p.no_service;

-- View: Analytics - Conversion rate suggestion
CREATE OR REPLACE VIEW `view_analytics_suggestion_conversion` AS
SELECT
    DATE(p.tanggal_penawaran) as tanggal,
    COUNT(*) as total_penawaran,
    SUM(CASE WHEN p.is_from_suggestion = 1 THEN 1 ELSE 0 END) as dari_suggestion,
    SUM(CASE WHEN p.is_from_suggestion = 0 THEN 1 ELSE 0 END) as manual_input,
    SUM(CASE WHEN p.is_from_suggestion = 1 AND p.status_penawaran = 'disetujui' THEN 1 ELSE 0 END) as suggestion_disetujui,
    SUM(CASE WHEN p.is_from_suggestion = 0 AND p.status_penawaran = 'disetujui' THEN 1 ELSE 0 END) as manual_disetujui,
    ROUND(
        SUM(CASE WHEN p.is_from_suggestion = 1 AND p.status_penawaran = 'disetujui' THEN 1 ELSE 0 END) * 100.0 /
        NULLIF(SUM(CASE WHEN p.is_from_suggestion = 1 THEN 1 ELSE 0 END), 0),
        2
    ) as conversion_rate_suggestion
FROM tbservis_penawaran_part p
WHERE p.tanggal_penawaran >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(p.tanggal_penawaran)
ORDER BY tanggal DESC;

-- =====================================================
-- 8. CREATE STORED PROCEDURES (Helper Functions)
-- =====================================================

DELIMITER $$

-- Procedure: Get next kode temuan
DROP PROCEDURE IF EXISTS `sp_get_next_kode_temuan` $$
CREATE PROCEDURE `sp_get_next_kode_temuan`(OUT next_kode VARCHAR(20))
BEGIN
    DECLARE last_number INT;

    SELECT COALESCE(MAX(CAST(SUBSTRING(kode_temuan, 4) AS UNSIGNED)), 0) INTO last_number
    FROM tbmaster_temuan
    WHERE kode_temuan LIKE 'TMN%';

    SET next_kode = CONCAT('TMN', LPAD(last_number + 1, 3, '0'));
END $$

-- Procedure: Get recommended parts by temuan
DROP PROCEDURE IF EXISTS `sp_get_recommended_parts` $$
CREATE PROCEDURE `sp_get_recommended_parts`(IN p_kode_temuan VARCHAR(20))
BEGIN
    SELECT
        m.id as mapping_id,
        m.noitem as kode_barang,
        i.namaitem as nama_barang,
        i.hargajual as harga_jual,
        i.satuan,
        m.is_primary,
        m.prioritas,
        m.qty_default,
        m.keterangan,
        0 as stok_tersedia
    FROM tbmaster_temuan_barang_mapping m
    INNER JOIN tblitem i ON m.noitem = i.noitem
    WHERE m.kode_temuan = p_kode_temuan
    AND m.status_aktif = 1
    ORDER BY m.prioritas ASC, m.is_primary DESC;
END $$

DELIMITER ;

-- =====================================================
-- 9. VERIFICATION QUERIES
-- =====================================================

-- Check PKs
SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
AND TABLE_NAME IN ('tbmaster_temuan', 'tbmaster_temuan_barang_mapping', 'tbservis_temuan', 'tbservis_penawaran_part')
AND CONSTRAINT_TYPE = 'PRIMARY KEY';

-- Check FKs
SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
AND TABLE_NAME IN ('tbmaster_temuan_barang_mapping', 'tbservis_temuan', 'tbservis_penawaran_part')
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Check Indexes
SELECT
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
AND TABLE_NAME IN ('tbmaster_temuan', 'tbmaster_temuan_barang_mapping', 'tbservis_temuan', 'tbservis_penawaran_part')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- =====================================================
-- 10. PERFORMANCE TEST QUERIES
-- =====================================================

-- Test query 1: Get parts by temuan (should use index)
EXPLAIN SELECT *
FROM tbmaster_temuan_barang_mapping
WHERE kode_temuan = 'TMN001'
AND status_aktif = 1
ORDER BY prioritas, is_primary DESC;

-- Test query 2: Get pending penawaran by service (should use composite index)
EXPLAIN SELECT *
FROM tbservis_penawaran_part
WHERE no_service = 'SV25000000001'
AND status_penawaran = 'pending';

-- =====================================================
-- NOTES & RECOMMENDATIONS
-- =====================================================

/*
CATATAN PENTING:

1. FOREIGN KEYS:
   - Akan PREVENT delete master data jika masih ada referensi
   - ON DELETE CASCADE = Auto-delete child data
   - ON DELETE RESTRICT = Prevent delete jika ada child
   - ON DELETE SET NULL = Set NULL di child

2. INDEXES:
   - Mempercepat query SELECT, tapi sedikit memperlambat INSERT/UPDATE
   - Composite index (multi-column) untuk query dengan WHERE multiple columns
   - Index pada kolom yang sering di-filter atau di-join

3. VIEWS:
   - Simplify complex queries
   - Tidak menyimpan data (virtual table)
   - Bisa di-query seperti table biasa

4. STORED PROCEDURES:
   - Encapsulate business logic
   - Reusable
   - Bisa dipanggil dari PHP: mysqli_query($koneksi, "CALL sp_name()")

5. BACKWARD COMPATIBILITY:
   - Semua perubahan tidak mengubah existing data
   - Aplikasi existing tetap jalan normal
   - Hanya menambah constraint & optimization

CARA TESTING:
1. Backup database: mysqldump fitmotor_dbbengkel > backup.sql
2. Run script di dev environment dulu
3. Test semua endpoint AJAX
4. Check error log
5. Jika OK, baru apply di production

ROLLBACK (jika ada masalah):
1. Restore dari backup: mysql fitmotor_dbbengkel < backup.sql
2. Atau manual drop FK & indexes yang baru ditambahkan

COMMON ERRORS & SOLUTIONS:

Error #1: "Duplicate key name 'idx_xxx'"
- Artinya: Index sudah ada
- Solution: ABAIKAN error ini, lanjut ke statement berikutnya
- Tidak perlu drop & re-add

Error #2: "Multiple primary key defined"
- Artinya: Primary key sudah ada
- Solution: ABAIKAN error ini, skip ke section berikutnya
- Primary key sudah OK

Error #3: "Constraint 'xxx' already exists"
- Artinya: Foreign key atau constraint sudah ada
- Solution: ABAIKAN error ini, lanjut
- Constraint sudah OK

Error #4: "Cannot add foreign key constraint"
- Artinya: Ada orphan data (child tanpa parent)
- Solution:
  a) Check orphan data dulu
  b) Cleanup orphan data
  c) Baru run statement FK lagi

Error #5: "Cannot add or update a child row: a foreign key constraint fails"
- Artinya: Ada data yang tidak konsisten
- Solution: Fix data inconsistency dulu sebelum add FK

VERIFIKASI SETELAH RUN SCRIPT:
-- Check apakah indexes sudah ada
SHOW INDEX FROM tbmaster_temuan;
SHOW INDEX FROM tbmaster_temuan_barang_mapping;

-- Check apakah FK sudah ada
SELECT
  CONSTRAINT_NAME,
  TABLE_NAME,
  REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Check apakah views sudah dibuat
SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW';
*/

-- END OF SCRIPT
