<?php
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Validasi parameter "next" (halaman tujuan setelah login berhasil) supaya
 * gak disalahgunakan buat open redirect ke domain luar -- harus path relatif
 * di dalam BASE_URL sendiri, bukan URL absolut ke situs lain.
 */
function nextUrlAman(string $next): ?string
{
    if ($next === '') return null;
    if (str_starts_with($next, '//')) return null; // protocol-relative -> bisa ke luar domain
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $next)) return null; // ada skema (http:, javascript:, dll)
    if (!str_starts_with($next, BASE_URL . '/')) return null; // wajib di dalam BASE_URL

    return $next;
}

$next = nextUrlAman($_GET['next'] ?? $_POST['next'] ?? '');

// Kalau sudah login, langsung redirect sesuai role (atau ke $next kalau ada)
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($next ?? urlBerandaUntukRole($_SESSION['role'])));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';
    $ingat_saya  = isset($_POST['ingat_saya']);

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, nama_lengkap, username, password, role, status
             FROM users
             WHERE username = :username
             LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Username atau password salah.';
        } elseif ($user['status'] !== 'aktif') {
            $error = 'Akun Anda nonaktif. Hubungi administrator.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Username atau password salah.';
        } else {
            // "Ingat saya" -> perpanjang umur cookie session jadi 30 hari.
            // Catatan: ini baru perpanjangan cookie session, bukan remember-token
            // terpisah. Cukup untuk MVP; bisa di-upgrade nanti kalau perlu.
            if ($ingat_saya) {
                $lifetime = 60 * 60 * 24 * 30; // 30 hari
                setcookie(session_name(), session_id(), time() + $lifetime, '/');
            }

            session_regenerate_id(true);

            // Catat waktu login terakhir -- dipakai di admin/kelola_user.php ("Terakhir Login").
            $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')->execute(['id' => $user['id']]);

            $_SESSION['user_id']      = $user['id'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['role']         = $user['role'];

            header('Location: ' . ($next ?? urlBerandaUntukRole($user['role'])));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal GIS - SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/logo-biotrop.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-start: #43a047;
            --green-end:   #1b5e20;
            --overlay-green-dark: rgba(9, 38, 18, .45);
            --overlay-green-light: rgba(67, 160, 71, .55);
            --text-dark:  #16181a;
            --text-gray:  #6b7280;
            --border-gray:#d8dbe0;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
        }

        .login-wrapper {
            height: 100vh;
            display: flex;
        }

        /* ---------- Panel kiri: foto + overlay ---------- */
        .login-visual {
            position: relative;
            flex: 1.5;
            min-width: 0;
            background-image:
                linear-gradient(180deg, var(--overlay-green-dark) 0%, var(--overlay-green-light) 100%),
                url('../assets/img/bg-campus.jpg');
            background-size: cover;
            background-position: center;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            text-align: left;
            padding: 48px 56px;
        }

        .login-visual .brand-lockup {
            position: absolute;
            left: 56px;
            bottom: 48px;
        }

        .login-visual .brand-lockup img {
            height: 46px;
        }

        .login-visual .visual-copy {
            max-width: 520px;
        }

        .login-visual .visual-copy h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.08;
            margin: 0 0 4px;
            text-shadow: 0 2px 10px rgba(0,0,0,.15);
        }

        .login-visual .visual-copy h1.sub {
            font-weight: 500;
        }

        .login-visual .visual-copy p {
            max-width: 480px;
            font-size: 1.05rem;
            margin: 18px 0 0;
            opacity: .95;
        }

        /* ---------- Panel kanan: form ---------- */
        .login-form-panel {
            flex: 1;
            min-width: 380px;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 32px 64px;
            background: #fff;
        }

        .login-form-panel .logos {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .login-form-panel .logos img {
            height: 56px;
        }

        .login-form-panel h2 {
            font-size: 1.9rem;
            font-weight: 800;
            margin: 0 0 8px;
        }

        .login-form-panel .lead {
            color: var(--text-gray);
            font-size: .95rem;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 14px; }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: .92rem;
            margin-bottom: 6px;
        }

        .form-control-custom {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            font-size: .95rem;
            font-family: inherit;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--green-end);
            box-shadow: 0 0 0 3px rgba(169, 118, 15, .15);
        }

        .password-wrap { position: relative; }

        .password-wrap .toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            font-size: .9rem;
        }

        .remember-row input[type="checkbox"] {
            accent-color: var(--green-end);
            width: 16px;
            height: 16px;
        }

        .btn-masuk {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 1.02rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(90deg, var(--green-start), var(--green-end));
            cursor: pointer;
        }

        .btn-masuk:hover { filter: brightness(.96); }

        .lupa-password {
            display: block;
            text-align: center;
            margin-top: 14px;
            font-size: .88rem;
            color: var(--text-gray);
            text-decoration: none;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            font-size: .82rem;
            color: var(--text-gray);
            margin-top: 20px;
        }

        .form-footer a { color: var(--text-gray); text-decoration: none; }

        /* ---------- Toast notifikasi ---------- */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fff;
            border: 1px solid #f3c1bf;
            border-left: 4px solid #b3261e;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,.12);
            padding: 12px 14px;
            min-width: 260px;
            max-width: 340px;
            font-size: .88rem;
            color: var(--text-dark);
            animation: toast-in .25s ease-out;
        }

        .toast.toast-hide {
            animation: toast-out .2s ease-in forwards;
        }

        .toast .toast-icon {
            color: #b3261e;
            font-weight: 700;
            line-height: 1;
        }

        .toast .toast-msg { flex: 1; }

        .toast .toast-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-gray);
            font-size: 1rem;
            line-height: 1;
            padding: 0;
        }

        @keyframes toast-in {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes toast-out {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(20px); }
        }

        /* ---------- Responsive: stack di mobile ---------- */
        @media (max-width: 900px) {
            html, body { overflow-y: auto; }
            .login-wrapper { flex-direction: column; height: auto; min-height: 100vh; }
            .login-visual { min-height: 260px; padding: 32px; }
            .login-form-panel {
                max-width: 100%;
                min-width: 0;
                padding: 36px 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Toast notifikasi -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="login-wrapper">

        <!-- Panel kiri: visual -->
        <div class="login-visual">
            <div class="brand-lockup">
                <img src="../assets/img/logo-biotrop-lockup.png" alt="SEAMEO BIOTROP">
            </div>
            <div class="visual-copy">
                <h1>SEAMEO BIOTROP</h1>
                <h1 class="sub">Activities in Spatial</h1>
                <p>Platform GIS untuk pemetaan lokasi kegiatan dan aktivitas SEAMEO BIOTROP.</p>
            </div>
        </div>

        <!-- Panel kanan: form login -->
        <div class="login-form-panel">
            <div>
                <div class="logos">
                    <img src="../assets/img/logo-seameo.png" alt="Logo SEAMEO">
                    <img src="../assets/img/logo-biotrop.png" alt="Logo BIOTROP">
                </div>

                <h2>Login Portal GIS</h2>
                <p class="lead">Masuk untuk melihat detail kegiatan lebih lengkap atau mengakses panel staf.</p>

                <form method="POST" action="">
                    <input type="hidden" name="next" value="<?= htmlspecialchars($next ?? '') ?>">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control-custom"
                               placeholder="Username anda"
                               value="<?= htmlspecialchars($username ?? '') ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password" class="form-control-custom"
                                   placeholder="Password anda" required>
                            <button type="button" class="toggle-eye" id="toggleEyeBtn" onclick="togglePassword()" aria-label="Tampilkan password">
                                <!-- ikon mata terbuka (default) -->
                                <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <!-- ikon mata tertutup (muncul saat password ditampilkan) -->
                                <svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a19.83 19.83 0 0 1 5.06-6.06M9.9 4.24A10.4 10.4 0 0 1 12 4c7 0 11 8 11 8a19.86 19.86 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="remember-row">
                        <input type="checkbox" id="ingat_saya" name="ingat_saya">
                        <label for="ingat_saya" style="margin:0;font-weight:500;">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn-masuk">Masuk</button>

                    <p class="lupa-password">Tidak punya akun? <a href="../user/index.php" style="color: var(--green-end); font-weight: 600;">Lihat peta tanpa login</a>.</p>
                </form>
            </div>

            <div class="form-footer">
                <span>© <?= date('Y') ?> SEAMEO BIOTROP</span>
                <a href="mailto:info@biotrop.org">Hubungi Kami</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input      = document.getElementById('password');
            const eyeOpen    = document.getElementById('eyeOpen');
            const eyeClosed  = document.getElementById('eyeClosed');
            const btn        = document.getElementById('toggleEyeBtn');
            const showing    = input.type === 'password';

            input.type = showing ? 'text' : 'password';
            eyeOpen.style.display   = showing ? 'none' : 'block';
            eyeClosed.style.display = showing ? 'block' : 'none';
            btn.setAttribute('aria-label', showing ? 'Sembunyikan password' : 'Tampilkan password');
        }

        function showToast(message) {
            const container = document.getElementById('toastContainer');

            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `
                <span class="toast-icon">&#9888;</span>
                <span class="toast-msg"></span>
                <button type="button" class="toast-close" aria-label="Tutup notifikasi">&times;</button>
            `;
            toast.querySelector('.toast-msg').textContent = message;

            const remove = () => {
                toast.classList.add('toast-hide');
                setTimeout(() => toast.remove(), 200);
            };

            toast.querySelector('.toast-close').addEventListener('click', remove);
            container.appendChild(toast);

            // auto-dismiss setelah 6 detik (tetap bisa ditutup manual sebelum itu)
            setTimeout(remove, 6000);
        }

        <?php if ($error): ?>
        showToast(<?= json_encode($error) ?>);
        <?php endif; ?>
    </script>
</body>
</html>