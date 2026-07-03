-- Master Workorder: kombinasi WO + item gratis per-baris
-- Lihat spec: docs/superpowers/specs/2026-07-02-workorder-kombinasi-design.md

ALTER TABLE `tbworkorderdetail`
  ADD COLUMN `is_gratis` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Barang harga jual dikunci 0 (termasuk paket)';

ALTER TABLE `tbservis_pending_items`
  ADD COLUMN `is_gratis` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Dibawa dari tbworkorderdetail, harga_satuan dikunci 0';
