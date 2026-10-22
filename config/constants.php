<?php
/**
 * Konstanta global project
 * Ditambah bertahap sesuai kebutuhan modul yang sedang dikerjakan.
 */

define('BASE_URL', '/biotrop-gis');

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
            return BASE_URL . '/user/index.php';
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