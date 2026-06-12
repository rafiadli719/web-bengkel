-- Helper view to derive kd_jenis_motor for a service
CREATE OR REPLACE VIEW `view_service_jenis_motor` AS
SELECT 
  s.no_service,
  s.no_pelanggan,
  s.no_polisi,
  k.kode_tipe,
  k.kode_jenis,
  COALESCE(NULLIF(k.kode_jenis, 0),
           (SELECT m.kd_jenis_motor 
              FROM tbtipe_jenis_motor_map m 
             WHERE m.kode_tipe = k.kode_tipe 
             LIMIT 1)) AS kd_jenis_motor
FROM tblservice s
LEFT JOIN tblkendaraan k ON k.nopolisi = s.no_polisi;

-- Optional: materialized mapping view of item applicability per jenis (for reporting)
CREATE OR REPLACE VIEW `view_item_applicability_by_jenis` AS
SELECT 
  i.noitem,
  i.namaitem,
  jm.kd_jenis_motor
FROM tblitem i
JOIN tbitem_jenis_motor jm ON jm.noitem = i.noitem;
