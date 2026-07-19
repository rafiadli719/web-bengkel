-- Daftarkan permission promo_diskon_read ke posisi Administrator (ADM).
-- FSD_PROMO.md: role yang boleh kelola Master Promo/Diskon = "Owner + Admin Pusat saja".
-- Skema RBAC ini cuma punya 1 posisi admin (ADM = Administrator, user_akses_level=1),
-- gak ada pemisahan Owner vs Admin Pusat, jadi didaftarkan ke ADM.
-- Sudah dijalankan live 2026-07-19 via PHP CLI (json_decode+append+json_encode,
-- bukan string replace, biar aman dari escaping) - file ini cuma dokumentasi
-- reproducible, idempotent lewat JSON_CONTAINS guard.
UPDATE tb_master_posisi
SET permissions = JSON_ARRAY_APPEND(permissions, '$', 'promo_diskon_read')
WHERE kode_posisi = 'ADM'
  AND NOT JSON_CONTAINS(permissions, '"promo_diskon_read"');
