<?php
/**
 * includes/navbar_user.php
 *
 * Topbar untuk semua halaman /user/* (Peta Kegiatan, Detail Kegiatan, dst).
 * Beda sama includes/navbar_admin.php yang buat sidebar admin -- ini cuma
 * topbar simpel buat halaman publik/staf di sisi user.
 *
 * WAJIB SEBELUM include file ini:
 *   session_start();
 *   require_once __DIR__ . '/../config/constants.php'; // buat labelRole()
 *
 * OPSIONAL -- kalau $sudahLogin belum di-set sebelum include, file ini
 * otomatis fallback ke isset($_SESSION['user_id']). Lebih baik tetap
 * di-set manual di halaman pemanggil kalau logikanya beda dari default itu.
 *
 * OPSIONAL (default false) -- set true kalau halaman pemanggil mau dropdown
 * staf-nya nampilin link "Panel Admin" juga (dulu cuma ada di detail_kegiatan.php):
 *   $tampilkanLinkPanelAdmin = true;
 *
 * Cara pakai:
 *   <?php require_once __DIR__ . '/../includes/navbar_user.php'; ?>
 */

$sudahLogin = $sudahLogin ?? isset($_SESSION['user_id']);
$isStaff = $sudahLogin && ($_SESSION['role'] ?? '') !== 'user';
$tampilkanLinkPanelAdmin = $tampilkanLinkPanelAdmin ?? false;
?>
<!-- ================= TOPBAR ================= -->
<div class="topbar">
    <div class="brand">
        <div class="title">
            SEAMEO BIOTROP
            <small>Activities in Spatial</small>
        </div>
    </div>
    <div class="actions">
        <?php if ($isStaff): ?>
            <div class="user-pill-wrapper" id="userPillWrapper">
                <button type="button" class="user-pill" id="userPillBtn">
                    <span class="user-avatar"><?= htmlspecialchars(strtoupper(mb_substr($_SESSION['nama_lengkap'], 0, 1))) ?></span>
                    <span><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                    <i class="bi bi-chevron-down user-pill-caret"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <?php if ($tampilkanLinkPanelAdmin): ?>
                        <a href="../admin/dashboard.php" class="user-dropdown-item">
                            <i class="bi bi-speedometer2"></i> Panel Admin
                        </a>
                    <?php endif; ?>
                    <a href="../admin/pengaturan_akun.php" class="user-dropdown-item">
                        <i class="bi bi-gear"></i> Pengaturan Akun
                    </a>
                </div>
            </div>
            <a href="../auth/logout.php" class="logout-btn-white" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        <?php elseif ($sudahLogin): ?>
            <span style="margin-right:16px;">
                Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                (<?= htmlspecialchars(labelRole($_SESSION['role'])) ?>)
            </span>
            <a href="../auth/logout.php" class="btn-login-outline">Logout</a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn-login-outline">Login untuk detail</a>
        <?php endif; ?>
    </div>
</div>
<script>
    (function () {
        var wrapper = document.getElementById('userPillWrapper');
        var btn = document.getElementById('userPillBtn');
        if (!wrapper || !btn) return;

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
    })();
</script>