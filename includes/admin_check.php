<?php
/**
 * Guard: wajib login DAN role internal/superadmin (area /admin yang sudah
 * ada: dashboard, kelola lokasi, kelola kegiatan, pengaturan akun, dst).
 * Include file ini di baris paling atas setiap halaman
 * di folder /admin, sebelum ada output HTML apapun.
 *
 * Catatan: ini BUKAN guard untuk semua staf. Role 'admin' (verifikator)
 * SENGAJA tidak dimasukkan ke sini -- tugasnya cuma approve/reject di
 * halaman verifikasi (lihat includes/verifikator_check.php). Kalau admin
 * nyasar ke halaman-halaman ini, ditendang balik ke halaman verifikasinya.
 *
 * Untuk halaman yang cuma boleh diakses superadmin (Kelola User), pakai
 * superadmin_check.php.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/auth_check.php';

if (!in_array($_SESSION['role'], ['internal', 'superadmin'], true)) {
    // Setiap role ditendang ke halaman "berandanya" masing-masing --
    // user -> peta, admin (verifikator) -> halaman verifikasi.
    header('Location: ' . urlBerandaUntukRole($_SESSION['role']));
    exit;
}