-- Modul Komplain: pengaturan yang bisa diubah tanpa deploy (spec 6.2 poin 6: batas No-show
-- default 7 hari, dapat dikonfigurasi). Reversible: DROP TABLE tblkomplain_setting;
CREATE TABLE IF NOT EXISTS tblkomplain_setting (
  kunci VARCHAR(50) NOT NULL PRIMARY KEY,
  nilai VARCHAR(255) NOT NULL,
  updated_by VARCHAR(20) DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT IGNORE INTO tblkomplain_setting (kunci, nilai) VALUES ('noshow_hari', '7');
