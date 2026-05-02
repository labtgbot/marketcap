-- Migration: 0010_premium_payment_state
-- Purpose: Telegram Stars payment audit state for premium subscriptions.

ALTER TABLE `premium_entitlements`
  ADD COLUMN `last_telegram_payment_charge_id` VARCHAR(180) NULL AFTER `provider_subscription_ref_hash`,
  ADD COLUMN `last_provider_payment_charge_id` VARCHAR(180) NULL AFTER `last_telegram_payment_charge_id`,
  ADD COLUMN `last_telegram_payment_charge_id_hash` CHAR(64) NULL AFTER `last_provider_payment_charge_id`,
  ADD COLUMN `last_provider_payment_charge_id_hash` CHAR(64) NULL AFTER `last_telegram_payment_charge_id_hash`,
  ADD COLUMN `cancel_at_period_end` TINYINT(1) NOT NULL DEFAULT 0 AFTER `revoked_at`,
  ADD COLUMN `canceled_at` DATETIME(6) NULL AFTER `cancel_at_period_end`,
  ADD COLUMN `refunded_at` DATETIME(6) NULL AFTER `canceled_at`,
  ADD KEY `idx_premium_entitlements_charge` (`last_telegram_payment_charge_id_hash`),
  ADD KEY `idx_premium_entitlements_canceled` (`cancel_at_period_end`, `expires_at`);

CREATE TABLE IF NOT EXISTS `premium_payment_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `entitlement_id` BIGINT UNSIGNED NULL,
  `provider` ENUM('telegram_stars') NOT NULL DEFAULT 'telegram_stars',
  `event_type` ENUM('invoice_created', 'pre_checkout_approved', 'payment_succeeded', 'subscription_renewed', 'subscription_canceled', 'payment_refunded') NOT NULL,
  `plan_code` VARCHAR(80) NULL,
  `invoice_payload_hash` CHAR(64) NULL,
  `telegram_payment_charge_id_hash` CHAR(64) NULL,
  `provider_payment_charge_id_hash` CHAR(64) NULL,
  `amount_stars` INT UNSIGNED NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'XTR',
  `event_at` DATETIME(6) NOT NULL,
  `metadata_json` JSON NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_premium_payment_events_user_time` (`user_id`, `event_at`),
  KEY `idx_premium_payment_events_event_time` (`event_type`, `event_at`),
  KEY `idx_premium_payment_events_charge` (`telegram_payment_charge_id_hash`),
  KEY `idx_premium_payment_events_invoice` (`invoice_payload_hash`),
  CONSTRAINT `fk_premium_payment_events_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_premium_payment_events_entitlement_id` FOREIGN KEY (`entitlement_id`) REFERENCES `premium_entitlements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `feature_flags` (`feature_key`, `enabled`, `rollout_percent`, `rules_json`)
VALUES (
  'premium',
  0,
  0,
  JSON_OBJECT(
    'free_limits', JSON_OBJECT(
      'alerts_per_user', 3,
      'watchlist_entries', 20,
      'advanced_ranges', JSON_ARRAY('24h', '7d'),
      'ai_digest_per_day', 1,
      'priority_refresh', false,
      'market_refresh_seconds', 300
    ),
    'premium_limits', JSON_OBJECT(
      'alerts_per_user', 100,
      'watchlist_entries', 250,
      'advanced_ranges', JSON_ARRAY('24h', '7d', '30d', '90d', '1y'),
      'ai_digest_per_day', 24,
      'priority_refresh', true,
      'market_refresh_seconds', 60
    ),
    'provider', 'telegram_stars',
    'currency', 'XTR',
    'subscription_period_seconds', 2592000
  )
);
