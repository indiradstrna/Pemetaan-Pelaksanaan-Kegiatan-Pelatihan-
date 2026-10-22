<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/admin_check.php';

$activePage = 'kelola_lokasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lokasi - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/toast.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">

    <style>
        #wilayahSaatIni { background-color: #f8f9fa; }

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
            </style>
</head>
<body class="admin-body">
    <?php require_once __DIR__ . '/../includes/navbar_admin.php'; ?>

        <h1 class="page-title">Kelola Lokasi</h1>
        <p class="page-subtitle">Menu kumpulan data serta pengelolaan data lokasi</p>

        <div class="admin-toolbar">
            <button type="button" class="btn-tambah" onclick="openCreateModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Tambah lokasi
            </button>

            <div class="toolbar-right">
                <div class="dropdown">
                    <button type="button" class="btn-urutkan btn-filter" id="btnFilterLokasi"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        <span>Filter</span>
                        <span id="filterBadge" class="filter-badge d-none">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end filter-panel p-3" aria-labelledby="btnFilterLokasi">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Provinsi</label>
                                <select class="form-select form-select-sm" id="filterProvinsi">
                                    <option value="">Semua Provinsi</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Kabupaten/Kota</label>
                                <select class="form-select form-select-sm" id="filterKabupaten" disabled>
                                    <option value="">Semua Kab/Kota</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Kecamatan</label>
                                <select class="form-select form-select-sm" id="filterKecamatan" disabled>
                                    <option value="">Semua Kecamatan</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Kelurahan/Desa</label>
                                <select class="form-select form-select-sm" id="filterKelurahan" disabled>
                                    <option value="">Semua Kel/Desa</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Jumlah Kegiatan</label>
                                <select class="form-select form-select-sm" id="filterJumlahPreset">
                                    <option value="">Semua</option>
                                    <option value="0">0 kegiatan</option>
                                    <option value="1-5">1 - 5 kegiatan</option>
                                    <option value="6-10">6 - 10 kegiatan</option>
                                    <option value="10+">Lebih dari 10</option>
                                    <option value="custom">Kustom...</option>
                                </select>
                            </div>
                            <div class="col-6 d-none" id="filterJumlahCustomRow">
                                <label class="form-label small fw-semibold mb-1">Rentang Kustom</label>
                                <div class="d-flex gap-1">
                                    <input type="number" min="0" class="form-control form-control-sm" id="filterJumlahMin" placeholder="Min">
                                    <input type="number" min="0" class="form-control form-control-sm" id="filterJumlahMax" placeholder="Maks">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3 pt-2 border-top">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilterLokasi">Reset</button>
                            <button type="button" class="btn btn-sm btn-simpan" id="btnTerapkanFilterLokasi">Terapkan</button>
                        </div>
                    </div>
                </div>
                <div class="search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="inputCari" placeholder="Cari">
                </div>
                <button type="button" class="btn-urutkan" onclick="toggleUrutkan()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18M6 12h12M10 18h4"></path>
                    </svg>
                    <span id="labelUrutkan">Urutkan</span>
                </button>
            </div>
        </div>

        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama Lokasi</th>
                        <th>Alamat</th>
                        <th>Kegiatan</th>
                        <th class="th-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelLokasi">
                    <tr><td colspan="4" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-row" id="paginationRow"></div>

    <!-- ==================== Modal Tambah/Edit Lokasi ==================== -->
    <div class="modal fade" id="modalLokasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLokasiTitle">Tambah Lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formLokasi">
                    <div class="modal-body">
                        <!-- Field tersembunyi yang beneran dikirim ke server -->
                        <input type="hidden" id="lokasiId" name="id">
                        <input type="hidden" id="fieldProvinsi" name="provinsi">
                        <input type="hidden" id="fieldKabupaten" name="kabupaten_kota">
                        <input type="hidden" id="fieldKecamatan" name="kecamatan">
                        <input type="hidden" id="fieldKelurahan" name="kelurahan">
                        <input type="hidden" id="fieldLatitude" name="latitude">
                        <input type="hidden" id="fieldLongitude" name="longitude">
                        <input type="hidden" id="fieldKoordinatManual" name="koordinat_manual" value="0">

                        <div class="mb-3">
                            <label class="form-label">Nama Lokasi</label>
                            <input type="text" class="form-control" name="nama_lokasi" id="namaLokasi"
                                   placeholder="Contoh: SEAMEO BIOTROP" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Jalan <span class="text-muted small">(opsional)</span></label>
                            <input type="text" class="form-control" name="nama_jalan" id="namaJalan"
                                   placeholder="Contoh: Jl. Raya Tajur KM. 6">
                        </div>

                        <!-- Ringkasan wilayah saat ini (mode edit) -->
                        <div id="wilayahSaatIni" class="alert border d-none mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <small class="text-muted d-block">Wilayah saat ini</small>
                                    <span id="wilayahSaatIniText" class="fw-semibold"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        onclick="tampilkanPilihWilayah()">Ganti Wilayah</button>
                            </div>
                        </div>

                        <!-- Cascading dropdown wilayah -->
                        <div id="wilayahDropdownGroup" class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Provinsi</label>
                                <select class="form-select" id="selectProvinsi">
                                    <option value="">-- Pilih Provinsi --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kabupaten/Kota</label>
                                <select class="form-select" id="selectKabupaten" disabled>
                                    <option value="">-- Pilih Kabupaten/Kota --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kecamatan</label>
                                <select class="form-select" id="selectKecamatan" disabled>
                                    <option value="">-- Pilih Kecamatan --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kelurahan/Desa <span class="text-muted small">(opsional)</span></label>
                                <select class="form-select" id="selectKelurahan" disabled>
                                    <option value="">-- Pilih Kelurahan/Desa --</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Negara</label>
                            <input type="text" class="form-control" name="negara" id="negara" value="Indonesia">
                        </div>

                        <div class="mb-2" id="koordinatSectionWrap">
                        <div class="mb-2">
                            <label class="form-label mb-0">Titik Koordinat <span class="text-muted small">(klik peta atau geser marker)</span></label>
                        </div>
                        <div class="d-flex align-items-start gap-2 mb-2 p-2 rounded" style="background:#fffbeb;border:1px solid #fde68a;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px;">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <small style="color:#92400e;">
                                Titik awal di peta cuma perkiraan berdasarkan wilayah yang dipilih dan kemungkinan belum akurat.
                                Pastikan geser marker atau klik peta ke titik lokasi yang sebenarnya sebelum menyimpan.
                            </small>
                        </div>
                        <div id="mapPicker" style="height: 280px; border-radius: 8px;"></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted">Latitude: <span id="latDisplay">-</span></small>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted">Longitude: <span id="lngDisplay">-</span></small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-simpan" id="btnSimpanLokasi">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Konfirmasi Hapus ==================== -->
    <div class="modal fade" id="modalKonfirmasiHapus" tabindex="-1" aria-hidden="true">
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
                    <h5 class="mb-2">Hapus Lokasi?</h5>
                    <p class="text-muted mb-0">
                        Yakin mau hapus lokasi <strong id="namaLokasiDihapus"></strong>?
                        Data yang sudah dihapus tidak bisa dikembalikan.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger px-4" id="btnKonfirmasiHapus">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Peringatan Duplikat ==================== -->
    <div class="modal fade" id="modalPeringatanDuplikat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center pt-4 pb-2">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;border-radius:50%;background:#fef3c7;color:#b45309;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h5 class="mb-2">Kemungkinan Data Duplikat</h5>
                    <p class="text-muted mb-3">
                        Lokasi yang mau kamu simpan mirip dengan data yang sudah ada di wilayah yang sama:
                    </p>
                    <div class="border rounded p-3 text-start mb-2" style="background:#f9fafb;">
                        <div class="fw-semibold" id="duplikatNamaLokasi"></div>
                        <div class="text-muted small" id="duplikatNamaJalan"></div>
                        <div class="text-muted small" id="duplikatWilayah"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal, Saya Ubah Dulu</button>
                    <button type="button" class="btn btn-warning px-4" id="btnTetapSimpan">Tetap Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Preview Lokasi (read-only) ==================== -->
    <div class="modal fade" id="modalPreviewLokasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewNamaLokasi">-</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Alamat Lengkap</div>
                                <div id="previewAlamat" class="fw-medium">-</div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Titik Koordinat</div>
                                <div id="previewKoordinat" class="fw-medium">-</div>
                            </div>
                            <div class="mb-0">
                                <div class="text-muted small mb-1">Jumlah Kegiatan</div>
                                <div id="previewJumlahKegiatan" class="fw-medium">-</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="mapPreview" style="height: 240px; border-radius: 8px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-simpan" id="btnEditDariPreview">Edit Lokasi</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/toast.js"></script>

    <script>
        const API_LOKASI       = '<?= BASE_URL ?>/api/crud_lokasi.php';
        const API_WILAYAH      = '<?= BASE_URL ?>/api/get_wilayah.php';
        const API_CEK_DUPLIKAT = '<?= BASE_URL ?>/api/cek_duplikat_lokasi.php';

        const DEFAULT_LAT = -6.5971; // sekitar Bogor
        const DEFAULT_LNG = 106.8060;

        let map, marker;
        let mode = 'create'; // 'create' | 'update'
        let targetLat = DEFAULT_LAT;
        let targetLng = DEFAULT_LNG;

        const modalLokasiEl = document.getElementById('modalLokasi');
        const modalLokasi    = new bootstrap.Modal(modalLokasiEl);

        const selProvinsi  = document.getElementById('selectProvinsi');
        const selKabupaten = document.getElementById('selectKabupaten');
        const selKecamatan = document.getElementById('selectKecamatan');
        const selKelurahan = document.getElementById('selectKelurahan');

        // ==================== Peta Leaflet ====================
        function initMapIfNeeded() {
            if (map) return;
            map = L.map('mapPicker', { scrollWheelZoom: false }).setView([DEFAULT_LAT, DEFAULT_LNG], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            marker = L.marker([DEFAULT_LAT, DEFAULT_LNG], { draggable: true }).addTo(map);
            marker.on('dragend', () => {
                document.getElementById('fieldKoordinatManual').value = '1';
                updateLatLngFromMarker();
            });
            map.on('click', (e) => {
                marker.setLatLng(e.latlng);
                document.getElementById('fieldKoordinatManual').value = '1';
                updateLatLngFromMarker();
            });
        }

        function updateLatLngFromMarker() {
            const { lat, lng } = marker.getLatLng();
            document.getElementById('fieldLatitude').value = lat.toFixed(7);
            document.getElementById('fieldLongitude').value = lng.toFixed(7);
            document.getElementById('latDisplay').textContent = lat.toFixed(7);
            document.getElementById('lngDisplay').textContent = lng.toFixed(7);
        }

        function setMapPosition(lat, lng, zoom = 15) {
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], zoom);
            updateLatLngFromMarker();
        }

        modalLokasiEl.addEventListener('shown.bs.modal', () => {
            initMapIfNeeded();
            map.invalidateSize();
            setMapPosition(targetLat, targetLng);
        });

        // ==================== Cascading dropdown wilayah ====================
        async function fetchWilayah(level, parent = '') {
            const url = new URL(API_WILAYAH, window.location.origin);
            url.searchParams.set('level', level);
            if (parent) url.searchParams.set('parent', parent);
            const res = await fetch(url);
            return res.json();
        }

        function resetSelect(select, placeholder) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
            select.disabled = true;
        }

        function fillSelect(select, data) {
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.kode;
                opt.textContent = item.nama;
                opt.dataset.nama = item.nama;
                if (item.latitude !== null && item.latitude !== undefined && item.longitude !== null && item.longitude !== undefined) {
                    opt.dataset.lat = item.latitude;
                    opt.dataset.lng = item.longitude;
                }
                select.appendChild(opt);
            });
            select.disabled = false;
        }

        function setHiddenFromSelect(select, hiddenId) {
            document.getElementById(hiddenId).value = select.selectedOptions[0]?.dataset.nama || '';
        }

        async function loadProvinsiOptions() {
            const data = await fetchWilayah('provinsi');
            fillSelect(selProvinsi, data);
        }

        selProvinsi.addEventListener('change', async () => {
            document.getElementById('fieldKoordinatManual').value = '0'; // ganti wilayah -> titik lama gak relevan lagi
            setHiddenFromSelect(selProvinsi, 'fieldProvinsi');
            resetSelect(selKabupaten, '-- Pilih Kabupaten/Kota --');
            resetSelect(selKecamatan, '-- Pilih Kecamatan --');
            resetSelect(selKelurahan, '-- Pilih Kelurahan/Desa --');
            document.getElementById('fieldKabupaten').value = '';
            document.getElementById('fieldKecamatan').value = '';
            document.getElementById('fieldKelurahan').value = '';

            if (!selProvinsi.value) return;

            // Auto-center peta ke titik tengah provinsi dulu, biar user gak mulai dari 0
            const { lat, lng } = selProvinsi.selectedOptions[0]?.dataset || {};
            if (lat && lng) {
                initMapIfNeeded();
                setMapPosition(parseFloat(lat), parseFloat(lng), 8);
            }

            fillSelect(selKabupaten, await fetchWilayah('kabupaten', selProvinsi.value));
        });

        selKabupaten.addEventListener('change', async () => {
            document.getElementById('fieldKoordinatManual').value = '0';
            setHiddenFromSelect(selKabupaten, 'fieldKabupaten');
            resetSelect(selKecamatan, '-- Pilih Kecamatan --');
            resetSelect(selKelurahan, '-- Pilih Kelurahan/Desa --');
            document.getElementById('fieldKecamatan').value = '';
            document.getElementById('fieldKelurahan').value = '';

            if (!selKabupaten.value) return;

            // Auto-center peta ke titik tengah kab/kota yang dipilih
            const { lat, lng } = selKabupaten.selectedOptions[0]?.dataset || {};
            if (lat && lng) {
                initMapIfNeeded();
                setMapPosition(parseFloat(lat), parseFloat(lng), 11);
            }

            fillSelect(selKecamatan, await fetchWilayah('kecamatan', selKabupaten.value));
        });

        selKecamatan.addEventListener('change', async () => {
            document.getElementById('fieldKoordinatManual').value = '0';
            setHiddenFromSelect(selKecamatan, 'fieldKecamatan');
            resetSelect(selKelurahan, '-- Pilih Kelurahan/Desa --');
            document.getElementById('fieldKelurahan').value = '';

            if (!selKecamatan.value) return;

            // Auto-center peta ke titik tengah kecamatan (hasil rata-rata kelurahan di bawahnya)
            const { lat, lng } = selKecamatan.selectedOptions[0]?.dataset || {};
            if (lat && lng) {
                initMapIfNeeded();
                setMapPosition(parseFloat(lat), parseFloat(lng), 13);
            }

            fillSelect(selKelurahan, await fetchWilayah('kelurahan', selKecamatan.value));
        });

        selKelurahan.addEventListener('change', () => {
            document.getElementById('fieldKoordinatManual').value = '0';
            setHiddenFromSelect(selKelurahan, 'fieldKelurahan');

            if (!selKelurahan.value) return;

            // Auto-center peta ke titik kelurahan/desa yang dipilih (paling presisi dari data wilayah)
            const { lat, lng } = selKelurahan.selectedOptions[0]?.dataset || {};
            if (lat && lng) {
                initMapIfNeeded();
                setMapPosition(parseFloat(lat), parseFloat(lng), 15);
            }
        });

        // ==================== Panel Filter: alamat & jumlah kegiatan ====================
        const filProvinsi  = document.getElementById('filterProvinsi');
        const filKabupaten = document.getElementById('filterKabupaten');
        const filKecamatan = document.getElementById('filterKecamatan');
        const filKelurahan = document.getElementById('filterKelurahan');

        let filterAktif = {
            provinsi: '', kabupaten: '', kecamatan: '', kelurahan: '',
            jumlahMode: '', jumlahMin: null, jumlahMax: null,
        };

        async function loadFilterProvinsiOptions() {
            fillSelect(filProvinsi, await fetchWilayah('provinsi'));
        }

        filProvinsi.addEventListener('change', async () => {
            resetSelect(filKabupaten, 'Semua Kabupaten/Kota');
            resetSelect(filKecamatan, 'Semua Kecamatan');
            resetSelect(filKelurahan, 'Semua Kelurahan/Desa');

            if (!filProvinsi.value) return;
            fillSelect(filKabupaten, await fetchWilayah('kabupaten', filProvinsi.value));
        });

        filKabupaten.addEventListener('change', async () => {
            resetSelect(filKecamatan, 'Semua Kecamatan');
            resetSelect(filKelurahan, 'Semua Kelurahan/Desa');

            if (!filKabupaten.value) return;
            fillSelect(filKecamatan, await fetchWilayah('kecamatan', filKabupaten.value));
        });

        filKecamatan.addEventListener('change', async () => {
            resetSelect(filKelurahan, 'Semua Kelurahan/Desa');

            if (!filKecamatan.value) return;
            fillSelect(filKelurahan, await fetchWilayah('kelurahan', filKecamatan.value));
        });

        document.getElementById('filterJumlahPreset').addEventListener('change', (e) => {
            document.getElementById('filterJumlahCustomRow').classList.toggle('d-none', e.target.value !== 'custom');
        });

        function perbaruiBadgeFilter() {
            let jumlahAktif = 0;
            ['provinsi', 'kabupaten', 'kecamatan', 'kelurahan'].forEach(k => { if (filterAktif[k]) jumlahAktif++; });
            if (filterAktif.jumlahMode) jumlahAktif++;

            const badge = document.getElementById('filterBadge');
            if (jumlahAktif > 0) {
                badge.textContent = jumlahAktif;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }

        document.getElementById('btnTerapkanFilterLokasi').addEventListener('click', () => {
            filterAktif.provinsi  = filProvinsi.selectedOptions[0]?.dataset.nama || '';
            filterAktif.kabupaten = filKabupaten.selectedOptions[0]?.dataset.nama || '';
            filterAktif.kecamatan = filKecamatan.selectedOptions[0]?.dataset.nama || '';
            filterAktif.kelurahan = filKelurahan.selectedOptions[0]?.dataset.nama || '';

            const preset = document.getElementById('filterJumlahPreset').value;
            if (preset === 'custom') {
                const minVal = document.getElementById('filterJumlahMin').value;
                const maxVal = document.getElementById('filterJumlahMax').value;
                filterAktif.jumlahMode = 'custom';
                filterAktif.jumlahMin  = minVal !== '' ? parseInt(minVal, 10) : null;
                filterAktif.jumlahMax  = maxVal !== '' ? parseInt(maxVal, 10) : null;
            } else {
                filterAktif.jumlahMode = preset;
                filterAktif.jumlahMin  = null;
                filterAktif.jumlahMax  = null;
            }

            perbaruiBadgeFilter();
            currentPage = 1;
            renderTabel();
            bootstrap.Dropdown.getOrCreateInstance(document.getElementById('btnFilterLokasi')).hide();
        });

        document.getElementById('btnResetFilterLokasi').addEventListener('click', () => {
            filProvinsi.value = '';
            resetSelect(filKabupaten, 'Semua Kabupaten/Kota');
            resetSelect(filKecamatan, 'Semua Kecamatan');
            resetSelect(filKelurahan, 'Semua Kelurahan/Desa');
            document.getElementById('filterJumlahPreset').value = '';
            document.getElementById('filterJumlahCustomRow').classList.add('d-none');
            document.getElementById('filterJumlahMin').value = '';
            document.getElementById('filterJumlahMax').value = '';

            filterAktif = {
                provinsi: '', kabupaten: '', kecamatan: '', kelurahan: '',
                jumlahMode: '', jumlahMin: null, jumlahMax: null,
            };

            perbaruiBadgeFilter();
            currentPage = 1;
            renderTabel();
        });

        function tampilkanPilihWilayah() {
            document.getElementById('wilayahSaatIni').classList.add('d-none');
            document.getElementById('wilayahDropdownGroup').classList.remove('d-none');
        }

        // ==================== Buka modal: Tambah ====================
        function openCreateModal() {
            mode = 'create';
            document.getElementById('formLokasi').reset();
            document.getElementById('lokasiId').value = '';
            document.getElementById('modalLokasiTitle').textContent = 'Tambah Lokasi';
            document.getElementById('negara').value = 'Indonesia';

            ['fieldProvinsi', 'fieldKabupaten', 'fieldKecamatan', 'fieldKelurahan', 'fieldLatitude', 'fieldLongitude']
                .forEach(id => document.getElementById(id).value = '');
            document.getElementById('fieldKoordinatManual').value = '0';

            document.getElementById('wilayahSaatIni').classList.add('d-none');
            document.getElementById('wilayahDropdownGroup').classList.remove('d-none');
            resetSelect(selProvinsi, '-- Pilih Provinsi --');
            resetSelect(selKabupaten, '-- Pilih Kabupaten/Kota --');
            resetSelect(selKecamatan, '-- Pilih Kecamatan --');
            resetSelect(selKelurahan, '-- Pilih Kelurahan/Desa --');
            loadProvinsiOptions().then(() => selProvinsi.disabled = false);

            targetLat = DEFAULT_LAT;
            targetLng = DEFAULT_LNG;

            modalLokasi.show();
        }

        // ==================== Buka modal: Edit ====================
        async function openEditModal(id, highlight = null) {
            const res  = await fetch(`${API_LOKASI}?action=detail&id=${id}`);
            const json = await res.json();

            if (!json.success) {
                showToast(json.message || 'Gagal memuat data lokasi.');
                return;
            }

            const d = json.data;
            mode = 'update';

            document.getElementById('modalLokasiTitle').textContent = 'Edit Lokasi';
            document.getElementById('lokasiId').value   = d.id;
            document.getElementById('namaLokasi').value = d.nama_lokasi;
            document.getElementById('namaJalan').value  = d.nama_jalan || '';
            document.getElementById('negara').value     = d.negara || 'Indonesia';

            document.getElementById('fieldProvinsi').value  = d.provinsi;
            document.getElementById('fieldKabupaten').value = d.kabupaten_kota;
            document.getElementById('fieldKecamatan').value = d.kecamatan || '';
            document.getElementById('fieldKelurahan').value = d.kelurahan || '';

            const ringkasan = [d.kelurahan, d.kecamatan, d.kabupaten_kota, d.provinsi]
                .filter(Boolean).join(', ');
            document.getElementById('wilayahSaatIniText').textContent = ringkasan;
            document.getElementById('wilayahSaatIni').classList.remove('d-none');
            document.getElementById('wilayahDropdownGroup').classList.add('d-none');
            loadProvinsiOptions(); // siap kalau nanti "Ganti Wilayah" diklik

            targetLat = parseFloat(d.latitude);
            targetLng = parseFloat(d.longitude);
            document.getElementById('fieldKoordinatManual').value = d.koordinat_manual ? '1' : '0';

            modalLokasi.show();
            if (highlight) highlightField(highlight);
        }

        // ==================== Highlight elemen (deep-link dari dashboard "Perlu Perhatian") ====================
        const HIGHLIGHT_TARGET = {
            koordinat: 'koordinatSectionWrap',
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
            }, 250);
        }

        // ==================== Submit form (dengan cek duplikat dulu) ====================
        let pendingFormData = null;
        const modalPeringatanDuplikat = new bootstrap.Modal(document.getElementById('modalPeringatanDuplikat'));

        document.getElementById('formLokasi').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('action', mode);

            const btn = document.getElementById('btnSimpanLokasi');
            btn.disabled = true;
            btn.textContent = 'Mengecek data...';

            try {
                const cekForm = new FormData();
                cekForm.append('nama_lokasi', document.getElementById('namaLokasi').value);
                cekForm.append('nama_jalan', document.getElementById('namaJalan').value);
                cekForm.append('provinsi', document.getElementById('fieldProvinsi').value);
                cekForm.append('kabupaten_kota', document.getElementById('fieldKabupaten').value);
                cekForm.append('kecamatan', document.getElementById('fieldKecamatan').value);
                cekForm.append('kelurahan', document.getElementById('fieldKelurahan').value);
                if (mode === 'update') {
                    cekForm.append('exclude_id', document.getElementById('lokasiId').value);
                }

                const cekRes  = await fetch(API_CEK_DUPLIKAT, { method: 'POST', body: cekForm });
                const cekJson = await cekRes.json();

                if (cekJson.duplikat) {
                    pendingFormData = formData; // simpan buat dipakai kalau admin pilih "Tetap Simpan"
                    tampilkanPeringatanDuplikat(cekJson.match);
                    return;
                }
            } catch (err) {
                console.error('Cek duplikat gagal:', err);
                // Kalau pengecekan gagal (misal koneksi), jangan blokir simpan — lanjut aja.
            }

            await simpanLokasiSebenarnya(formData);
        });

        function tampilkanPeringatanDuplikat(match) {
            document.getElementById('duplikatNamaLokasi').textContent = match.nama_lokasi;
            document.getElementById('duplikatNamaJalan').textContent = match.nama_jalan || '(tanpa nama jalan)';
            document.getElementById('duplikatWilayah').textContent = [
                match.kelurahan ? `Kel. ${match.kelurahan}` : null,
                match.kecamatan ? `Kec. ${match.kecamatan}` : null,
                match.kabupaten_kota,
                match.provinsi,
            ].filter(Boolean).join(', ');

            modalPeringatanDuplikat.show();

            const btn = document.getElementById('btnSimpanLokasi');
            btn.disabled = false;
            btn.textContent = 'Simpan';
        }

        document.getElementById('btnTetapSimpan').addEventListener('click', async () => {
            modalPeringatanDuplikat.hide();
            if (pendingFormData) {
                await simpanLokasiSebenarnya(pendingFormData);
                pendingFormData = null;
            }
        });

        async function simpanLokasiSebenarnya(formData) {
            const btn = document.getElementById('btnSimpanLokasi');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const res  = await fetch(API_LOKASI, { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    modalLokasi.hide();
                    showToast(json.message, 'success');
                    loadLokasiList();
                } else {
                    showToast(json.message || 'Gagal menyimpan data.');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi ke server.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Simpan';
            }
        }

        // ==================== Hapus lokasi ====================
        let idAkanDihapus = null;
        const modalKonfirmasiHapus = new bootstrap.Modal(document.getElementById('modalKonfirmasiHapus'));

        function hapusLokasi(id, nama) {
            idAkanDihapus = id;
            document.getElementById('namaLokasiDihapus').textContent = `"${nama}"`;
            modalKonfirmasiHapus.show();
        }

        document.getElementById('btnKonfirmasiHapus').addEventListener('click', async () => {
            if (!idAkanDihapus) return;

            const btn = document.getElementById('btnKonfirmasiHapus');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', idAkanDihapus);

            try {
                const res  = await fetch(API_LOKASI, { method: 'POST', body: formData });
                const json = await res.json();

                modalKonfirmasiHapus.hide();

                if (json.success) {
                    showToast(json.message, 'success');
                    loadLokasiList();
                } else {
                    showToast(json.message || 'Gagal menghapus lokasi.');
                }
            } catch (err) {
                modalKonfirmasiHapus.hide();
                showToast('Terjadi kesalahan koneksi ke server.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Ya, Hapus';
                idAkanDihapus = null;
            }
        });

        // ==================== Load, filter, sort & render tabel ====================
        const PAGE_SIZE = 15;
        let semuaLokasi = [];   // data mentah dari server
        let currentPage = 1;
        let sortAsc = true;

        function formatAlamat(l) {
            return [
                l.kelurahan ? `Kel. ${l.kelurahan}` : null,
                l.kecamatan ? `Kec. ${l.kecamatan}` : null,
                l.kabupaten_kota,
            ].filter(Boolean).join(', ');
        }

        // ==================== Preview Lokasi (read-only, klik baris tabel) ====================
        const modalPreviewLokasiEl = document.getElementById('modalPreviewLokasi');
        const modalPreviewLokasi   = new bootstrap.Modal(modalPreviewLokasiEl);
        let mapPreview, markerPreview;

        function initPreviewMapIfNeeded() {
            if (mapPreview) return;
            mapPreview = L.map('mapPreview', { scrollWheelZoom: false, dragging: false, zoomControl: true, doubleClickZoom: false })
                .setView([DEFAULT_LAT, DEFAULT_LNG], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(mapPreview);
            markerPreview = L.marker([DEFAULT_LAT, DEFAULT_LNG]).addTo(mapPreview);
        }

        function bukaPreviewLokasi(id) {
            const l = semuaLokasi.find(x => x.id == id);
            if (!l) return;

            document.getElementById('previewNamaLokasi').textContent = l.nama_lokasi;

            const alamatLengkap = [
                l.nama_jalan,
                l.kelurahan ? `Kel. ${l.kelurahan}` : null,
                l.kecamatan ? `Kec. ${l.kecamatan}` : null,
                l.kabupaten_kota,
                l.provinsi,
                l.negara,
            ].filter(Boolean).join(', ');
            document.getElementById('previewAlamat').textContent = alamatLengkap || '-';

            const lat = parseFloat(l.latitude);
            const lng = parseFloat(l.longitude);
            const koordinatValid = Number.isFinite(lat) && Number.isFinite(lng);
            document.getElementById('previewKoordinat').textContent = koordinatValid
                ? `${lat.toFixed(7)}, ${lng.toFixed(7)}`
                : '-';
            document.getElementById('previewJumlahKegiatan').textContent = `${l.jumlah_kegiatan} Kegiatan`;

            document.getElementById('btnEditDariPreview').onclick = () => {
                modalPreviewLokasi.hide();
                openEditModal(l.id);
            };

            modalPreviewLokasiEl.addEventListener('shown.bs.modal', function onShown() {
                initPreviewMapIfNeeded();
                mapPreview.invalidateSize();
                if (koordinatValid) {
                    mapPreview.setView([lat, lng], 14);
                    markerPreview.setLatLng([lat, lng]);
                }
                modalPreviewLokasiEl.removeEventListener('shown.bs.modal', onShown);
            });

            modalPreviewLokasi.show();
        }

        async function loadLokasiList() {
            const res  = await fetch(`${API_LOKASI}?action=list`);
            const json = await res.json();

            semuaLokasi = json.success ? json.data : [];
            currentPage = 1;
            renderTabel();
        }

        function cocokJumlahKegiatan(jumlah) {
            if (!filterAktif.jumlahMode) return true;

            if (filterAktif.jumlahMode === 'custom') {
                if (filterAktif.jumlahMin !== null && jumlah < filterAktif.jumlahMin) return false;
                if (filterAktif.jumlahMax !== null && jumlah > filterAktif.jumlahMax) return false;
                return true;
            }

            if (filterAktif.jumlahMode === '0')    return jumlah === 0;
            if (filterAktif.jumlahMode === '1-5')  return jumlah >= 1 && jumlah <= 5;
            if (filterAktif.jumlahMode === '6-10') return jumlah >= 6 && jumlah <= 10;
            if (filterAktif.jumlahMode === '10+')  return jumlah > 10;

            return true;
        }

        function getDataTampil() {
            const keyword = document.getElementById('inputCari').value.trim().toLowerCase();

            let data = semuaLokasi.filter(l => {
                if (keyword) {
                    const cocokKeyword = l.nama_lokasi.toLowerCase().includes(keyword) ||
                                         formatAlamat(l).toLowerCase().includes(keyword);
                    if (!cocokKeyword) return false;
                }

                if (filterAktif.provinsi  && l.provinsi !== filterAktif.provinsi) return false;
                if (filterAktif.kabupaten && l.kabupaten_kota !== filterAktif.kabupaten) return false;
                if (filterAktif.kecamatan && l.kecamatan !== filterAktif.kecamatan) return false;
                if (filterAktif.kelurahan && l.kelurahan !== filterAktif.kelurahan) return false;

                if (!cocokJumlahKegiatan(Number(l.jumlah_kegiatan))) return false;

                return true;
            });

            data.sort((a, b) => sortAsc
                ? a.nama_lokasi.localeCompare(b.nama_lokasi)
                : b.nama_lokasi.localeCompare(a.nama_lokasi));

            return data;
        }

        function renderTabel() {
            const tbody = document.getElementById('tabelLokasi');
            const data  = getDataTampil();

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Belum ada data lokasi.</td></tr>';
                document.getElementById('paginationRow').innerHTML = '';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;

            const start   = (currentPage - 1) * PAGE_SIZE;
            const halaman = data.slice(start, start + PAGE_SIZE);

            tbody.innerHTML = halaman.map(l => {
                const namaEscaped = l.nama_lokasi.replace(/'/g, "\\'");

                return `
                    <tr class="baris-lokasi" style="cursor:pointer" onclick="bukaPreviewLokasi(${l.id})">
                        <td class="fw-semibold">${l.nama_lokasi}</td>
                        <td>${formatAlamat(l)}</td>
                        <td>${l.jumlah_kegiatan} Kegiatan</td>
                        <td class="td-aksi">
                            <button class="icon-btn" title="Edit" onclick="event.stopPropagation(); openEditModal(${l.id})">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="icon-btn icon-danger" title="Hapus" onclick="event.stopPropagation(); hapusLokasi(${l.id}, '${namaEscaped}')">
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

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="gotoPage(${currentPage - 1})">&lsaquo;</button>`;

            const tampilkanLangsung = 3; // halaman 1..3 selalu tampil kalau ada
            for (let p = 1; p <= Math.min(tampilkanLangsung, totalPages); p++) {
                html += `<button class="page-btn ${p === currentPage ? 'active' : ''}" onclick="gotoPage(${p})">${p}</button>`;
            }

            if (totalPages > tampilkanLangsung + 1) {
                html += `<span class="page-ellipsis">…</span>`;
            }

            if (totalPages > tampilkanLangsung) {
                html += `<button class="page-btn ${totalPages === currentPage ? 'active' : ''}" onclick="gotoPage(${totalPages})">${totalPages}</button>`;
            }

            html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="gotoPage(${currentPage + 1})">&rsaquo;</button>`;

            container.innerHTML = html;
        }

        function gotoPage(p) {
            currentPage = p;
            renderTabel();
        }

        function toggleUrutkan() {
            sortAsc = !sortAsc;
            document.getElementById('labelUrutkan').textContent = sortAsc ? 'Urutkan (A-Z)' : 'Urutkan (Z-A)';
            currentPage = 1;
            renderTabel();
        }

        let searchDebounce;
        document.getElementById('inputCari').addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                currentPage = 1;
                renderTabel();
            }, 250);
        });

        loadLokasiList();
        loadFilterProvinsiOptions();

        // Deep-link dari dashboard:
        // - "Aksi Cepat" -> ?aksi=tambah -> langsung buka modal Tambah
        // - Panel "Perlu Perhatian" -> ?edit=<id> -> buka modal Edit + highlight titik koordinat
        const paramsUrlLokasi = new URLSearchParams(location.search);
        if (paramsUrlLokasi.get('aksi') === 'tambah') {
            openCreateModal();
        } else if (paramsUrlLokasi.get('edit')) {
            openEditModal(paramsUrlLokasi.get('edit'), 'koordinat');
        }
    </script>
</body>
</html>