-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 19 Jun 2026 pada 12.03
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `audit_5r`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','division') NOT NULL DEFAULT 'division',
  `division_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `area_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `division_id`, `created_at`, `area_id`) VALUES
(1, 'admin', '$2y$10$uqH7Fbe/7WjT4cK9C2yMlux0W3gXm7H5rF5M9oI6oK3d2qK7O6yW2', 'admin', NULL, '2026-06-12 03:57:26', NULL),
(2, 'area_kantor_lantai_1', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 29),
(3, 'area_kantor_lantai_2', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 30),
(4, 'area_gudang_it', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 31),
(5, 'area_kantin_atas', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 32),
(6, 'area_kantin_bawah', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 33),
(7, 'area_mushola_atas', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 34),
(8, 'area_mushola_bawah', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 35),
(9, 'area_gazebo_assembling', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 36),
(10, 'area_mulsa_kantin', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 37),
(11, 'area_gerbang_cf_if', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 38),
(12, 'area_tps', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 39),
(13, 'area_ruang_dokumen', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-12 06:46:37', 40),
(14, 'area_area_blow_dan_pet', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 41),
(15, 'area_area_inject', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 42),
(16, 'area_area_mixing', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 43),
(17, 'area_area_crusher', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 44),
(18, 'area_area_welding', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 45),
(19, 'area_area_kantor_plastik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 46),
(20, 'area_area_produksi_mulsa', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 47),
(21, 'area_area_mulsa_recycle', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 48),
(22, 'area_area_mulsa_mixing', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 49),
(23, 'area_area_kantor_mulsa', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 50),
(24, 'area_area_mulsa_granulator', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 51),
(25, 'area_assembling_perakitan', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 52),
(26, 'area_assembling_area_sablon', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 53),
(27, 'area_assembling_kantor', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-12 06:46:37', 54),
(28, 'area_ruang_office_lab', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 55),
(29, 'area_ruang_sampel_plastik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 56),
(30, 'area_ruang_meeting_lab', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 57),
(31, 'area_ruang_loby_dan_taman_lab', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 58),
(32, 'area_ruang_instrumen', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 59),
(33, 'area_ruang_arsip_sampel', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 60),
(34, 'area_ruang_formulasi_rnd', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 61),
(35, 'area_ruang_preparasi', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 62),
(36, 'area_ruang_oven_dan_timbangan', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 63),
(37, 'area_ruang_mikrobiologi', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 64),
(38, 'area_ruang_qc_plastik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 65),
(39, 'area_ruang_qc_assembling', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 66),
(40, 'area_minilab_filling', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 67),
(41, 'area_minilab_reaktor', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 68),
(42, 'area_ruang_workshop_rnd', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 69),
(43, 'area_minilab_cf_if', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-12 06:46:37', 70),
(44, 'area_produksi_centafur', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-12 06:46:37', 71),
(45, 'area_if_packing_gd_b4', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-12 06:46:37', 72),
(46, 'area_if_mixer_gd_b1', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-12 06:46:37', 73),
(47, 'area_methyl', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-12 06:46:37', 74),
(48, 'area_jetmill', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-12 06:46:37', 75),
(49, 'area_mp_packing', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-12 06:46:37', 76),
(50, 'area_starkum', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-12 06:46:37', 77),
(51, 'area_filling_e1', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 78),
(52, 'area_filling_f4', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 79),
(53, 'area_area_ka_shift_dan_operator_reaktor', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 80),
(54, 'area_area_panel_kontrol', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 81),
(55, 'area_reaktor_glyposate_bagian_atas', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 82),
(56, 'area_reaktor_paraquat_bagian_atas', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 83),
(57, 'area_reaktor_aux_bagian_atas', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 84),
(58, 'area_reaktor_glyposate_bagian_bawah', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 85),
(59, 'area_reaktor_paraquat_bagian_bawah', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 86),
(60, 'area_reaktor_aux_bagian_bawah', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 87),
(61, 'area_tangki_amonia', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-12 06:46:37', 88),
(62, 'area_area_loading_1', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 89),
(63, 'area_area_loading_2', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 90),
(64, 'area_gudang_barang_jadi_glyposate_f5', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 91),
(65, 'area_gudang_barang_jadi_centafur_a2', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 92),
(66, 'area_gudang_barang_jadi_insect_fungi_a3', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 93),
(67, 'area_gudang_barang_jadi_assembling', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 94),
(68, 'area_gudang_barang_jadi_mulsa', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 95),
(69, 'area_gudang_bj_j_barat', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-12 06:46:37', 96),
(70, 'area_gudang_bahan_baku_if_cf_a1', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 97),
(71, 'area_gudang_bahan_baku_if_cf_stiker_b1', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 98),
(72, 'area_gudang_bahan_baku_metyl_aux_mp_dan_stiker_f3', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 99),
(73, 'area_gudang_bahan_baku_gliphosate_f2', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 100),
(74, 'area_gudang_bahan_baku_paraquat_f1', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 101),
(75, 'area_gudang_i_1_bahan_baku_gliposate_paraquat_mp_a', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 102),
(76, 'area_gudang_i_2_karton_box', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 103),
(77, 'area_gudang_bahan_baku_assembling_i3', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 104),
(78, 'area_gudang_bahan_baku_assembling_i4', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 105),
(79, 'area_gudang_barang_jadi_tutup_botol_d4', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 106),
(80, 'area_gudang_bb_botol_d5', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 107),
(81, 'area_gudang_bahan_baku_mulsa', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-12 06:46:37', 108),
(82, 'area_otomotif', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 109),
(83, 'area_area_penyimpanan_change_part_cf', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 110),
(84, 'area_mtc_filling', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 111),
(85, 'area_cooling_tower_wtp', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 112),
(86, 'area_workshop_utility', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 113),
(87, 'area_kantor_engineering', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 114),
(88, 'area_chiller', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 115),
(89, 'area_genset_cmp_gl', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 116),
(90, 'area_compressor_hanbell', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 117),
(91, 'area_gudang_pestisida', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 118),
(92, 'area_gudang_plastik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 119),
(93, 'area_area_penyimpanan_change_part_mp', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 120),
(94, 'area_area_panel_cf', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 121),
(95, 'area_area_panel_mp_jetmil', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 122),
(96, 'area_area_panel_mt', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 123),
(97, 'area_ruang_panel_glyposate', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 124),
(98, 'area_kantor_engineering_plastik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 125),
(99, 'area_workshop_plastik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-12 06:46:37', 126),
(100, 'divisi_rmt', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-15 01:14:27', NULL),
(101, 'divisi_plastik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 2, '2026-06-15 01:14:27', NULL),
(102, 'divisi_insekfungi', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 3, '2026-06-15 01:14:27', NULL),
(103, 'divisi_herbisida', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 4, '2026-06-15 01:14:27', NULL),
(104, 'divisi_fg_logistik', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 5, '2026-06-15 01:14:27', NULL),
(105, 'divisi_maintenance', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 6, '2026-06-15 01:14:27', NULL),
(106, 'divisi_qc', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 7, '2026-06-15 01:14:27', NULL),
(107, 'divisi_ga', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 8, '2026-06-15 01:14:27', NULL),
(108, 'kadiv_rmt', '$2y$10$OqmnBux8lxOPYSqup0xfDeyHwBWdck643fEOS8V1hKh/z0hIwxLHm', 'division', 1, '2026-06-15 02:48:43', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `division_id` (`division_id`),
  ADD KEY `fk_users_area` (`area_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
