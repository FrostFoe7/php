-- Migration: Allow optional question/explanation images

ALTER TABLE `questions`
  ADD COLUMN `question_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `explanation`,
  ADD COLUMN `explanation_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `question_image`;
