<?php
/**
 * GET /api/get_provinsi_publik.php
 *
 * Endpoint PUBLIK (tanpa login) khusus untuk dropdown provinsi di survey
 * pengunjung (user/index.php). Sengaja dipisah dari api/get_wilayah.php
 * yang wajib login, supaya endpoint internal tetap terproteksi sementara
 * halaman peta publik tetap bisa menampilkan pilihan provinsi.
 *
 * Cuma expose level provinsi (kode tanpa titik) dari tabel `wilayah`,
 * tidak ada parameter dinamis, jadi aman diakses publik.
 *
 * Response: JSON array [{ "kode": "...", "nama": "..." }, ...]
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->prepare(
        "SELECT kode, nama FROM wilayah
         WHERE kode NOT LIKE '%.%'
         ORDER BY nama ASC"
    );
    $stmt->execute();

    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data provinsi.']);
}