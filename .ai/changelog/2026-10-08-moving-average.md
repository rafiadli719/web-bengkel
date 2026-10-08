# 2026-10-08 — Moving Average (Fase 0-2 mode shadow, modul Persediaan)

## Added
- Tabel `inv_location`, `inv_balance`, `inv_ledger` (append-only), `inv_avg_history`, `inv_config`, `inv_audit_log` (`db/migrations/2026-10-08_run_inv_core.php [up|down]`).
- `app/lib/InventoryService.php`: `postIn`/`postOut` (average tertimbang, HPP saat posting, guard stok minus, idempotency, half-up integer), mode `off|shadow|live` per cabang, `reverseShadow`, `reconcile`, retry deadlock.
- `app/_include_inv_shadow.php`: adapter shadow (koneksi DB terpisah, tidak pernah melempar error).
- Panggilan shadow di 29 titik tulis stok (pembelian, DO, penjualan, servis x6, retur, antar cabang, stok manual, opname).
- Modul `app/_inventory/`: Pengaturan & Rekonsiliasi, Opening Stock (template saran biaya + validasi + muat sekali per lokasi), Valuasi Persediaan, Kartu Stok Bernilai. Menu "Persediaan (Moving Average)".
- Permission `inv_admin`, `inv_opening`, `inv_laporan`, `inv_view_all` (`db/migrations/2026-10-08_run_inv_permissions.php [up|down]`).
- Tes: `tests/inventory/run_inventory_tests.php` (40), `run_shadow_adapter_tests.php` (19 + 1 mode off).
- Dokumen: keputusan default D-01..D-12, audit jalur tulis stok dan potong ganda servis.

- Fase 3-4: `app/lib/InventoryCorrection.php` (adjust, koreksi harga beli + PPV, maker-checker, opname, revaluasi, scrap, transit + aging, klaim supplier, bonus proporsional), periode open/closed/locked, `postValueAdjust`, `convertUnit` (konversi satuan), `selfCheck`.
- Fase 5: `app/lib/InventoryJournal.php` (jurnal seimbang per baris ledger + PPV, COA lewat `inv_config acct_*`, otomatis aktif saat COA diisi), `scripts/inv_harian.php` (integritas, rekonsiliasi shadow, rekonsiliasi jurnal, aging transit).
- Halaman Koreksi (maker-checker), Laporan Transit/PPV/Klaim/HPP. Guard sync Access untuk cabang live.
- E2E browser (akun uji lokal, sudah dihapus): pembelian dan penjualan nyata dengan PST shadow, kartu stok bernilai, rekonsiliasi 0 selisih, maker-checker ADM ke KEU, opening stock (validasi, muat, tolak muat kedua), RBAC 403.

## Changed
- Tidak ada perubahan perilaku jalur lama selama `inv_mode=off` (default semua cabang).

## Fixed
- `penjualan_add_rst.php`: penjualan langsung tanpa pesanan gagal simpan header (total_qty_order NULL, MySQL strict). Ditemukan E2E browser. Plus `error_log` pada cabang gagal.
- Temuan F0-08 (servis-reguler-byr tanpa guard, guard tanpa cabang) dicatat, belum diubah.

## Removed
- —

## Belum / butuh Rafi
- Migrasi `2026-10-08_*` (inv_core, inv_koreksi, inv_permissions) BELUM di produksi.
- Opening stock dari opname fisik per cabang (D-03), aktivasi `live` per cabang (F5-06) dan penegakan BR-05/larang backdate di tiap alur, kode akun COA (D-07) agar jurnal aktif.
- Checklist-projek belum diupdate (butuh keputusan modul/fitur baru di dashboard).
