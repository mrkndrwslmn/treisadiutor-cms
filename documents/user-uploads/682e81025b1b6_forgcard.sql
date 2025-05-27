-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 02:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `forgcard`
--

-- --------------------------------------------------------

--
-- Table structure for table `business_cards`
--

CREATE TABLE `business_cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `card_name` varchar(100) DEFAULT NULL,
  `card_data` text DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `share_link` varchar(255) DEFAULT NULL,
  `is_printed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_cards`
--

INSERT INTO `business_cards` (`id`, `user_id`, `template_id`, `card_name`, `card_data`, `qr_code_path`, `share_link`, `is_printed`, `created_at`) VALUES
(3, 1, 3, 'Sonya Morris', '{\"template_id\":\"3\",\"card_name\":\"Sonya Morris\",\"full_name\":\"Madison Elliott\",\"job_title\":\"Sed quasi in reicien\",\"email\":\"kuwynygov@treisadiutor.com\",\"phone\":\"+1 (186) 245-3963\",\"website\":\"https:\\/\\/www.hajycibibynuco.net\",\"social_links\":\"Cillum enim autem nu\",\"create_card\":\"\"}', '', '', 0, '2025-05-19 23:00:41'),
(4, 1, 1, 'Tucker Hopkins', '{\"template_id\":\"1\",\"card_name\":\"Tucker Hopkins\",\"full_name\":\"Jaquelyn Rollins\",\"job_title\":\"Aliqua Non sint qui\",\"email\":\"celalo@treisadiutor.com\",\"phone\":\"+1 (977) 445-9697\",\"website\":\"https:\\/\\/www.myrob.biz\",\"social_links\":\"Nulla reiciendis odi\",\"create_card\":\"\"}', '', '', 0, '2025-05-19 23:27:39'),
(5, 1, 2, 'Editor', '{\"template_id\":\"2\",\"card_name\":\"Editor\",\"full_name\":\"Kyla Rivero\",\"job_title\":\"Software Developer\",\"email\":\"kylarivero@treisadiutor.com\",\"phone\":\"3391278301\",\"website\":\"https:\\/\\/www,treisadiutor.com\",\"social_platforms\":[\"linkedin\",\"twitter\",\"instagram\"],\"social_urls\":[\"https:\\/\\/www.profile.com\\/example\",\"https:\\/\\/www.profile.com\\/example\",\"https:\\/\\/www.profile.com\\/example\"],\"create_card\":\"\"}', '', '', 0, '2025-05-20 02:15:41'),
(6, 1, 2, 'Editor', '{\"template_id\":\"2\",\"card_name\":\"Editor\",\"full_name\":\"Kyla Rivero\",\"job_title\":\"Software Developer\",\"email\":\"kylarivero@treisadiutor.com\",\"phone\":\"3391278301\",\"website\":\"https:\\/\\/www,treisadiutor.com\",\"social_platforms\":[\"linkedin\",\"twitter\",\"instagram\"],\"social_urls\":[\"https:\\/\\/www.profile.com\\/example\",\"https:\\/\\/www.profile.com\\/example\",\"https:\\/\\/www.profile.com\\/example\"],\"create_card\":\"\"}', '', '', 0, '2025-05-20 02:16:44'),
(7, 3, 5, 'Editor', '{\"template_id\":\"5\",\"card_name\":\"Editor\",\"full_name\":\"Woka Loka\",\"job_title\":\"Editor\",\"email\":\"woven@treisadiutor.com\",\"phone\":\"3391278301\",\"website\":\"https:\\/\\/wokapparel.com\",\"social_platforms\":[\"linkedin\",\"twitter\",\"instagram\"],\"social_urls\":[\"https:\\/\\/www.profile.com\\/example\",\"https:\\/\\/www.profile.com\\/example\",\"https:\\/\\/www.profile.com\\/example\"],\"create_card\":\"\"}', '', '', 0, '2025-05-20 11:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `card_info`
--

CREATE TABLE `card_info` (
  `id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `card_info`
--

INSERT INTO `card_info` (`id`, `card_id`, `full_name`, `job_title`, `email`, `phone`, `website`, `logo_path`) VALUES
(1, 3, 'Madison Elliott', 'Sed quasi in reicien', 'kuwynygov@treisadiutor.com', '+1 (186) 245-3963', 'https://www.hajycibibynuco.net', '../../uploads/682bb8191769e.png'),
(2, 4, 'Jaquelyn Rollins', 'Aliqua Non sint qui', 'celalo@treisadiutor.com', '+1 (977) 445-9697', 'https://www.myrob.biz', '../../uploads/682bbe6ba35f3.png'),
(3, 5, 'Kyla Rivero', 'Software Developer', 'kylarivero@treisadiutor.com', '3391278301', 'https://www,treisadiutor.com', '../../uploads/682be5cd0e805.png'),
(4, 6, 'Kyla Rivero', 'Software Developer', 'kylarivero@treisadiutor.com', '3391278301', 'https://www,treisadiutor.com', '../../uploads/682be60c19129.png'),
(5, 7, 'Woka Loka', 'Editor', 'woven@treisadiutor.com', '3391278301', 'https://wokapparel.com', '../../uploads/682c6d571bfa6.png');

-- --------------------------------------------------------

--
-- Table structure for table `card_social_links`
--

CREATE TABLE `card_social_links` (
  `id` int(11) NOT NULL,
  `card_id` int(11) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `link_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `card_social_links`
--

INSERT INTO `card_social_links` (`id`, `card_id`, `platform`, `link_url`) VALUES
(1, 6, 'linkedin', 'https://www.profile.com/example'),
(2, 6, 'twitter', 'https://www.profile.com/example'),
(3, 6, 'instagram', 'https://www.profile.com/example'),
(4, 7, 'linkedin', 'https://www.profile.com/example'),
(5, 7, 'twitter', 'https://www.profile.com/example'),
(6, 7, 'instagram', 'https://www.profile.com/example');

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `qr_text` text NOT NULL,
  `qr_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `template_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`id`, `name`, `thumbnail`, `template_path`, `is_active`) VALUES
(1, 'Template 1', NULL, '/gcard/templates/template1/index.php', 1),
(2, 'Template 2', NULL, '/gcard/templates/template2/index.php', 1),
(3, 'Template 3', NULL, '/gcard/templates/template3/index.php', 1),
(4, 'Template 4', NULL, '/gcard/templates/template4/index.php', 1),
(5, 'Template 5', NULL, '/gcard/templates/template5/index.php', 1),
(6, 'Template 6', NULL, '/gcard/templates/template6/index.php', 1),
(7, 'Template 7', NULL, '/gcard/templates/template7/index.php', 1),
(8, 'Template 8', NULL, '/gcard/templates/template8/index.php', 1),
(9, 'Template 9', NULL, '/gcard/templates/template9/index.php', 1),
(10, 'Template 10', NULL, '/gcard/templates/template10/index.php', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `profile_photo`, `role`, `created_at`) VALUES
(1, 'Hanna Rodriquez', 'pudixo@treisadiutor.com', '$2y$10$.QtPJ0scohg.fbI5S1Y/I.8f4GZJExIwZw1/sqpM3qC.J6POqUxpW', NULL, 'user', '2025-05-19 13:40:23'),
(2, 'Fredericka Harvey', 'faruxorilu@treisadiutor.com', '$2y$10$UXHAGD98MmMrQV.Q6tNguejAZGqnJYEnGTwnDOBWzhPuVUhzVWCOe', NULL, 'user', '2025-05-19 13:41:16'),
(3, 'Graham Ballard', 'woven@treisadiutor.com', '$2y$10$.QtPJ0scohg.fbI5S1Y/I.8f4GZJExIwZw1/sqpM3qC.J6POqUxpW', NULL, 'user', '2025-05-20 11:51:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `business_cards`
--
ALTER TABLE `business_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `card_info`
--
ALTER TABLE `card_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `card_id` (`card_id`);

--
-- Indexes for table `card_social_links`
--
ALTER TABLE `card_social_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `card_id` (`card_id`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `card_id` (`card_id`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `business_cards`
--
ALTER TABLE `business_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `card_info`
--
ALTER TABLE `card_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `card_social_links`
--
ALTER TABLE `card_social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `business_cards`
--
ALTER TABLE `business_cards`
  ADD CONSTRAINT `business_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_cards_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `card_info`
--
ALTER TABLE `card_info`
  ADD CONSTRAINT `card_info_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `business_cards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `card_social_links`
--
ALTER TABLE `card_social_links`
  ADD CONSTRAINT `card_social_links_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `card_info` (`card_id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `business_cards` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
