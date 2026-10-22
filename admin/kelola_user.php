<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/superadmin_check.php';

$activePage = 'kelola_user';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/toast.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">

    <style>
        .modal-dialog-scrollable .modal-body {
            overflow-y: auto;
            max-height: 70vh;
        }

        /* ---------- Kontrol atas tabel ---------- */
        .user-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-tambah {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--admin-green);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            font-size: .92rem;
            cursor: pointer;
        }
        .btn-tambah:hover { background: #128a3e; color: #fff; }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-box { position: relative; }
        .search-box svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--admin-text-muted);
        }
        .search-box input {
            padding: 9px 14px 9px 36px;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            font-size: .9rem;
            width: 220px;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--admin-green);
        }

        .filter-select {
            padding: 9px 12px;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            font-size: .88rem;
            color: var(--admin-text);
            background: #fff;
        }
        .filter-select:focus { outline: none; border-color: var(--admin-green); }

        /* ---------- Tabel ---------- */
        .user-card {
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .user-table { width: 100%; border-collapse: collapse; }
        .user-table thead th {
            background: #f9fafb;
            text-align: left;
            font-size: .82rem;
            font-weight: 600;
            color: var(--admin-text-muted);
            padding: 14px 20px;
            border-bottom: 1px solid var(--admin-border);
        }
        .user-table tbody td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--admin-border);
            font-size: .9rem;
            vertical-align: middle;
        }
        .user-table tbody tr:last-child td { border-bottom: none; }
        .user-table tbody tr:hover { background: #fafafa; }

        .th-aksi, .td-aksi {
            white-space: nowrap;
            padding-left: 14px !important;
            padding-right: 14px !important;
            width: 1%;
        }

        .nama-cell-sub {
            display: block;
            font-size: .8rem;
            color: var(--admin-text-muted);
        }

        .badge-role {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        /* Palet pill solid (bg warna + teks kontras), sesuai referensi: superadmin hitam,
           internal hijau tua, admin (verifikator) biru, user kuning. */
        .badge-role.role-superadmin { background: #111827; color: #ffffff; }
        .badge-role.role-admin      { background: #1d4ed8; color: #ffffff; }
        .badge-role.role-internal   { background: #166534; color: #ffffff; }
        .badge-role.role-user  { background: #eab308; color: #1f2937; }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
        }
        .badge-status.status-aktif { background: var(--admin-green-bg); color: var(--admin-green); }
        .badge-status.status-nonaktif { background: #fee2e2; color: #b91c1c; }

        .badge-anda {
            display: inline-block;
            margin-left: 6px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            background: #f3f4f6;
            color: var(--admin-text-muted);
        }

        .icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--admin-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .icon-btn:hover { background: #f3f4f6; color: var(--admin-text); }
        .icon-btn.icon-danger:hover { background: #fee2e2; color: #b91c1c; }
        .icon-btn:disabled {
            opacity: .35;
            cursor: not-allowed;
            background: transparent !important;
            color: var(--admin-text-muted) !important;
        }

        /* ---------- Pagination ---------- */
        .pagination-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 18px 0 4px;
        }
        .page-btn {
            min-width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--admin-border);
            background: #fff;
            color: var(--admin-text);
            font-size: .88rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .page-btn:hover:not(:disabled) { background: #f7f8fa; }
        .page-btn:disabled { opacity: .4; cursor: default; }
        .page-btn.active {
            background: var(--admin-green);
            border-color: var(--admin-green);
            color: #fff;
            font-weight: 700;
        }
        .page-ellipsis {
            width: 34px;
            text-align: center;
            color: var(--admin-text-muted);
        }

        .btn-simpan {
            background-color: var(--admin-green);
            border-color: var(--admin-green);
            color: #fff;
        }
        .btn-simpan:hover {
            background-color: #128a3e;
            border-color: #128a3e;
            color: #fff;
        }
        .btn-simpan:disabled {
            background-color: var(--admin-green);
            border-color: var(--admin-green);
            opacity: .65;
        }

        /* ---------- Pilihan metode password ---------- */
        .password-mode-group {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        .password-mode-option {
            flex: 1;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            font-size: .88rem;
            font-weight: 600;
            text-align: center;
            color: var(--admin-text-muted);
        }
        .password-mode-option input { display: none; }
        .password-mode-option.active {
            border-color: var(--admin-green);
            background: var(--admin-green-bg);
            color: var(--admin-green);
        }

        .password-wrap { position: relative; }
        .password-wrap .toggle-eye {
            position: absolute;
            right: 10px;
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

        /* ---------- Tampilan hasil password (generate acak) ---------- */
        .password-hasil-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #f9fafb;
            border: 1px dashed var(--admin-border);
            border-radius: 8px;
            padding: 12px 14px;
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .5px;
        }
        .btn-copy {
            border: none;
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            padding: 6px 10px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
        }
        .btn-copy:hover { background: #f3f4f6; }

        /* ---------- Keterangan hak akses role (vertikal) ---------- */
        .role-hint-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
    </style>
</head>
<body class="admin-body">
    <!-- Toast notifikasi -->
    <div class="toast-container" id="toastContainer"></div>

    <?php require_once __DIR__ . '/../includes/navbar_admin.php'; ?>

        <h1 class="page-title">Kelola User</h1>
        <p class="page-subtitle">Menu kumpulan data serta pengelolaan akun admin dan user</p>

        <div class="user-toolbar">
            <button type="button" class="btn-tambah" onclick="openCreateModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Tambah user
            </button>

            <div class="toolbar-right">
                <select class="filter-select" id="filterRole">
                    <option value="">Semua Role</option>
                    <option value="superadmin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="internal">Internal</option>
                    <option value="user">User</option>
                </select>
                <select class="filter-select" id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
                <div class="search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="inputCari" placeholder="Cari nama / username">
                </div>
            </div>
        </div>

        <div class="user-card">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Terakhir Login</th>
                        <th class="th-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelUser">
                    <tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-row" id="paginationRow"></div>

    <!-- ==================== Modal Tambah/Edit User ==================== -->
    <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUserTitle">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formUser">
                    <div class="modal-body">
                        <input type="hidden" id="userId" name="id">

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" id="namaLengkap"
                                   placeholder="Contoh: Budi Santoso" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username"
                                   placeholder="Contoh: budi.santoso" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="role" required>
                                <option value="user">User</option>
                                <option value="internal">Internal</option>
                                <option value="admin">Admin</option>
                                <option value="superadmin">Super Admin</option>
                            </select>
                            <ul class="role-hint-list text-muted small mt-1 mb-0">
                                <li><strong>User</strong>: Hanya bisa lihat peta &amp; detail kegiatan</li>
                                <li><strong>Internal</strong>: Bikin kegiatan (masuk antrian verifikasi), kelola lokasi</li>
                                <li><strong>Admin</strong>: Verifikator -- approve/reject kegiatan yang diajukan Internal</li>
                                <li><strong>Super Admin</strong>: Akses penuh termasuk Kelola User</li>
                            </ul>
                        </div>

                        <!-- Field password hanya tampil saat mode TAMBAH -->
                        <div id="passwordSection">
                            <hr>
                            <label class="form-label d-block mb-2">Password Awal</label>
                            <div class="password-mode-group">
                                <label class="password-mode-option active" data-mode="random">
                                    <input type="radio" name="password_mode" value="random" checked> Buat Otomatis
                                </label>
                                <label class="password-mode-option" data-mode="manual">
                                    <input type="radio" name="password_mode" value="manual"> Isi Manual
                                </label>
                            </div>

                            <div id="passwordManualGroup" class="mb-1" style="display:none;">
                                <div class="password-wrap">
                                    <input type="password" class="form-control" name="password" id="passwordManual"
                                           placeholder="Minimal 8 karakter" minlength="8">
                                    <button type="button" class="toggle-eye" data-target="passwordManual" aria-label="Tampilkan password">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-muted small mb-0" id="passwordHint">Password akan dibuat otomatis dan ditampilkan sekali setelah user disimpan.</p>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-simpan" id="btnSimpanUser">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Reset Password ==================== -->
    <div class="modal fade" id="modalResetPassword" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formResetPassword">
                    <div class="modal-body">
                        <input type="hidden" id="resetUserId">
                        <p class="text-muted small mb-3">
                            Reset password untuk <strong id="resetNamaUser"></strong>.
                            User perlu login memakai password baru ini.
                        </p>

                        <div class="password-mode-group">
                            <label class="password-mode-option active" data-mode="random" data-group="reset">
                                <input type="radio" name="reset_password_mode" value="random" checked> Buat Otomatis
                            </label>
                            <label class="password-mode-option" data-mode="manual" data-group="reset">
                                <input type="radio" name="reset_password_mode" value="manual"> Isi Manual
                            </label>
                        </div>

                        <div id="resetPasswordManualGroup" class="mb-1" style="display:none;">
                            <div class="password-wrap">
                                <input type="password" class="form-control" id="resetPasswordManual"
                                       placeholder="Minimal 8 karakter" minlength="8">
                                <button type="button" class="toggle-eye" data-target="resetPasswordManual" aria-label="Tampilkan password">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-simpan" id="btnResetPassword">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Hasil Password (dibuat otomatis) ==================== -->
    <div class="modal fade" id="modalHasilPassword" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center pt-4 pb-2">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;border-radius:50%;background:#dcfce7;color:#16a34a;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                    <h5 class="mb-2" id="hasilPasswordTitle">Password Berhasil Dibuat</h5>
                    <p class="text-muted mb-3">
                        Catat password ini sekarang. Password tidak akan ditampilkan lagi setelah modal ini ditutup.
                    </p>
                    <div class="password-hasil-box">
                        <span id="hasilPasswordText"></span>
                        <button type="button" class="btn-copy" id="btnCopyPassword">Salin</button>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-simpan px-4" data-bs-dismiss="modal">Selesai</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Konfirmasi Ubah Status ==================== -->
    <div class="modal fade" id="modalKonfirmasiStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center pt-4 pb-2">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" id="statusIconWrap"
                         style="width:56px;height:56px;border-radius:50%;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h5 class="mb-2" id="statusModalTitle">Ubah Status User?</h5>
                    <p class="text-muted mb-0" id="statusModalMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning px-4" id="btnKonfirmasiStatus">Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Modal Sukses Akun (dipakai untuk: edit profil, reset password manual, ubah status, hapus) ==================== -->
    <div class="modal fade" id="modalSuksesAkun" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center pt-4 pb-2">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;border-radius:50%;background:#dcfce7;color:#16a34a;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                    <h5 class="mb-2" id="suksesAkunTitle">Berhasil</h5>
                    <p class="text-muted mb-0" id="suksesAkunMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-simpan px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
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
                    <h5 class="mb-2">Hapus User?</h5>
                    <p class="text-muted mb-0">
                        Yakin mau hapus user <strong id="namaUserDihapus"></strong>?
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

    <!-- ==================== Modal Pesan (error/info, pengganti toast) ==================== -->
    <div class="modal fade" id="modalPesan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center pt-4 pb-2">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" id="pesanIconWrap"
                         style="width:56px;height:56px;border-radius:50%;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h5 class="mb-2" id="pesanTitle">Gagal</h5>
                    <p class="text-muted mb-0" id="pesanMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/toast.js"></script>

    <script>
        // Pesan (error/success) ditampilkan lewat modal, bukan toast pojok layar —
        // supaya selalu kelihatan jelas dan tidak tergantung file toast.js.
        const modalPesan = new bootstrap.Modal(document.getElementById('modalPesan'));

        const ICON_CEKLIS = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>`;
        const ICON_PERINGATAN = `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;

        function tampilkanPesan(msg, type = 'error') {
            const iconWrap = document.getElementById('pesanIconWrap');
            const title     = document.getElementById('pesanTitle');

            if (type === 'success') {
                iconWrap.style.background = '#dcfce7';
                iconWrap.style.color = '#16a34a';
                iconWrap.innerHTML = ICON_CEKLIS; // samain sama icon sukses di modal lain (ceklis, bukan segitiga peringatan)
                title.textContent = 'Berhasil';
            } else {
                iconWrap.style.background = '#fee2e2';
                iconWrap.style.color = '#b91c1c';
                iconWrap.innerHTML = ICON_PERINGATAN;
                title.textContent = 'Gagal';
            }

            document.getElementById('pesanMessage').textContent = msg;
            modalPesan.show();
        }

        // Kalau modal notifikasi (sukses/gagal) langsung ditampilkan sementara modal lain
        // (form/konfirmasi) masih kebuka, dua modal jadi numpuk (backdrop dobel, tampilan
        // "ketahan"/kurang rapi). Makanya modal yang lama ditutup dulu sampai benar-benar
        // selesai (event hidden.bs.modal) baru modal barunya dimunculin.
        function tutupLaluTampilkan(modalYangDitutup, tampilkanCallback) {
            if (modalYangDitutup && modalYangDitutup.classList.contains('show')) {
                modalYangDitutup.addEventListener('hidden.bs.modal', tampilkanCallback, { once: true });
                bootstrap.Modal.getInstance(modalYangDitutup)?.hide();
            } else {
                tampilkanCallback();
            }
        }

        const API_USER        = '<?= BASE_URL ?>/api/crud_user.php';
        const CURRENT_USER_ID = <?= (int) ($_SESSION['user_id'] ?? 0) ?>;

        // ==================== Toggle mata password (tampil/sembunyi) ====================
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.toggle-eye');
            if (!btn) return;
            const input = document.getElementById(btn.dataset.target);
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
        });

        // ==================== Pilihan metode password (radio bergaya kartu) ====================
        function setupPasswordModeToggle(groupSelector, manualGroupId, hintId) {
            document.querySelectorAll(groupSelector).forEach(function (label) {
                label.addEventListener('click', function () {
                    document.querySelectorAll(groupSelector).forEach(l => l.classList.remove('active'));
                    label.classList.add('active');
                    label.querySelector('input').checked = true;

                    const isManual = label.dataset.mode === 'manual';
                    const manualGroup = document.getElementById(manualGroupId);
                    if (manualGroup) manualGroup.style.display = isManual ? '' : 'none';

                    const hint = hintId ? document.getElementById(hintId) : null;
                    if (hint) {
                        hint.textContent = isManual
                            ? 'Isi password sesuai keinginan, minimal 8 karakter.'
                            : 'Password akan dibuat otomatis dan ditampilkan sekali setelah user disimpan.';
                    }
                });
            });
        }
        setupPasswordModeToggle('#passwordSection .password-mode-option', 'passwordManualGroup', 'passwordHint');
        setupPasswordModeToggle('.password-mode-option[data-group="reset"]', 'resetPasswordManualGroup', null);

        // ==================== Modal Tambah/Edit User ====================
        const modalUser = new bootstrap.Modal(document.getElementById('modalUser'));
        const formUser  = document.getElementById('formUser');

        function resetFormUser() {
            formUser.reset();
            document.getElementById('userId').value = '';
            document.querySelectorAll('#passwordSection .password-mode-option').forEach(l => l.classList.remove('active'));
            document.querySelector('#passwordSection .password-mode-option[data-mode="random"]').classList.add('active');
            document.querySelector('#passwordSection input[value="random"]').checked = true;
            document.getElementById('passwordManualGroup').style.display = 'none';
            document.getElementById('passwordHint').textContent = 'Password akan dibuat otomatis dan ditampilkan sekali setelah user disimpan.';
        }

        function openCreateModal() {
            resetFormUser();
            document.getElementById('modalUserTitle').textContent = 'Tambah User';
            document.getElementById('passwordSection').style.display = '';
            document.getElementById('passwordManual').required = false;
            modalUser.show();
        }

        function openEditModal(id) {
            resetFormUser();
            document.getElementById('modalUserTitle').textContent = 'Edit User';
            document.getElementById('passwordSection').style.display = 'none';

            const u = semuaUser.find(x => x.id == id);
            if (!u) return;

            document.getElementById('userId').value = u.id;
            document.getElementById('namaLengkap').value = u.nama_lengkap;
            document.getElementById('username').value = u.username;
            document.getElementById('role').value = u.role;

            modalUser.show();
        }

        formUser.addEventListener('submit', async function (e) {
            e.preventDefault();

            const isEdit = document.getElementById('userId').value !== '';
            const mode = document.querySelector('#passwordSection input[name="password_mode"]:checked')?.value;

            if (!isEdit && mode === 'manual' && document.getElementById('passwordManual').value.length < 8) {
                tampilkanPesan('Password manual minimal 8 karakter.');
                return;
            }

            const formData = new FormData(formUser);
            formData.append('action', isEdit ? 'update' : 'create');

            const btn = document.getElementById('btnSimpanUser');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const res  = await fetch(API_USER, { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    loadUserList();
                    tutupLaluTampilkan(document.getElementById('modalUser'), () => {
                        if (json.generated_password) {
                            tampilkanHasilPassword(json.generated_password);
                        } else if (isEdit) {
                            tampilkanSuksesAkun('Data Akun Berhasil Diperbarui', json.message);
                        } else {
                            tampilkanPesan(json.message, 'success');
                        }
                    });
                } else {
                    tutupLaluTampilkan(document.getElementById('modalUser'), () => {
                        tampilkanPesan(json.message || 'Gagal menyimpan data.');
                    });
                }
            } catch (err) {
                tutupLaluTampilkan(document.getElementById('modalUser'), () => {
                    tampilkanPesan('Terjadi kesalahan koneksi ke server.');
                });
            } finally {
                btn.disabled = false;
                btn.textContent = 'Simpan';
            }
        });

        // ==================== Modal Reset Password ====================
        const modalResetPassword = new bootstrap.Modal(document.getElementById('modalResetPassword'));
        const formResetPassword  = document.getElementById('formResetPassword');

        function openResetPasswordModal(id, nama) {
            formResetPassword.reset();
            document.getElementById('resetUserId').value = id;
            document.getElementById('resetNamaUser').textContent = nama;

            document.querySelectorAll('.password-mode-option[data-group="reset"]').forEach(l => l.classList.remove('active'));
            document.querySelector('.password-mode-option[data-group="reset"][data-mode="random"]').classList.add('active');
            document.querySelector('input[name="reset_password_mode"][value="random"]').checked = true;
            document.getElementById('resetPasswordManualGroup').style.display = 'none';

            modalResetPassword.show();
        }

        formResetPassword.addEventListener('submit', async function (e) {
            e.preventDefault();

            const id   = document.getElementById('resetUserId').value;
            const mode = document.querySelector('input[name="reset_password_mode"]:checked').value;
            const passwordManual = document.getElementById('resetPasswordManual').value;

            if (mode === 'manual' && passwordManual.length < 8) {
                tampilkanPesan('Password manual minimal 8 karakter.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('id', id);
            formData.append('password_mode', mode);
            if (mode === 'manual') formData.append('password', passwordManual);

            const btn = document.getElementById('btnResetPassword');
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            try {
                const res  = await fetch(API_USER, { method: 'POST', body: formData });
                const json = await res.json();

                if (json.success) {
                    tutupLaluTampilkan(document.getElementById('modalResetPassword'), () => {
                        if (json.generated_password) {
                            tampilkanHasilPassword(json.generated_password);
                        } else {
                            tampilkanSuksesAkun('Password Akun Berhasil Diubah', json.message);
                        }
                    });
                } else {
                    tutupLaluTampilkan(document.getElementById('modalResetPassword'), () => {
                        tampilkanPesan(json.message || 'Gagal mereset password.');
                    });
                }
            } catch (err) {
                tutupLaluTampilkan(document.getElementById('modalResetPassword'), () => {
                    tampilkanPesan('Terjadi kesalahan koneksi ke server.');
                });
            } finally {
                btn.disabled = false;
                btn.textContent = 'Reset Password';
            }
        });

        // ==================== Modal hasil password (dibuat otomatis) ====================
        const modalHasilPassword = new bootstrap.Modal(document.getElementById('modalHasilPassword'));

        function tampilkanHasilPassword(password) {
            document.getElementById('hasilPasswordText').textContent = password;
            modalHasilPassword.show();
        }

        document.getElementById('btnCopyPassword').addEventListener('click', async function () {
            const password = document.getElementById('hasilPasswordText').textContent;
            try {
                await navigator.clipboard.writeText(password);
                tampilkanPesan('Password disalin ke clipboard.', 'success');
            } catch (err) {
                tampilkanPesan('Gagal menyalin, salin manual ya.');
            }
        });

        // ==================== Modal sukses akun (edit profil, reset password manual, ubah status, hapus) ====================
        const modalSuksesAkun = new bootstrap.Modal(document.getElementById('modalSuksesAkun'));

        function tampilkanSuksesAkun(title, message) {
            document.getElementById('suksesAkunTitle').textContent = title;
            document.getElementById('suksesAkunMessage').textContent = message;
            modalSuksesAkun.show();
        }

        // ==================== Ubah status (aktif/nonaktif) ====================
        const modalKonfirmasiStatus = new bootstrap.Modal(document.getElementById('modalKonfirmasiStatus'));
        let idStatusDiubah = null;

        function ubahStatus(id, nama, statusSaatIni) {
            idStatusDiubah = id;
            const akanNonaktif = statusSaatIni === 'aktif';

            document.getElementById('statusModalTitle').textContent = akanNonaktif ? 'Nonaktifkan User?' : 'Aktifkan User?';
            document.getElementById('statusModalMessage').innerHTML = akanNonaktif
                ? `<strong>${nama}</strong> tidak akan bisa login sampai diaktifkan kembali.`
                : `<strong>${nama}</strong> akan bisa login kembali seperti biasa.`;

            const iconWrap = document.getElementById('statusIconWrap');
            iconWrap.style.background = akanNonaktif ? '#fef3c7' : '#dcfce7';
            iconWrap.style.color = akanNonaktif ? '#b45309' : '#16a34a';

            modalKonfirmasiStatus.show();
        }

        document.getElementById('btnKonfirmasiStatus').addEventListener('click', async () => {
            if (!idStatusDiubah) return;

            const btn = document.getElementById('btnKonfirmasiStatus');
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', idStatusDiubah);

            try {
                const res  = await fetch(API_USER, { method: 'POST', body: formData });
                const json = await res.json();

                tutupLaluTampilkan(document.getElementById('modalKonfirmasiStatus'), () => {
                    if (json.success) {
                        tampilkanSuksesAkun(
                            json.status === 'aktif' ? 'Akun Berhasil Diaktifkan' : 'Akun Berhasil Dinonaktifkan',
                            json.message
                        );
                    } else {
                        tampilkanPesan(json.message || 'Gagal mengubah status user.');
                    }
                });
                if (json.success) loadUserList();
            } catch (err) {
                tutupLaluTampilkan(document.getElementById('modalKonfirmasiStatus'), () => {
                    tampilkanPesan('Terjadi kesalahan koneksi ke server.');
                });
            } finally {
                btn.disabled = false;
                btn.textContent = 'Ya, Lanjutkan';
                idStatusDiubah = null;
            }
        });

        // ==================== Hapus user ====================
        let idAkanDihapus = null;
        const modalKonfirmasiHapus = new bootstrap.Modal(document.getElementById('modalKonfirmasiHapus'));

        function hapusUser(id, nama) {
            idAkanDihapus = id;
            document.getElementById('namaUserDihapus').textContent = `"${nama}"`;
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
                const res  = await fetch(API_USER, { method: 'POST', body: formData });
                const json = await res.json();

                tutupLaluTampilkan(document.getElementById('modalKonfirmasiHapus'), () => {
                    if (json.success) {
                        tampilkanSuksesAkun('Akun Berhasil Dihapus', json.message);
                    } else {
                        tampilkanPesan(json.message || 'Gagal menghapus user.');
                    }
                });
                if (json.success) loadUserList();
            } catch (err) {
                tutupLaluTampilkan(document.getElementById('modalKonfirmasiHapus'), () => {
                    tampilkanPesan('Terjadi kesalahan koneksi ke server.');
                });
            } finally {
                btn.disabled = false;
                btn.textContent = 'Ya, Hapus';
                idAkanDihapus = null;
            }
        });

        // ==================== Load, filter, sort & render tabel ====================
        const PAGE_SIZE = 15;
        let semuaUser = [];   // data mentah dari server
        let currentPage = 1;

        function labelRole(role) {
            const labels = { user: 'User', internal: 'Internal', admin: 'Admin', superadmin: 'Super Admin' };
            return labels[role] || role;
        }

        function formatTanggal(tgl) {
            if (!tgl) return '-';
            const d = new Date(tgl.replace(' ', 'T'));
            if (isNaN(d)) return tgl;
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function formatTanggalJam(tgl) {
            if (!tgl) return 'Belum pernah login';
            const d = new Date(tgl.replace(' ', 'T'));
            if (isNaN(d)) return tgl;
            const tanggal = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            const jam = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return `${tanggal}, ${jam}`;
        }

        async function loadUserList() {
            const res  = await fetch(`${API_USER}?action=list`);
            const json = await res.json();

            semuaUser = json.success ? json.data : [];
            currentPage = 1;
            renderTabel();
        }

        function getDataTampil() {
            const keyword = document.getElementById('inputCari').value.trim().toLowerCase();
            const role    = document.getElementById('filterRole').value;
            const status  = document.getElementById('filterStatus').value;

            return semuaUser.filter(u => {
                if (role && u.role !== role) return false;
                if (status && u.status !== status) return false;
                if (!keyword) return true;
                return u.nama_lengkap.toLowerCase().includes(keyword) ||
                       u.username.toLowerCase().includes(keyword);
            });
        }

        function renderTabel() {
            const tbody = document.getElementById('tabelUser');
            const data  = getDataTampil();

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data user.</td></tr>';
                document.getElementById('paginationRow').innerHTML = '';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;

            const start   = (currentPage - 1) * PAGE_SIZE;
            const halaman = data.slice(start, start + PAGE_SIZE);

            tbody.innerHTML = halaman.map(u => {
                const namaEscaped = u.nama_lengkap.replace(/'/g, "\\'");
                const isSelf = u.id == CURRENT_USER_ID;
                const isAktif = u.status === 'aktif';
                const isSuperadmin = u.role === 'superadmin';

                return `
                    <tr>
                        <td>
                            <span class="fw-semibold">${u.nama_lengkap}</span>
                            ${isSelf ? '<span class="badge-anda">Anda</span>' : ''}
                            <span class="nama-cell-sub">@${u.username}</span>
                        </td>
                        <td><span class="badge-role role-${u.role}">${labelRole(u.role)}</span></td>
                        <td><span class="badge-status status-${u.status}">${u.status}</span></td>
                        <td>${formatTanggal(u.created_at)}</td>
                        <td>${formatTanggalJam(u.last_login)}</td>
                        <td class="td-aksi">
                            <button class="icon-btn" title="${isSuperadmin ? 'Akun Super Admin tidak bisa diedit dari sini' : 'Edit'}"
                                    ${isSuperadmin ? 'disabled' : ''}
                                    onclick="openEditModal(${u.id})">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="icon-btn" title="${isSuperadmin ? 'Password Super Admin tidak bisa direset dari sini' : 'Reset Password'}"
                                    ${isSuperadmin ? 'disabled' : ''}
                                    onclick="openResetPasswordModal(${u.id}, '${namaEscaped}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="7.5" cy="15.5" r="5.5"></circle>
                                    <path d="M21 2l-9.6 9.6"></path>
                                    <path d="M15.5 7.5l3 3L22 7l-3-3"></path>
                                </svg>
                            </button>
                            <button class="icon-btn" title="${isSuperadmin ? 'Status Super Admin tidak bisa diubah dari sini' : (isSelf ? 'Tidak bisa menonaktifkan akun sendiri' : (isAktif ? 'Nonaktifkan' : 'Aktifkan'))}"
                                    ${(isSuperadmin || isSelf) ? 'disabled' : ''}
                                    onclick="ubahStatus(${u.id}, '${namaEscaped}', '${u.status}')">
                                ${isAktif ? `
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="4.9" y1="4.9" x2="19.1" y2="19.1"></line>
                                    </svg>
                                ` : `
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5"></path>
                                    </svg>
                                `}
                            </button>
                            <button class="icon-btn icon-danger" title="${isSuperadmin ? 'Akun Super Admin tidak bisa dihapus dari sini' : (isSelf ? 'Tidak bisa menghapus akun sendiri' : 'Hapus')}"
                                    ${(isSuperadmin || isSelf) ? 'disabled' : ''}
                                    onclick="hapusUser(${u.id}, '${namaEscaped}')">
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

            const tampilkanLangsung = 3;
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

        let searchDebounce;
        document.getElementById('inputCari').addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                currentPage = 1;
                renderTabel();
            }, 250);
        });
        document.getElementById('filterRole').addEventListener('change', () => { currentPage = 1; renderTabel(); });
        document.getElementById('filterStatus').addEventListener('change', () => { currentPage = 1; renderTabel(); });

        loadUserList();

        // Deep-link dari "Aksi Cepat" di dashboard: ?aksi=tambah -> langsung buka modal Tambah
        if (new URLSearchParams(location.search).get('aksi') === 'tambah') {
            openCreateModal();
        }
    </script>
</body>
</html>