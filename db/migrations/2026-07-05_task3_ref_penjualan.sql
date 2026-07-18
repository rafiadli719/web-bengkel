-- Task 3: konversi nota Penjualan Umum jadi servis (Work Order)
-- Kolom ref di tblservice untuk link balik ke nota Penjualan asal
ALTER TABLE tblservice
  ADD COLUMN ref_no_penjualan_asal VARCHAR(30) NULL;

-- Kolom penanda asal barang di tblservis_barang, menggantikan pola
-- string-check no_item='PART-CUST' yang rapuh dengan kolom eksplisit.
ALTER TABLE tblservis_barang
  ADD COLUMN asal_barang ENUM('SERVIS','PART-CUST','PENJUALAN') NOT NULL DEFAULT 'SERVIS';

-- Backfill baris PART-CUST lama supaya konsisten dengan kolom baru
UPDATE tblservis_barang SET asal_barang = 'PART-CUST' WHERE no_item = 'PART-CUST';
