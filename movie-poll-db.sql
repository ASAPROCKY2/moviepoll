-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2025 at 10:54 PM
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
-- Database: `movie-poll-db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$Bjc7S/iZRVX0hVBs60B/qucnA8vXNhyaI0GUncgh6U2q.umkq3ULi', NULL, '2025-04-04 15:47:15'),
(2, 'ian', 'admin1111', NULL, '2025-04-04 20:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `tmdb_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `poster_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `created_at` datetime DEFAULT current_timestamp(),
  `data` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `type`, `created_at`, `data`, `created_by`, `is_read`) VALUES
(1, '', NULL, 'admin', '2025-04-08 19:29:33', '{\"type\":\"admin\",\"subject\":\"New poll\",\"message\":\"new poll shall be created soon\",\"created_by\":2,\"created_at\":\"2025-04-08 19:29:33\"}', 2, 0),
(2, '', NULL, 'admin', '2025-04-08 19:30:12', '{\"type\":\"admin\",\"subject\":\"New poll\",\"message\":\"new poll shall be created soon\",\"created_by\":2,\"created_at\":\"2025-04-08 19:30:12\"}', 2, 0),
(3, '', NULL, 'admin', '2025-04-08 19:30:51', '{\"type\":\"admin\",\"subject\":\"New poll\",\"message\":\"new poll shall be created soon\",\"created_by\":2,\"created_at\":\"2025-04-08 19:30:51\"}', 2, 0),
(4, '', NULL, 'admin', '2025-04-09 23:09:29', '{\"type\":\"admin\",\"subject\":\"New poll\",\"message\":\"new poll will be available soon \",\"created_by\":2,\"created_at\":\"2025-04-09 23:09:29\"}', 2, 0),
(5, '', NULL, 'admin', '2025-04-11 14:35:05', '{\"type\":\"admin\",\"subject\":\"New poll\",\"message\":\"Be ready \",\"created_by\":2,\"created_at\":\"2025-04-11 14:35:05\"}', 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Movie Poll',
  `question` text NOT NULL,
  `categories` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` datetime DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`id`, `title`, `question`, `categories`, `image`, `image_url`, `created_at`, `expiry_date`, `end_date`, `active`, `status`) VALUES
(4, 'Movie Poll', 'What is your best Ryan reynolds movie ', 'Comedy', NULL, NULL, '2025-04-08 16:45:13', '2025-06-12 12:00:00', NULL, 0, 'active'),
(5, 'Movie Poll', 'whats the best Movie Of all Times ', 'Action', NULL, NULL, '2025-04-11 19:26:39', '2025-05-14 12:00:00', NULL, 1, 'active'),
(6, 'Movie Poll', 'Action genres to watch during Cinema ', 'Action', NULL, NULL, '2025-04-12 10:26:26', '2025-06-11 12:00:00', NULL, 1, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `text` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `trailer` varchar(255) DEFAULT NULL,
  `votes` int(11) DEFAULT 0,
  `movie_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poll_options`
--

INSERT INTO `poll_options` (`id`, `poll_id`, `text`, `image`, `trailer`, `votes`, `movie_id`) VALUES
(11, 4, 'Free Guy ', 'uploads/67f552995e530-free guy.jpeg', 'https://www.youtube.com/watch?v=X2m-08cOAbc', 0, NULL),
(12, 4, 'Deadpool 3 ', 'uploads/67f55299603fa-Deadpool.jpeg', 'https://www.youtube.com/watch?v=73_1biulkYk', 0, NULL),
(13, 4, 'The Hitman Bodyguard ', 'uploads/67f5529960c40-htiman bodyguard.jpeg', 'https://www.youtube.com/watch?v=IpKmt4MpctM', 0, NULL),
(14, 4, '6 underground ', NULL, 'https://www.youtube.com/watch?v=YLE85olJjp8', 0, NULL),
(15, 5, 'Game of thrones ', NULL, 'https://www.youtube.com/watch?v=X2m-08cOAbc', 0, NULL),
(16, 5, 'Deadpool 3 ', NULL, 'https://www.youtube.com/watch?v=73_1biulkYk', 0, NULL),
(17, 6, 'Citadel', 'uploads/67fa3fd2636dc-Citadel.jpg', 'https://www.youtube.com/watch?v=J-s8hMj9iLY', 0, NULL),
(18, 6, 'John Wick ', 'uploads/67fa3fd265724-john wick.jpg', 'https://www.youtube.com/watch?v=2AUmvWm5ZDQ', 0, NULL),
(19, 6, 'The Continental ', 'uploads/67fa3fd265fee-the continetal.jpg', 'https://www.youtube.com/watch?v=y3FzXBkCUAg', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `poll_views`
--

CREATE TABLE `poll_views` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `log_type` varchar(20) NOT NULL COMMENT 'login, security, admin, system',
  `username` varchar(50) DEFAULT NULL,
  `action` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `log_type`, `username`, `action`, `ip_address`, `timestamp`) VALUES
(1, 'login', 'admin', 'Successful login', '127.0.0.1', '2025-04-08 23:46:56'),
(2, 'security', 'admin', 'Failed login attempt', '192.168.1.100', '2025-04-08 23:46:56'),
(3, 'admin', 'admin', 'User created', '10.0.0.1', '2025-04-08 23:46:56');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `default_role` varchar(20) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'active',
  `bio` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`, `status`, `bio`, `avatar`) VALUES
(1, 'king of the North', 'snow@gmail.com', '$2y$10$m9wS/xSUk7F/ZZrLRUhI8uqRDuUQFFd6LP1puszzEgXseyAo5P3DC', 'user', '2025-04-04 16:42:08', 'active', 'King Of The North', 'uploads/avatars/user_1_1743896717.jpg'),
(2, 'kingslayer', 'kingslayer@gmail.com', '$2y$10$T60DvUNMyjMhkNPDB5zsJeC7JdIACu4dUZFgqBlc4ROgaNYcgdUo.', 'user', '2025-04-04 16:42:51', 'active', NULL, NULL),
(3, 'kendrick', 'Kdot@gmal.com', '$2y$10$o4x6YVnlzSt0TFffzmQU8.tAiDrvt6.IA6qrMw4fVSxeZ.8oF9vnC', 'user', '2025-04-05 19:18:24', 'active', NULL, NULL),
(4, 'john stones', 'stones@gmail.com', '$2y$10$njavb6NazVrx4mE00t/YPuT18BceMB2AWRxfs9vXqCRrDxwJpL4T2', 'user', '2025-04-05 20:05:03', 'active', 'King Of The North \r\nLeader Of The Great Wall \r\nMovie Enthusiast\r\nWon against The Battle in the North\r\nFirst to link up with the wildlinks', 'uploads/avatars/user_4.jpg'),
(5, 'Eazy E', 'Eazy@gmail.com', '$2y$10$Cqp8fmg00ciHc.uFD5WIC.HP0fSaprC4ux26aF8CeE6k5ljf0oYD6', 'user', '2025-04-07 18:37:19', 'active', 'Staright Outta Compton', 'uploads/avatars/user_5_1744214218.jpg'),
(6, 'The Weekend', 'weekend@gmail.com', '$2y$10$iLL0Gy6Z2Erm6BypB6erf.lZAx0YQX0vL9X51jQvtOIMp9SAn4nnK', 'user', '2025-04-09 16:52:06', 'active', NULL, NULL),
(7, 'phil Foden', 'foden@gmail.com', '$2y$10$sjkGSbaIm9RbGbTNmUiT0uwhL6YXfxCVZgRQZZOtN4rmoWjVN2J0G', 'user', '2025-04-09 19:24:08', 'active', NULL, NULL),
(8, 'jack ryan', 'jack@gmail.com', '$2y$10$eQ4jImkLsujq1LUCMyHO2.vJ7s4eiilS/9S/F26zC/d8hAuj0ICma', 'user', '2025-04-09 20:28:56', 'active', NULL, NULL),
(9, 'Ice cube', 'cube@gmail.com', '$2y$10$R9YPyzDZuwMAoIl7Ix02MeX9rx.1dh/QrGmrExgRl6.rkifASzHUC', 'user', '2025-04-09 20:49:24', 'active', NULL, NULL),
(10, 'SUNG jinwooo', 'jinwoo@gmail.com', '$2y$10$xAvXk04G3BdrP9wUxdVULObnlZhp3arejUJ.aZFojUyIhwTgX3WQq', 'user', '2025-04-10 15:40:23', 'active', 'Aura', 'uploads/avatars/user_10_1744300622.jpg'),
(11, 'Richie Spice', 'spice@gmail.com', '$2y$10$5dAKwd7V1A/nPA/KU6Qgouum2Vg4pSiYfGDpI3BtYThFR5BSO8F12', 'user', '2025-04-12 08:38:25', 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_backup`
--

CREATE TABLE `users_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `delivered_at` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `notification_id`, `is_read`, `delivered_at`, `status`, `created_at`) VALUES
(1, 1, 3, 0, '2025-04-08 20:30:51', 'unread', '2025-04-08 20:30:51'),
(2, 2, 3, 0, '2025-04-08 20:30:51', 'unread', '2025-04-08 20:30:51'),
(3, 3, 3, 0, '2025-04-08 20:30:51', 'unread', '2025-04-08 20:30:51'),
(4, 4, 3, 0, '2025-04-08 20:30:51', 'unread', '2025-04-08 20:30:51'),
(5, 5, 3, 0, '2025-04-08 20:30:51', 'unread', '2025-04-08 20:30:51'),
(6, 1, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(7, 2, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(8, 3, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(9, 4, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(10, 5, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(11, 6, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(12, 7, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(13, 8, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(14, 9, 4, 0, '2025-04-10 00:09:29', 'unread', '2025-04-10 00:09:29'),
(15, 1, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(16, 2, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(17, 3, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(18, 4, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(19, 5, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(20, 6, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(21, 7, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(22, 8, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(23, 9, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05'),
(24, 10, 5, 0, '2025-04-11 15:35:05', 'unread', '2025-04-11 15:35:05');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`id`, `poll_id`, `option_id`, `user_id`, `voted_at`) VALUES
(6, 4, 13, 5, '2025-04-08 17:10:29'),
(13, 4, 12, 7, '2025-04-09 20:27:15'),
(15, 4, 13, 8, '2025-04-09 20:30:06'),
(17, 4, 13, 10, '2025-04-11 07:44:46'),
(18, 5, 15, 10, '2025-04-11 19:27:57'),
(19, 6, 18, 11, '2025-04-12 11:01:04'),
(20, 5, 15, 11, '2025-04-12 11:06:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `tmdb_id` (`tmdb_id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_id` (`poll_id`);

--
-- Indexes for table `poll_views`
--
ALTER TABLE `poll_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_id` (`poll_id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_log_type_username` (`log_type`,`username`),
  ADD KEY `idx_timestamp_desc` (`timestamp`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `notification_id` (`notification_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_id` (`poll_id`),
  ADD KEY `option_id` (`option_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `poll_views`
--
ALTER TABLE `poll_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `poll_views`
--
ALTER TABLE `poll_views`
  ADD CONSTRAINT `poll_views_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`);

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_notifications_ibfk_2` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
