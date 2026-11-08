# 2026-11-07 Audit input modul servis

## Added
- `db/migrations/2026-11-07_run_tblservice_catatan.php [up|down]`: kolom `tblservice.catatan` (TEXT NULL). Lokal sudah jalan, produksi belum.
- `docs/planning/ANALISIS_PAYMENT_GATEWAY_DAN_AUDIT_INPUT_SERVIS_2026-11-07.md`.

## Fixed
- Catatan "tidak selesai" / item ditolak tidak tersimpan (kolom `catatan` tidak ada).
- Keluhan dan keterangan >255 karakter hilang diam-diam (dipotong 255, `maxlength` di input).
- `servis-input-reguler.php` Simpan header menimpa `keterangan` jadi kosong; INSERT baru di-escape.
- Escape ganda (`\'`) di catatan keluhan/WO tidak selesai dan tolak item.
- `btnreject_wo_item` menulis ke `keterangan` varchar(100): pindah ke `catatan`.
- Edit item/paket servis menerima qty negatif dan potongan >100 (6 file `servis_edit_*`).

## Changed
- Catatan penolakan item kini di `tblservice.catatan` (tampil di tab riwayat servis sebelumnya, bukan header).

## Removed
- (tidak ada)

Uji: harness CLI (halaman asli, session buatan, DB lokal, data uji dihapus). Belum diuji: invoice WA, uji klik browser.
