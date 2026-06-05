-- Migration: 0007_smart_alerts rollback
-- Purpose: Revert Stage 4 alert-rule extensions.

-- This rollback cannot safely map Stage 4-only trigger types into the old enum.
-- The duplicate insert below intentionally aborts the migration when such rows
-- exist; archive, delete, or convert them explicitly before retrying rollback.
CREATE TEMPORARY TABLE `tonbankcard_0007_removed_trigger_type_guard` (
  `rollback_blocker` TINYINT NOT NULL,
  PRIMARY KEY (`rollback_blocker`)
);

INSERT INTO `tonbankcard_0007_removed_trigger_type_guard` (`rollback_blocker`)
SELECT 1
FROM `alert_rules`
WHERE `trigger_type` IN ('rank_change', 'sentiment_change', 'ton_ecosystem')
LIMIT 1;

INSERT INTO `tonbankcard_0007_removed_trigger_type_guard` (`rollback_blocker`)
VALUES (1);

DROP TEMPORARY TABLE `tonbankcard_0007_removed_trigger_type_guard`;

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
