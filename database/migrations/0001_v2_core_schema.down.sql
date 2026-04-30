-- Rollback: 0001_v2_core_schema
-- The schema_migrations ledger is intentionally retained for the migration runner.

DROP TABLE IF EXISTS `premium_entitlements`;
DROP TABLE IF EXISTS `admin_audit_logs`;
DROP TABLE IF EXISTS `feature_flags`;
DROP TABLE IF EXISTS `provider_settings`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `ai_insight_cache`;
DROP TABLE IF EXISTS `referral_attributions`;
DROP TABLE IF EXISTS `referral_campaigns`;
DROP TABLE IF EXISTS `alert_deliveries`;
DROP TABLE IF EXISTS `alert_rules`;
DROP TABLE IF EXISTS `watchlist_entries`;
DROP TABLE IF EXISTS `watchlists`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `users`;
