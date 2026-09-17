ALTER TABLE `#__xdecaroorganizations_organizations`
  ADD COLUMN `structure_level` VARCHAR(32) NOT NULL DEFAULT 'unspecified' AFTER `type`,
  ADD COLUMN `territory_type` VARCHAR(32) DEFAULT NULL AFTER `structure_level`,
  ADD COLUMN `territory_name` VARCHAR(190) DEFAULT NULL AFTER `territory_type`,
  ADD COLUMN `operational_status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `territory_name`,
  ADD COLUMN `status_since` DATE DEFAULT NULL AFTER `operational_status`,
  ADD COLUMN `autonomy_legal` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status_since`,
  ADD COLUMN `autonomy_management` TINYINT(1) NOT NULL DEFAULT 0 AFTER `autonomy_legal`,
  ADD COLUMN `autonomy_administrative` TINYINT(1) NOT NULL DEFAULT 0 AFTER `autonomy_management`,
  ADD COLUMN `autonomy_tax` TINYINT(1) NOT NULL DEFAULT 0 AFTER `autonomy_administrative`,
  ADD COLUMN `autonomy_fiscal` TINYINT(1) NOT NULL DEFAULT 0 AFTER `autonomy_tax`,
  ADD KEY `idx_org_structure_level` (`structure_level`),
  ADD KEY `idx_org_operational_status` (`operational_status`);
