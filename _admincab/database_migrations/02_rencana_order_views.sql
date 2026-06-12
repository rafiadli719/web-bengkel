-- ============================================================================
-- MIGRATION STEP 2: Views untuk Sistem Rencana Order
-- JALANKAN FILE INI SETELAH 01_rencana_order_tables.sql
-- ============================================================================

-- ============================================================================
-- VIEW: Stok per cabang dari tbstok
-- ============================================================================
DROP VIEW IF EXISTS view_stok_cabang;
CREATE VIEW view_stok_cabang AS
SELECT
    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci AS no_item,
    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci AS kd_cabang,
    SUM(masuk - keluar) AS stok_akhir
FROM tbstok
GROUP BY
    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci,
    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci;

-- ============================================================================
-- VIEW: Item dengan stok rendah (perlu order segera)
-- ============================================================================
DROP VIEW IF EXISTS view_item_order_segera;
CREATE VIEW view_item_order_segera AS
SELECT
    m.no_item,
    CONVERT(i.namaitem USING utf8mb4) AS nama_item,
    m.kd_cabang,
    c.nama_cabang,
    COALESCE(s.stok_akhir, 0) AS stok_saat_ini,
    m.min_stok,
    m.max_stok,
    m.kategori,
    ROUND(m.min_stok / 2, 0) AS kebutuhan_3_hari,
    m.lead_time_hari,
    CASE
        WHEN COALESCE(s.stok_akhir, 0) < ROUND(m.min_stok / 2, 0) THEN 'URGENT'
        WHEN COALESCE(s.stok_akhir, 0) < m.min_stok THEN 'WARNING'
        ELSE 'OK'
    END AS status_stok,
    m.supplier1,
    m.supplier2
FROM tblitem_minmax m
LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
LEFT JOIN tbcabang c ON m.kd_cabang = c.kode_cabang
LEFT JOIN view_stok_cabang s ON m.no_item = s.no_item AND m.kd_cabang = s.kd_cabang
WHERE m.kategori IN ('A', 'B', 'C')
  AND COALESCE(s.stok_akhir, 0) < m.min_stok
ORDER BY
    CASE WHEN COALESCE(s.stok_akhir, 0) < ROUND(m.min_stok / 2, 0) THEN 1 ELSE 2 END,
    m.kd_cabang, m.no_item;

-- ============================================================================
-- VIEW: Summary MIN/MAX per item (aggregat semua cabang)
-- ============================================================================
DROP VIEW IF EXISTS view_item_minmax_summary;
CREATE VIEW view_item_minmax_summary AS
SELECT
    m.no_item,
    i.namaitem AS nama_item,
    i.jenis,
    i.hargapokok AS harga,
    COUNT(DISTINCT m.kd_cabang) AS jml_cabang,
    SUM(COALESCE(s.stok_akhir, 0)) AS total_stok,
    SUM(m.min_stok) AS total_min,
    SUM(m.max_stok) AS total_max,
    MIN(m.kategori) AS kategori_terbaik,
    MAX(m.kategori) AS kategori_terburuk,
    MAX(m.supplier1) AS supplier1,
    MAX(m.supplier2) AS supplier2
FROM tblitem_minmax m
LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
LEFT JOIN view_stok_cabang s ON m.no_item = s.no_item AND m.kd_cabang = s.kd_cabang
GROUP BY m.no_item, i.namaitem, i.jenis, i.hargapokok;

-- ============================================================================
-- VIEW: Summary rencana order per supplier
-- ============================================================================
DROP VIEW IF EXISTS view_rencana_order_per_supplier;
CREATE VIEW view_rencana_order_per_supplier AS
SELECT
    h.no_rencana,
    h.tanggal,
    'ORDER1' AS tipe_order,
    d.order1_supplier_final AS supplier,
    COUNT(DISTINCT d.no_item) AS total_item,
    SUM(d.order1_qty_final) AS total_qty,
    SUM(d.order1_nilai) AS total_nilai
FROM tblrencana_order_header h
JOIN tblrencana_order_detail d ON h.no_rencana = d.no_rencana
WHERE d.order1_qty_final > 0
GROUP BY h.no_rencana, h.tanggal, d.order1_supplier_final

UNION ALL

SELECT
    h.no_rencana,
    h.tanggal,
    'ORDER2' AS tipe_order,
    d.order2_supplier_final AS supplier,
    COUNT(DISTINCT d.no_item) AS total_item,
    SUM(d.order2_qty_final) AS total_qty,
    SUM(d.order2_nilai) AS total_nilai
FROM tblrencana_order_header h
JOIN tblrencana_order_detail d ON h.no_rencana = d.no_rencana
WHERE d.order2_qty_final > 0
GROUP BY h.no_rencana, h.tanggal, d.order2_supplier_final;

-- ============================================================================
-- SELESAI STEP 2
-- Views telah dibuat
-- ============================================================================
