<?php
/**
 * Guard: wajib login DAN salah satu role staf (internal/admin/superadmin).
 * Versi redirect (buat halaman HTML biasa) dari includes/staff_check.php
 * yang JSON (buat API). Dipakai di halaman yang boleh diakses SEMUA staf,
 * bukan cuma internal/superadmin -- saat ini: admin/dashboard.php dan
 * admin/lihat_peta.php.
 *
 * Untuk halaman yang HANYA boleh internal/superadmin (Kelola Lokasi, Kelola
 * Kegiatan), tetap pakai admin_check.php. Untuk yang HANYA admin/superadmin
 * (Verifikasi), pakai verifikator_check.php.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/auth_check.php';

if (!in_array($_SESSION['role'], ['internal', 'admin', 'superadmin'], true)) {
    header('Location: ' . urlBerandaUntukRole($_SESSION['role']));
    exit;
}