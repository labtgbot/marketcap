-- Migration: 0004_ai_feedback
-- Purpose: Store AI insight card feedback for admin review without raw prompts or responses.

CREATE TABLE IF NOT EXISTS `ai_feedback` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `session_token_hash` CHAR(64) NULL,
  `insight_id` CHAR(64) NOT NULL,
  `insight_type` ENUM('market_summary', 'coin_summary', 'watchlist_digest', 'ton_ecosystem_pulse', 'alert_explanation', 'sentiment') NOT NULL,
  `feedback_type` ENUM('helpful', 'stale', 'wrong', 'unsafe') NOT NULL,
  `provider` VARCHAR(64) NULL,
  `model` VARCHAR(128) NULL,
  `prompt_version` VARCHAR(64) NULL,
  `subject_hash` CHAR(64) NULL,
  `route_path` VARCHAR(191) NULL,
  `source_route` VARCHAR(80) NULL,
  `source_surface` ENUM('public_web', 'mobile_web', 'telegram_mini_app', 'telegram_bot', 'admin') NOT NULL DEFAULT 'public_web',
  `market_data_age_seconds` INT UNSIGNED NULL,
  `request_id` VARCHAR(128) NOT NULL,
  `metadata_json` JSON NULL,
  `review_state` ENUM('pending', 'reviewed', 'ignored', 'escalated') NOT NULL DEFAULT 'pending',
  `reviewed_by_admin_user_id` BIGINT UNSIGNED NULL,
  `reviewed_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_ai_feedback_review` (`review_state`, `feedback_type`, `created_at`),
  KEY `idx_ai_feedback_insight` (`insight_type`, `provider`, `created_at`),
  KEY `idx_ai_feedback_user` (`user_id`, `created_at`),
  KEY `idx_ai_feedback_created` (`created_at`),
  CONSTRAINT `fk_ai_feedback_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ai_feedback_reviewed_by` FOREIGN KEY (`reviewed_by_admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
