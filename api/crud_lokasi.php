<?php
/**
 * /api/crud_lokasi.php
 *
 * GET  ?action=list             -> semua lokasi + jumlah kegiatan terkait
 * GET  ?action=detail&id=X      -> detail 1 lokasi (untuk pre-fill form edit)
 * POST action=create            -> tambah lokasi baru
 * POST action=update            -> update lokasi (butuh field id)
 * POST action=delete            -> hapus lokasi (butuh field id, ditolak kalau masih ada kegiatan terkait)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_check.php'; // Kelola Lokasi = fitur admin

$method = $_SERVER['REQUEST_METHOD'];

function validasiLokasiInput(array $input): ?string
{
    if (trim($input['nama_lokasi'] ?? '') === '') {
        return 'Nama lokasi wajib diisi.';
    }
    if (trim($input['provinsi'] ?? '') === '') {
        return 'Provinsi wajib dipilih.';
    }
    if (trim($input['kabupaten_kota'] ?? '') === '') {
        return 'Kabupaten/Kota wajib dipilih.';
    }
    if (!isset($input['latitude']) || !is_numeric($input['latitude'])) {
        return 'Titik koordinat (latitude) belum valid. Klik peta untuk menentukan titik lokasi.';
    }
    if (!isset($input['longitude']) || !is_numeric($input['longitude'])) {
        return 'Titik koordinat (longitude) belum valid. Klik peta untuk menentukan titik lokasi.';
    }
    return null;
}

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $q        = trim($_GET['q'] ?? '');
            $provinsi = trim($_GET['provinsi'] ?? '');
            $limit    = (int) ($_GET['limit'] ?? 0);

            $where  = [];
            $params = [];

            if ($q !== '') {
                $where[] = '(l.nama_lokasi LIKE :q1 OR l.kabupaten_kota LIKE :q2)';
                $params['q1'] = "%{$q}%";
                $params['q2'] = "%{$q}%";
            }
            if ($provinsi !== '') {
                $where[] = 'l.provinsi = :provinsi';
                $params['provinsi'] = $provinsi;
            }

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $limitSql = $limit > 0 ? 'LIMIT ' . $limit : '';

            $stmt = $pdo->prepare(
                "SELECT l.*, COUNT(k.id) AS jumlah_kegiatan
                 FROM lokasi l
                 LEFT JOIN kegiatan k ON k.location_id = l.id
                 {$whereSql}
                 GROUP BY l.id
                 ORDER BY l.nama_lokasi ASC
                 {$limitSql}"
            );
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit;
        }

        if ($action === 'provinsi_list') {
            // Cuma provinsi yang beneran punya data lokasi (bukan semua provinsi Indonesia)
            $stmt = $pdo->query('SELECT DISTINCT provinsi FROM lokasi ORDER BY provinsi ASC');
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
            exit;
        }

        if ($action === 'detail') {
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM lokasi WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $lokasi = $stmt->fetch();

            if (!$lokasi) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Lokasi tidak ditemukan.']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $lokasi]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
        exit;
    }

    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $error = validasiLokasiInput($_POST);
            if ($error) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }

            $data = [
                'nama_lokasi'      => trim($_POST['nama_lokasi']),
                'nama_jalan'       => trim($_POST['nama_jalan'] ?? '') ?: null,
                'provinsi'         => trim($_POST['provinsi']),
                'kabupaten_kota'   => trim($_POST['kabupaten_kota']),
                'kecamatan'        => trim($_POST['kecamatan'] ?? '') ?: null,
                'kelurahan'        => trim($_POST['kelurahan'] ?? '') ?: null,
                'negara'           => trim($_POST['negara'] ?? '') ?: 'Indonesia',
                'latitude'         => $_POST['latitude'],
                'longitude'        => $_POST['longitude'],
                'koordinat_manual' => !empty($_POST['koordinat_manual']) ? 1 : 0,
            ];

            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO lokasi
                        (nama_lokasi, nama_jalan, provinsi, kabupaten_kota, kecamatan, kelurahan, negara, latitude, longitude, koordinat_manual)
                     VALUES
                        (:nama_lokasi, :nama_jalan, :provinsi, :kabupaten_kota, :kecamatan, :kelurahan, :negara, :latitude, :longitude, :koordinat_manual)'
                );
                $stmt->execute($data);

                echo json_encode([
                    'success' => true,
                    'message' => 'Lokasi berhasil ditambahkan.',
                    'id'      => $pdo->lastInsertId(),
                ]);
                exit;
            }

            // action === 'update'
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID lokasi tidak valid.']);
                exit;
            }

            $data['id'] = $id;

            $stmt = $pdo->prepare(
                'UPDATE lokasi SET
                    nama_lokasi = :nama_lokasi,
                    nama_jalan = :nama_jalan,
                    provinsi = :provinsi,
                    kabupaten_kota = :kabupaten_kota,
                    kecamatan = :kecamatan,
                    kelurahan = :kelurahan,
                    negara = :negara,
                    latitude = :latitude,
                    longitude = :longitude,
                    koordinat_manual = :koordinat_manual
                 WHERE id = :id'
            );
            $stmt->execute($data);

            echo json_encode(['success' => true, 'message' => 'Lokasi berhasil diperbarui.']);
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID lokasi tidak valid.']);
                exit;
            }

            // Cek dulu apakah masih ada kegiatan yang pakai lokasi ini
            // (backup di level app selain FK ON DELETE RESTRICT di database)
            $check = $pdo->prepare('SELECT COUNT(*) AS total FROM kegiatan WHERE location_id = :id');
            $check->execute(['id' => $id]);
            $total = (int) $check->fetch()['total'];

            if ($total > 0) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => "Lokasi ini masih dipakai oleh {$total} kegiatan. Pindahkan atau hapus kegiatan tersebut dulu sebelum menghapus lokasi.",
                ]);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM lokasi WHERE id = :id');
            $stmt->execute(['id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Lokasi berhasil dihapus.']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
}