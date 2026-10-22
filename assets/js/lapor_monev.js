/**
 * assets/js/lapor_monev.js
 * 
 * Interaktivitas Lapor Capaian M&E Portal Sekolah
 * - Dynamic Status Pill Highlight & Input Enable/Disable
 * - Live Progress Bar & Breakdown Calculation
 * - Program Switcher
 * - File Upload Validation & Name Display
 * - Asynchronous Form Submit with Toast Feedback
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formLaporMonev');
    const programSelector = document.getElementById('programSelector');
    const progressFill = document.getElementById('progressFill');
    const progressPercentageText = document.getElementById('progressPercentageText');
    const countTercapai = document.getElementById('countTercapai');
    const countBerjalan = document.getElementById('countBerjalan');
    const countBelum = document.getElementById('countBelum');
    const submitBtn = document.getElementById('btnSubmitMonev');
    const timelineList = document.getElementById('timelineList');

    const BASE_URL = form ? (form.dataset.baseUrl || '/biotrop-gis') : '/biotrop-gis';

    // ==================== Program Selector ====================
    if (programSelector) {
        programSelector.addEventListener('change', function () {
            const progId = this.value;
            if (progId) {
                window.location.href = `lapor_capaian_monev.php?program_id=${encodeURIComponent(progId)}`;
            }
        });
    }

    // ==================== Status Pill Toggle & Input Enable/Disable ====================
    function syncRowStatus(row) {
        const checkedRadio = row.querySelector('.status-radio:checked');
        const options = row.querySelectorAll('.status-pill-option');
        const numInputs = row.querySelectorAll('.input-unit-wrap input');

        // Bersihkan class active
        options.forEach(opt => {
            opt.classList.remove('active-tercapai', 'active-berjalan', 'active-belum');
        });

        if (checkedRadio) {
            const val = checkedRadio.value;
            const parentLabel = checkedRadio.closest('.status-pill-option');
            if (parentLabel) {
                if (val === 'sudah_tercapai') parentLabel.classList.add('active-tercapai');
                else if (val === 'sedang_berjalan') parentLabel.classList.add('active-berjalan');
                else if (val === 'belum_mulai') parentLabel.classList.add('active-belum');
            }

            // Enable / disable input kuantitatif
            const enable = (val === 'sudah_tercapai' || val === 'sedang_berjalan');
            numInputs.forEach(input => {
                input.disabled = !enable;
                if (!enable) {
                    input.dataset.prevVal = input.value;
                }
            });
        }
    }

    const statusRadios = document.querySelectorAll('.status-radio');
    statusRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            const row = this.closest('.checklist-row');
            if (row) syncRowStatus(row);
            updateProgressAndStats();
        });
    });

    // Inisialisasi status awal tiap baris
    document.querySelectorAll('.checklist-row').forEach(row => {
        syncRowStatus(row);
    });

    // ==================== Update Progress & Breakdown ====================
    function updateProgressAndStats() {
        const rows = document.querySelectorAll('.checklist-row');
        const total = rows.length;
        if (total === 0) return;

        let tercapai = 0;
        let berjalan = 0;
        let belum = 0;

        rows.forEach(row => {
            const checked = row.querySelector('.status-radio:checked');
            if (checked) {
                if (checked.value === 'sudah_tercapai') tercapai++;
                else if (checked.value === 'sedang_berjalan') berjalan++;
                else if (checked.value === 'belum_mulai') belum++;
            }
        });

        // Hitung persentase: Sudah tercapai = 100%, Sedang berjalan = 50%
        const score = (tercapai * 100) + (berjalan * 50);
        const percent = Math.min(100, Math.round(score / (total * 100) * 100));

        if (progressFill) {
            progressFill.style.width = percent + '%';
            if (percent >= 75) {
                progressFill.style.background = 'linear-gradient(90deg, #16a34a, #15803d)';
            } else if (percent >= 40) {
                progressFill.style.background = 'linear-gradient(90deg, #f59e0b, #d97706)';
            } else if (percent > 0) {
                progressFill.style.background = 'linear-gradient(90deg, #3b82f6, #1d4ed8)';
            } else {
                progressFill.style.background = '#cbd5e1';
            }
        }

        if (progressPercentageText) {
            progressPercentageText.textContent = percent + '%';
        }

        if (countTercapai) countTercapai.textContent = tercapai;
        if (countBerjalan) countBerjalan.textContent = berjalan;
        if (countBelum) countBelum.textContent = belum;
    }

    updateProgressAndStats();

    // ==================== File Upload Handling ====================
    const fileInputs = document.querySelectorAll('.monev-file-input');
    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            const id = this.dataset.no;
            const display = document.getElementById('fileNameDisplay_' + id);
            if (!display) return;

            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                const sizeMb = (file.size / (1024 * 1024)).toFixed(2);

                if (file.size > 2 * 1024 * 1024) {
                    if (typeof showToast === 'function') {
                        showToast('Ukuran file foto maksimal 2MB', 'error');
                    } else {
                        alert('Ukuran file foto maksimal 2MB');
                    }
                    this.value = '';
                    display.textContent = '';
                    return;
                }

                display.textContent = `✓ ${file.name} (${sizeMb} MB)`;
            } else {
                display.textContent = '';
            }
        });
    });

    // ==================== Form Submit via AJAX ====================
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Validasi minimal 1 status sudah dipilih
            const anyChecked = document.querySelector('.status-radio:checked');
            if (!anyChecked) {
                if (typeof showToast === 'function') {
                    showToast('Pilih minimal status satu indikator untuk dilaporkan.', 'warning');
                } else {
                    alert('Pilih minimal status satu indikator untuk dilaporkan.');
                }
                return;
            }

            const formData = new FormData(this);

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border-sm"></span> Menyimpan...';
            }

            try {
                const res = await fetch(`${BASE_URL}/api/submit_laporan_monev.php`, {
                    method: 'POST',
                    body: formData
                });

                const json = await res.json();

                if (json.success) {
                    if (typeof showToast === 'function') {
                        showToast(json.message || 'Laporan capaian M&E berhasil disimpan!', 'success');
                    }

                    // Tambah update timeline
                    if (timelineList) {
                        const now = new Date();
                        const timeStr = now.toLocaleDateString('id-ID', {
                            day: '2-digit', month: 'short', year: 'numeric',
                            hour: '2-digit', minute: '2-digit'
                        });

                        const emptyItem = timelineList.querySelector('.text-muted');
                        if (emptyItem) emptyItem.remove();

                        const newItem = document.createElement('div');
                        newItem.className = 'timeline-mini-item';
                        newItem.innerHTML = `
                            <div class="timeline-dot"></div>
                            <div class="timeline-info">
                                <strong>Pembaruan Laporan</strong>
                                <span>${timeStr} WIB</span>
                            </div>
                        `;
                        timelineList.prepend(newItem);
                    }

                    // Refresh setelah 1.2 detik agar preview foto dan data tersinkronisasi utuh
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);

                } else {
                    if (typeof showToast === 'function') {
                        showToast(json.message || 'Gagal menyimpan laporan.', 'error');
                    } else {
                        alert(json.message || 'Gagal menyimpan laporan.');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill"></i> Simpan & Kirim Laporan';
                    }
                }
            } catch (err) {
                console.error('Submit error:', err);
                if (typeof showToast === 'function') {
                    showToast('Terjadi kendala saat mengirim data ke server.', 'error');
                } else {
                    alert('Terjadi kendala saat mengirim data ke server.');
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill"></i> Simpan & Kirim Laporan';
                }
            }
        });
    }
});
