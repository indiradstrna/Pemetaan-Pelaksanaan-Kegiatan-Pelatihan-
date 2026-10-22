<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/verifikator_check.php';

$activePage = 'verifikasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kegiatan - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/toast.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/css/admin.css') ?>">

    <style>
        /* ---------- Tab Menunggu / Riwayat ---------- */
        .verif-tabs {
            display: flex;
            gap: 6px;
            border-bottom: 1px solid var(--admin-border);
            margin-bottom: 18px;
        }
        .verif-tab {
            border: none;
            background: none;
            padding: 10px 16px;
            font-weight: 600;
            font-size: .92rem;
            color: var(--admin-text-muted);
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        .verif-tab.active { color: var(--admin-green); border-bottom-color: var(--admin-green); }
        .verif-tab .count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: .75rem;
            margin-left: 6px;
        }

        /* ---------- Badge status verifikasi ---------- */
        .badge-verif {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-verif.pending  { background: #fef3e2; color: #d97706; }
        .badge-verif.approved { background: #dcfce7; color: #16a34a; }
        .badge-verif.rejected { background: #fee2e2; color: #b91c1c; }

        /* ---------- Badge Jenis pengajuan: kegiatan baru vs revisi ---------- */
        .badge-jenis {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-jenis.baru   { background: #e0f2fe; color: #0369a1; }
        .badge-jenis.revisi { background: #ede9fe; color: #6d28d9; }

        .judul-lokasi-cell .judul { font-weight: 600; }
        .judul-lokasi-cell .lokasi { color: var(--admin-text-muted); font-size: .82rem; }

        #tabelVerifikasi tr.row-klik { cursor: pointer; }
        #tabelVerifikasi tr.row-klik:hover { background: #f8f9fb; }
        #tabelVerifikasi tr.row-klik td.td-aksi { cursor: default; }

        /* ---------- Modal Preview (read-only, sama persis gaya Kelola Kegiatan) ---------- */
        .preview-label {
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--admin-text-muted);
            margin-bottom: 4px;
        }
        .preview-value { font-size: .95rem; margin-bottom: 16px; }
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

        .catatan-lama-box {
            background: #fef9e7;
            border: 1px solid #f5deb3;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: .9rem;
            color: #7c5800;
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
    </style>
</head>
<body class="admin-body">
    <?php require_once __DIR__ . '/../includes/navbar_admin.php'; ?>

        <h1 class="page-title">Verifikasi Kegiatan</h1>
        <p class="page-subtitle">Tinjau kegiatan yang diajukan Internal sebelum tayang di peta publik.</p>

        <div class="verif-tabs">
            <button type="button" class="verif-tab active" data-tab="pending" onclick="gantiTab('pending')">
                Menunggu Verifikasi <span class="count-pill" id="countPending">0</span>
            </button>
            <button type="button" class="verif-tab" data-tab="riwayat" onclick="gantiTab('riwayat')">
                Riwayat
            </button>
        </div>

        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr id="theadVerifikasi">
                        <th>Kode</th>
                        <th>Jenis</th>
                        <th>Judul &amp; Lokasi</th>
                        <th>Diajukan Oleh</th>
                        <th>Tanggal Kegiatan</th>
                        <th>Diajukan Pada</th>
                        <th class="th-aksi"></th>
                    </tr>
                </thead>
                <tbody id="tabelVerifikasi">
                    <tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

    <!-- ==================== Modal Detail & Keputusan ==================== -->
    <div class="modal fade" id="modalVerifikasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="vJudul">Detail Kegiatan</h5>
                        <small class="text-muted" id="vKode"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <span class="badge-verif" id="vStatusBadge"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="preview-label">Diajukan Oleh</div>
                            <div class="preview-value" id="vDiajukanOleh">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Diajukan Pada</div>
                            <div class="preview-value" id="vDiajukanPada">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Lokasi</div>
                            <div class="preview-value" id="vLokasi">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Tanggal Pelaksanaan</div>
                            <div class="preview-value" id="vTanggal">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Penyelenggara</div>
                            <div class="preview-value" id="vPenyelenggara">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Jumlah Peserta</div>
                            <div class="preview-value" id="vPeserta">-</div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Deskripsi</div>
                            <div class="preview-value" id="vDeskripsi">-</div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Klasifikasi / Tag</div>
                            <div class="preview-value"><div class="preview-tags" id="vTags">-</div></div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Departemen</div>
                            <div class="preview-value"><div class="preview-tags" id="vDept">-</div></div>
                        </div>
                        <div class="col-12">
                            <div class="preview-label">Dokumentasi Foto</div>
                            <div class="preview-foto-grid" id="vFotoGrid"></div>
                            <div class="text-muted small" id="vFotoKosong">Belum ada foto.</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Ditampilkan kalau statusnya masih pending -- form keputusan -->
                    <div id="vBlokKeputusan">
                        <label class="form-label">Catatan Verifikator <span class="text-muted small">(wajib diisi kalau menolak)</span></label>
                        <textarea class="form-control" id="vCatatan" rows="3" placeholder="Contoh: mohon lengkapi deskripsi kegiatan sebelum disetujui."></textarea>
                    </div>

                    <!-- Ditampilkan kalau sudah pernah diverifikasi -- riwayat, read-only -->
                    <div id="vBlokRiwayat" style="display:none;">
                        <div class="preview-label">Catatan Verifikator</div>
                        <div class="catatan-lama-box" id="vCatatanLama">-</div>
                    </div>
                </div>

                <div class="modal-footer" id="vFooterKeputusan">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger" id="btnTolak">Tolak</button>
                    <button type="button" class="btn btn-simpan" id="btnSetujui">Setujui</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Review Pengajuan Revisi ==================== -->
    <div class="modal fade" id="modalReviewRevisi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Pengajuan Revisi</h5>
                        <small class="text-muted" id="rvKode"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <span class="badge-verif" id="rvStatusBadge"></span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="preview-label">Diajukan Oleh</div>
                            <div class="preview-value" id="rvDiajukanOleh">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="preview-label">Diajukan Pada</div>
                            <div class="preview-value" id="rvDiajukanPada">-</div>
                        </div>
                    </div>

                    <p class="text-muted small">Perubahan yang diajukan:</p>
                    <div id="rvDiffList"></div>

                    <hr class="my-3">

                    <div id="rvBlokKeputusan">
                        <label class="form-label">Catatan Verifikator <span class="text-muted small">(Wajib diisi jika menolak)</span></label>
                        <textarea class="form-control" id="rvCatatan" rows="3" placeholder="Contoh: mohon lampirkan sumber data jumlah peserta yang baru."></textarea>
                    </div>

                    <div id="rvBlokRiwayat" style="display:none;">
                        <div class="preview-label">Catatan Verifikator</div>
                        <div class="catatan-lama-box" id="rvCatatanLama">-</div>
                    </div>
                </div>

                <div class="modal-footer" id="rvFooterKeputusan">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger" id="btnRvTolak">Tolak</button>
                    <button type="button" class="btn btn-simpan" id="btnRvSetujui">Setujui</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/toast.js"></script>
    <script>
        const API_URL    = '<?= BASE_URL ?>/api/crud_kegiatan.php';
        const API_REVISI = '<?= BASE_URL ?>/api/crud_kegiatan_revisi.php';

        let semuaData  = [];
        let tabAktif   = 'pending';
        let idDilihat  = null;

        const modalVerifikasi = new bootstrap.Modal(document.getElementById('modalVerifikasi'));
        const modalReviewRevisi = new bootstrap.Modal(document.getElementById('modalReviewRevisi'));

        const LABEL_VERIF = { pending: 'Menunggu Verifikasi', approved: 'Disetujui', rejected: 'Ditolak' };

        document.addEventListener('DOMContentLoaded', muatData);

        async function muatData() {
            try {
                const [resBaru, resRevisi] = await Promise.all([
                    fetch(`${API_URL}?action=list`),
                    fetch(`${API_REVISI}?action=list`),
                ]);
                const jsonBaru   = await resBaru.json();
                const jsonRevisi = await resRevisi.json();

                if (!jsonBaru.success) throw new Error(jsonBaru.message || 'Gagal memuat data kegiatan.');
                if (!jsonRevisi.success) throw new Error(jsonRevisi.message || 'Gagal memuat data revisi.');

                const dataBaru = jsonBaru.data.map(k => ({
                    id: k.id,
                    jenis: 'baru',
                    kode_kegiatan: k.kode_kegiatan,
                    judul: k.judul,
                    nama_lokasi: k.nama_lokasi,
                    kabupaten_kota: k.kabupaten_kota,
                    diajukan_oleh_nama: k.dibuat_oleh_nama,
                    tanggal_mulai: k.tanggal_mulai,
                    tanggal_selesai: k.tanggal_selesai,
                    created_at: k.created_at,
                    status_verifikasi: k.status_verifikasi,
                }));

                const dataRevisi = jsonRevisi.data.map(r => ({
                    id: r.id,
                    jenis: 'revisi',
                    kode_kegiatan: r.kode_kegiatan,
                    judul: r.judul,
                    nama_lokasi: r.nama_lokasi,
                    kabupaten_kota: r.kabupaten_kota,
                    diajukan_oleh_nama: r.diajukan_oleh_nama,
                    tanggal_mulai: r.tanggal_mulai,
                    tanggal_selesai: r.tanggal_selesai,
                    created_at: r.created_at,
                    status_verifikasi: r.status_verifikasi,
                }));

                semuaData = [...dataBaru, ...dataRevisi];
                renderTabel();
            } catch (err) {
                showToast(err.message || 'Gagal memuat data verifikasi.', 'error');
            }
        }

        function gantiTab(tab) {
            tabAktif = tab;
            document.querySelectorAll('.verif-tab').forEach(el => {
                el.classList.toggle('active', el.dataset.tab === tab);
            });
            renderTabel();
        }

        function getDataTampil() {
            return tabAktif === 'pending'
                ? semuaData.filter(k => k.status_verifikasi === 'pending')
                : semuaData.filter(k => k.status_verifikasi !== 'pending');
        }

        function formatTanggalRange(mulai, selesai) {
            const opt = { day: 'numeric', month: 'short', year: 'numeric' };
            const dMulai   = new Date(mulai).toLocaleDateString('id-ID', opt);
            const dSelesai = new Date(selesai).toLocaleDateString('id-ID', opt);
            return mulai === selesai ? dMulai : `${dMulai} - ${dSelesai}`;
        }

        function formatTanggalWaktu(tanggal) {
            if (!tanggal) return '-';
            const opt = { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(tanggal.replace(' ', 'T')).toLocaleString('id-ID', opt);
        }

        function badgeVerif(status) {
            return `<span class="badge-verif ${status}">${LABEL_VERIF[status] || status}</span>`;
        }

        function badgeJenis(jenis) {
            return jenis === 'revisi'
                ? '<span class="badge-jenis revisi">Pengajuan Revisi</span>'
                : '<span class="badge-jenis baru">Kegiatan Baru</span>';
        }

        function renderTabel() {
            const tbody = document.getElementById('tabelVerifikasi');
            const data  = getDataTampil();

            document.getElementById('countPending').textContent =
                semuaData.filter(k => k.status_verifikasi === 'pending').length;

            // Kolom terakhir beda isi tergantung tab: badge status di Riwayat, tombol di Menunggu.
            document.getElementById('theadVerifikasi').children[6].textContent =
                tabAktif === 'riwayat' ? 'Status' : '';

            if (data.length === 0) {
                const pesan = tabAktif === 'pending'
                    ? 'Tidak ada kegiatan yang menunggu verifikasi.'
                    : 'Belum ada riwayat verifikasi.';
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">${pesan}</td></tr>`;
                return;
            }

            tbody.innerHTML = data.map(k => `
                <tr class="row-klik" onclick="bukaModal(${k.id}, '${k.jenis}')">
                    <td class="text-muted small">${k.kode_kegiatan}</td>
                    <td>${badgeJenis(k.jenis)}</td>
                    <td class="judul-lokasi-cell">
                        <div class="judul">${k.judul}</div>
                        <div class="lokasi">${k.nama_lokasi}, ${k.kabupaten_kota}</div>
                    </td>
                    <td class="small">${k.diajukan_oleh_nama ?? '-'}</td>
                    <td class="small">${formatTanggalRange(k.tanggal_mulai, k.tanggal_selesai)}</td>
                    <td class="small">${formatTanggalWaktu(k.created_at)}</td>
                    <td class="td-aksi">${tabAktif === 'riwayat' ? badgeVerif(k.status_verifikasi) : ''}</td>
                </tr>
            `).join('');
        }

        function bukaModal(id, jenis) {
            if (jenis === 'revisi') {
                bukaModalRevisi(id);
            } else {
                bukaModalKegiatanBaru(id);
            }
        }

        async function bukaModalKegiatanBaru(id) {
            try {
                const res  = await fetch(`${API_URL}?action=detail&id=${id}`);
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Gagal memuat detail kegiatan.');

                const k = json.data;
                idDilihat = id;

                document.getElementById('vJudul').textContent = k.judul;
                document.getElementById('vKode').textContent  = k.kode_kegiatan;

                const statusBadge = document.getElementById('vStatusBadge');
                statusBadge.className   = `badge-verif ${k.status_verifikasi}`;
                statusBadge.textContent = LABEL_VERIF[k.status_verifikasi] || k.status_verifikasi;

                document.getElementById('vDiajukanOleh').textContent = k.dibuat_oleh_nama || 'Tidak diketahui';
                document.getElementById('vDiajukanPada').textContent = formatTanggalWaktu(k.created_at);

                document.getElementById('vLokasi').textContent       = `${k.nama_lokasi}, ${k.kabupaten_kota}`;
                document.getElementById('vTanggal').textContent      = formatTanggalRange(k.tanggal_mulai, k.tanggal_selesai);
                document.getElementById('vPenyelenggara').textContent = k.penyelenggara || '-';
                document.getElementById('vPeserta').textContent      = k.jumlah_peserta ?? '-';
                document.getElementById('vDeskripsi').textContent    = k.deskripsi || '-';

                const tagsEl = document.getElementById('vTags');
                tagsEl.innerHTML = (k.tags && k.tags.length)
                    ? k.tags.map(t => `<span class="preview-tag">${t.nama_tag}</span>`).join('')
                    : '-';

                const deptEl = document.getElementById('vDept');
                deptEl.innerHTML = (k.departemen && k.departemen.length)
                    ? k.departemen.map(d => `<span class="preview-tag">${d}</span>`).join('')
                    : '-';

                const fotoGrid   = document.getElementById('vFotoGrid');
                const fotoKosong = document.getElementById('vFotoKosong');
                if (k.foto && k.foto.length) {
                    fotoGrid.style.display = 'grid';
                    fotoKosong.style.display = 'none';
                    fotoGrid.innerHTML = k.foto.map(f => `
                        <a href="<?= BASE_URL ?>/${f.path_foto}" target="_blank" rel="noopener">
                            <img src="<?= BASE_URL ?>/${f.path_foto}" alt="Foto kegiatan">
                        </a>
                    `).join('');
                } else {
                    fotoGrid.style.display = 'none';
                    fotoKosong.style.display = 'block';
                }

                const sudahDiputuskan = k.status_verifikasi !== 'pending';
                document.getElementById('vBlokKeputusan').style.display = sudahDiputuskan ? 'none' : 'block';
                document.getElementById('vBlokRiwayat').style.display   = sudahDiputuskan ? 'block' : 'none';
                document.getElementById('vFooterKeputusan').style.display = sudahDiputuskan ? 'none' : 'flex';

                if (sudahDiputuskan) {
                    document.getElementById('vCatatanLama').textContent = k.catatan_verifikasi || 'Tidak ada catatan.';
                } else {
                    document.getElementById('vCatatan').value = '';
                }

                modalVerifikasi.show();
            } catch (err) {
                showToast(err.message || 'Gagal memuat detail kegiatan.', 'error');
            }
        }

        async function kirimKeputusan(keputusan) {
            const catatan = document.getElementById('vCatatan').value.trim();

            if (keputusan === 'reject' && catatan === '') {
                showToast('Catatan penolakan wajib diisi supaya Internal tahu apa yang perlu direvisi.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'verifikasi');
            formData.append('id', idDilihat);
            formData.append('keputusan', keputusan);
            formData.append('catatan', catatan);

            try {
                const res  = await fetch(API_URL, { method: 'POST', body: formData });
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Gagal menyimpan keputusan.');

                showToast(json.message, 'success');
                modalVerifikasi.hide();
                muatData();
            } catch (err) {
                showToast(err.message || 'Gagal menyimpan keputusan.', 'error');
            }
        }

        document.getElementById('btnSetujui').addEventListener('click', () => kirimKeputusan('approve'));
        document.getElementById('btnTolak').addEventListener('click', () => kirimKeputusan('reject'));

        // ==================== Modal Review Pengajuan Revisi ====================
        let revisiIdDilihat = null;

        function tampilkanNilaiRevisi(v) {
            if (v === null || v === undefined || v === '') return '<span class="text-muted">(kosong)</span>';
            if (Array.isArray(v)) return v.length ? v.join(', ') : '<span class="text-muted">(kosong)</span>';
            return String(v);
        }

        // Escape teks buat disisipkan ke dalam isi/atribut HTML lewat template string.
        function escAttr(teks) {
            return String(teks ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        async function bukaModalRevisi(id) {
            try {
                const res  = await fetch(`${API_REVISI}?action=detail&id=${id}`);
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Gagal memuat detail revisi.');

                const r = json.data;
                revisiIdDilihat = id;

                document.getElementById('rvKode').textContent = `${r.kode_kegiatan} — ${r.judul_kegiatan}`;

                const statusBadge = document.getElementById('rvStatusBadge');
                statusBadge.className   = `badge-verif ${r.status_verifikasi}`;
                statusBadge.textContent = LABEL_VERIF[r.status_verifikasi] || r.status_verifikasi;

                document.getElementById('rvDiajukanOleh').textContent = r.diajukan_oleh_nama || 'Tidak diketahui';
                document.getElementById('rvDiajukanPada').textContent = formatTanggalWaktu(r.created_at);

                const list = document.getElementById('rvDiffList');
                let html = '';

                const fieldKeys = Object.keys(r.fields || {});
                if (fieldKeys.length === 0) {
                    html += '<p class="text-muted small">Tidak ada perubahan data teks (hanya foto).</p>';
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

                const sudahDiputuskan = r.status_verifikasi !== 'pending';
                document.getElementById('rvBlokKeputusan').style.display   = sudahDiputuskan ? 'none' : 'block';
                document.getElementById('rvBlokRiwayat').style.display     = sudahDiputuskan ? 'block' : 'none';
                document.getElementById('rvFooterKeputusan').style.display = sudahDiputuskan ? 'none' : 'flex';

                if (sudahDiputuskan) {
                    document.getElementById('rvCatatanLama').textContent = r.catatan_verifikasi || 'Tidak ada catatan.';
                } else {
                    document.getElementById('rvCatatan').value = '';
                }

                modalReviewRevisi.show();
            } catch (err) {
                showToast(err.message || 'Gagal memuat detail revisi.', 'error');
            }
        }

        async function kirimKeputusanRevisi(keputusan) {
            const catatan = document.getElementById('rvCatatan').value.trim();

            if (keputusan === 'reject' && catatan === '') {
                showToast('Catatan penolakan wajib diisi supaya pengaju tahu apa yang perlu diperbaiki.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'verifikasi');
            formData.append('id', revisiIdDilihat);
            formData.append('keputusan', keputusan);
            formData.append('catatan', catatan);

            try {
                const res  = await fetch(API_REVISI, { method: 'POST', body: formData });
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Gagal menyimpan keputusan.');

                showToast(json.message, 'success');
                modalReviewRevisi.hide();
                muatData();
            } catch (err) {
                showToast(err.message || 'Gagal menyimpan keputusan.', 'error');
            }
        }

        document.getElementById('btnRvSetujui').addEventListener('click', () => kirimKeputusanRevisi('approve'));
        document.getElementById('btnRvTolak').addEventListener('click', () => kirimKeputusanRevisi('reject'));
    </script>
</body>
</html>