CREATE TABLE IF NOT EXISTS `#__xdecaroorganizations_bodies` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `uuid` CHAR(36) NOT NULL,
 `organization_id` INT UNSIGNED NOT NULL,
 `parent_id` INT UNSIGNED DEFAULT NULL,
 `name` VARCHAR(190) NOT NULL,
 `code` VARCHAR(100) DEFAULT NULL,
 `body_type` VARCHAR(50) NOT NULL DEFAULT 'other',
 `starts_on` DATE DEFAULT NULL,
 `ends_on` DATE DEFAULT NULL,
 `notes` TEXT DEFAULT NULL,
 `state` TINYINT NOT NULL DEFAULT 1,
 `created` DATETIME NOT NULL,
 `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
 `modified` DATETIME DEFAULT NULL,
 `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `idx_body_uuid` (`uuid`),
 KEY `idx_body_org` (`organization_id`),
 KEY `idx_body_parent` (`parent_id`),
 KEY `idx_body_type` (`body_type`),
 KEY `idx_body_org_state` (`organization_id`,`state`),
 CONSTRAINT `fk_xdecaroorganizations_body_org` FOREIGN KEY (`organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE,
 CONSTRAINT `fk_xdecaroorganizations_body_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__xdecaroorganizations_bodies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__xdecaroorganizations_appointments`
  ADD COLUMN `body_id` INT UNSIGNED DEFAULT NULL AFTER `organization_id`,
  ADD KEY `idx_appointment_body` (`body_id`),
  ADD CONSTRAINT `fk_xdecaroorganizations_appointment_body`
    FOREIGN KEY (`body_id`) REFERENCES `#__xdecaroorganizations_bodies` (`id`) ON DELETE SET NULL;
