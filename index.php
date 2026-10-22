<?php
/**
 * Entry point project.
 */

session_start();
require_once __DIR__ . '/config/constants.php';

if (isset($_SESSION['user_id'])) {
    // Staf (internal/admin/superadmin) yang sudah login langsung ke halaman kerjanya masing-masing
    // (dashboard buat internal/superadmin, halaman verifikasi buat admin).
    header('Location: ' . urlBerandaUntukRole($_SESSION['role']));
} else {
    // Default: pengunjung publik & user biasa langsung ke peta,
    // tanpa wajib login. Login hanya dipakai kalau mau lihat detail/akses staf.
    header('Location: ' . BASE_URL . '/user/index.php');
}
exit;