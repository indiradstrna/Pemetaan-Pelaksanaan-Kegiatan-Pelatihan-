<?php
/**
 * /api/crud_kegiatan.php
 *
 * GET  ?action=list                 -> semua kegiatan (+ nama lokasi, nama pembuat, jml foto).
 *                                       Tambah &status_verifikasi=pending|approved|rejected
 *                                       buat filter (dipakai halaman Verifikasi).
 * GET  ?action=detail&id=X          -> detail kegiatan (+ tags & foto terkait)
 * GET  ?action=tags                 -> semua tag yang pernah dipakai (untuk chip picker)
 * POST action=create                -> tambah kegiatan baru (kode di-generate otomatis).
 *                                       Kalau pembuatnya role 'internal' -> status_verifikasi
 *                                       jadi 'pending' (belum tampil di peta publik, nunggu
 *                                       Admin/Superadmin approve). Kalau pembuatnya 'superadmin'
 *                                       -> langsung 'approved'.
 * POST action=update                -> update kegiatan (butuh field id). Kegiatan yang
 *                                       status_verifikasi-nya masih 'pending'/'rejected' boleh
 *                                       diedit langsung (bagian dari revisi & submit ulang).
 *                                       Kegiatan yang sudah 'approved' TIDAK bisa diedit lewat
 *                                       sini oleh role 'internal' (403) -- nunggu fitur pengajuan
 *                                       revisi terpisah; 'superadmin' masih boleh edit langsung.
 * POST action=selesaikan            -> tandai kegiatan sebagai 'selesai' (butuh id +
 *                                       deskripsi_hasil; hapus_foto_ids[]/foto_baru[]
 *                                       opsional, dipakai dari popup "Tandai Selesai")
 * POST action=verifikasi             -> approve/reject kegiatan berstatus 'pending'.
 *                                       KHUSUS role admin (verifikator) & superadmin.
 *                                       Butuh: id, keputusan ('approve'|'reject'),
 *                                       catatan (wajib kalau reject, opsional kalau approve).
 * POST action=delete                -> hapus kegiatan (+ file foto fisik ikut dihapus)
 *
 * Field yang dikirim dari form (create/update):
 *   judul, location_id, tanggal_mulai, tanggal_selesai,
 *   penyelenggara, skala_kegiatan, deskripsi, jumlah_peserta,
 *   (status TIDAK dikirim dari form -- create/update cuma bisa menghasilkan
 *    'belum_berlangsung'/'berlangsung' otomatis dari tanggal, lihat hitungStatusKegiatan().
 *    Status 'selesai' HANYA bisa diset lewat action=selesaikan, dan begitu sudah
 *    'selesai', update berikutnya tidak akan menurunkan status itu lagi.)
 *   tag_ids[]      -> id tag yang sudah ada & dipilih
 *   tag_baru[]     -> nama tag baru yang belum ada di tabel tags
 *   departemen[]   -> kode departemen terpilih (SITD/HCID/HRAD/FAD/FMD/KMD, multi-select, opsi tetap)
 *   hapus_foto_ids[] -> id kegiatan_foto yang mau dihapus (mode update)
 *   foto_baru[]    -> file foto baru yang diupload (multipart)
 *   foto_caption[] -> caption per foto_baru[], opsional, index-nya harus selaras
 *                     sama urutan file di foto_baru[]
 *   caption_lama[id] -> caption buat foto lama yang sudah ada, dikirim sebagai
 *                     array asosiatif keyed by id kegiatan_foto (mode update/selesaikan)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff_check.php';
require_once __DIR__ . '/../includes/notifikasi_helper.php';
require_once __DIR__ . '/../includes/kegiatan_helper.php';

// Role 'admin' (verifikator) boleh baca (list/detail/tags/departemen) buat
// keperluan meninjau kegiatan sebelum diputuskan, dan action=verifikasi buat
// approve/reject -- tapi TIDAK boleh create/update/selesaikan/delete (itu
// wilayahnya internal/superadmin, verifikator gak ikut bikin/edit kegiatan).
$actionDiminta   = $_GET['action'] ?? $_POST['action'] ?? 'list';
$actionMengubah  = ['create', 'update', 'selesaikan', 'delete'];
if ($_SESSION['role'] === 'admin' && in_array($actionDiminta, $actionMengubah, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Role Admin (verifikator) cuma bisa meninjau & memverifikasi, bukan mengelola kegiatan.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            // Filter opsional ?status_verifikasi=pending -- dipakai halaman
            // Verifikasi (admin/verifikasi.php) buat ambil antrian pending doang.
            // Kalau parameter ini gak dikirim, perilakunya sama seperti sebelumnya
            // (semua kegiatan, gak difilter).
            $filterStatusVerifikasi = $_GET['status_verifikasi'] ?? null;
            $statusVerifikasiValid  = ['pending', 'approved', 'rejected'];

            $where = '';
            $params = [];
            if (in_array($filterStatusVerifikasi, $statusVerifikasiValid, true)) {
                $where = 'WHERE k.status_verifikasi = :status_verifikasi';
                $params['status_verifikasi'] = $filterStatusVerifikasi;
            }

            // Kolom `status` di-override oleh CASE di bawah supaya tetap akurat kalau
            // masih 'belum_berlangsung'/'berlangsung' (mengikuti tanggal hari ini), TAPI
            // begitu status tersimpan sudah 'selesai' (ditandai manual lewat action=selesaikan),
            // itu yang menang -- tidak pernah diturunkan lagi otomatis.
            $stmt = $pdo->prepare(
                "SELECT k.*,
                        CASE
                            WHEN k.status = 'selesai' THEN 'selesai'
                            WHEN CURDATE() < k.tanggal_mulai THEN 'belum_berlangsung'
                            ELSE 'berlangsung'
                        END AS status,
                        l.nama_lokasi, l.kabupaten_kota,
                        u.nama_lengkap AS dibuat_oleh_nama,
                        (SELECT COUNT(*) FROM kegiatan_foto f WHERE f.kegiatan_id = k.id) AS jumlah_foto
                 FROM kegiatan k
                 JOIN lokasi l ON l.id = k.location_id
                 LEFT JOIN users u ON u.id = k.dibuat_oleh
                 {$where}
                 ORDER BY k.tanggal_mulai DESC"
            );
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit;
        }

        if ($action === 'tags') {
            $stmt = $pdo->query('SELECT id, nama_tag FROM tags ORDER BY nama_tag ASC');
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit;
        }

        if ($action === 'departemen') {
            // Opsi tetap, gak perlu query DB -- lihat konstanta DEPARTEMEN_VALID di atas.
            echo json_encode(['success' => true, 'data' => DEPARTEMEN_VALID]);
            exit;
        }

        if ($action === 'detail') {
            $id = (int) ($_GET['id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT k.*,
                        CASE
                            WHEN k.status = 'selesai' THEN 'selesai'
                            WHEN CURDATE() < k.tanggal_mulai THEN 'belum_berlangsung'
                            ELSE 'berlangsung'
                        END AS status,
                        l.nama_lokasi, l.kabupaten_kota,
                        u.nama_lengkap AS dibuat_oleh_nama,
                        v.nama_lengkap AS diverifikasi_oleh_nama
                 FROM kegiatan k
                 JOIN lokasi l ON l.id = k.location_id
                 LEFT JOIN users u ON u.id = k.dibuat_oleh
                 LEFT JOIN users v ON v.id = k.diverifikasi_oleh
                 WHERE k.id = :id"
            );
            $stmt->execute(['id' => $id]);
            $kegiatan = $stmt->fetch();

            if (!$kegiatan) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
                exit;
            }

            $tagStmt = $pdo->prepare(
                'SELECT t.id, t.nama_tag FROM tags t
                 JOIN kegiatan_tags kt ON kt.tag_id = t.id
                 WHERE kt.kegiatan_id = :id'
            );
            $tagStmt->execute(['id' => $id]);
            $kegiatan['tags'] = $tagStmt->fetchAll();

            $deptStmt = $pdo->prepare(
                'SELECT departemen_kode FROM kegiatan_departemen WHERE kegiatan_id = :id'
            );
            $deptStmt->execute(['id' => $id]);
            $kegiatan['departemen'] = array_column($deptStmt->fetchAll(), 'departemen_kode');

            $fotoStmt = $pdo->prepare(
                'SELECT id, path_foto, caption, urutan FROM kegiatan_foto WHERE kegiatan_id = :id ORDER BY urutan ASC'
            );
            $fotoStmt->execute(['id' => $id]);
            $kegiatan['foto'] = $fotoStmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $kegiatan]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
        exit;
    }

    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $error = validasiKegiatanInput($_POST);
            if ($error) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }

            $tagIds  = array_map('intval', $_POST['tag_ids'] ?? []);
            $tagBaru = array_filter(array_map('trim', $_POST['tag_baru'] ?? []));
            $departemenTerpilih = $_POST['departemen'] ?? [];

            $data = [
                'judul'           => trim($_POST['judul']),
                'status'          => hitungStatusKegiatan($_POST['tanggal_mulai'], $_POST['tanggal_selesai']),
                'location_id'     => (int) $_POST['location_id'],
                'tanggal_mulai'   => $_POST['tanggal_mulai'],
                'tanggal_selesai' => $_POST['tanggal_selesai'],
                'penyelenggara'   => trim($_POST['penyelenggara'] ?? '') ?: null,
                'skala_kegiatan'  => $_POST['skala_kegiatan'],
                'deskripsi'       => trim($_POST['deskripsi'] ?? '') ?: null,
                'rencana_output'  => trim($_POST['rencana_output'] ?? '') ?: null,
                'deskripsi_hasil' => trim($_POST['deskripsi_hasil'] ?? '') ?: null,
                'jumlah_peserta'  => ($_POST['jumlah_peserta'] ?? '') !== '' ? (int) $_POST['jumlah_peserta'] : null,
            ];

            // Hitung jumlah foto yang bakal ada setelah operasi ini (existing - dihapus + baru)
            $jumlahFotoBaru = isset($_FILES['foto_baru']) ? count(array_filter($_FILES['foto_baru']['name'])) : 0;
            $fotoCaptions   = $_POST['foto_caption'] ?? [];

            if ($action === 'create') {
                if ($jumlahFotoBaru > MAX_FOTO) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Maksimal ' . MAX_FOTO . ' foto per kegiatan.']);
                    exit;
                }

                // Alur verifikasi: kegiatan baru dari Internal masuk antrian ('pending'),
                // baru tampil di peta publik setelah di-approve Admin/Superadmin.
                // Superadmin sendiri sekaligus verifikator -> kegiatan buatannya langsung
                // 'approved' (gak masuk akal minta approval ke diri sendiri).
                $userId = (int) $_SESSION['user_id'];
                if ($_SESSION['role'] === 'superadmin') {
                    $data['status_verifikasi']   = 'approved';
                    $data['dibuat_oleh']         = $userId;
                    $data['diverifikasi_oleh']   = $userId;
                    $data['verified_at']         = date('Y-m-d H:i:s');
                } else {
                    $data['status_verifikasi']   = 'pending';
                    $data['dibuat_oleh']         = $userId;
                    $data['diverifikasi_oleh']   = null;
                    $data['verified_at']         = null;
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO kegiatan
                        (kode_kegiatan, judul, status, status_verifikasi, location_id, tanggal_mulai, tanggal_selesai,
                         penyelenggara, skala_kegiatan, deskripsi, rencana_output, deskripsi_hasil, jumlah_peserta,
                         dibuat_oleh, diverifikasi_oleh, verified_at)
                     VALUES
                        (:kode_kegiatan, :judul, :status, :status_verifikasi, :location_id, :tanggal_mulai, :tanggal_selesai,
                         :penyelenggara, :skala_kegiatan, :deskripsi, :rencana_output, :deskripsi_hasil, :jumlah_peserta,
                         :dibuat_oleh, :diverifikasi_oleh, :verified_at)'
                );

                // generateKodeKegiatan() bisa tabrakan kalau ada 2 kegiatan dibuat nyaris
                // bersamaan (race condition: keduanya baca MAX yang sama sebelum salah satu
                // commit). Kalau kejadian, coba lagi generate kode baru & insert ulang.
                $percobaanMaksimal = 5;
                $kegiatanId = null;

                for ($percobaan = 1; $percobaan <= $percobaanMaksimal; $percobaan++) {
                    $data['kode_kegiatan'] = generateKodeKegiatan($pdo);
                    try {
                        $stmt->execute($data);
                        $kegiatanId = (int) $pdo->lastInsertId();
                        break;
                    } catch (PDOException $e) {
                        $kodeBentrok = $e->getCode() === '23000' && strpos($e->getMessage(), 'kode_kegiatan') !== false;
                        if (!$kodeBentrok || $percobaan === $percobaanMaksimal) {
                            throw $e; // bukan soal kode bentrok, atau udah kehabisan percobaan
                        }
                        // kode bentrok -- ulangi loop, generate kode baru
                    }
                }

                simpanTagsKegiatan($pdo, $kegiatanId, $tagIds, $tagBaru);
                simpanDepartemenKegiatan($pdo, $kegiatanId, $departemenTerpilih);

                if ($data['status_verifikasi'] === 'pending') {
                    notifikasiKegiatanBaru($pdo, $kegiatanId, $data['judul']);
                }

                // Pesan balik ke form beda tergantung apakah langsung tayang atau masih
                // menunggu verifikasi -- dipakai UI buat nentuin teks toast yang muncul.
                $pesanSukses = $data['status_verifikasi'] === 'approved'
                    ? "Kegiatan berhasil ditambahkan ({$data['kode_kegiatan']})."
                    : "Kegiatan berhasil diajukan ({$data['kode_kegiatan']}) -- menunggu verifikasi Admin sebelum tampil di peta publik.";

                if ($jumlahFotoBaru > 0) {
                    $fotoError = uploadFotoBaru($pdo, $kegiatanId, $_FILES['foto_baru'], 0, $fotoCaptions);
                    if ($fotoError) {
                        echo json_encode(['success' => true, 'fotoWarning' => true, 'message' => "Kegiatan tersimpan, tapi ada masalah foto: {$fotoError}", 'id' => $kegiatanId, 'statusVerifikasi' => $data['status_verifikasi']]);
                        exit;
                    }
                    updateFotoUtamaOtomatis($pdo, $kegiatanId);
                }

                echo json_encode(['success' => true, 'message' => $pesanSukses, 'id' => $kegiatanId, 'statusVerifikasi' => $data['status_verifikasi']]);
                exit;
            }

            // action === 'update'
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID kegiatan tidak valid.']);
                exit;
            }

            // Status 'selesai' cuma boleh diset lewat tombol "Selesai" (action=selesaikan).
            // Kalau kegiatan ini sudah 'selesai', form update ini TIDAK BOLEH menurunkannya
            // lagi jadi 'berlangsung' walau tanggalnya ikut diedit.
            $cekStatusLama = $pdo->prepare('SELECT status, status_verifikasi FROM kegiatan WHERE id = :id');
            $cekStatusLama->execute(['id' => $id]);
            $statusLama = $cekStatusLama->fetch();

            if (!$statusLama) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
                exit;
            }

            if ($statusLama['status'] === 'selesai') {
                $data['status'] = 'selesai';
            }

            // Kegiatan yang sudah 'approved' (tayang di peta publik) TIDAK boleh diedit
            // langsung lewat sini oleh Internal -- harus lewat alur pengajuan revisi
            // (kegiatan_revisi, lihat api/crud_kegiatan_revisi.php) supaya data yang
            // sudah publik gak berubah diam-diam sebelum diverifikasi ulang. Superadmin
            // masih boleh edit langsung karena dia sendiri juga verifikatornya.
            if ($statusLama['status_verifikasi'] === 'approved' && $_SESSION['role'] !== 'superadmin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Kegiatan ini sudah disetujui dan tayang di peta publik. Gunakan tombol "Ajukan Revisi" untuk mengubah datanya.',
                ]);
                exit;
            }

            $hapusFotoIds = array_map('intval', $_POST['hapus_foto_ids'] ?? []);

            $cekFoto = $pdo->prepare('SELECT COUNT(*) AS total FROM kegiatan_foto WHERE kegiatan_id = :id');
            $cekFoto->execute(['id' => $id]);
            $totalFotoSekarang = (int) $cekFoto->fetch()['total'];
            $totalSetelah = $totalFotoSekarang - count($hapusFotoIds) + $jumlahFotoBaru;

            if ($totalSetelah > MAX_FOTO) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Maksimal ' . MAX_FOTO . ' foto per kegiatan. Hapus beberapa foto lama dulu.']);
                exit;
            }

            // Kegiatan 'rejected' yang direvisi & disubmit ulang otomatis balik jadi
            // 'pending' -- supaya muncul lagi di antrian verifikasi Admin. Catatan
            // penolakan sebelumnya SENGAJA tidak dihapus di sini (baru ketimpa pas
            // action=verifikasi diputuskan lagi), jadi masih bisa jadi jejak/rujukan.
            $statusVerifikasiBaru = $statusLama['status_verifikasi'] === 'rejected'
                ? 'pending'
                : $statusLama['status_verifikasi'];

            $data['id'] = $id;
            $data['status_verifikasi_baru'] = $statusVerifikasiBaru;
            $stmt = $pdo->prepare(
                'UPDATE kegiatan SET
                    judul = :judul,
                    status = :status,
                    status_verifikasi = :status_verifikasi_baru,
                    location_id = :location_id,
                    tanggal_mulai = :tanggal_mulai,
                    tanggal_selesai = :tanggal_selesai,
                    penyelenggara = :penyelenggara,
                    skala_kegiatan = :skala_kegiatan,
                    deskripsi = :deskripsi,
                    rencana_output = :rencana_output,
                    deskripsi_hasil = :deskripsi_hasil,
                    jumlah_peserta = :jumlah_peserta
                 WHERE id = :id'
            );
            $stmt->execute($data);

            simpanTagsKegiatan($pdo, $id, $tagIds, $tagBaru);
            simpanDepartemenKegiatan($pdo, $id, $departemenTerpilih);

            // Hapus foto yang ditandai dihapus (DB + file fisik)
            if (!empty($hapusFotoIds)) {
                $in = implode(',', array_fill(0, count($hapusFotoIds), '?'));
                $selectFoto = $pdo->prepare("SELECT id, path_foto FROM kegiatan_foto WHERE kegiatan_id = ? AND id IN ($in)");
                $selectFoto->execute(array_merge([$id], $hapusFotoIds));
                $fotoDihapus = $selectFoto->fetchAll();

                foreach ($fotoDihapus as $f) {
                    $fullPath = __DIR__ . '/../' . $f['path_foto'];
                    if (is_file($fullPath)) unlink($fullPath);
                }

                $deleteFoto = $pdo->prepare("DELETE FROM kegiatan_foto WHERE kegiatan_id = ? AND id IN ($in)");
                $deleteFoto->execute(array_merge([$id], $hapusFotoIds));

                // Kalau foto_utama yang lagi kepake ikut dihapus, kosongin biar di-reset
                $pdo->prepare(
                    'UPDATE kegiatan SET foto_utama = NULL WHERE id = :id
                     AND foto_utama NOT IN (SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :id2)'
                )->execute(['id' => $id, 'id2' => $id]);
            }

            simpanCaptionFotoLama($pdo, $id, $_POST['caption_lama'] ?? []);

            if ($jumlahFotoBaru > 0) {
                $urutanMulai = $totalFotoSekarang - count($hapusFotoIds);
                $fotoError = uploadFotoBaru($pdo, $id, $_FILES['foto_baru'], max(0, $urutanMulai), $fotoCaptions);
                if ($fotoError) {
                    echo json_encode(['success' => true, 'fotoWarning' => true, 'message' => "Kegiatan diperbarui, tapi ada masalah foto: {$fotoError}"]);
                    exit;
                }
            }

            updateFotoUtamaOtomatis($pdo, $id);

            if ($statusLama['status_verifikasi'] === 'rejected' && $statusVerifikasiBaru === 'pending') {
                notifikasiKegiatanBaru($pdo, $id, $data['judul'], true);
            }

            echo json_encode(['success' => true, 'message' => 'Kegiatan berhasil diperbarui.']);
            exit;
        }

        if ($action === 'verifikasi') {
            // Cuma admin (verifikator) & superadmin yang boleh sampai sini -- sudah
            // dijamin sama pengecekan role di atas ($actionDiminta), tapi dicek lagi
            // di sini biar action ini tetap aman kalau dipanggil dari alur lain nanti.
            if (!in_array($_SESSION['role'], ['admin', 'superadmin'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Tidak punya akses untuk memverifikasi.']);
                exit;
            }

            $id        = (int) ($_POST['id'] ?? 0);
            $keputusan = $_POST['keputusan'] ?? ''; // 'approve' | 'reject'
            $catatan   = trim($_POST['catatan'] ?? '');

            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID kegiatan tidak valid.']);
                exit;
            }

            if (!in_array($keputusan, ['approve', 'reject'], true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Keputusan harus approve atau reject.']);
                exit;
            }

            $cek = $pdo->prepare('SELECT judul, status_verifikasi, dibuat_oleh FROM kegiatan WHERE id = :id');
            $cek->execute(['id' => $id]);
            $kegiatan = $cek->fetch();

            if (!$kegiatan) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
                exit;
            }

            if ($kegiatan['status_verifikasi'] !== 'pending') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Kegiatan ini sudah diverifikasi sebelumnya (bukan pending lagi).']);
                exit;
            }

            // Reject wajib disertai catatan -- ini yang dibaca Internal buat tau
            // apa yang perlu direvisi sebelum submit ulang. Approve, catatan opsional.
            if ($keputusan === 'reject' && $catatan === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Catatan penolakan wajib diisi supaya Internal tahu apa yang perlu direvisi.']);
                exit;
            }

            $statusBaru = $keputusan === 'approve' ? 'approved' : 'rejected';

            $update = $pdo->prepare(
                'UPDATE kegiatan
                 SET status_verifikasi = :status_verifikasi,
                     diverifikasi_oleh = :diverifikasi_oleh,
                     catatan_verifikasi = :catatan_verifikasi,
                     verified_at = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                'status_verifikasi'  => $statusBaru,
                'diverifikasi_oleh'  => (int) $_SESSION['user_id'],
                'catatan_verifikasi' => $catatan !== '' ? $catatan : null,
                'id'                 => $id,
            ]);

            // Sinkronkan notifikasi -- begitu diverifikasi (approve/reject), notifikasi
            // 'kegiatan_baru' soal kegiatan ini ke SEMUA verifikator ditandai selesai,
            // biar gak nyangkut terus di badge padahal udah diurus (lewat entry point manapun).
            tandaiNotifikasiSelesai($pdo, $id, 'kegiatan_baru');

            notifikasiKeputusanVerifikasi(
                $pdo,
                $id,
                $kegiatan['judul'],
                $kegiatan['dibuat_oleh'] !== null ? (int) $kegiatan['dibuat_oleh'] : null,
                $keputusan,
                $catatan !== '' ? $catatan : null
            );

            $pesan = $keputusan === 'approve'
                ? 'Kegiatan disetujui & sekarang tayang di peta publik.'
                : 'Kegiatan ditolak. Internal akan diminta merevisi & mengajukan ulang.';

            echo json_encode(['success' => true, 'message' => $pesan]);
            exit;
        }

        if ($action === 'selesaikan') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID kegiatan tidak valid.']);
                exit;
            }

            $cek = $pdo->prepare('SELECT status FROM kegiatan WHERE id = :id');
            $cek->execute(['id' => $id]);
            $kegiatan = $cek->fetch();

            if (!$kegiatan) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
                exit;
            }

            if ($kegiatan['status'] === 'selesai') {
                echo json_encode(['success' => true, 'message' => 'Kegiatan ini sudah berstatus Selesai.']);
                exit;
            }

            $deskripsiHasil = trim($_POST['deskripsi_hasil'] ?? '');
            if ($deskripsiHasil === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Deskripsi Hasil wajib diisi.']);
                exit;
            }

            // Dokumentasi foto di popup ini opsional -- pola hapus/tambahnya
            // sama persis dengan yang dipakai di action=update.
            $hapusFotoIds   = array_map('intval', $_POST['hapus_foto_ids'] ?? []);
            $jumlahFotoBaru = isset($_FILES['foto_baru']) ? count(array_filter($_FILES['foto_baru']['name'])) : 0;

            $cekFoto = $pdo->prepare('SELECT COUNT(*) AS total FROM kegiatan_foto WHERE kegiatan_id = :id');
            $cekFoto->execute(['id' => $id]);
            $totalFotoSekarang = (int) $cekFoto->fetch()['total'];
            $totalSetelah = $totalFotoSekarang - count($hapusFotoIds) + $jumlahFotoBaru;

            if ($totalSetelah > MAX_FOTO) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Maksimal ' . MAX_FOTO . ' foto per kegiatan. Hapus beberapa foto lama dulu.']);
                exit;
            }

            $update = $pdo->prepare("UPDATE kegiatan SET status = 'selesai', deskripsi_hasil = :deskripsi_hasil WHERE id = :id");
            $update->execute(['deskripsi_hasil' => $deskripsiHasil, 'id' => $id]);

            if (!empty($hapusFotoIds)) {
                $in = implode(',', array_fill(0, count($hapusFotoIds), '?'));
                $selectFoto = $pdo->prepare("SELECT id, path_foto FROM kegiatan_foto WHERE kegiatan_id = ? AND id IN ($in)");
                $selectFoto->execute(array_merge([$id], $hapusFotoIds));
                $fotoDihapus = $selectFoto->fetchAll();

                foreach ($fotoDihapus as $f) {
                    $fullPath = __DIR__ . '/../' . $f['path_foto'];
                    if (is_file($fullPath)) unlink($fullPath);
                }

                $deleteFoto = $pdo->prepare("DELETE FROM kegiatan_foto WHERE kegiatan_id = ? AND id IN ($in)");
                $deleteFoto->execute(array_merge([$id], $hapusFotoIds));

                $pdo->prepare(
                    'UPDATE kegiatan SET foto_utama = NULL WHERE id = :id
                     AND foto_utama NOT IN (SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :id2)'
                )->execute(['id' => $id, 'id2' => $id]);
            }

            simpanCaptionFotoLama($pdo, $id, $_POST['caption_lama'] ?? []);

            if ($jumlahFotoBaru > 0) {
                $urutanMulai = $totalFotoSekarang - count($hapusFotoIds);
                $fotoError = uploadFotoBaru($pdo, $id, $_FILES['foto_baru'], max(0, $urutanMulai), $_POST['foto_caption'] ?? []);
                if ($fotoError) {
                    echo json_encode(['success' => true, 'fotoWarning' => true, 'message' => "Kegiatan ditandai Selesai, tapi ada masalah foto: {$fotoError}"]);
                    exit;
                }
            }

            updateFotoUtamaOtomatis($pdo, $id);

            echo json_encode(['success' => true, 'message' => 'Kegiatan berhasil ditandai Selesai.']);
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ID kegiatan tidak valid.']);
                exit;
            }

            // Hapus file fisik foto dulu sebelum row DB-nya kehapus (cascade)
            $fotoStmt = $pdo->prepare('SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :id');
            $fotoStmt->execute(['id' => $id]);
            foreach ($fotoStmt->fetchAll() as $f) {
                $fullPath = __DIR__ . '/../' . $f['path_foto'];
                if (is_file($fullPath)) unlink($fullPath);
            }

            $stmt = $pdo->prepare('DELETE FROM kegiatan WHERE id = :id');
            $stmt->execute(['id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Kegiatan berhasil dihapus.']);
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
    error_log('[crud_kegiatan] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
}