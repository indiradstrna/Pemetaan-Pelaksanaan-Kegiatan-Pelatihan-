<?php
/**
 * /api/crud_kegiatan_revisi.php
 *
 * Alur "Ajukan Revisi": kegiatan yang sudah status_verifikasi='approved'
 * (tayang di peta publik) TIDAK bisa diedit langsung oleh role 'internal'
 * (lihat blokir di api/crud_kegiatan.php action=update). Internal harus
 * mengajukan revisi lewat sini; perubahan baru diterapkan ke tabel `kegiatan`
 * setelah disetujui Admin/Superadmin di halaman Verifikasi.
 *
 * Cuma FIELD YANG BERUBAH yang disimpan & ditampilkan ke verifikator (mode
 * diff/before-after), disimpan sebagai JSON di kolom kegiatan_revisi.data_perubahan:
 *   { "fields": { "<nama_field>": {"label":"...", "lama":..., "baru":...}, ... },
 *     "foto_tambah": [{"path": "...", "caption": "..."}, ...],
 *     "foto_hapus_ids": [12, 13],
 *     "foto_caption_ubah": { "id_foto": "caption baru", ... } }
 * Foto (tambah maupun hapus) IKUT masuk alur approval -- foto baru disimpan
 * sementara di uploads/kegiatan_revisi/ dan baru dipindah ke uploads/kegiatan/
 * (+insert ke kegiatan_foto) begitu revisi disetujui; foto yang diajukan utk
 * dihapus juga baru benar-benar dihapus saat itu. Perubahan caption foto yang
 * SUDAH ADA (foto lama) juga baru diterapkan ke kegiatan_foto.caption begitu
 * revisi disetujui -- bukan langsung, biar konsisten sama field lain.
 *
 * GET  ?action=list&status_verifikasi=pending|approved|rejected  -> daftar pengajuan revisi
 * GET  ?action=detail&id=X                                       -> detail 1 pengajuan (diff lengkap)
 * POST action=ajukan       -> Internal mengajukan revisi (field sama seperti form edit kegiatan
 *                             + kegiatan_id, foto_baru[], foto_caption[], hapus_foto_ids[],
 *                             caption_lama[id])
 * POST action=verifikasi   -> Admin/Superadmin approve/reject (id, keputusan, catatan)
 * POST action=batal        -> Internal (pengaju) atau Superadmin membatalkan pengajuan yang masih pending
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff_check.php';
require_once __DIR__ . '/../includes/notifikasi_helper.php';
require_once __DIR__ . '/../includes/kegiatan_helper.php';

// Label field yang ikut dibandingkan (lama vs baru). Field lain di tabel
// kegiatan (kode_kegiatan, status, status_verifikasi, foto_utama, dst)
// sengaja gak masuk sini -- itu bukan sesuatu yang diajukan lewat form.
const LABEL_FIELD_REVISI = [
    'judul'           => 'Judul',
    'location_id'     => 'Lokasi',
    'tanggal_mulai'   => 'Tanggal Mulai',
    'tanggal_selesai' => 'Tanggal Selesai',
    'penyelenggara'   => 'Penyelenggara',
    'skala_kegiatan'  => 'Skala Kegiatan',
    'deskripsi'       => 'Deskripsi',
    'rencana_output'  => 'Rencana Output',
    'deskripsi_hasil' => 'Deskripsi Hasil',
    'jumlah_peserta'  => 'Jumlah Peserta',
    'tags'            => 'Klasifikasi / Tag',
    'departemen'      => 'Departemen',
];

/** Label lokasi buat ditampilkan di panel diff ("Nama Lokasi, Kab/Kota"). */
function labelLokasi(PDO $pdo, int $locationId): string
{
    if ($locationId <= 0) return '-';
    $stmt = $pdo->prepare('SELECT nama_lokasi, kabupaten_kota FROM lokasi WHERE id = :id');
    $stmt->execute(['id' => $locationId]);
    $l = $stmt->fetch();
    return $l ? "{$l['nama_lokasi']}, {$l['kabupaten_kota']}" : "Lokasi #{$locationId} (sudah dihapus)";
}

/** Ubah array id tag jadi array nama tag, buat ditampilkan di panel diff. */
function namaTagDariId(PDO $pdo, array $tagIds): array
{
    $tagIds = array_map('intval', $tagIds);
    if (empty($tagIds)) return [];
    $in = implode(',', array_fill(0, count($tagIds), '?'));
    $stmt = $pdo->prepare("SELECT nama_tag FROM tags WHERE id IN ($in) ORDER BY nama_tag ASC");
    $stmt->execute($tagIds);
    return array_column($stmt->fetchAll(), 'nama_tag');
}

/** Bandingkan data kegiatan lama vs data form baru, hasilnya cuma field yang BEDA. */
function hitungDiffKegiatan(array $lama, array $baru): array
{
    $fields = [];
    foreach (LABEL_FIELD_REVISI as $key => $label) {
        $nilaiLama = $lama[$key] ?? null;
        $nilaiBaru = $baru[$key] ?? null;

        if (in_array($key, ['tags', 'departemen'], true)) {
            $l = $nilaiLama ?? [];
            $b = $nilaiBaru ?? [];
            sort($l);
            sort($b);
            if ($l == $b) continue;
            $nilaiLama = $l;
            $nilaiBaru = $b;
        } else {
            if ((string) $nilaiLama === (string) $nilaiBaru) continue;
        }

        $fields[$key] = ['label' => $label, 'lama' => $nilaiLama, 'baru' => $nilaiBaru];
    }
    return $fields;
}

/** Hapus file-file foto pending (staged) revisi yang gak jadi dipakai (revisi ditolak/dibatalkan).
 *  $pathList boleh array path string biasa, ATAU array item ['path'=>..., 'caption'=>...]. */
function hapusFotoRevisiFisik(array $pathList): void
{
    foreach ($pathList as $item) {
        $path = is_array($item) ? ($item['path'] ?? '') : $item;
        if ($path === '') continue;
        $fullPath = __DIR__ . '/../' . $path;
        if (is_file($fullPath)) unlink($fullPath);
    }
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $filterStatus = $_GET['status_verifikasi'] ?? null;
            $statusValid  = ['pending', 'approved', 'rejected'];

            $where  = '';
            $params = [];
            if (in_array($filterStatus, $statusValid, true)) {
                $where = 'WHERE r.status_verifikasi = :status_verifikasi';
                $params['status_verifikasi'] = $filterStatus;
            }

            // Role internal cuma boleh lihat pengajuan revisinya sendiri;
            // admin/superadmin boleh lihat semua (buat direview).
            if ($_SESSION['role'] === 'internal') {
                $where .= ($where === '' ? 'WHERE ' : ' AND ') . 'r.diajukan_oleh = :diajukan_oleh';
                $params['diajukan_oleh'] = (int) $_SESSION['user_id'];
            }

            $stmt = $pdo->prepare(
                "SELECT r.id, r.kegiatan_id, r.status_verifikasi, r.diajukan_oleh, r.diverifikasi_oleh,
                        r.catatan_verifikasi, r.verified_at, r.created_at, r.data_perubahan,
                        k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
                        l.nama_lokasi, l.kabupaten_kota,
                        u.nama_lengkap AS diajukan_oleh_nama
                 FROM kegiatan_revisi r
                 JOIN kegiatan k ON k.id = r.kegiatan_id
                 JOIN lokasi l ON l.id = k.location_id
                 LEFT JOIN users u ON u.id = r.diajukan_oleh
                 {$where}
                 ORDER BY r.created_at DESC"
            );
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            foreach ($data as &$row) {
                $decoded = json_decode($row['data_perubahan'], true) ?: [];
                $row['jumlah_field_berubah'] = count($decoded['fields'] ?? []);
                $row['jumlah_foto_tambah']   = count($decoded['foto_tambah'] ?? []);
                $row['jumlah_foto_hapus']    = count($decoded['foto_hapus_ids'] ?? []);
                unset($row['data_perubahan']); // gak perlu dikirim mentah-mentah di list, cukup ringkasannya
            }
            unset($row);

            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        if ($action === 'detail') {
            $id = (int) ($_GET['id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT r.*, k.kode_kegiatan, k.judul AS judul_kegiatan,
                        l.nama_lokasi, l.kabupaten_kota,
                        u.nama_lengkap AS diajukan_oleh_nama
                 FROM kegiatan_revisi r
                 JOIN kegiatan k ON k.id = r.kegiatan_id
                 JOIN lokasi l ON l.id = k.location_id
                 LEFT JOIN users u ON u.id = r.diajukan_oleh
                 WHERE r.id = :id"
            );
            $stmt->execute(['id' => $id]);
            $revisi = $stmt->fetch();

            if (!$revisi) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Pengajuan revisi tidak ditemukan.']);
                exit;
            }

            if ($_SESSION['role'] === 'internal' && (int) $revisi['diajukan_oleh'] !== (int) $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bukan pengajuan revisi milik Anda.']);
                exit;
            }

            $decoded = json_decode($revisi['data_perubahan'], true) ?: [];

            // Field 'location_id' disimpan sbg id mentah -- resolve ke label buat ditampilkan.
            $fields = $decoded['fields'] ?? [];
            if (isset($fields['location_id'])) {
                $fields['location_id']['lama'] = labelLokasi($pdo, (int) $fields['location_id']['lama']);
                $fields['location_id']['baru'] = labelLokasi($pdo, (int) $fields['location_id']['baru']);
            }
            // Field tags/departemen disimpan sbg array id/kode -- resolve ke nama biar kebaca.
            if (isset($fields['tags'])) {
                $fields['tags']['lama'] = namaTagDariId($pdo, $fields['tags']['lama']);
                $fields['tags']['baru'] = namaTagDariId($pdo, $fields['tags']['baru']);
            }
            if (isset($fields['departemen'])) {
                $fields['departemen']['lama'] = implode(', ', $fields['departemen']['lama']) ?: '-';
                $fields['departemen']['baru'] = implode(', ', $fields['departemen']['baru']) ?: '-';
            }

            $revisi['fields'] = $fields;
            $revisi['foto_tambah'] = $decoded['foto_tambah'] ?? [];

            $fotoHapusIds = $decoded['foto_hapus_ids'] ?? [];
            $revisi['foto_hapus'] = [];
            if (!empty($fotoHapusIds)) {
                $in = implode(',', array_fill(0, count($fotoHapusIds), '?'));
                $fotoStmt = $pdo->prepare("SELECT id, path_foto FROM kegiatan_foto WHERE id IN ($in)");
                $fotoStmt->execute($fotoHapusIds);
                $revisi['foto_hapus'] = $fotoStmt->fetchAll();
            }

            // Caption foto lama yang diajukan berubah -- resolve path & caption
            // sekarang (before) buat ditampilkan sebagai diff ke verifikator.
            $fotoCaptionUbah = $decoded['foto_caption_ubah'] ?? [];
            $revisi['foto_caption_ubah'] = [];
            if (!empty($fotoCaptionUbah)) {
                $idFoto = array_map('intval', array_keys($fotoCaptionUbah));
                $in = implode(',', array_fill(0, count($idFoto), '?'));
                $fotoStmt = $pdo->prepare("SELECT id, path_foto, caption FROM kegiatan_foto WHERE id IN ($in)");
                $fotoStmt->execute($idFoto);
                foreach ($fotoStmt->fetchAll() as $f) {
                    $revisi['foto_caption_ubah'][] = [
                        'id'            => (int) $f['id'],
                        'path_foto'     => $f['path_foto'],
                        'caption_lama'  => $f['caption'],
                        'caption_baru'  => $fotoCaptionUbah[$f['id']] ?? null,
                    ];
                }
            }

            unset($revisi['data_perubahan']);

            echo json_encode(['success' => true, 'data' => $revisi]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
        exit;
    }

    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'ajukan') {
            if ($_SESSION['role'] !== 'internal') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Cuma role Internal yang mengajukan revisi (Superadmin bisa edit langsung).']);
                exit;
            }

            $kegiatanId = (int) ($_POST['kegiatan_id'] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT k.*, l.nama_lokasi, l.kabupaten_kota
                 FROM kegiatan k JOIN lokasi l ON l.id = k.location_id
                 WHERE k.id = :id"
            );
            $stmt->execute(['id' => $kegiatanId]);
            $kegiatanLama = $stmt->fetch();

            if (!$kegiatanLama) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
                exit;
            }

            if ($kegiatanLama['status_verifikasi'] !== 'approved') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Kegiatan ini belum approved -- masih bisa diedit langsung lewat Kelola Kegiatan.']);
                exit;
            }

            $cekPending = $pdo->prepare(
                "SELECT id FROM kegiatan_revisi WHERE kegiatan_id = :id AND status_verifikasi = 'pending'"
            );
            $cekPending->execute(['id' => $kegiatanId]);
            if ($cekPending->fetch()) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Sudah ada pengajuan revisi yang menunggu persetujuan untuk kegiatan ini.']);
                exit;
            }

            $error = validasiKegiatanInput($_POST);
            if ($error) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }

            $tagIds  = array_map('intval', $_POST['tag_ids'] ?? []);
            $tagBaru = array_filter(array_map('trim', $_POST['tag_baru'] ?? []));
            $tagIdFinal = $tagIds;
            foreach ($tagBaru as $nama) {
                $tid = resolveTagId($pdo, $nama);
                if ($tid) $tagIdFinal[] = $tid;
            }
            $tagIdFinal = array_values(array_unique(array_map('intval', $tagIdFinal)));

            $departemenTerpilih = array_values(array_unique(array_intersect(
                array_map('strtoupper', array_map('trim', $_POST['departemen'] ?? [])),
                DEPARTEMEN_VALID
            )));

            $tagLamaStmt = $pdo->prepare('SELECT tag_id FROM kegiatan_tags WHERE kegiatan_id = :id');
            $tagLamaStmt->execute(['id' => $kegiatanId]);
            $tagLamaIds = array_map('intval', array_column($tagLamaStmt->fetchAll(), 'tag_id'));

            $deptLamaStmt = $pdo->prepare('SELECT departemen_kode FROM kegiatan_departemen WHERE kegiatan_id = :id');
            $deptLamaStmt->execute(['id' => $kegiatanId]);
            $deptLamaKode = array_column($deptLamaStmt->fetchAll(), 'departemen_kode');

            $dataBaru = [
                'judul'           => trim($_POST['judul']),
                'location_id'     => (int) $_POST['location_id'],
                'tanggal_mulai'   => $_POST['tanggal_mulai'],
                'tanggal_selesai' => $_POST['tanggal_selesai'],
                'penyelenggara'   => trim($_POST['penyelenggara'] ?? '') ?: null,
                'skala_kegiatan'  => $_POST['skala_kegiatan'],
                'deskripsi'       => trim($_POST['deskripsi'] ?? '') ?: null,
                'rencana_output'  => trim($_POST['rencana_output'] ?? '') ?: null,
                'deskripsi_hasil' => trim($_POST['deskripsi_hasil'] ?? '') ?: null,
                'jumlah_peserta'  => ($_POST['jumlah_peserta'] ?? '') !== '' ? (int) $_POST['jumlah_peserta'] : null,
                'tags'            => $tagIdFinal,
                'departemen'      => $departemenTerpilih,
            ];
            $dataLama = [
                'judul'           => $kegiatanLama['judul'],
                'location_id'     => (int) $kegiatanLama['location_id'],
                'tanggal_mulai'   => $kegiatanLama['tanggal_mulai'],
                'tanggal_selesai' => $kegiatanLama['tanggal_selesai'],
                'penyelenggara'   => $kegiatanLama['penyelenggara'],
                'skala_kegiatan'  => $kegiatanLama['skala_kegiatan'],
                'deskripsi'       => $kegiatanLama['deskripsi'],
                'rencana_output'  => $kegiatanLama['rencana_output'],
                'deskripsi_hasil' => $kegiatanLama['deskripsi_hasil'],
                'jumlah_peserta'  => $kegiatanLama['jumlah_peserta'],
                'tags'            => $tagLamaIds,
                'departemen'      => $deptLamaKode,
            ];

            $fieldsBerubah = hitungDiffKegiatan($dataLama, $dataBaru);

            // Foto: hitung batas MAX_FOTO (existing - dihapus + baru)
            $hapusFotoIds = array_map('intval', $_POST['hapus_foto_ids'] ?? []);
            if (!empty($hapusFotoIds)) {
                $in = implode(',', array_fill(0, count($hapusFotoIds), '?'));
                $validasiFoto = $pdo->prepare("SELECT id FROM kegiatan_foto WHERE kegiatan_id = ? AND id IN ($in)");
                $validasiFoto->execute(array_merge([$kegiatanId], $hapusFotoIds));
                $hapusFotoIds = array_map('intval', array_column($validasiFoto->fetchAll(), 'id'));
            }

            $jumlahFotoBaru = isset($_FILES['foto_baru']) ? count(array_filter($_FILES['foto_baru']['name'])) : 0;

            $cekFotoStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM kegiatan_foto WHERE kegiatan_id = :id');
            $cekFotoStmt->execute(['id' => $kegiatanId]);
            $totalFotoSekarang = (int) $cekFotoStmt->fetch()['total'];
            $totalSetelah = $totalFotoSekarang - count($hapusFotoIds) + $jumlahFotoBaru;

            if ($totalSetelah > MAX_FOTO) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Maksimal ' . MAX_FOTO . ' foto per kegiatan. Hapus beberapa foto lama dulu di pengajuan ini.']);
                exit;
            }

            // Caption foto LAMA yang diubah -- cuma yang beneran beda dari caption
            // sekarang yang disimpan sebagai proposal (foto_caption_ubah), foto yang
            // sekalian ditandai dihapus gak usah ikut diproses captionnya.
            $fotoCaptionUbah = [];
            $captionLamaKirim = $_POST['caption_lama'] ?? [];
            if (!empty($captionLamaKirim)) {
                $fotoLamaStmt = $pdo->prepare('SELECT id, caption FROM kegiatan_foto WHERE kegiatan_id = :id');
                $fotoLamaStmt->execute(['id' => $kegiatanId]);
                $captionSekarangById = array_column($fotoLamaStmt->fetchAll(), 'caption', 'id');

                foreach ($captionLamaKirim as $fotoId => $captionBaruMentah) {
                    $fotoId = (int) $fotoId;
                    if (!array_key_exists($fotoId, $captionSekarangById) || in_array($fotoId, $hapusFotoIds, true)) continue;

                    $captionBaruMentah = trim((string) $captionBaruMentah);
                    $captionBaru = $captionBaruMentah !== '' ? mb_substr($captionBaruMentah, 0, 255) : null;

                    if ($captionBaru !== $captionSekarangById[$fotoId]) {
                        $fotoCaptionUbah[$fotoId] = $captionBaru;
                    }
                }
            }

            $fotoTambah = [];
            if ($jumlahFotoBaru > 0) {
                $hasil = uploadFotoRevisi($kegiatanId, $_FILES['foto_baru'], $_POST['foto_caption'] ?? []);
                $fotoTambah = $hasil['foto'];
                if ($hasil['errors']) {
                    hapusFotoRevisiFisik($fotoTambah);
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Ada masalah foto: ' . implode(' ', $hasil['errors'])]);
                    exit;
                }
            }

            if (empty($fieldsBerubah) && empty($fotoTambah) && empty($hapusFotoIds) && empty($fotoCaptionUbah)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan yang diajukan. Ubah minimal 1 data sebelum mengajukan revisi.']);
                exit;
            }

            $dataPerubahan = json_encode([
                'fields'            => $fieldsBerubah,
                'foto_tambah'       => $fotoTambah,
                'foto_hapus_ids'    => $hapusFotoIds,
                'foto_caption_ubah' => $fotoCaptionUbah,
            ], JSON_UNESCAPED_UNICODE);

            $insert = $pdo->prepare(
                "INSERT INTO kegiatan_revisi (kegiatan_id, data_perubahan, status_verifikasi, diajukan_oleh, created_at, updated_at)
                 VALUES (:kegiatan_id, :data_perubahan, 'pending', :diajukan_oleh, NOW(), NOW())"
            );
            $insert->execute([
                'kegiatan_id'    => $kegiatanId,
                'data_perubahan' => $dataPerubahan,
                'diajukan_oleh'  => (int) $_SESSION['user_id'],
            ]);
            $revisiId = (int) $pdo->lastInsertId();

            notifikasiRevisiBaru($pdo, $kegiatanId, $kegiatanLama['judul']);

            echo json_encode([
                'success' => true,
                'message' => 'Revisi berhasil diajukan -- menunggu persetujuan Admin/Superadmin.',
                'id'      => $revisiId,
            ]);
            exit;
        }

        if ($action === 'verifikasi') {
            if (!in_array($_SESSION['role'], ['admin', 'superadmin'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Cuma Admin (verifikator) & Superadmin yang bisa memutuskan pengajuan revisi.']);
                exit;
            }

            $id        = (int) ($_POST['id'] ?? 0);
            $keputusan = $_POST['keputusan'] ?? '';
            $catatan   = trim($_POST['catatan'] ?? '') ?: null;

            if (!in_array($keputusan, ['approve', 'reject'], true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Keputusan tidak valid.']);
                exit;
            }
            if ($keputusan === 'reject' && !$catatan) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Catatan wajib diisi kalau menolak pengajuan revisi.']);
                exit;
            }

            $stmt = $pdo->prepare('SELECT * FROM kegiatan_revisi WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $revisi = $stmt->fetch();

            if (!$revisi) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Pengajuan revisi tidak ditemukan.']);
                exit;
            }
            if ($revisi['status_verifikasi'] !== 'pending') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Pengajuan ini sudah diputuskan sebelumnya.']);
                exit;
            }

            $kegiatanId = (int) $revisi['kegiatan_id'];
            $decoded    = json_decode($revisi['data_perubahan'], true) ?: [];
            $fields         = $decoded['fields'] ?? [];
            $fotoTambah     = $decoded['foto_tambah'] ?? [];
            $fotoHapusIds   = $decoded['foto_hapus_ids'] ?? [];
            $fotoCaptionUbah = $decoded['foto_caption_ubah'] ?? [];

            $judulStmt = $pdo->prepare('SELECT judul FROM kegiatan WHERE id = :id');
            $judulStmt->execute(['id' => $kegiatanId]);
            $judulKegiatan = $judulStmt->fetch()['judul'] ?? '(kegiatan tidak ditemukan)';

            if ($keputusan === 'approve') {
                // Terapkan field skalar ke tabel kegiatan (kecuali tags & departemen, ditangani terpisah)
                $setClauses = [];
                $params     = ['id' => $kegiatanId];
                foreach ($fields as $key => $val) {
                    if (in_array($key, ['tags', 'departemen'], true)) continue;
                    $setClauses[] = "{$key} = :{$key}";
                    $params[$key] = $val['baru'];
                }
                if (!empty($setClauses)) {
                    $pdo->prepare('UPDATE kegiatan SET ' . implode(', ', $setClauses) . ' WHERE id = :id')->execute($params);
                }
                if (isset($fields['tags'])) {
                    simpanTagsKegiatan($pdo, $kegiatanId, $fields['tags']['baru'], []);
                }
                if (isset($fields['departemen'])) {
                    simpanDepartemenKegiatan($pdo, $kegiatanId, $fields['departemen']['baru']);
                }

                // Terapkan foto yang mau dihapus
                if (!empty($fotoHapusIds)) {
                    $in = implode(',', array_fill(0, count($fotoHapusIds), '?'));
                    $selectFoto = $pdo->prepare("SELECT id, path_foto FROM kegiatan_foto WHERE kegiatan_id = ? AND id IN ($in)");
                    $selectFoto->execute(array_merge([$kegiatanId], $fotoHapusIds));
                    foreach ($selectFoto->fetchAll() as $f) {
                        $fullPath = __DIR__ . '/../' . $f['path_foto'];
                        if (is_file($fullPath)) unlink($fullPath);
                    }
                    $pdo->prepare("DELETE FROM kegiatan_foto WHERE kegiatan_id = ? AND id IN ($in)")
                        ->execute(array_merge([$kegiatanId], $fotoHapusIds));
                    $pdo->prepare(
                        'UPDATE kegiatan SET foto_utama = NULL WHERE id = :id
                         AND foto_utama NOT IN (SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :id2)'
                    )->execute(['id' => $kegiatanId, 'id2' => $kegiatanId]);
                }

                // Terapkan caption foto lama yang diajukan berubah
                simpanCaptionFotoLama($pdo, $kegiatanId, $fotoCaptionUbah);

                // Pindahkan foto yang diajukan dari folder staging ke folder live
                if (!empty($fotoTambah)) {
                    $urutanStmt = $pdo->prepare('SELECT COALESCE(MAX(urutan), -1) + 1 AS urutan FROM kegiatan_foto WHERE kegiatan_id = :id');
                    $urutanStmt->execute(['id' => $kegiatanId]);
                    $urutan = (int) $urutanStmt->fetch()['urutan'];

                    $insertFoto = $pdo->prepare('INSERT INTO kegiatan_foto (kegiatan_id, path_foto, caption, urutan) VALUES (:kid, :path, :caption, :urutan)');
                    if (!is_dir(UPLOAD_PATH_KEGIATAN)) mkdir(UPLOAD_PATH_KEGIATAN, 0755, true);

                    foreach ($fotoTambah as $item) {
                        $pathStaging = is_array($item) ? ($item['path'] ?? '') : $item;
                        $caption     = is_array($item) ? ($item['caption'] ?? null) : null;
                        if ($pathStaging === '') continue;

                        $namaFile   = basename($pathStaging);
                        $sumberFull = __DIR__ . '/../' . $pathStaging;
                        $tujuanRel  = 'uploads/kegiatan/' . $namaFile;
                        $tujuanFull = __DIR__ . '/../' . $tujuanRel;

                        if (is_file($sumberFull) && rename($sumberFull, $tujuanFull)) {
                            $insertFoto->execute(['kid' => $kegiatanId, 'path' => $tujuanRel, 'caption' => $caption, 'urutan' => $urutan]);
                            $urutan++;
                        }
                    }
                }

                updateFotoUtamaOtomatis($pdo, $kegiatanId);
            } else {
                // Ditolak -- kegiatan LIVE gak disentuh sama sekali, cuma buang file staging.
                hapusFotoRevisiFisik($fotoTambah);
            }

            $pdo->prepare(
                "UPDATE kegiatan_revisi
                 SET status_verifikasi = :status, diverifikasi_oleh = :diverifikasi_oleh,
                     catatan_verifikasi = :catatan, verified_at = NOW()
                 WHERE id = :id"
            )->execute([
                'status'            => $keputusan === 'approve' ? 'approved' : 'rejected',
                'diverifikasi_oleh' => (int) $_SESSION['user_id'],
                'catatan'           => $catatan,
                'id'                => $id,
            ]);

            // Sinkronkan notifikasi -- begitu pengajuan revisi diputuskan, notifikasi
            // 'revisi_baru' soal pengajuan ini ke SEMUA verifikator ditandai selesai.
            tandaiNotifikasiSelesai($pdo, $kegiatanId, 'revisi_baru');

            notifikasiKeputusanRevisi($pdo, $kegiatanId, $judulKegiatan, (int) $revisi['diajukan_oleh'], $keputusan, $catatan);

            $pesan = $keputusan === 'approve'
                ? 'Revisi disetujui & perubahan sudah diterapkan ke kegiatan.'
                : 'Revisi ditolak. Kegiatan yang tayang tidak berubah.';
            echo json_encode(['success' => true, 'message' => $pesan]);
            exit;
        }

        if ($action === 'batal') {
            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $pdo->prepare('SELECT * FROM kegiatan_revisi WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $revisi = $stmt->fetch();

            if (!$revisi) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Pengajuan revisi tidak ditemukan.']);
                exit;
            }
            if ($revisi['status_verifikasi'] !== 'pending') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Pengajuan ini sudah diputuskan, gak bisa dibatalkan lagi.']);
                exit;
            }

            $bolehBatal = $_SESSION['role'] === 'superadmin'
                || (int) $revisi['diajukan_oleh'] === (int) $_SESSION['user_id'];
            if (!$bolehBatal) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bukan pengajuan revisi milik Anda.']);
                exit;
            }

            $decoded = json_decode($revisi['data_perubahan'], true) ?: [];
            hapusFotoRevisiFisik($decoded['foto_tambah'] ?? []);

            $pdo->prepare('DELETE FROM kegiatan_revisi WHERE id = :id')->execute(['id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Pengajuan revisi dibatalkan.']);
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
    error_log('[crud_kegiatan_revisi] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
}