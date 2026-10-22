<?php
/**
 * /api/cek_duplikat_lokasi.php
 *
 * Deteksi kecurigaan data lokasi duplikat SEBELUM disimpan.
 *
 * Logika:
 *  1. Untuk tiap lokasi yang sudah ada, cek apakah nama_lokasi ATAU nama_jalan
 *     MIRIP (bukan cuma beda huruf besar/kecil, tapi toleransi typo ringan)
 *     dengan input yang mau disimpan.
 *  2. Kalau salah satu dari nama/jalan itu "curiga mirip" ke suatu record,
 *     baru dicek lagi: apakah wilayah (provinsi + kab/kota + kecamatan + kelurahan)
 *     dari record yang sama itu JUGA persis sama (case-insensitive)?
 *  3. Kalau iya -> fix dianggap duplikat, kembalikan detail record yang cocok.
 *
 * POST params: nama_lokasi, nama_jalan, provinsi, kabupaten_kota, kecamatan,
 *              kelurahan, exclude_id (opsional, dipakai pas mode edit biar
 *              gak nge-match ke data dirinya sendiri)
 *
 * Response: { duplikat: bool, match: {...} | null }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_check.php';

// Ambang batas kemiripan (persen). Bisa disesuaikan kalau kebanyakan/kekurangan false-positive.
const AMBANG_MIRIP = 80;

function normalisasi(?string $s): string
{
    $s = mb_strtolower(trim((string) $s));
    return preg_replace('/\s+/', ' ', $s);
}

function mirip(string $a, string $b): bool
{
    if ($a === '' || $b === '') return false;
    if ($a === $b) return true;
    similar_text($a, $b, $percent);
    return $percent >= AMBANG_MIRIP;
}

$input = [
    'nama_lokasi'    => normalisasi($_POST['nama_lokasi'] ?? ''),
    'nama_jalan'     => normalisasi($_POST['nama_jalan'] ?? ''),
    'provinsi'       => normalisasi($_POST['provinsi'] ?? ''),
    'kabupaten_kota' => normalisasi($_POST['kabupaten_kota'] ?? ''),
    'kecamatan'      => normalisasi($_POST['kecamatan'] ?? ''),
    'kelurahan'      => normalisasi($_POST['kelurahan'] ?? ''),
];
$excludeId = (int) ($_POST['exclude_id'] ?? 0);

try {
    if ($excludeId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM lokasi WHERE id != :id');
        $stmt->execute(['id' => $excludeId]);
    } else {
        $stmt = $pdo->query('SELECT * FROM lokasi');
    }

    $semuaLokasi = $stmt->fetchAll();

    foreach ($semuaLokasi as $row) {
        $namaMirip  = mirip($input['nama_lokasi'], normalisasi($row['nama_lokasi']));
        $jalanMirip = mirip($input['nama_jalan'], normalisasi($row['nama_jalan']));

        if (!$namaMirip && !$jalanMirip) {
            continue; // gak ada indikasi apapun, lanjut ke record berikutnya
        }

        // Ada kecurigaan dari nama/jalan -> cek wilayah persis sama
        $wilayahSama =
            normalisasi($row['provinsi']) === $input['provinsi'] &&
            normalisasi($row['kabupaten_kota']) === $input['kabupaten_kota'] &&
            normalisasi($row['kecamatan']) === $input['kecamatan'] &&
            normalisasi($row['kelurahan']) === $input['kelurahan'];

        if ($wilayahSama) {
            echo json_encode([
                'duplikat' => true,
                'match'    => [
                    'id'             => $row['id'],
                    'nama_lokasi'    => $row['nama_lokasi'],
                    'nama_jalan'     => $row['nama_jalan'],
                    'provinsi'       => $row['provinsi'],
                    'kabupaten_kota' => $row['kabupaten_kota'],
                    'kecamatan'      => $row['kecamatan'],
                    'kelurahan'      => $row['kelurahan'],
                ],
            ]);
            exit;
        }
    }

    echo json_encode(['duplikat' => false, 'match' => null]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['duplikat' => false, 'match' => null, 'error' => 'Gagal mengecek data.']);
}