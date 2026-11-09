# Keputusan: identitas servis = (no_service, kd_cabang)

## Masalah
`no_service` tidak unik lintas cabang (30.889 nilai ganda, 78.106 baris tblservice). Join `no_service` saja salah cabang. 18 tabel anak tidak punya `kd_cabang`, 8 view tidak memakai `kd_cabang`, tidak ada FK nyata.

## Alternatif
1. Renumber `no_service` agar unik global: merusak nota, WA, arsip, referensi tersimpan.
2. Tambah ID surrogate dan FK di semua anak: perubahan terlalu besar, semua kode ikut berubah.
3. Kunci gabungan `(no_service, kd_cabang)` yang sudah ada di tblservice (PRIMARY KEY), diperluas ke semua anak.

## Alasan memilih
Opsi 3: nomor lama tetap valid, tblservice sudah memakainya, perubahan bertahap dan reversible. Baris lama yang cabangnya tidak pasti dikarantina, tidak ditebak.

## Dampak
Migrasi bertahap (kolom nullable, backfill, kode, NOT NULL, FK opsional), view ditulis ulang, penjaga audit di lint. Detail fase: `docs/planning/PLAN_NO_SERVICE_COMPOSITE_KEY_2026-11-09.md`. Semua migrasi produksi dijalankan Rafi.
