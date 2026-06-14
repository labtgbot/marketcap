-- Migration: 0011_stage9_reliability_hardening
-- Purpose: Stage 9 security and reliability hardening for payment idempotency and alert retries.

DELETE newer
FROM `premium_payment_events` AS newer
INNER JOIN `premium_payment_events` AS older
  ON older.`telegram_payment_charge_id_hash` = newer.`telegram_payment_charge_id_hash`
 AND older.`event_type` = newer.`event_type`
 AND older.`id` < newer.`id`
WHERE newer.`telegram_payment_charge_id_hash` IS NOT NULL;

ALTER TABLE `premium_payment_events`
  ADD UNIQUE KEY `uniq_premium_payment_events_charge_event` (`telegram_payment_charge_id_hash`, `event_type`);

ALTER TABLE `alert_rules`
  ADD COLUMN `evaluation_claim_token` VARCHAR(80) NULL AFTER `next_evaluation_at`,
  ADD KEY `idx_alert_rules_evaluation_claim` (`evaluation_claim_token`);

ALTER TABLE `alert_deliveries`
  ADD COLUMN `retry_claim_token` VARCHAR(80) NULL AFTER `next_retry_at`,
  ADD KEY `idx_alert_deliveries_claim` (`retry_claim_token`),
  ADD KEY `idx_alert_deliveries_retry` (`delivery_status`, `next_retry_at`, `attempt_count`);
