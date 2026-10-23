<?php
/**
 * Konstanta global project
 * Ditambah bertahap sesuai kebutuhan modul yang sedang dikerjakan.
 */

// Deteksi BASE_URL otomatis:
// - Di hosting (simones.biotrop.org): DocumentRoot mengarah langsung ke folder project, jadi BASE_URL = ''
// - Di localhost (XAMPP): project berada di dalam subfolder '/biotrop-gis', jadi BASE_URL = '/biotrop-gis'
if (!defined('BASE_URL')) {
    $host   = $_SERVER['HTTP_HOST'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    if (strpos($host, 'simones.biotrop.org') !== false) {
        define('BASE_URL', '');
    } elseif (strpos($script, '/biotrop-gis') === 0 || in_array($host, ['localhost', '127.0.0.1']) || str_starts_with($host, 'localhost:')) {
        define('BASE_URL', '/biotrop-gis');
    } else {
        define('BASE_URL', '');
    }
}

// Path upload foto kegiatan (dipakai nanti saat modul Kelola Kegiatan)
define('UPLOAD_PATH_KEGIATAN', __DIR__ . '/../uploads/kegiatan/');
define('UPLOAD_PATH_KEGIATAN_REVISI', __DIR__ . '/../uploads/kegiatan_revisi/');

/**
 * Halaman "beranda" tiap role setelah login (dipakai index.php, login.php,
 * dan guard-guard di /includes). Dipusatkan di sini biar kalau ada role baru
 * atau halaman verifikasi pindah lokasi, cukup ubah di satu tempat.
 */
function urlBerandaUntukRole(string $role): string
{
    switch ($role) {
        case 'admin':
            return BASE_URL . '/admin/verifikasi.php';
        case 'internal':
        case 'superadmin':
            return BASE_URL . '/admin/dashboard.php';
        case 'user':
            // Redirect user sekolah ke halaman lapor capaian M&E
            return BASE_URL . '/user/lapor_capaian_monev.php';
        default: // guest yang somehow kepanggil ke sini
            return BASE_URL . '/sekar.php';
    }
}

/**
 * Label tampilan untuk role.
 *
 * PENTING: ini HANYA mengubah teks yang tampil ke user, bukan nilai
 * kolom `role` di database (`user`/`internal`/`admin`/`superadmin`,
 * lihat migrations/001_alur_verifikasi.sql) dan bukan nama folder
 * /admin/. Role saat ini kebetulan sudah pas kalau di-ucfirst() begitu
 * saja (internal -> Internal, admin -> Admin, superadmin -> Superadmin),
 * makanya $labels kosong. Kalau nanti butuh teks custom (mis. 'admin'
 * mau ditampilkan sebagai 'Verifikator'), tambahkan mapping-nya di sini.
 */
function labelRole(string $role): string
{
    $labels = [];

    return $labels[$role] ?? ucfirst($role);
}