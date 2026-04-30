-- Migration: 0001_v2_core_schema
-- Purpose: Durable TONBANKCARD V2 core data model for MySQL or MariaDB.

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` VARCHAR(191) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `checksum` CHAR(64) NOT NULL,
  `applied_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `telegram_user_id` BIGINT UNSIGNED NULL,
  `telegram_language_code` VARCHAR(16) NULL,
  `telegram_is_premium` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'restricted', 'deleted') NOT NULL DEFAULT 'active',
  `first_seen_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `last_seen_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `deleted_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_telegram_user_id` (`telegram_user_id`),
  KEY `idx_users_status_last_seen` (`status`, `last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `session_token_hash` CHAR(64) NOT NULL,
  `telegram_init_data_hash` CHAR(64) NULL,
  `surface` ENUM('public_web', 'mobile_web', 'telegram_mini_app', 'telegram_bot', 'admin') NOT NULL,
  `trust_state` ENUM('anonymous', 'telegram_validated', 'admin_validated', 'revoked') NOT NULL DEFAULT 'anonymous',
  `start_param_hash` CHAR(64) NULL,
  `chat_instance_hash` CHAR(64) NULL,
  `ip_hash` CHAR(64) NULL,
  `user_agent_hash` CHAR(64) NULL,
  `started_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `last_seen_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `expires_at` DATETIME(6) NOT NULL,
  `revoked_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_sessions_token_hash` (`session_token_hash`),
  KEY `idx_user_sessions_user_expires` (`user_id`, `expires_at`),
  KEY `idx_user_sessions_telegram_init_data_hash` (`telegram_init_data_hash`),
  KEY `idx_user_sessions_surface_last_seen` (`surface`, `last_seen_at`),
  CONSTRAINT `fk_user_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `watchlists` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(80) NOT NULL DEFAULT 'Default',
  `scope` ENUM('personal', 'telegram_group') NOT NULL DEFAULT 'personal',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `deleted_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_watchlists_user_name` (`user_id`, `name`),
  KEY `idx_watchlists_user` (`user_id`, `is_default`, `deleted_at`),
  CONSTRAINT `fk_watchlists_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `watchlist_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `watchlist_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `coin_id` VARCHAR(96) NOT NULL,
  `symbol` VARCHAR(32) NULL,
  `source` VARCHAR(32) NOT NULL DEFAULT 'coingecko',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `added_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_watchlist_entries_watchlist_coin` (`watchlist_id`, `coin_id`),
  KEY `idx_watchlist_entries_user_coin` (`user_id`, `coin_id`),
  KEY `idx_watchlist_entries_coin` (`coin_id`, `added_at`),
  CONSTRAINT `fk_watchlist_entries_watchlist_id` FOREIGN KEY (`watchlist_id`) REFERENCES `watchlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_watchlist_entries_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `alert_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `coin_id` VARCHAR(96) NOT NULL,
  `symbol` VARCHAR(32) NULL,
  `trigger_type` ENUM('price_cross', 'percent_move', 'volume_spike', 'market_cap_cross') NOT NULL,
  `comparison_operator` ENUM('gt', 'gte', 'lt', 'lte') NULL,
  `threshold_value` DECIMAL(36, 18) NULL,
  `percent_window_minutes` INT UNSIGNED NULL,
  `volume_window_minutes` INT UNSIGNED NULL,
  `delivery_channel` ENUM('telegram_bot') NOT NULL DEFAULT 'telegram_bot',
  `status` ENUM('active', 'paused', 'deleted') NOT NULL DEFAULT 'active',
  `quiet_hours_start` TIME NULL,
  `quiet_hours_end` TIME NULL,
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
  `last_evaluated_at` DATETIME(6) NULL,
  `last_triggered_at` DATETIME(6) NULL,
  `next_evaluation_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `deleted_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alert_rules_user_status` (`user_id`, `status`, `created_at`),
  KEY `idx_alert_rules_active_coin` (`status`, `coin_id`, `next_evaluation_at`),
  KEY `idx_alert_rules_next_evaluation` (`status`, `next_evaluation_at`),
  CONSTRAINT `fk_alert_rules_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `alert_deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alert_rule_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `delivery_channel` ENUM('telegram_bot') NOT NULL DEFAULT 'telegram_bot',
  `delivery_status` ENUM('queued', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'queued',
  `telegram_message_id` BIGINT NULL,
  `error_code` VARCHAR(80) NULL,
  `request_id` CHAR(36) NULL,
  `attempted_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `delivered_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alert_deliveries_rule_time` (`alert_rule_id`, `attempted_at`),
  KEY `idx_alert_deliveries_user_time` (`user_id`, `attempted_at`),
  KEY `idx_alert_deliveries_status` (`delivery_status`, `attempted_at`),
  CONSTRAINT `fk_alert_deliveries_alert_rule_id` FOREIGN KEY (`alert_rule_id`) REFERENCES `alert_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alert_deliveries_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referral_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(80) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `source` VARCHAR(80) NULL,
  `status` ENUM('draft', 'active', 'paused', 'archived') NOT NULL DEFAULT 'draft',
  `starts_at` DATETIME(6) NULL,
  `ends_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_referral_campaigns_code` (`code`),
  KEY `idx_referral_campaigns_status` (`status`, `starts_at`, `ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referral_attributions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referred_user_id` BIGINT UNSIGNED NOT NULL,
  `inviter_user_id` BIGINT UNSIGNED NULL,
  `campaign_id` BIGINT UNSIGNED NULL,
  `payload_hash` CHAR(64) NULL,
  `landing_route` VARCHAR(191) NULL,
  `attribution_model` ENUM('first_touch', 'manual') NOT NULL DEFAULT 'first_touch',
  `attributed_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_referral_attributions_referred` (`referred_user_id`),
  KEY `idx_referral_attributions_campaign` (`campaign_id`, `attributed_at`),
  KEY `idx_referral_attributions_inviter` (`inviter_user_id`, `attributed_at`),
  KEY `idx_referral_attributions_payload` (`payload_hash`),
  CONSTRAINT `fk_referral_attributions_referred_user_id` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_referral_attributions_inviter_user_id` FOREIGN KEY (`inviter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_referral_attributions_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `referral_campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_insight_cache` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cache_key_hash` CHAR(64) NOT NULL,
  `provider` VARCHAR(64) NOT NULL,
  `model` VARCHAR(128) NOT NULL,
  `prompt_version` VARCHAR(64) NOT NULL,
  `insight_type` ENUM('market_pulse', 'coin_detail', 'watchlist_digest', 'alert_explanation') NOT NULL,
  `coin_id` VARCHAR(96) NULL,
  `market_data_hash` CHAR(64) NULL,
  `safety_state` ENUM('passed', 'blocked', 'needs_review', 'stale') NOT NULL DEFAULT 'passed',
  `response_ref` VARCHAR(191) NULL,
  `output_hash` CHAR(64) NULL,
  `expires_at` DATETIME(6) NOT NULL,
  `last_used_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_insight_cache_key` (`cache_key_hash`),
  KEY `idx_ai_insight_cache_provider` (`provider`, `model`, `prompt_version`),
  KEY `idx_ai_insight_cache_coin` (`coin_id`, `insight_type`, `expires_at`),
  KEY `idx_ai_insight_cache_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_hash` CHAR(64) NOT NULL,
  `display_name` VARCHAR(120) NULL,
  `role` ENUM('support', 'content', 'admin', 'owner') NOT NULL DEFAULT 'support',
  `status` ENUM('invited', 'active', 'disabled') NOT NULL DEFAULT 'invited',
  `last_login_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_users_email_hash` (`email_hash`),
  KEY `idx_admin_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `provider_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(64) NOT NULL,
  `setting_key` VARCHAR(96) NOT NULL,
  `value_json` JSON NULL,
  `secret_ref` VARCHAR(191) NULL,
  `value_is_secret` TINYINT(1) NOT NULL DEFAULT 0,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by_admin_user_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_provider_settings_provider_key` (`provider`, `setting_key`),
  KEY `idx_provider_settings_provider_enabled` (`provider`, `enabled`),
  KEY `idx_provider_settings_updated_by` (`updated_by_admin_user_id`, `updated_at`),
  CONSTRAINT `fk_provider_settings_updated_by` FOREIGN KEY (`updated_by_admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `feature_flags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `feature_key` VARCHAR(96) NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `rollout_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `rules_json` JSON NULL,
  `updated_by_admin_user_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_feature_flags_key` (`feature_key`),
  KEY `idx_feature_flags_enabled` (`enabled`, `feature_key`),
  KEY `idx_feature_flags_updated_by` (`updated_by_admin_user_id`, `updated_at`),
  CONSTRAINT `fk_feature_flags_updated_by` FOREIGN KEY (`updated_by_admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_admin_user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(120) NOT NULL,
  `subject_type` VARCHAR(80) NOT NULL,
  `subject_id` VARCHAR(120) NULL,
  `request_id` CHAR(36) NULL,
  `ip_hash` CHAR(64) NULL,
  `user_agent_hash` CHAR(64) NULL,
  `before_json` JSON NULL,
  `after_json` JSON NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_admin_audit_logs_actor` (`actor_admin_user_id`, `created_at`),
  KEY `idx_admin_audit_logs_subject` (`subject_type`, `subject_id`, `created_at`),
  KEY `idx_admin_audit_logs_action` (`action`, `created_at`),
  CONSTRAINT `fk_admin_audit_logs_actor` FOREIGN KEY (`actor_admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `premium_entitlements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `plan_code` VARCHAR(80) NOT NULL,
  `status` ENUM('trialing', 'active', 'past_due', 'expired', 'revoked') NOT NULL DEFAULT 'active',
  `source` ENUM('telegram_stars', 'manual', 'partner', 'test') NOT NULL,
  `provider_customer_ref_hash` CHAR(64) NULL,
  `provider_subscription_ref_hash` CHAR(64) NULL,
  `starts_at` DATETIME(6) NOT NULL,
  `expires_at` DATETIME(6) NULL,
  `renewed_at` DATETIME(6) NULL,
  `revoked_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_premium_entitlements_user_status` (`user_id`, `status`, `expires_at`),
  KEY `idx_premium_entitlements_plan_status` (`plan_code`, `status`, `expires_at`),
  KEY `idx_premium_entitlements_provider_customer` (`provider_customer_ref_hash`),
  CONSTRAINT `fk_premium_entitlements_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
