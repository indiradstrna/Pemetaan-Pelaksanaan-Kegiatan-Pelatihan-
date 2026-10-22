<?php
/**
 * GET /api/get_wilayah.php
 *
 * Endpoint cascading dropdown wilayah (provinsi/kab-kota/kecamatan/kelurahan),
 * datanya bersumber dari tabel `wilayah` (import dari cahyadsn/wilayah).
 *
 * Struktur kode wilayah berjenjang pakai titik:
 *   11                -> provinsi        (panjang kode = 2)
 *   11.01             -> kab/kota        (panjang kode = 5)
 *   11.01.01          -> kecamatan       (panjang kode = 8)
 *   11.01.01.2001     -> kelurahan/desa  (panjang kode = 13)
 *
 * Parameter:
 *   level  : provinsi | kabupaten | kecamatan | kelurahan
 *   parent : kode level di atasnya (tidak dipakai kalau level = provinsi)
 *
 * Response: JSON array [{ "kode": "...", "nama": "...", "latitude": ..., "longitude": ... }, ...]
 * (latitude/longitude terisi untuk semua level kalau migrasi koordinat sudah dijalankan;
 *  kalau belum, kolomnya NULL dan frontend otomatis skip auto-center)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php'; // wajib login (endpoint internal, bukan publik)

$level  = $_GET['level']  ?? '';
$parent = $_GET['parent'] ?? '';

$allowedLevels = ['provinsi', 'kabupaten', 'kecamatan', 'kelurahan'];

if (!in_array($level, $allowedLevels, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter level tidak valid.']);
    exit;
}

// Validasi format kode wilayah: cuma boleh angka & titik (cegah LIKE injection/abuse)
if ($level !== 'provinsi' && !preg_match('/^[0-9.]+$/', $parent)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter parent tidak valid.']);
    exit;
}

try {
    switch ($level) {
        case 'provinsi':
            // Kode provinsi tidak mengandung titik
            $stmt = $pdo->prepare(
                "SELECT kode, nama, latitude, longitude FROM wilayah
                 WHERE kode NOT LIKE '%.%'
                 ORDER BY nama ASC"
            );
            $stmt->execute();
            break;

        case 'kabupaten':
            $stmt = $pdo->prepare(
                "SELECT kode, nama, latitude, longitude FROM wilayah
                 WHERE kode LIKE CONCAT(:parent, '.%')
                   AND LENGTH(kode) = 5
                 ORDER BY nama ASC"
            );
            $stmt->execute(['parent' => $parent]);
            break;

        case 'kecamatan':
            $stmt = $pdo->prepare(
                "SELECT kode, nama, latitude, longitude FROM wilayah
                 WHERE kode LIKE CONCAT(:parent, '.%')
                   AND LENGTH(kode) = 8
                 ORDER BY nama ASC"
            );
            $stmt->execute(['parent' => $parent]);
            break;

        case 'kelurahan':
            $stmt = $pdo->prepare(
                "SELECT kode, nama, latitude, longitude FROM wilayah
                 WHERE kode LIKE CONCAT(:parent, '.%')
                   AND LENGTH(kode) = 13
                 ORDER BY nama ASC"
            );
            $stmt->execute(['parent' => $parent]);
            break;

        default:
            // Gak akan kena sebenarnya (udah divalidasi in_array di atas), tapi ini jaga-jaga
            // biar $stmt selalu pasti ke-assign & gak fatal error kalau nanti $allowedLevels
            // nambah value baru tapi lupa ditangani di switch ini.
            http_response_code(400);
            echo json_encode(['error' => 'Level tidak dikenali.']);
            exit;
    }

    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data wilayah.']);
}