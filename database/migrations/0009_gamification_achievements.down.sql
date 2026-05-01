-- Migration: 0009_gamification_achievements rollback
-- Purpose: Remove durable gamification achievement tables.

DELETE FROM `feature_flags`
WHERE `feature_key` = 'gamification'
  AND `enabled` = 0
  AND `updated_by_admin_user_id` IS NULL;

DROP TABLE IF EXISTS `achievement_prompt_dismissals`;
DROP TABLE IF EXISTS `user_achievements`;
DROP TABLE IF EXISTS `achievement_events`;
