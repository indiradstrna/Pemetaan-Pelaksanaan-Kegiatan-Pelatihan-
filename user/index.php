<?php
/**
 * Halaman publik: Peta Kegiatan (Activities in Spatial)
 *
 * Bisa diakses TANPA LOGIN. Login (auth/login.php) sifatnya opsional,
 * hanya diperlukan kalau pengunjung ingin melihat detail lebih lanjut
 * atau masuk sebagai staf.
 *
 * Sebelum peta ditampilkan, pengunjung yang baru pertama kali membuka
 * website (belum pernah isi survey & belum login) akan diminta mengisi
 * survey singkat (Profesi, Gender, Asal Daerah) lewat popup di atas peta.
 * Jawaban disimpan ke tabel `survey_pengunjung`
 * (lihat sql/create_survey_pengunjung.sql) dan ditandai lewat cookie
 * supaya tidak muncul lagi di kunjungan berikutnya (browser yang sama).
 */

session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

const SURVEY_COOKIE = 'biotrop_survey_done';

$sudahLogin     = isset($_SESSION['user_id']);
$sudahIsiSurvey = isset($_COOKIE[SURVEY_COOKIE]);

// Staf/user yang sudah login dianggap sudah "dikenal", jadi survey dilewati.
$perluSurvey = !$sudahLogin && !$sudahIsiSurvey;

$errorSurvey = '';

// Field-field yang tadi diisi, dipakai lagi buat isi ulang form kalau ada error validasi.
$oldInput = [
    'profesi'     => '',
    'institusi'   => '',
    'gender'      => '',
    'asal_daerah' => '',
    'provinsi'    => '',
    'negara'      => '',
];

// ---------- Proses submit survey ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_survey'])) {
    $profesiValid = ['Pelajar', 'Guru', 'Dosen', 'Peneliti', 'Umum'];
    $genderValid  = ['Laki-laki', 'Perempuan'];
    $asalValid    = ['Nasional', 'Internasional'];

    $profesi    = trim($_POST['profesi'] ?? '');
    $institusi  = trim($_POST['institusi'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $asalDaerah = trim($_POST['asal_daerah'] ?? '');
    $provinsi   = trim($_POST['provinsi'] ?? '');
    $negara     = trim($_POST['negara'] ?? '');

    $oldInput = [
        'profesi'     => $profesi,
        'institusi'   => $institusi,
        'gender'      => $gender,
        'asal_daerah' => $asalDaerah,
        'provinsi'    => $provinsi,
        'negara'      => $negara,
    ];

    $perluInstitusi = in_array($profesi, ['Pelajar', 'Guru', 'Dosen'], true);

    if (!in_array($profesi, $profesiValid, true)
        || !in_array($gender, $genderValid, true)
        || !in_array($asalDaerah, $asalValid, true)) {
        $errorSurvey = 'Mohon lengkapi semua pertanyaan survey.';
    } elseif ($perluInstitusi && $institusi === '') {
        $errorSurvey = $profesi === 'Pelajar'
            ? 'Mohon isi nama sekolah/universitas Anda.'
            : 'Mohon isi nama institusi Anda.';
    } elseif ($asalDaerah === 'Nasional' && $provinsi === '') {
        $errorSurvey = 'Mohon pilih provinsi asal Anda.';
    } elseif ($asalDaerah === 'Internasional' && $negara === '') {
        $errorSurvey = 'Mohon isi negara asal Anda.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO survey_pengunjung (profesi, institusi, gender, asal_daerah, provinsi, negara)
             VALUES (:profesi, :institusi, :gender, :asal_daerah, :provinsi, :negara)'
        );
        $stmt->execute([
            'profesi'     => $profesi,
            'institusi'   => $perluInstitusi ? substr($institusi, 0, 150) : null,
            'gender'      => $gender,
            'asal_daerah' => $asalDaerah,
            'provinsi'    => $asalDaerah === 'Nasional' ? substr($provinsi, 0, 100) : null,
            'negara'      => $asalDaerah === 'Internasional' ? substr($negara, 0, 100) : null,
        ]);

        // Tandai supaya tidak ditanya lagi (berlaku 1 tahun).
        setcookie(SURVEY_COOKIE, '1', time() + 60 * 60 * 24 * 365, '/');

        // Redirect ke diri sendiri (pola POST-redirect-GET) supaya refresh
        // tidak submit ulang dan peta langsung tampil.
        header('Location: ' . BASE_URL . '/user/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activities in Spatial - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <style>
        :root {
            --green-start:  #43a047;
            --green-end:    #1b5e20;
            --green-soft:   #e8f5e9;
            --overlay-tint: rgba(9, 38, 18, .55);
            /* Warna status peta/kartu -- sengaja dipisah dari hijau brand & kuning tag-pill
               biar node peta gak kebaur sama warna UI lain (feedback mentor). */
            --status-selesai:      #2563eb;
            --status-berlangsung:  #d97706;
            --status-akan-datang:  #7c3aed;
            --status-selesai-soft:     #e8f0fe;
            --status-berlangsung-soft: #fef3e2;
            --status-akan-datang-soft: #f1e9fc;
            --text-dark:  #16181a;
            --text-gray:  #6b7280;
            --border-gray:#d8dbe0;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            margin: 0;
        }

        /* ---------- Topbar ---------- */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 24px;
            background: linear-gradient(90deg, var(--green-end), var(--green-start));
            color: #fff;
            height: 64px;
        }

        .topbar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .topbar .brand .title { line-height: 1.15; }

        .topbar .brand .title small {
            display: block;
            font-weight: 500;
            font-size: .78rem;
            opacity: .9;
        }

        .topbar .actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar .actions > span,
        .topbar .actions > a.btn-login-outline {
            color: #fff;
            font-size: .9rem;
            text-decoration: none;
        }

        .btn-login-outline {
            border: 1.5px solid rgba(255,255,255,.8);
            padding: 7px 18px;
            border-radius: 8px;
            font-weight: 600;
            transition: background .15s ease;
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

        /* ---------- App body (sidebar + peta) ---------- */
        .app-body {
            display: flex;
            height: calc(100vh - 64px);
            overflow: hidden;
        }

        /* ---------- Icon rail (selalu tampil, mini) ---------- */
        .rail {
            width: 64px;
            flex-shrink: 0;
            background: #fff;
            border-right: 1px solid var(--border-gray);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 14px 0;
            gap: 8px;
        }

        .rail-btn {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-gray);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.05rem;
            text-decoration: none;
        }

        .rail-btn:hover { background: #f2f4f2; color: var(--text-gray); }
        .rail-btn.active { background: var(--green-soft); color: var(--green-end); }

        /* Ikon Panel Admin -- sejajar dengan Peta/Dashboard, dibikin sedikit lebih tebal/menonjol */
        .rail-btn-admin { font-size: 1.25rem; font-weight: 700; color: var(--green-end); }
        .rail-btn-admin:hover { background: var(--green-soft); color: var(--green-end); }

        .rail-divider { width: 30px; height: 1px; background: var(--border-gray); margin: 4px 0 8px; }

        /* ---------- Panel lebar (Peta / Dashboard), bisa di-collapse terpisah dari rail ---------- */
        .panel-wide {
            width: 420px;
            min-width: 420px;
            background: #fff;
            border-right: 1px solid var(--border-gray);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width .22s ease, min-width .22s ease;
        }

        .panel-wide.collapsed { width: 0; min-width: 0; border-right-width: 0; }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            font-weight: 800;
            font-size: .95rem;
            border-bottom: 1px solid var(--border-gray);
            flex-shrink: 0;
            white-space: nowrap;
        }

        .panel-header button {
            background: none;
            border: none;
            color: var(--text-gray);
            cursor: pointer;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .panel-header button:hover { color: var(--text-dark); }

        .sidebar-panel {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            width: 420px;
        }

        #panelPeta { display: flex; flex-direction: column; }

        /* Scope+search+filter+chips nempel di atas biar gak ke-scroll ke atas bareng list kartu. */
        .panel-peta-sticky-top {
            position: sticky;
            top: -16px;
            margin: -16px -16px 12px;
            padding: 16px 16px 0;
            background: #fff;
            z-index: 6;
        }

        /* ---------- Search & filter ---------- */
        .search-row { display: flex; gap: 8px; margin-bottom: 10px; }

        /* ---------- Scope toggle: Nasional / Internasional / Semua ---------- */
        .scope-toggle {
            display: flex;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .scope-btn {
            flex: 1;
            padding: 8px 4px;
            text-align: center;
            font-size: .76rem;
            font-weight: 700;
            background: #fff;
            border: none;
            border-right: 1px solid var(--border-gray);
            cursor: pointer;
            color: var(--text-gray);
        }

        .scope-btn:last-child { border-right: none; }
        .scope-btn.active { background: var(--green-start); color: #fff; }

        .search-box { flex: 1; position: relative; }

        .search-box i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            font-size: .85rem;
        }

        .search-box input {
            width: 100%;
            padding: 9px 12px 9px 32px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            font-size: .86rem;
        }

        .search-box input:focus { outline: none; border-color: var(--green-start); }

        .btn-filter {
            padding: 9px 14px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            background: #fff;
            font-size: .84rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .filter-popup {
            position: fixed;
            width: 400px;
            max-width: calc(100vw - 32px);
            display: flex;
            flex-direction: column;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 40px rgba(0,0,0,.18);
            border: 1px solid var(--border-gray);
            z-index: 2001;
        }
        .filter-popup-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 18px 12px;
            font-weight: 700;
            font-size: 1.05rem;
            flex-shrink: 0;
        }
        .filter-reset-link {
            border: none; background: none;
            color: var(--green-start);
            font-weight: 700;
            font-size: .82rem;
            cursor: pointer;
            padding: 2px;
        }
        .filter-reset-link:hover { text-decoration: underline; }
        .filter-popup-body {
            padding: 0 18px 4px;
            overflow-y: auto;
        }

        .accordion-list {
            border: 1px solid var(--border-gray);
            border-radius: 12px;
            overflow: hidden;
        }
        .acc-item { border-bottom: 1px solid var(--border-gray); background: #fff; }
        .acc-item:last-child { border-bottom: none; }
        .acc-header {
            width: 100%;
            display: flex; align-items: center; gap: 10px;
            padding: 13px 14px;
            background: none; border: none;
            cursor: pointer;
            font-size: .88rem;
            font-weight: 600;
            color: #222;
            text-align: left;
        }
        .acc-header:hover { background: #fafbfa; }
        .acc-icon { color: var(--text-gray); flex-shrink: 0; }
        .acc-label { flex: 1; }
        .acc-count {
            background: #eaf5ee;
            color: var(--green-start);
            font-size: .72rem;
            font-weight: 700;
            border-radius: 10px;
            padding: 1px 8px;
        }
        .acc-chevron { color: var(--text-gray); flex-shrink: 0; transition: transform .15s ease; }
        .acc-item.open .acc-chevron { transform: rotate(180deg); }
        .acc-panel { display: none; padding: 0 14px 14px; }
        .acc-item.open .acc-panel { display: block; }

        .filter-popup-footer {
            display: flex; gap: 10px;
            padding: 14px 18px;
            border-top: 1px solid var(--border-gray);
            flex-shrink: 0;
        }
        .btn-filter-close {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            background: #fff;
            font-weight: 700;
            font-size: .85rem;
            cursor: pointer;
        }
        .btn-filter-close:hover { background: #f5f7f5; }
        .filter-popup .btn-apply-filter { flex: 1; width: auto; margin: 0; }

        .filter-group { margin-bottom: 0; }

        .filter-group label {
            font-size: .78rem;
            font-weight: 700;
            display: block;
            margin-bottom: 4px;
            color: var(--text-gray);
        }

        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            font-size: .84rem;
        }
        .acc-panel select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            font-size: .84rem;
        }

        .ms-search-input {
            width: 100%;
            padding: 7px 8px;
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            font-size: .82rem;
            margin-bottom: 6px;
            box-sizing: border-box;
        }
        .ms-options { max-height: 180px; overflow-y: auto; }
        .ms-option {
            display: flex; align-items: center; gap: 7px;
            padding: 6px 4px;
            font-size: .84rem;
            border-radius: 5px;
            cursor: pointer;
        }
        .ms-option:hover { background: #f5f7f5; }
        .ms-option input { margin: 0; }
        .ms-empty { padding: 10px 4px; font-size: .8rem; color: var(--text-gray); }

        .btn-apply-filter {
            width: 100%;
            background: var(--green-start);
            color: #fff;
            border: none;
            padding: 9px;
            border-radius: 7px;
            font-weight: 700;
            font-size: .85rem;
            cursor: pointer;
        }

        .active-chips { display: none; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; }

        .chip {
            background: var(--green-soft);
            color: var(--green-end);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: .76rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chip button {
            border: none;
            background: none;
            color: var(--green-end);
            cursor: pointer;
            font-weight: 700;
            font-size: .9rem;
            line-height: 1;
        }

        .clear-filter-link {
            font-size: .78rem;
            color: var(--text-gray);
            text-decoration: underline;
            cursor: pointer;
            background: none;
            border: none;
        }

        .sort-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .8rem;
            color: var(--text-gray);
            margin-bottom: 10px;
        }

        /* ---------- Kartu kegiatan ---------- */
        .card-list { display: flex; flex-direction: column; gap: 12px; }

        .kegiatan-card {
            border: 1px solid var(--border-gray);
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .kegiatan-card:hover {
            border-color: var(--green-start);
            box-shadow: 0 2px 10px rgba(67, 160, 71, .12);
        }

        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 6px;
        }

        .badge-kode {
            background: #eef0f2;
            color: #444;
            font-size: .7rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .badge-status { font-size: .7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .badge-status.selesai { background: var(--status-selesai-soft); color: var(--status-selesai); }
        .badge-status.berlangsung { background: var(--status-berlangsung-soft); color: var(--status-berlangsung); }
        .badge-status.belum_berlangsung { background: var(--status-akan-datang-soft); color: var(--status-akan-datang); }

        .card-date { font-size: .76rem; color: var(--text-gray); display: flex; align-items: center; gap: 4px; }

        .card-peserta {
            font-size: .8rem;
            font-weight: 700;
            color: var(--green-end);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
        }

        .card-title { font-weight: 700; font-size: .94rem; margin-bottom: 6px; line-height: 1.3; }

        .card-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }

        .tag-pill { background: #fff8e1; color: #8d6e00; font-size: .7rem; font-weight: 700; padding: 3px 9px; border-radius: 6px; }

        .card-meta { font-size: .78rem; color: var(--text-gray); margin-bottom: 10px; line-height: 1.4; }

        .btn-detail {
            display: block;
            width: 100%;
            text-align: center;
            background: #eaf3ff;
            color: #1565c0;
            padding: 8px;
            border-radius: 8px;
            font-weight: 700;
            font-size: .84rem;
            border: none;
            cursor: pointer;
        }

        .btn-detail:hover { background: #dbeafe; }

        .empty-state { text-align: center; padding: 30px 10px; color: var(--text-gray); font-size: .86rem; }

        /* ---------- Paginasi ---------- */
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 14px; flex-wrap: wrap; }

        .page-btn {
            min-width: 32px;
            height: 32px;
            padding: 0 6px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            background: #fff;
            font-size: .8rem;
            cursor: pointer;
        }

        .page-btn.active { background: var(--green-start); color: #fff; border-color: var(--green-start); font-weight: 700; }
        .page-btn:disabled { opacity: .4; cursor: default; }

        .page-dots { display: flex; align-items: center; justify-content: center; font-size: .8rem; color: var(--text-gray); width: 24px; }


        /* ---------- Area peta ---------- */
        .map-area { position: relative; flex: 1; min-width: 0; }

        #leafletMap { position: absolute; inset: 0; z-index: 1; }

        .map-legend {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0,0,0,.15);
            z-index: 500;
            overflow: hidden;
            min-width: 175px;
        }

        .legend-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 14px;
            font-weight: 700;
            font-size: .84rem;
            border-bottom: 1px solid var(--border-gray);
        }

        .legend-header button {
            border: none;
            background: none;
            font-size: .85rem;
            cursor: pointer;
            color: var(--text-gray);
            line-height: 1;
        }

        .legend-body { padding: 10px 14px; display: flex; flex-direction: column; gap: 8px; font-size: .8rem; }
        .legend-body.collapsed { display: none; }

        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .dot-selesai { background: var(--status-selesai); }
        .dot-berlangsung { background: var(--status-berlangsung); }
        .dot-akan-datang { background: var(--status-akan-datang); }

        /* ---------- Marker & popup peta ---------- */
        /* Cluster: beberapa lokasi digabung jadi satu node cincin (donut) saat zoom out --
           tiap segmen warnanya proporsional sama komposisi status kegiatan di dalamnya.
           Dipakai juga buat marker lokasi individual (ukuran 34px), biar konsisten. */
        .marker-cluster-custom {
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.35);
        }
        .marker-cluster-inner {
            background: #fff;
            color: #222;
            font-weight: 800;
            font-size: .78rem;
            border-radius: 50%;
            width: 64%;
            height: 64%;
            display: flex; align-items: center; justify-content: center;
        }

        .popup-card { width: 225px; }
        .popup-card h6 { font-weight: 700; font-size: .86rem; margin: 0 0 2px; }
        .popup-card .addr { font-size: .72rem; color: var(--text-gray); margin-bottom: 8px; }

        /* Slideshow foto kegiatan di popup */
        .popup-slideshow {
            position: relative;
            width: 100%;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 8px;
            background: #eef0f2;
        }

        .popup-slide-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .popup-slide-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b0b6bd;
            font-size: 1.5rem;
        }

        .popup-slide-counter {
            position: absolute;
            bottom: 6px;
            right: 8px;
            background: rgba(0,0,0,.5);
            color: #fff;
            font-size: .64rem;
            padding: 1px 7px;
            border-radius: 10px;
            z-index: 2;
        }

        /* List kegiatan terbaru di popup */
        .popup-recent-title {
            font-size: .68rem;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: .03em;
            margin: 4px 0 5px;
        }

        .popup-recent-list { list-style: none; margin: 0 0 10px; padding: 0; display: flex; flex-direction: column; gap: 5px; }

        .popup-recent-list li { display: flex; align-items: center; gap: 6px; font-size: .74rem; color: var(--text-dark); }

        .popup-recent-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .popup-recent-dot.selesai { background: var(--status-selesai); }
        .popup-recent-dot.berlangsung { background: var(--status-berlangsung); }
        .popup-recent-dot.belum_berlangsung { background: var(--status-akan-datang); }

        .popup-recent-judul { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .popup-recent-empty { font-size: .74rem; color: var(--text-gray); margin-bottom: 10px; }

        .popup-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: #eef0f2;
            color: #333;
            padding: 6px;
            border-radius: 6px;
            font-size: .76rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
        }

        .popup-btn:hover { background: #e2e5e8; }

        /* ---------- Survey popup ---------- */
        .survey-overlay {
            position: fixed;
            inset: 0;
            background: var(--overlay-tint);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 2000;
        }

        .survey-card {
            background: #fff;
            border-radius: 18px;
            width: 100%;
            max-width: 480px;
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0,0,0,.3);
        }

        .survey-card .brand-mini {
            font-size: .82rem;
            font-weight: 700;
            color: var(--green-end);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }

        .survey-card h3 { font-weight: 800; margin-bottom: 6px; }
        .survey-card .lead-text { color: var(--text-gray); font-size: .92rem; margin-bottom: 22px; }

        .survey-group { margin-bottom: 18px; }
        .survey-group label.survey-label { display: block; font-weight: 700; font-size: .92rem; margin-bottom: 8px; }

        .survey-options { display: flex; flex-wrap: wrap; gap: 8px; }
        .survey-options input[type="radio"] { display: none; }

        .survey-options label.opt {
            border: 1.5px solid var(--border-gray);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: .88rem;
            cursor: pointer;
            transition: all .15s ease;
        }

        .survey-options input[type="radio"]:checked + label.opt {
            background: var(--green-start);
            border-color: var(--green-start);
            color: #fff;
            font-weight: 600;
        }

        .form-control-custom {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            font-size: .9rem;
            font-family: inherit;
        }

        .form-control-custom:focus { outline: none; border-color: var(--green-start); box-shadow: 0 0 0 3px rgba(67, 160, 71, .15); }

        .combo-wrap { position: relative; }

        .combo-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            max-height: 180px;
            overflow-y: auto;
            box-shadow: 0 8px 20px rgba(0,0,0,.12);
            z-index: 10;
        }

        .combo-item { padding: 9px 14px; font-size: .88rem; cursor: pointer; }
        .combo-item:hover, .combo-item.active { background: var(--green-soft); }
        .combo-empty { padding: 9px 14px; font-size: .85rem; color: var(--text-gray); }

        .btn-survey-submit {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(90deg, var(--green-start), var(--green-end));
            cursor: pointer;
            margin-top: 6px;
        }

        .btn-survey-submit:hover { filter: brightness(.96); }

        .survey-error {
            background: #fdecea;
            border: 1px solid #f3c1bf;
            color: #b3261e;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: .86rem;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/../includes/navbar_user.php'; ?>

    <!-- ================= APP BODY: RAIL + PANEL + PETA ================= -->
    <div class="app-body">

        <!-- ---------------- ICON RAIL (selalu tampil, mini) ---------------- -->
        <nav class="rail" id="rail">
            <button type="button" class="rail-btn active" data-tab="peta" title="Peta Kegiatan">
                <i class="bi bi-geo-alt-fill"></i>
            </button>

            <?php if ($sudahLogin && $_SESSION['role'] !== 'user'): ?>
                <div class="rail-divider"></div>
                <a href="../admin/dashboard.php" class="rail-btn rail-btn-admin" title="Panel Admin">
                    <i class="bi bi-speedometer2"></i>
                </a>
            <?php endif; ?>
        </nav>

        <!-- ---------------- PANEL LEBAR (isi Peta) ---------------- -->
        <aside class="panel-wide" id="panelWide">
            <div class="panel-header">
                <span id="panelTitle">Peta Kegiatan</span>
                <button type="button" id="panelCollapseBtn" title="Sembunyikan panel">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
            </div>

            <!-- Menu: Peta (list kegiatan) -->
            <div class="sidebar-panel" id="panelPeta">
                <div class="panel-peta-sticky-top">
                    <div class="scope-toggle" id="scopeToggle">
                        <button type="button" class="scope-btn active" data-scope="nasional">Nasional</button>
                        <button type="button" class="scope-btn" data-scope="internasional">Internasional</button>
                        <button type="button" class="scope-btn" data-scope="semua">Semua</button>
                    </div>

                    <div class="search-row">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari daerah/judul pelatihan">
                        </div>
                        <button type="button" class="btn-filter" id="filterToggleBtn">
                            <i class="bi bi-sliders"></i> Filter
                        </button>
                    </div>

                    <div class="filter-popup" id="filterDropdown" style="display:none;">
                        <div class="filter-popup-header">
                            <span>Filter</span>
                            <button type="button" class="filter-reset-link" id="filterResetLink">Reset Semua</button>
                        </div>
                        <div class="filter-popup-body">
                            <div class="accordion-list">
                                <div class="acc-item" data-acc="tag">
                                    <button type="button" class="acc-header">
                                        <svg class="acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 12 22l-9-9V4a1 1 0 0 1 1-1h9l9 9a2 2 0 0 1 0 2.41Z"></path><circle cx="8" cy="7.5" r="1.2" fill="currentColor" stroke="none"></circle></svg>
                                        <span class="acc-label">Tag</span>
                                        <span class="acc-count" id="tagCount" style="display:none;"></span>
                                        <svg class="acc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div class="acc-panel">
                                        <input type="text" class="ms-search-input" id="tagSearchInput" placeholder="Cari tag..." autocomplete="off">
                                        <div class="ms-options" id="tagOptions"></div>
                                    </div>
                                </div>
                                <div class="acc-item" data-acc="departemen">
                                    <button type="button" class="acc-header">
                                        <svg class="acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"></path><path d="M2 22h20"></path><path d="M10 7h4M10 11h4M10 15h4"></path></svg>
                                        <span class="acc-label">Departemen</span>
                                        <span class="acc-count" id="departemenCount" style="display:none;"></span>
                                        <svg class="acc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div class="acc-panel">
                                        <div class="ms-options" id="departemenOptions"></div>
                                    </div>
                                </div>
                                <div class="acc-item" data-acc="status">
                                    <button type="button" class="acc-header">
                                        <svg class="acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                                        <span class="acc-label">Status</span>
                                        <span class="acc-count" id="statusCount" style="display:none;"></span>
                                        <svg class="acc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div class="acc-panel">
                                        <div class="ms-options" id="statusOptions"></div>
                                    </div>
                                </div>
                                <div class="acc-item" data-acc="tahun">
                                    <button type="button" class="acc-header">
                                        <svg class="acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <span class="acc-label">Tahun</span>
                                        <span class="acc-count" id="tahunCount" style="display:none;"></span>
                                        <svg class="acc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div class="acc-panel">
                                        <select id="filterTahun" autocomplete="off"><option value="">Semua Tahun</option></select>
                                    </div>
                                </div>
                                <div class="acc-item" data-acc="bulan" id="filterBulanGroup" style="display:none;">
                                    <button type="button" class="acc-header">
                                        <svg class="acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><circle cx="12" cy="15" r="1.5" fill="currentColor" stroke="none"></circle></svg>
                                        <span class="acc-label">Bulan</span>
                                        <span class="acc-count" id="bulanCount" style="display:none;"></span>
                                        <svg class="acc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div class="acc-panel">
                                        <select id="filterBulan" autocomplete="off"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="filter-popup-footer">
                            <button type="button" class="btn-filter-close" id="filterPopupCloseBtn">Tutup</button>
                            <button type="button" class="btn-apply-filter" id="applyFilterBtn">Terapkan Filter</button>
                        </div>
                    </div>

                    <div class="active-chips" id="activeChips"></div>

                    <div class="sort-row">
                        <span id="resultCount">Ditemukan 0 data</span>
                    </div>
                </div>

                <div class="card-list" id="cardList">
                    <div class="empty-state">Memuat data...</div>
                </div>

                <div class="pagination" id="pagination"></div>
            </div>
        </aside>

        <!-- ---------------- AREA PETA ---------------- -->
        <div class="map-area">
            <div id="leafletMap"></div>

            <div class="map-legend" id="mapLegend">
                <div class="legend-header">
                    <span>Indikator</span>
                    <button type="button" id="legendToggleBtn" title="Minimize"><i class="bi bi-dash-lg"></i></button>
                </div>
                <div class="legend-body" id="legendBody">
                    <div><span class="dot dot-selesai"></span>Kegiatan Selesai</div>
                    <div><span class="dot dot-berlangsung"></span>Sedang Berlangsung</div>
                    <div><span class="dot dot-akan-datang"></span>Akan Datang</div>
                </div>
            </div>
        </div>
    </div>


    <!-- ================= SURVEY POPUP (hanya muncul kunjungan pertama) ================= -->
    <?php if ($perluSurvey): ?>
    <div class="survey-overlay" id="surveyOverlay">
        <div class="survey-card">
            <div class="brand-mini">SEAMEO BIOTROP · Activities in Spatial</div>
            <h3>Selamat Datang</h3>
            <p class="lead-text">Sebelum melihat peta, bantu kami mengenal Anda lewat survey singkat berikut.</p>

            <?php if ($errorSurvey): ?>
                <div class="survey-error"><?= htmlspecialchars($errorSurvey) ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="surveyForm" novalidate>
                <!-- Profesi -->
                <div class="survey-group">
                    <label class="survey-label">Profesi</label>
                    <div class="survey-options">
                        <?php foreach (['Pelajar', 'Guru', 'Dosen', 'Peneliti', 'Umum'] as $opt): ?>
                            <input type="radio" name="profesi" id="profesi_<?= $opt ?>" value="<?= $opt ?>"
                                   <?= $oldInput['profesi'] === $opt ? 'checked' : '' ?> required>
                            <label class="opt" for="profesi_<?= $opt ?>"><?= $opt ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Institusi (kondisional: Pelajar / Guru / Dosen) -->
                <div class="survey-group" id="institusiWrap" style="display:none;">
                    <label class="survey-label" id="institusiLabel" for="institusiInput">Institusi</label>
                    <input type="text" name="institusi" id="institusiInput" class="form-control-custom"
                           placeholder="Nama sekolah/universitas/institusi"
                           value="<?= htmlspecialchars($oldInput['institusi']) ?>">
                </div>

                <!-- Gender -->
                <div class="survey-group">
                    <label class="survey-label">Gender</label>
                    <div class="survey-options">
                        <?php foreach (['Laki-laki', 'Perempuan'] as $opt): ?>
                            <input type="radio" name="gender" id="gender_<?= $opt ?>" value="<?= $opt ?>"
                                   <?= $oldInput['gender'] === $opt ? 'checked' : '' ?> required>
                            <label class="opt" for="gender_<?= $opt ?>"><?= $opt ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Asal Daerah -->
                <div class="survey-group">
                    <label class="survey-label">Asal Daerah</label>
                    <div class="survey-options">
                        <?php foreach (['Nasional', 'Internasional'] as $opt): ?>
                            <input type="radio" name="asal_daerah" id="asal_<?= $opt ?>" value="<?= $opt ?>"
                                   <?= $oldInput['asal_daerah'] === $opt ? 'checked' : '' ?> required>
                            <label class="opt" for="asal_<?= $opt ?>"><?= $opt ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Provinsi (kondisional: Nasional) - custom searchable dropdown -->
                <div class="survey-group" id="provinsiWrap" style="display:none;">
                    <label class="survey-label" for="provinsiSearchInput">Provinsi</label>
                    <div class="combo-wrap">
                        <input type="text" id="provinsiSearchInput" class="form-control-custom"
                               placeholder="Cari provinsi..." autocomplete="off"
                               value="<?= htmlspecialchars($oldInput['provinsi']) ?>">
                        <input type="hidden" name="provinsi" id="provinsiHidden" value="<?= htmlspecialchars($oldInput['provinsi']) ?>">
                        <div class="combo-dropdown" id="provinsiDropdown"></div>
                    </div>
                </div>

                <!-- Negara (kondisional: Internasional) -->
                <div class="survey-group" id="negaraWrap" style="display:none;">
                    <label class="survey-label" for="negaraInput">Negara</label>
                    <input type="text" name="negara" id="negaraInput" class="form-control-custom"
                           placeholder="Nama negara" value="<?= htmlspecialchars($oldInput['negara']) ?>">
                </div>

                <button type="submit" name="submit_survey" value="1" class="btn-survey-submit">Lanjut ke Peta</button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var profesiRadios   = document.querySelectorAll('input[name="profesi"]');
            var institusiWrap   = document.getElementById('institusiWrap');
            var institusiLabel  = document.getElementById('institusiLabel');
            var institusiInput  = document.getElementById('institusiInput');

            var asalRadios      = document.querySelectorAll('input[name="asal_daerah"]');
            var provinsiWrap    = document.getElementById('provinsiWrap');
            var negaraWrap      = document.getElementById('negaraWrap');
            var negaraInput     = document.getElementById('negaraInput');

            var provinsiSearch  = document.getElementById('provinsiSearchInput');
            var provinsiHidden  = document.getElementById('provinsiHidden');
            var provinsiDropdown= document.getElementById('provinsiDropdown');
            var comboWrap       = provinsiSearch.closest('.combo-wrap');

            var provinsiData = [];
            var provinsiLoaded = false;

            function loadProvinsi() {
                if (provinsiLoaded) return;
                provinsiLoaded = true;
                fetch('../api/get_provinsi_publik.php')
                    .then(function (res) { return res.json(); })
                    .then(function (data) { provinsiData = Array.isArray(data) ? data : []; })
                    .catch(function () { provinsiData = []; });
            }

            function renderDropdown(list) {
                provinsiDropdown.innerHTML = '';
                if (list.length === 0) {
                    var empty = document.createElement('div');
                    empty.className = 'combo-empty';
                    empty.textContent = 'Provinsi tidak ditemukan.';
                    provinsiDropdown.appendChild(empty);
                    provinsiDropdown.style.display = 'block';
                    return;
                }
                list.forEach(function (p) {
                    var item = document.createElement('div');
                    item.className = 'combo-item';
                    item.textContent = p.nama;
                    item.addEventListener('click', function () {
                        provinsiSearch.value = p.nama;
                        provinsiHidden.value = p.nama;
                        provinsiSearch.setCustomValidity('');
                        provinsiDropdown.style.display = 'none';
                    });
                    provinsiDropdown.appendChild(item);
                });
                provinsiDropdown.style.display = 'block';
            }

            provinsiSearch.addEventListener('focus', function () {
                loadProvinsi();
                renderDropdown(provinsiData);
            });

            provinsiSearch.addEventListener('input', function () {
                provinsiHidden.value = '';
                provinsiSearch.setCustomValidity('');
                var q = this.value.toLowerCase();
                var filtered = provinsiData.filter(function (p) {
                    return p.nama.toLowerCase().indexOf(q) !== -1;
                });
                renderDropdown(filtered);
            });

            document.addEventListener('click', function (e) {
                if (!comboWrap.contains(e.target)) {
                    provinsiDropdown.style.display = 'none';
                }
            });

            function updateProfesiField() {
                var checked = document.querySelector('input[name="profesi"]:checked');
                var val = checked ? checked.value : '';

                if (val === 'Pelajar') {
                    institusiWrap.style.display = 'block';
                    institusiLabel.textContent = 'Sekolah/Universitas';
                    institusiInput.placeholder = 'Nama sekolah/universitas';
                } else if (val === 'Guru' || val === 'Dosen') {
                    institusiWrap.style.display = 'block';
                    institusiLabel.textContent = 'Institusi';
                    institusiInput.placeholder = 'Nama institusi';
                } else {
                    institusiWrap.style.display = 'none';
                    institusiInput.value = '';
                }
            }

            function updateAsalField() {
                var checked = document.querySelector('input[name="asal_daerah"]:checked');
                var val = checked ? checked.value : '';

                if (val === 'Nasional') {
                    provinsiWrap.style.display = 'block';
                    negaraWrap.style.display = 'none';
                    negaraInput.value = '';
                } else if (val === 'Internasional') {
                    provinsiWrap.style.display = 'none';
                    negaraWrap.style.display = 'block';
                    provinsiSearch.value = '';
                    provinsiHidden.value = '';
                } else {
                    provinsiWrap.style.display = 'none';
                    negaraWrap.style.display = 'none';
                }
            }

            profesiRadios.forEach(function (r) { r.addEventListener('change', updateProfesiField); });
            asalRadios.forEach(function (r) { r.addEventListener('change', updateAsalField); });

            updateProfesiField();
            updateAsalField();

            document.getElementById('surveyForm').addEventListener('submit', function (e) {
                var asalChecked = document.querySelector('input[name="asal_daerah"]:checked');
                var asalVal = asalChecked ? asalChecked.value : '';

                if (asalVal === 'Nasional' && !provinsiHidden.value) {
                    e.preventDefault();
                    provinsiSearch.setCustomValidity('Pilih salah satu provinsi dari daftar.');
                    provinsiSearch.reportValidity();
                }
            });
        })();
    </script>
    <?php endif; ?>

    <script>window.SUDAH_LOGIN = <?= json_encode($sudahLogin) ?>;</script>
    <script src="../assets/js/toast.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            var currentPage     = 1;
            var currentScope    = 'nasional';
            var currentQ        = '';
            var currentTag         = []; // array nama_tag, multi-select
            var currentDepartemen  = []; // array kode departemen, multi-select
            var currentTahun       = '';
            var currentBulan       = ''; // format YYYY-MM, dari deep-link dashboard admin
            var currentStatus      = []; // array belum_berlangsung|berlangsung|selesai, multi-select
            var currentLokasiId = 0;
            var currentLokasiNama = '';

            var mapInstance, markerLayerGroup;
            var semuaLokasiData = []; // data mentah dari server, di-render ulang tiap ganti tier zoom
            var activeTab       = 'peta';

            var panelWide        = document.getElementById('panelWide');
            var panelTitle        = document.getElementById('panelTitle');
            var panelCollapseBtn  = document.getElementById('panelCollapseBtn');
            var railBtns          = document.querySelectorAll('.rail-btn[data-tab]');
            var legendToggleBtn  = document.getElementById('legendToggleBtn');
            var legendBody       = document.getElementById('legendBody');

            function escapeHtml(str) {
                var d = document.createElement('div');
                d.textContent = str == null ? '' : str;
                return d.innerHTML;
            }

            var NAMA_BULAN_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            function labelBulan(yyyyMm) {
                var parts = (yyyyMm || '').split('-');
                if (parts.length !== 2) return yyyyMm;
                var idx = parseInt(parts[1], 10) - 1;
                return (NAMA_BULAN_ID[idx] || parts[1]) + ' ' + parts[0];
            }

            function isiOpsiBulan() {
                var sel = document.getElementById('filterBulan');
                sel.innerHTML = '';
                var opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = 'Semua Bulan';
                sel.appendChild(opt0);
                NAMA_BULAN_ID.forEach(function (nama, i) {
                    var opt = document.createElement('option');
                    opt.value = String(i + 1).padStart(2, '0');
                    opt.textContent = nama;
                    sel.appendChild(opt);
                });
            }

            // Filter Bulan cuma masuk akal kalau Tahun sudah dipilih (butuh konteks tahun) --
            // disembunyikan (bukan di-disable/dikunci) selama belum ada tahun yang dipilih.
            function syncFilterBulanState() {
                var tahunSelect = document.getElementById('filterTahun');
                var bulanGroup  = document.getElementById('filterBulanGroup');
                if (tahunSelect.value) {
                    if (!bulanGroup.dataset.filled) {
                        isiOpsiBulan();
                        bulanGroup.dataset.filled = '1';
                    }
                    bulanGroup.style.display = '';
                } else {
                    bulanGroup.style.display = 'none';
                    document.getElementById('filterBulan').value = '';
                }
            }
            document.getElementById('filterTahun').addEventListener('change', function () {
                syncFilterBulanState();
                if (document.getElementById('filterDropdown').style.display !== 'none') posisikanFilterPopup();
            });

            function fetchJSON(url) {
                return fetch(url).then(function (res) { return res.json(); });
            }

            // ---------------- Accordion filter (buka satu section per waktu) ----------------
            function renderAccCount(elId, arr) {
                var el = document.getElementById(elId);
                if (!el) return;
                if (arr.length > 0) { el.textContent = String(arr.length); el.style.display = 'inline-block'; }
                else { el.style.display = 'none'; }
            }

            document.querySelectorAll('#filterDropdown .acc-header').forEach(function (header) {
                header.addEventListener('click', function () {
                    var item = header.closest('.acc-item');
                    var sudahTerbuka = item.classList.contains('open');
                    document.querySelectorAll('#filterDropdown .acc-item.open').forEach(function (el) { el.classList.remove('open'); });
                    if (!sudahTerbuka) {
                        item.classList.add('open');
                        if (item.dataset.acc === 'tag' && !item.dataset.loaded) {
                            item.dataset.loaded = '1';
                            document.getElementById('tagSearchInput').value = '';
                            cariTag('');
                        }
                    }
                    if (document.getElementById('filterDropdown').style.display !== 'none') posisikanFilterPopup();
                });
            });

            // Dipakai buat Departemen & Status: daftar opsi tetap/kecil, langsung ditampilkan semua.
            function buatMultiCheckGroup(optionsId, countElId, opsi, terpilihArr) {
                var optionsEl = document.getElementById(optionsId);

                function render() {
                    optionsEl.innerHTML = opsi.map(function (o) {
                        var checked = terpilihArr.indexOf(o.value) > -1 ? 'checked' : '';
                        return '<label class="ms-option"><input type="checkbox" value="' + escapeHtml(o.value) + '" ' + checked + '> ' + escapeHtml(o.label) + '</label>';
                    }).join('');
                    renderAccCount(countElId, terpilihArr);
                }

                optionsEl.addEventListener('change', function (e) {
                    if (e.target.type !== 'checkbox') return;
                    var v = e.target.value;
                    var idx = terpilihArr.indexOf(v);
                    if (e.target.checked && idx === -1) terpilihArr.push(v);
                    if (!e.target.checked && idx > -1) terpilihArr.splice(idx, 1);
                    render();
                });

                render();
                return { render: render };
            }

            var OPSI_STATUS = [
                { value: 'berlangsung', label: 'Berlangsung' },
                { value: 'selesai', label: 'Selesai' },
                { value: 'belum_berlangsung', label: 'Akan Datang' },
            ];
            var msStatus = buatMultiCheckGroup('statusOptions', 'statusCount', OPSI_STATUS, currentStatus);
            var msDepartemen = null; // diinisialisasi setelah opsi departemen dimuat dari server

            // ---------------- Combobox Tag (cari on-demand, gak narik semua tag) ----------------
            var tagSearchTimer = null;

            function renderTagOptions(hasil) {
                var optionsEl = document.getElementById('tagOptions');
                // Tag yang sudah dipilih selalu ditampilkan duluan (checked), walau gak nyangkut di hasil pencarian saat ini.
                var gabungan = currentTag.map(function (t) { return { nama_tag: t }; });
                (hasil || []).forEach(function (item) {
                    if (currentTag.indexOf(item.nama_tag) === -1) gabungan.push(item);
                });

                if (gabungan.length === 0) {
                    optionsEl.innerHTML = '<div class="ms-empty">Tag tidak ditemukan</div>';
                    return;
                }
                optionsEl.innerHTML = gabungan.map(function (item) {
                    var checked = currentTag.indexOf(item.nama_tag) > -1 ? 'checked' : '';
                    return '<label class="ms-option"><input type="checkbox" value="' + escapeHtml(item.nama_tag) + '" ' + checked + '> ' + escapeHtml(item.nama_tag) + '</label>';
                }).join('');
            }

            function cariTag(q) {
                fetchJSON('../api/get_kegiatan_publik.php?action=cari_tag&q=' + encodeURIComponent(q)).then(function (res) {
                    renderTagOptions(res.success ? res.data : []);
                });
            }

            document.getElementById('tagSearchInput').addEventListener('input', function (e) {
                clearTimeout(tagSearchTimer);
                var q = e.target.value;
                tagSearchTimer = setTimeout(function () { cariTag(q); }, 250);
            });

            document.getElementById('tagOptions').addEventListener('change', function (e) {
                if (e.target.type !== 'checkbox') return;
                var v = e.target.value;
                var idx = currentTag.indexOf(v);
                if (e.target.checked && idx === -1) currentTag.push(v);
                if (!e.target.checked && idx > -1) currentTag.splice(idx, 1);
                renderAccCount('tagCount', currentTag);
            });

            renderAccCount('tagCount', currentTag);

            // ---------------- Reset Semua (di header popup, sebelum klik Terapkan) ----------------
            document.getElementById('filterResetLink').addEventListener('click', function () {
                currentTag.length = 0;
                currentDepartemen.length = 0;
                currentStatus.length = 0;
                document.getElementById('filterTahun').value = '';
                document.getElementById('filterBulan').value = '';
                syncFilterBulanState();
                renderAccCount('tagCount', currentTag);
                document.getElementById('tagSearchInput').value = '';
                cariTag('');
                msStatus.render();
                if (msDepartemen) msDepartemen.render();
                if (document.getElementById('filterDropdown').style.display !== 'none') posisikanFilterPopup();
            });

            // ---------------- Filter options (Departemen & Tahun) ----------------
            function loadFilterOptions() {
                return fetchJSON('../api/get_kegiatan_publik.php?action=filter_options').then(function (res) {
                    if (!res.success) return;

                    var opsiDepartemen = (res.departemen || []).map(function (d) {
                        return { value: d.departemen_kode, label: d.departemen_kode + ' (' + d.jumlah + ')' };
                    });
                    msDepartemen = buatMultiCheckGroup('departemenOptions', 'departemenCount', opsiDepartemen, currentDepartemen);

                    var tahunSelect = document.getElementById('filterTahun');
                    res.tahun.forEach(function (y) {
                        var opt = document.createElement('option');
                        opt.value = y;
                        opt.textContent = y;
                        tahunSelect.appendChild(opt);
                    });
                });
            }

            // ---------------- Chip filter aktif ----------------
            function renderChips() {
                var wrap = document.getElementById('activeChips');
                wrap.innerHTML = '';
                var chips = [];

                currentTag.forEach(function (t) {
                    chips.push({ label: t, clear: function () {
                        var idx = currentTag.indexOf(t);
                        if (idx > -1) currentTag.splice(idx, 1);
                        renderAccCount('tagCount', currentTag);
                        currentPage = 1;
                        loadList();
                        loadMarkers();
                        renderChips();
                    }});
                });
                currentDepartemen.forEach(function (d) {
                    chips.push({ label: d, clear: function () {
                        var idx = currentDepartemen.indexOf(d);
                        if (idx > -1) currentDepartemen.splice(idx, 1);
                        if (msDepartemen) msDepartemen.render();
                        currentPage = 1;
                        loadList();
                        loadMarkers();
                        renderChips();
                    }});
                });
                if (currentTahun) {
                    chips.push({ label: currentTahun, clear: function () {
                        currentTahun = '';
                        document.getElementById('filterTahun').value = '';
                        syncFilterBulanState();
                        currentPage = 1;
                        loadList();
                        loadMarkers();
                        renderChips();
                    }});
                }
                if (currentBulan) {
                    chips.push({ label: labelBulan(currentBulan), clear: function () {
                        currentBulan = '';
                        document.getElementById('filterBulan').value = '';
                        currentPage = 1;
                        loadList();
                        loadMarkers();
                        renderChips();
                    }});
                }
                currentStatus.forEach(function (s) {
                    chips.push({ label: statusLabel(s), clear: function () {
                        var idx = currentStatus.indexOf(s);
                        if (idx > -1) currentStatus.splice(idx, 1);
                        msStatus.render();
                        currentPage = 1;
                        loadList();
                        loadMarkers();
                        renderChips();
                    }});
                });
                if (currentLokasiId) {
                    chips.push({ label: 'Lokasi terpilih: ' + (currentLokasiNama || '-'), clear: function () {
                        currentLokasiId = 0;
                        currentLokasiNama = '';
                        currentPage = 1;
                        loadList();
                        renderChips();
                    }});
                }

                if (chips.length === 0) { wrap.style.display = 'none'; return; }
                wrap.style.display = 'flex';

                chips.forEach(function (c) {
                    var el = document.createElement('span');
                    el.className = 'chip';
                    el.innerHTML = escapeHtml(c.label) + ' <button type="button">&times;</button>';
                    el.querySelector('button').addEventListener('click', c.clear);
                    wrap.appendChild(el);
                });

                var clearAll = document.createElement('button');
                clearAll.type = 'button';
                clearAll.className = 'clear-filter-link';
                clearAll.textContent = 'Hapus filter';
                clearAll.addEventListener('click', function () {
                    currentTag.length = 0;
                    currentDepartemen.length = 0;
                    currentTahun = '';
                    currentBulan = '';
                    currentStatus.length = 0;
                    currentLokasiId = 0;
                    currentLokasiNama = '';
                    renderAccCount('tagCount', currentTag);
                    if (msDepartemen) msDepartemen.render();
                    msStatus.render();
                    document.getElementById('filterTahun').value = '';
                    document.getElementById('filterBulan').value = '';
                    syncFilterBulanState();
                    currentPage = 1;
                    loadList();
                    loadMarkers();
                    renderChips();
                });
                wrap.appendChild(clearAll);
            }

            // ---------------- Kartu kegiatan ----------------
            function statusLabel(status) {
                return { selesai: 'Selesai', berlangsung: 'Berlangsung', belum_berlangsung: 'Akan Datang' }[status] || status;
            }

            function formatTanggal(dateStr) {
                var d = new Date(dateStr);
                if (isNaN(d)) return dateStr;
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            }

            function renderCard(item) {
                var tags = (item.tags || []).map(function (t) {
                    return '<span class="tag-pill">' + escapeHtml(t) + '</span>';
                }).join('');

                return '' +
                '<div class="kegiatan-card" data-lokasi-id="' + item.location_id + '" ' +
                     'data-lat="' + escapeHtml(item.latitude) + '" data-lng="' + escapeHtml(item.longitude) + '">' +
                    '<div class="card-top-row">' +
                        '<span class="badge-kode">' + escapeHtml(item.kode_kegiatan) + '</span>' +
                        '<span class="badge-status ' + item.status + '">' + statusLabel(item.status) + '</span>' +
                        '<span class="card-date"><i class="bi bi-calendar3"></i> ' + formatTanggal(item.tanggal_mulai) + '</span>' +
                    '</div>' +
                    '<div class="card-peserta"><i class="bi bi-people-fill"></i> ' + (item.jumlah_peserta || 0) + ' Peserta</div>' +
                    '<div class="card-title">' + escapeHtml(item.judul) + '</div>' +
                    '<div class="card-tags">' + tags + '</div>' +
                    '<div class="card-meta">' + escapeHtml(item.penyelenggara || '-') + '<br>' +
                        escapeHtml(item.kabupaten_kota) + ', ' + escapeHtml(item.provinsi) +
                    '</div>' +
                    '<button type="button" class="btn-detail" data-id="' + item.id + '">Lihat Detail Kegiatan</button>' +
                '</div>';
            }

            function handleDetailClick(e) {
                e.stopPropagation(); // jangan sampai ikut memicu klik kartu (zoom peta)
                var detailUrl = 'detail_kegiatan.php?id=' + this.dataset.id;
                if (!window.SUDAH_LOGIN) {
                    window.location.href = '../auth/login.php?next=' + encodeURIComponent('../user/' + detailUrl);
                    return;
                }
                window.location.href = detailUrl;
            }

            // Feedback mentor: klik kartu kegiatan -> peta zoom ke lokasinya.
            function handleCardClick(e) {
                var lat = parseFloat(this.dataset.lat);
                var lng = parseFloat(this.dataset.lng);
                var lokasiId = parseInt(this.dataset.lokasiId, 10);
                if (isNaN(lat) || isNaN(lng)) return;
                focusLocation(lokasiId, lat, lng);
            }

            // Kumpulan filter aktif saat ini (dipakai bareng oleh loadList & loadMarkers,
            // supaya card di sidebar dan node/marker di peta selalu konsisten).
            function buildFilterParams() {
                var params = new URLSearchParams({ scope: currentScope });
                if (currentQ) params.set('q', currentQ);
                if (currentTag.length) params.set('tag', currentTag.join(','));
                if (currentDepartemen.length) params.set('departemen', currentDepartemen.join(','));
                if (currentTahun) params.set('tahun', currentTahun);
                if (currentBulan) params.set('bulan', currentBulan);
                if (currentStatus.length) params.set('status', currentStatus.join(','));
                return params;
            }

            function loadList() {
                var params = buildFilterParams();
                params.set('action', 'list');
                params.set('page', currentPage);
                if (currentLokasiId) params.set('lokasi_id', currentLokasiId);

                var listEl = document.getElementById('cardList');
                listEl.innerHTML = '<div class="empty-state">Memuat data...</div>';

                fetchJSON('../api/get_kegiatan_publik.php?' + params.toString()).then(function (res) {
                    if (!res.success) {
                        listEl.innerHTML = '<div class="empty-state">Gagal memuat data.</div>';
                        return;
                    }

                    document.getElementById('resultCount').textContent = 'Ditemukan ' + res.total + ' data';

                    if (res.data.length === 0) {
                        listEl.innerHTML = '<div class="empty-state">Tidak ada kegiatan yang cocok dengan pencarian/filter ini.</div>';
                    } else {
                        listEl.innerHTML = res.data.map(renderCard).join('');
                        listEl.querySelectorAll('.btn-detail').forEach(function (btn) {
                            btn.addEventListener('click', handleDetailClick);
                        });
                        listEl.querySelectorAll('.kegiatan-card').forEach(function (card) {
                            card.addEventListener('click', handleCardClick);
                        });
                    }

                    renderPagination(res.page, res.total_pages);
                });
            }

            function renderPagination(page, totalPages) {
                var wrap = document.getElementById('pagination');
                wrap.innerHTML = '';
                if (totalPages <= 1) return;

                function makeBtn(label, targetPage, opts) {
                    opts = opts || {};
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'page-btn' + (opts.active ? ' active' : '');
                    b.textContent = label;
                    b.disabled = !!opts.disabled;
                    b.addEventListener('click', function () { currentPage = targetPage; loadList(); });
                    return b;
                }

                wrap.appendChild(makeBtn('\u2039', Math.max(1, page - 1), { disabled: page === 1 }));

                var pagesSet = {};
                [1, totalPages, page, page - 1, page + 1].forEach(function (p) {
                    if (p >= 1 && p <= totalPages) pagesSet[p] = true;
                });
                var sorted = Object.keys(pagesSet).map(Number).sort(function (a, b) { return a - b; });

                var prev = 0;
                sorted.forEach(function (p) {
                    if (prev && p - prev > 1) {
                        var dots = document.createElement('span');
                        dots.className = 'page-dots';
                        dots.textContent = '...';
                        wrap.appendChild(dots);
                    }
                    wrap.appendChild(makeBtn(String(p), p, { active: p === page }));
                    prev = p;
                });

                wrap.appendChild(makeBtn('\u203a', Math.min(totalPages, page + 1), { disabled: page === totalPages }));
            }

            // ---------------- Search & filter events ----------------
            var searchDebounce;
            document.getElementById('searchInput').addEventListener('input', function () {
                var val = this.value;
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function () {
                    currentQ = val.trim();
                    currentPage = 1;
                    loadList();
                    loadMarkers();
                }, 400);
            });

            function posisikanFilterPopup() {
                var btn = document.getElementById('filterToggleBtn');
                var popup = document.getElementById('filterDropdown');
                var rect = btn.getBoundingClientRect();
                var gap = 8;
                var marginLayar = 16;

                // Rata kiri ke tombol Filter, boleh melebar ke luar sidebar/menutupi peta.
                var lebarPopup = popup.offsetWidth || 460;
                var left = rect.left;
                var maxLeft = window.innerWidth - lebarPopup - 12;
                if (left > maxLeft) left = maxLeft;
                if (left < 12) left = 12;
                popup.style.left = left + 'px';

                // Tinggi popup dibatasi sesuai SISA RUANG LAYAR (bukan persentase viewport
                // statis) -- supaya gak kepotong walau tombolnya ada di posisi manapun.
                var ruangBawah = window.innerHeight - rect.bottom - gap - marginLayar;
                var ruangAtas = rect.top - gap - marginLayar;

                if (ruangBawah >= 220 || ruangBawah >= ruangAtas) {
                    // Buka ke bawah tombol.
                    popup.style.top = (rect.bottom + gap) + 'px';
                    popup.style.maxHeight = Math.max(ruangBawah, 160) + 'px';
                } else {
                    // Ruang bawah kurang -> buka ke atas tombol.
                    popup.style.maxHeight = Math.max(ruangAtas, 160) + 'px';
                    var tinggiPopup = Math.min(popup.scrollHeight, ruangAtas);
                    popup.style.top = (rect.top - gap - tinggiPopup) + 'px';
                }
            }

            function bukaFilterPopup() {
                var popup = document.getElementById('filterDropdown');
                popup.style.display = 'flex';
                posisikanFilterPopup();
            }
            function tutupFilterPopup() {
                document.getElementById('filterDropdown').style.display = 'none';
            }

            document.getElementById('filterToggleBtn').addEventListener('click', function (e) {
                e.stopPropagation();
                var sedangTerbuka = document.getElementById('filterDropdown').style.display !== 'none';
                if (sedangTerbuka) tutupFilterPopup(); else bukaFilterPopup();
            });
            document.getElementById('filterPopupCloseBtn').addEventListener('click', tutupFilterPopup);

            // Klik di luar popup (dan bukan di tombol Filter) -> tutup, kayak dropdown biasa.
            document.addEventListener('click', function (e) {
                var popup = document.getElementById('filterDropdown');
                var btn = document.getElementById('filterToggleBtn');
                if (popup.style.display === 'none') return;
                if (popup.contains(e.target) || btn.contains(e.target)) return;
                tutupFilterPopup();
            });
            document.getElementById('filterDropdown').addEventListener('click', function (e) {
                e.stopPropagation();
            });
            window.addEventListener('resize', function () {
                if (document.getElementById('filterDropdown').style.display !== 'none') posisikanFilterPopup();
            });

            document.getElementById('applyFilterBtn').addEventListener('click', function () {
                // currentTag / currentDepartemen / currentStatus sudah ke-update langsung
                // tiap checkbox dicentang/dilepas (lihat komponen multi-select di atas).
                currentTahun = document.getElementById('filterTahun').value;

                var bulanDipilih = document.getElementById('filterBulan').value;
                // Bulan spesifik cuma valid kalau ada tahunnya -- kirim sebagai satu filter "bulan"
                // (YYYY-MM) dan lepas filter "tahun" biar gak dobel/ambigu di backend.
                if (currentTahun && bulanDipilih) {
                    currentBulan = currentTahun + '-' + bulanDipilih;
                    currentTahun = '';
                } else {
                    currentBulan = '';
                }

                currentPage = 1;
                tutupFilterPopup();
                loadList();
                loadMarkers();
                renderChips();
            });

            // ---------------- Scope: Nasional / Internasional / Semua ----------------
            // Feedback mentor: split biar query peta gak langsung narik semua data sekaligus.
            var scopeViews = {
                nasional: { center: [-2.5, 118], zoom: 5 },
                // Internasional: zoom ke kawasan ASEAN aja, bukan seluruh dunia.
                internasional: { center: [8, 112], zoom: 4 },
                semua: { center: [10, 60], zoom: 2 }
            };

            document.querySelectorAll('.scope-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (btn.classList.contains('active')) return;
                    document.querySelectorAll('.scope-btn').forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');

                    currentScope = btn.dataset.scope;
                    currentPage = 1;
                    currentLokasiId = 0; // lokasi terpilih sebelumnya belum tentu ada di scope baru
                    currentLokasiNama = '';

                    var view = scopeViews[currentScope];
                    if (mapInstance && view) mapInstance.setView(view.center, view.zoom);

                    loadList();
                    loadMarkers();
                    renderChips();
                });
            });

            // ---------------- Rail & panel ----------------
            // Cuma tersisa 1 menu (Peta) sejak panel Dashboard dipindah ke admin/dashboard.php.
            // Fungsi ini tetap dipertahankan (bukan dihapus total) buat jaga-jaga kalau nanti
            // ada menu lain ditambah lagi di rail, jadi gak perlu bikin ulang dari nol.
            var panelTitles = { peta: 'Peta Kegiatan' };

            function openPanel(tab) {
                activeTab = tab;
                panelWide.classList.remove('collapsed');

                railBtns.forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.tab === tab);
                });

                document.getElementById('panelPeta').style.display = tab === 'peta' ? 'flex' : 'none';
                panelTitle.textContent = panelTitles[tab] || '';

                setTimeout(function () { if (mapInstance) mapInstance.invalidateSize(); }, 260);
            }

            function collapsePanel() {
                panelWide.classList.add('collapsed');
                // Lepas highlight ikon aktif pas panel ketutup, biar gak kelihatan kayak nge-lag/nyangkut.
                railBtns.forEach(function (btn) { btn.classList.remove('active'); });
                setTimeout(function () { if (mapInstance) mapInstance.invalidateSize(); }, 260);
            }

            railBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tab = btn.dataset.tab;
                    // Klik ikon yang lagi aktif & panel lagi kebuka -> collapse (kayak activity bar VSCode).
                    if (!panelWide.classList.contains('collapsed') && activeTab === tab) {
                        collapsePanel();
                    } else {
                        openPanel(tab);
                    }
                });
            });

            panelCollapseBtn.addEventListener('click', collapsePanel);

            // ---------------- Toggle legend (feedback mentor: bisa di-minimize) ----------------
            legendToggleBtn.addEventListener('click', function () {
                var collapsed = legendBody.classList.toggle('collapsed');
                legendToggleBtn.innerHTML = collapsed
                    ? '<i class="bi bi-plus-lg"></i>'
                    : '<i class="bi bi-dash-lg"></i>';
            });

            // ---------------- Peta (Leaflet) ----------------
            var markersByLocationId = {};

            function initMap() {
                // Feedback mentor: default fullscreen cakupan Indonesia, bukan zoom ke satu kota.
                mapInstance = L.map('leafletMap', {
                    zoomControl: true,
                    zoomSnap: 1,
                    zoomDelta: 1,
                    wheelPxPerZoomLevel: 120
                }).setView([-2.5, 118], 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(mapInstance);

                markerLayerGroup = L.layerGroup().addTo(mapInstance);

                // PENTING: pengelompokan node di sini SENGAJA dihitung dari jarak asli
                // (km, rumus Haversine) per tier zoom yang tetap -- BUKAN dari radius
                // piksel layar seperti library clustering pada umumnya. Alasannya: radius
                // piksel berubah tiap level zoom (bahkan tiap sedikit pergeseran), jadi
                // keanggotaan cluster gampang "lompat" gak konsisten. Dengan jarak asli +
                // tier tetap, hasil pengelompokan 100% deterministik untuk rentang zoom
                // yang sama -- gak mungkin berubah tanpa piksel/pergeseran zoom.
                mapInstance.on('zoomend', renderMarkerUntukZoomSaatIni);
            }

            // Tiap tier berlaku buat rentang zoom tertentu, dengan radius gabungan (km) tetap.
            // Baris terakhir (radiusKm: 0) = gak ada gabungan sama sekali, semua presisi individual.
            var TIER_CLUSTER_JARAK = [
                { maxZoom: 5,  radiusKm: 200 },
                { maxZoom: 7,  radiusKm: 75 },
                { maxZoom: 9,  radiusKm: 25 },
                { maxZoom: 11, radiusKm: 8 },
                { maxZoom: 22, radiusKm: 0 },
            ];
            var ZOOM_MULAI_PRESISI = 12; // tier pertama yang radiusKm-nya 0

            function radiusKmUntukZoom(zoom) {
                for (var i = 0; i < TIER_CLUSTER_JARAK.length; i++) {
                    if (zoom <= TIER_CLUSTER_JARAK[i].maxZoom) return TIER_CLUSTER_JARAK[i].radiusKm;
                }
                return 0;
            }

            // Jarak antar 2 koordinat dalam km (rumus Haversine).
            function jarakKm(lat1, lon1, lat2, lon2) {
                var R = 6371;
                var dLat = (lat2 - lat1) * Math.PI / 180;
                var dLon = (lon2 - lon1) * Math.PI / 180;
                var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            }

            // Pengelompokan greedy sederhana: ambil 1 lokasi jadi basis, tarik semua lokasi
            // lain yang jaraknya <= radiusKm ke grup yang sama, ulangi buat sisanya.
            // Deterministik selama data & radiusKm-nya sama (gak tergantung viewport).
            function kelompokkanBerdasarkanJarak(daftarLokasi, radiusKm) {
                var sisa = daftarLokasi.slice();
                var grup = [];
                while (sisa.length > 0) {
                    var basis = sisa.shift();
                    var anggota = [basis];
                    for (var i = sisa.length - 1; i >= 0; i--) {
                        if (jarakKm(basis.latitude, basis.longitude, sisa[i].latitude, sisa[i].longitude) <= radiusKm) {
                            anggota.push(sisa[i]);
                            sisa.splice(i, 1);
                        }
                    }
                    grup.push(anggota);
                }
                return grup;
            }

            function renderMarkerUntukZoomSaatIni() {
                if (!mapInstance) return;
                markerLayerGroup.clearLayers();
                markersByLocationId = {};

                var zoom = mapInstance.getZoom();
                var radiusKm = radiusKmUntukZoom(zoom);
                var valid = semuaLokasiData.filter(function (loc) { return loc.latitude && loc.longitude; });
                var grupGrup = radiusKm > 0 ? kelompokkanBerdasarkanJarak(valid, radiusKm) : valid.map(function (loc) { return [loc]; });

                // Lapisan koreksi visual: gabungkan grup yang node-nya bakal numpuk/ketiban
                // di layar pada zoom saat ini, walau secara jarak asli mereka gak satu grup
                // (bisa kejadian kalau centroid cluster geser mendekati lokasi tetangga yang
                // jaraknya ke basis dikit di atas radiusKm). Keanggotaan asli per radiusKm
                // gak berubah -- ini murni buat mencegah tabrakan piksel pas render.
                grupGrup = gabungkanTabrakanVisual(grupGrup);

                grupGrup.forEach(function (anggota) {
                    if (anggota.length === 1) {
                        tambahMarkerLokasi(anggota[0]);
                    } else {
                        tambahMarkerCluster(anggota, zoom);
                    }
                });
            }

            // Estimasi radius ikon (px) buat grup berisi N lokasi -- dipakai buat deteksi
            // tabrakan visual. Harus sinkron sama ukuran ikon di tambahMarkerLokasi (34px)
            // dan tambahMarkerCluster (40/46/52px).
            function radiusIkonPx(jumlahAnggota) {
                if (jumlahAnggota === 1) return 17;
                if (jumlahAnggota >= 10) return 26;
                if (jumlahAnggota >= 5) return 23;
                return 20;
            }

            function centroidGrup(anggota) {
                var sumLat = 0, sumLng = 0;
                anggota.forEach(function (loc) {
                    sumLat += Number(loc.latitude);
                    sumLng += Number(loc.longitude);
                });
                return { lat: sumLat / anggota.length, lng: sumLng / anggota.length };
            }

            // Gabungkan grup yang ikonnya akan bertabrakan di layar pada zoom saat ini.
            // Union sederhana yang diulang sampai gak ada tabrakan lagi (merge bisa
            // memperbesar ikon gabungan sehingga berpotensi nyenggol grup lain lagi).
            function gabungkanTabrakanVisual(grupGrup) {
                var grup = grupGrup.map(function (anggota) { return anggota.slice(); });
                var berubah = true;

                while (berubah && grup.length > 1) {
                    berubah = false;

                    var titik = grup.map(function (anggota) {
                        var c = centroidGrup(anggota);
                        return mapInstance.latLngToContainerPoint([c.lat, c.lng]);
                    });

                    outer:
                    for (var i = 0; i < grup.length; i++) {
                        for (var j = i + 1; j < grup.length; j++) {
                            var jarakPx = titik[i].distanceTo(titik[j]);
                            var batasPx = radiusIkonPx(grup[i].length) + radiusIkonPx(grup[j].length);
                            if (jarakPx < batasPx + 4) { // +4px margin biar gak napel persis
                                grup[i] = grup[i].concat(grup[j]);
                                grup.splice(j, 1);
                                berubah = true;
                                break outer;
                            }
                        }
                    }
                }

                return grup;
            }

            function tambahMarkerLokasi(loc) {
                // Dulu: warna marker dipilih berdasarkan prioritas (berlangsung > selesai >
                // akan datang) via statusMarkerClass, jadi 1 lokasi cuma bisa nunjukin 1 warna
                // solid walau kegiatannya campuran status. Sekarang: disamain sama node cluster
                // -- donut proporsional ke komposisi status di lokasi itu, biar kegiatan akan
                // datang tetap kelihatan meskipun ada kegiatan lain yang udah selesai/berlangsung.
                var gradasi = buildGradasiStatus(
                    Number(loc.berlangsung_count) || 0,
                    Number(loc.selesai_count) || 0,
                    Number(loc.akan_datang_count) || 0
                );
                var icon = L.divIcon({
                    className: '',
                    html: '<div class="marker-cluster-custom" style="width:34px;height:34px;background:' + gradasi + '">' +
                              '<div class="marker-cluster-inner">' + loc.total_kegiatan + '</div>' +
                          '</div>',
                    iconSize: [34, 34],
                    iconAnchor: [17, 17]
                });

                var marker = L.marker([loc.latitude, loc.longitude], { icon: icon }).addTo(markerLayerGroup);
                marker.locData = loc;
                markersByLocationId[loc.location_id] = marker;

                var slideInterval = null;
                marker.bindPopup(buildPopupHtml(loc));

                // Lokasi yang sendirian (gak digabung ke cluster manapun) tetap bisa muncul
                // di zoom jauh kalau emang gak ada lokasi lain di sekitarnya. Klik di zoom
                // sejauh itu cuma buka popup pas-pasan tanpa zoom -- biar konsisten sama node
                // cluster (yang langsung zoom in kalau diklik), lokasi tunggal juga ikut
                // di-zoom ke tier presisi dulu sebelum popup-nya kebuka.
                marker.on('click', function () {
                    if (mapInstance.getZoom() < ZOOM_MULAI_PRESISI) {
                        marker.closePopup(); // cegah "kekunci" kebuka di posisi zoom lama sebelum di-render ulang
                        focusLocation(loc.location_id, loc.latitude, loc.longitude);
                    }
                });

                marker.on('popupopen', function (e) {
                    var popupEl = e.popup.getElement();
                    slideInterval = startSlideshowAuto(popupEl);

                    var btn = popupEl.querySelector('.popup-btn');
                    if (!btn) return;
                    btn.addEventListener('click', function () {
                        currentLokasiId = parseInt(btn.dataset.lokasi, 10);
                        currentLokasiNama = btn.dataset.nama || '';
                        currentPage = 1;
                        openPanel('peta');
                        loadList();
                        renderChips();
                        marker.closePopup();
                    });
                });

                // Berhenti ganti-ganti foto begitu popup-nya ditutup, biar gak jalan sia-sia di background.
                marker.on('popupclose', function () {
                    if (slideInterval) {
                        clearInterval(slideInterval);
                        slideInterval = null;
                    }
                });
            }

            // Node cluster: cincin donut di titik tengah (centroid) sekelompok lokasi yang
            // berdekatan secara jarak asli. Klik -> zoom in ke tier berikutnya (lebih halus).
            function tambahMarkerCluster(anggota, zoomSaatIni) {
                var totalKegiatan = 0, totalBerlangsung = 0, totalSelesai = 0, totalAkanDatang = 0;
                var sumLat = 0, sumLng = 0;
                anggota.forEach(function (loc) {
                    totalKegiatan += Number(loc.total_kegiatan) || 0;
                    totalBerlangsung += Number(loc.berlangsung_count) || 0;
                    totalSelesai += Number(loc.selesai_count) || 0;
                    totalAkanDatang += Number(loc.akan_datang_count) || 0;
                    sumLat += Number(loc.latitude);
                    sumLng += Number(loc.longitude);
                });
                var centroidLat = sumLat / anggota.length;
                var centroidLng = sumLng / anggota.length;

                // Cincin donut: tiap warna statusnya proporsional ke komposisi kegiatan di dalam cluster.
                var gradasi = buildGradasiStatus(totalBerlangsung, totalSelesai, totalAkanDatang);

                var ukuran = anggota.length >= 10 ? 52 : (anggota.length >= 5 ? 46 : 40);

                var icon = L.divIcon({
                    className: '',
                    html: '<div class="marker-cluster-custom" style="width:' + ukuran + 'px;height:' + ukuran + 'px;background:' + gradasi + '">' +
                              '<div class="marker-cluster-inner">' + totalKegiatan + '</div>' +
                          '</div>',
                    iconSize: [ukuran, ukuran],
                    iconAnchor: [ukuran / 2, ukuran / 2]
                });

                var marker = L.marker([centroidLat, centroidLng], { icon: icon }).addTo(markerLayerGroup);
                marker.on('click', function () {
                    // Dulu: naik satu tier zoom per klik (zoomTierBerikutnya), jadi dari zoom
                    // jauh banget butuh klik berkali-kali buat "menembus" tiap tier.
                    // Sekarang: langsung fit ke bounds semua anggota cluster ini -> sekali klik
                    // langsung zoom in sedekat mungkin sampai seluruh anggotanya kelihatan.
                    var bounds = L.latLngBounds(anggota.map(function (loc) {
                        return [loc.latitude, loc.longitude];
                    }));
                    mapInstance.flyToBounds(bounds, {
                        paddingTopLeft: [40, 40],
                        paddingBottomRight: [40, 40],
                        maxZoom: 18,
                        duration: 0.6
                    });
                });
            }

            // Rumus gradasi donut yang sama dipakai buat marker individual maupun cluster,
            // biar warna proporsional ke komposisi status selalu konsisten di kedua tempat.
            function buildGradasiStatus(totalBerlangsung, totalSelesai, totalAkanDatang) {
                var totalStatus = totalBerlangsung + totalSelesai + totalAkanDatang;
                var pctBerlangsung = totalStatus > 0 ? (totalBerlangsung / totalStatus) * 100 : 0;
                var pctSelesai = totalStatus > 0 ? (totalSelesai / totalStatus) * 100 : 0;
                var batas1 = pctBerlangsung;
                var batas2 = pctBerlangsung + pctSelesai;
                return 'conic-gradient(var(--status-berlangsung) 0% ' + batas1 + '%, ' +
                    'var(--status-selesai) ' + batas1 + '% ' + batas2 + '%, ' +
                    'var(--status-akan-datang) ' + batas2 + '% 100%)';
            }

            // Feedback mentor: klik kartu kegiatan -> peta zoom ke lokasinya + buka popup-nya.
            function focusLocation(lokasiId, lat, lng) {
                if (!mapInstance) return;
                var zoomSekarang = mapInstance.getZoom();
                var perluGantiZoom = zoomSekarang < ZOOM_MULAI_PRESISI;
                var targetZoom = Math.max(zoomSekarang, ZOOM_MULAI_PRESISI);

                var bukaPopup = function () {
                    var marker = markersByLocationId[lokasiId];
                    if (marker) marker.openPopup();
                };

                mapInstance.flyTo([lat, lng], targetZoom, { duration: 0.6 });

                // Kalau tier zoom-nya ikut berubah, tunggu 'zoomend' (render ulang dulu baru buka popup).
                // Kalau tier-nya sama (udah di tahap presisi), cukup tunggu animasi pan selesai.
                if (perluGantiZoom) {
                    mapInstance.once('zoomend', function () { setTimeout(bukaPopup, 50); });
                } else {
                    mapInstance.once('moveend', function () { setTimeout(bukaPopup, 50); });
                }
            }

            // Label status singkat buat dipakai di list "Kegiatan Terbaru" pada popup.
            function buildPopupSlideshow(fotos) {
                if (!fotos || fotos.length === 0) {
                    return '<div class="popup-slideshow popup-slide-empty"><i class="bi bi-image"></i></div>';
                }

                var imgs = fotos.map(function (url, i) {
                    return '<img src="' + url + '" class="popup-slide-img" data-idx="' + i + '" style="display:' + (i === 0 ? 'block' : 'none') + '">';
                }).join('');

                var counter = fotos.length > 1
                    ? '<div class="popup-slide-counter">1/' + fotos.length + '</div>'
                    : '';

                return '<div class="popup-slideshow" data-total="' + fotos.length + '">' + imgs + counter + '</div>';
            }

            function buildPopupRecentList(kegiatanTerbaru) {
                if (!kegiatanTerbaru || kegiatanTerbaru.length === 0) {
                    return '<div class="popup-recent-empty">Belum ada kegiatan tercatat.</div>';
                }

                var items = kegiatanTerbaru.map(function (k) {
                    return '<li><span class="popup-recent-dot ' + k.status + '"></span>' +
                        '<span class="popup-recent-judul" title="' + escapeHtml(k.judul) + '">' + escapeHtml(k.judul) + '</span></li>';
                }).join('');

                return '<div class="popup-recent-title">Kegiatan Terbaru</div><ul class="popup-recent-list">' + items + '</ul>';
            }

            function buildPopupHtml(loc) {
                var alamat = [loc.nama_jalan, loc.kabupaten_kota, loc.provinsi].filter(Boolean).join(', ');

                return '' +
                    '<div class="popup-card">' +
                        buildPopupSlideshow(loc.fotos) +
                        '<h6>' + escapeHtml(loc.nama_lokasi) + '</h6>' +
                        '<div class="addr">' + escapeHtml(alamat) + '</div>' +
                        buildPopupRecentList(loc.kegiatan_terbaru) +
                        '<button type="button" class="popup-btn" data-lokasi="' + loc.location_id + '" data-nama="' + escapeHtml(loc.nama_lokasi) + '">Tampilkan Kegiatan</button>' +
                    '</div>';
            }

            // Slideshow jalan otomatis (gak ada tombol panah) -- ganti foto tiap 2.8 detik selama popup kebuka.
            var SLIDESHOW_INTERVAL_MS = 2800;

            function startSlideshowAuto(popupEl) {
                var slideshow = popupEl.querySelector('.popup-slideshow[data-total]');
                if (!slideshow) return null;

                var total = parseInt(slideshow.dataset.total, 10);
                if (!total || total <= 1) return null;

                var idx = 0;
                var imgs = slideshow.querySelectorAll('.popup-slide-img');
                var counter = slideshow.querySelector('.popup-slide-counter');

                return setInterval(function () {
                    idx = (idx + 1) % total;
                    imgs.forEach(function (img, j) { img.style.display = j === idx ? 'block' : 'none'; });
                    if (counter) counter.textContent = (idx + 1) + '/' + total;
                }, SLIDESHOW_INTERVAL_MS);
            }

            function loadMarkers() {
                var params = buildFilterParams();
                params.set('action', 'markers');
                fetchJSON('../api/get_kegiatan_publik.php?' + params.toString()).then(function (res) {
                    if (!res.success) return;
                    semuaLokasiData = res.data || [];
                    renderMarkerUntukZoomSaatIni();
                });
            }

            // ---------------- Init ----------------
            // Baca filter dari URL kalau halaman ini dibuka lewat deep-link dari
            // admin/dashboard.php (contoh: ?status=berlangsung atau ?bulan=2026-07).
            // PENTING: currentTag/currentDepartemen/currentStatus itu array yang sudah
            // "dipegang" (referensi) sama komponen multi-select -- makanya di sini di-push
            // satu-satu, bukan di-reassign, biar referensinya gak putus.
            (function bacaFilterDariUrl() {
                var qp = new URLSearchParams(window.location.search);
                var tahun      = qp.get('tahun');
                var bulan      = qp.get('bulan');
                var status     = qp.get('status');
                var tag        = qp.get('tag');
                var departemen = qp.get('departemen');
                if (tahun) currentTahun = tahun;
                if (bulan) currentBulan = bulan;
                if (status) status.split(',').forEach(function (s) { if (currentStatus.indexOf(s) === -1) currentStatus.push(s); });
                if (tag) tag.split(',').forEach(function (t) { if (currentTag.indexOf(t) === -1) currentTag.push(t); });
                if (departemen) departemen.split(',').forEach(function (d) { if (currentDepartemen.indexOf(d) === -1) currentDepartemen.push(d); });
            })();

            initMap();
            msStatus.render();
            renderAccCount('tagCount', currentTag);
            loadFilterOptions().then(function () {
                if (currentTahun) document.getElementById('filterTahun').value = currentTahun;
                if (currentBulan) {
                    // Bulan format YYYY-MM: set tahunnya di select Tahun (biar konsisten
                    // tampilannya), lalu isi & pilih opsi bulannya.
                    var parts = currentBulan.split('-');
                    document.getElementById('filterTahun').value = parts[0];
                    syncFilterBulanState();
                    document.getElementById('filterBulan').value = parts[1];
                }
            });
            loadList();
            loadMarkers();
            renderChips();
        })();
    </script>

</body>
</html>