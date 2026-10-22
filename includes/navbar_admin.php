<?php
/**
 * includes/navbar_admin.php
 *
 * Sidebar + topbar untuk semua halaman /admin/*.
 * Cara pakai: set $activePage SEBELUM include file ini, contoh:
 *   $activePage = 'kelola_lokasi';
 *   require_once __DIR__ . '/../includes/navbar_admin.php';
 *
 * Nilai $activePage yang valid: dashboard, kelola_lokasi, kelola_kegiatan,
 * kelola_user, lihat_peta, verifikasi
 *
 * PENTING: file ini membuka <div class="admin-main"><div class="admin-content">
 * yang HARUS ditutup manual di halaman pemanggil (2x </div>) sebelum </body>.
 */

$activePage = $activePage ?? '';

function navItemClass(string $page, string $activePage): string
{
    return $page === $activePage ? 'nav-item active' : 'nav-item';
}

$namaUser  = $_SESSION['nama_lengkap'] ?? 'Staf';
$hurufAwal = strtoupper(mb_substr($namaUser, 0, 1));
?>
<script>
    // Terapkan preferensi ciutkan sidebar SEBELUM elemen sidebar sempat kelihatan,
    // biar gak ada "kedip" (expanded sekilas lalu collapse).
    if (localStorage.getItem('adminSidebarCollapsed') === '1') {
        document.body.classList.add('sidebar-collapsed');
    }
</script>
<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-logos">
            <img src="<?= BASE_URL ?>/assets/img/logo-seameo.png" alt="SEAMEO">
            <img src="<?= BASE_URL ?>/assets/img/logo-biotrop.png" alt="BIOTROP">
        </div>
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Ciutkan/perluas sidebar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="3"></rect>
                <line x1="9" y1="3" x2="9" y2="21"></line>
            </svg>
        </button>
    </div>
    <div class="sidebar-subtitle">Activity Management</div>

    <div class="sidebar-menu-label">
        <?php
        $labelMenuSidebar = [
            'superadmin' => 'Menu Super Admin',
            'admin'      => 'Menu Verifikator',
        ];
        echo htmlspecialchars($labelMenuSidebar[$_SESSION['role'] ?? ''] ?? 'Menu Internal');
        ?>
    </div>
    <nav class="sidebar-nav">
        <?php if (in_array($_SESSION['role'] ?? '', ['internal', 'admin', 'superadmin'], true)): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= navItemClass('dashboard', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                <rect x="3" y="16" width="7" height="5" rx="1"></rect>
            </svg>
            <span class="nav-label">Dashboard</span>
        </a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['role'] ?? '', ['internal', 'superadmin'], true)): ?>
        <a href="<?= BASE_URL ?>/admin/kelola_lokasi.php" class="<?= navItemClass('kelola_lokasi', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span class="nav-label">Kelola Lokasi</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/kelola_kegiatan.php" class="<?= navItemClass('kelola_kegiatan', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span class="nav-label">Kelola Kegiatan</span>
        </a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)): ?>
        <a href="<?= BASE_URL ?>/admin/verifikasi.php" class="<?= navItemClass('verifikasi', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <span class="nav-label">Verifikasi</span>
        </a>
        <?php endif; ?>
        <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
            <a href="<?= BASE_URL ?>/admin/kelola_user.php" class="<?= navItemClass('kelola_user', $activePage) ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span class="nav-label">Manajemen User</span>
            </a>
        <?php endif; ?>
    </nav>

    <hr class="sidebar-divider">

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/lihat_peta.php" class="<?= navItemClass('lihat_peta', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                <line x1="8" y1="2" x2="8" y2="18"></line>
                <line x1="16" y1="6" x2="16" y2="22"></line>
            </svg>
            <span class="nav-label">Lihat Peta</span>
        </a>
    </nav>
</div>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="notif-wrapper" id="notifWrapper">
            <button type="button" class="notif-bell-btn" id="notifBellBtn" title="Notifikasi">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notif-count-badge" id="notifCountBadge" style="display:none;">0</span>
            </button>

            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">
                    <span>Notifikasi</span>
                    <button type="button" class="notif-mark-all-btn" id="notifMarkAllBtn">Tandai semua dibaca</button>
                </div>
                <div class="notif-dropdown-list" id="notifDropdownList">
                    <div class="notif-empty">Memuat notifikasi...</div>
                </div>
            </div>
        </div>
        <div class="user-pill-wrapper" id="userPillWrapper">
            <button type="button" class="user-pill" id="userPillBtn">
                <span class="user-avatar"><?= htmlspecialchars($hurufAwal) ?></span>
                <span><?= htmlspecialchars($namaUser) ?></span>
                <svg class="user-pill-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <div class="user-dropdown" id="userDropdown">
                <a href="<?= BASE_URL ?>/admin/pengaturan_akun.php" class="user-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    Pengaturan Akun
                </a>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-btn" title="Logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('userPillWrapper');
            const btn = document.getElementById('userPillBtn');

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                wrapper.classList.toggle('open');
            });

            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) {
                    wrapper.classList.remove('open');
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    wrapper.classList.remove('open');
                }
            });

            // Toggle ciutkan/perluas sidebar, preferensinya disimpan di localStorage
            // biar tetap kepilih walau pindah halaman (bukan SPA).
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            toggleBtn.addEventListener('click', function () {
                const collapsed = document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('adminSidebarCollapsed', collapsed ? '1' : '0');
            });

            // ---------------- Notifikasi ----------------
            const notifWrapper  = document.getElementById('notifWrapper');
            const notifBellBtn  = document.getElementById('notifBellBtn');
            const notifList     = document.getElementById('notifDropdownList');
            const notifBadge    = document.getElementById('notifCountBadge');
            const notifMarkAll  = document.getElementById('notifMarkAllBtn');
            const NOTIF_API     = '<?= BASE_URL ?>/api/notifikasi.php';

            // Link tujuan tiap tipe notifikasi -- dipetakan di sini (bukan disimpan
            // di DB) biar gampang diubah kalau nanti halamannya pindah.
            const NOTIF_LINK = {
                kegiatan_baru:        '<?= BASE_URL ?>/admin/verifikasi.php',
                keputusan_verifikasi: '<?= BASE_URL ?>/admin/kelola_kegiatan.php',
                revisi_baru:          '<?= BASE_URL ?>/admin/verifikasi.php',
                keputusan_revisi:     '<?= BASE_URL ?>/admin/kelola_kegiatan.php',
            };

            function waktuRelatif(tanggal) {
                const detik = Math.floor((Date.now() - new Date(tanggal.replace(' ', 'T'))) / 1000);
                if (detik < 60) return 'Baru saja';
                if (detik < 3600) return `${Math.floor(detik / 60)} menit lalu`;
                if (detik < 86400) return `${Math.floor(detik / 3600)} jam lalu`;
                return `${Math.floor(detik / 86400)} hari lalu`;
            }

            function renderNotifikasi(data) {
                const belumDibaca = data.filter(n => !n.dibaca).length;
                notifBadge.textContent = belumDibaca > 9 ? '9+' : belumDibaca;
                notifBadge.style.display = belumDibaca > 0 ? 'flex' : 'none';

                if (data.length === 0) {
                    notifList.innerHTML = '<div class="notif-empty">Belum ada notifikasi.</div>';
                    return;
                }

                notifList.innerHTML = data.map(n => `
                    <button type="button" class="notif-item ${n.dibaca ? '' : 'belum-dibaca'}" data-id="${n.id}" data-tipe="${n.tipe}">
                        <span class="notif-item-pesan">${n.pesan}</span>
                        <span class="notif-item-waktu">${waktuRelatif(n.created_at)}</span>
                    </button>
                `).join('');
            }

            async function muatNotifikasi() {
                try {
                    const res  = await fetch(`${NOTIF_API}?action=list`);
                    const json = await res.json();
                    if (json.success) renderNotifikasi(json.data);
                } catch (err) {
                    notifList.innerHTML = '<div class="notif-empty">Gagal memuat notifikasi.</div>';
                }
            }

            notifBellBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                notifWrapper.classList.toggle('open');
            });

            document.addEventListener('click', function (e) {
                if (!notifWrapper.contains(e.target)) {
                    notifWrapper.classList.remove('open');
                }
            });

            notifList.addEventListener('click', async function (e) {
                const item = e.target.closest('.notif-item');
                if (!item) return;

                const id   = item.dataset.id;
                const tipe = item.dataset.tipe;

                try {
                    await fetch(NOTIF_API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=tandai_dibaca&id=${encodeURIComponent(id)}`,
                    });
                } catch (err) {
                    // Gagal tandai dibaca gak perlu blokir navigasi, lanjut aja.
                }

                window.location.href = NOTIF_LINK[tipe] || '<?= BASE_URL ?>/admin/dashboard.php';
            });

            notifMarkAll.addEventListener('click', async function () {
                try {
                    await fetch(NOTIF_API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=tandai_semua_dibaca',
                    });
                    muatNotifikasi();
                } catch (err) {
                    // biarin, gak fatal
                }
            });

            muatNotifikasi();
        });
    </script>
<div class="admin-content">