<?php
/**
 * user/lapor_capaian_monev.php
 * 
 * Halaman pelaporan Capaian M&E (Monitoring & Evaluasi) untuk portal sekolah.
 * User sekolah dapat melaporkan capaian dari kegiatan/program yang sedang berjalan.
 * 
 * Project: Web GIS SEAMEO BIOTROP
 */

session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Halaman dapat dibuka untuk kebutuhan presentasi. Aksi simpan tetap hanya
// tersedia bagi sekolah yang sudah masuk, karena API memiliki validasi sesi.
$isPresentationMode = !isset($_SESSION['user_id']);

// Pastikan user adalah sekolah/user (bukan admin/superadmin/internal)
if (($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['role'] ?? '') === 'superadmin') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$idSekolah = (int)($_SESSION['user_id'] ?? 0);
$namaSekolah = $_SESSION['nama_lengkap'] ?? 'Mode Presentasi';

// Ambil daftar program aktif dari database
$stmtProg = $pdo->query("
    SELECT id, nama_program, bulan_pelaporan, tahun, deskripsi 
    FROM program_pelaporan 
    WHERE is_active = 1 
    ORDER BY tahun DESC, id ASC
");
$programList = $stmtProg->fetchAll();

// Tentukan program yang dipilih
$selectedProgramId = isset($_GET['program_id']) && is_numeric($_GET['program_id']) 
    ? (int)$_GET['program_id'] 
    : ($programList[0]['id'] ?? 0);

$currentProgram = null;
foreach ($programList as $p) {
    if ((int)$p['id'] === $selectedProgramId) {
        $currentProgram = $p;
        break;
    }
}
if (!$currentProgram && !empty($programList)) {
    $currentProgram = $programList[0];
    $selectedProgramId = (int)$currentProgram['id'];
}

// Ambil data indikator program dan field kuantitatif
$indikatorList = [];
$fieldsByIndikator = [];
$savedReports = [];
$lastUpdated = null;

if ($selectedProgramId > 0) {
    $stmtInd = $pdo->prepare("
        SELECT id, nomor_urut, nama_indikator, deskripsi, icon_class, has_quantitative_input 
        FROM indikator_program 
        WHERE program_id = :prog_id 
        ORDER BY nomor_urut ASC
    ");
    $stmtInd->execute(['prog_id' => $selectedProgramId]);
    $indikatorList = $stmtInd->fetchAll();

    $indikatorIds = array_column($indikatorList, 'id');
    if (!empty($indikatorIds)) {
        $placeholders = implode(',', array_fill(0, count($indikatorIds), '?'));
        $stmtFields = $pdo->prepare("
            SELECT id, indikator_id, field_name, label, satuan, urutan 
            FROM field_kuantitatif_indikator 
            WHERE indikator_id IN ($placeholders) 
            ORDER BY urutan ASC
        ");
        $stmtFields->execute($indikatorIds);
        foreach ($stmtFields->fetchAll() as $f) {
            $fieldsByIndikator[$f['indikator_id']][] = $f;
        }
    }

    // Ambil data capaian yang tersimpan untuk sekolah & program ini
    if (!$isPresentationMode) {
        $stmtSaved = $pdo->prepare("
            SELECT indikator_no, status, dampak_json, foto_path, kendala, created_at, updated_at 
            FROM capaian_monev 
            WHERE sekolah_id = :sid AND program_id = :pid
        ");
        $stmtSaved->execute(['sid' => $idSekolah, 'pid' => $selectedProgramId]);
        foreach ($stmtSaved->fetchAll() as $row) {
            $savedReports[$row['indikator_no']] = $row;
            if (!$lastUpdated || strtotime($row['updated_at']) > strtotime($lastUpdated)) {
                $lastUpdated = $row['updated_at'];
            }
        }
    }
}

// Hitung statistik awal
$totalIndikator = count($indikatorList);
$tercapaiCount = 0;
$berjalanCount = 0;
$belumCount = 0;

foreach ($indikatorList as $ind) {
    $no = $ind['nomor_urut'];
    $st = $savedReports[$no]['status'] ?? 'belum_mulai';
    if ($st === 'sudah_tercapai') $tercapaiCount++;
    elseif ($st === 'sedang_berjalan') $berjalanCount++;
    else $belumCount++;
}

$initialScore = ($tercapaiCount * 100) + ($berjalanCount * 50);
$initialPercent = $totalIndikator > 0 ? min(100, round(($initialScore / ($totalIndikator * 100)) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Capaian M&E - Web GIS SEAMEO BIOTROP</title>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/img/logo-biotrop.png">
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= @filemtime(__DIR__ . '/../assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/toast.css?v=<?= @filemtime(__DIR__ . '/../assets/css/toast.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/lapor_monev.css?v=<?= @filemtime(__DIR__ . '/../assets/css/lapor_monev.css') ?>">
</head>
<body class="admin-body">

    <!-- Sidebar & Topbar Portal Sekolah -->
    <?php 
    $activePage = 'lapor_monev';
    require_once __DIR__ . '/../includes/navbar_sekolah.php'; 
    ?>

    <!-- Konten Utama (Posisi di Tengah dan Proporsional) -->
    <div class="lapor-monev-wrapper">
        
        <!-- Page Header -->
        <div class="lapor-page-header">
            <h1 class="page-title">Lapor Capaian M&E</h1>
            <p class="page-subtitle">Monitoring dan evaluasi capaian implementasi program kegiatan sekolah Anda</p>
            <?php if ($isPresentationMode): ?>
                <p class="mt-2 mb-0 text-muted"><i class="bi bi-eye me-1"></i>Mode presentasi — data contoh dapat dilihat tanpa login.</p>
            <?php endif; ?>
        </div>

        <!-- Header Card: Info Sekolah & Pemilihan Program -->
        <div class="lapor-header-card">
            <div class="lapor-header-top">
                <div class="sekolah-profile-badge">
                    <div class="sekolah-badge-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="sekolah-badge-details">
                        <h3><?= htmlspecialchars($namaSekolah) ?></h3>
                        <span class="sekolah-role-tag">
                            <i class="bi bi-shield-check"></i> Sekolah Mitra BIOTROP
                        </span>
                    </div>
                </div>

                <div class="program-selector-wrapper">
                    <label for="programSelector" class="program-selector-label">
                        <i class="bi bi-calendar2-range me-1"></i> Program Pelaporan:
                    </label>
                    <select class="program-select-input" id="programSelector">
                        <?php foreach ($programList as $prog): ?>
                            <option value="<?= $prog['id'] ?>" <?= (int)$prog['id'] === $selectedProgramId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prog['nama_program']) ?> (<?= htmlspecialchars($prog['bulan_pelaporan']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($currentProgram): ?>
            <div class="lapor-header-info">
                <div class="program-info-title">
                    <h4><?= htmlspecialchars($currentProgram['nama_program']) ?></h4>
                    <span class="program-badge-period">
                        <i class="bi bi-clock-history me-1"></i> <?= htmlspecialchars($currentProgram['bulan_pelaporan']) ?> <?= htmlspecialchars($currentProgram['tahun']) ?>
                    </span>
                </div>
                <p class="program-info-desc">
                    <?= htmlspecialchars($currentProgram['deskripsi'] ?: 'Silakan isi perkembangan indikator kegiatan di bawah ini.') ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Grid: Form & Sidebar Progress -->
        <div class="lapor-monev-grid">
            
            <!-- Panel Kiri: Form Checklist Indikator -->
            <div class="lapor-panel-card">
                <div class="panel-card-header">
                    <h2>
                        <i class="bi bi-card-checklist text-success"></i> Indikator Capaian Kegiatan
                    </h2>
                    <span class="header-hint">Pilih status dan lengkapi bukti capaian kegiatan</span>
                </div>

                <form id="formLaporMonev" method="POST" enctype="multipart/form-data" data-base-url="<?= BASE_URL ?>">
                    <input type="hidden" name="program_id" id="programId" value="<?= $selectedProgramId ?>">
                    <input type="hidden" name="sekolah_id" value="<?= $idSekolah ?>">
                    <fieldset <?= $isPresentationMode ? 'disabled' : '' ?>>

                    <div class="tabel-monev-wrapper">
                        <table class="tabel-monev">
                            <thead>
                                <tr>
                                    <th class="col-indikator">Komponen / Indikator</th>
                                    <th class="col-status">Status Penerapan</th>
                                    <th class="col-dampak">Dampak Kuantitatif</th>
                                    <th class="col-evidence">Bukti Dukung & Kendala</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($indikatorList)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            Belum ada indikator untuk program pelaporan ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($indikatorList as $ind): 
                                        $no = (int)$ind['nomor_urut'];
                                        $fields = $fieldsByIndikator[$ind['id']] ?? [];
                                        $saved = $savedReports[$no] ?? null;
                                        $savedStatus = $saved['status'] ?? 'belum_mulai';
                                        $savedDampak = [];
                                        if (!empty($saved['dampak_json'])) {
                                            $decoded = json_decode($saved['dampak_json'], true);
                                            if (is_array($decoded)) $savedDampak = $decoded;
                                        }
                                        $savedFoto = $saved['foto_path'] ?? '';
                                        $savedKendala = $saved['kendala'] ?? '';
                                    ?>
                                    <tr class="checklist-row" data-indikator-id="<?= $no ?>">
                                        
                                        <!-- Kolom Indikator -->
                                        <td class="col-indikator">
                                            <div class="indikator-item-box">
                                                <div class="indikator-number"><?= sprintf('%02d', $no) ?></div>
                                                <div class="indikator-title-wrap">
                                                    <strong>
                                                        <?php if (!empty($ind['icon_class'])): ?>
                                                            <i class="<?= htmlspecialchars($ind['icon_class']) ?> me-1 text-success"></i>
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($ind['nama_indikator']) ?>
                                                    </strong>
                                                    <?php if (!empty($ind['deskripsi'])): ?>
                                                        <p><?= htmlspecialchars($ind['deskripsi']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kolom Status (Pills Radio) -->
                                        <td class="col-status">
                                            <div class="status-pill-group">
                                                <label class="status-pill-option <?= $savedStatus === 'sudah_tercapai' ? 'active-tercapai' : '' ?>">
                                                    <input 
                                                        type="radio" 
                                                        name="status_<?= $no ?>" 
                                                        value="sudah_tercapai" 
                                                        class="status-radio"
                                                        <?= $savedStatus === 'sudah_tercapai' ? 'checked' : '' ?>
                                                    >
                                                    <span class="status-text">
                                                        <i class="bi bi-check2-circle me-1 text-success"></i> Sudah Tercapai
                                                    </span>
                                                </label>

                                                <label class="status-pill-option <?= $savedStatus === 'sedang_berjalan' ? 'active-berjalan' : '' ?>">
                                                    <input 
                                                        type="radio" 
                                                        name="status_<?= $no ?>" 
                                                        value="sedang_berjalan" 
                                                        class="status-radio"
                                                        <?= $savedStatus === 'sedang_berjalan' ? 'checked' : '' ?>
                                                    >
                                                    <span class="status-text">
                                                        <i class="bi bi-arrow-repeat me-1 text-warning"></i> Sedang Berjalan
                                                    </span>
                                                </label>

                                                <label class="status-pill-option <?= $savedStatus === 'belum_mulai' ? 'active-belum' : '' ?>">
                                                    <input 
                                                        type="radio" 
                                                        name="status_<?= $no ?>" 
                                                        value="belum_mulai" 
                                                        class="status-radio"
                                                        <?= $savedStatus === 'belum_mulai' ? 'checked' : '' ?>
                                                    >
                                                    <span class="status-text">
                                                        <i class="bi bi-dash-circle me-1 text-secondary"></i> Belum Mulai
                                                    </span>
                                                </label>
                                            </div>
                                        </td>

                                        <!-- Kolom Dampak Kuantitatif -->
                                        <td class="col-dampak">
                                            <?php if (!empty($fields)): ?>
                                                <div class="dampak-field-list">
                                                    <?php foreach ($fields as $field): 
                                                        $val = $savedDampak[$field['field_name']] ?? '';
                                                        $disabled = ($savedStatus === 'belum_mulai');
                                                    ?>
                                                        <div class="dampak-field-item">
                                                            <label><?= htmlspecialchars($field['label']) ?></label>
                                                            <div class="input-unit-wrap">
                                                                <input 
                                                                    type="number" 
                                                                    min="0"
                                                                    step="any"
                                                                    name="dampak_<?= $no ?>_<?= htmlspecialchars($field['field_name']) ?>" 
                                                                    value="<?= htmlspecialchars($val) ?>"
                                                                    placeholder="0"
                                                                    <?= $disabled ? 'disabled' : '' ?>
                                                                >
                                                                <span class="unit-tag"><?= htmlspecialchars($field['satuan']) ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="no-dampak-text">
                                                    <i class="bi bi-dash"></i> Tidak ada input kuantitatif
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Kolom Bukti & Kendala -->
                                        <td class="col-evidence">
                                            <div class="evidence-box">
                                                
                                                <!-- Upload Foto -->
                                                <div class="upload-action-wrap">
                                                    <label for="foto_<?= $no ?>" class="btn-file-upload" title="Unggah bukti foto">
                                                        <i class="bi bi-camera"></i> 
                                                        <?= !empty($savedFoto) ? 'Ganti Foto' : 'Unggah Foto' ?>
                                                    </label>
                                                    <input 
                                                        type="file" 
                                                        id="foto_<?= $no ?>" 
                                                        name="foto_<?= $no ?>" 
                                                        data-no="<?= $no ?>"
                                                        class="monev-file-input"
                                                        accept="image/*"
                                                        style="display:none;"
                                                    >
                                                    <span class="file-selected-name" id="fileNameDisplay_<?= $no ?>"></span>
                                                    
                                                    <?php if (!empty($savedFoto)): ?>
                                                        <div class="uploaded-preview-badge">
                                                            <i class="bi bi-image text-success"></i>
                                                            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($savedFoto) ?>" target="_blank" rel="noopener">
                                                                Lihat Foto Saat Ini <i class="bi bi-box-arrow-up-right ms-1"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Kendala / Catatan -->
                                                <div class="kendala-wrap">
                                                    <textarea 
                                                        name="kendala_<?= $no ?>" 
                                                        class="kendala-textarea"
                                                        placeholder="Catatan hambatan / kendala lapangan..."
                                                        rows="2"
                                                    ><?= htmlspecialchars($savedKendala) ?></textarea>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </fieldset>
                </form>
            </div>

            <!-- Panel Kanan: Sidebar Ringkasan & Submit -->
            <div class="lapor-monev-sidebar">
                
                <!-- Progress Capaian Card -->
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">
                        <i class="bi bi-graph-up-arrow text-success"></i> Capaian Action Plan
                    </h3>

                    <div class="progress-bar-wrap">
                        <div class="progress-header">
                            <span>Tingkat Ketercapaian</span>
                            <span class="progress-percentage" id="progressPercentageText"><?= $initialPercent ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar-fill" id="progressFill" style="width: <?= $initialPercent ?>%;"></div>
                        </div>
                    </div>

                    <div class="progress-breakdown">
                        <div class="breakdown-item item-tercapai">
                            <span class="b-label">Sudah Tercapai</span>
                            <span class="b-val" id="countTercapai"><?= $tercapaiCount ?></span>
                        </div>
                        <div class="breakdown-item item-berjalan">
                            <span class="b-label">Sedang Berjalan</span>
                            <span class="b-val" id="countBerjalan"><?= $berjalanCount ?></span>
                        </div>
                        <div class="breakdown-item">
                            <span class="b-label">Belum Mulai</span>
                            <span class="b-val" id="countBelum"><?= $belumCount ?></span>
                        </div>
                        <div class="breakdown-item">
                            <span class="b-label">Total Indikator</span>
                            <span class="b-val"><?= $totalIndikator ?></span>
                        </div>
                    </div>
                </div>

                <!-- Riwayat / Status Card -->
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">
                        <i class="bi bi-clock-history text-primary"></i> Status Pelaporan
                    </h3>

                    <div class="timeline-mini-list" id="timelineList">
                        <?php if ($lastUpdated): ?>
                            <div class="timeline-mini-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-info">
                                    <strong>Terakhir Disimpan</strong>
                                    <span><?= date('d M Y, H:i', strtotime($lastUpdated)) ?> WIB</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-info-circle me-1"></i> Belum ada data laporan yang dikirimkan untuk program ini.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="sidebar-card" style="padding: 16px;">
                    <button type="submit" form="formLaporMonev" class="btn-monev-submit" id="btnSubmitMonev" <?= $isPresentationMode ? 'disabled title="Login diperlukan untuk menyimpan laporan"' : '' ?>>
                        <i class="bi bi-cloud-arrow-up-fill"></i> Simpan &amp; Kirim Laporan
                    </button>
                    <p class="submit-safe-hint">
                        <i class="bi bi-shield-lock me-1"></i> Data yang Anda kirim akan langsung diverifikasi dan tercatat pada sistem M&amp;E BIOTROP.
                    </p>
                </div>

            </div>
        </div>

    </div>

    <!-- Tutup elemen <div class="admin-content"><div class="admin-main"> yang dibuka di navbar_sekolah.php -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<!-- Toast Container & Scripts -->
<script src="<?= BASE_URL ?>/assets/js/toast.js?v=<?= @filemtime(__DIR__ . '/../assets/js/toast.js') ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/lapor_monev.js?v=<?= @filemtime(__DIR__ . '/../assets/js/lapor_monev.js') ?>"></script>

</body>
</html>
