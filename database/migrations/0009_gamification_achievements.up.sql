-- Migration: 0009_gamification_achievements
-- Purpose: Durable achievement events, badge unlocks, prompt dismissals, and admin tuning defaults.

CREATE TABLE IF NOT EXISTS `achievement_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `session_token_hash` CHAR(64) NULL,
  `achievement_id` VARCHAR(96) NULL,
  `event_name` VARCHAR(96) NOT NULL,
  `source_route` VARCHAR(96) NULL,
  `coin_id` VARCHAR(96) NULL,
  `symbol` VARCHAR(32) NULL,
  `streak_count` INT UNSIGNED NULL,
  `event_hash` CHAR(64) NULL,
  `metadata_json` JSON NULL,
  `occurred_at` DATETIME(6) NOT NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_achievement_events_user_time` (`user_id`, `occurred_at`),
  KEY `idx_achievement_events_name_time` (`event_name`, `occurred_at`),
  KEY `idx_achievement_events_achievement` (`achievement_id`, `occurred_at`),
  KEY `idx_achievement_events_hash` (`event_hash`),
  CONSTRAINT `fk_achievement_events_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_achievements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `achievement_id` VARCHAR(96) NOT NULL,
  `prompt_state` ENUM('active', 'queued', 'dismissed') NOT NULL DEFAULT 'active',
  `unlocked_at` DATETIME(6) NOT NULL,
  `dismissed_at` DATETIME(6) NULL,
  `shared_at` DATETIME(6) NULL,
  `metadata_json` JSON NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_achievements_user_badge` (`user_id`, `achievement_id`),
  KEY `idx_user_achievements_prompt` (`prompt_state`, `unlocked_at`),
  KEY `idx_user_achievements_unlocked` (`achievement_id`, `unlocked_at`),
  CONSTRAINT `fk_user_achievements_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `achievement_prompt_dismissals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `achievement_id` VARCHAR(96) NOT NULL,
  `prompt_context` VARCHAR(96) NOT NULL DEFAULT 'achievement_prompt',
  `dismissed_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_achievement_prompt_dismissals_user` (`user_id`, `dismissed_at`),
  KEY `idx_achievement_prompt_dismissals_badge` (`achievement_id`, `dismissed_at`),
  CONSTRAINT `fk_achievement_prompt_dismissals_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `feature_flags` (`feature_key`, `enabled`, `rollout_percent`, `rules_json`)
VALUES (
  'gamification',
  0,
  0,
  JSON_OBJECT(
    'weekly_check_days', 7,
    'share_milestone_count', 3,
    'movement_threshold_percent', 7.5,
    'max_prompts_per_session', 1,
    'achievements', JSON_ARRAY(
      'first_watchlist',
      'first_alert',
      'weekly_market_check',
      'ton_explorer',
      'share_milestone',
      'caught_market_movement'
    )
  )
);
