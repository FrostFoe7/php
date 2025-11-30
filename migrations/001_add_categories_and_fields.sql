-- Migration: Add categories table and new fields to files and questions tables

-- Create categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007bff',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add new columns to files table if they don't exist
ALTER TABLE `files` ADD COLUMN `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `original_filename`;
ALTER TABLE `files` ADD COLUMN `category_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `display_name`;
ALTER TABLE `files` ADD COLUMN `external_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `files` ADD COLUMN `batch_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `files` ADD COLUMN `set_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- Add foreign key constraint to categories
ALTER TABLE `files` ADD CONSTRAINT `files_ibfk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

-- Change section column from int to varchar in questions table
ALTER TABLE `questions` MODIFY COLUMN `section` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT 'e.g., p, c, m, b, bm, bn, e, i, gk, iq';
