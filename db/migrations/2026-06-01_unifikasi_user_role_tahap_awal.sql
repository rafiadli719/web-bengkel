-- ============================================================
-- MIGRASI TAHAP AWAL - UNIFIKASI USER, ROLE, KARYAWAN
-- File aman sebagai blueprint refactor bertahap.
-- Fokus:
-- 1. Menetapkan struktur target ter-normalisasi
-- 2. Menjaga kompatibilitas data lama
-- 3. Tidak langsung mematikan tabel legacy
-- ============================================================

START TRANSACTION;

-- ============================================================
-- A. MASTER BARU
-- ============================================================

CREATE TABLE IF NOT EXISTS mst_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_role VARCHAR(20) NOT NULL UNIQUE,
    nama_role VARCHAR(100) NOT NULL,
    departemen VARCHAR(100) DEFAULT NULL,
    user_akses_level INT DEFAULT NULL,
    deskripsi TEXT DEFAULT NULL,
    is_active ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS mst_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_permission VARCHAR(100) NOT NULL UNIQUE,
    nama_permission VARCHAR(150) NOT NULL,
    modul VARCHAR(100) DEFAULT NULL,
    aksi VARCHAR(50) DEFAULT NULL,
    deskripsi TEXT DEFAULT NULL,
    is_active ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS map_role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_map_role_permissions_role
        FOREIGN KEY (role_id) REFERENCES mst_roles(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_map_role_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES mst_permissions(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS mst_job_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_level VARCHAR(20) NOT NULL UNIQUE,
    kode_role VARCHAR(20) NOT NULL,
    nama_level VARCHAR(100) NOT NULL,
    urutan INT NOT NULL DEFAULT 1,
    deskripsi TEXT DEFAULT NULL,
    is_active ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS mst_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_karyawan VARCHAR(20) NOT NULL UNIQUE,
    nik VARCHAR(20) DEFAULT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    nama_panggilan VARCHAR(50) DEFAULT NULL,
    kode_role VARCHAR(20) NOT NULL,
    kode_level VARCHAR(20) DEFAULT NULL,
    kode_cabang VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    telp VARCHAR(20) DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    tanggal_masuk DATE DEFAULT NULL,
    tanggal_keluar DATE DEFAULT NULL,
    spesialisasi TEXT DEFAULT NULL,
    sertifikat TEXT DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    status_kerja ENUM('active','inactive') NOT NULL DEFAULT 'active',
    sumber_data VARCHAR(30) NOT NULL DEFAULT 'tbuser_karyawan',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS mst_user_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    tbuser_id INT DEFAULT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_akses_level INT DEFAULT NULL,
    is_active ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    last_login TIMESTAMP NULL DEFAULT NULL,
    must_change_password ENUM('yes','no') NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mst_user_accounts_employee
        FOREIGN KEY (employee_id) REFERENCES mst_employees(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS mst_mechanic_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    kode_mekanik_legacy VARCHAR(20) DEFAULT NULL UNIQUE,
    jenis_mekanik ENUM('kepala_mekanik','mekanik','admin_workshop') NOT NULL DEFAULT 'mekanik',
    keahlian_legacy VARCHAR(10) DEFAULT NULL,
    status_legacy VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mst_mechanic_profiles_employee
        FOREIGN KEY (employee_id) REFERENCES mst_employees(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- B. SEED DARI STRUKTUR YANG SUDAH ADA
-- ============================================================

INSERT INTO mst_roles (kode_role, nama_role, departemen, user_akses_level, deskripsi, is_active)
SELECT
    p.kode_posisi,
    p.nama_posisi,
    p.departemen,
    p.user_akses_level,
    p.deskripsi,
    p.is_active
FROM tb_master_posisi p
WHERE NOT EXISTS (
    SELECT 1 FROM mst_roles r WHERE r.kode_role = p.kode_posisi
);

INSERT INTO mst_job_levels (kode_level, kode_role, nama_level, urutan, deskripsi, is_active)
SELECT
    l.kode_level,
    l.kode_posisi,
    l.nama_level,
    l.urutan,
    l.deskripsi,
    l.is_active
FROM tb_master_level l
WHERE NOT EXISTS (
    SELECT 1 FROM mst_job_levels x WHERE x.kode_level = l.kode_level
);

INSERT INTO mst_employees (
    kode_karyawan, nik, nama_lengkap, nama_panggilan, kode_role, kode_level, kode_cabang,
    email, telp, alamat, tanggal_masuk, tanggal_keluar, spesialisasi, sertifikat, foto, status_kerja, sumber_data
)
SELECT
    k.kode_karyawan,
    k.nik,
    k.nama_lengkap,
    k.nama_panggilan,
    k.kode_posisi,
    k.kode_level,
    k.kode_cabang,
    k.email,
    k.telp,
    k.alamat,
    k.tanggal_masuk,
    k.tanggal_keluar,
    k.spesialisasi,
    k.sertifikat,
    k.foto,
    'active',
    'tbuser_karyawan'
FROM tbuser_karyawan k
WHERE NOT EXISTS (
    SELECT 1 FROM mst_employees e WHERE e.kode_karyawan = k.kode_karyawan
);

INSERT INTO mst_user_accounts (
    employee_id, tbuser_id, username, password_hash, user_akses_level, is_active, last_login, must_change_password
)
SELECT
    e.id,
    u.id,
    u.nama_user,
    COALESCE(NULLIF(u.password, ''), CONCAT('legacy-', u.id)),
    u.user_akses,
    COALESCE(u.is_active, 'active'),
    u.last_login,
    'no'
FROM tbuser u
JOIN mst_employees e
    ON e.kode_karyawan = u.kode_karyawan
WHERE u.nama_user IS NOT NULL
  AND u.nama_user <> ''
  AND NOT EXISTS (
      SELECT 1 FROM mst_user_accounts a WHERE a.username = u.nama_user
  );

INSERT INTO mst_mechanic_profiles (
    employee_id, kode_mekanik_legacy, jenis_mekanik, keahlian_legacy, status_legacy
)
SELECT
    e.id,
    m.nomekanik,
    CASE
        WHEN e.kode_role = 'KM' THEN 'kepala_mekanik'
        WHEN e.kode_role = 'MK' THEN 'mekanik'
        ELSE 'admin_workshop'
    END,
    m.keahlian,
    m.status
FROM tblmekanik m
JOIN mst_employees e
    ON e.nama_lengkap = m.nama
WHERE NOT EXISTS (
    SELECT 1 FROM mst_mechanic_profiles mp WHERE mp.kode_mekanik_legacy = m.nomekanik
);

-- ============================================================
-- C. KOMPATIBILITAS QUERY BARU
-- ============================================================

CREATE OR REPLACE VIEW v_user_role_unified AS
SELECT
    ua.id AS user_account_id,
    ua.tbuser_id,
    ua.username,
    ua.user_akses_level,
    ua.is_active AS user_status,
    ua.last_login,
    e.id AS employee_id,
    e.kode_karyawan,
    e.nama_lengkap,
    e.nama_panggilan,
    e.kode_role,
    r.nama_role,
    r.departemen,
    e.kode_level,
    l.nama_level,
    e.kode_cabang,
    e.email,
    e.telp
FROM mst_user_accounts ua
JOIN mst_employees e ON e.id = ua.employee_id
LEFT JOIN mst_roles r ON r.kode_role = e.kode_role
LEFT JOIN mst_job_levels l ON l.kode_level = e.kode_level;

COMMIT;
