<?php
/**
 * /api/update_akun.php
 *
 * POST action=update_profil   -> update nama_lengkap & username milik akun yang sedang login
 * POST action=update_password -> ganti password akun yang sedang login (butuh password_lama valid)
 *
 * Catatan: hanya boleh mengubah data diri sendiri (berdasarkan $_SESSION['user_id']),
 * bukan berdasarkan id yang dikirim dari client.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_check.php';

$method  = $_SERVER['REQUEST_METHOD'];
$user_id = (int) $_SESSION['user_id'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_profil') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username     = trim($_POST['username'] ?? '');

        if ($nama_lengkap === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Nama lengkap wajib diisi.']);
            exit;
        }
        if ($username === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Username wajib diisi.']);
            exit;
        }

        // Cek username sudah dipakai user lain atau belum
        $cek = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
        $cek->execute(['username' => $username, 'id' => $user_id]);
        if ($cek->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Username sudah dipakai akun lain.']);
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE users SET nama_lengkap = :nama_lengkap, username = :username WHERE id = :id'
        );
        $stmt->execute([
            'nama_lengkap' => $nama_lengkap,
            'username'     => $username,
            'id'           => $user_id,
        ]);

        // Sinkronkan session biar navbar & halaman lain langsung ikut update
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $_SESSION['username']     = $username;

        echo json_encode([
            'success'      => true,
            'message'      => 'Profil berhasil diperbarui.',
            'nama_lengkap' => $nama_lengkap,
            'username'     => $username,
        ]);
        exit;
    }

    if ($action === 'update_password') {
        $password_lama    = $_POST['password_lama'] ?? '';
        $password_baru    = $_POST['password_baru'] ?? '';
        $konfirmasi_baru  = $_POST['konfirmasi_password_baru'] ?? '';

        if ($password_lama === '' || $password_baru === '' || $konfirmasi_baru === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Semua field password wajib diisi.']);
            exit;
        }
        if (strlen($password_baru) < 8) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password baru minimal 8 karakter.']);
            exit;
        }
        if ($password_baru !== $konfirmasi_baru) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Konfirmasi password baru tidak cocok.']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password_lama, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Password lama yang Anda masukkan salah.']);
            exit;
        }

        if (password_verify($password_baru, $user['password'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password baru tidak boleh sama dengan password lama.']);
            exit;
        }

        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $upd  = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $upd->execute(['password' => $hash, 'id' => $user_id]);

        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
}