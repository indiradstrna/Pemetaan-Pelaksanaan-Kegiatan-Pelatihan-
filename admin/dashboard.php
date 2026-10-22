<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/staff_page_check.php';

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/css/admin.css') ?>">
    <link href="<?= BASE_URL ?>/assets/css/toast.css" rel="stylesheet">
    <style>
        /* ==================== Toggle periode ==================== */
        .periode-toggle {
            display: inline-flex;
            background: #f3f4f6;
            border-radius: 10px;
            padding: 3px;
            gap: 2px;
        }
        .periode-toggle button {
            border: none;
            background: transparent;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
            color: var(--admin-text-muted);
            cursor: pointer;
            transition: background .15s ease, color .15s ease;
        }
        .periode-toggle button.active {
            background: var(--admin-green-bg);
            color: var(--admin-green);
        }
        .periode-indikator {
            font-size: .82rem;
            font-weight: 600;
            color: var(--admin-text-muted);
            margin: 8px 2px 0;
            text-align: right;
        }

        /* ==================== Stat cards ==================== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 8px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #eef0f2;
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 2px 6px rgba(16,24,40,.04);
        }
        .stat-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .stat-card-label { color: var(--admin-text-muted); font-size: .9rem; }
        .stat-card-icon {
            width: 30px; height: 30px;
            border-radius: 8px;
            background: var(--admin-green);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            text-decoration: none;
            transition: filter .15s ease;
        }
        .stat-card-icon:hover { filter: brightness(.9); color: #fff; }
        .stat-card-value { font-size: 2.2rem; font-weight: 800; line-height: 1; margin-bottom: 10px; }
        .stat-card-trend { font-size: .82rem; color: var(--admin-text-muted); }
        .stat-card-trend.up   { color: var(--admin-green); }
        .stat-card-trend.down { color: #b91c1c; }
        .terakhir-update { color: var(--admin-text-muted); font-size: .82rem; margin: 6px 2px 20px; }

        /* ==================== Panel generik ==================== */
        .dash-panel {
            background: #fff;
            border: 1px solid #eef0f2;
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 2px 6px rgba(16,24,40,.04);
        }
        .dash-panel-title { font-weight: 700; font-size: 1.02rem; margin: 0; }

        .dash-row-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            align-items: stretch;
        }
        .dash-row-2-notif {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        /* ==================== Statistik Detail ==================== */
        .dash-section-title {
            font-weight: 700;
            font-size: 1.05rem;
            margin: 22px 2px 12px;
        }
        /* Chart berpasangan sejajar (2 kolom sama lebar) -- pola "chart utama + chart
           pendamping sebaris" kayak di referensi (Revenue Over Time | Revenue by Category,
           Sales by Region | peta). Dipakai buat Tren Bulan+Peserta dan Provinsi+Departemen. */
        .dash-row-half {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .dash-row-half { grid-template-columns: 1fr; }
        }

        /* Wrapper tinggi tetap buat tiap canvas Chart.js -- WAJIB ada tinggi pasti di sini,
           bukan cuma max-height di canvas-nya. Chart.js dengan maintainAspectRatio:false
           mengukur tinggi PARENT langsung buat nentuin ukurannya; kalau parent gak punya
           tinggi pasti (cuma nurut isi/canvas), ukurannya bisa membesar tanpa henti (berat +
           bikin overflow ke samping). overflow:hidden di sini jadi jaring pengaman terakhir.
        */
        .chart-box { position: relative; width: 100%; height: 220px; overflow: hidden; }

        /* ==================== Jumlah Data (Daerah/Kategori) ==================== */
        .jd-tabs { display: flex; gap: 6px; margin-bottom: 16px; }
        .jd-tabs.inline { margin-bottom: 0; }
        .jd-tabs button {
            border: 1px solid #e5e7eb;
            background: #fff;
            color: var(--admin-text-muted);
            padding: 5px 14px;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
        }
        .jd-tabs button.active {
            background: var(--admin-green-bg);
            border-color: var(--admin-green-bg);
            color: var(--admin-green);
        }
        .jd-list { max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; }
        .jd-item-top { display: flex; justify-content: space-between; font-size: .87rem; margin-bottom: 5px; }
        .jd-item-count { color: var(--admin-text-muted); }
        .jd-bar-track { height: 6px; border-radius: 999px; background: #f1f2f4; overflow: hidden; }
        .jd-bar-fill { height: 100%; border-radius: 999px; }
        .jd-empty { color: var(--admin-text-muted); font-size: .85rem; text-align: center; padding: 24px 0; }

        /* ==================== Kegiatan Terbaru ==================== */
        .btn-lihat-semua {
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 600;
            color: var(--admin-text);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-lihat-semua:hover { background: #f7f8fa; color: var(--admin-text); }

        .row-kegiatan-terbaru:hover td { background: #f7f8fa; }
        .badge-status { padding: 3px 10px; border-radius: 999px; font-size: .78rem; font-weight: 600; white-space: nowrap; }
        .badge-status.selesai           { background: #dcfce7; color: #16a34a; }
        .badge-status.berlangsung       { background: #fef3c7; color: #b45309; }
        .badge-status.belum_berlangsung { background: #e0e7ff; color: #4338ca; }

        /* ==================== Notifikasi ==================== */
        /* ==================== Perlu Perhatian (dulu namanya "Notifikasi", di-rename
           biar gak ketuker sama dropdown lonceng notifikasi di navbar -- classnya juga
           SENGAJA dibedain (reminder-* bukan notif-*) biar gak numpuk/collision kayak
           sebelumnya, soalnya dropdown lonceng itu pakai class notif-* juga) ==================== */
        .reminder-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .reminder-hapus-semua {
            border: none; background: none;
            color: var(--admin-text-muted);
            font-size: .8rem;
            cursor: pointer;
            display: flex; align-items: center; gap: 4px;
        }
        .reminder-hapus-semua:hover { color: var(--admin-text); }
        .reminder-list { display: flex; flex-direction: column; gap: 10px; }
        .reminder-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 14px;
            position: relative;
            cursor: pointer;
            transition: filter .12s ease;
        }
        .reminder-item:hover { filter: brightness(.97); }
        .reminder-item-icon {
            width: 26px; height: 26px; border-radius: 7px;
            background: #f59e0b; color: #fff;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .reminder-item-title { font-weight: 700; font-size: .87rem; color: #78350f; }
        .reminder-item-desc { font-size: .8rem; color: #92400e; margin-top: 1px; }
        .reminder-item-close {
            border: none; background: none; color: #b45309;
            position: absolute; top: 10px; right: 10px;
            cursor: pointer; line-height: 1; font-size: 1rem;
            z-index: 2;
        }
        .reminder-empty { color: var(--admin-text-muted); font-size: .85rem; text-align: center; padding: 20px 0; }

        /* Badge pembeda jenis pesan: data belum lengkap (kuning) vs belum ditandai selesai (merah) */
        .reminder-tipe-badge {
            display: inline-flex;
            align-items: center;
            padding: 1px 8px;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin-bottom: 3px;
        }
        .reminder-tipe-badge.tipe-lengkap { background: #fef3e2; color: #b45309; }
        .reminder-tipe-badge.tipe-selesai { background: #fee2e2; color: #b91c1c; }
        .reminder-item.tipe-selesai { background: #fef2f2; border-color: #fecaca; }
        .reminder-item.tipe-selesai .reminder-item-icon { background: #dc2626; }
        .reminder-item.tipe-selesai .reminder-item-title { color: #7f1d1d; }
        .reminder-item.tipe-selesai .reminder-item-desc  { color: #991b1b; }

        /* Pagination sederhana (prev/next) buat panel Perlu Perhatian */
        .reminder-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #f1f2f4;
        }
        .reminder-pagination button {
            border: 1px solid #e5e7eb;
            background: #fff;
            width: 28px; height: 28px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--admin-text);
        }
        .reminder-pagination button:disabled { opacity: .35; cursor: default; }
        .reminder-pagination button:not(:disabled):hover { background: #f8f9fb; }
        .reminder-pagination-info { font-size: .78rem; color: var(--admin-text-muted); }

        /* ==================== Aksi Cepat ==================== */
        .aksi-list { display: flex; flex-direction: column; gap: 10px; }
        .aksi-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            border-radius: 10px;
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: .9rem;
            text-decoration: none;
        }
        .aksi-btn:hover { color: #fff; opacity: .92; }
        .aksi-btn svg { flex-shrink: 0; }
        .aksi-btn.aksi-kegiatan { background: #365314; }
        .aksi-btn.aksi-lokasi   { background: var(--admin-green); }
        .aksi-btn.aksi-user     { background: #1e3a8a; }

        @media (max-width: 1100px) {
            .stat-grid { grid-template-columns: 1fr; }
            .dash-row-2, .dash-row-2-notif { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="admin-body">
    <?php require_once __DIR__ . '/../includes/navbar_admin.php'; ?>

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle mb-0">Ringkasan pengelolaan lokasi dan kegiatan</p>
            </div>
            <div class="periode-toggle" id="periodeToggle">
                <button type="button" data-periode="bulan" class="active">Bulan Ini</button>
                <button type="button" data-periode="tahun">Tahun Ini</button>
                <button type="button" data-periode="semua">Semua</button>
            </div>
        </div>
        <div class="periode-indikator" id="periodeIndikator"></div>

        <div class="stat-grid mt-4" id="statGrid">
            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Total Kegiatan</span>
                    <a class="stat-card-icon" id="linkTotalKegiatan" href="#" title="Lihat di peta">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
                <div class="stat-card-value" id="statTotalKegiatan">-</div>
                <div class="stat-card-trend" id="statTrendKegiatan">-</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Kegiatan Berlangsung</span>
                    <a class="stat-card-icon" id="linkBerlangsung" href="#" title="Lihat di peta">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
                <div class="stat-card-value" id="statBerlangsung">-</div>
                <div class="stat-card-trend" id="statLokasiBerlangsung">-</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-card-label">Kegiatan Selesai</span>
                    <a class="stat-card-icon" id="linkSelesai" href="#" title="Lihat di peta">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
                <div class="stat-card-value" id="statKegiatanSelesai">-</div>
                <div class="stat-card-trend" id="statTrendSelesai">-</div>
            </div>
        </div>
        <div class="terakhir-update" id="terakhirUpdate">Terakhir Update: -</div>

        <div class="dash-row-2">
            <div class="dash-panel">
                <p class="dash-panel-title mb-3">Grafik Total Kegiatan</p>
                <canvas id="chartKegiatan" height="110"></canvas>
            </div>

            <div class="dash-panel">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <p class="dash-panel-title mb-0">Jumlah Data</p>
                </div>
                <div class="jd-tabs mt-3">
                    <button type="button" class="active" data-tab="daerah">Daerah</button>
                    <button type="button" data-tab="kategori">Kategori</button>
                </div>
                <div class="jd-list" id="jdList"></div>
            </div>
        </div>

        <p class="dash-section-title">Statistik Detail</p>

        <div class="dash-row-2">
            <div class="dash-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="dash-panel-title mb-0">Tren Kegiatan &amp; Peserta</p>
                    <div class="jd-tabs inline" id="trenTabs">
                        <button type="button" class="active" data-tren="kegiatan">Kegiatan</button>
                        <button type="button" data-tren="peserta">Peserta</button>
                    </div>
                </div>
                <div class="chart-box"><canvas id="chartTren"></canvas></div>
            </div>
            <div class="dash-panel">
                <p class="dash-panel-title mb-3">Distribusi per Tag</p>
                <div class="chart-box"><canvas id="chartTag"></canvas></div>
            </div>
        </div>

        <div class="dash-row-half">
            <div class="dash-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="dash-panel-title mb-0">Provinsi</p>
                    <div class="jd-tabs inline" id="provinsiTabs">
                        <button type="button" class="active" data-provinsi="teraktif">Teraktif</button>
                        <button type="button" data-provinsi="tersedikit">Tersedikit</button>
                    </div>
                </div>
                <div class="chart-box"><canvas id="chartProvinsi"></canvas></div>
            </div>
            <div class="dash-panel">
                <p class="dash-panel-title mb-3">Kegiatan per Departemen</p>
                <div class="chart-box"><canvas id="chartDepartemen"></canvas></div>
            </div>
        </div>

        <div class="dash-panel mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="dash-panel-title mb-0">Kegiatan Terbaru</p>
                <a href="<?= BASE_URL ?>/admin/kelola_kegiatan.php" class="btn-lihat-semua">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    Lihat Semua
                </a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Kegiatan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tabelKegiatanTerbaru">
                    <tr><td colspan="3" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dash-row-2-notif">
            <div class="dash-panel">
                <div class="reminder-header">
                    <p class="dash-panel-title mb-0">Perlu Perhatian</p>
                    <button type="button" class="reminder-hapus-semua" id="btnHapusSemuaReminder">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        Hapus Semua
                    </button>
                </div>
                <div class="reminder-list" id="reminderList"></div>
                <div class="reminder-pagination" id="reminderPagination"></div>
            </div>

            <div class="dash-panel">
                <p class="dash-panel-title mb-3">Aksi Cepat</p>
                <div class="aksi-list">
                    <a href="<?= BASE_URL ?>/admin/kelola_kegiatan.php?aksi=tambah" class="aksi-btn aksi-kegiatan">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><line x1="12" y1="14" x2="12" y2="18"></line><line x1="10" y1="16" x2="14" y2="16"></line></svg>
                        Tambah Kegiatan
                    </a>
                    <a href="<?= BASE_URL ?>/admin/kelola_lokasi.php?aksi=tambah" class="aksi-btn aksi-lokasi">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"></path><circle cx="12" cy="10" r="2.2"></circle></svg>
                        Tambah Lokasi
                    </a>
                    <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
                    <a href="<?= BASE_URL ?>/admin/kelola_user.php?aksi=tambah" class="aksi-btn aksi-user">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                        Tambah User
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/toast.js"></script>

    <script>
        const API_DASHBOARD = '<?= BASE_URL ?>/api/dashboard_data.php';
        const BASE_URL = '<?= BASE_URL ?>';

        let dashboardData = null;
        let chartKegiatan = null;
        let chartInstances = {}; // nyimpen instance 9 chart tambahan (Chart.js), key = canvas id
        let jdTabAktif = 'daerah';
        let periodeAktif = 'bulan';

        // Warna status (biru #2563eb, oranye #d97706, ungu #7c3aed) sengaja tidak dipakai di sini
        // biar tidak ketuker sama warna indikator status di kartu/grafik lain.
        const paletteDaerah = ['#16a34a', '#dc2626', '#0891b2', '#db2777', '#65a30d', '#0d9488', '#6b7280', '#334155'];

        function formatTanggalJam(iso) {
            if (!iso) return '-';
            const d = new Date(iso.replace(' ', 'T'));
            if (isNaN(d)) return '-';
            const pad = n => String(n).padStart(2, '0');
            return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function badgeStatus(status) {
            const label = { selesai: 'Selesai', berlangsung: 'Berlangsung', belum_berlangsung: 'Belum Berlangsung' };
            return `<span class="badge-status ${status}">${label[status] || status}</span>`;
        }

        // Label "Bulan: ..." / "Tahun: ..." di bawah toggle periode, biar user gak bingung data yang ditampilkan itu cakupannya apa.
        function updatePeriodeIndikator(periode) {
            const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const now = new Date();
            const el = document.getElementById('periodeIndikator');

            if (periode === 'bulan') {
                el.textContent = `Bulan: ${namaBulan[now.getMonth()]} ${now.getFullYear()}`;
            } else if (periode === 'tahun') {
                el.textContent = `Tahun: ${now.getFullYear()}`;
            } else {
                el.textContent = 'Menampilkan: Semua Data';
            }
        }

        async function loadDashboard(periode) {
            periodeAktif = periode;
            updatePeriodeIndikator(periode);
            const res  = await fetch(`${API_DASHBOARD}?period=${encodeURIComponent(periode)}`);
            const json = await res.json();

            if (!json.success) {
                showToast(json.message || 'Gagal memuat data dashboard.');
                return;
            }

            dashboardData = json.data;
            renderStat(dashboardData.stat);
            renderChart(dashboardData.grafik);
            renderChartTambahan(dashboardData.chart_tambahan);
            renderJumlahData();
            renderKegiatanTerbaru(dashboardData.kegiatan_terbaru);
            renderPengingat(dashboardData.pengingat);
        }

        // Bulan berjalan dalam format YYYY-MM, dipakai buat deep-link "Total Kegiatan"/"Kegiatan Selesai" ke peta.
        function bulanIniYYYYMM() {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        }

        // Bangun query filter peta sesuai periode toggle yang lagi aktif (bulan/tahun/semua).
        function paramsPeriodeAktif() {
            const params = new URLSearchParams();
            if (periodeAktif === 'bulan') params.set('bulan', bulanIniYYYYMM());
            else if (periodeAktif === 'tahun') params.set('tahun', String(new Date().getFullYear()));
            return params;
        }

        function updateLinkKartu() {
            const pTotal = paramsPeriodeAktif();
            document.getElementById('linkTotalKegiatan').href = `${BASE_URL}/user/index.php?${pTotal.toString()}`;

            document.getElementById('linkBerlangsung').href = `${BASE_URL}/user/index.php?status=berlangsung`;

            const pSelesai = paramsPeriodeAktif();
            pSelesai.set('status', 'selesai');
            document.getElementById('linkSelesai').href = `${BASE_URL}/user/index.php?${pSelesai.toString()}`;
        }

        function renderTrend(elId, persen, label) {
            const el = document.getElementById(elId);
            if (persen === null) {
                el.textContent = 'Total keseluruhan';
                el.className = 'stat-card-trend';
                return;
            }
            const naik = persen >= 0;
            el.innerHTML = `${naik ? '↑' : '↓'} ${Math.abs(persen)}% dari ${label}`;
            el.className = 'stat-card-trend ' + (naik ? 'up' : 'down');
        }

        function renderStat(stat) {
            document.getElementById('statTotalKegiatan').textContent = stat.total_kegiatan;
            renderTrend('statTrendKegiatan', stat.trend_persen, stat.trend_label);

            document.getElementById('statBerlangsung').textContent = stat.kegiatan_berlangsung;
            document.getElementById('statLokasiBerlangsung').textContent =
                stat.lokasi_berlangsung > 0 ? `di ${stat.lokasi_berlangsung} lokasi berbeda` : 'Tidak ada yang berlangsung';

            document.getElementById('statKegiatanSelesai').textContent = stat.kegiatan_selesai;
            const elBelumBerlangsung = document.getElementById('statTrendSelesai');
            elBelumBerlangsung.textContent = `${stat.kegiatan_belum_berlangsung} Kegiatan akan berlangsung`;
            elBelumBerlangsung.className = 'stat-card-trend';

            updateLinkKartu();

            document.getElementById('terakhirUpdate').textContent = `Terakhir Update: ${formatTanggalJam(stat.terakhir_update)}`;
        }

        const MAX_BAR_THICKNESS = 46;

        // Plugin lokal (cuma dipakai di chart ini): kalau dalam satu kategori (Minggu/Bulan/Tahun)
        // cuma salah satu dataset (Selesai/Berlangsung) yang ada nilainya, bar itu digeser ke tengah
        // dan dilebarkan supaya lurus sama label sumbu-X -- bukan nempel di slot kiri/kanannya.
        // Kalau dua-duanya ada nilai di kategori yang sama, dibiarkan grouped seperti biasa.
        const alignSingleBarPlugin = {
            id: 'alignSingleBar',
            afterDatasetsUpdate(chart) {
                if (chart.data.datasets.length < 2) return;
                const meta0 = chart.getDatasetMeta(0);
                const meta1 = chart.getDatasetMeta(1);
                const xScale = chart.scales.x;
                if (!meta0 || !meta1 || !xScale) return;

                chart.data.labels.forEach((_, i) => {
                    const v0 = Number(chart.data.datasets[0].data[i]) || 0;
                    const v1 = Number(chart.data.datasets[1].data[i]) || 0;
                    const bar0 = meta0.data[i];
                    const bar1 = meta1.data[i];
                    if (!bar0 || !bar1) return;

                    const hanyaSatu = (v0 > 0) !== (v1 > 0);
                    if (!hanyaSatu) return;

                    const tengah = xScale.getPixelForValue(i);
                    const lebarGabungan = Math.min(bar0.width + bar1.width, MAX_BAR_THICKNESS);

                    if (v0 > 0) {
                        bar0.x = tengah;
                        bar0.width = lebarGabungan;
                    } else {
                        bar1.x = tengah;
                        bar1.width = lebarGabungan;
                    }
                });
            }
        };

        function renderChart(grafik) {
            if (chartKegiatan) chartKegiatan.destroy();
            const ctx = document.getElementById('chartKegiatan').getContext('2d');

            chartKegiatan = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: grafik.labels,
                    datasets: [
                        // Warna disamakan dengan patokan status di user/index.php (--status-selesai, --status-berlangsung)
                        { label: 'Selesai', data: grafik.selesai, backgroundColor: '#2563eb', borderRadius: 6, maxBarThickness: MAX_BAR_THICKNESS },
                        { label: 'Berlangsung', data: grafik.berlangsung, backgroundColor: '#d97706', borderRadius: 6, maxBarThickness: MAX_BAR_THICKNESS },
                    ]
                },
                options: {
                    responsive: true,
                    animation: false, // supaya reposisi bar dari alignSingleBarPlugin tidak ketimpa animasi transisi
                    plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' } } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                },
                plugins: [alignSingleBarPlugin]
            });
        }

        // ==================== 9 chart statistik detail (dipindah dari peta publik) ====================
        // Palet warna disamakan sama paletteDaerah yang udah dipakai di panel "Jumlah Data",
        // biar konsisten satu dashboard, bukan warna baru yang ketuker-tuker.
        // Gaya tooltip yang lebih modern (dipakai semua chart baru): rounded, padding
        // lega, tanpa kotak warna kecil di depan angka -- lebih mirip popover kayak
        // di referensi desain, bukan tooltip default Chart.js yang kaku.
        const TOOLTIP_MODERN = {
            backgroundColor: '#fff',
            titleColor: '#6b7280',
            bodyColor: '#16181a',
            borderColor: '#eef0f2',
            borderWidth: 1,
            cornerRadius: 10,
            padding: 10,
            displayColors: false,
            titleFont: { weight: '600', size: 11 },
            bodyFont: { weight: '700', size: 13 },
        };

        function renderBarChart(canvasId, labels, data, color, horizontal, maxThickness) {
            if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
            const ctx = document.getElementById(canvasId).getContext('2d');
            chartInstances[canvasId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: color,
                        hoverBackgroundColor: color,
                        borderRadius: 8,
                        maxBarThickness: maxThickness || MAX_BAR_THICKNESS,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: horizontal ? 'y' : 'x',
                    plugins: { legend: { display: false }, tooltip: TOOLTIP_MODERN },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: !horizontal, color: '#f3f4f6' } },
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: horizontal, color: '#f3f4f6' } }
                    }
                }
            });
        }

        function renderDonutChartAdmin(canvasId, labels, data, colors) {
            if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
            const ctx = document.getElementById(canvasId).getContext('2d');
            chartInstances[canvasId] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: colors || labels.map((_, i) => paletteDaerah[i % paletteDaerah.length]),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } },
                        tooltip: TOOLTIP_MODERN
                    }
                }
            });
        }

        // Gradasi lembut di bawah garis (isi area chart), bukan warna solid -- biar
        // lebih modern kayak referensi (garis Overview yang ada gradient hijau samar).
        function buatGradienGaris(ctx, warna) {
            const tinggi = ctx.canvas.height || 220;
            const gradient = ctx.createLinearGradient(0, 0, 0, tinggi);
            gradient.addColorStop(0, warna + '3d');
            gradient.addColorStop(1, warna + '00');
            return gradient;
        }

        function renderLineChart(canvasId, labels, data, color) {
            if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
            const ctx = document.getElementById(canvasId).getContext('2d');
            chartInstances[canvasId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data,
                        borderColor: color,
                        backgroundColor: buatGradienGaris(ctx, color),
                        fill: true,
                        tension: .35,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: color,
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: TOOLTIP_MODERN },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } }
                    }
                }
            });
        }

        // Toggle Tren Kegiatan/Peserta -- 1 chart, datanya ganti tanpa fetch ulang
        // (data kedua-duanya udah ada di respons dashboard_data.php sekaligus).
        let trenAktif     = 'kegiatan';
        let trenDataCache = null;

        function renderTren() {
            if (!trenDataCache) return;
            const data  = trenAktif === 'kegiatan' ? trenDataCache.total : trenDataCache.peserta;
            const warna = trenAktif === 'kegiatan' ? '#16a34a' : '#d97706';
            renderLineChart('chartTren', trenDataCache.labels, data, warna);
        }

        document.querySelectorAll('#trenTabs button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                trenAktif = btn.dataset.tren;
                document.querySelectorAll('#trenTabs button').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                renderTren();
            });
        });

        // Toggle Provinsi Teraktif/Tersedikit -- 1 chart, data provinsi lengkap (gak
        // dibatasi LIMIT di backend) di-slice di sini: 10 teratas (jumlah terbanyak)
        // vs 10 terbawah (jumlah tersedikit, diurutkan naik).
        let provinsiAktif     = 'teraktif';
        let provinsiDataCache = [];

        function renderProvinsi() {
            const data = provinsiAktif === 'teraktif'
                ? provinsiDataCache.slice(0, 10)
                : provinsiDataCache.slice(-10).reverse();
            renderBarChart('chartProvinsi', data.map(x => x.label), data.map(x => x.jumlah), '#0891b2', true);
        }

        document.querySelectorAll('#provinsiTabs button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                provinsiAktif = btn.dataset.provinsi;
                document.querySelectorAll('#provinsiTabs button').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                renderProvinsi();
            });
        });

        function renderChartTambahan(ct) {
            provinsiDataCache = ct.provinsi;
            renderProvinsi();
            // Bar thickness dikecilin dibanding default (MAX_BAR_THICKNESS) -- departemen
            // kategorinya lebih sedikit jadi kalau pakai thickness sama, bar-nya keliatan
            // jauh lebih chunky/besar dibanding chart Provinsi di sebelahnya.
            renderBarChart('chartDepartemen', ct.departemen.map(x => x.label), ct.departemen.map(x => x.jumlah), '#db2777', true, 22);
            trenDataCache = ct.tren_bulan;
            renderTren();
            const topTag = ct.tag.slice(0, 12);
            renderBarChart('chartTag', topTag.map(x => x.label), topTag.map(x => x.jumlah), '#65a30d', true);
        }

        function renderJumlahData() {
            const list = dashboardData.jumlah_data[jdTabAktif] || [];
            const container = document.getElementById('jdList');

            if (list.length === 0) {
                container.innerHTML = '<div class="jd-empty">Belum ada data untuk periode ini.</div>';
                return;
            }

            const totalJumlah = list.reduce((sum, x) => sum + Number(x.jumlah), 0);
            container.innerHTML = list.map((item, i) => {
                const persen = totalJumlah > 0 ? Math.round((item.jumlah / totalJumlah) * 100) : 0;
                const warna = paletteDaerah[i % paletteDaerah.length];
                return `
                    <div>
                        <div class="jd-item-top">
                            <span>${escapeHtml(item.label)}</span>
                            <span class="jd-item-count">${item.jumlah}</span>
                        </div>
                        <div class="jd-bar-track">
                            <div class="jd-bar-fill" style="width:${persen}%;background:${warna};"></div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderKegiatanTerbaru(list) {
            const tbody = document.getElementById('tabelKegiatanTerbaru');
            if (!list || list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Belum ada data kegiatan.</td></tr>';
                return;
            }
            tbody.innerHTML = list.map(k => `
                <tr class="row-kegiatan-terbaru" data-id="${k.id}" style="cursor:pointer;" title="Lihat detail kegiatan">
                    <td class="fw-semibold">${escapeHtml(k.kode_kegiatan)}</td>
                    <td>${escapeHtml(k.judul)}</td>
                    <td>${badgeStatus(k.status)}</td>
                </tr>
            `).join('');

            tbody.querySelectorAll('.row-kegiatan-terbaru').forEach(row => {
                row.addEventListener('click', () => {
                    window.location.href = `${BASE_URL}/user/detail_kegiatan.php?id=${row.dataset.id}&from=admin`;
                });
            });
        }

        function iconPeringatan() {
            return `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
        }

        // ==================== Perlu Perhatian: list, pagination, redirect ====================
        const REMINDER_PAGE_SIZE = 3;
        let reminderItems = [];   // list gabungan dari 3 kategori, udah ke-tag tipe & link tujuan
        let reminderPage  = 1;

        // Label & target link beda-beda per tipe pesan -- diklik langsung ke data terkait.
        function renderPengingat(pengingat) {
            const daftar = [];

            pengingat.kegiatan_terlambat.items.forEach(item => {
                const tgl = new Date(item.tanggal_selesai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                daftar.push({
                    tipe: 'selesai',
                    tipeLabel: 'Belum Ditandai Selesai',
                    judul: 'Kegiatan belum ditandai selesai',
                    desc: `"${item.judul}" sudah lewat tanggal selesai (${tgl}), tapi belum ditandai selesai.`,
                    href: `${BASE_URL}/admin/kelola_kegiatan.php?selesai=${item.id}`,
                });
            });

            pengingat.lokasi_tanpa_verifikasi.items.forEach(item => {
                daftar.push({
                    tipe: 'lengkap',
                    tipeLabel: 'Data Belum Lengkap',
                    judul: 'Lokasi belum diverifikasi',
                    desc: `"${item.nama_lokasi}" masih pakai titik perkiraan, belum pernah digeser manual di peta.`,
                    href: `${BASE_URL}/admin/kelola_lokasi.php?edit=${item.id}`,
                });
            });

            pengingat.kegiatan_tanpa_dokumentasi.items.forEach(item => {
                daftar.push({
                    tipe: 'lengkap',
                    tipeLabel: 'Data Belum Lengkap',
                    judul: 'Kegiatan tanpa dokumentasi',
                    desc: `"${item.judul}" belum memiliki foto dokumentasi.`,
                    href: `${BASE_URL}/admin/kelola_kegiatan.php?edit=${item.id}&highlight=foto`,
                });
            });

            reminderItems = daftar;
            reminderPage  = 1;
            renderReminderPage();
        }

        function renderReminderPage() {
            const container = document.getElementById('reminderList');
            const btnHapusSemua = document.getElementById('btnHapusSemuaReminder');

            if (reminderItems.length === 0) {
                container.innerHTML = '<div class="reminder-empty">Gak ada yang perlu diperhatikan. Semua data lengkap 🎉</div>';
                btnHapusSemua.classList.add('d-none');
                renderReminderPagination();
                return;
            }

            btnHapusSemua.classList.remove('d-none');

            const totalPages = Math.max(1, Math.ceil(reminderItems.length / REMINDER_PAGE_SIZE));
            if (reminderPage > totalPages) reminderPage = totalPages;

            const start   = (reminderPage - 1) * REMINDER_PAGE_SIZE;
            const halaman = reminderItems.slice(start, start + REMINDER_PAGE_SIZE);

            container.innerHTML = halaman.map((n, i) => `
                <div class="reminder-item tipe-${n.tipe}" data-reminder-idx="${start + i}">
                    <span class="reminder-item-icon">${iconPeringatan()}</span>
                    <div>
                        <span class="reminder-tipe-badge tipe-${n.tipe}">${escapeHtml(n.tipeLabel)}</span>
                        <div class="reminder-item-title">${escapeHtml(n.judul)}</div>
                        <div class="reminder-item-desc">${escapeHtml(n.desc)}</div>
                    </div>
                    <button type="button" class="reminder-item-close" onclick="event.stopPropagation(); hapusPengingat(${start + i})" aria-label="Tutup">&times;</button>
                </div>
            `).join('');

            container.querySelectorAll('.reminder-item').forEach(el => {
                el.addEventListener('click', () => {
                    const idx = parseInt(el.dataset.reminderIdx, 10);
                    window.location.href = reminderItems[idx].href;
                });
            });

            renderReminderPagination(totalPages);
        }

        function renderReminderPagination(totalPages) {
            const container = document.getElementById('reminderPagination');
            if (!container) return;

            if (!totalPages || totalPages <= 1) { container.innerHTML = ''; return; }

            const start = (reminderPage - 1) * REMINDER_PAGE_SIZE + 1;
            const end   = Math.min(reminderPage * REMINDER_PAGE_SIZE, reminderItems.length);

            container.innerHTML = `
                <button type="button" ${reminderPage === 1 ? 'disabled' : ''} onclick="gotoReminderPage(${reminderPage - 1})" aria-label="Sebelumnya">&lsaquo;</button>
                <span class="reminder-pagination-info">${start}–${end} dari ${reminderItems.length}</span>
                <button type="button" ${reminderPage === totalPages ? 'disabled' : ''} onclick="gotoReminderPage(${reminderPage + 1})" aria-label="Selanjutnya">&rsaquo;</button>
            `;
        }

        function gotoReminderPage(p) { reminderPage = p; renderReminderPage(); }

        function hapusPengingat(idx) {
            // Dismiss sementara aja (bukan permanen) -- muncul lagi kalau halaman di-reload.
            reminderItems.splice(idx, 1);
            renderReminderPage();
        }

        document.getElementById('btnHapusSemuaReminder').addEventListener('click', () => {
            reminderItems = [];
            renderReminderPage();
        });

        // ==================== Tab Jumlah Data ====================
        document.querySelectorAll('.jd-tabs button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.jd-tabs button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                jdTabAktif = btn.dataset.tab;
                renderJumlahData();
            });
        });

        // ==================== Toggle Periode ====================
        document.querySelectorAll('#periodeToggle button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#periodeToggle button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                loadDashboard(btn.dataset.periode);
            });
        });

        loadDashboard('bulan');
    </script>
</body>
</html>