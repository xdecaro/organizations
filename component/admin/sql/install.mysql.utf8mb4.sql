CREATE TABLE IF NOT EXISTS `#__xdecaroorganizations_organizations` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL, `name` VARCHAR(255) NOT NULL, `legal_name` VARCHAR(255) DEFAULT NULL, `code` VARCHAR(100) DEFAULT NULL, `type` VARCHAR(50) NOT NULL DEFAULT 'organization', `parent_id` INT UNSIGNED DEFAULT NULL, `vat_id` VARCHAR(64) DEFAULT NULL, `tax_identifier` VARCHAR(64) DEFAULT NULL, `email` VARCHAR(254) DEFAULT NULL, `pec_email` VARCHAR(254) DEFAULT NULL, `phone` VARCHAR(50) DEFAULT NULL, `website` VARCHAR(512) DEFAULT NULL, `address_line` VARCHAR(255) DEFAULT NULL, `postal_code` VARCHAR(32) DEFAULT NULL, `city` VARCHAR(190) DEFAULT NULL, `region` VARCHAR(190) DEFAULT NULL, `country_code` CHAR(2) DEFAULT NULL, `language` VARCHAR(16) DEFAULT NULL, `logo` VARCHAR(512) DEFAULT NULL, `notes` TEXT DEFAULT NULL, `state` TINYINT NOT NULL DEFAULT 1, `access` INT UNSIGNED NOT NULL DEFAULT 1, `created` DATETIME NOT NULL, `created_by` INT UNSIGNED NOT NULL DEFAULT 0, `modified` DATETIME DEFAULT NULL, `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`), UNIQUE KEY `idx_org_uuid` (`uuid`), KEY `idx_org_parent` (`parent_id`), KEY `idx_org_name` (`name`), KEY `idx_org_code` (`code`), KEY `idx_org_vat` (`vat_id`), KEY `idx_org_tax` (`tax_identifier`), KEY `idx_org_type` (`type`), KEY `idx_org_state_access` (`state`,`access`), CONSTRAINT `fk_xdecaroorganizations_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecaroorganizations_appointments` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `uuid` CHAR(36) NOT NULL,
 `organization_id` INT UNSIGNED NOT NULL,
 `person_uuid` CHAR(36) NOT NULL,
 `person_name_snapshot` VARCHAR(255) NOT NULL,
 `role_code` VARCHAR(50) NOT NULL,
 `role_custom` VARCHAR(190) DEFAULT NULL,
 `starts_on` DATE NOT NULL,
 `planned_ends_on` DATE DEFAULT NULL,
 `ended_on` DATE DEFAULT NULL,
 `end_reason` VARCHAR(50) DEFAULT NULL,
 `end_note` TEXT DEFAULT NULL,
 `notes` TEXT DEFAULT NULL,
 `state` TINYINT NOT NULL DEFAULT 1,
 `created` DATETIME NOT NULL,
 `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
 `modified` DATETIME DEFAULT NULL,
 `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `idx_appointment_uuid` (`uuid`),
 KEY `idx_appointment_org` (`organization_id`),
 KEY `idx_appointment_person` (`person_uuid`),
 KEY `idx_appointment_org_dates` (`organization_id`,`starts_on`,`planned_ends_on`),
 KEY `idx_appointment_state` (`state`),
 CONSTRAINT `fk_xdecaroorganizations_appointment_org` FOREIGN KEY (`organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
