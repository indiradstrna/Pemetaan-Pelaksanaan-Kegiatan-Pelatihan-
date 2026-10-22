<?php
/**
 * /api/dashboard_data.php
 *
 * GET ?period=bulan|tahun|semua -> data JSON untuk admin/dashboard.php
 *
 * PENTING: file ini HARUS selalu mengeluarkan JSON, tidak boleh ada
 * output HTML apapun sebelum/sesudahnya (makanya tidak require
 * navbar_admin.php / footer_admin.php seperti halaman biasa).
 *
 * Semua angka statistik (stat card, grafik, jumlah data per daerah/kategori,
 * kegiatan terbaru) cuma dihitung dari kegiatan status_verifikasi='approved'
 * -- biar konsisten sama yang dilihat Guest/User di peta publik (kegiatan
 * pending/rejected gak nyempil ke angka dashboard). Checklist "Kegiatan
 * Tanpa Dokumentasi" di bagian bawah SENGAJA tidak difilter -- itu daftar
 * pengingat buat staf, relevan terlepas dari status verifikasinya.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/staff_page_check.php';

// Status real-time: 'selesai' tersimpan (ditandai manual lewat admin) selalu
// menang; sisanya (belum_berlangsung/berlangsung) dihitung ulang tiap dibaca
// mengikuti tanggal hari ini -- pola sama seperti STATUS_CASE_SQL di
// api/get_kegiatan_publik.php & api/crud_kegiatan.php, supaya konsisten
// dan tidak nyangkut di status lama seperti kolom `status` mentah.
const STATUS_CASE_SQL = "
    CASE
        WHEN k.status = 'selesai' THEN 'selesai'
        WHEN CURDATE() < k.tanggal_mulai THEN 'belum_berlangsung'
        ELSE 'berlangsung'
    END
";

const NAMA_BULAN_ID = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

try {
    $periode = $_GET['period'] ?? 'bulan';
    if (!in_array($periode, ['bulan', 'tahun', 'semua'], true)) {
        $periode = 'bulan';
    }

    // ==================== Rentang tanggal per periode ====================
    $now = new DateTime();
    $rangeSekarang = null; // [start, end] atau null utk 'semua'
    $rangeSebelumnya = null;

    if ($periode === 'bulan') {
        $start = new DateTime($now->format('Y-m-01'));
        $end   = (clone $start)->modify('first day of next month');
        $prevStart = (clone $start)->modify('-1 month');
        $prevEnd   = $start;
        $rangeSekarang   = [$start, $end];
        $rangeSebelumnya = [$prevStart, $prevEnd];
    } elseif ($periode === 'tahun') {
        $start = new DateTime($now->format('Y-01-01'));
        $end   = (clone $start)->modify('+1 year');
        $prevStart = (clone $start)->modify('-1 year');
        $prevEnd   = $start;
        $rangeSekarang   = [$start, $end];
        $rangeSebelumnya = [$prevStart, $prevEnd];
    }
    // 'semua' -> $rangeSekarang tetap null (tidak difilter tanggal)

    // Label buat teks trend, misal "bulan sebelumnya (Juni)" / "tahun sebelumnya (2025)".
    $trendLabel = null;
    if ($periode === 'bulan') {
        $trendLabel = 'bulan sebelumnya (' . NAMA_BULAN_ID[(int) $rangeSebelumnya[0]->format('n')] . ')';
    } elseif ($periode === 'tahun') {
        $trendLabel = 'tahun sebelumnya (' . $rangeSebelumnya[0]->format('Y') . ')';
    }

    function hitungKegiatan(PDO $pdo, ?array $range, ?string $status = null): int
    {
        $where = ["k.status_verifikasi = 'approved'"];
        $params = [];
        if ($range !== null) {
            $where[] = 'k.tanggal_mulai >= :start AND k.tanggal_mulai < :end';
            $params['start'] = $range[0]->format('Y-m-d');
            $params['end']   = $range[1]->format('Y-m-d');
        }
        if ($status !== null) {
            $where[] = '(' . STATUS_CASE_SQL . ') = :status';
            $params['status'] = $status;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kegiatan k {$whereSql}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    function hitungTrend(int $sekarang, ?int $sebelumnya): ?int
    {
        if ($sebelumnya === null) return null;
        if ($sebelumnya > 0) return (int) round((($sekarang - $sebelumnya) / $sebelumnya) * 100);
        return $sekarang > 0 ? 100 : 0;
    }

    // ==================== Stat cards ====================
    $totalKegiatan = hitungKegiatan($pdo, $rangeSekarang);
    $totalSebelumnya = $rangeSebelumnya !== null ? hitungKegiatan($pdo, $rangeSebelumnya) : null;
    $trendPersen = hitungTrend($totalKegiatan, $totalSebelumnya);

    $kegiatanSelesai = hitungKegiatan($pdo, $rangeSekarang, 'selesai');

    $kegiatanBerlangsung = (int) $pdo->query(
        "SELECT COUNT(*) FROM kegiatan k WHERE k.status_verifikasi = 'approved' AND (" . STATUS_CASE_SQL . ") = 'berlangsung'"
    )->fetchColumn();

    $lokasiBerlangsung = (int) $pdo->query(
        "SELECT COUNT(DISTINCT location_id) FROM kegiatan k WHERE k.status_verifikasi = 'approved' AND (" . STATUS_CASE_SQL . ") = 'berlangsung'"
    )->fetchColumn();

    $kegiatanBelumBerlangsung = (int) $pdo->query(
        "SELECT COUNT(*) FROM kegiatan k WHERE k.status_verifikasi = 'approved' AND (" . STATUS_CASE_SQL . ") = 'belum_berlangsung'"
    )->fetchColumn();

    $terakhirUpdate = $pdo->query("SELECT MAX(updated_at) FROM kegiatan WHERE status_verifikasi = 'approved'")->fetchColumn();

    // ==================== Grafik (selesai vs berlangsung) ====================
    $labels = [];
    $dataSelesai = [];
    $dataBerlangsung = [];

    if ($periode === 'bulan') {
        // Bagi bulan berjalan menjadi 4 minggu
        $awalBulan = new DateTime($now->format('Y-m-01'));
        for ($i = 0; $i < 4; $i++) {
            $mingguStart = (clone $awalBulan)->modify("+{$i} week");
            $mingguEnd   = (clone $mingguStart)->modify('+1 week');
            $labels[] = 'Minggu ' . ($i + 1);

            $stmt = $pdo->prepare(
                "SELECT (" . STATUS_CASE_SQL . ") AS status, COUNT(*) AS jml FROM kegiatan k
                 WHERE k.status_verifikasi = 'approved'
                   AND k.tanggal_mulai >= :start AND k.tanggal_mulai < :end
                 GROUP BY (" . STATUS_CASE_SQL . ")"
            );
            $stmt->execute([
                'start' => $mingguStart->format('Y-m-d'),
                'end'   => $mingguEnd->format('Y-m-d'),
            ]);
            $rows = $stmt->fetchAll();
            $selesai = 0; $berlangsung = 0;
            foreach ($rows as $r) {
                if ($r['status'] === 'selesai') $selesai = (int) $r['jml'];
                if ($r['status'] === 'berlangsung') $berlangsung = (int) $r['jml'];
            }
            $dataSelesai[] = $selesai;
            $dataBerlangsung[] = $berlangsung;
        }
    } elseif ($periode === 'tahun') {
        $namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $tahunIni = $now->format('Y');
        $stmt = $pdo->prepare(
            "SELECT MONTH(k.tanggal_mulai) AS bln, (" . STATUS_CASE_SQL . ") AS status, COUNT(*) AS jml
             FROM kegiatan k WHERE k.status_verifikasi = 'approved' AND YEAR(k.tanggal_mulai) = :tahun
             GROUP BY MONTH(k.tanggal_mulai), (" . STATUS_CASE_SQL . ")"
        );
        $stmt->execute(['tahun' => $tahunIni]);
        $rows = $stmt->fetchAll();
        $peta = [];
        foreach ($rows as $r) {
            $peta[(int) $r['bln']][$r['status']] = (int) $r['jml'];
        }
        for ($b = 1; $b <= 12; $b++) {
            $labels[] = $namaBulan[$b - 1];
            $dataSelesai[] = $peta[$b]['selesai'] ?? 0;
            $dataBerlangsung[] = $peta[$b]['berlangsung'] ?? 0;
        }
    } else { // semua
        $stmt = $pdo->query(
            "SELECT YEAR(k.tanggal_mulai) AS thn, (" . STATUS_CASE_SQL . ") AS status, COUNT(*) AS jml
             FROM kegiatan k WHERE k.status_verifikasi = 'approved'
             GROUP BY YEAR(k.tanggal_mulai), (" . STATUS_CASE_SQL . ") ORDER BY thn ASC"
        );
        $rows = $stmt->fetchAll();
        $peta = [];
        foreach ($rows as $r) {
            $peta[(int) $r['thn']][$r['status']] = (int) $r['jml'];
        }
        $tahunList = array_keys($peta);
        if (empty($tahunList)) {
            $tahunList = [(int) $now->format('Y')];
        }
        sort($tahunList);
        foreach ($tahunList as $thn) {
            $labels[] = (string) $thn;
            $dataSelesai[] = $peta[$thn]['selesai'] ?? 0;
            $dataBerlangsung[] = $peta[$thn]['berlangsung'] ?? 0;
        }
    }

    // ==================== Jumlah Data (tab: daerah & kategori) ====================
    $filterTanggal = '';
    $paramsPeriode = [];
    if ($rangeSekarang !== null) {
        $filterTanggal = ' AND k.tanggal_mulai >= :start AND k.tanggal_mulai < :end ';
        $paramsPeriode = [
            'start' => $rangeSekarang[0]->format('Y-m-d'),
            'end'   => $rangeSekarang[1]->format('Y-m-d'),
        ];
    }

    $stmt = $pdo->prepare(
        "SELECT l.kabupaten_kota AS label, COUNT(*) AS jumlah
         FROM kegiatan k
         JOIN lokasi l ON l.id = k.location_id
         WHERE k.status_verifikasi = 'approved' {$filterTanggal}
         GROUP BY l.kabupaten_kota
         ORDER BY jumlah DESC"
    );
    $stmt->execute($paramsPeriode);
    $jdDaerah = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT t.nama_tag AS label, COUNT(*) AS jumlah
         FROM kegiatan_tags kt
         JOIN tags t ON t.id = kt.tag_id
         JOIN kegiatan k ON k.id = kt.kegiatan_id
         WHERE k.status_verifikasi = 'approved' {$filterTanggal}
         GROUP BY t.nama_tag
         ORDER BY jumlah DESC"
    );
    $stmt->execute($paramsPeriode);
    $jdKategori = $stmt->fetchAll();

    // ==================== Chart breakdown tambahan (semua ikut filter periode yang sama) ====================
    // Status kegiatan dalam periode terpilih (beda dari stat card $kegiatanBerlangsung dkk
    // yang selalu "hari ini", bukan dibatasi periode -- ini versi period-filtered buat donut chart).
    $stmt = $pdo->prepare(
        "SELECT (" . STATUS_CASE_SQL . ") AS status, COUNT(*) AS jumlah
         FROM kegiatan k
         WHERE k.status_verifikasi = 'approved' {$filterTanggal}
         GROUP BY (" . STATUS_CASE_SQL . ")"
    );
    $stmt->execute($paramsPeriode);
    $rows = $stmt->fetchAll();
    $petaStatus = ['belum_berlangsung' => 0, 'berlangsung' => 0, 'selesai' => 0];
    foreach ($rows as $r) {
        $petaStatus[$r['status']] = (int) $r['jumlah'];
    }
    $chartStatus = [
        ['label' => 'Akan Datang', 'jumlah' => $petaStatus['belum_berlangsung']],
        ['label' => 'Berlangsung', 'jumlah' => $petaStatus['berlangsung']],
        ['label' => 'Selesai',     'jumlah' => $petaStatus['selesai']],
    ];

    $stmt = $pdo->prepare(
        "SELECT l.provinsi AS label, COUNT(k.id) AS jumlah
         FROM kegiatan k
         JOIN lokasi l ON l.id = k.location_id
         WHERE k.status_verifikasi = 'approved' {$filterTanggal}
         GROUP BY l.provinsi
         ORDER BY jumlah DESC"
    );
    $stmt->execute($paramsPeriode);
    $chartProvinsi = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT CASE WHEN l.negara = 'Indonesia' THEN 'Nasional' ELSE 'Internasional' END AS label,
                COUNT(k.id) AS jumlah
         FROM kegiatan k
         JOIN lokasi l ON l.id = k.location_id
         WHERE k.status_verifikasi = 'approved' {$filterTanggal}
         GROUP BY label"
    );
    $stmt->execute($paramsPeriode);
    $chartNegara = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT COALESCE(NULLIF(skala_kegiatan, ''), 'Tidak diketahui') AS label, COUNT(*) AS jumlah
         FROM kegiatan k
         WHERE k.status_verifikasi = 'approved' {$filterTanggal}
         GROUP BY label
         ORDER BY jumlah DESC"
    );
    $stmt->execute($paramsPeriode);
    $chartSkala = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT kd.departemen_kode AS label, COUNT(*) AS jumlah
         FROM kegiatan_departemen kd
         JOIN kegiatan k ON k.id = kd.kegiatan_id
         WHERE k.status_verifikasi = 'approved' {$filterTanggal}
         GROUP BY kd.departemen_kode
         ORDER BY jumlah DESC"
    );
    $stmt->execute($paramsPeriode);
    $chartDepartemen = $stmt->fetchAll();

    // Tren per bulan/minggu/tahun -- total kegiatan (SEMUA status, gak kayak $labels/$dataSelesai/
    // $dataBerlangsung di atas yang sengaja cuma selesai+berlangsung) & total peserta. Bucket-nya
    // ngikutin periode yang sama persis kayak grafik utama di atas (minggu utk 'bulan',
    // bulan utk 'tahun', tahun utk 'semua') biar labelnya konsisten.
    $trenLabels = [];
    $trenTotal = [];
    $trenPeserta = [];

    if ($periode === 'bulan') {
        $awalBulan = new DateTime($now->format('Y-m-01'));
        for ($i = 0; $i < 4; $i++) {
            $mingguStart = (clone $awalBulan)->modify("+{$i} week");
            $mingguEnd   = (clone $mingguStart)->modify('+1 week');
            $trenLabels[] = 'Minggu ' . ($i + 1);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS jml, COALESCE(SUM(jumlah_peserta), 0) AS peserta
                 FROM kegiatan k
                 WHERE k.status_verifikasi = 'approved'
                   AND k.tanggal_mulai >= :start AND k.tanggal_mulai < :end"
            );
            $stmt->execute(['start' => $mingguStart->format('Y-m-d'), 'end' => $mingguEnd->format('Y-m-d')]);
            $row = $stmt->fetch();
            $trenTotal[] = (int) $row['jml'];
            $trenPeserta[] = (int) $row['peserta'];
        }
    } elseif ($periode === 'tahun') {
        $namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $stmt = $pdo->prepare(
            "SELECT MONTH(k.tanggal_mulai) AS bln, COUNT(*) AS jml, COALESCE(SUM(k.jumlah_peserta), 0) AS peserta
             FROM kegiatan k
             WHERE k.status_verifikasi = 'approved' AND YEAR(k.tanggal_mulai) = :tahun
             GROUP BY MONTH(k.tanggal_mulai)"
        );
        $stmt->execute(['tahun' => $now->format('Y')]);
        $rows = $stmt->fetchAll();
        $peta = [];
        foreach ($rows as $r) {
            $peta[(int) $r['bln']] = ['jml' => (int) $r['jml'], 'peserta' => (int) $r['peserta']];
        }
        for ($b = 1; $b <= 12; $b++) {
            $trenLabels[] = $namaBulan[$b - 1];
            $trenTotal[] = $peta[$b]['jml'] ?? 0;
            $trenPeserta[] = $peta[$b]['peserta'] ?? 0;
        }
    } else { // semua
        $stmt = $pdo->query(
            "SELECT YEAR(k.tanggal_mulai) AS thn, COUNT(*) AS jml, COALESCE(SUM(k.jumlah_peserta), 0) AS peserta
             FROM kegiatan k
             WHERE k.status_verifikasi = 'approved'
             GROUP BY YEAR(k.tanggal_mulai) ORDER BY thn ASC"
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $trenLabels[] = (string) $r['thn'];
            $trenTotal[] = (int) $r['jml'];
            $trenPeserta[] = (int) $r['peserta'];
        }
        if (empty($trenLabels)) {
            $trenLabels[] = $now->format('Y');
            $trenTotal[] = 0;
            $trenPeserta[] = 0;
        }
    }

    // ==================== Kegiatan Terbaru ====================
    $kegiatanTerbaru = $pdo->query(
        "SELECT k.id, k.kode_kegiatan, k.judul, (" . STATUS_CASE_SQL . ") AS status
         FROM kegiatan k WHERE k.status_verifikasi = 'approved' ORDER BY k.created_at DESC LIMIT 5"
    )->fetchAll();

    // ==================== Perlu Perhatian (reminder, dulu namanya "Notifikasi" --
    // di-rename biar gak ketuker sama sistem notifikasi lonceng di navbar/tabel `notifikasi`) ====================
    // Kegiatan yang tanggal_selesai-nya udah lewat tapi belum ditandai 'selesai' manual
    // (lewat action=selesaikan) -- kemungkinan Internal lupa nutup laporan kegiatannya.
    $kegiatanTerlambat = $pdo->query(
        "SELECT id, judul, tanggal_selesai FROM kegiatan
         WHERE status != 'selesai' AND tanggal_selesai < CURDATE()
         ORDER BY tanggal_selesai ASC"
    )->fetchAll();

    $lokasiBelumVerif = $pdo->query(
        "SELECT id, nama_lokasi FROM lokasi WHERE koordinat_manual = 0 ORDER BY created_at DESC"
    )->fetchAll();

    $kegiatanTanpaFoto = $pdo->query(
        "SELECT id, judul FROM kegiatan
         WHERE foto_utama IS NULL
           AND id NOT IN (SELECT DISTINCT kegiatan_id FROM kegiatan_foto)
         ORDER BY created_at DESC"
    )->fetchAll();

    $response = [
        'success' => true,
        'data' => [
            'stat' => [
                'total_kegiatan'        => $totalKegiatan,
                'trend_persen'          => $trendPersen,
                'kegiatan_berlangsung'  => $kegiatanBerlangsung,
                'lokasi_berlangsung'    => $lokasiBerlangsung,
                'kegiatan_selesai'      => $kegiatanSelesai,
                'kegiatan_belum_berlangsung' => $kegiatanBelumBerlangsung,
                'trend_label'           => $trendLabel,
                'terakhir_update'       => $terakhirUpdate,
            ],
            'grafik' => [
                'labels'      => $labels,
                'selesai'     => $dataSelesai,
                'berlangsung' => $dataBerlangsung,
            ],
            'jumlah_data' => [
                'daerah'   => $jdDaerah,
                'kategori' => $jdKategori,
            ],
            'chart_tambahan' => [
                'status'     => $chartStatus,
                'lokasi'     => $jdDaerah,
                'provinsi'   => $chartProvinsi,
                'negara'     => $chartNegara,
                'skala'      => $chartSkala,
                'departemen' => $chartDepartemen,
                'tag'        => $jdKategori,
                'tren_bulan' => [
                    'labels'  => $trenLabels,
                    'total'   => $trenTotal,
                    'peserta' => $trenPeserta,
                ],
            ],
            'kegiatan_terbaru' => $kegiatanTerbaru,
            // Batas 30 per kategori (bukan 3 lagi) -- sekarang di-paginate 3/halaman
            // di sisi frontend (tombol ‹ ›), jadi kirim lebih banyak biar bisa di-browse.
            'pengingat' => [
                'kegiatan_terlambat' => [
                    'items' => array_slice($kegiatanTerlambat, 0, 30),
                    'total' => count($kegiatanTerlambat),
                ],
                'lokasi_tanpa_verifikasi' => [
                    'items' => array_slice($lokasiBelumVerif, 0, 30),
                    'total' => count($lokasiBelumVerif),
                ],
                'kegiatan_tanpa_dokumentasi' => [
                    'items' => array_slice($kegiatanTanpaFoto, 0, 30),
                    'total' => count($kegiatanTanpaFoto),
                ],
            ],
        ],
    ];

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memuat data dashboard: ' . $e->getMessage(),
    ]);
}