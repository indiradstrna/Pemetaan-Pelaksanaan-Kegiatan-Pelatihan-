-- Migration: Tabel untuk fitur Lapor Capaian M&E
-- Database: biotrop_gis_kegiatan
-- Tanggal: 2026-09-02

-- ============================================
-- Tabel: capaian_monev
-- Deskripsi: Menyimpan laporan capaian M&E dari sekolah
-- ============================================
CREATE TABLE IF NOT EXISTS `capaian_monev` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sekolah_id` INT NOT NULL COMMENT 'ID sekolah (user_id dari tabel user)',
  `program_id` INT NOT NULL COMMENT 'ID program pelaporan',
  `indikator_no` INT NOT NULL COMMENT 'Nomor urut indikator',
  `status` ENUM('belum_mulai', 'sedang_berjalan', 'sudah_tercapai') COMMENT 'Status penerapan indikator',
  `dampak_json` JSON COMMENT 'Data kuantitatif dampak dalam format JSON',
  `foto_path` VARCHAR(255) COMMENT 'Path ke file foto/bukti upload',
  `kendala` TEXT COMMENT 'Kendala/hambatan dalam implementasi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Foreign Keys
  CONSTRAINT fk_capaian_monev_sekolah FOREIGN KEY (sekolah_id) 
    REFERENCES `user`(id) ON DELETE CASCADE ON UPDATE CASCADE,
  
  -- Indexes untuk performa query
  KEY idx_sekolah_program (sekolah_id, program_id),
  KEY idx_created_at (created_at),
  UNIQUE KEY uk_sekolah_program_indikator (sekolah_id, program_id, indikator_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tabel penyimpanan laporan capaian M&E dari portal sekolah';

-- ============================================
-- Tabel: program_pelaporan (Reference Table)
-- Deskripsi: Master data program/kegiatan yang dapat dilaporkan
-- ============================================
CREATE TABLE IF NOT EXISTS `program_pelaporan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_program` VARCHAR(255) NOT NULL COMMENT 'Nama program/kegiatan',
  `bulan_pelaporan` VARCHAR(50) COMMENT 'Bulan periode pelaporan (contoh: Agustus 2026)',
  `tahun` YEAR COMMENT 'Tahun pelaksanaan',
  `deskripsi` TEXT,
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Status aktif program',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  KEY idx_is_active (is_active),
  KEY idx_tahun (tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Master data program pelaporan M&E';

-- ============================================
-- Tabel: indikator_program (Reference Table)
-- Deskripsi: Master data indikator untuk setiap program
-- ============================================
CREATE TABLE IF NOT EXISTS `indikator_program` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_id` INT NOT NULL,
  `nomor_urut` INT NOT NULL COMMENT 'Nomor urut indikator',
  `nama_indikator` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT,
  `icon_class` VARCHAR(100) COMMENT 'Font Awesome class untuk icon (contoh: fa-sprout)',
  `has_quantitative_input` TINYINT(1) DEFAULT 0 COMMENT 'Ada input data kuantitatif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_indikator_program FOREIGN KEY (program_id) 
    REFERENCES `program_pelaporan`(id) ON DELETE CASCADE ON UPDATE CASCADE,
  
  KEY idx_program_id (program_id),
  UNIQUE KEY uk_program_nomor (program_id, nomor_urut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Master data indikator untuk setiap program pelaporan';

-- ============================================
-- Tabel: field_kuantitatif_indikator (Reference Table)
-- Deskripsi: Definisi field input kuantitatif untuk setiap indikator
-- ============================================
CREATE TABLE IF NOT EXISTS `field_kuantitatif_indikator` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `indikator_id` INT NOT NULL,
  `field_name` VARCHAR(100) NOT NULL COMMENT 'Nama field (contoh: guru_terlibat)',
  `label` VARCHAR(255) NOT NULL COMMENT 'Label tampilan (contoh: Jumlah Guru Terlibat (L/P))',
  `satuan` VARCHAR(50) COMMENT 'Satuan (contoh: orang)',
  `urutan` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_field_indikator FOREIGN KEY (indikator_id) 
    REFERENCES `indikator_program`(id) ON DELETE CASCADE ON UPDATE CASCADE,
  
  KEY idx_indikator_id (indikator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Definisi field input kuantitatif untuk indikator';

-- ============================================
-- Dummy Data untuk Testing
-- ============================================

-- Insert Program Pelaporan
INSERT INTO `program_pelaporan` (nama_program, bulan_pelaporan, tahun, deskripsi, is_active)
VALUES 
  ('Smart Urban Farming', 'Agustus 2026', 2026, 'Program Pertanian Perkotaan Pintar', 1),
  ('Circular Economy', 'Juli 2026', 2026, 'Program Ekonomi Sirkular', 1),
  ('Smart Urban Farming', 'Juni 2026', 2026, 'Program Pertanian Perkotaan Pintar', 1);

-- Insert Indikator untuk Program Smart Urban Farming (Program ID 1)
INSERT INTO `indikator_program` (program_id, nomor_urut, nama_indikator, deskripsi, icon_class, has_quantitative_input)
VALUES 
  (1, 1, 'Penyediaan Lahan Gantung Kebun Gantung', 'Penyediaan dan pemasangan sistem lahan gantung', 'fa-sprout', 1),
  (1, 2, 'Penanaman Bibit Sayuran', 'Penanaman bibit sayuran organik', 'fa-leaf', 1),
  (1, 3, 'Pengomposan Limbah Organik', 'Program pengomposan limbah organik sekolah', 'fa-recycle', 0);

-- Insert Field Kuantitatif untuk Indikator 1
INSERT INTO `field_kuantitatif_indikator` (indikator_id, field_name, label, satuan, urutan)
VALUES 
  (1, 'guru_terlibat', 'Jumlah Guru Terlibat (L/P)', 'orang', 1),
  (1, 'siswa_terdampak', 'Siswa Terdampak (L/P)', 'orang', 2);

-- Insert Field Kuantitatif untuk Indikator 2
INSERT INTO `field_kuantitatif_indikator` (indikator_id, field_name, label, satuan, urutan)
VALUES 
  (2, 'guru_sayuran', 'Guru', 'orang', 1),
  (2, 'siswa_sayuran', 'Siswa', 'orang', 2);

-- ============================================
-- Create Directory untuk Upload Bukti
-- ============================================
-- Catatan: Jalankan command berikut di terminal untuk membuat folder:
-- mkdir -p /var/www/html/biotrop-gis/uploads/monev_bukti
-- chmod 755 /var/www/html/biotrop-gis/uploads/monev_bukti
