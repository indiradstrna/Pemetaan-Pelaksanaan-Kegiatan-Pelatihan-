<?php
/**
 * /user/detail_kegiatan.php
 *
 * Halaman detail 1 kegiatan. Wajib login (keputusan awal project: peta bisa
 * diakses publik, tapi detail kegiatan cuma buat yang sudah login). Kalau
 * belum login, diarahkan ke auth/login.php dengan parameter `next` supaya
 * setelah login sukses langsung balik ke halaman ini juga.
 */

session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);

// ---------- Asal navigasi (buat nentuin tujuan tombol "Kembali") ----------
// Whitelist nilai supaya gak dipakai buat open-redirect.
$from = ($_GET['from'] ?? '') === 'admin' ? 'admin' : '';

if (!isset($_SESSION['user_id'])) {
    $next = BASE_URL . '/user/detail_kegiatan.php?id=' . $id . ($from ? '&from=' . $from : '');
    header('Location: ' . BASE_URL . '/auth/login.php?next=' . urlencode($next));
    exit;
}

if ($from === 'admin') {
    $backUrl   = BASE_URL . '/admin/dashboard.php';
    $backLabel = 'Kembali ke Dashboard';
} else {
    $backUrl   = BASE_URL . '/user/index.php';
    $backLabel = 'Kembali ke Peta';
}

const STATUS_CASE_SQL_DETAIL = "
    CASE
        WHEN k.status = 'selesai' THEN 'selesai'
        WHEN CURDATE() < k.tanggal_mulai THEN 'belum_berlangsung'
        ELSE 'berlangsung'
    END
";

$stmt = $pdo->prepare(
    "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
            k.jumlah_peserta, k.penyelenggara, k.skala_kegiatan, k.deskripsi,
            k.rencana_output, k.deskripsi_hasil, k.foto_utama, k.location_id,
            " . STATUS_CASE_SQL_DETAIL . " AS status,
            l.nama_lokasi, l.nama_jalan, l.kelurahan, l.kecamatan,
            l.kabupaten_kota, l.provinsi
     FROM kegiatan k
     JOIN lokasi l ON l.id = k.location_id
     WHERE k.id = :id"
);
$stmt->execute(['id' => $id]);
$kegiatan = $stmt->fetch();

// ---------- 404 sederhana kalau kegiatan gak ketemu ----------
if (!$kegiatan) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Kegiatan Tidak Ditemukan — SEAMEO BIOTROP</title>
        <style>
            body { font-family: sans-serif; text-align: center; padding: 80px 20px; color: #16181a; }
            a { color: #1b5e20; font-weight: 700; }
        </style>
    </head>
    <body>
        <h2>Kegiatan tidak ditemukan</h2>
        <p>Kegiatan yang kamu cari mungkin sudah dihapus atau linknya salah.</p>
        <a href="<?= htmlspecialchars($backUrl) ?>">&larr; <?= htmlspecialchars($backLabel) ?></a>
    </body>
    </html>
    <?php
    exit;
}

$tagStmt = $pdo->prepare(
    'SELECT nama_tag FROM tags t
     JOIN kegiatan_tags kt ON kt.tag_id = t.id
     WHERE kt.kegiatan_id = :id
     ORDER BY nama_tag ASC'
);
$tagStmt->execute(['id' => $id]);
$tags = array_column($tagStmt->fetchAll(), 'nama_tag');

$deptStmt = $pdo->prepare(
    'SELECT departemen_kode FROM kegiatan_departemen
     WHERE kegiatan_id = :id ORDER BY departemen_kode ASC'
);
$deptStmt->execute(['id' => $id]);
$departemen = array_column($deptStmt->fetchAll(), 'departemen_kode');

$fotoStmt = $pdo->prepare(
    'SELECT path_foto, caption FROM kegiatan_foto WHERE kegiatan_id = :id ORDER BY urutan ASC LIMIT 5'
);
$fotoStmt->execute(['id' => $id]);
$fotoDokumentasi = array_map(
    fn($f) => ['src' => BASE_URL . '/' . $f['path_foto'], 'caption' => $f['caption']],
    $fotoStmt->fetchAll()
);
$fotoUtamaUrl = $kegiatan['foto_utama'] ? BASE_URL . '/' . $kegiatan['foto_utama'] : null;

// ---------- Kegiatan Lainnya: berbagi minimal 1 tag yang sama ----------
$lainnyaStmt = $pdo->prepare(
    "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
            k.jumlah_peserta, k.penyelenggara, k.foto_utama,
            " . STATUS_CASE_SQL_DETAIL . " AS status,
            l.kabupaten_kota, l.provinsi,
            (
                SELECT GROUP_CONCAT(t2.nama_tag ORDER BY t2.nama_tag SEPARATOR '||')
                FROM kegiatan_tags kt2
                JOIN tags t2 ON t2.id = kt2.tag_id
                WHERE kt2.kegiatan_id = k.id
            ) AS tags_concat
     FROM kegiatan k
     JOIN lokasi l ON l.id = k.location_id
     WHERE k.id != :id
       AND EXISTS (
           SELECT 1 FROM kegiatan_tags kt_a
           JOIN kegiatan_tags kt_b ON kt_b.tag_id = kt_a.tag_id
           WHERE kt_a.kegiatan_id = :id2 AND kt_b.kegiatan_id = k.id
       )
     ORDER BY k.tanggal_mulai DESC
     LIMIT 10"
);
$lainnyaStmt->execute(['id' => $id, 'id2' => $id]);
$kegiatanLainnya = $lainnyaStmt->fetchAll();

// Fallback tingkat 2: kalau gak ada yang share tag, cari kegiatan lain yang
// judulnya punya kata-kata penting yang mirip sama judul kegiatan ini (skip
// kata sambung umum). Diurut dari yang paling banyak kata cocok.
if (empty($kegiatanLainnya)) {
    $stopwords = ['dan', 'atau', 'yang', 'dengan', 'untuk', 'dari', 'pada', 'oleh', 'para', 'akan',
                  'dalam', 'serta', 'kepada', 'antar', 'melalui', 'terhadap', 'kepada', 'bagi'];
    $kataJudul = preg_split('/[^a-zA-Z0-9]+/', $kegiatan['judul'], -1, PREG_SPLIT_NO_EMPTY);
    $kataPenting = array_values(array_unique(array_filter(
        $kataJudul,
        fn($k) => mb_strlen($k) >= 4 && !in_array(mb_strtolower($k), $stopwords, true)
    )));

    if (!empty($kataPenting)) {
        $skorCase = [];
        $params = ['id' => $id];
        foreach ($kataPenting as $i => $kata) {
            $ph = "kata{$i}";
            $skorCase[] = "(CASE WHEN k.judul LIKE :{$ph} THEN 1 ELSE 0 END)";
            $params[$ph] = '%' . $kata . '%';
        }
        $skorSql = implode(' + ', $skorCase);

        $miripStmt = $pdo->prepare(
            "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
                    k.jumlah_peserta, k.penyelenggara, k.foto_utama,
                    " . STATUS_CASE_SQL_DETAIL . " AS status,
                    l.kabupaten_kota, l.provinsi,
                    ({$skorSql}) AS skor_relevansi,
                    (
                        SELECT GROUP_CONCAT(t2.nama_tag ORDER BY t2.nama_tag SEPARATOR '||')
                        FROM kegiatan_tags kt2
                        JOIN tags t2 ON t2.id = kt2.tag_id
                        WHERE kt2.kegiatan_id = k.id
                    ) AS tags_concat
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             WHERE k.id != :id
             HAVING skor_relevansi > 0
             ORDER BY skor_relevansi DESC, k.tanggal_mulai DESC
             LIMIT 10"
        );
        $miripStmt->execute($params);
        $kegiatanLainnya = $miripStmt->fetchAll();
    }
}

// Fallback tingkat 3: kalau tetep gak ada (gak ada tag & gak ada kata judul yang
// mirip), tampilkan kegiatan terbaru secara umum aja (biar section-nya gak pernah kosong).
if (empty($kegiatanLainnya)) {
    $fallbackStmt = $pdo->prepare(
        "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
                k.jumlah_peserta, k.penyelenggara, k.foto_utama,
                " . STATUS_CASE_SQL_DETAIL . " AS status,
                l.kabupaten_kota, l.provinsi,
                (
                    SELECT GROUP_CONCAT(t2.nama_tag ORDER BY t2.nama_tag SEPARATOR '||')
                    FROM kegiatan_tags kt2
                    JOIN tags t2 ON t2.id = kt2.tag_id
                    WHERE kt2.kegiatan_id = k.id
                ) AS tags_concat
         FROM kegiatan k
         JOIN lokasi l ON l.id = k.location_id
         WHERE k.id != :id
         ORDER BY k.tanggal_mulai DESC
         LIMIT 10"
    );
    $fallbackStmt->execute(['id' => $id]);
    $kegiatanLainnya = $fallbackStmt->fetchAll();
}

// ---------- Helper kecil ----------
function formatTanggalIndo(?string $tanggal): string
{
    if (!$tanggal) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    [$y, $m, $d] = explode('-', $tanggal);
    return ((int) $d) . ' ' . $bulan[(int) $m] . ' ' . $y;
}

function labelStatusKegiatan(string $status): string
{
    return [
        'selesai'           => 'Selesai',
        'berlangsung'       => 'Berlangsung',
        'belum_berlangsung' => 'Akan Datang',
    ][$status] ?? $status;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kegiatan['judul']) ?> — SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-start:  #43a047;
            --green-end:    #1b5e20;
            --green-soft:   #e8f5e9;
            --status-selesai:      #2563eb;
            --status-berlangsung:  #e11d48;
            --status-akan-datang:  #7c3aed;
            --status-selesai-soft:     #e8f0fe;
            --status-berlangsung-soft: #fde8ec;
            --status-akan-datang-soft: #f1e9fc;
            --text-dark:  #16181a;
            --text-gray:  #6b7280;
            --border-gray:#d8dbe0;
            --bg-page: #f4f5f6;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            margin: 0;
            background: var(--bg-page);
        }

        /* ---------- Topbar (sama kayak user/index.php) ---------- */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
            background: linear-gradient(90deg, var(--green-end), var(--green-start));
            color: #fff;
        }
        .topbar .brand { display: flex; align-items: center; gap: 12px; font-weight: 700; }
        .topbar .brand .title { line-height: 1.15; }
        .topbar .brand .title small { display: block; font-weight: 500; font-size: .78rem; opacity: .9; }
        .topbar .actions { display: flex; align-items: center; gap: 10px; }
        .topbar .actions > span, .topbar .actions > a.btn-login-outline { color: #fff; font-size: .9rem; text-decoration: none; }
        .btn-login-outline {
            border: 1.5px solid rgba(255,255,255,.8);
            padding: 7px 18px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-login-outline:hover { background: rgba(255,255,255,.15); color: #fff; }

        .user-pill-wrapper { position: relative; }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            border-radius: 999px;
            padding: 5px 14px 5px 5px;
            font-size: .86rem;
            font-weight: 600;
            color: var(--text-dark);
            border: none;
            cursor: pointer;
            font-family: inherit;
        }

        .user-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--green-soft);
            color: var(--green-end);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .78rem;
            flex-shrink: 0;
        }

        .user-pill-caret { font-size: .7rem; transition: transform .2s ease; }
        .user-pill-wrapper.open .user-pill-caret { transform: rotate(180deg); }

        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 190px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
            padding: 6px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity .15s ease, transform .15s ease, visibility .15s;
            z-index: 1000;
        }
        .user-pill-wrapper.open .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 6px;
            color: #333;
            text-decoration: none;
            font-size: .85rem;
        }
        .user-dropdown-item:hover { background: #f2f2f2; }

        .logout-btn-white {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f3f4f6;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-gray);
            cursor: pointer;
            text-decoration: none;
            font-size: .95rem;
            flex-shrink: 0;
        }
        .logout-btn-white:hover { background: #e5e7eb; color: var(--text-dark); }

        /* ---------- Konten ---------- */
        .detail-wrap { max-width: 1000px; margin: 0 auto; padding: 24px 24px 60px; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--green-end);
            font-weight: 700;
            font-size: .92rem;
            text-decoration: none;
            margin-bottom: 14px;
        }
        .back-link:hover { text-decoration: underline; }

        .breadcrumb-row {
            font-size: .82rem;
            color: var(--text-gray);
            margin-bottom: 16px;
        }
        .breadcrumb-row a { color: var(--text-gray); text-decoration: none; }
        .breadcrumb-row a:hover { text-decoration: underline; }

        .top-badges { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .badge-kode {
            background: #eef0f2; color: #444; font-size: .76rem; font-weight: 700;
            padding: 4px 10px; border-radius: 6px;
        }
        .badge-status-lg {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: .82rem; font-weight: 700; padding: 5px 14px; border-radius: 20px;
        }
        .badge-status-lg.selesai { background: var(--status-selesai-soft); color: var(--status-selesai); }
        .badge-status-lg.berlangsung { background: var(--status-berlangsung-soft); color: var(--status-berlangsung); }
        .badge-status-lg.belum_berlangsung { background: var(--status-akan-datang-soft); color: var(--status-akan-datang); }

        .judul-kegiatan { font-size: 2rem; font-weight: 800; margin: 0 0 20px; line-height: 1.25; }

        /* ---------- Grid foto utama + info card ---------- */
        .top-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .foto-utama-wrap {
            border: 1.5px solid var(--green-start);
            border-radius: 14px;
            overflow: hidden;
            background: #e5e7eb;
            min-height: 260px;
        }
        .foto-utama-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .foto-utama-kosong {
            display: flex; align-items: center; justify-content: center;
            height: 100%; min-height: 260px; color: #9aa0a6; font-size: 2.2rem;
        }

        .info-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px 24px;
        }
        .info-row { display: flex; gap: 28px; flex-wrap: wrap; margin-bottom: 16px; }
        .info-item .info-label {
            display: flex; align-items: center; gap: 6px;
            font-size: .78rem; color: var(--text-gray); margin-bottom: 4px;
        }
        .info-item .info-value { font-size: .98rem; font-weight: 700; color: var(--green-end); }

        .info-divider { border: none; border-top: 1px solid var(--border-gray); margin: 4px 0 16px; }

        .info-lokasi-label {
            display: flex; align-items: center; gap: 6px;
            font-size: .78rem; color: var(--text-gray); margin-bottom: 4px;
        }
        .info-lokasi-nama { font-size: 1rem; font-weight: 700; color: var(--green-end); margin-bottom: 2px; }
        .info-lokasi-alamat { font-size: .84rem; color: var(--text-gray); }

        /* ---------- Card konten ---------- */
        .content-card {
            background: #fff;
            border-radius: 14px;
            padding: 26px 28px;
            margin-bottom: 20px;
        }
        .content-card h2 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--green-end);
            margin-bottom: 18px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f1f2;
        }
        .field-row:last-child { border-bottom: none; }
        .field-label { font-weight: 700; font-size: .88rem; }
        .field-value { font-size: .92rem; color: #333; line-height: 1.55; }
        .field-value.kosong { color: var(--text-gray); font-style: italic; }

        .pill-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag-pill { background: #fff8e1; color: #8d6e00; font-size: .78rem; font-weight: 700; padding: 4px 11px; border-radius: 6px; }
        .dept-pill { background: #ccfbf1; color: #0d9488; font-size: .78rem; font-weight: 700; padding: 4px 11px; border-radius: 6px; }

        .hasil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 700px) { .hasil-grid { grid-template-columns: 1fr; } }

        .dok-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
        }
        .dok-thumb-btn {
            display: block;
            width: 100%;
            aspect-ratio: 1;
            border: 2px solid var(--green-start);
            border-radius: 10px;
            overflow: hidden;
            padding: 0;
            background: none;
            cursor: pointer;
        }
        .dok-grid img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .dok-kosong { color: var(--text-gray); font-size: .88rem; font-style: italic; }

        /* ---------- Lightbox popup buat foto dokumentasi ---------- */
        .lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 15, 12, .88);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .lightbox-img {
            max-width: 88vw;
            max-height: 82vh;
            border-radius: 8px;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            border: none;
            color: #fff;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .lightbox-close:hover { background: rgba(255,255,255,.22); }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            border: none;
            color: #fff;
            font-size: 1.3rem;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .lightbox-nav:hover { background: rgba(255,255,255,.22); }
        .lightbox-nav.prev { left: 24px; }
        .lightbox-nav.next { right: 24px; }
        .lightbox-counter {
            position: absolute;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-size: .82rem;
            background: rgba(255,255,255,.12);
            padding: 4px 14px;
            border-radius: 20px;
        }
        .lightbox-caption {
            position: absolute;
            bottom: 60px;
            left: 50%;
            transform: translateX(-50%);
            max-width: 80%;
            color: #fff;
            font-size: .9rem;
            text-align: center;
            background: rgba(0,0,0,.45);
            padding: 6px 16px;
            border-radius: 10px;
        }

        /* ---------- Kegiatan lainnya ---------- */
        .lainnya-title { font-size: 1.1rem; font-weight: 700; color: var(--text-gray); margin: 28px 0 16px; }
        .lainnya-grid { position: relative; }
        .lainnya-page { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 700px) { .lainnya-page { grid-template-columns: 1fr; } }

        .kegiatan-card-lainnya {
            display: block;
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 12px;
            padding: 14px;
            text-decoration: none;
            color: inherit;
            transition: box-shadow .15s ease, border-color .15s ease, transform .15s ease;
            cursor: pointer;
        }
        .kegiatan-card-lainnya:hover {
            border-color: var(--green-end);
            box-shadow: 0 4px 14px rgba(0,0,0,.08);
            transform: translateY(-2px);
        }
        .card-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px; }
        .badge-status { font-size: .7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .badge-status.selesai { background: var(--status-selesai-soft); color: var(--status-selesai); }
        .badge-status.berlangsung { background: var(--status-berlangsung-soft); color: var(--status-berlangsung); }
        .badge-status.belum_berlangsung { background: var(--status-akan-datang-soft); color: var(--status-akan-datang); }
        .card-date { font-size: .76rem; color: var(--text-gray); display: flex; align-items: center; gap: 4px; }
        .card-peserta { font-size: .8rem; font-weight: 700; color: var(--green-end); display: flex; align-items: center; gap: 5px; margin-bottom: 6px; }
        .card-title { font-weight: 700; font-size: .94rem; margin-bottom: 6px; line-height: 1.3; }
        .card-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .card-meta { font-size: .78rem; color: var(--text-gray); line-height: 1.4; }

        .lainnya-pagination { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 16px; }
        .lainnya-nav {
            width: 34px; height: 34px; border-radius: 50%;
            border: 1px solid var(--border-gray); background: #fff; color: var(--green-end);
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem; cursor: pointer; transition: background .15s ease;
        }
        .lainnya-nav:hover:not(:disabled) { background: #eafaf0; }
        .lainnya-nav:disabled { color: #ccc; border-color: #eee; cursor: not-allowed; }

        @media (max-width: 800px) {
            .top-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- ================= TOPBAR ================= -->
    <?php $tampilkanLinkPanelAdmin = true; ?>
    <?php require_once __DIR__ . '/../includes/navbar_user.php'; ?>

    <div class="detail-wrap">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="back-link"><i class="bi bi-arrow-left"></i> <?= htmlspecialchars($backLabel) ?></a>

        <div class="breadcrumb-row">
            <a href="index.php">Halaman Utama</a> &nbsp;›&nbsp; <?= htmlspecialchars($kegiatan['judul']) ?>
        </div>

        <div class="top-badges">
            <span class="badge-kode"><?= htmlspecialchars($kegiatan['kode_kegiatan']) ?></span>
            <span class="badge-status-lg <?= $kegiatan['status'] ?>">
                <?= labelStatusKegiatan($kegiatan['status']) ?>
            </span>
        </div>

        <h1 class="judul-kegiatan"><?= htmlspecialchars($kegiatan['judul']) ?></h1>

        <div class="top-grid">
            <div class="foto-utama-wrap">
                <?php if ($fotoUtamaUrl): ?>
                    <img src="<?= htmlspecialchars($fotoUtamaUrl) ?>" alt="<?= htmlspecialchars($kegiatan['judul']) ?>">
                <?php else: ?>
                    <div class="foto-utama-kosong"><i class="bi bi-image"></i></div>
                <?php endif; ?>
            </div>

            <div class="info-card">
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-calendar3"></i> Tanggal Mulai</div>
                        <div class="info-value"><?= formatTanggalIndo($kegiatan['tanggal_mulai']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-calendar3"></i> Tanggal Selesai</div>
                        <div class="info-value"><?= formatTanggalIndo($kegiatan['tanggal_selesai']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-people-fill"></i> Jumlah Peserta</div>
                        <div class="info-value"><?= $kegiatan['jumlah_peserta'] !== null ? number_format((int) $kegiatan['jumlah_peserta'], 0, ',', '.') . ' Orang' : '-' ?></div>
                    </div>
                </div>

                <hr class="info-divider">

                <div class="info-lokasi-label"><i class="bi bi-geo-alt-fill"></i> Lokasi Kegiatan</div>
                <div class="info-lokasi-nama"><?= htmlspecialchars($kegiatan['nama_lokasi']) ?></div>
                <div class="info-lokasi-alamat">
                    <?= htmlspecialchars(implode(', ', array_filter([
                        $kegiatan['kelurahan'] ? 'Kelurahan ' . $kegiatan['kelurahan'] : null,
                        $kegiatan['kecamatan'] ? 'Kecamatan ' . $kegiatan['kecamatan'] : null,
                        $kegiatan['kabupaten_kota'],
                        $kegiatan['provinsi'],
                    ]))) ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h2>Gambaran Kegiatan</h2>

            <div class="field-row">
                <div class="field-label">Deskripsi Kegiatan</div>
                <div class="field-value <?= $kegiatan['deskripsi'] ? '' : 'kosong' ?>">
                    <?= $kegiatan['deskripsi'] ? nl2br(htmlspecialchars($kegiatan['deskripsi'])) : 'Belum diisi.' ?>
                </div>
            </div>
            <div class="field-row">
                <div class="field-label">Penyelenggara</div>
                <div class="field-value <?= $kegiatan['penyelenggara'] ? '' : 'kosong' ?>">
                    <?= $kegiatan['penyelenggara'] ? htmlspecialchars($kegiatan['penyelenggara']) : 'Belum diisi.' ?>
                </div>
            </div>
            <div class="field-row">
                <div class="field-label">Skala Kegiatan</div>
                <div class="field-value"><?= htmlspecialchars($kegiatan['skala_kegiatan']) ?></div>
            </div>
            <div class="field-row">
                <div class="field-label">Klasifikasi</div>
                <div class="field-value">
                    <?php if (empty($tags)): ?>
                        <span class="kosong">Belum ada tag.</span>
                    <?php else: ?>
                        <div class="pill-row">
                            <?php foreach ($tags as $t): ?>
                                <span class="tag-pill"><?= htmlspecialchars($t) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="field-row">
                <div class="field-label">Departemen</div>
                <div class="field-value">
                    <?php if (empty($departemen)): ?>
                        <span class="kosong">Belum ada departemen.</span>
                    <?php else: ?>
                        <div class="pill-row">
                            <?php foreach ($departemen as $dcode): ?>
                                <span class="dept-pill"><?= htmlspecialchars($dcode) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h2>Hasil Kegiatan</h2>
            <div class="hasil-grid">
                <div>
                    <div class="field-label mb-2">Rencana Output</div>
                    <div class="field-value <?= $kegiatan['rencana_output'] ? '' : 'kosong' ?>">
                        <?= $kegiatan['rencana_output'] ? nl2br(htmlspecialchars($kegiatan['rencana_output'])) : 'Belum diisi.' ?>
                    </div>
                </div>
                <div>
                    <div class="field-label mb-2">Deskripsi Hasil</div>
                    <div class="field-value <?= $kegiatan['deskripsi_hasil'] ? '' : 'kosong' ?>">
                        <?= $kegiatan['deskripsi_hasil'] ? nl2br(htmlspecialchars($kegiatan['deskripsi_hasil'])) : 'Belum diisi.' ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h2>Dokumentasi Kegiatan</h2>
            <?php if (empty($fotoDokumentasi)): ?>
                <div class="dok-kosong">Belum ada foto dokumentasi.</div>
            <?php else: ?>
                <div class="dok-grid">
                    <?php foreach ($fotoDokumentasi as $i => $foto): ?>
                        <button type="button" class="dok-thumb-btn" onclick="openLightbox(<?= $i ?>)" title="<?= htmlspecialchars($foto['caption'] ?? '') ?>">
                            <img src="<?= htmlspecialchars($foto['src']) ?>" alt="<?= htmlspecialchars($foto['caption'] ?: 'Dokumentasi kegiatan') ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($kegiatanLainnya)):
            $lainnyaHalaman = array_chunk($kegiatanLainnya, 2);
            $totalHalamanLainnya = count($lainnyaHalaman);
        ?>
            <div class="lainnya-title">Kegiatan Lainnya</div>
            <div class="lainnya-wrap">
                <div class="lainnya-grid" id="lainnyaGrid">
                    <?php foreach ($lainnyaHalaman as $halIdx => $itemHalaman): ?>
                        <div class="lainnya-page" data-page="<?= $halIdx ?>" style="<?= $halIdx === 0 ? '' : 'display:none;' ?>">
                            <?php foreach ($itemHalaman as $item):
                                $itemTags = $item['tags_concat'] ? explode('||', $item['tags_concat']) : [];
                            ?>
                                <a href="detail_kegiatan.php?id=<?= (int) $item['id'] ?>" class="kegiatan-card-lainnya">
                                    <div class="card-top-row">
                                        <span class="badge-kode"><?= htmlspecialchars($item['kode_kegiatan']) ?></span>
                                        <span class="badge-status <?= $item['status'] ?>"><?= labelStatusKegiatan($item['status']) ?></span>
                                        <span class="card-date"><i class="bi bi-calendar3"></i> <?= formatTanggalIndo($item['tanggal_mulai']) ?></span>
                                    </div>
                                    <div class="card-peserta"><i class="bi bi-people-fill"></i> <?= (int) ($item['jumlah_peserta'] ?? 0) ?> Peserta</div>
                                    <div class="card-title"><?= htmlspecialchars($item['judul']) ?></div>
                                    <div class="card-tags">
                                        <?php foreach ($itemTags as $t): ?>
                                            <span class="tag-pill"><?= htmlspecialchars($t) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="card-meta">
                                        <?= htmlspecialchars($item['penyelenggara'] ?: '-') ?><br>
                                        <?= htmlspecialchars($item['kabupaten_kota']) ?>, <?= htmlspecialchars($item['provinsi']) ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalHalamanLainnya > 1): ?>
                    <div class="lainnya-pagination">
                        <button type="button" class="lainnya-nav" id="lainnyaPrev" onclick="gantiHalamanLainnya(-1)" disabled aria-label="Halaman sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="lainnya-nav" id="lainnyaNext" onclick="gantiHalamanLainnya(1)" aria-label="Halaman berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ================= LIGHTBOX FOTO DOKUMENTASI ================= -->
    <div class="lightbox-overlay" id="lightboxOverlay" style="display:none;">
        <button type="button" class="lightbox-close" id="lightboxClose"><i class="bi bi-x-lg"></i></button>
        <button type="button" class="lightbox-nav prev" id="lightboxPrev" style="display:none;"><i class="bi bi-chevron-left"></i></button>
        <img src="" alt="Dokumentasi kegiatan" class="lightbox-img" id="lightboxImg">
        <button type="button" class="lightbox-nav next" id="lightboxNext" style="display:none;"><i class="bi bi-chevron-right"></i></button>
        <div class="lightbox-caption" id="lightboxCaption" style="display:none;"></div>
        <div class="lightbox-counter" id="lightboxCounter"></div>
    </div>

    <script>
        (function () {
            var fotoList = <?= json_encode($fotoDokumentasi) ?>;
            var idx = 0;

            var overlay  = document.getElementById('lightboxOverlay');
            var imgEl    = document.getElementById('lightboxImg');
            var captionEl = document.getElementById('lightboxCaption');
            var counter  = document.getElementById('lightboxCounter');
            var prevBtn  = document.getElementById('lightboxPrev');
            var nextBtn  = document.getElementById('lightboxNext');
            var closeBtn = document.getElementById('lightboxClose');

            window.openLightbox = function (i) {
                idx = i;
                render();
                overlay.style.display = 'flex';
            };

            function render() {
                var foto = fotoList[idx];
                imgEl.src = foto.src;
                counter.textContent = (idx + 1) + ' / ' + fotoList.length;
                if (foto.caption) {
                    captionEl.textContent = foto.caption;
                    captionEl.style.display = 'block';
                } else {
                    captionEl.style.display = 'none';
                }
                var showNav = fotoList.length > 1;
                prevBtn.style.display = showNav ? 'flex' : 'none';
                nextBtn.style.display = showNav ? 'flex' : 'none';
            }

            function closeLightbox() { overlay.style.display = 'none'; }

            closeBtn.addEventListener('click', closeLightbox);
            overlay.addEventListener('click', function (e) { if (e.target === overlay) closeLightbox(); });
            prevBtn.addEventListener('click', function () { idx = (idx - 1 + fotoList.length) % fotoList.length; render(); });
            nextBtn.addEventListener('click', function () { idx = (idx + 1) % fotoList.length; render(); });

            document.addEventListener('keydown', function (e) {
                if (overlay.style.display === 'none') return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') prevBtn.click();
                if (e.key === 'ArrowRight') nextBtn.click();
            });
        })();
    </script>

    <script>
        (function () {
            var halamanSaatIni = 0;
            var grid = document.getElementById('lainnyaGrid');
            if (!grid) return;

            var halamanEls = grid.querySelectorAll('.lainnya-page');
            var prevBtn = document.getElementById('lainnyaPrev');
            var nextBtn = document.getElementById('lainnyaNext');

            window.gantiHalamanLainnya = function (delta) {
                var halamanBaru = halamanSaatIni + delta;
                if (halamanBaru < 0 || halamanBaru >= halamanEls.length) return;

                halamanEls[halamanSaatIni].style.display = 'none';
                halamanSaatIni = halamanBaru;
                halamanEls[halamanSaatIni].style.display = 'grid';

                if (prevBtn) prevBtn.disabled = halamanSaatIni === 0;
                if (nextBtn) nextBtn.disabled = halamanSaatIni === halamanEls.length - 1;
            };
        })();
    </script>


</body>
</html>