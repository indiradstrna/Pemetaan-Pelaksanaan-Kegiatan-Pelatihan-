<?php
/**
 * SEKAR BIOTROP
 * Sistem Evaluasi, Kemitraan, dan Aksi Rencana
 */

session_start();
require_once __DIR__ . '/config/constants.php';

$sudahLogin = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEKAR BIOTROP - Peta Monitoring Sekolah Pelatihan</title>
    <meta name="description" content="Sistem Evaluasi, Kemitraan, dan Aksi Rencana (SEKAR) SEAMEO BIOTROP - Peta monitoring sekolah peserta pelatihan Smart Urban Farming dan Circular Economy.">
    <link rel="icon" type="image/x-icon" href="assets/img/logo-biotrop.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --green-100: #e8f5e9;
            --green-200: #c8e6c9;
            --green-400: #66bb6a;
            --green-500: #43a047;
            --green-700: #2e7d32;
            --green-900: #1b5e20;
            --teal-500: #00897b;
            --amber-500: #f59e0b;
            --blue-500: #2563eb;
            --rose-500: #f43f5e;
            --text-dark: #111827;
            --text-med: #374151;
            --text-gray: #6b7280;
            --text-light: #9ca3af;
            --border: #e5e7eb;
            --surface: #ffffff;
            --surface-2: #f9fafb;
            --surface-3: #f3f4f6;
            --shadow-md: 0 4px 16px rgba(0,0,0,.1);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --clr-urban: #2e7d32;
            --clr-gems: #d97706;
            --clr-kultur: #0284c7;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--surface-2);
            color: var(--text-dark);
            overflow: hidden;
        }

        .topnav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 0 20px 0 18px;
            height: 78px;
            background: linear-gradient(90deg, #f7f7f7 0%, #f7f7f7 62%, #ffffff 100%);
            color: var(--text-dark);
            border-bottom: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
            position: relative;
            z-index: 20;
        }

        .topnav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2e7d32, #0f766e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 1.25rem;
            box-shadow: inset 0 0 0 2px rgba(255,255,255,.45);
        }

        .brand-text {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-text strong {
            font-size: 1.02rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .brand-text span {
            font-size: .74rem;
            color: var(--text-gray);
            opacity: .8;
            font-weight: 600;
            white-space: nowrap;
        }

        .topnav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }

        .header-search {
            display: flex;
            align-items: center;
            gap: 8px;
            width: min(360px, 34vw);
            background: #f3f4f6;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            padding: 9px 12px;
            color: var(--text-gray);
        }

        .header-search i { font-size: .9rem; }
        .header-search input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            color: var(--text-dark);
            font-family: inherit;
            font-size: .88rem;
        }
        .header-search input::placeholder { color: var(--text-gray); }

        .topnav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            color: var(--text-dark);
            text-decoration: none;
            background: #f3f4f6;
            border: 1px solid rgba(15, 23, 42, 0.08);
            font-size: 1rem;
        }

        .topnav-user {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            padding: 5px 10px 5px 5px;
            font-size: .82rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .topnav-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2e7d32, #0f766e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 800;
            color: #fff;
        }

        .app-shell {
            display: flex;
            height: calc(100vh - 78px);
            overflow: hidden;
            position: relative;
        }

        .left-sidebar {
            width: 76px;
            min-width: 76px;
            background: #ffffff;
            border-right: 1px solid var(--border);
            box-shadow: inset -1px 0 0 rgba(17,24,39,.02);
            padding: 14px 10px;
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

        .sidebar-section {
            display: none;
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
            color: var(--text-dark);
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
            font-size: 1.1rem;
            color: var(--text-gray);
        }

        .nav-item:hover {
            background: var(--green-100);
            border-color: rgba(46,125,50,.12);
            color: var(--green-900);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--green-700), var(--teal-500));
            color: #fff;
            box-shadow: 0 10px 20px rgba(46,125,50,.16);
        }

        .nav-item.active i {
            color: #fff;
        }

        .main-area {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .top-filter-bar {
            background: rgba(255,255,255,.94);
            border-bottom: 1px solid var(--border);
            padding: 14px 18px 12px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            position: relative;
            z-index: 10;
        }

        .filter-wrap {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 220px;
            max-width: 260px;
            flex: 1;
        }

        .filter-wrap label {
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-gray);
        }

        .select-wrap {
            position: relative;
        }

        .select-wrap select {
            width: 100%;
            padding: 10px 38px 10px 12px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: linear-gradient(180deg, #ffffff 0%, #f6faf7 100%);
            font-size: .84rem;
            color: var(--green-700);
            font-weight: 700;
            font-family: inherit;
            appearance: none;
            -webkit-appearance: none;
            outline: none;
            transition: all .15s ease;
        }

        .select-wrap select:focus {
            border-color: var(--green-400);
            box-shadow: 0 0 0 3px rgba(67, 160, 71, .12);
            background: linear-gradient(180deg, #ffffff 0%, #eefaf0 100%);
        }

        .select-wrap select option {
            color: var(--green-700);
            background: #ffffff;
            font-weight: 600;
            padding: 10px 12px;
        }

        .select-wrap select option:checked,
        .select-wrap select option:hover,
        .select-wrap select option:focus {
            background: linear-gradient(135deg, var(--green-700), var(--teal-500));
            color: #ffffff;
        }

        .select-wrap i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--green-700);
            pointer-events: none;
        }

        .program-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: auto;
            padding-top: 18px;
        }

        .prog-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            cursor: pointer;
            font-size: .76rem;
            font-weight: 700;
            color: #111111;
            transition: all .15s ease;
        }

        .prog-chip:hover {
            border-color: rgba(46, 125, 50, 0.45);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.10);
        }

        .prog-chip .chip-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 1px rgba(17,17,17,0.12);
        }

        .prog-chip.active {
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 8px 18px rgba(46,125,50,.16);
        }

        .prog-chip.active .chip-dot {
            background: #ffffff !important;
            box-shadow: none;
        }

        .prog-chip.urban.active { background: linear-gradient(135deg, #2e7d32, #4caf50); }
        .prog-chip.gems.active { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .prog-chip.kultur.active { background: linear-gradient(135deg, #0284c7, #38bdf8); }

        .map-area {
            position: relative;
            flex: 1;
            min-height: 0;
            overflow: hidden;
            background: #eaf3ea;
        }

        #sekarMap {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .map-legend {
            position: absolute;
            left: 14px;
            bottom: 20px;
            z-index: 500;
            background: rgba(255,255,255,.96);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 12px 14px;
            min-width: 180px;
        }

        .legend-title {
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            color: var(--text-gray);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .78rem;
            color: var(--text-med);
            margin-bottom: 6px;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .right-panel {
            width: 360px;
            min-width: 360px;
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width .25s ease, min-width .25s ease;
            position: relative;
            z-index: 15;
        }

        .right-panel.collapsed {
            width: 0;
            min-width: 0;
            border-left: none;
        }

        .panel-open-btn {
            position: absolute;
            right: 16px;
            top: 18px;
            z-index: 30;
            width: 42px;
            height: 42px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.96);
            color: var(--green-700);
            box-shadow: var(--shadow-md);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: all .2s ease;
        }

        .panel-open-btn:hover {
            transform: translateY(-1px);
            background: var(--green-100);
            border-color: rgba(46, 125, 50, 0.25);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            background: rgba(255,255,255,.7);
        }

        .panel-header-title {
            font-size: .9rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-header-title i { color: var(--green-500); }

        .panel-collapse-btn {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 1rem;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
        }

        .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 360px;
        }

        .right-panel.collapsed .panel-body { display: none; }

        .region-ctx {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--green-100);
            border-radius: var(--radius-sm);
            padding: 9px 12px;
            font-size: .8rem;
            font-weight: 700;
            color: var(--green-900);
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 8px;
        }

        .metric-card {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .metric-card.highlight {
            grid-column: span 2;
            background: linear-gradient(135deg, var(--green-700), var(--teal-500));
            border-color: transparent;
            color: #fff;
        }

        .metric-card.highlight .metric-label,
        .metric-card.highlight .metric-sub {
            color: rgba(255,255,255,.75);
        }

        .metric-card.highlight .metric-value {
            color: #fff;
        }

        .metric-icon { font-size: 1.1rem; margin-bottom: 2px; }

        .metric-label {
            font-size: .7rem;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .metric-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
        }

        .metric-sub {
            font-size: .68rem;
            color: var(--text-gray);
        }

        .section-label {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-gray);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .monev-list { display: flex; flex-direction: column; gap: 8px; }

        .monev-item { display: flex; flex-direction: column; gap: 4px; }
        .monev-item-header { display: flex; justify-content: space-between; align-items: center; }
        .monev-item-label { font-size: .78rem; font-weight: 600; color: var(--text-med); }
        .monev-item-pct { font-size: .78rem; font-weight: 800; }
        .monev-bar { height: 6px; background: var(--surface-3); border-radius: 99px; overflow: hidden; }
        .monev-bar-fill { height: 100%; border-radius: 99px; }

        .gender-split { display: flex; gap: 8px; }
        .gender-bar-wrap {
            flex: 1;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            text-align: center;
        }
        .gender-bar-wrap .gval { font-size: 1.3rem; font-weight: 800; }
        .gender-bar-wrap .glabel { font-size: .7rem; color: var(--text-gray); font-weight: 600; margin-top: 2px; }
        .gender-bar-wrap.male .gval { color: var(--blue-500); }
        .gender-bar-wrap.female .gval { color: var(--rose-500); }

        #donutProgram { width: 100%; min-height: 150px; }

        .sekolah-search {
            position: relative;
        }

        .sekolah-search i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            font-size: .82rem;
        }

        .sekolah-search input {
            width: 100%;
            padding: 9px 12px 9px 32px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: .84rem;
            font-family: inherit;
            outline: none;
        }

        .sekolah-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 260px;
            overflow-y: auto;
        }

        .sekolah-card {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            cursor: pointer;
            transition: all .15s ease;
            background: var(--surface);
        }

        .sekolah-card:hover { border-color: var(--green-400); box-shadow: 0 2px 12px rgba(67,160,71,.12); }
        .sekolah-card.active { border-color: var(--green-500); background: var(--green-100); }

        .sk-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 5px; }
        .sk-name { font-size: .86rem; font-weight: 700; line-height: 1.3; color: var(--text-dark); flex: 1; }
        .sk-tags { display: flex; gap: 4px; flex-shrink: 0; }
        .sk-tag {
            width: 22px; height: 22px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .62rem; font-weight: 800;
        }
        .sk-tag.urban { background: var(--clr-urban); }
        .sk-tag.gems { background: var(--clr-gems); }
        .sk-tag.kultur { background: var(--clr-kultur); }
        .sk-meta { font-size: .74rem; color: var(--text-gray); display: flex; align-items: center; gap: 4px; }
        .sk-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
        .sk-monev-bar { flex: 1; height: 4px; background: var(--surface-3); border-radius: 99px; overflow: hidden; margin-right: 10px; }
        .sk-monev-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--green-500), var(--teal-500)); }
        .sk-monev-pct { font-size: .72rem; font-weight: 800; color: var(--green-700); white-space: nowrap; }

        .empty-state { text-align: center; padding: 30px 16px; color: var(--text-gray); font-size: .84rem; }
        .empty-state i { font-size: 2rem; display: block; margin-bottom: 8px; opacity: .4; }

        .sekolah-detail-panel {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 360px;
            background: var(--surface);
            border-left: 1px solid var(--border);
            z-index: 600;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            box-shadow: -6px 0 24px rgba(0,0,0,.1);
        }

        .sekolah-detail-panel.open { transform: translateX(0); }

        .detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 18px 18px 14px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .detail-school-name { font-size: .95rem; font-weight: 800; line-height: 1.3; }
        .detail-school-loc { font-size: .76rem; color: var(--text-gray); margin-top: 3px; display: flex; align-items: center; gap: 4px; }
        .detail-close-btn {
            background: none; border: none; font-size: 1.1rem; color: var(--text-gray);
            cursor: pointer; padding: 2px 4px; border-radius: 6px;
        }

        .detail-body {
            flex: 1;
            overflow-y: auto;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .detail-prog-tags { display: flex; gap: 6px; flex-wrap: wrap; }
        .detail-prog-tag {
            display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; color: #fff; font-size: .75rem; font-weight: 700;
        }
        .detail-prog-tag.urban { background: var(--clr-urban); }
        .detail-prog-tag.gems { background: var(--clr-gems); }
        .detail-prog-tag.kultur { background: var(--clr-kultur); }

        .monev-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 8px; }
        .monev-detail-item {
            background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; text-align: center;
        }
        .monev-detail-item .mval { font-size: 1.2rem; font-weight: 800; color: var(--text-dark); }
        .monev-detail-item .mlabel { font-size: .68rem; color: var(--text-gray); font-weight: 600; margin-top: 2px; }
        .monev-detail-item.green .mval { color: var(--green-700); }
        .monev-detail-item.blue .mval { color: var(--blue-500); }
        .monev-detail-item.rose .mval { color: var(--rose-500); }

        .impl-status {
            display: flex; align-items: center; gap: 10px;
            background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px 14px;
        }
        .impl-status-icon { font-size: 1.6rem; }
        .impl-status-text strong { display: block; font-size: .88rem; }
        .impl-status-text span { font-size: .74rem; color: var(--text-gray); }

        .timeline { display: flex; flex-direction: column; gap: 0; }
        .tl-item { display: flex; gap: 12px; }
        .tl-line-wrap { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; width: 18px; }
        .tl-dot {
            width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--surface); box-shadow: 0 0 0 2px currentColor; margin-top: 3px;
        }
        .tl-dot.urban { color: var(--clr-urban); background: var(--clr-urban); }
        .tl-dot.gems { color: var(--clr-gems); background: var(--clr-gems); }
        .tl-dot.kultur { color: var(--clr-kultur); background: var(--clr-kultur); }
        .tl-connector { width: 2px; flex: 1; min-height: 16px; background: var(--border); margin: 3px 0; }
        .tl-content { flex: 1; padding-bottom: 16px; }
        .tl-month { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-gray); margin-bottom: 4px; }
        .tl-title { font-size: .84rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; line-height: 1.3; }
        .tl-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
        .tl-pill {
            display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; color: var(--text-med); background: var(--surface-2); padding: 3px 9px; border-radius: 999px; border: 1px solid var(--border);
        }
        .tl-pdf-btn {
            display: inline-flex; align-items: center; gap: 6px; font-size: .76rem; font-weight: 700; color: var(--blue-500); background: #eff6ff; border: 1px solid #bfdbfe; padding: 5px 12px; border-radius: var(--radius-sm); text-decoration: none;
        }

        .sekar-marker {
            width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            border: 3px solid #fff; box-shadow: 0 3px 10px rgba(0,0,0,.3); color: #fff; font-size: .78rem; font-weight: 800;
        }
        .sekar-marker.urban { background: var(--clr-urban); }
        .sekar-marker.gems { background: var(--clr-gems); }
        .sekar-marker.kultur { background: var(--clr-kultur); }

        .leaflet-popup-content-wrapper { border-radius: 12px !important; box-shadow: var(--shadow-lg) !important; padding: 0 !important; overflow: hidden; }
        .leaflet-popup-content { margin: 0 !important; width: auto !important; }
        .leaflet-popup-tip-container { display: none; }

        .map-popup { width: 240px; font-family: 'Plus Jakarta Sans', sans-serif; }
        .map-popup-header { background: linear-gradient(135deg, var(--green-700), var(--teal-500)); color: #fff; padding: 12px 14px; }
        .map-popup-header h6 { margin: 0 0 2px; font-size: .88rem; font-weight: 800; }
        .map-popup-header p { margin: 0; font-size: .72rem; opacity: .85; }
        .map-popup-body { padding: 10px 14px 12px; }
        .popup-stat { display: flex; justify-content: space-between; align-items: center; font-size: .76rem; padding: 4px 0; border-bottom: 1px solid var(--border); color: var(--text-med); }
        .popup-stat:last-child { border-bottom: none; }
        .popup-stat strong { color: var(--text-dark); font-weight: 700; }
        .popup-open-btn {
            display: block; width: 100%; text-align: center; background: var(--green-500); color: #fff; border: none; padding: 8px; border-radius: 8px; font-size: .78rem; font-weight: 700; cursor: pointer; margin-top: 10px; font-family: inherit;
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }

        @media (max-width: 1100px) {
            .right-panel {
                width: 300px;
                min-width: 300px;
            }
            .panel-body { width: 300px; }
        }

        @media (max-width: 900px) {
            .app-shell {
                flex-direction: column;
                height: auto;
                overflow: auto;
            }
            .main-area { min-height: 52vh; }
            .right-panel {
                width: 100%;
                min-width: 100%;
                border-left: none;
                border-top: 1px solid var(--border);
            }
            .panel-body { width: 100%; }
        }
    </style>
</head>
<body>
    <nav class="topnav">
        <a href="<?= BASE_URL ?>/user/index.php" class="topnav-brand" title="Kembali ke Activities in Spatial">
            <div class="brand-mark">P</div>
            <div class="brand-text">
                <strong>PINTAR</strong>
                <span>Monitoring &amp; Evaluasi</span>
            </div>
        </a>

        <div class="topnav-actions">
            <label class="header-search" aria-label="Pencarian sekolah">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search..." id="headerSearchInput" />
            </label>

            <a href="#" class="topnav-link" title="Notifikasi"><i class="bi bi-bell"></i></a>

            <?php if ($sudahLogin): ?>
                <div class="topnav-user">
                    <div class="topnav-avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?></div>
                    <span><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></span>
                </div>
                <?php if (($_SESSION['role'] ?? '') !== 'user'): ?>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="topnav-link" title="Admin dashboard"><i class="bi bi-speedometer2"></i></a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="topnav-link" title="Masuk"><i class="bi bi-box-arrow-in-right"></i></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="app-shell">
        <aside class="left-sidebar">
            <div class="sidebar-title">
                <span class="mini-mark">P</span>
                <span>Menu</span>
            </div>

            <nav class="sidebar-nav">
                <a href="sekar.php" class="nav-item active" data-label="Peta Monitoring"><i class="bi bi-map"></i></a>
                <a href="dashboard.php" class="nav-item" data-label="Dashboard"><i class="bi bi-bar-chart"></i></a>
                <button class="nav-item" type="button" data-label="Data Sekolah"><i class="bi bi-building"></i></button>
                <button class="nav-item" type="button" data-label="Lokasi"><i class="bi bi-geo-alt"></i></button>
                <button class="nav-item" type="button" data-label="Mitra"><i class="bi bi-people"></i></button>
                <button class="nav-item" type="button" data-label="Profil"><i class="bi bi-person-circle"></i></button>
                <button class="nav-item" type="button" data-label="Pengaturan"><i class="bi bi-gear"></i></button>
            </nav>
        </aside>

        <div class="main-area">
            <div class="top-filter-bar">
                <div class="filter-wrap">
                    <label for="selProvinsi">Provinsi</label>
                    <div class="select-wrap">
                        <select id="selProvinsi">
                            <option value="">— Semua Provinsi —</option>
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                </div>

                <div class="filter-wrap">
                    <label for="selKabupaten">Kabupaten / Kota</label>
                    <div class="select-wrap">
                        <select id="selKabupaten" disabled>
                            <option value="">— Pilih Provinsi Dulu —</option>
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                </div>

                <div class="program-filter" aria-label="Filter program">
                    <div class="prog-chip urban active" data-prog="urban">
                        <span class="chip-dot" style="background:var(--clr-urban)"></span>
                        Smart Urban Farming
                    </div>
                    <div class="prog-chip gems active" data-prog="gems">
                        <span class="chip-dot" style="background:var(--clr-gems)"></span>
                        GEMS
                    </div>
                    <div class="prog-chip kultur active" data-prog="kultur">
                        <span class="chip-dot" style="background:var(--clr-kultur)"></span>
                        Kultur Jaringan
                    </div>
                </div>
            </div>

            <div class="map-area">
                <div id="sekarMap"></div>

                <div class="map-legend">
                    <div class="legend-title">Legenda Program</div>
                    <div class="legend-item"><span class="legend-dot" style="background:var(--clr-urban)"></span> Smart Urban Farming</div>
                    <div class="legend-item"><span class="legend-dot" style="background:var(--clr-gems)"></span> GEMS (Geopark)</div>
                    <div class="legend-item"><span class="legend-dot" style="background:var(--clr-kultur)"></span> Kultur Jaringan</div>
                </div>

                <div class="sekolah-detail-panel" id="detailPanel">
                    <div class="detail-header">
                        <div>
                            <div class="detail-school-name" id="detailName">—</div>
                            <div class="detail-school-loc" id="detailLoc"><i class="bi bi-geo-alt"></i> —</div>
                        </div>
                        <button class="detail-close-btn" id="detailCloseBtn"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="detail-body" id="detailBody"></div>
                </div>
            </div>
        </div>

        <aside class="right-panel" id="rightPanel">
            <div class="panel-header">
                <div class="panel-header-title">
                    <i class="bi bi-bar-chart-fill"></i> Dashboard Monev
                </div>
                <button class="panel-collapse-btn" id="rightCollapseBtn" title="Sembunyikan">
                    <i class="bi bi-layout-sidebar-inset-reverse"></i>
                </button>
            </div>

            <div class="panel-body">
                <div class="region-ctx" id="regionCtx">
                    <i class="bi bi-globe-asia-australia"></i>
                    <span id="regionCtxText">Seluruh Indonesia</span>
                </div>

                <div class="metric-grid">
                    <div class="metric-card highlight">
                        <div class="metric-label">Total Siswa Terdampak</div>
                        <div class="metric-value" id="mTotalSiswa">—</div>
                        <div class="metric-sub" id="mTotalSiswaSub">dari seluruh sekolah peserta</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">🏫</div>
                        <div class="metric-label">Sekolah Terlatih</div>
                        <div class="metric-value" id="mSekolah">—</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">👨‍🏫</div>
                        <div class="metric-label">Guru Terlibat</div>
                        <div class="metric-value" id="mGuru">—</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">🌱</div>
                        <div class="metric-label">Urban Farming</div>
                        <div class="metric-value" id="mUrban">—</div>
                        <div class="metric-sub">sekolah</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">⛰️</div>
                        <div class="metric-label">GEMS</div>
                        <div class="metric-value" id="mGems">—</div>
                        <div class="metric-sub">sekolah</div>
                    </div>
                    <div class="metric-card" style="grid-column: span 2;">
                        <div class="metric-icon">🧫</div>
                        <div class="metric-label">Kultur Jaringan</div>
                        <div class="metric-value" id="mKultur">—</div>
                        <div class="metric-sub">sekolah</div>
                    </div>
                </div>

                <div>
                    <div class="section-label"><i class="bi bi-clipboard2-check-fill"></i> Komponen Monev</div>
                    <div class="monev-list" id="monevList"></div>
                </div>

                <div>
                    <div class="section-label"><i class="bi bi-people-fill"></i> Gender Guru</div>
                    <div class="gender-split">
                        <div class="gender-bar-wrap male">
                            <div class="gval" id="gLaki">—</div>
                            <div class="glabel"><i class="bi bi-gender-male"></i> Laki-laki</div>
                        </div>
                        <div class="gender-bar-wrap female">
                            <div class="gval" id="gPerempuan">—</div>
                            <div class="glabel"><i class="bi bi-gender-female"></i> Perempuan</div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="section-label"><i class="bi bi-pie-chart-fill"></i> Sebaran Program</div>
                    <div id="donutProgram"></div>
                </div>

                <div>
                    <div class="section-label"><i class="bi bi-building"></i> Daftar Sekolah</div>
                    <div class="sekolah-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="sekolahSearch" placeholder="Cari nama sekolah...">
                    </div>
                    <div class="sekolah-list" id="sekolahList">
                        <div class="empty-state"><i class="bi bi-hourglass-split"></i> Memuat data sekolah...</div>
                    </div>
                </div>
            </div>
        </aside>

        <button class="panel-open-btn" id="rightOpenBtn" title="Munculkan panel kanan" aria-label="Munculkan panel kanan">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        let allSekolah = [];
        let filteredSek = [];
        let activeProgs = new Set(['urban', 'gems', 'kultur']);
        let selProv = '';
        let selKab = '';
        let searchQ = '';
        let activeMarkers = {};
        let donutChart = null;

        const map = L.map('sekarMap', { center: [-2.5, 117.5], zoom: 5, zoomControl: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(map);

        const markerLayer = L.layerGroup().addTo(map);

        function makeMarkerIcon(program) {
            let cls = 'urban';
            let icon = '🌱';
            if (program.includes('gems')) {
                cls = 'gems';
                icon = '⛰️';
            } else if (program.includes('kultur')) {
                cls = 'kultur';
                icon = '🧫';
            }
            return L.divIcon({
                className: '',
                html: `<div class="sekar-marker ${cls}">${icon}</div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
                popupAnchor: [0, -22]
            });
        }

        function getProgramLabel(program) {
            const labels = [];
            if (program.includes('urban')) labels.push('Smart Urban Farming');
            if (program.includes('gems')) labels.push('GEMS (Geopark)');
            if (program.includes('kultur')) labels.push('Kultur Jaringan');
            return labels.join(' & ') || 'Pelatihan BIOTROP';
        }

        function renderMarkers() {
            markerLayer.clearLayers();
            activeMarkers = {};

            filteredSek.forEach(sk => {
                const marker = L.marker([sk.lat, sk.lng], { icon: makeMarkerIcon(sk.program) });
                const progLabel = getProgramLabel(sk.program);
                const popupHTML = `
                    <div class="map-popup">
                        <div class="map-popup-header">
                            <h6>${sk.nama}</h6>
                            <p>${sk.kab}, ${sk.prov}</p>
                        </div>
                        <div class="map-popup-body">
                            <div class="popup-stat"><span>Program</span><strong>${progLabel}</strong></div>
                            <div class="popup-stat"><span>Guru</span><strong>${sk.dapodik.guru} orang</strong></div>
                            <div class="popup-stat"><span>Murid</span><strong>${sk.dapodik.murid.toLocaleString('id')} siswa</strong></div>
                            <div class="popup-stat"><span>Siswa Terdampak</span><strong>${sk.monev.siswa_terdampak.toLocaleString('id')} siswa</strong></div>
                            <div class="popup-stat"><span>Implementasi</span><strong>${sk.monev.persentase}%</strong></div>
                            <button class="popup-open-btn" onclick="openDetailPanel(${sk.id})">&#8505; Lihat Detail &amp; Pelatihan</button>
                        </div>
                    </div>`;

                marker.bindPopup(popupHTML, { maxWidth: 280, minWidth: 240 });
                marker.on('click', () => {
                    document.querySelectorAll('.sekolah-card').forEach(c => c.classList.remove('active'));
                    const card = document.getElementById('sk-card-' + sk.id);
                    if (card) {
                        card.classList.add('active');
                        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
                marker.addTo(markerLayer);
                activeMarkers[sk.id] = marker;
            });
        }

        function getProvinsi() { return [...new Set(allSekolah.map(s => s.prov))].sort(); }
        function getKabupaten(prov) { return [...new Set(allSekolah.filter(s => s.prov === prov).map(s => s.kab))].sort(); }

        function populateProvinsi() {
            const sel = document.getElementById('selProvinsi');
            if (!sel) return;
            sel.innerHTML = '<option value="">— Semua Provinsi —</option>';
            getProvinsi().forEach(p => {
                const o = document.createElement('option');
                o.value = p; o.textContent = p; sel.appendChild(o);
            });
        }

        function populateKabupaten(prov) {
            const sel = document.getElementById('selKabupaten');
            if (!sel) return;
            if (!prov) {
                sel.innerHTML = '<option value="">— Pilih Provinsi Dulu —</option>';
                sel.disabled = true;
                return;
            }
            sel.disabled = false;
            sel.innerHTML = '<option value="">— Semua Kabupaten/Kota —</option>';
            getKabupaten(prov).forEach(k => {
                const o = document.createElement('option');
                o.value = k; o.textContent = k; sel.appendChild(o);
            });
        }

        function syncSearchInputs(value) {
            const headerInput = document.getElementById('headerSearchInput');
            const sekolahSearch = document.getElementById('sekolahSearch');
            if (headerInput && headerInput.value !== value) headerInput.value = value;
            if (sekolahSearch && sekolahSearch.value !== value) sekolahSearch.value = value;
        }

        function fitMapToFilteredData() {
            if (!filteredSek.length) return;
            const bounds = L.latLngBounds(filteredSek.map(s => [s.lat, s.lng]));
            if (filteredSek.length === 1) {
                map.setView([filteredSek[0].lat, filteredSek[0].lng], 11, { animate: true });
                return;
            }
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: selKab ? 10 : 7 });
        }

        function applyFilters() {
            filteredSek = allSekolah.filter(sk => {
                if (selProv && sk.prov !== selProv) return false;
                if (selKab && sk.kab !== selKab) return false;

                const hasProg = sk.program.some(p => activeProgs.has(p));
                if (!hasProg) return false;

                if (searchQ && !sk.nama.toLowerCase().includes(searchQ)) return false;
                return true;
            });

            renderMarkers();
            renderSekolahList();
            renderDashboard();
            fitMapToFilteredData();

            const regionText = selKab ? `${selKab}, ${selProv}` : (selProv ? selProv : 'Seluruh Indonesia');
            const regionCtx = document.getElementById('regionCtxText');
            if (regionCtx) regionCtx.textContent = regionText;
        }

        function renderSekolahList() {
            const list = document.getElementById('sekolahList');
            const count = document.getElementById('sekolahCount');
            if (count) count.textContent = `${filteredSek.length} sekolah`;
            if (!list) return;

            if (!filteredSek.length) {
                list.innerHTML = '<div class="empty-state"><i class="bi bi-search"></i>Tidak ada sekolah ditemukan</div>';
                return;
            }

            const tagIcons = {
                'urban': { icon: '🌱', label: 'Smart Urban Farming' },
                'gems': { icon: '⛰️', label: 'GEMS' },
                'kultur': { icon: '🧫', label: 'Kultur Jaringan' }
            };

            list.innerHTML = filteredSek.map(sk => {
                const tags = (sk.program || []).map(p => {
                    const info = tagIcons[p] || { icon: '🌱', label: p };
                    return `<span class="sk-tag ${p}" title="${info.label}">${info.icon}</span>`;
                }).join('');
                return `
                    <div class="sekolah-card" id="sk-card-${sk.id}" onclick="focusSekolah(${sk.id})">
                        <div class="sk-top">
                            <div class="sk-name">${sk.nama}</div>
                            <div class="sk-tags">${tags}</div>
                        </div>
                        <div class="sk-meta"><i class="bi bi-geo-alt" style="color:var(--green-500)"></i> ${sk.kab}, ${sk.prov}</div>
                        <div class="sk-meta" style="margin-top:3px;"><i class="bi bi-people" style="color:var(--blue-500)"></i> ${sk.dapodik.guru} guru &middot; ${sk.dapodik.murid.toLocaleString('id')} murid</div>
                        <div class="sk-footer">
                            <div class="sk-monev-bar"><div class="sk-monev-fill" style="width:${sk.monev.persentase}%"></div></div>
                            <div class="sk-monev-pct">${sk.monev.persentase}%</div>
                        </div>
                    </div>`;
            }).join('');
        }

        function focusSekolah(id) {
            const sk = allSekolah.find(s => s.id === id);
            if (!sk) return;
            document.querySelectorAll('.sekolah-card').forEach(c => c.classList.remove('active'));
            const card = document.getElementById('sk-card-' + id);
            if (card) card.classList.add('active');
            const marker = activeMarkers[id];
            if (marker) {
                map.flyTo([sk.lat, sk.lng], 12, { duration: 1 });
                setTimeout(() => marker.openPopup(), 500);
            }
        }

        function renderDashboard() {
            const sek = filteredSek;
            const totalSiswa = sek.reduce((a, s) => a + s.monev.siswa_terdampak, 0);
            const totalGuru = sek.reduce((a, s) => a + s.monev.guru_kolaborasi, 0);
            const totalUrban = sek.filter(s => s.program.includes('urban')).length;
            const totalGems = sek.filter(s => s.program.includes('gems')).length;
            const totalKultur = sek.filter(s => s.program.includes('kultur')).length;
            const gLaki = sek.reduce((a, s) => a + s.guru_gender.laki, 0);
            const gPerem = sek.reduce((a, s) => a + s.guru_gender.perempuan, 0);
            const avgPct = sek.length ? Math.round(sek.reduce((a, s) => a + s.monev.persentase, 0) / sek.length) : 0;
            const diterapkan = sek.filter(s => s.monev.diterapkan).length;
            const totalDapodikGuru = sek.reduce((a, s) => a + s.dapodik.guru, 0);
            const totalDapodikMurid = sek.reduce((a, s) => a + s.dapodik.murid, 0);

            const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            setText('mTotalSiswa', totalSiswa.toLocaleString('id'));
            setText('mTotalSiswaSub', `dari ${sek.length} sekolah peserta`);
            setText('mSekolah', sek.length);
            setText('mGuru', totalGuru);
            setText('mUrban', totalUrban);
            setText('mGems', totalGems);
            setText('mKultur', totalKultur);
            setText('gLaki', gLaki);
            setText('gPerempuan', gPerem);

            const monevData = [
                { label: 'Action Plan Diterapkan', pct: sek.length ? Math.round((diterapkan / sek.length) * 100) : 0, color: '#2e7d32' },
                { label: 'Rata-rata Implementasi', pct: avgPct, color: '#7c3aed' },
                { label: 'Guru Berkolaborasi', pct: totalDapodikGuru ? Math.min(100, Math.round((totalGuru / totalDapodikGuru) * 100)) : 0, color: '#00897b' },
                { label: 'Siswa Terlibat', pct: totalDapodikMurid ? Math.min(100, Math.round((totalSiswa / totalDapodikMurid) * 100)) : 0, color: '#2563eb' }
            ];

            const monevList = document.getElementById('monevList');
            if (monevList) {
                monevList.innerHTML = monevData.map(m => `
                    <div class="monev-item">
                        <div class="monev-item-header">
                            <span class="monev-item-label">${m.label}</span>
                            <span class="monev-item-pct" style="color:${m.color}">${m.pct}%</span>
                        </div>
                        <div class="monev-bar"><div class="monev-bar-fill" style="width:${m.pct}%;background:${m.color}"></div></div>
                    </div>
                `).join('');
            }

            if (donutChart) donutChart.destroy();
            donutChart = new ApexCharts(document.getElementById('donutProgram'), {
                chart: { type: 'donut', height: 160, fontFamily: 'Plus Jakarta Sans, sans-serif', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
                series: [totalUrban, totalGems, totalKultur],
                labels: ['Smart Urban Farming', 'GEMS', 'Kultur Jaringan'],
                colors: ['#2e7d32', '#d97706', '#0284c7'],
                legend: { position: 'bottom', fontSize: '11px' },
                dataLabels: { enabled: false },
                plotOptions: { pie: { donut: { size: '60%', labels: { show: true, total: { show: true, label: 'Sekolah', fontSize: '11px', color: '#6b7280', formatter: () => sek.length } } } } },
                stroke: { width: 2 }
            });
            donutChart.render();
        }

        function openDetailPanel(id) {
            const sk = allSekolah.find(s => s.id === id);
            if (!sk) return;

            document.getElementById('detailName').textContent = sk.nama;
            document.getElementById('detailLoc').innerHTML = `<i class="bi bi-geo-alt"></i> ${sk.kab}, ${sk.prov}`;

            const progBadges = {
                'urban': '🌱 Smart Urban Farming',
                'gems': '⛰️ GEMS (Geopark)',
                'kultur': '🧫 Kultur Jaringan'
            };
            const progHTML = sk.program.map(p => `<span class="detail-prog-tag ${p}">${progBadges[p] || p}</span>`).join('');
            const tlHTML = sk.pelatihan.map((p, i, arr) => `
                <div class="tl-item">
                    <div class="tl-line-wrap">
                        <div class="tl-dot ${p.program === 'both' ? 'urban' : p.program}"></div>
                        ${i < arr.length - 1 ? '<div class="tl-connector"></div>' : ''}
                    </div>
                    <div class="tl-content">
                        <div class="tl-month">${p.bulan}</div>
                        <div class="tl-title">${p.judul}</div>
                        <div class="tl-meta">
                            <span class="tl-pill"><i class="bi bi-person-workspace"></i> ${p.guru} peserta</span>
                            <span class="tl-pill"><i class="bi bi-people"></i> ${p.murid} murid</span>
                        </div>
                        ${p.peserta_list && p.peserta_list.length ? `
                        <div style="font-size:.75rem;color:var(--text-med);margin-bottom:6px;background:var(--surface-3);padding:4px 8px;border-radius:6px;">
                            <i class="bi bi-person-check-fill" style="color:var(--green-700)"></i> <strong>Peserta:</strong> ${p.peserta_list.join(', ')}
                        </div>` : ''}
                        <div style="margin-bottom:8px;">
                            ${p.action_plans.map(ap => `<div style="font-size:.74rem;color:var(--text-med);padding:2px 0 2px 10px;border-left:2px solid var(--green-400);margin-bottom:3px;">&#128203; ${ap}</div>`).join('')}
                        </div>
                        <a class="tl-pdf-btn" href="${p.pdf}" target="_blank"><i class="bi bi-file-earmark-pdf-fill"></i> Download Action Plan PDF</a>
                    </div>
                </div>
            `).join('');

            const implIcon = sk.monev.diterapkan ? '&#9989;' : '&#9203;';
            const implText = sk.monev.diterapkan ? 'Sudah Diterapkan' : 'Belum Diterapkan';
            const implSub = sk.monev.diterapkan ? 'Action plan berjalan aktif' : 'Masih dalam proses perencanaan';

            document.getElementById('detailBody').innerHTML = `
                <div class="detail-prog-tags">${progHTML}</div>

                <div>
                    <div class="section-label"><i class="bi bi-database-fill"></i> Data Dapodik</div>
                    <div class="monev-detail-grid">
                        <div class="monev-detail-item"><div class="mval">${sk.dapodik.guru}</div><div class="mlabel">Total Guru</div></div>
                        <div class="monev-detail-item blue"><div class="mval">${sk.dapodik.murid.toLocaleString('id')}</div><div class="mlabel">Total Murid</div></div>
                        <div class="monev-detail-item"><div class="mval">${sk.guru_gender.laki}</div><div class="mlabel">&#128104; Guru Laki-laki</div></div>
                        <div class="monev-detail-item rose"><div class="mval">${sk.guru_gender.perempuan}</div><div class="mlabel">&#128105; Guru Perempuan</div></div>
                    </div>
                </div>

                <div>
                    <div class="section-label"><i class="bi bi-calendar-event-fill"></i> Riwayat Pelatihan</div>
                    <div class="timeline">${tlHTML}</div>
                </div>

                <div>
                    <div class="section-label"><i class="bi bi-clipboard2-check-fill"></i> Monev Sekolah</div>
                    <div class="impl-status" style="margin-bottom:10px;">
                        <div class="impl-status-icon">${implIcon}</div>
                        <div class="impl-status-text">
                            <strong>${implText}</strong>
                            <span>${implSub}</span>
                        </div>
                    </div>
                    <div class="monev-detail-grid">
                        <div class="monev-detail-item green"><div class="mval">${sk.monev.siswa_terdampak.toLocaleString('id')}</div><div class="mlabel">Siswa Terdampak</div></div>
                        <div class="monev-detail-item blue"><div class="mval">${sk.monev.guru_kolaborasi}</div><div class="mlabel">Guru Kolaborasi</div></div>
                    </div>
                    <div style="margin-top:10px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                            <span style="font-size:.76rem;font-weight:700;color:var(--text-gray);">Tingkat Implementasi</span>
                            <span style="font-size:.76rem;font-weight:800;color:var(--green-700);">${sk.monev.persentase}%</span>
                        </div>
                        <div class="monev-bar" style="height:10px;"><div class="monev-bar-fill" style="width:${sk.monev.persentase}%;background:linear-gradient(90deg,var(--green-500),var(--teal-500))"></div></div>
                    </div>
                </div>
            `;

            document.getElementById('detailPanel').classList.add('open');
            map.closePopup();
        }

        function closeDetailPanel() {
            const panel = document.getElementById('detailPanel');
            if (panel) panel.classList.remove('open');
        }

        document.getElementById('selProvinsi').addEventListener('change', function () {
            selProv = this.value;
            selKab = '';
            populateKabupaten(selProv);
            applyFilters();
        });

        document.getElementById('selKabupaten').addEventListener('change', function () {
            selKab = this.value;
            applyFilters();
        });

        document.querySelectorAll('.prog-chip').forEach(chip => {
            chip.addEventListener('click', function () {
                const prog = this.dataset.prog;
                const isActive = this.classList.contains('active');

                if (isActive) {
                    if (activeProgs.size > 1) {
                        activeProgs.delete(prog);
                        this.classList.remove('active');
                    }
                } else {
                    activeProgs.add(prog);
                    this.classList.add('active');
                }

                applyFilters();
            });
        });

        document.getElementById('sekolahSearch').addEventListener('input', function () {
            searchQ = this.value.trim().toLowerCase();
            syncSearchInputs(this.value);
            applyFilters();
        });

        document.getElementById('headerSearchInput').addEventListener('input', function () {
            searchQ = this.value.trim().toLowerCase();
            syncSearchInputs(this.value);
            applyFilters();
        });

        const rightPanel = document.getElementById('rightPanel');
        const rightOpenBtn = document.getElementById('rightOpenBtn');

        function syncRightPanelState() {
            const collapsed = rightPanel.classList.contains('collapsed');
            rightOpenBtn.style.display = collapsed ? 'flex' : 'none';
        }

        document.getElementById('rightCollapseBtn').addEventListener('click', () => {
            rightPanel.classList.add('collapsed');
            syncRightPanelState();
        });

        rightOpenBtn.addEventListener('click', () => {
            rightPanel.classList.remove('collapsed');
            syncRightPanelState();
        });

        document.getElementById('detailCloseBtn').addEventListener('click', closeDetailPanel);

        function handleUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const targetSekolah = urlParams.get('sekolah');
            const targetId = urlParams.get('id');
            if (targetId) {
                const idNum = parseInt(targetId);
                openDetailPanel(idNum);
                const sk = allSekolah.find(s => s.id === idNum);
                if (sk) {
                    map.setView([sk.lat, sk.lng], 13, { animate: true });
                    setTimeout(() => {
                        if (activeMarkers[idNum]) activeMarkers[idNum].openPopup();
                    }, 300);
                }
            } else if (targetSekolah) {
                const found = allSekolah.find(s => s.nama.toLowerCase().includes(targetSekolah.toLowerCase()));
                if (found) {
                    openDetailPanel(found.id);
                    map.setView([found.lat, found.lng], 13, { animate: true });
                    setTimeout(() => {
                        if (activeMarkers[found.id]) activeMarkers[found.id].openPopup();
                    }, 300);
                }
            }
        }

        function loadSekolahData() {
            const list = document.getElementById('sekolahList');
            if (list) {
                list.innerHTML = '<div class="empty-state"><i class="bi bi-hourglass-split"></i> Memuat data sekolah dari database...</div>';
            }

            fetch('api/get_sekar_data.php')
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data && res.data.length > 0) {
                        allSekolah = res.data;
                        filteredSek = [...allSekolah];
                        populateProvinsi();
                        applyFilters();
                        handleUrlParams();
                    } else {
                        console.warn('Data sekolah kosong di database');
                        if (list) list.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-triangle"></i> Data sekolah belum tersedia di database</div>';
                    }
                })
                .catch(err => {
                    console.error('Gagal mengambil data sekolah:', err);
                    if (list) list.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-circle text-danger"></i> Gagal memuat data dari database</div>';
                });
        }

        syncRightPanelState();
        loadSekolahData();
    </script>
</body>
</html>
