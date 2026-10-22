<?php
/**
 * /api/notifikasi.php
 *
 * GET  ?action=list                 -> 20 notifikasi terbaru milik user yang login
 * POST action=tandai_dibaca         -> tandai 1 notifikasi dibaca (butuh id)
 * POST action=tandai_semua_dibaca   -> tandai semua notifikasi milik user ini dibaca
 *
 * Wajib login (role apapun yang punya akses ke navbar_admin.php --
 * internal/admin/superadmin). Notifikasi cuma dikirim ke user yang relevan
 * (lihat includes/notifikasi_helper.php), jadi di sini cukup filter WHERE
 * user_id = user yang login, gak perlu pengecekan role tambahan.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/staff_check.php';

require_once __DIR__ . '/../config/database.php';

$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';
$userId  = (int) $_SESSION['user_id'];

if ($action === 'list') {
    $stmt = $pdo->prepare(
        'SELECT id, tipe, kegiatan_id, pesan, dibaca, created_at
         FROM notifikasi
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT 20'
    );
    $stmt->execute(['user_id' => $userId]);
    $data = $stmt->fetchAll();

    // Cast `dibaca` ke boolean asli biar gampang dipakai di JS (bukan string "0"/"1").
    foreach ($data as &$row) {
        $row['dibaca'] = (bool) $row['dibaca'];
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'tandai_dibaca') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid.']);
        exit;
    }

    // WHERE ikut user_id -- jangan sampai user A bisa tandai-dibaca notifikasi user B.
    $stmt = $pdo->prepare('UPDATE notifikasi SET dibaca = 1 WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $userId]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'tandai_semua_dibaca') {
    $stmt = $pdo->prepare('UPDATE notifikasi SET dibaca = 1 WHERE user_id = :user_id AND dibaca = 0');
    $stmt->execute(['user_id' => $userId]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);