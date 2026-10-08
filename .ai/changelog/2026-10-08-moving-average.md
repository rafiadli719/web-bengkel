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

## Changed
- Tidak ada perubahan perilaku jalur lama selama `inv_mode=off` (default semua cabang).

## Fixed
- Tidak ada perbaikan di jalur lama. Temuan F0-08 (servis-reguler-byr tanpa guard, guard tanpa cabang) dicatat, belum diubah.

## Removed
- —

## Belum / butuh Rafi
- Migrasi `2026-10-08_*` BELUM di produksi.
- E2E UI modul Persediaan di browser (butuh login).
- Opening stock dari opname fisik, aktivasi `live` (F5-06), jurnal/COA (D-07).
