/**
 * Toast notification reusable.
 * Pakai: showToast('Pesan detail di sini', 'success');
 * Type yang didukung: 'error' (default), 'success', 'warning', 'info'
 * Toast otomatis hilang sendiri setelah beberapa detik (default 4 detik),
 * tapi tetap bisa ditutup manual lewat tombol close.
 */
(function () {
    function ensureContainer() {
        let el = document.getElementById('toastContainer');
        if (!el) {
            el = document.createElement('div');
            el.id = 'toastContainer';
            el.className = 'toast-container';
            document.body.appendChild(el);
        }
        return el;
    }

    const ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4.5 4.5L19 7.5"></path></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><line x1="6.5" y1="17.5" x2="17.5" y2="6.5"></line></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="11" x2="12" y2="16"></line><circle cx="12" cy="8" r=".5" fill="currentColor" stroke="none"></circle></svg>',
    };

    const TITLES = {
        success: 'Berhasil!',
        error: 'Gagal',
        warning: 'Peringatan',
        info: 'Info',
    };

    window.showToast = function (message, type = 'error', durationMs = 4000) {
        const container = ensureContainer();
        const iconSvg = ICONS[type] || ICONS.error;
        const title = TITLES[type] || TITLES.error;

        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${iconSvg}</span>
            <span class="toast-body">
                <span class="toast-title"></span>
                <span class="toast-msg"></span>
            </span>
            <button type="button" class="toast-close" aria-label="Tutup notifikasi">&times;</button>
        `;
        toast.querySelector('.toast-title').textContent = title;
        toast.querySelector('.toast-msg').textContent = message;

        const remove = () => {
            toast.classList.add('toast-hide');
            setTimeout(() => toast.remove(), 200);
        };

        toast.querySelector('.toast-close').addEventListener('click', remove);
        container.appendChild(toast);
        setTimeout(remove, durationMs);
    };
})();