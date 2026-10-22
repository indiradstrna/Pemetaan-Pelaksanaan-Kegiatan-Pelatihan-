<?php
/**
 * /admin/lihat_peta.php
 *
 * Staf (internal/admin/superadmin) yang klik menu "Lihat Peta" di navbar
 * admin diarahkan ke halaman peta publik yang sama dipakai pengunjung
 * (user/index.php). Gak perlu duplikat halaman peta di /admin -- konten &
 * datanya identik, cuma topbar-nya otomatis nampilin nama & tombol Logout
 * karena staf ini kan sudah login (dicek lewat staff_page_check.php di bawah).
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/staff_page_check.php';

header('Location: ' . BASE_URL . '/user/index.php');
exit;