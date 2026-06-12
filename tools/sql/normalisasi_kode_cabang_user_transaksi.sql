UPDATE tbuser
SET kode_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kode_cabang, ''))) IN ('CAB001', 'KODE_CABANG_SESI_AND');

UPDATE tbuser_karyawan
SET kode_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kode_cabang, ''))) IN ('CAB001', '001', '1', '0');

UPDATE tblservice
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0', '');

UPDATE tblservice
SET kd_cabang = 'PESALAKAN'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) = 'PES';

UPDATE tblpenjualan_header
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0');

UPDATE tblpenjualan_detail
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0');

UPDATE tblorder_header
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0');

UPDATE tblorder_detail
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0');

UPDATE tbitem_masuk_header
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0');

UPDATE tbitem_keluar_header
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0');

UPDATE tblkas_keluar_masuk
SET kd_cabang = 'PST'
WHERE UPPER(TRIM(COALESCE(kd_cabang, ''))) IN ('001', '1', '0');
