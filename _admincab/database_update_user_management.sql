-- ========================================
-- DATABASE UPDATE SCRIPT FOR USER MANAGEMENT
-- File: database_update_user_management.sql
-- Date: $(date)
-- Purpose: Update database structure for enhanced user management system
-- ========================================

-- 1. UPDATE EXISTING TABLES
-- ========================================

-- Update tbuser table to add more user roles and fields
ALTER TABLE `tbuser`
ADD COLUMN `role_name` VARCHAR(50) DEFAULT NULL COMMENT 'Nama role untuk display',
ADD COLUMN `department` VARCHAR(50) DEFAULT NULL COMMENT 'Departemen user',
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN `last_login` TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN `is_active` ENUM('active','inactive') DEFAULT 'active';

-- Update existing user access levels
UPDATE `tbuser` SET `role_name` = 'Administrator', `department` = 'Management' WHERE `user_akses` = 1;
UPDATE `tbuser` SET `role_name` = 'CS & Kasir', `department` = 'Front Office' WHERE `user_akses` = 2;
-- Migrate existing Kasir users to CS & Kasir role
UPDATE `tbuser` SET `user_akses` = 2, `role_name` = 'CS & Kasir', `department` = 'Front Office' WHERE `user_akses` = 3;
UPDATE `tbuser` SET `role_name` = 'Mekanik', `department` = 'Workshop' WHERE `user_akses` = 4;
UPDATE `tbuser` SET `role_name` = 'Pengadaan', `department` = 'Purchasing' WHERE `user_akses` = 5;
UPDATE `tbuser` SET `role_name` = 'CRM', `department` = 'Marketing' WHERE `user_akses` = 6;
UPDATE `tbuser` SET `role_name` = 'Manajemen', `department` = 'Management' WHERE `user_akses` = 7;
UPDATE `tbuser` SET `role_name` = 'Keuangan', `department` = 'Finance' WHERE `user_akses` = 8;
UPDATE `tbuser` SET `role_name` = 'HRD', `department` = 'Human Resource' WHERE `user_akses` = 9;

-- 2. CREATE NEW ROLE MANAGEMENT TABLES
-- ========================================

-- Table for role definitions
CREATE TABLE IF NOT EXISTS `tb_user_roles` (
  `role_id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_code` INT(11) NOT NULL UNIQUE,
  `role_name` VARCHAR(50) NOT NULL,
  `role_description` TEXT DEFAULT NULL,
  `department` VARCHAR(50) DEFAULT NULL,
  `permissions` JSON DEFAULT NULL COMMENT 'JSON array of permissions',
  `is_active` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  KEY `idx_role_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default roles
INSERT INTO `tb_user_roles` (`role_code`, `role_name`, `role_description`, `department`, `permissions`) VALUES
(1, 'Administrator', 'Full system access', 'Management', '["all"]'),
(2, 'CS & Kasir', 'Customer service and cashier operations', 'Front Office', '["service_read","service_create","customer_read","customer_create","payment_read","payment_create","invoice_read"]'),
(4, 'Mekanik', 'Workshop operations', 'Workshop', '["service_read","service_update_progress","task_read","task_update"]'),
(5, 'Pengadaan', 'Purchasing operations', 'Purchasing', '["purchase_read","purchase_create","inventory_read"]'),
(6, 'CRM', 'Customer relationship management', 'Marketing', '["customer_read","customer_update","marketing_read"]'),
(7, 'Manajemen', 'Management operations', 'Management', '["report_read","dashboard_read","analytics_read"]'),
(8, 'Keuangan', 'Financial operations', 'Finance', '["finance_read","finance_create","report_read"]'),
(9, 'HRD', 'Human resource operations', 'Human Resource', '["employee_read","employee_create","payroll_read"]'),
(10, 'Kepala Mekanik', 'Workshop supervisor', 'Workshop', '["service_read","service_update","team_assign","quality_check"]');

-- 3. UPDATE MEKANIK TABLE STRUCTURE
-- ========================================

-- Add missing columns to tblmekanik if they don't exist
ALTER TABLE `tblmekanik`
ADD COLUMN `email` VARCHAR(100) DEFAULT NULL,
ADD COLUMN `tanggal_masuk` DATE DEFAULT NULL,
ADD COLUMN `gaji_pokok` DECIMAL(10,0) DEFAULT NULL,
ADD COLUMN `spesialisasi` TEXT DEFAULT NULL COMMENT 'Spesialisasi keahlian',
ADD COLUMN `sertifikat` TEXT DEFAULT NULL COMMENT 'Sertifikat yang dimiliki',
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create index for better performance
ALTER TABLE `tblmekanik` ADD INDEX `idx_keahlian` (`keahlian`);
ALTER TABLE `tblmekanik` ADD INDEX `idx_status` (`status`);

-- 4. CREATE USER-MEKANIK MAPPING TABLE
-- ========================================

-- Table to link tbuser with tblmekanik for mechanics
CREATE TABLE IF NOT EXISTS `tb_user_mekanik_mapping` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `mekanik_code` VARCHAR(20) NOT NULL,
  `is_primary` ENUM('yes','no') DEFAULT 'yes',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_mekanik_code` (`mekanik_code`),
  FOREIGN KEY (`user_id`) REFERENCES `tbuser`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mekanik_code`) REFERENCES `tblmekanik`(`nomekanik`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. CREATE PERMISSION MANAGEMENT TABLES
-- ========================================

-- Table for detailed permissions
CREATE TABLE IF NOT EXISTS `tb_permissions` (
  `permission_id` INT(11) NOT NULL AUTO_INCREMENT,
  `permission_code` VARCHAR(50) NOT NULL UNIQUE,
  `permission_name` VARCHAR(100) NOT NULL,
  `permission_description` TEXT DEFAULT NULL,
  `module` VARCHAR(50) DEFAULT NULL,
  `is_active` ENUM('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default permissions
INSERT INTO `tb_permissions` (`permission_code`, `permission_name`, `permission_description`, `module`) VALUES
('all', 'Full Access', 'Complete system access', 'system'),
('service_read', 'Read Service', 'View service records', 'service'),
('service_create', 'Create Service', 'Create new service records', 'service'),
('service_update', 'Update Service', 'Modify service records', 'service'),
('service_delete', 'Delete Service', 'Remove service records', 'service'),
('service_update_progress', 'Update Progress', 'Update service progress only', 'service'),
('customer_read', 'Read Customer', 'View customer records', 'customer'),
('customer_create', 'Create Customer', 'Create new customers', 'customer'),
('customer_update', 'Update Customer', 'Modify customer records', 'customer'),
('team_assign', 'Assign Team', 'Assign mechanics to tasks', 'workshop'),
('quality_check', 'Quality Check', 'Perform quality validation', 'workshop'),
('task_read', 'Read Tasks', 'View assigned tasks', 'workshop'),
('task_update', 'Update Tasks', 'Update task status', 'workshop'),
('payment_read', 'Read Payments', 'View payment records', 'finance'),
('payment_create', 'Create Payments', 'Process payments', 'finance'),
('report_read', 'Read Reports', 'View system reports', 'report'),
('dashboard_read', 'Read Dashboard', 'View dashboard data', 'dashboard'),
('user_management', 'User Management', 'Manage users and roles', 'admin'),
('system_config', 'System Configuration', 'Configure system settings', 'admin');

-- Insert additional permissions if missing
INSERT IGNORE INTO `tb_permissions` (`permission_code`, `permission_name`, `permission_description`, `module`) VALUES
('inventory_read', 'Read Inventory', 'View inventory/master items', 'inventory'),
('purchase_read', 'Read Purchases', 'View purchase data', 'purchase'),
('purchase_create', 'Create Purchases', 'Create purchase orders/receipts', 'purchase'),
('finance_read', 'Read Finance', 'View financial data', 'finance'),
('finance_create', 'Create Finance', 'Create financial entries', 'finance'),
('employee_read', 'Read Employees', 'View employee data', 'hr'),
('employee_create', 'Create Employees', 'Create/modify employee data', 'hr'),
('payroll_read', 'Read Payroll', 'View payroll data', 'hr'),
('marketing_read', 'Read Marketing', 'Access marketing/CRM data', 'marketing'),
('invoice_read', 'Read Invoices', 'View invoice data', 'finance'),
('analytics_read', 'Read Analytics', 'View analytics dashboards', 'analytics');

-- 6. UPDATE EXISTING DATA
-- ========================================

-- Link existing mechanics with users (if any exist)
-- This assumes mechanics might have corresponding user accounts
INSERT INTO `tb_user_mekanik_mapping` (`user_id`, `mekanik_code`, `is_primary`)
SELECT u.id, m.nomekanik, 'yes'
FROM `tbuser` u
JOIN `tblmekanik` m ON u.nama_user = m.nama
WHERE u.user_akses IN (4, 10)
ON DUPLICATE KEY UPDATE `is_primary` = 'yes';

-- 7. CREATE INDEXES FOR PERFORMANCE
-- ========================================

-- Add indexes to tbuser
ALTER TABLE `tbuser` ADD INDEX `idx_user_akses` (`user_akses`);
ALTER TABLE `tbuser` ADD INDEX `idx_is_active` (`is_active`);
ALTER TABLE `tbuser` ADD INDEX `idx_department` (`department`);

-- 8. CREATE AUDIT LOG TABLE
-- ========================================

-- Table for user activity logging
CREATE TABLE IF NOT EXISTS `tb_user_activity_log` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `tbuser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. INSERT SAMPLE DATA FOR KEPALA MEKANIK
-- ========================================

-- Add Kepala Mekanik role user (sample)
INSERT INTO `tbuser` (`nama_user`, `password`, `foto_user`, `status_row`, `user_akses`, `role_name`, `department`, `is_active`) VALUES
('kepala_mekanik', '123456', 'file_upload/avatar.png', '0', 10, 'Kepala Mekanik', 'Workshop', 'active');

-- Update existing mechanics to have higher keahlian for head mechanics (if exists)
UPDATE `tblmekanik` SET `keahlian` = '1' WHERE `nomekanik` = 'MK001' LIMIT 1;

-- 10. CREATE VIEWS FOR EASIER QUERIES
-- ========================================

-- View for user details with role information
CREATE OR REPLACE VIEW `view_user_details` AS
SELECT
    u.id,
    u.nama_user,
    u.user_akses,
    u.role_name,
    u.department,
    u.is_active,
    u.last_login,
    ur.role_description,
    ur.permissions,
    CASE
        WHEN u.user_akses IN (4, 10) THEN 'Workshop'
        WHEN u.user_akses IN (1, 7) THEN 'Management'
        WHEN u.user_akses IN (2, 6) THEN 'Front Office'
        WHEN u.user_akses = 8 THEN 'Finance'
        ELSE 'Other'
    END AS role_category
FROM `tbuser` u
LEFT JOIN `tb_user_roles` ur ON u.user_akses = ur.role_code;

-- View for mechanics with user mapping
CREATE OR REPLACE VIEW `view_mekanik_users` AS
SELECT
    m.nomekanik,
    m.nama,
    m.alamat,
    m.telp,
    m.keahlian,
    m.status,
    m.email,
    m.spesialisasi,
    u.nama_user,
    u.user_akses,
    u.is_active as user_active,
    CASE m.keahlian
        WHEN '1' THEN 'Kepala Mekanik'
        WHEN '2' THEN 'Mekanik Senior'
        WHEN '3' THEN 'Mekanik Junior'
        ELSE 'Tidak Ditentukan'
    END as keahlian_text
FROM `tblmekanik` m
LEFT JOIN `tb_user_mekanik_mapping` umm ON m.nomekanik = umm.mekanik_code
LEFT JOIN `tbuser` u ON umm.user_id = u.id;

-- ========================================
-- SCRIPT COMPLETED
-- ========================================

-- Summary:
-- 1. Enhanced tbuser table with additional fields
-- 2. Created role management system
-- 3. Enhanced tblmekanik table structure
-- 4. Created user-mechanic mapping
-- 5. Created permission management
-- 6. Added audit logging capability
-- 7. Created useful views for queries
-- 8. Added proper indexes for performance

-- To execute: Run this script in your MySQL database
-- Note: Always backup your database before running updates!