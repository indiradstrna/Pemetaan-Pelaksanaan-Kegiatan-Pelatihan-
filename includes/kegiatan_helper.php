<?php
/**
 * includes/kegiatan_helper.php
 *
 * Konstanta & fungsi bersama seputar data kegiatan -- dipisah dari
 * api/crud_kegiatan.php supaya bisa dipakai juga oleh
 * api/crud_kegiatan_revisi.php (alur "Ajukan Revisi") tanpa duplikasi
 * logic validasi/tag/departemen/foto. Kedua file itu require_once
 * file ini, JANGAN dobel-declare const/fungsi yang sama di file lain.
 */

const SKALA_VALID      = ['Lokal', 'Kota/Kabupaten', 'Provinsi', 'Nasional', 'Internasional'];
const DEPARTEMEN_VALID = ['SITD', 'HCID', 'HRAD', 'FAD', 'FMD', 'KMD'];
const MAX_FOTO          = 5;
// MAX_UKURAN_MB sekarang jadi TARGET ukuran akhir setelah dikompres (bukan batas tolak-mentah
// lagi) -- foto besar apa pun bakal dikompres otomatis (lihat kompresGambar()) sampai muat
// di bawah angka ini, bukan langsung ditolak. MAX_UKURAN_UPLOAD_MENTAH_MB itu pagar terpisah
// buat file MENTAH sebelum sempat dikompres -- cuma jaga-jaga dari file yang kebesaran banget
// (misal 40MP RAW-ish) yang bisa bikin GD kehabisan memori pas dimuat ke memori.
const MAX_UKURAN_MB               = 2;
const MAX_UKURAN_UPLOAD_MENTAH_MB = 15;
const TIPE_FOTO_OK      = ['image/jpeg', 'image/png'];

function generateKodeKegiatan(PDO $pdo): string
{
    $tahun = date('Y');
    $stmt  = $pdo->prepare(
        "SELECT MAX(CAST(SUBSTRING_INDEX(kode_kegiatan, '-', -1) AS UNSIGNED)) AS max_urut
         FROM kegiatan WHERE kode_kegiatan LIKE :pattern"
    );
    $stmt->execute(['pattern' => "KEG-{$tahun}-%"]);
    $maxUrut = (int) ($stmt->fetch()['max_urut'] ?? 0);
    $urut    = str_pad((string) ($maxUrut + 1), 3, '0', STR_PAD_LEFT);
    return "KEG-{$tahun}-{$urut}";
}

function validasiKegiatanInput(array $input): ?string
{
    if (trim($input['judul'] ?? '') === '') return 'Judul kegiatan wajib diisi.';
    if ((int) ($input['location_id'] ?? 0) <= 0) return 'Lokasi kegiatan wajib dipilih.';
    if (empty($input['tanggal_mulai']) || empty($input['tanggal_selesai'])) return 'Tanggal mulai dan selesai wajib diisi.';
    if (strtotime($input['tanggal_selesai']) < strtotime($input['tanggal_mulai'])) return 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    if (!in_array($input['skala_kegiatan'] ?? '', SKALA_VALID, true)) return 'Skala kegiatan tidak valid.';
    return null;
}

/**
 * Status kegiatan TIDAK diisi lewat form -- 2 status pertama dihitung otomatis
 * dari rentang tanggal, tapi status 'selesai' TIDAK PERNAH dihasilkan di sini lagi.
 * 'belum_berlangsung' -> hari ini masih sebelum tanggal_mulai
 * 'berlangsung'       -> hari ini sudah >= tanggal_mulai (termasuk yang tanggal_selesai-nya
 *                        sudah lewat tapi belum ditandai selesai manual lewat action=selesaikan)
 */
function hitungStatusKegiatan(string $tanggalMulai, string $tanggalSelesai): string
{
    $hariIni = date('Y-m-d');
    if ($hariIni < $tanggalMulai) return 'belum_berlangsung';
    return 'berlangsung';
}

/** Cari id tag berdasarkan nama (case-insensitive), bikin baru kalau belum ada. */
function resolveTagId(PDO $pdo, string $namaTag): ?int
{
    $namaTag = trim($namaTag);
    if ($namaTag === '') return null;

    $stmt = $pdo->prepare('SELECT id FROM tags WHERE LOWER(nama_tag) = LOWER(:nama)');
    $stmt->execute(['nama' => $namaTag]);
    $row = $stmt->fetch();
    if ($row) return (int) $row['id'];

    $insert = $pdo->prepare('INSERT INTO tags (nama_tag) VALUES (:nama)');
    $insert->execute(['nama' => $namaTag]);
    return (int) $pdo->lastInsertId();
}

/** Simpan relasi tag untuk 1 kegiatan (hapus dulu semua yang lama, insert ulang). */
function simpanTagsKegiatan(PDO $pdo, int $kegiatanId, array $tagIds, array $tagBaru): void
{
    $semuaId = $tagIds;
    foreach ($tagBaru as $nama) {
        $id = resolveTagId($pdo, $nama);
        if ($id) $semuaId[] = $id;
    }
    $semuaId = array_unique(array_map('intval', $semuaId));

    $pdo->prepare('DELETE FROM kegiatan_tags WHERE kegiatan_id = :id')->execute(['id' => $kegiatanId]);

    if (!empty($semuaId)) {
        $stmt = $pdo->prepare('INSERT INTO kegiatan_tags (kegiatan_id, tag_id) VALUES (:kid, :tid)');
        foreach ($semuaId as $tagId) {
            $stmt->execute(['kid' => $kegiatanId, 'tid' => $tagId]);
        }
    }
}

/** Simpan relasi departemen untuk 1 kegiatan (hapus dulu semua yang lama, insert ulang).
 *  Kode yang gak ada di DEPARTEMEN_VALID diabaikan diam-diam (bukan error keras),
 *  karena opsinya memang sudah tetap/fixed di sisi form. */
function simpanDepartemenKegiatan(PDO $pdo, int $kegiatanId, array $kodeList): void
{
    $kodeValid = array_values(array_unique(array_intersect(
        array_map('strtoupper', array_map('trim', $kodeList)),
        DEPARTEMEN_VALID
    )));

    $pdo->prepare('DELETE FROM kegiatan_departemen WHERE kegiatan_id = :id')->execute(['id' => $kegiatanId]);

    if (!empty($kodeValid)) {
        $stmt = $pdo->prepare('INSERT INTO kegiatan_departemen (kegiatan_id, departemen_kode) VALUES (:kid, :kode)');
        foreach ($kodeValid as $kode) {
            $stmt->execute(['kid' => $kegiatanId, 'kode' => $kode]);
        }
    }
}

/** Terjemahkan kode error upload PHP jadi pesan yang gampang dimengerti. */
function pesanErrorUpload(int $kode): string
{
    switch ($kode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'ukuran melebihi batas upload server (upload_max_filesize).';
        case UPLOAD_ERR_FORM_SIZE:
            return 'ukuran melebihi batas form.';
        case UPLOAD_ERR_PARTIAL:
            return 'upload terputus di tengah jalan, coba upload ulang.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return 'server gagal menyimpan file sementara.';
        default:
            return 'gagal diupload (kode error ' . $kode . ').';
    }
}

/** Muat gambar dari file sumber jadi resource GD, sekalian auto-rotate berdasarkan
 *  data EXIF Orientation (kalau ada) -- penting buat foto dari HP yang biasanya
 *  disimpan "mendatar" secara data walau tampil tegak berkat flag EXIF ini. Tanpa
 *  auto-rotate, hasil kompres GD bisa keluar muter/miring padahal aslinya normal. */
function muatGambarDenganRotasi(string $path, string $tipe)
{
    $img = $tipe === 'image/png' ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
    if (!$img) return null;

    // EXIF cuma relevan buat JPEG, dan cuma kalau ekstensi exif aktif di server.
    if ($tipe === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $orientasi = $exif['Orientation'] ?? 1;
        switch ($orientasi) {
            case 3: $img = imagerotate($img, 180, 0); break;
            case 6: $img = imagerotate($img, -90, 0); break;
            case 8: $img = imagerotate($img, 90, 0); break;
        }
    }

    return $img;
}

/** Kompres 1 file gambar (dari path sementara hasil upload) lalu simpan ke $tujuan.
 *  Strategi 2 tahap -- resize dulu (paling efektif motong ukuran file), baru turunin
 *  kualitas JPEG bertahap kalau masih di atas target:
 *   1) Kalau sisi terpanjang gambar > $maxDimensiPx, diperkecil proporsional dulu.
 *   2) Coba encode ulang sebagai JPEG dengan kualitas menurun (85 -> 25) sampai
 *      hasilnya <= $targetBytes. PNG ikut dikonversi ke JPEG di tahap ini (PNG lossless
 *      gak bisa dipepetin sekecil itu buat foto asli, dan kita gak perlu transparansi
 *      buat foto dokumentasi kegiatan).
 *  Return null kalau sukses, string pesan error kalau gagal (misal file korup/bukan gambar).
 *  Ekstensi hasil akhir SELALU .jpg (lihat pemanggilnya -- $tujuan sudah disesuaikan). */
function kompresGambar(string $sumber, string $tipe, string $tujuan, int $targetBytes, int $maxDimensiPx = 1920): ?string
{
    if (!function_exists('imagecreatefromjpeg')) {
        // GD gak aktif di server ini -- fallback aman: pindahin apa adanya daripada gagal total.
        return @copy($sumber, $tujuan) ? null : 'ekstensi GD tidak aktif di server & gagal fallback copy.';
    }

    $img = muatGambarDenganRotasi($sumber, $tipe);
    if (!$img) return 'file gambar rusak atau tidak bisa dibaca.';

    $lebar  = imagesx($img);
    $tinggi = imagesy($img);
    $sisiTerpanjang = max($lebar, $tinggi);

    // Tahap 1: resize proporsional kalau resolusinya kegedean.
    if ($sisiTerpanjang > $maxDimensiPx) {
        $rasio       = $maxDimensiPx / $sisiTerpanjang;
        $lebarBaru   = (int) round($lebar * $rasio);
        $tinggiBaru  = (int) round($tinggi * $rasio);

        $resized = imagecreatetruecolor($lebarBaru, $tinggiBaru);
        // Latar putih dulu (bukan hitam) -- jaga-jaga kalau sumbernya PNG transparan,
        // biar area transparan gak jadi hitam pekat pas di-flatten ke JPEG nanti.
        $putih = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $putih);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
        imagedestroy($img);
        $img = $resized;
    } elseif ($tipe === 'image/png') {
        // PNG yang gak perlu di-resize tetap perlu di-flatten (isi transparansinya
        // putih) sebelum diencode sebagai JPEG di tahap 2 -- JPEG gak dukung alpha channel.
        $flat = imagecreatetruecolor(imagesx($img), imagesy($img));
        $putih = imagecolorallocate($flat, 255, 255, 255);
        imagefill($flat, 0, 0, $putih);
        imagecopy($flat, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imagedestroy($img);
        $img = $flat;
    }

    // Tahap 2: turunin kualitas JPEG bertahap sampai muat di bawah target ukuran.
    // Dites di memori (output buffer) dulu -- baru kualitas yang lolos yang ditulis ke disk,
    // biar gak bolak-balik I/O ke disk tiap coba kualitas.
    $levelKualitas = [85, 75, 65, 55, 45, 35, 25];
    $hasilTerbaik  = null;

    foreach ($levelKualitas as $kualitas) {
        ob_start();
        imagejpeg($img, null, $kualitas);
        $buffer = ob_get_clean();

        $hasilTerbaik = $buffer; // simpan yang terakhir dicoba -- jaga-jaga semua level masih kegedean
        if (strlen($buffer) <= $targetBytes) break;
    }

    imagedestroy($img);

    if ($hasilTerbaik === null || file_put_contents($tujuan, $hasilTerbaik) === false) {
        return 'gagal menulis hasil kompresi ke server.';
    }

    return null; // sukses, meski di kasus foto yang EKSTREM detail/besar, hasil akhir
                 // bisa aja masih sedikit di atas target walau udah kualitas 25 -- ini
                 // trade-off yang wajar drpd nolak upload user sama sekali.
}


/** Upload foto baru untuk 1 kegiatan (langsung ke uploads/kegiatan & tabel kegiatan_foto).
 *  $captions opsional -- array caption per foto, index-nya harus selaras sama urutan
 *  di $files (index ke-0 di $files = caption ke-0 di $captions, dst). Caption kosong/
 *  gak ada disimpan sebagai NULL.
 *  Return null kalau semua sukses, string pesan error kalau ada yang gagal (foto lain yang valid tetap disimpan). */
function uploadFotoBaru(PDO $pdo, int $kegiatanId, array $files, int $urutanAwal, array $captions = []): ?string
{
    $uploadDir = UPLOAD_PATH_KEGIATAN;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $namaFiles = $files['name'] ?? [];
    $jumlahFile = is_array($namaFiles) ? count(array_filter($namaFiles)) : 0;
    if ($jumlahFile === 0) return null;

    $stmtInsert = $pdo->prepare(
        'INSERT INTO kegiatan_foto (kegiatan_id, path_foto, caption, urutan) VALUES (:kid, :path, :caption, :urutan)'
    );

    $urutan = $urutanAwal;
    $errors = [];

    for ($i = 0; $i < $jumlahFile; $i++) {
        $namaAsli = $files['name'][$i] ?? ('foto ke-' . ($i + 1));

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "{$namaAsli}: " . pesanErrorUpload($files['error'][$i]);
            continue;
        }

        $tipe   = $files['type'][$i];
        $ukuran = $files['size'][$i];

        if (!in_array($tipe, TIPE_FOTO_OK, true)) {
            $errors[] = "{$namaAsli}: format harus JPG atau PNG.";
            continue;
        }
        if ($ukuran > MAX_UKURAN_UPLOAD_MENTAH_MB * 1024 * 1024) {
            // Ini pagar file MENTAH (sebelum sempat dikompres) -- beda dari target
            // MAX_UKURAN_MB yang berlaku SETELAH kompresi. Kalau lolos sini, ukuran
            // akhirnya bakal ditekan otomatis ke bawah MAX_UKURAN_MB, bukan ditolak.
            $errors[] = "{$namaAsli}: ukuran file mentah maksimal " . MAX_UKURAN_UPLOAD_MENTAH_MB . "MB.";
            continue;
        }

        // Hasil kompresi SELALU disimpan sebagai .jpg (lihat kompresGambar -- PNG ikut
        // diflatten & diencode ulang jadi JPEG di situ), jadi ekstensi tujuan gak lagi
        // ngikutin tipe asli file.
        $namaFile = 'kegiatan_' . $kegiatanId . '_' . uniqid() . '.jpg';
        $tujuan   = $uploadDir . $namaFile;

        $errKompres = kompresGambar($files['tmp_name'][$i], $tipe, $tujuan, MAX_UKURAN_MB * 1024 * 1024);
        if ($errKompres !== null) {
            $errors[] = "{$namaAsli}: {$errKompres}";
            continue;
        }

        $pathRelatif = 'uploads/kegiatan/' . $namaFile;
        $captionMentah = trim((string) ($captions[$i] ?? ''));
        $caption = $captionMentah !== '' ? mb_substr($captionMentah, 0, 255) : null;
        try {
            $stmtInsert->execute(['kid' => $kegiatanId, 'path' => $pathRelatif, 'caption' => $caption, 'urutan' => $urutan]);
        } catch (PDOException $e) {
            // Insert DB gagal padahal file udah kepalang ke-copy ke server --
            // hapus lagi filenya biar gak jadi file yatim yang gak ke-link ke DB.
            if (is_file($tujuan)) unlink($tujuan);
            $errors[] = "{$namaAsli}: gagal disimpan ke database ({$e->getMessage()}).";
            continue;
        }
        $urutan++;
    }

    return $errors ? implode(' ', $errors) : null;
}

/** Upload foto BARU hasil pengajuan revisi -- BEDA sama uploadFotoBaru() biasa:
 *  file disimpan sementara ke uploads/kegiatan_revisi/ (BUKAN uploads/kegiatan/)
 *  dan BELUM di-insert ke tabel kegiatan_foto, karena masih nunggu persetujuan
 *  verifikator. $captions opsional, index-nya selaras sama $files. Return array
 *  ['foto' => [{path, caption}, ...], 'errors' => [...]]. */
function uploadFotoRevisi(int $kegiatanId, array $files, array $captions = []): array
{
    $uploadDir = UPLOAD_PATH_KEGIATAN_REVISI;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $namaFiles  = $files['name'] ?? [];
    $jumlahFile = is_array($namaFiles) ? count(array_filter($namaFiles)) : 0;

    $fotoTersimpan = [];
    $errors = [];

    for ($i = 0; $i < $jumlahFile; $i++) {
        $namaAsli = $files['name'][$i] ?? ('foto ke-' . ($i + 1));

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "{$namaAsli}: " . pesanErrorUpload($files['error'][$i]);
            continue;
        }

        $tipe   = $files['type'][$i];
        $ukuran = $files['size'][$i];

        if (!in_array($tipe, TIPE_FOTO_OK, true)) {
            $errors[] = "{$namaAsli}: format harus JPG atau PNG.";
            continue;
        }
        if ($ukuran > MAX_UKURAN_UPLOAD_MENTAH_MB * 1024 * 1024) {
            $errors[] = "{$namaAsli}: ukuran file mentah maksimal " . MAX_UKURAN_UPLOAD_MENTAH_MB . "MB.";
            continue;
        }

        $namaFile = 'revisi_' . $kegiatanId . '_' . uniqid() . '.jpg';
        $tujuan   = $uploadDir . $namaFile;

        $errKompres = kompresGambar($files['tmp_name'][$i], $tipe, $tujuan, MAX_UKURAN_MB * 1024 * 1024);
        if ($errKompres !== null) {
            $errors[] = "{$namaAsli}: {$errKompres}";
            continue;
        }

        $captionMentah = trim((string) ($captions[$i] ?? ''));
        $fotoTersimpan[] = [
            'path'    => 'uploads/kegiatan_revisi/' . $namaFile,
            'caption' => $captionMentah !== '' ? mb_substr($captionMentah, 0, 255) : null,
        ];
    }

    return ['foto' => $fotoTersimpan, 'errors' => $errors];
}

function updateFotoUtamaOtomatis(PDO $pdo, int $kegiatanId): void
{
    $cek = $pdo->prepare('SELECT foto_utama FROM kegiatan WHERE id = :id');
    $cek->execute(['id' => $kegiatanId]);
    $current = $cek->fetch()['foto_utama'] ?? null;

    if ($current) return; // sudah ada, gak usah diubah

    $stmt = $pdo->prepare('SELECT path_foto FROM kegiatan_foto WHERE kegiatan_id = :id ORDER BY urutan ASC LIMIT 1');
    $stmt->execute(['id' => $kegiatanId]);
    $foto = $stmt->fetch();

    if ($foto) {
        $pdo->prepare('UPDATE kegiatan SET foto_utama = :path WHERE id = :id')
            ->execute(['path' => $foto['path_foto'], 'id' => $kegiatanId]);
    }
}

/** Update caption foto-foto lama (yang udah ada di kegiatan_foto) buat 1 kegiatan.
 *  $captions berupa array asosiatif [id_foto => teks_caption], biasanya dari
 *  $_POST['caption_lama']. id yang gak dikirim gak disentuh sama sekali (caption
 *  lama tetap seperti semula); id yang gak ada / bukan milik kegiatan ini diabaikan
 *  diam-diam lewat kondisi kegiatan_id di WHERE. Caption kosong disimpan sebagai NULL. */
function simpanCaptionFotoLama(PDO $pdo, int $kegiatanId, array $captions): void
{
    if (empty($captions)) return;

    $stmt = $pdo->prepare(
        'UPDATE kegiatan_foto SET caption = :caption WHERE id = :fid AND kegiatan_id = :kid'
    );

    foreach ($captions as $fotoId => $captionMentah) {
        $fotoId = (int) $fotoId;
        if ($fotoId <= 0) continue;

        $captionMentah = trim((string) $captionMentah);
        $caption = $captionMentah !== '' ? mb_substr($captionMentah, 0, 255) : null;

        $stmt->execute(['caption' => $caption, 'fid' => $fotoId, 'kid' => $kegiatanId]);
    }
}