-- Migration: 0007_smart_alerts rollback
-- Purpose: Revert Stage 4 alert-rule extensions.

UPDATE `alert_rules`
SET `trigger_type` = 'percent_move'
WHERE `trigger_type` IN ('rank_change', 'sentiment_change', 'ton_ecosystem');

ALTER TABLE `alert_deliveries`
  DROP KEY `idx_alert_deliveries_fingerprint`,
  DROP COLUMN `next_retry_at`,
  DROP COLUMN `attempt_count`,
  DROP COLUMN `delivery_fingerprint`,
  DROP COLUMN `deep_link_url`;

ALTER TABLE `alert_rules`
  DROP COLUMN `last_delivery_fingerprint`,
  DROP COLUMN `max_deliveries_per_day`,
  DROP COLUMN `frequency_cap_seconds`,
  DROP COLUMN `context_path`,
  DROP COLUMN `display_name`,
  MODIFY `trigger_type` ENUM('price_cross', 'percent_move', 'volume_spike', 'market_cap_cross') NOT NULL;
