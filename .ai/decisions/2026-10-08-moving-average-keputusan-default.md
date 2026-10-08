# Keputusan default Moving Average (D-01 s/d D-12)

Tanggal: 2026-10-08
Sumber: `docs/planning/PLANNING_MOVING_AVERAGE_WEBBASE_2026-10-05.md` §9
Status: DEFAULT dari rekomendasi planning. Rafi memerintahkan eksekusi sampai tuntas tanpa menjawab satu per satu,
jadi rekomendasi dipakai. Semua bisa dikoreksi; item bertanda (OWNER) wajib dikonfirmasi sebelum cutover produksi.

## Masalah
PRD Moving Average butuh 12 keputusan bisnis/teknis sebelum task terkait bisa jalan.

## Keputusan yang dipakai

| ID | Keputusan default | Catatan |
|---|---|---|
| D-01 | Kunci lokasi kanonik = `tbcabang.kode_cabang` (CIKDITIRO, PACUL, PESALAKAN, PST, TRAYEMAN). Alias: CDT=CIKDITIRO, PCL=PACUL, PSL=PESALAKAN, TRM=TRAYEMAN. `001` dan string kosong TIDAK dipetakan otomatis: 88 baris `001` (2025-04..06, sisa uji awal) dan 24 baris `''` (stok awal tipe 1) dibiarkan di ledger lama | (OWNER) konfirmasi arti `001` |
| D-02 | Biaya awal: beli terakhir, lalu `gabung_hpp_acuan`, lalu `tblitem.hargapokok`, lalu input manual | Dipakai saat memuat opening stock (F0-06) |
| D-03 | Saldo awal dari stok opname fisik per lokasi, bukan dari ledger lama | (OWNER) opname fisik harus dilakukan sebelum cutover |
| D-04 | Stok servis: dicek saat item ditambah, dipotong final saat bayar (perilaku sekarang tidak berubah) | Dikerjakan di Fase 2 |
| D-05 | Penjualan: Cancel hanya sebelum cetak/serah. Servis: Cancel sebelum status bayar, sesudahnya Void/retur | |
| D-06 | Approval ikut PRD; ambang opname default Rp0 | |
| D-07 | Jurnal/COA persediaan ditunda; Fase 5 jurnal menyusul setelah R1-R2 jalan | (OWNER) |
| D-08 | `total_value` DECIMAL(18,2) otoritatif; `average_cost = ROUND(total_value/qty, 2)` half up; HPP baris dibulatkan per baris | |
| D-09 | Alert aging transit 3 hari | |
| D-10 | Ledger historis `tbstok` dipertahankan sebagai arsip; ledger bernilai (`inv_*`) mulai dari opening stock | |
| D-11 | Pembelian cabang ke supplier lokal ikut BR-16 apa adanya | |
| D-12 | Cutover: PST dulu, lalu cabang kecil, terakhir cabang besar. Fase 0-2 hanya mode `shadow` | (OWNER) |

## Alternatif
Menunggu jawaban Rafi per ID. Ditolak: Fase 0-1 tidak memblokir pada keputusan ini selama mode default `off/shadow`.

## Alasan memilih
Rekomendasi planning sudah memperhitungkan data (saldo ledger 99% negatif, kunci lokasi campur). Mode shadow membuat salah pilih tidak merusak operasional.

## Dampak
- Deviasi dari F0-03: `tbstok.kd_cabang` TIDAK dinormalisasi di tempat. Alasan: CDT/PCL/PSL berisi saldo negatif historis (100% pasangan negatif). Menggabungkannya ke kode cabang baru akan membuat pembaca lama (`view_stok_master`) melihat saldo negatif dan memblokir penjualan. Sesuai D-10, ledger baru `inv_*` memakai kunci kanonik sejak awal lewat `inv_location`, dan normalisasi `tbstok` ditunda ke cutover per cabang.
- Tidak ada perubahan perilaku untuk user selama `inv_mode` = `off` atau `shadow`.
- Produksi tidak disentuh; semua migrasi dijalankan Rafi lewat runner.
