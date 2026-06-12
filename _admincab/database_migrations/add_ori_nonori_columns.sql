-- Database migration for ORI/NON-ORI item classification system
-- Execute this SQL to add new columns to tblitem table

-- Add new columns to tblitem table
ALTER TABLE `tblitem` 
ADD COLUMN `tipe_item` ENUM('ORI', 'NON_ORI') DEFAULT 'NON_ORI' COMMENT 'ORI=Genuine Part, NON_ORI=Aftermarket/Imitasi',
ADD COLUMN `merek` VARCHAR(50) NULL COMMENT 'Merek untuk ORI (Honda, Yamaha, Suzuki, dll)',
ADD COLUMN `kode_part_resmi` VARCHAR(50) NULL COMMENT 'Kode part number resmi untuk ORI',
ADD COLUMN `nama_part_resmi` VARCHAR(100) NULL COMMENT 'Nama part resmi sesuai catalog',
ADD COLUMN `penggunaan_motor` VARCHAR(100) NULL COMMENT 'Penggunaan motor untuk NON-ORI',
ADD COLUMN `merek_tipe` VARCHAR(100) NULL COMMENT 'Merek/Tipe/Ukuran untuk NON-ORI',
ADD COLUMN `kategori_rak` VARCHAR(10) NULL COMMENT 'Kategori rak untuk auto-generate code NON-ORI',
ADD COLUMN `status_validasi` ENUM('pending_validation', 'validated', 'rejected') DEFAULT 'pending_validation' COMMENT 'Status validasi item',
ADD COLUMN `created_by` INT(11) NULL COMMENT 'User ID yang membuat item',
ADD COLUMN `validated_by` INT(11) NULL COMMENT 'User ID yang memvalidasi item',
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create index for better performance
ALTER TABLE `tblitem` 
ADD INDEX `idx_tipe_item` (`tipe_item`),
ADD INDEX `idx_merek` (`merek`),
ADD INDEX `idx_kategori_rak` (`kategori_rak`),
ADD INDEX `idx_status_validasi` (`status_validasi`);

-- Create table for kategori rak (if not exists)
CREATE TABLE IF NOT EXISTS `tbkategori_rak` (
  `kode` VARCHAR(10) PRIMARY KEY,
  `nama_kategori` VARCHAR(50) NOT NULL,
  `deskripsi` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Insert default kategori rak
INSERT INTO `tbkategori_rak` (`kode`, `nama_kategori`, `deskripsi`) VALUES
('KB', 'Kabel', 'Kategori untuk produk kabel'),
('EL', 'Kelistrikan', 'Kategori untuk produk kelistrikan'),
('RM', 'Rem', 'Kategori untuk produk rem'),
('MS', 'Mesin', 'Kategori untuk produk mesin'),
('CV', 'CVT', 'Kategori untuk produk CVT'),
('RD', 'Roda', 'Kategori untuk produk roda'),
('CR', 'Carbu', 'Kategori untuk produk karburator'),
('FL', 'Filter', 'Kategori untuk produk filter'),
('CH', 'Cairan', 'Kategori untuk produk cairan'),
('BD', 'Baud', 'Kategori untuk produk baud')
ON DUPLICATE KEY UPDATE 
nama_kategori = VALUES(nama_kategori), 
deskripsi = VALUES(deskripsi);

-- Create table for tracking validation history
CREATE TABLE IF NOT EXISTS `tbitem_validation_log` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `noitem` VARCHAR(20) NOT NULL,
  `status_lama` VARCHAR(20) NULL,
  `status_baru` VARCHAR(20) NOT NULL,
  `keterangan` TEXT NULL,
  `validated_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`noitem`) REFERENCES `tblitem`(`noitem`) ON DELETE CASCADE,
  FOREIGN KEY (`validated_by`) REFERENCES `tbuser`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Create view for easy item listing with ORI/NON-ORI classification
CREATE OR REPLACE VIEW `view_item_classified` AS
SELECT 
    i.noitem,
    i.namaitem,
    i.tipe_item,
    i.merek,
    i.kode_part_resmi,
    i.nama_part_resmi,
    i.penggunaan_motor,
    i.merek_tipe,
    i.kategori_rak,
    kr.nama_kategori,
    i.hargapokok,
    i.hargajual,
    i.quantity,
    i.status_validasi,
    i.statusitem,
    u1.nama_user as created_by_name,
    u2.nama_user as validated_by_name,
    i.created_at,
    i.updated_at
FROM tblitem i
LEFT JOIN tbkategori_rak kr ON i.kategori_rak = kr.kode
LEFT JOIN tbuser u1 ON i.created_by = u1.id
LEFT JOIN tbuser u2 ON i.validated_by = u2.id
ORDER BY i.created_at DESC;

-- Update existing items to set default tipe_item based on existing data
-- Items with specific patterns are likely ORI
UPDATE tblitem SET 
    tipe_item = 'ORI',
    status_validasi = 'validated'
WHERE 
    jenis = 'ORISIN' 
    OR namaitem LIKE '%HONDA%' 
    OR namaitem LIKE '%YAMAHA%' 
    OR namaitem LIKE '%SUZUKI%' 
    OR namaitem LIKE '%KAWASAKI%'
    OR namaitem LIKE '%GENUINE%'
    OR namaitem LIKE '%ORIGINAL%';

-- Items with IMI or IMITAS are likely NON-ORI
UPDATE tblitem SET 
    tipe_item = 'NON_ORI',
    kategori_rak = CASE 
        WHEN namaitem LIKE '%KABEL%' THEN 'KB'
        WHEN namaitem LIKE '%LISTRIK%' OR namaitem LIKE '%LAMPU%' THEN 'EL'
        WHEN namaitem LIKE '%REM%' OR namaitem LIKE '%BRAKE%' THEN 'RM'
        WHEN namaitem LIKE '%MESIN%' OR namaitem LIKE '%ENGINE%' THEN 'MS'
        WHEN namaitem LIKE '%CVT%' THEN 'CV'
        WHEN namaitem LIKE '%RODA%' OR namaitem LIKE '%WHEEL%' THEN 'RD'
        WHEN namaitem LIKE '%CARBU%' OR namaitem LIKE '%KARBU%' THEN 'CR'
        WHEN namaitem LIKE '%FILTER%' THEN 'FL'
        WHEN namaitem LIKE '%OLI%' OR namaitem LIKE '%CAIRAN%' THEN 'CH'
        WHEN namaitem LIKE '%BAUD%' OR namaitem LIKE '%MUR%' THEN 'BD'
        ELSE NULL
    END,
    status_validasi = 'pending_validation'
WHERE 
    jenis = 'IMITAS' 
    OR namaitem LIKE '%IMI%' 
    OR namaitem LIKE '%IMITASI%'
    OR namaitem LIKE '%KW%';

-- Create trigger to auto-generate code for NON-ORI items
DELIMITER $$

CREATE TRIGGER `auto_generate_nonori_code` 
BEFORE INSERT ON `tblitem`
FOR EACH ROW 
BEGIN
    DECLARE next_number INT;
    
    -- Only for NON-ORI items with empty noitem
    IF NEW.tipe_item = 'NON_ORI' AND (NEW.noitem IS NULL OR NEW.noitem = '') THEN
        -- Get the next number for this category
        SELECT COALESCE(MAX(CAST(SUBSTRING(noitem, 6) AS UNSIGNED)), 0) + 1
        INTO next_number
        FROM tblitem 
        WHERE noitem LIKE CONCAT('IM-', NEW.kategori_rak, '%')
        AND tipe_item = 'NON_ORI';
        
        -- Generate the new code
        SET NEW.noitem = CONCAT('IM-', NEW.kategori_rak, LPAD(next_number, 4, '0'));
        
        -- Format the name if not already formatted
        IF NEW.penggunaan_motor IS NOT NULL AND NEW.namaitem NOT LIKE '%IMI' THEN
            SET NEW.namaitem = CONCAT(NEW.namaitem, ' ', NEW.penggunaan_motor, ' IMI');
        END IF;
    END IF;
END$$

DELIMITER ;

-- Insert sample data for testing
INSERT INTO `tblitem` (
    `namaitem`, `tipe_item`, `merek`, `kode_part_resmi`, `nama_part_resmi`,
    `jenis`, `satuan`, `hargapokok`, `hargajual`, `statusitem`, `status_validasi`,
    `created_by`
) VALUES (
    'KAMPAS REM DEPAN BEAT', 'ORI', 'HONDA', '06455-KVB-900', 
    'BRAKE PAD SET, FR.', 'ORISIN', 'PSG', 85000, 125000, '1', 'validated', 1
);

INSERT INTO `tblitem` (
    `namaitem`, `tipe_item`, `penggunaan_motor`, `merek_tipe`, `kategori_rak`,
    `jenis`, `satuan`, `hargapokok`, `hargajual`, `statusitem`, `status_validasi`,
    `created_by`
) VALUES (
    'KABEL GAS H. BEAT IMI', 'NON_ORI', 'H. BEAT', 'ASPIRA', 'KB',
    'IMITAS', 'PCS', 15000, 25000, '1', 'pending_validation', 1
);

-- Create stored procedure for item validation
DELIMITER $$

CREATE PROCEDURE `ValidateItem`(
    IN p_noitem VARCHAR(20),
    IN p_status VARCHAR(20),
    IN p_keterangan TEXT,
    IN p_validated_by INT
)
BEGIN
    DECLARE old_status VARCHAR(20);
    
    -- Get current status
    SELECT status_validasi INTO old_status FROM tblitem WHERE noitem = p_noitem;
    
    -- Update item status
    UPDATE tblitem SET 
        status_validasi = p_status,
        validated_by = p_validated_by,
        updated_at = CURRENT_TIMESTAMP
    WHERE noitem = p_noitem;
    
    -- Log the validation
    INSERT INTO tbitem_validation_log (noitem, status_lama, status_baru, keterangan, validated_by)
    VALUES (p_noitem, old_status, p_status, p_keterangan, p_validated_by);
    
END$$

DELIMITER ;

-- Grant necessary permissions (adjust as needed)
-- GRANT SELECT, INSERT, UPDATE ON view_item_classified TO 'your_user'@'localhost';