CREATE OR REPLACE VIEW view_cari_pelanggan AS
SELECT
    p.nopelanggan,
    p.namapelanggan,
    p.alamat,
    p.kota,
    p.propinsi,
    p.kodepost,
    p.negara,
    p.telephone,
    p.fax,
    p.kontakperson,
    p.note,
    p.potongan,
    p.tipepot,
    p.lavelharga,
    p.kgrup,
    p.patokan,
    p.klat,
    p.klong,
    p.panggilan,
    pg.grup,
    COALESCE(sp.status_member, pg.grup, 'Bronze') AS status_member,
    COALESCE(sp.diskon_persen, 0) AS diskon_persen
FROM tblpelanggan p
LEFT JOIN tblpelanggangrup pg ON pg.kgrup = p.kgrup
LEFT JOIN statistik_pelanggan sp ON sp.no_pelanggan = p.nopelanggan;

CREATE OR REPLACE VIEW view_cari_kendaraan AS
SELECT
    k.nopolisi,
    COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) AS pemilik,
    COALESCE(p_map.alamat, p_plate.alamat, k.alamat) AS alamat,
    k.kode_merek,
    k.tipe,
    k.kode_tipe,
    k.jenis,
    k.kode_jenis,
    k.tahun_buat,
    k.tahun_rakit,
    k.silinder,
    k.warna,
    k.kode_warna,
    k.no_rangka,
    k.no_mesin,
    k.note,
    COALESCE(pm.merek, '') AS merek,
    COALESCE(p_map.nopelanggan, p_plate.nopelanggan, latest_map.no_pelanggan_map, '') AS nopelanggan,
    COALESCE(p_map.telephone, p_plate.telephone, '') AS telephone,
    COALESCE(p_map.kgrup, p_plate.kgrup, '') AS kgrup,
    COALESCE(sp_map.status_member, sp_plate.status_member, 'Bronze') AS status_member,
    COALESCE(sp_map.diskon_persen, sp_plate.diskon_persen, 0) AS diskon_persen
FROM tblkendaraan k
LEFT JOIN tbpabrik_motor pm ON pm.id = k.kode_merek
LEFT JOIN (
    SELECT
        ts.no_polisi,
        SUBSTRING_INDEX(GROUP_CONCAT(ts.no_pelanggan ORDER BY ts.tanggal DESC, ts.jam DESC, ts.no_service DESC SEPARATOR '||'), '||', 1) AS no_pelanggan_map
    FROM tblservice ts
    WHERE ts.no_pelanggan IS NOT NULL
      AND ts.no_pelanggan <> ''
    GROUP BY ts.no_polisi
) latest_map ON latest_map.no_polisi = k.nopolisi
LEFT JOIN tblpelanggan p_map ON p_map.nopelanggan = latest_map.no_pelanggan_map
LEFT JOIN tblpelanggan p_plate ON p_plate.nopelanggan = k.nopolisi
LEFT JOIN statistik_pelanggan sp_map ON sp_map.no_pelanggan = p_map.nopelanggan
LEFT JOIN statistik_pelanggan sp_plate ON sp_plate.no_pelanggan = p_plate.nopelanggan;

CREATE OR REPLACE VIEW view_pelanggan_kendaraan AS
SELECT
    k.nopolisi,
    COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) AS pemilik,
    COALESCE(p_map.alamat, p_plate.alamat, k.alamat) AS alamat,
    k.kode_merek,
    k.tipe,
    k.kode_tipe,
    k.jenis,
    k.kode_jenis,
    k.tahun_buat,
    k.tahun_rakit,
    k.silinder,
    k.warna,
    k.kode_warna,
    k.no_rangka,
    k.no_mesin,
    k.note,
    COALESCE(pm.merek, '') AS merek,
    COALESCE(p_map.telephone, p_plate.telephone, '') AS telephone,
    COALESCE(p_map.nopelanggan, p_plate.nopelanggan, latest_map.no_pelanggan_map, '') AS nopelanggan,
    COALESCE(sp_map.status_member, sp_plate.status_member, 'Bronze') AS status_member
FROM tblkendaraan k
LEFT JOIN tbpabrik_motor pm ON pm.id = k.kode_merek
LEFT JOIN (
    SELECT
        ts.no_polisi,
        SUBSTRING_INDEX(GROUP_CONCAT(ts.no_pelanggan ORDER BY ts.tanggal DESC, ts.jam DESC, ts.no_service DESC SEPARATOR '||'), '||', 1) AS no_pelanggan_map
    FROM tblservice ts
    WHERE ts.no_pelanggan IS NOT NULL
      AND ts.no_pelanggan <> ''
    GROUP BY ts.no_polisi
) latest_map ON latest_map.no_polisi = k.nopolisi
LEFT JOIN tblpelanggan p_map ON p_map.nopelanggan = latest_map.no_pelanggan_map
LEFT JOIN tblpelanggan p_plate ON p_plate.nopelanggan = k.nopolisi
LEFT JOIN statistik_pelanggan sp_map ON sp_map.no_pelanggan = p_map.nopelanggan
LEFT JOIN statistik_pelanggan sp_plate ON sp_plate.no_pelanggan = p_plate.nopelanggan
ORDER BY pemilik, k.nopolisi;
