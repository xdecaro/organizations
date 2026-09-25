-- Organizations 1.2.13
-- Store public social profile URLs on organization records.

ALTER TABLE `#__xdecaroorganizations_organizations`
  ADD COLUMN `facebook_url` VARCHAR(512) DEFAULT NULL AFTER `website`,
  ADD COLUMN `instagram_url` VARCHAR(512) DEFAULT NULL AFTER `facebook_url`,
  ADD COLUMN `youtube_url` VARCHAR(512) DEFAULT NULL AFTER `instagram_url`,
  ADD COLUMN `linkedin_url` VARCHAR(512) DEFAULT NULL AFTER `youtube_url`,
  ADD COLUMN `tiktok_url` VARCHAR(512) DEFAULT NULL AFTER `linkedin_url`;
