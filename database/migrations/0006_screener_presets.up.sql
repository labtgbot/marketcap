-- Migration: 0006_screener_presets
-- Purpose: Store advanced screener presets for trusted Telegram sessions.

CREATE TABLE IF NOT EXISTS `screener_presets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `filters_json` JSON NOT NULL,
  `sort_key` VARCHAR(80) NOT NULL DEFAULT 'market_cap_rank',
  `sort_direction` ENUM('asc', 'desc') NOT NULL DEFAULT 'asc',
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `deleted_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_screener_presets_user_name` (`user_id`, `name`),
  KEY `idx_screener_presets_user_updated` (`user_id`, `deleted_at`, `updated_at`),
  CONSTRAINT `fk_screener_presets_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
