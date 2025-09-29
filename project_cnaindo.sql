-- Clean one‑click import for project_cnaindo
-- Compatible with MySQL/MariaDB (phpMyAdmin / XAMPP)

START TRANSACTION;

-- Create and select database
CREATE DATABASE IF NOT EXISTS `project_cnaindo`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `project_cnaindo`;

-- Environment
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Re-import safety
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed admin from dump (fixed trailing comma)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$QWL5wkkmb6SnChgo1uCfquPpP5hwADZH/aSfksUG7/J1CDpNNNOSC', 'admin', '2025-09-24 09:17:35');

-- settings
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allowed_lat` decimal(10,8) DEFAULT NULL,
  `allowed_lon` decimal(11,8) DEFAULT NULL,
  `radius` int(11) DEFAULT 100,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `work_start` time DEFAULT '09:00:00',
  `work_end` time DEFAULT '17:00:00',
  `open_from` time DEFAULT NULL,
  `on_time_deadline` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional default row to match expected sequence
INSERT INTO `settings` (`id`,`allowed_lat`,`allowed_lon`,`radius`,`updated_at`,`work_start`,`work_end`,`open_from`,`on_time_deadline`)
VALUES (1,NULL,NULL,100,CURRENT_TIMESTAMP,'09:00:00','17:00:00',NULL,NULL);

-- attendance
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('checkin','checkout') NOT NULL,
  `timestamp` datetime NOT NULL,
  `lat` double DEFAULT NULL,
  `lon` double DEFAULT NULL,
  `accuracy` double DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Match original next IDs
ALTER TABLE `users` AUTO_INCREMENT = 6;
ALTER TABLE `attendance` AUTO_INCREMENT = 64;
ALTER TABLE `settings` AUTO_INCREMENT = 2;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
-- End of file