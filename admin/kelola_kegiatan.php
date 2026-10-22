<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/admin_check.php';

$activePage = 'kelola_kegiatan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kegiatan - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/toast.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/css/admin.css') ?>">

    <style>
        /* ---------- Badge status ---------- */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-status.selesai            { background: #e8f0fe; color: #2563eb; }
        .badge-status.berlangsung        { background: #fef3e2; color: #d97706; }
        .badge-status.belum_berlangsung  { background: #f1e9fc; color: #7c3aed; }

        /* Badge status verifikasi -- cuma ditampilkan buat pending/rejected,
           approved gak ditandai lagi (dianggap kondisi normal). */
        .badge-verif {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
            margin-top: 4px;
        }
        .badge-verif.pending  { background: #fef3e2; color: #d97706; margin-top: 4px; }
        .badge-verif.rejected { background: #fee2e2; color: #b91c1c; margin-top: 4px; }
        .badge-verif.terlambat { background: #fde2e2; color: #991b1b; margin-top: 4px; margin-left: 4px; }
        .badge-verif.revisi {
            background: #ede9fe; color: #6d28d9; margin-top: 4px;
            cursor: pointer; border: none;
        }
        .badge-verif.revisi:hover { background: #ddd6fe; }

        /* Kotak highlight catatan verifikator -- muncul di dalam modal (preview & edit),
           BUKAN di tooltip/hover, biar gak perlu dicari-cari lagi. */
        .catatan-verif-alert {
            background: #fef9e7;
            border: 1px solid #f5deb3;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: .88rem;
            color: #7c5800;
            margin-bottom: 16px;
        }
        .catatan-verif-alert strong { display: block; margin-bottom: 2px; }

        /* ---------- Status preview (read-only, dihitung dari tanggal) ---------- */
        .badge-status-preview {
            display: inline-flex;
            align-items: center;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 600;
        }
        .badge-status-preview.kosong             { background: #f3f4f6; color: var(--admin-text-muted); font-weight: 500; }
        .badge-status-preview.belum_berlangsung  { background: #f1e9fc; color: #7c3aed; }
        .badge-status-preview.berlangsung        { background: #fef3e2; color: #d97706; }
        .badge-status-preview.selesai            { background: #e8f0fe; color: #2563eb; }

        .judul-lokasi-cell .judul { font-weight: 600; }
        .judul-lokasi-cell .lokasi { color: var(--admin-text-muted); font-size: .82rem; }

        /* ---------- Highlight bagian yang perlu diperhatikan (deep-link dari dashboard) ---------- */
        .field-highlight {
            outline: 2px solid #f59e0b;
            outline-offset: 4px;
            border-radius: 10px;
            animation: fieldHighlightPulse 1.4s ease-in-out 3;
        }
        @keyframes fieldHighlightPulse {
            0%, 100% { outline-color: #f59e0b; background-color: rgba(245, 158, 11, .06); }
            50%      { outline-color: #fde68a; background-color: rgba(245, 158, 11, .14); }
        }

        /* ---------- Panel diff pengajuan revisi ---------- */
        .revisi-diff-item {
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
        }
        .revisi-diff-label { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--admin-text-muted); margin-bottom: 6px; }
        .revisi-diff-row { display: flex; align-items: flex-start; gap: 10px; font-size: .88rem; }
        .revisi-diff-lama { color: #b91c1c; text-decoration: line-through; opacity: .8; flex: 1; }
        .revisi-diff-baru { color: #16a34a; font-weight: 600; flex: 1; }
        .revisi-diff-arrow { color: var(--admin-text-muted); flex-shrink: 0; }
        .revisi-foto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 6px; }
        .revisi-foto-item img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border); display: block; }
        .revisi-foto-grid.hapus .revisi-foto-item img { border-color: #fecaca; opacity: .6; }
        .revisi-foto-caption { font-size: .7rem; color: var(--admin-text); margin-top: 4px; line-height: 1.3; word-break: break-word; }
        .revisi-foto-caption.kosong { color: var(--admin-text-muted, #999); font-style: italic; }

        /* ---------- Chip picker (klasifikasi/tags) ---------- */
        .tag-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }
        .tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--admin-green);
            background: var(--admin-green-bg);
            color: var(--admin-green);
            border-radius: 999px;
            padding: 5px 12px;
            font-size: .85rem;
            font-weight: 600;
            cursor: default;
        }
        .tag-chip button {
            background: none;
            border: none;
            color: inherit;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }
        .tag-input-wrap { position: relative; }
        .tag-input-wrap input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            font-size: .88rem;
        }

        .tag-suggest-box {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,.12);
            z-index: 70;
            max-height: 220px;
            overflow-y: auto;
            padding: 6px;
        }
        .tag-suggest-box.open { display: block; }
        .tag-suggest-item {
            display: block;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 7px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: .85rem;
        }
        .tag-suggest-item:hover { background: #f3f4f6; }
        .tag-suggest-item.tag-suggest-baru {
            color: var(--admin-green);
            font-weight: 600;
            border-top: 1px solid var(--admin-border);
            margin-top: 4px;
            padding-top: 9px;
        }

        /* ---------- Chip toggle (Departemen -- opsi tetap, klik buat pilih/batal) ---------- */
        .dept-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .dept-chip-btn {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid var(--admin-border);
            background: #fff;
            color: var(--admin-text-muted);
            border-radius: 999px;
            padding: 5px 14px;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .12s ease;
        }
        .dept-chip-btn.selected {
            border-color: #0d9488;
            background: #ccfbf1;
            color: #0d9488;
        }

        .preview-dept-tag {
            background: #ccfbf1;
            color: #0d9488;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: .82rem;
            font-weight: 600;
        }


        /* ---------- Foto grid ---------- */
        .foto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        .foto-thumb {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--admin-border);
            cursor: pointer;
        }
        .foto-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .foto-thumb .foto-hapus {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(0,0,0,.55);
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .8rem;
            line-height: 1;
            z-index: 2;
        }
        .foto-thumb .foto-caption-badge {
            position: absolute;
            left: 4px;
            right: 24px;
            bottom: 4px;
            background: rgba(0,0,0,.55);
            color: #fff;
            font-size: .65rem;
            padding: 2px 6px;
            border-radius: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .foto-thumb .foto-caption-hint {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(0,0,0,.4);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
        }
        /* ---------- Foto expanded (accordion, cuma 1 yang bisa kebuka sekaligus) ---------- */
        .foto-thumb-expanded {
            grid-column: 1 / -1;
            aspect-ratio: unset;
            cursor: default;
            border: 1px solid var(--admin-green);
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .foto-expanded-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .foto-btn-minimize {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--admin-green);
            color: #fff;
            border: none;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .72rem;
            font-weight: 600;
            cursor: pointer;
        }
        .foto-btn-hapus-expanded {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: none;
            border: 1px solid #dc3545;
            color: #dc3545;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .72rem;
            font-weight: 600;
            cursor: pointer;
        }
        .foto-expanded-img {
            width: 100%;
            max-height: 220px;
            object-fit: contain;
            background: #111;
            border-radius: 8px;
            display: block;
        }
        .foto-expanded-caption-label {
            font-size: .74rem;
            font-weight: 600;
            margin: 2px 0 0;
            color: var(--admin-text);
        }
        .foto-expanded-caption-input {
            font-size: .8rem;
        }
        .foto-empty-note {
            color: var(--admin-text-muted);
            font-size: .85rem;
        }
        .foto-dropzone {
            border: 1.5px dashed var(--admin-border);
            border-radius: 10px;
            padding: 22px 16px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 12px;
            transition: border-color .15s ease, background .15s ease;
        }
        .foto-dropzone:hover,
        .foto-dropzone.dragover {
            border-color: var(--admin-green);
            background: var(--admin-green-bg);
        }
        .foto-dropzone .btn-upload-foto {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 8px 18px;
            font-size: .88rem;
            font-weight: 600;
            color: var(--admin-text);
            pointer-events: none;
        }
        .foto-dropzone p {
            margin: 10px 0 0;
            font-size: .82rem;
            color: var(--admin-text-muted);
        }
        .foto-dropzone p.foto-dropzone-hint {
            font-size: .75rem;
            margin-top: 2px;
        }
        .foto-lama-label {
            color: var(--admin-text-muted);
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: 6px;
        }

        /* ---------- Custom Lokasi Picker ---------- */
        .lokasi-picker { position: relative; }

        .lokasi-picker-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 9px 14px;
            font-size: .95rem;
            color: var(--admin-text);
            cursor: pointer;
        }
        .lokasi-picker-btn:hover { border-color: var(--admin-green); }
        .lokasi-picker.invalid .lokasi-picker-btn { border-color: #dc3545; }

        .lokasi-picker-panel {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,.12);
            z-index: 60;
            padding: 12px;
        }
        .lokasi-picker.open .lokasi-picker-panel { display: block; }

        .lokasi-picker-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        .lokasi-picker-filters select,
        .lokasi-picker-filters input {
            padding: 8px 10px;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            font-size: .87rem;
        }
        .lokasi-picker-filters select { flex: 0 0 40%; }
        .lokasi-picker-filters input { flex: 1; }

        .lokasi-picker-list {
            max-height: 220px;
            overflow-y: auto;
            border-top: 1px solid var(--admin-border);
        }
        .lokasi-picker-item {
            display: block;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 9px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: .88rem;
        }
        .lokasi-picker-item:hover { background: #f3f4f6; }
        .lokasi-picker-item .nama { font-weight: 600; }
        .lokasi-picker-item .wilayah { color: var(--admin-text-muted); font-size: .8rem; }

        .lokasi-picker-empty {
            padding: 14px 8px;
            color: var(--admin-text-muted);
            font-size: .85rem;
            text-align: center;
        }

        /* ---------- Baris tabel bisa diklik utk preview ---------- */
        #tabelKegiatan tr.row-klik { cursor: pointer; }
        #tabelKegiatan tr.row-klik:hover { background: #f8f9fb; }
        #tabelKegiatan tr.row-klik td.td-aksi { cursor: default; }

        /* ---------- Sort by header click ---------- */
        .admin-table th.th-sort {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        .admin-table th.th-sort:hover { background: #f1f3f5; }
        .th-sort .sort-ind {
            display: inline-block;
            margin-left: 4px;
            font-size: .72rem;
            color: var(--admin-text-muted);
            opacity: .45;
        }
        .th-sort.sort-active .sort-ind { opacity: 1; color: var(--admin-green); }

        /* ---------- Modal Preview (read-only) ---------- */
        .preview-label {
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--admin-text-muted);
            margin-bottom: 4px;
        }
        .preview-value {
            font-size: .95rem;
            margin-bottom: 16px;
        }
        .preview-value.kosong { color: var(--admin-text-muted); font-style: italic; }
        .preview-tags { display: flex; flex-wrap: wrap; gap: 8px; }
        .preview-tag {
            background: var(--admin-green-bg);
            color: var(--admin-green);
            border-radius: 999px;
            padding: 5px 12px;
            font-size: .82rem;
            font-weight: 600;
        }
        .preview-foto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;
        }
        .preview-foto-grid a {
            display: block;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--admin-border);
        }
        .preview-foto-grid img { width: 100%; height: 100%; object-fit: cover; display: block; }
    </style>
</head>
<body class="admin-body">
    <?php require_once __DIR__ . '/../includes/navbar_admin.php'; ?>

        <h1 class="page-title">Kelola Kegiatan</h1>
        <p class="page-subtitle">Menu kumpulan data serta pengelolaan data kegiatan</p>

        <div class="admin-toolbar">
            <button type="button" class="btn-tambah" onclick="openCreateModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Tambah Kegiatan
            </button>

            <div class="toolbar-right">
                <select class="filter-select" id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="belum_berlangsung">Belum Berlangsung</option>
                    <option value="berlangsung">Berlangsung</option>
                    <option value="selesai">Selesai</option>
                </select>
                <div class="search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="inputCari" placeholder="Cari judul/lokasi">
                </div>
            </div>
        </div>

        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="th-sort" data-sort="kode_kegiatan">Kode<span class="sort-ind"></span></th>
                        <th class="th-sort" data-sort="judul">Judul & Lokasi<span class="sort-ind"></span></th>
                        <th class="th-sort" data-sort="tanggal_mulai">Tanggal<span class="sort-ind"></span></th>
                        <th class="th-sort" data-sort="status">Status<span class="sort-ind"></span></th>
                        <th class="th-sort" data-sort="jumlah_peserta">Peserta<span class="sort-ind"></span></th>
                        <th class="th-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelKegiatan">
                    <tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-row" id="paginationRow"></div>

    <!-- ==================== Modal Tambah/Edit Kegiatan ==================== -->
    <div class="modal fade" id="modalKegiatan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="modalKegiatanTitle">Tambah Kegiatan</h5>
                        <small class="text-muted" id="kodeKegiatanInfo">Kode akan digenerate otomatis setelah disimpan</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formKegiatan" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="kegiatanId" name="id">

                        <div class="catatan-verif-alert" id="editCatatanVerifikator" style="display:none;">
                            <strong>Catatan Verifikator</strong>
                            <span id="editCatatanVerifikatorTeks"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Judul Kegiatan</label>
                            <input type="text" class="form-control" name="judul" id="judul"
                                   placeholder="Contoh: Seminar Penyatuan Visi-Misi Pertanian Bogor" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div>
                                    <span class="badge-status-preview kosong" id="statusPreviewBadge">Isi tanggal dulu</span>
                                </div>
                                <div class="form-text">Belum Berlangsung/Berlangsung otomatis ikut tanggal. Selesai ditandai manual lewat tombol di tabel.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Skala Kegiatan</label>
                                <select class="form-select" name="skala_kegiatan" id="skalaKegiatan" required>
                                    <option value="">-- Pilih Skala --</option>
                                    <option value="Lokal">Lokal</option>
                                    <option value="Kota/Kabupaten">Kota/Kabupaten</option>
                                    <option value="Provinsi">Provinsi</option>
                                    <option value="Nasional">Nasional</option>
                                    <option value="Internasional">Internasional</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lokasi Kegiatan</label>
                            <div class="lokasi-picker" id="lokasiPicker">
                                <button type="button" class="lokasi-picker-btn" id="lokasiPickerBtn">
                                    <span id="lokasiPickerLabel" class="text-muted">-- Pilih Lokasi --</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                                <input type="hidden" name="location_id" id="locationId">

                                <div class="lokasi-picker-panel" id="lokasiPickerPanel">
                                    <div class="lokasi-picker-filters">
                                        <select id="lokasiFilterProvinsi">
                                            <option value="">Semua Provinsi</option>
                                        </select>
                                        <input type="text" id="lokasiFilterSearch" placeholder="Cari nama lokasi...">
                                    </div>
                                    <div class="lokasi-picker-list" id="lokasiPickerList">
                                        <div class="lokasi-picker-empty">Ketik atau pilih provinsi untuk mencari lokasi.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" name="tanggal_mulai" id="tanggalMulai" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" name="tanggal_selesai" id="tanggalSelesai" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Penyelenggara <span class="text-muted small">(opsional)</span></label>
                                <input type="text" class="form-control" name="penyelenggara" id="penyelenggara"
                                       placeholder="Contoh: SEAMEO BIOTROP, Institut Pertanian Bogor">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Peserta <span class="text-muted small">(opsional)</span></label>
                                <input type="number" class="form-control" name="jumlah_peserta" id="jumlahPeserta" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Kegiatan <span class="text-muted small">(opsional)</span></label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Klasifikasi / Tag</label>
                            <div class="tag-chips" id="tagChips"></div>
                            <div class="tag-input-wrap">
                                <input type="text" id="inputTagBaru" placeholder="Ketik untuk cari atau tambah tag baru..." autocomplete="off">
                                <div class="tag-suggest-box" id="tagSuggestBox"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Departemen</label>
                            <div class="dept-chips" id="deptChips"></div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">Hasil Kegiatan</h6>

                        <div class="mb-3">
                            <label class="form-label">Rencana Output <span class="text-muted small">(opsional)</span></label>
                            <textarea class="form-control" name="rencana_output" id="rencanaOutput" rows="2"></textarea>
                        </div>

                        <div class="mb-3" id="deskripsiHasilWrap" style="display:none;">
                            <label class="form-label">Deskripsi Hasil</label>
                            <textarea class="form-control" name="deskripsi_hasil" id="deskripsiHasil" rows="2"></textarea>
                            <div class="form-text">Muncul otomatis setelah Tanggal Selesai terlewati.</div>
                        </div>

                        <div class="mb-2" id="fotoSectionWrap">
                            <label class="form-label">Dokumentasi Foto <span class="text-muted small">(maks 5 foto, JPG/PNG). Klik foto buat kasih caption.</span></label>

                            <div class="foto-dropzone" id="fotoDropzone">
                                <button type="button" class="btn-upload-foto">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    Upload
                                </button>
                                <p>Pilih foto atau seret &amp; lepas di sini.</p>
                                <p class="foto-dropzone-hint">JPG dan PNG. Maks 5 foto.</p>
                                <input type="file" name="foto_baru[]" id="fotoBaruInput" accept="image/jpeg,image/png" multiple hidden>
                            </div>

                            <div id="fotoLamaWrapper">
                                <p class="foto-lama-label" id="fotoLamaLabel" style="display:none;">Foto tersimpan</p>
                                <div class="foto-grid" id="fotoLamaGrid"></div>
                            </div>

                            <div class="foto-grid" id="fotoBaruGrid"></div>
                            <p class="foto-empty-note" id="fotoEmptyNote">Belum ada foto dipilih.</p>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-simpan" id="btnSimpanKegiatan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Detail Pengajuan Revisi (read-only + batal) ==================== -->
    <div class="modal fade" id="modalDetailRevisi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Pengajuan Revisi</h5>
                        <small class="text-muted" id="revisiKodeKegiatan"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Menunggu persetujuan Admin/Superadmin. Perubahan di bawah ini <strong>belum</strong> diterapkan ke data yang tayang.</p>
                    <div id="revisiDiffList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger" id="btnBatalRevisi">Batalkan Pengajuan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Preview Kegiatan (read-only) ==================== -->
    <div class="modal fade" id="modalPreviewKegiatan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="previewJudul">Detail Kegiatan</h5>
                        <small class="text-muted" id="previewKode"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="catatan-verif-alert" id="previewCatatanVerifikator" style="display:none;">
                        <strong>Catatan Verifikator</strong>
                        <span id="previewCatatanVerifikatorTeks"></span>
                    </div>

                    <div class="mb-3" id="previewStatusWrap">
                        <span class="badge-status-preview" id="previewStatusBadge"></span>
                        <span class="text-muted small ms-2" id="previewSkala"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="preview-label">Lokasi</div>
                            <div class="preview-value" id="previewLokasi">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Tanggal Pelaksanaan</div>
                            <div class="preview-value" id="previewTanggal">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Penyelenggara</div>
                            <div class="preview-value" id="previewPenyelenggara">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Jumlah Peserta</div>
                            <div class="preview-value" id="previewPeserta">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Diajukan oleh</div>
                            <div class="preview-value" id="previewDiajukanOleh">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Diverifikasi oleh</div>
                            <div class="preview-value" id="previewDiverifikasiOleh">-</div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Deskripsi</div>
                            <div class="preview-value" id="previewDeskripsi">-</div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Klasifikasi / Tag</div>
                            <div class="preview-value">
                                <div class="preview-tags" id="previewTags">-</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Departemen</div>
                            <div class="preview-value">
                                <div class="preview-tags" id="previewDept">-</div>
                            </div>
                        </div>
                        <div class="col-12"><hr class="my-2"></div>
                        <div class="col-12">
                            <div class="preview-label">Rencana Output</div>
                            <div class="preview-value" id="previewRencanaOutput">-</div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Deskripsi Hasil</div>
                            <div class="preview-value" id="previewDeskripsiHasil">-</div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Dokumentasi Foto</div>
                            <div class="preview-foto-grid" id="previewFotoGrid"></div>
                            <div class="text-muted small" id="previewFotoKosong">Belum ada foto.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-simpan" id="btnEditDariPreview">Edit Kegiatan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Konfirmasi Hapus ==================== -->
    <div class="modal fade" id="modalKonfirmasiHapusKegiatan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center pt-4 pb-2">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;border-radius:50%;background:#fee2e2;color:#b91c1c;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                            <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                        </svg>
                    </div>
                    <h5 class="mb-2">Hapus Kegiatan?</h5>
                    <p class="text-muted mb-0">
                        Yakin mau hapus kegiatan <strong id="judulKegiatanDihapus"></strong>?
                        Semua foto dokumentasi & tag terkait juga akan ikut terhapus dan tidak bisa dikembalikan.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger px-4" id="btnKonfirmasiHapusKegiatan">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal: Tandai Selesai ==================== -->
    <div class="modal fade" id="modalSelesaiKegiatan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Tandai Kegiatan Selesai</h5>
                        <p class="text-muted small mb-0" id="judulKegiatanDiselesaikan"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Status kegiatan ini bakal jadi <strong>Selesai</strong> dan gak balik otomatis lagi
                        walau tanggalnya diedit nanti.
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi Hasil <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsiHasilSelesai" rows="3" required></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Dokumentasi Foto <span class="text-muted small">(opsional, maks 5 foto, JPG/PNG). Klik foto buat kasih caption.</span></label>

                        <div class="foto-dropzone" id="fotoDropzoneSelesai">
                            <button type="button" class="btn-upload-foto">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Upload
                            </button>
                            <p>Pilih foto atau seret &amp; lepas di sini.</p>
                            <p class="foto-dropzone-hint">JPG dan PNG. Maks 5 foto.</p>
                            <input type="file" id="fotoBaruInputSelesai" accept="image/jpeg,image/png" multiple hidden>
                        </div>

                        <div id="fotoLamaWrapperSelesai">
                            <p class="foto-lama-label" id="fotoLamaLabelSelesai" style="display:none;">Foto tersimpan</p>
                            <div class="foto-grid" id="fotoLamaGridSelesai"></div>
                        </div>

                        <div class="foto-grid" id="fotoBaruGridSelesai"></div>
                        <p class="foto-empty-note" id="fotoEmptyNoteSelesai">Belum ada foto dipilih.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnSubmitSelesaiKegiatan">Selesai</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/toast.js"></script>

    <script>
        const API_KEGIATAN = '<?= BASE_URL ?>/api/crud_kegiatan.php';
        const API_LOKASI   = '<?= BASE_URL ?>/api/crud_lokasi.php';
        const API_REVISI   = '<?= BASE_URL ?>/api/crud_kegiatan_revisi.php';
        const CURRENT_ROLE = '<?= $_SESSION['role'] ?>';

        let mode = 'create'; // 'create' | 'update' | 'ajukan_revisi'
        // kegiatan_id -> id kegiatan_revisi yang masih 'pending' -- dipakai buat
        // nampilin badge "Revisi Menunggu" & nge-nonaktifin tombol Edit di baris terkait.
        let revisiPendingMap = {};
        const modalKegiatanEl = document.getElementById('modalKegiatan');
        const modalKegiatan   = new bootstrap.Modal(modalKegiatanEl);

        // ==================== Custom Lokasi Picker (search + filter provinsi) ====================
        const LIMIT_HASIL_LOKASI = 20;

        const lokasiPicker      = document.getElementById('lokasiPicker');
        const lokasiPickerBtn   = document.getElementById('lokasiPickerBtn');
        const lokasiPickerLabel = document.getElementById('lokasiPickerLabel');
        const lokasiPickerList  = document.getElementById('lokasiPickerList');
        const lokasiFilterProvinsi = document.getElementById('lokasiFilterProvinsi');
        const lokasiFilterSearch   = document.getElementById('lokasiFilterSearch');
        const locationIdInput      = document.getElementById('locationId');

        let provinsiListLoaded = false;

        async function muatProvinsiFilter() {
            if (provinsiListLoaded) return;
            const res  = await fetch(`${API_LOKASI}?action=provinsi_list`);
            const json = await res.json();
            if (json.success) {
                json.data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p;
                    opt.textContent = p;
                    lokasiFilterProvinsi.appendChild(opt);
                });
            }
            provinsiListLoaded = true;
        }

        async function cariLokasiUntukPicker() {
            const q        = lokasiFilterSearch.value.trim();
            const provinsi = lokasiFilterProvinsi.value;

            const url = new URL(API_LOKASI, window.location.origin);
            url.searchParams.set('action', 'list');
            url.searchParams.set('limit', LIMIT_HASIL_LOKASI);
            if (q) url.searchParams.set('q', q);
            if (provinsi) url.searchParams.set('provinsi', provinsi);

            const res  = await fetch(url);
            const json = await res.json();
            renderLokasiPickerList(json.success ? json.data : []);
        }

        function renderLokasiPickerList(data) {
            if (data.length === 0) {
                lokasiPickerList.innerHTML = '<div class="lokasi-picker-empty">Tidak ada lokasi yang cocok.</div>';
                return;
            }

            lokasiPickerList.innerHTML = data.map(l => `
                <button type="button" class="lokasi-picker-item" onclick="pilihLokasi(${l.id}, '${l.nama_lokasi.replace(/'/g, "\\'")}', '${l.kabupaten_kota.replace(/'/g, "\\'")}')">
                    <div class="nama">${l.nama_lokasi}</div>
                    <div class="wilayah">${l.kabupaten_kota}, ${l.provinsi}</div>
                </button>
            `).join('');

            if (data.length === LIMIT_HASIL_LOKASI) {
                lokasiPickerList.innerHTML += `<div class="lokasi-picker-empty">Menampilkan ${LIMIT_HASIL_LOKASI} hasil teratas — persempit pencarian kalau lokasinya belum kelihatan.</div>`;
            }
        }

        function pilihLokasi(id, nama, kabupaten) {
            locationIdInput.value = id;
            lokasiPickerLabel.textContent = `${nama} — ${kabupaten}`;
            lokasiPickerLabel.classList.remove('text-muted');
            lokasiPicker.classList.remove('open');
            lokasiPicker.classList.remove('invalid');
        }

        function resetLokasiPicker() {
            locationIdInput.value = '';
            lokasiPickerLabel.textContent = '-- Pilih Lokasi --';
            lokasiPickerLabel.classList.add('text-muted');
            lokasiFilterSearch.value = '';
            lokasiFilterProvinsi.value = '';
            lokasiPickerList.innerHTML = '<div class="lokasi-picker-empty">Ketik atau pilih provinsi untuk mencari lokasi.</div>';
            lokasiPicker.classList.remove('invalid');
        }

        function setLokasiTerpilih(id, nama, kabupaten) {
            locationIdInput.value = id;
            lokasiPickerLabel.textContent = `${nama} — ${kabupaten}`;
            lokasiPickerLabel.classList.remove('text-muted');
        }

        lokasiPickerBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const sedangTerbuka = lokasiPicker.classList.contains('open');
            if (sedangTerbuka) {
                lokasiPicker.classList.remove('open');
                return;
            }
            lokasiPicker.classList.add('open');
            await muatProvinsiFilter();
            cariLokasiUntukPicker();
        });

        document.addEventListener('click', (e) => {
            if (!lokasiPicker.contains(e.target)) {
                lokasiPicker.classList.remove('open');
            }
        });

        let lokasiSearchDebounce;
        lokasiFilterSearch.addEventListener('input', () => {
            clearTimeout(lokasiSearchDebounce);
            lokasiSearchDebounce = setTimeout(cariLokasiUntukPicker, 300);
        });

        lokasiFilterProvinsi.addEventListener('change', cariLokasiUntukPicker);

        // ==================== Status kegiatan (preview otomatis, bukan input) ====================
        const LABEL_STATUS = {
            belum_berlangsung: 'Belum Berlangsung',
            berlangsung: 'Berlangsung',
            selesai: 'Selesai'
        };

        // Status asli kegiatan yang sedang dibuka di modal edit (null kalau mode tambah).
        // Dipakai supaya badge preview gak salah nurunin status 'selesai' balik jadi
        // 'berlangsung' cuma gara-gara tanggalnya ditampilin ulang di form.
        let statusKegiatanAktif = null;

        function hitungStatusOtomatis(mulai, selesai) {
            if (!mulai || !selesai) return null;
            const hariIni = new Date().toISOString().slice(0, 10);
            if (hariIni < mulai) return 'belum_berlangsung';
            return 'berlangsung';
        }

        function perbaruiStatusPreview() {
            const mulai   = document.getElementById('tanggalMulai').value;
            const selesai = document.getElementById('tanggalSelesai').value;
            const badge   = document.getElementById('statusPreviewBadge');

            // Kalau kegiatan ini sudah ditandai Selesai manual, badge selalu nampilin
            // itu -- gak ikut dihitung ulang dari tanggal (konsisten sama backend).
            const status = statusKegiatanAktif === 'selesai'
                ? 'selesai'
                : hitungStatusOtomatis(mulai, selesai);

            if (!status) {
                badge.className = 'badge-status-preview kosong';
                badge.textContent = 'Isi tanggal dulu';
                return;
            }
            badge.className = `badge-status-preview ${status}`;
            badge.textContent = LABEL_STATUS[status];
        }

        // Kolom "Deskripsi Hasil" cuma muncul kalau Tanggal Selesai yang diisi sudah lewat hari ini.
        function perbaruiVisibilitasHasil() {
            const selesai = document.getElementById('tanggalSelesai').value;
            const hariIni = new Date().toISOString().slice(0, 10);
            const sudahLewat = !!selesai && selesai < hariIni;
            document.getElementById('deskripsiHasilWrap').style.display = sudahLewat ? 'block' : 'none';
        }

        document.getElementById('tanggalMulai').addEventListener('input', perbaruiStatusPreview);
        document.getElementById('tanggalSelesai').addEventListener('input', () => {
            perbaruiStatusPreview();
            perbaruiVisibilitasHasil();
        });

        // ==================== Tag / Klasifikasi (chip picker) ====================
        let tagsTersedia   = [];      // [{id, nama_tag}] dari server
        let tagIdTerpilih  = new Set();
        let tagBaruTerpilih = new Set();

        async function muatTags() {
            const res  = await fetch(`${API_KEGIATAN}?action=tags`);
            const json = await res.json();
            tagsTersedia = json.success ? json.data : [];
            renderTagChips();
        }

        function renderTagChips() {
            const container = document.getElementById('tagChips');
            let html = '';

            tagsTersedia.forEach(t => {
                if (!tagIdTerpilih.has(t.id)) return;
                html += `
                    <span class="tag-chip">
                        ${t.nama_tag}
                        <button type="button" onclick="toggleTag(${t.id})">&times;</button>
                    </span>`;
            });

            tagBaruTerpilih.forEach(nama => {
                html += `
                    <span class="tag-chip">
                        ${nama}
                        <button type="button" onclick="hapusTagBaru('${nama.replace(/'/g, "\\'")}')">&times;</button>
                    </span>`;
            });

            container.innerHTML = html || '<span class="text-muted small">Belum ada tag dipilih.</span>';
        }

        function toggleTag(id) {
            if (tagIdTerpilih.has(id)) {
                tagIdTerpilih.delete(id);
            } else {
                tagIdTerpilih.add(id);
            }
            renderTagChips();
        }

        function hapusTagBaru(nama) {
            tagBaruTerpilih.delete(nama);
            renderTagChips();
        }

        // ==================== Departemen (chip toggle, opsi tetap) ====================
        let deptTersedia  = [];      // ['SITD', 'HCID', ...] dari server
        let deptTerpilih  = new Set();

        async function muatDepartemen() {
            const res  = await fetch(`${API_KEGIATAN}?action=departemen`);
            const json = await res.json();
            deptTersedia = json.success ? json.data : [];
            renderDeptChips();
        }

        function renderDeptChips() {
            const container = document.getElementById('deptChips');
            container.innerHTML = deptTersedia.map(kode => `
                <button type="button" class="dept-chip-btn ${deptTerpilih.has(kode) ? 'selected' : ''}"
                        onclick="toggleDept('${kode}')">${kode}</button>
            `).join('');
        }

        function toggleDept(kode) {
            if (deptTerpilih.has(kode)) {
                deptTerpilih.delete(kode);
            } else {
                deptTerpilih.add(kode);
            }
            renderDeptChips();
        }

        // ---------- Dropdown saran tag (mirip, tidak case sensitive) ----------
        const inputTagBaru  = document.getElementById('inputTagBaru');
        const tagSuggestBox = document.getElementById('tagSuggestBox');

        function tutupSaranTag() {
            tagSuggestBox.classList.remove('open');
            tagSuggestBox.innerHTML = '';
        }

        function renderSaranTag(query) {
            const q = query.trim();
            if (q === '') { tutupSaranTag(); return; }

            const qLower = q.toLowerCase();
            const cocok = tagsTersedia.filter(t =>
                t.nama_tag.toLowerCase().includes(qLower) && !tagIdTerpilih.has(t.id)
            );
            const adaCocokPersis = tagsTersedia.some(t => t.nama_tag.toLowerCase() === qLower);

            let html = cocok.map(t => `
                <button type="button" class="tag-suggest-item" onclick="pilihSaranTag(${t.id})">${t.nama_tag}</button>
            `).join('');

            // Kalau belum ada tag dengan nama persis sama, tawarkan opsi bikin tag baru.
            if (!adaCocokPersis) {
                const qEscaped = q.replace(/'/g, "\\'");
                html += `<button type="button" class="tag-suggest-item tag-suggest-baru" onclick="tambahTagBaru('${qEscaped}')">+ Tambah tag baru: "${q}"</button>`;
            }

            if (html === '') { tutupSaranTag(); return; }

            tagSuggestBox.innerHTML = html;
            tagSuggestBox.classList.add('open');
        }

        function pilihSaranTag(id) {
            tagIdTerpilih.add(id);
            inputTagBaru.value = '';
            tutupSaranTag();
            renderTagChips();
        }

        function tambahTagBaru(nama) {
            nama = nama.trim();
            if (nama === '') return;

            // Kalau nama cocok persis (not case sensitive) dengan tag yang sudah ada, pilih tag itu saja.
            const cocokPersis = tagsTersedia.find(t => t.nama_tag.toLowerCase() === nama.toLowerCase());
            if (cocokPersis) {
                tagIdTerpilih.add(cocokPersis.id);
            } else {
                tagBaruTerpilih.add(nama);
            }

            inputTagBaru.value = '';
            tutupSaranTag();
            renderTagChips();
        }

        inputTagBaru.addEventListener('input', (e) => renderSaranTag(e.target.value));
        inputTagBaru.addEventListener('focus', (e) => renderSaranTag(e.target.value));

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.tag-input-wrap')) tutupSaranTag();
        });

        inputTagBaru.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                tambahTagBaru(inputTagBaru.value);
            }
        });


        // ==================== Foto dokumentasi ====================
        const MAX_FOTO = 5;
        // Foto besar sekarang DIKOMPRES OTOMATIS di server (lihat kompresGambar() di
        // includes/kegiatan_helper.php), bukan ditolak lagi -- jadi batas di sisi client ini
        // BUKAN target ukuran akhir, cuma pagar buat file mentah yang kelewat ekstrem (misal
        // salah pilih file gede banget), samain sama MAX_UKURAN_UPLOAD_MENTAH_MB di kegiatan_helper.php.
        const MAX_UKURAN_MENTAH_MB = 15;
        let fotoLama     = [];   // [{id, path_foto, caption}] dari server (mode edit)
        let hapusFotoIds = [];   // id foto lama yang ditandai dihapus
        let fotoBaruFiles = [];  // File[] baru yang dipilih
        let fotoBaruCaptions = []; // caption[] baru, index-nya selaras sama fotoBaruFiles
        let fotoTerbukaKey = null; // { jenis: 'lama'|'baru', key: id atau idx } -- cuma 1 foto yang bisa kebuka sekaligus

        // Escape teks buat disisipkan ke dalam atribut/isi HTML lewat template string.
        function escAttr(teks) {
            return String(teks ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        /** Template 1 kartu foto -- collapsed (thumbnail kecil, klik buat buka) atau
         *  expanded (accordion: gambar besar + field caption + tombol Tutup/Hapus yang jelas beda). */
        function templateFotoItem({ terbuka, src, caption, onToggle, onHapus }) {
            if (terbuka) {
                return `
                    <div class="foto-thumb foto-thumb-expanded">
                        <div class="foto-expanded-head">
                            <button type="button" class="foto-btn-minimize" onclick="${onToggle}">
                                <i class="bi bi-chevron-up"></i> Tutup foto
                            </button>
                            <button type="button" class="foto-btn-hapus-expanded" onclick="${onHapus}">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </div>
                        <img src="${src}" alt="Foto" class="foto-expanded-img">
                        <p class="foto-expanded-caption-label">Caption <span class="text-muted small fw-normal">(opsional)</span></p>
                        <textarea class="form-control foto-expanded-caption-input" rows="2" maxlength="255"
                                  placeholder="Tulis caption buat foto ini...">${escAttr(caption)}</textarea>
                    </div>
                `;
            }
            return `
                <div class="foto-thumb" onclick="${onToggle}" title="Klik buat lihat & kasih caption">
                    <img src="${src}" alt="Foto">
                    <button type="button" class="foto-hapus" onclick="event.stopPropagation(); ${onHapus}" title="Hapus foto">&times;</button>
                    ${caption ? `<span class="foto-caption-badge">${escAttr(caption)}</span>` : ''}
                </div>
            `;
        }

        function toggleFotoTerbuka(jenis, key) {
            fotoTerbukaKey = (fotoTerbukaKey && fotoTerbukaKey.jenis === jenis && fotoTerbukaKey.key === key)
                ? null
                : { jenis, key };
            renderFotoLama();
            renderFotoBaru();
        }

        function renderFotoLama() {
            const grid  = document.getElementById('fotoLamaGrid');
            const label = document.getElementById('fotoLamaLabel');
            const sisa  = fotoLama.filter(f => !hapusFotoIds.includes(f.id));

            grid.innerHTML = sisa.map(f => templateFotoItem({
                terbuka: !!(fotoTerbukaKey && fotoTerbukaKey.jenis === 'lama' && fotoTerbukaKey.key === f.id),
                src: `<?= BASE_URL ?>/${f.path_foto}`,
                caption: f.caption || '',
                onToggle: `toggleFotoTerbuka('lama', ${f.id})`,
                onHapus: `hapusFotoLama(${f.id})`,
            })).join('');

            grid.querySelectorAll('.foto-thumb-expanded textarea').forEach(el => {
                el.oninput = (e) => { const f = fotoLama.find(x => x.id === fotoTerbukaKey.key); if (f) f.caption = e.target.value; };
            });

            label.style.display = sisa.length > 0 ? 'block' : 'none';

            perbaruiCatatanFoto();
        }

        function hapusFotoLama(id) {
            hapusFotoIds.push(id);
            if (fotoTerbukaKey && fotoTerbukaKey.jenis === 'lama' && fotoTerbukaKey.key === id) fotoTerbukaKey = null;
            renderFotoLama();
        }

        function renderFotoBaru() {
            const grid = document.getElementById('fotoBaruGrid');
            grid.innerHTML = fotoBaruFiles.map((file, idx) => templateFotoItem({
                terbuka: !!(fotoTerbukaKey && fotoTerbukaKey.jenis === 'baru' && fotoTerbukaKey.key === idx),
                src: URL.createObjectURL(file),
                caption: fotoBaruCaptions[idx] || '',
                onToggle: `toggleFotoTerbuka('baru', ${idx})`,
                onHapus: `hapusFotoBaru(${idx})`,
            })).join('');

            grid.querySelectorAll('.foto-thumb-expanded textarea').forEach(el => {
                el.oninput = (e) => { fotoBaruCaptions[fotoTerbukaKey.key] = e.target.value; };
            });

            perbaruiCatatanFoto();
        }

        function sinkronkanInputFoto() {
            const dt = new DataTransfer();
            fotoBaruFiles.forEach(f => dt.items.add(f));
            document.getElementById('fotoBaruInput').files = dt.files;
        }

        function hapusFotoBaru(idx) {
            fotoBaruFiles.splice(idx, 1);
            fotoBaruCaptions.splice(idx, 1);
            fotoTerbukaKey = null; // index bisa geser setelah splice, biar aman langsung tutup accordion
            sinkronkanInputFoto();
            renderFotoBaru();
        }

        function perbaruiCatatanFoto() {
            const sisaLama = fotoLama.filter(f => !hapusFotoIds.includes(f.id)).length;
            const total = sisaLama + fotoBaruFiles.length;
            const note = document.getElementById('fotoEmptyNote');
            note.style.display = total === 0 ? 'block' : 'none';
        }

        function tambahFotoBaru(fileList) {
            const sisaLama = fotoLama.filter(f => !hapusFotoIds.includes(f.id)).length;
            const dipilihFormatOk = Array.from(fileList).filter(f => f.type === 'image/jpeg' || f.type === 'image/png');

            const batasByte = MAX_UKURAN_MENTAH_MB * 1024 * 1024;
            const kebesaran = dipilihFormatOk.filter(f => f.size > batasByte);
            const dipilihBaru = dipilihFormatOk.filter(f => f.size <= batasByte);

            if (kebesaran.length > 0) {
                const daftar = kebesaran.map(f => f.name).join(', ');
                showToast(`${kebesaran.length} foto melebihi ${MAX_UKURAN_MENTAH_MB}MB dan tidak diupload: ${daftar}`);
            }

            const sisaSlot = MAX_FOTO - sisaLama - fotoBaruFiles.length;
            let dipakai = dipilihBaru;
            if (dipilihBaru.length > sisaSlot) {
                showToast(`Maksimal ${MAX_FOTO} foto total. Cuma ${Math.max(0, sisaSlot)} foto tambahan yang dipakai.`);
                dipakai = dipilihBaru.slice(0, Math.max(0, sisaSlot));
            }

            fotoBaruFiles = fotoBaruFiles.concat(dipakai);
            fotoBaruCaptions = fotoBaruCaptions.concat(dipakai.map(() => ''));
            sinkronkanInputFoto();
            renderFotoBaru();
        }

        document.getElementById('fotoBaruInput').addEventListener('change', (e) => {
            tambahFotoBaru(e.target.files);
            // CATATAN: sengaja TIDAK reset e.target.value di sini. Reset value pada
            // <input type="file"> ikut ngosongin .files-nya juga, padahal .files ini
            // yang dipakai FormData pas submit (di-set manual lewat DataTransfer di
            // sinkronkanInputFoto()). Kalau di-reset, foto keliatan OK di preview
            // tapi ilang beneran pas submit ke server.
        });

        const fotoDropzone = document.getElementById('fotoDropzone');
        fotoDropzone.addEventListener('click', () => document.getElementById('fotoBaruInput').click());
        fotoDropzone.addEventListener('dragover', (e) => { e.preventDefault(); fotoDropzone.classList.add('dragover'); });
        fotoDropzone.addEventListener('dragleave', () => fotoDropzone.classList.remove('dragover'));
        fotoDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            fotoDropzone.classList.remove('dragover');
            tambahFotoBaru(e.dataTransfer.files);
        });

        // ==================== Buka modal: Tambah ====================
        async function openCreateModal() {
            mode = 'create';
            statusKegiatanAktif = null;
            document.getElementById('formKegiatan').reset();
            document.getElementById('kegiatanId').value = '';
            document.getElementById('modalKegiatanTitle').textContent = 'Tambah Kegiatan';
            document.getElementById('kodeKegiatanInfo').textContent = 'Kode akan digenerate otomatis setelah disimpan';
            document.getElementById('btnSimpanKegiatan').textContent = 'Simpan';
            document.getElementById('editCatatanVerifikator').style.display = 'none';

            tagIdTerpilih = new Set();
            tagBaruTerpilih = new Set();
            deptTerpilih = new Set();
            fotoLama = [];
            hapusFotoIds = [];
            fotoBaruFiles = [];
            fotoBaruCaptions = [];
            fotoTerbukaKey = null;

            document.getElementById('fotoLamaGrid').innerHTML = '';
            document.getElementById('fotoLamaLabel').style.display = 'none';
            renderFotoBaru();

            resetLokasiPicker();
            perbaruiStatusPreview();
            perbaruiVisibilitasHasil();
            await muatTags();
            await muatDepartemen();

            modalKegiatan.show();
        }

        // ==================== Preview Kegiatan (read-only, klik baris) ====================
        const modalPreviewKegiatan = new bootstrap.Modal(document.getElementById('modalPreviewKegiatan'));
        let previewIdAktif = null;

        function previewIsi(id, teks) {
            const el = document.getElementById(id);
            const kosong = teks === null || teks === undefined || teks === '';
            el.textContent = kosong ? '-' : teks;
            el.classList.toggle('kosong', kosong);
        }

        async function openPreviewModal(id) {
            const res  = await fetch(`${API_KEGIATAN}?action=detail&id=${id}`);
            const json = await res.json();

            if (!json.success) {
                showToast(json.message || 'Gagal memuat data kegiatan.');
                return;
            }

            const d = json.data;
            previewIdAktif = d.id;

            document.getElementById('previewJudul').textContent = d.judul;
            document.getElementById('previewKode').textContent = `Kode: ${d.kode_kegiatan}`;

            const catatanBoxPreview = document.getElementById('previewCatatanVerifikator');
            if (d.status_verifikasi === 'rejected' && d.catatan_verifikasi) {
                catatanBoxPreview.style.display = 'block';
                document.getElementById('previewCatatanVerifikatorTeks').textContent = d.catatan_verifikasi;
            } else {
                catatanBoxPreview.style.display = 'none';
            }

            const badge = document.getElementById('previewStatusBadge');
            badge.className = `badge-status-preview ${d.status}`;
            badge.textContent = LABEL_STATUS[d.status] || d.status;
            document.getElementById('previewSkala').textContent = d.skala_kegiatan;

            previewIsi('previewLokasi', `${d.nama_lokasi} — ${d.kabupaten_kota}`);
            previewIsi('previewTanggal', formatTanggalRange(d.tanggal_mulai, d.tanggal_selesai));
            previewIsi('previewPenyelenggara', d.penyelenggara);
            previewIsi('previewPeserta', d.jumlah_peserta ?? null);
            // Diverifikasi oleh cuma relevan kalau kegiatannya udah pernah diverifikasi --
            // masih 'pending' -> diverifikasi_oleh_nama pasti null, ditampilin "-" (wajar).
            previewIsi('previewDiajukanOleh', d.dibuat_oleh_nama);
            previewIsi('previewDiverifikasiOleh', d.diverifikasi_oleh_nama);
            previewIsi('previewDeskripsi', d.deskripsi);
            previewIsi('previewRencanaOutput', d.rencana_output);
            previewIsi('previewDeskripsiHasil', d.deskripsi_hasil);

            const tagsWrap = document.getElementById('previewTags');
            tagsWrap.innerHTML = (d.tags && d.tags.length > 0)
                ? d.tags.map(t => `<span class="preview-tag">${t.nama_tag}</span>`).join('')
                : '<span class="preview-value kosong">Belum ada tag.</span>';

            const deptWrap = document.getElementById('previewDept');
            deptWrap.innerHTML = (d.departemen && d.departemen.length > 0)
                ? d.departemen.map(kode => `<span class="preview-dept-tag">${kode}</span>`).join('')
                : '<span class="preview-value kosong">Belum ada departemen.</span>';

            const fotoGrid   = document.getElementById('previewFotoGrid');
            const fotoKosong = document.getElementById('previewFotoKosong');
            if (d.foto && d.foto.length > 0) {
                fotoGrid.innerHTML = d.foto.map(f => `
                    <a href="<?= BASE_URL ?>/${f.path_foto}" target="_blank" rel="noopener" title="${escAttr(f.caption)}">
                        <img src="<?= BASE_URL ?>/${f.path_foto}" alt="${escAttr(f.caption) || 'Foto kegiatan'}">
                    </a>
                `).join('');
                fotoKosong.style.display = 'none';
            } else {
                fotoGrid.innerHTML = '';
                fotoKosong.style.display = 'block';
            }

            modalPreviewKegiatan.show();
        }

        document.getElementById('btnEditDariPreview').addEventListener('click', () => {
            modalPreviewKegiatan.hide();
            if (previewIdAktif) openEditModal(previewIdAktif);
        });

        // ==================== Highlight elemen (deep-link dari dashboard "Perlu Perhatian") ====================
        // Nama field -> id elemen yang mau di-highlight & di-scroll-in-view di dalam modal.
        const HIGHLIGHT_TARGET = {
            foto: 'fotoSectionWrap',
        };

        function highlightField(nama) {
            const targetId = HIGHLIGHT_TARGET[nama];
            if (!targetId) return;
            const el = document.getElementById(targetId);
            if (!el) return;

            setTimeout(() => {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('field-highlight');
                setTimeout(() => el.classList.remove('field-highlight'), 4500);
            }, 250); // nunggu transisi modal Bootstrap kelar biar scroll-nya akurat
        }

        // ==================== Buka modal: Edit / Ajukan Revisi ====================
        // Satu fungsi buat dua mode -- kalau kegiatan sudah approved & yang buka
        // bukan superadmin, otomatis dialihkan ke mode "Ajukan Revisi" (perubahan
        // gak langsung diterapkan, harus direview dulu di halaman Verifikasi).
        async function openEditModal(id, highlight = null) {
            const res  = await fetch(`${API_KEGIATAN}?action=detail&id=${id}`);
            const json = await res.json();

            if (!json.success) {
                showToast(json.message || 'Gagal memuat data kegiatan.');
                return;
            }

            const d = json.data;
            const perluAjukanRevisi = d.status_verifikasi === 'approved' && CURRENT_ROLE !== 'superadmin';

            if (perluAjukanRevisi && revisiPendingMap[id]) {
                showToast('Sudah ada pengajuan revisi yang menunggu persetujuan untuk kegiatan ini.');
                bukaDetailRevisi(revisiPendingMap[id]);
                return;
            }

            mode = perluAjukanRevisi ? 'ajukan_revisi' : 'update';
            statusKegiatanAktif = d.status;

            document.getElementById('modalKegiatanTitle').textContent = perluAjukanRevisi ? 'Ajukan Revisi Kegiatan' : 'Edit Kegiatan';
            document.getElementById('kodeKegiatanInfo').textContent = perluAjukanRevisi
                ? `Kode: ${d.kode_kegiatan} — perubahan direview dulu sebelum diterapkan`
                : `Kode: ${d.kode_kegiatan}`;
            document.getElementById('btnSimpanKegiatan').textContent = perluAjukanRevisi ? 'Ajukan Revisi' : 'Simpan';
            document.getElementById('kegiatanId').value = d.id;

            const catatanBoxEdit = document.getElementById('editCatatanVerifikator');
            if (d.status_verifikasi === 'rejected' && d.catatan_verifikasi) {
                catatanBoxEdit.style.display = 'block';
                document.getElementById('editCatatanVerifikatorTeks').textContent = d.catatan_verifikasi;
            } else {
                catatanBoxEdit.style.display = 'none';
            }

            document.getElementById('judul').value = d.judul;
            document.getElementById('skalaKegiatan').value = d.skala_kegiatan;
            document.getElementById('tanggalMulai').value = d.tanggal_mulai;
            document.getElementById('tanggalSelesai').value = d.tanggal_selesai;
            document.getElementById('penyelenggara').value = d.penyelenggara || '';
            document.getElementById('jumlahPeserta').value = d.jumlah_peserta || '';
            document.getElementById('deskripsi').value = d.deskripsi || '';
            document.getElementById('rencanaOutput').value = d.rencana_output || '';
            document.getElementById('deskripsiHasil').value = d.deskripsi_hasil || '';
            perbaruiStatusPreview();
            perbaruiVisibilitasHasil();

            tagIdTerpilih = new Set(d.tags.map(t => t.id));
            tagBaruTerpilih = new Set();
            deptTerpilih = new Set(d.departemen || []);

            fotoLama = d.foto;
            hapusFotoIds = [];
            fotoBaruFiles = [];
            fotoBaruCaptions = [];
            fotoTerbukaKey = null;
            document.getElementById('fotoBaruInput').value = '';

            setLokasiTerpilih(d.location_id, d.nama_lokasi, d.kabupaten_kota);
            await muatTags();
            await muatDepartemen();
            renderFotoLama();
            renderFotoBaru();

            modalKegiatan.show();
            if (highlight) highlightField(highlight);
        }

        // ==================== Submit form ====================
        function fokusDanScroll(el) {
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.focus({ preventScroll: true });
        }

        function validasiFormKegiatan() {
            const judul   = document.getElementById('judul');
            const skala   = document.getElementById('skalaKegiatan');
            const mulai   = document.getElementById('tanggalMulai');
            const selesai = document.getElementById('tanggalSelesai');

            if (judul.value.trim() === '') {
                showToast('Judul kegiatan wajib diisi.');
                fokusDanScroll(judul);
                return false;
            }
            if (skala.value === '') {
                showToast('Skala kegiatan wajib dipilih.');
                fokusDanScroll(skala);
                return false;
            }
            if (!mulai.value || !selesai.value) {
                showToast('Tanggal mulai dan selesai wajib diisi.');
                fokusDanScroll(!mulai.value ? mulai : selesai);
                return false;
            }
            if (selesai.value < mulai.value) {
                showToast('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                fokusDanScroll(selesai);
                return false;
            }
            if (!locationIdInput.value) {
                lokasiPicker.classList.add('invalid');
                showToast('Lokasi kegiatan wajib dipilih.');
                fokusDanScroll(lokasiPickerBtn);
                return false;
            }

            return true;
        }

        document.getElementById('formKegiatan').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!validasiFormKegiatan()) return;

            const formData = new FormData(e.target);

            // Mode 'ajukan_revisi' -> POST ke API_REVISI (action=ajukan), butuh field
            // kegiatan_id (bukan cuma 'id'). Mode lain tetap ke API_KEGIATAN seperti biasa.
            const targetApi = mode === 'ajukan_revisi' ? API_REVISI : API_KEGIATAN;
            formData.append('action', mode === 'ajukan_revisi' ? 'ajukan' : mode);
            if (mode === 'ajukan_revisi') {
                formData.append('kegiatan_id', document.getElementById('kegiatanId').value);
            }

            tagIdTerpilih.forEach(id => formData.append('tag_ids[]', id));
            tagBaruTerpilih.forEach(nama => formData.append('tag_baru[]', nama));
            deptTerpilih.forEach(kode => formData.append('departemen[]', kode));
            hapusFotoIds.forEach(id => formData.append('hapus_foto_ids[]', id));
            fotoBaruCaptions.forEach(cap => formData.append('foto_caption[]', cap || ''));
            fotoLama.forEach(f => {
                if (!hapusFotoIds.includes(f.id)) formData.append(`caption_lama[${f.id}]`, f.caption || '');
            });

            const btn = document.getElementById('btnSimpanKegiatan');
            btn.disabled = true;
            btn.textContent = mode === 'ajukan_revisi' ? 'Mengajukan...' : 'Menyimpan...';

            try {
                const res  = await fetch(targetApi, { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    modalKegiatan.hide();
                    // Kalau ada masalah upload foto, tetap tampil sebagai toast warning
                    // (bukan toast hijau) biar gak kelewat kebaca -- data kegiatan-nya
                    // sendiri tetap tersimpan, cuma fotonya yang gagal.
                    showToast(json.message, json.fotoWarning ? 'error' : 'success');
                    loadKegiatanList();
                } else {
                    showToast(json.message || 'Gagal menyimpan data.');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi ke server.');
            } finally {
                btn.disabled = false;
                btn.textContent = mode === 'ajukan_revisi' ? 'Ajukan Revisi' : 'Simpan';
            }
        });

        // ==================== Detail Pengajuan Revisi (read-only + batal) ====================
        const modalDetailRevisi = new bootstrap.Modal(document.getElementById('modalDetailRevisi'));
        let revisiIdAktif = null;

        function tampilkanNilaiRevisi(v) {
            if (v === null || v === undefined || v === '') return '<span class="text-muted">(kosong)</span>';
            if (Array.isArray(v)) return v.length ? v.join(', ') : '<span class="text-muted">(kosong)</span>';
            return String(v);
        }

        async function bukaDetailRevisi(revisiId) {
            try {
                const res  = await fetch(`${API_REVISI}?action=detail&id=${revisiId}`);
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Gagal memuat detail revisi.');

                const r = json.data;
                revisiIdAktif = revisiId;
                document.getElementById('revisiKodeKegiatan').textContent = `${r.kode_kegiatan} — ${r.judul_kegiatan}`;

                const list = document.getElementById('revisiDiffList');
                let html = '';

                const fieldKeys = Object.keys(r.fields || {});
                if (fieldKeys.length === 0) {
                    html += '<p class="text-muted small">Tidak ada perubahan data teks (cuma foto).</p>';
                } else {
                    fieldKeys.forEach(key => {
                        const f = r.fields[key];
                        html += `
                            <div class="revisi-diff-item">
                                <div class="revisi-diff-label">${f.label}</div>
                                <div class="revisi-diff-row">
                                    <div class="revisi-diff-lama">${tampilkanNilaiRevisi(f.lama)}</div>
                                    <div class="revisi-diff-arrow">→</div>
                                    <div class="revisi-diff-baru">${tampilkanNilaiRevisi(f.baru)}</div>
                                </div>
                            </div>
                        `;
                    });
                }

                if (r.foto_tambah && r.foto_tambah.length) {
                    html += `
                        <div class="revisi-diff-item">
                            <div class="revisi-diff-label">Foto Ditambahkan (${r.foto_tambah.length})</div>
                            <div class="revisi-foto-grid">
                                ${r.foto_tambah.map(f => `
                                    <div class="revisi-foto-item">
                                        <img src="<?= BASE_URL ?>/${f.path}" alt="Foto baru">
                                        <div class="revisi-foto-caption ${f.caption ? '' : 'kosong'}">${f.caption ? escAttr(f.caption) : '(tanpa caption)'}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
                if (r.foto_hapus && r.foto_hapus.length) {
                    html += `
                        <div class="revisi-diff-item">
                            <div class="revisi-diff-label">Foto Akan Dihapus (${r.foto_hapus.length})</div>
                            <div class="revisi-foto-grid hapus">
                                ${r.foto_hapus.map(f => `<div class="revisi-foto-item"><img src="<?= BASE_URL ?>/${f.path_foto}" alt="Foto dihapus"></div>`).join('')}
                            </div>
                        </div>
                    `;
                }
                if (r.foto_caption_ubah && r.foto_caption_ubah.length) {
                    html += `
                        <div class="revisi-diff-item">
                            <div class="revisi-diff-label">Caption Foto Diubah (${r.foto_caption_ubah.length})</div>
                            ${r.foto_caption_ubah.map(f => `
                                <div class="revisi-diff-row mb-2" style="align-items:center;">
                                    <img src="<?= BASE_URL ?>/${f.path_foto}" alt="Foto" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid var(--admin-border);flex-shrink:0;">
                                    <div class="revisi-diff-lama">${tampilkanNilaiRevisi(f.caption_lama)}</div>
                                    <div class="revisi-diff-arrow">→</div>
                                    <div class="revisi-diff-baru">${tampilkanNilaiRevisi(f.caption_baru)}</div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }

                list.innerHTML = html;
                modalDetailRevisi.show();
            } catch (err) {
                showToast(err.message || 'Gagal memuat detail revisi.');
            }
        }

        document.getElementById('btnBatalRevisi').addEventListener('click', async () => {
            if (!revisiIdAktif) return;
            const btn = document.getElementById('btnBatalRevisi');
            btn.disabled = true;
            btn.textContent = 'Membatalkan...';

            try {
                const formData = new FormData();
                formData.append('action', 'batal');
                formData.append('id', revisiIdAktif);

                const res  = await fetch(API_REVISI, { method: 'POST', body: formData });
                const json = await res.json();

                modalDetailRevisi.hide();
                if (json.success) {
                    showToast(json.message, 'success');
                    loadKegiatanList();
                } else {
                    showToast(json.message || 'Gagal membatalkan pengajuan revisi.');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi ke server.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Batalkan Pengajuan';
                revisiIdAktif = null;
            }
        });

        // ==================== Hapus kegiatan ====================
        let idAkanDihapus = null;
        const modalKonfirmasiHapusKegiatan = new bootstrap.Modal(document.getElementById('modalKonfirmasiHapusKegiatan'));

        function hapusKegiatan(id, judul) {
            idAkanDihapus = id;
            document.getElementById('judulKegiatanDihapus').textContent = `"${judul}"`;
            modalKonfirmasiHapusKegiatan.show();
        }

        document.getElementById('btnKonfirmasiHapusKegiatan').addEventListener('click', async () => {
            if (!idAkanDihapus) return;

            const btn = document.getElementById('btnKonfirmasiHapusKegiatan');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', idAkanDihapus);

            try {
                const res  = await fetch(API_KEGIATAN, { method: 'POST', body: formData });
                const json = await res.json();

                modalKonfirmasiHapusKegiatan.hide();

                if (json.success) {
                    showToast(json.message, 'success');
                    loadKegiatanList();
                } else {
                    showToast(json.message || 'Gagal menghapus kegiatan.');
                }
            } catch (err) {
                modalKonfirmasiHapusKegiatan.hide();
                showToast('Terjadi kesalahan koneksi ke server.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Ya, Hapus';
                idAkanDihapus = null;
            }
        });

        // ==================== Tandai kegiatan Selesai (popup: hasil + dokumentasi) ====================
        const modalSelesaiKegiatan = new bootstrap.Modal(document.getElementById('modalSelesaiKegiatan'));
        let idAkanDiselesaikan   = null;
        let fotoLamaSelesai      = [];  // [{id, path_foto, caption}] foto yang udah ada di kegiatan ini
        let hapusFotoIdsSelesai  = [];
        let fotoBaruFilesSelesai = [];
        let fotoBaruCaptionsSelesai = []; // caption[] baru, selaras sama fotoBaruFilesSelesai
        let fotoTerbukaKeySelesai = null; // { jenis: 'lama'|'baru', key } -- cuma 1 foto yang bisa kebuka sekaligus

        function toggleFotoTerbukaSelesai(jenis, key) {
            fotoTerbukaKeySelesai = (fotoTerbukaKeySelesai && fotoTerbukaKeySelesai.jenis === jenis && fotoTerbukaKeySelesai.key === key)
                ? null
                : { jenis, key };
            renderFotoLamaSelesai();
            renderFotoBaruSelesai();
        }

        function renderFotoLamaSelesai() {
            const grid  = document.getElementById('fotoLamaGridSelesai');
            const label = document.getElementById('fotoLamaLabelSelesai');
            const sisa  = fotoLamaSelesai.filter(f => !hapusFotoIdsSelesai.includes(f.id));

            grid.innerHTML = sisa.map(f => templateFotoItem({
                terbuka: !!(fotoTerbukaKeySelesai && fotoTerbukaKeySelesai.jenis === 'lama' && fotoTerbukaKeySelesai.key === f.id),
                src: `<?= BASE_URL ?>/${f.path_foto}`,
                caption: f.caption || '',
                onToggle: `toggleFotoTerbukaSelesai('lama', ${f.id})`,
                onHapus: `hapusFotoLamaSelesai(${f.id})`,
            })).join('');

            grid.querySelectorAll('.foto-thumb-expanded textarea').forEach(el => {
                el.oninput = (e) => { const f = fotoLamaSelesai.find(x => x.id === fotoTerbukaKeySelesai.key); if (f) f.caption = e.target.value; };
            });

            label.style.display = sisa.length > 0 ? 'block' : 'none';
            perbaruiCatatanFotoSelesai();
        }

        function hapusFotoLamaSelesai(id) {
            hapusFotoIdsSelesai.push(id);
            if (fotoTerbukaKeySelesai && fotoTerbukaKeySelesai.jenis === 'lama' && fotoTerbukaKeySelesai.key === id) fotoTerbukaKeySelesai = null;
            renderFotoLamaSelesai();
        }

        function renderFotoBaruSelesai() {
            const grid = document.getElementById('fotoBaruGridSelesai');
            grid.innerHTML = fotoBaruFilesSelesai.map((file, idx) => templateFotoItem({
                terbuka: !!(fotoTerbukaKeySelesai && fotoTerbukaKeySelesai.jenis === 'baru' && fotoTerbukaKeySelesai.key === idx),
                src: URL.createObjectURL(file),
                caption: fotoBaruCaptionsSelesai[idx] || '',
                onToggle: `toggleFotoTerbukaSelesai('baru', ${idx})`,
                onHapus: `hapusFotoBaruSelesai(${idx})`,
            })).join('');

            grid.querySelectorAll('.foto-thumb-expanded textarea').forEach(el => {
                el.oninput = (e) => { fotoBaruCaptionsSelesai[fotoTerbukaKeySelesai.key] = e.target.value; };
            });

            perbaruiCatatanFotoSelesai();
        }

        function sinkronkanInputFotoSelesai() {
            const dt = new DataTransfer();
            fotoBaruFilesSelesai.forEach(f => dt.items.add(f));
            document.getElementById('fotoBaruInputSelesai').files = dt.files;
        }

        function hapusFotoBaruSelesai(idx) {
            fotoBaruFilesSelesai.splice(idx, 1);
            fotoBaruCaptionsSelesai.splice(idx, 1);
            fotoTerbukaKeySelesai = null; // index bisa geser setelah splice, biar aman langsung tutup accordion
            sinkronkanInputFotoSelesai();
            renderFotoBaruSelesai();
        }

        function perbaruiCatatanFotoSelesai() {
            const sisaLama = fotoLamaSelesai.filter(f => !hapusFotoIdsSelesai.includes(f.id)).length;
            const total = sisaLama + fotoBaruFilesSelesai.length;
            document.getElementById('fotoEmptyNoteSelesai').style.display = total === 0 ? 'block' : 'none';
        }

        function tambahFotoBaruSelesai(fileList) {
            const sisaLama = fotoLamaSelesai.filter(f => !hapusFotoIdsSelesai.includes(f.id)).length;
            const dipilihFormatOk = Array.from(fileList).filter(f => f.type === 'image/jpeg' || f.type === 'image/png');

            const batasByte = MAX_UKURAN_MENTAH_MB * 1024 * 1024;
            const kebesaran = dipilihFormatOk.filter(f => f.size > batasByte);
            const dipilihBaru = dipilihFormatOk.filter(f => f.size <= batasByte);

            if (kebesaran.length > 0) {
                const daftar = kebesaran.map(f => f.name).join(', ');
                showToast(`${kebesaran.length} foto melebihi ${MAX_UKURAN_MENTAH_MB}MB dan tidak diupload: ${daftar}`);
            }

            const sisaSlot = MAX_FOTO - sisaLama - fotoBaruFilesSelesai.length;
            let dipakai = dipilihBaru;
            if (dipilihBaru.length > sisaSlot) {
                showToast(`Maksimal ${MAX_FOTO} foto total. Cuma ${Math.max(0, sisaSlot)} foto tambahan yang dipakai.`);
                dipakai = dipilihBaru.slice(0, Math.max(0, sisaSlot));
            }

            fotoBaruFilesSelesai = fotoBaruFilesSelesai.concat(dipakai);
            fotoBaruCaptionsSelesai = fotoBaruCaptionsSelesai.concat(dipakai.map(() => ''));
            sinkronkanInputFotoSelesai();
            renderFotoBaruSelesai();
        }

        document.getElementById('fotoBaruInputSelesai').addEventListener('change', (e) => {
            tambahFotoBaruSelesai(e.target.files);
            // Sama kayak input utama -- jangan reset value, lihat catatan di atas.
        });

        const fotoDropzoneSelesai = document.getElementById('fotoDropzoneSelesai');
        fotoDropzoneSelesai.addEventListener('click', () => document.getElementById('fotoBaruInputSelesai').click());
        fotoDropzoneSelesai.addEventListener('dragover', (e) => { e.preventDefault(); fotoDropzoneSelesai.classList.add('dragover'); });
        fotoDropzoneSelesai.addEventListener('dragleave', () => fotoDropzoneSelesai.classList.remove('dragover'));
        fotoDropzoneSelesai.addEventListener('drop', (e) => {
            e.preventDefault();
            fotoDropzoneSelesai.classList.remove('dragover');
            tambahFotoBaruSelesai(e.dataTransfer.files);
        });

        // Buka popup -- ambil detail kegiatan biar foto lama & (kalau ada) draft deskripsi hasil ke-load.
        async function bukaModalSelesai(id) {
            const res  = await fetch(`${API_KEGIATAN}?action=detail&id=${id}`);
            const json = await res.json();

            if (!json.success) {
                showToast(json.message || 'Gagal memuat data kegiatan.');
                return;
            }

            const d = json.data;
            idAkanDiselesaikan = d.id;

            document.getElementById('judulKegiatanDiselesaikan').textContent = d.judul;
            document.getElementById('deskripsiHasilSelesai').value = d.deskripsi_hasil || '';

            fotoLamaSelesai      = d.foto || [];
            hapusFotoIdsSelesai  = [];
            fotoBaruFilesSelesai = [];
            fotoBaruCaptionsSelesai = [];
            fotoTerbukaKeySelesai = null;
            document.getElementById('fotoBaruInputSelesai').value = '';
            renderFotoLamaSelesai();
            renderFotoBaruSelesai();

            modalSelesaiKegiatan.show();
        }

        document.getElementById('btnSubmitSelesaiKegiatan').addEventListener('click', async () => {
            if (!idAkanDiselesaikan) return;

            const deskripsiHasil = document.getElementById('deskripsiHasilSelesai').value.trim();
            if (!deskripsiHasil) {
                showToast('Deskripsi Hasil wajib diisi.');
                return;
            }

            const btn = document.getElementById('btnSubmitSelesaiKegiatan');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            const formData = new FormData();
            formData.append('action', 'selesaikan');
            formData.append('id', idAkanDiselesaikan);
            formData.append('deskripsi_hasil', deskripsiHasil);
            hapusFotoIdsSelesai.forEach(id => formData.append('hapus_foto_ids[]', id));
            fotoBaruFilesSelesai.forEach(f => formData.append('foto_baru[]', f));
            fotoBaruCaptionsSelesai.forEach(cap => formData.append('foto_caption[]', cap || ''));
            fotoLamaSelesai.forEach(f => {
                if (!hapusFotoIdsSelesai.includes(f.id)) formData.append(`caption_lama[${f.id}]`, f.caption || '');
            });

            try {
                const res  = await fetch(API_KEGIATAN, { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    modalSelesaiKegiatan.hide();
                    showToast(json.message, 'success');
                    loadKegiatanList();
                } else {
                    showToast(json.message || 'Gagal menandai kegiatan selesai.');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi ke server.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Selesai';
                idAkanDiselesaikan = null;
            }
        });

        // ==================== Load, filter, sort & render tabel ====================
        const PAGE_SIZE = 15;
        let semuaKegiatan = [];
        let currentPage = 1;
        let sortColumn    = 'tanggal_mulai';
        let sortDirection = 'desc'; // 'asc' | 'desc'

        function formatTanggal(tgl) {
            const [y, m, d] = tgl.split('-');
            const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return `${parseInt(d)} ${bulan[parseInt(m) - 1]} ${y}`;
        }

        function formatTanggalRange(mulai, selesai) {
            return mulai === selesai ? formatTanggal(mulai) : `${formatTanggal(mulai)} - ${formatTanggal(selesai)}`;
        }

        function badgeStatus(status) {
            if (status === 'selesai') return '<span class="badge-status selesai">✓ Selesai</span>';
            if (status === 'belum_berlangsung') return '<span class="badge-status belum_berlangsung">◷ Belum Berlangsung</span>';
            return '<span class="badge-status berlangsung">● Berlangsung</span>';
        }

        // Cuma tampil buat pending/rejected -- approved dianggap kondisi normal,
        // gak perlu ditandai lagi biar tabel gak penuh badge. Catatan verifikator
        // (kalau ditolak) ditampilkan di DALAM modal preview/edit, bukan tooltip.
        function badgeVerifikasi(k) {
            if (k.status_verifikasi === 'pending') {
                return '<span class="badge-verif pending">⏳ Menunggu Verifikasi</span>';
            }
            if (k.status_verifikasi === 'rejected') {
                return '<span class="badge-verif rejected">✕ Ditolak</span>';
            }
            return '';
        }

        async function loadKegiatanList() {
            const res  = await fetch(`${API_KEGIATAN}?action=list`);
            const json = await res.json();
            semuaKegiatan = json.success ? json.data : [];
            currentPage = 1;
            await muatRevisiPendingMap();
            renderTabel();
        }

        // Ambil daftar pengajuan revisi yang masih pending, buat badge & blokir
        // tombol Edit di baris kegiatan yang bersangkutan (satu pending per kegiatan).
        async function muatRevisiPendingMap() {
            try {
                const res  = await fetch(`${API_REVISI}?action=list&status_verifikasi=pending`);
                const json = await res.json();
                revisiPendingMap = {};
                if (json.success) {
                    json.data.forEach(r => { revisiPendingMap[r.kegiatan_id] = r.id; });
                }
            } catch (err) {
                revisiPendingMap = {};
            }
        }

        function getDataTampil() {
            const keyword = document.getElementById('inputCari').value.trim().toLowerCase();
            const filterStatus = document.getElementById('filterStatus').value;

            let data = semuaKegiatan.filter(k => {
                if (filterStatus && k.status !== filterStatus) return false;
                if (!keyword) return true;
                return k.judul.toLowerCase().includes(keyword) ||
                       k.nama_lokasi.toLowerCase().includes(keyword) ||
                       k.kode_kegiatan.toLowerCase().includes(keyword);
            });

            data.sort((a, b) => {
                let hasil = bandingkanKolom(a, b, sortColumn);
                return sortDirection === 'asc' ? hasil : -hasil;
            });

            return data;
        }

        // Komparator generik per kolom -- otomatis nentuin cara banding
        // (tanggal, angka, atau teks) berdasarkan nama kolomnya.
        function bandingkanKolom(a, b, kolom) {
            if (kolom === 'tanggal_mulai') {
                return new Date(a.tanggal_mulai) - new Date(b.tanggal_mulai);
            }
            if (kolom === 'jumlah_peserta') {
                const na = a.jumlah_peserta ?? -1;
                const nb = b.jumlah_peserta ?? -1;
                return na - nb;
            }
            // Kolom teks: kode_kegiatan, judul, status
            const va = String(a[kolom] ?? '').toLowerCase();
            const vb = String(b[kolom] ?? '').toLowerCase();
            return va.localeCompare(vb);
        }

        // Tanggal selesai sudah lewat hari ini? (buat aktifkan tombol "Tandai Selesai")
        function sudahLewatTanggalSelesai(tanggalSelesai) {
            const hariIni = new Date().toISOString().slice(0, 10);
            return tanggalSelesai < hariIni;
        }

        function renderTabel() {
            const tbody = document.getElementById('tabelKegiatan');
            const data  = getDataTampil();

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data kegiatan.</td></tr>';
                document.getElementById('paginationRow').innerHTML = '';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;

            const start   = (currentPage - 1) * PAGE_SIZE;
            const halaman = data.slice(start, start + PAGE_SIZE);

            tbody.innerHTML = halaman.map(k => {
                const judulEscaped = k.judul.replace(/'/g, "\\'");

                const sudahSelesai = k.status === 'selesai';
                const tanggalLewat = sudahLewatTanggalSelesai(k.tanggal_selesai);

                let tombolSelesai = '';
                if (!sudahSelesai) {
                    const alasan = tanggalLewat ? 'Tandai Selesai' : 'Tanggal selesai belum lewat';
                    tombolSelesai = `
                        <button class="icon-btn icon-success" title="${alasan}" ${tanggalLewat ? '' : 'disabled'}
                                onclick="event.stopPropagation(); bukaModalSelesai(${k.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </button>
                    `;
                }

                // Kegiatan yang sudah approved gak boleh diedit langsung oleh Internal --
                // kalau udah ada pengajuan revisi pending, tombol Edit dinonaktifkan &
                // badge "Revisi Menunggu" muncul (klik badge = buka pengajuan itu buat dibatalkan).
                const revisiId = revisiPendingMap[k.id];
                const perluAjukanRevisi = k.status_verifikasi === 'approved' && CURRENT_ROLE !== 'superadmin';
                let tombolEdit;
                if (revisiId) {
                    tombolEdit = `
                        <button class="icon-btn" title="Menunggu persetujuan revisi" disabled style="opacity:.35;cursor:default;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    `;
                } else {
                    tombolEdit = `
                        <button class="icon-btn" title="${perluAjukanRevisi ? 'Ajukan Revisi' : 'Edit'}" onclick="event.stopPropagation(); openEditModal(${k.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    `;
                }

                return `
                    <tr class="row-klik" onclick="openPreviewModal(${k.id})">
                        <td class="text-muted small">${k.kode_kegiatan}</td>
                        <td class="judul-lokasi-cell">
                            <div class="judul">${k.judul}</div>
                            <div class="lokasi">${k.nama_lokasi}, ${k.kabupaten_kota}</div>
                            ${badgeVerifikasi(k)}
                            ${!sudahSelesai && tanggalLewat ? '<span class="badge-verif terlambat">⏱ Perlu diselesaikan</span>' : ''}
                            ${revisiId ? `<button type="button" class="badge-verif revisi" onclick="event.stopPropagation(); bukaDetailRevisi(${revisiId})">✎ Revisi Menunggu</button>` : ''}
                        </td>
                        <td class="small">${formatTanggalRange(k.tanggal_mulai, k.tanggal_selesai)}</td>
                        <td>${badgeStatus(k.status)}</td>
                        <td>${k.jumlah_peserta ?? '-'}</td>
                        <td class="td-aksi">
                            ${tombolSelesai}
                            ${tombolEdit}
                            <button class="icon-btn icon-danger" title="Hapus" onclick="event.stopPropagation(); hapusKegiatan(${k.id}, '${judulEscaped}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                    <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            const container = document.getElementById('paginationRow');
            if (totalPages <= 1) { container.innerHTML = ''; return; }

            let html = `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="gotoPage(${currentPage - 1})">&lsaquo;</button>`;

            const tampilkanLangsung = 3;
            for (let p = 1; p <= Math.min(tampilkanLangsung, totalPages); p++) {
                html += `<button class="page-btn ${p === currentPage ? 'active' : ''}" onclick="gotoPage(${p})">${p}</button>`;
            }
            if (totalPages > tampilkanLangsung + 1) html += `<span class="page-ellipsis">…</span>`;
            if (totalPages > tampilkanLangsung) {
                html += `<button class="page-btn ${totalPages === currentPage ? 'active' : ''}" onclick="gotoPage(${totalPages})">${totalPages}</button>`;
            }
            html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="gotoPage(${currentPage + 1})">&rsaquo;</button>`;

            container.innerHTML = html;
        }

        function gotoPage(p) { currentPage = p; renderTabel(); }

        function urutkanBerdasarkan(kolom) {
            if (sortColumn === kolom) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = kolom;
                // Default arah: tanggal & peserta mulai dari terbesar/terbaru, teks dari A-Z
                sortDirection = (kolom === 'tanggal_mulai' || kolom === 'jumlah_peserta') ? 'desc' : 'asc';
            }
            currentPage = 1;
            perbaruiIndikatorSort();
            renderTabel();
        }

        function perbaruiIndikatorSort() {
            document.querySelectorAll('.admin-table th.th-sort').forEach(th => {
                const ind = th.querySelector('.sort-ind');
                if (th.dataset.sort === sortColumn) {
                    th.classList.add('sort-active');
                    ind.textContent = sortDirection === 'asc' ? '▲' : '▼';
                } else {
                    th.classList.remove('sort-active');
                    ind.textContent = '';
                }
            });
        }

        document.querySelectorAll('.admin-table th.th-sort').forEach(th => {
            th.addEventListener('click', () => urutkanBerdasarkan(th.dataset.sort));
        });
        perbaruiIndikatorSort();

        let searchDebounce;
        document.getElementById('inputCari').addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => { currentPage = 1; renderTabel(); }, 250);
        });

        document.getElementById('filterStatus').addEventListener('change', () => {
            currentPage = 1;
            renderTabel();
        });

        loadKegiatanList();

        // Deep-link dari dashboard:
        // - "Aksi Cepat" -> ?aksi=tambah -> langsung buka modal Tambah
        // - Panel "Perlu Perhatian" -> ?edit=<id>&highlight=foto -> buka modal Edit + highlight
        // - Panel "Perlu Perhatian" -> ?selesai=<id> -> langsung buka modal "Tandai Selesai"
        const paramsUrl = new URLSearchParams(location.search);
        if (paramsUrl.get('aksi') === 'tambah') {
            openCreateModal();
        } else if (paramsUrl.get('edit')) {
            openEditModal(paramsUrl.get('edit'), paramsUrl.get('highlight'));
        } else if (paramsUrl.get('selesai')) {
            bukaModalSelesai(paramsUrl.get('selesai'));
        }
    </script>
</body>
</html>