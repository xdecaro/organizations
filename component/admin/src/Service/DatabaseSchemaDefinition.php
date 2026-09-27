<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use RuntimeException;

final class DatabaseSchemaDefinition
{
    public const FUNCTIONAL_TABLES = [
        '#__xdecaroorganizations_organizations',
        '#__xdecaroorganizations_bodies',
        '#__xdecaroorganizations_appointments',
        '#__xdecaroorganizations_delegations',
        '#__xdecaroorganizations_affiliations',
    ];

    public const MAINTENANCE_TABLES = [
        '#__xdecaroorganizations_backups',
        '#__xdecaroorganizations_maintenance_log',
    ];

    private const TABLES = [
        '#__xdecaroorganizations_organizations' => [
            'role' => 'functional',
            'columns' => [
                'id' => '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'uuid' => '`uuid` CHAR(36) NOT NULL',
                'name' => '`name` VARCHAR(255) NOT NULL',
                'legal_name' => '`legal_name` VARCHAR(255) DEFAULT NULL',
                'code' => '`code` VARCHAR(100) DEFAULT NULL',
                'type' => "`type` VARCHAR(50) NOT NULL DEFAULT 'organization'",
                'structure_level' => "`structure_level` VARCHAR(32) NOT NULL DEFAULT 'unspecified'",
                'territory_type' => '`territory_type` VARCHAR(32) DEFAULT NULL',
                'territory_name' => '`territory_name` VARCHAR(190) DEFAULT NULL',
                'operational_status' => "`operational_status` VARCHAR(32) NOT NULL DEFAULT 'active'",
                'status_since' => '`status_since` DATE DEFAULT NULL',
                'autonomy_legal' => '`autonomy_legal` TINYINT(1) NOT NULL DEFAULT 0',
                'autonomy_management' => '`autonomy_management` TINYINT(1) NOT NULL DEFAULT 0',
                'autonomy_administrative' => '`autonomy_administrative` TINYINT(1) NOT NULL DEFAULT 0',
                'autonomy_tax' => '`autonomy_tax` TINYINT(1) NOT NULL DEFAULT 0',
                'autonomy_fiscal' => '`autonomy_fiscal` TINYINT(1) NOT NULL DEFAULT 0',
                'appointment_membership_requirement' => "`appointment_membership_requirement` VARCHAR(32) NOT NULL DEFAULT 'inherit'",
                'parent_id' => '`parent_id` INT UNSIGNED DEFAULT NULL',
                'vat_id' => '`vat_id` VARCHAR(64) DEFAULT NULL',
                'tax_identifier' => '`tax_identifier` VARCHAR(64) DEFAULT NULL',
                'email' => '`email` VARCHAR(254) DEFAULT NULL',
                'pec_email' => '`pec_email` VARCHAR(254) DEFAULT NULL',
                'phone' => '`phone` VARCHAR(50) DEFAULT NULL',
                'website' => '`website` VARCHAR(512) DEFAULT NULL',
                'facebook_url' => '`facebook_url` VARCHAR(512) DEFAULT NULL',
                'instagram_url' => '`instagram_url` VARCHAR(512) DEFAULT NULL',
                'youtube_url' => '`youtube_url` VARCHAR(512) DEFAULT NULL',
                'linkedin_url' => '`linkedin_url` VARCHAR(512) DEFAULT NULL',
                'tiktok_url' => '`tiktok_url` VARCHAR(512) DEFAULT NULL',
                'address_line' => '`address_line` VARCHAR(255) DEFAULT NULL',
                'postal_code' => '`postal_code` VARCHAR(32) DEFAULT NULL',
                'city' => '`city` VARCHAR(190) DEFAULT NULL',
                'province' => '`province` VARCHAR(190) DEFAULT NULL',
                'region' => '`region` VARCHAR(190) DEFAULT NULL',
                'country_code' => '`country_code` CHAR(2) DEFAULT NULL',
                'language' => "`language` VARCHAR(16) NOT NULL DEFAULT '*'",
                'logo' => '`logo` VARCHAR(512) DEFAULT NULL',
                'notes' => '`notes` TEXT DEFAULT NULL',
                'state' => '`state` TINYINT NOT NULL DEFAULT 1',
                'access' => '`access` INT UNSIGNED NOT NULL DEFAULT 1',
                'created' => '`created` DATETIME NOT NULL',
                'created_by' => '`created_by` INT UNSIGNED NOT NULL DEFAULT 0',
                'modified' => '`modified` DATETIME DEFAULT NULL',
                'modified_by' => '`modified_by` INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'primary' => 'PRIMARY KEY (`id`)',
            'unique_indexes' => ['idx_org_uuid' => 'UNIQUE KEY `idx_org_uuid` (`uuid`)'],
            'indexes' => [
                'idx_org_parent' => 'KEY `idx_org_parent` (`parent_id`)',
                'idx_org_name' => 'KEY `idx_org_name` (`name`)',
                'idx_org_code' => 'KEY `idx_org_code` (`code`)',
                'idx_org_vat' => 'KEY `idx_org_vat` (`vat_id`)',
                'idx_org_tax' => 'KEY `idx_org_tax` (`tax_identifier`)',
                'idx_org_type' => 'KEY `idx_org_type` (`type`)',
                'idx_org_structure_level' => 'KEY `idx_org_structure_level` (`structure_level`)',
                'idx_org_operational_status' => 'KEY `idx_org_operational_status` (`operational_status`)',
                'idx_org_state_access' => 'KEY `idx_org_state_access` (`state`,`access`)',
            ],
            'constraints' => [
                'fk_xdecaroorganizations_parent' => 'CONSTRAINT `fk_xdecaroorganizations_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE SET NULL',
            ],
        ],
        '#__xdecaroorganizations_bodies' => [
            'role' => 'functional',
            'columns' => [
                'id' => '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'uuid' => '`uuid` CHAR(36) NOT NULL',
                'organization_id' => '`organization_id` INT UNSIGNED NOT NULL', 'parent_id' => '`parent_id` INT UNSIGNED DEFAULT NULL',
                'name' => '`name` VARCHAR(190) NOT NULL', 'code' => '`code` VARCHAR(100) DEFAULT NULL',
                'body_type' => "`body_type` VARCHAR(50) NOT NULL DEFAULT 'other'", 'starts_on' => '`starts_on` DATE DEFAULT NULL',
                'ends_on' => '`ends_on` DATE DEFAULT NULL', 'notes' => '`notes` TEXT DEFAULT NULL', 'state' => '`state` TINYINT NOT NULL DEFAULT 1',
                'created' => '`created` DATETIME NOT NULL', 'created_by' => '`created_by` INT UNSIGNED NOT NULL DEFAULT 0',
                'modified' => '`modified` DATETIME DEFAULT NULL', 'modified_by' => '`modified_by` INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'primary' => 'PRIMARY KEY (`id`)',
            'unique_indexes' => ['idx_body_uuid' => 'UNIQUE KEY `idx_body_uuid` (`uuid`)'],
            'indexes' => [
                'idx_body_org' => 'KEY `idx_body_org` (`organization_id`)', 'idx_body_parent' => 'KEY `idx_body_parent` (`parent_id`)',
                'idx_body_type' => 'KEY `idx_body_type` (`body_type`)', 'idx_body_org_state' => 'KEY `idx_body_org_state` (`organization_id`,`state`)',
            ],
            'constraints' => [
                'fk_xdecaroorganizations_body_org' => 'CONSTRAINT `fk_xdecaroorganizations_body_org` FOREIGN KEY (`organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE',
                'fk_xdecaroorganizations_body_parent' => 'CONSTRAINT `fk_xdecaroorganizations_body_parent` FOREIGN KEY (`parent_id`) REFERENCES `#__xdecaroorganizations_bodies` (`id`) ON DELETE SET NULL',
            ],
        ],
        '#__xdecaroorganizations_appointments' => [
            'role' => 'functional',
            'columns' => [
                'id' => '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'uuid' => '`uuid` CHAR(36) NOT NULL',
                'organization_id' => '`organization_id` INT UNSIGNED NOT NULL', 'body_id' => '`body_id` INT UNSIGNED DEFAULT NULL',
                'person_uuid' => '`person_uuid` CHAR(36) NOT NULL', 'person_name_snapshot' => '`person_name_snapshot` VARCHAR(255) NOT NULL',
                'role_code' => '`role_code` VARCHAR(50) NOT NULL', 'role_custom' => '`role_custom` VARCHAR(190) DEFAULT NULL',
                'starts_on' => '`starts_on` DATE NOT NULL', 'planned_ends_on' => '`planned_ends_on` DATE DEFAULT NULL',
                'ended_on' => '`ended_on` DATE DEFAULT NULL', 'end_reason' => '`end_reason` VARCHAR(50) DEFAULT NULL',
                'end_note' => '`end_note` TEXT DEFAULT NULL', 'notes' => '`notes` TEXT DEFAULT NULL',
                'show_on_frontend' => '`show_on_frontend` TINYINT(1) NOT NULL DEFAULT 0', 'state' => '`state` TINYINT NOT NULL DEFAULT 1',
                'created' => '`created` DATETIME NOT NULL', 'created_by' => '`created_by` INT UNSIGNED NOT NULL DEFAULT 0',
                'modified' => '`modified` DATETIME DEFAULT NULL', 'modified_by' => '`modified_by` INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'primary' => 'PRIMARY KEY (`id`)',
            'unique_indexes' => ['idx_appointment_uuid' => 'UNIQUE KEY `idx_appointment_uuid` (`uuid`)'],
            'indexes' => [
                'idx_appointment_org' => 'KEY `idx_appointment_org` (`organization_id`)', 'idx_appointment_body' => 'KEY `idx_appointment_body` (`body_id`)',
                'idx_appointment_person' => 'KEY `idx_appointment_person` (`person_uuid`)',
                'idx_appointment_org_dates' => 'KEY `idx_appointment_org_dates` (`organization_id`,`starts_on`,`planned_ends_on`)',
                'idx_appointment_state' => 'KEY `idx_appointment_state` (`state`)',
            ],
            'constraints' => [
                'fk_xdecaroorganizations_appointment_org' => 'CONSTRAINT `fk_xdecaroorganizations_appointment_org` FOREIGN KEY (`organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE',
                'fk_xdecaroorganizations_appointment_body' => 'CONSTRAINT `fk_xdecaroorganizations_appointment_body` FOREIGN KEY (`body_id`) REFERENCES `#__xdecaroorganizations_bodies` (`id`) ON DELETE SET NULL',
            ],
        ],
        '#__xdecaroorganizations_delegations' => [
            'role' => 'functional',
            'columns' => [
                'id' => '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'uuid' => '`uuid` CHAR(36) NOT NULL',
                'organization_id' => '`organization_id` INT UNSIGNED NOT NULL', 'appointment_id' => '`appointment_id` INT UNSIGNED NOT NULL',
                'title' => '`title` VARCHAR(190) NOT NULL', 'scope' => '`scope` TEXT DEFAULT NULL', 'starts_on' => '`starts_on` DATE NOT NULL',
                'ends_on' => '`ends_on` DATE DEFAULT NULL', 'notes' => '`notes` TEXT DEFAULT NULL', 'state' => '`state` TINYINT NOT NULL DEFAULT 1',
                'created' => '`created` DATETIME NOT NULL', 'created_by' => '`created_by` INT UNSIGNED NOT NULL DEFAULT 0',
                'modified' => '`modified` DATETIME DEFAULT NULL', 'modified_by' => '`modified_by` INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'primary' => 'PRIMARY KEY (`id`)',
            'unique_indexes' => ['idx_delegation_uuid' => 'UNIQUE KEY `idx_delegation_uuid` (`uuid`)'],
            'indexes' => [
                'idx_delegation_org' => 'KEY `idx_delegation_org` (`organization_id`)',
                'idx_delegation_appointment' => 'KEY `idx_delegation_appointment` (`appointment_id`)',
                'idx_delegation_org_dates' => 'KEY `idx_delegation_org_dates` (`organization_id`,`starts_on`,`ends_on`)',
                'idx_delegation_state' => 'KEY `idx_delegation_state` (`state`)',
            ],
            'constraints' => [
                'fk_xdecaroorganizations_delegation_org' => 'CONSTRAINT `fk_xdecaroorganizations_delegation_org` FOREIGN KEY (`organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE',
                'fk_xdecaroorganizations_delegation_appointment' => 'CONSTRAINT `fk_xdecaroorganizations_delegation_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `#__xdecaroorganizations_appointments` (`id`) ON DELETE RESTRICT',
            ],
        ],
        '#__xdecaroorganizations_affiliations' => [
            'role' => 'functional',
            'columns' => [
                'id' => '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT', 'uuid' => '`uuid` CHAR(36) NOT NULL',
                'organization_id' => '`organization_id` INT UNSIGNED NOT NULL', 'target_organization_id' => '`target_organization_id` INT UNSIGNED NOT NULL',
                'relation_type' => "`relation_type` VARCHAR(50) NOT NULL DEFAULT 'sports_affiliation'", 'relation_code' => '`relation_code` VARCHAR(100) DEFAULT NULL',
                'starts_on' => '`starts_on` DATE DEFAULT NULL', 'ends_on' => '`ends_on` DATE DEFAULT NULL',
                'status' => "`status` VARCHAR(32) NOT NULL DEFAULT 'active'", 'notes' => '`notes` TEXT DEFAULT NULL', 'state' => '`state` TINYINT NOT NULL DEFAULT 1',
                'created' => '`created` DATETIME NOT NULL', 'created_by' => '`created_by` INT UNSIGNED NOT NULL DEFAULT 0',
                'modified' => '`modified` DATETIME DEFAULT NULL', 'modified_by' => '`modified_by` INT UNSIGNED NOT NULL DEFAULT 0',
            ],
            'primary' => 'PRIMARY KEY (`id`)',
            'unique_indexes' => ['idx_affiliation_uuid' => 'UNIQUE KEY `idx_affiliation_uuid` (`uuid`)'],
            'indexes' => [
                'idx_affiliation_org' => 'KEY `idx_affiliation_org` (`organization_id`)', 'idx_affiliation_target' => 'KEY `idx_affiliation_target` (`target_organization_id`)',
                'idx_affiliation_type' => 'KEY `idx_affiliation_type` (`relation_type`)', 'idx_affiliation_status' => 'KEY `idx_affiliation_status` (`status`)',
                'idx_affiliation_org_state' => 'KEY `idx_affiliation_org_state` (`organization_id`,`state`)',
            ],
            'constraints' => [
                'fk_xdecaroorganizations_affiliation_org' => 'CONSTRAINT `fk_xdecaroorganizations_affiliation_org` FOREIGN KEY (`organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE',
                'fk_xdecaroorganizations_affiliation_target' => 'CONSTRAINT `fk_xdecaroorganizations_affiliation_target` FOREIGN KEY (`target_organization_id`) REFERENCES `#__xdecaroorganizations_organizations` (`id`) ON DELETE CASCADE',
            ],
        ],
        '#__xdecaroorganizations_backups' => [
            'role' => 'maintenance',
            'columns' => [
                'id' => '`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT', 'uuid' => '`uuid` CHAR(36) NOT NULL',
                'filename' => '`filename` VARCHAR(255) NOT NULL', 'storage_path' => '`storage_path` VARCHAR(1024) NOT NULL',
                'sha256' => '`sha256` CHAR(64) NOT NULL', 'size_bytes' => '`size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0',
                'organizations_count' => '`organizations_count` INT UNSIGNED NOT NULL DEFAULT 0',
                'bodies_count' => '`bodies_count` INT UNSIGNED NOT NULL DEFAULT 0',
                'appointments_count' => '`appointments_count` INT UNSIGNED NOT NULL DEFAULT 0',
                'delegations_count' => '`delegations_count` INT UNSIGNED NOT NULL DEFAULT 0',
                'affiliations_count' => '`affiliations_count` INT UNSIGNED NOT NULL DEFAULT 0',
                'component_version' => '`component_version` VARCHAR(32) NOT NULL', 'schema_version' => '`schema_version` VARCHAR(32) NOT NULL',
                'created' => '`created` DATETIME NOT NULL', 'created_by' => '`created_by` INT UNSIGNED NOT NULL DEFAULT 0',
                'status' => "`status` VARCHAR(32) NOT NULL DEFAULT 'ready'",
            ],
            'primary' => 'PRIMARY KEY (`id`)',
            'unique_indexes' => ['idx_org_backup_uuid' => 'UNIQUE KEY `idx_org_backup_uuid` (`uuid`)'],
            'indexes' => ['idx_org_backup_created' => 'KEY `idx_org_backup_created` (`created`)', 'idx_org_backup_status' => 'KEY `idx_org_backup_status` (`status`)'],
            'constraints' => [],
        ],
        '#__xdecaroorganizations_maintenance_log' => [
            'role' => 'maintenance',
            'columns' => [
                'id' => '`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT', 'action' => '`action` VARCHAR(64) NOT NULL',
                'subject_uuid' => '`subject_uuid` CHAR(36) DEFAULT NULL', 'actor_user_id' => '`actor_user_id` INT UNSIGNED NOT NULL DEFAULT 0',
                'created' => '`created` DATETIME NOT NULL', 'metadata' => '`metadata` LONGTEXT DEFAULT NULL',
            ],
            'primary' => 'PRIMARY KEY (`id`)',
            'unique_indexes' => [],
            'indexes' => [
                'idx_org_maintenance_action_created' => 'KEY `idx_org_maintenance_action_created` (`action`,`created`)',
                'idx_org_maintenance_subject_created' => 'KEY `idx_org_maintenance_subject_created` (`subject_uuid`,`created`)',
                'idx_org_maintenance_actor' => 'KEY `idx_org_maintenance_actor` (`actor_user_id`)',
            ],
            'constraints' => [],
        ],
    ];

    public function tables(): array { return self::TABLES; }
    public function functionalTables(): array { return self::FUNCTIONAL_TABLES; }
    public function maintenanceTables(): array { return self::MAINTENANCE_TABLES; }

    public function table(string $table): array
    {
        if (!isset(self::TABLES[$table])) {
            throw new RuntimeException('Unknown Organizations canonical table: ' . $table);
        }
        return self::TABLES[$table];
    }

    public function createTableSql(string $table): string
    {
        $spec = $this->table($table);
        $parts = array_values($spec['columns']);
        $parts[] = $spec['primary'];
        $parts = array_merge($parts, array_values($spec['unique_indexes']), array_values($spec['indexes']), array_values($spec['constraints']));
        return "CREATE TABLE IF NOT EXISTS `{$table}` (\n  " . implode(",\n  ", $parts) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    public function legacyRemovals(): array { return []; }
}
