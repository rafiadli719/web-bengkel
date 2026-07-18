-- ============================================================
-- MIGRATION: Fase 1 & 2 — Restrukturisasi Karyawan
-- Tanggal: 2026-06-27
-- Catatan: tblmekanik dan tbuser_karyawan TIDAK disentuh
-- Jalankan via: app/_tools/migrate_fase1_fase2.php
-- ============================================================

-- ============================================================
-- FASE 1: Master Tables Baru
-- ============================================================

-- 1.1 master_departemen
CREATE TABLE IF NOT EXISTS master_departemen (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    kode      VARCHAR(20) NOT NULL UNIQUE,
    nama      VARCHAR(100) NOT NULL,
    is_active ENUM('active','inactive') DEFAULT 'active'
);

INSERT IGNORE INTO master_departemen (kode, nama) VALUES
('WORKSHOP',    'Workshop / Bengkel'),
('FRONTOFFICE', 'Front Office'),
('PURCHASING',  'Pembelian & Pengadaan'),
('MANAGEMENT',  'Manajemen'),
('FINANCE',     'Keuangan'),
('MARKETING',   'Marketing & CRM'),
('HRD',         'Human Resource');

-- 1.2 master_jabatan
CREATE TABLE IF NOT EXISTS master_jabatan (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    kode_jabatan VARCHAR(20) NOT NULL UNIQUE,
    nama_jabatan VARCHAR(100) NOT NULL,
    kode_posisi  VARCHAR(20) NOT NULL,
    urutan       INT DEFAULT 0,
    is_active    ENUM('active','inactive') DEFAULT 'active'
);

INSERT IGNORE INTO master_jabatan (kode_jabatan, nama_jabatan, kode_posisi, urutan) VALUES
('ADM-1',  'Administrator',    'ADM', 1),
('CS-1',   'Customer Service', 'CS',  1),
('KSR-1',  'Kasir',            'KSR', 1),
('PGD-1',  'Staff Pengadaan',  'PGD', 1),
('CRM-1',  'CRM Staff',        'CRM', 1),
('MNG-1',  'Manager',          'MNG', 1),
('KEU-1',  'Staff Keuangan',   'KEU', 1),
('HRD-1',  'HRD Staff',        'HRD', 1);

-- 1.3 ALTER tb_master_posisi — tambah kolom (safe, tidak ubah kolom lama)
ALTER TABLE tb_master_posisi
    ADD COLUMN IF NOT EXISTS kode_departemen VARCHAR(20) DEFAULT NULL AFTER departemen,
    ADD COLUMN IF NOT EXISTS bisa_login      ENUM('ya','tidak') DEFAULT 'ya' AFTER kode_departemen,
    ADD COLUMN IF NOT EXISTS dapat_insentif  ENUM('ya','tidak') DEFAULT 'tidak' AFTER bisa_login;

UPDATE tb_master_posisi SET kode_departemen='FRONTOFFICE', bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='ADM';
UPDATE tb_master_posisi SET kode_departemen='FRONTOFFICE', bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='CS';
UPDATE tb_master_posisi SET kode_departemen='FRONTOFFICE', bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='KSR';
UPDATE tb_master_posisi SET kode_departemen='WORKSHOP',    bisa_login='ya',   dapat_insentif='ya'    WHERE kode_posisi='KM';
UPDATE tb_master_posisi SET kode_departemen='WORKSHOP',    bisa_login='tidak',dapat_insentif='ya'    WHERE kode_posisi='MK';
UPDATE tb_master_posisi SET kode_departemen='PURCHASING',  bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='PGD';
UPDATE tb_master_posisi SET kode_departemen='MARKETING',   bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='CRM';
UPDATE tb_master_posisi SET kode_departemen='MANAGEMENT',  bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='MNG';
UPDATE tb_master_posisi SET kode_departemen='FINANCE',     bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='KEU';
UPDATE tb_master_posisi SET kode_departemen='HRD',         bisa_login='ya',   dapat_insentif='tidak' WHERE kode_posisi='HRD';

-- 1.4 Hapus tb_user_mekanik_mapping (0 baris, aman)
DROP TABLE IF EXISTS tb_user_mekanik_mapping;

-- ============================================================
-- FASE 2: Tabel Karyawan Non-Mekanik
-- ============================================================

-- 2.1 karyawan (non-mekanik: Admin, CS, Kasir, Pengadaan, dll)
CREATE TABLE IF NOT EXISTS karyawan (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    kode_karyawan  VARCHAR(20) NOT NULL UNIQUE,
    nik            VARCHAR(20),
    nama_lengkap   VARCHAR(100) NOT NULL,
    nama_panggilan VARCHAR(50),
    kode_posisi    VARCHAR(20) NOT NULL,
    kode_jabatan   VARCHAR(20),
    kode_cabang    VARCHAR(10),
    email          VARCHAR(100),
    telp           VARCHAR(20),
    alamat         TEXT,
    spesialisasi   TEXT,
    sertifikat     TEXT,
    tanggal_masuk  DATE,
    tanggal_keluar DATE,
    is_active      ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2.2 karyawan_login
CREATE TABLE IF NOT EXISTS karyawan_login (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kode_karyawan VARCHAR(20) NOT NULL UNIQUE,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    is_active     ENUM('active','inactive') DEFAULT 'active',
    last_login    TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_karyawan) REFERENCES karyawan(kode_karyawan) ON DELETE CASCADE
);
