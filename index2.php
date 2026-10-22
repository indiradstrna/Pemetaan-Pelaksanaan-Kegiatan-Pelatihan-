<?php
/**
 * SEKAR BIOTROP - GeoImpact DSS
 * Edu-Monitoring & Spatial Decision Support System
 * File: index2.php (Migrasi code.html ke PHP terintegrasi peta & menu DSS)
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
    <title>GeoImpact DSS - Peta &amp; Menu Edu-Monitoring GIS | SEKAR BIOTROP</title>
    <meta name="description" content="GeoImpact DSS SEKAR BIOTROP - Peta spasial dan menu monitoring evaluasi sekolah program Smart Urban Farming dan Circular Economy.">
    <link rel="icon" type="image/x-icon" href="assets/img/logo-biotrop.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Leaflet GIS CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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

        /* Left Sidebar styling identical to sekar.php and code.html */
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

        /* Leaflet custom marker pins */
        .custom-gis-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(0,0,0,.22);
            border: 2px solid #006948;
            font-size: 16px;
            cursor: pointer;
            transition: transform .2s ease;
        }
        .custom-gis-pin:hover {
            transform: scale(1.15);
        }
        .custom-gis-pin.urban { border-color: #006948; color: #006948; }
        .custom-gis-pin.circular { border-color: #825100; color: #825100; }
        .custom-gis-pin.lso { border-color: #006398; color: #006398; }
        .custom-gis-pin.multi { border-color: #00855d; color: #00855d; background: #e8f5e9; }
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
                            <span class="font-bold text-base text-on-surface tracking-tight leading-tight">GeoImpact DSS</span>
                            <span class="text-[10px] uppercase font-extrabold px-1.5 py-0.5 rounded bg-primary-fixed text-on-primary-fixed">Edu-GIS</span>
                        </div>
                        <span class="text-xs text-on-surface-variant font-medium">Sistem Pemantauan &amp; Menu Spasial Terpadu</span>
                    </div>
                </a>

                <div class="hidden xl:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container text-primary text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    Status Data: Realtime Q3 2024
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

    <!-- Left Sidebar (Matching sekar.php & code.html) -->
    <aside class="sekar-sidebar">
        <div class="sidebar-title">
            <span class="mini-mark">P</span>
        </div>

        <nav class="sidebar-nav">
            <a href="index2.php" class="nav-item active" data-label="Peta &amp; Menu DSS">
                <i class="bi bi-map"></i>
            </a>
            <a href="sekar.php" class="nav-item" data-label="Peta SEKAR">
                <i class="bi bi-globe-americas"></i>
            </a>
            <a href="dashboard.php" class="nav-item" data-label="Matriks Evaluasi">
                <i class="bi bi-bar-chart"></i>
            </a>
            <button class="nav-item" type="button" data-label="Analisis Wilayah" onclick="switchRightTab('dss')">
                <i class="bi bi-pie-chart"></i>
            </button>
            <button class="nav-item" type="button" data-label="Detail Sekolah" onclick="switchRightTab('detail')">
                <i class="bi bi-building"></i>
            </button>
            <button class="nav-item" type="button" data-label="Upload Action Plan" onclick="openUploadModal()">
                <i class="bi bi-cloud-upload"></i>
            </button>
            <a href="sekar.php" class="nav-item" data-label="Kembali ke Beranda">
                <i class="bi bi-arrow-left-circle"></i>
            </a>
        </nav>
    </aside>

    <!-- Main Content Body -->
    <main class="content-container flex flex-col w-auto">

        <!-- Page Sub-bar with Filters & Tactical Controls -->
        <section class="w-full bg-surface-container-lowest px-4 md:px-gutter-desktop py-2.5 shadow-sm flex flex-col gap-2 border-b border-surface-container">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 bg-surface-container-low px-2.5 py-1.5 rounded-lg shrink-0">
                        <span class="material-symbols-outlined text-primary text-base">travel_explore</span>
                        <span class="text-[11px] text-on-surface-variant uppercase font-bold">Wilayah:</span>
                    </div>

                    <!-- Provinsi Select -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select id="provinsiSelect" onchange="onProvinsiChange()" class="w-full bg-surface-container-lowest border border-surface-container text-on-surface text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer pr-8">
                            <option value="all">Semua Provinsi (Nasional)</option>
                            <option value="Jawa Barat" selected>Jawa Barat</option>
                            <option value="Jawa Timur">Jawa Timur</option>
                            <option value="Jawa Tengah">Jawa Tengah</option>
                            <option value="DKI Jakarta">DKI Jakarta</option>
                            <option value="D.I. Yogyakarta">DI Yogyakarta</option>
                        </select>
                    </div>

                    <!-- Kabupaten Select -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select id="kabupatenSelect" onchange="applyFilters()" class="w-full bg-surface-container-lowest border border-surface-container text-on-surface text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer pr-8">
                            <option value="">Semua Kab/Kota</option>
                            <option value="Kab. Bandung" selected>Kab. Bandung (Fokus Wilayah)</option>
                            <option value="Kab. Bogor">Kab. Bogor</option>
                            <option value="Kota Bandung">Kota Bandung</option>
                            <option value="Kota Bogor">Kota Bogor</option>
                            <option value="Kota Depok">Kota Depok</option>
                            <option value="Kota Bekasi">Kota Bekasi</option>
                        </select>
                    </div>

                    <!-- Fly to region button -->
                    <button id="btnFlyTo" onclick="flyToTargetRegion()" class="p-1.5 rounded-lg bg-primary-container text-on-primary-container hover:bg-primary transition-colors flex items-center justify-center shadow-sm shrink-0" title="Fokus ke Wilayah">
                        <span class="material-symbols-outlined text-base">my_location</span>
                    </button>
                </div>

                <!-- Action Button Group -->
                <div class="flex items-center gap-2 ml-auto w-full sm:w-auto justify-end flex-wrap">
                    <button id="btnResetFilter" onclick="resetFilters()" class="px-3 py-1.5 rounded-lg bg-surface-container text-on-surface-variant hover:text-on-surface text-xs font-semibold flex items-center gap-1 transition-all shadow-sm">
                        <span class="material-symbols-outlined text-sm">restart_alt</span> Reset
                    </button>
                    <button id="btnExportSummary" onclick="exportSummaryCSV()" class="px-3 py-1.5 rounded-lg bg-surface-container text-secondary hover:bg-secondary-fixed transition-colors text-xs font-semibold flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-sm">file_download</span> Ekspor
                    </button>
                    <button id="btnOpenUploadModal" onclick="openUploadModal()" class="px-3.5 py-1.5 rounded-lg bg-primary text-white hover:bg-primary-container shadow-sm text-xs font-bold flex items-center gap-1.5 transition-transform active:scale-95">
                        <span class="material-symbols-outlined text-sm">upload_file</span> Upload Action Plan PDF
                    </button>
                </div>
            </div>

            <!-- Training Layer Filter Pills & Spatial Live Engine Indicator -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-surface-container/60">
                <div class="flex flex-wrap items-center gap-2 text-on-surface">
                    <span class="text-xs text-on-surface-variant mr-1 font-semibold">Layer Pelatihan:</span>

                    <!-- Urban Farming -->
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition-all select-none border border-primary/20">
                        <input id="layerUrban" checked type="checkbox" onchange="applyFilters()" class="accent-primary rounded cursor-pointer w-3.5 h-3.5">
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                        <span class="text-[11px] font-bold">SMART URBAN FARMING</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-surface-container-lowest text-primary font-bold shadow-xs" id="badge-count-urban">24</span>
                    </label>

                    <!-- Circular Economy -->
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed-variant hover:bg-tertiary-fixed-dim transition-all select-none border border-tertiary/20">
                        <input id="layerCircular" checked type="checkbox" onchange="applyFilters()" class="accent-tertiary rounded cursor-pointer w-3.5 h-3.5">
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                        <span class="text-[11px] font-bold">CIRCULAR ECONOMY</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-surface-container-lowest text-on-tertiary-fixed-variant font-bold shadow-xs" id="badge-count-circular">18</span>
                    </label>

                    <!-- LSO -->
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant hover:bg-secondary-fixed-dim transition-all select-none border border-secondary/20">
                        <input id="layerLSO" checked type="checkbox" onchange="applyFilters()" class="accent-secondary rounded cursor-pointer w-3.5 h-3.5">
                        <span class="w-2 h-2 rounded-full bg-secondary"></span>
                        <span class="text-[11px] font-bold">LSO (LIFE SKILLS &amp; ORG)</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-surface-container-lowest text-on-secondary-fixed-variant font-bold shadow-xs" id="badge-count-lso">14</span>
                    </label>
                </div>

                <div class="flex items-center gap-2 text-on-surface-variant text-[11px] font-medium ml-auto sm:ml-0">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-primary-fixed-dim animate-ping"></span> Live Spatial Engine
                    </span>
                    <span>&bull; Datum: WGS84</span>
                </div>
            </div>
        </section>

        <!-- Main Workspace (Grid: Left Map 7 Cols, Right DSS Menu 5 Cols) -->
        <div class="w-full flex-1 grid grid-cols-1 lg:grid-cols-12 gap-0 relative overflow-hidden min-h-[calc(100vh-8rem)]">

            <!-- Left GIS Map Area (7 Cols) -->
            <div class="lg:col-span-7 h-[420px] sm:h-[480px] lg:h-auto relative bg-surface-dim overflow-hidden select-none flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-surface-container">

                <!-- Floating Search & Map Controls (Top Left) -->
                <div class="absolute top-3 left-3 z-30 flex flex-wrap items-center gap-2 max-w-[calc(100%-1.5rem)]">
                    <div class="flex items-center bg-surface-container-lowest/95 backdrop-blur-md px-3 py-1.5 rounded-xl shadow-md border border-surface-container">
                        <span class="material-symbols-outlined text-outline text-base mr-1.5">search</span>
                        <input id="schoolSearchInput" oninput="applyFilters()" class="bg-transparent text-on-surface text-xs focus:outline-none w-44 sm:w-60 placeholder:text-outline-variant" placeholder="Cari nama sekolah / NPSN..." type="text">
                        <button id="btnClearSearch" onclick="clearSearch()" class="hidden text-outline hover:text-on-surface p-0.5">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>

                    <div class="flex flex-col bg-surface-container-lowest/95 backdrop-blur-md rounded-xl shadow-md border border-surface-container overflow-hidden">
                        <button id="mapZoomIn" onclick="zoomInMap()" class="p-2 hover:bg-surface-container text-on-surface transition-colors" title="Perbesar">
                            <span class="material-symbols-outlined text-base">add</span>
                        </button>
                        <div class="h-px bg-surface-container"></div>
                        <button id="mapZoomOut" onclick="zoomOutMap()" class="p-2 hover:bg-surface-container text-on-surface transition-colors" title="Perkecil">
                            <span class="material-symbols-outlined text-base">remove</span>
                        </button>
                    </div>

                    <button id="btnCenterAll" onclick="centerAllSchools()" class="p-2 rounded-xl bg-surface-container-lowest/95 backdrop-blur-md text-on-surface hover:bg-surface-container shadow-md border border-surface-container transition-colors" title="Fokus Seluruh Sekolah">
                        <span class="material-symbols-outlined text-base">crop_free</span>
                    </button>
                </div>

                <!-- Floating Live Coordinate Pill (Top Right) -->
                <div class="absolute top-3 right-3 bg-surface-container-lowest/90 backdrop-blur-md px-3 py-1 rounded-lg text-on-surface-variant text-[11px] z-20 flex items-center gap-1.5 shadow-sm border border-surface-container">
                    <span class="material-symbols-outlined text-primary text-base">explore</span>
                    <span id="coordDisplay">Lat: -7.0251 S | Long: 107.5197 E</span>
                </div>

                <!-- Interactive Leaflet Map Canvas -->
                <div id="gisMapCanvas" class="absolute inset-0 w-full h-full z-10"></div>

                <!-- Floating Spatial Legend (Bottom Left) -->
                <div class="absolute bottom-3 left-3 z-30 bg-surface-container-lowest/95 backdrop-blur-md p-3 rounded-xl shadow-lg max-w-[260px] sm:max-w-xs border border-surface-container">
                    <div class="flex items-center justify-between pb-1 mb-1.5 border-b border-surface-container">
                        <span class="text-[11px] text-on-surface font-extrabold uppercase tracking-wider">Legenda Spasial GIS</span>
                        <span class="material-symbols-outlined text-outline text-base">layers</span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-[11px] text-on-surface-variant font-medium">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary shrink-0"></span>
                            <span class="truncate">Urban Farming</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-tertiary shrink-0"></span>
                            <span class="truncate">Circular Econ</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-secondary shrink-0"></span>
                            <span class="truncate">Life Skills (LSO)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-surface-container-lowest ring-2 ring-primary shrink-0"></span>
                            <span class="truncate">Multi-Modul</span>
                        </div>
                    </div>
                    <div class="mt-1.5 pt-1.5 border-t border-surface-container flex items-center justify-between text-[10px] text-on-surface-variant font-semibold">
                        <span>Skor Rubrik:</span>
                        <span class="text-error">&lt;50% Prioritas</span>
                        <span class="text-tertiary">50-79%</span>
                        <span class="text-primary">&ge;80% Mandiri</span>
                    </div>
                </div>

                <!-- Map Density & Scale Info (Bottom Right) -->
                <div class="hidden sm:flex absolute bottom-3 right-3 z-30 bg-surface-container-lowest/90 backdrop-blur-md px-3 py-1 rounded-lg shadow-sm text-xs text-on-surface-variant items-center gap-2 border border-surface-container">
                    <span id="mapDensityText">Kerapatan: 32 Sekolah Terdata</span>
                    <span class="w-1 h-1 rounded-full bg-outline"></span>
                    <span>Skala: 1:50.000</span>
                </div>
            </div>

            <!-- Right DSS Side Panel & Menu (5 Cols) -->
            <div class="lg:col-span-5 bg-surface flex flex-col shadow-inner h-auto lg:h-full overflow-hidden">
                <!-- Sticky Top Tab Header -->
                <div class="bg-surface-container-lowest px-4 md:px-gutter-desktop pt-2.5 border-b border-surface-container flex items-center justify-between sticky top-0 z-20">
                    <div class="flex items-center gap-2">
                        <button class="px-3 md:px-4 py-2 text-xs font-semibold text-on-surface-variant border-b-2 border-transparent hover:text-on-surface transition-all flex items-center gap-1.5" id="tabModeDSS" onclick="switchRightTab('dss')">
                            <span class="material-symbols-outlined text-base">monitoring</span> Analisis Wilayah (DSS)
                        </button>
                        <button class="px-3 md:px-4 py-2 text-xs font-bold border-b-2 border-primary text-primary transition-all flex items-center gap-1.5" id="tabModeDetail" onclick="switchRightTab('detail')">
                            <span class="material-symbols-outlined text-base">school</span> Detail Sekolah Terpilih
                            <span class="w-2 h-2 rounded-full bg-primary animate-pulse" id="detailBadgeActive"></span>
                        </button>
                    </div>
                    <span class="hidden sm:inline-block text-[11px] text-outline font-mono font-semibold">DSS-v3.4</span>
                </div>

                <!-- Scrollable Tab Content Container -->
                <div class="flex-1 overflow-y-auto px-4 md:px-gutter-desktop py-4 space-y-4">

                    <!-- Tab 1: Analisis Wilayah (DSS) -->
                    <div class="space-y-4 hidden" id="viewDSSSummary">
                        <!-- Region Overview Card -->
                        <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm border border-surface-container">
                            <div class="flex items-center gap-1.5 text-primary text-xs font-bold mb-1">
                                <span class="material-symbols-outlined text-base">location_city</span>
                                <span class="tracking-wider">GEO-IMPACT SURVEILLANCE</span>
                            </div>
                            <h3 class="text-base font-bold text-on-surface" id="regionHeaderTitle">Wilayah: Jawa Barat &bull; Kab. Bandung &amp; Sekitarnya</h3>
                            <p class="text-xs text-on-surface-variant mt-0.5" id="regionHeaderSub">32 Sekolah Terpetakan dalam Radius Prioritas CSR &amp; M&amp;E Q3 2024</p>
                        </div>

                        <!-- KPI Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Metric 1: Total Sekolah -->
                            <div class="bg-surface-container-lowest p-3.5 rounded-xl shadow-sm relative overflow-hidden border border-surface-container">
                                <div class="absolute top-0 left-0 right-0 h-1 bg-primary"></div>
                                <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Total Sekolah</span>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <span class="text-2xl font-extrabold text-on-surface" id="dss-total-school">32</span>
                                    <span class="text-xs text-primary font-bold">+4 Q3</span>
                                </div>
                                <span class="text-[11px] text-on-surface-variant mt-1 block">100% Terverifikasi GIS</span>
                            </div>

                            <!-- Metric 2: Guru Terlatih -->
                            <div class="bg-surface-container-lowest p-3.5 rounded-xl shadow-sm relative overflow-hidden border border-surface-container">
                                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary"></div>
                                <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Guru Terlatih</span>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <span class="text-2xl font-extrabold text-on-surface" id="dss-total-guru">184</span>
                                    <span class="text-xs text-secondary font-bold">Fasilitator</span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-on-surface-variant mt-1 font-medium">
                                    <span>Lk: <strong id="dss-guru-lk">82</strong></span>
                                    <span>&bull;</span>
                                    <span>Pr: <strong id="dss-guru-pr">102 (55%)</strong></span>
                                </div>
                            </div>

                            <!-- Metric 3: Siswa Terpapar Edukasi -->
                            <div class="col-span-1 sm:col-span-2 bg-surface-container-lowest p-4 rounded-xl shadow-sm relative overflow-hidden border border-surface-container">
                                <div class="absolute top-0 left-0 right-0 h-1 bg-primary-container"></div>
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Siswa Terpapar Edukasi</span>
                                    <span class="text-xs text-primary font-bold">Target 96.4% Tercapai</span>
                                </div>
                                <div class="flex items-baseline gap-2 my-1.5">
                                    <span class="text-2xl font-extrabold text-on-surface" id="dss-total-siswa">4.820</span>
                                    <span class="text-xs text-on-surface-variant font-medium">Siswa Aktif</span>
                                </div>
                                <div class="w-full mt-2">
                                    <div class="flex justify-between text-[11px] text-on-surface-variant mb-1 font-semibold">
                                        <span class="text-secondary" id="dss-siswa-lk">Laki-laki: 2.310 (48%)</span>
                                        <span class="text-tertiary-container" id="dss-siswa-pr">Perempuan: 2.510 (52%)</span>
                                    </div>
                                    <div class="w-full h-2.5 rounded-full bg-surface-container overflow-hidden flex">
                                        <div class="h-full bg-secondary" id="dss-bar-lk" style="width: 48%;"></div>
                                        <div class="h-full bg-tertiary-container" id="dss-bar-pr" style="width: 52%;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Metric 4: Rata-Rata Indeks Action Plan DSS -->
                            <div class="col-span-1 sm:col-span-2 bg-surface-container-lowest p-4 rounded-xl shadow-sm flex items-center justify-between gap-4 border border-surface-container">
                                <div>
                                    <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Rata-Rata Indeks Action Plan DSS</span>
                                    <div class="flex items-baseline gap-2 mt-1">
                                        <span class="text-2xl font-extrabold text-primary" id="dss-avg-score">84.6%</span>
                                        <span class="px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-bold">Sangat Mandiri</span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">Status kelayakan replikasi mandiri oleh komite sekolah.</p>
                                </div>
                                <div class="relative w-16 h-16 shrink-0">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-surface-container stroke-current" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3.5"></path>
                                        <path class="text-primary stroke-current" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-dasharray="84.6, 100" stroke-linecap="round" stroke-width="3.5"></path>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center text-xs font-bold text-on-surface" id="dss-gauge-text">85%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Distribusi Sebaran Modul Pelatihan -->
                        <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm space-y-3 border border-surface-container">
                            <h4 class="text-sm font-bold text-on-surface">Distribusi Sebaran Modul Pelatihan</h4>
                            <div class="space-y-2.5 pt-1">
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-on-surface font-semibold">Smart Urban Farming</span>
                                        <span class="text-primary font-bold" id="dist-urban-text">24 Sekolah (75%)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                                        <div class="h-full bg-primary rounded-full transition-all duration-500" id="dist-urban-bar" style="width: 75%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-on-surface font-semibold">Circular Economy &amp; Waste Bank</span>
                                        <span class="text-tertiary font-bold" id="dist-circular-text">18 Sekolah (56%)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                                        <div class="h-full bg-tertiary rounded-full transition-all duration-500" id="dist-circular-bar" style="width: 56%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-on-surface font-semibold">Life Skills &amp; Organization (LSO)</span>
                                        <span class="text-secondary font-bold" id="dist-lso-text">14 Sekolah (44%)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                                        <div class="h-full bg-secondary rounded-full transition-all duration-500" id="dist-lso-bar" style="width: 44%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rekomendasi Algoritma DSS Wilayah -->
                        <div class="bg-surface-container-high/60 p-4 rounded-xl border-l-4 border-primary flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary text-2xl shrink-0">psychology_alt</span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h5 class="text-xs font-bold text-on-surface">Rekomendasi Algoritma DSS Wilayah</h5>
                                    <span class="px-1.5 py-0.5 rounded bg-primary-container text-on-primary-container text-[10px] font-bold">Prioritas Anggaran</span>
                                </div>
                                <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                                    💡 <strong>Alokasi Anggaran Lanjutan:</strong> 6 sekolah di Kec. Majalaya membutuhkan intervensi tahap 2 modul Circular Economy. Tingkat retensi komposter organik mencapai 91%, berpeluang besar menjadi <em>Sentra Studi Tani Sekolah Terbuka</em>.
                                </p>
                                <div class="mt-2.5 flex gap-2">
                                    <button class="text-xs text-primary font-bold hover:underline flex items-center gap-1" onclick="selectSchool(1)">
                                        Lihat Sekolah Percontohan &rarr;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Detail Sekolah Terpilih -->
                    <div class="space-y-4" id="viewSchoolDetail">
                        <!-- School Header Card -->
                        <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm space-y-2 border border-surface-container">
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed text-[11px] font-bold" id="detail-school-badge">Aktif M&amp;E Mandiri</span>
                                        <span class="text-xs text-on-surface-variant font-mono" id="detail-school-npsn">NPSN: 20219482</span>
                                    </div>
                                    <h3 class="text-lg font-bold text-on-surface leading-snug" id="detail-school-name">SMK Negeri 1 Pertanian Mandiri</h3>
                                    <p class="text-xs text-on-surface-variant flex items-center gap-1" id="detail-school-address">
                                        <span class="material-symbols-outlined text-outline text-base">place</span> Jl. Raya Majalaya No. 104, Kab. Bandung, Jawa Barat
                                    </p>
                                </div>
                                <button class="p-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-primary transition-colors shrink-0 shadow-xs" onclick="centerMapOnCurrentSchool()" title="Fokuskan di Peta">
                                    <span class="material-symbols-outlined text-lg">center_focus_strong</span>
                                </button>
                            </div>

                            <div class="pt-1 flex flex-wrap gap-1.5" id="detail-school-tracks">
                                <span class="px-2.5 py-1 rounded-full bg-primary/10 text-primary text-[11px] font-semibold border border-primary/20">SMART URBAN FARMING</span>
                                <span class="px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed-variant text-[11px] font-semibold border border-tertiary/20">CIRCULAR ECONOMY</span>
                                <span class="px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant text-[11px] font-semibold border border-secondary/20">LSO LEADERSHIP</span>
                            </div>
                        </div>

                        <!-- Evaluasi Rubrik Action Plan -->
                        <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm space-y-2 border border-surface-container">
                            <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Evaluasi Rubrik Action Plan</span>
                                    <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                        <span class="text-2xl font-extrabold text-primary" id="detail-score">88%</span>
                                        <span class="px-2 py-0.5 rounded bg-primary-container text-on-primary-container text-xs font-bold" id="detail-tier">Tier A (Mandiri Unggul)</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-on-surface-variant block font-medium">Status Verifikasi</span>
                                    <span class="text-xs text-primary font-bold flex items-center justify-end gap-1">
                                        <span class="material-symbols-outlined text-sm">verified</span> Terverifikasi
                                    </span>
                                </div>
                            </div>

                            <!-- 4 Pillars Rubric Bars -->
                            <div class="space-y-2 pt-2">
                                <div class="bg-surface-container-low p-2.5 rounded-lg">
                                    <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                        <span class="text-on-surface">1. Infrastruktur &amp; Instalasi Kebun</span>
                                        <span class="text-primary font-bold" id="detail-p1-text">100% (Selesai &amp; Beroperasi)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                                        <div class="h-full bg-primary rounded-full transition-all duration-300" id="detail-p1-bar" style="width: 100%"></div>
                                    </div>
                                </div>

                                <div class="bg-surface-container-low p-2.5 rounded-lg">
                                    <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                        <span class="text-on-surface">2. Integrasi Kurikulum Kelas</span>
                                        <span class="text-primary font-bold" id="detail-p2-text">90% (Masuk Muatan Lokal)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                                        <div class="h-full bg-primary rounded-full transition-all duration-300" id="detail-p2-bar" style="width: 90%"></div>
                                    </div>
                                </div>

                                <div class="bg-surface-container-low p-2.5 rounded-lg">
                                    <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                        <span class="text-on-surface">3. Partisipasi Siswa &amp; Gender Inclusivity</span>
                                        <span class="text-primary font-bold" id="detail-p3-text">85% (Keseimbangan Peran Tim)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                                        <div class="h-full bg-primary rounded-full transition-all duration-300" id="detail-p3-bar" style="width: 85%"></div>
                                    </div>
                                </div>

                                <div class="bg-surface-container-low p-2.5 rounded-lg">
                                    <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                        <span class="text-on-surface">4. Keberlanjutan Finansial Panen</span>
                                        <span class="text-tertiary-container font-bold" id="detail-p4-text">78% (Kantin Menyerap 60%)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                                        <div class="h-full bg-tertiary-container rounded-full transition-all duration-300" id="detail-p4-bar" style="width: 78%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Riwayat Pelatihan & Dampak Siswa (Timeline) -->
                        <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm space-y-2 border border-surface-container">
                            <h4 class="text-sm font-bold text-on-surface">Riwayat Pelatihan &amp; Dampak Siswa</h4>
                            <div class="space-y-4 relative border-l-2 border-surface-container-high ml-2 pl-4" id="detail-timeline">
                                <!-- Populated dynamically via JS -->
                            </div>
                        </div>

                        <!-- Dokumen Rubrik & Verifikasi Lapangan -->
                        <div class="bg-surface-container-lowest p-4 rounded-xl shadow-sm space-y-2 border border-surface-container">
                            <h4 class="text-sm font-bold text-on-surface">Dokumen Rubrik &amp; Verifikasi Lapangan</h4>
                            <div class="flex flex-wrap sm:flex-nowrap items-center justify-between p-3 rounded-lg bg-surface-container-low gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="material-symbols-outlined text-error text-3xl shrink-0">picture_as_pdf</span>
                                    <div class="min-w-0">
                                        <span class="text-xs font-bold text-on-surface block truncate max-w-[200px] sm:max-w-xs" id="detail-pdf-name">action-plan-rubrik-smkn1.pdf</span>
                                        <span class="text-[11px] text-on-surface-variant" id="detail-pdf-uploader">Diunggah Tim CSR Admin &bull; 28 Agu 2024</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                                    <button class="px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors" onclick="previewActionPlanPdf()">
                                        Preview
                                    </button>
                                    <button class="px-3 py-1.5 rounded-lg bg-primary text-white hover:bg-primary-container text-xs font-semibold transition-colors shadow-xs" onclick="openUploadModalForCurrentSchool()">
                                        Update
                                    </button>
                                </div>
                            </div>

                            <div class="p-3 rounded-lg bg-primary-fixed/20 text-on-surface flex items-start gap-2 border border-primary/20">
                                <span class="material-symbols-outlined text-primary text-lg shrink-0 mt-0.5">task_alt</span>
                                <p class="text-[11px] leading-relaxed">
                                    <strong>Status Pengisian Mandiri Sekolah:</strong> Terverifikasi valid oleh Koordinator Guru pada September 2024. Telah memenuhi kriteria kemandirian program edukasi.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- Upload Action Plan PDF Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm hidden" id="uploadModal">
        <div class="bg-surface-container-lowest rounded-2xl max-w-lg w-full p-6 shadow-2xl flex flex-col gap-4 border border-surface-container">
            <div class="flex items-center justify-between pb-2 border-b border-surface-container">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-primary-fixed text-on-primary-fixed material-symbols-outlined text-lg">upload_file</span>
                    <div>
                        <h3 class="text-base font-extrabold text-on-surface">Upload Action Plan PDF</h3>
                        <p class="text-xs text-on-surface-variant">Unggah lembar action plan dan rubrik evaluasi mandiri sekolah.</p>
                    </div>
                </div>
                <button class="p-1 rounded-full hover:bg-surface-container text-on-surface-variant" onclick="closeUploadModal()">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form onsubmit="handleUploadSubmit(event)" class="flex flex-col gap-3">
                <div>
                    <label class="text-xs font-bold text-on-surface mb-1 block">Sekolah Target</label>
                    <select id="modalSchoolSelect" class="w-full bg-surface-container-low border border-surface-container rounded-lg px-3 py-2 text-xs font-semibold text-on-surface focus:outline-none focus:ring-1 focus:ring-primary">
                        <!-- Populated by JS -->
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-on-surface mb-1 block">Program Modul Terkait</label>
                    <select class="w-full bg-surface-container-low border border-surface-container rounded-lg px-3 py-2 text-xs font-semibold text-on-surface focus:outline-none focus:ring-1 focus:ring-primary">
                        <option>Smart Urban Farming</option>
                        <option>Circular Economy</option>
                        <option>Leadership &amp; Student Org (LSO)</option>
                        <option>Terpadu Multi-Modul</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-on-surface mb-1 block">Pilih Berkas PDF (Maks. 10MB)</label>
                    <div class="border-2 border-dashed border-surface-container-highest hover:border-primary rounded-xl p-4 text-center cursor-pointer bg-surface-container-low transition-colors" onclick="document.getElementById('actionPlanFileInput').click()">
                        <span class="material-symbols-outlined text-primary text-3xl">cloud_upload</span>
                        <p class="text-xs font-semibold text-on-surface mt-1" id="fileUploadLabel">Klik untuk pilih berkas PDF atau tarik ke sini</p>
                        <span class="text-[10px] text-on-surface-variant">Format: .pdf (Action Plan, BAST, Evaluasi)</span>
                        <input type="file" id="actionPlanFileInput" accept=".pdf" class="hidden" onchange="onFileSelected(this)">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-surface-container">
                    <button type="button" class="px-4 py-2 rounded-lg bg-surface-container text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors" onclick="closeUploadModal()">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary-container transition-colors shadow-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">send</span> Unggah Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PDF Preview Mockup Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden" id="previewModal">
        <div class="bg-surface-container-lowest rounded-2xl max-w-2xl w-full p-6 shadow-2xl flex flex-col gap-4 border border-surface-container">
            <div class="flex items-center justify-between pb-2 border-b border-surface-container">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-error text-2xl">picture_as_pdf</span>
                    <h3 class="text-base font-bold text-on-surface" id="previewModalTitle">Preview Action Plan</h3>
                </div>
                <button class="p-1 rounded-full hover:bg-surface-container text-on-surface-variant" onclick="closePreviewModal()">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="bg-surface-container-low p-6 rounded-xl border border-surface-container flex flex-col items-center justify-center text-center space-y-3 min-h-[280px]">
                <span class="material-symbols-outlined text-primary text-5xl">task</span>
                <h4 class="text-sm font-bold text-on-surface" id="previewDocTitle">Action Plan Terverifikasi</h4>
                <p class="text-xs text-on-surface-variant max-w-md">
                    Dokumen ini berisi rincian target capaian implementasi 4 pilar program edukasi berkelanjutan BIOTROP, daftar fasilitas greenhouse yang dibangun, serta absensi guru &amp; murid yang berpartisipasi.
                </p>
                <div class="flex items-center gap-2 pt-2">
                    <button onclick="showToast('Mengunduh dokumen PDF...'); closePreviewModal();" class="px-4 py-2 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary-container transition-colors shadow-sm flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">download</span> Unduh PDF Asli
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-notify" class="fixed bottom-6 right-6 z-50 bg-on-surface text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-2.5 text-xs font-semibold transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
        <span class="material-symbols-outlined text-primary-fixed" id="toast-icon">check_circle</span>
        <span id="toast-message">Operasi berhasil.</span>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Interactive Script -->
    <script>
        // Database Sekolah Lengkap dengan Spasial GIS & Monev Data
        const SEKOLAH_GIS = [
            {
                id: 1,
                nama: "SMK Negeri 1 Pertanian Mandiri",
                npsn: "20219482",
                alamat: "Jl. Raya Majalaya No. 104, Kab. Bandung, Jawa Barat",
                kab: "Kab. Bandung",
                prov: "Jawa Barat",
                lat: -7.0505,
                lng: 107.7490,
                tracks: ["urban", "circular", "lso"],
                capaian: 88,
                tier: "Tier A (Mandiri Unggul)",
                badge: "Aktif M&E Mandiri",
                guru: { total: 8, lk: 3, pr: 5 },
                siswa: { total: 240, lk: 115, pr: 125 },
                rubrik: {
                    p1: { val: 100, label: "100% (Selesai & Beroperasi)" },
                    p2: { val: 90, label: "90% (Masuk Muatan Lokal)" },
                    p3: { val: 85, label: "85% (Keseimbangan Peran Tim)" },
                    p4: { val: 78, label: "78% (Kantin Menyerap 60%)" }
                },
                timeline: [
                    { bulan: "Mei 2024", modul: "Smart Urban Farming (Batch II)", guru: "8 Org (5 Pr, 3 Lk)", siswa: "240 Murid (115 Lk, 125 Pr)", output: "🌱 Kebun hidroponik sekolah 120m² & panen sayur pakcoy mandiri bergilir." },
                    { bulan: "Juli 2024", modul: "Circular Economy & Waste Bank", guru: "6 Org (4 Pr, 2 Lk)", siswa: "180 Murid (90 Lk, 90 Pr)", output: "♻️ Unit komposting aerobik & sistem penimbangan bank sampah berbasis digital." },
                    { bulan: "Agustus 2024", modul: "LSO (Leadership & Org Management)", guru: "4 Org (2 Pr, 2 Lk)", siswa: "95 Siswa pengurus OSIS & Ekskul", output: "👥 Struktur organisasi duta lingkungan sekolah dan SOP tata tertib ramah iklim." }
                ],
                pdf: "action-plan-rubrik-smkn1.pdf",
                pdf_uploader: "Diunggah Tim CSR Admin • 28 Agu 2024"
            },
            {
                id: 2,
                nama: "SMA Negeri 2 Soreang",
                npsn: "20206120",
                alamat: "Jl. Soreang-Banjaran No. 42, Kab. Bandung, Jawa Barat",
                kab: "Kab. Bandung",
                prov: "Jawa Barat",
                lat: -7.0315,
                lng: 107.5197,
                tracks: ["circular"],
                capaian: 76,
                tier: "Tier B (Berkembang)",
                badge: "M&E Dalam Evaluasi",
                guru: { total: 6, lk: 3, pr: 3 },
                siswa: { total: 180, lk: 88, pr: 92 },
                rubrik: {
                    p1: { val: 78, label: "78% (Instalasi Sedang Pemeliharaan)" },
                    p2: { val: 75, label: "75% (Modul Kelas Semester Ganjil)" },
                    p3: { val: 79, label: "79% (Keterlibatan Siswi 52%)" },
                    p4: { val: 68, label: "68% (Kemitraan Bank Sampah Rintisan)" }
                },
                timeline: [
                    { bulan: "Juni 2024", modul: "Circular Economy & Bank Sampah", guru: "6 Org (3 Pr, 3 Lk)", siswa: "180 Murid (88 Lk, 92 Pr)", output: "♻️ Pembuatan bank sampah dan pelatihan pemilahan sampah organik & anorganik." }
                ],
                pdf: "action-plan-rubrik-sman2-soreang.pdf",
                pdf_uploader: "Diunggah Koordinator Sekolah • 12 Agu 2024"
            },
            {
                id: 3,
                nama: "SMK Pertanian Baleendah",
                npsn: "20228704",
                alamat: "Jl. Adipati Agung, Baleendah, Kab. Bandung, Jawa Barat",
                kab: "Kab. Bandung",
                prov: "Jawa Barat",
                lat: -6.9985,
                lng: 107.6250,
                tracks: ["urban"],
                capaian: 54,
                tier: "Tier C (Prioritas Intervensi)",
                badge: "Perlu Pendampingan",
                guru: { total: 4, lk: 2, pr: 2 },
                siswa: { total: 110, lk: 60, pr: 50 },
                rubrik: {
                    p1: { val: 48, label: "48% (Pompa Air Memerlukan Perbaikan)" },
                    p2: { val: 52, label: "52% (Rotasi Guru Pembimbing Baru)" },
                    p3: { val: 61, label: "61% (Partisipasi Ekstrakurikuler)" },
                    p4: { val: 42, label: "42% (Hasil Panen Terbatas)" }
                },
                timeline: [
                    { bulan: "Maret 2024", modul: "Smart Urban Farming Pemula", guru: "4 Org (2 Pr, 2 Lk)", siswa: "110 Murid (60 Lk, 50 Pr)", output: "🌱 Plot semai hidroponik skala kecil dan pelatihan nutrisi AB Mix." }
                ],
                pdf: "action-plan-rubrik-smk-baleendah.pdf",
                pdf_uploader: "Diunggah Fasilitator Lapangan • 5 Sep 2024"
            },
            {
                id: 4,
                nama: "SMA Karya Pembangunan Majalaya",
                npsn: "20228512",
                alamat: "Jl. Pasar Baru No. 12, Majalaya, Kab. Bandung, Jawa Barat",
                kab: "Kab. Bandung",
                prov: "Jawa Barat",
                lat: -7.0380,
                lng: 107.7320,
                tracks: ["lso", "circular"],
                capaian: 82,
                tier: "Tier A (Mandiri Unggul)",
                badge: "Mandiri Berprestasi",
                guru: { total: 5, lk: 2, pr: 3 },
                siswa: { total: 150, lk: 70, pr: 80 },
                rubrik: {
                    p1: { val: 86, label: "86% (Fasilitas Bank Sampah Lengkap)" },
                    p2: { val: 84, label: "84% (Silabus Kepemimpinan Siswa)" },
                    p3: { val: 89, label: "89% (Duta Lingkungan Aktif)" },
                    p4: { val: 87, label: "87% (Produk Daur Ulang Bernilai Jual)" }
                },
                timeline: [
                    { bulan: "April 2024", modul: "LSO Leadership & Environment", guru: "5 Org (3 Pr, 2 Lk)", siswa: "150 Murid", output: "👥 Pembentukan Duta Hijau Sekolah dan lomba daur ulang antar kelas." }
                ],
                pdf: "action-plan-karya-pembangunan.pdf",
                pdf_uploader: "Diunggah Tim M&E • 15 Jul 2024"
            },
            {
                id: 5,
                nama: "SMP Negeri 1 Ciwidey",
                npsn: "20206114",
                alamat: "Jl. Raya Ciwidey No. 88, Kab. Bandung, Jawa Barat",
                kab: "Kab. Bandung",
                prov: "Jawa Barat",
                lat: -7.0850,
                lng: 107.4620,
                tracks: ["urban", "circular"],
                capaian: 84,
                tier: "Tier A (Mandiri Unggul)",
                badge: "Sentra Toga Mandiri",
                guru: { total: 7, lk: 3, pr: 4 },
                siswa: { total: 210, lk: 100, pr: 110 },
                rubrik: {
                    p1: { val: 82, label: "82% (Kebun Sayur & Tanaman Obat)" },
                    p2: { val: 80, label: "80% (Integrasi Pembelajaran IPA)" },
                    p3: { val: 83, label: "83% (Praktik Berkebun Siswa)" },
                    p4: { val: 79, label: "79% (Pupuk Kompos Kascing)" }
                },
                timeline: [
                    { bulan: "Februari 2024", modul: "Urban Farming Dataran Tinggi", guru: "7 Org", siswa: "210 Murid", output: "🌱 Budidaya stroberi organik & tanaman obat keluarga (TOGA)." }
                ],
                pdf: "action-plan-smpn1-ciwidey.pdf",
                pdf_uploader: "Diunggah Staf Sekolah • 20 Agu 2024"
            },
            {
                id: 6,
                nama: "SMK Pasundan Rancaekek",
                npsn: "20227914",
                alamat: "Jl. Raya Rancaekek No. 55, Kab. Bandung, Jawa Barat",
                kab: "Kab. Bandung",
                prov: "Jawa Barat",
                lat: -6.9650,
                lng: 107.7650,
                tracks: ["urban", "lso"],
                capaian: 79,
                tier: "Tier B (Berkembang)",
                badge: "Aktif Bertahap",
                guru: { total: 6, lk: 3, pr: 3 },
                siswa: { total: 190, lk: 95, pr: 95 },
                rubrik: {
                    p1: { val: 77, label: "77% (Greenhouse Rintisan)" },
                    p2: { val: 79, label: "79% (Pelatihan Guru Bersertifikat)" },
                    p3: { val: 81, label: "81% (Komunitas Siswa Tani)" },
                    p4: { val: 76, label: "76% (Penjualan Sayur ke Guru)" }
                },
                timeline: [
                    { bulan: "Mei 2024", modul: "Urban Farming Vokasi", guru: "6 Org", siswa: "190 Murid", output: "🌱 Instalasi rakit apung dan tanaman pakcoy hidroponik." }
                ],
                pdf: "action-plan-pasundan-rancaekek.pdf",
                pdf_uploader: "Diunggah Koordinator Guru • 18 Agu 2024"
            },
            {
                id: 7,
                nama: "SMP Negeri 1 Bogor",
                npsn: "20220311",
                alamat: "Jl. Ir. H. Juanda No. 16, Kota Bogor, Jawa Barat",
                kab: "Kota Bogor",
                prov: "Jawa Barat",
                lat: -6.5972,
                lng: 106.7985,
                tracks: ["urban"],
                capaian: 78,
                tier: "Tier B (Berkembang)",
                badge: "Pilot Project Urban",
                guru: { total: 8, lk: 3, pr: 5 },
                siswa: { total: 280, lk: 130, pr: 150 },
                rubrik: {
                    p1: { val: 80, label: "80% (Greenhouse Sentral)" },
                    p2: { val: 78, label: "78% (Kurikulum Lingkungan Hidup)" },
                    p3: { val: 81, label: "81% (Kader Adiwiyata)" },
                    p4: { val: 76, label: "76% (Bazar Sayur Segar)" }
                },
                timeline: [
                    { bulan: "Agustus 2023", modul: "Pelatihan Smart Urban Farming", guru: "12 Org", siswa: "320 Murid", output: "🌱 Pembangunan kebun vertikal dan kompos mandiri." }
                ],
                pdf: "action-plan-smpn1-bogor.pdf",
                pdf_uploader: "Diunggah Staf BIOTROP • 10 Jan 2024"
            },
            {
                id: 8,
                nama: "SMAN 3 Bandung",
                npsn: "20219732",
                alamat: "Jl. Belitung No. 8, Kota Bandung, Jawa Barat",
                kab: "Kota Bandung",
                prov: "Jawa Barat",
                lat: -6.9147,
                lng: 107.6098,
                tracks: ["circular"],
                capaian: 85,
                tier: "Tier A (Mandiri Unggul)",
                badge: "Sekolah Nol Sampah",
                guru: { total: 14, lk: 6, pr: 8 },
                siswa: { total: 510, lk: 240, pr: 270 },
                rubrik: {
                    p1: { val: 88, label: "88% (Bank Sampah Digital)" },
                    p2: { val: 86, label: "86% (Muatan Lokal P5 Berkelanjutan)" },
                    p3: { val: 88, label: "88% (Partisipasi 500+ Murid)" },
                    p4: { val: 84, label: "84% (Daur Ulang Plastik Komersial)" }
                },
                timeline: [
                    { bulan: "Oktober 2023", modul: "Pelatihan Circular Economy", guru: "15 Org", siswa: "400 Murid", output: "♻️ Bank sampah sekolah dan kreasi produk plastik daur ulang." }
                ],
                pdf: "action-plan-sman3-bandung.pdf",
                pdf_uploader: "Diunggah Admin Sekolah • 5 Feb 2024"
            },
            {
                id: 9,
                nama: "SDN Cibinong 01",
                npsn: "20231201",
                alamat: "Jl. Raya Jakarta-Bogor Km. 44, Kab. Bogor, Jawa Barat",
                kab: "Kab. Bogor",
                prov: "Jawa Barat",
                lat: -6.4798,
                lng: 106.8517,
                tracks: ["urban", "circular"],
                capaian: 65,
                tier: "Tier B (Berkembang)",
                badge: "Rintisan Sekolah Hijau",
                guru: { total: 8, lk: 3, pr: 5 },
                siswa: { total: 200, lk: 95, pr: 105 },
                rubrik: {
                    p1: { val: 68, label: "68% (Kebun Belajar Siswa)" },
                    p2: { val: 66, label: "66% (Edukasi Dini Lingkungan)" },
                    p3: { val: 72, label: "72% (Eco-brick Siswa)" },
                    p4: { val: 60, label: "60% (Komposter Daun Kering)" }
                },
                timeline: [
                    { bulan: "Februari 2024", modul: "Pelatihan Terpadu Urban & Sampah", guru: "10 Org", siswa: "200 Murid", output: "🌱 Kebun sekolah berbasis daur ulang dan eco-brick project." }
                ],
                pdf: "action-plan-cibinong01.pdf",
                pdf_uploader: "Diunggah Guru Pembimbing • 14 Mar 2024"
            },
            {
                id: 10,
                nama: "SMA Negeri 2 Bekasi",
                npsn: "20223019",
                alamat: "Jl. Tangkuban Perahu No. 1, Kota Bekasi, Jawa Barat",
                kab: "Kota Bekasi",
                prov: "Jawa Barat",
                lat: -6.2383,
                lng: 107.0000,
                tracks: ["urban"],
                capaian: 45,
                tier: "Tier C (Prioritas Intervensi)",
                badge: "Tahap Perencanaan",
                guru: { total: 4, lk: 2, pr: 2 },
                siswa: { total: 150, lk: 75, pr: 75 },
                rubrik: {
                    p1: { val: 40, label: "40% (Lahan Masih Penyiapan)" },
                    p2: { val: 45, label: "45% (Sosialisasi Awal Guru)" },
                    p3: { val: 50, label: "50% (Peminatan Siswa)" },
                    p4: { val: 38, label: "38% (Belum Berproduksi)" }
                },
                timeline: [
                    { bulan: "Mei 2024", modul: "Pengenalan Smart Urban Farming", guru: "6 Org", siswa: "150 Murid", output: "📋 Survey lahan sekolah dan rencana plot kebun." }
                ],
                pdf: "action-plan-sman2-bekasi.pdf",
                pdf_uploader: "Diunggah Staf CSR • 20 Jun 2024"
            },
            {
                id: 11,
                nama: "SMPN 5 Depok",
                npsn: "20229045",
                alamat: "Jl. Mandor Samin, Sukmajaya, Kota Depok, Jawa Barat",
                kab: "Kota Depok",
                prov: "Jawa Barat",
                lat: -6.4025,
                lng: 106.7942,
                tracks: ["circular"],
                capaian: 72,
                tier: "Tier B (Berkembang)",
                badge: "Kreativitas Daur Ulang",
                guru: { total: 11, lk: 4, pr: 7 },
                siswa: { total: 400, lk: 190, pr: 210 },
                rubrik: {
                    p1: { val: 74, label: "74% (Pojok Sampah Kreatif)" },
                    p2: { val: 72, label: "72% (Workshop Sablon Alami)" },
                    p3: { val: 75, label: "75% (Koperasi Sampah Siswa)" },
                    p4: { val: 70, label: "70% (Bazar Produk Siswa)" }
                },
                timeline: [
                    { bulan: "Juli 2023", modul: "Workshop Circular Economy", guru: "10 Org", siswa: "300 Murid", output: "♻️ Bazar produk daur ulang & pojok kreasi limbah." }
                ],
                pdf: "action-plan-smpn5-depok.pdf",
                pdf_uploader: "Diunggah Tim M&E • 12 Des 2023"
            },
            {
                id: 12,
                nama: "SD Islam Al-Azhar 1 Jakarta",
                npsn: "20101482",
                alamat: "Jl. Sisingamangaraja, Kebayoran Baru, Jakarta Selatan",
                kab: "Jakarta Selatan",
                prov: "DKI Jakarta",
                lat: -6.2441,
                lng: 106.8043,
                tracks: ["urban", "circular"],
                capaian: 91,
                tier: "Tier A (Mandiri Unggul)",
                badge: "Center of Excellence",
                guru: { total: 20, lk: 8, pr: 12 },
                siswa: { total: 650, lk: 310, pr: 340 },
                rubrik: {
                    p1: { val: 95, label: "95% (Rooftop Farm Smart IoT)" },
                    p2: { val: 92, label: "92% (Integrasi Kurikulum Global)" },
                    p3: { val: 90, label: "90% (Zero-Waste Cafeteria)" },
                    p4: { val: 91, label: "91% (Komersialisasi Mandiri)" }
                },
                timeline: [
                    { bulan: "Januari 2024", modul: "Program Sekolah Hijau Terpadu", guru: "22 Org", siswa: "650 Murid", output: "🌱 Rooftop garden, zero-waste cafeteria & eco-fashion show." }
                ],
                pdf: "action-plan-alazhar1.pdf",
                pdf_uploader: "Diunggah Kepala Sekolah • 18 Feb 2024"
            }
        ];

        let currentSchoolId = 1;
        let leafletMap = null;
        let mapMarkers = {};
        let activeLayerUrban = true;
        let activeLayerCircular = true;
        let activeLayerLSO = true;

        // Initialize Map & App on DOM Ready
        document.addEventListener('DOMContentLoaded', () => {
            initLeafletMap();
            populateModalSchools();
            applyFilters();
            selectSchool(1); // default selection
        });

        function initLeafletMap() {
            leafletMap = L.map('gisMapCanvas', {
                center: [-7.0251, 107.65],
                zoom: 11,
                zoomControl: false
            });

            // Clean CartoDB Positron / OSM Basemap
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://carto.com/">CARTO</a> &bull; SEAMEO BIOTROP',
                maxZoom: 19
            }).addTo(leafletMap);

            // Live Coordinates display on mousemove
            leafletMap.on('mousemove', (e) => {
                const latStr = e.latlng.lat.toFixed(4);
                const lngStr = e.latlng.lng.toFixed(4);
                document.getElementById('coordDisplay').innerText = `Lat: ${latStr} S | Long: ${lngStr} E`;
            });

            renderMapMarkers();
        }

        function getMarkerClass(tracks) {
            if (tracks.length > 1) return 'multi';
            if (tracks.includes('urban')) return 'urban';
            if (tracks.includes('circular')) return 'circular';
            return 'lso';
        }

        function getMarkerIconSymbol(tracks) {
            if (tracks.length > 1) return '&#127807;';
            if (tracks.includes('urban')) return '🌱';
            if (tracks.includes('circular')) return '♻️';
            return '👥';
        }

        function renderMapMarkers() {
            // Clear existing markers
            Object.values(mapMarkers).forEach(m => leafletMap.removeLayer(m));
            mapMarkers = {};

            const provFilter = document.getElementById('provinsiSelect').value;
            const kabFilter = document.getElementById('kabupatenSelect').value;
            const searchVal = document.getElementById('schoolSearchInput').value.trim().toLowerCase();

            let visibleCount = 0;

            SEKOLAH_GIS.forEach(sk => {
                // Filter checks
                if (provFilter !== 'all' && sk.prov !== provFilter) return;
                if (kabFilter && sk.kab !== kabFilter) return;

                // Check layers
                const hasUrban = sk.tracks.includes('urban') && activeLayerUrban;
                const hasCircular = sk.tracks.includes('circular') && activeLayerCircular;
                const hasLSO = sk.tracks.includes('lso') && activeLayerLSO;
                if (!hasUrban && !hasCircular && !hasLSO) return;

                // Search check
                if (searchVal) {
                    const match = sk.nama.toLowerCase().includes(searchVal) || sk.npsn.includes(searchVal) || sk.kab.toLowerCase().includes(searchVal);
                    if (!match) return;
                }

                visibleCount++;

                // Create custom divIcon
                const cls = getMarkerClass(sk.tracks);
                const symbol = getMarkerIconSymbol(sk.tracks);
                const customIcon = L.divIcon({
                    className: '',
                    html: `
                        <div class="custom-gis-pin ${cls}">
                            <span>${symbol}</span>
                        </div>
                    `,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18],
                    popupAnchor: [0, -20]
                });

                const marker = L.marker([sk.lat, sk.lng], { icon: customIcon });

                // Popup content matching code.html design
                const popupContent = `
                    <div class="p-1 min-w-[220px]">
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">Capaian: ${sk.capaian}%</span>
                            <span class="text-gray-500 font-mono">NPSN: ${sk.npsn}</span>
                        </div>
                        <h4 class="font-bold text-gray-900 text-sm leading-tight">${sk.nama}</h4>
                        <p class="text-xs text-gray-600 mt-0.5">${sk.kab}, ${sk.prov}</p>
                        <div class="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-emerald-700 font-bold">${sk.tracks.length} Program Aktif</span>
                            <button onclick="selectSchool(${sk.id})" class="px-2.5 py-1 rounded-lg bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 transition-colors">
                                Buka Detail &rarr;
                            </button>
                        </div>
                    </div>
                `;

                marker.bindPopup(popupContent);
                marker.on('click', () => {
                    selectSchool(sk.id);
                });

                marker.addTo(leafletMap);
                mapMarkers[sk.id] = marker;
            });

            document.getElementById('mapDensityText').innerText = `Kerapatan: ${visibleCount} Sekolah Aktif`;
        }

        // Switch between DSS Regional Analysis and School Detail
        function switchRightTab(tab) {
            const tabDSS = document.getElementById('tabModeDSS');
            const tabDetail = document.getElementById('tabModeDetail');
            const viewDSS = document.getElementById('viewDSSSummary');
            const viewDetail = document.getElementById('viewSchoolDetail');

            if (tab === 'dss') {
                viewDSS.classList.remove('hidden');
                viewDetail.classList.add('hidden');

                tabDSS.className = "px-3 md:px-4 py-2 text-xs font-bold border-b-2 border-primary text-primary transition-all flex items-center gap-1.5";
                tabDetail.className = "px-3 md:px-4 py-2 text-xs font-semibold text-on-surface-variant border-b-2 border-transparent hover:text-on-surface transition-all flex items-center gap-1.5";
            } else {
                viewDetail.classList.remove('hidden');
                viewDSS.classList.add('hidden');

                tabDetail.className = "px-3 md:px-4 py-2 text-xs font-bold border-b-2 border-primary text-primary transition-all flex items-center gap-1.5";
                tabDSS.className = "px-3 md:px-4 py-2 text-xs font-semibold text-on-surface-variant border-b-2 border-transparent hover:text-on-surface transition-all flex items-center gap-1.5";
            }
        }

        // Select school and populate the right-side detail menu
        function selectSchool(id) {
            const sk = SEKOLAH_GIS.find(s => s.id === id);
            if (!sk) return;

            currentSchoolId = id;

            // Update Header & Identity
            document.getElementById('detail-school-name').innerText = sk.nama;
            document.getElementById('detail-school-npsn').innerText = `NPSN: ${sk.npsn}`;
            document.getElementById('detail-school-address').innerHTML = `<span class="material-symbols-outlined text-outline text-base">place</span> ${sk.alamat}`;
            document.getElementById('detail-school-badge').innerText = sk.badge;
            document.getElementById('detail-score').innerText = `${sk.capaian}%`;
            document.getElementById('detail-tier').innerText = sk.tier;

            // Render Track Tags
            const tracksContainer = document.getElementById('detail-school-tracks');
            tracksContainer.innerHTML = sk.tracks.map(t => {
                if (t === 'urban') return `<span class="px-2.5 py-1 rounded-full bg-primary/10 text-primary text-[11px] font-semibold border border-primary/20">SMART URBAN FARMING</span>`;
                if (t === 'circular') return `<span class="px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed-variant text-[11px] font-semibold border border-tertiary/20">CIRCULAR ECONOMY</span>`;
                return `<span class="px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant text-[11px] font-semibold border border-secondary/20">LSO LEADERSHIP</span>`;
            }).join(' ');

            // Update Rubric Progress Bars
            document.getElementById('detail-p1-text').innerText = sk.rubrik.p1.label;
            document.getElementById('detail-p1-bar').style.width = `${sk.rubrik.p1.val}%`;
            document.getElementById('detail-p2-text').innerText = sk.rubrik.p2.label;
            document.getElementById('detail-p2-bar').style.width = `${sk.rubrik.p2.val}%`;
            document.getElementById('detail-p3-text').innerText = sk.rubrik.p3.label;
            document.getElementById('detail-p3-bar').style.width = `${sk.rubrik.p3.val}%`;
            document.getElementById('detail-p4-text').innerText = sk.rubrik.p4.label;
            document.getElementById('detail-p4-bar').style.width = `${sk.rubrik.p4.val}%`;

            // Update Timeline
            const tlContainer = document.getElementById('detail-timeline');
            tlContainer.innerHTML = sk.timeline.map((item, idx) => `
                <div class="relative ${idx > 0 ? 'pt-1' : ''}">
                    <span class="absolute -left-[1.65rem] top-1.5 w-3.5 h-3.5 rounded-full ${idx === 0 ? 'bg-primary' : (idx === 1 ? 'bg-tertiary' : 'bg-secondary')} ring-4 ring-surface-container-lowest"></span>
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-on-surface">${item.modul}</h5>
                        <span class="text-[11px] text-on-surface-variant font-medium">${item.bulan}</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">${item.guru} &bull; ${item.siswa}</p>
                    <div class="mt-1.5 px-3 py-1.5 rounded bg-surface-container-low text-[11px] text-on-surface leading-relaxed">
                        ${item.output}
                    </div>
                </div>
            `).join('');

            // PDF info
            document.getElementById('detail-pdf-name').innerText = sk.pdf;
            document.getElementById('detail-pdf-uploader').innerText = sk.pdf_uploader;

            // Switch view to Detail Tab
            switchRightTab('detail');

            // Pan map
            if (leafletMap) {
                leafletMap.setView([sk.lat, sk.lng], 13, { animate: true });
                if (mapMarkers[sk.id]) {
                    mapMarkers[sk.id].openPopup();
                }
            }
        }

        function centerMapOnCurrentSchool() {
            const sk = SEKOLAH_GIS.find(s => s.id === currentSchoolId);
            if (sk && leafletMap) {
                leafletMap.setView([sk.lat, sk.lng], 14, { animate: true });
                if (mapMarkers[sk.id]) mapMarkers[sk.id].openPopup();
            }
        }

        function centerAllSchools() {
            if (!leafletMap) return;
            const valid = SEKOLAH_GIS.map(s => [s.lat, s.lng]);
            const bounds = L.latLngBounds(valid);
            leafletMap.fitBounds(bounds, { padding: [40, 40] });
        }

        function zoomInMap() { if (leafletMap) leafletMap.zoomIn(); }
        function zoomOutMap() { if (leafletMap) leafletMap.zoomOut(); }

        // Cascading & Filter logic
        function onProvinsiChange() {
            const prov = document.getElementById('provinsiSelect').value;
            const kabSelect = document.getElementById('kabupatenSelect');
            kabSelect.innerHTML = '<option value="">Semua Kab/Kota</option>';

            const kabList = [...new Set(
                SEKOLAH_GIS
                    .filter(s => prov === 'all' || s.prov === prov)
                    .map(s => s.kab)
            )].sort();

            kabList.forEach(kab => {
                const opt = document.createElement('option');
                opt.value = kab;
                opt.textContent = kab;
                kabSelect.appendChild(opt);
            });

            applyFilters();
            flyToTargetRegion();
        }

        function applyFilters() {
            activeLayerUrban = document.getElementById('layerUrban').checked;
            activeLayerCircular = document.getElementById('layerCircular').checked;
            activeLayerLSO = document.getElementById('layerLSO').checked;

            const searchVal = document.getElementById('schoolSearchInput').value.trim();
            const btnClear = document.getElementById('btnClearSearch');
            btnClear.classList.toggle('hidden', searchVal === '');

            renderMapMarkers();
            recalculateDSSSummary();
        }

        function clearSearch() {
            document.getElementById('schoolSearchInput').value = '';
            applyFilters();
        }

        function flyToTargetRegion() {
            const prov = document.getElementById('provinsiSelect').value;
            const kab = document.getElementById('kabupatenSelect').value;

            if (kab) {
                const sample = SEKOLAH_GIS.find(s => s.kab === kab);
                if (sample && leafletMap) {
                    leafletMap.setView([sample.lat, sample.lng], 12, { animate: true });
                    return;
                }
            }

            if (prov === 'Jawa Timur' && leafletMap) leafletMap.setView([-7.2575, 112.7521], 10);
            else if (prov === 'DKI Jakarta' && leafletMap) leafletMap.setView([-6.2088, 106.8456], 11);
            else if (prov === 'D.I. Yogyakarta' && leafletMap) leafletMap.setView([-7.7956, 110.3695], 11);
            else if (leafletMap) leafletMap.setView([-7.0251, 107.65], 11);
        }

        function resetFilters() {
            document.getElementById('provinsiSelect').value = 'Jawa Barat';
            document.getElementById('kabupatenSelect').value = 'Kab. Bandung';
            document.getElementById('schoolSearchInput').value = '';
            document.getElementById('layerUrban').checked = true;
            document.getElementById('layerCircular').checked = true;
            document.getElementById('layerLSO').checked = true;
            applyFilters();
            flyToTargetRegion();
            showToast("Filter berhasil direset ke kondisi awal.");
        }

        // Recalculate DSS Regional Summary Tab
        function recalculateDSSSummary() {
            const prov = document.getElementById('provinsiSelect').value;
            const kab = document.getElementById('kabupatenSelect').value;

            const activeSchools = SEKOLAH_GIS.filter(sk => {
                if (prov !== 'all' && sk.prov !== prov) return false;
                if (kab && sk.kab !== kab) return false;
                return true;
            });

            const total = activeSchools.length;
            const totalGuru = activeSchools.reduce((acc, s) => acc + s.guru.total, 0);
            const guruLk = activeSchools.reduce((acc, s) => acc + s.guru.lk, 0);
            const guruPr = activeSchools.reduce((acc, s) => acc + s.guru.pr, 0);

            const totalSiswa = activeSchools.reduce((acc, s) => acc + s.siswa.total, 0);
            const siswaLk = activeSchools.reduce((acc, s) => acc + s.siswa.lk, 0);
            const siswaPr = activeSchools.reduce((acc, s) => acc + s.siswa.pr, 0);

            const avgScore = total ? (activeSchools.reduce((acc, s) => acc + s.capaian, 0) / total).toFixed(1) : "0.0";

            document.getElementById('dss-total-school').innerText = total;
            document.getElementById('dss-total-guru').innerText = totalGuru;
            document.getElementById('dss-guru-lk').innerText = guruLk;
            document.getElementById('dss-guru-pr').innerText = `${guruPr} (${totalGuru ? Math.round((guruPr/totalGuru)*100) : 0}%)`;

            document.getElementById('dss-total-siswa').innerText = totalSiswa.toLocaleString('id');
            const lkPct = totalSiswa ? Math.round((siswaLk / totalSiswa) * 100) : 50;
            const prPct = 100 - lkPct;
            document.getElementById('dss-siswa-lk').innerText = `Laki-laki: ${siswaLk.toLocaleString('id')} (${lkPct}%)`;
            document.getElementById('dss-siswa-pr').innerText = `Perempuan: ${siswaPr.toLocaleString('id')} (${prPct}%)`;
            document.getElementById('dss-bar-lk').style.width = `${lkPct}%`;
            document.getElementById('dss-bar-pr').style.width = `${prPct}%`;

            document.getElementById('dss-avg-score').innerText = `${avgScore}%`;
            document.getElementById('dss-gauge-text').innerText = `${Math.round(avgScore)}%`;

            // Distribution bars
            const urbanCount = activeSchools.filter(s => s.tracks.includes('urban')).length;
            const circCount = activeSchools.filter(s => s.tracks.includes('circular')).length;
            const lsoCount = activeSchools.filter(s => s.tracks.includes('lso')).length;

            document.getElementById('dist-urban-text').innerText = `${urbanCount} Sekolah (${total ? Math.round((urbanCount/total)*100) : 0}%)`;
            document.getElementById('dist-urban-bar').style.width = `${total ? (urbanCount/total)*100 : 0}%`;

            document.getElementById('dist-circular-text').innerText = `${circCount} Sekolah (${total ? Math.round((circCount/total)*100) : 0}%)`;
            document.getElementById('dist-circular-bar').style.width = `${total ? (circCount/total)*100 : 0}%`;

            document.getElementById('dist-lso-text').innerText = `${lsoCount} Sekolah (${total ? Math.round((lsoCount/total)*100) : 0}%)`;
            document.getElementById('dist-lso-bar').style.width = `${total ? (lsoCount/total)*100 : 0}%`;

            // Region title
            const regionTitle = kab ? `Wilayah: ${prov} • ${kab}` : (prov !== 'all' ? `Wilayah: ${prov}` : 'Wilayah: Seluruh Indonesia (Nasional)');
            document.getElementById('regionHeaderTitle').innerText = regionTitle;
        }

        // Populate schools inside Modal
        function populateModalSchools() {
            const select = document.getElementById('modalSchoolSelect');
            select.innerHTML = SEKOLAH_GIS.map(s => `<option value="${s.id}">${s.nama} (${s.kab})</option>`).join('');
        }

        // Upload Modal Controls
        function openUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function openUploadModalForCurrentSchool() {
            document.getElementById('modalSchoolSelect').value = currentSchoolId;
            openUploadModal();
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
        }

        function onFileSelected(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileUploadLabel').innerText = `Berkas dipilih: ${input.files[0].name} (${(input.files[0].size/1024).toFixed(1)} KB)`;
            }
        }

        function handleUploadSubmit(e) {
            e.preventDefault();
            const fileInput = document.getElementById('actionPlanFileInput');
            const schoolId = parseInt(document.getElementById('modalSchoolSelect').value);
            const targetSchool = SEKOLAH_GIS.find(s => s.id === schoolId);

            if (fileInput.files && fileInput.files[0]) {
                const fileName = fileInput.files[0].name;
                if (targetSchool) {
                    targetSchool.pdf = fileName;
                    targetSchool.pdf_uploader = `Diunggah ${userNama} • Baru saja`;
                    if (targetSchool.id === currentSchoolId) {
                        document.getElementById('detail-pdf-name').innerText = fileName;
                        document.getElementById('detail-pdf-uploader').innerText = targetSchool.pdf_uploader;
                    }
                }
                closeUploadModal();
                showToast(`Action Plan PDF '${fileName}' berhasil diunggah.`);
            } else {
                showToast("Pilih berkas PDF terlebih dahulu.");
            }
        }

        // PDF Preview Modal
        function previewActionPlanPdf() {
            const sk = SEKOLAH_GIS.find(s => s.id === currentSchoolId);
            if (!sk) return;

            document.getElementById('previewModalTitle').innerText = `Preview: ${sk.pdf}`;
            document.getElementById('previewDocTitle').innerText = `Action Plan ${sk.nama} (Tingkat: ${sk.tier})`;
            document.getElementById('previewModal').classList.remove('hidden');
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
        }

        // Export Function
        function exportSummaryCSV() {
            let csv = "ID,Nama Sekolah,NPSN,Wilayah,Provinsi,Program,Capaian_Monev,Status_Tier\n";
            SEKOLAH_GIS.forEach(s => {
                csv += `${s.id},"${s.nama}","${s.npsn}","${s.kab}","${s.prov}","${s.tracks.join('&')}",${s.capaian}%,"${s.tier}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `Laporan_Spasial_GIS_SEKAR_${new Date().toISOString().slice(0,10)}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            showToast("Laporan Spasial GIS berhasil diekspor ke CSV.");
        }

        // Toast feedback
        function showToast(msg) {
            const toast = document.getElementById('toast-notify');
            const toastMsg = document.getElementById('toast-message');
            toastMsg.innerText = msg;
            toast.classList.remove('translate-y-20', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }
    </script>
</body>
</html>
