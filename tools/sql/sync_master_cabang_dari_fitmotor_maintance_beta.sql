DELETE FROM tbcabang
WHERE kode_cabang IN ('SBY', 'PACUL', 'CIKDITIRO', 'TRAYEMAN');

INSERT INTO tbcabang (
    kode_cabang,
    cabang_ref_kode,
    perusahaan_id,
    nama_cabang,
    entry_year,
    entry_month,
    alamat_cabang,
    google_maps_cabang,
    lat_cabang,
    long_cabang,
    tipe_cabang
) VALUES
('PESALAKAN', '201601001', 1, 'FIT MOTOR ADIWERNA', '2016', '01', 'Jl. Pesalakan No. 10', 'https://www.google.com/maps/place/Bengkel+Fit+Motor+Adiwerna/@-6.9327759,109.1267849,17z/data=!3m1!4b1!4m6!3m5!1s0x2e6fb97481fcfcc7:0xb756d2862d79d10d!8m2!3d-6.9327812!4d109.1293598!16s%2Fg%2F11gsbqlwkq?authuser=0&entry=ttu&g_ep=EgoyMDI1MTAwMS4wIKXMDSoASAFQAw%3D%3D', '-6.9327759', '109.1267849', '1'),
('PACUL', '201809001', 1, 'FIT MOTOR PACUL', '2018', '09', '', NULL, NULL, NULL, '1'),
('CIKDITIRO', '202201001', 1, 'FIT MOTOR CIKDITIRO', '2022', '01', '', NULL, NULL, NULL, '1'),
('TRAYEMAN', '202505001', 1, 'FIT MOTOR TRAYEMAN', '2025', '05', '', NULL, NULL, NULL, '1'),
('PST', '202601001', 1, 'FIT MOTOR PUSAT', '2026', '01', 'Jl. Industri No. 1', NULL, NULL, NULL, '1')
ON DUPLICATE KEY UPDATE
    cabang_ref_kode = VALUES(cabang_ref_kode),
    perusahaan_id = VALUES(perusahaan_id),
    nama_cabang = VALUES(nama_cabang),
    entry_year = VALUES(entry_year),
    entry_month = VALUES(entry_month),
    alamat_cabang = VALUES(alamat_cabang),
    google_maps_cabang = VALUES(google_maps_cabang),
    lat_cabang = VALUES(lat_cabang),
    long_cabang = VALUES(long_cabang),
    tipe_cabang = VALUES(tipe_cabang);
