-- Migration: 0008_share_referral_attribution rollback
-- Purpose: Restore one referral attribution per user regardless of campaign.

ALTER TABLE `referral_attributions`
  DROP KEY `uniq_referral_attributions_referred_campaign`,
  ADD UNIQUE KEY `uniq_referral_attributions_referred` (`referred_user_id`);
