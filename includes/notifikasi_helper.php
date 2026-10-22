<?php
/**
 * includes/notifikasi_helper.php
 *
 * Helper buat nulis notifikasi ke tabel `notifikasi`. Dipanggil dari
 * api/crud_kegiatan.php di 3 titik: kegiatan baru diajukan (create atau
 * diajukan-ulang setelah rejected), dan keputusan verifikasi (approve/reject).
 */

/**
 * Tandai SEMUA notifikasi bertipe tertentu buat 1 kegiatan sebagai sudah dibaca --
 * dipanggil begitu tindakan yang diminta notifikasi itu udah selesai diurus
 * (misal: kegiatan udah diverifikasi), TERLEPAS notifikasinya sendiri sempat
 * dibuka/diklik atau enggak, dan TERLEPAS siapa yang ngurus (kalau verifikator
 * A yang approve, notifikasi ke verifikator B soal kegiatan yang sama juga ikut
 * ke-tandai selesai -- soalnya tugasnya emang udah beres, gak perlu diurus lagi
 * sama siapapun). Ini yang bikin badge notifikasi selalu sinkron sama kondisi
 * data yang sebenarnya, gak peduli notifikasinya dibuka lewat mana.
 */
function tandaiNotifikasiSelesai(PDO $pdo, int $kegiatanId, string $tipe): void
{
    $pdo->prepare(
        'UPDATE notifikasi SET dibaca = 1 WHERE kegiatan_id = :kegiatan_id AND tipe = :tipe AND dibaca = 0'
    )->execute(['kegiatan_id' => $kegiatanId, 'tipe' => $tipe]);
}

/**
 * Kirim notifikasi 'kegiatan_baru' ke semua verifikator (role admin & superadmin).
 * Dipanggil begitu kegiatan masuk status_verifikasi='pending' (baik kegiatan
 * baru maupun yang diajukan ulang setelah direvisi dari 'rejected').
 */
function notifikasiKegiatanBaru(PDO $pdo, int $kegiatanId, string $judulKegiatan, bool $diajukanUlang = false): void
{
    $penerima = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'superadmin')")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($penerima)) {
        return;
    }

    $pesan = $diajukanUlang
        ? "Kegiatan \"{$judulKegiatan}\" diajukan ulang dan menunggu verifikasi Anda."
        : "Kegiatan baru \"{$judulKegiatan}\" menunggu verifikasi Anda.";

    $stmt = $pdo->prepare(
        'INSERT INTO notifikasi (user_id, tipe, kegiatan_id, pesan)
         VALUES (:user_id, \'kegiatan_baru\', :kegiatan_id, :pesan)'
    );

    foreach ($penerima as $userId) {
        $stmt->execute(['user_id' => $userId, 'kegiatan_id' => $kegiatanId, 'pesan' => $pesan]);
    }
}

/**
 * Kirim notifikasi 'revisi_baru' ke semua verifikator (role admin & superadmin)
 * begitu Internal mengajukan revisi untuk kegiatan yang sudah 'approved'.
 */
function notifikasiRevisiBaru(PDO $pdo, int $kegiatanId, string $judulKegiatan): void
{
    $penerima = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'superadmin')")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($penerima)) {
        return;
    }

    $pesan = "Pengajuan revisi untuk kegiatan \"{$judulKegiatan}\" menunggu persetujuan Anda.";

    $stmt = $pdo->prepare(
        'INSERT INTO notifikasi (user_id, tipe, kegiatan_id, pesan)
         VALUES (:user_id, \'revisi_baru\', :kegiatan_id, :pesan)'
    );

    foreach ($penerima as $userId) {
        $stmt->execute(['user_id' => $userId, 'kegiatan_id' => $kegiatanId, 'pesan' => $pesan]);
    }
}

/**
 * Kirim notifikasi 'keputusan_revisi' (approve/reject) ke pengaju revisi
 * dan semua user role superadmin. Kalau pengajunya sendiri superadmin,
 * gak dobel dikirim.
 */
function notifikasiKeputusanRevisi(
    PDO $pdo,
    int $kegiatanId,
    string $judulKegiatan,
    ?int $diajukanOleh,
    string $keputusan, // 'approve' | 'reject'
    ?string $catatan
): void {
    $penerima = [];
    if ($diajukanOleh) {
        $penerima[] = $diajukanOleh;
    }

    $superadminIds = $pdo->query("SELECT id FROM users WHERE role = 'superadmin'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($superadminIds as $id) {
        if (!in_array((int) $id, $penerima, true)) {
            $penerima[] = (int) $id;
        }
    }

    if (empty($penerima)) {
        return;
    }

    $pesan = $keputusan === 'approve'
        ? "Revisi untuk kegiatan \"{$judulKegiatan}\" telah disetujui dan diterapkan."
        : "Revisi untuk kegiatan \"{$judulKegiatan}\" ditolak." . ($catatan ? " Catatan: {$catatan}" : '');

    $stmt = $pdo->prepare(
        'INSERT INTO notifikasi (user_id, tipe, kegiatan_id, pesan)
         VALUES (:user_id, \'keputusan_revisi\', :kegiatan_id, :pesan)'
    );

    foreach ($penerima as $userId) {
        $stmt->execute(['user_id' => $userId, 'kegiatan_id' => $kegiatanId, 'pesan' => $pesan]);
    }
}

/**
 * Kirim notifikasi 'keputusan_verifikasi' (approve/reject) ke pembuat kegiatan
 * (dibuat_oleh) dan semua user role superadmin. Kalau pembuatnya sendiri
 * superadmin, gak dobel dikirim.
 */
function notifikasiKeputusanVerifikasi(
    PDO $pdo,
    int $kegiatanId,
    string $judulKegiatan,
    ?int $dibuatOleh,
    string $keputusan, // 'approve' | 'reject'
    ?string $catatan
): void {
    $penerima = [];
    if ($dibuatOleh) {
        $penerima[] = $dibuatOleh;
    }

    $superadminIds = $pdo->query("SELECT id FROM users WHERE role = 'superadmin'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($superadminIds as $id) {
        if (!in_array((int) $id, $penerima, true)) {
            $penerima[] = (int) $id;
        }
    }

    if (empty($penerima)) {
        return;
    }

    $pesan = $keputusan === 'approve'
        ? "Kegiatan \"{$judulKegiatan}\" telah disetujui dan tayang di peta publik."
        : "Kegiatan \"{$judulKegiatan}\" ditolak." . ($catatan ? " Catatan: {$catatan}" : '');

    $stmt = $pdo->prepare(
        'INSERT INTO notifikasi (user_id, tipe, kegiatan_id, pesan)
         VALUES (:user_id, \'keputusan_verifikasi\', :kegiatan_id, :pesan)'
    );

    foreach ($penerima as $userId) {
        $stmt->execute(['user_id' => $userId, 'kegiatan_id' => $kegiatanId, 'pesan' => $pesan]);
    }
}