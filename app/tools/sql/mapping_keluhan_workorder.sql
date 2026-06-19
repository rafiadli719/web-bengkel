-- Mapping master keluhan ke workorder
-- KEL024-KEL125 (102 keluhan dari data lapangan Access)
-- Generated: 2026-06-19
--
-- Referensi WO:
--   WO0001 = SERVIS STANDAR MATIC/BEBEK (Rp 62.000)
--   WO0002 = GANTI KAMPAS BELAKANG (Rp 15.000)
--   WO0005 = PAKET SERVIS LENGKAP (Rp 233.000)
--   WO001  = Servis Rutin Matic (Rp 50.000)
--   WO002  = Ganti Oli + Filter (Rp 75.000)
--   WO003  = Tune Up Basic (Rp 100.000)
--   WO005  = Ganti Ban + Balancing (Rp 200.000)

START TRANSACTION;

-- Hapus mapping lama untuk kode ini jika ada (idempotent)
DELETE FROM tbmaster_keluhan_workorder
WHERE kode_keluhan IN (
    'KEL024','KEL025','KEL026','KEL027','KEL028','KEL029','KEL030',
    'KEL031','KEL032','KEL033','KEL034','KEL035','KEL036','KEL037',
    'KEL038','KEL039','KEL040','KEL041','KEL042','KEL043','KEL044',
    'KEL045','KEL046','KEL047','KEL048','KEL049','KEL050','KEL051',
    'KEL052','KEL053','KEL054','KEL055','KEL056','KEL057','KEL058',
    'KEL059','KEL060','KEL061','KEL062','KEL063','KEL064','KEL065',
    'KEL066','KEL067','KEL068','KEL069','KEL070','KEL071','KEL072',
    'KEL073','KEL074','KEL075','KEL076','KEL077','KEL078','KEL079',
    'KEL080','KEL081','KEL082','KEL083','KEL084','KEL085','KEL086',
    'KEL087','KEL088','KEL089','KEL090','KEL091','KEL092','KEL093',
    'KEL094','KEL095','KEL096','KEL097','KEL098','KEL099','KEL100',
    'KEL101','KEL102','KEL103','KEL104','KEL105','KEL106','KEL107',
    'KEL108','KEL109','KEL110','KEL111','KEL112','KEL113','KEL114',
    'KEL115','KEL116','KEL117','KEL118','KEL119','KEL120','KEL121',
    'KEL122','KEL123','KEL124','KEL125'
);

INSERT INTO tbmaster_keluhan_workorder (kode_keluhan, kode_workorder, prioritas, status_aktif) VALUES

-- === GANTI OLI ===
('KEL024','WO002','sedang','1'),    -- Ganti Oli Mesin
('KEL030','WO002','sedang','1'),    -- Ganti Oli Gear
('KEL032','WO002','sedang','1'),    -- Ganti Oli Gardan
('KEL036','WO002','rendah','1'),    -- Cek Oli Gear
('KEL058','WO002','rendah','1'),    -- Cek Oli Gardan
('KEL067','WO002','rendah','1'),    -- Cek Oli Mesin

-- === SERVIS RUTIN ===
('KEL025','WO0001','sedang','1'),   -- Servis Standar
('KEL028','WO0001','sedang','1'),   -- Servis Rutin
('KEL042','WO0005','sedang','1'),   -- Servis Full
('KEL064','WO003','sedang','1'),    -- Gurah Mesin
('KEL084','WO0005','sedang','1'),   -- Paket Servis Gratis Gurah
('KEL122','WO0005','sedang','1'),   -- Servis Lengkap

-- === TUNE UP ===
('KEL026','WO003','sedang','1'),    -- Tune Up

-- === CVT ===
('KEL027','WO001','sedang','1'),    -- Servis Cvt
('KEL038','WO001','sedang','1'),    -- Perawatan Cvt
('KEL073','WO001','sedang','1'),    -- Cvt Bunyi
('KEL078','WO001','sedang','1'),    -- Ganti Roller
('KEL086','WO0005','sedang','1'),   -- Servis Standar/Cvt/Gurah
('KEL090','WO001','sedang','1'),    -- Ganti Kampas Ganda
('KEL103','WO001','rendah','1'),    -- Cek Cvt

-- === MESIN - BERAT ===
('KEL029','WO0005','tinggi','1'),   -- Mogok
('KEL071','WO0005','tinggi','1'),   -- Turun Mesin

-- === MESIN - RINGAN ===
('KEL031','WO003','sedang','1'),    -- Tarikan Berat
('KEL045','WO003','sedang','1'),    -- Mbrebet
('KEL048','WO003','sedang','1'),    -- Suara Kasar
('KEL050','WO003','sedang','1'),    -- Gas Berat
('KEL051','WO0001','sedang','1'),   -- Ganti Busi
('KEL063','WO003','sedang','1'),    -- Bensin Boros
('KEL074','WO003','sedang','1'),    -- Tarikan Biar Enteng
('KEL096','WO003','sedang','1'),    -- Tarikannya Berat
('KEL106','WO003','sedang','1'),    -- Tarikan Kurang
('KEL108','WO0001','sedang','1'),   -- Oli Bocor
('KEL110','WO003','sedang','1'),    -- Gas Ngempos
('KEL116','WO0001','rendah','1'),   -- Cek Busi
('KEL123','WO0001','sedang','1'),   -- Bensin Bocor

-- === KELISTRIKAN ===
('KEL033','WO0001','sedang','1'),   -- Ganti Aki
('KEL034','WO0001','sedang','1'),   -- Ganti Lampu Depan
('KEL035','WO0001','sedang','1'),   -- Lampu Depan Mati
('KEL041','WO0001','sedang','1'),   -- Stater Mati
('KEL046','WO0001','rendah','1'),   -- Cek Aki
('KEL047','WO0001','rendah','1'),   -- Ganti Bohlam Depan
('KEL054','WO0001','sedang','1'),   -- Stater Susah
('KEL055','WO0001','rendah','1'),   -- Lampu Belakang Mati
('KEL057','WO0001','sedang','1'),   -- Strum Aki
('KEL061','WO0001','rendah','1'),   -- Klakson Mati
('KEL065','WO0001','rendah','1'),   -- Speedometer Mati
('KEL075','WO0001','rendah','1'),   -- Ganti Lampu Belakang
('KEL079','WO0001','rendah','1'),   -- Spedometer Mati
('KEL087','WO0001','rendah','1'),   -- Riting Mati
('KEL091','WO0001','rendah','1'),   -- Lampu Jarak Pendek Mati
('KEL092','WO0001','rendah','1'),   -- Kilometer Mati
('KEL094','WO0001','rendah','1'),   -- Cek Lampu
('KEL099','WO0001','rendah','1'),   -- Ganti Bohlam Belakang
('KEL101','WO0001','tinggi','1'),   -- Ngga Bisa Distater
('KEL102','WO0001','tinggi','1'),   -- Gak Bisa Distater
('KEL104','WO0001','rendah','1'),   -- Lampu Rem Mati
('KEL111','WO0001','rendah','1'),   -- Cek Kelistrikan
('KEL115','WO0001','rendah','1'),   -- Bohlam Depan Mati
('KEL121','WO0001','rendah','1'),   -- Spidometer Mati
('KEL124','WO0001','rendah','1'),   -- Speedo Mati

-- === REM ===
('KEL039','WO0001','rendah','1'),   -- Cek Rem
('KEL044','WO0002','sedang','1'),   -- Rem Kurang Pakem
('KEL076','WO0002','sedang','1'),   -- Ganti Master Rem
('KEL082','WO0002','sedang','1'),   -- Rem Belakang Bunyi
('KEL095','WO0002','tinggi','1'),   -- Rem Depan Blong
('KEL097','WO0001','sedang','1'),   -- Oli Rembes

-- === SUSPENSI ===
('KEL043','WO0001','sedang','1'),   -- Ganti Komstir
('KEL049','WO0001','rendah','1'),   -- Cek Komstir
('KEL056','WO0001','sedang','1'),   -- Stang Berat
('KEL070','WO0001','sedang','1'),   -- Stang Tempang
('KEL077','WO0001','sedang','1'),   -- Ganti Laher Depan
('KEL085','WO0001','sedang','1'),   -- Stel Komstir
('KEL105','WO0001','sedang','1'),   -- Setel Komstir
('KEL120','WO0001','rendah','1'),   -- Cek Shock Depan

-- === BAN ===
('KEL052','WO0001','sedang','1'),   -- Ganti Ban Dalam Belakang (ban dalam, tanpa balancing)
('KEL053','WO005','sedang','1'),    -- Ganti Ban Luar Belakang
('KEL066','WO005','sedang','1'),    -- Ganti Ban Luar Depan
('KEL072','WO005','sedang','1'),    -- Ganti Ban Tubles Belakang
('KEL118','WO005','sedang','1'),    -- Ganti Ban Tubles Depan

-- === TRANSMISI ===
('KEL059','WO0001','sedang','1'),   -- Ganti Fanbelt
('KEL062','WO0001','sedang','1'),   -- Ganti Girset
('KEL069','WO0001','rendah','1'),   -- Setel Rantai
('KEL088','WO0001','rendah','1'),   -- Stel Rante
('KEL098','WO0001','rendah','1'),   -- Rante Kendor
('KEL112','WO0001','rendah','1'),   -- Cek Fanbelt
('KEL117','WO0001','rendah','1'),   -- Rantai Kendor
('KEL119','WO0001','rendah','1'),   -- Setel Rante

-- === PENDINGIN ===
('KEL060','WO001','rendah','1'),    -- Cek Air Radiator
('KEL089','WO001','rendah','1'),    -- Isi Air Radiator
('KEL113','WO001','sedang','1'),    -- Ganti Air Radiator
('KEL114','WO001','sedang','1'),    -- Kuras Radiator

-- === FILTER ===
('KEL083','WO002','rendah','1'),    -- Ganti Filter Udara

-- === BODY ===
('KEL093','WO0001','rendah','1'),   -- Ganti Spion
('KEL100','WO0001','rendah','1'),   -- Pasang Spion

-- === UMUM ===
('KEL037','WO0002','sedang','1'),   -- Ganti Kampas Belakang
('KEL040','WO0002','sedang','1'),   -- Ganti Kampas Depan
('KEL068','WO0001','rendah','1'),   -- Bagian Belakang Bunyi
('KEL080','WO0001','rendah','1'),   -- Bagian Depan Bunyi
('KEL081','WO0002','rendah','1'),   -- Cek Kampas Belakang
('KEL107','WO0001','rendah','1'),   -- Km Mati
('KEL109','WO0002','rendah','1'),   -- Cek Kampas Depan
('KEL125','WO0005','rendah','1');   -- Dicek Semua

COMMIT;

SELECT CONCAT('Total mapping baru: ', COUNT(*), ' record') as info
FROM tbmaster_keluhan_workorder
WHERE kode_keluhan BETWEEN 'KEL024' AND 'KEL125';
