<?php
/**
 * Konfigurasi koneksi database (PDO)
 * Project: Web GIS SEAMEO BIOTROP Activity Map
 */

$DB_HOST = 'localhost';
$DB_NAME = 'biotrop_gis_kegiatan';
$DB_USER = 'root';  
$DB_PASS = '';       
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Di production, jangan tampilkan detail error ke user.
    // Untuk tahap development (XAMPP lokal) masih ditampilkan agar mudah debug.
    die('Koneksi database gagal: ' . $e->getMessage());
}