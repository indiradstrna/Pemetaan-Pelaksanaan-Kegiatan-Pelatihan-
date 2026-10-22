<?php
/**
 * GET /api/get_kegiatan_publik.php
 *
 * Endpoint PUBLIK (tanpa login) khusus untuk halaman peta publik (user/index.php).
 * Read-only -- tidak ada create/update/delete di sini, itu tetap lewat
 * api/crud_kegiatan.php yang wajib login admin.
 *
 * PENTING: SEMUA action di file ini cuma nampilin kegiatan yang
 * status_verifikasi-nya 'approved' -- kegiatan 'pending'/'rejected' gak
 * pernah bocor ke Guest maupun User lewat endpoint ini (termasuk statistik
 * dashboard & daftar tag/departemen/tahun di filter).
 *
 * Actions:
 *   ?action=list            -> daftar kegiatan buat sidebar (support q, tag, tahun, lokasi_id, page, per_page)
 *                              tag/departemen/status support multi-value dipisah koma (OR), mis. tag=IoT,Pertanian
 *   ?action=cari_tag        -> cari tag on-demand (buat combobox filter Tag, gak narik semua tag sekaligus)
 *   ?action=markers         -> titik lokasi + rekap status buat marker peta
 *   ?action=filter_options  -> daftar departemen & tahun yang tersedia (buat dropdown filter)
 *   ?action=dashboard       -> rekap statistik buat panel Dashboard
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

const PER_PAGE_DEFAULT = 10;

// Sama kayak DEPARTEMEN_VALID di api/crud_kegiatan.php -- opsi tetap, gak ada tabel lookup terpisah.
const DEPARTEMEN_VALID = ['SITD', 'HCID', 'HRAD', 'FAD', 'FMD', 'KMD'];

/** Hitung status kegiatan real-time: status 'selesai' tersimpan (ditandai manual
 *  lewat admin) selalu menang; sisanya (belum_berlangsung/berlangsung) tetap
 *  mengikuti tanggal, konsisten dgn crud_kegiatan.php. */
const STATUS_CASE_SQL = "
    CASE
        WHEN k.status = 'selesai' THEN 'selesai'
        WHEN CURDATE() < k.tanggal_mulai THEN 'belum_berlangsung'
        ELSE 'berlangsung'
    END
";

$action = $_GET['action'] ?? 'list';

try {
    // =================================================================
    // ACTION: list -- kartu kegiatan di sidebar (search + filter + paginasi)
    // =================================================================
    if ($action === 'list') {
        $q          = trim($_GET['q'] ?? '');
        $tag        = trim($_GET['tag'] ?? '');
        $tahun      = trim($_GET['tahun'] ?? '');
        $bulan      = trim($_GET['bulan'] ?? ''); // format YYYY-MM, dari deep-link dashboard admin
        $status     = trim($_GET['status'] ?? ''); // belum_berlangsung|berlangsung|selesai, dari deep-link dashboard admin
        $departemen = trim($_GET['departemen'] ?? '');
        $lokasiId   = (int) ($_GET['lokasi_id'] ?? 0);
        $scope      = $_GET['scope'] ?? 'nasional';
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = max(1, min(50, (int) ($_GET['per_page'] ?? PER_PAGE_DEFAULT)));
        $offset     = ($page - 1) * $perPage;

        $where  = ["k.status_verifikasi = 'approved'"];
        $params = [];

        // Feedback mentor: split Nasional/Internasional biar query gak langsung narik semua data.
        if ($scope === 'nasional') {
            $where[] = "l.negara = 'Indonesia'";
        } elseif ($scope === 'internasional') {
            $where[] = "l.negara != 'Indonesia'";
        }
        // $scope === 'semua' -> tanpa filter negara

        if ($q !== '') {
            $where[] = '(k.judul LIKE :q1 OR l.kabupaten_kota LIKE :q2 OR l.nama_lokasi LIKE :q3)';
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
        }

        if ($tag !== '') {
            $tagList = array_values(array_filter(array_map('trim', explode(',', $tag))));
            if (!empty($tagList)) {
                $ph = [];
                foreach ($tagList as $i => $t) {
                    $key = "tag{$i}";
                    $ph[] = ":{$key}";
                    $params[$key] = $t;
                }
                $where[] = 'k.id IN (
                    SELECT kt.kegiatan_id FROM kegiatan_tags kt
                    JOIN tags t ON t.id = kt.tag_id
                    WHERE t.nama_tag IN (' . implode(',', $ph) . ')
                )';
            }
        }

        if ($departemen !== '') {
            $deptList = array_values(array_filter(array_map('trim', explode(',', $departemen)), function ($d) {
                return in_array($d, DEPARTEMEN_VALID, true);
            }));
            if (!empty($deptList)) {
                $ph = [];
                foreach ($deptList as $i => $d) {
                    $key = "dept{$i}";
                    $ph[] = ":{$key}";
                    $params[$key] = $d;
                }
                $where[] = 'k.id IN (
                    SELECT kegiatan_id FROM kegiatan_departemen
                    WHERE departemen_kode IN (' . implode(',', $ph) . ')
                )';
            }
        }

        if ($tahun !== '' && ctype_digit($tahun)) {
            $where[] = 'YEAR(k.tanggal_mulai) = :tahun';
            $params['tahun'] = (int) $tahun;
        }

        if ($bulan !== '' && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $where[] = "DATE_FORMAT(k.tanggal_mulai, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }

        if ($status !== '') {
            $statusList = array_values(array_filter(array_map('trim', explode(',', $status)), function ($s) {
                return in_array($s, ['belum_berlangsung', 'berlangsung', 'selesai'], true);
            }));
            if (!empty($statusList)) {
                $ph = [];
                foreach ($statusList as $i => $s) {
                    $key = "status{$i}";
                    $ph[] = ":{$key}";
                    $params[$key] = $s;
                }
                $where[] = '(' . STATUS_CASE_SQL . ') IN (' . implode(',', $ph) . ')';
            }
        }

        if ($lokasiId > 0) {
            $where[] = 'k.location_id = :lokasi_id';
            $params['lokasi_id'] = $lokasiId;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total data (buat paginasi & "Ditemukan X data")
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) AS total
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $stmt = $pdo->prepare(
            "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
                    k.jumlah_peserta, k.penyelenggara, k.foto_utama, k.location_id,
                    " . STATUS_CASE_SQL . " AS status,
                    l.nama_lokasi, l.kabupaten_kota, l.provinsi, l.latitude, l.longitude,
                    (
                        SELECT GROUP_CONCAT(t.nama_tag ORDER BY t.nama_tag SEPARATOR '||')
                        FROM kegiatan_tags kt
                        JOIN tags t ON t.id = kt.tag_id
                        WHERE kt.kegiatan_id = k.id
                    ) AS tags_concat
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             {$whereSql}
             ORDER BY k.tanggal_mulai DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $data = array_map(function ($row) {
            $row['tags'] = $row['tags_concat'] ? explode('||', $row['tags_concat']) : [];
            unset($row['tags_concat']);
            $row['foto_url'] = $row['foto_utama'] ? BASE_URL . '/' . $row['foto_utama'] : null;
            return $row;
        }, $rows);

        echo json_encode([
            'success'     => true,
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
        exit;
    }

    // =================================================================
    // ACTION: detail -- data lengkap 1 kegiatan buat halaman Detail Kegiatan
    // =================================================================
    if ($action === 'detail') {
        $id = (int) ($_GET['id'] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
                    k.jumlah_peserta, k.penyelenggara, k.skala_kegiatan, k.deskripsi,
                    k.rencana_output, k.deskripsi_hasil, k.foto_utama, k.location_id,
                    " . STATUS_CASE_SQL . " AS status,
                    l.nama_lokasi, l.nama_jalan, l.kelurahan, l.kecamatan,
                    l.kabupaten_kota, l.provinsi, l.latitude, l.longitude
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             WHERE k.id = :id AND k.status_verifikasi = 'approved'"
        );
        $stmt->execute(['id' => $id]);
        $kegiatan = $stmt->fetch();

        if (!$kegiatan) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
            exit;
        }

        $tagStmt = $pdo->prepare(
            'SELECT nama_tag FROM tags t
             JOIN kegiatan_tags kt ON kt.tag_id = t.id
             WHERE kt.kegiatan_id = :id
             ORDER BY nama_tag ASC'
        );
        $tagStmt->execute(['id' => $id]);
        $kegiatan['tags'] = array_column($tagStmt->fetchAll(), 'nama_tag');

        $deptStmt = $pdo->prepare(
            'SELECT departemen_kode FROM kegiatan_departemen
             WHERE kegiatan_id = :id ORDER BY departemen_kode ASC'
        );
        $deptStmt->execute(['id' => $id]);
        $kegiatan['departemen'] = array_column($deptStmt->fetchAll(), 'departemen_kode');

        $fotoStmt = $pdo->prepare(
            'SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :id ORDER BY urutan ASC LIMIT 5'
        );
        $fotoStmt->execute(['id' => $id]);
        $kegiatan['foto'] = array_map(
            fn($f) => BASE_URL . '/' . $f['path_foto'],
            $fotoStmt->fetchAll()
        );
        $kegiatan['foto_utama_url'] = $kegiatan['foto_utama'] ? BASE_URL . '/' . $kegiatan['foto_utama'] : null;

        // Kegiatan Lainnya -- kegiatan lain yang berbagi minimal 1 tag yang sama.
        // Bentuk row-nya disamain kayak action=list, biar bisa dipakai ulang oleh
        // fungsi renderCard() yang sama di sisi frontend.
        $lainnyaStmt = $pdo->prepare(
            "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
                    k.jumlah_peserta, k.penyelenggara, k.foto_utama, k.location_id,
                    " . STATUS_CASE_SQL . " AS status,
                    l.nama_lokasi, l.kabupaten_kota, l.provinsi, l.latitude, l.longitude,
                    (
                        SELECT GROUP_CONCAT(t2.nama_tag ORDER BY t2.nama_tag SEPARATOR '||')
                        FROM kegiatan_tags kt2
                        JOIN tags t2 ON t2.id = kt2.tag_id
                        WHERE kt2.kegiatan_id = k.id
                    ) AS tags_concat
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             WHERE k.id != :id
               AND k.status_verifikasi = 'approved'
               AND EXISTS (
                   SELECT 1 FROM kegiatan_tags kt_a
                   JOIN kegiatan_tags kt_b ON kt_b.tag_id = kt_a.tag_id
                   WHERE kt_a.kegiatan_id = :id2 AND kt_b.kegiatan_id = k.id
               )
             ORDER BY k.tanggal_mulai DESC
             LIMIT 4"
        );
        $lainnyaStmt->execute(['id' => $id, 'id2' => $id]);
        $lainnyaRows = $lainnyaStmt->fetchAll();

        // Fallback: kalau gak ada yang share tag yang sama, tampilkan kegiatan
        // terbaru secara umum aja (biar 'kegiatan_lainnya' gak pernah kosong).
        if (empty($lainnyaRows)) {
            $fallbackStmt = $pdo->prepare(
                "SELECT k.id, k.kode_kegiatan, k.judul, k.tanggal_mulai, k.tanggal_selesai,
                        k.jumlah_peserta, k.penyelenggara, k.foto_utama, k.location_id,
                        " . STATUS_CASE_SQL . " AS status,
                        l.nama_lokasi, l.kabupaten_kota, l.provinsi, l.latitude, l.longitude,
                        (
                            SELECT GROUP_CONCAT(t2.nama_tag ORDER BY t2.nama_tag SEPARATOR '||')
                            FROM kegiatan_tags kt2
                            JOIN tags t2 ON t2.id = kt2.tag_id
                            WHERE kt2.kegiatan_id = k.id
                        ) AS tags_concat
                 FROM kegiatan k
                 JOIN lokasi l ON l.id = k.location_id
                 WHERE k.id != :id AND k.status_verifikasi = 'approved'
                 ORDER BY k.tanggal_mulai DESC
                 LIMIT 4"
            );
            $fallbackStmt->execute(['id' => $id]);
            $lainnyaRows = $fallbackStmt->fetchAll();
        }

        $kegiatanLainnya = array_map(function ($row) {
            $row['tags'] = $row['tags_concat'] ? explode('||', $row['tags_concat']) : [];
            unset($row['tags_concat']);
            $row['foto_url'] = $row['foto_utama'] ? BASE_URL . '/' . $row['foto_utama'] : null;
            return $row;
        }, $lainnyaRows);

        echo json_encode(['success' => true, 'data' => $kegiatan, 'kegiatan_lainnya' => $kegiatanLainnya]);
        exit;
    }

    // =================================================================
    // ACTION: markers -- titik lokasi buat peta (dikelompokkan per lokasi)
    // =================================================================
    if ($action === 'markers') {
        $scope      = $_GET['scope'] ?? 'nasional';
        $q          = trim($_GET['q'] ?? '');
        $tag        = trim($_GET['tag'] ?? '');
        $tahun      = trim($_GET['tahun'] ?? '');
        $bulan      = trim($_GET['bulan'] ?? '');
        $status     = trim($_GET['status'] ?? '');
        $departemen = trim($_GET['departemen'] ?? '');

        // Where + params dipakai bareng buat query rekap lokasi & query kegiatan per lokasi,
        // supaya kedua query konsisten dengan filter yang sama (sama kayak action=list).
        $where  = ["k.status_verifikasi = 'approved'"];
        $params = [];

        if ($scope === 'nasional') {
            $where[] = "l.negara = 'Indonesia'";
        } elseif ($scope === 'internasional') {
            $where[] = "l.negara != 'Indonesia'";
        }
        // $scope === 'semua' -> tanpa filter negara

        if ($q !== '') {
            $where[] = '(k.judul LIKE :q1 OR l.kabupaten_kota LIKE :q2 OR l.nama_lokasi LIKE :q3)';
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
        }

        if ($tag !== '') {
            $tagList = array_values(array_filter(array_map('trim', explode(',', $tag))));
            if (!empty($tagList)) {
                $ph = [];
                foreach ($tagList as $i => $t) {
                    $key = "tag{$i}";
                    $ph[] = ":{$key}";
                    $params[$key] = $t;
                }
                $where[] = 'k.id IN (
                    SELECT kt.kegiatan_id FROM kegiatan_tags kt
                    JOIN tags t ON t.id = kt.tag_id
                    WHERE t.nama_tag IN (' . implode(',', $ph) . ')
                )';
            }
        }

        if ($departemen !== '') {
            $deptList = array_values(array_filter(array_map('trim', explode(',', $departemen)), function ($d) {
                return in_array($d, DEPARTEMEN_VALID, true);
            }));
            if (!empty($deptList)) {
                $ph = [];
                foreach ($deptList as $i => $d) {
                    $key = "dept{$i}";
                    $ph[] = ":{$key}";
                    $params[$key] = $d;
                }
                $where[] = 'k.id IN (
                    SELECT kegiatan_id FROM kegiatan_departemen
                    WHERE departemen_kode IN (' . implode(',', $ph) . ')
                )';
            }
        }

        if ($tahun !== '' && ctype_digit($tahun)) {
            $where[] = 'YEAR(k.tanggal_mulai) = :tahun';
            $params['tahun'] = (int) $tahun;
        }

        if ($bulan !== '' && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $where[] = "DATE_FORMAT(k.tanggal_mulai, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan;
        }

        if ($status !== '') {
            $statusList = array_values(array_filter(array_map('trim', explode(',', $status)), function ($s) {
                return in_array($s, ['belum_berlangsung', 'berlangsung', 'selesai'], true);
            }));
            if (!empty($statusList)) {
                $ph = [];
                foreach ($statusList as $i => $s) {
                    $key = "status{$i}";
                    $ph[] = ":{$key}";
                    $params[$key] = $s;
                }
                $where[] = '(' . STATUS_CASE_SQL . ') IN (' . implode(',', $ph) . ')';
            }
        }

        $scopeWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Rekap jumlah kegiatan per lokasi (buat warna & angka marker) -- sekarang ikut filter aktif.
        $stmt = $pdo->prepare(
            "SELECT l.id AS location_id, l.nama_lokasi, l.nama_jalan,
                    l.kabupaten_kota, l.provinsi, l.latitude, l.longitude,
                    COUNT(k.id) AS total_kegiatan,
                    SUM(CASE WHEN " . STATUS_CASE_SQL . " = 'selesai' THEN 1 ELSE 0 END) AS selesai_count,
                    SUM(CASE WHEN " . STATUS_CASE_SQL . " = 'berlangsung' THEN 1 ELSE 0 END) AS berlangsung_count,
                    SUM(CASE WHEN " . STATUS_CASE_SQL . " = 'belum_berlangsung' THEN 1 ELSE 0 END) AS akan_datang_count
             FROM lokasi l
             JOIN kegiatan k ON k.location_id = l.id
             {$scopeWhere}
             GROUP BY l.id
             HAVING total_kegiatan > 0"
        );
        $stmt->execute($params);
        $lokasiRows = $stmt->fetchAll();

        // Ambil semua kegiatan yang match scope+filter, diurutkan terbaru dulu per lokasi.
        // Dikumpulin di PHP (bukan N+1 query per lokasi) buat susun foto slideshow & 3 kegiatan terbaru.
        $kegStmt = $pdo->prepare(
            "SELECT k.id, k.location_id, k.judul, k.tanggal_mulai, k.foto_utama,
                    " . STATUS_CASE_SQL . " AS status
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             {$scopeWhere}
             ORDER BY k.location_id ASC, k.tanggal_mulai DESC"
        );
        $kegStmt->execute($params);
        $kegiatanPerLokasi = [];
        foreach ($kegStmt->fetchAll() as $keg) {
            $kegiatanPerLokasi[$keg['location_id']][] = $keg;
        }

        $data = array_map(function ($loc) use ($kegiatanPerLokasi) {
            $daftarKegiatan = $kegiatanPerLokasi[$loc['location_id']] ?? [];

            // Slideshow: foto_utama yang gak kosong, maksimal 5, dari yang terbaru.
            $fotos = [];
            foreach ($daftarKegiatan as $keg) {
                if ($keg['foto_utama']) {
                    $fotos[] = BASE_URL . '/' . $keg['foto_utama'];
                    if (count($fotos) >= 5) {
                        break;
                    }
                }
            }

            // Kegiatan terbaru: maksimal 3.
            $terbaru = array_slice(array_map(function ($keg) {
                return [
                    'id'            => $keg['id'],
                    'judul'         => $keg['judul'],
                    'status'        => $keg['status'],
                    'tanggal_mulai' => $keg['tanggal_mulai'],
                ];
            }, $daftarKegiatan), 0, 3);

            $loc['fotos']            = $fotos;
            $loc['kegiatan_terbaru'] = $terbaru;
            return $loc;
        }, $lokasiRows);

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // =================================================================
    // ACTION: cari_tag -- cari tag on-demand buat combobox filter Tag
    // (sengaja gak ada "ambil semua tag sekaligus" -- daftar tag bakal terus
    // bertambah, jadi query selalu dibatasi LIMIT + sesuai kata kunci yang diketik)
    // =================================================================
    if ($action === 'cari_tag') {
        $q = trim($_GET['q'] ?? '');

        if ($q !== '') {
            $stmt = $pdo->prepare(
                "SELECT t.nama_tag, COUNT(kt.kegiatan_id) AS jumlah
                 FROM tags t
                 JOIN kegiatan_tags kt ON kt.tag_id = t.id
                 JOIN kegiatan k ON k.id = kt.kegiatan_id AND k.status_verifikasi = 'approved'
                 WHERE t.nama_tag LIKE :q
                 GROUP BY t.id
                 ORDER BY jumlah DESC, t.nama_tag ASC
                 LIMIT 20"
            );
            $stmt->execute(['q' => "%{$q}%"]);
        } else {
            // Belum ngetik apa-apa -> tampilkan tag paling sering dipakai sebagai saran awal.
            $stmt = $pdo->query(
                "SELECT t.nama_tag, COUNT(kt.kegiatan_id) AS jumlah
                 FROM tags t
                 JOIN kegiatan_tags kt ON kt.tag_id = t.id
                 JOIN kegiatan k ON k.id = kt.kegiatan_id AND k.status_verifikasi = 'approved'
                 GROUP BY t.id
                 ORDER BY jumlah DESC, t.nama_tag ASC
                 LIMIT 20"
            );
        }

        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        exit;
    }

    // =================================================================
    // ACTION: filter_options -- daftar departemen & tahun yang tersedia
    // (tag SENGAJA gak diikutkan di sini -- lihat action=cari_tag)
    // =================================================================
    if ($action === 'filter_options') {
        $tahunStmt = $pdo->query(
            "SELECT DISTINCT YEAR(tanggal_mulai) AS tahun
             FROM kegiatan
             WHERE status_verifikasi = 'approved'
             ORDER BY tahun DESC"
        );
        $tahun = array_map(fn($r) => (int) $r['tahun'], $tahunStmt->fetchAll());

        $deptStmt = $pdo->query(
            "SELECT kd.departemen_kode, COUNT(kd.kegiatan_id) AS jumlah
             FROM kegiatan_departemen kd
             JOIN kegiatan k ON k.id = kd.kegiatan_id AND k.status_verifikasi = 'approved'
             GROUP BY kd.departemen_kode
             ORDER BY kd.departemen_kode ASC"
        );
        $departemen = $deptStmt->fetchAll();

        echo json_encode(['success' => true, 'tahun' => $tahun, 'departemen' => $departemen]);
        exit;
    }

    // =================================================================
    // ACTION: dashboard -- rekap statistik buat panel Dashboard
    // =================================================================
    if ($action === 'dashboard') {
        // Jumlah kegiatan per provinsi/kota (pakai kabupaten_kota, lebih granular)
        $perLokasiStmt = $pdo->query(
            "SELECT l.kabupaten_kota, COUNT(k.id) AS total
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             WHERE k.status_verifikasi = 'approved'
             GROUP BY l.kabupaten_kota
             ORDER BY total DESC
             LIMIT 10"
        );
        $perLokasi = $perLokasiStmt->fetchAll();

        // Tren jumlah kegiatan per bulan (12 bulan terakhir dari kegiatan yang ada)
        $perBulanStmt = $pdo->query(
            "SELECT DATE_FORMAT(tanggal_mulai, '%Y-%m') AS bulan, COUNT(*) AS total
             FROM kegiatan
             WHERE status_verifikasi = 'approved'
             GROUP BY bulan
             ORDER BY bulan ASC"
        );
        $perBulan = $perBulanStmt->fetchAll();

        // Distribusi kegiatan per tag/kategori
        $perTagStmt = $pdo->query(
            "SELECT t.nama_tag, COUNT(kt.kegiatan_id) AS total
             FROM tags t
             JOIN kegiatan_tags kt ON kt.tag_id = t.id
             JOIN kegiatan k ON k.id = kt.kegiatan_id AND k.status_verifikasi = 'approved'
             GROUP BY t.id
             ORDER BY total DESC
             LIMIT 10"
        );
        $perTag = $perTagStmt->fetchAll();

        // Jumlah kegiatan per provinsi (lebih luas dari per kabupaten/kota di atas)
        $perProvinsiStmt = $pdo->query(
            "SELECT l.provinsi, COUNT(k.id) AS total
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             WHERE k.status_verifikasi = 'approved'
             GROUP BY l.provinsi
             ORDER BY total DESC
             LIMIT 10"
        );
        $perProvinsi = $perProvinsiStmt->fetchAll();

        // Kegiatan nasional vs internasional (berdasarkan lokasi.negara)
        $perNegaraStmt = $pdo->query(
            "SELECT CASE WHEN l.negara = 'Indonesia' THEN 'Nasional' ELSE 'Internasional' END AS kelompok,
                    COUNT(k.id) AS total
             FROM kegiatan k
             JOIN lokasi l ON l.id = k.location_id
             WHERE k.status_verifikasi = 'approved'
             GROUP BY kelompok"
        );
        $perNegara = $perNegaraStmt->fetchAll();

        // Distribusi skala kegiatan (Kota/Kabupaten, Provinsi, Nasional, dst)
        $perSkalaStmt = $pdo->query(
            "SELECT COALESCE(NULLIF(skala_kegiatan, ''), 'Tidak diketahui') AS skala, COUNT(*) AS total
             FROM kegiatan
             WHERE status_verifikasi = 'approved'
             GROUP BY skala
             ORDER BY total DESC"
        );
        $perSkala = $perSkalaStmt->fetchAll();

        // Kegiatan per departemen yang terlibat
        $perDepartemenStmt = $pdo->query(
            "SELECT kd.departemen_kode, COUNT(*) AS total
             FROM kegiatan_departemen kd
             JOIN kegiatan k ON k.id = kd.kegiatan_id AND k.status_verifikasi = 'approved'
             GROUP BY kd.departemen_kode
             ORDER BY total DESC"
        );
        $perDepartemen = $perDepartemenStmt->fetchAll();

        // Tren jumlah peserta per bulan (pelengkap tren jumlah kegiatan di atas)
        $perBulanPesertaStmt = $pdo->query(
            "SELECT DATE_FORMAT(tanggal_mulai, '%Y-%m') AS bulan, COALESCE(SUM(jumlah_peserta), 0) AS total
             FROM kegiatan
             WHERE status_verifikasi = 'approved'
             GROUP BY bulan
             ORDER BY bulan ASC"
        );
        $perBulanPeserta = $perBulanPesertaStmt->fetchAll();

        // Ringkasan angka buat kartu kecil di atas dashboard
        $ringkasanStmt = $pdo->query(
            "SELECT
                COUNT(*) AS total_kegiatan,
                SUM(CASE WHEN " . STATUS_CASE_SQL . " = 'selesai' THEN 1 ELSE 0 END) AS total_selesai,
                SUM(CASE WHEN " . STATUS_CASE_SQL . " = 'berlangsung' THEN 1 ELSE 0 END) AS total_berlangsung,
                SUM(CASE WHEN " . STATUS_CASE_SQL . " = 'belum_berlangsung' THEN 1 ELSE 0 END) AS total_akan_datang,
                COALESCE(SUM(jumlah_peserta), 0) AS total_peserta
             FROM kegiatan k
             WHERE k.status_verifikasi = 'approved'"
        );
        $ringkasan = $ringkasanStmt->fetch();

        echo json_encode([
            'success'          => true,
            'ringkasan'        => $ringkasan,
            'per_lokasi'       => $perLokasi,
            'per_bulan'        => $perBulan,
            'per_tag'          => $perTag,
            'per_provinsi'     => $perProvinsi,
            'per_negara'       => $perNegara,
            'per_skala'        => $perSkala,
            'per_departemen'   => $perDepartemen,
            'per_bulan_peserta'=> $perBulanPeserta,
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data kegiatan.']);
}