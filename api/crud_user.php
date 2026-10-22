<?php
/**
 * /api/crud_user.php
 *
 * GET  ?action=list                 -> semua user (tanpa kolom password)
 * GET  ?action=detail&id=X          -> detail 1 user (tanpa kolom password)
 * POST action=create                -> tambah user baru
 * POST action=update                -> update nama_lengkap/username/role (butuh field id)
 * POST action=reset_password        -> reset password user (butuh field id)
 * POST action=toggle_status         -> aktif <-> nonaktif (butuh field id, tidak bisa untuk diri sendiri)
 * POST action=delete                -> hapus permanen (butuh field id, tidak bisa untuk diri sendiri)
 *
 * Aturan tambahan:
 * - Tidak boleh menonaktifkan atau menghapus akun sendiri.
 * - Akun ber-role superadmin ditampilkan di daftar, tapi tidak bisa jadi
 *   target aksi apapun (edit, reset password, ubah status, hapus) dari sini.
 * - Maksimal MAX_SUPERADMIN akun superadmin boleh ada sekaligus di sistem.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_check.php'; // Kelola User = fitur admin

$method       = $_SERVER['REQUEST_METHOD'];
$currentAdmin = (int) $_SESSION['user_id'];

// Batas maksimal akun superadmin yang boleh ada sekaligus di sistem.
const MAX_SUPERADMIN = 2;

/**
 * Hitung berapa jumlah akun ber-role superadmin saat ini.
 * $kecualiId dipakai saat update, supaya akun yang sedang diedit tidak
 * ikut dihitung dobel terhadap dirinya sendiri.
 */
function jumlahSuperadmin(PDO $pdo, ?int $kecualiId = null): int
{
    if ($kecualiId !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'superadmin' AND id != :id");
        $stmt->execute(['id' => $kecualiId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'superadmin'");
    }

    return (int) $stmt->fetch()['total'];
}

/**
 * Bikin password acak yang gampang dibaca (hindari karakter yang gampang tertukar: 0/O, 1/l/I).
 */
function generatePasswordAcak(int $panjang = 10): string
{
    $charset = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $hasil   = '';
    $max     = strlen($charset) - 1;

    for ($i = 0; $i < $panjang; $i++) {
        $hasil .= $charset[random_int(0, $max)];
    }

    return $hasil;
}

function validasiDataUser(array $input, bool $wajibPassword): ?string
{
    if (trim($input['nama_lengkap'] ?? '') === '') {
        return 'Nama lengkap wajib diisi.';
    }
    if (trim($input['username'] ?? '') === '') {
        return 'Username wajib diisi.';
    }
    if (!in_array($input['role'] ?? '', ['user', 'internal', 'admin', 'superadmin'], true)) {
        return 'Role tidak valid.';
    }

    if ($wajibPassword) {
        $mode = $input['password_mode'] ?? '';
        if (!in_array($mode, ['manual', 'random'], true)) {
            return 'Metode password tidak valid.';
        }
        if ($mode === 'manual' && strlen($input['password'] ?? '') < 8) {
            return 'Password manual minimal 8 karakter.';
        }
    }

    return null;
}

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $stmt = $pdo->query(
                'SELECT id, nama_lengkap, username, role, status, created_at, last_login
                 FROM users
                 ORDER BY nama_lengkap ASC'
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit;
        }

        if ($action === 'detail') {
            $id   = (int) ($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT id, nama_lengkap, username, role, status, created_at, last_login FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();

            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $user]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
        exit;
    }

    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        // ==================== Tambah user ====================
        if ($action === 'create') {
            $error = validasiDataUser($_POST, true);
            if ($error) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }

            $nama_lengkap = trim($_POST['nama_lengkap']);
            $username     = trim($_POST['username']);
            $role         = $_POST['role'];
            $mode         = $_POST['password_mode'];

            if ($role === 'superadmin' && jumlahSuperadmin($pdo) >= MAX_SUPERADMIN) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Sudah ada ' . MAX_SUPERADMIN . ' akun Super Admin. Tidak bisa menambah lagi.']);
                exit;
            }

            $cek = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
            $cek->execute(['username' => $username]);
            if ($cek->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Username sudah dipakai user lain.']);
                exit;
            }

            $passwordPlain = $mode === 'random' ? generatePasswordAcak() : $_POST['password'];
            $hash          = password_hash($passwordPlain, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (nama_lengkap, username, password, role, status)
                 VALUES (:nama_lengkap, :username, :password, :role, :status)'
            );
            $stmt->execute([
                'nama_lengkap' => $nama_lengkap,
                'username'     => $username,
                'password'     => $hash,
                'role'         => $role,
                'status'       => 'aktif',
            ]);

            $response = [
                'success' => true,
                'message' => 'User berhasil ditambahkan.',
                'id'      => $pdo->lastInsertId(),
            ];

            // Password random hanya ditampilkan SEKALI di sini, tidak pernah disimpan plain text.
            if ($mode === 'random') {
                $response['generated_password'] = $passwordPlain;
            }

            echo json_encode($response);
            exit;
        }

        // ==================== Update data user (bukan password) ====================
        if ($action === 'update') {
            $error = validasiDataUser($_POST, false);
            if ($error) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID user tidak valid.']);
                exit;
            }

            $targetStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
            $targetStmt->execute(['id' => $id]);
            $target = $targetStmt->fetch();

            if (!$target) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
                exit;
            }

            if ($target['role'] === 'superadmin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akun Super Admin tidak bisa diedit dari sini.']);
                exit;
            }

            $nama_lengkap = trim($_POST['nama_lengkap']);
            $username     = trim($_POST['username']);
            $role         = $_POST['role'];

            $cek = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
            $cek->execute(['username' => $username, 'id' => $id]);
            if ($cek->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Username sudah dipakai user lain.']);
                exit;
            }

            if ($role === 'superadmin' && jumlahSuperadmin($pdo, $id) >= MAX_SUPERADMIN) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Sudah ada ' . MAX_SUPERADMIN . ' akun Super Admin. Tidak bisa menambah lagi.']);
                exit;
            }

            $stmt = $pdo->prepare(
                'UPDATE users SET nama_lengkap = :nama_lengkap, username = :username, role = :role WHERE id = :id'
            );
            $stmt->execute([
                'nama_lengkap' => $nama_lengkap,
                'username'     => $username,
                'role'         => $role,
                'id'           => $id,
            ]);

            echo json_encode(['success' => true, 'message' => 'Data user berhasil diperbarui.']);
            exit;
        }

        // ==================== Reset password ====================
        if ($action === 'reset_password') {
            $id   = (int) ($_POST['id'] ?? 0);
            $mode = $_POST['password_mode'] ?? '';

            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID user tidak valid.']);
                exit;
            }
            if (!in_array($mode, ['manual', 'random'], true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Metode password tidak valid.']);
                exit;
            }
            if ($mode === 'manual' && strlen($_POST['password'] ?? '') < 8) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Password manual minimal 8 karakter.']);
                exit;
            }

            $cek = $pdo->prepare('SELECT id, role FROM users WHERE id = :id');
            $cek->execute(['id' => $id]);
            $targetUser = $cek->fetch();
            if (!$targetUser) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
                exit;
            }
            if ($targetUser['role'] === 'superadmin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Password akun Super Admin tidak bisa direset dari sini.']);
                exit;
            }

            $passwordPlain = $mode === 'random' ? generatePasswordAcak() : $_POST['password'];
            $hash          = password_hash($passwordPlain, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $stmt->execute(['password' => $hash, 'id' => $id]);

            $response = ['success' => true, 'message' => 'Password user berhasil direset.'];
            if ($mode === 'random') {
                $response['generated_password'] = $passwordPlain;
            }

            echo json_encode($response);
            exit;
        }

        // ==================== Aktifkan / nonaktifkan ====================
        if ($action === 'toggle_status') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID user tidak valid.']);
                exit;
            }

            if ($id === $currentAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Tidak bisa menonaktifkan akun sendiri.']);
                exit;
            }

            $stmt = $pdo->prepare('SELECT status, role FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();

            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
                exit;
            }

            if ($user['role'] === 'superadmin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Status akun Super Admin tidak bisa diubah dari sini.']);
                exit;
            }

            $statusBaru = $user['status'] === 'aktif' ? 'nonaktif' : 'aktif';
            $upd = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
            $upd->execute(['status' => $statusBaru, 'id' => $id]);

            echo json_encode([
                'success' => true,
                'message' => $statusBaru === 'aktif' ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.',
                'status'  => $statusBaru,
            ]);
            exit;
        }

        // ==================== Hapus permanen ====================
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID user tidak valid.']);
                exit;
            }

            if ($id === $currentAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Tidak bisa menghapus akun sendiri.']);
                exit;
            }

            $cek = $pdo->prepare('SELECT id, role FROM users WHERE id = :id');
            $cek->execute(['id' => $id]);
            $targetUser = $cek->fetch();
            if (!$targetUser) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
                exit;
            }

            if ($targetUser['role'] === 'superadmin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akun Super Admin tidak bisa dihapus dari sini.']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);

            echo json_encode(['success' => true, 'message' => 'User berhasil dihapus.']);
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