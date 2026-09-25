UPDATE `#__xdecaroorganizations_organizations`
SET `language` = '*'
WHERE `language` IS NULL OR TRIM(`language`) = '';

ALTER TABLE `#__xdecaroorganizations_organizations`
  MODIFY `language` VARCHAR(16) NOT NULL DEFAULT '*';
