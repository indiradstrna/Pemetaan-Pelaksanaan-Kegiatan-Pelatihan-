<?php
/**
 * SEKAR BIOTROP - GeoImpact DSS
 * Dashboard Matriks Evaluasi Rubrik & Pemeringkatan Prioritas (Algoritma SAW)
 * Migrasi dari code.html ke PHP terintegrasi dengan Sidebar SEKAR
 */

session_start();
require_once __DIR__ . '/config/constants.php';

$sudahLogin = isset($_SESSION['user_id']);
$userNama = $_SESSION['nama'] ?? 'Bambang Pratama';
$userRole = $_SESSION['role'] ?? 'M&E Lead Specialist';
$userInitial = strtoupper(substr($userNama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>GeoImpact DSS - Edu-Monitoring GIS | SEKAR BIOTROP</title>
    <meta name="description" content="Dashboard Matriks Evaluasi Rubrik DSS & Pemeringkatan Prioritas Algoritma SAW - SEKAR SEAMEO BIOTROP">
    <link rel="icon" type="image/x-icon" href="assets/img/logo-biotrop.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-tint": "#006c4a",
                        "secondary-fixed-dim": "#93ccff",
                        "background": "#faf8ff",
                        "on-tertiary-fixed": "#2a1700",
                        "surface-variant": "#dae2fd",
                        "error": "#ba1a1a",
                        "secondary-fixed": "#cce5ff",
                        "tertiary": "#825100",
                        "tertiary-fixed-dim": "#ffb95f",
                        "surface-container-high": "#e2e7ff",
                        "surface-container-lowest": "#ffffff",
                        "error-container": "#ffdad6",
                        "surface-container-low": "#f2f3ff",
                        "outline": "#6d7a72",
                        "on-tertiary-container": "#fffbff",
                        "on-secondary-fixed": "#001d31",
                        "on-surface-variant": "#3d4a42",
                        "on-tertiary-fixed-variant": "#653e00",
                        "on-error-container": "#93000a",
                        "tertiary-container": "#a36700",
                        "on-error": "#ffffff",
                        "on-surface": "#131b2e",
                        "tertiary-fixed": "#ffddb8",
                        "surface": "#faf8ff",
                        "inverse-surface": "#283044",
                        "primary-fixed": "#85f8c4",
                        "outline-variant": "#bccac0",
                        "surface-container-highest": "#dae2fd",
                        "surface-bright": "#faf8ff",
                        "on-primary": "#ffffff",
                        "on-tertiary": "#ffffff",
                        "primary-fixed-dim": "#68dba9",
                        "on-primary-fixed": "#002114",
                        "on-secondary": "#ffffff",
                        "on-primary-fixed-variant": "#005137",
                        "secondary-container": "#5bb8fe",
                        "primary-container": "#00855d",
                        "on-background": "#131b2e",
                        "surface-container": "#eaedff",
                        "on-secondary-fixed-variant": "#004b73",
                        "inverse-on-surface": "#eef0ff",
                        "primary": "#006948",
                        "on-secondary-container": "#00476e",
                        "surface-dim": "#d2d9f4",
                        "on-primary-container": "#f5fff7",
                        "secondary": "#006398",
                        "inverse-primary": "#68dba9"
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    spacing: {
                        "gutter-desktop": "1.25rem",
                        "margin": "1rem",
                        "space-lg": "1.5rem",
                        "margin-desktop": "1.5rem",
                        "gutter": "1rem",
                        "space-md": "1rem",
                        "space-xl": "2rem",
                        "space-sm": "0.5rem",
                        "space-xs": "0.25rem"
                    },
                    fontFamily: {
                        "sans": ["Plus Jakarta Sans", "system-ui", "sans-serif"]
                    }
                }
            }
        };
    </script>

    <style>
        @layer base {
            html, body { margin: 0; padding: 0; }
            body { overscroll-behavior: none; }
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Left Sidebar styling identical to sekar.php */
        .sekar-sidebar {
            width: 76px;
            min-width: 76px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            box-shadow: inset -1px 0 0 rgba(17,24,39,.02);
            padding: 14px 10px;
            position: fixed;
            top: 64px;
            left: 0;
            bottom: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .sidebar-title {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            padding: 8px 0;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(46,125,50,.08), rgba(0,137,123,.08));
        }

        .sidebar-title .mini-mark {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            background: linear-gradient(135deg, #2e7d32, #0f766e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .8rem;
            font-weight: 800;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }

        .nav-item {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 1px solid transparent;
            background: transparent;
            color: #111827;
            border-radius: 12px;
            padding: 0;
            font-size: 0;
            cursor: pointer;
            transition: all .15s ease;
            text-decoration: none;
        }

        .nav-item::after {
            content: attr(data-label);
            position: absolute;
            left: 50%;
            bottom: calc(100% + 8px);
            transform: translateX(-50%);
            background: rgba(17, 24, 39, 0.92);
            color: #fff;
            font-size: .68rem;
            font-weight: 600;
            line-height: 1;
            padding: 7px 9px;
            border-radius: 8px;
            opacity: 0;
            pointer-events: none;
            white-space: nowrap;
            transition: opacity .15s ease;
            z-index: 60;
        }

        .nav-item:hover::after {
            opacity: 1;
        }

        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: 1.15rem;
            color: #6b7280;
        }

        .nav-item:hover {
            background: #e8f5e9;
            border-color: rgba(46,125,50,.12);
            color: #1b5e20;
        }

        .nav-item.active {
            background: linear-gradient(135deg, #2e7d32, #00897b);
            color: #fff;
            box-shadow: 0 8px 18px rgba(46,125,50,.2);
        }

        .nav-item.active i {
            color: #fff;
        }

        .content-container {
            margin-left: 76px;
            padding-top: 64px;
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-background font-sans text-on-surface antialiased selection:bg-primary-fixed selection:text-on-primary-fixed">

    <!-- Top Navigation Bar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-surface-container-lowest/95 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.06)] h-16 border-b border-surface-container">
        <div class="h-16 w-full px-4 sm:px-6 flex items-center justify-between gap-4">
            <!-- Brand Info -->
            <div class="flex items-center gap-4 shrink-0">
                <a href="sekar.php" class="flex items-center gap-3 text-inherit no-underline">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#2e7d32] to-[#0f766e] flex items-center justify-center text-white font-extrabold text-lg shadow-sm">
                        P
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-base text-on-surface tracking-tight leading-tight">SEKAR BIOTROP</span>
                            <span class="text-[10px] uppercase font-extrabold px-1.5 py-0.5 rounded bg-primary-fixed text-on-primary-fixed">DSS</span>
                        </div>
                        <span class="text-xs text-on-surface-variant font-medium">Edu-Monitoring &amp; Decision Support System</span>
                    </div>
                </a>

                <div class="hidden xl:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container text-primary text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    Status Data: Realtime Q3 2024 (Aktual)
                </div>
            </div>


            <!-- User Status & Profile -->
            <div class="flex items-center gap-3 shrink-0">
                <?php if ($sudahLogin): ?>
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-xs font-bold text-on-surface leading-tight"><?= htmlspecialchars($userNama) ?></span>
                        <span class="text-[11px] text-on-surface-variant"><?= htmlspecialchars(labelRole($userRole)) ?></span>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#2e7d32] to-[#0f766e] text-white flex items-center justify-center text-xs font-extrabold ring-2 ring-primary-fixed shadow-sm">
                        <?= $userInitial ?>
                    </div>
                    <?php if (($_SESSION['role'] ?? '') !== 'user'): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="w-8 h-8 rounded-lg bg-surface-container text-on-surface flex items-center justify-center hover:bg-surface-container-high transition-colors" title="Admin Dashboard">
                            <i class="bi bi-speedometer2 text-sm"></i>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-xs font-bold text-primary transition-colors">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk Staf
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Left Sidebar (Matching sekar.php) -->
    <aside class="sekar-sidebar">
        <div class="sidebar-title">
            <span class="mini-mark">P</span>
        </div>

        <nav class="sidebar-nav">
            <a href="sekar.php" class="nav-item" data-label="Peta Monitoring">
                <i class="bi bi-map"></i>
            </a>
            <a href="dashboard.php" class="nav-item active" data-label="Dashboard">
                <i class="bi bi-bar-chart"></i>
            </a>
            <button class="nav-item" type="button" data-label="Data Sekolah" onclick="scrollSection('table-ranking')">
                <i class="bi bi-building"></i>
            </button>
            <button class="nav-item" type="button" data-label="Komparasi Radar" onclick="scrollSection('radar-section')">
                <i class="bi bi-radar"></i>
            </button>
            <button class="nav-item" type="button" data-label="Audit Dokumen" onclick="scrollSection('audit-section')">
                <i class="bi bi-shield-check"></i>
            </button>
            <button class="nav-item" type="button" data-label="Simulasi Bobot" onclick="toggleSimModal()">
                <i class="bi bi-sliders"></i>
            </button>
            <a href="sekar.php" class="nav-item" data-label="Kembali ke Peta">
                <i class="bi bi-arrow-left-circle"></i>
            </a>
        </nav>
    </aside>

    <!-- Main Content Body -->
    <main class="content-container flex flex-col w-auto">

        <!-- Page Header & Tactical Filter Bar -->
        <section class="w-full bg-surface-container-lowest shadow-sm px-6 py-5 flex flex-col gap-4 border-b border-surface-container">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1.5 text-on-surface-variant text-xs font-semibold">
                        <span>Sistem Pendukung Keputusan</span>
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                        <span class="text-primary font-bold">Matriks Evaluasi &amp; Pemeringkatan SAW</span>
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                        <span class="text-on-surface">Periode Q3 2024</span>
                    </div>

                    <div class="flex items-center flex-wrap gap-2.5 mt-0.5">
                        <h1 class="text-2xl font-extrabold text-on-surface tracking-tight">Matriks Evaluasi Rubrik DSS &amp; Perangkingan Prioritas</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-bold shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-ping"></span>
                            Algoritma SAW Aktif
                        </span>
                    </div>

                    <p class="text-xs text-on-surface-variant max-w-3xl leading-relaxed">
                        Komparasi multi-kriteria berbasis pembobotan 4 pilar program edukasi berkelanjutan (Smart Urban Farming, GEMS Geopark, &amp; Kultur Jaringan). Menentukan kelayakan replikasi mandiri hibah Tahap II serta memetakan prioritas intervensi CSR terarah.
                    </p>
                </div>

                <!-- Action Button Group -->
                <div class="flex items-center flex-wrap gap-2 shrink-0">
                    <button class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container text-xs font-bold text-on-surface hover:bg-surface-container-high transition-all shadow-sm" id="btn-weight-sim" onclick="toggleSimModal()">
                        <span class="material-symbols-outlined text-base text-primary">tune</span>
                        <span>Simulasi Bobot AHP/SAW</span>
                    </button>
                    <button onclick="exportMatrixCSV()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container text-xs font-bold text-on-surface hover:bg-surface-container-high transition-all shadow-sm">
                        <span class="material-symbols-outlined text-base text-secondary">file_download</span>
                        <span>Ekspor Matriks (CSV)</span>
                    </button>
                    <button class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-container transition-all shadow-sm disabled:opacity-40" id="compare-btn" onclick="openCompareModal()">
                        <span class="material-symbols-outlined text-base">balance</span>
                        <span id="compare-text">Bandingkan Terpilih (0/3)</span>
                    </button>
                </div>
            </div>

            <!-- Cascading Administrative & Criteria Filter Bar -->
            <div class="pt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                <!-- Provinsi -->
                <div class="flex flex-col bg-surface-container-low px-3 py-2 rounded-lg border border-transparent focus-within:border-primary">
                    <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Provinsi</span>
                    <select id="filterProvinsi" onchange="onProvinsiChange()" class="bg-transparent text-xs text-on-surface font-bold focus:outline-none cursor-pointer mt-0.5">
                        <option value="" selected>Semua Provinsi (Seluruh Indonesia)</option>
                    </select>
                </div>

                <!-- Kabupaten / Kota -->
                <div class="flex flex-col bg-surface-container-low px-3 py-2 rounded-lg border border-transparent focus-within:border-primary">
                    <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Kabupaten / Kota</span>
                    <select id="filterKabupaten" onchange="applyFilters()" class="bg-transparent text-xs text-on-surface font-bold focus:outline-none cursor-pointer mt-0.5">
                        <option value="">Semua Kab/Kota</option>
                    </select>
                </div>

                <!-- Track Modul Program -->
                <div class="flex flex-col bg-surface-container-low px-3 py-2 rounded-lg border border-transparent focus-within:border-primary">
                    <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Track Modul Program</span>
                    <select id="filterProgram" onchange="applyFilters()" class="bg-transparent text-xs text-on-surface font-bold focus:outline-none cursor-pointer mt-0.5">
                        <option value="">Semua Program Terpadu</option>
                        <option value="urban">🌱 Smart Urban Farming</option>
                        <option value="gems">⛰️ GEMS (Geopark)</option>
                        <option value="kultur">🧫 Kultur Jaringan</option>
                    </select>
                </div>

                <!-- Tahun Ajaran / Periode Pelatihan -->
                <div class="flex flex-col bg-surface-container-low px-3 py-2 rounded-lg border border-transparent focus-within:border-primary">
                    <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Tahun / Periode Pelatihan</span>
                    <select id="filterPeriode" onchange="applyFilters()" class="bg-transparent text-xs text-on-surface font-bold focus:outline-none cursor-pointer mt-0.5">
                        <option selected>Tahun 2026 (Database HCID Terkini)</option>
                        <option>Semester I 2026</option>
                        <option>Semester II 2026</option>
                    </select>
                </div>

                <!-- Kategori Kemandirian DSS -->
                <div class="flex flex-col bg-surface-container-low px-3 py-2 rounded-lg border border-transparent focus-within:border-primary">
                    <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Kategori Kemandirian DSS</span>
                    <select id="filterKategori" onchange="applyFilters()" class="bg-transparent text-xs text-on-surface font-bold focus:outline-none cursor-pointer mt-0.5">
                        <option value="" selected>Semua Kategori (100%)</option>
                        <option value="mandiri">Mandiri / Replikasi (&ge; 80.0)</option>
                        <option value="berkembang">Berkembang (60.0 - 79.9)</option>
                        <option value="prioritas">Prioritas Intervensi (&lt; 60.0)</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Main Body Space -->
        <div class="w-full px-6 py-6 flex flex-col gap-6">

            <!-- KPI Summary Ribbon Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Unit -->
                <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm relative overflow-hidden flex flex-col justify-between border border-surface-container">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-primary"></div>
                    <div class="flex items-start justify-between">
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Total Unit Dinilai</span>
                        <span class="p-1 rounded-lg bg-surface-container text-primary material-symbols-outlined text-lg">school</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span class="text-2xl font-extrabold text-on-surface" id="kpi-total-unit">...</span>
                        <span class="text-xs text-on-surface-variant">Sekolah Terdata</span>
                    </div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-primary font-semibold">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        <span id="kpi-valid-sub">100% data riil dari 9 batch pelatihan HCID</span>
                    </div>
                </div>

                <!-- Card 2: Rata-rata Skor -->
                <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm relative overflow-hidden flex flex-col justify-between border border-surface-container">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-secondary"></div>
                    <div class="flex items-start justify-between">
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Rata-rata Skor DSS</span>
                        <span class="p-1 rounded-lg bg-surface-container text-secondary material-symbols-outlined text-lg">analytics</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span class="text-2xl font-extrabold text-on-surface" id="kpi-avg-score">—</span>
                        <span class="text-xs text-on-surface-variant">/ 100 Skala SAW</span>
                    </div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-primary font-semibold">
                        <span class="material-symbols-outlined text-sm">trending_up</span>
                        <span>Evaluasi Komposit 4 Pilar</span>
                    </div>
                </div>

                <!-- Card 3: Mandiri -->
                <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm relative overflow-hidden flex flex-col justify-between border border-surface-container">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-primary-container"></div>
                    <div class="flex items-start justify-between">
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Siap Mandiri &amp; Replikasi</span>
                        <span class="p-1 rounded-lg bg-primary-fixed text-on-primary-fixed material-symbols-outlined text-lg">verified</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span class="text-2xl font-extrabold text-on-surface" id="kpi-mandiri-count">—</span>
                        <span class="text-xs text-primary font-bold" id="kpi-mandiri-pct">Sekolah</span>
                    </div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-on-surface-variant">
                        <span>Kandidat Center of Excellence</span>
                    </div>
                </div>

                <!-- Card 4: Intervensi -->
                <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm relative overflow-hidden flex flex-col justify-between border border-surface-container">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-tertiary"></div>
                    <div class="flex items-start justify-between">
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Prioritas Intervensi CSR</span>
                        <span class="p-1 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed material-symbols-outlined text-lg">priority_high</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span class="text-2xl font-extrabold text-tertiary" id="kpi-intervensi-count">—</span>
                        <span class="text-xs text-tertiary font-bold" id="kpi-intervensi-pct">Sekolah</span>
                    </div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-error font-semibold">
                        <span class="material-symbols-outlined text-sm">warning</span>
                        <span>Butuh pendampingan &amp; fasilitasi lanjutan</span>
                    </div>
                </div>
            </div>

            <!-- SAW Weight Allocation Interactive Banner -->
            <div class="bg-surface-container-lowest p-5 rounded-xl shadow-sm flex flex-col gap-4 border border-surface-container">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-2 border-b border-surface-container">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-surface-container text-primary">
                            <span class="material-symbols-outlined text-xl">balance</span>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-on-surface">Struktur Pembobotan Kriteria DSS (Simple Additive Weighting)</h2>
                            <p class="text-xs text-on-surface-variant">Bobot matriks ditentukan melalui normalisasi Analytic Hierarchy Process (AHP) bersama Dewan Ahli Agribisnis &amp; Kemdikbud.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="toggleSimModal()" class="px-3 py-1.5 rounded-lg bg-surface-container text-xs font-bold text-primary hover:bg-surface-container-high transition-colors flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">tune</span> Ubah Bobot
                        </button>
                        <div class="flex items-center gap-1 text-primary text-xs font-semibold">
                            <span class="material-symbols-outlined text-sm">lock</span>
                            <span class="hidden sm:inline">Konfigurasi Terverifikasi</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <!-- Pilar 1 -->
                    <div class="bg-surface-container-low p-4 rounded-lg flex flex-col gap-1 border border-surface-container/60">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-extrabold">Pilar I</span>
                            <span class="px-2 py-0.5 rounded bg-surface-container-high text-xs font-bold text-on-surface" id="p1-badge-weight">Bobot 25%</span>
                        </div>
                        <span class="text-sm font-bold text-on-surface mt-1">Infrastruktur &amp; Fasilitas</span>
                        <p class="text-xs text-on-surface-variant">Greenhouse pintar, sensor IoT, instalasi irigasi tetes, laboratorium kultur jaringan.</p>
                        <div class="mt-2 w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                            <div class="bg-primary h-full rounded-full transition-all duration-300" id="p1-bar" style="width: 25%"></div>
                        </div>
                    </div>

                    <!-- Pilar 2 -->
                    <div class="bg-surface-container-low p-4 rounded-lg flex flex-col gap-1 border border-surface-container/60">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-extrabold">Pilar II</span>
                            <span class="px-2 py-0.5 rounded bg-surface-container-high text-xs font-bold text-on-surface" id="p2-badge-weight">Bobot 30%</span>
                        </div>
                        <span class="text-sm font-bold text-on-surface mt-1">Kurikulum &amp; Guru</span>
                        <p class="text-xs text-on-surface-variant">Modul tematik P5 terintegrasi, jam mengajar guru, sertifikasi TOT pelatihan BIOTROP.</p>
                        <div class="mt-2 w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                            <div class="bg-secondary h-full rounded-full transition-all duration-300" id="p2-bar" style="width: 30%"></div>
                        </div>
                    </div>

                    <!-- Pilar 3 -->
                    <div class="bg-surface-container-low p-4 rounded-lg flex flex-col gap-1 border border-surface-container/60">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-extrabold">Pilar III</span>
                            <span class="px-2 py-0.5 rounded bg-surface-container-high text-xs font-bold text-on-surface" id="p3-badge-weight">Bobot 20%</span>
                        </div>
                        <span class="text-sm font-bold text-on-surface mt-1">Keterlibatan Siswa &amp; Gender</span>
                        <p class="text-xs text-on-surface-variant">Presensi siswa, rasio gender guru terlatih, karya ilmiah &amp; ekstrakurikuler siswa.</p>
                        <div class="mt-2 w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                            <div class="bg-tertiary h-full rounded-full transition-all duration-300" id="p3-bar" style="width: 20%"></div>
                        </div>
                    </div>

                    <!-- Pilar 4 -->
                    <div class="bg-surface-container-low p-4 rounded-lg flex flex-col gap-1 border border-surface-container/60">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-extrabold">Pilar IV</span>
                            <span class="px-2 py-0.5 rounded bg-surface-container-high text-xs font-bold text-on-surface" id="p4-badge-weight">Bobot 25%</span>
                        </div>
                        <span class="text-sm font-bold text-on-surface mt-1">Rencana Aksi &amp; Keberlanjutan</span>
                        <p class="text-xs text-on-surface-variant">Penerapan action plan sekolah, diseminasi modul, dan keberlanjutan pasca-pelatihan.</p>
                        <div class="mt-2 w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                            <div class="bg-primary-container h-full rounded-full transition-all duration-300" id="p4-bar" style="width: 25%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Ranking & DSS Matrix Table -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden flex flex-col border border-surface-container" id="table-ranking">
                <!-- Table Header Bar -->
                <div class="px-5 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-surface-container-low border-b border-surface-container">
                    <div class="flex items-center gap-3">
                        <span class="text-base font-bold text-on-surface">Pemeringkatan Matriks Evaluasi (Ranking DSS)</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-surface-container-highest text-on-surface-variant font-mono font-bold" id="badge-total-count">Memuat data...</span>
                    </div>

                    <div class="flex items-center flex-wrap gap-3">
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined text-on-surface-variant absolute left-2.5 text-base">search</span>
                            <input id="searchInput" oninput="applyFilters()" class="pl-8 pr-3 py-1.5 rounded-lg bg-surface-container-lowest text-xs text-on-surface focus:outline-none focus:ring-1 focus:ring-primary w-60 shadow-sm border border-surface-container" placeholder="Cari nama sekolah / NPSN / kab..." type="text">
                        </div>

                        <div class="flex items-center gap-1.5 text-on-surface-variant text-xs font-semibold">
                            <span>Urutkan:</span>
                            <select id="sortSelect" onchange="applyFilters()" class="bg-transparent font-bold text-on-surface focus:outline-none cursor-pointer">
                                <option value="score_desc">Skor DSS Tertinggi (Desc)</option>
                                <option value="score_asc">Skor DSS Terendah (Asc)</option>
                                <option value="name_asc">Nama Sekolah (A-Z)</option>
                                <option value="rank_asc">Peringkat Prioritas</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1100px]">
                        <thead>
                            <tr class="bg-surface-container text-[11px] text-on-surface-variant uppercase tracking-wider font-bold">
                                <th class="py-3 px-4 w-12 text-center">
                                    <input class="rounded accent-primary cursor-pointer" id="select-all" type="checkbox" onchange="toggleSelectAll(this)">
                                </th>
                                <th class="py-3 px-3 w-16 text-center">Rank</th>
                                <th class="py-3 px-4 min-w-[240px]">Sekolah &amp; Wilayah</th>
                                <th class="py-3 px-3 min-w-[140px]">Program Modul</th>
                                <th class="py-3 px-3 min-w-[110px]" id="th-p1">P1: Sarpras (25%)</th>
                                <th class="py-3 px-3 min-w-[110px]" id="th-p2">P2: Guru (30%)</th>
                                <th class="py-3 px-3 min-w-[130px]" id="th-p3">P3: Siswa/Gender (20%)</th>
                                <th class="py-3 px-3 min-w-[110px]" id="th-p4">P4: Rencana Aksi (25%)</th>
                                <th class="py-3 px-3 min-w-[100px] text-center">Skor DSS</th>
                                <th class="py-3 px-3 min-w-[140px]">Status Kelayakan</th>
                                <th class="py-3 px-4 min-w-[200px]">Rekomendasi Tindakan DSS</th>
                                <th class="py-3 px-4 text-right min-w-[100px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low text-xs text-on-surface" id="matrixTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination & Table Metadata Footer -->
                <div class="px-5 py-3 bg-surface-container-low flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-surface-container">
                    <span class="text-xs text-on-surface-variant" id="table-page-info">
                        Menampilkan <strong class="text-on-surface font-semibold" id="table-count-shown">1 - 6</strong> dari <strong class="text-on-surface font-semibold" id="table-count-total">32</strong> sekolah terdaftar
                    </span>

                    <div class="flex items-center gap-1" id="pagination-controls">
                        <!-- Controls generated via JS -->
                    </div>
                </div>
            </div>

            <!-- Strategic Deep Dive Grid: Radar Comparison + Algorithmic Insights + Audit Verification History -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" id="radar-section">
                <!-- Panel 1: Radar Chart Comparison (5 Cols) -->
                <div class="lg:col-span-5 bg-surface-container-lowest p-6 rounded-xl shadow-sm flex flex-col justify-between border border-surface-container">
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-on-surface">Komparasi Pilar: Top 1 vs Rata-rata Wilayah</h3>
                            <span class="p-1.5 rounded-lg bg-surface-container text-secondary material-symbols-outlined text-base">radar</span>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-1">Evaluasi keseimbangan performa antar 4 pilar rubrik penilaian program sekolah percontohan.</p>
                    </div>

                    <!-- Inline Dynamic SVG Radar Visualization -->
                    <div class="my-5 flex flex-col items-center justify-center">
                        <svg class="w-64 h-64 overflow-visible" viewBox="0 0 200 200" id="radarSvg">
                            <!-- Background Web Polygons -->
                            <polygon fill="none" points="100,10 190,100 100,190 10,100" stroke="#dae2fd" stroke-width="1"></polygon>
                            <polygon fill="none" points="100,32 168,100 100,168 32,100" stroke="#dae2fd" stroke-width="1"></polygon>
                            <polygon fill="none" points="100,55 145,100 100,145 55,100" stroke="#dae2fd" stroke-width="1"></polygon>
                            <polygon fill="none" points="100,77 123,100 100,123 77,100" stroke="#dae2fd" stroke-width="1"></polygon>
                            <!-- Cross Axis Lines -->
                            <line stroke="#bccac0" stroke-dasharray="2,2" stroke-width="1" x1="100" x2="100" y1="10" y2="190"></line>
                            <line stroke="#bccac0" stroke-dasharray="2,2" stroke-width="1" x1="10" x2="190" y1="100" y2="100"></line>
                            <!-- Axis Labels -->
                            <text class="fill-current text-on-surface-variant text-[9px] font-bold" text-anchor="middle" x="100" y="3">P1: Sarpras</text>
                            <text class="fill-current text-on-surface-variant text-[9px] font-bold" text-anchor="start" x="195" y="103">P2: Guru</text>
                            <text class="fill-current text-on-surface-variant text-[9px] font-bold" text-anchor="middle" x="100" y="202">P3: Siswa</text>
                            <text class="fill-current text-on-surface-variant text-[9px] font-bold" text-anchor="end" x="5" y="103">P4: Circular</text>
                            <!-- Area 1: Regional Average (Blue/Secondary) -->
                            <polygon id="radar-area-avg" fill="#cce5ff" fill-opacity="0.45" points="100,28 170,100 100,173 31,100" stroke="#006398" stroke-width="2"></polygon>
                            <!-- Area 2: Top 1 School (Emerald/Primary) -->
                            <polygon id="radar-area-top" fill="#85f8c4" fill-opacity="0.45" points="100,15 182,100 100,181 18,100" stroke="#006948" stroke-width="2.5"></polygon>
                            <!-- Nodes for Top 1 -->
                            <circle id="radar-node-p1" cx="100" cy="15" fill="#006948" r="3.5"></circle>
                            <circle id="radar-node-p2" cx="182" cy="100" fill="#006948" r="3.5"></circle>
                            <circle id="radar-node-p3" cx="100" cy="181" fill="#006948" r="3.5"></circle>
                            <circle id="radar-node-p4" cx="18" cy="100" fill="#006948" r="3.5"></circle>
                        </svg>
                    </div>

                    <!-- Legend Bar -->
                    <div class="pt-2 flex items-center justify-around bg-surface-container-low px-4 py-2 rounded-lg">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm bg-primary"></span>
                            <span class="text-xs font-semibold text-on-surface" id="radar-label-top">SMK N 1 Majalaya (91.9)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm bg-secondary"></span>
                            <span class="text-xs font-semibold text-on-surface" id="radar-label-avg">Rata-rata Wilayah (81.4)</span>
                        </div>
                    </div>
                </div>

                <!-- Panel 2: DSS Algorithmic Recommendations (4 Cols) -->
                <div class="lg:col-span-4 bg-surface-container-lowest p-6 rounded-xl shadow-sm flex flex-col justify-between border border-surface-container">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-on-surface">Insight Algoritma Rekomendasi</h3>
                            <span class="p-1.5 rounded-lg bg-surface-container text-primary material-symbols-outlined text-base">psychology</span>
                        </div>

                        <!-- Insight Card 1: Replikasi Mandiri -->
                        <div class="p-3 bg-surface-container-low rounded-lg flex gap-3 border border-surface-container/60">
                            <div class="mt-0.5 p-1 rounded bg-primary-fixed text-on-primary-fixed material-symbols-outlined text-base shrink-0">workspace_premium</div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-on-surface">Center of Excellence Baru (<span id="insight-coe-count">14</span> Unit)</span>
                                <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">
                                    Sekolah dengan skor &ge;80.0 telah mencapai <strong class="text-on-surface">kemandirian sirkular 84%</strong>. Siap dihubungkan langsung ke program pendanaan kredit mikro CSR hijau tahap II.
                                </p>
                            </div>
                        </div>

                        <!-- Insight Card 2: Intervensi Darurat -->
                        <div class="p-3 bg-error-container/20 rounded-lg flex gap-3 border border-error-container/40">
                            <div class="mt-0.5 p-1 rounded bg-error-container text-on-error-container material-symbols-outlined text-base shrink-0">fmd_bad</div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-error">Bottleneck: Rotasi Guru &amp; Pemeliharaan</span>
                                <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">
                                    Pada <span id="insight-pri-count">6</span> sekolah prioritas intervensi, kendala terbesar adalah ketiadaan guru pembimbing bersertifikat setelah mutasi ASN semester genap.
                                </p>
                            </div>
                        </div>

                        <!-- Insight Card 3: Gender Parity Notice -->
                        <div class="p-3 bg-surface-container-low rounded-lg flex gap-3 border border-surface-container/60">
                            <div class="mt-0.5 p-1 rounded bg-secondary-fixed text-on-secondary-fixed material-symbols-outlined text-base shrink-0">group</div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-on-surface">Keseimbangan Partisipasi Siswa</span>
                                <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">
                                    Rasio keterlibatan siswi putri di struktur pengurus bank sampah mencapai <strong class="text-on-surface">51.4%</strong>, melampaui indikator target SDG 5 (Gender Equality).
                                </p>
                            </div>
                        </div>
                    </div>

                    <button onclick="sendNotificationAlert()" class="mt-4 w-full py-2.5 rounded-lg bg-surface-container text-primary text-xs font-bold hover:bg-surface-container-high transition-colors flex items-center justify-center gap-1.5">
                        <span>Kirim Notifikasi Rekomendasi ke Tim M&amp;E</span>
                        <span class="material-symbols-outlined text-sm">send</span>
                    </button>
                </div>

                <!-- Panel 3: Verifikasi Dokumen Rubrik & Bukti Fisik Lapangan (3 Cols) -->
                <div class="lg:col-span-3 bg-surface-container-lowest p-6 rounded-xl shadow-sm flex flex-col justify-between border border-surface-container" id="audit-section">
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-on-surface">Audit Rubrik Lapangan</h3>
                            <span class="p-1.5 rounded-lg bg-surface-container text-tertiary material-symbols-outlined text-base">verified_user</span>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-1">Status verifikasi berkas bukti fisik Q3 oleh auditor independen.</p>

                        <div class="mt-4 flex flex-col gap-2.5">
                            <!-- Doc 1 -->
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-surface-container-low border border-surface-container/60 hover:bg-surface-container transition-colors">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="material-symbols-outlined text-primary text-lg">description</span>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-semibold truncate text-on-surface">BAST_SMKN1_Majalaya.pdf</span>
                                        <span class="text-[10px] text-on-surface-variant">Telah diverifikasi &bull; Q3 2024</span>
                                    </div>
                                </div>
                                <span class="p-1 rounded bg-primary-fixed text-on-primary-fixed material-symbols-outlined text-xs">done_all</span>
                            </div>

                            <!-- Doc 2 -->
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-surface-container-low border border-surface-container/60 hover:bg-surface-container transition-colors">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="material-symbols-outlined text-secondary text-lg">description</span>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-semibold truncate text-on-surface">Laporan_Keuangan_Banjaran.pdf</span>
                                        <span class="text-[10px] text-on-surface-variant">Telah diverifikasi &bull; Q3 2024</span>
                                    </div>
                                </div>
                                <span class="p-1 rounded bg-primary-fixed text-on-primary-fixed material-symbols-outlined text-xs">done_all</span>
                            </div>

                            <!-- Doc 3 -->
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-surface-container-low border border-surface-container/60 hover:bg-surface-container transition-colors">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="material-symbols-outlined text-tertiary text-lg">pending_actions</span>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-semibold truncate text-on-surface">Foto_Greenhouse_Baleendah.zip</span>
                                        <span class="text-[10px] text-error font-medium">Revisi berkas diminta</span>
                                    </div>
                                </div>
                                <span class="p-1 rounded bg-error-container text-on-error-container material-symbols-outlined text-xs">priority_high</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-surface-container flex items-center justify-between text-xs text-on-surface-variant font-medium">
                        <span>Kelengkapan Berkas:</span>
                        <strong class="text-on-surface font-bold">29 / 32 Sekolah (90.6%)</strong>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Slide-out Drawer: School Rubric Detail Inspection -->
    <div class="fixed inset-y-0 right-0 w-full sm:w-[520px] bg-surface-container-lowest shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between border-l border-surface-container" id="school-detail-drawer">
        <!-- Drawer Header -->
        <div class="p-5 bg-surface-container-low flex items-start justify-between border-b border-surface-container">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-bold" id="drawer-badge">Mandiri / Unggul</span>
                    <span class="text-xs text-on-surface-variant font-mono" id="drawer-npsn">NPSN: 20228391</span>
                </div>
                <h3 class="text-xl font-bold text-on-surface" id="drawer-school-name">SMK Negeri 1 Majalaya</h3>
                <span class="text-xs text-on-surface-variant" id="drawer-school-loc">Kec. Majalaya, Kab. Bandung</span>
            </div>
            <button class="p-1.5 rounded-full hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors" onclick="closeDrawer()">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Drawer Body (Scrollable) -->
        <div class="p-5 overflow-y-auto flex-1 flex flex-col gap-5">
            <!-- Composite Score Badge Header -->
            <div class="p-4 rounded-xl bg-surface-container flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Skor Gabungan Terbobot (SAW)</span>
                    <span class="text-2xl font-extrabold text-primary" id="drawer-composite-score">91.9 / 100</span>
                    <span class="text-xs text-on-surface-variant">Memenuhi kualifikasi hibah peralatan lanjutan</span>
                </div>
                <div class="w-14 h-14 rounded-full bg-primary-container text-white flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-3xl">emoji_events</span>
                </div>
            </div>

            <!-- Real Documentation Implementation Photo Thumbnail -->
            <div class="flex flex-col gap-1.5">
                <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Bukti Lapangan Pelaksanaan Q3</span>
                <div class="h-40 w-full rounded-lg overflow-hidden relative shadow-sm border border-surface-container">
                    <img id="drawer-photo" class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=800&auto=format&fit=crop&q=80" alt="Greenhouse Sekolah">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent flex items-end p-3">
                        <span class="text-white text-xs font-semibold" id="drawer-photo-caption">Instalasi Smart Greenhouse &amp; Pembibitan Siswa Mandiri</span>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown of 4 Pillars -->
            <div class="flex flex-col gap-2.5">
                <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Rincian 4 Pilar Penilaian Rubrik</span>

                <!-- Pillar 1 -->
                <div class="p-3 rounded-lg bg-surface-container-low flex flex-col gap-1 border border-surface-container/60">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-on-surface">Pilar 1: Sarana Prasarana (Bobot <span class="w-p1-text">25%</span>)</span>
                        <span class="text-xs font-bold text-primary" id="drawer-p1">94.0%</span>
                    </div>
                    <div class="w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                        <div class="bg-primary h-full rounded-full transition-all duration-300" id="drawer-p1-bar" style="width: 94%"></div>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Irigasi tetes beroperasi otomatis, sensor kelembaban tanah aktif 24/7.</p>
                </div>

                <!-- Pillar 2 -->
                <div class="p-3 rounded-lg bg-surface-container-low flex flex-col gap-1 border border-surface-container/60">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-on-surface">Pilar 2: Guru &amp; Kurikulum (Bobot <span class="w-p2-text">30%</span>)</span>
                        <span class="text-xs font-bold text-secondary" id="drawer-p2">92.0%</span>
                    </div>
                    <div class="w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                        <div class="bg-secondary h-full rounded-full transition-all duration-300" id="drawer-p2-bar" style="width: 92%"></div>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Guru mengantongi sertifikat TOT Urban Farming Nasional.</p>
                </div>

                <!-- Pillar 3 -->
                <div class="p-3 rounded-lg bg-surface-container-low flex flex-col gap-1 border border-surface-container/60">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-on-surface">Pilar 3: Keterlibatan Siswa &amp; Gender (Bobot <span class="w-p3-text">20%</span>)</span>
                        <span class="text-xs font-bold text-tertiary" id="drawer-p3">90.5%</span>
                    </div>
                    <div class="w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                        <div class="bg-tertiary h-full rounded-full transition-all duration-300" id="drawer-p3-bar" style="width: 90.5%"></div>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Partisipasi siswa aktif; rasio kepengurusan gender seimbang.</p>
                </div>

                <!-- Pillar 4 -->
                <div class="p-3 rounded-lg bg-surface-container-low flex flex-col gap-1 border border-surface-container/60">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-on-surface">Pilar 4: Kemandirian Sirkular (Bobot <span class="w-p4-text">25%</span>)</span>
                        <span class="text-xs font-bold text-primary-container" id="drawer-p4">91.0%</span>
                    </div>
                    <div class="w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                        <div class="bg-primary-container h-full rounded-full transition-all duration-300" id="drawer-p4-bar" style="width: 91%"></div>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Penjualan hasil panen rutin masuk ke kas operasional mandiri sekolah.</p>
                </div>
            </div>

            <!-- Rubric Action Plan Checkpoints -->
            <div class="flex flex-col gap-2">
                <span class="text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Checklist Rekomendasi Terprogram</span>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 p-2.5 rounded bg-surface-container-low border border-surface-container/60 cursor-pointer">
                        <input checked class="accent-primary rounded" type="checkbox">
                        <span class="text-xs text-on-surface">Pengajuan Replikasi Skala Kawasan (Hibah CSR Tahap 2)</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded bg-surface-container-low border border-surface-container/60 cursor-pointer">
                        <input checked class="accent-primary rounded" type="checkbox">
                        <span class="text-xs text-on-surface">Pelibatan sebagai Narasumber Studi Banding 5 Sekolah Binaan Baru</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded bg-surface-container-low border border-surface-container/60 cursor-pointer">
                        <input class="accent-primary rounded" type="checkbox">
                        <span class="text-xs text-on-surface">Penyusunan Kurikulum Muatan Lokal Vokasi Agribisnis Berkelanjutan</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Drawer Footer Actions -->
        <div class="p-4 bg-surface-container-low flex items-center justify-between gap-3 border-t border-surface-container">
            <button class="px-4 py-2 rounded-lg bg-surface-container text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors" onclick="closeDrawer()">
                Tutup Panel
            </button>
            <div class="flex items-center gap-2">
                <button id="drawer-btn-gis" class="px-3.5 py-2 rounded-lg bg-secondary text-white text-xs font-bold hover:bg-secondary/90 transition-colors flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-base">pin_drop</span>
                    <span>Sorot di Peta GIS</span>
                </button>
                <button onclick="downloadRubricPdf()" class="px-3.5 py-2 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary-container transition-colors flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-base">download</span>
                    <span>Unduh Berkas</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Backdrop for Slide-out Drawer -->
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 hidden transition-opacity" id="drawer-backdrop" onclick="closeDrawer()"></div>

    <!-- Interactive Weight Simulation Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm hidden" id="weight-sim-modal">
        <div class="bg-surface-container-lowest rounded-2xl max-w-lg w-full p-6 shadow-2xl flex flex-col gap-4 border border-surface-container">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-primary-fixed text-on-primary-fixed material-symbols-outlined text-lg">tune</span>
                    <div>
                        <h3 class="text-base font-extrabold text-on-surface">Simulasi Bobot Kriteria DSS (AHP/SAW)</h3>
                        <p class="text-xs text-on-surface-variant">Sesuaikan bobot prioritas untuk kalkulasi ulang perangkingan.</p>
                    </div>
                </div>
                <button class="p-1 rounded-full hover:bg-surface-container text-on-surface-variant" onclick="toggleSimModal()">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Total indicator -->
            <div class="p-3 rounded-xl bg-surface-container-low flex items-center justify-between">
                <span class="text-xs text-on-surface-variant font-semibold">Total Akumulasi Bobot:</span>
                <span class="text-sm font-extrabold text-primary" id="sim-total-weight">100% (Sesuai)</span>
            </div>

            <div class="flex flex-col gap-4 pt-1">
                <!-- P1 -->
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-on-surface">Pilar 1: Infrastruktur &amp; Sarpras</span>
                        <span class="font-bold text-primary" id="val-p1">25%</span>
                    </div>
                    <input class="w-full accent-primary cursor-pointer" id="range-p1" max="50" min="5" oninput="onSimSliderChange()" type="range" value="25">
                </div>

                <!-- P2 -->
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-on-surface">Pilar 2: Integrasi Kurikulum &amp; Guru</span>
                        <span class="font-bold text-secondary" id="val-p2">30%</span>
                    </div>
                    <input class="w-full accent-secondary cursor-pointer" id="range-p2" max="50" min="5" oninput="onSimSliderChange()" type="range" value="30">
                </div>

                <!-- P3 -->
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-on-surface">Pilar 3: Keterlibatan Siswa &amp; Gender</span>
                        <span class="font-bold text-tertiary" id="val-p3">20%</span>
                    </div>
                    <input class="w-full accent-tertiary cursor-pointer" id="range-p3" max="50" min="5" oninput="onSimSliderChange()" type="range" value="20">
                </div>

                <!-- P4 -->
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-on-surface">Pilar 4: Keberlanjutan Circular Economy</span>
                        <span class="font-bold text-primary-container" id="val-p4">25%</span>
                    </div>
                    <input class="w-full accent-primary-container cursor-pointer" id="range-p4" max="50" min="5" oninput="onSimSliderChange()" type="range" value="25">
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-surface-container">
                <button onclick="resetSimWeights()" class="text-xs text-on-surface-variant hover:text-on-surface underline">
                    Reset Default (25-30-20-25)
                </button>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 rounded-lg bg-surface-container text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors" onclick="toggleSimModal()">Batal</button>
                    <button class="px-4 py-2 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary-container transition-colors shadow-sm" onclick="applySimWeights()">Terapkan Simulasi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-School Comparison Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm hidden" id="compare-modal">
        <div class="bg-surface-container-lowest rounded-2xl max-w-4xl w-full p-6 shadow-2xl flex flex-col gap-4 border border-surface-container max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-surface-container">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-secondary-fixed text-on-secondary-fixed material-symbols-outlined text-lg">balance</span>
                    <div>
                        <h3 class="text-base font-extrabold text-on-surface">Komparasi Multi-Sekolah Terpilih</h3>
                        <p class="text-xs text-on-surface-variant">Perbandingan 4 pilar evaluasi rubrik dan kelayakan mandiri.</p>
                    </div>
                </div>
                <button class="p-1 rounded-full hover:bg-surface-container text-on-surface-variant" onclick="closeCompareModal()">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Comparison Table Container -->
            <div class="w-full overflow-x-auto" id="compare-modal-content">
                <!-- Dynamically filled -->
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-surface-container">
                <button class="px-4 py-2 rounded-lg bg-surface-container text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors" onclick="closeCompareModal()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-notify" class="fixed bottom-6 right-6 z-50 bg-on-surface text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-2.5 text-xs font-semibold transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
        <span class="material-symbols-outlined text-primary-fixed" id="toast-icon">check_circle</span>
        <span id="toast-message">Operasi berhasil.</span>
    </div>

    <!-- Interactive Logic Script -->
    <script>
        // Full Dataset: dimuat secara dinamis dari database biotrop_gis_kegiatan
        let DATA_SEKOLAH = [];

        // Active SAW Weights (Initial AHP Normalized)
        let weights = {
            w1: 0.25, // Sarpras
            w2: 0.30, // Guru
            w3: 0.20, // Siswa
            w4: 0.25  // Rencana Aksi
        };

        let calculatedSchools = [];
        let filteredSchools = [];
        let currentPage = 1;
        const pageSize = 10;
        let selectedSchoolIds = new Set();
        let isLoading = true;

        // Load data sekolah dinamis dari endpoint SEKAR
        async function loadDashboardData() {
            const tbody = document.getElementById('matrixTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12" class="py-12 text-center text-on-surface-variant">
                            <div class="inline-flex items-center gap-2 text-sm font-semibold">
                                <span class="material-symbols-outlined animate-spin text-primary">sync</span>
                                <span>Memuat data sebaran sekolah dari database pelatihan HCID...</span>
                            </div>
                        </td>
                    </tr>`;
            }

            try {
                const resp = await fetch('api/get_sekar_data.php');
                const res = await resp.json();
                if (res.success && Array.isArray(res.data)) {
                    DATA_SEKOLAH = res.data;
                    isLoading = false;
                    populateProvinsiDropdown();
                    recalculateScores();
                    applyFilters();
                } else {
                    showToast("Gagal memuat data sekolah dari server.");
                }
            } catch (err) {
                console.error("Error loading SEKAR data:", err);
                showToast("Koneksi gagal saat mengambil data dari database.");
            }
        }

        // Calculate SAW composite score for each school
        function recalculateScores() {
            calculatedSchools = DATA_SEKOLAH.map(s => {
                const sawScore = (s.p1 * weights.w1) + (s.p2 * weights.w2) + (s.p3 * weights.w3) + (s.p4 * weights.w4);
                const roundedScore = Math.round(sawScore * 10) / 10;

                let category = "Berkembang";
                if (roundedScore >= 80.0) category = "Mandiri / Unggul";
                else if (roundedScore < 60.0) category = "Prioritas Intervensi";

                return {
                    ...s,
                    score: roundedScore,
                    category: category
                };
            });

            // Sort by default score desc
            calculatedSchools.sort((a, b) => b.score - a.score);

            // Assign rank
            calculatedSchools.forEach((s, idx) => {
                s.rank = idx + 1;
            });
        }

        // Populate Provinsi dropdown dynamically
        function populateProvinsiDropdown() {
            const provSelect = document.getElementById('filterProvinsi');
            if (!provSelect) return;
            const provList = [...new Set(DATA_SEKOLAH.map(s => s.prov))].filter(Boolean).sort();
            provSelect.innerHTML = '<option value="" selected>Semua Provinsi (Seluruh Indonesia)</option>';
            provList.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                provSelect.appendChild(opt);
            });
        }

        // Populate Kabupaten options according to selected Provinsi
        function onProvinsiChange() {
            const prov = document.getElementById('filterProvinsi').value;
            const kabSelect = document.getElementById('filterKabupaten');
            kabSelect.innerHTML = '<option value="">Semua Kab/Kota</option>';

            const kabList = [...new Set(
                DATA_SEKOLAH
                    .filter(s => !prov || s.prov === prov)
                    .map(s => s.kab)
            )].filter(Boolean).sort();

            kabList.forEach(kab => {
                const opt = document.createElement('option');
                opt.value = kab;
                opt.textContent = kab;
                kabSelect.appendChild(opt);
            });

            applyFilters();
        }

        // Apply all filters and render
        function applyFilters() {
            const prov = document.getElementById('filterProvinsi').value;
            const kab = document.getElementById('filterKabupaten').value;
            const prog = document.getElementById('filterProgram').value;
            const kat = document.getElementById('filterKategori').value;
            const search = document.getElementById('searchInput').value.trim().toLowerCase();
            const sort = document.getElementById('sortSelect').value;

            filteredSchools = calculatedSchools.filter(s => {
                if (prov && s.prov !== prov) return false;
                if (kab && s.kab !== kab) return false;
                if (prog && !s.tracks.includes(prog)) return false;
                if (kat) {
                    if (kat === 'mandiri' && s.score < 80.0) return false;
                    if (kat === 'berkembang' && (s.score < 60.0 || s.score >= 80.0)) return false;
                    if (kat === 'prioritas' && s.score >= 60.0) return false;
                }
                if (search) {
                    const matchName = s.nama.toLowerCase().includes(search);
                    const matchNpsn = s.npsn.includes(search);
                    const matchKab = s.kab.toLowerCase().includes(search);
                    const matchKec = s.kec.toLowerCase().includes(search);
                    if (!matchName && !matchNpsn && !matchKab && !matchKec) return false;
                }
                return true;
            });

            // Sorting
            if (sort === 'score_desc') {
                filteredSchools.sort((a, b) => b.score - a.score);
            } else if (sort === 'score_asc') {
                filteredSchools.sort((a, b) => a.score - b.score);
            } else if (sort === 'name_asc') {
                filteredSchools.sort((a, b) => a.nama.localeCompare(b.nama));
            } else if (sort === 'rank_asc') {
                filteredSchools.sort((a, b) => a.rank - b.rank);
            }

            currentPage = 1;
            renderKPIs();
            renderTable();
            renderRadar();
            updateCompareButton();
        }

        // Render KPI Ribbon Cards
        function renderKPIs() {
            const total = filteredSchools.length;
            const avg = total ? (filteredSchools.reduce((acc, s) => acc + s.score, 0) / total).toFixed(1) : "0.0";
            const mandiri = filteredSchools.filter(s => s.score >= 80.0).length;
            const intervensi = filteredSchools.filter(s => s.score < 60.0).length;

            const mandiriPct = total ? ((mandiri / total) * 100).toFixed(1) : "0.0";
            const intervensiPct = total ? ((intervensi / total) * 100).toFixed(1) : "0.0";

            document.getElementById('kpi-total-unit').innerText = total;
            document.getElementById('kpi-avg-score').innerText = avg;
            document.getElementById('kpi-mandiri-count').innerText = mandiri;
            document.getElementById('kpi-mandiri-pct').innerText = `Sekolah (${mandiriPct}%)`;
            document.getElementById('kpi-intervensi-count').innerText = intervensi;
            document.getElementById('kpi-intervensi-pct').innerText = `Sekolah (${intervensiPct}%)`;

            document.getElementById('badge-total-count').innerText = `Total ${total} Sekolah`;
            document.getElementById('insight-coe-count').innerText = mandiri;
            document.getElementById('insight-pri-count').innerText = intervensi;
        }

        // Render Matrix Table
        function renderTable() {
            const tbody = document.getElementById('matrixTableBody');
            tbody.innerHTML = '';

            const total = filteredSchools.length;
            if (total === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12" class="py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl opacity-40 block mb-2">search_off</span>
                            <span class="text-sm font-semibold">Tidak ada sekolah yang cocok dengan kriteria filter saat ini.</span>
                            <p class="text-xs opacity-70 mt-1">Coba sesuaikan filter provinsi, kabupaten, atau kata kunci pencarian.</p>
                        </td>
                    </tr>`;
                document.getElementById('table-count-shown').innerText = "0";
                document.getElementById('table-count-total').innerText = "0";
                document.getElementById('pagination-controls').innerHTML = '';
                return;
            }

            const startIdx = (currentPage - 1) * pageSize;
            const endIdx = Math.min(startIdx + pageSize, total);
            const pageSchools = filteredSchools.slice(startIdx, endIdx);

            document.getElementById('table-count-shown').innerText = `${startIdx + 1} - ${endIdx}`;
            document.getElementById('table-count-total').innerText = total;

            pageSchools.forEach(sk => {
                const tr = document.createElement('tr');
                const isPriority = sk.score < 60.0;
                tr.className = `hover:bg-surface-container-low/60 transition-colors group ${isPriority ? 'bg-error-container/15' : ''}`;

                // Rank badge styling
                let rankBadge = `<span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-surface-container text-on-surface font-extrabold text-xs shadow-sm">${sk.rank}</span>`;
                if (sk.rank === 1) {
                    rankBadge = `<span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-fixed text-on-primary-fixed font-extrabold text-xs shadow-sm ring-1 ring-primary/30">1</span>`;
                } else if (sk.rank === 2) {
                    rankBadge = `<span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-secondary-fixed text-on-secondary-fixed font-extrabold text-xs shadow-sm ring-1 ring-secondary/30">2</span>`;
                } else if (sk.rank === 3) {
                    rankBadge = `<span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-extrabold text-xs shadow-sm ring-1 ring-tertiary/30">3</span>`;
                }

                // Program chips
                const trackChips = (sk.tracks || []).map(t => {
                    if (t === 'urban') return `<span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">🌱 Urban Farming</span>`;
                    if (t === 'gems') return `<span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">⛰️ GEMS</span>`;
                    if (t === 'kultur') return `<span class="px-2 py-0.5 rounded bg-sky-100 text-sky-800 text-[10px] font-bold">🧫 Kultur Jaringan</span>`;
                    return `<span class="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant text-[10px] font-semibold">${t}</span>`;
                }).join(' ');

                // Gender split parsing
                const [gL, gP] = sk.gender.split(':').map(Number);

                // Status Badge
                let statusBadge = '';
                if (sk.score >= 80.0) {
                    statusBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary-fixed text-on-primary-fixed text-[11px] font-bold"><span class="material-symbols-outlined text-xs">verified</span> Mandiri / Unggul</span>`;
                } else if (sk.score >= 60.0) {
                    statusBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed text-[11px] font-semibold"><span class="material-symbols-outlined text-xs">sync</span> Berkembang</span>`;
                } else {
                    statusBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-error-container text-on-error-container text-[11px] font-bold"><span class="material-symbols-outlined text-xs">warning</span> Prioritas Intervensi</span>`;
                }

                const isChecked = selectedSchoolIds.has(sk.id);

                tr.innerHTML = `
                    <td class="py-3 px-4 text-center">
                        <input class="school-checkbox rounded accent-primary cursor-pointer" type="checkbox" value="${sk.id}" ${isChecked ? 'checked' : ''} onchange="onSchoolCheckChange(this, ${sk.id})">
                    </td>
                    <td class="py-3 px-3 text-center">
                        ${rankBadge}
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex flex-col">
                            <span class="font-bold text-on-surface group-hover:text-primary transition-colors cursor-pointer" onclick="openDrawerById(${sk.id})">${sk.nama}</span>
                            <div class="flex items-center gap-1.5 text-on-surface-variant text-[11px] mt-0.5">
                                <span class="font-mono">NPSN: ${sk.npsn}</span>
                                <span>&bull;</span>
                                <span>${sk.kec}, ${sk.kab}</span>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <div class="flex flex-wrap gap-1">
                            ${trackChips}
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-xs">${sk.p1.toFixed(1)}</span>
                            <div class="w-16 bg-surface-container-high h-1.5 rounded-full overflow-hidden">
                                <div class="bg-primary h-full" style="width: ${sk.p1}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-xs">${sk.p2.toFixed(1)}</span>
                            <div class="w-16 bg-surface-container-high h-1.5 rounded-full overflow-hidden">
                                <div class="bg-secondary h-full" style="width: ${sk.p2}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <div class="flex flex-col gap-1">
                            <div class="flex justify-between items-center text-[10px]">
                                <span class="font-bold">${sk.p3.toFixed(1)}</span>
                                <span class="text-on-surface-variant font-mono">${sk.gender}</span>
                            </div>
                            <div class="w-20 bg-surface-container-high h-1.5 rounded-full overflow-hidden flex">
                                <div class="bg-secondary h-full" style="width: ${gL}%" title="Laki-laki ${gL}%"></div>
                                <div class="bg-tertiary-fixed-dim h-full" style="width: ${gP}%" title="Perempuan ${gP}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-xs">${sk.p4.toFixed(1)}</span>
                            <div class="w-16 bg-surface-container-high h-1.5 rounded-full overflow-hidden">
                                <div class="bg-primary-container h-full" style="width: ${sk.p4}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-3 text-center">
                        <div class="inline-flex flex-col items-center">
                            <span class="px-2.5 py-1 rounded-lg ${sk.score >= 80 ? 'bg-primary text-white' : (sk.score < 60 ? 'bg-error-container text-on-error-container font-extrabold' : 'bg-surface-container font-bold text-on-surface')} text-xs shadow-sm">
                                ${sk.score.toFixed(1)}
                            </span>
                            ${sk.rank <= 3 ? `<span class="text-[10px] font-bold ${sk.rank === 1 ? 'text-primary' : (sk.rank === 2 ? 'text-secondary' : 'text-tertiary')}">Top ${sk.rank}</span>` : ''}
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        ${statusBadge}
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex flex-col">
                            <span class="font-bold text-on-surface ${isPriority ? 'text-error' : ''}">${sk.rekomendasi_title}</span>
                            <span class="text-[11px] text-on-surface-variant mt-0.5">${sk.rekomendasi_sub}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button class="p-1.5 rounded-lg hover:bg-surface-container text-primary transition-colors" onclick="openDrawerById(${sk.id})" title="Detail Evaluasi Rubrik">
                                <span class="material-symbols-outlined text-base">assignment</span>
                            </button>
                            <a href="sekar.php?sekolah=${encodeURIComponent(sk.nama)}" class="p-1.5 rounded-lg hover:bg-surface-container text-secondary transition-colors" title="Lihat Lokasi di GIS">
                                <span class="material-symbols-outlined text-base">map</span>
                            </a>
                        </div>
                    </td>
                `;

                tbody.appendChild(tr);
            });

            renderPagination(total);
        }

        // Render Pagination Controls (dengan Smart Windowing)
        function renderPagination(total) {
            const pageContainer = document.getElementById('pagination-controls');
            pageContainer.innerHTML = '';

            const totalPages = Math.ceil(total / pageSize);
            if (totalPages <= 1) return;

            // Prev Button
            const prevBtn = document.createElement('button');
            prevBtn.className = "px-3 py-1 rounded bg-surface-container-lowest text-on-surface text-xs font-semibold shadow-sm hover:bg-surface-container disabled:opacity-40";
            prevBtn.innerText = "Sebelumnya";
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; renderTable(); } };
            pageContainer.appendChild(prevBtn);

            // Windowed page numbers
            const getPageRange = (curr, max) => {
                if (max <= 7) return Array.from({ length: max }, (_, i) => i + 1);
                if (curr <= 4) return [1, 2, 3, 4, 5, '...', max];
                if (curr >= max - 3) return [1, '...', max - 4, max - 3, max - 2, max - 1, max];
                return [1, '...', curr - 1, curr, curr + 1, '...', max];
            };

            const pages = getPageRange(currentPage, totalPages);
            pages.forEach(p => {
                if (p === '...') {
                    const span = document.createElement('span');
                    span.className = "px-2 py-1 text-xs text-on-surface-variant font-bold";
                    span.innerText = "...";
                    pageContainer.appendChild(span);
                } else {
                    const pBtn = document.createElement('button');
                    pBtn.className = `px-3 py-1 rounded text-xs font-bold shadow-sm ${p === currentPage ? 'bg-primary text-white' : 'bg-surface-container-lowest text-on-surface hover:bg-surface-container'}`;
                    pBtn.innerText = p;
                    pBtn.onclick = () => { currentPage = p; renderTable(); };
                    pageContainer.appendChild(pBtn);
                }
            });

            // Next Button
            const nextBtn = document.createElement('button');
            nextBtn.className = "px-3 py-1 rounded bg-surface-container-lowest text-on-surface text-xs font-semibold shadow-sm hover:bg-surface-container disabled:opacity-40";
            nextBtn.innerText = "Berikutnya";
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; renderTable(); } };
            pageContainer.appendChild(nextBtn);
        }

        // Radar Chart updates
        function renderRadar() {
            if (!filteredSchools.length) return;

            const topSchool = filteredSchools[0];
            const avgP1 = filteredSchools.reduce((a, s) => a + s.p1, 0) / filteredSchools.length;
            const avgP2 = filteredSchools.reduce((a, s) => a + s.p2, 0) / filteredSchools.length;
            const avgP3 = filteredSchools.reduce((a, s) => a + s.p3, 0) / filteredSchools.length;
            const avgP4 = filteredSchools.reduce((a, s) => a + s.p4, 0) / filteredSchools.length;

            const calcPoints = (p1, p2, p3, p4) => {
                const y1 = (100 - (p1 / 100 * 90)).toFixed(1);
                const x2 = (100 + (p2 / 100 * 90)).toFixed(1);
                const y3 = (100 + (p3 / 100 * 90)).toFixed(1);
                const x4 = (100 - (p4 / 100 * 90)).toFixed(1);
                return { points: `100,${y1} ${x2},100 100,${y3} ${x4},100`, y1, x2, y3, x4 };
            };

            const topCoords = calcPoints(topSchool.p1, topSchool.p2, topSchool.p3, topSchool.p4);
            const avgCoords = calcPoints(avgP1, avgP2, avgP3, avgP4);

            document.getElementById('radar-area-top').setAttribute('points', topCoords.points);
            document.getElementById('radar-area-avg').setAttribute('points', avgCoords.points);

            document.getElementById('radar-node-p1').setAttribute('cy', topCoords.y1);
            document.getElementById('radar-node-p2').setAttribute('cx', topCoords.x2);
            document.getElementById('radar-node-p3').setAttribute('cy', topCoords.y3);
            document.getElementById('radar-node-p4').setAttribute('cx', topCoords.x4);

            document.getElementById('radar-label-top').innerText = `${topSchool.nama} (${topSchool.score.toFixed(1)})`;
            document.getElementById('radar-label-avg').innerText = `Rata-rata (${(filteredSchools.reduce((a, s) => a + s.score, 0) / filteredSchools.length).toFixed(1)})`;
        }

        // Selection & Comparison logic
        function onSchoolCheckChange(checkbox, id) {
            if (checkbox.checked) {
                if (selectedSchoolIds.size >= 3) {
                    showToast("Maksimal komparasi adalah 3 sekolah secara bersamaan.");
                    checkbox.checked = false;
                    return;
                }
                selectedSchoolIds.add(id);
            } else {
                selectedSchoolIds.delete(id);
            }
            updateCompareButton();
        }

        function toggleSelectAll(selectAllBox) {
            if (selectAllBox.checked) {
                selectedSchoolIds.clear();
                const limit = Math.min(3, filteredSchools.length);
                for (let i = 0; i < limit; i++) {
                    selectedSchoolIds.add(filteredSchools[i].id);
                }
            } else {
                selectedSchoolIds.clear();
            }
            renderTable();
            updateCompareButton();
        }

        function updateCompareButton() {
            const count = selectedSchoolIds.size;
            const btn = document.getElementById('compare-btn');
            const text = document.getElementById('compare-text');

            text.innerText = `Bandingkan Terpilih (${count}/3)`;
            if (count > 0) {
                btn.disabled = false;
                btn.classList.remove('bg-primary');
                btn.classList.add('bg-primary-container', 'ring-2', 'ring-primary-fixed');
            } else {
                btn.classList.remove('bg-primary-container', 'ring-2', 'ring-primary-fixed');
                btn.classList.add('bg-primary');
            }
        }

        function openCompareModal() {
            if (selectedSchoolIds.size === 0) {
                showToast("Pilih minimal 1 - 3 sekolah dari tabel dengan mencentang kotak.");
                return;
            }

            const targetSchools = calculatedSchools.filter(s => selectedSchoolIds.has(s.id));
            const container = document.getElementById('compare-modal-content');

            let html = `
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container font-bold text-on-surface-variant uppercase">
                            <th class="p-3 w-40">Metrik Penilaian</th>
                            ${targetSchools.map(s => `<th class="p-3 min-w-[200px] text-center font-bold text-on-surface">${s.nama}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low text-on-surface">
                        <tr>
                            <td class="p-3 font-bold bg-surface-container-low">Peringkat &amp; Skor SAW</td>
                            ${targetSchools.map(s => `
                                <td class="p-3 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full font-extrabold text-sm bg-primary text-white">Rank #${s.rank} &bull; ${s.score.toFixed(1)}</span>
                                </td>
                            `).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-semibold bg-surface-container-low">Status Kemandirian</td>
                            ${targetSchools.map(s => `<td class="p-3 text-center font-bold">${s.category}</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-semibold bg-surface-container-low">P1: Sarpras (${(weights.w1*100).toFixed(0)}%)</td>
                            ${targetSchools.map(s => `<td class="p-3 text-center font-bold text-primary">${s.p1.toFixed(1)}%</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-semibold bg-surface-container-low">P2: Guru &amp; Kurikulum (${(weights.w2*100).toFixed(0)}%)</td>
                            ${targetSchools.map(s => `<td class="p-3 text-center font-bold text-secondary">${s.p2.toFixed(1)}%</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-semibold bg-surface-container-low">P3: Siswa &amp; Gender (${(weights.w3*100).toFixed(0)}%)</td>
                            ${targetSchools.map(s => `<td class="p-3 text-center font-bold text-tertiary">${s.p3.toFixed(1)}% (L:P ${s.gender})</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-semibold bg-surface-container-low">P4: Rencana Aksi &amp; Keberlanjutan (${(weights.w4*100).toFixed(0)}%)</td>
                            ${targetSchools.map(s => `<td class="p-3 text-center font-bold text-primary-container">${s.p4.toFixed(1)}%</td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-semibold bg-surface-container-low">Rekomendasi Tindakan</td>
                            ${targetSchools.map(s => `<td class="p-3 text-center text-xs"><strong class="block">${s.rekomendasi_title}</strong><span class="text-on-surface-variant">${s.rekomendasi_sub}</span></td>`).join('')}
                        </tr>
                        <tr>
                            <td class="p-3 font-semibold bg-surface-container-low">Aksi GIS</td>
                            ${targetSchools.map(s => `<td class="p-3 text-center"><a href="sekar.php?sekolah=${encodeURIComponent(s.nama)}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-secondary text-white font-bold hover:bg-secondary/90 transition-colors"><i class="bi bi-pin-map"></i> Buka Peta</a></td>`).join('')}
                        </tr>
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
            document.getElementById('compare-modal').classList.remove('hidden');
        }

        function closeCompareModal() {
            document.getElementById('compare-modal').classList.add('hidden');
        }

        // Drawer Detail Inspection
        function openDrawerById(id) {
            const sk = calculatedSchools.find(s => s.id === id);
            if (!sk) return;

            document.getElementById('drawer-school-name').innerText = sk.nama;
            document.getElementById('drawer-npsn').innerText = `NPSN: ${sk.npsn}`;
            document.getElementById('drawer-school-loc').innerText = `Kec. ${sk.kec}, ${sk.kab}, ${sk.prov}`;
            document.getElementById('drawer-composite-score').innerText = `${sk.score.toFixed(1)} / 100`;
            document.getElementById('drawer-badge').innerText = sk.category;

            if (sk.photo) {
                document.getElementById('drawer-photo').src = sk.photo;
            }

            // Pillars
            document.getElementById('drawer-p1').innerText = `${sk.p1.toFixed(1)}%`;
            document.getElementById('drawer-p1-bar').style.width = `${sk.p1}%`;
            document.getElementById('drawer-p2').innerText = `${sk.p2.toFixed(1)}%`;
            document.getElementById('drawer-p2-bar').style.width = `${sk.p2}%`;
            document.getElementById('drawer-p3').innerText = `${sk.p3.toFixed(1)}%`;
            document.getElementById('drawer-p3-bar').style.width = `${sk.p3}%`;
            document.getElementById('drawer-p4').innerText = `${sk.p4.toFixed(1)}%`;
            document.getElementById('drawer-p4-bar').style.width = `${sk.p4}%`;

            // Setup GIS button
            const gisBtn = document.getElementById('drawer-btn-gis');
            gisBtn.onclick = () => {
                window.location.href = `sekar.php?sekolah=${encodeURIComponent(sk.nama)}`;
            };

            const drawer = document.getElementById('school-detail-drawer');
            const backdrop = document.getElementById('drawer-backdrop');
            drawer.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
        }

        function closeDrawer() {
            const drawer = document.getElementById('school-detail-drawer');
            const backdrop = document.getElementById('drawer-backdrop');
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('hidden');
        }

        // Simulation Modal Logic
        function toggleSimModal() {
            const modal = document.getElementById('weight-sim-modal');
            modal.classList.toggle('hidden');
        }

        function onSimSliderChange() {
            const p1 = parseInt(document.getElementById('range-p1').value);
            const p2 = parseInt(document.getElementById('range-p2').value);
            const p3 = parseInt(document.getElementById('range-p3').value);
            const p4 = parseInt(document.getElementById('range-p4').value);

            document.getElementById('val-p1').innerText = p1 + '%';
            document.getElementById('val-p2').innerText = p2 + '%';
            document.getElementById('val-p3').innerText = p3 + '%';
            document.getElementById('val-p4').innerText = p4 + '%';

            const total = p1 + p2 + p3 + p4;
            const ind = document.getElementById('sim-total-weight');
            if (total === 100) {
                ind.innerText = "100% (Sesuai)";
                ind.className = "text-sm font-extrabold text-primary";
            } else {
                ind.innerText = `${total}% (Harus 100%)`;
                ind.className = "text-sm font-extrabold text-error";
            }
        }

        function resetSimWeights() {
            document.getElementById('range-p1').value = 25;
            document.getElementById('range-p2').value = 30;
            document.getElementById('range-p3').value = 20;
            document.getElementById('range-p4').value = 25;
            onSimSliderChange();
        }

        function applySimWeights() {
            const p1 = parseInt(document.getElementById('range-p1').value);
            const p2 = parseInt(document.getElementById('range-p2').value);
            const p3 = parseInt(document.getElementById('range-p3').value);
            const p4 = parseInt(document.getElementById('range-p4').value);

            const total = p1 + p2 + p3 + p4;
            if (total !== 100) {
                alert(`Total bobot saat ini ${total}%. Harap sesuaikan agar berjumlah tepat 100% sebelum menerapkan kalkulasi SAW.`);
                return;
            }

            weights.w1 = p1 / 100;
            weights.w2 = p2 / 100;
            weights.w3 = p3 / 100;
            weights.w4 = p4 / 100;

            // Update UI Weight headers & cards
            document.getElementById('p1-badge-weight').innerText = `Bobot ${p1}%`;
            document.getElementById('p2-badge-weight').innerText = `Bobot ${p2}%`;
            document.getElementById('p3-badge-weight').innerText = `Bobot ${p3}%`;
            document.getElementById('p4-badge-weight').innerText = `Bobot ${p4}%`;

            document.getElementById('p1-bar').style.width = p1 + '%';
            document.getElementById('p2-bar').style.width = p2 + '%';
            document.getElementById('p3-bar').style.width = p3 + '%';
            document.getElementById('p4-bar').style.width = p4 + '%';

            document.getElementById('th-p1').innerText = `P1: Sarpras (${p1}%)`;
            document.getElementById('th-p2').innerText = `P2: Guru (${p2}%)`;
            document.getElementById('th-p3').innerText = `P3: Siswa/Gender (${p3}%)`;
            document.getElementById('th-p4').innerText = `P4: Rencana Aksi (${p4}%)`;

            document.querySelectorAll('.w-p1-text').forEach(el => el.innerText = p1 + '%');
            document.querySelectorAll('.w-p2-text').forEach(el => el.innerText = p2 + '%');
            document.querySelectorAll('.w-p3-text').forEach(el => el.innerText = p3 + '%');
            document.querySelectorAll('.w-p4-text').forEach(el => el.innerText = p4 + '%');

            recalculateScores();
            applyFilters();
            toggleSimModal();
            showToast("Simulasi bobot AHP/SAW berhasil dikalkulasi ulang untuk seluruh sekolah!");
        }

        // CSV Export Functionality
        function exportMatrixCSV() {
            let csv = "Rank,Nama Sekolah,NPSN,Kecamatan,Kabupaten,Provinsi,Program,P1_Sarpras,P2_Guru,P3_Siswa,P4_Rencana_Aksi,Skor_DSS_SAW,Status_Kelayakan,Rekomendasi\n";

            filteredSchools.forEach(s => {
                const row = [
                    s.rank,
                    `"${s.nama}"`,
                    `"${s.npsn}"`,
                    `"${s.kec}"`,
                    `"${s.kab}"`,
                    `"${s.prov}"`,
                    `"${s.tracks.join(' & ')}"`,
                    s.p1,
                    s.p2,
                    s.p3,
                    s.p4,
                    s.score,
                    `"${s.category}"`,
                    `"${s.rekomendasi_title} - ${s.rekomendasi_sub}"`
                ];
                csv += row.join(",") + "\n";
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `Matriks_Evaluasi_DSS_SEKAR_${new Date().toISOString().slice(0,10)}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            showToast("Berkas CSV Matriks Evaluasi berhasil diunduh.");
        }

        function downloadRubricPdf() {
            showToast("Mengunduh berkas lengkap evaluasi rubrik format PDF...");
        }

        function sendNotificationAlert() {
            showToast("Notifikasi hasil perangkingan dan prioritas CSR telah diteruskan ke Tim M&E.");
        }

        function scrollSection(id) {
            const el = document.getElementById(id);
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        }

        function showToast(msg) {
            const toast = document.getElementById('toast-notify');
            const toastMsg = document.getElementById('toast-message');
            toastMsg.innerText = msg;
            toast.classList.remove('translate-y-20', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3200);
        }

        // Initialize on Load
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboardData();
        });
    </script>
</body>
</html>
