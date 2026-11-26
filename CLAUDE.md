## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## Lapor progress ke checklist-projek

WAJIB lapor tiap selesai mengerjakan/menguji satu fitur ke dashboard `checklist-projek`
(produksi `https://checklist.fitmotor.web.id`, lokal `http://localhost:8090`, lihat `CHECKLIST_BASE_URL` di `.env`).
Cara, status valid, daftar ID fitur resmi, dan endpoint API: skill `lapor-checklist`
(`.claude/skills/lapor-checklist/SKILL.md`).

## Status Ringkas per 2026-11-15 (satu-satunya sumber kebenaran status)

Jurnal lengkap "Update YYYY-MM-DD" (2026-09-03 s/d 2026-11-15) ada di
`.ai/changelog/2026-claude-md-status-history.md` dan `.ai/changelog/2026-09-kasir-keuangan-merge-history.md`.
Jurnal akurat PADA SAAT ditulis; kalau bertentangan dengan section ini, section ini yang benar.
Cross-check `git log` sebelum menyebut sesuatu "masih backlog". Update section ini tiap status berubah.

**Aturan bisnis:** HPP resmi = Moving Average per lokasi (aturan lama "HPP FIFO" USANG). `inv_mode` default `off`
di semua cabang = nol efek ke jalur lama. Planning `docs/planning/PLANNING_MOVING_AVERAGE_WEBBASE_2026-10-05.md`,
runbook `docs/planning/RUNBOOK_CUTOVER_MOVING_AVERAGE.md`, SOP `docs/SOP_PERSEDIAAN_MOVING_AVERAGE.md`,
keputusan `.ai/decisions/2026-10-08-moving-average-keputusan-default.md`. `tbstok.kd_cabang` sengaja TIDAK dinormalisasi.
Identitas servis = `(no_service, kd_cabang)` (no_service tidak unik lintas cabang).

**Migrasi yang SUDAH di lokal tapi BELUM di produksi** (Rafi jalankan runner-nya di shared hosting):
- `db/migrations/2026-09-30_run_cancel_servis_charset.php`, `2026-09-30_run_default_kolom_warisan_access.php`,
  `2026-09-30_run_tblservis_jasa_keterangan.php` (tanpa ini beberapa INSERT gagal di MySQL strict).
- `2026-10-04_run_merge_kategori_motor_cleanup_wo.php`, `2026-10-04_run_merge_merek_motor_duplikat.php`,
  `tbcabang.telepon_cabang`, `tblkomplain_setting`.
- Semua `2026-10-08_*` (Moving Average, termasuk `2026-10-08_run_inv_hpp_snapshot.php`).
- `2026-11-07_run_tblservice_catatan.php` (WAJIB sebelum/bersamaan deploy commit `8f4d0ee`).
- `2026-11-14_run_no_service_fase{1,2,3,4,6}_*.php` (runbook `.ai/changelog/2026-11-14-no-service-composite-key.md`).
- `2026-11-15_run_center_reload_schema.php`.

**Lokal, BELUM push/deploy:** `a6a2eb5` (E2E servis+stok), `8f4d0ee` + qty dibulatkan (2026-11-09),
fase no_service 2026-11-14, reload center Adiwerna (branch `feat/center-reload-adiwerna`).

**Pekerjaan aktif:** reload data histori dari `fitmotor_center` (VPS, warehouse 5 MDB Access) per cabang via
`scripts/center_reload/reload_cabang.php`. Urutan: Adiwerna (PESALAKAN, SELESAI lokal) -> Pacul -> Cikditiro ->
Trayeman -> Pusat. WAJIB sebelum sesi PACUL: Fase K (kunci gabungan nomor transaksi pembelian/penjualan/PO/hutang/dst;
PK `tblorder_header` terkunci FK DO/approval). Tabel HR dipertahankan; DB lokal lain jangan dihapus.
Detail `docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md`, `.ai/changelog/2026-11-15-reload-center-adiwerna.md`.

**Menunggu keputusan/data dari Rafi:**
- Akun staf ASLI KACAB per cabang + KM di PACUL/PESALAKAN/TRAYEMAN/CIKDITIRO (JANGAN auto-generate akun staf asli).
  Akun TEST `TEST-KACAB-*`/`TEST-KM-*` (tbuser id 125-135 lokal) wajib dinonaktifkan sebelum produksi.
- Admin CIKDITIRO di `tbuser_karyawan` kosong -> bayar servis di cabang itu gagal.
- Karantina `ns_karantina` (137 grup open); servis uji `SV26000103580` sengaja dibiarkan (jejak stok/komisi).
- Opening stock dari opname fisik (D-03), mode `live` per cabang + larang backdate (F5-06), kode akun COA (D-07).
- K2 (Keuangan Kasir vs ACE), K4 glosarium, K5 mobile, K7 menu KACAB; Rencana Order (butuh data tempo supplier,
  `tblsupplier_tempo` hilang); history service pelanggan (kode tak cocok skema asli); fitur yang tabelnya tidak ada
  (notifications/activity_logs, tb_servis_mekanik_assignment, tb_pickup_details, tbl_whatsapp_messages, dst): bangun atau arsip.
- Orphan merek legacy id 1-4 (`tblkendaraan.kode_merek`, `tblitem.kd_pabrik`, `tbtipe_motor.kode_pabrik=3`) TIDAK disentuh.
- Alamat cabang CIKDITIRO/PACUL kosong, PESALAKAN/PST tampak dummy, telepon cabang belum diisi (Master Cabang).
- 5 baris lama CDT total edit-item meleset (`.ai/changelog/2026-11-09-qty-bulat-edit-item.md`), belum dikoreksi.

**Backlog teknis:**
- Task 34 retire `masterkey.php` blocked Task 21 (SSO bridge `priori-tech` -> `tbuser`, koordinasi eksternal).
- Kredensial DB fallback hardcode di `app/koneksi.php` dan file lama sejenis (utang lintas banyak file).
- Watchlist performa: `statistik_pelanggan_dashboard.php` ~1,8s; `laporan-cancel-servis.php` (view berat).
- Kode mati boleh diarsip: keluhan-proses.php, servis-input-reguler-jemput-rst.php, _ajax/ajax-save-service.php,
  save_*_h.php, save_barang.php.

## Gotcha (tidak terlihat dari kode)

- Modul komplain butuh `app/db_env.php` DAN `config/db_env.php` (tiap `koneksi.php` cari di direktorinya sendiri),
  keduanya gitignored, isi `putenv()` DB_HOST/DB_USER/DB_PASS/DB_NAME. Jangan pernah beri default kredensial di kode.
- Dari Windows `127.0.0.1:3306` = MySQL WSL; Laragon pakai `localhost`.
- `_include_navbar.php` cuma fragmen: halaman wajib membungkus
  `<div id="navbar" class="navbar navbar-default"><div class="navbar-container">`.
- Dompdf lama: hindari div+h2/h3/h4 ber-margin (teks saling menimpa), pakai tabel.
- `requirePermission()` redirect `403.php` dihitung relatif kedalaman direktori; file di subfolder harus tetap pakai helper itu.
- Halaman legacy pakai `alert()` + `window.location.href`: tab browser automation bisa freeze 10-40 detik walau
  server sudah selesai. Verifikasi state lewat DB, jangan tutup tab saat macet.
- Tes browser: tab sering `visibilityState=hidden` (screenshot gagal / innerWidth 0) -> buat tab baru +
  `resize_window` 1366x768. Browser menyimpan cache `ace.min.css` & DataTables -> hard-reload sekali setelah deploy.
- `tbmaster_temuan_jasa_mapping` ada di dump produksi tapi tidak ada di DB lokal.
- Keputusan Rafi yang permanen: Task 17 Step 2 di-skip; 4 tabel akuntansi aktif sengaja tidak didrop (Task 18);
  Komplain Garansi modul servis terpisah dari Modul Komplain.
