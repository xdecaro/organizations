CREATE TABLE IF NOT EXISTS `#__xdecaroorganizations_affiliations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `organization_id` INT UNSIGNED NOT NULL,
  `target_organization_id` INT UNSIGNED NOT NULL,
  `relation_type` VARCHAR(50) NOT NULL DEFAULT 'sports_affiliation',
  `relation_code` VARCHAR(100) DEFAULT NULL,
  `starts_on` DATE DEFAULT NULL,
  `ends_on` DATE DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_affiliation_uuid` (`uuid`),
  KEY `idx_affiliation_org` (`organization_id`),
  KEY `idx_affiliation_target` (`target_organization_id`),
  KEY `idx_affiliation_type` (`relation_type`),
  KEY `idx_affiliation_status` (`status`),
  KEY `idx_affiliation_org_state` (`organization_id`,`state`),
  CONSTRAINT `fk_xdecaroorganizations_affiliation_org`
    FOREIGN KEY (`organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_xdecaroorganizations_affiliation_target`
    FOREIGN KEY (`target_organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
