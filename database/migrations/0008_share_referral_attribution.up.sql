-- Migration: 0008_share_referral_attribution
-- Purpose: Allow first-touch referral attribution once per user and campaign.

ALTER TABLE `referral_attributions`
  DROP KEY `uniq_referral_attributions_referred`,
  ADD UNIQUE KEY `uniq_referral_attributions_referred_campaign` (`referred_user_id`, `campaign_id`);
