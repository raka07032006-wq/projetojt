-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `audit_5r` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `audit_5r`;

-- Drop existing tables to ensure schema changes are applied
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `finding_images`;
DROP TABLE IF EXISTS `findings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `area_evaluations`;
DROP TABLE IF EXISTS `areas`;
DROP TABLE IF EXISTS `divisions`;

-- Table for Divisions (8 specific divisions)
CREATE TABLE IF NOT EXISTS `divisions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `akses_perbaikan` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table for Predefined Areas
CREATE TABLE IF NOT EXISTS `areas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `division_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`division_id`) REFERENCES `divisions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table for Area Evaluations (Nilai 5R per Bulan/Tahun)
CREATE TABLE IF NOT EXISTS `area_evaluations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `area_id` INT NOT NULL,
  `bulan` TINYINT NOT NULL CHECK (`bulan` BETWEEN 1 AND 12),
  `tahun` INT NOT NULL,
  `nilai_5r` DECIMAL(3,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_area_month_year` (`area_id`, `bulan`, `tahun`)
) ENGINE=InnoDB;


-- Table for Users (Admin and Division accounts)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'division') NOT NULL DEFAULT 'division',
  `division_id` INT DEFAULT NULL,
  `area_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`division_id`) REFERENCES `divisions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table for 5R Audit Findings (Temuan Audit)
-- Area is now manual typing text (VARCHAR)
CREATE TABLE IF NOT EXISTS `findings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `area` VARCHAR(150) NOT NULL,
  `division_id` INT NOT NULL,
  `description` TEXT NOT NULL,
  `pic` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('Pending', 'On Progress', 'Done') NOT NULL DEFAULT 'On Progress',
  `improvement_description` TEXT DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`division_id`) REFERENCES `divisions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table for Finding Images
CREATE TABLE IF NOT EXISTS `finding_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `finding_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `type` ENUM('before', 'after') NOT NULL DEFAULT 'before',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`finding_id`) REFERENCES `findings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed default divisions (8 divisions from user request)
INSERT INTO `divisions` (`id`, `name`) VALUES
(1, 'RMT - Windriani'),
(2, 'Plastik - Supriyadi'),
(3, 'Insekfungi - Slamet'),
(4, 'Herbisida - Slamet'),
(5, 'FG & Logistik - Purwanto'),
(6, 'Maintenance - Abdul'),
(7, 'QC - Martya'),
(8, 'GA - Nur Fk')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed default Admin account (Username: admin, Password: admin123)
INSERT INTO `users` (`username`, `password`, `role`, `division_id`) VALUES
('admin', '$2y$10$22ZrJ0Agr7qsqpullwgHCOpSD0VwZjGcA7vS0dWKvMwo7YBR0q9pa', 'admin', NULL)
ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);

-- Table for Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `finding_id` INT NOT NULL,
  `division_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `recipient_role` ENUM('admin', 'division') NOT NULL DEFAULT 'admin',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`finding_id`) REFERENCES `findings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`division_id`) REFERENCES `divisions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed default areas for each division
INSERT INTO `areas` (`id`, `division_id`, `name`) VALUES
(1, 1, 'Gudang Bahan Baku If Cf ( A1 )'),
(2, 1, 'Gudang Bahan Baku If Cf Stiker ( B1 )'),
(3, 1, 'Gudang bahan baku metyl, aux, mp dan stiker ( F3 )'),
(4, 1, 'Gudang bahan baku gliphosate ( F2 )'),
(5, 1, 'Gudang bahan baku paraquat ( F1 )'),
(6, 1, 'Gudang ( I 1 ) bahan baku gliposate, paraquat, mp, aux'),
(7, 1, 'Gudang ( I 2 ) karton box'),
(8, 1, 'Gudang bahan baku assembling I3'),
(9, 1, 'Gudang bahan baku assembling I4'),
(10, 1, 'Gudang barang jadi tutup botol D4'),
(11, 1, 'Gudang bb botol D5'),
(12, 1, 'Gudang bahan baku mulsa'),
(13, 2, 'Area Blow dan PET'),
(14, 2, 'Area Inject'),
(15, 2, 'Area Mixing'),
(16, 2, 'Area Crusher'),
(17, 2, 'Area Welding'),
(18, 2, 'Area Kantor Plastik'),
(19, 2, 'Area Produksi Mulsa'),
(20, 2, 'Area Mulsa Recycle'),
(21, 2, 'Area Mulsa Mixing'),
(22, 2, 'Area Kantor Mulsa'),
(23, 2, 'Area Mulsa Granulator'),
(24, 2, 'Assembling Perakitan'),
(25, 2, 'Assembling Area Sablon'),
(26, 2, 'Assembling Kantor'),
(27, 3, 'Produksi Centafur'),
(28, 3, 'IF Packing Gd. B4'),
(29, 3, 'IF Mixer Gd. B1'),
(30, 3, 'Methyl'),
(31, 3, 'Jetmill'),
(32, 3, 'MP Packing'),
(33, 3, 'Starkum'),
(34, 4, 'Filling E1'),
(35, 4, 'Filling F4'),
(36, 4, 'Area ka.shift dan operator reaktor'),
(37, 4, 'Area panel kontrol'),
(38, 4, 'Reaktor glyposate bagian atas'),
(39, 4, 'Reaktor paraquat bagian atas'),
(40, 4, 'Reaktor aux bagian atas'),
(41, 4, 'Reaktor glyposate bagian bawah'),
(42, 4, 'Reaktor paraquat bagian bawah'),
(43, 4, 'Reaktor aux bagian bawah'),
(44, 4, 'Tangki amonia'),
(45, 5, 'Area loading 1'),
(46, 5, 'Area loading 2'),
(47, 5, 'Gudang barang jadi Glyposate ( F5 )'),
(48, 5, 'Gudang barang jadi Centafur ( A2 )'),
(49, 5, 'Gudang barang jadi Insect Fungi (A3)'),
(50, 5, 'Gudang barang jadi assembling'),
(51, 5, 'Gudang barang jadi mulsa'),
(52, 5, 'Gudang BJ J ( BARAT )'),
(53, 6, 'Otomotif'),
(54, 6, 'Area penyimpanan change part CF'),
(55, 6, 'Mtc filling'),
(56, 6, 'Cooling tower & WTP'),
(57, 6, 'Workshop utility'),
(58, 6, 'Kantor engineering'),
(59, 6, 'Chiller'),
(60, 6, 'Genset & cmp gl'),
(61, 6, 'Compressor hanbell'),
(62, 6, 'Gudang pestisida'),
(63, 6, 'Gudang plastik'),
(64, 6, 'Area penyimpanan change part MP'),
(65, 6, 'Area panel CF'),
(66, 6, 'Area panel MP jetmil'),
(67, 6, 'Area panel MT'),
(68, 6, 'Ruang panel Glyposate'),
(69, 6, 'Kantor engineering Plastik'),
(70, 6, 'Workshop Plastik'),
(71, 7, 'Ruang office lab'),
(72, 7, 'Ruang sampel plastik'),
(73, 7, 'Ruang meeting lab'),
(74, 7, 'Ruang loby dan taman lab'),
(75, 7, 'Ruang instrumen'),
(76, 7, 'Ruang arsip sampel'),
(77, 7, 'Ruang formulasi RnD'),
(78, 7, 'Ruang preparasi'),
(79, 7, 'Ruang oven dan timbangan'),
(80, 7, 'Ruang mikrobiologi'),
(81, 7, 'Ruang QC plastik'),
(82, 7, 'Ruang QC assembling'),
(83, 7, 'Minilab filling'),
(84, 7, 'Minilab reaktor'),
(85, 7, 'Ruang workshop RnD'),
(86, 7, 'Minilab CF-IF'),
(87, 8, 'Kantor lantai 1'),
(88, 8, 'Kantor lantai 2'),
(89, 8, 'Gudang IT'),
(90, 8, 'Kantin atas'),
(91, 8, 'Kantin bawah'),
(92, 8, 'Mushola atas'),
(93, 8, 'Mushola bawah'),
(94, 8, 'Gazebo-assembling'),
(95, 8, 'Mulsa-Kantin'),
(96, 8, 'Gerbang- CF IF'),
(97, 8, 'TPS'),
(98, 8, 'Ruang Dokumen')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

