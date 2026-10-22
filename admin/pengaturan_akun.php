<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/admin_check.php';

$activePage = 'pengaturan_akun';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/toast.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">

    <style>
        .akun-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            max-width: 980px;
        }

        @media (max-width: 900px) {
            .akun-grid { grid-template-columns: 1fr; }
        }

        .akun-card {
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 24px;
        }

        .akun-card h2 {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .akun-card .akun-card-desc {
            color: var(--admin-text-muted);
            font-size: .85rem;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: .88rem;
            margin-bottom: 6px;
        }

        .form-control-custom {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            font-size: .9rem;
            font-family: inherit;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--admin-green);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
        }

        .form-control-custom[readonly] {
            background: #f7f8fa;
            color: var(--admin-text-muted);
            cursor: not-allowed;
        }

        .password-wrap { position: relative; }

        .password-wrap .toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--admin-text-muted);
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-simpan {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--admin-green);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: .9rem;
            cursor: pointer;
        }

        .btn-simpan:hover { background: #128a3e; }
        .btn-simpan:disabled { opacity: .65; cursor: not-allowed; }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--admin-green-bg);
            color: var(--admin-green);
            font-size: .78rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            text-transform: capitalize;
        }
    </style>
</head>
<body class="admin-body">
    <!-- Toast notifikasi -->
    <div class="toast-container" id="toastContainer"></div>

    <?php require_once __DIR__ . '/../includes/navbar_admin.php'; ?>

        <h1 class="page-title">Pengaturan Akun</h1>
        <p class="page-subtitle">Kelola informasi profil dan keamanan akun Anda</p>

        <div class="akun-grid">
            <!-- Card: Informasi Profil -->
            <div class="akun-card">
                <h2>Informasi Profil</h2>
                <p class="akun-card-desc">Nama dan username ini akan tampil di seluruh halaman admin.</p>

                <form id="formProfil">
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control-custom"
                               value="<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control-custom"
                               value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <div><span class="role-badge"><?= htmlspecialchars(labelRole($_SESSION['role'] ?? '')) ?></span></div>
                    </div>

                    <button type="submit" class="btn-simpan" id="btnSimpanProfil">Simpan Perubahan</button>
                </form>
            </div>

            <!-- Card: Ubah Password -->
            <div class="akun-card">
                <h2>Ubah Password</h2>
                <p class="akun-card-desc">Gunakan password baru minimal 8 karakter yang belum pernah dipakai sebelumnya.</p>

                <form id="formPassword">
                    <div class="form-group">
                        <label for="password_lama">Password Lama</label>
                        <div class="password-wrap">
                            <input type="password" id="password_lama" name="password_lama" class="form-control-custom" required>
                            <button type="button" class="toggle-eye" data-target="password_lama" aria-label="Tampilkan password">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_baru">Password Baru</label>
                        <div class="password-wrap">
                            <input type="password" id="password_baru" name="password_baru" class="form-control-custom" minlength="8" required>
                            <button type="button" class="toggle-eye" data-target="password_baru" aria-label="Tampilkan password">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="konfirmasi_password_baru">Konfirmasi Password Baru</label>
                        <div class="password-wrap">
                            <input type="password" id="konfirmasi_password_baru" name="konfirmasi_password_baru" class="form-control-custom" minlength="8" required>
                            <button type="button" class="toggle-eye" data-target="konfirmasi_password_baru" aria-label="Tampilkan password">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-simpan" id="btnSimpanPassword">Ubah Password</button>
                </form>
            </div>
        </div>

    <!-- ==================== Modal Konfirmasi (dipakai bareng untuk kedua form) ==================== -->
    <div class="modal fade" id="modalKonfirmasiAkun" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <!-- Tahap 1: pertanyaan konfirmasi -->
                <div id="konfirmasiStepTanya">
                    <div class="modal-body text-center pt-4 pb-2">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                             style="width:56px;height:56px;border-radius:50%;background:#dbeafe;color:#1d4ed8;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 16v-4M12 8h.01"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </div>
                        <h5 class="mb-2" id="modalKonfirmasiAkunTitle">Apakah kamu yakin?</h5>
                        <p class="text-muted mb-0" id="modalKonfirmasiAkunMessage"></p>
                    </div>
                    <div class="modal-footer border-0 justify-content-center pb-4">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary px-4" id="btnKonfirmasiAkunOk">Ya, Lanjutkan</button>
                    </div>
                </div>

                <!-- Tahap 2: hasil (sukses / gagal), muncul gantiin tahap 1 tanpa modal ketutup -->
                <div id="konfirmasiStepHasil" style="display:none;">
                    <div class="modal-body text-center pt-4 pb-2">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" id="hasilIconWrap"
                             style="width:56px;height:56px;border-radius:50%;">
                            <svg id="hasilIconSukses" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M20 6L9 17l-5-5"></path>
                            </svg>
                            <svg id="hasilIconGagal" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <h5 class="mb-2" id="hasilTitle"></h5>
                        <p class="text-muted mb-0" id="hasilMessage"></p>
                    </div>
                    <div class="modal-footer border-0 justify-content-center pb-4">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_URL = <?= json_encode(BASE_URL) ?>;

        // Toggle show/hide password di masing-masing field
        document.querySelectorAll('.toggle-eye').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(btn.dataset.target);
                const showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                btn.setAttribute('aria-label', showing ? 'Sembunyikan password' : 'Tampilkan password');
            });
        });

        // ==================== Modal konfirmasi 2 tahap (tanya -> hasil) ====================
        const modalKonfirmasiEl = document.getElementById('modalKonfirmasiAkun');
        const modalKonfirmasi   = new bootstrap.Modal(modalKonfirmasiEl);
        const btnKonfirmasiOk   = document.getElementById('btnKonfirmasiAkunOk');
        const stepTanya         = document.getElementById('konfirmasiStepTanya');
        const stepHasil         = document.getElementById('konfirmasiStepHasil');

        // pendingRequest menyimpan fungsi yang HARUS mengembalikan Promise<{success, message}>
        let pendingRequest = null;

        // Dipakai buat tau apakah modal ditutup setelah hasil SUKSES,
        // supaya halaman bisa di-refresh otomatis
        let lastResultSuccess = false;

        function mintaKonfirmasi(title, message, requestFn) {
            document.getElementById('modalKonfirmasiAkunTitle').textContent = title;
            document.getElementById('modalKonfirmasiAkunMessage').textContent = message;
            pendingRequest = requestFn;
            lastResultSuccess = false;

            stepTanya.style.display = '';
            stepHasil.style.display = 'none';
            btnKonfirmasiOk.disabled = false;
            btnKonfirmasiOk.textContent = 'Ya, Lanjutkan';

            modalKonfirmasi.show();
        }

        function tampilkanHasil(success, message) {
            lastResultSuccess = success;

            document.getElementById('hasilTitle').textContent = success ? 'Berhasil!' : 'Gagal';
            document.getElementById('hasilMessage').textContent = message;

            const iconWrap = document.getElementById('hasilIconWrap');
            iconWrap.style.background = success ? '#dcfce7' : '#fee2e2';
            iconWrap.style.color = success ? '#16a34a' : '#b91c1c';
            document.getElementById('hasilIconSukses').style.display = success ? 'block' : 'none';
            document.getElementById('hasilIconGagal').style.display = success ? 'none' : 'block';

            stepTanya.style.display = 'none';
            stepHasil.style.display = '';
        }

        btnKonfirmasiOk.addEventListener('click', function () {
            if (typeof pendingRequest !== 'function') return;

            const requestFn = pendingRequest;
            pendingRequest = null;

            btnKonfirmasiOk.disabled = true;
            btnKonfirmasiOk.textContent = 'Memproses...';

            requestFn()
                .then(function (hasil) {
                    tampilkanHasil(hasil.success, hasil.message);
                })
                .catch(function (err) {
                    console.error('Gagal memproses permintaan:', err);
                    tampilkanHasil(false, 'Terjadi kesalahan tak terduga. Cek console (F12) untuk detail.');
                });
        });

        // Reset modal ke tahap pertanyaan lagi setiap kali modal ditutup,
        // KECUALI kalau ditutup setelah hasil sukses -> refresh halaman
        modalKonfirmasiEl.addEventListener('hidden.bs.modal', function () {
            if (lastResultSuccess) {
                window.location.reload();
                return;
            }

            stepTanya.style.display = '';
            stepHasil.style.display = 'none';
            btnKonfirmasiOk.disabled = false;
            btnKonfirmasiOk.textContent = 'Ya, Lanjutkan';
        });

        // ==================== Form: Informasi Profil ====================
        document.getElementById('formProfil').addEventListener('submit', function (e) {
            e.preventDefault();
            const formEl = this;

            mintaKonfirmasi(
                'Simpan Perubahan Profil?',
                'Nama lengkap dan username kamu akan diperbarui.',
                function () { return kirimFormProfil(formEl); }
            );
        });

        function kirimFormProfil(formEl) {
            const formData = new FormData(formEl);
            formData.append('action', 'update_profil');

            return fetch(BASE_URL + '/api/update_akun.php', { method: 'POST', body: formData })
                .then(function (res) {
                    if (!res.ok) {
                        console.error('Response HTTP tidak OK:', res.status, res.statusText);
                    }
                    return res.json();
                });
        }

        // ==================== Form: Ubah Password ====================
        document.getElementById('formPassword').addEventListener('submit', function (e) {
            e.preventDefault();
            const formEl = this;

            mintaKonfirmasi(
                'Ubah Password?',
                'Kamu akan keluar dari kebiasaan lama — pastikan password baru sudah kamu catat sebelum lanjut.',
                function () { return kirimFormPassword(formEl); }
            );
        });

        function kirimFormPassword(formEl) {
            const formData = new FormData(formEl);
            formData.append('action', 'update_password');

            return fetch(BASE_URL + '/api/update_akun.php', { method: 'POST', body: formData })
                .then(function (res) {
                    if (!res.ok) {
                        console.error('Response HTTP tidak OK:', res.status, res.statusText);
                    }
                    return res.json();
                })
                .then(function (data) {
                    if (data.success) {
                        formEl.reset();
                    }
                    return data;
                });
        }
    </script>
</body>
</html>