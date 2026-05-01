-- Migration: 0010_premium_payment_state rollback
-- Purpose: Remove Telegram Stars payment audit state.

DELETE FROM `feature_flags`
WHERE `feature_key` = 'premium'
  AND `enabled` = 0
  AND `updated_by_admin_user_id` IS NULL;

DROP TABLE IF EXISTS `premium_payment_events`;

ALTER TABLE `premium_entitlements`
  DROP KEY `idx_premium_entitlements_canceled`,
  DROP KEY `idx_premium_entitlements_charge`,
  DROP COLUMN `refunded_at`,
  DROP COLUMN `canceled_at`,
  DROP COLUMN `cancel_at_period_end`,
  DROP COLUMN `last_provider_payment_charge_id_hash`,
  DROP COLUMN `last_telegram_payment_charge_id_hash`,
  DROP COLUMN `last_provider_payment_charge_id`,
  DROP COLUMN `last_telegram_payment_charge_id`;
