<?php
/**
 * includes/navbar_sekolah.php
 *
 * Sidebar + topbar untuk halaman portal sekolah (/user/lapor_capaian_monev.php, dst).
 * Cara pakai: set $activePage SEBELUM include file ini, contoh:
 *   $activePage = 'lapor_monev';
 *   require_once __DIR__ . '/../includes/navbar_sekolah.php';
 *
 * Nilai $activePage yang valid: dashboard_sekolah, lihat_peta_sekolah, lapor_monev
 *
 * PENTING: file ini membuka <div class="admin-main"><div class="admin-content">
 * yang HARUS ditutup manual di halaman pemanggil (2x </div>) sebelum </body>.
 */

$activePage = $activePage ?? '';

function navItemClass(string $page, string $activePage): string
{
    return $page === $activePage ? 'nav-item active' : 'nav-item';
}

$namaSekolah = $_SESSION['nama_lengkap'] ?? 'Sekolah';
$hurufAwal = strtoupper(mb_substr($namaSekolah, 0, 1));
?>
<script>
    // Terapkan preferensi ciutkan sidebar SEBELUM elemen sidebar sempat kelihatan,
    // biar gak ada "kedip" (expanded sekilas lalu collapse).
    if (localStorage.getItem('schoolSidebarCollapsed') === '1') {
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
    <div class="sidebar-subtitle">Portal Sekolah</div>

    <div class="sidebar-menu-label">Menu Utama</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/user/dashboard_sekolah.php" class="<?= navItemClass('dashboard_sekolah', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                <rect x="3" y="16" width="7" height="5" rx="1"></rect>
            </svg>
            <span class="nav-label">Dasbor</span>
        </a>
        <a href="<?= BASE_URL ?>/user/index.php" class="<?= navItemClass('lihat_peta_sekolah', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                <line x1="8" y1="2" x2="8" y2="18"></line>
                <line x1="16" y1="6" x2="16" y2="22"></line>
            </svg>
            <span class="nav-label">Lihat Peta</span>
        </a>
    </nav>

    <div class="sidebar-menu-label" style="margin-top: 24px;">Pelaporan M&E</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/user/lapor_capaian_monev.php" class="<?= navItemClass('lapor_monev', $activePage) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                <polyline points="13 2 13 9 20 9"></polyline>
                <polyline points="9 13 11 13"></polyline>
                <polyline points="9 17 11 17"></polyline>
                <polyline points="15 13 17 13"></polyline>
                <polyline points="15 17 17 17"></polyline>
            </svg>
            <span class="nav-label">Lapor Capaian M&E</span>
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
                </div>
                <div class="notif-dropdown-list" id="notifDropdownList">
                    <div class="notif-empty">Tidak ada notifikasi baru</div>
                </div>
            </div>
        </div>
        <div class="user-pill-wrapper" id="userPillWrapper">
            <button type="button" class="user-pill" id="userPillBtn">
                <span class="user-avatar"><?= htmlspecialchars($hurufAwal) ?></span>
                <span><?= htmlspecialchars($namaSekolah) ?></span>
                <svg class="user-pill-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <a href="<?= BASE_URL ?>/admin/pengaturan_akun.php" class="user-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="1"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                        <path d="M4.22 4.22l4.24 4.24m2.12 2.12l4.24 4.24"></path>
                        <path d="M1 12h6m6 0h6"></path>
                        <path d="M4.22 19.78l4.24-4.24m2.12-2.12l4.24-4.24"></path>
                    </svg>
                    Pengaturan Akun
                </a>
                <hr class="user-dropdown-divider">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="user-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('userPillWrapper');
            const btn = document.getElementById('userPillBtn');
            if (btn && wrapper) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    wrapper.classList.toggle('open');
                });
                document.addEventListener('click', function (e) {
                    if (!wrapper.contains(e.target)) wrapper.classList.remove('open');
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') wrapper.classList.remove('open');
                });
            }

            const toggleBtn = document.getElementById('sidebarToggleBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    const collapsed = document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('schoolSidebarCollapsed', collapsed ? '1' : '0');
                });
            }
        });
    </script>
    <div class="admin-content">
