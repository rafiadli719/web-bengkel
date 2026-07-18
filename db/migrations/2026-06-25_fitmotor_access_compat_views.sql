-- FITMOTOR Access compatibility views
-- Generated to bridge Access table/query names to existing MySQL data tables.

DROP VIEW IF EXISTS `GABUNG_PELANGGAN`;
CREATE OR REPLACE VIEW `GABUNG_PELANGGAN` AS
SELECT
  NoPelanggan,
  NamaPelanggan,
  Telephone,
  Telephone AS NO_HP,
  DOMISILI AS CABANG,
  NULL AS MEMBER
FROM `nomor_wa__domisili_polisi_data`;

DROP VIEW IF EXISTS `TBLPelanggan_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLPelanggan_PESALAKAN` AS
SELECT
  NoPelanggan,
  NamaPelanggan,
  Telephone,
  NULL AS KGrup,
  NULL AS Alamat,
  NULL AS Note,
  NULL AS Kota,
  NULL AS KontakPerson,
  NULL AS Member
FROM `GABUNG_PELANGGAN`
WHERE UPPER(CABANG) = 'PESALAKAN' OR CABANG IS NULL;

DROP VIEW IF EXISTS `TBLPelanggan_PACUL`;
CREATE OR REPLACE VIEW `TBLPelanggan_PACUL` AS
SELECT
  NoPelanggan,
  NamaPelanggan,
  Telephone,
  NULL AS KGrup,
  NULL AS Alamat,
  NULL AS Note,
  NULL AS Kota,
  NULL AS KontakPerson,
  NULL AS Member
FROM `GABUNG_PELANGGAN`
WHERE UPPER(CABANG) = 'PACUL' OR CABANG IS NULL;

DROP VIEW IF EXISTS `TBLPelanggan_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLPelanggan_CIKDITIRO` AS
SELECT
  NoPelanggan,
  NamaPelanggan,
  Telephone,
  NULL AS KGrup,
  NULL AS Alamat,
  NULL AS Note,
  NULL AS Kota,
  NULL AS KontakPerson,
  NULL AS Member
FROM `GABUNG_PELANGGAN`
WHERE UPPER(CABANG) = 'CIKDITIRO' OR CABANG IS NULL;

DROP VIEW IF EXISTS `TBLPelanggan_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLPelanggan_TRAYEMAN` AS
SELECT
  NoPelanggan,
  NamaPelanggan,
  Telephone,
  NULL AS KGrup,
  NULL AS Alamat,
  NULL AS Note,
  NULL AS Kota,
  NULL AS KontakPerson,
  NULL AS Member
FROM `GABUNG_PELANGGAN`
WHERE UPPER(CABANG) = 'TRAYEMAN' OR CABANG IS NULL;

DROP VIEW IF EXISTS `TBLPelanggan_PUSAT`;
CREATE OR REPLACE VIEW `TBLPelanggan_PUSAT` AS
SELECT
  NoPelanggan,
  NamaPelanggan,
  Telephone,
  NULL AS KGrup,
  NULL AS Alamat,
  NULL AS Note,
  NULL AS Kota,
  NULL AS KontakPerson,
  NULL AS Member
FROM `GABUNG_PELANGGAN`
WHERE UPPER(CABANG) = 'PUSAT' OR CABANG IS NULL;

DROP VIEW IF EXISTS `TBLPelangganGrup_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLPelangganGrup_PESALAKAN` AS
SELECT 'NON MEMBER' AS KGrup, 'NON MEMBER' AS Grup
UNION ALL SELECT 'GOLD', 'GOLD'
UNION ALL SELECT 'SILVER', 'SILVER';

DROP VIEW IF EXISTS `TBLPelangganGrup_PACUL`;
CREATE OR REPLACE VIEW `TBLPelangganGrup_PACUL` AS
SELECT 'NON MEMBER' AS KGrup, 'NON MEMBER' AS Grup
UNION ALL SELECT 'GOLD', 'GOLD'
UNION ALL SELECT 'SILVER', 'SILVER';

DROP VIEW IF EXISTS `TBLPelangganGrup_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLPelangganGrup_CIKDITIRO` AS
SELECT 'NON MEMBER' AS KGrup, 'NON MEMBER' AS Grup
UNION ALL SELECT 'GOLD', 'GOLD'
UNION ALL SELECT 'SILVER', 'SILVER';

DROP VIEW IF EXISTS `TBLPelangganGrup_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLPelangganGrup_TRAYEMAN` AS
SELECT 'NON MEMBER' AS KGrup, 'NON MEMBER' AS Grup
UNION ALL SELECT 'GOLD', 'GOLD'
UNION ALL SELECT 'SILVER', 'SILVER';

DROP VIEW IF EXISTS `TBLPelangganGrup_PUSAT`;
CREATE OR REPLACE VIEW `TBLPelangganGrup_PUSAT` AS
SELECT 'NON MEMBER' AS KGrup, 'NON MEMBER' AS Grup
UNION ALL SELECT 'GOLD', 'GOLD'
UNION ALL SELECT 'SILVER', 'SILVER';

DROP VIEW IF EXISTS `TBLMekanik_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLMekanik_PESALAKAN` AS
SELECT no_mekanik AS NOMEKANIK, nama AS NAMA
FROM `gabung_mekanik_data`
WHERE UPPER(kd_cabang) = 'PESALAKAN' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLMekanik_PACUL`;
CREATE OR REPLACE VIEW `TBLMekanik_PACUL` AS
SELECT no_mekanik AS NOMEKANIK, nama AS NAMA
FROM `gabung_mekanik_data`
WHERE UPPER(kd_cabang) = 'PACUL' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLMekanik_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLMekanik_CIKDITIRO` AS
SELECT no_mekanik AS NOMEKANIK, nama AS NAMA
FROM `gabung_mekanik_data`
WHERE UPPER(kd_cabang) = 'CIKDITIRO' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLMekanik_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLMekanik_TRAYEMAN` AS
SELECT no_mekanik AS NOMEKANIK, nama AS NAMA
FROM `gabung_mekanik_data`
WHERE UPPER(kd_cabang) = 'TRAYEMAN' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLMekanik_PUSAT`;
CREATE OR REPLACE VIEW `TBLMekanik_PUSAT` AS
SELECT no_mekanik AS NOMEKANIK, nama AS NAMA
FROM `gabung_mekanik_data`
WHERE UPPER(kd_cabang) = 'PUSAT' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLSupplier_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLSupplier_PESALAKAN` AS
SELECT
  no_supplier AS NoSupplier,
  nama_supplier AS NamaSupplier,
  NULL AS Alamat,
  NULL AS Kota,
  NULL AS Propinsi,
  NULL AS KodePost,
  NULL AS Negara,
  NULL AS Telephone,
  fax AS Fax,
  NULL AS NamaBank,
  NULL AS NoAccount,
  NULL AS AtasNama,
  kontak_person AS KontakPerson,
  NULL AS Email,
  note AS Note,
  saldo_awal AS SaldoAwal,
  NULL AS PerTanggal,
  NULL AS JmlBayar,
  NULL AS Sisa
FROM `gabung_supplier_data`
WHERE UPPER(kd_cabang) = 'PESALAKAN' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLSupplier_PACUL`;
CREATE OR REPLACE VIEW `TBLSupplier_PACUL` AS
SELECT
  no_supplier AS NoSupplier,
  nama_supplier AS NamaSupplier,
  NULL AS Alamat,
  NULL AS Kota,
  NULL AS Propinsi,
  NULL AS KodePost,
  NULL AS Negara,
  NULL AS Telephone,
  fax AS Fax,
  NULL AS NamaBank,
  NULL AS NoAccount,
  NULL AS AtasNama,
  kontak_person AS KontakPerson,
  NULL AS Email,
  note AS Note,
  saldo_awal AS SaldoAwal,
  NULL AS PerTanggal,
  NULL AS JmlBayar,
  NULL AS Sisa
FROM `gabung_supplier_data`
WHERE UPPER(kd_cabang) = 'PACUL' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLSupplier_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLSupplier_CIKDITIRO` AS
SELECT
  no_supplier AS NoSupplier,
  nama_supplier AS NamaSupplier,
  NULL AS Alamat,
  NULL AS Kota,
  NULL AS Propinsi,
  NULL AS KodePost,
  NULL AS Negara,
  NULL AS Telephone,
  fax AS Fax,
  NULL AS NamaBank,
  NULL AS NoAccount,
  NULL AS AtasNama,
  kontak_person AS KontakPerson,
  NULL AS Email,
  note AS Note,
  saldo_awal AS SaldoAwal,
  NULL AS PerTanggal,
  NULL AS JmlBayar,
  NULL AS Sisa
FROM `gabung_supplier_data`
WHERE UPPER(kd_cabang) = 'CIKDITIRO' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLSupplier_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLSupplier_TRAYEMAN` AS
SELECT
  no_supplier AS NoSupplier,
  nama_supplier AS NamaSupplier,
  NULL AS Alamat,
  NULL AS Kota,
  NULL AS Propinsi,
  NULL AS KodePost,
  NULL AS Negara,
  NULL AS Telephone,
  fax AS Fax,
  NULL AS NamaBank,
  NULL AS NoAccount,
  NULL AS AtasNama,
  kontak_person AS KontakPerson,
  NULL AS Email,
  note AS Note,
  saldo_awal AS SaldoAwal,
  NULL AS PerTanggal,
  NULL AS JmlBayar,
  NULL AS Sisa
FROM `gabung_supplier_data`
WHERE UPPER(kd_cabang) = 'TRAYEMAN' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLSupplier_PUSAT`;
CREATE OR REPLACE VIEW `TBLSupplier_PUSAT` AS
SELECT
  no_supplier AS NoSupplier,
  nama_supplier AS NamaSupplier,
  NULL AS Alamat,
  NULL AS Kota,
  NULL AS Propinsi,
  NULL AS KodePost,
  NULL AS Negara,
  NULL AS Telephone,
  fax AS Fax,
  NULL AS NamaBank,
  NULL AS NoAccount,
  NULL AS AtasNama,
  kontak_person AS KontakPerson,
  NULL AS Email,
  note AS Note,
  saldo_awal AS SaldoAwal,
  NULL AS PerTanggal,
  NULL AS JmlBayar,
  NULL AS Sisa
FROM `gabung_supplier_data`
WHERE UPPER(kd_cabang) = 'PUSAT' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLUser_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLUser_PESALAKAN` AS
SELECT userid AS USERID, namauser AS NAMAUSER, NULL AS PASSWORD FROM `gabung_user_data`;

DROP VIEW IF EXISTS `TBLUser_PACUL`;
CREATE OR REPLACE VIEW `TBLUser_PACUL` AS
SELECT userid AS USERID, namauser AS NAMAUSER, NULL AS PASSWORD FROM `gabung_user_data`;

DROP VIEW IF EXISTS `TBLUser_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLUser_CIKDITIRO` AS
SELECT userid AS USERID, namauser AS NAMAUSER, NULL AS PASSWORD FROM `gabung_user_data`;

DROP VIEW IF EXISTS `TBLUser_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLUser_TRAYEMAN` AS
SELECT userid AS USERID, namauser AS NAMAUSER, NULL AS PASSWORD FROM `gabung_user_data`;

DROP VIEW IF EXISTS `TBLUser_PUSAT`;
CREATE OR REPLACE VIEW `TBLUser_PUSAT` AS
SELECT userid AS USERID, namauser AS NAMAUSER, NULL AS PASSWORD FROM `gabung_user_data`;

DROP VIEW IF EXISTS `TBLItem_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLItem_PESALAKAN` AS
SELECT
  NoItem,
  KodeBarCode,
  NamaItem,
  Jenis,
  Satuan,
  HargaPokok,
  HargaJual,
  HargaJual2,
  HargaJual3,
  HJQtyD2,
  HJQtyD3,
  HJQtyS1,
  HJQtyS2,
  TotalPokok,
  Quantity,
  StokMin,
  StatusItem,
  Supplier,
  Supplier2,
  Supplier3,
  StatusProduk,
  Gambar,
  Note,
  RakBarang,
  JasaWaktu,
  JasaSatuanWaktu,
  JenisKomisi,
  KomisiProsen,
  KomisiNominal,
  Inv_IdAwal,
  Inv_JmlAwal,
  Inv_HrgAwal,
  Inv_TglAwal,
  tanggal_data
FROM `gabung_tblitem_perbengkel_data`
WHERE UPPER(STS) = 'PESALAKAN' OR STS IS NULL;

DROP VIEW IF EXISTS `TBLItem_PACUL`;
CREATE OR REPLACE VIEW `TBLItem_PACUL` AS
SELECT
  NoItem,
  KodeBarCode,
  NamaItem,
  Jenis,
  Satuan,
  HargaPokok,
  HargaJual,
  HargaJual2,
  HargaJual3,
  HJQtyD2,
  HJQtyD3,
  HJQtyS1,
  HJQtyS2,
  TotalPokok,
  Quantity,
  StokMin,
  StatusItem,
  Supplier,
  Supplier2,
  Supplier3,
  StatusProduk,
  Gambar,
  Note,
  RakBarang,
  JasaWaktu,
  JasaSatuanWaktu,
  JenisKomisi,
  KomisiProsen,
  KomisiNominal,
  Inv_IdAwal,
  Inv_JmlAwal,
  Inv_HrgAwal,
  Inv_TglAwal,
  tanggal_data
FROM `gabung_tblitem_perbengkel_data`
WHERE UPPER(STS) = 'PACUL' OR STS IS NULL;

DROP VIEW IF EXISTS `TBLItem_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLItem_CIKDITIRO` AS
SELECT
  NoItem,
  KodeBarCode,
  NamaItem,
  Jenis,
  Satuan,
  HargaPokok,
  HargaJual,
  HargaJual2,
  HargaJual3,
  HJQtyD2,
  HJQtyD3,
  HJQtyS1,
  HJQtyS2,
  TotalPokok,
  Quantity,
  StokMin,
  StatusItem,
  Supplier,
  Supplier2,
  Supplier3,
  StatusProduk,
  Gambar,
  Note,
  RakBarang,
  JasaWaktu,
  JasaSatuanWaktu,
  JenisKomisi,
  KomisiProsen,
  KomisiNominal,
  Inv_IdAwal,
  Inv_JmlAwal,
  Inv_HrgAwal,
  Inv_TglAwal,
  tanggal_data
FROM `gabung_tblitem_perbengkel_data`
WHERE UPPER(STS) = 'CIKDITIRO' OR STS IS NULL;

DROP VIEW IF EXISTS `TBLItem_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLItem_TRAYEMAN` AS
SELECT
  NoItem,
  KodeBarCode,
  NamaItem,
  Jenis,
  Satuan,
  HargaPokok,
  HargaJual,
  HargaJual2,
  HargaJual3,
  HJQtyD2,
  HJQtyD3,
  HJQtyS1,
  HJQtyS2,
  TotalPokok,
  Quantity,
  StokMin,
  StatusItem,
  Supplier,
  Supplier2,
  Supplier3,
  StatusProduk,
  Gambar,
  Note,
  RakBarang,
  JasaWaktu,
  JasaSatuanWaktu,
  JenisKomisi,
  KomisiProsen,
  KomisiNominal,
  Inv_IdAwal,
  Inv_JmlAwal,
  Inv_HrgAwal,
  Inv_TglAwal,
  tanggal_data
FROM `gabung_tblitem_perbengkel_data`
WHERE UPPER(STS) = 'TRAYEMAN' OR STS IS NULL;

DROP VIEW IF EXISTS `TBLItem_PUSAT`;
CREATE OR REPLACE VIEW `TBLItem_PUSAT` AS
SELECT
  NoItem,
  KodeBarCode,
  NamaItem,
  Jenis,
  Satuan,
  HargaPokok,
  HargaJual,
  HargaJual2,
  HargaJual3,
  HJQtyD2,
  HJQtyD3,
  HJQtyS1,
  HJQtyS2,
  TotalPokok,
  Quantity,
  StokMin,
  StatusItem,
  Supplier,
  Supplier2,
  Supplier3,
  StatusProduk,
  Gambar,
  Note,
  RakBarang,
  JasaWaktu,
  JasaSatuanWaktu,
  JenisKomisi,
  KomisiProsen,
  KomisiNominal,
  Inv_IdAwal,
  Inv_JmlAwal,
  Inv_HrgAwal,
  Inv_TglAwal,
  tanggal_data
FROM `gabung_tblitem_perbengkel_data`
WHERE UPPER(STS) = 'PUSAT' OR STS IS NULL;

DROP VIEW IF EXISTS `TBLItemJenis_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLItemJenis_PESALAKAN` AS
SELECT DISTINCT jenis AS JENIS, jenis AS NAMAJENIS
FROM `gabung_tblitem_perbengkel_data`;

DROP VIEW IF EXISTS `TBLItemJenis_PACUL`;
CREATE OR REPLACE VIEW `TBLItemJenis_PACUL` AS
SELECT DISTINCT jenis AS JENIS, jenis AS NAMAJENIS
FROM `gabung_tblitem_perbengkel_data`;

DROP VIEW IF EXISTS `TBLItemJenis_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLItemJenis_CIKDITIRO` AS
SELECT DISTINCT jenis AS JENIS, jenis AS NAMAJENIS
FROM `gabung_tblitem_perbengkel_data`;

DROP VIEW IF EXISTS `TBLItemJenis_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLItemJenis_TRAYEMAN` AS
SELECT DISTINCT jenis AS JENIS, jenis AS NAMAJENIS
FROM `gabung_tblitem_perbengkel_data`;

DROP VIEW IF EXISTS `TBLItemJenis_PUSAT`;
CREATE OR REPLACE VIEW `TBLItemJenis_PUSAT` AS
SELECT DISTINCT jenis AS JENIS, jenis AS NAMAJENIS
FROM `gabung_tblitem_perbengkel_data`;

DROP VIEW IF EXISTS `TBLKendaraan_PESALAKAN`;
CREATE OR REPLACE VIEW `TBLKendaraan_PESALAKAN` AS
SELECT
  no_polisi AS NoPolisi,
  merek AS Merek,
  tipe AS Tipe,
  jenis AS Jenis,
  warna AS Warna,
  tahun_buat AS TahunBuat,
  telephone AS Telephone,
  grup AS Grup,
  alamat AS Alamat,
  note AS Note,
  kategori AS Kategori,
  kota AS Kota,
  kontak_person AS KontakPerson
FROM `kendaraan_pelanggan_gabung_data`
WHERE UPPER(kd_cabang) = 'PESALAKAN' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLKendaraan_PACUL`;
CREATE OR REPLACE VIEW `TBLKendaraan_PACUL` AS
SELECT
  no_polisi AS NoPolisi,
  merek AS Merek,
  tipe AS Tipe,
  jenis AS Jenis,
  warna AS Warna,
  tahun_buat AS TahunBuat,
  telephone AS Telephone,
  grup AS Grup,
  alamat AS Alamat,
  note AS Note,
  kategori AS Kategori,
  kota AS Kota,
  kontak_person AS KontakPerson
FROM `kendaraan_pelanggan_gabung_data`
WHERE UPPER(kd_cabang) = 'PACUL' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLKendaraan_CIKDITIRO`;
CREATE OR REPLACE VIEW `TBLKendaraan_CIKDITIRO` AS
SELECT
  no_polisi AS NoPolisi,
  merek AS Merek,
  tipe AS Tipe,
  jenis AS Jenis,
  warna AS Warna,
  tahun_buat AS TahunBuat,
  telephone AS Telephone,
  grup AS Grup,
  alamat AS Alamat,
  note AS Note,
  kategori AS Kategori,
  kota AS Kota,
  kontak_person AS KontakPerson
FROM `kendaraan_pelanggan_gabung_data`
WHERE UPPER(kd_cabang) = 'CIKDITIRO' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLKendaraan_TRAYEMAN`;
CREATE OR REPLACE VIEW `TBLKendaraan_TRAYEMAN` AS
SELECT
  no_polisi AS NoPolisi,
  merek AS Merek,
  tipe AS Tipe,
  jenis AS Jenis,
  warna AS Warna,
  tahun_buat AS TahunBuat,
  telephone AS Telephone,
  grup AS Grup,
  alamat AS Alamat,
  note AS Note,
  kategori AS Kategori,
  kota AS Kota,
  kontak_person AS KontakPerson
FROM `kendaraan_pelanggan_gabung_data`
WHERE UPPER(kd_cabang) = 'TRAYEMAN' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TBLKendaraan_PUSAT`;
CREATE OR REPLACE VIEW `TBLKendaraan_PUSAT` AS
SELECT
  no_polisi AS NoPolisi,
  merek AS Merek,
  tipe AS Tipe,
  jenis AS Jenis,
  warna AS Warna,
  tahun_buat AS TahunBuat,
  telephone AS Telephone,
  grup AS Grup,
  alamat AS Alamat,
  note AS Note,
  kategori AS Kategori,
  kota AS Kota,
  kontak_person AS KontakPerson
FROM `kendaraan_pelanggan_gabung_data`
WHERE UPPER(kd_cabang) = 'PUSAT' OR kd_cabang IS NULL;

DROP VIEW IF EXISTS `TIPEMOTOR`;
CREATE OR REPLACE VIEW `TIPEMOTOR` AS
SELECT DISTINCT
  tipe AS TIPE,
  tipe AS KATEGORI
FROM `kendaraan_pelanggan_gabung_data`;

DROP VIEW IF EXISTS `NOMOR_WA__DOMISILI_NOPOLISI`;
CREATE OR REPLACE VIEW `NOMOR_WA__DOMISILI_NOPOLISI` AS
SELECT
  NamaPelanggan,
  Telephone,
  NoPelanggan,
  DOMISILI
FROM `nomor_wa__domisili_polisi_data`;

DROP VIEW IF EXISTS `REKAP_KONSUMEN`;
CREATE OR REPLACE VIEW `REKAP_KONSUMEN` AS
SELECT * FROM `rekap_konsumen_data`;

DROP VIEW IF EXISTS `REKAP_KONSUMEN_DATANGBERIKUTNYA`;
CREATE OR REPLACE VIEW `REKAP_KONSUMEN_DATANGBERIKUTNYA` AS
SELECT * FROM `rekap_konsumen_datangberikutnya_data`;

DROP VIEW IF EXISTS `REKONSIL_JUAL_BELI_ANTARCABANG`;
CREATE OR REPLACE VIEW `REKONSIL_JUAL_BELI_ANTARCABANG` AS
SELECT * FROM `rekonsil_jual_beli_antarcabang_data`;
