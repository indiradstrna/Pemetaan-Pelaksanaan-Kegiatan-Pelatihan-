<?php
/**
 * Guard: wajib login DAN role superadmin.
 * Include file ini di baris paling atas halaman yang HANYA boleh
 * diakses superadmin — saat ini cuma Kelola User (admin/kelola_user.php
 * dan api/crud_user.php).
 *
 * Internal yang nyasar ke sini ditendang ke dashboard admin (bukan ke
 * /user/index.php, karena internal tetap punya akses ke area /admin,
 * cuma bukan ke menu ini).
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/auth_check.php';

if ($_SESSION['role'] !== 'superadmin') {
    if ($_SESSION['role'] === 'internal') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/user/index.php');
    }
    exit;
}