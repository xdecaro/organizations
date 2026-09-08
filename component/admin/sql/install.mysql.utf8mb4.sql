CREATE TABLE IF NOT EXISTS `#__xdecaroorganizations_organizations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'organization',
  `parent_id` int unsigned DEFAULT NULL,
  `state` tinyint NOT NULL DEFAULT 1,
  `access` int unsigned NOT NULL DEFAULT 1,
  `created` datetime NOT NULL,
  `created_by` int unsigned NOT NULL DEFAULT 0,
  `modified` datetime DEFAULT NULL,
  `modified_by` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `idx_uuid` (`uuid`), KEY `idx_parent` (`parent_id`), KEY `idx_state_access` (`state`,`access`),
  CONSTRAINT `fk_xdecaroorganizations_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
