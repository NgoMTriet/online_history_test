-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 22, 2026 at 03:21 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `history`
--

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` int NOT NULL,
  `question_id` int NOT NULL,
  `option_code` enum('A','B','C','D') NOT NULL,
  `content` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `is_fixed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `question_id`, `option_code`, `content`, `is_correct`, `is_fixed`) VALUES
(99, 31, 'A', 'Các vua Hùng', 1, 0),
(100, 31, 'B', 'An Dương Vương', 0, 0),
(101, 31, 'C', 'Triệu Đà', 0, 0),
(102, 31, 'D', 'Ngô Quyền', 0, 0),
(103, 32, 'A', 'Phong Châu', 0, 0),
(104, 32, 'B', 'Cổ Loa', 1, 0),
(105, 32, 'C', 'Hoa Lư', 0, 0),
(106, 32, 'D', 'Thăng Long', 0, 0),
(107, 33, 'A', 'Năm 40', 1, 0),
(108, 33, 'B', 'Năm 248', 0, 0),
(109, 33, 'C', 'Năm 542', 0, 0),
(110, 33, 'D', 'Năm 938', 0, 0),
(111, 34, 'A', 'Lý Bí', 0, 0),
(112, 34, 'B', 'Phùng Hưng', 0, 0),
(113, 34, 'C', 'Triệu Thị Trinh', 1, 0),
(114, 34, 'D', 'Mai Thúc Loan', 0, 0),
(115, 35, 'A', 'Lê Hoàn', 0, 0),
(116, 35, 'B', 'Ngô Quyền', 1, 0),
(117, 35, 'C', 'Trần Hưng Đạo', 0, 0),
(118, 35, 'D', 'Lý Thường Kiệt', 0, 0),
(119, 36, 'A', 'Sông có địa thế hiểm yếu, thuận lợi bố trí trận địa cọc và lợi dụng thủy triều', 1, 0),
(120, 36, 'B', 'Đây là nơi có kinh đô của nhà Nam Hán', 0, 0),
(121, 36, 'C', 'Quân Nam Hán không biết đi thuyền', 0, 0),
(122, 36, 'D', 'Sông nằm sâu trong lãnh thổ Trung Quốc', 0, 0),
(123, 37, 'A', 'Mở đầu thời kì Bắc thuộc', 0, 0),
(124, 37, 'B', 'Chấm dứt hoàn toàn mọi cuộc chiến tranh trong khu vực', 0, 0),
(125, 37, 'C', 'Đập tan ý chí xâm lược của tất cả các nước láng giềng', 0, 0),
(126, 37, 'D', 'Chấm dứt hơn một nghìn năm Bắc thuộc, mở ra thời kì độc lập tự chủ lâu dài', 1, 0),
(127, 38, 'A', 'Nhà Đinh', 1, 0),
(128, 38, 'B', 'Nhà Lý', 0, 0),
(129, 38, 'C', 'Nhà Trần', 0, 0),
(130, 38, 'D', 'Nhà Hồ', 0, 0),
(131, 39, 'A', 'Đinh Tiên Hoàng', 0, 0),
(132, 39, 'B', 'Lê Đại Hành', 0, 0),
(133, 39, 'C', 'Lý Công Uẩn', 1, 0),
(134, 39, 'D', 'Trần Thái Tông', 0, 0),
(135, 40, 'A', 'Chi Lăng', 0, 0),
(136, 40, 'B', 'Rạch Gầm – Xoài Mút', 0, 0),
(137, 40, 'C', 'Đống Đa', 0, 0),
(138, 40, 'D', 'Bạch Đằng', 1, 0),
(179, 51, 'A', 'Hương Cảng (Trung Quốc)', 1, 0),
(180, 51, 'B', 'Quảng Châu (Trung Quốc)', 0, 0),
(181, 51, 'C', 'Cao Bằng (Việt Nam)', 0, 0),
(182, 51, 'D', 'Hà Nội (Việt Nam)', 0, 0),
(183, 52, 'A', 'Trần Phú', 0, 0),
(184, 52, 'B', 'Nguyễn Ái Quốc', 1, 0),
(185, 52, 'C', 'Lê Hồng Phong', 0, 0),
(186, 52, 'D', 'Hà Huy Tập', 0, 0),
(187, 53, 'A', 'Đông Dương Cộng sản đảng và An Nam Cộng sản đảng', 1, 0),
(188, 53, 'B', 'Đông Dương Cộng sản liên đoàn và An Nam Cộng sản đảng', 0, 0),
(189, 53, 'C', 'Đông Dương Cộng sản đảng và Việt Nam Quốc dân đảng', 0, 0),
(190, 53, 'D', 'Tân Việt Cách mạng đảng và An Nam Cộng sản đảng', 0, 0),
(191, 54, 'A', 'Trần Phú', 0, 0),
(192, 54, 'B', 'Nguyễn Ái Quốc', 1, 0),
(193, 54, 'C', 'Trường Chinh', 0, 0),
(194, 54, 'D', 'Lê Hồng Phong', 0, 0),
(195, 55, 'A', 'Nguyễn Ái Quốc', 0, 0),
(196, 55, 'B', 'Trần Phú', 1, 0),
(197, 55, 'C', 'Hà Huy Tập', 0, 0),
(198, 55, 'D', 'Nguyễn Văn Cừ', 0, 0),
(199, 56, 'A', 'Nguyễn Ái Quốc', 0, 0),
(200, 56, 'B', 'Trần Phú', 1, 0),
(201, 56, 'C', 'Lê Hồng Phong', 0, 0),
(202, 56, 'D', 'Hà Huy Tập', 0, 0),
(203, 57, 'A', 'Chính phủ lâm thời Việt Nam Dân chủ Cộng hòa', 0, 0),
(204, 57, 'B', 'Các Xô viết ở Nghệ An và Hà Tĩnh', 1, 0),
(205, 57, 'C', 'Ủy ban dân tộc giải phóng', 0, 0),
(206, 57, 'D', 'Mặt trận Việt Minh', 0, 0),
(207, 58, 'A', 'Lần đầu tiên giai cấp công nhân lãnh đạo phong trào với sự liên minh chặt chẽ với nông dân', 1, 0),
(208, 58, 'B', 'Chỉ dùng phương pháp đấu tranh hòa bình hợp pháp', 0, 0),
(209, 58, 'C', 'Do giai cấp tư sản dân tộc khởi xướng và lãnh đạo', 0, 0),
(210, 58, 'D', 'Diễn ra chủ yếu ở các đô thị lớn', 0, 0),
(211, 59, 'A', 'Mở ra một bước ngoặt vĩ đại trong lịch sử cách mạng Việt Nam, chấm dứt khủng hoảng đường lối', 1, 0),
(212, 59, 'B', 'Đánh đổ hoàn toàn ách đô hộ của thực dân Pháp', 0, 0),
(213, 59, 'C', 'Giành lại độc lập hoàn toàn cho đất nước ngay trong năm 1930', 0, 0),
(214, 59, 'D', 'Thành lập ngay chính quyền cách mạng trong toàn quốc', 0, 0),
(215, 60, 'A', 'Khởi nghĩa Yên Bái thất bại (1930)', 0, 0),
(216, 60, 'B', 'Đảng Cộng sản Việt Nam được thành lập (1930)', 1, 0),
(217, 60, 'C', 'Phong trào Xô viết Nghệ - Tĩnh bùng nổ (1930)', 0, 0),
(218, 60, 'D', 'Nguyễn Ái Quốc gửi Bổn yêu sách đến Hội nghị Ve-xai (1919)', 0, 0),
(219, 61, 'A', 'a', 1, 0),
(220, 61, 'B', 'b', 0, 0),
(221, 61, 'C', 'c', 0, 0),
(222, 61, 'D', 'd', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `exam_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `grade` int NOT NULL,
  `duration` int NOT NULL,
  `max_attempts` int DEFAULT '0',
  `exam_password` varchar(255) DEFAULT NULL,
  `total_questions` int NOT NULL,
  `is_randomized` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `exam`
--

INSERT INTO `exam` (`exam_id`, `title`, `grade`, `duration`, `max_attempts`, `exam_password`, `total_questions`, `is_randomized`, `created_at`) VALUES
(1, 'ls6', 6, 15, 0, NULL, 1, 0, '2026-10-14 12:34:48'),
(2, 'ls6-15p', 6, 15, 0, NULL, 2, 0, '2026-10-14 12:50:40'),
(3, '15p_ls6', 6, 1, 0, NULL, 10, 0, '2026-10-17 03:21:23'),
(4, 'ls6_gk', 6, 1, 0, NULL, 5, 1, '2026-10-17 03:21:50'),
(5, 'ls6_nbn', 6, 1, 0, NULL, 10, 0, '2026-10-17 03:51:09'),
(6, 'ls6.1', 6, 1, 0, NULL, 10, 1, '2026-10-17 04:11:25'),
(7, 'ls9', 9, 1, 0, NULL, 10, 0, '2026-10-18 15:04:24'),
(8, 'ls9_coyen', 9, 1, 1, NULL, 2, 1, '2026-10-22 01:51:43'),
(9, 'ls6_coyen', 6, 1, 2, NULL, 5, 1, '2026-10-22 02:04:37'),
(10, 'ls6_1.1.1', 9, 1, 2, '1234', 5, 0, '2026-10-22 02:43:19');

-- --------------------------------------------------------

--
-- Table structure for table `exam_questions`
--

CREATE TABLE `exam_questions` (
  `exam_id` int NOT NULL,
  `question_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exam_questions`
--

INSERT INTO `exam_questions` (`exam_id`, `question_id`) VALUES
(6, 31),
(6, 32),
(6, 33),
(6, 34),
(6, 35),
(9, 35),
(6, 36),
(9, 36),
(6, 37),
(9, 37),
(6, 38),
(9, 38),
(6, 39),
(6, 40),
(9, 40),
(7, 51),
(7, 52),
(8, 52),
(7, 53),
(7, 54),
(7, 55),
(7, 56),
(10, 56),
(7, 57),
(10, 57),
(7, 58),
(10, 58),
(7, 59),
(10, 59),
(7, 60),
(8, 60),
(10, 60);

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `result_id` int NOT NULL,
  `user_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `score` decimal(4,2) NOT NULL DEFAULT '0.00',
  `correct_answers` int DEFAULT '0',
  `correct_answers_count` int NOT NULL DEFAULT '0',
  `total_questions` int NOT NULL,
  `completion_time` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('PASSED','FAILED') NOT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`result_id`, `user_id`, `exam_id`, `score`, `correct_answers`, `correct_answers_count`, `total_questions`, `completion_time`, `created_at`, `status`, `submitted_at`) VALUES
(1, 1, 1, 10.00, 0, 1, 1, 900, '2026-10-22 02:12:52', 'PASSED', '2026-10-14 12:47:39'),
(2, 1, 2, 10.00, 0, 2, 2, 900, '2026-10-22 02:12:52', 'PASSED', '2026-10-14 12:51:13'),
(3, 1, 5, 3.00, 0, 3, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 03:58:00'),
(4, 1, 5, 3.00, 0, 3, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:01:32'),
(5, 1, 5, 3.00, 0, 3, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:01:38'),
(6, 1, 5, 3.00, 0, 3, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:04:57'),
(7, 1, 5, 3.00, 0, 3, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:05:27'),
(8, 1, 6, 3.00, 0, 3, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:12:00'),
(9, 1, 6, 2.00, 0, 2, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:13:19'),
(10, 1, 6, 2.00, 0, 2, 10, 900, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:13:20'),
(11, 1, 6, 4.00, 0, 4, 10, 11, '2026-10-22 02:12:52', 'FAILED', '2026-10-17 04:23:25'),
(12, 2, 6, 3.00, 0, 3, 10, 16, '2026-10-22 02:12:52', 'FAILED', '2026-10-18 14:52:42'),
(13, 1, 6, 6.00, 0, 6, 10, 12, '2026-10-22 02:12:52', 'PASSED', '2026-10-18 14:54:43'),
(14, 1, 7, 3.00, 0, 3, 10, 14, '2026-10-22 02:12:52', 'FAILED', '2026-10-18 15:04:54'),
(15, 1, 7, 4.00, 0, 4, 10, 14, '2026-10-22 02:12:52', 'FAILED', '2026-10-19 00:30:43'),
(16, 1, 7, 1.00, 0, 1, 10, 20, '2026-10-22 02:12:52', 'FAILED', '2026-10-22 01:39:45'),
(17, 1, 8, 0.00, 0, 0, 2, 7, '2026-10-22 02:12:52', 'FAILED', '2026-10-22 01:52:13'),
(18, 1, 8, 0.00, 0, 0, 2, 5, '2026-10-22 02:12:52', 'FAILED', '2026-10-22 01:52:25'),
(19, 1, 8, 0.00, 0, 0, 2, 5, '2026-10-22 02:12:52', 'FAILED', '2026-10-22 01:52:34'),
(20, 1, 8, 0.00, 0, 0, 2, 5, '2026-10-22 02:12:52', 'FAILED', '2026-10-22 02:03:32'),
(21, 1, 9, 0.00, 0, 0, 5, 9, '2026-10-22 02:12:52', 'FAILED', '2026-10-22 02:04:54'),
(22, 1, 9, 2.00, 0, 1, 5, 8, '2026-10-22 02:12:52', 'FAILED', '2026-10-22 02:05:08'),
(23, 1, 10, 2.00, 0, 1, 5, 13, '2026-10-22 02:44:47', 'FAILED', '2026-10-22 02:44:47'),
(24, 1, 10, 4.00, 0, 2, 5, 14, '2026-10-22 02:45:04', 'FAILED', '2026-10-22 02:45:04'),
(25, 1, 6, 4.00, 0, 4, 10, 12, '2026-10-22 03:05:50', 'FAILED', '2026-10-22 03:05:50');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `used_at` datetime DEFAULT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `expires_at`, `created_at`, `used_at`, `token_hash`) VALUES
(1, 1, '2026-10-19 02:02:31', '2026-10-19 01:02:31', '2026-10-19 08:02:38', '6b7d49e84d4965cc3048c31b4f35b1856ef9a659d34eedf718f7b6961aad82a1'),
(2, 1, '2026-10-19 02:02:38', '2026-10-19 01:02:38', '2026-10-19 08:14:39', 'd5dadb5482a0ed5e686e416cb2bcb554b5eb54a721a113045f68f588dfcd1810'),
(3, 1, '2026-10-19 02:14:39', '2026-10-19 01:14:39', '2026-10-19 08:16:04', 'a3e69b7a72ffd49af352dd703556072f9687d63c8d97084f5a0c76b0efdc7db8'),
(4, 2, '2026-10-19 02:28:49', '2026-10-19 01:28:49', NULL, '1590277e420c1b43c78e1df2b42b04945a37e14e42b872aea9e4aca377d307a5');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int NOT NULL,
  `topic_id` int NOT NULL,
  `content` text NOT NULL,
  `difficulty` enum('EASY','MEDIUM','HARD') NOT NULL,
  `explanation` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `topic_id`, `content`, `difficulty`, `explanation`) VALUES
(31, 3, 'Nhà nước Văn Lang ra đời gắn với các vua nào?', 'EASY', 'Nhà nước Văn Lang ra đời vào khoảng thế kỉ VII TCN, đứng đầu là các vua Hùng.'),
(32, 3, 'Kinh đô của nước Âu Lạc được đặt ở đâu?', 'EASY', 'An Dương Vương xây dựng kinh đô Âu Lạc ở Cổ Loa (nay thuộc Hà Nội).'),
(33, 3, 'Cuộc khởi nghĩa Hai Bà Trưng bùng nổ vào năm nào?', 'EASY', 'Cuộc khởi nghĩa Hai Bà Trưng bùng nổ vào mùa xuân năm 40.'),
(34, 3, 'Ai lãnh đạo cuộc khởi nghĩa Bà Triệu năm 248?', 'EASY', 'Triệu Thị Trinh, thường được gọi là Bà Triệu, lãnh đạo cuộc khởi nghĩa năm 248.'),
(35, 3, 'Chiến thắng Bạch Đằng năm 938 gắn liền với vị tướng nào?', 'EASY', 'Ngô Quyền lãnh đạo quân dân ta đánh bại quân Nam Hán trên sông Bạch Đằng năm 938.'),
(36, 3, 'Nguyên nhân trực tiếp khiến Ngô Quyền quyết định chọn sông Bạch Đằng làm nơi quyết chiến năm 938 là gì?', 'EASY', 'Địa hình sông Bạch Đằng và quy luật thủy triều giúp quân ta bố trí cọc ngầm, nhử địch vào trận địa.'),
(37, 3, 'Ý nghĩa lịch sử nổi bật của chiến thắng Bạch Đằng năm 938 là gì?', 'EASY', 'Chiến thắng năm 938 đánh bại quân Nam Hán và tạo bước ngoặt lớn cho nền độc lập của dân tộc.'),
(38, 3, 'Nhà nước Đại Cồ Việt được thành lập dưới triều đại nào?', 'EASY', 'Năm 968, Đinh Bộ Lĩnh thống nhất đất nước, lên ngôi hoàng đế và đặt quốc hiệu là Đại Cồ Việt.'),
(39, 3, 'Ai là người dời đô từ Hoa Lư ra Thăng Long năm 1010?', 'EASY', 'Năm 1010, Lý Công Uẩn ban Chiếu dời đô, chuyển kinh đô từ Hoa Lư ra Đại La và đổi tên thành Thăng Long.'),
(40, 3, 'Trong cuộc kháng chiến chống quân Mông–Nguyên lần thứ ba (1287–1288), chiến thắng nào có vai trò quyết định?', 'EASY', 'Năm 1288, quân Trần do Trần Hưng Đạo chỉ huy đánh bại thủy quân Nguyên trên sông Bạch Đằng.'),
(51, 5, 'Hội nghị thành lập Đảng Cộng sản Việt Nam (2/1930) diễn ra tại đâu?', 'EASY', 'Hội nghị thành lập Đảng diễn ra từ ngày 6-1-1930 đến đầu tháng 2-1930 tại Cửu Long (Hương Cảng, Trung Quốc) do Nguyễn Ái Quốc chủ trì.'),
(52, 5, 'Ai là người chủ trì Hội nghị hợp nhất các tổ chức cộng sản thành lập Đảng Cộng sản Việt Nam?', 'EASY', 'Nguyễn Ái Quốc là người chủ trì Hội nghị thành lập Đảng Cộng sản Việt Nam tại Hương Cảng, Trung Quốc.'),
(53, 5, 'Những tổ chức cộng sản nào đã tham gia hợp nhất thành Đảng Cộng sản Việt Nam đầu năm 1930?', 'EASY', 'Tại Hội nghị, hai tổ chức Đông Dương Cộng sản đảng và An Nam Cộng sản đảng đã hợp nhất thành Đảng Cộng sản Việt Nam (sau đó Đông Dương Cộng sản liên đoàn tiếp tục gia nhập).'),
(54, 5, 'Cương lĩnh chính trị đầu tiên của Đảng Cộng sản Việt Nam do ai soạn thảo?', 'EASY', 'Chánh cương tóm tắt và Sách lược tóm tắt do Nguyễn Ái Quốc soạn thảo được thông qua tại Hội nghị thành lập Đảng chính là Cương lĩnh chính trị đầu tiên.'),
(55, 5, 'Luận cương chính trị tháng 10/1930 do ai soạn thảo?', 'EASY', 'Hội nghị Ban Chấp hành Trung ương lâm thời Đảng Cộng sản Việt Nam họp tháng 10/1930 tại Hương Cảng đã thông qua Luận cương chính trị do Trần Phú soạn thảo.'),
(56, 5, 'Ai là Tổng Bí thư đầu tiên của Đảng Cộng sản Việt Nam (tháng 10/1930)?', 'EASY', 'Tại Hội nghị Trung ương tháng 10/1930, Trần Phú được bầu làm Tổng Bí thư đầu tiên của Đảng.'),
(57, 5, 'Đỉnh cao của phong trào cách mạng 1930 - 1931 ở Việt Nam là sự thành lập hình thức chính quyền nào?', 'EASY', 'Đỉnh cao của phong trào 1930 - 1931 là việc thành lập chính quyền công nông dưới hình thức các Xô viết ở một số xã thuộc hai tỉnh Nghệ An và Hà Tĩnh.'),
(58, 5, 'Đặc điểm nổi bật nhất của phong trào cách mạng 1930 - 1931 là gì?', 'EASY', 'Phong trào 1930-1931 đánh dấu lần đầu tiên giai cấp công nhân cùng nông dân dưới sự lãnh đạo của Đảng Cộng sản đứng lên đấu tranh quyết liệt chống đế quốc và phong kiến.'),
(59, 5, 'Ý nghĩa lịch sử quan trọng nhất của sự thành lập Đảng Cộng sản Việt Nam đầu năm 1930 là gì?', 'EASY', 'Đảng ra đời là bước ngoặt vĩ đại, chấm dứt thời kỳ khủng hoảng sâu sắc về đường lối và giai cấp lãnh đạo cách mạng Việt Nam.'),
(60, 5, 'Sự kiện nào đánh dấu bước ngoặt chấm dứt cuộc khủng hoảng về đường lối cứu nước ở Việt Nam đầu thế kỷ XX?', 'EASY', 'Sự ra đời của Đảng Cộng sản Việt Nam đầu năm 1930 với đường lối đúng đắn đã chấm dứt hoàn toàn sự khủng hoảng về đường lối và lực lượng lãnh đạo cách mạng Việt Nam.'),
(61, 5, 'cau hoi', 'EASY', 'k bt');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `result_id` int NOT NULL,
  `question_id` int NOT NULL,
  `selected_answer_id` int DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`result_id`, `question_id`, `selected_answer_id`, `is_correct`) VALUES
(8, 31, 100, 0),
(8, 32, 106, 0),
(8, 33, 108, 0),
(8, 34, 114, 0),
(8, 35, 116, 1),
(8, 36, 119, 1),
(8, 37, 124, 0),
(8, 38, 130, 0),
(8, 39, 133, 1),
(8, 40, 136, 0),
(9, 31, 102, 0),
(9, 32, 105, 0),
(9, 33, 110, 0),
(9, 34, 112, 0),
(9, 35, 117, 0),
(9, 36, 121, 0),
(9, 37, 125, 0),
(9, 38, 127, 1),
(9, 39, 133, 1),
(9, 40, 135, 0),
(10, 31, 102, 0),
(10, 32, 105, 0),
(10, 33, 110, 0),
(10, 34, 112, 0),
(10, 35, 117, 0),
(10, 36, 121, 0),
(10, 37, 125, 0),
(10, 38, 127, 1),
(10, 39, 133, 1),
(10, 40, 135, 0),
(11, 31, 101, 0),
(11, 32, 104, 1),
(11, 33, 109, 0),
(11, 34, 112, 0),
(11, 35, 117, 0),
(11, 36, 120, 0),
(11, 37, 126, 1),
(11, 38, 127, 1),
(11, 39, 133, 1),
(11, 40, 136, 0),
(12, 31, 99, 1),
(12, 32, 105, 0),
(12, 33, 110, 0),
(12, 34, 113, 1),
(12, 35, 117, 0),
(12, 36, 119, 1),
(12, 37, 124, 0),
(12, 38, 130, 0),
(12, 39, 131, 0),
(12, 40, 135, 0),
(13, 31, 99, 1),
(13, 32, 104, 1),
(13, 33, 107, 1),
(13, 34, 114, 0),
(13, 35, 116, 1),
(13, 36, 122, 0),
(13, 37, 126, 1),
(13, 38, 127, 1),
(13, 39, 132, 0),
(13, 40, 136, 0),
(14, 51, 181, 0),
(14, 52, 184, 1),
(14, 53, 189, 0),
(14, 54, 191, 0),
(14, 55, 196, 1),
(14, 56, 201, 0),
(14, 57, 204, 1),
(14, 58, 210, 0),
(14, 59, 213, 0),
(14, 60, 215, 0),
(15, 51, 181, 0),
(15, 52, 186, 0),
(15, 53, 188, 0),
(15, 54, 192, 1),
(15, 55, 196, 1),
(15, 56, 200, 1),
(15, 57, 206, 0),
(15, 58, 208, 0),
(15, 59, 214, 0),
(15, 60, 216, 1),
(16, 51, 179, 1),
(16, 52, 183, 0),
(16, 53, 190, 0),
(16, 54, 193, 0),
(16, 55, 197, 0),
(16, 56, 202, 0),
(16, 57, 205, 0),
(16, 58, 209, 0),
(16, 59, 213, 0),
(16, 60, 218, 0),
(17, 52, 183, 0),
(17, 60, 218, 0),
(18, 52, 186, 0),
(18, 60, 217, 0),
(19, 52, 185, 0),
(19, 60, 218, 0),
(20, 52, 185, 0),
(20, 60, 218, 0),
(21, 35, 118, 0),
(21, 36, 121, 0),
(21, 37, 123, 0),
(21, 38, 129, 0),
(21, 40, 136, 0),
(22, 35, 115, 0),
(22, 36, 121, 0),
(22, 37, 126, 1),
(22, 38, 128, 0),
(22, 40, 136, 0),
(23, 56, 199, 0),
(23, 57, 206, 0),
(23, 58, 210, 0),
(23, 59, 211, 1),
(23, 60, 217, 0),
(24, 56, 200, 1),
(24, 57, 203, 0),
(24, 58, 209, 0),
(24, 59, 213, 0),
(24, 60, 216, 1),
(25, 31, 99, 1),
(25, 32, 106, 0),
(25, 33, 109, 0),
(25, 34, 111, 0),
(25, 35, 115, 0),
(25, 36, 119, 1),
(25, 37, 124, 0),
(25, 38, 127, 1),
(25, 39, 133, 1),
(25, 40, 136, 0);

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `topic_id` int NOT NULL,
  `topic_name` varchar(150) NOT NULL,
  `grade` int NOT NULL,
  `description` text
) ;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`topic_id`, `topic_name`, `grade`, `description`) VALUES
(3, 'Chiến tranh thế giới', 6, NULL),
(4, 'tên chủ đề', 7, NULL),
(5, 'VN 1930', 9, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `class_name` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('STUDENT','TEACHER','ADMIN') NOT NULL DEFAULT 'STUDENT',
  `status` enum('ACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  `reset_otp` varchar(255) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `class_name`, `dob`, `email`, `role`, `status`, `reset_otp`, `otp_expires`) VALUES
(1, 'nbn', '$2y$10$PGvFkJiuI2M0bfI5xvO1f.5pexhib3SjDYodHR0eGvGCNE35dEMcy', 'Nguyễn Bình Nguyên', '6a3', '2006-07-19', 'nbnguyenktpm2411052@student.ctuet.edu.vn', 'STUDENT', 'ACTIVE', '$2y$10$WpiwiCDsVCvk9N7FmSGPcetCk5pJ8ekstY5i40kOnkp4ipg3NbtV.', '2026-10-18 21:55:34'),
(2, 'nguyen', '$2y$10$cvN6ZDFkNyMqzI0QtrdKeOz9wL0gmr8dm2EP.w9WQJfRXz0w.B/Ui', 'Nguyễn Bình Nguyên', NULL, NULL, 'binhnguyen2006.vx@gmail.com', 'TEACHER', 'ACTIVE', '$2y$10$XF2QqG1tGXjB8GNGl7VYJOMdDo.0EkILNgpfSY2qoZZOq.s5LZ2TS', '2026-10-17 12:08:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `idx_question_id` (`question_id`);

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`exam_id`);

--
-- Indexes for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`exam_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_resets_user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`result_id`,`question_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `selected_answer_id` (`selected_answer_id`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`topic_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `exam_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `result_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `topic_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `exam_results` (`result_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_3` FOREIGN KEY (`selected_answer_id`) REFERENCES `answers` (`answer_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
