-- Migration: 0008_share_referral_attribution rollback
-- Purpose: Restore one referral attribution per user regardless of campaign.

DELETE duplicate_attribution
FROM `referral_attributions` AS duplicate_attribution
INNER JOIN `referral_attributions` AS keeper_attribution
  ON keeper_attribution.`referred_user_id` = duplicate_attribution.`referred_user_id`
  AND (
    keeper_attribution.`attributed_at` < duplicate_attribution.`attributed_at`
    OR (
      keeper_attribution.`attributed_at` = duplicate_attribution.`attributed_at`
      AND keeper_attribution.`id` < duplicate_attribution.`id`
    )
  );

ALTER TABLE `referral_attributions`
  DROP KEY `uniq_referral_attributions_referred_campaign`,
  ADD UNIQUE KEY `uniq_referral_attributions_referred` (`referred_user_id`);
