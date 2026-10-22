<?php
/**
 * Guard: wajib login DAN role admin (verifikator) / superadmin.
 * Include file ini di baris paling atas halaman yang cuma boleh diakses
 * verifikator -- saat ini cuma admin/verifikasi.php dan action=verifikasi
 * di api/crud_kegiatan.php.
 *
 * Internal yang nyasar ke sini ditendang ke dashboard admin (dia gak
 * berhak verifikasi, tapi tetap punya akses ke area /admin lainnya).
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/auth_check.php';

if (!in_array($_SESSION['role'], ['admin', 'superadmin'], true)) {
    header('Location: ' . urlBerandaUntukRole($_SESSION['role']));
    exit;
}