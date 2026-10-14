<?php
// Guard endpoint tulis master barang/supplier (G9): hanya posisi dengan izin tulis (ADM) yang lolos.
// Pakai setelah koneksi DB tersedia: guardMasterWrite($koneksi, 'barang_write');
require_once __DIR__ . '/_include_menu_rbac.php';

function guardMasterWrite($koneksi, string $izin): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_iduser'])) {
        header('location:../index.php');
        exit;
    }
    requirePermission($koneksi, (int) $_SESSION['_iduser'], $izin);
}
