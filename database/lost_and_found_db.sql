-- =========================================================
-- LOST & FOUND MANAGEMENT SYSTEM
-- Database: lost_and_found_db
-- Import via phpMyAdmin
-- =========================================================

CREATE DATABASE IF NOT EXISTS `lost_and_found_db1` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `lost_and_found_db1`;

-- ---------------------------------------------------------
-- USERS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- ITEM CATEGORIES
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`name`) VALUES
('Electronics'),
('Bags & Wallets'),
('Clothing & Accessories'),
('Books & Documents'),
('Keys & IDs'),
('Jewelry & Watches'),
('Sports Equipment'),
('Toys & Games'),
('Medicine & Health'),
('Others');

-- ---------------------------------------------------------
-- LOST ITEMS
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lost_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_name` VARCHAR(150) NOT NULL,
  `category_id` INT(11) UNSIGNED DEFAULT NULL,
  `description` TEXT,
  `date_lost` DATE NOT NULL,
  `location_lost` VARCHAR(180) DEFAULT NULL,
  `contact_info` VARCHAR(180) DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('open','matched','returned','closed') NOT NULL DEFAULT 'open',
  `reported_by` INT(11) UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_reported_by` (`reported_by`),
  CONSTRAINT `fk_lost_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_lost_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- FOUND ITEMS
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `found_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_name` VARCHAR(150) NOT NULL,
  `category_id` INT(11) UNSIGNED DEFAULT NULL,
  `description` TEXT,
  `date_found` DATE NOT NULL,
  `location_found` VARCHAR(180) DEFAULT NULL,
  `storage_location` VARCHAR(180) DEFAULT NULL,
  `notes` TEXT,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('available','matched','returned','closed') NOT NULL DEFAULT 'available',
  `recorded_by` INT(11) UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_recorded_by` (`recorded_by`),
  CONSTRAINT `fk_found_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_found_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- MATCHES (links lost ↔ found)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `matches` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lost_item_id` INT(11) UNSIGNED NOT NULL,
  `found_item_id` INT(11) UNSIGNED NOT NULL,
  `confirmed_by` INT(11) UNSIGNED NOT NULL,
  `notes` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lost` (`lost_item_id`),
  KEY `idx_found` (`found_item_id`),
  CONSTRAINT `fk_match_lost` FOREIGN KEY (`lost_item_id`) REFERENCES `lost_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_found` FOREIGN KEY (`found_item_id`) REFERENCES `found_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_user` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- ACTIVITY LOG
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(80) NOT NULL,
  `target_type` VARCHAR(60) DEFAULT NULL,
  `target_id` INT(11) UNSIGNED DEFAULT NULL,
  `details` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- SYSTEM SETTINGS
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` TEXT,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('system_name', 'Lost & Found Management System'),
('organization_name', 'School Administration Office'),
('default_status_lost', 'open'),
('default_status_found', 'available'),
('items_per_page', '15');

-- ---------------------------------------------------------
-- BACKUPS LOG (tracks admin-generated backups)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `backup_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `size_bytes` BIGINT UNSIGNED DEFAULT 0,
  `action` ENUM('backup','restore') NOT NULL,
  `status` ENUM('success','failed') NOT NULL,
  `notes` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_backup_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;