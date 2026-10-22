<?php
/**
 * Guard: wajib login DAN salah satu role staf (internal/admin/superadmin).
 *
 * Ini guard "longgar" -- cuma mastiin yang manggil itu staf, BUKAN guest/user.
 * Dipakai di endpoint API yang aksinya campuran (ada yang buat internal, ada
 * yang buat admin/verifikator), jadi pembatasan siapa-boleh-action-apa
 * dilakukan MANUAL di dalam file API-nya sendiri (per action), bukan di sini.
 *
 * Untuk halaman /admin/*.php biasa (bukan API), tetap pakai admin_check.php
 * (internal/superadmin) atau verifikator_check.php (admin/superadmin) sesuai
 * kebutuhan halamannya -- guard ini khusus API.
 */

require_once __DIR__ . '/../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['internal', 'admin', 'superadmin'], true)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid atau tidak punya akses.']);
    exit;
}