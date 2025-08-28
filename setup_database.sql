-- Database setup for Web Bengkel Testing
CREATE DATABASE IF NOT EXISTS web_bengkel;
USE web_bengkel;

-- Create basic tables for testing
CREATE TABLE IF NOT EXISTS tbcabang (
    kode_cabang VARCHAR(10) PRIMARY KEY,
    nama_cabang VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS tbuser (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    level VARCHAR(20) NOT NULL,
    kode_cabang VARCHAR(10),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif'
);

CREATE TABLE IF NOT EXISTS tbservis_mekanik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_service VARCHAR(50) NOT NULL,
    nama_mekanik VARCHAR(100) NOT NULL,
    persentase DECIMAL(5,2) NOT NULL,
    tipe_mekanik ENUM('head', 'regular') NOT NULL
);

-- Insert sample data
INSERT INTO tbcabang (kode_cabang, nama_cabang) VALUES 
('CAB001', 'Cabang Utama'),
('CAB002', 'Cabang Kedua');

INSERT INTO tbuser (username, password, nama, level, kode_cabang) VALUES 
('admin', MD5('admin'), 'Administrator', 'admin', 'CAB001'),
('user', MD5('user'), 'User Test', 'user', 'CAB001');
