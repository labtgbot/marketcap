-- Migration: 0007_smart_alerts
-- Purpose: Extend alert rules for Stage 4 Telegram smart-alert delivery.

ALTER TABLE `alert_rules`
  MODIFY `trigger_type` ENUM('price_cross', 'percent_move', 'volume_spike', 'market_cap_cross', 'rank_change', 'sentiment_change', 'ton_ecosystem') NOT NULL,
  ADD COLUMN `display_name` VARCHAR(160) NULL AFTER `symbol`,
  ADD COLUMN `context_path` VARCHAR(200) NULL AFTER `delivery_channel`,
  ADD COLUMN `frequency_cap_seconds` INT UNSIGNED NOT NULL DEFAULT 3600 AFTER `timezone`,
  ADD COLUMN `max_deliveries_per_day` INT UNSIGNED NOT NULL DEFAULT 8 AFTER `frequency_cap_seconds`,
  ADD COLUMN `last_delivery_fingerprint` CHAR(64) NULL AFTER `last_triggered_at`;

ALTER TABLE `alert_deliveries`
  ADD COLUMN `deep_link_url` VARCHAR(255) NULL AFTER `request_id`,
  ADD COLUMN `delivery_fingerprint` CHAR(64) NULL AFTER `deep_link_url`,
  ADD COLUMN `attempt_count` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `delivery_fingerprint`,
  ADD COLUMN `next_retry_at` DATETIME(6) NULL AFTER `delivered_at`,
  ADD KEY `idx_alert_deliveries_fingerprint` (`delivery_fingerprint`, `attempted_at`);
