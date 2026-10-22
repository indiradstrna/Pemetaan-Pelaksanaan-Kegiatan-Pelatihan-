<?php
/**
 * api/submit_laporan_monev.php
 * 
 * API Endpoint untuk menyimpan laporan capaian M&E dari sekolah
 * Method: POST
 * Content-Type: multipart/form-data (untuk file upload)
 */

session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Header JSON
header('Content-Type: application/json; charset=utf-8');

// Validasi: User harus sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit;
}

// Pastikan user adalah sekolah (bukan admin)
if (($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['role'] ?? '') === 'superadmin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini'
    ]);
    exit;
}

// Validasi: Request harus POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}

try {
    $sekolahId = $_SESSION['user_id'];
    $programId = $_POST['program_id'] ?? null;
    $timestamp = date('Y-m-d H:i:s');

    // Validasi program_id
    if (!$programId || !is_numeric($programId)) {
        throw new Exception('Program ID tidak valid');
    }

    // Collect data dari form
    $laporanData = [];
    $hasAnyData = false;

    // Parse form data untuk setiap indikator
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'status_') === 0) {
            $indikatorNo = str_replace('status_', '', $key);
            
            if (!isset($laporanData[$indikatorNo])) {
                $laporanData[$indikatorNo] = [
                    'indikator_no' => $indikatorNo,
                    'status' => $value,
                    'dampak' => [],
                    'foto_path' => null,
                    'kendala' => null
                ];
                $hasAnyData = true;
            } else {
                $laporanData[$indikatorNo]['status'] = $value;
            }
        }
    }

    // Parse dampak kuantitatif
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'dampak_') === 0) {
            preg_match('/dampak_(\d+)_(.+)/', $key, $matches);
            if (!empty($matches)) {
                $indikatorNo = $matches[1];
                $fieldName = $matches[2];
                
                if (!isset($laporanData[$indikatorNo])) {
                    $laporanData[$indikatorNo] = [
                        'indikator_no' => $indikatorNo,
                        'status' => null,
                        'dampak' => [],
                        'foto_path' => null,
                        'kendala' => null
                    ];
                }
                
                if ($value !== '') {
                    $laporanData[$indikatorNo]['dampak'][$fieldName] = (int)$value;
                    $hasAnyData = true;
                }
            }
        }
    }

    // Parse kendala
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'kendala_') === 0) {
            $indikatorNo = str_replace('kendala_', '', $key);
            
            if (!isset($laporanData[$indikatorNo])) {
                $laporanData[$indikatorNo] = [
                    'indikator_no' => $indikatorNo,
                    'status' => null,
                    'dampak' => [],
                    'foto_path' => null,
                    'kendala' => $value
                ];
            } else {
                $laporanData[$indikatorNo]['kendala'] = $value;
            }

            if (!empty($value)) {
                $hasAnyData = true;
            }
        }
    }

    // Handle file upload untuk foto
    foreach ($_FILES as $key => $file) {
        if (strpos($key, 'foto_') === 0 && $file['error'] === UPLOAD_ERR_OK) {
            $indikatorNo = str_replace('foto_', '', $key);
            
            // Validasi file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $mimeType = mime_content_type($file['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, atau PDF');
            }

            // Validasi file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Ukuran file tidak boleh lebih dari 2MB');
            }

            // Create directory jika belum ada
            $uploadDir = __DIR__ . '/../uploads/monev_bukti/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'monev_' . $sekolahId . '_' . $programId . '_' . $indikatorNo . '_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Gagal menyimpan file');
            }

            if (!isset($laporanData[$indikatorNo])) {
                $laporanData[$indikatorNo] = [
                    'indikator_no' => $indikatorNo,
                    'status' => null,
                    'dampak' => [],
                    'foto_path' => 'uploads/monev_bukti/' . $filename,
                    'kendala' => null
                ];
            } else {
                $laporanData[$indikatorNo]['foto_path'] = 'uploads/monev_bukti/' . $filename;
            }

            $hasAnyData = true;
        }
    }

    // Validasi: harus ada minimal satu data yang diinput
    if (!$hasAnyData) {
        throw new Exception('Mohon isi minimal satu indikator');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Insert ke tabel laporan_monev (jika belum ada table, buat terlebih dahulu)
    // Untuk sekarang, kita simpan data ke table sementara atau langsung proses
    // Assumption: Table `laporan_capaian_monev` sudah ada dengan struktur:
    // id, sekolah_id, program_id, indikator_no, status, dampak_json, 
    // foto_path, kendala, created_at, updated_at

    $sqlInsert = 'INSERT INTO capaian_monev 
                  (sekolah_id, program_id, indikator_no, status, dampak_json, 
                   foto_path, kendala, created_at, updated_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  status = VALUES(status),
                  dampak_json = VALUES(dampak_json),
                  foto_path = IF(VALUES(foto_path) IS NOT NULL AND VALUES(foto_path) != \'\', VALUES(foto_path), foto_path),
                  kendala = VALUES(kendala),
                  updated_at = VALUES(updated_at)';

    $stmt = $pdo->prepare($sqlInsert);

    foreach ($laporanData as $data) {
        if ($data['status'] === null && empty($data['dampak']) && $data['foto_path'] === null && $data['kendala'] === null) {
            continue; // Skip empty entries
        }

        $dampakVal = !empty($data['dampak']) ? json_encode($data['dampak']) : null;

        $stmt->execute([
            $sekolahId,
            $programId,
            $data['indikator_no'],
            $data['status'],
            $dampakVal,
            $data['foto_path'],
            $data['kendala'],
            $timestamp,
            $timestamp
        ]);
    }

    $pdo->commit();

    // Response sukses
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Laporan capaian M&E berhasil disimpan',
        'timestamp' => $timestamp
    ]);

} catch (Exception $e) {
    // Rollback transaction jika ada error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Hapus file yang sudah diupload jika ada error
    if (isset($filepath) && file_exists($filepath)) {
        @unlink($filepath);
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
