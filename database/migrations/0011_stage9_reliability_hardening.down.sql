-- Migration: 0011_stage9_reliability_hardening
-- Purpose: Roll back Stage 9 reliability hardening indexes.

ALTER TABLE `alert_deliveries`
  DROP KEY `idx_alert_deliveries_retry`,
  DROP KEY `idx_alert_deliveries_claim`,
  DROP COLUMN `retry_claim_token`;

ALTER TABLE `alert_rules`
  DROP KEY `idx_alert_rules_evaluation_claim`,
  DROP COLUMN `evaluation_claim_token`;

ALTER TABLE `premium_payment_events`
  DROP KEY `uniq_premium_payment_events_charge_event`;
